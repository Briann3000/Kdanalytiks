<?php

namespace App\Services;

use App\Models\Survey;
use Illuminate\Support\Str;

class AiHumanizerService
{
    private const FLAGGED_WORDS = [
        'delve',
        'testament',
        'tapestry',
        'pivotal',
        'furthermore',
        'moreover',
        'demystify',
        'showcase',
        'revolutionary',
        'groundbreaking',
        'beacon',
        'utilize',
        'optimize',
        'interdisciplinary',
        'holistic',
        'robust',
        'plethora',
        'myriad',
        'paramount',
        'underscore',
        'stark',
        'testament',
        'realm',
        'fostering',
        'navigating',
        'shed_light',
        'beacon_of'
    ];

    public function __construct(
        private readonly GroqStreamingClient $groqStreamingClient
    ) {
    }

    /**
     * Scan the text using heuristics to estimate AI signature probability.
     */
    public function analyzeText(string $text): array
    {
        // Strip punctuation and split to count total words
        $cleanText = preg_replace('/[^a-z\s]/i', '', strtolower($text));
        $words = preg_split('/\s+/', $cleanText);
        $words = array_filter($words, fn($w) => strlen($w) > 0);
        $totalWordsCount = count($words);

        if ($totalWordsCount === 0) {
            return [
                'aiProbability' => 0,
                'perplexity' => 100,
                'burstiness' => 100,
                'flaggedWords' => [],
                'recommendations' => ['Please enter some text to analyze.']
            ];
        }

        // 1. Perplexity (Unique Word Ratio)
        $uniqueWords = array_unique($words);
        $uniqueRatio = count($uniqueWords) / $totalWordsCount;
        $perplexityScore = min(100, max(0, round($uniqueRatio * 130)));

        // 2. Burstiness (Sentence Length Variance)
        $sentences = preg_split('/[.!?]+/', $text);
        $sentences = array_filter(array_map('trim', $sentences), fn($s) => strlen($s) > 0);
        $sentenceCount = count($sentences);

        $sentenceLengths = [];
        foreach ($sentences as $sentence) {
            $swords = preg_split('/\s+/', $sentence);
            $sentenceLengths[] = count(array_filter($swords));
        }

        $burstinessScore = 0;
        if ($sentenceCount > 1) {
            $avgLength = array_sum($sentenceLengths) / $sentenceCount;
            $varianceSum = 0;
            foreach ($sentenceLengths as $len) {
                $varianceSum += pow($len - $avgLength, 2);
            }
            $stdDev = sqrt($varianceSum / ($sentenceCount - 1));
            // Map standard deviation to burstiness score (0 - 100)
            $burstinessScore = min(100, max(0, round($stdDev * 8)));
        } else {
            $burstinessScore = 10;
        }

        // 3. Flagged Words
        $foundFlags = [];
        foreach (self::FLAGGED_WORDS as $flag) {
            $pattern = '/\b' . preg_quote($flag, '/') . '\b/i';
            if (preg_match_all($pattern, $text, $matches)) {
                $foundFlags[] = [
                    'word' => $flag,
                    'count' => count($matches[0])
                ];
            }
        }

        // 4. AI Probability Estimate
        $aiWeight = 100 - (($perplexityScore * 0.4) + ($burstinessScore * 0.4));
        $aiWeight += count($foundFlags) * 12;
        $aiProbability = min(100, max(0, round($aiWeight)));

        // 5. Recommendations
        $recs = [];
        if ($burstinessScore < 45) {
            $recs[] = 'Sentence lengths are highly uniform. Mix short sentences with longer ones to improve natural burstiness.';
        }
        if ($perplexityScore < 45) {
            $recs[] = 'Vocabulary choice is highly predictable. Try using more varied descriptive words and natural phrasing.';
        }
        if (count($foundFlags) > 0) {
            $flagList = collect($foundFlags)->pluck('word')->implode(', ');
            $recs[] = "Avoid typical AI transition words and clichés found in your text: {$flagList}.";
        }
        if ($aiProbability < 40) {
            $recs[] = 'Great job! The text shows strong indicators of human erratic flow and vocabulary variety.';
        } else {
            $recs[] = 'Use the Humanizer to automatically restructure sentence rhythm and strip typical AI vocabulary patterns.';
        }

        return [
            'aiProbability' => $aiProbability,
            'perplexity' => $perplexityScore,
            'burstiness' => $burstinessScore,
            'flaggedWords' => $foundFlags,
            'recommendations' => $recs
        ];
    }

    /**
     * Send text to the LLM to humanize it using dynamic settings, including paragraph chunking and multi-pass loops.
     */
    public function humanizeText(string $text, string $mode = 'standard', string $intensity = 'medium'): string
    {
        // Step 1: Chunk paragraphs keeping each chunk around 4000 chars to avoid token limit drops
        $paragraphs = preg_split('/\n\s*\n/', $text);
        $paragraphs = array_filter(array_map('trim', $paragraphs));

        $chunks = [];
        $currentChunk = [];
        $currentLength = 0;

        foreach ($paragraphs as $para) {
            $paraLength = strlen($para);
            if ($currentLength + $paraLength > 4000 && !empty($currentChunk)) {
                $chunks[] = implode("\n\n", $currentChunk);
                $currentChunk = [];
                $currentLength = 0;
            }
            $currentChunk[] = $para;
            $currentLength += $paraLength;
        }
        if (!empty($currentChunk)) {
            $chunks[] = implode("\n\n", $currentChunk);
        }

        $humanizedChunks = [];
        foreach ($chunks as $chunk) {
            $hChunk = $this->processParagraphChunk($chunk, $mode, $intensity);
            // Selective per-chunk refinement (only if AI Risk > 25%) to optimize token costs and eliminate full-document truncation
            $cScan = $this->analyzeText($hChunk);
            if ($cScan['aiProbability'] > 25) {
                $hChunk = $this->runRefinementPass($hChunk);
            }
            $humanizedChunks[] = $hChunk;
        }

        return implode("\n\n", $humanizedChunks);
    }

    /**
     * Process a chunk of paragraphs preserving exact structure.
     */
    private function processParagraphChunk(string $chunk, string $mode, string $intensity): string
    {
        $systemPrompt = "You are an expert copywriter specializing in humanizing AI-generated content to completely bypass AI detectors (Turnitin, Quillbot, GPTZero, etc.). Your goal is to rewrite the input text paragraph-by-paragraph to emulate natural, dynamic, and erratic human writing profiles.

CORE HUMANIZATION RULES:

1. PARAGRAPH RHYTHM & OPENER DIVERSIFICATION:
- Vary sentence structures wildly. Include periodic single-sentence emphasis statements next to sprawling, clause-heavy compound paragraphs.
- Banish predictable topic-sentence formulations (e.g., 'The study of...', 'This analysis represents...', 'Agroecology represents...'). Force openers to begin with localized conditions, direct rhetorical framing, or mid-thought narrative shifts.

2. EXTREME BURSTINESS & RHYTHM SHOCK:
- Enforce strict length contrast: pair micro-sentences (4 to 6 words) directly adjacent to sprawling, multi-clause compound sentences (35+ words).
- Force clusters of 2 or 3 extremely short sentences in a row (e.g., 'The numbers were clear. No one was surprised. People are just tired.').
- Eliminate the uniform 15-to-25-word sentence rhythm entirely.

3. BREAK SYNTACTIC PARALLELISM:
- Mix nouns, verbs, and clauses unevenly inside lists rather than using parallel grammatical structures.

4. ABSOLUTE BUZZWORD & TRANSITION BLACKLIST:
- Do not use standard AI transitional words (furthermore, moreover, consequently, in addition, subsequently, thus, therefore, in conclusion). Use organic, conversational conjunctions (e.g., 'And yet,', 'So,', 'But,', 'Look,', 'Actually,').
- Completely ban flowery, corporate, or academic AI buzzwords: plethora, myriad, paramount, underscore, stark, realm, fostering, navigating, transformative, leverage, catalyst, paradigm, landscape, foster, resonate, seamlessly, elevate, streamline, empower, holistic, robust. Replace them with simpler, grounded verbs and nouns.

5. Maintain point of view in original text (first-person, second-person, third-person) and preserve the original facts, data points, and core meaning. Do not summarize or omit key analytical information.

6. HEDGING, CONFIDENCE, & CONVERSATIONAL FILLERS:
- Introduce direct assertions and localized modifiers (e.g., 'clearly', 'frankly', 'largely', 'to be honest') to inject human conviction

7. GRAMMAR FLOW MICRO-VARIATIONS:
- Write with a academic-lite voice, allowing for sentence-ending prepositions and colloquial contractions.


8. STATISTICAL REDIRECT:
- Convert dry statistical list sequences into conversational summaries (e.g., changing '20% agree, 20% neutral' to 'roughly a fifth agreed, while another fifth stayed neutral').

9. PRESERVE STRUCTURE:
- You MUST rewrite the input text paragraph-by-paragraph.
- Preserve the exact number of paragraphs in the input. Do not merge, summarize, shorten, or omit paragraphs.
- Maintain the exact original facts, data points, and core meaning. Do not summarize or omit key analytical information.
Never combine comma and 'and' if finalizing a list. (eg., 'apples, oranges, and bananas' is incorrect; use 'apples, oranges and bananas').
Return ONLY the final humanized text. Do not include any greeting, explanation, preamble, or trailing text.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $chunk]
        ];

        $result = $this->groqStreamingClient->streamChatCompletion(
            $messages,
            fn($delta) => null,
            'llama-3.3-70b-versatile',
            0.9
        );

        return $result['content'] ?? $chunk;
    }

    /**
     * Secondary refinement pass over the full text.
     */
    private function runRefinementPass(string $text): string
    {
        $systemPrompt = "You are an expert copywriter. Your task is to perform an aggressive, secondary humanization pass over a draft that still carries some mechanical AI patterns. 
You must break up the sentence structures even more dramatically:
- Split any remaining long sentences into two or three short sentences.
- If academic tone selected don't use casual tone.
- Replace any remaining corporate, academic, or complex words (like plethora, myriad, paramount, underscore, stark, realm, fostering) with simple, direct vocabulary.
- Ensure the result has high sentence length variation (burstiness) but not too much.

Return ONLY the refined humanized text, without any preambles or notes.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $text]
        ];

        $result = $this->groqStreamingClient->streamChatCompletion(
            $messages,
            fn($delta) => null,
            'llama-3.3-70b-versatile',
            0.95
        );

        return $result['content'] ?? $text;
    }
}
