@extends('layouts.app')

@section('title', __('Plagiarism Checker — KDAnalytiks'))
@section('meta_robots', 'noindex, nofollow')

@section('content')
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Header & Action Row -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-200 pb-5">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ __('Plagiarism Checker') }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ __('Verify manuscript originality, detect overlapping literature, and evaluate AI probability.') }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                @if($canScan)
                    <a href="{{ route('plagiarism.create') }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#2271b1] hover:bg-[#135e96] text-white text-xs font-semibold rounded-lg shadow-sm transition-all">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>{{ __('New Originality Scan') }}</span>
                    </a>
                @else
                    <a href="{{ route('subscriptions.index') }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-900 hover:bg-black text-white text-xs font-semibold rounded-lg shadow-sm transition-all">
                        <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                        <span>{{ __('Upgrade to Scan More') }}</span>
                    </a>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div
                class="p-4 rounded-lg bg-green-50 border border-green-200 text-green-800 text-xs font-medium flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-green-600"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div
                class="p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-xs font-medium flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-red-600"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Quota & Subscription Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm space-y-1">
                <span class="text-xs font-semibold text-gray-500">{{ __('Current Subscription Plan') }}</span>
                <div class="flex items-center justify-between">
                    <span class="text-lg font-bold text-gray-900 capitalize">{{ $tierSlug }} {{ __('Tier') }}</span>
                    <span class="text-xs px-2.5 py-1 rounded-md bg-gray-100 text-gray-700 font-medium">
                        {{ ucfirst($tierSlug) }}
                    </span>
                </div>
                <p class="text-xs text-gray-400 pt-1">
                    {{ __('Word limit: :count words per scan', ['count' => number_format($wordLimit)]) }}</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm space-y-1">
                <span class="text-xs font-semibold text-gray-500">{{ __('Remaining Scan Quota') }}</span>
                <div class="flex items-center justify-between">
                    <span class="text-lg font-bold text-gray-900">
                        {{ is_numeric($remainingScans) ? number_format($remainingScans) . ' ' . __('Scans Left') : __('Unlimited Scans') }}
                    </span>
                    <i class="fa-solid fa-gauge-high text-gray-400"></i>
                </div>
                <p class="text-xs text-gray-400 pt-1">
                    @if($tierSlug === 'free')
                        {{ __('Free tier includes 3 trial scans total.') }}
                    @elseif($tierSlug === 'pro')
                        {{ __('Pro tier includes 15 scans per billing cycle.') }}
                    @else
                        {{ __('Enterprise tier includes unlimited shared scans.') }}
                    @endif
                </p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm space-y-1">
                <span class="text-xs font-semibold text-gray-500">{{ __('Total Scans Executed') }}</span>
                <div class="flex items-center justify-between">
                    <span class="text-lg font-bold text-gray-900">{{ number_format($scans->total()) }}
                        {{ __('Documents') }}</span>
                    <i class="fa-solid fa-file-lines text-gray-400"></i>
                </div>
                <p class="text-xs text-gray-400 pt-1">{{ __('Archived in your secure workspace') }}</p>
            </div>
        </div>

        <!-- Scan History List -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div
                class="p-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-gray-50/50">
                <h2 class="text-sm font-bold text-gray-800">{{ __('Recent Manuscript Scans') }}</h2>
                <form method="GET" action="{{ route('plagiarism.index') }}" class="relative w-full sm:w-64">
                    <i
                        class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="{{ __('Search By Title...') }}"
                        class="w-full pl-8 pr-3 py-1.5 bg-white border border-gray-200 rounded-lg text-xs text-gray-900 focus:ring-1 focus:ring-[#2271b1] focus:border-[#2271b1]">
                </form>
            </div>

            @if($scans->count() > 0)
                <div class="divide-y divide-gray-100">
                    @foreach($scans as $scan)
                        <div
                            class="p-4 sm:px-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 hover:bg-gray-50/60 transition-colors">
                            <div class="space-y-1 min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('plagiarism.show', $scan) }}"
                                        class="text-sm font-semibold text-gray-900 hover:text-[#2271b1] truncate">
                                        {{ $scan->title }}
                                    </a>
                                    <span
                                        class="text-[11px] px-2 py-0.5 rounded bg-gray-100 text-gray-600 font-medium uppercase tracking-wider">
                                        {{ $scan->file_type }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                                    <span>{{ number_format($scan->word_count) }} {{ __('Words') }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $scan->created_at->format('M d, Y · H:i') }}</span>
                                    @if($scan->original_filename)
                                        <span>&bull;</span>
                                        <span class="truncate max-w-xs text-gray-400">{{ $scan->original_filename }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <!-- Similarity Metric -->
                                <div class="text-right">
                                    <div
                                        class="text-xs font-bold {{ $scan->similarity_percentage > 25 ? 'text-red-700' : ($scan->similarity_percentage > 12 ? 'text-amber-700' : 'text-gray-900') }}">
                                        {{ number_format($scan->similarity_percentage, 1) }}%
                                    </div>
                                    <span class="text-[10px] text-gray-400">{{ __('Similarity') }}</span>
                                </div>

                                <!-- AI Content Metric -->
                                <div class="text-right pl-3 border-l border-gray-200">
                                    <div class="text-xs font-bold text-gray-700">
                                        {{ number_format($scan->ai_percentage, 1) }}%
                                    </div>
                                    <span class="text-[10px] text-gray-400">{{ __('AI Probability') }}</span>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-2 pl-2">
                                    <a href="{{ route('plagiarism.show', $scan) }}"
                                        class="px-3 py-1.5 bg-white border border-gray-200 text-gray-700 hover:text-[#2271b1] hover:border-gray-300 text-xs font-semibold rounded-lg shadow-sm transition-all"
                                        title="{{ __('View Diagnostic Report') }}">
                                        {{ __('View Report') }}
                                    </a>

                                    <a href="{{ route('plagiarism.pdf', $scan) }}"
                                        class="p-1.5 text-gray-400 hover:text-gray-700 rounded-lg transition-colors"
                                        title="{{ __('Download PDF Certificate') }}">
                                        <i class="fa-solid fa-file-pdf"></i>
                                    </a>

                                    <form method="POST" action="{{ route('plagiarism.destroy', $scan) }}"
                                        onsubmit="return confirm('Delete this scan report?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg transition-colors"
                                            title="{{ __('Delete Scan') }}">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($scans->hasPages())
                    <div class="p-4 border-t border-gray-200 bg-gray-50/50">
                        {{ $scans->links() }}
                    </div>
                @endif
            @else
                <div class="p-12 text-center space-y-3">
                    <div
                        class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 mx-auto flex items-center justify-center text-lg">
                        <i class="fa-solid fa-file-circle-check"></i>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800">{{ __('No Scans Found') }}</h3>
                    <p class="text-xs text-gray-500 max-w-sm mx-auto leading-relaxed">
                        {{ __('Upload your research proposal, journal draft, or thesis chapter to perform your first originality check.') }}
                    </p>
                    @if($canScan)
                        <div class="pt-2">
                            <a href="{{ route('plagiarism.create') }}"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-[#2271b1] hover:bg-[#135e96] text-white text-xs font-semibold rounded-lg shadow-sm transition-all">
                                <i class="fa-solid fa-plus text-xs"></i>
                                <span>{{ __('Start First Scan') }}</span>
                            </a>
                        </div>
                    @endif
                </div>
            @endif
        </div>

    </div>
@endsection