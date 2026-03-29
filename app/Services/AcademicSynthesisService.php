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
     * Generate a full academic research report using an OPTIMIZED batch pipeline.
     */
    public function generateIterativeReport(Survey $survey, string $style = 'apa7', array $manualReferences = [])
    {
        set_time_limit(600); 
        $aggregatedData = $this->dataAggregator->aggregate($survey);
        $referencePrompt = $this->prepareReferencePrompt($manualReferences, $style);
        $currentYear = date('Y');
        $totalResponses = $aggregatedData['survey_info']['total_responses'] ?? 0;
        $dataSummary = $this->buildDataSummary($aggregatedData);

        $sections = [];

        // ── 1. STATIC PRELIMINARY PAGES ──
        $sections['Title Page'] = $this->generateTitlePage($survey, $currentYear, $style);
        $sections['Declaration'] = $this->generateDeclaration($survey, $currentYear);
        $sections['Acknowledgement'] = $this->generateAcknowledgement($survey);

        // ── 2. BATCH CALL: PRELIMINARY AI SECTIONS (Abstract, Abbreviations, Terms) ──
        sleep(1); // Rate limit buffer
        $prelimPrompt = "Write the following preliminary sections for a research report titled '{$survey->title}'.\n" .
            "Use exact markers [SECTION: Name] before each section.\n\n" .
            "[SECTION: Abstract] - Write a 250-word academic abstract.\n" .
            "[SECTION: Abbreviations] - List 10 standard research abbreviations.\n" .
            "[SECTION: Definition of Key Terms] - Define 5-8 key academic terms relevant to this topic.\n\n" .
            "SURVEY DATA CONTEXT:\n{$dataSummary}";
        $this->batchProcess($prelimPrompt, $sections, $survey, $style, $currentYear);

        // ── 3. BATCH CALL: CHAPTER 1 (INTRODUCTION) ──
        sleep(2); // Rate limit buffer
        $ch1Prompt = "Write CHAPTER 1: INTRODUCTION for the research report '{$survey->title}'.\n" .
            "Use markers [SECTION: Name] for each sub-section:\n" .
            "[SECTION: 1.1 Background of the Study]\n" .
            "[SECTION: 1.2 Statement of the Problem]\n" .
            "[SECTION: 1.3 Objectives of the Study]\n" .
            "[SECTION: 1.4 Research Questions]\n" .
            "[SECTION: 1.5 Significance of the Study]\n" .
            "[SECTION: 1.6 Scope and Limitations]\n\n" .
            "Respondents: {$totalResponses}. Description: {$survey->description}.";
        $sections['CHAPTER 1: INTRODUCTION'] = '__chapter_header__';
        $this->batchProcess($ch1Prompt, $sections, $survey, $style, $currentYear);

        // ── 4. BATCH CALL: CHAPTER 2 (LITERATURE REVIEW) ──
        sleep(2); // Rate limit buffer
        $ch2Prompt = "Write CHAPTER 2: LITERATURE REVIEW for '{$survey->title}'.\n" .
            "Use markers:\n" .
            "[SECTION: 2.0 Introduction]\n" .
            "[SECTION: 2.1 Theoretical Framework]\n" .
            "[SECTION: 2.2 Conceptual Framework]\n" .
            "[SECTION: 2.3 Empirical Review]\n" .
            "[SECTION: 2.4 Research Gaps]\n" .
            "[SECTION: 2.5 Summary]\n\n" .
            "REFERENCES TO USE:\n{$referencePrompt}";
        $sections['CHAPTER 2: LITERATURE REVIEW'] = '__chapter_header__';
        $this->batchProcess($ch2Prompt, $sections, $survey, $style, $currentYear);

        // ── 5. BATCH CALL: CHAPTER 3 (METHODOLOGY) ──
        sleep(2); // Rate limit buffer
        $ch3Prompt = "Write CHAPTER 3: RESEARCH METHODOLOGY for '{$survey->title}'.\n" .
            "Use markers:\n" .
            "[SECTION: 3.1 Research Design]\n" .
            "[SECTION: 3.2 Target Population]\n" .
            "[SECTION: 3.3 Sample Size and Sampling Techniques]\n" .
            "[SECTION: 3.4 Data Collection Instruments]\n" .
            "[SECTION: 3.5 Data Collection Procedures]\n" .
            "[SECTION: 3.6 Validity and Reliability]\n" .
            "[SECTION: 3.7 Data Analysis and Presentation]\n\n" .
            "Respondents: {$totalResponses}. Methodology: Survey-based.";
        $sections['CHAPTER 3: RESEARCH METHODOLOGY'] = '__chapter_header__';
        $this->batchProcess($ch3Prompt, $sections, $survey, $style, $currentYear);

        // ── 6. CHAPTER 4: RESULTS AND DISCUSSION (Data Heavy - Segmented) ──
        $sections['CHAPTER 4: RESULTS AND DISCUSSION'] = '__chapter_header__';
        $sections['4.0 Introduction'] = "This chapter presents the analysis and interpretation of data collected from {$totalResponses} respondents for the survey '{$survey->title}'.";
        
        // We still iterate questions to ensure tables are embedded correctly
        $questionGroups = array_chunk($aggregatedData['questions'], 5); // Process in groups of 5 to stay fast
        $qIdx = 1;
        foreach ($questionGroups as $group) {
            $groupContext = "";
            foreach ($group as $q) {
                $groupContext .= "Q" . ($qIdx++) . ": {$q['label']}\nData: " . json_encode($q['stats'] ?? $q['insights']) . "\n\n";
            }
            
            $qPrompt = "Write an academic discussion for the following 5 survey findings. " .
                "Use the marker [SECTION: 4.X Title] for each. " .
                "Mention specific percentages and counts in your prose.\n\nFINDINGS:\n{$groupContext}";
            
            $this->batchProcess($qPrompt, $sections, $survey, $style, $currentYear);
        }

        // Re-inject tables into Chapter 4 sections (since AI generates prose, we append the HTML tables)
        foreach ($aggregatedData['questions'] as $idx => $q) {
            $foundKey = null;
            $search = "4." . ($idx + 1);
            foreach (array_keys($sections) as $key) {
                if (str_starts_with($key, $search)) {
                    $foundKey = $key;
                    break;
                }
            }
            if ($foundKey && !empty($q['stats'])) {
                $sections[$foundKey] .= "\n\n" . $this->buildSingleQuestionTableHtml($q['label'], $q['stats'], $totalResponses);
            }
        }

        // ── 7. BATCH CALL: CHAPTER 5 ──
        sleep(3); // Extra buffer for the final chapter call
        Log::info('Starting Chapter 5 generation...');
        $ch5Prompt = "Write CHAPTER 5: SUMMARY, CONCLUSIONS AND RECOMMENDATIONS for '{$survey->title}'.\n" .
            "Use markers:\n" .
            "[SECTION: 5.1 Summary of Findings]\n" .
            "[SECTION: 5.2 Conclusions]\n" .
            "[SECTION: 5.3 Recommendations]\n\n" .
            "DATA SUMMARY:\n{$dataSummary}";
        $sections['CHAPTER 5: SUMMARY, CONCLUSIONS AND RECOMMENDATIONS'] = '__chapter_header__';
        $this->batchProcess($ch5Prompt, $sections, $survey, $style, $currentYear);

        // ── 8. BATCH CALL: REFERENCES ──
        sleep(2); // Rate limit buffer
        $refPrompt = "Generate the REFERENCES section for '{$survey->title}'.\n" .
            "Use marker: [SECTION: REFERENCES]\n" .
            "List 10-15 academic sources strictly in {$style} style.\n\n" .
            "USER REFERENCES:\n{$referencePrompt}";
        $this->batchProcess($refPrompt, $sections, $survey, $style, $currentYear);

        // ── 9. APPENDICES ──
        $sections['APPENDICES'] = $this->generateAppendices($survey, $aggregatedData);

        return [
            'outline' => "Generated Academic Structure",
            'sections' => $sections,
            'metadata' => [
                'survey_id' => $survey->id,
                'style' => $style,
                'manual_references' => $manualReferences,
                'generated_at' => now()->toIso8601String(),
                'total_responses' => $totalResponses,
            ]
        ];
    }

    /**
     * Process a batch of sections from a single AI call.
     */
    private function batchProcess($prompt, &$sections, $survey, $style, $currentYear)
    {
        $systemPrompt = "You are a senior academic director. Write formal, objective research prose. " .
            "CRITICAL: You MUST use the marker [SECTION: Name] before every new section you write. " .
            "Gound every claim in the survey findings. Attribute data to '{$survey->title}' ({$currentYear}). " .
            "Citation style: {$style}. Output only the marked sections.";

        Log::info("Executing Batch AI Call for prompt: " . substr($prompt, 0, 100) . "...");
        $response = $this->aiService->callGroq($prompt, $systemPrompt);

        if ($response) {
            // More resilient regex: handles optional spaces and case sensitivity
            $parts = preg_split('/\[SECTION:\s*([^\]]+)\]/i', $response, -1, PREG_SPLIT_DELIM_CAPTURE);
            
            for ($i = 1; $i < count($parts); $i += 2) {
                $title = trim($parts[$i]);
                $content = trim($parts[$i + 1] ?? '');
                if ($title && $content) {
                    $sections[$title] = $content;
                }
            }
        } else {
            // Improved failover: List exactly which sections were expected
            preg_match_all('/\[SECTION:\s*([^\]]+)\]/i', $prompt, $matches);
            foreach ($matches[1] as $expectedTitle) {
                $trimmedTitle = trim($expectedTitle);
                if (!isset($sections[$trimmedTitle])) {
                    $sections[$trimmedTitle] = "[Generation timed out. Please try regenerating this specific section.]";
                }
            }
        }
    }

    /**
     * Static helper to build a data summary for prompts.
     */
    private function buildDataSummary($aggregatedData)
    {
        $summary = "";
        foreach ($aggregatedData['questions'] as $q) {
            $summary .= "Q: {$q['label']} ({$q['type']})\n";
            if (!empty($q['stats'])) {
                foreach (array_slice($q['stats'], 0, 5) as $s) {
                    $summary .= "  - {$s['option']}: {$s['percentage']}%\n";
                }
            }
        }
        return substr($summary, 0, 2000);
    }

    /**
     * Helper to build HTML table for data.
     */
    private function buildSingleQuestionTableHtml($label, $stats, $totalResponses)
    {
        $table = "<div class='data-table' style='margin: 20px 0;'>";
        $table .= "<table style='width:100%; border-collapse:collapse; font-size:12px; border:1px solid #e5e7eb;'>";
        $table .= "<caption style='font-weight:bold; margin-bottom:5px; text-align:left;'>Table: " . e($label) . " (N={$totalResponses})</caption>";
        $table .= "<tr style='background:#f9fafb;'><th style='padding:8px; border:1px solid #e5e7eb; text-align:left;'>Response</th><th style='padding:8px; border:1px solid #e5e7eb;'>f</th><th style='padding:8px; border:1px solid #e5e7eb;'>%</th></tr>";
        foreach ($stats as $s) {
            $table .= "<tr><td style='padding:8px; border:1px solid #e5e7eb;'>" . e($s['option']) . "</td><td style='padding:8px; border:1px solid #e5e7eb; text-align:center;'>{$s['count']}</td><td style='padding:8px; border:1px solid #e5e7eb; text-align:center;'>{$s['percentage']}%</td></tr>";
        }
        $table .= "</table></div>";
        return $table;
    }

    // ... (Keep existing Title Page, Declaration, Acknowledgement, Appendices, Export methods from previous version) ...
    // Note: I will merge the rest of the existing methods below.

    private function generateTitlePage($survey, $year, $style) {
        $user = auth()->user();
        $name = $user ? $user->name : 'Researcher';
        return "<div class='title-page' style='text-align:center; padding-top:100px;'><h1>" . strtoupper($survey->title) . "</h1><p>A Research Report</p><br><p>By</p><h3>{$name}</h3><br><p>Style: " . strtoupper($style) . "</p><p>{$year}</p></div>";
    }

    private function generateDeclaration($survey, $year) {
        return "I declare that this report is my original work based on survey data '{$survey->title}' collected in {$year}.";
    }

    private function generateAcknowledgement($survey) {
        return "I acknowledge the contributions of all respondents to the '{$survey->title}' survey.";
    }

    private function generateAppendices($survey, $aggregatedData) {
        $app = "Appendix A: Questionnaire\n\n";
        foreach ($aggregatedData['questions'] as $idx => $q) {
            $app .= ($idx+1) . ". " . $q['label'] . "\n";
        }
        return $app;
    }

    private function prepareReferencePrompt(array $references, string $style) {
        $p = "";
        foreach ($references as $r) {
            $p .= "Author: {$r['author']}, Title: {$r['title']}, Year: {$r['year']}\n";
        }
        return $p ?: "General academic knowledge.";
    }

    public function exportToDocx(array $content, string $filename) {
        $phpWord = new PhpWord(); $section = $phpWord->addSection();
        foreach ($content as $title => $text) {
            if ($text === '__chapter_header__') { $section->addTitle($title, 1); continue; }
            $section->addTitle($title, 2); $section->addText($text); $section->addTextBreak(1);
        }
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $path = storage_path('app/public/reports/'.$filename.'.docx'); $writer->save($path);
        return $path;
    }

    public function exportToPdf(array $content, string $filename) {
        $html = "";
        foreach ($content as $title => $text) {
            if ($text === '__chapter_header__') { $html .= "<h1 style='page-break-before:always; text-align:center;'>{$title}</h1>"; continue; }
            if (str_contains($text, 'title-page')) { $html .= $text; continue; }
            $html .= "<h2>{$title}</h2><div>".nl2br(e($text))."</div>";
        }
        $mpdf = new Mpdf(); $mpdf->WriteHTML($html);
        $path = storage_path('app/public/reports/'.$filename.'.pdf');
        $mpdf->Output($path, \Mpdf\Output\Destination::FILE);
        return $path;
    }
}
