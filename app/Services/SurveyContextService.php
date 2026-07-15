<?php

namespace App\Services;

use App\Models\Survey;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SurveyContextService
{
    public function __construct(
        private readonly DataAggregatorService $dataAggregator
    ) {
    }

    public function buildSnapshot(Survey $survey): array
    {
        $sampleLimit = max(1, (int) config('socius.context_sample_limit', 5));
        $pairLimit = max(1, (int) config('socius.crosstab_pair_limit', 3));
        $aggregated = $this->dataAggregator->aggregate($survey);
        $questionSummaries = collect($aggregated['questions'] ?? [])
            ->map(function (array $question) use ($sampleLimit) {
                return [
                    'id' => $question['id'] ?? null,
                    'label' => $question['label'] ?? 'Question',
                    'type' => $question['type'] ?? 'text',
                    'stats' => collect($question['stats'] ?? [])
                        ->sortByDesc('count')
                        ->take(10)
                        ->values()
                        ->all(),
                    'qualitative_samples' => collect($question['insights'] ?? [])
                        ->filter(fn($value) => filled($value))
                        ->map(fn($value) => Str::limit(trim((string) $value), 300))
                        ->take($sampleLimit)
                        ->values()
                        ->all(),
                ];
            })
            ->values();

        $crosstabVariables = $questionSummaries
            ->filter(fn(array $question) => $this->isChartableType($question['type'] ?? null))
            ->map(fn(array $question) => [
                'id' => $question['id'],
                'label' => $question['label'],
                'type' => $question['type'],
            ])
            ->values();

        return [
            'survey' => [
                'id' => $survey->id,
                'title' => $survey->title,
                'description' => $survey->description,
                'total_responses' => $aggregated['survey_info']['total_responses'] ?? 0,
                'category' => optional($survey->category)->value ?? (string) $survey->category,
                'status' => optional($survey->status)->value ?? (string) $survey->status,
            ],
            'questions' => $questionSummaries->all(),
            'crosstab_variables' => $crosstabVariables->all(),
            'crosstab_summaries' => $this->buildCrosstabSummaries($survey, $crosstabVariables, $pairLimit),
        ];
    }

    public function serializeForPrompt(Survey $survey): string
    {
        return json_encode(
            $this->buildSnapshot($survey),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: '{}';
    }

    private function buildCrosstabSummaries(Survey $survey, Collection $variables, int $pairLimit): array
    {
        $variablePairs = [];
        $values = $variables->values();

        for ($i = 0; $i < $values->count(); $i++) {
            for ($j = $i + 1; $j < $values->count(); $j++) {
                $variablePairs[] = [$values[$i], $values[$j]];
                if (count($variablePairs) >= $pairLimit) {
                    break 2;
                }
            }
        }

        if ($variablePairs === []) {
            return [];
        }

        $responses = $survey->responses()->with('answers')->get();
        $isJson = !empty($survey->json_schema) && $survey->json_schema !== '[]';
        $summaries = [];

        foreach ($variablePairs as [$row, $col]) {
            $matrix = [];

            foreach ($responses as $response) {
                $rowValue = $this->getAnswerValue($response, $row['id'], $isJson) ?? '[Missing]';
                $colValue = $this->getAnswerValue($response, $col['id'], $isJson) ?? '[Missing]';
                $matrix[$rowValue][$colValue] = ($matrix[$rowValue][$colValue] ?? 0) + 1;
            }

            $topCells = collect($matrix)
                ->flatMap(function (array $cols, string $rowValue) {
                    return collect($cols)->map(fn($count, $colValue) => [
                        'row_value' => $rowValue,
                        'col_value' => $colValue,
                        'count' => $count,
                    ]);
                })
                ->sortByDesc('count')
                ->take(5)
                ->values()
                ->all();

            $summaries[] = [
                'row_label' => $row['label'],
                'column_label' => $col['label'],
                'top_cells' => $topCells,
            ];
        }

        return $summaries;
    }

    private function getAnswerValue($response, $questionId, bool $isJson): ?string
    {
        if ($isJson) {
            $firstAnswer = $response->answers->first();
            $data = json_decode($firstAnswer ? $firstAnswer->value : '[]', true);

            $isVirtualLikert = str_contains($questionId, '___');
            $matchName = $isVirtualLikert ? explode('___', $questionId)[0] : $questionId;
            $rowKey = $isVirtualLikert ? explode('___', $questionId)[1] : null;

            foreach ((array) $data as $entry) {
                if (($entry['name'] ?? null) === $matchName) {
                    $value = $entry['userData'] ?? null;
                    if ($isVirtualLikert) {
                        $matrixAnswers = is_string($value) ? json_decode($value, true) : $value;
                        if (is_array($matrixAnswers)) {
                            if (isset($matrixAnswers[0])) {
                                if (is_string($matrixAnswers[0])) {
                                    $decoded = json_decode($matrixAnswers[0], true);
                                    if (is_array($decoded)) {
                                        $matrixAnswers = $decoded;
                                    }
                                } elseif (is_array($matrixAnswers[0])) {
                                    $matrixAnswers = $matrixAnswers[0];
                                }
                            }
                            $value = $matrixAnswers[$rowKey] ?? null;
                        } else {
                            $value = null;
                        }
                    }
                    return is_array($value) ? implode(', ', $value) : (filled($value) ? (string) $value : null);
                }
            }

            return null;
        }

        $answer = $response->answers->where('question_id', $questionId)->first();
        return $answer && filled($answer->value) ? (string) $answer->value : null;
    }

    private function isChartableType(?string $type): bool
    {
        return in_array($type, [
            'radio',
            'checkbox',
            'select',
            'number',
            'select_one',
            'select_many',
            'select-one',
            'select-multiple',
            'radio-group',
            'checkbox-group',
            'rating',
            'range',
            'ranking',
            'decimal',
            'starRating',
            'toggle',
        ], true);
    }
}
