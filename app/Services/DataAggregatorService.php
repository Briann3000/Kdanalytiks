<?php

namespace App\Services;

use App\Models\Survey;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

class DataAggregatorService
{
    /**
     * Aggregate all data for a survey into a structured format for AI analysis.
     */
    public function aggregate(Survey $survey)
    {
        $questions = $survey->questions()->get();
        $totalResponses = $survey->responses()->count();
        $isJson = !empty($survey->json_schema);

        $aggregatedData = [
            'survey_info' => [
                'title' => $survey->title,
                'description' => $survey->description,
                'total_responses' => $totalResponses,
            ],
            'questions' => []
        ];

        // If legacy questions table is empty, parse from JSON schema
        if ($questions->isEmpty() && $isJson) {
            $schema = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : $survey->json_schema;
            $schema = is_array($schema) ? $schema : [];

            $expandedSchema = [];
            foreach ($schema as $field) {
                if (!isset($field['name']) || in_array($field['type'], ['header', 'paragraph', 'group']))
                    continue;

                if (in_array($field['type'], ['likert_matrix_grid', 'likert_matrix'])) {
                    $rows = $field['rows'] ?? [];
                    foreach ($rows as $r) {
                        $rowVal = $r['value'] ?? '';
                        $rowLabel = $r['label'] ?? $rowVal;
                        $expandedSchema[] = (object) [
                            'id' => $field['name'] . '___' . $rowVal,
                            'name' => $field['name'] . '___' . $rowVal,
                            'type' => 'radio',
                            'label' => ($field['label'] ?? $field['name']) . ' - ' . $rowLabel,
                            'is_virtual_likert' => true,
                            'parent_likert_name' => $field['name'],
                            'likert_row_value' => $rowVal,
                            'columns' => $field['columns'] ?? [],
                        ];
                    }
                } else {
                    $expandedSchema[] = (object) [
                        'id' => $field['name'],
                        'name' => $field['name'],
                        'type' => $field['type'] ?? 'text',
                        'label' => $field['label'] ?? $field['name'],
                    ];
                }
            }

            foreach ($expandedSchema as $qObj) {
                $aggregatedData['questions'][] = $this->aggregateQuestionData($qObj, $totalResponses);
            }
        } else {
            foreach ($questions as $question) {
                $aggregatedData['questions'][] = $this->aggregateQuestionData($question, $totalResponses);
            }
        }

        return $aggregatedData;
    }

    /**
     * Aggregate data for a single question.
     */
    private function aggregateQuestionData($question, $totalResponses)
    {
        $data = [
            'id' => $question->id,
            'type' => $question->type,
            'label' => $question->label ?? $question->text ?? $question->name ?? 'Question',
            'stats' => [],
            'insights' => []
        ];

        // Try standard individual answers first
        if (in_array($question->type, ['select', 'radio-group', 'checkbox-group', 'starRating'])) {
            $data['stats'] = $this->getChoiceStats($question, $totalResponses);
        } elseif (in_array($question->type, ['text', 'textarea'])) {
            $data['insights'] = $this->getThematicInsights($question);
        }

        // If no stats/insights found, check JSON blob responses (common in this app)
        if (empty($data['stats']) && empty($data['insights'])) {
            $this->aggregateFromJsonBlobs($question, $data, $totalResponses);
        }

        return $data;
    }

    /**
     * For surveys using FormBuilder JSON storage.
     */
    private function aggregateFromJsonBlobs($question, &$data, $totalResponses)
    {
        // Find the "name" or identifier in the schema if applicable
        $name = isset($question->is_virtual_likert) ? $question->parent_likert_name : ($question->name ?? $question->id);

        $responses = DB::table('answers')
            ->whereNull('question_id')
            ->where('value', 'LIKE', '%"name":"' . $name . '"%')
            ->pluck('value');

        $frequencyCount = [];
        $textInsights = [];

        foreach ($responses as $jsonBlob) {
            $parsed = json_decode($jsonBlob, true) ?? [];
            foreach ($parsed as $entry) {
                if (isset($entry['name']) && $entry['name'] == $name && isset($entry['userData'])) {
                    $val = $entry['userData'];

                    if (isset($question->is_virtual_likert)) {
                        $matrixAnswers = is_string($val) ? json_decode($val, true) : $val;
                        if (is_array($matrixAnswers)) {
                            if (isset($matrixAnswers[0])) {
                                if (is_string($matrixAnswers[0])) {
                                    $decoded = json_decode($matrixAnswers[0], true);
                                    if (is_array($decoded)) {
                                        $matrixAnswers = $decoded;
                                    }
                                } elseif (is_array($matrixAnswers[0])) {
                                    $matrixAnswers = $matrixAnswers[0];
                                }
                            }
                            $rowKey = $question->likert_row_value;
                            $val = $matrixAnswers[$rowKey] ?? null;
                        } else {
                            $val = null;
                        }

                        // Map option value to option label for virtual likert columns
                        if ($val !== null && $val !== '') {
                            $colsDef = $question->columns ?? [];
                            $colLabel = collect($colsDef)->firstWhere('value', $val)['label'] ?? $val;
                            $val = $colLabel;
                        }
                    }

                    if ($val !== null && $val !== '') {
                        if (is_array($val)) {
                            foreach ($val as $v) {
                                $frequencyCount[$v] = ($frequencyCount[$v] ?? 0) + 1;
                            }
                        } else {
                            if (in_array($data['type'], ['text', 'textarea'])) {
                                $textInsights[] = $val;
                            } else {
                                $frequencyCount[$val] = ($frequencyCount[$val] ?? 0) + 1;
                            }
                        }
                    }
                }
            }
        }

        if (!empty($frequencyCount)) {
            foreach ($frequencyCount as $option => $count) {
                $data['stats'][] = [
                    'option' => $option,
                    'count' => $count,
                    'percentage' => $totalResponses > 0 ? round(($count / $totalResponses) * 100, 2) : 0
                ];
            }
        }

        if (!empty($textInsights)) {
            $data['insights'] = array_slice($textInsights, 0, 50);
        }
    }

    /**
     * Get frequency stats for choice-based questions.
     */
    private function getChoiceStats($question, $totalResponses)
    {
        $answers = DB::table('answers')
            ->where('question_id', $question->id)
            ->select('value', DB::raw('count(*) as count'))
            ->groupBy('value')
            ->get();

        return $answers->map(function ($answer) use ($totalResponses) {
            return [
                'option' => $answer->value,
                'count' => $answer->count,
                'percentage' => $totalResponses > 0 ? round(($answer->count / $totalResponses) * 100, 2) : 0
            ];
        })->toArray();
    }

    /**
     * Get a sample of text answers for qualitative analysis.
     */
    private function getThematicInsights($question)
    {
        return DB::table('answers')
            ->where('question_id', $question->id)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->latest()
            ->take(50)
            ->pluck('value')
            ->toArray();
    }
}
