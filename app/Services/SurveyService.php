<?php

namespace App\Services;

use App\Models\Survey;
use App\Models\Response;
use Illuminate\Support\Facades\DB;
use App\Enums\SurveyType;
use App\Enums\SurveyStatus;

class SurveyService
{
    /**
     * Get the JSON schema for a survey.
     */
    public function getSchema(Survey $survey): ?string
    {
        return $survey->json_schema;
    }

    /**
     * Update the JSON schema for a survey.
     */
    public function updateSchema(Survey $survey, string $schema): bool
    {
        return $survey->update([
            'json_schema' => $schema,
            'status' => SurveyStatus::Active->value // Depending on business logic, maybe updating the schema sets it active or leaves draft
        ]);
    }

    /**
     * Submit responses for a survey.
     */
    public function submitResponse(Survey $survey, array $answers, ?int $respondentId = null): Response
    {
        return DB::transaction(function () use ($survey, $answers, $respondentId) {
            $response = $survey->responses()->create([
                'respondent_id' => $respondentId,
            ]);

            $answerRecords = [];
            foreach ($answers as $questionKey => $value) {
                // If using SurveyJS, form keys might be strings entirely. 
                // Legacy system might use numeric question_id.
                // We'll store complex or string-keyed results as JSON to be flexible.
                $isNumericKey = is_numeric($questionKey);

                $answerRecords[] = [
                    'question_id' => $isNumericKey ? $questionKey : null,
                    'value' => is_array($value) ? json_encode($value) : $value,
                ];
            }

            $response->answers()->createMany($answerRecords);

            return $response;
        });
    }
}
