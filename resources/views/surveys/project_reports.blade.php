@extends('surveys.project_hub')

@section('project-content')
<div class="space-y-12" x-data="{ reportTab: 'quantitative' }">
    <!-- Report Sub-Navigation -->
    <div class="flex items-center gap-2 bg-gray-100/50 p-1 rounded-xl w-fit mb-8">
        <button @click="reportTab = 'quantitative'" 
                :class="reportTab === 'quantitative' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all">
            <i class="fa-solid fa-chart-column mr-2"></i> Quantitative
        </button>
        <button @click="reportTab = 'qualitative'" 
                :class="reportTab === 'qualitative' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all">
            <i class="fa-solid fa-brain mr-2"></i> AI Qualitative
        </button>
        <div class="w-px h-4 bg-gray-200 mx-2"></div>
        <a href="{{ route('surveys.export_pdf', $survey) }}" class="px-4 py-2 text-red-600 hover:text-red-700 text-xs font-bold uppercase tracking-wider transition-all">
            <i class="fa-solid fa-file-pdf mr-2"></i> PDF Report
        </a>
    </div>

    <!-- AI Executive Summary (Always Visible at top of report) -->
    <div class="bg-indigo-600 rounded-3xl p-8 text-white shadow-xl shadow-indigo-100 relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row gap-8 items-start">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/20">
                    <i class="fa-solid fa-robot text-2xl"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-xs font-bold uppercase tracking-widest mb-4 opacity-80">Strategic AI Synthesis</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @php
                        $rawLines = explode("\n", $aiSummary ?? '');
                        $validLines = array_filter($rawLines, function($line) {
                            return !empty(ltrim(trim($line), "-* \t\n\r\0\x0B"));
                        });
                    @endphp
                    @forelse(array_slice($validLines, 0, 4) as $line)
                        <div class="flex items-start gap-3 p-4 bg-white/10 rounded-2xl border border-white/10 backdrop-blur-sm">
                            <i class="fa-solid fa-bolt text-[10px] mt-1 text-indigo-300"></i>
                            <p class="text-[12px] font-medium leading-relaxed italic">{{ ltrim(trim($line), "-* \t\n\r\0\x0B") }}</p>
                        </div>
                    @empty
                        <div class="col-span-2 p-4 text-center text-indigo-200 italic font-bold text-xs uppercase tracking-widest">
                            AI is currently synthesizing your data...
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
        <i class="fa-solid fa-sparkles absolute right-[-40px] top-[-40px] text-white/5 text-[200px]"></i>
    </div>

    <!-- Quantitative Content -->
    <div x-show="reportTab === 'quantitative'" class="space-y-8 animate-in fade-in duration-500" x-data="chartManager()">
        @foreach($analysis as $item)
            @if($item['isChartable'])
                <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm">
                    <div class="mb-8 border-b border-gray-50 pb-6 flex justify-between items-end">
                        <h4 class="text-xl font-black text-gray-900 tracking-tight">
                            <span class="text-indigo-600 mr-2 opacity-30 text-base font-black">#{{ $loop->iteration }}</span> 
                            {{ $item['label'] }}
                        </h4>
                        <div class="flex gap-1 bg-gray-50 p-1 rounded-lg border border-gray-100">
                             @foreach(['bar', 'pie', 'doughnut'] as $type)
                                <button @click="switchChartType('{{ $item['canvasId'] }}', '{{ $type }}')" 
                                        :class="chartTypes['{{ $item['canvasId'] }}'] === '{{ $type }}' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-400 hover:text-gray-600'"
                                        class="p-1 px-2 rounded-md text-[10px] font-bold uppercase transition-all">
                                    {{ $type }}
                                </button>
                             @endforeach
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                        <div class="h-64 relative flex items-center justify-center bg-gray-50/30 rounded-2xl p-6 border border-gray-50">
                            <canvas id="{{ $item['canvasId'] }}"></canvas>
                        </div>
                        
                        <div class="overflow-hidden">
                             <table class="w-full text-left">
                                <thead>
                                    <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                        <th class="py-2">Value</th>
                                        <th class="py-2 text-right">Frequency</th>
                                        <th class="py-2 text-right">Ratio</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach($item['stats'] as $stat)
                                        <tr>
                                            <td class="py-3 text-[11px] font-black text-gray-700 uppercase tracking-tight">{{ $stat['value'] }}</td>
                                            <td class="py-3 text-right text-[11px] font-black text-gray-900">{{ $stat['count'] }}</td>
                                            <td class="py-3 text-right">
                                                <span class="text-[11px] font-black text-indigo-600">{{ $stat['percentage'] }}%</span>
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
                    
                    <x-ai-insight-card 
                        :question-id="$item['id']" 
                        :question-title="$item['label']" 
                        :survey-id="$item['survey_id']" 
                    />

                    <div class="mt-12 bg-gray-50 rounded-2xl p-6 border border-gray-100">
                        <h5 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-6">Recent Verbatims</h5>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @forelse(array_slice($item['answers'] ?? [], 0, 6) as $answer)
                                <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm text-[12px] font-medium text-gray-700 italic border-l-4 border-l-indigo-500">
                                    "{{ $answer }}"
                                </div>
                            @empty
                                <p class="col-span-2 text-gray-400 italic text-xs text-center py-4 uppercase font-black tracking-widest">No text data collected</p>
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
    const brandColor = '#4f46e5';

    function createChart(canvasId, config, type = 'bar') {
        const canvasElement = document.getElementById(canvasId);
        if (!canvasElement) return;
        const ctx = canvasElement.getContext('2d');
        
        const isMultipleColors = ['pie', 'doughnut'].includes(type);
        const colors = ['#4f46e5', '#818cf8', '#a5b4fc', '#c7d2fe', '#e0e7ff', '#6366f1'];

        const chartConfig = {
            type: type,
            data: {
                labels: config.labels,
                datasets: [{ 
                    data: config.data, 
                    backgroundColor: isMultipleColors ? colors : brandColor,
                    borderRadius: type === 'bar' ? 6 : 0,
                    barThickness: type === 'bar' ? 24 : null,
                }]
            },
            options: {
                responsive: true, 
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: isMultipleColors, position: 'bottom', labels: { font: { weight: '700', size: 9 } } }
                }
            }
        };

        if (type === 'bar') {
            chartConfig.options.scales = {
                y: { beginAtZero: true, grid: { display: false }, ticks: { font: { weight: '700', size: 9 } } },
                x: { grid: { display: false }, ticks: { font: { weight: '700', size: 9 } } }
            };
        }

        return new Chart(ctx, chartConfig);
    }

    window.chartManager = function() {
        return {
            chartTypes: {},
            init() {
                chartConfigs.forEach(config => {
                    this.chartTypes[config.canvas_id] = 'bar';
                    chartInstances[config.canvas_id] = createChart(config.canvas_id, config, 'bar');
                });
            },
            switchChartType(canvasId, type) {
                this.chartTypes[canvasId] = type;
                const config = chartConfigs.find(c => c.canvas_id === canvasId);
                if (chartInstances[canvasId]) chartInstances[canvasId].destroy();
                chartInstances[canvasId] = createChart(canvasId, config, type);
            }
        }
    };
</script>
@endpush
@endsection
