<?php

namespace App\Jobs;

use App\Models\OrgSociusContext;
use App\Models\Survey;
use App\Services\AiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateOrgSociusContextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Survey $survey,
        public string $analysisText,
        public ?int $userId = null
    ) {
    }

    public function handle(AiService $ai): void
    {
        if (!$this->survey->organization_id) {
            return;
        }

        $summaryPrompt = "Summarize the key findings, methodology, sample size, and main conclusions of the following survey analysis in 300-500 words. This summary will serve as institutional memory for cross-survey AI intelligence queries. Survey Title: '{$this->survey->title}'.\n\nAnalysis Content:\n{$this->analysisText}";

        try {
            $summary = $ai->quickComplete($summaryPrompt);
        } catch (\Exception $e) {
            logger()->error("Failed generating org socius context summary for Survey ID {$this->survey->id}: " . $e->getMessage());
            return;
        }

        if (empty($summary)) {
            return;
        }

        OrgSociusContext::updateOrCreate(
            [
                'organization_id' => $this->survey->organization_id,
                'survey_id' => $this->survey->id,
            ],
            [
                'context_type' => 'survey_summary',
                'content' => "[Study: {$this->survey->title}]\n[Date: " . ($this->survey->created_at ? $this->survey->created_at->format('Y-m-d') : date('Y-m-d')) . "]\n\n{$summary}",
                'generated_at' => now(),
                'created_by' => $this->userId ?: $this->survey->created_by,
            ]
        );
    }
}
