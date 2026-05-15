@props(['questionId', 'surveyId', 'stats'])

<div x-data="{
    loading: false,
    insight: null,
    error: null,
    qId: '{{ $questionId }}',
    sId: '{{ $surveyId }}',
    async generate() {
        this.loading = true;
        this.error = null;
        try {
            const response = await fetch(`/ai/insights/quantitative/${this.qId}?survey_id=${this.sId}`);
            if (response.status === 429) throw new Error(@js(__('Rate Limit Exceeded. Please wait.')));
            if (!response.ok) throw new Error(@js(__('Failed to fetch analysis.')));
            const data = await response.json();
            this.insight = data.insight;
        } catch (err) {
            this.error = err.message;
        } finally {
            this.loading = false;
        }
    }
}"
    class="bg-gradient-to-br from-white to-indigo-50/30 rounded-3xl p-6 border border-indigo-100 shadow-sm mt-6 min-h-[100px] flex flex-col justify-center">
    <div class="flex items-start gap-4">
        <div class="flex-shrink-0">
            <div
                class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-100">
                <i class="fa-solid fa-chart-pie text-sm"></i>
            </div>
        </div>
        <div class="flex-1">
            <div class="flex items-center justify-between mb-2">
                <h5 class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">
                    {{ __('AI Trend Interpretation') }}
                </h5>
                <span x-show="insight"
                    class="text-[9px] font-black bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full uppercase"
                    style="display: none;">
                    {{ __('AI Analyzed') }}
                </span>
            </div>

            <div x-show="loading" class="flex items-center gap-2 text-gray-400 py-2">
                <i class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                <span class="text-[11px] font-bold uppercase tracking-wider">{{ __('Analyzing Trends...') }}</span>
            </div>

            <p x-show="error" class="text-xs text-red-500 font-medium italic py-2" x-text="error"
                style="display: none;"></p>

            <p x-show="insight" class="text-[13px] text-gray-700 leading-relaxed font-medium py-2" x-text="insight"
                style="display: none;"></p>

            <div x-show="!insight && !loading && !error" class="py-2">
                <p class="text-[11px] text-gray-400 font-medium italic">
                    {{ __('Click below to generate a strategic interpretation of these numbers.') }}
                </p>
            </div>

            <!-- Further Analysis (Premium Enticement) -->
            <div class="mt-4 pt-4 border-t border-indigo-100/50 flex justify-end">
                @if(auth()->user() && auth()->user()->canUseAiAnalysis())
                    <button @click="generate()" x-show="!insight && !loading"
                        class="flex items-center gap-2 text-[9px] font-black text-indigo-600 uppercase tracking-widest hover:text-indigo-800 transition-colors">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        {{ __('Deep Trend Analysis') }}
                    </button>
                    <button x-show="insight" @click="insight = null"
                        class="text-[9px] font-black text-gray-400 uppercase tracking-widest hover:text-indigo-600 transition-colors"
                        style="display: none;">
                        {{ __('Reset Analysis') }}
                    </button>
                @else
                    <button @click="window.location.href='{{ route('subscriptions.index') }}'"
                        class="flex items-center gap-2 text-[9px] font-black text-gray-400 uppercase tracking-widest hover:text-indigo-500 transition-colors">
                        <i class="fa-solid fa-lock text-[8px]"></i>
                        {{ __('Deep Analysis (Premium)') }}
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>