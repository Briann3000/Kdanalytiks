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
        // Reuse authorizeOwner from SurveyController if possible or implement here
        // For simplicity, we'll assume the middleware handles basic auth, 
        // but we should check ownership.
        if (auth()->user()->role->value !== 'admin' && 
            $survey->created_by !== auth()->id() && 
            $survey->organization_id !== auth()->user()->organization?->id &&
            $survey->independent_id !== auth()->user()->independent?->id) {
            abort(403);
        }

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
                $rawAnswers = Answer::whereHas('response', function($q) use ($survey) {
                        $q->where('survey_id', $survey->id);
                    })
                    ->whereNull('question_id')
                    ->pluck('value')
                    ->toArray();

                foreach ($rawAnswers as $jsonBlob) {
                    $parsed = json_decode($jsonBlob, true) ?? [];
                    foreach ($parsed as $entry) {
                        if (isset($entry['name']) && $entry['name'] === $questionId && isset($entry['userData'])) {
                            $val = $entry['userData'];
                            if ($val !== null && $val !== '' && !is_array($val)) {
                                $responses[] = $val;
                            }
                        }
                    }
                }
            }

            return $this->analysisService->analyzeResponses($responses);
        });

        return response()->json($insight);
    }

    /**
     * Generate AI insights for a specific open-ended question or JSON field.
     */
    public function generateQuestionInsight(Request $request, $questionId)
    {
        // Existing method preserved for compatibility
        $surveyId = $request->query('survey_id');
        $cacheKey = "ai_insight_q_{$questionId}_s_{$surveyId}";
        $forceRefresh = $request->has('refresh');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        $insight = Cache::remember($cacheKey, 86400, function () use ($questionId, $surveyId) {
            $responses = [];

            if (is_numeric($questionId)) {
                // Legacy Question format
                $responses = Answer::where('question_id', $questionId)
                    ->whereNotNull('value')
                    ->where('value', '!=', '')
                    ->pluck('value')
                    ->toArray();
            } else {
                // JSON Survey field format
                $rawAnswers = Answer::whereHas('response', function($q) use ($surveyId) {
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

            return $this->analysisService->analyzeResponses($responses);
        });

        return response()->json($insight);
    }
}
