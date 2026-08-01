<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AiQuantInsightCard extends Component
{
    public $questionId;
    public $surveyId;
    public $stats;
    public $insight;
    public $error;

    /**
     * Create a new component instance.
     */
    public function __construct($questionId, $surveyId, $stats)
    {
        $this->questionId = $questionId;
        $this->surveyId = $surveyId;
        $this->stats = $stats;
        $this->generateInsight();
    }

    protected function generateInsight()
    {
        try {
            $survey = \App\Models\Survey::find($this->surveyId);
            $style = $survey ? ($survey->reporting_style ?? 'apa') : 'apa';

            $cacheKeyStyle = "quantitative_analysis_{$this->surveyId}_{$this->questionId}_{$style}";
            $cacheKeyDefault = "quantitative_analysis_{$this->surveyId}_{$this->questionId}";

            $aiService = app(\App\Services\QualitativeAnalysisService::class);

            $this->insight = \Illuminate\Support\Facades\Cache::remember($cacheKeyStyle, 86400, function () use ($aiService, $style) {
                $cacheKeyDefault = "quantitative_analysis_{$this->surveyId}_{$this->questionId}";
                if (\Illuminate\Support\Facades\Cache::has($cacheKeyDefault)) {
                    return \Illuminate\Support\Facades\Cache::get($cacheKeyDefault);
                }
                return $aiService->analyzeQuantitativeData($this->stats, $this->questionId, $style);
            });

            if (!\Illuminate\Support\Facades\Cache::has($cacheKeyDefault)) {
                \Illuminate\Support\Facades\Cache::put($cacheKeyDefault, $this->insight, 86400);
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ai-quant-insight-card');
    }
}
