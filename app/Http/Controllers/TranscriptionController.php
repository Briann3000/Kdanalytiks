<?php

namespace App\Http\Controllers;

use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TranscriptionController extends Controller
{
    public function __construct(
        private readonly AiService $aiService
    ) {
    }

    /**
     * Display the standalone transcription view.
     */
    public function index(): View
    {
        return view('transcription.index');
    }

    /**
     * Handle media file upload or recorded audio/video blob and transcribe it.
     */
    public function transcribe(Request $request): JsonResponse
    {
        @set_time_limit(300);

        $request->validate([
            'file' => 'required|file|max:51200', // 50MB max limit
        ]);

        $user = $request->user();
        if ($user && !$user->canTranscribe()) {
            return response()->json([
                'success' => false,
                'message' => __('Upgrade Required: Your transcription limit has been reached for your current plan (Free: 1, Pro: 10/mo, Enterprise: Unlimited). Please upgrade to transcribe more media.')
            ], 403);
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'webm');
        $allowedExtensions = ['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm', 'ogg', 'mov', 'aac', 'flac'];

        if (!in_array($extension, $allowedExtensions, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unsupported format (.' . $extension . '). Supported formats: MP3, MP4, WAV, WEBM, M4A, OGG, MPEG, AAC, FLAC.'
            ], 422);
        }

        $tempFileName = 'transcription_' . Str::uuid() . '.' . $extension;
        $tempPath = 'temp/' . $tempFileName;

        try {
            Storage::disk('local')->put($tempPath, file_get_contents($file->getRealPath()));
            $absolutePath = Storage::disk('local')->path($tempPath);

            $transcription = $this->aiService->transcribeMedia($absolutePath);

            if ($user && !$user->isAdmin()) {
                $user->increment('transcription_count');
            }

            if (Storage::disk('local')->exists($tempPath)) {
                Storage::disk('local')->delete($tempPath);
            }

            if (empty($transcription)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not extract transcription text from the provided media file. Please check audio quality or try again.'
                ], 422);
            }

            return response()->json([
                'success' => true,
                'transcription' => $transcription,
                'word_count' => str_word_count($transcription),
                'char_count' => mb_strlen($transcription)
            ]);
        } catch (\Throwable $e) {
            if (Storage::disk('local')->exists($tempPath)) {
                Storage::disk('local')->delete($tempPath);
            }

            Log::error('Media Transcription Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during transcription processing: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Flash transcription context to the session and return a redirect URL
     * so the client can navigate to the standalone Socius page with context.
     */
    public function analyzeWithSocius(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transcription_text' => ['required', 'string', 'max:50000'],
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        // Flash the context so StandaloneSociusController::index() can pick it up
        session()->flash('socius_standalone_context', [
            'type' => 'transcription',
            'text' => $validated['transcription_text'],
            'label' => $validated['label'] ?? __('AI Transcription'),
        ]);

        return response()->json([
            'redirect' => route('socius.chat.index'),
        ]);
    }
}
