<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\OrgSociusContext;
use App\Models\SociusKnowledgeBase;
use App\Models\SurveyAiThread;
use App\Models\SurveyAiMessage;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrgSociusController extends Controller
{
    public function __construct(
        protected AiService $aiService
    ) {
    }

    public function index(Request $request): View
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        $surveys = \App\Models\Survey::where('organization_id', $org->id)->get(['id', 'title']);

        $contexts = OrgSociusContext::where('organization_id', $org->id)
            ->with(['survey', 'creator'])
            ->latest('generated_at')
            ->get();

        $threads = SurveyAiThread::where('user_id', auth()->id())
            ->latest()
            ->get();

        $sharedKBs = SociusKnowledgeBase::where('organization_id', $org->id)
            ->where('is_org_shared', true)
            ->where('is_active', true)
            ->get();

        try {
            return view('organization.socius.index', compact('org', 'contexts', 'threads', 'sharedKBs', 'surveys'));
        } catch (\Throwable $e) {
            logger()->error('OrgSocius index view render error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            throw $e;
        }
    }

    public function createThread(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'prompt' => 'nullable|string|max:1000',
        ]);

        $title = $request->input('title');
        if (!$title && $request->filled('prompt')) {
            $title = \Illuminate\Support\Str::limit($request->prompt, 40, '...');
        }
        if (!$title) {
            $title = 'Org Socius Session - ' . date('M j, Y H:i');
        }

        $thread = SurveyAiThread::create([
            'user_id' => auth()->id(),
            'title' => $title,
        ]);

        return redirect()->route('organization.socius.threads.show', $thread->id);
    }

    public function showThread(Request $request, SurveyAiThread $thread): RedirectResponse
    {
        if ($thread->user_id !== auth()->id()) {
            abort(403, 'Unauthorized thread access.');
        }

        return redirect()->route('socius.chat.threads.show', $thread->id);
    }

    public function updateThread(Request $request, SurveyAiThread $thread): JsonResponse|RedirectResponse
    {
        if ($thread->user_id !== auth()->id()) {
            abort(403, 'Unauthorized thread update.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $thread->update(['title' => $request->title]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'thread' => $thread]);
        }

        return redirect()->back()->with('success', __('Thread title updated.'));
    }

    public function destroyThread(Request $request, SurveyAiThread $thread): JsonResponse|RedirectResponse
    {
        if ($thread->user_id !== auth()->id()) {
            abort(403, 'Unauthorized thread deletion.');
        }

        $thread->messages()->delete();
        $thread->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('organization.socius.index')->with('success', __('Thread deleted.'));
    }

    public function storeKnowledgeBase(Request $request): RedirectResponse
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'document' => 'nullable|file|mimes:pdf,txt,docx,doc|max:5000',
        ]);

        $content = $request->content;

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $extractedText = '';
            if ($file->getClientOriginalExtension() === 'txt') {
                $extractedText = file_get_contents($file->getRealPath());
            }
            if (!empty($extractedText)) {
                $content .= "\n\n[Document Content: {$file->getClientOriginalName()}]\n" . $extractedText;
            }
        }

        SociusKnowledgeBase::create([
            'user_id' => auth()->id(),
            'organization_id' => $org->id,
            'title' => $request->title,
            'content' => $content,
            'is_org_shared' => true,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', __('Reference document added to Shared Knowledge Base.'));
    }

    public function streamOrgContext(Request $request, SurveyAiThread $thread): StreamedResponse
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        if ($thread->user_id !== auth()->id()) {
            abort(403, 'Unauthorized thread access.');
        }

        $userMessage = $request->input('message');
        if (empty($userMessage)) {
            abort(422, 'Message cannot be empty.');
        }

        // Auto-update thread title if still generic
        if (str_contains($thread->title, 'Org Socius Session') || str_contains($thread->title, 'Org Workspace Socius Thread')) {
            $thread->update(['title' => \Illuminate\Support\Str::limit($userMessage, 40, '...')]);
        }

        $selectedSurveyIds = $request->input('survey_ids', []);
        if (is_string($selectedSurveyIds)) {
            $selectedSurveyIds = array_filter(explode(',', $selectedSurveyIds));
        }

        $contextQuery = OrgSociusContext::where('organization_id', $org->id)
            ->where('context_type', 'survey_summary');

        if (!empty($selectedSurveyIds)) {
            $contextQuery->whereIn('survey_id', $selectedSurveyIds);
        }

        $surveySummaries = $contextQuery->latest('generated_at')
            ->limit(10)
            ->pluck('content')
            ->join("\n\n---\n\n");

        $orgKB = SociusKnowledgeBase::where('organization_id', $org->id)
            ->where('is_org_shared', true)
            ->where('is_active', true)
            ->pluck('content')
            ->join("\n\n");

        $systemPrompt = implode("\n\n", array_filter([
            "You are Socius, an AI research assistant embedded within the '{$org->name}' research workspace.",
            "You have access to this organization's full research history and institutional knowledge base.",
            $surveySummaries ? "PAST STUDIES SUMMARY & INSTITUTIONAL MEMORY:\n{$surveySummaries}" : null,
            $orgKB ? "ORGANIZATIONAL KNOWLEDGE BASE:\n{$orgKB}" : null,
            "Use this institutional context when answering questions. Be specific about which study or past finding you are referencing.",
            "When comparing studies, clearly identify differences in methodology, sample size, findings, and strategic recommendations.",
            "Format your output cleanly using clear markdown headers, bold text, bullet points, and markdown tables where relevant.",
        ]));

        SurveyAiMessage::create([
            'thread_id' => $thread->id,
            'role' => 'user',
            'content' => $userMessage,
        ]);

        return response()->stream(function () use ($userMessage, $thread, $systemPrompt) {
            try {
                $reply = $this->aiService->quickComplete($userMessage, $systemPrompt);
            } catch (\Exception $e) {
                $reply = "I encountered an error retrieving institutional research context: " . $e->getMessage();
            }

            SurveyAiMessage::create([
                'thread_id' => $thread->id,
                'role' => 'assistant',
                'content' => $reply,
            ]);

            echo "data: " . json_encode(['content' => $reply, 'done' => true]) . "\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
