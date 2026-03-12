<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Response;
use App\Models\Survey;

class AiService
{
    /**
     * Analyze sentiment of a specific response.
     */
    public function analyzeResponseSentiment(Response $response)
    {
        $answers = $response->answers()->with('question')->get();
        $textData = "";

        foreach ($answers as $answer) {
            if (in_array($answer->question->type, ['text', 'textarea'])) {
                $textData .= "Question: {$answer->question->text}\nAnswer: {$answer->answer_text}\n\n";
            }
        }

        if (empty($textData))
            return null;

        $prompt = "Analyze the sentiment of the following survey response. Return ONLY a JSON object with 'sentiment' (Positive, Negative, or Neutral) and 'confidence' (0-1). \n\n" . $textData;

        try {
            $result = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a professional survey data analyst.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => ['type' => 'json_object']
            ]);

            $metadata = json_decode($result->choices[0]->message->content, true);
            $response->update(['ai_metadata' => array_merge($response->ai_metadata ?? [], $metadata)]);

            return $metadata;
        } catch (\Exception $e) {
            \Log::error("AI Sentiment Analysis Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate an executive summary for a survey based on its responses.
     */
    public function generateSurveySummary(Survey $survey)
    {
        $responses = $survey->responses()->with('answers.question')->take(20)->get();
        if ($responses->isEmpty())
            return "No responses available for analysis.";

        $dataDump = "";
        foreach ($responses as $index => $response) {
            $dataDump .= "Response " . ($index + 1) . ":\n";
            foreach ($response->answers as $answer) {
                if (in_array($answer->question->type, ['text', 'textarea'])) {
                    $dataDump .= "Q: {$answer->question->text} | A: {$answer->answer_text}\n";
                }
            }
            $dataDump .= "---\n";
        }

        $prompt = "Summarize the key findings from these survey responses in exactly three bullet points. Focus on recurring themes and overall tone.\n\n" . $dataDump;

        try {
            $result = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an executive researcher summarizing raw data.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            $summary = $result->choices[0]->message->content;

            // Store summary in survey metadata or similar?
            // For now, let's just return it for the report view.
            return $summary;
        } catch (\Exception $e) {
            \Log::error("AI Summary Error: " . $e->getMessage());
            return "Unable to generate AI summary at this time.";
        }
    }

    /**
     * Generate a full survey schema (JSON) based on a text prompt.
     * Compatible with jQuery FormBuilder.
     */
    public function generateSurveySchema($prompt)
    {
        // Check if API key is configured
        if (!config('openai.api_key')) {
            \Log::warning("OpenAI API Key is missing. Using Mock AI for schema generation.");
            return $this->getMockSchema($prompt);
        }

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
            $result = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => ['type' => 'json_object']
            ]);

            $content = $result->choices[0]->message->content;
            $decoded = json_decode($content, true);

            if (!isset($decoded[0]) && is_array($decoded)) {
                $firstKey = array_key_first($decoded);
                if (is_array($decoded[$firstKey])) {
                    return $decoded[$firstKey];
                }
            }

            return $decoded;
        } catch (\Exception $e) {
            \Log::error("AI Schema Generation Error: " . $e->getMessage());
            return $this->getMockSchema($prompt);
        }
    }

    /**
     * Provide a fallback schema for testing when AI is unavailable.
     */
    private function getMockSchema($prompt)
    {
        $lowercase = strtolower($prompt);

        if (str_contains($lowercase, 'coffee') || str_contains($lowercase, 'shop')) {
            return [
                [
                    'type' => 'header',
                    'subtype' => 'h2',
                    'label' => 'Coffee Shop Experience'
                ],
                [
                    'type' => 'radio-group',
                    'label' => 'How would you rate our coffee?',
                    'name' => 'coffee_rating',
                    'values' => [
                        ['label' => '5 - Excellent', 'value' => '5', 'selected' => 'false'],
                        ['label' => '4 - Good', 'value' => '4', 'selected' => 'false'],
                        ['label' => '3 - Average', 'value' => '3', 'selected' => 'false'],
                        ['label' => '2 - Poor', 'value' => '2', 'selected' => 'false'],
                        ['label' => '1 - Awful', 'value' => '1', 'selected' => 'false']
                    ]
                ],
                [
                    'type' => 'select',
                    'label' => 'Which was your favorite brew?',
                    'name' => 'favorite_brew',
                    'values' => [
                        ['label' => 'Espresso', 'value' => 'espresso', 'selected' => 'true'],
                        ['label' => 'Cappuccino', 'value' => 'cappuccino', 'selected' => 'false'],
                        ['label' => 'Latte', 'value' => 'latte', 'selected' => 'false']
                    ]
                ],
                [
                    'type' => 'textarea',
                    'label' => 'Any suggestions for our staff?',
                    'name' => 'staff_feedback',
                    'className' => 'form-control'
                ]
            ];
        }

        return [
            [
                'type' => 'header',
                'subtype' => 'h2',
                'label' => 'AI Generated Survey (Simulated)'
            ],
            [
                'type' => 'text',
                'label' => 'What is your primary feedback?',
                'name' => 'feedback',
                'required' => 'true',
                'className' => 'form-control'
            ],
            [
                'type' => 'radio-group',
                'label' => 'Would you recommend us?',
                'name' => 'recommend',
                'values' => [
                    ['label' => 'Yes', 'value' => 'yes', 'selected' => 'false'],
                    ['label' => 'No', 'value' => 'no', 'selected' => 'false']
                ]
            ],
            [
                'type' => 'paragraph',
                'subtype' => 'p',
                'label' => 'Note: Add an OpenAI API key to .env to enable real AI generation.'
            ]
        ];
    }
}
