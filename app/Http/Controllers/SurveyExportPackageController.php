<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\Question;
use App\Models\Response;
use App\Models\Answer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SurveyExportPackageController extends Controller
{
    use AuthorizesRequests;

    // ─────────────────────────────────────────────────────────────────────
    // 1. Export .kdsurvey ZIP bundle (portable JSON)
    // ─────────────────────────────────────────────────────────────────────

    public function exportPackage(Survey $survey)
    {
        $this->authorize('view', $survey);

        // 1. Extract questions (from questions table or json_schema)
        $dbQuestions = $survey->questions()->orderBy('position')->get();
        $questions = [];

        if ($dbQuestions->isNotEmpty()) {
            $questions = $dbQuestions->map(fn($q) => [
                'id' => (string) $q->id,
                'name' => 'question_' . $q->id,
                'text' => $q->text,
                'type' => $q->type,
                'options' => $q->options,
                'required' => (bool) $q->required,
                'position' => $q->position,
            ])->toArray();
        } else {
            $schema = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : ($survey->json_schema ?? []);
            if (is_array($schema)) {
                $pos = 1;
                foreach ($schema as $field) {
                    if (!isset($field['name']) || in_array($field['type'] ?? '', ['header', 'paragraph', 'hidden_field', 'group'])) {
                        continue;
                    }
                    $questions[] = [
                        'id' => $field['name'],
                        'name' => $field['name'],
                        'text' => $field['label'] ?? $field['name'],
                        'type' => $field['type'] ?? 'text',
                        'options' => $field['values'] ?? $field['options'] ?? [],
                        'required' => (bool) ($field['required'] ?? false),
                        'position' => $pos++,
                    ];
                }
            }
        }

        // 2. Extract responses and normalize answers
        $dbResponses = $survey->responses()->with('answers')->get();
        $responses = $dbResponses->map(function ($r) {
            $answersList = [];
            $dataMap = [];

            foreach ($r->answers as $a) {
                if ($a->value !== null && $a->value !== '') {
                    $decoded = json_decode($a->value, true);
                    if (is_array($decoded) && isset($decoded[0]['name'])) {
                        // Dynamic schema JSON array of {name, userData}
                        foreach ($decoded as $item) {
                            $fName = $item['name'] ?? null;
                            $uData = $item['userData'] ?? null;
                            if ($fName) {
                                $answersList[] = [
                                    'name' => $fName,
                                    'value' => $uData,
                                ];
                                $dataMap[$fName] = $uData;
                            }
                        }
                    } else {
                        // Standard question_id answer
                        $qKey = $a->question_id ? 'question_' . $a->question_id : 'answer_' . $a->id;
                        $answersList[] = [
                            'question_id' => $a->question_id,
                            'name' => $qKey,
                            'value' => $a->value,
                        ];
                        $dataMap[$qKey] = $a->value;
                    }
                }
            }

            return [
                'id' => $r->id,
                'guest_name' => $r->guest_name,
                'respondent_email' => $r->respondent?->email,
                'created_at' => $r->created_at?->toIso8601String(),
                'answers' => $answersList,
                'data' => $dataMap,
                'ai_metadata' => $r->ai_metadata,
            ];
        })->toArray();

        // 3. Survey metadata bundle
        $surveyData = [
            'id' => $survey->id,
            'title' => $survey->title,
            'description' => $survey->description,
            'category' => $survey->category?->value ?? (string) $survey->category,
            'status' => $survey->status?->value ?? (string) $survey->status,
            'json_schema' => is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : ($survey->json_schema ?? []),
            'export_org_name' => $survey->export_org_name,
            'remove_kd_branding' => (bool) $survey->remove_kd_branding,
            'exported_at' => now()->toIso8601String(),
            'exported_by' => Auth::user()?->name ?? 'System',
            'response_count' => count($responses),
        ];

        $readme = "KDAnalytiks Data Package (.kdsurvey)\n"
            . "====================================\n"
            . "Survey Title: {$survey->title}\n"
            . "Exported At : " . now()->format('Y-m-d H:i:s') . "\n"
            . "Total Responses: " . count($responses) . "\n\n"
            . "File Structure:\n"
            . "- survey.json    : Survey metadata, category, and full schema\n"
            . "- questions.json : Normalized list of questions/variables\n"
            . "- responses.json : Anonymized responses and mapped data points\n\n"
            . "To re-import this survey or append its data:\n"
            . "Upload this file via the Survey Import menu.\n";

        // Build ZIP in temporary storage
        $zipPath = storage_path('app/exports/' . Str::uuid() . '.zip');
        @mkdir(dirname($zipPath), 0777, true);

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('survey.json', json_encode($surveyData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('questions.json', json_encode($questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('responses.json', json_encode($responses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('README.txt', $readme);
        $zip->close();

        $filename = Str::slug($survey->title ?: 'survey') . '_export.kdsurvey';

        return response()->download($zipPath, $filename, [
            'Content-Type' => 'application/octet-stream',
        ])->deleteFileAfterSend(true);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 2. Export SPSS .sav round-trip
    // ─────────────────────────────────────────────────────────────────────

    public function exportSpssPackage(Survey $survey)
    {
        $this->authorize('view', $survey);

        $dbQuestions = $survey->questions()->orderBy('position')->get();
        $responses = $survey->responses()->with('answers')->get();

        // 1. Build variable definitions from DB questions or json_schema
        $fieldDefs = [];
        if ($dbQuestions->isNotEmpty()) {
            foreach ($dbQuestions as $q) {
                $fieldDefs[] = [
                    'key' => 'question_' . $q->id,
                    'question_id' => $q->id,
                    'label' => $q->text,
                    'type' => $q->type,
                    'options' => $q->options ?? [],
                ];
            }
        } else {
            $schema = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : ($survey->json_schema ?? []);
            if (is_array($schema)) {
                foreach ($schema as $f) {
                    if (!isset($f['name']) || in_array($f['type'] ?? '', ['header', 'paragraph', 'hidden_field', 'group'])) {
                        continue;
                    }
                    $fieldDefs[] = [
                        'key' => $f['name'],
                        'question_id' => null,
                        'label' => $f['label'] ?? $f['name'],
                        'type' => $f['type'] ?? 'text',
                        'options' => $f['values'] ?? $f['options'] ?? [],
                    ];
                }
            }
        }

        // Fallback if no questions/schema exist
        if (empty($fieldDefs)) {
            $fieldDefs[] = [
                'key' => 'resp_id',
                'question_id' => null,
                'label' => 'Respondent ID',
                'type' => 'number',
                'options' => [],
            ];
        }

        // 2. Build variables data array for SPSS\Sav\Writer
        $variables = [];
        foreach ($fieldDefs as $i => $field) {
            $varName = 'VAR' . str_pad($i + 1, 5, '0', STR_PAD_LEFT);
            $label = mb_substr((string) $field['label'], 0, 120);

            $valueLabels = [];
            $valueMap = [];
            $isNumeric = false;
            $format = \SPSS\Sav\Variable::FORMAT_TYPE_A;
            $width = 255;
            $decimals = 0;

            if (!empty($field['options']) && is_array($field['options'])) {
                $isNumeric = true;
                $format = \SPSS\Sav\Variable::FORMAT_TYPE_F;
                $width = 8;
                $decimals = 0;

                foreach (array_values($field['options']) as $idx => $opt) {
                    $code = $idx + 1;
                    $optLabel = is_array($opt) ? ($opt['label'] ?? $opt['value'] ?? (string) $opt) : (string) $opt;
                    $valKey = is_array($opt) ? ($opt['value'] ?? $optLabel) : (string) $opt;

                    $valueLabels[$code] = mb_substr($optLabel, 0, 120);
                    $valueMap[$valKey] = $code;
                    $valueMap[$optLabel] = $code;
                }
            } elseif (in_array($field['type'], ['number', 'decimal', 'rating', 'scale'])) {
                $isNumeric = true;
                $format = \SPSS\Sav\Variable::FORMAT_TYPE_F;
                $width = 8;
                $decimals = ($field['type'] === 'decimal') ? 2 : 0;
            }

            // Collect data for each response
            $varData = [];
            foreach ($responses as $response) {
                $respData = [];
                $legacyMap = $response->answers->keyBy('question_id');

                foreach ($response->answers as $ans) {
                    if ($ans->value !== null && $ans->value !== '') {
                        $decoded = json_decode($ans->value, true);
                        if (is_array($decoded) && isset($decoded[0]['name'])) {
                            foreach ($decoded as $item) {
                                if (isset($item['name'])) {
                                    $respData[$item['name']] = $item['userData'] ?? '';
                                }
                            }
                        }
                    }
                }

                $val = '';
                $key = $field['key'];
                $qId = $field['question_id'];

                if ($qId && $legacyMap->has($qId)) {
                    $val = $legacyMap->get($qId)->value;
                } elseif (isset($respData[$key])) {
                    $val = $respData[$key];
                } elseif (isset($respData['question_' . $qId])) {
                    $val = $respData['question_' . $qId];
                }

                if (is_array($val)) {
                    $val = implode(', ', $val);
                }

                if ($isNumeric) {
                    if (!empty($valueMap) && isset($valueMap[(string) $val])) {
                        $varData[] = (int) $valueMap[(string) $val];
                    } elseif (is_numeric($val)) {
                        $varData[] = ($decimals > 0) ? (float) $val : (int) $val;
                    } else {
                        $varData[] = 0;
                    }
                } else {
                    $varData[] = mb_substr((string) $val, 0, 255);
                }
            }

            $varEntry = [
                'name' => $varName,
                'format' => $format,
                'width' => $width,
                'decimals' => $decimals,
                'label' => $label,
                'data' => $varData,
            ];

            if (!empty($valueLabels)) {
                $varEntry['values'] = $valueLabels;
            }

            $variables[] = $varEntry;
        }

        $spssData = [
            'header' => [
                'prodName' => '@(#) IBM SPSS STATISTICS',
                'layoutCode' => 2,
                'fileLabel' => mb_substr($survey->title ?: 'Survey Export', 0, 60),
                'weightIndex' => 0,
            ],
            'variables' => $variables,
        ];

        $writer = new \SPSS\Sav\Writer($spssData);

        $tmpPath = storage_path('app/exports/' . Str::uuid() . '.sav');
        @mkdir(dirname($tmpPath), 0777, true);
        $writer->save($tmpPath);

        $filename = Str::slug($survey->title ?: 'survey') . '_data.sav';

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/octet-stream',
        ])->deleteFileAfterSend(true);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 3. Export PDF Summary
    // ─────────────────────────────────────────────────────────────────────

    public function exportPdfSummary(Survey $survey)
    {
        $this->authorize('view', $survey);

        $dbQuestions = $survey->questions()->with('answers')->orderBy('position')->get();
        $summaryData = [];

        if ($dbQuestions->isNotEmpty()) {
            foreach ($dbQuestions as $question) {
                $frequencies = $question->answers->groupBy('value')->map->count()->sortDesc();
                $totalAnswers = $question->answers->count();

                $summaryData[] = [
                    'question' => (object) [
                        'id' => $question->id,
                        'text' => $question->text,
                        'type' => $question->type,
                    ],
                    'frequencies' => $frequencies,
                    'total' => $totalAnswers,
                ];
            }
        } else {
            $schema = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : ($survey->json_schema ?? []);
            $responses = $survey->responses()->with('answers')->get();

            if (is_array($schema)) {
                foreach ($schema as $field) {
                    if (!isset($field['name']) || in_array($field['type'] ?? '', ['header', 'paragraph', 'hidden_field', 'group'])) {
                        continue;
                    }

                    $fieldName = $field['name'];
                    $fieldLabel = $field['label'] ?? $fieldName;
                    $fieldType = $field['type'] ?? 'text';
                    $valuesList = [];

                    foreach ($responses as $r) {
                        foreach ($r->answers as $ans) {
                            $decoded = json_decode($ans->value ?? '', true);
                            if (is_array($decoded)) {
                                foreach ($decoded as $item) {
                                    if (($item['name'] ?? null) === $fieldName && isset($item['userData']) && $item['userData'] !== '') {
                                        $uData = $item['userData'];
                                        if (is_array($uData)) {
                                            foreach ($uData as $subVal) {
                                                $valuesList[] = (string) $subVal;
                                            }
                                        } else {
                                            $valuesList[] = (string) $uData;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // Map option codes to option labels if defined
                    $optMap = [];
                    if (!empty($field['values']) && is_array($field['values'])) {
                        foreach ($field['values'] as $v) {
                            if (is_array($v) && isset($v['value'])) {
                                $optMap[(string) $v['value']] = $v['label'] ?? $v['value'];
                            }
                        }
                    }

                    $frequencies = collect($valuesList)->map(function ($val) use ($optMap) {
                        return $optMap[$val] ?? $val;
                    })->countBy()->sortDesc();

                    $summaryData[] = [
                        'question' => (object) [
                            'id' => $fieldName,
                            'text' => $fieldLabel,
                            'type' => $fieldType,
                        ],
                        'frequencies' => $frequencies,
                        'total' => count($valuesList),
                    ];
                }
            }
        }

        // Convert export logo to base64 Data URI for robust DomPDF embedding
        $logoBase64 = null;
        if ($survey->export_logo_url) {
            $logoPath = storage_path('app/public/' . $survey->export_logo_url);
            if (file_exists($logoPath)) {
                $type = pathinfo($logoPath, PATHINFO_EXTENSION);
                $data = file_get_contents($logoPath);
                $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        }

        $pdf = Pdf::loadView('surveys.export_pdf_summary', [
            'survey' => $survey,
            'summaryData' => $summaryData,
            'logoBase64' => $logoBase64,
            'exportedAt' => now()->format('F j, Y'),
        ])->setPaper('a4');

        $filename = Str::slug($survey->title ?: 'survey') . '_summary.pdf';

        return $pdf->download($filename);
    }
}

