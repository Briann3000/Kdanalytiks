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

    protected function isHardRefresh(Request $request): bool
    {
        return $request->has('refresh')
            || $request->has('clear_cache')
            || $request->header('Cache-Control') === 'no-cache'
            || $request->header('Pragma') === 'no-cache'
            || str_contains($request->header('Cache-Control', ''), 'no-cache');
    }

    protected function getSurveyCacheVersion(\App\Models\Survey $survey): string
    {
        $count = $survey->responses()->count();
        $latest = $survey->responses()->max('updated_at') ?? $survey->updated_at;
        $ts = $latest ? (is_string($latest) ? strtotime($latest) : $latest->timestamp) : 0;
        return "c{$count}_t{$ts}";
    }

    /**
     * Analyze a specific question using Groq AI.
     */
    public function analyze(Request $request, \App\Models\Survey $survey, $questionId)
    {
        $ver = $this->getSurveyCacheVersion($survey);
        $cacheKey = "qualitative_analysis_{$survey->id}_{$questionId}_{$ver}";
        $forceRefresh = $this->isHardRefresh($request);

        if ($forceRefresh) {
            Cache::forget($cacheKey);
            Cache::forget("qualitative_analysis_{$survey->id}_{$questionId}");
        }

        $insight = Cache::remember($cacheKey, 86400, function () use ($survey, $questionId) {
            $responses = [];
            $questionText = $questionId;
            $isVirtualLikert = str_contains($questionId, '___');
            $matchName = $isVirtualLikert ? explode('___', $questionId)[0] : $questionId;
            $rowKey = $isVirtualLikert ? explode('___', $questionId)[1] : null;

            $aiService = $this->aiService;
            $surveyResponses = $survey->responses()->with('answers')->get();

            if (is_numeric($questionId)) {
                $question = \App\Models\Question::find($questionId);
                if ($question) {
                    $questionText = $question->text;
                }

                foreach ($surveyResponses as $resp) {
                    $aiMeta = $resp->ai_metadata ?? [];
                    $transcriptions = $aiMeta['transcriptions'] ?? [];
                    $ans = $resp->answers->firstWhere('question_id', $questionId);

                    if ($ans && $ans->value !== null && $ans->value !== '') {
                        $valStr = trim((string) $ans->value);
                        $isMedia = (str_starts_with($valStr, 'uploads/') || str_starts_with($valStr, 'storage/') || str_starts_with($valStr, 'survey_audio/'))
                            && preg_match('/\.(mp4|webm|ogg|ogv|mov|mp3|wav|m4a|aac)$/i', $valStr);

                        if ($isMedia) {
                            $trans = $transcriptions[$valStr] ?? null;
                            if (!$trans) {
                                foreach ($transcriptions as $tPath => $tText) {
                                    if (basename($tPath) === basename($valStr)) {
                                        $trans = $tText;
                                        break;
                                    }
                                }
                            }
                            if (!$trans) {
                                $filePath = public_path($valStr);
                                if (!file_exists($filePath)) {
                                    $filePath = storage_path('app/public/' . preg_replace('/^(storage|uploads)\//', '', $valStr));
                                }
                                if (file_exists($filePath)) {
                                    try {
                                        $trans = $aiService->transcribeMedia($filePath);
                                        if ($trans) {
                                            $transcriptions[$valStr] = $trans;
                                            $aiMeta['transcriptions'] = $transcriptions;
                                            $resp->ai_metadata = $aiMeta;
                                            $resp->save();
                                        }
                                    } catch (\Exception $te) {
                                        \Log::warning("On-demand audio transcription failed for {$valStr}: " . $te->getMessage());
                                    }
                                }
                            }
                            if ($trans) {
                                $responses[] = $trans;
                            }
                        } else {
                            $responses[] = $valStr;
                        }
                    }
                }
            } else {
                $schema = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : $survey->json_schema;
                $schema = is_array($schema) ? $schema : [];
                $field = collect($schema)->firstWhere('name', $matchName);
                if ($field) {
                    if ($isVirtualLikert) {
                        $rows = $field['rows'] ?? [];
                        $rowLabel = collect($rows)->firstWhere('value', $rowKey)['label'] ?? $rowKey;
                        $questionText = ($field['label'] ?? $field['name']) . ' - ' . $rowLabel;
                    } else {
                        $questionText = $field['label'] ?? $field['name'];
                    }
                }

                foreach ($surveyResponses as $resp) {
                    $aiMeta = $resp->ai_metadata ?? [];
                    $transcriptions = $aiMeta['transcriptions'] ?? [];

                    foreach ($resp->answers as $ans) {
                        if ($ans->question_id === null && !empty($ans->value)) {
                            $parsed = is_string($ans->value) ? json_decode($ans->value, true) : $ans->value;
                            if (is_array($parsed)) {
                                foreach ($parsed as $entry) {
                                    if (isset($entry['name']) && $entry['name'] === $matchName && isset($entry['userData'])) {
                                        $val = $entry['userData'];
                                        if ($isVirtualLikert) {
                                            $matrixAnswers = is_string($val) ? json_decode($val, true) : $val;
                                            if (is_array($matrixAnswers)) {
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
                                                $val = $matrixAnswers[$rowKey] ?? null;
                                            } else {
                                                $val = null;
                                            }

                                            if ($val !== null && $val !== '' && $field && isset($field['columns']) && is_array($field['columns'])) {
                                                $opt = collect($field['columns'])->firstWhere('value', $val);
                                                $val = $opt ? ($opt['label'] ?? $val) : $val;
                                            }
                                        }

                                        $valStr = is_array($val) ? (count($val) === 1 && is_string($val[0]) ? $val[0] : implode(', ', $val)) : (string) $val;
                                        $valStr = trim($valStr);

                                        $isMedia = (str_starts_with($valStr, 'uploads/') || str_starts_with($valStr, 'storage/') || str_starts_with($valStr, 'survey_audio/'))
                                            && preg_match('/\.(mp4|webm|ogg|ogv|mov|mp3|wav|m4a|aac)$/i', $valStr);

                                        if ($isMedia) {
                                            $trans = $transcriptions[$valStr] ?? null;
                                            if (!$trans) {
                                                foreach ($transcriptions as $tPath => $tText) {
                                                    if (basename($tPath) === basename($valStr)) {
                                                        $trans = $tText;
                                                        break;
                                                    }
                                                }
                                            }
                                            if (!$trans) {
                                                $filePath = public_path($valStr);
                                                if (!file_exists($filePath)) {
                                                    $filePath = storage_path('app/public/' . preg_replace('/^(storage|uploads)\//', '', $valStr));
                                                }
                                                if (file_exists($filePath)) {
                                                    try {
                                                        $trans = $aiService->transcribeMedia($filePath);
                                                        if ($trans) {
                                                            $transcriptions[$valStr] = $trans;
                                                            $aiMeta['transcriptions'] = $transcriptions;
                                                            $resp->ai_metadata = $aiMeta;
                                                            $resp->save();
                                                        }
                                                    } catch (\Exception $te) {
                                                        \Log::warning("On-demand audio transcription failed for {$valStr}: " . $te->getMessage());
                                                    }
                                                }
                                            }
                                            if ($trans) {
                                                $responses[] = $trans;
                                            }
                                        } else {
                                            if ($field) {
                                                $val = \App\Http\Controllers\SurveyController::formatResponseValue($val, $field);
                                            }
                                            if ($val !== null && $val !== '') {
                                                $responses[] = is_array($val) ? implode(', ', $val) : $val;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return $this->analysisService->analyzeResponses($responses, $questionText);
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

    protected function getQuestionStatsAndLabel(\App\Models\Survey $survey, string $questionId): array
    {
        $responses = $survey->responses;
        $totalResponses = $responses->count();
        $frequencyCount = [];
        $questionLabel = $questionId;

        $isVirtualLikert = str_contains($questionId, '___');
        $matchName = $isVirtualLikert ? explode('___', $questionId)[0] : $questionId;
        $rowKey = $isVirtualLikert ? explode('___', $questionId)[1] : null;

        $matchedField = null;
        if (!empty($survey->json_schema)) {
            $schema = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : $survey->json_schema;
            if (is_array($schema)) {
                foreach ($schema as $field) {
                    if (isset($field['name']) && $field['name'] === $matchName) {
                        $matchedField = $field;
                        $baseLabel = $field['label'] ?? $field['name'];
                        if ($isVirtualLikert && isset($field['rows']) && is_array($field['rows'])) {
                            $rowDef = collect($field['rows'])->firstWhere('value', $rowKey);
                            $rowLabel = $rowDef['label'] ?? $rowKey;
                            $questionLabel = "{$baseLabel} - {$rowLabel}";
                        } else {
                            $questionLabel = $baseLabel;
                        }
                        break;
                    }
                }
            }
        }

        if (is_numeric($questionId)) {
            $question = \App\Models\Question::find($questionId);
            if ($question) {
                $questionLabel = $question->text;
                $answers = $question->answers;
                foreach ($responses as $response) {
                    $answer = $answers->where('response_id', $response->id)->first();
                    if ($answer && $answer->value !== null && $answer->value !== '') {
                        $frequencyCount[$answer->value] = ($frequencyCount[$answer->value] ?? 0) + 1;
                    }
                }
            }
        } else {
            foreach ($responses as $response) {
                foreach ($response->answers as $ans) {
                    if ($ans->value !== null && $ans->value !== '') {
                        $data = is_string($ans->value) ? json_decode($ans->value, true) : $ans->value;
                        if (is_array($data)) {
                            foreach ($data as $entry) {
                                if (isset($entry['name']) && $entry['name'] === $matchName && isset($entry['userData'])) {
                                    $val = $entry['userData'];
                                    if ($isVirtualLikert && is_array($val) && isset($val[$rowKey])) {
                                        $val = $val[$rowKey];
                                    }
                                    if ($val !== null && $val !== '') {
                                        if (is_array($val)) {
                                            $mapped = [];
                                            foreach ($val as $v) {
                                                $opt = ($matchedField && isset($matchedField['values']) && is_array($matchedField['values']))
                                                    ? collect($matchedField['values'])->firstWhere('value', $v)
                                                    : null;
                                                $mapped[] = $opt ? ($opt['label'] ?? $v) : $v;
                                            }
                                            $valStr = implode(', ', $mapped);
                                        } else {
                                            $opt = ($matchedField && isset($matchedField['values']) && is_array($matchedField['values']))
                                                ? collect($matchedField['values'])->firstWhere('value', $val)
                                                : null;
                                            $valStr = $opt ? ($opt['label'] ?? $val) : $val;
                                        }
                                        $frequencyCount[$valStr] = ($frequencyCount[$valStr] ?? 0) + 1;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $stats = [];
        foreach ($frequencyCount as $val => $count) {
            $stats[] = [
                'value' => $val,
                'count' => $count,
                'percentage' => $totalResponses > 0 ? round(($count / $totalResponses) * 100, 1) : 0
            ];
        }

        return [
            'label' => $questionLabel,
            'stats' => $stats
        ];
    }

    public function generateQuantitativeInsight(Request $request, $questionId)
    {
        $surveyId = $request->query('survey_id');
        $survey = \App\Models\Survey::findOrFail($surveyId);

        $user = auth()->user();
        if (!$user || !$user->canUseAiAnalysis()) {
            return response()->json(['error' => 'Premium subscription required for Trend Interpretation.'], 403);
        }

        $style = $request->query('style', $survey->reporting_style ?? 'apa');
        $ver = $this->getSurveyCacheVersion($survey);

        $cacheKey = "quantitative_analysis_{$survey->id}_{$questionId}_{$style}_{$ver}";
        $likertCacheKey = "likert_matrix_analysis_{$survey->id}_{$questionId}_{$style}_{$ver}";
        $forceRefresh = $this->isHardRefresh($request);

        if ($forceRefresh) {
            Cache::forget($cacheKey);
            Cache::forget($likertCacheKey);
            Cache::forget("quantitative_analysis_{$survey->id}_{$questionId}_{$style}");
            Cache::forget("likert_matrix_analysis_{$survey->id}_{$questionId}_{$style}");
        }

        $schema = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : $survey->json_schema;
        $schema = is_array($schema) ? $schema : [];
        $field = collect($schema)->firstWhere('name', $questionId);

        if ($field && in_array($field['type'] ?? '', ['likert_matrix', 'likert_matrix_grid'])) {
            $insight = Cache::remember($likertCacheKey, 86400, function () use ($survey, $questionId, $field, $style) {
                $rawLabel = !empty($field['label']) ? $field['label'] : (!empty($field['title']) ? $field['title'] : ($field['name'] ?? $questionId));
                $label = (preg_match('/^field-\d+$/i', trim($rawLabel)) || preg_match('/^question[-_\d]+$/i', trim($rawLabel))) ? 'Survey Question' : $rawLabel;

                $controller = new \App\Http\Controllers\SurveyController();
                $analyticalData = $controller->getAnalyticalData($survey, $survey->responses, true);
                $analysisList = $analyticalData['analysis'] ?? [];
                $qItem = collect($analysisList)->firstWhere('id', $questionId);
                if ($qItem && !empty($qItem['likert_matrix_rows'])) {
                    return $this->analysisService->analyzeLikertMatrixData($qItem['likert_matrix_rows'], $label, $style);
                }
                return "Insufficient data for trend interpretation.";
            });

            return response()->json(['insight' => $insight]);
        }

        $insight = Cache::remember($cacheKey, 86400, function () use ($survey, $questionId, $style) {
            $data = $this->getQuestionStatsAndLabel($survey, $questionId);
            if (empty($data['stats'])) {
                return "Insufficient data for trend interpretation.";
            }
            return $this->analysisService->analyzeQuantitativeData($data['stats'], $data['label'], $style);
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

    public function analyzeInferential(Request $request)
    {
        $surveyId = $request->input('survey_id');
        $survey = \App\Models\Survey::findOrFail($surveyId);

        $user = auth()->user();
        if (!$user || !$user->hasActiveSubscription()) {
            return response()->json(['success' => false, 'message' => 'Premium subscription required for Statistical Intelligence.'], 403);
        }

        // Fetch active KB rules for the user
        $kbPromptSection = "";
        if ($user) {
            $kbRules = $user->sociusKnowledgeBases()
                ->where('is_active', true)
                ->pluck('content')
                ->filter(function ($c) {
                    if (empty($c))
                        return false;
                    if (str_starts_with($c, '[Qualitative]') || str_starts_with($c, '[Proposal]')) {
                        return false;
                    }
                    return true;
                })
                ->map(function ($c) {
                    return preg_replace('/^\[(?:Inferential|Quantitative|General)\]\s*/i', '', $c);
                })
                ->implode("\n- ");

            if (!empty($kbRules)) {
                $kbPromptSection = "\n\nCUSTOM USER KNOWLEDGE BASE INSTRUCTIONS:\n- " . $kbRules . "\n";
            }
        }

        $feedback = $request->input('feedback');
        $messages = $request->input('messages');

        if (!empty($feedback) && !empty($messages)) {
            $method = $request->input('method');
            $data = $request->input('data');

            $prompt = "You are an expert statistician. We are analyzing a survey titled '{$survey->title}' using the test method '{$method}'.\n";
            if (!empty($data)) {
                $prompt .= "Here is the current statistical calculation data and variable settings of the table:\n";
                $prompt .= json_encode($data) . "\n\n";
            }
            if (!empty($kbPromptSection)) {
                $prompt .= $kbPromptSection . "\n";
            }
            $prompt .= "Here is the conversation history with the researcher:\n\n";

            foreach ($messages as $msg) {
                $roleName = $msg['role'] === 'assistant' ? 'AI' : 'Researcher';
                $prompt .= "{$roleName}: {$msg['content']}\n\n";
            }

            $prompt .= "Researcher's latest refinement instruction:\n";
            $prompt .= "\"\"\"\n{$feedback}\n\"\"\"\n\n";
            $targetLang = $this->getTargetLanguage();
            $prompt .= "Instructions:\n";
            $prompt .= "1. Address the researcher's request and provide a revised, polished analysis.\n";
            $prompt .= "2. Ensure the statistical details remain correct and academic.\n";
            $prompt .= "3. Keep the response concise, professional, and return ONLY the updated statistical interpretation with no conversational chat filler or meta-commentary.\n";
            $prompt .= "4. If the user asks to modify the tables, update metrics, or add/remove rows or columns (e.g. 'reflect it in the table', 'add likelihood ratio to the table', 'add variable X3', etc.), you MUST append a valid JSON block containing the updated metrics or fields at the very end of your response inside a single ```json ... ``` code block. Make sure to preserve existing parameters if they should still be shown. For example, for Chi-Square: {\"likelihoodRatio\": 23.13, \"likelihoodSignificant\": false, \"likelihoodPValue\": 0.0935, \"linearAssociation\": 12.4, \"linearPValue\": 0.045, \"validCases\": 150}. For Regression: {\"equation\": \"Y = ...\", \"r\": \"...\", \"r2\": \"...\", \"adjR2\": \"...\", \"stdErrorEst\": \"...\", \"anova\": {...}, \"coefficients\": [...]}.\n";
            $prompt .= "5. If the user asks to interchange, swap, or rotate row and column variables, or change grouping/dependent/independent variables (e.g., 'Interchange the rows and columns'), you must return a recalculation action JSON block inside a ```json ... ``` code block indicating which variables to swap/update. For example, if row variable key is 'q_1' and column variable key is 'q_2', return: {\"action\": \"recalculate\", \"rowVar\": \"q_2\", \"colVar\": \"q_1\"}. If they want to change regression/anova variables, return {\"action\": \"recalculate\", \"depVar\": \"new_dep_id\", \"groupVar\": \"new_group_id\", \"indVars\": [\"new_ind_id1\"], \"varX\": \"...\", \"varY\": \"...\"}. Only swap variables that exist in the calculation data structure.\n";
            $prompt .= "6. You MUST write the entire revised analysis and response in the {$targetLang} language. Do not output it in English if the target language is different.";

            try {
                $polishSystemPrompt = "You are a senior research statistician. Refine the statistical interpretation strictly according to the researcher's instructions. Maintain APA 7 statistical precision, past tense, and plain text formatting without Markdown symbols.";
                $insight = $this->aiService->callAi($prompt, $polishSystemPrompt, false, 2048, 0.3);
                return response()->json(['success' => true, 'insight' => $insight]);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'AI Refinement Failed: ' . $e->getMessage()], 500);
            }
        }

        $method = $request->input('method');
        $data = $request->input('data');

        if (empty($data)) {
            return response()->json(['success' => false, 'message' => 'No data to analyze.'], 400);
        }

        $prompt = "As an expert statistician and research analyst, interpret the empirical results of a statistical test run on survey data from '{$survey->title}'.\n\n";

        switch ($method) {
            case 'crosstab':
                $prompt .= "Test: Cross-Tabulation Distribution Analysis\n";
                $prompt .= "Variables: Row='{$data['rowLabel']}', Column='{$data['colLabel']}'\n";
                $prompt .= "Total Sample (N): " . ($data['grandTotal'] ?? 0) . "\n";
                $prompt .= "Row Categories: " . implode(', ', (array) ($data['rows'] ?? [])) . "\n";
                $prompt .= "Column Categories: " . implode(', ', (array) ($data['columns'] ?? [])) . "\n";
                $prompt .= "Observed Cell Frequencies: " . json_encode($data['matrix'] ?? []) . "\n";
                if (!empty($data['rowPercentages'])) {
                    $prompt .= "Row Percentages (%): " . json_encode($data['rowPercentages']) . "\n";
                }
                $prompt .= "\n";
                break;

            case 'chisquare':
                $prompt .= "Test: Chi-Square Test of Independence\n";
                $prompt .= "Variables: Row='{$data['rowLabel']}', Column='{$data['colLabel']}'\n";
                $prompt .= "Total Sample (N): " . ($data['grandTotal'] ?? 0) . "\n";
                $prompt .= "Pearson Chi-Square (χ²): " . ($data['chiSquare'] ?? 0) . ", df: " . ($data['df'] ?? 1) . ", p-value: " . ($data['pValue'] ?? 1) . "\n";
                $prompt .= "Cramer's V (Effect Size): " . ($data['cramersV'] ?? 'N/A') . " (" . ($data['effectLabel'] ?? 'N/A') . ")\n";
                if (isset($data['likelihoodRatio'])) {
                    $prompt .= "Likelihood Ratio: " . $data['likelihoodRatio'] . " (p = " . ($data['likelihoodPValue'] ?? 'N/A') . ")\n";
                }
                if (isset($data['linearAssociation'])) {
                    $prompt .= "Linear-by-Linear Association: " . $data['linearAssociation'] . " (p = " . ($data['linearPValue'] ?? 'N/A') . ")\n";
                }
                $prompt .= "Statistical Significance: " . (!empty($data['significant']) ? "Statistically Significant (p < 0.05)" : "Not Statistically Significant (p >= 0.05)") . "\n";
                $prompt .= "Observed Contingency Matrix: " . json_encode($data['matrix'] ?? []) . "\n\n";
                break;

            case 'cronbach':
                $prompt .= "Test: Cronbach's Alpha Scale Reliability Analysis\n";
                $prompt .= "Number of Items (K): " . ($data['k_items'] ?? 0) . ", Valid Cases (N): " . ($data['valid_n'] ?? 0) . "\n";
                $prompt .= "Cronbach's Alpha (α): " . ($data['alpha'] ?? 0) . "\n";
                $prompt .= "Standardized Alpha (α_std): " . ($data['std_alpha'] ?? $data['alpha'] ?? 0) . "\n";
                $prompt .= "Internal Consistency Rating: " . ($data['interpretation'] ?? 'N/A') . "\n";
                if (!empty($data['item_stats'])) {
                    $prompt .= "Item-Total Statistics:\n";
                    foreach ((array) $data['item_stats'] as $item) {
                        $label = $item['label'] ?? $item['item_key'] ?? 'Item';
                        $meanDel = $item['scale_mean_if_deleted'] ?? 'N/A';
                        $corr = $item['item_total_corr'] ?? 'N/A';
                        $alphaDel = $item['alpha_if_deleted'] ?? 'N/A';
                        $prompt .= "- Item '{$label}': Mean if deleted = {$meanDel}, Corrected Item-Total Corr = {$corr}, Alpha if deleted = {$alphaDel}\n";
                    }
                }
                $prompt .= "\n";
                break;

            case 'ttest':
                $prompt .= "Test: Independent Samples T-Test (Comparing 2 Independent Group Means)\n";
                $prompt .= "Dependent Variable (Outcome): '{$data['depLabel']}'\n";
                $prompt .= "Grouping Variable (Factor): '{$data['groupLabel']}'\n";
                if (!empty($data['groups']) && is_array($data['groups'])) {
                    $prompt .= "Group Descriptive Statistics:\n";
                    foreach ($data['groups'] as $g) {
                        $name = $g['name'] ?? 'Group';
                        $n = $g['n'] ?? 'N/A';
                        $m = $g['mean'] ?? 'N/A';
                        $sd = $g['stdDev'] ?? 'N/A';
                        $se = $g['stdError'] ?? 'N/A';
                        $prompt .= "- Group '{$name}': N = {$n}, Mean (M) = {$m}, Std Dev (SD) = {$sd}, Std Error = {$se}\n";
                    }
                }
                $prompt .= "Test Results:\n";
                $prompt .= "- t-statistic: {$data['tValue']}, df: {$data['df']}, p-value (2-tailed): {$data['pValue']}\n";
                $prompt .= "- Mean Difference: {$data['meanDiff']}, Std Error of Difference: {$data['stdErrorDiff']}\n";
                if (isset($data['cohensD'])) {
                    $effect = $data['dEffectLabel'] ?? 'N/A';
                    $prompt .= "- Cohen's d (Effect Size): {$data['cohensD']} ({$effect} effect)\n";
                }
                if (isset($data['ciLowerAssumed']) && isset($data['ciUpperAssumed'])) {
                    $prompt .= "- 95% Confidence Interval of Difference: [{$data['ciLowerAssumed']}, {$data['ciUpperAssumed']}]\n";
                }
                $prompt .= "- Statistical Significance: " . (!empty($data['significant']) ? "Statistically Significant (p < 0.05)" : "Not Statistically Significant (p >= 0.05)") . "\n\n";
                break;

            case 'correlation':
                $prompt .= "Test: Pearson Bivariate Product-Moment Correlation (r)\n";
                $prompt .= "Variables: Variable X = '{$data['labelX']}', Variable Y = '{$data['labelY']}'\n";
                $prompt .= "Sample Size (N): {$data['n']}\n";
                $prompt .= "Pearson correlation coefficient (r): {$data['r']}, Coefficient of Determination (R²): {$data['r2']}\n";
                $prompt .= "t-statistic: {$data['tValue']}, p-value: {$data['pValue']}\n";
                $prompt .= "Statistical Significance: " . (!empty($data['significant']) ? "Statistically Significant (p < 0.05)" : "Not Statistically Significant (p >= 0.05)") . "\n\n";
                break;

            case 'anova':
                $prompt .= "Test: One-Way Analysis of Variance (ANOVA)\n";
                $prompt .= "Dependent Variable: '{$data['depLabel']}', Grouping Factor: '{$data['groupLabel']}'\n";
                if (!empty($data['groupStats']) && is_array($data['groupStats'])) {
                    $prompt .= "Group Descriptive Statistics:\n";
                    foreach ($data['groupStats'] as $g) {
                        $name = $g['name'] ?? 'Group';
                        $n = $g['n'] ?? 'N/A';
                        $m = $g['mean'] ?? 'N/A';
                        $sd = $g['stdDev'] ?? 'N/A';
                        $se = $g['stdError'] ?? 'N/A';
                        $prompt .= "- Group '{$name}': N = {$n}, Mean (M) = {$m}, Std Dev (SD) = {$sd}, Std Error = {$se}\n";
                    }
                }
                $prompt .= "ANOVA Table Metrics:\n";
                $prompt .= "- F-statistic: {$data['fValue']}, df Between: {$data['dfBetween']}, df Within: {$data['dfWithin']}, p-value: {$data['pValue']}\n";
                $prompt .= "- Sum of Squares Between (SSB): {$data['ssb']}, Within (SSW): {$data['ssw']}\n";
                if (isset($data['etaSquared'])) {
                    $prompt .= "- Eta-Squared (η² effect size): {$data['etaSquared']}\n";
                }
                $prompt .= "- Statistical Significance: " . (!empty($data['significant']) ? "Statistically Significant (p < 0.05)" : "Not Statistically Significant (p >= 0.05)") . "\n\n";
                break;

            case 'regression':
                $prompt .= "Test: Simple Linear Regression\n";
                $prompt .= "Dependent Variable (Y): '{$data['depLabel']}', Independent Predictor (X): '{$data['indLabel']}'\n";
                $prompt .= "Model Fit: R = {$data['r']}, R-squared (R²) = {$data['r2']}, Adjusted R² = {$data['adjR2']}, Std Error of Estimate = {$data['stdErrorEst']}\n";
                if (!empty($data['anova'])) {
                    $prompt .= "ANOVA Model Fit: F = {$data['anova']['fValue']}, p-value = {$data['anova']['pValue']}\n";
                }
                if (!empty($data['coefficients']) && is_array($data['coefficients'])) {
                    $prompt .= "Regression Coefficients:\n";
                    foreach ($data['coefficients'] as $c) {
                        $var = $c['variable'] ?? 'Variable';
                        $b = $c['b'] ?? 'N/A';
                        $se = $c['stdError'] ?? 'N/A';
                        $beta = $c['beta'] ?? 'N/A';
                        $t = $c['tValue'] ?? 'N/A';
                        $p = $c['pValue'] ?? 'N/A';
                        $prompt .= "- Variable '{$var}': B = {$b}, Std Error = {$se}, Beta (β) = {$beta}, t = {$t}, p = {$p}\n";
                    }
                }
                if (!empty($data['equation'])) {
                    $prompt .= "Regression Equation: {$data['equation']}\n";
                }
                $prompt .= "\n";
                break;

            case 'regression_multiple':
                $prompt .= "Test: Multiple Linear Regression\n";
                $prompt .= "Dependent Variable (Y): '{$data['depLabel']}'\n";
                $prompt .= "Model Fit: R = {$data['r']}, R-squared (R²) = {$data['r2']}, Adjusted R² = {$data['adjR2']}, Std Error of Estimate = {$data['stdErrorEst']}\n";
                if (!empty($data['equation'])) {
                    $prompt .= "Regression Equation: {$data['equation']}\n";
                }
                if (!empty($data['anova'])) {
                    $dfReg = $data['anova']['dfReg'] ?? 1;
                    $dfRes = $data['anova']['dfRes'] ?? 1;
                    $prompt .= "ANOVA Model Fit: F = {$data['anova']['fValue']}, df Regression = {$dfReg}, df Residual = {$dfRes}, p-value = {$data['anova']['pValue']}\n";
                }
                if (!empty($data['coefficients']) && is_array($data['coefficients'])) {
                    $prompt .= "Regression Coefficients Table:\n";
                    foreach ($data['coefficients'] as $c) {
                        $var = $c['variable'] ?? 'Variable';
                        $b = $c['b'] ?? 'N/A';
                        $se = $c['stdError'] ?? 'N/A';
                        $beta = $c['beta'] ?? 'N/A';
                        $t = $c['tValue'] ?? 'N/A';
                        $p = $c['pValue'] ?? 'N/A';
                        $prompt .= "- Variable '{$var}': Unstandardized B = {$b}, Std Error = {$se}, Standardized Beta (β) = {$beta}, t = {$t}, p = {$p}\n";
                    }
                }
                $prompt .= "\n";
                break;
        }

        if (!empty($kbPromptSection)) {
            $prompt .= $kbPromptSection . "\n";
        }

        $targetLang = $this->getTargetLanguage();
        $systemPrompt = <<<PROMPT
You are a senior research statistician and academic data analyst. Write a clear, comprehensive, and publication-ready academic statistical interpretation of the provided empirical test results.

OUTPUT REQUIREMENTS:
- Write ONE cohesive, well-developed paragraph (approximately 3 to 5 sentences).
- Report exact sample statistics, group means (M), standard deviations (SD), test statistics (t, F, χ², r, or α), degrees of freedom (df), p-values, and effect sizes (Cohen's d, Cramer's V, R²) in standard APA 7 reporting style.
- State whether the result is statistically significant at the α = 0.05 level and what that means for the research variables.
- Write in formal academic past tense throughout (e.g., 'indicated', 'revealed', 'demonstrated', 'accounted for').
- Plain text only: NO Markdown symbols, NO asterisks, NO bolding, NO bullet points, NO headings.
- Always finish the entire paragraph completely with a closing period. Never terminate mid-sentence.
- Write entirely in {$targetLang}.
PROMPT;

        try {
            $insight = $this->aiService->callAi($prompt, $systemPrompt, false, 2048, 0.3);
            return response()->json(['success' => true, 'insight' => $insight]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'AI Analysis Failed: ' . $e->getMessage()], 500);
        }
    }

    public function refineQuantitativeInsight(Request $request, $questionId)
    {
        $surveyId = $request->input('survey_id');
        $survey = \App\Models\Survey::findOrFail($surveyId);

        $user = auth()->user();
        if (!$user || !$user->canUseAiAnalysis()) {
            return response()->json(['success' => false, 'message' => 'Premium subscription required for Trend Interpretation.'], 403);
        }

        $messages = $request->input('messages', []);
        $feedback = $request->input('feedback');
        $style = $request->input('style', $survey->reporting_style ?? 'apa');

        if (empty($feedback)) {
            return response()->json(['success' => false, 'message' => 'Refinement instructions are required.'], 400);
        }

        $schema = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : $survey->json_schema;
        $schema = is_array($schema) ? $schema : [];
        $field = collect($schema)->firstWhere('name', $questionId);

        if ($field && in_array($field['type'] ?? '', ['likert_matrix', 'likert_matrix_grid'])) {
            $rawLabel = !empty($field['label']) ? $field['label'] : (!empty($field['title']) ? $field['title'] : ($field['name'] ?? $questionId));
            $questionLabel = (preg_match('/^field-\d+$/i', trim($rawLabel)) || preg_match('/^question[-_\d]+$/i', trim($rawLabel))) ? 'Likert Matrix Question' : $rawLabel;

            $controller = new \App\Http\Controllers\SurveyController();
            $analyticalData = $controller->getAnalyticalData($survey, $survey->responses, true);
            $analysisList = $analyticalData['analysis'] ?? [];
            $qItem = collect($analysisList)->firstWhere('id', $questionId);
            $statsText = "STATEMENT / ROW ITEM BREAKDOWN:\n";
            if ($qItem && !empty($qItem['likert_matrix_rows'])) {
                foreach ($qItem['likert_matrix_rows'] as $row) {
                    $rowLabel = $row['label'] ?? $row['value'] ?? 'Item';
                    $rowStats = $row['stats'] ?? [];
                    $itemStatsStr = [];
                    foreach ($rowStats as $s) {
                        if (isset($s['is_missing']) && $s['is_missing'])
                            continue;
                        $itemStatsStr[] = $s['value'] . ": " . $s['percentage'] . "%";
                    }
                    $statsText .= "- Statement Item \"{$rowLabel}\": " . implode(", ", $itemStatsStr) . "\n";
                }
            }
        } else {
            $data = $this->getQuestionStatsAndLabel($survey, $questionId);
            $questionLabel = $data['label'];
            $statsText = "";
            foreach ($data['stats'] as $stat) {
                $statsText .= "Choice: {$stat['value']} | Count: {$stat['count']} | Percentage: {$stat['percentage']}%\n";
            }
        }

        $stylePrompts = [
            'apa' => "You are a senior quantitative research analyst writing in formal academic APA Style (7th Edition).
STRICT RULES:
- Do NOT include raw sample size counts / frequencies in narrative prose, e.g., do NOT write '(n = 122)' or '(n = 92)'. Always use percentages only (e.g. 24.4%).
- Formulate your interpretation dynamically in formal academic APA style.
- Base your analysis STRICTLY on the statistical payload.",
            'harvard' => "You are a senior quantitative research analyst writing in formal academic Harvard Style.
STRICT RULES:
- Use Harvard referencing and citation style conventions in the text structure.
- Present findings in a formal academic tone focusing on percentages and relative distributions.",
            'oscola' => "You are a senior quantitative research analyst writing in OSCOLA (Oxford Standard for the Citation of Legal Authorities) Style.
STRICT RULES:
- Use legal research formatting and OSCOLA citation tone.
- Analyze findings with a highly structured, precise analytical tone suitable for legal scholarship.",
            'ieee' => "You are a senior quantitative research analyst writing in IEEE technical style.
STRICT RULES:
- Use technical, precise, objective engineering style.
- Employ IEEE citation conventions and bracketed references where appropriate.",
            'vancouver' => "You are a senior quantitative research analyst writing in Vancouver medical style.
STRICT RULES:
- Use biomedical and clinical research reporting conventions.
- Focus on objective percentage indicators and systematic data summary.",
            'mla' => "You are a senior quantitative research analyst writing in MLA (Modern Language Association) Style.
STRICT RULES:
- Use MLA narrative voice conventions suitable for humanities research.
- Present statistical insights in a cohesive, prose-driven flow."
        ];

        $styleRules = $stylePrompts[$style] ?? $stylePrompts['apa'];
        $targetLang = $this->getTargetLanguage();

        $prompt = "{$styleRules}
We are refining the statistical interpretation of a specific survey question from '{$survey->title}'.

TARGET QUESTION: {$questionLabel}
STATISTICAL FREQUENCY DATA FOR THIS QUESTION:
{$statsText}

";

        if (!empty($messages)) {
            $prompt .= "Here is the previous conversation history with the researcher:\n\n";
            foreach ($messages as $msg) {
                $roleName = ($msg['role'] ?? 'assistant') === 'assistant' ? 'AI' : 'Researcher';
                $prompt .= "{$roleName}: " . ($msg['content'] ?? '') . "\n\n";
            }
        }

        $prompt .= "Researcher's latest refinement instruction:\n";
        $prompt .= "\"\"\"\n{$feedback}\n\"\"\"\n\n";
        $prompt .= "Instructions:\n";
        $prompt .= "1. Refine the interpretation for THIS TARGET QUESTION ONLY, incorporating the researcher's instruction.\n";
        $prompt .= "2. Base ALL findings strictly on the provided target question statistical frequency data above.\n";
        $prompt .= "3. Do not invent external statistics, percentages, or non-existent industries.\n";
        $prompt .= "4. Keep the response concise, strategic, and professional in {$targetLang}. Do not include meta-commentary.";

        try {
            $insight = $this->aiService->callAi($prompt, "You are an expert research analyst and statistician. Base all findings strictly on the target question data provided.");

            // Save refined interpretation to cache keys to persist it
            $cacheKeyStyle = "quantitative_analysis_{$survey->id}_{$questionId}_{$style}";
            $cacheKeyDefault = "quantitative_analysis_{$survey->id}_{$questionId}";
            $likertCacheKey = "likert_matrix_analysis_{$survey->id}_{$questionId}_{$style}";
            \Illuminate\Support\Facades\Cache::put($cacheKeyStyle, $insight, 86400);
            \Illuminate\Support\Facades\Cache::put($cacheKeyDefault, $insight, 86400);
            \Illuminate\Support\Facades\Cache::put($likertCacheKey, $insight, 86400);

            return response()->json(['success' => true, 'insight' => $insight]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Refinement Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate narrative synthesis & thematic summary for a qualitative question.
     */
    public function generateQualitativeNarrative(Request $request, $questionId)
    {
        $surveyId = $request->input('survey_id', $request->query('survey_id'));
        $survey = \App\Models\Survey::findOrFail($surveyId);

        $style = $request->input('style', $request->query('style', $survey->reporting_style ?? 'apa'));
        $ver = $this->getSurveyCacheVersion($survey);

        $cacheKey = "qualitative_narrative_{$survey->id}_{$questionId}_{$style}_{$ver}";
        $forceRefresh = $this->isHardRefresh($request);

        if ($forceRefresh) {
            Cache::forget($cacheKey);
            Cache::forget("qualitative_narrative_{$survey->id}_{$questionId}_{$style}");
        }

        $result = Cache::remember($cacheKey, 86400, function () use ($survey, $questionId, $style) {
            $responses = [];
            $transcripts = [];
            $questionText = $questionId;
            $isAudioVideo = false;

            $aiService = $this->aiService;
            $surveyResponses = $survey->responses()->with('answers')->get();

            if (is_numeric($questionId)) {
                $question = \App\Models\Question::find($questionId);
                if ($question) {
                    $questionText = $question->text;
                }

                foreach ($surveyResponses as $resp) {
                    $aiMeta = $resp->ai_metadata ?? [];
                    $transcriptions = $aiMeta['transcriptions'] ?? [];
                    $ans = $resp->answers->firstWhere('question_id', $questionId);

                    if ($ans && $ans->value !== null && $ans->value !== '') {
                        $valStr = trim((string) $ans->value);
                        $isMedia = (str_starts_with($valStr, 'uploads/') || str_starts_with($valStr, 'storage/') || str_starts_with($valStr, 'survey_audio/'))
                            && preg_match('/\.(mp4|webm|ogg|ogv|mov|mp3|wav|m4a|aac)$/i', $valStr);

                        if ($isMedia) {
                            $isAudioVideo = true;
                            $trans = $transcriptions[$valStr] ?? null;
                            if (!$trans) {
                                foreach ($transcriptions as $tPath => $tText) {
                                    if (basename($tPath) === basename($valStr)) {
                                        $trans = $tText;
                                        break;
                                    }
                                }
                            }
                            if (!$trans) {
                                $filePath = public_path($valStr);
                                if (!file_exists($filePath)) {
                                    $filePath = storage_path('app/public/' . preg_replace('/^(storage|uploads)\//', '', $valStr));
                                }
                                if (file_exists($filePath)) {
                                    try {
                                        $trans = $aiService->transcribeMedia($filePath);
                                        if ($trans) {
                                            $transcriptions[$valStr] = $trans;
                                            $aiMeta['transcriptions'] = $transcriptions;
                                            $resp->ai_metadata = $aiMeta;
                                            $resp->save();
                                        }
                                    } catch (\Exception $te) {
                                        \Log::warning("On-demand transcription failed: " . $te->getMessage());
                                    }
                                }
                            }
                            if ($trans) {
                                $transcripts[] = $trans;
                                $responses[] = $trans;
                            }
                        } else {
                            $responses[] = $valStr;
                        }
                    }
                }
            } else {
                $schema = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : $survey->json_schema;
                $schema = is_array($schema) ? $schema : [];
                $field = collect($schema)->firstWhere('name', $questionId);
                if ($field) {
                    $questionText = $field['label'] ?? $field['name'];
                    if (in_array($field['type'] ?? '', ['audio', 'video', 'recording', 'media'])) {
                        $isAudioVideo = true;
                    }
                }

                foreach ($surveyResponses as $resp) {
                    $aiMeta = $resp->ai_metadata ?? [];
                    $transcriptions = $aiMeta['transcriptions'] ?? [];

                    foreach ($resp->answers as $ans) {
                        if ($ans->question_id === null && !empty($ans->value)) {
                            $parsed = is_string($ans->value) ? json_decode($ans->value, true) : $ans->value;
                            if (is_array($parsed)) {
                                foreach ($parsed as $entry) {
                                    if (isset($entry['name']) && $entry['name'] === $questionId && isset($entry['userData'])) {
                                        $val = $entry['userData'];
                                        $valStr = is_array($val) ? (count($val) === 1 && is_string($val[0]) ? $val[0] : implode(', ', $val)) : (string) $val;
                                        $valStr = trim($valStr);

                                        $isMedia = (str_starts_with($valStr, 'uploads/') || str_starts_with($valStr, 'storage/') || str_starts_with($valStr, 'survey_audio/'))
                                            && preg_match('/\.(mp4|webm|ogg|ogv|mov|mp3|wav|m4a|aac)$/i', $valStr);

                                        if ($isMedia) {
                                            $isAudioVideo = true;
                                            $trans = $transcriptions[$valStr] ?? null;
                                            if (!$trans) {
                                                foreach ($transcriptions as $tPath => $tText) {
                                                    if (basename($tPath) === basename($valStr)) {
                                                        $trans = $tText;
                                                        break;
                                                    }
                                                }
                                            }
                                            if (!$trans) {
                                                $filePath = public_path($valStr);
                                                if (!file_exists($filePath)) {
                                                    $filePath = storage_path('app/public/' . preg_replace('/^(storage|uploads)\//', '', $valStr));
                                                }
                                                if (file_exists($filePath)) {
                                                    try {
                                                        $trans = $aiService->transcribeMedia($filePath);
                                                        if ($trans) {
                                                            $transcriptions[$valStr] = $trans;
                                                            $aiMeta['transcriptions'] = $transcriptions;
                                                            $resp->ai_metadata = $aiMeta;
                                                            $resp->save();
                                                        }
                                                    } catch (\Exception $te) {
                                                        \Log::warning("On-demand transcription failed: " . $te->getMessage());
                                                    }
                                                }
                                            }
                                            if ($trans) {
                                                $transcripts[] = $trans;
                                                $responses[] = $trans;
                                            }
                                        } else {
                                            if ($field) {
                                                $val = \App\Http\Controllers\SurveyController::formatResponseValue($val, $field);
                                            }
                                            if ($val !== null && $val !== '') {
                                                $responses[] = is_array($val) ? implode(', ', $val) : $val;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($isAudioVideo && !empty($transcripts)) {
                return $this->analysisService->extractFromTranscripts($transcripts, $questionText, $style);
            }

            return $this->analysisService->synthesizeNarrative($responses, $questionText, $style);
        });

        return response()->json($result);
    }

    /**
     * Refine qualitative narrative synthesis with user feedback.
     */
    public function refineQualitativeNarrative(Request $request, $questionId)
    {
        $surveyId = $request->input('survey_id', $request->query('survey_id'));
        $survey = \App\Models\Survey::findOrFail($surveyId);

        $user = auth()->user();
        if (!$user || !$user->canUseAiAnalysis()) {
            return response()->json(['success' => false, 'message' => 'Subscription required for Narrative Synthesis Refinement.'], 403);
        }

        $messages = $request->input('messages', []);
        $feedback = $request->input('feedback', $request->input('instruction'));
        $style = $request->input('style', $survey->reporting_style ?? 'apa');

        if (empty($feedback)) {
            return response()->json(['success' => false, 'message' => 'Refinement instructions are required.'], 400);
        }

        $targetLang = $this->getTargetLanguage();
        $prompt = "You are a qualitative research specialist refining an academic narrative synthesis for survey question: \"{$questionId}\".\n";
        $prompt .= "Survey Title: \"{$survey->title}\"\n\n";

        if (!empty($messages)) {
            $prompt .= "Previous narrative:\n";
            foreach ($messages as $msg) {
                $roleName = ($msg['role'] ?? 'assistant') === 'assistant' ? 'Narrative' : 'User Instruction';
                $prompt .= "{$roleName}: " . ($msg['content'] ?? '') . "\n\n";
            }
        }

        $prompt .= "User Refinement Instruction:\n\"\"\"\n{$feedback}\n\"\"\"\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "1. Return a refined, cohesive 3 to 4 sentence narrative synthesis in {$targetLang}.\n";
        $prompt .= "2. Plain text only. No bullet points, no markdown formatting.\n";
        $prompt .= "3. Past tense academic prose conforming to {$style} style conventions.";

        try {
            $narrative = $this->aiService->callAi($prompt, "You are a senior qualitative researcher. Provide refined, academic narrative synthesis.");

            $cacheKey = "qualitative_narrative_{$survey->id}_{$questionId}_{$style}";
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                $cached['narrative'] = $narrative;
                Cache::put($cacheKey, $cached, 86400);
            } else {
                Cache::put($cacheKey, ['narrative' => $narrative], 86400);
            }

            return response()->json([
                'success' => true,
                'narrative' => $narrative
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Refinement Failed: ' . $e->getMessage()], 500);
        }
    }

    private function getTargetLanguage()
    {
        $locale = app()->getLocale();
        $langNames = [
            'sw' => 'Swahili (Kiswahili)',
            'de' => 'German (Deutsch)',
            'es' => 'Spanish (Español)',
            'fr' => 'French (Français)',
            'ar' => 'Arabic (العربية)',
            'zh' => 'Chinese (中文)',
            'en' => 'English'
        ];
        return $langNames[$locale] ?? 'English';
    }
}
