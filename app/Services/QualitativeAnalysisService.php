<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QualitativeAnalysisService
{
    protected AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Analyze a collection of text responses.
     */
    public function analyzeResponses(array $responses, string $questionText = null): array
    {
        // 1. Filter out non-textual or empty content (e.g. signatures, base64 images, files)
        $responses = array_filter($responses, function ($r) {
            if ($r === null || $r === '') {
                return false;
            }

            $r = is_array($r) ? implode(', ', $r) : (string) $r;
            $trimmed = trim($r);

            if ($trimmed === '' || $trimmed === '[missing / skipped]') {
                return false;
            }

            // Skip data URLs (base64) often found in signatures/images
            if (str_starts_with($trimmed, 'data:image/') || str_contains($trimmed, ';base64,')) {
                return false;
            }

            return true;
        });

        if (empty($responses)) {
            return [
                'sentiment_breakdown' => ['Positive' => 0, 'Neutral' => 100, 'Negative' => 0],
                'narrative' => __('No responses have been submitted for this question yet.'),
                'key_findings' => [__('Awaiting participant submissions to generate qualitative thematic synthesis.')],
                'key_themes' => [],
                'representative_quotes' => []
            ];
        }

        // Balance between AI context quality and Groq TPM limits
        $responses = array_map(function ($r) {
            $r = is_array($r) ? implode(', ', $r) : (string) $r;
            return strlen($r) > 200 ? substr($r, 0, 197) . '...' : $r;
        }, array_values($responses));

        $chunks = array_chunk($responses, 25);

        return $this->processChunk($chunks[0], $questionText);
    }

    /**
     * Send a specific chunk of responses to Groq.
     */
    protected function processChunk(array $batch, string $questionText = null): array
    {
        $textData = implode("\n---\n", $batch);
        $count = count($batch);

        $targetLang = $this->getTargetLanguage();
        $systemPrompt = "You are a professional Political and Survey Data Analyst. 
Analyze the provided responses and return a strict JSON object.";

        if ($questionText) {
            $systemPrompt .= "\nThese responses were gathered specifically in response to the question: \"{$questionText}\". Ensure your analysis directly targets and interprets this question.";
        }

        $systemPrompt .= "\n\nNote: There are {$count} response(s) provided (which may include written open-ended text and verbatim audio interview transcriptions). Even if the sample size is small or answers are brief, synthesize all available spoken and written information meaningfully, extract key recurring themes, identify sentiment tone, and provide an insightful academic narrative synthesis.";

        $systemPrompt .= "\n\nJSON STRUCTURE:
{
  \"sentiment\": {
    \"positive\": 0, 
    \"neutral\": 0, 
    \"negative\": 0
  },
  \"narrative\": \"A cohesive 2 to 3 sentence academic synthesis in {$targetLang} summarizing participant sentiment and qualitative findings.\",
  \"key_findings\": [
    \"Key finding 1 in {$targetLang}\",
    \"Key finding 2 in {$targetLang}\"
  ],
  \"conclusion\": \"A concise concluding academic prose paragraph (2 to 3 sentences) in {$targetLang} inferring overarching implications, strategic takeaways, and patterns deduced from the narrative, key findings, and recurring themes.\",
  \"key_themes\": [
    {
      \"theme\": \"Theme Name in {$targetLang}\",
      \"narrative\": \"A detailed academic prose paragraph in {$targetLang} explaining what respondents stated regarding this theme, its significance, and nuanced observations.\",
      \"quotes\": [
        \"Direct verbatim quote or spoken excerpt supporting this theme in {$targetLang}\"
      ]
    }
  ],
  \"top_quotes\": [
    \"Direct, impactful quote 1 in {$targetLang}\",
    \"Direct, impactful quote 2 in {$targetLang}\"
  ]
}

RULES:
1. 'sentiment' values must be percentages summing to 100. If neutral, informational, or factual (e.g. dates, times), classify appropriately (e.g. 100% neutral). For spoken audio interviews, reflect the participant's expressed tone and sentiment.
2. 'narrative' should be an academic synthesis in past tense analyzing the overarching patterns, viewpoints, or verbal explanations given by respondents.
3. 'key_findings' should be 1-3 concise summary takeaways.
4. 'conclusion' should be a cohesive concluding academic prose paragraph synthesizing the broader implications deduced from the narrative, takeaways, and themes.
5. 'key_themes' should be 1-4 observed themes. Each theme MUST have a cohesive 'narrative' prose paragraph detailing the thematic findings and 1-2 direct 'quotes'.
6. 'top_quotes' should be 1-3 representative quotes or verbatim spoken excerpts from the provided data.
7. Respond ONLY with the JSON object.
8. All text values inside the JSON object MUST be written in the {$targetLang} language. Do not output them in English if the target language is different.";

        try {
            Log::info("QualitativeAnalysisService: Analyzing batch of " . count($batch) . " responses.");

            $content = $this->aiService->callAi("VOTER RESPONSES TO ANALYZE:\n" . $textData, $systemPrompt, true);

            if (empty($content)) {
                throw new \Exception('AI analysis service failed/rate limited.');
            }

            Log::info("QualitativeAnalysisService: Raw AI content: " . substr($content, 0, 100) . "...");

            $data = json_decode($content, true);
            if (!$data)
                throw new \Exception('Malformed AI JSON response.');

            // Ensure structure consistency with ai-insight-card.blade.php
            $rawQuotes = $data['top_quotes'] ?? [];
            $cleanQuotes = is_array($rawQuotes) ? array_values(array_unique(array_filter(array_map('trim', $rawQuotes)))) : [];

            $rawThemes = $data['key_themes'] ?? [];
            $formattedThemes = [];
            if (is_array($rawThemes)) {
                foreach ($rawThemes as $themeItem) {
                    if (is_array($themeItem) && !empty($themeItem['theme'])) {
                        $narrativeText = $themeItem['narrative'] ?? $themeItem['explanation'] ?? '';
                        $themeQuotes = $themeItem['quotes'] ?? [];
                        if (!is_array($themeQuotes)) {
                            $themeQuotes = $themeQuotes ? [$themeQuotes] : [];
                        }
                        $formattedThemes[] = [
                            'theme' => $themeItem['theme'],
                            'narrative' => $narrativeText,
                            'explanation' => $narrativeText,
                            'quotes' => array_values(array_filter(array_map('trim', $themeQuotes)))
                        ];
                    }
                }
            }

            return [
                'sentiment_breakdown' => [
                    'Positive' => $data['sentiment']['positive'] ?? 0,
                    'Neutral' => $data['sentiment']['neutral'] ?? 0,
                    'Negative' => $data['sentiment']['negative'] ?? 0
                ],
                'narrative' => $data['narrative'] ?? '',
                'key_findings' => $data['key_findings'] ?? [],
                'conclusion' => $data['conclusion'] ?? '',
                'key_themes' => $formattedThemes,
                'representative_quotes' => $cleanQuotes
            ];

        } catch (\Exception $e) {
            Log::error('QualitativeAnalysisService Exception: ' . $e->getMessage());
            return [
                'sentiment_breakdown' => ['Positive' => 0, 'Neutral' => 0, 'Negative' => 0],
                'key_themes' => [],
                'representative_quotes' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Analyze quantitative statistical data.
     */
    public function analyzeQuantitativeData(array $stats, ?string $questionText = null, string $style = 'apa'): string
    {
        if ($questionText && (preg_match('/^field-\d+$/i', trim($questionText)) || preg_match('/^question[-_\d]+$/i', trim($questionText)))) {
            $questionText = "this survey question";
        }

        $statsText = "";
        if ($questionText) {
            $statsText .= "QUESTION: {$questionText}\n";
        }
        foreach ($stats as $stat) {
            if (isset($stat['is_missing']) && $stat['is_missing'])
                continue;
            $statsText .= "Choice: " . $stat['value'] . " | Count: " . $stat['count'] . " | Percentage: " . $stat['percentage'] . "%\n";
        }

        //fetch KB rules
        $kbPromptSection = "";
        if (auth()->check()) {
            $kbRules = auth()->user()->sociusKnowledgeBases()
                ->where('is_active', true)
                ->pluck('content')
                ->filter()
                ->implode("\n- ");

            if (!empty($kbRules)) {
                $kbPromptSection = "\n\nCUSTOM USER INSTRUCTIONS:\n- " . $kbRules;
            }
        }
        // Style-specific tone descriptors — tone only, length and format are enforced globally below
        $styleTones = [
            'apa' => 'Write in a formal academic tone consistent with APA 7th edition conventions.',
            'harvard' => 'Write in a formal academic tone consistent with Harvard referencing conventions.',
            'oscola' => 'Write in a precise, structured analytical tone consistent with legal scholarship.',
            'ieee' => 'Write in a concise, technical and objective tone consistent with IEEE engineering style.',
            'vancouver' => 'Write in an objective, systematic tone consistent with biomedical research reporting.',
            'mla' => 'Write in a cohesive, prose-driven tone consistent with MLA humanities conventions.',
        ];

        $styleTone = $styleTones[$style] ?? $styleTones['apa'];
        $targetLang = $this->getTargetLanguage();

        $systemPrompt = <<<PROMPT
You are a senior quantitative research analyst. Write a concise, professional academic trend interpretation of the provided survey frequency data.

OUTPUT REQUIREMENTS:
- Write ONE cohesive paragraph (approximately 3 to 4 sentences).
- Always finish the entire paragraph completely with a closing period. Never stop mid-sentence.
- Use pure plain text only: NO Markdown, NO asterisks, NO bullet points, NO headings.
- Base ALL insights strictly on the provided frequency numbers and percentages.
- DO NOT reference internal field identifiers or raw IDs like "field-12345" or "question-1".

TONE: {$styleTone}
LANGUAGE: Write entirely in {$targetLang}.

ANALYSIS GUIDANCE:
- Report the dominant findings, percentages, and key distributional patterns in past tense.
- Provide a grounded analytical observation summarizing what these proportions indicate.
PROMPT;

        $userMessage = "STATISTICAL DATA:\n{$statsText}\n\nWrite ONE paragraph (3 to 4 sentences) of plain text trend interpretation. Ensure the text ends cleanly with a period.";

        try {
            $content = $this->aiService->callAi($userMessage, $systemPrompt, false, 2048, 0.4);
            if ($content && trim($content) !== '') {
                $content = preg_replace('/\bfield-\d+\b/i', 'this question', $content);
                $content = preg_replace('/\bquestion-\d+\b/i', 'this question', $content);
                return trim($content);
            }
        } catch (\Throwable $e) {
            Log::warning('QualitativeAnalysisService Quant AI call failed, falling back to deterministic synthesis: ' . $e->getMessage());
        }

        // Deterministic high-precision fallback ensures bulk exports (160+ questions) never stall or return empty
        return self::generateDeterministicQuantitativeInsight($stats, $questionText, $style);
    }

    /**
     * Deterministic, instantaneous academic statistical trend interpretation fallback.
     */
    public static function generateDeterministicQuantitativeInsight(array $stats, ?string $questionText = null, string $style = 'apa'): string
    {
        $cleanTitle = "this surveyed item";
        if ($questionText && !preg_match('/^field-\d+$/i', trim($questionText)) && !preg_match('/^question[-_\d]+$/i', trim($questionText))) {
            $cleanTitle = '"' . trim(rtrim($questionText, "?:.")) . '"';
        }

        $validStats = [];
        foreach ($stats as $s) {
            if (empty($s['is_missing']) && ($s['value'] ?? '') !== 'Skipped' && ($s['value'] ?? '') !== 'Missing') {
                $validStats[] = [
                    'value' => (string) ($s['value'] ?? 'Option'),
                    'count' => (int) ($s['count'] ?? 0)
                ];
            }
        }

        if (empty($validStats)) {
            return "Descriptive analysis for {$cleanTitle} indicates that no valid participant responses were recorded for this item.";
        }

        usort($validStats, fn($a, $b) => $b['count'] <=> $a['count']);
        $total = array_sum(array_column($validStats, 'count'));
        if ($total <= 0) {
            return "Descriptive analysis for {$cleanTitle} indicates that no responses were registered.";
        }

        $top = $validStats[0];
        $topPct = round(($top['count'] / $total) * 100, 1);
        $second = $validStats[1] ?? null;
        $secondPct = $second ? round(($second['count'] / $total) * 100, 1) : 0;
        $least = count($validStats) > 1 ? end($validStats) : null;
        $leastPct = $least ? round(($least['count'] / $total) * 100, 1) : 0;

        // Sentence 1: Primary Finding
        if ($topPct >= 60) {
            $s1 = "Descriptive frequency analysis for {$cleanTitle} demonstrates a clear consensus, with a substantial majority of respondents ({$topPct}%, n = {$top['count']}) selecting \"{$top['value']}\".";
        } elseif ($topPct >= 40) {
            $s1 = "An examination of response patterns for {$cleanTitle} indicates a predominant plurality favoring \"{$top['value']}\", representing {$topPct}% of the sample (n = {$top['count']}).";
        } else {
            $s1 = "Analysis of responses for {$cleanTitle} reveals a diversified distribution across categories, led by \"{$top['value']}\" with {$topPct}% of total observations (n = {$top['count']}).";
        }

        // Sentence 2: Secondary / Dispersion Context
        if ($second && count($validStats) > 2) {
            $s2 = "In comparison, the second most frequent response was \"{$second['value']}\" ({$secondPct}%, n = {$second['count']}), while \"{$least['value']}\" recorded the lowest frequency at {$leastPct}% (n = {$least['count']}).";
        } elseif ($second) {
            $s2 = "The remaining proportion of participants selected \"{$second['value']}\", accounting for {$secondPct}% of the responses (n = {$second['count']}).";
        } else {
            $s2 = "All participating respondents uniformly selected this single option across the survey.";
        }

        // Sentence 3: Academic Implication / Synthesis
        if ($topPct >= 50) {
            $s3 = "These empirical results reflect strong respondent alignment and consistent prioritization of the leading choice within the sample.";
        } elseif (count($validStats) > 2 && ($topPct - $secondPct) < 10) {
            $s3 = "The narrow margin between leading selections suggests balanced perspectives and nuanced evaluations among respondents.";
        } else {
            $s3 = "Overall, the distribution demonstrates measurable directional tendencies that substantiate the underlying quantitative findings.";
        }

        return "{$s1} {$s2} {$s3}";
    }

    /**
     * Analyze Likert matrix statistical data row item by row item.
     */
    public function analyzeLikertMatrixData(array $likertMatrixRows, ?string $questionText = null, string $style = 'apa'): string
    {
        if ($questionText && (preg_match('/field-\d+/i', trim($questionText)) || preg_match('/question[-_\d]+/i', trim($questionText)))) {
            $questionText = "Likert Matrix Question";
        }

        $statsText = "";
        if ($questionText) {
            $statsText .= "QUESTION: {$questionText}\n";
        }
        $statsText .= "STATEMENT / ROW ITEM BREAKDOWN:\n";

        foreach ($likertMatrixRows as $row) {
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

        $styleTones = [
            'apa' => 'Write in a formal academic tone consistent with APA 7th edition conventions.',
            'harvard' => 'Write in a formal academic tone consistent with Harvard referencing conventions.',
            'oscola' => 'Write in a precise, structured analytical tone consistent with legal scholarship.',
            'ieee' => 'Write in a concise, technical and objective tone consistent with IEEE engineering style.',
            'vancouver' => 'Write in an objective, systematic tone consistent with biomedical research reporting.',
            'mla' => 'Write in a cohesive, prose-driven tone consistent with MLA humanities conventions.',
        ];

        $styleTone = $styleTones[$style] ?? $styleTones['apa'];
        $targetLang = $this->getTargetLanguage();

        $systemPrompt = <<<PROMPT
You are a senior quantitative research analyst interpreting a Likert Matrix survey question. Write a cohesive, professional academic interpretation of the statement items.

OUTPUT REQUIREMENTS:
- Write ONE cohesive paragraph (approximately 3 to 4 sentences).
- Always finish the entire paragraph completely with a closing period. Never stop mid-sentence.
- Use pure plain text only: NO Markdown, NO asterisks, NO bullet points, NO headings.
- Base ALL insights strictly on the provided statement item frequencies and percentages.
- Refer to statement items by their actual statement names, NOT internal codes or IDs.

TONE: {$styleTone}
LANGUAGE: Write entirely in {$targetLang}.

ANALYSIS GUIDANCE:
- Compare and contrast the agreement levels across the statement items in the matrix.
- Highlight which specific statements received the strongest agreement and which had notable neutrality or disagreement.
PROMPT;

        $userMessage = "STATISTICAL DATA:\n{$statsText}\n\nWrite ONE paragraph (3 to 4 sentences) of plain text interpreting these Likert Matrix statement items. Ensure the text ends cleanly with a period.";

        try {
            $content = $this->aiService->callAi($userMessage, $systemPrompt, false, 2048, 0.4);
            if ($content && trim($content) !== '') {
                $content = preg_replace('/\bfield-\d+\b/i', 'this question', $content);
                $content = preg_replace('/\bquestion-\d+\b/i', 'this question', $content);
                return trim($content);
            }
        } catch (\Throwable $e) {
            Log::warning('QualitativeAnalysisService Likert Error, falling back to deterministic synthesis: ' . $e->getMessage());
        }

        return self::generateDeterministicLikertInsight($likertMatrixRows, $questionText, $style);
    }

    /**
     * Deterministic, instantaneous academic interpretation fallback for Likert Matrix questions.
     */
    public static function generateDeterministicLikertInsight(array $likertMatrixRows, ?string $questionText = null, string $style = 'apa'): string
    {
        $cleanTitle = "the multidimensional Likert items";
        if ($questionText && !preg_match('/^field-\d+$/i', trim($questionText)) && !preg_match('/^question[-_\d]+$/i', trim($questionText))) {
            $cleanTitle = '"' . trim(rtrim($questionText, "?:.")) . '"';
        }

        if (empty($likertMatrixRows)) {
            return "Analysis of {$cleanTitle} reveals that no matrix items were submitted for evaluation.";
        }

        $rowSummaries = [];
        foreach ($likertMatrixRows as $row) {
            $rowLabel = $row['label'] ?? $row['value'] ?? 'Item';
            $stats = $row['stats'] ?? [];
            $valid = array_filter($stats, fn($s) => empty($s['is_missing']));
            if (empty($valid))
                continue;

            $total = array_sum(array_column($valid, 'count'));
            $topOption = null;
            $maxCount = -1;
            foreach ($valid as $s) {
                if (($s['count'] ?? 0) > $maxCount) {
                    $maxCount = $s['count'];
                    $topOption = $s;
                }
            }

            $rowSummaries[] = [
                'label' => $rowLabel,
                'top_option' => $topOption['value'] ?? 'Option',
                'top_pct' => $total > 0 ? round(($maxCount / $total) * 100, 1) : 0,
                'total' => $total
            ];
        }

        if (empty($rowSummaries)) {
            return "Descriptive evaluation across {$cleanTitle} indicates consistent completion across all matrix items.";
        }

        usort($rowSummaries, fn($a, $b) => $b['top_pct'] <=> $a['top_pct']);
        $highest = $rowSummaries[0];
        $lowest = end($rowSummaries);

        $s1 = "Evaluation of the multidimensional Likert grid for {$cleanTitle} reveals distinct respondent evaluation patterns across the measured items.";
        $s2 = "The strongest consensus emerged for statement \"{$highest['label']}\", with {$highest['top_pct']}% of participants selecting \"{$highest['top_option']}\".";

        if (count($rowSummaries) > 1 && $highest['label'] !== $lowest['label']) {
            $s3 = "In contrast, statement \"{$lowest['label']}\" demonstrated greater response dispersion ({$lowest['top_pct']}% selecting \"{$lowest['top_option']}\"), indicating comparative diversity in respondent attitudes.";
        } else {
            $s3 = "Overall, response distributions reflect coherent evaluation across all constituent statement items.";
        }

        return "{$s1} {$s2} {$s3}";
    }

    /**
     * Synthesize qualitative narrative and structured thematic coding for open-ended text responses.
     */
    public function synthesizeNarrative(array $responses, ?string $questionText = null, string $style = 'apa'): array
    {
        // 1. Filter out non-textual or empty content
        $cleanedResponses = [];
        foreach ($responses as $idx => $r) {
            if (empty($r))
                continue;
            $str = is_array($r) ? implode(', ', $r) : (string) $r;
            if (str_starts_with($str, 'data:image/') || str_contains($str, ';base64,'))
                continue;
            if (strlen(trim($str)) < 2)
                continue;
            $cleanedResponses[] = [
                'index' => $idx + 1,
                'text' => strlen($str) > 250 ? substr($str, 0, 247) . '...' : $str,
                'full_text' => $str
            ];
        }

        if (empty($cleanedResponses)) {
            return [
                'thematic_summary' => [],
                'narrative' => 'Insufficient qualitative text responses collected for thematic synthesis.',
                'response_codings' => [],
                'key_findings' => [],
                'error' => 'Insufficient qualitative text data for synthesis.'
            ];
        }

        $totalCount = count($cleanedResponses);
        // Take a representative sample (up to 40 responses) to respect LLM context tokens
        $sample = array_slice($cleanedResponses, 0, 40);
        $textList = "";
        foreach ($sample as $item) {
            $textList .= "[Response {$item['index']}]: \"{$item['text']}\"\n";
        }

        // Fetch KB rules
        $kbPromptSection = "";
        if (auth()->check()) {
            $kbRules = auth()->user()->sociusKnowledgeBases()
                ->where('is_active', true)
                ->pluck('content')
                ->filter()
                ->implode("\n- ");

            if (!empty($kbRules)) {
                $kbPromptSection = "\n\nCUSTOM INSTRUCTIONS:\n- " . $kbRules;
            }
        }

        $styleTones = [
            'apa' => 'Write in a formal academic tone consistent with APA 7th edition conventions.',
            'harvard' => 'Write in a formal academic tone consistent with Harvard referencing conventions.',
            'oscola' => 'Write in a precise, structured analytical tone consistent with legal scholarship.',
            'ieee' => 'Write in a concise, technical and objective tone consistent with IEEE engineering style.',
            'vancouver' => 'Write in an objective, systematic tone consistent with biomedical research reporting.',
            'mla' => 'Write in a cohesive, prose-driven tone consistent with MLA humanities conventions.',
        ];
        $styleTone = $styleTones[$style] ?? $styleTones['apa'];
        $targetLang = $this->getTargetLanguage();

        if ($questionText && (preg_match('/^field-\d+$/i', trim($questionText)) || preg_match('/^question[-_\d]+$/i', trim($questionText)))) {
            $questionText = "this survey question";
        }

        $systemPrompt = <<<PROMPT
You are a senior qualitative research analyst and methodologist.
Analyze the qualitative open-ended participant responses and return a STRICT, valid JSON object.

QUESTION CONTEXT: "{$questionText}"
TONE: {$styleTone}
LANGUAGE: Write all explanations, themes, and narrative synthesis entirely in {$targetLang}.
{$kbPromptSection}

OUTPUT JSON SCHEMA:
{
  "thematic_summary": [
    {
      "theme": "Theme Name in {$targetLang}",
      "description": "Concise definition of the theme in {$targetLang}",
      "count": 0,
      "percentage": 0
    }
  ],
  "narrative": "A cohesive 3 to 4 sentence academic synthesis paragraph in {$targetLang} synthesizing the core qualitative findings in past tense. Plain text only. No asterisks, no bullet points.",
  "key_findings": [
    "Key qualitative takeaway 1 in {$targetLang}",
    "Key qualitative takeaway 2 in {$targetLang}",
    "Key qualitative takeaway 3 in {$targetLang}"
  ],
  "response_codings": [
    {
      "index": 1,
      "theme": "Assigned Theme Name",
      "tone": "Positive|Neutral|Critical"
    }
  ]
}

RULES:
1. 'thematic_summary' should contain 3 to 5 predominant themes. Ensure counts across themes reflect relative frequency in the sample and percentages sum to approximately 100%.
2. 'narrative' must be ONE cohesive paragraph (3 to 4 sentences) of rigorous academic prose in past tense. Never use internal IDs or meta-commentary.
3. 'response_codings' should assign each response index in the provided list to its most fitting theme and tone (strictly 'Positive', 'Neutral', or 'Critical').
4. Respond ONLY with the JSON object. No Markdown formatting wrappers around the JSON if possible.
PROMPT;

        $userMessage = "PARTICIPANT RESPONSES TO ANALYZE (Total in sample: " . count($sample) . "):\n{$textList}\n\nReturn the strict JSON synthesis object.";

        try {
            $content = $this->aiService->callAi($userMessage, $systemPrompt, true, 3000, 0.3);
            if (empty($content)) {
                throw new \Exception('Synthesis generation timed out or returned empty.');
            }

            $data = json_decode($content, true);
            if (!$data || !isset($data['thematic_summary']) || !isset($data['narrative'])) {
                throw new \Exception('Malformed synthesis JSON response.');
            }

            // Normalise counts & percentages if necessary
            $thematicSummary = $data['thematic_summary'] ?? [];
            foreach ($thematicSummary as &$theme) {
                if (!isset($theme['percentage']) || $theme['percentage'] == 0) {
                    $cnt = $theme['count'] ?? 1;
                    $theme['percentage'] = $totalCount > 0 ? round(($cnt / $totalCount) * 100, 1) : 0;
                }
            }

            return [
                'thematic_summary' => $thematicSummary,
                'narrative' => $data['narrative'] ?? 'No narrative generated.',
                'key_findings' => $data['key_findings'] ?? [],
                'response_codings' => $data['response_codings'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('QualitativeAnalysisService synthesizeNarrative error: ' . $e->getMessage());
            return [
                'thematic_summary' => [],
                'narrative' => 'Unable to generate qualitative narrative synthesis at this time.',
                'key_findings' => [],
                'response_codings' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Deep extraction and synthesis for audio/video media transcripts.
     */
    public function extractFromTranscripts(array $transcripts, ?string $questionText = null, string $style = 'apa'): array
    {
        $validTranscripts = [];
        foreach ($transcripts as $idx => $t) {
            $text = is_array($t) ? ($t['text'] ?? $t['transcript'] ?? '') : (string) $t;
            if (empty(trim($text)))
                continue;
            $validTranscripts[] = [
                'index' => $idx + 1,
                'text' => strlen($text) > 300 ? substr($text, 0, 297) . '...' : $text
            ];
        }

        if (empty($validTranscripts)) {
            return [
                'thematic_summary' => [],
                'thematic_narrative' => 'No recorded transcriptions found for this media question.',
                'key_findings' => ['No verbatim audio or video transcripts available for analysis.'],
                'response_codings' => []
            ];
        }

        $sample = array_slice($validTranscripts, 0, 30);
        $transcriptText = "";
        foreach ($sample as $item) {
            $transcriptText .= "[Recording {$item['index']}]: \"{$item['text']}\"\n";
        }

        $styleTones = [
            'apa' => 'Write in a formal academic tone consistent with APA 7th edition conventions.',
            'harvard' => 'Write in a formal academic tone consistent with Harvard referencing conventions.',
            'oscola' => 'Write in a precise, structured analytical tone consistent with legal scholarship.',
            'ieee' => 'Write in a concise, technical and objective tone consistent with IEEE engineering style.',
            'vancouver' => 'Write in an objective, systematic tone consistent with biomedical research reporting.',
            'mla' => 'Write in a cohesive, prose-driven tone consistent with MLA humanities conventions.',
        ];
        $styleTone = $styleTones[$style] ?? $styleTones['apa'];
        $targetLang = $this->getTargetLanguage();

        $systemPrompt = <<<PROMPT
You are a senior qualitative researcher specializing in verbatim transcription analysis.
Examine the recorded voice/video transcripts collected for: "{$questionText}"
Analyze themes, implicit attitudes, and verbatim statements.

OUTPUT JSON SCHEMA:
{
  "thematic_summary": [
    {
      "theme": "Theme Name in {$targetLang}",
      "description": "Explanation of the theme in {$targetLang}",
      "count": 0,
      "percentage": 0
    }
  ],
  "thematic_narrative": "A cohesive 3 to 4 sentence academic interpretation paragraph in {$targetLang} summarizing the spoken feedback in past tense.",
  "key_findings": [
    "Key extracted spoken insight 1 in {$targetLang}",
    "Key extracted spoken insight 2 in {$targetLang}",
    "Key extracted spoken insight 3 in {$targetLang}"
  ],
  "response_codings": [
    {
      "index": 1,
      "theme": "Assigned Theme Name",
      "tone": "Positive|Neutral|Critical"
    }
  ]
}

RULES:
1. All narrative text must be strictly in {$targetLang}.
2. Tone: {$styleTone}
3. Respond ONLY with the JSON object.
PROMPT;

        try {
            $content = $this->aiService->callAi("VERBATIM TRANSCRIPTS:\n" . $transcriptText, $systemPrompt, true, 3000, 0.3);
            $data = json_decode($content, true);
            if (!$data || !isset($data['thematic_narrative'])) {
                throw new \Exception('Malformed transcript analysis JSON.');
            }

            return [
                'thematic_summary' => $data['thematic_summary'] ?? [],
                'thematic_narrative' => $data['thematic_narrative'] ?? 'Analysis completed.',
                'key_findings' => $data['key_findings'] ?? [],
                'response_codings' => $data['response_codings'] ?? []
            ];
        } catch (\Exception $e) {
            Log::error('QualitativeAnalysisService extractFromTranscripts error: ' . $e->getMessage());
            return [
                'thematic_summary' => [],
                'thematic_narrative' => 'Unable to extract findings from transcripts at this time.',
                'key_findings' => [],
                'response_codings' => [],
                'error' => $e->getMessage()
            ];
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

