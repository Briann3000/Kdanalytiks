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
    public function analyzeResponses(array $responses): array
    {
        if (empty($responses)) {
            return [
                'sentiment' => ['positive' => 0, 'neutral' => 0, 'negative' => 0],
                'key_themes' => [],
                'top_quotes' => [],
                'error' => 'No responses provided for analysis.'
            ];
        }

        // Balance between AI context quality and Groq TPM limits
        $responses = array_map(function ($r) {
            $r = is_array($r) ? implode(', ', $r) : $r;
            return strlen($r) > 200 ? substr($r, 0, 197) . '...' : $r;
        }, $responses);

        $chunks = array_chunk($responses, 25);

        return $this->processChunk($chunks[0]);
    }

    /**
     * Send a specific chunk of responses to Groq.
     */
    protected function processChunk(array $batch): array
    {
        $textData = implode("\n---\n", $batch);

        $systemPrompt = "You are a professional Political Data Analyst. 
Analyze the provided voter responses and return a strict JSON object.

JSON STRUCTURE:
{
  \"sentiment\": {
    \"positive\": 0, 
    \"neutral\": 0, 
    \"negative\": 0
  },
  \"key_themes\": [
    { \"theme\": \"Theme Name\", \"explanation\": \"Brief detail of why this is a concern\" }
  ],
  \"top_quotes\": [
    \"Direct, impactful quote 1\",
    \"Direct, impactful quote 2\",
    \"Direct, impactful quote 3\"
  ]
}

RULES:
1. 'sentiment' values must be percentages summing to 100.
2. 'key_themes' should be the 3-5 most frequent issues.
3. 'top_quotes' should be the 3 most representative and emotionally resonant excerpts.
4. Respond ONLY with the JSON object.";

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
}
