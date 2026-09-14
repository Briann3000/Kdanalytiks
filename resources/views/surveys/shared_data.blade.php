@extends('layouts.app')

@section('title', $survey->title . ' - ' . __('Public Data View'))
@section('meta_robots', 'noindex, nofollow')

@section('content')
    <div class="container-fluid px-4 sm:px-8 py-6 space-y-6 max-w-7xl mx-auto min-w-0 w-full"
        x-data="sharedDataManager(@js(request('tab', 'table')), @js($chartConfigs))"
        x-init="if (activeTab === 'reports') $nextTick(() => initCharts())">

        <!-- Header -->
        <header
            class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <span
                        class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <i class="fa-solid fa-share-nodes mr-1"></i> {{ __('Public Sharing (Read-Only)') }}
                    </span>
                    <span class="text-gray-300">•</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wider bg-gray-100 text-gray-600">
                        {{ __(ucfirst($survey->category->value ?? 'Survey')) }}
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wider bg-blue-50 text-[#2271b1]">
                        {{ $totalResponses }} {{ Str::plural(__('Submission'), $totalResponses) }}
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">{{ $survey->title }}</h1>
                @if($survey->description)
                    <p class="text-xs text-gray-500 font-medium max-w-3xl leading-relaxed">{{ $survey->description }}</p>
                @endif
            </div>

            <div class="flex items-center gap-2 flex-wrap self-start md:self-center">
                @guest
                    <a href="{{ route('login') }}"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-all">
                        {{ __('Sign In') }}
                    </a>
                    <a href="{{ route('register', ['role' => 'independent']) }}"
                        class="px-4 py-2 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-xl text-xs font-bold transition-all shadow-xs">
                        {{ __('Create Free Account') }}
                    </a>
                @endguest
            </div>
        </header>

        <!-- Navigation Sub-Tabs -->
        <div class="flex items-center gap-2 border-b border-gray-200 pb-2 overflow-x-auto whitespace-nowrap">
            <button type="button" @click="switchTab('table')"
                class="flex-shrink-0 px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-all flex items-center gap-2"
                :class="activeTab === 'table' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white hover:bg-gray-100 text-gray-600 border border-gray-200'">
                <i class="fa-solid fa-table-cells text-xs"></i>
                <span>{{ __('Table (Data)') }}</span>
                <span class="px-1.5 py-0.2 text-[9px] rounded-md font-bold"
                    :class="activeTab === 'table' ? 'bg-slate-700 text-white' : 'bg-gray-100 text-gray-600'">
                    {{ $responses->total() }}
                </span>
            </button>

            <button type="button" @click="switchTab('reports')"
                class="flex-shrink-0 px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-all flex items-center gap-2"
                :class="activeTab === 'reports' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white hover:bg-gray-100 text-gray-600 border border-gray-200'">
                <i class="fa-solid fa-chart-pie text-xs"></i>
                <span>{{ __('Reports (Charts & Analytics)') }}</span>
            </button>

            <button type="button" @click="switchTab('downloads')"
                class="flex-shrink-0 px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-all flex items-center gap-2"
                :class="activeTab === 'downloads' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white hover:bg-gray-100 text-gray-600 border border-gray-200'">
                <i class="fa-solid fa-cloud-arrow-down text-xs"></i>
                <span>{{ __('Downloads') }}</span>
            </button>
        </div>

        <!-- ========================================== -->
        <!-- TAB 1: TABLE (DATA) -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'table'" class="space-y-3 min-w-0 w-full max-w-full">
            <!-- Slim toolbar: search only, no card header -->
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <form method="GET" action="{{ route('surveys.shared_data', $token) }}"
                    class="flex items-center gap-1.5 w-full sm:w-auto">
                    <input type="hidden" name="tab" value="table">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="{{ __('Search responses...') }}"
                        class="w-full sm:w-auto text-xs rounded-xl border-gray-200 px-3.5 py-2 font-medium text-gray-700 bg-white shadow-2xs focus:border-[#2271b1] focus:ring-[#2271b1]">
                    <button type="submit"
                        class="flex-shrink-0 px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition-all shadow-2xs">
                        {{ __('Search') }}
                    </button>
                    @if(request('search'))
                        <a href="{{ route('surveys.shared_data', ['token' => $token, 'tab' => 'table']) }}"
                            class="flex-shrink-0 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-bold transition-all">
                            {{ __('Clear') }}
                        </a>
                    @endif
                </form>
            </div>
            <div
                class="bg-white rounded-3xl shadow-xs border border-gray-100 min-w-0 w-full max-w-full flex flex-col overflow-hidden"
                style="height: calc(100vh - 180px); max-height: calc(100vh - 180px);">
                @if($responses->count() > 0)
                    <div class="w-full flex-1 min-h-0 overflow-x-auto overflow-y-auto" style="height: 100%;">
                        <table class="w-max min-w-full divide-y divide-gray-100 border-separate border-spacing-0">
                                <thead class="sticky top-0 z-20 bg-gray-50 shadow-xs">
                                    <tr>
                                        <th scope="col"
                                            class="sticky top-0 bg-gray-50 px-4 py-3.5 text-left text-[9px] font-black text-gray-500 tracking-widest uppercase whitespace-nowrap border-b border-gray-200 z-20">
                                            # {{ __('ID') }}
                                        </th>
                                        <th scope="col"
                                            class="sticky top-0 bg-gray-50 px-6 py-3.5 text-left text-[10px] font-black text-gray-500 tracking-widest uppercase whitespace-nowrap border-b border-gray-200 z-20">
                                            {{ __('Submission Date') }}
                                        </th>
                                        <th scope="col"
                                            class="sticky top-0 bg-gray-50 px-6 py-3.5 text-left text-[10px] font-black text-gray-500 tracking-widest uppercase whitespace-nowrap border-b border-gray-200 z-20">
                                            {{ __('Respondent') }}
                                        </th>
                                        <th scope="col"
                                            class="sticky top-0 bg-gray-50 px-6 py-3.5 text-left text-[10px] font-black text-gray-500 tracking-widest uppercase whitespace-nowrap border-b border-gray-200 z-20">
                                            {{ __('Quality Score') }}
                                        </th>
                                        @foreach($headers as $header)
                                            <th scope="col"
                                                class="sticky top-0 bg-gray-50 px-6 py-3.5 text-left text-[10px] font-black text-gray-700 tracking-widest uppercase border-b border-gray-200 z-20 whitespace-nowrap"
                                                title="{{ $header['label'] }}">
                                                {{ $header['label'] }}
                                            </th>
                                        @endforeach
                                        <th scope="col"
                                            class="sticky top-0 bg-gray-50 px-6 py-3.5 text-left text-[10px] font-black text-gray-500 tracking-widest uppercase whitespace-nowrap border-b border-gray-200 z-20">
                                            {{ __('Sentiment') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 bg-white">
                                    @foreach($responses as $response)
                                        @php
                                            $schemaFields = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : $survey->json_schema;
                                            if (!is_array($schemaFields))
                                                $schemaFields = [];

                                            $transcriptions = $response->ai_metadata['transcriptions'] ?? [];
                                            $score = $response->quality_score ?? 100;
                                            $isFlagged = $response->is_flagged;

                                            if ($score >= 70 && !$isFlagged) {
                                                $badgeClass = 'bg-emerald-50 text-emerald-600 border-emerald-100';
                                                $badgeLabel = __('Clean');
                                            } elseif ($score >= 40 && !$isFlagged) {
                                                $badgeClass = 'bg-amber-50 text-amber-600 border-amber-100';
                                                $badgeLabel = __('Review');
                                            } else {
                                                $badgeClass = 'bg-rose-50 text-rose-600 border-rose-100';
                                                $badgeLabel = __('Flagged');
                                            }
                                        @endphp
                                        <tr class="hover:bg-gray-50/50 transition-colors {{ $isFlagged ? 'bg-red-50/10' : '' }}">
                                            <td class="px-4 py-4 whitespace-nowrap text-[10px] font-black text-gray-900">
                                                {{ $loop->iteration + ($responses->currentPage() - 1) * $responses->perPage() }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-[10px] text-gray-500 font-bold">
                                                {{ $response->created_at->format('M d, Y • H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-[10px] font-black text-gray-900 tracking-tight">
                                                    {{ $response->respondent ? $response->respondent->name : ($response->guest_name ?? __('Anonymous')) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[8px] font-black border {{ $badgeClass }}">
                                                    @if($isFlagged)
                                                        ⚠️ {{ $badgeLabel }} ({{ $score }})
                                                    @else
                                                        ✓ {{ $badgeLabel }} ({{ $score }})
                                                    @endif
                                                </span>
                                            </td>

                                            @foreach($headers as $header)
                                                @php
                                                    $val = '—';
                                                    if (!empty($survey->json_schema)) {
                                                        $jsonAnswer = $response->answers->first();
                                                        if ($jsonAnswer) {
                                                            $parsed = json_decode($jsonAnswer->value, true) ?? [];
                                                            foreach ($parsed as $item) {
                                                                if (isset($item['name']) && $item['name'] === $header['id']) {
                                                                    $val = $item['userData'] ?? '—';
                                                                    $field = collect($schemaFields)->firstWhere('name', $header['id']);
                                                                    if ($field && $val !== '—' && $val !== null && $val !== '') {
                                                                        if (in_array($field['type'], ['likert_matrix_grid', 'likert_matrix'])) {
                                                                            $matrixAnswers = is_string($val) ? json_decode($val, true) : $val;
                                                                            if (is_array($matrixAnswers)) {
                                                                                if (isset($matrixAnswers[0])) {
                                                                                    if (is_string($matrixAnswers[0])) {
                                                                                        $decoded = json_decode($matrixAnswers[0], true);
                                                                                        if (is_array($decoded))
                                                                                            $matrixAnswers = $decoded;
                                                                                    } elseif (is_array($matrixAnswers[0])) {
                                                                                        $matrixAnswers = $matrixAnswers[0];
                                                                                    }
                                                                                }
                                                                                $pairs = [];
                                                                                $rowsDef = $field['rows'] ?? [];
                                                                                $colsDef = $field['columns'] ?? [];
                                                                                foreach ($rowsDef as $r) {
                                                                                    $rk = $r['value'] ?? '';
                                                                                    $rowLabel = $r['label'] ?? $rk;
                                                                                    if (isset($matrixAnswers[$rk]) && $matrixAnswers[$rk] !== null && $matrixAnswers[$rk] !== '') {
                                                                                        $cv = $matrixAnswers[$rk];
                                                                                        $colLabel = collect($colsDef)->firstWhere('value', $cv)['label'] ?? $cv;
                                                                                        $pairs[] = "• $rowLabel: $colLabel";
                                                                                    } else {
                                                                                        $pairs[] = "• $rowLabel: —";
                                                                                    }
                                                                                }
                                                                                $val = implode("\n", $pairs);
                                                                            }
                                                                        } elseif (isset($field['values']) && is_array($field['values'])) {
                                                                            if (is_array($val)) {
                                                                                $mapped = [];
                                                                                foreach ($val as $v) {
                                                                                    $opt = collect($field['values'])->firstWhere('value', $v);
                                                                                    $mapped[] = $opt ? ($opt['label'] ?? $v) : $v;
                                                                                }
                                                                                $val = implode(', ', $mapped);
                                                                            } else {
                                                                                $opt = collect($field['values'])->firstWhere('value', $val);
                                                                                $val = $opt ? ($opt['label'] ?? $val) : $val;
                                                                            }
                                                                        }
                                                                    }
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                    } else {
                                                        $ans = $response->answers->where('question_id', $header['id'])->first();
                                                        $val = $ans ? $ans->value : '—';
                                                    }

                                                    if (is_array($val)) {
                                                        $val = implode(', ', array_map(function ($v) {
                                                            return is_array($v) ? json_encode($v) : (string) $v;
                                                        }, $val));
                                                    }

                                                    $valStr = is_string($val) ? trim($val) : (is_array($val) ? json_encode($val) : (string) $val);
                                                    $isMedia = is_string($valStr) && str_starts_with($valStr, 'uploads/') && preg_match('/\.(mp4|webm|ogg|ogv|mov|mp3|wav|m4a|aac)$/i', $valStr);
                                                @endphp

                                                <td
                                                    class="px-6 py-4 text-[10px] text-gray-600 font-medium whitespace-nowrap {{ $isMedia ? 'min-w-[280px] max-w-[320px]' : 'max-w-[250px] truncate' }}">
                                                    @if($isMedia)
                                                        @php
                                                            $mediaUrl = route('surveys.responses.media', [$survey, $response, 'path' => $valStr, 'token' => $token]);
                                                            $mediaDownloadUrl = route('surveys.responses.media', [$survey, $response, 'path' => $valStr, 'download' => 1, 'token' => $token]);
                                                            $transcriptionText = $transcriptions[$valStr] ?? null;
                                                        @endphp
                                                        <div x-data="{ showAudio: false, copied: false }" class="flex flex-col gap-2 py-1">
                                                            <div class="flex items-center flex-wrap gap-2">
                                                                <button type="button" @click="showAudio = !showAudio"
                                                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-[10px] font-bold transition-all shadow-2xs">
                                                                    <i class="fa-solid" :class="showAudio ? 'fa-stop' : 'fa-play'"></i>
                                                                    <span
                                                                        x-text="showAudio ? '{{ __('Hide Player') }}' : '{{ __('Listen') }}'"></span>
                                                                </button>

                                                                <a href="{{ $mediaDownloadUrl }}" download
                                                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg text-[10px] font-bold transition-all"
                                                                    title="{{ __('Download Audio') }}">
                                                                    <i class="fa-solid fa-download text-[9px]"></i>
                                                                    <span>{{ __('Download') }}</span>
                                                                </a>

                                                                <a href="{{ $mediaUrl }}" target="_blank"
                                                                    class="inline-flex items-center text-[10px] font-bold text-gray-400 hover:text-gray-600 transition-colors"
                                                                    title="{{ __('Open file in new tab') }}">
                                                                    <i class="fa-solid fa-external-link"></i>
                                                                </a>
                                                            </div>

                                                            <div x-show="showAudio" class="pt-1">
                                                                <audio controls class="w-56 h-8 rounded-lg shadow-inner bg-gray-100"
                                                                    preload="metadata">
                                                                    <source src="{{ $mediaUrl }}">
                                                                    {{ __('Your browser does not support audio playback.') }}
                                                                </audio>
                                                            </div>

                                                            @if($transcriptionText)
                                                                <div class="flex flex-col gap-1.5 mt-0.5">
                                                                    <div class="flex items-center justify-between gap-2">
                                                                        <span
                                                                            class="text-[9px] font-black text-indigo-600 uppercase tracking-wider">{{ __('Transcription') }}</span>
                                                                        <button type="button"
                                                                            @click="navigator.clipboard.writeText(@js($transcriptionText)); copied = true; setTimeout(() => copied = false, 2000)"
                                                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[8px] font-bold uppercase tracking-wider transition-all"
                                                                            :class="copied ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                                                                            <i class="fa-solid" :class="copied ? 'fa-check' : 'fa-copy'"></i>
                                                                            <span
                                                                                x-text="copied ? '{{ __('Copied!') }}' : '{{ __('Copy Text') }}'"></span>
                                                                        </button>
                                                                    </div>
                                                                    <div class="text-[9px] text-gray-700 italic bg-amber-50/50 border border-amber-100/80 p-2 rounded-xl leading-relaxed select-text whitespace-normal"
                                                                        title="{{ $transcriptionText }}">
                                                                        "{{ $transcriptionText }}"
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @elseif (str_contains($valStr, 'base64,'))
                                                        <a href="javascript:void(0)"
                                                            onclick="Swal.fire({title:'Signature', imageUrl:'{{ $valStr }}', imageAlt:'Signature', customClass: {image: 'rounded-xl border border-gray-100 shadow-lg'}})"
                                                            class="inline-flex items-center text-[#2271b1] hover:text-[#135e96] font-bold">
                                                            <i class="fa-solid fa-signature mr-1.5 text-zinc-500"></i>
                                                            <span>{{ __('View Signature') }}</span>
                                                        </a>
                                                    @elseif (preg_match('/^-?\d+\.\d+,-?\d+\.\d+$/', $valStr))
                                                        📍 {{ $valStr }}
                                                    @elseif (str_starts_with($valStr, '[') && json_decode($valStr) !== null)
                                                        @php $decoded = json_decode($valStr, true); @endphp
                                                        {{ count($decoded) . ' ' . __('entries') }}
                                                    @elseif (str_starts_with($valStr, '{') && json_decode($valStr) !== null)
                                                        @php
                                                            $decoded = json_decode($valStr, true);
                                                            $pairs = [];
                                                            foreach ($decoded as $k => $v) {
                                                                $pairs[] = (str_contains((string) $k, 'item-') ? '' : $k . ': ') . (is_array($v) ? json_encode($v) : $v);
                                                            }
                                                            echo htmlspecialchars(implode(', ', $pairs));
                                                        @endphp
                                                    @elseif ($valStr === 'true') ✅
                                                    @elseif ($valStr === 'false') ❌
                                                    @else
                                                        <span class="whitespace-pre-line">{{ $valStr }}</span>
                                                    @endif
                                                </td>
                                            @endforeach

                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @php
                                                    $sentiment = $response->ai_metadata['sentiment'] ?? 'Neutral';
                                                    $colors = [
                                                        'Positive' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                                        'Negative' => 'bg-rose-50 text-rose-600 border-rose-100',
                                                        'Neutral' => 'bg-slate-50 text-slate-500 border-slate-100',
                                                    ];
                                                    $cls = $colors[$sentiment] ?? $colors['Neutral'];
                                                @endphp
                                                <span class="px-2 py-0.5 rounded text-[8px] font-black border {{ $cls }}">
                                                    {{ __($sentiment) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                    </div>

                    @if($responses->hasPages())
                        <div class="px-6 py-3 border-t border-gray-100 bg-gray-50/30 shrink-0">
                            {{ $responses->links() }}
                        </div>
                    @endif
                @else
                    <div class="p-16 text-center">
                        <div
                            class="w-16 h-16 bg-gray-50 rounded-3xl flex items-center justify-center text-gray-300 mx-auto mb-4">
                            <i class="fa-solid fa-database text-2xl"></i>
                        </div>
                        <h3 class="text-xs font-black text-gray-600 tracking-widest uppercase mb-1">
                            {{ __('No Submissions Found') }}
                        </h3>
                        <p class="text-[11px] text-gray-400 font-medium">
                            {{ __('No responses match your query or the survey has not received responses yet.') }}
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 2: REPORTS (CHARTS & ANALYTICS) -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'reports'" class="space-y-8">
            @if(empty($analysis) || count($analysis) === 0)
                <div class="bg-white rounded-3xl p-16 text-center border border-gray-100 shadow-xs">
                    <i class="fa-solid fa-chart-pie text-3xl text-gray-300 mb-3"></i>
                    <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider">{{ __('No Analytics Available') }}
                    </h3>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ __('Reports and charts will automatically populate as responses are submitted.') }}</p>
                </div>
            @else
                @foreach($analysis as $item)
                    @php
                        $qIdSafe = preg_replace('/[^a-zA-Z0-9_]/', '_', $item['id']);
                        $isChartable = !empty($item['isChartable']);
                        $hasStats = !empty($item['stats']);
                        $hasLikertRows = !empty($item['likert_matrix_rows']);
                    @endphp
                    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-xs space-y-6">
                        <!-- Question Header -->
                        <div class="border-b border-gray-100 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <h4 class="text-base sm:text-lg font-black text-gray-900 leading-snug">
                                <span class="text-[#2271b1] mr-2 text-sm font-black">#{{ $loop->iteration }}</span>
                                {{ $item['label'] ?? ($item['question'] ?? ($item['title'] ?? $item['id'])) }}
                            </h4>
                            <span
                                class="px-3 py-1 bg-gray-100 text-gray-600 rounded-xl text-xs font-bold shrink-0 self-start sm:self-auto">
                                {{ $item['answered_count'] ?? $totalResponses }}
                                {{ Str::plural(__('Response'), $item['answered_count'] ?? $totalResponses) }}
                            </span>
                        </div>

                        <!-- Chart & Frequency Distribution Container -->
                        @if($isChartable)
                            <div class="grid grid-cols-1 {{ empty($item['isLikertLike']) ? 'lg:grid-cols-2' : '' }} gap-6 items-start">
                                <!-- Chart Canvas -->
                                @if(empty($item['isLikertLike']))
                                    <div
                                        class="h-64 sm:h-72 relative flex items-center justify-center bg-gray-50/50 rounded-2xl p-4 border border-gray-100 shadow-2xs">
                                        <canvas id="{{ $item['canvasId'] }}"></canvas>
                                    </div>
                                @endif

                                <!-- Frequency / Statistical Table -->
                                <div
                                    class="overflow-x-auto rounded-2xl border border-gray-200 shadow-2xs max-h-72 overflow-y-auto custom-scrollbar bg-white">
                                    @if($hasLikertRows)
                                        <table class="w-full text-left border-collapse">
                                            <thead class="sticky top-0 bg-gray-50/90 backdrop-blur-xs z-10 border-b border-gray-200">
                                                <tr class="text-[10px] sm:text-[11px] font-black text-gray-700 tracking-wider">
                                                    <th class="py-2.5 px-3 border-r border-gray-200">{{ __('Item / Statement') }}</th>
                                                    @if(isset($item['likert_matrix_rows'][0]['stats']))
                                                        @foreach($item['likert_matrix_rows'][0]['stats'] as $hCol)
                                                            @if(!isset($hCol['is_missing']) || !$hCol['is_missing'])
                                                                <th class="py-2.5 px-2 text-center border-r border-gray-200">{{ $hCol['value'] }}</th>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 text-xs">
                                                @foreach($item['likert_matrix_rows'] as $matrixRow)
                                                    <tr class="hover:bg-gray-50/30 transition-colors">
                                                        <td
                                                            class="py-2.5 px-3 text-[11px] font-semibold text-gray-800 border-r border-gray-100">
                                                            {{ $matrixRow['label'] }}
                                                        </td>
                                                        @foreach($matrixRow['stats'] as $stat)
                                                            @if(!isset($stat['is_missing']) || !$stat['is_missing'])
                                                                @php
                                                                    $totalFreqLikert = array_sum(array_column(array_filter($matrixRow['stats'], fn($s) => !isset($s['is_missing']) || !$s['is_missing']), 'count'));
                                                                    $percentLikert = $totalFreqLikert > 0 ? ($stat['count'] / $totalFreqLikert) * 100 : 0;
                                                                @endphp
                                                                <td
                                                                    class="py-2.5 px-2 text-center text-[11px] font-medium text-gray-900 border-r border-gray-100 whitespace-nowrap">
                                                                    {{ number_format($stat['count']) }} <span
                                                                        class="text-gray-400 text-[10px]">({{ number_format($percentLikert, 1) }}%)</span>
                                                                </td>
                                                            @endif
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @elseif($hasStats)
                                        <table class="w-full text-left border-collapse">
                                            <thead class="sticky top-0 bg-gray-50/90 backdrop-blur-xs z-10 border-b border-gray-200">
                                                <tr class="text-[10px] sm:text-[11px] font-black text-gray-700 tracking-wider">
                                                    <th class="py-2.5 px-3 border-r border-gray-200">{{ __('Value / Option') }}</th>
                                                    <th class="py-2.5 px-3 text-right border-r border-gray-200">{{ __('Frequency') }}</th>
                                                    <th class="py-2.5 px-3 text-right border-r border-gray-200">{{ __('Percent') }}</th>
                                                    <th class="py-2.5 px-3 text-right">{{ __('Valid %') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 text-xs">
                                                @php
                                                    $totalFreq = 0;
                                                    $validFreq = 0;
                                                    foreach ($item['stats'] as $s) {
                                                        if (!isset($s['is_missing']) || !$s['is_missing']) {
                                                            $validFreq += $s['count'];
                                                        }
                                                        $totalFreq += $s['count'];
                                                    }
                                                    if ($validFreq === 0)
                                                        $validFreq = $totalFreq;
                                                @endphp
                                                @foreach($item['stats'] as $stat)
                                                    @php
                                                        $isMissing = isset($stat['is_missing']) && $stat['is_missing'];
                                                        if ($isMissing && $stat['count'] == 0)
                                                            continue;
                                                        $pct = $totalFreq > 0 ? ($stat['count'] / $totalFreq) * 100 : 0;
                                                        $validPct = $isMissing ? null : ($validFreq > 0 ? ($stat['count'] / $validFreq) * 100 : 0);
                                                    @endphp
                                                    <tr class="hover:bg-gray-50/30 transition-colors">
                                                        <td class="py-2.5 px-3 text-[11px] font-medium text-gray-700 border-r border-gray-100">
                                                            {{ $stat['value'] }}
                                                        </td>
                                                        <td
                                                            class="py-2.5 px-3 text-right text-[11px] font-bold text-gray-900 border-r border-gray-100">
                                                            {{ number_format($stat['count']) }}
                                                        </td>
                                                        <td class="py-2.5 px-3 text-right text-[11px] text-gray-600 border-r border-gray-100">
                                                            {{ number_format($pct, 1) }}%
                                                        </td>
                                                        <td class="py-2.5 px-3 text-right text-[11px] text-gray-600">
                                                            {{ $validPct !== null ? number_format($validPct, 1) . '%' : '—' }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot
                                                class="bg-gray-50/60 border-t border-gray-200 font-bold text-[10px] sm:text-[11px] text-gray-900">
                                                <tr>
                                                    <td class="py-2 px-3 border-r border-gray-200">{{ __('Total') }}</td>
                                                    <td class="py-2 px-3 text-right border-r border-gray-200">
                                                        {{ number_format($totalFreq) }}</td>
                                                    <td class="py-2 px-3 text-right border-r border-gray-200">100.0%</td>
                                                    <td class="py-2 px-3 text-right">100.0%</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    @endif
                                </div>
                            </div>

                            <!-- AI Trend Interpretation Card (Read-Only Mode) -->
                            <x-ai-quant-insight-card :question-id="$item['id']" :survey-id="$survey->id" :stats="$item['stats'] ?? []"
                                :read-only="true" />
                        @endif
                    </div>
                @endforeach
            @endif
        </div>

        <!-- ========================================== -->
        <!-- TAB 3: DOWNLOADS -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'downloads'" class="space-y-6">
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-xs space-y-6">
                <div>
                    <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-file-export text-[#2271b1]"></i>
                        {{ __('Export & Download Center') }}
                    </h3>
                    <p class="text-xs text-gray-400 font-medium mt-0.5">
                        {{ __('Download raw dataset files, statistical bundles, and executive summary reports.') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Excel XLSX -->
                    <div
                        class="p-6 bg-emerald-50/50 border border-emerald-100 rounded-3xl flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <div
                                class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-xl shadow-xs">
                                <i class="fa-solid fa-file-excel"></i>
                            </div>
                            <h4 class="text-sm font-black text-gray-900">{{ __('Excel Workbook') }}</h4>
                            <p class="text-xs text-gray-500 font-medium">
                                {{ __('Complete structured dataset with headers and individual participant rows.') }}</p>
                        </div>
                        <a href="{{ route('surveys.shared_data.export', ['token' => $token, 'format' => 'xlsx']) }}"
                            class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider text-center transition-all shadow-xs flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-download"></i>
                            <span>{{ __('Download .XLSX') }}</span>
                        </a>
                    </div>

                    <!-- CSV -->
                    <div
                        class="p-6 bg-slate-50 border border-slate-200 rounded-3xl flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <div
                                class="w-12 h-12 rounded-2xl bg-slate-800 text-white flex items-center justify-center text-xl shadow-xs">
                                <i class="fa-solid fa-file-csv"></i>
                            </div>
                            <h4 class="text-sm font-black text-gray-900">{{ __('Comma-Separated (CSV)') }}</h4>
                            <p class="text-xs text-gray-500 font-medium">
                                {{ __('Standard plain-text table format for R, Python, and data pipelines.') }}</p>
                        </div>
                        <a href="{{ route('surveys.shared_data.export', ['token' => $token, 'format' => 'csv']) }}"
                            class="w-full py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold uppercase tracking-wider text-center transition-all shadow-xs flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-download"></i>
                            <span>{{ __('Download .CSV') }}</span>
                        </a>
                    </div>

                    <!-- Word DOCX -->
                    <div
                        class="p-6 bg-blue-50/50 border border-blue-100 rounded-3xl flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <div
                                class="w-12 h-12 rounded-2xl bg-[#2271b1] text-white flex items-center justify-center text-xl shadow-xs">
                                <i class="fa-solid fa-file-word"></i>
                            </div>
                            <h4 class="text-sm font-black text-gray-900">{{ __('Word Document (DOCX)') }}</h4>
                            <p class="text-xs text-gray-500 font-medium">
                                {{ __('Formatted analytical report summary ready for editing and document preparation.') }}
                            </p>
                        </div>
                        <a href="{{ route('surveys.shared_data.export', ['token' => $token, 'format' => 'docx']) }}"
                            class="w-full py-2.5 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-xl text-xs font-bold uppercase tracking-wider text-center transition-all shadow-xs flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-download"></i>
                            <span>{{ __('Download .DOCX') }}</span>
                        </a>
                    </div>

                    <!-- PDF -->
                    <div
                        class="p-6 bg-rose-50/50 border border-rose-100 rounded-3xl flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <div
                                class="w-12 h-12 rounded-2xl bg-rose-600 text-white flex items-center justify-center text-xl shadow-xs">
                                <i class="fa-solid fa-file-pdf"></i>
                            </div>
                            <h4 class="text-sm font-black text-gray-900">{{ __('PDF Summary Report') }}</h4>
                            <p class="text-xs text-gray-500 font-medium">
                                {{ __('Print-ready executive summary with charts, frequencies, and insights.') }}</p>
                        </div>
                        <a href="{{ route('surveys.shared_data.export', ['token' => $token, 'format' => 'pdf']) }}"
                            class="w-full py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider text-center transition-all shadow-xs flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-download"></i>
                            <span>{{ __('Download .PDF') }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Chart.js CDN for visual reports -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script>
        window.sharedDataManager = function (initialTab, chartConfigs) {
            return {
                activeTab: initialTab || 'table',
                chartsInitialized: false,
                configs: chartConfigs || [],
                switchTab(tab) {
                    this.activeTab = tab;
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', tab);
                    window.history.pushState({ tab: tab }, '', url);
                    if (tab === 'reports' && !this.chartsInitialized) {
                        this.$nextTick(() => {
                            this.initCharts();
                        });
                    }
                },
                initCharts() {
                    if (typeof Chart === 'undefined') return;
                    this.chartsInitialized = true;
                    const palettes = ['#4f46e5', '#3b82f6', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
                    this.configs.forEach(config => {
                        if (!config || !config.canvas_id) return;
                        const canvas = document.getElementById(config.canvas_id);
                        if (canvas) {
                            try {
                                const bgColors = config.labels.map((_, i) => palettes[i % palettes.length]);
                                new Chart(canvas, {
                                    type: 'bar',
                                    data: {
                                        labels: config.labels,
                                        datasets: [{
                                            label: config.short_theme || config.question_name || 'Responses',
                                            data: config.data,
                                            backgroundColor: bgColors,
                                            borderRadius: 6,
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: { display: false },
                                            tooltip: { enabled: true }
                                        },
                                        scales: {
                                            y: { beginAtZero: true, ticks: { precision: 0 } },
                                            x: { ticks: { maxRotation: 45, minRotation: 0 } }
                                        }
                                    }
                                });
                            } catch (e) {
                                console.error('Failed to render chart for ' + config.canvas_id, e);
                            }
                        }
                    });
                }
            };
        };

        // Global coordinator: Automatically pause other audio when one starts playing
        document.addEventListener('play', function (e) {
            if (e.target && e.target.tagName === 'AUDIO') {
                document.querySelectorAll('audio').forEach(function (otherAudio) {
                    if (otherAudio !== e.target && !otherAudio.paused) {
                        otherAudio.pause();
                    }
                });
            }
        }, true);
    </script>
@endsection