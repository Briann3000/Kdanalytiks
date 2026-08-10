@extends('layouts.app')

@section('title', $thread->title . ' - Org Socius AI')

@push('styles')
    <style>
        /* Hide floating agent FAB inside Org Socius thread view */
        #kda-btn,
        #kda-box,
        #chatbot-widget,
        .kda-agent-fab {
            display: none !important;
        }

        .socius-root-container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 64px);
            width: 100%;
            background-color: #1e1e1e !important;
            overflow: hidden;
            color: #f1f5f9;
        }

        .socius-prose {
            color: #e2e8f0;
            font-size: 0.875rem;
            line-height: 1.625;
        }

        .socius-prose p {
            margin-bottom: 0.75rem;
        }

        .socius-prose p:last-child {
            margin-bottom: 0;
        }

        .socius-prose ul {
            list-style-type: disc;
            padding-left: 1.25rem;
            margin-bottom: 0.75rem;
        }

        .socius-prose ol {
            list-style-type: decimal;
            padding-left: 1.25rem;
            margin-bottom: 0.75rem;
        }

        .socius-prose li {
            margin-bottom: 0.25rem;
        }

        .socius-prose strong,
        .socius-prose b {
            font-weight: 700;
            color: #ffffff;
        }

        .socius-prose code {
            background: #0f172a;
            color: #38bdf8;
            padding: 0.15rem 0.35rem;
            border-radius: 0.375rem;
            font-size: 0.8em;
        }

        .socius-prose table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            font-size: 0.8rem;
        }

        .socius-prose th {
            background: #0f172a;
            color: #f8fafc;
            font-weight: 700;
            padding: 0.5rem 0.75rem;
            text-align: left;
            border: 1px solid #334155;
        }

        .socius-prose td {
            padding: 0.5rem 0.75rem;
            border: 1px solid #334155;
        }
    </style>
    <!-- Include marked.js for Markdown parsing -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
@endpush

@section('content')
    <div class="socius-root-container" x-data="orgSociusChat()">
        <!-- Header Bar -->
        <header
            class="h-14 bg-slate-900 border-b border-slate-800 px-6 flex items-center justify-between shrink-0 shadow-md z-10">
            <div class="flex items-center gap-3">
                <a href="{{ route('organization.socius.index') }}"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition"
                    title="{{ __('Back to Intelligence Hub') }}">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </a>
                <div class="flex items-center gap-2">
                    <div
                        class="w-7 h-7 rounded-lg bg-[#2271b1] text-white flex items-center justify-center font-black text-xs shadow-sm">
                        <i class="fa-solid fa-brain"></i>
                    </div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-sm font-bold text-white leading-none truncate max-w-xs">{{ $thread->title }}</h1>
                        <button type="button" @click="promptRename()" class="text-slate-500 hover:text-slate-300 text-xs"
                            title="{{ __('Rename Thread') }}">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <!-- Study Filter Pills -->
                <div
                    class="hidden md:flex items-center gap-1.5 bg-slate-950 p-1 rounded-xl border border-slate-800 text-[11px]">
                    <button type="button" @click="selectedSurveyIds = []"
                        class="px-2.5 py-1 rounded-lg font-bold transition"
                        :class="selectedSurveyIds.length === 0 ? 'bg-[#2271b1] text-white' : 'text-slate-400 hover:text-white'">
                        {{ __('All Studies') }} ({{ $surveys->count() }})
                    </button>
                    <button type="button" @click="showMemoryDrawer = true"
                        class="px-2.5 py-1 rounded-lg font-bold transition text-slate-400 hover:text-white flex items-center gap-1">
                        <i class="fa-solid fa-filter text-[10px]"></i>
                        <span
                            x-text="selectedSurveyIds.length > 0 ? `${selectedSurveyIds.length} Selected` : 'Filter Studies'"></span>
                    </button>
                </div>

                <button type="button" @click="showMemoryDrawer = !showMemoryDrawer"
                    class="px-3 py-1.5 rounded-xl border border-slate-800 bg-slate-950 hover:bg-slate-800 text-slate-300 text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-layer-group text-[#72aee6]"></i>
                    <span>{{ __('Study Memory') }} ({{ $contexts->count() }})</span>
                </button>
            </div>
        </header>

        <div class="flex-1 flex overflow-hidden relative">
            <!-- Main Chat Area -->
            <main class="flex-1 flex flex-col h-full overflow-hidden bg-[#1e1e1e]">
                <!-- Messages Container -->
                <div class="flex-1 overflow-y-auto px-4 py-6 md:px-12 custom-scrollbar space-y-6"
                    id="org-socius-scroll-area">
                    <!-- Welcome Prompt Banner -->
                    @if($thread->messages->isEmpty())
                        <div class="max-w-2xl mx-auto text-center py-10 space-y-4">
                            <div
                                class="w-12 h-12 rounded-2xl bg-indigo-950 text-indigo-400 mx-auto flex items-center justify-center text-xl shadow-md border border-indigo-800">
                                <i class="fa-solid fa-comments"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-white">
                                    {{ __('Ask Org Socius Anything Across Your Workspace') }}</h2>
                                <p class="text-xs text-slate-400 max-w-md mx-auto mt-1">
                                    {{ __('Org Socius synthesizes insights across past research studies, response metrics, and analyst findings stored in :org.', ['org' => $org->name]) }}
                                </p>
                            </div>

                            <!-- Prompt Suggestion Chips -->
                            <div class="flex flex-wrap items-center justify-center gap-2 pt-2">
                                <button type="button"
                                    @click="usePrompt('What are the main methodology differences and findings across our recent studies?')"
                                    class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 hover:border-[#2271b1] text-slate-300 text-xs font-medium shadow-sm transition">
                                    🔍 {{ __('Compare findings across recent studies') }}
                                </button>
                                <button type="button"
                                    @click="usePrompt('Summarize key customer feedback and pain points from all surveys.')"
                                    class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 hover:border-[#2271b1] text-slate-300 text-xs font-medium shadow-sm transition">
                                    📊 {{ __('Summarize key customer pain points') }}
                                </button>
                                <button type="button"
                                    @click="usePrompt('What strategic recommendations can we draw from our research data?')"
                                    class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 hover:border-[#2271b1] text-slate-300 text-xs font-medium shadow-sm transition">
                                    💡 {{ __('Synthesize strategic recommendations') }}
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- Saved Messages -->
                    @foreach($thread->messages as $msg)
                        <div class="flex {{ $msg->role === 'user' ? 'justify-end' : 'justify-start' }}">
                            <div
                                class="max-w-3xl rounded-2xl p-4 shadow-md {{ $msg->role === 'user' ? 'bg-[#2271b1] text-white rounded-br-none' : 'bg-slate-900 border border-slate-800 text-slate-100 rounded-bl-none' }}">
                                <div class="flex items-center justify-between gap-2 mb-2 pb-1 border-b border-white/10">
                                    <span
                                        class="text-[10px] font-extrabold uppercase tracking-wider {{ $msg->role === 'user' ? 'text-indigo-100' : 'text-[#72aee6]' }}">
                                        {{ $msg->role === 'user' ? __('You') : __('Org Socius AI') }}
                                    </span>

                                    @if($msg->role === 'assistant')
                                        <!-- Message Action Controls: Copy & Retry -->
                                        <div class="flex items-center gap-2">
                                            <button type="button" @click="copyText('{{ addslashes($msg->content) }}')"
                                                class="text-[10px] text-slate-400 hover:text-white transition flex items-center gap-1">
                                                <i class="fa-solid fa-copy"></i>
                                                <span>{{ __('Copy') }}</span>
                                            </button>
                                            <button type="button"
                                                @click="retryMessage('{{ addslashes($thread->messages->where('role', 'user')->last()?->content ?? '') }}')"
                                                class="text-[10px] text-slate-400 hover:text-white transition flex items-center gap-1">
                                                <i class="fa-solid fa-rotate"></i>
                                                <span>{{ __('Retry') }}</span>
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                <div class="socius-prose" x-html="renderMarkdown('{{ addslashes($msg->content) }}')"></div>
                            </div>
                        </div>
                    @endforeach

                    <!-- Streaming active reply bubble -->
                    <div x-show="isStreaming" class="flex justify-start" id="org-socius-streaming-bubble">
                        <div
                            class="max-w-3xl rounded-2xl p-4 bg-slate-900 border border-slate-800 text-slate-100 rounded-bl-none shadow-lg space-y-2">
                            <div class="flex items-center gap-2 text-xs font-bold text-[#72aee6]">
                                <i class="fa-solid fa-circle-notch fa-spin"></i>
                                <span>{{ __('Synthesizing Institutional Memory...') }}</span>
                            </div>
                            <div class="socius-prose"
                                x-html="renderMarkdown(streamBuffer || 'Processing query across workspace studies...')">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sticky Prompt Bar -->
                <div class="p-4 bg-slate-900 border-t border-slate-800 shrink-0">
                    <div class="max-w-3xl mx-auto">
                        <form @submit.prevent="sendMessage()"
                            class="flex items-end gap-2 bg-slate-950 rounded-2xl p-2 border border-slate-800 focus-within:border-[#2271b1] focus-within:ring-2 focus-within:ring-indigo-900/40 transition">
                            <textarea x-model="newMessage" @keydown.enter.prevent="if(!$event.shiftKey) sendMessage()"
                                :disabled="isStreaming" rows="1"
                                placeholder="{{ __('Ask a question across :org\'s research memory...', ['org' => $org->name]) }}"
                                class="flex-1 bg-transparent border-0 resize-none px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:ring-0 focus:outline-none custom-scrollbar max-h-32"></textarea>
                            <button type="submit" :disabled="isStreaming || !newMessage.trim()"
                                class="px-4 py-2.5 bg-[#2271b1] hover:bg-[#135e96] disabled:opacity-40 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center gap-1.5 shrink-0">
                                <span>{{ __('Ask Socius') }}</span>
                                <i class="fa-solid fa-paper-plane text-[10px]"></i>
                            </button>
                        </form>
                        <p class="text-[10px] text-slate-500 text-center mt-2 font-medium">
                            {{ __('Press Enter to send, Shift + Enter for new line. AI synthesizes memory across :org.', ['org' => $org->name]) }}
                        </p>
                    </div>
                </div>
            </main>

            <!-- Right Side Memory Drawer & Study Selector -->
            <aside x-show="showMemoryDrawer" x-cloak x-transition
                class="w-80 bg-slate-900 border-l border-slate-800 p-5 overflow-y-auto shrink-0 shadow-2xl z-20 flex flex-col space-y-6">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-xs font-extrabold text-white uppercase tracking-wider">
                        {{ __('Targeted Survey Filter') }}</h3>
                    <button type="button" @click="showMemoryDrawer = false" class="text-slate-400 hover:text-white text-sm">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- Survey Filter Selection List -->
                <div class="space-y-3">
                    <h4 class="text-[11px] font-extrabold text-[#72aee6] uppercase tracking-wider">
                        {{ __('Select Surveys to Synthesize') }}</h4>
                    <div
                        class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar p-2 bg-slate-950 rounded-xl border border-slate-800">
                        <label
                            class="flex items-center gap-2 text-xs text-slate-200 cursor-pointer p-1.5 hover:bg-slate-900 rounded-lg">
                            <input type="checkbox" :checked="selectedSurveyIds.length === 0"
                                @change="selectedSurveyIds = []"
                                class="rounded border-slate-700 bg-slate-900 text-[#2271b1]">
                            <span class="font-bold">{{ __('All Workspace Surveys') }}</span>
                        </label>

                        @foreach($surveys as $s)
                            <label
                                class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer p-1.5 hover:bg-slate-900 rounded-lg">
                                <input type="checkbox" value="{{ $s->id }}" x-model="selectedSurveyIds"
                                    class="rounded border-slate-700 bg-slate-900 text-[#2271b1]">
                                <span class="truncate">{{ $s->title }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-4">
                    <h4 class="text-[11px] font-extrabold text-[#72aee6] uppercase tracking-wider">
                        {{ __('Institutional Memory Cards (:count)', ['count' => $contexts->count()]) }}</h4>
                    @forelse($contexts as $ctx)
                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs font-bold text-white line-clamp-1">{{ $ctx->survey->title ?? __('Institutional Study') }}</span>
                            </div>
                            <p class="text-[11px] text-slate-400 line-clamp-3 leading-relaxed font-mono">{{ $ctx->content }}</p>
                            <span
                                class="text-[9px] text-slate-500 block font-semibold">{{ $ctx->generated_at ? $ctx->generated_at->format('M j, Y') : __('Recent') }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 italic">{{ __('No study summaries loaded yet.') }}</p>
                    @endforelse
                </div>
            </aside>
        </div>
    </div>

    <script>
        function orgSociusChat() {
            return {
                newMessage: '{{ request('initial_prompt') ? addslashes(request('initial_prompt')) : '' }}',
                isStreaming: false,
                streamBuffer: '',
                showMemoryDrawer: false,
                selectedSurveyIds: [],
                init() {
                    if (this.newMessage.trim()) {
                        this.$nextTick(() => { this.sendMessage(); });
                    }
                },
                usePrompt(text) {
                    this.newMessage = text;
                },
                renderMarkdown(text) {
                    if (!text) return '';
                    if (typeof marked !== 'undefined') {
                        return marked.parse(text);
                    }
                    return text;
                },
                scrollToBottom() {
                    this.$nextTick(() => {
                        const area = document.getElementById('org-socius-scroll-area');
                        if (area) area.scrollTop = area.scrollHeight;
                    });
                },
                copyText(text) {
                    navigator.clipboard.writeText(text);
                    alert('{{ __('Response copied to clipboard!') }}');
                },
                retryMessage(lastPrompt) {
                    if (!lastPrompt || this.isStreaming) return;
                    this.newMessage = lastPrompt;
                    this.sendMessage();
                },
                promptRename() {
                    const newTitle = prompt('{{ __('Enter new thread title:') }}', '{{ addslashes($thread->title) }}');
                    if (newTitle && newTitle.trim() && newTitle !== '{{ addslashes($thread->title) }}') {
                        fetch('{{ route('organization.socius.threads.update', $thread->id) }}', {
                            method: 'PATCH',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ title: newTitle })
                        }).then(res => { if (res.ok) window.location.reload(); });
                    }
                },
                sendMessage() {
                    if (!this.newMessage.trim() || this.isStreaming) return;
                    const message = this.newMessage.trim();
                    this.newMessage = '';
                    this.isStreaming = true;
                    this.streamBuffer = '';
                    this.scrollToBottom();

                    const params = new URLSearchParams({ message: message });
                    if (this.selectedSurveyIds.length > 0) {
                        params.append('survey_ids', this.selectedSurveyIds.join(','));
                    }

                    const streamUrl = "{{ route('organization.socius.threads.stream', $thread->id) }}?" + params.toString();

                    fetch(streamUrl)
                        .then(response => {
                            if (!response.ok) throw new Error('Stream failed');
                            return response.text();
                        })
                        .then(text => {
                            const lines = text.split('\n\n');
                            lines.forEach(line => {
                                if (line.startsWith('data: ')) {
                                    try {
                                        const json = JSON.parse(line.replace('data: ', ''));
                                        if (json.content) {
                                            this.streamBuffer = json.content;
                                            this.scrollToBottom();
                                        }
                                    } catch (e) { }
                                }
                            });
                        })
                        .catch(err => {
                            this.streamBuffer = "Error connecting to Org Socius AI service.";
                        })
                        .finally(() => {
                            this.isStreaming = false;
                            window.location.reload();
                        });
                }
            }
        }
    </script>
@endsection