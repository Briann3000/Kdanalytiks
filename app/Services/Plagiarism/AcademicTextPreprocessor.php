<?php

namespace App\Services\Plagiarism;

class AcademicTextPreprocessor
{
    /**
     * Preprocess and segment document into searchable segments with offset markers
     */
    public function preprocess(string $rawText, bool $excludeQuotes = true, bool $excludeReferences = true, int $minWords = 8, bool $excludeCitations = true): array
    {
        $textLength = mb_strlen($rawText);
        $bibliographyStartOffset = $excludeReferences ? $this->locateBibliographyStart($rawText) : null;

        // Split text into paragraphs and sentences while keeping exact character offsets
        $segments = [];
        $offset = 0;

        // Split by paragraph first
        $paragraphs = preg_split('/(\r\n|\n|\r)/', $rawText, -1, PREG_SPLIT_DELIM_CAPTURE);
        $currentOffset = 0;

        foreach ($paragraphs as $piece) {
            $pieceLen = mb_strlen($piece);
            if ($piece === "\n" || $piece === "\r\n" || $piece === "\r" || empty(trim($piece))) {
                $currentOffset += $pieceLen;
                continue;
            }

            // Segment sentences within paragraph
            $sentences = $this->splitIntoSentences($piece);
            $paraRelativeOffset = 0;

            foreach ($sentences as $sentence) {
                $sentenceTrimmed = trim($sentence);
                if (empty($sentenceTrimmed)) {
                    continue;
                }

                $sentencePos = mb_strpos($piece, $sentence, $paraRelativeOffset);
                if ($sentencePos === false) {
                    $sentencePos = $paraRelativeOffset;
                }
                $startOffset = $currentOffset + $sentencePos;
                $endOffset = $startOffset + mb_strlen($sentence);
                $paraRelativeOffset = $sentencePos + mb_strlen($sentence);

                $wordCount = count(preg_split('/\s+/u', $sentenceTrimmed, -1, PREG_SPLIT_NO_EMPTY));
                $isQuote = $this->isDirectQuote($sentenceTrimmed);
                $isCitation = $this->isCitationOnly($sentenceTrimmed);
                $isBibliography = ($bibliographyStartOffset !== null && $startOffset >= $bibliographyStartOffset);

                $isExcluded = false;
                $exclusionReason = null;

                if ($isBibliography) {
                    $isExcluded = true;
                    $exclusionReason = 'References & Bibliography';
                } elseif ($excludeQuotes && $isQuote) {
                    $isExcluded = true;
                    $exclusionReason = 'Direct Quotation';
                } elseif ($excludeCitations && $isCitation) {
                    $isExcluded = true;
                    $exclusionReason = 'In-Text Citation';
                } elseif ($wordCount < $minWords) {
                    $isExcluded = true;
                    $exclusionReason = 'Below Word Threshold';
                }

                $segments[] = [
                    'text' => $sentenceTrimmed,
                    'start_offset' => $startOffset,
                    'end_offset' => $endOffset,
                    'word_count' => $wordCount,
                    'is_quote' => $isQuote,
                    'is_citation' => $isCitation,
                    'is_bibliography' => $isBibliography,
                    'is_excluded' => $isExcluded,
                    'exclusion_reason' => $exclusionReason,
                ];
            }

            $currentOffset += $pieceLen;
        }

        return [
            'raw_text' => $rawText,
            'total_characters' => $textLength,
            'total_words' => count(preg_split('/\s+/u', trim($rawText), -1, PREG_SPLIT_NO_EMPTY)),
            'bibliography_offset' => $bibliographyStartOffset,
            'segments' => $segments,
        ];
    }

    /**
     * Locate the start offset of the References / Bibliography section in academic manuscripts
     */
    private function locateBibliographyStart(string $text): ?int
    {
        // Common Academic Bibliography Headers
        $patterns = [
            '/(?:\n|^)\s*(?:#{1,3}\s*)?(?:References|REFERENCES|Bibliography|BIBLIOGRAPHY|Works\s+Cited|WORKS\s+CITED|Literature\s+Cited|References\s+and\s+Notes)\s*(?:\n|$)/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                // Return character offset (convert byte offset to multibyte)
                $byteOffset = $matches[0][1];
                return mb_strlen(substr($text, 0, $byteOffset));
            }
        }

        return null;
    }

    /**
     * Detect if a text fragment is enclosed in direct quotes
     */
    private function isDirectQuote(string $text): bool
    {
        $text = trim($text);
        if (mb_strlen($text) < 4) {
            return false;
        }

        $starts = in_array(mb_substr($text, 0, 1), ['"', '“', '«', "'", '‘']);
        $ends = in_array(mb_substr($text, -1, 1), ['"', '”', '»', "'", '’', '.', ';']);

        return $starts && $ends;
    }

    /**
     * Detect if a sentence is merely a standalone citation reference
     */
    private function isCitationOnly(string $text): bool
    {
        // APA/MLA/Harvard pattern: (Author, 2020) or (Author et al., 2021: 45)
        return (bool) preg_match('/^\([A-Z][a-zA-Z\s\.,&]+,\s*\d{4}[a-z]?(?::\s*\d+(?:-\d+)?)?\)$/u', trim($text));
    }

    /**
     * Split text cleanly into sentences using punctuation boundaries
     */
    private function splitIntoSentences(string $text): array
    {
        // Avoid breaking on common academic abbreviations (e.g., e.g., i.e., Dr., Prof., Vol., No., pp., et al., etc.)
        $protected = preg_replace_callback('/\b(e\.g|i\.e|et al|Prof|Dr|Mr|Mrs|Ms|vs|Vol|No|pp|p|Fig|al)\./i', function ($m) {
            return $m[1] . '__DOT__';
        }, $text);

        $rawSentences = preg_split('/(?<=[.?!;])\s+(?=[A-Z0-9"“«])/u', $protected);

        return array_map(function ($sentence) {
            return str_replace('__DOT__', '.', $sentence);
        }, $rawSentences);
    }

    /**
     * Generate representative rolling n-gram shingles (7-10 words) for search indexing
     */
    public function generateShingles(string $text, int $shingleSize = 8): array
    {
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $totalWords = count($words);

        if ($totalWords <= $shingleSize) {
            return [$text];
        }

        $shingles = [];
        $step = max(1, (int) floor($shingleSize / 2)); // 50% overlap

        for ($i = 0; $i <= $totalWords - $shingleSize; $i += $step) {
            $slice = array_slice($words, $i, $shingleSize);
            $shingles[] = implode(' ', $slice);
        }

        return $shingles;
    }
}
