@extends('layouts.app')

@section('title', 'View Proposal - Research Studio')

@section('sub_sidebar')
    <!-- Professional Collapsible Sidebar for Proposal Navigation -->
    <div class="bg-white border-r border-gray-100 flex-shrink-0 flex flex-col z-30 shadow-sm transition-all duration-300 overflow-hidden"
        :class="sidebarOpen ? 'w-60' : 'w-12'">

        <div class="p-6 border-b border-gray-100 bg-gray-50/30 flex items-center"
            :class="sidebarOpen ? '' : 'justify-center px-0'">
            <i class="fa-solid fa-file-invoice text-zinc-2000" :class="sidebarOpen ? 'mr-3' : ''"></i>
            <h3 x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                class="text-[10px] font-black text-gray-400 uppercase tracking-widest truncate flex-1">
                Proposal Draft
            </h3>
            <button @click="sidebarOpen = !sidebarOpen"
                class="w-6 h-6 flex items-center justify-center rounded-lg bg-white border border-gray-100 text-gray-400 hover:text-[#2271b1] hover:border-zinc-200 transition-all"
                :class="sidebarOpen ? 'ml-2' : 'absolute -right-3 top-7 z-50 shadow-md'">
                <i class="fa-solid fa-chevron-left text-[10px] transition-transform duration-300"
                    :class="sidebarOpen ? '' : 'rotate-180'"></i>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto p-3 space-y-1 custom-scrollbar">
            @foreach($proposal->content ?? [] as $title => $content)
                <a href="#section-{{ $loop->iteration }}" title="{{ $title }}"
                    class="flex items-center rounded-xl text-xs font-bold text-gray-600 hover:bg-zinc-100 hover:text-[#135e96] transition-all group border border-transparent hover:border-zinc-200"
                    :class="sidebarOpen ? 'px-4 py-3' : 'p-2 justify-center'">
                    <span
                        class="w-6 h-6 flex-shrink-0 flex items-center justify-center rounded-lg bg-gray-50 text-[10px] group-hover:bg-zinc-200 group-hover:text-[#2271b1] transition-colors"
                        :class="sidebarOpen ? 'mr-3' : ''">
                        {{ $loop->iteration }}
                    </span>
                    <span x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        class="truncate">{{ $title }}</span>
                </a>
            @endforeach
        </nav>
    </div>
@endsection

@section('content')
    <div class="flex flex-col bg-gray-50/50" style="height: calc(100vh - 4.1rem);">
        <!-- Condensed Sticky Toolbar matching Preview -->
        <div
            class="sticky top-0 z-40 px-4 py-3 bg-white/90 backdrop-blur-md border-b border-gray-200 shadow-sm flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="flex items-center space-x-2">
                    <a href="{{ route('research-proposal.index') }}"
                        class="w-7 h-7 flex items-center justify-center rounded-lg bg-gray-50 text-gray-400 hover:bg-gray-100 transition-all border border-gray-100">
                        <i class="fa-solid fa-arrow-left text-[10px]"></i>
                    </a>
                    <div class="hidden sm:block">
                        <h1 class="text-[10px] font-black text-gray-900 uppercase tracking-tight leading-none">Draft Report
                        </h1>
                        <p class="text-[8px] text-gray-400 font-bold uppercase tracking-wider truncate max-w-[200px]">
                            {{ $proposal->title }}</p>
                    </div>
                </div>
                <div class="h-5 w-[1px] bg-gray-200"></div>
                <span
                    class="px-2 py-0.5 bg-emerald-50 text-emerald-600 text-[8px] font-black uppercase tracking-widest rounded border border-emerald-100 italic">Formal
                    Draft</span>
            </div>

            <div class="flex items-center space-x-3">
                <div class="flex items-center space-x-2 bg-gray-50 px-3 py-1.5 rounded-xl border border-gray-100">
                    <span class="text-[9px] font-black text-gray-400 uppercase">Style:</span>
                    <span class="text-[9px] font-black text-[#2271b1] uppercase">{{ strtoupper($proposal->style) }}</span>
                </div>

                <div class="h-5 w-[1px] bg-gray-200"></div>

                <a href="{{ route('research-proposal.export-proposal', ['id' => $proposal->id]) }}"
                    class="px-5 py-2 bg-[#2271b1] text-white rounded-xl font-black text-[9px] uppercase tracking-wider shadow-lg shadow-zinc-200/50 hover:bg-[#135e96] transition-all flex items-center group">
                    <i class="fa-solid fa-file-word mr-2 text-[11px]"></i>
                    <span>Export (DOCX)</span>
                </a>
            </div>
        </div>

        <!-- Scrollable Draft Content Area -->
        <div class="flex-1 p-10 overflow-y-auto custom-scrollbar bg-gray-50/50">
            <div
                class="max-w-4xl mx-auto bg-white shadow-2xl shadow-gray-200/50 rounded-lg border border-gray-100 p-16 min-h-screen mb-12">
                <!-- Cover Section -->
                <div class="text-center py-12 mb-16 border-b-2 border-gray-50">
                    <span
                        class="text-[12px] font-black text-[#2271b1] uppercase tracking-[0.4em] mb-4 block animate-pulse">Formal
                        Research Report Draft</span>
                    <h2 class="text-4xl font-black text-gray-900 mb-4 tracking-tighter uppercase leading-none">
                        {{ $proposal->title }}</h2>
                    <div class="w-16 h-1 bg-[#2271b1] mx-auto rounded-full mb-6"></div>

                    <div class="flex items-center justify-center space-x-6">
                        <div class="text-center">
                            <p class="text-[8px] font-bold text-gray-400 uppercase tracking-widest mb-1">Methodology</p>
                            <p class="text-[10px] font-black text-gray-900 uppercase tracking-widest italic">
                                {{ $proposal->methodology_type }}</p>
                        </div>
                        <div class="h-8 w-[1px] bg-gray-100"></div>
                        <div class="text-center">
                            <p class="text-[8px] font-bold text-gray-400 uppercase tracking-widest mb-1">Standard</p>
                            <p class="text-[10px] font-black text-gray-900 uppercase tracking-widest">
                                {{ strtoupper($proposal->style) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Generated Sections -->
                <div class="space-y-16">
                    @php
                        $currentChapter = null;
                    @endphp
                    @foreach($proposal->content ?? [] as $title => $content)
                        @php
                            // Check if it's a new chapter to add spacing/headers
                            $isChapter = str_contains(strtoupper($title), 'CHAPTER');
                        @endphp

                        <section id="section-{{ $loop->iteration }}" class="scroll-mt-32">
                            @if($isChapter)
                                <div class="pt-8 pb-4 mb-10 border-b-4 border-gray-900">
                                    <h3 class="text-2xl font-black text-gray-900 tracking-tighter uppercase leading-none">
                                        {{ $title }}</h3>
                                </div>
                            @else
                                <h4
                                    class="text-lg font-black text-gray-900 mb-6 border-l-4 border-[#2271b1] pl-5 tracking-tight uppercase leading-none">
                                    {{ $title }}</h4>
                            @endif

                            <div
                                class="prose prose-slate prose-lg max-w-none text-gray-700 leading-relaxed font-serif text-justify whitespace-pre-wrap {{ $isChapter ? 'hidden' : '' }}">
                                {!! $content !!}
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>

            <footer class="max-w-4xl mx-auto py-12 text-center">
                <p class="text-[9px] text-gray-300 font-bold uppercase tracking-[0.3em]">&bull; END OF DRAFT REPORT &bull;
                </p>
                <p class="text-[8px] text-gray-400 font-medium mt-2 uppercase tracking-widest font-black italic opacity-50">
                    Generated by PRC™ Synthesis Engine</p>
            </footer>
        </div>
    </div>

    @push('styles')
        <style>
            .custom-scrollbar::-webkit-scrollbar {
                width: 4px;
            }

            .custom-scrollbar::-webkit-scrollbar-track {
                background: transparent;
            }

            .custom-scrollbar::-webkit-scrollbar-thumb {
                background: #E5E7EB;
                border-radius: 10px;
            }

            .prose p {
                margin-bottom: 2rem;
            }

            html {
                scroll-behavior: smooth;
            }
        </style>
    @endpush
@endsection