@extends('layouts.app')

@section('title', 'Survey Report: ' . $survey->title)

@section('sub_sidebar')
    @include('reports.partials._report_sidebar')
@endsection

@section('content')
    <div class="w-full">
        <!-- Persistent Top Header -->
        <header class="sticky top-0 z-50 bg-gray-100 border-b border-gray-100 flex items-center justify-between px-10 py-5">
            <div>
                <h1 class="text-xl font-black text-gray-900 tracking-tight leading-none mb-1">{{ $survey->title }}</h1>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-[0.2em]">Analysis Workspace</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('surveys.export', $survey) }}"
                    class="flex items-center gap-2 px-4 py-2 bg-white text-gray-700 rounded-xl text-[10px] font-black uppercase tracking-widest border border-gray-200 hover:border-[#2271b1] hover:text-[#2271b1] transition-all shadow-sm">
                    <i class="fa-solid fa-file-csv"></i> CSV
                </a>
                <a href="{{ route('surveys.export_pdf', $survey) }}"
                    class="flex items-center gap-2 px-4 py-2 bg-white text-gray-700 rounded-xl text-[10px] font-black uppercase tracking-widest border border-gray-200 hover:border-red-600 hover:text-red-600 transition-all shadow-sm">
                    <i class="fa-solid fa-file-pdf"></i> PDF Report
                </a>
            </div>
        </header>

        <div class="p-10 space-y-12 max-w-7xl mx-auto">

            <!-- Dashboard/Overview Tab -->
            <div x-show="$store.workspace.activeTab === 'overview'" x-transition:enter="transition duration-200"
                class="space-y-8">
                <div class="flex justify-between items-start">
                    <h2 class="text-2xl font-black text-gray-900 tracking-tight">Executive Dashboard</h2>
                    <span
                        class="px-3 py-1 bg-zinc-100 text-[#2271b1] text-[10px] font-black uppercase tracking-widest rounded-lg border border-zinc-200">Live
                        View</span>
                </div>

                <!-- Header Summary Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-zinc-100 flex items-center justify-center text-[#2271b1]">
                            <i class="fa-solid fa-comment-dots text-xl"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-gray-900">{{ count($responses) }}</p>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-none">Total
                                Responses</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <i class="fa-solid fa-circle-question text-xl"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-gray-900">{{ count($analysis) }}</p>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-none">Analyzed
                                Fields</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-zinc-50 flex items-center justify-center text-red-700">
                            <i class="fa-solid fa-calendar-check text-xl"></i>
                        </div>
                        <div>
                            <p class="text-xl font-black text-gray-900">{{ $survey->created_at->format('M d, Y') }}</p>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-none">Start Date
                            </p>
                        </div>
                    </div>
                </div>

                <!-- AI Executive Summary Card -->
                <!-- AI Executive Summary Card -->
                <div class="bg-[#2271b1] rounded-3xl p-8 text-white shadow-xl shadow-zinc-200/50 relative overflow-hidden">
                    <div class="absolute right-0 top-0 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                        <i class="fa-solid fa-sparkles text-[200px]"></i>
                    </div>

                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center">
                                <i class="fa-solid fa-robot"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-black uppercase tracking-widest">AI Summary</h3>
                                <p class="text-xs text-zinc-400 font-bold opacity-80 uppercase tracking-tighter">Automatic
                                    Strategic Insights</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            @php
                                $rawLines = explode("\n", $aiSummary ?? '');
                                $validLines = array_filter($rawLines, function ($line) {
                                    return !empty(ltrim(trim($line), "-* \t\n\r\0\x0B"));
                                });
                            @endphp

                            @forelse($validLines as $line)
                                @php $trimmed = ltrim(trim($line), "-* \t\n\r\0\x0B"); @endphp
                                <div
                                    class="flex gap-4 items-start p-4 bg-white/10 rounded-2xl border border-white/10 backdrop-blur-sm">
                                    <i class="fa-solid fa-bolt text-xs mt-1.5 text-zinc-500"></i>
                                    <p class="text-sm font-medium leading-relaxed">{{ $trimmed }}</p>
                                </div>
                            @empty
                                <p class="text-zinc-400 italic font-medium">Processing survey intelligence...</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quantitative Tab -->
            <div x-show="$store.workspace.activeTab === 'quantitative'" x-data="chartManager()"
                x-transition:enter="transition duration-200" style="display: none;" class="space-y-12">
                <h2 class="text-2xl font-black text-gray-900 tracking-tight mb-8">Statistical Analysis</h2>
                @foreach($analysis as $item)
                    <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm" id="q-{{ $item['id'] }}">
                        <div class="flex justify-between items-start mb-6 border-b border-gray-50 pb-4">
                            <h4 class="text-xl font-black text-gray-900"><span
                                    class="text-[#2271b1] mr-2">#{{ $loop->iteration }}</span> {{ $item['label'] }}</h4>
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-black bg-gray-100 text-gray-500 uppercase tracking-widest">
                                {{ $item['type'] }}
                            </span>
                        </div>

                        @if(empty($item['answers']) && ($item['missing_count'] ?? 0) == ($totalResponses ?? 0))
                            <div class="py-16 text-center bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                                <i class="fa-solid fa-wind text-gray-300 text-4xl mb-4"></i>
                                <p class="text-gray-500 font-bold">No data points collected yet.</p>
                            </div>
                        @else
                            @if($item['isChartable'])
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                                    <!-- Chart Area -->
                                    <div class="flex flex-col gap-4">
                                        <!-- Chart Type Selector (Relocated to top) -->
                                        <div class="flex items-center justify-between bg-gray-50 p-2 rounded-xl border border-gray-100">
                                            <span
                                                class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-2">Visualization
                                                Type</span>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach(['bar' => 'Bar', 'pie' => 'Pie', 'doughnut' => 'Donut', 'line' => 'Line', 'polarArea' => 'Area', 'radar' => 'Radar'] as $type => $label)
                                                    <button @click="switchChartType('{{ $item['canvasId'] }}', '{{ $type }}')"
                                                        class="px-3 py-1 text-[10px] font-black rounded-lg transition-all uppercase tracking-tighter"
                                                        :class="chartTypes['{{ $item['canvasId'] }}'] === '{{ $type }}' ? 'bg-[#2271b1] text-white shadow-lg' : 'text-gray-500 hover:bg-white hover:shadow-sm'">
                                                        {{ $label }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>

                                        <!-- Canvas Container -->
                                        <div
                                            class="relative h-72 lg:h-80 w-full flex items-center justify-center bg-gray-50/30 rounded-2xl p-6 border border-gray-50">
                                            <canvas id="{{ $item['canvasId'] }}"></canvas>
                                        </div>
                                    </div>

                                    <!-- Frequency Table -->
                                    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
                                        <div class="p-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Frequency
                                                Distribution</span>
                                            <span
                                                class="text-[10px] font-bold text-zinc-2000 bg-zinc-100 px-2 py-0.5 rounded uppercase">n={{ $item['answered_count'] }}</span>
                                        </div>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-left text-sm">
                                                <thead>
                                                    <tr
                                                        class="bg-gray-50/50 text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                                        <th class="px-4 py-3">Value</th>
                                                        <th class="px-4 py-3 text-right">Freq</th>
                                                        <th class="px-4 py-3 text-right">%</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-50">
                                                    @foreach($item['stats'] as $stat)
                                                        <tr
                                                            class="{{ isset($stat['is_missing']) ? 'bg-red-50/20' : 'hover:bg-gray-50' }} transition-colors">
                                                            <td
                                                                class="px-4 py-3 font-medium {{ isset($stat['is_missing']) ? 'text-red-700 italic' : 'text-gray-700' }}">
                                                                {{ $stat['value'] }}
                                                            </td>
                                                            <td class="px-4 py-3 text-right font-black text-gray-900">{{ $stat['count'] }}
                                                            </td>
                                                            <td class="px-4 py-3 text-right">
                                                                <div class="flex items-center justify-end gap-2">
                                                                    <span class="font-bold text-[#2271b1]">{{ $stat['percentage'] }}%</span>
                                                                    <div class="w-12 bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                                                        <div class="bg-zinc-1000 h-full"
                                                                            style="width: {{ $stat['percentage'] }}%"></div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <!-- Hybrid Qualitative View (Integrated into Quantitative Tab) -->
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                                    <div
                                        class="bg-zinc-100 rounded-2xl p-8 border border-zinc-200 flex flex-col justify-center text-center">
                                        <i class="fa-solid fa-brain text-zinc-500 text-4xl mb-4"></i>
                                        <h5 class="text-lg font-black text-zinc-900 mb-2">Qualitative Data</h5>
                                        <p class="text-[#2271b1] text-[11px] font-bold uppercase tracking-wider mb-6">Open-ended
                                            responses feed</p>
                                        <button
                                            @click="$store.workspace.setTab('qualitative'); $store.workspace.scrollTo('ql-{{ $item['id'] }}')"
                                            class="mx-auto px-6 py-2 bg-[#2271b1] text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-[#135e96] transition-all shadow-lg shadow-zinc-200/50">
                                            View Deep Insights
                                        </button>
                                    </div>

                                    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
                                        <div
                                            class="p-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                            Latest Responses
                                        </div>
                                        <div class="max-h-64 overflow-y-auto custom-scrollbar p-1">
                                            @forelse(array_slice($item['answers'] ?? [], 0, 5) as $ans)
                                                <div
                                                    class="p-4 text-xs font-medium text-gray-600 border-b border-gray-50 last:border-0 hover:bg-gray-50 transition-colors">
                                                    "{{ str($ans)->limit(150) }}"
                                                </div>
                                            @empty
                                                <div class="p-8 text-center text-gray-400 italic text-xs">No responses yet.</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Qualitative Tab -->
            <div x-show="$store.workspace.activeTab === 'qualitative'" x-transition:enter="transition duration-200"
                style="display: none;" class="space-y-12">
                <h2 class="text-2xl font-black text-gray-900 tracking-tight mb-8">AI Qualitative Insights</h2>
                @foreach($analysis as $item)
                    @if(!$item['isChartable'])
                        <div class="bg-white rounded-3xl p-10 shadow-sm border border-gray-100" id="ql-{{ $item['id'] }}">
                            <div class="mb-8 border-b border-gray-50 pb-6">
                                <span class="text-[10px] font-black text-zinc-2000 uppercase tracking-widest block mb-2">Question
                                    Analysis</span>
                                <h4 class="text-2xl font-black text-gray-900 tracking-tight">
                                    <span class="text-[#2271b1] mr-2">Q{{ $loop->iteration }}.</span> {{ $item['label'] }}
                                </h4>
                            </div>

                            <x-ai-insight-card :question-id="$item['id']" :question-title="$item['label']"
                                :survey-id="$item['survey_id']" />

                            <!-- Response Feed -->
                            <div class="mt-12 bg-gray-50 rounded-2xl p-6 border border-gray-100">
                                <h5 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-6">Individual Responses
                                    Feed</h5>
                                <div class="max-h-[400px] overflow-y-auto pr-2 custom-scrollbar space-y-3">
                                    @forelse($item['answers'] as $answer)
                                        <div
                                            class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm text-gray-700 text-sm leading-relaxed relative group overflow-hidden">
                                            <div
                                                class="absolute left-0 top-0 w-1 h-full bg-zinc-100 group-hover:bg-zinc-1000 transition-colors">
                                            </div>
                                            {{ $answer }}
                                        </div>
                                    @empty
                                        <p class="text-gray-400 italic text-sm text-center py-4">No qualitative answers yet.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Tables Tab -->
            <div x-show="$store.workspace.activeTab === 'tables'" x-transition:enter="transition duration-200"
                style="display: none;" class="space-y-8">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-black text-gray-900 tracking-tight">Data Explorer</h2>
                    <div
                        class="text-[10px] font-black text-gray-400 uppercase tracking-widest bg-gray-50 px-3 py-1 rounded-lg border border-gray-100">
                        Excel/SPSS Format
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-xl overflow-hidden">
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse min-w-max">
                            <thead>
                                <tr class="bg-gray-900 text-white">
                                    <th
                                        class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] border-r border-gray-800 sticky left-0 bg-gray-900 z-10">
                                        ID</th>
                                    <th
                                        class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] border-r border-gray-800">
                                        Date Submitted</th>
                                    @foreach($analysis as $item)
                                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] border-r border-gray-800 last:border-r-0 min-w-[200px]"
                                            title="{{ $item['label'] }}">
                                            {{ str($item['label'])->limit(30) }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($responses as $response)
                                    <tr class="hover:bg-zinc-100 transition-colors group">
                                        <td
                                            class="px-6 py-4 font-black text-[#2271b1] text-sm border-r border-gray-50 sticky left-0 bg-white group-hover:bg-zinc-100 z-10 shadow-[2px_0_5px_rgba(0,0,0,0.02)]">
                                            #{{ $response->id }}
                                        </td>
                                        <td
                                            class="px-6 py-4 text-xs font-bold text-gray-500 border-r border-gray-50 uppercase tracking-tighter">
                                            {{ $response->created_at->format('Y-m-d H:i') }}
                                        </td>
                                        @php
                                            // Pre-parse the JSON answer for speed if it's a JSON survey
                                            $parsedResponse = [];
                                            if (!empty($survey->json_schema)) {
                                                $jsonAnswer = $response->answers->first();
                                                if ($jsonAnswer) {
                                                    $data = json_decode($jsonAnswer->value, true) ?? [];
                                                    foreach ($data as $field) {
                                                        if (isset($field['name']) && isset($field['userData'])) {
                                                            $val = $field['userData'];
                                                            $parsedResponse[$field['name']] = is_array($val) ? implode(', ', $val) : $val;
                                                        }
                                                    }
                                                }
                                            } else {
                                                // Legacy format
                                                foreach ($response->answers as $ans) {
                                                    $parsedResponse[$ans->question_id] = $ans->value;
                                                }
                                            }
                                        @endphp
                                        @foreach($analysis as $item)
                                            <td
                                                class="px-6 py-4 text-sm text-gray-700 border-r border-gray-50 last:border-r-0 italic font-medium">
                                                @php $val = $parsedResponse[$item['id']] ?? null; @endphp
                                                {{ $val ?: '—' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($analysis) + 2 }}"
                                            class="py-20 text-center text-gray-400 font-bold uppercase tracking-widest text-xs">
                                            No submissions recorded yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-center pt-4">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">Viewing latest
                        {{ count($responses) }} responsive blocks</p>
                </div>
            </div>

            <!-- Gallery Tab -->
            <div x-show="$store.workspace.activeTab === 'gallery'" x-transition:enter="transition duration-200"
                style="display: none;">
                <div class="h-full flex flex-col items-center justify-center py-24 text-center">
                    <div
                        class="w-32 h-32 bg-emerald-50 rounded-[3rem] flex items-center justify-center mb-10 transform rotate-3">
                        <i class="fa-solid fa-film text-5xl text-emerald-200"></i>
                    </div>
                    <h3 class="text-3xl font-black text-gray-900 mb-4 tracking-tighter">Media Repository</h3>
                    <p class="text-lg text-gray-500 font-medium max-w-sm mx-auto leading-relaxed">
                        Aggregate and view all uploaded photoblocks, voice recordings, and videos in this gallery workspace
                        (Phase 4).
                    </p>
                </div>
            </div>

        </div>
    </div>

    @push('styles')
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
    @endpush

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

                const isMultipleColors = ['pie', 'doughnut', 'polarArea', 'bar'].includes(type);
                const colors = [
                    '#4f46e5', '#818cf8', '#a5b4fc', '#c7d2fe', '#e0e7ff',
                    '#6366f1', '#4338ca', '#3730a3', '#312e81', '#1e1b4b',
                    '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9'
                ];

                const chartConfig = {
                    type: type,
                    data: {
                        labels: config.labels,
                        datasets: [{
                            label: 'Frequency',
                            data: config.data,
                            backgroundColor: isMultipleColors ? colors.slice(0, config.data.length) : brandColor,
                            borderColor: type === 'line' || type === 'radar' ? brandColor : 'transparent',
                            borderWidth: type === 'line' || type === 'radar' ? 2 : 0,
                            pointBackgroundColor: brandColor,
                            borderRadius: type === 'bar' ? 8 : 0,
                            barThickness: type === 'bar' ? 32 : null,
                            fill: type === 'line' ? true : false,
                            backgroundColor: type === 'line' ? 'rgba(79, 70, 229, 0.1)' : (isMultipleColors ? colors.slice(0, config.data.length) : brandColor),
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: ['pie', 'doughnut', 'polarArea', 'radar'].includes(type),
                                position: 'bottom',
                                labels: {
                                    boxWidth: 10, padding: 15,
                                    font: { family: "'Inter', sans-serif", size: 10, weight: '700' }
                                }
                            },
                            tooltip: {
                                backgroundColor: '#111827',
                                padding: 12,
                                titleFont: { family: "'Inter', sans-serif", weight: 'bold' },
                                bodyFont: { family: "'Inter', sans-serif" },
                                cornerRadius: 12
                            }
                        }
                    }
                };

                // Custom scales based on type
                if (['bar', 'line'].includes(type)) {
                    chartConfig.options.scales = {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, font: { weight: '600', size: 10 } },
                            grid: { color: '#f1f5f9' },
                            border: { display: false }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { weight: '600', size: 10 } },
                            border: { display: false }
                        }
                    };
                } else if (type === 'radar' || type === 'polarArea') {
                    chartConfig.options.scales = {
                        r: {
                            grid: { color: '#f1f5f9' },
                            ticks: { display: false },
                            pointLabels: { font: { weight: '700', size: 9 } }
                        }
                    };
                }

                return new Chart(ctx, chartConfig);
            }

            window.chartManager = function () {
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

                        if (chartInstances[canvasId]) {
                            chartInstances[canvasId].destroy();
                        }

                        chartInstances[canvasId] = createChart(canvasId, config, type);
                    }
                }
            };
        </script>
    @endpush
@endsection