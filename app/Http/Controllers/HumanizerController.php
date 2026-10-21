<?php

namespace App\Http\Controllers;

use App\Services\AiHumanizerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class HumanizerController extends Controller
{
    public function __construct(
        private readonly AiHumanizerService $aiHumanizerService
    ) {
    }

    public function index(Request $request): View
    {
        return view('surveys.humanizer');
    }

    public function process(Request $request): JsonResponse
    {
        @set_time_limit(300);
        $request->validate([
            'text' => 'required|string|max:30000',
            'mode' => 'nullable|string|in:standard,academic,creative',
            'intensity' => 'nullable|string|in:low,medium,high',
            'custom_instructions' => 'nullable|string|max:1000',
            'analyze_only' => 'nullable|boolean'
        ]);

        $text = $request->input('text');
        $mode = $request->input('mode', 'standard');
        $intensity = $request->input('intensity', 'medium');
        $customInstructions = $request->input('custom_instructions');
        $analyzeOnly = (bool) $request->input('analyze_only', false);

        // Enforce user subscription tier limits on actual humanization requests
        $user = $request->user();
        if (!$analyzeOnly && $user && !$user->canProofread()) {
            return response()->json([
                'error' => 'Quota exceeded',
                'message' => 'You have reached your free humanization limit. Please upgrade to Pro for unlimited access.'
            ], 403);
        }

        try {
            $analysis = $this->aiHumanizerService->analyzeText($text);

            if ($analyzeOnly) {
                return response()->json(['analysis' => $analysis]);
            }

            $humanizedText = $this->aiHumanizerService->humanizeText($text, $mode, $intensity, $customInstructions);
            $newAnalysis = $this->aiHumanizerService->analyzeText($humanizedText);

            if ($user) {
                $user->increment('proofread_count');
            }

            return response()->json([
                'original_analysis' => $analysis,
                'humanized_text' => $humanizedText,
                'humanized_analysis' => $newAnalysis
            ]);
        } catch (\Throwable $e) {
            Log::error('Humanizer Standalone Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Humanizer service error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadDocx(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:100000',
            'filename' => 'nullable|string|max:100'
        ]);

        $text = $request->input('text');
        $filename = $request->input('filename', 'humanized_document');
        $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);
        if (!str_ends_with($filename, '.docx')) {
            $filename .= '.docx';
        }

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();

        // Sanitize control characters that corrupt Word document XML
        $cleanText = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text);

        // Split text into major paragraphs preserving paragraph spacing
        $paragraphs = preg_split('/(\r?\n){2,}/u', $cleanText);

        foreach ($paragraphs as $index => $paragraph) {
            $trimmed = trim($paragraph);
            if ($trimmed === '') {
                continue;
            }

            // Handle internal single linebreaks within a paragraph
            $lines = preg_split('/\r?\n/u', $trimmed);
            $textRun = $section->addTextRun();
            foreach ($lines as $lIndex => $line) {
                if ($lIndex > 0) {
                    $textRun->addTextBreak(1);
                }
                $textRun->addText(trim($line));
            }

            // Paragraph spacing
            if ($index < count($paragraphs) - 1) {
                $section->addTextBreak(1);
            }
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'phpword');
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
