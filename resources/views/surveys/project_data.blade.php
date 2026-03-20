@extends('surveys.project_hub')

@section('project-content')
<div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-8 py-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/30">
        <div>
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Raw Dataset</h3>
            <p class="text-[11px] text-gray-400 font-medium mt-0.5">Showing all verified submissions for this project.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-100 uppercase tracking-tight">
                {{ $responses->total() }} Total Responses
            </span>
            <a href="{{ route('surveys.export', $survey) }}" class="px-4 py-2 bg-white text-gray-700 rounded-xl text-xs font-bold uppercase tracking-wider border border-gray-200 hover:border-indigo-600 hover:text-indigo-600 transition-all shadow-sm">
                <i class="fa-solid fa-file-csv mr-2"></i> Export CSV
            </a>
        </div>
    </div>

    @if($responses->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="bg-gray-50/10">
                        <th scope="col" class="px-8 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Submission Date</th>
                        <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Respondent</th>
                        <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Sentiment</th>
                        <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Brief</th>
                        <th scope="col" class="px-8 py-4 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 bg-white">
                    @foreach($responses as $response)
                        <tr class="hover:bg-gray-50/50 transition-colors group">
                            <td class="px-8 py-4 whitespace-nowrap text-xs text-gray-500 font-bold">
                                {{ $response->created_at->format('M d, Y • H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs font-bold text-gray-900 tracking-tight">{{ $response->respondent ? $response->respondent->name : 'Anonymous User' }}</span>
                            </td>
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
                                <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase border {{ $cls }}">
                                    {{ $sentiment }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-[11px] text-gray-400 max-w-xs truncate font-medium italic">
                                @php
                                    $answersSummary = $response->answers->take(2)->map(function ($a) {
                                        return $a->value;
                                    })->implode(', ');
                                @endphp
                                {{ str($answersSummary)->limit(50) }}
                            </td>
                            <td class="px-8 py-4 text-right">
                                <a href="{{ route('surveys.responses.show', [$survey, $response]) }}" class="w-8 h-8 rounded-lg bg-gray-50 text-gray-400 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all">
                                    <i class="fa-solid fa-eye text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($responses->hasPages())
            <div class="p-8 border-t border-gray-50">
                {{ $responses->links() }}
            </div>
        @endif
    @else
        <div class="p-20 text-center">
            <div class="w-20 h-20 bg-gray-50 rounded-3xl flex items-center justify-center text-gray-200 mx-auto mb-6">
                <i class="fa-solid fa-database text-3xl"></i>
            </div>
            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">No Submissions Detected</h3>
            <p class="text-[10px] text-gray-300 font-bold uppercase italic">Data will appear here once the survey is deployed and responses start coming in.</p>
        </div>
    @endif
</div>
@endsection
