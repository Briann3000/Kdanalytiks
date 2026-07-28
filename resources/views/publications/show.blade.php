@extends('layouts.app')

@section('title', $publication['title'] . ' — KDAnalytiks Publications')

@push('styles')
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($publication['summary']), 150) }}">
    <meta property="og:title" content="{{ $publication['title'] }}">
    <meta property="og:description"
        content="{{ \Illuminate\Support\Str::limit(strip_tags($publication['summary']), 150) }}">
    <meta property="og:type" content="article">
@endpush

@section('content')
    <div class="bg-gray-50 py-16 sm:py-24 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto space-y-8">

            <!-- Back Button -->
            <div>
                <a href="{{ route('publications') }}"
                    class="inline-flex items-center gap-2 text-xs font-bold text-[#135e96] hover:underline">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i>
                    <span>{{ __('Back to Publications') }}</span>
                </a>
            </div>

            <div class="bg-white rounded-2xl p-8 sm:p-12 border border-gray-200/80 shadow-sm space-y-8">
                <!-- Publication Header -->
                <div class="space-y-4 border-b border-gray-100 pb-8">
                    <div class="flex items-center gap-3">
                        <span
                            class="px-3 py-1 bg-blue-50 text-[#135e96] rounded-full text-xs font-bold">{{ $publication['category'] }}</span>
                        <span class="text-xs text-gray-400">{{ $publication['date'] }}</span>
                        @if(!empty($publication['wp_synced']))
                            <span
                                class="px-2 py-0.5 bg-cyan-50 text-cyan-700 rounded text-[10px] font-bold border border-cyan-200">
                                <i class="fa-brands fa-wordpress mr-1"></i> WP Synced
                            </span>
                        @endif
                    </div>

                    <h1 class="text-2xl sm:text-4xl font-black text-gray-900 leading-tight">
                        {{ $publication['title'] }}
                    </h1>

                    <div class="text-xs text-gray-500 font-medium">
                        {{ __('Author: ') }} <span class="text-gray-900 font-bold">{{ $publication['author'] }}</span>
                    </div>
                </div>

                <!-- Executive Summary Box -->
                <div class="bg-slate-50 p-6 rounded-xl border border-slate-200/60 space-y-2">
                    <h3 class="text-xs font-bold text-[#135e96] uppercase tracking-wider">{{ __('Executive Summary') }}</h3>
                    <p class="text-sm text-gray-700 leading-relaxed font-medium">
                        {{ $publication['summary'] }}
                    </p>
                </div>

                <!-- Academia.edu Style PDF Paper Reader & Scroller Container -->
                @php
                    $pdfSrc = $publication['pdf_url'] ?? null;
                @endphp

                <div class="space-y-4">
                    <div class="flex items-center justify-between bg-slate-900 text-white px-6 py-4 rounded-2xl shadow-md">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-9 h-9 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center font-bold">
                                <i class="fa-solid fa-file-pdf text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold tracking-tight text-white">
                                    {{ __('Academia Research Paper Viewer') }}
                                </h3>
                                <p class="text-[11px] text-slate-400 font-medium">{{ __('Empirical PDF Document Reader') }}
                                </p>
                            </div>
                        </div>

                        @if($pdfSrc)
                            <div class="flex items-center gap-2">
                                <a href="{{ asset($pdfSrc) }}" target="_blank" download
                                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow flex items-center gap-1.5">
                                    <i class="fa-solid fa-download text-xs"></i>
                                    <span>{{ __('Download PDF') }}</span>
                                </a>
                                <a href="{{ asset($pdfSrc) }}" target="_blank"
                                    class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-bold transition-all flex items-center gap-1">
                                    <i class="fa-solid fa-[#2271b1] fa-expand text-xs"></i>
                                    <span class="hidden sm:inline">{{ __('Fullscreen') }}</span>
                                </a>
                            </div>
                        @endif
                    </div>

                    @if($pdfSrc)
                        <!-- PDF Viewport Scroller Container -->
                        <div
                            class="relative w-full rounded-2xl overflow-hidden border border-gray-200 bg-slate-100 shadow-inner">
                            <iframe src="{{ asset($pdfSrc) }}#toolbar=1&navpanes=0"
                                class="w-full h-[650px] sm:h-[750px] border-none rounded-2xl bg-white"
                                title="Research Paper Viewer"></iframe>
                        </div>
                    @else
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-8 text-center space-y-3">
                            <i class="fa-solid fa-book-open-reader text-3xl text-slate-400"></i>
                            <h4 class="text-sm font-bold text-gray-900">{{ __('Attached Research Document') }}</h4>
                            <p class="text-xs text-gray-600 max-w-xl mx-auto leading-relaxed">
                                {{ $publication['content'] ?? $publication['summary'] }}
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Dataset / Public Survey Action Box -->
                <div class="pt-8 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div>
                        <h4 class="text-base font-bold text-gray-900">{{ __('Empirical Dataset & Survey Link') }}</h4>
                        <p class="text-xs text-gray-500">
                            {{ __('Access open data collection instruments, raw response models, and public survey forms associated with this research.') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        @auth
                            @php
                                $userRoleShow = is_object(auth()->user()->role) ? auth()->user()->role->value : auth()->user()->role;
                            @endphp
                            @if($userRoleShow === 'admin' || auth()->user()->name === ($publication['author'] ?? ''))
                                <form method="POST" action="{{ route('publications.destroy', $publication['id']) }}"
                                    onsubmit="return confirm('{{ __('Are you sure you want to delete this publication?') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="px-5 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs rounded-xl border border-rose-200 transition-colors">
                                        <i class="fa-solid fa-trash-can mr-1"></i> {{ __('Delete Publication') }}
                                    </button>
                                </form>
                            @endif
                        @endauth
                        <a href="{{ route('surveys.public') }}"
                            class="px-6 py-2.5 bg-[#2271b1] hover:bg-[#135e96] text-white text-xs font-bold rounded-xl shadow transition-all">
                            {{ __('View Public Datasets') }}
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection