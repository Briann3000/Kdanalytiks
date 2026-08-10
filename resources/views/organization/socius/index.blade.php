@extends('layouts.app')

@section('title', 'Org Socius Cross-Survey Intelligence - ' . $org->name)

@section('content')
<div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8" x-data="orgSociusHub()">
    <div class="max-w-6xl mx-auto space-y-8">

        <!-- Top Header Bar -->
        <div class="flex flex-col md:flex-row md:items-center justify-between bg-white p-6 rounded-2xl border border-slate-200 shadow-sm gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-bold text-xl shadow-md">
                    <i class="fa-solid fa-brain"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">{{ __('Org Socius AI — Institutional Intelligence') }}</h1>
                    <p class="text-xs text-slate-500 mt-0.5">{{ __('Synthesize research memory & shared documents across') }} <strong class="text-indigo-600">{{ $org->name }}</strong>.</p>
                </div>
            </div>

            <button type="button" @click="showStartModal = true" 
                    class="inline-flex items-center px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-sm transition gap-2">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>{{ __('Start New Cross-Survey AI Session') }}</span>
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main: Past Studies Context Summaries (Collapsible Accordions) -->
            <div class="lg:col-span-2 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>{{ __('Institutional Study Memory') }}</span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">{{ $contexts->count() }}</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">{{ __('Auto-summarized findings from workspace AI analyses.') }}</p>
                    </div>

                    <!-- Search Filter -->
                    <div class="relative w-full sm:w-64">
                        <input type="text" x-model="searchQuery" placeholder="{{ __('Search study memories...') }}" 
                               class="w-full pl-9 pr-4 py-2 rounded-xl bg-white border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-xs text-slate-400"></i>
                    </div>
                </div>

                <div class="space-y-3">
                    @forelse($contexts as $ctx)
                        @php
                            $ctxId = $ctx->id;
                            $surveyTitle = $ctx->survey->title ?? __('Institutional Study');
                        @endphp
                        <div x-show="matchesSearch('{{ strtolower(addslashes(str_replace(["\r", "\n"], ' ', $surveyTitle . ' ' . $ctx->content))) }}')" 
                             class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden transition hover:border-indigo-200">
                            <!-- Accordion Header -->
                            <button type="button" @click="toggleAccordion({{ $ctxId }})" 
                                    class="w-full p-4 flex items-center justify-between text-left hover:bg-slate-50 transition">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs shrink-0 font-bold">
                                        <i class="fa-solid fa-file-contract"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-900 text-sm leading-snug">{{ $surveyTitle }}</h3>
                                        <div class="flex items-center gap-2 text-[10px] text-slate-500 mt-0.5">
                                            <span>{{ $ctx->generated_at ? $ctx->generated_at->diffForHumans() : __('Recently') }}</span>
                                            <span>•</span>
                                            <span class="capitalize text-indigo-600 font-semibold">{{ str_replace('_', ' ', $ctx->context_type) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-slate-500 font-medium" x-text="openAccordion === {{ $ctxId }} ? '{{ __('Collapse') }}' : '{{ __('Expand') }}'"></span>
                                    <i class="fa-solid fa-chevron-down text-xs text-slate-400 transition-transform duration-200"
                                       :class="{ 'rotate-180': openAccordion === {{ $ctxId }} }"></i>
                                </div>
                            </button>

                            <!-- Accordion Body -->
                            <div x-show="openAccordion === {{ $ctxId }}" x-cloak x-transition
                                 class="p-4 border-t border-slate-100 bg-slate-50 text-xs text-slate-700 font-mono leading-relaxed whitespace-pre-line max-h-60 overflow-y-auto custom-scrollbar">
                                {{ $ctx->content }}
                            </div>
                        </div>
                    @empty
                        <div class="bg-white p-12 rounded-2xl border border-slate-200 text-center space-y-3 shadow-sm">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto text-xl">
                                <i class="fa-solid fa-box-archive"></i>
                            </div>
                            <h3 class="font-bold text-slate-900">{{ __('No Institutional Summaries Yet') }}</h3>
                            <p class="text-xs text-slate-500 max-w-sm mx-auto">{{ __('When team members analyze surveys or chat in Report Socius, study summaries auto-populate here.') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Sidebar: Saved Socius Threads & Shared KB -->
            <div class="space-y-6">
                <!-- Socius Threads Section -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider">{{ __('Your Socius Threads') }}</h3>
                        <button type="button" @click="showStartModal = true" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold transition flex items-center gap-1">
                            <i class="fa-solid fa-plus text-[10px]"></i>
                            <span>{{ __('New') }}</span>
                        </button>
                    </div>

                    <div class="space-y-2">
                        @forelse($threads as $thread)
                            <div class="group flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100 hover:border-indigo-200 hover:bg-indigo-50/40 transition">
                                <a href="{{ route('socius.chat.threads.show', $thread->id) }}" class="flex-1 min-w-0 mr-2">
                                    <div class="font-bold text-slate-800 text-xs truncate group-hover:text-indigo-600 transition">{{ $thread->title }}</div>
                                    <div class="text-[9px] text-slate-400 mt-0.5">{{ $thread->created_at->diffForHumans() }}</div>
                                </a>

                                <!-- Action Buttons: Rename & Delete -->
                                <div class="flex items-center gap-1 opacity-80 group-hover:opacity-100 transition">
                                    <button type="button" @click.stop="promptRename({{ $thread->id }}, '{{ addslashes($thread->title) }}')" 
                                            class="w-6 h-6 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-indigo-600 flex items-center justify-center shadow-xs transition" title="{{ __('Rename Thread') }}">
                                        <i class="fa-solid fa-pen text-[9px]"></i>
                                    </button>

                                    <form action="{{ route('organization.socius.threads.destroy', $thread->id) }}" method="POST" onsubmit="return confirm('Delete this Socius thread?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-6 h-6 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-rose-600 flex items-center justify-center shadow-xs transition" title="{{ __('Delete Thread') }}">
                                            <i class="fa-solid fa-trash-can text-[9px]"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 italic py-2">{{ __('No saved threads yet.') }}</p>
                        @endforelse
                    </div>
                </div>

                <!-- Shared Org Knowledge Base -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider">{{ __('Shared Knowledge Base (:count)', ['count' => $sharedKBs->count()]) }}</h3>
                        <button type="button" @click="showKbModal = true" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold transition flex items-center gap-1">
                            <i class="fa-solid fa-upload text-[10px]"></i>
                            <span>{{ __('Upload') }}</span>
                        </button>
                    </div>

                    <div class="space-y-2">
                        @forelse($sharedKBs as $kb)
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                                <h4 class="text-xs font-bold text-slate-900 leading-snug">{{ $kb->title }}</h4>
                                <p class="text-[10px] text-slate-500 line-clamp-2 leading-relaxed font-mono">{{ $kb->content }}</p>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 italic py-2">{{ __('No shared reference documents or AI instructions uploaded.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Start AI Session Modal (Survey Picker) -->
    <div x-show="showStartModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="showStartModal = false" class="bg-white border border-slate-200 rounded-2xl max-w-lg w-full p-6 space-y-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">{{ __('Select Surveys to Cross-Analyze') }}</h3>
                </div>
                <button type="button" @click="showStartModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>

            <div class="space-y-4">
                <p class="text-xs text-slate-500">{{ __('Choose whether to synthesize insights across all workspace surveys or select specific studies for comparison:') }}</p>

                <div class="space-y-2 max-h-56 overflow-y-auto custom-scrollbar p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <label class="flex items-center gap-2.5 text-xs text-slate-900 cursor-pointer p-2 hover:bg-white rounded-lg transition font-bold border-b border-slate-200/60">
                        <input type="checkbox" :checked="selectedSurveys.length === 0" @change="selectedSurveys = []" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span>{{ __('All Workspace Surveys') }} ({{ $surveys->count() }})</span>
                    </label>

                    @foreach($surveys as $s)
                        <label class="flex items-center gap-2.5 text-xs text-slate-700 cursor-pointer p-2 hover:bg-white rounded-lg transition">
                            <input type="checkbox" value="{{ $s->id }}" x-model="selectedSurveys" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="truncate font-medium">{{ $s->title }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                <button type="button" @click="showStartModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200 transition">
                    {{ __('Cancel') }}
                </button>
                <button type="button" @click="launchSession()" class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700 transition shadow-md flex items-center gap-1.5">
                    <span>{{ __('Launch AI Session') }}</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Upload Reference Document / AI Instructions Modal -->
    <div x-show="showKbModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="showKbModal = false" class="bg-white border border-slate-200 rounded-2xl max-w-lg w-full p-6 space-y-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <h3 class="text-base font-bold text-slate-900">{{ __('Add Knowledge Document / AI Instructions') }}</h3>
                <button type="button" @click="showKbModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>

            <form action="{{ route('organization.socius.kb.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">{{ __('Document or Instruction Title') }}</label>
                    <input type="text" name="title" required placeholder="{{ __('e.g., Executive Reporting Guidelines / Pricing Strategy') }}"
                           class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">{{ __('Text Content / Custom AI Instructions') }}</label>
                    <textarea name="content" rows="4" required placeholder="{{ __('Paste reference guidelines or specific instructions for AI report synthesis...') }}"
                              class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none custom-scrollbar"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">{{ __('Attach Reference File (PDF, TXT, DOCX - Optional)') }}</label>
                    <input type="file" name="document" accept=".pdf,.txt,.docx,.doc"
                           class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                </div>

                <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                    <button type="button" @click="showKbModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200 transition">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700 transition shadow-md">
                        {{ __('Save Knowledge Base Entry') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function orgSociusHub() {
        return {
            openAccordion: null,
            searchQuery: '',
            showStartModal: false,
            showKbModal: false,
            selectedSurveys: [],
            toggleAccordion(id) {
                this.openAccordion = (this.openAccordion === id) ? null : id;
            },
            matchesSearch(text) {
                if (!this.searchQuery.trim()) return true;
                return text.includes(this.searchQuery.toLowerCase().trim());
            },
            promptRename(id, oldTitle) {
                const newTitle = prompt('{{ __('Enter new thread title:') }}', oldTitle);
                if (newTitle && newTitle.trim() && newTitle !== oldTitle) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/organization/socius/threads/${id}`;
                    form.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="PATCH"><input type="hidden" name="title" value="${newTitle.replace(/"/g, '&quot;')}">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            },
            launchSession() {
                let url = "{{ route('socius.chat.index') }}";
                if (this.selectedSurveys.length > 0) {
                    url += "?surveys=" + this.selectedSurveys.join(',');
                }
                window.location.href = url;
            }
        }
    }
</script>
@endsection
