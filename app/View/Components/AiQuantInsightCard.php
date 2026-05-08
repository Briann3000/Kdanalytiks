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
            $aiService = new \App\Services\QualitativeAnalysisService();
            // We'll add a new method to the service for quantitative interpretation
            $this->insight = $aiService->analyzeQuantitativeData($this->stats);
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
