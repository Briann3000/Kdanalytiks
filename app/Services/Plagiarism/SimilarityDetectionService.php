<?php

namespace App\Services\Plagiarism;

use App\Models\PlagiarismScan;
use App\Models\PlagiarismMatch;
use App\Services\Ai\UnifiedAiDetectorService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SimilarityDetectionService
{
    private readonly WebDocumentScraperService $webScraper;
    private readonly DeepTextAlignmentService $textAlignment;

    public function __construct(
        private readonly AcademicTextPreprocessor $preprocessor,
        ?WebDocumentScraperService $webScraper = null,
        ?DeepTextAlignmentService $textAlignment = null,
        private readonly ?UnifiedAiDetectorService $aiDetector = null
    ) {
        $this->webScraper = $webScraper ?? new WebDocumentScraperService();
        $this->textAlignment = $textAlignment ?? new DeepTextAlignmentService();
    }

    /**
     * Run full dual detection scan (Deep Multi-Engine Plagiarism Search + Synchronized AI Probability)
     */
    public function executeScan(PlagiarismScan $scan): PlagiarismScan
    {
        try {
            $scan->update(['status' => 'processing']);

            // 1. Academic Preprocessing & Segmentation
            $preprocessed = $this->preprocessor->preprocess(
                $scan->content,
                $scan->exclude_quotes,
                $scan->exclude_references,
                $scan->min_words_threshold ?? 8
            );

            $segments = $preprocessed['segments'];
            $totalWords = $preprocessed['total_words'];
            $scan->update([
                'word_count' => $totalWords,
                'character_count' => $preprocessed['total_characters'],
            ]);

            // 2. Multi-Source Search & Deep Document Alignment
            $matches = $this->detectMatches($scan, $segments);

            // 3. Dual Detection: Calculate Synchronized AI Probability Score
            $aiDetector = $this->aiDetector ?? new UnifiedAiDetectorService();
            $aiAnalysis = $aiDetector->analyze($scan->content);
            $aiScore = $aiAnalysis['aiProbability'] ?? 0.0;

            // 4. Calculate Net Similarity Percentage (non-overlapping)
            $similarityPercentage = $this->calculateNetSimilarity($scan->content, $matches, $scan);

            // 5. Build Top Sources & Summary Metadata
            $summaryMetadata = $this->compileSummaryMetadata($matches, $aiAnalysis, $totalWords);

            $scan->update([
                'similarity_percentage' => $similarityPercentage,
                'ai_percentage' => $aiScore,
                'status' => 'completed',
                'summary_metadata' => $summaryMetadata,
            ]);

            return $scan->fresh(['matches']);
        } catch (Throwable $e) {
            Log::error('Plagiarism scan failed: ' . $e->getMessage(), [
                'scan_id' => $scan->id,
                'trace' => $e->getTraceAsString(),
            ]);

            $scan->update([
                'status' => 'failed',
                'error_message' => 'Scan error: ' . $e->getMessage(),
            ]);

            return $scan;
        }
    }

    /**
     * Detect candidate matches across Web, Academic, and Workspace sources using deep document alignment
     */
    private function detectMatches(PlagiarismScan $scan, array $segments): array
    {
        $createdMatches = [];
        $searchableSegments = array_values(array_filter($segments, fn($s) => !$s['is_excluded'] && $s['word_count'] >= 5));
        $totalSearchable = count($searchableSegments);

        if ($totalSearchable === 0) {
            return [];
        }

        // Step 1: Discover candidate URLs using multi-anchor search queries
        // Select up to 15 strategic anchors spread evenly across the document
        $candidateUrls = $this->discoverCandidateUrls($searchableSegments);

        // Step 2: Deep scrape candidate web pages
        $scrapedPages = [];
        foreach (array_slice($candidateUrls, 0, 8) as $urlInfo) {
            $url = $urlInfo['url'];
            $fullText = $this->webScraper->fetchCleanText($url);
            if (!empty($fullText)) {
                $scrapedPages[] = [
                    'url' => $url,
                    'title' => $urlInfo['title'] ?? parse_url($url, PHP_URL_HOST),
                    'domain' => parse_url($url, PHP_URL_HOST),
                    'full_text' => $fullText,
                    'type' => $urlInfo['type'] ?? 'web',
                ];
            }
        }

        // Step 3: Deep Document-to-Document Alignment for EVERY segment
        $minWordsThreshold = $scan->min_words_threshold ?? 6;
        $excludedDomains = is_array($scan->excluded_domains) ? $scan->excluded_domains : [];

        foreach ($searchableSegments as $seg) {
            $matched = false;

            // Check against scraped candidate pages first
            foreach ($scrapedPages as $page) {
                if (in_array(strtolower($page['domain']), array_map('strtolower', $excludedDomains))) {
                    continue;
                }

                $alignment = $this->textAlignment->alignSegmentAgainstDocument($seg, $page['full_text'], $minWordsThreshold);
                if ($alignment && $alignment['similarity_score'] >= 45.0) {
                    $match = PlagiarismMatch::create([
                        'scan_id' => $scan->id,
                        'source_url' => $page['url'],
                        'source_title' => $page['title'],
                        'source_domain' => $page['domain'],
                        'matched_text' => $alignment['matched_snippet'],
                        'original_snippet' => $seg['text'],
                        'similarity_score' => $alignment['similarity_score'],
                        'start_offset' => $seg['start_offset'],
                        'end_offset' => $seg['end_offset'],
                        'match_type' => $page['type'],
                        'is_excluded' => false,
                    ]);

                    $createdMatches[] = $match;
                    $matched = true;
                    break;
                }
            }

            // Fallback: If not matched in top scraped pages, perform targeted direct search query
            if (!$matched && count($createdMatches) < 40) {
                $directResults = $this->searchWebMultiProvider($seg['text']);
                foreach ($directResults as $res) {
                    $domain = parse_url($res['url'], PHP_URL_HOST) ?? 'web-source.org';
                    if (in_array(strtolower($domain), array_map('strtolower', $excludedDomains))) {
                        continue;
                    }

                    $simScore = $this->computeStringSimilarity($seg['text'], $res['content']);
                    if ($simScore >= 45.0) {
                        $match = PlagiarismMatch::create([
                            'scan_id' => $scan->id,
                            'source_url' => $res['url'],
                            'source_title' => $res['title'],
                            'source_domain' => $domain,
                            'matched_text' => mb_substr($res['content'], 0, 500),
                            'original_snippet' => $seg['text'],
                            'similarity_score' => round($simScore, 1),
                            'start_offset' => $seg['start_offset'],
                            'end_offset' => $seg['end_offset'],
                            'match_type' => $res['type'] ?? 'web',
                            'is_excluded' => false,
                        ]);

                        $createdMatches[] = $match;
                        break;
                    }
                }
            }
        }

        return $createdMatches;
    }

    /**
     * Discover candidate matching URLs across the web for the manuscript
     */
    private function discoverCandidateUrls(array $segments): array
    {
        $total = count($segments);
        $sampleCount = min(12, $total);
        $step = max(1, (int) floor($total / $sampleCount));

        $urlsDiscovered = [];
        $seenUrls = [];

        for ($i = 0; $i < $total; $i += $step) {
            $seg = $segments[$i];
            $results = $this->searchWebMultiProvider($seg['text']);

            foreach ($results as $res) {
                $url = $res['url'];
                if (!empty($url) && !isset($seenUrls[$url])) {
                    $seenUrls[$url] = true;
                    $urlsDiscovered[] = $res;
                }
            }

            if (count($urlsDiscovered) >= 15) {
                break;
            }
        }

        return $urlsDiscovered;
    }

    /**
     * Multi-Provider Web & Scholarly Search Adapter (Serper Google API, Tavily, OpenAlex, Zero-Config Fallback)
     */
    private function searchWebMultiProvider(string $query): array
    {
        $cleanQuery = trim(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $query));
        $words = preg_split('/\s+/u', $cleanQuery, -1, PREG_SPLIT_NO_EMPTY);
        $shortQuery = implode(' ', array_slice($words, 0, 10));

        if (empty($shortQuery) || count($words) < 3) {
            return [];
        }

        // 1. Serper Google Search API (Priority 1 for universal internet index)
        $serperKey = config('services.serper.api_key') ?? env('SERPER_API_KEY');
        if (!empty($serperKey)) {
            try {
                $res = Http::withHeaders([
                    'X-API-KEY' => $serperKey,
                    'Content-Type' => 'application/json',
                ])->timeout(6)->post('https://google.serper.dev/search', [
                            'q' => '"' . $shortQuery . '"',
                            'num' => 3,
                        ]);

                if ($res->successful()) {
                    $organic = $res->json('organic') ?? [];
                    if (!empty($organic)) {
                        return array_map(fn($o) => [
                            'title' => $o['title'] ?? 'Web Source',
                            'url' => $o['link'] ?? '',
                            'content' => $o['snippet'] ?? '',
                            'type' => 'google_web',
                        ], $organic);
                    }
                }
            } catch (Throwable $e) {
                Log::debug('Serper Search error: ' . $e->getMessage());
            }
        }

        // 2. Tavily Search API (Priority 2)
        $tavilyKey = config('services.tavily.api_key') ?? env('TAVILY_API_KEY');
        if (!empty($tavilyKey)) {
            try {
                $res = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->timeout(6)->post('https://api.tavily.com/search', [
                            'api_key' => $tavilyKey,
                            'query' => $shortQuery,
                            'search_depth' => 'basic',
                            'max_results' => 3,
                        ]);

                if ($res->successful()) {
                    $results = $res->json('results') ?? [];
                    if (!empty($results)) {
                        return array_map(fn($r) => [
                            'title' => $r['title'] ?? 'Web Source',
                            'url' => $r['url'] ?? '',
                            'content' => $r['content'] ?? '',
                            'type' => 'tavily_web',
                        ], $results);
                    }
                }
            } catch (Throwable $e) {
                Log::debug('Tavily Search error: ' . $e->getMessage());
            }
        }

        // 3. OpenAlex Scholarly Fallback
        try {
            $academicRes = Http::timeout(5)->get('https://api.openalex.org/works', [
                'search' => $shortQuery,
                'per_page' => 2,
            ]);

            if ($academicRes->successful()) {
                $items = $academicRes->json('results') ?? [];
                if (!empty($items)) {
                    return array_map(fn($w) => [
                        'title' => $w['display_name'] ?? 'Scholarly Publication',
                        'url' => $w['doi'] ?? ($w['primary_location']['landing_page_url'] ?? ''),
                        'content' => $w['display_name'],
                        'type' => 'academic',
                    ], $items);
                }
            }
        } catch (Throwable $e) {
            // Ignore OpenAlex fallback timeout
        }

        // 4. Zero-Config DuckDuckGo HTML Search Fallback
        try {
            $ddgRes = Http::timeout(5)
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
                ->asForm()
                ->post('https://html.duckduckgo.com/html/', [
                    'q' => $shortQuery,
                ]);

            if ($ddgRes->successful()) {
                $html = $ddgRes->body();
                $results = [];
                if (preg_match_all('/<a\s+class="result__url"\s+href="([^"]+)"[^>]*>([^<]+)<\/a>/i', $html, $m)) {
                    for ($k = 0; $k < min(3, count($m[1])); $k++) {
                        $rawUrl = urldecode(trim($m[1][$k]));
                        if (str_contains($rawUrl, 'uddg=')) {
                            parse_str(parse_url($rawUrl, PHP_URL_QUERY), $qp);
                            $rawUrl = $qp['uddg'] ?? $rawUrl;
                        }
                        if (filter_var($rawUrl, FILTER_VALIDATE_URL)) {
                            $results[] = [
                                'title' => trim($m[2][$k]),
                                'url' => $rawUrl,
                                'content' => $shortQuery,
                                'type' => 'web_fallback',
                            ];
                        }
                    }
                }
                if (!empty($results)) {
                    return $results;
                }
            }
        } catch (Throwable $e) {
            // Ignore fallback error
        }

        return [];
    }

    /**
     * Compute string similarity percentage
     */
    public function computeStringSimilarity(string $str1, string $str2): float
    {
        $s1 = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $str1)));
        $s2 = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $str2)));

        if ($s1 === $s2 || mb_strpos($s2, $s1) !== false || mb_strpos($s1, $s2) !== false) {
            return 100.0;
        }

        similar_text($s1, $s2, $percent);
        return (float) $percent;
    }

    /**
     * Calculate net non-overlapping similarity percentage across the manuscript
     */
    public function calculateNetSimilarity(string $fullText, array $matches, PlagiarismScan $scan): float
    {
        $totalWords = max(1, count(preg_split('/\s+/u', trim($fullText), -1, PREG_SPLIT_NO_EMPTY)));
        $matchedWordsCount = 0;

        $excludedDomains = is_array($scan->excluded_domains) ? array_map('strtolower', $scan->excluded_domains) : [];
        $activeRanges = [];

        foreach ($matches as $match) {
            $isExcluded = $match instanceof PlagiarismMatch ? $match->is_excluded : (!empty($match['is_excluded']));
            if ($isExcluded) {
                continue;
            }

            $domain = strtolower($match instanceof PlagiarismMatch ? ($match->source_domain ?? '') : ($match['source_domain'] ?? ''));
            if (!empty($domain) && in_array($domain, $excludedDomains)) {
                continue;
            }

            $start = $match instanceof PlagiarismMatch ? $match->start_offset : $match['start_offset'];
            $end = $match instanceof PlagiarismMatch ? $match->end_offset : $match['end_offset'];

            $activeRanges[] = [$start, $end];
        }

        if (empty($activeRanges)) {
            return 0.0;
        }

        usort($activeRanges, fn($a, $b) => $a[0] <=> $b[0]);

        $merged = [];
        $current = $activeRanges[0];

        for ($i = 1; $i < count($activeRanges); $i++) {
            $next = $activeRanges[$i];
            if ($next[0] <= $current[1]) {
                // Overlap: merge
                $current[1] = max($current[1], $next[1]);
            } else {
                $merged[] = $current;
                $current = $next;
            }
        }
        $merged[] = $current;

        // Calculate matched words from merged ranges
        foreach ($merged as $range) {
            $chunk = mb_substr($fullText, $range[0], $range[1] - $range[0]);
            $chunkWords = count(preg_split('/\s+/u', trim($chunk), -1, PREG_SPLIT_NO_EMPTY));
            $matchedWordsCount += $chunkWords;
        }

        $percentage = ($matchedWordsCount / $totalWords) * 100.0;
        return round(min(100.0, max(0.0, $percentage)), 1);
    }

    /**
     * Compile structured summary metadata
     */
    private function compileSummaryMetadata(array $matches, array $aiAnalysis, int $totalWords): array
    {
        $sourcesMap = [];
        foreach ($matches as $match) {
            $domain = $match->source_domain ?? 'Web Source';
            if (!isset($sourcesMap[$domain])) {
                $sourcesMap[$domain] = [
                    'domain' => $domain,
                    'title' => $match->source_title ?? $domain,
                    'url' => $match->source_url,
                    'match_count' => 0,
                    'highest_similarity' => 0.0,
                    'type' => $match->match_type ?? 'web',
                ];
            }
            $sourcesMap[$domain]['match_count']++;
            $sourcesMap[$domain]['highest_similarity'] = max($sourcesMap[$domain]['highest_similarity'], $match->similarity_score);
        }

        usort($sourcesMap, fn($a, $b) => $b['match_count'] <=> $a['match_count']);

        return [
            'total_sources' => count($sourcesMap),
            'top_sources' => array_values(array_slice($sourcesMap, 0, 10)),
            'total_matches_flagged' => count($matches),
            'ai_analysis' => $aiAnalysis,
            'word_count' => $totalWords,
            'scanned_at' => now()->toIso8601String(),
        ];
    }
}
