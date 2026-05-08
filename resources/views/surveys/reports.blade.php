@extends('surveys.hub')

@section('survey-content')
    <div class="space-y-12" x-data="{ reportTab: 'quantitative' }">
        <!-- Sub Navigation -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
            <div class="flex items-center gap-2 bg-gray-100/50 p-1 rounded-2xl w-fit">
                <button @click="reportTab = 'quantitative'"
                    :class="reportTab === 'quantitative' ? 'bg-white text-indigo-700 shadow-sm border border-indigo-50' : 'text-gray-500 hover:text-gray-700'"
                    class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all">
                    <i class="fa-solid fa-chart-column mr-2"></i>
                    {{ __('Quantitative') }}
                </button>
                <button @click="reportTab = 'qualitative'"
                    :class="reportTab === 'qualitative' ? 'bg-white text-indigo-700 shadow-sm border border-indigo-50' : 'text-gray-500 hover:text-gray-700'"
                    class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all">
                    <i class="fa-solid fa-comments mr-2"></i>
                    {{ __('Qualitative') }}
                </button>
                <button @click="reportTab = 'crosstab'"
                    :class="reportTab === 'crosstab' ? 'bg-white text-indigo-700 shadow-sm border border-indigo-50' : 'text-gray-500 hover:text-gray-700'"
                    class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all">
                    <i class="fa-solid fa-table-cells mr-2"></i>
                    {{ __('Cross-Tabulation') }}
                    @if(!auth()->check() || !auth()->user()->hasActiveSubscription())
                        <i class="fa-solid fa-lock ml-2 text-[10px] opacity-50"></i>
                    @endif
                </button>
            </div>

            <div class="flex items-center gap-3">
                @if(!isset($isSharedView) || !$isSharedView)
                    <a href="{{ route('surveys.export_pdf', $survey) }}"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                        <i class="fa-solid fa-file-pdf text-sm"></i>
                        {{ __('PDF Report') }}
                    </a>
                    <a href="{{ route('surveys.export_docx', $survey) }}"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white border border-gray-200 text-gray-700 rounded-xl font-black text-[10px] uppercase tracking-widest hover:border-indigo-600 hover:text-indigo-600 transition-all shadow-sm">
                        <i class="fa-solid fa-file-word text-sm text-blue-600"></i>
                        {{ __('DOCX Report') }}
                    </a>
                @else
                    <div class="px-6 py-3 bg-indigo-50 text-indigo-700 rounded-xl font-black text-[10px] uppercase tracking-widest flex items-center gap-2">
                        <i class="fa-solid fa-lock"></i> {{ __('Read-Only Live View') }}
                    </div>
                @endif
            </div>
        </div>

        @if(!isset($isSharedView) || !$isSharedView)
            @if(auth()->user()->hasActiveSubscription())
                <div class="p-6 bg-gradient-to-r from-amber-50/50 to-orange-50/50 border border-amber-100 rounded-3xl flex flex-col lg:flex-row items-center justify-between gap-6 shadow-sm mb-10">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-100 flex items-center justify-center text-amber-600">
                            <i class="fa-solid fa-satellite-dish text-xl"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-amber-900 uppercase tracking-widest">{{ __('Live Result Dashboard') }}</h4>
                            <p class="text-[10px] text-amber-700/70 font-bold mt-0.5">{{ __('Share a real-time, read-only version of this report with stakeholders.') }}</p>
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                        @if($survey->share_report_token)
                            <div class="flex-1 lg:flex-none relative">
                                <input type="text" readonly value="{{ route('surveys.reports.shared', $survey->share_report_token) }}" id="share-report-link"
                                    class="w-full lg:w-80 bg-white border-amber-200 rounded-xl px-4 py-2.5 text-[11px] font-bold text-amber-900 outline-none focus:ring-2 focus:ring-amber-500/20">
                            </div>
                            <button onclick="copyToClipboard('share-report-link')" class="p-3 bg-amber-200 text-amber-800 rounded-xl hover:bg-amber-300 transition-colors" title="Copy Link">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                            <form action="{{ route('surveys.reports.toggle-shared', $survey) }}" method="POST" class="ml-auto lg:ml-0">
                                @csrf
                                <input type="hidden" name="disable" value="1">
                                <button type="submit" class="px-6 py-2.5 bg-red-50 text-red-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-red-100 transition-all border border-red-100">
                                    {{ __('Disable') }}
                                </button>
                            </form>
                        @else
                            <form action="{{ route('surveys.reports.toggle-shared', $survey) }}" method="POST" class="w-full lg:w-auto">
                                @csrf
                                <button type="submit" class="w-full lg:w-auto px-8 py-3 bg-amber-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-amber-700 transition-all shadow-lg shadow-amber-200">
                                    {{ __('Activate Live Dashboard') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                <script>
                    function copyToClipboard(id) {
                        const input = document.getElementById(id);
                        input.select();
                        input.setSelectionRange(0, 99999);
                        navigator.clipboard.writeText(input.value);
                        
                        const btn = event.currentTarget;
                        const originalHtml = btn.innerHTML;
                        btn.innerHTML = '<i class=\"fa-solid fa-check\"></i>';
                        btn.classList.replace('bg-amber-200', 'bg-green-500');
                        btn.classList.replace('text-amber-800', 'text-white');
                        
                        setTimeout(() => {
                            btn.innerHTML = originalHtml;
                            btn.classList.replace('bg-green-500', 'bg-amber-200');
                            btn.classList.replace('text-white', 'text-amber-800');
                        }, 2000);
                    }
                </script>
            @endif
        @endif

        @if(!isset($isSharedView) || !$isSharedView)
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
                        {{ __('Executive Thematic Analysis') }}</h3>
                    
                    @if(!$canAnalyze)
                        <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6">
                            <p class="text-lg font-bold mb-2">🚀 {{ __('Ready for deeper insights?') }}</p>
                            <p class="text-sm opacity-90 leading-relaxed mb-4">
                                {{ __('You have reached your free AI analysis limit. Upgrade to a Pro or Enterprise plan to unlock unlimited strategic summaries and advanced data interpretations.') }}
                            </p>
                            <a href="{{ route('subscriptions.index') }}" class="inline-flex items-center px-4 py-2 bg-white text-indigo-600 rounded-lg text-xs font-black uppercase tracking-widest hover:bg-indigo-50 transition-colors">
                                {{ __('View Plans') }} <i class="fa-solid fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    @else
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
                    @endif
                </div>
            </div>
            <i class="fa-solid fa-sparkles absolute right-[-40px] top-[-40px] text-white/5 text-[200px]"></i>
        </div>
        @endif

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
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="bg-gray-50/50 text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-gray-100">
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

                        <div class="mt-12 bg-gray-50 rounded-3xl overflow-hidden border border-gray-100">
                            <div class="px-8 py-6 border-b border-gray-100 bg-white flex justify-between items-center">
                                <h5 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    {{ __('Detailed Responses (Verbatims)') }}</h5>
                                <span class="text-[10px] font-black text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full uppercase">
                                    {{ count($item['answers'] ?? []) }} {{ __('Total Entries') }}
                                </span>
                            </div>
                            <div class="max-h-[500px] overflow-y-auto">
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
                </div>
            </template>
        </div>
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
        </script>
    @endpush
@endsection