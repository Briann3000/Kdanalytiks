@extends('surveys.hub')

@section('survey-content')
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #cbd5e1;
        }
    </style>
    @php
        $allowedReportTabs = ['quantitative', 'qualitative', 'inferential', 'crosstab', 'analyse', 'humanizer'];
        $requestedReportTab = request('reportTab', 'quantitative');
        $initialReportTab = in_array($requestedReportTab, $allowedReportTabs, true) ? $requestedReportTab : 'quantitative';
    @endphp

    <script>
        window.reportManager = function () {
            return {
                reportTab: @js($initialReportTab === 'crosstab' ? 'inferential' : $initialReportTab),
                humanizerOriginal: '',
                humanizerResult: '',
                isHumanizing: false,
                isAnalyzing: false,
                humanizerMode: 'standard',
                humanizerIntensity: 'medium',
                customInstructions: '',
                originalAnalysis: null,
                humanizedAnalysis: null,
                reportingStyle: @js($survey->reporting_style ?? 'apa'),
                isPremium: @js(auth()->user() ? auth()->user()->hasActiveSubscription() : false),
                globalFeedback: '',
                globalRefining: false,
                globalRefinePaused: false,
                globalRefineAbort: false,
                globalRefineIndex: 0,
                globalRefineTotal: 0,
                globalRefineSuccessCount: 0,
                globalRefineFailCount: 0,
                globalRefineProgress: '',
                globalRefineActiveFeedback: '',
                quantQuestionIds: @js(collect($analysis)->where('isChartable', true)->where('isLikertLike', false)->pluck('id')),
                allQuantQuestions: @js(collect($analysis)->where('isChartable', true)->map(fn($q) => ['id' => $q['id'], 'label' => $q['label'] ?? ('Question #' . $q['id'])])->values()),
                qualQuestionIds: @js(collect($analysis)->where('isChartable', false)->where('isAnalyzable', true)->pluck('id')),
                allQualQuestions: @js(collect($analysis)->where('isChartable', false)->where('isAnalyzable', true)->map(fn($q) => ['id' => $q['id'], 'label' => $q['label'] ?? ('Question #' . $q['id'])])->values()),

                // Knowledge Base modal & scoped state
                kbRules: [],
                kbLoaded: false,
                showKbModal: false,
                kbScope: 'quantitative',
                newKbInstruction: '',
                addingKb: false,
                loadingKb: false,

                get quantKbCount() {
                    return this.kbRules.filter(r => r.content && r.content.startsWith('[Quantitative]')).length;
                },
                get qualKbCount() {
                    return this.kbRules.filter(r => r.content && r.content.startsWith('[Qualitative]')).length;
                },
                get inferentialKbCount() {
                    return this.kbRules.filter(r => r.content && r.content.startsWith('[Inferential]')).length;
                },
                get filteredKbRules() {
                    if (this.kbScope === 'qualitative') {
                        return this.kbRules.filter(r => r.content && r.content.startsWith('[Qualitative]'));
                    } else if (this.kbScope === 'quantitative') {
                        return this.kbRules.filter(r => r.content && r.content.startsWith('[Quantitative]'));
                    } else if (this.kbScope === 'inferential') {
                        return this.kbRules.filter(r => r.content && (r.content.startsWith('[Inferential]') || (!r.content.startsWith('[Quantitative]') && !r.content.startsWith('[Qualitative]') && !r.content.startsWith('[Book/Doc:'))));
                    }
                    return this.kbRules;
                },

                openKbModal(scope = null) {
                    this.kbScope = scope || (this.reportTab === 'qualitative' ? 'qualitative' : (this.reportTab === 'inferential' ? 'inferential' : 'quantitative'));
                    this.showKbModal = true;
                    if (!this.kbLoaded) this.loadKbRules();
                },

                async loadKbRules() {
                    this.loadingKb = true;
                    try {
                        const res = await fetch('/socius/knowledge-base');
                        if (res.ok) {
                            const data = await res.json();
                            this.kbRules = data.rules || [];
                            this.kbLoaded = true;
                        }
                    } catch (e) {
                        console.error('Failed to load KB rules:', e);
                    } finally {
                        this.loadingKb = false;
                    }
                },

                async addKbRule() {
                    if (!this.newKbInstruction.trim()) return;
                    this.addingKb = true;
                    const scope = this.kbScope;
                    let text = this.newKbInstruction.trim();
                    const tag = scope === 'qualitative' ? '[Qualitative]' : (scope === 'inferential' ? '[Inferential]' : '[Quantitative]');
                    if (!text.startsWith('[')) {
                        text = `${tag} ${text}`;
                    }
                    try {
                        const res = await fetch('/socius/knowledge-base', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                content: text,
                                scope: scope,
                                is_active: true
                            })
                        });
                        if (res.ok) {
                            const data = await res.json();
                            if (data.rule) {
                                this.kbRules.unshift(data.rule);
                                this.newKbInstruction = '';
                            }
                        }
                    } catch (e) {
                        console.error('Failed to add KB rule:', e);
                    } finally {
                        this.addingKb = false;
                    }
                },

                async toggleKbRule(rule) {
                    rule.is_active = !rule.is_active;
                    try {
                        await fetch(`/socius/knowledge-base/${rule.id}`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ is_active: rule.is_active })
                        });
                    } catch (e) {
                        console.error('Failed to toggle KB rule:', e);
                    }
                },

                async deleteKbRule(id) {
                    this.kbRules = this.kbRules.filter(r => r.id !== id);
                    try {
                        await fetch(`/socius/knowledge-base/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                    } catch (e) {
                        console.error('Failed to delete KB rule:', e);
                    }
                },

                async applySingleKbRule(rule) {
                    if (!rule || !rule.content) return;
                    const cleanText = rule.content.replace(/^\[(Quantitative|Qualitative|Inferential|Socius|General|Book\/Doc|Doc:[^\]]+)\]\s*/i, '');
                    this.showKbModal = false;

                    if (this.reportTab === 'qualitative') {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: @js(__('Applying Rule to Qualitative Analysis')),
                                text: cleanText,
                                icon: 'info',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000
                            });
                        }
                        await this.globalRefineQualitative(cleanText);
                    } else if (this.reportTab === 'inferential') {
                        const inferentialEl = document.querySelector('[x-data*="inferentialManager"]');
                        if (inferentialEl && window.Alpine) {
                            const infData = Alpine.$data(inferentialEl);
                            if (infData) {
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        title: @js(__('Applying Rule to Inferential Analysis')),
                                        text: cleanText,
                                        icon: 'info',
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 2000
                                    });
                                }
                                await infData.polishWithInstruction(cleanText);
                                return;
                            }
                        }
                    } else if (this.reportTab === 'analyse') {
                        const inputEl = document.getElementById('socius-prompt-input');
                        if (inputEl) {
                            inputEl.value = cleanText;
                            inputEl.dispatchEvent(new Event('input', { bubbles: true }));
                            inputEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            inputEl.focus();
                        }
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: @js(__('Instruction Loaded')),
                                text: @js(__('Loaded into Socius prompt input.')),
                                icon: 'success',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000
                            });
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: @js(__('Applying Rule to Quantitative Analysis')),
                                text: cleanText,
                                icon: 'info',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000
                            });
                        }
                        await this.globalRefineAll(cleanText);
                    }
                },

                async applyGlobalReview() {
                    if (!this.globalFeedback.trim()) return;
                    const feedback = this.globalFeedback.trim();
                    const currentTab = this.reportTab;
                    const tag = currentTab === 'qualitative' ? '[Qualitative]' : '[Quantitative]';
                    const scope = currentTab === 'qualitative' ? 'qualitative' : 'quantitative';

                    // Auto-save to Knowledge Base
                    try {
                        const kbRes = await fetch('/socius/knowledge-base', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                content: `${tag} ${feedback}`,
                                scope: scope,
                                is_active: true
                            })
                        });
                        if (kbRes.ok) {
                            const kbData = await kbRes.json();
                            if (kbData.rule) {
                                this.kbRules.unshift(kbData.rule);
                            }
                        }
                    } catch (e) {
                        console.error('Failed to auto-save to KB:', e);
                    }

                    if (currentTab === 'qualitative') {
                        await this.globalRefineQualitative(feedback);
                    } else {
                        await this.globalRefineAll(feedback);
                    }
                },

                pauseGlobalReview() {
                    if (!this.globalRefining) return;
                    this.globalRefinePaused = true;
                    this.globalRefineProgress = `${@js(__('Paused at question'))} ${this.globalRefineIndex + 1} / ${this.globalRefineTotal}. ${@js(__('Click Resume to continue.'))}`;
                },

                resumeGlobalReview() {
                    if (!this.globalRefining || !this.globalRefinePaused) return;
                    this.globalRefinePaused = false;
                    this.globalRefineProgress = `${@js(__('Resuming review...'))}`;
                },

                stopGlobalReview() {
                    this.globalRefineAbort = true;
                    this.globalRefinePaused = false;
                    this.globalRefineProgress = `${@js(__('Stopping review...'))}`;
                },

                async globalRefineAll(feedbackText = null) {
                    const feedback = feedbackText || this.globalFeedback.trim();
                    if (!feedback) return;
                    if (!this.isPremium) {
                        alert(@js(__('Premium subscription required for bulk refinement.')));
                        return;
                    }
                    this.globalRefining = true;
                    this.globalRefinePaused = false;
                    this.globalRefineAbort = false;
                    this.globalRefineActiveFeedback = feedback;
                    this.globalRefineProgress = '';
                    const list = this.allQuantQuestions;
                    this.globalRefineTotal = list.length;
                    this.globalRefineSuccessCount = 0;
                    this.globalRefineFailCount = 0;

                    for (let i = 0; i < list.length; i++) {
                        if (this.globalRefineAbort) break;

                        while (this.globalRefinePaused && !this.globalRefineAbort) {
                            await new Promise(resolve => setTimeout(resolve, 200));
                        }
                        if (this.globalRefineAbort) break;

                        this.globalRefineIndex = i;
                        const q = list[i];
                        const shortLabel = q.label.length > 28 ? q.label.substring(0, 25) + '...' : q.label;
                        this.globalRefineProgress = `${@js(__('Refining'))} "${shortLabel}" (${i + 1}/${this.globalRefineTotal})...`;

                        let card = window.quantInsightInstances && window.quantInsightInstances[q.id];
                        if (!card) {
                            for (let retry = 0; retry < 5 && !card; retry++) {
                                await new Promise(r => setTimeout(r, 100));
                                card = window.quantInsightInstances && window.quantInsightInstances[q.id];
                            }
                        }

                        if (card) {
                            try {
                                const timeoutPromise = new Promise((_, reject) =>
                                    setTimeout(() => reject(new Error('Question refinement timed out after 30s')), 30000)
                                );
                                await Promise.race([
                                    card.refineFromGlobal(this.globalRefineActiveFeedback, this.reportingStyle),
                                    timeoutPromise
                                ]);
                                this.globalRefineSuccessCount++;
                            } catch (e) {
                                console.error(`Refinement failed for question ${q.id}:`, e);
                                this.globalRefineFailCount++;
                            }
                        } else {
                            console.warn(`Quant insight card not found for ${q.id}`);
                        }

                        if (this.globalRefineAbort) break;
                        await new Promise(resolve => setTimeout(resolve, 600));
                    }

                    const wasAborted = this.globalRefineAbort;
                    this.globalRefining = false;
                    this.globalRefinePaused = false;
                    this.globalRefineAbort = false;

                    if (wasAborted) {
                        this.globalRefineProgress = `${@js(__('Stopped.'))} ${@js(__('Updated'))} ${this.globalRefineSuccessCount} / ${this.globalRefineTotal} ${@js(__('questions.'))}`;
                    } else {
                        this.globalFeedback = '';
                        this.globalRefineProgress = `${@js(__('Done. Updated'))} ${this.globalRefineSuccessCount} ${@js(__('questions.'))}` + (this.globalRefineFailCount > 0 ? ` (${this.globalRefineFailCount} ${@js(__('failed/skipped'))})` : '');
                    }
                    setTimeout(() => {
                        if (!this.globalRefining) {
                            this.globalRefineProgress = '';
                        }
                    }, 5000);
                },

                async globalRefineQualitative(feedbackText = null) {
                    const feedback = feedbackText || this.globalFeedback.trim();
                    if (!feedback) return;
                    if (!this.isPremium) {
                        alert(@js(__('Premium subscription required for bulk refinement.')));
                        return;
                    }
                    this.globalRefining = true;
                    this.globalRefinePaused = false;
                    this.globalRefineAbort = false;
                    this.globalRefineActiveFeedback = feedback;
                    this.globalRefineProgress = '';
                    const list = this.allQualQuestions;
                    this.globalRefineTotal = list.length;
                    this.globalRefineSuccessCount = 0;
                    this.globalRefineFailCount = 0;

                    for (let i = 0; i < list.length; i++) {
                        if (this.globalRefineAbort) break;

                        while (this.globalRefinePaused && !this.globalRefineAbort) {
                            await new Promise(resolve => setTimeout(resolve, 200));
                        }
                        if (this.globalRefineAbort) break;

                        this.globalRefineIndex = i;
                        const q = list[i];
                        const shortLabel = q.label.length > 28 ? q.label.substring(0, 25) + '...' : q.label;
                        this.globalRefineProgress = `${@js(__('Refining'))} "${shortLabel}" (${i + 1}/${this.globalRefineTotal})...`;

                        let card = window.qualInsightInstances && window.qualInsightInstances[q.id];
                        if (!card) {
                            for (let retry = 0; retry < 5 && !card; retry++) {
                                await new Promise(r => setTimeout(r, 100));
                                card = window.qualInsightInstances && window.qualInsightInstances[q.id];
                            }
                        }

                        if (card) {
                            try {
                                const timeoutPromise = new Promise((_, reject) =>
                                    setTimeout(() => reject(new Error('Qualitative refinement timed out after 30s')), 30000)
                                );
                                await Promise.race([
                                    card.refineFromGlobal(this.globalRefineActiveFeedback, this.reportingStyle),
                                    timeoutPromise
                                ]);
                                this.globalRefineSuccessCount++;
                            } catch (e) {
                                console.error(`Qualitative refinement failed for ${q.id}:`, e);
                                this.globalRefineFailCount++;
                            }
                        } else {
                            console.warn(`Qual insight card not found for ${q.id}`);
                        }

                        if (this.globalRefineAbort) break;
                        await new Promise(resolve => setTimeout(resolve, 600));
                    }

                    const wasAborted = this.globalRefineAbort;
                    this.globalRefining = false;
                    this.globalRefinePaused = false;
                    this.globalRefineAbort = false;

                    if (wasAborted) {
                        this.globalRefineProgress = `${@js(__('Stopped.'))} ${@js(__('Updated'))} ${this.globalRefineSuccessCount} / ${this.globalRefineTotal} ${@js(__('questions.'))}`;
                    } else {
                        this.globalFeedback = '';
                        this.globalRefineProgress = `${@js(__('Done. Updated'))} ${this.globalRefineSuccessCount} ${@js(__('questions.'))}` + (this.globalRefineFailCount > 0 ? ` (${this.globalRefineFailCount} ${@js(__('failed/skipped'))})` : '');
                    }
                    setTimeout(() => {
                        if (!this.globalRefining) {
                            this.globalRefineProgress = '';
                        }
                    }, 5000);
                },

                init() {
                    window.currentReportingStyle = this.reportingStyle;
                    this.loadKbRules();
                    this.$watch('reportingStyle', (val) => {
                        window.currentReportingStyle = val;
                        fetch(`{{ route('surveys.reporting-style', $survey->id) }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ reporting_style: val })
                        }).then(res => {
                            if (!res.ok) {
                                console.error('Failed to update reporting style');
                            } else {
                                for (const q of this.allQuantQuestions) {
                                    const card = window.quantInsightInstances && window.quantInsightInstances[q.id];
                                    if (card) {
                                        card.generate(true);
                                    }
                                }
                                for (const q of this.allQualQuestions) {
                                    const card = window.qualInsightInstances && window.qualInsightInstances[q.id];
                                    if (card) {
                                        card.generate();
                                    }
                                }
                            }
                        });
                    });

                    const applyTabStyles = (tab) => {
                        document.body.setAttribute('data-report-tab', tab);
                        const mainViewport = document.getElementById('main-viewport');
                        const footer = document.querySelector('footer');
                        if (tab === 'analyse' || tab === 'humanizer') {
                            if (mainViewport) {
                                mainViewport.style.setProperty('padding', '0', 'important');
                                mainViewport.style.setProperty('margin', '0', 'important');
                                mainViewport.style.setProperty('overflow', 'hidden', 'important');
                                mainViewport.style.setProperty('height', 'calc(100dvh - 4.1rem)', 'important');
                                mainViewport.style.setProperty('background-color', '#1e1e1e', 'important');
                                if (mainViewport.firstElementChild) {
                                    mainViewport.firstElementChild.style.setProperty('padding', '0', 'important');
                                    mainViewport.firstElementChild.style.setProperty('margin', '0', 'important');
                                }
                            }
                            if (footer) footer.style.setProperty('display', 'none', 'important');
                        } else {
                            if (mainViewport) {
                                mainViewport.style.removeProperty('padding');
                                mainViewport.style.removeProperty('margin');
                                mainViewport.style.removeProperty('overflow');
                                mainViewport.style.removeProperty('height');
                                mainViewport.style.removeProperty('background-color');
                                if (mainViewport.firstElementChild) {
                                    mainViewport.firstElementChild.style.removeProperty('padding');
                                    mainViewport.firstElementChild.style.removeProperty('margin');
                                }
                            }
                            if (footer) footer.style.removeProperty('display');
                        }
                    };

                    this.$watch('reportTab', (tab) => {
                        applyTabStyles(tab);
                        if (tab === 'analyse') {
                            this.$nextTick(() => {
                                const inputEl = document.getElementById('socius-prompt-input');
                                if (inputEl) {
                                    inputEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    inputEl.focus();
                                }
                            });
                        }
                    });

                    applyTabStyles(this.reportTab);

                    window.addEventListener('popstate', (e) => {
                        const params = new URLSearchParams(window.location.search);
                        const tabFromUrl = params.get('reportTab') || 'quantitative';
                        this.reportTab = tabFromUrl;
                    });
                },

                switchReportTab(tab) {
                    if (this.reportTab === tab) return;
                    this.reportTab = tab;
                    const url = new URL(window.location.href);
                    url.searchParams.set('reportTab', tab);
                    if (tab !== 'analyse') {
                        url.searchParams.delete('thread');
                    }
                    window.history.pushState({ reportTab: tab }, '', url);
                },

                goToHumanizer(text) {
                    this.humanizerOriginal = text;
                    this.humanizerResult = '';
                    this.customInstructions = '';
                    this.originalAnalysis = null;
                    this.humanizedAnalysis = null;
                    this.switchReportTab('humanizer');
                    this.$nextTick(() => {
                        this.analyzeHumanizerText();
                    });
                },

                async analyzeHumanizerText() {
                    if (!this.humanizerOriginal.trim()) return;
                    this.isAnalyzing = true;
                    try {
                        const response = await fetch(`{{ route('surveys.analyse.humanize', $survey->id) }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                text: this.humanizerOriginal,
                                analyze_only: true
                            })
                        });
                        const data = await response.json();
                        this.originalAnalysis = data.analysis;
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.isAnalyzing = false;
                    }
                },

                transferBack() {
                    if (!this.humanizerResult.trim()) return;
                    this.humanizerOriginal = this.humanizerResult;
                    this.humanizerResult = '';
                    this.humanizedAnalysis = null;
                    this.originalAnalysis = null;
                    this.$nextTick(() => { this.analyzeHumanizerText(); });
                },

                async humanizeAction() {
                    if (!this.humanizerOriginal.trim()) return;
                    this.isHumanizing = true;
                    this.humanizerResult = '';
                    this.humanizedAnalysis = null;
                    try {
                        const response = await fetch(`{{ route('surveys.analyse.humanize', $survey->id) }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                text: this.humanizerOriginal,
                                mode: this.humanizerMode,
                                intensity: this.humanizerIntensity,
                                custom_instructions: this.customInstructions
                            })
                        });
                        const data = await response.json();
                        this.humanizerResult = data.humanized_text;
                        this.originalAnalysis = data.original_analysis;
                        this.humanizedAnalysis = data.humanized_analysis;
                    } catch (e) {
                        alert('Humanizer error: ' + e.message);
                    } finally {
                        this.isHumanizing = false;
                    }
                }
            };
        };
    </script>

    <div :data-report-tab="reportTab"
        :class="(reportTab === 'analyse' || reportTab === 'humanizer') ? 'w-full h-full p-0 m-0 overflow-hidden' : 'space-y-12'"
        x-data="reportManager()">
        <!-- Sub Navigation -->
        <div x-show="reportTab !== 'analyse' && reportTab !== 'humanizer'"
            class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-10 border-b border-gray-100 pb-6">
            <div class="flex flex-wrap items-center gap-6">
                <!-- Descriptive Group -->
                <div class="flex flex-col gap-1.5">
                    <span
                        class="text-xs font-bold text-zinc-500 tracking-tight pl-1">{{ __('Descriptive Statistics') }}</span>
                    <div class="flex items-center gap-1 bg-gray-100/50 p-1 rounded-2xl w-fit">
                        <button @click="switchReportTab('quantitative')"
                            :class="reportTab === 'quantitative' ? 'bg-white text-[#2271b1] shadow-sm border border-gray-100' : 'text-gray-500 hover:text-gray-700'"
                            class="px-5 py-2 rounded-xl text-xs font-bold tracking-tight transition-all">
                            <i class="fa-solid fa-chart-column mr-2"></i>
                            {{ __('Quantitative') }}
                        </button>
                        <button @click="switchReportTab('qualitative')"
                            :class="reportTab === 'qualitative' ? 'bg-white text-[#2271b1] shadow-sm border border-gray-100' : 'text-gray-500 hover:text-gray-700'"
                            class="px-5 py-2 rounded-xl text-xs font-bold tracking-tight transition-all">
                            <i class="fa-solid fa-comments mr-2"></i>
                            {{ __('Qualitative') }}
                        </button>
                    </div>
                </div>

                <!-- Divider -->
                <div class="hidden lg:block h-10 w-[1px] bg-gray-200 self-end mb-1"></div>

                <!-- Inferential Group -->
                <div class="flex flex-col gap-1.5">
                    <span
                        class="text-xs font-bold text-zinc-500 tracking-tight pl-1">{{ __('Inferential Statistics') }}</span>
                    <div class="flex items-center gap-1 bg-gray-100/50 p-1 rounded-2xl w-fit">
                        <button @click="switchReportTab('inferential')"
                            :class="reportTab === 'inferential' ? 'bg-white text-[#2271b1] shadow-sm border border-gray-100' : 'text-gray-500 hover:text-gray-700'"
                            class="px-5 py-2 rounded-xl text-xs font-bold tracking-tight transition-all">
                            <i class="fa-solid fa-calculator mr-2"></i>
                            {{ __('Analyze') }}
                            @if(!auth()->check() || !auth()->user()->hasActiveSubscription())
                                <i class="fa-solid fa-lock ml-2 text-[10px] opacity-50"></i>
                            @endif
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @if(!isset($isSharedView) || !$isSharedView)
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open"
                            class="inline-flex items-center gap-2 px-5 py-3 bg-indigo-600 text-white rounded-xl font-black text-[10px]  tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                            <i class="fa-solid fa-file-arrow-down text-sm"></i>
                            {{ __('Export') }}
                            <i class="fa-solid fa-chevron-down text-[9px] transition-transform duration-200"
                                :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open" x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-44 bg-white border border-gray-100 rounded-2xl shadow-2xl shadow-gray-200/60 z-50 overflow-hidden"
                            style="display:none;">
                            <a href="{{ route('surveys.export_pdf', $survey) }}"
                                @click.prevent="window.exportReportWithSettings('pdf', '{{ $survey->id }}')"
                                class="flex items-center gap-3 px-4 py-3 text-xs font-semibold text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                                <i class="fa-solid fa-file-pdf text-red-500 w-4"></i>
                                {{ __('PDF Report') }}
                            </a>
                            <a href="{{ route('surveys.export_docx', $survey) }}"
                                @click.prevent="window.exportReportWithSettings('docx', '{{ $survey->id }}')"
                                class="flex items-center gap-3 px-4 py-3 text-xs font-semibold text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors border-t border-gray-50">
                                <i class="fa-solid fa-file-word text-blue-600 w-4"></i>
                                {{ __('DOCX Report') }}
                            </a>
                            @if(auth()->user()->hasActiveSubscription())
                                <a href="{{ route('surveys.export_xlsx', $survey) }}"
                                    class="flex items-center gap-3 px-4 py-3 text-xs font-semibold text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors border-t border-gray-50">
                                    <i class="fa-solid fa-file-excel text-green-600 w-4"></i>
                                    {{ __('Excel (.xlsx)') }}
                                </a>
                            @else
                                <div class="flex items-center gap-3 px-4 py-3 text-xs font-semibold text-gray-400 border-t border-gray-50 cursor-not-allowed"
                                    title="{{ __('Pro/Enterprise feature') }}">
                                    <i class="fa-solid fa-file-excel text-gray-300 w-4"></i>
                                    {{ __('Excel (.xlsx)') }}
                                    <i class="fa-solid fa-lock text-[9px] ml-auto opacity-50"></i>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div
                        class="px-6 py-3 bg-indigo-50 text-indigo-700 rounded-xl font-black text-[10px]  tracking-widest flex items-center gap-2">
                        <i class="fa-solid fa-lock"></i> {{ __('Read-Only Live View') }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Global Review & Knowledge Base Card -->
        <div x-show="(reportTab === 'quantitative' || reportTab === 'qualitative') && isPremium"
            class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-sm mb-10 space-y-5 animate-in fade-in duration-300"
            style="display: none;">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-4">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-2xl bg-[#2271b1]/10 text-[#2271b1] flex items-center justify-center font-bold shrink-0">
                        <i class="fa-solid fa-wand-magic-sparkles text-base"></i>
                    </div>
                    <div>
                        <h4 class="text-base font-bold text-gray-900 tracking-tight"
                            x-text="reportTab === 'qualitative' ? '{{ __('Global Qualitative Review') }}' : '{{ __('Global Quantitative Review') }}'">
                        </h4>
                        <p class="text-xs text-gray-500 font-medium">
                            <span
                                x-text="reportTab === 'qualitative' ? '{{ __('Apply instructions across all qualitative questions and auto-sync to Knowledge Base.') }}' : '{{ __('Apply instructions across all quantitative questions and auto-sync to Knowledge Base.') }}'"></span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="openKbModal(reportTab)"
                        class="px-4 py-2 bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-xs cursor-pointer"
                        title="{{ __('Open Knowledge Base') }}">
                        <i class="fa-solid fa-book-bookmark text-[#2271b1]"></i>
                        <span>{{ __('Knowledge Base') }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-[#2271b1]/10 text-[#2271b1] font-bold"
                            x-text="reportTab === 'qualitative' ? qualKbCount : quantKbCount"></span>
                    </button>
                </div>
            </div>

            <div class="space-y-4">
                <div class="relative w-full">
                    <textarea x-model="globalFeedback" :disabled="globalRefining" rows="3"
                        :placeholder="reportTab === 'qualitative' ? '{{ __('e.g., Use APA 7 style, focus on key recurring themes, keep summaries concise and highlight critical outliers...') }}' : '{{ __('e.g., Use APA 7 style, report percentages without raw frequencies, highlight top category and analyze major variations...') }}'"
                        @keydown.enter.exact.prevent="applyGlobalReview()"
                        class="w-full bg-white border-2 border-gray-200 text-sm sm:text-base font-medium rounded-2xl p-4 text-gray-800 placeholder-gray-400 focus:bg-white focus:ring-4 focus:ring-[#2271b1]/15 focus:border-[#2271b1] focus:outline-none transition-all disabled:bg-gray-100 disabled:text-gray-400 resize-y min-h-[96px] shadow-xs"></textarea>
                    <div class="flex items-center justify-between text-[11px] text-gray-400 px-1 mt-1">
                        <span>{{ __('Press Enter to apply, Shift + Enter for new line') }}</span>
                        <button type="button" x-show="globalFeedback.trim() && !globalRefining" @click="globalFeedback = ''"
                            class="text-gray-400 hover:text-gray-600 transition-colors cursor-pointer"
                            style="display: none;">
                            <i class="fa-solid fa-xmark mr-1"></i>{{ __('Clear') }}
                        </button>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-gray-100">
                    <!-- Progress & Status Message -->
                    <div class="flex items-center gap-2 min-h-[32px]">
                        <template x-if="globalRefining && !globalRefinePaused">
                            <span
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-blue-50 text-[#2271b1] border border-blue-100 animate-pulse">
                                <i class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                                <span x-text="globalRefineProgress || '{{ __('Refining questions...') }}'"></span>
                            </span>
                        </template>
                        <template x-if="globalRefining && globalRefinePaused">
                            <span
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                <i class="fa-solid fa-circle-pause text-xs"></i>
                                <span x-text="globalRefineProgress || '{{ __('Paused') }}'"></span>
                            </span>
                        </template>
                        <template x-if="!globalRefining && globalRefineProgress">
                            <span
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                <i class="fa-solid fa-circle-check text-xs"></i>
                                <span x-text="globalRefineProgress"></span>
                            </span>
                        </template>
                    </div>

                    <!-- Action Controls -->
                    <div class="flex items-center gap-2 shrink-0 ml-auto">
                        <!-- Apply to All Button -->
                        <button type="button" x-show="!globalRefining" @click="applyGlobalReview()"
                            :disabled="!globalFeedback.trim()"
                            class="px-6 py-2.5 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-xl text-xs font-bold transition-all disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-sm cursor-pointer">
                            <i class="fa-solid fa-wand-magic-sparkles text-xs"></i>
                            <span>{{ __('Apply to All') }}</span>
                        </button>

                        <!-- Pause Button (when refining & not paused) -->
                        <button type="button" x-show="globalRefining && !globalRefinePaused" @click="pauseGlobalReview()"
                            style="display: none;"
                            class="px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-sm cursor-pointer">
                            <i class="fa-solid fa-pause text-xs"></i>
                            <span>{{ __('Pause') }}</span>
                        </button>

                        <!-- Resume Button (when refining & paused) -->
                        <button type="button" x-show="globalRefining && globalRefinePaused" @click="resumeGlobalReview()"
                            style="display: none;"
                            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-sm cursor-pointer">
                            <i class="fa-solid fa-play text-xs"></i>
                            <span>{{ __('Resume') }}</span>
                        </button>

                        <!-- Stop Button (when refining) -->
                        <button type="button" x-show="globalRefining" @click="stopGlobalReview()" style="display: none;"
                            class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-sm cursor-pointer">
                            <i class="fa-solid fa-stop text-xs"></i>
                            <span>{{ __('Stop') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quantitative Content -->
        <!-- Quantitative Content -->
        <div x-show="reportTab === 'quantitative'" class="space-y-8 animate-in fade-in duration-500"
            x-data="chartManager()">
            @foreach($analysis as $item)
                @if($item['isChartable'])
                    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm space-y-6"
                        x-data="{ showStats: false }">
                        <div class="border-b border-gray-100 pb-6 space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <h4 class="text-lg font-black text-gray-900 leading-snug w-full">
                                    <span
                                        class="text-indigo-600 mr-2 opacity-30 text-base font-black">#{{ $loop->iteration }}</span>
                                    {{ $item['label'] }}
                                </h4>

                                @if(empty($item['isLikertLike']))
                                    <div class="flex items-center gap-2 shrink-0">
                                        @if(!empty($item['summary_stats']))
                                            <!-- Stats Toggle Button -->
                                            <button type="button" @click="showStats = !showStats"
                                                :class="showStats ? 'bg-[#2271b1] text-white border-[#2271b1]' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 border-gray-200'"
                                                class="text-xs font-bold rounded-xl px-3 py-2 border transition-colors shadow-sm"
                                                title="{{ __('Toggle Summary Statistics') }}">
                                                Stats
                                            </button>
                                        @endif

                                        <!-- Chart Type Dropdown -->
                                        <select @change="switchChartType('{{ $item['canvasId'] }}', $event.target.value)"
                                            :value="chartTypes['{{ $item['canvasId'] }}'] || 'bar'"
                                            class="bg-gray-100 text-gray-700 text-xs font-bold rounded-xl px-3 py-2 border border-gray-200 focus:ring-2 focus:ring-[#2271b1]">
                                            <option value="bar">📊 Bar Chart</option>
                                            <option value="horizontal">📐 Horizontal Bar</option>
                                            <option value="line">📈 Line Chart</option>
                                            <option value="area">🌊 Area Chart</option>
                                            <option value="pie">🥧 Pie Chart</option>
                                            <option value="doughnut">🍩 Doughnut Chart</option>
                                            <option value="polarArea">⭕ Polar Area</option>
                                            <option value="radar">🕸️ Radar Chart</option>
                                        </select>

                                        <!-- Palette Dropdown -->
                                        <select @change="switchColor('{{ $item['canvasId'] }}', $event.target.value)"
                                            :value="activeColors['{{ $item['canvasId'] }}'] || 'vibrant'"
                                            class="bg-gray-100 text-gray-700 text-xs font-bold rounded-xl px-3 py-2 border border-gray-200 focus:ring-2 focus:ring-[#2271b1]">
                                            <option value="vibrant">🎨 Vibrant</option>
                                            <option value="indigo">🟦 Indigo</option>
                                            <option value="emerald">🟩 Emerald</option>
                                            <option value="rose">🟥 Rose</option>
                                            <option value="amber">🟧 Amber</option>
                                            <option value="purple">🟪 Purple</option>
                                            <option value="greyscale">⬛ Greyscale</option>
                                        </select>

                                        <!-- Chart 3-Dot Action Dropdown -->
                                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                            <button type="button" @click="open = !open"
                                                class="w-9 h-9 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 flex items-center justify-center transition-colors shadow-sm"
                                                title="{{ __('Chart Actions') }}">
                                                <i class="fa-solid fa-ellipsis-vertical text-sm"></i>
                                            </button>
                                            <div x-show="open" x-cloak x-transition
                                                class="absolute right-0 mt-1 w-44 bg-white border border-gray-100 rounded-2xl shadow-xl z-30 p-1.5 space-y-0.5"
                                                style="display:none;">
                                                <button type="button"
                                                    @click="window.copyChartToClipboard('{{ $item['canvasId'] }}', $event.currentTarget); open = false;"
                                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-zinc-100 hover:text-indigo-600 rounded-xl transition-colors">
                                                    <i class="fa-solid fa-copy text-indigo-500 w-4 text-center"></i>
                                                    <span>{{ __('Copy Chart') }}</span>
                                                </button>
                                                <button type="button"
                                                    @click="window.exportChartToPng('{{ $item['canvasId'] }}', '{{ addslashes($item['label']) }}'); open = false;"
                                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-zinc-100 hover:text-indigo-600 rounded-xl transition-colors">
                                                    <i class="fa-solid fa-file-image text-emerald-600 w-4 text-center"></i>
                                                    <span>{{ __('Export PNG') }}</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-col gap-10">
                            @if(!empty($item['summary_stats']))
                                <!-- Summary Statistics Card -->
                                <div x-show="showStats" x-cloak x-transition
                                    class="bg-gradient-to-br from-slate-50 via-indigo-50/20 to-blue-50/30 rounded-2xl p-5 border border-indigo-100/60 shadow-inner">
                                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-gray-200/60">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-xs font-black text-gray-800 uppercase tracking-wider">{{ __('Summary Statistics') }}</span>
                                            <span
                                                class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-indigo-100 text-indigo-700">{{ __('Parametric & Non-Parametric') }}</span>
                                        </div>
                                        <span class="text-[11px] font-semibold text-gray-500">N =
                                            {{ number_format($item['summary_stats']['count']) }} {{ __('valid responses') }}</span>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
                                        <div class="bg-white rounded-xl p-3 border border-gray-100 shadow-xs">
                                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">{{ __('Mean') }}
                                            </div>
                                            <div class="text-sm font-black text-gray-900 mt-0.5">
                                                {{ $item['summary_stats']['prefix'] ?? '' }}{{ number_format($item['summary_stats']['mean'], 2) }}{{ !empty($item['summary_stats']['unit']) ? ' ' . $item['summary_stats']['unit'] : '' }}
                                            </div>
                                        </div>
                                        <div class="bg-white rounded-xl p-3 border border-gray-100 shadow-xs">
                                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">{{ __('Median') }}
                                            </div>
                                            <div class="text-sm font-black text-gray-900 mt-0.5">
                                                {{ $item['summary_stats']['prefix'] ?? '' }}{{ number_format($item['summary_stats']['median'], 2) }}{{ !empty($item['summary_stats']['unit']) ? ' ' . $item['summary_stats']['unit'] : '' }}
                                            </div>
                                        </div>
                                        <div class="bg-white rounded-xl p-3 border border-gray-100 shadow-xs">
                                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">{{ __('Std Dev') }}
                                            </div>
                                            <div class="text-sm font-black text-gray-900 mt-0.5">
                                                {{ number_format($item['summary_stats']['std_dev'], 2) }}
                                            </div>
                                        </div>
                                        <div class="bg-white rounded-xl p-3 border border-gray-100 shadow-xs">
                                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">{{ __('Min') }}
                                            </div>
                                            <div class="text-sm font-black text-gray-900 mt-0.5">
                                                {{ $item['summary_stats']['prefix'] ?? '' }}{{ number_format($item['summary_stats']['min'], 2) }}{{ !empty($item['summary_stats']['unit']) ? ' ' . $item['summary_stats']['unit'] : '' }}
                                            </div>
                                        </div>
                                        <div class="bg-white rounded-xl p-3 border border-gray-100 shadow-xs">
                                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">{{ __('Max') }}
                                            </div>
                                            <div class="text-sm font-black text-gray-900 mt-0.5">
                                                {{ $item['summary_stats']['prefix'] ?? '' }}{{ number_format($item['summary_stats']['max'], 2) }}{{ !empty($item['summary_stats']['unit']) ? ' ' . $item['summary_stats']['unit'] : '' }}
                                            </div>
                                        </div>
                                        <div class="bg-white rounded-xl p-3 border border-gray-100 shadow-xs">
                                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">
                                                {{ __('IQR (Q1–Q3)') }}
                                            </div>
                                            <div class="text-sm font-black text-gray-900 mt-0.5">
                                                {{ number_format($item['summary_stats']['iqr'], 2) }}
                                                <span
                                                    class="text-[10px] text-gray-400 font-normal block">({{ number_format($item['summary_stats']['q1'], 1) }}
                                                    – {{ number_format($item['summary_stats']['q3'], 1) }})</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if(empty($item['isLikertLike']))
                                <!-- Chart Canvas Area -->
                                <div
                                    class="h-96 w-full max-w-4xl mx-auto relative flex items-center justify-center bg-gray-50/30 rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-inner group/chart">
                                    <div
                                        class="chart-canvas-wrapper w-full h-full flex items-center justify-center transition-all duration-300">
                                        <canvas id="{{ $item['canvasId'] }}"></canvas>
                                    </div>
                                </div>
                            @endif

                            <!-- Table Area (Below Chart) -->
                            <div class="bg-white rounded-2xl border border-gray-100 relative z-20"
                                id="table-wrapper-{{ $item['canvasId'] }}">
                                <div
                                    class="px-6 py-4 bg-gray-50/50 border-b border-gray-100 flex justify-between items-center rounded-t-2xl">
                                    <div class="flex items-center gap-2.5">
                                        <span
                                            class="text-xs font-bold text-zinc-500 tracking-tight">{{ empty($item['isLikertLike']) ? __('Frequency Table') : __('Likert Matrix Table') }}</span>
                                        @if(empty($item['isLikertLike']))
                                            <span
                                                class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100/80">
                                                {{ __('Live Editable') }}
                                            </span>
                                            <button type="button" id="reset-btn-{{ $item['canvasId'] }}"
                                                @click="window.resetTableAndChart('{{ $item['canvasId'] }}')"
                                                class="table-reset-inline-btn px-2 py-0.5 rounded-md text-[10px] font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200/60 transition-all cursor-pointer shadow-2xs"
                                                style="display: none;" title="{{ __('Reset changes to original data') }}">
                                                {{ __('Reset') }}
                                            </button>
                                        @endif
                                    </div>

                                    <!-- Table 3-Dot Action Dropdown -->
                                    <div class="relative z-30" x-data="{ open: false }" @click.outside="open = false"
                                        data-html2canvas-ignore>
                                        <button type="button" @click="open = !open"
                                            class="w-8 h-8 rounded-xl bg-white border border-gray-200 hover:bg-gray-100 text-gray-600 flex items-center justify-center transition-colors shadow-sm"
                                            title="{{ __('Table Actions') }}">
                                            <i class="fa-solid fa-ellipsis-vertical text-xs"></i>
                                        </button>
                                        <div x-show="open" x-cloak x-transition
                                            class="absolute right-0 mt-1 w-40 bg-white border border-gray-100 rounded-2xl shadow-xl z-50 p-1.5 space-y-0.5"
                                            style="display:none;">
                                            <button type="button"
                                                @click="window.copyTableToClipboard('table-{{ $item['canvasId'] }}'); open = false;"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-zinc-100 hover:text-indigo-600 rounded-xl transition-colors">
                                                <i class="fa-solid fa-copy text-indigo-500 w-4 text-center"></i>
                                                <span>{{ __('Copy Data') }}</span>
                                            </button>
                                            <button type="button"
                                                @click="window.exportTableToCsv('table-{{ $item['canvasId'] }}', '{{ addslashes($item['label']) }}'); open = false;"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-zinc-100 hover:text-indigo-600 rounded-xl transition-colors">
                                                <i class="fa-solid fa-file-csv text-blue-600 w-4 text-center"></i>
                                                <span>{{ __('Export CSV') }}</span>
                                            </button>
                                            <button type="button"
                                                @click="window.exportTableToPng('table-wrapper-{{ $item['canvasId'] }}', '{{ addslashes($item['label']) }}'); open = false;"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-zinc-100 hover:text-indigo-600 rounded-xl transition-colors">
                                                <i class="fa-solid fa-file-image text-emerald-600 w-4 text-center"></i>
                                                <span>{{ __('Export PNG') }}</span>
                                            </button>
                                            <div class="my-1 border-t border-gray-100"></div>
                                            <button type="button"
                                                @click="window.resetTableAndChart('{{ $item['canvasId'] }}'); open = false;"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 rounded-xl transition-colors">
                                                <i class="fa-solid fa-rotate-left text-rose-500 w-4 text-center"></i>
                                                <span>{{ __('Reset to Original') }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-full rounded-b-2xl overflow-x-auto">
                                    <table class="w-full text-left border border-gray-200" id="table-{{ $item['canvasId'] }}"
                                        data-canvas-id="{{ $item['canvasId'] }}">
                                        @if(!empty($item['isLikertLike']))
                                            <thead class="bg-gray-50/70 border-b border-gray-200 shadow-sm">
                                                <tr
                                                    class="text-[9px] sm:text-[11px] font-black text-gray-700 tracking-wider border-b border-gray-200">
                                                    <th rowspan="2"
                                                        class="py-2 sm:py-3 px-2 sm:px-4 break-words border-r border-gray-200 align-bottom bg-gray-50/90 w-1/4 sm:w-1/3">
                                                        {{ __('Item') }}
                                                    </th>
                                                    @foreach($item['stats'] as $stat)
                                                        @if(!isset($stat['is_missing']) || !$stat['is_missing'])
                                                            <th colspan="2"
                                                                class="hidden sm:table-cell py-2 px-2 text-center border-b border-r border-gray-200 whitespace-nowrap font-bold text-gray-800">
                                                                {{ $stat['value'] }}
                                                            </th>
                                                            <th colspan="1"
                                                                class="sm:hidden py-1 px-1 text-center border-b border-r border-gray-200 text-[9px] font-bold text-gray-800 leading-tight">
                                                                {{ $stat['value'] }}
                                                            </th>
                                                        @endif
                                                    @endforeach
                                                </tr>
                                                <tr class="text-[9px] sm:text-[10px] font-bold text-gray-500 tracking-wider">
                                                    @foreach($item['stats'] as $stat)
                                                        @if(!isset($stat['is_missing']) || !$stat['is_missing'])
                                                            <th
                                                                class="hidden sm:table-cell py-1.5 px-3 text-center border-r border-gray-200 bg-gray-50/50">
                                                                Frequency</th>
                                                            <th
                                                                class="hidden sm:table-cell py-1.5 px-3 text-center border-r border-gray-200 bg-gray-50/50">
                                                                %</th>
                                                            <th
                                                                class="sm:hidden py-1 px-1 text-center border-r border-gray-200 bg-gray-50/50 text-[8px]">
                                                                Freq (%)</th>
                                                        @endif
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                @if(!empty($item['likert_matrix_rows']))
                                                    @foreach($item['likert_matrix_rows'] as $matrixRow)
                                                        <tr class="hover:bg-gray-50/30 transition-colors">
                                                            <td
                                                                class="py-2 sm:py-3 px-2 sm:px-4 text-[10px] sm:text-xs font-semibold text-gray-800 break-words leading-tight border-r border-gray-200">
                                                                {{ $matrixRow['label'] }}
                                                            </td>
                                                            @foreach($matrixRow['stats'] as $stat)
                                                                @if(!isset($stat['is_missing']) || !$stat['is_missing'])
                                                                    @php
                                                                        $totalFreqLikert = array_sum(array_column(array_filter($matrixRow['stats'], fn($s) => !isset($s['is_missing']) || !$s['is_missing']), 'count'));
                                                                        $percentLikert = $totalFreqLikert > 0 ? ($stat['count'] / $totalFreqLikert) * 100 : 0;
                                                                    @endphp
                                                                    <td
                                                                        class="hidden sm:table-cell py-3 px-2 text-center text-xs font-normal text-gray-900 border-r border-gray-200">
                                                                        {{ number_format($stat['count']) }}
                                                                    </td>
                                                                    <td
                                                                        class="hidden sm:table-cell py-3 px-2 text-center text-xs font-normal text-gray-900 border-r border-gray-200">
                                                                        {{ number_format($percentLikert, 1) }}%
                                                                    </td>
                                                                    <td
                                                                        class="sm:hidden py-2 px-1 text-center text-[10px] font-normal text-gray-900 border-r border-gray-200 whitespace-nowrap">
                                                                        {{ number_format($stat['count']) }} <span
                                                                            class="text-gray-500">({{ number_format($percentLikert, 0) }}%)</span>
                                                                    </td>
                                                                @endif
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                @else
                                                    <tr class="hover:bg-gray-50/30 transition-colors">
                                                        <td
                                                            class="py-2 sm:py-3 px-2 sm:px-4 text-[10px] sm:text-xs font-normal text-gray-700 break-words leading-tight border-r border-gray-200">
                                                            {{ $item['label'] }}
                                                        </td>
                                                        @foreach($item['stats'] as $stat)
                                                            @if(!isset($stat['is_missing']) || !$stat['is_missing'])
                                                                @php
                                                                    $totalFreqLikert = array_sum(array_column(array_filter($item['stats'], fn($s) => !isset($s['is_missing']) || !$s['is_missing']), 'count'));
                                                                    $percentLikert = $totalFreqLikert > 0 ? ($stat['count'] / $totalFreqLikert) * 100 : 0;
                                                                @endphp
                                                                <td
                                                                    class="hidden sm:table-cell py-3 px-2 text-center text-xs font-normal text-gray-900 border-r border-gray-200">
                                                                    {{ number_format($stat['count']) }}
                                                                </td>
                                                                <td
                                                                    class="hidden sm:table-cell py-3 px-2 text-center text-xs font-normal text-gray-900 border-r border-gray-200">
                                                                    {{ number_format($percentLikert, 1) }}%
                                                                </td>
                                                                <td
                                                                    class="sm:hidden py-2 px-1 text-center text-[10px] font-normal text-gray-900 border-r border-gray-200 whitespace-nowrap">
                                                                    {{ number_format($stat['count']) }} <span
                                                                        class="text-gray-500">({{ number_format($percentLikert, 0) }}%)</span>
                                                                </td>
                                                            @endif
                                                        @endforeach
                                                    </tr>
                                                @endif
                                            </tbody>
                                        @else
                                            <thead class="bg-gray-50/70 border-b border-gray-200 shadow-sm">
                                                <tr
                                                    class="text-[9px] sm:text-[11px] font-black text-gray-700 tracking-wider border-b border-gray-200">
                                                    <th class="py-2 sm:py-3 px-2 sm:px-4 break-words border-r border-gray-200">
                                                        {{ __('Value') }}
                                                    </th>
                                                    <th class="py-2 sm:py-3 px-2 sm:px-4 text-right border-r border-gray-200">
                                                        <span class="hidden sm:inline">{{ __('Frequency') }}</span>
                                                        <span class="sm:hidden">{{ __("Freq") }}</span>
                                                    </th>
                                                    <th class="py-2 sm:py-3 px-2 sm:px-4 text-right border-r border-gray-200">
                                                        <span class="hidden sm:inline">{{ __('Percent') }}</span>
                                                        <span class="sm:hidden">%</span>
                                                    </th>
                                                    <th class="py-2 sm:py-3 px-2 sm:px-4 text-right border-r border-gray-200">
                                                        <span class="hidden sm:inline">{{ __('Valid Percent') }}</span>
                                                        <span class="sm:hidden">{{ __("Valid %") }}</span>
                                                    </th>
                                                    <th class="py-2 sm:py-3 px-2 sm:px-4 text-right border-r border-gray-200">
                                                        <span class="hidden sm:inline">{{ __('Cumulative Percent') }}</span>
                                                        <span class="sm:hidden">{{ __("Cumulative %") }}</span>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                @php 
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    $totalFreq = 0;
                                                    $validFreq = 0;
                                                    // First pass to get valid total
                                                    foreach ($item['stats'] as $s) {
                                                        if (!isset($s['is_missing']) || !$s['is_missing']) {
                                                            $validFreq += $s['count'];
                                                        }
                                                        $totalFreq += $s['count'];
                                                    }
                                                    if ($validFreq === 0)
                                                        $validFreq = $totalFreq;

                                                    $cumulativePerc = 0;
                                                @endphp
                                                @foreach($item['stats'] as $stat)
                                                    @php
                                                        $isMissing = isset($stat['is_missing']) && $stat['is_missing'];
                                                        if ($isMissing && $stat['count'] == 0)
                                                            continue; // Omit zero-count missing rows

                                                        $percent = $totalFreq > 0 ? ($stat['count'] / $totalFreq) * 100 : 0;

                                                        if ($isMissing) {
                                                            $validPercent = null;
                                                            $cumPercentDisplay = '-';
                                                        } else {
                                                            $validPercent = $validFreq > 0 ? ($stat['count'] / $validFreq) * 100 : 0;
                                                            $cumulativePerc += $validPercent;
                                                            $cumPercentDisplay = number_format($cumulativePerc, 1) . '%';
                                                        }
                                                    @endphp
                                                    <tr class="hover:bg-gray-50/50 transition-colors border-b border-gray-100 freq-row {{ $isMissing ? 'bg-amber-50/20' : '' }}"
                                                        data-index="{{ $loop->index }}" data-is-missing="{{ $isMissing ? '1' : '0' }}"
                                                        data-original-value="{{ $stat['value'] }}"
                                                        data-original-count="{{ $stat['count'] }}">
                                                        <td
                                                            class="py-2 sm:py-2.5 px-2 sm:px-4 text-[10px] sm:text-xs font-medium text-gray-800 break-words leading-tight border-r border-gray-200">
                                                            @if(!$isMissing)
                                                                <span
                                                                    class="freq-value-cell inline-block w-full outline-none border-b border-dashed border-gray-300 hover:border-indigo-400 hover:bg-indigo-50/50 focus:border-indigo-500 focus:bg-indigo-50/80 focus:ring-1 focus:ring-indigo-400 rounded px-1.5 py-0.5 transition-all cursor-text"
                                                                    contenteditable="true" spellcheck="false"
                                                                    oninput="window.onFreqTableChange('{{ $item['canvasId'] }}')"
                                                                    onblur="window.onFreqTableChange('{{ $item['canvasId'] }}')">{{ $stat['value'] }}</span>
                                                            @else
                                                                <span class="text-amber-800 italic font-semibold">{{ $stat['value'] }}</span>
                                                            @endif
                                                        </td>
                                                        <td
                                                            class="py-2 sm:py-2.5 px-2 sm:px-4 text-right text-[10px] sm:text-xs font-semibold text-gray-900 border-r border-gray-200">
                                                            <span
                                                                class="freq-count-cell inline-block outline-none border-b border-dashed border-gray-300 hover:border-indigo-400 hover:bg-indigo-50/50 focus:border-indigo-500 focus:bg-indigo-50/80 focus:ring-1 focus:ring-indigo-400 rounded px-1.5 py-0.5 transition-all cursor-text min-w-[2.5rem]"
                                                                contenteditable="true" spellcheck="false"
                                                                oninput="window.onFreqTableChange('{{ $item['canvasId'] }}')"
                                                                onblur="window.onFreqTableChange('{{ $item['canvasId'] }}')">{{ number_format($stat['count']) }}</span>
                                                        </td>
                                                        <td
                                                            class="py-2 sm:py-2.5 px-2 sm:px-4 text-right text-[10px] sm:text-xs font-medium text-gray-900 border-r border-gray-200 freq-percent-cell">
                                                            {{ number_format($percent, 1) }}%
                                                        </td>
                                                        <td
                                                            class="py-2 sm:py-2.5 px-2 sm:px-4 text-right text-[10px] sm:text-xs font-normal text-gray-900 border-r border-gray-200 freq-valid-percent-cell">
                                                            {{ $validPercent !== null ? number_format($validPercent, 1) . '%' : '-' }}
                                                        </td>
                                                        <td
                                                            class="py-2 sm:py-2.5 px-2 sm:px-4 text-right text-[10px] sm:text-xs font-normal text-gray-900 border-r border-gray-200 freq-cum-percent-cell">
                                                            {{ $cumPercentDisplay }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="bg-gray-50/50 border-t-2 border-gray-200">
                                                <tr class="font-normal text-gray-900 text-[10px] sm:text-[11px]">
                                                    <td
                                                        class="py-2 sm:py-3 px-2 sm:px-4 tracking-wider border-r border-gray-200 font-bold">
                                                        {{ __('Total') }}
                                                    </td>
                                                    <td
                                                        class="py-2 sm:py-3 px-2 sm:px-4 text-right border-r border-gray-200 font-bold text-gray-900 freq-total-cell">
                                                        {{ number_format($totalFreq) }}
                                                    </td>
                                                    <td class="py-2 sm:py-3 px-2 sm:px-4 text-right border-r border-gray-200 font-bold">
                                                        100.0%
                                                    </td>
                                                    <td class="py-2 sm:py-3 px-2 sm:px-4 text-right border-r border-gray-200 font-bold">
                                                        100.0%
                                                    </td>
                                                    <td class="py-2 sm:py-3 px-2 sm:px-4 text-right border-r border-gray-200"></td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            </div>

                            @if($canAnalyze)
                                <x-ai-quant-insight-card :question-id="$item['id']" :survey-id="$survey->id" :stats="$item['stats']" />
                            @else
                                <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 text-center mt-6">
                                    <p class="text-xs font-bold text-zinc-500 tracking-tight mb-1">{{ __('Quantitative AI Insights') }}
                                    </p>
                                    <p class="text-xs text-gray-500 font-medium">
                                        {{ __('Upgrade to Pro to unlock automated trend interpretation for numerical data.') }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <div x-show="reportTab === 'qualitative'" class="space-y-12 animate-in fade-in duration-500" style="display: none;">
            @php $qualitativeQuestions = collect($analysis)->where('isChartable', false)->where('isAnalyzable', true); @endphp
            @if($qualitativeQuestions->isEmpty())
                <div
                    class="bg-white rounded-3xl p-12 sm:p-16 text-center border border-gray-100 shadow-sm max-w-2xl mx-auto space-y-4">
                    <div
                        class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center text-2xl mx-auto border border-indigo-100/80">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base sm:text-lg font-black text-gray-900 tracking-tight">
                            {{ __('No Qualitative Questions Found') }}
                        </h3>
                        <p class="text-xs sm:text-sm text-gray-500 max-w-md mx-auto leading-relaxed">
                            {{ __('This survey contains only structured quantitative and choice-based questions. All distributions, charts, and interpretations are available under the Quantitative tab.') }}
                        </p>
                    </div>
                    <div class="pt-2">
                        <button type="button" @click="switchReportTab('quantitative')"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-xl text-xs font-bold transition-all shadow-xs">
                            <i class="fa-solid fa-chart-simple"></i>
                            <span>{{ __('View Quantitative Report') }}</span>
                        </button>
                    </div>
                </div>
            @else
                @foreach($analysis as $item)
                    @if(!$item['isChartable'] && !empty($item['isAnalyzable']))
                        @php
                            $rawDetailedResps = $item['detailed_responses'] ?? [];
                            $detailedResps = array_values(array_filter($rawDetailedResps, function ($resp) {
                                $v = trim((string) ($resp['value'] ?? ''));
                                $cl = strtolower($v);
                                if ($v === '' || preg_match('/^[\s\-\.\,\_]*$/u', $v) || in_array($cl, ['n/a', 'na', 'none', 'nil', '-', '--', '---', '[missing / skipped]'])) {
                                    return false;
                                }
                                return true;
                            }));

                            $rawAnswers = $item['answers'] ?? [];
                            $filteredAnswers = array_values(array_filter($rawAnswers, function ($ans) {
                                if ($ans === null)
                                    return false;
                                $v = is_array($ans) ? implode(', ', $ans) : trim((string) $ans);
                                $cl = strtolower($v);
                                if ($v === '' || preg_match('/^[\s\-\.\,\_]*$/u', $v) || in_array($cl, ['n/a', 'na', 'none', 'nil', '-', '--', '---', '[missing / skipped]'])) {
                                    return false;
                                }
                                return true;
                            }));

                            $totalAnswersCount = count($detailedResps) > 0 ? count($detailedResps) : count($filteredAnswers);
                            $isAudioVideo = !empty($item['is_audio_video']);
                            $transcribedCount = $item['transcribed_count'] ?? 0;
                            $topKeywords = $item['top_keywords'] ?? [];
                            $sentiment = $item['sentiment'] ?? [];
                            $totSent = array_sum($sentiment);
                            $posPct = $totSent > 0 ? round(($sentiment['positive'] / $totSent) * 100) : 0;
                            $neuPct = $totSent > 0 ? round(($sentiment['neutral'] / $totSent) * 100) : 0;
                            $negPct = $totSent > 0 ? round(($sentiment['negative'] / $totSent) * 100) : 0;

                            $questionIdStr = (string) ($item['id'] ?? $loop->index);
                            $surveyIdVal = (string) ($item['survey_id'] ?? $survey->id);
                        @endphp
                        <div class="bg-white rounded-3xl p-6 sm:p-8 md:p-10 shadow-sm border border-gray-100 space-y-6"
                            id="qual-card-{{ $loop->index }}">

                            <!-- Question Header & Badges -->
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-100 pb-6">
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold bg-zinc-100 text-zinc-700">
                                            #{{ $loop->iteration }}
                                        </span>
                                        @if($isAudioVideo)
                                            <span
                                                class="px-2.5 py-0.5 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 flex items-center gap-1.5">
                                                <i class="fa-solid fa-microphone"></i> {{ __('Audio / Video Responses') }}
                                            </span>
                                        @endif
                                    </div>
                                    <h4 class="text-lg sm:text-xl font-bold text-gray-900 tracking-tight leading-snug">
                                        {{ $item['label'] }}
                                    </h4>
                                </div>

                                <!-- Summary KPI Badges -->
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span
                                        class="px-3 py-1.5 rounded-xl text-xs font-bold bg-gray-50 text-gray-700 border border-gray-200">
                                        {{ $totalAnswersCount }} {{ __('Entries') }}
                                    </span>
                                    @if($isAudioVideo)
                                        <span
                                            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                            {{ $transcribedCount }}/{{ $totalAnswersCount }} {{ __('Transcribed') }}
                                            ({{ $totalAnswersCount > 0 ? round(($transcribedCount / $totalAnswersCount) * 100) : 0 }}%)
                                        </span>
                                    @endif
                                    @if($totSent > 0)
                                        <span
                                            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            {{ $posPct }}% {{ __('Positive') }}
                                        </span>
                                        <span
                                            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-gray-50 text-gray-700 border border-gray-200">
                                            {{ $neuPct }}% {{ __('Neutral') }}
                                        </span>
                                        <span
                                            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            {{ $negPct }}% {{ __('Critical') }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Single Unified Analytical Results Card -->
                            <x-ai-insight-card :question-id="$item['id']" :question-title="$item['label']"
                                :survey-id="$item['survey_id'] ?? $survey->id" :index="$loop->index" />
                        </div>
                    @endif
                @endforeach
            @endif
        </div>


        @if(!isset($isSharedView) || !$isSharedView)
            <div x-show="reportTab === 'analyse'" class="w-full h-full max-w-full overflow-hidden bg-[#1e1e1e] p-0 m-0">
                @include('surveys.partials.report_analyse')
            </div>
            <div x-show="reportTab === 'humanizer'" class="w-full h-full max-w-full overflow-hidden bg-[#1e1e1e] p-0 m-0"
                style="display: none;">
                @include('surveys.partials.report_humanizer')
            </div>
        @endif




        <!-- Inferential Content -->
        <div x-show="reportTab === 'inferential'" class="space-y-6 animate-in fade-in duration-500" style="display: none;">
            @php
                $infQuestions = $inferentialVariables ?? $analysis;
                $likertStandardLabels = [
                    '1' => '1 - Strongly Disagree',
                    '2' => '2 - Disagree',
                    '3' => '3 - Neutral',
                    '4' => '4 - Agree',
                    '5' => '5 - Strongly Agree',
                ];
                $questionsMap = [];
                foreach ($analysis ?? [] as $q) {
                    $qId = (string) ($q['id'] ?? '');
                    $categories = [];
                    $categoryOptions = [];
                    if (!empty($q['stats'])) {
                        foreach ($q['stats'] as $st) {
                            $valStr = trim((string) ($st['value'] ?? ''));
                            $labelStr = trim((string) ($st['label'] ?? ($st['option'] ?? '')));
                            if (empty($st['is_missing']) && !in_array($valStr, ['Missing', '[Missing / Skipped]', 'Skipped', ''], true) && ($st['count'] ?? 0) > 0) {
                                $categories[] = $valStr;
                                $displayLabel = ($labelStr !== '' && $labelStr !== $valStr) ? ($valStr . ' - ' . $labelStr) : ($likertStandardLabels[$valStr] ?? $valStr);
                                $categoryOptions[] = [
                                    'value' => $valStr,
                                    'label' => $displayLabel,
                                ];
                            }
                        }
                    }
                    $qDef = [
                        'id' => $qId,
                        'label' => $q['label'] ?? '',
                        'type' => $q['type'] ?? 'question',
                        'answered_count' => $q['answered_count'] ?? null,
                        'categories' => $categories,
                        'category_options' => $categoryOptions,
                    ];
                    $questionsMap[$qId] = $qDef;
                    $cleanId = preg_replace('/^(qual-)?chart-/', '', $qId);
                    $questionsMap[$cleanId] = $qDef;
                    $questionsMap['chart-' . $cleanId] = $qDef;

                    if (!empty($q['likert_matrix_rows'])) {
                        foreach ($q['likert_matrix_rows'] as $subIdx => $subRow) {
                            $rKey = $subRow['value'] ?? '';
                            $subId = $qId . '__' . $rKey;
                            $cleanSubId = $cleanId . '__' . $rKey;
                            $rawSubLabel = $subRow['label'] ?? '';
                            $cleanSubText = preg_replace('/^(\d+[\.\:\)\s]+|\([a-zA-Z0-9]+\)\s*|[a-zA-Z][\.\:\)\s]+)\s*/', '', $rawSubLabel);

                            $subCategories = [];
                            $subCategoryOptions = [];
                            if (!empty($subRow['stats'])) {
                                foreach ($subRow['stats'] as $rst) {
                                    $rv = trim((string) ($rst['value'] ?? ''));
                                    $rl = trim((string) ($rst['label'] ?? ''));
                                    if ($rv !== '') {
                                        $subCategories[] = $rv;
                                        $optTxt = ($rl !== '' && $rl !== $rv) ? ($rv . ' - ' . $rl) : ($likertStandardLabels[$rv] ?? $rv);
                                        $subCategoryOptions[] = [
                                            'value' => $rv,
                                            'label' => $optTxt,
                                        ];
                                    }
                                }
                            }
                            if (empty($subCategories)) {
                                $subCategories = ['1', '2', '3', '4', '5'];
                                $subCategoryOptions = [
                                    ['value' => '1', 'label' => '1 - Strongly Disagree'],
                                    ['value' => '2', 'label' => '2 - Disagree'],
                                    ['value' => '3', 'label' => '3 - Neutral'],
                                    ['value' => '4', 'label' => '4 - Agree'],
                                    ['value' => '5', 'label' => '5 - Strongly Agree'],
                                ];
                            }

                            $subDef = [
                                'id' => $subId,
                                'label' => ($q['label'] ?? '') . ' - ' . $rawSubLabel,
                                'parent_label' => $q['label'] ?? '',
                                'sub_label' => $rawSubLabel,
                                'clean_sub_label' => $cleanSubText,
                                'type' => 'likert_item',
                                'answered_count' => $subRow['answered_count'] ?? ($q['answered_count'] ?? null),
                                'categories' => $subCategories,
                                'category_options' => $subCategoryOptions,
                            ];
                            $questionsMap[$subId] = $subDef;
                            $questionsMap[$cleanSubId] = $subDef;
                            $questionsMap['chart-' . $cleanSubId] = $subDef;
                        }
                    }
                }
                $scaleItemsMap = collect($inferentialVariables ?? [])->keyBy('id')->map(function ($q) {
                    return [
                        'id' => (string) ($q['id'] ?? ''),
                        'label' => $q['label'] ?? '',
                        'type' => $q['type'] ?? 'scale_item',
                    ];
                })->toArray();

                $scaleGroups = [];
                $standaloneScaleItems = [];
                $allEligibleScaleIds = [];

                foreach ($inferentialVariables ?? [] as $item) {
                    $type = strtolower($item['type'] ?? '');
                    $isScale = !empty($item['isMatrixItem'])
                        || !empty($item['isScaleItem'])
                        || in_array($type, ['likert', 'rating', 'scale', 'slider', 'number', 'range', 'star_rating', 'nps', 'likert_item', 'matrix_item'])
                        || str_contains($type, 'likert')
                        || str_contains($type, 'rating')
                        || str_contains($type, 'scale');

                    if (empty($item['isChartable']) || !empty($item['isMatrixParent']) || !$isScale) {
                        continue;
                    }

                    $allEligibleScaleIds[] = (string) $item['id'];

                    if (!empty($item['isMatrixItem']) && !empty($item['parentLabel'])) {
                        $parentKey = $item['parentFieldName'] ?? $item['parentLabel'];
                        if (!isset($scaleGroups[$parentKey])) {
                            $scaleGroups[$parentKey] = [
                                'label' => $item['parentLabel'],
                                'items' => []
                            ];
                        }
                        $scaleGroups[$parentKey]['items'][] = $item;
                    } else {
                        $standaloneScaleItems[] = $item;
                    }
                }

                if (empty($scaleGroups) && empty($standaloneScaleItems)) {
                    foreach ($inferentialVariables ?? [] as $item) {
                        if (!empty($item['isChartable']) && empty($item['isMatrixParent'])) {
                            $standaloneScaleItems[] = $item;
                            $allEligibleScaleIds[] = (string) $item['id'];
                        }
                    }
                }
            @endphp
            <div x-data="inferentialManager({{ $savedInferentialTests->toJson() }})" class="space-y-6">

                <div class="flex flex-col lg:flex-row gap-6 items-start">
                    <!-- Left Sidebar (History/Manager) -->
                    <div x-show="sidebarOpen" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 -translate-x-4"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        class="w-full lg:w-64 shrink-0 bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden sticky top-8"
                        style="display: none;">
                        <div class="p-5 border-b border-gray-100 bg-indigo-50/30 flex justify-between items-center">
                            <div>
                                <h4 class="text-xs font-black text-indigo-900 tracking-tight">{{ __('Saved Analyses') }}
                                </h4>
                                <p class="text-[10px] text-gray-500 font-bold"
                                    x-text="'In Report: ' + (savedTests.filter(t => t.is_included_in_report).length) + ' / ' + savedTests.length"></p>
                            </div>
                            <div class="flex items-center gap-1">
                                <button @click="resetForm()"
                                    class="w-7 h-7 flex items-center justify-center bg-indigo-100 text-indigo-600 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors"
                                    title="{{ __('New Test') }}">
                                    <i class="fa-solid fa-plus text-xs"></i>
                                </button>
                                <button @click="sidebarOpen = false"
                                    class="w-7 h-7 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors"
                                    title="{{ __('Close') }}">
                                    <i class="fa-solid fa-xmark text-xs"></i>
                                </button>
                            </div>
                        </div>
                        <div class="p-3 max-h-[60vh] overflow-y-auto custom-scrollbar space-y-2.5 bg-gray-50/50">
                            <template x-if="savedTests.length === 0">
                                <div class="text-center py-8 opacity-60">
                                    <i class="fa-solid fa-flask text-xl text-gray-300 mb-2"></i>
                                    <p class="text-[10px] font-black text-gray-500 tracking-widest">
                                        {{ __('No tests saved yet') }}
                                    </p>
                                </div>
                            </template>
                            <template x-for="test in savedTests" :key="test.id">
                                <div class="bg-white border border-gray-200 rounded-2xl p-3 shadow-sm hover:border-indigo-300 transition-all cursor-pointer group"
                                    @click="loadTest(test)"
                                    :class="{'border-indigo-500 ring-2 ring-indigo-100 bg-indigo-50/10': loadedTestId === test.id}">
                                    <div class="flex justify-between items-center mb-1.5 gap-1.5">
                                        <span
                                            class="px-2 py-0.5 bg-indigo-50 text-indigo-700 text-[8px] font-black uppercase tracking-widest rounded-md"
                                            x-text="formatMethod(test.method)"></span>
                                        <div class="flex items-center gap-1">
                                            <button type="button" @click.stop="toggleReportInclusion(test)"
                                                class="text-[8px] font-extrabold px-1.5 py-0.5 rounded transition-all flex items-center gap-1 shadow-2xs cursor-pointer"
                                                :class="test.is_included_in_report ? 'bg-[#2271b1] text-white border border-[#1b5b8d]' : 'bg-gray-100 text-gray-500 hover:bg-gray-200 border border-gray-200'"
                                                :title="test.is_included_in_report ? '{{ __('Included in published report (Click to exclude)') }}' : '{{ __('Draft only (Click to include in report)') }}'">
                                                <i :class="test.is_included_in_report ? 'fa-solid fa-check text-[7px]' : 'fa-solid fa-plus text-[7px]'"></i>
                                                <span x-text="test.is_included_in_report ? '{{ __('In Report') }}' : '{{ __('Draft') }}'"></span>
                                            </button>
                                            <button @click.stop="deleteSavedTest(test.id)"
                                                class="text-gray-300 hover:text-red-500 transition-colors p-1"
                                                title="{{ __('Delete saved test') }}">
                                                <i class="fa-solid fa-trash-alt text-[10px]"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <h5 class="text-xs font-bold text-gray-800 line-clamp-2 leading-tight"
                                        x-text="test.title"></h5>
                                    <p class="text-[9px] text-gray-500 mt-1 font-medium" x-text="formatTestVariables(test)">
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Right Main Area -->
                    <div class="w-full" :class="sidebarOpen ? 'lg:w-[calc(100%-17rem)]' : 'w-full'">
                        <div
                            class="bg-white rounded-3xl p-4 sm:p-6 md:p-8 border border-gray-100 shadow-sm relative overflow-hidden space-y-6 sm:space-y-8">

                            <!-- Header Area -->
                            <div class="border-b border-gray-50 pb-6 flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
                                <div class="flex items-center gap-3">
                                    <button type="button" @click="sidebarOpen = !sidebarOpen"
                                        class="w-9 h-9 rounded-xl bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 border border-gray-200 text-gray-700 flex items-center justify-center transition-all shadow-sm shrink-0"
                                        :title="sidebarOpen ? '{{ __('Hide History Sidebar') }}' : '{{ __('Open History Sidebar') }}'">
                                        <i class="fa-solid text-sm"
                                            :class="sidebarOpen ? 'fa-xmark' : 'fa-bars-staggered'"></i>
                                    </button>
                                    <div>
                                        <h4 class="text-xl font-black text-gray-900 tracking-tight"
                                            x-text="loadedTestId ? '{{ __('View Saved Analysis') }}' : '{{ __('New Inferential Test') }}'">
                                        </h4>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ __('Run significance tests, correlations and regressions on your survey responses.') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <template x-if="loadedTestId && savedTests.find(t => t.id === loadedTestId)">
                                        <button type="button" @click="toggleReportInclusion(savedTests.find(t => t.id === loadedTestId))"
                                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 border shadow-2xs cursor-pointer"
                                            :class="savedTests.find(t => t.id === loadedTestId)?.is_included_in_report ? 'bg-blue-50 text-[#2271b1] border-blue-200 hover:bg-blue-100' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'">
                                            <i :class="savedTests.find(t => t.id === loadedTestId)?.is_included_in_report ? 'fa-solid fa-circle-check text-[#2271b1]' : 'fa-regular fa-circle text-gray-400'"></i>
                                            <span x-text="savedTests.find(t => t.id === loadedTestId)?.is_included_in_report ? '{{ __('Included in Report') }}' : '{{ __('Include in Report') }}'"></span>
                                        </button>
                                    </template>
                                    <span x-show="isSaving"
                                        class="text-xs font-bold text-indigo-600 bg-indigo-50 px-3 py-1.5 rounded-xl flex items-center gap-2">
                                        <i class="fa-solid fa-spinner fa-spin"></i> {{ __('Saving...') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Test Selection -->
                            <div class="mb-8">
                                <label
                                    class="block text-sm font-medium text-indigo-600  tracking-widest mb-2">{{ __('Select Statistical Method') }}</label>
                                <select x-model="testMethod"
                                    class="w-full md:w-1/2 bg-gray-50 border border-indigo-100 text-xs sm:text-sm font-semibold rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-indigo-500 transition-all max-w-full">
                                    <option value="crosstab">{{ __('Cross-Tabulation Analysis') }}</option>
                                    <option value="chisquare">{{ __('Chi-Square for Independence (χ²)') }}</option>
                                    <option value="cronbach">{{ __('Reliability Test (Cronbach\'s Alpha α) — Pilot Data') }}
                                    </option>
                                    <option value="ttest">{{ __('Independent Samples T-Test') }}</option>
                                    <option value="correlation">{{ __('Pearson Correlation (r)') }}</option>
                                    <option value="anova">{{ __('One-Way ANOVA') }}</option>
                                    <option value="regression">{{ __('Simple Linear Regression') }}</option>
                                    <option value="regression_multiple">{{ __('Multiple Linear Regression') }}</option>
                                </select>
                            </div>

                            <!-- Dynamic Input Fields based on Selected Method -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-gray-50 pt-6">
                                <!-- Scope Selector for T-Test and ANOVA -->
                                <template x-if="testMethod === 'ttest' || testMethod === 'anova'">
                                    <div class="col-span-full mb-2">
                                        <label
                                            class="block text-xs font-bold text-gray-700 tracking-normal mb-2">{{ __('Analysis Scope & Comparison Mode') }}</label>
                                        <div
                                            class="inline-flex flex-wrap p-1 bg-gray-100/90 rounded-2xl border border-gray-200/80 gap-1">
                                            <button type="button" @click="scope = 'within'"
                                                :class="scope === 'within' ? 'bg-white text-[#2271b1] shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900 font-medium'"
                                                class="px-4 py-2 rounded-xl text-xs transition-all">
                                                {{ __('Within This Survey') }}
                                            </button>
                                            <button type="button" @click="scope = 'cross_survey'"
                                                :class="scope === 'cross_survey' ? 'bg-white text-[#2271b1] shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900 font-medium'"
                                                class="px-4 py-2 rounded-xl text-xs transition-all">
                                                {{ __('Compare with Another Survey') }}
                                            </button>
                                            <template x-if="testMethod === 'ttest'">
                                                <button type="button" @click="scope = 'upload'"
                                                    :class="scope === 'upload' ? 'bg-white text-[#2271b1] shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900 font-medium'"
                                                    class="px-4 py-2 rounded-xl text-xs transition-all">
                                                    {{ __('Upload External Dataset') }}
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <!-- Case 1: Crosstab / Chi-Square -->
                                <!-- Case 1: Crosstab / Chi-Square -->
                                <template x-if="testMethod === 'crosstab' || testMethod === 'chisquare'">
                                    <div class="contents">
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-700 tracking-normal mb-2">{{ __('Row Variable') }}</label>
                                            <select x-model="rowVar"
                                                class="w-full bg-gray-50 border border-gray-200 text-xs sm:text-sm font-medium rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-indigo-500 transition-all truncate">
                                                <option value="">{{ __('Select Question...') }}</option>
                                                @foreach($analysis as $item)
                                                    @if(in_array($item['type'] ?? '', ['likert_matrix', 'likert_matrix_grid']) && !empty($item['likert_matrix_rows']))
                                                        <optgroup
                                                            label="Q{{ $loop->iteration }}: {{ \Illuminate\Support\Str::limit(\App\Http\Controllers\SurveyController::formatShortCategoryTheme($item['label']), 65) }}">
                                                            @foreach($item['likert_matrix_rows'] as $subIdx => $subRow)
                                                                @php
                                                                    $rawSub = $subRow['label'] ?? '';
                                                                    $cleanSub = preg_replace('/^(\d+[\.\:\)\s]+|\([a-zA-Z0-9]+\)\s*|[a-zA-Z][\.\:\)\s]+)\s*/', '', $rawSub);
                                                                @endphp
                                                                <option value="{{ $item['id'] }}__{{ $subRow['value'] }}"
                                                                    title="{{ $rawSub }} ({{ $item['label'] }})">
                                                                    Q{{ $loop->parent->iteration }}.{{ $subIdx + 1 }}:
                                                                    {{ \Illuminate\Support\Str::limit($cleanSub, 70) }}
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                    @else
                                                        <option value="{{ $item['id'] }}" title="{{ $item['label'] }}">
                                                            Q{{ $loop->iteration }}:
                                                            {{ \Illuminate\Support\Str::limit($item['label'], 75) }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <template x-if="getQuestionInfo(rowVar)">
                                                <div
                                                    class="mt-2.5 p-3.5 bg-indigo-50/60 rounded-2xl border border-indigo-100/80 shadow-2xs text-xs space-y-1.5 animate-in fade-in duration-200">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <div class="flex items-center gap-2">
                                                            <span
                                                                class="text-[9px] font-black tracking-wider uppercase px-2 py-0.5 rounded-md bg-white text-indigo-700 border border-indigo-200/60 shadow-2xs"
                                                                x-text="getQuestionInfo(rowVar).type === 'likert_item' ? '{{ __('Likert Sub-Item') }}' : getQuestionInfo(rowVar).type"></span>
                                                        </div>
                                                        <template
                                                            x-if="getQuestionInfo(rowVar).answered_count !== undefined && getQuestionInfo(rowVar).answered_count !== null">
                                                            <span class="text-[11px] text-gray-500 font-bold"
                                                                x-text="'N = ' + getQuestionInfo(rowVar).answered_count + ' responses'"></span>
                                                        </template>
                                                    </div>
                                                    <template x-if="getQuestionInfo(rowVar).parent_label">
                                                        <div class="text-[11px] text-gray-500 font-medium leading-relaxed">
                                                            <span
                                                                class="font-bold text-gray-700">{{ __('Context:') }}</span>
                                                            <span x-text="getQuestionInfo(rowVar).parent_label"></span>
                                                        </div>
                                                    </template>
                                                    <div class="text-xs font-bold text-gray-900 leading-snug break-words">
                                                        <template x-if="getQuestionInfo(rowVar).sub_label">
                                                            <div>
                                                                <span
                                                                    class="text-indigo-700 font-extrabold">{{ __('Item Statement:') }}</span>
                                                                <span x-text="getQuestionInfo(rowVar).sub_label"></span>
                                                            </div>
                                                        </template>
                                                        <template x-if="!getQuestionInfo(rowVar).sub_label">
                                                            <p x-text="getQuestionInfo(rowVar).label"></p>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-700 tracking-normal mb-2">{{ __('Column Variable') }}</label>
                                            <select x-model="colVar"
                                                class="w-full bg-gray-50 border border-gray-200 text-xs sm:text-sm font-medium rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-indigo-500 transition-all truncate">
                                                <option value="">{{ __('Select Question...') }}</option>
                                                @foreach($analysis as $item)
                                                    @if(in_array($item['type'] ?? '', ['likert_matrix', 'likert_matrix_grid']) && !empty($item['likert_matrix_rows']))
                                                        <optgroup
                                                            label="Q{{ $loop->iteration }}: {{ \Illuminate\Support\Str::limit(\App\Http\Controllers\SurveyController::formatShortCategoryTheme($item['label']), 65) }}">
                                                            @foreach($item['likert_matrix_rows'] as $subIdx => $subRow)
                                                                @php
                                                                    $rawSub = $subRow['label'] ?? '';
                                                                    $cleanSub = preg_replace('/^(\d+[\.\:\)\s]+|\([a-zA-Z0-9]+\)\s*|[a-zA-Z][\.\:\)\s]+)\s*/', '', $rawSub);
                                                                @endphp
                                                                <option value="{{ $item['id'] }}__{{ $subRow['value'] }}"
                                                                    title="{{ $rawSub }} ({{ $item['label'] }})">
                                                                    Q{{ $loop->parent->iteration }}.{{ $subIdx + 1 }}:
                                                                    {{ \Illuminate\Support\Str::limit($cleanSub, 70) }}
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                    @else
                                                        <option value="{{ $item['id'] }}" title="{{ $item['label'] }}">
                                                            Q{{ $loop->iteration }}:
                                                            {{ \Illuminate\Support\Str::limit($item['label'], 75) }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <template x-if="getQuestionInfo(colVar)">
                                                <div
                                                    class="mt-2.5 p-3.5 bg-indigo-50/60 rounded-2xl border border-indigo-100/80 shadow-2xs text-xs space-y-1.5 animate-in fade-in duration-200">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <div class="flex items-center gap-2">
                                                            <span
                                                                class="text-[9px] font-black tracking-wider uppercase px-2 py-0.5 rounded-md bg-white text-indigo-700 border border-indigo-200/60 shadow-2xs"
                                                                x-text="getQuestionInfo(colVar).type === 'likert_item' ? '{{ __('Likert Sub-Item') }}' : getQuestionInfo(colVar).type"></span>
                                                        </div>
                                                        <template
                                                            x-if="getQuestionInfo(colVar).answered_count !== undefined && getQuestionInfo(colVar).answered_count !== null">
                                                            <span class="text-[11px] text-gray-500 font-bold"
                                                                x-text="'N = ' + getQuestionInfo(colVar).answered_count + ' responses'"></span>
                                                        </template>
                                                    </div>
                                                    <template x-if="getQuestionInfo(colVar).parent_label">
                                                        <div class="text-[11px] text-gray-500 font-medium leading-relaxed">
                                                            <span
                                                                class="font-bold text-gray-700">{{ __('Context:') }}</span>
                                                            <span x-text="getQuestionInfo(colVar).parent_label"></span>
                                                        </div>
                                                    </template>
                                                    <div class="text-xs font-bold text-gray-900 leading-snug break-words">
                                                        <template x-if="getQuestionInfo(colVar).sub_label">
                                                            <div>
                                                                <span
                                                                    class="text-indigo-700 font-extrabold">{{ __('Item Statement:') }}</span>
                                                                <span x-text="getQuestionInfo(colVar).sub_label"></span>
                                                            </div>
                                                        </template>
                                                        <template x-if="!getQuestionInfo(colVar).sub_label">
                                                            <p x-text="getQuestionInfo(colVar).label"></p>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <!-- Case 1b: Cronbach Alpha -->
                                <template x-if="testMethod === 'cronbach'">
                                    <div class="col-span-full space-y-4">
                                        <div
                                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs">
                                            <div>
                                                <label
                                                    class="block text-xs font-bold text-gray-800 tracking-normal flex items-center gap-2">
                                                    <i class="fa-solid fa-layer-group text-[#2271b1] text-xs"></i>
                                                    <span>{{ __('Select Likert / Rating Scale Items for Reliability Testing') }}</span>
                                                </label>
                                                <p class="text-[11px] text-gray-500 mt-0.5">
                                                    {{ __('Categorical and text fields are hidden. Items are organized by construct. Select at least 2 items.') }}
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0">
                                                <template x-if="cronbachItems.length > 0">
                                                    <button type="button" @click="clearScaleItems()"
                                                        class="text-[11px] font-semibold text-gray-400 hover:text-red-500 transition-colors cursor-pointer px-2 py-1">
                                                        {{ __('Clear') }}
                                                    </button>
                                                </template>
                                                <button type="button"
                                                    @click="selectAllScaleItems(@js($allEligibleScaleIds))"
                                                    class="text-[11px] font-bold text-[#2271b1] hover:text-[#135e96] bg-blue-50 hover:bg-blue-100/80 px-2.5 py-1.5 rounded-xl transition-all cursor-pointer">
                                                    {{ __('Select All') }} ({{ count($allEligibleScaleIds) }})
                                                </button>
                                                <span class="text-xs font-black px-2.5 py-1 rounded-xl transition-all"
                                                    :class="cronbachItems.length >= 2 ? 'text-emerald-700 bg-emerald-50 border border-emerald-200/60' : 'text-amber-700 bg-amber-50 border border-amber-200/60'"
                                                    x-text="cronbachItems.length + ' {{ __('selected') }}' + (cronbachItems.length < 2 ? ' (min 2)' : '')"></span>
                                            </div>
                                        </div>

                                        <div class="space-y-4 max-h-80 overflow-y-auto custom-scrollbar p-1">
                                            @if(!empty($scaleGroups))
                                                @foreach($scaleGroups as $groupKey => $group)
                                                    @php
                                                        $groupIds = array_column($group['items'], 'id');
                                                    @endphp
                                                    <div class="bg-gray-50/70 border border-gray-200/80 rounded-2xl p-4 space-y-3">
                                                        <div
                                                            class="flex items-center justify-between pb-2 border-b border-gray-200/60">
                                                            <div class="flex items-center gap-2">
                                                                <span class="w-2 h-2 rounded-full bg-[#2271b1] shrink-0"></span>
                                                                <span class="text-xs font-bold text-gray-800 tracking-tight">
                                                                    {{ __('Construct / Section:') }} <span
                                                                        class="text-gray-900 font-black">{{ $group['label'] }}</span>
                                                                </span>
                                                                <span
                                                                    class="text-[10px] font-semibold text-gray-500 bg-white px-2 py-0.5 rounded-full border border-gray-200 shadow-2xs">
                                                                    {{ count($group['items']) }} {{ __('items') }}
                                                                </span>
                                                            </div>
                                                            <button type="button" @click="toggleGroupItems(@js($groupIds))"
                                                                class="text-[11px] font-bold px-2.5 py-1 rounded-lg transition-all border cursor-pointer"
                                                                :class="isGroupAllSelected(@js($groupIds)) ? 'bg-[#2271b1] text-white border-[#2271b1] shadow-xs' : 'bg-white text-[#2271b1] border-blue-200 hover:bg-blue-50/80'">
                                                                <span
                                                                    x-text="isGroupAllSelected(@js($groupIds)) ? '{{ __('Deselect Construct') }}' : '{{ __('Select All in Construct') }}'"></span>
                                                            </button>
                                                        </div>

                                                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                                                            @foreach($group['items'] as $item)
                                                                <label
                                                                    class="flex items-start gap-2.5 text-xs font-semibold p-2.5 rounded-xl border transition-all cursor-pointer select-none"
                                                                    :class="cronbachItems.includes('{{ $item['id'] }}') ? 'bg-white border-[#2271b1] ring-1 ring-[#2271b1] shadow-xs text-gray-900' : 'bg-white border-gray-200 text-gray-700 hover:border-gray-300 hover:bg-gray-50/50'"
                                                                    title="{{ $item['label'] }}">
                                                                    <input type="checkbox" :value="'{{ $item['id'] }}'"
                                                                        x-model="cronbachItems"
                                                                        class="mt-0.5 rounded border-gray-300 text-[#2271b1] focus:ring-[#2271b1] shrink-0">
                                                                    <div class="min-w-0 flex-1">
                                                                        <p class="font-bold leading-snug break-words text-[11px]">
                                                                            {{ $item['subItemText'] ?? $item['label'] }}
                                                                        </p>
                                                                    </div>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endif

                                            @if(!empty($standaloneScaleItems))
                                                <div class="bg-gray-50/70 border border-gray-200/80 rounded-2xl p-4 space-y-3">
                                                    @php
                                                        $standaloneIds = array_column($standaloneScaleItems, 'id');
                                                    @endphp
                                                    <div
                                                        class="flex items-center justify-between pb-2 border-b border-gray-200/60">
                                                        <div class="flex items-center gap-2">
                                                            <span class="w-2 h-2 rounded-full bg-[#2271b1] shrink-0"></span>
                                                            <span class="text-xs font-bold text-gray-800 tracking-tight">
                                                                {{ __('Individual Scale & Rating Items') }}
                                                            </span>
                                                            <span
                                                                class="text-[10px] font-semibold text-gray-500 bg-white px-2 py-0.5 rounded-full border border-gray-200 shadow-2xs">
                                                                {{ count($standaloneScaleItems) }} {{ __('items') }}
                                                            </span>
                                                        </div>
                                                        <button type="button" @click="toggleGroupItems(@js($standaloneIds))"
                                                            class="text-[11px] font-bold px-2.5 py-1 rounded-lg transition-all border cursor-pointer"
                                                            :class="isGroupAllSelected(@js($standaloneIds)) ? 'bg-[#2271b1] text-white border-[#2271b1] shadow-xs' : 'bg-white text-[#2271b1] border-blue-200 hover:bg-blue-50/80'">
                                                            <span
                                                                x-text="isGroupAllSelected(@js($standaloneIds)) ? '{{ __('Deselect All') }}' : '{{ __('Select All') }}'"></span>
                                                        </button>
                                                    </div>

                                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                                                        @foreach($standaloneScaleItems as $item)
                                                            <label
                                                                class="flex items-start gap-2.5 text-xs font-semibold p-2.5 rounded-xl border transition-all cursor-pointer select-none"
                                                                :class="cronbachItems.includes('{{ $item['id'] }}') ? 'bg-white border-[#2271b1] ring-1 ring-[#2271b1] shadow-xs text-gray-900' : 'bg-white border-gray-200 text-gray-700 hover:border-gray-300 hover:bg-gray-50/50'"
                                                                title="{{ $item['label'] }}">
                                                                <input type="checkbox" :value="'{{ $item['id'] }}'"
                                                                    x-model="cronbachItems"
                                                                    class="mt-0.5 rounded border-gray-300 text-[#2271b1] focus:ring-[#2271b1] shrink-0">
                                                                <div class="min-w-0 flex-1">
                                                                    <p class="font-bold leading-snug break-words text-[11px]">
                                                                        {{ $item['label'] }}
                                                                    </p>
                                                                    @if(!empty($item['type']))
                                                                        <span
                                                                            class="inline-block mt-1 text-[9px] font-bold uppercase tracking-wider text-gray-500 bg-gray-100 px-1.5 py-0.2 rounded">{{ $item['type'] }}</span>
                                                                    @endif
                                                                </div>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </template>

                                <!-- Case 2: T-Test / ANOVA -->
                                <template x-if="testMethod === 'ttest' || testMethod === 'anova'">
                                    <div class="contents">
                                        <!-- Subcase A: Within Survey -->
                                        <template x-if="scope === 'within'">
                                            <div class="contents">
                                                <div>
                                                    <label
                                                        class="block text-xs font-bold text-gray-700 tracking-normal mb-2">{{ __('Dependent Variable (Numeric / Metric)') }}</label>
                                                    <select x-model="depVar"
                                                        class="w-full bg-gray-50 border border-gray-200 text-xs sm:text-sm font-medium rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-[#2271b1] transition-all truncate">
                                                        <option value="">{{ __('Select Metric Question...') }}</option>
                                                        @foreach($analysis as $item)
                                                            @php
                                                                $itemType = strtolower($item['type'] ?? '');
                                                                $isLikertMatrix = in_array($itemType, ['likert_matrix', 'likert_matrix_grid']) && !empty($item['likert_matrix_rows']);
                                                                $isContinuousMetric = !empty($item['summary_stats'])
                                                                    || in_array($itemType, ['number', 'rating', 'scale', 'slider', 'range', 'star_rating', 'nps', 'likert', 'likert_item'])
                                                                    || (!empty($item['isLikertLike']) && !in_array($itemType, ['select_one', 'radio', 'select_many', 'checkbox', 'select', 'text', 'textarea', 'email', 'date', 'file']));
                                                            @endphp
                                                            @if($isLikertMatrix)
                                                                <optgroup
                                                                    label="Q{{ $loop->iteration }}: {{ \Illuminate\Support\Str::limit(\App\Http\Controllers\SurveyController::formatShortCategoryTheme($item['label']), 90) }}">
                                                                    @foreach($item['likert_matrix_rows'] as $subIdx => $subRow)
                                                                        @php
                                                                            $rawSub = $subRow['label'] ?? '';
                                                                            $cleanSub = preg_replace('/^(\d+[\.\:\)\s]+|\([a-zA-Z0-9]+\)\s*|[a-zA-Z][\.\:\)\s]+)\s*/', '', $rawSub);
                                                                            $cleanTheme = \App\Http\Controllers\SurveyController::formatShortCategoryTheme($item['label']);
                                                                        @endphp
                                                                        <option value="{{ $item['id'] }}__{{ $subRow['value'] }}"
                                                                            title="{{ $cleanSub }} ({{ $cleanTheme }})">
                                                                            Q{{ $loop->parent->iteration }}.{{ $subIdx + 1 }}:
                                                                            {{ \Illuminate\Support\Str::limit($cleanSub, 85) }}
                                                                        </option>
                                                                    @endforeach
                                                                </optgroup>
                                                            @elseif($isContinuousMetric && !empty($item['isChartable']))
                                                                <option value="{{ $item['id'] }}" title="{{ $item['label'] }}">
                                                                    Q{{ $loop->iteration }}:
                                                                    {{ \Illuminate\Support\Str::limit($item['label'], 85) }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                    <template x-if="getQuestionInfo(depVar)">
                                                        <div
                                                            class="mt-2.5 p-3.5 bg-blue-50/60 rounded-2xl border border-blue-100/80 shadow-2xs text-xs space-y-1.5 animate-in fade-in duration-200">
                                                            <div class="flex items-center justify-between gap-2">
                                                                <div class="flex items-center gap-2">
                                                                    <span
                                                                        class="text-[9px] font-black tracking-wider uppercase px-2 py-0.5 rounded-md bg-white text-[#2271b1] border border-blue-200/60 shadow-2xs"
                                                                        x-text="getQuestionInfo(depVar).type === 'likert_item' ? '{{ __('Likert Sub-Item') }}' : getQuestionInfo(depVar).type"></span>
                                                                </div>
                                                                <template
                                                                    x-if="getQuestionInfo(depVar).answered_count !== undefined && getQuestionInfo(depVar).answered_count !== null">
                                                                    <span class="text-[11px] text-gray-500 font-bold"
                                                                        x-text="'N = ' + getQuestionInfo(depVar).answered_count + ' responses'"></span>
                                                                </template>
                                                            </div>
                                                            <template x-if="getQuestionInfo(depVar).parent_label">
                                                                <div
                                                                    class="text-[11px] text-gray-500 font-medium leading-relaxed">
                                                                    <span
                                                                        class="font-bold text-gray-700">{{ __('Context:') }}</span>
                                                                    <span
                                                                        x-text="getQuestionInfo(depVar).parent_label"></span>
                                                                </div>
                                                            </template>
                                                            <div
                                                                class="text-xs font-bold text-gray-900 leading-snug break-words">
                                                                <template x-if="getQuestionInfo(depVar).sub_label">
                                                                    <div>
                                                                        <span
                                                                            class="text-[#2271b1] font-extrabold">{{ __('Item Statement:') }}</span>
                                                                        <span
                                                                            x-text="getQuestionInfo(depVar).sub_label"></span>
                                                                    </div>
                                                                </template>
                                                                <template x-if="!getQuestionInfo(depVar).sub_label">
                                                                    <p x-text="getQuestionInfo(depVar).label"></p>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-xs font-bold text-gray-700 tracking-normal mb-2">{{ __('Grouping Variable (Categorical / Factor)') }}</label>
                                                    <select x-model="groupVar"
                                                        class="w-full bg-gray-50 border border-gray-200 text-xs sm:text-sm font-medium rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-[#2271b1] transition-all truncate">
                                                        <option value="">{{ __('Select Question...') }}</option>
                                                        @foreach($analysis as $item)
                                                            @php
                                                                $itemType = strtolower($item['type'] ?? '');
                                                                $isExcluded = in_array($itemType, ['text', 'textarea', 'email', 'date', 'file', 'checkbox', 'multiple_choice']);
                                                            @endphp
                                                            @if(in_array($itemType, ['likert_matrix', 'likert_matrix_grid']) && !empty($item['likert_matrix_rows']))
                                                                <optgroup
                                                                    label="Q{{ $loop->iteration }}: {{ \Illuminate\Support\Str::limit(\App\Http\Controllers\SurveyController::formatShortCategoryTheme($item['label']), 90) }}">
                                                                    @foreach($item['likert_matrix_rows'] as $subIdx => $subRow)
                                                                        @php
                                                                            $rawSub = $subRow['label'] ?? '';
                                                                            $cleanSub = preg_replace('/^(\d+[\.\:\)\s]+|\([a-zA-Z0-9]+\)\s*|[a-zA-Z][\.\:\)\s]+)\s*/', '', $rawSub);
                                                                            $cleanTheme = \App\Http\Controllers\SurveyController::formatShortCategoryTheme($item['label']);
                                                                        @endphp
                                                                        <option value="{{ $item['id'] }}__{{ $subRow['value'] }}"
                                                                            title="{{ $cleanSub }} ({{ $cleanTheme }})">
                                                                            Q{{ $loop->parent->iteration }}.{{ $subIdx + 1 }}:
                                                                            {{ \Illuminate\Support\Str::limit($cleanSub, 85) }}
                                                                        </option>
                                                                    @endforeach
                                                                </optgroup>
                                                            @elseif(!$isExcluded)
                                                                <option value="{{ $item['id'] }}" title="{{ $item['label'] }}">
                                                                    Q{{ $loop->iteration }}:
                                                                    {{ \Illuminate\Support\Str::limit($item['label'], 85) }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                    <template x-if="getQuestionInfo(groupVar)">
                                                        <div
                                                            class="mt-2.5 p-3.5 bg-blue-50/60 rounded-2xl border border-blue-100/80 shadow-2xs text-xs space-y-1.5 animate-in fade-in duration-200">
                                                            <div class="flex items-center justify-between gap-2">
                                                                <div class="flex items-center gap-2">
                                                                    <span
                                                                        class="text-[9px] font-black tracking-wider uppercase px-2 py-0.5 rounded-md bg-white text-[#2271b1] border border-blue-200/60 shadow-2xs"
                                                                        x-text="getQuestionInfo(groupVar).type === 'likert_item' ? '{{ __('Likert Sub-Item') }}' : getQuestionInfo(groupVar).type"></span>
                                                                </div>
                                                                <template
                                                                    x-if="getQuestionInfo(groupVar).answered_count !== undefined && getQuestionInfo(groupVar).answered_count !== null">
                                                                    <span class="text-[11px] text-gray-500 font-bold"
                                                                        x-text="'N = ' + getQuestionInfo(groupVar).answered_count + ' responses'"></span>
                                                                </template>
                                                            </div>
                                                            <template x-if="getQuestionInfo(groupVar).parent_label">
                                                                <div
                                                                    class="text-[11px] text-gray-500 font-medium leading-relaxed">
                                                                    <span
                                                                        class="font-bold text-gray-700">{{ __('Context:') }}</span>
                                                                    <span
                                                                        x-text="getQuestionInfo(groupVar).parent_label"></span>
                                                                </div>
                                                            </template>
                                                            <div
                                                                class="text-xs font-bold text-gray-900 leading-snug break-words">
                                                                <template x-if="getQuestionInfo(groupVar).sub_label">
                                                                    <div>
                                                                        <span
                                                                            class="text-[#2271b1] font-extrabold">{{ __('Item Statement:') }}</span>
                                                                        <span
                                                                            x-text="getQuestionInfo(groupVar).sub_label"></span>
                                                                    </div>
                                                                </template>
                                                                <template x-if="!getQuestionInfo(groupVar).sub_label">
                                                                    <p x-text="getQuestionInfo(groupVar).label"></p>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    <!-- SPSS Define Groups Dynamic Cohort Selector (T-Test with > 2 categories) -->
                                                    <template
                                                        x-if="testMethod === 'ttest' && scope === 'within' && getQuestionInfo(groupVar) && (getQuestionInfo(groupVar).categories || []).length > 2">
                                                        <div
                                                            class="mt-2.5 p-3.5 bg-blue-50/80 rounded-2xl border border-blue-200/90 shadow-2xs space-y-2.5 animate-in fade-in duration-200">
                                                            <div class="flex items-center justify-between flex-wrap gap-1">
                                                                <span
                                                                    class="text-[10px] font-black tracking-wider uppercase px-2 py-0.5 rounded-md bg-[#2271b1] text-white shadow-2xs">
                                                                    {{ __('SPSS Define Groups: Select 2 Cohorts') }}
                                                                </span>
                                                                <button type="button" @click="testMethod = 'anova'"
                                                                    class="text-[11px] font-bold text-[#2271b1] hover:underline flex items-center gap-1 cursor-pointer">
                                                                    <span>{{ __('Compare all categories with One-Way ANOVA') }}</span>
                                                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                                                </button>
                                                            </div>
                                                            <p class="text-[11px] text-gray-600 font-medium">
                                                                <span
                                                                    x-text="'This variable contains ' + (getQuestionInfo(groupVar).categories || []).length + ' categories. Choose 2 specific cohorts for independent sample comparison:'"></span>
                                                            </p>
                                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                                                <div>
                                                                    <label
                                                                        class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Group 1 (Cohort A):') }}</label>
                                                                    <select x-model="ttestGroup1"
                                                                        class="w-full bg-white border border-gray-200 text-xs font-semibold rounded-xl px-3 py-2 focus:ring-2 focus:ring-[#2271b1] transition-all truncate">
                                                                        <template
                                                                            x-for="opt in (getQuestionInfo(groupVar).category_options || (getQuestionInfo(groupVar).categories || []).map(c => ({value: c, label: c})))"
                                                                            :key="'g1_' + opt.value">
                                                                            <option :value="opt.value" x-text="opt.label"
                                                                                :disabled="opt.value === ttestGroup2">
                                                                            </option>
                                                                        </template>
                                                                    </select>
                                                                </div>
                                                                <div>
                                                                    <label
                                                                        class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Group 2 (Cohort B):') }}</label>
                                                                    <select x-model="ttestGroup2"
                                                                        class="w-full bg-white border border-gray-200 text-xs font-semibold rounded-xl px-3 py-2 focus:ring-2 focus:ring-[#2271b1] transition-all truncate">
                                                                        <template
                                                                            x-for="opt in (getQuestionInfo(groupVar).category_options || (getQuestionInfo(groupVar).categories || []).map(c => ({value: c, label: c})))"
                                                                            :key="'g2_' + opt.value">
                                                                            <option :value="opt.value" x-text="opt.label"
                                                                                :disabled="opt.value === ttestGroup1">
                                                                            </option>
                                                                        </template>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    <!-- Binary Factor Confirmation Badge (T-Test with exactly 2 categories) -->
                                                    <template
                                                        x-if="testMethod === 'ttest' && scope === 'within' && getQuestionInfo(groupVar) && (getQuestionInfo(groupVar).categories || []).length === 2">
                                                        <div
                                                            class="mt-2 px-3 py-2 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-bold text-emerald-800 flex items-center justify-between shadow-2xs animate-in fade-in duration-200">
                                                            <span class="flex items-center gap-1.5">
                                                                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                                                                <span>{{ __('Binary Factor:') }} <span class="text-gray-900"
                                                                        x-text="(getQuestionInfo(groupVar).category_options ? getQuestionInfo(groupVar).category_options.map(o => o.label) : (getQuestionInfo(groupVar).categories || [])).join(' vs ')"></span></span>
                                                            </span>
                                                            <span
                                                                class="text-[9px] font-black uppercase tracking-wider text-emerald-700 bg-white border border-emerald-200 px-2 py-0.5 rounded-md">{{ __('2 Cohorts Auto-Selected') }}</span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Subcase B: Cross-Survey -->
                                        <template x-if="scope === 'cross_survey'">
                                            <div class="contents">
                                                <div>
                                                    <label
                                                        class="block text-xs font-bold text-gray-700 tracking-normal mb-2">{{ __('Current Dataset Question') }}</label>
                                                    <select x-model="depVar"
                                                        class="w-full bg-gray-50 border border-gray-200 text-xs sm:text-sm font-medium rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-[#2271b1] transition-all truncate">
                                                        <option value="">{{ __('Select Question...') }}</option>
                                                        @foreach($analysis as $item)
                                                            @php
                                                                $itemType = strtolower($item['type'] ?? '');
                                                                $isLikertMatrix = in_array($itemType, ['likert_matrix', 'likert_matrix_grid']) && !empty($item['likert_matrix_rows']);
                                                                $isContinuousMetric = !empty($item['summary_stats'])
                                                                    || in_array($itemType, ['number', 'rating', 'scale', 'slider', 'range', 'star_rating', 'nps', 'likert', 'likert_item'])
                                                                    || (!empty($item['isLikertLike']) && !in_array($itemType, ['select_one', 'radio', 'select_many', 'checkbox', 'select', 'text', 'textarea', 'email', 'date', 'file']));
                                                            @endphp
                                                            @if($isLikertMatrix)
                                                                <optgroup
                                                                    label="Q{{ $loop->iteration }}: {{ \Illuminate\Support\Str::limit(\App\Http\Controllers\SurveyController::formatShortCategoryTheme($item['label']), 90) }}">
                                                                    @foreach($item['likert_matrix_rows'] as $subIdx => $subRow)
                                                                        @php
                                                                            $rawSub = $subRow['label'] ?? '';
                                                                            $cleanSub = preg_replace('/^(\d+[\.\:\)\s]+|\([a-zA-Z0-9]+\)\s*|[a-zA-Z][\.\:\)\s]+)\s*/', '', $rawSub);
                                                                            $cleanTheme = \App\Http\Controllers\SurveyController::formatShortCategoryTheme($item['label']);
                                                                        @endphp
                                                                        <option value="{{ $item['id'] }}__{{ $subRow['value'] }}"
                                                                            title="{{ $cleanSub }} ({{ $cleanTheme }})">
                                                                            Q{{ $loop->parent->iteration }}.{{ $subIdx + 1 }}:
                                                                            {{ \Illuminate\Support\Str::limit($cleanSub, 85) }}
                                                                        </option>
                                                                    @endforeach
                                                                </optgroup>
                                                            @elseif($isContinuousMetric && !empty($item['isChartable']))
                                                                <option value="{{ $item['id'] }}" title="{{ $item['label'] }}">
                                                                    Q{{ $loop->iteration }}:
                                                                    {{ \Illuminate\Support\Str::limit($item['label'], 85) }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                    <template x-if="getQuestionInfo(depVar)">
                                                        <div
                                                            class="mt-2.5 p-3.5 bg-blue-50/60 rounded-2xl border border-blue-100/80 shadow-2xs text-xs space-y-1.5 animate-in fade-in duration-200">
                                                            <div class="flex items-center justify-between gap-2">
                                                                <div class="flex items-center gap-2">
                                                                    <span
                                                                        class="text-[9px] font-black tracking-wider uppercase px-2 py-0.5 rounded-md bg-white text-[#2271b1] border border-blue-200/60 shadow-2xs"
                                                                        x-text="getQuestionInfo(depVar).type === 'likert_item' ? '{{ __('Likert Sub-Item') }}' : getQuestionInfo(depVar).type"></span>
                                                                </div>
                                                                <template
                                                                    x-if="getQuestionInfo(depVar).answered_count !== undefined && getQuestionInfo(depVar).answered_count !== null">
                                                                    <span class="text-[11px] text-gray-500 font-bold"
                                                                        x-text="'N = ' + getQuestionInfo(depVar).answered_count + ' responses'"></span>
                                                                </template>
                                                            </div>
                                                            <template x-if="getQuestionInfo(depVar).parent_label">
                                                                <div
                                                                    class="text-[11px] text-gray-500 font-medium leading-relaxed">
                                                                    <span
                                                                        class="font-bold text-gray-700">{{ __('Context:') }}</span>
                                                                    <span
                                                                        x-text="getQuestionInfo(depVar).parent_label"></span>
                                                                </div>
                                                            </template>
                                                            <div
                                                                class="text-xs font-bold text-gray-900 leading-snug break-words">
                                                                <template x-if="getQuestionInfo(depVar).sub_label">
                                                                    <div>
                                                                        <span
                                                                            class="text-[#2271b1] font-extrabold">{{ __('Item Statement:') }}</span>
                                                                        <span
                                                                            x-text="getQuestionInfo(depVar).sub_label"></span>
                                                                    </div>
                                                                </template>
                                                                <template x-if="!getQuestionInfo(depVar).sub_label">
                                                                    <p x-text="getQuestionInfo(depVar).label"></p>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>

                                                <!-- T-Test Cross Survey target -->
                                                <template x-if="testMethod === 'ttest'">
                                                    <div class="space-y-4">
                                                        <div>
                                                            <label
                                                                class="block text-xs font-bold text-gray-700 tracking-normal mb-2">{{ __('External Dataset') }}</label>
                                                            <select x-model="targetSurveyId" @change="targetDepVar = ''"
                                                                class="w-full bg-gray-50 border border-gray-200 text-xs sm:text-sm font-medium rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-[#2271b1] transition-all truncate">
                                                                <option value="">{{ __('Select Other Survey...') }}</option>
                                                                <template x-for="s in userSurveys" :key="s.id">
                                                                    <option :value="s.id"
                                                                        x-text="s.title.length > 70 ? s.title.substring(0, 70) + '...' : s.title"
                                                                        :title="s.title"></option>
                                                                </template>
                                                            </select>
                                                        </div>
                                                        <div x-show="targetSurveyId">
                                                            <label
                                                                class="block text-xs font-bold text-gray-700 tracking-normal mb-2">{{ __('Target Comparison Question') }}</label>
                                                            <select x-model="targetDepVar"
                                                                @change="targetDepVar = $el.value"
                                                                class="w-full bg-gray-50 border border-gray-200 text-xs sm:text-sm font-medium rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-[#2271b1] transition-all truncate"
                                                                x-html="getTargetQuestionsHtml(targetSurveyId, targetDepVar)">
                                                            </select>
                                                            <template
                                                                x-if="getTargetQuestionInfo(targetSurveyId, targetDepVar)">
                                                                <div
                                                                    class="mt-2.5 p-3.5 bg-blue-50/60 rounded-2xl border border-blue-100/80 shadow-2xs text-xs space-y-1.5 animate-in fade-in duration-200">
                                                                    <div class="flex items-center justify-between gap-2">
                                                                        <div class="flex items-center gap-2">
                                                                            <span
                                                                                class="text-[9px] font-black tracking-wider uppercase px-2 py-0.5 rounded-md bg-white text-[#2271b1] border border-blue-200/60 shadow-2xs"
                                                                                x-text="getTargetQuestionInfo(targetSurveyId, targetDepVar).type === 'likert_item' ? '{{ __('Likert Sub-Item') }}' : getTargetQuestionInfo(targetSurveyId, targetDepVar).type"></span>
                                                                        </div>
                                                                    </div>
                                                                    <template
                                                                        x-if="getTargetQuestionInfo(targetSurveyId, targetDepVar).parentLabel">
                                                                        <div
                                                                            class="text-[11px] text-gray-500 font-medium leading-relaxed">
                                                                            <span
                                                                                class="font-bold text-gray-700">{{ __('Context:') }}</span>
                                                                            <span
                                                                                x-text="getTargetQuestionInfo(targetSurveyId, targetDepVar).parentLabel"></span>
                                                                        </div>
                                                                    </template>
                                                                    <div
                                                                        class="text-xs font-bold text-gray-900 leading-snug break-words">
                                                                        <template
                                                                            x-if="getTargetQuestionInfo(targetSurveyId, targetDepVar).subItemText">
                                                                            <div>
                                                                                <span
                                                                                    class="text-[#2271b1] font-extrabold">{{ __('Item Statement:') }}</span>
                                                                                <span
                                                                                    x-text="getTargetQuestionInfo(targetSurveyId, targetDepVar).subItemText"></span>
                                                                            </div>
                                                                        </template>
                                                                        <template
                                                                            x-if="!getTargetQuestionInfo(targetSurveyId, targetDepVar).subItemText">
                                                                            <p
                                                                                x-text="getTargetQuestionInfo(targetSurveyId, targetDepVar).label">
                                                                            </p>
                                                                        </template>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>

                                                <!-- ANOVA Multi-Survey Comparison Builder (3+ Surveys) -->
                                                <template x-if="testMethod === 'anova'">
                                                    <div class="space-y-4">
                                                        <div class="flex items-center justify-between">
                                                            <label
                                                                class="block text-xs font-bold text-gray-700 tracking-normal">{{ __('External Comparison Surveys') }}</label>
                                                            <button type="button" @click="addAnovaTargetSurvey()"
                                                                class="text-[11px] font-bold text-[#2271b1] hover:text-[#135e96] bg-blue-50 hover:bg-blue-100/80 px-2.5 py-1.5 rounded-xl transition-all cursor-pointer flex items-center gap-1.5">
                                                                <i class="fa-solid fa-plus text-[10px]"></i>
                                                                <span>{{ __('Add Comparison Survey') }}</span>
                                                            </button>
                                                        </div>

                                                        <div
                                                            class="space-y-3 max-h-[360px] overflow-y-auto custom-scrollbar p-1">
                                                            <template x-for="(target, tIdx) in anovaTargetSurveys"
                                                                :key="tIdx">
                                                                <div
                                                                    class="bg-gray-50/80 border border-gray-200/90 rounded-2xl p-4 space-y-3 relative group">
                                                                    <div
                                                                        class="flex items-center justify-between pb-2 border-b border-gray-200/60">
                                                                        <span
                                                                            class="text-xs font-bold text-gray-700 flex items-center gap-1.5">
                                                                            <span
                                                                                class="w-2 h-2 rounded-full bg-[#2271b1]"></span>
                                                                            <span
                                                                                x-text="'Comparison Survey ' + (tIdx + 1)"></span>
                                                                        </span>
                                                                        <template x-if="anovaTargetSurveys.length > 1">
                                                                            <button type="button"
                                                                                @click="removeAnovaTargetSurvey(tIdx)"
                                                                                class="text-xs text-gray-400 hover:text-red-500 transition-colors p-1"
                                                                                title="{{ __('Remove this survey') }}">
                                                                                <i class="fa-regular fa-trash-can"></i>
                                                                            </button>
                                                                        </template>
                                                                    </div>
                                                                    <div class="space-y-3">
                                                                        <div>
                                                                            <select x-model="target.survey_id"
                                                                                @change="target.dep = ''"
                                                                                class="w-full bg-white border border-gray-200 text-xs sm:text-sm font-medium rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-[#2271b1] transition-all truncate">
                                                                                <option value="">
                                                                                    {{ __('Select Survey...') }}
                                                                                </option>
                                                                                <template x-for="s in userSurveys"
                                                                                    :key="s.id">
                                                                                    <option :value="s.id"
                                                                                        x-text="s.title.length > 70 ? s.title.substring(0, 70) + '...' : s.title"
                                                                                        :title="s.title"></option>
                                                                                </template>
                                                                            </select>
                                                                        </div>
                                                                        <div x-show="target.survey_id">
                                                                            <select x-model="target.dep"
                                                                                @change="target.dep = $el.value"
                                                                                class="w-full bg-white border border-gray-200 text-xs sm:text-sm font-medium rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-[#2271b1] transition-all truncate"
                                                                                x-html="getTargetQuestionsHtml(target.survey_id, target.dep)">
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>

                                        <!-- Subcase C: Upload Benchmark Dataset -->
                                        <template x-if="scope === 'upload'">
                                            <div
                                                class="col-span-full grid grid-cols-1 md:grid-cols-2 gap-6 bg-blue-50/30 p-5 rounded-2xl border border-blue-100/60">
                                                <div>
                                                    <label
                                                        class="block text-xs font-bold text-gray-700 tracking-normal mb-2">{{ __('Current Dataset Question') }}</label>
                                                    <select x-model="depVar"
                                                        class="w-full bg-white border border-gray-200 text-xs sm:text-sm font-medium rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#2271b1] transition-all truncate">
                                                        <option value="">{{ __('Select Question...') }}</option>
                                                        @foreach($analysis as $item)
                                                            @php
                                                                $itemType = strtolower($item['type'] ?? '');
                                                                $isLikertMatrix = in_array($itemType, ['likert_matrix', 'likert_matrix_grid']) && !empty($item['likert_matrix_rows']);
                                                                $isContinuousMetric = !empty($item['summary_stats'])
                                                                    || in_array($itemType, ['number', 'rating', 'scale', 'slider', 'range', 'star_rating', 'nps', 'likert', 'likert_item'])
                                                                    || (!empty($item['isLikertLike']) && !in_array($itemType, ['select_one', 'radio', 'select_many', 'checkbox', 'select', 'text', 'textarea', 'email', 'date', 'file']));
                                                            @endphp
                                                            @if($isLikertMatrix)
                                                                <optgroup
                                                                    label="Q{{ $loop->iteration }}: {{ \Illuminate\Support\Str::limit(\App\Http\Controllers\SurveyController::formatShortCategoryTheme($item['label']), 90) }}">
                                                                    @foreach($item['likert_matrix_rows'] as $subIdx => $subRow)
                                                                        @php
                                                                            $rawSub = $subRow['label'] ?? '';
                                                                            $cleanSub = preg_replace('/^(\d+[\.\:\)\s]+|\([a-zA-Z0-9]+\)\s*|[a-zA-Z][\.\:\)\s]+)\s*/', '', $rawSub);
                                                                            $cleanTheme = \App\Http\Controllers\SurveyController::formatShortCategoryTheme($item['label']);
                                                                        @endphp
                                                                        <option value="{{ $item['id'] }}__{{ $subRow['value'] }}"
                                                                            title="{{ $cleanSub }} ({{ $cleanTheme }})">
                                                                            Q{{ $loop->parent->iteration }}.{{ $subIdx + 1 }}:
                                                                            {{ \Illuminate\Support\Str::limit($cleanSub, 85) }}
                                                                        </option>
                                                                    @endforeach
                                                                </optgroup>
                                                            @elseif($isContinuousMetric && !empty($item['isChartable']))
                                                                <option value="{{ $item['id'] }}" title="{{ $item['label'] }}">
                                                                    Q{{ $loop->iteration }}:
                                                                    {{ \Illuminate\Support\Str::limit($item['label'], 85) }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                    <template x-if="getQuestionInfo(depVar)">
                                                        <div
                                                            class="mt-2.5 p-3.5 bg-white rounded-2xl border border-blue-100 shadow-2xs text-xs space-y-1.5 animate-in fade-in duration-200">
                                                            <div class="flex items-center justify-between gap-2">
                                                                <div class="flex items-center gap-2">
                                                                    <span
                                                                        class="text-[9px] font-black tracking-wider uppercase px-2 py-0.5 rounded-md bg-[#2271b1]/10 text-[#2271b1] border border-blue-200/60"
                                                                        x-text="getQuestionInfo(depVar).type === 'likert_item' ? '{{ __('Likert Sub-Item') }}' : getQuestionInfo(depVar).type"></span>
                                                                </div>
                                                            </div>
                                                            <p class="text-xs font-semibold text-gray-800 leading-snug break-words"
                                                                x-text="getQuestionInfo(depVar).label"></p>
                                                        </div>
                                                    </template>
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-xs font-bold text-gray-700 tracking-normal mb-2">{{ __('External Dataset Label') }}</label>
                                                    <input type="text" x-model="uploadedDataLabel"
                                                        class="w-full bg-white border border-gray-200 text-xs sm:text-sm font-medium rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#2271b1] transition-all truncate">
                                                </div>
                                                <div class="col-span-full">
                                                    <label
                                                        class="block text-xs font-bold text-gray-700 tracking-normal mb-2">{{ __('Upload CSV / Excel file or Paste Numbers') }}</label>
                                                    <div class="flex flex-col sm:flex-row gap-3 items-stretch">
                                                        <label
                                                            class="flex-1 border-2 border-dashed border-blue-200 hover:border-blue-400 bg-white rounded-xl p-4 flex flex-col items-center justify-center cursor-pointer transition-colors group">
                                                            <i
                                                                class="fa-solid fa-cloud-arrow-up text-[#2271b1] group-hover:scale-110 text-xl mb-1.5 transition-transform"></i>
                                                            <span class="text-xs font-bold text-gray-700"
                                                                x-text="uploadedFileName || '{{ __('Choose CSV or Excel file') }}'"></span>
                                                            <span
                                                                class="text-[11px] text-gray-400">{{ __('Single column of numeric values') }}</span>
                                                            <input type="file" accept=".csv, .txt, .xlsx, .xls"
                                                                class="hidden" @change="handleFileUpload($event)">
                                                        </label>
                                                        <div class="flex-1 flex flex-col">
                                                            <textarea x-model="rawUploadedText" @input="parseUploadedText()"
                                                                rows="3"
                                                                placeholder="{{ __('Or paste comma or newline-separated numbers here: 4.5, 3.2, 5.0, ...') }}"
                                                                class="w-full h-full bg-white border border-gray-200 text-xs rounded-xl p-3 focus:ring-2 focus:ring-[#2271b1] transition-all resize-none"></textarea>
                                                        </div>
                                                    </div>
                                                    <div x-show="uploadedDataValues.length > 0"
                                                        class="mt-2 text-xs font-bold text-emerald-700 flex items-center gap-1.5">
                                                        <i class="fa-solid fa-circle-check text-emerald-600"></i>
                                                        <span
                                                            x-text="uploadedDataValues.length + ' {{ __('numeric data points ready for comparison.') }}'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <!-- Case 3: Correlation -->
                                <template x-if="testMethod === 'correlation'">
                                    <div class="contents">
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-700 tracking-normal mb-2">{{ __('Variable X (Numeric / Metric)') }}</label>
                                            <select x-model="varX"
                                                class="w-full bg-gray-50 border border-gray-200 text-xs sm:text-sm font-medium rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-[#2271b1] transition-all truncate">
                                                <option value="">{{ __('Select Metric Question...') }}</option>
                                                @foreach($analysis as $item)
                                                    @php
                                                        $itemType = strtolower($item['type'] ?? '');
                                                        $isLikertMatrix = in_array($itemType, ['likert_matrix', 'likert_matrix_grid']) && !empty($item['likert_matrix_rows']);
                                                        $isContinuousMetric = !empty($item['summary_stats'])
                                                            || in_array($itemType, ['number', 'rating', 'scale', 'slider', 'range', 'star_rating', 'nps', 'likert', 'likert_item'])
                                                            || (!empty($item['isLikertLike']) && !in_array($itemType, ['select_one', 'radio', 'select_many', 'checkbox', 'select', 'text', 'textarea', 'email', 'date', 'file']));
                                                    @endphp
                                                    @if($isLikertMatrix)
                                                        <optgroup
                                                            label="Q{{ $loop->iteration }}: {{ \Illuminate\Support\Str::limit(\App\Http\Controllers\SurveyController::formatShortCategoryTheme($item['label']), 90) }}">
                                                            @foreach($item['likert_matrix_rows'] as $subIdx => $subRow)
                                                                @php
                                                                    $rawSub = $subRow['label'] ?? '';
                                                                    $cleanSub = preg_replace('/^(\d+[\.\:\)\s]+|\([a-zA-Z0-9]+\)\s*|[a-zA-Z][\.\:\)\s]+)\s*/', '', $rawSub);
                                                                    $cleanTheme = \App\Http\Controllers\SurveyController::formatShortCategoryTheme($item['label']);
                                                                @endphp
                                                                <option value="{{ $item['id'] }}__{{ $subRow['value'] }}"
                                                                    title="{{ $cleanSub }} ({{ $cleanTheme }})">
                                                                    Q{{ $loop->parent->iteration }}.{{ $subIdx + 1 }}:
                                                                    {{ \Illuminate\Support\Str::limit($cleanSub, 85) }}
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                    @elseif($isContinuousMetric && !empty($item['isChartable']))
                                                        <option value="{{ $item['id'] }}" title="{{ $item['label'] }}">
                                                            Q{{ $loop->iteration }}:
                                                            {{ \Illuminate\Support\Str::limit($item['label'], 85) }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <template x-if="getQuestionInfo(varX)">
                                                <div
                                                    class="mt-2.5 p-3.5 bg-blue-50/60 rounded-2xl border border-blue-100/80 shadow-2xs text-xs space-y-1.5 animate-in fade-in duration-200">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <div class="flex items-center gap-2">
                                                            <span
                                                                class="text-[9px] font-black tracking-wider uppercase px-2 py-0.5 rounded-md bg-white text-[#2271b1] border border-blue-200/60 shadow-2xs"
                                                                x-text="getQuestionInfo(varX).type === 'likert_item' ? '{{ __('Likert Sub-Item') }}' : getQuestionInfo(varX).type"></span>
                                                        </div>
                                                        <template
                                                            x-if="getQuestionInfo(varX).answered_count !== undefined && getQuestionInfo(varX).answered_count !== null">
                                                            <span class="text-[11px] text-gray-500 font-bold"
                                                                x-text="'N = ' + getQuestionInfo(varX).answered_count + ' responses'"></span>
                                                        </template>
                                                    </div>
                                                    <template x-if="getQuestionInfo(varX).parent_label">
                                                        <div class="text-[11px] text-gray-500 font-medium leading-relaxed">
                                                            <span
                                                                class="font-bold text-gray-700">{{ __('Context:') }}</span>
                                                            <span x-text="getQuestionInfo(varX).parent_label"></span>
                                                        </div>
                                                    </template>
                                                    <div class="text-xs font-bold text-gray-900 leading-snug break-words">
                                                        <template x-if="getQuestionInfo(varX).sub_label">
                                                            <div>
                                                                <span
                                                                    class="text-[#2271b1] font-extrabold">{{ __('Item Statement:') }}</span>
                                                                <span x-text="getQuestionInfo(varX).sub_label"></span>
                                                            </div>
                                                        </template>
                                                        <template x-if="!getQuestionInfo(varX).sub_label">
                                                            <p x-text="getQuestionInfo(varX).label"></p>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-700 tracking-normal mb-2">{{ __('Variable Y (Numeric / Metric)') }}</label>
                                            <select x-model="varY"
                                                class="w-full bg-gray-50 border border-gray-200 text-xs sm:text-sm font-medium rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-[#2271b1] transition-all truncate">
                                                <option value="">{{ __('Select Metric Question...') }}</option>
                                                @foreach($analysis as $item)
                                                    @php
                                                        $itemType = strtolower($item['type'] ?? '');
                                                        $isLikertMatrix = in_array($itemType, ['likert_matrix', 'likert_matrix_grid']) && !empty($item['likert_matrix_rows']);
                                                        $isContinuousMetric = !empty($item['summary_stats'])
                                                            || in_array($itemType, ['number', 'rating', 'scale', 'slider', 'range', 'star_rating', 'nps', 'likert', 'likert_item'])
                                                            || (!empty($item['isLikertLike']) && !in_array($itemType, ['select_one', 'radio', 'select_many', 'checkbox', 'select', 'text', 'textarea', 'email', 'date', 'file']));
                                                    @endphp
                                                    @if($isLikertMatrix)
                                                        <optgroup
                                                            label="Q{{ $loop->iteration }}: {{ \Illuminate\Support\Str::limit(\App\Http\Controllers\SurveyController::formatShortCategoryTheme($item['label']), 90) }}">
                                                            @foreach($item['likert_matrix_rows'] as $subIdx => $subRow)
                                                                @php
                                                                    $rawSub = $subRow['label'] ?? '';
                                                                    $cleanSub = preg_replace('/^(\d+[\.\:\)\s]+|\([a-zA-Z0-9]+\)\s*|[a-zA-Z][\.\:\)\s]+)\s*/', '', $rawSub);
                                                                    $cleanTheme = \App\Http\Controllers\SurveyController::formatShortCategoryTheme($item['label']);
                                                                @endphp
                                                                <option value="{{ $item['id'] }}__{{ $subRow['value'] }}"
                                                                    title="{{ $cleanSub }} ({{ $cleanTheme }})">
                                                                    Q{{ $loop->parent->iteration }}.{{ $subIdx + 1 }}:
                                                                    {{ \Illuminate\Support\Str::limit($cleanSub, 85) }}
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                    @elseif($isContinuousMetric && !empty($item['isChartable']))
                                                        <option value="{{ $item['id'] }}" title="{{ $item['label'] }}">
                                                            Q{{ $loop->iteration }}:
                                                            {{ \Illuminate\Support\Str::limit($item['label'], 85) }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <template x-if="getQuestionInfo(varY)">
                                                <div
                                                    class="mt-2.5 p-3.5 bg-blue-50/60 rounded-2xl border border-blue-100/80 shadow-2xs text-xs space-y-1.5 animate-in fade-in duration-200">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <div class="flex items-center gap-2">
                                                            <span
                                                                class="text-[9px] font-black tracking-wider uppercase px-2 py-0.5 rounded-md bg-white text-[#2271b1] border border-blue-200/60 shadow-2xs"
                                                                x-text="getQuestionInfo(varY).type === 'likert_item' ? '{{ __('Likert Sub-Item') }}' : getQuestionInfo(varY).type"></span>
                                                        </div>
                                                        <template
                                                            x-if="getQuestionInfo(varY).answered_count !== undefined && getQuestionInfo(varY).answered_count !== null">
                                                            <span class="text-[11px] text-gray-500 font-bold"
                                                                x-text="'N = ' + getQuestionInfo(varY).answered_count + ' responses'"></span>
                                                        </template>
                                                    </div>
                                                    <template x-if="getQuestionInfo(varY).parent_label">
                                                        <div class="text-[11px] text-gray-500 font-medium leading-relaxed">
                                                            <span
                                                                class="font-bold text-gray-700">{{ __('Context:') }}</span>
                                                            <span x-text="getQuestionInfo(varY).parent_label"></span>
                                                        </div>
                                                    </template>
                                                    <div class="text-xs font-bold text-gray-900 leading-snug break-words">
                                                        <template x-if="getQuestionInfo(varY).sub_label">
                                                            <div>
                                                                <span
                                                                    class="text-[#2271b1] font-extrabold">{{ __('Item Statement:') }}</span>
                                                                <span x-text="getQuestionInfo(varY).sub_label"></span>
                                                            </div>
                                                        </template>
                                                        <template x-if="!getQuestionInfo(varY).sub_label">
                                                            <p x-text="getQuestionInfo(varY).label"></p>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <!-- Case 4: Regression -->
                                <template x-if="testMethod === 'regression'">
                                    <div class="contents">
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-700 tracking-normal mb-2">{{ __('Dependent Variable Y (Numeric / Metric)') }}</label>
                                            <select x-model="depVar"
                                                class="w-full bg-gray-50 border border-gray-200 text-xs sm:text-sm font-medium rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-[#2271b1] transition-all truncate">
                                                <option value="">{{ __('Select Metric Question...') }}</option>
                                                @foreach($analysis as $item)
                                                    @php
                                                        $itemType = strtolower($item['type'] ?? '');
                                                        $isLikertMatrix = in_array($itemType, ['likert_matrix', 'likert_matrix_grid']) && !empty($item['likert_matrix_rows']);
                                                        $isContinuousMetric = !empty($item['summary_stats'])
                                                            || in_array($itemType, ['number', 'rating', 'scale', 'slider', 'range', 'star_rating', 'nps', 'likert', 'likert_item'])
                                                            || (!empty($item['isLikertLike']) && !in_array($itemType, ['select_one', 'radio', 'select_many', 'checkbox', 'select', 'text', 'textarea', 'email', 'date', 'file']));
                                                    @endphp
                                                    @if($isLikertMatrix)
                                                        <optgroup
                                                            label="Q{{ $loop->iteration }}: {{ \Illuminate\Support\Str::limit(\App\Http\Controllers\SurveyController::formatShortCategoryTheme($item['label']), 90) }}">
                                                            @foreach($item['likert_matrix_rows'] as $subIdx => $subRow)
                                                                @php
                                                                    $rawSub = $subRow['label'] ?? '';
                                                                    $cleanSub = preg_replace('/^(\d+[\.\:\)\s]+|\([a-zA-Z0-9]+\)\s*|[a-zA-Z][\.\:\)\s]+)\s*/', '', $rawSub);
                                                                    $cleanTheme = \App\Http\Controllers\SurveyController::formatShortCategoryTheme($item['label']);
                                                                @endphp
                                                                <option value="{{ $item['id'] }}__{{ $subRow['value'] }}"
                                                                    title="{{ $cleanSub }} ({{ $cleanTheme }})">
                                                                    Q{{ $loop->parent->iteration }}.{{ $subIdx + 1 }}:
                                                                    {{ \Illuminate\Support\Str::limit($cleanSub, 85) }}
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                    @elseif($isContinuousMetric && !empty($item['isChartable']))
                                                        <option value="{{ $item['id'] }}" title="{{ $item['label'] }}">
                                                            Q{{ $loop->iteration }}:
                                                            {{ \Illuminate\Support\Str::limit($item['label'], 85) }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <template x-if="getQuestionInfo(depVar)">
                                                <div
                                                    class="mt-2.5 p-3.5 bg-blue-50/60 rounded-2xl border border-blue-100/80 shadow-2xs text-xs space-y-1.5 animate-in fade-in duration-200">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <div class="flex items-center gap-2">
                                                            <span
                                                                class="text-[9px] font-black tracking-wider uppercase px-2 py-0.5 rounded-md bg-white text-[#2271b1] border border-blue-200/60 shadow-2xs"
                                                                x-text="getQuestionInfo(depVar).type === 'likert_item' ? '{{ __('Likert Sub-Item') }}' : getQuestionInfo(depVar).type"></span>
                                                        </div>
                                                        <template
                                                            x-if="getQuestionInfo(depVar).answered_count !== undefined && getQuestionInfo(depVar).answered_count !== null">
                                                            <span class="text-[11px] text-gray-500 font-bold"
                                                                x-text="'N = ' + getQuestionInfo(depVar).answered_count + ' responses'"></span>
                                                        </template>
                                                    </div>
                                                    <template x-if="getQuestionInfo(depVar).parent_label">
                                                        <div class="text-[11px] text-gray-500 font-medium leading-relaxed">
                                                            <span
                                                                class="font-bold text-gray-700">{{ __('Context:') }}</span>
                                                            <span x-text="getQuestionInfo(depVar).parent_label"></span>
                                                        </div>
                                                    </template>
                                                    <div class="text-xs font-bold text-gray-900 leading-snug break-words">
                                                        <template x-if="getQuestionInfo(depVar).sub_label">
                                                            <div>
                                                                <span
                                                                    class="text-[#2271b1] font-extrabold">{{ __('Item Statement:') }}</span>
                                                                <span x-text="getQuestionInfo(depVar).sub_label"></span>
                                                            </div>
                                                        </template>
                                                        <template x-if="!getQuestionInfo(depVar).sub_label">
                                                            <p x-text="getQuestionInfo(depVar).label"></p>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-700 tracking-normal mb-2">{{ __('Independent Variable X (Numeric / Metric)') }}</label>
                                            <select x-model="groupVar"
                                                class="w-full bg-gray-50 border border-gray-200 text-xs sm:text-sm font-medium rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-[#2271b1] transition-all truncate">
                                                <option value="">{{ __('Select Metric Question...') }}</option>
                                                @foreach($analysis as $item)
                                                    @php
                                                        $itemType = strtolower($item['type'] ?? '');
                                                        $isLikertMatrix = in_array($itemType, ['likert_matrix', 'likert_matrix_grid']) && !empty($item['likert_matrix_rows']);
                                                        $isContinuousMetric = !empty($item['summary_stats'])
                                                            || in_array($itemType, ['number', 'rating', 'scale', 'slider', 'range', 'star_rating', 'nps', 'likert', 'likert_item'])
                                                            || (!empty($item['isLikertLike']) && !in_array($itemType, ['select_one', 'radio', 'select_many', 'checkbox', 'select', 'text', 'textarea', 'email', 'date', 'file']));
                                                    @endphp
                                                    @if($isLikertMatrix)
                                                        <optgroup
                                                            label="Q{{ $loop->iteration }}: {{ \Illuminate\Support\Str::limit(\App\Http\Controllers\SurveyController::formatShortCategoryTheme($item['label']), 90) }}">
                                                            @foreach($item['likert_matrix_rows'] as $subIdx => $subRow)
                                                                @php
                                                                    $rawSub = $subRow['label'] ?? '';
                                                                    $cleanSub = preg_replace('/^(\d+[\.\:\)\s]+|\([a-zA-Z0-9]+\)\s*|[a-zA-Z][\.\:\)\s]+)\s*/', '', $rawSub);
                                                                    $cleanTheme = \App\Http\Controllers\SurveyController::formatShortCategoryTheme($item['label']);
                                                                @endphp
                                                                <option value="{{ $item['id'] }}__{{ $subRow['value'] }}"
                                                                    title="{{ $cleanSub }} ({{ $cleanTheme }})">
                                                                    Q{{ $loop->parent->iteration }}.{{ $subIdx + 1 }}:
                                                                    {{ \Illuminate\Support\Str::limit($cleanSub, 85) }}
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                    @elseif($isContinuousMetric && !empty($item['isChartable']))
                                                        <option value="{{ $item['id'] }}" title="{{ $item['label'] }}">
                                                            Q{{ $loop->iteration }}:
                                                            {{ \Illuminate\Support\Str::limit($item['label'], 85) }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <template x-if="getQuestionInfo(groupVar)">
                                                <div
                                                    class="mt-2.5 p-3.5 bg-blue-50/60 rounded-2xl border border-blue-100/80 shadow-2xs text-xs space-y-1.5 animate-in fade-in duration-200">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <div class="flex items-center gap-2">
                                                            <span
                                                                class="text-[9px] font-black tracking-wider uppercase px-2 py-0.5 rounded-md bg-white text-[#2271b1] border border-blue-200/60 shadow-2xs"
                                                                x-text="getQuestionInfo(groupVar).type === 'likert_item' ? '{{ __('Likert Sub-Item') }}' : getQuestionInfo(groupVar).type"></span>
                                                        </div>
                                                        <template
                                                            x-if="getQuestionInfo(groupVar).answered_count !== undefined && getQuestionInfo(groupVar).answered_count !== null">
                                                            <span class="text-[11px] text-gray-500 font-bold"
                                                                x-text="'N = ' + getQuestionInfo(groupVar).answered_count + ' responses'"></span>
                                                        </template>
                                                    </div>
                                                    <template x-if="getQuestionInfo(groupVar).parent_label">
                                                        <div class="text-[11px] text-gray-500 font-medium leading-relaxed">
                                                            <span
                                                                class="font-bold text-gray-700">{{ __('Context:') }}</span>
                                                            <span x-text="getQuestionInfo(groupVar).parent_label"></span>
                                                        </div>
                                                    </template>
                                                    <div class="text-xs font-bold text-gray-900 leading-snug break-words">
                                                        <template x-if="getQuestionInfo(groupVar).sub_label">
                                                            <div>
                                                                <span
                                                                    class="text-[#2271b1] font-extrabold">{{ __('Item Statement:') }}</span>
                                                                <span x-text="getQuestionInfo(groupVar).sub_label"></span>
                                                            </div>
                                                        </template>
                                                        <template x-if="!getQuestionInfo(groupVar).sub_label">
                                                            <p x-text="getQuestionInfo(groupVar).label"></p>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <!-- Case 5: Multiple Linear Regression -->
                                <template x-if="testMethod === 'regression_multiple'">
                                    <div class="contents">
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-700 tracking-normal mb-2">{{ __('Dependent Variable Y (Numeric / Metric)') }}</label>
                                            <select x-model="depVar"
                                                class="w-full bg-gray-50 border border-gray-200 text-xs sm:text-sm font-medium rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-[#2271b1] transition-all truncate">
                                                <option value="">{{ __('Select Metric Question...') }}</option>
                                                @foreach($analysis as $item)
                                                    @php
                                                        $itemType = strtolower($item['type'] ?? '');
                                                        $isLikertMatrix = in_array($itemType, ['likert_matrix', 'likert_matrix_grid']) && !empty($item['likert_matrix_rows']);
                                                        $isContinuousMetric = !empty($item['summary_stats'])
                                                            || in_array($itemType, ['number', 'rating', 'scale', 'slider', 'range', 'star_rating', 'nps', 'likert', 'likert_item'])
                                                            || (!empty($item['isLikertLike']) && !in_array($itemType, ['select_one', 'radio', 'select_many', 'checkbox', 'select', 'text', 'textarea', 'email', 'date', 'file']));
                                                    @endphp
                                                    @if($isLikertMatrix)
                                                        <optgroup
                                                            label="Q{{ $loop->iteration }}: {{ \Illuminate\Support\Str::limit(\App\Http\Controllers\SurveyController::formatShortCategoryTheme($item['label']), 90) }}">
                                                            @foreach($item['likert_matrix_rows'] as $subIdx => $subRow)
                                                                @php
                                                                    $rawSub = $subRow['label'] ?? '';
                                                                    $cleanSub = preg_replace('/^(\d+[\.\:\)\s]+|\([a-zA-Z0-9]+\)\s*|[a-zA-Z][\.\:\)\s]+)\s*/', '', $rawSub);
                                                                    $cleanTheme = \App\Http\Controllers\SurveyController::formatShortCategoryTheme($item['label']);
                                                                @endphp
                                                                <option value="{{ $item['id'] }}__{{ $subRow['value'] }}"
                                                                    title="{{ $cleanSub }} ({{ $cleanTheme }})">
                                                                    Q{{ $loop->parent->iteration }}.{{ $subIdx + 1 }}:
                                                                    {{ \Illuminate\Support\Str::limit($cleanSub, 85) }}
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                    @elseif($isContinuousMetric && !empty($item['isChartable']))
                                                        <option value="{{ $item['id'] }}" title="{{ $item['label'] }}">
                                                            Q{{ $loop->iteration }}:
                                                            {{ \Illuminate\Support\Str::limit($item['label'], 85) }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <template x-if="getQuestionInfo(depVar)">
                                                <div
                                                    class="mt-2.5 p-3.5 bg-blue-50/60 rounded-2xl border border-blue-100/80 shadow-2xs text-xs space-y-1.5 animate-in fade-in duration-200">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <div class="flex items-center gap-2">
                                                            <span
                                                                class="text-[9px] font-black tracking-wider uppercase px-2 py-0.5 rounded-md bg-white text-[#2271b1] border border-blue-200/60 shadow-2xs"
                                                                x-text="getQuestionInfo(depVar).type === 'likert_item' ? '{{ __('Likert Sub-Item') }}' : getQuestionInfo(depVar).type"></span>
                                                        </div>
                                                        <template
                                                            x-if="getQuestionInfo(depVar).answered_count !== undefined && getQuestionInfo(depVar).answered_count !== null">
                                                            <span class="text-[11px] text-gray-500 font-bold"
                                                                x-text="'N = ' + getQuestionInfo(depVar).answered_count + ' responses'"></span>
                                                        </template>
                                                    </div>
                                                    <template x-if="getQuestionInfo(depVar).parent_label">
                                                        <div class="text-[11px] text-gray-500 font-medium leading-relaxed">
                                                            <span
                                                                class="font-bold text-gray-700">{{ __('Context:') }}</span>
                                                            <span x-text="getQuestionInfo(depVar).parent_label"></span>
                                                        </div>
                                                    </template>
                                                    <div class="text-xs font-bold text-gray-900 leading-snug break-words">
                                                        <template x-if="getQuestionInfo(depVar).sub_label">
                                                            <div>
                                                                <span
                                                                    class="text-[#2271b1] font-extrabold">{{ __('Item Statement:') }}</span>
                                                                <span x-text="getQuestionInfo(depVar).sub_label"></span>
                                                            </div>
                                                        </template>
                                                        <template x-if="!getQuestionInfo(depVar).sub_label">
                                                            <p x-text="getQuestionInfo(depVar).label"></p>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        <div>
                                            <div class="flex items-center justify-between mb-2">
                                                <label
                                                    class="block text-xs font-bold text-gray-700 tracking-normal">{{ __('Independent Variables X (Select Multiple Numeric / Metric)') }}</label>
                                                <template x-if="indVars.length > 0">
                                                    <span
                                                        class="text-[10px] font-bold text-[#2271b1] bg-blue-50 px-2 py-0.5 rounded"
                                                        x-text="indVars.length + ' {{ __('selected') }}'"></span>
                                                </template>
                                            </div>
                                            <div
                                                class="bg-gray-50 border border-gray-200 rounded-xl p-4 max-h-[160px] overflow-y-auto space-y-2.5">
                                                @foreach($analysis as $item)
                                                    @php
                                                        $itemType = strtolower($item['type'] ?? '');
                                                        $isLikertMatrix = in_array($itemType, ['likert_matrix', 'likert_matrix_grid']) && !empty($item['likert_matrix_rows']);
                                                        $isContinuousMetric = !empty($item['summary_stats'])
                                                            || in_array($itemType, ['number', 'rating', 'scale', 'slider', 'range', 'star_rating', 'nps', 'likert', 'likert_item'])
                                                            || (!empty($item['isLikertLike']) && !in_array($itemType, ['select_one', 'radio', 'select_many', 'checkbox', 'select', 'text', 'textarea', 'email', 'date', 'file']));
                                                    @endphp
                                                    @if($isLikertMatrix)
                                                        @php
                                                            $cleanTheme = \App\Http\Controllers\SurveyController::formatShortCategoryTheme($item['label']);
                                                        @endphp
                                                        <div
                                                            class="text-[11px] font-black text-gray-500 uppercase tracking-wider pt-1 border-t border-gray-200/60 first:border-t-0 first:pt-0">
                                                            Q{{ $loop->iteration }}:
                                                            {{ \Illuminate\Support\Str::limit($cleanTheme, 75) }}
                                                        </div>
                                                        @foreach($item['likert_matrix_rows'] as $subIdx => $subRow)
                                                            @php
                                                                $rawSub = $subRow['label'] ?? '';
                                                                $cleanSub = preg_replace('/^(\d+[\.\:\)\s]+|\([a-zA-Z0-9]+\)\s*|[a-zA-Z][\.\:\)\s]+)\s*/', '', $rawSub);
                                                            @endphp
                                                            <label
                                                                class="flex items-center gap-2.5 text-xs font-semibold text-gray-700 hover:text-[#2271b1] transition-colors cursor-pointer pl-2"
                                                                x-show="depVar !== '{{ $item['id'] }}__{{ $subRow['value'] }}'"
                                                                :class="depVar === '{{ $item['id'] }}__{{ $subRow['value'] }}' ? 'opacity-30 pointer-events-none' : ''"
                                                                title="{{ $cleanSub }} ({{ $cleanTheme }})">
                                                                <input type="checkbox"
                                                                    :value="'{{ $item['id'] }}__{{ $subRow['value'] }}'"
                                                                    x-model="indVars"
                                                                    :disabled="depVar === '{{ $item['id'] }}__{{ $subRow['value'] }}'"
                                                                    class="rounded text-[#2271b1] focus:ring-[#2271b1] border-gray-300">
                                                                <span
                                                                    class="truncate">Q{{ $loop->parent->iteration }}.{{ $subIdx + 1 }}:
                                                                    {{ \Illuminate\Support\Str::limit($cleanSub, 85) }}</span>
                                                            </label>
                                                        @endforeach
                                                    @elseif($isContinuousMetric && !empty($item['isChartable']))
                                                        <label
                                                            class="flex items-center gap-2.5 text-xs font-semibold text-gray-700 hover:text-[#2271b1] transition-colors cursor-pointer"
                                                            x-show="depVar !== '{{ $item['id'] }}'"
                                                            :class="depVar === '{{ $item['id'] }}' ? 'opacity-30 pointer-events-none' : ''"
                                                            title="{{ $item['label'] }}">
                                                            <input type="checkbox" :value="'{{ $item['id'] }}'" x-model="indVars"
                                                                :disabled="depVar === '{{ $item['id'] }}'"
                                                                class="rounded text-[#2271b1] focus:ring-[#2271b1] border-gray-300">
                                                            <span class="truncate">Q{{ $loop->iteration }}:
                                                                {{ \Illuminate\Support\Str::limit($item['label'], 85) }}</span>
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div
                                class="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-gray-50 mt-6 pt-6">
                                <div>
                                    <template x-if="testMethod === 'cronbach'">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold px-3 py-1.5 rounded-xl flex items-center gap-1.5"
                                                :class="cronbachItems.length >= 2 ? 'text-emerald-700 bg-emerald-50 border border-emerald-200/80 shadow-2xs' : 'text-amber-800 bg-amber-50 border border-amber-200/80 shadow-2xs'">
                                                <i
                                                    :class="cronbachItems.length >= 2 ? 'fa-solid fa-circle-check text-emerald-600' : 'fa-solid fa-circle-info text-amber-600'"></i>
                                                <span
                                                    x-text="cronbachItems.length >= 2 ? cronbachItems.length + ' {{ __('scale items selected (Ready to evaluate)') }}' : '{{ __('Select at least 2 items to run reliability (Current: ') }}' + cronbachItems.length + '/2)'"></span>
                                            </span>
                                        </div>
                                    </template>
                                </div>
                                <button @click="runAnalysis()" :disabled="loading || isRunDisabled()"
                                    class="w-full sm:w-auto px-8 py-3 bg-[#2271b1] text-white rounded-2xl font-black text-[10px] tracking-widest hover:bg-[#135e96] transition-all shadow-lg shadow-blue-100 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                    <i class="fa-solid fa-calculator" :class="{'fa-spin': loading}"></i> <span
                                        x-text="loading ? '{{ __('Calculating...') }}' : '{{ __('Run Analysis') }}'"></span>
                                </button>
                            </div>
                        </div>

                        <!-- Insufficient Data / Analysis Error Banner -->
                        <template x-if="analysisError">
                            <div
                                class="p-5 bg-amber-50/90 border border-amber-200 rounded-3xl flex items-start gap-3.5 shadow-xs animate-in fade-in duration-300">
                                <div
                                    class="w-9 h-9 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                                    <i class="fa-solid fa-triangle-exclamation text-base"></i>
                                </div>
                                <div class="space-y-1">
                                    <h5 class="text-xs font-black text-amber-900 uppercase tracking-wider">
                                        {{ __('Cannot Compute Analysis') }}
                                    </h5>
                                    <p class="text-xs font-medium text-amber-800 leading-relaxed" x-text="analysisError">
                                    </p>
                                </div>
                            </div>
                        </template>

                        <!-- 1. Pure Crosstabulation Results ( -->
                        <template x-if="testMethod === 'crosstab' && matrixData">
                            <div class="space-y-8 animate-in fade-in duration-500">
                                <div
                                    class="bg-white rounded-3xl p-4 sm:p-6 md:p-8 border border-gray-100 shadow-sm space-y-6 overflow-hidden">
                                    <div class="border-b border-gray-100 pb-4 flex justify-between items-center">
                                        <div>
                                            <h4 class="text-lg font-black text-gray-900">
                                                {{ __('Cross-Tabulation Contingency Table') }}
                                            </h4>
                                            <p class="text-xs text-gray-500 mt-1"
                                                x-text="'Joint frequency distribution of ' + formatShortVarLabel(matrixData.rowLabel, 40) + ' vs ' + formatShortVarLabel(matrixData.colLabel, 40)"
                                                :title="(matrixData.rowLabel || 'Row') + ' vs ' + (matrixData.colLabel || 'Column')">
                                            </p>
                                        </div>
                                    </div>

                                    <!-- SPSS Case Processing Summary -->
                                    <template x-if="matrixData && matrixData.case_summary">
                                        <div class="border border-gray-200 rounded-2xl overflow-hidden shadow-xs">
                                            <div
                                                class="bg-gray-50/90 px-4 py-2.5 border-b border-gray-200 flex items-center justify-between">
                                                <span
                                                    class="text-xs font-bold text-gray-700 uppercase tracking-wider">{{ __('Case Processing Summary') }}</span>
                                                <span class="text-[11px] text-gray-500 font-medium"
                                                    x-text="'Total Sample N = ' + matrixData.case_summary.total_n"></span>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left text-xs text-gray-600">
                                                    <thead
                                                        class="bg-gray-50/50 text-[11px] font-bold text-gray-500 uppercase border-b border-gray-100">
                                                        <tr>
                                                            <th class="py-2.5 px-4">{{ __('Variables Analyzed') }}</th>
                                                            <th class="py-2.5 px-4 text-center">{{ __('Valid N') }}</th>
                                                            <th class="py-2.5 px-4 text-center">{{ __('Valid %') }}</th>
                                                            <th class="py-2.5 px-4 text-center">{{ __('Excluded N') }}</th>
                                                            <th class="py-2.5 px-4 text-center">{{ __('Excluded %') }}</th>
                                                            <th class="py-2.5 px-4 text-center">{{ __('Total N') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100">
                                                        <tr>
                                                            <td class="py-2.5 px-4 font-semibold text-gray-800"
                                                                :title="(matrixData.rowLabel || 'Row') + ' * ' + (matrixData.colLabel || 'Column')">
                                                                <span
                                                                    x-text="formatShortVarLabel(matrixData.rowLabel, 40) + ' * ' + formatShortVarLabel(matrixData.colLabel, 40)"></span>
                                                            </td>
                                                            <td class="py-2.5 px-4 text-center font-bold text-indigo-700"
                                                                x-text="matrixData.case_summary.valid_n"></td>
                                                            <td class="py-2.5 px-4 text-center font-semibold text-indigo-700"
                                                                x-text="Number(matrixData.case_summary.valid_pct).toFixed(1) + '%'">
                                                            </td>
                                                            <td class="py-2.5 px-4 text-center font-medium text-amber-600"
                                                                x-text="matrixData.case_summary.missing_n"></td>
                                                            <td class="py-2.5 px-4 text-center font-medium text-amber-600"
                                                                x-text="Number(matrixData.case_summary.missing_pct).toFixed(1) + '%'">
                                                            </td>
                                                            <td class="py-2.5 px-4 text-center font-bold text-gray-900"
                                                                x-text="matrixData.case_summary.total_n"></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </template>

                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left border-collapse min-w-[600px]">
                                            <thead>
                                                <tr>
                                                    <th class="p-4 border-b border-r border-gray-200 bg-gray-50 w-1/4 text-xs font-semibold text-gray-700"
                                                        :title="(matrixData.rowLabel || 'Row') + ' \\ ' + (matrixData.colLabel || 'Column')">
                                                        <div class="space-y-0.5">
                                                            <div class="text-indigo-900 font-bold"
                                                                x-text="formatShortVarLabel(matrixData.rowLabel, 35)"></div>
                                                            <div class="text-gray-500 text-[10px] font-medium"
                                                                x-text="'\\ ' + formatShortVarLabel(matrixData.colLabel, 35)">
                                                            </div>
                                                        </div>
                                                    </th>
                                                    <template x-for="col in matrixData.columns" :key="col">
                                                        <th class="p-4 border-b border-gray-200 bg-gray-50 text-xs font-medium text-gray-600  tracking-widest text-center"
                                                            x-text="col"></th>
                                                    </template>
                                                    <th
                                                        class="p-4 border-b border-l border-gray-200 bg-indigo-50 text-[12px] font-black text-indigo-800  tracking-widest text-center">
                                                        {{ __('Total') }}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="row in matrixData.rows" :key="row">
                                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                                        <th class="p-4 border-b border-r border-gray-200 text-[11px] font-black text-gray-700"
                                                            x-text="row"></th>
                                                        <template x-for="col in matrixData.columns" :key="col">
                                                            <td
                                                                class="p-3 border-b border-gray-100 text-center text-xs font-medium text-gray-700 align-top">
                                                                <div
                                                                    class="flex flex-col items-center justify-start gap-1 min-w-[90px]">
                                                                    <!-- Count (Bold Large) -->
                                                                    <div class="font-black text-gray-900 text-sm leading-tight"
                                                                        x-text="getMatrixValue(row, col)"></div>

                                                                    <!-- Expected Count / Badge -->
                                                                    <div class="h-5 flex items-center justify-center">
                                                                        <template
                                                                            x-if="matrixData.expectedMatrix && matrixData.expectedMatrix[row]">
                                                                            <span
                                                                                class="text-[10px] leading-tight px-1.5 py-0.5 rounded transition-all inline-flex items-center justify-center"
                                                                                :class="(Number(((matrixData.expectedMatrix[row] || {})[col] || 0)) < 5) 
                                                                                                                                                        ? 'text-amber-800 font-bold bg-amber-50 border border-amber-200/80 shadow-2xs' 
                                                                                                                                                        : 'text-gray-500 font-medium border border-transparent'"
                                                                                x-text="'Exp: ' + formatExpectedCount((matrixData.expectedMatrix[row] || {})[col])">
                                                                            </span>
                                                                        </template>
                                                                    </div>

                                                                    <!-- Percentages Block (Row %, Col %, Tot %) -->
                                                                    <div
                                                                        class="w-full pt-1.5 border-t border-gray-100 space-y-0.5 text-[10px] text-gray-500 font-medium text-left px-1">
                                                                        <div class="flex items-center justify-between">
                                                                            <span class="text-gray-400 font-normal">%
                                                                                Row:</span>
                                                                            <span class="font-bold text-gray-700"
                                                                                x-text="Number(((matrixData.rowPercentages || {})[row] || {})[col] || 0).toFixed(1) + '%'"></span>
                                                                        </div>
                                                                        <div class="flex items-center justify-between">
                                                                            <span class="text-gray-400 font-normal">%
                                                                                Col:</span>
                                                                            <span class="font-bold text-gray-700"
                                                                                x-text="Number(((matrixData.colPercentages || {})[row] || {})[col] || 0).toFixed(1) + '%'"></span>
                                                                        </div>
                                                                        <div class="flex items-center justify-between">
                                                                            <span class="text-gray-400 font-normal">%
                                                                                Tot:</span>
                                                                            <span class="font-bold text-gray-700"
                                                                                x-text="Number(((matrixData.totalPercentages || {})[row] || {})[col] || 0).toFixed(1) + '%'"></span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </template>
                                                        <td
                                                            class="p-3 border-b border-l border-gray-200 bg-indigo-50/30 text-center text-sm font-black text-indigo-700 align-top">
                                                            <div x-text="(matrixData.rowTotals || {})[row] || 0"></div>
                                                            <div class="text-[10px] font-bold text-indigo-600 mt-1"
                                                                x-text="matrixData.grandTotal > 0 ? ((((matrixData.rowTotals || {})[row] || 0) / matrixData.grandTotal * 100).toFixed(1) + '%') : '0.0%'">
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th
                                                        class="p-4 border-t border-r border-gray-200 bg-indigo-50 text-[12px] font-medium text-indigo-800  tracking-widest">
                                                        {{ __('Total') }}
                                                    </th>
                                                    <template x-for="col in matrixData.columns" :key="col">
                                                        <th
                                                            class="p-3 border-t border-gray-200 bg-indigo-50 text-center text-sm font-black text-indigo-800">
                                                            <div x-text="(matrixData.colTotals || {})[col] || 0"></div>
                                                            <div class="text-[10px] font-bold text-indigo-600 mt-0.5"
                                                                x-text="Number((matrixData.colTotalPercentages || {})[col] || 0).toFixed(1) + '%'">
                                                            </div>
                                                        </th>
                                                    </template>
                                                    <th
                                                        class="p-4 border-t border-l border-indigo-200 bg-indigo-100 text-center text-sm font-black text-indigo-900">
                                                        <div x-text="matrixData.grandTotal || 0"></div>
                                                        <div class="text-[10px] font-medium text-indigo-700 mt-0.5">100.0%
                                                        </div>
                                                    </th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>

                                    <!-- Cochran Assumption Warning / Footnote for Crosstab -->
                                    <template x-if="matrixData && matrixData.assumptionWarning">
                                        <div class="p-4 rounded-2xl border text-xs flex items-start gap-3 transition-all shadow-xs"
                                            :class="matrixData.assumptionWarning.is_violated ? 'bg-amber-50/80 border-amber-200 text-amber-900' : 'bg-gray-50/80 border-gray-200 text-gray-700'">
                                            <i class="fa-solid mt-0.5 text-sm shrink-0"
                                                :class="matrixData.assumptionWarning.is_violated ? 'fa-triangle-exclamation text-amber-500' : 'fa-circle-check text-emerald-500'"></i>
                                            <div class="space-y-1 w-full">
                                                <div class="font-bold flex items-center justify-between">
                                                    <span
                                                        x-text="matrixData.assumptionWarning.is_violated ? '{{ __('Cochran Chi-Square Assumption Check (Violated)') }}' : '{{ __('Contingency Table Assumption Check (Passed)') }}'"></span>
                                                    <span
                                                        class="px-2 py-0.5 rounded-full text-[10px] font-black tracking-wider uppercase"
                                                        :class="matrixData.assumptionWarning.is_violated ? 'bg-amber-200 text-amber-900' : 'bg-emerald-100 text-emerald-800'"
                                                        x-text="matrixData.assumptionWarning.is_violated ? '{{ __('Assumption Violated') }}' : '{{ __('Assumptions Met') }}'"></span>
                                                </div>
                                                <p class="text-[11px] leading-relaxed text-gray-700"
                                                    x-text="matrixData.assumptionWarning.message"></p>
                                                <template x-if="matrixData.assumptionWarning.is_violated">
                                                    <p
                                                        class="text-[11px] font-medium text-amber-800 leading-relaxed pt-0.5">
                                                        {{ __('Methodological note: More than 20% of cells have expected counts below 5. Consider collapsing sparse adjacent categories or evaluating Fisher’s Exact Test.') }}
                                                    </p>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- AI Narrative Insight Card for Crosstab -->
                                    <template x-if="matrixData && matrixData.aiSummary">
                                        <div
                                            class="p-5 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 rounded-2xl border border-indigo-100 shadow-sm space-y-2">
                                            <div class="flex items-center gap-2 text-indigo-950 font-bold text-xs">
                                                <i class="fa-solid fa-sparkles text-indigo-600"></i>
                                                <span>{{ __('AI Cross-Tabulation Academic Synthesis') }}</span>
                                            </div>
                                            <p class="text-xs text-gray-800 font-medium leading-relaxed"
                                                x-text="matrixData.aiSummary"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- 2. Standalone Chi-Square Test Results -->
                        <template x-if="testMethod === 'chisquare' && (chisquareData || matrixData)">
                            <div class="space-y-8 animate-in fade-in duration-500">
                                <div
                                    class="bg-white rounded-3xl p-4 sm:p-6 md:p-8 border border-gray-100 shadow-sm space-y-6 overflow-hidden">
                                    <div class="border-b border-gray-100 pb-4 flex justify-between items-center">
                                        <div>
                                            <h4 class="text-lg font-black text-gray-900">
                                                {{ __('Chi-Square for Independence') }}
                                            </h4>
                                            <p class="text-xs text-gray-500 mt-1"
                                                x-text="'Hypothesis test of independence between ' + ((chisquareData || matrixData).rowLabel || 'Row') + ' and ' + ((chisquareData || matrixData).colLabel || 'Column')">
                                            </p>
                                        </div>
                                    </div>

                                    <!-- SPSS Case Processing Summary for Chi-Square -->
                                    <template
                                        x-if="(chisquareData || matrixData) && (chisquareData || matrixData).case_summary">
                                        <div class="border border-gray-200 rounded-2xl overflow-hidden shadow-xs">
                                            <div
                                                class="bg-gray-50/90 px-4 py-2.5 border-b border-gray-200 flex items-center justify-between">
                                                <span
                                                    class="text-xs font-bold text-gray-700 uppercase tracking-wider">{{ __('Case Processing Summary') }}</span>
                                                <span class="text-[11px] text-gray-500 font-medium"
                                                    x-text="'Total Sample N = ' + (chisquareData || matrixData).case_summary.total_n"></span>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left text-xs text-gray-600">
                                                    <thead
                                                        class="bg-gray-50/50 text-[11px] font-bold text-gray-500 uppercase border-b border-gray-100">
                                                        <tr>
                                                            <th class="py-2.5 px-4">{{ __('Variables Analyzed') }}</th>
                                                            <th class="py-2.5 px-4 text-center">{{ __('Valid N') }}</th>
                                                            <th class="py-2.5 px-4 text-center">{{ __('Valid %') }}</th>
                                                            <th class="py-2.5 px-4 text-center">{{ __('Excluded N') }}</th>
                                                            <th class="py-2.5 px-4 text-center">{{ __('Excluded %') }}</th>
                                                            <th class="py-2.5 px-4 text-center">{{ __('Total N') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100">
                                                        <tr>
                                                            <td class="py-2.5 px-4 font-semibold text-gray-800"
                                                                x-text="((chisquareData || matrixData).rowLabel || 'Row') + ' * ' + ((chisquareData || matrixData).colLabel || 'Column')">
                                                            </td>
                                                            <td class="py-2.5 px-4 text-center font-bold text-indigo-700"
                                                                x-text="(chisquareData || matrixData).case_summary.valid_n">
                                                            </td>
                                                            <td class="py-2.5 px-4 text-center font-semibold text-indigo-700"
                                                                x-text="Number((chisquareData || matrixData).case_summary.valid_pct).toFixed(1) + '%'">
                                                            </td>
                                                            <td class="py-2.5 px-4 text-center font-medium text-amber-600"
                                                                x-text="(chisquareData || matrixData).case_summary.missing_n">
                                                            </td>
                                                            <td class="py-2.5 px-4 text-center font-medium text-amber-600"
                                                                x-text="Number((chisquareData || matrixData).case_summary.missing_pct).toFixed(1) + '%'">
                                                            </td>
                                                            <td class="py-2.5 px-4 text-center font-bold text-gray-900"
                                                                x-text="(chisquareData || matrixData).case_summary.total_n">
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Crosstabulation Contingency Table for Chi-Square -->
                                    <template x-if="(chisquareData || matrixData) && (chisquareData || matrixData).rows">
                                        <div class="border-t border-gray-100 pt-6 space-y-3">
                                            <div class="flex items-center justify-between">
                                                <h5 class="text-sm font-black text-gray-900">
                                                    {{ __('Cross-Tabulation Contingency Table') }}
                                                </h5>
                                                <span
                                                    class="text-[11px] text-gray-500 font-medium">{{ __('Observed Counts, Expected Frequencies & Percentages') }}</span>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left border-collapse min-w-[600px]">
                                                    <thead>
                                                        <tr>
                                                            <th class="p-4 border-b border-r border-gray-200 bg-gray-50 w-1/4 text-xs font-semibold text-gray-700"
                                                                :title="((chisquareData || matrixData).rowLabel || 'Row') + ' \\ ' + ((chisquareData || matrixData).colLabel || 'Column')">
                                                                <div class="space-y-0.5">
                                                                    <div class="text-indigo-900 font-bold"
                                                                        x-text="formatShortVarLabel((chisquareData || matrixData).rowLabel, 35)">
                                                                    </div>
                                                                    <div class="text-gray-500 text-[10px] font-medium"
                                                                        x-text="'\\ ' + formatShortVarLabel((chisquareData || matrixData).colLabel, 35)">
                                                                    </div>
                                                                </div>
                                                            </th>
                                                            <template x-for="col in (chisquareData || matrixData).columns"
                                                                :key="col">
                                                                <th class="p-4 border-b border-gray-200 bg-gray-50 text-xs font-medium text-gray-600 tracking-widest text-center"
                                                                    x-text="col"></th>
                                                            </template>
                                                            <th
                                                                class="p-4 border-b border-l border-gray-200 bg-indigo-50 text-[12px] font-black text-indigo-800 tracking-widest text-center">
                                                                {{ __('Total') }}
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <template x-for="row in (chisquareData || matrixData).rows"
                                                            :key="row">
                                                            <tr class="hover:bg-gray-50/50 transition-colors">
                                                                <th class="p-4 border-b border-r border-gray-200 text-[11px] font-black text-gray-700"
                                                                    x-text="row"></th>
                                                                <template
                                                                    x-for="col in (chisquareData || matrixData).columns"
                                                                    :key="col">
                                                                    <td
                                                                        class="p-3 border-b border-gray-100 text-center text-xs font-medium text-gray-700 align-top">
                                                                        <div
                                                                            class="flex flex-col items-center justify-start gap-1 min-w-[90px]">
                                                                            <!-- Count (Bold Large) -->
                                                                            <div class="font-black text-gray-900 text-sm leading-tight"
                                                                                x-text="getMatrixValue(row, col)"></div>

                                                                            <!-- Expected Count / Badge Slot -->
                                                                            <div
                                                                                class="h-5 flex items-center justify-center">
                                                                                <template
                                                                                    x-if="(chisquareData || matrixData).expectedMatrix && (chisquareData || matrixData).expectedMatrix[row]">
                                                                                    <span
                                                                                        class="text-[10px] leading-tight px-1.5 py-0.5 rounded transition-all inline-flex items-center justify-center"
                                                                                        :class="(Number((((chisquareData || matrixData).expectedMatrix[row] || {})[col] || 0)) < 5) 
                                                                                                                                                                ? 'text-amber-800 font-bold bg-amber-50 border border-amber-200/80 shadow-2xs' 
                                                                                                                                                                : 'text-gray-500 font-medium border border-transparent'"
                                                                                        x-text="'Exp: ' + formatExpectedCount(((chisquareData || matrixData).expectedMatrix[row] || {})[col])">
                                                                                    </span>
                                                                                </template>
                                                                            </div>

                                                                            <!-- Percentages Block (Row %, Col %, Tot %) -->
                                                                            <div
                                                                                class="w-full pt-1.5 border-t border-gray-100 space-y-0.5 text-[10px] text-gray-500 font-medium text-left px-1">
                                                                                <div
                                                                                    class="flex items-center justify-between">
                                                                                    <span
                                                                                        class="text-gray-400 font-normal">%
                                                                                        Row:</span>
                                                                                    <span class="font-bold text-gray-700"
                                                                                        x-text="Number((((chisquareData || matrixData).rowPercentages || {})[row] || {})[col] || 0).toFixed(1) + '%'"></span>
                                                                                </div>
                                                                                <div
                                                                                    class="flex items-center justify-between">
                                                                                    <span
                                                                                        class="text-gray-400 font-normal">%
                                                                                        Col:</span>
                                                                                    <span class="font-bold text-gray-700"
                                                                                        x-text="Number((((chisquareData || matrixData).colPercentages || {})[row] || {})[col] || 0).toFixed(1) + '%'"></span>
                                                                                </div>
                                                                                <div
                                                                                    class="flex items-center justify-between">
                                                                                    <span
                                                                                        class="text-gray-400 font-normal">%
                                                                                        Tot:</span>
                                                                                    <span class="font-bold text-gray-700"
                                                                                        x-text="Number((((chisquareData || matrixData).totalPercentages || {})[row] || {})[col] || 0).toFixed(1) + '%'"></span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                </template>
                                                                <td
                                                                    class="p-3 border-b border-l border-gray-200 bg-indigo-50/30 text-center text-sm font-black text-indigo-700 align-top">
                                                                    <div
                                                                        x-text="((chisquareData || matrixData).rowTotals || {})[row] || 0">
                                                                    </div>
                                                                    <div class="text-[10px] font-bold text-indigo-600 mt-1"
                                                                        x-text="(chisquareData || matrixData).grandTotal > 0 ? (((((chisquareData || matrixData).rowTotals || {})[row] || 0) / (chisquareData || matrixData).grandTotal * 100).toFixed(1) + '%') : '0.0%'">
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <th
                                                                class="p-4 border-t border-r border-gray-200 bg-indigo-50 text-[12px] font-medium text-indigo-800 tracking-widest">
                                                                {{ __('Total') }}
                                                            </th>
                                                            <template x-for="col in (chisquareData || matrixData).columns"
                                                                :key="col">
                                                                <th
                                                                    class="p-3 border-t border-gray-200 bg-indigo-50 text-center text-sm font-black text-indigo-800">
                                                                    <div
                                                                        x-text="((chisquareData || matrixData).colTotals || {})[col] || 0">
                                                                    </div>
                                                                    <div class="text-[10px] font-bold text-indigo-600 mt-0.5"
                                                                        x-text="Number(((chisquareData || matrixData).colTotalPercentages || {})[col] || 0).toFixed(1) + '%'">
                                                                    </div>
                                                                </th>
                                                            </template>
                                                            <th
                                                                class="p-4 border-t border-l border-indigo-200 bg-indigo-100 text-center text-sm font-black text-indigo-900">
                                                                <div x-text="(chisquareData || matrixData).grandTotal || 0">
                                                                </div>
                                                                <div class="text-[10px] font-medium text-indigo-700 mt-0.5">
                                                                    100.0%</div>
                                                            </th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Key Statistics Summary Grid -->
                                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                                        <div
                                            class="p-5 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 rounded-2xl border border-grey-100">
                                            <span
                                                class="text-[12px] font-medium text-grey-600 tracking-widest">{{ __('Chi-Square (χ²)') }}</span>
                                            <p class="text-2xl font-medium text-grey-900 mt-1"
                                                x-text="formatDec((chisquareData || matrixData).chiSquare, 3)"></p>
                                        </div>
                                        <div
                                            class="p-5 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 rounded-2xl border border-grey-100">
                                            <span
                                                class="text-[12px] font-medium text-grey-600 tracking-widest">{{ __('Degrees of Freedom (df)') }}</span>
                                            <p class="text-2xl font-medium text-grey-900 mt-1"
                                                x-text="(chisquareData || matrixData).df"></p>
                                        </div>
                                        <div class="p-5 rounded-2xl border"
                                            :class="(chisquareData || matrixData).significant ? 'bg-blue-50/50 border-grey-200 text-grey-900' : 'bg-indigo-50/50 border-grey-200 text-grey-900'">
                                            <span class="text-[12px] font-medium tracking-widest"
                                                :class="(chisquareData || matrixData).significant ? 'text-grey-700' : 'text-grey-600'">{{ __('p-Value (Sig.)') }}</span>
                                            <p class="text-2xl font-medium mt-1"
                                                x-text="formatP((chisquareData || matrixData).pValue)"></p>
                                            <span class="text-[10px] font-medium"
                                                x-text="(chisquareData || matrixData).significant ? 'Significant (p < 0.05)' : 'Not Significant (p >= 0.05)'"></span>
                                        </div>
                                        <div
                                            class="p-5 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 rounded-2xl border border-grey-100">
                                            <span
                                                class="text-[12px] font-medium text-grey-700 tracking-widest">{{ __("Cramer's V (Effect Size)") }}</span>
                                            <p class="text-2xl font-medium text-grey-950 mt-1"
                                                x-text="(chisquareData || matrixData).cramersV !== undefined ? formatDec((chisquareData || matrixData).cramersV, 3, true) : 'N/A'">
                                            </p>
                                            <span class="text-[10px] font-medium text-grey-800"
                                                x-text="((chisquareData || matrixData).effectLabel || 'Effect') + ' Association'"></span>
                                        </div>
                                    </div>

                                    <!-- Chi-Square Tests Table -->
                                    <div class="mt-6 border-t border-gray-100 pt-6 space-y-3">
                                        <h5 class="text-sm font-black text-gray-900">{{ __('Chi-Square Tests Table') }}</h5>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-left border-collapse min-w-[500px]">
                                                <thead>
                                                    <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                        <th class="p-3 border-b">{{ __('Test Statistic') }}</th>
                                                        <th class="p-3 border-b text-center">{{ __('Value') }}</th>
                                                        <th class="p-3 border-b text-center">{{ __('df') }}</th>
                                                        <th class="p-3 border-b text-center">
                                                            {{ __('Asymp. Sig. (2-sided)') }}
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr class="hover:bg-gray-50/50">
                                                        <td class="p-3 border-b text-xs font-semibold text-gray-800">
                                                            {{ __('Pearson Chi-Square') }}
                                                        </td>
                                                        <td class="p-3 border-b text-xs font-bold text-gray-900 text-center"
                                                            x-text="formatDec((chisquareData || matrixData).chiSquare, 3)">
                                                        </td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="(chisquareData || matrixData).df"></td>
                                                        <td class="p-3 border-b text-xs font-black text-center"
                                                            :class="(chisquareData || matrixData).significant ? 'text-emerald-600' : 'text-gray-500'"
                                                            x-text="formatP((chisquareData || matrixData).pValue)"></td>
                                                    </tr>
                                                    <template
                                                        x-if="(chisquareData || matrixData).isTwoByTwo && (chisquareData || matrixData).yatesChiSquare !== null">
                                                        <tr class="hover:bg-gray-50/50">
                                                            <td class="p-3 border-b text-xs font-semibold text-gray-800">
                                                                {{ __('Continuity Correction (Yates)') }}
                                                            </td>
                                                            <td class="p-3 border-b text-xs font-medium text-gray-700 text-center"
                                                                x-text="formatDec((chisquareData || matrixData).yatesChiSquare, 3)">
                                                            </td>
                                                            <td
                                                                class="p-3 border-b text-xs font-medium text-gray-600 text-center">
                                                                1</td>
                                                            <td class="p-3 border-b text-xs font-bold text-center"
                                                                :class="(chisquareData || matrixData).yatesPValue < 0.05 ? 'text-emerald-600' : 'text-gray-500'"
                                                                x-text="formatP((chisquareData || matrixData).yatesPValue)">
                                                            </td>
                                                        </tr>
                                                    </template>
                                                    <template x-if="(chisquareData || matrixData).likelihoodRatio">
                                                        <tr class="hover:bg-gray-50/50">
                                                            <td class="p-3 border-b text-xs font-semibold text-gray-800">
                                                                {{ __('Likelihood Ratio') }}
                                                            </td>
                                                            <td class="p-3 border-b text-xs font-medium text-gray-700 text-center"
                                                                x-text="formatDec((chisquareData || matrixData).likelihoodRatio, 3)">
                                                            </td>
                                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                x-text="(chisquareData || matrixData).df"></td>
                                                            <td class="p-3 border-b text-xs font-bold text-center"
                                                                :class="(chisquareData || matrixData).likelihoodSignificant ? 'text-emerald-600' : 'text-gray-500'"
                                                                x-text="formatP((chisquareData || matrixData).likelihoodPValue)">
                                                            </td>
                                                        </tr>
                                                    </template>
                                                    <template
                                                        x-if="(chisquareData || matrixData).isTwoByTwo && (chisquareData || matrixData).fisherExact2Sided !== null">
                                                        <tr class="hover:bg-gray-50/50">
                                                            <td class="p-3 border-b text-xs font-semibold text-gray-800">
                                                                {{ __("Fisher's Exact Test (2-sided / 1-sided)") }}
                                                            </td>
                                                            <td
                                                                class="p-3 border-b text-xs font-medium text-gray-500 text-center">
                                                                N/A</td>
                                                            <td
                                                                class="p-3 border-b text-xs font-medium text-gray-500 text-center">
                                                                -</td>
                                                            <td class="p-3 border-b text-xs font-bold text-center"
                                                                :class="(chisquareData || matrixData).fisherExact2Sided < 0.05 ? 'text-emerald-600' : 'text-gray-500'"
                                                                x-text="formatP((chisquareData || matrixData).fisherExact2Sided) + ' (1-sided: ' + formatP((chisquareData || matrixData).fisherExact1Sided) + ')'">
                                                            </td>
                                                        </tr>
                                                    </template>
                                                    <template x-if="(chisquareData || matrixData).linearAssociation">
                                                        <tr class="hover:bg-gray-50/50">
                                                            <td class="p-3 border-b text-xs font-semibold text-gray-800">
                                                                <span>{{ __('Linear-by-Linear Association') }}</span>
                                                                <span
                                                                    class="block text-[10px] text-gray-400 font-normal">{{ __('Requires ordinal scale data') }}</span>
                                                            </td>
                                                            <td class="p-3 border-b text-xs font-medium text-gray-700 text-center"
                                                                x-text="formatDec((chisquareData || matrixData).linearAssociation, 3)">
                                                            </td>
                                                            <td
                                                                class="p-3 border-b text-xs font-medium text-gray-600 text-center">
                                                                1</td>
                                                            <td class="p-3 border-b text-xs font-bold text-center"
                                                                x-text="formatP((chisquareData || matrixData).linearPValue)">
                                                            </td>
                                                        </tr>
                                                    </template>
                                                    <tr class="hover:bg-gray-50/50 bg-gray-50/30">
                                                        <td class="p-3 border-b text-xs font-bold text-gray-900">
                                                            {{ __('N of Valid Cases') }}
                                                        </td>
                                                        <td class="p-3 border-b text-xs font-bold text-gray-900 text-center"
                                                            x-text="(chisquareData || matrixData).grandTotal || (chisquareData || matrixData).validCases">
                                                        </td>
                                                        <td class="p-3 border-b text-xs text-center"></td>
                                                        <td class="p-3 border-b text-xs text-center"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <!-- Cochran Assumption Warning / Footnote for Chi-Square -->
                                            <template
                                                x-if="(chisquareData || matrixData) && (chisquareData || matrixData).assumptionWarning">
                                                <div class="mt-3 p-4 rounded-2xl border text-xs flex items-start gap-3 transition-all shadow-xs"
                                                    :class="(chisquareData || matrixData).assumptionWarning.is_violated ? 'bg-amber-50/80 border-amber-200 text-amber-900' : 'bg-gray-50/80 border-gray-200 text-gray-700'">
                                                    <i class="fa-solid mt-0.5 text-sm shrink-0"
                                                        :class="(chisquareData || matrixData).assumptionWarning.is_violated ? 'fa-triangle-exclamation text-amber-500' : 'fa-circle-check text-emerald-500'"></i>
                                                    <div class="space-y-1 w-full">
                                                        <div class="font-bold flex items-center justify-between">
                                                            <span
                                                                x-text="(chisquareData || matrixData).assumptionWarning.is_violated ? '{{ __('Cochran Chi-Square Assumption Check (Violated)') }}' : '{{ __('Chi-Square Assumption Check (Passed)') }}'"></span>
                                                            <span
                                                                class="px-2 py-0.5 rounded-full text-[10px] font-black tracking-wider uppercase"
                                                                :class="(chisquareData || matrixData).assumptionWarning.is_violated ? 'bg-amber-200 text-amber-900' : 'bg-emerald-100 text-emerald-800'"
                                                                x-text="(chisquareData || matrixData).assumptionWarning.is_violated ? '{{ __('Assumption Violated') }}' : '{{ __('Assumptions Met') }}'"></span>
                                                        </div>
                                                        <p class="text-[11px] leading-relaxed text-gray-700"
                                                            x-text="(chisquareData || matrixData).assumptionWarning.message">
                                                        </p>
                                                        <template
                                                            x-if="(chisquareData || matrixData).assumptionWarning.is_violated">
                                                            <p
                                                                class="text-[11px] font-medium text-amber-800 leading-relaxed pt-0.5">
                                                                {{ __('Methodological note: More than 20% of cells have expected counts below 5. The standard asymptotic chi-square p-value may be anti-conservative; consider collapsing sparse categories or referring to Fisher’s Exact Test.') }}
                                                            </p>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                            <template
                                                x-if="!(chisquareData || matrixData)?.assumptionWarning && (chisquareData || matrixData)?.footnote">
                                                <p class="text-xs text-gray-500 mt-1"
                                                    x-text="(chisquareData || matrixData).footnote"></p>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Symmetric Measures Table -->
                                    <template
                                        x-if="(chisquareData || matrixData).symmetricMeasures && (chisquareData || matrixData).symmetricMeasures.length > 0">
                                        <div class="mt-6 border-t border-gray-100 pt-6 space-y-3">
                                            <h5 class="text-sm font-black text-gray-900">
                                                {{ __('Symmetric Measures Table') }}
                                            </h5>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left border-collapse min-w-[500px]">
                                                    <thead>
                                                        <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                            <th class="p-3 border-b">{{ __('Category') }}</th>
                                                            <th class="p-3 border-b">{{ __('Measure') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('Value') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('Approx. Sig.') }}
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <template
                                                            x-for="meas in (chisquareData || matrixData).symmetricMeasures"
                                                            :key="meas.measure">
                                                            <tr class="hover:bg-gray-50/50">
                                                                <td class="p-3 border-b text-xs font-semibold text-gray-800"
                                                                    x-text="meas.category"></td>
                                                                <td class="p-3 border-b text-xs font-medium text-gray-700"
                                                                    x-text="meas.measure"></td>
                                                                <td class="p-3 border-b text-xs font-bold text-gray-900 text-center"
                                                                    x-text="formatDec(meas.value, 3, true)"></td>
                                                                <td class="p-3 border-b text-xs font-black text-center"
                                                                    :class="meas.approxSig < 0.05 ? 'text-emerald-600' : 'text-gray-500'"
                                                                    x-text="formatP(meas.approxSig)"></td>
                                                            </tr>
                                                        </template>
                                                        <tr class="hover:bg-gray-50/50 bg-gray-50/30">
                                                            <td class="p-3 border-b text-xs font-bold text-gray-900">
                                                                {{ __('N of Valid Cases') }}
                                                            </td>
                                                            <td class="p-3 border-b text-xs text-gray-500"></td>
                                                            <td class="p-3 border-b text-xs font-bold text-gray-900 text-center"
                                                                x-text="(chisquareData || matrixData).grandTotal || (chisquareData || matrixData).validCases">
                                                            </td>
                                                            <td class="p-3 border-b text-xs text-center"></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Academic Reporting & APA Summary Card -->
                                    <template
                                        x-if="(chisquareData || matrixData) && (chisquareData || matrixData).aiSummary">
                                        <div
                                            class="p-5 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 rounded-2xl border border-indigo-100 shadow-sm space-y-2">
                                            <div class="flex items-center gap-2 text-blue-950 font-bold text-xs">
                                                <i class="fa-solid fa-file-lines text-blue-700 text-xs"></i>
                                                <span>{{ __('Academic Reporting & APA Summary') }}</span>
                                            </div>
                                            <p class="text-xs text-gray-800 font-medium leading-relaxed"
                                                x-text="(chisquareData || matrixData).aiSummary"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- 3. Cronbach Alpha Reliability Test Results -->
                        <template x-if="testMethod === 'cronbach' && cronbachData">
                            <div class="space-y-8 animate-in fade-in duration-500">
                                <div
                                    class="bg-white rounded-3xl p-4 sm:p-6 md:p-8 border border-gray-100 shadow-sm space-y-6 overflow-hidden">
                                    <div class="border-b border-gray-100 pb-4 flex justify-between items-center">
                                        <div>
                                            <h4 class="text-lg font-black text-gray-900">
                                                {{ __("Reliability Analysis (Cronbach's Alpha α)") }}
                                            </h4>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ __('Internal consistency assessment across scale items') }}
                                            </p>
                                        </div>
                                        <span
                                            class="px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-bold border border-emerald-100">{{ __('Scale Reliability') }}</span>
                                    </div>

                                    <!-- SPSS Case Processing Summary -->
                                    <template x-if="cronbachData && cronbachData.case_summary">
                                        <div class="border border-gray-200 rounded-2xl overflow-hidden shadow-xs">
                                            <div
                                                class="bg-gray-50/90 px-4 py-2.5 border-b border-gray-200 flex items-center justify-between">
                                                <span
                                                    class="text-xs font-bold text-gray-700 uppercase tracking-wider">{{ __('Case Processing Summary') }}</span>
                                                <span class="text-[11px] text-gray-500 font-medium"
                                                    x-text="'Total Sample N = ' + cronbachData.case_summary.total_n"></span>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left text-xs text-gray-600">
                                                    <thead
                                                        class="bg-gray-50/50 text-[11px] font-bold text-gray-500 uppercase border-b border-gray-100">
                                                        <tr>
                                                            <th class="py-2.5 px-4">{{ __('Cases') }}</th>
                                                            <th class="py-2.5 px-4 text-center">{{ __('N') }}</th>
                                                            <th class="py-2.5 px-4 text-center">{{ __('%') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100">
                                                        <tr class="hover:bg-gray-50/50 font-semibold text-gray-800">
                                                            <td class="py-2.5 px-4 flex items-center gap-2">
                                                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                                <span>{{ __('Valid') }}</span>
                                                            </td>
                                                            <td class="py-2.5 px-4 text-center font-bold text-gray-900"
                                                                x-text="cronbachData.case_summary.valid_n"></td>
                                                            <td class="py-2.5 px-4 text-center text-emerald-700 font-bold"
                                                                x-text="cronbachData.case_summary.valid_pct + '%'"></td>
                                                        </tr>
                                                        <tr class="hover:bg-gray-50/50 text-gray-600">
                                                            <td class="py-2.5 px-4 flex items-center gap-2">
                                                                <span class="w-2 h-2 rounded-full bg-gray-300"></span>
                                                                <span>{{ __('Excluded (Listwise Missing)') }}</span>
                                                            </td>
                                                            <td class="py-2.5 px-4 text-center font-medium text-gray-600"
                                                                x-text="cronbachData.case_summary.excluded_n"></td>
                                                            <td class="py-2.5 px-4 text-center text-gray-500"
                                                                x-text="cronbachData.case_summary.excluded_pct + '%'"></td>
                                                        </tr>
                                                        <tr
                                                            class="bg-gray-50/80 font-bold text-gray-900 border-t border-gray-200">
                                                            <td class="py-2.5 px-4">{{ __('Total') }}</td>
                                                            <td class="py-2.5 px-4 text-center font-black"
                                                                x-text="cronbachData.case_summary.total_n"></td>
                                                            <td class="py-2.5 px-4 text-center font-black">100.0%</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div
                                                class="bg-gray-50/60 px-4 py-2 border-t border-gray-100 text-[10px] text-gray-500 italic">
                                                {{ __('a. Listwise deletion based on all scale items in the procedure.') }}
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Key Metric Cards -->
                                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                                        <div class="p-5 rounded-2xl border shadow-2xs"
                                            :class="cronbachData.alpha >= 0.7 ? 'bg-emerald-50/50 border-emerald-200' : 'bg-amber-50/50 border-amber-200'">
                                            <span class="text-[10px] font-black tracking-widest uppercase"
                                                :class="cronbachData.alpha >= 0.7 ? 'text-emerald-700' : 'text-amber-700'">{{ __("Cronbach's Alpha (α)") }}</span>
                                            <p class="text-3xl font-black mt-1 tracking-tight"
                                                :class="cronbachData.alpha >= 0.7 ? 'text-emerald-950' : 'text-amber-950'"
                                                x-text="formatDec(cronbachData.alpha, 4, true)"></p>
                                        </div>
                                        <div class="p-5 bg-blue-50/50 rounded-2xl border border-blue-100 shadow-2xs">
                                            <span
                                                class="text-[10px] font-black text-[#2271b1] tracking-widest uppercase">{{ __('Standardized Alpha') }}
                                                (α<sub>std</sub>)</span>
                                            <p class="text-3xl font-black text-blue-950 mt-1 tracking-tight"
                                                x-text="formatDec(cronbachData.std_alpha || cronbachData.alpha, 4, true)">
                                            </p>
                                        </div>
                                        <div class="p-5 bg-gray-50 rounded-2xl border border-gray-200 shadow-2xs">
                                            <span
                                                class="text-[10px] font-black text-gray-500 tracking-widest uppercase">{{ __('Items Evaluated (K)') }}</span>
                                            <p class="text-3xl font-black text-gray-900 mt-1" x-text="cronbachData.k_items">
                                            </p>
                                        </div>
                                        <div class="p-5 bg-blue-50/50 rounded-2xl border border-blue-100 shadow-2xs">
                                            <span
                                                class="text-[10px] font-black text-[#2271b1] tracking-widest uppercase">{{ __('Valid Cases (N)') }}</span>
                                            <p class="text-3xl font-black text-blue-950 mt-1" x-text="cronbachData.valid_n">
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="p-4 rounded-2xl border bg-gray-50 border-gray-200 flex items-center justify-between shadow-2xs">
                                        <span
                                            class="text-xs font-bold text-gray-700">{{ __('Scale Consistency Rating:') }}</span>
                                        <span
                                            class="text-xs font-black px-3 py-1 rounded-full text-[#2271b1] bg-blue-50 border border-blue-100 shadow-2xs"
                                            x-text="cronbachData.interpretation"></span>
                                    </div>

                                    <!-- Item Statistics Table -->
                                    <template
                                        x-if="cronbachData.item_descriptives && cronbachData.item_descriptives.length > 0">
                                        <div class="mt-6 border-t border-gray-100 pt-6 space-y-3">
                                            <div class="flex items-center justify-between">
                                                <h5 class="text-sm font-black text-gray-900">{{ __('Item Statistics') }}
                                                </h5>
                                                <span class="text-[11px] text-gray-500 font-medium">{{ __('N = ') }}<span
                                                        x-text="cronbachData.valid_n"></span></span>
                                            </div>
                                            <div class="overflow-x-auto border border-gray-200 rounded-2xl shadow-xs">
                                                <table class="w-full text-left border-collapse min-w-[500px]">
                                                    <thead>
                                                        <tr
                                                            class="bg-gray-50/90 font-bold text-gray-700 text-xs uppercase border-b border-gray-200">
                                                            <th class="p-3">{{ __('Scale Item') }}</th>
                                                            <th class="p-3 text-center">{{ __('Mean') }}</th>
                                                            <th class="p-3 text-center">{{ __('Std. Deviation') }}</th>
                                                            <th class="p-3 text-center">{{ __('N') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100 text-xs">
                                                        <template x-for="(item, idx) in cronbachData.item_descriptives"
                                                            :key="item.item_key">
                                                            <tr class="hover:bg-gray-50/50 transition-colors">
                                                                <td class="p-3 font-semibold text-gray-800">
                                                                    <div class="flex items-start gap-2">
                                                                        <span
                                                                            class="px-1.5 py-0.5 rounded bg-blue-50 text-[#2271b1] border border-blue-100 text-[10px] font-bold shrink-0 mt-0.5"
                                                                            x-text="item.short_label || ('Item ' + (idx + 1))"></span>
                                                                        <span class="leading-relaxed"
                                                                            x-text="item.label"></span>
                                                                    </div>
                                                                </td>
                                                                <td class="p-3 font-medium text-gray-700 text-center"
                                                                    x-text="formatDec(item.mean, 4)"></td>
                                                                <td class="p-3 font-medium text-gray-700 text-center"
                                                                    x-text="formatDec(item.stdDev, 4)"></td>
                                                                <td class="p-3 font-medium text-gray-600 text-center"
                                                                    x-text="item.n"></td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Inter-Item Correlation Matrix Table -->
                                    <template
                                        x-if="cronbachData.inter_item_matrix && Object.keys(cronbachData.inter_item_matrix).length > 0">
                                        <div class="mt-6 border-t border-gray-100 pt-6 space-y-3">
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                                <div>
                                                    <h5 class="text-sm font-black text-gray-900">
                                                        {{ __('Inter-Item Correlation Matrix') }}
                                                    </h5>
                                                    <p class="text-[11px] text-gray-500">
                                                        {{ __('Bivariate Pearson correlation coefficients (r) across scale items. Tap or hover any cell for details.') }}
                                                    </p>
                                                </div>
                                                <template x-if="cronbachData.mean_inter_item_corr !== undefined">
                                                    <span
                                                        class="text-xs font-bold px-2.5 py-1 rounded-xl bg-blue-50 text-[#2271b1] border border-blue-100 self-start sm:self-auto">
                                                        {{ __('Average r = ') }}<span
                                                            x-text="formatDec(cronbachData.mean_inter_item_corr, 3, true)"></span>
                                                    </span>
                                                </template>
                                            </div>

                                            <!-- Tap-to-Inspect Micro Popover Card (Touch/Mobile & Desktop) -->
                                            <template x-if="activeCorrInspect">
                                                <div
                                                    class="p-3.5 bg-slate-900 text-white rounded-2xl shadow-md border border-slate-800 flex items-center justify-between gap-3 animate-in fade-in duration-200">
                                                    <div class="space-y-1 text-xs">
                                                        <div class="flex items-center gap-2">
                                                            <span
                                                                class="px-2 py-0.5 rounded bg-slate-800 text-blue-200 text-[10px] font-bold"
                                                                x-text="activeCorrInspect.rowShort + ' ⟷ ' + activeCorrInspect.colShort"></span>
                                                            <span class="font-black text-amber-300 text-sm"
                                                                x-text="'r = ' + activeCorrInspect.r"></span>
                                                            <template x-if="activeCorrInspect.isDiag">
                                                                <span
                                                                    class="text-[10px] text-slate-400 italic">({{ __('Identity diagonal') }})</span>
                                                            </template>
                                                        </div>
                                                        <p class="text-[11px] text-slate-200 leading-snug">
                                                            <span class="font-semibold text-white"
                                                                x-text="activeCorrInspect.rowShort + ': ' + activeCorrInspect.rowLabel"></span>
                                                            <span class="mx-1 text-slate-400">×</span>
                                                            <span class="font-semibold text-white"
                                                                x-text="activeCorrInspect.colShort + ': ' + activeCorrInspect.colLabel"></span>
                                                        </p>
                                                    </div>
                                                    <button type="button" @click="clearCorrInspect()"
                                                        class="text-slate-400 hover:text-white p-1.5 rounded-lg hover:bg-slate-800 transition-colors shrink-0">
                                                        <i class="fa-solid fa-xmark text-sm"></i>
                                                    </button>
                                                </div>
                                            </template>

                                            <!-- Correlation Matrix Grid -->
                                            <div class="overflow-x-auto border border-gray-200 rounded-2xl shadow-xs">
                                                <table class="w-full text-left border-collapse min-w-[600px]">
                                                    <thead>
                                                        <tr
                                                            class="bg-gray-50/90 font-bold text-gray-700 text-xs border-b border-gray-200">
                                                            <th class="p-3 uppercase text-[11px]">{{ __('Item') }}</th>
                                                            <template x-for="(item, idx) in cronbachData.item_descriptives"
                                                                :key="'col_' + item.item_key">
                                                                <th class="p-2.5 text-center text-xs whitespace-nowrap cursor-pointer hover:bg-blue-50/60 transition-colors"
                                                                    :title="item.label"
                                                                    @click="inspectCorrelation(item, item, 1.0)">
                                                                    <span
                                                                        class="px-2 py-1 rounded-lg bg-white border border-gray-200 text-gray-800 font-bold shadow-2xs text-[11px]"
                                                                        x-text="item.short_label || ('Item ' + (idx + 1))"></span>
                                                                </th>
                                                            </template>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100 text-xs">
                                                        <template x-for="(row, rIdx) in cronbachData.item_descriptives"
                                                            :key="'row_' + row.item_key">
                                                            <tr class="hover:bg-gray-50/50 transition-colors">
                                                                <td class="p-3 font-semibold text-gray-800 whitespace-nowrap cursor-pointer"
                                                                    :title="row.label"
                                                                    @click="inspectCorrelation(row, row, 1.0)">
                                                                    <div class="flex items-center gap-2">
                                                                        <span
                                                                            class="px-2 py-0.5 rounded bg-blue-50 text-[#2271b1] border border-blue-100 text-[10px] font-bold"
                                                                            x-text="row.short_label || ('Item ' + (rIdx + 1))"></span>
                                                                        <span
                                                                            class="text-xs text-gray-600 hidden sm:inline max-w-[180px] truncate"
                                                                            x-text="row.label"></span>
                                                                    </div>
                                                                </td>
                                                                <template
                                                                    x-for="(col, cIdx) in cronbachData.item_descriptives"
                                                                    :key="'cell_' + row.item_key + '_' + col.item_key">
                                                                    <td class="p-2.5 text-center transition-colors cursor-pointer"
                                                                        :class="row.item_key === col.item_key ? 'font-black text-gray-400 bg-gray-50/60' : 'text-gray-800 hover:bg-blue-50 hover:text-[#2271b1] font-medium'"
                                                                        @click="inspectCorrelation(row, col, (cronbachData.inter_item_matrix[row.item_key] || {})[col.item_key])">
                                                                        <span
                                                                            x-text="row.item_key === col.item_key ? '1.000' : formatDec((cronbachData.inter_item_matrix[row.item_key] || {})[col.item_key], 3, true)"></span>
                                                                    </td>
                                                                </template>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <!-- Item Reference Key / Legend -->
                                            <div class="bg-gray-50/70 border border-gray-200/80 rounded-2xl p-4 space-y-2">
                                                <div class="flex items-center gap-2 pb-1 border-b border-gray-200/60">
                                                    <i class="fa-solid fa-list-ol text-[#2271b1] text-xs"></i>
                                                    <h6 class="text-xs font-bold text-gray-800 uppercase tracking-wider">
                                                        {{ __('Item Reference Key') }}
                                                    </h6>
                                                </div>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs pt-1">
                                                    <template x-for="(item, idx) in cronbachData.item_descriptives"
                                                        :key="'legend_' + item.item_key">
                                                        <div class="flex items-start gap-2 p-1.5 rounded-lg hover:bg-white transition-colors cursor-pointer"
                                                            @click="inspectCorrelation(item, item, 1.0)">
                                                            <span
                                                                class="px-2 py-0.5 rounded bg-white text-[#2271b1] border border-blue-100 font-bold text-[10px] shrink-0 mt-0.5 shadow-2xs"
                                                                x-text="item.short_label || ('Item ' + (idx + 1))"></span>
                                                            <p class="text-gray-700 leading-snug break-words"
                                                                x-text="item.label"></p>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Item-Total Statistics Table -->
                                    <template x-if="cronbachData.item_stats && cronbachData.item_stats.length > 0">
                                        <div class="mt-6 border-t border-gray-100 pt-6 space-y-3">
                                            <div class="flex items-center justify-between">
                                                <h5 class="text-sm font-black text-gray-900">
                                                    {{ __('Item-Total Statistics Table') }}
                                                </h5>
                                                <span
                                                    class="text-[11px] text-gray-500 italic">{{ __('SPSS Reliability Procedure') }}</span>
                                            </div>
                                            <div class="overflow-x-auto border border-gray-200 rounded-2xl shadow-xs">
                                                <table class="w-full text-left border-collapse min-w-[700px]">
                                                    <thead>
                                                        <tr
                                                            class="bg-gray-50/90 font-bold text-gray-700 text-xs border-b border-gray-200 uppercase">
                                                            <th class="p-3">{{ __('Scale Item') }}</th>
                                                            <th class="p-3 text-center">
                                                                {{ __('Scale Mean if Item Deleted') }}
                                                            </th>
                                                            <th class="p-3 text-center">
                                                                {{ __('Scale Variance if Item Deleted') }}
                                                            </th>
                                                            <th class="p-3 text-center">
                                                                {{ __('Corrected Item-Total Corr.') }}
                                                            </th>
                                                            <th class="p-3 text-center">
                                                                {{ __('Squared Multiple Corr.') }}
                                                            </th>
                                                            <th class="p-3 text-center">
                                                                {{ __("Cronbach's α if Item Deleted") }}
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100 text-xs">
                                                        <template x-for="(item, idx) in cronbachData.item_stats"
                                                            :key="item.item_key">
                                                            <tr class="hover:bg-gray-50/50 transition-colors">
                                                                <td class="p-3 font-semibold text-gray-800">
                                                                    <div class="flex items-start gap-2">
                                                                        <span
                                                                            class="px-1.5 py-0.5 rounded bg-blue-50 text-[#2271b1] border border-blue-100 text-[10px] font-bold shrink-0 mt-0.5"
                                                                            x-text="item.short_label || ('Item ' + (idx + 1))"></span>
                                                                        <span class="leading-relaxed"
                                                                            x-text="item.label"></span>
                                                                    </div>
                                                                </td>
                                                                <td class="p-3 font-medium text-gray-700 text-center"
                                                                    x-text="formatDec(item.scale_mean_if_deleted, 2)"></td>
                                                                <td class="p-3 font-medium text-gray-700 text-center"
                                                                    x-text="formatDec(item.scale_var_if_deleted, 2)"></td>
                                                                <td class="p-3 font-black text-center"
                                                                    :class="item.item_total_corr >= 0.3 ? 'text-[#2271b1]' : 'text-rose-500'"
                                                                    x-text="formatDec(item.item_total_corr, 3, true)"></td>
                                                                <td class="p-3 font-bold text-gray-700 text-center"
                                                                    x-text="formatDec(item.squared_multiple_corr || 0, 3, true)">
                                                                </td>
                                                                <td class="p-3 font-black text-center"
                                                                    :class="item.alpha_if_deleted > cronbachData.alpha ? 'text-amber-600 font-black' : 'text-gray-700'"
                                                                    x-text="formatDec(item.alpha_if_deleted, 4, true)"></td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Scale Statistics Table -->
                                    <template x-if="cronbachData.scale_statistics">
                                        <div class="mt-6 border-t border-gray-100 pt-6 space-y-3">
                                            <h5 class="text-sm font-black text-gray-900">{{ __('Scale Statistics') }}</h5>
                                            <div class="overflow-x-auto border border-gray-200 rounded-2xl shadow-xs">
                                                <table class="w-full text-left border-collapse min-w-[500px]">
                                                    <thead>
                                                        <tr
                                                            class="bg-gray-50/90 font-bold text-gray-700 text-xs border-b border-gray-200 uppercase">
                                                            <th class="p-3 text-center">{{ __('Mean') }}</th>
                                                            <th class="p-3 text-center">{{ __('Variance') }}</th>
                                                            <th class="p-3 text-center">{{ __('Std. Deviation') }}</th>
                                                            <th class="p-3 text-center">{{ __('N of Items') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100 text-xs">
                                                        <tr class="hover:bg-gray-50/50">
                                                            <td class="p-3 font-bold text-gray-900 text-center"
                                                                x-text="formatDec(cronbachData.scale_statistics.mean, 4)">
                                                            </td>
                                                            <td class="p-3 font-medium text-gray-700 text-center"
                                                                x-text="formatDec(cronbachData.scale_statistics.variance, 4)">
                                                            </td>
                                                            <td class="p-3 font-medium text-gray-700 text-center"
                                                                x-text="formatDec(cronbachData.scale_statistics.stdDev, 4)">
                                                            </td>
                                                            <td class="p-3 font-bold text-[#2271b1] text-center"
                                                                x-text="cronbachData.scale_statistics.k_items"></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- AI Scale Reliability Academic Synthesis Card -->
                                    <template x-if="cronbachData && cronbachData.aiSummary">
                                        <div
                                            class="p-5 bg-amber-50/70 rounded-2xl border border-amber-200/80 shadow-2xs space-y-2">
                                            <div class="flex items-center gap-2 text-amber-950 font-bold text-xs">
                                                <i class="fa-solid fa-clipboard-check text-amber-600"></i>
                                                <span>{{ __('Scale Reliability Academic Synthesis') }}</span>
                                            </div>
                                            <p class="text-xs text-gray-800 font-medium leading-relaxed"
                                                x-html="cronbachData.aiSummary"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- 2. T-Test Results -->
                        <template x-if="testMethod === 'ttest' && tTestData">
                            <div class="space-y-8 animate-in fade-in duration-500">
                                <div
                                    class="bg-white rounded-3xl p-4 sm:p-6 md:p-8 border border-gray-100 shadow-sm space-y-6 sm:space-y-8 overflow-hidden">
                                    <!-- Case Processing Summary (SPSS Standard) -->
                                    <template x-if="tTestData.case_summary">
                                        <div class="border border-gray-200/80 rounded-2xl overflow-hidden shadow-2xs">
                                            <div
                                                class="bg-gray-50/90 px-4 py-2.5 border-b border-gray-200 flex items-center justify-between">
                                                <span
                                                    class="text-xs font-bold text-gray-700 uppercase tracking-wider">{{ __('Case Processing Summary') }}</span>
                                                <span class="text-[11px] text-gray-500 font-medium"
                                                    x-text="'Total Cases Evaluated = ' + tTestData.case_summary.total_n"></span>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <template x-if="tTestData.case_summary.group1">
                                                    <table class="w-full text-left text-xs text-gray-600">
                                                        <thead
                                                            class="bg-gray-50/75 text-[11px] font-bold text-gray-700 uppercase border-b border-gray-200">
                                                            <tr>
                                                                <th class="py-2.5 px-4" rowspan="2"
                                                                    style="vertical-align: middle;">
                                                                    {{ __('Sample / Cohort') }}
                                                                </th>
                                                                <th class="py-2 px-4 text-center border-l border-gray-200"
                                                                    colspan="2">{{ __('Included / Valid') }}</th>
                                                                <th class="py-2 px-4 text-center border-l border-gray-200"
                                                                    colspan="2">{{ __('Excluded / Missing') }}</th>
                                                                <th class="py-2 px-4 text-center border-l border-gray-200"
                                                                    colspan="2">{{ __('Total Cases') }}</th>
                                                            </tr>
                                                            <tr
                                                                class="bg-gray-50/50 text-[10px] text-gray-500 border-t border-gray-100">
                                                                <th
                                                                    class="py-1.5 px-3 text-center border-l border-gray-200">
                                                                    {{ __('N') }}
                                                                </th>
                                                                <th class="py-1.5 px-3 text-center">{{ __('Percent') }}</th>
                                                                <th
                                                                    class="py-1.5 px-3 text-center border-l border-gray-200">
                                                                    {{ __('N') }}
                                                                </th>
                                                                <th class="py-1.5 px-3 text-center">{{ __('Percent') }}</th>
                                                                <th
                                                                    class="py-1.5 px-3 text-center border-l border-gray-200">
                                                                    {{ __('N') }}
                                                                </th>
                                                                <th class="py-1.5 px-3 text-center">{{ __('Percent') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-100">
                                                            <tr class="hover:bg-gray-50/50">
                                                                <td class="py-2.5 px-4 font-semibold text-gray-800"
                                                                    x-text="tTestData.case_summary.group1.name"></td>
                                                                <td class="py-2.5 px-3 text-center font-bold text-gray-900 border-l border-gray-100"
                                                                    x-text="tTestData.case_summary.group1.valid_n"></td>
                                                                <td class="py-2.5 px-3 text-center text-emerald-700 font-bold"
                                                                    x-text="tTestData.case_summary.group1.valid_pct + '%'">
                                                                </td>
                                                                <td class="py-2.5 px-3 text-center font-medium text-gray-600 border-l border-gray-100"
                                                                    x-text="tTestData.case_summary.group1.missing_n"></td>
                                                                <td class="py-2.5 px-3 text-center text-gray-500"
                                                                    x-text="tTestData.case_summary.group1.missing_pct + '%'">
                                                                </td>
                                                                <td class="py-2.5 px-3 text-center font-bold text-gray-900 border-l border-gray-100"
                                                                    x-text="tTestData.case_summary.group1.total_n"></td>
                                                                <td class="py-2.5 px-3 text-center text-gray-500">100.0%
                                                                </td>
                                                            </tr>
                                                            <tr class="hover:bg-gray-50/50">
                                                                <td class="py-2.5 px-4 font-semibold text-gray-800"
                                                                    x-text="tTestData.case_summary.group2.name"></td>
                                                                <td class="py-2.5 px-3 text-center font-bold text-gray-900 border-l border-gray-100"
                                                                    x-text="tTestData.case_summary.group2.valid_n"></td>
                                                                <td class="py-2.5 px-3 text-center text-emerald-700 font-bold"
                                                                    x-text="tTestData.case_summary.group2.valid_pct + '%'">
                                                                </td>
                                                                <td class="py-2.5 px-3 text-center font-medium text-gray-600 border-l border-gray-100"
                                                                    x-text="tTestData.case_summary.group2.missing_n"></td>
                                                                <td class="py-2.5 px-3 text-center text-gray-500"
                                                                    x-text="tTestData.case_summary.group2.missing_pct + '%'">
                                                                </td>
                                                                <td class="py-2.5 px-3 text-center font-bold text-gray-900 border-l border-gray-100"
                                                                    x-text="tTestData.case_summary.group2.total_n"></td>
                                                                <td class="py-2.5 px-3 text-center text-gray-500">100.0%
                                                                </td>
                                                            </tr>
                                                            <tr
                                                                class="bg-gray-50/80 font-bold text-gray-900 border-t-2 border-gray-200">
                                                                <td class="py-2.5 px-4">{{ __('Total') }}</td>
                                                                <td class="py-2.5 px-3 text-center font-black border-l border-gray-200"
                                                                    x-text="tTestData.case_summary.valid_n"></td>
                                                                <td class="py-2.5 px-3 text-center font-black text-emerald-700"
                                                                    x-text="tTestData.case_summary.valid_pct + '%'"></td>
                                                                <td class="py-2.5 px-3 text-center font-bold text-gray-700 border-l border-gray-200"
                                                                    x-text="tTestData.case_summary.missing_n"></td>
                                                                <td class="py-2.5 px-3 text-center font-bold text-gray-600"
                                                                    x-text="tTestData.case_summary.missing_pct + '%'"></td>
                                                                <td class="py-2.5 px-3 text-center font-black border-l border-gray-200"
                                                                    x-text="tTestData.case_summary.total_n"></td>
                                                                <td class="py-2.5 px-3 text-center font-black">100.0%</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </template>
                                                <template x-if="!tTestData.case_summary.group1">
                                                    <table class="w-full text-left text-xs text-gray-600">
                                                        <thead
                                                            class="bg-gray-50/50 text-[11px] font-bold text-gray-500 uppercase border-b border-gray-100">
                                                            <tr>
                                                                <th class="py-2.5 px-4">{{ __('Cases') }}</th>
                                                                <th class="py-2.5 px-4 text-center">{{ __('N') }}</th>
                                                                <th class="py-2.5 px-4 text-center">{{ __('%') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-100">
                                                            <tr class="hover:bg-gray-50/50 font-semibold text-gray-800">
                                                                <td class="py-2.5 px-4 flex items-center gap-2">
                                                                    <span
                                                                        class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                                    <span>{{ __('Valid (Included in Comparison)') }}</span>
                                                                </td>
                                                                <td class="py-2.5 px-4 text-center font-bold text-gray-900"
                                                                    x-text="tTestData.case_summary.valid_n"></td>
                                                                <td class="py-2.5 px-4 text-center text-emerald-700 font-bold"
                                                                    x-text="tTestData.case_summary.valid_pct + '%'"></td>
                                                            </tr>
                                                            <tr class="hover:bg-gray-50/50 text-gray-600">
                                                                <td class="py-2.5 px-4 flex items-center gap-2">
                                                                    <span class="w-2 h-2 rounded-full bg-gray-300"></span>
                                                                    <span>{{ __('Excluded (Missing / Other Groups)') }}</span>
                                                                </td>
                                                                <td class="py-2.5 px-4 text-center font-medium text-gray-600"
                                                                    x-text="tTestData.case_summary.missing_n"></td>
                                                                <td class="py-2.5 px-4 text-center text-gray-500"
                                                                    x-text="tTestData.case_summary.missing_pct + '%'"></td>
                                                            </tr>
                                                            <tr
                                                                class="bg-gray-50/80 font-bold text-gray-900 border-t border-gray-200">
                                                                <td class="py-2.5 px-4">{{ __('Total') }}</td>
                                                                <td class="py-2.5 px-4 text-center font-black"
                                                                    x-text="tTestData.case_summary.total_n"></td>
                                                                <td class="py-2.5 px-4 text-center font-black">100.0%</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </template>
                                            </div>
                                            <div
                                                class="bg-gray-50/60 px-4 py-2 border-t border-gray-100 text-[10px] text-gray-500 italic">
                                                {{ __('a. Listwise deletion based on dependent and grouping variables in the procedure.') }}
                                            </div>
                                        </div>
                                    </template>

                                    <div>
                                        <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('Group Statistics') }}</h5>
                                        <div class="overflow-x-auto mb-8">
                                            <table class="w-full text-left border-collapse">
                                                <thead>
                                                    <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                        <th class="p-3 border-b">{{ __('Grouping Variable') }}</th>
                                                        <th class="p-3 border-b text-center">{{ __('N') }}</th>
                                                        <th class="p-3 border-b text-center">{{ __('Mean') }}</th>
                                                        <th class="p-3 border-b text-center">{{ __('Std. Deviation') }}</th>
                                                        <th class="p-3 border-b text-center">{{ __('Std. Error Mean') }}
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="g in tTestData.groups" :key="g.name">
                                                        <tr>
                                                            <td class="p-3 border-b text-xs font-semibold text-gray-800"
                                                                x-text="g.name"></td>
                                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                x-text="g.n"></td>
                                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                x-text="g.mean"></td>
                                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                x-text="g.stdDev"></td>
                                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                x-text="g.stdError"></td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>

                                        <h5 class="text-sm font-black text-gray-900 mb-4">
                                            {{ __('Independent Samples Test') }}
                                        </h5>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-left border-collapse min-w-[800px]">
                                                <thead>
                                                    <tr
                                                        class="bg-gray-50 font-bold text-gray-700 text-[10px]  tracking-wider">
                                                        <th class="p-3 border-b" rowspan="2"></th>
                                                        <th class="p-3 border-b text-center border-r" colspan="2">
                                                            {{ __("Levene's Test for Equality of Variances") }}
                                                        </th>
                                                        <th class="p-3 border-b text-center" colspan="8">
                                                            {{ __('t-test for Equality of Means') }}
                                                        </th>
                                                    </tr>
                                                    <tr
                                                        class="bg-gray-50 font-bold text-gray-700 text-[10px]  tracking-wider">
                                                        <th class="p-3 border-b text-center">{{ __('F') }}</th>
                                                        <th class="p-3 border-b text-center border-r">{{ __('Sig.') }}</th>
                                                        <th class="p-3 border-b text-center">{{ __('t') }}</th>
                                                        <th class="p-3 border-b text-center">{{ __('df') }}</th>
                                                        <th class="p-3 border-b text-center">{{ __('Sig. (2-tailed)') }}
                                                        </th>
                                                        <th class="p-3 border-b text-center">{{ __('Sig. (1-tailed)') }}
                                                        </th>
                                                        <th class="p-3 border-b text-center">{{ __('Mean Difference') }}
                                                        </th>
                                                        <th class="p-3 border-b text-center">
                                                            {{ __('Std. Error Difference') }}
                                                        </th>
                                                        <th class="p-3 border-b text-center">
                                                            {{ __('95% Confidence Interval (Lower)') }}
                                                        </th>
                                                        <th class="p-3 border-b text-center">
                                                            {{ __('95% Confidence Interval (Upper)') }}
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                                        <td class="p-3 border-b text-xs font-semibold text-gray-800">
                                                            {{ __('Equal variances assumed') }}
                                                        </td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="tTestData.leveneF"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r"
                                                            x-text="formatP(tTestData.leveneSig)"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="tTestData.tValue"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="tTestData.df"></td>
                                                        <td class="p-3 border-b text-xs font-black text-center"
                                                            :class="tTestData.significant ? 'text-green-600' : 'text-gray-500'"
                                                            x-text="formatP(tTestData.pValue)"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="formatP(tTestData.pValue1Tailed)"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="tTestData.meanDiff"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="tTestData.stdErrorDiff"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="tTestData.ciLower"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="tTestData.ciUpper"></td>
                                                    </tr>
                                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                                        <td class="p-3 border-b text-xs font-semibold text-gray-800">
                                                            {{ __('Equal variances not assumed') }}
                                                        </td>
                                                        <td
                                                            class="p-3 border-b text-xs font-medium text-gray-600 text-center">
                                                        </td>
                                                        <td
                                                            class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r">
                                                        </td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="tTestData.tValueWelch || tTestData.tValue"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="tTestData.dfWelch || tTestData.df"></td>
                                                        <td class="p-3 border-b text-xs font-black text-center"
                                                            :class="(tTestData.significantWelch !== undefined ? tTestData.significantWelch : tTestData.significant) ? 'text-green-600' : 'text-gray-500'"
                                                            x-text="formatP(tTestData.pValueWelch || tTestData.pValue)">
                                                        </td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="formatP(tTestData.pValueWelch1Tailed || tTestData.pValue1Tailed)">
                                                        </td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="tTestData.meanDiff"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="tTestData.stdErrorDiffWelch || tTestData.stdErrorDiff">
                                                        </td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="tTestData.ciLowerWelch || tTestData.ciLower"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="tTestData.ciUpperWelch || tTestData.ciUpper"></td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                            <!-- Independent Samples Effect Sizes (SPSS v27+ Standard) -->
                                            <template x-if="tTestData.effect_sizes && tTestData.effect_sizes.length > 0">
                                                <div class="mt-8 border-t border-gray-100 pt-6">
                                                    <h5 class="text-sm font-black text-gray-900 mb-4">
                                                        {{ __('Independent Samples Effect Sizes') }}
                                                    </h5>
                                                    <div class="overflow-x-auto">
                                                        <table class="w-full text-left border-collapse min-w-[600px]">
                                                            <thead>
                                                                <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                                    <th class="p-3 border-b">{{ __('Effect Size Measure') }}
                                                                    </th>
                                                                    <th class="p-3 border-b text-center">
                                                                        {{ __('Standardizer a') }}
                                                                    </th>
                                                                    <th class="p-3 border-b text-center">
                                                                        {{ __('Point Estimate') }}
                                                                    </th>
                                                                    <th class="p-3 border-b text-center border-l"
                                                                        colspan="2">{{ __('95% Confidence Interval') }}</th>
                                                                </tr>
                                                                <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                                    <th class="p-3 border-b" colspan="3"></th>
                                                                    <th class="p-3 border-b text-center border-l">
                                                                        {{ __('Lower') }}
                                                                    </th>
                                                                    <th class="p-3 border-b text-center">{{ __('Upper') }}
                                                                    </th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <template x-for="es in tTestData.effect_sizes"
                                                                    :key="es.name">
                                                                    <tr class="hover:bg-gray-50/50">
                                                                        <td class="p-3 border-b text-xs font-semibold text-gray-800"
                                                                            x-text="es.name"></td>
                                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                            x-text="formatDec(es.standardizer, 4)"></td>
                                                                        <td class="p-3 border-b text-xs font-bold text-indigo-700 text-center"
                                                                            x-text="formatDec(es.point_estimate, 3)"></td>
                                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-l"
                                                                            x-text="formatDec(es.ci_lower, 3)"></td>
                                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                            x-text="formatDec(es.ci_upper, 3)"></td>
                                                                    </tr>
                                                                </template>
                                                            </tbody>
                                                        </table>
                                                        <div class="mt-2 text-[10px] text-gray-400 italic space-y-0.5">
                                                            <p>{{ __("a. The denominator used in estimating the effect sizes. Cohen's d and Hedges' correction use the pooled standard deviation. Glass's delta uses the sample standard deviation of the control/comparison group.") }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                            <!-- Footnotes and Effect Sizes (Cohen's d & Hedges' g) -->
                                            <div
                                                class="mt-4 pt-3 border-t border-gray-100 flex flex-wrap gap-4 items-center justify-between text-xs text-gray-600">
                                                <div class="flex items-center gap-4">
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="font-bold text-gray-700">{{ __("Cohen's d:") }}</span>
                                                        <span class="font-black text-indigo-600"
                                                            x-text="tTestData.cohensD"></span>
                                                        <span class="text-gray-500 font-medium"
                                                            x-text="'(' + (tTestData.cohensDEffect || '') + ')'"></span>
                                                    </div>
                                                    <template x-if="tTestData.hedgesG !== undefined">
                                                        <div
                                                            class="flex items-center gap-1.5 border-l border-gray-200 pl-4">
                                                            <span
                                                                class="font-bold text-gray-700">{{ __("Hedges' g:") }}</span>
                                                            <span class="font-black text-indigo-600"
                                                                x-text="tTestData.hedgesG"></span>
                                                            <span class="text-gray-500 font-medium"
                                                                x-text="'(' + (tTestData.hedgesGEffect || '') + ')'"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                                <div class="text-[11px] text-gray-500 italic">
                                                    <template x-if="tTestData.equalVarAssumed">
                                                        <span>{{ __("Levene's test p ≥ .05: Equal variances assumed (Pooled t-test interpretation valid).") }}</span>
                                                    </template>
                                                    <template x-if="!tTestData.equalVarAssumed">
                                                        <span>{{ __("Levene's test p < .05: Equal variances violated (Welch Satterthwaite t-test reported).") }}</span>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </template>

                        <!-- 3. Correlation Results -->
                        <template x-if="testMethod === 'correlation' && correlationData">
                            <div class="space-y-8 animate-in fade-in duration-500">
                                <div
                                    class="bg-white rounded-3xl p-4 sm:p-6 md:p-8 border border-gray-100 shadow-sm space-y-6 overflow-hidden">
                                    <!-- Descriptive Statistics Table -->
                                    <template
                                        x-if="correlationData.descriptives && correlationData.descriptives.length > 0">
                                        <div>
                                            <h5 class="text-sm font-black text-gray-900 mb-4">
                                                {{ __('Descriptive Statistics') }}
                                            </h5>
                                            <div class="overflow-x-auto mb-6">
                                                <table class="w-full text-left border-collapse">
                                                    <thead>
                                                        <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                            <th class="p-3 border-b">{{ __('Variable') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('Mean') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('Std. Deviation') }}
                                                            </th>
                                                            <th class="p-3 border-b text-center">{{ __('N') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <template x-for="d in correlationData.descriptives"
                                                            :key="d.variable">
                                                            <tr class="hover:bg-gray-50/50">
                                                                <td class="p-3 border-b text-xs font-semibold text-gray-800"
                                                                    x-text="d.short_label || d.label" :title="d.label"></td>
                                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                    x-text="d.mean"></td>
                                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                    x-text="d.stdDev"></td>
                                                                <td class="p-3 border-b text-xs font-bold text-gray-900 text-center"
                                                                    x-text="d.n"></td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </template>

                                    <h5 class="text-sm font-black text-gray-900 mb-4">
                                        {{ __('Correlations Matrix (Pearson & Spearman)') }}
                                    </h5>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left border-collapse min-w-[500px]">
                                            <thead>
                                                <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                    <th class="p-3 border-b"></th>
                                                    <th class="p-3 border-b"
                                                        x-text="correlationData.shortLabelX || correlationData.labelX"
                                                        :title="correlationData.labelX"></th>
                                                    <th class="p-3 border-b"
                                                        x-text="correlationData.shortLabelY || correlationData.labelY"
                                                        :title="correlationData.labelY"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="border-b hover:bg-gray-50/30 transition-colors">
                                                    <td class="p-3 text-xs font-bold text-gray-800"
                                                        x-text="correlationData.shortLabelX || correlationData.labelX"
                                                        :title="correlationData.labelX"></td>
                                                    <td class="p-3 text-xs text-gray-600 space-y-1">
                                                        <div>{{ __('Pearson r:') }} <span class="font-bold">1</span></div>
                                                        <div>{{ __('Sig. (2-tailed):') }} <span
                                                                class="text-gray-400">-</span></div>
                                                        <template x-if="correlationData.spearmanRho !== undefined">
                                                            <div>{{ __("Spearman's ρ:") }} <span class="font-bold">1</span>
                                                            </div>
                                                        </template>
                                                        <div class="text-[10px] text-gray-400 mt-1">N: <span
                                                                x-text="correlationData.n"></span></div>
                                                    </td>
                                                    <td class="p-3 text-xs text-gray-600 space-y-1">
                                                        <div>{{ __('Pearson r:') }} <span class="font-black text-indigo-600"
                                                                x-text="correlationData.r + (correlationData.sigMarker || '')"></span>
                                                        </div>
                                                        <div>{{ __('Sig. (2-tailed):') }} <span
                                                                class="font-bold text-indigo-700"
                                                                x-text="formatP(correlationData.pValue)"></span></div>
                                                        <template x-if="correlationData.spearmanRho !== undefined">
                                                            <div>
                                                                <span>{{ __("Spearman's ρ:") }}</span>
                                                                <span class="font-black text-purple-600"
                                                                    x-text="correlationData.spearmanRho + (correlationData.spearmanSigMarker || '')"></span>
                                                                <span class="text-[10px] text-gray-500"
                                                                    x-text="'(p = ' + formatP(correlationData.spearmanPValue) + ')'"></span>
                                                            </div>
                                                        </template>
                                                        <div class="text-[10px] text-gray-400 mt-1">N: <span
                                                                x-text="correlationData.n"></span></div>
                                                    </td>
                                                </tr>
                                                <tr class="border-b hover:bg-gray-50/30 transition-colors">
                                                    <td class="p-3 text-xs font-bold text-gray-800"
                                                        x-text="correlationData.shortLabelY || correlationData.labelY"
                                                        :title="correlationData.labelY"></td>
                                                    <td class="p-3 text-xs text-gray-600 space-y-1">
                                                        <div>{{ __('Pearson r:') }} <span class="font-black text-indigo-600"
                                                                x-text="correlationData.r + (correlationData.sigMarker || '')"></span>
                                                        </div>
                                                        <div>{{ __('Sig. (2-tailed):') }} <span
                                                                class="font-bold text-indigo-700"
                                                                x-text="formatP(correlationData.pValue)"></span></div>
                                                        <template x-if="correlationData.spearmanRho !== undefined">
                                                            <div>
                                                                <span>{{ __("Spearman's ρ:") }}</span>
                                                                <span class="font-black text-purple-600"
                                                                    x-text="correlationData.spearmanRho + (correlationData.spearmanSigMarker || '')"></span>
                                                                <span class="text-[10px] text-gray-500"
                                                                    x-text="'(p = ' + formatP(correlationData.spearmanPValue) + ')'"></span>
                                                            </div>
                                                        </template>
                                                        <div class="text-[10px] text-gray-400 mt-1">N: <span
                                                                x-text="correlationData.n"></span></div>
                                                    </td>
                                                    <td class="p-3 text-xs text-gray-600 space-y-1">
                                                        <div>{{ __('Pearson r:') }} <span class="font-bold">1</span></div>
                                                        <div>{{ __('Sig. (2-tailed):') }} <span
                                                                class="text-gray-400">-</span></div>
                                                        <template x-if="correlationData.spearmanRho !== undefined">
                                                            <div>{{ __("Spearman's ρ:") }} <span class="font-bold">1</span>
                                                            </div>
                                                        </template>
                                                        <div class="text-[10px] text-gray-400 mt-1">N: <span
                                                                x-text="correlationData.n"></span></div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <!-- Footnotes -->
                                        <div class="mt-4 space-y-1 text-[10px] text-gray-500 italic">
                                            <template
                                                x-if="correlationData.sigMarker === '**' || correlationData.spearmanSigMarker === '**'">
                                                <div>
                                                    {{ __('**. Correlation is significant at the 0.01 level (2-tailed).') }}
                                                </div>
                                            </template>
                                            <template
                                                x-if="correlationData.sigMarker === '*' || correlationData.spearmanSigMarker === '*'">
                                                <div>{{ __('*. Correlation is significant at the 0.05 level (2-tailed).') }}
                                                </div>
                                            </template>
                                            <div class="text-gray-400 mt-2 font-medium">
                                                {{ __('Standard Error of r:') }} <span class="font-bold"
                                                    x-text="correlationData.stdErrorR"></span> |
                                                {{ __('Covariance:') }} <span class="font-bold"
                                                    x-text="correlationData.covariance"></span> |
                                                {{ __('95% Confidence Interval for r:') }} [<span
                                                    x-text="correlationData.ciLower"></span>, <span
                                                    x-text="correlationData.ciUpper"></span>]
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Interactive Scatter Plot with Trendline -->
                                    <template
                                        x-if="correlationData.scatterPoints && correlationData.scatterPoints.length > 0">
                                        <div class="mt-6 border-t border-gray-100 pt-6">
                                            <div class="flex items-center justify-between mb-4">
                                                <h5 class="text-sm font-black text-gray-900">
                                                    {{ __('Scatter Plot & Regression Trendline') }}
                                                </h5>
                                                <span
                                                    class="text-[10px] font-semibold px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-md"
                                                    x-text="'R² = ' + correlationData.r2"></span>
                                            </div>
                                            <div class="relative w-full h-80 sm:h-96 bg-gray-50/50 rounded-2xl p-4 border border-gray-100"
                                                x-init="$nextTick(() => renderCorrelationScatter($el, correlationData))">
                                                <canvas class="w-full h-full"></canvas>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- 4. ANOVA Results -->
                        <template x-if="testMethod === 'anova' && anovaData">
                            <div class="space-y-8 animate-in fade-in duration-500">
                                <div
                                    class="bg-white rounded-3xl p-4 sm:p-6 md:p-8 border border-gray-100 shadow-sm overflow-hidden space-y-6 sm:space-y-8">
                                    <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('ANOVA Descriptives') }}</h5>
                                    <div class="overflow-x-auto mb-8">
                                        <table class="w-full text-left border-collapse">
                                            <thead>
                                                <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                    <th class="p-3 border-b" rowspan="2">{{ __('Group') }}</th>
                                                    <th class="p-3 border-b text-center" rowspan="2">{{ __('N') }}</th>
                                                    <th class="p-3 border-b text-center" rowspan="2">{{ __('Mean') }}</th>
                                                    <th class="p-3 border-b text-center" rowspan="2">
                                                        {{ __('Std. Deviation') }}
                                                    </th>
                                                    <th class="p-3 border-b text-center" rowspan="2">{{ __('Std. Error') }}
                                                    </th>
                                                    <th class="p-3 border-b text-center border-l" colspan="2">
                                                        {{ __('95% Confidence Interval for Mean') }}
                                                    </th>
                                                    <th class="p-3 border-b text-center border-l" rowspan="2">
                                                        {{ __('Minimum') }}
                                                    </th>
                                                    <th class="p-3 border-b text-center" rowspan="2">{{ __('Maximum') }}
                                                    </th>
                                                </tr>
                                                <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                    <th class="p-3 border-b text-center border-l">{{ __('Lower Bound') }}
                                                    </th>
                                                    <th class="p-3 border-b text-center">{{ __('Upper Bound') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="g in anovaData.groupStats" :key="g.name">
                                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                                        <td class="p-3 border-b text-xs font-semibold text-gray-800"
                                                            x-text="g.name"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="g.n"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="formatDec(g.mean, 4)"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="formatDec(g.stdDev, 4)"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="formatDec(g.stdError, 4)"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-l"
                                                            x-text="formatDec(g.ciLower, 4)"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="formatDec(g.ciUpper, 4)"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-l"
                                                            x-text="formatDec(g.min, 4)"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="formatDec(g.max, 4)"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Test of Homogeneity of Variances (Levene's Test) -->
                                    <template x-if="anovaData.levene">
                                        <div class="mb-8">
                                            <h5 class="text-sm font-black text-gray-900 mb-4">
                                                {{ __("Test of Homogeneity of Variances (Levene's)") }}
                                            </h5>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left border-collapse min-w-[500px]">
                                                    <thead>
                                                        <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                            <th class="p-3 border-b">{{ __('Levene Statistic') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('df1') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('df2') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('Sig.') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr class="hover:bg-gray-50/50">
                                                            <td class="p-3 border-b text-xs font-bold text-gray-900"
                                                                x-text="anovaData.levene.statistic"></td>
                                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                x-text="anovaData.levene.df1"></td>
                                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                x-text="anovaData.levene.df2"></td>
                                                            <td class="p-3 border-b text-xs font-black text-center"
                                                                :class="anovaData.levene.sig < 0.05 ? 'text-amber-600' : 'text-emerald-600'"
                                                                x-text="formatP(anovaData.levene.sig)"></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <p class="text-[11px] text-gray-500 italic mt-2">
                                                    <span
                                                        x-show="anovaData.levene.equalVarAssumed">{{ __("p ≥ .05: Equal variances assumed across comparison groups.") }}</span>
                                                    <span
                                                        x-show="!anovaData.levene.equalVarAssumed">{{ __("p < .05: Variance homogeneity assumption is violated.") }}</span>
                                                </p>
                                            </div>
                                        </div>
                                    </template>

                                    <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('ANOVA Table') }}</h5>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left border-collapse">
                                            <thead>
                                                <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                    <th class="p-3 border-b"></th>
                                                    <th class="p-3 border-b text-center">{{ __('Sum of Squares') }}</th>
                                                    <th class="p-3 border-b text-center">{{ __('df') }}</th>
                                                    <th class="p-3 border-b text-center">{{ __('Mean Square') }}</th>
                                                    <th class="p-3 border-b text-center">{{ __('F') }}</th>
                                                    <th class="p-3 border-b text-center">{{ __('Sig.') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="p-3 border-b text-xs font-semibold text-gray-800">
                                                        {{ __('Between Groups') }}
                                                    </td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="anovaData.ssb"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="anovaData.dfBetween"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="anovaData.msb"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="anovaData.fValue" rowspan="2"
                                                        style="vertical-align: middle;"></td>
                                                    <td class="p-3 border-b text-xs font-black text-center"
                                                        :class="anovaData.significant ? 'text-green-600' : 'text-gray-500'"
                                                        x-text="formatP(anovaData.pValue)" rowspan="2"
                                                        style="vertical-align: middle;"></td>
                                                </tr>
                                                <tr>
                                                    <td class="p-3 border-b text-xs font-semibold text-gray-800">
                                                        {{ __('Within Groups') }}
                                                    </td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="anovaData.ssw"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="anovaData.dfWithin"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="anovaData.msw"></td>
                                                </tr>
                                                <tr class="bg-indigo-50/10">
                                                    <td class="p-3 border-b text-xs font-bold text-indigo-900">
                                                        {{ __('Total') }}
                                                    </td>
                                                    <td class="p-3 border-b text-xs font-bold text-indigo-900 text-center"
                                                        x-text="anovaData.sst"></td>
                                                    <td class="p-3 border-b text-xs font-bold text-indigo-900 text-center"
                                                        x-text="anovaData.dfTotal"></td>
                                                    <td class="p-3 border-b" colspan="3"></td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <!-- ANOVA Effect Size (Eta-Squared) -->
                                        <div
                                            class="mt-4 pt-3 border-t border-gray-100 flex flex-wrap gap-4 items-center justify-between text-xs text-gray-600">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="font-bold text-gray-700">{{ __("Eta-Squared (η² Effect Size):") }}</span>
                                                <span class="font-black text-indigo-600"
                                                    x-text="anovaData.etaSquared"></span>
                                                <span class="text-gray-500 font-medium"
                                                    x-text="'(' + (anovaData.etaEffect || '') + ' {{ __('effect') }}' + ')'"></span>
                                            </div>
                                            <div class="text-[11px] text-gray-500 italic">
                                                <span>{{ __("η² indicates the proportion of total variance explained by the grouping variable.") }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Robust Tests of Equality of Means (Welch & Brown-Forsythe ANOVA) -->
                                    <template x-if="anovaData.robust_tests">
                                        <div class="mt-8 border-t border-gray-100 pt-6">
                                            <div class="flex items-center justify-between mb-4">
                                                <h5 class="text-sm font-black text-gray-900">
                                                    {{ __('Robust Tests of Equality of Means') }}
                                                </h5>
                                                <span x-show="anovaData.levene && !anovaData.levene.equalVarAssumed"
                                                    class="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200/80 px-2.5 py-1 rounded-lg">
                                                    {{ __('Recommended Test (Equal Variances Violated)') }}
                                                </span>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left border-collapse min-w-[500px]">
                                                    <thead>
                                                        <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                            <th class="p-3 border-b">{{ __('Test') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('Statistic a') }}
                                                            </th>
                                                            <th class="p-3 border-b text-center">{{ __('df1') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('df2') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('Sig.') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr class="hover:bg-gray-50/50"
                                                            :class="anovaData.levene && !anovaData.levene.equalVarAssumed ? 'bg-amber-50/30 font-semibold' : ''">
                                                            <td class="p-3 border-b text-xs font-semibold text-gray-800">
                                                                {{ __('Welch') }}
                                                            </td>
                                                            <td class="p-3 border-b text-xs font-bold text-indigo-700 text-center"
                                                                x-text="formatDec(anovaData.robust_tests.welch.statistic, 3)">
                                                            </td>
                                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                x-text="anovaData.robust_tests.welch.df1"></td>
                                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                x-text="formatDec(anovaData.robust_tests.welch.df2, 3)">
                                                            </td>
                                                            <td class="p-3 border-b text-xs font-black text-center"
                                                                :class="anovaData.robust_tests.welch.sig < 0.05 ? 'text-emerald-600' : 'text-gray-500'"
                                                                x-text="formatP(anovaData.robust_tests.welch.sig)"></td>
                                                        </tr>
                                                        <tr class="hover:bg-gray-50/50">
                                                            <td class="p-3 border-b text-xs font-semibold text-gray-800">
                                                                {{ __('Brown-Forsythe') }}
                                                            </td>
                                                            <td class="p-3 border-b text-xs font-bold text-indigo-700 text-center"
                                                                x-text="formatDec(anovaData.robust_tests.brown_forsythe.statistic, 3)">
                                                            </td>
                                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                x-text="anovaData.robust_tests.brown_forsythe.df1"></td>
                                                            <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                x-text="formatDec(anovaData.robust_tests.brown_forsythe.df2, 3)">
                                                            </td>
                                                            <td class="p-3 border-b text-xs font-black text-center"
                                                                :class="anovaData.robust_tests.brown_forsythe.sig < 0.05 ? 'text-emerald-600' : 'text-gray-500'"
                                                                x-text="formatP(anovaData.robust_tests.brown_forsythe.sig)">
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <p class="text-[10px] text-gray-400 italic mt-2">
                                                    {{ __('a. Asymptotically F distributed.') }}
                                                </p>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Means Plot (Interactive Chart with 95% CI Error Bars) -->
                                    <div class="mt-8 border-t border-gray-100 pt-6">
                                        <div class="flex items-center justify-between mb-4">
                                            <h5 class="text-sm font-black text-gray-900">
                                                {{ __('Means Plot (with 95% Confidence Intervals)') }}
                                            </h5>
                                            <span
                                                class="text-[11px] text-gray-500 font-medium">{{ __('Error bars represent 95% CI for the mean') }}</span>
                                        </div>
                                        <div id="anova-means-chart-container"
                                            class="h-64 sm:h-72 w-full bg-gray-50/50 p-4 rounded-2xl border border-gray-100">
                                            <canvas></canvas>
                                        </div>
                                    </div>

                                    <!-- Post-Hoc Multiple Comparisons Table (Tukey HSD) -->
                                    <template x-if="anovaData.postHoc && anovaData.postHoc.length > 0">
                                        <div class="mt-8 border-t border-gray-100 pt-6">
                                            <h5 class="text-sm font-black text-gray-900 mb-3">
                                                {{ __('Post-Hoc Multiple Comparisons (Tukey HSD / Bonferroni)') }}
                                            </h5>
                                            <!-- Non-significant Omnibus F Note (SPSS convention) -->
                                            <template x-if="anovaData && !anovaData.significant">
                                                <div
                                                    class="mb-4 p-3.5 rounded-xl bg-amber-50 border border-amber-200/80 flex items-start gap-3">
                                                    <i
                                                        class="fa-solid fa-circle-info text-amber-600 mt-0.5 text-sm shrink-0"></i>
                                                    <div class="text-xs text-amber-900 leading-relaxed">
                                                        <span class="font-bold">{{ __('Note on Post-Hoc Tests:') }}</span>
                                                        {{ __('The Omnibus F-test is non-significant (p ≥ .05), indicating no overall statistically significant difference between group means. In accordance with standard statistical conventions (SPSS / APA), post-hoc pairwise comparisons are provided for exploratory reference only and should not be interpreted as confirmatory significant differences.') }}
                                                    </div>
                                                </div>
                                            </template>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left border-collapse min-w-[700px]">
                                                    <thead>
                                                        <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                            <th class="p-3 border-b">{{ __('(I) Group') }}</th>
                                                            <th class="p-3 border-b">{{ __('(J) Group') }}</th>
                                                            <th class="p-3 border-b text-center">
                                                                {{ __('Mean Difference (I-J)') }}
                                                            </th>
                                                            <th class="p-3 border-b text-center">{{ __('Std. Error') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('Sig. (Tukey)') }}
                                                            </th>
                                                            <th class="p-3 border-b text-center">{{ __('95% CI Lower') }}
                                                            </th>
                                                            <th class="p-3 border-b text-center">{{ __('95% CI Upper') }}
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <template x-for="ph in anovaData.postHoc"
                                                            :key="ph.groupI + '_' + ph.groupJ">
                                                            <tr class="hover:bg-gray-50/50"
                                                                :class="ph.significant ? 'bg-emerald-50/20' : ''">
                                                                <td class="p-3 border-b text-xs font-semibold text-gray-800"
                                                                    x-text="ph.groupI"></td>
                                                                <td class="p-3 border-b text-xs font-medium text-gray-700"
                                                                    x-text="ph.groupJ"></td>
                                                                <td class="p-3 border-b text-xs font-bold text-center"
                                                                    :class="ph.significant ? 'text-indigo-700' : 'text-gray-800'"
                                                                    x-text="formatDec(ph.meanDiff, 4) + (ph.significant ? ' *' : '')">
                                                                </td>
                                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                    x-text="formatDec(ph.stdError, 4)"></td>
                                                                <td class="p-3 border-b text-xs font-black text-center"
                                                                    :class="ph.significant ? 'text-emerald-600' : 'text-gray-500'"
                                                                    x-text="formatP(ph.sig)"></td>
                                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                    x-text="formatDec(ph.ciLower, 4)"></td>
                                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                    x-text="formatDec(ph.ciUpper, 4)"></td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                                <p class="text-[10px] text-gray-400 italic mt-2">
                                                    {{ __('*. The mean difference is significant at the 0.05 level.') }}
                                                </p>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Games-Howell Post-Hoc Multiple Comparisons (Unequal Variances) -->
                                    <template
                                        x-if="anovaData.postHocGamesHowell && anovaData.postHocGamesHowell.length > 0">
                                        <div class="mt-8 border-t border-gray-100 pt-6">
                                            <div class="flex items-center justify-between mb-3">
                                                <h5 class="text-sm font-black text-gray-900">
                                                    {{ __('Post-Hoc Multiple Comparisons (Games-Howell)') }}
                                                </h5>
                                                <span
                                                    class="text-[10px] font-semibold px-2 py-0.5 bg-amber-50 text-amber-800 rounded-md border border-amber-200">
                                                    {{ __('Unequal Variances Assumed') }}
                                                </span>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left border-collapse min-w-[750px]">
                                                    <thead>
                                                        <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                            <th class="p-3 border-b">{{ __('(I) Group') }}</th>
                                                            <th class="p-3 border-b">{{ __('(J) Group') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('Mean Diff (I-J)') }}
                                                            </th>
                                                            <th class="p-3 border-b text-center">{{ __('Std. Error') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('df') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('Sig.') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('95% CI Lower') }}
                                                            </th>
                                                            <th class="p-3 border-b text-center">{{ __('95% CI Upper') }}
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <template x-for="gh in anovaData.postHocGamesHowell"
                                                            :key="'gh_' + gh.groupI + '_' + gh.groupJ">
                                                            <tr class="hover:bg-gray-50/50"
                                                                :class="gh.significant ? 'bg-amber-50/20' : ''">
                                                                <td class="p-3 border-b text-xs font-semibold text-gray-800"
                                                                    x-text="gh.groupI"></td>
                                                                <td class="p-3 border-b text-xs font-medium text-gray-700"
                                                                    x-text="gh.groupJ"></td>
                                                                <td class="p-3 border-b text-xs font-bold text-center"
                                                                    :class="gh.significant ? 'text-amber-700' : 'text-gray-800'"
                                                                    x-text="formatDec(gh.meanDiff, 4) + (gh.significant ? ' *' : '')">
                                                                </td>
                                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                    x-text="formatDec(gh.stdError, 4)"></td>
                                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                    x-text="formatDec(gh.df, 2)"></td>
                                                                <td class="p-3 border-b text-xs font-black text-center"
                                                                    :class="gh.significant ? 'text-amber-700' : 'text-gray-500'"
                                                                    x-text="formatP(gh.sig)"></td>
                                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                    x-text="formatDec(gh.ciLower, 4)"></td>
                                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                    x-text="formatDec(gh.ciUpper, 4)"></td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                                <p class="text-[10px] text-gray-400 italic mt-2">
                                                    {{ __('*. The mean difference is significant at the 0.05 level. Welch-Satterthwaite df used.') }}
                                                </p>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Homogeneous Subsets (Tukey HSD Harmonic Mean) Table -->
                                    <template
                                        x-if="anovaData.homogeneousSubsets && anovaData.homogeneousSubsets.rows && anovaData.homogeneousSubsets.rows.length > 0">
                                        <div class="mt-8 border-t border-gray-100 pt-6">
                                            <h5 class="text-sm font-black text-gray-900 mb-3">
                                                {{ __('Homogeneous Subsets (Tukey HSD)') }}
                                            </h5>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left border-collapse min-w-[500px]">
                                                    <thead>
                                                        <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                            <th class="p-3 border-b" rowspan="2">{{ __('Group') }}</th>
                                                            <th class="p-3 border-b text-center" rowspan="2">{{ __('N') }}
                                                            </th>
                                                            <th class="p-3 border-b text-center border-l"
                                                                :colspan="anovaData.homogeneousSubsets.subsetCount">
                                                                {{ __('Subset for alpha = 0.05') }}
                                                            </th>
                                                        </tr>
                                                        <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                            <template
                                                                x-for="sIdx in anovaData.homogeneousSubsets.subsetCount"
                                                                :key="'subset_th_' + sIdx">
                                                                <th class="p-3 border-b text-center border-l" x-text="sIdx">
                                                                </th>
                                                            </template>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <template x-for="row in anovaData.homogeneousSubsets.rows"
                                                            :key="'hs_' + row.group">
                                                            <tr class="hover:bg-gray-50/50">
                                                                <td class="p-3 border-b text-xs font-semibold text-gray-800"
                                                                    x-text="row.group"></td>
                                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                    x-text="row.n"></td>
                                                                <template
                                                                    x-for="sIdx in anovaData.homogeneousSubsets.subsetCount"
                                                                    :key="'hs_val_' + row.group + '_' + sIdx">
                                                                    <td class="p-3 border-b text-xs font-bold text-center border-l"
                                                                        :class="row.subsets[sIdx - 1] !== null ? 'text-indigo-700 bg-indigo-50/30' : 'text-gray-300'"
                                                                        x-text="row.subsets[sIdx - 1] !== null ? formatDec(row.subsets[sIdx - 1], 4) : ''">
                                                                    </td>
                                                                </template>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                                <p class="text-[10px] text-gray-500 italic mt-2">
                                                    {{ __('Means for groups in homogeneous subsets are displayed. Harmonic mean sample size = ') }}
                                                    <span class="font-semibold"
                                                        x-text="formatDec(anovaData.homogeneousSubsets.harmonicMeanN, 3)"></span>.
                                                    <span x-show="anovaData.homogeneousSubsets.isUnequalN"
                                                        class="text-amber-600">
                                                        {{ __('Type I error levels are not guaranteed for unequal sample sizes.') }}
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- 5. Regression Results -->
                        <template x-if="testMethod === 'regression' && regressionData">
                            <div class="space-y-8 animate-in fade-in duration-500">
                                <div
                                    class="bg-white rounded-3xl p-4 sm:p-6 md:p-8 border border-gray-100 shadow-sm overflow-hidden space-y-6 sm:space-y-8">
                                    <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('Variables Entered/Removed') }}
                                    </h5>
                                    <div class="overflow-x-auto mb-8">
                                        <table class="w-full text-left border-collapse">
                                            <thead>
                                                <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                    <th class="p-3 border-b">{{ __('Model') }}</th>
                                                    <th class="p-3 border-b">{{ __('Variables Entered') }}</th>
                                                    <th class="p-3 border-b">{{ __('Variables Removed') }}</th>
                                                    <th class="p-3 border-b">{{ __('Method') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="hover:bg-gray-50/50 transition-colors">
                                                    <td class="p-3 border-b text-xs font-semibold text-gray-800">1</td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-700"
                                                        x-text="(regressionData.shortIndLabel || regressionData.indLabel) + ' ᵇ'"
                                                        :title="regressionData.indLabel"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600">.</td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600">
                                                        {{ __('Enter') }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <div class="mt-2 text-[10px] text-gray-500 italic space-y-0.5">
                                            <p
                                                x-text="'a. Dependent Variable: ' + (regressionData.shortDepLabel || regressionData.depLabel)">
                                            </p>
                                            <p>{{ __('b. All requested variables entered.') }}</p>
                                        </div>
                                    </div>

                                    <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('Model Summary') }}</h5>
                                    <div class="overflow-x-auto mb-8">
                                        <table class="w-full text-left border-collapse">
                                            <thead>
                                                <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                    <th class="p-3 border-b text-center">{{ __('Model') }}</th>
                                                    <th class="p-3 border-b text-center">{{ __('R') }}</th>
                                                    <th class="p-3 border-b text-center">{{ __('R Square') }}</th>
                                                    <th class="p-3 border-b text-center">{{ __('Adjusted R Square') }}</th>
                                                    <th class="p-3 border-b text-center">
                                                        {{ __('Std. Error of the Estimate') }}
                                                    </th>
                                                    <th class="p-3 border-b text-center">{{ __('Durbin-Watson') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td
                                                        class="p-3 border-b text-xs font-semibold text-gray-800 text-center">
                                                        1</td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="regressionData.r"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="regressionData.r2"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="regressionData.adjR2"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="regressionData.stdErrorEst"></td>
                                                    <td class="p-3 border-b text-xs font-bold text-gray-900 text-center"
                                                        x-text="regressionData.durbinWatson"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <h5 class="text-sm font-black text-gray-900 mb-4">
                                        {{ __('ANOVA (Regression significance)') }}
                                    </h5>
                                    <div class="overflow-x-auto mb-8">
                                        <table class="w-full text-left border-collapse">
                                            <thead>
                                                <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                    <th class="p-3 border-b"></th>
                                                    <th class="p-3 border-b text-center">{{ __('Sum of Squares') }}</th>
                                                    <th class="p-3 border-b text-center">{{ __('df') }}</th>
                                                    <th class="p-3 border-b text-center">{{ __('Mean Square') }}</th>
                                                    <th class="p-3 border-b text-center">{{ __('F') }}</th>
                                                    <th class="p-3 border-b text-center">{{ __('Sig.') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="p-3 border-b text-xs font-semibold text-gray-800">
                                                        {{ __('Regression') }}
                                                    </td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="regressionData.anova.ssr"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="regressionData.anova.dfReg"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="regressionData.anova.msr"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="regressionData.anova.fValue" rowspan="2"
                                                        style="vertical-align: middle;"></td>
                                                    <td class="p-3 border-b text-xs font-black text-center"
                                                        :class="regressionData.anova.significant ? 'text-green-600' : 'text-gray-500'"
                                                        x-text="formatP(regressionData.anova.pValue)" rowspan="2"
                                                        style="vertical-align: middle;"></td>
                                                </tr>
                                                <tr>
                                                    <td class="p-3 border-b text-xs font-semibold text-gray-800">
                                                        {{ __('Residual') }}
                                                    </td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="regressionData.anova.sse"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="regressionData.anova.dfRes"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="regressionData.anova.mse"></td>
                                                </tr>
                                                <tr class="bg-gray-50/50 font-bold text-gray-900">
                                                    <td class="p-3 border-b text-xs">{{ __('Total') }}</td>
                                                    <td class="p-3 border-b text-xs text-center"
                                                        x-text="regressionData.anova.sst"></td>
                                                    <td class="p-3 border-b text-xs text-center"
                                                        x-text="regressionData.anova.dfTotal"></td>
                                                    <td class="p-3 border-b text-xs text-gray-400 text-center">-</td>
                                                    <td class="p-3 border-b text-xs text-gray-400 text-center">-</td>
                                                    <td class="p-3 border-b text-xs text-gray-400 text-center">-</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('Coefficients') }}</h5>
                                    <div class="overflow-x-auto mb-8">
                                        <table class="w-full text-left border-collapse min-w-[700px]">
                                            <thead>
                                                <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                    <th class="p-3 border-b" rowspan="2">{{ __('Model') }}</th>
                                                    <th class="p-3 border-b text-center border-r" colspan="2">
                                                        {{ __('Unstandardized Coefficients') }}
                                                    </th>
                                                    <th class="p-3 border-b text-center border-r" rowspan="2">
                                                        {{ __('Standardized Coefficients (Beta)') }}
                                                    </th>
                                                    <th class="p-3 border-b text-center border-r" rowspan="2">{{ __('t') }}
                                                    </th>
                                                    <th class="p-3 border-b text-center border-r" rowspan="2">
                                                        {{ __('Sig.') }}
                                                    </th>
                                                    <th class="p-3 border-b text-center" colspan="2">
                                                        {{ __('95.0% Confidence Interval for B') }}
                                                    </th>
                                                </tr>
                                                <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                    <th class="p-3 border-b text-center">{{ __('B') }}</th>
                                                    <th class="p-3 border-b text-center border-r">{{ __('Std. Error') }}
                                                    </th>
                                                    <th class="p-3 border-b text-center border-l">{{ __('Lower Bound') }}
                                                    </th>
                                                    <th class="p-3 border-b text-center">{{ __('Upper Bound') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="hover:bg-gray-50/50 transition-colors">
                                                    <td class="p-3 border-b text-xs font-semibold text-gray-800">
                                                        {{ __('(Constant)') }}
                                                    </td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="regressionData.coefficients.intercept.coef"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r"
                                                        x-text="regressionData.coefficients.intercept.stdError"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r"
                                                        x-text="regressionData.coefficients.intercept.beta"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r"
                                                        x-text="regressionData.coefficients.intercept.tValue"></td>
                                                    <td class="p-3 border-b text-xs font-black text-center border-r"
                                                        :class="regressionData.coefficients.intercept.significant ? 'text-green-600' : 'text-gray-500'"
                                                        x-text="formatP(regressionData.coefficients.intercept.pValue)"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-l"
                                                        x-text="regressionData.coefficients.intercept.ciLower"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="regressionData.coefficients.intercept.ciUpper"></td>
                                                </tr>
                                                <tr class="hover:bg-gray-50/50 transition-colors">
                                                    <td class="p-3 border-b text-xs font-semibold text-gray-800 max-w-xs sm:max-w-sm whitespace-normal break-words leading-relaxed"
                                                        x-text="regressionData.shortIndLabel || regressionData.indLabel"
                                                        :title="regressionData.indLabel"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="regressionData.coefficients.slope.coef"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r"
                                                        x-text="regressionData.coefficients.slope.stdError"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r font-bold text-indigo-700"
                                                        x-text="regressionData.coefficients.slope.beta"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r"
                                                        x-text="regressionData.coefficients.slope.tValue"></td>
                                                    <td class="p-3 border-b text-xs font-black text-center border-r"
                                                        :class="regressionData.coefficients.slope.significant ? 'text-green-600' : 'text-gray-500'"
                                                        x-text="formatP(regressionData.coefficients.slope.pValue)"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-l"
                                                        x-text="regressionData.coefficients.slope.ciLower"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="regressionData.coefficients.slope.ciUpper"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Residuals Statistics Table -->
                                    <template
                                        x-if="regressionData.residualsStats && regressionData.residualsStats.length > 0">
                                        <div class="mb-8">
                                            <h5 class="text-sm font-black text-gray-900 mb-4">
                                                {{ __('Residuals Statistics') }}
                                            </h5>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left border-collapse min-w-[500px]">
                                                    <thead>
                                                        <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                            <th class="p-3 border-b">{{ __('Statistic') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('Minimum') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('Maximum') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('Mean') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('Std. Deviation') }}
                                                            </th>
                                                            <th class="p-3 border-b text-center">{{ __('N') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <template x-for="r in regressionData.residualsStats"
                                                            :key="r.statistic">
                                                            <tr class="hover:bg-gray-50/50">
                                                                <td class="p-3 border-b text-xs font-semibold text-gray-800"
                                                                    x-text="r.statistic"></td>
                                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                    x-text="r.min"></td>
                                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                    x-text="r.max"></td>
                                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                    x-text="r.mean"></td>
                                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                    x-text="r.stdDev"></td>
                                                                <td class="p-3 border-b text-xs font-bold text-gray-900 text-center"
                                                                    x-text="r.n"></td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Interactive Scatter Plot with Regression Line -->
                                    <template
                                        x-if="regressionData.scatterPoints && regressionData.scatterPoints.length > 0">
                                        <div class="border-t border-gray-100 pt-6">
                                            <div class="flex items-center justify-between mb-4">
                                                <h5 class="text-sm font-black text-gray-900">
                                                    {{ __('Linear Regression Fit Plot') }}
                                                </h5>
                                                <span
                                                    class="text-[10px] font-semibold px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-md"
                                                    x-text="'R² = ' + regressionData.r2"></span>
                                            </div>
                                            <div class="relative w-full h-80 sm:h-96 bg-gray-50/50 rounded-2xl p-4 border border-gray-100"
                                                x-init="$nextTick(() => renderCorrelationScatter($el, regressionData))">
                                                <canvas class="w-full h-full"></canvas>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- 5. Multiple Linear Regression Results -->
                        <template x-if="testMethod === 'regression_multiple' && multipleRegressionData">
                            <div class="space-y-8 animate-in fade-in duration-500">
                                <!-- Mathematical Formula Banner -->
                                <div style="background-color: #2271b1; color: #ffffff;"
                                    class="bg-[#2271b1] rounded-3xl p-5 sm:p-6 text-white border border-[#1b5b8d] shadow-sm flex flex-col gap-4">
                                    <div
                                        class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-white shrink-0">
                                                <i class="fa-solid fa-square-root-variable text-lg"></i>
                                            </div>
                                            <div>
                                                <h5 class="text-[9px] font-black text-blue-100 tracking-widest uppercase">
                                                    {{ __('Computed Regression Model Equation') }}
                                                </h5>
                                                <p class="text-sm sm:text-base font-bold mt-1 tracking-wide text-white font-mono break-all"
                                                    x-text="formatRegressionEquation(multipleRegressionData)"></p>
                                            </div>
                                        </div>
                                        <div
                                            class="text-[10px] font-semibold text-white bg-white/15 px-3 py-1.5 rounded-xl border border-white/20 whitespace-nowrap shrink-0">
                                            <i class="fa-solid fa-circle-info mr-1"></i> {{ __('OLS Parameter Estimates') }}
                                        </div>
                                    </div>
                                    <!-- Equation Token Legend / Key -->
                                    <template
                                        x-if="getEquationKey(multipleRegressionData) && getEquationKey(multipleRegressionData).length > 0">
                                        <div
                                            class="pt-3 border-t border-white/20 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 text-xs">
                                            <template x-for="item in getEquationKey(multipleRegressionData)"
                                                :key="item.code">
                                                <div
                                                    class="flex items-start gap-1.5 text-blue-100 text-[11px] leading-tight">
                                                    <span class="font-mono font-bold text-white shrink-0"
                                                        x-text="'[' + item.code + ']:'"></span>
                                                    <span class="truncate" :title="item.label"
                                                        x-text="item.short_label || item.label"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>

                                <div
                                    class="bg-white rounded-3xl p-4 sm:p-6 md:p-8 border border-gray-100 shadow-sm overflow-hidden space-y-6 sm:space-y-8">
                                    <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('Variables Entered/Removed') }}
                                    </h5>
                                    <div class="overflow-x-auto mb-8">
                                        <table class="w-full text-left border-collapse">
                                            <thead>
                                                <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                    <th class="p-3 border-b">{{ __('Model') }}</th>
                                                    <th class="p-3 border-b">{{ __('Variables Entered') }}</th>
                                                    <th class="p-3 border-b">{{ __('Variables Removed') }}</th>
                                                    <th class="p-3 border-b">{{ __('Method') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="hover:bg-gray-50/50 transition-colors">
                                                    <td class="p-3 border-b text-xs font-semibold text-gray-800">1</td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-700"
                                                        x-text="(multipleRegressionData.indShortLabels || multipleRegressionData.indLabels || []).join(', ') + ' ᵇ'">
                                                    </td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600">.</td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600">
                                                        {{ __('Enter') }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <div class="mt-2 text-[10px] text-gray-500 italic space-y-0.5">
                                            <p
                                                x-text="'a. Dependent Variable: ' + (multipleRegressionData.shortDepLabel || multipleRegressionData.depLabel)">
                                            </p>
                                            <p>{{ __('b. All requested variables entered.') }}</p>
                                        </div>
                                    </div>

                                    <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('Model Summary') }}</h5>
                                    <div class="overflow-x-auto mb-8">
                                        <table class="w-full text-left border-collapse">
                                            <thead>
                                                <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                    <th class="p-3 border-b text-center">{{ __('Model') }}</th>
                                                    <th class="p-3 border-b text-center">{{ __('R') }}</th>
                                                    <th class="p-3 border-b text-center">{{ __('R Square') }}</th>
                                                    <th class="p-3 border-b text-center">{{ __('Adjusted R Square') }}</th>
                                                    <th class="p-3 border-b text-center">
                                                        {{ __('Std. Error of the Estimate') }}
                                                    </th>
                                                    <template x-if="multipleRegressionData.durbinWatson">
                                                        <th class="p-3 border-b text-center">{{ __('Durbin-Watson') }}</th>
                                                    </template>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td
                                                        class="p-3 border-b text-xs font-semibold text-gray-800 text-center">
                                                        1</td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="multipleRegressionData.r"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="multipleRegressionData.r2"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="multipleRegressionData.adjR2"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="multipleRegressionData.stdErrorEst"></td>
                                                    <template x-if="multipleRegressionData.durbinWatson">
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="multipleRegressionData.durbinWatson"></td>
                                                    </template>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <h5 class="text-sm font-black text-gray-900 mb-4">
                                        {{ __('ANOVA (Regression significance)') }}
                                    </h5>
                                    <div class="overflow-x-auto mb-8">
                                        <table class="w-full text-left border-collapse">
                                            <thead>
                                                <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                    <th class="p-3 border-b"></th>
                                                    <th class="p-3 border-b text-center">{{ __('Sum of Squares') }}</th>
                                                    <th class="p-3 border-b text-center">{{ __('df') }}</th>
                                                    <th class="p-3 border-b text-center">{{ __('Mean Square') }}</th>
                                                    <th class="p-3 border-b text-center">{{ __('F') }}</th>
                                                    <th class="p-3 border-b text-center">{{ __('Sig.') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="p-3 border-b text-xs font-semibold text-gray-800">
                                                        {{ __('Regression') }}
                                                    </td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="multipleRegressionData.anova.ssr"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="multipleRegressionData.anova.dfReg"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="multipleRegressionData.anova.msr"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="multipleRegressionData.anova.fValue" rowspan="2"
                                                        style="vertical-align: middle;"></td>
                                                    <td class="p-3 border-b text-xs font-black text-center"
                                                        :class="multipleRegressionData.anova.significant ? 'text-green-600' : 'text-gray-500'"
                                                        x-text="formatP(multipleRegressionData.anova.pValue)" rowspan="2"
                                                        style="vertical-align: middle;"></td>
                                                </tr>
                                                <tr>
                                                    <td class="p-3 border-b text-xs font-semibold text-gray-800">
                                                        {{ __('Residual') }}
                                                    </td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="multipleRegressionData.anova.sse"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="multipleRegressionData.anova.dfRes"></td>
                                                    <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                        x-text="multipleRegressionData.anova.mse"></td>
                                                </tr>
                                                <tr class="bg-gray-50/50 font-bold text-gray-900">
                                                    <td class="p-3 border-b text-xs">{{ __('Total') }}</td>
                                                    <td class="p-3 border-b text-xs text-center"
                                                        x-text="multipleRegressionData.anova.sst"></td>
                                                    <td class="p-3 border-b text-xs text-center"
                                                        x-text="multipleRegressionData.anova.dfTotal"></td>
                                                    <td class="p-3 border-b text-xs text-gray-400 text-center">-</td>
                                                    <td class="p-3 border-b text-xs text-gray-400 text-center">-</td>
                                                    <td class="p-3 border-b text-xs text-gray-400 text-center">-</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Multicollinearity Alert Banner -->
                                    <template x-if="multipleRegressionData.hasMulticollinearity">
                                        <div
                                            class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-2xl text-amber-800 text-xs flex items-start gap-3">
                                            <i class="fa-solid fa-triangle-exclamation text-amber-500 mt-0.5 text-sm"></i>
                                            <div>
                                                <span
                                                    class="font-bold block">{{ __('Warning: Potential Multicollinearity Detected') }}</span>
                                                <span
                                                    class="text-amber-700">{{ __('One or more predictor variables exhibit a Variance Inflation Factor (VIF) > 5.0 or Tolerance < 0.20, indicating high collinearity that may inflate standard errors.') }}</span>
                                            </div>
                                        </div>
                                    </template>

                                    <h5 class="text-sm font-black text-gray-900 mb-4">{{ __('Coefficients') }}</h5>
                                    <div class="overflow-x-auto mb-8">
                                        <table class="w-full text-left border-collapse min-w-[750px]">
                                            <thead>
                                                <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                    <th class="p-3 border-b" rowspan="2">{{ __('Model') }}</th>
                                                    <th class="p-3 border-b text-center border-r" colspan="2">
                                                        {{ __('Unstandardized Coefficients') }}
                                                    </th>
                                                    <th class="p-3 border-b text-center border-r" rowspan="2">
                                                        {{ __('Standardized Coefficients (Beta)') }}
                                                    </th>
                                                    <th class="p-3 border-b text-center border-r" rowspan="2">{{ __('t') }}
                                                    </th>
                                                    <th class="p-3 border-b text-center border-r" rowspan="2">
                                                        {{ __('Sig.') }}
                                                    </th>
                                                    <th class="p-3 border-b text-center border-r" colspan="2">
                                                        {{ __('95.0% Confidence Interval for B') }}
                                                    </th>
                                                    <th class="p-3 border-b text-center" colspan="2">
                                                        {{ __('Collinearity Statistics') }}
                                                    </th>
                                                </tr>
                                                <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                    <th class="p-3 border-b text-center">{{ __('B') }}</th>
                                                    <th class="p-3 border-b text-center border-r">{{ __('Std. Error') }}
                                                    </th>
                                                    <th class="p-3 border-b text-center border-l">{{ __('Lower Bound') }}
                                                    </th>
                                                    <th class="p-3 border-b text-center border-r">{{ __('Upper Bound') }}
                                                    </th>
                                                    <th class="p-3 border-b text-center border-l">{{ __('Tolerance') }}</th>
                                                    <th class="p-3 border-b text-center">{{ __('VIF') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="coef in multipleRegressionData.coefficients"
                                                    :key="coef.variable">
                                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                                        <td class="p-3 border-b text-xs font-semibold text-gray-800 max-w-xs sm:max-w-sm whitespace-normal break-words leading-relaxed"
                                                            x-text="coef.short_label || coef.label" :title="coef.label">
                                                        </td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                            x-text="coef.coef"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r"
                                                            x-text="coef.stdError"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r font-bold"
                                                            :class="coef.beta !== 'N/A' ? 'text-[#2271b1]' : 'text-gray-400'"
                                                            x-text="coef.beta"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r"
                                                            x-text="coef.tValue"></td>
                                                        <td class="p-3 border-b text-xs font-black text-center border-r"
                                                            :class="coef.significant ? 'text-green-600' : 'text-gray-500'"
                                                            x-text="formatP(coef.pValue)"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-l"
                                                            x-text="coef.ciLower"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-r"
                                                            x-text="coef.ciUpper"></td>
                                                        <td class="p-3 border-b text-xs font-medium text-gray-600 text-center border-l"
                                                            x-text="coef.tolerance !== undefined && coef.tolerance !== null ? coef.tolerance : '-'">
                                                        </td>
                                                        <td class="p-3 border-b text-xs font-medium text-center"
                                                            :class="coef.vif > 10 ? 'font-black text-red-600' : (coef.vif > 5 ? 'font-bold text-amber-600' : 'text-gray-600')"
                                                            x-text="coef.vif !== undefined && coef.vif !== null ? coef.vif : '-'">
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Residuals Statistics Table -->
                                    <template
                                        x-if="multipleRegressionData.residualsStats && multipleRegressionData.residualsStats.length > 0">
                                        <div class="mb-4">
                                            <h5 class="text-sm font-black text-gray-900 mb-4">
                                                {{ __('Residuals Statistics') }}
                                            </h5>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left border-collapse min-w-[500px]">
                                                    <thead>
                                                        <tr class="bg-gray-50 font-bold text-gray-700 text-xs">
                                                            <th class="p-3 border-b">{{ __('Statistic') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('Minimum') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('Maximum') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('Mean') }}</th>
                                                            <th class="p-3 border-b text-center">{{ __('Std. Deviation') }}
                                                            </th>
                                                            <th class="p-3 border-b text-center">{{ __('N') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <template x-for="r in multipleRegressionData.residualsStats"
                                                            :key="r.statistic">
                                                            <tr class="hover:bg-gray-50/50">
                                                                <td class="p-3 border-b text-xs font-semibold text-gray-800"
                                                                    x-text="r.statistic"></td>
                                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                    x-text="r.min"></td>
                                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                    x-text="r.max"></td>
                                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                    x-text="r.mean"></td>
                                                                <td class="p-3 border-b text-xs font-medium text-gray-600 text-center"
                                                                    x-text="r.stdDev"></td>
                                                                <td class="p-3 border-b text-xs font-bold text-gray-900 text-center"
                                                                    x-text="r.n"></td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Copy / Flow actions for successful calculation -->
                        <template
                            x-if="matrixData || tTestData || anovaData || correlationData || regressionData || multipleRegressionData">
                            <div
                                class="bg-white rounded-3xl p-4 sm:p-6 border border-gray-100 shadow-sm flex flex-col sm:flex-row gap-4 items-stretch sm:items-center justify-between overflow-hidden">
                                <button @click="copyResultsToClipboard()"
                                    class="w-full sm:w-auto px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-[10px] font-black tracking-widest transition-all flex items-center justify-center gap-2">
                                    <i class="fa-solid fa-copy"></i> {{ __('Copy Results to Clipboard') }}
                                </button>

                                <div class="flex items-center gap-3">
                                    <span
                                        class="text-[10px] font-bold text-gray-400  tracking-wider">{{ __('Continue Analysis Flow:') }}</span>
                                    <template x-if="testMethod === 'correlation'">
                                        <button
                                            @click="testMethod = 'regression'; depVar = varY; groupVar = varX; runAnalysis();"
                                            class="text-[10px] font-black text-indigo-600 hover:text-indigo-800 uppercase tracking-widest transition-all">
                                            {{ __('Run Simple Regression') }} <i class="fa-solid fa-arrow-right ml-1"></i>
                                        </button>
                                    </template>
                                    <template x-if="testMethod === 'regression'">
                                        <button
                                            @click="testMethod = 'regression_multiple'; indVars = [groupVar]; runAnalysis();"
                                            class="text-[10px] font-black text-indigo-600 hover:text-indigo-800  tracking-widest transition-all">
                                            {{ __('Continue to Multiple Regression') }} <i
                                                class="fa-solid fa-arrow-right ml-1"></i>
                                        </button>
                                    </template>
                                    <template x-if="testMethod === 'ttest'">
                                        <button @click="testMethod = 'anova'; runAnalysis();"
                                            class="text-[10px] font-black text-indigo-600 hover:text-indigo-800  tracking-widest transition-all">
                                            {{ __('Run ANOVA check') }} <i class="fa-solid fa-arrow-right ml-1"></i>
                                        </button>
                                    </template>
                                    <template x-if="testMethod === 'crosstab'">
                                        <button @click="testMethod = 'correlation'; varX = rowVar; varY = colVar;"
                                            class="text-[10px] font-black text-indigo-600 hover:text-indigo-800  tracking-widest transition-all">
                                            {{ __('Check Pearson Correlation') }} <i
                                                class="fa-solid fa-arrow-right ml-1"></i>
                                        </button>
                                    </template>
                                    <template x-if="testMethod === 'regression_multiple' || testMethod === 'anova'">
                                        <span
                                            class="text-[10px] text-gray-400 italic">{{ __('Analysis flow complete.') }}</span>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Statistical Interpretation Card -->
                        <template
                            x-if="matrixData || chisquareData || cronbachData || tTestData || anovaData || correlationData || regressionData || multipleRegressionData">
                            <div class="mt-6 space-y-4">
                                <!-- Loading State -->
                                <div x-show="aiLoading"
                                    class="bg-white rounded-2xl border border-gray-100 p-6 flex items-center justify-center gap-3 text-[#2271b1]">
                                    <i class="fa-solid fa-circle-notch fa-spin text-base"></i>
                                    <span class="text-xs font-bold">{{ __('Analyzing statistical significance...') }}</span>
                                </div>

                                <!-- Interpretation Content Box -->
                                <div x-show="aiMessages.length > 0 && !aiLoading"
                                    class="bg-white rounded-2xl p-6 sm:p-8 border border-gray-100 shadow-sm relative space-y-4">
                                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                                        <h5 class="text-xs font-bold text-[#2271b1] flex items-center gap-2">
                                            <i class="fa-solid fa-chart-line text-xs"></i>
                                            <span>{{ __('Interpretation') }}</span>
                                        </h5>
                                        <button type="button" @click="aiMessages = []; aiInsight = null; aiFeedback = '';"
                                            class="text-xs font-semibold text-gray-400 hover:text-red-500 transition-colors cursor-pointer">
                                            {{ __('Reset') }}
                                        </button>
                                    </div>

                                    <!-- Chat / Interpretation Messages -->
                                    <div class="space-y-3 py-1 w-full">
                                        <template x-for="(msg, index) in aiMessages" :key="index">
                                            <template
                                                x-if="msg && msg.content && typeof msg.content === 'string' && msg.content.trim() !== ''">
                                                <div class="flex flex-col"
                                                    :class="msg.role === 'user' ? 'items-end' : 'items-start'">
                                                    <div class="max-w-[90%] rounded-2xl px-4 py-3 text-[13px] leading-relaxed font-medium"
                                                        :class="msg.role === 'user' 
                                                                                                                                                                                                        ? 'bg-[#2271b1] text-white rounded-br-none shadow-sm' 
                                                                                                                                                                                                        : 'bg-gray-50 text-gray-800 rounded-bl-none border border-gray-200/60 shadow-xs'">
                                                        <div class="whitespace-pre-wrap leading-relaxed"
                                                            x-html="renderFormattedText(msg.content)"></div>
                                                    </div>
                                                </div>
                                            </template>
                                        </template>
                                    </div>

                                    <!-- Polish / Refinement Input -->
                                    <div x-show="!aiPolishing && aiMessages.length > 0"
                                        class="border-t border-gray-100 pt-4 mt-2 w-full">
                                        <label
                                            class="block text-xs font-bold text-gray-700 mb-1.5">{{ __('Refine this statistical interpretation') }}</label>
                                        <div class="flex flex-col sm:flex-row gap-2.5 items-stretch">
                                            <input x-model="aiFeedback" type="text"
                                                placeholder="{{ __('Type instructions to refine in your own voice, e.g., focus on practical policy takeaways...') }}"
                                                @keydown.enter.prevent="polishAiInsight()"
                                                class="flex-1 bg-gray-50 border border-gray-200 text-xs font-medium rounded-xl px-3.5 py-2.5 focus:bg-white focus:ring-1 focus:ring-[#2271b1] focus:border-[#2271b1] focus:outline-none transition-all">
                                            <button @click="polishAiInsight()" :disabled="aiPolishing || !aiFeedback.trim()"
                                                class="px-5 py-2.5 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-xl text-xs font-bold transition-all disabled:opacity-50 flex items-center justify-center gap-1.5 shrink-0 shadow-xs cursor-pointer">
                                                <i class="fa-solid fa-arrows-rotate text-xs"
                                                    :class="{'fa-spin': aiPolishing}"></i>
                                                <span
                                                    x-text="aiPolishing ? '{{ __('Polishing...') }}' : '{{ __('Refine') }}'"></span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Actions Toolbar (Analyze in Socius, KB Rules) -->
                                    <div
                                        class="mt-4 pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" @click="analyzeWithSocius()"
                                                class="px-3.5 py-1.5 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-xs cursor-pointer"
                                                title="{{ __('Transfer complete statistical test results to Socius chat for deep writing & literature synthesis') }}">
                                                <i class="fa-solid fa-comments text-xs"></i>
                                                <span>{{ __('Analyze in Socius') }}</span>
                                                <i class="fa-solid fa-arrow-right text-[10px] ml-0.5 opacity-80"></i>
                                            </button>
                                            <button type="button" @click="openKbModal('inferential')"
                                                class="px-3 py-1.5 bg-gray-50 border border-gray-200 hover:bg-gray-100 text-gray-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
                                                title="{{ __('Open Knowledge Base rules for Inferential Statistics') }}">
                                                <i class="fa-solid fa-book-bookmark text-xs text-[#2271b1]"></i>
                                                <span>{{ __('KB Rules') }}</span>
                                                <span
                                                    class="px-1.5 py-0.2 rounded-full text-[10px] bg-[#2271b1]/10 text-[#2271b1] font-bold"
                                                    x-text="inferentialKbCount"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Initial State (Interpret Results Button) -->
                                <div x-show="!aiLoading && aiMessages.length === 0"
                                    class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex flex-wrap items-center justify-between gap-4">
                                    <div>
                                        <h5 class="text-xs font-bold text-gray-900">{{ __('Statistical Interpretation') }}
                                        </h5>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            {{ __('Generate a statistical narrative and academic discussion for these results.') }}
                                        </p>
                                    </div>
                                    <button type="button" @click="getAiInterpretation()" :disabled="aiLoading"
                                        class="px-5 py-2.5 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-xl font-bold text-xs transition-all shadow-xs flex items-center gap-2 cursor-pointer disabled:opacity-50">
                                        <i class="fa-solid fa-chart-line text-xs" :class="{'fa-spin': aiLoading}"></i>
                                        <span
                                            x-text="aiLoading ? '{{ __('Interpreting...') }}' : '{{ __('Interpret Results') }}'"></span>
                                    </button>
                                    <button type="button" @click="analyzeWithSocius()"
                                        class="px-4 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-xl font-bold text-xs transition-all shadow-xs flex items-center gap-2 cursor-pointer">
                                        <i class="fa-solid fa-comments text-[#2271b1] text-xs"></i>
                                        <span>{{ __('Analyze in Socius') }}</span>
                                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                    </button>
                                </div>
                            </div>
                    </div>
                    </template>
                </div>
            </div>

            @push('scripts')
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
                <script>
                    const chartConfigs = {!! json_encode($chartConfigs) !!};
                    const chartInstances = {};

                    const colorPalettes = {
                        indigo: ['#4f46e5', '#6366f1', '#818cf8', '#a5b4fc', '#c7d2fe', '#e0e7ff', '#3730a3', '#312e81'],
                        emerald: ['#10b981', '#059669', '#34d399', '#6ee7b7', '#a7f3d0', '#d1fae5', '#065f46', '#064e3b'],
                        rose: ['#f43f5e', '#e11d48', '#fb7185', '#fda4af', '#fecdd3', '#fff1f2', '#9f1239', '#881337'],
                        amber: ['#f59e0b', '#d97706', '#fbbf24', '#fcd34d', '#fde68a', '#fef3c7', '#b45309', '#92400e'],
                        purple: ['#8b5cf6', '#7c3aed', '#a78bfa', '#c4b5fd', '#ddd6fe', '#ede9fe', '#5b21b6', '#4c1d95'],
                        vibrant: ['#6366f1', '#10b981', '#f43f5e', '#f59e0b', '#8b5cf6', '#06b6d4', '#ec4899', '#f97316'],
                        greyscale: ['#374151', '#4b5563', '#6b7280', '#9ca3af', '#d1d5db', '#e5e7eb', '#1f2937', '#111827']
                    };

                    window.onFreqTableChange = function (canvasId) {
                        const table = document.getElementById(`table-${canvasId}`);
                        if (!table) return;

                        const rows = Array.from(table.querySelectorAll('tbody tr.freq-row'));
                        if (!rows.length) return;

                        let validTotal = 0;
                        let grandTotal = 0;
                        let isModified = false;

                        const rowData = rows.map(row => {
                            const isMissing = row.getAttribute('data-is-missing') === '1';
                            const valueCell = row.querySelector('.freq-value-cell');
                            const countCell = row.querySelector('.freq-count-cell');

                            const origVal = (row.getAttribute('data-original-value') || '').trim();
                            const origCountRaw = row.getAttribute('data-original-count');
                            const origCount = origCountRaw !== null ? Math.max(0, parseFloat(origCountRaw) || 0) : null;

                            const label = valueCell ? valueCell.innerText.trim() : (isMissing ? 'Missing' : '');
                            const countRaw = countCell ? countCell.innerText.replace(/[^0-9.]/g, '') : '0';
                            const count = Math.max(0, parseFloat(countRaw) || 0);

                            if ((valueCell && label !== origVal) || (origCount !== null && count !== origCount)) {
                                isModified = true;
                            }

                            // If count is 0 for a non-missing row (or if user zeroed/cleared it), hide the table row from view
                            if (!isMissing && count === 0) {
                                row.style.display = 'none';
                            } else {
                                row.style.display = '';
                            }

                            if (!isMissing && count > 0) {
                                validTotal += count;
                                grandTotal += count;
                            } else if (isMissing) {
                                grandTotal += count;
                            }

                            return { row, isMissing, label, count };
                        });

                        // Show or hide inline reset button next to Live Editable badge
                        const resetBtn = document.getElementById(`reset-btn-${canvasId}`);
                        if (resetBtn) {
                            resetBtn.style.display = isModified ? 'inline-flex' : 'none';
                        }

                        if (validTotal === 0) validTotal = grandTotal;

                        const chartLabels = [];
                        const chartPercentages = [];
                        const chartCounts = [];
                        let cumulativeValidPct = 0;

                        rowData.forEach(({ row, isMissing, label, count }) => {
                            if (!isMissing && count === 0) {
                                return; // Row is hidden from table & chart
                            }

                            const pct = grandTotal > 0 ? (count / grandTotal) * 100 : 0;
                            const pctCell = row.querySelector('.freq-percent-cell');
                            const validPctCell = row.querySelector('.freq-valid-percent-cell');
                            const cumPctCell = row.querySelector('.freq-cum-percent-cell');

                            if (pctCell) pctCell.innerText = `${pct.toFixed(1)}%`;

                            if (isMissing) {
                                if (validPctCell) validPctCell.innerText = '-';
                                if (cumPctCell) cumPctCell.innerText = '-';
                            } else {
                                const validPct = validTotal > 0 ? (count / validTotal) * 100 : 0;
                                cumulativeValidPct += validPct;

                                if (validPctCell) validPctCell.innerText = `${validPct.toFixed(1)}%`;
                                if (cumPctCell) cumPctCell.innerText = `${Math.min(100, cumulativeValidPct).toFixed(1)}%`;

                                // Exclude 0 count from the chart and use raw % (percent of grand total)
                                if (count > 0) {
                                    chartLabels.push(label);
                                    chartPercentages.push(parseFloat(pct.toFixed(1)));
                                    chartCounts.push(count);
                                }
                            }
                        });

                        const totalCell = table.querySelector('.freq-total-cell');
                        if (totalCell) totalCell.innerText = grandTotal.toLocaleString();

                        // Sync chart
                        const chart = chartInstances[canvasId];
                        if (chart) {
                            chart.data.labels = chartLabels.map(lbl => wrapJsChartLabel(lbl, 25));
                            if (chart.data.datasets && chart.data.datasets[0]) {
                                chart.data.datasets[0].data = chartPercentages;
                                const curPalette = (window.Alpine && document.querySelector('[x-data]'))
                                    ? (colorPalettes[Alpine.$data(document.querySelector('[x-data]')).selectedPalette] || colorPalettes.indigo)
                                    : colorPalettes.indigo;
                                if (Array.isArray(chart.data.datasets[0].backgroundColor)) {
                                    chart.data.datasets[0].backgroundColor = chartLabels.map((_, i) => curPalette[i % curPalette.length]);
                                }
                                if (Array.isArray(chart.data.datasets[0].borderColor) && (chart.config.type === 'bar' || chart.config.options?.indexAxis === 'y')) {
                                    chart.data.datasets[0].borderColor = chartLabels.map((_, i) => curPalette[i % curPalette.length]);
                                }
                            }

                            const config = chartConfigs.find(c => c.canvas_id === canvasId);
                            if (config) {
                                config.data = chartCounts;
                                config.labels = chartLabels;
                                config.total_responses = grandTotal;
                            }

                            const canvasWrapper = chart.canvas.closest('.chart-canvas-wrapper') || chart.canvas.parentElement;
                            if (canvasWrapper && canvasWrapper.classList.contains('chart-canvas-wrapper') && chart.config.type === 'bar' && chart.config.options?.indexAxis !== 'y') {
                                if (chartLabels.length <= 5) {
                                    canvasWrapper.style.maxWidth = `${Math.min(800, Math.max(340, chartLabels.length * 130 + 80))}px`;
                                } else {
                                    canvasWrapper.style.maxWidth = '100%';
                                }
                            }

                            chart.update();
                        }
                    };

                    window.resetTableAndChart = function (canvasId) {
                        const table = document.getElementById(`table-${canvasId}`);
                        if (!table) return;

                        const rows = Array.from(table.querySelectorAll('tbody tr.freq-row'));
                        rows.forEach(row => {
                            row.style.display = '';
                            const origVal = row.getAttribute('data-original-value');
                            const origCount = row.getAttribute('data-original-count');
                            const valueCell = row.querySelector('.freq-value-cell');
                            const countCell = row.querySelector('.freq-count-cell');

                            if (valueCell && origVal !== null) valueCell.innerText = origVal;
                            if (countCell && origCount !== null) countCell.innerText = Number(origCount).toLocaleString();
                        });

                        window.onFreqTableChange(canvasId);

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: @js(__('Data Reset')),
                                text: @js(__('Table and chart restored to original values.')),
                                icon: 'success',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 1800
                            });
                        }
                    };

                    window.copyChartToClipboard = function (canvasId, btn = null) {
                        const canvas = document.getElementById(canvasId);
                        if (!canvas) return;

                        const scaleFactor = 3;
                        const tempCanvas = document.createElement('canvas');
                        tempCanvas.width = canvas.width * scaleFactor;
                        tempCanvas.height = canvas.height * scaleFactor;
                        const tempCtx = tempCanvas.getContext('2d');
                        tempCtx.imageSmoothingEnabled = true;
                        tempCtx.imageSmoothingQuality = 'high';

                        // Draw solid crisp white background
                        tempCtx.fillStyle = '#ffffff';
                        tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
                        tempCtx.drawImage(canvas, 0, 0, tempCanvas.width, tempCanvas.height);

                        const dataUrl = tempCanvas.toDataURL('image/png');
                        const chart = chartInstances[canvasId];
                        let htmlContent = '';

                        if (chart) {
                            const mapName = `map-${canvasId}-${Math.random().toString(36).substr(2, 9)}`;
                            let areas = [];
                            const datasets = chart.data.datasets;

                            if (datasets && datasets[0]) {
                                const meta = chart.getDatasetMeta(0);
                                const labels = chart.data.labels;
                                const data = datasets[0].data;

                                if (chart.config.type === 'bar') {
                                    const isHorizontal = chart.config.options?.indexAxis === 'y';
                                    meta.data.forEach((element, index) => {
                                        const view = element;
                                        const label = labels[index] || '';
                                        const rawVal = data[index] || 0;
                                        let left, right, top, bottom;

                                        if (isHorizontal) {
                                            left = view.base * scaleFactor;
                                            right = view.x * scaleFactor;
                                            top = (view.y - view.height / 2) * scaleFactor;
                                            bottom = (view.y + view.height / 2) * scaleFactor;
                                        } else {
                                            left = (view.x - view.width / 2) * scaleFactor;
                                            right = (view.x + view.width / 2) * scaleFactor;
                                            top = view.y * scaleFactor;
                                            bottom = view.base * scaleFactor;
                                        }
                                        areas.push(`<area shape="rect" coords="${Math.round(left)},${Math.round(top)},${Math.round(right)},${Math.round(bottom)}" title="${label}: ${rawVal}%" alt="${label}" />`);
                                    });
                                } else if (['pie', 'doughnut', 'polarArea'].includes(chart.config.type)) {
                                    meta.data.forEach((element, index) => {
                                        const view = element;
                                        const label = labels[index] || '';
                                        const rawVal = data[index] || 0;
                                        const cx = view.x * scaleFactor;
                                        const cy = view.y * scaleFactor;
                                        const r = view.outerRadius * scaleFactor;
                                        const start = view.startAngle;
                                        const end = view.endAngle;

                                        let coords = [];
                                        coords.push(`${cx},${cy}`);
                                        const steps = 16;
                                        for (let i = 0; i <= steps; i++) {
                                            const angle = start + (end - start) * (i / steps);
                                            const px = cx + r * Math.cos(angle);
                                            const py = cy + r * Math.sin(angle);
                                            coords.push(`${Math.round(px)},${Math.round(py)}`);
                                        }
                                        areas.push(`<area shape="poly" coords="${coords.join(',')}" title="${label}: ${rawVal}%" alt="${label}" />`);
                                    });
                                }
                            }

                            if (areas.length > 0) {
                                htmlContent = `<img src="${dataUrl}" usemap="#${mapName}" style="max-width:100%;height:auto;" />
                                                                                                                                                                                                                                                                                                               <map name="${mapName}">
                                                                                                                                                                                                                                                                                                                 ${areas.join('\n  ')}
                                                                                                                                                                                                                                                                                                               </map>`;
                            }
                        }

                        if (!htmlContent) {
                            htmlContent = `<img src="${dataUrl}" style="max-width:100%;height:auto;" />`;
                        }

                        tempCanvas.toBlob(blob => {
                            if (!blob) return;
                            const htmlBlob = new Blob([htmlContent], { type: 'text/html' });

                            navigator.clipboard.write([
                                new ClipboardItem({
                                    'image/png': blob,
                                    'text/html': htmlBlob
                                })
                            ]).then(() => {
                                if (btn) {
                                    const btnSpan = btn.querySelector('span');
                                    const originalText = btnSpan.innerText;
                                    btnSpan.innerText = 'Copied (3x HD)!';
                                    btn.classList.add('bg-green-600', 'text-white');
                                    setTimeout(() => {
                                        btnSpan.innerText = originalText;
                                        btn.classList.remove('bg-green-600', 'text-white');
                                    }, 2000);
                                }
                            }).catch(err => {
                                console.error('Copy chart failed:', err);
                                Swal.fire({
                                    title: @js(__('Copy Failed')),
                                    text: @js(__('Could not copy chart to clipboard.')),
                                    icon: 'error',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 2000
                                });
                            });
                        }, 'image/png');
                    };

                    window.exportChartToPng = function (canvasId, title) {
                        const canvas = document.getElementById(canvasId);
                        if (!canvas) return;

                        const scaleFactor = 3;
                        const tempCanvas = document.createElement('canvas');
                        tempCanvas.width = canvas.width * scaleFactor;
                        tempCanvas.height = canvas.height * scaleFactor;
                        const tempCtx = tempCanvas.getContext('2d');
                        tempCtx.imageSmoothingEnabled = true;
                        tempCtx.imageSmoothingQuality = 'high';

                        // Draw solid crisp white background
                        tempCtx.fillStyle = '#ffffff';
                        tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
                        tempCtx.drawImage(canvas, 0, 0, tempCanvas.width, tempCanvas.height);

                        const url = tempCanvas.toDataURL('image/png');
                        const link = document.createElement('a');
                        link.download = `${title.replace(/[^a-z0-9]/gi, '_').toLowerCase()}_chart_hd.png`;
                        link.href = url;
                        link.click();
                    };

                    window.exportTableToCsv = function (tableId, filename) {
                        const table = document.getElementById(tableId);
                        if (!table) return;

                        const rows = Array.from(table.querySelectorAll('tr'));
                        const csvContent = rows.map(row => {
                            const cols = Array.from(row.querySelectorAll('th, td'));
                            return cols.map(col => {
                                let text = col.innerText.trim();
                                text = text.replace(/"/g, '""');
                                return `"${text}"`;
                            }).join(',');
                        }).join('\n');

                        const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
                        const url = URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.setAttribute('download', `${filename.replace(/[^a-z0-9]/gi, '_').toLowerCase()}_table.csv`);
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    };

                    window.copyTableToClipboard = function (tableId) {
                        const table = document.getElementById(tableId);
                        if (!table) return;

                        const clone = table.cloneNode(true);
                        clone.style.borderCollapse = 'collapse';
                        clone.style.width = '100%';
                        clone.style.fontFamily = 'Arial, sans-serif';
                        clone.style.fontSize = '13px';

                        clone.querySelectorAll('th').forEach(th => {
                            th.style.border = '1px solid #d4d4d8';
                            th.style.padding = '8px 12px';
                            th.style.backgroundColor = '#f4f4f5';
                            th.style.fontWeight = 'bold';
                            th.style.textAlign = th.classList.contains('text-right') ? 'right' : 'left';
                        });
                        clone.querySelectorAll('td').forEach(td => {
                            td.style.border = '1px solid #d4d4d8';
                            td.style.padding = '8px 12px';
                            td.style.textAlign = td.classList.contains('text-right') ? 'right' : 'left';
                        });

                        const htmlContent = `<table>${clone.innerHTML}</table>`;
                        const plainText = Array.from(table.querySelectorAll('tr'))
                            .map(row => Array.from(row.querySelectorAll('th, td')).map(c => c.innerText.trim()).join('\t'))
                            .join('\n');

                        const blobHtml = new Blob([htmlContent], { type: 'text/html' });
                        const blobText = new Blob([plainText], { type: 'text/plain' });

                        navigator.clipboard.write([
                            new ClipboardItem({ 'text/html': blobHtml, 'text/plain': blobText })
                        ]).then(() => {
                            Swal.fire({
                                title: @js(__('Copied!')),
                                text: @js(__('Table copied. Paste directly into Word or Google Docs.')),
                                icon: 'success',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                                customClass: { popup: 'rounded-2xl shadow-xl border-none' }
                            });
                        }).catch(() => {
                            navigator.clipboard.writeText(plainText);
                        });
                    };

                    window.copyChartToClipboard = function (canvasId, btn = null) {
                        const canvas = document.getElementById(canvasId);
                        if (!canvas) return;

                        const tempCanvas = document.createElement('canvas');
                        tempCanvas.width = canvas.width;
                        tempCanvas.height = canvas.height;
                        const tempCtx = tempCanvas.getContext('2d');
                        tempCtx.fillStyle = '#ffffff';
                        tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
                        tempCtx.drawImage(canvas, 0, 0);

                        tempCanvas.toBlob(blob => {
                            if (!blob) return;
                            navigator.clipboard.write([
                                new ClipboardItem({ 'image/png': blob })
                            ]).then(() => {
                                if (btn) {
                                    const span = btn.querySelector('span');
                                    const orig = span ? span.innerText : btn.innerHTML;
                                    if (span) span.innerText = 'Copied!';
                                    btn.classList.add('!bg-emerald-600', '!text-white');
                                    setTimeout(() => {
                                        if (span) span.innerText = orig;
                                        btn.classList.remove('!bg-emerald-600', '!text-white');
                                    }, 2000);
                                }
                            }).catch(err => {
                                console.error('Copy chart failed:', err);
                                Swal.fire({
                                    title: @js(__('Copy Failed')),
                                    text: @js(__('Your browser blocked clipboard access. Try exporting instead.')),
                                    icon: 'warning', toast: true, position: 'top-end',
                                    showConfirmButton: false, timer: 3000
                                });
                            });
                        }, 'image/png');
                    };

                    window.copyRenderedSociusTable = function (tableId, btn = null) {
                        const table = document.getElementById(tableId);
                        if (!table) return;

                        let plainText = '';
                        const rows = table.querySelectorAll('tr');
                        rows.forEach((row) => {
                            const cols = row.querySelectorAll('th, td');
                            const rowData = [];
                            cols.forEach(col => {
                                rowData.push(col.innerText.trim());
                            });
                            plainText += rowData.join('\t') + '\n';
                        });

                        let htmlContent = `<table style="border-collapse: collapse; width: 100%; font-family: Calibri, Arial, sans-serif; font-size: 13px; color: #1e293b; border: 1px solid #cbd5e1;">`;
                        const headerRows = table.querySelectorAll('thead tr');
                        if (headerRows.length > 0) {
                            htmlContent += `<thead>`;
                            headerRows.forEach(row => {
                                htmlContent += `<tr>`;
                                row.querySelectorAll('th').forEach(th => {
                                    htmlContent += `<th style="border: 1px solid #cbd5e1; background-color: #f1f5f9; padding: 8px 12px; font-weight: bold; text-align: left; color: #0f172a;">${th.innerHTML.trim()}</th>`;
                                });
                                htmlContent += `</tr>`;
                            });
                            htmlContent += `</thead>`;
                        }

                        const bodyRows = table.querySelectorAll('tbody tr');
                        htmlContent += `<tbody>`;
                        bodyRows.forEach((row, idx) => {
                            const isTotal = row.innerText.toLowerCase().includes('total');
                            const bg = isTotal ? 'background-color: #f8fafc; font-weight: bold;' : (idx % 2 === 0 ? 'background-color: #ffffff;' : 'background-color: #f8fafc;');
                            htmlContent += `<tr style="${bg}">`;
                            row.querySelectorAll('td').forEach(td => {
                                const fontWeight = isTotal ? 'font-weight: bold; color: #0f172a;' : 'color: #334155;';
                                htmlContent += `<td style="border: 1px solid #cbd5e1; padding: 8px 12px; text-align: left; ${fontWeight}">${td.innerHTML.trim()}</td>`;
                            });
                            htmlContent += `</tr>`;
                        });
                        htmlContent += `</tbody></table>`;

                        try {
                            const blobHtml = new Blob([htmlContent], { type: 'text/html' });
                            const blobText = new Blob([plainText], { type: 'text/plain' });
                            const item = new ClipboardItem({
                                'text/html': blobHtml,
                                'text/plain': blobText
                            });

                            navigator.clipboard.write([item]).then(() => {
                                if (btn) {
                                    const originalHtml = btn.innerHTML;
                                    btn.innerHTML = '<i class="fa-solid fa-check text-[10px] text-green-400"></i> Copied!';
                                    setTimeout(() => { btn.innerHTML = originalHtml; }, 2000);
                                }
                            }).catch(err => {
                                navigator.clipboard.writeText(plainText).then(() => {
                                    if (btn) {
                                        const originalHtml = btn.innerHTML;
                                        btn.innerHTML = '<i class="fa-solid fa-check text-[10px] text-green-400"></i> Copied!';
                                        setTimeout(() => { btn.innerHTML = originalHtml; }, 2000);
                                    }
                                });
                            });
                        } catch (e) {
                            navigator.clipboard.writeText(plainText);
                        }
                    };

                    window.exportTableToPng = function (containerId, title) {
                        const element = document.getElementById(containerId);
                        if (!element) return;

                        let loadingAlert = Swal.fire({
                            title: @js(__('Exporting Table...')),
                            text: @js(__('Generating ready-to-use PNG image. Please wait.')),
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        html2canvas(element, {
                            backgroundColor: '#ffffff',
                            scale: 2,
                            useCORS: true,
                            logging: false
                        }).then(canvas => {
                            const url = canvas.toDataURL('image/png');
                            const link = document.createElement('a');
                            link.download = `${title.replace(/[^a-z0-9]/gi, '_').toLowerCase()}_table.png`;
                            link.href = url;
                            link.click();
                            Swal.close();
                        }).catch(err => {
                            console.error("html2canvas error", err);
                            Swal.fire({
                                title: @js(__('Export Failed')),
                                text: @js(__('Could not generate the table image.')),
                                icon: 'error'
                            });
                        });
                    };

                    function formatShortCategoryTheme(rawText) {
                        if (!rawText || typeof rawText !== 'string') return 'Choices';
                        let str = rawText.trim();
                        if (!str) return 'Choices';

                        const originalText = str;

                        // Clean leading indices e.g. "1. ", "#1 ", "Q1: "
                        str = str.replace(/^(#|\bQ)?\d+[\.\:\)\s]+/i, '').trim();

                        // 1. Likert / Matrix separation e.g. "Indicate whether... - AMIS has improved..."
                        const separators = [' - ', ' -- ', ' — ', ' – ', ' : '];
                        for (const sep of separators) {
                            if (str.includes(sep)) {
                                const parts = str.split(sep);
                                const firstPartLower = parts[0].toLowerCase();
                                if (firstPartLower.includes('disagree') || firstPartLower.includes('agree') || firstPartLower.includes('rate') || firstPartLower.includes('indicate') || firstPartLower.includes('scale')) {
                                    str = parts.slice(1).join(sep).trim();
                                    break;
                                }
                            }
                        }

                        // 2. Specific Question Form Transformations:

                        // A) "How likely are you to [verb phrase]" -> "Likelihood to [verb phrase]"
                        if (/^how\s+likely\s+(are\s+you|is\s+it)\s+to\s+(.*)/i.test(str)) {
                            const verb = str.replace(/^how\s+likely\s+(are\s+you|is\s+it)\s+to\s+/i, '').replace(/[\?\:\.]+$|\s+$/g, '').trim();
                            str = `Likelihood to ${verb}`;
                        }
                        // B) "How many [noun] have you / do you / did you [verb] at/in/on [place]?"
                        // e.g. "How many years have you spent at this university?" -> "Years Spent at University"
                        else if (/^how\s+many\s+([a-z0-9\s]+?)\s+(have\s+you|do\s+you|did\s+you|are\s+you)\s+(spent|worked|studied|lived|been)\s+(at|in|on|with|for)\s+(this|the|a|an)?\s*(.*)/i.test(str)) {
                            str = str.replace(/^how\s+many\s+([a-z0-9\s]+?)\s+(have\s+you|do\s+you|did\s+you|are\s+you)\s+(spent|worked|studied|lived|been)\s+(at|in|on|with|for)\s+(this|the|a|an)?\s*(.*)/i, '$1 Spent $4 $6').trim();
                        }
                        // C) "How many [noun] do/have/did you [verb]..."
                        else if (/^how\s+many\s+([a-z0-9\s]+?)\s+(have\s+you|do\s+you|did\s+you|are\s+there|were\s+there)\s*(.*)/i.test(str)) {
                            const match = str.match(/^how\s+many\s+([a-z0-9\s]+?)\s+(have\s+you|do\s+you|did\s+you|are\s+there|were\s+there)\s*(.*)/i);
                            const noun = match[1].trim();
                            let rest = match[3].trim().replace(/[\?\:\.]+$|\s+$/g, '');
                            rest = rest.replace(/^(been|had|done|got|taken)\s+/i, '');
                            str = rest ? `${noun} ${rest}` : noun;
                        }
                        // D) "What type/kind/category/level of [noun] are you / do you / is ..."
                        // e.g. "What type of university are you currently working in or attending?" -> "Type of University"
                        else if (/^what\s+(type|kind|category|level|sort|form|class|sector|mode)\s+of\s+([a-z0-9\s]+?)\s+(are\s+you|do\s+you|have\s+you|is\s+|were\s+|did\s+).*/i.test(str)) {
                            const match = str.match(/^what\s+(type|kind|category|level|sort|form|class|sector|mode)\s+of\s+([a-z0-9\s]+?)\s+(are\s+you|do\s+you|have\s+you|is\s+|were\s+|did\s+).*/i);
                            const typeWord = match[1].trim();
                            const mainNoun = match[2].trim();
                            str = `${typeWord} of ${mainNoun}`;
                        }
                        // E) "What is your [noun]?" / "What are your [noun]?"
                        else if (/^what\s+(is|are)\s+(your|the)\s+([a-z0-9\s]+?)[\?\:\.]*$/i.test(str)) {
                            str = str.replace(/^what\s+(is|are)\s+(your|the)\s+/i, '');
                        }
                        // F) "How satisfied are you with [noun]?" -> "Satisfaction with [noun]"
                        else if (/^how\s+satisfied\s+are\s+you\s+(with|about)\s+(the|your)?\s*(.*)/i.test(str)) {
                            const item = str.replace(/^how\s+satisfied\s+are\s+you\s+(with|about)\s+(the|your)?\s*/i, '').replace(/[\?\:\.]+$|\s+$/g, '').trim();
                            str = `Satisfaction with ${item}`;
                        }
                        // G) General prompt prefix stripping:
                        else {
                            const prefixes = [
                                /^please\s+indicate\s+(whether\s+you\s+)?(strongly\s+disagree[^\-\:]*[\-\:])?\s*(your\s+|the\s+)?/i,
                                /^please\s+(specify|select|state|provide|rate|choose|enter)\s+(your\s+|the\s+)?/i,
                                /^indicate\s+(whether\s+you\s+)?(strongly\s+disagree[^\-\:]*[\-\:])?\s*(your\s+|the\s+)?/i,
                                /^(specify|select|state|provide|rate|choose|enter)\s+(your\s+|the\s+)?/i,
                                /^what\s+is\s+(your\s+|the\s+)?/i,
                                /^what\s+are\s+(your\s+|the\s+)?/i,
                                /^which\s+of\s+the\s+following\s+(best\s+describes\s+)?(your\s+|the\s+)?/i,
                                /^which\s+(category|option|one)\s+(best\s+describes\s+)?(your\s+|the\s+)?/i,
                                /^how\s+would\s+you\s+rate\s+(your\s+|the\s+)?/i,
                                /^how\s+satisfied\s+are\s+you\s+with\s+(your\s+|the\s+)?/i,
                                /^how\s+(often|long)\s+do\s+you\s+/i,
                                /^to\s+what\s+extent\s+(do\s+you\s+agree|do\s+you\s+feel)?\s*(that\s+)?(the\s+|your\s+)?/i,
                                /^do\s+you\s+agree\s+(or\s+disagree\s+)?(that\s+)?(the\s+|your\s+)?/i,
                                /^kindly\s+(indicate|state|specify|select)\s+(your\s+|the\s+)?/i
                            ];

                            for (const ptn of prefixes) {
                                if (ptn.test(str)) {
                                    str = str.replace(ptn, '').trim();
                                    break;
                                }
                            }
                        }

                        // Clean punctuation & trailing filler words
                        str = str.replace(/[\?\:\.]+$|\s+$/g, '').trim();
                        str = str.replace(/^(your|the|a|an)\s+/i, '').trim();

                        if (!str) return originalText;

                        // Capitalize Title Case
                        const words = str.split(/\s+/);
                        const formatted = words.map((w, idx) => {
                            if (!w) return '';
                            const lower = w.toLowerCase();
                            if (idx > 0 && ['of', 'in', 'at', 'on', 'for', 'to', 'with', 'and', 'or', 'a', 'an', 'the'].includes(lower)) {
                                return lower;
                            }
                            return w.charAt(0).toUpperCase() + w.slice(1);
                        }).join(' ');

                        return formatted.charAt(0).toUpperCase() + formatted.slice(1);
                    }

                    function wrapJsChartLabel(label, maxLen = 18) {
                        if (!label) return '';
                        if (typeof label !== 'string') return label;
                        if (label.length <= maxLen) return label;
                        return label.slice(0, maxLen - 3) + '...';
                    }

                    function createChart(canvasId, config, type = 'bar', colorTheme = 'indigo') {
                        const canvasElement = document.getElementById(canvasId);
                        if (!canvasElement) return;

                        if (chartInstances[canvasId]) {
                            chartInstances[canvasId].destroy();
                            delete chartInstances[canvasId];
                        }

                        const ctx = canvasElement.getContext('2d');

                        const activeTheme = colorTheme && colorPalettes[colorTheme] ? colorTheme : 'indigo';
                        const palette = colorPalettes[activeTheme] || colorPalettes['indigo'];
                        const colors = config.labels.map((_, i) => palette[i % palette.length]);
                        const primaryColor = palette[0];

                        let chartType = type;
                        let indexAxis = 'x';
                        let fill = false;

                        if (type === 'horizontal') {
                            chartType = 'bar';
                            indexAxis = 'y';
                        } else if (type === 'area') {
                            chartType = 'line';
                            fill = true;
                        }

                        // 1. Calculate overall responses sum to compute percentage values
                        const totalResponses = config.total_responses || config.data.reduce((sum, val) => sum + val, 0);

                        // Filter out missing and zero counts initially so only active non-zero responses are plotted
                        const validIndices = [];
                        (config.labels || []).forEach((lbl, idx) => {
                            const val = config.data[idx] || 0;
                            const isMissing = String(lbl).toLowerCase() === 'missing' || String(lbl).toLowerCase() === 'skipped';
                            if (!isMissing && val > 0) {
                                validIndices.push(idx);
                            }
                        });

                        const isFiltered = validIndices.length > 0 && validIndices.length < config.labels.length;
                        const activeLabels = isFiltered ? validIndices.map(i => config.labels[i]) : (validIndices.length === 0 && config.labels.length > 0 ? [] : config.labels);
                        const activeCounts = isFiltered ? validIndices.map(i => config.data[i]) : (validIndices.length === 0 && config.data.length > 0 ? [] : config.data);

                        const rawPercentageData = activeCounts.map(val =>
                            totalResponses > 0 ? parseFloat(((val / totalResponses) * 100).toFixed(1)) : 0
                        );

                        const isCategorical = ['pie', 'doughnut', 'polarArea', 'bar', 'horizontal'].includes(type);

                        // Determine spacer slots for bar charts to group bars tightly in the center
                        const origLabels = (activeLabels || []).map(lbl => wrapJsChartLabel(lbl, 25));
                        const origPerc = rawPercentageData;
                        const origCounts = activeCounts || [];
                        const origColors = activeLabels.map((_, i) => palette[i % palette.length]);

                        const canvasWrapper = canvasElement.closest('.chart-canvas-wrapper') || canvasElement.parentElement;
                        if (canvasWrapper && canvasWrapper.classList.contains('chart-canvas-wrapper')) {
                            if (type === 'bar') {
                                const catCount = origLabels.length;
                                if (catCount <= 5) {
                                    canvasWrapper.style.maxWidth = `${Math.min(800, Math.max(340, catCount * 130 + 80))}px`;
                                } else {
                                    canvasWrapper.style.maxWidth = '100%';
                                }
                            } else if (['pie', 'doughnut', 'polarArea', 'radar'].includes(type)) {
                                canvasWrapper.style.maxWidth = '420px';
                            } else {
                                canvasWrapper.style.maxWidth = '100%';
                            }
                        }

                        let finalLabels = origLabels;
                        let finalPerc = origPerc;
                        let finalCounts = origCounts;
                        let finalColors = origColors;

                        // 2. Custom inline plugin to render frequency and percentage above elements
                        const datalabelsPlugin = {
                            id: 'customDatalabels',
                            afterDatasetsDraw(chart) {
                                const { ctx } = chart;
                                ctx.save();
                                chart.data.datasets.forEach((dataset, i) => {
                                    const meta = chart.getDatasetMeta(i);
                                    meta.data.forEach((element, index) => {
                                        const rawVal = dataset.data[index];
                                        if (rawVal === null || rawVal === undefined || isNaN(rawVal) || rawVal === '') return;
                                        const numVal = (typeof rawVal === 'number') ? rawVal : parseFloat(String(rawVal || 0).replace('%', ''));
                                        if (isNaN(numVal)) return;
                                        const text = `${numVal % 1 === 0 ? numVal.toFixed(0) : numVal.toFixed(1)}%`;

                                        ctx.fillStyle = '#0f172a';
                                        ctx.font = 'bold 11px Inter, system-ui, -apple-system, sans-serif';

                                        if (chart.options.indexAxis === 'y') {
                                            ctx.textAlign = 'left';
                                            ctx.textBaseline = 'middle';
                                            ctx.fillText(text, element.x + 6, element.y);
                                        } else {
                                            ctx.textAlign = 'center';
                                            ctx.textBaseline = 'bottom';
                                            ctx.fillText(text, element.x, element.y - 6);
                                        }
                                    });
                                });
                                ctx.restore();
                            }
                        };

                        const chartConfig = {
                            type: chartType,
                            data: {
                                labels: finalLabels,
                                datasets: [{
                                    label: 'Responses (%)',
                                    data: finalPerc, // Y-axis uses percentages
                                    backgroundColor: isCategorical ? finalColors : (fill ? `${primaryColor}44` : primaryColor),
                                    borderColor: isCategorical ? (type === 'bar' || type === 'horizontal' ? finalColors : '#fff') : primaryColor,
                                    borderWidth: (type === 'line' || type === 'radar' || type === 'area') ? 3 : 1,
                                    categoryPercentage: 0.75,
                                    barPercentage: 0.85,
                                    maxBarThickness: 45,
                                    fill: fill,
                                    borderRadius: (chartType === 'bar') ? 6 : 0,
                                    tension: 0.4,
                                    pointBackgroundColor: primaryColor,
                                    pointRadius: 4
                                }]
                            },
                            options: {
                                devicePixelRatio: Math.max(window.devicePixelRatio || 1, 2.5),
                                indexAxis: indexAxis,
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: ['pie', 'doughnut', 'polarArea', 'radar'].includes(type),
                                        position: 'bottom',
                                        labels: {
                                            boxWidth: 10,
                                            padding: 15,
                                            font: { weight: '800', size: 10, family: 'Inter, sans-serif' },
                                            color: '#0f172a',
                                            usePointStyle: true
                                        }
                                    },
                                    tooltip: {
                                        backgroundColor: '#0f172a',
                                        padding: 12,
                                        titleFont: { size: 12, weight: '800' },
                                        bodyFont: { size: 12, weight: '600' },
                                        cornerRadius: 12,
                                        displayColors: true,
                                        filter: function (tooltipItem) {
                                            return tooltipItem.raw !== null && tooltipItem.raw !== undefined;
                                        },
                                        callbacks: {
                                            label: function (context) {
                                                const index = context.dataIndex;
                                                const rawVal = finalCounts[index];
                                                if (rawVal === null || rawVal === undefined) return '';
                                                return ` ${rawVal} (${context.raw}%)`;
                                            }
                                        }
                                    }
                                }
                            }
                        };

                        // Add the custom inline text labels plugin for bar & line layouts
                        if (chartType === 'bar' || chartType === 'line') {
                            chartConfig.plugins = [datalabelsPlugin];
                        }

                        if (chartType === 'bar' || chartType === 'line') {
                            const isHorizontal = indexAxis === 'y';
                            const validPerc = finalPerc.filter(v => v !== null && v !== undefined);
                            const maxPct = Math.max(...validPerc, 10);
                            const ySuggestedMax = Math.min(100, Math.ceil(maxPct * 1.15));

                            const valueAxisConfig = {
                                beginAtZero: true,
                                suggestedMax: ySuggestedMax,
                                grace: '10%',
                                grid: { color: '#e2e8f0', drawBorder: false },
                                ticks: {
                                    font: { weight: '700', size: 11, family: 'Inter, sans-serif' },
                                    color: '#1e293b',
                                    callback: function (value) {
                                        return value + '%';
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Percentage (%)',
                                    color: '#0f172a',
                                    font: { weight: '800', size: 12, family: 'Inter, sans-serif' }
                                }
                            };
                            const labelAxisConfig = {
                                grid: { display: false },
                                ticks: {
                                    font: { weight: '700', size: 11, family: 'Inter, sans-serif' },
                                    color: '#1e293b',
                                    maxRotation: 45,
                                    minRotation: 0,
                                    autoSkip: false
                                },
                                title: {
                                    display: true,
                                    text: config.short_theme || formatShortCategoryTheme(config.question_name) || 'Choices',
                                    color: '#0f172a',
                                    font: { weight: '800', size: 12, family: 'Inter, sans-serif' }
                                }
                            };

                            chartConfig.options.scales = {
                                y: isHorizontal ? labelAxisConfig : valueAxisConfig,
                                x: isHorizontal ? valueAxisConfig : labelAxisConfig
                            };
                        }

                        if (type === 'radar') {
                            chartConfig.options.scales = {
                                r: {
                                    grid: { color: '#e2e8f0' },
                                    angleLines: { color: '#e2e8f0' },
                                    pointLabels: { font: { weight: '800', size: 11, family: 'Inter, sans-serif' }, color: '#0f172a' },
                                    ticks: { display: false }
                                }
                            };
                        }

                        return new Chart(ctx, chartConfig);
                    }

                    window.formatP = function (val) {
                        if (val === null || val === undefined || val === '') return '-';
                        let num = parseFloat(val);
                        if (isNaN(num)) return val;
                        if (num < 0.001) return '< .001';
                        let s = num.toFixed(3);
                        return s.startsWith('0.') ? s.substring(1) : (s.startsWith('-0.') ? '-' + s.substring(2) : s);
                    };

                    window.formatDec = function (val, decimals = 3, dropLeadingZero = false) {
                        if (val === null || val === undefined || val === '') return '-';
                        let num = parseFloat(val);
                        if (isNaN(num)) return val;
                        let s = num.toFixed(decimals);
                        if (dropLeadingZero) {
                            return s.startsWith('0.') ? s.substring(1) : (s.startsWith('-0.') ? '-' + s.substring(2) : s);
                        }
                        return s;
                    };

                    window.wrapAxisTitle = function (lbl, maxCharsPerLine = 35, maxLines = 2) {
                        if (!lbl) return '';
                        let str = String(lbl).trim();
                        if (str.includes(' - ')) {
                            const parts = str.split(' - ');
                            str = parts[parts.length - 1].trim();
                        }
                        const words = str.split(/\s+/);
                        const lines = [];
                        let currentLine = '';

                        for (let word of words) {
                            if (!currentLine) {
                                currentLine = word;
                            } else if ((currentLine + ' ' + word).length <= maxCharsPerLine) {
                                currentLine += ' ' + word;
                            } else {
                                lines.push(currentLine);
                                currentLine = word;
                                if (lines.length === maxLines - 1) {
                                    break;
                                }
                            }
                        }
                        if (currentLine && lines.length < maxLines) {
                            lines.push(currentLine);
                        }
                        return lines.length === 1 ? lines[0] : lines;
                    };

                    window.formatRegressionEquation = function (data) {
                        if (!data) return '';
                        if (data.equation && !data.equation.includes('...') && !data.equation.includes('[')) {
                            return data.equation;
                        }

                        let depCode = 'Y';
                        const depLbl = data.shortDepLabel || data.depLabel || '';
                        const mDep = depLbl.match(/^(Q\d+(\.\d+)?)/i);
                        if (mDep) {
                            depCode = mDep[1];
                        }

                        const interceptObj = (data.coefficients || []).find(c => c.variable === '(Constant)');
                        const b0 = interceptObj ? (parseFloat(interceptObj.coef) || 0).toFixed(4) : '0';

                        let eq = `ŷ(${depCode}) = ${b0}`;
                        const predictors = (data.coefficients || []).filter(c => c.variable !== '(Constant)');

                        predictors.forEach((coef, idx) => {
                            const val = parseFloat(coef.coef) || 0;
                            const sign = val >= 0 ? ' + ' : ' - ';
                            const absVal = Math.abs(val).toFixed(4);

                            let code = 'X' + (idx + 1);
                            const lbl = coef.short_label || coef.label || '';
                            const m = lbl.match(/^(Q\d+(\.\d+)?)/i);
                            if (m) {
                                code = m[1];
                            } else if (data.equationKey && data.equationKey[idx] && data.equationKey[idx].code) {
                                code = data.equationKey[idx].code;
                            }
                            eq += `${sign}${absVal}(${code})`;
                        });

                        return eq;
                    };

                    window.getEquationKey = function (data) {
                        if (!data) return [];
                        if (data.equationKey && Array.isArray(data.equationKey) && data.equationKey.length > 0) {
                            return data.equationKey;
                        }
                        const predictors = (data.coefficients || []).filter(c => c.variable !== '(Constant)');
                        return predictors.map((coef, idx) => {
                            let code = 'X' + (idx + 1);
                            const lbl = coef.short_label || coef.label || '';
                            const m = lbl.match(/^(Q\d+(\.\d+)?)/i);
                            if (m) {
                                code = m[1];
                            }
                            return {
                                code: code,
                                label: lbl,
                                short_label: coef.short_label || lbl
                            };
                        });
                    };

                    window.renderAnovaMeansPlot = function (container, data) {
                        if (!container || !data || !data.groupStats) return;
                        const canvas = container.querySelector('canvas');
                        if (!canvas) return;

                        if (canvas._chartInstance) {
                            canvas._chartInstance.destroy();
                            canvas._chartInstance = null;
                        }

                        const labels = data.groupStats.map(g => wrapAxisTitle(g.name, 25, 2));
                        const means = data.groupStats.map(g => Number(g.mean));
                        const ciLowers = data.groupStats.map(g => Number(g.ciLower));
                        const ciUppers = data.groupStats.map(g => Number(g.ciUpper));

                        const errorBarPlugin = {
                            id: 'errorBars',
                            afterDatasetsDraw(chart) {
                                const ctx = chart.ctx;
                                const meta = chart.getDatasetMeta(0);
                                const yScale = chart.scales.y;
                                ctx.save();
                                ctx.strokeStyle = '#2271b1';
                                ctx.lineWidth = 2;

                                meta.data.forEach((point, idx) => {
                                    const x = point.x;
                                    const yLow = yScale.getPixelForValue(ciLowers[idx]);
                                    const yHigh = yScale.getPixelForValue(ciUppers[idx]);
                                    const capWidth = 5;

                                    ctx.beginPath();
                                    ctx.moveTo(x, yLow);
                                    ctx.lineTo(x, yHigh);
                                    ctx.stroke();

                                    ctx.beginPath();
                                    ctx.moveTo(x - capWidth, yHigh);
                                    ctx.lineTo(x + capWidth, yHigh);
                                    ctx.stroke();

                                    ctx.beginPath();
                                    ctx.moveTo(x - capWidth, yLow);
                                    ctx.lineTo(x + capWidth, yLow);
                                    ctx.stroke();
                                });
                                ctx.restore();
                            }
                        };

                        const rawDepLabel = data.shortDepLabel || data.depLabel || 'Mean Score';
                        const yAxisTitle = wrapAxisTitle(rawDepLabel, 30, 2);

                        canvas._chartInstance = new Chart(canvas.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Group Mean',
                                    data: means,
                                    borderColor: '#2271b1',
                                    backgroundColor: '#2271b1',
                                    borderWidth: 2.5,
                                    pointBackgroundColor: '#2271b1',
                                    pointBorderColor: '#ffffff',
                                    pointBorderWidth: 2,
                                    pointRadius: 6,
                                    pointHoverRadius: 8,
                                    tension: 0,
                                    fill: false
                                }]
                            },
                            plugins: [errorBarPlugin],
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                layout: {
                                    padding: { left: 25, right: 20, top: 15, bottom: 20 }
                                },
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            label: function (ctx) {
                                                const idx = ctx.dataIndex;
                                                return [
                                                    `Mean: ${means[idx].toFixed(4)}`,
                                                    `95% CI: [${ciLowers[idx].toFixed(4)}, ${ciUppers[idx].toFixed(4)}]`
                                                ];
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        grid: { display: false },
                                        ticks: { font: { weight: '600', size: 11, family: 'Inter, sans-serif' }, color: '#334155' }
                                    },
                                    y: {
                                        grid: { color: '#f1f5f9' },
                                        ticks: { font: { weight: '600', size: 11, family: 'Inter, sans-serif' }, color: '#334155' },
                                        title: {
                                            display: true,
                                            text: yAxisTitle,
                                            color: '#0f172a',
                                            font: { weight: '700', size: 12, family: 'Inter, sans-serif' },
                                            padding: { bottom: 10 }
                                        }
                                    }
                                }
                            }
                        });
                    };

                    window.renderCorrelationScatter = function (container, data) {
                        if (!container || !data) return;
                        const canvas = container.querySelector('canvas');
                        if (!canvas) return;

                        if (canvas._chartInstance) {
                            canvas._chartInstance.destroy();
                            canvas._chartInstance = null;
                        }

                        const isDiscrete = (data.scatterPoints || []).every(p => Number.isInteger(Number(p.x)) && Number.isInteger(Number(p.y)));
                        const scatterPoints = (data.scatterPoints || []).map((p, idx) => {
                            const rawX = Number(p.x);
                            const rawY = Number(p.y);
                            const jitterX = isDiscrete ? ((((idx * 17 + 5) % 100) / 100) - 0.5) * 0.12 : 0;
                            const jitterY = isDiscrete ? ((((idx * 23 + 11) % 100) / 100) - 0.5) * 0.12 : 0;
                            return {
                                x: rawX + jitterX,
                                y: rawY + jitterY,
                                rawX: rawX,
                                rawY: rawY
                            };
                        });

                        const datasets = [
                            {
                                type: 'scatter',
                                label: 'Sample Data',
                                data: scatterPoints,
                                backgroundColor: 'rgba(34, 113, 177, 0.7)',
                                borderColor: '#2271b1',
                                borderWidth: 1,
                                pointRadius: 4.5,
                                pointHoverRadius: 6.5
                            }
                        ];

                        const trendPoints = Array.isArray(data.trendline)
                            ? data.trendline
                            : (data.trendline && data.trendline.x1 !== undefined
                                ? [{ x: data.trendline.x1, y: data.trendline.y1 }, { x: data.trendline.x2, y: data.trendline.y2 }]
                                : []);

                        if (trendPoints.length > 0) {
                            datasets.push({
                                type: 'line',
                                label: 'Linear Fit Line (ŷ = β₀ + β₁X)',
                                data: trendPoints.map(p => ({ x: Number(p.x), y: Number(p.y) })),
                                borderColor: '#2271b1',
                                backgroundColor: '#2271b1',
                                borderWidth: 2.5,
                                pointRadius: 0,
                                fill: false,
                                tension: 0
                            });
                        }

                        const rawX = data.shortVarXLabel || data.shortIndLabel || data.varXLabel || data.indLabel || 'Independent Variable (X)';
                        const rawY = data.shortVarYLabel || data.shortDepLabel || data.varYLabel || data.depLabel || 'Dependent Variable (Y)';
                        const xLabel = wrapAxisTitle(rawX, 38, 2);
                        const yLabel = wrapAxisTitle(rawY, 28, 2);

                        canvas._chartInstance = new Chart(canvas.getContext('2d'), {
                            type: 'scatter',
                            data: { datasets },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                layout: {
                                    padding: { left: 30, right: 20, top: 15, bottom: 25 }
                                },
                                plugins: {
                                    legend: {
                                        position: 'top',
                                        labels: { boxWidth: 12, font: { family: 'Inter, sans-serif', size: 11, weight: '600' } }
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function (ctx) {
                                                if (ctx.dataset.type === 'line') return 'Trendline';
                                                return `(${ctx.parsed.x.toFixed(2)}, ${ctx.parsed.y.toFixed(2)})`;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        type: 'linear',
                                        position: 'bottom',
                                        title: {
                                            display: true,
                                            text: xLabel,
                                            font: { family: 'Inter, sans-serif', size: 11, weight: '700' },
                                            color: '#334155',
                                            padding: { top: 10 }
                                        },
                                        grid: { color: '#f1f5f9' },
                                        ticks: { font: { weight: '600', size: 10, family: 'Inter, sans-serif' }, color: '#475569' }
                                    },
                                    y: {
                                        type: 'linear',
                                        title: {
                                            display: true,
                                            text: yLabel,
                                            font: { family: 'Inter, sans-serif', size: 11, weight: '700' },
                                            color: '#334155',
                                            padding: { bottom: 10 }
                                        },
                                        grid: { color: '#f1f5f9' },
                                        ticks: { font: { weight: '600', size: 10, family: 'Inter, sans-serif' }, color: '#475569' }
                                    }
                                }
                            }
                        });
                    };

                    window.inferentialManager = function (savedTestsInit = []) {
                        return {
                            formatP(val) {
                                return window.formatP(val);
                            },
                            formatDec(val, decimals = 3, dropLeadingZero = false) {
                                return window.formatDec(val, decimals, dropLeadingZero);
                            },
                            savedTests: savedTestsInit,
                            loadedTestId: null,
                            isSaving: false,
                            sidebarOpen: false,

                            userSurveys: @json($userSurveys ?? []),
                            questionsMap: @json($questionsMap ?? []),
                            scaleItemsMap: @json($scaleItemsMap ?? []),
                            getQuestionInfo(id) {
                                if (!id) return null;
                                const key = String(id);
                                return this.questionsMap[key] || this.scaleItemsMap[key] || null;
                            },
                            scope: 'within',
                            targetSurveyId: '',
                            targetDepVar: '',
                            targetSurveyIds: [],
                            anovaTargetSurveys: [{ survey_id: '', dep: '' }],
                            addAnovaTargetSurvey() {
                                this.anovaTargetSurveys.push({ survey_id: '', dep: '' });
                            },
                            removeAnovaTargetSurvey(index) {
                                if (this.anovaTargetSurveys.length > 1) {
                                    this.anovaTargetSurveys.splice(index, 1);
                                }
                            },
                            uploadedDataLabel: 'National Benchmark (2025)',
                            uploadedDataValues: [],
                            rawUploadedText: '',
                            uploadedFileName: '',

                            testMethod: 'crosstab',
                            rowVar: '',
                            colVar: '',
                            depVar: '',
                            groupVar: '',
                            ttestGroup1: '',
                            ttestGroup2: '',
                            varX: '',
                            varY: '',
                            indVars: [],
                            cronbachItems: [],
                            loading: false,
                            analysisError: null,
                            aiLoading: false,
                            aiInsight: null,
                            aiFeedback: '',
                            aiPolishing: false,
                            aiMessages: [],

                            matrixData: null,
                            chisquareData: null,
                            cronbachData: null,
                            tTestData: null,
                            anovaData: null,
                            correlationData: null,
                            regressionData: null,
                            multipleRegressionData: null,

                            isRunDisabled() {
                                if (this.testMethod === 'crosstab' || this.testMethod === 'chisquare') {
                                    return !this.rowVar || !this.colVar;
                                }
                                if (this.testMethod === 'correlation') {
                                    return !this.varX || !this.varY;
                                }
                                if (this.testMethod === 'ttest') {
                                    if (this.scope === 'within') {
                                        return !this.depVar || !this.groupVar;
                                    }
                                    if (this.scope === 'cross_survey') {
                                        return !this.depVar || !this.targetSurveyId || !this.targetDepVar;
                                    }
                                    if (this.scope === 'upload') {
                                        return !this.depVar || this.uploadedDataValues.length === 0;
                                    }
                                }
                                if (this.testMethod === 'anova') {
                                    if (this.scope === 'within') {
                                        return !this.depVar || !this.groupVar;
                                    }
                                    if (this.scope === 'cross_survey') {
                                        return !this.depVar || (this.anovaTargetSurveys || []).some(t => !t.survey_id || !t.dep);
                                    }
                                }
                                if (this.testMethod === 'regression') {
                                    return !this.depVar || !this.groupVar;
                                }
                                if (this.testMethod === 'regression_multiple') {
                                    return !this.depVar || !this.indVars || this.indVars.length === 0;
                                }
                                if (this.testMethod === 'cronbach') {
                                    return !this.cronbachItems || this.cronbachItems.length < 2;
                                }
                                return false;
                            },
                            init() {
                                this.$watch('depVar', (val) => {
                                    if (val && this.indVars && this.indVars.includes(val)) {
                                        this.indVars = this.indVars.filter(v => v !== val);
                                    }
                                });
                                this.$watch('testMethod', () => {
                                    if (!this.loadedTestId) this.clearResults();
                                });
                                this.$watch('groupVar', () => {
                                    this.syncTTestGroups();
                                });
                                this.$watch('anovaData', (val) => {
                                    if (val && this.testMethod === 'anova') {
                                        this.$nextTick(() => {
                                            const el = document.getElementById('anova-means-chart-container');
                                            if (el && window.renderAnovaMeansPlot) {
                                                window.renderAnovaMeansPlot(el, val);
                                            }
                                        });
                                    }
                                });
                            },

                            syncTTestGroups() {
                                if (!this.groupVar) {
                                    this.ttestGroup1 = '';
                                    this.ttestGroup2 = '';
                                    return;
                                }
                                const qInfo = this.getQuestionInfo(this.groupVar);
                                const opts = qInfo && qInfo.category_options ? qInfo.category_options : [];
                                const cats = qInfo && qInfo.categories ? qInfo.categories : [];
                                if (opts.length >= 2) {
                                    this.ttestGroup1 = opts[0].value;
                                    this.ttestGroup2 = opts[1].value;
                                } else if (cats.length >= 2) {
                                    this.ttestGroup1 = cats[0];
                                    this.ttestGroup2 = cats[1];
                                } else {
                                    this.ttestGroup1 = '';
                                    this.ttestGroup2 = '';
                                }
                            },

                            getTargetQuestions(surveyId) {
                                if (!surveyId) return [];
                                const s = this.userSurveys.find(item => item.id == surveyId);
                                if (!s || !s.questions) return [];
                                return s.questions.filter(q => {
                                    const t = (q.type || '').toLowerCase();
                                    return q.isScaleItem || t === 'likert_item' || ['number', 'rating', 'scale', 'slider', 'range', 'star_rating', 'nps', 'likert'].includes(t);
                                });
                            },

                            getTargetQuestionInfo(surveyId, qId) {
                                if (!surveyId || !qId) return null;
                                const list = this.getTargetQuestions(surveyId);
                                return list.find(q => q.id === qId) || null;
                            },

                            getTargetGroupedQuestions(surveyId) {
                                const list = this.getTargetQuestions(surveyId);
                                const groups = {};
                                const standalones = [];
                                list.forEach(q => {
                                    if (q.optgroup) {
                                        if (!groups[q.optgroup]) {
                                            groups[q.optgroup] = [];
                                        }
                                        groups[q.optgroup].push(q);
                                    } else {
                                        standalones.push(q);
                                    }
                                });
                                return { groups, standalones };
                            },

                            getTargetQuestionsHtml(surveyId, selectedVal = '') {
                                if (!surveyId) return '<option value="">{{ __("Select Survey First...") }}</option>';
                                const grouped = this.getTargetGroupedQuestions(surveyId);
                                let html = '<option value="">{{ __("Select Matching Question...") }}</option>';
                                if (grouped.groups && Object.keys(grouped.groups).length > 0) {
                                    for (const [groupLabel, items] of Object.entries(grouped.groups)) {
                                        const cleanGrp = String(groupLabel).replace(/"/g, '&quot;');
                                        html += `<optgroup label="${cleanGrp}">`;
                                        for (const q of items) {
                                            const sel = String(q.id) === String(selectedVal) ? ' selected' : '';
                                            const disp = String(q.display_label || q.label).replace(/</g, '&lt;').replace(/>/g, '&gt;');
                                            html += `<option value="${q.id}"${sel}>${disp}</option>`;
                                        }
                                        html += '</optgroup>';
                                    }
                                }
                                if (grouped.standalones && grouped.standalones.length > 0) {
                                    for (const q of grouped.standalones) {
                                        const sel = String(q.id) === String(selectedVal) ? ' selected' : '';
                                        const disp = String(q.display_label || q.label).replace(/</g, '&lt;').replace(/>/g, '&gt;');
                                        html += `<option value="${q.id}"${sel}>${disp}</option>`;
                                    }
                                }
                                return html;
                            },

                            formatVarCode(id) {
                                if (!id) return '';
                                const info = this.getQuestionInfo(id);
                                if (!info) return String(id);
                                if (info.display_label) {
                                    const m = info.display_label.match(/^(Q\d+(\.\d+)?)/i);
                                    if (m) return m[1];
                                }
                                if (info.code) return info.code;
                                const sub = info.sub_label || info.label || String(id);
                                const m2 = sub.match(/^(Q\d+(\.\d+)?)/i);
                                if (m2) return m2[1];
                                return sub.length > 15 ? sub.substring(0, 12) + '...' : sub;
                            },

                            formatTestVariables(test) {
                                if (!test) return '';
                                if (test.variables_formatted) return test.variables_formatted;
                                if (!test.variables) return '';
                                // If variables contains raw ids, format them nicely
                                return test.variables.split(',').map(part => {
                                    const trimmed = part.trim();
                                    const [k, v] = trimmed.split(':');
                                    if (v) {
                                        const keyTrim = k.trim();
                                        const valTrim = v.trim();
                                        if (['Dep', 'Grp', 'Ind', 'Row', 'Col', 'X', 'Y'].includes(keyTrim)) {
                                            return keyTrim + ': ' + this.formatVarCode(valTrim);
                                        }
                                        if (keyTrim === 'Inds') {
                                            const codes = valTrim.split(',').map(subId => this.formatVarCode(subId.trim()));
                                            return 'Inds: ' + codes.join(', ');
                                        }
                                    }
                                    return trimmed;
                                }).join(' | ');
                            },

                            handleFileUpload(e) {
                                const file = e.target.files[0];
                                if (!file) return;
                                this.uploadedFileName = file.name;
                                const isExcel = /\.(xlsx|xls)$/i.test(file.name);

                                if (isExcel && typeof XLSX !== 'undefined') {
                                    const reader = new FileReader();
                                    reader.onload = (evt) => {
                                        try {
                                            const data = new Uint8Array(evt.target.result);
                                            const workbook = XLSX.read(data, { type: 'array' });
                                            const firstSheetName = workbook.SheetNames[0];
                                            const worksheet = workbook.Sheets[firstSheetName];
                                            const json = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
                                            const numbers = [];
                                            json.forEach(row => {
                                                if (Array.isArray(row)) {
                                                    row.forEach(cell => {
                                                        if (typeof cell === 'number' && !isNaN(cell)) {
                                                            numbers.push(cell);
                                                        } else if (typeof cell === 'string') {
                                                            const trimmed = cell.trim().replace(/^["']|["']$/g, '');
                                                            if (trimmed !== '' && !isNaN(Number(trimmed))) {
                                                                numbers.push(Number(trimmed));
                                                            }
                                                        }
                                                    });
                                                }
                                            });
                                            this.uploadedDataValues = numbers;
                                            this.rawUploadedText = numbers.length > 0 ? numbers.join('\n') : '';
                                        } catch (err) {
                                            console.error("Failed to parse Excel file:", err);
                                            this.uploadedDataValues = [];
                                            this.rawUploadedText = '';
                                        }
                                    };
                                    reader.readAsArrayBuffer(file);
                                } else {
                                    const reader = new FileReader();
                                    reader.onload = (evt) => {
                                        let text = evt.target.result;
                                        if (typeof text !== 'string') text = '';
                                        // If binary zip header detected (e.g. xlsx uploaded as csv)
                                        if (text.startsWith('PK\x03\x04') || text.includes('\x00')) {
                                            if (typeof XLSX !== 'undefined') {
                                                const abReader = new FileReader();
                                                abReader.onload = (abEvt) => {
                                                    try {
                                                        const data = new Uint8Array(abEvt.target.result);
                                                        const workbook = XLSX.read(data, { type: 'array' });
                                                        const ws = workbook.Sheets[workbook.SheetNames[0]];
                                                        const json = XLSX.utils.sheet_to_json(ws, { header: 1 });
                                                        const nums = [];
                                                        json.forEach(r => Array.isArray(r) && r.forEach(c => {
                                                            const n = Number(String(c).trim());
                                                            if (!isNaN(n) && String(c).trim() !== '') nums.push(n);
                                                        }));
                                                        this.uploadedDataValues = nums;
                                                        this.rawUploadedText = nums.join('\n');
                                                    } catch (e) { }
                                                };
                                                abReader.readAsArrayBuffer(file);
                                                return;
                                            }
                                        }
                                        const tokens = text.split(/[\r\n,;\t]+/);
                                        const numbers = [];
                                        tokens.forEach(tok => {
                                            const cleaned = tok.trim().replace(/^["']|["']$/g, '');
                                            if (cleaned !== '' && !isNaN(Number(cleaned))) {
                                                numbers.push(Number(cleaned));
                                            }
                                        });
                                        this.uploadedDataValues = numbers;
                                        this.rawUploadedText = numbers.length > 0 ? numbers.join('\n') : text;
                                    };
                                    reader.readAsText(file);
                                }
                            },

                            parseUploadedText() {
                                if (!this.rawUploadedText) {
                                    this.uploadedDataValues = [];
                                    return;
                                }
                                const tokens = this.rawUploadedText.split(/[\r\n,;\t\s]+/);
                                const numbers = [];
                                tokens.forEach(tok => {
                                    const cleaned = tok.trim().replace(/^["']|["']$/g, '');
                                    if (cleaned !== '' && !isNaN(Number(cleaned))) {
                                        numbers.push(Number(cleaned));
                                    }
                                });
                                this.uploadedDataValues = numbers;
                            },

                            toggleGroupItems(itemIds) {
                                if (!itemIds || !itemIds.length) return;
                                const strIds = itemIds.map(String);
                                const allSelected = strIds.every(id => this.cronbachItems.map(String).includes(id));
                                if (allSelected) {
                                    this.cronbachItems = this.cronbachItems.filter(id => !strIds.includes(String(id)));
                                } else {
                                    const set = new Set(this.cronbachItems.map(String));
                                    strIds.forEach(id => set.add(id));
                                    this.cronbachItems = Array.from(set);
                                }
                            },

                            isGroupAllSelected(itemIds) {
                                if (!itemIds || !itemIds.length) return false;
                                const currentSelected = this.cronbachItems.map(String);
                                return itemIds.map(String).every(id => currentSelected.includes(id));
                            },

                            selectAllScaleItems(allIds) {
                                if (!allIds || !allIds.length) return;
                                this.cronbachItems = Array.from(new Set(allIds.map(String)));
                            },

                            clearScaleItems() {
                                this.cronbachItems = [];
                            },

                            activeCorrInspect: null,
                            inspectCorrelation(rowItem, colItem, rVal) {
                                if (!rowItem || !colItem) return;
                                if (this.activeCorrInspect && this.activeCorrInspect.rowKey === rowItem.item_key && this.activeCorrInspect.colKey === colItem.item_key) {
                                    this.activeCorrInspect = null;
                                    return;
                                }
                                this.activeCorrInspect = {
                                    rowKey: rowItem.item_key,
                                    colKey: colItem.item_key,
                                    rowShort: rowItem.short_label || 'Item',
                                    colShort: colItem.short_label || 'Item',
                                    rowLabel: rowItem.label || rowItem.item_key,
                                    colLabel: colItem.label || colItem.item_key,
                                    r: rVal !== undefined && rVal !== null ? this.formatDec(rVal, 3, true) : '—',
                                    isDiag: rowItem.item_key === colItem.item_key
                                };
                            },
                            clearCorrInspect() {
                                this.activeCorrInspect = null;
                            },

                            resetForm() {
                                this.loadedTestId = null;
                                this.clearResults();
                                this.testMethod = 'crosstab';
                                this.scope = 'within';
                                this.targetSurveyId = '';
                                this.targetDepVar = '';
                                this.targetSurveyIds = [];
                                this.uploadedDataValues = [];
                                this.rawUploadedText = '';
                                this.uploadedFileName = '';
                                this.ttestGroup1 = '';
                                this.ttestGroup2 = '';
                            },

                            clearResults() {
                                this.matrixData = null;
                                this.chisquareData = null;
                                this.cronbachData = null;
                                this.tTestData = null;
                                this.anovaData = null;
                                this.correlationData = null;
                                this.regressionData = null;
                                this.multipleRegressionData = null;
                                this.aiInsight = null;
                                this.aiFeedback = '';
                                this.aiMessages = [];
                                this.loading = false;
                                this.aiLoading = false;
                                this.aiPolishing = false;
                            },

                            formatMethod(method) {
                                const names = {
                                    'crosstab': 'Cross-Tab',
                                    'chisquare': 'Chi-Square',
                                    'cronbach': 'Cronbach α',
                                    'ttest': 'T-Test',
                                    'anova': 'ANOVA',
                                    'correlation': 'Correlation',
                                    'regression': 'Regression',
                                    'regression_multiple': 'Multiple Reg.'
                                };
                                return names[method] || method;
                            },

                            async runAnalysis() {
                                this.clearResults();
                                this.loading = true;

                                let url = `{{ route('surveys.reports.inferential', $survey) }}`;
                                let body = {
                                    method: this.testMethod,
                                    scope: this.scope
                                };

                                if (this.testMethod === 'crosstab' || this.testMethod === 'chisquare') {
                                    if (!this.rowVar || !this.colVar) return this.loading = false;
                                    body.row = this.rowVar;
                                    body.col = this.colVar;
                                } else if (this.testMethod === 'cronbach') {
                                    if (this.cronbachItems.length < 2) {
                                        alert('Please select at least 2 questions for Cronbach Alpha reliability analysis.');
                                        return this.loading = false;
                                    }
                                    body.items = this.cronbachItems.join(',');
                                } else if (this.testMethod === 'ttest') {
                                    if (!this.depVar) return this.loading = false;
                                    body.dep = this.depVar;
                                    if (this.scope === 'cross_survey') {
                                        if (!this.targetSurveyId) {
                                            alert('Please select a target comparison survey.');
                                            return this.loading = false;
                                        }
                                        body.target_survey_id = this.targetSurveyId;
                                        body.target_dep = this.targetDepVar || this.depVar;
                                    } else if (this.scope === 'upload') {
                                        if (this.uploadedDataValues.length < 2) {
                                            alert('Please upload or enter at least 2 numeric values for the external dataset.');
                                            return this.loading = false;
                                        }
                                        body.dataset_label = this.uploadedDataLabel || 'External Dataset';
                                        body.dataset_values = this.uploadedDataValues;
                                    } else {
                                        if (!this.groupVar) return this.loading = false;
                                        body.group = this.groupVar;
                                        if (this.ttestGroup1 && this.ttestGroup2) {
                                            body.group1 = this.ttestGroup1;
                                            body.group2 = this.ttestGroup2;
                                        }
                                    }
                                } else if (this.testMethod === 'anova') {
                                    if (!this.depVar) return this.loading = false;
                                    body.dep = this.depVar;
                                    if (this.scope === 'cross_survey') {
                                        if (!this.targetSurveyId && (!this.targetSurveyIds || this.targetSurveyIds.length < 1)) {
                                            alert('Please select a target comparison survey.');
                                            return this.loading = false;
                                        }
                                        if (this.targetSurveyId) {
                                            body.target_survey_id = this.targetSurveyId;
                                            body.target_dep = this.targetDepVar || this.depVar;
                                        }
                                        if (this.targetSurveyIds && this.targetSurveyIds.length > 0) {
                                            body.target_survey_ids = this.targetSurveyIds;
                                        }
                                    } else {
                                        if (!this.groupVar) return this.loading = false;
                                        body.group = this.groupVar;
                                    }
                                } else if (this.testMethod === 'correlation') {
                                    if (!this.varX || !this.varY) return this.loading = false;
                                    body.varX = this.varX;
                                    body.varY = this.varY;
                                } else if (this.testMethod === 'regression') {
                                    if (!this.depVar || !this.groupVar) return this.loading = false;
                                    body.dep = this.depVar;
                                    body.ind = this.groupVar;
                                } else if (this.testMethod === 'regression_multiple') {
                                    if (!this.depVar || this.indVars.length === 0) return this.loading = false;
                                    body.dep = this.depVar;
                                    body.ind = this.indVars.join(',');
                                }

                                try {
                                    const res = await fetch(url, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        },
                                        body: JSON.stringify(body)
                                    });
                                    const data = await res.json().catch(() => ({}));
                                    if (!res.ok || data.success === false) {
                                        this.clearResults();
                                        this.analysisError = data.message || 'Unable to compute statistical analysis.';
                                        return;
                                    }
                                    this.analysisError = null;
                                    if (this.testMethod === 'crosstab') {
                                        this.matrixData = data;
                                    } else if (this.testMethod === 'chisquare') {
                                        this.chisquareData = data;
                                    } else if (this.testMethod === 'cronbach') {
                                        this.cronbachData = data;
                                    } else if (this.testMethod === 'ttest') {
                                        this.tTestData = data;
                                    } else if (this.testMethod === 'anova') {
                                        this.anovaData = data;
                                    } else if (this.testMethod === 'correlation') {
                                        this.correlationData = data;
                                    } else if (this.testMethod === 'regression') {
                                        this.regressionData = data;
                                    } else if (this.testMethod === 'regression_multiple') {
                                        this.multipleRegressionData = data;
                                    }

                                    // Auto-trigger AI Interpretation immediately
                                    this.$nextTick(async () => {
                                        await this.getAiInterpretation();
                                    });
                                } catch (err) {
                                    this.clearResults();
                                    this.analysisError = err.message || 'An unexpected error occurred while calculating results.';
                                } finally {
                                    this.loading = false;
                                }
                            },

                            async saveTest() {
                                this.isSaving = true;

                                let currentData = null;
                                if (this.testMethod === 'crosstab') currentData = this.matrixData;
                                else if (this.testMethod === 'chisquare') currentData = this.chisquareData;
                                else if (this.testMethod === 'cronbach') currentData = this.cronbachData;
                                else if (this.testMethod === 'ttest') currentData = this.tTestData;
                                else if (this.testMethod === 'anova') currentData = this.anovaData;
                                else if (this.testMethod === 'correlation') currentData = this.correlationData;
                                else if (this.testMethod === 'regression') currentData = this.regressionData;
                                else if (this.testMethod === 'regression_multiple') currentData = this.multipleRegressionData;

                                let variables = [];
                                if (this.scope) variables.push("Scope: " + this.scope);
                                if (this.rowVar) variables.push("Row: " + this.rowVar);
                                if (this.colVar) variables.push("Col: " + this.colVar);
                                if (this.depVar) variables.push("Dep: " + this.depVar);
                                if (this.groupVar) variables.push("Grp: " + this.groupVar);
                                if (this.ttestGroup1 && this.ttestGroup2) variables.push("Cohorts: " + this.ttestGroup1 + " vs " + this.ttestGroup2);
                                if (this.targetSurveyId) variables.push("Target Survey: " + this.targetSurveyId);
                                if (this.varX) variables.push("X: " + this.varX);
                                if (this.varY) variables.push("Y: " + this.varY);

                                try {
                                    const res = await fetch(`{{ route('surveys.reports.inferential.save', $survey) }}`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        },
                                        body: JSON.stringify({
                                            survey_id: "{{ $survey->id }}",
                                            method: this.testMethod,
                                            title: "Saved " + this.formatMethod(this.testMethod) + " Analysis",
                                            variables: variables.join(', '),
                                            ai_summary: this.aiInsight,
                                            payload: {
                                                data: currentData,
                                                vars: {
                                                    scope: this.scope,
                                                    rowVar: this.rowVar,
                                                    colVar: this.colVar,
                                                    depVar: this.depVar,
                                                    groupVar: this.groupVar,
                                                    ttestGroup1: this.ttestGroup1,
                                                    ttestGroup2: this.ttestGroup2,
                                                    targetSurveyId: this.targetSurveyId,
                                                    targetDepVar: this.targetDepVar,
                                                    targetSurveyIds: this.targetSurveyIds,
                                                    uploadedDataLabel: this.uploadedDataLabel,
                                                    uploadedDataValues: this.uploadedDataValues,
                                                    varX: this.varX,
                                                    varY: this.varY,
                                                    indVars: this.indVars,
                                                    cronbachItems: this.cronbachItems
                                                }
                                            }
                                        })
                                    });
                                    const result = await res.json();
                                    if (result.success) {
                                        this.savedTests.unshift(result.analysis);
                                        this.loadedTestId = result.analysis.id;
                                    }
                                } catch (e) {
                                    console.error(e);
                                } finally {
                                    this.isSaving = false;
                                }
                            },

                            loadTest(test) {
                                this.resetForm();
                                this.loadedTestId = test.id;
                                this.testMethod = test.method;

                                let vars = (test.payload && test.payload.vars) ? test.payload.vars : {};
                                this.scope = vars.scope || 'within';
                                this.rowVar = vars.rowVar || '';
                                this.colVar = vars.colVar || '';
                                this.depVar = vars.depVar || '';
                                this.groupVar = vars.groupVar || '';
                                this.ttestGroup1 = vars.ttestGroup1 || '';
                                this.ttestGroup2 = vars.ttestGroup2 || '';
                                this.targetSurveyId = vars.targetSurveyId || '';
                                this.targetDepVar = vars.targetDepVar || '';
                                this.targetSurveyIds = vars.targetSurveyIds || [];
                                this.uploadedDataLabel = vars.uploadedDataLabel || 'National Benchmark (2025)';
                                this.uploadedDataValues = vars.uploadedDataValues || [];
                                this.varX = vars.varX || '';
                                this.varY = vars.varY || '';
                                this.indVars = vars.indVars || [];
                                this.cronbachItems = vars.cronbachItems || [];

                                let currentData = test.payload ? test.payload.data : null;
                                if (this.testMethod === 'crosstab') this.matrixData = currentData;
                                else if (this.testMethod === 'chisquare') { this.chisquareData = currentData; this.matrixData = currentData; }
                                else if (this.testMethod === 'cronbach') this.cronbachData = currentData;
                                else if (this.testMethod === 'ttest') this.tTestData = currentData;
                                else if (this.testMethod === 'anova') this.anovaData = currentData;
                                else if (this.testMethod === 'correlation') this.correlationData = currentData;
                                else if (this.testMethod === 'regression') this.regressionData = currentData;
                                else if (this.testMethod === 'regression_multiple') this.multipleRegressionData = currentData;

                                this.aiInsight = test.ai_summary;
                                if (this.aiInsight && typeof this.aiInsight === 'string' && this.aiInsight.trim() !== '') {
                                    this.aiMessages = [{ role: 'assistant', content: this.aiInsight.trim() }];
                                } else {
                                    this.aiInsight = null;
                                    this.aiMessages = [];
                                }
                            },

                            async deleteSavedTest(id) {
                                if (!confirm("Are you sure you want to delete this saved analysis?")) return;
                                try {
                                    const res = await fetch(`{{ url('/surveys/' . $survey->id . '/inferential-analysis') }}/${id}`, {
                                        method: 'DELETE',
                                        headers: {
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                            'Accept': 'application/json'
                                        }
                                    });
                                    if (res.ok) {
                                        this.savedTests = this.savedTests.filter(t => t.id !== id);
                                        if (this.loadedTestId === id) this.resetForm();
                                    } else {
                                        const errData = await res.json().catch(() => ({}));
                                        if (window.Swal) {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Delete Failed',
                                                text: errData.message || 'Unable to delete the saved test.'
                                            });
                                        }
                                    }
                                } catch (e) {
                                    console.error(e);
                                    if (window.Swal) {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: 'A network error occurred while deleting the test.'
                                        });
                                    }
                                }
                            },

                            async toggleReportInclusion(test) {
                                if (!test || !test.id) return;
                                try {
                                    const res = await fetch(`{{ url('/surveys/' . $survey->id . '/inferential-analysis') }}/${test.id}/toggle-report`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                            'Accept': 'application/json'
                                        }
                                    });
                                    const data = await res.json();
                                    if (data.success) {
                                        test.is_included_in_report = data.is_included_in_report;
                                        if (window.Swal) {
                                            Swal.fire({
                                                toast: true,
                                                position: 'top-end',
                                                icon: 'success',
                                                title: data.message,
                                                showConfirmButton: false,
                                                timer: 2000
                                            });
                                        }
                                    }
                                } catch (e) {
                                    console.error('Toggle report inclusion failed:', e);
                                }
                            },

                            getMatrixValue(row, col) {
                                const data = this.testMethod === 'chisquare' ? (this.chisquareData || this.matrixData) : this.matrixData;
                                if (data && data.matrix && data.matrix[row] && data.matrix[row][col] !== undefined) {
                                    return data.matrix[row][col];
                                }
                                return 0;
                            },

                            escapeHtml(value) {
                                return String(value)
                                    .replace(/&/g, '&amp;')
                                    .replace(/</g, '&lt;')
                                    .replace(/>/g, '&gt;')
                                    .replace(/"/g, '&quot;')
                                    .replace(/'/g, '&#039;');
                            },

                            renderFormattedText(text) {
                                if (!text) return '';
                                const escaped = this.escapeHtml(text);
                                return escaped
                                    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                                    .replace(/__(.+?)__/g, '<strong>$1</strong>')
                                    .replace(/\*(.+?)\*/g, '<em>$1</em>')
                                    .replace(/`(.+?)`/g, '<code class="bg-gray-200/60 px-1 py-0.5 rounded text-xs">$1</code>');
                            },

                            formatExpectedCount(val) {
                                if (val === undefined || val === null) return '0.0';
                                const num = Number(val);
                                if (isNaN(num)) return '0.0';
                                if (num < 1.0) {
                                    return num.toFixed(2);
                                }
                                return num.toFixed(1);
                            },

                            formatShortVarLabel(label, maxLen = 45) {
                                if (!label) return '';
                                const str = String(label).trim();
                                if (str.length <= maxLen) return str;
                                return str.substring(0, maxLen).trim() + '...';
                            },

                            async getAiInterpretation() {
                                let currentData = null;
                                if (this.testMethod === 'crosstab') currentData = this.matrixData;
                                else if (this.testMethod === 'chisquare') currentData = this.chisquareData || this.matrixData;
                                else if (this.testMethod === 'cronbach') currentData = this.cronbachData;
                                else if (this.testMethod === 'ttest') currentData = this.tTestData;
                                else if (this.testMethod === 'anova') currentData = this.anovaData;
                                else if (this.testMethod === 'correlation') currentData = this.correlationData;
                                else if (this.testMethod === 'regression') currentData = this.regressionData;
                                else if (this.testMethod === 'regression_multiple') currentData = this.multipleRegressionData;

                                if (!currentData) return;
                                if (currentData.grandTotal !== undefined && currentData.grandTotal <= 0) return;
                                if (currentData.validCases !== undefined && currentData.validCases <= 0) return;
                                if (currentData.n !== undefined && currentData.n <= 0) return;
                                if (currentData.valid_n !== undefined && currentData.valid_n <= 0) return;

                                this.aiLoading = true;
                                this.aiInsight = null;
                                this.aiMessages = [];

                                try {
                                    const res = await fetch(`{{ route('ai.insights.inferential') }}`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        },
                                        body: JSON.stringify({
                                            survey_id: "{{ $survey->id }}",
                                            method: this.testMethod,
                                            data: currentData
                                        })
                                    });
                                    if (!res.ok) {
                                        const errData = await res.json();
                                        throw new Error(errData.message || 'Analysis failed.');
                                    }
                                    const data = await res.json();
                                    if (data && data.insight && typeof data.insight === 'string' && data.insight.trim() !== '') {
                                        this.aiInsight = data.insight.trim();
                                        this.aiMessages = [{ role: 'assistant', content: this.aiInsight }];
                                    } else {
                                        this.aiInsight = null;
                                        this.aiMessages = [];
                                    }
                                } catch (err) {
                                    console.error("Interpretation Error: ", err);
                                    this.aiInsight = null;
                                    this.aiMessages = [];
                                    if (window.Swal) {
                                        Swal.fire({
                                            icon: 'warning',
                                            title: @js(__('Interpretation Notice')),
                                            text: err.message || @js(__('Unable to generate statistical interpretation. Please check your AI quota or connection.')),
                                            confirmButtonColor: '#2271b1'
                                        });
                                    }
                                } finally {
                                    this.aiLoading = false;
                                    // Auto-save analysis test right after calculation & AI completion
                                    this.saveTest();
                                }
                            },

                            async polishAiInsight() {
                                if (!this.aiFeedback.trim() || this.aiMessages.length === 0) return;
                                const userMsg = this.aiFeedback.trim();
                                this.aiMessages.push({ role: 'user', content: userMsg });
                                this.aiFeedback = '';
                                this.aiPolishing = true;
                                try {
                                    let currentData = null;
                                    if (this.testMethod === 'crosstab') currentData = this.matrixData;
                                    else if (this.testMethod === 'ttest') currentData = this.tTestData;
                                    else if (this.testMethod === 'anova') currentData = this.anovaData;
                                    else if (this.testMethod === 'correlation') currentData = this.correlationData;
                                    else if (this.testMethod === 'regression') currentData = this.regressionData;
                                    else if (this.testMethod === 'regression_multiple') currentData = this.multipleRegressionData;

                                    const res = await fetch(`{{ route('ai.insights.inferential') }}`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        },
                                        body: JSON.stringify({
                                            survey_id: "{{ $survey->id }}",
                                            messages: this.aiMessages,
                                            feedback: userMsg,
                                            method: this.testMethod,
                                            data: currentData
                                        })
                                    });
                                    if (!res.ok) {
                                        const errData = await res.json();
                                        throw new Error(errData.message || 'AI Polish failed.');
                                    }
                                    const data = await res.json();
                                    if (data.success) {
                                        let insightText = data.insight;
                                        const jsonMatch = insightText.match(/```json\s*([\s\S]*?)\s*```/);
                                        if (jsonMatch) {
                                            try {
                                                const parsed = JSON.parse(jsonMatch[1].trim());
                                                if (parsed.action === 'recalculate') {
                                                    if (parsed.rowVar) this.rowVar = parsed.rowVar;
                                                    if (parsed.colVar) this.colVar = parsed.colVar;
                                                    if (parsed.depVar) this.depVar = parsed.depVar;
                                                    if (parsed.groupVar) this.groupVar = parsed.groupVar;
                                                    if (parsed.varX) this.varX = parsed.varX;
                                                    if (parsed.varY) this.varY = parsed.varY;
                                                    if (parsed.indVars) this.indVars = parsed.indVars;
                                                    if (parsed.testMethod) this.testMethod = parsed.testMethod;

                                                    // Pre-append messages thread and trigger re-run
                                                    this.$nextTick(() => {
                                                        this.runAnalysis();
                                                    });
                                                    return;
                                                }

                                                if (this.testMethod === 'crosstab' && this.matrixData) {
                                                    this.matrixData = { ...this.matrixData, ...parsed };
                                                } else if (this.testMethod === 'regression' && this.regressionData) {
                                                    this.regressionData = { ...this.regressionData, ...parsed };
                                                } else if (this.testMethod === 'regression_multiple' && this.multipleRegressionData) {
                                                    this.multipleRegressionData = { ...this.multipleRegressionData, ...parsed };
                                                }
                                            } catch (e) {
                                                console.error("Failed to parse updated table JSON from AI:", e);
                                            }
                                            insightText = insightText.replace(/```json\s*[\s\S]*?\s*```/, '').trim();
                                        }
                                        this.aiInsight = insightText;
                                        this.aiMessages.push({ role: 'assistant', content: insightText });
                                    } else {
                                        throw new Error(data.message || 'AI Polish failed.');
                                    }
                                } catch (err) {
                                    alert("AI Polish Error: " + err.message);
                                    this.aiMessages.pop();
                                    this.aiFeedback = userMsg;
                                } finally {
                                    this.aiPolishing = false;
                                }
                            },

                            async polishWithInstruction(instruction) {
                                if (!instruction) return;
                                this.aiFeedback = instruction;
                                if (this.aiMessages.length === 0) {
                                    await this.getAiInterpretation();
                                } else {
                                    await this.polishAiInsight();
                                }
                            },

                            async saveInsightToKb() {
                                const lastMsg = [...this.aiMessages].reverse().find(m => m.role === 'user');
                                const instructionToSave = (this.aiFeedback && this.aiFeedback.trim())
                                    ? this.aiFeedback.trim()
                                    : (lastMsg ? lastMsg.content : (this.aiInsight ? `Format ${this.testMethod} statistical analysis in APA 7th style with effect sizes` : ''));
                                if (!instructionToSave) {
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            title: @js(__('No Instruction to Save')),
                                            text: @js(__('Please enter a refinement instruction before saving to Knowledge Base.')),
                                            icon: 'warning',
                                            customClass: { popup: 'rounded-2xl shadow-xl' }
                                        });
                                    }
                                    return;
                                }

                                try {
                                    const res = await fetch('/socius/knowledge-base', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                            'Accept': 'application/json'
                                        },
                                        body: JSON.stringify({
                                            content: `[Inferential] ${instructionToSave}`,
                                            scope: 'inferential',
                                            is_active: true
                                        })
                                    });
                                    if (res.ok) {
                                        const data = await res.json();
                                        const mainEl = document.querySelector('[x-data*="reportManager"]');
                                        if (mainEl && window.Alpine) {
                                            const rootData = Alpine.$data(mainEl);
                                            if (rootData && data.rule) {
                                                rootData.kbRules.unshift(data.rule);
                                            }
                                        }
                                        if (typeof Swal !== 'undefined') {
                                            Swal.fire({
                                                title: @js(__('Saved to Knowledge Base')),
                                                text: @js(__('Instruction has been saved and will guide future inferential interpretations.')),
                                                icon: 'success',
                                                toast: true,
                                                position: 'top-end',
                                                showConfirmButton: false,
                                                timer: 3000
                                            });
                                        }
                                    }
                                } catch (e) {
                                    console.error('Failed to save to KB:', e);
                                }
                            },

                            analyzeWithSocius() {
                                let testName = 'Statistical Analysis';
                                let statsSummary = '';
                                let variablesSummary = '';

                                if (this.testMethod === 'ttest' && this.tTestData) {
                                    testName = 'Independent Samples T-Test';
                                    variablesSummary = `Dependent Variable: "${this.tTestData.depLabel || 'Dependent Variable'}", Grouping Variable: "${this.tTestData.groupLabel || 'Grouping Variable'}"`;
                                    statsSummary = `t-value = ${this.tTestData.tValue}, df = ${this.tTestData.df}, p-value = ${this.tTestData.pValue} (${this.tTestData.significant ? 'Statistically Significant' : 'Not Significant'}), Mean Difference = ${this.tTestData.meanDiff}, Std Error = ${this.tTestData.stdErrorDiff}.\nGroup Statistics: ` + JSON.stringify(this.tTestData.groups, null, 2);
                                } else if (this.testMethod === 'anova' && this.anovaData) {
                                    testName = 'One-Way ANOVA';
                                    variablesSummary = `Dependent Variable: "${this.anovaData.depLabel || 'Dependent Variable'}", Grouping Variable: "${this.anovaData.groupLabel || 'Grouping Variable'}"`;
                                    statsSummary = `F-value = ${this.anovaData.fValue}, df = (${this.anovaData.dfBetween}, ${this.anovaData.dfWithin}), p-value = ${this.anovaData.pValue} (${this.anovaData.significant ? 'Statistically Significant' : 'Not Significant'}), SSB = ${this.anovaData.ssb}, SSW = ${this.anovaData.ssw}.\nGroup Descriptives: ` + JSON.stringify(this.anovaData.groupStats, null, 2);
                                } else if (this.testMethod === 'correlation' && this.correlationData) {
                                    testName = 'Pearson Correlation Analysis';
                                    variablesSummary = `Variables: "${this.correlationData.labelX || 'Variable X'}" vs "${this.correlationData.labelY || 'Variable Y'}"`;
                                    statsSummary = `Sample size N = ${this.correlationData.n}, Pearson r = ${this.correlationData.r}, R² = ${this.correlationData.r2}, t = ${this.correlationData.tValue}, p-value = ${this.correlationData.pValue} (${this.correlationData.significant ? 'Statistically Significant' : 'Not Significant'}).`;
                                } else if (this.testMethod === 'regression' && this.regressionData) {
                                    testName = 'Simple Linear Regression Analysis';
                                    variablesSummary = `Dependent Variable (Y): "${this.regressionData.depLabel || 'Y'}", Independent Variable (X): "${this.regressionData.indLabel || 'X'}"`;
                                    statsSummary = `Sample N = ${this.regressionData.n}, Residual df = ${this.regressionData.anova?.dfRes}, R = ${this.regressionData.r}, R² = ${this.regressionData.r2}, Adjusted R² = ${this.regressionData.adjR2}, Std Error = ${this.regressionData.stdErrorEst}, ANOVA F(${this.regressionData.anova?.dfReg}, ${this.regressionData.anova?.dfRes}) = ${this.regressionData.anova?.fValue}, p = ${this.regressionData.anova?.pValue}. Note: Report slope t-test in APA format citing residual df as t(${this.regressionData.anova?.dfRes}) = ${this.regressionData.coefficients?.slope?.tValue}.\nCoefficients: ` + JSON.stringify(this.regressionData.coefficients, null, 2);
                                } else if (this.testMethod === 'regression_multiple' && this.multipleRegressionData) {
                                    testName = 'Multiple Linear Regression Analysis';
                                    variablesSummary = `Dependent Variable (Y): "${this.multipleRegressionData.depLabel || 'Y'}", Model Equation: "${this.multipleRegressionData.equation || ''}"`;
                                    statsSummary = `Sample N = ${this.multipleRegressionData.n}, Residual df = ${this.multipleRegressionData.anova?.dfRes}, R = ${this.multipleRegressionData.r}, R² = ${this.multipleRegressionData.r2}, Adjusted R² = ${this.multipleRegressionData.adjR2}, Std Error = ${this.multipleRegressionData.stdErrorEst}, ANOVA F(${this.multipleRegressionData.anova?.dfReg}, ${this.multipleRegressionData.anova?.dfRes}) = ${this.multipleRegressionData.anova?.fValue}, p = ${this.multipleRegressionData.anova?.pValue}. Note: Report predictor t-tests in APA format citing residual df as t(${this.multipleRegressionData.anova?.dfRes}) = value.\nCoefficients: ` + JSON.stringify(this.multipleRegressionData.coefficients, null, 2);
                                } else if (this.testMethod === 'chisquare' && (this.chisquareData || this.matrixData)) {
                                    const d = this.chisquareData || this.matrixData;
                                    testName = 'Chi-Square Test of Independence';
                                    variablesSummary = `Row: "${d.rowLabel || 'Row Variable'}", Column: "${d.colLabel || 'Column Variable'}"`;
                                    statsSummary = `χ² = ${d.chiSquare || 0}, df = ${d.df || 1}, p-value = ${d.pValue || 1} (${d.significant ? 'Statistically Significant' : 'Not Significant'}), Cramer's V = ${d.cramersV || 'N/A'} (${d.effectLabel || 'N/A'}).`;
                                } else if (this.testMethod === 'cronbach' && this.cronbachData) {
                                    testName = "Cronbach's Alpha Reliability Analysis";
                                    variablesSummary = `Items evaluated: ${this.cronbachData.k_items}, Valid cases: ${this.cronbachData.valid_n}`;
                                    statsSummary = `Cronbach's Alpha α = ${this.cronbachData.alpha}, Standardized α = ${this.cronbachData.std_alpha}, Reliability rating: ${this.cronbachData.interpretation}.\nItem-Total Statistics: ` + JSON.stringify(this.cronbachData.item_stats, null, 2);
                                } else if (this.testMethod === 'crosstab' && this.matrixData) {
                                    testName = 'Cross-Tabulation Matrix Analysis';
                                    variablesSummary = `Row: "${this.matrixData.rowLabel || 'Row'}", Column: "${this.matrixData.colLabel || 'Column'}"`;
                                    statsSummary = `Total Sample N = ${this.matrixData.grandTotal}.\nFrequencies: ` + JSON.stringify(this.matrixData.matrix, null, 2);
                                }

                                const aiCurrent = this.aiInsight ? `\n\nPreliminary Interpretation Summary:\n"${this.aiInsight}"` : '';

                                const promptText = `STATISTICAL RESEARCH ANALYSIS (${testName.toUpperCase()}):
                                                                                                                                                                                                                                                                                                                                            Please provide a comprehensive academic discussion and formal APA 7th style writeup for the following statistical findings from survey: "{{ $survey->title }}".

                                                                                                                                                                                                                                                                                                                                            TEST DETAILS:
                                                                                                                                                                                                                                                                                                                                            - Test Conducted: ${testName}
                                                                                                                                                                                                                                                                                                                                            - Variables: ${variablesSummary}

                                                                                                                                                                                                                                                                                                                                            STATISTICAL METRICS & DATA PAYLOAD:
                                                                                                                                                                                                                                                                                                                                            ${statsSummary}${aiCurrent}

                                                                                                                                                                                                                                                                                                                                            KEY REQUIREMENTS:
                                                                                                                                                                                                                                                                                                                                            1. Provide a rigorous APA 7th statistical writeup (reporting test statistic, degrees of freedom, exact p-value, effect size / confidence interval).
                                                                                                                                                                                                                                                                                                                                            2. Detail the empirical interpretation in relation to the research questions.
                                                                                                                                                                                                                                                                                                                                            3. Discuss practical and policy implications for decision-makers.
                                                                                                                                                                                                                                                                                                                                            4. Highlight methodological limitations and recommend actionable next steps.`;

                                // Switch to Analyze tab in main survey reports
                                const mainEl = document.querySelector('[x-data*="reportManager"]');
                                if (mainEl && window.Alpine) {
                                    const rootData = Alpine.$data(mainEl);
                                    if (rootData && rootData.switchReportTab) {
                                        rootData.switchReportTab('analyse');
                                    }
                                }

                                // Pre-fill Socius chat prompt input
                                setTimeout(() => {
                                    const inputEl = document.getElementById('socius-prompt-input');
                                    if (inputEl) {
                                        inputEl.value = promptText;
                                        inputEl.dispatchEvent(new Event('input', { bubbles: true }));
                                        if (window.Alpine) {
                                            const sociusData = Alpine.$data(inputEl);
                                            if (sociusData && 'draft' in sociusData) {
                                                sociusData.draft = promptText;
                                            }
                                        }
                                        inputEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                        inputEl.focus();
                                    }
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            title: @js(__('Switched to Socius')),
                                            text: @js(__('Statistical data transferred to Socius chat!')),
                                            icon: 'success',
                                            toast: true,
                                            position: 'top-end',
                                            showConfirmButton: false,
                                            timer: 2500
                                        });
                                    }
                                }, 300);
                            },

                            copyFinalOutput() {
                                const lastMsg = [...this.aiMessages].reverse().find(m => m.role === 'assistant');
                                if (!lastMsg) return;
                                navigator.clipboard.writeText(lastMsg.content).then(() => {
                                    alert("Copied interpretation to clipboard!");
                                });
                            },

                            downloadFinalOutput() {
                                const lastMsg = [...this.aiMessages].reverse().find(m => m.role === 'assistant');
                                if (!lastMsg) return;
                                const blob = new Blob([lastMsg.content], { type: 'text/plain;charset=utf-8' });
                                const url = URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = url;
                                a.download = `statistical_interpretation_${this.testMethod}.txt`;
                                document.body.appendChild(a);
                                a.click();
                                document.body.removeChild(a);
                                URL.revokeObjectURL(url);
                            },

                            copyResultsToClipboard() {
                                let text = "";

                                if (this.testMethod === 'crosstab' && this.matrixData) {
                                    text += "Cross-Tabulation Matrix: " + this.matrixData.rowLabel + " vs " + this.matrixData.colLabel + "\n";
                                    text += "\t" + this.matrixData.columns.join("\t") + "\tTotal\n";
                                    this.matrixData.rows.forEach(r => {
                                        text += r;
                                        this.matrixData.columns.forEach(c => {
                                            text += "\t" + this.getMatrixValue(r, c);
                                        });
                                        text += "\t" + (this.matrixData.rowTotals[r] || 0) + "\n";
                                    });
                                    text += "Total";
                                    this.matrixData.columns.forEach(c => {
                                        text += "\t" + (this.matrixData.colTotals[c] || 0);
                                    });
                                    text += "\t" + this.matrixData.grandTotal + "\n\n";
                                    text += "Chi-Square Test:\n";
                                    text += "Pearson Chi-Square\tValue: " + this.matrixData.chiSquare + "\tdf: " + this.matrixData.df + "\tSig: " + this.matrixData.pValue + "\n";
                                }

                                else if (this.testMethod === 'ttest' && this.tTestData) {
                                    text += "Independent Samples T-Test: Group Descriptives\n";
                                    text += "Group\tN\tMean\tStd. Deviation\tStd. Error Mean\n";
                                    this.tTestData.groups.forEach(g => {
                                        text += g.name + "\t" + g.n + "\t" + g.mean + "\t" + g.stdDev + "\t" + g.stdError + "\n";
                                    });
                                    text += "\nT-Test statistics:\nt\tdf\tSig. (2-tailed)\tMean Difference\n";
                                    text += this.tTestData.tValue + "\t" + this.tTestData.df + "\t" + this.tTestData.pValue + "\t" + this.tTestData.meanDiff + "\n";
                                }

                                else if (this.testMethod === 'correlation' && this.correlationData) {
                                    text += "Pearson Correlation matrix:\n";
                                    text += "\t" + this.correlationData.labelX + "\t" + this.correlationData.labelY + "\n";
                                    text += this.correlationData.labelX + "\tr=1.000\tr=" + this.correlationData.r + " (p=" + this.correlationData.pValue + ", N=" + this.correlationData.n + ")\n";
                                    text += this.correlationData.labelY + "\tr=" + this.correlationData.r + " (p=" + this.correlationData.pValue + ", N=" + this.correlationData.n + ")\tr=1.000\n";
                                    text += "\nCovariance: " + this.correlationData.covariance + "\tStd. Error: " + this.correlationData.stdErrorR + "\t95% CI: [" + this.correlationData.ciLower + ", " + this.correlationData.ciUpper + "]\n";
                                }

                                else if (this.testMethod === 'anova' && this.anovaData) {
                                    text += "ANOVA Descriptives:\nGroup\tN\tMean\tStd. Deviation\tStd. Error\n";
                                    this.anovaData.groupStats.forEach(g => {
                                        text += g.name + "\t" + g.n + "\t" + g.mean + "\t" + g.stdDev + "\t" + g.stdError + "\n";
                                    });
                                    text += "\nANOVA Source Table:\nSource\tSum of Squares\tdf\tMean Square\tF\tSig.\n";
                                    text += "Between Groups\t" + this.anovaData.ssb + "\t" + this.anovaData.dfBetween + "\t" + this.anovaData.msb + "\t" + this.anovaData.fValue + "\t" + this.anovaData.pValue + "\n";
                                    text += "Within Groups\t" + this.anovaData.ssw + "\t" + this.anovaData.dfWithin + "\t" + this.anovaData.msw + "\n";
                                    text += "Total\t" + this.anovaData.sst + "\t" + this.anovaData.dfTotal + "\n";
                                }

                                else if (this.testMethod === 'regression' && this.regressionData) {
                                    text += "Simple Regression Summary:\nR=" + this.regressionData.r + "\tR Square=" + this.regressionData.r2 + "\tAdj R Square=" + this.regressionData.adjR2 + "\tStd Error=" + this.regressionData.stdErrorEst + "\n";
                                    text += "\nANOVA (Model Fit):\nSource\tSS\tdf\tMS\tF\tSig.\n";
                                    text += "Regression\t" + this.regressionData.anova.ssr + "\t" + this.regressionData.anova.dfReg + "\t" + this.regressionData.anova.msr + "\t" + this.regressionData.anova.fValue + "\t" + this.regressionData.anova.pValue + "\n";
                                    text += "Residual\t" + this.regressionData.anova.sse + "\t" + this.regressionData.anova.dfRes + "\t" + this.regressionData.anova.mse + "\n";
                                    text += "\nCoefficients:\nModel\tB\tStd. Error\tt\tSig.\n";
                                    text += "(Constant)\t" + this.regressionData.coefficients.intercept.coef + "\t" + this.regressionData.coefficients.intercept.stdError + "\t" + this.regressionData.coefficients.intercept.tValue + "\t" + this.regressionData.coefficients.intercept.pValue + "\n";
                                    text += "Slope (X)\t" + this.regressionData.coefficients.slope.coef + "\t" + this.regressionData.coefficients.slope.stdError + "\t" + this.regressionData.coefficients.slope.tValue + "\t" + this.regressionData.coefficients.slope.pValue + "\n";
                                }

                                else if (this.testMethod === 'regression_multiple' && this.multipleRegressionData) {
                                    text += "Multiple Regression Equation: " + this.multipleRegressionData.equation + "\n\n";
                                    text += "Model Summary:\nR=" + this.multipleRegressionData.r + "\tR Square=" + this.multipleRegressionData.r2 + "\tAdj R Square=" + this.multipleRegressionData.adjR2 + "\tStd Error=" + this.multipleRegressionData.stdErrorEst + "\n";
                                    text += "\nANOVA (Model Fit):\nSource\tSS\tdf\tMS\tF\tSig.\n";
                                    text += "Regression\t" + this.multipleRegressionData.anova.ssr + "\t" + this.multipleRegressionData.anova.dfReg + "\t" + this.multipleRegressionData.anova.msr + "\t" + this.multipleRegressionData.anova.fValue + "\t" + this.multipleRegressionData.anova.pValue + "\n";
                                    text += "Residual\t" + this.multipleRegressionData.anova.sse + "\t" + this.multipleRegressionData.anova.dfRes + "\t" + this.multipleRegressionData.anova.mse + "\n";
                                    text += "\nCoefficients:\nModel\tB\tStd. Error\tBeta\tt\tSig.\n";
                                    this.multipleRegressionData.coefficients.forEach(c => {
                                        text += c.label + "\t" + c.coef + "\t" + c.stdError + "\t" + c.beta + "\t" + c.tValue + "\t" + c.pValue + "\n";
                                    });
                                }

                                if (!text) {
                                    alert("No data available to copy.");
                                    return;
                                }

                                navigator.clipboard.writeText(text).then(() => {
                                    alert("Results copied in TSV format! You can now paste directly into Excel, Word, or SPSS.");
                                }).catch(err => {
                                    console.error(err);
                                    alert("Failed to copy results.");
                                });
                            }
                        };
                    };


                    window.currentActiveColors = {};
                    window.currentChartTypes = {};

                    window.exportReportWithSettings = function (format, surveyId) {
                        const formatName = format === 'pdf' ? 'PDF Report' : 'Word (DOCX) Document';
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: `<span class="text-base font-bold text-gray-900">${@js(__('Generating Full Executive Report...'))}</span>`,
                                html: `<div class="text-xs text-gray-500 space-y-2 text-left mt-2 bg-gray-50 p-3 rounded-xl border border-gray-100">
                                                                                                                                                                                                                                                                                                                                                            <p class="flex items-center gap-2"><i class="fa-solid fa-chart-pie text-[#2271b1]"></i> <span>${@js(__('Compiling charts & distribution tables...'))}</span></p>
                                                                                                                                                                                                                                                                                                                                                            <p class="flex items-center gap-2"><i class="fa-solid fa-brain text-indigo-500"></i> <span>${@js(__('Integrating statistical interpretations...'))}</span></p>
                                                                                                                                                                                                                                                                                                                                                            <p class="text-[11px] text-amber-700 font-medium pt-1 border-t border-gray-200/60">${@js(__('For comprehensive or large surveys, this download may take a few moments.'))}</p>
                                                                                                                                                                                                                                                                                                                                                           </div>`,
                                showConfirmButton: false,
                                allowOutsideClick: false,
                                timer: 10000,
                                timerProgressBar: true,
                                customClass: { popup: 'rounded-3xl shadow-2xl border border-gray-100 max-w-sm' },
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                        }

                        const actionUrl = format === 'pdf'
                            ? `{{ route('surveys.export_pdf', $survey) }}`
                            : `{{ route('surveys.export_docx', $survey) }}`;

                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = actionUrl;
                        form.style.display = 'none';

                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        if (csrfToken) {
                            const csrfInput = document.createElement('input');
                            csrfInput.type = 'hidden';
                            csrfInput.name = '_token';
                            csrfInput.value = csrfToken;
                            form.appendChild(csrfInput);
                        }

                        if (Object.keys(window.currentActiveColors || {}).length > 0) {
                            const colorsInput = document.createElement('input');
                            colorsInput.type = 'hidden';
                            colorsInput.name = 'colors';
                            colorsInput.value = JSON.stringify(window.currentActiveColors);
                            form.appendChild(colorsInput);
                        }

                        if (Object.keys(window.currentChartTypes || {}).length > 0) {
                            const typesInput = document.createElement('input');
                            typesInput.type = 'hidden';
                            typesInput.name = 'types';
                            typesInput.value = JSON.stringify(window.currentChartTypes);
                            form.appendChild(typesInput);
                        }

                        document.body.appendChild(form);
                        form.submit();
                        setTimeout(() => { document.body.removeChild(form); }, 1500);
                    };

                    window.qualitativeQuestionCard = function (questionId, surveyId) {
                        return {
                            qId: questionId,
                            sId: surveyId,
                            loading: false,
                            polishing: false,
                            instruction: '',
                            narrative: null,
                            keyFindings: [],
                            errorMessage: null,

                            init() {
                                if (!window.qualInsightInstances) {
                                    window.qualInsightInstances = {};
                                }
                                window.qualInsightInstances[this.qId] = this;
                            },

                            async generate() {
                                this.loading = true;
                                this.errorMessage = null;
                                try {
                                    const res = await fetch(`/ai/insights/qualitative/${this.qId}?survey_id=${this.sId}`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                            'Accept': 'application/json'
                                        }
                                    });
                                    const data = await res.json();
                                    if (!res.ok || (data.success !== undefined && !data.success)) {
                                        throw new Error(data.message || @js(__('Failed to synthesize qualitative responses.')));
                                    }
                                    this.narrative = data.narrative || (typeof data === 'string' ? data : null);
                                    this.keyFindings = data.key_findings || [];
                                } catch (e) {
                                    this.errorMessage = e.message;
                                } finally {
                                    this.loading = false;
                                }
                            },

                            async refine(customInstruction = null) {
                                const prompt = customInstruction || this.instruction;
                                if (!prompt || !prompt.trim()) return;
                                this.polishing = true;
                                this.errorMessage = null;
                                try {
                                    const res = await fetch(`/ai/insights/qualitative/${this.qId}/refine`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                            'Accept': 'application/json'
                                        },
                                        body: JSON.stringify({
                                            survey_id: this.sId,
                                            feedback: prompt,
                                            messages: this.narrative ? [{ role: 'assistant', content: this.narrative }] : []
                                        })
                                    });
                                    const data = await res.json();
                                    if (!res.ok || !data.success) {
                                        throw new Error(data.message || @js(__('Failed to refine narrative synthesis.')));
                                    }
                                    this.narrative = data.narrative;
                                    if (data.key_findings) this.keyFindings = data.key_findings;
                                    if (!customInstruction) this.instruction = '';
                                } catch (e) {
                                    this.errorMessage = e.message;
                                } finally {
                                    this.polishing = false;
                                }
                            },

                            async refineFromGlobal(instruction) {
                                if (!this.narrative && !this.loading) {
                                    await this.generate();
                                }
                                if (this.narrative) {
                                    await this.refine(instruction);
                                }
                            }
                        };
                    };

                    window.chartManager = function () {
                        return {
                            sId: '{{ $survey->id }}',
                            chartTypes: {},
                            activeColors: {},
                            init() {
                                let savedTypes = {};
                                let savedColors = {};
                                try {
                                    savedTypes = JSON.parse(localStorage.getItem('survey_chart_types_' + this.sId) || '{}');
                                    savedColors = JSON.parse(localStorage.getItem('survey_chart_colors_' + this.sId) || '{}');
                                } catch (e) { }

                                chartConfigs.forEach(config => {
                                    const el = document.getElementById(config.canvas_id);
                                    if (el) {
                                        const defaultType = savedTypes[config.canvas_id] || 'bar';
                                        const defaultColor = savedColors[config.canvas_id] || 'vibrant';
                                        this.chartTypes[config.canvas_id] = defaultType;
                                        this.activeColors[config.canvas_id] = defaultColor;
                                        window.currentChartTypes[config.canvas_id] = defaultType;
                                        window.currentActiveColors[config.canvas_id] = defaultColor;
                                        chartInstances[config.canvas_id] = createChart(config.canvas_id, config, defaultType, defaultColor);
                                    }
                                });
                            },
                            switchChartType(canvasId, type) {
                                this.chartTypes[canvasId] = type;
                                window.currentChartTypes[canvasId] = type;
                                this.refreshChart(canvasId);
                                try {
                                    localStorage.setItem('survey_chart_types_' + this.sId, JSON.stringify(window.currentChartTypes));
                                } catch (e) { }
                            },
                            switchColor(canvasId, color) {
                                this.activeColors[canvasId] = color;
                                window.currentActiveColors[canvasId] = color;
                                this.refreshChart(canvasId);
                                try {
                                    localStorage.setItem('survey_chart_colors_' + this.sId, JSON.stringify(window.currentActiveColors));
                                } catch (e) { }
                            },
                            refreshChart(canvasId) {
                                const config = chartConfigs.find(c => c.canvas_id === canvasId);
                                if (chartInstances[canvasId]) {
                                    chartInstances[canvasId].destroy();
                                    delete chartInstances[canvasId];
                                }
                                const el = document.getElementById(canvasId);
                                if (el && config) {
                                    chartInstances[canvasId] = createChart(canvasId, config, this.chartTypes[canvasId] || 'bar', this.activeColors[canvasId] || 'vibrant');
                                }
                            },

                            async uploadKbDocument(event) {
                                const file = event.target.files[0];
                                if (!file) return;
                                this.uploadingKbDoc = true;
                                const formData = new FormData();
                                formData.append('document', file);
                                try {
                                    const response = await fetch('/socius/knowledge-base/upload', {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                            'Accept': 'application/json'
                                        },
                                        body: formData
                                    });
                                    const data = await response.json();
                                    if (!response.ok) throw new Error(data.message || 'Upload failed');
                                    await this.loadKbRules();
                                    Swal.fire({ title: 'Knowledge Integrated!', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, timerProgressBar: true, customClass: { popup: 'rounded-2xl shadow-xl border-none' } });
                                } catch (e) {
                                    Swal.fire({ title: 'Upload Error', text: e.message, icon: 'error', customClass: { popup: 'rounded-3xl border-none shadow-2xl' } });
                                } finally {
                                    this.uploadingKbDoc = false;
                                    event.target.value = '';
                                }
                            }
                        }
                    };

                    window.sociusManager = function (config) {
                        return {
                            canAnalyze: config.canAnalyze,
                            currentThreadId: null,
                            currentThread: null,
                            threads: [],
                            messages: [],
                            draft: '',
                            pendingFiles: [],
                            includeSurveyContext: true,
                            loadingThreads: false,
                            loadingMessages: false,
                            creatingThread: false,
                            sending: false,
                            activeAbortController: null,

                            stopGeneration() {
                                if (this.activeAbortController) {
                                    this.activeAbortController.abort();
                                    this.activeAbortController = null;
                                }
                                this.sending = false;
                            },
                            streamingUserId: null,
                            streamingAssistantId: null,
                            renamingThreadId: null,
                            editingTitle: '',
                            threadMenuOpen: null,
                            urls: config.urls,
                            activeGroupId: config.activeGroupId || null,
                            groups: config.groups || [],
                            isOwner: config.isOwner || false,

                            // Phase 4 Features
                            isListening: false,
                            recognition: null,
                            editingMessageId: null,
                            editingContent: '',
                            isRegenerating: false,
                            tokenUsage: null,
                            webSearchEnabled: false,
                            reviewModeEnabled: false,
                            historyOpen: window.innerWidth > 1280,
                            scrolledUp: false,
                            activePromptId: null,
                            showQuoteButton: false,
                            quoteButtonX: 0,
                            quoteButtonY: 0,
                            selectedText: '',

                            handleScroll() {
                                const el = this.$refs.messageList;
                                if (!el) return;
                                this.scrolledUp = (el.scrollHeight - el.scrollTop - el.clientHeight) > 150;

                                // Find which user message is closest to the middle of the scroll container
                                const userMsgs = this.messages.filter(m => m.role === 'user');
                                let closestId = null;
                                let minDiff = Infinity;

                                const containerRect = el.getBoundingClientRect();
                                const centerY = containerRect.top + containerRect.height / 2;

                                userMsgs.forEach(m => {
                                    const msgEl = document.getElementById(`msg-${m.id}`);
                                    if (msgEl) {
                                        const rect = msgEl.getBoundingClientRect();
                                        const msgCenterY = rect.top + rect.height / 2;
                                        const diff = Math.abs(msgCenterY - centerY);
                                        if (diff < minDiff) {
                                            minDiff = diff;
                                            closestId = m.id;
                                        }
                                    }
                                });

                                if (closestId) {
                                    this.activePromptId = closestId;
                                }
                            },
                            scrollToBottom() {
                                const el = this.$refs.messageList;
                                if (el) {
                                    el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
                                }
                            },
                            scrollToPrompt(msgId) {
                                const el = document.getElementById(`msg-${msgId}`);
                                if (el) {
                                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    this.activePromptId = msgId;
                                }
                            },

                            // Knowledge Base
                            kbModalOpen: false,
                            kbRules: [],
                            newKbRuleContent: '',
                            loadingKb: false,
                            savingKb: false,
                            uploadingKbDoc: false,

                            init() {
                                this.loadThreads();
                                this.loadKbRules();

                                // Debounced visual rendering to handle high-frequency stream updates
                                this.renderDebounce = null;
                                this.$watch('messages', () => {
                                    if (this.renderDebounce) clearTimeout(this.renderDebounce);
                                    this.renderDebounce = setTimeout(() => this.renderVisuals(), 100);
                                });

                                this.$nextTick(() => this.renderVisuals());

                                // Setup selection change listener to dynamically show the quote reference popover
                                document.addEventListener('selectionchange', () => {
                                    if (this.currentThreadId === null) {
                                        this.showQuoteButton = false;
                                        return;
                                    }
                                    const selection = window.getSelection();
                                    const selected = selection.toString().trim();
                                    if (!selected || selected.length < 3) {
                                        this.showQuoteButton = false;
                                        return;
                                    }

                                    let node = selection.anchorNode;
                                    let isInsideSociusProse = false;
                                    while (node) {
                                        if (node.classList && node.classList.contains('socius-prose')) {
                                            isInsideSociusProse = true;
                                            break;
                                        }
                                        node = node.parentNode;
                                    }

                                    if (!isInsideSociusProse) {
                                        this.showQuoteButton = false;
                                        return;
                                    }

                                    this.selectedText = selected;

                                    try {
                                        const range = selection.getRangeAt(0);
                                        const rect = range.getBoundingClientRect();
                                        const wrapper = document.querySelector('section.flex-1.bg-\\[\\#252525\\]');
                                        if (wrapper) {
                                            const wrapperRect = wrapper.getBoundingClientRect();
                                            this.quoteButtonX = rect.left - wrapperRect.left + (rect.width / 2) - 40;
                                            this.quoteButtonY = rect.top - wrapperRect.top - 40;
                                            this.showQuoteButton = true;
                                        }
                                    } catch (e) {
                                        this.showQuoteButton = false;
                                    }
                                });
                            },

                            quoteSelection() {
                                if (!this.selectedText) return;
                                const quote = `> "${this.selectedText}"\n\n`;
                                this.draft = quote + this.draft;
                                this.showQuoteButton = false;
                                window.getSelection().removeAllRanges();
                                const inputEl = document.getElementById('socius-prompt-input');
                                if (inputEl) inputEl.focus();
                            },

                            adjustTextareaHeight(target) {
                                const el = target || document.getElementById('socius-prompt-input');
                                if (!el) return;
                                el.style.height = 'auto';
                                const newHeight = Math.min(el.scrollHeight, 200);
                                el.style.height = newHeight + 'px';
                                el.style.overflowY = el.scrollHeight > 200 ? 'auto' : 'hidden';
                            },

                            async loadThreads() {
                                this.loadingThreads = true;
                                this.error = null;
                                this.currentThreadId = this.currentThreadId || config.initialThreadId;;
                                this.currentThread = null;
                                this.messages = [];

                                try {
                                    const url = this.activeGroupId ? `${this.urls.list}?group_id=${this.activeGroupId}` : this.urls.list;
                                    const response = await fetch(url, {
                                        headers: { 'Accept': 'application/json' }
                                    });
                                    const data = await this.parseJsonResponse(response);
                                    this.threads = data.threads || [];

                                    if (this.currentThreadId) {
                                        await this.loadThread(this.currentThreadId);
                                    }
                                } catch (error) {
                                    this.error = error.message;
                                } finally {
                                    this.loadingThreads = false;
                                }
                            },

                            async createThread(selectAfter = true) {
                                if (!this.canAnalyze) {
                                    return null;
                                }

                                this.creatingThread = true;
                                this.error = null;

                                try {
                                    const url = this.activeGroupId ? `${this.urls.create}?group_id=${this.activeGroupId}` : this.urls.create;
                                    const response = await fetch(url, {
                                        method: 'POST',
                                        headers: {
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        }
                                    });
                                    const data = await this.parseJsonResponse(response);
                                    const thread = data.thread;
                                    this.threads = [thread, ...this.threads.filter(item => item.id !== thread.id)];

                                    if (selectAfter) {
                                        await this.loadThread(thread.id);
                                    }

                                    return thread;
                                } catch (error) {
                                    this.error = error.message;
                                    return null;
                                } finally {
                                    this.creatingThread = false;
                                }
                            },

                            async renameThread(threadId, newTitle) {
                                if (!newTitle || !newTitle.trim()) return;
                                try {
                                    const response = await fetch(this.threadUrl('updateTemplate', threadId), {
                                        method: 'PATCH',
                                        headers: {
                                            'Accept': 'application/json',
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        },
                                        body: JSON.stringify({ title: newTitle.trim() })
                                    });
                                    const data = await this.parseJsonResponse(response);
                                    const idx = this.threads.findIndex(t => t.id === threadId);
                                    if (idx !== -1) this.threads[idx] = data.thread;
                                    if (this.currentThread && this.currentThread.id === threadId) {
                                        this.currentThread = data.thread;
                                    }
                                } catch (error) {
                                    this.error = error.message;
                                } finally {
                                    this.renamingThreadId = null;
                                    this.editingTitle = '';
                                }
                            },

                            async deleteThread(threadId) {
                                console.log('Socius: deleteThread prompt for ID:', threadId);

                                const result = await Swal.fire({
                                    title: @js(__('Delete Conversation?')),
                                    text: @js(__('This will permanently delete this conversation and all associated attachments.')),
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#ef4444',
                                    cancelButtonColor: '#6b7280',
                                    confirmButtonText: @js(__('Yes, Delete It')),
                                    cancelButtonText: @js(__('Cancel')),
                                    customClass: {
                                        popup: 'rounded-3xl border-none shadow-2xl',
                                        title: 'text-2xl font-black tracking-tight text-gray-900',
                                        confirmButton: 'rounded-xl px-6 py-2.5 text-xs font-black  tracking-widest',
                                        cancelButton: 'rounded-xl px-6 py-2.5 text-xs font-black  tracking-widest'
                                    }
                                });

                                if (!result.isConfirmed) {
                                    console.log('Socius: Delete cancelled via SweetAlert');
                                    return;
                                }

                                const url = this.threadUrl('destroyTemplate', threadId);
                                console.log('Socius: Fetching DELETE URL:', url);

                                try {
                                    const response = await fetch(url, {
                                        method: 'DELETE',
                                        headers: {
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        }
                                    });

                                    console.log('Socius: Delete response status:', response.status);
                                    const data = await this.parseJsonResponse(response);
                                    console.log('Socius: Delete success data:', data);

                                    // Use loose inequality in case of string/number mismatch
                                    const beforeCount = this.threads.length;
                                    this.threads = this.threads.filter(t => t.id != threadId);
                                    console.log('Socius: Threads filtered. Before:', beforeCount, 'After:', this.threads.length);

                                    if (this.currentThreadId == threadId) {
                                        console.log('Socius: Deleting active thread, resetting state.');
                                        this.currentThreadId = null;
                                        this.currentThread = null;
                                        this.messages = [];
                                        this.syncQuery();
                                    }

                                    Swal.fire({
                                        title: @js(__('Deleted!')),
                                        text: @js(__('The conversation has been removed.')),
                                        icon: 'success',
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 3000,
                                        customClass: {
                                            popup: 'rounded-2xl shadow-xl border-none'
                                        }
                                    });
                                } catch (error) {
                                    console.error('Socius Delete Error:', error);
                                    Swal.fire({
                                        title: @js(__('Error')),
                                        text: error.message,
                                        icon: 'error',
                                        customClass: {
                                            popup: 'rounded-3xl border-none shadow-2xl'
                                        }
                                    });
                                    this.error = error.message;
                                }
                            },

                            async togglePin(threadId) {
                                try {
                                    const response = await fetch(this.threadUrl('pin_toggleTemplate', threadId), {
                                        method: 'POST',
                                        headers: {
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        }
                                    });
                                    const data = await this.parseJsonResponse(response);
                                    const idx = this.threads.findIndex(t => t.id === threadId);
                                    if (idx !== -1) {
                                        this.threads[idx] = data.thread;
                                        this.sortThreads();
                                    }
                                } catch (error) {
                                    this.error = error.message;
                                }
                            },

                            sortThreads() {
                                this.threads.sort((a, b) => {
                                    if (a.is_pinned !== b.is_pinned) return b.is_pinned ? 1 : -1;
                                    return new Date(b.last_activity_at) - new Date(a.last_activity_at);
                                });
                            },

                            copyMessage(content, messageId, btn = null) {
                                const element = document.getElementById(`socius-message-body-${messageId}`);
                                if (element) {
                                    const clone = element.cloneNode(true);

                                    // Remove scripts, styles, helper textareas and control buttons
                                    const controls = clone.querySelectorAll('.visual-header button, .socius-visual-loading, script, style, textarea.visual-source');
                                    controls.forEach(el => el.remove());

                                    // Replace visual graphs/diagrams with inline data summary tables
                                    const visuals = clone.querySelectorAll('.socius-visual');
                                    visuals.forEach(visual => {
                                        const type = visual.dataset.visualType || 'visual';
                                        const visualId = visual.dataset.visualId || '';
                                        const titleEl = visual.querySelector('.visual-header span');
                                        const title = titleEl ? titleEl.innerText : type;

                                        let replacement;

                                        if ((type === 'chartjs' || type === 'chart.js') && visualId) {
                                            // Try to extract chart data from the Chart.js instance
                                            const canvasEl = document.querySelector(`#${visualId} canvas`);
                                            const chartInstance = canvasEl && typeof Chart !== 'undefined'
                                                ? Chart.getChart(canvasEl) : null;

                                            if (chartInstance && chartInstance.data) {
                                                const labels = chartInstance.data.labels || [];
                                                const dataset = chartInstance.data.datasets?.[0] || {};
                                                const values = dataset.data || [];

                                                // Build an HTML summary table
                                                const wrapper = document.createElement('div');
                                                wrapper.style.margin = '12px 0';

                                                const heading = document.createElement('p');
                                                heading.style.fontWeight = 'bold';
                                                heading.style.marginBottom = '6px';
                                                heading.style.fontSize = '13px';
                                                heading.innerText = `Chart: ${title}`;
                                                wrapper.appendChild(heading);

                                                const tbl = document.createElement('table');
                                                tbl.style.borderCollapse = 'collapse';
                                                tbl.style.width = '100%';
                                                tbl.style.fontFamily = 'Arial, sans-serif';
                                                tbl.style.fontSize = '12px';

                                                // Header row
                                                const thead = tbl.createTHead();
                                                const hRow = thead.insertRow();
                                                ['Option', 'Value'].forEach(h => {
                                                    const th = document.createElement('th');
                                                    th.innerText = h;
                                                    th.style.border = '1px solid #d4d4d8';
                                                    th.style.padding = '6px 10px';
                                                    th.style.backgroundColor = '#f4f4f5';
                                                    th.style.fontWeight = 'bold';
                                                    th.style.textAlign = 'left';
                                                    hRow.appendChild(th);
                                                });

                                                // Data rows
                                                const tbody = tbl.createTBody();
                                                labels.forEach((label, i) => {
                                                    const row = tbody.insertRow();
                                                    [label, values[i] ?? ''].forEach(val => {
                                                        const td = row.insertCell();
                                                        td.innerText = val;
                                                        td.style.border = '1px solid #d4d4d8';
                                                        td.style.padding = '6px 10px';
                                                    });
                                                });

                                                wrapper.appendChild(tbl);
                                                replacement = wrapper;
                                            }
                                        }

                                        // Fallback: simple bold label
                                        if (!replacement) {
                                            replacement = document.createElement('p');
                                            replacement.style.fontWeight = 'bold';
                                            replacement.style.color = '#3f3f46';
                                            replacement.style.fontStyle = 'italic';
                                            replacement.innerText = `[${title} — chart not available in this format]`;
                                        }

                                        visual.parentNode.replaceChild(replacement, visual);
                                    });

                                    // Style tables for clipboard pasting to Word/Google Docs
                                    const tables = clone.querySelectorAll('table');
                                    tables.forEach(table => {
                                        table.style.width = '100%';
                                        table.style.borderCollapse = 'collapse';
                                        table.style.margin = '12px 0';

                                        table.querySelectorAll('th, td').forEach(cell => {
                                            cell.style.border = '1px solid #d4d4d8';
                                            cell.style.padding = '8px 12px';
                                            cell.style.textAlign = 'left';
                                        });
                                        table.querySelectorAll('th').forEach(th => {
                                            th.style.backgroundColor = '#f4f4f5';
                                            th.style.fontWeight = 'bold';
                                        });
                                    });

                                    const rawHtml = clone.innerHTML;
                                    const rawText = clone.innerText || clone.textContent;

                                    const blobHtml = new Blob([rawHtml], { type: 'text/html' });
                                    const blobText = new Blob([rawText], { type: 'text/plain' });

                                    navigator.clipboard.write([
                                        new ClipboardItem({
                                            'text/html': blobHtml,
                                            'text/plain': blobText
                                        })
                                    ]).then(() => {
                                        if (btn) {
                                            const original = btn.innerHTML;
                                            btn.innerHTML = '<i class="fa-solid fa-check text-green-400"></i>';
                                            setTimeout(() => { btn.innerHTML = original; }, 2000);
                                        }
                                    }).catch(err => {
                                        console.error('Failed to copy message:', err);
                                        navigator.clipboard.writeText(rawText);
                                    });
                                } else {
                                    navigator.clipboard.writeText(content);
                                }
                            },

                            async selectThread(threadId) {
                                if (threadId === this.currentThreadId) {
                                    return;
                                }

                                await this.loadThread(threadId);
                            },

                            async loadThread(threadId) {
                                this.loadingMessages = true;
                                this.error = null;

                                try {
                                    const response = await fetch(this.threadUrl('showTemplate', threadId), {
                                        headers: { 'Accept': 'application/json' }
                                    });
                                    const data = await this.parseJsonResponse(response);
                                    this.currentThread = data.thread;
                                    this.currentThreadId = data.thread.id;
                                    this.messages = data.messages || [];
                                    this.syncQuery();
                                    this.scrollMessages();
                                } catch (error) {
                                    this.error = error.message;
                                } finally {
                                    this.loadingMessages = false;
                                    this.$nextTick(() => {
                                        const inputEl = document.getElementById('socius-prompt-input');
                                        if (inputEl) inputEl.focus();
                                    });
                                }
                            },

                            pickFiles() {
                                this.$refs.fileInput.click();
                            },

                            handleFileSelection(event) {
                                const selected = Array.from(event.target.files || []);
                                selected.forEach(file => {
                                    const exists = this.pendingFiles.some(existing => existing.name === file.name && existing.size === file.size);
                                    if (!exists) {
                                        this.pendingFiles.push(file);
                                    }
                                });
                                event.target.value = '';
                            },

                            // Phase 4 Methods
                            toggleVoiceInput() {
                                if (this.isListening) {
                                    if (this.recognition) this.recognition.stop();
                                    return;
                                }

                                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                                if (!SpeechRecognition) {
                                    this.error = @js(__('Your browser does not support voice input.'));
                                    return;
                                }

                                if (!this.recognition) {
                                    this.recognition = new SpeechRecognition();
                                    this.recognition.continuous = true;
                                    this.recognition.interimResults = true;
                                    this.recognition.lang = document.documentElement.lang || 'en-US';

                                    this.recognition.onresult = (event) => {
                                        let transcript = '';
                                        for (let i = event.resultIndex; i < event.results.length; i++) {
                                            transcript += event.results[i][0].transcript;
                                        }
                                        this.draft = transcript;
                                    };

                                    this.recognition.onend = () => {
                                        this.isListening = false;
                                    };

                                    this.recognition.onerror = (event) => {
                                        if (event.error !== 'no-speech') {
                                            this.error = @js(__('Voice recognition error: ')) + event.error;
                                        }
                                        this.isListening = false;
                                    };
                                }

                                this.error = null;
                                try {
                                    this.recognition.start();
                                    this.isListening = true;
                                } catch (e) {
                                    console.error("Speech start error:", e);
                                    this.isListening = false;
                                }
                            },

                            async regenerateResponse(messageId) {
                                if (this.sending) return;

                                const idx = this.messages.findIndex(m => m.id === messageId);
                                if (idx <= 0) return;

                                const userMessage = this.messages[idx - 1];
                                if (userMessage.role !== 'user') return;

                                const threadId = this.currentThreadId;
                                const content = userMessage.content;

                                // Optimistically remove following messages
                                this.messages = this.messages.slice(0, idx);

                                await this.sendMessage(content, threadId);
                            },

                            startEditing(messageId, content) {
                                this.editingMessageId = messageId;
                                this.editingContent = content;
                                this.$nextTick(() => {
                                    const el = document.getElementById(`edit-textarea-${messageId}`);
                                    if (el) el.focus();
                                });
                            },

                            cancelEditing() {
                                this.editingMessageId = null;
                                this.editingContent = '';
                            },

                            async submitEdit(messageId) {
                                if (!this.editingContent.trim() || this.sending) return;

                                const idx = this.messages.findIndex(m => m.id === messageId);
                                if (idx === -1) return;

                                const threadId = this.currentThreadId;
                                const newContent = this.editingContent;

                                this.cancelEditing();
                                this.messages = this.messages.slice(0, idx);

                                await this.sendMessage(newContent, threadId);
                            },

                            removePendingFile(index) {
                                this.pendingFiles.splice(index, 1);
                            },

                            async sendMessage(overrideContent = null, overrideThreadId = null) {
                                if (this.sending || !this.canAnalyze) {
                                    return;
                                }

                                const content = (overrideContent !== null ? overrideContent : this.draft).trim();
                                if (!content && this.pendingFiles.length === 0) {
                                    return;
                                }

                                let threadId = overrideThreadId || this.currentThreadId;
                                if (!threadId) {
                                    const thread = await this.createThread(false);
                                    if (!thread) {
                                        return;
                                    }
                                    threadId = thread.id;
                                    this.currentThread = thread;
                                    this.currentThreadId = thread.id;
                                    this.syncQuery();
                                }

                                this.error = null;
                                this.sending = true;
                                this.activeAbortController = new AbortController();

                                const tempUserId = `temp-user-${Date.now()}`;
                                const tempAssistantId = `temp-assistant-${Date.now()}`;
                                const optimisticAttachments = this.pendingFiles.map((file, index) => ({
                                    id: `pending-${index}`,
                                    original_name: file.name,
                                    size_bytes: file.size,
                                    excerpt: this.formatBytes(file.size)
                                }));

                                this.messages.push({
                                    id: tempUserId,
                                    role: 'user',
                                    content,
                                    attachments: optimisticAttachments,
                                    created_at: new Date().toISOString()
                                });
                                this.messages.push({
                                    id: tempAssistantId,
                                    role: 'assistant',
                                    content: '',
                                    attachments: [],
                                    created_at: new Date().toISOString()
                                });
                                this.scrollMessages();

                                const formData = new FormData();
                                formData.append('message', content);
                                formData.append('include_survey_context', this.includeSurveyContext ? '1' : '0');
                                formData.append('web_search_enabled', this.webSearchEnabled ? '1' : '0');
                                formData.append('review_mode_enabled', this.reviewModeEnabled ? '1' : '0');
                                this.pendingFiles.forEach(file => formData.append('attachments[]', file));

                                const usedFiles = [...this.pendingFiles];
                                this.draft = '';
                                this.pendingFiles = [];
                                this.reviewModeEnabled = false;

                                try {
                                    const response = await fetch(this.threadUrl('streamTemplate', threadId), {
                                        method: 'POST',
                                        signal: this.activeAbortController.signal,
                                        headers: {
                                            'Accept': 'text/event-stream',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        },
                                        body: formData
                                    });

                                    if (!response.ok || !response.body) {
                                        if (response.status === 429) {
                                            throw new Error("{{ __('Groq AI Rate Limit Exceeded. Please wait a minute and try again.') }}");
                                        }
                                        const errorData = await this.safeReadJson(response);
                                        throw new Error(errorData?.message || @js(__('Socius could not send this message.')));
                                    }

                                    await this.consumeEventStream(response.body, tempUserId, tempAssistantId);
                                    await this.loadThread(threadId);
                                    await this.reloadThreadList();
                                } catch (error) {
                                    if (error.name === 'AbortError') {
                                        this.error = null;
                                    } else {
                                        this.error = error.message;
                                        const failedAssistantId = this.streamingAssistantId || tempAssistantId;
                                        this.replaceMessage(failedAssistantId, {
                                            id: failedAssistantId,
                                            role: 'assistant',
                                            content: error.message,
                                            attachments: [],
                                            created_at: new Date().toISOString()
                                        });
                                        this.pendingFiles = usedFiles;
                                    }
                                } finally {
                                    this.activeAbortController = null;
                                    this.streamingUserId = null;
                                    this.streamingAssistantId = null;
                                    this.sending = false;
                                    this.$nextTick(() => {
                                        const inputEl = document.getElementById('socius-prompt-input');
                                        if (inputEl) inputEl.focus();
                                    });
                                }
                            },

                            async reloadThreadList() {
                                try {
                                    const url = this.activeGroupId ? `${this.urls.list}?group_id=${this.activeGroupId}` : this.urls.list;
                                    const response = await fetch(url, {
                                        headers: { 'Accept': 'application/json' }
                                    });
                                    const data = await this.parseJsonResponse(response);
                                    this.threads = data.threads || [];
                                } catch (error) {
                                    this.error = error.message;
                                }
                            },

                            async consumeEventStream(stream, tempUserId, tempAssistantId) {
                                const reader = stream.getReader();
                                const decoder = new TextDecoder();
                                let buffer = '';

                                while (true) {
                                    const { value, done } = await reader.read();
                                    if (done) break;

                                    buffer += decoder.decode(value, { stream: true });
                                    let boundaryIndex;

                                    while ((boundaryIndex = buffer.indexOf('\n\n')) !== -1) {
                                        const rawEvent = buffer.slice(0, boundaryIndex).trim();
                                        buffer = buffer.slice(boundaryIndex + 2);

                                        if (rawEvent) {
                                            this.handleStreamEvent(rawEvent, tempUserId, tempAssistantId);
                                        }
                                    }
                                }
                            },

                            handleStreamEvent(rawEvent, tempUserId, tempAssistantId) {
                                const lines = rawEvent.split('\n');
                                let eventName = 'message';
                                let data = {};

                                lines.forEach(line => {
                                    if (line.startsWith('event:')) {
                                        eventName = line.replace('event:', '').trim();
                                    }
                                    if (line.startsWith('data:')) {
                                        try {
                                            data = JSON.parse(line.replace('data:', '').trim());
                                        } catch (error) {
                                            data = {};
                                        }
                                    }
                                });

                                if (eventName === 'meta') {
                                    this.streamingUserId = data.user_message_id || tempUserId;
                                    this.streamingAssistantId = data.assistant_message_id || tempAssistantId;
                                    this.updateMessageId(tempUserId, data.user_message_id);
                                    this.updateMessageId(tempAssistantId, data.assistant_message_id);
                                }

                                if (eventName === 'delta') {
                                    const assistantMessage = this.messages.find(message => message.id === this.streamingAssistantId)
                                        || this.messages.find(message => message.id === tempAssistantId);
                                    if (assistantMessage) {
                                        assistantMessage.content = `${assistantMessage.content || ''}${data.content || ''}`;
                                        this.scrollMessages();
                                    }
                                }

                                if (eventName === 'error') {
                                    throw new Error(data.message || @js(__('Streaming failed.')));
                                }
                            },

                            updateMessageId(oldId, newId) {
                                const target = this.messages.find(message => message.id === oldId);
                                if (target && newId) {
                                    target.id = newId;
                                }
                            },

                            replaceMessage(messageId, replacement) {
                                const index = this.messages.findIndex(message => message.id === messageId);
                                if (index !== -1) {
                                    this.messages.splice(index, 1, replacement);
                                }
                            },

                            threadUrl(key, threadId) {
                                return this.urls[key].replace('__THREAD__', threadId);
                            },

                            syncQuery() {
                                const url = new URL(window.location.href);
                                const currentTab = url.searchParams.get('reportTab') || 'quantitative';
                                if (currentTab !== 'analyse') {
                                    return;
                                }
                                url.searchParams.set('reportTab', 'analyse');
                                if (this.currentThreadId) {
                                    url.searchParams.set('thread', this.currentThreadId);
                                } else {
                                    url.searchParams.delete('thread');
                                }
                                window.history.replaceState({}, '', url);
                            },

                            scrollMessages() {
                                this.$nextTick(() => {
                                    if (this.$refs.messageList) {
                                        this.$refs.messageList.scrollTop = this.$refs.messageList.scrollHeight;
                                    }
                                });
                            },

                            async parseJsonResponse(response) {
                                const data = await this.safeReadJson(response);
                                if (!response.ok) {
                                    throw new Error(data?.message || @js(__('Request failed.')));
                                }
                                return data || {};
                            },

                            async safeReadJson(response) {
                                const text = await response.text();
                                if (!text) {
                                    return null;
                                }
                                try {
                                    return JSON.parse(text);
                                } catch (error) {
                                    return null;
                                }
                            },

                            formatRelativeTime(timestamp) {
                                if (!timestamp) return '{{ __('Just now') }}';
                                const date = new Date(timestamp);
                                if (Number.isNaN(date.getTime())) return '{{ __('Just now') }}';
                                const diffSeconds = Math.round((date.getTime() - Date.now()) / 1000);
                                const rtf = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });

                                if (Math.abs(diffSeconds) < 60) return rtf.format(diffSeconds, 'second');
                                if (Math.abs(diffSeconds) < 3600) return rtf.format(Math.round(diffSeconds / 60), 'minute');
                                if (Math.abs(diffSeconds) < 86400) return rtf.format(Math.round(diffSeconds / 3600), 'hour');
                                return rtf.format(Math.round(diffSeconds / 86400), 'day');
                            },

                            formatBytes(bytes) {
                                if (!bytes) return '0 B';
                                const units = ['B', 'KB', 'MB', 'GB'];
                                let value = bytes;
                                let unitIndex = 0;
                                while (value >= 1024 && unitIndex < units.length - 1) {
                                    value /= 1024;
                                    unitIndex += 1;
                                }
                                return `${value.toFixed(value >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
                            },

                            renderMessage(content, role) {
                                if (role === 'user') {
                                    return this.escapeHtml(content || '').replace(/\n/g, '<br>');
                                }

                                // Check if content is a structured analysis report JSON
                                const trimmed = (content || '').trim();
                                if (trimmed.startsWith('{') && trimmed.endsWith('}')) {
                                    try {
                                        const parsed = JSON.parse(trimmed);
                                        if (parsed.summary || parsed.key_findings || parsed.interpretation || parsed.table) {
                                            return this.renderStructuredReport(parsed);
                                        }
                                    } catch (e) {
                                        // Fallback to standard markdown rendering
                                    }
                                }

                                return this.renderMarkdownLike(content || '');
                            },

                            renderStructuredReport(data) {
                                let html = '<div class="space-y-4 my-3 text-slate-200">';

                                if (data.summary || data.title) {
                                    html += `
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <div class="bg-white/5 rounded-2xl p-4 border border-white/10 space-y-2">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <div class="flex items-center gap-2">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <i class="fa-solid fa-file-lines text-indigo-400 text-xs"></i>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <h5 class="text-xs font-bold text-white tracking-tight">${this.escapeHtml(data.title || 'Summary')}</h5>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <p class="text-xs leading-relaxed text-slate-300">${this.inlineFormat(data.summary || '')}</p>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    `;
                                }

                                if (data.key_findings && Array.isArray(data.key_findings) && data.key_findings.length > 0) {
                                    html += `
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <div class="bg-white/5 rounded-2xl p-4 border border-white/10 space-y-2">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <div class="flex items-center gap-2">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <i class="fa-solid fa-lightbulb text-amber-400 text-xs"></i>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <h5 class="text-xs font-bold text-white tracking-tight">${this.escapeHtml('Key Findings')}</h5>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <ul class="list-disc list-inside space-y-1 text-xs text-slate-300">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                ${data.key_findings.map(f => `<li>${this.inlineFormat(f)}</li>`).join('')}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </ul>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    `;
                                }

                                if (data.table && typeof data.table === 'object') {
                                    const headers = data.table.headers || [];
                                    const rows = data.table.rows || [];
                                    if (headers.length > 0 && rows.length > 0) {
                                        const tableId = `socius-struct-tbl-${Math.random().toString(36).slice(2, 9)}`;
                                        html += `
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <div class="rounded-2xl border border-white/10 overflow-hidden bg-[#1e1e2d]/60 shadow-xl">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <div class="flex items-center justify-between gap-3 px-4 py-2 bg-white/[0.05] border-b border-white/10">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <span class="text-xs font-bold text-white">${this.escapeHtml(data.table.title || 'Data Table')}</span>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <button type="button" onclick="window.copyRenderedSociusTable('${tableId}', this)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white/10 border border-white/10 text-[10px] font-bold text-slate-300 hover:bg-[#2271b1] hover:text-white transition-all">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <i class="fa-regular fa-copy"></i> Copy
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </button>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <div class="overflow-x-auto">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <table id="${tableId}" class="min-w-full text-left text-xs border-collapse">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <thead>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <tr class="bg-white/[0.04] border-b border-white/10">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                ${headers.map(h => `<th class="px-4 py-2.5 text-[11px] font-bold text-indigo-300">${this.escapeHtml(h)}</th>`).join('')}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </tr>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </thead>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <tbody class="divide-y divide-white/5">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ${rows.map(r => `
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <tr class="hover:bg-white/[0.02]">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    ${(Array.isArray(r) ? r : Object.values(r)).map(c => `<td class="px-4 py-2 text-slate-300">${this.inlineFormat(String(c))}</td>`).join('')}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </tr>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            `).join('')}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </tbody>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </table>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        `;
                                    }
                                }

                                if (data.interpretation || data.recommendations) {
                                    html += `
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <div class="bg-white/5 rounded-2xl p-4 border border-white/10 space-y-2">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <div class="flex items-center gap-2">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <i class="fa-solid fa-compass text-emerald-400 text-xs"></i>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <h5 class="text-xs font-bold text-white tracking-tight">${this.escapeHtml('Interpretation & Recommendations')}</h5>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <p class="text-xs leading-relaxed text-slate-300">${this.inlineFormat(data.interpretation || data.recommendations || '')}</p>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    `;
                                }

                                html += '</div>';
                                return html;
                            },

                            escapeHtml(value) {
                                return String(value)
                                    .replace(/&/g, '&amp;')
                                    .replace(/</g, '&lt;')
                                    .replace(/>/g, '&gt;')
                                    .replace(/"/g, '&quot;')
                                    .replace(/'/g, '&#039;');
                            },

                            renderMarkdownLike(text) {
                                const normalized = text.replace(/\r\n/g, '\n');
                                const lines = normalized.split('\n');
                                const blocks = [];
                                let paragraph = [];
                                let listItems = [];
                                let tableLines = [];
                                let inCodeBlock = false;
                                let codeBlockType = '';
                                let codeBlockLines = [];

                                const flushParagraph = () => {
                                    if (paragraph.length) {
                                        blocks.push(`<p class="mb-4">${this.inlineFormat(paragraph.join(' '))}</p>`);
                                        paragraph = [];
                                    }
                                };

                                const flushList = () => {
                                    if (listItems.length) {
                                        blocks.push(`<ol class="list-decimal list-inside space-y-1 mb-4">${listItems.map(item => `<li>${this.inlineFormat(item)}</li>`).join('')}</ol>`);
                                        listItems = [];
                                    }
                                };

                                const flushTable = () => {
                                    if (tableLines.length) {
                                        blocks.push(this.renderMarkdownTable(tableLines));
                                        tableLines = [];
                                    }
                                };

                                const flushCodeBlock = () => {
                                    if (inCodeBlock) {
                                        const content = codeBlockLines.join('\n');
                                        const id = 'visual-' + Math.random().toString(36).substr(2, 9);

                                        if (codeBlockType === 'mermaid' || codeBlockType === 'chartjs' || codeBlockType === 'chart.js' || codeBlockType === 'pollinations') {
                                            const type = codeBlockType === 'chart.js' ? 'chartjs' : codeBlockType;
                                            const isImage = type === 'pollinations';
                                            blocks.push(`
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <div class="socius-visual my-6 bg-white/5 rounded-2xl border border-white/10 overflow-hidden" 
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 data-visual-type="${type}" 
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 data-visual-id="${id}">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <div class="visual-header flex items-center justify-between px-4 py-2 border-b border-white/10 bg-white/5">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <div class="flex gap-2 ml-auto">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <button onclick="window.sociusVisuals.copy('${id}', this)" class="text-[10px] font-bold text-slate-400 hover:text-white transition-colors">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <i class="fa-solid fa-copy mr-1"></i> {{ __('Copy') }}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </button>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <button onclick="window.sociusVisuals.download('${id}', 'png')" class="text-[10px] font-bold text-slate-400 hover:text-white transition-colors">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <i class="fa-solid fa-download mr-1"></i> {{ __('PNG') }}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </button>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <div id="${id}" class="visual-body p-6 flex justify-center overflow-x-auto min-h-[100px] relative">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <textarea class="visual-source hidden">${this.escapeHtml(content)}</textarea>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <div class="visual-target w-full flex justify-center">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        ${isImage ? '<div class="animate-pulse flex flex-col items-center gap-3 p-8"><i class="fa-solid fa-wand-magic-sparkles text-[#3894dc] text-2xl"></i><span class="text-[10px] text-slate-500 font-bold">{{ __('Generating Image...') }}</span></div>' : ''}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        `);
                                        } else {
                                            blocks.push(`<pre class="bg-black/30 p-4 rounded-xl overflow-x-auto text-xs my-4 border border-white/5"><code>${this.escapeHtml(content)}</code></pre>`);
                                        }
                                        inCodeBlock = false;
                                        codeBlockLines = [];
                                        codeBlockType = '';
                                    }
                                };

                                for (let i = 0; i < lines.length; i++) {
                                    const rawLine = lines[i];
                                    const line = rawLine.trim();

                                    const codeBlockMatch = line.match(/^`{3,}(.*)$/);
                                    if (codeBlockMatch) {
                                        if (inCodeBlock) {
                                            flushCodeBlock();
                                        } else {
                                            flushParagraph();
                                            flushList();
                                            flushTable();
                                            inCodeBlock = true;
                                            codeBlockType = codeBlockMatch[1].trim().toLowerCase();
                                        }
                                        continue;
                                    }

                                    if (inCodeBlock) {
                                        codeBlockLines.push(rawLine);
                                        continue;
                                    }

                                    if (!line) {
                                        flushParagraph();
                                        flushList();
                                        flushTable();
                                        continue;
                                    }

                                    if (this.looksLikeMarkdownTableLine(line)) {
                                        flushParagraph();
                                        flushList();
                                        tableLines.push(line);
                                        continue;
                                    }

                                    flushTable();

                                    const headingMatch = line.match(/^#{1,6}\s+(.*)$/);
                                    if (headingMatch) {
                                        flushParagraph();
                                        flushList();
                                        blocks.push(`<h4 class="text-base font-bold text-slate-100 mt-6 mb-3 tracking-tight">${this.inlineFormat(headingMatch[1])}</h4>`);
                                        continue;
                                    }

                                    const orderedMatch = line.match(/^\d+\.\s+(.*)$/);
                                    if (orderedMatch) {
                                        flushParagraph();
                                        listItems.push(orderedMatch[1]);
                                        continue;
                                    }

                                    if (line.startsWith('- ') || line.startsWith('* ')) {
                                        flushParagraph();
                                        listItems.push(line.slice(2));
                                        continue;
                                    }

                                    flushList();
                                    paragraph.push(line);
                                }

                                flushParagraph();
                                flushList();
                                flushTable();

                                if (inCodeBlock) {
                                    if (codeBlockType === 'mermaid' || codeBlockType === 'chartjs' || codeBlockType === 'chart.js' || codeBlockType === 'pollinations') {
                                        const type = codeBlockType === 'chart.js' ? 'chartjs' : codeBlockType;
                                        const isImage = type === 'pollinations';
                                        blocks.push(`
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <div class="socius-visual-loading my-6 bg-white/5 rounded-2xl border border-white/10 border-dashed p-8 text-center animate-pulse">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <i class="fa-solid ${isImage ? 'fa-wand-magic-sparkles' : 'fa-chart-simple'} text-[#3894dc]/50 text-2xl mb-3"></i>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <p class="text-[10px] text-slate-500 font-bold">{{ __('Socius is generating an image...') }}</p>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    `);
                                    } else {
                                        flushCodeBlock();
                                    }
                                }

                                return blocks.join('');
                            },

                            inlineFormat(text) {
                                const escaped = this.escapeHtml(text);
                                return escaped
                                    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                                    .replace(/__(.+?)__/g, '<strong>$1</strong>')
                                    .replace(/\*(.+?)\*/g, '<em>$1</em>')
                                    .replace(/`(.+?)`/g, '<code>$1</code>');
                            },

                            looksLikeMarkdownTableLine(line) {
                                if (!line.includes('|')) return false;
                                const pipeCount = (line.match(/\|/g) || []).length;
                                return pipeCount >= 2;
                            },

                            renderMarkdownTable(lines) {
                                const rows = lines
                                    .filter(line => line !== '')
                                    .map(line => line.replace(/^\|/, '').replace(/\|$/, '').split('|').map(cell => cell.trim()));

                                if (rows.length < 2) {
                                    return `<pre>${lines.join('\n')}</pre>`;
                                }

                                const separatorIndex = rows.findIndex(row => row.every(cell => /^:?-{3,}:?$/.test(cell)));
                                if (separatorIndex !== 1) {
                                    return `<pre>${lines.join('\n')}</pre>`;
                                }

                                const header = rows[0].map(h => {
                                    let cleanH = h;
                                    if (cleanH.toLowerCase() === 'frequency') cleanH = 'Frequency';
                                    if (cleanH.toLowerCase() === 'percentage' || cleanH.toLowerCase() === 'percent') cleanH = 'Percentage (%)';
                                    return cleanH;
                                });
                                const body = [...rows.slice(2)];

                                // Auto-check if Total row is present; if not, calculate and append
                                const hasTotalRow = body.some(row => row[0] && row[0].toLowerCase().includes('total'));
                                if (!hasTotalRow && body.length > 0) {
                                    const totalRow = [];
                                    header.forEach((colName, colIdx) => {
                                        if (colIdx === 0) {
                                            totalRow.push('Total');
                                        } else {
                                            let sum = 0;
                                            let isPercent = colName.includes('%') || colName.toLowerCase().includes('percentage');
                                            let isCount = colName.toLowerCase().includes('freq') || colName.toLowerCase().includes('(n)') || colName.toLowerCase().includes('count');

                                            body.forEach(r => {
                                                const valStr = (r[colIdx] || '').replace(/[^0-9.]/g, '');
                                                const val = parseFloat(valStr);
                                                if (!isNaN(val)) sum += val;
                                            });

                                            if (isPercent) {
                                                totalRow.push('100%');
                                            } else if (isCount) {
                                                totalRow.push(`${Math.round(sum)}`);
                                            } else {
                                                totalRow.push(sum > 0 ? (sum % 1 === 0 ? sum.toFixed(0) : sum.toFixed(1)) : '-');
                                            }
                                        }
                                    });
                                    body.push(totalRow);
                                }

                                const tableId = `socius-table-${Math.random().toString(36).slice(2, 10)}`;

                                return `
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <div class="my-4 rounded-2xl border border-white/10 overflow-hidden bg-[#1e1e2d]/60 shadow-xl">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <div class="flex items-center justify-between gap-3 px-4 py-2.5 bg-white/[0.05] border-b border-white/10">

                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <button type="button" onclick="window.copyRenderedSociusTable('${tableId}', this)" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white/10 border border-white/10 text-[10px] font-bold text-slate-300 hover:bg-[#2271b1] hover:text-white transition-all">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <i class="fa-regular fa-copy text-[10px]"></i>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        {{ __('Copy Table') }}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </button>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <div class="overflow-x-auto">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <table id="${tableId}" class="min-w-full text-left text-xs border-collapse">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <thead>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <tr class="bg-white/[0.04] border-b border-white/10">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                ${header.map(cell => `<th class="px-4 py-3 text-[11px] font-bold text-blue-300 border-b border-white/10 bg-white/[0.03]">${this.inlineFormat(cell)}</th>`).join('')}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </tr>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </thead>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <tbody>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ${body.map((row, rIdx) => {
                                    const isTotal = row[0] && row[0].toLowerCase().includes('total');
                                    const rowBg = isTotal ? 'bg-white/[0.08] font-bold text-blue-200' : (rIdx % 2 === 0 ? 'bg-transparent' : 'bg-white/[0.02]');
                                    return `
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <tr class="${rowBg}">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        ${row.map(cell => `<td class="px-4 py-2.5 border-b border-white/5 ${isTotal ? 'font-bold text-blue-200 border-t border-white/10' : 'text-slate-200'}">${this.inlineFormat(cell)}</td>`).join('')}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </tr>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                `;
                                }).join('')}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </tbody>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </table>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        `;
                            },

                            async renderVisuals() {
                                const visuals = document.querySelectorAll('.socius-visual:not(.rendered)');
                                if (visuals.length === 0) return;

                                if (typeof mermaid !== 'undefined') {
                                    try {
                                        mermaid.initialize({
                                            startOnLoad: false,
                                            theme: 'dark',
                                            securityLevel: 'loose',
                                            fontFamily: 'Inter',
                                            suppressErrorIndicators: true,
                                            logLevel: 4
                                        });
                                    } catch (e) { }
                                }

                                for (const el of Array.from(visuals)) {
                                    const type = el.dataset.visualType;
                                    const id = el.dataset.visualId;
                                    const sourceEl = el.querySelector('.visual-source');
                                    if (!sourceEl) continue;

                                    let source = (sourceEl.value || sourceEl.textContent).trim();
                                    const target = el.querySelector('.visual-target');
                                    if (!target) continue;

                                    console.log(`Socius: Rendering ${type}`, { id, source });

                                    try {
                                        if (type === 'mermaid' && typeof mermaid !== 'undefined') {
                                            if (!source.match(/^(graph|sequenceDiagram|gantt|classDiagram|stateDiagram|erDiagram|journey|pie|quadrantChart|xychart-beta|mindmap|timeline)/i)) {
                                                source = 'graph TD\n' + source;
                                            }

                                            const { svg } = await mermaid.render('svg-' + id, source);
                                            target.innerHTML = svg;
                                            el.classList.add('rendered');
                                        } else if (type === 'chartjs' && typeof Chart !== 'undefined') {
                                            const repairedSource = this.repairJson(source);
                                            const config = JSON.parse(repairedSource);
                                            const canvas = document.createElement('canvas');
                                            target.innerHTML = '';
                                            target.appendChild(canvas);
                                            // Make sure config structure matches percentages config
                                            if (config.data && Array.isArray(config.data.datasets) && config.data.datasets[0]) {
                                                const isMultiDataset = config.data.datasets.length > 1;

                                                if (!isMultiDataset) {
                                                    const dataset = config.data.datasets[0];
                                                    const rawData = Array.isArray(dataset.data) ? dataset.data : [];
                                                    const totalResponses = rawData.reduce((sum, val) => sum + Number(val || 0), 0);
                                                    const percentageData = rawData.map(val =>
                                                        totalResponses > 0 ? parseFloat(((Number(val || 0) / totalResponses) * 100).toFixed(1)) : (typeof val === 'number' ? val : parseFloat(String(val || 0).replace('%', '')) || 0)
                                                    );

                                                    // Apply percentage dataset values
                                                    dataset.data = percentageData;
                                                    if (!dataset.label) dataset.label = 'Percentage (%)';
                                                    if (!dataset.backgroundColor) {
                                                        dataset.backgroundColor = ['#2271b1', '#3894dc', '#6366f1', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6'];
                                                    }

                                                    if (!config.options) config.options = {};
                                                    if (!config.options.plugins) config.options.plugins = {};
                                                    config.options.plugins.legend = { display: false };
                                                }

                                                const chartType = config.type || 'bar';
                                                const isCartesian = ['bar', 'line'].includes(chartType);

                                                if (isCartesian) {
                                                    // Only add datalabels plugin if single-dataset
                                                    if (!isMultiDataset) {
                                                        const datalabelsPlugin = {
                                                            id: 'customDatalabels',
                                                            afterDatasetsDraw(chart) {
                                                                const { ctx } = chart;
                                                                ctx.save();
                                                                chart.data.datasets.forEach((dt, i) => {
                                                                    const meta = chart.getDatasetMeta(i);
                                                                    meta.data.forEach((element, index) => {
                                                                        const rawVal = dt.data[index];
                                                                        const numVal = (typeof rawVal === 'number') ? rawVal : parseFloat(String(rawVal || 0).replace('%', ''));
                                                                        const text = `${isNaN(numVal) ? 0 : (numVal % 1 === 0 ? numVal.toFixed(0) : numVal.toFixed(1))}%`;

                                                                        ctx.fillStyle = (chart.options.scales?.x?.ticks?.color === '#333333' || chart.options.scales?.y?.ticks?.color === '#333333') ? '#333333' : '#e2e8f0';
                                                                        ctx.font = 'bold 9px Inter, sans-serif';

                                                                        if (chart.options.indexAxis === 'y') {
                                                                            ctx.textAlign = 'left';
                                                                            ctx.textBaseline = 'middle';
                                                                            ctx.fillText(text, element.x + 6, element.y);
                                                                        } else {
                                                                            ctx.textAlign = 'center';
                                                                            ctx.textBaseline = 'bottom';
                                                                            ctx.fillText(text, element.x, element.y - 6);
                                                                        }
                                                                    });
                                                                });
                                                                ctx.restore();
                                                            }
                                                        };

                                                        if (!config.plugins) config.plugins = [];
                                                        config.plugins.push(datalabelsPlugin);
                                                    }

                                                    // Set scales
                                                    if (!config.options) config.options = {};
                                                    const isHorizontal = config.options.indexAxis === 'y';

                                                    let valAxisTitle = (config.options?.scales?.[isHorizontal ? 'x' : 'y']?.title?.text) || 'Percentage (%)';
                                                    if (valAxisTitle.toLowerCase() === 'frequency' || valAxisTitle.toLowerCase() === 'count' || valAxisTitle.toLowerCase() === 'freq') {
                                                        valAxisTitle = 'Percentage (%)';
                                                    }

                                                    const valueAxisConfig = {
                                                        beginAtZero: true,
                                                        grace: '12%',
                                                        grid: { color: 'rgba(255, 255, 255, 0.08)', drawBorder: false },
                                                        ticks: {
                                                            font: { weight: '600', size: 10, color: '#94a3b8' },
                                                            callback: function (value) {
                                                                return value + '%';
                                                            }
                                                        },
                                                        title: {
                                                            display: true,
                                                            text: valAxisTitle,
                                                            color: '#94a3b8',
                                                            font: { weight: '600', size: 10 }
                                                        }
                                                    };
                                                    if (config.data && config.data.labels) {
                                                        config.data.labels = config.data.labels.map(lbl => wrapJsChartLabel(lbl, 18));
                                                    }

                                                    const labelAxisConfig = {
                                                        grid: { display: false },
                                                        ticks: {
                                                            font: { weight: '600', size: 10, color: '#94a3b8' },
                                                            maxRotation: 45,
                                                            minRotation: 0,
                                                            autoSkip: true
                                                        },
                                                        title: {
                                                            display: true,
                                                            text: formatShortCategoryTheme(config.short_theme || (config.options?.scales?.[isHorizontal ? 'y' : 'x']?.title?.text) || (config.options.plugins?.title?.text) || config.question_name) || 'Categories',
                                                            color: '#94a3b8',
                                                            font: { weight: '600', size: 10 }
                                                        }
                                                    };

                                                    config.options.scales = {
                                                        y: isHorizontal ? labelAxisConfig : valueAxisConfig,
                                                        x: isHorizontal ? valueAxisConfig : labelAxisConfig
                                                    };
                                                } else {
                                                    // Make sure options has no scales for pie, doughnut, polarArea
                                                    if (config.options) {
                                                        delete config.options.scales;
                                                    }
                                                }

                                                // Tooltip adjustments
                                                if (!config.options.plugins) config.options.plugins = {};
                                                config.options.plugins.tooltip = {
                                                    backgroundColor: '#0f172a',
                                                    padding: 12,
                                                    titleFont: { size: 12, weight: '800' },
                                                    bodyFont: { size: 12, weight: '600' },
                                                    cornerRadius: 12,
                                                    displayColors: true,
                                                    callbacks: {
                                                        label: function (context) {
                                                            const index = context.dataIndex;
                                                            const rawVal = rawData[index] || 0;
                                                            return ` ${rawVal} (${context.raw}%)`;
                                                        }
                                                    }
                                                };
                                            }

                                            if (!config.options) config.options = {};
                                            config.options.responsive = true;
                                            config.options.maintainAspectRatio = false;

                                            new Chart(canvas, config);
                                            canvas.style.maxHeight = '400px';
                                            el.classList.add('rendered');
                                        } else if (type === 'pollinations') {
                                            // Queue this image - don't fire immediately
                                            if (!window._sociusImageQueue) {
                                                window._sociusImageQueue = [];
                                                window._sociusImageProcessing = false;
                                            }

                                            const rawPrompt = source.replace(/```/g, '').trim();
                                            // Simplify prompt: strip verbose suffixes that slow generation
                                            const prompt = rawPrompt
                                                .replace(/,?\s*(cinematic lighting|8k resolution|photorealistic|ultra detailed|high quality|4k|hdr)/gi, '')
                                                .trim()
                                                .substring(0, 200); // Keep prompts short

                                            window._sociusImageQueue.push({ prompt, target, el });
                                            el.classList.add('rendered'); // Mark as handled so renderVisuals doesn't re-process

                                            // Start processing queue if not already running
                                            if (!window._sociusImageProcessing) {
                                                this.processImageQueue();
                                            }
                                        }
                                    } catch (e) {
                                        console.error(`Socius Visual Error [${type}]:`, e);
                                        target.innerHTML = `<div class="text-red-400/60 text-[10px] font-bold p-4 bg-red-500/10 rounded-xl border border-red-500/20">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> 
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        {{ __('Invalid visual syntax.') }}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </div>`;
                                        el.classList.add('rendered');
                                    }
                                }
                            },

                            repairJson(str) {
                                let cleaned = str;

                                // If it starts/ends with markdown code fences, remove them
                                cleaned = cleaned.replace(/^```(json)?\n?/i, '').replace(/```$/i, '').trim();

                                cleaned = cleaned
                                    .replace(/\/\*[\s\S]*?\*\/|([^\\:]|^)\/\/.*$/gm, '$1') // Remove comments
                                    .replace(/,\s*([}\]])/g, '$1'); // Remove trailing commas

                                return cleaned.trim();
                            },

                            async processImageQueue() {
                                window._sociusImageProcessing = true;

                                while (window._sociusImageQueue.length > 0) {
                                    const { prompt, target, el } = window._sociusImageQueue.shift();

                                    target.innerHTML = `<div class="animate-pulse flex flex-col items-center gap-3 p-8">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <i class="fa-solid fa-wand-magic-sparkles fa-bounce text-indigo-400 text-2xl"></i>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <span class="text-[10px] text-slate-500  tracking-widest font-bold">{{ __('Visualizing Analysis...') }}</span>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </div>`;

                                    await this.loadSingleImage(prompt, target, el);
                                    await new Promise(r => setTimeout(r, 1000));
                                }

                                window._sociusImageProcessing = false;
                            },

                            loadSingleImage(prompt, target, el) {
                                return new Promise(async (resolve) => {
                                    try {
                                        const res = await fetch(`{{ route('surveys.analyse.image.generate', $survey) }}`, {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                            },
                                            body: JSON.stringify({ prompt })
                                        });

                                        if (!res.ok) throw new Error('API error');

                                        const contentType = res.headers.get('Content-Type');
                                        let imgSrc = '';

                                        if (contentType && contentType.includes('image/')) {
                                            const blob = await res.blob();
                                            imgSrc = URL.createObjectURL(blob);
                                        } else {
                                            const data = await res.json();
                                            imgSrc = data.url || data.fallback_url;
                                        }

                                        if (imgSrc) {
                                            const img = new Image();
                                            img.className = 'rounded-xl max-w-full h-auto shadow-2xl transition-opacity duration-500 opacity-0';
                                            img.src = imgSrc;
                                            img.onload = () => {
                                                target.innerHTML = '';
                                                target.appendChild(img);
                                                void img.offsetWidth;
                                                img.classList.remove('opacity-0');
                                                resolve();
                                            };
                                            img.onerror = () => {
                                                target.innerHTML = `<div class="p-6 text-center bg-slate-800/40 rounded-xl border border-slate-700/30">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <i class="fa-solid fa-triangle-exclamation text-amber-500/50 text-xl mb-2"></i>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <p class="text-[10px] text-slate-400  font-bold tracking-widest">{{ __('Image Source Unreachable') }}</p>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </div>`;
                                                resolve();
                                            };
                                        }
                                    } catch (e) {
                                        console.error('Image load failed:', e);
                                        target.innerHTML = `<div class="p-6 text-center bg-slate-800/40 rounded-xl">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <p class="text-[9px] text-slate-500">{{ __('Visualization failed to render') }}</p>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </div>`;
                                        resolve();
                                    }
                                });
                            },

                            async loadKbRules() {
                                this.loadingKb = true;
                                try {
                                    const response = await fetch(this.urls.kbList, {
                                        headers: { 'Accept': 'application/json' }
                                    });
                                    const data = await this.parseJsonResponse(response);
                                    this.kbRules = data.rules || [];
                                } catch (error) {
                                    console.error('Failed to load KB rules', error);
                                } finally {
                                    this.loadingKb = false;
                                }
                            },

                            async addKbRule() {
                                if (!this.newKbRuleContent || !this.newKbRuleContent.trim()) return;
                                this.savingKb = true;
                                try {
                                    const response = await fetch(this.urls.kbStore, {
                                        method: 'POST',
                                        headers: {
                                            'Accept': 'application/json',
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        },
                                        body: JSON.stringify({ content: this.newKbRuleContent.trim() })
                                    });
                                    const data = await this.parseJsonResponse(response);

                                    if (response.ok) {
                                        this.newKbRuleContent = '';

                                        if (typeof this.loadKbRules === 'function') {
                                            await this.loadKbRules();//pull stored rules
                                        } else if (data.rules) {
                                            this.kbRules = data.rules;
                                        } else if (data.rule) {
                                            this.kbRules = [data.rule, ...this.kbRules];//fallback
                                        }

                                        Swal.fire({
                                            title: @js(__('Memory Updated!')),
                                            text: data.message || @js(__('Instruction added.')),
                                            icon: 'success',
                                            toast: true,
                                            position: 'top-end',
                                            showConfirmButton: false,
                                            timer: 2500,
                                            timerProgressBar: true,
                                            customClass: {
                                                popup: 'rounded-2xl shadow-xl border-none',
                                                title: 'test-sm font-bold text-emerald-800 dark:text-emerald-400',
                                                htmlContainer: 'text-xs text-gray-800 dark:text-gray-300',
                                            }
                                        });
                                    }
                                } catch (error) {
                                    Swal.fire({
                                        title: @js(__('Error')),
                                        text: error.message,
                                        icon: 'error',
                                        customClass: { popup: 'rounded-3xl border-none shadow-2xl' }
                                    });
                                } finally {
                                    this.savingKb = false;
                                }
                            },

                            applyKbRuleToPrompt(rule) {
                                if (!rule || !rule.content) return;
                                const cleanText = rule.content.replace(/^\[(Quantitative|Qualitative|Inferential|Socius|General|Book\/Doc|Doc:[^\]]+)\]\s*/i, '');
                                const inputEl = document.getElementById('socius-prompt-input');
                                if (inputEl) {
                                    inputEl.value = cleanText;
                                    inputEl.dispatchEvent(new Event('input', { bubbles: true }));
                                    inputEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    inputEl.focus();
                                }
                                this.kbModalOpen = false;
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        title: @js(__('Instruction Loaded')),
                                        text: @js(__('Instruction ready in Socius chat prompt.')),
                                        icon: 'success',
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 2000
                                    });
                                }
                            },

                            async toggleKbRule(rule) {
                                const originalState = rule.is_active;
                                const newState = !originalState;
                                rule.is_active = newState; // optimistic update

                                try {
                                    const url = this.urls.kbUpdateTemplate.replace('__KB__', rule.id);
                                    const response = await fetch(url, {
                                        method: 'PATCH',
                                        headers: {
                                            'Accept': 'application/json',
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        },
                                        body: JSON.stringify({ is_active: newState })
                                    });
                                    const data = await this.parseJsonResponse(response);
                                    if (data.rule) {
                                        rule.is_active = data.rule.is_active;
                                    }
                                } catch (error) {
                                    rule.is_active = originalState; // rollback
                                    Swal.fire({
                                        title: @js(__('Error')),
                                        text: error.message,
                                        icon: 'error',
                                        customClass: { popup: 'rounded-3xl border-none shadow-2xl' }
                                    });
                                }
                            },

                            async deleteKbRule(ruleId) {
                                const result = await Swal.fire({
                                    title: @js(__('Delete Preference?')),
                                    text: @js(__('This preference will no longer apply to future chat answers.')),
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#ef4444',
                                    cancelButtonColor: '#6b7280',
                                    confirmButtonText: @js(__('Yes, Delete It')),
                                    cancelButtonText: @js(__('Cancel')),
                                    customClass: {
                                        popup: 'rounded-3xl border-none shadow-2xl',
                                        title: 'text-2xl font-black tracking-tight text-gray-900',
                                        confirmButton: 'rounded-xl px-6 py-2.5 text-xs font-black  tracking-widest',
                                        cancelButton: 'rounded-xl px-6 py-2.5 text-xs font-black  tracking-widest'
                                    }
                                });

                                if (!result.isConfirmed) return;

                                try {
                                    const url = this.urls.kbDestroyTemplate.replace('__KB__', ruleId);
                                    const response = await fetch(url, {
                                        method: 'DELETE',
                                        headers: {
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        }
                                    });
                                    const data = await this.parseJsonResponse(response);
                                    this.kbRules = this.kbRules.filter(r => r.id !== ruleId);
                                    Swal.fire({
                                        title: @js(__('Deleted!')),
                                        text: data.message || @js(__('Preference removed.')),
                                        icon: 'success',
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 2000,
                                        customClass: { popup: 'rounded-2xl shadow-xl border-none' }
                                    });
                                } catch (error) {
                                    Swal.fire({
                                        title: @js(__('Error')),
                                        text: error.message,
                                        icon: 'error',
                                        customClass: { popup: 'rounded-3xl border-none shadow-2xl' }
                                    });
                                }
                            },

                            formatDocName(content) {
                                if (!content) return 'Uploaded Document';
                                const match = content.match(/^\[(?:Book\/Doc|Doc):\s*(.*?)\]/i);
                                return match ? match[1] : 'Uploaded Document';
                            },

                            async deactivateAllKbRules() {
                                try {
                                    const response = await fetch('/socius/knowledge-base/deactivate-all', {
                                        method: 'POST',
                                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                                    });
                                    const data = await this.parseJsonResponse(response);
                                    this.kbRules.forEach(r => r.is_active = false);
                                    Swal.fire({ title: @js(__('All Deactivated')), text: data.message, icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
                                } catch (e) { Swal.fire({ title: @js(__('Error')), text: e.message, icon: 'error', customClass: { popup: 'rounded-3xl border-none shadow-2xl' } }); }
                            },

                            async deleteAllKbRules() {
                                const result = await Swal.fire({
                                    title: @js(__('Delete ALL Instructions?')),
                                    text: @js(__('CRITICAL: This will permanently remove all Knowledge Base instructions and uploaded documents.')),
                                    icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280',
                                    confirmButtonText: @js(__('Yes, Delete All')), cancelButtonText: @js(__('Cancel')),
                                    customClass: { popup: 'rounded-3xl border-none shadow-2xl' }
                                });
                                if (!result.isConfirmed) return;
                                try {
                                    const response = await fetch('/socius/knowledge-base/delete-all', {
                                        method: 'DELETE',
                                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                                    });
                                    const data = await this.parseJsonResponse(response);
                                    this.kbRules = [];
                                    Swal.fire({ title: @js(__('All Deleted!')), text: data.message, icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
                                } catch (e) { Swal.fire({ title: @js(__('Error')), text: e.message, icon: 'error', customClass: { popup: 'rounded-3xl border-none shadow-2xl' } }); }
                            },

                            humanizeMessage(content) {
                                if (!content) return;
                                window.location.href = "{{ route('humanizer.index') }}?text=" + encodeURIComponent(content);
                            },

                            transferToHumanizer(content) {
                                if (!content) return;
                                this.humanizerOriginal = content;
                                this.switchReportTab('humanizer');
                            },

                            async rateMessage(messageId, rating) {
                                try {
                                    const response = await fetch(`/socius/chat/messages/${messageId}/rate`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        },
                                        body: JSON.stringify({ rating: rating })
                                    });
                                    const data = await response.json();
                                    if (data.success) {
                                        const msg = this.messages.find(m => m.id === messageId);
                                        if (msg) {
                                            if (!msg.metadata) msg.metadata = {};
                                            msg.metadata.rating = rating;
                                        }
                                        Swal.fire({
                                            title: rating === 'like' ? @js(__('Feedback Received 👍')) : @js(__('Feedback Received 👎')),
                                            text: data.message,
                                            icon: 'success',
                                            toast: true,
                                            position: 'top-end',
                                            showConfirmButton: false,
                                            timer: 500,
                                            customClass: { popup: 'rounded-2xl shadow-xl border-none' }
                                        });
                                    }
                                } catch (e) {
                                    console.error('Rate message failed', e);
                                }
                            },

                            async uploadKbDocument(event) {
                                const file = event.target.files[0];
                                if (!file) return;
                                this.uploadingKbDoc = true;
                                const formData = new FormData();
                                formData.append('document', file);
                                try {
                                    const response = await fetch('/socius/knowledge-base/upload', {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                            'Accept': 'application/json'
                                        },
                                        body: formData
                                    });
                                    const data = await response.json();
                                    if (!response.ok) throw new Error(data.message || 'Upload failed');
                                    await this.loadKbRules();
                                    Swal.fire({ title: 'Knowledge Integrated!', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 4500, timerProgressBar: true, customClass: { popup: 'rounded-2xl shadow-xl border-none' } });
                                } catch (e) {
                                    Swal.fire({ title: 'Upload Error', text: e.message, icon: 'error', customClass: { popup: 'rounded-3xl border-none shadow-2xl' } });
                                } finally {
                                    this.uploadingKbDoc = false;
                                    event.target.value = '';
                                }
                            }
                        }
                    };

                    window.sociusVisuals = {
                        async copy(id, btn = null) {
                            const target = document.querySelector(`#${id} .visual-target`);
                            if (!target) return;

                            const canvas = target.querySelector('canvas');
                            if (canvas && typeof Chart !== 'undefined') {
                                const chartInstance = Chart.getChart(canvas);
                                if (chartInstance) {
                                    const originalScaleXColor = chartInstance.options.scales?.x?.ticks?.color;
                                    const originalScaleYColor = chartInstance.options.scales?.y?.ticks?.color;
                                    const originalLegendColor = chartInstance.options.plugins?.legend?.labels?.color;
                                    const originalTitleColor = chartInstance.options.plugins?.title?.color;

                                    if (chartInstance.options.scales?.x?.ticks) chartInstance.options.scales.x.ticks.color = '#333333';
                                    if (chartInstance.options.scales?.y?.ticks) chartInstance.options.scales.y.ticks.color = '#333333';
                                    if (chartInstance.options.plugins?.legend?.labels) chartInstance.options.plugins.legend.labels.color = '#333333';
                                    if (chartInstance.options.plugins?.title) chartInstance.options.plugins.title.color = '#333333';

                                    chartInstance.update('none');

                                    const tempCanvas = document.createElement('canvas');
                                    tempCanvas.width = canvas.width;
                                    tempCanvas.height = canvas.height;
                                    const tempCtx = tempCanvas.getContext('2d');
                                    tempCtx.fillStyle = '#ffffff';
                                    tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
                                    tempCtx.drawImage(canvas, 0, 0);

                                    if (chartInstance.options.scales?.x?.ticks) chartInstance.options.scales.x.ticks.color = originalScaleXColor;
                                    if (chartInstance.options.scales?.y?.ticks) chartInstance.options.scales.y.ticks.color = originalScaleYColor;
                                    if (chartInstance.options.plugins?.legend?.labels) chartInstance.options.plugins.legend.labels.color = originalLegendColor;
                                    if (chartInstance.options.plugins?.title) chartInstance.options.plugins.title.color = originalTitleColor;

                                    chartInstance.update('none');

                                    tempCanvas.toBlob(async (blob) => {
                                        if (!blob) return;
                                        try {
                                            await navigator.clipboard.write([
                                                new ClipboardItem({ 'image/png': blob })
                                            ]);
                                            if (btn) {
                                                const original = btn.innerHTML;
                                                btn.innerHTML = '<i class="fa-solid fa-check mr-1 text-green-400"></i> Copied';
                                                setTimeout(() => { btn.innerHTML = original; }, 2000);
                                            }
                                        } catch (err) {
                                            console.error('Copy chart failed:', err);
                                        }
                                    }, 'image/png');
                                    return;
                                }
                            }

                            if (typeof htmlToImage === 'undefined') return;

                            const styleSheetsFilter = (sheet) => {
                                try {
                                    const rules = sheet.cssRules;
                                    return true;
                                } catch (e) {
                                    return false;
                                }
                            };

                            const orgConsoleError = console.error;
                            console.error = function (...args) {
                                if (args[0] && typeof args[0] === 'string' && args[0].includes('cssRules')) {
                                    return;
                                }
                                orgConsoleError.apply(console, args);
                            };

                            try {
                                const dataUrl = await htmlToImage.toPng(target, {
                                    backgroundColor: '#ffffff',
                                    style: { padding: '20px', color: '#111111' },
                                    styleSheetsFilter
                                });

                                const response = await fetch(dataUrl);
                                const blob = await response.blob();
                                await navigator.clipboard.write([
                                    new ClipboardItem({ 'image/png': blob })
                                ]);

                                if (btn) {
                                    const original = btn.innerHTML;
                                    btn.innerHTML = '<i class="fa-solid fa-check mr-1 text-green-400"></i> Copied';
                                    setTimeout(() => { btn.innerHTML = original; }, 2000);
                                }
                            } catch (e) {
                                console.error('Copy failed:', e);
                            } finally {
                                console.error = orgConsoleError;
                            }
                        },
                        async download(id, format) {
                            const target = document.querySelector(`#${id} .visual-target`);
                            if (!target) return;

                            const canvas = target.querySelector('canvas');
                            if (canvas && typeof Chart !== 'undefined') {
                                const chartInstance = Chart.getChart(canvas);
                                if (chartInstance) {
                                    const originalScaleXColor = chartInstance.options.scales?.x?.ticks?.color;
                                    const originalScaleYColor = chartInstance.options.scales?.y?.ticks?.color;
                                    const originalLegendColor = chartInstance.options.plugins?.legend?.labels?.color;
                                    const originalTitleColor = chartInstance.options.plugins?.title?.color;

                                    if (chartInstance.options.scales?.x?.ticks) chartInstance.options.scales.x.ticks.color = '#333333';
                                    if (chartInstance.options.scales?.y?.ticks) chartInstance.options.scales.y.ticks.color = '#333333';
                                    if (chartInstance.options.plugins?.legend?.labels) chartInstance.options.plugins.legend.labels.color = '#333333';
                                    if (chartInstance.options.plugins?.title) chartInstance.options.plugins.title.color = '#333333';

                                    chartInstance.update('none');

                                    const tempCanvas = document.createElement('canvas');
                                    tempCanvas.width = canvas.width;
                                    tempCanvas.height = canvas.height;
                                    const tempCtx = tempCanvas.getContext('2d');
                                    tempCtx.fillStyle = '#ffffff';
                                    tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
                                    tempCtx.drawImage(canvas, 0, 0);

                                    if (chartInstance.options.scales?.x?.ticks) chartInstance.options.scales.x.ticks.color = originalScaleXColor;
                                    if (chartInstance.options.scales?.y?.ticks) chartInstance.options.scales.y.ticks.color = originalScaleYColor;
                                    if (chartInstance.options.plugins?.legend?.labels) chartInstance.options.plugins.legend.labels.color = originalLegendColor;
                                    if (chartInstance.options.plugins?.title) chartInstance.options.plugins.title.color = originalTitleColor;

                                    chartInstance.update('none');

                                    const dataUrl = tempCanvas.toDataURL('image/png');
                                    const link = document.createElement('a');
                                    link.download = `socius-visual-${id}.png`;
                                    link.href = dataUrl;
                                    link.click();
                                    return;
                                }
                            }

                            if (typeof htmlToImage === 'undefined') return;

                            const styleSheetsFilter = (sheet) => {
                                try {
                                    const rules = sheet.cssRules;
                                    return true;
                                } catch (e) {
                                    return false;
                                }
                            };

                            const orgConsoleError = console.error;
                            console.error = function (...args) {
                                if (args[0] && typeof args[0] === 'string' && args[0].includes('cssRules')) {
                                    return;
                                }
                                orgConsoleError.apply(console, args);
                            };

                            try {
                                const dataUrl = await htmlToImage.toPng(target, {
                                    backgroundColor: '#ffffff',
                                    style: { padding: '20px', color: '#111111' },
                                    styleSheetsFilter
                                });

                                const link = document.createElement('a');
                                link.download = `socius-visual-${id}.png`;
                                link.href = dataUrl;
                                link.click();
                            } catch (e) {
                                console.error('Download failed:', e);
                            } finally {
                                console.error = orgConsoleError;
                            }
                        }
                    };
                </script>
                <style>
                    .socius-prose p {
                        margin: 0 0 1rem;
                    }

                    .socius-prose h4 {
                        margin: 1rem 0 0.75rem;
                        font-size: 1rem;
                        font-weight: 800;
                        letter-spacing: 0.04em;
                    }

                    .socius-prose ol {
                        margin: 0 0 1rem 1.25rem;
                        padding: 0;
                    }

                    .socius-prose li {
                        margin: 0 0 0.5rem;
                    }

                    .socius-prose code {
                        background: rgba(255, 255, 255, 0.08);
                        border: 1px solid rgba(255, 255, 255, 0.08);
                        border-radius: 0.5rem;
                        padding: 0.125rem 0.375rem;
                        font-size: 0.875em;
                    }

                    .socius-prose pre {
                        white-space: pre-wrap;
                        background: rgba(255, 255, 255, 0.04);
                        border: 1px solid rgba(255, 255, 255, 0.08);
                        border-radius: 1rem;
                        padding: 1rem;
                        overflow-x: auto;
                        margin: 0 0 1rem;
                    }

                    .socius-visual .visual-target svg {
                        max-width: 100%;
                        height: auto !important;
                    }
                </style>
            @endpush

        </div>
    </div>

    <!-- Floating Scroll Control Stack (Available across quantitative, qualitative & inferential tabs) -->
    <div x-show="reportTab !== 'analyse' && reportTab !== 'humanizer'"
        x-data="{ 
                                                                                                                                                                showTop: false, 
                                                                                                                                                                showBottom: false,
                                                                                                                                                                getScrollContainer() {
                                                                                                                                                                    return document.getElementById('main-viewport') || 
                                                                                                                                                                           document.querySelector('.content-pane') || 
                                                                                                                                                                           document.querySelector('main') || 
                                                                                                                                                                           document.documentElement;
                                                                                                                                                                },
                                                                                                                                                                check() {
                                                                                                                                                                    const p = this.getScrollContainer();
                                                                                                                                                                    const scrollTop = Math.max(
                                                                                                                                                                        window.pageYOffset || 0,
                                                                                                                                                                        document.documentElement.scrollTop || 0,
                                                                                                                                                                        document.body.scrollTop || 0,
                                                                                                                                                                        p ? (p.scrollTop || 0) : 0
                                                                                                                                                                    );
                                                                                                                                                                    const scrollHeight = Math.max(
                                                                                                                                                                        document.documentElement.scrollHeight || 0,
                                                                                                                                                                        document.body.scrollHeight || 0,
                                                                                                                                                                        p ? (p.scrollHeight || 0) : 0
                                                                                                                                                                    );
                                                                                                                                                                    const clientHeight = window.innerHeight || (p ? p.clientHeight : 0) || document.documentElement.clientHeight || 0;

                                                                                                                                                                    this.showTop = scrollTop > 150; 
                                                                                                                                                                    this.showBottom = (scrollTop + clientHeight) < (scrollHeight - 150);
                                                                                                                                                                },
                                                                                                                                                                scrollToTop() {
                                                                                                                                                                    window.scrollTo({ top: 0, behavior: 'smooth' });
                                                                                                                                                                    const p = this.getScrollContainer();
                                                                                                                                                                    if (p && p.scrollTo) p.scrollTo({ top: 0, behavior: 'smooth' });
                                                                                                                                                                },
                                                                                                                                                                scrollToBottom() {
                                                                                                                                                                    const scrollHeight = Math.max(
                                                                                                                                                                        document.documentElement.scrollHeight || 0,
                                                                                                                                                                        document.body.scrollHeight || 0
                                                                                                                                                                    );
                                                                                                                                                                    window.scrollTo({ top: scrollHeight, behavior: 'smooth' });
                                                                                                                                                                    const p = this.getScrollContainer();
                                                                                                                                                                    if (p && p.scrollTo) {
                                                                                                                                                                        p.scrollTo({ top: p.scrollHeight || scrollHeight, behavior: 'smooth' });
                                                                                                                                                                    }
                                                                                                                                                                }
                                                                                                                                                            }"
        x-init="$nextTick(() => {
                                                                                                                                                                check();
                                                                                                                                                                const p = getScrollContainer();
                                                                                                                                                                if (p) p.addEventListener('scroll', () => check(), { passive: true });
                                                                                                                                                                window.addEventListener('resize', () => check(), { passive: true });
                                                                                                                                                                window.addEventListener('scroll', () => check(), { passive: true });
                                                                                                                                                            })" @scroll.window.throttle.50ms="check()"
        class="fixed bottom-6 right-6 z-[9999] flex flex-col gap-2">

        <!-- TOP BUTTON -->
        <button x-show="showTop" x-transition @click="scrollToTop()"
            class="w-11 h-11 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-full shadow-2xl flex items-center justify-center transition-all cursor-pointer border-2 border-white"
            title="{{ __('Scroll to top') }}">
            <i class="fa-solid fa-arrow-up text-sm"></i>
        </button>

        <!-- BOTTOM BUTTON -->
        <button x-show="showBottom" x-transition @click="scrollToBottom()"
            class="w-11 h-11 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-full shadow-2xl flex items-center justify-center transition-all cursor-pointer border-2 border-white"
            title="{{ __('Scroll to bottom') }}">
            <i class="fa-solid fa-arrow-down text-sm"></i>
        </button>
    </div>

    <!-- Reports Knowledge Base Modal -->
    <div x-show="showKbModal" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
        @keydown.escape.window="showKbModal = false" style="display: none;">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl border border-gray-100 space-y-6 animate-in fade-in zoom-in-95 duration-200"
            @click.outside="showKbModal = false">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-2xl bg-[#2271b1]/10 text-[#2271b1] flex items-center justify-center font-bold">
                        <i class="fa-solid fa-book-bookmark text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900"
                            x-text="kbScope === 'qualitative' ? '{{ __('Qualitative Knowledge Base') }}' : (kbScope === 'inferential' ? '{{ __('Inferential Statistics Knowledge Base') }}' : '{{ __('Quantitative Knowledge Base') }}')">
                        </h3>
                        <p class="text-xs text-gray-500"
                            x-text="kbScope === 'qualitative' ? '{{ __('Rules and formatting preferences applied to qualitative analysis.') }}' : (kbScope === 'inferential' ? '{{ __('Rules and guidelines applied to statistical hypothesis testing & inferential AI interpretations.') }}' : '{{ __('Rules and formatting preferences applied to numerical and statistical analysis.') }}')">
                        </p>
                    </div>
                </div>
                <button type="button" @click="showKbModal = false"
                    class="w-8 h-8 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center transition-colors cursor-pointer">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Add Rule Input -->
            <div class="space-y-2">
                <label class="text-xs font-bold text-gray-700 flex items-center justify-between">
                    <span>{{ __('Add New Instruction') }}</span>
                    <span class="text-[10px] text-gray-400 font-normal">{{ __('Press Enter to save') }}</span>
                </label>
                <div class="relative flex items-center">
                    <input type="text" x-model="newKbInstruction" :disabled="addingKb"
                        :placeholder="kbScope === 'qualitative' ? '{{ __('e.g., Focus on sentiment nuance, use APA 7 format, highlight outlier themes...') }}' : (kbScope === 'inferential' ? '{{ __('e.g., Report effect size (Cohen d), discuss null hypothesis, use APA 7 reporting...') }}' : '{{ __('e.g., Format p-values to 3 decimals, report effect size, use APA 7 tables...') }}')"
                        @keydown.enter.prevent="addKbRule()"
                        class="w-full bg-gray-50 border border-gray-200 text-xs rounded-xl px-3.5 py-2.5 pr-10 focus:bg-white focus:ring-1 focus:ring-[#2271b1] focus:border-[#2271b1] focus:outline-none transition-all" />
                    <div x-show="addingKb" class="absolute right-3 text-[#2271b1]" style="display: none;">
                        <i class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                    </div>
                </div>
            </div>

            <!-- Rules List -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold text-gray-700 flex items-center gap-2">
                        <span>{{ __('Saved Instructions') }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600"
                            x-text="filteredKbRules.length"></span>
                    </h4>
                </div>

                <div class="max-h-[320px] overflow-y-auto custom-scrollbar space-y-2 pr-1">
                    <template x-if="loadingKb">
                        <div class="space-y-2 py-4">
                            <div class="h-12 bg-gray-100 rounded-xl animate-pulse"></div>
                            <div class="h-12 bg-gray-100 rounded-xl animate-pulse"></div>
                        </div>
                    </template>

                    <template x-if="!loadingKb && filteredKbRules.length === 0">
                        <div class="text-center py-8 bg-gray-50/60 rounded-2xl border border-dashed border-gray-200">
                            <i class="fa-regular fa-lightbulb text-gray-400 text-2xl mb-2"></i>
                            <p class="text-xs font-semibold text-gray-700">
                                {{ __('No saved instructions for this tab.') }}
                            </p>
                            <p class="text-[11px] text-gray-400 mt-0.5">
                                {{ __('Add instructions above or apply a review to automatically save.') }}
                            </p>
                        </div>
                    </template>

                    <template x-for="rule in filteredKbRules" :key="rule.id">
                        <div
                            class="flex items-center justify-between gap-3 p-3 bg-gray-50/70 hover:bg-gray-50 border border-gray-100 rounded-xl transition-all group/kb">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <button type="button" @click="toggleKbRule(rule)"
                                    class="relative inline-flex h-4 w-7 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="rule.is_active ? 'bg-[#2271b1]' : 'bg-gray-300'">
                                    <span
                                        class="pointer-events-none inline-block h-3 w-3 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                        :class="rule.is_active ? 'translate-x-3' : 'translate-x-0'"></span>
                                </button>
                                <span class="text-xs font-medium text-gray-800 break-words"
                                    :class="{ 'line-through text-gray-400': !rule.is_active }"
                                    x-text="rule.content.replace(/^\[(Quantitative|Qualitative|Inferential|Socius|General)\]\s*/i, '')"></span>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button type="button" @click="applySingleKbRule(rule)"
                                    class="px-2.5 py-1 bg-[#2271b1]/10 hover:bg-[#2271b1] text-[#2271b1] hover:text-white rounded-lg text-[11px] font-bold transition-all flex items-center gap-1 cursor-pointer shadow-2xs"
                                    title="{{ __('Apply this specific instruction immediately') }}">
                                    <i class="fa-solid fa-wand-magic-sparkles text-[10px]"></i>
                                    <span>{{ __('Apply Now') }}</span>
                                </button>
                                <button type="button" @click="deleteKbRule(rule.id)"
                                    class="text-gray-400 hover:text-red-500 p-1.5 transition-colors cursor-pointer"
                                    title="{{ __('Delete Rule') }}">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Footer -->
            <div class="border-t border-gray-100 pt-4 flex justify-end">
                <button type="button" @click="showKbModal = false"
                    class="px-5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-all">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </div>
    </div>
@endsection