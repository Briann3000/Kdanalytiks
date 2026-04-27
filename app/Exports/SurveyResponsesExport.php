<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SurveyResponsesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $survey;
    protected $responses;

    public function __construct($survey, $responses)
    {
        $this->survey = $survey;
        $this->responses = $responses;
    }

    public function collection()
    {
        return $this->responses;
    }

    public function headings(): array
    {
        $headers = ['Response ID', 'Date', 'Respondent Email', 'Respondent Name'];
        if (!empty($this->survey->json_schema) && $this->survey->json_schema !== '[]') {
            $headers[] = 'Raw JSON Data';
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

        if (!empty($this->survey->json_schema) && $this->survey->json_schema !== '[]') {
            $jsonAnswer = $response->answers->first();
            $row[] = $jsonAnswer ? $jsonAnswer->value : '{}';
        } else {
            foreach ($this->survey->questions()->orderBy('position')->get() as $q) {
                $answer = $response->answers->where('question_id', $q->id)->first();
                $row[] = $answer ? $answer->value : '';
            }
        }

        return $row;
    }
}
