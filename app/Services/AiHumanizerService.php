<?php

namespace App\Services;

use App\Models\Survey;
use Illuminate\Support\Str;

class AiHumanizerService
{
    public function __construct(
        private readonly GroqStreamingClient $groqStreamingClient,
        private readonly ?\App\Services\Ai\UnifiedAiDetectorService $unifiedAiDetector = null
    ) {
    }

    /**
     * Scan the text using the centralized Unified AI detector for consistent scoring.
     */
    public function analyzeText(string $text): array
    {
        $detector = $this->unifiedAiDetector ?? new \App\Services\Ai\UnifiedAiDetectorService($this->groqStreamingClient);
        return $detector->analyze($text);
    }

    /**
     * Send text to the LLM to humanize it using dynamic settings, including paragraph chunking,
     * cross-lingual pivot back-translation, and multi-pass refinement loops.
     */
    public function humanizeText(string $text, string $mode = 'standard', string $intensity = 'medium', ?string $customInstructions = null): string
    {
        // Step 1: Chunk paragraphs keeping each chunk around 3500 chars to avoid token limit drops
        $rawParagraphs = preg_split('/\n\s*\n/', $text);
        $paragraphs = [];

        foreach ($rawParagraphs as $rawPara) {
            $rawPara = trim($rawPara);
            if (empty($rawPara)) {
                continue;
            }

            // Subdivide giant single paragraphs (>3500 chars) along sentence boundaries
            if (strlen($rawPara) > 3500) {
                $subSentences = preg_split('/(?<=[.!?])\s+/u', $rawPara);
                $subChunk = [];
                $subLen = 0;
                foreach ($subSentences as $sent) {
                    $sentLen = strlen($sent);
                    if ($subLen + $sentLen > 3000 && !empty($subChunk)) {
                        $paragraphs[] = implode(' ', $subChunk);
                        $subChunk = [];
                        $subLen = 0;
                    }
                    $subChunk[] = $sent;
                    $subLen += $sentLen;
                }
                if (!empty($subChunk)) {
                    $paragraphs[] = implode(' ', $subChunk);
                }
            } else {
                $paragraphs[] = $rawPara;
            }
        }

        $chunks = [];
        $currentChunk = [];
        $currentLength = 0;

        foreach ($paragraphs as $para) {
            $paraLength = strlen($para);
            if ($currentLength + $paraLength > 3500 && !empty($currentChunk)) {
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
            // Stage 1: Cross-Lingual Pivot (Option C) to disrupt syntactic token probability trees
            $pivotChunk = $this->pivotTranslate($chunk, 'Spanish');

            // Stage 2: Target Style Alignment & Entity Polish (academic, standard, creative)
            $hChunk = $this->processParagraphChunk($pivotChunk, $mode, $intensity, $customInstructions);

            // Stage 3: Guardrail scan & targeted refinement if AI probability exceeds 20%
            $cScan = $this->analyzeText($hChunk);
            if ($cScan['aiProbability'] > 20) {
                $hChunk = $this->runRefinementPass($hChunk, $customInstructions);
            }
            $humanizedChunks[] = $hChunk;
        }

        return implode("\n\n", $humanizedChunks);
    }

    /**
     * Perform a bidirectional cross-lingual pivot (English -> Pivot Language -> English)
     * to disrupt LLM n-gram probability distributions and burstiness profiles.
     */
    public function pivotTranslate(string $text, string $pivotLanguage = 'Spanish'): string
    {
        if (trim($text) === '') {
            return $text;
        }

        try {
            // Forward pass: English -> Pivot Language (high fidelity)
            $intermediate = $this->translateToPivot($text, $pivotLanguage);
            if (empty(trim($intermediate))) {
                return $text;
            }

            // Reverse pass: Pivot Language -> Idiomatic Human English (varied burstiness)
            $retranslated = $this->translateFromPivot($intermediate, $pivotLanguage);
            if (empty(trim($retranslated))) {
                return $intermediate;
            }

            return $retranslated;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Cross-lingual pivot fallback: ' . $e->getMessage());
            return $text;
        }
    }

    /**
     * Translate text from English to an intermediate pivot language preserving technical entities and citations.
     */
    public function translateToPivot(string $text, string $targetLanguage = 'Spanish'): string
    {
        $systemPrompt = "You are an expert bilingual linguist translating English prose into natural, idiomatic {$targetLanguage}.
Your translation will serve as an intermediate linguistic bridge.

CRITICAL INTEGRITY CONSTRAINTS:
1. PRESERVE FACTUAL DATA: Keep all numbers, percentages, dates, statistics, formulas, and metric units strictly identical.
2. PRESERVE CITATIONS: Keep all academic citations (e.g. '(Smith et al., 2021)', '(Johnson, 2019, p. 45)'), author names, and bibliographic references verbatim.
3. PRESERVE PROPER ENTITIES: Maintain proper nouns, institutional names, and domain-specific terminology unchanged.
4. NATURAL SYNTAX: Express sentences fluently in natural {$targetLanguage} rather than word-for-word literal substitution.
5. PRESERVE STRUCTURE: Retain exact paragraph breaks.

Return ONLY the {$targetLanguage} translation. Do not include any greeting, preamble, notes, or markdown commentary.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $text]
        ];

        $result = $this->groqStreamingClient->streamChatCompletion(
            $messages,
            fn($delta) => null,
            config('services.groq.model', 'openai/gpt-oss-120b'),
            0.3
        );

        return trim($result['content'] ?? '');
    }

    /**
     * Re-translate intermediate text back into natural, idiomatic English breaking robotic token patterns.
     */
    public function translateFromPivot(string $text, string $sourceLanguage = 'Spanish'): string
    {
        $systemPrompt = "You are a master bilingual editor translating {$sourceLanguage} prose back into natural, authentic human English.
Your goal is to convey the exact original meaning with organic human cadence, diverse sentence rhythm, and zero machine-generated stiffness.

CORE RULES FOR NATURAL HUMAN RE-TRANSLATION:
1. NATURAL HUMAN CADENCE: Use idiomatic English phrasing. Strictly avoid stiff, awkward literal phrasing ('translationese').
2. BURSTINESS & SENTENCE DIVERSITY: Intentionally alternate sentence lengths. Pair longer analytical clauses with punchy, direct statements.
3. BAN SYNTHETIC AI MARKERS: You are strictly forbidden from using generic AI transition clichés or buzzwords:
   - Banned words: 'delve', 'delving', 'testament', 'tapestry', 'pivotal', 'furthermore', 'moreover', 'paramount', 'realm', 'beacon', 'crucial', 'multifaceted', 'interdisciplinary', 'stark', 'underscore'.
   - Connect ideas organically through logic rather than formulaic transition words.
4. PRESERVE FACTUAL DATA & CITATIONS: Strictly preserve all figures, percentages, dates, equations, and citations (e.g., '(Smith et al., 2021)') exactly as in the source.
5. PRESERVE STRUCTURE: Preserve paragraph count and formatting.

Return ONLY the final English translation. Do not include notes, introduction, or explanations.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $text]
        ];

        $result = $this->groqStreamingClient->streamChatCompletion(
            $messages,
            fn($delta) => null,
            config('services.groq.model', 'openai/gpt-oss-120b'),
            0.7
        );

        return trim($result['content'] ?? '');
    }

    /**
     * Process a chunk of paragraphs preserving exact structure.
     */
    protected function processParagraphChunk(string $chunk, string $mode, string $intensity, ?string $customInstructions = null): string
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
            config('services.groq.model', 'openai/gpt-oss-120b'),
            0.95
        );

        return $result['content'] ?? $chunk;
    }

    /**
     * Secondary refinement pass over the full text.
     */
    protected function runRefinementPass(string $text, ?string $customInstructions = null): string
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
            config('services.groq.model', 'openai/gpt-oss-120b'),
            0.95
        );

        return $result['content'] ?? $text;
    }
}
