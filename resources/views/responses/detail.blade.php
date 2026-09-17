@extends('layouts.app')

@section('title', 'Response #' . $response->id . ' - ' . $survey->title)

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8" x-data="{ activeAudioId: null }">

        <!-- Header Navigation & Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-200">
            <div>
                @php 
                                                                                                                    $userRoleVal = auth()->user()->role instanceof \UnitEnum ? auth()->user()->role->value : auth()->user()->role;
                    $backRoute = $userRoleVal === 'admin' ? route('admin.surveys.index') : route('surveys.index');
                @endphp
                <nav class="flex items-center gap-2 text-xs font-bold text-gray-400 mb-1">
                    <a href="{{ $backRoute }}" class="hover:text-[#2271b1] transition-colors">{{ __('Surveys') }}</a>
                    <i class="fa-solid fa-chevron-right text-[9px]"></i>
                    <a href="{{ route('surveys.data', $survey) }}"
                        class="hover:text-[#2271b1] transition-colors">{{ $survey->title }}</a>
                    <i class="fa-solid fa-chevron-right text-[9px]"></i>
                    <span class="text-gray-700">#{{ $response->id }}</span>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight flex items-center gap-3">
                    <span>{{ __('Response Submission') }}</span>
                    <span
                        class="text-sm font-black px-3 py-1 bg-gray-100 text-gray-600 rounded-xl">#{{ $response->id }}</span>
                </h1>
            </div>

            <div class="flex items-center flex-wrap gap-2">
                <a href="{{ route('surveys.responses.export_pdf', [$survey, $response]) }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 rounded-xl text-xs font-bold transition-all shadow-2xs">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>{{ __('PDF') }}</span>
                </a>
                <a href="{{ route('surveys.responses.export_docx', [$survey, $response]) }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 rounded-xl text-xs font-bold transition-all shadow-2xs">
                    <i class="fa-solid fa-file-word"></i>
                    <span>{{ __('DOCX') }}</span>
                </a>
                <a href="{{ route('surveys.data', $survey) }}"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-white text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 rounded-xl text-xs font-bold transition-all shadow-2xs">
                    <i class="fa-solid fa-arrow-left text-[11px]"></i>
                    <span>{{ __('Back to Data') }}</span>
                </a>
            </div>
        </div>

        <!-- Submitter Info Profile Card -->
        @php
            $score = $response->quality_score ?? 100;
            $isFlagged = $response->is_flagged;
            $flags = $response->quality_flags ?? [];

            if ($score >= 70 && !$isFlagged) {
                $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                $badgeLabel = __('Clean');
            } elseif ($score >= 40 && !$isFlagged) {
                $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                $badgeLabel = __('Review Required');
            } else {
                $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                $badgeLabel = __('Flagged');
            }

            $sentiment = $response->ai_metadata['sentiment'] ?? 'Neutral';
            $sentimentColors = [
                'Positive' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'Negative' => 'bg-rose-50 text-rose-700 border-rose-200',
                'Neutral' => 'bg-slate-100 text-slate-700 border-slate-200',
            ];
            $sentClass = $sentimentColors[$sentiment] ?? $sentimentColors['Neutral'];
        @endphp

        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-start sm:items-center gap-4">
                    <div
                        class="w-12 h-12 rounded-2xl bg-indigo-50 text-[#2271b1] flex items-center justify-center font-black text-lg shrink-0 border border-indigo-100/80">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">
                            {{ $response->respondent ? $response->respondent->name : ($response->guest_name ?? __('Anonymous Submitter')) }}
                        </h2>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 text-xs text-gray-500 font-medium">
                            @if($response->respondent && $response->respondent->email)
                                <span class="flex items-center gap-1.5"><i class="fa-regular fa-envelope text-gray-400"></i>
                                    {{ $response->respondent->email }}</span>
                            @endif
                            @if($response->guest_phone || ($response->respondent && $response->respondent->phone_number))
                                <span class="flex items-center gap-1.5"><i class="fa-solid fa-phone text-gray-400"></i>
                                    {{ $response->respondent ? $response->respondent->phone_number : $response->guest_phone }}</span>
                            @endif
                            <span class="flex items-center gap-1.5"><i class="fa-regular fa-clock text-gray-400"></i>
                                {{ $response->created_at->format('M d, Y • H:i:s') }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 pt-4 md:pt-0 border-t md:border-t-0 border-gray-100">
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-black border {{ $badgeClass }}">
                        @if($isFlagged)
                            <i class="fa-solid fa-triangle-exclamation"></i> {{ $badgeLabel }} ({{ $score }}%)
                        @else
                            <i class="fa-solid fa-circle-check"></i> {{ $badgeLabel }} ({{ $score }}%)
                        @endif
                    </span>
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-black border {{ $sentClass }}">
                        <i class="fa-solid fa-brain text-[11px]"></i> {{ __($sentiment) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Answers Section Header -->
        <div class="flex items-center justify-between pt-2">
            <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider">{{ __('Questions & Responses') }}</h3>
        </div>

        <!-- Question Cards List -->
        <div class="space-y-5">
            @if(!empty($survey->json_schema))
                @php
                    $jsonAnswer = $response->answers->first();
                    $parsedData = $jsonAnswer ? json_decode($jsonAnswer->value, true) : [];
                    $schemaFields = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : $survey->json_schema;
                    if (!is_array($schemaFields))
                        $schemaFields = [];

                    $nonInputTypes = ['header', 'note', 'description', 'paragraph', 'group', 'hidden', 'divider', 'page_break', 'html'];
                    $schemaFields = array_values(array_filter($schemaFields, function ($field) use ($nonInputTypes) {
                        return isset($field['name']) && !in_array($field['type'] ?? '', $nonInputTypes);
                    }));

                    $isPremium = auth()->user()->hasProAccess();
                    $transcriptions = $response->ai_metadata['transcriptions'] ?? [];
                @endphp

                @if(count($schemaFields) > 0)
                    @foreach($schemaFields as $index => $field)
                        @php
                            $val = '—';
                            $label = $field['label'] ?? $field['name'];
                            $isLikert = in_array($field['type'] ?? '', ['likert_matrix_grid', 'likert_matrix']);
                            $matrixAnswers = [];

                            foreach ($parsedData as $data) {
                                if (isset($data['name']) && $data['name'] === $field['name']) {
                                    $val = $data['userData'] ?? '—';

                                    if ($val !== '—' && $val !== null && $val !== '') {
                                        if ($isLikert) {
                                            $decodedVal = is_string($val) ? json_decode($val, true) : $val;
                                            if (is_array($decodedVal)) {
                                                if (isset($decodedVal[0])) {
                                                    if (is_string($decodedVal[0])) {
                                                        $innerDecoded = json_decode($decodedVal[0], true);
                                                        if (is_array($innerDecoded)) $decodedVal = $innerDecoded;
                                                    } elseif (is_array($decodedVal[0])) {
                                                        $decodedVal = $decodedVal[0];
                                                    }
                                                }
                                                $matrixAnswers = $decodedVal;
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

                            if (is_array($val) && !$isLikert) {
                                if (count($val) === 1 && is_string($val[0]) && (str_starts_with($val[0], 'data:audio/') || str_starts_with($val[0], 'data:video/') || str_starts_with($val[0], 'uploads/'))) {
                                    $val = $val[0];
                                } else {
                                    $val = implode(', ', array_map(function ($v) {
                                        return is_array($v) ? json_encode($v) : (string) $v;
                                    }, $val));
                                }
                            }

                            $valStr = is_string($val) ? trim($val) : (is_array($val) ? json_encode($val) : (string) $val);
                            $isBase64Media = is_string($valStr) && (str_starts_with($valStr, 'data:audio/') || str_starts_with($valStr, 'data:video/'));
                            $isMedia = (is_string($valStr) && str_starts_with($valStr, 'uploads/') && preg_match('/\.(mp4|webm|ogg|ogv|mov|mp3|wav|m4a|aac)$/i', $valStr)) || $isBase64Media;
                            $questionIdUnique = 'q_' . $response->id . '_' . $field['name'];
                        @endphp

                        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-sm space-y-4">
                            <!-- Card Header: Number & Question Text -->
                            <div class="flex items-start justify-between gap-4">
                                <div class="space-y-1 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="px-2.5 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider bg-zinc-100 text-zinc-700">
                                            {{ __('Q') }}{{ $loop->iteration }}
                                        </span>
                                        @if($isMedia)
                                            <span
                                                class="px-2 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                <i class="fa-solid fa-microphone mr-1"></i> {{ __('Audio Response') }}
                                            </span>
                                        @endif
                                    </div>
                                    <h4 class="text-sm sm:text-base font-bold text-gray-900 leading-snug pt-1">
                                        {{ $label }}
                                    </h4>
                                </div>
                            </div>

                            <!-- Answer Body -->
                            <div class="pt-2 border-t border-gray-50">
                                @if($isMedia)
                                    @php
                                        $mediaUrl = $isBase64Media ? $valStr : route('surveys.responses.media', [$survey, $response, 'path' => $valStr]);
                                        $mediaDownloadUrl = $isBase64Media ? $valStr : route('surveys.responses.media', [$survey, $response, 'path' => $valStr, 'download' => 1]);
                                        $transcriptionText = $transcriptions[$valStr] ?? null;
                                    @endphp
                                    <div x-data="{ 
                                                                                                                                        transcribing: false, 
                                                                                                                                        transcription: @js($transcriptionText),
                                                                                                                                        error: null,
                                                                                                                                        copied: false,
                                                                                                                                        get isOpen() { return activeAudioId === '{{ $questionIdUnique }}'; },
                                                                                                                                        togglePlay() {
                                                                                                                                            const audio = this.$refs.audioPlayer;
                                                                                                                                            if (activeAudioId === '{{ $questionIdUnique }}') {
                                                                                                                                                if (audio) { audio.pause(); }
                                                                                                                                                activeAudioId = null;
                                                                                                                                            } else {
                                                                                                                                                activeAudioId = '{{ $questionIdUnique }}';
                                                                                                                                                this.$nextTick(() => {
                                                                                                                                                    if (audio) { audio.play().catch(() => {}); }
                                                                                                                                                });
                                                                                                                                            }
                                                                                                                                        },
                                                                                                                                        async transcribe() {
                                                                                                                                            @if(!$isPremium)
                                                                                                                                                Swal.fire({
                                                                                                                                                    title: '{{ __('Premium Feature') }}',
                                                                                                                                                    text: '{{ __('Transcription is only available for Pro and Enterprise plans.') }}',
                                                                                                                                                    icon: 'info',
                                                                                                                                                    showCancelButton: true,
                                                                                                                                                    confirmButtonText: '{{ __('Upgrade Now') }}',
                                                                                                                                                    confirmButtonColor: '#4f46e5'
                                                                                                                                                }).then((result) => {
                                                                                                                                                    if (result.isConfirmed) {
                                                                                                                                                        window.location.href = '{{ route('subscriptions.index') }}';
                                                                                                                                                    }
                                                                                                                                                });
                                                                                                                                                return;
                                                                                                                                            @endif

                                                                                                                                            this.transcribing = true;
                                                                                                                                            this.error = null;
                                                                                                                                            try {
                                                                                                                                                const response = await fetch('{{ route('surveys.responses.transcribe', [$survey, $response]) }}', {
                                                                                                                                                    method: 'POST',
                                                                                                                                                    headers: {
                                                                                                                                                        'Content-Type': 'application/json',
                                                                                                                                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                                                                                                                    },
                                                                                                                                                    body: JSON.stringify({ file_path: @js($valStr) })
                                                                                                                                                });
                                                                                                                                                const data = await response.json();
                                                                                                                                                if (data.success) {
                                                                                                                                                    this.transcription = data.transcription;
                                                                                                                                                } else {
                                                                                                                                                    this.error = data.message;
                                                                                                                                                }
                                                                                                                                            } catch (e) {
                                                                                                                                                this.error = '{{ __('Transcription failed.') }}';
                                                                                                                                            } finally {
                                                                                                                                                this.transcribing = false;
                                                                                                                                            }
                                                                                                                                        },
                                                                                                                                        copyTranscription() {
                                                                                                                                            if (!this.transcription) return;
                                                                                                                                            navigator.clipboard.writeText(this.transcription);
                                                                                                                                            this.copied = true;
                                                                                                                                            setTimeout(() => this.copied = false, 2000);
                                                                                                                                        }
                                                                                                                                    }"
                                        class="space-y-3">

                                        <!-- Media Toolbar -->
                                        <div class="flex items-center flex-wrap gap-2">
                                            <button type="button" @click="togglePlay()"
                                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shadow-2xs border"
                                                :class="isOpen ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border-indigo-100'">
                                                <i class="fa-solid" :class="isOpen ? 'fa-pause' : 'fa-play'"></i>
                                                <span x-text="isOpen ? '{{ __('Pause') }}' : '{{ __('Listen') }}'"></span>
                                            </button>

                                            <a href="{{ $mediaDownloadUrl }}" download
                                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-gray-50 text-gray-700 hover:bg-gray-100 rounded-xl text-xs font-bold transition-all border border-gray-200"
                                                title="{{ __('Download Audio') }}">
                                                <i class="fa-solid fa-download text-[10px]"></i>
                                                <span>{{ __('Download') }}</span>
                                            </a>

                                            <a href="{{ $mediaUrl }}" target="_blank"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white text-gray-500 hover:text-gray-700 rounded-xl text-xs font-bold transition-all border border-gray-200"
                                                title="{{ __('Open media in new tab') }}">
                                                <i class="fa-solid fa-external-link text-[10px]"></i>
                                                <span>{{ __('View Media') }}</span>
                                            </a>

                                            <template x-if="!transcription">
                                                <button @click="transcribe" :disabled="transcribing"
                                                    class="inline-flex items-center gap-1.5 text-emerald-700 hover:text-emerald-900 font-bold bg-emerald-50 px-3.5 py-1.5 rounded-xl border border-emerald-200 disabled:opacity-50 transition-all text-xs">
                                                    <i class="fa-solid"
                                                        :class="transcribing ? 'fa-circle-notch fa-spin' : 'fa-wand-magic-sparkles'"></i>
                                                    <span
                                                        x-text="transcribing ? '{{ __('Transcribing...') }}' : '{{ __('Transcribe') }}'"></span>
                                                </button>
                                            </template>
                                        </div>

                                        <!-- Inline Audio Player (Controlled by single audio coordinator) -->
                                        <div x-show="isOpen" class="pt-1">
                                            <audio x-ref="audioPlayer" controls
                                                class="w-full max-w-lg h-9 rounded-xl shadow-inner bg-gray-100" preload="metadata">
                                                <source src="{{ $mediaUrl }}">
                                                {{ __('Your browser does not support audio playback.') }}
                                            </audio>
                                        </div>

                                        <!-- Clean Structured Transcription Box -->
                                        <template x-if="transcription">
                                            <div
                                                class="mt-3 p-5 bg-slate-50/80 rounded-2xl border-l-4 border-indigo-500 border-y border-r border-slate-200/70 space-y-2.5">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center gap-2">
                                                        <span class="w-2 h-2 bg-indigo-500 rounded-full animate-pulse"></span>
                                                        <span
                                                            class="text-[10px] font-black text-indigo-900 uppercase tracking-wider">{{ __('Transcription') }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <button type="button" @click="copyTranscription"
                                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[9px] font-bold uppercase tracking-wider transition-all"
                                                            :class="copied ? 'bg-emerald-100 text-emerald-700' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-100 shadow-2xs'">
                                                            <i class="fa-solid" :class="copied ? 'fa-check' : 'fa-copy'"></i>
                                                            <span x-text="copied ? '{{ __('Copied!') }}' : '{{ __('Copy Text') }}'"></span>
                                                        </button>
                                                        <button @click="transcribe" :disabled="transcribing"
                                                            class="text-[9px] font-bold uppercase text-gray-400 hover:text-gray-700 transition-colors flex items-center gap-1">
                                                            <i class="fa-solid fa-rotate-right text-[8px]"
                                                                :class="transcribing ? 'fa-spin' : ''"></i>
                                                            <span
                                                                x-text="transcribing ? '{{ __('Working...') }}' : '{{ __('Re-transcribe') }}'"></span>
                                                        </button>
                                                    </div>
                                                </div>
                                                <p class="text-xs sm:text-sm text-gray-800 leading-relaxed italic select-text whitespace-pre-wrap"
                                                    x-text="transcription"></p>
                                            </div>
                                        </template>

                                        <template x-if="error">
                                            <p class="text-xs text-rose-600 font-bold" x-text="error"></p>
                                        </template>
                                    </div>
                                @elseif ($isLikert)
                                    @php
                                        $rowsDef = $field['rows'] ?? [];
                                        $colsDef = $field['columns'] ?? [];
                                    @endphp
                                    <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white shadow-2xs">
                                        <table class="w-full text-left border-collapse">
                                            <thead>
                                                <tr class="bg-gray-50/80 border-b border-gray-200 text-[11px] font-black text-gray-700 uppercase tracking-wider">
                                                    <th class="py-3 px-4 min-w-[240px]">{{ __('Statement / Item') }}</th>
                                                    @foreach($colsDef as $col)
                                                        @php
                                                            $cLabel = is_array($col) ? ($col['label'] ?? $col['value'] ?? '') : $col;
                                                        @endphp
                                                        <th class="py-3 px-3 text-center min-w-[110px] border-l border-gray-100 font-bold text-gray-600">{{ $cLabel }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 text-xs">
                                                @foreach($rowsDef as $r)
                                                    @php
                                                        $rKey = is_array($r) ? ($r['value'] ?? '') : $r;
                                                        $rLabel = is_array($r) ? ($r['label'] ?? $rKey) : $r;
                                                        $selectedVal = $matrixAnswers[$rKey] ?? null;
                                                    @endphp
                                                    <tr class="hover:bg-gray-50/40 transition-colors">
                                                        <td class="py-3 px-4 font-semibold text-gray-800 leading-snug">
                                                            {{ $rLabel }}
                                                        </td>
                                                        @foreach($colsDef as $col)
                                                            @php
                                                                $cVal = is_array($col) ? ($col['value'] ?? $col['label'] ?? '') : $col;
                                                                $cLbl = is_array($col) ? ($col['label'] ?? $col['value'] ?? '') : $col;
                                                                $isSelected = ($selectedVal !== null && $selectedVal !== '' && (strcasecmp((string)$selectedVal, (string)$cVal) === 0 || strcasecmp((string)$selectedVal, (string)$cLbl) === 0));
                                                            @endphp
                                                            <td class="py-3 px-3 text-center border-l border-gray-100 {{ $isSelected ? 'bg-blue-50/50' : '' }}">
                                                                @if($isSelected)
                                                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-[#2271b1] text-white shadow-xs font-bold text-xs" title="{{ __('Selected:') }} {{ $cLbl }}">
                                                                        <i class="fa-solid fa-check text-[10px]"></i>
                                                                    </span>
                                                                @else
                                                                    <span class="inline-block w-4 h-4 rounded-full border border-gray-300 bg-gray-50/50 opacity-40"></span>
                                                                @endif
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @elseif (str_contains($valStr, 'base64,') && !str_starts_with($valStr, 'data:audio/') && !str_starts_with($valStr, 'data:video/'))
                                    <a href="javascript:void(0)"
                                        onclick="Swal.fire({title:'Signature', imageUrl:'{{ $valStr }}', imageAlt:'Signature', customClass: {image: 'rounded-xl border border-gray-100 shadow-lg'}})"
                                        class="inline-flex items-center text-[#2271b1] hover:text-[#135e96] font-bold bg-zinc-50 px-3.5 py-2 rounded-xl border border-zinc-200 text-xs">
                                        <i class="fa-solid fa-signature mr-2"></i> {{ __('View Signature') }}
                                    </a>
                                @elseif (preg_match('/^-?\d+\.\d+,-?\d+\.\d+$/', $valStr))
                                    <div class="text-xs font-bold text-gray-700 flex items-center gap-1.5">
                                        <span>📍</span> <span>{{ $valStr }}</span>
                                    </div>
                                @elseif ($valStr === 'true' || $valStr === '1')
                                    <span
                                        class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-bold">✅
                                        {{ __('Yes / Checked') }}</span>
                                @elseif ($valStr === 'false' || $valStr === '0')
                                    <span class="px-2.5 py-1 bg-gray-50 text-gray-600 border border-gray-200 rounded-lg text-xs font-bold">❌
                                        {{ __('No / Unchecked') }}</span>
                                @else
                                    <div
                                        class="text-xs sm:text-sm font-semibold text-gray-800 leading-relaxed whitespace-pre-wrap bg-gray-50/50 p-3.5 rounded-2xl border border-gray-100">
                                        {{ $valStr }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="p-8 text-center text-gray-400 bg-white rounded-3xl border border-gray-100">
                        {{ __('No structured fields recorded.') }}
                    </div>
                @endif

            @else
                {{-- Legacy Questions --}}
                @php
                    $isPremium = auth()->user()->hasProAccess();
                    $transcriptions = $response->ai_metadata['transcriptions'] ?? [];
                @endphp
                @foreach($survey->questions()->orderBy('position')->get() as $question)
                    @php
                        $answer = $response->answers->where('question_id', $question->id)->first();
                        $valStr = $answer ? $answer->value : '—';
                        $isMedia = $answer && str_starts_with($valStr, 'uploads/') && preg_match('/\.(mp4|webm|ogg|ogv|mov|mp3|wav|m4a|aac)$/i', $valStr);
                        $questionIdUnique = 'q_leg_' . $response->id . '_' . $question->id;
                    @endphp
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-sm space-y-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1 flex-1">
                                <span
                                    class="px-2.5 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider bg-zinc-100 text-zinc-700">
                                    {{ __('Q') }}{{ $loop->iteration }}
                                </span>
                                <h4 class="text-sm sm:text-base font-bold text-gray-900 leading-snug pt-1">
                                    {{ $question->text }}
                                </h4>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-gray-50">
                            @if($isMedia)
                                @php
                                    $mediaUrl = route('surveys.responses.media', [$survey, $response, 'path' => $valStr]);
                                    $mediaDownloadUrl = route('surveys.responses.media', [$survey, $response, 'path' => $valStr, 'download' => 1]);
                                    $transcriptionText = $transcriptions[$valStr] ?? null;
                                @endphp
                                <div x-data="{ 
                                                                                                                transcribing: false, 
                                                                                                                transcription: @js($transcriptionText),
                                                                                                                error: null,
                                                                                                                copied: false,
                                                                                                                get isOpen() { return activeAudioId === '{{ $questionIdUnique }}'; },
                                                                                                                togglePlay() {
                                                                                                                    const audio = this.$refs.audioPlayer;
                                                                                                                    if (activeAudioId === '{{ $questionIdUnique }}') {
                                                                                                                        if (audio) { audio.pause(); }
                                                                                                                        activeAudioId = null;
                                                                                                                    } else {
                                                                                                                        activeAudioId = '{{ $questionIdUnique }}';
                                                                                                                        this.$nextTick(() => {
                                                                                                                            if (audio) { audio.play().catch(() => {}); }
                                                                                                                        });
                                                                                                                    }
                                                                                                                },
                                                                                                                async transcribe() {
                                                                                                                    @if(!$isPremium)
                                                                                                                        Swal.fire({
                                                                                                                            title: '{{ __('Premium Feature') }}',
                                                                                                                            text: '{{ __('Transcription is only available for Pro and Enterprise plans.') }}',
                                                                                                                            icon: 'info',
                                                                                                                            showCancelButton: true,
                                                                                                                            confirmButtonText: '{{ __('Upgrade Now') }}',
                                                                                                                            confirmButtonColor: '#4f46e5'
                                                                                                                        }).then((result) => {
                                                                                                                            if (result.isConfirmed) {
                                                                                                                                window.location.href = '{{ route('subscriptions.index') }}';
                                                                                                                            }
                                                                                                                        });
                                                                                                                        return;
                                                                                                                    @endif

                                                                                                                    this.transcribing = true;
                                                                                                                    this.error = null;
                                                                                                                    try {
                                                                                                                        const response = await fetch('{{ route('surveys.responses.transcribe', [$survey, $response]) }}', {
                                                                                                                            method: 'POST',
                                                                                                                            headers: {
                                                                                                                                'Content-Type': 'application/json',
                                                                                                                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                                                                                            },
                                                                                                                            body: JSON.stringify({ file_path: @js($valStr) })
                                                                                                                        });
                                                                                                                        const data = await response.json();
                                                                                                                        if (data.success) {
                                                                                                                            this.transcription = data.transcription;
                                                                                                                        } else {
                                                                                                                            this.error = data.message;
                                                                                                                        }
                                                                                                                    } catch (e) {
                                                                                                                        this.error = '{{ __('Transcription failed.') }}';
                                                                                                                    } finally {
                                                                                                                        this.transcribing = false;
                                                                                                                    }
                                                                                                                },
                                                                                                                copyTranscription() {
                                                                                                                    if (!this.transcription) return;
                                                                                                                    navigator.clipboard.writeText(this.transcription);
                                                                                                                    this.copied = true;
                                                                                                                    setTimeout(() => this.copied = false, 2000);
                                                                                                                }
                                                                                                            }" class="space-y-3">

                                    <div class="flex items-center flex-wrap gap-2">
                                        <button type="button" @click="togglePlay()"
                                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shadow-2xs border"
                                            :class="isOpen ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border-indigo-100'">
                                            <i class="fa-solid" :class="isOpen ? 'fa-pause' : 'fa-play'"></i>
                                            <span x-text="isOpen ? '{{ __('Pause') }}' : '{{ __('Listen') }}'"></span>
                                        </button>

                                        <a href="{{ $mediaDownloadUrl }}" download
                                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-gray-50 text-gray-700 hover:bg-gray-100 rounded-xl text-xs font-bold transition-all border border-gray-200"
                                            title="{{ __('Download Audio') }}">
                                            <i class="fa-solid fa-download text-[10px]"></i>
                                            <span>{{ __('Download') }}</span>
                                        </a>

                                        <a href="{{ $mediaUrl }}" target="_blank"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white text-gray-500 hover:text-gray-700 rounded-xl text-xs font-bold transition-all border border-gray-200">
                                            <i class="fa-solid fa-external-link text-[10px]"></i>
                                            <span>{{ __('View Media') }}</span>
                                        </a>

                                        <template x-if="!transcription">
                                            <button @click="transcribe" :disabled="transcribing"
                                                class="inline-flex items-center gap-1.5 text-emerald-700 hover:text-emerald-900 font-bold bg-emerald-50 px-3.5 py-1.5 rounded-xl border border-emerald-200 disabled:opacity-50 transition-all text-xs">
                                                <i class="fa-solid"
                                                    :class="transcribing ? 'fa-circle-notch fa-spin' : 'fa-wand-magic-sparkles'"></i>
                                                <span
                                                    x-text="transcribing ? '{{ __('Transcribing...') }}' : '{{ __('Transcribe') }}'"></span>
                                            </button>
                                        </template>
                                    </div>

                                    <div x-show="isOpen" class="pt-1">
                                        <audio x-ref="audioPlayer" controls
                                            class="w-full max-w-lg h-9 rounded-xl shadow-inner bg-gray-100" preload="metadata">
                                            <source src="{{ $mediaUrl }}">
                                            {{ __('Your browser does not support audio playback.') }}
                                        </audio>
                                    </div>

                                    <template x-if="transcription">
                                        <div
                                            class="mt-3 p-5 bg-slate-50/80 rounded-2xl border-l-4 border-indigo-500 border-y border-r border-slate-200/70 space-y-2.5">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2 h-2 bg-indigo-500 rounded-full animate-pulse"></span>
                                                    <span
                                                        class="text-[10px] font-black text-indigo-900 uppercase tracking-wider">{{ __('Transcription') }}</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <button type="button" @click="copyTranscription"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[9px] font-bold uppercase tracking-wider transition-all"
                                                        :class="copied ? 'bg-emerald-100 text-emerald-700' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-100 shadow-2xs'">
                                                        <i class="fa-solid" :class="copied ? 'fa-check' : 'fa-copy'"></i>
                                                        <span x-text="copied ? '{{ __('Copied!') }}' : '{{ __('Copy Text') }}'"></span>
                                                    </button>
                                                    <button @click="transcribe" :disabled="transcribing"
                                                        class="text-[9px] font-bold uppercase text-gray-400 hover:text-gray-700 transition-colors flex items-center gap-1">
                                                        <i class="fa-solid fa-rotate-right text-[8px]"
                                                            :class="transcribing ? 'fa-spin' : ''"></i>
                                                        <span
                                                            x-text="transcribing ? '{{ __('Working...') }}' : '{{ __('Re-transcribe') }}'"></span>
                                                    </button>
                                                </div>
                                            </div>
                                            <p class="text-xs sm:text-sm text-gray-800 leading-relaxed italic select-text whitespace-pre-wrap"
                                                x-text="transcription"></p>
                                        </div>
                                    </template>

                                    <template x-if="error">
                                        <p class="text-xs text-rose-600 font-bold" x-text="error"></p>
                                    </template>
                                </div>
                            @else
                                <div
                                    class="text-xs sm:text-sm font-semibold text-gray-800 leading-relaxed whitespace-pre-wrap bg-gray-50/50 p-3.5 rounded-2xl border border-gray-100">
                                    {{ $valStr }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <script>
        // Global coordinator: Automatically pause any other audio when one starts playing
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