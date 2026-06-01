<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SurveyVersionController extends Controller
{
    /**
     * Display a list of all survey versions.
     */
    public function index(Survey $survey)
    {
        Gate::authorize('update', $survey);

        $versions = $survey->versions()
            ->with('modifier')
            ->orderBy('version_number', 'desc')
            ->paginate(15);

        return view('surveys.versions', compact('survey', 'versions'));
    }

    /**
     * Get a specific version details for preview.
     */
    public function show(Survey $survey, SurveyVersion $version)
    {
        Gate::authorize('update', $survey);

        if ((int) $version->survey_id !== (int) $survey->id) {
            abort(404);
        }

        return response()->json([
            'success' => true,
            'version' => [
                'version_number' => $version->version_number,
                'title' => $version->title,
                'description' => $version->description,
                'change_summary' => $version->change_summary,
                'json_schema' => $version->json_schema,
                'created_at' => $version->created_at->toDateTimeString(),
                'changed_by' => $version->modifier?->name ?? 'System',
            ]
        ]);
    }

    /**
     * Restore the survey to a specific version.
     */
    public function restore(Survey $survey, SurveyVersion $version)
    {
        Gate::authorize('update', $survey);

        if ((int) $version->survey_id !== (int) $survey->id) {
            abort(404);
        }

        // Capture current state as a version snapshot before performing the restore
        app(\App\Services\SurveyVersioningService::class)->createVersionIfChanged(
            $survey,
            [
                'title' => $version->title,
                'description' => $version->description,
                'json_schema' => $version->json_schema,
            ],
            auth()->id()
        );

        // Perform rollback
        $survey->update([
            'title' => $version->title,
            'description' => $version->description,
            'json_schema' => $version->json_schema,
        ]);

        return redirect()->route('surveys.versions', $survey)
            ->with('success', __('Survey successfully restored to Version :version!', ['version' => $version->version_number]));
    }
}
