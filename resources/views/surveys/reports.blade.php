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
        $allowedReportTabs = ['quantitative', 'qualitative', 'inferential', 'crosstab', 'analyse', 'humanizer'];
        $requestedReportTab = request('reportTab', 'quantitative');
        $initialReportTab = in_array($requestedReportTab, $allowedReportTabs, true) ? $requestedReportTab : 'quantitative';
    @endphp

    <div :class="(reportTab === 'analyse' || reportTab === 'humanizer') ? '' : 'space-y-12'" x-data="{
            reportTab: @js($initialReportTab) === 'crosstab' ? 'inferential' : @js($initialReportTab),
            humanizerOriginal: '',
            humanizerResult: '',
            isHumanizing: false,
            isAnalyzing: false,
            humanizerMode: 'standard',
            humanizerIntensity: 'medium',
            originalAnalysis: null,
            humanizedAnalysis: null,
            init() {
                this.$watch('reportTab', (tab) => {
                    if (tab === 'analyse') {
                        this.$nextTick(() => {
                            const inputEl = document.getElementById('socius-prompt-input');
                            if (inputEl) {
                                inputEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                inputEl.focus();
                            }
                        });
                    }
                });
                if (this.reportTab === 'analyse') {
                    this.$nextTick(() => {
                        const inputEl = document.getElementById('socius-prompt-input');
                        if (inputEl) {
                            inputEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            inputEl.focus();
                        }
                    });
                }
            },
            switchReportTab(tab) {
                this.reportTab = tab;
                const url = new URL(window.location.href);
                url.searchParams.set('reportTab', tab);
                if (tab !== 'analyse') {
                    url.searchParams.delete('thread');
                }
                window.history.replaceState({}, '', url);
            },
            goToHumanizer(text) {
                this.humanizerOriginal = text;
                this.humanizerResult = '';
                this.originalAnalysis = null;
                this.humanizedAnalysis = null;
                this.switchReportTab('humanizer');
                this.$nextTick(() => {
                    this.analyzeHumanizerText();
                });
            },
            async analyzeHumanizerText() {
                if (!this.humanizerOriginal.trim()) return;
                this.isAnalyzing = true;
                try {
                    const response = await fetch(`{{ route('surveys.analyse.humanize', $survey->id) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            text: this.humanizerOriginal,
                            analyze_only: true
                        })
                    });
                    const data = await response.json();
                    this.originalAnalysis = data.analysis;
                } catch (e) {
                    console.error(e);
                } finally {
                    this.isAnalyzing = false;
                }
            },
            async uploadFile(event) {
                const file = event.target.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('file', file);
                formData.append('_token', '{{ csrf_token() }}');

                this.isAnalyzing = true;
                try {
                    const response = await fetch('{{ route('humanizer.upload') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json'
                        },
                        body: formData
                    });
                    const data = await response.json();
                    if (data.error) {
                        alert('Extraction error: ' + data.message);
                        return;
                    }
                    this.humanizerOriginal = data.text || '';
                    this.$nextTick(() => {
                        this.analyzeHumanizerText();
                    });
                } catch (e) {
                    alert('Upload error: ' + e.message);
                } finally {
                    this.isAnalyzing = false;
                    event.target.value = '';
                }
            },
            async humanizeAction() {
                if (!this.humanizerOriginal.trim()) return;
                this.isHumanizing = true;
                this.humanizerResult = '';
                try {
                    const response = await fetch(`{{ route('surveys.analyse.humanize', $survey->id) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            text: this.humanizerOriginal,
                            mode: this.humanizerMode,
                            intensity: this.humanizerIntensity
                        })
                    });
                    const data = await response.json();
                    this.humanizerResult = data.humanized_text;
                    this.originalAnalysis = data.original_analysis;
                    this.humanizedAnalysis = data.humanized_analysis;
                } catch (e) {
                    alert('Humanizer error: ' + e.message);
                } finally {
                    this.isHumanizing = false;
                }
            }
        }">
        <!-- Sub Navigation -->
        <div x-show="reportTab !== 'analyse'" class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-10 border-b border-gray-100 pb-6">
            <div class="flex flex-wrap items-center gap-6">
                <!-- Descriptive Group -->
                <div class="flex flex-col gap-1.5">
                    <span class="text-xs font-bold text-zinc-500 tracking-tight pl-1">{{ __('Descriptive Statistics') }}</span>
                    <div class="flex items-center gap-1 bg-gray-100/50 p-1 rounded-2xl w-fit">
                        <button @click="switchReportTab('quantitative')"
                            :class="reportTab === 'quantitative' ? 'bg-white text-indigo-700 shadow-sm border border-indigo-50' : 'text-gray-500 hover:text-gray-700'"
                            class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all">
                            <i class="fa-solid fa-chart-column mr-2"></i>
                            {{ __('Quantitative') }}
                        </button>
                        <button @click="switchReportTab('qualitative')"
                            :class="reportTab === 'qualitative' ? 'bg-white text-indigo-700 shadow-sm border border-indigo-50' : 'text-gray-500 hover:text-gray-700'"
                            class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all">
                            <i class="fa-solid fa-comments mr-2"></i>
                            {{ __('Qualitative') }}
                        </button>
                    </div>
                </div>

                <!-- Divider -->
                <div class="hidden lg:block h-10 w-[1px] bg-gray-200 self-end mb-1"></div>

                <!-- Inferential Group -->
                <div class="flex flex-col gap-1.5">
                    <span class="text-xs font-bold text-zinc-500 tracking-tight pl-1">{{ __('Inferential Statistics') }}</span>
                    <div class="flex items-center gap-1 bg-gray-100/50 p-1 rounded-2xl w-fit">
                        <button @click="switchReportTab('inferential')"
                            :class="reportTab === 'inferential' ? 'bg-white text-indigo-700 shadow-sm border border-indigo-50' : 'text-gray-500 hover:text-gray-700'"
                            class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all">
                            <i class="fa-solid fa-calculator mr-2"></i>
                            {{ __('Analyse') }}
                            @if(!auth()->check() || !auth()->user()->hasActiveSubscription())
                                <i class="fa-solid fa-lock ml-2 text-[10px] opacity-50"></i>
                            @endif
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @if(!isset($isSharedView) || !$isSharedView)
                    <button type="button" onclick="window.startReportsDashboardTour()"
                        class="inline-flex items-center gap-2 px-5 py-3 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all">
                        <i class="fa-solid fa-compass text-sm"></i>
                        {{ __('Tour') }}
                    </button>
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
                                    @foreach(['vibrant', 'indigo', 'emerald', 'rose', 'amber', 'purple', 'greyscale'] as $color)
                                        <button @click="switchColor('{{ $item['canvasId'] }}', '{{ $color }}')"
                                            class="w-4 h-4 rounded-full border border-white ring-1 ring-gray-200 transition-transform hover:scale-125 shadow-sm"
                                            :class="{
                                                                'bg-gradient-to-br from-indigo-500 via-emerald-500 to-rose-500': '{{ $color }}' === 'vibrant',
                                                                'bg-indigo-500': '{{ $color }}' === 'indigo',
                                                                'bg-emerald-500': '{{ $color }}' === 'emerald',
                                                                'bg-rose-500': '{{ $color }}' === 'rose',
                                                                'bg-amber-500': '{{ $color }}' === 'amber',
                                                                'bg-purple-500': '{{ $color }}' === 'purple',
                                                                'bg-gray-500': '{{ $color }}' === 'greyscale',
                                                                'ring-2 ring-offset-2 ring-indigo-600 scale-125': activeColors['{{ $item['canvasId'] }}'] === '{{ $color }}'
                                                            }">
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-12">
                            <!-- Chart Area (Reduced Width) -->
                            <div
                                class="h-96 w-full max-w-4xl mx-auto relative flex items-center justify-center bg-gray-50/30 rounded-3xl p-8 border border-gray-100 shadow-inner group/chart">
                                <canvas id="{{ $item['canvasId'] }}"></canvas>
                                
                                <div class="absolute top-4 right-4 flex gap-2 z-20">
                                    <button @click="window.copyChartToClipboard('{{ $item['canvasId'] }}', $event.currentTarget)"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-white hover:bg-emerald-600 hover:text-white text-gray-600 hover:shadow-md rounded-xl text-[10px] font-black uppercase tracking-widest border border-gray-100 shadow-sm transition-all"
                                        title="{{ __('Copy Chart') }}">
                                        <i class="fa-solid fa-copy"></i>
                                        <span>{{ __('Copy') }}</span>
                                    </button>
                                    <button @click="window.exportChartToPng('{{ $item['canvasId'] }}', '{{ addslashes($item['label']) }}')"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-white hover:bg-indigo-600 hover:text-white text-gray-600 hover:shadow-md rounded-xl text-[10px] font-black uppercase tracking-widest border border-gray-100 shadow-sm transition-all"
                                        title="{{ __('Export Chart') }}">
                                        <i class="fa-solid fa-file-image"></i>
                                        {{ __('Export') }}
                                    </button>
                                </div>
                            </div>

                            <!-- Table Area (Below Chart) -->
                            <div class="overflow-hidden bg-white rounded-2xl border border-gray-100" id="table-wrapper-{{ $item['canvasId'] }}">
                                <div class="px-6 py-4 bg-gray-50/50 border-b border-gray-100 flex justify-between items-center">
                                    <span class="text-xs font-bold text-zinc-500 tracking-tight">{{ __('Frequency Table') }}</span>
                                    <div class="flex gap-2" data-html2canvas-ignore>
                                        <button @click="window.copyTableToClipboard('table-{{ $item['canvasId'] }}')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-gray-100 border border-gray-200 text-gray-600 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all shadow-sm">
                                            <i class="fa-solid fa-copy"></i>
                                            {{ __('Copy') }}
                                        </button>
                                        <button @click="window.exportTableToCsv('table-{{ $item['canvasId'] }}', '{{ addslashes($item['label']) }}')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-indigo-600 hover:text-white border border-gray-200 text-gray-600 hover:border-indigo-600 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all shadow-sm">
                                            <i class="fa-solid fa-file-csv"></i>
                                            {{ __('CSV') }}
                                        </button>
                                        <button @click="window.exportTableToPng('table-wrapper-{{ $item['canvasId'] }}', '{{ addslashes($item['label']) }}')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-emerald-600 hover:text-white border border-gray-200 text-gray-600 hover:border-emerald-600 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all shadow-sm">
                                            <i class="fa-solid fa-file-image"></i>
                                            {{ __('PNG') }}
                                        </button>
                                    </div>
                                </div>
                                <div class="overflow-x-auto custom-scrollbar">
                                    <table class="w-full text-left" id="table-{{ $item['canvasId'] }}">
                                        <thead class="sticky top-0 bg-white z-10 border-b border-gray-100 shadow-sm">
                                            <tr class="text-[12px] font-black text-gray-500 tracking-widest">
                                                <th class="py-4 px-6">{{ __('Value') }}</th>
                                                <th class="py-4 px-6 text-right">{{ __('Frequency') }}</th>
                                                <th class="py-4 px-6 text-right">{{ __('Percentage') }}</th>
                                            </tr>
                                        </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @php 
                                            $totalFreq = 0;
                                            $totalPerc = 0;
                                        @endphp
                                        @foreach($item['stats'] as $stat)
                                            @php 
                                                $totalFreq += $stat['count'];
                                                $totalPerc += (float)$stat['percentage'];
                                            @endphp
                                            <tr class="hover:bg-gray-50/30 transition-colors">
                                                <td class="py-4 px-6 text-[13px] font-black text-gray-700 tracking-tight">
                                                    {{ $stat['value'] }}</td>
                                                <td class="py-4 px-6 text-right text-[12px] font-black text-gray-900">{{ number_format($stat['count']) }}
                                                </td>
                                                <td class="py-4 px-6 text-right">
                                                    <span
                                                        class="text-[12px] font-black text-indigo-600">{{ $stat['percentage'] }}%</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-indigo-50/30 border-t-2 border-indigo-100">
                                        <tr class="font-black text-indigo-900">
                                            <td class="py-4 px-6 text-[11px] uppercase tracking-widest">{{ __('Total') }}</td>
                                            <td class="py-4 px-6 text-right text-[11px]">{{ number_format($totalFreq) }}</td>
                                            <td class="py-4 px-6 text-right text-[11px]">{{ round($totalPerc) }}%</td>
                                        </tr>
                                    </tfoot>
                                </table>
                                </div>
                            </div>

                            @if($canAnalyze)
                                <x-ai-quant-insight-card :question-id="$item['id']" :survey-id="$survey->id" :stats="$item['stats']" />
                            @else
                                <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 text-center mt-6">
                                    <p class="text-xs font-bold text-zinc-500 tracking-tight mb-1">{{ __('Quantitative AI Insights') }}</p>
                                    <p class="text-xs text-gray-500 font-medium">{{ __('Upgrade to Pro to unlock automated trend interpretation for numerical data.') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <div x-show="reportTab === 'qualitative'" class="space-y-12 animate-in fade-in duration-500"
            style="display: none;">
            @foreach($analysis as $item)
                @if(!$item['isChartable'])
                    <div class="bg-white rounded-3xl p-10 shadow-sm border border-gray-100">
                        <div class="mb-8 flex justify-between items-center border-b border-gray-50 pb-6">
                            <h4 class="text-2xl font-black text-gray-900 tracking-tight">
                                <span class="text-indigo-600 mr-2 opacity-30 text-base font-black">#{{ $loop->iteration }}</span>
                                {{ $item['label'] }}
                            </h4>
                        </div>

                        @if($item['isChartable'] && !empty($item['stats']))
                            <div class="mb-10 p-6 bg-gray-50/50 border border-gray-100 rounded-3xl animate-in fade-in duration-300">
                                <div class="mb-6 flex justify-between items-end">
                                    <span class="text-xs font-bold text-zinc-500 tracking-tight">{{ __('Frequency Distribution') }}</span>
                                    <div class="flex gap-1 bg-gray-100 p-1 rounded-lg border border-gray-100">
                                        @foreach(['bar', 'horizontal', 'line', 'pie', 'doughnut'] as $type)
                                            <button @click="switchChartType('qual-{{ $item['canvasId'] }}', '{{ $type }}')"
                                                :class="chartTypes['qual-{{ $item['canvasId'] }}'] === '{{ $type }}' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-600'"
                                                class="p-1 px-2 rounded-md text-[10px] font-bold tracking-tight transition-all">
                                                {{ substr($type, 0, 3) }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                                
                                <div class="grid md:grid-cols-2 gap-8 items-start">
                                    <div class="h-64 relative flex items-center justify-center bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
                                        <canvas id="qual-{{ $item['canvasId'] }}"></canvas>
                                    </div>
                                    
                                    <div class="overflow-hidden bg-white rounded-2xl border border-gray-100 max-h-64 overflow-y-auto custom-scrollbar">
                                        <table class="w-full text-left">
                                            <thead class="sticky top-0 bg-white z-10 border-b border-gray-100 shadow-sm">
                                                <tr class="text-xs font-bold text-zinc-500 tracking-tight">
                                                    <th class="py-3 px-4 font-bold">{{ __('Option') }}</th>
                                                    <th class="py-3 px-4 text-right font-bold">{{ __('Count') }}</th>
                                                    <th class="py-3 px-4 text-right font-bold">{{ __('Percentage') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-50">
                                                @foreach($item['stats'] as $stat)
                                                    <tr class="hover:bg-gray-50/30 transition-colors">
                                                        <td class="py-3 px-4 text-[11px] font-medium text-gray-700 tracking-tight">{{ $stat['value'] }}</td>
                                                        <td class="py-3 px-4 text-right text-[11px] font-bold text-gray-900">{{ number_format($stat['count']) }}</td>
                                                        <td class="py-3 px-4 text-right text-[11px] font-bold text-indigo-600">{{ $stat['percentage'] }}%</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($item['isAnalyzable'])
                            <x-ai-insight-card :question-id="$item['id']" :question-title="$item['label']"
                                :survey-id="$item['survey_id']" :index="$loop->index" />
                        @endif

                        <div class="mt-6 bg-gray-50 rounded-3xl overflow-hidden border border-gray-100" id="qual-wrapper-{{ $loop->index }}">
                            <div class="px-8 py-6 border-b border-gray-100 bg-white flex justify-between items-center">
                                <h5 class="text-xs font-bold text-zinc-500 tracking-tight">
                                    {{ __('Detailed Responses') }}</h5>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full">
                                        {{ count($item['answers'] ?? []) }} {{ __('Total Entries') }}
                                    </span>
                                    <div class="flex gap-2" data-html2canvas-ignore>
                                        <button onclick="window.copyTableToClipboard('qual-table-{{ $loop->index }}')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-gray-100 border border-gray-200 text-gray-600 rounded-xl text-[10px] font-bold tracking-tight transition-all shadow-sm">
                                            <i class="fa-solid fa-copy"></i>
                                            {{ __('Copy') }}
                                        </button>
                                        <button onclick="window.exportTableToCsv('qual-table-{{ $loop->index }}', '{{ addslashes($item['label']) }}')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-indigo-600 hover:text-white border border-gray-200 text-gray-600 hover:border-indigo-600 rounded-xl text-[10px] font-bold tracking-tight transition-all shadow-sm">
                                            <i class="fa-solid fa-file-csv"></i>
                                            {{ __('CSV') }}
                                        </button>
                                        <button onclick="window.exportTableToPng('qual-wrapper-{{ $loop->index }}', '{{ addslashes($item['label']) }}')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-emerald-600 hover:text-white border border-gray-200 text-gray-600 hover:border-emerald-600 rounded-xl text-[10px] font-bold tracking-tight transition-all shadow-sm">
                                            <i class="fa-solid fa-file-image"></i>
                                            {{ __('PNG') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="max-h-[350px] overflow-y-auto custom-scrollbar">
                                <table class="w-full text-left border-collapse" id="qual-table-{{ $loop->index }}">
                                    <thead>
                                        <tr class="bg-gray-50/50 text-xs font-bold text-zinc-500 tracking-tight sticky top-0 bg-white border-b border-gray-100 z-10">
                                            <th class="py-4 px-8 w-16 text-center font-bold">#</th>
                                            <th class="py-4 px-8 font-bold">{{ __('Response Content') }}</th>
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
            <div x-show="reportTab === 'analyse'">
                @include('surveys.partials.report_analyse')
            </div>
            <div x-show="reportTab === 'humanizer'" style="display: none;">
                @include('surveys.partials.report_humanizer')
            </div>
        @endif

        <!-- Inferential Content -->
        <div x-show="reportTab === 'inferential'" class="space-y-8 animate-in fade-in duration-500" style="display: none;">
            <div x-data="inferentialManager()" class="space-y-8">
                <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm relative overflow-hidden">
                    <div class="mb-8 border-b border-gray-50 pb-6">
                        <h4 class="text-xl font-black text-gray-900 tracking-tight">{{ __('Basic Inferential Stats') }}</h4>
                        <p class="text-xs text-gray-500 mt-2">{{ __('Run significance tests, correlations, and regressions on your survey responses.') }}</p>
                    </div>

                    <!-- Test Selection -->
                    <div class="mb-8">
                        <label class="block text-[10px] font-black text-indigo-600 uppercase tracking-widest mb-2">{{ __('Select Statistical Method') }}</label>
                        <select x-model="testMethod" class="w-full md:w-1/2 bg-gray-50 border border-indigo-100 text-sm font-semibold rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-indigo-500 transition-all">
                            <option value="crosstab">{{ __('Cross-Tabulation & Chi-Square Test') }}</option>
                            <option value="ttest">{{ __('Independent Samples T-Test') }}</option>
                            <option value="correlation">{{ __('Pearson Correlation (r)') }}</option>
                            <option value="anova">{{ __('One-Way ANOVA') }}</option>
                            <option value="regression">{{ __('Simple Linear Regression') }}</option>
                            <option value="regression_multiple">{{ __('Multiple Linear Regression') }}</option>
                        </select>
                    </div>

                    <!-- Dynamic Input Fields based on Selected Method -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-gray-50 pt-6">
                        <!-- Case 1: Crosstab / Chi-Square -->
                        <template x-if="testMethod === 'crosstab'">
                            <div class="contents">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">{{ __('Row Variable (Categorical)') }}</label>
                                    <select x-model="rowVar" class="w-full bg-gray-50 border border-gray-200 text-sm font-medium rounded-xl px-4 py-3.5 focus:ring-indigo-500 transition-all">
                                        <option value="">{{ __('Select Question...') }}</option>
                                        @foreach($analysis as $item)
                                            <option value="{{ $item['id'] }}">{{ \Illuminate\Support\Str::limit($item['label'], 60) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">{{ __('Column Variable (Categorical)') }}</label>
                                    <select x-model="colVar" class="w-full bg-gray-50 border border-gray-200 text-sm font-medium rounded-xl px-4 py-3.5 focus:ring-indigo-500 transition-all">
                                        <option value="">{{ __('Select Question...') }}</option>
                                        @foreach($analysis as $item)
                                            <option value="{{ $item['id'] }}">{{ \Illuminate\Support\Str::limit($item['label'], 60) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </template>

                        <!-- Case 2: T-Test / ANOVA -->
                        <template x-if="testMethod === 'ttest' || testMethod === 'anova'">
                            <div class="contents">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">{{ __('Dependent Variable (Numeric)') }}</label>
                                    <select x-model="depVar" class="w-full bg-gray-50 border border-gray-200 text-sm font-medium rounded-xl px-4 py-3.5 focus:ring-indigo-500 transition-all">
                                        <option value="">{{ __('Select Question...') }}</option>
                                        @foreach($analysis as $item)
                                            @if($item['isChartable'])
                                                <option value="{{ $item['id'] }}">{{ \Illuminate\Support\Str::limit($item['label'], 60) }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">{{ __('Grouping Variable (Categorical)') }}</label>
                                    <select x-model="groupVar" class="w-full bg-gray-50 border border-gray-200 text-sm font-medium rounded-xl px-4 py-3.5 focus:ring-indigo-500 transition-all">
                                        <option value="">{{ __('Select Question...') }}</option>
                                        @foreach($analysis as $item)
                                            <option value="{{ $item['id'] }}">{{ \Illuminate\Support\Str::limit($item['label'], 60) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </template>

                        <!-- Case 3: Correlation -->
                        <template x-if="testMethod === 'correlation'">
                            <div class="contents">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">{{ __('Variable X (Numeric)') }}</label>
                                    <select x-model="varX" class="w-full bg-gray-50 border border-gray-200 text-sm font-medium rounded-xl px-4 py-3.5 focus:ring-indigo-500 transition-all">
                                        <option value="">{{ __('Select Question...') }}</option>
                                        @foreach($analysis as $item)
                                            @if($item['isChartable'])
                                                <option value="{{ $item['id'] }}">{{ \Illuminate\Support\Str::limit($item['label'], 60) }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">{{ __('Variable Y (Numeric)') }}</label>
                                    <select x-model="varY" class="w-full bg-gray-50 border border-gray-200 text-sm font-medium rounded-xl px-4 py-3.5 focus:ring-indigo-500 transition-all">
                                        <option value="">{{ __('Select Question...') }}</option>
                                        @foreach($analysis as $item)
                                            @if($item['isChartable'])
                                                <option value="{{ $item['id'] }}">{{ \Illuminate\Support\Str::limit($item['label'], 60) }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </template>

                        <!-- Case 4: Regression -->
                        <template x-if="testMethod === 'regression'">
                            <div class="contents">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">{{ __('Dependent Variable Y (Numeric)') }}</label>
                                    <select x-model="depVar" class="w-full bg-gray-50 border border-gray-200 text-sm font-medium rounded-xl px-4 py-3.5 focus:ring-indigo-500 transition-all">
                                        <option value="">{{ __('Select Question...') }}</option>
                                        @foreach($analysis as $item)
                                            @if($item['isChartable'])
                                                <option value="{{ $item['id'] }}">{{ \Illuminate\Support\Str::limit($item['label'], 60) }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">{{ __('Independent Variable X (Numeric)') }}</label>
                                    <select x-model="groupVar" class="w-full bg-gray-50 border border-gray-200 text-sm font-medium rounded-xl px-4 py-3.5 focus:ring-indigo-500 transition-all">
                                        <option value="">{{ __('Select Question...') }}</option>
                                        @foreach($analysis as $item)
                                            @if($item['isChartable'])
                                                <option value="{{ $item['id'] }}">{{ \Illuminate\Support\Str::limit($item['label'], 60) }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </template>

                        <!-- Case 5: Multiple Linear Regression -->
                        <template x-if="testMethod === 'regression_multiple'">
                            <div class="contents">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">{{ __('Dependent Variable Y (Numeric)') }}</label>
                                    <select x-model="depVar" class="w-full bg-gray-50 border border-gray-200 text-sm font-medium rounded-xl px-4 py-3.5 focus:ring-indigo-500 transition-all">
                                        <option value="">{{ __('Select Question...') }}</option>
                                        @foreach($analysis as $item)
                                            @if($item['isChartable'])
                                                <option value="{{ $item['id'] }}">{{ \Illuminate\Support\Str::limit($item['label'], 60) }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">{{ __('Independent Variables X (Select Multiple)') }}</label>
                                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 max-h-[160px] overflow-y-auto space-y-2.5">
                                        @foreach($analysis as $item)
                                            @if($item['isChartable'])
                                                <label class="flex items-center gap-2.5 text-xs font-semibold text-gray-700 hover:text-indigo-600 transition-colors cursor-pointer">
                                                    <input type="checkbox" :value="'{{ $item['id'] }}'" x-model="indVars" class="rounded text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                                    <span>{{ \Illuminate\Support\Str::limit($item['label'], 60) }}</span>
                                                </label>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="flex justify-end border-t border-gray-50 mt-6 pt-6">
                        <button @click="runAnalysis()" :disabled="loading"
                            class="px-8 py-3 bg-indigo-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                            <i class="fa-solid fa-calculator" :class="{'fa-spin': loading}"></i> <span x-text="loading ? '{{ __('Calculating...') }}' : '{{ __('Run Analysis') }}'"></span>
                        </button>
                    </div>
                </div>

                <!-- 1. Crosstab & Chi-Square Results -->
                <template x-if="testMethod === 'crosstab' && matrixData">
                    <div class="space-y-8 animate-in fade-in duration-500">
                        <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm">
                            <div class="overflow-x-auto mb-8">
                                <table class="w-full text-left border-collapse min-w-[600px]">
                                    <thead>
                                        <tr>
                                            <th class="p-4 border-b border-r border-gray-200 bg-gray-50 w-1/4"></th>
                                            <template x-for="col in matrixData.columns" :key="col">
                                                <th class="p-4 border-b border-gray-200 bg-gray-50 text-[10px] font-black text-gray-600 uppercase tracking-widest text-center" x-text="col"></th>
                                            </template>
                                            <th class="p-4 border-b border-l border-gray-200 bg-indigo-50 text-[10px] font-black text-indigo-800 uppercase tracking-widest text-center">{{ __('Total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="row in matrixData.rows" :key="row">
                                            <tr class="hover:bg-gray-50/50 transition-colors">
                                                <th class="p-4 border-b border-r border-gray-200 text-[11px] font-black text-gray-700" x-text="row"></th>
                                                <template x-for="col in matrixData.columns" :key="col">
                                                    <td class="p-4 border-b border-gray-100 text-center text-sm font-medium text-gray-600">
                                                        <div class="font-bold text-gray-900" x-text="getMatrixValue(row, col)"></div>
                                                        <div class="text-[10px] text-gray-400 mt-0.5">{{ __('Expected:') }} <span x-text="matrixData.expectedMatrix[row][col]"></span></div>
                                                    </td>
                                                </template>
                                                <td class="p-4 border-b border-l border-gray-200 bg-indigo-50/30 text-center text-sm font-black text-indigo-700" x-text="(matrixData.rowTotals || {})[row] || 0"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th class="p-4 border-t border-r border-gray-200 bg-indigo-50 text-[10px] font-black text-indigo-800 uppercase tracking-widest">{{ __('Total') }}</th>
                                            <template x-for="col in matrixData.columns" :key="col">
                                                <th class="p-4 border-t border-gray-200 bg-indigo-50 text-center text-sm font-black text-indigo-800" x-text="(matrixData.colTotals || {})[col] || 0"></th>
                                            </template>
                                            <th class="p-4 border-t border-l border-indigo-200 bg-indigo-100 text-center text-sm font-black text-indigo-900" x-text="matrixData.grandTotal || 0"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Chi-Square Test Results -->
                            <div class="mt-8 border-t border-gray-100 pt-6">
                                <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('Chi-Square Tests') }}</h5>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                <th class="p-3 border-b">{{ __('Statistic') }}</th>
                                                <th class="p-3 border-b text-center">{{ __('Value') }}</th>
                                                <th class="p-3 border-b text-center">{{ __('df') }}</th>
                                                <th class="p-3 border-b text-center">{{ __('Asymp. Sig. (2-sided)') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="p-3 border-b text-xs font-semibold text-gray-800">{{ __('Pearson Chi-Square') }}</td>
                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="matrixData.chiSquare"></td>
                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="matrixData.df"></td>
                                                <td class="p-3 border-b text-xs font-black text-center" :class="matrixData.significant ? 'text-green-600' : 'text-gray-500'" x-text="matrixData.pValue"></td>
                                            </tr>
                                            <template x-if="matrixData.likelihoodRatio !== undefined && matrixData.likelihoodRatio !== null">
                                                <tr>
                                                    <td class="p-3 border-b text-xs font-semibold text-gray-800">{{ __('Likelihood Ratio') }}</td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="matrixData.likelihoodRatio"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="matrixData.df"></td>
                                                    <td class="p-3 border-b text-xs font-black text-center" :class="matrixData.likelihoodSignificant ? 'text-green-600' : 'text-gray-500'" x-text="matrixData.likelihoodPValue || matrixData.pValue"></td>
                                                </tr>
                                            </template>
                                            <template x-if="matrixData.linearAssociation !== undefined && matrixData.linearAssociation !== null">
                                                <tr>
                                                    <td class="p-3 border-b text-xs font-semibold text-gray-800">{{ __('Linear-by-Linear Association') }}</td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="matrixData.linearAssociation"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center">1</td>
                                                    <td class="p-3 border-b text-xs font-black text-center" x-text="matrixData.linearPValue"></td>
                                                </tr>
                                            </template>
                                            <template x-if="(matrixData.validCases !== undefined && matrixData.validCases !== null) || (matrixData.n !== undefined && matrixData.n !== null)">
                                                <tr>
                                                    <td class="p-3 border-b text-xs font-semibold text-gray-800">{{ __('N of Valid Cases') }}</td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="matrixData.validCases || matrixData.n || matrixData.grandTotal"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                    <template x-if="matrixData.footnote">
                                        <div class="text-[10px] text-gray-500 italic mt-3" x-text="matrixData.footnote"></div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- 2. T-Test Results -->
                <template x-if="testMethod === 'ttest' && tTestData">
                    <div class="space-y-8 animate-in fade-in duration-500">
                        <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm">
                            <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('Group Statistics') }}</h5>
                            <div class="overflow-x-auto mb-8">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                            <th class="p-3 border-b">{{ __('Grouping Variable') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('N') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('Mean') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('Std. Deviation') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('Std. Error Mean') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="g in tTestData.groups" :key="g.name">
                                            <tr>
                                                <td class="p-3 border-b text-xs font-semibold text-gray-800" x-text="g.name"></td>
                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="g.n"></td>
                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="g.mean"></td>
                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="g.stdDev"></td>
                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="g.stdError"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                             <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('Independent Samples Test') }}</h5>
                             <div class="overflow-x-auto">
                                 <table class="w-full text-left border-collapse min-w-[800px]">
                                     <thead>
                                         <tr class="bg-gray-50 font-bold text-gray-700 text-[10px] uppercase tracking-wider">
                                             <th class="p-3 border-b" rowspan="2"></th>
                                             <th class="p-3 border-b text-center border-r" colspan="2">{{ __("Levene's Test for Equality of Variances") }}</th>
                                             <th class="p-3 border-b text-center" colspan="7">{{ __('t-test for Equality of Means') }}</th>
                                         </tr>
                                         <tr class="bg-gray-50 font-bold text-gray-700 text-[10px] uppercase tracking-wider">
                                             <th class="p-3 border-b text-center">{{ __('F') }}</th>
                                             <th class="p-3 border-b text-center border-r">{{ __('Sig.') }}</th>
                                             <th class="p-3 border-b text-center">{{ __('t') }}</th>
                                             <th class="p-3 border-b text-center">{{ __('df') }}</th>
                                             <th class="p-3 border-b text-center">{{ __('Sig. (2-tailed)') }}</th>
                                             <th class="p-3 border-b text-center">{{ __('Mean Difference') }}</th>
                                             <th class="p-3 border-b text-center">{{ __('Std. Error Difference') }}</th>
                                             <th class="p-3 border-b text-center">{{ __('95% Confidence Interval (Lower)') }}</th>
                                             <th class="p-3 border-b text-center">{{ __('95% Confidence Interval (Upper)') }}</th>
                                         </tr>
                                     </thead>
                                     <tbody>
                                         <tr class="hover:bg-gray-50/50 transition-colors">
                                             <td class="p-3 border-b text-xs font-semibold text-gray-800">{{ __('Equal variances assumed') }}</td>
                                             <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="tTestData.leveneF"></td>
                                             <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r" x-text="tTestData.leveneSig"></td>
                                             <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="tTestData.tValue"></td>
                                             <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="tTestData.df"></td>
                                             <td class="p-3 border-b text-xs font-black text-center" :class="tTestData.significant ? 'text-green-600' : 'text-gray-500'" x-text="tTestData.pValue"></td>
                                             <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="tTestData.meanDiff"></td>
                                             <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="tTestData.stdErrorDiff"></td>
                                             <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="tTestData.ciLower"></td>
                                             <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="tTestData.ciUpper"></td>
                                         </tr>
                                         <tr class="hover:bg-gray-50/50 transition-colors">
                                             <td class="p-3 border-b text-xs font-semibold text-gray-800">{{ __('Equal variances not assumed') }}</td>
                                             <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"></td>
                                             <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r"></td>
                                             <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="tTestData.tValueWelch || tTestData.tValue"></td>
                                             <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="tTestData.dfWelch || tTestData.df"></td>
                                             <td class="p-3 border-b text-xs font-black text-center" :class="(tTestData.significantWelch !== undefined ? tTestData.significantWelch : tTestData.significant) ? 'text-green-600' : 'text-gray-500'" x-text="tTestData.pValueWelch || tTestData.pValue"></td>
                                             <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="tTestData.meanDiff"></td>
                                             <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="tTestData.stdErrorDiffWelch || tTestData.stdErrorDiff"></td>
                                             <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="tTestData.ciLowerWelch || tTestData.ciLower"></td>
                                             <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="tTestData.ciUpperWelch || tTestData.ciUpper"></td>
                                         </tr>
                                     </tbody>
                                 </table>
                             </div>
                        </div>
                    </div>
                </template>

                <!-- 3. Correlation Results -->
                <template x-if="testMethod === 'correlation' && correlationData">
                    <div class="space-y-8 animate-in fade-in duration-500">
                        <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm">
                            <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('Correlations Matrix') }}</h5>
                             <div class="overflow-x-auto">
                                 <table class="w-full text-left border-collapse">
                                     <thead>
                                         <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                             <th class="p-3 border-b"></th>
                                             <th class="p-3 border-b" x-text="correlationData.labelX"></th>
                                             <th class="p-3 border-b" x-text="correlationData.labelY"></th>
                                         </tr>
                                     </thead>
                                     <tbody>
                                         <tr class="border-b hover:bg-gray-50/30 transition-colors">
                                             <td class="p-3 text-xs font-bold text-gray-800" x-text="correlationData.labelX"></td>
                                             <td class="p-3 text-xs text-gray-600">
                                                 <div>{{ __('Pearson Correlation:') }} <span class="font-bold">1</span></div>
                                                 <div class="mt-1">{{ __('Sig. (2-tailed):') }} </div>
                                                 <div class="text-[10px] text-gray-400 mt-1">N: <span x-text="correlationData.n"></span></div>
                                             </td>
                                             <td class="p-3 text-xs text-gray-600">
                                                 <div>{{ __('Pearson Correlation:') }} <span class="font-black text-indigo-600" x-text="correlationData.r + (correlationData.sigMarker || '')"></span></div>
                                                 <div class="mt-1">{{ __('Sig. (2-tailed):') }} <span class="font-bold text-indigo-700" x-text="correlationData.pValue"></span></div>
                                                 <div class="text-[10px] text-gray-400 mt-1">N: <span x-text="correlationData.n"></span></div>
                                             </td>
                                         </tr>
                                         <tr class="border-b hover:bg-gray-50/30 transition-colors">
                                             <td class="p-3 text-xs font-bold text-gray-800" x-text="correlationData.labelY"></td>
                                             <td class="p-3 text-xs text-gray-600">
                                                 <div>{{ __('Pearson Correlation:') }} <span class="font-black text-indigo-600" x-text="correlationData.r + (correlationData.sigMarker || '')"></span></div>
                                                 <div class="mt-1">{{ __('Sig. (2-tailed):') }} <span class="font-bold text-indigo-700" x-text="correlationData.pValue"></span></div>
                                                 <div class="text-[10px] text-gray-400 mt-1">N: <span x-text="correlationData.n"></span></div>
                                             </td>
                                             <td class="p-3 text-xs text-gray-600">
                                                 <div>{{ __('Pearson Correlation:') }} <span class="font-bold">1</span></div>
                                                 <div class="mt-1">{{ __('Sig. (2-tailed):') }} </div>
                                                 <div class="text-[10px] text-gray-400 mt-1">N: <span x-text="correlationData.n"></span></div>
                                             </td>
                                         </tr>
                                     </tbody>
                                 </table>
                                 
                                 <!-- Footnotes -->
                                 <div class="mt-4 space-y-1 text-[10px] text-gray-500 italic">
                                     <template x-if="correlationData.sigMarker === '**'">
                                         <div>{{ __('**. Correlation is significant at the 0.01 level (2-tailed).') }}</div>
                                     </template>
                                     <template x-if="correlationData.sigMarker === '*'">
                                         <div>{{ __('*. Correlation is significant at the 0.05 level (2-tailed).') }}</div>
                                     </template>
                                     <div class="text-gray-400 mt-2 font-medium">
                                         {{ __('Standard Error of r:') }} <span class="font-bold" x-text="correlationData.stdErrorR"></span> | 
                                         {{ __('Covariance:') }} <span class="font-bold" x-text="correlationData.covariance"></span> | 
                                         {{ __('95% Confidence Interval for r:') }} [<span x-text="correlationData.ciLower"></span>, <span x-text="correlationData.ciUpper"></span>]
                                     </div>
                                 </div>
                             </div>
                        </div>
                    </div>
                </template>

                <!-- 4. ANOVA Results -->
                <template x-if="testMethod === 'anova' && anovaData">
                    <div class="space-y-8 animate-in fade-in duration-500">
                        <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm">
                            <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('ANOVA Descriptives') }}</h5>
                            <div class="overflow-x-auto mb-8">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                         <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                             <th class="p-3 border-b" rowspan="2">{{ __('Group') }}</th>
                                             <th class="p-3 border-b text-center" rowspan="2">{{ __('N') }}</th>
                                             <th class="p-3 border-b text-center" rowspan="2">{{ __('Mean') }}</th>
                                             <th class="p-3 border-b text-center" rowspan="2">{{ __('Std. Deviation') }}</th>
                                             <th class="p-3 border-b text-center" rowspan="2">{{ __('Std. Error') }}</th>
                                             <th class="p-3 border-b text-center border-l" colspan="2">{{ __('95% Confidence Interval for Mean') }}</th>
                                             <th class="p-3 border-b text-center border-l" rowspan="2">{{ __('Minimum') }}</th>
                                             <th class="p-3 border-b text-center" rowspan="2">{{ __('Maximum') }}</th>
                                         </tr>
                                         <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                             <th class="p-3 border-b text-center border-l">{{ __('Lower Bound') }}</th>
                                             <th class="p-3 border-b text-center">{{ __('Upper Bound') }}</th>
                                         </tr>
                                     </thead>
                                     <tbody>
                                         <template x-for="g in anovaData.groupStats" :key="g.name">
                                             <tr class="hover:bg-gray-50/50 transition-colors">
                                                 <td class="p-3 border-b text-xs font-semibold text-gray-800" x-text="g.name"></td>
                                                 <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="g.n"></td>
                                                 <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="g.mean"></td>
                                                 <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="g.stdDev"></td>
                                                 <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="g.stdError"></td>
                                                 <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-l" x-text="g.ciLower"></td>
                                                 <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="g.ciUpper"></td>
                                                 <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-l" x-text="g.min"></td>
                                                 <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="g.max"></td>
                                             </tr>
                                         </template>
                                     </tbody>
                                </table>
                            </div>

                            <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('ANOVA Table') }}</h5>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                            <th class="p-3 border-b"></th>
                                            <th class="p-3 border-b text-center">{{ __('Sum of Squares') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('df') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('Mean Square') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('F') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('Sig.') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="p-3 border-b text-xs font-semibold text-gray-800">{{ __('Between Groups') }}</td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="anovaData.ssb"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="anovaData.dfBetween"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="anovaData.msb"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="anovaData.fValue" rowspan="2" style="vertical-align: middle;"></td>
                                            <td class="p-3 border-b text-xs font-black text-center" :class="anovaData.significant ? 'text-green-600' : 'text-gray-500'" x-text="anovaData.pValue" rowspan="2" style="vertical-align: middle;"></td>
                                        </tr>
                                        <tr>
                                            <td class="p-3 border-b text-xs font-semibold text-gray-800">{{ __('Within Groups') }}</td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="anovaData.ssw"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="anovaData.dfWithin"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="anovaData.msw"></td>
                                        </tr>
                                        <tr class="bg-indigo-50/10">
                                            <td class="p-3 border-b text-xs font-bold text-indigo-900">{{ __('Total') }}</td>
                                            <td class="p-3 border-b text-xs font-bold text-indigo-900 text-center" x-text="anovaData.sst"></td>
                                            <td class="p-3 border-b text-xs font-bold text-indigo-900 text-center" x-text="anovaData.dfTotal"></td>
                                            <td class="p-3 border-b" colspan="3"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- 5. Regression Results -->
                <template x-if="testMethod === 'regression' && regressionData">
                    <div class="space-y-8 animate-in fade-in duration-500">
                        <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm">
                            <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('Variables Entered/Removed') }}</h5>
                            <div class="overflow-x-auto mb-8">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                            <th class="p-3 border-b">{{ __('Model') }}</th>
                                            <th class="p-3 border-b">{{ __('Variables Entered') }}</th>
                                            <th class="p-3 border-b">{{ __('Variables Removed') }}</th>
                                            <th class="p-3 border-b">{{ __('Method') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="p-3 border-b text-xs font-semibold text-gray-800">1</td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600" x-text="regressionData.indLabel"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600">.</td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600">{{ __('Enter') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('Model Summary') }}</h5>
                            <div class="overflow-x-auto mb-8">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                            <th class="p-3 border-b text-center">{{ __('Model') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('R') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('R Square') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('Adjusted R Square') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('Std. Error of the Estimate') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="p-3 border-b text-xs font-semibold text-gray-800 text-center">1</td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="regressionData.r"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="regressionData.r2"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="regressionData.adjR2"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="regressionData.stdErrorEst"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('ANOVA (Regression significance)') }}</h5>
                            <div class="overflow-x-auto mb-8">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                            <th class="p-3 border-b"></th>
                                            <th class="p-3 border-b text-center">{{ __('Sum of Squares') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('df') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('Mean Square') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('F') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('Sig.') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="p-3 border-b text-xs font-semibold text-gray-800">{{ __('Regression') }}</td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="regressionData.anova.ssr"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="regressionData.anova.dfReg"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="regressionData.anova.msr"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="regressionData.anova.fValue" rowspan="2" style="vertical-align: middle;"></td>
                                            <td class="p-3 border-b text-xs font-black text-center" :class="regressionData.anova.significant ? 'text-green-600' : 'text-gray-500'" x-text="regressionData.anova.pValue" rowspan="2" style="vertical-align: middle;"></td>
                                        </tr>
                                        <tr>
                                            <td class="p-3 border-b text-xs font-semibold text-gray-800">{{ __('Residual') }}</td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="regressionData.anova.sse"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="regressionData.anova.dfRes"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="regressionData.anova.mse"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('Coefficients') }}</h5>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse min-w-[700px]">
                                    <thead>
                                        <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                            <th class="p-3 border-b" rowspan="2">{{ __('Model') }}</th>
                                            <th class="p-3 border-b text-center border-r" colspan="2">{{ __('Unstandardized Coefficients') }}</th>
                                            <th class="p-3 border-b text-center border-r" rowspan="2">{{ __('Standardized Coefficients (Beta)') }}</th>
                                            <th class="p-3 border-b text-center border-r" rowspan="2">{{ __('t') }}</th>
                                            <th class="p-3 border-b text-center border-r" rowspan="2">{{ __('Sig.') }}</th>
                                            <th class="p-3 border-b text-center" colspan="2">{{ __('95.0% Confidence Interval for B') }}</th>
                                        </tr>
                                        <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                            <th class="p-3 border-b text-center">{{ __('B') }}</th>
                                            <th class="p-3 border-b text-center border-r">{{ __('Std. Error') }}</th>
                                            <th class="p-3 border-b text-center border-l">{{ __('Lower Bound') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('Upper Bound') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="p-3 border-b text-xs font-semibold text-gray-800">{{ __('(Constant)') }}</td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="regressionData.coefficients.intercept.coef"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r" x-text="regressionData.coefficients.intercept.stdError"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r" x-text="regressionData.coefficients.intercept.beta"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r" x-text="regressionData.coefficients.intercept.tValue"></td>
                                            <td class="p-3 border-b text-xs font-black text-center border-r" :class="regressionData.coefficients.intercept.significant ? 'text-green-600' : 'text-gray-500'" x-text="regressionData.coefficients.intercept.pValue"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-l" x-text="regressionData.coefficients.intercept.ciLower"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="regressionData.coefficients.intercept.ciUpper"></td>
                                        </tr>
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="p-3 border-b text-xs font-semibold text-gray-800" x-text="regressionData.indLabel"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="regressionData.coefficients.slope.coef"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r" x-text="regressionData.coefficients.slope.stdError"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r" x-text="regressionData.coefficients.slope.beta"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r" x-text="regressionData.coefficients.slope.tValue"></td>
                                            <td class="p-3 border-b text-xs font-black text-center border-r" :class="regressionData.coefficients.slope.significant ? 'text-green-600' : 'text-gray-500'" x-text="regressionData.coefficients.slope.pValue"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-l" x-text="regressionData.coefficients.slope.ciLower"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="regressionData.coefficients.slope.ciUpper"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- 5. Multiple Linear Regression Results -->
                <template x-if="testMethod === 'regression_multiple' && multipleRegressionData">
                    <div class="space-y-8 animate-in fade-in duration-500">
                        <!-- Mathematical Formula Banner -->
                        <div class="bg-gradient-to-r from-indigo-900 to-indigo-950 rounded-3xl p-6 text-white border border-indigo-950 shadow-sm flex flex-col md:flex-row gap-4 items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-500/20 flex items-center justify-center text-indigo-300">
                                    <i class="fa-solid fa-square-root-variable text-lg"></i>
                                </div>
                                <div>
                                    <h5 class="text-[9px] font-black text-indigo-300 uppercase tracking-widest">{{ __('Computed Regression Model Equation') }}</h5>
                                    <p class="text-sm font-bold mt-1" x-text="multipleRegressionData.equation"></p>
                                </div>
                            </div>
                            <div class="text-[10px] font-medium text-indigo-200 bg-white/10 px-3 py-1.5 rounded-lg border border-white/10">
                                <i class="fa-solid fa-circle-info mr-1"></i> {{ __('OLS Parameter Estimates') }}
                            </div>
                        </div>

                        <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm">
                            <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('Variables Entered/Removed') }}</h5>
                            <div class="overflow-x-auto mb-8">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                            <th class="p-3 border-b">{{ __('Model') }}</th>
                                            <th class="p-3 border-b">{{ __('Variables Entered') }}</th>
                                            <th class="p-3 border-b">{{ __('Variables Removed') }}</th>
                                            <th class="p-3 border-b">{{ __('Method') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="p-3 border-b text-xs font-semibold text-gray-800">1</td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600" x-text="multipleRegressionData.indLabels.join(', ')"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600">.</td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600">{{ __('Enter') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('Model Summary') }}</h5>
                            <div class="overflow-x-auto mb-8">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                            <th class="p-3 border-b text-center">{{ __('Model') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('R') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('R Square') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('Adjusted R Square') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('Std. Error of the Estimate') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="p-3 border-b text-xs font-semibold text-gray-800 text-center">1</td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="multipleRegressionData.r"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="multipleRegressionData.r2"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="multipleRegressionData.adjR2"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="multipleRegressionData.stdErrorEst"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('ANOVA (Regression significance)') }}</h5>
                            <div class="overflow-x-auto mb-8">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                            <th class="p-3 border-b"></th>
                                            <th class="p-3 border-b text-center">{{ __('Sum of Squares') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('df') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('Mean Square') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('F') }}</th>
                                            <th class="p-3 border-b text-center">{{ __('Sig.') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="p-3 border-b text-xs font-semibold text-gray-800">{{ __('Regression') }}</td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="multipleRegressionData.anova.ssr"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="multipleRegressionData.anova.dfReg"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="multipleRegressionData.anova.msr"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="multipleRegressionData.anova.fValue" rowspan="2" style="vertical-align: middle;"></td>
                                            <td class="p-3 border-b text-xs font-black text-center" :class="multipleRegressionData.anova.significant ? 'text-green-600' : 'text-gray-500'" x-text="multipleRegressionData.anova.pValue" rowspan="2" style="vertical-align: middle;"></td>
                                        </tr>
                                        <tr>
                                            <td class="p-3 border-b text-xs font-semibold text-gray-800">{{ __('Residual') }}</td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="multipleRegressionData.anova.sse"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="multipleRegressionData.anova.dfRes"></td>
                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="multipleRegressionData.anova.mse"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('Coefficients') }}</h5>
                             <div class="overflow-x-auto">
                                 <table class="w-full text-left border-collapse min-w-[700px]">
                                     <thead>
                                         <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                             <th class="p-3 border-b" rowspan="2">{{ __('Model') }}</th>
                                             <th class="p-3 border-b text-center border-r" colspan="2">{{ __('Unstandardized Coefficients') }}</th>
                                             <th class="p-3 border-b text-center border-r" rowspan="2">{{ __('Standardized Coefficients (Beta)') }}</th>
                                             <th class="p-3 border-b text-center border-r" rowspan="2">{{ __('t') }}</th>
                                             <th class="p-3 border-b text-center border-r" rowspan="2">{{ __('Sig.') }}</th>
                                             <th class="p-3 border-b text-center" colspan="2">{{ __('95.0% Confidence Interval for B') }}</th>
                                         </tr>
                                         <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                             <th class="p-3 border-b text-center">{{ __('B') }}</th>
                                             <th class="p-3 border-b text-center border-r">{{ __('Std. Error') }}</th>
                                             <th class="p-3 border-b text-center border-l">{{ __('Lower Bound') }}</th>
                                             <th class="p-3 border-b text-center">{{ __('Upper Bound') }}</th>
                                         </tr>
                                     </thead>
                                     <tbody>
                                         <template x-for="coef in multipleRegressionData.coefficients" :key="coef.variable">
                                             <tr class="hover:bg-gray-50/50 transition-colors">
                                                 <td class="p-3 border-b text-xs font-semibold text-gray-800" x-text="coef.label"></td>
                                                 <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="coef.coef"></td>
                                                 <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r" x-text="coef.stdError"></td>
                                                 <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r font-bold" :class="coef.beta !== 'N/A' ? 'text-indigo-600' : 'text-gray-400'" x-text="coef.beta"></td>
                                                 <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r" x-text="coef.tValue"></td>
                                                 <td class="p-3 border-b text-xs font-black text-center border-r" :class="coef.significant ? 'text-green-600' : 'text-gray-500'" x-text="coef.pValue"></td>
                                                 <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-l" x-text="coef.ciLower"></td>
                                                 <td class="p-3 border-b text-xs font-medium text-gray-600 text-center" x-text="coef.ciUpper"></td>
                                             </tr>
                                         </template>
                                     </tbody>
                                 </table>
                             </div>
                        </div>
                    </div>
                </template>

                <!-- Copy / Flow actions for successful calculation -->
                <template x-if="matrixData || tTestData || anovaData || correlationData || regressionData || multipleRegressionData">
                    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm flex flex-wrap gap-4 items-center justify-between">
                        <button @click="copyResultsToClipboard()" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
                            <i class="fa-solid fa-copy"></i> {{ __('Copy Results to Clipboard') }}
                        </button>
                        
                        <div class="flex items-center gap-3">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ __('Continue Analysis Flow:') }}</span>
                            <template x-if="testMethod === 'correlation'">
                                <button @click="testMethod = 'regression'; depVar = varY; groupVar = varX; runAnalysis();" class="text-[10px] font-black text-indigo-600 hover:text-indigo-800 uppercase tracking-widest transition-all">
                                    {{ __('Run Simple Regression') }} <i class="fa-solid fa-arrow-right ml-1"></i>
                                </button>
                            </template>
                            <template x-if="testMethod === 'regression'">
                                <button @click="testMethod = 'regression_multiple'; indVars = [groupVar]; runAnalysis();" class="text-[10px] font-black text-indigo-600 hover:text-indigo-800 uppercase tracking-widest transition-all">
                                    {{ __('Continue to Multiple Regression') }} <i class="fa-solid fa-arrow-right ml-1"></i>
                                </button>
                            </template>
                            <template x-if="testMethod === 'ttest'">
                                <button @click="testMethod = 'anova'; runAnalysis();" class="text-[10px] font-black text-indigo-600 hover:text-indigo-800 uppercase tracking-widest transition-all">
                                    {{ __('Run ANOVA check') }} <i class="fa-solid fa-arrow-right ml-1"></i>
                                </button>
                            </template>
                            <template x-if="testMethod === 'crosstab'">
                                <button @click="testMethod = 'correlation'; varX = rowVar; varY = colVar;" class="text-[10px] font-black text-indigo-600 hover:text-indigo-800 uppercase tracking-widest transition-all">
                                    {{ __('Check Pearson Correlation') }} <i class="fa-solid fa-arrow-right ml-1"></i>
                                </button>
                            </template>
                            <template x-if="testMethod === 'regression_multiple' || testMethod === 'anova'">
                                <span class="text-[10px] text-gray-400 italic">{{ __('Analysis flow complete.') }}</span>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- AI Statistical Intelligence Card -->
                <template x-if="matrixData || tTestData || anovaData || correlationData || regressionData || multipleRegressionData">
                    <div class="mt-8 pt-8 border-t border-gray-100 flex flex-col items-center">
                        <div x-show="aiLoading" class="flex items-center gap-3 text-indigo-600 py-4">
                            <i class="fa-solid fa-circle-notch fa-spin text-xl"></i>
                            <span class="text-[10px] font-black uppercase tracking-widest">{{ __('Analyzing Statistical Significance...') }}</span>
                        </div>
                        
                        <div x-show="aiMessages.length > 0" class="w-full bg-gradient-to-br from-indigo-50 to-white rounded-3xl p-8 border border-indigo-100 shadow-inner relative overflow-hidden">
                            <i class="fa-solid fa-brain absolute right-[-20px] top-[-20px] text-[120px] text-indigo-600/5"></i>
                            <div class="relative z-10 flex flex-col md:flex-row gap-6 items-start">
                                <div class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center text-white shrink-0 shadow-lg shadow-indigo-200">
                                    <i class="fa-solid fa-comments text-lg"></i>
                                </div>
                                <div class="w-full">
                                    <h5 class="text-[10px] font-black text-indigo-600 uppercase tracking-widest mb-3">{{ __('Statistical Intelligence') }}</h5>
                                    
                                    <!-- Chat Logs -->
                                    <div class="space-y-4 py-2 w-full">
                                        <template x-for="(msg, index) in aiMessages" :key="index">
                                            <div class="flex flex-col mb-1" :class="msg.role === 'user' ? 'items-end' : 'items-start'">
                                                <div class="max-w-[85%] rounded-2xl px-4 py-3 text-[13px] leading-relaxed font-medium"
                                                     :class="msg.role === 'user' 
                                                             ? 'bg-indigo-600 text-white rounded-br-none shadow-sm' 
                                                             : 'bg-white/90 text-gray-800 rounded-bl-none border border-gray-200/50 shadow-sm'">
                                                    <p class="whitespace-pre-wrap" x-text="msg.content"></p>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    
                                    <!-- Polish / Refinement Input -->
                                    <div x-show="!aiPolishing" class="border-t border-indigo-100/50 pt-4 mt-4 w-full">
                                        <div class="flex flex-col md:flex-row gap-3 items-end">
                                            <div class="flex-1 w-full">
                                                <label class="block text-[9px] font-black text-indigo-600 uppercase tracking-widest mb-1.5">{{ __('Refine this statistical interpretation (e.g. "Focus on variable X", "Explain in simple words")') }}</label>
                                                <input x-model="aiFeedback" type="text" placeholder="{{ __('Type instructions to refine...') }}" @keydown.enter="polishAiInsight()" class="w-full bg-white/70 border border-indigo-100 text-xs font-semibold rounded-xl px-3 py-2.5 focus:ring-1 focus:ring-indigo-500 focus:outline-none transition-all">
                                            </div>
                                            <button @click="polishAiInsight()" :disabled="aiPolishing || !aiFeedback.trim()" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all disabled:opacity-50 flex items-center gap-1.5 self-stretch justify-center whitespace-nowrap">
                                                <i class="fa-solid fa-paper-plane" :class="{'fa-spin': aiPolishing}"></i>
                                                <span x-text="aiPolishing ? '{{ __('Polishing...') }}' : '{{ __('Polish') }}'"></span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Export Chat Actions -->
                                    <div x-show="aiMessages.length > 0 && !aiLoading" class="mt-4 pt-3 border-t border-indigo-100/30 flex items-center justify-between" style="display: none;">
                                        <div class="flex items-center gap-3">
                                            <button @click="copyFinalOutput()" class="flex items-center gap-1.5 text-[9px] font-black text-indigo-600 uppercase tracking-widest hover:text-indigo-800 transition-colors">
                                                <i class="fa-solid fa-copy"></i>
                                                {{ __('Copy Output') }}
                                            </button>
                                            <button @click="downloadFinalOutput()" class="flex items-center gap-1.5 text-[9px] font-black text-indigo-600 uppercase tracking-widest hover:text-indigo-800 transition-colors">
                                                <i class="fa-solid fa-download"></i>
                                                {{ __('Export TXT') }}
                                            </button>
                                        </div>
                                        <button @click="aiMessages = []; aiInsight = null; aiFeedback = '';" class="text-[9px] font-black text-red-500 hover:text-red-700 uppercase tracking-widest transition-colors">
                                            {{ __('Reset') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button x-show="!aiLoading && aiMessages.length === 0" @click="getAiInterpretation()"
                            class="px-8 py-4 bg-indigo-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 flex items-center gap-3 group">
                            <i class="fa-solid fa-brain group-hover:scale-110 transition-transform"></i> {{ __('Interpret with AI') }}
                        </button>
                    </div>
                </template>
            </div>
        </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
        <script>
            const chartConfigs = {!! json_encode($chartConfigs) !!};
            const chartInstances = {};

            const colorPalettes = {
                indigo: ['#4f46e5', '#6366f1', '#818cf8', '#a5b4fc', '#c7d2fe', '#e0e7ff', '#3730a3', '#312e81'],
                emerald: ['#10b981', '#059669', '#34d399', '#6ee7b7', '#a7f3d0', '#d1fae5', '#065f46', '#064e3b'],
                rose: ['#f43f5e', '#e11d48', '#fb7185', '#fda4af', '#fecdd3', '#fff1f2', '#9f1239', '#881337'],
                amber: ['#f59e0b', '#d97706', '#fbbf24', '#fcd34d', '#fde68a', '#fef3c7', '#b45309', '#92400e'],
                purple: ['#8b5cf6', '#7c3aed', '#a78bfa', '#c4b5fd', '#ddd6fe', '#ede9fe', '#5b21b6', '#4c1d95'],
                vibrant: ['#6366f1', '#10b981', '#f43f5e', '#f59e0b', '#8b5cf6', '#06b6d4', '#ec4899', '#f97316'],
                greyscale: ['#374151', '#4b5563', '#6b7280', '#9ca3af', '#d1d5db', '#e5e7eb', '#1f2937', '#111827']
            };

            window.copyChartToClipboard = function(canvasId, btn = null) {
                const canvas = document.getElementById(canvasId);
                if (!canvas) return;

                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = canvas.width;
                tempCanvas.height = canvas.height;
                const tempCtx = tempCanvas.getContext('2d');

                // Draw solid white background
                tempCtx.fillStyle = '#ffffff';
                tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
                tempCtx.drawImage(canvas, 0, 0);

                const dataUrl = tempCanvas.toDataURL('image/png');
                const chart = chartInstances[canvasId];
                let htmlContent = '';

                if (chart) {
                    const mapName = `map-${canvasId}-${Math.random().toString(36).substr(2, 9)}`;
                    let areas = [];
                    const datasets = chart.data.datasets;
                    
                    if (datasets && datasets[0]) {
                        const meta = chart.getDatasetMeta(0);
                        const labels = chart.data.labels;
                        const data = datasets[0].data;

                        if (chart.config.type === 'bar') {
                            const isHorizontal = chart.config.options?.indexAxis === 'y';
                            meta.data.forEach((element, index) => {
                                const view = element;
                                const label = labels[index] || '';
                                const rawVal = data[index] || 0;
                                let left, right, top, bottom;

                                if (isHorizontal) {
                                    left = view.base;
                                    right = view.x;
                                    top = view.y - view.height / 2;
                                    bottom = view.y + view.height / 2;
                                } else {
                                    left = view.x - view.width / 2;
                                    right = view.x + view.width / 2;
                                    top = view.y;
                                    bottom = view.base;
                                }
                                areas.push(`<area shape="rect" coords="${Math.round(left)},${Math.round(top)},${Math.round(right)},${Math.round(bottom)}" title="${label}: ${rawVal}%" alt="${label}" />`);
                            });
                        } else if (['pie', 'doughnut', 'polarArea'].includes(chart.config.type)) {
                            meta.data.forEach((element, index) => {
                                const view = element;
                                const label = labels[index] || '';
                                const rawVal = data[index] || 0;
                                const cx = view.x;
                                const cy = view.y;
                                const r = view.outerRadius;
                                const start = view.startAngle;
                                const end = view.endAngle;
                                
                                let coords = [];
                                coords.push(`${cx},${cy}`);
                                const steps = 16;
                                for (let i = 0; i <= steps; i++) {
                                    const angle = start + (end - start) * (i / steps);
                                    const px = cx + r * Math.cos(angle);
                                    const py = cy + r * Math.sin(angle);
                                    coords.push(`${Math.round(px)},${Math.round(py)}`);
                                }
                                areas.push(`<area shape="poly" coords="${coords.join(',')}" title="${label}: ${rawVal}%" alt="${label}" />`);
                            });
                        }
                    }

                    if (areas.length > 0) {
                        htmlContent = `<img src="${dataUrl}" usemap="#${mapName}" style="max-width:100%;height:auto;" />
<map name="${mapName}">
  ${areas.join('\n  ')}
</map>`;
                    }
                }

                if (!htmlContent) {
                    htmlContent = `<img src="${dataUrl}" style="max-width:100%;height:auto;" />`;
                }

                tempCanvas.toBlob(blob => {
                    if (!blob) return;
                    const htmlBlob = new Blob([htmlContent], { type: 'text/html' });
                    
                    navigator.clipboard.write([
                        new ClipboardItem({
                            'image/png': blob,
                            'text/html': htmlBlob
                        })
                    ]).then(() => {
                        if (btn) {
                            const btnSpan = btn.querySelector('span');
                            const originalText = btnSpan.innerText;
                            btnSpan.innerText = 'Copied!';
                            btn.classList.add('bg-green-600', 'text-white');
                            setTimeout(() => {
                                btnSpan.innerText = originalText;
                                btn.classList.remove('bg-green-600', 'text-white');
                            }, 2000);
                        }
                    }).catch(err => {
                        console.error('Copy chart failed:', err);
                        Swal.fire({
                            title: @js(__('Copy Failed')),
                            text: @js(__('Could not copy chart to clipboard.')),
                            icon: 'error',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    });
                }, 'image/png');
            };

            window.exportChartToPng = function(canvasId, title) {
                const canvas = document.getElementById(canvasId);
                if (!canvas) return;

                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = canvas.width;
                tempCanvas.height = canvas.height;
                const tempCtx = tempCanvas.getContext('2d');

                // Draw white background
                tempCtx.fillStyle = '#ffffff';
                tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
                tempCtx.drawImage(canvas, 0, 0);

                const url = tempCanvas.toDataURL('image/png');
                const link = document.createElement('a');
                link.download = `${title.replace(/[^a-z0-9]/gi, '_').toLowerCase()}_chart.png`;
                link.href = url;
                link.click();
            };

            window.exportTableToCsv = function(tableId, filename) {
                const table = document.getElementById(tableId);
                if (!table) return;

                const rows = Array.from(table.querySelectorAll('tr'));
                const csvContent = rows.map(row => {
                    const cols = Array.from(row.querySelectorAll('th, td'));
                    return cols.map(col => {
                        let text = col.innerText.trim();
                        text = text.replace(/"/g, '""');
                        return `"${text}"`;
                    }).join(',');
                }).join('\n');

                const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `${filename.replace(/[^a-z0-9]/gi, '_').toLowerCase()}_table.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            };

            window.copyTableToClipboard = function(tableId) {
                const table = document.getElementById(tableId);
                if (!table) return;

                const clone = table.cloneNode(true);
                clone.style.borderCollapse = 'collapse';
                clone.style.width = '100%';
                clone.style.fontFamily = 'Arial, sans-serif';
                clone.style.fontSize = '13px';

                clone.querySelectorAll('th').forEach(th => {
                    th.style.border = '1px solid #d4d4d8';
                    th.style.padding = '8px 12px';
                    th.style.backgroundColor = '#f4f4f5';
                    th.style.fontWeight = 'bold';
                    th.style.textAlign = th.classList.contains('text-right') ? 'right' : 'left';
                });
                clone.querySelectorAll('td').forEach(td => {
                    td.style.border = '1px solid #d4d4d8';
                    td.style.padding = '8px 12px';
                    td.style.textAlign = td.classList.contains('text-right') ? 'right' : 'left';
                });

                const htmlContent = `<table>${clone.innerHTML}</table>`;
                const plainText = Array.from(table.querySelectorAll('tr'))
                    .map(row => Array.from(row.querySelectorAll('th, td')).map(c => c.innerText.trim()).join('\t'))
                    .join('\n');

                const blobHtml = new Blob([htmlContent], { type: 'text/html' });
                const blobText = new Blob([plainText], { type: 'text/plain' });

                navigator.clipboard.write([
                    new ClipboardItem({ 'text/html': blobHtml, 'text/plain': blobText })
                ]).then(() => {
                    Swal.fire({
                        title: @js(__('Copied!')),
                        text: @js(__('Table copied. Paste directly into Word or Google Docs.')),
                        icon: 'success',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        customClass: { popup: 'rounded-2xl shadow-xl border-none' }
                    });
                }).catch(() => {
                    navigator.clipboard.writeText(plainText);
                });
            };

            window.copyChartToClipboard = function(canvasId, btn = null) {
                const canvas = document.getElementById(canvasId);
                if (!canvas) return;

                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = canvas.width;
                tempCanvas.height = canvas.height;
                const tempCtx = tempCanvas.getContext('2d');
                tempCtx.fillStyle = '#ffffff';
                tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
                tempCtx.drawImage(canvas, 0, 0);

                tempCanvas.toBlob(blob => {
                    if (!blob) return;
                    navigator.clipboard.write([
                        new ClipboardItem({ 'image/png': blob })
                    ]).then(() => {
                        if (btn) {
                            const span = btn.querySelector('span');
                            const orig = span ? span.innerText : btn.innerHTML;
                            if (span) span.innerText = 'Copied!';
                            btn.classList.add('!bg-emerald-600', '!text-white');
                            setTimeout(() => {
                                if (span) span.innerText = orig;
                                btn.classList.remove('!bg-emerald-600', '!text-white');
                            }, 2000);
                        }
                    }).catch(err => {
                        console.error('Copy chart failed:', err);
                        Swal.fire({
                            title: @js(__('Copy Failed')),
                            text: @js(__('Your browser blocked clipboard access. Try exporting instead.')),
                            icon: 'warning', toast: true, position: 'top-end',
                            showConfirmButton: false, timer: 3000
                        });
                    });
                }, 'image/png');
            };

            window.copyRenderedSociusTable = function(tableId, btn = null) {
                const table = document.getElementById(tableId);
                if (!table) return;

                let text = '';
                const rows = table.querySelectorAll('tr');
                rows.forEach((row) => {
                    const cols = row.querySelectorAll('th, td');
                    const rowData = [];
                    cols.forEach(col => {
                        rowData.push(col.innerText.trim());
                    });
                    text += rowData.join('\t') + '\n';
                });

                navigator.clipboard.writeText(text).then(() => {
                    if (btn) {
                        const originalHtml = btn.innerHTML;
                        btn.innerHTML = '<i class="fa-solid fa-check text-[10px] text-green-400"></i> Copied!';
                        setTimeout(() => {
                            btn.innerHTML = originalHtml;
                        }, 2000);
                    }
                }).catch(err => {
                    console.error('Failed to copy table: ', err);
                });
            };

            window.exportTableToPng = function(containerId, title) {
                const element = document.getElementById(containerId);
                if (!element) return;

                let loadingAlert = Swal.fire({
                    title: @js(__('Exporting Table...')),
                    text: @js(__('Generating ready-to-use PNG image. Please wait.')),
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                html2canvas(element, {
                    backgroundColor: '#ffffff',
                    scale: 2,
                    useCORS: true,
                    logging: false
                }).then(canvas => {
                    const url = canvas.toDataURL('image/png');
                    const link = document.createElement('a');
                    link.download = `${title.replace(/[^a-z0-9]/gi, '_').toLowerCase()}_table.png`;
                    link.href = url;
                    link.click();
                    Swal.close();
                }).catch(err => {
                    console.error("html2canvas error", err);
                    Swal.fire({
                        title: @js(__('Export Failed')),
                        text: @js(__('Could not generate the table image.')),
                        icon: 'error'
                    });
                });
            };

            function formatShortCategoryTheme(rawText) {
                if (!rawText || typeof rawText !== 'string') return 'Choices';
                let str = rawText.trim();
                if (!str) return 'Choices';

                const originalText = str;

                // Clean leading indices e.g. "1. ", "#1 ", "Q1: "
                str = str.replace(/^(#|\bQ)?\d+[\.\:\)\s]+/i, '').trim();

                // 1. Likert / Matrix separation e.g. "Indicate whether... - AMIS has improved..."
                const separators = [' - ', ' -- ', ' — ', ' – ', ' : '];
                for (const sep of separators) {
                    if (str.includes(sep)) {
                        const parts = str.split(sep);
                        const firstPartLower = parts[0].toLowerCase();
                        if (firstPartLower.includes('disagree') || firstPartLower.includes('agree') || firstPartLower.includes('rate') || firstPartLower.includes('indicate') || firstPartLower.includes('scale')) {
                            str = parts.slice(1).join(sep).trim();
                            break;
                        }
                    }
                }

                // 2. Specific Question Form Transformations:

                // A) "How likely are you to [verb phrase]" -> "Likelihood to [verb phrase]"
                if (/^how\s+likely\s+(are\s+you|is\s+it)\s+to\s+(.*)/i.test(str)) {
                    const verb = str.replace(/^how\s+likely\s+(are\s+you|is\s+it)\s+to\s+/i, '').replace(/[\?\:\.]+$|\s+$/g, '').trim();
                    str = `Likelihood to ${verb}`;
                }
                // B) "How many [noun] have you / do you / did you [verb] at/in/on [place]?"
                // e.g. "How many years have you spent at this university?" -> "Years Spent at University"
                else if (/^how\s+many\s+([a-z0-9\s]+?)\s+(have\s+you|do\s+you|did\s+you|are\s+you)\s+(spent|worked|studied|lived|been)\s+(at|in|on|with|for)\s+(this|the|a|an)?\s*(.*)/i.test(str)) {
                    str = str.replace(/^how\s+many\s+([a-z0-9\s]+?)\s+(have\s+you|do\s+you|did\s+you|are\s+you)\s+(spent|worked|studied|lived|been)\s+(at|in|on|with|for)\s+(this|the|a|an)?\s*(.*)/i, '$1 Spent $4 $6').trim();
                }
                // C) "How many [noun] do/have/did you [verb]..."
                else if (/^how\s+many\s+([a-z0-9\s]+?)\s+(have\s+you|do\s+you|did\s+you|are\s+there|were\s+there)\s*(.*)/i.test(str)) {
                    const match = str.match(/^how\s+many\s+([a-z0-9\s]+?)\s+(have\s+you|do\s+you|did\s+you|are\s+there|were\s+there)\s*(.*)/i);
                    const noun = match[1].trim();
                    let rest = match[3].trim().replace(/[\?\:\.]+$|\s+$/g, '');
                    rest = rest.replace(/^(been|had|done|got|taken)\s+/i, '');
                    str = rest ? `${noun} ${rest}` : noun;
                }
                // D) "What type/kind/category/level of [noun] are you / do you / is ..."
                // e.g. "What type of university are you currently working in or attending?" -> "Type of University"
                else if (/^what\s+(type|kind|category|level|sort|form|class|sector|mode)\s+of\s+([a-z0-9\s]+?)\s+(are\s+you|do\s+you|have\s+you|is\s+|were\s+|did\s+).*/i.test(str)) {
                    const match = str.match(/^what\s+(type|kind|category|level|sort|form|class|sector|mode)\s+of\s+([a-z0-9\s]+?)\s+(are\s+you|do\s+you|have\s+you|is\s+|were\s+|did\s+).*/i);
                    const typeWord = match[1].trim();
                    const mainNoun = match[2].trim();
                    str = `${typeWord} of ${mainNoun}`;
                }
                // E) "What is your [noun]?" / "What are your [noun]?"
                else if (/^what\s+(is|are)\s+(your|the)\s+([a-z0-9\s]+?)[\?\:\.]*$/i.test(str)) {
                    str = str.replace(/^what\s+(is|are)\s+(your|the)\s+/i, '');
                }
                // F) "How satisfied are you with [noun]?" -> "Satisfaction with [noun]"
                else if (/^how\s+satisfied\s+are\s+you\s+(with|about)\s+(the|your)?\s*(.*)/i.test(str)) {
                    const item = str.replace(/^how\s+satisfied\s+are\s+you\s+(with|about)\s+(the|your)?\s*/i, '').replace(/[\?\:\.]+$|\s+$/g, '').trim();
                    str = `Satisfaction with ${item}`;
                }
                // G) General prompt prefix stripping:
                else {
                    const prefixes = [
                        /^please\s+indicate\s+(whether\s+you\s+)?(strongly\s+disagree[^\-\:]*[\-\:])?\s*(your\s+|the\s+)?/i,
                        /^please\s+(specify|select|state|provide|rate|choose|enter)\s+(your\s+|the\s+)?/i,
                        /^indicate\s+(whether\s+you\s+)?(strongly\s+disagree[^\-\:]*[\-\:])?\s*(your\s+|the\s+)?/i,
                        /^(specify|select|state|provide|rate|choose|enter)\s+(your\s+|the\s+)?/i,
                        /^what\s+is\s+(your\s+|the\s+)?/i,
                        /^what\s+are\s+(your\s+|the\s+)?/i,
                        /^which\s+of\s+the\s+following\s+(best\s+describes\s+)?(your\s+|the\s+)?/i,
                        /^which\s+(category|option|one)\s+(best\s+describes\s+)?(your\s+|the\s+)?/i,
                        /^how\s+would\s+you\s+rate\s+(your\s+|the\s+)?/i,
                        /^how\s+satisfied\s+are\s+you\s+with\s+(your\s+|the\s+)?/i,
                        /^how\s+(often|long)\s+do\s+you\s+/i,
                        /^to\s+what\s+extent\s+(do\s+you\s+agree|do\s+you\s+feel)?\s*(that\s+)?(the\s+|your\s+)?/i,
                        /^do\s+you\s+agree\s+(or\s+disagree\s+)?(that\s+)?(the\s+|your\s+)?/i,
                        /^kindly\s+(indicate|state|specify|select)\s+(your\s+|the\s+)?/i
                    ];

                    for (const ptn of prefixes) {
                        if (ptn.test(str)) {
                            str = str.replace(ptn, '').trim();
                            break;
                        }
                    }
                }

                // Clean punctuation & trailing filler words
                str = str.replace(/[\?\:\.]+$|\s+$/g, '').trim();
                str = str.replace(/^(your|the|a|an)\s+/i, '').trim();

                if (!str) return originalText;

                // Capitalize Title Case
                const words = str.split(/\s+/);
                const formatted = words.map((w, idx) => {
                    if (!w) return '';
                    const lower = w.toLowerCase();
                    if (idx > 0 && ['of', 'in', 'at', 'on', 'for', 'to', 'with', 'and', 'or', 'a', 'an', 'the'].includes(lower)) {
                        return lower;
                    }
                    return w.charAt(0).toUpperCase() + w.slice(1);
                }).join(' ');

                return formatted.charAt(0).toUpperCase() + formatted.slice(1);
            }

            function createChart(canvasId, config, type = 'bar', colorTheme = 'indigo') {
                const canvasElement = document.getElementById(canvasId);
                if (!canvasElement) return;

                if (chartInstances[canvasId]) {
                    chartInstances[canvasId].destroy();
                    delete chartInstances[canvasId];
                }

                const ctx = canvasElement.getContext('2d');

                const activeTheme = colorTheme && colorPalettes[colorTheme] ? colorTheme : 'indigo';
                const palette = colorPalettes[activeTheme] || colorPalettes['indigo'];
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

                // 1. Calculate overall responses sum to compute percentage values
                const totalResponses = config.data.reduce((sum, val) => sum + val, 0);
                const percentageData = config.data.map(val => 
                    totalResponses > 0 ? parseFloat(((val / totalResponses) * 100).toFixed(1)) : 0
                );

                const isCategorical = ['pie', 'doughnut', 'polarArea', 'bar', 'horizontal'].includes(type);

                // 2. Custom inline plugin to render frequency and percentage above elements
                const datalabelsPlugin = {
                    id: 'customDatalabels',
                    afterDatasetsDraw(chart) {
                        const { ctx } = chart;
                        ctx.save();
                        chart.data.datasets.forEach((dataset, i) => {
                            const meta = chart.getDatasetMeta(i);
                            meta.data.forEach((element, index) => {
                                const pctVal = dataset.data[index];
                                const text = `${pctVal}%`;

                                ctx.fillStyle = '#475569';
                                ctx.font = 'bold 9px Inter, sans-serif';
                                
                                if (chart.options.indexAxis === 'y') {
                                    ctx.textAlign = 'left';
                                    ctx.textBaseline = 'middle';
                                    ctx.fillText(text, element.x + 6, element.y);
                                } else {
                                    ctx.textAlign = 'center';
                                    ctx.textBaseline = 'bottom';
                                    ctx.fillText(text, element.x, element.y - 6);
                                }
                            });
                        });
                        ctx.restore();
                    }
                };

                const chartConfig = {
                    type: chartType,
                    data: {
                        labels: config.labels,
                        datasets: [{
                            label: 'Responses (%)',
                            data: percentageData, // Y-axis uses percentages
                            backgroundColor: isCategorical ? colors : (fill ? `${primaryColor}44` : primaryColor),
                            borderColor: isCategorical ? (type === 'bar' || type === 'horizontal' ? colors : '#fff') : primaryColor,
                            borderWidth: (type === 'line' || type === 'radar' || type === 'area') ? 3 : 1,
                            maxBarThickness: 45,
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
                                displayColors: true,
                                callbacks: {
                                    label: function(context) {
                                        const index = context.dataIndex;
                                        const rawVal = config.data[index];
                                        return ` ${rawVal} (${context.raw}%)`;
                                    }
                                }
                            }
                        }
                    }
                };

                // Add the custom inline text labels plugin for bar & line layouts
                if (chartType === 'bar' || chartType === 'line') {
                    chartConfig.plugins = [datalabelsPlugin];
                }

                if (chartType === 'bar' || chartType === 'line') {
                    const isHorizontal = indexAxis === 'y';

                    const valueAxisConfig = {
                        beginAtZero: true,
                        grace: '12%', // Add top padding to keep values from clipping
                        grid: { color: '#f8fafc', drawBorder: false },
                        ticks: { 
                            font: { weight: '500', size: 12, color: '#64748b' },
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        title: {
                            display: true,
                            text: 'Percentage(%)',
                            color: '#64748b',
                            font: { weight: '600', size: 12 }
                        }
                    };
                    const labelAxisConfig = {
                        grid: { display: false },
                        ticks: { font: { weight: '500', size: 12, color: '#64748b' } },
                        title: {
                            display: true,
                            text: config.short_theme || formatShortCategoryTheme(config.question_name) || 'Choices',
                            color: '#64748b',
                            font: { weight: '600', size: 12 }
                        }
                    };

                    chartConfig.options.scales = {
                        y: isHorizontal ? labelAxisConfig : valueAxisConfig,
                        x: isHorizontal ? valueAxisConfig : labelAxisConfig
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

            window.inferentialManager = function () {
                return {
                    testMethod: 'crosstab',
                    rowVar: '',
                    colVar: '',
                    depVar: '',
                    groupVar: '',
                    varX: '',
                    varY: '',
                    indVars: [],
                    loading: false,
                    aiLoading: false,
                    aiInsight: null,
                    aiFeedback: '',
                    aiPolishing: false,
                    aiMessages: [],
                    
                    matrixData: null,
                    tTestData: null,
                    anovaData: null,
                    correlationData: null,
                    regressionData: null,
                    multipleRegressionData: null,

                    init() {
                        this.$watch('testMethod', () => {
                            this.clearResults();
                        });
                    },

                    clearResults() {
                        this.matrixData = null;
                        this.tTestData = null;
                        this.anovaData = null;
                        this.correlationData = null;
                        this.regressionData = null;
                        this.multipleRegressionData = null;
                        this.aiInsight = null;
                        this.aiFeedback = '';
                        this.aiMessages = [];
                        this.loading = false;
                        this.aiLoading = false;
                        this.aiPolishing = false;
                    },

                    async runAnalysis() {
                        this.clearResults();
                        this.loading = true;

                        let url = `{{ route('surveys.reports.inferential', $survey) }}?method=${this.testMethod}`;
                        if (this.testMethod === 'crosstab') {
                            if (!this.rowVar || !this.colVar) return this.loading = false;
                            url += `&row=${this.rowVar}&col=${this.colVar}`;
                        } else if (this.testMethod === 'ttest' || this.testMethod === 'anova') {
                            if (!this.depVar || !this.groupVar) return this.loading = false;
                            url += `&dep=${this.depVar}&group=${this.groupVar}`;
                        } else if (this.testMethod === 'correlation') {
                            if (!this.varX || !this.varY) return this.loading = false;
                            url += `&varX=${this.varX}&varY=${this.varY}`;
                        } else if (this.testMethod === 'regression') {
                            if (!this.depVar || !this.groupVar) return this.loading = false;
                            url += `&dep=${this.depVar}&ind=${this.groupVar}`;
                        } else if (this.testMethod === 'regression_multiple') {
                            if (!this.depVar || this.indVars.length === 0) return this.loading = false;
                            url += `&dep=${this.depVar}&ind=${this.indVars.join(',')}`;
                        }

                        try {
                            const res = await fetch(url);
                            if (!res.ok) {
                                const errData = await res.json();
                                throw new Error(errData.message || 'Analysis failed.');
                            }
                            const data = await res.json();
                            if (this.testMethod === 'crosstab') {
                                this.matrixData = data;
                            } else if (this.testMethod === 'ttest') {
                                this.tTestData = data;
                            } else if (this.testMethod === 'anova') {
                                this.anovaData = data;
                            } else if (this.testMethod === 'correlation') {
                                this.correlationData = data;
                            } else if (this.testMethod === 'regression') {
                                this.regressionData = data;
                            } else if (this.testMethod === 'regression_multiple') {
                                this.multipleRegressionData = data;
                            }
                            
                            // Auto-trigger AI Interpretation immediately
                            this.$nextTick(() => {
                                this.getAiInterpretation();
                            });
                        } catch (err) {
                            alert("Analysis Error: " + err.message);
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

                    async getAiInterpretation() {
                        let currentData = null;
                        if (this.testMethod === 'crosstab') currentData = this.matrixData;
                        else if (this.testMethod === 'ttest') currentData = this.tTestData;
                        else if (this.testMethod === 'anova') currentData = this.anovaData;
                        else if (this.testMethod === 'correlation') currentData = this.correlationData;
                        else if (this.testMethod === 'regression') currentData = this.regressionData;
                        else if (this.testMethod === 'regression_multiple') currentData = this.multipleRegressionData;

                        if (!currentData) return;
                        this.aiLoading = true;
                        this.aiInsight = null;
                        this.aiMessages = [];

                        try {
                            const res = await fetch(`{{ route('ai.insights.inferential') }}`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({
                                    survey_id: "{{ $survey->id }}",
                                    method: this.testMethod,
                                    data: currentData
                                })
                            });
                            if (!res.ok) {
                                const errData = await res.json();
                                throw new Error(errData.message || 'AI Insight failed.');
                            }
                            const data = await res.json();
                            this.aiInsight = data.insight;
                            this.aiMessages = [{ role: 'assistant', content: data.insight }];
                        } catch (err) {
                            alert("AI Interpretation Error: " + err.message);
                        } finally {
                            this.aiLoading = false;
                        }
                    },

                    async polishAiInsight() {
                        if (!this.aiFeedback.trim() || this.aiMessages.length === 0) return;
                        const userMsg = this.aiFeedback.trim();
                        this.aiMessages.push({ role: 'user', content: userMsg });
                        this.aiFeedback = '';
                        this.aiPolishing = true;
                        try {
                            let currentData = null;
                            if (this.testMethod === 'crosstab') currentData = this.matrixData;
                            else if (this.testMethod === 'ttest') currentData = this.tTestData;
                            else if (this.testMethod === 'anova') currentData = this.anovaData;
                            else if (this.testMethod === 'correlation') currentData = this.correlationData;
                            else if (this.testMethod === 'regression') currentData = this.regressionData;
                            else if (this.testMethod === 'regression_multiple') currentData = this.multipleRegressionData;

                            const res = await fetch(`{{ route('ai.insights.inferential') }}`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({
                                    survey_id: "{{ $survey->id }}",
                                    messages: this.aiMessages,
                                    feedback: userMsg,
                                    method: this.testMethod,
                                    data: currentData
                                })
                            });
                            if (!res.ok) {
                                const errData = await res.json();
                                throw new Error(errData.message || 'AI Polish failed.');
                            }
                            const data = await res.json();
                            if (data.success) {
                                let insightText = data.insight;
                                const jsonMatch = insightText.match(/```json\s*([\s\S]*?)\s*```/);
                                if (jsonMatch) {
                                    try {
                                        const parsed = JSON.parse(jsonMatch[1].trim());
                                        if (parsed.action === 'recalculate') {
                                            if (parsed.rowVar) this.rowVar = parsed.rowVar;
                                            if (parsed.colVar) this.colVar = parsed.colVar;
                                            if (parsed.depVar) this.depVar = parsed.depVar;
                                            if (parsed.groupVar) this.groupVar = parsed.groupVar;
                                            if (parsed.varX) this.varX = parsed.varX;
                                            if (parsed.varY) this.varY = parsed.varY;
                                            if (parsed.indVars) this.indVars = parsed.indVars;
                                            if (parsed.testMethod) this.testMethod = parsed.testMethod;
                                            
                                            // Pre-append messages thread and trigger re-run
                                            this.$nextTick(() => {
                                                this.runAnalysis();
                                            });
                                            return;
                                        }

                                        if (this.testMethod === 'crosstab' && this.matrixData) {
                                            this.matrixData = { ...this.matrixData, ...parsed };
                                        } else if (this.testMethod === 'regression' && this.regressionData) {
                                            this.regressionData = { ...this.regressionData, ...parsed };
                                        } else if (this.testMethod === 'regression_multiple' && this.multipleRegressionData) {
                                            this.multipleRegressionData = { ...this.multipleRegressionData, ...parsed };
                                        }
                                    } catch (e) {
                                        console.error("Failed to parse updated table JSON from AI:", e);
                                    }
                                    insightText = insightText.replace(/```json\s*[\s\S]*?\s*```/, '').trim();
                                }
                                this.aiInsight = insightText;
                                this.aiMessages.push({ role: 'assistant', content: insightText });
                            } else {
                                throw new Error(data.message || 'AI Polish failed.');
                            }
                        } catch (err) {
                            alert("AI Polish Error: " + err.message);
                            this.aiMessages.pop();
                            this.aiFeedback = userMsg;
                        } finally {
                            this.aiPolishing = false;
                        }
                    },

                    copyFinalOutput() {
                        const lastMsg = [...this.aiMessages].reverse().find(m => m.role === 'assistant');
                        if (!lastMsg) return;
                        navigator.clipboard.writeText(lastMsg.content).then(() => {
                            alert("Copied interpretation to clipboard!");
                        });
                    },

                    downloadFinalOutput() {
                        const lastMsg = [...this.aiMessages].reverse().find(m => m.role === 'assistant');
                        if (!lastMsg) return;
                        const blob = new Blob([lastMsg.content], { type: 'text/plain;charset=utf-8' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `statistical_interpretation_${this.testMethod}.txt`;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    },

                    copyResultsToClipboard() {
                        let text = "";
                        
                        if (this.testMethod === 'crosstab' && this.matrixData) {
                            text += "Cross-Tabulation Matrix: " + this.matrixData.rowLabel + " vs " + this.matrixData.colLabel + "\n";
                            text += "\t" + this.matrixData.columns.join("\t") + "\tTotal\n";
                            this.matrixData.rows.forEach(r => {
                                text += r;
                                this.matrixData.columns.forEach(c => {
                                    text += "\t" + this.getMatrixValue(r, c);
                                });
                                text += "\t" + (this.matrixData.rowTotals[r] || 0) + "\n";
                            });
                            text += "Total";
                            this.matrixData.columns.forEach(c => {
                                text += "\t" + (this.matrixData.colTotals[c] || 0);
                            });
                            text += "\t" + this.matrixData.grandTotal + "\n\n";
                            text += "Chi-Square Test:\n";
                            text += "Pearson Chi-Square\tValue: " + this.matrixData.chiSquare + "\tdf: " + this.matrixData.df + "\tSig: " + this.matrixData.pValue + "\n";
                        }
                        
                        else if (this.testMethod === 'ttest' && this.tTestData) {
                            text += "Independent Samples T-Test: Group Descriptives\n";
                            text += "Group\tN\tMean\tStd. Deviation\tStd. Error Mean\n";
                            this.tTestData.groups.forEach(g => {
                                text += g.name + "\t" + g.n + "\t" + g.mean + "\t" + g.stdDev + "\t" + g.stdError + "\n";
                            });
                            text += "\nT-Test statistics:\nt\tdf\tSig. (2-tailed)\tMean Difference\n";
                            text += this.tTestData.tValue + "\t" + this.tTestData.df + "\t" + this.tTestData.pValue + "\t" + this.tTestData.meanDiff + "\n";
                        }
                        
                        else if (this.testMethod === 'correlation' && this.correlationData) {
                            text += "Pearson Correlation matrix:\n";
                            text += "\t" + this.correlationData.labelX + "\t" + this.correlationData.labelY + "\n";
                            text += this.correlationData.labelX + "\tr=1.000\tr=" + this.correlationData.r + " (p=" + this.correlationData.pValue + ", N=" + this.correlationData.n + ")\n";
                            text += this.correlationData.labelY + "\tr=" + this.correlationData.r + " (p=" + this.correlationData.pValue + ", N=" + this.correlationData.n + ")\tr=1.000\n";
                            text += "\nCovariance: " + this.correlationData.covariance + "\tStd. Error: " + this.correlationData.stdErrorR + "\t95% CI: [" + this.correlationData.ciLower + ", " + this.correlationData.ciUpper + "]\n";
                        }
                        
                        else if (this.testMethod === 'anova' && this.anovaData) {
                            text += "ANOVA Descriptives:\nGroup\tN\tMean\tStd. Deviation\tStd. Error\n";
                            this.anovaData.groupStats.forEach(g => {
                                text += g.name + "\t" + g.n + "\t" + g.mean + "\t" + g.stdDev + "\t" + g.stdError + "\n";
                            });
                            text += "\nANOVA Source Table:\nSource\tSum of Squares\tdf\tMean Square\tF\tSig.\n";
                            text += "Between Groups\t" + this.anovaData.ssb + "\t" + this.anovaData.dfBetween + "\t" + this.anovaData.msb + "\t" + this.anovaData.fValue + "\t" + this.anovaData.pValue + "\n";
                            text += "Within Groups\t" + this.anovaData.ssw + "\t" + this.anovaData.dfWithin + "\t" + this.anovaData.msw + "\n";
                            text += "Total\t" + this.anovaData.sst + "\t" + this.anovaData.dfTotal + "\n";
                        }
                        
                        else if (this.testMethod === 'regression' && this.regressionData) {
                            text += "Simple Regression Summary:\nR=" + this.regressionData.r + "\tR Square=" + this.regressionData.r2 + "\tAdj R Square=" + this.regressionData.adjR2 + "\tStd Error=" + this.regressionData.stdErrorEst + "\n";
                            text += "\nANOVA (Model Fit):\nSource\tSS\tdf\tMS\tF\tSig.\n";
                            text += "Regression\t" + this.regressionData.anova.ssr + "\t" + this.regressionData.anova.dfReg + "\t" + this.regressionData.anova.msr + "\t" + this.regressionData.anova.fValue + "\t" + this.regressionData.anova.pValue + "\n";
                            text += "Residual\t" + this.regressionData.anova.sse + "\t" + this.regressionData.anova.dfRes + "\t" + this.regressionData.anova.mse + "\n";
                            text += "\nCoefficients:\nModel\tB\tStd. Error\tt\tSig.\n";
                            text += "(Constant)\t" + this.regressionData.coefficients.intercept.coef + "\t" + this.regressionData.coefficients.intercept.stdError + "\t" + this.regressionData.coefficients.intercept.tValue + "\t" + this.regressionData.coefficients.intercept.pValue + "\n";
                            text += "Slope (X)\t" + this.regressionData.coefficients.slope.coef + "\t" + this.regressionData.coefficients.slope.stdError + "\t" + this.regressionData.coefficients.slope.tValue + "\t" + this.regressionData.coefficients.slope.pValue + "\n";
                        }
                        
                        else if (this.testMethod === 'regression_multiple' && this.multipleRegressionData) {
                            text += "Multiple Regression Equation: " + this.multipleRegressionData.equation + "\n\n";
                            text += "Model Summary:\nR=" + this.multipleRegressionData.r + "\tR Square=" + this.multipleRegressionData.r2 + "\tAdj R Square=" + this.multipleRegressionData.adjR2 + "\tStd Error=" + this.multipleRegressionData.stdErrorEst + "\n";
                            text += "\nANOVA (Model Fit):\nSource\tSS\tdf\tMS\tF\tSig.\n";
                            text += "Regression\t" + this.multipleRegressionData.anova.ssr + "\t" + this.multipleRegressionData.anova.dfReg + "\t" + this.multipleRegressionData.anova.msr + "\t" + this.multipleRegressionData.anova.fValue + "\t" + this.multipleRegressionData.anova.pValue + "\n";
                            text += "Residual\t" + this.multipleRegressionData.anova.sse + "\t" + this.multipleRegressionData.anova.dfRes + "\t" + this.multipleRegressionData.anova.mse + "\n";
                            text += "\nCoefficients:\nModel\tB\tStd. Error\tBeta\tt\tSig.\n";
                            this.multipleRegressionData.coefficients.forEach(c => {
                                text += c.label + "\t" + c.coef + "\t" + c.stdError + "\t" + c.beta + "\t" + c.tValue + "\t" + c.pValue + "\n";
                            });
                        }

                        if (!text) {
                            alert("No data available to copy.");
                            return;
                        }

                        navigator.clipboard.writeText(text).then(() => {
                            alert("Results copied in TSV format! You can now paste directly into Excel, Word, or SPSS.");
                        }).catch(err => {
                            console.error(err);
                            alert("Failed to copy results.");
                        });
                    }
                };
            };


            window.chartManager = function () {
                return {
                    chartTypes: {},
                    activeColors: {},
                    init() {
                        chartConfigs.forEach(config => {
                            const el = document.getElementById(config.canvas_id);
                            if (el) {
                                this.chartTypes[config.canvas_id] = 'bar';
                                this.activeColors[config.canvas_id] = 'indigo';
                                chartInstances[config.canvas_id] = createChart(config.canvas_id, config, 'bar', 'indigo');
                            }
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
                        if (chartInstances[canvasId]) {
                            chartInstances[canvasId].destroy();
                            delete chartInstances[canvasId];
                        }
                        const el = document.getElementById(canvasId);
                        if (el && config) {
                            chartInstances[canvasId] = createChart(canvasId, config, this.chartTypes[canvasId] || 'bar', this.activeColors[canvasId] || 'indigo');
                        }
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
                    activeGroupId: config.activeGroupId || null,
                    groups: config.groups || [],
                    isOwner: config.isOwner || false,

                    // Phase 4 Features
                    isListening: false,
                    recognition: null,
                    editingMessageId: null,
                    editingContent: '',
                    isRegenerating: false,
                    tokenUsage: null,
                    webSearchEnabled: false,
                    reviewModeEnabled: false,
                    historyOpen: window.innerWidth > 1280,
                    scrolledUp: false,
                    activePromptId: null,
                    showQuoteButton: false,
                    quoteButtonX: 0,
                    quoteButtonY: 0,
                    selectedText: '',

                    handleScroll() {
                        const el = this.$refs.messageList;
                        if (!el) return;
                        this.scrolledUp = (el.scrollHeight - el.scrollTop - el.clientHeight) > 150;

                        // Find which user message is closest to the middle of the scroll container
                        const userMsgs = this.messages.filter(m => m.role === 'user');
                        let closestId = null;
                        let minDiff = Infinity;
                        
                        const containerRect = el.getBoundingClientRect();
                        const centerY = containerRect.top + containerRect.height / 2;
                        
                        userMsgs.forEach(m => {
                            const msgEl = document.getElementById(`msg-${m.id}`);
                            if (msgEl) {
                                const rect = msgEl.getBoundingClientRect();
                                const msgCenterY = rect.top + rect.height / 2;
                                const diff = Math.abs(msgCenterY - centerY);
                                if (diff < minDiff) {
                                    minDiff = diff;
                                    closestId = m.id;
                                }
                            }
                        });
                        
                        if (closestId) {
                            this.activePromptId = closestId;
                        }
                    },
                    scrollToBottom() {
                        const el = this.$refs.messageList;
                        if (el) {
                            el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
                        }
                    },
                    scrollToPrompt(msgId) {
                        const el = document.getElementById(`msg-${msgId}`);
                        if (el) {
                            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            this.activePromptId = msgId;
                        }
                    },

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

                        // Setup selection change listener to dynamically show the quote reference popover
                        document.addEventListener('selectionchange', () => {
                            if (this.currentThreadId === null) {
                                this.showQuoteButton = false;
                                return;
                            }
                            const selection = window.getSelection();
                            const selected = selection.toString().trim();
                            if (!selected || selected.length < 3) {
                                this.showQuoteButton = false;
                                return;
                            }
                            
                            let node = selection.anchorNode;
                            let isInsideSociusProse = false;
                            while (node) {
                                if (node.classList && node.classList.contains('socius-prose')) {
                                    isInsideSociusProse = true;
                                    break;
                                }
                                node = node.parentNode;
                            }
                            
                            if (!isInsideSociusProse) {
                                this.showQuoteButton = false;
                                return;
                            }
                            
                            this.selectedText = selected;
                            
                            try {
                                const range = selection.getRangeAt(0);
                                const rect = range.getBoundingClientRect();
                                const wrapper = document.querySelector('section.flex-1.bg-\\[\\#252525\\]');
                                if (wrapper) {
                                    const wrapperRect = wrapper.getBoundingClientRect();
                                    this.quoteButtonX = rect.left - wrapperRect.left + (rect.width / 2) - 40;
                                    this.quoteButtonY = rect.top - wrapperRect.top - 40;
                                    this.showQuoteButton = true;
                                }
                            } catch (e) {
                                this.showQuoteButton = false;
                            }
                        });
                    },

                    quoteSelection() {
                        if (!this.selectedText) return;
                        const quote = `> "${this.selectedText}"\n\n`;
                        this.draft = quote + this.draft;
                        this.showQuoteButton = false;
                        window.getSelection().removeAllRanges();
                        const inputEl = document.getElementById('socius-prompt-input');
                        if (inputEl) inputEl.focus();
                    },

                    async loadThreads() {
                        this.loadingThreads = true;
                        this.error = null;
                        this.currentThreadId = null;
                        this.currentThread = null;
                        this.messages = [];

                        try {
                            const url = this.activeGroupId ? `${this.urls.list}?group_id=${this.activeGroupId}` : this.urls.list;
                            const response = await fetch(url, {
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
                            const url = this.activeGroupId ? `${this.urls.create}?group_id=${this.activeGroupId}` : this.urls.create;
                            const response = await fetch(url, {
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

                    copyMessage(content, messageId, btn = null) {
                        const element = document.getElementById(`socius-message-body-${messageId}`);
                        if (element) {
                            const clone = element.cloneNode(true);
                            
                            // Remove scripts, styles, helper textareas and control buttons
                            const controls = clone.querySelectorAll('.visual-header button, .socius-visual-loading, script, style, textarea.visual-source');
                            controls.forEach(el => el.remove());
                            
                            // Replace visual graphs/diagrams with inline data summary tables
                            const visuals = clone.querySelectorAll('.socius-visual');
                            visuals.forEach(visual => {
                                const type = visual.dataset.visualType || 'visual';
                                const visualId = visual.dataset.visualId || '';
                                const titleEl = visual.querySelector('.visual-header span');
                                const title = titleEl ? titleEl.innerText : type;

                                let replacement;

                                if ((type === 'chartjs' || type === 'chart.js') && visualId) {
                                    // Try to extract chart data from the Chart.js instance
                                    const canvasEl = document.querySelector(`#${visualId} canvas`);
                                    const chartInstance = canvasEl && typeof Chart !== 'undefined'
                                        ? Chart.getChart(canvasEl) : null;

                                    if (chartInstance && chartInstance.data) {
                                        const labels = chartInstance.data.labels || [];
                                        const dataset = chartInstance.data.datasets?.[0] || {};
                                        const values = dataset.data || [];

                                        // Build an HTML summary table
                                        const wrapper = document.createElement('div');
                                        wrapper.style.margin = '12px 0';

                                        const heading = document.createElement('p');
                                        heading.style.fontWeight = 'bold';
                                        heading.style.marginBottom = '6px';
                                        heading.style.fontSize = '13px';
                                        heading.innerText = `Chart: ${title}`;
                                        wrapper.appendChild(heading);

                                        const tbl = document.createElement('table');
                                        tbl.style.borderCollapse = 'collapse';
                                        tbl.style.width = '100%';
                                        tbl.style.fontFamily = 'Arial, sans-serif';
                                        tbl.style.fontSize = '12px';

                                        // Header row
                                        const thead = tbl.createTHead();
                                        const hRow = thead.insertRow();
                                        ['Option', 'Value'].forEach(h => {
                                            const th = document.createElement('th');
                                            th.innerText = h;
                                            th.style.border = '1px solid #d4d4d8';
                                            th.style.padding = '6px 10px';
                                            th.style.backgroundColor = '#f4f4f5';
                                            th.style.fontWeight = 'bold';
                                            th.style.textAlign = 'left';
                                            hRow.appendChild(th);
                                        });

                                        // Data rows
                                        const tbody = tbl.createTBody();
                                        labels.forEach((label, i) => {
                                            const row = tbody.insertRow();
                                            [label, values[i] ?? ''].forEach(val => {
                                                const td = row.insertCell();
                                                td.innerText = val;
                                                td.style.border = '1px solid #d4d4d8';
                                                td.style.padding = '6px 10px';
                                            });
                                        });

                                        wrapper.appendChild(tbl);
                                        replacement = wrapper;
                                    }
                                }

                                // Fallback: simple bold label
                                if (!replacement) {
                                    replacement = document.createElement('p');
                                    replacement.style.fontWeight = 'bold';
                                    replacement.style.color = '#3f3f46';
                                    replacement.style.fontStyle = 'italic';
                                    replacement.innerText = `[${title} — chart not available in this format]`;
                                }

                                visual.parentNode.replaceChild(replacement, visual);
                            });

                            // Style tables for clipboard pasting to Word/Google Docs
                            const tables = clone.querySelectorAll('table');
                            tables.forEach(table => {
                                table.style.width = '100%';
                                table.style.borderCollapse = 'collapse';
                                table.style.margin = '12px 0';
                                
                                table.querySelectorAll('th, td').forEach(cell => {
                                    cell.style.border = '1px solid #d4d4d8';
                                    cell.style.padding = '8px 12px';
                                    cell.style.textAlign = 'left';
                                });
                                table.querySelectorAll('th').forEach(th => {
                                    th.style.backgroundColor = '#f4f4f5';
                                    th.style.fontWeight = 'bold';
                                });
                            });

                            const rawHtml = clone.innerHTML;
                            const rawText = clone.innerText || clone.textContent;

                            const blobHtml = new Blob([rawHtml], { type: 'text/html' });
                            const blobText = new Blob([rawText], { type: 'text/plain' });
                            
                            navigator.clipboard.write([
                                new ClipboardItem({
                                    'text/html': blobHtml,
                                    'text/plain': blobText
                                })
                            ]).then(() => {
                                if (btn) {
                                    const original = btn.innerHTML;
                                    btn.innerHTML = '<i class="fa-solid fa-check text-green-400"></i>';
                                    setTimeout(() => { btn.innerHTML = original; }, 2000);
                                }
                            }).catch(err => {
                                console.error('Failed to copy message:', err);
                                navigator.clipboard.writeText(rawText);
                            });
                        } else {
                            navigator.clipboard.writeText(content);
                        }
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
                            this.$nextTick(() => {
                                const inputEl = document.getElementById('socius-prompt-input');
                                if (inputEl) inputEl.focus();
                            });
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
                        formData.append('review_mode_enabled', this.reviewModeEnabled ? '1' : '0');
                        this.pendingFiles.forEach(file => formData.append('attachments[]', file));

                        const usedFiles = [...this.pendingFiles];
                        this.draft = '';
                        this.pendingFiles = [];
                        this.reviewModeEnabled = false;

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
                            this.$nextTick(() => {
                                const inputEl = document.getElementById('socius-prompt-input');
                                if (inputEl) inputEl.focus();
                            });
                        }
                    },

                    async reloadThreadList() {
                        try {
                            const url = this.activeGroupId ? `${this.urls.list}?group_id=${this.activeGroupId}` : this.urls.list;
                            const response = await fetch(url, {
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
                        const currentTab = url.searchParams.get('reportTab') || 'quantitative';
                        if (currentTab !== 'analyse') {
                            return;
                        }
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
                                                <div class="flex gap-2 ml-auto">
                                                    <button onclick="window.sociusVisuals.copy('${id}', this)" class="text-[10px] font-bold text-slate-400 hover:text-white transition-colors">
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
                                                    ${isImage ? '<div class="animate-pulse flex flex-col items-center gap-3 p-8"><i class="fa-solid fa-wand-magic-sparkles text-[#3894dc] text-2xl"></i><span class="text-[10px] text-slate-500 font-bold">{{ __('Generating Image...') }}</span></div>' : ''}
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

                            const codeBlockMatch = line.match(/^`{3,}(.*)$/);
                            if (codeBlockMatch) {
                                if (inCodeBlock) {
                                    flushCodeBlock();
                                } else {
                                    flushParagraph();
                                    flushList();
                                    flushTable();
                                    inCodeBlock = true;
                                    codeBlockType = codeBlockMatch[1].trim().toLowerCase();
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
                                blocks.push(`<h4 class="text-base font-bold text-slate-100 mt-6 mb-3 tracking-tight">${this.inlineFormat(headingMatch[1])}</h4>`);
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
                                        <i class="fa-solid ${isImage ? 'fa-wand-magic-sparkles' : 'fa-chart-simple'} text-[#3894dc]/50 text-2xl mb-3"></i>
                                        <p class="text-[10px] text-slate-500 font-bold">{{ __('Socius is generating an image...') }}</p>
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

                        const tableId = `socius-table-${Math.random().toString(36).slice(2, 10)}`;

                        return `
                            <div class="my-4 rounded-2xl border border-white/10 overflow-hidden bg-white/[0.02]">
                                <div class="flex items-center justify-between gap-3 px-4 py-2.5 bg-white/[0.05] border-b border-white/10">
                                    <span class="text-[10px] font-semibold text-slate-400 tracking-normal">{{ __('Table') }}</span>
                                    <button type="button" onclick="window.copyRenderedSociusTable('${tableId}', this)" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/10 border border-white/10 text-[10px] font-semibold text-slate-300 hover:bg-[#2271b1] hover:text-white transition-all">
                                        <i class="fa-regular fa-copy text-[10px]"></i>
                                        {{ __('Copy Table') }}
                                    </button>
                                </div>
                                <div class="overflow-x-auto">
                                    <table id="${tableId}" class="min-w-full text-left text-sm border-separate border-spacing-0">
                                        <thead>
                                            <tr>
                                                ${header.map(cell => `<th class="px-4 py-3 text-[11px] font-bold text-blue-300 border-b border-white/10 bg-transparent">${this.inlineFormat(cell)}</th>`).join('')}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${body.map(row => `
                                                <tr>
                                                    ${row.map(cell => `<td class="px-4 py-3 border-b border-white/5 text-slate-100 bg-transparent">${this.inlineFormat(cell)}</td>`).join('')}
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
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
                                                                        // Make sure config structure matches percentages config
                                    if (config.data && Array.isArray(config.data.datasets) && config.data.datasets[0]) {
                                        const isMultiDataset = config.data.datasets.length > 1;

                                        if (!isMultiDataset) {
                                            const dataset = config.data.datasets[0];
                                            const rawData = Array.isArray(dataset.data) ? dataset.data : [];
                                            const totalResponses = rawData.reduce((sum, val) => sum + Number(val || 0), 0);
                                            const percentageData = rawData.map(val => 
                                                totalResponses > 0 ? parseFloat(((Number(val || 0) / totalResponses) * 100).toFixed(1)) : 0
                                            );

                                            // Apply percentage dataset values
                                            dataset.data = percentageData;
                                            if (!dataset.label) dataset.label = 'Responses (%)';
                                        }

                                        const chartType = config.type || 'bar';
                                        const isCartesian = ['bar', 'line'].includes(chartType);

                                        if (isCartesian) {
                                            // Only add datalabels plugin if single-dataset
                                            if (!isMultiDataset) {
                                                const datalabelsPlugin = {
                                                    id: 'customDatalabels',
                                                    afterDatasetsDraw(chart) {
                                                        const { ctx } = chart;
                                                        ctx.save();
                                                        chart.data.datasets.forEach((dt, i) => {
                                                            const meta = chart.getDatasetMeta(i);
                                                            meta.data.forEach((element, index) => {
                                                                const pctVal = dt.data[index] || 0;
                                                                const text = `${pctVal}%`;

                                                                ctx.fillStyle = (chart.options.scales?.x?.ticks?.color === '#333333' || chart.options.scales?.y?.ticks?.color === '#333333') ? '#333333' : '#e2e8f0';
                                                                ctx.font = 'bold 9px Inter, sans-serif';
                                                                
                                                                if (chart.options.indexAxis === 'y') {
                                                                    ctx.textAlign = 'left';
                                                                    ctx.textBaseline = 'middle';
                                                                    ctx.fillText(text, element.x + 6, element.y);
                                                                } else {
                                                                    ctx.textAlign = 'center';
                                                                    ctx.textBaseline = 'bottom';
                                                                    ctx.fillText(text, element.x, element.y - 6);
                                                                }
                                                            });
                                                        });
                                                        ctx.restore();
                                                    }
                                                };

                                                if (!config.plugins) config.plugins = [];
                                                config.plugins.push(datalabelsPlugin);
                                            }

                                            // Set scales
                                            if (!config.options) config.options = {};
                                            const isHorizontal = config.options.indexAxis === 'y';
                                            
                                            const valueAxisConfig = {
                                                beginAtZero: true,
                                                grace: '12%',
                                                grid: { color: 'rgba(255, 255, 255, 0.08)', drawBorder: false },
                                                ticks: { 
                                                    font: { weight: '600', size: 10, color: '#94a3b8' },
                                                    callback: function(value) {
                                                        return value + '%';
                                                    }
                                                },
                                                title: {
                                                    display: true,
                                                    text: (config.options?.scales?.[isHorizontal ? 'x' : 'y']?.title?.text) || 'Percentage of Responses (%)',
                                                    color: '#94a3b8',
                                                    font: { weight: '600', size: 10 }
                                                }
                                            };
                                            const labelAxisConfig = {
                                                grid: { display: false },
                                                ticks: { font: { weight: '600', size: 10, color: '#94a3b8' } },
                                                title: {
                                                    display: true,
                                                    text: formatShortCategoryTheme(config.short_theme || (config.options?.scales?.[isHorizontal ? 'y' : 'x']?.title?.text) || (config.options.plugins?.title?.text) || config.question_name) || 'Categories',
                                                    color: '#94a3b8',
                                                    font: { weight: '600', size: 10 }
                                                }
                                            };

                                            config.options.scales = {
                                                y: isHorizontal ? labelAxisConfig : valueAxisConfig,
                                                x: isHorizontal ? valueAxisConfig : labelAxisConfig
                                            };
                                        } else {
                                            // Make sure options has no scales for pie, doughnut, polarArea
                                            if (config.options) {
                                                delete config.options.scales;
                                            }
                                        }

                                        // Tooltip adjustments
                                        if (!config.options.plugins) config.options.plugins = {};
                                        config.options.plugins.tooltip = {
                                            backgroundColor: '#0f172a',
                                            padding: 12,
                                            titleFont: { size: 12, weight: '800' },
                                            bodyFont: { size: 12, weight: '600' },
                                            cornerRadius: 12,
                                            displayColors: true,
                                            callbacks: {
                                                label: function(context) {
                                                    const index = context.dataIndex;
                                                    const rawVal = rawData[index] || 0;
                                                    return ` ${rawVal} (${context.raw}%)`;
                                                }
                                            }
                                        };
                                    }

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
                async copy(id, btn = null) {
                    const target = document.querySelector(`#${id} .visual-target`);
                    if (!target) return;

                    const canvas = target.querySelector('canvas');
                    if (canvas && typeof Chart !== 'undefined') {
                        const chartInstance = Chart.getChart(canvas);
                        if (chartInstance) {
                            const originalScaleXColor = chartInstance.options.scales?.x?.ticks?.color;
                            const originalScaleYColor = chartInstance.options.scales?.y?.ticks?.color;
                            const originalLegendColor = chartInstance.options.plugins?.legend?.labels?.color;
                            const originalTitleColor = chartInstance.options.plugins?.title?.color;

                            if (chartInstance.options.scales?.x?.ticks) chartInstance.options.scales.x.ticks.color = '#333333';
                            if (chartInstance.options.scales?.y?.ticks) chartInstance.options.scales.y.ticks.color = '#333333';
                            if (chartInstance.options.plugins?.legend?.labels) chartInstance.options.plugins.legend.labels.color = '#333333';
                            if (chartInstance.options.plugins?.title) chartInstance.options.plugins.title.color = '#333333';
                            
                            chartInstance.update('none');

                            const tempCanvas = document.createElement('canvas');
                            tempCanvas.width = canvas.width;
                            tempCanvas.height = canvas.height;
                            const tempCtx = tempCanvas.getContext('2d');
                            tempCtx.fillStyle = '#ffffff';
                            tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
                            tempCtx.drawImage(canvas, 0, 0);

                            if (chartInstance.options.scales?.x?.ticks) chartInstance.options.scales.x.ticks.color = originalScaleXColor;
                            if (chartInstance.options.scales?.y?.ticks) chartInstance.options.scales.y.ticks.color = originalScaleYColor;
                            if (chartInstance.options.plugins?.legend?.labels) chartInstance.options.plugins.legend.labels.color = originalLegendColor;
                            if (chartInstance.options.plugins?.title) chartInstance.options.plugins.title.color = originalTitleColor;
                            
                            chartInstance.update('none');

                            tempCanvas.toBlob(async (blob) => {
                                if (!blob) return;
                                try {
                                    await navigator.clipboard.write([
                                        new ClipboardItem({ 'image/png': blob })
                                    ]);
                                    if (btn) {
                                        const original = btn.innerHTML;
                                        btn.innerHTML = '<i class="fa-solid fa-check mr-1 text-green-400"></i> Copied';
                                        setTimeout(() => { btn.innerHTML = original; }, 2000);
                                    }
                                } catch (err) {
                                    console.error('Copy chart failed:', err);
                                }
                            }, 'image/png');
                            return;
                        }
                    }

                    if (typeof htmlToImage === 'undefined') return;

                    const styleSheetsFilter = (sheet) => {
                        try {
                            const rules = sheet.cssRules;
                            return true;
                        } catch (e) {
                            return false;
                        }
                    };

                    const orgConsoleError = console.error;
                    console.error = function(...args) {
                        if (args[0] && typeof args[0] === 'string' && args[0].includes('cssRules')) {
                            return;
                        }
                        orgConsoleError.apply(console, args);
                    };

                    try {
                        const dataUrl = await htmlToImage.toPng(target, { 
                            backgroundColor: '#ffffff',
                            style: { padding: '20px', color: '#111111' },
                            styleSheetsFilter
                        });

                        const response = await fetch(dataUrl);
                        const blob = await response.blob();
                        await navigator.clipboard.write([
                            new ClipboardItem({ 'image/png': blob })
                        ]);

                        if (btn) {
                            const original = btn.innerHTML;
                            btn.innerHTML = '<i class="fa-solid fa-check mr-1 text-green-400"></i> Copied';
                            setTimeout(() => { btn.innerHTML = original; }, 2000);
                        }
                    } catch (e) {
                        console.error('Copy failed:', e);
                    } finally {
                        console.error = orgConsoleError;
                    }
                },
                async download(id, format) {
                    const target = document.querySelector(`#${id} .visual-target`);
                    if (!target) return;

                    const canvas = target.querySelector('canvas');
                    if (canvas && typeof Chart !== 'undefined') {
                        const chartInstance = Chart.getChart(canvas);
                        if (chartInstance) {
                            const originalScaleXColor = chartInstance.options.scales?.x?.ticks?.color;
                            const originalScaleYColor = chartInstance.options.scales?.y?.ticks?.color;
                            const originalLegendColor = chartInstance.options.plugins?.legend?.labels?.color;
                            const originalTitleColor = chartInstance.options.plugins?.title?.color;

                            if (chartInstance.options.scales?.x?.ticks) chartInstance.options.scales.x.ticks.color = '#333333';
                            if (chartInstance.options.scales?.y?.ticks) chartInstance.options.scales.y.ticks.color = '#333333';
                            if (chartInstance.options.plugins?.legend?.labels) chartInstance.options.plugins.legend.labels.color = '#333333';
                            if (chartInstance.options.plugins?.title) chartInstance.options.plugins.title.color = '#333333';
                            
                            chartInstance.update('none');

                            const tempCanvas = document.createElement('canvas');
                            tempCanvas.width = canvas.width;
                            tempCanvas.height = canvas.height;
                            const tempCtx = tempCanvas.getContext('2d');
                            tempCtx.fillStyle = '#ffffff';
                            tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
                            tempCtx.drawImage(canvas, 0, 0);

                            if (chartInstance.options.scales?.x?.ticks) chartInstance.options.scales.x.ticks.color = originalScaleXColor;
                            if (chartInstance.options.scales?.y?.ticks) chartInstance.options.scales.y.ticks.color = originalScaleYColor;
                            if (chartInstance.options.plugins?.legend?.labels) chartInstance.options.plugins.legend.labels.color = originalLegendColor;
                            if (chartInstance.options.plugins?.title) chartInstance.options.plugins.title.color = originalTitleColor;
                            
                            chartInstance.update('none');

                            const dataUrl = tempCanvas.toDataURL('image/png');
                            const link = document.createElement('a');
                            link.download = `socius-visual-${id}.png`;
                            link.href = dataUrl;
                            link.click();
                            return;
                        }
                    }

                    if (typeof htmlToImage === 'undefined') return;

                    const styleSheetsFilter = (sheet) => {
                        try {
                            const rules = sheet.cssRules;
                            return true;
                        } catch (e) {
                            return false;
                        }
                    };

                    const orgConsoleError = console.error;
                    console.error = function(...args) {
                        if (args[0] && typeof args[0] === 'string' && args[0].includes('cssRules')) {
                            return;
                        }
                        orgConsoleError.apply(console, args);
                    };

                    try {
                        const dataUrl = await htmlToImage.toPng(target, { 
                            backgroundColor: '#ffffff',
                            style: { padding: '20px', color: '#111111' },
                            styleSheetsFilter
                        });

                        const link = document.createElement('a');
                        link.download = `socius-visual-${id}.png`;
                        link.href = dataUrl;
                        link.click();
                    } catch (e) {
                        console.error('Download failed:', e);
                    } finally {
                        console.error = orgConsoleError;
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
