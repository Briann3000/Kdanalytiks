<div x-show="reportTab === 'analyse'" x-data="sociusManager({
        canAnalyze: @js($canAnalyze),
        initialThreadId: @js((int) request('thread')),
        urls: {
            list: @js(route('surveys.analyse.threads.index', $survey)),
            create: @js(route('surveys.analyse.threads.store', $survey)),
            showTemplate: @js(route('surveys.analyse.threads.show', [$survey, '__THREAD__'])),
            streamTemplate: @js(route('surveys.analyse.threads.stream', [$survey, '__THREAD__'])),
            updateTemplate: @js(route('surveys.analyse.threads.update', [$survey, '__THREAD__'])),
            pin_toggleTemplate: @js(route('surveys.analyse.threads.pin_toggle', [$survey, '__THREAD__'])),
            destroyTemplate: @js(route('surveys.analyse.threads.destroy', [$survey, '__THREAD__'])),
            kbList: @js(route('socius.knowledge-base.index')),
            kbStore: @js(route('socius.knowledge-base.store')),
            kbUpdateTemplate: @js(route('socius.knowledge-base.update', ['knowledgeBase' => '__KB__'])),
            kbDestroyTemplate: @js(route('socius.knowledge-base.destroy', ['knowledgeBase' => '__KB__']))
        }
    })" x-init="init()" class="animate-in fade-in duration-500" style="display: none;">
    <div class="relative flex gap-4 h-[calc(100vh-14rem)] min-h-[600px] overflow-hidden">
        {{-- Sidebar History --}}
        <aside :class="historyOpen ? 'w-64 md:w-72 opacity-100' : 'w-0 opacity-0 -ml-4'"
            class="bg-[#2b2b2b] text-white rounded-[2rem] border border-white/5 overflow-hidden flex flex-col h-full transition-all duration-300 ease-in-out flex-shrink-0">
            <div class="px-4 py-3 border-b border-white/10 flex items-center justify-between gap-2">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                    {{ __('Conversation History') }}
                </p>
                <button @click="createThread()" :disabled="creatingThread || !canAnalyze"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-orange-400 text-slate-950 text-[9px] font-black uppercase tracking-widest disabled:opacity-40 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-plus text-[9px]" :class="{ 'fa-spin': creatingThread }"></i>
                    {{ __('New') }}
                </button>

                <template x-if="tokenUsage">
                    <div
                        class="px-2.5 py-1.5 rounded-xl bg-white/5 border border-white/10 text-[9px] font-bold text-slate-400 uppercase tracking-tighter">
                        <i class="fa-solid fa-bolt-lightning text-orange-300 mr-1"></i>
                        <span x-text="`${tokenUsage.total_tokens} {{ __('tokens') }}`"></span>
                    </div>
                </template>
            </div>

            <div class="p-4 space-y-3 flex-1 overflow-y-auto custom-scrollbar">
                <template x-if="loadingThreads">
                    <div class="space-y-3">
                        <div class="h-20 rounded-3xl bg-white/5 animate-pulse"></div>
                        <div class="h-20 rounded-3xl bg-white/5 animate-pulse"></div>
                    </div>
                </template>

                <template x-if="!loadingThreads && threads.length === 0">
                    <div class="rounded-3xl border border-dashed border-white/15 bg-white/[0.03] p-6 text-center">
                        <div
                            class="w-12 h-12 rounded-2xl bg-white/10 mx-auto mb-4 flex items-center justify-center text-orange-300">
                            <i class="fa-solid fa-comments"></i>
                        </div>
                        <p class="text-sm font-semibold">{{ __('No Socius threads yet') }}</p>
                        <p class="text-xs text-slate-400 mt-2">
                            {{ __('Start a focused analysis conversation for this survey.') }}
                        </p>
                    </div>
                </template>

                <template x-for="thread in threads" :key="thread.id">
                    <div class="relative group/thread"
                        @click.outside="threadMenuOpen === thread.id && (threadMenuOpen = null)">

                        {{-- Rename inline editor --}}
                        <template x-if="renamingThreadId === thread.id">
                            <div class="rounded-2xl bg-white/[0.06] border border-white/15 px-3 py-2">
                                <input type="text" x-model="editingTitle"
                                    @keydown.enter="renameThread(thread.id, editingTitle)"
                                    @keydown.escape="renamingThreadId = null" @click.stop
                                    class="w-full rounded-lg bg-white/10 border border-white/20 px-2 py-1.5 text-xs text-white focus:outline-none focus:border-orange-400"
                                    x-init="$nextTick(() => $el.focus())">
                                <div class="flex gap-1.5 mt-2">
                                    <button @click.stop="renameThread(thread.id, editingTitle)"
                                        class="flex-1 text-[9px] px-2 py-1 rounded-lg bg-orange-400 text-slate-900 font-black">{{ __('Save') }}</button>
                                    <button @click.stop="renamingThreadId = null"
                                        class="flex-1 text-[9px] px-2 py-1 rounded-lg bg-white/10 text-white">{{ __('Cancel') }}</button>
                                </div>
                            </div>
                        </template>

                        {{-- Normal thread item --}}
                        <template x-if="renamingThreadId !== thread.id">
                            <div class="relative">
                                {{-- Main clickable area --}}
                                <button @click="selectThread(thread.id)"
                                    class="w-full text-left rounded-2xl px-3 py-3 pr-9 border transition-all" :class="currentThreadId === thread.id
                                        ? 'bg-white text-slate-900 border-white shadow-xl shadow-black/20'
                                        : 'bg-white/[0.04] border-white/10 hover:bg-white/[0.08] text-white'">
                                    <div class="flex items-center gap-1.5 overflow-hidden">
                                        <template x-if="thread.is_pinned">
                                            <i
                                                class="fa-solid fa-thumbtack text-[9px] text-orange-400 rotate-45 flex-shrink-0"></i>
                                        </template>
                                        <p class="text-xs font-semibold truncate leading-snug" x-text="thread.title">
                                        </p>
                                    </div>
                                    <p class="mt-1 text-[10px] opacity-60 line-clamp-1"
                                        x-text="thread.latest_message_preview || '{{ __('Fresh thread') }}'"></p>
                                    <p class="mt-1.5 text-[9px] font-bold uppercase tracking-widest opacity-40"
                                        x-text="formatRelativeTime(thread.last_activity_at)"></p>
                                </button>

                                {{-- 3-dot button — visible on hover --}}
                                <button @click.stop="threadMenuOpen = (threadMenuOpen === thread.id ? null : thread.id)"
                                    class="absolute right-2 top-3 w-6 h-6 rounded-lg flex items-center justify-center transition-all
                                           opacity-0 group-hover/thread:opacity-100 focus:opacity-100" :class="[
                                        threadMenuOpen === thread.id ? 'opacity-100' : '',
                                        currentThreadId === thread.id
                                            ? 'hover:bg-slate-200 text-slate-600'
                                            : 'hover:bg-white/15 text-slate-400'
                                    ]" title="{{ __('Options') }}">
                                    <i class="fa-solid fa-ellipsis-vertical text-[11px]"></i>
                                </button>

                                {{-- Dropdown menu --}}
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
                                        <i class="fa-solid fa-thumbtack text-[10px] text-slate-400 w-3"
                                            :class="thread.is_pinned ? 'text-orange-400' : ''"></i>
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

        <section
            class="flex-1 bg-[#252525] text-white rounded-[2rem] border border-white/5 shadow-2xl overflow-hidden flex flex-col h-full relative min-w-0">

            {{-- Toggle Button when Sidebar is hidden --}}
            <template x-if="!historyOpen">
                <div class="absolute left-0 top-1/2 -translate-y-1/2 z-50">
                    <button @click="historyOpen = true"
                        class="p-2 rounded-r-xl bg-[#2b2b2b] border border-l-0 border-white/10 text-slate-400 hover:text-white transition-all shadow-xl">
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </button>
                </div>
            </template>

            <div class="px-5 py-3 border-b border-white/10 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button @click="historyOpen = !historyOpen"
                        class="p-2 rounded-xl bg-white/5 border border-white/10 text-slate-400 hover:text-white transition-all">
                        <i class="fa-solid fa-bars-staggered"></i>
                    </button>
                    <h3 class="text-base font-semibold tracking-tight truncate max-w-[200px] md:max-w-md"
                        x-text="currentThread ? currentThread.title : '{{ __('Socius') }}'"></h3>
                </div>

                <div class="flex items-center gap-2">
                    {{-- Knowledge Base Button --}}
                    <button @click="kbModalOpen = true"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/10 border border-white/10 text-xs font-bold text-slate-300 hover:text-white transition-all"
                        title="{{ __('Manage persistent formatting rules and styles') }}">
                        <i class="fa-solid fa-brain text-[10px] text-orange-300"></i>
                        {{ __('Knowledge Base') }}
                    </button>

                    {{-- Export Dropdown --}}
                    <template x-if="currentThreadId">
                        <div class="relative" x-data="{ exportMenuOpen: false }" @click.away="exportMenuOpen = false">
                            <button @click="exportMenuOpen = !exportMenuOpen"
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/10 border border-white/10 text-xs font-bold text-slate-300 hover:text-white transition-all">
                                <i class="fa-solid fa-download text-[10px]"></i>
                                {{ __('Export') }}
                                <i class="fa-solid fa-chevron-down text-[10px] opacity-50"></i>
                            </button>

                            <div x-show="exportMenuOpen" x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                class="absolute right-0 mt-2 w-48 rounded-2xl bg-[#363636] border border-white/10 shadow-2xl z-50 overflow-hidden py-1">
                                <a :href="`{{ route('surveys.analyse.threads.export', [$survey, ':thread']) }}`.replace(':thread', currentThreadId) + '?format=pdf'"
                                    class="flex items-center gap-3 px-4 py-2.5 text-xs text-slate-300 hover:bg-white/10 hover:text-white transition-colors">
                                    <i class="fa-solid fa-file-pdf text-red-400"></i> {{ __('PDF Report') }}
                                </a>
                                <a :href="`{{ route('surveys.analyse.threads.export', [$survey, ':thread']) }}`.replace(':thread', currentThreadId) + '?format=docx'"
                                    class="flex items-center gap-3 px-4 py-2.5 text-xs text-slate-300 hover:bg-white/10 hover:text-white transition-colors">
                                    <i class="fa-solid fa-file-word text-blue-400"></i> {{ __('Word Document') }}
                                </a>
                                <a :href="`{{ route('surveys.analyse.threads.export', [$survey, ':thread']) }}`.replace(':thread', currentThreadId) + '?format=excel'"
                                    class="flex items-center gap-3 px-4 py-2.5 text-xs text-slate-300 hover:bg-white/10 hover:text-white transition-colors">
                                    <i class="fa-solid fa-file-excel text-green-400"></i> {{ __('Excel Spreadsheet') }}
                                </a>
                                <a :href="`{{ route('surveys.analyse.threads.export', [$survey, ':thread']) }}`.replace(':thread', currentThreadId) + '?format=md'"
                                    class="flex items-center gap-3 px-4 py-2.5 text-xs text-slate-300 hover:bg-white/10 hover:text-white transition-colors">
                                    <i class="fa-solid fa-file-code text-slate-400"></i> {{ __('Markdown Text') }}
                                </a>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar px-4 md:px-8 py-6 space-y-6" x-ref="messageList">
                <template x-if="!canAnalyze">
                    <div
                        class="max-w-2xl mx-auto rounded-[2rem] border border-amber-400/20 bg-amber-400/10 p-8 text-center">
                        <div
                            class="w-14 h-14 rounded-2xl bg-amber-300/20 text-amber-200 mx-auto mb-4 flex items-center justify-center">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <h4 class="text-xl font-black">{{ __('Socius is currently locked for this account') }}</h4>
                        <p class="text-sm text-amber-100/80 mt-3">
                            {{ __('Your current AI allocation has been reached. Upgrade your plan or wait for access to reset before continuing with chat analysis.') }}
                        </p>
                        <a href="{{ route('subscriptions.index') }}"
                            class="inline-flex items-center gap-2 mt-6 px-5 py-3 rounded-2xl bg-white text-slate-900 text-[10px] font-black uppercase tracking-widest">
                            {{ __('View Plans') }}
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </template>

                <template x-if="canAnalyze && !currentThreadId && threads.length === 0 && !loadingThreads">
                    <div class="max-w-3xl mx-auto pt-10">
                        <div class="text-center mb-8">
                            <div
                                class="w-20 h-20 rounded-[2rem] bg-orange-400/15 border border-orange-300/20 mx-auto flex items-center justify-center text-orange-300">
                                <i class="fa-solid fa-sparkles text-3xl"></i>
                            </div>
                            <h4 class="text-4xl font-semibold tracking-tight mt-6">{{ __('Socius') }}</h4>
                            <p class="text-slate-300 mt-4 max-w-2xl mx-auto">
                                {{ __('Start a new thread to analyze survey findings, uploaded documents, or both. Socius will use the current survey context automatically when the toggle is enabled.') }}
                            </p>
                        </div>
                        <div class="rounded-[2rem] border border-white/10 bg-white/[0.04] p-8">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-left">
                                <div class="rounded-3xl bg-white/[0.04] p-5 border border-white/10">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-orange-200">
                                        {{ __('Quantitative') }}
                                    </p>
                                    <p class="text-sm text-slate-200 mt-3">
                                        {{ __('Interpret trends, frequencies, and cross-tab patterns directly from the live report.') }}
                                    </p>
                                </div>
                                <div class="rounded-3xl bg-white/[0.04] p-5 border border-white/10">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-orange-200">
                                        {{ __('Qualitative') }}
                                    </p>
                                    <p class="text-sm text-slate-200 mt-3">
                                        {{ __('Synthesize open-ended responses into themes, insights, and APA-ready narrative.') }}
                                    </p>
                                </div>
                                <div class="rounded-3xl bg-white/[0.04] p-5 border border-white/10">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-orange-200">
                                        {{ __('Documents') }}
                                    </p>
                                    <p class="text-sm text-slate-200 mt-3">
                                        {{ __('Attach PDFs, CSVs, TXT files, or DOCX notes to combine external evidence with survey data.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="currentThreadId && messages.length === 0 && !loadingMessages">
                    <div
                        class="max-w-2xl mx-auto rounded-[2rem] border border-dashed border-white/15 bg-white/[0.03] p-8 text-center">
                        <p class="text-lg font-semibold">{{ __('This thread is ready for analysis') }}</p>
                        <p class="text-sm text-slate-300 mt-3">
                            {{ __('Send a prompt below and Socius will stream the response here in real time.') }}
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
                    <div class="max-w-4xl mx-auto group/msg"
                        :class="message.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                        <div class="relative w-full md:w-auto md:max-w-[80%] rounded-[2rem] px-5 py-4 border" :class="message.role === 'user'
                                ? 'bg-orange-400 text-slate-950 border-orange-300 shadow-lg shadow-orange-500/10'
                                : 'bg-white/[0.04] text-white border-white/10'">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-9 h-9 rounded-2xl flex items-center justify-center text-sm"
                                    :class="message.role === 'user' ? 'bg-white/60' : 'bg-white/10 text-orange-200'">
                                    <i class="fa-solid"
                                        :class="message.role === 'user' ? 'fa-user' : 'fa-sparkles'"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-[10px] font-black uppercase tracking-[0.25em]"
                                        x-text="message.role === 'user' ? '{{ __('User') }}' : '{{ __('Socius') }}'">
                                    </p>
                                    <p class="text-[11px] opacity-70" x-text="formatRelativeTime(message.created_at)">
                                    </p>
                                </div>
                                {{-- Message Actions --}}
                                <div x-data="{ localExportOpen: false }" @click.outside="localExportOpen = false"
                                    class="flex items-center gap-1 transition-opacity relative"
                                    :class="localExportOpen ? 'opacity-100' : 'opacity-0 group-hover/msg:opacity-100'">
                                    <template x-if="message.role === 'user'">
                                        <button @click="startEditing(message.id, message.content)"
                                            class="w-7 h-7 rounded-xl flex items-center justify-center transition-all"
                                            :class="message.role === 'user' ? 'hover:bg-black/10 text-slate-700' : 'hover:bg-white/10 text-slate-400'"
                                            title="{{ __('Edit') }}">
                                            <i class="fa-regular fa-pen-to-square text-xs"></i>
                                        </button>
                                    </template>
                                    <template x-if="message.role === 'assistant' && index === messages.length - 1">
                                        <button @click="regenerateResponse(message.id)"
                                            class="w-7 h-7 rounded-xl flex items-center justify-center transition-all hover:bg-white/10 text-slate-400"
                                            title="{{ __('Regenerate') }}">
                                            <i class="fa-solid fa-rotate text-xs"></i>
                                        </button>
                                    </template>

                                    {{-- Single Message Export Dropdown --}}
                                    <template
                                        x-if="message.role === 'assistant' && !message.id.toString().startsWith('temp-')">
                                        <div class="relative">
                                            <button @click="localExportOpen = !localExportOpen"
                                                class="w-7 h-7 rounded-xl flex items-center justify-center transition-all hover:bg-white/10 text-slate-400"
                                                title="{{ __('Export Report') }}">
                                                <i class="fa-solid fa-download text-xs"></i>
                                            </button>
                                            <div x-show="localExportOpen"
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-75"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-95"
                                                class="absolute right-0 mt-1 w-44 rounded-2xl bg-[#363636] border border-white/10 shadow-2xl z-50 overflow-hidden py-1"
                                                style="display: none;">
                                                <a :href="`{{ route('surveys.analyse.threads.export', [$survey, ':thread']) }}`.replace(':thread', currentThreadId) + '?format=pdf&message_id=' + message.id"
                                                    class="flex items-center gap-2.5 px-3.5 py-2 text-xs text-slate-300 hover:bg-white/10 hover:text-white transition-colors">
                                                    <i class="fa-solid fa-file-pdf text-red-400 w-4"></i>
                                                    {{ __('PDF Document') }}
                                                </a>
                                                <a :href="`{{ route('surveys.analyse.threads.export', [$survey, ':thread']) }}`.replace(':thread', currentThreadId) + '?format=docx&message_id=' + message.id"
                                                    class="flex items-center gap-2.5 px-3.5 py-2 text-xs text-slate-300 hover:bg-white/10 hover:text-white transition-colors">
                                                    <i class="fa-solid fa-file-word text-blue-400 w-4"></i>
                                                    {{ __('Word Document') }}
                                                </a>
                                                <a :href="`{{ route('surveys.analyse.threads.export', [$survey, ':thread']) }}`.replace(':thread', currentThreadId) + '?format=excel&message_id=' + message.id"
                                                    class="flex items-center gap-2.5 px-3.5 py-2 text-xs text-slate-300 hover:bg-white/10 hover:text-white transition-colors">
                                                    <i class="fa-solid fa-file-excel text-green-400 w-4"></i>
                                                    {{ __('Excel Sheet') }}
                                                </a>
                                                <a :href="`{{ route('surveys.analyse.threads.export', [$survey, ':thread']) }}`.replace(':thread', currentThreadId) + '?format=md&message_id=' + message.id"
                                                    class="flex items-center gap-2.5 px-3.5 py-2 text-xs text-slate-300 hover:bg-white/10 hover:text-white transition-colors">
                                                    <i class="fa-solid fa-file-code text-slate-400 w-4"></i>
                                                    {{ __('Markdown Text') }}
                                                </a>
                                            </div>
                                        </div>
                                    </template>

                                    <button @click="copyMessage(message.content)"
                                        class="w-7 h-7 rounded-xl flex items-center justify-center transition-all"
                                        :class="message.role === 'user' ? 'hover:bg-black/10 text-slate-700' : 'hover:bg-white/10 text-slate-400'"
                                        title="{{ __('Copy') }}">
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
                                                x-text="attachment.excerpt || formatBytes(attachment.size_bytes)"></div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            {{-- Inline Editor --}}
                            <template x-if="editingMessageId === message.id">
                                <div class="mt-2">
                                    <textarea :id="`edit-textarea-${message.id}`" x-model="editingContent"
                                        class="w-full bg-white/20 border-white/30 rounded-xl text-sm text-slate-900 placeholder:text-slate-600 focus:ring-0 resize-none px-3 py-2"
                                        rows="3" @keydown.escape="cancelEditing()"
                                        @keydown.enter.ctrl="submitEdit(message.id)"></textarea>
                                    <div class="flex justify-end gap-2 mt-2">
                                        <button @click="cancelEditing()"
                                            class="text-[9px] font-black uppercase tracking-widest px-3 py-1.5 rounded-lg bg-black/10 text-slate-700 hover:bg-black/20 transition-all">{{ __('Cancel') }}</button>
                                        <button @click="submitEdit(message.id)"
                                            class="text-[9px] font-black uppercase tracking-widest px-3 py-1.5 rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition-all shadow-sm">{{ __('Save & Resend') }}</button>
                                    </div>
                                </div>
                            </template>

                            <template x-if="editingMessageId !== message.id">
                                <div class="text-sm leading-7 socius-prose"
                                    x-html="renderMessage(message.content, message.role)"></div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <div class="px-4 py-3 border-t border-white/10 bg-[#2b2b2b]">
                <input type="file" x-ref="fileInput" class="hidden" multiple
                    accept=".pdf,.csv,.txt,.docx,.jpg,.jpeg,.png,.webp" @change="handleFileSelection">

                <div class="rounded-2xl border border-white/10 bg-[#363636] px-4 py-3">
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

                    <textarea x-model="draft" x-ref="textarea" rows="2"
                        @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); sendMessage(); }"
                        class="w-full bg-transparent border-0 focus:ring-0 resize-none text-sm text-white placeholder:text-slate-500"
                        placeholder="{{ __('Message Socius...') }}" :disabled="sending || !canAnalyze"></textarea>

                    <div class="flex items-center justify-between gap-3 pt-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" @click="pickFiles()" :disabled="sending || !canAnalyze"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-white/[0.06] border border-white/10 text-xs font-semibold text-slate-300 hover:text-white hover:bg-white/10 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                                <i class="fa-solid fa-paperclip text-[10px]"></i>
                                {{ __('Attach') }}
                            </button>

                            <label
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border text-xs font-semibold cursor-pointer transition-all select-none"
                                :class="includeSurveyContext ? 'bg-orange-400/15 border-orange-400/30 text-orange-300' : 'bg-white/[0.06] border-white/10 text-slate-400 hover:text-slate-200'">
                                <input type="checkbox" x-model="includeSurveyContext" class="hidden">
                                <i class="fa-solid fa-database text-[10px]"></i>
                                {{ __('Context') }}
                            </label>

                            <label
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border text-xs font-semibold cursor-pointer transition-all select-none"
                                :class="webSearchEnabled ? 'bg-blue-400/15 border-blue-400/30 text-blue-300' : 'bg-white/[0.06] border-white/10 text-slate-400 hover:text-slate-200'">
                                <input type="checkbox" x-model="webSearchEnabled" class="hidden">
                                <i class="fa-solid fa-globe text-[10px]"></i>
                                {{ __('Search') }}
                            </label>

                            <button type="button"
                                @click="draft = '{{ __('Generate an image of ') }}'; $nextTick(() => $refs.textarea.focus())"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-white/[0.06] border border-white/10 text-xs font-semibold text-slate-400 hover:text-slate-200 hover:bg-white/10 transition-all">
                                <i class="fa-solid fa-wand-magic-sparkles text-[10px]"></i>
                                {{ __('AI Image') }}
                            </button>

                            <button type="button" @click="toggleVoiceInput()"
                                :class="isListening ? 'bg-red-500/20 border-red-500/40 text-red-400' : 'bg-white/[0.06] border-white/10 text-slate-400'"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border text-xs font-semibold hover:text-slate-200 hover:bg-white/10 transition-all">
                                <i class="fa-solid"
                                    :class="isListening ? 'fa-microphone-lines animate-pulse' : 'fa-microphone text-[10px]'"></i>
                                <span x-text="isListening ? '{{ __('Listening...') }}' : '{{ __('Voice') }}'"></span>
                            </button>

                            <template x-if="error">
                                <p class="text-xs text-red-400 max-w-[180px] truncate" x-text="error"></p>
                            </template>
                        </div>

                        <button type="button" @click="sendMessage()"
                            :disabled="sending || !canAnalyze || (!draft.trim() && pendingFiles.length === 0)"
                            class="w-8 h-8 rounded-full bg-white/10 border border-white/10 text-white flex items-center justify-center hover:bg-orange-400 hover:text-slate-950 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="fa-solid text-xs"
                                :class="sending ? 'fa-circle-notch fa-spin' : 'fa-arrow-up'"></i>
                        </button>
                    </div>
                </div>
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
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4" @click.outside="kbModalOpen = false">

            {{-- Modal Header --}}
            <div class="px-6 py-5 border-b border-white/10 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-2xl bg-orange-400/10 flex items-center justify-center text-orange-300">
                        <i class="fa-solid fa-brain text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold tracking-tight">{{ __('Knowledge Base') }}</h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ __('Set custom style and formatting instructions for future answers.') }}</p>
                    </div>
                </div>
                <button @click="kbModalOpen = false"
                    class="w-8 h-8 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-slate-400 hover:text-white transition-all">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-6">
                {{-- Add New Rule Form --}}
                <div class="space-y-3">
                    <h4 class="text-xs font-black uppercase tracking-widest text-slate-400">
                        {{ __('Add Custom Instruction') }}</h4>
                    <div
                        class="rounded-2xl border border-white/10 bg-white/[0.03] p-3 focus-within:border-orange-400/50 transition-all">
                        <textarea x-model="newKbRuleContent" rows="3"
                            class="w-full bg-transparent border-0 focus:ring-0 resize-none text-sm text-white placeholder:text-slate-500"
                            placeholder="{{ __('e.g., Use APA style but with custom modifications like including the author initials in all in-text citations.') }}"
                            :disabled="savingKb"
                            @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); addKbRule(); }"></textarea>
                        <div class="flex justify-end pt-2">
                            <button @click="addKbRule()" :disabled="savingKb || !newKbRuleContent.trim()"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-orange-400 text-slate-950 text-[10px] font-black uppercase tracking-widest disabled:opacity-40 disabled:cursor-not-allowed hover:bg-orange-300 transition-all">
                                <i class="fa-solid fa-plus text-[10px]" :class="{ 'fa-spin': savingKb }"></i>
                                {{ __('Add Instruction') }}
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Rules List --}}
                <div class="space-y-3">
                    <h4 class="text-xs font-black uppercase tracking-widest text-slate-400">
                        {{ __('Active Instructions') }}</h4>

                    <template x-if="loadingKb">
                        <div class="space-y-2">
                            <div class="h-16 rounded-2xl bg-white/[0.03] animate-pulse"></div>
                            <div class="h-16 rounded-2xl bg-white/[0.03] animate-pulse"></div>
                        </div>
                    </template>

                    <template x-if="!loadingKb && kbRules.length === 0">
                        <div
                            class="rounded-[1.5rem] border border-dashed border-white/10 bg-white/[0.01] p-8 text-center">
                            <div
                                class="w-12 h-12 rounded-2xl bg-white/5 mx-auto mb-3 flex items-center justify-center text-slate-500">
                                <i class="fa-solid fa-lightbulb"></i>
                            </div>
                            <p class="text-sm font-semibold text-slate-300">{{ __('No persistent preferences yet') }}
                            </p>
                            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                                {{ __('Add formatting details or stylistic guidelines above. Socius will remember them across all of your surveys.') }}
                            </p>
                        </div>
                    </template>

                    <template x-if="!loadingKb && kbRules.length > 0">
                        <div class="space-y-2.5 flex flex-col">
                            <template x-for="rule in kbRules" :key="rule.id">
                                <div class="group/rule flex items-start justify-between gap-4 p-4 rounded-2xl border border-white/5 transition-all"
                                    :class="rule.is_active ? 'bg-white/[0.03] border-white/10' : 'bg-white/[0.01] border-white/[0.02] opacity-60'">

                                    <div class="flex items-start gap-3 flex-1 min-w-0">
                                        {{-- Toggle --}}
                                        <button @click="toggleKbRule(rule)"
                                            class="mt-0.5 relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                            :class="rule.is_active ? 'bg-orange-400' : 'bg-white/10'">
                                            <span
                                                class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                                :class="rule.is_active ? 'translate-x-4' : 'translate-x-0'"></span>
                                        </button>

                                        {{-- Content --}}
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-slate-100 break-words whitespace-pre-wrap font-medium leading-relaxed"
                                                :class="{ 'line-through text-slate-500': !rule.is_active }"
                                                x-text="rule.content"></p>
                                            <p class="text-[9px] text-slate-500 mt-1 uppercase tracking-wider font-bold"
                                                x-text="formatRelativeTime(rule.created_at)"></p>
                                        </div>
                                    </div>

                                    {{-- Delete --}}
                                    <button @click="deleteKbRule(rule.id)"
                                        class="w-7 h-7 rounded-xl flex items-center justify-center text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-all opacity-0 group-hover/rule:opacity-100 focus:opacity-100"
                                        title="{{ __('Delete Rule') }}">
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-4 border-t border-white/10 bg-[#232323] flex justify-end">
                <button @click="kbModalOpen = false"
                    class="px-5 py-2.5 rounded-xl bg-white/5 border border-white/10 text-xs font-bold text-slate-300 hover:text-white hover:bg-white/10 transition-all">
                    {{ __('Close') }}
                </button>
            </div>

        </div>
    </div>
</div>