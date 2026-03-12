@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
@php /** @var \Illuminate\Support\Collection|\App\Models\Survey[] $recentPublicSurveys */ @endphp
    <div class="px-4 sm:px-0">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Admin Overview</h2>
                <p class="mt-1 text-sm text-gray-500">Global system metrics and administrative controls.</p>
            </div>
            <div class="flex space-x-3">
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <span class="h-2 w-2 mr-1.5 rounded-full bg-green-500"></span>
                    System Healthy
                </span>
            </div>
        </div>

        <!-- Main Stats Grid -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <!-- Total Users -->
            <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-100 transition-hover hover:shadow-md">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                            <i class="fa-solid fa-users text-white text-xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">{{ $stats['totalUsers'] }}</div>
                                    <div class="ml-2 flex items-baseline text-sm font-semibold text-gray-500">
                                        <span class="sr-only">Pending</span>
                                        <span class="text-orange-600">({{ $stats['pendingUsers'] }} pending)</span>
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Surveys -->
            <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-100 transition-hover hover:shadow-md">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                            <i class="fa-solid fa-file-lines text-white text-xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Surveys</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">{{ $stats['totalSurveys'] }}</div>
                                    <div class="ml-2 flex flex-col text-xs font-semibold">
                                        <span class="text-orange-600">{{ $stats['pendingSurveys'] }} pending approval</span>
                                        <span class="text-gray-400">{{ $stats['draftSurveys'] }} drafts</span>
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Responses -->
            <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-100 transition-hover hover:shadow-md">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                            <i class="fa-solid fa-comment-dots text-white text-xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Responses</dt>
                                <dd>
                                    <div class="text-2xl font-semibold text-gray-900">{{ $stats['totalResponses'] }}</div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Organizations & Researchers -->
            <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-100 transition-hover hover:shadow-md">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                            <i class="fa-solid fa-building-columns text-white text-xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Entities</dt>
                                <dd class="flex flex-col">
                                    <span class="text-sm text-gray-900 font-semibold">{{ $stats['totalOrganizations'] }}
                                        Orgs</span>
                                    <span class="text-sm text-gray-900 font-semibold">{{ $stats['totalResearchers'] }}
                                        Researchers</span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Public Metrics -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 mb-8">
            <!-- Quick Actions -->
            <div class="bg-white shadow rounded-lg border border-gray-100">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">Quick Administrative Actions</h3>
                </div>
                <div class="p-6 grid grid-cols-2 gap-4">
                    <a href="{{ route('admin.users.index') }}"
                        class="flex flex-col items-center justify-center p-4 rounded-xl border border-gray-100 bg-gray-50 hover:bg-indigo-50 hover:border-indigo-200 transition-all group">
                        <i class="fa-solid fa-user-gear text-2xl text-gray-400 group-hover:text-indigo-600 mb-2"></i>
                        <span class="text-sm font-bold text-gray-700 group-hover:text-indigo-900">Manage Users</span>
                    </a>
                    <a href="{{ route('admin.surveys.index') }}"
                        class="flex flex-col items-center justify-center p-4 rounded-xl border border-gray-100 bg-gray-50 hover:bg-blue-50 hover:border-blue-200 transition-all group">
                        <i class="fa-solid fa-list-check text-2xl text-gray-400 group-hover:text-blue-600 mb-2"></i>
                        <span class="text-sm font-bold text-gray-700 group-hover:text-blue-900">Manage Surveys</span>
                    </a>
                    <a href="{{ route('admin.surveys.create') }}"
                        class="flex flex-col items-center justify-center p-4 rounded-xl border border-gray-100 bg-gray-50 hover:bg-green-50 hover:border-green-200 transition-all group">
                        <i class="fa-solid fa-plus-circle text-2xl text-gray-400 group-hover:text-green-600 mb-2"></i>
                        <span class="text-sm font-bold text-gray-700 group-hover:text-green-900">Create Survey</span>
                    </a>
                    <a href="{{ route('admin.analytics.index') }}"
                        class="flex flex-col items-center justify-center p-4 rounded-xl border border-gray-100 bg-gray-50 hover:bg-purple-50 hover:border-purple-200 transition-all group">
                        <i class="fa-solid fa-chart-line text-2xl text-gray-400 group-hover:text-purple-600 mb-2"></i>
                        <span class="text-sm font-bold text-gray-700 group-hover:text-purple-900">Analytics Dashboard</span>
                    </a>
                    <a href="{{ route('surveys.public') }}"
                        class="flex flex-col items-center justify-center p-4 rounded-xl border border-gray-100 bg-gray-50 hover:bg-orange-50 hover:border-orange-200 transition-all group">
                        <i class="fa-solid fa-globe text-2xl text-gray-400 group-hover:text-orange-600 mb-2"></i>
                        <span class="text-sm font-bold text-gray-700 group-hover:text-orange-900">Browse Public
                            Surveys</span>
                    </a>
                </div>
            </div>

            <!-- Public Surveys Overview -->
            <div class="bg-white shadow rounded-lg border border-gray-100">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">Public Engagement</h3>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex flex-col">
                            <span class="text-3xl font-extrabold text-indigo-600">{{ $publicStats['count'] }}</span>
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Public Surveys</span>
                        </div>
                        <div class="flex flex-col text-right">
                            <span class="text-3xl font-extrabold text-blue-600">{{ $publicStats['responses'] }}</span>
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Public Responses</span>
                        </div>
                    </div>
                    <div class="bg-indigo-50 rounded-xl p-4 flex items-center">
                        <div
                            class="flex-shrink-0 h-10 w-10 bg-white rounded-full flex items-center justify-center shadow-sm">
                            <i class="fa-solid fa-gauge-high text-indigo-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-bold text-indigo-900">Engagement Score</p>
                            <p class="text-xs text-indigo-700">Average of <span
                                    class="font-bold">{{ $publicStats['average'] }}</span> responses per public survey.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Public Surveys Table -->
        <div class="bg-white shadow rounded-lg border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900">Recent Public Surveys</h3>
                <a href="{{ route('admin.surveys.index') }}?type=public"
                    class="text-sm font-medium text-indigo-600 hover:text-indigo-500">View All</a>
            </div>
            @if($recentPublicSurveys->count() > 0)
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responses
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($recentPublicSurveys as $survey)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">{{ $survey->title }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $survey->organization_id ? $survey->organization->name : ($survey->independent_id ? $survey->independent->name : 'Admin') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $survey->responses_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $survey->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="p-12 text-center">
                    <i class="fa-solid fa-folder-open text-gray-200 text-5xl mb-4"></i>
                    <p class="text-gray-500 font-medium">No recent public surveys found.</p>
                </div>
            @endif
        </div>
    </div>
@endsection