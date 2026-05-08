<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SurveyResponsesExport implements FromCollection, WithHeadings, WithMapping
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
            if (!$jsonAnswer) continue;
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
                if (!isset($field['name']) || in_array($field['type'], ['header', 'paragraph', 'hidden_field'])) continue;
                
                $label = $field['label'] ?? $field['name'];
                
                if ($field['type'] === 'repeat_container') {
                    $max = $this->maxRepeats[$field['name']] ?? 1;
                    $innerFields = $field['fields'] ?? [];
                    for ($i = 1; $i <= $max; $i++) {
                        foreach ($innerFields as $inner) {
                            $headers[] = $label . " (Instance $i) - " . ($inner['label'] ?? $inner['name']);
                        }
                    }
                } elseif ($field['type'] === 'likert_matrix_grid') {
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
            $response->created_at->format('Y-m-d H:i:s'),
            $response->respondent ? $response->respondent->email : 'N/A',
            $response->respondent ? $response->respondent->name : 'Anonymous'
        ];

        if (!empty($this->schemaFields)) {
            $jsonAnswer = $response->answers->first();
            $parsedData = $jsonAnswer ? json_decode($jsonAnswer->value, true) : [];
            
            foreach ($this->schemaFields as $field) {
                if (!isset($field['name']) || in_array($field['type'], ['header', 'paragraph', 'hidden_field'])) continue;
                
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
                                    if (is_array($innerVal)) $innerVal = implode(', ', $innerVal);
                                    break;
                                }
                            }
                            $row[] = $innerVal;
                        }
                    }
                } elseif ($field['type'] === 'likert_matrix_grid') {
                    $matrixAnswers = is_array($val) ? $val : [];
                    $rows = $field['rows'] ?? [];
                    foreach ($rows as $r) {
                        $rowVal = '';
                        foreach ($matrixAnswers as $ma) {
                            if (isset($ma['row']) && $ma['row'] === ($r['value'] ?? '')) {
                                $rowVal = $ma['column'] ?? '';
                                break;
                            }
                        }
                        $row[] = $rowVal;
                    }
                } else {
                    if (is_array($val)) $val = implode(', ', $val);
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
