<?php

namespace App\Services\Plagiarism;

class DeepTextAlignmentService
{
    /**
     * Perform deep text-to-document alignment between manuscript segments and a fetched source webpage
     */
    public function alignSegmentAgainstDocument(array $segment, string $sourceDocText, int $minMatchWords = 6): ?array
    {
        $segText = $segment['text'];
        $cleanSeg = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $segText)));
        $cleanDoc = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $sourceDocText)));

        if (empty($cleanSeg) || empty($cleanDoc)) {
            return null;
        }

        // 1. Direct exact or substring match
        if (mb_strpos($cleanDoc, $cleanSeg) !== false) {
            return [
                'similarity_score' => 100.0,
                'matched_snippet' => mb_substr($segText, 0, 500),
                'match_type' => 'exact',
            ];
        }

        // 2. Rolling n-gram shingle match against document
        $segWords = preg_split('/\s+/u', $cleanSeg, -1, PREG_SPLIT_NO_EMPTY);
        $totalSegWords = count($segWords);

        if ($totalSegWords < $minMatchWords) {
            return null;
        }

        // Extract 6-word rolling shingles from the segment
        $shingleSize = min(6, $totalSegWords);
        $matchedShingles = 0;
        $totalShingles = 0;
        $sampleBestSnippet = '';

        for ($i = 0; $i <= $totalSegWords - $shingleSize; $i++) {
            $shingle = implode(' ', array_slice($segWords, $i, $shingleSize));
            $totalShingles++;

            $pos = mb_strpos($cleanDoc, $shingle);
            if ($pos !== false) {
                $matchedShingles++;
                if (empty($sampleBestSnippet)) {
                    $startSnip = max(0, $pos - 30);
                    $sampleBestSnippet = mb_substr($sourceDocText, $startSnip, 350);
                }
            }
        }

        if ($totalShingles > 0 && ($matchedShingles / $totalShingles) >= 0.40) {
            $ratio = ($matchedShingles / $totalShingles) * 100.0;
            return [
                'similarity_score' => round(min(100.0, max(45.0, $ratio)), 1),
                'matched_snippet' => !empty($sampleBestSnippet) ? $sampleBestSnippet : mb_substr($segText, 0, 300),
                'match_type' => 'shingle_overlap',
            ];
        }

        // 3. Similar text ratio fallback
        similar_text($cleanSeg, mb_substr($cleanDoc, 0, mb_strlen($cleanSeg) * 2), $percent);
        if ($percent >= 60.0) {
            return [
                'similarity_score' => round($percent, 1),
                'matched_snippet' => mb_substr($sourceDocText, 0, 300),
                'match_type' => 'fuzzy',
            ];
        }

        return null;
    }
}
