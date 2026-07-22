<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QualitativeAnalysisService
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.groq.api_key');
        $this->model = config('services.groq.model', 'llama-3.1-8b-instant');
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

            $response = null;
            $maxRetries = 3;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                $response = Http::withToken($this->apiKey)
                    ->timeout(90)
                    ->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model' => $this->model,
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => "VOTER RESPONSES TO ANALYZE:\n" . $textData]
                        ],
                        'response_format' => ['type' => 'json_object'],
                        'temperature' => 0.1
                    ]);

                if ($response->successful()) {
                    break;
                }

                // Rate limited - wait and retry
                if ($response->status() === 429 && $attempt < $maxRetries) {
                    Log::warning("QualitativeAnalysisService: Rate limited, retry {$attempt}/{$maxRetries}");
                    sleep($attempt * 3); // 3s, 6s backoff
                    continue;
                }
            }

            if ($response->failed()) {
                Log::error('QualitativeAnalysisService Error: ' . $response->body());
                throw new \Exception('AI analysis service failed/rate limited.');
            }

            $result = $response->json();
            $content = $result['choices'][0]['message']['content'] ?? '{}';
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
    public function analyzeQuantitativeData(array $stats, ?string $questionText = null): string
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

        $targetLang = $this->getTargetLanguage();
        $systemPrompt = "You are a senior statistical analyst. 
STRICT DATA-GROUNDING RULE (CRITICAL):
- Base your analysis STRICTLY AND EXCLUSIVELY on the provided question and frequency data payload.
- You MUST NOT invent, hallucinate, or assume any external statistics, percentages, or non-existent industries (e.g. '72% adoption', 'finance', 'healthcare') not in the payload.
- Provide a concise (2-3 sentences) strategic interpretation of the majorities, distribution, or consensus.
- Avoid simply restating numbers verbatim; explain what the distribution suggests about respondent sentiment for this question.
You MUST write the entire response in the {$targetLang} language.";

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => "STATISTICAL DATA:\n" . $statsText]
                    ],
                    'temperature' => 0.1
                ]);

            if ($response->failed())
                return "Analysis temporarily unavailable.";

            $result = $response->json();
            return $result['choices'][0]['message']['content'] ?? "No insight generated.";

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
