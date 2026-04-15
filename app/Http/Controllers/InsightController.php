<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Services\QualitativeAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class InsightController extends Controller
{
    protected $analysisService;

    public function __construct(QualitativeAnalysisService $analysisService)
    {
        $this->analysisService = $analysisService;
    }

    /**
     * Show the qualitative report dashboard.
     */
    public function showQualitativeReport(\App\Models\Survey $survey)
    {
        \Illuminate\Support\Facades\Gate::authorize('view', $survey);

        $questions = [];
        if (!empty($survey->json_schema)) {
            $schema = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : $survey->json_schema;
            foreach ($schema as $field) {
                if (isset($field['type']) && in_array($field['type'], ['text', 'textarea'])) {
                    $questions[] = [
                        'id' => $field['name'],
                        'text' => $field['label'] ?? $field['name']
                    ];
                }
            }
        } else {
            $questions = $survey->questions()
                ->whereIn('type', ['text', 'textarea'])
                ->get(['id', 'text'])
                ->toArray();
        }

        return view('reports.qualitative', compact('survey', 'questions'));
    }

    /**
     * Analyze a specific question using Groq AI.
     */
    public function analyze(Request $request, \App\Models\Survey $survey, $questionId)
    {
        $cacheKey = "qualitative_analysis_{$survey->id}_{$questionId}";
        $forceRefresh = $request->has('refresh');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        $insight = Cache::remember($cacheKey, 86400, function () use ($survey, $questionId) {
            $responses = [];

            if (is_numeric($questionId)) {
                // Legacy format
                $responses = Answer::where('question_id', $questionId)
                    ->whereNotNull('value')
                    ->where('value', '!=', '')
                    ->pluck('value')
                    ->toArray();
            } else {
                // JSON format
                $surveyId = $survey->id;
                $rawAnswers = Answer::whereHas('response', function ($q) use ($surveyId) {
                    $q->where('survey_id', $surveyId);
                })
                    ->whereNull('question_id')
                    ->pluck('value')
                    ->toArray();

                foreach ($rawAnswers as $jsonBlob) {
                    $parsed = json_decode($jsonBlob, true) ?? [];
                    foreach ($parsed as $entry) {
                        if (isset($entry['name']) && $entry['name'] === $questionId && isset($entry['userData'])) {
                            $val = $entry['userData'];
                            if ($val !== null && $val !== '') {
                                $responses[] = is_array($val) ? implode(', ', $val) : $val;
                            }
                        }
                    }
                }
            }

            \Log::info("Qualitative Analysis (analyze): Found " . count($responses) . " responses for Question ID: {$questionId} in Survey: {$survey->id}");

            return $this->analysisService->analyzeResponses($responses);
        });

        // Backend Paywall Truncation Logic
        $user = auth()->user();
        $isTruncated = false;

        if ($user && $user->role === \App\Enums\UserRole::Respondent && !$user->hasActiveSubscription()) {
            $isTruncated = true;
            if (isset($insight['key_themes']) && is_array($insight['key_themes'])) {
                $insight['key_themes'] = array_slice($insight['key_themes'], 0, 1);
            }
            if (isset($insight['representative_quotes']) && is_array($insight['representative_quotes'])) {
                $insight['representative_quotes'] = array_slice($insight['representative_quotes'], 0, 1);
            }
        }

        $insight['is_truncated'] = $isTruncated;

        return response()->json($insight);
    }

    /**
     * Generate AI insights for a specific open-ended question or JSON field.
     * (Preserved as a wrapper for the 'ai.insights.question' route)
     */
    public function generateQuestionInsight(Request $request, $questionId)
    {
        $surveyId = $request->query('survey_id');
        $survey = \App\Models\Survey::findOrFail($surveyId);

        return $this->analyze($request, $survey, $questionId);
    }
}
