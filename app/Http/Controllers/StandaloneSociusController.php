<?php

namespace App\Http\Controllers;

use App\Models\SurveyAiMessage;
use App\Models\SurveyAiThread;
use App\Services\DocumentExtractionService;
use App\Services\GroqStreamingClient;
use App\Services\SociusPromptBuilder;
use App\Services\WebSearchService;
use App\Services\MemoryExtractionService;
use App\Services\AiHumanizerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Handles standalone Socius AI chat sessions that are NOT tied to any survey.
 * Used by the sidebar "Socius AI" feature and by the AI Transcription "Analyze" flow.
 */
class StandaloneSociusController extends Controller
{
    public function __construct(
        private readonly GroqStreamingClient $groqStreamingClient,
        private readonly SociusPromptBuilder $sociusPromptBuilder,
        private readonly DocumentExtractionService $documentExtractionService,
        private readonly WebSearchService $webSearchService,
        private readonly MemoryExtractionService $memoryExtractionService,
        private readonly AiHumanizerService $aiHumanizerService,
    ) {
    }

    /**
     * Show the standalone Socius chat page.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Optionally receive initial context seeded from another feature or query preset
        $initialContext = session()->pull('socius_standalone_context', null);
        if (!$initialContext && $request->query('preset') === 'proposal') {
            $initialContext = "RESEARCH PROPOSAL GENERATION (PACED STAGE 1): Hello Socius! Please draft CHAPTER 1: INTRODUCTION of a research proposal in full academic prose.\n\n" .
                "Include the following sections strictly in order:\n" .
                "• 1.1 Background to the Study (600+ words across 4 sequential paragraphs: Global -> Regional -> National -> Local with local gap pivot sentence)\n" .
                "• 1.2 Statement of the Problem (4-part: Ideal State, Current Reality, Elaborated Empirical Research Gap, Cost of Inaction)\n" .
                "• 1.3 Research Objectives (NUMBERED LIST: 1.3.1 General Objective, 1.3.2 Specific Objectives numbered 1., 2., 3. starting with action verbs)\n" .
                "• 1.4 Research Questions (NUMBERED LIST: numbered 1., 2., 3. matching specific objectives directly)\n" .
                "• 1.5 Research Hypotheses (Include ONLY if quantitative design warrants it; OMIT section completely if qualitative/exploratory)\n" .
                "• 1.5 / 1.6 Significance of the Study (Deep continuous PROSE PARAGRAPHS weaving in Policy Makers, Practitioners, Academic Literature)\n" .
                "• 1.6 / 1.7 Scope of the Study\n" .
                "• 1.7 / 1.8 Limitations of the Study (Continuous PROSE PARAGRAPHS detailing constraints alongside explicit Mitigation Strategies)\n" .
                "• 1.8 / 1.9 Definition of Key Terms (Title MUST be 'Definition of Key Terms'; list format defining ALL core independent, dependent, and outcome variables)\n\n" .
                "Write in 100% narrative prose (NO data tables or charts in Chapter 1). STOP STRICTLY after Definition of Key Terms. Do NOT write Chapter 2 or Chapter 3 yet. Conclude naturally by asking the user if they are ready to proceed to Chapter 2: Review of Related Literature.";
        }

        return view('socius.chat', [
            'canAnalyze' => $user->canUseAiAnalysis(),
            'initialContext' => $initialContext,
            'urls' => [
                'list' => route('socius.chat.threads'),
                'create' => route('socius.chat.threads.store'),
                'showTemplate' => route('socius.chat.threads.show', ['thread' => '__THREAD__']),
                'streamTemplate' => route('socius.chat.threads.stream', ['thread' => '__THREAD__']),
                'updateTemplate' => route('socius.chat.threads.update', ['thread' => '__THREAD__']),
                'pin_toggleTemplate' => route('socius.chat.threads.pin_toggle', ['thread' => '__THREAD__']),
                'destroyTemplate' => route('socius.chat.threads.destroy', ['thread' => '__THREAD__']),
                'exportTemplate' => route('socius.chat.threads.export', ['thread' => '__THREAD__']),
                'kbList' => route('socius.knowledge-base.index'),
                'kbStore' => route('socius.knowledge-base.store'),
                'kbUpdateTemplate' => route('socius.knowledge-base.update', ['knowledgeBase' => '__KB__']),
                'kbDestroyTemplate' => route('socius.knowledge-base.destroy', ['knowledgeBase' => '__KB__']),
            ],
        ]);
    }

    /**
     * List all standalone threads for the authenticated user.
     */
    public function threads(Request $request): JsonResponse
    {
        $user = $request->user();

        $threads = SurveyAiThread::where('user_id', $user->id)
            ->whereNull('survey_id')
            ->with(['latestMessage'])
            ->withCount('messages')
            ->orderByDesc('is_pinned')
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn(SurveyAiThread $t) => $this->serializeThread($t));

        return response()->json(['threads' => $threads]);
    }

    /**
     * Create a new standalone thread.
     */
    public function storeThread(Request $request): JsonResponse
    {
        $this->ensureAiEligible($request);

        $validated = $request->validate([
            'context_type' => ['nullable', 'string', 'in:general,transcription'],
            'initial_context_text' => ['nullable', 'string', 'max:50000'],
            'initial_context_label' => ['nullable', 'string', 'max:255'],
        ]);

        $label = $validated['initial_context_label'] ?? null;
        $title = $label ? ('Analysis: ' . Str::limit($label, 50)) : 'New chat';

        $thread = SurveyAiThread::create([
            'user_id' => $request->user()->id,
            'survey_id' => null,
            'context_type' => $validated['context_type'] ?? 'general',
            'title' => $title,
            'last_activity_at' => now(),
        ]);

        return response()->json([
            'thread' => $this->serializeThread($thread->loadMissing('latestMessage')),
        ], 201);
    }

    /**
     * Load a specific standalone thread with its messages.
     */
    public function showThread(SurveyAiThread $thread, Request $request): JsonResponse
    {
        $this->authorizeThread($thread, $request);

        $thread->load(['messages.attachments', 'latestMessage']);

        return response()->json([
            'thread' => $this->serializeThread($thread),
            'messages' => $thread->messages
                ->sortBy('id')
                ->values()
                ->map(fn(SurveyAiMessage $m) => $this->serializeMessage($m)),
        ]);
    }

    /**
     * Stream an AI response for a standalone thread.
     */
    public function streamThread(SurveyAiThread $thread, Request $request)
    {
        set_time_limit(0);
        $this->authorizeThread($thread, $request);
        $this->ensureAiEligible($request);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'web_search_enabled' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'mimes:' . implode(',', config('socius.supported_extensions', ['pdf', 'csv', 'txt', 'docx'])),
                'max:' . (max(1, (int) config('socius.max_attachment_mb', 10)) * 1024),
            ],
        ]);

        $webSearchEnabled = $request->boolean('web_search_enabled', false);
        $storedPaths = [];

        $userMessage = $thread->messages()->create([
            'user_id' => $request->user()->id,
            'role' => 'user',
            'content' => trim($validated['message']),
            'metadata' => [
                'locale' => app()->getLocale(),
                'web_search_enabled' => $webSearchEnabled,
            ],
        ]);

        try {
            foreach ($request->file('attachments', []) as $file) {
                $this->storeAttachment($thread, $userMessage, $file, $storedPaths);
            }
        } catch (\Throwable $e) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }
            $userMessage->delete();
            return response()->json(['message' => 'File upload failed: ' . $e->getMessage()], 422);
        }

        // Auto-title on first real user message (skip if it was the seeded initial context)
        $userCount = $thread->messages()->where('role', 'user')->count();
        if ($userCount <= 2) {
            $thread->update(['title' => $this->generateTitle($userMessage->content)]);
        }

        $messages = $this->buildMessages($thread, $webSearchEnabled);
        $assistantMessage = $thread->messages()->create([
            'user_id' => $request->user()->id,
            'role' => 'assistant',
            'content' => '',
            'metadata' => ['status' => 'streaming'],
        ]);

        $thread->update(['last_activity_at' => now()]);

        $user = $request->user();
        $headers = [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ];

        return response()->stream(function () use ($messages, $thread, $userMessage, $assistantMessage, $user) {
            $assistantContent = '';

            $this->emitEvent('meta', [
                'thread_id' => $thread->id,
                'user_message_id' => $userMessage->id,
                'assistant_message_id' => $assistantMessage->id,
            ]);

            try {
                $hasImages = $thread->messages()
                    ->whereHas('attachments', fn($q) => $q->where('mime_type', 'like', 'image/%'))
                    ->exists();

                $model = $this->sociusPromptBuilder->getModel($hasImages);

                $userPrompt = strtolower($userMessage->content);
                $lastAssistantMsg = $thread->messages()
                    ->where('role', 'assistant')
                    ->where('id', '<', $assistantMessage->id)
                    ->latest()
                    ->first();
                $lastAssistantContent = strtolower($lastAssistantMsg->content ?? '');

                $isChapter2Request = (bool) preg_match('/chapter\s*2|proceed\s*to\s*chapter\s*2|review\s*of\s*related\s*literature/i', $userPrompt)
                    || (str_contains($lastAssistantContent, 'chapter 1 is complete') && (bool) preg_match('/proceed|next|continue|yes|ok|start/i', $userPrompt));

                if ($isChapter2Request) {
                    // STAGE 1: Sections 2.1 (Introduction) & 2.2 (Theoretical Review)
                    $messagesStage1 = $messages;
                    $messagesStage1[] = [
                        'role' => 'system',
                        'content' => "CHAPTER 2 STAGE 1 OF 3 (THEORETICAL REVIEW):\n" .
                            "Output ONLY Sections 2.1 and 2.2 for Chapter 2: Review of Related Literature.\n\n" .
                            "• 2.1 Introduction: State chapter purpose and list upcoming sub-sections (Theoretical Review, Conceptual Framework, Empirical Review organized by objectives, Literature Synthesis, Knowledge Gap).\n" .
                            "• 2.2 Theoretical Review: Review 3 formal foundational theories/frameworks relevant to the study (e.g., Theory of Planned Behavior [Ajzen, 1991], Institutional Theory [North, 1990; Ostrom, 2005], Sustainable Livelihoods Framework [DFID, 1999]).\n" .
                            "STRICT MANDATES FOR SECTION 2.2:\n" .
                            "1. Format each theory with an explicit H3 markdown header: ### 2.2.1 [Theory Name], ### 2.2.2 [Theory Name], ### 2.2.3 [Theory Name]. DO NOT print theory titles as plain text.\n" .
                            "2. DO NOT define generic terms or concepts in 2.2 (e.g. WCED definitions). Review ONLY formal theories.\n" .
                            "3. FOR EACH OF THE 3 THEORIES, WRITE EXACTLY 2 PARAGRAPHS:\n" .
                            "   - Paragraph 1: Originator name(s), year, core tenets, constructs, and principles.\n" .
                            "   - Paragraph 2: Mandatory 'Application to the Study' paragraph explicitly detailing how the theory's tenets analyze the study's specific Independent Variables (IVs), Dependent Variable (DV), and site context.\n" .
                            "4. STRICT PROHIBITION: DO NOT CITE EMPIRICAL FIELD STUDIES IN SECTION 2.2 (no Ogwueleka, Mbuligwe, Otieno, etc. in 2.2; empirical field studies belong exclusively in Section 2.4).\n\n" .
                            "Write in deep, thesis-ready academic prose. Stop strictly after Section 2.2. Do NOT write conversational state-tracking messages like 'Chapter 2 is incomplete'."
                    ];

                    $res1 = $this->groqStreamingClient->streamChatCompletion(
                        $messagesStage1,
                        function (string $delta) use (&$assistantContent) {
                            $assistantContent .= $delta;
                            $this->emitEvent('delta', ['content' => $delta]);
                        },
                        $model
                    );
                    $stage1Text = $res1['content'] ?? '';

                    // STAGE 2: Section 2.3 (Conceptual Framework & Parallel Mermaid Diagram)
                    $assistantContent .= "\n\n";
                    $this->emitEvent('delta', ['content' => "\n\n"]);

                    $messagesStage2 = $messages;
                    $messagesStage2[] = ['role' => 'assistant', 'content' => $stage1Text];
                    $messagesStage2[] = [
                        'role' => 'system',
                        'content' => "CHAPTER 2 STAGE 2 OF 3 (CONCEPTUAL FRAMEWORK):\n" .
                            "Output ONLY Section 2.3 (Conceptual Framework).\n\n" .
                            "STRICT MANDATES FOR SECTION 2.3:\n" .
                            "1. Write 3 detailed narrative paragraphs:\n" .
                            "   - Paragraph 1: Define Independent Variables (IV1: Socio-Economic Factors & IV2: Institutional Frameworks) with specific measurable indicators.\n" .
                            "   - Paragraph 2: Define Dependent Variable (DV: Sustainable Waste Management / Study Outcome) with specific indicators.\n" .
                            "   - Paragraph 3: Explain the directional relationships and interactions between IVs, DV, and moderating variables.\n" .
                            "2. MERMAID DIAGRAM MANDATE (graph LR):\n" .
                            "   - Include a clean ```mermaid graph LR block on its own line with double newlines before and after.\n" .
                            "   - Model IV1 (Socio-Economic Factors) and IV2 (Institutional Frameworks) as PARALLEL Independent Variables pointing directly to DV (Sustainable Waste Management / Study Outcome).\n" .
                            "   - DO NOT create a linear chain (IV1 -> IV2 -> DV). IV1 and IV2 MUST be parallel blocks targeting DV directly.\n" .
                            "   - Use valid Mermaid arrow labels: `A[\"Socio-Economic Factors\"] -->|Influence| C[\"Sustainability of Waste Management\"]`. DO NOT output invalid syntax like `-->|Influence|>`. Quote all node text containing spaces or parentheses.\n\n" .
                            "Write in deep thesis-level academic prose. Stop strictly after Section 2.3. Do NOT write conversational state-tracking messages like 'Chapter 2 is incomplete'."
                    ];

                    $res2 = $this->groqStreamingClient->streamChatCompletion(
                        $messagesStage2,
                        function (string $delta) use (&$assistantContent) {
                            $assistantContent .= $delta;
                            $this->emitEvent('delta', ['content' => $delta]);
                        },
                        $model
                    );
                    $stage2Text = $res2['content'] ?? '';

                    // STAGE 3: Sections 2.4 (Empirical Review) & 2.5 (Summary & Knowledge Gap)
                    $assistantContent .= "\n\n";
                    $this->emitEvent('delta', ['content' => "\n\n"]);

                    $messagesStage3 = $messages;
                    $messagesStage3[] = ['role' => 'assistant', 'content' => $stage1Text . "\n\n" . $stage2Text];
                    $messagesStage3[] = [
                        'role' => 'system',
                        'content' => "CHAPTER 2 STAGE 3 OF 3 (EMPIRICAL REVIEW & KNOWLEDGE GAP):\n" .
                            "Output ONLY Sections 2.4 and 2.5.\n\n" .
                            "STRICT MANDATES FOR SECTION 2.4 (EMPIRICAL REVIEW):\n" .
                            "1. Under '### 2.4 Empirical Review', write ONLY 1 short sentence stating that empirical literature is organized by objectives. DO NOT list authors or summarize studies directly under 2.4.\n" .
                            "2. Split into 3 distinct thematic sub-sections matching research objectives: ### 2.4.1 [Objective 1 Theme], ### 2.4.2 [Objective 2 Theme], ### 2.4.3 [Objective 3 Theme].\n" .
                            "3. Synthesize 4–6 empirical studies PER SUB-SECTION in an active scholarly debate using direct comparative transitions (e.g. 'In contrast to Ogwueleka (2013), who argued X, Njoroge (2018) demonstrated Y...', 'Similarly, Mwangi (2019) corroborated these findings by...').\n\n" .
                            "STRICT MANDATES FOR SECTION 2.5 (SUMMARY & KNOWLEDGE GAP):\n" .
                            "1. Synthesize the reviewed literature.\n" .
                            "2. Explicitly detail the research gap under 3 BOLD SUB-HEADINGS:\n" .
                            "   - **Geographical Gap** (urban-fringe/peri-urban transition zones vs primary metropolitan hubs like Nairobi/Mombasa).\n" .
                            "   - **Contextual/Governance Gap** (governance & socio-economics vs technical waste engineering hardware).\n" .
                            "   - **Methodological Gap**.\n\n" .
                            "Write in deep, thesis-level academic prose. Conclude naturally in plain text with: 'Chapter 2 is complete. Let me know when you are ready to proceed to Chapter 3: Research Methodology.'"
                    ];

                    $res3 = $this->groqStreamingClient->streamChatCompletion(
                        $messagesStage3,
                        function (string $delta) use (&$assistantContent) {
                            $assistantContent .= $delta;
                            $this->emitEvent('delta', ['content' => $delta]);
                        },
                        $model
                    );

                    // Clean up interjections before final update
                    $cleanContent = $assistantContent;
                    $cleanContent = preg_replace('/Chapter 2 is incomplete.*?(?=\n|$)/i', '', $cleanContent);
                    $cleanContent = preg_replace('/Let me know when you are ready to proceed to Section.*?(?=\n|$)/i', '', $cleanContent);
                    $cleanContent = preg_replace('/##\s*Chapter 2:\s*Review of Related Literature\s*\(Continued\)/i', '', $cleanContent);
                    $cleanContent = preg_replace('/\n{3,}/', "\n\n", $cleanContent);
                    $assistantContent = trim($cleanContent);

                    $result = [
                        'content' => $assistantContent,
                        'finish_reason' => $res3['finish_reason'] ?? 'stop',
                        'model' => $res3['model'] ?? config('services.groq.model'),
                        'usage' => $res3['usage'] ?? null,
                    ];
                } else {
                    $isChapter3Request = (bool) preg_match('/chapter\s*3|proceed\s*to\s*chapter\s*3|research\s*methodology/i', $userPrompt)
                        || (str_contains($lastAssistantContent, 'chapter 2 is complete') && (bool) preg_match('/proceed|next|continue|yes|ok|start/i', $userPrompt));

                    if ($isChapter3Request) {
                        $messagesChapter3 = $messages;
                        $messagesChapter3[] = [
                            'role' => 'system',
                            'content' => "CHAPTER 3 GENERATION (RESEARCH METHODOLOGY):\n" .
                                "Draft CHAPTER 3: RESEARCH METHODOLOGY in deep, thesis-ready academic prose.\n\n" .
                                "STRICT CHRONOLOGICAL SECTION SEQUENCE MANDATE:\n" .
                                "Must follow strict academic flow and STOP strictly after Section 3.8 Ethical Considerations:\n" .
                                "3.1 Research Design -> 3.2 Target Population -> 3.3 Sample Size & Sampling Procedure -> 3.4 Data Collection Instruments -> 3.5 Data Collection Procedure -> 3.6 Data Analysis Procedure -> 3.7 Validity, Reliability & Trustworthiness -> 3.8 Ethical Considerations.\n\n" .
                                "LATEX MATHEMATICAL FORMULA FORMATTING MANDATE:\n" .
                                "Render ALL mathematical equations cleanly using LaTeX block syntax: `$$n = \\frac{N}{1 + N(e)^2}$$` and `$$Y = \\beta_0 + \\beta_1 X_1 + \\beta_2 X_2 + \\epsilon$$`. DO NOT output plain text equations like `n = N / (1 + N(e)^2)`.\n\n" .
                                "SPECIFIC SECTION MANDATES:\n" .
                                "1. 3.1 Research Design: Explicitly label as a 'Convergent Parallel Mixed-Methods Design' (quantitative survey data and qualitative key informant interview data collected concurrently, analyzed separately, and merged during interpretation).\n" .
                                "2. 3.2 & 3.3 Target Population, Sample Size & Sampling Procedure:\n" .
                                "   - Quantitative Sample: Derive using Yamane's (1967) Formula in LaTeX: $$n = \\frac{N}{1 + N(e)^2}$$ where N is total target population (200,000) and e = 0.05 margin of error at 95% confidence level, yielding n ≈ 400 households. Include proportional allocation formula (n_i = (N_i / N) * n) for stratified random sampling.\n" .
                                "   - Qualitative Sample: Explicitly state n = 15–20 Key Informants (e.g., County Environmental Officers, NEMA Officials, Waste Management Association Leaders) selected via Purposive Sampling until theoretical saturation is reached.\n" .
                                "3. 3.4 Data Collection Instruments: Detail structured questionnaires for households and semi-structured interview guides for key informant officers.\n" .
                                "4. 3.5 Data Collection Procedure: Detail field administration logistics, enumerator training, participant consent protocols, and data entry.\n" .
                                "5. 3.6 Data Analysis Procedure: Merge BOTH the Econometric Regression Model equation and Data Analytical Software Tools directly inside Section 3.6 under two structured sub-sections:\n" .
                                "   - ### 3.6.1 Quantitative Analysis & Econometric Model: Render the Multiple Linear Regression LaTeX equation $$Y = \\beta_0 + \\beta_1 X_1 + \\beta_2 X_2 + \\epsilon$$ (where Y = DV, X1 = IV1, X2 = IV2, β0 = intercept, β1, β2 = coefficients, ε = error term) and state software tools (IBM SPSS Statistics v28 or R).\n" .
                                "   - ### 3.6.2 Qualitative Data Analysis: Detail thematic coding and matrix analysis using NVivo 12 software.\n" .
                                "   - STRICT PROHIBITION: DO NOT output standalone trailing sections for Econometric Model or Software Tools. They belong strictly inside Section 3.6.\n" .
                                "6. 3.7 Validity, Reliability & Trustworthiness:\n" .
                                "   - Quantitative Reliability: Evaluated using Cronbach's Alpha (α ≥ 0.70).\n" .
                                "   - Validity: Content Validity Index (CVI) evaluated by university panel/supervisors, and Construct Validity via factor analysis.\n" .
                                "   - Qualitative Trustworthiness: Apply Lincoln and Guba's (1985) criteria (Credibility, Transferability, Dependability, Confirmability).\n" .
                                "7. 3.8 Ethical Considerations: Explicitly name statutory regulatory permits: NACOSTI (National Commission for Science, Technology and Innovation) research permit, University Ethics Review Committee approval, and County Administrative/Commissioner clearance.\n" .
                                "8. STRICT PROHIBITION ON TIMELINE & BUDGET INSIDE CHAPTER 3: DO NOT write Timeline/Work Plan or Budget sections inside Chapter 3 (e.g. DO NOT create Section 3.11 Timeline or 3.12 Budget). In standard research proposals, Timeline and Budget belong exclusively in the Appendices!\n" .
                                "9. PROPOSAL SCOPE: Chapter 3 is the FINAL chapter of a 3-chapter research proposal and MUST STOP strictly after Section 3.8. Do NOT draft or suggest Chapters 4 or 5. Conclude naturally in plain text with: 'Chapter 3 is complete. The 3-chapter research proposal is fully drafted! Let me know if you would like me to generate the References or Appendices (Questionnaire, Key Informant Interview Guide, Research Budget, Work Plan).'"
                        ];

                        $result = $this->groqStreamingClient->streamChatCompletion(
                            $messagesChapter3,
                            function (string $delta) use (&$assistantContent) {
                                $assistantContent .= $delta;
                                $this->emitEvent('delta', ['content' => $delta]);
                            },
                            $model
                        );
                    } else {
                        $isAppendicesRequest = (bool) preg_match('/references|appendices|questionnaire|interview\s*guide|work\s*plan|budget/i', $userPrompt)
                            || (str_contains($lastAssistantContent, 'chapter 3 is complete') && (bool) preg_match('/proceed|next|continue|yes|ok|start|generate/i', $userPrompt));

                        if ($isAppendicesRequest) {
                            $messagesAppendices = $messages;
                            $messagesAppendices[] = [
                                'role' => 'system',
                                'content' => "REFERENCES & APPENDICES GENERATION (RESEARCH PROPOSAL PACKAGE):\n" .
                                    "Generate the complete, submission-ready REFERENCES and APPENDICES package for the research proposal in full academic detail.\n\n" .
                                    "STRICT FORMATTING & CONTENT MANDATES:\n" .
                                    "1. DO NOT WRAP ANY PART OF THIS OUTPUT IN ``` CODE BLOCKS OR MARKDOWN CODE FENCES. Write standard Markdown text, headings, and tables directly.\n" .
                                    "2. START IMMEDIATELY WITH '## References'. DO NOT SKIP THE REFERENCES SECTION.\n\n" .
                                    "SECTION SPECIFICATIONS:\n" .
                                    "1. ## References (APA 7th Edition Format):\n" .
                                    "   - Provide a comprehensive, alphabetized APA 7th Edition Reference List including ALL authors cited across Chapters 1, 2, and 3 (Ajzen 1991, Braun & Clarke 2006, DFID 1999, DiMaggio & Powell 1983, KNBS 2019, NACOSTI, NEMA 2020, North 1990, Ogwueleka 2013, Ostrom 2005, Otieno 2015, World Bank 2022, Yamane 1967).\n" .
                                    "   - Follow APA 7th rules: Omit publisher location cities, italicize journal/book titles, include DOIs/URL links where available.\n\n" .
                                    "2. ## Appendix A: Household Structured Questionnaire:\n" .
                                    "   - Provide a full-text, actionable 4-part survey instrument with categorical options and Likert Scale tables:\n" .
                                    "     * Section I: Socio-Economic Profile (Gender, Age, Education Level, Monthly Income Brackets, Household Size, Town/Sub-location).\n" .
                                    "     * Section II: Household Solid Waste Management Practices [DV] (Disposal methods, collection frequency, waste segregation, recycling adoption).\n" .
                                    "     * Section III: Socio-Economic Determinants [IV1] (Structured 5-point Likert Scale table: 1=Strongly Disagree to 5=Strongly Agree measuring affordability, willingness to pay, and environmental awareness).\n" .
                                    "     * Section IV: Institutional & Governance Frameworks [IV2] (Structured 5-point Likert Scale table: 1=Strongly Disagree to 5=Strongly Agree measuring county enforcement, receptacle provision, compliance checks, and public participation).\n\n" .
                                    "3. ## Appendix B: Key Informant Interview Guide:\n" .
                                    "   - Provide a full-text semi-structured qualitative interview guide for County Environmental Officers, NEMA Officials, and Private Waste Managers (6 comprehensive research probes).\n\n" .
                                    "4. ## Appendix C: Research Work Plan & Timeline:\n" .
                                    "   - Output a structured markdown Gantt table covering EXACTLY 6 MONTHS (Month 1 to Month 6: Proposal Writing, NACOSTI Permit & Ethics Approval, Field Survey Administration, Data Entry & SPSS/NVivo Analysis, Draft Writing, Final Submission). DO NOT output 12 months.\n\n" .
                                    "5. ## Appendix D: Itemized Research Budget:\n" .
                                    "   - Output a structured markdown budget table strictly matching the KES 500,000 budget breakdown from Chapter 3:\n" .
                                    "     | Item Description | Quantity / Rate | Cost (KES) |\n" .
                                    "     | Data Collection & Field Enumerators | 10 Enumerators x 14 Days | 150,000 |\n" .
                                    "     | Field Logistics, Transport & Communication | Fuel, Airtime, Local Admin | 150,000 |\n" .
                                    "     | Data Processing & Statistical Analysis | SPSS v28 & NVivo 12 Licensing/Coding | 100,000 |\n" .
                                    "     | Report Printing, Binding & Submissions | 10 Hard Copies & NACOSTI Permit | 50,000 |\n" .
                                    "     | Contingency Fund | Unforeseen Field Expenses | 50,000 |\n" .
                                    "     | **TOTAL PROPOSAL BUDGET** | | **500,000** |\n" .
                                    "     (DO NOT output KES 300,000).\n\n" .
                                    "Conclude naturally in plain text with: 'The complete 3-chapter research proposal package (including full-text References and Appendices) is fully finalized and ready for formal submission!'"
                            ];

                            $result = $this->groqStreamingClient->streamChatCompletion(
                                $messagesAppendices,
                                function (string $delta) use (&$assistantContent) {
                                    $assistantContent .= $delta;
                                    $this->emitEvent('delta', ['content' => $delta]);
                                },
                                $model
                            );
                        } else {
                            $result = $this->groqStreamingClient->streamChatCompletion(
                                $messages,
                                function (string $delta) use (&$assistantContent) {
                                    $assistantContent .= $delta;
                                    $this->emitEvent('delta', ['content' => $delta]);
                                },
                                $model
                            );
                        }
                    }
                }

                $assistantMessage->update([
                    'content' => $assistantContent ?: ($result['content'] ?? ''),
                    'metadata' => [
                        'status' => 'complete',
                        'finish_reason' => $result['finish_reason'] ?? null,
                        'model' => $result['model'] ?? config('services.groq.model'),
                        'usage' => $result['usage'] ?? null,
                    ],
                ]);

                $thread->update(['last_activity_at' => now()]);

                if (!$user->hasProAccess()) {
                    $user->recordAiUsage();
                }

                $this->memoryExtractionService->extractAndStore($thread);

                $this->emitEvent('done', [
                    'thread_id' => $thread->id,
                    'assistant_message_id' => $assistantMessage->id,
                    'status' => 'complete',
                ]);
            } catch (\Throwable $e) {
                Log::error('Standalone Socius streaming failed.', [
                    'thread_id' => $thread->id,
                    'message' => $e->getMessage(),
                ]);

                $rawErr = $e->getMessage();
                $friendlyError = "An error occurred while generating response.";

                if (str_contains(strtolower($rawErr), '429') || str_contains(strtolower($rawErr), 'rate limit')) {
                    $friendlyError = "Groq API Rate Limit Exceeded: The system received too many requests in a short window. Please wait 10–15 seconds and try again.";
                } elseif (str_contains(strtolower($rawErr), '401') || str_contains(strtolower($rawErr), 'api key')) {
                    $friendlyError = "Authentication Error: The Groq API key is missing or invalid in server settings.";
                } elseif (str_contains(strtolower($rawErr), '503') || str_contains(strtolower($rawErr), 'timeout') || str_contains(strtolower($rawErr), 'overloaded')) {
                    $friendlyError = "AI Engine Overloaded: The upstream AI model timed out. Please retry your request.";
                } else {
                    $friendlyError = "Generation Error: " . $rawErr;
                }

                $assistantMessage->update([
                    'content' => $assistantContent ?: $friendlyError,
                    'metadata' => ['status' => 'failed', 'error' => $friendlyError],
                ]);

                $this->emitEvent('error', ['message' => $friendlyError]);
            }
        }, 200, $headers);
    }

    /**
     * Rename a thread.
     */
    public function updateThread(SurveyAiThread $thread, Request $request): JsonResponse
    {
        $this->authorizeThread($thread, $request);

        $validated = $request->validate(['title' => ['required', 'string', 'max:120']]);
        $thread->update(['title' => trim($validated['title'])]);

        return response()->json(['thread' => $this->serializeThread($thread->loadMissing('latestMessage'))]);
    }

    /**
     * Delete a thread.
     */
    public function destroyThread(SurveyAiThread $thread, Request $request): JsonResponse
    {
        $this->authorizeThread($thread, $request);

        $attachmentPaths = $thread->attachments()->pluck('storage_path')->filter()->toArray();
        $thread->delete();

        foreach ($attachmentPaths as $path) {
            Storage::disk('local')->delete($path);
        }

        return response()->json(['deleted' => true]);
    }

    /**
     * Toggle pin on a thread.
     */
    public function togglePinThread(SurveyAiThread $thread, Request $request): JsonResponse
    {
        $this->authorizeThread($thread, $request);
        $thread->update(['is_pinned' => !$thread->is_pinned]);

        return response()->json(['thread' => $this->serializeThread($thread->loadMissing('latestMessage'))]);
    }

    /**
     * Export a standalone thread (PDF / DOCX).
     */
    public function exportThread(SurveyAiThread $thread, Request $request)
    {
        $this->authorizeThread($thread, $request);

        $format = $request->query('format', 'pdf');
        $thread->load(['messages.attachments']);

        // Reuse the same export logic as the survey-linked Socius
        $exportController = app(\App\Http\Controllers\SociusChatController::class);

        // Delegate to SociusChatController's export rendering but with a fake Survey
        // We'll use a simplified approach: render and export directly
        return $this->exportStandaloneThread($thread, $format, $request);
    }

    // ─────────────────────────────── Helpers ───────────────────────────────

    private function authorizeThread(SurveyAiThread $thread, Request $request): void
    {
        // Thread must be standalone (no survey)
        abort_if($thread->survey_id !== null, 404);

        $user = $request->user();
        if (!$user->isAdmin()) {
            abort_unless((int) $thread->user_id === (int) $user->id, 403);
        }
    }

    private function ensureAiEligible(Request $request): void
    {
        abort_unless(
            $request->user()->canUseAiAnalysis(),
            403,
            'AI analysis is unavailable for your account right now.'
        );
    }

    private function buildMessages(SurveyAiThread $thread, bool $webSearchEnabled = false): array
    {
        $user = auth()->user();

        // Fetch long-term memory for this user (standalone mode shares memories across all context types)
        $memories = \App\Models\SurveyAiMemory::where('user_id', $user->id)
            ->orderByDesc('importance')
            ->limit(5)
            ->pluck('fact')
            ->toArray();

        // Fetch active knowledge base rules
        $knowledgeBaseRules = $user->sociusKnowledgeBases()
            ->where('is_active', true)
            ->pluck('content')
            ->toArray();

        $locales = [
            'en' => 'English',
            'sw' => 'Swahili',
            'fr' => 'French',
            'de' => 'German',
            'es' => 'Spanish',
            'ar' => 'Arabic',
            'zh' => 'Chinese',
        ];
        $currentLanguage = $locales[app()->getLocale()] ?? 'English';

        $messages = [
            ['role' => 'system', 'content' => $this->sociusPromptBuilder->getSystemPrompt($memories, $knowledgeBaseRules)],
            ['role' => 'system', 'content' => "User current language: {$currentLanguage}. You must respond in {$currentLanguage} by default. IMPORTANT: If the user communicates in a different language, you MUST automatically detect it and converse in that language instead."],
            ['role' => 'system', 'content' => "STANDALONE MODE: This chat has no specific survey attached. You are a general-purpose academic and research AI assistant. The user may paste documents, transcriptions, or any text for analysis."],
        ];

        // Inject transcription context from first message metadata if present
        $firstMessage = $thread->messages()->orderBy('id')->first();
        if ($firstMessage && data_get($firstMessage->metadata, 'is_initial_context')) {
            $contextText = data_get($firstMessage->metadata, 'initial_context_text', '');
            $contextLabel = data_get($firstMessage->metadata, 'context_label', 'Document');
            if ($contextText) {
                $messages[] = [
                    'role' => 'system',
                    'content' => "DOCUMENT CONTEXT ({$contextLabel}):\n{$contextText}",
                ];
            }
        }

        // Inject web search grounding if enabled
        if ($webSearchEnabled) {
            $lastUserMessage = $thread->messages()->where('role', 'user')->latest('id')->first();
            if ($lastUserMessage) {
                try {
                    $searchResult = $this->webSearchService->search($lastUserMessage->content);
                    $messages[] = [
                        'role' => 'system',
                        'content' => "External Knowledge (Real-time Grounding):\n" . $searchResult,
                    ];
                } catch (\Throwable $e) {
                    Log::warning('Standalone Socius web search failed: ' . $e->getMessage());
                }
            }
        }

        // Append conversation history
        $history = $thread->messages()->with('attachments')->orderBy('id')->get();

        foreach ($history as $message) {
            if ($message->role === 'assistant' && data_get($message->metadata, 'status') === 'streaming') {
                continue;
            }

            $content = [];

            // Inject text content
            if ($message->content) {
                $content[] = ['type' => 'text', 'text' => (string) $message->content];
            }

            // Inject image attachments for vision models
            foreach ($message->attachments ?? [] as $attachment) {
                if (str_starts_with($attachment->mime_type ?? '', 'image/')) {
                    $absolutePath = Storage::disk('local')->path($attachment->storage_path);
                    if (file_exists($absolutePath)) {
                        $base64 = base64_encode(file_get_contents($absolutePath));
                        $content[] = [
                            'type' => 'image_url',
                            'image_url' => ['url' => "data:{$attachment->mime_type};base64,{$base64}"],
                        ];
                    }
                } elseif ($attachment->extracted_text) {
                    $content[] = [
                        'type' => 'text',
                        'text' => "[Attached: {$attachment->original_filename}]\n" . $attachment->extracted_text,
                    ];
                }
            }

            if (count($content) === 1 && ($content[0]['type'] ?? '') === 'text') {
                $messages[] = ['role' => $message->role, 'content' => $content[0]['text']];
            } elseif (!empty($content)) {
                $messages[] = ['role' => $message->role, 'content' => $content];
            }
        }

        return $messages;
    }

    private function storeAttachment(SurveyAiThread $thread, SurveyAiMessage $message, $file, array &$storedPaths): void
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "socius/standalone/{$thread->id}/{$filename}";
        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));
        $storedPaths[] = $path;

        $extractedText = null;
        $mime = $file->getMimeType();

        if (!str_starts_with($mime ?? '', 'image/')) {
            try {
                $extractedText = $this->documentExtractionService->extract(Storage::disk('local')->path($path), $mime);
            } catch (\Throwable $e) {
                Log::warning('Standalone Socius attachment extraction failed: ' . $e->getMessage());
            }
        }

        $thread->attachments()->create([
            'message_id' => $message->id,
            'original_filename' => $file->getClientOriginalName(),
            'storage_path' => $path,
            'mime_type' => $mime,
            'file_size' => $file->getSize(),
            'extracted_text' => $extractedText,
        ]);
    }

    private function generateTitle(string $firstMessage): string
    {
        return Str::limit(preg_replace('/\s+/', ' ', strip_tags($firstMessage)), 60);
    }

    private function emitEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo 'data: ' . json_encode($data) . "\n\n";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    private function serializeThread(SurveyAiThread $thread): array
    {
        $latestMessage = $thread->relationLoaded('latestMessage') ? $thread->latestMessage : null;

        return [
            'id' => $thread->id,
            'title' => $thread->title,
            'user_id' => $thread->user_id,
            'context_type' => $thread->context_type,
            'is_pinned' => (bool) $thread->is_pinned,
            'last_activity_at' => ($thread->last_activity_at ?? $thread->updated_at)?->toIso8601String(),
            'message_count' => $thread->messages_count ?? $thread->messages()->count(),
            'latest_message_preview' => $latestMessage ? Str::limit((string) $latestMessage->content, 120) : null,
        ];
    }

    private function serializeMessage(SurveyAiMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'metadata' => $message->metadata,
            'created_at' => $message->created_at?->toIso8601String(),
            'attachments' => $message->relationLoaded('attachments')
                ? $message->attachments->map(fn($a) => [
                    'id' => $a->id,
                    'original_filename' => $a->original_filename,
                    'mime_type' => $a->mime_type,
                    'file_size' => $a->file_size,
                ])->toArray()
                : [],
        ];
    }

    private function exportStandaloneThread(SurveyAiThread $thread, string $format, Request $request)
    {
        // Reuse the SociusChatController export logic by proxying
        // We forge a fake Survey binding and call the export method
        // Simple approach: call the static HTML render from SociusChatController
        $thread->load(['messages.attachments']);

        $title = $thread->title ?? 'Socius AI Chat';
        $messages = $thread->messages->sortBy('id')->values();

        // Render the thread as an HTML view
        $html = view('exports.socius-thread', [
            'thread' => $thread,
            'messages' => $messages,
            'title' => $title,
            'survey' => null,
        ])->render();

        if ($format === 'docx') {
            return $this->exportAsDocx($html, $title);
        }

        return $this->exportAsPdf($html, $title);
    }

    private function exportAsPdf(string $html, string $title): \Illuminate\Http\Response
    {
        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . Str::slug($title) . '.pdf"',
        ]);
    }

    private function exportAsDocx(string $html, string $title): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        $section = $phpWord->addSection([
            'marginTop' => 1200,
            'marginBottom' => 1200,
            'marginLeft' => 1200,
            'marginRight' => 1200,
        ]);

        \PhpOffice\PhpWord\Shared\Html::addHtml($section, strip_tags($html, '<p><br><b><strong><i><em><ul><ol><li><table><tr><td><th>'));

        $tmpPath = storage_path('app/temp/' . Str::uuid() . '.docx');
        if (!is_dir(dirname($tmpPath))) {
            mkdir(dirname($tmpPath), 0755, true);
        }

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpPath);

        return response()->download($tmpPath, Str::slug($title) . '.docx')->deleteFileAfterSend(true);
    }

    /**
     * Store Thumbs Up / Thumbs Down feedback for AI learning on a message.
     */
    public function rateMessage(Request $request, $messageId)
    {
        $validated = $request->validate([
            'rating' => 'required|string|in:like,dislike',
            'feedback' => 'nullable|string|max:1000',
        ]);

        $userId = auth()->id();
        $message = SurveyAiMessage::where(function ($query) use ($userId) {
            $query->where('user_id', $userId)
                ->orWhereHas('thread', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
        })->find($messageId);

        if (!$message) {
            return response()->json(['success' => false, 'message' => 'Message not found'], 404);
        }

        $meta = $message->metadata ?? [];
        $meta['rating'] = $validated['rating'];
        if (!empty($validated['feedback'])) {
            $meta['rating_feedback'] = $validated['feedback'];
        }
        $message->metadata = $meta;
        $message->save();

        // If dislike with feedback, save feedback as negative memory preference so AI learns
        if ($validated['rating'] === 'dislike' && !empty($validated['feedback'])) {
            \App\Models\SurveyAiMemory::create([
                'user_id' => auth()->id(),
                'fact' => "User Disliked Output Pattern: " . $validated['feedback'],
                'importance' => 4,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => __('Thank you for your feedback! Socius has logged this to refine future outputs.'),
            'rating' => $validated['rating'],
        ]);
    }
}
