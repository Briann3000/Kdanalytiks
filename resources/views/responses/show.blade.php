@extends('layouts.app')

@section('title', 'Responses for ' . $survey->title)

@section('content')
    <div class="px-4 sm:px-0 mb-8">
        <div class="flex items-center justify-between">
            <div>
                <nav class="flex mb-2" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-2 text-sm text-gray-500">
                        @php 
                            $userRoleVal = auth()->user()->role instanceof \UnitEnum ? auth()->user()->role->value : auth()->user()->role;
                        @endphp
                        <li><a href="{{ route($userRoleVal . '.surveys.index') }}" class="hover:text-indigo-600">Surveys</a></li>
                        <li><i class="fa-solid fa-chevron-right text-[10px]"></i></li>
                        <li class="font-medium text-gray-900">{{ $survey->title }}</li>
                    </ol>
                </nav>
                <h2 class="text-2xl font-bold text-gray-900">Survey Responses</h2>
            </div>

                           <div class="flex space-x-3">
                <a href="{{ route('surveys.export', $survey) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fa-solid fa-download mr-2"></i> Export CSV
                </a>
            </div>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg border border-gray-100">
        <div class="px-4 py-5 sm:px-6 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Total Responses: {{ $responses->total() }}
        </h3>
        </div>

        @if($responses->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-5
              0                     ">
                            <tr>


                                                        <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</t
                                h>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Respondent</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">AI Sentiment</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Answers</th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">View</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($responses as $response)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $response->created_at->format('M d, Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $response->respondent ? $response->respondent->name : 'Anonymous' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $sentiment = $response->ai_metadata['sentiment'] ?? 'N/A';
                                            $badgeClasses = [
                                                'Positive' => 'bg-green-100 text-green-800',
                                                'Negative' => 'bg-red-100 text-red-800',
                                                'Neutral' => 'bg-gray-100 text-gray-800',
                                                'N/A' => 'bg-gray-50 text-gray-400'
                                            ];
                                            $class = $badgeClasses[$sentiment] ?? $badgeClasses['N/A'];
                                        @endphp
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $class }}">
                                            {{ $sentiment }}
                                        </span>
                                    </td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                @php
                                    $answersSummary = $response->answers->take(3)->map(function ($a) {
                                        return ($a->question ? $a->question->text : 'Q') . ': ' . $a->value;
                                    })->implode(', ');
                                @endphp
                                        {{ $answersSummary }}{{ $response->answers->count() > 3 ? '...' : '' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('surveys.responses.show', [$survey, $response]) }}" class="text-indigo-600 hover:text-indigo-900">View Details</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
        @else
            <div class="p-12 text-center">
                <i class="fa-solid fa-comment-slash text-gray-200 text-6xl mb-4"></i>
                <p class="text-gray-500 font-medium">No responses yet for this survey.</p>
            </div>
        @endif
    </div>

    @if($responses->hasPages())
        <div class="mt-6">
            {{ $responses->links() }}
        </div>
    @endif
@endsection
