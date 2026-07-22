<?php

namespace App\Services;

use App\Models\Survey;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndexNowService
{
    protected string $key = 'c7b9e6a20d4f4e8b91a23456789abcdef';

    /**
     * Submit an array of URLs to the IndexNow API (Bing, DuckDuckGo, Yandex, Naver).
     */
    public function submitUrls(array $urls): bool
    {
        if (empty($urls)) {
            return false;
        }

        $appUrl = config('app.url', 'https://kdanalytiks.com');
        $host = parse_url($appUrl, PHP_URL_HOST) ?? 'kdanalytiks.com';
        $keyLocation = rtrim($appUrl, '/') . '/' . $this->key . '.txt';

        $payload = [
            'host' => $host,
            'key' => $this->key,
            'keyLocation' => $keyLocation,
            'urlList' => array_values(array_unique($urls)),
        ];

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Content-Type' => 'application/json; charset=utf-8'])
                ->post('https://api.indexnow.org/indexnow', $payload);

            if ($response->successful() || $response->status() === 202) {
                Log::info('IndexNow submission successful', ['count' => count($urls), 'status' => $response->status()]);
                return true;
            }

            Log::warning('IndexNow submission returned non-200 status', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('IndexNow submission failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Gather all public pages and active public surveys and submit them to IndexNow.
     */
    public function submitAllPublicPages(): bool
    {
        $appUrl = rtrim(config('app.url', 'https://kdanalytiks.com'), '/');

        $staticUrls = [
            $appUrl . '/',
            $appUrl . '/humanizer',
            $appUrl . '/surveys/public',
            $appUrl . '/docs',
            $appUrl . '/privacy-policy',
            $appUrl . '/terms-and-conditions',
        ];

        $publicSurveys = Survey::where('is_template', false)
            ->where('status', \App\Enums\SurveyStatus::Active)
            ->where('type', \App\Enums\SurveyType::Public)
            ->get();

        $surveyUrls = [];
        foreach ($publicSurveys as $survey) {
            $surveyUrls[] = route('surveys.show', $survey->id);
        }

        $allUrls = array_merge($staticUrls, $surveyUrls);

        return $this->submitUrls($allUrls);
    }
}
