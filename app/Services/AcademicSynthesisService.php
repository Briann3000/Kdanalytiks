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

    public function __construct(AiService $aiService, AcademicReferencingService $referencingService)
    {
        $this->aiService = $aiService;
        $this->referencingService = $referencingService;
    }

    /**
     * Generate a multi-section academic report based on survey data.
     */
    public function generateFullReport(Survey $survey, string $style = 'apa7', array $manualReferences = [])
    {
        $sections = [
            'Abstract' => "Provide a 250-word formal abstract for a research paper based on this survey data. Enforce a professional, objective tone.",
            'Introduction' => "Write a formal introduction that sets the stage for the research objectives. Use the passive voice where appropriate for academic formality.",
            'Methodology' => "Describe the methodology used in this survey, including data collection and tools. Ensure descriptions are clinical and precise.",
            'Results' => "Analyze the quantitative and qualitative results. Focus on key trends and statistical highlights without using first-person pronouns.",
            'Discussion' => "Discuss the implications of the results in the context of the initial objectives. Critically evaluate findings using formal scholarly language.",
            'Conclusion' => "Provide a final conclusion and recommendations for future research. Summarize the scholarly contribution of this study."
        ];

        $reportContent = [];
        $surveyData = $this->prepareSurveyData($survey);
        $referencePrompt = $this->prepareReferencePrompt($manualReferences, $style);

        foreach ($sections as $title => $instruction) {
            $systemPrompt = "You are a professional academic writer specialized in {$style} formatting. " .
                "Maintain a strictly formal, objective, and scholarly tone. Use the passive voice for methodological descriptions and avoid first-person pronouns (no 'I', 'we', 'my'). " .
                "IMPORTANT: Output ONLY the formal academic text for the section. DO NOT output JSON, DO NOT echo back the survey context data, and DO NOT explain your reasoning. Just provide the section prose. " .
                "If citations are required, use the provided reference list strictly following {$style} rules. " .
                "Current Section to write: {$title}. Instruction: {$instruction}";

            $userPrompt = "SURVEY CONTEXT:\nTitle: {$survey->title}\nDescription: {$survey->description}\n\n" .
                "DATA SUMMARY:\n" . $surveyData . "\n\n" .
                "AVAILABLE REFERENCES FOR CITATION:\n" . $referencePrompt;

            Log::info("Generating section: {$title} for survey: {$survey->id}");
            $sectionContent = $this->aiService->callGroq($userPrompt, $systemPrompt);

            if ($sectionContent) {
                $reportContent[$title] = $sectionContent;
            } else {
                $reportContent[$title] = "[AI failed to generate this section]";
            }
        }

        return $reportContent;
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
