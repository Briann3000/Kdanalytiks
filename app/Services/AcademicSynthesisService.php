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
    public function generateIterativeReport(Survey $survey, string $style = 'apa7', array $manualReferences = [], array $branding = [])
    {
        set_time_limit(600);
        $aggregatedData = $this->dataAggregator->aggregate($survey);
        $referencePrompt = $this->prepareReferencePrompt($manualReferences, $style);
        $currentYear = date('Y');
        $totalResponses = $aggregatedData['survey_info']['total_responses'] ?? 0;
        $dataSummary = $this->buildDataSummary($aggregatedData);
        $locale = \App::getLocale();

        $sections = [];
        $sections['Title Page'] = $this->generateTitlePage($survey, $currentYear, $style);
        $sections['Declaration'] = $this->generateDeclaration($survey, $currentYear);
        $sections['Acknowledgement'] = $this->generateAcknowledgement($survey);

        // ── DEFINE ALL PROMPTS ──
        $prelimPrompt = "Write Abstract, Abbreviations, and Definition of Key Terms for '{$survey->title}'. Use [SECTION: Name] markers.\nDATA:\n{$dataSummary}";
        $ch1Prompt = "Write Chapter 1: Introduction for '{$survey->title}'. Use markers: [SECTION: 1.1 Background of the Study], [SECTION: 1.2 Statement of the Problem], [SECTION: 1.3 Research Objectives / Questions], [SECTION: 1.4 Significance of the Study], [SECTION: 1.5 Scope and Limitations].";
        $ch2Prompt = "Write Chapter 2: Literature Review for '{$survey->title}'. Use markers: [SECTION: 2.1 Introduction], [SECTION: 2.2 Theoretical Framework], [SECTION: 2.3 Conceptual Framework], [SECTION: 2.4 Empirical Review], [SECTION: 2.5 Research Gaps], [SECTION: 2.6 Summary]. References:\n{$referencePrompt}";
        $ch3Prompt = "Write Chapter 3: Research Methodology for '{$survey->title}'. Use markers: [SECTION: 3.1 Research Design], [SECTION: 3.2 Target Population], [SECTION: 3.3 Sample Size and Sampling Techniques], [SECTION: 3.4 Data Collection Instruments], [SECTION: 3.5 Data Collection Procedures], [SECTION: 3.6 Validity and Reliability], [SECTION: 3.7 Data Analysis and Presentation].";

        $groupContext = "";
        foreach (array_slice($aggregatedData['questions'], 0, 20) as $idx => $q) {
            $dataToUse = !empty($q['stats']) ? $q['stats'] : $q['insights'];
            $groupContext .= "Q" . ($idx + 1) . ": " . ($q['label'] ?? 'Question') . "\nData: " . json_encode($dataToUse) . "\n\n";
        }
        $qPrompt = "Write Chapter 4: Results and Discussion for '{$survey->title}'.\n" .
            "Total respondents: {$totalResponses}.\n" .
            "Use markers: [SECTION: 4.1 Introduction], [SECTION: 4.2 Response Rate], [SECTION: 4.3 Respondent Demographics], [SECTION: 4.4 Data Analysis and Presentation], [SECTION: 4.5 Discussion of Findings (Research Objectives / Questions)], [SECTION: 4.6 Summary].\n" .
            "CRITICAL RULES:\n" .
            "- ALL percentages, frequencies, and figures you mention MUST come EXACTLY from the DATA below. DO NOT invent or estimate any numbers.\n" .
            "- If data for a question is empty ([]), write 'No quantitative data was collected for this question' and provide only a qualitative summary.\n" .
            "GUIDELINES for 4.4:\n" .
            "- For each survey question, provide a sub-heading: 4.4.1 [Question Text], 4.4.2 [Question Text], etc.\n" .
            "- Present the factual findings first (using EXACT figures from the data), then provide a brief interpretation of what the data means for that question.\n" .
            "- DO NOT use asterisks (***) or (**) for sub-headings. Write them as plain text on their own line.\n" .
            "GUIDELINES for 4.5:\n" .
            "- ONLY refer to the Research Objectives / Questions generated in Chapter 1.\n" .
            "- For each objective/question, discuss how the collected survey data addresses it, citing specific findings from 4.4.\n" .
            "- DO NOT append '(linked to objectives)' to any headings in 4.5.\n" .
            "DATA:\n{$groupContext}";

        $ch5RefPrompt = "Write Chapter 5: Conclusions and Recommendations for '{$survey->title}'.\n" .
            "Use markers: [SECTION: 5.1 Summary of Findings], [SECTION: 5.2 Conclusions], [SECTION: 5.3 Limitations of the Study], [SECTION: 5.4 Recommendations]\n" .
            "CRITICAL CONSTRAINTS:\n" .
            "- Do NOT write or generate a [SECTION: REFERENCES] or Bibliography block at the end.\n" .
            "- All citations within the text MUST use standard parenthetical style (e.g., Author, Year).\n" .
            "- Keep the tone formal, academic, and concise.\n\n" .
            "DATA:\n{$dataSummary}";

        // ── EXECUTE TURBO PARALLEL WAVES ──
        $wave1 = [
            'prelim' => $prelimPrompt,
            'ch1' => $ch1Prompt,
            'ch2' => $ch2Prompt
        ];
        $results1 = $this->processWave($wave1, $survey, $style, $currentYear);

        $wave2 = [
            'ch3' => $ch3Prompt,
            'ch4' => $qPrompt,
            'ch5ref' => $ch5RefPrompt
        ];
        $results2 = $this->processWave($wave2, $survey, $style, $currentYear);

        // ── MERGE SEQUENTIALLY ──
        $sections = array_merge($sections, $results1['prelim'] ?? []);
        $sections = array_merge($sections, $results1['ch1'] ?? []);
        $sections = array_merge($sections, $results1['ch2'] ?? []);
        $sections = array_merge($sections, $results2['ch3'] ?? []);
        $sections = array_merge($sections, $results2['ch4'] ?? []);
        $sections = array_merge($sections, $results2['ch5ref'] ?? []);

        // ── 7. REFERENCES (Generated Deterministically from Citations) ──
        $fullText = implode("\n\n", $sections);
        $sections['REFERENCES'] = $this->referencingService->generateReferencesBlock($fullText, $style);

        // ── 8. APPENDICES ──
        $sections['Appendices'] = $this->generateAppendices($survey, $aggregatedData);

        return $sections;
    }

    private function parseAndInject($response, &$sections)
    {
        $parts = preg_split('/\[SECTION:\s*([^\]]+)\]/i', $response, -1, PREG_SPLIT_DELIM_CAPTURE);
        for ($i = 1; $i < count($parts); $i += 2) {
            $rawTitle = trim($parts[$i]);
            $content = trim($parts[$i + 1] ?? '');

            // Marker Normalization
            $title = $rawTitle;
            $mappings = [
                'Background' => '1.1 Background of the Study',
                'Statement of the Problem' => '1.2 Statement of the Problem',
                'Objectives' => '1.3 Objectives and Research Questions',
                'Research Questions' => '1.3 Objectives and Research Questions',
                'Significance' => '1.4 Significance of the Study',
                'Scope and Limitations' => '1.5 Scope and Limitations',
                '2.1 Introduction' => '2.1 Introduction',
                'Theoretical Framework' => '2.2 Theoretical Framework',
                'Conceptual Framework' => '2.3 Conceptual Framework',
                'Empirical Review' => '2.4 Empirical Review',
                'Research Gaps' => '2.5 Research Gaps',
                '2.6 Summary' => '2.6 Summary',
                '4.1 Introduction' => '4.1 Introduction',
                'Response Rate' => '4.2 Response Rate',
                'Respondent Demographics' => '4.3 Respondent Demographics',
                'Discussion of Findings' => '4.5 Discussion of Findings',
                '4.6 Summary' => '4.6 Summary',
                'Summary of Findings' => '5.1 Summary of Findings',
                'Conclusions' => '5.2 Conclusions',
                'Limitations' => '5.3 Limitations of the Study',
                'Recommendations' => '5.4 Recommendations',
                'Declaration' => 'Declaration',
                'Acknowledgement' => 'Acknowledgement',
                'Abstract' => 'Abstract',
                'Abbreviations' => 'Abbreviations',
                'Definition of Key Terms' => 'Definition of Key Terms',
                'REFERENCES' => 'REFERENCES',
            ];

            foreach ($mappings as $match => $standardKey) {
                if (str_contains($rawTitle, $match)) {
                    $title = $standardKey;
                    break;
                }
            }

            if ($title && $content) {
                $sections[$title] = $content;
            }
        }
    }

    public function translateReport(array $sections, string $targetLocale)
    {
        $langMap = [
            'sw' => 'Swahili',
            'fr' => 'French',
            'de' => 'German',
            'es' => 'Spanish',
            'ar' => 'Arabic',
            'zh' => 'Chinese (Simplified)',
        ];
        $targetLanguage = $langMap[$targetLocale] ?? 'English';

        $translatedSections = $sections;
        $sectionsToTranslate = [];
        $idMap = [];
        $counter = 0;

        foreach ($sections as $title => $content) {
            if ($content === '__chapter_header__' || $title === 'Title Page') {
                continue;
            }
            $id = "SEC" . (++$counter);
            $sectionsToTranslate[$id] = $content;
            $idMap[$id] = $title;
        }

        if (empty($sectionsToTranslate))
            return ['success' => true, 'sections' => $sections];

        $chunks = array_chunk($sectionsToTranslate, 5, true);
        $prompts = [];
        foreach ($chunks as $chunkIdx => $chunk) {
            $chunkPrompt = "Translate the following sections into {$targetLanguage}. Maintain formatting.\n\n";
            foreach ($chunk as $id => $content) {
                $chunkPrompt .= "[[[ID: {$id}]]]\n{$content}\n\n";
            }
            $prompts["chunk_{$chunkIdx}"] = $chunkPrompt;
        }

        $systemPrompt = "You are a professional academic translator. Output ONLY the marked sections. Keep [[[ID: ...]]] markers EXACTLY as written. DO NOT translate the 'ID' part of the marker.";
        $responses = $this->aiService->callGroqParallel($prompts, $systemPrompt);

        $anySuccess = false;
        foreach ($responses as $key => $response) {
            if ($response) {
                $anySuccess = true;
                $parts = preg_split('/\[\[\[ID:\s*([^\]]+)\]\]\]/i', $response, -1, PREG_SPLIT_DELIM_CAPTURE);
                for ($i = 1; $i < count($parts); $i += 2) {
                    $idPart = trim($parts[$i]);
                    $contentPart = trim($parts[$i + 1] ?? '');
                    if (isset($idMap[$idPart])) {
                        $translatedSections[$idMap[$idPart]] = $contentPart;
                    }
                }
            }
        }

        return [
            'success' => $anySuccess,
            'sections' => $translatedSections
        ];
    }

    private function processWave(array $prompts, $survey, $style, $currentYear)
    {
        $locale = \Illuminate\Support\Facades\App::getLocale();
        $langMap = [
            'sw' => 'Swahili',
            'fr' => 'French',
            'de' => 'German',
            'es' => 'Spanish',
            'ar' => 'Arabic',
            'zh-CN' => 'Chinese (Simplified)',
        ];
        $language = $langMap[$locale] ?? 'English';

        $systemPrompt = "You are a senior academic director. Write formal, objective research prose. " .
            "CRITICAL: You MUST use the exact English markers [SECTION: Name] provided in the prompt before every new section you write. " .
            "Do NOT translate the names inside the [SECTION: ...] markers, even if you are writing the content in another language. " .
            "Ground every claim in the survey findings. Attribute data to '{$survey->title}' ({$currentYear}). " .
            "Citation style: {$style}. Output only the marked sections. " .
            "WARNING: Do NOT append a 'References' list or bibliography at the end of your text unless explicitly asked to generate [SECTION: REFERENCES]. " .
            "IMPORTANT: You MUST write the entire CONTENT of the sections in {$language}.";

        Log::info("Executing Parallel Wave with " . count($prompts) . " prompts...");
        $responses = $this->aiService->callAiParallel($prompts, $systemPrompt);

        $parsedResults = [];

        foreach ($prompts as $key => $prompt) {
            $parsedResults[$key] = [];
            $response = $responses[$key] ?? null;
            if ($response) {
                $this->parseAndInject($response, $parsedResults[$key]);
            } else {
                Log::warning("Wave call '{$key}' returned null — falling back to sequential.");
                $fallback = $this->aiService->callAi($prompt, $systemPrompt);
                if ($fallback) {
                    $this->parseAndInject($fallback, $parsedResults[$key]);
                }
            }
        }

        return $parsedResults;
    }

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

    private function generateTitlePage($survey, $year, $style)
    {
        $user = auth()->user();
        $name = $user ? $user->name : __('Researcher');
        $reportType = __('A Research Report');
        $byText = __('By');
        $styleText = __('Style');
        return "<div class='title-page' style='text-align:center; padding-top:100px;'><h1>" . strtoupper($survey->title) . "</h1><p>{$reportType}</p><br><p>{$byText}</p><h3>{$name}</h3><br><p>{$styleText}: " . strtoupper($style) . "</p><p>{$year}</p></div>";
    }

    private function generateDeclaration($survey, $year)
    {
        return __("I declare that this report is my original work based on survey data ':title' collected in :year.", [
            'title' => $survey->title,
            'year' => $year
        ]);
    }

    private function generateAcknowledgement($survey)
    {
        return __("I acknowledge the contributions of all respondents to the ':title' survey.", [
            'title' => $survey->title
        ]);
    }

    private function generateAppendices($survey, $aggregatedData)
    {
        $app = __("Appendix A: Questionnaire") . "\n\n";
        foreach ($aggregatedData['questions'] as $idx => $q) {
            $app .= ($idx + 1) . ". " . $q['label'] . "\n";
        }
        return $app;
    }

    private function prepareReferencePrompt(array $references, string $style)
    {
        $p = "";
        foreach ($references as $r) {
            $p .= "Author: {$r['author']}, Title: {$r['title']}, Year: {$r['year']}\n";
        }
        return $p ?: "General academic knowledge.";
    }

    /**
     * Export proposal / report sections to DOCX with native tables and formatted headers.
     */
    /**
     * Export proposal / report sections to DOCX with native tables and formatted headers.
     */
    public function exportToDocx(array $content, string $filename, array $branding = [])
    {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();

        // Define clean academic document styles (Times New Roman / Arial 11pt, 1.15 line spacing, 6pt after)
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        $phpWord->addTitleStyle(1, ['name' => 'Times New Roman', 'size' => 16, 'bold' => true, 'color' => '0F172A'], ['spaceBefore' => 280, 'spaceAfter' => 140]);
        $phpWord->addTitleStyle(2, ['name' => 'Times New Roman', 'size' => 13, 'bold' => true, 'color' => '0F172A'], ['spaceBefore' => 200, 'spaceAfter' => 100]);
        $phpWord->addTitleStyle(3, ['name' => 'Times New Roman', 'size' => 12, 'bold' => true, 'color' => '1E293B'], ['spaceBefore' => 160, 'spaceAfter' => 80]);

        $section = $phpWord->addSection([
            'marginTop' => 1440, // 1 inch
            'marginBottom' => 1440,
            'marginLeft' => 1440,
            'marginRight' => 1440,
        ]);

        // Handle Branding for DOCX
        if (!empty($branding)) {
            if (!empty($branding['showKdBranding'])) {
                $footer = $section->addFooter();
                $footer->addPreserveText('Generated by KDAnalytiks - Page {PAGE} of {NUMPAGES}', ['bold' => true, 'size' => 10, 'color' => '999999'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            } else {
                $header = $section->addHeader();
                if (!empty($branding['customLogo'])) {
                    $logoPath = storage_path('app/public/' . $branding['customLogo']);
                    if (file_exists($logoPath)) {
                        $header->addImage($logoPath, [
                            'width' => 80,
                            'height' => 80,
                            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
                        ]);
                    }
                }
                if (!empty($branding['customOrgName'])) {
                    $header->addTextBreak(1);
                    $header->addText($branding['customOrgName'], ['bold' => true, 'size' => 14], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                }
                $footer = $section->addFooter();
                $footer->addPreserveText('Page {PAGE} of {NUMPAGES}', null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            }
        }

        foreach ($content as $title => $text) {
            $localizedTitle = __($title);
            if ($text === '__chapter_header__') {
                $section->addPageBreak();
                $section->addTitle($localizedTitle, 1);
                continue;
            }

            // Skip title for the Title Page, Chapters, or Budget as the markdown body already includes the primary heading
            if ($title !== 'Title Page' && !str_starts_with($title, 'CHAPTER') && !str_starts_with($title, 'PROPOSED BUDGET')) {
                $section->addTitle($localizedTitle, 2);
            }

            // Block-by-block robust injection with multi-tier sanitization
            $blocks = $this->splitIntoBlocks($text);
            foreach ($blocks as $block) {
                $trimmedBlock = trim($block);
                if (empty($trimmedBlock))
                    continue;

                // Handle standard Markdown headings natively for zero XML parse failures
                if (preg_match('/^(#{1,4})\s+(.+)$/m', $trimmedBlock, $hMatches) && strlen($trimmedBlock) < 300 && !str_contains($trimmedBlock, "\n")) {
                    $level = strlen($hMatches[1]);
                    $headingText = trim(strip_tags($hMatches[2]));
                    $section->addTitle($headingText, min($level, 3));
                    continue;
                }

                $blockHtml = $this->markdownToHtmlForWord($block);
                try {
                    \PhpOffice\PhpWord\Shared\Html::addHtml($section, $blockHtml, false, false);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("DOCX Block HTML Error: " . $e->getMessage() . " | Executing native fallback.");
                    // Clean native fallback by line
                    $lines = explode("\n", $trimmedBlock);
                    foreach ($lines as $l) {
                        $cleanL = trim(strip_tags($l));
                        if (!empty($cleanL)) {
                            if (str_starts_with($cleanL, '- ') || str_starts_with($cleanL, '* ')) {
                                $section->addListItem(substr($cleanL, 2), 0, ['size' => 11, 'color' => '333333']);
                            } else {
                                $section->addText($cleanL, ['size' => 11, 'color' => '333333']);
                            }
                        }
                    }
                }
            }

            $section->addTextBreak(1);
        }

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $path = storage_path('app/public/reports/' . $filename . '.docx');
        $writer->save($path);
        return $path;
    }

    /**
     * Split text into structural blocks (paragraphs, headers, tables) for isolated try-catch processing.
     */
    private function splitIntoBlocks(string $text): array
    {
        $blocks = [];
        $lines = explode("\n", str_replace("\r", "", $text));
        $currentTable = [];
        $currentParagraph = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (str_starts_with($trimmed, '|')) {
                if (!empty($currentParagraph)) {
                    $blocks[] = implode("\n", $currentParagraph);
                    $currentParagraph = [];
                }
                $currentTable[] = $line;
            } else {
                if (!empty($currentTable)) {
                    $blocks[] = implode("\n", $currentTable);
                    $currentTable = [];
                }
                if ($trimmed === '') {
                    if (!empty($currentParagraph)) {
                        $blocks[] = implode("\n", $currentParagraph);
                        $currentParagraph = [];
                    }
                } else {
                    $currentParagraph[] = $line;
                }
            }
        }

        if (!empty($currentTable)) {
            $blocks[] = implode("\n", $currentTable);
        }
        if (!empty($currentParagraph)) {
            $blocks[] = implode("\n", $currentParagraph);
        }

        return !empty($blocks) ? $blocks : [$text];
    }

    /**
     * Convert markdown text (headers, bold, tables, lists) to safe, strictly-valid HTML for Word generation.
     */
    private function markdownToHtmlForWord(string $text): string
    {
        $trimmed = trim($text);
        if (empty($trimmed))
            return '';

        // Transform Mermaid diagrams into an Academic Box-Model Table for native Word compatibility
        if (stripos($trimmed, '```mermaid') !== false || stripos($trimmed, 'graph LR') !== false) {
            $cleanMermaid = preg_replace('/```(?:mermaid)?/i', '', $trimmed);

            // Extract independent and dependent variables from mermaid text
            preg_match_all('/(?:IV\d*\["|\[)([^\]"]+)(?:"\]|\])/i', $cleanMermaid, $ivMatches);
            preg_match_all('/(?:DV\d*\["|\[)([^\]"]+)(?:"\]|\])/i', $cleanMermaid, $dvMatches);

            $ivItems = array_unique(array_filter($ivMatches[1] ?? [], function ($v) {
                return !in_array(strtolower(trim($v)), ['independent variables', 'dependent variables', 'dependent variable', 'moderating variables']);
            }));
            $dvItems = array_unique(array_filter($dvMatches[1] ?? [], function ($v) {
                return !in_array(strtolower(trim($v)), ['independent variables', 'dependent variables', 'dependent variable', 'moderating variables']);
            }));

            $ivListHtml = '';
            foreach ($ivItems as $item) {
                $ivListHtml .= '<li style="margin-bottom:4px; font-size:10pt;">' . htmlspecialchars($item) . '</li>';
            }
            if (empty($ivListHtml))
                $ivListHtml = '<li style="font-size:10pt;">Independent Predictors (Construct Indicators)</li>';

            $dvListHtml = '';
            foreach ($dvItems as $item) {
                $dvListHtml .= '<li style="margin-bottom:4px; font-size:10pt;">' . htmlspecialchars($item) . '</li>';
            }
            if (empty($dvListHtml))
                $dvListHtml = '<li style="font-size:10pt;">Dependent Outcome Variables</li>';

            return '<table border="1" cellpadding="8" style="width:100%; border-collapse:collapse; margin-top:14px; margin-bottom:18px; border:2px solid #1e293b;">' .
                '<tr>' .
                '<th style="width:44%; background-color:#f1f5f9; color:#0f172a; font-weight:bold; font-size:11pt; padding:10px 14px; text-align:left; border:1px solid #cbd5e1;">INDEPENDENT VARIABLES (IVs)</th>' .
                '<th style="width:12%; background-color:#f8fafc; text-align:center; font-weight:bold; font-size:12pt; border:1px solid #cbd5e1; color:#2271b1;">PATH</th>' .
                '<th style="width:44%; background-color:#f1f5f9; color:#0f172a; font-weight:bold; font-size:11pt; padding:10px 14px; text-align:left; border:1px solid #cbd5e1;">DEPENDENT VARIABLES (DVs)</th>' .
                '</tr>' .
                '<tr>' .
                '<td style="vertical-align:middle; padding:14px 16px; border:1px solid #cbd5e1; background-color:#ffffff;"><ul style="margin:0; padding-left:18px;">' . $ivListHtml . '</ul></td>' .
                '<td style="vertical-align:middle; text-align:center; font-size:16pt; color:#2271b1; border:1px solid #cbd5e1; background-color:#f8fafc;"><b>&#10132;</b></td>' .
                '<td style="vertical-align:middle; padding:14px 16px; border:1px solid #cbd5e1; background-color:#ffffff;"><ul style="margin:0; padding-left:18px;">' . $dvListHtml . '</ul></td>' .
                '</tr>' .
                '</table>' .
                '<p style="font-size:9pt; color:#64748b; font-style:italic; margin-top:4px; margin-bottom:16px;">Figure 2.1: Conceptual Framework mapping hypothesized relational pathways.</p>';
        }

        // If it's a markdown table block
        if (str_starts_with($trimmed, '|')) {
            $rows = explode("\n", str_replace("\r", "", $trimmed));
            $html = '<table border="1" cellpadding="6" style="width:100%; border-collapse:collapse; margin-bottom:12px;">';
            $isHeader = true;

            foreach ($rows as $row) {
                $row = trim($row);
                if (empty($row) || preg_match('/^\|[\s\-:|]+\|$/', $row)) {
                    $isHeader = false;
                    continue;
                }
                $cells = array_map('trim', explode('|', trim($row, '|')));
                $tag = $isHeader ? 'th' : 'td';
                $bg = $isHeader ? 'background-color:#f1f5f9; font-weight:bold;' : '';

                $html .= '<tr>';
                foreach ($cells as $cell) {
                    $cleanCell = htmlspecialchars($cell, ENT_QUOTES, 'UTF-8');
                    // Allow simple inline bolding inside table cells
                    $cleanCell = preg_replace('/\*\*(.+?)\*\*/s', '<b>$1</b>', $cleanCell);
                    $html .= "<{$tag} style='padding:6px 10px; border:1px solid #cbd5e1; {$bg}'>{$cleanCell}</{$tag}>";
                }
                $html .= '</tr>';
                if ($isHeader) {
                    $isHeader = false;
                }
            }
            $html .= '</table>';
            return $html;
        }

        // Clean LaTeX math notations before markdown conversion for clean DOCX display
        $formattedText = $this->convertLatexMathForWord($text);

        // Use Laravel's built-in CommonMark parser for standard markdown blocks
        try {
            $html = \Illuminate\Support\Str::markdown($formattedText);
            // Sanitize unneeded outer wrapper tags for PHPWord
            $html = preg_replace('/<\/?(?:div|section|article)[^>]*>/i', '', $html);
            return trim($html);
        } catch (\Exception $e) {
            return '<p style="margin-bottom:10px; line-height:1.6;">' . nl2br(htmlspecialchars($formattedText, ENT_QUOTES, 'UTF-8')) . '</p>';
        }
    }

    /**
     * Convert LaTeX math notation ($$...$$, \(...\), \frac, \beta, etc.) into clean Word-friendly HTML.
     */
    private function convertLatexMathForWord(string $text): string
    {
        // Replace display math $$ ... $$ with clean bold italic formula lines
        $text = preg_replace_callback('/\$\$\s*(.+?)\s*\$\$/s', function ($matches) {
            $formula = $matches[1];
            $clean = $this->cleanMathExpression($formula);
            return "\n\n> **Formula / Model:** *{$clean}*\n\n";
        }, $text);

        // Replace inline math \( ... \) or $ ... $
        $text = preg_replace_callback('/\\\\\(\s*(.+?)\s*\\\\\)/s', function ($matches) {
            return '*' . $this->cleanMathExpression($matches[1]) . '*';
        }, $text);

        $text = preg_replace_callback('/(?<!\$)\$(?!\$)(.+?)(?<!\$)\$(?!\$)/s', function ($matches) {
            return '*' . $this->cleanMathExpression($matches[1]) . '*';
        }, $text);

        return $text;
    }

    /**
     * Clean LaTeX symbols to standard Unicode academic characters for DOCX.
     */
    private function cleanMathExpression(string $expr): string
    {
        // Remove LaTeX formatting commands
        $expr = preg_replace('/\\\\text\{([^}]+)\}/', '$1', $expr);
        $expr = preg_replace('/\\\\bigl\(|\\\\bigr\)/', '', $expr);
        $expr = preg_replace('/\\\\left\(|\\\\right\)/', '', $expr);

        // Convert fractions \frac{num}{den} to (num / den)
        $expr = preg_replace('/\\\\frac\{([^}]+)\}\{([^}]+)\}/', '($1 / $2)', $expr);

        // Convert Greek & mathematical symbols to Unicode
        $replacements = [
            '\\beta_{0}' => 'β₀',
            '\\beta_0' => 'β₀',
            '\\beta_{1}' => 'β₁',
            '\\beta_1' => 'β₁',
            '\\beta_{2}' => 'β₂',
            '\\beta_2' => 'β₂',
            '\\beta_{3}' => 'β₃',
            '\\beta_3' => 'β₃',
            '\\beta_{4}' => 'β₄',
            '\\beta_4' => 'β₄',
            '\\beta_{5}' => 'β₅',
            '\\beta_5' => 'β₅',
            '\\beta_k' => 'βₖ',
            '\\beta_{j}' => 'βⱼ',
            '\\beta_j' => 'βⱼ',
            '\\beta' => 'β',
            '\\varepsilon' => 'ε',
            '\\epsilon' => 'ε',
            '\\alpha' => 'α',
            '\\times' => ' × ',
            '\\approx' => ' ≈ ',
            '\\neq' => ' ≠ ',
            '\\le' => ' ≤ ',
            '\\ge' => ' ≥ ',
            '\\pm' => ' ± ',
            '\\sum' => 'Σ',
            '\\sqrt' => '√',
            '\\mu' => 'μ',
            '\\sigma' => 'σ',
            '\\Delta' => 'Δ',
            '\\;' => ' ',
            '\\,' => ' ',
            '\\{' => '',
            '\\}' => '',
            '{' => '',
            '}' => '',
        ];

        return trim(str_replace(array_keys($replacements), array_values($replacements), $expr));
    }
}
