<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Services\SurveyService;
use Illuminate\Http\Request;
use App\Http\Requests\SubmitSurveyRequest;
use Illuminate\Http\JsonResponse;

class SurveyController extends Controller
{
    protected SurveyService $surveyService;

    public function __construct(SurveyService $surveyService)
    {
        $this->surveyService = $surveyService;
    }

    /**
     * Fetch the survey schema based on ID.
     */
    public function show(Survey $survey): JsonResponse
    {
        // For public surveys, anyone can view the schema.
        // For invitation-only surveys, we might check if they have access.

        $schema = json_decode($this->surveyService->getSchema($survey));

        return response()->json([
            'survey' => [
                'id' => $survey->id,
                'title' => $survey->title,
                'description' => $survey->description,
                'type' => $survey->type->value,
                'status' => $survey->status->value,
            ],
            'schema' => $schema
        ]);
    }

    /**
     * Save or update the survey schema.
     */
    public function update(Request $request, Survey $survey): JsonResponse
    {
        $request->validate([
            'json_schema' => 'required|string',
        ]);

        $this->authorizeOwnerAccess($survey);

        $this->surveyService->updateSchema($survey, $request->input('json_schema'));

        return response()->json(['message' => 'Survey schema saved successfully']);
    }

    /**
     * Handle respondent answers.
     */
    public function submit(SubmitSurveyRequest $request, Survey $survey): JsonResponse
    {
        // Fetch respondent ID if authenticated via Sanctum/session in this API context
        $respondentId = auth('sanctum')->id();

        if ($survey->type === \App\Enums\SurveyType::Invitation && !$respondentId) {
            return response()->json(['message' => 'Authentication required to submit this survey.'], 403);
        }

        if ($survey->status !== \App\Enums\SurveyStatus::Active) {
            return response()->json(['message' => 'This survey is not currently active.'], 400);
        }

        $this->surveyService->submitResponse($survey, $request->validated('answers'), $respondentId);

        return response()->json(['message' => 'Survey submitted successfully']);
    }

    /**
     * Ensure the authenticated user has rights to modify this survey.
     */
    protected function authorizeOwnerAccess(Survey $survey): void
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            abort(401, 'Unauthenticated access.');
        }

        $isCreator = $survey->created_by === $user->id;
        $isOrgOwner = $survey->organization_id && $user->organization?->id === $survey->organization_id;
        $isIndOwner = $survey->independent_id && $user->independent?->id === $survey->independent_id;
        $roleValue = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;
        $isAdmin = $roleValue === 'admin';

        if (!$isCreator && !$isOrgOwner && !$isIndOwner && !$isAdmin) {
            abort(403, 'Unauthorized. You do not have permission to modify this survey.');
        }
    }
}
