@extends('layouts.app')

@section('content')
    <div class="mb-6 px-4 sm:px-0">
        <h2 class="text-2xl font-bold text-gray-900">Survey Reports</h2>
        <p class="mt-1 text-sm text-gray-500">Generate and view reports for your surveys.</p>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        @if ($surveys->count() > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Survey</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Responses</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($surveys as $survey)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $survey->title }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $survey->responses_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('surveys.report', $survey) }}" class="text-indigo-600 hover:text-indigo-900"><i
                                        class="fa-solid fa-file-chart-pie mr-1"></i> Generate Report</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 sm:px-6">
                {{ $surveys->links() }}
            </div>
        @else
            <div class="px-4 py-12 flex flex-col items-center justify-center text-center sm:px-6">
                <i class="fa-solid fa-chart-line text-gray-300 text-5xl mb-4"></i>
                <p class="text-gray-500 font-medium text-lg">No reports available</p>
                <p class="text-gray-400 text-sm mt-1">Once you have active surveys with responses, you can generate reports
                    here.</p>
            </div>
        @endif
    </div>
@endsection