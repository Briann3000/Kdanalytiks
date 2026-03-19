@extends('layouts.app')

@section('title', 'Create New Project - Choice')

@section('content')
<div class="container-fluid px-4 py-8 min-h-[80vh] flex flex-col items-center justify-center">
    <div class="max-w-5xl w-full">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-black text-gray-900 tracking-tight uppercase mb-4">Initialize New Project</h1>
            <p class="text-gray-500 font-medium text-lg">Select a research framework to begin building your survey.</p>
        </div>

        <form action="{{ route('surveys.initialize') }}" method="POST" id="categoryForm">
            @csrf
            
            <div class="mb-8">
                <label for="title" class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Project Name</label>
                <input type="text" name="title" id="title" required
                    class="w-full bg-white border-gray-200 rounded-2xl px-6 py-5 text-xl font-bold text-gray-900 placeholder-gray-300 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all shadow-sm"
                    placeholder="Enter a descriptive title for your project...">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @php
                    $categories = [
                        'academic' => [
                            'icon' => 'fa-graduation-cap',
                            'label' => 'Academic / Scientific',
                            'desc' => 'Thematic research focusing on academic rigor, hypotheses, and peer-reviewed standards.',
                            'color' => 'indigo'
                        ],
                        'polls' => [
                            'icon' => 'fa-square-poll-vertical',
                            'label' => 'Standard Polls',
                            'desc' => 'Quick sentiment tracking or public opinion voting on specific, single-focused topics.',
                            'color' => 'blue'
                        ],
                        'market_research' => [
                            'icon' => 'fa-chart-line',
                            'label' => 'Market Research',
                            'desc' => 'Consumer behavior analysis, brand positioning, and competitive market landscape.',
                            'color' => 'emerald'
                        ],
                        'feasibility' => [
                            'icon' => 'fa-vial',
                            'label' => 'Feasibility Study',
                            'desc' => 'Assessing viability of new projects, business ideas, or technical implementations.',
                            'color' => 'amber'
                        ],
                        'social' => [
                            'icon' => 'fa-people-group',
                            'label' => 'Social Research',
                            'desc' => 'NGO impact assessments, community needs, and sociological behavioral studies.',
                            'color' => 'rose'
                        ],
                        'business' => [
                            'icon' => 'fa-briefcase',
                            'label' => 'Business Operations',
                            'desc' => 'Employee NPS, operational efficiency reviews, and internal organizational feedback.',
                            'color' => 'slate'
                        ],
                    ];
                @endphp

                @foreach($categories as $key => $cat)
                <label class="relative group cursor-pointer">
                    <input type="radio" name="category" value="{{ $key }}" class="peer hidden" {{ $loop->first ? 'checked' : '' }}>
                    <div class="h-full bg-white border-2 border-gray-100 rounded-3xl p-6 transition-all duration-300 hover:border-indigo-500 hover:shadow-xl hover:shadow-indigo-500/5 peer-checked:border-indigo-600 peer-checked:bg-indigo-50/20 peer-checked:ring-4 peer-checked:ring-indigo-600/5 flex flex-col">
                        <div class="w-14 h-14 rounded-2xl bg-{{ $cat['color'] }}-50 flex items-center justify-center text-{{ $cat['color'] }}-600 mb-6 group-hover:scale-110 transition-transform shadow-sm border border-{{ $cat['color'] }}-100">
                            <i class="fa-solid {{ $cat['icon'] }} text-2xl"></i>
                        </div>
                        <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest mb-2">{{ $cat['label'] }}</h3>
                        <p class="text-xs text-gray-400 font-bold leading-relaxed line-clamp-3 italic">{{ $cat['desc'] }}</p>
                        
                        <div class="mt-auto pt-6 flex justify-end opacity-0 group-hover:opacity-100 peer-checked:opacity-100 transition-opacity">
                            <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-200">
                                <i class="fa-solid fa-check text-[10px]"></i>
                            </div>
                        </div>
                    </div>
                </label>
                @endforeach
            </div>

            <div class="mt-12 flex justify-center">
                <button type="submit" class="px-12 py-5 bg-indigo-600 text-white rounded-2xl font-black text-xs uppercase tracking-[0.3em] shadow-2xl shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-1 transition-all flex items-center group">
                    Start Customizing Builder
                    <i class="fa-solid fa-arrow-right ml-4 group-hover:translate-x-2 transition-transform"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Safelist tailwind colors for the looped categories */
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

    .bg-slate-50 { background-color: #f8fafc; }
    .text-slate-600 { color: #475569; }
    .border-slate-100 { border-color: #f1f5f9; }
</style>
@endsection
