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
        $surveys = \App\Models\Survey::where('user_id', $user->id)
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
            'style' => 'required|string|in:apa7,mla9,harvard',
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
    public function show(ResearchProposal $proposal)
    {
        $this->authorizeOwner($proposal);
        return view('admin.research-proposal.show', compact('proposal'));
    }

    /**
     * Generate a report from a survey and redirect to preview.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'survey_id' => 'required|exists:surveys,id',
            'style' => 'required|string|in:apa7,mla9,harvard',
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
        $manualReferences = array_filter($manualReferences, function($ref) {
            return !empty($ref['author']) || !empty($ref['title']);
        });

        // Generate the academic sections using the NEW iterative pipeline
        $reportData = $this->synthesisService->generateIterativeReport($survey, $style, $manualReferences);

        // Store the report in the session temporarily for the preview
        $reportId = uniqid('report_');
        session([$reportId => $reportData]);

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
        $reportData = session($reportId);
        if (!$reportData) {
            return redirect()->route('research-proposal.index')->with('error', 'Report draft not found or expired.');
        }

        $format = $request->input('format', 'pdf');
        return view('admin.research-proposal.preview', compact('reportData', 'reportId', 'format'));
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

        if ($format === 'docx') {
            $path = $this->synthesisService->exportToDocx($reportData['sections'], $filename);
        } else {
            $path = $this->synthesisService->exportToPdf($reportData['sections'], $filename);
        }

        return response()->download($path);
    }

    /**
     * Export a saved Research Proposal.
     */
    public function exportProposal(ResearchProposal $proposal)
    {
        $this->authorizeOwner($proposal);
        $filename = 'research_proposal_' . str($proposal->title)->slug() . '_' . time();
        $path = $this->synthesisService->exportToDocx($proposal->content, $filename);
        return response()->download($path);
    }

    public function destroy(ResearchProposal $proposal)
    {
        $this->authorizeOwner($proposal);
        $proposal->delete();
        return back()->with('success', 'Draft report deleted successfully.');
    }

    private function authorizeOwner(ResearchProposal $proposal)
    {
        if (auth()->user()->role === \App\Enums\UserRole::Admin || auth()->user()->role === 'admin') {
            return;
        }

        if ((int)$proposal->user_id !== (int)auth()->id()) {
            abort(403);
        }
    }
}
