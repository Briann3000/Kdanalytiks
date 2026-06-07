@extends('layouts.app')

@section('content')
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
        <div class="px-6 py-6 sm:px-8 flex justify-between items-center bg-indigo-50 border-l-8 border-indigo-600">
            <div>
                <h3 class="text-2xl leading-none font-black text-gray-900 mb-1">
                    {{ __('Welcome back') }}, {{ $displayName }}
                </h3>
                <p class="text-sm text-gray-600 font-bold uppercase tracking-widest">
                    {{ __(strtoupper($role) . ' DASHBOARD') }}
                </p>
            </div>
            @if(in_array($role, ['organization', 'independent']) && isset($subscriptionTier))
                @php
                    $tierSlug = $subscriptionTier->slug ?? 'free';
                    $tierName = $subscriptionTier->name ?? 'Free';
                    $tierConfig = match (true) {
                        str_contains(strtolower($tierSlug), 'enterprise') => [
                            'bg' => 'bg-amber-50',
                            'text' => 'text-amber-700',
                            'border' => 'border-amber-300',
                            'dot' => 'bg-amber-500',
                            'icon' => 'fa-crown',
                            'label' => 'Enterprise',
                        ],
                        str_contains(strtolower($tierSlug), 'pro') => [
                            'bg' => 'bg-indigo-50',
                            'text' => 'text-indigo-700',
                            'border' => 'border-indigo-300',
                            'dot' => 'bg-indigo-500',
                            'icon' => 'fa-bolt',
                            'label' => 'Pro',
                        ],
                        default => [
                            'bg' => 'bg-gray-100',
                            'text' => 'text-gray-500',
                            'border' => 'border-gray-300',
                            'dot' => 'bg-gray-400',
                            'icon' => 'fa-circle-check',
                            'label' => 'Free',
                        ],
                    };
                @endphp
                <div class="flex flex-col items-end gap-1">
                    <span
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-black uppercase tracking-widest border shadow-sm {{ $tierConfig['bg'] }} {{ $tierConfig['text'] }} {{ $tierConfig['border'] }}">
                        <i class="fa-solid {{ $tierConfig['icon'] }} text-[11px]"></i>
                        {{ __($tierName . ' Plan') }}
                    </span>
                    <a href="{{ route('subscriptions.index') }}"
                        class="text-[10px] font-bold text-indigo-500 hover:text-indigo-700 tracking-wide transition-colors">
                        @if($tierSlug === 'free') {{ __('Upgrade Plan') }} &rsaquo; @else {{ __('Manage Subscription') }}
                        &rsaquo; @endif
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Quick Action Shortcuts (Organization & Independent) -->
    @if(in_array($role, ['organization', 'independent']))
        <div class="mb-8">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">{{ __('Quick Actions') }}</h3>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
                <a href="{{ route('surveys.create') }}"
                    class="group relative flex flex-col items-center justify-center p-6 bg-white rounded-lg shadow-sm border border-gray-200 hover:border-indigo-500 hover:ring-1 hover:ring-indigo-500 transition-all">
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors mb-3">
                        <i class="fa-solid fa-plus text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-900">{{ __('Create Survey') }}</span>
                </a>

                <a href="{{ route('surveys.index', ['status' => 'active']) }}"
                    class="group relative flex flex-col items-center justify-center p-6 bg-white rounded-lg shadow-sm border border-gray-200 hover:border-blue-500 hover:ring-1 hover:ring-blue-500 transition-all">
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-full bg-blue-100 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors mb-3">
                        <i class="fa-solid fa-list-check text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-900">{{ __('Manage Surveys') }}</span>
                </a>

                <a href="{{ route($role . '.responses.index') }}"
                    class="group relative flex flex-col items-center justify-center p-6 bg-white rounded-lg shadow-sm border border-gray-200 hover:border-green-500 hover:ring-1 hover:ring-green-500 transition-all">
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-full bg-green-100 text-green-600 group-hover:bg-green-600 group-hover:text-white transition-colors mb-3">
                        <i class="fa-solid fa-reply-all text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-900">{{ __('View Responses') }}</span>
                </a>

                <a href="{{ route('research-proposal.index') }}"
                    class="group relative flex flex-col items-center justify-center p-6 bg-white rounded-lg shadow-sm border border-gray-200 hover:border-purple-500 hover:ring-1 hover:ring-purple-500 transition-all">
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-full bg-purple-100 text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-colors mb-3">
                        <i class="fa-solid fa-chart-bar text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-900">{{ __('Generate Report') }}</span>
                </a>

                <a href="{{ route('surveys.public') }}"
                    class="group relative flex flex-col items-center justify-center p-6 bg-white rounded-lg shadow-sm border border-gray-200 hover:border-orange-500 hover:ring-1 hover:ring-orange-500 transition-all">
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-full bg-orange-100 text-orange-600 group-hover:bg-orange-600 group-hover:text-white transition-colors mb-3">
                        <i class="fa-solid fa-globe text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-900">{{ __('Take Surveys') }}</span>
                </a>
            </div>
        </div>
    @endif

    <!-- Quick Action Shortcuts (Respondent) -->
    @if($role === 'respondent')
        <div class="mb-8">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">{{ __('Quick Actions') }}</h3>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <a href="{{ route('surveys.public') }}"
                    class="group relative flex flex-col items-center justify-center p-6 bg-white rounded-lg shadow-sm border border-gray-200 hover:border-indigo-500 hover:ring-1 hover:ring-indigo-500 transition-all">
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors mb-3">
                        <i class="fa-solid fa-magnifying-glass text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-900">{{ __('Take Surveys') }}</span>
                </a>

                <a href="{{ route('respondent.history') }}"
                    class="group relative flex flex-col items-center justify-center p-6 bg-white rounded-lg shadow-sm border border-gray-200 hover:border-blue-500 hover:ring-1 hover:ring-blue-500 transition-all">
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-full bg-blue-100 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors mb-3">
                        <i class="fa-solid fa-clock-rotate-left text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-900">{{ __('My Responses') }}</span>
                </a>

            </div>
        </div>
    @endif

    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">{{ __('Overview Metrics') }}</h3>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- Total Surveys Widget -->
        <div
            class="bg-white overflow-hidden shadow rounded-lg border-t-4 border-indigo-400 transform hover:scale-[1.02] transition-all">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fa-solid fa-globe text-3xl text-indigo-400"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                {{ $role === 'respondent' ? __('Available Public Surveys') : __('Total Surveys Generated') }}
                            </dt>
                            <dd>
                                <div class="text-2xl font-bold text-gray-900">
                                    {{ $totalSurveys ?? 0 }}
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3 text-xs">
                @if(in_array($role, ['organization', 'independent']))
                    <a href="{{ route('surveys.index', ['status' => 'active']) }}"
                        class="font-bold text-indigo-600 hover:text-indigo-500">
                        {{ __('Manage active surveys') }} <i class="fa-solid fa-arrow-right ml-1 text-[10px]"></i></a>
                @else
                    <a href="{{ route('surveys.public') }}" class="font-bold text-indigo-600 hover:text-indigo-500">
                        {{ __('Browse all') }}
                        <i class="fa-solid fa-arrow-right ml-1 text-[10px]"></i></a>
                @endif
            </div>
        </div>

        <!-- Total Responses Widget -->
        <div
            class="bg-white overflow-hidden shadow rounded-lg border-t-4 border-green-400 transform hover:scale-[1.02] transition-all">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fa-solid fa-check-double text-3xl text-green-400"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                {{ $role === 'respondent' ? __('Completed Responses') : __('Total Responses Collected') }}
                            </dt>
                            <dd>
                                <div class="text-2xl font-bold text-gray-900">
                                    {{ number_format($totalResponses ?? 0) }}
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3 text-xs">
                @if(in_array($role, ['organization', 'independent']))
                    <a href="{{ route($role . '.responses.index') }}" class="font-bold text-green-600 hover:text-green-500">
                        {{ __('View responses') }} <i class="fa-solid fa-arrow-right ml-1 text-[10px]"></i></a>
                @else
                    <a href="{{ route('respondent.history') }}" class="font-bold text-green-600 hover:text-green-500">
                        {{ __('View history') }}
                        <i class="fa-solid fa-arrow-right ml-1 text-[10px]"></i></a>
                @endif
            </div>
        </div>

        @if($role !== 'respondent')
            <!-- Draft Surveys Widget -->
            <div
                class="bg-white overflow-hidden shadow rounded-lg border-t-4 border-yellow-400 transform hover:scale-[1.02] transition-all">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fa-regular fa-file-lines text-3xl text-yellow-500"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    {{ __('Draft Surveys') }}
                                </dt>
                                <dd>
                                    <div class="text-2xl font-bold text-gray-900">
                                        {{ $pendingSurveys ?? 0 }}
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3 text-xs">
                    <a href="{{ route('surveys.index', ['status' => 'draft']) }}"
                        class="font-bold text-yellow-600 hover:text-yellow-500">
                        {{ __('Manage drafts') }} <i class="fa-solid fa-arrow-right ml-1 text-[10px]"></i>
                    </a>
                </div>
            </div>
        @endif

        <!-- Generated Reports Widget -->
        <div
            class="bg-white overflow-hidden shadow rounded-lg border-t-4 border-purple-400 transform hover:scale-[1.02] transition-all text-center">
            <div class="p-5">
                <dl>
                    <dt class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">
                        {{ __('Reports Generated') }}</dt>
                    <dd>
                        <div class="text-3xl font-black text-gray-900">{{ $reportsGenerated ?? 0 }}</div>
                    </dd>
                </dl>
            </div>
            <div class="bg-gray-50 px-5 py-3 text-xs">
                <a href="{{ route('research-proposal.history') }}"
                    class="font-bold text-purple-600 hover:text-purple-500 uppercase tracking-tighter">
                    {{ __('Reports Gallery') }} <i class="fa-solid fa-chevron-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Bottom Content Area -->
    <div class="space-y-8 mb-12">
        <!-- Recent activity if not respondent -->
        @if($role !== 'respondent')
            <div class="bg-white shadow rounded-lg border border-gray-100 overflow-hidden">
                <div class="px-6 py-6 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        <i class="fa-solid fa-clock-rotate-left mr-2 text-indigo-500"></i> {{ __('Recent Platform Activity') }}
                    </h3>
                </div>
                <div class="p-8">
                    @if(count($recentActivity) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($recentActivity as $activity)
                                <div
                                    class="flex flex-col p-6 hover:bg-white rounded-2xl transition-all border border-gray-50 hover:border-indigo-200 hover:shadow-xl group bg-gray-50/50">
                                    <div class="flex items-center justify-between mb-4">
                                        <div
                                            class="p-3 bg-indigo-100 text-indigo-600 rounded-xl group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                            <i class="fa-solid fa-file-signature text-lg"></i>
                                        </div>
                                        <span
                                            class="text-[9px] font-black text-indigo-400 uppercase tracking-widest bg-white px-2 py-1 rounded-md border border-indigo-50 shadow-sm">{{ __($activity->status->value ?? $activity->status) }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0 mb-6">
                                        <p class="text-base font-medium text-gray-900 mb-1 leading-tight">{{ $activity->title }}</p>
                                        <p class="text-xs text-gray-400 font-medium tracking-tight">
                                            {{ $activity->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                    <div class="pt-4 border-t border-gray-100 flex justify-between items-center">
                                        <span class="text-[10px] text-gray-300 font-bold uppercase">{{ __('Activity Log') }}
                                            #{{ $activity->id }}</span>
                                        <a href="{{ route('surveys.summary', $activity) }}"
                                            class="text-[10px] font-black text-indigo-600 hover:text-indigo-800 uppercase flex items-center">
                                            {{ __('Manage') }} <i class="fa-solid fa-chevron-right ml-1 text-[8px]"></i>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-500 text-sm font-medium">{{ __('no recent activity') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    @if($role === 'respondent')
        <div class="mt-12 bg-white shadow-sm rounded-2xl border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-white">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">{{ __('Available Public Surveys') }}</h3>
                    <p class="text-xs text-gray-500">{{ __('Recently published surveys open for your participation') }}</p>
                </div>
                <a href="{{ route('surveys.public') }}"
                    class="inline-flex items-center px-4 py-2 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg hover:bg-indigo-600 hover:text-white transition-all">
                    {{ __('Explore Surveys') }} <i class="fa-solid fa-arrow-right ml-2 text-[10px]"></i>
                </a>
            </div>
            <div class="overflow-x-auto scrollbar-hide">
                @if(count($recentPublicSurveys) > 0)
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                    {{ __('Survey Title & Date') }}</th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                    {{ __('Category') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center">
                                    {{ __('Activity') }}
                                </th>
                                <th scope="col" class="relative px-6 py-4">
                                    <span class="sr-only">Action</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-50">
                            @foreach($recentPublicSurveys as $survey)
                                <tr class="hover:bg-indigo-50/20 transition-colors group">
                                    <td class="px-6 py-5">
                                        <div class="flex flex-col">
                                            <div class="text-sm font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">
                                                {{ $survey->title }}
                                            </div>
                                            <div class="text-[11px] font-medium text-gray-400">{{ __('Created') }}
                                                {{ $survey->created_at->format('M d, Y') }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100/50 uppercase tracking-tight">
                                            {{ __($survey->category->value ?? $survey->category) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap text-center text-xs text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <span class="font-bold text-gray-900">{{ number_format($survey->responses_count) }}</span>
                                            <span class="text-[10px] text-gray-400 font-medium">{{ __('Responses') }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('surveys.show', $survey) }}"
                                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-xs font-bold hover:bg-gray-900 transition-all shadow-lg shadow-indigo-100">
                                            {{ __('Take Survey') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-folder-open text-gray-300 text-2xl"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-500">
                            {{ __('No public surveys available right now. Check back later!') }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

@endsection