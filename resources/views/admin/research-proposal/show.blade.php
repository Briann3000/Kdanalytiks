@extends('layouts.app')

@section('title', 'View Proposal - Research Studio')

@section('sub_sidebar')
    <!-- Professional Collapsible Sidebar for Proposal Navigation -->
    <div class="bg-white border-r border-gray-100 flex-shrink-0 flex flex-col z-30 shadow-sm transition-all duration-300 overflow-hidden"
         :class="sidebarOpen ? 'w-60' : 'w-12'">
        
        <div class="p-6 border-b border-gray-100 bg-gray-50/30 flex items-center" :class="sidebarOpen ? '' : 'justify-center px-0'">
            <i class="fa-solid fa-file-invoice text-indigo-500" :class="sidebarOpen ? 'mr-3' : ''"></i>
            <h3 x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                class="text-[10px] font-black text-gray-400 uppercase tracking-widest truncate flex-1">
                Proposal Draft
            </h3>
            <button @click="sidebarOpen = !sidebarOpen" 
                    class="w-6 h-6 flex items-center justify-center rounded-lg bg-white border border-gray-100 text-gray-400 hover:text-indigo-600 hover:border-indigo-100 transition-all"
                    :class="sidebarOpen ? 'ml-2' : 'absolute -right-3 top-7 z-50 shadow-md'">
                <i class="fa-solid fa-chevron-left text-[10px] transition-transform duration-300" :class="sidebarOpen ? '' : 'rotate-180'"></i>
            </button>
        </div>
        
        <nav class="flex-1 overflow-y-auto p-3 space-y-1 custom-scrollbar">
            @foreach($proposal->content ?? [] as $title => $content)
                <a href="#section-{{ $loop->iteration }}" 
                   title="{{ $title }}"
                   class="flex items-center rounded-xl text-xs font-bold text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 transition-all group border border-transparent hover:border-indigo-100"
                   :class="sidebarOpen ? 'px-4 py-3' : 'p-2 justify-center'">
                    <span class="w-6 h-6 flex-shrink-0 flex items-center justify-center rounded-lg bg-gray-50 text-[10px] group-hover:bg-indigo-100 group-hover:text-indigo-600 transition-colors"
                          :class="sidebarOpen ? 'mr-3' : ''">
                        {{ $loop->iteration }}
                    </span>
                    <span x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
                          class="truncate">{{ $title }}</span>
                </a>
            @endforeach
        </nav>
    </div>
@endsection

@section('content')
<div class="flex flex-col h-full bg-gray-50/50">
    <!-- Condensed Sticky Toolbar -->
    <div class="sticky top-0 z-20 px-4 py-3 bg-white/90 backdrop-blur-md border-b border-gray-200 shadow-sm flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <a href="{{ route('research-proposal.index') }}" class="w-7 h-7 flex items-center justify-center rounded-lg bg-gray-50 text-gray-400 hover:bg-gray-100 transition-all border border-gray-100">
                <i class="fa-solid fa-arrow-left text-[10px]"></i>
            </a>
            <div class="hidden sm:block">
                <h1 class="text-[10px] font-black text-gray-900 uppercase tracking-tight leading-none">Proposal Preview</h1>
                <p class="text-[8px] text-gray-400 font-bold uppercase tracking-wider">{{ $proposal->title }}</p>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-widest rounded-lg border border-emerald-100">Ready for Review</span>
            
            <a href="{{ route('research-proposal.export-proposal', $proposal->id) }}" 
               class="px-4 py-1.5 bg-indigo-600 text-white rounded-xl font-black text-[8px] uppercase tracking-wider shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center group">
                <i class="fa-solid fa-download mr-2 text-[10px]"></i>
                <span>Download (DOCX)</span>
            </a>
        </div>
    </div>

    <!-- Scrollable Draft Content Area -->
    <div class="flex-1 p-10 overflow-y-auto custom-scrollbar">
        <div class="max-w-4xl mx-auto bg-white shadow-2xl shadow-gray-200/50 rounded-lg border border-gray-100 p-16 min-h-screen mb-12">
            <!-- Cover Section -->
            <div class="text-center py-12 mb-16 border-b-2 border-gray-50">
                <span class="text-[12px] font-black text-indigo-600 uppercase tracking-[0.4em] mb-4 block">Formal Proposal</span>
                <h2 class="text-3xl font-black text-gray-900 mb-4 tracking-tighter uppercase leading-none">{{ $proposal->title }}</h2>
                <div class="w-16 h-1 bg-indigo-600 mx-auto rounded-full mb-6"></div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-none">Methodology: {{ ucfirst($proposal->methodology_type) }} | Style: {{ strtoupper($proposal->style) }}</p>
            </div>

            <!-- Generated Sections -->
            <div class="space-y-16">
                @foreach($proposal->content ?? [] as $title => $content)
                    <section id="section-{{ $loop->iteration }}" class="scroll-mt-32">
                        <h3 class="text-xl font-black text-gray-900 mb-8 border-l-4 border-indigo-600 pl-5 tracking-tight uppercase leading-none">{{ $title }}</h3>
                        <div class="prose prose-slate prose-lg max-w-none text-gray-700 leading-relaxed font-serif text-justify whitespace-pre-wrap">
                            {!! $content !!}
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
        
        <footer class="max-w-4xl mx-auto py-12 text-center">
            <p class="text-[9px] text-gray-300 font-bold uppercase tracking-[0.3em]">&bull; END OF PROPOSAL DRAFT &bull;</p>
        </footer>
    </div>
</div>

@push('styles')
<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #E5E7EB; border-radius: 10px; }
    .prose p { margin-bottom: 2rem; }
    html { scroll-behavior: smooth; }
</style>
@endpush
@endsection
