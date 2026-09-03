<?php

namespace App\Http\Controllers;

use App\Models\ResearchProposal;
use App\Services\AcademicSynthesisService;
use App\Services\ProposalGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResearchProposalController extends Controller
{
    public $synthesisService;
    public $proposalService;

    public function __construct(
        AcademicSynthesisService $synthesisService,
        ProposalGeneratorService $proposalService
    ) {
        $this->synthesisService = $synthesisService;
        $this->proposalService = $proposalService;
    }

    /**
     * Show the main Proposal Studio.
     */
    public function index()
    {
        return redirect()->route('research-proposal.history');
    }

    /**
     * Show User Proposals Library (Saved proposals and uploaded proposals).
     */
    public function history()
    {
        $user = auth()->user();
        $proposals = ResearchProposal::where('user_id', $user->id)->latest()->get();
        return view('admin.research-proposal.history', compact('proposals'));
    }

    /**
     * Launch the Interactive Research Proposal Wizard.
     */
    public function create()
    {
        // {{-- LEGACY REDIRECT [ROLLBACK SAFE] --}}
        return view('admin.research-proposal.create');
    }

    /**
     * Rapid Variable Suggestions Endpoint (Powered by Fast Inference).
     */
    public function suggestVariables(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'domain' => 'nullable|string|max:100',
            'problem' => 'nullable|string|max:1000',
        ]);

        $title = $request->input('title');
        $domain = $request->input('domain', 'Business Administration & Economics');
        $problem = $request->input('problem', '');

        // Add a random variation seed so clicking "Suggest" repeatedly produces fresh angles
        $perspectives = [
            "Focus on empirical operational dimensions and direct managerial practices.",
            "Focus on behavioral usage metrics, digital capabilities, and organizational performance.",
            "Focus on process efficiency, transactional indicators, and financial stewardship."
        ];
        $perspective = $perspectives[array_rand($perspectives)];

        $prompt = "You are a distinguished Senior Academic Research Methodologist.\n" .
            "Deconstruct this specific research study into precise, measurable academic variables:\n" .
            "STUDY TITLE: {$title}\n" .
            "DISCIPLINE: {$domain}\n" .
            "EVERYDAY PROBLEM CONTEXT: {$problem}\n" .
            "PERSPECTIVE: {$perspective}\n\n" .
            "CRITICAL METHODOLOGICAL RULES:\n" .
            "1. GEOGRAPHIC CONTEXT IS NOT A VARIABLE: Locations (e.g. 'Nairobi County', 'Kenya', 'Eastleigh') are study sites, NOT variables. NEVER return a city, country, or location name as an independent or dependent variable.\n" .
            "2. DECOMPOSE OVERARCHING CONSTRUCTS: Return ONLY 3-4 distinct operational dimensions of the primary independent phenomenon (e.g. 'Transaction Frequency', 'Revenue Proportion Processed', 'Digital Payment Adoption', 'Record-Keeping Automation').\n" .
            "3. SPECIFIC DEPENDENT OUTCOME: Derive 1-2 distinct dependent outcome variables (e.g. 'Financial Management Practices', 'Cash-Flow Monitoring Discipline', 'Separation of Business and Personal Accounts').\n" .
            "4. NEVER RETURN GENERIC JARGON: Strictly FORBID generic buzzwords like 'Strategic Planning', 'Resource Allocation', 'Operational Capacity', 'Training', 'Service Delivery', 'Program Performance'.\n\n" .
            "Return JSON ONLY with this exact structure:\n" .
            "{\n" .
            '  "independent": ["Dimension 1", "Dimension 2", "Dimension 3", "Dimension 4"],' . "\n" .
            '  "dependent": ["Specific Outcome 1", "Specific Outcome 2"],' . "\n" .
            '  "theories": ["Theory 1 (Author, Year)", "Theory 2 (Author, Year)"]' . "\n" .
            "}";

        $systemPrompt = "You are a master academic methodologist. Return JSON ONLY with precise topic-extracted constructs. NEVER return geographic locations as variables. NEVER return generic management buzzwords.";

        try {
            $aiService = app(\App\Services\AiService::class);
            // Temperature 0.4 allows fresh variation on repeat clicks while maintaining strict structural adherence
            $raw = $aiService->callAi($prompt, $systemPrompt, true, 500, 0.4);
            if ($raw) {
                $cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
                $cleaned = preg_replace('/\s*```$/i', '', $cleaned);
                $data = json_decode($cleaned, true);
                if (is_array($data) && isset($data['independent']) && isset($data['dependent'])) {
                    // Filter out any rogue location mentions and strip self-referential 'Effect of...' / 'Influence of...' prefixes
                    $cleanIndep = array_values(array_filter(array_map(function ($v) {
                        $v = trim($v);
                        $v = preg_replace('/^(?:the\s+)?(?:effect|influence|impact|role|determinants)\s+of\s+/i', '', $v);
                        return trim($v);
                    }, (array) $data['independent']), function ($v) {
                        return !empty($v) && !preg_match('/(nairobi|county|kenya|africa|sub-saharan|kajiado|kiambu|district|region)/i', $v);
                    }));

                    $cleanDep = array_values(array_filter(array_map(function ($v) {
                        $v = trim($v);
                        $v = preg_replace('/^(?:the\s+)?(?:effect|influence|impact|role|determinants)\s+of\s+/i', '', $v);
                        return trim($v);
                    }, (array) $data['dependent']), function ($v) {
                        return !empty($v) && !preg_match('/(nairobi|county|kenya|africa|sub-saharan|kajiado|kiambu|district|region)/i', $v);
                    }));

                    if (!empty($cleanIndep) && !empty($cleanDep)) {
                        return response()->json([
                            'success' => true,
                            'independent' => $cleanIndep,
                            'dependent' => $cleanDep,
                            'theories' => array_values(array_filter((array) ($data['theories'] ?? [])))
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Fast Variable Suggestion Error: " . $e->getMessage());
        }

        // Methodological Fallback if AI inference fails: decompose study topic into clean dimensions
        $titleClean = trim(preg_replace('/^(?:an?\s+)?(?:investigation|study|analysis|examination|assessment|evaluation)\s+(?:in|into|of|on)\s+/i', '', $title));
        $titleClean = preg_replace('/^(?:the\s+)?(?:effect|influence|impact|role|determinants)\s+of\s+/i', '', $titleClean);
        $titleNoGeo = preg_replace('/\b(?:in|across|within|at)\s+[^,\.\n]+(?:,\s*[^,\.\n]+)*/i', '', $titleClean);
        $titleParts = preg_split('/(?:\band\b|\bon\b|\bamong\b)/i', $titleNoGeo);
        $extracted = array_values(array_filter(array_map('trim', $titleParts), fn($p) => strlen($p) > 3));

        $baseIV = !empty($extracted[0]) ? preg_replace('/^(?:the\s+)?(?:effect|influence|impact|role)\s+of\s+/i', '', $extracted[0]) : 'Core Predictor';
        $indepFallback = [
            "{$baseIV} Frequency",
            "{$baseIV} Intensity",
            "{$baseIV} Integration",
            "{$baseIV} Automation"
        ];

        $depFallback = !empty($extracted[1])
            ? [trim(preg_replace('/^(?:the\s+)?(?:effect|influence|impact|role)\s+of\s+/i', '', $extracted[1]))]
            : ['Financial Management Practices'];

        return response()->json([
            'success' => true,
            'independent' => $indepFallback,
            'dependent' => $depFallback,
            'theories' => [
                'Technology Acceptance Model (Davis, 1989)',
                'Resource-Based View (Barney, 1991)'
            ]
        ]);
    }

    /**
     * Save proposal output directly from Socius AI into User Library.
     */
    public function saveFromSocius(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'style' => 'nullable|string',
        ]);

        $proposal = ResearchProposal::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'research_question' => $validated['title'],
            'objectives' => __('Generated from Socius AI Workspace'),
            'methodology_type' => 'mixed',
            'content' => ['proposal' => $validated['content']],
            'style' => $validated['style'] ?? 'apa7',
            'status' => 'completed'
        ]);

        return response()->json([
            'success' => true,
            'message' => __('Proposal saved to your Library successfully!'),
            'proposal' => $proposal
        ]);
    }

    /**
     * Upload an existing proposal file directly into User Library.
     */
    public function uploadProposal(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:docx,pdf,txt|max:10240'
        ]);

        $file = $request->file('file');
        $extractedText = app(\App\Services\DocumentExtractionService::class)->extractTextFromFile($file);

        $proposal = ResearchProposal::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'research_question' => $request->title,
            'objectives' => __('Uploaded Document Proposal'),
            'methodology_type' => 'mixed',
            'content' => ['proposal' => $extractedText],
            'style' => 'apa7',
            'status' => 'completed'
        ]);

        return redirect()->route('research-proposal.history')
            ->with('success', __('Proposal uploaded and saved to your Library!'));
    }

    /**
     * Generate a fast single-stage live preview for the interactive wizard.
     */
    public function previewStage(Request $request)
    {
        $validated = $request->validate([
            'stage' => 'required|integer|min:1|max:4',
            'title' => 'required|string|max:500',
            'problem_statement' => 'nullable|string',
            'domain' => 'nullable|string|max:200',
            'target_location' => 'nullable|string|max:255',
            'independent_variables' => 'nullable|array',
            'dependent_variables' => 'nullable|array',
            'theories' => 'nullable|array',
            'population_size' => 'nullable|numeric',
            'sample_size' => 'nullable|numeric',
            'target_population' => 'nullable|string',
            'study_goal' => 'nullable|string',
            'methodology_type' => 'nullable|string',
            'style' => 'nullable|string',
            'budget' => 'nullable|array',
            'custom_instructions' => 'nullable|string',
        ]);

        $markdown = $this->proposalService->generateSingleStagePreview($validated, (int) $validated['stage']);

        return response()->json([
            'success' => true,
            'stage' => (int) $validated['stage'],
            'markdown' => $markdown
        ]);
    }

    /**
     * Refine a specific stage preview using user's AI modification prompt.
     */
    public function refineStagePreview(Request $request)
    {
        $validated = $request->validate([
            'stage' => 'required|integer|min:1|max:4',
            'title' => 'required|string|max:500',
            'current_markdown' => 'required|string',
            'instruction' => 'required|string|max:2000',
            'style' => 'nullable|string'
        ]);

        $markdown = $this->proposalService->refineSingleStagePreview(
            $validated,
            (int) $validated['stage'],
            $validated['current_markdown'],
            $validated['instruction']
        );

        return response()->json([
            'success' => true,
            'stage' => (int) $validated['stage'],
            'markdown' => $markdown
        ]);
    }

    /**
     * Handle the intake form / wizard submission and assemble the final proposal.
     */
    public function storeProposal(Request $request)
    {
        set_time_limit(900);
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'problem_statement' => 'nullable|string',
            'research_question' => 'nullable|string',
            'objectives' => 'nullable|string',
            'methodology_type' => 'nullable|string',
            'target_location' => 'nullable|string',
            'scope' => 'nullable|string',
            'style' => 'nullable|string',
            'domain' => 'nullable|string',
            'independent_variables' => 'nullable|array',
            'dependent_variables' => 'nullable|array',
            'theories' => 'nullable|array',
            'target_population' => 'nullable|string',
            'population_size' => 'nullable|numeric',
            'sample_size' => 'nullable|numeric',
            'data_collection_modes' => 'nullable|array',
            'custom_instructions' => 'nullable|string',
            'budget' => 'nullable|array',
            'previews' => 'nullable|array',
            'include_budget' => 'nullable|boolean',
        ]);

        $style = $validated['style'] ?? 'apa7';
        $methodologyType = $validated['methodology_type'] ?? 'mixed';

        // Synthesize research questions & objectives if not provided
        $indepVars = !empty($validated['independent_variables']) ? implode(', ', $validated['independent_variables']) : '';
        $depVars = !empty($validated['dependent_variables']) ? implode(', ', $validated['dependent_variables']) : '';

        $problem = $validated['problem_statement'] ?? $validated['research_question'] ?? $validated['title'];
        $researchQuestion = $validated['research_question'] ?? ("To what extent do " . ($indepVars ?: "key independent factors") . " influence " . ($depVars ?: "study outcomes") . " in the context of " . $validated['title'] . "?");

        $objectives = $validated['objectives'] ?? '';
        if (empty($objectives) && (!empty($indepVars) || !empty($depVars))) {
            $objList = [];
            $i = 1;
            if (!empty($validated['independent_variables'])) {
                foreach ($validated['independent_variables'] as $iv) {
                    $objList[] = "{$i}. Assess the effect of {$iv} on " . ($depVars ?: 'overall performance') . ".";
                    $i++;
                }
            }
            if (!empty($validated['dependent_variables'])) {
                $objList[] = "{$i}. Evaluate the impact on " . $depVars . ".";
            }
            $objectives = implode("\n", $objList);
        }

        $scope = $validated['scope'] ?? '';
        if (empty($scope)) {
            $scopeParts = [];
            if (!empty($validated['target_location'])) {
                $scopeParts[] = "Location: " . $validated['target_location'];
            }
            if (!empty($validated['target_population'])) {
                $scopeParts[] = "Target Population: " . $validated['target_population'];
            }
            if (!empty($validated['population_size'])) {
                $scopeParts[] = "Estimated Population: " . number_format($validated['population_size']);
            }
            if (!empty($validated['sample_size'])) {
                $scopeParts[] = "Target Sample Size: " . number_format($validated['sample_size']);
            }
            if (!empty($validated['data_collection_modes'])) {
                $scopeParts[] = "Data Collection: " . implode(', ', $validated['data_collection_modes']);
            }
            $scope = implode("; ", $scopeParts);
        }

        $cleanBudget = [];
        if (!empty($validated['budget']) && is_array($validated['budget'])) {
            foreach ($validated['budget'] as $b) {
                if (is_array($b) && !empty($b['item'])) {
                    $cleanBudget[] = [
                        'item' => trim($b['item']),
                        'cost' => (float) ($b['cost'] ?? 0)
                    ];
                }
            }
        }

        $customInstructions = $validated['custom_instructions'] ?? null;
        $varNotes = [];
        if (!empty($validated['independent_variables']) && is_array($validated['independent_variables'])) {
            $varNotes[] = "Independent Variables: " . implode(', ', $validated['independent_variables']) . ".";
        }
        if (!empty($validated['dependent_variables']) && is_array($validated['dependent_variables'])) {
            $varNotes[] = "Dependent Variables: " . implode(', ', $validated['dependent_variables']) . ".";
        }
        if (!empty($validated['theories']) && is_array($validated['theories'])) {
            $varNotes[] = "Theoretical Anchors: " . implode(', ', $validated['theories']) . ".";
        }
        if (!empty($validated['data_collection_modes']) && is_array($validated['data_collection_modes'])) {
            $varNotes[] = "Data Collection Instruments: " . implode(', ', $validated['data_collection_modes']) . ".";
        }
        if (!empty($varNotes)) {
            $combinedNotes = implode("\n", $varNotes);
            $customInstructions = $customInstructions ? ($combinedNotes . "\n" . $customInstructions) : $combinedNotes;
        }

        // Prevent duplicate library records: reuse existing proposal if ID passed or update matching draft
        $proposalId = $request->input('proposal_id');
        if ($proposalId) {
            $proposal = ResearchProposal::where('id', $proposalId)->where('user_id', auth()->id())->first();
        }
        if (empty($proposal)) {
            // Find recent draft with identical title within 2 hours
            $proposal = ResearchProposal::where('user_id', auth()->id())
                ->where('title', $validated['title'])
                ->where('created_at', '>=', now()->subHours(2))
                ->latest()
                ->first();
        }

        if ($proposal) {
            $proposal->update([
                'research_question' => $researchQuestion,
                'objectives' => $objectives ?: __('Comprehensive study objectives to be synthesized by engine.'),
                'methodology_type' => $methodologyType,
                'scope' => $scope ?: __('Geographic and target population context.'),
                'style' => $style,
                'custom_instructions' => $customInstructions,
                'budget' => !empty($cleanBudget) ? $cleanBudget : null,
            ]);
        } else {
            $proposal = ResearchProposal::create([
                'user_id' => auth()->id(),
                'title' => $validated['title'],
                'research_question' => $researchQuestion,
                'objectives' => $objectives ?: __('Comprehensive study objectives to be synthesized by engine.'),
                'methodology_type' => $methodologyType,
                'scope' => $scope ?: __('Geographic and target population context.'),
                'style' => $style,
                'custom_instructions' => $customInstructions,
                'budget' => !empty($cleanBudget) ? $cleanBudget : null,
                'status' => 'draft'
            ]);
        }

        // Assemble full document hierarchy: Preliminaries -> Ch1 -> Ch2 -> Ch3 -> Budget -> Appendices
        $previews = $request->input('previews', []);
        $content = [];

        // 0. Front Matter & Preliminaries (Title Page, Declaration, Abstract, Definition of Terms)
        $pPrelim = $this->proposalService->buildPreliminariesPrompt($proposal);
        $aiService = app(\App\Services\AiService::class);
        $sysPrompt = "You are a distinguished Senior Academic Research Methodologist drafting front matter for a formal {$style} proposal.";
        $prelimRaw = $aiService->callAi($pPrelim, $sysPrompt);
        if ($prelimRaw) {
            $parts = preg_split('/\[SECTION:\s*([^\]]+)\]/i', $prelimRaw, -1, PREG_SPLIT_DELIM_CAPTURE);
            for ($i = 1; $i < count($parts); $i += 2) {
                $title = trim($parts[$i]);
                $body = trim($parts[$i + 1] ?? '');
                if ($title && $body) {
                    $content[$title] = $body;
                }
            }
        }
        if (empty($content)) {
            $content['Title Page'] = "# {$proposal->title}\n\n**A Formal Research Proposal**\n\nAuthor: [Principal Investigator]\nInstitution: Faculty of Graduate Studies\nDate: " . date('F Y');
        }

        // 1. Chapter 1: Introduction
        $ch1 = $previews[1] ?? $previews['1'] ?? null;
        if (!$ch1) {
            $ch1 = $this->proposalService->generateSingleStagePreview($validated, 1);
        }
        $content['CHAPTER 1: INTRODUCTION'] = $ch1;

        // 2. Chapter 2: Literature Review & Framework
        $ch2 = $previews[2] ?? $previews['2'] ?? null;
        if (!$ch2) {
            $ch2 = $this->proposalService->generateSingleStagePreview($validated, 2);
        }
        $content['CHAPTER 2: LITERATURE REVIEW'] = $ch2;

        // 3. Chapter 3: Research Methodology
        $ch3 = $previews[3] ?? $previews['3'] ?? null;
        if (!$ch3) {
            $ch3 = $this->proposalService->generateSingleStagePreview($validated, 3);
        }
        $content['CHAPTER 3: RESEARCH METHODOLOGY'] = $ch3;

        // 4. Proposed Budget & Work Plan (if not skipped)
        $includeBudget = $request->input('include_budget', true);
        if ($includeBudget) {
            $ch4 = $previews[4] ?? $previews['4'] ?? null;
            if (!$ch4 && !empty($cleanBudget)) {
                $ch4 = $this->proposalService->generateSingleStagePreview($validated, 4);
            }
            if ($ch4) {
                $content['PROPOSED BUDGET AND WORK PLAN'] = $ch4;
            }
        }

        // 5. Appendices (References, Questionnaire, Interview Guide)
        $pAppendix = $this->proposalService->buildAppendixPrompt($proposal);
        $appendixContent = [];
        $aiService = app(\App\Services\AiService::class);
        $sysPrompt = "You are a distinguished Senior Academic Research Methodologist drafting references and appendices for a formal {$style} proposal.";
        $appendixRaw = $aiService->callAi($pAppendix, $sysPrompt);
        if ($appendixRaw) {
            $parts = preg_split('/\[SECTION:\s*([^\]]+)\]/i', $appendixRaw, -1, PREG_SPLIT_DELIM_CAPTURE);
            for ($i = 1; $i < count($parts); $i += 2) {
                $title = trim($parts[$i]);
                $body = trim($parts[$i + 1] ?? '');
                if ($title && $body) {
                    $appendixContent[$title] = $body;
                }
            }
        }

        foreach ($appendixContent as $k => $v) {
            $content[$k] = $v;
        }

        $proposal->update([
            'content' => $content,
            'status' => 'completed'
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'redirect_url' => route('research-proposal.show', $proposal->id),
                'proposal_id' => $proposal->id
            ]);
        }

        return redirect()->route('research-proposal.show', $proposal->id)
            ->with('success', __('Your formal research proposal has been compiled and saved!'));
    }

    /**
     * Handle user feedback and refine an existing proposal.
     */
    public function refineProposal(Request $request, ResearchProposal $research_proposal)
    {
        $this->authorizeOwner($research_proposal);

        $request->validate([
            'refinement_instructions' => 'required|string|max:4000',
            'target_section' => 'nullable|string',
        ]);

        $userFeedback = $request->input('refinement_instructions');
        $targetSection = $request->input('target_section', 'all');
        $this->proposalService->refineProposal($research_proposal, $userFeedback, $targetSection);

        return redirect()->back()->with('success', __('Research proposal refined successfully based on your feedback!'));
    }

    /**
     * View a generated proposal.
     */
    public function show(ResearchProposal $research_proposal)
    {
        $this->authorizeOwner($research_proposal);
        $proposal = $research_proposal;
        return view('admin.research-proposal.show', compact('proposal'));
    }

    /**
     * Generate a report from a survey and redirect to preview.
     */
    public function generate(Request $request)
    {
        set_time_limit(300); // Increase limit for complex AI generation
        $request->validate([
            'survey_id' => 'required|exists:surveys,id',
            'style' => 'required|string|in:apa7,mla9,harvard,chicago,ieee,vancouver,oscola',
            'format' => 'required|string|in:docx,pdf',
            'references' => 'nullable|array',
            'references.*.author' => 'nullable|string',
            'references.*.year' => 'nullable|string',
            'references.*.title' => 'nullable|string',
            'references.*.source' => 'nullable|string',
        ]);

        $survey = \App\Models\Survey::findOrFail($request->survey_id);
        $style = $request->style;
        $manualReferences = $request->input('references', []);

        // Filter out empty references
        $manualReferences = array_filter($manualReferences, function ($ref) {
            return !empty($ref['author']) || !empty($ref['title']);
        });

        $branding = $this->resolveBrandingContext($survey);

        // Generate the academic sections using the NEW iterative pipeline
        $reportData = $this->synthesisService->generateIterativeReport($survey, $style, $manualReferences, $branding);

        // Store the report and survey_id in the session
        $reportId = uniqid('report_');
        session([$reportId => array_merge($reportData, ['survey_id' => $survey->id])]);

        return redirect()->route('research-proposal.preview', [
            'reportId' => $reportId,
            'format' => $request->input('format')
        ]);
    }

    /**
     * Preview the generated report draft.
     */
    public function preview($reportId, Request $request)
    {
        set_time_limit(300); // Allow time for potential auto-translation
        $reportData = session($reportId);
        if (!$reportData) {
            return redirect()->route('research-proposal.index')->with('error', 'Report draft not found or expired.');
        }

        // --- STRUCTURAL NORMALIZATION ---
        // Ensure all keys are standard English so __() can translate them in any locale
        $reportData['sections'] = $this->synthesisService->normalizeReportKeys($reportData['sections']);
        session([$reportId => $reportData]);

        // --- AUTO-TRANSLATION LOGIC ---
        // If the report's stored locale doesn't match the current UI locale,
        // automatically trigger the translation to unify the experience.
        $currentLocale = \App::getLocale();
        $reportLocale = $reportData['metadata']['locale'] ?? 'en';

        if ($reportLocale !== $currentLocale && !isset($reportData['is_translating'])) {
            try {
                // Prevent recursive loops if translation fails
                $reportData['is_translating'] = true;
                session([$reportId => $reportData]);

                $translationResult = $this->synthesisService->translateReport($reportData['sections'], $currentLocale);

                if ($translationResult['success']) {
                    $reportData['sections'] = $translationResult['sections'];
                    $reportData['metadata']['locale'] = $currentLocale;
                    $reportData['metadata']['translated_at'] = now()->toIso8601String();
                    session()->now('success', __('Report content automatically unified with :lang', ['lang' => strtoupper($currentLocale)]));
                }

                unset($reportData['is_translating']);
                session([$reportId => $reportData]); // <--- FIX: Actually save the translated data and clear the lock
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Auto-translation failed: ' . $e->getMessage());
                unset($reportData['is_translating']);
                session([$reportId => $reportData]);
            }
        }

        $user = auth()->user();
        $isTruncated = false;

        // Admins always see the full preview.
        // Others see truncated preview if they don't have an active subscription (Pro/Enterprise).
        if ($user && !$user->isAdmin()) {
            $isTruncated = !$user->hasActiveSubscription();
        }

        $format = $request->input('format', 'pdf');
        $canExport = !$isTruncated || $user->free_export_count < 2;
        $remainingExports = max(0, 2 - $user->free_export_count);

        return view('admin.research-proposal.preview', compact('reportData', 'reportId', 'format', 'isTruncated', 'canExport', 'remainingExports'));
    }

    /**
     * Translate the current report session data.
     */
    public function translate($reportId, Request $request)
    {
        set_time_limit(300); // Increase limit for translation
        $reportData = session($reportId);
        if (!$reportData) {
            return back()->with('error', 'Report data not found.');
        }

        $targetLocale = \App::getLocale();

        // Don't translate if already in the target locale
        if (isset($reportData['metadata']['locale']) && $reportData['metadata']['locale'] === $targetLocale) {
            return back()->with('info', 'Report is already in ' . strtoupper($targetLocale));
        }

        try {
            $translationResult = $this->synthesisService->translateReport($reportData['sections'], $targetLocale);

            $reportData['sections'] = $translationResult['sections'] ?? $translationResult;
            $reportData['metadata']['locale'] = $targetLocale;
            $reportData['metadata']['translated_at'] = now()->toIso8601String();

            session([$reportId => $reportData]);

            return back()->with('success', 'Report has been translated to ' . strtoupper($targetLocale));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Report translation error: ' . $e->getMessage());
            return back()->with('error', 'Failed to translate report. Please try again.');
        }
    }

    /**
     * Final export from the preview page.
     */
    public function export($reportId, Request $request)
    {
        $reportData = session($reportId);
        if (!$reportData) {
            return redirect()->route('research-proposal.index')->with('error', 'Report data lost.');
        }

        $format = $request->input('format', 'pdf');
        $filename = 'research_report_' . time();

        $user = auth()->user();
        if (!$user->hasActiveSubscription() && $user->free_export_count >= 2) {
            return redirect()->route('research-proposal.preview', $reportId)->with('error', 'You have reached your limit of 2 free exports. Please upgrade to continue.');
        }

        $survey = isset($reportData['survey_id']) ? \App\Models\Survey::find($reportData['survey_id']) : null;
        $branding = $this->resolveBrandingContext($survey);

        \Illuminate\Support\Facades\Log::info('Export Branding Context', [
            'user_id' => auth()->id(),
            'is_pro' => auth()->user()->hasActiveSubscription(),
            'branding' => $branding
        ]);

        if ($format === 'docx') {
            $path = $this->synthesisService->exportToDocx($reportData['sections'], $filename, $branding);
        } else {
            $path = $this->synthesisService->exportToPdf($reportData['sections'], $filename, $branding);
        }

        // Increment free export count if not subscribed
        if (!$user->hasActiveSubscription()) {
            $user->increment('free_export_count');
        }

        return response()->download($path);
    }

    /**
     * Export a saved Research Proposal.
     */
    public function exportProposal($id)
    {
        $research_proposal = ResearchProposal::findOrFail($id);
        $this->authorizeOwner($research_proposal);

        $user = auth()->user();
        if (!$user->hasActiveSubscription() && $user->free_export_count >= 2) {
            return back()->with('error', 'You have reached your limit of 2 free exports. Please upgrade to continue.');
        }

        $filename = 'research_proposal_' . str($research_proposal->title)->slug() . '_' . time();
        $branding = $this->resolveBrandingContext(); // Global user branding for standalone proposals

        $path = $this->synthesisService->exportToDocx($research_proposal->content, $filename, $branding);

        if (!$user->hasActiveSubscription()) {
            $user->increment('free_export_count');
        }

        return response()->download($path);
    }

    /**
     * Resolve branding context based on User tier and Survey settings.
     */
    private function resolveBrandingContext(?\App\Models\Survey $survey = null): array
    {
        $user = auth()->user();
        $canRemove = $user->hasActiveSubscription();

        // Global User Settings
        $userRemoveBranding = $user->remove_kd_branding;
        $userOrgName = $user->export_org_name;
        $userLogo = $user->export_logo_url;

        if ($survey) {
            return [
                'showKdBranding' => !($canRemove && ($userRemoveBranding || $survey->remove_kd_branding)),
                'customLogo' => ($canRemove) ? ($survey->export_logo_url ?: $userLogo) : null,
                'customOrgName' => ($canRemove) ? ($survey->export_org_name ?: $userOrgName) : null,
                'brandColor' => ($canRemove) ? ($survey->brand_color ?: ($user->brand_color ?: '#4f46e5')) : '#4f46e5',
            ];
        }

        return [
            'showKdBranding' => !($canRemove && $userRemoveBranding),
            'customLogo' => ($canRemove && $userLogo) ? $userLogo : null,
            'customOrgName' => ($canRemove && $userOrgName) ? $userOrgName : null,
            'brandColor' => ($canRemove) ? ($user->brand_color ?: '#4f46e5') : '#4f46e5',
        ];
    }

    public function destroy(ResearchProposal $research_proposal)
    {
        $this->authorizeOwner($research_proposal);
        $research_proposal->delete();
        return back()->with('success', 'Proposal draft deleted successfully.');
    }

    /**
     * Direct In-Wizard DOCX Export endpoint from current state.
     */
    public function exportDocxFromWizard(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'previews' => 'required|array',
            'style' => 'nullable|string',
            'budget' => 'nullable|array',
            'include_budget' => 'nullable|boolean'
        ]);

        $title = $validated['title'];
        $previews = $validated['previews'];
        $content = [];

        // Build full document
        $content['Title Page'] = "# {$title}\n\n**A Formal Research Proposal**\n\nDepartment & Institution: Academic Faculty of Research & Graduate Studies\nDate: " . date('F Y');

        if (!empty($previews[1]))
            $content['CHAPTER 1: INTRODUCTION'] = $previews[1];
        if (!empty($previews[2]))
            $content['CHAPTER 2: LITERATURE REVIEW'] = $previews[2];
        if (!empty($previews[3]))
            $content['CHAPTER 3: RESEARCH METHODOLOGY'] = $previews[3];
        if (!empty($previews[4]) && ($validated['include_budget'] ?? true)) {
            $content['PROPOSED BUDGET AND WORK PLAN'] = $previews[4];
        }

        $filename = 'research_proposal_' . str($title)->slug() . '_' . time();
        $branding = $this->resolveBrandingContext();

        $path = $this->synthesisService->exportToDocx($content, $filename, $branding);
        return response()->download($path);
    }

    private function authorizeOwner(ResearchProposal $research_proposal)
    {
        if (auth()->user()->role === \App\Enums\UserRole::Admin || auth()->user()->role === 'admin') {
            return;
        }

        if ((int) $research_proposal->user_id !== (int) auth()->id()) {
            abort(403);
        }
    }
}
