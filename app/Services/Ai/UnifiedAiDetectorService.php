<?php

namespace App\Services\Ai;

use App\Services\GroqStreamingClient;
use Illuminate\Support\Facades\Log;
use Throwable;

class UnifiedAiDetectorService
{
    private const FLAGGED_AI_MARKERS = [
        'delve',
        'delves',
        'delving',
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
        'underscores',
        'stark',
        'realm',
        'fostering',
        'navigating',
        'shed_light',
        'beacon_of',
        'in summary',
        'in conclusion',
        'it is crucial',
        'it is important to note',
        'plays a vital role',
        'ever-evolving',
        'multifaceted'
    ];

    public function __construct(
        private readonly ?GroqStreamingClient $groqStreamingClient = null
    ) {
    }

    /**
     * Comprehensive multi-factor AI probability analysis
     */
    public function analyze(string $text): array
    {
        $cleanText = trim($text);
        if (empty($cleanText)) {
            return [
                'aiProbability' => 0.0,
                'perplexity' => 100.0,
                'burstiness' => 100.0,
                'flaggedWords' => [],
                'riskLevel' => 'Likely Human',
                'recommendations' => ['Please provide text to analyze.'],
                'sentenceBreakdown' => [],
            ];
        }

        // Tokenize words
        $wordsOnly = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', mb_strtolower($cleanText));
        $words = preg_split('/\s+/u', trim($wordsOnly), -1, PREG_SPLIT_NO_EMPTY);
        $totalWordsCount = count($words);

        if ($totalWordsCount < 15) {
            return [
                'aiProbability' => 0.0,
                'perplexity' => 100.0,
                'burstiness' => 100.0,
                'flaggedWords' => [],
                'riskLevel' => 'Likely Human',
                'recommendations' => ['Text is too short for reliable statistical AI pattern detection.'],
                'sentenceBreakdown' => [],
            ];
        }

        // 1. Perplexity / Lexical Entropy (Unique Word Ratio & Repetitive Bigrams)
        $uniqueWords = array_unique($words);
        $uniqueRatio = count($uniqueWords) / $totalWordsCount;
        $perplexityScore = min(100.0, max(0.0, round($uniqueRatio * 125, 1)));

        // 2. Burstiness & Rhythm (Sentence Length Standard Deviation)
        $sentences = preg_split('/[.!?]+(?:\s+|$)/u', $cleanText, -1, PREG_SPLIT_NO_EMPTY);
        $sentences = array_values(array_filter(array_map('trim', $sentences), fn($s) => mb_strlen($s) > 0));
        $sentenceCount = count($sentences);

        $sentenceLengths = [];
        $sentenceBreakdown = [];

        foreach ($sentences as $idx => $s) {
            $sWords = preg_split('/\s+/u', trim($s), -1, PREG_SPLIT_NO_EMPTY);
            $len = count($sWords);
            if ($len > 0) {
                $sentenceLengths[] = $len;
                $sentenceBreakdown[] = [
                    'index' => $idx + 1,
                    'text' => $s,
                    'word_count' => $len,
                    'is_uniform' => false,
                ];
            }
        }

        $burstinessScore = 50.0;
        if ($sentenceCount > 1) {
            $avgLength = array_sum($sentenceLengths) / $sentenceCount;
            $varianceSum = 0;
            foreach ($sentenceLengths as $len) {
                $varianceSum += pow($len - $avgLength, 2);
            }
            $stdDev = sqrt($varianceSum / ($sentenceCount - 1));
            // Humans typically have stdDev >= 6-12 words across varied sentences
            $burstinessScore = min(100.0, max(0.0, round(($stdDev / max(1, $avgLength)) * 110, 1)));

            // Mark sentences that deviate little from average as uniform
            foreach ($sentenceBreakdown as &$sb) {
                if (abs($sb['word_count'] - $avgLength) < 3.0) {
                    $sb['is_uniform'] = true;
                }
            }
            unset($sb);
        }

        // 3. Contraction Ratio (Human Casual Marker)
        preg_match_all("/\b[a-z]+'(?:t|ll|re|ve|d|m|s)\b/i", $cleanText, $contractions);
        $contractionCount = count($contractions[0]);
        $contractionBonus = min(20.0, $contractionCount * 4.0);

        // 4. Cliché / Formulaic AI Transition Marker Density
        $foundFlags = [];
        $lowerText = mb_strtolower($cleanText);
        $totalMarkerHits = 0;

        foreach (self::FLAGGED_AI_MARKERS as $flag) {
            $pattern = '/\b' . preg_quote($flag, '/') . '\b/i';
            if (preg_match_all($pattern, $lowerText, $matches)) {
                $count = count($matches[0]);
                $totalMarkerHits += $count;
                $foundFlags[] = [
                    'word' => $flag,
                    'count' => $count,
                ];
            }
        }

        $markerDensityPer100 = ($totalMarkerHits / $totalWordsCount) * 100.0;

        // 5. Compute Base Statistical AI Probability
        // High AI signals: low burstiness (monotonous length), low perplexity (formulaic vocabulary), high transition density
        $heuristicAi = 0.0;

        // Burstiness penalty (AI tends to keep sentences between 15-25 words uniformly)
        if ($burstinessScore < 30.0) {
            $heuristicAi += 45.0;
        } elseif ($burstinessScore < 50.0) {
            $heuristicAi += 30.0;
        } elseif ($burstinessScore < 70.0) {
            $heuristicAi += 15.0;
        }

        // Perplexity penalty
        if ($perplexityScore < 45.0) {
            $heuristicAi += 35.0;
        } elseif ($perplexityScore < 60.0) {
            $heuristicAi += 20.0;
        }

        // Marker Density penalty
        if ($markerDensityPer100 >= 1.5) {
            $heuristicAi += 30.0;
        } elseif ($markerDensityPer100 >= 0.8) {
            $heuristicAi += 20.0;
        } elseif ($totalMarkerHits > 0) {
            $heuristicAi += 10.0;
        }

        // Human discount
        $heuristicAi -= $contractionBonus;
        $calculatedAi = min(100.0, max(0.0, round($heuristicAi, 1)));

        // 6. Fast LLM Classification Check (for high accuracy on nuanced / mixed texts)
        $finalAiProbability = $calculatedAi;
        if ($totalWordsCount >= 40 && $this->groqStreamingClient) {
            $llmProbability = $this->evaluateWithLlm($cleanText);
            if ($llmProbability !== null) {
                // Blend statistical heuristic (30%) with direct structural LLM analysis (70%)
                $finalAiProbability = round(($llmProbability * 0.70) + ($calculatedAi * 0.30), 1);
            }
        }

        $finalAiProbability = min(99.0, max(1.0, $finalAiProbability));

        // Risk Level Classification
        $riskLevel = 'Likely Human';
        if ($finalAiProbability >= 70.0) {
            $riskLevel = 'Highly Likely AI';
        } elseif ($finalAiProbability >= 35.0) {
            $riskLevel = 'Mixed / Paraphrased AI';
        }

        // Recommendations
        $recs = [];
        if ($burstinessScore < 45.0) {
            $recs[] = 'Sentence lengths are uniform. Mix very short sentences with longer compound ones to improve human cadence.';
        }
        if ($perplexityScore < 50.0) {
            $recs[] = 'Vocabulary structure is highly predictable. Vary phrasing and use specific contextual terminology.';
        }
        if (count($foundFlags) > 0) {
            $flagList = collect($foundFlags)->pluck('word')->take(5)->implode(', ');
            $recs[] = "Reduce formulaic AI transitions: {$flagList}.";
        }
        if ($finalAiProbability < 35.0) {
            $recs[] = 'Original text displays strong human pacing, natural irregularity, and organic phrasing.';
        } else {
            $recs[] = 'Consider humanizing the flagged paragraphs to eliminate mechanical flow.';
        }

        return [
            'aiProbability' => $finalAiProbability,
            'perplexity' => $perplexityScore,
            'burstiness' => $burstinessScore,
            'flaggedWords' => $foundFlags,
            'riskLevel' => $riskLevel,
            'recommendations' => $recs,
            'sentenceBreakdown' => $sentenceBreakdown,
        ];
    }

    /**
     * Fast LLM Structural Classifier for High Confidence AI Probability Estimation
     */
    private function evaluateWithLlm(string $text): ?float
    {
        try {
            $sampleExcerpt = mb_substr($text, 0, 1500);
            $prompt = [
                [
                    'role' => 'system',
                    'content' => "You are an expert AI detection classifier. Analyze the text for AI patterns (GPT/Claude/Llama style: predictable flow, generic transitions, uniform pacing, lack of idiosyncratic human errors). Output ONLY a single JSON object: {\"ai_percentage\": <number 0 to 100>}."
                ],
                [
                    'role' => 'user',
                    'content' => "Evaluate the AI probability of this text excerpt:\n\n" . $sampleExcerpt
                ]
            ];

            $result = $this->groqStreamingClient->streamChatCompletion(
                $prompt,
                function () {},
                config('services.groq.model', 'openai/gpt-oss-120b'),
                0.0
            );

            $content = $result['content'] ?? '';
            if (preg_match('/"ai_percentage"\s*:\s*([0-9]+(?:\.[0-9]+)?)/i', $content, $m)) {
                return floatval($m[1]);
            }
        } catch (Throwable $e) {
            Log::warning('LLM AI Detection evaluation fallback: ' . $e->getMessage());
        }

        return null;
    }
}
