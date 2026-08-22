<?php

namespace App\Services\Plagiarism;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebDocumentScraperService
{
    /**
     * In-memory cache of scraped documents for the current execution cycle
     */
    private array $pageCache = [];

    /**
     * Fetch clean body text from a candidate web URL
     */
    public function fetchCleanText(string $url): ?string
    {
        $normalizedUrl = trim($url);
        if (empty($normalizedUrl) || !filter_var($normalizedUrl, FILTER_VALIDATE_URL)) {
            return null;
        }

        if (isset($this->pageCache[$normalizedUrl])) {
            return $this->pageCache[$normalizedUrl];
        }

        try {
            $response = Http::timeout(7)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                ])
                ->get($normalizedUrl);

            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();
            $cleanText = $this->extractArticleText($html);

            if (!empty(trim($cleanText))) {
                $this->pageCache[$normalizedUrl] = $cleanText;
                return $cleanText;
            }
        } catch (Throwable $e) {
            Log::debug("Web document scrape failed for [{$normalizedUrl}]: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Extract main readable article text from raw HTML
     */
    public function extractArticleText(string $html): string
    {
        if (empty(trim($html))) {
            return '';
        }

        // 1. Remove non-content tags
        $clean = preg_replace('/<(script|style|nav|header|footer|aside|noscript|svg|iframe)\b[^>]*>[\s\S]*?<\/\1>/i', ' ', $html);

        // 2. Convert line breaks and paragraph closings to newlines
        $clean = preg_replace('/<\/(p|div|h[1-6]|li|tr|blockquote|section|article)>/i', "\n", $clean);
        $clean = preg_replace('/<br\s*\/?>/i', "\n", $clean);

        // 3. Strip remaining tags and decode entities
        $text = strip_tags($clean);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 4. Normalize spaces and multiple newlines
        $lines = explode("\n", $text);
        $validLines = [];

        foreach ($lines as $line) {
            $trimmed = trim(preg_replace('/[ \t\x{00a0}]+/u', ' ', $line));
            // Keep meaningful lines with at least 3 words
            if (mb_strlen($trimmed) > 15 && count(explode(' ', $trimmed)) >= 3) {
                $validLines[] = $trimmed;
            }
        }

        return implode("\n", $validLines);
    }
}
