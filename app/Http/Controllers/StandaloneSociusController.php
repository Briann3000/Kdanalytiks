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

        // Optionally receive initial context seeded from another feature (e.g. transcription)
        $initialContext = session()->pull('socius_standalone_context', null);

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

                $result = $this->groqStreamingClient->streamChatCompletion(
                    $messages,
                    function (string $delta) use (&$assistantContent) {
                        $assistantContent .= $delta;
                        $this->emitEvent('delta', ['content' => $delta]);
                    },
                    $model
                );

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

                $assistantMessage->update([
                    'content' => $assistantContent,
                    'metadata' => ['status' => 'failed', 'error' => $e->getMessage()],
                ]);

                $this->emitEvent('error', ['message' => 'Something went wrong. Please try again.']);
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
}
