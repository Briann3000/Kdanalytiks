@extends('layouts.app')

@section('title', __('Originality Diagnostic Report — ') . $scan->title)
@section('meta_robots', 'noindex, nofollow')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-5" x-data="plagiarismReportViewer()">

        <!-- Top Navigation & Title Bar -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200 pb-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <a href="{{ route('plagiarism.index') }}"
                        class="text-xs font-semibold text-[#2271b1] hover:underline inline-flex items-center gap-1">
                        <i class="fa-solid fa-arrow-left text-[10px]"></i>
                        <span>{{ __('Scan History') }}</span>
                    </a>
                    <span class="text-gray-300">/</span>
                    <span class="text-xs text-gray-500 font-medium">{{ __('Diagnostic Report') }}</span>
                </div>
                <h1 class="text-xl font-bold text-gray-900 tracking-tight">{{ $scan->title }}</h1>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                    <span>{{ number_format($scan->word_count) }} {{ __('Words') }}</span>
                    <span>&bull;</span>
                    <span>{{ $scan->created_at->format('M d, Y · H:i') }}</span>
                    @if($scan->original_filename)
                        <span>&bull;</span>
                        <span class="text-gray-400 truncate max-w-xs">{{ $scan->original_filename }}</span>
                    @endif
                </div>
            </div>

            <!-- Overall Score Badges & PDF Button -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Similarity Gauge -->
                <div class="px-3.5 py-2 rounded-xl bg-white border border-gray-200 shadow-sm flex items-center gap-3">
                    <div>
                        <div class="flex items-center gap-1 group relative">
                            <span
                                class="text-[10px] uppercase font-bold tracking-wider text-gray-400 block">{{ __('Similarity Index') }}</span>
                            <i
                                class="fa-solid fa-circle-question text-[10px] text-gray-400 hover:text-gray-600 cursor-help"></i>
                            <div
                                class="opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 absolute z-50 bottom-full left-0 mb-2 w-64 p-2.5 bg-gray-900 text-white text-[11px] font-normal leading-relaxed rounded-lg shadow-xl border border-gray-800 pointer-events-none">
                                {{ __('Percentage of document words matching external web pages, open journals and indexed academic publications.') }}
                            </div>
                        </div>
                        <span class="text-base font-bold text-gray-900"
                            x-text="similarityPercentage + '%'">{{ number_format($scan->similarity_percentage, 1) }}%</span>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md"
                        :class="similarityPercentage > 25 ? 'bg-red-50 text-red-700 border border-red-200' : (similarityPercentage > 10 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-green-50 text-green-700 border border-green-200')"
                        x-text="similarityLevel">{{ $scan->similarity_level }}</span>
                </div>

                <!-- AI Content Probability Gauge -->
                <div class="px-3.5 py-2 rounded-xl bg-white border border-gray-200 shadow-sm flex items-center gap-3">
                    <div>
                        <div class="flex items-center gap-1 group relative">
                            <span
                                class="text-[10px] uppercase font-bold tracking-wider text-gray-400 block">{{ __('AI Probability') }}</span>
                            <i
                                class="fa-solid fa-circle-question text-[10px] text-gray-400 hover:text-gray-600 cursor-help"></i>
                            <div
                                class="opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 absolute z-50 bottom-full left-0 mb-2 w-64 p-2.5 bg-gray-900 text-white text-[11px] font-normal leading-relaxed rounded-lg shadow-xl border border-gray-800 pointer-events-none">
                                {{ __('Linguistic analysis evaluating sentence rhythm, vocabulary predictability and formulaic transition markers.') }}
                            </div>
                        </div>
                        <span class="text-base font-bold text-gray-900">{{ number_format($scan->ai_percentage, 1) }}%</span>
                    </div>
                    <span
                        class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-gray-100 text-gray-700 border border-gray-200">
                        {{ $scan->ai_level }}
                    </span>
                </div>

                <!-- PDF Download Button -->
                <a href="{{ route('plagiarism.pdf', $scan) }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-900 hover:bg-black text-white text-xs font-semibold rounded-xl shadow-sm transition-all">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>{{ __('Download PDF Certificate') }}</span>
                </a>
            </div>
        </div>

        <!-- Dual-Pane Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Left Pane: Manuscript Reader (Col 7) -->
            <div class="lg:col-span-7 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-3.5 border-b border-gray-200 bg-gray-50/50 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-book-open text-xs text-gray-400"></i>
                        <h2 class="text-xs font-bold text-gray-800">{{ __('Manuscript Document Text') }}</h2>
                    </div>
                    <span class="text-[11px] text-gray-400 font-medium">
                        {{ __('Click any highlighted passage to inspect matching source') }}
                    </span>
                </div>

                <div class="p-6 md:p-8 max-h-[750px] overflow-y-auto font-serif text-sm leading-relaxed text-gray-800 space-y-4 select-text"
                    id="manuscript-viewport">
                    @php
                        $fullContent = $scan->content;
                        $activeMatches = $scan->matches->where('is_excluded', false)->sortBy('start_offset');
                    @endphp

                    @if($activeMatches->count() === 0)
                        <div class="text-justify whitespace-pre-wrap leading-loose">
                            {{ $fullContent }}
                        </div>
                    @else
                        @php
                            $renderedHtml = '';
                            $lastPos = 0;
                        @endphp

                        @foreach($activeMatches as $m)
                            @php
                                $start = max(0, $m->start_offset);
                                $end = min(mb_strlen($fullContent), $m->end_offset);
                                if ($start < $lastPos) {
                                    continue;
                                }
                                // Preceding text
                                $renderedHtml .= e(mb_substr($fullContent, $lastPos, $start - $lastPos));
                                // Highlighted matched text in bright yellow with border
                                $highlightText = e(mb_substr($fullContent, $start, $end - $start));
                                $renderedHtml .= '<mark id="match-span-' . $m->id . '" class="match-highlight px-1 py-0.5 rounded cursor-pointer transition-all bg-yellow-200 hover:bg-yellow-300 text-gray-900 border-b-2 border-yellow-500 font-serif" @click="selectMatch(' . $m->id . ')" title="Matched with: ' . e($m->source_domain ?? 'Source') . ' (' . number_format($m->similarity_score, 0) . '%)">' . $highlightText . '</mark>';
                                $lastPos = $end;
                            @endphp
                        @endforeach

                        @php
                            $renderedHtml .= e(mb_substr($fullContent, $lastPos));
                        @endphp

                        <div class="text-justify whitespace-pre-wrap leading-loose">
                            {!! $renderedHtml !!}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Pane: Matched Sources & Diagnostic Controls (Col 5) -->
            <div class="lg:col-span-5 space-y-4">

                <!-- Academic Exclusions Control Card -->
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm space-y-3.5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5 group relative">
                            <span class="text-xs font-bold text-gray-800">{{ __('Academic Exclusion Filters') }}</span>
                            <i
                                class="fa-solid fa-circle-question text-[11px] text-gray-400 hover:text-gray-600 cursor-help"></i>
                            <div
                                class="opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 absolute z-50 bottom-full left-0 mb-2 w-72 p-2.5 bg-gray-900 text-white text-[11px] font-normal leading-relaxed rounded-lg shadow-xl border border-gray-800 pointer-events-none">
                                {{ __('Standard academic protocols allow excluding bibliographies, direct quotes, and citations to eliminate false positives on literature reviews.') }}
                            </div>
                        </div>
                        <i class="fa-solid fa-sliders text-xs text-gray-400"></i>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <label
                            class="flex items-center justify-between p-2 rounded-lg border border-gray-200 hover:bg-gray-50/50 cursor-pointer text-xs transition-colors group relative">
                            <div class="flex items-center gap-1.5">
                                <input type="checkbox" x-model="excludeReferences"
                                    @change="toggleSetting('exclude_references', excludeReferences)"
                                    class="rounded border-gray-300 text-[#2271b1] focus:ring-[#2271b1] text-xs">
                                <span class="text-[11px] font-medium text-gray-700">{{ __('References') }}</span>
                            </div>
                            <i
                                class="fa-solid fa-circle-question text-[10px] text-gray-400 hover:text-gray-600 cursor-help"></i>
                            <div
                                class="opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 absolute z-50 bottom-full left-0 mb-2 w-56 p-2 bg-gray-900 text-white text-[10.5px] font-normal leading-relaxed rounded-md shadow-xl border border-gray-800 pointer-events-none">
                                {{ __('Excludes the References, Works Cited or Bibliography section at the end of the manuscript.') }}
                            </div>
                        </label>

                        <label
                            class="flex items-center justify-between p-2 rounded-lg border border-gray-200 hover:bg-gray-50/50 cursor-pointer text-xs transition-colors group relative">
                            <div class="flex items-center gap-1.5">
                                <input type="checkbox" x-model="excludeQuotes"
                                    @change="toggleSetting('exclude_quotes', excludeQuotes)"
                                    class="rounded border-gray-300 text-[#2271b1] focus:ring-[#2271b1] text-xs">
                                <span class="text-[11px] font-medium text-gray-700">{{ __('Quotes') }}</span>
                            </div>
                            <i
                                class="fa-solid fa-circle-question text-[10px] text-gray-400 hover:text-gray-600 cursor-help"></i>
                            <div
                                class="opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 absolute z-50 bottom-full left-0 mb-2 w-56 p-2 bg-gray-900 text-white text-[10.5px] font-normal leading-relaxed rounded-md shadow-xl border border-gray-800 pointer-events-none">
                                {{ __('Ignores text enclosed in quotation marks or formatted as academic blockquotes.') }}
                            </div>
                        </label>

                        <label
                            class="flex items-center justify-between p-2 rounded-lg border border-gray-200 hover:bg-gray-50/50 cursor-pointer text-xs transition-colors group relative">
                            <div class="flex items-center gap-1.5">
                                <input type="checkbox" x-model="excludeCitations"
                                    @change="toggleSetting('exclude_citations', excludeCitations)"
                                    class="rounded border-gray-300 text-[#2271b1] focus:ring-[#2271b1] text-xs">
                                <span class="text-[11px] font-medium text-gray-700">{{ __('Citations') }}</span>
                            </div>
                            <i
                                class="fa-solid fa-circle-question text-[10px] text-gray-400 hover:text-gray-600 cursor-help"></i>
                            <div
                                class="opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 absolute z-50 bottom-full left-0 mb-2 w-56 p-2 bg-gray-900 text-white text-[10.5px] font-normal leading-relaxed rounded-md shadow-xl border border-gray-800 pointer-events-none">
                                {{ __('Ignores parenthetical in-text citations like (Author, 2020) and et al. references.') }}
                            </div>
                        </label>
                    </div>

                    <!-- Min Word Threshold -->
                    <div class="pt-1">
                        <div class="flex items-center justify-between text-[11px] font-medium text-gray-600 mb-1">
                            <div class="flex items-center gap-1 group relative">
                                <span>{{ __('Minimum Match Length') }}: <strong class="text-gray-900"
                                        x-text="minWordsThreshold + ' words'"></strong></span>
                                <i
                                    class="fa-solid fa-circle-question text-[10px] text-gray-400 hover:text-gray-600 cursor-help"></i>
                                <div
                                    class="opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 absolute z-50 bottom-full left-0 mb-2 w-64 p-2 bg-gray-900 text-white text-[10.5px] font-normal leading-relaxed rounded-md shadow-xl border border-gray-800 pointer-events-none">
                                    {{ __('Ignores short phrase matches below this word count to prevent false flags on standard academic idioms (e.g. \'the purpose of this study\').') }}
                                </div>
                            </div>
                        </div>
                        <input type="range" min="4" max="20" step="1" x-model="minWordsThreshold"
                            @change="toggleSetting('min_words_threshold', minWordsThreshold)"
                            class="w-full h-1.5 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-[#2271b1]">
                    </div>

                    <!-- Excluded Domains Whitelist -->
                    <div class="pt-2 border-t border-gray-100 space-y-2">
                        <div class="flex items-center gap-1 group relative">
                            <label
                                class="block text-[11px] font-bold text-gray-700">{{ __('Domain Whitelist (Ignored Sources)') }}</label>
                            <i
                                class="fa-solid fa-circle-question text-[10px] text-gray-400 hover:text-gray-600 cursor-help"></i>
                            <div
                                class="opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 absolute z-50 bottom-full left-0 mb-2 w-64 p-2 bg-gray-900 text-white text-[10.5px] font-normal leading-relaxed rounded-md shadow-xl border border-gray-800 pointer-events-none">
                                {{ __('Whitelisted websites will be completely excluded from the calculated similarity score.') }}
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <input type="text" x-model="newDomainInput" @keydown.enter.prevent="addDomain()"
                                class="flex-1 px-2.5 py-1.5 text-xs bg-gray-50/50 border border-gray-200 rounded-lg focus:bg-white focus:ring-1 focus:ring-[#2271b1]">
                            <button type="button" @click="addDomain()"
                                class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg transition-colors">
                                {{ __('Add') }}
                            </button>
                        </div>

                        <template x-if="excludedDomains.length > 0">
                            <div class="flex flex-wrap gap-1.5 pt-1">
                                <template x-for="domain in excludedDomains" :key="domain">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-[11px] border border-gray-200">
                                        <span x-text="domain"></span>
                                        <button type="button" @click="removeDomain(domain)"
                                            class="text-gray-400 hover:text-red-600">
                                            &times;
                                        </button>
                                    </span>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Apply Filters Action Button -->
                    <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-[11px] text-gray-400" x-show="filterUpdatedMessage" x-text="filterUpdatedMessage"
                            x-cloak></span>
                        <span class="text-[11px] text-gray-400"
                            x-show="!filterUpdatedMessage">{{ __('Adjust filters and apply') }}</span>
                        <button type="button" @click="applyAllFilters()" :disabled="isApplyingFilters"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-[#2271b1] hover:bg-[#135e96] text-white text-xs font-semibold rounded-lg shadow-sm transition-all disabled:opacity-50">
                            <i class="fa-solid fa-spinner fa-spin text-xs" x-show="isApplyingFilters" x-cloak></i>
                            <i class="fa-solid fa-check text-xs" x-show="!isApplyingFilters"></i>
                            <span>{{ __('Apply Filters') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Matched Sources Panel -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                    <div class="p-4 border-b border-gray-200 bg-gray-50/50 flex items-center justify-between">
                        <div>
                            <h2 class="text-xs font-bold text-gray-900">{{ __('Identified Sources') }}</h2>
                            <span class="text-[11px] text-gray-500 font-medium">
                                <span x-text="activeMatchesCount">{{ $scan->activeMatches()->count() }}</span>
                                {{ __('flagged segments found') }}
                            </span>
                        </div>
                        <span class="text-xs font-bold px-2 py-0.5 rounded bg-gray-100 text-gray-600">
                            {{ $scan->matches->count() }} {{ __('Total') }}
                        </span>
                    </div>

                    <div class="divide-y divide-gray-100 max-h-[620px] overflow-y-auto p-2 space-y-2">
                        @forelse($scan->matches as $index => $match)
                            <div id="source-card-{{ $match->id }}"
                                :class="selectedMatchId === {{ $match->id }} ? 'ring-2 ring-[#2271b1] bg-blue-50/20' : 'hover:bg-gray-50/60'"
                                class="p-3.5 rounded-lg border border-gray-200/80 transition-all space-y-2.5 bg-white">

                                <!-- Source Header -->
                                <div class="flex items-start justify-between gap-2">
                                    <div class="space-y-0.5 min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span
                                                class="w-5 h-5 rounded-full bg-gray-100 text-[10px] font-bold text-gray-600 flex items-center justify-center flex-shrink-0">
                                                {{ $index + 1 }}
                                            </span>
                                            <span class="text-xs font-bold text-gray-900 truncate">
                                                {{ $match->source_domain ?? 'Web / Academic Source' }}
                                            </span>
                                        </div>
                                        @if($match->source_title)
                                            <p class="text-[11px] text-gray-500 truncate pl-6.5">{{ $match->source_title }}</p>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <span
                                            class="text-xs font-bold px-2 py-0.5 rounded bg-yellow-100 text-yellow-900 border border-yellow-300">
                                            {{ number_format($match->similarity_score, 0) }}% {{ __('Match') }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Text Comparison Diff Snippet -->
                                <div class="bg-gray-50/70 p-2.5 rounded-md border border-gray-100 text-xs space-y-2 font-mono">
                                    <div>
                                        <span
                                            class="text-[10px] uppercase font-bold text-gray-400 block tracking-wider">{{ __('Your Document Text') }}</span>
                                        <p class="text-gray-800 italic mt-0.5 line-clamp-3 leading-relaxed">
                                            "{{ $match->original_snippet }}"
                                        </p>
                                    </div>
                                    <div class="pt-1.5 border-t border-gray-200/60">
                                        <span
                                            class="text-[10px] uppercase font-bold text-gray-400 block tracking-wider">{{ __('Matched Source Text') }}</span>
                                        <p class="text-gray-600 mt-0.5 line-clamp-3 leading-relaxed">
                                            "{{ $match->matched_text }}"
                                        </p>
                                    </div>
                                </div>

                                <!-- Source Actions Footer -->
                                <div class="flex items-center justify-between text-xs pt-1">
                                    @if($match->source_url)
                                        <a href="{{ $match->source_url }}" target="_blank" rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#2271b1] hover:underline truncate max-w-[200px]">
                                            <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                                            <span>{{ __('Visit Source Link') }}</span>
                                        </a>
                                    @else
                                        <span class="text-[11px] text-gray-400">{{ __('Scholarly Index') }}</span>
                                    @endif

                                    <button type="button" @click="toggleMatchExclusion({{ $match->id }})"
                                        class="text-[11px] font-semibold text-gray-500 hover:text-gray-800 transition-colors inline-flex items-center gap-1">
                                        <i class="fa-solid fa-ban text-[10px]"></i>
                                        <span
                                            x-text="excludedMatchIds.includes({{ $match->id }}) ? '{{ __('Restore Match') }}' : '{{ __('Exclude Match') }}'">
                                            {{ $match->is_excluded ? __('Restore Match') : __('Exclude Match') }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center space-y-2">
                                <i class="fa-solid fa-circle-check text-2xl text-green-500"></i>
                                <h4 class="text-xs font-bold text-gray-800">{{ __('No Overlapping Sources Detected') }}</h4>
                                <p class="text-[11px] text-gray-500 leading-relaxed">
                                    {{ __('The scanned text demonstrates high originality with no significant verbatim overlaps found in external indexes.') }}
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>

        </div>

    </div>

    <script>
        function plagiarismReportViewer() {
            return {
                selectedMatchId: null,
                similarityPercentage: {{ $scan->similarity_percentage }},
                similarityLevel: '{{ $scan->similarity_level }}',
                activeMatchesCount: {{ $scan->activeMatches()->count() }},
                excludeQuotes: {{ $scan->exclude_quotes ? 'true' : 'false' }},
                excludeReferences: {{ $scan->exclude_references ? 'true' : 'false' }},
                excludeCitations: {{ $scan->exclude_citations ? 'true' : 'false' }},
                minWordsThreshold: {{ $scan->min_words_threshold ?? 8 }},
                excludedDomains: @json($scan->excluded_domains ?? []),
                newDomainInput: '',
                isApplyingFilters: false,
                filterUpdatedMessage: '',
                excludedMatchIds: @json($scan->matches->where('is_excluded', true)->pluck('id')->values()),

                selectMatch(matchId) {
                    this.selectedMatchId = matchId;
                    const sourceCard = document.getElementById('source-card-' + matchId);
                    if (sourceCard) {
                        sourceCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                },

                applyAllFilters() {
                    this.isApplyingFilters = true;
                    this.filterUpdatedMessage = '';

                    const payload = {
                        _token: '{{ csrf_token() }}',
                        exclude_references: this.excludeReferences ? 1 : 0,
                        exclude_quotes: this.excludeQuotes ? 1 : 0,
                        exclude_citations: this.excludeCitations ? 1 : 0,
                        min_words_threshold: parseInt(this.minWordsThreshold)
                    };

                    fetch('{{ route('plagiarism.toggle-exclusion', $scan) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload)
                    })
                        .then(res => res.json())
                        .then(data => {
                            this.isApplyingFilters = false;
                            if (data.success) {
                                this.similarityPercentage = data.similarity_percentage;
                                this.similarityLevel = data.similarity_level;
                                this.activeMatchesCount = data.active_matches_count;
                                this.filterUpdatedMessage = 'Filters applied!';
                                setTimeout(() => { this.filterUpdatedMessage = ''; }, 3000);
                            }
                        })
                        .catch(() => {
                            this.isApplyingFilters = false;
                        });
                },

                toggleSetting(settingKey, val) {
                    let payload = {
                        _token: '{{ csrf_token() }}',
                    };
                    payload[settingKey] = val;

                    this.sendExclusionRequest(payload);
                },

                addDomain() {
                    let domain = this.newDomainInput.trim();
                    if (!domain) return;
                    this.newDomainInput = '';

                    this.sendExclusionRequest({
                        _token: '{{ csrf_token() }}',
                        add_excluded_domain: domain
                    });
                },

                removeDomain(domain) {
                    this.sendExclusionRequest({
                        _token: '{{ csrf_token() }}',
                        remove_excluded_domain: domain
                    });
                },

                toggleMatchExclusion(matchId) {
                    this.sendExclusionRequest({
                        _token: '{{ csrf_token() }}',
                        match_id: matchId
                    }, matchId);
                },

                sendExclusionRequest(payload, matchId = null) {
                    fetch('{{ route('plagiarism.toggle-exclusion', $scan) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload)
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                this.similarityPercentage = data.similarity_percentage;
                                this.similarityLevel = data.similarity_level;
                                this.activeMatchesCount = data.active_matches_count;
                                if (data.excluded_domains) {
                                    this.excludedDomains = data.excluded_domains;
                                }

                                if (matchId) {
                                    if (this.excludedMatchIds.includes(matchId)) {
                                        this.excludedMatchIds = this.excludedMatchIds.filter(id => id !== matchId);
                                        const span = document.getElementById('match-span-' + matchId);
                                        if (span) span.style.display = '';
                                    } else {
                                        this.excludedMatchIds.push(matchId);
                                        const span = document.getElementById('match-span-' + matchId);
                                        if (span) span.style.display = 'none';
                                    }
                                }
                            }
                        });
                }
            }
        }
    </script>
@endsection