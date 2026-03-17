<?php

namespace App\Services;

use App\Models\Survey;
use App\Models\Response;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Mpdf\Mpdf;

class AcademicSynthesisService
{
    protected $aiService;
    protected $referencingService;
    protected $dataAggregator;

    public function __construct(
        AiService $aiService, 
        AcademicReferencingService $referencingService,
        DataAggregatorService $dataAggregator
    ) {
        $this->aiService = $aiService;
        $this->referencingService = $referencingService;
        $this->dataAggregator = $dataAggregator;
    }

    /**
     * Generate a full report using an iterative pipeline: Outline -> Sections -> Final Summary.
     */
    public function generateIterativeReport(Survey $survey, string $style = 'apa7', array $manualReferences = [])
    {
        set_time_limit(300); // Allow up to 5 minutes for multi-stage synthesis
        $aggregatedData = $this->dataAggregator->aggregate($survey);
        $referencePrompt = $this->prepareReferencePrompt($manualReferences, $style);

        // Step 1: Generate Outline
        $outlinePrompt = "Create a logical academic report outline for a survey titled: '{$survey->title}'. " .
            "The report MUST be strictly based on the following aggregated data:\n" . json_encode($aggregatedData['questions']) . "\n\n" .
            "Provide the outline as a numbered list of sections (e.g., 1. Introduction, 2. Methodology, etc.). " .
            "IMPORTANT: DO NOT include word count percentages or estimated lengths in the section titles.";
        
        $systemPrompt = "You are a senior academic supervisor. Create a logical, standard academic structure for a research paper that directly addresses the survey findings.";
        $outline = $this->aiService->callGroq($outlinePrompt, $systemPrompt);

        // Parse outline into sections
        $sectionTitles = $this->parseOutline($outline);

        $reportContent = [];
        $currentYear = date('Y');
        foreach ($sectionTitles as $title) {
            $sectionPrompt = "Write the '{$title}' section of the academic report. " .
                "MANDATORY REQUIREMENT: Every paragraph MUST be grounded in the survey results provided below. " .
                "MANDATORY SELF-CITATION: When reporting findings, refer to this survey as '{$survey->title}' ({$currentYear}). " .
                "For example: 'Notably, the {$survey->title} ({$currentYear}) reveals that...' or 'According to the data gathered in {$currentYear}...' \n\n" .
                "SURVEY DATA:\n" . json_encode($aggregatedData['questions']) . "\n\n" .
                "AVAILABLE REFERENCES FOR CITATION:\n" . $referencePrompt;

            $sectionSystemPrompt = "You are a professional academic writer. Write formal, objective prose. " .
                "STRICT DATA ADHERENCE: You are only allowed to write about information found in the 'SURVEY DATA'. " .
                "AUTHORITATIVE ATTRIBUTION: Always attribute findings explicitly to the '{$survey->title}' survey conducted in {$currentYear}. " .
                "Maintain strict academic rigor and follow {$style} rules. DO NOT output JSON. Output ONLY the prose.";

            Log::info("Generating iterative section: {$title}");
            $content = $this->aiService->callGroq($sectionPrompt, $sectionSystemPrompt);
            $reportContent[$title] = $content ?? "[Generation failed]";
        }

        return [
            'outline' => $outline,
            'sections' => $reportContent,
            'metadata' => [
                'survey_id' => $survey->id,
                'style' => $style,
                'manual_references' => $manualReferences,
                'generated_at' => now()->toIso8601String()
            ]
        ];
    }

    /**
     * Simple parser to extract section titles from a numbered list outline.
     */
    private function parseOutline(string $outline)
    {
        $lines = explode("\n", $outline);
        $titles = [];
        foreach ($lines as $line) {
            if (preg_match('/^\d+\.\s*(.*)/', trim($line), $matches)) {
                $title = trim($matches[1]);
                $title = str_replace(['*', '_', '#'], '', $title);
                $titles[] = $title;
            }
        }
        
        // Fallback to defaults if parsing fails
        return !empty($titles) ? $titles : ['Introduction', 'Methodology', 'Results', 'Discussion', 'Conclusion'];
    }

    /**
     * Prepare a readable string of manual references for the AI.
     */
    private function prepareReferencePrompt(array $references, string $style)
    {
        if (empty($references)) {
            return "No manual references provided. Use general academic knowledge if needed, but do not hallucinate specific citations.";
        }

        $prompt = "Please use the following sources for in-text citations and bibliographical references where appropriate:\n";
        foreach ($references as $index => $ref) {
            $prompt .= "[" . ($index + 1) . "] Author: " . ($ref['author'] ?? 'Unknown') . 
                       " | Year: " . ($ref['year'] ?? 'n.d.') . 
                       " | Title: " . ($ref['title'] ?? 'Untitled') . 
                       " | Source: " . ($ref['source'] ?? 'N/A') . "\n";
        }
        $prompt .= "\nFollow {$style} formatting for all citations.";
        return $prompt;
    }

    /**
     * Prepare a condensed summary of survey data for the AI prompt.
     */
    private function prepareSurveyData(Survey $survey)
    {
        $responses = $survey->responses()->with('answers.question')->latest()->take(10)->get();
        if ($responses->isEmpty()) {
            return "No response data available.";
        }

        $dataDump = "";
        foreach ($responses as $response) {
            foreach ($response->answers as $answer) {
                if ($answer->question && in_array($answer->question->type, ['text', 'textarea', 'select', 'radio-group'])) {
                    $dataDump .= "Q: {$answer->question->label} | A: {$answer->value}\n";
                }
            }
        }

        return substr($dataDump, 0, 3000); // Limit to avoid prompt token overhead
    }

    /**
     * Export the generated report to DOCX.
     */
    public function exportToDocx(array $content, string $filename)
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        foreach ($content as $title => $text) {
            $section->addTitle($title, 1);
            $section->addText($text);
            $section->addTextBreak(1);
        }

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $path = storage_path('app/public/reports/' . $filename . '.docx');
        
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $objWriter->save($path);
        return $path;
    }

    /**
     * Export the generated report to PDF.
     */
    public function exportToPdf(array $content, string $filename)
    {
        $html = "<h1>Academic Research Report</h1>";
        foreach ($content as $title => $text) {
            $html .= "<h2>{$title}</h2>";
            $html .= "<p>" . nl2br(e($text)) . "</p>";
        }

        $mpdf = new Mpdf();
        $mpdf->WriteHTML($html);
        
        $path = storage_path('app/public/reports/' . $filename . '.pdf');
        
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $mpdf->Output($path, \Mpdf\Output\Destination::FILE);
        return $path;
    }
}
