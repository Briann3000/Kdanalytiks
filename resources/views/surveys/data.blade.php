@extends('surveys.hub')

@section('survey-content')
    <div class="space-y-6" x-data="{ selectedResponses: [] }">
        <!-- Message Alerts -->
        @if(session('success'))
            <div class="p-4 bg-green-50 border border-green-100 rounded-2xl">
                <p class="text-xs text-green-700 font-bold uppercase tracking-widest">
                    <i class="fa-solid fa-circle-check mr-2"></i> {{ session('success') }}
                </p>
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 bg-red-50 border border-red-100 rounded-2xl">
                <p class="text-xs text-red-700 font-bold uppercase tracking-widest">
                    <i class="fa-solid fa-circle-exclamation mr-2"></i> {{ session('error') }}
                </p>
            </div>
        @endif

        <!-- Bulk Action Form Container -->
        <form action="{{ route('surveys.responses.bulk-quality-override', $survey) }}" method="POST">
            @csrf

            <!-- Bulk Action Floating Bar -->
            <div x-show="selectedResponses.length > 0" x-cloak
                class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-gray-900 text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-6 z-[12000] animate-in slide-in-from-bottom-4 duration-300 border border-gray-800">
                <span class="text-xs font-black uppercase tracking-wider text-gray-400">
                    <span x-text="selectedResponses.length" class="text-indigo-400 font-black"></span> {{ __('Selected') }}
                </span>
                <div class="flex items-center gap-2">
                    <button type="submit" name="action" value="approve"
                        class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-md">
                        <i class="fa-solid fa-circle-check mr-1.5"></i>{{ __('Approve Selected') }}
                    </button>
                    <button type="submit" name="action" value="reject"
                        class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-md"
                        onclick="return confirm('Permanently delete selected responses? This action is irreversible.')">
                        <i class="fa-solid fa-trash mr-1.5"></i>{{ __('Reject/Delete Selected') }}
                    </button>
                </div>
            </div>

            <!-- Datatable Card -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div
                    class="px-8 py-6 border-b border-gray-50 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/30">
                    <div>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Raw Dataset') }}</h3>
                        <p class="text-[11px] text-gray-400 font-medium mt-0.5">
                            {{ __('Showing all responses and fraud-detection scoring logs.') }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Quality filter dropdown -->
                        <div class="flex items-center gap-1.5">
                            <span
                                class="text-[10px] font-black text-gray-400 uppercase tracking-wider">{{ __('Quality Filter') }}:</span>
                            <select
                                onchange="window.location.href = '{{ route('surveys.data', $survey) }}?quality=' + this.value"
                                class="text-[10px] rounded-xl border-gray-150 px-3 py-2 font-black uppercase tracking-wider text-gray-600 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('All Responses') }}</option>
                                <option value="clean" {{ request('quality') === 'clean' ? 'selected' : '' }}>🟢
                                    {{ __('Clean Only') }}
                                </option>
                                <option value="review" {{ request('quality') === 'review' ? 'selected' : '' }}>🟡
                                    {{ __('Review Only') }}
                                </option>
                                <option value="flagged" {{ request('quality') === 'flagged' ? 'selected' : '' }}>🔴
                                    {{ __('Flagged Only') }}
                                </option>
                            </select>
                        </div>

                        <span
                            class="px-3 py-2 bg-indigo-50 text-indigo-700 text-[10px] font-black rounded-xl border border-indigo-100 uppercase tracking-wider">
                            {{ $responses->total() }} {{ __('Total') }}
                        </span>

                        <a href="{{ route('surveys.export', $survey) }}"
                            class="px-4 py-2 bg-white text-gray-700 rounded-xl text-xs font-bold uppercase tracking-wider border border-gray-200 hover:border-indigo-600 hover:text-indigo-600 transition-all shadow-sm">
                            <i class="fa-solid fa-file-csv mr-2"></i> {{ __('Export CSV') }}
                        </a>
                    </div>
                </div>

                @if($responses->count() > 0)
                    <div class="overflow-x-auto min-h-[400px]">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead>
                                <tr class="bg-gray-50/10">
                                    <!-- Bulk Selection Checkbox Header -->
                                    <th scope="col" class="px-6 py-4 text-left w-10">
                                        <input type="checkbox"
                                            @change="selectedResponses = $event.target.checked ? {{ json_encode($responses->pluck('id')->toArray()) }} : []"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    </th>
                                    <th scope="col"
                                        class="px-4 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-widest">
                                        # {{ __('ID') }}
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-widest">
                                        {{ __('Submission Date') }}
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-widest">
                                        {{ __('Respondent') }}
                                    </th>
                                    <!-- Quality Score Column Header -->
                                    <th scope="col"
                                        class="px-6 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-widest">
                                        {{ __('Quality Score') }}
                                    </th>
                                    @foreach($headers as $header)
                                        <th scope="col"
                                            class="px-6 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-widest max-w-[200px] truncate"
                                            title="{{ $header['label'] }}">
                                            {{ $header['label'] }}
                                        </th>
                                    @endforeach
                                    <th scope="col"
                                        class="px-6 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-widest">
                                        {{ __('Sentiment') }}
                                    </th>
                                    <th scope="col"
                                        class="px-8 py-4 text-right sticky right-0 bg-white z-10 border-l border-gray-50">
                                        {{ __('Actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 bg-white">
                                @foreach($responses as $response)
                                    @php
                                        $schemaFields = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : $survey->json_schema;
                                        if (!is_array($schemaFields))
                                            $schemaFields = [];

                                        $isPremium = auth()->user()->hasProAccess();
                                        $transcriptions = $response->ai_metadata['transcriptions'] ?? [];

                                        // Quality details calculation
                                        $score = $response->quality_score ?? 100;
                                        $isFlagged = $response->is_flagged;
                                        $flags = $response->quality_flags ?? [];

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
                                    <tr class="hover:bg-gray-50/50 transition-colors group {{ $isFlagged ? 'bg-red-50/10' : '' }}">
                                        <!-- Checkbox cell -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" name="response_ids[]" value="{{ $response->id }}"
                                                x-model="selectedResponses"
                                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-[10px] font-black text-gray-900">
                                            {{ $loop->iteration + ($responses->currentPage() - 1) * $responses->perPage() }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-[10px] text-gray-500 font-bold">
                                            {{ $response->created_at->format('M d, Y • H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-[10px] font-black text-gray-900 uppercase tracking-tight">
                                                {{ $response->respondent ? $response->respondent->name : ($response->guest_name ?? __('Anonymous')) }}
                                            </span>
                                            @if($response->ip_address)
                                                <span class="block text-[8px] text-gray-400 font-medium lowercase">IP:
                                                    {{ $response->ip_address }}</span>
                                            @endif
                                        </td>
                                        <!-- Quality Cell -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex flex-col gap-0.5">
                                                <span
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[8px] font-black uppercase border {{ $badgeClass }} cursor-help"
                                                    @if(!empty($flags))
                                                    title="{{ collect($flags)->pluck('message')->implode('; ') }}" @endif>
                                                    @if($isFlagged)
                                                        ⚠️ {{ $badgeLabel }} ({{ $score }})
                                                    @else
                                                        ✓ {{ $badgeLabel }} ({{ $score }})
                                                    @endif
                                                </span>
                                                @if(!empty($flags))
                                                    <span
                                                        class="text-[7.5px] text-red-500 font-bold tracking-tight max-w-[120px] truncate"
                                                        title="{{ collect($flags)->pluck('message')->implode('; ') }}">
                                                        {{ count($flags) }} {{ __('flag(s)') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        @foreach($headers as $header)
                                            <td class="px-6 py-4 text-[10px] text-gray-600 font-medium max-w-[250px] truncate">
                                                @php
                                                    $val = '—';
                                                    if (!empty($survey->json_schema)) {
                                                        $jsonAnswer = $response->answers->first();
                                                        if ($jsonAnswer) {
                                                            $parsed = json_decode($jsonAnswer->value, true) ?? [];
                                                            foreach ($parsed as $item) {
                                                                if (isset($item['name']) && $item['name'] === $header['id']) {
                                                                    $val = $item['userData'] ?? '—';

                                                                    // Resolve options/Likert values to labels in data tab
                                                                    $field = collect($schemaFields)->firstWhere('name', $header['id']);
                                                                    if ($field && $val !== '—' && $val !== null && $val !== '') {
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
                                                        }
                                                    } else {
                                                        $ans = $response->answers->where('question_id', $header['id'])->first();
                                                        $val = $ans ? $ans->value : '—';
                                                    }

                                                    $valStr = trim((string) $val);
                                                    $isMedia = str_starts_with($valStr, 'uploads/') && preg_match('/\.(mp4|webm|ogg|ogv|mov|mp3|wav|m4a|aac)$/i', $valStr);
                                                @endphp

                                                @if($isMedia)
                                                    <div x-data="{ 
                                                                                                                                                                                                                                                                transcribing: false, 
                                                                                                                                                                                                                                                                transcription: @js($transcriptions[$valStr] ?? null),
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
                                                                                                                                                                                                                                                                            if (result.isConfirmed) window.location.href = '{{ route('subscriptions.index') }}';
                                                                                                                                                                                                                                                                        });
                                                                                                                                                                                                                                                                        return;
                                                                                                                                                                                                                                                                    @endif
                                                                                                                                                                                                                                                                    this.transcribing = true;
                                                                                                                                                                                                                                                                    try {
                                                                                                                                                                                                                                                                        const response = await fetch('{{ route('surveys.responses.transcribe', [$survey, $response]) }}', {
                                                                                                                                                                                                                                                                            method: 'POST',
                                                                                                                                                                                                                                                                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                                                                                                                                                                                                                                                            body: JSON.stringify({ file_path: @js($valStr) })
                                                                                                                                                                                                                                                                        });
                                                                                                                                                                                                                                                                        const data = await response.json();
                                                                                                                                                                                                                                                                        if (data.success) this.transcription = data.transcription;
                                                                                                                                                                                                                                                                    } finally {
                                                                                                                                                                                                                                                                        this.transcribing = false;
                                                                                                                                                                                                                                                                    }
                                                                                                                                                                                                                                                                }
                                                                                                                                                                                                                                                            }"
                                                        class="flex flex-col gap-1.5">
                                                        <div class="flex flex-col gap-1.5 py-1">
                                                            <a href="{{ asset('storage/' . $valStr) }}" target="_blank"
                                                                class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-bold group/media">
                                                                <i
                                                                    class="fa-solid fa-circle-play mr-1.5 text-indigo-400 group-hover/media:text-indigo-600 transition-colors"></i>
                                                                <span>{{ __('View Media') }}</span>
                                                            </a>

                                                            <div class="flex flex-col gap-1">
                                                                <button type="button" @click="transcribe" :disabled="transcribing"
                                                                    class="inline-flex items-center text-[9px] font-black uppercase tracking-wider transition-all"
                                                                    :class="transcription ? 'text-indigo-400 hover:text-indigo-600' : 'text-emerald-600 hover:text-emerald-800'">
                                                                    <template x-if="!transcription">
                                                                        <span><i
                                                                                class="fa-solid fa-wand-magic-sparkles mr-1.5"></i>{{ __('Transcribe') }}</span>
                                                                    </template>
                                                                    <template x-if="transcription">
                                                                        <span class="flex items-center">
                                                                            <i class="fa-solid fa-rotate-right mr-1.5 transition-transform"
                                                                                :class="transcribing ? 'fa-spin' : 'group-hover:rotate-180'"></i>
                                                                            {{ __('Regenerate') }}
                                                                        </span>
                                                                    </template>
                                                                </button>

                                                                <template x-if="transcription">
                                                                    <div class="text-[9px] text-gray-400 line-clamp-1 italic bg-gray-50/50 px-1.5 py-0.5 rounded border border-gray-100/50"
                                                                        :title="transcription" x-text="transcription"></div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @elseif (str_contains($valStr, 'base64,'))
                                                    <a href="javascript:void(0)"
                                                        onclick="Swal.fire({title:'Signature', imageUrl:'{{ $valStr }}', imageAlt:'Signature', customClass: {image: 'rounded-xl border border-gray-100 shadow-lg'}})"
                                                        class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-bold group/sig">
                                                        <i
                                                            class="fa-solid fa-signature mr-1.5 text-indigo-400 group-hover/sig:text-indigo-600 transition-colors"></i>
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
                                                    {{ $valStr }}
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
                                            <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase border {{ $cls }}">
                                                {{ __($sentiment) }}
                                            </span>
                                        </td>

                                        <!-- Actions Cell with single-override actions -->
                                        <td
                                            class="px-8 py-4 text-right sticky right-0 bg-white group-hover:bg-gray-50/50 z-10 border-l border-gray-50">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <a href="{{ route('surveys.responses.show', [$survey, $response]) }}"
                                                    class="inline-flex items-center px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-[9px] font-black uppercase tracking-wider transition-all">
                                                    <i class="fa-solid fa-eye mr-1"></i> {{ __('View') }}
                                                </a>

                                                @if($isFlagged)
                                                    <button type="submit" name="single_override_approve"
                                                        formaction="{{ route('surveys.responses.quality-override', [$survey, $response]) }}?action=approve"
                                                        class="inline-flex items-center px-2 py-1 bg-emerald-550 hover:bg-emerald-600 text-white rounded text-[9px] font-black uppercase tracking-wider transition-all">
                                                        <i class="fa-solid fa-circle-check"></i>
                                                    </button>
                                                    <button type="submit" name="single_override_reject"
                                                        formaction="{{ route('surveys.responses.quality-override', [$survey, $response]) }}?action=reject"
                                                        onclick="return confirm('Permanently reject and delete this flagged response?')"
                                                        class="inline-flex items-center px-2 py-1 bg-rose-550 hover:bg-rose-600 text-white rounded text-[9px] font-black uppercase tracking-wider transition-all">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($responses->hasPages())
                        <div class="p-8 border-t border-gray-50 bg-gray-50/30">
                            {{ $responses->links() }}
                        </div>
                    @endif
                @else
                    <div class="p-20 text-center">
                        <div
                            class="w-20 h-20 bg-gray-50 rounded-3xl flex items-center justify-center text-gray-200 mx-auto mb-6">
                            <i class="fa-solid fa-database text-3xl"></i>
                        </div>
                        <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">
                            {{ __('No Submissions Detected') }}
                        </h3>
                        <p class="text-[10px] text-gray-300 font-bold uppercase italic">
                            {{ __('Data will appear here once responses matching this quality tier are submitted.') }}
                        </p>
                    </div>
                @endif
            </div>
        </form>
    </div>
@endsection