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
            return [
                'sentiment_breakdown' => [
                    'Positive' => $data['sentiment']['positive'] ?? 0,
                    'Neutral' => $data['sentiment']['neutral'] ?? 0,
                    'Negative' => $data['sentiment']['negative'] ?? 0
                ],
                'key_themes' => $data['key_themes'] ?? [],
                'representative_quotes' => $data['top_quotes'] ?? []
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
        $statsText = "";
        if ($questionText) {
            $statsText .= "QUESTION: {$questionText}\n";
        }
        foreach ($stats as $stat) {
            if (isset($stat['is_missing']) && $stat['is_missing'])
                continue;
            $statsText .= "Choice: " . $stat['value'] . " | Count: " . $stat['count'] . " | Percentage: " . $stat['percentage'] . "%\n";
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

        $systemPrompt = "{$styleRules}
STRICT DATA-SYNTHESIS & DATA-GROUNDING RULES:
- Base your analysis STRICTLY AND EXCLUSIVELY on the provided question title and statistical payload. Do NOT hallucinate external facts or statistics not in the payload.
- DO NOT use a rigid or hardcoded template structure. Synthesize the findings dynamically, explaining the practical significance of the majorities, central tendencies, or distribution spread.
- Avoid cliché robotic intro phrases like 'Based on the provided data' or 'Looking at the chart'. Use varied, scholarly sentence structures every single time.
- You MUST write the entire response in the {$targetLang} language.";

        try {
            $content = $this->aiService->callAi("STATISTICAL DATA:\n" . $statsText, $systemPrompt, false);
            return $content ?? "No insight generated.";

        } catch (\Exception $e) {
            Log::error('QualitativeAnalysisService Quant Error: ' . $e->getMessage());
            return "Unable to analyze data at this time.";
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
