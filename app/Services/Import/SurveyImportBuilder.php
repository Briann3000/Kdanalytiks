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

class SurveyImportBuilder
{
    /**
     * Infer the question type from a variable definition.
     *
     * Rules:
     *   - No value_labels → 'text'
     *   - Has value_labels AND 3-7 numeric keys → 'rating' (Likert scale)
     *   - Has value_labels AND other count → 'select_one' (MCQ / select one)
     */
    public function inferType(array $variable): string
    {
        $valueLabels = $variable['value_labels'] ?? [];
        $typeFormat = strtolower($variable['type_format'] ?? $variable['type'] ?? '');

        if (str_contains($typeFormat, 'date') || str_contains($typeFormat, 'time')) {
            return 'date';
        }

        if (empty($valueLabels)) {
            if (str_contains($typeFormat, 'num') || str_contains($typeFormat, 'int') || str_contains($typeFormat, 'double') || str_contains($typeFormat, 'float')) {
                return (str_contains($typeFormat, 'float') || str_contains($typeFormat, 'double') || str_contains($typeFormat, 'dec')) ? 'decimal' : 'number';
            }
            return 'text';
        }

        $keys = array_keys($valueLabels);
        $isNumeric = count(array_filter($keys, 'is_numeric')) === count($keys);
        $count = count($valueLabels);

        if ($isNumeric && $count >= 3 && $count <= 7) {
            return 'rating';
        }

        return 'select_one';
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
                // Map var_index to existing question by position
                $existingQuestions = $survey->questions()->orderBy('position')->get();
                foreach ($includedColumns as $col) {
                    $idx = $col['var_index'];
                    if (isset($existingQuestions[$idx])) {
                        $questionMap[$idx] = $existingQuestions[$idx];
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
                    $question = $questionMap[$varIndex] ?? null;

                    if (!$question) {
                        continue;
                    }

                    $rawValue = $rowData[$varIndex] ?? null;

                    // Filter out SPSS SYSMIS float/string markers
                    if ($rawValue !== null) {
                        if (is_float($rawValue) && $rawValue <= -1e300) {
                            $rawValue = null;
                        } elseif (is_string($rawValue) && (str_contains($rawValue, '-1.797693') || str_contains($rawValue, 'E+308'))) {
                            $rawValue = null;
                        }
                    }

                    // Resolve value label if available, else use raw value
                    $valueLabels = $col['value_labels'] ?? [];
                    if ($rawValue === null || $rawValue === '') {
                        $value = '';
                    } else {
                        $value = isset($valueLabels[(string) $rawValue])
                            ? $valueLabels[(string) $rawValue]
                            : (string) $rawValue;
                    }

                    // Package into JSON array element
                    $answersJson[] = [
                        'name' => 'question_' . $question->id,
                        'userData' => $value,
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
