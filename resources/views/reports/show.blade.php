@extends('layouts.app')

@section('title', 'Question Analysis: ' . $survey->title)

@section('content')
<div class="px-4 sm:px-0 mb-8 max-w-6xl mx-auto">
    <div class="flex items-center justify-between">
        <div>
            <nav class="flex mb-2" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2 text-sm text-gray-500">
                    @php 
                        $userRoleVal = auth()->user()->role instanceof \UnitEnum ? auth()->user()->role->value : auth()->user()->role;
                    @endphp
                    <li><a href="{{ route($userRoleVal . '.reports.index') }}" class="hover:text-indigo-600">Reports</a></li>
                    <li><i class="fa-solid fa-chevron-right text-[10px]"></i></li>
                    <li class="font-medium text-gray-900">{{ $survey->title }}</li>
                </ol>
            </nav>
            <h2 class="text-3xl font-bold text-gray-900 tracking-tight">Report Overview</h2>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('surveys.export', $survey) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-bold text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                <i class="fa-solid fa-file-csv mr-2 text-green-600"></i> Export CSV
            </a>
            <a href="{{ route('surveys.export_pdf', $survey) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-bold text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                <i class="fa-solid fa-file-pdf mr-2 text-red-500"></i> Export PDF
            </a>
            <a href="{{ route('surveys.qualitative', $survey) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-bold text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                <i class="fa-solid fa-brain mr-2 text-indigo-600"></i> Qualitative Insights
            </a>
            <a href="{{ route('surveys.responses', $survey) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                <i class="fa-solid fa-users mr-2"></i> View Submissions
            </a>
        </div>
    </div>
</div>

<div class="max-w-6xl mx-auto">
    <!-- Header Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="bg-indigo-50 rounded-2xl p-6 text-center border border-indigo-100 shadow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-indigo-100 rounded-full blur-xl group-hover:bg-indigo-200 transition-colors"></div>
            <h3 class="text-4xl font-black text-indigo-700 mb-1 relative z-10">{{ count($responses) }}</h3>
            <p class="text-indigo-600 font-bold text-sm tracking-wide uppercase relative z-10">Total Responses</p>
        </div>
        <div class="bg-emerald-50 rounded-2xl p-6 text-center border border-emerald-100 shadow-sm relative overflow-hidden group">
            <div class="absolute -left-4 -bottom-4 w-20 h-20 bg-emerald-100 rounded-full blur-xl group-hover:bg-emerald-200 transition-colors"></div>
            <h3 class="text-4xl font-black text-emerald-700 mb-1 relative z-10">{{ count($analysis) }}</h3>
            <p class="text-emerald-600 font-bold text-sm tracking-wide uppercase relative z-10">Questions Analyzed</p>
        </div>
        <div class="bg-orange-50 rounded-2xl p-6 text-center border border-orange-100 shadow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-orange-100 rounded-full blur-xl group-hover:bg-orange-200 transition-colors"></div>
            <h3 class="text-4xl font-black text-orange-700 mb-1 relative z-10">{{ $survey->created_at->format('M j, Y') }}</h3>
            <p class="text-orange-600 font-bold text-sm tracking-wide uppercase relative z-10">Launch Date</p>
        </div>
    </div>

    <!-- AI Executive Summary Card (Restored Tailwind Style) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-10 overflow-hidden">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 shadow-sm">
                        <i class="fa-solid fa-sparkles text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 tracking-tight leading-none uppercase">AI Executive Summary</h3>
                        <p class="text-[10px] text-indigo-500 font-bold uppercase tracking-widest mt-1">Automatic Insight Analysis</p>
                    </div>
                </div>
                <span class="px-2.5 py-1 bg-indigo-50 text-indigo-600 text-[10px] font-black uppercase tracking-widest rounded-full border border-indigo-100">Groq Llama 3.1</span>
            </div>

            <div class="grid gap-3">
                @php
                    $rawLines = explode("\n", $aiSummary ?? '');
                    $validLines = array_filter($rawLines, function($line) {
                        return !empty(ltrim(trim($line), "-* \t\n\r\0\x0B"));
                    });
                @endphp

                @forelse($validLines as $line)
                    @php $trimmed = ltrim(trim($line), "-* \t\n\r\0\x0B"); @endphp
                    <div class="flex gap-4 items-start p-4 bg-indigo-50/30 rounded-xl border border-indigo-100/20 group hover:bg-indigo-50 transition-colors">
                        <div class="mt-1 flex-shrink-0">
                            <i class="fa-solid fa-circle-check text-indigo-400 text-sm group-hover:scale-110 transition-transform"></i>
                        </div>
                        <p class="text-gray-700 font-medium leading-relaxed text-sm">{{ $trimmed }}</p>
                    </div>
                @empty
                    <div class="py-12 text-center bg-gray-50 rounded-xl border border-dashed border-gray-200">
                        <i class="fa-solid fa-robot-slash text-gray-300 text-4xl mb-3"></i>
                        <p class="text-gray-500 font-medium max-w-xs mx-auto">
                            {{ $aiSummary ?: 'Intelligence engine is currently processing or unavailable.' }}
                        </p>
                    </div>
                @endforelse
            </div>

            <div class="mt-6 pt-5 border-t border-gray-100 flex items-center justify-between opacity-60">
                <div class="flex items-center text-gray-400 text-[10px] font-bold uppercase tracking-relaxed">
                    <i class="fa-solid fa-clock-rotate-left mr-2"></i> Real-time analysis of last 15 responses
                </div>
            </div>
        </div>
    </div>

    <!-- Question Analysis Feed -->
    <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
        <i class="fa-solid fa-chart-pie text-indigo-500 mr-3"></i> Question Analysis
    </h3>

    @if(empty($analysis))
        <div class="bg-white rounded-2xl p-16 text-center border-2 border-dashed border-gray-100 shadow-sm">
            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fa-solid fa-chart-bar text-3xl text-gray-300"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">No analytical data available</h3>
            <p class="text-gray-500 max-w-sm mx-auto">This survey has no identifiable questions or the format is not supported for automatic analysis.</p>
        </div>
    @else
        <div class="space-y-6 mb-16">
            @foreach($analysis as $item)
                <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
                    <div class="flex justify-between items-start mb-6 border-b border-gray-50 pb-4">
                        <h4 class="text-xl font-bold text-gray-900">{{ $item['label'] }}</h4>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-600 font-mono">
                            type: {{ $item['type'] }}
                        </span>
                    </div>

                    @if(empty($item['answers']))
                        <div class="py-10 text-center bg-gray-50 rounded-xl border border-dashed border-gray-200">
                            <i class="fa-solid fa-inbox text-gray-300 text-3xl mb-3"></i>
                            <p class="text-gray-500 font-medium">No answers recorded for this question yet.</p>
                        </div>
                    @else
                        @if($item['isChartable'])
                            <!-- Chart Container -->
                            <div class="relative h-64 w-full flex items-center justify-center bg-gray-50/50 rounded-xl p-4">
                                <canvas id="{{ $item['canvasId'] }}"></canvas>
                            </div>
                        @else
                            <!-- AI Qualitative Analysis Card -->
                            <x-ai-insight-card 
                                :question-id="$item['id']" 
                                :question-title="$item['label']" 
                                :survey-id="$item['survey_id']" 
                            />

                            <!-- Text Response Feed -->
                            <div class="bg-gray-50 rounded-xl border border-gray-100 p-2">
                                <div class="max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                                    <ul class="space-y-2">
                                        @foreach($item['answers'] as $answer)
                                            <li class="bg-white p-4 rounded-lg shadow-sm border border-gray-100 flex items-start text-gray-700 text-sm hover:border-indigo-200 transition-colors">
                                                <i class="fa-solid fa-comment-dots text-indigo-400 mt-1 mr-3 flex-shrink-0"></i> 
                                                <span class="leading-relaxed whitespace-pre-wrap">{{ $answer }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

@push('styles')
<style>
    /* Custom thin scrollbar for long string feeds */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f5f9; 
        border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1; 
        border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8; 
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chartConfigs = {!! json_encode($chartConfigs) !!};
        
        // Brand Colors matching Tailwind Indigo palette
        const brandColor = '#4f46e5'; 
        const brandColorLight = '#e0e7ff';

        chartConfigs.forEach(config => {
            const canvasElement = document.getElementById(config.canvas_id);
            if (!canvasElement) return;

            const ctx = canvasElement.getContext('2d');

            if (config.labels.length === 0) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['No responses yet'],
                        datasets: [{ 
                            label: 'Responses', 
                            data: [0], 
                            backgroundColor: '#e2e8f0',
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true, 
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { 
                            y: { beginAtZero: true, max: 1, ticks: { precision: 0 } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            } else {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: config.labels,
                        datasets: [{ 
                            label: 'Responses Frequency', 
                            data: config.data, 
                            backgroundColor: brandColor,
                            hoverBackgroundColor: '#4338ca',
                            borderRadius: 6,
                            borderSkipped: false
                        }]
                    },
                    options: {
                        responsive: true, 
                        maintainAspectRatio: false,
                        plugins: { 
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1e293b',
                                padding: 12,
                                titleFont: { size: 14, family: "'Inter', sans-serif" },
                                bodyFont: { size: 14, family: "'Inter', sans-serif" },
                                cornerRadius: 8
                            }
                        },
                        scales: { 
                            y: { 
                                beginAtZero: true, 
                                ticks: { precision: 0, font: {family: "'Inter', sans-serif"} },
                                grid: { color: '#f1f5f9' },
                                border: { display: false }
                            },
                            x: { 
                                grid: { display: false },
                                ticks: { font: {family: "'Inter', sans-serif", weight: 500} },
                                border: { display: false }
                            }
                        }
                    }
                });
            }
        });
    });
</script>
@endpush
@endsection
