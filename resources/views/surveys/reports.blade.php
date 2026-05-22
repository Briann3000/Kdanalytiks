@extends('surveys.hub')

@section('survey-content')
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #cbd5e1;
        }
    </style>
    @php
        $allowedReportTabs = ['quantitative', 'qualitative', 'crosstab'];
        if (!isset($isSharedView) || !$isSharedView) {
            $allowedReportTabs[] = 'analyse';
        }
        $requestedReportTab = request('reportTab', 'quantitative');
        $initialReportTab = in_array($requestedReportTab, $allowedReportTabs, true) ? $requestedReportTab : 'quantitative';
    @endphp

    <div class="space-y-12" x-data="{
            reportTab: @js($initialReportTab),
            switchReportTab(tab) {
                this.reportTab = tab;
                const url = new URL(window.location.href);
                url.searchParams.set('reportTab', tab);
                if (tab !== 'analyse') {
                    url.searchParams.delete('thread');
                }
                window.history.replaceState({}, '', url);
            }
        }">
        <!-- Sub Navigation -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
            <div class="flex items-center gap-2 bg-gray-100/50 p-1 rounded-2xl w-fit">
                <button @click="switchReportTab('quantitative')"
                    :class="reportTab === 'quantitative' ? 'bg-white text-indigo-700 shadow-sm border border-indigo-50' : 'text-gray-500 hover:text-gray-700'"
                    class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all">
                    <i class="fa-solid fa-chart-column mr-2"></i>
                    {{ __('Quantitative') }}
                </button>
                <button @click="switchReportTab('qualitative')"
                    :class="reportTab === 'qualitative' ? 'bg-white text-indigo-700 shadow-sm border border-indigo-50' : 'text-gray-500 hover:text-gray-700'"
                    class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all">
                    <i class="fa-solid fa-comments mr-2"></i>
                    {{ __('Qualitative') }}
                </button>
                <button @click="switchReportTab('crosstab')"
                    :class="reportTab === 'crosstab' ? 'bg-white text-indigo-700 shadow-sm border border-indigo-50' : 'text-gray-500 hover:text-gray-700'"
                    class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all">
                    <i class="fa-solid fa-table-cells mr-2"></i>
                    {{ __('Cross-Tabulation') }}
                    @if(!auth()->check() || !auth()->user()->hasActiveSubscription())
                        <i class="fa-solid fa-lock ml-2 text-[10px] opacity-50"></i>
                    @endif
                </button>
                @if(!isset($isSharedView) || !$isSharedView)
                    <button @click="switchReportTab('analyse')"
                        :class="reportTab === 'analyse' ? 'bg-white text-indigo-700 shadow-sm border border-indigo-50' : 'text-gray-500 hover:text-gray-700'"
                        class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all">
                        <i class="fa-solid fa-sparkles mr-2"></i>
                        {{ __('Analyse') }}
                    </button>
                @endif
            </div>

            <div class="flex items-center gap-3">
                @if(!isset($isSharedView) || !$isSharedView)
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open"
                            class="inline-flex items-center gap-2 px-5 py-3 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                            <i class="fa-solid fa-file-arrow-down text-sm"></i>
                            {{ __('Export') }}
                            <i class="fa-solid fa-chevron-down text-[9px] transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open" x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-44 bg-white border border-gray-100 rounded-2xl shadow-2xl shadow-gray-200/60 z-50 overflow-hidden"
                            style="display:none;">
                            <a href="{{ route('surveys.export_pdf', $survey) }}"
                                class="flex items-center gap-3 px-4 py-3 text-xs font-semibold text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                                <i class="fa-solid fa-file-pdf text-red-500 w-4"></i>
                                {{ __('PDF Report') }}
                            </a>
                            <a href="{{ route('surveys.export_docx', $survey) }}"
                                class="flex items-center gap-3 px-4 py-3 text-xs font-semibold text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors border-t border-gray-50">
                                <i class="fa-solid fa-file-word text-blue-600 w-4"></i>
                                {{ __('DOCX Report') }}
                            </a>
                            @if(auth()->user()->hasActiveSubscription())
                            <a href="{{ route('surveys.export_xlsx', $survey) }}"
                                class="flex items-center gap-3 px-4 py-3 text-xs font-semibold text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors border-t border-gray-50">
                                <i class="fa-solid fa-file-excel text-green-600 w-4"></i>
                                {{ __('Excel (.xlsx)') }}
                            </a>
                            @else
                            <div class="flex items-center gap-3 px-4 py-3 text-xs font-semibold text-gray-400 border-t border-gray-50 cursor-not-allowed" title="{{ __('Pro/Enterprise feature') }}">
                                <i class="fa-solid fa-file-excel text-gray-300 w-4"></i>
                                {{ __('Excel (.xlsx)') }}
                                <i class="fa-solid fa-lock text-[9px] ml-auto opacity-50"></i>
                            </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="px-6 py-3 bg-indigo-50 text-indigo-700 rounded-xl font-black text-[10px] uppercase tracking-widest flex items-center gap-2">
                        <i class="fa-solid fa-lock"></i> {{ __('Read-Only Live View') }}
                    </div>
                @endif
            </div>
        </div>





        <!-- Quantitative Content -->
        <div x-show="reportTab === 'quantitative'" class="space-y-8 animate-in fade-in duration-500"
            x-data="chartManager()">
            @foreach($analysis as $item)
                @if($item['isChartable'])
                    <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm">
                        <div class="mb-8 border-b border-gray-50 pb-6 flex justify-between items-end">
                            <h4 class="text-xl font-black text-gray-900 tracking-tight">
                                <span class="text-indigo-600 mr-2 opacity-30 text-base font-black">#{{ $loop->iteration }}</span>
                                {{ $item['label'] }}
                            </h4>
                            <div class="flex flex-col gap-2">
                                <div class="flex gap-1 bg-gray-100 p-1 rounded-lg border border-gray-100">
                                    @foreach(['bar', 'horizontal', 'line', 'area', 'pie', 'doughnut', 'polarArea', 'radar'] as $type)
                                        <button @click="switchChartType('{{ $item['canvasId'] }}', '{{ $type }}')"
                                            :class="chartTypes['{{ $item['canvasId'] }}'] === '{{ $type }}' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-600'"
                                            class="p-1 px-2 rounded-md text-[9px] font-black uppercase transition-all"
                                            title="{{ ucfirst($type) }}">
                                            {{ substr($type, 0, 3) }}
                                        </button>
                                    @endforeach
                                </div>
                                <div class="flex justify-end gap-1">
                                    @foreach(['vibrant', 'indigo', 'emerald', 'rose', 'amber', 'purple'] as $color)
                                        <button @click="switchColor('{{ $item['canvasId'] }}', '{{ $color }}')"
                                            class="w-4 h-4 rounded-full border border-white ring-1 ring-gray-200 transition-transform hover:scale-125 shadow-sm"
                                            :class="{
                                                                'bg-gradient-to-br from-indigo-500 via-emerald-500 to-rose-500': '{{ $color }}' === 'vibrant',
                                                                'bg-indigo-500': '{{ $color }}' === 'indigo',
                                                                'bg-emerald-500': '{{ $color }}' === 'emerald',
                                                                'bg-rose-500': '{{ $color }}' === 'rose',
                                                                'bg-amber-500': '{{ $color }}' === 'amber',
                                                                'bg-purple-500': '{{ $color }}' === 'purple',
                                                                'ring-2 ring-offset-2 ring-indigo-600 scale-125': activeColors['{{ $item['canvasId'] }}'] === '{{ $color }}'
                                                            }">
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-12">
                            <!-- Chart Area (Full Width) -->
                            <div
                                class="h-96 relative flex items-center justify-center bg-gray-50/30 rounded-3xl p-8 border border-gray-100 shadow-inner">
                                <canvas id="{{ $item['canvasId'] }}"></canvas>
                            </div>

                            <!-- Table Area (Below Chart) -->
                            <div class="overflow-hidden bg-white rounded-2xl border border-gray-100">
                                <div class="max-h-[300px] overflow-y-auto custom-scrollbar">
                                    <table class="w-full text-left">
                                        <thead class="sticky top-0 bg-white z-10 border-b border-gray-100 shadow-sm">
                                            <tr class="text-[10px] font-black text-gray-500 uppercase tracking-widest">
                                                <th class="py-4 px-6">{{ __('Value') }}</th>
                                                <th class="py-4 px-6 text-right">{{ __('Frequency') }}</th>
                                                <th class="py-4 px-6 text-right">{{ __('Ratio') }}</th>
                                            </tr>
                                        </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @php 
                                            $totalFreq = 0;
                                            $totalRatio = 0;
                                        @endphp
                                        @foreach($item['stats'] as $stat)
                                            @php 
                                                $totalFreq += $stat['count'];
                                                $totalRatio += (float)$stat['percentage'];
                                            @endphp
                                            <tr class="hover:bg-gray-50/30 transition-colors">
                                                <td class="py-4 px-6 text-[11px] font-black text-gray-700 uppercase tracking-tight">
                                                    {{ $stat['value'] }}</td>
                                                <td class="py-4 px-6 text-right text-[11px] font-black text-gray-900">{{ number_format($stat['count']) }}
                                                </td>
                                                <td class="py-4 px-6 text-right">
                                                    <span
                                                        class="text-[11px] font-black text-indigo-600">{{ $stat['percentage'] }}%</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-indigo-50/30 border-t-2 border-indigo-100">
                                        <tr class="font-black text-indigo-900">
                                            <td class="py-4 px-6 text-[11px] uppercase tracking-widest">{{ __('Total') }}</td>
                                            <td class="py-4 px-6 text-right text-[11px]">{{ number_format($totalFreq) }}</td>
                                            <td class="py-4 px-6 text-right text-[11px]">{{ round($totalRatio) }}%</td>
                                        </tr>
                                    </tfoot>
                                </table>
                                </div>
                            </div>

                            @if($canAnalyze)
                                <x-ai-quant-insight-card :question-id="$item['id']" :survey-id="$survey->id" :stats="$item['stats']" />
                            @else
                                <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 text-center mt-6">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ __('Quantitative AI Insights') }}</p>
                                    <p class="text-xs text-gray-500 font-medium">{{ __('Upgrade to Pro to unlock automated trend interpretation for numerical data.') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- Qualitative Content -->
        <div x-show="reportTab === 'qualitative'" class="space-y-12 animate-in fade-in duration-500" style="display: none;">
            @foreach($analysis as $item)
                @if(!$item['isChartable'])
                    <div class="bg-white rounded-3xl p-10 shadow-sm border border-gray-100">
                        <div class="mb-8 flex justify-between items-center border-b border-gray-50 pb-6">
                            <h4 class="text-2xl font-black text-gray-900 tracking-tight">
                                <span class="text-indigo-600 mr-2 opacity-30 text-base font-black">#{{ $loop->iteration }}</span>
                                {{ $item['label'] }}
                            </h4>
                        </div>

                        @if($item['isAnalyzable'])
                            <x-ai-insight-card :question-id="$item['id']" :question-title="$item['label']"
                                :survey-id="$item['survey_id']" :index="$loop->index" />
                        @endif

                        <div class="mt-6 bg-gray-50 rounded-3xl overflow-hidden border border-gray-100">
                            <div class="px-8 py-6 border-b border-gray-100 bg-white flex justify-between items-center">
                                <h5 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    {{ __('Detailed Responses (Verbatims)') }}</h5>
                                <span class="text-[10px] font-black text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full uppercase">
                                    {{ count($item['answers'] ?? []) }} {{ __('Total Entries') }}
                                </span>
                            </div>
                            <div class="max-h-[350px] overflow-y-auto custom-scrollbar">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50/50 text-[10px] font-black text-gray-500 uppercase tracking-widest sticky top-0 bg-white border-b border-gray-100 z-10">
                                            <th class="py-4 px-8 w-16 text-center">#</th>
                                            <th class="py-4 px-8">{{ __('Response Content') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @forelse($item['answers'] ?? [] as $answer)
                                            <tr class="hover:bg-indigo-50/20 transition-colors">
                                                <td class="py-6 px-8 text-center text-[10px] font-black text-gray-300">
                                                    {{ $loop->iteration }}
                                                </td>
                                                <td class="py-6 px-8">
                                                    <div class="text-[13px] font-medium text-gray-700 leading-relaxed italic border-l-4 border-indigo-500 pl-4">
                                                        @php
                                                            $displayAnswer = '"' . $answer . '"';
                                                            $isSignature = str_contains($answer, 'base64,');
                                                            $isMedia = str_starts_with($answer, 'uploads/');
                                                        @endphp

                                                        @if($isSignature)
                                                            <button onclick="Swal.fire({
                                                                title: '{{ __("Signature Preview") }}',
                                                                imageUrl: '{{ $answer }}',
                                                                imageAlt: 'Signature',
                                                                customClass: {
                                                                    popup: 'rounded-[3rem] border-none shadow-2xl',
                                                                    image: 'rounded-2xl border border-gray-100 shadow-sm max-h-[400px] w-auto'
                                                                },
                                                                confirmButtonText: '{{ __("Close") }}',
                                                                confirmButtonColor: '#4f46e5'
                                                            })" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-700 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-100 transition-all">
                                                                <i class="fa-solid fa-signature text-sm"></i>
                                                                {{ __('View Captured Signature') }}
                                                            </button>
                                                        @elseif($isMedia)
                                                            <a href="{{ asset('storage/' . $answer) }}" target="_blank"
                                                               class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-700 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-emerald-100 transition-all">
                                                                <i class="fa-solid fa-play-circle text-sm"></i>
                                                                {{ __('Open Media File') }}
                                                            </a>
                                                        @else
                                                            "{{ $answer }}"
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="py-20 text-center">
                                                    <i class="fa-solid fa-comment-slash text-4xl mb-4 text-gray-200"></i>
                                                    <p class="text-gray-400 italic text-xs uppercase font-black tracking-widest">
                                                        {{ __('No text data collected for this question') }}
                                                    </p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        @if(!isset($isSharedView) || !$isSharedView)
            @include('surveys.partials.report_analyse')
        @endif

        <!-- Crosstab Content -->
        <div x-show="reportTab === 'crosstab'" class="space-y-8 animate-in fade-in duration-500" style="display: none;">
            <div x-data="crosstabManager()" class="space-y-8">
                <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm relative overflow-hidden">
                    <div class="mb-8 border-b border-gray-50 pb-6">
                        <h4 class="text-xl font-black text-gray-900 tracking-tight">{{ __('Cross-Tabulation Analysis') }}</h4>
                        <p class="text-xs text-gray-500 mt-2">{{ __('Compare two variables to discover correlations and hidden patterns.') }}</p>
                    </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">{{ __('Row Variable') }}</label>
                        <select x-model="rowVar" class="w-full bg-gray-50 border border-gray-200 text-sm font-medium rounded-xl px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                            <option value="">{{ __('Select Question...') }}</option>
                            @foreach($analysis as $item)
                                @if($item['isChartable'])
                                    <option value="{{ $item['id'] }}">{{ \Illuminate\Support\Str::limit($item['label'], 60) }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">{{ __('Column Variable') }}</label>
                        <select x-model="colVar" class="w-full bg-gray-50 border border-gray-200 text-sm font-medium rounded-xl px-4 py-3 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                            <option value="">{{ __('Select Question...') }}</option>
                            @foreach($analysis as $item)
                                @if($item['isChartable'])
                                    <option value="{{ $item['id'] }}">{{ \Illuminate\Support\Str::limit($item['label'], 60) }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex justify-end border-t border-gray-50 pt-6">
                    <button @click="generateMatrix()" :disabled="!rowVar || !colVar || loading"
                        class="px-8 py-3 bg-indigo-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <i class="fa-solid fa-table-cells" :class="{'fa-spin': loading}"></i> <span x-text="loading ? '{{ __('Generating...') }}' : '{{ __('Generate Matrix') }}'"></span>
                    </button>
                </div>

                <!-- Matrix Results -->
                <template x-if="matrixData && matrixData.rows && matrixData.rows.length > 0">
                    <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm animate-in fade-in duration-500">
                        <div class="overflow-x-auto mb-8">
                            <table class="w-full text-left border-collapse min-w-[600px]">
                                <thead>
                                    <tr>
                                        <th class="p-4 border-b border-r border-gray-200 bg-gray-50 w-1/4"></th>
                                        <template x-for="col in matrixData?.columns || []" :key="col">
                                            <th class="p-4 border-b border-gray-200 bg-gray-50 text-[10px] font-black text-gray-600 uppercase tracking-widest text-center" x-text="col"></th>
                                        </template>
                                        <th class="p-4 border-b border-l border-gray-200 bg-indigo-50 text-[10px] font-black text-indigo-800 uppercase tracking-widest text-center">{{ __('Total') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="row in matrixData?.rows || []" :key="row">
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <th class="p-4 border-b border-r border-gray-200 text-[11px] font-black text-gray-700" x-text="row"></th>
                                            <template x-for="col in matrixData?.columns || []" :key="col">
                                                <td class="p-4 border-b border-gray-100 text-center text-sm font-medium text-gray-600" x-text="getMatrixValue(row, col)"></td>
                                            </template>
                                            <td class="p-4 border-b border-l border-gray-200 bg-indigo-50/30 text-center text-sm font-black text-indigo-700" x-text="(matrixData?.rowTotals || {})[row] || 0"></td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th class="p-4 border-t border-r border-gray-200 bg-indigo-50 text-[10px] font-black text-indigo-800 uppercase tracking-widest">{{ __('Total') }}</th>
                                        <template x-for="col in matrixData?.columns || []" :key="col">
                                            <th class="p-4 border-t border-gray-200 bg-indigo-50 text-center text-sm font-black text-indigo-800" x-text="(matrixData?.colTotals || {})[col] || 0"></th>
                                        </template>
                                        <th class="p-4 border-t border-l border-indigo-200 bg-indigo-100 text-center text-sm font-black text-indigo-900" x-text="matrixData?.grandTotal || 0"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>


            <!-- AI Correlation Intelligence -->
            <div class="mt-8 pt-8 border-t border-gray-100 flex flex-col items-center">
                <div x-show="aiLoading" class="flex items-center gap-3 text-indigo-600 py-4">
                    <i class="fa-solid fa-circle-notch fa-spin text-xl"></i>
                    <span class="text-[10px] font-black uppercase tracking-widest">{{ __('Analyzing Correlation Patterns...') }}</span>
                </div>
                
                <div x-show="aiInsight" class="w-full bg-gradient-to-br from-indigo-50 to-white rounded-3xl p-8 border border-indigo-100 shadow-inner relative overflow-hidden" style="display: none;">
                    <i class="fa-solid fa-brain absolute right-[-20px] top-[-20px] text-[120px] text-indigo-600/5"></i>
                    <div class="relative z-10 flex flex-col md:flex-row gap-6 items-start">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center text-white shrink-0 shadow-lg shadow-indigo-200">
                            <i class="fa-solid fa-wand-magic-sparkles text-lg"></i>
                        </div>
                        <div>
                            <h5 class="text-[10px] font-black text-indigo-600 uppercase tracking-widest mb-3">{{ __('Correlation Intelligence') }}</h5>
                            <div class="text-[13px] text-gray-700 leading-relaxed font-medium whitespace-pre-line" x-text="aiInsight"></div>
                        </div>
                    </div>
                </div>

                <button x-show="!aiLoading && !aiInsight && matrixData && matrixData.rows && matrixData.rows.length > 0" @click="getCorrelationIntelligence()"
                    class="px-8 py-4 bg-indigo-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 flex items-center gap-3 group">
                    <i class="fa-solid fa-brain group-hover:scale-110 transition-transform"></i> {{ __('Correlation Intelligence') }}
                </button>
            </div>
        </template>
    </div>
</div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const chartConfigs = {!! json_encode($chartConfigs) !!};
            const chartInstances = {};

            const colorPalettes = {
                indigo: ['#4f46e5', '#6366f1', '#818cf8', '#a5b4fc', '#c7d2fe', '#e0e7ff', '#3730a3', '#312e81'],
                emerald: ['#10b981', '#059669', '#34d399', '#6ee7b7', '#a7f3d0', '#d1fae5', '#065f46', '#064e3b'],
                rose: ['#f43f5e', '#e11d48', '#fb7185', '#fda4af', '#fecdd3', '#fff1f2', '#9f1239', '#881337'],
                amber: ['#f59e0b', '#d97706', '#fbbf24', '#fcd34d', '#fde68a', '#fef3c7', '#b45309', '#92400e'],
                purple: ['#8b5cf6', '#7c3aed', '#a78bfa', '#c4b5fd', '#ddd6fe', '#ede9fe', '#5b21b6', '#4c1d95'],
                vibrant: ['#6366f1', '#10b981', '#f43f5e', '#f59e0b', '#8b5cf6', '#06b6d4', '#ec4899', '#f97316']
            };

            function createChart(canvasId, config, type = 'bar', colorTheme = 'indigo') {
                const canvasElement = document.getElementById(canvasId);
                if (!canvasElement) return;
                const ctx = canvasElement.getContext('2d');

                const palette = colorPalettes[colorTheme];
                // Ensure we have enough colors by repeating the palette if needed
                const colors = config.labels.map((_, i) => palette[i % palette.length]);
                const primaryColor = palette[0];

                let chartType = type;
                let indexAxis = 'x';
                let fill = false;

                if (type === 'horizontal') {
                    chartType = 'bar';
                    indexAxis = 'y';
                } else if (type === 'area') {
                    chartType = 'line';
                    fill = true;
                }

                const isCategorical = ['pie', 'doughnut', 'polarArea', 'bar', 'horizontal'].includes(type);

                const chartConfig = {
                    type: chartType,
                    data: {
                        labels: config.labels,
                        datasets: [{
                            label: 'Responses',
                            data: config.data,
                            backgroundColor: isCategorical ? colors : (fill ? `${primaryColor}44` : primaryColor),
                            borderColor: isCategorical ? (type === 'bar' || type === 'horizontal' ? colors : '#fff') : primaryColor,
                            borderWidth: (type === 'line' || type === 'radar' || type === 'area') ? 3 : 1,
                            fill: fill,
                            borderRadius: (chartType === 'bar') ? 6 : 0,
                            tension: 0.4,
                            pointBackgroundColor: primaryColor,
                            pointRadius: 4
                        }]
                    },
                    options: {
                        indexAxis: indexAxis,
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: ['pie', 'doughnut', 'polarArea', 'radar'].includes(type),
                                position: 'bottom',
                                labels: {
                                    boxWidth: 10,
                                    padding: 15,
                                    font: { weight: '700', size: 9, family: 'Inter' },
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                padding: 12,
                                titleFont: { size: 12, weight: '800' },
                                bodyFont: { size: 12, weight: '600' },
                                cornerRadius: 12,
                                displayColors: true
                            }
                        }
                    }
                };

                if (chartType === 'bar' || chartType === 'line') {
                    chartConfig.options.scales = {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f8fafc', drawBorder: false },
                            ticks: { font: { weight: '600', size: 10, color: '#64748b' } }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { weight: '600', size: 10, color: '#64748b' } }
                        }
                    };
                }

                if (type === 'radar') {
                    chartConfig.options.scales = {
                        r: {
                            grid: { color: '#f1f5f9' },
                            angleLines: { color: '#f1f5f9' },
                            pointLabels: { font: { weight: '700', size: 10 } },
                            ticks: { display: false }
                        }
                    };
                }

                return new Chart(ctx, chartConfig);
            }

            window.crosstabManager = function () {
                return {
                    rowVar: '',
                    colVar: '',
                    loading: false,
                    aiLoading: false,
                    aiInsight: null,
                    matrixData: null,

                    async generateMatrix() {
                        if (!this.rowVar || !this.colVar) return;
                        this.loading = true;
                        this.matrixData = null;
                        this.aiInsight = null;

                        try {
                            const res = await fetch(`{{ route('surveys.reports.crosstab', $survey) }}?row=${this.rowVar}&col=${this.colVar}`);
                            if (!res.ok) throw new Error('Failed to generate matrix');
                            const data = await res.json();
                            this.matrixData = data.results;
                            this.matrixData.columns = data.results.cols;
                            this.matrixData.rowLabel = data.rowLabel;
                            this.matrixData.colLabel = data.colLabel;
                        } catch (err) {
                            alert(err.message);
                        } finally {
                            this.loading = false;
                        }
                    },

                    getMatrixValue(row, col) {
                        if (this.matrixData && this.matrixData.matrix[row] && this.matrixData.matrix[row][col] !== undefined) {
                            return this.matrixData.matrix[row][col];
                        }
                        return 0;
                    },

                    async getCorrelationIntelligence() {
                        if (!this.matrixData) return;
                        this.aiLoading = true;
                        
                        try {
                            const res = await fetch(`{{ route('ai.insights.crosstab') }}?survey_id={{ $survey->id }}`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({
                                    matrix: this.matrixData.matrix,
                                    rowLabel: this.matrixData.rowLabel,
                                    colLabel: this.matrixData.colLabel
                                })
                            });
                            
                            const data = await res.json();
                            if (data.error) throw new Error(data.error);
                            this.aiInsight = data.insight;
                        } catch (err) {
                            alert("Correlation Engine Error: " + err.message);
                        } finally {
                            this.aiLoading = false;
                        }
                    }
                }
            };

            window.chartManager = function () {
                return {
                    chartTypes: {},
                    activeColors: {},
                    init() {
                        chartConfigs.forEach(config => {
                            this.chartTypes[config.canvas_id] = 'bar';
                            this.activeColors[config.canvas_id] = 'vibrant';
                            chartInstances[config.canvas_id] = createChart(config.canvas_id, config, 'bar', 'vibrant');
                        });
                    },
                    switchChartType(canvasId, type) {
                        this.chartTypes[canvasId] = type;
                        this.refreshChart(canvasId);
                    },
                    switchColor(canvasId, color) {
                        this.activeColors[canvasId] = color;
                        this.refreshChart(canvasId);
                    },
                    refreshChart(canvasId) {
                        const config = chartConfigs.find(c => c.canvas_id === canvasId);
                        if (chartInstances[canvasId]) chartInstances[canvasId].destroy();
                        chartInstances[canvasId] = createChart(canvasId, config, this.chartTypes[canvasId], this.activeColors[canvasId]);
                    }
                }
            };

            window.sociusManager = function (config) {
                return {
                    canAnalyze: config.canAnalyze,
                    currentThreadId: config.initialThreadId || null,
                    currentThread: null,
                    threads: [],
                    messages: [],
                    draft: '',
                    pendingFiles: [],
                    includeSurveyContext: true,
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

                    // Phase 4 Features
                    isListening: false,
                    recognition: null,
                    editingMessageId: null,
                    editingContent: '',
                    isRegenerating: false,
                    tokenUsage: null,
                    webSearchEnabled: false,
                    historyOpen: window.innerWidth > 1280,

                    // Knowledge Base
                    kbModalOpen: false,
                    kbRules: [],
                    newKbRuleContent: '',
                    loadingKb: false,
                    savingKb: false,

                    init() {
                        this.loadThreads();
                        this.loadKbRules();
                        
                        // Debounced visual rendering to handle high-frequency stream updates
                        this.renderDebounce = null;
                        this.$watch('messages', () => {
                            if (this.renderDebounce) clearTimeout(this.renderDebounce);
                            this.renderDebounce = setTimeout(() => this.renderVisuals(), 100);
                        });
                        
                        this.$nextTick(() => this.renderVisuals());
                    },

                    async loadThreads() {
                        this.loadingThreads = true;
                        this.error = null;

                        try {
                            const response = await fetch(this.urls.list, {
                                headers: { 'Accept': 'application/json' }
                            });
                            const data = await this.parseJsonResponse(response);
                            this.threads = data.threads || [];

                            if (this.currentThreadId) {
                                await this.loadThread(this.currentThreadId);
                            } else if (this.threads.length > 0) {
                                await this.loadThread(this.threads[0].id);
                            }
                        } catch (error) {
                            this.error = error.message;
                        } finally {
                            this.loadingThreads = false;
                        }
                    },

                    async createThread(selectAfter = true) {
                        if (!this.canAnalyze) {
                            return null;
                        }

                        this.creatingThread = true;
                        this.error = null;

                        try {
                            const response = await fetch(this.urls.create, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                }
                            });
                            const data = await this.parseJsonResponse(response);
                            const thread = data.thread;
                            this.threads = [thread, ...this.threads.filter(item => item.id !== thread.id)];

                            if (selectAfter) {
                                await this.loadThread(thread.id);
                            }

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
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({ title: newTitle.trim() })
                            });
                            const data = await this.parseJsonResponse(response);
                            const idx = this.threads.findIndex(t => t.id === threadId);
                            if (idx !== -1) this.threads[idx] = data.thread;
                            if (this.currentThread && this.currentThread.id === threadId) {
                                this.currentThread = data.thread;
                            }
                        } catch (error) {
                            this.error = error.message;
                        } finally {
                            this.renamingThreadId = null;
                            this.editingTitle = '';
                        }
                    },

                    async deleteThread(threadId) {
                        console.log('Socius: deleteThread prompt for ID:', threadId);
                        
                        const result = await Swal.fire({
                            title: @js(__('Delete Conversation?')),
                            text: @js(__('This will permanently delete this conversation and all associated attachments.')),
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#6b7280',
                            confirmButtonText: @js(__('Yes, Delete It')),
                            cancelButtonText: @js(__('Cancel')),
                            customClass: {
                                popup: 'rounded-3xl border-none shadow-2xl',
                                title: 'text-2xl font-black tracking-tight text-gray-900',
                                confirmButton: 'rounded-xl px-6 py-2.5 text-xs font-black uppercase tracking-widest',
                                cancelButton: 'rounded-xl px-6 py-2.5 text-xs font-black uppercase tracking-widest'
                            }
                        });

                        if (!result.isConfirmed) {
                            console.log('Socius: Delete cancelled via SweetAlert');
                            return;
                        }
                        
                        const url = this.threadUrl('destroyTemplate', threadId);
                        console.log('Socius: Fetching DELETE URL:', url);

                        try {
                            const response = await fetch(url, {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                }
                            });
                            
                            console.log('Socius: Delete response status:', response.status);
                            const data = await this.parseJsonResponse(response);
                            console.log('Socius: Delete success data:', data);
                            
                            // Use loose inequality in case of string/number mismatch
                            const beforeCount = this.threads.length;
                            this.threads = this.threads.filter(t => t.id != threadId);
                            console.log('Socius: Threads filtered. Before:', beforeCount, 'After:', this.threads.length);
                            
                            if (this.currentThreadId == threadId) {
                                console.log('Socius: Deleting active thread, resetting state.');
                                this.currentThreadId = null;
                                this.currentThread = null;
                                this.messages = [];
                                this.syncQuery();
                            }

                            Swal.fire({
                                title: @js(__('Deleted!')),
                                text: @js(__('The conversation has been removed.')),
                                icon: 'success',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                                customClass: {
                                    popup: 'rounded-2xl shadow-xl border-none'
                                }
                            });
                        } catch (error) {
                            console.error('Socius Delete Error:', error);
                            Swal.fire({
                                title: @js(__('Error')),
                                text: error.message,
                                icon: 'error',
                                customClass: {
                                    popup: 'rounded-3xl border-none shadow-2xl'
                                }
                            });
                            this.error = error.message;
                        }
                    },

                    async togglePin(threadId) {
                        try {
                            const response = await fetch(this.threadUrl('pin_toggleTemplate', threadId), {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                }
                            });
                            const data = await this.parseJsonResponse(response);
                            const idx = this.threads.findIndex(t => t.id === threadId);
                            if (idx !== -1) {
                                this.threads[idx] = data.thread;
                                this.sortThreads();
                            }
                        } catch (error) {
                            this.error = error.message;
                        }
                    },

                    sortThreads() {
                        this.threads.sort((a, b) => {
                            if (a.is_pinned !== b.is_pinned) return b.is_pinned ? 1 : -1;
                            return new Date(b.last_activity_at) - new Date(a.last_activity_at);
                        });
                    },

                    copyMessage(content) {
                        const plain = content
                            .replace(/<[^>]*>/g, '')
                            .replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&#039;/g, "'");
                        navigator.clipboard.writeText(plain).catch(() => {});
                    },

                    async selectThread(threadId) {
                        if (threadId === this.currentThreadId) {
                            return;
                        }

                        await this.loadThread(threadId);
                    },

                    async loadThread(threadId) {
                        this.loadingMessages = true;
                        this.error = null;

                        try {
                            const response = await fetch(this.threadUrl('showTemplate', threadId), {
                                headers: { 'Accept': 'application/json' }
                            });
                            const data = await this.parseJsonResponse(response);
                            this.currentThread = data.thread;
                            this.currentThreadId = data.thread.id;
                            this.messages = data.messages || [];
                            this.syncQuery();
                            this.scrollMessages();
                        } catch (error) {
                            this.error = error.message;
                        } finally {
                            this.loadingMessages = false;
                        }
                    },

                    pickFiles() {
                        this.$refs.fileInput.click();
                    },

                    handleFileSelection(event) {
                        const selected = Array.from(event.target.files || []);
                        selected.forEach(file => {
                            const exists = this.pendingFiles.some(existing => existing.name === file.name && existing.size === file.size);
                            if (!exists) {
                                this.pendingFiles.push(file);
                            }
                        });
                        event.target.value = '';
                    },

                    // Phase 4 Methods
                    toggleVoiceInput() {
                        if (this.isListening) {
                            if (this.recognition) this.recognition.stop();
                            return;
                        }

                        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                        if (!SpeechRecognition) {
                            this.error = @js(__('Your browser does not support voice input.'));
                            return;
                        }

                        if (!this.recognition) {
                            this.recognition = new SpeechRecognition();
                            this.recognition.continuous = true;
                            this.recognition.interimResults = true;
                            this.recognition.lang = document.documentElement.lang || 'en-US';

                            this.recognition.onresult = (event) => {
                                let transcript = '';
                                for (let i = event.resultIndex; i < event.results.length; i++) {
                                    transcript += event.results[i][0].transcript;
                                }
                                this.draft = transcript;
                            };

                            this.recognition.onend = () => {
                                this.isListening = false;
                            };

                            this.recognition.onerror = (event) => {
                                if (event.error !== 'no-speech') {
                                    this.error = @js(__('Voice recognition error: ')) + event.error;
                                }
                                this.isListening = false;
                            };
                        }

                        this.error = null;
                        try {
                            this.recognition.start();
                            this.isListening = true;
                        } catch (e) {
                            console.error("Speech start error:", e);
                            this.isListening = false;
                        }
                    },

                    async regenerateResponse(messageId) {
                        if (this.sending) return;
                        
                        const idx = this.messages.findIndex(m => m.id === messageId);
                        if (idx <= 0) return;
                        
                        const userMessage = this.messages[idx - 1];
                        if (userMessage.role !== 'user') return;

                        const threadId = this.currentThreadId;
                        const content = userMessage.content;

                        // Optimistically remove following messages
                        this.messages = this.messages.slice(0, idx);
                        
                        await this.sendMessage(content, threadId);
                    },

                    startEditing(messageId, content) {
                        this.editingMessageId = messageId;
                        this.editingContent = content;
                        this.$nextTick(() => {
                            const el = document.getElementById(`edit-textarea-${messageId}`);
                            if (el) el.focus();
                        });
                    },

                    cancelEditing() {
                        this.editingMessageId = null;
                        this.editingContent = '';
                    },

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

                    removePendingFile(index) {
                        this.pendingFiles.splice(index, 1);
                    },

                    async sendMessage(overrideContent = null, overrideThreadId = null) {
                        if (this.sending || !this.canAnalyze) {
                            return;
                        }

                        const content = (overrideContent !== null ? overrideContent : this.draft).trim();
                        if (!content && this.pendingFiles.length === 0) {
                            return;
                        }

                        let threadId = overrideThreadId || this.currentThreadId;
                        if (!threadId) {
                            const thread = await this.createThread(false);
                            if (!thread) {
                                return;
                            }
                            threadId = thread.id;
                            this.currentThread = thread;
                            this.currentThreadId = thread.id;
                            this.syncQuery();
                        }

                        this.error = null;
                        this.sending = true;

                        const tempUserId = `temp-user-${Date.now()}`;
                        const tempAssistantId = `temp-assistant-${Date.now()}`;
                        const optimisticAttachments = this.pendingFiles.map((file, index) => ({
                            id: `pending-${index}`,
                            original_name: file.name,
                            size_bytes: file.size,
                            excerpt: this.formatBytes(file.size)
                        }));

                        this.messages.push({
                            id: tempUserId,
                            role: 'user',
                            content,
                            attachments: optimisticAttachments,
                            created_at: new Date().toISOString()
                        });
                        this.messages.push({
                            id: tempAssistantId,
                            role: 'assistant',
                            content: '',
                            attachments: [],
                            created_at: new Date().toISOString()
                        });
                        this.scrollMessages();

                        const formData = new FormData();
                        formData.append('message', content);
                        formData.append('include_survey_context', this.includeSurveyContext ? '1' : '0');
                        formData.append('web_search_enabled', this.webSearchEnabled ? '1' : '0');
                        this.pendingFiles.forEach(file => formData.append('attachments[]', file));

                        const usedFiles = [...this.pendingFiles];
                        this.draft = '';
                        this.pendingFiles = [];

                        try {
                            const response = await fetch(this.threadUrl('streamTemplate', threadId), {
                                method: 'POST',
                                headers: {
                                    'Accept': 'text/event-stream',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: formData
                            });

                            if (!response.ok || !response.body) {
                                if (response.status === 429) {
                                    throw new Error("{{ __('Groq AI Rate Limit Exceeded. Please wait a minute and try again.') }}");
                                }
                                const errorData = await this.safeReadJson(response);
                                throw new Error(errorData?.message || @js(__('Socius could not send this message.')));
                            }

                            await this.consumeEventStream(response.body, tempUserId, tempAssistantId);
                            await this.loadThread(threadId);
                            await this.reloadThreadList();
                        } catch (error) {
                            this.error = error.message;
                            const failedAssistantId = this.streamingAssistantId || tempAssistantId;
                            this.replaceMessage(failedAssistantId, {
                                id: failedAssistantId,
                                role: 'assistant',
                                content: error.message,
                                attachments: [],
                                created_at: new Date().toISOString()
                            });
                            this.pendingFiles = usedFiles;
                        } finally {
                            this.streamingUserId = null;
                            this.streamingAssistantId = null;
                            this.sending = false;
                        }
                    },

                    async reloadThreadList() {
                        try {
                            const response = await fetch(this.urls.list, {
                                headers: { 'Accept': 'application/json' }
                            });
                            const data = await this.parseJsonResponse(response);
                            this.threads = data.threads || [];
                        } catch (error) {
                            this.error = error.message;
                        }
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

                                if (rawEvent) {
                                    this.handleStreamEvent(rawEvent, tempUserId, tempAssistantId);
                                }
                            }
                        }
                    },

                    handleStreamEvent(rawEvent, tempUserId, tempAssistantId) {
                        const lines = rawEvent.split('\n');
                        let eventName = 'message';
                        let data = {};

                        lines.forEach(line => {
                            if (line.startsWith('event:')) {
                                eventName = line.replace('event:', '').trim();
                            }
                            if (line.startsWith('data:')) {
                                try {
                                    data = JSON.parse(line.replace('data:', '').trim());
                                } catch (error) {
                                    data = {};
                                }
                            }
                        });

                        if (eventName === 'meta') {
                            this.streamingUserId = data.user_message_id || tempUserId;
                            this.streamingAssistantId = data.assistant_message_id || tempAssistantId;
                            this.updateMessageId(tempUserId, data.user_message_id);
                            this.updateMessageId(tempAssistantId, data.assistant_message_id);
                        }

                        if (eventName === 'delta') {
                            const assistantMessage = this.messages.find(message => message.id === this.streamingAssistantId)
                                || this.messages.find(message => message.id === tempAssistantId);
                            if (assistantMessage) {
                                assistantMessage.content = `${assistantMessage.content || ''}${data.content || ''}`;
                                this.scrollMessages();
                            }
                        }

                        if (eventName === 'error') {
                            throw new Error(data.message || @js(__('Streaming failed.')));
                        }
                    },

                    updateMessageId(oldId, newId) {
                        const target = this.messages.find(message => message.id === oldId);
                        if (target && newId) {
                            target.id = newId;
                        }
                    },

                    replaceMessage(messageId, replacement) {
                        const index = this.messages.findIndex(message => message.id === messageId);
                        if (index !== -1) {
                            this.messages.splice(index, 1, replacement);
                        }
                    },

                    threadUrl(key, threadId) {
                        return this.urls[key].replace('__THREAD__', threadId);
                    },

                    syncQuery() {
                        const url = new URL(window.location.href);
                        url.searchParams.set('reportTab', 'analyse');
                        if (this.currentThreadId) {
                            url.searchParams.set('thread', this.currentThreadId);
                        } else {
                            url.searchParams.delete('thread');
                        }
                        window.history.replaceState({}, '', url);
                    },

                    scrollMessages() {
                        this.$nextTick(() => {
                            if (this.$refs.messageList) {
                                this.$refs.messageList.scrollTop = this.$refs.messageList.scrollHeight;
                            }
                        });
                    },

                    async parseJsonResponse(response) {
                        const data = await this.safeReadJson(response);
                        if (!response.ok) {
                            throw new Error(data?.message || @js(__('Request failed.')));
                        }
                        return data || {};
                    },

                    async safeReadJson(response) {
                        const text = await response.text();
                        if (!text) {
                            return null;
                        }
                        try {
                            return JSON.parse(text);
                        } catch (error) {
                            return null;
                        }
                    },

                    formatRelativeTime(timestamp) {
                        if (!timestamp) return '{{ __('Just now') }}';
                        const date = new Date(timestamp);
                        if (Number.isNaN(date.getTime())) return '{{ __('Just now') }}';
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
                        let value = bytes;
                        let unitIndex = 0;
                        while (value >= 1024 && unitIndex < units.length - 1) {
                            value /= 1024;
                            unitIndex += 1;
                        }
                        return `${value.toFixed(value >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
                    },

                    renderMessage(content, role) {
                        if (role === 'user') {
                            return this.escapeHtml(content || '').replace(/\n/g, '<br>');
                        }

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
                        let paragraph = [];
                        let listItems = [];
                        let tableLines = [];
                        let inCodeBlock = false;
                        let codeBlockType = '';
                        let codeBlockLines = [];

                        const flushParagraph = () => {
                            if (paragraph.length) {
                                blocks.push(`<p class="mb-4">${this.inlineFormat(paragraph.join(' '))}</p>`);
                                paragraph = [];
                            }
                        };

                        const flushList = () => {
                            if (listItems.length) {
                                blocks.push(`<ol class="list-decimal list-inside space-y-1 mb-4">${listItems.map(item => `<li>${this.inlineFormat(item)}</li>`).join('')}</ol>`);
                                listItems = [];
                            }
                        };

                        const flushTable = () => {
                            if (tableLines.length) {
                                blocks.push(this.renderMarkdownTable(tableLines));
                                tableLines = [];
                            }
                        };

                        const flushCodeBlock = () => {
                            if (inCodeBlock) {
                                const content = codeBlockLines.join('\n');
                                const id = 'visual-' + Math.random().toString(36).substr(2, 9);
                                
                                if (codeBlockType === 'mermaid' || codeBlockType === 'chartjs' || codeBlockType === 'chart.js' || codeBlockType === 'pollinations') {
                                    const type = codeBlockType === 'chart.js' ? 'chartjs' : codeBlockType;
                                    const isImage = type === 'pollinations';
                                    blocks.push(`
                                        <div class="socius-visual my-6 bg-white/5 rounded-2xl border border-white/10 overflow-hidden" 
                                             data-visual-type="${type}" 
                                             data-visual-id="${id}">
                                            <div class="visual-header flex items-center justify-between px-4 py-2 border-b border-white/10 bg-white/5">
                                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">${isImage ? '{{ __('AI Image') }}' : type}</span>
                                                <div class="flex gap-2">
                                                    <button onclick="window.sociusVisuals.copy('${id}')" class="text-[10px] font-bold text-slate-400 hover:text-white transition-colors">
                                                        <i class="fa-solid fa-copy mr-1"></i> {{ __('Copy') }}
                                                    </button>
                                                    <button onclick="window.sociusVisuals.download('${id}', 'png')" class="text-[10px] font-bold text-slate-400 hover:text-white transition-colors">
                                                        <i class="fa-solid fa-download mr-1"></i> {{ __('PNG') }}
                                                    </button>
                                                </div>
                                            </div>
                                            <div id="${id}" class="visual-body p-6 flex justify-center overflow-x-auto min-h-[100px] relative">
                                                <textarea class="visual-source hidden">${this.escapeHtml(content)}</textarea>
                                                <div class="visual-target w-full flex justify-center">
                                                    ${isImage ? '<div class="animate-pulse flex flex-col items-center gap-3 p-8"><i class="fa-solid fa-wand-magic-sparkles text-orange-400 text-2xl"></i><span class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">{{ __('Generating Image...') }}</span></div>' : ''}
                                                </div>
                                            </div>
                                        </div>
                                    `);
                                } else {
                                    blocks.push(`<pre class="bg-black/30 p-4 rounded-xl overflow-x-auto text-xs my-4 border border-white/5"><code>${this.escapeHtml(content)}</code></pre>`);
                                }
                                inCodeBlock = false;
                                codeBlockLines = [];
                                codeBlockType = '';
                            }
                        };

                        for (let i = 0; i < lines.length; i++) {
                            const rawLine = lines[i];
                            const line = rawLine.trim();

                            if (line.startsWith('```')) {
                                if (inCodeBlock) {
                                    flushCodeBlock();
                                } else {
                                    flushParagraph();
                                    flushList();
                                    flushTable();
                                    inCodeBlock = true;
                                    codeBlockType = line.slice(3).trim().toLowerCase();
                                }
                                continue;
                            }

                            if (inCodeBlock) {
                                codeBlockLines.push(rawLine);
                                continue;
                            }

                            if (!line) {
                                flushParagraph();
                                flushList();
                                flushTable();
                                continue;
                            }

                            if (this.looksLikeMarkdownTableLine(line)) {
                                flushParagraph();
                                flushList();
                                tableLines.push(line);
                                continue;
                            }

                            flushTable();

                            const headingMatch = line.match(/^#{1,6}\s+(.*)$/);
                            if (headingMatch) {
                                flushParagraph();
                                flushList();
                                blocks.push(`<h4 class="text-sm font-black text-orange-200 mt-6 mb-3 uppercase tracking-wider">${this.inlineFormat(headingMatch[1])}</h4>`);
                                continue;
                            }

                            const orderedMatch = line.match(/^\d+\.\s+(.*)$/);
                            if (orderedMatch) {
                                flushParagraph();
                                listItems.push(orderedMatch[1]);
                                continue;
                            }

                            if (line.startsWith('- ') || line.startsWith('* ')) {
                                flushParagraph();
                                listItems.push(line.slice(2));
                                continue;
                            }

                            flushList();
                            paragraph.push(line);
                        }

                        flushParagraph();
                        flushList();
                        flushTable();

                        if (inCodeBlock) {
                            if (codeBlockType === 'mermaid' || codeBlockType === 'chartjs' || codeBlockType === 'chart.js' || codeBlockType === 'pollinations') {
                                const type = codeBlockType === 'chart.js' ? 'chartjs' : codeBlockType;
                                const isImage = type === 'pollinations';
                                blocks.push(`
                                    <div class="socius-visual-loading my-6 bg-white/5 rounded-2xl border border-white/10 border-dashed p-8 text-center animate-pulse">
                                        <i class="fa-solid ${isImage ? 'fa-wand-magic-sparkles' : 'fa-chart-simple'} text-orange-400/50 text-2xl mb-3"></i>
                                        <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">{{ __('Socius is generating an image...') }}</p>
                                    </div>
                                `);
                            } else {
                                flushCodeBlock();
                            }
                        }

                        return blocks.join('');
                    },

                    inlineFormat(text) {
                        const escaped = this.escapeHtml(text);
                        return escaped
                            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                            .replace(/__(.+?)__/g, '<strong>$1</strong>')
                            .replace(/\*(.+?)\*/g, '<em>$1</em>')
                            .replace(/`(.+?)`/g, '<code>$1</code>');
                    },

                    looksLikeMarkdownTableLine(line) {
                        if (!line.includes('|')) return false;
                        const pipeCount = (line.match(/\|/g) || []).length;
                        return pipeCount >= 2;
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

                        const header = rows[0];
                        const body = rows.slice(2);

                        return `
                            <div class="overflow-x-auto my-4">
                                <table class="min-w-full text-left text-sm border-separate border-spacing-0">
                                    <thead>
                                        <tr>
                                            ${header.map(cell => `<th class="px-4 py-3 text-[11px] font-black uppercase tracking-widest text-orange-200 border-b border-white/10">${this.inlineFormat(cell)}</th>`).join('')}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${body.map(row => `
                                            <tr>
                                                ${row.map(cell => `<td class="px-4 py-3 border-b border-white/5 text-slate-100">${this.inlineFormat(cell)}</td>`).join('')}
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    },

                    async renderVisuals() {
                        const visuals = document.querySelectorAll('.socius-visual:not(.rendered)');
                        if (visuals.length === 0) return;

                        if (typeof mermaid !== 'undefined') {
                            try {
                                mermaid.initialize({ 
                                    startOnLoad: false, 
                                    theme: 'dark',
                                    securityLevel: 'loose',
                                    fontFamily: 'Inter',
                                    suppressErrorIndicators: true,
                                    logLevel: 4
                                });
                            } catch (e) {}
                        }

                        for (const el of Array.from(visuals)) {
                            const type = el.dataset.visualType;
                            const id = el.dataset.visualId;
                            const sourceEl = el.querySelector('.visual-source');
                            if (!sourceEl) continue;
                            
                            let source = (sourceEl.value || sourceEl.textContent).trim();
                            const target = el.querySelector('.visual-target');
                            if (!target) continue;

                            console.log(`Socius: Rendering ${type}`, { id, source });

                            try {
                                if (type === 'mermaid' && typeof mermaid !== 'undefined') {
                                    if (!source.match(/^(graph|sequenceDiagram|gantt|classDiagram|stateDiagram|erDiagram|journey|pie|quadrantChart|xychart-beta|mindmap|timeline)/i)) {
                                        source = 'graph TD\n' + source;
                                    }
                                    
                                    const { svg } = await mermaid.render('svg-' + id, source);
                                    target.innerHTML = svg;
                                    el.classList.add('rendered');
                                } else if (type === 'chartjs' && typeof Chart !== 'undefined') {
                                    const repairedSource = this.repairJson(source);
                                    const config = JSON.parse(repairedSource);
                                    const canvas = document.createElement('canvas');
                                    target.innerHTML = '';
                                    target.appendChild(canvas);
                                    
                                    if (!config.options) config.options = {};
                                    config.options.responsive = true;
                                    config.options.maintainAspectRatio = false;
                                    
                                    new Chart(canvas, config);
                                    canvas.style.maxHeight = '400px';
                                    el.classList.add('rendered');
                                } else if (type === 'pollinations') {
                                    // Queue this image - don't fire immediately
                                    if (!window._sociusImageQueue) {
                                        window._sociusImageQueue = [];
                                        window._sociusImageProcessing = false;
                                    }
                                    
                                    const rawPrompt = source.replace(/```/g, '').trim();
                                    // Simplify prompt: strip verbose suffixes that slow generation
                                    const prompt = rawPrompt
                                        .replace(/,?\s*(cinematic lighting|8k resolution|photorealistic|ultra detailed|high quality|4k|hdr)/gi, '')
                                        .trim()
                                        .substring(0, 200); // Keep prompts short
                                    
                                    window._sociusImageQueue.push({ prompt, target, el });
                                    el.classList.add('rendered'); // Mark as handled so renderVisuals doesn't re-process
                                    
                                    // Start processing queue if not already running
                                    if (!window._sociusImageProcessing) {
                                        this.processImageQueue();
                                    }
                                }
                            } catch (e) {
                                console.error(`Socius Visual Error [${type}]:`, e);
                                target.innerHTML = `<div class="text-red-400/60 text-[10px] font-bold p-4 bg-red-500/10 rounded-xl border border-red-500/20">
                                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> 
                                    {{ __('Invalid visual syntax.') }}
                                </div>`;
                                el.classList.add('rendered');
                            }
                        }
                    },

                    repairJson(str) {
                        let cleaned = str;
                        
                        // If it starts/ends with markdown code fences, remove them
                        cleaned = cleaned.replace(/^```(json)?\n?/i, '').replace(/```$/i, '').trim();

                        cleaned = cleaned
                            .replace(/\/\*[\s\S]*?\*\/|([^\\:]|^)\/\/.*$/gm, '$1') // Remove comments
                            .replace(/,\s*([}\]])/g, '$1'); // Remove trailing commas
                            
                        return cleaned.trim();
                    },

                    async processImageQueue() {
                        window._sociusImageProcessing = true;
                        
                        while (window._sociusImageQueue.length > 0) {
                            const { prompt, target, el } = window._sociusImageQueue.shift();
                            
                            target.innerHTML = `<div class="animate-pulse flex flex-col items-center gap-3 p-8">
                                <i class="fa-solid fa-wand-magic-sparkles fa-bounce text-indigo-400 text-2xl"></i>
                                <span class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">{{ __('Visualizing Analysis...') }}</span>
                            </div>`;
                            
                            await this.loadSingleImage(prompt, target, el);
                            await new Promise(r => setTimeout(r, 1000));
                        }
                        
                        window._sociusImageProcessing = false;
                    },

                    loadSingleImage(prompt, target, el) {
                        return new Promise(async (resolve) => {
                            try {
                                const res = await fetch(`{{ route('surveys.analyse.image.generate', $survey) }}`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    },
                                    body: JSON.stringify({ prompt })
                                });

                                if (!res.ok) throw new Error('API error');

                                const contentType = res.headers.get('Content-Type');
                                let imgSrc = '';

                                if (contentType && contentType.includes('image/')) {
                                    const blob = await res.blob();
                                    imgSrc = URL.createObjectURL(blob);
                                } else {
                                    const data = await res.json();
                                    imgSrc = data.url || data.fallback_url;
                                }

                                if (imgSrc) {
                                    const img = new Image();
                                    img.className = 'rounded-xl max-w-full h-auto shadow-2xl transition-opacity duration-500 opacity-0';
                                    img.src = imgSrc;
                                    img.onload = () => {
                                        target.innerHTML = '';
                                        target.appendChild(img);
                                        void img.offsetWidth;
                                        img.classList.remove('opacity-0');
                                        resolve();
                                    };
                                    img.onerror = () => {
                                        target.innerHTML = `<div class="p-6 text-center bg-slate-800/40 rounded-xl border border-slate-700/30">
                                            <i class="fa-solid fa-triangle-exclamation text-amber-500/50 text-xl mb-2"></i>
                                            <p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest">{{ __('Image Source Unreachable') }}</p>
                                        </div>`;
                                        resolve();
                                    };
                                }
                            } catch (e) {
                                console.error('Image load failed:', e);
                                target.innerHTML = `<div class="p-6 text-center bg-slate-800/40 rounded-xl">
                                    <p class="text-[9px] text-slate-500">{{ __('Visualization failed to render') }}</p>
                                </div>`;
                                resolve();
                            }
                        });
                    },

                    async loadKbRules() {
                        this.loadingKb = true;
                        try {
                            const response = await fetch(this.urls.kbList, {
                                headers: { 'Accept': 'application/json' }
                            });
                            const data = await this.parseJsonResponse(response);
                            this.kbRules = data.rules || [];
                        } catch (error) {
                            console.error('Failed to load KB rules', error);
                        } finally {
                            this.loadingKb = false;
                        }
                    },

                    async addKbRule() {
                        if (!this.newKbRuleContent || !this.newKbRuleContent.trim()) return;
                        this.savingKb = true;
                        try {
                            const response = await fetch(this.urls.kbStore, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({ content: this.newKbRuleContent.trim() })
                            });
                            const data = await this.parseJsonResponse(response);
                            if (data.rule) {
                                this.kbRules = [data.rule, ...this.kbRules];
                                this.newKbRuleContent = '';
                                Swal.fire({
                                    title: @js(__('Added!')),
                                    text: data.message || @js(__('Preference added.')),
                                    icon: 'success',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 2000,
                                    customClass: { popup: 'rounded-2xl shadow-xl border-none' }
                                });
                            }
                        } catch (error) {
                            Swal.fire({
                                title: @js(__('Error')),
                                text: error.message,
                                icon: 'error',
                                customClass: { popup: 'rounded-3xl border-none shadow-2xl' }
                            });
                        } finally {
                            this.savingKb = false;
                        }
                    },

                    async toggleKbRule(rule) {
                        const originalState = rule.is_active;
                        const newState = !originalState;
                        rule.is_active = newState; // optimistic update
                        
                        try {
                            const url = this.urls.kbUpdateTemplate.replace('__KB__', rule.id);
                            const response = await fetch(url, {
                                method: 'PATCH',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({ is_active: newState })
                            });
                            const data = await this.parseJsonResponse(response);
                            if (data.rule) {
                                rule.is_active = data.rule.is_active;
                            }
                        } catch (error) {
                            rule.is_active = originalState; // rollback
                            Swal.fire({
                                title: @js(__('Error')),
                                text: error.message,
                                icon: 'error',
                                customClass: { popup: 'rounded-3xl border-none shadow-2xl' }
                            });
                        }
                    },

                    async deleteKbRule(ruleId) {
                        const result = await Swal.fire({
                            title: @js(__('Delete Preference?')),
                            text: @js(__('This preference will no longer apply to future chat answers.')),
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#6b7280',
                            confirmButtonText: @js(__('Yes, Delete It')),
                            cancelButtonText: @js(__('Cancel')),
                            customClass: {
                                popup: 'rounded-3xl border-none shadow-2xl',
                                title: 'text-2xl font-black tracking-tight text-gray-900',
                                confirmButton: 'rounded-xl px-6 py-2.5 text-xs font-black uppercase tracking-widest',
                                cancelButton: 'rounded-xl px-6 py-2.5 text-xs font-black uppercase tracking-widest'
                            }
                        });

                        if (!result.isConfirmed) return;

                        try {
                            const url = this.urls.kbDestroyTemplate.replace('__KB__', ruleId);
                            const response = await fetch(url, {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                }
                            });
                            const data = await this.parseJsonResponse(response);
                            this.kbRules = this.kbRules.filter(r => r.id !== ruleId);
                            Swal.fire({
                                title: @js(__('Deleted!')),
                                text: data.message || @js(__('Preference removed.')),
                                icon: 'success',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000,
                                customClass: { popup: 'rounded-2xl shadow-xl border-none' }
                            });
                        } catch (error) {
                            Swal.fire({
                                title: @js(__('Error')),
                                text: error.message,
                                icon: 'error',
                                customClass: { popup: 'rounded-3xl border-none shadow-2xl' }
                            });
                        }
                    }
                }
            };

            window.sociusVisuals = {
                async copy(id) {
                    const target = document.querySelector(`#${id} .visual-target`);
                    if (!target || typeof htmlToImage === 'undefined') return;
                    try {
                        const dataUrl = await htmlToImage.toPng(target, { 
                            backgroundColor: '#1a1a1a',
                            style: { padding: '20px' }
                        });
                        const response = await fetch(dataUrl);
                        const blob = await response.blob();
                        await navigator.clipboard.write([
                            new ClipboardItem({ 'image/png': blob })
                        ]);
                        // Simple toast-like feedback
                        const btn = event.currentTarget;
                        const original = btn.innerHTML;
                        btn.innerHTML = '<i class="fa-solid fa-check mr-1 text-green-400"></i> Copied';
                        setTimeout(() => btn.innerHTML = original, 2000);
                    } catch (e) {
                        console.error('Copy failed:', e);
                    }
                },
                async download(id, format) {
                    const target = document.querySelector(`#${id} .visual-target`);
                    if (!target || typeof htmlToImage === 'undefined') return;
                    try {
                        const dataUrl = await htmlToImage.toPng(target, { 
                            backgroundColor: '#1a1a1a',
                            style: { padding: '20px' }
                        });
                        const link = document.createElement('a');
                        link.download = `socius-visual-${id}.png`;
                        link.href = dataUrl;
                        link.click();
                    } catch (e) {
                        console.error('Download failed:', e);
                    }
                }
            };
        </script>
        <style>
            .socius-prose p {
                margin: 0 0 1rem;
            }

            .socius-prose h4 {
                margin: 1rem 0 0.75rem;
                font-size: 1rem;
                font-weight: 800;
                letter-spacing: 0.04em;
            }

            .socius-prose ol {
                margin: 0 0 1rem 1.25rem;
                padding: 0;
            }

            .socius-prose li {
                margin: 0 0 0.5rem;
            }

            .socius-prose code {
                background: rgba(255, 255, 255, 0.08);
                border: 1px solid rgba(255, 255, 255, 0.08);
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

            .socius-visual .visual-target canvas {
                max-width: 100%;
                height: auto !important;
            }
        </style>
    @endpush
@endsection
