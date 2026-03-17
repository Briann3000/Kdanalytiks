<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Services\AcademicSynthesisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResearchProposalController extends Controller
{
    public $synthesisService;

    public function __construct(AcademicSynthesisService $synthesisService)
    {
        $this->synthesisService = $synthesisService;
    }

    /**
     * Show the main page of the Research Proposal Studio.
     */
    public function index()
    {
        $user = auth()->user();
        $role = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;

        if ($role === 'admin') {
            $surveys = Survey::all();
        } elseif (in_array($role, ['organization', 'independent'])) {
            // For researchers, show their own surveys and public ones
            $surveys = Survey::where('user_id', $user->id)
                ->orWhere('type', \App\Enums\SurveyType::Public)
                ->get();
        } else {
            // For respondents, only show public active surveys
            $surveys = Survey::where('type', \App\Enums\SurveyType::Public)
                ->where('status', \App\Enums\SurveyStatus::Active)
                ->get();
        }

        return view('admin.research-proposal.index', compact('surveys'));
    }

    /**
     * Generate a proposal from a survey.
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

        $survey = Survey::findOrFail($request->survey_id);
        $style = $request->style;
        $format = $request->input('format');
        $manualReferences = $request->input('references', []);

        // Filter out empty references
        $manualReferences = array_filter($manualReferences, function($ref) {
            return !empty($ref['author']) || !empty($ref['title']);
        });

        // Generate the academic sections using AI
        $content = $this->synthesisService->generateFullReport($survey, $style, $manualReferences);

        $filename = 'research_proposal_' . $survey->id . '_' . time();
        
        if ($format === 'docx') {
            $path = $this->synthesisService->exportToDocx($content, $filename);
        } else {
            $path = $this->synthesisService->exportToPdf($content, $filename);
        }

        return response()->download($path);
    }
}
