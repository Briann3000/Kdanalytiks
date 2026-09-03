@extends('layouts.app')

@section('title', 'View Proposal - Research Studio')



@section('content')
    <div class="flex flex-col bg-gray-50/50" x-data="proposalViewer()" x-init="initParser()" style="min-height: 100vh;">
        <!-- Condensed Sticky Toolbar -->
        <div
            class="sticky top-0 z-40 px-4 py-3 bg-white/95 backdrop-blur-md border-b border-gray-200 shadow-sm flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <button type="button" @click="sidebarOpen = !sidebarOpen"
                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-blue-50 text-[#2271b1] hover:bg-blue-100 transition-all border border-blue-100"
                    title="{{ __('Toggle Table of Contents Sidebar') }}">
                    <i class="fa-solid fa-bars-staggered text-xs"></i>
                </button>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('research-proposal.create') }}" title="{{ __('Back to Proposal Studio Editor') }}"
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
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-sm font-black text-gray-900 flex items-center gap-2">
                        <i class="fa-solid fa-wand-magic-sparkles text-amber-500"></i>
                        {{ __('Refine Research Proposal') }}
                    </h3>
                    <button @click="showRefineModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form action="{{ route('research-proposal.generate') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="proposal_id" value="{{ $proposal->id }}">

                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 mb-1">{{ __('Target Section / Chapter') }}</label>
                        <select name="target_section"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-medium focus:ring-2 focus:ring-[#2271b1]">
                            <option value="all">{{ __('Entire Document (All Sections)') }}</option>
                            <option value="ch1">{{ __('Chapter 1: Introduction & Background') }}</option>
                            <option value="ch2">{{ __('Chapter 2: Literature Review & Framework') }}</option>
                            <option value="ch3">{{ __('Chapter 3: Research Methodology') }}</option>
                            <option value="budget">{{ __('Proposed Budget & Work Plan') }}</option>
                            <option value="appendix">{{ __('References & Appendices') }}</option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 mb-1">{{ __('Refinement Instructions') }}</label>
                        <textarea name="user_feedback" rows="4" required
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-xs focus:ring-2 focus:ring-[#2271b1]"
                            placeholder="{{ __('e.g., Expand Section 1.1 with more Kajiado statistics; elaborate the TAM theoretical framework in Chapter 2; adjust the sample size in Chapter 3...') }}"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showRefineModal = false"
                            class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl text-xs font-bold">{{ __('Cancel') }}</button>
                        <button type="submit"
                            class="px-5 py-2 bg-[#2271b1] text-white rounded-xl text-xs font-bold hover:bg-[#135e96]">{{ __('Apply Refinement') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Main Content Document View -->
        <div class="flex-1 p-6 md:p-10 max-w-5xl mx-auto w-full">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 md:p-14 mb-10">
                <div class="space-y-8">
                    @foreach($proposal->content ?? [] as $title => $content)
                        <section class="scroll-mt-32">
                            <div
                                class="prose prose-slate prose-lg max-w-none text-slate-800 leading-relaxed text-justify markdown-container">
                                <script type="application/json" class="raw-markdown">
                                                    {!! json_encode($content) !!}
                                                </script>
                                <div class="parsed-content"></div>
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>

            <footer class="max-w-4xl mx-auto py-8 text-center">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.25em]">&bull; END OF FORMAL RESEARCH
                    PROPOSAL &bull;</p>
            </footer>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.css">
        <style>
            .custom-scrollbar::-webkit-scrollbar {
                width: 4px;
            }

            .custom-scrollbar::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 10px;
            }

            /* Academic Proposal Styling */
            .prose h1 {
                font-size: 1.5rem !important;
                font-weight: 900 !important;
                color: #0f172a !important;
                text-transform: uppercase !important;
                letter-spacing: -0.02em !important;
                margin-top: 2.5rem !important;
                margin-bottom: 1.25rem !important;
                padding-bottom: 0.5rem !important;
                border-bottom: 2px solid #0f172a !important;
            }

            .prose h2 {
                font-size: 1.2rem !important;
                font-weight: 800 !important;
                color: #0f172a !important;
                margin-top: 2rem !important;
                margin-bottom: 0.75rem !important;
                padding-left: 0.75rem !important;
                border-left: 4px solid #2271b1 !important;
            }

            .prose h3 {
                font-size: 1.05rem !important;
                font-weight: 700 !important;
                color: #334155 !important;
                margin-top: 1.5rem !important;
                margin-bottom: 0.5rem !important;
            }

            .prose p {
                margin-bottom: 1.15rem !important;
                margin-top: 0 !important;
                line-height: 1.8 !important;
                color: #334155 !important;
            }

            /* Beautiful Academic Tables */
            .prose table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin: 1.75rem 0 !important;
                font-size: 0.85rem !important;
                border: 1px solid #cbd5e1 !important;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05) !important;
            }

            .prose th {
                background-color: #f1f5f9 !important;
                color: #0f172a !important;
                font-weight: 800 !important;
                padding: 10px 14px !important;
                border: 1px solid #cbd5e1 !important;
                text-align: left !important;
            }

            .prose td {
                padding: 10px 14px !important;
                border: 1px solid #e2e8f0 !important;
                vertical-align: top !important;
                color: #334155 !important;
            }

            .prose tr:nth-child(even) {
                background-color: #f8fafc !important;
            }

            .prose ul,
            .prose ol {
                margin-top: 0.5rem !important;
                margin-bottom: 1rem !important;
                padding-left: 1.5rem !important;
            }

            .prose li {
                margin-bottom: 0.35rem !important;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.js"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/contrib/auto-render.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
        <script>mermaid.initialize({ startOnLoad: false, theme: 'neutral', securityLevel: 'loose', suppressErrorRendering: true });</script>
        <script>
            function proposalViewer() {
                return {
                    showRefineModal: false,
                    toc: [],

                    initParser() {
                        this.$nextTick(() => {
                            this.buildTableOfContents();
                        });
                    },
                    buildTableOfContents() {
                        const containers = document.querySelectorAll('.markdown-container');
                        let allTocItems = [];
                        let headingCounter = 0;

                        containers.forEach(container => {
                            try {
                                const scriptEl = container.querySelector('.raw-markdown');
                                const parsedDiv = container.querySelector('.parsed-content');

                                if (scriptEl && parsedDiv) {
                                    let rawMarkdown = JSON.parse(scriptEl.textContent);

                                    // Pre-process markdown: Auto-elevate plain numbered headings like "1.1 Background" to markdown ## if missing
                                    rawMarkdown = rawMarkdown.replace(/^(\d+\.\d+\s+[A-Za-z].*)$/gm, '## $1');
                                    rawMarkdown = rawMarkdown.replace(/^(\d+\.\d+\.\d+\s+[A-Za-z].*)$/gm, '### $1');
                                    rawMarkdown = rawMarkdown.replace(/^(CHAPTER\s+\d+[:\s]+[A-Za-z\s]+)$/gmi, '# $1');
                                    rawMarkdown = rawMarkdown.replace(/^(PROPOSED BUDGET AND WORK PLAN|REFERENCES|APPENDIX\s+[A-Z][: ].*)$/gmi, '# $1');

                                    let html = marked.parse(rawMarkdown);

                                    // Replace ```mermaid with safe containers
                                    let dIdx = 0;
                                    html = html.replace(/<pre><code class="language-mermaid">([\s\S]*?)<\/code><\/pre>/gi, function (match, code) {
                                        dIdx++;
                                        let cleanCode = code.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
                                        cleanCode = cleanCode.replace(/\[([^\]"'\n]+)\]/g, function (m, p1) {
                                            return '["' + p1.replace(/"/g, '') + '"]';
                                        });
                                        const cId = 'mermaid-show-' + dIdx + '-' + Date.now();
                                        return '<div class="mermaid-diagram-container" id="' + cId + '" data-code="' + btoa(unescape(encodeURIComponent(cleanCode.trim()))) + '"></div>';
                                    });

                                    const tempDiv = document.createElement('div');
                                    tempDiv.innerHTML = html;
                                    const headings = tempDiv.querySelectorAll('h1, h2, h3');

                                    headings.forEach((heading) => {
                                        const titleText = heading.textContent.trim();
                                        const id = 'chapter-' + headingCounter++;
                                        heading.setAttribute('id', id);
                                        heading.classList.add('scroll-mt-32');

                                        allTocItems.push({
                                            id: id,
                                            title: titleText,
                                            level: parseInt(heading.tagName.substring(1))
                                        });
                                    });

                                    parsedDiv.innerHTML = tempDiv.innerHTML;

                                    // Extract headings directly from DOM
                                    parsedDiv.querySelectorAll('h1, h2, h3').forEach((heading) => {
                                        const titleText = heading.innerText.trim();
                                        if (titleText) {
                                            const id = 'chapter-' + (++headingCounter);
                                            heading.setAttribute('id', id);
                                            heading.classList.add('scroll-mt-32');

                                            allTocItems.push({
                                                id: id,
                                                title: titleText,
                                                level: parseInt(heading.tagName.substring(1))
                                            });
                                        }
                                    });
                                }
                            } catch (e) {
                                console.error('Failed to parse section markdown:', e);
                            }
                        });

                        this.toc = allTocItems;

                        setTimeout(async () => {
                            if (window.mermaid) {
                                const containers = document.querySelectorAll('.mermaid-diagram-container[data-code]');
                                for (let el of containers) {
                                    try {
                                        const rawCode = decodeURIComponent(escape(atob(el.getAttribute('data-code'))));
                                        const id = 'svg-' + Math.random().toString(36).substr(2, 9);
                                        const { svg } = await window.mermaid.render(id, rawCode);
                                        el.innerHTML = svg;
                                        el.removeAttribute('data-code');
                                    } catch (err) {
                                        console.warn('Mermaid rendering fallback in show:', err);
                                        el.innerHTML = '<div class="w-full bg-slate-50 border border-slate-200 rounded-lg p-4 text-center text-xs text-slate-600"><span class="font-bold text-slate-800"><i class="fa-solid fa-project-diagram text-[#2271b1] mr-1.5"></i> Conceptual Model Relationships</span></div>';
                                    }
                                }
                            }

                            try {
                                if (window.renderMathInElement) {
                                    document.querySelectorAll('.markdown-container').forEach(el => {
                                        window.renderMathInElement(el, {
                                            delimiters: [
                                                { left: '$$', right: '$$', display: true },
                                                { left: '\\[', right: '\\]', display: true },
                                                { left: '$', right: '$', display: false },
                                                { left: '\\(', right: '\\)', display: false }
                                            ],
                                            throwOnError: false
                                        });
                                    });
                                }
                            } catch (mathErr) {
                                console.warn('KaTeX math render error in show:', mathErr);
                            }
                        }, 100);
                    }
                };
            }
        </script>
    @endpush
@endsection