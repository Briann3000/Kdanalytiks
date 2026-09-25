<?php

namespace App\Services\Import;

use App\Models\Survey;
use App\Models\Question;
use App\Models\Response;
use App\Models\Answer;
use App\Enums\SurveyStatus;
use App\Enums\SurveyCategory;
use App\Enums\SurveyType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class SurveyImportBuilder
{
    use CleansImportValues;

    /**
     * Smartly infer the survey question type using:
     * 1. Variable labels and name keywords (e.g. Rate, Age, Lat/Long, Comments)
     * 2. Actual column data pattern analysis (numbers, decimals, dates, Likert scales, multi-select delimiters)
     * 3. Statistical distribution & cardinality (Binary/Radio vs Select vs Textarea vs Text)
     */
    public function inferType(array $variable, array $columnValues = []): string
    {
        $label = strtolower(trim((string) ($variable['label'] ?? '')));
        $name = strtolower(trim((string) ($variable['name'] ?? '')));
        $valueLabels = $variable['value_labels'] ?? [];
        $typeFormat = strtolower($variable['type_format'] ?? $variable['type'] ?? '');

        // 1. Check explicit format metadata (e.g. from SPSS or typed exports)
        if (str_contains($typeFormat, 'date') || str_contains($typeFormat, 'time')) {
            return 'date';
        }

        // 2. Extract non-blank column values
        $nonBlank = array_values(array_filter($columnValues, fn($v) => $v !== null && trim((string) $v) !== ''));

        // If no values provided, fallback to analyzing label & valueLabels
        if (empty($nonBlank)) {
            if (!empty($valueLabels)) {
                $count = count($valueLabels);
                $keys = array_keys($valueLabels);
                $isNumericKeys = count(array_filter($keys, 'is_numeric')) === count($keys);

                $likertPhrases = ['strongly agree', 'agree', 'neutral', 'disagree', 'strongly disagree', 'very satisfied', 'satisfied', 'dissatisfied', 'very dissatisfied', 'very good', 'good', 'fair', 'poor', 'very poor', 'excellent', 'never', 'rarely', 'sometimes', 'often', 'always'];
                $likertHits = 0;
                foreach ($valueLabels as $lbl) {
                    if (in_array(strtolower(trim((string) $lbl)), $likertPhrases)) {
                        $likertHits++;
                    }
                }

                if (($likertHits >= 2 || $isNumericKeys) && $count >= 3 && $count <= 7) {
                    return 'rating';
                }

                if ($count <= 6)
                    return 'radio';
                if ($count <= 50)
                    return 'select';
            }
            return 'text';
        }

        // 3. Keyword Heuristics on Question Label / Name
        // Geo-coordinates & Decimals
        if (
            preg_match('/\b(latitude|lat|longitude|long|lng|altitude|elevation|gps|coordinate|percentage|percent|margin|ratio|gpa|average|avg|rate_per)\b/i', $label) ||
            preg_match('/^(lat|latitude|long|longitude|lng|alt|altitude|elevation|gps|percentage|gpa)$/i', $name)
        ) {
            return 'decimal';
        }

        // Long Text / Textarea
        if (
            preg_match('/\b(comment|comments|feedback|remarks|suggestion|suggestions|explain|elaborate|describe|reason|narrative|opinion|why)\b/i', $label) ||
            preg_match('/(comment|feedback|remark|suggestion|explain|describe)/i', $name)
        ) {
            return 'textarea';
        }

        // Date detection by keyword
        if (
            preg_match('/\b(date|timestamp|dob|birthday|submission_date|start_date|end_date)\b/i', $label) ||
            preg_match('/(date|timestamp|dob|created_at)/i', $name)
        ) {
            return 'date';
        }

        // 4. Data Pattern Inspection
        $sampleCount = min(count($nonBlank), 200);
        $sample = array_slice($nonBlank, 0, $sampleCount);

        // Date inspection: test if majority of values are valid dates
        $dateMatchCount = 0;
        foreach ($sample as $val) {
            $strVal = trim((string) $val);
            if (
                preg_match('/^\d{4}[-\/\.]\d{1,2}[-\/\.]\d{1,2}/', $strVal) ||
                preg_match('/^\d{1,2}[-\/\.]\d{1,2}[-\/\.]\d{2,4}/', $strVal)
            ) {
                if (strtotime($strVal) !== false) {
                    $dateMatchCount++;
                }
            }
        }
        if ($dateMatchCount >= count($sample) * 0.7) {
            return 'date';
        }

        // Multi-select Checkbox detection: check for delimiters like commas / semicolons / pipes
        $delimitedCount = 0;
        foreach ($sample as $val) {
            $strVal = (string) $val;
            if (preg_match('/[,;|]/', $strVal) && !is_numeric($strVal)) {
                $delimitedCount++;
            }
        }
        if ($delimitedCount >= count($sample) * 0.4) {
            return 'checkbox';
        }

        // Numeric Inspection
        $numericCount = count(array_filter($sample, 'is_numeric'));
        $isMostlyNumeric = ($numericCount >= count($sample) * 0.9);

        if ($isMostlyNumeric) {
            $numericValues = array_map('floatval', array_filter($sample, 'is_numeric'));
            $hasDecimals = false;
            foreach ($sample as $v) {
                if (is_numeric($v) && str_contains((string) $v, '.') && !preg_match('/\.0+$/', (string) $v)) {
                    $hasDecimals = true;
                    break;
                }
            }

            if ($hasDecimals) {
                return 'decimal';
            }

            $min = min($numericValues);
            $max = max($numericValues);
            $uniqueInts = array_unique($numericValues);
            $uniqueCount = count($uniqueInts);

            // Likert rating scale check: integers with a small scale range (e.g. 1-5, 1-7, 1-10)
            $isLikertLabel = (bool) preg_match('/\b(rate|rating|scale|satisfaction|how satisfied|extent|level of|agreement|strongly)\b/i', $label);
            if ($isLikertLabel && $min >= 0 && $max <= 10) {
                return 'rating';
            }
            if ($min >= 1 && $max <= 5 && $uniqueCount <= 5) {
                return 'rating';
            }
            if ($min >= 1 && $max <= 7 && $uniqueCount <= 7) {
                return 'rating';
            }

            // General integer: Age, count, quantity, index
            return 'number';
        }

        // 5. Categorical String Inspection
        $uniqueValues = array_values(array_unique(array_map('trim', $sample)));
        $uniqueCount = count($uniqueValues);

        // Check for Likert scale wording in text values (e.g. Strongly Agree, Neutral, Disagree)
        $likertPhrases = ['strongly agree', 'agree', 'neutral', 'disagree', 'strongly disagree', 'very satisfied', 'satisfied', 'dissatisfied', 'very dissatisfied', 'very good', 'good', 'fair', 'poor', 'very poor', 'excellent', 'never', 'rarely', 'sometimes', 'often', 'always'];
        $likertHits = 0;
        foreach ($uniqueValues as $uVal) {
            if (in_array(strtolower($uVal), $likertPhrases)) {
                $likertHits++;
            }
        }
        if ($likertHits >= 2 && $uniqueCount <= 7) {
            return 'rating';
        }

        // Binary / Two options (e.g. Yes/No, Male/Female, True/False)
        if ($uniqueCount === 2) {
            return 'radio';
        }

        // 3 to 6 distinct options (e.g. Low/Medium/High, Wards with 3-6 items, Marital status)
        if ($uniqueCount >= 3 && $uniqueCount <= 6) {
            return 'radio';
        }

        // 7 to 50 distinct categorical options (e.g. 15 Wards, 47 Counties, 20 Departments)
        if ($uniqueCount >= 7 && $uniqueCount <= 50) {
            return 'select';
        }

        // High cardinality / Free-form text
        // Check average string length
        $totalLength = array_sum(array_map('strlen', $sample));
        $avgLength = $totalLength / max(count($sample), 1);
        if ($avgLength > 50) {
            return 'textarea';
        }

        return 'text';
    }

    /**
     * Determine if a column should be included by default (detect obvious metadata like system IDs/UUIDs).
     */
    public function shouldIncludeByDefault(array $variable): bool
    {
        $name = strtolower(trim((string) ($variable['name'] ?? '')));
        $label = strtolower(trim((string) ($variable['label'] ?? '')));

        // Standard metadata columns often present in survey tools (Kobo, ODK, Qualtrics, SurveyMonkey)
        $metadataPatterns = [
            '/^(_id|_uuid|uuid|instanceid|instance_id|submission_id)$/i',
            '/^(_submission_time|_submitted_by|_status|_version_)$/i',
            '/^(deviceid|phonenumber|simserial|subscriberid)$/i',
            '/^(meta:instanceid|meta:rootuuid)$/i',
        ];

        foreach ($metadataPatterns as $pattern) {
            if (preg_match($pattern, $name) || preg_match($pattern, $label)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Format raw cell values, converting Excel serial numbers for dates
     */
    public function formatCellValue(mixed $value, string $inferredType): mixed
    {
        if (is_numeric($value) && $inferredType === 'date') {
            try {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return $value;
            }
        }

        return $value;
    }

    /**
     * Build the options array for a question from the value_labels map.
     * Returns an array of ['label' => '...', 'value' => '...'] objects.
     */
    public function buildOptions(array $valueLabels): array
    {
        $options = [];
        foreach ($valueLabels as $code => $label) {
            $options[] = ['label' => $label, 'value' => (string) $code];
        }
        return $options;
    }

    /**
     * Create a Survey with Questions, Responses, and Answers from parsed import data.
     */
    public function build(
        string $title,
        string $importSource,
        array $mapping,
        array $rows,
        ?Survey $appendToSurvey = null
    ): Survey {
        return DB::transaction(function () use ($title, $importSource, $mapping, $rows, $appendToSurvey) {
            $user = Auth::user();
            $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;

            // --- 1. Create or reuse Survey ---
            if ($appendToSurvey) {
                $survey = $appendToSurvey;
            } else {
                $surveyData = [
                    'title' => $title,
                    'description' => 'Imported from ' . strtoupper($importSource) . ' data file.',
                    'status' => SurveyStatus::Active,
                    'type' => SurveyType::Invitation,
                    'category' => SurveyCategory::Academic,
                    'import_source' => $importSource,
                    'created_by' => $user->id,
                    'json_schema' => json_encode([]),
                    'share_token' => Str::random(32),
                ];

                // Assign to organization/independent entity to make it show up in active list
                if ($role === 'organization') {
                    $surveyData['organization_id'] = $user->organization?->id;
                } elseif ($role === 'independent') {
                    $surveyData['independent_id'] = $user->independent?->id;
                }

                $survey = Survey::create($surveyData);
            }

            // --- 2. Create Questions (only when building a new survey) ---
            $includedColumns = array_filter($mapping, fn($col) => $col['include'] ?? true);
            $questionMap = []; // var_index => Question model

            if (!$appendToSurvey) {
                $survey->questions()->delete(); // Ensure no pre-existing questions

                $position = 1;
                foreach ($includedColumns as $col) {
                    // Map visual type options
                    $type = $col['type'];
                    if ($type === 'radio')
                        $type = 'select_one';
                    if ($type === 'scale')
                        $type = 'rating';
                    if ($type === 'select')
                        $type = 'select'; // select dropdown

                    $question = Question::create([
                        'survey_id' => $survey->id,
                        'text' => $col['label'],
                        'type' => $type,
                        'options' => $col['options'] ?? [],
                        'required' => false,
                        'position' => $position++,
                    ]);
                    $questionMap[$col['var_index']] = $question;
                }

                // Update json_schema to match question structure expected by builder/reports
                $schemaQuestions = [];
                foreach ($survey->questions()->orderBy('position')->get() as $q) {
                    $schemaQuestions[] = [
                        'name' => 'question_' . $q->id,
                        'label' => $q->text,
                        'type' => $q->type,
                        'required' => $q->required,
                        'values' => $q->options ?? [],
                    ];
                }
                $survey->update(['json_schema' => json_encode($schemaQuestions)]);
            } else {
                // Map var_index to existing question or json_schema field
                $existingQuestions = $survey->questions()->get();
                $schema = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : ($survey->json_schema ?? []);

                foreach ($includedColumns as $col) {
                    $matchedQuestion = $existingQuestions->firstWhere('text', $col['label']);
                    if ($matchedQuestion) {
                        $questionMap[$col['var_index']] = [
                            'name' => 'question_' . $matchedQuestion->id,
                        ];
                    } elseif (is_array($schema)) {
                        $matchedField = collect($schema)->first(function ($f) use ($col) {
                            return ($f['label'] ?? '') === $col['label'] || ($f['name'] ?? '') === $col['label'];
                        });
                        if ($matchedField && isset($matchedField['name'])) {
                            $questionMap[$col['var_index']] = [
                                'name' => $matchedField['name'],
                            ];
                        }
                    }
                }
            }

            // --- 3. Create Responses & Answers (JSON formatted) ---
            foreach ($rows as $rowData) {
                $response = Response::create([
                    'survey_id' => $survey->id,
                    'respondent_id' => null,
                    'guest_name' => 'Imported Respondent',
                    'ai_metadata' => null,
                ]);

                $answersJson = [];

                foreach ($includedColumns as $col) {
                    $varIndex = $col['var_index'];
                    $fieldInfo = $questionMap[$varIndex] ?? null;

                    if (!$fieldInfo) {
                        continue;
                    }

                    $rawValue = $rowData[$varIndex] ?? null;

                    // Clean SPSS/Excel artifacts including #NULL!, SYSMIS markers, whitespace
                    $rawValue = $this->cleanValue($rawValue);

                    // If question is a date, format Excel serial if needed
                    if (($col['type'] ?? '') === 'date') {
                        $rawValue = $this->formatCellValue($rawValue, 'date');
                    }

                    // Resolve value label if available, else use raw value
                    $valueLabels = $col['value_labels'] ?? [];
                    if ($rawValue === null || $rawValue === '') {
                        $value = '';
                    } else {
                        $lookupKey = is_numeric($rawValue) ? (string) (int) $rawValue : (string) $rawValue;
                        $value = isset($valueLabels[$lookupKey])
                            ? $valueLabels[$lookupKey]
                            : (string) $rawValue;
                    }

                    $fieldName = is_array($fieldInfo) ? $fieldInfo['name'] : ('question_' . $fieldInfo->id);

                    // Package into JSON array element
                    $answersJson[] = [
                        'name' => $fieldName,
                        'userData' => $value,
                    ];
                }
                // Format as key-value JSON array or legacy list depending on report decoder
                $formattedAnswers = [];
                foreach ($answersJson as $qKey => $qVal) {
                    $formattedAnswers[] = [
                        'name' => $qKey,
                        'userData' => $qVal,
                    ];
                }

                // Save as a single JSON-encoded answer row matching the normal submission structure
                Answer::create([
                    'response_id' => $response->id,
                    'question_id' => null,
                    'value' => json_encode($answersJson),
                ]);
            }

            return $survey;
        });
    }
}
