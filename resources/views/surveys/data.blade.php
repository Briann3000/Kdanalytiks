@extends('surveys.hub')

@section('survey-content')
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-8 py-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/30">
            <div>
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Raw Dataset') }}</h3>
                <p class="text-[11px] text-gray-400 font-medium mt-0.5">{{ __('Showing all verified submissions for this project.') }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span
                    class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-100 uppercase tracking-tight">
                    {{ $responses->total() }} {{ __('Total Responses') }}
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
                            <th scope="col"
                                class="px-6 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-widest sticky left-0 bg-white z-10 border-r border-gray-50">
                                # {{ __('ID') }}</th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-widest">
                                {{ __('Submission Date') }}</th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-widest">
                                {{ __('Respondent') }}</th>
                            @foreach($headers as $header)
                                <th scope="col"
                                    class="px-6 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-widest max-w-[200px] truncate"
                                    title="{{ $header['label'] }}">
                                    {{ $header['label'] }}
                                </th>
                            @endforeach
                            <th scope="col"
                                class="px-6 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-widest">
                                {{ __('Sentiment') }}</th>
                            <th scope="col" class="px-8 py-4 text-right sticky right-0 bg-white z-10 border-l border-gray-50">
                                {{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 bg-white">
                        @foreach($responses as $response)
                            <tr class="hover:bg-gray-50/50 transition-colors group">
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-[10px] font-black text-gray-900 sticky left-0 bg-white group-hover:bg-gray-50/50 z-10 border-r border-gray-50">
                                    {{ $loop->iteration + ($responses->currentPage() - 1) * $responses->perPage() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-[10px] text-gray-500 font-bold">
                                    {{ $response->created_at->format('M d, Y • H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="text-[10px] font-black text-gray-900 uppercase tracking-tight">{{ $response->respondent ? $response->respondent->name : __('Anonymous') }}</span>
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
                                                            $val = isset($item['userData']) ? (is_array($item['userData']) ? implode(', ', $item['userData']) : $item['userData']) : '—';
                                                            break;
                                                        }
                                                    }
                                                }
                                            } else {
                                                $ans = $response->answers->where('question_id', $header['id'])->first();
                                                $val = $ans ? $ans->value : '—';
                                            }
                                            // Handle media paths
                                            if (str_starts_with($val, 'uploads/')) {
                                                $val = __('Media File');
                                            }
                                        @endphp
                                        {{ str($val)->limit(40) }}
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
                                <td
                                    class="px-8 py-4 text-right sticky right-0 bg-white group-hover:bg-gray-50/50 z-10 border-l border-gray-50">
                                    <a href="{{ route('surveys.responses.show', [$survey, $response]) }}"
                                        class="inline-flex items-center px-3 py-1 bg-gray-900 text-white rounded text-[9px] font-black uppercase tracking-widest hover:bg-indigo-600 transition-all">
                                        <i class="fa-solid fa-eye mr-2"></i> {{ __('View') }}
                                    </a>
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
                <div class="w-20 h-20 bg-gray-50 rounded-3xl flex items-center justify-center text-gray-200 mx-auto mb-6">
                    <i class="fa-solid fa-database text-3xl"></i>
                </div>
                <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">{{ __('No Submissions Detected') }}</h3>
                <p class="text-[10px] text-gray-300 font-bold uppercase italic">{{ __('Data will appear here once the survey is deployed and responses start coming in.') }}</p>
            </div>
        @endif
    </div>
@endsection