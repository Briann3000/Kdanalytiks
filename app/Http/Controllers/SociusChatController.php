<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyAiAttachment;
use App\Models\SurveyAiMessage;
use App\Models\SurveyAiThread;
use App\Services\DocumentExtractionService;
use App\Services\GroqStreamingClient;
use App\Services\SurveyContextService;
use App\Services\SociusPromptBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SociusChatController extends Controller
{


    public function __construct(
        private readonly SurveyContextService $surveyContextService,
        private readonly DocumentExtractionService $documentExtractionService,
        private readonly GroqStreamingClient $groqStreamingClient,
        private readonly SociusPromptBuilder $sociusPromptBuilder,
        private readonly \App\Services\WebSearchService $webSearchService,
        private readonly \App\Services\MemoryExtractionService $memoryExtractionService,
        private readonly \App\Services\AiHumanizerService $aiHumanizerService,
    ) {
    }

    public function index(Survey $survey, Request $request): JsonResponse
    {
        $this->authorizeSurvey($survey);

        $threads = $this->threadQuery($survey, $request)
            ->with(['latestMessage', 'user'])
            ->withCount('messages')
            ->orderByDesc('is_pinned')
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn(SurveyAiThread $thread) => $this->serializeThread($thread));

        return response()->json(['threads' => $threads]);
    }

    public function store(Survey $survey, Request $request): JsonResponse
    {
        $this->authorizeSurvey($survey);
        $this->ensureAiEligible($request);

        $user = $request->user();
        $groupId = null;

        // If not owner/admin, associate with their group
        if (!$user->isAdmin() && (int) $survey->created_by !== (int) $user->id) {
            $group = $user->surveyGroups()->where('survey_id', $survey->id)->first();
            if ($group) {
                $groupId = $group->id;
            }
        } else {
            // Survey owner can specify which group this thread belongs to
            if ($request->has('group_id') && $request->group_id !== 'personal' && $request->group_id !== '') {
                $groupId = $request->group_id;
            }
        }

        $thread = $survey->aiThreads()->create([
            'user_id' => $user->id,
            'survey_group_id' => $groupId,
            'title' => 'New chat',
            'last_activity_at' => now(),
        ]);

        return response()->json([
            'thread' => $this->serializeThread($thread->loadMissing('latestMessage')),
        ], 201);
    }

    public function show(Survey $survey, SurveyAiThread $thread, Request $request): JsonResponse
    {
        $this->authorizeThread($survey, $thread, $request);

        $thread->load([
            'messages.attachments',
            'user',
            'latestMessage',
        ]);

        return response()->json([
            'thread' => $this->serializeThread($thread),
            'messages' => $thread->messages
                ->sortBy('id')
                ->values()
                ->map(fn(SurveyAiMessage $message) => $this->serializeMessage($message)),
        ]);
    }

    public function update(Survey $survey, SurveyAiThread $thread, Request $request): JsonResponse
    {
        $this->authorizeThread($survey, $thread, $request);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
        ]);

        $thread->update(['title' => trim($validated['title'])]);

        return response()->json(['thread' => $this->serializeThread($thread->loadMissing('latestMessage'))]);
    }

    public function destroy(Survey $survey, SurveyAiThread $thread, Request $request): JsonResponse
    {
        Log::info('Socius: destroy request received', [
            'survey_id' => $survey->id,
            'thread_id' => $thread->id,
            'user_id' => $request->user()->id
        ]);

        $this->authorizeThread($survey, $thread, $request);
        Log::info('Socius: destroy authorized');

        // Collect all attachment storage paths before deleting the records
        $attachmentPaths = $thread->attachments()->pluck('storage_path')->filter()->toArray();
        Log::info('Socius: found attachments to delete', ['count' => count($attachmentPaths)]);

        // Delete the thread. Database cascades will handle:
        // 1. survey_ai_messages (thread_id)
        // 2. survey_ai_attachments (thread_id AND message_id)
        $thread->delete();
        Log::info('Socius: thread deleted from database');

        // Cleanup physical files from storage
        foreach ($attachmentPaths as $path) {
            Storage::disk('local')->delete($path);
        }
        Log::info('Socius: attachment files cleaned up');

        return response()->json(['deleted' => true]);
    }

    public function togglePin(Survey $survey, SurveyAiThread $thread, Request $request): JsonResponse
    {
        $this->authorizeThread($survey, $thread, $request);

        $thread->update(['is_pinned' => !$thread->is_pinned]);

        return response()->json(['thread' => $this->serializeThread($thread->loadMissing('latestMessage'))]);
    }

    public function stream(Survey $survey, SurveyAiThread $thread, Request $request)
    {
        // Disable PHP execution timeout for this streaming endpoint.
        // Groq responses can take well beyond 60s for long prompts and the
        // default max_execution_time would silently kill the stream, leaving
        // an empty assistant message in the DB with no visible error.
        set_time_limit(0);

        $this->authorizeThread($survey, $thread, $request);
        $this->ensureAiEligible($request);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'include_survey_context' => ['nullable', 'boolean'],
            'review_mode_enabled' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'mimes:' . implode(',', config('socius.supported_extensions', ['pdf', 'csv', 'txt', 'docx'])),
                'max:' . (max(1, (int) config('socius.max_attachment_mb', 10)) * 1024),
            ],
        ]);

        $includeSurveyContext = $request->boolean('include_survey_context', true);
        $webSearchEnabled = $request->boolean('web_search_enabled', false);
        $reviewModeEnabled = $request->boolean('review_mode_enabled', false);
        $storedPaths = [];

        $userMessage = $thread->messages()->create([
            'user_id' => $request->user()->id,
            'role' => 'user',
            'content' => trim($validated['message']),
            'include_survey_context' => $includeSurveyContext,
            'metadata' => [
                'locale' => app()->getLocale(),
                'web_search_enabled' => $webSearchEnabled,
                'review_mode_enabled' => $reviewModeEnabled,
            ],
        ]);

        try {
            foreach ($request->file('attachments', []) as $file) {
                $this->storeAttachment($survey, $thread, $userMessage, $file, $storedPaths);
            }
        } catch (\Throwable $e) {
            foreach ($storedPaths as $storedPath) {
                Storage::disk('local')->delete($storedPath);
            }

            $userMessage->delete();

            return response()->json([
                'message' => $this->friendlyUploadErrorMessage($e),
            ], 422);
        }

        if ($thread->messages()->where('role', 'user')->count() === 1) {
            $thread->update([
                'title' => $this->generateTitle($userMessage->content),
            ]);
        }

        $messages = $this->buildGroqMessages($survey, $thread, $includeSurveyContext, $webSearchEnabled);
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

            $this->emitStreamEvent('meta', [
                'thread_id' => $thread->id,
                'user_message_id' => $userMessage->id,
                'assistant_message_id' => $assistantMessage->id,
            ]);

            try {
                $hasImages = $thread->messages()
                    ->whereHas('attachments', function ($q) {
                        $q->where('mime_type', 'like', 'image/%');
                    })->exists();

                $model = $this->sociusPromptBuilder->getModel($hasImages);

                $result = $this->groqStreamingClient->streamChatCompletion($messages, function (string $delta) use (&$assistantContent) {
                    $assistantContent .= $delta;
                    $this->emitStreamEvent('delta', ['content' => $delta]);
                }, $model);

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

                // Auto-extract long-term memory
                $this->memoryExtractionService->extractAndStore($thread);

                $this->emitStreamEvent('done', [
                    'thread_id' => $thread->id,
                    'assistant_message_id' => $assistantMessage->id,
                    'status' => 'complete',
                ]);
            } catch (\Throwable $e) {
                Log::error('Socius streaming failed.', [
                    'thread_id' => $thread->id,
                    'message' => $e->getMessage(),
                ]);

                $assistantMessage->update([
                    'content' => $assistantContent,
                    'metadata' => [
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ],
                ]);

                $this->emitStreamEvent('error', [
                    'message' => $this->friendlyStreamingErrorMessage($e),
                ]);
            }
        }, 200, $headers);
    }

    private function authorizeSurvey(Survey $survey): void
    {
        Gate::authorize('view', $survey);
    }

    private function authorizeThread(Survey $survey, SurveyAiThread $thread, Request $request): void
    {
        $this->authorizeSurvey($survey);

        abort_unless((int) $thread->survey_id === (int) $survey->id, 404);

        $user = $request->user();
        if (!$user->isAdmin()) {
            // Survey owner/creator can see their own personal chats OR any group chat
            if ((int) $survey->created_by === (int) $user->id) {
                if ($thread->survey_group_id) {
                    return;
                }
                abort_unless((int) $thread->user_id === (int) $user->id, 404);
                return;
            }

            // Normal users / students can only see their own chats
            abort_unless((int) $thread->user_id === (int) $user->id, 404);
        }
    }

    private function ensureAiEligible(Request $request): void
    {
        abort_unless($request->user()->canUseAiAnalysis(), 403, 'AI analysis is unavailable for your account right now.');
    }

    private function threadQuery(Survey $survey, Request $request)
    {
        $query = $survey->aiThreads()->getQuery();
        $user = $request->user();

        if ($user->isAdmin()) {
            if ($request->has('group_id') && $request->group_id !== 'personal' && $request->group_id !== '') {
                $query->where('survey_group_id', $request->group_id);
            } else {
                $query->whereNull('survey_group_id');
            }
        } elseif ((int) $survey->created_by === (int) $user->id) {
            // Survey owners can see any chat associated with a group, but only their own personal chats
            if ($request->has('group_id') && $request->group_id !== 'personal' && $request->group_id !== '') {
                $query->where('survey_group_id', $request->group_id);
            } else {
                $query->whereNull('survey_group_id')->where('user_id', $user->id);
            }
        } else {
            // Normal users / students can only see their own chats (personal or group-bound)
            $query->where('user_id', $user->id);
            if ($request->has('group_id') && $request->group_id !== 'personal' && $request->group_id !== '') {
                $query->where('survey_group_id', $request->group_id);
            } else {
                $query->whereNull('survey_group_id');
            }
        }

        return $query;
    }

    private function serializeThread(SurveyAiThread $thread): array
    {
        $latestMessage = $thread->relationLoaded('latestMessage') ? $thread->latestMessage : null;

        return [
            'id' => $thread->id,
            'title' => $thread->title,
            'user_id' => $thread->user_id,
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
            'include_survey_context' => $message->include_survey_context,
            'metadata' => $message->metadata,
            'created_at' => optional($message->created_at)->toIso8601String(),
            'attachments' => $message->attachments->map(fn(SurveyAiAttachment $attachment) => [
                'id' => $attachment->id,
                'original_name' => $attachment->original_name,
                'mime_type' => $attachment->mime_type,
                'size_bytes' => $attachment->size_bytes,
                'excerpt' => Str::limit((string) $attachment->extracted_text, 180),
            ])->values()->all(),
        ];
    }

    private function storeAttachment(
        Survey $survey,
        SurveyAiThread $thread,
        SurveyAiMessage $message,
        UploadedFile $file,
        array &$storedPaths
    ): SurveyAiAttachment {
        $prefix = trim((string) config('socius.storage_prefix', 'socius'), '/');
        $relativePath = sprintf(
            '%s/%d/%d/%s-%s.%s',
            $prefix,
            $survey->id,
            $thread->id,
            now()->format('YmdHis'),
            Str::uuid(),
            strtolower($file->getClientOriginalExtension())
        );

        Storage::disk('local')->put($relativePath, file_get_contents($file->getRealPath()));
        $storedPaths[] = $relativePath;

        $extractedText = $this->documentExtractionService->extractText($file, $relativePath);

        return $thread->attachments()->create([
            'message_id' => $message->id,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize() ?: 0,
            'storage_path' => $relativePath,
            'extracted_text' => $extractedText,
            'expires_at' => Carbon::now()->addDays(max(1, (int) config('socius.attachment_ttl_days', 7))),
        ]);
    }

    private function buildGroqMessages(
        Survey $survey,
        SurveyAiThread $thread,
        bool $includeSurveyContext,
        bool $webSearchEnabled = false
    ): array {
        // Fetch long-term project memory
        $memories = \App\Models\SurveyAiMemory::where('user_id', auth()->id())
            ->where('survey_id', $survey->id)
            ->orderByDesc('importance')
            ->limit(5)
            ->pluck('fact')
            ->toArray();

        // Fetch user active knowledge base rules
        $knowledgeBaseRules = [];
        if (auth()->check()) {
            $knowledgeBaseRules = auth()->user()
                ->sociusKnowledgeBases()
                ->where('is_active', true)
                ->pluck('content')
                ->toArray();
        }

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
            ['role' => 'system', 'content' => "User current language: {$currentLanguage}. You must respond in {$currentLanguage} by default. IMPORTANT: If the user communicates in a different language (e.g. Swahili, French, etc.), you MUST automatically detect it and converse in that language instead. Always follow the user's lead on language."],
        ];

        $reviewModeEnabled = false;
        if ($lastUserMessage = $thread->messages()->where('role', 'user')->latest('id')->first()) {
            $reviewModeEnabled = data_get($lastUserMessage->metadata, 'review_mode_enabled', false);
        }

        if ($reviewModeEnabled) {
            $messages[] = [
                'role' => 'system',
                'content' => "SPECIAL INSTRUCTION (Supervisor Review & Correction Mode):\nThe user has uploaded a report, article, draft, or list of comments with supervisor corrections. Your primary goal is to act as an editor to FIX and REVISE the current findings, report draft, or statistics to address all comments and correction notes raised by the supervisor. Write the revised outputs, sections, or corrected tables clearly. Ensure the revised draft fully incorporates all of the supervisor's feedback."
            ];
        }

        if ($includeSurveyContext) {
            $messages[] = [
                'role' => 'system',
                'content' => "Current survey context (hidden from end user):\n" . $this->surveyContextService->serializeForPrompt($survey),
            ];
        }

        // Inject real-time grounding if enabled
        if ($webSearchEnabled && $lastUserMessage = $thread->messages()->where('role', 'user')->latest('id')->first()) {
            try {
                $searchResult = $this->webSearchService->search($lastUserMessage->content);
                $messages[] = [
                    'role' => 'system',
                    'content' => "External Knowledge (Real-time Grounding):\n" . $searchResult,
                ];
            } catch (\Throwable $e) {
                Log::warning("Web search grounding failed: " . $e->getMessage());
            }
        }

        $history = $thread->messages()
            ->with('attachments')
            ->orderBy('id')
            ->get();

        foreach ($history as $message) {
            $content = $this->buildPromptContent($message);

            if (empty($content)) {
                continue;
            }

            $messages[] = [
                'role' => $message->role,
                'content' => $content,
            ];
        }

        return $messages;
    }

    private function isImage(?string $path): bool
    {
        if (!$path)
            return false;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
    }

    private function buildPromptContent(SurveyAiMessage $message): string|array
    {
        $content = trim((string) $message->content);

        if ($message->role === 'user' && $message->relationLoaded('attachments') && $message->attachments->isNotEmpty()) {
            $hasImages = $message->attachments->contains(fn($a) => $this->isImage($a->storage_path));

            if ($hasImages) {
                $payload = [
                    ['type' => 'text', 'text' => $content ?: 'Analyze the following image(s).']
                ];

                foreach ($message->attachments as $attachment) {
                    if ($this->isImage($attachment->storage_path)) {
                        try {
                            $imageData = base64_encode(Storage::disk('local')->get($attachment->storage_path));
                            $mime = $attachment->mime_type ?: 'image/jpeg';
                            $payload[] = [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => "data:{$mime};base64,{$imageData}"
                                ]
                            ];
                        } catch (\Throwable $e) {
                            Log::warning("Could not base64 encode image for Groq: " . $e->getMessage());
                        }
                    } else {
                        $payload[0]['text'] .= sprintf(
                            "\n\nAttachment: %s\nExtracted content:\n%s",
                            $attachment->original_name,
                            Str::limit((string) $attachment->extracted_text, 8000, '...')
                        );
                    }
                }
                return $payload;
            }

            $attachmentBlocks = $message->attachments
                ->map(function (SurveyAiAttachment $attachment) {
                    return sprintf(
                        "Attachment: %s\nExtracted content:\n%s",
                        $attachment->original_name,
                        Str::limit((string) $attachment->extracted_text, 12000, '...')
                    );
                })
                ->implode("\n\n");

            $content = trim($content . "\n\n" . $attachmentBlocks);
        }

        return $content;
    }

    private function generateTitle(string $content): string
    {
        return Str::limit(trim(preg_replace('/\s+/u', ' ', $content) ?? 'New chat'), 60, '...');
    }

    private function emitStreamEvent(string $event, array $payload): void
    {
        echo "event: {$event}\n";
        echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";

        if (function_exists('ob_flush')) {
            @ob_flush();
        }

        flush();
    }

    private function friendlyUploadErrorMessage(\Throwable $e): string
    {
        $message = strtolower($e->getMessage());

        if (str_contains($message, 'scanned pdfs are not supported')) {
            return 'This PDF looks like a scanned document, so Socius could not read text from it. Please upload a text-based PDF instead.';
        }

        if (str_contains($message, 'docx file could not be read')) {
            return 'That Word document could not be opened. Please save it again as a standard .docx file and retry.';
        }

        if (str_contains($message, 'does not contain readable text')) {
            return 'The uploaded file does not contain readable text for Socius to analyze.';
        }

        if (str_contains($message, 'not supported')) {
            return 'That file type is not supported yet. Please upload a PDF, CSV, TXT, or DOCX file.';
        }

        return 'Socius could not read that file. Technical details: ' . $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine();
    }

    public function export(Survey $survey, SurveyAiThread $thread, Request $request)
    {
        $this->authorizeThread($survey, $thread, $request);

        $format = $request->query('format', 'pdf');
        $messageId = $request->query('message_id');

        if ($messageId) {
            $messages = $thread->messages()
                ->where('id', $messageId)
                ->get();
            if ($messages->isEmpty()) {
                abort(404, 'Message not found');
            }
        } else {
            $messages = $thread->messages()->orderBy('id')->get();
        }

        return match ($format) {
            'pdf' => $this->exportToPdf($thread, $messages, (bool) $messageId),
            'docx' => $this->exportToDocx($thread, $messages, (bool) $messageId),
            'excel' => $this->exportToExcel($thread, $messages, (bool) $messageId),
            'markdown', 'md' => $this->exportToMarkdown($thread, $messages, (bool) $messageId),
            default => abort(404),
        };
    }

    private function exportToPdf(SurveyAiThread $thread, $messages, bool $isSingleMessage = false)
    {
        // Pre-process each message: parse markdown, render charts/mermaid as images
        $processedMessages = $messages->map(function ($message) {
            return [
                'role' => $message->role,
                'created_at' => $message->created_at,
                'html' => $message->role === 'assistant'
                    ? $this->renderSociusMarkdownHtml($message->content)
                    : '<p>' . nl2br(e($message->content)) . '</p>',
            ];
        });

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.socius-thread', [
            'thread' => $thread,
            'processedMessages' => $processedMessages,
            'isSingleMessage' => $isSingleMessage,
        ])->setPaper('a4', 'portrait');

        $filename = $isSingleMessage
            ? 'socius-report-' . $messages->first()->id . '.pdf'
            : Str::slug($thread->title ?: 'socius-chat') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Convert raw markdown message content into export-ready HTML.
     * - chartjs blocks ? QuickChart.io PNG image tags
     * - mermaid blocks ? mermaid.ink PNG image tags
     * - pollinations blocks ? rendered image tags when available
     * - remaining text ? parsed through league/commonmark (GFM tables, headings, etc.)
     */
    private function renderSociusMarkdownHtml(string $content): string
    {
        $content = preg_replace_callback(
            '/```chartjs\s*([\s\S]*?)```/i',
            function ($matches) {
                $json = trim($matches[1]);
                try {
                    json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                    $url = 'https://quickchart.io/chart?c=' . urlencode($json) . '&width=600&height=350&backgroundColor=white';
                    $imageData = @file_get_contents($url);
                    if ($imageData) {
                        $base64 = base64_encode($imageData);
                        return '<div class="chart-img"><img src="data:image/png;base64,' . $base64 . '" style="max-width:100%;height:auto;display:block;margin:12px auto;border-radius:8px;" /></div>';
                    }
                } catch (\Throwable $e) {
                    // fallback below
                }
                return '<p><em>[Chart could not be rendered]</em></p>';
            },
            $content
        );

        $content = preg_replace_callback(
            '/```mermaid\s*([\s\S]*?)```/i',
            function ($matches) {
                $definition = trim($matches[1]);
                try {
                    $encoded = base64_encode($definition);
                    $url = 'https://mermaid.ink/img/' . $encoded . '?bgColor=white';
                    $imageData = @file_get_contents($url);
                    if ($imageData) {
                        $base64 = base64_encode($imageData);
                        return '<div class="mermaid-img"><img src="data:image/png;base64,' . $base64 . '" style="max-width:100%;height:auto;display:block;margin:12px auto;" /></div>';
                    }
                } catch (\Throwable $e) {
                    // fallback below
                }
                return '<p><em>[Diagram could not be rendered]</em></p>';
            },
            $content
        );

        $content = preg_replace_callback(
            '/```pollinations\s*([\s\S]*?)```/i',
            function ($matches) {
                $prompt = trim($matches[1]);
                if ($prompt === '') {
                    return '<p><em>[Image prompt was empty]</em></p>';
                }

                try {
                    $url = 'https://image.pollinations.ai/prompt/' . urlencode($prompt) . '?nologo=true&seed=' . rand(1, 999999);
                    $imageData = @file_get_contents($url);
                    if ($imageData) {
                        $base64 = base64_encode($imageData);
                        return '<div class="chart-img"><img src="data:image/png;base64,' . $base64 . '" style="max-width:100%;height:auto;display:block;margin:12px auto;border-radius:8px;" /></div>';
                    }
                } catch (\Throwable $e) {
                    // fallback below
                }

                return '<p><em>[Image could not be rendered]</em></p>';
            },
            $content
        );

        $converter = new \League\CommonMark\GithubFlavoredMarkdownConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        return $converter->convert($content)->getContent();
    }

    private function preprocessForExport(string $content): string
    {
        return $this->renderSociusMarkdownHtml($content);
    }

    private function cleanHtmlForDocx(string $html): string
    {
        // Remove 4-byte UTF-8 characters (emojis, etc.) that corrupt MS Word XML output
        $html = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $html);

        // Strip thead, tbody tags which are not supported by the HTML parser of PhpWord and cause it to fail
        $html = preg_replace('/<thead[^>]*>|<\/thead>|<tbody[^>]*>|<\/tbody>/i', '', $html);

        // Convert th tags to td with simple styling so PhpWord renders them as cells successfully
        $html = preg_replace('/<th([^>]*)>/i', '<td$1 style="font-weight: bold; background-color: #f1f5f9;">', $html);
        $html = str_ireplace('</th>', '</td>', $html);

        return $html;
    }

    private function exportToDocx(SurveyAiThread $thread, $messages, bool $isSingleMessage = false)
    {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();

        if ($isSingleMessage) {
            $message = $messages->first();
            $section->addTitle($thread->title ?: 'Socius Report', 1);
            $section->addText('Date: ' . now()->toDayDateTimeString());
            $section->addTextBreak(2);

            if ($message->role === 'assistant') {
                $html = $this->cleanHtmlForDocx($this->renderSociusMarkdownHtml($message->content));
                try {
                    \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html, false, true);
                } catch (\Throwable $e) {
                    Log::warning('Socius DOCX HTML render failed for single message.', ['message' => $e->getMessage()]);
                    $section->addText(strip_tags($message->content));
                }
            } else {
                $section->addText($message->content);
            }
        } else {
            $section->addTitle($thread->title ?: 'Socius Chat Export', 1);
            $section->addText('Date: ' . now()->toDayDateTimeString());
            $section->addTextBreak(2);

            foreach ($messages as $message) {
                $role = ucfirst($message->role);
                $section->addText($role . ' (' . $message->created_at->format('Y-m-d H:i') . ')', ['bold' => true]);

                $html = $message->role === 'assistant'
                    ? $this->renderSociusMarkdownHtml($message->content)
                    : '<p>' . nl2br(e($message->content)) . '</p>';

                $html = $this->cleanHtmlForDocx($html);

                try {
                    \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html, false, true);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Socius DOCX HTML render failed.', ['message' => $e->getMessage()]);
                    $section->addText(strip_tags($message->content));
                }

                $section->addTextBreak(1);
            }
        }

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $tempFile = tempnam(sys_get_temp_dir(), 'socius_docx');
        $objWriter->save($tempFile);

        $filename = $isSingleMessage
            ? 'socius-report-' . $messages->first()->id . '.docx'
            : Str::slug($thread->title ?: 'socius-chat') . '.docx';

        if (ob_get_length()) {
            ob_end_clean();
        }
        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    private function exportToExcel(SurveyAiThread $thread, $messages, bool $isSingleMessage = false)
    {
        $data = [
            ['Role', 'Timestamp', 'Message Content']
        ];

        foreach ($messages as $message) {
            $data[] = [
                ucfirst($message->role),
                $message->created_at->toDateTimeString(),
                $message->content
            ];
        }

        $filename = $isSingleMessage
            ? 'socius-report-' . $messages->first()->id . '.xlsx'
            : Str::slug($thread->title ?: 'socius-chat') . '.xlsx';

        if (ob_get_length()) {
            ob_end_clean();
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new class ($data) implements \Maatwebsite\Excel\Concerns\FromCollection {
            public function __construct(private array $data)
            {}
            public function collection()
            {
                return collect($this->data); }
            },
            $filename
        );
    }

    private function exportToMarkdown(SurveyAiThread $thread, $messages, bool $isSingleMessage = false)
    {
        if ($isSingleMessage) {
            $message = $messages->first();
            $md = $message->content;
            $filename = 'socius-report-' . $message->id . '.md';
        } else {
            $md = "# " . ($thread->title ?: 'Socius Chat Export') . "\n\n";
            $md .= "Date: " . now()->toDayDateTimeString() . "\n\n---\n\n";

            foreach ($messages as $message) {
                $role = $message->role === 'user' ? 'User' : 'Socius';
                $md .= "### " . $role . " (" . $message->created_at->format('Y-m-d H:i') . ")\n\n";
                $md .= $message->content . "\n\n---\n\n";
            }
            $filename = Str::slug($thread->title ?: 'socius-chat') . '.md';
        }

        return response($md)
            ->header('Content-Type', 'text/markdown')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function friendlyStreamingErrorMessage(\Throwable $e): string
    {
        $message = strtolower($e->getMessage());

        if (str_contains($message, 'rate limit') || str_contains($message, '429')) {
            return 'Socius is receiving too many requests right now. Please wait a moment and try again.';
        }

        if (str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
            return 'Socius took too long to respond. Please try again with a shorter request.';
        }

        if (str_contains($message, 'api key')) {
            return 'Socius is temporarily unavailable because the AI service is not configured correctly.';
        }

        return 'Socius ran into a problem while generating the reply. Please try again.';
    }

    public function generateImage(Request $request, Survey $survey)
    {
        try {
            $prompt = $request->input('prompt');
            if (!$prompt)
                return response()->json(['error' => 'No prompt provided'], 400);

            $apiKey = env('HUGGINGFACE_API_KEY');

            // 1. Try Hugging Face (Standard Inference URL)
            $modelUrl = 'https://api-inference.huggingface.co/models/prompthero/openjourney';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $modelUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['inputs' => $prompt, 'parameters' => ['wait_for_model' => true]]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Fail fast on HF to use fallback
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'Accept: image/jpeg',
                'User-Agent: Mozilla/5.0'
            ]);

            $result = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);
            // If Hugging Face works, return it
            if ($status === 200 && str_contains($contentType, 'image')) {
                return response($result)
                    ->header('Content-Type', 'image/jpeg')
                    ->header('X-AI-Source', 'HuggingFace (OpenJourney)');
            }

            // FALLBACK: Use Pollinations as a "Transparent Proxy"
            $fallbackUrl = "https://image.pollinations.ai/prompt/" . urlencode($prompt) . "?nologo=true&seed=" . rand(1, 999999);

            $ch2 = curl_init();
            curl_setopt($ch2, CURLOPT_URL, $fallbackUrl);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch2, CURLOPT_TIMEOUT, 120); // Give it plenty of time!
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch2, CURLOPT_USERAGENT, 'Mozilla/5.0');

            $fallbackResult = curl_exec($ch2);
            $fallbackStatus = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);

            if ($fallbackStatus === 200) {
                return response($fallbackResult)
                    ->header('Content-Type', 'image/jpeg')
                    ->header('X-AI-Source', 'Pollinations (Stable Diffusion XL)');
            }

            return response()->json([
                'error' => 'All AI services timed out',
                'tip' => 'AI is very busy right now. Please try again in a moment.'
            ], 500);

        } catch (\Throwable $e) {
            return response()->json(['error' => 'System Error', 'message' => $e->getMessage()], 500);
        }
    }

    public function humanize(Survey $survey, Request $request): JsonResponse
    {
        @set_time_limit(300);
        try {
            $this->authorizeSurvey($survey);
            $this->ensureAiEligible($request);

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
            Log::error('Humanizer Action Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'error' => 'Humanizer service error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
