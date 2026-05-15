<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class WebSearchService
{
    /**
     * Search the web for grounding information.
     * Currently implements a placeholder/duckduckgo-lite logic.
     * Can be extended to use Tavily or Serper.dev APIs.
     */
    public function search(string $query): string
    {
        $apiKey = config('services.tavily.api_key');

        if (!$apiKey) {
            return "Web Search Grounding: The user enabled web search, but the API key is missing. (Developer: Add TAVILY_API_KEY to your .env)";
        }

        try {
            $client = new Client(['timeout' => 10]);
            $response = $client->post('https://api.tavily.com/search', [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'api_key' => $apiKey,
                    'query' => $query,
                    'search_depth' => 'basic',
                    'max_results' => 5,
                    'include_answer' => true,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data['results'])) {
                return "No search results found for this query.";
            }

            $context = "Real-time Search Results (Context):\n";
            foreach ($data['results'] as $index => $result) {
                $idx = $index + 1;
                $context .= "[{$idx}] Source: {$result['url']}\nSnippet: {$result['content']}\n\n";
            }

            if (!empty($data['answer'])) {
                $context = "Direct AI Answer from Search: " . $data['answer'] . "\n\n" . $context;
            }

            return $context;
        } catch (\Throwable $e) {
            Log::error("Tavily search failed: " . $e->getMessage());
            return "Web Search Error: Could not fetch real-time data at this moment.";
        }
    }
}
