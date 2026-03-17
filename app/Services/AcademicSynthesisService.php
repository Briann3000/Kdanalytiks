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
    public function generateFullReport(Survey $survey, string $style = 'apa7')
    {
        $sections = [
            'Abstract' => "Provide a 250-word formal abstract for a research paper based on this survey data.",
            'Introduction' => "Write a formal introduction that sets the stage for the research objectives.",
            'Methodology' => "Describe the methodology used in this survey, including data collection and tools.",
            'Results' => "Analyze the quantitative and qualitative results. Focus on key trends and statistical highlights.",
            'Discussion' => "Discuss the implications of the results in the context of the initial objectives.",
            'Conclusion' => "Provide a final conclusion and recommendations for future research."
        ];

        $reportContent = [];
        $surveyData = $this->prepareSurveyData($survey);

        foreach ($sections as $title => $instruction) {
            $prompt = $instruction . "\n\nSURVEY CONTEXT:\nTitle: {$survey->title}\nDescription: {$survey->description}\n\nDATA SUMMARY:\n" . $surveyData;
            
            Log::info("Generating section: {$title} for survey: {$survey->id}");
            $sectionContent = $this->aiService->callGroq($prompt);
            
            if ($sectionContent) {
                $reportContent[$title] = $sectionContent;
            } else {
                $reportContent[$title] = "[AI failed to generate this section]";
            }
        }

        return $reportContent;
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
