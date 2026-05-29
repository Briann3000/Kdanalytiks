<?php

namespace App\Services;

use App\Models\Response;
use App\Models\Survey;
use Illuminate\Http\Request;

class ResponseQualityService
{
    /**
     * Run all checks on a response, calculate score, populate flags, and save.
     */
    public function analyze(Response $response, Request $request): Response
    {
        $survey = $response->survey;
        $score = 100;
        $flags = [];

        // Save IP and device fingerprint
        $response->ip_address = $request->ip();
        $response->device_fingerprint = $request->input('device_fingerprint');
        $response->completion_time_seconds = $request->input('completion_time_seconds');

        // Extract answers
        $answers = $this->extractAnswers($response);

        // 1. Speed Check
        $speedFlag = $this->checkSpeed($response, $survey);
        if ($speedFlag) {
            $score -= 65; // Severe: drop below 40
            $flags[] = $speedFlag;
        }

        // 2. Duplicate Check
        $duplicateFlag = $this->checkDuplicates($response);
        if ($duplicateFlag) {
            $score -= 65; // Severe: drop below 40
            $flags[] = $duplicateFlag;
        }

        // 3. Straight-Lining Check
        $straightLiningFlag = $this->checkStraightLining($answers, $survey);
        if ($straightLiningFlag) {
            $score -= 65; // Severe: drop below 40
            $flags[] = $straightLiningFlag;
        }

        // 4. Text Quality Check
        $textFlags = $this->checkTextQuality($answers, $survey);
        if (!empty($textFlags)) {
            foreach ($textFlags as $flag) {
                if (str_contains($flag['message'], 'repeated') || str_contains($flag['message'], 'mashing')) {
                    $score -= 65; // Severe gibberish: drop below 40
                } else {
                    $score -= 35; // Minor: drop to 65 (Review)
                }
                $flags[] = $flag;
            }
        }

        // Finalize score and status
        $response->quality_score = max(0, $score);
        $response->quality_flags = $flags;
        $response->is_flagged = $response->quality_score < 40;
        $response->save();

        return $response;
    }

    /**
     * Extract answers into an associative array of [question_name => answer_value]
     */
    private function extractAnswers(Response $response): array
    {
        $answers = [];

        // Check if there is a JSON submission (first answer value is JSON array)
        $firstAnswer = $response->answers()->whereNull('question_id')->first();
        if ($firstAnswer) {
            $data = json_decode($firstAnswer->value, true) ?? [];
            foreach ($data as $item) {
                if (isset($item['name']) && isset($item['userData'])) {
                    $answers[$item['name']] = $item['userData'];
                }
            }
        } else {
            // Legacy submission
            foreach ($response->answers()->with('question')->get() as $a) {
                $name = $a->question->name ?? 'question_' . $a->question_id;
                $answers[$name] = $a->value;
            }
        }

        return $answers;
    }

    /**
     * Run the speed/completion time check.
     */
    private function checkSpeed(Response $response, Survey $survey): ?array
    {
        $time = $response->completion_time_seconds;
        if (is_null($time) || $time <= 0) {
            return null;
        }

        // Calculate median of past responses
        $times = Response::where('survey_id', $survey->id)
            ->where('id', '!=', $response->id)
            ->whereNotNull('completion_time_seconds')
            ->pluck('completion_time_seconds');

        if ($times->count() >= 5) {
            $median = $times->median();
            $threshold = $median * 0.20; // Flag if completed in under 20% of median time
            if ($time < $threshold) {
                return [
                    'type' => 'speed',
                    'message' => sprintf('Completed too quickly (%d seconds vs median %d seconds)', $time, $median)
                ];
            }
        } else {
            // Heuristic if insufficient history: 10 seconds per question
            $schema = json_decode($survey->json_schema, true) ?? [];
            $qCount = is_array($schema) ? count($schema) : $survey->questions()->count();
            $qCount = max(1, $qCount);

            $threshold = $qCount * 8; // 8 seconds per question threshold
            if ($time < $threshold) {
                return [
                    'type' => 'speed',
                    'message' => sprintf('Completed too quickly (%d seconds vs heuristic threshold of %d seconds)', $time, $threshold)
                ];
            }
        }

        return null;
    }

    /**
     * Run duplicate checking.
     */
    private function checkDuplicates(Response $response): ?array
    {
        // 1. Check logged-in user
        if ($response->respondent_id) {
            $duplicateUser = Response::where('survey_id', $response->survey_id)
                ->where('id', '!=', $response->id)
                ->where('respondent_id', $response->respondent_id)
                ->exists();
            if ($duplicateUser) {
                return [
                    'type' => 'duplicate',
                    'message' => 'Duplicate submission by same authenticated user'
                ];
            }
        }

        // 2. Check IP + Fingerprint combination
        if ($response->ip_address && $response->device_fingerprint) {
            $duplicateIpFingerprint = Response::where('survey_id', $response->survey_id)
                ->where('id', '!=', $response->id)
                ->where('ip_address', $response->ip_address)
                ->where('device_fingerprint', $response->device_fingerprint)
                ->exists();
            if ($duplicateIpFingerprint) {
                return [
                    'type' => 'duplicate',
                    'message' => 'Duplicate IP address and device fingerprint'
                ];
            }
        }

        return null;
    }

    /**
     * Run straight-lining detection (answering same option for multiple consecutive questions).
     */
    private function checkStraightLining(array $answers, Survey $survey): ?array
    {
        $schema = json_decode($survey->json_schema, true) ?? [];

        // Find choice question names
        $choiceQuestionNames = [];
        foreach ($schema as $q) {
            if (isset($q['type']) && in_array($q['type'], ['select_one', 'select_many', 'select', 'rating', 'radio', 'checkbox'])) {
                if (isset($q['name'])) {
                    $choiceQuestionNames[] = $q['name'];
                }
            }
        }

        // Fallback for legacy
        if (empty($choiceQuestionNames)) {
            $choiceQuestionNames = $survey->questions()
                ->whereIn('type', ['radio', 'checkbox', 'select', 'rating'])
                ->pluck('name')
                ->toArray();
        }

        if (count($choiceQuestionNames) < 3) {
            return null; // Not enough choice questions to determine straight-lining
        }

        $valuesSelected = [];
        foreach ($choiceQuestionNames as $name) {
            if (isset($answers[$name]) && !is_array($answers[$name]) && $answers[$name] !== '') {
                $valuesSelected[] = $answers[$name];
            }
        }

        if (count($valuesSelected) < 3) {
            return null;
        }

        // If all selected values are identical
        if (count(array_unique($valuesSelected)) === 1) {
            return [
                'type' => 'straight_lining',
                'message' => sprintf('Straight-lined response: selected same option "%s" for all %d choice questions', $valuesSelected[0], count($valuesSelected))
            ];
        }

        return null;
    }

    /**
     * Run low-effort text detection.
     */
    private function checkTextQuality(array $answers, Survey $survey): array
    {
        $flags = [];

        // Parse text-friendly question names from schema
        $textQuestionNames = [];
        $schema = json_decode($survey->json_schema, true) ?? [];
        foreach ($schema as $q) {
            if (isset($q['type']) && in_array($q['type'], ['text', 'textarea'])) {
                if (isset($q['name'])) {
                    $textQuestionNames[$q['name']] = $q['label'] ?? $q['name'];
                }
            }
        }

        // Fallback for legacy
        if (empty($textQuestionNames)) {
            $questions = $survey->questions()->whereIn('type', ['text', 'textarea'])->get();
            foreach ($questions as $q) {
                $name = $q->name ?? 'question_' . $q->id;
                $textQuestionNames[$name] = $q->label ?? $name;
            }
        }

        foreach ($textQuestionNames as $name => $label) {
            if (isset($answers[$name]) && is_string($answers[$name]) && trim($answers[$name]) !== '') {
                $text = trim($answers[$name]);

                // 1. Repeated consecutive character mashing (e.g. "aaaaaa" or "asdfasdfasdfasdf")
                if (preg_match('/(.)\1{4,}/', $text)) {
                    $flags[] = [
                        'type' => 'text_quality',
                        'message' => "Suspicious repeated characters in question '{$label}'"
                    ];
                    continue;
                }

                // 2. High consonant mashing (gibberish keyboard mashing like "vbnmghj" or "qwerty")
                if (preg_match('/[bcdfghjklmnpqrstvwxyz]{6,}/i', $text)) {
                    $flags[] = [
                        'type' => 'text_quality',
                        'message' => "Potential keyboard mashing (gibberish) in question '{$label}'"
                    ];
                    continue;
                }

                // 3. Short low-effort responses when comment/feedback is requested
                $isElaborative = preg_match('/(feedback|comment|suggest|opinion|describe|explain|why)/i', $label);
                if ($isElaborative) {
                    $wordCount = str_word_count($text);
                    if ($wordCount > 0 && $wordCount < 3) {
                        $flags[] = [
                            'type' => 'text_quality',
                            'message' => "Low-effort/too short response in question '{$label}'"
                        ];
                    }
                }
            }
        }

        return $flags;
    }
}
