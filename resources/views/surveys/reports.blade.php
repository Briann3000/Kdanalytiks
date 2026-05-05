@extends('surveys.hub')

@section('survey-content')
    <div class="space-y-12" x-data="{ reportTab: 'quantitative' }">
        <!-- Report Sub-Navigation -->
        <div class="flex items-center gap-2 bg-gray-100/50 p-1 rounded-xl w-fit mb-8">
            <button @click="reportTab = 'quantitative'"
                :class="reportTab === 'quantitative' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all">
                <i class="fa-solid fa-chart-column mr-2"></i> {{ __('Quantitative') }}
            </button>
            <button @click="reportTab = 'qualitative'"
                :class="reportTab === 'qualitative' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all">
                <i class="fa-solid fa-brain mr-2"></i> {{ __('AI Qualitative') }}
            </button>
            <div class="w-px h-4 bg-gray-200 mx-2"></div>
            <a href="{{ route('surveys.export_pdf', $survey) }}"
                class="px-4 py-2 text-red-600 hover:text-red-700 text-xs font-bold uppercase tracking-wider transition-all">
                <i class="fa-solid fa-file-pdf mr-2"></i> {{ __('PDF Report') }}
            </a>
        </div>

        <!-- AI Executive Summary (Always Visible at top of report) -->
        <div class="bg-indigo-600 rounded-3xl p-8 text-white shadow-xl shadow-indigo-100 relative overflow-hidden">
            <div class="relative z-10 flex flex-col md:flex-row gap-8 items-start">
                <div class="flex-shrink-0">
                    <div
                        class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/20">
                        <i class="fa-solid fa-robot text-2xl"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-xs font-bold uppercase tracking-widest mb-4 opacity-80">
                        {{ __('Strategic AI Synthesis') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @php
                            $rawLines = explode("\n", $aiSummary ?? '');
                            $validLines = array_filter($rawLines, function ($line) {
                                return !empty(ltrim(trim($line), "-* \t\n\r\0\x0B"));
                            });
                        @endphp
                        @forelse(array_slice($validLines, 0, 4) as $line)
                            <div
                                class="flex items-start gap-3 p-4 bg-white/10 rounded-2xl border border-white/10 backdrop-blur-sm">
                                <i class="fa-solid fa-bolt text-[10px] mt-1 text-indigo-300"></i>
                                <p class="text-[12px] font-medium leading-relaxed italic">
                                    {{ ltrim(trim($line), "-* \t\n\r\0\x0B") }}</p>
                            </div>
                        @empty
                            <div class="col-span-2 p-8 text-center bg-white/5 rounded-3xl border border-white/10">
                                <i class="fa-solid fa-hourglass-start text-3xl mb-4 text-indigo-200 opacity-50"></i>
                                <h4 class="text-sm font-black uppercase tracking-widest mb-2">
                                    {{ __('Insufficient Data for Synthesis') }}</h4>
                                <p class="text-[11px] text-indigo-100/70 font-medium leading-relaxed max-w-md mx-auto">
                                    {{ __('Our AI requires a diverse set of responses to generate high-quality strategic insights. Once more respondents complete the survey, we\'ll provide a comprehensive analysis of trends and sentiment.') }}
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            <i class="fa-solid fa-sparkles absolute right-[-40px] top-[-40px] text-white/5 text-[200px]"></i>
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

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                            <div
                                class="h-64 relative flex items-center justify-center bg-gray-50/30 rounded-2xl p-6 border border-gray-50">
                                <canvas id="{{ $item['canvasId'] }}"></canvas>
                            </div>

                            <div class="overflow-hidden">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="text-[10px] font-black text-gray-500 uppercase tracking-widest">
                                            <th class="py-2">{{ __('Value') }}</th>
                                            <th class="py-2 text-right">{{ __('Frequency') }}</th>
                                            <th class="py-2 text-right">{{ __('Ratio') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @foreach($item['stats'] as $stat)
                                            <tr>
                                                <td class="py-3 text-[11px] font-black text-gray-700 uppercase tracking-tight">
                                                    {{ $stat['value'] }}</td>
                                                <td class="py-3 text-right text-[11px] font-black text-gray-900">{{ $stat['count'] }}
                                                </td>
                                                <td class="py-3 text-right">
                                                    <span
                                                        class="text-[11px] font-black text-indigo-600">{{ $stat['percentage'] }}%</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
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

                        <x-ai-insight-card :question-id="$item['id']" :question-title="$item['label']"
                            :survey-id="$item['survey_id']" />

                        <div class="mt-12 bg-gray-50 rounded-2xl p-6 border border-gray-100">
                            <h5 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-6">
                                {{ __('Recent Verbatims') }}</h5>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @forelse(array_slice($item['answers'] ?? [], 0, 6) as $answer)
                                    <div
                                        class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm text-[12px] font-medium text-gray-700 italic border-l-4 border-l-indigo-500">
                                        "{{ $answer }}"
                                    </div>
                                @empty
                                    <p
                                        class="col-span-2 text-gray-400 italic text-xs text-center py-4 uppercase font-black tracking-widest">
                                        {{ __('No text data collected') }}</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
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
        </script>
    @endpush
@endsection