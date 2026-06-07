<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Services\QualitativeAnalysisService;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class InsightController extends Controller
{
    protected $analysisService;
    protected $aiService;

    public function __construct(QualitativeAnalysisService $analysisService, AiService $aiService)
    {
        $this->analysisService = $analysisService;
        $this->aiService = $aiService;
    }

    /**
     * Show the qualitative report dashboard.
     */
    public function showQualitativeReport(\App\Models\Survey $survey)
    {
        Gate::authorize('view', $survey);

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
                $responses = Answer::where('question_id', $questionId)
                    ->whereNotNull('value')
                    ->where('value', '!=', '')
                    ->pluck('value')
                    ->toArray();
            } else {
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

            return $this->analysisService->analyzeResponses($responses);
        });

        $user = auth()->user();
        $isTruncated = false;

        $roleValue = $user ? ($user->role instanceof \UnitEnum ? $user->role->value : $user->role) : null;
        if ($user && $roleValue === 'respondent' && !$user->hasActiveSubscription()) {
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

    public function generateQuestionInsight(Request $request, $questionId)
    {
        $surveyId = $request->query('survey_id');
        $survey = \App\Models\Survey::findOrFail($surveyId);
        return $this->analyze($request, $survey, $questionId);
    }

    public function generateQuantitativeInsight(Request $request, $questionId)
    {
        $surveyId = $request->query('survey_id');
        $survey = \App\Models\Survey::findOrFail($surveyId);

        $user = auth()->user();
        if (!$user || !$user->canUseAiAnalysis()) {
            return response()->json(['error' => 'Premium subscription required for Trend Interpretation.'], 403);
        }

        $cacheKey = "quantitative_analysis_{$survey->id}_{$questionId}";

        $insight = Cache::remember($cacheKey, 86400, function () use ($survey, $questionId) {
            $responses = $survey->responses;
            $stats = [];
            $totalResponses = $responses->count();
            $frequencyCount = [];

            if (is_numeric($questionId)) {
                $question = \App\Models\Question::findOrFail($questionId);
                $answers = $question->answers;
                foreach ($responses as $response) {
                    $answer = $answers->where('response_id', $response->id)->first();
                    if ($answer && $answer->value !== null && $answer->value !== '') {
                        $frequencyCount[$answer->value] = ($frequencyCount[$answer->value] ?? 0) + 1;
                    }
                }
            } else {
                foreach ($responses as $response) {
                    $jsonAnswer = $response->answers->first();
                    if ($jsonAnswer) {
                        $data = is_string($jsonAnswer->value) ? json_decode($jsonAnswer->value, true) : $jsonAnswer->value;
                        if (is_array($data)) {
                            foreach ($data as $entry) {
                                if (isset($entry['name']) && $entry['name'] === $questionId && isset($entry['userData'])) {
                                    $val = $entry['userData'];
                                    if ($val !== null && $val !== '') {
                                        $valStr = is_array($val) ? implode(', ', $val) : $val;
                                        $frequencyCount[$valStr] = ($frequencyCount[$valStr] ?? 0) + 1;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            foreach ($frequencyCount as $val => $count) {
                $stats[] = [
                    'value' => $val,
                    'count' => $count,
                    'percentage' => $totalResponses > 0 ? round(($count / $totalResponses) * 100, 1) : 0
                ];
            }

            if (empty($stats))
                return "Insufficient data for trend interpretation.";
            return $this->analysisService->analyzeQuantitativeData($stats);
        });

        return response()->json(['insight' => $insight]);
    }

    public function analyzeCrosstab(Request $request)
    {
        $surveyId = $request->query('survey_id');
        $survey = \App\Models\Survey::findOrFail($surveyId);

        $user = auth()->user();
        if (!$user || !$user->hasActiveSubscription()) {
            return response()->json(['error' => 'Premium subscription required for Correlation Intelligence.'], 403);
        }

        $matrix = $request->input('matrix');
        $rowLabel = $request->input('rowLabel');
        $colLabel = $request->input('colLabel');

        if (empty($matrix)) {
            return response()->json(['error' => 'No data to analyze.'], 400);
        }

        $prompt = "As an expert research analyst, interpret this cross-tabulation matrix from a survey titled '{$survey->title}'.\n";
        $prompt .= "The matrix correlates '{$rowLabel}' (rows) against '{$colLabel}' (columns).\n\n";
        $prompt .= "Data Matrix:\n" . json_encode($matrix, JSON_PRETTY_PRINT) . "\n\n";
        $prompt .= "Instructions:\n";
        $prompt .= "1. Identify the strongest correlations or patterns found.\n";
        $prompt .= "2. Note any surprising deviations or outliers.\n";
        $prompt .= "3. Provide a strategic takeaway or 'So What?' for the researcher.\n";
        $prompt .= "4. Keep it professional, concise (max 200 words), and data-driven.";

        try {
            $insight = $this->aiService->callAi($prompt, "You are an expert research analyst and statistician. Provide clear, concise, and highly strategic interpretations of survey data.");
            return response()->json(['insight' => $insight]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'AI Analysis Failed: ' . $e->getMessage()], 500);
        }
    }
}
