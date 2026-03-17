@extends('layouts.app')

@section('title', 'Responses for ' . $survey->title)

@section('sub_sidebar')
    @include('reports.partials._report_sidebar')
@endsection

@section('content')
<div class="w-full">
    <!-- Workspace Content (Scrollable Container) -->
    <div class="relative">
        <!-- Persistent Top Header -->
        <header class="sticky top-0 z-50 bg-gray-100 border-b border-gray-100 flex items-center justify-between px-10 py-5">
            <div>
                <h1 class="text-xl font-black text-gray-900 tracking-tight leading-none mb-1">{{ $survey->title }}</h1>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-[0.2em]">Responses Dataset</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <a href="{{ route('surveys.export', $survey) }}" class="flex items-center gap-2 px-4 py-2 bg-white text-gray-700 rounded-xl text-[10px] font-black uppercase tracking-widest border border-gray-200 hover:border-indigo-600 hover:text-indigo-600 transition-all shadow-sm">
                    <i class="fa-solid fa-file-csv"></i> Export CSV
                </a>
            </div>
        </header>

        <div class="p-10 space-y-8 max-w-7xl mx-auto">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50/50 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest">
                        Dataset Statistics
                    </h3>
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-[10px] font-black rounded-lg border border-indigo-100 uppercase tracking-tighter">
                        {{ $responses->total() }} Total Responses
                    </span>
                </div>

                @if($responses->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead>
                                <tr class="bg-gray-50/30">
                                    <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Date</th>
                                    <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Respondent</th>
                                    <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">AI Sentiment</th>
                                    <th scope="col" class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Answers Preview</th>
                                    <th scope="col" class="relative px-6 py-4">
                                        <span class="sr-only">View</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-50">
                                @foreach($responses as $response)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-[12px] text-gray-500 font-medium">
                                            {{ $response->created_at->format('M d, Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-[13px] text-gray-900 font-bold">
                                            {{ $response->respondent ? $response->respondent->name : 'Anonymous' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $sentiment = $response->ai_metadata['sentiment'] ?? 'N/A';
                                                $badgeClasses = [
                                                    'Positive' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                                    'Negative' => 'bg-rose-50 text-rose-700 border-rose-100',
                                                    'Neutral' => 'bg-gray-50 text-gray-600 border-gray-100',
                                                    'N/A' => 'bg-gray-50 text-gray-400 border-transparent'
                                                ];
                                                $class = $badgeClasses[$sentiment] ?? $badgeClasses['N/A'];
                                            @endphp
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase border {{ $class }}">
                                                {{ $sentiment }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-[12px] text-gray-500 max-w-xs truncate font-medium">
                                            @php
                                                $answersSummary = $response->answers->take(3)->map(function ($a) {
                                                    return ($a->question ? $a->question->text : 'Q') . ': ' . $a->value;
                                                })->implode(', ');
                                            @endphp
                                            {{ $answersSummary }}{{ $response->answers->count() > 3 ? '...' : '' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <a href="{{ route('surveys.responses.show', [$survey, $response]) }}" class="text-indigo-600 hover:text-indigo-900 font-black text-[11px] uppercase tracking-wider">Details</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-12 text-center">
                        <i class="fa-solid fa-comment-slash text-gray-100 text-6xl mb-4"></i>
                        <p class="text-gray-400 font-black uppercase text-[11px] tracking-widest">No responses yet</p>
                    </div>
                @endif
            </div>

            @if($responses->hasPages())
                <div class="flex justify-center pt-4">
                    {{ $responses->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
