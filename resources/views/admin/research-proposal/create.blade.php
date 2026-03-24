@extends('layouts.app')

@section('title', 'Draft New Report - Research Studio')

@section('content')
<div class="container-fluid px-4 md:px-8 py-8">
    <div class="max-w-full mx-auto">
        <header class="mb-8 flex items-center justify-between">
            <div>
                <a href="{{ route('research-proposal.index') }}" class="inline-flex items-center text-xs font-bold text-gray-400 hover:text-indigo-600 mb-2 transition-colors uppercase tracking-widest">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Back to Studio
                </a>
                <h1 class="text-3xl font-black text-gray-900 tracking-tight uppercase">Draft New Report</h1>
                <p class="text-gray-500 font-medium">Define your research vision and let AI draft the formal documentation.</p>
            </div>
            <div class="hidden md:block">
                <div class="w-16 h-16 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 shadow-sm border border-indigo-100">
                    <i class="fa-solid fa-pen-nib text-2xl"></i>
                </div>
            </div>
        </header>

        <form action="{{ route('research-proposal.store') }}" method="POST" class="space-y-8" id="proposalForm" x-data="{ loading: false }">
            @csrf
            
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 space-y-8">
                <!-- Project Core -->
                <div class="grid grid-cols-1 gap-6 border-b border-gray-50 pb-8">
                    <div>
                        <label for="title" class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2">Report Title</label>
                        <input type="text" name="title" id="title" required
                            class="w-full bg-gray-50 border-gray-100 rounded-2xl px-5 py-4 text-gray-900 font-bold placeholder-gray-300 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                            placeholder="e.g. Socio-Economic Impact of Remote Work in Urban Environments">
                    </div>

                    <div>
                        <label for="research_question" class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2">Primary Research Question</label>
                        <textarea name="research_question" id="research_question" rows="3" required
                            class="w-full bg-gray-50 border-gray-100 rounded-2xl px-5 py-4 text-gray-900 font-medium placeholder-gray-300 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                            placeholder="To what extent does..."></textarea>
                    </div>
                </div>

                <!-- Objectives & Scope -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 border-b border-gray-50 pb-8">
                    <div>
                        <label for="objectives" class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2">Objectives & Scope</label>
                        <textarea name="objectives" id="objectives" rows="5" required
                            class="w-full bg-gray-50 border-gray-100 rounded-2xl px-5 py-4 text-gray-900 font-medium placeholder-gray-300 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                            placeholder="1. Analyze...\n2. Evaluate...\n3. Propose..."></textarea>
                        <p class="mt-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest">List 3-5 clear objectives.</p>
                    </div>
                    <div>
                        <label for="scope" class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2">Target Population / Context</label>
                        <textarea name="scope" id="scope" rows="5"
                            class="w-full bg-gray-50 border-gray-100 rounded-2xl px-5 py-4 text-gray-900 font-medium placeholder-gray-300 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                            placeholder="Define the geographic or demographic boundaries..."></textarea>
                    </div>
                </div>

                <!-- Settings -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label for="methodology_type" class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2">Proposed Methodology</label>
                        <div class="grid grid-cols-1 gap-4">
                            @php
                                $methodologies = [
                                    'survey' => [
                                        'label' => 'Survey-Based',
                                        'desc' => 'Uses standardized questionnaires to identify statistical patterns across large populations.'
                                    ],
                                    'qualitative' => [
                                        'label' => 'In-depth Qualitative',
                                        'desc' => 'Explores nuanced human experiences through detailed interviews and open responses.'
                                    ],
                                    'mixed' => [
                                        'label' => 'Mixed Methods',
                                        'desc' => 'Combines quantitative breadth and qualitative depth for a multi-dimensional view.'
                                    ]
                                ];
                            @endphp
                            @foreach($methodologies as $val => $data)
                            <label class="relative flex items-start p-5 rounded-2xl border-2 border-gray-100 cursor-pointer hover:bg-gray-50 transition-all has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50/30 group">
                                <input type="radio" name="methodology_type" value="{{ $val }}" required class="hidden" {{ $loop->first ? 'checked' : '' }}>
                                <div class="flex-1 pr-4">
                                    <p class="text-[11px] font-black text-gray-900 group-hover:text-indigo-600 transition-colors uppercase tracking-widest mb-1">{{ $data['label'] }}</p>
                                    <p class="text-[10px] text-gray-400 font-bold leading-relaxed line-clamp-2 uppercase italic">{{ $data['desc'] }}</p>
                                </div>
                                <div class="w-5 h-5 mt-1 rounded-full border-2 border-gray-200 flex items-center justify-center p-1 group-hover:border-indigo-300 transition-all">
                                    <div class="w-full h-full bg-indigo-500 rounded-full scale-0 transition-transform duration-200 peer-checked:scale-100"></div>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label for="style" class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2">Academic Standard</label>
                        <select name="style" id="style" class="w-full bg-gray-50 border-gray-100 rounded-2xl px-5 py-4 text-gray-900 font-black uppercase tracking-widest focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                            <option value="apa7">APA 7th Edition</option>
                            <option value="mla9">MLA 9th Edition</option>
                            <option value="harvard">Harvard Style</option>
                        </select>
                        
                        <div class="mt-8 p-6 bg-indigo-600 rounded-2xl text-white shadow-xl shadow-indigo-100 relative overflow-hidden">
                            <div class="relative z-10">
                                <h4 class="text-xs font-black uppercase tracking-widest mb-1">AI Synthesis Engine</h4>
                                <p class="text-[10px] text-indigo-100 font-bold opacity-80 uppercase leading-relaxed">The generator will produce Intro, Problem Statement, Methodology, and Timeline sections automatically.</p>
                            </div>
                            <i class="fa-solid fa-sparkles absolute right-2 bottom-2 text-white/10 text-4xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-4">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest max-w-xs">
                    By proceeding, the synthesis engine will analyze your inputs and generate a structured draft. This can take up to 60 seconds.
                </p>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('research-proposal.index') }}" class="px-8 py-4 rounded-2xl text-xs font-black text-gray-500 uppercase tracking-widest hover:bg-gray-100 transition-all">Cancel</a>
                    <button type="submit" @click="loading = true" class="w-full md:w-auto px-10 py-4 bg-indigo-600 text-white rounded-2xl font-black text-xs uppercase tracking-[0.2em] shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center justify-center border-none group">
                        <template x-if="!loading">
                            <div class="flex items-center justify-center w-full">
                                <span class="mr-3">Draft Reports</span>
                                <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                            </div>
                        </template>
                        <template x-if="loading">
                            <div class="flex items-center">
                                <i class="fa-solid fa-circle-notch animate-spin mr-3"></i>
                                <span>Generating...</span>
                            </div>
                        </template>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    input:checked + span + div + div .bg-indigo-500 {
        transform: scale(1);
    }
    label:has(input:checked) {
        border-color: #4f46e5;
        background-color: #f5f3ff;
    }
</style>
@endsection
