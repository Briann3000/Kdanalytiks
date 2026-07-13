<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SurveyResponsesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $survey;
    protected $responses;
    protected $schemaFields = [];
    protected $maxRepeats = [];

    public function __construct($survey, $responses)
    {
        $this->survey = $survey;
        $this->responses = $responses;

        if (!empty($this->survey->json_schema) && $this->survey->json_schema !== '[]') {
            $this->schemaFields = is_string($this->survey->json_schema) ? json_decode($this->survey->json_schema, true) : $this->survey->json_schema;
            $this->scanForMaxRepeats();
        }
    }

    protected function scanForMaxRepeats()
    {
        foreach ($this->responses as $response) {
            $jsonAnswer = $response->answers->first();
            if (!$jsonAnswer)
                continue;
            $parsedData = json_decode($jsonAnswer->value, true) ?? [];
            foreach ($parsedData as $data) {
                if (isset($data['type']) && $data['type'] === 'repeat_container' && isset($data['userData'])) {
                    $fieldName = $data['name'];
                    $count = count($data['userData']);
                    $this->maxRepeats[$fieldName] = max($this->maxRepeats[$fieldName] ?? 0, $count);
                }
            }
        }
    }

    public function collection()
    {
        return $this->responses;
    }

    public function headings(): array
    {
        $headers = ['Response ID', 'Date', 'Respondent Email', 'Respondent Name'];

        if (!empty($this->schemaFields)) {
            foreach ($this->schemaFields as $field) {
                if (!isset($field['name']) || in_array($field['type'], ['header', 'paragraph', 'hidden_field', 'group']))
                    continue;

                $label = $field['label'] ?? $field['name'];

                if ($field['type'] === 'repeat_container') {
                    $max = $this->maxRepeats[$field['name']] ?? 1;
                    $innerFields = $field['fields'] ?? [];
                    for ($i = 1; $i <= $max; $i++) {
                        foreach ($innerFields as $inner) {
                            $headers[] = $label . " (Instance $i) - " . ($inner['label'] ?? $inner['name']);
                        }
                    }
                } elseif (in_array($field['type'], ['likert_matrix_grid', 'likert_matrix'])) {
                    $rows = $field['rows'] ?? [];
                    foreach ($rows as $row) {
                        $headers[] = $label . " [" . ($row['label'] ?? $row['value']) . "]";
                    }
                } else {
                    $headers[] = $label;
                }
            }
        } else {
            foreach ($this->survey->questions()->orderBy('position')->get() as $q) {
                $headers[] = $q->text;
            }
        }
        return $headers;
    }

    public function map($response): array
    {
        $row = [
            $response->id,
            $response->created_at->format('M d, Y h:i A'),
            $response->respondent ? $response->respondent->email : 'N/A',
            $response->respondent ? $response->respondent->name : 'Anonymous'
        ];

        if (!empty($this->schemaFields)) {
            $jsonAnswer = $response->answers->first();
            $parsedData = $jsonAnswer ? json_decode($jsonAnswer->value, true) : [];

            foreach ($this->schemaFields as $field) {
                if (!isset($field['name']) || in_array($field['type'], ['header', 'paragraph', 'hidden_field', 'group']))
                    continue;

                $fieldData = collect($parsedData)->firstWhere('name', $field['name']);
                $val = $fieldData['userData'] ?? '';

                if ($field['type'] === 'repeat_container') {
                    $max = $this->maxRepeats[$field['name']] ?? 1;
                    $instances = is_array($val) ? $val : [];
                    $innerFields = $field['fields'] ?? [];

                    for ($i = 0; $i < $max; $i++) {
                        $instanceData = $instances[$i] ?? [];
                        foreach ($innerFields as $inner) {
                            $innerVal = '';
                            foreach ($instanceData as $d) {
                                if (isset($d['name']) && $d['name'] === $inner['name']) {
                                    $innerVal = $d['userData'] ?? '';

                                    // Resolve options to labels for nested fields inside repeat container
                                    if (isset($inner['values']) && is_array($inner['values'])) {
                                        if (is_array($innerVal)) {
                                            $mapped = [];
                                            foreach ($innerVal as $v) {
                                                $opt = collect($inner['values'])->firstWhere('value', $v);
                                                $mapped[] = $opt ? ($opt['label'] ?? $v) : $v;
                                            }
                                            $innerVal = $mapped;
                                        } else {
                                            $opt = collect($inner['values'])->firstWhere('value', $innerVal);
                                            $innerVal = $opt ? ($opt['label'] ?? $innerVal) : $innerVal;
                                        }
                                    }

                                    if (is_array($innerVal))
                                        $innerVal = implode(', ', $innerVal);
                                    break;
                                }
                            }
                            $row[] = $innerVal;
                        }
                    }
                } elseif (in_array($field['type'], ['likert_matrix_grid', 'likert_matrix'])) {
                    $matrixAnswers = is_array($val) ? $val : (is_string($val) ? json_decode($val, true) : []);
                    if (!is_array($matrixAnswers)) {
                        $matrixAnswers = [];
                    }
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
                    $rows = $field['rows'] ?? [];
                    $colsDef = $field['columns'] ?? [];
                    foreach ($rows as $r) {
                        $rowVal = '';
                        $rowId = $r['value'] ?? '';
                        if (isset($matrixAnswers[$rowId])) {
                            $colVal = $matrixAnswers[$rowId];
                            $colOpt = collect($colsDef)->firstWhere('value', $colVal);
                            $rowVal = $colOpt ? ($colOpt['label'] ?? $colVal) : $colVal;
                        }
                        $row[] = $rowVal;
                    }
                } else {
                    // Resolve options to labels for simple fields
                    if (isset($field['values']) && is_array($field['values'])) {
                        if (is_array($val)) {
                            $mapped = [];
                            foreach ($val as $v) {
                                $opt = collect($field['values'])->firstWhere('value', $v);
                                $mapped[] = $opt ? ($opt['label'] ?? $v) : $v;
                            }
                            $val = $mapped;
                        } else {
                            $opt = collect($field['values'])->firstWhere('value', $val);
                            $val = $opt ? ($opt['label'] ?? $val) : $val;
                        }
                    }
                    if (is_array($val))
                        $val = implode(', ', $val);
                    $row[] = $val;
                }
            }
        } else {
            foreach ($this->survey->questions()->orderBy('position')->get() as $q) {
                $answer = $response->answers->where('question_id', $q->id)->first();
                $row[] = $answer ? $answer->value : '';
            }
        }

        return $row;
    }
}
