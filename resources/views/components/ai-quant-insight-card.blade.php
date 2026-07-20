@props(['questionId', 'surveyId', 'stats'])

<div x-data="{
    loading: false,
    error: null,
    qId: '{{ $questionId }}',
    sId: '{{ $surveyId }}',
    feedback: '',
    aiPolishing: false,
    messages: [], // stores conversation thread: { role: 'assistant'|'user', content: string }
    async generate() {
        this.loading = true;
        this.error = null;
        try {
            const response = await fetch(`/ai/insights/quantitative/${this.qId}?survey_id=${this.sId}`);
            if (response.status === 429) throw new Error(@js(__('Rate Limit Exceeded. Please wait.')));
            if (!response.ok) throw new Error(@js(__('Failed to fetch analysis.')));
            const data = await response.json();
            this.messages = [{ role: 'assistant', content: data.insight }];
        } catch (err) {
            this.error = err.message;
        } finally {
            this.loading = false;
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
                    'X-CSRF-TOKEN': document.querySelector('meta[name=&quot;csrf-token&quot;]').getAttribute('content')
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
            // Remove the user message if it failed so they can try again
            this.messages.pop();
            this.feedback = userMsg;
        } finally {
            this.aiPolishing = false;
        }
    },
    copyFinalOutput() {
        const lastMsg = [...this.messages].reverse().find(m => m.role === 'assistant');
        if (!lastMsg) return;
        navigator.clipboard.writeText(lastMsg.content).then(() => {
            alert(@js(__('Copied interpretation to clipboard!')));
        });
    },
    downloadFinalOutput() {
        const lastMsg = [...this.messages].reverse().find(m => m.role === 'assistant');
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
    }
}"
    class="bg-gradient-to-br from-white to-zinc-100/30 rounded-3xl p-6 border border-zinc-200 shadow-sm mt-6 min-h-[100px] flex flex-col justify-center">
    <div class="flex items-start gap-4">
        <div class="flex-shrink-0">
            <div
                class="w-10 h-10 rounded-xl bg-[#2271b1] flex items-center justify-center text-white shadow-lg shadow-zinc-200/50">
                <i class="fa-solid fa-chart-pie text-sm"></i>
            </div>
        </div>
        <div class="flex-1 w-full overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <h5 class="text-[10px] font-black text-[#2271b1] uppercase tracking-widest">
                    {{ __('AI Trend Interpretation') }}
                </h5>
                <span x-show="messages.length > 0"
                    class="text-[9px] font-black bg-zinc-200 text-[#135e96] px-2 py-0.5 rounded-full uppercase"
                    style="display: none;">
                    {{ __('Interactive Chat') }}
                </span>
            </div>

            <!-- Loader -->
            <div x-show="loading" class="flex items-center gap-2 text-gray-400 py-2">
                <i class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                <span class="text-[11px] font-bold uppercase tracking-wider">{{ __('Analyzing Trends...') }}</span>
            </div>

            <!-- Error -->
            <p x-show="error" class="text-xs text-red-500 font-medium italic py-2" x-text="error"
                style="display: none;"></p>

            <!-- Chat Message Logs -->
            <div x-show="messages.length > 0" class="space-y-4 py-2" style="display: none;">
                <template x-for="(msg, index) in messages" :key="index">
                    <div class="flex flex-col mb-1" :class="msg.role === 'user' ? 'items-end' : 'items-start'">
                        <div class="max-w-[85%] rounded-2xl px-4 py-3 text-[13px] leading-relaxed font-medium shadow-sm"
                            :class="msg.role === 'user' 
                                     ? 'bg-[#2271b1] text-white rounded-br-none' 
                                     : 'bg-gray-100/80 text-gray-800 rounded-bl-none border border-gray-200/50'">
                            <p class="whitespace-pre-wrap" x-text="msg.content"></p>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty State Prompt -->
            <div x-show="messages.length === 0 && !loading && !error" class="py-2">
                <p class="text-[11px] text-gray-400 font-medium italic">
                    {{ __('Click below to generate a strategic interpretation of these numbers.') }}
                </p>
            </div>

            <!-- Polish / Refinement Input Bar inside Chat -->
            <div x-show="messages.length > 0 && !loading" class="mt-5 pt-4 border-t border-zinc-200/50"
                style="display: none;">
                <div class="flex flex-col md:flex-row gap-3 items-end">
                    <div class="flex-1 w-full">
                        <label
                            class="block text-[9px] font-black text-[#2271b1] uppercase tracking-widest mb-1.5">{{ __('Refine this analysis (e.g. "Focus more on X", "Keep it simple")') }}</label>
                        <input x-model="feedback" type="text" placeholder="{{ __('Type instructions to refine...') }}"
                            @keydown.enter="polish()"
                            class="w-full bg-gray-50 border border-zinc-200 text-xs font-semibold rounded-xl px-3 py-2.5 focus:ring-1 focus:ring-[#2271b1] focus:outline-none transition-all">
                    </div>
                    <button @click="polish()" :disabled="aiPolishing || !feedback.trim()"
                        class="px-5 py-2.5 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all disabled:opacity-50 flex items-center gap-1.5 self-stretch justify-center whitespace-nowrap">
                        <i class="fa-solid fa-paper-plane" :class="{'fa-spin': aiPolishing}"></i>
                        <span x-text="aiPolishing ? '{{ __('Polishing...') }}' : '{{ __('Polish') }}'"></span>
                    </button>
                </div>
            </div>

            <!-- Actions Menu -->
            <div class="mt-4 pt-4 border-t border-zinc-200/50 flex justify-between items-center">
                <div class="flex items-center">
                    <button x-show="messages.length > 0" @click="copyFinalOutput()"
                        class="flex items-center gap-1 text-[9px] font-black text-gray-400 uppercase tracking-widest hover:text-[#2271b1] transition-colors mr-4"
                        style="display: none;">
                        <i class="fa-solid fa-copy"></i>
                        {{ __('Copy Output') }}
                    </button>
                    <button x-show="messages.length > 0" @click="downloadFinalOutput()"
                        class="flex items-center gap-1 text-[9px] font-black text-gray-400 uppercase tracking-widest hover:text-[#2271b1] transition-colors"
                        style="display: none;">
                        <i class="fa-solid fa-download"></i>
                        {{ __('Export TXT') }}
                    </button>
                </div>
                <div>
                    @if(auth()->user() && auth()->user()->canUseAiAnalysis())
                        <button @click="generate()" x-show="messages.length === 0 && !loading"
                            class="flex items-center gap-2 text-[9px] font-black text-[#2271b1] uppercase tracking-widest hover:text-[#135e96] transition-colors">
                            <i class="fa-solid fa-chart-line"></i>
                            {{ __('Deep Trend Analysis') }}
                        </button>
                        <button x-show="messages.length > 0" @click="reset()"
                            class="text-[9px] font-black text-red-500 hover:text-red-700 uppercase tracking-widest transition-colors"
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