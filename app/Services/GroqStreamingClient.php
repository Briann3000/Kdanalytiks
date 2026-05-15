<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GroqStreamingClient
{
    public function __construct(
        private readonly ?Client $client = null
    ) {
    }

    public function streamChatCompletion(array $messages, callable $onDelta, ?string $model = null): array
    {
        $apiKey = config('services.groq.api_key');
        $model = $model ?? config('services.groq.model', 'llama-3.1-8b-instant');
        $endpoint = 'https://api.groq.com/openai/v1/chat/completions';

        if (!$apiKey) {
            throw new RuntimeException('Groq API key is missing.');
        }

        $client = $this->client ?? new Client([
            'timeout' => 300,
            'connect_timeout' => 10,
        ]);

        $response = $client->request('POST', $endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'text/event-stream',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.3,
                'max_tokens' => 4096,
                'stream' => true,
                'stream_options' => ['include_usage' => true],
            ],
            'stream' => true,
        ]);

        $body = $response->getBody();
        $buffer = '';
        $content = '';
        $finishReason = null;
        $usage = null;

        while (!$body->eof()) {
            $buffer .= $body->read(1024);

            while (($lineBreak = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $lineBreak));
                $buffer = substr($buffer, $lineBreak + 1);

                if ($line === '' || !str_starts_with($line, 'data:')) {
                    continue;
                }

                $payload = trim(substr($line, 5));
                if ($payload === '[DONE]') {
                    break 2;
                }

                $data = json_decode($payload, true);
                if (!is_array($data)) {
                    continue;
                }

                // Capture usage if present
                if (isset($data['usage'])) {
                    $usage = $data['usage'];
                }

                $choice = $data['choices'][0] ?? [];
                $delta = $choice['delta']['content'] ?? null;

                if (is_string($delta) && $delta !== '') {
                    $content .= $delta;
                    $onDelta($delta);
                }

                if (($choice['finish_reason'] ?? null) !== null) {
                    $finishReason = $choice['finish_reason'];
                }
            }
        }

        return [
            'content' => $content,
            'finish_reason' => $finishReason,
            'model' => $model,
            'usage' => $usage,
        ];
    }
}
