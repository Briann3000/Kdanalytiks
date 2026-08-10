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
            if (empty($r))
                return false;

            $r = is_array($r) ? implode(', ', $r) : (string) $r;

            // Skip data URLs (base64) often found in signatures/images
            if (str_starts_with($r, 'data:image/') || str_contains($r, ';base64,')) {
                return false;
            }

            // Skip very short or non-informative strings that look like IDs
            if (strlen(trim($r)) < 2)
                return false;

            return true;
        });

        if (empty($responses)) {
            return [
                'sentiment_breakdown' => ['Positive' => 0, 'Neutral' => 0, 'Negative' => 0],
                'key_themes' => [],
                'representative_quotes' => [],
                'error' => 'Insufficient qualitative text data for analysis.'
            ];
        }

        // Balance between AI context quality and Groq TPM limits
        $responses = array_map(function ($r) {
            $r = is_array($r) ? implode(', ', $r) : (string) $r;
            return strlen($r) > 200 ? substr($r, 0, 197) . '...' : $r;
        }, $responses);

        $chunks = array_chunk($responses, 25);

        return $this->processChunk($chunks[0], $questionText);
    }

    /**
     * Send a specific chunk of responses to Groq.
     */
    protected function processChunk(array $batch, string $questionText = null): array
    {
        $textData = implode("\n---\n", $batch);

        $targetLang = $this->getTargetLanguage();
        $systemPrompt = "You are a professional Political Data Analyst. 
Analyze the provided responses and return a strict JSON object.";

        if ($questionText) {
            $systemPrompt .= "\nThese responses were gathered specifically in response to the question: \"{$questionText}\". Ensure your analysis directly targets and answers this question.";
        }

        $systemPrompt .= "\n\nJSON STRUCTURE:
{
  \"sentiment\": {
    \"positive\": 0, 
    \"neutral\": 0, 
    \"negative\": 0
  },
  \"key_themes\": [
    { \"theme\": \"Theme Name in {$targetLang}\", \"explanation\": \"Brief detail of why this is a concern in {$targetLang}\" }
  ],
  \"top_quotes\": [
    \"Direct, impactful quote 1 in {$targetLang}\",
    \"Direct, impactful quote 2 in {$targetLang}\",
    \"Direct, impactful quote 3 in {$targetLang}\"
  ]
}

RULES:
1. 'sentiment' values must be percentages summing to 100.
2. 'key_themes' should be the 3-5 most frequent issues.
3. 'top_quotes' should be the 3 most representative and emotionally resonant excerpts.
4. Respond ONLY with the JSON object.
5. All text values inside the JSON object (Theme names, explanations, representative quotes) MUST be written in the {$targetLang} language. Do not output them in English if the target language is different.";

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

            return [
                'sentiment_breakdown' => [
                    'Positive' => $data['sentiment']['positive'] ?? 0,
                    'Neutral' => $data['sentiment']['neutral'] ?? 0,
                    'Negative' => $data['sentiment']['negative'] ?? 0
                ],
                'key_themes' => $data['key_themes'] ?? [],
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
You are a quantitative research analyst. Your task is to write a brief trend interpretation of survey frequency data.

OUTPUT FORMAT — NON-NEGOTIABLE:
- Write EXACTLY ONE paragraph.
- EXACTLY 3 to 4 sentences total. No more. No fewer.
- No bullet points, no numbered lists, no headings, no Markdown of any kind.
- No asterisks, no pound signs, no backticks, no bold, no italics.
- Pure plain text only.
- DO NOT reference raw code identifiers or field names like "field-1783944490217" or "question-123".

TONE: {$styleTone}

LANGUAGE: Write entirely in {$targetLang}.

WRITING RULES:
- Write in past tense throughout (e.g., "emerged", "indicated", "accounted for", "revealed").
- Use plain, direct English. Replace complex academic jargon with simpler words.
- Do not open with clichés like "Based on the provided data", "Looking at the chart", or "The data shows".
- Use percentages only (e.g., 24.4%). Never include raw counts or sample sizes like (n = 122).

STRUCTURE (do NOT produce visible labels — write it as seamless flowing prose):
- Sentences 1–2: Report and rank the key percentage findings. Highlight the dominant choice and notable contrasts.
- Sentences 3–4: Provide a grounded analytical conclusion. Begin naturally with an academic marker phrase (vary these — do not always use the same opener). Keep the conclusion strictly tied to the numbers. Do not speculate, invent causes, or introduce external context not present in the data.
PROMPT;

        $userMessage = "STATISTICAL DATA:\n{$statsText}\n\nWrite ONE paragraph, 3 to 4 sentences, plain text only.";

        try {
            $content = $this->aiService->callAi($userMessage, $systemPrompt, false, 300, 0.3);
            if ($content) {
                $content = preg_replace('/\bfield-\d+\b/i', 'this question', $content);
                $content = preg_replace('/\bquestion-\d+\b/i', 'this question', $content);
            }
            return $content ?? "No insight generated.";

        } catch (\Exception $e) {
            Log::error('QualitativeAnalysisService Quant Error: ' . $e->getMessage());
            return "Unable to analyze data at this time.";
        }
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
You are a quantitative research analyst interpreting a Likert Matrix survey question.

OUTPUT FORMAT — NON-NEGOTIABLE:
- Write EXACTLY ONE paragraph.
- EXACTLY 3 to 4 sentences total. No more. No fewer.
- No bullet points, no numbered lists, no headings, no Markdown of any kind.
- No asterisks, no pound signs, no backticks, no bold, no italics.
- Pure plain text only.
- DO NOT reference internal code identifiers or field names like "field-1783944490217" or "question-123". Refer to items by their statement text.

TONE: {$styleTone}

LANGUAGE: Write entirely in {$targetLang}.

WRITING RULES:
- Focus your analysis on comparing and contrasting the findings across the specific statement items (rows) in the matrix.
- Highlight which specific statement items received the highest positive agreement (Agree / Strongly Agree) and which statement items recorded higher neutral or disagree responses.
- Write in past tense throughout (e.g., "recorded", "indicated", "revealed", "emerged").
- Use percentages only (e.g., 60.0%). Never include raw counts or sample sizes like (n = 122).
- Do not open with clichés like "Based on the provided data".
PROMPT;

        $userMessage = "STATISTICAL DATA:\n{$statsText}\n\nWrite ONE paragraph, 3 to 4 sentences, plain text only interpreting the statement items.";

        try {
            $content = $this->aiService->callAi($userMessage, $systemPrompt, false, 300, 0.3);
            if ($content) {
                $content = preg_replace('/\bfield-\d+\b/i', 'this question', $content);
                $content = preg_replace('/\bquestion-\d+\b/i', 'this question', $content);
            }
            return $content ?? "No insight generated.";
        } catch (\Exception $e) {
            Log::error('QualitativeAnalysisService Likert Error: ' . $e->getMessage());
            return "Unable to analyze Likert matrix data at this time.";
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
