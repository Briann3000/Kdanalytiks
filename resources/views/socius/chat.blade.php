@extends('layouts.app')

@section('title', __('Socius AI'))

@push('styles')
    <style>
        /* Hide floating KDA agent button on Socius page */
        #kda-btn,
        #kda-box,
        #chatbot-widget,
        .kda-agent-fab {
            display: none !important;
        }

        .socius-root-container {
            overscroll-behavior-y: none;
            overscroll-behavior-x: none;
            overflow-x: hidden;
            background-color: #1e1e1e !important;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 999px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        .socius-prose p {
            margin-bottom: 1rem;
        }

        .socius-prose h2 {
            font-size: 1.25rem;
            font-weight: 800;
            margin: 1.5rem 0 0.75rem;
            color: #f1f5f9;
        }

        .socius-prose h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 1.25rem 0 0.5rem;
            color: #e2e8f0;
        }

        .socius-prose h4 {
            font-size: 1rem;
            font-weight: 700;
            margin: 1rem 0 0.5rem;
            color: #e2e8f0;
        }

        .socius-prose ul {
            list-style: disc;
            padding-left: 1.5rem;
            margin-bottom: 1rem;
        }

        .socius-prose ol {
            list-style: decimal;
            padding-left: 1.5rem;
            margin-bottom: 1rem;
        }

        .socius-prose li {
            margin-bottom: 0.25rem;
        }

        .socius-prose strong,
        .socius-prose b {
            font-weight: 700;
        }

        .socius-prose em,
        .socius-prose i {
            font-style: italic;
        }

        .socius-prose table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            font-size: 0.85rem;
        }

        .socius-prose th,
        .socius-prose td {
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.5rem 0.75rem;
            text-align: left;
        }

        .socius-prose th {
            background: rgba(255, 255, 255, 0.05);
            font-weight: 700;
        }

        .socius-prose blockquote {
            border-left: 3px solid #3894dc;
            padding: 0.5rem 1rem;
            margin: 1rem 0;
            color: #94a3b8;
            background: rgba(56, 148, 220, 0.05);
            border-radius: 0 0.5rem 0.5rem 0;
        }

        .socius-prose code {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 0.5rem;
            padding: 0.125rem 0.375rem;
            font-size: 0.875em;
        }

        .socius-prose pre {
            white-space: pre-wrap;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            padding: 1rem;
            overflow-x: auto;
            margin: 0 0 1rem;
        }

        .socius-visual .visual-target svg {
            max-width: 100%;
            height: auto !important;
        }
    </style>
@endpush

@section('content')
    <div x-data="standaloneSociusManager({
                                                        canAnalyze: @js($canAnalyze),
                                                        initialContext: @js($initialContext),
                                                        urls: @js($urls)
                                                    })" x-init="init()"
        class="socius-root-container animate-in fade-in duration-500">

        <div class="relative flex gap-4 w-full bg-[#1e1e1e]" style="height: calc(100dvh - 4.1rem); overflow: hidden;">

            {{-- Sidebar History --}}
            <aside :class="historyOpen ? 'w-64 md:w-72 opacity-100 z-40' : 'w-0 opacity-0 -ml-4 pointer-events-none'"
                class="bg-[#2b2b2b] text-white border border-white/5 overflow-hidden flex flex-col h-full transition-all duration-300 ease-in-out flex-shrink-0 absolute xl:relative inset-y-0 left-0">
                <div class="px-4 py-3 border-b border-white/10 flex items-center justify-between gap-2">
                    <p class="text-[10px] font-bold text-slate-400">{{ __('Conversation History') }}</p>
                    <button @click="createThread()" :disabled="creatingThread || !canAnalyze"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-[#2271b1] text-white text-[9px] font-bold disabled:opacity-40 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-plus text-[9px]" :class="{ 'fa-spin': creatingThread }"></i>
                        {{ __('New') }}
                    </button>
                    <button @click="historyOpen = false"
                        class="p-2 rounded-xl bg-white/5 border border-white/10 text-slate-400 hover:text-white transition-all xl:hidden">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="p-4 space-y-3 flex-1 overflow-y-auto custom-scrollbar" style="overscroll-behavior-y: contain;">
                    <template x-if="loadingThreads">
                        <div class="space-y-3">
                            <div class="h-20 rounded-3xl bg-white/5 animate-pulse"></div>
                            <div class="h-20 rounded-3xl bg-white/5 animate-pulse"></div>
                        </div>
                    </template>

                    <template x-if="!loadingThreads && threads.length === 0">
                        <div class="rounded-3xl border border-dashed border-white/15 bg-white/[0.03] p-6 text-center">
                            <div
                                class="w-12 h-12 rounded-2xl bg-white/10 mx-auto mb-4 flex items-center justify-center text-[#3894dc]">
                                <i class="fa-solid fa-comment-dots"></i>
                            </div>
                            <p class="text-sm font-semibold">{{ __('No chats yet') }}</p>
                            <p class="text-xs text-slate-400 mt-2">{{ __('Start a new conversation with Socius.') }}</p>
                        </div>
                    </template>

                    <template x-for="thread in threads" :key="thread.id">
                        <div class="relative group/thread"
                            @click.outside="threadMenuOpen === thread.id && (threadMenuOpen = null)">

                            <template x-if="renamingThreadId === thread.id">
                                <div class="rounded-2xl bg-white/[0.06] border border-white/15 px-3 py-2">
                                    <input type="text" x-model="editingTitle"
                                        @keydown.enter="renameThread(thread.id, editingTitle)"
                                        @keydown.escape="renamingThreadId = null" @click.stop
                                        class="w-full rounded-lg bg-white/10 border border-white/20 px-2 py-1.5 text-xs text-white focus:outline-none focus:border-[#2271b1]"
                                        x-init="$nextTick(() => $el.focus())">
                                    <div class="flex gap-1.5 mt-2">
                                        <button @click.stop="renameThread(thread.id, editingTitle)"
                                            class="flex-1 text-[9px] px-2 py-1 rounded-lg bg-[#2271b1] text-white font-bold">{{ __('Save') }}</button>
                                        <button @click.stop="renamingThreadId = null"
                                            class="flex-1 text-[9px] px-2 py-1 rounded-lg bg-white/10 text-white">{{ __('Cancel') }}</button>
                                    </div>
                                </div>
                            </template>

                            <template x-if="renamingThreadId !== thread.id">
                                <div class="relative">
                                    <button @click="selectThread(thread.id)"
                                        class="w-full text-left rounded-2xl px-3 py-3 pr-9 border transition-all"
                                        :class="currentThreadId === thread.id
                                                                                        ? 'bg-white text-slate-900 border-white shadow-xl shadow-black/20'
                                                                                        : 'bg-white/[0.04] border-white/10 hover:bg-white/[0.08] text-white'">
                                        <div class="flex items-center gap-1.5 overflow-hidden">
                                            <template x-if="thread.is_pinned">
                                                <i
                                                    class="fa-solid fa-thumbtack text-[9px] text-[#3894dc] rotate-45 flex-shrink-0"></i>
                                            </template>
                                            <p class="text-xs font-semibold truncate leading-snug" x-text="thread.title">
                                            </p>
                                        </div>
                                        <p class="mt-1 text-[10px] opacity-60 line-clamp-1"
                                            x-text="thread.latest_message_preview || '{{ __('Fresh thread') }}'"></p>
                                        <p class="mt-1.5 text-[9px] font-bold opacity-40"
                                            x-text="formatRelativeTime(thread.last_activity_at)"></p>
                                    </button>

                                    <button @click.stop="threadMenuOpen = (threadMenuOpen === thread.id ? null : thread.id)"
                                        class="absolute right-2 top-3 w-6 h-6 rounded-lg flex items-center justify-center transition-all opacity-0 group-hover/thread:opacity-100 focus:opacity-100"
                                        :class="[
                                                                                        threadMenuOpen === thread.id ? 'opacity-100' : '',
                                                                                        currentThreadId === thread.id ? 'hover:bg-slate-200 text-slate-600' : 'hover:bg-white/15 text-slate-400'
                                                                                    ]">
                                        <i class="fa-solid fa-ellipsis-vertical text-[11px]"></i>
                                    </button>

                                    <div x-show="threadMenuOpen === thread.id"
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95"
                                        class="absolute right-0 top-9 z-50 w-36 bg-[#3a3a3a] border border-white/10 rounded-xl shadow-2xl overflow-hidden"
                                        style="display:none;">
                                        <button @click.stop="togglePin(thread.id); threadMenuOpen = null"
                                            class="flex items-center gap-2.5 w-full px-3 py-2.5 text-xs font-semibold text-slate-200 hover:bg-white/10 transition-colors">
                                            <i class="fa-solid fa-thumbtack text-[10px] text-[#3894dc] w-3"
                                                :class="thread.is_pinned ? 'text-[#3894dc]' : ''"></i>
                                            <span
                                                x-text="thread.is_pinned ? '{{ __('Unpin') }}' : '{{ __('Pin') }}'"></span>
                                        </button>
                                        <button
                                            @click.stop="renamingThreadId = thread.id; editingTitle = thread.title; threadMenuOpen = null"
                                            class="flex items-center gap-2.5 w-full px-3 py-2.5 text-xs font-semibold text-slate-200 hover:bg-white/10 transition-colors border-t border-white/5">
                                            <i class="fa-solid fa-pencil text-[10px] text-slate-400 w-3"></i>
                                            {{ __('Rename') }}
                                        </button>
                                        <button @click.stop="deleteThread(thread.id); threadMenuOpen = null"
                                            class="flex items-center gap-2.5 w-full px-3 py-2.5 text-xs font-semibold text-red-400 hover:bg-red-500/15 transition-colors border-t border-white/5">
                                            <i class="fa-solid fa-trash text-[10px] w-3"></i>
                                            {{ __('Delete') }}
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </aside>

            {{-- Main Chat Area --}}
            <section class="w-full h-full bg-[#1e1e1e] flex flex-col justify-between overflow-hidden relative">

                {{-- Mobile Backdrop --}}
                <div x-show="historyOpen" x-cloak @click="historyOpen = false"
                    class="fixed inset-0 bg-black/60 backdrop-blur-sm z-30 xl:hidden"></div>

                {{-- Toggle Button when Sidebar is hidden --}}
                <template x-if="!historyOpen">
                    <div class="absolute left-0 top-1/2 -translate-y-1/2 z-50">
                        <button @click="historyOpen = true"
                            class="p-2 rounded-r-xl bg-[#2b2b2b] border border-l-0 border-white/10 text-slate-400 hover:text-white transition-all shadow-xl">
                            <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </button>
                    </div>
                </template>

                {{-- Floating Prompt Timeline Navigation --}}
                <div class="absolute right-4 top-1/2 -translate-y-1/2 z-40 flex items-center gap-3"
                    x-data="{ hovered: false }" @mouseenter="hovered = true" @mouseleave="hovered = false"
                    x-show="currentThreadId && messages.filter(m => m.role === 'user').length > 0" style="display: none;">
                    <div x-show="hovered" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-x-2"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        class="w-64 max-h-[300px] overflow-y-auto custom-scrollbar bg-[#202020]/95 backdrop-blur-md rounded-2xl border border-white/10 p-3 shadow-2xl flex flex-col gap-1 text-slate-300"
                        style="display: none;">
                        <template x-for="(userMsg, idx) in messages.filter(m => m.role === 'user')" :key="userMsg.id">
                            <button @click="scrollToPrompt(userMsg.id)"
                                class="w-full text-left px-3 py-1.5 rounded-xl text-xs transition-all truncate"
                                :class="activePromptId === userMsg.id ? 'bg-white/10 text-[#3894dc] font-bold' : 'hover:bg-white/5 text-slate-300'"
                                x-text="userMsg.content"></button>
                        </template>
                    </div>
                    <div
                        class="flex flex-col gap-2 p-2 bg-black/40 backdrop-blur-md rounded-2xl border border-white/5 shadow-2xl max-h-[80vh] overflow-y-auto custom-scrollbar">
                        <template x-for="(userMsg, idx) in messages.filter(m => m.role === 'user')" :key="userMsg.id">
                            <button @click="scrollToPrompt(userMsg.id)"
                                class="w-5 h-1.5 rounded-full transition-all duration-200"
                                :class="activePromptId === userMsg.id ? 'bg-[#2271b1] w-8' : 'bg-white/20 hover:bg-white/60'"></button>
                        </template>
                    </div>
                </div>

                {{-- Top Bar --}}
                <div
                    class="px-3 sm:px-5 py-2 border-b border-white/10 flex items-center justify-between gap-2 sm:gap-4 shrink-0">
                    <div class="flex items-center gap-1.5 sm:gap-3 min-w-0 flex-1">
                        <button @click="historyOpen = !historyOpen"
                            class="p-2 rounded-xl bg-white/5 border border-white/10 text-slate-400 hover:text-white transition-all shrink-0"
                            title="{{ __('Toggle History') }}">
                            <i class="fa-solid fa-bars-staggered text-xs"></i>
                        </button>
                        <div class="flex items-center gap-2 min-w-0">
                            <div
                                class="w-7 h-7 rounded-xl bg-[#2271b1]/20 flex-shrink-0 flex items-center justify-center text-[#3894dc]">
                                <i class="fa-solid fa-comment-dots text-xs"></i>
                            </div>
                            <h3 class="text-xs sm:text-sm text-white font-semibold tracking-tight truncate max-w-[100px] xs:max-w-[140px] sm:max-w-[220px] md:max-w-md"
                                x-text="currentThread ? currentThread.title : '{{ __('Socius AI') }}'"></h3>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                        {{-- Knowledge Base Button --}}
                        <button @click="kbModalOpen = true"
                            class="inline-flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-1.5 rounded-xl bg-white/10 border border-white/10 text-xs font-bold text-slate-300 hover:text-white transition-all shrink-0">
                            <i class="fa-solid fa-brain text-[10px]"></i>
                            <span class="hidden sm:inline">{{ __('Knowledge Base') }}</span>
                        </button>

                        {{-- Export Dropdown --}}
                        <template x-if="currentThreadId">
                            <div class="relative shrink-0" x-data="{ exportMenuOpen: false }"
                                @click.away="exportMenuOpen = false">
                                <button @click="exportMenuOpen = !exportMenuOpen"
                                    class="inline-flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-1.5 rounded-xl bg-white/10 border border-white/10 text-xs font-bold text-slate-300 hover:text-white transition-all">
                                    <i class="fa-solid fa-download text-[10px]"></i>
                                    <span class="hidden xs:inline">{{ __('Export') }}</span>
                                    <i class="fa-solid fa-chevron-down text-[10px] opacity-50"></i>
                                </button>
                                <div x-show="exportMenuOpen" x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    class="absolute right-0 mt-2 w-48 rounded-2xl bg-[#363636] border border-white/10 shadow-2xl z-50 overflow-hidden py-1">
                                    <a :href="urls.exportTemplate.replace('__THREAD__', currentThreadId) + '?format=pdf'"
                                        class="flex items-center gap-3 px-4 py-2.5 text-xs text-slate-300 hover:bg-white/10 hover:text-white transition-colors">
                                        <i class="fa-solid fa-file-pdf text-red-400"></i> {{ __('PDF Report') }}
                                    </a>
                                    <a :href="urls.exportTemplate.replace('__THREAD__', currentThreadId) + '?format=docx'"
                                        class="flex items-center gap-3 px-4 py-2.5 text-xs text-slate-300 hover:bg-white/10 hover:text-white transition-colors">
                                        <i class="fa-solid fa-file-word text-blue-400"></i> {{ __('Word Document') }}
                                    </a>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Message List --}}
                <div class="flex-1 overflow-y-auto custom-scrollbar px-4 md:px-8 py-6 space-y-6" x-ref="messageList"
                    @scroll="handleScroll()" style="overscroll-behavior-y: contain;">

                    <template x-if="!canAnalyze">
                        <div
                            class="max-w-2xl mx-auto rounded-[2rem] border border-amber-400/20 bg-amber-400/10 p-8 text-center">
                            <div
                                class="w-14 h-14 rounded-2xl bg-amber-300/20 text-amber-200 mx-auto mb-4 flex items-center justify-center">
                                <i class="fa-solid fa-lock"></i>
                            </div>
                            <h4 class="text-xl font-black">{{ __('Socius is currently locked for this account') }}</h4>
                            <p class="text-sm text-amber-100/80 mt-3">
                                {{ __('Your current AI allocation has been reached. Upgrade your plan or wait for access to reset before continuing.') }}
                            </p>
                            <a href="{{ route('subscriptions.index') }}"
                                class="inline-flex items-center gap-2 mt-6 px-5 py-3 rounded-2xl bg-white text-slate-900 text-[10px] font-bold">
                                {{ __('View Plans') }} <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </template>

                    <template x-if="canAnalyze && !currentThreadId && !loadingThreads">
                        <div class="max-w-3xl mx-auto pt-10">
                            <div class="text-center mb-8">
                                <div
                                    class="w-20 h-20 rounded-3xl bg-[#2271b1]/10 border border-[#2271b1]/20 mx-auto flex items-center justify-center mb-6">
                                    <i class="fa-solid fa-comment-dots text-3xl text-[#3894dc]"></i>
                                </div>
                                <h4 class="text-4xl font-semibold text-white tracking-tight">{{ __('Socius') }}</h4>
                                <p class="text-slate-300 mt-4 max-w-2xl mx-auto">
                                    {{ __('I am Socius, your guide. Develop your literature and run analyses.') }}
                                </p>

                            </div>
                        </div>
                    </template>

                    <template x-if="currentThreadId && messages.length === 0 && !loadingMessages">
                        <div
                            class="max-w-2xl mx-auto rounded-[2rem] border border-dashed border-white/15 bg-white/[0.03] p-8 text-center">
                            <p class="text-lg font-semibold">{{ __('This thread is ready') }}</p>
                            <p class="text-sm text-slate-300 mt-3">
                                {{ __('Send a prompt and Socius will respond in real time.') }}
                            </p>
                        </div>
                    </template>

                    <template x-if="loadingMessages">
                        <div class="space-y-5 max-w-3xl mx-auto w-full">
                            <div class="h-24 rounded-[2rem] bg-white/[0.04] animate-pulse"></div>
                            <div class="h-24 rounded-[2rem] bg-white/[0.04] animate-pulse"></div>
                        </div>
                    </template>

                    <template x-for="(message, index) in messages" :key="message.id">
                        <div :id="'msg-' + message.id" class="max-w-4xl mx-auto group/msg"
                            :class="message.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                            <div class="relative w-full md:w-auto md:max-w-[80%] rounded-[2rem] px-5 py-4 border"
                                :class="message.role === 'user'
                                                                                ? 'bg-[#2271b1] text-white border-[#1d629b] shadow-lg shadow-blue-500/10'
                                                                                : 'bg-white/[0.04] text-white border-white/10'">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-9 h-9 rounded-2xl flex items-center justify-center text-sm"
                                        :class="message.role === 'user' ? 'bg-white/60' : 'bg-white/10 text-blue-300'">
                                        <i class="fa-solid"
                                            :class="message.role === 'user' ? 'fa-user' : 'fa-sparkles'"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-[10px] font-bold text-slate-400"
                                            x-text="message.role === 'user' ? '{{ __('You') }}' : '{{ __('Socius') }}'"></p>
                                        <p class="text-[11px] opacity-70" x-text="formatRelativeTime(message.created_at)">
                                        </p>
                                    </div>
                                    <div x-data="{ localExportOpen: false }" @click.outside="localExportOpen = false"
                                        class="flex items-center gap-1 transition-opacity relative"
                                        :class="localExportOpen ? 'opacity-100' : 'opacity-0 group-hover/msg:opacity-100'">
                                        <template x-if="message.role === 'user'">
                                            <button @click="startEditing(message.id, message.content)"
                                                class="w-7 h-7 rounded-xl flex items-center justify-center transition-all hover:bg-black/10 text-slate-700">
                                                <i class="fa-regular fa-pen-to-square text-xs"></i>
                                            </button>
                                        </template>
                                        <template x-if="message.role === 'assistant' && index === messages.length - 1">
                                            <button @click="regenerateResponse(message.id)"
                                                class="w-7 h-7 rounded-xl flex items-center justify-center transition-all hover:bg-white/10 text-slate-400">
                                                <i class="fa-solid fa-rotate text-xs"></i>
                                            </button>
                                        </template>
                                        <button @click="copyMessage(message.content, message.id, $event.currentTarget)"
                                            class="w-7 h-7 rounded-xl flex items-center justify-center transition-all"
                                            :class="message.role === 'user' ? 'hover:bg-black/10 text-slate-700' : 'hover:bg-white/10 text-slate-400'"
                                            title="{{ __('Copy message') }}">
                                            <i class="fa-regular fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                </div>

                                <template x-if="message.attachments && message.attachments.length">
                                    <div class="flex flex-wrap gap-2 mb-3">
                                        <template x-for="attachment in message.attachments"
                                            :key="attachment.id ?? attachment.original_name">
                                            <div class="px-3 py-2 rounded-2xl text-xs border"
                                                :class="message.role === 'user' ? 'border-white/40 bg-white/30' : 'border-white/10 bg-white/[0.05]'">
                                                <div class="font-black" x-text="attachment.original_name"></div>
                                                <div class="opacity-70 mt-1"
                                                    x-text="formatBytes(attachment.file_size || attachment.size_bytes)">
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="editingMessageId === message.id">
                                    <div class="mt-2">
                                        <textarea :id="`edit-textarea-${message.id}`" x-model="editingContent"
                                            class="w-full bg-white/20 border-white/30 rounded-xl text-sm text-slate-900 placeholder:text-slate-600 focus:ring-0 resize-none px-3 py-2"
                                            rows="3" @keydown.escape="cancelEditing()"
                                            @keydown.enter.ctrl="submitEdit(message.id)"></textarea>
                                        <div class="flex justify-end gap-2 mt-2">
                                            <button @click="cancelEditing()"
                                                class="text-[9px] font-bold px-3 py-1.5 rounded-lg bg-black/10 text-slate-700">{{ __('Cancel') }}</button>
                                            <button @click="submitEdit(message.id)"
                                                class="text-[9px] font-bold px-3 py-1.5 rounded-lg bg-slate-900 text-white">{{ __('Save & Resend') }}</button>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="editingMessageId !== message.id">
                                    <div>
                                        <div :id="`socius-message-body-${message.id}`"
                                            class="text-sm leading-7 socius-prose"
                                            x-html="renderMessage(message.content, message.role)"></div>

                                        {{-- Bottom Toolbar & Quality Rating (Rendered ONLY after output is finished) --}}
                                        <template x-if="!sending && message.role === 'assistant'">
                                            <div
                                                class="mt-4 pt-3 border-t border-white/10 flex flex-wrap items-center justify-between gap-3">
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="text-[10px] font-bold text-slate-400 mr-1">{{ __('Was this response helpful?') }}</span>
                                                    <button @click="rateMessage(message.id, 'like')"
                                                        class="px-2.5 py-1 rounded-xl text-[10px] font-bold flex items-center gap-1 transition-all"
                                                        :class="message.metadata?.rating === 'like' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-white/5 hover:bg-white/10 text-slate-300 border border-white/10'"
                                                        title="{{ __('Helpful response') }}">
                                                        <i class="fa-solid fa-thumbs-up text-[10px]"></i> {{ __('Yes') }}
                                                    </button>
                                                    <button @click="rateMessage(message.id, 'dislike')"
                                                        class="px-2.5 py-1 rounded-xl text-[10px] font-bold flex items-center gap-1 transition-all"
                                                        :class="message.metadata?.rating === 'dislike' ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' : 'bg-white/5 hover:bg-white/10 text-slate-300 border border-white/10'"
                                                        title="{{ __('Needs improvement') }}">
                                                        <i class="fa-solid fa-thumbs-down text-[10px]"></i> {{ __('No') }}
                                                    </button>
                                                </div>

                                                <div>
                                                    <button @click="saveAsProposal(message.content)"
                                                        class="px-3 py-1.5 rounded-xl text-[10px] font-bold bg-[#2271b1] text-white hover:bg-[#135e96] flex items-center gap-1.5 shadow-xs transition-all"
                                                        title="{{ __('Save this response in your Library') }}">
                                                        <i class="fa-solid fa-bookmark text-[9px]"></i>
                                                        {{ __('Save') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </template>

                                        {{-- Optional Suggestions Bar (Rendered ONLY after output is finished) --}}
                                        <template
                                            x-if="!sending && message.role === 'assistant' && (message.content.includes('Chapter 1') || message.content.includes('Proposal') || message.content.includes('Methodology'))">
                                            <div class="mt-3 pt-2 flex flex-wrap items-center gap-2">
                                                <span
                                                    class="text-[10px] font-bold text-slate-400 mr-1 flex items-center gap-1">
                                                    {{ __('Add Optional Sections:') }}
                                                </span>
                                                <button
                                                    @click="promptSuggestedSection('Include Project Budget following the baseline structure')"
                                                    class="px-2.5 py-1 rounded-xl text-[10px] font-bold bg-white/5 hover:bg-white/10 text-slate-200 border border-white/10 flex items-center gap-1 transition-all">
                                                    {{ __('➕ Add Budget') }}
                                                </button>
                                                <button
                                                    @click="promptSuggestedSection('Include Project Timeline & Milestones following the baseline structure')"
                                                    class="px-2.5 py-1 rounded-xl text-[10px] font-bold bg-white/5 hover:bg-white/10 text-slate-200 border border-white/10 flex items-center gap-1 transition-all">
                                                    {{ __('➕ Add Timeline') }}
                                                </button>
                                                <button
                                                    @click="promptSuggestedSection('Include Expected Outcomes & Policy Implications following the baseline structure')"
                                                    class="px-2.5 py-1 rounded-xl text-[10px] font-bold bg-white/5 hover:bg-white/10 text-slate-200 border border-white/10 flex items-center gap-1 transition-all">
                                                    {{ __('➕ Add Expected Outcomes') }}
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Scroll to bottom floating button --}}
                <div x-show="scrolledUp" x-transition class="absolute bottom-28 left-1/2 -translate-x-1/2 z-30"
                    style="display: none;">
                    <button @click="scrollToBottom()"
                        class="w-10 h-10 rounded-full bg-[#2271b1] text-white flex items-center justify-center shadow-lg hover:bg-[#135e96]">
                        <i class="fa-solid fa-arrow-down"></i>
                    </button>
                </div>

                {{-- Floating Quote Button --}}
                <div x-show="showQuoteButton" x-transition
                    class="absolute z-50 bg-[#363636] border border-white/15 text-slate-200 text-[10px] font-bold px-3 py-1.5 rounded-xl shadow-2xl cursor-pointer hover:bg-[#2271b1] hover:text-white transition-all flex items-center gap-1.5"
                    :style="`left: ${quoteButtonX}px; top: ${quoteButtonY}px;`" @mousedown.prevent.stop="quoteSelection()"
                    style="display: none;">
                    <i class="fa-solid fa-quote-left text-[9px]"></i>
                    {{ __('Quote') }}
                </div>

                {{-- Input Area --}}
                <div class="px-4 py-2 border-t border-white/10 bg-[#2b2b2b]">
                    <input type="file" x-ref="fileInput" class="hidden" multiple
                        accept=".pdf,.csv,.txt,.docx,.jpg,.jpeg,.png,.webp" @change="handleFileSelection">
                    <div class="rounded-2xl border border-white/10 bg-[#363636] px-4 py-2" x-data="{ toolsOpen: false }">
                        <template x-if="pendingFiles.length">
                            <div class="flex flex-wrap gap-2 mb-2">
                                <template x-for="(file, index) in pendingFiles" :key="file.name + file.size + index">
                                    <div
                                        class="inline-flex items-center gap-2 rounded-xl bg-white/[0.06] border border-white/10 px-2.5 py-1.5 text-xs">
                                        <div>
                                            <p class="font-semibold text-white" x-text="file.name"></p>
                                            <p class="text-slate-400" x-text="formatBytes(file.size)"></p>
                                        </div>
                                        <button type="button" @click="removePendingFile(index)"
                                            class="text-slate-400 hover:text-white">
                                            <i class="fa-solid fa-xmark text-[10px]"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <textarea id="socius-prompt-input" x-model="draft" x-ref="textarea" rows="1"
                            @input="adjustTextareaHeight($event.target)"
                            @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); sendMessage(); }"
                            style="min-height: 36px; max-height: 100px; overflow-y: auto;"
                            class="w-full bg-transparent border-0 focus:ring-0 resize-none text-sm text-white placeholder:text-slate-500 leading-relaxed py-1"
                            :placeholder="'{{ __('Ask Socius...') }}'" :disabled="sending || !canAnalyze"></textarea>

                        <div class="flex items-center justify-between gap-3 pt-2" x-data="{ toolsOpen: false }">
                            <div class="relative">
                                <button type="button" @click="toolsOpen = !toolsOpen" @click.outside="toolsOpen = false"
                                    class="w-8 h-8 rounded-full bg-white/[0.06] border border-white/10 text-slate-300 hover:text-white hover:bg-white/10 flex items-center justify-center transition-all duration-150"
                                    :class="{ 'rotate-45 text-white bg-white/20': toolsOpen }">
                                    <i class="fa-solid fa-plus text-xs"></i>
                                </button>

                                <div x-show="toolsOpen" x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                                    class="absolute bottom-11 left-0 z-50 min-w-[200px] bg-[#2a2a2a] border border-white/10 rounded-2xl shadow-2xl p-2 flex flex-col gap-1.5 backdrop-blur-md"
                                    style="display: none;">

                                    <button type="button" @click="pickFiles(); toolsOpen = false"
                                        :disabled="sending || !canAnalyze"
                                        class="w-full inline-flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-white hover:bg-white/10 disabled:opacity-40 disabled:cursor-not-allowed transition-all text-left">
                                        <i class="fa-solid fa-paperclip text-xs w-4 text-center"></i>
                                        <span>{{ __('Attach') }}</span>
                                    </button>

                                    <label
                                        class="w-full inline-flex items-center gap-2.5 px-3 py-2 rounded-xl border text-xs font-semibold cursor-pointer transition-all select-none"
                                        :class="webSearchEnabled ? 'bg-blue-400/20 border-blue-400/40 text-blue-300' : 'bg-transparent border-transparent text-slate-400 hover:text-slate-200 hover:bg-white/10'">
                                        <input type="checkbox" x-model="webSearchEnabled" class="hidden">
                                        <i class="fa-solid fa-globe text-xs w-4 text-center"></i>
                                        <span>{{ __('Web Search') }}</span>
                                    </label>

                                    <button type="button" @click="toggleVoiceInput()"
                                        :class="isListening ? 'bg-red-500/20 border-red-500/40 text-red-400' : 'bg-transparent border-transparent text-slate-400 hover:text-slate-200 hover:bg-white/10'"
                                        class="w-full inline-flex items-center gap-2.5 px-3 py-2 rounded-xl border text-xs font-semibold transition-all text-left">
                                        <i class="fa-solid w-4 text-center"
                                            :class="isListening ? 'fa-microphone-lines animate-pulse' : 'fa-microphone text-xs'"></i>
                                        <span
                                            x-text="isListening ? '{{ __('Listening...') }}' : '{{ __('Voice') }}'"></span>
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <template x-if="error">
                                    <p class="text-xs text-red-400 max-w-[180px] truncate" x-text="error"></p>
                                </template>

                                <template x-if="sending">
                                    <button type="button" @click="stopGeneration()"
                                        class="w-8 h-8 rounded-full bg-red-500/20 border border-red-500/40 text-red-400 flex items-center justify-center hover:bg-red-500/40 hover:text-white transition-all shadow-sm"
                                        title="{{ __('Stop Generating') }}">
                                        <i class="fa-solid fa-stop text-[10px]"></i>
                                    </button>
                                </template>
                                <template x-if="!sending">
                                    <button type="button" @click="sendMessage()"
                                        :disabled="!canAnalyze || (!draft.trim() && pendingFiles.length === 0)"
                                        class="w-8 h-8 rounded-full bg-white/10 border border-white/10 text-white flex items-center justify-center hover:bg-[#2271b1] hover:text-white transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                                        <i class="fa-solid fa-arrow-up text-xs"></i>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                    <p class="text-[9px] text-slate-500 text-center mt-1">
                        {{ __('Socius AI can make mistakes. Please verify important information.') }}
                    </p>
                </div>
            </section>
        </div>

        {{-- Knowledge Base Modal --}}
        <div x-show="kbModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display: none;"
            @click.self="kbModalOpen = false">
            <div class="relative w-full max-w-2xl bg-[#2b2b2b] text-white rounded-[2rem] border border-white/10 shadow-2xl flex flex-col max-h-[85vh] overflow-hidden"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0">
                <div class="px-6 py-5 border-b border-white/10 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-[#2271b1]/10 flex items-center justify-center text-[#3894dc]">
                            <i class="fa-solid fa-brain text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold tracking-tight">{{ __('Knowledge Base') }}</h3>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ __('Set custom style and formatting instructions for Socius.') }}
                            </p>
                        </div>
                    </div>
                    <button @click="kbModalOpen = false"
                        class="w-8 h-8 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-slate-400 hover:text-white transition-all">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-6">
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-slate-400">{{ __('Add Custom Instruction') }}</h4>
                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-3 focus-within:border-[#2271b1]/50 transition-all">
                            <textarea x-model="newKbRuleContent" rows="3"
                                class="w-full bg-transparent border-0 focus:ring-0 resize-none text-sm text-white placeholder:text-slate-500"
                                placeholder="{{ __('e.g., Always use APA 7th edition format...') }}" :disabled="savingKb"
                                @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); addKbRule(); }"></textarea>
                            <div class="flex justify-end pt-2">
                                <button @click="addKbRule()" :disabled="savingKb || !newKbRuleContent.trim()"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#2271b1] text-white text-[10px] font-bold disabled:opacity-40 disabled:cursor-not-allowed hover:bg-[#135e96] transition-all">
                                    <i class="fa-solid fa-plus text-[10px]" :class="{ 'fa-spin': savingKb }"></i>
                                    {{ __('Add Instruction') }}
                                </button>
                            </div>
                        </div>
                        <div class="space-y-3 pt-2 border-t border-white/10">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-slate-300 flex items-center gap-1.5">
                                    {{ __('Upload Knowledge Base File') }}
                                </h4>
                                <span class="text-[10px] text-slate-500 font-mono">PDF, DOCX, TXT (Max 20MB)</span>
                            </div>
                            <div
                                class="rounded-2xl border border-dashed border-white/15 bg-white/[0.02] p-4 text-center hover:bg-white/[0.04] transition-all">
                                <input type="file" x-ref="kbFileInput" @change="uploadKbDocument($event)"
                                    accept=".pdf,.docx,.doc,.txt,.md" class="hidden">
                                <div class="flex flex-col items-center gap-2 cursor-pointer"
                                    @click="$refs.kbFileInput.click()">
                                    <div
                                        class="w-10 h-10 rounded-2xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center">
                                        <i class="fa-solid"
                                            :class="uploadingKbDoc ? 'fa-spinner fa-spin text-base' : 'fa-cloud-arrow-up text-base'"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-200"
                                            x-text="uploadingKbDoc ? 'Reading & Extracting Document...' : '{{ __('Click to upload Research Guidebook or Reference Manual') }}'">
                                        </p>
                                        <p class="text-[11px] text-slate-400 mt-0.5">
                                            {{ __('Socius will automatically parse and integrate full document knowledge.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-slate-400">{{ __('Active Instructions') }}</h4>
                                <template x-if="!loadingKb && kbRules.length > 0">
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="deactivateAllKbRules()"
                                            class="px-2.5 py-1 rounded-xl text-[10px] font-bold bg-amber-500/10 text-amber-300 hover:bg-amber-500/20 border border-amber-500/20 transition-all flex items-center gap-1"
                                            title="{{ __('Deactivate all instructions') }}">
                                            <i class="fa-solid fa-power-off text-[9px]"></i> {{ __('Deactivate All') }}
                                        </button>
                                        <button type="button" @click="deleteAllKbRules()"
                                            class="px-2.5 py-1 rounded-xl text-[10px] font-bold bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 transition-all flex items-center gap-1"
                                            title="{{ __('Delete all instructions') }}">
                                            <i class="fa-solid fa-trash-can text-[9px]"></i> {{ __('Delete All') }}
                                        </button>
                                    </div>
                                </template>
                            </div>
                            <template x-if="loadingKb">
                                <div class="space-y-2">
                                    <div class="h-16 rounded-2xl bg-white/[0.03] animate-pulse"></div>
                                    <div class="h-16 rounded-2xl bg-white/[0.03] animate-pulse"></div>
                                </div>
                            </template>
                            <template x-if="!loadingKb && kbRules.length === 0">
                                <div
                                    class="rounded-[1.5rem] border border-dashed border-white/10 bg-white/[0.01] p-8 text-center">
                                    <p class="text-sm font-semibold text-slate-300">{{ __('No instructions yet') }}</p>
                                    <p class="text-xs text-slate-500 mt-1">
                                        {{ __('Add formatting details or stylistic guidelines above.') }}
                                    </p>
                                </div>
                            </template>
                            <template x-if="!loadingKb && kbRules.length > 0">
                                <div class="space-y-2.5 flex flex-col">
                                    <template x-for="rule in kbRules" :key="rule.id">
                                        <div class="group/rule flex items-start justify-between gap-4 p-4 rounded-2xl border border-white/5 transition-all"
                                            :class="rule.is_active ? 'bg-white/[0.03] border-white/10' : 'bg-white/[0.01] border-white/[0.02] opacity-60'">
                                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                                <button @click="toggleKbRule(rule)"
                                                    class="mt-0.5 relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                                                    :class="rule.is_active ? 'bg-[#2271b1]' : 'bg-white/10'">
                                                    <span
                                                        class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                                        :class="rule.is_active ? 'translate-x-4' : 'translate-x-0'"></span>
                                                </button>
                                                <div class="flex-1 min-w-0">
                                                    <template
                                                        x-if="rule.content && (rule.content.startsWith('[Book/Doc:') || rule.content.startsWith('[Doc:'))">
                                                        <div>
                                                            <div class="flex items-center gap-2">
                                                                <i class="fa-solid fa-file-lines text-blue-400 text-sm"></i>
                                                                <span class="text-xs font-extrabold text-slate-100 truncate"
                                                                    x-text="formatDocName(rule.content)"></span>
                                                            </div>
                                                            <p class="text-[10px] text-emerald-400 font-bold mt-1">
                                                                <i class="fa-solid fa-circle-check text-[9px] mr-1"></i>
                                                                <span
                                                                    x-text="'Document read & active (' + rule.content.length.toLocaleString() + ' characters)'"></span>
                                                            </p>
                                                        </div>
                                                    </template>
                                                    <template
                                                        x-if="!rule.content || (!rule.content.startsWith('[Book/Doc:') && !rule.content.startsWith('[Doc:'))">
                                                        <p class="text-sm text-slate-100 break-words whitespace-pre-wrap font-medium leading-relaxed"
                                                            :class="{ 'line-through text-slate-500': !rule.is_active }"
                                                            x-text="rule.content"></p>
                                                    </template>
                                                    <p class="text-[9px] text-slate-500 mt-1 font-bold"
                                                        x-text="formatRelativeTime(rule.created_at)"></p>
                                                </div>
                                            </div>
                                            <button @click="deleteKbRule(rule.id)"
                                                class="w-7 h-7 rounded-xl flex items-center justify-center text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-all opacity-0 group-hover/rule:opacity-100">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-white/10 bg-[#232323] flex justify-end">
                        <button @click="kbModalOpen = false"
                            class="px-5 py-2.5 rounded-xl bg-white/5 border border-white/10 text-xs font-bold text-slate-300 hover:text-white hover:bg-white/10 transition-all">
                            {{ __('Close') }}
                        </button>
                    </div>
                </div>
            </div>

        </div>
@endsection

    @push('scripts')
        <script>
            function wrapLabel(label, maxLen = 18) {
                if (!label) return '';
                const str = String(label);
                if (str.length <= maxLen) return str;
                const words = str.split(' ');
                const lines = [];
                let current = '';
                words.forEach(word => {
                    if ((current + ' ' + word).trim().length > maxLen) {
                        if (current) lines.push(current.trim());
                        current = word;
                    } else {
                        current = (current + ' ' + word).trim();
                    }
                });
                if (current) lines.push(current.trim());
                return lines;
            }

            window.copyRenderedSociusTable = function (tableId, btn = null) {
                const table = document.getElementById(tableId);
                if (!table) return;

                let plainText = '';
                const rows = table.querySelectorAll('tr');
                rows.forEach((row) => {
                    const cols = row.querySelectorAll('th, td');
                    const rowData = [];
                    cols.forEach(col => {
                        rowData.push(col.innerText.trim());
                    });
                    plainText += rowData.join('\t') + '\n';
                });

                let htmlContent = `<table style="border-collapse: collapse; width: 100%; font-family: Calibri, Arial, sans-serif; font-size: 13px; color: #1e293b; border: 1px solid #cbd5e1;">`;
                const headerRows = table.querySelectorAll('thead tr');
                if (headerRows.length > 0) {
                    htmlContent += `<thead>`;
                    headerRows.forEach(row => {
                        htmlContent += `<tr>`;
                        row.querySelectorAll('th').forEach(th => {
                            htmlContent += `<th style="border: 1px solid #cbd5e1; background-color: #f1f5f9; padding: 8px 12px; font-weight: bold; text-align: left; color: #0f172a;">${th.innerHTML.trim()}</th>`;
                        });
                        htmlContent += `</tr>`;
                    });
                    htmlContent += `</thead>`;
                }

                const bodyRows = table.querySelectorAll('tbody tr');
                htmlContent += `<tbody>`;
                bodyRows.forEach((row, idx) => {
                    const isTotal = row.innerText.toLowerCase().includes('total');
                    const bg = isTotal ? 'background-color: #f8fafc; font-weight: bold;' : (idx % 2 === 0 ? 'background-color: #ffffff;' : 'background-color: #f8fafc;');
                    htmlContent += `<tr style="${bg}">`;
                    row.querySelectorAll('td').forEach(td => {
                        const fontWeight = isTotal ? 'font-weight: bold; color: #0f172a;' : 'color: #334155;';
                        htmlContent += `<td style="border: 1px solid #cbd5e1; padding: 8px 12px; text-align: left; ${fontWeight}">${td.innerHTML.trim()}</td>`;
                    });
                    htmlContent += `</tr>`;
                });
                htmlContent += `</tbody></table>`;

                try {
                    const blobHtml = new Blob([htmlContent], { type: 'text/html' });
                    const blobText = new Blob([plainText], { type: 'text/plain' });
                    const item = new ClipboardItem({ 'text/html': blobHtml, 'text/plain': blobText });

                    navigator.clipboard.write([item]).then(() => {
                        if (btn) {
                            const originalHtml = btn.innerHTML;
                            btn.innerHTML = '<i class="fa-solid fa-check text-[10px] text-green-400"></i> Copied!';
                            setTimeout(() => { btn.innerHTML = originalHtml; }, 2000);
                        }
                    }).catch(() => {
                        navigator.clipboard.writeText(plainText).then(() => {
                            if (btn) {
                                const originalHtml = btn.innerHTML;
                                btn.innerHTML = '<i class="fa-solid fa-check text-[10px] text-green-400"></i> Copied!';
                                setTimeout(() => { btn.innerHTML = originalHtml; }, 2000);
                            }
                        });
                    });
                } catch (e) {
                    navigator.clipboard.writeText(plainText);
                }
            };

            window.standaloneSociusManager = function (config) {
                return {
                    canAnalyze: config.canAnalyze,
                    initialContext: config.initialContext || null,
                    currentThreadId: null,
                    currentThread: null,
                    threads: [],
                    messages: [],
                    draft: '',
                    pendingFiles: [],
                    loadingThreads: false,
                    loadingMessages: false,
                    creatingThread: false,
                    sending: false,
                    error: null,
                    streamingUserId: null,
                    streamingAssistantId: null,
                    renamingThreadId: null,
                    editingTitle: '',
                    threadMenuOpen: null,
                    urls: config.urls,
                    isListening: false,
                    recognition: null,
                    editingMessageId: null,
                    editingContent: '',
                    tokenUsage: null,
                    webSearchEnabled: false,
                    historyOpen: window.innerWidth > 1280,
                    scrolledUp: false,
                    activePromptId: null,
                    showQuoteButton: false,
                    quoteButtonX: 0,
                    quoteButtonY: 0,
                    selectedText: '',
                    kbModalOpen: false,
                    kbRules: [],
                    newKbRuleContent: '',
                    loadingKb: false,
                    savingKb: false,
                    renderDebounce: null,
                    activeAbortController: null,

                    stopGeneration() {
                        if (this.activeAbortController) {
                            this.activeAbortController.abort();
                            this.activeAbortController = null;
                        }
                        this.sending = false;
                    },

                    handleScroll() {
                        const el = this.$refs.messageList;
                        if (!el) return;
                        this.scrolledUp = (el.scrollHeight - el.scrollTop - el.clientHeight) > 150;
                        const userMsgs = this.messages.filter(m => m.role === 'user');
                        let closestId = null, minDiff = Infinity;
                        const containerRect = el.getBoundingClientRect();
                        const centerY = containerRect.top + containerRect.height / 2;
                        userMsgs.forEach(m => {
                            const msgEl = document.getElementById(`msg-${m.id}`);
                            if (msgEl) {
                                const rect = msgEl.getBoundingClientRect();
                                const diff = Math.abs(rect.top + rect.height / 2 - centerY);
                                if (diff < minDiff) { minDiff = diff; closestId = m.id; }
                            }
                        });
                        if (closestId) this.activePromptId = closestId;
                    },

                    scrollToBottom() {
                        const el = this.$refs.messageList;
                        if (el) el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
                    },

                    scrollToPrompt(msgId) {
                        const el = document.getElementById(`msg-${msgId}`);
                        if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); this.activePromptId = msgId; }
                    },

                    quoteSelection() {
                        if (!this.selectedText) return;
                        this.draft = `> "${this.selectedText}"\n\n` + this.draft;
                        this.showQuoteButton = false;
                        window.getSelection().removeAllRanges();
                        const inputEl = document.getElementById('socius-prompt-input');
                        if (inputEl) inputEl.focus();
                    },

                    init() {
                        this.loadThreads();
                        this.loadKbRules();

                        this.$watch('messages', () => {
                            if (this.renderDebounce) clearTimeout(this.renderDebounce);
                            this.renderDebounce = setTimeout(() => this.renderVisuals(), 100);
                        });
                        this.$nextTick(() => this.renderVisuals());

                        document.addEventListener('selectionchange', () => {
                            if (this.currentThreadId === null) { this.showQuoteButton = false; return; }
                            const selection = window.getSelection();
                            const selected = selection.toString().trim();
                            if (!selected || selected.length < 3) { this.showQuoteButton = false; return; }
                            let node = selection.anchorNode;
                            let isInsideSociusProse = false;
                            while (node) {
                                if (node.classList && node.classList.contains('socius-prose')) { isInsideSociusProse = true; break; }
                                node = node.parentNode;
                            }
                            if (!isInsideSociusProse) { this.showQuoteButton = false; return; }
                            this.selectedText = selected;
                            try {
                                const range = selection.getRangeAt(0);
                                const rect = range.getBoundingClientRect();
                                const wrapper = document.querySelector('.socius-root-container');
                                if (wrapper) {
                                    const wrapperRect = wrapper.getBoundingClientRect();
                                    this.quoteButtonX = rect.left - wrapperRect.left + (rect.width / 2) - 40;
                                    this.quoteButtonY = rect.top - wrapperRect.top - 40;
                                    this.showQuoteButton = true;
                                }
                            } catch (e) { this.showQuoteButton = false; }
                        });

                        // Seed initial context from transcription if present
                        if (this.initialContext && this.initialContext.text) {
                            const contextObj = { ...this.initialContext };
                            this.initialContext = null;
                            this.$nextTick(async () => {
                                const thread = await this.createThreadWithContext(contextObj);
                                if (thread) {
                                    this.currentThread = thread;
                                    this.currentThreadId = thread.id;
                                    this.messages = [];
                                    const prompt = `Analyze the following ${contextObj.label || 'transcription'}:\n\n${contextObj.text}`;
                                    await this.sendMessage(prompt, thread.id);
                                }
                            });
                        }
                    },

                    async loadThreads() {
                        this.loadingThreads = true;
                        this.error = null;
                        this.currentThreadId = null;
                        this.currentThread = null;
                        this.messages = [];
                        try {
                            const response = await fetch(this.urls.list, { headers: { 'Accept': 'application/json' } });
                            const data = await this.parseJsonResponse(response);
                            this.threads = data.threads || [];
                        } catch (error) {
                            this.error = error.message;
                        } finally {
                            this.loadingThreads = false;
                        }
                    },

                    async createThread(selectAfter = true) {
                        if (!this.canAnalyze) return null;
                        this.creatingThread = true;
                        this.error = null;
                        try {
                            const response = await fetch(this.urls.create, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({ context_type: 'general' })
                            });
                            const data = await this.parseJsonResponse(response);
                            const thread = data.thread;
                            this.threads = [thread, ...this.threads.filter(item => item.id !== thread.id)];
                            if (selectAfter) await this.loadThread(thread.id);
                            return thread;
                        } catch (error) {
                            this.error = error.message;
                            return null;
                        } finally {
                            this.creatingThread = false;
                        }
                    },

                    async createThreadWithContext(context) {
                        if (!this.canAnalyze) return null;
                        this.creatingThread = true;
                        this.error = null;
                        try {
                            const response = await fetch(this.urls.create, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({
                                    context_type: context.type || 'transcription',
                                    initial_context_text: context.text || '',
                                    initial_context_label: context.label || 'Document'
                                })
                            });
                            const data = await this.parseJsonResponse(response);
                            const thread = data.thread;
                            this.threads = [thread, ...this.threads.filter(item => item.id !== thread.id)];
                            return thread;
                        } catch (error) {
                            this.error = error.message;
                            return null;
                        } finally {
                            this.creatingThread = false;
                        }
                    },

                    async renameThread(threadId, newTitle) {
                        if (!newTitle || !newTitle.trim()) return;
                        try {
                            const response = await fetch(this.threadUrl('updateTemplate', threadId), {
                                method: 'PATCH',
                                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                                body: JSON.stringify({ title: newTitle.trim() })
                            });
                            const data = await this.parseJsonResponse(response);
                            const idx = this.threads.findIndex(t => t.id === threadId);
                            if (idx !== -1) this.threads[idx] = data.thread;
                            if (this.currentThread && this.currentThread.id === threadId) this.currentThread = data.thread;
                        } catch (error) {
                            this.error = error.message;
                        } finally {
                            this.renamingThreadId = null;
                            this.editingTitle = '';
                        }
                    },

                    async deleteThread(threadId) {
                        const result = await Swal.fire({
                            title: @js(__('Delete Conversation?')),
                            text: @js(__('This will permanently delete this conversation and all associated attachments.')),
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#6b7280',
                            confirmButtonText: @js(__('Yes, Delete It')),
                            cancelButtonText: @js(__('Cancel')),
                            customClass: { popup: 'rounded-3xl border-none shadow-2xl' }
                        });
                        if (!result.isConfirmed) return;
                        try {
                            const response = await fetch(this.threadUrl('destroyTemplate', threadId), {
                                method: 'DELETE',
                                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                            });
                            await this.parseJsonResponse(response);
                            this.threads = this.threads.filter(t => t.id != threadId);
                            if (this.currentThreadId == threadId) {
                                this.currentThreadId = null;
                                this.currentThread = null;
                                this.messages = [];
                            }
                            Swal.fire({ title: @js(__('Deleted!')), icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, customClass: { popup: 'rounded-2xl shadow-xl border-none' } });
                        } catch (error) {
                            Swal.fire({ title: @js(__('Error')), text: error.message, icon: 'error', customClass: { popup: 'rounded-3xl border-none shadow-2xl' } });
                        }
                    },

                    async togglePin(threadId) {
                        try {
                            const response = await fetch(this.threadUrl('pin_toggleTemplate', threadId), {
                                method: 'POST',
                                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                            });
                            const data = await this.parseJsonResponse(response);
                            const idx = this.threads.findIndex(t => t.id === threadId);
                            if (idx !== -1) { this.threads[idx] = data.thread; this.sortThreads(); }
                        } catch (error) { this.error = error.message; }
                    },

                    sortThreads() {
                        this.threads.sort((a, b) => {
                            if (a.is_pinned !== b.is_pinned) return b.is_pinned ? 1 : -1;
                            return new Date(b.last_activity_at) - new Date(a.last_activity_at);
                        });
                    },

                    async selectThread(threadId) {
                        if (threadId === this.currentThreadId) return;
                        await this.loadThread(threadId);
                    },

                    async loadThread(threadId) {
                        this.loadingMessages = true;
                        this.error = null;
                        try {
                            const response = await fetch(this.threadUrl('showTemplate', threadId), { headers: { 'Accept': 'application/json' } });
                            const data = await this.parseJsonResponse(response);
                            this.currentThread = data.thread;
                            this.currentThreadId = data.thread.id;
                            this.messages = data.messages || [];
                            this.scrollMessages();
                        } catch (error) {
                            this.error = error.message;
                        } finally {
                            this.loadingMessages = false;
                            this.$nextTick(() => {
                                const inputEl = document.getElementById('socius-prompt-input');
                                if (inputEl) inputEl.focus();
                            });
                        }
                    },

                    adjustTextareaHeight(target) {
                        const el = target || document.getElementById('socius-prompt-input');
                        if (!el) return;
                        el.style.height = 'auto';
                        const newHeight = Math.min(el.scrollHeight, 200);
                        el.style.height = newHeight + 'px';
                        el.style.overflowY = el.scrollHeight > 200 ? 'auto' : 'hidden';
                    },

                    pickFiles() { this.$refs.fileInput.click(); },

                    handleFileSelection(event) {
                        const selected = Array.from(event.target.files || []);
                        selected.forEach(file => {
                            const exists = this.pendingFiles.some(e => e.name === file.name && e.size === file.size);
                            if (!exists) this.pendingFiles.push(file);
                        });
                        event.target.value = '';
                    },

                    removePendingFile(index) { this.pendingFiles.splice(index, 1); },

                    startEditing(messageId, content) {
                        this.editingMessageId = messageId;
                        this.editingContent = content;
                        this.$nextTick(() => {
                            const el = document.getElementById(`edit-textarea-${messageId}`);
                            if (el) el.focus();
                        });
                    },

                    cancelEditing() { this.editingMessageId = null; this.editingContent = ''; },

                    async submitEdit(messageId) {
                        if (!this.editingContent.trim() || this.sending) return;
                        const idx = this.messages.findIndex(m => m.id === messageId);
                        if (idx === -1) return;
                        const threadId = this.currentThreadId;
                        const newContent = this.editingContent;
                        this.cancelEditing();
                        this.messages = this.messages.slice(0, idx);
                        await this.sendMessage(newContent, threadId);
                    },

                    async regenerateResponse(messageId) {
                        if (this.sending) return;
                        const idx = this.messages.findIndex(m => m.id === messageId);
                        if (idx <= 0) return;
                        const userMessage = this.messages[idx - 1];
                        if (userMessage.role !== 'user') return;
                        const threadId = this.currentThreadId;
                        const content = userMessage.content;
                        this.messages = this.messages.slice(0, idx);
                        await this.sendMessage(content, threadId);
                    },

                    async sendMessage(overrideContent = null, overrideThreadId = null) {
                        if (this.sending || !this.canAnalyze) return;
                        const content = (overrideContent !== null ? overrideContent : this.draft).trim();
                        if (!content && this.pendingFiles.length === 0) return;

                        let threadId = overrideThreadId || this.currentThreadId;
                        if (!threadId) {
                            const thread = await this.createThread(false);
                            if (!thread) return;
                            threadId = thread.id;
                            this.currentThread = thread;
                            this.currentThreadId = thread.id;
                        }

                        this.error = null;
                        this.sending = true;
                        this.activeAbortController = new AbortController();

                        const tempUserId = `temp-user-${Date.now()}`;
                        const tempAssistantId = `temp-assistant-${Date.now()}`;
                        const optimisticAttachments = this.pendingFiles.map((file, index) => ({ id: `pending-${index}`, original_name: file.name, file_size: file.size }));

                        this.messages.push({ id: tempUserId, role: 'user', content, attachments: optimisticAttachments, created_at: new Date().toISOString() });
                        this.messages.push({ id: tempAssistantId, role: 'assistant', content: '', attachments: [], created_at: new Date().toISOString() });
                        this.scrollMessages();

                        const formData = new FormData();
                        formData.append('message', content);
                        formData.append('web_search_enabled', this.webSearchEnabled ? '1' : '0');
                        this.pendingFiles.forEach(file => formData.append('attachments[]', file));

                        const usedFiles = [...this.pendingFiles];
                        this.draft = '';
                        this.pendingFiles = [];

                        try {
                            const response = await fetch(this.threadUrl('streamTemplate', threadId), {
                                method: 'POST',
                                signal: this.activeAbortController.signal,
                                headers: { 'Accept': 'text/event-stream', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                                body: formData
                            });

                            if (!response.ok || !response.body) {
                                if (response.status === 429) throw new Error(@js(__('Rate limit exceeded. Please wait a minute and try again.')));
                                const errorData = await this.safeReadJson(response);
                                throw new Error(errorData?.message || @js(__('Socius could not send this message.')));
                            }

                            await this.consumeEventStream(response.body, tempUserId, tempAssistantId);
                            await this.loadThread(threadId);
                            await this.reloadThreadList();
                        } catch (error) {
                            if (error.name === 'AbortError') {
                                this.error = null;
                            } else {
                                this.error = error.message;
                                const failedAssistantId = this.streamingAssistantId || tempAssistantId;
                                this.replaceMessage(failedAssistantId, { id: failedAssistantId, role: 'assistant', content: error.message, attachments: [], created_at: new Date().toISOString() });
                                this.pendingFiles = usedFiles;
                            }
                        } finally {
                            this.activeAbortController = null;
                            this.streamingUserId = null;
                            this.streamingAssistantId = null;
                            this.sending = false;
                            this.$nextTick(() => {
                                const inputEl = document.getElementById('socius-prompt-input');
                                if (inputEl) inputEl.focus();
                            });
                        }
                    },

                    async reloadThreadList() {
                        try {
                            const response = await fetch(this.urls.list, { headers: { 'Accept': 'application/json' } });
                            const data = await this.parseJsonResponse(response);
                            this.threads = data.threads || [];
                        } catch (error) { this.error = error.message; }
                    },

                    async consumeEventStream(stream, tempUserId, tempAssistantId) {
                        const reader = stream.getReader();
                        const decoder = new TextDecoder();
                        let buffer = '';
                        while (true) {
                            const { value, done } = await reader.read();
                            if (done) break;
                            buffer += decoder.decode(value, { stream: true });
                            let boundaryIndex;
                            while ((boundaryIndex = buffer.indexOf('\n\n')) !== -1) {
                                const rawEvent = buffer.slice(0, boundaryIndex).trim();
                                buffer = buffer.slice(boundaryIndex + 2);
                                if (rawEvent) this.handleStreamEvent(rawEvent, tempUserId, tempAssistantId);
                            }
                        }
                    },

                    handleStreamEvent(rawEvent, tempUserId, tempAssistantId) {
                        const lines = rawEvent.split('\n');
                        let eventName = 'message', data = {};
                        lines.forEach(line => {
                            if (line.startsWith('event:')) eventName = line.replace('event:', '').trim();
                            if (line.startsWith('data:')) {
                                try { data = JSON.parse(line.replace('data:', '').trim()); } catch (e) { data = {}; }
                            }
                        });
                        if (eventName === 'meta') {
                            this.streamingUserId = data.user_message_id || tempUserId;
                            this.streamingAssistantId = data.assistant_message_id || tempAssistantId;
                            this.updateMessageId(tempUserId, data.user_message_id);
                            this.updateMessageId(tempAssistantId, data.assistant_message_id);
                        }
                        if (eventName === 'delta') {
                            const assistantMessage = this.messages.find(m => m.id === this.streamingAssistantId) || this.messages.find(m => m.id === tempAssistantId);
                            if (assistantMessage) { assistantMessage.content = `${assistantMessage.content || ''}${data.content || ''}`; this.scrollMessages(); }
                        }
                        if (eventName === 'error') throw new Error(data.message || @js(__('Streaming failed.')));
                    },

                    updateMessageId(oldId, newId) {
                        const target = this.messages.find(m => m.id === oldId);
                        if (target && newId) target.id = newId;
                    },

                    replaceMessage(messageId, replacement) {
                        const index = this.messages.findIndex(m => m.id === messageId);
                        if (index !== -1) this.messages.splice(index, 1, replacement);
                    },

                    threadUrl(key, threadId) { return this.urls[key].replace('__THREAD__', threadId); },

                    scrollMessages() {
                        this.$nextTick(() => {
                            if (this.$refs.messageList) this.$refs.messageList.scrollTop = this.$refs.messageList.scrollHeight;
                        });
                    },

                    async parseJsonResponse(response) {
                        const data = await this.safeReadJson(response);
                        if (!response.ok) throw new Error(data?.message || @js(__('Request failed.')));
                        return data || {};
                    },

                    async safeReadJson(response) {
                        const text = await response.text();
                        if (!text) return null;
                        try { return JSON.parse(text); } catch (e) { return null; }
                    },

                    formatRelativeTime(timestamp) {
                        if (!timestamp) return @js(__('Just now'));
                        const date = new Date(timestamp);
                        if (Number.isNaN(date.getTime())) return @js(__('Just now'));
                        const diffSeconds = Math.round((date.getTime() - Date.now()) / 1000);
                        const rtf = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });
                        if (Math.abs(diffSeconds) < 60) return rtf.format(diffSeconds, 'second');
                        if (Math.abs(diffSeconds) < 3600) return rtf.format(Math.round(diffSeconds / 60), 'minute');
                        if (Math.abs(diffSeconds) < 86400) return rtf.format(Math.round(diffSeconds / 3600), 'hour');
                        return rtf.format(Math.round(diffSeconds / 86400), 'day');
                    },

                    formatBytes(bytes) {
                        if (!bytes) return '0 B';
                        const units = ['B', 'KB', 'MB', 'GB'];
                        let value = bytes, unitIndex = 0;
                        while (value >= 1024 && unitIndex < units.length - 1) { value /= 1024; unitIndex++; }
                        return `${value.toFixed(value >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
                    },

                    renderMessage(content, role) {
                        if (role === 'user') return this.escapeHtml(content || '').replace(/\n/g, '<br>');
                        return this.renderMarkdownLike(content || '');
                    },

                    escapeHtml(value) {
                        return String(value)
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;');
                    },

                    renderMarkdownLike(text) {
                        const normalized = text.replace(/\r\n/g, '\n');
                        const lines = normalized.split('\n');
                        const blocks = [];
                        let paragraph = [], listItems = [], tableLines = [];
                        let inCodeBlock = false, codeBlockType = '', codeBlockLines = [];

                        const flushParagraph = () => {
                            if (paragraph.length) { blocks.push(`<p class="mb-4">${this.inlineFormat(paragraph.join(' '))}</p>`); paragraph = []; }
                        };
                        const flushList = () => {
                            if (listItems.length) {
                                blocks.push(`<ol class="list-decimal list-inside space-y-1 mb-4">${listItems.map(item => `<li>${this.inlineFormat(item)}</li>`).join('')}</ol>`);
                                listItems = [];
                            }
                        };
                        const flushTable = () => {
                            if (tableLines.length) { blocks.push(this.renderMarkdownTable(tableLines)); tableLines = []; }
                        };
                        const flushCodeBlock = () => {
                            if (inCodeBlock) {
                                const content = codeBlockLines.join('\n');
                                const id = 'visual-' + Math.random().toString(36).substr(2, 9);
                                const type = codeBlockType === 'chart.js' ? 'chartjs' : codeBlockType;
                                const isVisual = ['mermaid', 'chartjs', 'pollinations'].includes(type);
                                if (isVisual) {
                                    blocks.push(`<div class="socius-visual my-6 bg-white/5 rounded-2xl border border-white/10 overflow-hidden" data-visual-type="${type}" data-visual-id="${id}"><div class="visual-header flex items-center justify-between px-4 py-2 border-b border-white/10 bg-white/5"><div class="flex gap-2 ml-auto"><button onclick="window.sociusVisuals&&window.sociusVisuals.copy('${id}',this)" class="text-[10px] font-bold text-slate-400 hover:text-white transition-colors"><i class="fa-solid fa-copy mr-1"></i> Copy</button><button onclick="window.sociusVisuals&&window.sociusVisuals.download('${id}','png')" class="text-[10px] font-bold text-slate-400 hover:text-white transition-colors"><i class="fa-solid fa-download mr-1"></i> PNG</button></div></div><div id="${id}" class="visual-body p-6 flex justify-center overflow-x-auto min-h-[100px] relative"><textarea class="visual-source hidden">${this.escapeHtml(content)}</textarea><div class="visual-target w-full flex justify-center"></div></div></div>`);
                                } else {
                                    blocks.push(`<pre class="bg-black/30 p-4 rounded-xl overflow-x-auto text-xs my-4 border border-white/5"><code>${this.escapeHtml(content)}</code></pre>`);
                                }
                                inCodeBlock = false; codeBlockLines = []; codeBlockType = '';
                            }
                        };

                        for (let i = 0; i < lines.length; i++) {
                            const rawLine = lines[i];
                            const line = rawLine.trim();
                            const codeBlockMatch = line.match(/^`{3,}(.*)$/);
                            if (codeBlockMatch) {
                                if (!inCodeBlock) {
                                    flushParagraph(); flushList(); flushTable();
                                    inCodeBlock = true; codeBlockType = codeBlockMatch[1].trim(); codeBlockLines = [];
                                } else { flushCodeBlock(); }
                                continue;
                            }
                            if (inCodeBlock) { codeBlockLines.push(rawLine); continue; }
                            if (/^\|.+\|/.test(line)) { flushParagraph(); flushList(); tableLines.push(line); continue; }
                            if (tableLines.length && !line.startsWith('|')) { flushTable(); }
                            if (/^#{1,6}\s/.test(line)) {
                                flushParagraph(); flushList(); flushTable();
                                const level = (line.match(/^(#{1,6})\s/) || [])[1]?.length || 2;
                                const hText = line.replace(/^#{1,6}\s/, '');
                                const classes = ['', '', 'text-xl font-bold mb-3 mt-5 text-slate-100', 'text-lg font-bold mb-2 mt-4 text-slate-100', 'text-base font-bold mb-2 mt-3 text-slate-200', 'text-sm font-bold mb-1 mt-2 text-slate-200', 'text-xs font-bold mb-1 mt-2 text-slate-300'];
                                blocks.push(`<h${level} class="${classes[level] || ''}">${this.inlineFormat(hText)}</h${level}>`);
                            } else if (/^\d+\.\s/.test(line)) {
                                flushParagraph(); flushTable();
                                listItems.push(line.replace(/^\d+\.\s/, ''));
                            } else if (/^[-*]\s/.test(line)) {
                                flushParagraph(); flushList(); flushTable();
                                blocks.push(`<ul class="list-disc list-inside mb-2 text-slate-200"><li>${this.inlineFormat(line.replace(/^[-*]\s/, ''))}</li></ul>`);
                            } else if (/^>\s/.test(line)) {
                                flushParagraph(); flushList(); flushTable();
                                blocks.push(`<blockquote class="border-l-4 border-[#3894dc]/50 pl-4 py-1 text-slate-400 italic my-2 bg-[#3894dc]/5 rounded-r-xl">${this.inlineFormat(line.replace(/^>\s/, ''))}</blockquote>`);
                            } else if (/^---+$/.test(line)) {
                                flushParagraph(); flushList(); flushTable();
                                blocks.push('<hr class="border-white/10 my-4">');
                            } else if (line === '') {
                                flushParagraph(); flushList(); flushTable();
                            } else {
                                listItems.length ? listItems.push(line) : paragraph.push(line);
                            }
                        }
                        flushParagraph(); flushList(); flushTable(); flushCodeBlock();
                        return blocks.join('');
                    },

                    renderMarkdownTable(lines) {
                        const rows = lines
                            .filter(line => line !== '')
                            .map(line => line.replace(/^\|/, '').replace(/\|$/, '').split('|').map(cell => cell.trim()));

                        if (rows.length < 2) {
                            return `<pre>${lines.join('\n')}</pre>`;
                        }

                        const separatorIndex = rows.findIndex(row => row.every(cell => /^:?-{3,}:?$/.test(cell)));
                        if (separatorIndex !== 1) {
                            return `<pre>${lines.join('\n')}</pre>`;
                        }

                        const header = rows[0].map(h => {
                            let cleanH = h;
                            if (cleanH.toLowerCase() === 'frequency') cleanH = 'Frequency';
                            if (cleanH.toLowerCase() === 'percentage' || cleanH.toLowerCase() === 'percent') cleanH = 'Percentage (%)';
                            return cleanH;
                        });
                        const body = [...rows.slice(2)];

                        // Auto-check if Total row is present; if not, calculate and append
                        /*const hasTotalRow = body.some(row => row[0] && row[0].toLowerCase().includes('total'));
                        if (!hasTotalRow && body.length > 0) {
                            const totalRow = [];
                            header.forEach((colName, colIdx) => {
                                if (colIdx === 0) {
                                    totalRow.push('Total');
                                } else {
                                    let sum = 0;
                                    let isPercent = colName.includes('%') || colName.toLowerCase().includes('percentage');
                                    let isCount = colName.toLowerCase().includes('freq') || colName.toLowerCase().includes('(n)') || colName.toLowerCase().includes('count');

                                    body.forEach(r => {
                                        const valStr = (r[colIdx] || '').replace(/[^0-9.]/g, '');
                                        const val = parseFloat(valStr);
                                        if (!isNaN(val)) sum += val;
                                    });

                                    if (isPercent) {
                                        totalRow.push('100%');
                                    } else if (isCount) {
                                        totalRow.push(`${Math.round(sum)}`);
                                    } else {
                                        totalRow.push(sum > 0 ? (sum % 1 === 0 ? sum.toFixed(0) : sum.toFixed(1)) : '-');
                                    }
                                }
                            });
                            body.push(totalRow);
                        }*/

                        const tableId = `socius-table-${Math.random().toString(36).slice(2, 10)}`;

                        return `
                                                                    <div class="my-4 rounded-2xl border border-white/10 overflow-hidden bg-[#1e1e2d]/60 shadow-xl">
                                                                        <div class="flex items-center justify-between gap-3 px-4 py-2.5 bg-white/[0.05] border-b border-white/10">
                                                                            <button type="button" onclick="window.copyRenderedSociusTable('${tableId}', this)" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white/10 border border-white/10 text-[10px] font-bold text-slate-300 hover:bg-[#2271b1] hover:text-white transition-all">
                                                                                <i class="fa-regular fa-copy text-[10px]"></i>
                                                                                ${@js(__('Copy Table'))}
                                                                            </button>
                                                                        </div>
                                                                        <div class="overflow-x-auto">
                                                                            <table id="${tableId}" class="min-w-full text-left text-xs border-collapse">
                                                                                <thead>
                                                                                    <tr class="bg-white/[0.04] border-b border-white/10">
                                                                                        ${header.map(cell => `<th class="px-4 py-3 text-[11px] font-bold text-blue-300 border-b border-white/10 bg-white/[0.03]">${this.inlineFormat(cell)}</th>`).join('')}
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                    ${body.map((row, rIdx) => {
                            const isTotal = row[0] && row[0].toLowerCase().includes('total');
                            const rowBg = isTotal ? 'bg-white/[0.08] font-bold text-blue-200' : (rIdx % 2 === 0 ? 'bg-transparent' : 'bg-white/[0.02]');
                            return `
                                                                                            <tr class="${rowBg}">
                                                                                                ${row.map(cell => `<td class="px-4 py-2.5 border-b border-white/5 ${isTotal ? 'font-bold text-blue-200 border-t border-white/10' : 'text-slate-200'}">${this.inlineFormat(cell)}</td>`).join('')}
                                                                                            </tr>
                                                                                        `;
                        }).join('')}
                                                                                </tbody>
                                                                            </table>
                                                                        </div>
                                                                    </div>
                                                                `;
                    },

                    inlineFormat(text) {
                        return text
                            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                            .replace(/\*(.+?)\*/g, '<em>$1</em>')
                            .replace(/`(.+?)`/g, '<code class="bg-white/10 px-1.5 py-0.5 rounded text-[11px] font-mono">$1</code>')
                            .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" class="text-[#3894dc] hover:underline">$1</a>');
                    },

                    copyMessage(content, messageId, btn = null) {
                        const element = document.getElementById(`socius-message-body-${messageId}`);
                        if (element) {
                            const clone = element.cloneNode(true);
                            const controls = clone.querySelectorAll('.visual-header button, .socius-visual-loading, script, style, textarea.visual-source');
                            controls.forEach(el => el.remove());
                            const visuals = clone.querySelectorAll('.socius-visual');
                            visuals.forEach(visual => {
                                const titleEl = visual.querySelector('.visual-header span');
                                const title = titleEl ? titleEl.innerText : 'chart';
                                const replacement = document.createElement('p');
                                replacement.style.fontWeight = 'bold';
                                replacement.style.color = '#3f3f46';
                                replacement.style.fontStyle = 'italic';
                                replacement.innerText = `[${title} — chart not available in this format]`;
                                visual.parentNode.replaceChild(replacement, visual);
                            });
                            const tables = clone.querySelectorAll('table');
                            tables.forEach(table => {
                                table.style.width = '100%'; table.style.borderCollapse = 'collapse'; table.style.margin = '12px 0';
                                table.querySelectorAll('th, td').forEach(cell => {
                                    cell.style.border = '1px solid #d4d4d8'; cell.style.padding = '8px 12px'; cell.style.textAlign = 'left';
                                });
                                table.querySelectorAll('th').forEach(th => { th.style.backgroundColor = '#f4f4f5'; th.style.fontWeight = 'bold'; });
                            });
                            const rawHtml = clone.innerHTML;
                            const rawText = clone.innerText || clone.textContent;
                            const blobHtml = new Blob([rawHtml], { type: 'text/html' });
                            const blobText = new Blob([rawText], { type: 'text/plain' });
                            navigator.clipboard.write([new ClipboardItem({ 'text/html': blobHtml, 'text/plain': blobText })]).then(() => {
                                if (btn) { const orig = btn.innerHTML; btn.innerHTML = '<i class="fa-solid fa-check text-green-400"></i>'; setTimeout(() => { btn.innerHTML = orig; }, 2000); }
                            }).catch(() => navigator.clipboard.writeText(rawText));
                        } else {
                            navigator.clipboard.writeText(content);
                        }
                    },

                    toggleVoiceInput() {
                        if (this.isListening) { if (this.recognition) this.recognition.stop(); return; }
                        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                        if (!SpeechRecognition) { this.error = @js(__('Your browser does not support voice input.')); return; }
                        if (!this.recognition) {
                            this.recognition = new SpeechRecognition();
                            this.recognition.continuous = true; this.recognition.interimResults = true;
                            this.recognition.lang = document.documentElement.lang || 'en-US';
                            this.recognition.onresult = (event) => {
                                let transcript = '';
                                for (let i = event.resultIndex; i < event.results.length; i++) transcript += event.results[i][0].transcript;
                                this.draft = transcript;
                            };
                            this.recognition.onend = () => { this.isListening = false; };
                            this.recognition.onerror = (event) => { if (event.error !== 'no-speech') this.error = @js(__('Voice recognition error: ')) + event.error; this.isListening = false; };
                        }
                        this.error = null;
                        try { this.recognition.start(); this.isListening = true; } catch (e) { this.isListening = false; }
                    },

                    async renderVisuals() {
                        const visuals = document.querySelectorAll('.socius-visual:not(.rendered)');
                        if (visuals.length === 0) return;
                        if (typeof mermaid !== 'undefined') {
                            try { mermaid.initialize({ startOnLoad: false, theme: 'dark', securityLevel: 'loose', fontFamily: 'Inter', suppressErrorIndicators: true, logLevel: 4 }); } catch (e) { }
                        }
                        for (const el of Array.from(visuals)) {
                            const type = el.dataset.visualType;
                            const id = el.dataset.visualId;
                            const sourceEl = el.querySelector('.visual-source');
                            if (!sourceEl) continue;
                            let source = (sourceEl.value || sourceEl.textContent).trim();
                            const target = el.querySelector('.visual-target');
                            if (!target) continue;
                            try {
                                if (type === 'mermaid' && typeof mermaid !== 'undefined') {
                                    if (!source.match(/^(graph|sequenceDiagram|gantt|classDiagram|stateDiagram|erDiagram|journey|pie|quadrantChart|xychart-beta|mindmap|timeline)/i)) source = 'graph TD\n' + source;
                                    const { svg } = await mermaid.render('svg-' + id, source);
                                    target.innerHTML = svg;
                                    el.classList.add('rendered');
                                } else if (type === 'chartjs' && typeof Chart !== 'undefined') {
                                    const repairedSource = this.repairJson(source);
                                    const config = JSON.parse(repairedSource);
                                    const canvas = document.createElement('canvas');
                                    target.innerHTML = '';
                                    target.appendChild(canvas);
                                    if (config.data && Array.isArray(config.data.datasets) && config.data.datasets[0]) {
                                        const dataset = config.data.datasets[0];
                                        const rawData = Array.isArray(dataset.data) ? dataset.data : [];
                                        const total = rawData.reduce((s, v) => s + Number(v || 0), 0);
                                        dataset.data = rawData.map(v => total > 0 ? parseFloat(((Number(v || 0) / total) * 100).toFixed(1)) : parseFloat(String(v || 0).replace('%', '')) || 0);
                                        if (!dataset.label) dataset.label = 'Percentage (%)';
                                        if (!dataset.backgroundColor) dataset.backgroundColor = ['#2271b1', '#3894dc', '#6366f1', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6'];
                                        if (!config.options) config.options = {};
                                        if (!config.options.plugins) config.options.plugins = {};
                                        config.options.plugins.legend = { display: false };
                                        const chartType = config.type || 'bar';
                                        const isCartesian = ['bar', 'line'].includes(chartType);
                                        if (isCartesian) {
                                            const isHorizontal = config.options.indexAxis === 'y';
                                            const valAxis = { beginAtZero: true, grace: '12%', grid: { color: 'rgba(255,255,255,0.08)' }, ticks: { font: { size: 10 }, callback: v => v + '%' }, title: { display: true, text: 'Percentage (%)', color: '#94a3b8', font: { size: 10 } } };
                                            if (config.data && config.data.labels) config.data.labels = config.data.labels.map(l => wrapLabel(l, 18));
                                            const labelAxis = { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 45, autoSkip: true } };
                                            config.options.scales = { y: isHorizontal ? labelAxis : valAxis, x: isHorizontal ? valAxis : labelAxis };
                                        } else { if (config.options) delete config.options.scales; }
                                    }
                                    if (!config.options) config.options = {};
                                    config.options.responsive = true; config.options.maintainAspectRatio = false;
                                    new Chart(canvas, config);
                                    canvas.style.maxHeight = '400px';
                                    el.classList.add('rendered');
                                }
                            } catch (e) {
                                console.error(`Socius Visual Error [${type}]:`, e);
                                target.innerHTML = `<div class="text-red-400/60 text-[10px] font-bold p-4 bg-red-500/10 rounded-xl border border-red-500/20"><i class="fa-solid fa-triangle-exclamation mr-1"></i> {{ __('Invalid visual syntax.') }}</div>`;
                                el.classList.add('rendered');
                            }
                        }
                    },

                    repairJson(str) {
                        let cleaned = str;
                        cleaned = cleaned.replace(/^```(json)?\n?/i, '').replace(/```$/i, '').trim();
                        cleaned = cleaned.replace(/\/\*[\s\S]*?\*\/|([^\\:]|^)\/\/.*$/gm, '$1').replace(/,\s*([}\]])/g, '$1');
                        return cleaned.trim();
                    },

                    async loadKbRules() {
                        this.loadingKb = true;
                        try {
                            const response = await fetch(this.urls.kbList, { headers: { 'Accept': 'application/json' } });
                            const data = await this.parseJsonResponse(response);
                            this.kbRules = data.rules || [];
                        } catch (e) { console.error('Failed to load KB rules', e); } finally { this.loadingKb = false; }
                    },

                    async addKbRule() {
                        if (!this.newKbRuleContent || !this.newKbRuleContent.trim()) return;
                        this.savingKb = true;
                        try {
                            const response = await fetch(this.urls.kbStore, {
                                method: 'POST',
                                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                                body: JSON.stringify({ content: this.newKbRuleContent.trim() })
                            });
                            const data = await this.parseJsonResponse(response);
                            if (response.ok) {
                                this.newKbRuleContent = '';
                                await this.loadKbRules();
                                Swal.fire({ title: @js(__('Memory Updated!')), text: data.message || @js(__('Instruction added.')), icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 3500, timerProgressBar: true, customClass: { popup: 'rounded-2xl shadow-xl border-none' } });
                            }
                        } catch (e) { Swal.fire({ title: @js(__('Error')), text: e.message, icon: 'error', customClass: { popup: 'rounded-3xl border-none shadow-2xl' } }); } finally { this.savingKb = false; }
                    },

                    uploadingKbDoc: false,
                    async uploadKbDocument(event) {
                        const file = event.target.files[0];
                        if (!file) return;
                        this.uploadingKbDoc = true;
                        const formData = new FormData();
                        formData.append('document', file);
                        try {
                            const response = await fetch('/socius/knowledge-base/upload', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'Accept': 'application/json'
                                },
                                body: formData
                            });
                            const data = await this.parseJsonResponse(response);
                            if (!response.ok) throw new Error(data.message || @js(__('Upload failed')));
                            await this.loadKbRules();
                            Swal.fire({ title: @js(__('Knowledge Integrated!')), text: data.message || @js(__('Document loaded into Knowledge Base.')), icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 4500, timerProgressBar: true, customClass: { popup: 'rounded-2xl shadow-xl border-none' } });
                        } catch (e) {
                            Swal.fire({ title: @js(__('Upload Error')), text: e.message, icon: 'error', customClass: { popup: 'rounded-3xl border-none shadow-2xl' } });
                        } finally {
                            this.uploadingKbDoc = false;
                            event.target.value = '';
                        }
                    },

                    async toggleKbRule(rule) {
                        const originalState = rule.is_active;
                        rule.is_active = !originalState;
                        try {
                            const url = this.urls.kbUpdateTemplate.replace('__KB__', rule.id);
                            const response = await fetch(url, {
                                method: 'PATCH',
                                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                                body: JSON.stringify({ is_active: rule.is_active })
                            });
                            const data = await this.parseJsonResponse(response);
                            if (data.rule) rule.is_active = data.rule.is_active;
                        } catch (e) { rule.is_active = originalState; Swal.fire({ title: @js(__('Error')), text: e.message, icon: 'error', customClass: { popup: 'rounded-3xl border-none shadow-2xl' } }); }
                    },

                    async deleteKbRule(ruleId) {
                        const result = await Swal.fire({
                            title: @js(__('Delete Preference?')),
                            text: @js(__('This preference will no longer apply to future answers.')),
                            icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280',
                            confirmButtonText: @js(__('Yes, Delete It')), cancelButtonText: @js(__('Cancel')),
                            customClass: { popup: 'rounded-3xl border-none shadow-2xl' }
                        });
                        if (!result.isConfirmed) return;
                        try {
                            const url = this.urls.kbDestroyTemplate.replace('__KB__', ruleId);
                            const response = await fetch(url, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') } });
                            const data = await this.parseJsonResponse(response);
                            this.kbRules = this.kbRules.filter(r => r.id !== ruleId);
                            Swal.fire({ title: @js(__('Deleted!')), text: data.message || @js(__('Preference removed.')), icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, customClass: { popup: 'rounded-2xl shadow-xl border-none' } });
                        } catch (e) { Swal.fire({ title: @js(__('Error')), text: e.message, icon: 'error', customClass: { popup: 'rounded-3xl border-none shadow-2xl' } }); }
                    },
                    async deactivateAllKbRules() {
                        try {
                            const response = await fetch('/socius/knowledge-base/deactivate-all', {
                                method: 'POST',
                                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                            });
                            const data = await this.parseJsonResponse(response);
                            this.kbRules.forEach(r => r.is_active = false);
                            Swal.fire({ title: @js(__('All Deactivated')), text: data.message, icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
                        } catch (e) { Swal.fire({ title: @js(__('Error')), text: e.message, icon: 'error', customClass: { popup: 'rounded-3xl border-none shadow-2xl' } }); }
                    },
                    async deleteAllKbRules() {
                        const result = await Swal.fire({
                            title: @js(__('Delete ALL Instructions?')),
                            text: @js(__('CRITICAL: This will permanently remove all Knowledge Base instructions and uploaded documents.')),
                            icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280',
                            confirmButtonText: @js(__('Yes, Delete All')), cancelButtonText: @js(__('Cancel')),
                            customClass: { popup: 'rounded-3xl border-none shadow-2xl' }
                        });
                        if (!result.isConfirmed) return;
                        try {
                            const response = await fetch('/socius/knowledge-base/delete-all', {
                                method: 'DELETE',
                                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                            });
                            const data = await this.parseJsonResponse(response);
                            this.kbRules = [];
                            Swal.fire({ title: @js(__('All Deleted!')), text: data.message, icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
                        } catch (e) { Swal.fire({ title: @js(__('Error')), text: e.message, icon: 'error', customClass: { popup: 'rounded-3xl border-none shadow-2xl' } }); }
                    },
                    formatDocName(content) {
                        if (!content) return 'Uploaded Document';
                        const match = content.match(/^\[(?:Book\/Doc|Doc):\s*(.*?)\]/i);
                        return match ? match[1] : 'Uploaded Document';
                    },
                    async saveAsProposal(content) {
                        const { value: title } = await Swal.fire({
                            title: @js(__('Save as Proposal')),
                            input: 'text',
                            inputLabel: @js(__('Enter a title for this Research Proposal:')),
                            inputValue: @js(__('Socius Research Proposal')),
                            showCancelButton: true,
                            confirmButtonColor: '#2271b1',
                            confirmButtonText: @js(__('Save to Library')),
                            inputValidator: (value) => {
                                if (!value) {
                                    return @js(__('Please enter a title!'));
                                }
                            },
                            customClass: { popup: 'rounded-3xl border-none shadow-2xl' }
                        });

                        if (!title) return;

                        try {
                            const response = await fetch('/research-proposal/save-from-socius', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({ title: title, content: content })
                            });
                            const data = await this.parseJsonResponse(response);
                            if (response.ok && data.success) {
                                Swal.fire({
                                    title: @js(__('Chat Saved!')),
                                    text: @js(__('Your chat has been saved to your Socius Library.')),
                                    icon: 'success',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 3500,
                                    customClass: { popup: 'rounded-2xl shadow-xl border-none' }
                                });
                            } else {
                                throw new Error(data.message || 'Error saving proposal');
                            }
                        } catch (e) {
                            Swal.fire({ title: @js(__('Save Error')), text: e.message, icon: 'error', customClass: { popup: 'rounded-3xl border-none shadow-2xl' } });
                        }
                    },
                    humanizeMessage(content) {
                        if (!content) return;
                        window.location.href = "{{ route('humanizer.index') }}?text=" + encodeURIComponent(content);
                    },
                    promptSuggestedSection(promptText) {
                        this.draft = promptText;
                        this.sendMessage();
                    },
                    async rateMessage(messageId, rating) {
                        try {
                            const response = await fetch(`/socius/chat/messages/${messageId}/rate`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({ rating: rating })
                            });
                            const data = await response.json();
                            if (data.success) {
                                const msg = this.messages.find(m => m.id === messageId);
                                if (msg) {
                                    if (!msg.metadata) msg.metadata = {};
                                    msg.metadata.rating = rating;
                                }
                                Swal.fire({
                                    title: @js(__('Feedback Logged')),
                                    text: data.message,
                                    icon: 'success',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    customClass: { popup: 'rounded-2xl shadow-xl border-none' }
                                });
                            }
                        } catch (e) {
                            console.error('Rate message failed', e);
                        }
                    }
                };
            };
        </script>
    @endpush