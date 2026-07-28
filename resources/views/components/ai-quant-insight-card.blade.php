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

            <!-- Error -->
            <p x-show="error" class="text-xs text-red-500 font-medium italic py-2" x-text="error"
                style="display: none;"></p>

            <!-- Chat Message Logs -->
            <div x-show="messages.length > 0" class="space-y-4 py-2" style="display: none;">
                <template x-for="(msg, index) in messages" :key="index">
                    <div class="flex flex-col mb-1 group/msg"
                        :class="msg.role === 'user' ? 'items-end' : 'items-start'">
                        <div class="relative max-w-[85%] rounded-2xl px-4 py-3 text-[13px] leading-relaxed font-medium shadow-sm"
                            :class="msg.role === 'user' 
                                     ? 'bg-[#2271b1] text-white rounded-br-none' 
                                     : 'bg-gray-100/80 text-gray-800 rounded-bl-none border border-gray-200/50'">
                            <p class="whitespace-pre-wrap" x-text="msg.content"></p>

                            <!-- Hover Copy Button -->
                            <button type="button" @click="navigator.clipboard.writeText(msg.content)"
                                class="absolute top-2 right-2 opacity-0 group-hover/msg:opacity-100 transition-opacity p-1 bg-white/80 hover:bg-white text-gray-600 rounded-lg text-[10px] shadow border border-gray-200/60"
                                title="{{ __('Copy Message') }}">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty State Prompt -->
            <div x-show="messages.length === 0 && !loading && !error" class="py-2">
                <p class="text-xs text-gray-400 font-medium italic">
                    {{ __('Generating interpretation...') }}
                </p>
            </div>

            <!-- Polish / Refinement Input Bar inside Chat -->
            <div x-show="messages.length > 0 && !loading" class="mt-5 pt-4 border-t border-zinc-200/50"
                style="display: none;">
                <div class="flex flex-col md:flex-row gap-3 items-end">
                    <div class="flex-1 w-full">
                        <label
                            class="block text-[10px] font-bold text-[#2271b1] mb-1.5">{{ __('Refine this analysis ') }}</label>
                        <textarea x-model="feedback" rows="1" placeholder="{{ __('Reflect your own voice...') }}"
                            @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                            @keydown.enter.prevent="if(!$event.shiftKey) polish()"
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
                    <button x-show="messages.length > 0" @click="copyFinalOutput()"
                        class="flex items-center gap-1 text-[9px] font-black text-gray-400 tracking-widest hover:text-[#2271b1] transition-colors mr-4"
                        style="display: none;">
                        <i class="fa-solid fa-copy"></i>
                        {{ __('Copy Output') }}
                    </button>
                    <button x-show="messages.length > 0" @click="downloadFinalOutput()"
                        class="flex items-center gap-1 text-[9px] font-black text-gray-400 tracking-widest hover:text-[#2271b1] transition-colors"
                        style="display: none;">
                        <i class="fa-solid fa-download"></i>
                        {{ __('Export') }}
                    </button>
                </div>
                <div>
                    @if(auth()->user() && auth()->user()->canUseAiAnalysis())
                        <button @click="generate()" x-show="messages.length === 0 && !loading"
                            class="flex items-center gap-2 text-[9px] font-black text-[#2271b1] tracking-widest hover:text-[#135e96] transition-colors">
                            <i class="fa-solid fa-chart-line"></i>
                            {{ __('Deep Trend Analysis') }}
                        </button>
                        <button x-show="messages.length > 0" @click="reset()"
                            class="text-[9px] font-black text-red-500 hover:text-red-700 tracking-widest transition-colors"
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
                init() {
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
                        const url = `/ai/insights/quantitative/${this.qId}?survey_id=${this.sId}` + (forceRefresh ? '&refresh=1' : '');
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
                        this.retryCount = 0;
                    } catch (err) {
                        this.error = err.message;
                    } finally {
                        if (this.retryCount === 0 || this.messages.length > 0) {
                            this.loading = false;
                        }
                    }
                },
                async polish() {
                    if (!this.feedback.trim()) return;
                    const userMsg = this.feedback.trim();
                    this.messages.push({ role: 'user', content: userMsg });
                    this.feedback = '';
                    this.aiPolishing = true;
                    this.error = null;
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
                                feedback: userMsg
                            })
                        });
                        if (!response.ok) {
                            const errData = await response.json();
                            throw new Error(errData.message || @js(__('Failed to refine analysis.')));
                        }
                        const data = await response.json();
                        if (data.success) {
                            this.messages.push({ role: 'assistant', content: data.insight });
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
                copyFinalOutput() {
                    const lastMsg = [...this.messages].reverse().find(function (m) { return m.role === 'assistant'; });
                    if (!lastMsg) return;
                    navigator.clipboard.writeText(lastMsg.content).then(() => {
                        alert(@js(__('Copied interpretation to clipboard!')));
                    });
                },
                downloadFinalOutput() {
                    const lastMsg = [...this.messages].reverse().find(function (m) { return m.role === 'assistant'; });
                    if (!lastMsg) return;
                    const blob = new Blob([lastMsg.content], { type: 'text/plain;charset=utf-8' });
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
                    this.error = null;
                    this.feedback = '';
                    this.generate(true);
                }
            };
        };
    }
</script>