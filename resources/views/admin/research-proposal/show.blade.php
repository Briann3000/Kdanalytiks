@extends('layouts.app')

@section('title', 'View Proposal - Research Studio')

@section('sub_sidebar')
    <!-- Professional Collapsible Sidebar for Proposal Navigation -->
    <div class="bg-white border-r border-gray-100 flex-shrink-0 flex flex-col z-30 shadow-sm transition-all duration-300 relative"
        :class="sidebarOpen ? 'w-64' : 'w-14'">

        <div class="p-4 border-b border-gray-100 bg-gray-50/30 flex items-center justify-between"
            :class="sidebarOpen ? '' : 'px-2 justify-center'">
            <div class="flex items-center truncate">
                <i class="fa-solid fa-file-invoice text-[#2271b1]" :class="sidebarOpen ? 'mr-2' : ''"></i>
                <h3 x-show="sidebarOpen" x-transition
                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest truncate">
                    Proposal Chapters
                </h3>
            </div>
            <button @click="sidebarOpen = !sidebarOpen"
                class="w-6 h-6 flex items-center justify-center rounded-lg bg-white border border-gray-200 text-gray-400 hover:text-[#2271b1] transition-all shrink-0">
                <i class="fa-solid fa-chevron-left text-[10px] transition-transform duration-300"
                    :class="sidebarOpen ? '' : 'rotate-180'"></i>
            </button>
        </div>

        <!-- Dynamic Table of Contents Populated by Alpine -->
        <nav class="flex-1 overflow-y-auto p-3 space-y-1 custom-scrollbar">
            <template x-for="(navItem, idx) in toc" :key="idx">
                <a :href="'#' + navItem.id" :title="navItem.title"
                    @click.prevent="document.getElementById(navItem.id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                    class="flex items-center rounded-xl text-xs transition-all group border border-transparent hover:border-zinc-200 hover:bg-zinc-100"
                    :class="[
                            sidebarOpen ? 'px-3 py-2.5' : 'p-2 justify-center',
                            navItem.level === 1 ? 'font-bold text-gray-800 mt-2' : (navItem.level === 2 ? 'font-semibold text-gray-600 pl-4' : 'font-normal text-gray-500 pl-6')
                        ]">

                    <!-- Icon/Number for H1s -->
                    <template x-if="navItem.level === 1">
                        <span
                            class="w-5 h-5 flex-shrink-0 flex items-center justify-center rounded-md bg-blue-50 text-[9px] font-black text-[#2271b1] mr-2"
                            x-show="sidebarOpen">
                            <i class="fa-solid fa-hashtag text-[8px]"></i>
                        </span>
                    </template>

                    <!-- Bullet for nested items -->
                    <template x-if="navItem.level > 1">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-300 mr-2 flex-shrink-0" x-show="sidebarOpen"></span>
                    </template>

                    <span x-show="sidebarOpen" class="truncate" x-text="navItem.title"></span>

                    <!-- Closed sidebar icon fallback -->
                    <span x-show="!sidebarOpen"
                        class="w-6 h-6 flex items-center justify-center rounded-lg bg-gray-50 text-[10px] text-gray-500 group-hover:bg-zinc-200 group-hover:text-[#2271b1]">
                        <i class="fa-solid fa-minus" x-show="navItem.level > 1"></i>
                        <i class="fa-solid fa-hashtag" x-show="navItem.level === 1"></i>
                    </span>
                </a>
            </template>
        </nav>
    </div>
@endsection

@section('content')
    <div class="flex flex-col bg-gray-50/50" x-data="proposalViewer()" x-init="initParser()" style="min-h-screen;">
        <!-- Condensed Sticky Toolbar -->
        <div
            class="sticky top-0 z-40 px-4 py-3 bg-white/90 backdrop-blur-md border-b border-gray-200 shadow-sm flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <button type="button" @click="sidebarOpen = !sidebarOpen"
                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-blue-50 text-[#2271b1] hover:bg-blue-100 transition-all border border-blue-100"
                    title="{{ __('Toggle Table of Contents Sidebar') }}">
                    <i class="fa-solid fa-bars-staggered text-xs"></i>
                </button>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('research-proposal.history') }}"
                        class="w-7 h-7 flex items-center justify-center rounded-lg bg-gray-50 text-gray-400 hover:bg-gray-100 transition-all border border-gray-100">
                        <i class="fa-solid fa-arrow-left text-[10px]"></i>
                    </a>
                    <div class="hidden sm:block">
                        <h1 class="text-[10px] font-black text-gray-900 uppercase tracking-tight leading-none">Research
                            Proposal</h1>
                        <p class="text-[8px] text-gray-400 font-bold uppercase tracking-wider truncate max-w-[200px]">
                            {{ $proposal->title }}
                        </p>
                    </div>
                </div>
                <div class="h-5 w-[1px] bg-gray-200"></div>
                <span
                    class="px-2 py-0.5 bg-emerald-50 text-emerald-600 text-[8px] font-black uppercase tracking-widest rounded border border-emerald-100 italic">
                    Formal Proposal
                </span>
            </div>

            <div class="flex items-center space-x-3">
                <div class="flex items-center space-x-2 bg-gray-50 px-3 py-1.5 rounded-xl border border-gray-100">
                    <span class="text-[9px] font-black text-gray-400 uppercase">Style:</span>
                    <span class="text-[9px] font-black text-[#2271b1] uppercase">{{ strtoupper($proposal->style) }}</span>
                </div>

                <button type="button" @click="showRefineModal = true"
                    class="px-3.5 py-2 bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-200 rounded-xl font-bold text-xs flex items-center gap-1.5 transition-all">
                    <i class="fa-solid fa-wand-magic-sparkles text-amber-600"></i>
                    <span>{{ __('Refine Generation') }}</span>
                </button>

                <a href="{{ route('research-proposal.export-proposal', ['id' => $proposal->id]) }}"
                    class="px-5 py-2 bg-[#2271b1] text-white rounded-xl font-black text-[9px] uppercase tracking-wider shadow-lg shadow-zinc-200/50 hover:bg-[#135e96] transition-all flex items-center group">
                    <i class="fa-solid fa-file-word mr-2 text-[11px]"></i>
                    <span>Export (DOCX)</span>
                </a>
            </div>
        </div>

        <!-- Refine Modal -->
        <div x-show="showRefineModal"
            class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
            style="display: none;">
            <div class="bg-white rounded-3xl p-6 max-w-lg w-full space-y-4 shadow-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-black text-gray-900 flex items-center gap-2">
                        <i class="fa-solid fa-wand-magic-sparkles text-[#2271b1]"></i>
                        {{ __('Refine Proposal with AI') }}
                    </h3>
                    <button @click="showRefineModal = false" class="text-gray-400 hover:text-gray-600"><i
                            class="fa-solid fa-xmark"></i></button>
                </div>
                <form action="{{ route('research-proposal.refine', $proposal->id) }}" method="POST" class="space-y-4">
                    @csrf
                    <!-- ... Modal Fields remain identical ... -->
                    <div class="space-y-1">
                        <label
                            class="block text-xs font-bold text-gray-700">{{ __('Select Target Chapter / Section to Refine:') }}</label>
                        <select name="target_section"
                            class="w-full text-xs font-bold p-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#2271b1]">
                            <option value="all">{{ __('-- Entire Proposal (All Sections) --') }}</option>
                            <option value="preliminaries">{{ __('Preliminaries & Abstract') }}</option>
                            <option value="ch1">{{ __('Chapter 1: Introduction & Background') }}</option>
                            <option value="ch2">{{ __('Chapter 2: Literature & Theoretical Framework') }}</option>
                            <option value="ch3">{{ __('Chapter 3: Research Methodology & Sampling') }}</option>
                            <option value="budget">{{ __('Proposed Budget & Work Plan') }}</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-gray-700">{{ __('Refinement Instructions:') }}</label>
                        <textarea name="refinement_instructions" rows="4" required
                            placeholder="{{ __('e.g. Deepen Chapter 2 literature review, expand Chapter 3 sampling formula, or rewrite specific sections...') }}"
                            class="w-full text-xs p-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#2271b1]"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showRefineModal = false"
                            class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl text-xs font-bold">{{ __('Cancel') }}</button>
                        <button type="submit"
                            class="px-6 py-2 bg-[#2271b1] text-white rounded-xl text-xs font-bold shadow-md hover:bg-[#135e96] transition-all">
                            {{ __('Regenerate') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Scrollable Draft Content Area -->
        <div class="flex-1 p-3 sm:p-8 overflow-y-auto custom-scrollbar bg-gray-50/50">
            <div
                class="max-w-4xl mx-auto bg-white shadow-2xl shadow-gray-200/50 rounded-lg border border-gray-100 p-4 sm:p-10 md:p-16 min-h-screen mb-12">

                <!-- Cover Section -->
                <div class="text-center py-12 mb-16 border-b-2 border-gray-50">
                    <span class="text-[12px] font-black text-[#2271b1] uppercase tracking-[0.4em] mb-4 block">
                        Formal Research Proposal
                    </span>
                    <h2 class="text-4xl font-black text-gray-900 mb-4 tracking-tighter uppercase leading-none">
                        {{ $proposal->title }}
                    </h2>
                    <div class="w-16 h-1 bg-[#2271b1] mx-auto rounded-full mb-6"></div>

                    <div class="flex items-center justify-center space-x-6">
                        <div class="text-center">
                            <p class="text-[8px] font-bold text-gray-400 uppercase tracking-widest mb-1">Methodology</p>
                            <p class="text-[10px] font-black text-gray-900 uppercase tracking-widest italic">
                                {{ $proposal->methodology_type }}
                            </p>
                        </div>
                        <div class="h-8 w-[1px] bg-gray-100"></div>
                        <div class="text-center">
                            <p class="text-[8px] font-bold text-gray-400 uppercase tracking-widest mb-1">Standard</p>
                            <p class="text-[10px] font-black text-gray-900 uppercase tracking-widest">
                                {{ strtoupper($proposal->style) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Generated Sections -->
                <div class="space-y-12">
                    @foreach($proposal->content ?? [] as $title => $content)
                        <section class="scroll-mt-32">
                            <!-- Removed whitespace-pre-wrap to fix gaping space issues -->
                            <div
                                class="prose prose-slate prose-lg max-w-none text-gray-700 leading-relaxed font-serif text-justify markdown-container">
                                <script type="application/json" class="raw-markdown">
                                            {!! json_encode($content) !!}
                                        </script>
                                <!-- Parsed Markdown will be injected here -->
                                <div class="parsed-content"></div>
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>

            <footer class="max-w-4xl mx-auto py-12 text-center">
                <p class="text-[9px] text-gray-300 font-bold uppercase tracking-[0.3em]">&bull; END OF RESEARCH PROPOSAL
                    &bull;</p>
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

            /* Reduced paragraph margin drastically to fix whitespace issues */
            .prose p {
                margin-bottom: 1rem;
                margin-top: 0;
            }

            html {
                scroll-behavior: smooth;
            }

            /* Markdown Tables */
            .prose table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .prose th {
                background-color: #f3f4f6;
                padding: 0.75rem;
                border: 1px solid #e5e7eb;
                font-weight: 700;
                text-align: left;
            }

            .prose td {
                padding: 0.75rem;
                border: 1px solid #e5e7eb;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <script>
            function proposalViewer() {
                return {
                    showRefineModal: false,
                    toc: [], // Holds dynamically extracted chapters/headers

                    initParser() {
                        const containers = document.querySelectorAll('.markdown-container');
                        let allTocItems = [];
                        let headingCounter = 0;

                        containers.forEach(container => {
                            try {
                                const scriptEl = container.querySelector('.raw-markdown');
                                const parsedDiv = container.querySelector('.parsed-content');

                                if (scriptEl && parsedDiv) {
                                    const rawMarkdown = JSON.parse(scriptEl.textContent);
                                    let html = marked.parse(rawMarkdown);

                                    // Extract Headers and inject custom styling
                                    const tempDiv = document.createElement('div');
                                    tempDiv.innerHTML = html;
                                    const headings = tempDiv.querySelectorAll('h1, h2, h3');

                                    headings.forEach((heading) => {
                                        const titleText = heading.textContent.trim();
                                        const id = 'chapter-' + headingCounter++;
                                        heading.setAttribute('id', id);
                                        heading.classList.add('scroll-mt-32'); // Ensure jumping to header accounts for sticky toolbar

                                        // Address "headers look different" by applying Tailwind Typography overrides
                                        heading.classList.add('font-black', 'text-gray-900', 'tracking-tight', 'mb-4');
                                        if (heading.tagName === 'H1') {
                                            heading.classList.add('text-3xl', 'uppercase', 'mt-12', 'border-b-2', 'border-gray-100', 'pb-3');
                                        } else if (heading.tagName === 'H2') {
                                            heading.classList.add('text-xl', 'mt-8', 'text-[#2271b1]');
                                        } else if (heading.tagName === 'H3') {
                                            heading.classList.add('text-lg', 'mt-6');
                                        }

                                        // Push to Sidebar TOC array
                                        allTocItems.push({
                                            id: id,
                                            title: titleText,
                                            level: parseInt(heading.tagName.substring(1))
                                        });
                                    });

                                    parsedDiv.innerHTML = tempDiv.innerHTML;
                                }
                            } catch (e) {
                                console.error('Failed to parse section markdown:', e);
                            }
                        });

                        this.toc = allTocItems;

                        this.$nextTick(() => {
                            this.renderCharts();
                        });
                    },
                    renderCharts() {
                        const chartBlocks = document.querySelectorAll('pre code.language-chartjs');
                        chartBlocks.forEach((codeEl, index) => {
                            try {
                                const chartConfig = JSON.parse(codeEl.textContent);
                                const canvas = document.createElement('canvas');
                                canvas.id = 'proposal-chart-' + index;
                                canvas.className = 'my-6 max-h-96 w-full';

                                const preParent = codeEl.closest('pre');
                                if (preParent) {
                                    preParent.replaceWith(canvas);
                                    new Chart(canvas.getContext('2d'), chartConfig);
                                }
                            } catch (e) {
                                console.error('Failed to render Chart.js block:', e);
                            }
                        });
                    }
                }
            }
        </script>
    @endpush
@endsection