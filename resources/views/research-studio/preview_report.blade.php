@extends('layouts.app')

@section('title', $report->title . ' — Research Studio Preview')

@php
    if (!function_exists('safe_str')) {
        function safe_str($val): string
        {
            if (is_null($val))
                return '';
            $str = '';
            if (is_scalar($val)) {
                $str = (string) $val;
            } elseif (is_array($val)) {
                $flat = [];
                array_walk_recursive($val, function ($a) use (&$flat) {
                    if (is_scalar($a) && $a !== '')
                        $flat[] = (string) $a;
                });
                $str = implode(', ', $flat);
            }
            // Clean out raw markdown formatting (asterisks, hashtags, backticks)
            $str = preg_replace('/\*{1,3}/', '', $str);
            $str = preg_replace('/^#+\s+/m', '', $str);
            $str = str_replace('`', '', $str);
            return trim($str);
        }
    }
@endphp

@section('content')
    <div class="container-fluid px-4 md:px-8 py-8" x-data="{ activeSection: 'ch1' }">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Action Header -->
            <header
                class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-black text-[#2271b1] tracking-widest">{{ __('Full Report') }}</span>

                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">{{ safe_str($report->title) }}
                    </h1>
                    <p class="text-xs text-gray-500 font-semibold">
                        {{ __('Generated on') }} {{ $report->created_at->format('M d, Y') }} •
                        {{ __('Compiled Chapters 1-5') }}
                    </p>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ route('research-studio.report.history') }}"
                        class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all">
                        <i class="fa-solid fa-clock-rotate-left mr-1.5"></i> {{ __('Report History') }}
                    </a>
                    <a href="{{ route('research-studio.report.download', $report) }}"
                        class="px-6 py-2.5 bg-[#2271b1] hover:bg-[#2271b1] text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-2">
                        <i class="fa-solid fa-file-export"></i> {{ __('Download DOCX') }}
                    </a>
                </div>
            </header>

            <!-- Preview Document Container -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Sidebar Table of Contents -->
                <aside class="lg:col-span-1 space-y-3">
                    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm sticky top-6">
                        <h4 class="text-xs font-black text-gray-400 uppercase tracking-wider mb-4">
                            {{ __('Table of Contents') }}
                        </h4>
                        <nav class="space-y-1">
                            <a href="#front-matter" @click="activeSection = 'front'"
                                :class="activeSection === 'front' ? 'bg-indigo-50 text-indigo-700 font-extrabold' : 'text-gray-600 hover:bg-gray-50 font-semibold'"
                                class="block px-3.5 py-2.5 rounded-xl text-xs transition-all">
                                {{ __('Preliminaries') }}
                            </a>
                            <a href="#chapter-1" @click="activeSection = 'ch1'"
                                :class="activeSection === 'ch1' ? 'bg-indigo-50 text-indigo-700 font-extrabold' : 'text-gray-600 hover:bg-gray-50 font-semibold'"
                                class="block px-3.5 py-2.5 rounded-xl text-xs transition-all">
                                {{ __('Chapter 1: Introduction') }}
                            </a>
                            <a href="#chapter-2" @click="activeSection = 'ch2'"
                                :class="activeSection === 'ch2' ? 'bg-indigo-50 text-indigo-700 font-extrabold' : 'text-gray-600 hover:bg-gray-50 font-semibold'"
                                class="block px-3.5 py-2.5 rounded-xl text-xs transition-all">
                                {{ __('Chapter 2: Literature Review') }}
                            </a>
                            <a href="#chapter-3" @click="activeSection = 'ch3'"
                                :class="activeSection === 'ch3' ? 'bg-indigo-50 text-indigo-700 font-extrabold' : 'text-gray-600 hover:bg-gray-50 font-semibold'"
                                class="block px-3.5 py-2.5 rounded-xl text-xs transition-all">
                                {{ __('Chapter 3: Methodology') }}
                            </a>
                            <a href="#chapter-4" @click="activeSection = 'ch4'"
                                :class="activeSection === 'ch4' ? 'bg-indigo-50 text-indigo-700 font-extrabold' : 'text-gray-600 hover:bg-gray-50 font-semibold'"
                                class="block px-3.5 py-2.5 rounded-xl text-xs transition-all">
                                {{ __('Chapter 4: Data Findings') }}
                            </a>
                            <a href="#chapter-5" @click="activeSection = 'ch5'"
                                :class="activeSection === 'ch5' ? 'bg-indigo-50 text-indigo-700 font-extrabold' : 'text-gray-600 hover:bg-gray-50 font-semibold'"
                                class="block px-3.5 py-2.5 rounded-xl text-xs transition-all">
                                {{ __('Chapter 5: Conclusions') }}
                            </a>
                            <a href="#references-appendices" @click="activeSection = 'ref'"
                                :class="activeSection === 'ref' ? 'bg-indigo-50 text-indigo-700 font-extrabold' : 'text-gray-600 hover:bg-gray-50 font-semibold'"
                                class="block px-3.5 py-2.5 rounded-xl text-xs transition-all">
                                {{ __('References & Appendices') }}
                            </a>
                        </nav>
                    </div>
                </aside>

                @php
                    $ch1to3Paragraphs = [];
                    $refAndAppParagraphs = [];

                    $seenRefHashes = [];
                    $isCurrentlyInRefSection = false;

                    if (is_array($report->proofread_chapters)) {
                        foreach ($report->proofread_chapters as $idx => $p) {
                            $rawText = is_array($p)
                                ? ($p['status'] === 'rejected' ? ($p['original'] ?? '') : ($p['corrected'] ?? $p['original'] ?? ''))
                                : $p;
                            $textStr = trim(safe_str($rawText));

                            if (empty($textStr))
                                continue;

                            // Check if this paragraph starts a Chapter or main content section
                            $isChapterHeading = preg_match('/^(CHAPTER\s+\d+|[1-5]\.\d+|\[SECTION:\s*CHAPTER)/i', $textStr);

                            // Check if this paragraph starts References or Appendices
                            $isRefHeading = preg_match('/^(REFERENCES|BIBLIOGRAPHY|APPENDIX|APPENDICES|\[SECTION:\s*(REFERENCES|BIBLIOGRAPHY|APPENDIX|APPENDICES))/i', $textStr)
                                || (is_array($p) && !empty($p['isHeading']) && preg_match('/^(REFERENCES|BIBLIOGRAPHY)/i', $textStr));

                            if ($isRefHeading) {
                                $isCurrentlyInRefSection = true;
                            } elseif ($isChapterHeading) {
                                $isCurrentlyInRefSection = false;
                            }

                            if ($isCurrentlyInRefSection) {
                                $cleanHashStr = preg_replace('/^(REFERENCES|BIBLIOGRAPHY)\s*:\s*/i', '', $textStr);
                                $refHash = md5(mb_strtolower(trim($cleanHashStr)));

                                if (!isset($seenRefHashes[$refHash])) {
                                    $seenRefHashes[$refHash] = true;
                                    $refAndAppParagraphs[] = $p;
                                }
                            } else {
                                $ch1to3Paragraphs[] = $p;
                            }
                        }
                    }
                @endphp

                <!-- Main Document Viewer -->
                <main class="lg:col-span-3 space-y-8">
                    <!-- Proofread Chapters (1-3) -->
                    <section id="front-matter"
                        class="bg-white rounded-3xl p-8 sm:p-12 border border-gray-100 shadow-sm space-y-6">
                        <div class="border-b border-gray-100 pb-4">
                            <h2 class="text-xl font-black text-gray-900 tracking-tight mt-1">{{ __('Chapters 1–3') }}</h2>
                        </div>

                        <div class="space-y-4 leading-relaxed text-gray-800 text-sm font-normal">
                            @if(!empty($ch1to3Paragraphs))
                                @foreach($ch1to3Paragraphs as $p)
                                    @php
                                        $rawText = is_array($p) ? ($p['status'] === 'rejected' ? ($p['original'] ?? '') : ($p['corrected'] ?? $p['original'] ?? '')) : $p;
                                        $text = safe_str($rawText);
                                        $isHeading = !empty($p['isHeading']) || preg_match('/^(CHAPTER\s+\d+|[1-5]\.\d+)/i', $text);
                                    @endphp
                                    @if(!empty($text))
                                        @if($isHeading)
                                            <h3
                                                class="text-base font-extrabold text-gray-900 pt-4 pb-1 uppercase tracking-tight border-b border-gray-100">
                                                {{ $text }}
                                            </h3>
                                        @else
                                            <p class="text-gray-700 leading-relaxed font-medium">{{ $text }}</p>
                                        @endif
                                    @endif
                                @endforeach
                            @else
                                <p class="text-gray-500 italic">{{ __('No text paragraphs found.') }}</p>
                            @endif
                        </div>
                    </section>

                    <!-- Chapter 4 Findings -->
                    <section id="chapter-4"
                        class="bg-white rounded-3xl p-8 sm:p-12 border border-gray-100 shadow-sm space-y-6">
                        <div class="border-b border-gray-100 pb-4">
                            <span
                                class="text-[10px] font-black text-indigo-600  tracking-widest">{{ __('Chapter 4 ') }}</span>
                            <h2 class="text-xl font-black text-gray-900  tracking-tight mt-1">
                                {{ __('Data Analysis & Interpretation') }}
                            </h2>
                        </div>

                        <div class="space-y-6">
                            @if(is_array($report->chapter4_content))
                                @foreach($report->chapter4_content as $item)
                                    <div class="bg-gray-50/70 rounded-2xl p-6 border border-gray-100 space-y-4">
                                        <h4 class="text-sm font-extrabold text-gray-900">
                                            {{ safe_str($item['label'] ?? 'Survey Item') }}
                                        </h4>

                                        {{-- 5-Column Frequency Table (SPSS Standard) --}}
                                        @if(!empty($item['isChartable']) && !empty($item['stats']) && is_array($item['stats']))
                                            @php
                                                $totalCount = 0;
                                                $validCount = 0;
                                                foreach ($item['stats'] as $s) {
                                                    if (is_array($s)) {
                                                        $c = (int) ($s['count'] ?? 0);
                                                        $totalCount += $c;
                                                        if (empty($s['is_missing']))
                                                            $validCount += $c;
                                                    }
                                                }
                                                if ($validCount === 0)
                                                    $validCount = $totalCount;
                                                $cumValPercent = 0;
                                            @endphp
                                            <div class="bg-white rounded-xl p-4 border border-gray-200/60 overflow-x-auto shadow-xs">
                                                <table class="w-full text-xs text-left">
                                                    <thead>
                                                        <tr class="border-b border-gray-200 text-gray-500 font-black  text-[10px]">
                                                            <th class="py-2 px-3">{{ __('Response Option') }}</th>
                                                            <th class="py-2 px-3 text-right">{{ __('Frequency (N)') }}</th>
                                                            <th class="py-2 px-3 text-right">{{ __('Percent (%)') }}</th>
                                                            <th class="py-2 px-3 text-right">{{ __('Valid %') }}</th>
                                                            <th class="py-2 px-3 text-right">{{ __('Cumulative %') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                                                        @foreach($item['stats'] as $stat)
                                                            @if(is_array($stat))
                                                                @php
                                                                    $isMissing = !empty($stat['is_missing']);
                                                                    $cnt = (int) ($stat['count'] ?? 0);
                                                                    $pct = $totalCount > 0 ? round(($cnt / $totalCount) * 100, 1) : 0;
                                                                    if (!$isMissing) {
                                                                        $valPct = $validCount > 0 ? round(($cnt / $validCount) * 100, 1) : 0;
                                                                        $cumValPercent += $valPct;
                                                                    }
                                                                @endphp
                                                                <tr class="{{ $isMissing ? 'bg-gray-50/50 italic text-gray-400' : '' }}">
                                                                    <td class="py-2 px-3">{{ safe_str($stat['value'] ?? '') }}</td>
                                                                    <td class="py-2 px-3 text-right font-bold">{{ number_format($cnt) }}</td>
                                                                    <td class="py-2 px-3 text-right font-bold text-gray-600">
                                                                        {{ number_format($pct, 1) }}%
                                                                    </td>
                                                                    <td class="py-2 px-3 text-right font-bold text-gray-600">
                                                                        {{ !$isMissing ? number_format($valPct, 1) . '%' : '-' }}
                                                                    </td>
                                                                    <td class="py-2 px-3 text-right font-bold text-gray-600">
                                                                        {{ !$isMissing ? number_format(min($cumValPercent, 100), 1) . '%' : '-' }}
                                                                    </td>
                                                                </tr>
                                                            @endif
                                                        @endforeach
                                                    </tbody>
                                                    <tfoot>
                                                        <tr class="border-t-2 border-gray-200 font-extrabold text-gray-700 text-xs">
                                                            <td class="py-2 px-3 ">{{ __('Total') }}</td>
                                                            <td class="py-2 px-3 text-right">{{ number_format($totalCount) }}</td>
                                                            <td class="py-2 px-3 text-right">100.0%</td>
                                                            <td class="py-2 px-3 text-right">100.0%</td>
                                                            <td class="py-2 px-3 text-right">-</td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                            {{-- Dynamic Canvas Chart Rendering --}}
                                            @php
                                                $cUrl = $item['chartUrl'] ?? $item['chart_url'] ?? null;
                                                $cCfg = $item['chartConfig'] ?? null;

                                                // If chartConfig is missing, parse QuickChart URL to extract configuration on-the-fly
                                                if (empty($cCfg) && !empty($cUrl)) {
                                                    $parsedUrl = parse_url($cUrl);
                                                    if (!empty($parsedUrl['query'])) {
                                                        parse_str($parsedUrl['query'], $queryParams);
                                                        if (!empty($queryParams['c'])) {
                                                            $decodedJson = json_decode($queryParams['c'], true);
                                                            if (is_array($decodedJson)) {
                                                                $cCfg = $decodedJson;
                                                            }
                                                        }
                                                    }
                                                }
                                             @endphp

                                            @if(!empty($cCfg) || !empty($cUrl))
                                                <div
                                                    class="h-80 w-full max-w-4xl mx-auto relative bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
                                                    <canvas id="{{ $item['canvasId'] ?? 'chart-' . ($item['id'] ?? $loop->index) }}"
                                                        class="report-dynamic-chart" data-chart-config="{{ json_encode($cCfg ?? []) }}"
                                                        data-chart-url="{{ $cUrl }}" data-item-label="{{ safe_str($item['label'] ?? '') }}">
                                                    </canvas>
                                                </div>
                                            @endif

                                        @endif

                                        {{-- Qualitative / Text Analysis Rendering --}}
                                        @if(!empty($item['aiInsight']))
                                            @if(is_array($item['aiInsight']))
                                                <div class="space-y-4 bg-indigo-50/40 p-5 rounded-2xl border border-indigo-100/80">
                                                    <span
                                                        class="font-black text-[#2271b1] block  tracking-wider text-[11px]">{{ __('Qualitative Analysis & Thematic Synthesis') }}</span>

                                                    {{-- Sentiment Breakdown --}}
                                                    @if(!empty($item['aiInsight']['sentiment_breakdown']))
                                                        @php
                                                            $pos = (float) ($item['aiInsight']['sentiment_breakdown']['Positive'] ?? 0);
                                                            $neu = (float) ($item['aiInsight']['sentiment_breakdown']['Neutral'] ?? 0);
                                                            $neg = (float) ($item['aiInsight']['sentiment_breakdown']['Negative'] ?? 0);
                                                        @endphp
                                                        <div class="bg-white p-4 rounded-xl border border-indigo-100 shadow-xs space-y-2">
                                                            <h5 class="text-[11px] font-bold text-gray-700  tracking-wider">
                                                                {{ __('Sentiment Distribution') }}
                                                            </h5>
                                                            <div class="flex items-center gap-4 text-xs font-bold">
                                                                <span class="text-emerald-600"><i class="fa-solid fa-face-smile mr-1"></i>
                                                                    Positive: {{ $pos }}%</span>
                                                                <span class="text-amber-600"><i class="fa-solid fa-face-meh mr-1"></i> Neutral:
                                                                    {{ $neu }}%</span>
                                                                <span class="text-rose-600"><i class="fa-solid fa-face-frown mr-1"></i>
                                                                    Negative: {{ $neg }}%</span>
                                                            </div>
                                                            <div class="w-full bg-gray-100 h-2.5 rounded-full overflow-hidden flex">
                                                                <div style="width: {{ $pos }}%" class="bg-emerald-200 h-full"></div>
                                                                <div style="width: {{ $neu }}%" class="bg-amber-200 h-full"></div>
                                                                <div style="width: {{ $neg }}%" class="bg-rose-200 h-full"></div>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    {{-- Key Themes --}}
                                                    @if(!empty($item['aiInsight']['key_themes']) && is_array($item['aiInsight']['key_themes']))
                                                        <div class="bg-white p-4 rounded-xl border border-indigo-100 shadow-xs space-y-2">
                                                            <h5 class="text-[11px] font-bold text-gray-800  tracking-wider">
                                                                {{ __('Extracted Key Themes') }}
                                                            </h5>
                                                            <ul class="space-y-2 text-xs">
                                                                @foreach($item['aiInsight']['key_themes'] as $th)
                                                                    <li class="p-2.5 bg-gray-50 rounded-lg">
                                                                        <strong
                                                                            class="text-[#2271b1] block font-extrabold">{{ safe_str($th['theme'] ?? 'Theme') }}</strong>
                                                                        <span
                                                                            class="text-gray-600 font-medium">{{ safe_str($th['explanation'] ?? '') }}</span>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif

                                                    {{-- Representative Quotes --}}
                                                    @if(!empty($item['aiInsight']['representative_quotes']) && is_array($item['aiInsight']['representative_quotes']))
                                                        <div class="bg-white p-4 rounded-xl border border-[#2271b1] shadow-xs space-y-2">
                                                            <h5 class="text-[11px] font-bold text-gray-800  tracking-wider">
                                                                {{ __('Representative Respondent Excerpts') }}
                                                            </h5>
                                                            <ul class="space-y-1.5 text-xs italic text-gray-700">
                                                                @foreach($item['aiInsight']['representative_quotes'] as $q)
                                                                    <li
                                                                        class="p-2 bg-[#2271b1]/10 border-l-2 border-[#2271b1] rounded-r-lg font-medium">
                                                                        "{{ safe_str($q) }}"
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                </div>
                                            @elseif(is_string($item['aiInsight']))
                                                @php
                                                    $cleanedInsight = str_replace('**', '', $item['aiInsight']);
                                                    $cleanedInsight = preg_replace('/^#+\s+/m', '', $cleanedInsight);
                                                @endphp
                                                <div
                                                    class="text-xs text-gray-700 leading-relaxed bg-indigo-50/50 p-4 rounded-xl border border-[#2271b1]/40 space-y-2">
                                                    <span
                                                        class="font-extrabold text-[#2271b1] block uppercase tracking-wider text-[10px]">{{ __('Academic Interpretation') }}</span>
                                                    <div class="whitespace-pre-line font-medium text-gray-700">
                                                        {{ trim($cleanedInsight) }}
                                                    </div>
                                                </div>
                                            @endif
                                        @elseif(!empty($item['insights']) && is_array($item['insights']))
                                            {{-- Fallback: Raw text insights sample --}}
                                            <div class="bg-white p-4 rounded-xl border border-gray-100 space-y-2">
                                                <span
                                                    class="font-extrabold text-gray-700 block  tracking-wider text-[10px]">{{ __('Qualitative Responses Sample') }}</span>
                                                <ul class="space-y-1 text-xs text-gray-600 italic">
                                                    @foreach(array_slice($item['insights'], 0, 5) as $sample)
                                                        <li>• "{{ safe_str($sample) }}"</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </section>

                    <!-- Chapter 5 Conclusions -->
                    <section id="chapter-5"
                        class="bg-white rounded-3xl p-8 sm:p-12 border border-gray-100 shadow-sm space-y-6">
                        <div class="border-b border-gray-100 pb-4">
                            <span
                                class="text-[10px] font-black text-indigo-600  tracking-widest">{{ __('Chapter 5 Synthesis') }}</span>
                            <h2 class="text-xl font-black text-gray-900  tracking-tight mt-1">
                                {{ __('Summary, Conclusions & Recommendations') }}
                            </h2>
                        </div>

                        <div class="space-y-6 text-sm text-gray-800 font-medium leading-relaxed">
                            @if(is_array($report->chapter5_content))
                                @foreach($report->chapter5_content as $secTitle => $secBody)
                                    <div class="space-y-2">
                                        <h3
                                            class="text-base font-extrabold text-gray-900  tracking-tight border-b border-gray-100 pb-2">
                                            {{ safe_str($secTitle) }}
                                        </h3>
                                        <p class="text-gray-700">{!! nl2br(e(safe_str($secBody))) !!}</p>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </section>

                    <!-- References & Appendices (Always placed after Chapter 5 at the end of report) -->
                    @if(!empty($refAndAppParagraphs))
                        <section id="references-appendices"
                            class="bg-white rounded-3xl p-8 sm:p-12 border border-gray-100 shadow-sm space-y-6">
                            <div class="border-b border-gray-100 pb-4">
                                <h2 class="text-xl font-black text-gray-900 tracking-tight mt-1">
                                    {{ __('References & Appendices') }}
                                </h2>
                            </div>

                            <ul class="space-y-4 leading-relaxed text-gray-700 text-sm font-normal list-none pl-0">
                                @foreach($refAndAppParagraphs as $p)
                                    @php
                                        $rawText = is_array($p) ? ($p['status'] === 'rejected' ? ($p['original'] ?? '') : ($p['corrected'] ?? $p['original'] ?? '')) : $p;
                                        $text = safe_str($rawText);
                                        $text = str_replace('**', '', $text);
                                        $isHeading = !empty($p['isHeading']) || preg_match('/^(REFERENCES|BIBLIOGRAPHY|APPENDIX|APPENDICES|\[SECTION:\s*(REFERENCES|BIBLIOGRAPHY|APPENDIX|APPENDICES))/i', trim($text));
                                        $isSubHeading = preg_match('/^(APPENDIX\s+[A-Z]:|1\.\s+What|2\.\s+What|3\.\s+What)/i', trim($text));
                                    @endphp
                                    @if(!empty($text))
                                        @if($isHeading)
                                            <li
                                                class="pt-3 pb-1 border-b border-gray-100 font-bold text-gray-900 text-sm uppercase tracking-wider">
                                                {{ $text }}
                                            </li>
                                        @elseif($isSubHeading)
                                            <li class="pt-2 pb-1 font-semibold text-gray-800 text-sm">
                                                {{ $text }}
                                            </li>
                                        @else
                                            {{-- Added hanging indent styling (pl-6 -indent-6) for standard APA reference formatting --}}
                                            <li class="text-gray-700 leading-relaxed font-normal text-xs sm:text-sm pl-6 -indent-6">
                                                {{ $text }}
                                            </li>
                                        @endif
                                    @endif
                                @endforeach
                            </ul>
                        </section>
                    @endif
                </main>
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                    document.addEventListener("DOMContentLoaded", function () {
                        // Custom plugin matching survey reports to render percentage clearly ABOVE top of bars
                        const previewDatalabelsPlugin = {
                            id: 'previewDatalabels',
                            afterDatasetsDraw(chart) {
                                const { ctx } = chart;
                                ctx.save();
                                chart.data.datasets.forEach((dataset, i) => {
                                    const meta = chart.getDatasetMeta(i);
                                    meta.data.forEach((element, index) => {
                                        const rawVal = dataset.data[index];
                                        if (rawVal === undefined || rawVal === null) return;
                                        const numVal = (typeof rawVal === 'number') ? rawVal : parseFloat(String(rawVal).replace('%', ''));
                                        if (isNaN(numVal)) return;
                                        const text = `${numVal % 1 === 0 ? numVal.toFixed(0) : numVal.toFixed(1)}%`;

                                        ctx.fillStyle = '#374151';
                                        ctx.font = 'bold 11px Inter, system-ui, sans-serif';
                                        ctx.textAlign = 'center';
                                        ctx.textBaseline = 'bottom';
                                        // Draw precisely 8px ABOVE top edge of the bar element
                                        ctx.fillText(text, element.x, element.y - 8);
                                    });
                                });
                                ctx.restore();
                            }
                        };

                        document.querySelectorAll('.report-dynamic-chart').forEach(canvas => {
                            try {
                                const rawConfig = canvas.getAttribute('data-chart-config');
                                let configData = rawConfig ? JSON.parse(rawConfig) : null;
                                const questionLabel = canvas.getAttribute('data-item-label') || '';

                                // Fallback: If config is empty, attempt to parse chartUrl query param 'c'
                                if ((!configData || Object.keys(configData).length === 0) && canvas.hasAttribute('data-chart-url')) {
                                    const urlStr = canvas.getAttribute('data-chart-url');
                                    if (urlStr) {
                                        const url = new URL(urlStr);
                                        const cParam = url.searchParams.get('c');
                                        if (cParam) {
                                            configData = JSON.parse(cParam);
                                        }
                                    }
                                }

                                if (configData && (configData.data || configData.type)) {
                                    if (!configData.type) configData.type = 'bar';

                                    // Convert raw counts to percentages if needed
                                    if (configData.data && configData.data.datasets && configData.data.datasets[0]) {
                                        const dataset = configData.data.datasets[0];
                                        const rawData = dataset.data || [];
                                        const total = rawData.reduce((acc, val) => acc + (parseFloat(val) || 0), 0);

                                        if (total > 0 && Math.max(...rawData) > 100) {
                                            dataset.data = rawData.map(val => parseFloat(((val / total) * 100).toFixed(1)));
                                        }
                                    }

                                    const maxVal = Math.max(...(configData.data.datasets[0].data || [0]));
                                    const dynamicMax = Math.min(100, Math.ceil((maxVal * 1.35) / 5) * 5); // Extended padding for label

                                    configData.options = Object.assign({
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        layout: {
                                            padding: { top: 30, bottom: 10, left: 10, right: 10 }
                                        },
                                        plugins: {
                                            legend: { display: false }
                                        },
                                        scales: {
                                            x: {
                                                title: {
                                                    display: !!questionLabel,
                                                    text: questionLabel,
                                                    font: { weight: 'bold', size: 12 },
                                                    color: '#4b5563'
                                                },
                                                grid: { display: false }
                                            },
                                            y: {
                                                beginAtZero: true,
                                                max: dynamicMax,
                                                title: {
                                                    display: true,
                                                    text: 'Percentage (%)',
                                                    font: { weight: 'bold', size: 12 },
                                                    color: '#4b5563'
                                                },
                                                ticks: {
                                                    callback: (value) => value + '%'
                                                }
                                            }
                                        }
                                    }, configData.options || {});

                                    configData.plugins = [previewDatalabelsPlugin];

                                    new Chart(canvas.getContext('2d'), configData);
                                }
                            } catch (err) {
                                console.error("Error rendering dynamic canvas chart:", err);
                            }
                        });
                    });
                </script>
            </div>
        </div>

        <!-- Floating Scroll Control Stack -->
        <div x-data="{ 
                            showTop: false, 
                            showBottom: false,
                            checkScroll() {
                                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                                const scrollHeight = document.documentElement.scrollHeight;
                                const clientHeight = window.innerHeight || document.documentElement.clientHeight;

                                this.showTop = scrollTop > 300;
                                this.showBottom = (clientHeight + scrollTop) < (scrollHeight - 300);
                            }
                        }" x-init="checkScroll()" @scroll.window.throttle.50ms="checkScroll()"
            class="fixed bottom-40 left-6 z-[999] flex flex-col gap-2">

            <button x-show="showTop" x-transition @click="window.scrollTo({top: 0, behavior: 'smooth'})"
                class="w-10 h-10 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-full shadow-xl flex items-center justify-center transition-all cursor-pointer border border-white/20">
                <i class="fa-solid fa-arrow-up text-sm"></i>
            </button>

            <button x-show="showBottom" x-transition
                @click="window.scrollTo({top: document.documentElement.scrollHeight, behavior: 'smooth'})"
                class="w-10 h-10 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-full shadow-xl flex items-center justify-center transition-all cursor-pointer border border-white/20">
                <i class="fa-solid fa-arrow-down text-sm"></i>
            </button>
        </div>
    </div>
@endsection