<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyDashboardConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class DashboardBuilderController extends Controller
{
    /**
     * Show the interactive report dashboard builder.
     */
    public function dashboardBuilder(Survey $survey)
    {
        Gate::authorize('view', $survey);

        $responses = $survey->responses()->with('answers.question')->get();
        $analyticalData = app(SurveyController::class)->getAnalyticalData($survey, $responses);
        $analysis = $analyticalData['analysis'];

        $config = $survey->dashboardConfig;
        $layout = $config ? $config->layout : null;

        if (!$layout) {
            $layout = $this->generateDefaultLayout($survey);
        }

        return view('surveys.dashboard_builder', compact('survey', 'analysis', 'layout'));
    }

    /**
     * Save the customized dashboard layout config.
     */
    public function saveDashboardLayout(Request $request, Survey $survey)
    {
        Gate::authorize('view', $survey);

        $request->validate([
            'layout' => 'required|array',
        ]);

        $config = SurveyDashboardConfig::updateOrCreate(
            ['survey_id' => $survey->id],
            [
                'layout' => $request->input('layout'),
                'updated_by' => auth()->id(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => __('Dashboard layout saved successfully.'),
        ]);
    }

    /**
     * Preview the dashboard in presentation mode for stakeholders.
     */
    public function dashboardPreview(Survey $survey)
    {
        $user = auth()->user();
        $token = request('token');
        $isOwner = $user && ($survey->created_by == $user->id);
        $isAdmin = $user && $user->isAdmin();
        $isCollaborator = $user && $survey->collaborators()->where('user_id', $user->id)->exists();
        $hasToken = $token && $survey->share_report_token === $token;

        if (!$isOwner && !$isAdmin && !$isCollaborator && !$hasToken) {
            abort(403, __('You do not have permission to view this report.'));
        }

        $responses = $survey->responses()->with('answers.question')->get();
        $analyticalData = app(SurveyController::class)->getAnalyticalData($survey, $responses);
        $analysis = $analyticalData['analysis'];

        $config = $survey->dashboardConfig;
        $layout = $config ? $config->layout : null;

        if (!$layout) {
            $layout = $this->generateDefaultLayout($survey);
        }

        return view('surveys.dashboard_preview', compact('survey', 'analysis', 'layout', 'hasToken', 'token'));
    }

    /**
     * Helper to generate a default dashboard layout.
     */
    private function generateDefaultLayout(Survey $survey): array
    {
        $layout = [];
        $isJson = !empty($survey->json_schema) && $survey->json_schema !== '[]';

        if ($isJson) {
            $schema = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : $survey->json_schema;
            $schema = is_array($schema) ? $schema : [];
            foreach ($schema as $field) {
                if (!isset($field['name']) || in_array($field['type'], ['header', 'paragraph'])) {
                    continue;
                }
                $fieldId = $field['name'];
                $label = $field['label'] ?? $fieldId;
                $type = $field['type'] ?? 'text';

                $chartType = 'table';
                if (in_array($type, ['radio', 'select', 'checkbox', 'select-one', 'select-multiple', 'radio-group', 'checkbox-group', 'rating', 'starRating', 'toggle'])) {
                    $chartType = 'bar';
                } elseif (in_array($type, ['number', 'rating', 'starRating', 'range'])) {
                    $chartType = 'metric';
                }

                $layout[] = [
                    'widget_id' => 'w_' . Str::random(8),
                    'question_id' => $fieldId,
                    'chart_type' => $chartType,
                    'title' => $label,
                    'width' => 'full',
                    'visible' => true,
                    'config' => [
                        'show_percentages' => true,
                        'color_scheme' => 'default',
                        'limit_top_n' => 10,
                    ]
                ];
            }
        } else {
            $questions = $survey->questions()->orderBy('position')->get();
            foreach ($questions as $question) {
                $fieldId = 'question_' . $question->id;
                $label = $question->title;
                $type = $question->type;

                $chartType = 'table';
                if (in_array($type, ['radio', 'select', 'checkbox', 'select-one', 'select-multiple', 'radio-group', 'checkbox-group', 'rating', 'starRating', 'toggle'])) {
                    $chartType = 'bar';
                } elseif (in_array($type, ['number', 'rating', 'starRating', 'range'])) {
                    $chartType = 'metric';
                }

                $layout[] = [
                    'widget_id' => 'w_' . Str::random(8),
                    'question_id' => $fieldId,
                    'chart_type' => $chartType,
                    'title' => $label,
                    'width' => 'full',
                    'visible' => true,
                    'config' => [
                        'show_percentages' => true,
                        'color_scheme' => 'default',
                        'limit_top_n' => 10,
                    ]
                ];
            }
        }

        return $layout;
    }
}
