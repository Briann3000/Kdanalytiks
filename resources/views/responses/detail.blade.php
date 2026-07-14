@extends('layouts.app')

@section('title', 'Response Details')

@section('content')
<div class="px-4 sm:px-0 mb-8 max-w-4xl mx-auto">
    <div class="flex items-center justify-between">
        <div>
            <nav class="flex mb-2" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2 text-sm text-gray-500">
                    @php 
                        $userRoleVal = auth()->user()->role instanceof \UnitEnum ? auth()->user()->role->value : auth()->user()->role;
                    @endphp
                    <li><a href="{{ $userRoleVal === 'admin' ? route('admin.surveys.index') : route('surveys.index') }}" class="hover:text-indigo-600">Surveys</a></li>
                    <li><i class="fa-solid fa-chevron-right text-[10px]"></i></li>
                    <li><a href="{{ route('surveys.responses', $survey) }}" class="hover:text-indigo-600">{{ $survey->title }}</a></li>
                    <li><i class="fa-solid fa-chevron-right text-[10px]"></i></li>
                    <li class="font-medium text-gray-900">Response #{{ $response->id }}</li>
                </ol>
            </nav>
            <h2 class="text-2xl font-bold text-gray-900">Response Details</h2>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('surveys.responses.export_pdf', [$survey, $response]) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fa-solid fa-file-pdf mr-2 text-red-500"></i> PDF
            </a>
            <a href="{{ route('surveys.responses.export_docx', [$survey, $response]) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fa-solid fa-file-word mr-2 text-blue-500"></i> DOCX
            </a>
            <a href="{{ route('surveys.responses', $survey) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Responses
            </a>
        </div>
    </div>
</div>

<div class="max-w-4xl mx-auto bg-white shadow sm:rounded-lg border border-gray-100 mb-10">
    <div class="px-4 py-5 sm:px-6 bg-gray-50 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Submitter Information
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
            Submitted on {{ $response->created_at->format('M d, Y \a\t H:i:s') }}
        </p>
    </div>
    <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
        <dl class="sm:divide-y sm:divide-gray-200">
            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Name</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                    {{ $response->respondent ? $response->respondent->name : ($response->guest_name ?? 'Anonymous') }}
                </dd>
            </div>
            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Email address</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                    {{ $response->respondent ? $response->respondent->email : 'N/A' }}
                </dd>
            </div>
            @if($response->guest_phone || ($response->respondent && $response->respondent->phone_number))
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Phone Number</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        {{ $response->respondent ? $response->respondent->phone_number : $response->guest_phone }}
                    </dd>
                </div>
            @endif
        </dl>
    </div>
</div>

<div class="max-w-4xl mx-auto bg-white shadow sm:rounded-lg border border-gray-100">
    <div class="px-4 py-5 sm:px-6 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Answers
            </h3>
        </div>
    </div>
    <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
        <dl class="sm:divide-y sm:divide-gray-200">
            @if(!empty($survey->json_schema))
                @php
                    $jsonAnswer = $response->answers->first();
                    $parsedData = $jsonAnswer ? json_decode($jsonAnswer->value, true) : [];
                    $schemaFields = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : $survey->json_schema;
                    if (!is_array($schemaFields)) $schemaFields = [];
                    
                    $schemaFields = array_filter($schemaFields, function($field) {
                        return isset($field['name']) && !in_array($field['type'], ['header', 'paragraph', 'group']);
                    });

                    $isPremium = auth()->user()->hasProAccess();
                    $transcriptions = $response->ai_metadata['transcriptions'] ?? [];
                @endphp
                
                @if(count($schemaFields) > 0)
                     @foreach($schemaFields as $field)
                        @php
                            $val = '—';
                            $label = $field['label'] ?? $field['name'];
                            
                            foreach ($parsedData as $data) {
                                if (isset($data['name']) && $data['name'] === $field['name']) {
                                    $val = $data['userData'] ?? '—';
                                    
                                    // Resolve options/Likert values to labels in detail view
                                    if ($val !== '—' && $val !== null && $val !== '') {
                                        if (in_array($field['type'], ['likert_matrix_grid', 'likert_matrix'])) {
                                             $matrixAnswers = is_string($val) ? json_decode($val, true) : $val;
                                             if (is_array($matrixAnswers)) {
                                                 if (isset($matrixAnswers[0])) {
                                                     if (is_string($matrixAnswers[0])) {
                                                         $decoded = json_decode($matrixAnswers[0], true);
                                                         if (is_array($decoded)) {
                                                             $matrixAnswers = $decoded;
                                                         }
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
                                                $val = $mapped;
                                            } else {
                                                $opt = collect($field['values'])->firstWhere('value', $val);
                                                $val = $opt ? ($opt['label'] ?? $val) : $val;
                                            }
                                        }
                                    }
                                    
                                    if (is_array($val)) {
                                        $val = implode(', ', $val);
                                    }
                                    break;
                                }
                            }
                            
                            $valStr = trim((string)$val);
                            $isMedia = str_starts_with($valStr, 'uploads/') && preg_match('/\.(mp4|webm|ogg|ogv|mov|mp3|wav|m4a|aac)$/i', $valStr);
                        @endphp
                        <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 hover:bg-gray-50">
                            <dt class="text-sm font-medium text-gray-700">
                                {{ $label }}
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 font-bold whitespace-pre-wrap">
                                @if($isMedia)
                                    <div x-data="{ 
                                        transcribing: false, 
                                        transcription: @js($transcriptions[$valStr] ?? null),
                                        error: null,
                                        async transcribe() {
                                            @if(!$isPremium)
                                                Swal.fire({
                                                    title: 'Premium Feature',
                                                    text: 'AI Transcription is only available for Pro and Enterprise plans.',
                                                    icon: 'info',
                                                    showCancelButton: true,
                                                    confirmButtonText: 'Upgrade Now',
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
                                                this.error = 'Transcription failed.';
                                            } finally {
                                                this.transcribing = false;
                                            }
                                        }
                                    }" class="space-y-3">
                                        <div class="flex items-center gap-4">
                                            <a href="{{ asset('storage/' . $valStr) }}" target="_blank" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-bold bg-indigo-50 px-3 py-1.5 rounded-lg border border-indigo-100">
                                                <i class="fa-solid fa-play-circle mr-2"></i> {{ __('View Media') }}
                                            </a>
                                            
                                            <template x-if="!transcription">
                                                <button @click="transcribe" :disabled="transcribing" class="inline-flex items-center text-emerald-600 hover:text-emerald-800 font-bold bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-100 disabled:opacity-50 transition-all">
                                                    <template x-if="!transcribing">
                                                        <span><i class="fa-solid fa-wand-magic-sparkles mr-2"></i> {{ __('Transcribe with AI') }} @if(!$isPremium) <i class="fa-solid fa-lock ml-1 text-[10px]"></i> @endif</span>
                                                    </template>
                                                    <template x-if="transcribing">
                                                        <span><i class="fa-solid fa-circle-notch fa-spin mr-2"></i> {{ __('Transcribing...') }}</span>
                                                    </template>
                                                </button>
                                            </template>
                                        </div>

                                        <template x-if="transcription">
                                            <div class="mt-4 p-4 bg-gray-50 rounded-xl border border-gray-100 relative group">
                                                <div class="flex items-center justify-between mb-2">
                                                    <div class="flex items-center gap-2">
                                                        <span class="w-1.5 h-4 bg-emerald-500 rounded-full"></span>
                                                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('AI Transcription') }}</span>
                                                    </div>
                                                    <button @click="transcribe" :disabled="transcribing" class="text-[9px] font-black uppercase text-indigo-500 hover:text-indigo-700 transition-colors flex items-center gap-1">
                                                        <i class="fa-solid fa-rotate mr-0.5" :class="transcribing ? 'fa-spin' : ''"></i> 
                                                        <span x-text="transcribing ? '{{ __('Regenerating...') }}' : '{{ __('Regenerate') }}'"></span>
                                                    </button>
                                                </div>
                                                <p class="text-sm text-gray-700 leading-relaxed italic" x-text="transcription"></p>
                                            </div>
                                        </template>

                                        <template x-if="error">
                                            <p class="text-xs text-rose-500 font-medium" x-text="error"></p>
                                        </template>
                                    </div>
                                @elseif (str_contains($valStr, 'base64,'))
                                    <a href="javascript:void(0)" onclick="Swal.fire({title:'Signature', imageUrl:'{{ $valStr }}', imageAlt:'Signature', customClass: {image: 'rounded-xl border border-gray-100 shadow-lg'}})" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-bold bg-indigo-50 px-3 py-1.5 rounded-lg border border-indigo-100">
                                        <i class="fa-solid fa-signature mr-2"></i> {{ __('View Signature') }}
                                    </a>
                                @elseif (preg_match('/^-?\d+\.\d+,-?\d+\.\d+$/', $valStr))
                                    📍 {{ $valStr }}
                                @elseif (str_starts_with($valStr, '[') && ($decoded = json_decode($valStr, true)) !== null)
                                    {{ empty($decoded) ? '—' : count($decoded) . ' ' . __('entries') }}
                                @elseif (str_starts_with($valStr, '{') && ($decoded = json_decode($valStr, true)) !== null)
                                    @php
                                        $pairs = [];
                                        foreach($decoded as $k => $v) {
                                            $pairs[] = (str_contains((string)$k, 'item-') ? '' : $k . ': ') . (is_array($v) ? json_encode($v) : $v);
                                        }
                                        echo implode(', ', $pairs);
                                    @endphp
                                @elseif ($valStr === 'true' || $valStr === '1') 
                                    ✅ 
                                @elseif ($valStr === 'false' || $valStr === '0') 
                                    ❌ 
                                @else
                                    {{ $valStr }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                @else
                    <div class="py-4 sm:py-5 sm:px-6">
                        <pre class="whitespace-pre-wrap text-sm text-gray-700 bg-gray-100 p-4 rounded">{{ $jsonAnswer ? $jsonAnswer->value : 'No data found' }}</pre>
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
                        $valStr = $answer ? $answer->value : '';
                        $isMedia = $answer && str_starts_with($valStr, 'uploads/') && preg_match('/\.(mp4|webm|ogg|ogv|mov|mp3|wav|m4a|aac)$/i', $valStr);
                    @endphp
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 hover:bg-gray-50">
                        <dt class="text-sm font-medium text-gray-700">
                            {{ $question->text }}
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 font-bold whitespace-pre-wrap">
                            @if($isMedia)
                                <div x-data="{ 
                                    transcribing: false, 
                                    transcription: @js($transcriptions[$valStr] ?? null),
                                    error: null,
                                    async transcribe() {
                                        @if(!$isPremium)
                                            Swal.fire({
                                                title: 'Premium Feature',
                                                text: 'AI Transcription is only available for Pro and Enterprise plans.',
                                                icon: 'info',
                                                showCancelButton: true,
                                                confirmButtonText: 'Upgrade Now',
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
                                            this.error = 'Transcription failed.';
                                        } finally {
                                            this.transcribing = false;
                                        }
                                    }
                                }" class="space-y-3">
                                    <div class="flex items-center gap-4">
                                        <a href="{{ asset('storage/' . $valStr) }}" target="_blank" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-bold bg-indigo-50 px-3 py-1.5 rounded-lg border border-indigo-100">
                                            <i class="fa-solid fa-play-circle mr-2"></i> {{ __('View Media') }}
                                        </a>
                                        
                                        <template x-if="!transcription">
                                            <button @click="transcribe" :disabled="transcribing" class="inline-flex items-center text-emerald-600 hover:text-emerald-800 font-bold bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-100 disabled:opacity-50 transition-all">
                                                <template x-if="!transcribing">
                                                    <span><i class="fa-solid fa-wand-magic-sparkles mr-2"></i> {{ __('Transcribe with AI') }} @if(!$isPremium) <i class="fa-solid fa-lock ml-1 text-[10px]"></i> @endif</span>
                                                </template>
                                                <template x-if="transcribing">
                                                    <span><i class="fa-solid fa-circle-notch fa-spin mr-2"></i> {{ __('Transcribing...') }}</span>
                                                </template>
                                            </button>
                                        </template>
                                    </div>

                                    <template x-if="transcription">
                                        <div class="mt-4 p-4 bg-gray-50 rounded-xl border border-gray-100 relative group">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="w-1.5 h-4 bg-emerald-500 rounded-full"></span>
                                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('AI Transcription') }}</span>
                                            </div>
                                            <p class="text-sm text-gray-700 leading-relaxed italic" x-text="transcription"></p>
                                        </div>
                                    </template>

                                    <template x-if="error">
                                        <p class="text-xs text-rose-500 font-medium" x-text="error"></p>
                                    </template>
                                </div>
                            @elseif($answer)
                                {{ $answer->value }}
                            @else
                                <span class="text-gray-400 italic">No answer provided</span>
                            @endif
                        </dd>
                    </div>
                @endforeach
            @endif
        </dl>
    </div>
</div>
@endsection
