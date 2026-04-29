<?php

namespace App\Services;

use App\Models\ResearchProposal;
use Illuminate\Support\Facades\Log;

class ProposalGeneratorService
{
    protected $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Generate a formal research proposal based on user inputs.
     */
    public function generateProposal(ResearchProposal $proposal)
    {
        set_time_limit(600);
        $generatedContent = [];
        $style = $proposal->style ?? 'APA 7th';
        $currentYear = date('Y');

        $locale = \Illuminate\Support\Facades\App::getLocale();
        $langMap = [
            'sw' => 'Swahili',
            'fr' => 'French',
            'de' => 'German',
            'es' => 'Spanish',
            'ar' => 'Arabic',
            'zh' => 'Chinese (Simplified)',
        ];
        $language = $langMap[$locale] ?? 'English';

        $systemPrompt = "You are a professional academic consultant. " .
            "Transform sparse researcher inputs into high-quality, persuasive, logically sound 5-chapter drafts. " .
            "Formal tone. Academic style: {$style}. " .
            "CRITICAL: You MUST use the exact English markers [SECTION: Name] provided in the prompt before every new section you write. " .
            "Do NOT translate the names inside the [SECTION: ...] markers, even if you are writing the content in another language. " .
            "IMPORTANT: You MUST write the entire CONTENT of the sections in {$language}.";

        // ── 1. PRELIMINARIES ──
        $p0 = "Draft PRELIMINARY pages for a research study titled '{$proposal->title}':\n" .
            "Use markers [SECTION: Name] for:\n" .
            "[SECTION: Abstract] - 250-word formal abstract.\n" .
            "[SECTION: Abbreviations] - Relevant list.\n" .
            "[SECTION: Definition of Key Terms] - 5-8 core terms.";
        Log::info("Drafting Preliminaries for ID: {$proposal->id}");
        $this->batchProcess($p0, $generatedContent, $systemPrompt);

        // ── 2. CHAPTER 1: INTRODUCTION ──
        sleep(2);
        $p1 = "Draft CHAPTER 1: INTRODUCTION for '{$proposal->title}':\n" .
            "Objectives: {$proposal->objectives}\n" .
            "Question: {$proposal->research_question}\n" .
            "Scope: {$proposal->scope}\n\n" .
            "Use markers [SECTION: Name] for:\n" .
            "[SECTION: 1.1 Background of the Study]\n" .
            "[SECTION: 1.2 Statement of the Problem]\n" .
            "[SECTION: 1.3 Objectives of the Study]\n" .
            "[SECTION: 1.4 Research Questions]\n" .
            "[SECTION: 1.5 Significance of the Study]\n" .
            "[SECTION: 1.6 Scope and Limitations]";
        Log::info("Drafting Ch 1 for ID: {$proposal->id}");
        $this->batchProcess($p1, $generatedContent, $systemPrompt);

        // ── 3. CHAPTER 2: LITERATURE REVIEW ──
        sleep(2);
        $p2 = "Draft CHAPTER 2: LITERATURE REVIEW for '{$proposal->title}':\n" .
            "Use markers:\n" .
            "[SECTION: 2.1 Theoretical Framework]\n" .
            "[SECTION: 2.2 Conceptual Framework] - Discuss variables relationships.\n" .
            "[SECTION: 2.3 Empirical Review] - Discuss past studies trends.\n" .
            "[SECTION: 2.4 Research Gaps]";
        Log::info("Drafting Ch 2 for ID: {$proposal->id}");
        $this->batchProcess($p2, $generatedContent, $systemPrompt);

        // ── 4. CHAPTER 3: METHODOLOGY ──
        sleep(2);
        $p3 = "Draft CHAPTER 3: RESEARCH METHODOLOGY for '{$proposal->title}':\n" .
            "Methodology Type: {$proposal->methodology_type}\n\n" .
            "Use markers:\n" .
            "[SECTION: 3.1 Research Design]\n" .
            "[SECTION: 3.2 Target Population & Sampling]\n" .
            "[SECTION: 3.3 Data Collection Instruments]\n" .
            "[SECTION: 3.4 Validity and Reliability]\n" .
            "[SECTION: 3.5 Data Analysis Plan]";
        Log::info("Drafting Ch 3 for ID: {$proposal->id}");
        $this->batchProcess($p3, $generatedContent, $systemPrompt);

        // ── 5. CHAPTER 4: EXPECTED RESULTS ──
        sleep(2);
        $p4 = "Draft CHAPTER 4: EXPECTED RESULTS & DATA PRESENTATION for '{$proposal->title}':\n" .
            "Based on the objectives, discuss how findings will likely look.\n" .
            "Use markers:\n" .
            "[SECTION: 4.1 Introduction to Analysis]\n" .
            "[SECTION: 4.2 Expected Finding Trends]\n" .
            "[SECTION: 4.3 Data Presentation Plan]";
        Log::info("Drafting Ch 4 for ID: {$proposal->id}");
        $this->batchProcess($p4, $generatedContent, $systemPrompt);

        // ── 6. CHAPTER 5: RECOMMENDATIONS ──
        sleep(2);
        $p5 = "Draft CHAPTER 5: SUMMARY, CONCLUSIONS AND RECOMMENDATIONS for '{$proposal->title}':\n" .
            "Discuss conclusions for the study design.\n" .
            "Use markers:\n" .
            "[SECTION: 5.1 Summary of the Study Plan]\n" .
            "[SECTION: 5.2 Conclusions based on expected trends]\n" .
            "[SECTION: 5.3 Recommendations for Future Research]";
        Log::info("Drafting Ch 5 for ID: {$proposal->id}");
        $this->batchProcess($p5, $generatedContent, $systemPrompt);

        // ── 7. REFERENCES & APPENDIX ──
        sleep(2);
        $p6 = "Draft REFERENCES and APPENDIX for '{$proposal->title}':\n" .
            "Style: {$style}\n\n" .
            "Use markers:\n" .
            "[SECTION: REFERENCES] - 10-15 mock bibliography entries.\n" .
            "[SECTION: APPENDIX: Sample Questionnaire] - Draft a 10-question instrument.";
        Log::info("Drafting Appendix for ID: {$proposal->id}");
        $this->batchProcess($p6, $generatedContent, $systemPrompt);

        $proposal->update([
            'content' => $generatedContent,
            'status' => 'generated'
        ]);

        return $proposal;
    }

    private function batchProcess($prompt, &$contentArray, $systemPrompt)
    {
        $response = $this->aiService->callGroq($prompt, $systemPrompt);
        if ($response) {
            $parts = preg_split('/\[SECTION:\s*([^\]]+)\]/i', $response, -1, PREG_SPLIT_DELIM_CAPTURE);
            for ($i = 1; $i < count($parts); $i += 2) {
                $title = trim($parts[$i]);
                $body = trim($parts[$i + 1] ?? '');
                if ($title && $body) {
                    $contentArray[$title] = $body;
                }
            }
        }
    }
}
