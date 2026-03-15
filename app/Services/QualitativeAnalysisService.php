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

        // Chunk responses to stay within token limits (e.g., 80 per batch for Llama 3.1 8b)
        $chunks = array_chunk($responses, 80);
        
        // If multiple chunks, we analyze the first one for now but ensure the prompt is robust.
        // In a more advanced version, we would map-reduce these.
        return $this->processChunk(count($chunks) > 1 ? array_merge(...array_slice($chunks, 0, 2)) : $chunks[0]);
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

            if ($response->failed()) {
                Log::error('QualitativeAnalysisService Error: ' . $response->body());
                throw new \Exception('AI analysis service failed to respond.');
            }

            $result = $response->json();
            $content = $result['choices'][0]['message']['content'] ?? '{}';
            
            $data = json_decode($content, true);
            if (!$data) throw new \Exception('Malformed AI JSON response.');

            // Ensure structure consistency
            return [
                'sentiment' => $data['sentiment'] ?? ['positive' => 0, 'neutral' => 0, 'negative' => 0],
                'key_themes' => $data['key_themes'] ?? [],
                'top_quotes' => $data['top_quotes'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error('QualitativeAnalysisService Exception: ' . $e->getMessage());
            return [
                'sentiment' => ['positive' => 0, 'neutral' => 0, 'negative' => 0],
                'key_themes' => [],
                'top_quotes' => [],
                'error' => $e->getMessage()
            ];
        }
    }
}
