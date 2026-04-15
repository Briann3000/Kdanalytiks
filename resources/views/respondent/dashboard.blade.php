@extends('layouts.app')

@section('title', 'Respondent Dashboard')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
        <div class="px-6 py-6 sm:px-8 flex justify-between items-center bg-indigo-50 border-l-8 border-indigo-600">
            <div>
                <h3 class="text-2xl leading-none font-black text-gray-900 mb-1">
                    Welcome back, {{ auth()->user()->name }}
                </h3>
                <p class="text-sm text-gray-600 font-bold uppercase tracking-widest">
                    RESPONDENT DASHBOARD &bull; <span class="text-green-600">SYSTEM ONLINE</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Quick Action Shortcuts -->
    <div class="mb-8">
        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <a href="{{ route('surveys.public') }}"
                class="group relative flex flex-col items-center justify-center p-6 bg-white rounded-lg shadow-sm border border-gray-200 hover:border-indigo-500 hover:ring-1 hover:ring-indigo-500 transition-all">
                <div class="w-12 h-12 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors mb-3">
                    <i class="fa-solid fa-magnifying-glass text-xl"></i>
                </div>
                <span class="text-sm font-medium text-gray-900">Take Surveys</span>
            </a>

            <a href="{{ route('respondent.history') }}"
                class="group relative flex flex-col items-center justify-center p-6 bg-white rounded-lg shadow-sm border border-gray-200 hover:border-blue-500 hover:ring-1 hover:ring-blue-500 transition-all">
                <div class="w-12 h-12 flex items-center justify-center rounded-full bg-blue-100 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors mb-3">
                    <i class="fa-solid fa-clock-rotate-left text-xl"></i>
                </div>
                <span class="text-sm font-medium text-gray-900">My Responses</span>
            </a>

            <a href="{{ route('research-proposal.index') }}"
                class="group relative flex flex-col items-center justify-center p-6 bg-white rounded-lg shadow-sm border border-gray-200 hover:border-purple-500 hover:ring-1 hover:ring-purple-500 transition-all">
                <div class="w-12 h-12 flex items-center justify-center rounded-full bg-purple-100 text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-colors mb-3">
                    <i class="fa-solid fa-graduation-cap text-xl"></i>
                </div>
                <span class="text-sm font-medium text-gray-900">Research Studio</span>
            </a>

        </div>
    </div>

    <!-- Overview Metrics -->
    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Overview Metrics</h3>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- Wallet Balance -->
        <div class="bg-indigo-600 overflow-hidden shadow-lg rounded-2xl border-none transform hover:scale-[1.02] transition-all group relative">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform"></div>
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-white/20 p-3 rounded-xl backdrop-blur-sm">
                        <i class="fa-solid fa-wallet text-2xl text-white"></i>
                    </div>
                    <div class="ml-4 flex-1">
                        <dt class="text-[10px] font-black text-indigo-100 uppercase tracking-widest mb-1">Wallet Balance</dt>
                        <dd class="flex items-baseline">
                            <div class="text-2xl font-black text-white">
                                <span class="text-sm font-medium mr-1">{{ $wallet->currency ?? 'KES' }}</span>{{ number_format((float)$wallet->balance, 2) }}
                            </div>
                        </dd>
                    </div>
                </div>
            </div>
            <div class="bg-black/10 px-6 py-3 text-xs flex justify-between items-center group-hover:bg-black/20 transition-colors">
                <a href="{{ route('wallet.index') }}" class="font-black text-white uppercase tracking-tighter flex items-center">
                    Withdraw <i class="fa-solid fa-arrow-right ml-1 text-[10px]"></i>
                </a>
                <span class="text-[9px] text-white/60 font-medium tracking-wide">Sync: Real-time</span>
            </div>
        </div>
        <!-- Available Surveys -->
        <div class="bg-white overflow-hidden shadow rounded-lg border-t-4 border-indigo-400 transform hover:scale-[1.02] transition-all">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fa-solid fa-globe text-3xl text-indigo-400"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Available Public Surveys</dt>
                            <dd><div class="text-2xl font-bold text-gray-900">{{ count($availableSurveys) }}</div></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3 text-xs">
                <a href="{{ route('surveys.public') }}" class="font-bold text-indigo-600 hover:text-indigo-500 uppercase tracking-tighter">
                    Browse all <i class="fa-solid fa-arrow-right ml-1 text-[10px]"></i>
                </a>
            </div>
        </div>

        <!-- Completed Responses -->
        <div class="bg-white overflow-hidden shadow rounded-lg border-t-4 border-green-400 transform hover:scale-[1.02] transition-all">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fa-solid fa-check-double text-3xl text-green-400"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Completed Responses</dt>
                            <dd><div class="text-2xl font-bold text-gray-900">{{ count($responses) }}</div></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3 text-xs">
                <a href="{{ route('respondent.history') }}" class="font-bold text-green-600 hover:text-green-500 uppercase tracking-tighter">
                    View history <i class="fa-solid fa-arrow-right ml-1 text-[10px]"></i>
                </a>
            </div>
        </div>

        <!-- Studio Reports -->
        <div class="bg-white overflow-hidden shadow rounded-lg border-t-4 border-purple-400 transform hover:scale-[1.02] transition-all">
            <div class="p-5 text-center">
                <dl>
                    <dt class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Reports Generated</dt>
                    <dd><div class="text-3xl font-black text-gray-900">{{ $reportsGenerated }}</div></dd>
                </dl>
            </div>
            <div class="bg-gray-50 px-5 py-3 text-xs text-center uppercase tracking-tighter">
                <a href="{{ route('research-proposal.history') }}" class="font-bold text-purple-600 hover:text-purple-500">
                    My Reports <i class="fa-solid fa-chevron-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Activity Feed -->
    <div class="bg-white shadow rounded-lg border border-gray-100 overflow-hidden">
        <div class="px-6 py-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/30">
            <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight flex items-center">
                <i class="fa-solid fa-clock-rotate-left mr-3 text-indigo-500"></i> Recent Activity
            </h3>
        </div>
        <div class="p-6">
            @if(count($responses) > 0)
                <div class="space-y-4">
                    @foreach(($responses instanceof \Illuminate\Support\Collection ? $responses : collect($responses))->take(5) as $response)
                        <div class="flex items-center justify-between p-4 hover:bg-indigo-50/30 rounded-xl border border-gray-50 transition-all">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-green-100 text-green-600 rounded-lg">
                                    <i class="fa-solid fa-check"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ $response->survey->title ?? 'Deleted Survey' }}</p>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">
                                        Completed {{ $response->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            <span class="text-[10px] px-3 py-1 bg-gray-100 text-gray-500 rounded-full font-black uppercase tracking-widest border border-gray-200">
                                {{ $response->survey->category ?? 'General' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fa-solid fa-receipt text-gray-200 text-5xl mb-4"></i>
                    <p class="text-gray-400 font-bold uppercase tracking-widest">No activity recorded yet</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection