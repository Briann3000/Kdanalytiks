@extends('layouts.app')

@section('title', 'My Survey History')

@section('content')
    <div class="px-4 sm:px-0">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">My Survey History</h2>
                <p class="mt-1 text-sm text-gray-500">View the surveys you have previously participated in.</p>
            </div>
            <div>
                <a href="{{ route('respondent.dashboard') }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg border border-gray-100 overflow-hidden">
            <ul role="list" class="divide-y divide-gray-200">
                @forelse ($responses as $response)
                    <li class="p-6 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-bold text-indigo-600 truncate">
                                    {{ $response->survey?->title ?? 'Deleted Survey' }}
                                </h3>
                                <div class="mt-2 flex items-center text-sm text-gray-500">
                                    <i class="fa-solid fa-tag flex-shrink-0 mr-1.5 text-gray-400"></i>
                                    <span class="truncate mr-4">{{ $response->survey?->category ?? 'Unknown Category' }}</span>

                                    <i class="fa-solid fa-calendar-check flex-shrink-0 mr-1.5 text-green-500"></i>
                                    <span>Submitted on {{ $response->created_at->format('M j, Y g:i A') }}</span>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">
                                    <i class="fa-solid fa-check mr-1.5"></i> Completed
                                </span>
                                @if($response->survey && $response->survey->reward_per_response > 0)
                                    <span
                                        class="text-xs font-black text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-100">
                                        Earned: {{ number_format((float) $response->survey->reward_per_response, 2) }}
                                        {{ $response->survey->currency ?? 'KES' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="py-12 px-6 text-center">
                        <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fa-solid fa-folder-open text-2xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900">No History Yet</h3>
                        <p class="mt-2 text-sm text-gray-500 max-w-sm mx-auto">
                            You haven't completed any surveys. Head over to the Public Marketplace to get started!
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('surveys.public') }}"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                                <i class="fa-solid fa-globe mr-2"></i> Browse Public Surveys
                            </a>
                        </div>
                    </li>
                @endforelse
            </ul>

            @if(method_exists($responses, 'hasPages') && $responses->hasPages())
                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    {!! method_exists($responses, 'links') ? $responses->links() : '' !!}
                </div>
            @endif
        </div>
    </div>
@endsection