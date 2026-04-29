@extends('layouts.app')
@section('title', 'Report Preview - Research Studio')

@php
    $isTruncated = $isTruncated ?? false;
    $truncateAfter = 10; // Show front matter and Chapter 1 intro
    $sectionCount = 0;
    $reportLocale = $reportData['metadata']['locale'] ?? 'en';
    $currentLocale = app()->getLocale();
    $needsTranslation = ($reportLocale !== $currentLocale);

    // Title Normalizer for legacy or partially translated keys
    $normalizeTitle = function($title) {
        $clean = preg_replace('/\s*\(approx\..*?\)/i', '', $title);
        
        // Map common Swahili/Mixed prefixes back to standard English keys
        $mappings = [
            '1.1 Background ya Utafiti' => '1.1 Background of the Study',
            '1.1 Historia ya Utafiti' => '1.1 Background of the Study',
            '1.2 Maelezo ya Tatizo' => '1.2 Statement of the Problem',
            '1.3 Malengo ya Utafiti' => '1.3 Objectives of the Study',
            '1.4 Maswali ya Utafiti' => '1.4 Research Questions',
            '1.5 Muhimu wa Utafiti' => '1.5 Significance of the Study',
            '1.6 Upeo na Vikwazo' => '1.6 Scope and Limitations',
            '2.0 Utangulizi' => '2.0 Introduction',
            '2.1 Mfumo wa Kinadharia' => '2.1 Theoretical Framework',
            '2.2 Mfumo wa Dhana' => '2.2 Conceptual Framework',
            '2.3 Mapitio ya Kiuchunguzi' => '2.3 Empirical Review',
            '2.4 Mapengo ya Utafiti' => '2.4 Research Gaps',
            '2.5 Muhtasari' => '2.5 Summary',
            '3.1 Muundo wa Utafiti' => '3.1 Research Design',
            '3.2 Idadi Lengwa' => '3.2 Target Population',
            '3.3 Ukubwa wa Sampuli' => '3.3 Sample Size and Sampling Techniques',
            '3.4 Zana za Ukusanyaji' => '3.4 Data Collection Instruments',
            '3.6 Uhalali na Uaminifu' => '3.6 Validity and Reliability',
            '5.1 Muhtasari wa Matokeo' => '5.1 Summary of Findings',
            '5.2 Hitimisho' => '5.2 Conclusions',
            '5.3 Mapendekezo' => '5.3 Recommendations',
        ];

        foreach ($mappings as $sw => $en) {
            if (str_contains($clean, $sw)) return $en;
        }

        return $clean;
    };
@endphp

@section('sub_sidebar')
    <!-- Professional Collapsible Sidebar for Report Navigation -->
    <div class="report-structure-sidebar bg-white border-r border-gray-100 flex-shrink-0 flex flex-col z-30 shadow-sm transition-all duration-300"
         style="position: sticky; top: 0; height: calc(100vh - 4.1rem); overflow: hidden;"
         :class="sidebarOpen ? 'w-60' : 'w-12'">

        <div class="flex-shrink-0 p-4 border-b border-gray-100 bg-gray-50/30 flex items-center" :class="sidebarOpen ? '' : 'justify-center px-0'">
            <i class="fa-solid fa-folder-open text-indigo-500" :class="sidebarOpen ? 'mr-3' : ''"></i>
            <h3 x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                class="text-[10px] font- uppercase tracking-widest truncate flex-1">
                {{ __('Report Structure') }}
            </h3>
            <button @click="sidebarOpen = !sidebarOpen"
                class="w-6 h-6 flex items-center justify-center rounded-lg bg-white border border-gray-100 text-gray-400 hover:text-indigo-600 hover:border-indigo-100 transition-all"
                :class="sidebarOpen ? 'ml-2' : 'absolute -right-3 top-7 z-50 shadow-md'">
                <i class="fa-solid fa-chevron-left text-[10px] transition-transform duration-300" :class="sidebarOpen ? '' : 'rotate-180'"></i>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto p-3 space-y-1 custom-scrollbar" style="min-height: 0;">
            @foreach($reportData['sections'] as $title => $content)
                @php
                    $cleanTitle = $normalizeTitle($title);
                    $isChapter = str_starts_with($title, 'CHAPTER') || in_array($title, ['REFERENCES', 'APPENDICES']);
                    $isPrelim = in_array($title, ['Title Page', 'Declaration', 'Acknowledgement', 'Abstract', 'Abbreviations', 'Definition of Key Terms']);
                @endphp
                @if($isChapter)
                    <div class="mt-3 mb-1">
                        <a href="#section-{{ $loop->iteration }}"
                            class="flex items-center rounded-xl text-[10px] font-black text-indigo-700 bg-indigo-50 border border-indigo-100 hover:bg-indigo-100 transition-all group"
                            :class="sidebarOpen ? 'px-4 py-2.5' : 'p-2 justify-center'">
                            <span class="w-5 h-5 flex-shrink-0 flex items-center justify-center rounded bg-indigo-600 text-white text-[8px]"
                                :class="sidebarOpen ? 'mr-2' : ''">
                                <i class="fa-solid fa-bookmark text-[7px]"></i>
                            </span>
                            <span x-show="sidebarOpen" x-transition class="truncate uppercase tracking-wider">{{ __($cleanTitle) }}</span>
                        </a>
                    </div>
                @elseif($isPrelim)
                    <a href="#section-{{ $loop->iteration }}"
                        class="flex items-center rounded-xl text-[10px] font-bold text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-all border border-transparent hover:border-gray-100 italic"
                        :class="sidebarOpen ? 'px-4 py-2' : 'p-2 justify-center'">
                        <span class="w-5 h-5 flex-shrink-0 flex items-center justify-center rounded bg-gray-50 text-[8px] text-gray-400 group-hover:bg-gray-100 group-hover:text-gray-600 transition-colors"
                            :class="sidebarOpen ? 'mr-2' : ''">
                            {{ $loop->iteration }}
                        </span>
                        <span x-show="sidebarOpen" x-transition class="truncate">{{ __($cleanTitle) }}</span>
                    </a>
                @else
                    <a href="#section-{{ $loop->iteration }}"
                        class="flex items-center rounded-xl text-xs font-bold text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 transition-all border border-transparent hover:border-indigo-100 group"
                        :class="sidebarOpen ? 'px-4 py-2.5' : 'p-2 justify-center'">
                        <span
                            class="w-5 h-5 flex-shrink-0 flex items-center justify-center rounded-lg bg-gray-50 text-[9px] group-hover:bg-indigo-100 group-hover:text-indigo-600 transition-colors"
                            :class="sidebarOpen ? 'mr-2' : ''">
                            {{ $loop->iteration }}
                        </span>
                        <span x-show="sidebarOpen" x-transition class="truncate">{{ __($cleanTitle) }}</span>
                    </a>
                @endif
            @endforeach
        </nav>
    </div>
@endsection

@section('content')
    <div class="flex flex-col bg-gray-50/50">
        <!-- Condensed Sticky Export Configuration Top Bar -->
        <div
            class="sticky top-0 z-40 px-4 py-3 bg-white/90 backdrop-blur-md border-b border-gray-200 shadow-sm flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="flex items-center space-x-2">
                    <a href="{{ route('research-proposal.index') }}"
                        class="w-7 h-7 flex items-center justify-center rounded-lg bg-gray-50 text-gray-400 hover:bg-gray-100 transition-all border border-gray-100">
                        <i class="fa-solid fa-arrow-left text-[10px]"></i>
                    </a>
                    <div class="hidden sm:block">
                        <h1 class="text-[10px] font-black text-gray-900 uppercase tracking-tight leading-none">Preview</h1>
                        <p class="text-[8px] text-gray-400 font-bold uppercase tracking-wider">{{ $reportId }}</p>
                    </div>
                </div>
                <div class="h-5 w-[1px] bg-gray-200"></div>

                <form action="{{ route('research-proposal.generate') }}" method="POST" class="flex items-center space-x-2"
                    id="regenerateForm">
                    @csrf
                    <input type="hidden" name="survey_id" value="{{ $reportData['metadata']['survey_id'] }}">
                    <input type="hidden" name="format" value="{{ $format }}">
                    @foreach($reportData['metadata']['manual_references'] ?? [] as $index => $ref)
                        <input type="hidden" name="references[{{ $index }}][author]" value="{{ $ref['author'] }}">
                        <input type="hidden" name="references[{{ $index }}][year]" value="{{ $ref['year'] }}">
                        <input type="hidden" name="references[{{ $index }}][title]" value="{{ $ref['title'] }}">
                        <input type="hidden" name="references[{{ $index }}][source]" value="{{ $ref['source'] }}">
                    @endforeach

                    <span class="text-[9px] font-black text-gray-400 uppercase">Style:</span>
                    <select name="style" onchange="this.form.submit(); this.disabled=true;"
                        class="bg-indigo-50 text-indigo-600 text-[8px] font-black rounded border border-indigo-100 uppercase py-0.5 px-2 focus:ring-1 focus:ring-indigo-300 cursor-pointer hover:bg-indigo-100 transition-all outline-none">
                        <option value="apa7" {{ ($reportData['metadata']['style'] ?? '') === 'apa7' ? 'selected' : '' }}>APA
                        </option>
                        <option value="mla9" {{ ($reportData['metadata']['style'] ?? '') === 'mla9' ? 'selected' : '' }}>MLA
                        </option>
                        <option value="harvard" {{ ($reportData['metadata']['style'] ?? '') === 'harvard' ? 'selected' : '' }}>Harvard</option>
                        <option value="chicago" {{ ($reportData['metadata']['style'] ?? '') === 'chicago' ? 'selected' : '' }}>Chicago</option>
                        <option value="ieee" {{ ($reportData['metadata']['style'] ?? '') === 'ieee' ? 'selected' : '' }}>IEEE
                        </option>
                        <option value="vancouver" {{ ($reportData['metadata']['style'] ?? '') === 'vancouver' ? 'selected' : '' }}>Vancouver</option>
                        <option value="oscola" {{ ($reportData['metadata']['style'] ?? '') === 'oscola' ? 'selected' : '' }}>
                            OSCOLA</option>
                    </select>
                </form>
            </div>

            @if(!$canExport)
                <div class="flex items-center space-x-2 px-3 py-1.5 bg-amber-50 rounded-xl border border-amber-100">
                    <i class="fa-solid fa-lock text-amber-500 text-[10px]"></i>
                    <span class="text-[8px] font-black text-amber-700 uppercase tracking-wider">Upgrade for Export</span>
                </div>
            @else
                <form action="{{ route('research-proposal.export', $reportId) }}" method="POST"
                    x-data="{ format: '{{ $format }}' }" class="flex items-center space-x-3">
                    @csrf
                    <input type="hidden" name="format" :value="format">

                    @if($isTruncated)
                        <div class="flex items-center px-3 py-1.5 bg-indigo-50 rounded-lg border border-indigo-100 mr-2">
                            <span class="text-[8px] font-black text-indigo-600 uppercase tracking-wider">{{ $remainingExports }} Free {{ \Illuminate\Support\Str::plural('Export', $remainingExports) }} Left</span>
                        </div>
                    @endif

                    <div class="flex p-1 bg-gray-100 rounded-xl border border-gray-200/50">
                        <button type="button" @click="format = 'pdf'"
                            class="flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-[8px] font-black uppercase tracking-wider transition-all duration-200"
                            :class="format === 'pdf' ? 'bg-white text-red-600 shadow-sm scale-105' : 'text-gray-400 hover:text-gray-600'">
                            <i class="fa-solid fa-file-pdf text-[10px]"></i>
                            <span>PDF</span>
                        </button>
                        <button type="button" @click="format = 'docx'"
                            class="flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-[8px] font-black uppercase tracking-wider transition-all duration-200"
                            :class="format === 'docx' ? 'bg-white text-blue-600 shadow-sm scale-105' : 'text-gray-400 hover:text-gray-600'">
                            <i class="fa-solid fa-file-word text-[10px]"></i>
                            <span>Word</span>
                        </button>
                    </div>

                    <button type="submit"
                        class="px-4 py-1.5 bg-indigo-600 text-white rounded-xl font-black text-[8px] uppercase tracking-wider shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center group">
                        <i class="fa-solid fa-download mr-2 text-[10px]"></i>
                        <span x-text="format.toUpperCase()"></span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    @php
        $role = auth()->user()->role;
        $roleValue = $role instanceof \App\Enums\UserRole ? $role->value : $role;
        $targetTier = 'Respondent Pro';
        if ($roleValue === 'independent') {
            $targetTier = 'Pro Researcher';
        } elseif ($roleValue === 'organization') {
            $targetTier = 'Enterprise';
        }
    @endphp

    <!-- Scrollable Draft Content Area -->
    <div class="flex-1 py-6 px-4 sm:px-10 overflow-y-auto custom-scrollbar relative"
        :class="sidebarOpen ? '' : 'flex flex-col items-center'">
        <div
            class="max-w-4xl w-full mx-auto bg-white shadow-2xl shadow-gray-200/50 rounded-lg border border-gray-100 p-8 sm:p-12 min-h-screen mb-12 relative overflow-hidden">

            @if($isTruncated)
                <div
                    class="absolute inset-x-0 bottom-0 h-[70%] bg-gradient-to-t from-white via-white/95 to-transparent flex flex-col items-center justify-end pb-32 px-10 text-center z-50">
                    <div
                        class="bg-white/90 p-10 rounded-[3rem] shadow-2xl border border-gray-100 max-w-lg mb-12 transform scale-110">
                        <div
                            class="w-20 h-20 bg-indigo-600 rounded-2xl flex items-center justify-center text-white mx-auto mb-8 shadow-2xl shadow-indigo-200">
                            <i class="fa-solid fa-lock text-3xl"></i>
                        </div>
                        <h5 class="text-3xl font-black text-gray-900 mb-4 tracking-tighter">Academic Suite Locked</h5>
                        <p class="text-gray-500 text-lg mb-10 leading-relaxed font-medium">Upgrade to <span
                                class="text-indigo-600 font-bold">{{ $targetTier }}</span> to reveal full academic syntheses,
                            exportable drafts, and deep-dive methodology mappings.</p>
                        <a href="{{ route('subscriptions.index') }}"
                            class="inline-flex items-center justify-center w-full px-10 py-5 bg-indigo-600 text-white font-black rounded-2xl hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-100 hover:scale-[1.02] active:scale-[0.98]">
                            Unlock Complete Full Preview
                            <i class="fa-solid fa-arrow-right ml-3 text-sm"></i>
                        </a>
                    </div>
                </div>
            @endif

            <!-- Generated Sections -->
            @php $sectionCount = 0; @endphp
            <div class="space-y-12">
                @foreach($reportData['sections'] as $title => $content)
                    @php
                        $sectionCount++;
                        if ($isTruncated && $sectionCount > $truncateAfter)
                            break;

                        $cleanContentTitle = $normalizeTitle($title);
                        $isChapter = str_starts_with($title, 'CHAPTER') || in_array($title, ['REFERENCES', 'APPENDICES']);
                        $isTitlePage = $title === 'Title Page';
                    @endphp

                    @if($content === '__chapter_header__')
                        {{-- Chapter Divider Page --}}
                        <section id="section-{{ $loop->iteration }}"
                            class="scroll-mt-32 py-24 text-center border-t-2 border-b-2 border-gray-100 my-16 relative">
                            <h2 class="text-2xl font-black text-gray-900 uppercase tracking-widest">{{ __($cleanContentTitle) }}</h2>
                            <div class="w-20 h-1 bg-indigo-600 mx-auto mt-4 rounded-full"></div>
                        </section>
                    @elseif($isTitlePage)
                        {{-- Title Page with raw HTML --}}
                        <section id="section-{{ $loop->iteration }}" class="scroll-mt-32 py-8 border-b-2 border-gray-100 relative">
                            {!! $content !!}
                        </section>
                    @elseif(str_contains($content, "<div class='data-table'") || str_contains($content, "<table"))
                        {{-- Section with embedded data table --}}
                        <section id="section-{{ $loop->iteration }}" class="scroll-mt-32 relative">
                            @if($isChapter)
                                <h2 class="text-xl font-black text-gray-900 mb-8 tracking-tight uppercase leading-none text-center">
                                    {{ __($cleanContentTitle) }}</h2>
                            @else
                                <h3
                                    class="text-lg font-black text-gray-900 mb-6 border-l-4 border-indigo-600 pl-5 tracking-tight leading-snug">
                                    {{ __($cleanContentTitle) }}</h3>
                            @endif
                            <div
                                class="prose prose-slate prose-lg max-w-none text-gray-700 leading-relaxed font-serif text-justify">
                                @php
                                    // Split content into prose and table parts
                                    $parts = preg_split('/(<div class=\'data-table\'.*?<\/div>)/s', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
                                @endphp
                                @foreach($parts as $part)
                                    @if(str_starts_with(trim($part), "<div class='data-table'") || str_starts_with(trim($part), "<table"))
                                        {!! $part !!}
                                    @else
                                        {!! nl2br(e($part)) !!}
                                    @endif
                                @endforeach
                            </div>
                        </section>
                    @else
                        {{-- Standard prose section --}}
                        <section id="section-{{ $loop->iteration }}" class="scroll-mt-32 relative">
                            @if($isChapter)
                                <h2 class="text-xl font-black text-gray-900 mb-8 tracking-tight uppercase leading-none text-center">
                                    {{ __($cleanContentTitle) }}</h2>
                            @elseif(in_array($title, ['Declaration', 'Acknowledgement', 'Abstract', 'Abbreviations', 'Definition of Key Terms']))
                                <h3
                                    class="text-lg font-black text-gray-900 mb-6 tracking-tight uppercase leading-none text-center border-b border-gray-100 pb-4">
                                    {{ __($cleanContentTitle) }}</h3>
                            @else
                                <h3
                                    class="text-lg font-black text-gray-900 mb-6 border-l-4 border-indigo-600 pl-5 tracking-tight leading-snug">
                                    {{ __($cleanContentTitle) }}</h3>
                            @endif
                            <div
                                class="prose prose-slate prose-lg max-w-none text-gray-700 leading-relaxed font-serif text-justify whitespace-pre-line">
                                {!! nl2br(e($content)) !!}
                            </div>
                        </section>
                    @endif
                @endforeach

                @if(empty($reportData['sections']))
                    <div class="text-center py-24 bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200">
                        <i class="fa-solid fa-hourglass-start text-5xl text-gray-200 mb-4"></i>
                        <p class="text-gray-400 font-black uppercase tracking-widest text-[9px]">Populating section content...
                        </p>
                    </div>
                @endif
            </div>

        </div>

        <footer class="max-w-4xl mx-auto py-12 text-center">
            <p class="text-[9px] text-gray-300 font-bold uppercase tracking-[0.3em]">&bull; END OF REPORT &bull;</p>
        </footer>
    </div>
    </div>

    @push('styles')
        <style>
            .custom-scrollbar::-webkit-scrollbar {
                width: 4px;
            }

            .custom-scrollbar::-webkit-scrollbar-track {
                background: transparent;
            }

            .custom-scrollbar::-webkit-scrollbar-thumb {
                background: #E5E7EB;
                border-radius: 10px;
            }

            .prose p {
                margin-bottom: 2rem;
            }

            .prose table {
                font-family: system-ui, sans-serif;
            }

            .prose table th,
            .prose table td {
                padding: 8px 12px;
            }

            /* Ensure section transitions are smooth when clicking sidebar links */
            html {
                scroll-behavior: smooth;
            }
        </style>
    @endpush
@endsection