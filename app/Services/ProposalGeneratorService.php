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

        $systemPrompt = "You are a professional academic research consultant. " .
            "Transform sparse researcher inputs into high-quality, persuasive, logically sound academic research proposals. " .
            "Formal tone. Academic style: {$style}. " .
            "PROPOSAL BOUNDARY RULE: This is a formal RESEARCH PROPOSAL (pre-study plan). Generate ONLY Front Matter, Chapter 1, Chapter 2, Chapter 3, References, and Appendices. DO NOT generate Chapter 4 (Findings) or Chapter 5 (Conclusions), as no data has been collected yet. " .
            "CRITICAL TENSE DIRECTIVE: Use future tense ('will') STRICTLY for proposed research actions to be conducted by the researcher (e.g., 'Data will be collected using...', 'The target population will comprise...'). Use PRESENT TENSE for definitions, established facts, theoretical frameworks (e.g., TAM, Cognitive Load Theory), and existing literature summaries (e.g., 'AI refers to...', 'Cognitive Load Theory posits...'). Do NOT apply future tense to static definitions or facts. " .
            "ABSTRACT & KEYWORD RULES: Cap the Abstract at a maximum of 200 words. Enforce keyword frequency caps—do not repeat key terms or acronyms (e.g. 'AI-assisted code generators') more than 3 times in the abstract. " .
            "CITATION REALISM: Ensure generated references strictly conform to valid APA 7th edition formatting and historical context (do not attribute modern AI concepts to pre-2015 literature). " .
            "STRICT NO-MARKDOWN FORMATTING DIRECTIVE: Output clean PLAIN TEXT ONLY. DO NOT use any Markdown formatting characters such as asterisks (* or **), hashes (#), backticks (`), or bullet points (*). Format definitions and lists as clean paragraphs or numbered items (e.g., '1.', '2.'). " .
            "CRITICAL: You MUST use the exact English markers [SECTION: Name] provided in the prompt before every new section you write. " .
            "Do NOT translate the names inside the [SECTION: ...] markers, even if you are writing the content in another language. " .
            "IMPORTANT: You MUST write the entire CONTENT of the sections in {$language}.";

        if (!empty($proposal->custom_instructions)) {
            $systemPrompt .= "\n\nCUSTOM USER PRESET INSTRUCTIONS & TONE/VOICE GUIDELINES:\n{$proposal->custom_instructions}\nStrictly follow these custom tone, structure, and content directives.";
        }

        // ── 1. PRELIMINARIES ──
        $p0 = "Draft PRELIMINARY PAGES (Front Matter) for a research proposal titled '{$proposal->title}':\n" .
            "Use markers [SECTION: Name] for:\n" .
            "[SECTION: Title Page] - Formal academic title reflecting independent & dependent variables.\n" .
            "[SECTION: Abstract] - Maximum 200-word concise formal abstract (problem statement, objectives, proposed methodology, scope).\n" .
            "[SECTION: Abbreviations] - List of abbreviations and acronyms.\n" .
            "[SECTION: Definition of Key Terms] - 5-8 core terms with operational definitions using present tense.";
        Log::info("Drafting Preliminaries for ID: {$proposal->id}");
        $this->batchProcess($p0, $generatedContent, $systemPrompt);

        // ── 2. CHAPTER 1: INTRODUCTION ──
        sleep(1);
        $p1 = "Draft CHAPTER 1: INTRODUCTION for '{$proposal->title}':\n" .
            "Objectives: {$proposal->objectives}\n" .
            "Question: {$proposal->research_question}\n" .
            "Scope: {$proposal->scope}\n\n" .
            "Use markers [SECTION: Name] for ALL of the following sub-sections:\n" .
            "[SECTION: 1.1 Background to the Study] - Historical, global, regional, and local context of the research problem.\n" .
            "[SECTION: 1.2 Statement of the Problem] - Clear identification of the research gap and empirical problem.\n" .
            "[SECTION: 1.3 Purpose of the Study] - Overall goal and general objective.\n" .
            "[SECTION: 1.4 Specific Research Objectives] - Bulleted list of actionable objectives.\n" .
            "[SECTION: 1.5 Research Questions & Hypotheses] - Specific research questions or testable hypotheses.\n" .
            "[SECTION: 1.6 Justification and Significance] - Value to policy makers, scholars, and industry practitioners.\n" .
            "[SECTION: 1.7 Scope and Delimitation] - Geographical, thematic, and temporal boundaries.\n" .
            "[SECTION: 1.8 Limitations and Mitigation] - Potential constraints and proposed strategies to mitigate them.\n" .
            "[SECTION: 1.9 Assumptions of the Study] - Underlying assumptions taken for granted in conducting the research.";
        Log::info("Drafting Ch 1 for ID: {$proposal->id}");
        $this->batchProcess($p1, $generatedContent, $systemPrompt);

        // ── 3. CHAPTER 2: LITERATURE REVIEW ──
        sleep(1);
        $p2 = "Draft CHAPTER 2: LITERATURE REVIEW for '{$proposal->title}':\n" .
            "Use markers [SECTION: Name] for ALL of the following sub-sections:\n" .
            "[SECTION: 2.1 Introduction] - Overview of literature themes.\n" .
            "[SECTION: 2.2 Theoretical Framework] - Theories underpinning the study (e.g. TAM, UTAUT, Systems Theory).\n" .
            "[SECTION: 2.3 Conceptual Framework] - Narrative explanation of variable relationships.\n" .
            "[SECTION: 2.4 Empirical Review] - Systematic review of past empirical studies by key objective themes.\n" .
            "[SECTION: 2.5 Critique of Existing Literature] - Critical appraisal of methodologies and findings in previous studies.\n" .
            "[SECTION: 2.6 Summary of Knowledge Gaps] - Explicit summary of unaddressed empirical, methodological, or contextual gaps.";
        Log::info("Drafting Ch 2 for ID: {$proposal->id}");
        $this->batchProcess($p2, $generatedContent, $systemPrompt);

        // ── 4. CHAPTER 3: METHODOLOGY ──
        sleep(1);
        $p3 = "Draft CHAPTER 3: RESEARCH METHODOLOGY for '{$proposal->title}':\n" .
            "Methodology Type: {$proposal->methodology_type}\n\n" .
            "CRITICAL: Use FUTURE TENSE ('will be collected', 'will be selected') for all proposed procedures.\n" .
            "Use markers [SECTION: Name] for ALL of the following sub-sections:\n" .
            "[SECTION: 3.1 Introduction] - Overview of methodology structure.\n" .
            "[SECTION: 3.2 Research Design] - Justification of descriptive/correlational/mixed-methods design.\n" .
            "[SECTION: 3.3 Target Population] - Target demographic and accessible population description.\n" .
            "[SECTION: 3.4 Sampling Frame, Techniques & Sample Size] - Sampling frame, formula (e.g., Yamane/Krejcie-Morgan), and sampling strategy.\n" .
            "[SECTION: 3.5 Data Collection Instruments] - Structure of questionnaires, interview guides, or observation checklists.\n" .
            "[SECTION: 3.6 Data Collection Procedures] - Step-by-step administration and logistics protocol.\n" .
            "[SECTION: 3.7 Pilot Testing] - Pre-testing sample size (10% rule) and procedures.\n" .
            "[SECTION: 3.8 Validity and Reliability] - Construct/content validity and Cronbach's alpha reliability thresholds.\n" .
            "[SECTION: 3.9 Data Analysis Plan] - Statistical software (SPSS/R), descriptive statistics, and inferential models.\n" .
            "[SECTION: 3.10 Ethical Considerations] - Informed consent, confidentiality, anonymity, and institutional review permissions.";
        Log::info("Drafting Ch 3 for ID: {$proposal->id}");
        $this->batchProcess($p3, $generatedContent, $systemPrompt);

        // ── 5. PROPOSED BUDGET (If user entered budget) ──
        if (!empty($proposal->budget) && is_array($proposal->budget)) {
            $budgetSummary = "";
            foreach ($proposal->budget as $b) {
                if (is_array($b) && !empty($b['item'])) {
                    $budgetSummary .= "- " . $b['item'] . ": KES " . ($b['cost'] ?? '0') . "\n";
                }
            }
            if (!empty($budgetSummary)) {
                $pBudget = "Draft SECTION: Proposed Budget & Work Plan for '{$proposal->title}':\n" .
                    "Specified Budget Items:\n{$budgetSummary}\n" .
                    "Use marker [SECTION: Proposed Budget & Work Plan] to format a clean breakdown table and justification.";
                $this->batchProcess($pBudget, $generatedContent, $systemPrompt);
            }
        }

        // ── 6. REFERENCES & APPENDIX ──
        sleep(1);
        $p6 = "Draft REFERENCES and APPENDIX for '{$proposal->title}':\n" .
            "Style: {$style}\n\n" .
            "Use markers:\n" .
            "[SECTION: REFERENCES] - 12-18 realistic, historically accurate bibliography entries conforming strictly to APA 7th edition.\n" .
            "[SECTION: APPENDIX: Data Collection Instrument] - Draft a full 10-item research questionnaire with demographic and Likert scale sections.";
        Log::info("Drafting Appendix for ID: {$proposal->id}");
        $this->batchProcess($p6, $generatedContent, $systemPrompt);

        $proposal->update([
            'content' => $generatedContent,
            'status' => 'generated'
        ]);

        return $proposal;
    }

    /**
     * Refine/Regenerate an existing proposal using user feedback (supports target chapter selection).
     */
    public function refineProposal(ResearchProposal $proposal, string $userFeedback, string $targetSection = 'all')
    {
        set_time_limit(600);
        $existingContent = $proposal->content ?? [];
        $style = $proposal->style ?? 'APA 7th';

        $sectionFilterText = $targetSection !== 'all' ? "FOCUS EXCLUSIVELY ON REFINING TARGET SECTION: {$targetSection}. Do not modify unrelated sections." : "Refine document sections as requested.";

        $systemPrompt = "You are a professional academic research consultant refining a research proposal titled '{$proposal->title}'.\n" .
            "Academic style: {$style}.\n" .
            "TARGET CHAPTER SCOPE: {$sectionFilterText}\n" .
            "USER REFINEMENT INSTRUCTIONS: {$userFeedback}\n" .
            "STRUCTURAL FREEDOM DIRECTIVE: You HAVE FULL FREEDOM to rewrite, rename headings, change section titles, format into bullet lists or numbered items, add new subsections, or reorganize content as instructed by the user.\n" .
            "You MUST use English markers [SECTION: Section Name] before every section you write or update so they can be parsed.";

        $prompt = "Refine the research proposal '{$proposal->title}' based on user feedback: {$userFeedback}\n" .
            "Existing proposal sections:\n";
        foreach ($existingContent as $title => $body) {
            if ($targetSection === 'all' || stripos($title, $targetSection) !== false || stripos($targetSection, 'ch') !== false) {
                $prompt .= "[SECTION: {$title}]\n" . \Illuminate\Support\Str::limit($body, 500) . "\n\n";
            }
        }

        $refinedContent = [];
        $this->batchProcess($prompt, $refinedContent, $systemPrompt);

        if (!empty($refinedContent)) {
            if ($targetSection !== 'all') {
                $matchedKey = null;
                foreach ($existingContent as $key => $val) {
                    if (
                        stripos($key, $targetSection) !== false ||
                        ($targetSection === 'preliminaries' && (stripos($key, 'abstract') !== false || stripos($key, 'term') !== false || stripos($key, 'prelim') !== false)) ||
                        ($targetSection === 'ch1' && (stripos($key, 'ch1') !== false || stripos($key, 'chapter 1') !== false || stripos($key, 'introduction') !== false)) ||
                        ($targetSection === 'ch2' && (stripos($key, 'ch2') !== false || stripos($key, 'chapter 2') !== false || stripos($key, 'literature') !== false)) ||
                        ($targetSection === 'ch3' && (stripos($key, 'ch3') !== false || stripos($key, 'chapter 3') !== false || stripos($key, 'methodology') !== false)) ||
                        ($targetSection === 'budget' && (stripos($key, 'budget') !== false || stripos($key, 'cost') !== false))
                    ) {
                        $matchedKey = $key;
                        break;
                    }
                }

                if ($matchedKey) {
                    $existingContent[$matchedKey] = implode("\n\n", $refinedContent);
                } else {
                    $existingContent = array_merge($existingContent, $refinedContent);
                }
            } else {
                $existingContent = array_merge($existingContent, $refinedContent);
            }
            $proposal->update(['content' => $existingContent]);
        }

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
                    // Clean out raw markdown formatting (asterisks, hashtags, backticks)
                    $body = preg_replace('/\*{1,3}/', '', $body);
                    $body = preg_replace('/^#+\s+/m', '', $body);
                    $body = str_replace('`', '', $body);
                    $contentArray[$title] = trim($body);
                }
            }
        }
    }
}
