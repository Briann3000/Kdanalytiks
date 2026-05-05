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

        $ch5RefPrompt = "Write Chapter 5 AND References for '{$survey->title}'.\n" .
            "Use markers: [SECTION: 5.1 Summary of Findings], [SECTION: 5.2 Conclusions], [SECTION: 5.3 Limitations of the Study], [SECTION: 5.4 Recommendations], [SECTION: REFERENCES].\n" .
            "For REFERENCES, list 10-15 academic sources in {$style} style.\n" .
            "Manual references to include: {$referencePrompt}\n" .
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
        // 1. Preliminaries
        $sections = array_merge($sections, $results1['prelim'] ?? []);

        // 2. Chapter 1
        $sections['CHAPTER 1: INTRODUCTION'] = '__chapter_header__';
        $sections = array_merge($sections, $results1['ch1'] ?? []);

        // 3. Chapter 2
        $sections['CHAPTER 2: LITERATURE REVIEW'] = '__chapter_header__';
        $sections = array_merge($sections, $results1['ch2'] ?? []);

        // 4. Chapter 3
        $sections['CHAPTER 3: RESEARCH METHODOLOGY'] = '__chapter_header__';
        $sections = array_merge($sections, $results2['ch3'] ?? []);

        // 5. Chapter 4
        $sections['CHAPTER 4: RESULTS AND DISCUSSION'] = '__chapter_header__';
        $sections = array_merge($sections, $results2['ch4'] ?? []);

        // 6. Chapter 5 & References
        // The AI generates 5.1, 5.2, 5.3 and then REFERENCES.
        // We need to inject the CHAPTER 5 header before 5.1.
        $sections['CHAPTER 5: SUMMARY, CONCLUSIONS AND RECOMMENDATIONS'] = '__chapter_header__';

        // Separate out REFERENCES if it exists in the chunk
        $ch5RefChunk = $results2['ch5ref'] ?? [];
        $refsContent = null;
        if (isset($ch5RefChunk['REFERENCES'])) {
            $refsContent = $ch5RefChunk['REFERENCES'];
            unset($ch5RefChunk['REFERENCES']);
        }

        $sections = array_merge($sections, $ch5RefChunk);

        if ($refsContent) {
            $sections['REFERENCES'] = '__chapter_header__';
            $sections['REFERENCES_CONTENT'] = $refsContent; // Using a unique key so it doesn't overwrite header
        }

        // ── RE-INJECT TABLES INTO CH4 ──
        $targetKey = null;
        foreach (array_keys($sections) as $key) {
            if (str_contains($key, '4.4 Data Analysis')) {
                $targetKey = $key;
                break;
            }
        }

        if ($targetKey) {
            foreach ($aggregatedData['questions'] as $idx => $q) {
                if (!empty($q['stats'])) {
                    $sections[$targetKey] .= "\n\n" . $this->buildSingleQuestionTableHtml($q['label'], $q['stats'], $totalResponses);
                }
            }
            // Fix sub-headings formatting (convert **4.4.x ...** or plain 4.4.x or ***4.4.x...*** to headings)
            $sections[$targetKey] = preg_replace('/(?:\*+)?(4\.4\.\d+\s+[^\n\*]+)(?:\*+)?/', '<h4>$1</h4>', $sections[$targetKey]);
            // Remove "(linked to objectives)" text from any headings
            $sections[$targetKey] = preg_replace('/\s*\(linked\s+to\s+objectives\)/i', '', $sections[$targetKey]);
        }

        // Apply cleaning to all sections
        foreach ($sections as $k => $v) {
            if ($v !== '__chapter_header__') {
                $sections[$k] = preg_replace('/\s*\(linked\s+to\s+objectives\)/i', '', $sections[$k]);

                // Fix "Definition of Key Terms" asterisk formatting (e.g. **Term**: ... or ***Term***: ...)
                if (str_contains($k, 'Definition of Key Terms')) {
                    $sections[$k] = preg_replace('/(?:\*+)([^\*:]+)(?:\*+):/', '<strong>$1</strong>:', $sections[$k]);
                }
            }
        }

        // ── APPENDICES ──
        $sections['APPENDICES'] = $this->generateAppendices($survey, $aggregatedData);

        return [
            'outline' => "Generated Academic Structure",
            'sections' => array_filter($sections),
            'metadata' => [
                'survey_id' => $survey->id,
                'style' => $style,
                'manual_references' => $manualReferences,
                'generated_at' => now()->toIso8601String(),
                'total_responses' => $totalResponses,
                'locale' => $locale,
            ]
        ];
    }

    /**
     * Normalize report keys back to standard English keys so that __() can translate them.
     */
    public function normalizeReportKeys(array $sections)
    {
        $normalized = [];
        $mappings = [
            '1.1' => '1.1 Background of the Study',
            '1.2' => '1.2 Statement of the Problem',
            '1.3' => '1.3 Objectives and Research Questions',
            '1.4' => '1.4 Significance of the Study',
            '1.5' => '1.5 Scope and Limitations',
            '2.1' => '2.1 Introduction',
            '2.2' => '2.2 Theoretical Framework',
            '2.3' => '2.3 Conceptual Framework',
            '2.4' => '2.4 Empirical Review',
            '2.5' => '2.5 Research Gaps',
            '2.6' => '2.6 Summary',
            '3.1' => '3.1 Research Design',
            '3.2' => '3.2 Target Population',
            '3.3' => '3.3 Sample Size and Sampling Techniques',
            '3.4' => '3.4 Data Collection Instruments',
            '3.6' => '3.6 Validity and Reliability',
            '4.1' => '4.1 Introduction',
            '4.2' => '4.2 Response Rate',
            '4.3' => '4.3 Respondent Demographics',
            '4.4' => '4.4 Data Analysis and Presentation',
            '4.5' => '4.5 Discussion of Findings',
            '4.6' => '4.6 Summary',
            '5.1' => '5.1 Summary of Findings',
            '5.2' => '5.2 Conclusions',
            '5.3' => '5.3 Limitations of the Study',
            '5.4' => '5.4 Recommendations',
            'SURA YA 1' => 'CHAPTER 1: INTRODUCTION',
            'SURA YA 2' => 'CHAPTER 2: LITERATURE REVIEW',
            'SURA YA 3' => 'CHAPTER 3: RESEARCH METHODOLOGY',
            'SURA YA 4' => 'CHAPTER 4: RESULTS AND DISCUSSION',
            'SURA YA 5' => 'CHAPTER 5: SUMMARY, CONCLUSIONS AND RECOMMENDATIONS',
            'CHAPITRE 1' => 'CHAPTER 1: INTRODUCTION',
            'CHAPITRE 2' => 'CHAPTER 2: LITERATURE REVIEW',
            'CHAPITRE 3' => 'CHAPTER 3: RESEARCH METHODOLOGY',
            'CHAPITRE 4' => 'CHAPTER 4: RESULTS AND DISCUSSION',
            'CHAPITRE 5' => 'CHAPTER 5: SUMMARY, CONCLUSIONS AND RECOMMENDATIONS',
            'CAPÍTULO 1' => 'CHAPTER 1: INTRODUCTION',
            'CAPÍTULO 2' => 'CHAPTER 2: LITERATURE REVIEW',
            'CAPÍTULO 3' => 'CHAPTER 3: RESEARCH METHODOLOGY',
            'CAPÍTULO 4' => 'CHAPTER 4: RESULTS AND DISCUSSION',
            'CAPÍTULO 5' => 'CHAPTER 5: SUMMARY, CONCLUSIONS AND RECOMMENDATIONS',
        ];

        foreach ($sections as $title => $content) {
            $newTitle = $title;
            foreach ($mappings as $match => $standard) {
                if (str_contains(strtoupper($title), strtoupper($match))) {
                    $newTitle = $standard;
                    break;
                }
            }
            $normalized[$newTitle] = $content;
        }

        return $normalized;
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

    /**
     * Process a batch of sections from a single AI call.
     */
    /**
     * Translate an existing report to a new language using batched calls for speed.
     */
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
            \Illuminate\Support\Facades\Log::info("Translation Response for $key: " . substr($response, 0, 200));
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
    /**
     * Process a wave of 2 parallel AI calls, parsing results in order.
     */
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

        // Parse responses IN KEY ORDER
        foreach ($prompts as $key => $prompt) {
            $parsedResults[$key] = [];
            $response = $responses[$key] ?? null;
            if ($response) {
                $this->parseAndInject($response, $parsedResults[$key]);
            } else {
                Log::warning("Wave call '{$key}' returned null — falling back to sequential.");
                // Fallback: try a single sequential call for this prompt
                $fallback = $this->aiService->callAi($prompt, $systemPrompt);
                if ($fallback) {
                    $this->parseAndInject($fallback, $parsedResults[$key]);
                }
            }
        }

        return $parsedResults;
    }

    private function batchProcess($prompt, &$sections, $survey, $style, $currentYear)
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

        Log::info("Executing Batch AI Call for prompt: " . substr($prompt, 0, 100) . "...");
        $response = $this->aiService->callAi($prompt, $systemPrompt);

        if ($response) {
            // More resilient regex: handles optional spaces and case sensitivity
            $parts = preg_split('/\[SECTION:\s*([^\]]+)\]/i', $response, -1, PREG_SPLIT_DELIM_CAPTURE);

            for ($i = 1; $i < count($parts); $i += 2) {
                $rawTitle = trim($parts[$i]);
                $content = trim($parts[$i + 1] ?? '');

                // Marker Normalization: Map common translations back to English keys
                $title = $rawTitle;
                $mappings = [
                    'Background' => '1.1 Background of the Study',
                    'Statement of the Problem' => '1.2 Statement of the Problem',
                    'Objectives' => '1.3 Objectives of the Study',
                    'Research Questions' => '1.4 Research Questions',
                    'Significance' => '1.5 Significance of the Study',
                    'Scope and Limitations' => '1.6 Scope and Limitations',
                    'Theoretical Framework' => '2.1 Theoretical Framework',
                    'Conceptual Framework' => '2.2 Conceptual Framework',
                    'Empirical Review' => '2.3 Empirical Review',
                    'Research Gaps' => '2.4 Research Gaps',
                    'Summary' => '2.5 Summary',
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
        // ── 1. GENERATE CHART USING QUICKCHART ──
        $chartLabels = [];
        $chartData = [];
        foreach (array_slice($stats, 0, 8) as $s) {
            $chartLabels[] = strlen($s['option']) > 15 ? substr($s['option'], 0, 12) . '...' : $s['option'];
            $chartData[] = $s['count'];
        }

        $chartConfig = [
            'type' => 'bar',
            'data' => [
                'labels' => $chartLabels,
                'datasets' => [
                    [
                        'label' => 'Frequency',
                        'data' => $chartData,
                        'backgroundColor' => 'rgba(79, 70, 229, 0.7)',
                        'borderColor' => 'rgb(79, 70, 229)',
                        'borderWidth' => 1
                    ]
                ]
            ],
            'options' => [
                'title' => ['display' => true, 'text' => $label],
                'legend' => ['display' => false]
            ]
        ];

        $chartUrl = "https://quickchart.io/chart?c=" . urlencode(json_encode($chartConfig)) . "&w=500&h=300";

        $html = "<div style='text-align: center; margin: 30px 0;'>";
        $html .= "<img src='{$chartUrl}' style='max-width: 100%; height: auto; border: 1px solid #eee; border-radius: 8px;' />";
        $html .= "</div>";

        $html .= "<h4>Table: {$label} (N={$totalResponses})</h4>";
        $html .= "<table border='1' style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>";
        $html .= "<thead><tr style='background: #f3f4f6;'>";
        $html .= "<th style='padding: 8px; text-align: left;'>Response</th>";
        $html .= "<th style='padding: 8px; text-align: center;'>f</th>";
        $html .= "<th style='padding: 8px; text-align: center;'>%</th>";
        $html .= "</tr></thead>";
        $html .= "<tbody>";
        foreach ($stats as $s) {
            $html .= "<tr>";
            $html .= "<td style='padding: 8px; border: 1px solid #ddd;'>{$s['option']}</td>";
            $html .= "<td style='padding: 8px; border: 1px solid #ddd; text-align: center;'>{$s['count']}</td>";
            $html .= "<td style='padding: 8px; border: 1px solid #ddd; text-align: center;'>{$s['percentage']}%</td>";
            $html .= "</tr>";
        }
        $html .= "</tbody></table>";
        return $html;
    }

    // ... (Keep existing Title Page, Declaration, Acknowledgement, Appendices, Export methods from previous version) ...
    // Note: I will merge the rest of the existing methods below.

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

    public function exportToDocx(array $content, string $filename, array $branding = [])
    {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();

        // Handle Branding for DOCX
        if (!empty($branding)) {
            if ($branding['showKmBranding']) {
                $footer = $section->addFooter();
                $footer->addPreserveText('Generated by KMSurveyTool - Page {PAGE} of {NUMPAGES}', ['bold' => true, 'size' => 10, 'color' => '999999'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            } else {
                $header = $section->addHeader();
                if ($branding['customLogo']) {
                    $logoPath = storage_path('app/public/' . $branding['customLogo']);
                    if (file_exists($logoPath)) {
                        $header->addImage($logoPath, [
                            'width' => 80,
                            'height' => 80,
                            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
                        ]);
                    }
                }
                if ($branding['customOrgName']) {
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

            // Skip title for the Title Page as it's usually inside the content
            if ($title !== 'Title Page') {
                $section->addTitle($localizedTitle, 2);
            }

            // If the content looks like HTML, use the HTML parser
            if (str_contains($text, '<') && str_contains($text, '>')) {
                try {
                    // Strip problematic elements that PhpWord might not like
                    $safeHtml = preg_replace('/<div[^>]*>/i', '', $text);
                    $safeHtml = str_replace('</div>', '<br>', $safeHtml);
                    \PhpOffice\PhpWord\Shared\Html::addHtml($section, $safeHtml, false, false);
                } catch (\Exception $e) {
                    Log::error("DOCX HTML Export error: " . $e->getMessage());
                    $section->addText(strip_tags($text));
                }
            } else {
                $section->addText($text);
            }

            $section->addTextBreak(1);
        }
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $path = storage_path('app/public/reports/' . $filename . '.docx');
        $writer->save($path);
        return $path;
    }

    public function exportToPdf(array $content, string $filename, array $branding = [])
    {
        $html = "";
        foreach ($content as $title => $text) {
            $localizedTitle = __($title);
            if ($text === '__chapter_header__') {
                $html .= "<h1 style='page-break-before:always; text-align:center;'>{$localizedTitle}</h1>";
                continue;
            }
            if (str_contains($text, 'title-page')) {
                $html .= $text;
                continue;
            }
            $html .= "<h2>{$localizedTitle}</h2><div>" . nl2br(e($text)) . "</div>";
        }

        $mpdf = new \Mpdf\Mpdf([
            'margin_left' => 20,
            'margin_right' => 20,
            'margin_top' => 50,
            'margin_bottom' => 25,
            'margin_header' => 10,
            'margin_footer' => 10,
        ]);

        // Handle Branding for PDF
        if (!empty($branding)) {
            if ($branding['showKmBranding']) {
                // BIGGER BRANDING for Free Tier
                $mpdf->SetWatermarkText('KMSurveyTool');
                $mpdf->showWatermarkText = true;
                $mpdf->watermark_font = 'DejaVuSansCondensed';
                $mpdf->watermarkTextAlpha = 0.1;

                $footerHtml = '
                <div style="border-top: 1px solid #eee; padding-top: 10px; font-size: 10px; color: #666; text-align: center; font-weight: bold;">
                    THIS RESEARCH PROPOSAL WAS AI-GENERATED VIA KMSURVEYTOOL.COM. UPGRADE TO REMOVE THIS NOTICE.
                    <br/>
                    <span style="font-size: 8px;">Page {PAGENO} of {nbpg}</span>
                </div>';
                $mpdf->SetHTMLFooter($footerHtml);
            } else {
                // Professional branding for Pro/Enterprise - CENTERED & BIGGER
                $headerHtml = '<div style="text-align: center; border-bottom: 2px solid #f3f4f6; padding-bottom: 15px; margin-bottom: 20px;">';

                if ($branding['customLogo']) {
                    $logoPath = storage_path('app/public/' . $branding['customLogo']);
                    if (file_exists($logoPath)) {
                        $headerHtml .= '<img src="' . $logoPath . '" style="height: 70px; width: auto; margin-bottom: 10px;"><br>';
                    }
                }

                if ($branding['customOrgName']) {
                    $headerHtml .= '<div style="font-size: 16px; color: #111; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;">' . e($branding['customOrgName']) . '</div>';
                }
                $headerHtml .= '</div>';
                $mpdf->SetHTMLHeader($headerHtml);

                $footerHtml = '<div style="text-align: center; font-size: 9px; color: #999;">Page {PAGENO} of {nbpg}</div>';
                $mpdf->SetHTMLFooter($footerHtml);
            }
        }

        $mpdf->WriteHTML($html);
        $path = storage_path('app/public/reports/' . $filename . '.pdf');
        $mpdf->Output($path, \Mpdf\Output\Destination::FILE);
        return $path;
    }
}
