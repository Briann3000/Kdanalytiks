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
            $questionText = $questionId;
            $isVirtualLikert = str_contains($questionId, '___');
            $matchName = $isVirtualLikert ? explode('___', $questionId)[0] : $questionId;
            $rowKey = $isVirtualLikert ? explode('___', $questionId)[1] : null;

            if (is_numeric($questionId)) {
                $question = \App\Models\Question::find($questionId);
                if ($question) {
                    $questionText = $question->text;
                }
                $responses = Answer::where('question_id', $questionId)
                    ->whereNotNull('value')
                    ->where('value', '!=', '')
                    ->pluck('value')
                    ->toArray();
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
                            } else {
                                if ($field) {
                                    $val = \App\Http\Controllers\SurveyController::formatResponseValue($val, $field);
                                }
                            }
                            if ($val !== null && $val !== '') {
                                $responses[] = is_array($val) ? implode(', ', $val) : $val;
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

        if (!empty($survey->json_schema)) {
            $schema = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : $survey->json_schema;
            if (is_array($schema)) {
                foreach ($schema as $field) {
                    if (isset($field['name']) && $field['name'] === $matchName) {
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
                                        $valStr = is_array($val) ? implode(', ', $val) : $val;
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

        $cacheKey = "quantitative_analysis_{$survey->id}_{$questionId}_{$style}";
        $likertCacheKey = "likert_matrix_analysis_{$survey->id}_{$questionId}_{$style}";
        if ($request->has('refresh')) {
            Cache::forget($cacheKey);
            Cache::forget($likertCacheKey);
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
                $insight = $this->aiService->callAi($prompt, "You are an expert statistician and research advisor. Provide refined, strategically polished statistical interpretations.");
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

        $prompt = "As an expert statistician and research analyst, interpret the results of a statistical test run on survey data from '{$survey->title}'.\n\n";

        switch ($method) {
            case 'crosstab':
                $prompt .= "Test: Cross-Tabulation Distribution Analysis\n";
                $prompt .= "Variables: Row='{$data['rowLabel']}', Column='{$data['colLabel']}'\n";
                $prompt .= "Total Responses: " . ($data['grandTotal'] ?? 0) . "\n";
                $prompt .= "Observed Cell Frequencies: " . json_encode($data['matrix']) . "\n\n";
                break;
            case 'chisquare':
                $prompt .= "Test: Chi-Square Test of Independence\n";
                $prompt .= "Variables: Row='{$data['rowLabel']}', Column='{$data['colLabel']}'\n";
                $prompt .= "Chi-Square Value (χ²): " . ($data['chiSquare'] ?? 0) . ", df: " . ($data['df'] ?? 1) . ", p-value: " . ($data['pValue'] ?? 1) . "\n";
                $prompt .= "Cramer's V (Effect Size): " . ($data['cramersV'] ?? 'N/A') . " (" . ($data['effectLabel'] ?? 'N/A') . ")\n";
                $prompt .= "Result is " . (!empty($data['significant']) ? "Statistically Significant (p < 0.05)" : "Not Statistically Significant (p >= 0.05)") . ".\n\n";
                break;
            case 'cronbach':
                $prompt .= "Test: Cronbach's Alpha Reliability Analysis\n";
                $prompt .= "Cronbach's Alpha (α): " . ($data['alpha'] ?? 0) . "\n";
                $prompt .= "Standardized Alpha (α_std): " . ($data['std_alpha'] ?? $data['alpha'] ?? 0) . "\n";
                $prompt .= "Number of Items (K): " . ($data['k_items'] ?? 0) . ", Valid Cases (N): " . ($data['valid_n'] ?? 0) . "\n";
                $prompt .= "Interpretation: " . ($data['interpretation'] ?? 'N/A') . "\n";
                $prompt .= "Item-Total Statistics: " . json_encode($data['item_stats'] ?? []) . "\n\n";
                break;
            case 'ttest':
                $prompt .= "Test: Independent Samples T-Test (Comparing 2 Group Means)\n";
                $prompt .= "Dependent Variable: '{$data['depLabel']}', Grouping Variable: '{$data['groupLabel']}'\n";
                $prompt .= "t-value: {$data['tValue']}, df: {$data['df']}, p-value: {$data['pValue']}\n";
                $prompt .= "Mean Difference: {$data['meanDiff']}, Std Error of Difference: {$data['stdErrorDiff']}\n";
                $prompt .= "Result is " . ($data['significant'] ? "Statistically Significant (p < 0.05)" : "Not Statistically Significant (p >= 0.05)") . ".\n\n";
                $prompt .= "Group Statistics:\n" . json_encode($data['groups'], JSON_PRETTY_PRINT) . "\n\n";
                break;
            case 'correlation':
                $prompt .= "Test: Pearson Bivariate Correlation (r)\n";
                $prompt .= "Variables: X='{$data['labelX']}', Y='{$data['labelY']}'\n";
                $prompt .= "Sample Size (N): {$data['n']}\n";
                $prompt .= "Pearson r: {$data['r']}, R-squared: {$data['r2']}\n";
                $prompt .= "t-value: {$data['tValue']}, p-value: {$data['pValue']}\n";
                $prompt .= "Result is " . ($data['significant'] ? "Statistically Significant (p < 0.05)" : "Not Statistically Significant (p >= 0.05)") . ".\n\n";
                break;
            case 'anova':
                $prompt .= "Test: One-Way ANOVA (Comparing Multiple Group Means)\n";
                $prompt .= "Dependent Variable: '{$data['depLabel']}', Grouping Variable: '{$data['groupLabel']}'\n";
                $prompt .= "F-value: {$data['fValue']}, df Between: {$data['dfBetween']}, df Within: {$data['dfWithin']}, p-value: {$data['pValue']}\n";
                $prompt .= "Sum of Squares Between: {$data['ssb']}, Within: {$data['ssw']}\n";
                $prompt .= "Result is " . ($data['significant'] ? "Statistically Significant (p < 0.05)" : "Not Statistically Significant (p >= 0.05)") . ".\n\n";
                $prompt .= "Group Descriptives:\n" . json_encode($data['groupStats'], JSON_PRETTY_PRINT) . "\n\n";
                break;
            case 'regression':
                $prompt .= "Test: Simple Linear Regression\n";
                $prompt .= "Dependent Variable (Y): '{$data['depLabel']}', Independent Variable (X): '{$data['indLabel']}'\n";
                $prompt .= "Model: R: {$data['r']}, R-squared: {$data['r2']}, Adj. R-squared: {$data['adjR2']}, Std Error of Estimate: {$data['stdErrorEst']}\n";
                $prompt .= "ANOVA Regression test: F-value: {$data['anova']['fValue']}, p-value: {$data['anova']['pValue']}\n";
                $prompt .= "Coefficients:\n" . json_encode($data['coefficients'], JSON_PRETTY_PRINT) . "\n\n";
                break;
            case 'regression_multiple':
                $prompt .= "Test: Multiple Linear Regression\n";
                $prompt .= "Dependent Variable (Y): '{$data['depLabel']}'\n";
                $prompt .= "Model Summary: R: {$data['r']}, R-squared: {$data['r2']}, Adj. R-squared: {$data['adjR2']}, Std Error of Estimate: {$data['stdErrorEst']}\n";
                $prompt .= "Equation: {$data['equation']}\n";
                $prompt .= "ANOVA Model Fit test: F-value: {$data['anova']['fValue']}, df Regression: {$data['anova']['dfReg']}, df Residual: {$data['anova']['dfRes']}, p-value: {$data['anova']['pValue']}\n";
                $prompt .= "Coefficients:\n" . json_encode($data['coefficients'], JSON_PRETTY_PRINT) . "\n\n";
                break;
        }

        $targetLang = $this->getTargetLanguage();
        $prompt .= "OUTPUT FORMAT — NON-NEGOTIABLE:\n";
        $prompt .= "- Write EXACTLY ONE paragraph.\n";
        $prompt .= "- EXACTLY 3 to 4 sentences total. No more. No fewer.\n";
        $prompt .= "- Plain text only. NO Markdown formatting (no asterisks, no bolding, no italics, no bullet points, no headers).\n\n";
        $prompt .= "WRITING RULES:\n";
        $prompt .= "- Write in past tense throughout (e.g. 'indicated', 'showed', 'revealed', 'accounted for').\n";
        $prompt .= "- Use plain, direct English. Avoid GRE/SAT jargon.\n";
        $prompt .= "- Explicitly state whether the test result was statistically significant and what it means for the variables.\n";
        $prompt .= "- You MUST write the entire response in {$targetLang}.";

        try {
            $insight = $this->aiService->callAi($prompt, "You are an expert statistician. Write a concise, 3 to 4 sentence plain-text academic interpretation of statistical test results in past tense.", false, 300, 0.3);
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

        $messages = $request->input('messages');
        $feedback = $request->input('feedback');
        $style = $request->input('style', $survey->reporting_style ?? 'apa');

        if (empty($messages) || empty($feedback)) {
            return response()->json(['success' => false, 'message' => 'Conversation history and refinement instructions are required.'], 400);
        }

        $data = $this->getQuestionStatsAndLabel($survey, $questionId);
        $questionLabel = $data['label'];
        $statsText = "";
        foreach ($data['stats'] as $stat) {
            $statsText .= "Choice: {$stat['value']} | Count: {$stat['count']} | Percentage: {$stat['percentage']}%\n";
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

Here is the conversation history with the researcher:

";

        foreach ($messages as $msg) {
            $roleName = $msg['role'] === 'assistant' ? 'AI' : 'Researcher';
            $prompt .= "{$roleName}: {$msg['content']}\n\n";
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
            \Illuminate\Support\Facades\Cache::put($cacheKeyStyle, $insight, 86400);
            \Illuminate\Support\Facades\Cache::put($cacheKeyDefault, $insight, 86400);

            return response()->json(['success' => true, 'insight' => $insight]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'AI Refinement Failed: ' . $e->getMessage()], 500);
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
