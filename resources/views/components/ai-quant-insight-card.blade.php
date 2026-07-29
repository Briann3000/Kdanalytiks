@props(['questionId', 'surveyId', 'stats'])

<div x-data="quantInsightCard('{{ $questionId }}', '{{ $surveyId }}')" x-init="init()"
    class="bg-gradient-to-br from-white to-zinc-100/30 rounded-3xl p-6 border border-zinc-200 shadow-sm mt-6 min-h-[100px] flex flex-col justify-center">
    <div class="flex items-start gap-4">
        <div class="flex-shrink-0">

        </div>
        <div class="flex-1 w-full overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <h5 class="text-xs font-bold text-[#2271b1]">
                    {{ __('Trend Interpretation') }}
                </h5>
            </div>

            <!-- Loader -->
            <div x-show="loading" class="flex items-center gap-2 text-gray-400 py-2">
                <i class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                <span class="text-xs font-semibold">{{ __('Analyzing trends...') }}</span>
            </div>

            <!-- Refinement / Polishing Loader -->
            <div x-show="aiPolishing" class="flex items-center gap-2 text-[#2271b1] py-2 animate-pulse"
                style="display: none;">
                <i class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                <span class="text-xs font-semibold">{{ __('Polishing interpretation...') }}</span>
            </div>

            <!-- Error -->
            <p x-show="error" class="text-xs text-red-500 font-medium italic py-2" x-text="error"
                style="display: none;"></p>

            <!-- In-place Trend Interpretation Block -->
            <div x-show="currentText" class="relative group/trend mb-4" style="display: none;">
                <p class="whitespace-pre-wrap rounded-2xl px-4 py-3 text-[13px] leading-relaxed font-medium bg-gray-100/80 text-gray-800 border border-gray-200/50 shadow-sm"
                    x-text="currentText"></p>
                <!-- Copy Text Button on Bottom Right of the text block container -->
                <div class="absolute bottom-2 right-3"
                    x-show="currentText !== 'Unable to analyze data at this time.' && currentText !== 'Analysis temporarily unavailable.'">
                    <button type="button" @click="copyText()"
                        class="text-[10px] font-bold text-gray-400 hover:text-[#2271b1] transition-all bg-white/60 hover:bg-white px-2 py-0.5 rounded border border-gray-200/40"
                        x-text="copied ? '{{ __('copied') }}' : '{{ __('copy') }}'">
                    </button>
                </div>
            </div>

            <!-- Empty State Prompt -->
            <div x-show="!currentText && !loading && !error" class="py-2">
                <p class="text-xs text-gray-400 font-medium italic">
                    {{ __('Generating interpretation...') }}
                </p>
            </div>

            <!-- Polish / Refinement Input Bar -->
            <div x-show="currentText && currentText !== 'Unable to analyze data at this time.' && currentText !== 'Analysis temporarily unavailable.' && !loading"
                class="mt-5 pt-4 border-t border-zinc-200/50" style="display: none;">
                <div class="flex flex-col md:flex-row gap-3 items-end">
                    <div class="flex-1 w-full">
                        <label
                            class="block text-[10px] font-bold text-[#2271b1] mb-1.5">{{ __('Refine this analysis ') }}</label>
                        <textarea x-model="feedback" rows="1" placeholder="{{ __('Reflect your own voice...') }}"
                            @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                            @keydown.enter.prevent="if(!$event.shiftKey && !$event.ctrlKey) polish()"
                            class="w-full bg-gray-50 border border-zinc-200 text-xs font-semibold rounded-xl px-3 py-2.5 focus:ring-1 focus:ring-[#2271b1] focus:outline-none transition-all resize-none max-h-32 overflow-y-auto"></textarea>
                    </div>
                    <button @click="polish()" :disabled="aiPolishing || !feedback.trim()"
                        class="px-5 py-2.5 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-xl text-xs font-bold transition-all disabled:opacity-50 flex items-center gap-1.5 self-stretch justify-center whitespace-nowrap">
                        <span x-text="aiPolishing ? '{{ __('Polishing...') }}' : '{{ __('Polish') }}'"></span>
                    </button>
                </div>
            </div>

            <!-- Actions Menu -->
            <div class="mt-4 pt-4 border-t border-zinc-200/50 flex justify-between items-center">
                <div class="flex items-center">
                    <button
                        x-show="currentText && currentText !== 'Unable to analyze data at this time.' && currentText !== 'Analysis temporarily unavailable.'"
                        @click="copyFinalOutput()"
                        class="flex items-center gap-1 text-[9px] font-black text-gray-400 tracking-widest hover:text-[#2271b1] transition-colors mr-4"
                        style="display: none;">
                        <i class="fa-solid fa-copy"></i>
                        {{ __('Copy Output') }}
                    </button>
                    <button
                        x-show="currentText && currentText !== 'Unable to analyze data at this time.' && currentText !== 'Analysis temporarily unavailable.'"
                        @click="downloadFinalOutput()"
                        class="flex items-center gap-1 text-[9px] font-black text-gray-400 tracking-widest hover:text-[#2271b1] transition-colors"
                        style="display: none;">
                        <i class="fa-solid fa-download"></i>
                        {{ __('Export') }}
                    </button>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Regenerate / Retry Button for Errors -->
                    <button type="button"
                        x-show="error || currentText === 'Unable to analyze data at this time.' || currentText === 'Analysis temporarily unavailable.'"
                        @click="generate(true)"
                        class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-[10px] font-black tracking-widest transition-all flex items-center gap-1.5 border border-indigo-100 shadow-sm"
                        style="display: none;">
                        <i class="fa-solid fa-arrows-rotate"></i>
                        {{ __('Regenerate') }}
                    </button>

                    <!-- Muted Undo Button (Single level rollback) -->
                    <button type="button" x-show="previousText" @click="undo()"
                        class="text-[10px] font-black text-gray-400 hover:text-gray-600 tracking-widest transition-colors"
                        style="display: none;">
                        {{ __('Undo') }}
                    </button>

                    @if(auth()->user() && auth()->user()->canUseAiAnalysis())
                        <button @click="generate()" x-show="!currentText && !loading"
                            class="flex items-center gap-2 text-[9px] font-black text-[#2271b1] tracking-widest hover:text-[#135e96] transition-colors">
                            <i class="fa-solid fa-chart-line"></i>
                            {{ __('Deep Trend Analysis') }}
                        </button>
                        <button x-show="currentText" @click="reset()"
                            class="text-[10px] font-black text-red-500 hover:text-red-700 tracking-widest transition-colors"
                            style="display: none;">
                            {{ __('Reset') }}
                        </button>
                    @else
                        <button @click="window.location.href='{{ route('subscriptions.index') }}'"
                            class="flex items-center gap-2 text-[9px] font-black text-gray-400 uppercase tracking-widest hover:text-zinc-2000 transition-colors">
                            <i class="fa-solid fa-lock text-[8px]"></i>
                            {{ __('Deep Analysis (Premium)') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    if (typeof window.quantInsightCard === 'undefined') {
        window.quantInsightCard = function (qId, sId) {
            return {
                loading: false,
                error: null,
                qId: qId,
                sId: sId,
                feedback: '',
                aiPolishing: false,
                messages: [],
                hasFetched: false,
                retryCount: 0,
                currentText: '',
                previousText: null,
                copied: false,
                init() {
                    window.quantInsightInstances = window.quantInsightInstances || {};
                    window.quantInsightInstances[this.qId] = this;

                    // Use IntersectionObserver to lazy-load AI insights only when scrolled into view
                    if ('IntersectionObserver' in window) {
                        const observer = new IntersectionObserver((entries) => {
                            if (entries[0].isIntersecting && !this.hasFetched) {
                                this.hasFetched = true;
                                this.generate();
                                observer.disconnect();
                            }
                        }, { threshold: 0.1 });
                        observer.observe(this.$el);
                    } else {
                        this.generate();
                    }
                },
                async parseJsonResponse(response) {
                    const text = await response.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error(`Server returned HTML error (${response.status}). Please check system logs.`);
                    }
                },
                async generate(forceRefresh = false) {
                    this.loading = true;
                    this.error = null;
                    try {
                        const style = window.currentReportingStyle || 'apa';
                        const url = `/ai/insights/quantitative/${this.qId}?survey_id=${this.sId}&style=${style}` + (forceRefresh ? '&refresh=1' : '');
                        const response = await fetch(url, {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (response.status === 429) {
                            this.retryCount++;
                            this.error = @js(__('Rate limit reached. Retrying automatically in 5 seconds...'));
                            setTimeout(() => this.generate(forceRefresh), 5000);
                            return;
                        }
                        const data = await this.parseJsonResponse(response);
                        if (!response.ok) throw new Error(data.message || data.error || @js(__('Failed to fetch analysis.')));
                        this.messages = [{ role: 'assistant', content: data.insight }];
                        this.currentText = data.insight;
                        this.previousText = null;
                        this.retryCount = 0;
                    } catch (err) {
                        this.error = err.message;
                    } finally {
                        if (this.retryCount === 0 || this.currentText) {
                            this.loading = false;
                        }
                    }
                },
                async polish() {
                    if (!this.feedback.trim()) return;
                    const userMsg = this.feedback.trim();
                    this.messages.push({ role: 'user', content: userMsg });

                    const previousVal = this.currentText;
                    this.feedback = '';
                    this.aiPolishing = true;
                    this.error = null;
                    try {
                        const style = window.currentReportingStyle || 'apa';
                        const response = await fetch(`/ai/insights/quantitative/${this.qId}/refine`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector("meta[name='csrf-token']").getAttribute('content')
                            },
                            body: JSON.stringify({
                                survey_id: this.sId,
                                messages: this.messages,
                                feedback: userMsg,
                                style: style
                            })
                        });
                        if (!response.ok) {
                            const errData = await response.json();
                            throw new Error(errData.message || @js(__('Failed to refine analysis.')));
                        }
                        const data = await response.json();
                        if (data.success) {
                            this.messages.push({ role: 'assistant', content: data.insight });
                            this.previousText = previousVal;
                            this.currentText = data.insight;
                        } else {
                            throw new Error(data.message || @js(__('Failed to refine analysis.')));
                        }
                    } catch (err) {
                        this.error = err.message;
                        this.messages.pop();
                        this.feedback = userMsg;
                    } finally {
                        this.aiPolishing = false;
                    }
                },
                async refineFromGlobal(feedbackText, style) {
                    if (this.aiPolishing || this.loading) return;
                    this.aiPolishing = true;
                    this.error = null;

                    const userMsg = feedbackText.trim();
                    this.messages.push({ role: 'user', content: userMsg });
                    const previousVal = this.currentText;

                    try {
                        const response = await fetch(`/ai/insights/quantitative/${this.qId}/refine`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector("meta[name='csrf-token']").getAttribute('content')
                            },
                            body: JSON.stringify({
                                survey_id: this.sId,
                                messages: this.messages,
                                feedback: userMsg,
                                style: style
                            })
                        });
                        if (!response.ok) {
                            const errData = await response.json();
                            throw new Error(errData.message || @js(__('Failed to refine analysis.')));
                        }
                        const data = await response.json();
                        if (data.success) {
                            this.messages.push({ role: 'assistant', content: data.insight });
                            this.previousText = previousVal;
                            this.currentText = data.insight;
                        } else {
                            throw new Error(data.message || @js(__('Failed to refine analysis.')));
                        }
                    } catch (err) {
                        this.error = err.message;
                        this.messages.pop();
                    } finally {
                        this.aiPolishing = false;
                    }
                },
                copyText() {
                    if (!this.currentText) return;
                    navigator.clipboard.writeText(this.currentText).then(() => {
                        this.copied = true;
                        setTimeout(() => { this.copied = false; }, 2000);
                    });
                },
                undo() {
                    if (this.previousText) {
                        this.currentText = this.previousText;
                        this.previousText = null;
                        if (this.messages.length >= 2) {
                            this.messages.pop(); // Remove assistant
                            this.messages.pop(); // Remove user
                        }
                    }
                },
                copyFinalOutput() {
                    if (!this.currentText) return;
                    navigator.clipboard.writeText(this.currentText).then(() => {
                        alert(@js(__('Copied interpretation to clipboard!')));
                    });
                },
                downloadFinalOutput() {
                    if (!this.currentText) return;
                    const blob = new Blob([this.currentText], { type: 'text/plain;charset=utf-8' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `quantitative_trend_insight_${this.qId}.txt`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                },
                reset() {
                    this.messages = [];
                    this.currentText = '';
                    this.previousText = null;
                    this.error = null;
                    this.feedback = '';
                    this.generate(true);
                }
            };
        };
    }
</script>