@extends('layouts.app')

@section('content')
    <div class="mb-6 px-4 sm:px-0">
        <h2 class="text-2xl font-bold text-gray-900">Survey Responses</h2>
        <p class="mt-1 text-sm text-gray-500">View all responses collected across your surveys.</p>
    </div>

    <div class="bg-white shadow sm:rounded-md">
        @if ($responses->count() > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Survey</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Respondent</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                            Date</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($responses as $response)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $response->survey->title }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $response->respondent ? $response->respondent->name : 'Anonymous' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $response->created_at->format('M j, Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('surveys.responses.show', [$response->survey, $response]) }}"
                                    class="text-indigo-600 hover:text-indigo-900"><i class="fa-solid fa-eye mr-1"></i> View
                                    Details</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 sm:px-6">
                {{ $responses->links() }}
            </div>
        @else
            <div class="px-4 py-12 flex flex-col items-center justify-center text-center sm:px-6">
                <i class="fa-solid fa-comment-dots text-gray-300 text-5xl mb-4"></i>
                <p class="text-gray-500 font-medium text-lg">No responses yet</p>
                <p class="text-gray-400 text-sm mt-1">Once respondents start filling your surveys, they will appear here.</p>
            </div>
        @endif
    </div>
@endsection