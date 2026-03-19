@extends('layouts.app')

@section('title', 'Project Hub - Browse by Category')

@section('content')
<div class="container-fluid px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between mb-12">
            <div>
                <h1 class="text-3xl font-black text-gray-900 tracking-tight uppercase">Project Hub</h1>
                <p class="text-gray-500 font-medium text-lg mt-1">Select a category to view your active research projects.</p>
            </div>
            <div class="flex gap-4">
                <a href="{{ route('projects.active') }}" class="px-6 py-3 bg-white border border-gray-200 text-gray-700 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-sm hover:bg-gray-50 transition-all flex items-center">
                    <i class="fa-solid fa-list mr-2"></i> All Projects
                </a>
                <a href="{{ route('surveys.create') }}" class="px-6 py-3 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center">
                    <i class="fa-solid fa-plus mr-2"></i> New Project
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @php
                $categories = [
                    'academic' => [
                        'icon' => 'fa-graduation-cap',
                        'label' => 'Academic / Scientific',
                        'desc' => 'Thematic research focusing on academic rigor and peer-reviewed standards.',
                        'color' => 'indigo'
                    ],
                    'polls' => [
                        'icon' => 'fa-square-poll-vertical',
                        'label' => 'Standard Polls',
                        'desc' => 'Quick sentiment tracking or public opinion voting on specific topics.',
                        'color' => 'blue'
                    ],
                    'market_research' => [
                        'icon' => 'fa-chart-line',
                        'label' => 'Market Research',
                        'desc' => 'Consumer behavior analysis, brand positioning, and market landscape.',
                        'color' => 'emerald'
                    ],
                    'feasibility' => [
                        'icon' => 'fa-vial',
                        'label' => 'Feasibility Study',
                        'desc' => 'Assessing viability of new projects or technical implementations.',
                        'color' => 'amber'
                    ],
                    'social' => [
                        'icon' => 'fa-people-group',
                        'label' => 'Social Research',
                        'desc' => 'NGO impact assessments and community needs studies.',
                        'color' => 'rose'
                    ],
                ];
            @endphp

            @foreach($categories as $key => $cat)
            <a href="{{ route('projects.active', ['category' => $key]) }}" class="group relative bg-white border-2 border-gray-100 rounded-3xl p-8 transition-all duration-300 hover:border-indigo-500 hover:shadow-xl hover:shadow-indigo-500/5 flex flex-col">
                <div class="w-16 h-16 rounded-2xl bg-{{ $cat['color'] }}-50 flex items-center justify-center text-{{ $cat['color'] }}-600 mb-6 group-hover:scale-110 transition-transform shadow-sm border border-{{ $cat['color'] }}-100">
                    <i class="fa-solid {{ $cat['icon'] }} text-3xl"></i>
                </div>
                <h3 class="text-lg font-black text-gray-900 uppercase tracking-widest mb-3">{{ $cat['label'] }}</h3>
                <p class="text-sm text-gray-400 font-bold leading-relaxed line-clamp-3 italic">{{ $cat['desc'] }}</p>
                
                <div class="mt-8 pt-6 border-t border-gray-50 flex justify-between items-center group-hover:border-indigo-100 transition-colors">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest group-hover:text-indigo-600 transition-colors">View Projects</span>
                    <div class="w-10 h-10 rounded-full bg-gray-50 text-gray-400 flex items-center justify-center group-hover:bg-indigo-600 group-hover:text-white transition-all shadow-sm">
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</div>

<style>
    /* Safelist tailwind colors */
    .bg-indigo-50 { background-color: #f5f3ff; }
    .text-indigo-600 { color: #4f46e5; }
    .border-indigo-100 { border-color: #e0e7ff; }

    .bg-blue-50 { background-color: #eff6ff; }
    .text-blue-600 { color: #2563eb; }
    .border-blue-100 { border-color: #dbeafe; }

    .bg-emerald-50 { background-color: #ecfdf5; }
    .text-emerald-600 { color: #059669; }
    .border-emerald-100 { border-color: #d1fae5; }

    .bg-amber-50 { background-color: #fffbeb; }
    .text-amber-600 { color: #d97706; }
    .border-amber-100 { border-color: #fef3c7; }

    .bg-rose-50 { background-color: #fff1f2; }
    .text-rose-600 { color: #e11d48; }
    .border-rose-100 { border-color: #ffe4e6; }
</style>
@endsection
