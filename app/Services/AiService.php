<?php

namespace App\Services;

use App\Models\Response;
use App\Models\Survey;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    /**
     * Analyze sentiment of a specific response using Groq.
     */
    public function analyzeResponseSentiment(Response $response)
    {
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

        try {
            $responseJson = $this->callGroq($prompt, null, true);
            if ($responseJson) {
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
                    if (!is_array($parsed)) continue;
                    
                    foreach ($parsed as $entry) {
                        if (isset($entry['userData']) && !empty($entry['userData'])) {
                            if (is_array($entry['userData'])) continue;
                            
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
            $result = $this->callGroq($prompt);
            if (!$result) {
                 return "AI Summary is currently unavailable (Engine failed to respond).";
            }
            return $result;
        } catch (\Exception $e) {
            Log::error("Groq Summary Error: " . $e->getMessage());
            return "AI Analysis temporarily unavailable (Connection error).";
        }
    }

    /**
     * Primary Groq API caller.
     */
    public function callGroq($prompt, $systemPrompt = null, $isJson = false)
    {
        try {
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
                'temperature' => $isJson ? 0.1 : 0.4
            ];

            if ($isJson) {
                $options['response_format'] = ['type' => 'json_object'];
            }

            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->post('https://api.groq.com/openai/v1/chat/completions', $options);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? null;
            }
            
            Log::error("Groq API Error: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("Groq Call Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate a full survey schema (JSON) based on a text prompt using Groq.
     */
    public function generateSurveySchema($prompt)
    {
        $systemPrompt = "You are an expert survey designer and researcher. Your goal is to generate high-quality survey schemas based on user descriptions. 
        You MUST return ONLY a JSON array of objects. Each object represents a question field compatible with 'jQuery FormBuilder'.
        
        Supported field types and properties:
        - type: 'header', 'paragraph', 'text', 'textarea', 'select', 'checkbox-group', 'radio-group', 'number', 'date', 'starRating'
        - label: The question text
        - name: Unique slug (e.g., 'customer_name')
        - required: true/false
        - values (for groups/select): Arrary of {label: '...', value: '...', selected: false}
        - className: UI classes (e.g., 'form-control')
        
        Example JSON structure:
        [
          { \"type\": \"header\", \"label\": \"Customer Feedback\" },
          { \"type\": \"text\", \"label\": \"What is your name?\", \"name\": \"name\", \"required\": true },
          { \"type\": \"radio-group\", \"label\": \"How satisfied are you?\", \"name\": \"satisfaction\", \"values\": [{\"label\": \"Very Happy\", \"value\": \"5\"}, {\"label\": \"Unhappy\", \"value\": \"1\"}] }
        ]
        
        Generate exactly what the user asks for, optimized for high completion rates.";

        try {
            $groqData = $this->callGroq($prompt, $systemPrompt, true);
            if ($groqData) {
                $decoded = json_decode($groqData, true);
                return $this->formatSchemaResponse($decoded);
            }
            return $this->getMockSchema($prompt);
        } catch (\Exception $e) {
            Log::error("AI Schema Generation Error (Groq): " . $e->getMessage());
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
