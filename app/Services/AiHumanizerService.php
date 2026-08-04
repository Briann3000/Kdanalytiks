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
        $perplexityScore = min(100, max(0, round($uniqueRatio * 125)));

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
            // Map standard deviation to burstiness score (0 - 100) with realistic human threshold
            $burstinessScore = min(100, max(0, round($stdDev * 14)));
        } else {
            $burstinessScore = 30;
        }

        // 3. Contraction Ratio (Human Signal)
        preg_match_all("/\b[a-z]+'(?:t|ll|re|ve|d|m|s)\b/i", $text, $contractions);
        $contractionCount = count($contractions[0]);
        $contractionBonus = min(15, $contractionCount * 4);

        // 4. Flagged Words
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

        // 5. AI Probability Estimate (Rebalanced)
        $aiWeight = 100 - (($perplexityScore * 0.45) + ($burstinessScore * 0.45));
        $aiWeight -= $contractionBonus;
        // Cap flagged word penalty at 20 points max
        $aiWeight += min(20, count($foundFlags) * 5);
        $aiProbability = min(100, max(0, round($aiWeight)));

        // 6. Recommendations
        $recs = [];
        if ($burstinessScore < 45) {
            $recs[] = 'Sentence lengths are uniform. Mix short sentences with longer ones to improve natural burstiness.';
        }
        if ($perplexityScore < 45) {
            $recs[] = 'Vocabulary choice is predictable. Try using more varied descriptive words and natural phrasing.';
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
    public function humanizeText(string $text, string $mode = 'standard', string $intensity = 'medium', ?string $customInstructions = null): string
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
            $hChunk = $this->processParagraphChunk($chunk, $mode, $intensity, $customInstructions);
            // Selective per-chunk refinement (only if AI Risk > 25%) to optimize token costs and eliminate full-document truncation
            $cScan = $this->analyzeText($hChunk);
            if ($cScan['aiProbability'] > 25) {
                $hChunk = $this->runRefinementPass($hChunk, $customInstructions);
            }
            $humanizedChunks[] = $hChunk;
        }

        return implode("\n\n", $humanizedChunks);
    }

    /**
     * Process a chunk of paragraphs preserving exact structure.
     */
    private function processParagraphChunk(string $chunk, string $mode, string $intensity, ?string $customInstructions = null): string
    {

        if ($mode === 'academic') {
            $systemPrompt = "You are a senior academic researcher refining a draft for a peer-reviewed journal. 
Your objective is to rewrite the text with high academic rigor and clinical precision. 

CORE RULES FOR ACADEMIC HUMANIZATION:

1. ACADEMIC RESTRAINT & CAUTION: 
- Avoid absolute claims. Frame observations objectively (e.g., framing findings as indications, alignments, or observations rather than absolute proofs).
- DO NOT force awkward phrasing. The text must remain highly readable, logical, and grammatically flawless.

2. COHESIVE, COMPLEX SENTENCE STRUCTURES (CRITICAL):
- DO NOT write in a choppy, staccato manner. You are strictly forbidden from generating sequences of short, isolated, simple sentences.
- You MUST combine related concepts into fluid, complex sentences using subordinating conjunctions (e.g., 'While...', 'Although...', 'Given that...') and relative clauses (e.g., '...which in turn...').
- Intentionally vary your sentence lengths. Anchor a sprawling, multi-clause analytical thought with a concise, impactful summary sentence.

3. BAN SYNTHETIC TRANSITIONS:
- Completely ban robotic AI transition markers (e.g., 'Furthermore,' 'Moreover,' 'In conclusion,' 'Crucially,' 'Significantly,' 'Thus,' 'Therefore'). 
- Ensure ideas flow smoothly into one another based on logic, not filler words.

4. CLINICAL PRECISION (NO BUZZWORDS):
- Strip all flowery, hyperbolic AI adjectives. 
- Ban the following words entirely: transformative, robust, holistic, paramount, myriad, plethora, tapestry, delve, underscore, stark, realm. 
- Use dry, precise, domain-specific terminology.

5. PRESERVE DATA & MEANING:
- Maintain all original facts, variables, and analytical meaning. Do not summarize or dilute the core arguments.
- Preserve the exact number of paragraphs.";

        } elseif ($mode === 'creative') {
            $systemPrompt = "You are an expressive, creative writer. Focus on vivid imagery, varied sentence lengths, and an engaging narrative flow. Avoid typical AI buzzwords and robotic transition structures. Preserve the original meaning.";

        } else {
            // Default 'standard' mode
            $systemPrompt = "You are a busy domain expert quickly dictating notes to a colleague. You do not care about perfect academic flow or sounding like a copywriter. 
Your goal is to convey the input information in a highly conversational, slightly unpolished, and direct manner. 
Use a 'train of thought' structure. Sometimes use very short, blunt sentences. Sometimes ramble slightly before making your point. 
Do not use standard essay formatting. Break grammatical rules if it makes the text sound more like a spoken conversation. 
Never use transition words like 'Look,', 'And yet,', 'Furthermore', or 'Moreover'.";
        }

        if (!empty($customInstructions)) {
            $systemPrompt .= "\n\n10. USER'S PERSONAL VOICE & STYLISTIC INSTRUCTIONS:\n";
            $systemPrompt .= "- You MUST strictly apply these personal writing preferences and custom instructions: \"{$customInstructions}\"";
        }

        $systemPrompt .= "\nReturn ONLY the final humanized text. Do not include any greeting, explanation, preamble, or trailing text.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $chunk]
        ];

        $result = $this->groqStreamingClient->streamChatCompletion(
            $messages,
            fn($delta) => null,
            'llama-3.3-70b-versatile',
            0.95
        );

        return $result['content'] ?? $chunk;
    }

    /**
     * Secondary refinement pass over the full text.
     */
    private function runRefinementPass(string $text, ?string $customInstructions = null): string
    {
        $systemPrompt = "You are an expert copywriter and AI-detection bypass specialist. Your task is to perform an aggressive, secondary humanization pass over a draft that still carries mechanical AI patterns.

You must apply these exact formatting corrections:
1. FORCE BURSTINESS: Find the longest sentence in the text and ensure it is immediately preceded or followed by a sentence under 7 words. Split medium sentences to create this contrast.
2. PURGE CRUTCHES: Ruthlessly delete any conversational crutch words at the start of sentences (e.g., 'Look,', 'So,', 'Well,', 'And yet,', 'Ultimately,'). 
3. STRIP JARGON: Replace any remaining corporate or academic buzzwords (plethora, myriad, paramount, underscore, stark, realm) with simple, direct vocabulary.
4. TONE ALIGNMENT: If an academic tone is required, maintain professionalism but do not revert to a robotic formula. Do not use casual slang if the context is formal, but keep the sentence length varied.";

        if (!empty($customInstructions)) {
            $systemPrompt .= "\n- Strictly incorporate the user's personal style instructions: \"{$customInstructions}\"";
        }

        $systemPrompt .= "\n\nReturn ONLY the refined humanized text, without any preambles or notes.";

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
