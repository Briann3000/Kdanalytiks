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
     * Build the Canonical Construct Registry from user parameters.
     */
    public function buildConstructRegistry(array $params): array
    {
        $rawIndepVars = !empty($params['independent_variables']) && is_array($params['independent_variables'])
            ? array_values(array_filter(array_map('trim', $params['independent_variables'])))
            : ['Independent Factor 1', 'Independent Factor 2'];

        $depVars = !empty($params['dependent_variables']) && is_array($params['dependent_variables'])
            ? array_values(array_filter(array_map('trim', $params['dependent_variables'])))
            : ['Primary Study Outcome'];

        $title = strtolower($params['title'] ?? '');

        // Universal Econometric Deduplication:
        // If a user selected both an overarching umbrella term (e.g. "Mobile Money Usage") AND specific operational dimensions (e.g. "Transaction Frequency", "Transaction Volume"),
        // prune the redundant overarching umbrella term from the regression model so it doesn't cause multicollinearity.
        $indepVars = $rawIndepVars;
        if (count($rawIndepVars) >= 3) {
            $filtered = [];
            foreach ($rawIndepVars as $var) {
                $varLower = strtolower($var);
                $isExactUmbrellaOfOtherDimensions = false;

                // If this variable is basically identical to the broad topic prefix and we have other distinct operational dimensions, filter it
                if (in_array($varLower, ['mobile money usage', 'mobile-money usage', 'e-commerce adoption', 'digital marketing', 'corporate governance', 'esg practices'])) {
                    $isExactUmbrellaOfOtherDimensions = true;
                }

                if (!$isExactUmbrellaOfOtherDimensions) {
                    $filtered[] = $var;
                }
            }
            if (count($filtered) >= 2) {
                $indepVars = array_values($filtered);
            }
        }

        $scale = $params['measurement_scale'] ?? '5-point Likert Scale (1 = Strongly Disagree to 5 = Strongly Agree)';
        $methodology = $params['methodology_type'] ?? 'mixed';

        $constructs = [];
        $i = 1;
        foreach ($indepVars as $iv) {
            $constructs[] = [
                'id' => "IV_{$i}",
                'code' => "X_{$i}",
                'name' => trim($iv),
                'type' => 'independent',
                'role' => 'predictor',
                'measurement_scale' => $scale
            ];
            $i++;
        }

        $j = 1;
        foreach ($depVars as $dv) {
            $constructs[] = [
                'id' => "DV_{$j}",
                'code' => count($depVars) === 1 ? "Y" : "Y_{$j}",
                'name' => trim($dv),
                'type' => 'dependent',
                'role' => 'outcome',
                'measurement_scale' => $scale
            ];
            $j++;
        }

        $primaryTool = 'Multiple Linear Regression';
        if ($methodology === 'qualitative') {
            $primaryTool = 'Thematic Content Analysis (Braun & Clarke Framework)';
        } elseif ($methodology === 'descriptive') {
            $primaryTool = 'Descriptive Statistics & Cross-Tabulations';
        }

        return [
            'constructs' => $constructs,
            'structural_model' => [
                'primary_analysis_tool' => $primaryTool,
                'methodology_type' => $methodology,
                'advanced_methods_permitted' => false,
                'theories' => !empty($params['theories']) && is_array($params['theories']) ? array_values(array_filter($params['theories'])) : []
            ]
        ];
    }

    /**
     * Generate full formal proposal document.
     */
    /**
     * Build the Canonical Alignment Matrix (Objective -> RQ -> Hypotheses -> Model Term)
     * Enforces mathematical discipline: K=1 -> Simple Regression; K>=2 -> Multiple Regression + Joint Fit
     */
    /**
     * Build the Canonical Academic Alignment Matrix (Objectives, RQs, Hypotheses)
     */
    public function buildAlignmentMatrix(array $registry, string $location = 'the study area', string $targetPop = ''): array
    {
        $matrix = [];
        $ivList = [];
        $dvName = 'Primary Outcome';

        foreach ($registry['constructs'] as $c) {
            if ($c['type'] === 'dependent') {
                $dvName = $c['name'];
                break;
            }
        }

        // Clean target population for natural grammatical inclusion
        $popPhrase = '';
        if (!empty($targetPop)) {
            $cleaned = trim($targetPop);
            // If targetPop is generic placeholder like 'target respondents and key institutional actors', refine it
            if (stripos($cleaned, 'target respondents') !== false || stripos($cleaned, 'target population') !== false) {
                $cleaned = preg_replace('/^(target\s+respondents(\s+and\s+key\s+institutional\s+actors)?|target\s+population(\s+respondents)?)/i', 'study participants', $cleaned);
            }
            $popPhrase = " among " . lcfirst(rtrim($cleaned, '.'));
        }

        $locPhrase = !empty($location) ? " in {$location}" : "";

        $idx = 1;
        foreach ($registry['constructs'] as $c) {
            if ($c['type'] === 'independent' || $c['role'] === 'predictor') {
                $ivList[] = $c['name'];
                $ivName = $c['name'];
                $code = $c['code'];

                $matrix[] = [
                    'index' => $idx,
                    'construct' => $ivName,
                    'code' => $code,
                    'role' => $c['role'],
                    'objective' => "To determine the effect of {$ivName} on {$dvName}{$popPhrase}{$locPhrase}.",
                    'rq' => "What is the effect of {$ivName} on {$dvName}{$popPhrase}{$locPhrase}?",
                    'null_type' => "Null Hypothesis (H₀{$idx})",
                    'alt_type' => "Alternative Hypothesis (Hₐ{$idx})",
                    'null_hypothesis' => "There is no statistically significant relationship between {$ivName} and {$dvName}{$popPhrase}{$locPhrase} (β{$idx} = 0).",
                    'alt_hypothesis' => "There is a statistically significant relationship between {$ivName} and {$dvName}{$popPhrase}{$locPhrase} (β{$idx} ≠ 0).",
                    'model_term' => "\\beta_{$idx}(\\text{" . addslashes($ivName) . "})"
                ];
                $idx++;
            }
        }

        $k = count($ivList);
        $isMultiple = $k >= 2;
        $modelType = $isMultiple ? 'Multiple Linear Regression' : 'Simple Linear Regression';

        // Add Joint / Overall Model Fit ONLY IF K >= 2
        if ($isMultiple) {
            $jointBetas = implode(' = ', array_map(fn($i) => "β{$i}", range(1, $k))) . ' = 0';
            $jointObjective = "To evaluate the overall combined effect of the study predictor dimensions in explaining {$dvName}{$popPhrase}{$locPhrase}.";
            $jointRQ = "To what extent do the study predictor dimensions collectively explain variations in {$dvName}{$popPhrase}{$locPhrase}?";
            $jointNull = "The study predictor dimensions collectively have no statistically significant joint effect on {$dvName} ({$jointBetas}).";
            $jointAlt = "The study predictor dimensions collectively have a statistically significant joint effect on {$dvName} (at least one βⱼ ≠ 0).";

            $matrix[] = [
                'index' => $idx,
                'construct' => 'Composite Model (' . implode(', ', $ivList) . ')',
                'code' => 'Joint',
                'role' => 'composite',
                'objective' => $jointObjective,
                'rq' => $jointRQ,
                'null_type' => "Null Joint Hypothesis (H₀{$idx})",
                'alt_type' => "Alternative Joint Hypothesis (Hₐ{$idx})",
                'null_hypothesis' => $jointNull,
                'alt_hypothesis' => $jointAlt,
                'model_term' => 'Full Regression Model'
            ];
        }

        $activeTerms = $isMultiple ? array_slice($matrix, 0, -1) : $matrix;
        $equationTerms = array_map(fn($r) => "{$r['model_term']}", $activeTerms);
        $equation = "Y = \\beta_0 + " . implode(' + ', $equationTerms) . " + \\varepsilon";

        return [
            'rows' => $matrix,
            'dv_name' => $dvName,
            'iv_names' => $ivList,
            'k' => $k,
            'is_multiple' => $isMultiple,
            'model_type' => $modelType,
            'equation' => $equation
        ];
    }

    public function generateProposal(ResearchProposal $proposal)
    {
        set_time_limit(900);
        $generatedContent = [];
        $style = $proposal->style ?? 'APA 7th';

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

        $systemPrompt = "You are a distinguished Professor and Senior Academic Research Methodologist. " .
            "Transform the provided research parameters into an exhaustive, rigorous, submission-ready academic research proposal. " .
            "Academic Citation & Referencing Standard: {$style}. " .
            "PROPOSAL BOUNDARY RULE: This is a formal RESEARCH PROPOSAL (pre-study plan). Generate ONLY Front Matter, Chapter 1, Chapter 2, Chapter 3, References, Budget, and Appendices. DO NOT generate Chapter 4 (Findings) or Chapter 5 (Conclusions), as empirical data has not yet been collected. " .
            "TENSE DIRECTIVE: Use FUTURE TENSE ('will be collected', 'will be surveyed', 'will be conducted') for all proposed methodology procedures to be executed by the researcher. Use PRESENT TENSE for established facts, theoretical definitions, and conceptual foundations. " .
            "CITATION & REFERENCE REALISM: DO NOT fabricate fake scholar names or nonexistent publication years. Anchor theoretical frameworks to verified seminal founders (e.g., Davis, 1989 for TAM; Scott, 2014 for Institutional Theory; Mol & Spaargaren, 2000 for Ecological Modernization; Creswell & Creswell, 2018 for Research Design; Cochran, 1977 & Yamane, 1967 for Sampling; KNBS, 2022; UNEP; World Bank). " .
            "FORMATTING & TYPOGRAPHY DIRECTIVE: Use standard academic Markdown formatting: " .
            "- Use '# CHAPTER [N]: [TITLE]' for major chapter titles. " .
            "- Use '## [N.X] [Subheading Title]' for major sub-sections. " .
            "- Use Markdown Tables ('| Header | Header |') for all structured data. " .
            "- Use bolding ('**term**') for key constructs and definitions. " .
            "CRITICAL: You MUST use the exact English marker '[SECTION: Section Name]' before every section you write. " .
            "Write the entire CONTENT of the sections in {$language}.";

        if (!empty($proposal->custom_instructions)) {
            $systemPrompt .= "\n\nSUPERVISOR GUIDELINES & CUSTOM PRESET INSTRUCTIONS:\n{$proposal->custom_instructions}\nStrictly adhere to these guidelines throughout.";
        }

        // 1. PRELIMINARIES (Front Matter)
        $p0 = $this->buildPreliminariesPrompt($proposal);
        $this->batchProcess($p0, $generatedContent, $systemPrompt);

        // 2. CHAPTER 1
        sleep(1);
        $p1 = $this->buildChapter1Prompt($proposal);
        $this->batchProcess($p1, $generatedContent, $systemPrompt);

        // 3. CHAPTER 2
        sleep(1);
        $p2 = $this->buildChapter2Prompt($proposal);
        $this->batchProcess($p2, $generatedContent, $systemPrompt);

        // 4. CHAPTER 3
        sleep(1);
        $p3 = $this->buildChapter3Prompt($proposal);
        $this->batchProcess($p3, $generatedContent, $systemPrompt);

        // 5. PROPOSED BUDGET & WORK PLAN
        $pBudget = $this->buildBudgetPrompt($proposal);
        if ($pBudget) {
            $this->batchProcess($pBudget, $generatedContent, $systemPrompt);
        }

        // 6. REFERENCES & APPENDICES
        sleep(1);
        $p6 = $this->buildAppendixPrompt($proposal);
        $this->batchProcess($p6, $generatedContent, $systemPrompt);

        $proposal->update([
            'content' => $generatedContent,
            'status' => 'completed'
        ]);

        return $proposal;
    }

    /**
     * Modular Prompt Builder: Preliminary Pages
     */
    public function buildPreliminariesPrompt(ResearchProposal $proposal): string
    {
        return "Draft PRELIMINARY PAGES (Front Matter) for the academic proposal titled '{$proposal->title}':\n" .
            "Use markers [SECTION: Name] for each part:\n\n" .
            "[SECTION: Title Page]\n" .
            "# {$proposal->title}\n\n" .
            "A Research Proposal Submitted in Partial Fulfillment of the Requirements for the Degree...\n" .
            "Researcher: [Student / Principal Investigator]\n" .
            "Department & Institution: Academic Faculty of Research & Graduate Studies\n" .
            "Date: " . date('F Y') . "\n\n" .
            "[SECTION: Declaration]\n" .
            "Formal declaration of original academic work and supervisor endorsement statements.\n\n" .
            "[SECTION: Abstract]\n" .
            "## Abstract\n" .
            "An academic abstract of exactly 180–200 words synthesizing: (1) Background context and core problem, (2) General and specific objectives, (3) Theoretical anchoring, (4) Proposed methodology, target population, and sample size, and (5) Expected scholarly and policy contributions.\n" .
            "Keywords: 5–6 formal academic keywords.\n\n" .
            "[SECTION: Abbreviations and Acronyms]\n" .
            "## List of Abbreviations and Acronyms\n" .
            "Alphabetical list of 6–10 abbreviations and institutional acronyms used in the study.\n\n" .
            "[SECTION: Definition of Key Terms]\n" .
            "## Operational Definition of Key Terms\n" .
            "Provide formal, in-depth operational definitions for 5–7 central study constructs (independent variables, dependent variables, and institutional concepts). Write definitions in PRESENT TENSE.";
    }

    /**
     * Modular Prompt Builder: Chapter 1 Introduction
     */
    public function buildChapter1Prompt(ResearchProposal $proposal): string
    {
        return "Draft an exhaustive, high-depth CHAPTER 1: INTRODUCTION for '{$proposal->title}':\n" .
            "Context / Problem: {$proposal->research_question}\n" .
            "Objectives: {$proposal->objectives}\n" .
            "Scope / Population: {$proposal->scope}\n\n" .
            "Write comprehensive, substantive paragraphs. Use the following exact markers:\n\n" .
            "[SECTION: 1.1 Background to the Study]\n" .
            "# CHAPTER 1: INTRODUCTION\n\n" .
            "## 1.1 Background to the Study\n" .
            "Write an exhaustive 4–5 paragraph continuous funnel background (DO NOT insert 1.1.1, 1.1.2 subheadings). Progress logically: (1) Global empirical benchmarks, (2) Regional (Sub-Saharan Africa) dynamics, (3) National & Local Context in target geography.\n\n" .
            "[SECTION: 1.2 Statement of the Problem]\n" .
            "## 1.2 Statement of the Problem\n" .
            "Write a formal 4-part problem statement: (1) Ideal condition, (2) Ground empirical reality, (3) Dual Gap Analysis: What is known -> What remains unknown -> Why it matters -> Consequences of continued inaction (human, community & socio-economic fallout), (4) Study Intervention.\n\n" .
            "[SECTION: 1.3 Purpose of the Study]\n" .
            "## 1.3 Purpose of the Study\n" .
            "Clear, formal statement of overarching purpose.\n\n" .
            "[SECTION: 1.4 Specific Research Objectives]\n" .
            "## 1.4 Specific Research Objectives\n" .
            "Formulate 3–4 specific, construct-aligned, empirically researchable objectives evaluating each predictor.\n\n" .
            "[SECTION: 1.5 Research Questions & Hypotheses]\n" .
            "## 1.5 Research Questions and Research Hypotheses\n" .
            "List corresponding research questions and paired directional research hypotheses.\n\n" .
            "[SECTION: 1.6 Justification and Significance of the Study]\n" .
            "## 1.6 Significance & Justification of the Study\n" .
            "Detailed justification for Policy Makers, Industry Stakeholders, and Academicians.\n\n" .
            "[SECTION: 1.7 Scope and Delimitations of the Study]\n" .
            "## 1.7 Scope and Delimitations of the Study\n" .
            "Geographical, conceptual, and methodological scope (accurately aligned with the chosen design).\n\n" .
            "[SECTION: 1.8 Limitations of the Study and Mitigation Strategies]\n" .
            "## 1.8 Limitations of the Study and Mitigation Strategies\n" .
            "Methodological limitations and realistic field mitigation protocols.\n\n" .
            "[SECTION: 1.9 Operational Definition of Key Terms]\n" .
            "## 1.9 Operational Definition of Key Terms\n" .
            "Precise operational definitions for all core variables.";
    }

    /**
     * Modular Prompt Builder: Chapter 2 Literature Review
     */
    public function buildChapter2Prompt(ResearchProposal $proposal): string
    {
        return "Draft an exhaustive CHAPTER 2: LITERATURE REVIEW for '{$proposal->title}':\n" .
            "Directives & Objectives: {$proposal->objectives}\n\n" .
            "[SECTION: 2.1 Theoretical Framework]\n" .
            "# CHAPTER 2: LITERATURE REVIEW\n\n" .
            "## 2.1 Introduction\n\n" .
            "## 2.2 Theoretical Framework\n" .
            "Exhaustive theoretical review of relevant theories, propositions, and operational links.\n\n" .
            "[SECTION: 2.2 Conceptual Framework]\n" .
            "## 2.3 Conceptual Framework\n" .
            "Provide a formal Mermaid diagram enclosed in ```mermaid code block (all node labels in double quotes) followed by narrative interaction pathways.\n\n" .
            "[SECTION: 2.3 Empirical Literature Review]\n" .
            "## 2.4 Empirical Literature Review\n" .
            "Synthesize empirical studies construct-by-construct across global, regional, and local contexts.\n\n" .
            "[SECTION: 2.4 Critique and Research Gaps]\n" .
            "## 2.5 Critique of Existing Literature and Knowledge Gaps\n" .
            "Detailed critique with unordered bullet lists under: (1) Conceptual Limitations, (2) Methodological Shortcomings, (3) Contextual Gaps.\n\n" .
            "[SECTION: 2.5 Summary of Literature Gaps Matrix]\n" .
            "## 2.6 Summary of Literature Gaps Matrix Table\n" .
            "Comprehensive 6-column Markdown table summarizing prior empirical studies and identified gaps.";
    }

    /**
     * Modular Prompt Builder: Chapter 3 Research Methodology
     */
    public function buildChapter3Prompt(ResearchProposal $proposal): string
    {
        $instructions = $proposal->custom_instructions ?? '';
        $objectives = $proposal->objectives ?? '';
        return "Draft an exhaustive CHAPTER 3: RESEARCH METHODOLOGY for '{$proposal->title}':\n" .
            "Methodology Type: {$proposal->methodology_type}\n" .
            "Scope / Demographics: {$proposal->scope}\n" .
            "Study Objectives & Variables: {$objectives}\n" .
            "Directives & Theories: {$instructions}\n\n" .
            "CRITICAL VARIABLE MAPPING: Derive all variables (Y and X1, X2, X3) directly from the study title and objectives ({$objectives}).\n\n" .
            "[SECTION: 3.1 Research Design & Philosophy]\n" .
            "# CHAPTER 3: RESEARCH METHODOLOGY\n\n" .
            "## 3.1 Research Philosophy and Design\n" .
            "Epistemological philosophy and design justification aligned with {$proposal->methodology_type}.\n\n" .
            "[SECTION: 3.2 Target Population & Sampling]\n" .
            "## 3.2 Target Population\n" .
            "Target population definition and breakdown table.\n\n" .
            "## 3.3 Sampling Design and Sample Size Determination\n" .
            "Step-by-step mathematical calculation using Yamane or Cochran formula.\n\n" .
            "## 3.4 Sampling Technique and Allocation\n" .
            "Sampling strategy and stratified allocation table.\n\n" .
            "[SECTION: 3.3 Data Collection & Instruments]\n" .
            "## 3.5 Data Collection Instruments\n" .
            "Questionnaire and interview protocol design.\n\n" .
            "## 3.6 Pilot Testing Procedures\n" .
            "10% pilot protocol in a neighboring context.\n\n" .
            "## 3.7 Validity and Reliability Protocols\n" .
            "Content Validity Index (CVI >= 0.80) and Cronbach's Alpha (>= 0.70).\n\n" .
            "## 3.8 Ethical Approvals & Field Administration\n" .
            "Voluntary informed consent, confidentiality, and statutory licensing.\n\n" .
            "[SECTION: 3.4 Data Analysis & Operationalization Matrix]\n" .
            "## 3.9 Data Analysis and Statistical Modeling\n" .
            "Descriptive and inferential analysis plan with Multiple Linear Regression equation mapping active variables.\n\n" .
            "## 3.10 Variable Operationalization Matrix Table\n" .
            "Exhaustive 7-column Markdown Table operationalizing every specific objective and variable construct.";
    }

    /**
     * Modular Prompt Builder: Proposed Budget & Work Plan
     */
    public function buildBudgetPrompt(ResearchProposal $proposal): ?string
    {
        $budget = $proposal->budget ?? [];
        $budgetRows = "";
        $totalCost = 0;

        if (!empty($budget)) {
            foreach ($budget as $b) {
                if (is_array($b) && !empty($b['item'])) {
                    $cost = (float) ($b['cost'] ?? 0);
                    $totalCost += $cost;
                    $budgetRows .= "| " . trim($b['item']) . " | 1 Package | " . number_format($cost, 2) . " | " . number_format($cost, 2) . " | Operational research allocation |\n";
                }
            }
        } else {
            $budgetRows .= "| Fieldwork Enumerator Logistics & Transport | 5 Enumerators × 10 Days | 100,000.00 | 100,000.00 | Daily stipends and transit across study clusters |\n";
            $budgetRows .= "| Encrypted Survey Cloud Hosting & Data Bundles | 12 Months Hosting | 25,000.00 | 25,000.00 | Secure mobile data collection server & real-time synchronization |\n";
            $budgetRows .= "| Statutory Research Permit & Ethical Review | 1 Permit + 1 IRB Review | 20,000.00 | 20,000.00 | Official research permit and institutional review board fees |\n";
            $budgetRows .= "| Statistical Software Licensing & Computing | 2 Workstations | 50,000.00 | 100,000.00 | Specialized statistical computing software licenses |\n";
            $totalCost = 245000;
        }
        $formattedTotal = number_format($totalCost, 2);

        return "Draft PROPOSED BUDGET & 12-MONTH WORK PLAN for: '{$proposal->title}'\n\n" .
            "Use markers [SECTION: Name]:\n\n" .
            "[SECTION: 4.1 Proposed Budget Breakdown Table]\n" .
            "# PROPOSED BUDGET AND WORK PLAN\n\n" .
            "## 4.1 Proposed Budget Breakdown Table\n" .
            "| Item Category & Description | Quantity / Units | Unit Cost (KES) | Total Cost (KES) | Justification & Budget Notes |\n" .
            "| :--- | :---: | :---: | :---: | :--- |\n" .
            "{$budgetRows}" .
            "| **Grand Total Proposed Budget** | — | — | **KES {$formattedTotal}** | **Total study operational allocation** |\n\n" .
            "[SECTION: 4.2 12-Month Work Plan Timeline]\n" .
            "## 4.2 12-Month Work Plan Timeline (Gantt Milestone Schedule)\n" .
            "Provide a compact Markdown Gantt Table using M1 to M12 columns marking active milestone months with '✓'.";
    }

    /**
     * Modular Prompt Builder: References & Measurement Instrument Appendices
     */
    public function buildAppendixPrompt(ResearchProposal $proposal): string
    {
        $style = $proposal->style ?? 'APA 7th';
        return "Draft REFERENCES and comprehensive APPENDICES for '{$proposal->title}':\n" .
            "Style: {$style}\n\n" .
            "Use the following exact markers:\n\n" .
            "[SECTION: REFERENCES]\n" .
            "# REFERENCES\n" .
            "Generate 20–25 authentic academic references strictly conforming to {$style} formatting directly relevant to this study.\n\n" .
            "[SECTION: APPENDIX A: Research Questionnaire]\n" .
            "# APPENDIX A: RESEARCH QUESTIONNAIRE\n" .
            "Draft an authentic, full-scale 25–35 item measurement questionnaire formatted with:\n" .
            "- Introduction and Voluntary Informed Consent Statement (no conditional compensation)\n" .
            "- **Section A**: Demographic and Contextual Profile\n" .
            "- **Section B**: Independent Variable Construct Scales (4–5 Likert-scale items per variable: 1 = Strongly Disagree to 5 = Strongly Agree)\n" .
            "- **Section C**: Institutional Governance & Moderator Scales (if applicable)\n" .
            "- **Section D**: Dependent Outcome Variables (4–5 Likert items)\n\n" .
            "[SECTION: APPENDIX B: Key Informant Interview Guide]\n" .
            "# APPENDIX B: KEY INFORMANT INTERVIEW GUIDE\n" .
            "Draft 8–10 in-depth qualitative interview prompts tailored to key institutional directors, policy makers, and community stakeholder leaders.";
    }

    /**
     * Refine/Regenerate an existing proposal using user feedback.
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
            "Use Markdown headings ('#', '##', '###') and tables ('| Col | Col |').\n" .
            "You MUST use English markers [SECTION: Section Name] before every section you write or update.";

        $prompt = "Refine the research proposal '{$proposal->title}' based on user feedback: {$userFeedback}\n" .
            "Existing proposal sections:\n";
        foreach ($existingContent as $title => $body) {
            if ($targetSection === 'all' || stripos($title, $targetSection) !== false || stripos($targetSection, 'ch') !== false) {
                $prompt .= "[SECTION: {$title}]\n" . \Illuminate\Support\Str::limit($body, 500) . "\n\n";
            }
        }

        $refinedContent = [];
        $this->batchProcess($prompt, $refinedContent, $systemPrompt);

        foreach ($refinedContent as $title => $body) {
            $existingContent[$title] = $body;
        }

        $proposal->update([
            'content' => $existingContent
        ]);

        return $proposal;
    }

    /**
     * Batch process a prompt and parse into section keys.
     */
    private function batchProcess($prompt, &$contentArray, $systemPrompt)
    {
        $response = $this->aiService->callAi($prompt, $systemPrompt);
        if ($response) {
            $parts = preg_split('/\[SECTION:\s*([^\]]+)\]/i', $response, -1, PREG_SPLIT_DELIM_CAPTURE);
            for ($i = 1; $i < count($parts); $i += 2) {
                $title = trim($parts[$i]);
                $body = trim($parts[$i + 1] ?? '');
                if ($title && $body) {
                    $body = preg_replace('/^\s*Word count:\s*\d+\s*$/im', '', $body);
                    $body = preg_replace('/^\s*End of Document\.?\s*$/im', '', $body);
                    $body = trim($body);
                    $contentArray[$title] = $body;
                }
            }
        }
    }

    /**
     * Generate a deep, defense-grade single-stage live preview.
     */
    public function generateSingleStagePreview(array $params, int $stage): string
    {
        set_time_limit(300);
        $registry = $this->buildConstructRegistry($params);

        $style = $params['style'] ?? 'APA 7th';
        $title = $params['title'] ?? 'Research Proposal';
        $problem = $params['problem_statement'] ?? $title;
        $domain = $params['domain'] ?? 'Social Sciences & Development';
        $location = $params['target_location'] ?? 'Target Study Location';

        $indepVarsArray = [];
        $depVarsArray = [];
        foreach ($registry['constructs'] as $c) {
            if ($c['type'] === 'independent')
                $indepVarsArray[] = $c['name'];
            if ($c['type'] === 'dependent')
                $depVarsArray[] = $c['name'];
        }

        $indepVars = implode(', ', $indepVarsArray);
        $depVars = implode(', ', $depVarsArray);
        $theories = !empty($registry['structural_model']['theories']) ? implode(', ', $registry['structural_model']['theories']) : 'Theoretical frameworks directly relevant to the topic';
        $methodology = $registry['structural_model']['methodology_type'];
        $primaryTool = $registry['structural_model']['primary_analysis_tool'];

        $popSizeRaw = $params['population_size'] ?? 1000;
        $sampleSizeRaw = $params['sample_size'] ?? 286;
        $popSize = is_numeric($popSizeRaw) ? number_format((float) $popSizeRaw) : $popSizeRaw;
        $sampleSize = is_numeric($sampleSizeRaw) ? number_format((float) $sampleSizeRaw) : $sampleSizeRaw;

        $targetPop = !empty($params['target_population']) ? $params['target_population'] : 'target population respondents and key institutional actors';
        $samplingStrategy = $params['sampling_strategy'] ?? 'Stratified Random Sampling';
        $measurementScale = $params['measurement_scale'] ?? '5-point Likert Scale (1 = Strongly Disagree to 5 = Strongly Agree)';
        $instrumentModes = !empty($params['data_collection_modes']) && is_array($params['data_collection_modes'])
            ? implode(', ', $params['data_collection_modes'])
            : 'Structured Questionnaires and Key Informant Interviews';

        $baseSystemPrompt = "You are a distinguished Professor and Senior Academic Research Methodologist. " .
            "Draft an exhaustive, publication-grade academic proposal section in strict {$style} standard. " .
            "ACADEMIC CONTENT LANGUAGE RULE: Generate the entire chapter narrative, headings, and tables in the SAME primary language used in the study title and problem statement (e.g., French if title/problem is French, Swahili if title/problem is Swahili, English if English). " .
            "TENSE DIRECTIVE: Use FUTURE TENSE ('will be investigated', 'will be surveyed') for proposed methodology to be conducted. Use PRESENT TENSE for established theories, definitions, and facts. " .
            "TYPOGRAPHY: Use '#', '##', '###', and clean Markdown Tables ('| Header | Header |'). Output clean Markdown directly without conversational filler or 'Word count:'. " .
            "MATHEMATICAL NOTATION: Format all formulas and regression equations in valid display math delimiters ('$$ ... $$') with clear plain-text/Unicode representation (e.g. '$$ n = \\frac{N}{1 + N(e)^2} $$', 'n = N / [1 + N(e)²]', 'Y = β₀ + β₁X₁ + β₂X₂ + β₃X₃ + ε') so they render cleanly in both HTML KaTeX preview and Microsoft Word exports.";

        $citationConstraint = "\n\nCRITICAL CITATION & METHODOLOGICAL BOUNDARY RULES (STRICTLY ENFORCED):\n" .
            "1. NO FABRICATED STATISTICS: DO NOT invent exact micro percentages (e.g., '85%', '34% GDP'), specific monetary losses (e.g., 'KES 2.3 billion'), or fake survey figures. Express empirical trends in qualitative academic prose or cite verified high-level institutional baselines (e.g., KNBS, World Bank, Central Bank reports) in broad, defensible terms without inventing precise micro-data.\n" .
            "2. ONLY cite seminal authors genuinely relevant to the chosen theories ({$theories}) and academic domain ({$domain}). Derive theoretical scholars strictly from the study topic.\n" .
            "3. Methodological/statistical authors (Creswell, Cochran, Yamane, Saunders, Mugenda) MUST ONLY be cited in Chapter 3 for research design, formulas, and validity. NEVER cite methodologists or statisticians for empirical business, economic, or industry facts in Chapters 1 or 2.\n" .
            "4. SAMPLING RIGOR: Do NOT use contradictory phrases like 'purposive stratification enhances representativeness'. If probability sampling (Stratified Random Sampling), justify for statistical representativeness; if non-probability (Purposive), justify for qualitative information-rich depth.\n" .
            "4. METHODOLOGICAL INTEGRITY RULE: The study methodology is locked as: '{$methodology}'.\n" .
            ($methodology === 'mixed' ? "   - MUST describe BOTH quantitative survey instruments and qualitative in-depth interviews.\n   - MUST state the integration mechanism (e.g., convergent parallel design).\n   - DO NOT claim that qualitative nuances or subjective perspectives are excluded.\n" : "") .
            ($methodology === 'quantitative' ? "   - Focus strictly on numerical survey scales, hypotheses, frequencies, and inferential regression modeling.\n   - DO NOT claim qualitative focus groups or thematic saturation.\n" : "") .
            ($methodology === 'qualitative' ? "   - Focus strictly on qualitative depth, lived experiences, thematic analysis, and interview protocols.\n   - DO NOT claim regression modeling, p-values, or statistical generalizability.\n" : "") .
            "5. ECONOMETRIC & DATA ACCESS REALISM: The locked primary statistical analysis tool is '{$primaryTool}'. DO NOT casually invent unconfigured advanced statistical methods (e.g., do NOT invent SEM, PLS-SEM, Instrumental Variable regression, Propensity Score Matching, or Factor Analysis). DO NOT promise access to confidential bank/mobile money transaction databases unless explicitly specified as a data source.\n" .
            "6. ETHICAL INTEGRITY & CONSENT: Present participant involvement as strictly voluntary and based on informed consent. DO NOT promise conditional cash/financial compensation or automatic monetary incentives.\n" .
            "7. NO REFERENCE LIST: Do NOT append any 'References', 'Bibliography', or citation list at the end of this chunk. Output ONLY the requested numbered sections.\n" .
            "8. TABLE FORMATTING DIRECTIVE: Output all Markdown tables in strict single-line-per-row syntax ('| Col 1 | Col 2 |'). Do NOT insert raw line breaks, multiline paragraphs, or nested bullet points inside individual table cells.";

        if ($stage === 1) {
            // MICRO-CALL 1A: Continuous Funnel Background (Global -> Regional -> Local) & 4-Tier Problem Statement (~1,000 words)
            $prompt1A = "Draft CHAPTER 1 (PART 1: BACKGROUND & STATEMENT OF THE PROBLEM) for: '{$title}'\n" .
                "Academic Domain: {$domain} | Study Location: {$location}\n" .
                "Everyday Problem Description: {$problem}\n" .
                "Target Context: {$targetPop}\n" .
                "LOCKED Core Independent Variables: {$indepVars}\n" .
                "LOCKED Core Dependent Variable(s): {$depVars}\n" .
                "Theoretical Anchors: {$theories}\n\n" .
                "STRICT TOPIC & CONSTRUCT PRESERVATION RULE:\n" .
                "- The study is SOLELY and STRICTLY about '{$title}'.\n" .
                "- DO NOT substitute or invent unselected concepts such as 'strategic planning', 'resource allocation', 'operational capacity', 'training', 'service delivery', or 'program performance' unless those exact words appear in the study title.\n" .
                "- Focus the entire background narrative and problem strictly on ({$indepVars}) and ({$depVars}) in the context of {$targetPop} in {$location}.\n\n" .
                "Generate the following structured academic sections in deep, multi-paragraph prose:\n" .
                "# CHAPTER 1: INTRODUCTION\n\n" .
                "## 1.1 Background to the Study\n" .
                "Write an exhaustive, continuous 4–5 paragraph background narrative. DO NOT insert subheadings like 1.1.1, 1.1.2, or 1.1.3. Maintain a strict logical funnel progression across the paragraphs:\n" .
                "- Paragraph 1 (Global Perspective): International development benchmarks, global empirical trends, and international frameworks relevant to '{$title}'.\n" .
                "- Paragraph 2 (Regional Perspective - Sub-Saharan Africa): Continental dynamics, regional economic policies, and cross-country comparative evidence.\n" .
                "- Paragraphs 3–4 (National & Local Context in {$location}): Localized socio-economic landscape, commercial/administrative hubs in {$location}, verified macro statistics (e.g. KNBS/World Bank sector data), and lived daily tensions of {$targetPop}.\n\n" .
                "## 1.2 Statement of the Problem\n" .
                "Write a formal 4-part academic problem statement in 4 distinct, substantive paragraphs:\n" .
                "- Paragraph 1 (Ideal / Expected Condition): The optimal normative standard dictated by policy, technology, and industry best practices.\n" .
                "- Paragraph 2 (Empirical Ground Reality): The documented ground breakdowns, deficits, and operational bottlenecks observed among {$targetPop} in {$location}.\n" .
                "- Paragraph 3 (Dual Gap Analysis & Inaction Fallout): Follow a strict 4-step logic: What is known -> What remains unknown -> Why that gap matters -> Human, community, and socio-economic consequences of continued inaction.\n" .
                "- Paragraph 4 (The Study Intervention): Formulate the exact empirical and analytical contribution this investigation provides to resolve the problem.\n\n" .
                "CRITICAL CHAPTER 1 NUMERICAL RULE: DO NOT mention specific sample size calculations (n = {$sampleSize}) or finite sampling frames (N = {$popSize}) anywhere in Chapter 1. Sample size is mathematically calculated and justified in Chapter 3." .
                $citationConstraint;

            $res1A = $this->aiService->callAi($prompt1A, $baseSystemPrompt);

            $alignment = $this->buildAlignmentMatrix($registry, $location, $targetPop);
            $matrixRows = $alignment['rows'];
            $modelType = $alignment['model_type'];

            $objectivesText = "";
            $questionsText = "";
            $hypothesesTableText = "| Hypothesis Type | Statement |\n|---|---|\n";

            $objNum = 1;
            foreach ($matrixRows as $r) {
                $objectivesText .= "{$objNum}. {$r['objective']}\n";
                $questionsText .= "{$objNum}. {$r['rq']}\n";
                $hypothesesTableText .= "| {$r['null_type']} | {$r['null_hypothesis']} |\n";
                $hypothesesTableText .= "| {$r['alt_type']} | {$r['alt_hypothesis']} |\n";
                $objNum++;
            }

            $regressionFormula = $alignment['equation'];

            $totalObjCount = count($matrixRows);
            $numbersExample = implode(', ', array_map(fn($n) => "{$n}.", range(1, $totalObjCount)));

            // MICRO-CALL 1B: Objectives, Hypotheses, Justification, Scope, Limitations, Definitions (~900 words)
            $prompt1B = "Draft CHAPTER 1 (PART 2: OBJECTIVES, HYPOTHESES, SIGNIFICANCE, SCOPE, LIMITATIONS, DEFINITIONS) for: '{$title}'\n" .
                "Academic Domain: {$domain} | Location: {$location}\n" .
                "LOCKED Independent Variables: {$indepVars}\n" .
                "LOCKED Dependent Variable: {$depVars}\n" .
                "Target Population Context: {$targetPop}\n" .
                "Methodology Type: {$methodology}\n\n" .
                "STRICT TRACEABILITY & FORMATTING DIRECTIVES:\n" .
                "- Objectives (1.4): Output EXACTLY {$totalObjCount} numbered items ({$numbersExample}) matching the canonical list below. DO NOT add empty numbers. DO NOT prefix with nested numbers like '1. 1.1'.\n" .
                "- Research Questions (1.5): Output EXACTLY {$totalObjCount} numbered items ({$numbersExample}) matching the canonical list below. DO NOT add empty numbers.\n" .
                "- Research Hypotheses (1.6): Output the canonical Markdown table with exact columns '| Hypothesis Type | Statement |' where each hypothesis row contains the exact label like 'Null Hypothesis (H₀1)' and 'Alternative Hypothesis (Hₐ1)'.\n" .
                "- Statistical Model Labeling: This study utilizes a {$modelType}. State clearly: 'The {$modelType} is specified as:' followed by $$ {$regressionFormula} $$.\n\n" .
                "CANONICAL OBJECTIVES (OUTPUT VERBATIM AS A CLEAN ORDERED LIST WITH BLANK LINE AFTER EACH):\n{$objectivesText}\n\n" .
                "CANONICAL RESEARCH QUESTIONS (OUTPUT VERBATIM AS A CLEAN ORDERED LIST WITH BLANK LINE AFTER EACH):\n{$questionsText}\n\n" .
                "CANONICAL HYPOTHESES TABLE (OUTPUT VERBATIM AS MARKDOWN TABLE):\n{$hypothesesTableText}\n\n" .
                "LOCKED REGRESSION EQUATION:\n$$ {$regressionFormula} $$\n\n" .
                "Generate the following structured academic sections in full depth:\n" .
                "## 1.3 Purpose / General Objective of the Study\n" .
                "A formal academic statement of overarching purpose evaluating the effect of {$indepVars} on {$depVars} in {$location}.\n\n" .
                "## 1.4 Specific Research Objectives\n" .
                "Output the canonical substantive objectives verbatim as a clean numbered list ({$numbersExample}).\n\n" .
                "## 1.5 Research Questions\n" .
                "Output the canonical research questions verbatim as a clean numbered list ({$numbersExample}).\n\n" .
                "## 1.6 Research Hypotheses\n" .
                "Output the canonical Hypotheses table verbatim, followed by the {$modelType} formula $$ {$regressionFormula} $$.\n\n" .
                "## 1.7 Significance & Justification of the Study\n" .
                "Write comprehensive justification under 3 distinct subheadings:\n" .
                "### 1.7.1 To Policy Makers and Government Authorities\n" .
                "### 1.7.2 To Industry Practitioners, Enterprises, and Community Stakeholders\n" .
                "### 1.7.3 To Academicians and Future Researchers\n\n" .
                "## 1.8 Scope and Delimitations of the Study\n" .
                "Detail geographical scope in {$location}, conceptual boundaries based on ({$indepVars}, {$depVars}), and methodological scope aligned strictly with {$methodology} design.\n\n" .
                "## 1.9 Limitations of the Study and Mitigation Strategies\n" .
                "Detail 3–4 specific methodological limitations and realistic mitigation protocols.\n\n" .
                "## 1.10 Operational Definition of Key Terms\n" .
                "Provide precise operational definitions for all core variables ({$indepVars}, {$depVars}) and contextual constructs as used specifically in this study." .
                $citationConstraint;

            $res1B = $this->aiService->callAi($prompt1B, $baseSystemPrompt);

            $combined = $this->cleanOutput($res1A) . "\n\n" . $this->cleanOutput($res1B);
            return $this->cleanOutput($combined);

        } elseif ($stage === 2) {
            // MICRO-CALL 2A: Theoretical Framework & Conceptual Framework Diagram (~1,100 words)
            $prompt2A = "Draft CHAPTER 2 (PART 1: THEORETICAL & CONCEPTUAL FRAMEWORK) for: '{$title}'\n" .
                "Domain: {$domain}\n" .
                "Theoretical Anchors: {$theories}\n" .
                "Locked Independent Variables: {$indepVars}\n" .
                "Locked Dependent Variables: {$depVars}\n\n" .
                "BOUNDARY DIRECTIVE: Generate ONLY Sections 2.1, 2.2, and 2.3. DO NOT generate Section 2.4, 2.5, or 2.6 in this part.\n\n" .
                "Generate the following structured academic sections in exhaustive depth:\n" .
                "# CHAPTER 2: LITERATURE REVIEW\n\n" .
                "## 2.1 Introduction\n" .
                "Overview of literature review scope and thematic architecture.\n\n" .
                "## 2.2 Theoretical Framework\n" .
                "Write an exhaustive, multi-page theoretical grounding reviewing ONLY the theoretical anchors relevant to this study ({$theories}). For each theory:\n" .
                "- Detail its seminal origins and foundational scholars.\n" .
                "- Explain core theoretical propositions, constructs, and historical evolution.\n" .
                "- Explicitly analyze how its constructs operationalize and explain the relationship between ({$indepVars}) and ({$depVars}).\n\n" .
                "## 2.3 Conceptual Framework\n" .
                "Provide a formal Mermaid diagram enclosed in ```mermaid code block. CRITICAL: Always enclose all node text in double quotes inside square brackets (e.g., IV1[\"Construct Name\"]) to avoid syntax errors with special characters:\n" .
                "```mermaid\n" .
                "graph LR\n" .
                "    subgraph IV [\"Independent Variables\"]\n" .
                "        IV1[\"" . addslashes($indepVarsArray[0] ?? 'Predictor 1') . "\"]\n" .
                "        IV2[\"" . addslashes($indepVarsArray[1] ?? 'Predictor 2') . "\"]\n" .
                "    end\n" .
                "    subgraph DV [\"Dependent Variables\"]\n" .
                "        DV1[\"" . addslashes($depVarsArray[0] ?? 'Primary Outcome') . "\"]\n" .
                "    end\n" .
                "    IV1 --> DV1\n" .
                "    IV2 --> DV1\n" .
                "```\n" .
                "Follow the Mermaid diagram with a multi-paragraph detailed narrative explaining each hypothesized interaction and structural relationship path." .
                $citationConstraint;

            $res2A = $this->aiService->callAi($prompt2A, $baseSystemPrompt);

            // MICRO-CALL 2B: Dedicated Empirical Literature Review (~1,200 words)
            $prompt2B = "Draft CHAPTER 2 (PART 2: SECTION 2.4 EMPIRICAL LITERATURE REVIEW ONLY) for: '{$title}'\n" .
                "Domain: {$domain} | Location: {$location}\n" .
                "Locked Independent Variables: {$indepVars}\n" .
                "Locked Dependent Variables: {$depVars}\n\n" .
                "BOUNDARY DIRECTIVE: Generate ONLY Section 2.4 with subsections (2.4.1, 2.4.2, etc.). DO NOT generate Section 2.5 or 2.6 in this part.\n\n" .
                "Generate the following structured academic section in exhaustive depth:\n" .
                "## 2.4 Empirical Literature Review\n" .
                "Organize extensive empirical review sub-section by sub-section for EACH core independent variable construct:\n" .
                "Synthesize global, regional (African), and local empirical findings, citing authentic domain studies, sample sizes, and statistical findings." .
                $citationConstraint;

            $res2B = $this->aiService->callAi($prompt2B, $baseSystemPrompt);

            // MICRO-CALL 2C: Critique of Literature & Summary Gaps Matrix Table (~900 words)
            $prompt2C = "Draft CHAPTER 2 (PART 3: SECTIONS 2.5 CRITIQUE AND 2.6 GAPS MATRIX TABLE ONLY) for: '{$title}'\n" .
                "Domain: {$domain} | Location: {$location}\n" .
                "Locked Independent Variables: {$indepVars}\n" .
                "Locked Dependent Variables: {$depVars}\n\n" .
                "Generate the following structured academic sections in full depth:\n" .
                "## 2.5 Critique of Existing Literature and Knowledge Gaps\n" .
                "Structure this section systematically under 3 explicit subheadings with TRUE Markdown unordered bullet lists (using '- **Bold Term:** Explanation'):\n" .
                "### 2.5.1 Conceptual & Theoretical Limitations\n" .
                "- **Construct Ambiguity:** Detailed analysis...\n" .
                "- **Theoretical Blindspots:** Detailed analysis...\n\n" .
                "### 2.5.2 Methodological Shortcomings in Prior Studies\n" .
                "- **Cross-Sectional Design Vulnerabilities:** Detailed critique...\n" .
                "- **Sampling Bias & Generalizability Constraints:** Detailed critique...\n\n" .
                "### 2.5.3 Contextual & Geographical Gaps\n" .
                "- **Emerging Market Disconnect:** Detailed critique...\n" .
                "- **Institutional Fragmentation:** Detailed critique...\n\n" .
                "## 2.6 Summary of Literature Gaps Matrix Table\n" .
                "MUST provide a comprehensive, publication-grade Markdown Table with at least 5 empirical studies:\n" .
                "| Author(s) & Year | Study Focus / Title | Methodology Used | Key Findings | Identified Knowledge / Contextual Gap | How Present Study Addresses Gap |\n" .
                "|---|---|---|---|---|---|" .
                $citationConstraint;

            $res2C = $this->aiService->callAi($prompt2C, $baseSystemPrompt);

            $combined = $this->cleanOutput($res2A) . "\n\n" . $this->cleanOutput($res2B) . "\n\n" . $this->cleanOutput($res2C);
            return $this->cleanOutput($combined);

        } elseif ($stage === 3) {
            $alignment = $this->buildAlignmentMatrix($registry, $location, $targetPop);
            $regressionFormula = $alignment['equation'];

            // MICRO-CALL 3A: Philosophy, Design, Population & Sampling Mathematics (Sections 3.1 to 3.4.1 ONLY - ~1,000 words)
            $prompt3A = "Draft CHAPTER 3 (PART 1: SECTIONS 3.1 TO 3.4.1 ONLY - RESEARCH PHILOSOPHY, DESIGN, POPULATION & SAMPLING MATHEMATICS) for: '{$title}'\n" .
                "Domain: {$domain} | Location: {$location}\n" .
                "Methodology: {$methodology}\n" .
                "Target Population Universe (N): {$popSize} {$targetPop}\n" .
                "Calculated Sample Size (n): {$sampleSize} respondents\n\n" .
                "STRICT BOUNDARY DIRECTIVE: Generate ONLY Sections 3.1, 3.2, 3.3, 3.4, and 3.4.1. Stop immediately after Section 3.4.1. DO NOT draft Sections 3.5, 3.6, 3.7, 3.8, 3.9, or 3.10 (they belong to Part 2).\n\n" .
                "Generate the following structured academic sections in exhaustive depth:\n" .
                "# CHAPTER 3: RESEARCH METHODOLOGY\n\n" .
                "## 3.1 Introduction and Research Philosophy\n" .
                "Justify the research philosophy (Pragmatism / Positivism) and ontological/epistemological assumptions.\n\n" .
                "## 3.2 Research Design\n" .
                "Detailed justification for the {$methodology} design (e.g. convergent parallel mixed methods / cross-sectional correlational design).\n\n" .
                "## 3.3 Target Population\n" .
                "Define the target population ({$targetPop}, N = {$popSize}) and provide a structured Population Breakdown Table.\n\n" .
                "## 3.4 Sampling Design and Sample Size Determination\n" .
                "Show the complete step-by-step mathematical calculation using Yamane (1967) formula ($$ n = \\frac{N}{1 + N(e)^2} $$) or Cochran (1977) formula with a 95% confidence level and 5% margin of error (e = 0.05). Use standard display math delimiters ($$ ... $$) and show step-by-step arithmetic deriving n = {$sampleSize} from N = {$popSize}.\n\n" .
                "### 3.4.1 Sampling Technique and Allocation\n" .
                "Detailed justification for using {$samplingStrategy}. Provide a structured Markdown Stratified Sampling Distribution Table allocating the sample of n = {$sampleSize} across sub-locations/clusters in {$location}." .
                $citationConstraint;

            $res3A = $this->aiService->callAi($prompt3A, $baseSystemPrompt);

            // MICRO-CALL 3B: Instruments, Validity, Reliability, Regression & Operationalization Matrix (Sections 3.5 to 3.10 ONLY - ~1,100 words)
            $prompt3B = "Draft CHAPTER 3 (PART 2: SECTIONS 3.5 TO 3.10 ONLY - INSTRUMENTS, VALIDITY, RELIABILITY, REGRESSION & MATRIX) for: '{$title}'\n" .
                "Domain: {$domain} | Location: {$location}\n" .
                "Locked Independent Variables: {$indepVars}\n" .
                "Locked Dependent Variables: {$depVars}\n" .
                "Sample Size: n = {$sampleSize} respondents\n" .
                "Primary Analysis Tool: {$primaryTool}\n\n" .
                "STRICT BOUNDARY DIRECTIVE: Start DIRECTLY with '## 3.5 Data Collection Instruments & Administration Protocols'. DO NOT include the Chapter 3 title again. DO NOT repeat Sections 3.1, 3.2, 3.3, or 3.4.\n\n" .
                "Generate the following structured academic sections in full depth:\n" .
                "## 3.5 Data Collection Instruments & Administration Protocols\n" .
                "Detail the primary instrument modes selected: {$instrumentModes}.\n" .
                "Describe the administration protocol, structured questionnaire design using {$measurementScale}, and interview guide procedures.\n\n" .
                "## 3.6 Pilot Testing Procedures (10% Protocol)\n" .
                "Explain the pilot study on 10% of the sample in a neighboring context to test instrument clarity.\n\n" .
                "## 3.7 Validity and Reliability Protocols\n" .
                "- Content Validity Index (CVI >= 0.80) with expert university panels.\n" .
                "- Internal Consistency Reliability using Cronbach's Alpha (threshold >= 0.70) across all variable constructs.\n\n" .
                "## 3.8 Data Collection Procedures & Ethical Approvals\n" .
                "Detail statutory research licenses (e.g., NACOSTI), institutional ethical clearance, voluntary informed consent protocols, and confidentiality measures.\n\n" .
                "## 3.9 Data Analysis and Statistical Modeling\n" .
                "- Descriptive analysis (frequencies, percentages, means, standard deviations).\n" .
                "- Inferential analysis (Pearson correlation, diagnostic tests for multicollinearity VIF, normality, heteroscedasticity).\n" .
                "- Empirical Regression Model Equation: $$ {$regressionFormula} $$\n" .
                "  CRITICAL VARIABLE BINDING: Explicitly define Y as the chosen dependent variable ({$depVars}), and each Xi as the chosen independent variables ({$indepVars}). Define β0 as the constant/intercept, β1–βk as the partial regression coefficients, and ε as the stochastic error term. Do NOT substitute unrelated domain variables.\n\n" .
                "## 3.10 Variable Operationalization Matrix Table\n" .
                "MUST provide an exhaustive 7-column publication-grade Markdown Table operationalizing EVERY specific objective and variable construct ({$indepVars}, {$depVars}) using measurement scale: {$measurementScale}:\n" .
                "| Specific Objective | Variable Construct | Variable Type | Empirical Indicators | Measurement Scale | Survey Items / Questions | Statistical Analysis Tool |\n" .
                "| :--- | :--- | :---: | :--- | :---: | :--- | :---: |" .
                $citationConstraint;

            $res3B = $this->aiService->callAi($prompt3B, $baseSystemPrompt);

            $combined = $this->cleanOutput($res3A) . "\n\n" . $this->cleanOutput($res3B);
            return $this->cleanOutput($combined);

        } elseif ($stage === 4) {
            // FOCUSED CALL 4: Budget Breakdown Table & 12-Month Gantt Work Plan (~500 words + 2 Tables)
            $budgetItems = !empty($params['budget']) && is_array($params['budget']) ? $params['budget'] : [];
            $budgetRows = "";
            $totalCost = 0;

            $getJustificationAndUnit = function ($itemName, $cost) use ($sampleSize, $location) {
                $lower = strtolower($itemName);
                if (str_contains($lower, 'field') || str_contains($lower, 'enumerat') || str_contains($lower, 'logistic')) {
                    return [
                        'unit' => '5 Enumerators × 10 Days',
                        'just' => "Covers daily field enumerator stipends and local transit across target clusters in {$location}"
                    ];
                } elseif (str_contains($lower, 'permit') || str_contains($lower, 'ethics') || str_contains($lower, 'nacosti')) {
                    return [
                        'unit' => '1 Permit + 1 IRB Review',
                        'just' => 'Statutory research licensing authorization and institutional ethical review clearance'
                    ];
                } elseif (str_contains($lower, 'software') || str_contains($lower, 'spss') || str_contains($lower, 'licens') || str_contains($lower, 'analys')) {
                    return [
                        'unit' => '2 Workstation Seats',
                        'just' => 'Statistical modeling software licensing and qualitative analysis tools'
                    ];
                } elseif (str_contains($lower, 'data') || str_contains($lower, 'cloud') || str_contains($lower, 'mobile') || str_contains($lower, 'server')) {
                    return [
                        'unit' => '12 Months Secure Cloud',
                        'just' => 'Encrypted digital survey hosting, mobile data synchronization, and automated backups'
                    ];
                } elseif (str_contains($lower, 'print') || str_contains($lower, 'bind') || str_contains($lower, 'stationery')) {
                    return [
                        'unit' => "{$sampleSize} Survey Packs",
                        'just' => 'Participant consent forms, institutional debrief letters, and thesis compilation'
                    ];
                }
                return [
                    'unit' => '1 Package',
                    'just' => 'Operational expenditure required for empirical milestone completion'
                ];
            };

            if (!empty($budgetItems)) {
                foreach ($budgetItems as $b) {
                    if (is_array($b) && !empty($b['item'])) {
                        $cost = (float) ($b['cost'] ?? 0);
                        $totalCost += $cost;
                        $meta = $getJustificationAndUnit($b['item'], $cost);
                        $budgetRows .= "| " . trim($b['item']) . " | {$meta['unit']} | " . number_format($cost, 2) . " | " . number_format($cost, 2) . " | {$meta['just']} |\n";
                    }
                }
            } else {
                $budgetRows .= "| Fieldwork Enumerator Logistics & Transport | 5 Enumerators × 10 Days | 100,000.00 | 100,000.00 | Daily stipends and transit across study clusters in {$location} |\n";
                $budgetRows .= "| Encrypted Survey Cloud Hosting & Data Bundles | 12 Months Hosting | 25,000.00 | 25,000.00 | Secure mobile data collection server & real-time synchronization |\n";
                $budgetRows .= "| Statutory Research Permit & Ethical Review | 1 Permit + 1 IRB Review | 20,000.00 | 20,000.00 | Official research permit and institutional review board fees |\n";
                $budgetRows .= "| Statistical Software Licensing & Computing | 2 Workstations | 50,000.00 | 100,000.00 | Specialized statistical computing software licenses |\n";
                $totalCost = 245000;
            }
            $formattedTotal = number_format($totalCost, 2);

            $prompt4 = "Draft PROPOSED BUDGET & 12-MONTH WORK PLAN for: '{$title}'\n" .
                "Location: {$location} | Sample: n = {$sampleSize}\n\n" .
                "Generate the following structured academic sections in full depth:\n" .
                "# PROPOSED BUDGET AND WORK PLAN\n\n" .
                "## 4.1 Proposed Budget Breakdown Table\n" .
                "| Item Category & Description | Quantity / Units | Unit Cost (KES) | Total Cost (KES) | Justification & Budget Notes |\n" .
                "| :--- | :---: | :---: | :---: | :--- |\n" .
                "{$budgetRows}" .
                "| **Grand Total Proposed Budget** | — | — | **KES {$formattedTotal}** | **Total study operational allocation** |\n\n" .
                "## 4.2 12-Month Work Plan Timeline (Gantt Milestone Schedule)\n" .
                "Provide a compact, publication-grade Markdown Gantt Table using abbreviated monthly column headers (M1 to M12) to ensure clean horizontal rendering:\n" .
                "| Research Milestone Activity | M1 | M2 | M3 | M4 | M5 | M6 | M7 | M8 | M9 | M10 | M11 | M12 |\n" .
                "| :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: |\n" .
                "Mark active milestone months with '✓'. Cover: Concept Presentation (M1), Proposal Defense (M2), Statutory Licensing & Ethics (M3), Pilot Testing (M4), Main Field Data Collection (M5-M6), Data Entry & Cleaning (M7), Statistical Modeling & Analysis (M8), Thesis Chapters Drafting (M9-M10), Supervisor Review & Revisions (M11), and Final Defense & Dissemination (M12)." .
                $citationConstraint;

            $res4 = $this->aiService->callAi($prompt4, $baseSystemPrompt);
            return $this->cleanOutput($res4);
        }

        return "Invalid stage specified.";
    }

    /**
     * Clean output of machine artifacts and trailing reference chunks.
     */
    private function cleanOutput(string $text): string
    {
        $text = preg_replace('/^\s*Word count:\s*\d+\s*$/im', '', $text);
        $text = preg_replace('/^\s*End of Document\.?\s*$/im', '', $text);
        $text = preg_replace('/^#{1,3}\s*References[\s\S]*$/im', '', $text);
        $text = preg_replace('/^#{1,3}\s*Bibliography[\s\S]*$/im', '', $text);
        return trim($text);
    }

    public function refineSingleStagePreview(array $params, int $stage, string $currentMarkdown, string $userInstruction): string
    {
        $style = $params['style'] ?? 'APA 7th';
        $title = $params['title'] ?? 'Research Proposal';

        $systemPrompt = "You are a distinguished Senior Academic Research Methodologist refining a draft research proposal section titled '{$title}'. " .
            "Academic Citation & Referencing Standard: {$style}. " .
            "USER REFINEMENT INSTRUCTION: {$userInstruction} " .
            "DIRECTIVES: " .
            "- Carefully apply the user's requested changes to the draft. " .
            "- If the user requests TRANSLATING the chapter to another language (e.g., French, Swahili, German, Spanish, Arabic, Chinese, English), translate the ENTIRE chapter narrative, headings, and tables into that target language with high academic quality while preserving all empirical rigor, citations, formulas ($$ ... $$), and table syntax. " .
            "- Preserve proper Markdown headings ('#', '##', '###') and Markdown tables ('| Header | Header |'). " .
            "- Ground all theoretical discussions and empirical citations in real seminal scholarship. " .
            "- DO NOT output conversational filler or machine meta-text. Output clean revised Markdown directly.";

        $prompt = "CURRENT DRAFT OF STAGE {$stage}:\n\n{$currentMarkdown}\n\n" .
            "USER MODIFICATION INSTRUCTIONS:\n{$userInstruction}\n\n" .
            "Please output the complete, improved Markdown for this stage incorporating the modifications.";

        $response = $this->aiService->callAi($prompt, $systemPrompt);
        if (!$response) {
            return $currentMarkdown;
        }

        $response = preg_replace('/^\s*Word count:\s*\d+\s*$/im', '', $response);
        $response = preg_replace('/^\s*End of Document\.?\s*$/im', '', $response);

        return trim($response);
    }
}