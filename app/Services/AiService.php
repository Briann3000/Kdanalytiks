<?php

namespace App\Services;

use App\Models\Response;
use App\Models\Survey;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    /**
     * Check if an organization or independent user has reached its AI limit.
     */
    public function checkUsageLimit($org): bool
    {
        $tier = $org->subscriptionTier ?? \App\Models\SubscriptionTier::where('slug', 'free')->first();
        $limit = $tier ? (int) $tier->ai_limit_per_month : 5;

        if ($limit === -1) {
            return true; // Unlimited
        }

        return (int) $org->ai_usage_monthly < $limit;
    }

    /**
     * Increment AI usage for an organization or independent user.
     */
    public function incrementUsage($org): void
    {
        $org->increment('ai_usage_monthly');
    }

    /**
     * Analyze sentiment of a specific response using Groq.
     */
    public function analyzeResponseSentiment(Response $response)
    {
        $survey = $response->survey;
        $org = $survey ? ($survey->organization ?? $survey->independent) : null;

        if ($org && !$this->checkUsageLimit($org)) {
            Log::warning("AI Sentiment Analysis Skipped: Limit reached for Entity " . get_class($org) . " ID {$org->id}");
            return null;
        }

        $answers = $response->answers()->with('question')->get();
        $textData = "";

        foreach ($answers as $answer) {
            if ($answer->question && in_array($answer->question->type, ['text', 'textarea'])) {
                $textData .= "Question: {$answer->question->text}\nAnswer: {$answer->value}\n\n";
            }
        }

        if (empty($textData))
            return null;

        $prompt = "Analyze the sentiment of the following survey response. Return ONLY a JSON object with 'sentiment' (Positive, Negative, or Neutral) and 'confidence' (0-1). \n\n" . $textData;
        $systemPrompt = "You are a sentiment analysis assistant.";

        try {
            $responseJson = $this->callAi($prompt, $systemPrompt, true);
            if ($responseJson) {
                if ($org)
                    $this->incrementUsage($org);

                $metadata = json_decode($responseJson, true);
                if ($metadata) {
                    $response->update(['ai_metadata' => array_merge($response->ai_metadata ?? [], $metadata)]);
                    return $metadata;
                }
            }
            return null;
        } catch (\Exception $e) {
            Log::error("AI Sentiment Analysis Error (Groq): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate an executive summary for a survey using Groq.
     */
    public function generateSurveySummary(Survey $survey)
    {
        $org = $survey->organization ?? $survey->independent;
        if ($org && !$this->checkUsageLimit($org)) {
            return "AI Summary is currently unavailable: Your monthly subscription limit has been reached.";
        }

        // Reduced to 8 to stay well within Groq TPM limits (Free Tier)
        $responses = $survey->responses()->with('answers.question')->latest()->take(8)->get();
        if ($responses->isEmpty())
            return "No responses available for analysis.";

        $dataDump = "";
        foreach ($responses as $index => $response) {
            $dataDump .= "R" . ($index + 1) . ": ";
            foreach ($response->answers as $answer) {
                $val = $answer->value;
                if (is_null($answer->question_id) && !empty($val)) {
                    $parsed = json_decode($val, true) ?? [];
                    if (!is_array($parsed))
                        continue;

                    foreach ($parsed as $entry) {
                        if (isset($entry['userData']) && !empty($entry['userData'])) {
                            if (is_array($entry['userData']))
                                continue;

                            // Truncate long responses more aggressively (100 chars)
                            $cleanVal = strlen($entry['userData']) > 100 ? substr($entry['userData'], 0, 97) . '...' : $entry['userData'];
                            $dataDump .= "A: {$cleanVal} | ";
                        }
                    }
                } elseif ($answer->question && in_array($answer->question->type, ['text', 'textarea'])) {
                    $cleanVal = strlen($val) > 100 ? substr($val, 0, 97) . '...' : $val;
                    $dataDump .= "A: {$cleanVal} | ";
                } elseif (!is_null($val) && strlen($val) > 4) {
                    $cleanVal = strlen($val) > 100 ? substr($val, 0, 97) . '...' : $val;
                    $dataDump .= "A: {$cleanVal} | ";
                }
            }
            $dataDump .= "\n";
        }

        if (empty(trim($dataDump))) {
            return "No meaningful text responses were found in the recent submissions to summarize.";
        }

        $prompt = "Summarize these voter responses in 3 concise bullet points. Focus on key themes and tone.\n\nDATA:\n" . $dataDump;

        try {
            $result = $this->callAi($prompt);
            if (!$result) {
                return "AI Summary is currently unavailable (Engine failed to respond).";
            }

            if ($org)
                $this->incrementUsage($org);

            return $result;
        } catch (\Exception $e) {
            Log::error("Groq Summary Error: " . $e->getMessage());
            return "AI Analysis temporarily unavailable (Connection error).";
        }
    }

    /**
     * Unified AI caller that routes to the active provider.
     */
    public function callAi($prompt, $systemPrompt = null, $isJson = false)
    {
        $provider = env('AI_PROVIDER', 'groq');
        if ($provider === 'gemini') {
            return $this->callGemini($prompt, $systemPrompt);
        }
        return $this->callGroq($prompt, $systemPrompt, $isJson);
    }

    /**
     * Unified Parallel AI caller.
     */
    public function callAiParallel(array $prompts, $systemPrompt = null)
    {
        $provider = env('AI_PROVIDER', 'groq');
        if ($provider === 'gemini') {
            return $this->callGeminiParallel($prompts, $systemPrompt);
        }
        return $this->callGroqParallel($prompts, $systemPrompt);
    }

    /**
     * Call Google Gemini API.
     */
    public function callGemini($prompt, $systemPrompt = null)
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-2.5-flash');

        if (empty($apiKey)) {
            Log::error("Gemini API Key is missing.");
            return null;
        }

        $url = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}";

        $fullPrompt = $systemPrompt ? "SYSTEM INSTRUCTIONS: {$systemPrompt}\n\nUSER PROMPT: {$prompt}" : $prompt;

        $payload = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $fullPrompt]]]
            ]
        ];

        $maxRetries = 3;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::timeout(60)->post($url, $payload);
                if ($response->successful()) {
                    $data = $response->json();
                    return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                }

                $status = $response->status();
                if ($status === 429 || $status === 503) {
                    $waitSeconds = ($status === 429) ? 10 : 5;
                    Log::warning("Gemini API overloaded (HTTP {$status}, attempt {$attempt}/{$maxRetries}). Waiting {$waitSeconds}s...");
                    sleep($waitSeconds);
                    continue;
                }

                Log::error("Gemini API Error (HTTP {$status}): " . $response->body());
                return null;
            } catch (\Exception $e) {
                Log::error("Gemini Call Exception (attempt {$attempt}): " . $e->getMessage());
                if ($attempt < $maxRetries) {
                    sleep(3);
                    continue;
                }
                return null;
            }
        }

        Log::error("Gemini API: All {$maxRetries} attempts exhausted.");
        return null;
    }

    /**
     * Call Gemini in parallel.
     */
    public function callGeminiParallel(array $prompts, $systemPrompt = null)
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-1.5-flash');

        if (empty($apiKey) || empty($prompts))
            return [];

        $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($prompts, $apiKey, $model, $systemPrompt) {
            foreach ($prompts as $key => $prompt) {
                $url = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}";
                $fullPrompt = $systemPrompt ? "SYSTEM INSTRUCTIONS: {$systemPrompt}\n\nUSER PROMPT: {$prompt}" : $prompt;
                $payload = [
                    'contents' => [['role' => 'user', 'parts' => [['text' => $fullPrompt]]]]
                ];
                $pool->as($key)->timeout(90)->post($url, $payload);
            }
        });

        $results = [];
        foreach ($prompts as $key => $prompt) {
            $response = $responses[$key];
            if ($response instanceof \Illuminate\Http\Client\Response && $response->successful()) {
                $data = $response->json();
                $results[$key] = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            } else {
                $results[$key] = null;
            }
        }
        return $results;
    }

    /**
     * Call Groq in parallel for multiple prompts.
     * Returns an associative array of [key => content]
     */
    public function callGroqParallel(array $prompts, $systemPrompt = null)
    {
        $apiKey = config('services.groq.api_key');
        $model = config('services.groq.model', 'llama-3.1-8b-instant');

        if (empty($apiKey) || empty($prompts)) {
            return [];
        }

        $finalSystemPrompt = $systemPrompt ?? 'You are an expert researcher.';

        $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($prompts, $apiKey, $model, $finalSystemPrompt) {
            foreach ($prompts as $key => $prompt) {
                $pool->as($key)->withToken($apiKey)->timeout(60)->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $finalSystemPrompt],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.4,
                    'max_tokens' => 4096,
                ]);
            }
        });

        $results = [];
        foreach ($prompts as $key => $prompt) {
            $response = $responses[$key];
            if ($response instanceof \Illuminate\Http\Client\Response && $response->successful()) {
                $data = $response->json();
                $results[$key] = $data['choices'][0]['message']['content'] ?? null;
            } else {
                $errorMsg = ($response instanceof \Exception) ? $response->getMessage() : ($response ? $response->body() : 'No response');
                Log::error("Groq Parallel Error for key {$key}: " . $errorMsg);
                $results[$key] = null;
            }
        }

        return $results;
    }

    public function callGroq($prompt, $systemPrompt = null, $isJson = false)
    {
        $apiKey = config('services.groq.api_key');
        $model = config('services.groq.model', 'llama-3.1-8b-instant');

        if (empty($apiKey)) {
            Log::error("Groq API Key is missing in configuration.");
            return null;
        }

        $finalSystemPrompt = $systemPrompt;
        if (!$finalSystemPrompt) {
            $finalSystemPrompt = $isJson ? 'You are a helpful assistant that only outputs JSON.' : 'You are an expert researcher.';
        }

        $options = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $finalSystemPrompt],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $isJson ? 0.1 : 0.4,
            'max_tokens' => 4096,
        ];

        if ($isJson) {
            $options['response_format'] = ['type' => 'json_object'];
        }

        // Retry loop with rate limit handling
        $maxRetries = 3;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                Log::info("Groq API call (attempt {$attempt}): model={$model}, prompt_length=" . strlen($prompt));

                $response = Http::withToken($apiKey)
                    ->timeout(60)
                    ->post('https://api.groq.com/openai/v1/chat/completions', $options);

                if ($response->successful()) {
                    $data = $response->json();
                    $content = $data['choices'][0]['message']['content'] ?? null;
                    $finishReason = $data['choices'][0]['finish_reason'] ?? 'unknown';
                    Log::info("Groq API success: finish_reason={$finishReason}, response_length=" . strlen($content ?? ''));
                    return $content;
                }

                // Check if rate limited
                $body = $response->body();
                $status = $response->status();

                if ($status === 429 || str_contains($body, 'rate_limit_exceeded')) {
                    // Extract wait time from error message: "Please try again in X.XXs"
                    $waitSeconds = 10; // default fallback
                    if (preg_match('/try again in (\d+\.?\d*)s/i', $body, $matches)) {
                        $waitSeconds = ceil((float) $matches[1]) + 2; // Add 2s buffer
                    }
                    Log::warning("Groq rate limited (attempt {$attempt}/{$maxRetries}). Waiting {$waitSeconds}s before retry...");
                    sleep($waitSeconds);
                    continue; // Retry
                }

                // Non-rate-limit error
                Log::error("Groq API Error (HTTP {$status}): " . $body);
                return null;

            } catch (\Exception $e) {
                Log::error("Groq Call Exception (attempt {$attempt}): " . $e->getMessage());
                if ($attempt < $maxRetries) {
                    sleep(2);
                    continue;
                }
                return null;
            }
        }

        Log::error("Groq API: All {$maxRetries} attempts exhausted.");
        return null;
    }

    /**
     * Transcribe an audio or video file using Groq's Whisper model.
     */
    public function transcribeMedia(string $absoluteFilePath)
    {
        $apiKey = config('services.groq.api_key');
        if (empty($apiKey)) {
            Log::error("Groq API Key missing for transcription.");
            return null;
        }

        if (!file_exists($absoluteFilePath)) {
            Log::error("Media file not found for transcription: " . $absoluteFilePath);
            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(120) // Transcriptions can take longer for large files
                ->attach('file', file_get_contents($absoluteFilePath), basename($absoluteFilePath))
                ->post('https://api.groq.com/openai/v1/audio/transcriptions', [
                    'model' => 'whisper-large-v3',
                    'response_format' => 'json',
                    'language' => 'en', // Optional: Can be auto-detected
                    'temperature' => 0.0,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['text'] ?? null;
            }

            Log::error("Groq Transcription Error (HTTP " . $response->status() . "): " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("Groq Transcription Exception: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Generate a full survey schema (JSON) based on a text prompt using Groq.
     */
    public function generateSurveySchema($prompt)
    {
        $user = auth()->user();
        $org = null;
        if ($user) {
            $role = $user->role instanceof \App\Enums\UserRole ? $user->role->value : $user->role;
            if ($role === 'organization') {
                $org = $user->organization;
            } elseif ($role === 'independent') {
                $org = $user->independent;
            }
        }

        if ($org && !$this->checkUsageLimit($org)) {
            return $this->getMockSchema($prompt); // Fallback to mock if limit reached
        }

        $systemPrompt = "You are an expert survey designer and researcher. Your goal is to generate high-quality survey schemas based on user descriptions. 
        You MUST return ONLY a JSON array of objects. Each object represents a question field compatible with 'jQuery FormBuilder'.
        
        Supported field types and properties:
        - type: 'header', 'paragraph', 'text', 'select', 'select_one', 'select_many', 'number', 'date', 'rating', 'range', 'photo', 'note', 'time', 'audio', 'video'
        - label: The question text
        - name: Unique slug (e.g., 'customer_name')
        - required: true/false
        - values (for groups/selects): Array of {label: '...', value: '...', selected: false}
        - className: UI classes (e.g., 'form-control')
        
        Example JSON structure:
        [
          { \"type\": \"header\", \"label\": \"Customer Feedback\" },
          { \"type\": \"text\", \"label\": \"What is your name?\", \"name\": \"name\", \"required\": true },
          { \"type\": \"select_one\", \"label\": \"How satisfied are you?\", \"name\": \"satisfaction\", \"values\": [{\"label\": \"Very Happy\", \"value\": \"5\"}, {\"label\": \"Unhappy\", \"value\": \"1\"}] }
        ]
        
        Generate exactly what the user asks for, optimized for high completion rates.";

        try {
            $aiData = $this->callAi($prompt, $systemPrompt, true);
            if ($aiData) {
                if ($org)
                    $this->incrementUsage($org);

                $decoded = json_decode($aiData, true);
                return $this->formatSchemaResponse($decoded);
            }
            return $this->getMockSchema($prompt);
        } catch (\Exception $e) {
            Log::error("AI Schema Generation Error: " . $e->getMessage());
            return $this->getMockSchema($prompt);
        }
    }

    private function formatSchemaResponse($decoded)
    {
        if (!isset($decoded[0]) && is_array($decoded)) {
            $firstKey = array_key_first($decoded);
            if (is_array($decoded[$firstKey])) {
                return $decoded[$firstKey];
            }
        }
        return $decoded;
    }

    /**
     * Provide a fallback schema for testing when AI is unavailable.
     */
    private function getMockSchema($prompt)
    {
        $lowercase = strtolower($prompt);

        if (str_contains($lowercase, 'coffee') || str_contains($lowercase, 'shop')) {
            return [
                ['type' => 'header', 'substrate' => 'h2', 'label' => 'Coffee Shop Experience'],
                ['type' => 'radio-group', 'label' => 'How would you rate our coffee?', 'name' => 'coffee_rating', 'values' => [['label' => '5 - Excellent', 'value' => '5'], ['label' => '1 - Awful', 'value' => '1']]],
                ['type' => 'textarea', 'label' => 'Any suggestions?', 'name' => 'suggestions', 'className' => 'form-control']
            ];
        }

        return [
            ['type' => 'header', 'label' => 'AI Generated Survey (Simulated)'],
            ['type' => 'text', 'label' => 'General Feedback', 'name' => 'feedback', 'required' => true, 'className' => 'form-control'],
            ['type' => 'paragraph', 'label' => 'Note: The AI engine encountered an error, this is a simulated response.']
        ];
    }
}
