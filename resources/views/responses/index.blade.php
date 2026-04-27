@extends('layouts.app')

@section('content')
    <div class="mb-6 px-4 sm:px-0">
        <h2 class="text-2xl font-bold text-gray-900 leading-tight">Collected Responses</h2>
        <p class="mt-1 text-sm text-gray-500">View and manage responses collected across your active surveys.</p>
    </div>

    <div class="bg-white shadow-xl shadow-gray-200/50 sm:rounded-2xl border border-gray-100 overflow-hidden">
        @if ($surveys->count() > 0)
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th scope="col"
                            class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">
                            Survey Title</th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">
                            Total Responses</th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">
                            Last Response</th>
                        <th scope="col"
                            class="px-6 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-50">
                    @foreach ($surveys as $survey)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div
                                        class="h-8 w-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 mr-3">
                                        <i class="fa-solid fa-file-invoice"></i>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900">{{ $survey->title }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-black bg-green-50 text-green-700">
                                    {{ $survey->responses_count }} Responses
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $survey->updated_at->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('surveys.data', $survey) }}"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-50 text-indigo-700 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-100 transition-all">
                                    <i class="fa-solid fa-list-ul mr-2"></i> View All
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3 bg-white border-t border-gray-100 sm:px-6">
                {{ $surveys->links() }}
            </div>
        @else
            <div class="px-4 py-16 flex flex-col items-center justify-center text-center">
                <div class="h-20 w-20 bg-gray-50 rounded-full flex items-center justify-center mb-4 text-gray-300">
                    <i class="fa-solid fa-comment-dots text-4xl"></i>
                </div>
                <p class="text-gray-900 font-black text-lg uppercase tracking-tight">No responses captured</p>
                <p class="text-gray-500 text-sm mt-1 max-w-sm">Share your surveys to start collecting data. Once someone fills
                    them, they will appear here grouped by survey.</p>
                <a href="{{ route('surveys.index', ['status' => 'active']) }}"
                    class="mt-6 inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all">
                    Go to active surveys
                </a>
            </div>
        @endif
    </div>
@endsection