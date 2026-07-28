@extends('layouts.app')

@section('title')
    {{ $survey->title }} - Survey Hub
@endsection
@section('meta_robots', 'noindex, nofollow')

@section('content')
    <div class="container-fluid {{ request('reportTab') === 'analyse' ? 'p-0 overflow-hidden' : 'px-4 py-6' }}">
        @if((!isset($isSharedView) || !$isSharedView) && request('reportTab') !== 'analyse')
            <!-- Survey Header -->
            <header class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        @php
                            $role = auth()->user()->role instanceof \BackedEnum ? auth()->user()->role->value : auth()->user()->role;
                            $backRoute = $role === 'admin' ? route('admin.surveys.index', ['status' => 'active']) : route('surveys.index', ['status' => 'active']);
                        @endphp
                        <a href="{{ $backRoute }}"
                            class="text-xs font-bold text-gray-600 hover:text-[#2271b1] transition-colors">
                            <i class="fa-solid fa-chevron-left mr-1"></i> {{ __('My Surveys') }}
                        </a>
                        <span class="text-gray-300">•</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider bg-gray-100 text-gray-500">
                            {{ __($survey->category->value) }}
                        </span>
                        @if($survey->status->value === 'draft')
                            <span
                                class="px-2 py-0.5 rounded text-[10px] font-bold  tracking-wider bg-amber-100 text-amber-600 border border-amber-200">
                                {{ __('Draft') }}
                            </span>
                        @elseif($survey->status->value === 'active')
                            <span
                                class="px-2 py-0.5 rounded text-[10px] font-bold  tracking-wider bg-emerald-100 text-emerald-600 border border-emerald-200">
                                {{ __('Active') }}
                            </span>
                        @endif
                    </div>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight">{{ $survey->title }}</h1>
                </div>

                <div class="flex items-center gap-3">
                    @if($survey->status->value === 'draft' || $survey->status->value === 'pending_approval')
                        <form action="{{ route('surveys.publish', $survey) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="px-6 py-3 bg-[#2271b1] text-white rounded-xl font-black text-[12px] tracking-widest shadow-lg shadow-zinc-200/50 hover:bg-[#135e96] transition-all">
                                <i class="fa-solid fa-paper-plane mr-2"></i> {{ __('Deploy Survey') }}
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('surveys.edit', $survey) }}"
                        class="px-6 py-3 bg-white border border-gray-200 text-gray-600 rounded-xl font-black text-[12px] tracking-widest hover:bg-gray-50 transition-all">
                        <i class="fa-solid fa-pen-to-square mr-2"></i> {{ __('Edit Survey') }}
                    </a>
                </div>
            </header>

            <!-- Survey Tabs -->
            <div class="border-b border-gray-200 mb-8 relative z-30">
                <nav
                    class="flex items-center gap-1.5 sm:gap-5 -mb-px py-0.5 overflow-visible w-full justify-between sm:justify-start">
                    <a href="{{ route('surveys.summary', $survey) }}"
                        class="pb-3 sm:pb-4 px-1 sm:px-2 border-b-2 font-bold text-[11px] sm:text-sm transition-all whitespace-nowrap shrink-0 {{ request()->routeIs('surveys.summary') ? 'border-[#2271b1] text-[#135e96]' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }}">
                        <i class="fa-solid fa-chart-pie mr-1 sm:mr-2 text-[10px] sm:text-xs"></i> {{ __('Summary') }}
                    </a>

                    <a href="{{ route('surveys.data', $survey) }}"
                        class="pb-3 sm:pb-4 px-1 sm:px-2 border-b-2 font-bold text-[11px] sm:text-sm transition-all whitespace-nowrap shrink-0 {{ request()->routeIs('surveys.data') ? 'border-[#2271b1] text-[#135e96]' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }}">
                        <i class="fa-solid fa-database mr-1 sm:mr-2 text-[10px] sm:text-xs"></i> {{ __('Data') }}
                    </a>

                    <a href="{{ route('surveys.reports', $survey) }}?reportTab=analyse"
                        class="pb-3 sm:pb-4 px-1 sm:px-2 border-b-2 font-bold text-[11px] sm:text-sm transition-all whitespace-nowrap shrink-0 {{ (request()->routeIs('surveys.reports') && request('reportTab') === 'analyse') ? 'border-[#2271b1] text-[#135e96]' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }}">
                        <span>Analyze</span>
                    </a>

                    <!-- Reports Dropdown Container -->
                    <div class="relative flex shrink-0" x-data="{ open: false }" @click.outside="open = false">
                        <a href="{{ route('surveys.reports', $survey) }}" @mouseenter="open = true"
                            class="pb-3 sm:pb-4 px-1 sm:px-2 border-b-2 font-bold text-[11px] sm:text-sm transition-all whitespace-nowrap flex items-center gap-1 {{ (request()->routeIs('surveys.reports') && !in_array(request('reportTab'), ['analyse', 'humanizer'])) ? 'border-[#2271b1] text-[#135e96]' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }}">
                            <i class="fa-solid fa-file-contract text-[10px] sm:text-xs"></i> <span>{{ __('Reports') }}</span>
                            <i class="fa-solid fa-chevron-down text-[8px] transition-transform duration-200"
                                :class="open ? 'rotate-180' : ''"></i>
                        </a>
                        <!-- Dropdown Menu -->
                        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute left-0 mt-1 w-56 sm:w-60 bg-white border border-gray-100 rounded-2xl shadow-2xl shadow-gray-200/60 z-50 overflow-hidden"
                            style="display:none; top: 100%;">

                            <!-- Descriptive Section -->
                            <div class="px-4 py-2 bg-gray-50 border-b border-gray-100">
                                <span
                                    class="text-[9px] font-black text-gray-400 tracking-widest">{{ __('Descriptive Statistics') }}</span>
                            </div>
                            <div class="p-1.5 space-y-0.5">
                                <a href="{{ route('surveys.reports', $survey) }}?reportTab=quantitative"
                                    class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold text-gray-700 hover:bg-zinc-100 hover:text-[#135e96] rounded-xl transition-colors">
                                    <i class="fa-solid fa-chart-column text-zinc-400 w-4 text-center"></i>
                                    {{ __('Quantitative Report') }}
                                </a>
                                <a href="{{ route('surveys.reports', $survey) }}?reportTab=qualitative"
                                    class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold text-gray-700 hover:bg-zinc-100 hover:text-[#135e96] rounded-xl transition-colors">
                                    <i class="fa-solid fa-comments text-zinc-400 w-4 text-center"></i>
                                    {{ __('Qualitative Report') }}
                                </a>
                            </div>

                            <!-- Inferential Section -->
                            <div class="px-4 py-2 bg-gray-50 border-t border-b border-gray-100 mt-1">
                                <span
                                    class="text-[9px] font-black text-gray-400 tracking-widest">{{ __('Inferential Statistics') }}</span>
                            </div>
                            <div class="p-1.5 space-y-0.5">
                                <a href="{{ route('surveys.reports', $survey) }}?reportTab=inferential"
                                    class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold text-gray-700 hover:bg-zinc-100 hover:text-[#135e96] rounded-xl transition-colors">
                                    <i class="fa-solid fa-calculator text-zinc-400 w-4 text-center"></i>
                                    {{ __('Analyse') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    @php
                        $isMoreActive = request()->routeIs('surveys.settings', 'surveys.gallery', 'surveys.downloads', 'surveys.versions*', 'surveys.campaigns*', 'surveys.dashboard-builder') || (request()->routeIs('surveys.reports') && request('reportTab') === 'humanizer');
                    @endphp

                    <!-- 3-Dot More Dropdown Menu (Positioned Inline Next to Reports) -->
                    <div class="relative flex shrink-0" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button" @click="open = !open"
                            class="pb-3 sm:pb-4 px-1 sm:px-2 border-b-2 font-bold text-[11px] sm:text-sm transition-all whitespace-nowrap flex items-center gap-1 {{ $isMoreActive ? 'border-[#2271b1] text-[#135e96]' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }}"
                            title="{{ __('More Options') }}">
                            <i class="fa-solid fa-ellipsis-vertical text-[11px] sm:text-base"></i>
                            <span>{{ __('More') }}</span>
                        </button>

                        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 sm:left-0 mt-1 w-52 sm:w-56 bg-white border border-gray-100 rounded-2xl shadow-2xl shadow-gray-200/60 z-50 overflow-hidden p-1.5 space-y-0.5"
                            style="display:none; top: 100%;">

                            <a href="{{ route('surveys.reports', $survey) }}?reportTab=humanizer"
                                class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold rounded-xl transition-colors {{ (request()->routeIs('surveys.reports') && request('reportTab') === 'humanizer') ? 'bg-zinc-100 text-[#135e96]' : 'text-gray-700 hover:bg-zinc-50' }}">
                                <i class="fa-solid fa-wand-magic-sparkles text-cyan-600 w-4 text-center"></i>
                                {{ __('AI Humanizer') }}
                            </a>

                            <a href="{{ route('surveys.gallery', $survey) }}"
                                class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold rounded-xl transition-colors {{ request()->routeIs('surveys.gallery') ? 'bg-zinc-100 text-[#135e96]' : 'text-gray-700 hover:bg-zinc-50' }}">
                                <i class="fa-solid fa-images text-indigo-500 w-4 text-center"></i>
                                {{ __('Gallery') }}
                            </a>

                            <a href="{{ route('surveys.downloads', $survey) }}"
                                class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold rounded-xl transition-colors {{ request()->routeIs('surveys.downloads') ? 'bg-zinc-100 text-[#135e96]' : 'text-gray-700 hover:bg-zinc-50' }}">
                                <i class="fa-solid fa-download text-emerald-600 w-4 text-center"></i>
                                {{ __('Downloads') }}
                            </a>

                            <a href="{{ route('surveys.versions', $survey) }}"
                                class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold rounded-xl transition-colors {{ request()->routeIs('surveys.versions*') ? 'bg-zinc-100 text-[#135e96]' : 'text-gray-700 hover:bg-zinc-50' }}">
                                <i class="fa-solid fa-clock-rotate-left text-amber-600 w-4 text-center"></i>
                                {{ __('Version History') }}
                            </a>

                            <a href="{{ route('surveys.campaigns.index', $survey) }}"
                                class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold rounded-xl transition-colors {{ request()->routeIs('surveys.campaigns*') ? 'bg-zinc-100 text-[#135e96]' : 'text-gray-700 hover:bg-zinc-50' }}">
                                <i class="fa-solid fa-paper-plane text-blue-500 w-4 text-center"></i>
                                {{ __('Campaigns') }}
                            </a>

                            <a href="{{ route('surveys.dashboard-builder', $survey) }}"
                                class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold rounded-xl transition-colors {{ request()->routeIs('surveys.dashboard-builder') ? 'bg-zinc-100 text-[#135e96]' : 'text-gray-700 hover:bg-zinc-50' }}">
                                <i class="fa-solid fa-chart-line text-purple-600 w-4 text-center"></i>
                                {{ __('Dashboard Builder') }}
                            </a>

                            <div class="my-1 border-t border-gray-100"></div>

                            <a href="{{ route('surveys.settings', $survey) }}"
                                class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold rounded-xl transition-colors {{ request()->routeIs('surveys.settings') ? 'bg-zinc-100 text-[#135e96]' : 'text-gray-700 hover:bg-zinc-50' }}">
                                <i class="fa-solid fa-gears text-gray-500 w-4 text-center"></i>
                                {{ __('Settings') }}
                            </a>
                        </div>
                    </div>
                </nav>
            </div>
        @endif

        <!-- Tab Content -->
        <div class="animate-in fade-in slide-in-from-bottom-2 duration-500">
            @yield('survey-content')
        </div>
    </div>
@endsection