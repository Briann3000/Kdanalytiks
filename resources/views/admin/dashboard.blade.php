@extends('layouts.app')

@section('title', __('Admin Dashboard'))

@section('content')
    @php /** @var \Illuminate\Support\Collection|\App\Models\Survey[] $recentPublicSurveys */ @endphp
    <div class="px-4 sm:px-0">
        <div class="flex items-center justify-between mb-10">
            <div>
                <h2 class="text-3xl font-black text-gray-900 leading-none mb-2 tracking-tight">
                    {{ __('System Infrastructure') }}</h2>
                <p class="text-sm text-gray-500 font-bold uppercase tracking-widest">
                    {{ __('GLOBAL ADMINISTRATION & MONITORING') }}</p>
            </div>
            <div class="flex space-x-3">
                <span
                    class="inline-flex items-center px-4 py-2 rounded-full text-[10px] font-black bg-green-50 text-green-700 border border-green-100 uppercase tracking-widest">
                    <span class="h-2 w-2 mr-2 rounded-full bg-green-500 animate-pulse"></span>
                    {{ __('Systems Operational') }}
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
                                <dt class="text-sm font-medium text-gray-500 truncate">{{ __('Total Users') }}</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">{{ $stats['totalUsers'] }}</div>
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
                                <dt class="text-sm font-medium text-gray-500 truncate">{{ __('Total Surveys') }}</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">{{ $stats['totalSurveys'] }}</div>
                                    <div class="ml-2 flex flex-col text-xs font-semibold">
                                        <span class="text-gray-400">{{ $stats['draftSurveys'] }} {{ __('drafts') }}</span>
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
                                <dt class="text-sm font-medium text-gray-500 truncate">{{ __('Total Responses') }}</dt>
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
                                <dt class="text-sm font-medium text-gray-500 truncate">{{ __('Entities') }}</dt>
                                <dd class="flex flex-col">
                                    <span class="text-sm text-gray-900 font-semibold">{{ $stats['totalOrganizations'] }}
                                        {{ __('Orgs') }}</span>
                                    <span class="text-sm text-gray-900 font-semibold">{{ $stats['totalResearchers'] }}
                                        {{ __('Researchers') }}</span>
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
                    <h3 class="text-lg font-bold text-gray-900">{{ __('Quick Administrative Actions') }}</h3>
                </div>
                <div class="p-6 grid grid-cols-2 gap-4">
                    <a href="{{ route('admin.users.index') }}"
                        class="flex flex-col items-center justify-center p-4 rounded-xl border border-gray-100 bg-gray-50 hover:bg-indigo-50 hover:border-indigo-200 transition-all group">
                        <i class="fa-solid fa-user-gear text-2xl text-gray-400 group-hover:text-indigo-600 mb-2"></i>
                        <span
                            class="text-sm font-bold text-gray-700 group-hover:text-indigo-900">{{ __('Manage Users') }}</span>
                    </a>
                    <a href="{{ route('admin.surveys.index') }}"
                        class="flex flex-col items-center justify-center p-4 rounded-xl border border-gray-100 bg-gray-50 hover:bg-blue-50 hover:border-blue-200 transition-all group">
                        <i class="fa-solid fa-list-check text-2xl text-gray-400 group-hover:text-blue-600 mb-2"></i>
                        <span
                            class="text-sm font-bold text-gray-700 group-hover:text-blue-900">{{ __('Manage Surveys') }}</span>
                    </a>
                    <a href="{{ route('surveys.create') }}"
                        class="flex flex-col items-center justify-center p-4 rounded-xl border border-gray-100 bg-gray-50 hover:bg-green-50 hover:border-green-200 transition-all group">
                        <i class="fa-solid fa-plus-circle text-2xl text-gray-400 group-hover:text-green-600 mb-2"></i>
                        <span
                            class="text-sm font-bold text-gray-700 group-hover:text-green-900">{{ __('Create Survey') }}</span>
                    </a>
                    <a href="{{ route('admin.analytics.index') }}"
                        class="flex flex-col items-center justify-center p-4 rounded-xl border border-gray-100 bg-gray-50 hover:bg-purple-50 hover:border-purple-200 transition-all group">
                        <i class="fa-solid fa-chart-line text-2xl text-gray-400 group-hover:text-purple-600 mb-2"></i>
                        <span
                            class="text-sm font-bold text-gray-700 group-hover:text-purple-900">{{ __('Analytics Dashboard') }}</span>
                    </a>
                    <a href="{{ route('surveys.public') }}"
                        class="flex flex-col items-center justify-center p-4 rounded-xl border border-gray-100 bg-gray-50 hover:bg-orange-50 hover:border-orange-200 transition-all group">
                        <i class="fa-solid fa-globe text-2xl text-gray-400 group-hover:text-orange-600 mb-2"></i>
                        <span
                            class="text-sm font-bold text-gray-700 group-hover:text-orange-900">{{ __('Browse Public Surveys') }}</span>
                    </a>
                </div>
            </div>

            <!-- Public Surveys Overview -->
            <div class="bg-white shadow rounded-lg border border-gray-100">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">{{ __('Public Engagement') }}</h3>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex flex-col">
                            <span class="text-3xl font-extrabold text-indigo-600">{{ $publicStats['count'] }}</span>
                            <span
                                class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('PUBLIC SURVEYS') }}</span>
                        </div>
                        <div class="flex flex-col text-right">
                            <span class="text-3xl font-extrabold text-blue-600">{{ $publicStats['responses'] }}</span>
                            <span
                                class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('PUBLIC RESPONSES') }}</span>
                        </div>
                    </div>
                    <div class="bg-indigo-50 rounded-xl p-4 flex items-center">
                        <div
                            class="flex-shrink-0 h-10 w-10 bg-white rounded-full flex items-center justify-center shadow-sm">
                            <i class="fa-solid fa-gauge-high text-indigo-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-bold text-indigo-900">{{ __('Engagement Score') }}</p>
                            <p class="text-xs text-indigo-700">{{ __('Average of') }} <span
                                    class="font-bold">{{ $publicStats['average'] }}</span>
                                {{ __('responses per public survey.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Public Surveys Table -->
        <div class="bg-white shadow rounded-lg border border-gray-100 overflow-hidden mb-8">
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest">{{ __('Active Public Campaigns') }}
                </h3>
                <a href="{{ route('admin.surveys.index') }}?type=public"
                    class="text-[10px] font-black text-indigo-600 hover:text-indigo-800 uppercase bg-white px-3 py-1 rounded-full shadow-sm border border-gray-100">{{ __('Browse Full List') }}</a>
            </div>
            @if($recentPublicSurveys->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead>
                            <tr class="bg-gray-50/30">
                                <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    {{ __('Campaign') }}</th>
                                <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    {{ __('Entity') }}</th>
                                <th
                                    class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    {{ __('Engagement') }}</th>
                                <th class="px-6 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    {{ __('Launch Date') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($recentPublicSurveys as $survey)
                                <tr class="hover:bg-gray-50/50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-black text-gray-900 group-hover:text-indigo-600 transition-colors">
                                            {{ $survey->title }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-[11px] font-bold text-gray-500 uppercase">
                                            {{ $survey->organization_id ? $survey->organization->name : ($survey->independent_id ? $survey->independent->name : __('Platform')) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black bg-blue-50 text-blue-700 border border-blue-100 shadow-sm">
                                            {{ number_format($survey->responses_count) }} {{ __('RESPONSES') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-[10px] font-black text-gray-400 uppercase">
                                        {{ $survey->created_at->diffForHumans() }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-16 text-center">
                    <i class="fa-solid fa-folder-open text-gray-200 text-6xl mb-4 block"></i>
                    <p class="text-gray-400 font-black uppercase text-xs tracking-widest">{{ __('No active campaigns found') }}
                    </p>
                </div>
            @endif
        </div>

        <!-- Admin Dashboard Bottom Grid -->
        <div class="mb-12">
            <!-- Latest Signups - Now Full Width and more card-like -->
            <div class="bg-white shadow rounded-lg border border-gray-100 overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <h3 class="text-lg font-black text-gray-900 uppercase tracking-widest flex items-center">
                        <i class="fa-solid fa-user-plus mr-4 text-indigo-500"></i> {{ __('Recent User Integrations') }}
                    </h3>
                    <a href="{{ route('admin.users.index') }}"
                        class="text-[11px] font-black text-indigo-600 hover:text-indigo-800 uppercase bg-white px-4 py-2 rounded-full shadow-sm border border-gray-100 transition-all">{{ __('Audit Directory') }}</a>
                </div>
                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($latestUsers as $user)
                            <div
                                class="flex items-center space-x-5 p-6 bg-gray-50/50 rounded-2xl border border-gray-50 hover:border-indigo-200 hover:bg-white hover:shadow-lg transition-all group">
                                <div
                                    class="h-14 w-14 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-500 group-hover:bg-indigo-600 group-hover:text-white transition-all shadow-sm">
                                    <i class="fa-solid fa-user text-xl"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-base font-black text-gray-900 truncate tracking-tight mb-0.5">
                                        {{ $user->name }}</p>
                                    <p class="text-xs text-gray-400 font-medium truncate mb-2">{{ $user->email }}</p>
                                    <div class="flex items-center space-x-2">
                                        <span
                                            class="text-[9px] font-black px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded uppercase tracking-wider">{{ $user->role instanceof \UnitEnum ? $user->role->name : $user->role }}</span>
                                        <span
                                            class="text-[9px] font-bold text-gray-300 uppercase italic">{{ $user->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection