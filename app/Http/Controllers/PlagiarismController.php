<?php

namespace App\Http\Controllers;

use App\Models\PlagiarismScan;
use App\Models\PlagiarismMatch;
use App\Services\Plagiarism\DocumentParserService;
use App\Services\Plagiarism\SimilarityDetectionService;
use App\Services\Plagiarism\ReportGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlagiarismController extends Controller
{
    public function __construct(
        private readonly DocumentParserService $parserService,
        private readonly SimilarityDetectionService $similarityService,
        private readonly ReportGeneratorService $reportService
    ) {
    }

    /**
     * Display scan history and quota overview
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = PlagiarismScan::where('user_id', $user->id)->latest();

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $scans = $query->paginate(12);

        $tierSlug = $user->effectiveTierSlug();
        $wordLimit = $user->plagiarismWordLimit();
        $remainingScans = $user->remainingPlagiarismScans();
        $canScan = $user->canCheckPlagiarism();

        return view('plagiarism.index', compact(
            'scans',
            'tierSlug',
            'wordLimit',
            'remainingScans',
            'canScan'
        ));
    }

    /**
     * Show upload / direct text input page
     */
    public function create()
    {
        $user = Auth::user();
        if (!$user->canCheckPlagiarism()) {
            return redirect()->route('plagiarism.index')->with(
                'error',
                __('Scan Limit Reached: You have used your available plagiarism checks for this billing cycle. Please upgrade your subscription to continue.')
            );
        }

        $wordLimit = $user->plagiarismWordLimit();
        $tierSlug = $user->effectiveTierSlug();

        return view('plagiarism.create', compact('wordLimit', 'tierSlug'));
    }

    /**
     * Process document and initiate plagiarism scan
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->canCheckPlagiarism()) {
            return redirect()->route('plagiarism.index')->with(
                'error',
                __('Scan Limit Reached: Please upgrade your subscription to Pro or Enterprise for additional scans.')
            );
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'input_type' => 'required|in:file,text',
            'document' => 'nullable|file|mimes:pdf,docx,txt|max:20480', // 20MB max
            'content' => 'nullable|string',
            'exclude_quotes' => 'nullable|boolean',
            'exclude_references' => 'nullable|boolean',
            'exclude_small_matches' => 'nullable|boolean',
        ]);

        // 1. Parse content
        $parsed = null;
        if ($request->input_type === 'file' && $request->hasFile('document')) {
            $parsed = $this->parserService->parseFile($request->file('document'));
        } elseif ($request->filled('content')) {
            $parsed = $this->parserService->parseFile($request->content, $request->title);
        }

        if (!$parsed || empty(trim($parsed['content']))) {
            return back()->withInput()->withErrors([
                'content' => __('Please provide text content or upload a valid DOCX, PDF, or TXT file.')
            ]);
        }

        // 2. Check tier word limit
        $wordLimit = $user->plagiarismWordLimit();
        if ($parsed['word_count'] > $wordLimit) {
            return back()->withInput()->withErrors([
                'content' => __("Document length (:count words) exceeds your current tier limit (:limit words). Please split the document or upgrade to Pro/Enterprise.", [
                    'count' => number_format($parsed['word_count']),
                    'limit' => number_format($wordLimit),
                ])
            ]);
        }

        if ($parsed['word_count'] < 30) {
            return back()->withInput()->withErrors([
                'content' => __('Document is too short for a meaningful academic originality scan (minimum 30 words required).')
            ]);
        }

        // 3. Create scan record
        $scan = PlagiarismScan::create([
            'user_id' => $user->id,
            'organization_id' => $user->activeOrganization()?->id,
            'title' => $request->title,
            'original_filename' => $parsed['original_filename'],
            'file_type' => $parsed['file_type'],
            'content' => $parsed['content'],
            'word_count' => $parsed['word_count'],
            'character_count' => $parsed['character_count'],
            'exclude_quotes' => $request->boolean('exclude_quotes', true),
            'exclude_references' => $request->boolean('exclude_references', true),
            'exclude_citations' => $request->boolean('exclude_citations', true),
            'exclude_small_matches' => $request->boolean('exclude_small_matches', true),
            'min_words_threshold' => max(4, min(25, $request->integer('min_words_threshold', 8))),
            'excluded_domains' => $request->input('excluded_domains', []),
            'status' => 'pending',
        ]);

        // 4. Increment scan counter
        $user->increment('plagiarism_scan_count');

        // 5. Execute scan
        $this->similarityService->executeScan($scan);

        return redirect()->route('plagiarism.show', $scan)->with('success', __('Plagiarism scan completed successfully.'));
    }

    /**
     * Show interactive dual-pane diagnostic report
     */
    public function show(PlagiarismScan $scan)
    {
        $this->authorizeAccess($scan);

        $scan->load([
            'matches' => function ($q) {
                $q->orderBy('similarity_score', 'desc');
            }
        ]);

        return view('plagiarism.report', compact('scan'));
    }

    /**
     * Toggle match or setting exclusion via AJAX and recalculate score
     */
    public function toggleExclusion(Request $request, PlagiarismScan $scan)
    {
        $this->authorizeAccess($scan);

        $request->validate([
            'match_id' => 'nullable|exists:plagiarism_matches,id',
            'exclude_quotes' => 'nullable|boolean',
            'exclude_references' => 'nullable|boolean',
            'exclude_citations' => 'nullable|boolean',
            'min_words_threshold' => 'nullable|integer|min:4|max:25',
            'add_excluded_domain' => 'nullable|string|max:100',
            'remove_excluded_domain' => 'nullable|string|max:100',
        ]);

        if ($request->filled('match_id')) {
            $match = PlagiarismMatch::where('scan_id', $scan->id)->findOrFail($request->match_id);
            $match->update(['is_excluded' => !$match->is_excluded]);
        }

        if ($request->has('exclude_quotes')) {
            $scan->update(['exclude_quotes' => $request->boolean('exclude_quotes')]);
        }

        if ($request->has('exclude_references')) {
            $scan->update(['exclude_references' => $request->boolean('exclude_references')]);
        }

        if ($request->has('exclude_citations')) {
            $scan->update(['exclude_citations' => $request->boolean('exclude_citations')]);
        }

        if ($request->filled('min_words_threshold')) {
            $scan->update(['min_words_threshold' => $request->integer('min_words_threshold')]);
        }

        $excludedDomains = is_array($scan->excluded_domains) ? $scan->excluded_domains : [];
        if ($request->filled('add_excluded_domain')) {
            $cleanDomain = strtolower(trim(parse_url($request->add_excluded_domain, PHP_URL_HOST) ?? $request->add_excluded_domain));
            if (!empty($cleanDomain) && !in_array($cleanDomain, $excludedDomains)) {
                $excludedDomains[] = $cleanDomain;
                $scan->update(['excluded_domains' => array_values($excludedDomains)]);
            }
        }

        if ($request->filled('remove_excluded_domain')) {
            $domainToRemove = strtolower(trim($request->remove_excluded_domain));
            $excludedDomains = array_filter($excludedDomains, fn($d) => strtolower($d) !== $domainToRemove);
            $scan->update(['excluded_domains' => array_values($excludedDomains)]);
        }

        // Recalculate net similarity
        $scan->load('matches');
        $newScore = $this->similarityService->calculateNetSimilarity($scan->content, $scan->matches->all(), $scan);
        $scan->update(['similarity_percentage' => $newScore]);

        return response()->json([
            'success' => true,
            'similarity_percentage' => $newScore,
            'similarity_level' => $scan->similarity_level,
            'active_matches_count' => $scan->activeMatches()->count(),
            'excluded_domains' => $scan->excluded_domains ?? [],
        ]);
    }

    /**
     * Download formal PDF similarity report
     */
    public function exportPdf(PlagiarismScan $scan)
    {
        $this->authorizeAccess($scan);
        return $this->reportService->generatePdf($scan);
    }

    /**
     * Delete scan record
     */
    public function destroy(PlagiarismScan $scan)
    {
        $this->authorizeAccess($scan);
        $scan->delete();

        return redirect()->route('plagiarism.index')->with('success', __('Scan record deleted successfully.'));
    }

    /**
     * Ensure user owns scan or belongs to authorized organization
     */
    private function authorizeAccess(PlagiarismScan $scan): void
    {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return;
        }

        if ($scan->user_id === $user->id) {
            return;
        }

        if ($scan->organization_id && $user->activeOrganization()?->id === $scan->organization_id) {
            return;
        }

        abort(403, __('You do not have permission to view this report.'));
    }
}
