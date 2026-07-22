<?php

namespace App\Http\Controllers;

use App\Services\AiHumanizerService;
use App\Services\DocumentExtractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class HumanizerController extends Controller
{
    public function __construct(
        private readonly AiHumanizerService $aiHumanizerService,
        private readonly DocumentExtractionService $documentExtractionService
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
            'text' => 'required|string',
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

        try {
            $analysis = $this->aiHumanizerService->analyzeText($text);

            if ($analyzeOnly) {
                return response()->json(['analysis' => $analysis]);
            }

            $humanizedText = $this->aiHumanizerService->humanizeText($text, $mode, $intensity, $customInstructions);
            $newAnalysis = $this->aiHumanizerService->analyzeText($humanizedText);

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

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:51200', // 50MB limit
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['docx', 'doc', 'pdf', 'txt', 'csv', 'xlsx', 'xls'];

        if (!in_array($extension, $allowedExtensions, true)) {
            return response()->json([
                'errors' => ['file' => ['The uploaded file extension (.' . $extension . ') is not supported. Please upload a Word doc, PDF, Excel, CSV, or Text file.']],
                'message' => 'Invalid file extension.'
            ], 422);
        }
        $tempPath = 'temp/' . \Illuminate\Support\Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());

        try {
            \Illuminate\Support\Facades\Storage::disk('local')->put($tempPath, file_get_contents($file->getRealPath()));
            $extractedText = $this->documentExtractionService->extractText($file, $tempPath);
            \Illuminate\Support\Facades\Storage::disk('local')->delete($tempPath);

            return response()->json([
                'text' => $extractedText
            ]);
        } catch (\Throwable $e) {
            if (\Illuminate\Support\Facades\Storage::disk('local')->exists($tempPath)) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($tempPath);
            }
            Log::error('Humanizer Document Extraction Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Extraction failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadDocx(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
            'filename' => 'nullable|string'
        ]);

        $text = $request->input('text');
        $filename = $request->input('filename', 'humanized_document');
        $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);
        if (!str_ends_with($filename, '.docx')) {
            $filename .= '.docx';
        }

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();

        $paragraphs = preg_split('/\n/', $text);
        foreach ($paragraphs as $para) {
            $section->addText(trim($para));
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'phpword');
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
