<?php

namespace App\Http\Controllers;

use App\Models\CompiledReport;
use App\Models\Survey;
use App\Models\SurveyInferentialAnalysis;
use App\Services\AiService;
use App\Services\DocumentExtractionService;
use App\Services\ThesisCompilationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class ResearchStudioController extends Controller
{
    public function __construct(
        private readonly ThesisCompilationService $compilationService,
        private readonly DocumentExtractionService $extractionService,
        private readonly AiService $aiService
    ) {
    }

    public function createReport()
    {
        $surveys = Survey::where('created_by', auth()->id())->latest()->get();
        return view('research-studio.generate_report', compact('surveys'));
    }

    public function getInferentialTests(Survey $survey)
    {
        if ($survey->created_by !== auth()->id()) {
            return response()->json(['tests' => []], 403);
        }

        $tests = SurveyInferentialAnalysis::where('survey_id', $survey->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['tests' => $tests]);
    }

    public function reportHistory()
    {
        $reports = CompiledReport::where('user_id', auth()->id())->latest()->get();
        return view('research-studio.report_history', compact('reports'));
    }

    public function destroyReport(CompiledReport $report)
    {
        if ($report->user_id !== auth()->id()) {
            abort(403);
        }

        $report->delete();

        return redirect()->back()->with('success', __('Report deleted successfully.'));
    }

    public function downloadReport(CompiledReport $report, \App\Services\ThesisCompilationService $compiler)
    {
        $user = auth()->user();
        if ($report->user_id !== $user->id) {
            abort(403);
        }

        if (!$user->hasActiveSubscription() && ($user->free_report_export_count ?? 0) >= 2) {
            return redirect()->route('subscriptions.index')->with('error', __('Upgrade Required: Free accounts are limited to 2 free report DOCX exports. Please upgrade to Pro or Enterprise for unlimited exports.'));
        }

        // 1. Get the compiled PHPWord object
        $phpWord = $compiler->compileChapters(
            $report->title ?? 'Research Survey Report',
            $report->proofread_chapters ?? [], // or $report->proofread_content ?? []
            $report->chapter4_content ?? [],     // This holds your $item stats & charts
            $report->chapter5_content ?? [],     // Chapter 5 sections
            $report->branding_color ?? '4f46e5'
        );

        // 2. Overwrite / save directly to the report's path
        $relativePath = $report->final_docx_path ?? ('compiled_reports/thesis_' . $report->id . '.docx');
        $absolutePath = storage_path('app/' . ltrim($relativePath, '/'));

        // Ensure directory exists
        if (!file_exists(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0755, true);
        }

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($absolutePath);

        if (!$user->hasActiveSubscription() && !$user->isAdmin()) {
            $user->increment('free_report_export_count');
        }

        \Illuminate\Support\Facades\Log::info("Fresh Thesis DOCX compiled at: " . $absolutePath);

        return response()->download($absolutePath, \Illuminate\Support\Str::slug($report->title ?? 'thesis') . '.docx');
    }

    public function createProofread()
    {
        return view('research-studio.proofread');
    }

    public function processProofread(Request $request)
    {
        @set_time_limit(600);

        $user = $request->user();
        if ($user && !$user->canProofread()) {
            return redirect()->back()->with('error', __('Upgrade Required: Your document proofreading limit has been reached for your current plan (Free: 1, Pro: 10/mo, Enterprise: Unlimited). Please upgrade your subscription.'));
        }

        $request->validate([
            'file' => 'required|file|max:20480|mimes:docx,doc,txt',
        ]);

        $file = $request->file('file');
        $uuid = Str::uuid();
        $tempPath = 'temp/' . $uuid . '.' . $file->getClientOriginalExtension();
        Storage::disk('local')->put($tempPath, file_get_contents($file->getRealPath()));

        try {
            $text = $this->extractionService->extractText($file, $tempPath);
            Storage::disk('local')->delete($tempPath);

            if ($user && !$user->isAdmin()) {
                $user->increment('proofread_count');
            }

            $rawParagraphs = preg_split('/\n+/', $text);
            $processed = [];
            $seen = [];
            foreach ($rawParagraphs as $para) {
                $para = trim($para);
                if (empty($para) || strlen($para) < 5)
                    continue;

                // Deduplicate identical duplicate lines
                $hash = md5(mb_strtolower($para));
                if (isset($seen[$hash]))
                    continue;
                $seen[$hash] = true;

                // Check if line is a structural heading
                $isHeading = (bool) preg_match('/^(CHAPTER\s+\d+|[1-5]\.\d+(\.\d+)?\s+[A-Z])/i', $para);

                // Send to AI for safe proofreading (enforcing grammar only, no rewrite)
                $systemPrompt = "You are a professional academic copyeditor. Proofread the text for spelling, grammar, and punctuation mistakes ONLY. CRITICAL: You must NOT paraphrase, rewrite, restructure, simplify, or style the sentence. Keep the original wording, tone, and vocabulary completely identical, only fixing objective errors. Explicitly detect and split comma splices involving conjunctive adverbs (e.g., ', however', ', therefore', ', furthermore'). Replace them with a semicolon (e.g. '; however,') or a period. Return ONLY the corrected text. Do not add intro/outro comments.";

                $corrected = $para;
                if (!$isHeading) {
                    $corrected = $this->aiService->callAi($para, $systemPrompt);
                    $corrected = trim($corrected);
                    if (empty($corrected)) {
                        $corrected = $para;
                    }
                }

                $diffHtml = $this->generateDiffHtml($para, $corrected);

                $processed[] = [
                    'original' => $para,
                    'corrected' => $corrected,
                    'diff' => $diffHtml,
                    'isHeading' => $isHeading,
                    'status' => 'accepted' // 'accepted', 'rejected', 'edited'
                ];
            }

            return response()->json([
                'success' => true,
                'paragraphs' => $processed
            ]);
        } catch (\Exception $e) {
            if (Storage::disk('local')->exists($tempPath)) {
                Storage::disk('local')->delete($tempPath);
            }
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function downloadProofread(Request $request)
    {
        $paragraphsInput = $request->input('paragraphs');
        if (is_string($paragraphsInput)) {
            $paragraphsInput = json_decode($paragraphsInput, true);
        }

        if (!is_array($paragraphsInput)) {
            return back()->with('error', 'Invalid paragraphs data.');
        }

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        foreach ($paragraphsInput as $p) {
            $textToUse = $p['status'] === 'rejected' ? ($p['original'] ?? '') : ($p['corrected'] ?? $p['original'] ?? '');
            if (empty($textToUse))
                continue;

            $isHeading = !empty($p['isHeading']);
            if ($isHeading) {
                $section->addText($textToUse, ['name' => 'Arial', 'size' => 13, 'bold' => true]);
            } else {
                $section->addText($textToUse, ['name' => 'Arial', 'size' => 11]);
            }
            $section->addTextBreak(1);
        }

        $filename = 'proofread_thesis_' . time() . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'proofread_');
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    public function storeReport(Request $request)
    {
        @set_time_limit(600);
        $request->validate([
            'title' => 'required|string|max:255',
            'paragraphs' => 'required|array', // Chapters 1-3 proofread paragraphs
            'survey_id' => 'required|exists:surveys,id',
        ]);

        $survey = Survey::findOrFail($request->survey_id);

        // Load Chapter 4 Findings
        // We will fetch the survey descriptive stats
        $responses = $survey->responses()->with('answers.question')->get();
        // Use SurveyController's analytical method helper or construct descriptive data
        $surveyController = app(\App\Http\Controllers\SurveyController::class);
        $analyticalData = $surveyController->getAnalyticalData($survey, $responses, true, true);
        $analysis = $analyticalData['analysis'];

        // Prepare analysis payload for Chapter 4 (quantitative/qualitative sequential items)
        $chapter4Items = [];
        $systemColumns = ['respondent_id', 'respondent id', 'id', 'submission_id', 'created_at', 'updated_at', 'ip_address', 'user_agent'];

        foreach ($analysis as $item) {
            $labelLower = mb_strtolower(trim($item['label'] ?? ''));
            $idLower = mb_strtolower(trim($item['id'] ?? ''));
            if (in_array($labelLower, $systemColumns) || in_array($idLower, $systemColumns)) {
                continue; // Exclude system columns like Respondent ID
            }

            // Sanitize AI insight string if present (strip numeric vectors like "(0, 0, 100)")
            if (!empty($item['aiInsight']) && is_string($item['aiInsight'])) {
                $item['aiInsight'] = preg_replace('/^\s*[\(\[]?\s*\d+[\s,]+\d+[\s,]+\d+\s*[\)\]]?\s*/', '', $item['aiInsight']);
            }

            $chapter4Items[] = $item;
        }

        // Auto-generate Chapter 5 conclusions using the findings summary
        $findingsSummary = "";
        foreach ($chapter4Items as $idx => $item) {
            $findingsSummary .= ($idx + 1) . ". Item: " . ($item['label'] ?? '') . "\n";
            if (!empty($item['isChartable']) && !empty($item['stats'])) {
                foreach ($item['stats'] as $stat) {
                    if (!isset($stat['is_missing']) || !$stat['is_missing']) {
                        $findingsSummary .= "  - Option '{$stat['value']}': {$stat['count']} responses ({$stat['percentage']}%)\n";
                    }
                }
            }
            if (!empty($item['aiInsight']) && is_string($item['aiInsight'])) {
                $findingsSummary .= "  - Trend: " . Str::limit($item['aiInsight'], 200) . "\n";
            }
        }

        $systemPromptCh5 = "You are a senior academic supervisor. Write Chapter 5 (Summary, Conclusions, and Recommendations) for a dissertation based on these Chapter 4 findings. Return a JSON object with keys 'Summary of Findings', 'Conclusions', and 'Recommendations'. Write strictly in formal academic past tense (e.g. 'the study found', 'results indicated', 'participants reported'). Do not output anything else but the raw JSON object.";
        $ch5ResultJson = $this->aiService->callAi($findingsSummary, $systemPromptCh5, true);
        $ch5Decoded = json_decode($ch5ResultJson, true);

        $chapter5Sections = [
            'Summary of Findings' => $ch5Decoded['Summary of Findings'] ?? 'The study examined key empirical variables and synthesized quantitative response distributions across all research objectives.',
            'Conclusions' => $ch5Decoded['Conclusions'] ?? 'Based on the empirical findings, appropriate conclusions were drawn regarding the research objectives.',
            'Recommendations' => $ch5Decoded['Recommendations'] ?? 'Future research and policy actions are recommended to expand upon the variables identified in this study.'
        ];

        // Past-Tense Enforcement & Normalization:
        foreach ($chapter4Items as &$item) {
            if (!empty($item['aiInsight']) && is_string($item['aiInsight']) && empty($item['isInferential'])) {
                $pastPrompt = "Rewrite the following academic analysis section to strictly use academic past tense (e.g. 'indicated', 'showed', 'participants responded'). Do not summarize or change findings. Return ONLY the rewritten text.";
                $item['aiInsight'] = $this->aiService->callAi($item['aiInsight'], $pastPrompt);
            }
        }
        unset($item);

        // Compile to DOCX
        $phpWord = $this->compilationService->compileChapters(
            $request->title,
            $request->paragraphs,
            $chapter4Items,
            $chapter5Sections,
            $survey->brand_color ?? '4f46e5'
        );

        $filename = 'thesis_report_' . time() . '_' . Str::uuid() . '.docx';
        $storageDir = 'compiled_reports';
        if (!Storage::disk('local')->exists($storageDir)) {
            Storage::disk('local')->makeDirectory($storageDir);
        }
        $relativePath = $storageDir . '/' . $filename;
        $absolutePath = storage_path('app/' . $relativePath);
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($absolutePath);

        // Save CompiledReport in Database
        $report = CompiledReport::create([
            'user_id' => auth()->id(),
            'survey_id' => $survey->id,
            'title' => $request->title,
            'original_chapters_path' => null,
            'proofread_chapters' => $request->paragraphs,
            'chapter4_content' => $chapter4Items,
            'chapter5_content' => $chapter5Sections,
            'final_docx_path' => $relativePath,
            'status' => 'completed'
        ]);

        return response()->json([
            'success' => true,
            'report_id' => $report->id,
            'preview_url' => route('research-studio.report.preview', $report->id),
            'download_url' => route('research-studio.report.download', $report->id)
        ]);
    }

    public function previewReport(CompiledReport $report)
    {
        $user = auth()->user();
        if ($report->user_id !== $user->id) {
            abort(403);
        }

        $isTruncated = !$user->hasActiveSubscription();

        return view('research-studio.preview_report', compact('report', 'isTruncated'));
    }

    private function generateDiffHtml(string $old, string $new): string
    {
        $oldWords = preg_split('/\s+/', $old);
        $newWords = preg_split('/\s+/', $new);

        $matrix = [];
        $lenOld = count($oldWords);
        $lenNew = count($newWords);

        for ($i = 0; $i <= $lenOld; $i++) {
            $matrix[$i][0] = 0;
        }
        for ($j = 0; $j <= $lenNew; $j++) {
            $matrix[0][$j] = 0;
        }

        for ($i = 1; $i <= $lenOld; $i++) {
            for ($j = 1; $j <= $lenNew; $j++) {
                if ($oldWords[$i - 1] === $newWords[$j - 1]) {
                    $matrix[$i][$j] = $matrix[$i - 1][$j - 1] + 1;
                } else {
                    $matrix[$i][$j] = max($matrix[$i - 1][$j], $matrix[$i][$j - 1]);
                }
            }
        }

        $i = $lenOld;
        $j = $lenNew;
        $diff = [];

        while ($i > 0 || $j > 0) {
            if ($i > 0 && $j > 0 && $oldWords[$i - 1] === $newWords[$j - 1]) {
                array_unshift($diff, htmlspecialchars($oldWords[$i - 1]));
                $i--;
                $j--;
            } elseif ($j > 0 && ($i == 0 || $matrix[$i][$j - 1] >= $matrix[$i - 1][$j])) {
                array_unshift($diff, '<ins class="bg-emerald-100 text-emerald-800 px-1 rounded font-bold">' . htmlspecialchars($newWords[$j - 1]) . '</ins>');
                $j--;
            } elseif ($i > 0 && ($j == 0 || $matrix[$i][$j - 1] < $matrix[$i - 1][$j])) {
                array_unshift($diff, '<del class="bg-rose-100 text-rose-800 px-1 rounded line-through decoration-rose-400 font-bold">' . htmlspecialchars($oldWords[$i - 1]) . '</del>');
                $i--;
            }
        }

        return implode(' ', $diff);
    }
}
