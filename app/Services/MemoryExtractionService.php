<?php

namespace App\Services;

use App\Models\SurveyAiThread;
use App\Models\SurveyAiMemory;
use Illuminate\Support\Facades\Log;

class MemoryExtractionService
{
    public function __construct(
        private readonly GroqStreamingClient $groqStreamingClient
    ) {
    }

    /**
     * Analyze a thread and extract key facts to remember.
     */
    public function extractAndStore(SurveyAiThread $thread): void
    {
        $messages = $thread->messages()->latest('id')->limit(4)->get()->reverse();

        if ($messages->count() < 2)
            return;

        $conversationText = $messages->map(fn($m) => strtoupper($m->role) . ": " . $m->content)->implode("\n\n");

        $prompt = [
            [
                'role' => 'system',
                'content' => "You are a 'Memory Extractor'. Analyze the conversation and extract exactly 1-3 PERMANENT facts about the user's project, preferences, or organizational context. 
            
            RULES:
            - Only extract long-term facts (e.g., 'User is focusing on gender-based violence in Kenya').
            - Do not extract temporary data or specific numbers from one result.
            - Output ONLY a JSON array of strings. Example: [\"User prefers dark-themed charts\", \"Project is for the Ministry of Education\"]
            - If nothing important is found, return an empty array []."
            ],
            ['role' => 'user', 'content' => "Extract facts from this exchange:\n\n" . $conversationText]
        ];

        try {
            // Use a non-streaming call or just capture the full result
            $result = $this->groqStreamingClient->streamChatCompletion($prompt, function ($delta) {}, 'llama-3.1-8b-instant');
            $content = $result['content'] ?? '[]';

            $facts = json_decode($this->cleanJson($content), true);

            if (is_array($facts)) {
                foreach ($facts as $fact) {
                    $this->storeFact($thread->user_id, $thread->survey_id, $fact);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Memory extraction failed: " . $e->getMessage());
        }
    }

    private function storeFact(int $userId, int $surveyId, string $fact): void
    {
        // Simple deduplication: don't store if a very similar fact exists
        $exists = SurveyAiMemory::where('user_id', $userId)
            ->where('survey_id', $surveyId)
            ->where('fact', 'like', '%' . substr($fact, 0, 20) . '%')
            ->exists();

        if (!$exists) {
            SurveyAiMemory::create([
                'user_id' => $userId,
                'survey_id' => $surveyId,
                'fact' => trim($fact),
                'importance' => 1,
            ]);
        }
    }

    private function cleanJson(string $json): string
    {
        return preg_replace('/^.*?(\[.*\]).*?$/s', '$1', $json);
    }
}
