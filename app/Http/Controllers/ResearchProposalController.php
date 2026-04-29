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
     * Show the main page: Survey to Report Generator.
     */
    public function index()
    {
        $user = auth()->user();
        $surveys = \App\Models\Survey::where('created_by', $user->id)
            ->orWhere('type', \App\Enums\SurveyType::Public)
            ->get();

        return view('admin.research-proposal.index', compact('surveys'));
    }

    /**
     * Show the Workshop History: Reports and Proposals.
     */
    public function history()
    {
        $user = auth()->user();

        // Proposals from the database
        $proposals = ResearchProposal::where('user_id', $user->id)->latest()->get();

        // We'll also show any 'reports' generated in this session (or if we had a reports table, from there)
        // For now, let's look for any session-based reports or simulated past reports
        $reports = []; // If there was a GeneratedReport model, we'd query it here.

        return view('admin.research-proposal.history', compact('proposals', 'reports'));
    }

    /**
     * Show the guided intake form for drafting a NEW proposal.
     */
    public function create()
    {
        return view('admin.research-proposal.create');
    }

    /**
     * Handle the intake form submission and generate the proposal.
     */
    public function storeProposal(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'research_question' => 'required|string',
            'objectives' => 'required|string',
            'methodology_type' => 'required|string|in:survey,qualitative,mixed',
            'scope' => 'nullable|string',
            'style' => 'required|string|in:apa7,mla9,harvard,chicago,ieee,vancouver,oscola',
        ]);

        $proposal = ResearchProposal::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'research_question' => $validated['research_question'],
            'objectives' => $validated['objectives'],
            'methodology_type' => $validated['methodology_type'],
            'scope' => $validated['scope'],
            'style' => $validated['style'],
            'status' => 'draft'
        ]);

        // Trigger AI Generation
        $this->proposalService->generateProposal($proposal);

        return redirect()->route('research-proposal.show', $proposal->id)
            ->with('success', 'Your formal research proposal has been drafted!');
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

        // Generate the academic sections using the NEW iterative pipeline
        $reportData = $this->synthesisService->generateIterativeReport($survey, $style, $manualReferences);

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
        $userRemoveBranding = $user->remove_km_branding;
        $userOrgName = $user->export_org_name;
        $userLogo = $user->export_logo_url;

        if ($survey) {
            return [
                'showKmBranding' => !($canRemove && ($userRemoveBranding || $survey->remove_km_branding)),
                'customLogo' => ($canRemove) ? ($survey->export_logo_url ?: $userLogo) : null,
                'customOrgName' => ($canRemove) ? ($survey->export_org_name ?: $userOrgName) : null,
            ];
        }

        return [
            'showKmBranding' => !($canRemove && $userRemoveBranding),
            'customLogo' => ($canRemove && $userLogo) ? $userLogo : null,
            'customOrgName' => ($canRemove && $userOrgName) ? $userOrgName : null,
        ];
    }

    public function destroy(ResearchProposal $research_proposal)
    {
        $this->authorizeOwner($research_proposal);
        $research_proposal->delete();
        return back()->with('success', 'Draft report deleted successfully.');
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
