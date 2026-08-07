@extends('layouts.app')

@section('title', __('Research Proposal Studio — KDAnalytiks'))

@section('content')
    <div class="container-fluid px-4 md:px-8 py-8" x-data="{ loading: false, budgetItems: [{ item: '', cost: '' }] }">
        <div class="max-w-7xl mx-auto space-y-6">
            <header class="flex items-center justify-between">
                <div>
                    <a href="{{ route('research-proposal.history') }}"
                        class="inline-flex items-center text-xs font-bold text-gray-400 hover:text-[#2271b1] mb-1 transition-colors tracking-widest">
                        <i class="fa-solid fa-arrow-left mr-2"></i> {{ __('View Past Proposals') }}
                    </a>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight">{{ __('Research Proposal Studio') }}</h1>
                    <p class="text-gray-500 font-medium mt-1">
                        {{ __('Define your research title, objectives, optional budget, and custom formatting guidelines.') }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('research-proposal.history') }}"
                        class="px-5 py-2.5 bg-blue-50 hover:bg-blue-100 text-[#2271b1] font-bold rounded-2xl border border-blue-100 transition-all text-xs flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        {{ __('Proposal History') }}
                    </a>
                </div>
            </header>

            <form action="{{ route('research-proposal.store') }}" method="POST" class="space-y-8" id="proposalForm">
                @csrf
                <input type="hidden" name="methodology_type" value="survey">

                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 space-y-8">
                    <!-- Project Core -->
                    <div class="grid grid-cols-1 gap-6 border-b border-gray-50 pb-8">
                        <div>
                            <label for="title"
                                class="block text-xs font-black text-gray-500 tracking-wider mb-2">{{ __('Proposal Title') }}</label>
                            <input type="text" name="title" id="title" required
                                class="w-full bg-gray-50 border-gray-100 rounded-2xl px-5 py-4 text-gray-900 font-bold placeholder-gray-300 focus:ring-2 focus:ring-[#2271b1]/20 focus:border-[#2271b1] transition-all"
                                placeholder="{{ __('e.g. Socio-Economic Impact of Remote Work in Urban Environments') }}">
                        </div>

                        <div>
                            <label for="research_question"
                                class="block text-xs font-black text-gray-500 tracking-wider mb-2">{{ __('Primary Research Question & Main Objectives') }}</label>
                            <textarea name="research_question" id="research_question" rows="3" required
                                class="w-full bg-gray-50 border-gray-100 rounded-2xl px-5 py-4 text-gray-900 font-medium placeholder-gray-300 focus:ring-2 focus:ring-[#2271b1]/20 focus:border-[#2271b1] transition-all"
                                placeholder="{{ __('To what extent does remote work affect employee well-being and organizational productivity?') }}"></textarea>
                        </div>
                    </div>

                    <!-- Objectives & Scope -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 border-b border-gray-50 pb-8">
                        <div>
                            <label for="objectives"
                                class="block text-xs font-black text-gray-500 tracking-wider mb-2">{{ __('Specific Objectives') }}</label>
                            <textarea name="objectives" id="objectives" rows="5" required
                                class="w-full bg-gray-50 border-gray-100 rounded-2xl px-5 py-4 text-gray-900 font-medium placeholder-gray-300 focus:ring-2 focus:ring-[#2271b1]/20 focus:border-[#2271b1] transition-all"
                                placeholder="{{ __('1. Analyze employee stress levels...\n2. Evaluate productivity metrics...\n3. Propose policy recommendations...') }}"></textarea>
                            <p class="mt-2 text-[10px] font-bold text-gray-400 tracking-widest">
                                {{ __('List 3-5 clear objectives.') }}</p>
                        </div>
                        <div>
                            <label for="scope"
                                class="block text-xs font-black text-gray-500 tracking-wider mb-2">{{ __('Target Population & Geographic Scope') }}</label>
                            <textarea name="scope" id="scope" rows="5"
                                class="w-full bg-gray-50 border-gray-100 rounded-2xl px-5 py-4 text-gray-900 font-medium placeholder-gray-300 focus:ring-2 focus:ring-[#2271b1]/20 focus:border-[#2271b1] transition-all"
                                placeholder="{{ __('Define the geographic boundaries, target population, and sample context...') }}"></textarea>
                        </div>
                    </div>

                    <!-- Citation Style (Compact Row) -->
                    <div class="border-b border-gray-50 pb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <label for="style" class="block text-xs font-black text-gray-500 tracking-wider mb-1">{{ __('Academic Citation Standard') }}</label>
                            <p class="text-[10px] text-gray-400 font-medium">{{ __('Select standard for in-text citations and reference list formatting.') }}</p>
                        </div>
                        <select name="style" id="style"
                            class="w-full sm:w-64 bg-gray-50 border-gray-100 rounded-2xl px-4 py-3 text-gray-900 font-bold text-xs focus:ring-2 focus:ring-[#2271b1]">
                            <option value="apa7">APA 7th Edition</option>
                            <option value="mla9">MLA 9th Edition</option>
                            <option value="harvard">Harvard Style</option>
                            <option value="chicago">Chicago/Turabian</option>
                            <option value="ieee">IEEE Style</option>
                            <option value="vancouver">Vancouver Style</option>
                            <option value="oscola">OSCOLA (Law)</option>
                        </select>
                    </div>

                    <!-- Proposed Budget (Full Width) -->
                    <div class="border-b border-gray-50 pb-8 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-black text-gray-500 tracking-wider uppercase">{{ __('Proposed Budget (Optional)') }}</h4>
                                <p class="text-[10px] text-gray-400 font-medium">{{ __('Included in proposal document exports. If left empty, budget section is cleanly excluded.') }}</p>
                            </div>
                            <button type="button" @click="budgetItems.push({ item: '', cost: '' })"
                                class="px-3.5 py-2 bg-blue-50 text-[#2271b1] hover:bg-blue-100 font-bold rounded-xl text-xs transition-colors flex items-center gap-1.5">
                                <i class="fa-solid fa-plus text-[10px]"></i>
                                {{ __('Add Budget Line Item') }}
                            </button>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(b, bIndex) in budgetItems" :key="bIndex">
                                <div class="flex items-center gap-4">
                                    <input type="text" :name="'budget[' + bIndex + '][item]'" x-model="b.item"
                                        placeholder="{{ __('Budget item description (e.g. Field Survey Data Collection & Logistics)') }}"
                                        class="flex-1 bg-gray-50 border-gray-100 rounded-2xl px-5 py-3 text-xs text-gray-900 font-medium focus:ring-2 focus:ring-[#2271b1]">
                                    <input type="number" :name="'budget[' + bIndex + '][cost]'" x-model="b.cost"
                                        placeholder="{{ __('Cost (KES)') }}"
                                        class="w-44 bg-gray-50 border-gray-100 rounded-2xl px-5 py-3 text-xs text-gray-900 font-bold focus:ring-2 focus:ring-[#2271b1]">
                                    <button type="button" @click="budgetItems.splice(bIndex, 1)" x-show="budgetItems.length > 1"
                                        class="w-9 h-9 flex items-center justify-center text-gray-400 hover:text-red-500 transition-colors">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Highlighted Custom Preset Instructions (Positioned directly above Draft Proposal button) -->
                    <div class="p-6 bg-gradient-to-r from-blue-50/80 via-indigo-50/50 to-white rounded-3xl border-2 border-blue-200/80 shadow-xs space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-xl bg-[#2271b1] text-white flex items-center justify-center font-bold text-xs shadow-xs">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                                </div>
                                <div>
                                    <h4 class="text-xs font-black text-gray-900 tracking-wider uppercase">{{ __('Custom Formatting & Voice Presets / KB Guidelines') }}</h4>
                                    <p class="text-[10px] text-gray-500 font-medium">{{ __('Specify tone, structure, formatting preferences, or things to omit/add before generating.') }}</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 text-[10px] font-black bg-blue-100 text-[#2271b1] rounded-lg tracking-wider">{{ __('PRO FEATURE') }}</span>
                        </div>
                        <textarea name="custom_instructions" id="custom_instructions" rows="3"
                            class="w-full bg-white border border-blue-200/80 rounded-2xl px-5 py-4 text-gray-900 font-medium placeholder-gray-400 focus:ring-2 focus:ring-[#2271b1] transition-all text-xs"
                            placeholder="{{ __('e.g. Write in formal academic voice, adhere strictly to APA 7th edition, emphasize quantitative sampling strategies, and omit redundant background fluff.') }}"></textarea>
                    </div>
                </div>

                <!-- Submit Action Area -->
                <div class="flex items-center justify-between pt-2">
                    <p class="text-[10px] font-bold text-gray-400 tracking-widest max-w-xs">
                        {{ __('This can take up to 60 seconds.') }}
                    </p>
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('research-proposal.history') }}"
                            class="px-8 py-4 rounded-2xl text-xs font-black text-gray-500 tracking-widest hover:bg-gray-100 transition-all">{{ __('Cancel') }}</a>
                        <button type="submit" @click="loading = true"
                            class="w-full md:w-auto px-10 py-4 bg-[#2271b1] text-white rounded-2xl font-black text-xs tracking-[0.2em] shadow-xl shadow-zinc-200/50 hover:bg-[#135e96] transition-all flex items-center justify-center border-none group">
                            <template x-if="!loading">
                                <div class="flex items-center justify-center w-full">
                                    <span class="mr-3">{{ __('Draft Proposal') }}</span>
                                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                                </div>
                            </template>
                            <template x-if="loading">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-circle-notch animate-spin mr-3"></i>
                                    <span>{{ __('Drafting Proposal...') }}</span>
                                </div>
                            </template>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection