@extends('layouts.app')

@section('title')
    {{ $survey->title }} - Project Hub
@endsection

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Project Header -->
    <header class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('projects.active') }}" class="text-xs font-bold text-gray-600 hover:text-indigo-600 transition-colors">
                    <i class="fa-solid fa-chevron-left mr-1"></i> My Projects
                </a>
                <span class="text-gray-300">•</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-gray-100 text-gray-500">
                    {{ $survey->category->value }}
                </span>
                @if($survey->status->value === 'draft')
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-600 border border-amber-200">
                        Draft
                    </span>
                @elseif($survey->status->value === 'active')
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-600 border border-emerald-200">
                        Active
                    </span>
                @endif
            </div>
            <h1 class="text-3xl font-black text-gray-900 tracking-tight">{{ $survey->title }}</h1>
        </div>

        <div class="flex items-center gap-3">
             @if($survey->status->value === 'draft' || $survey->status->value === 'pending_approval')
                <form action="{{ route('projects.publish', $survey) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all">
                        <i class="fa-solid fa-paper-plane mr-2"></i> Deploy Project
                    </button>
                </form>
             @endif
             <a href="{{ route('surveys.edit', $survey) }}" class="px-6 py-3 bg-white border border-gray-200 text-gray-600 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-gray-50 transition-all">
                <i class="fa-solid fa-pen-to-square mr-2"></i> Edit Survey
             </a>
        </div>
    </header>

    <!-- Project Tabs -->
    <div class="border-b border-gray-200 mb-8">
        <nav class="flex flex-wrap gap-x-6 gap-y-2 -mb-px">
            <a href="{{ route('projects.summary', $survey) }}" 
               class="pb-4 px-1 border-b-2 font-bold text-sm transition-all {{ request()->routeIs('projects.summary') ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-600 hover:text-gray-600 hover:border-gray-300' }}">
                <i class="fa-solid fa-chart-pie mr-2"></i> Summary
            </a>
            <a href="{{ route('projects.data', $survey) }}" 
               class="pb-4 px-1 border-b-2 font-bold text-sm transition-all {{ request()->routeIs('projects.data') ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-600 hover:text-gray-600 hover:border-gray-300' }}">
                <i class="fa-solid fa-database mr-2"></i> Data
            </a>
            <a href="{{ route('projects.reports', $survey) }}" 
               class="pb-4 px-1 border-b-2 font-bold text-sm transition-all {{ request()->routeIs('projects.reports') ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-600 hover:text-gray-600 hover:border-gray-300' }}">
                <i class="fa-solid fa-file-contract mr-2"></i> Reports
            </a>
            <a href="{{ route('projects.gallery', $survey) }}" 
               class="pb-4 px-1 border-b-2 font-bold text-sm transition-all {{ request()->routeIs('projects.gallery') ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-600 hover:text-gray-600 hover:border-gray-300' }}">
                <i class="fa-solid fa-images mr-2"></i> Gallery
            </a>
            <a href="{{ route('projects.downloads', $survey) }}" 
               class="pb-4 px-1 border-b-2 font-bold text-sm transition-all {{ request()->routeIs('projects.downloads') ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-600 hover:text-gray-600 hover:border-gray-300' }}">
                <i class="fa-solid fa-download mr-2"></i> Downloads
            </a>
            <a href="{{ route('projects.settings', $survey) }}" 
               class="pb-4 px-1 border-b-2 font-bold text-sm transition-all {{ request()->routeIs('projects.settings') ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-600 hover:text-gray-600 hover:border-gray-300' }}">
                <i class="fa-solid fa-gears mr-2"></i> Settings
            </a>
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="animate-in fade-in slide-in-from-bottom-2 duration-500">
        @yield('project-content')
    </div>
</div>
@endsection
