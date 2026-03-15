@props(['questionId', 'questionTitle', 'surveyId'])

<div x-data="{
    loading: false,
    insight: null,
    error: null,
    qId: '{{ $questionId }}',
    sId: '{{ $surveyId }}',
    init() {
        this.$watch('qId', () => { this.insight = null; this.error = null; });
        if (this.qId && !this.loading) {
            this.generate();
        }
    },
    async generate(id = null) {
        if (id) this.qId = id;
        if (!this.qId) {
            console.warn('AI Insight Card: No question ID provided.');
            return;
        }

        console.log('AI Insight Card: Generating report for ID:', this.qId);
        this.loading = true;
        this.error = null;
        this.insight = null;
        try {
            const response = await fetch(`/ai/insights/question/${this.qId}?survey_id=${this.sId}`);
            if (!response.ok) throw new Error('API Request Failed');
            
            this.insight = await response.json();
            if (this.insight.error) throw new Error(this.insight.error);
            
            // Scroll to results
            this.$nextTick(() => {
                this.$el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        } catch (err) {
            this.error = 'Unable to analyze responses. There might be too few answers or a network issue.';
            console.error(err);
        } finally {
            this.loading = false;
        }
    }
}" 
x-on:trigger-analysis.window="generate($event.detail.id)"
class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 my-4 transition-all min-h-[300px] flex flex-col items-center justify-center text-center">
    
    <!-- Header -->
    <template x-if="insight">
        <div class="flex items-center justify-between mb-10 w-full border-b border-gray-50 pb-8">
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 rounded-2xl bg-indigo-600 flex items-center justify-center text-white shadow-xl shadow-indigo-200">
                    <i class="fa fa-chart-pie text-2xl"></i>
                </div>
                <div class="text-left">
                    <h4 class="font-black text-gray-900 text-2xl tracking-tighter">Analytical Results</h4>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <p class="text-[10px] text-gray-400 font-extrabold uppercase tracking-widest">Real-time Groq Feed</p>
                    </div>
                </div>
            </div>
            <div class="hidden sm:flex items-center gap-4">
                <div class="flex flex-col items-end">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">AI Engine active</span>
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-600 text-[10px] font-black uppercase tracking-widest rounded-lg border border-indigo-100">LLAMA 3.1 8B</span>
                </div>
            </div>
        </div>
    </template>

    <!-- Loading State -->
    <div x-show="loading" class="flex flex-col items-center justify-center py-20 w-full">
        <div class="relative w-20 h-20 mb-8">
            <div class="absolute inset-0 rounded-3xl bg-indigo-50 animate-pulse"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <i class="fa fa-brain text-4xl text-indigo-600"></i>
            </div>
            <div class="absolute -right-2 -top-2">
                <div class="flex space-x-1">
                    <div class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                    <div class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    <div class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0.3s"></div>
                </div>
            </div>
        </div>
        <h5 class="text-xl font-black text-gray-900 mb-2">Analyzing Responses</h5>
        <p class="text-gray-400 text-sm max-w-[280px] font-medium leading-relaxed">Please wait while our AI maps recurring themes and sentiment trends...</p>
    </div>

    <!-- Error State -->
    <template x-if="error">
        <div class="p-10 bg-rose-50 border border-rose-100 text-rose-600 rounded-3xl flex flex-col items-center gap-5 w-full max-w-lg shadow-sm">
            <div class="w-14 h-14 bg-rose-100 rounded-2xl flex items-center justify-center text-rose-600 shadow-inner">
                <i class="fa fa-triangle-exclamation text-2xl"></i>
            </div>
            <div class="text-center">
                <p class="font-black uppercase tracking-[0.2em] text-[10px] text-rose-400 mb-2">Technical Insight Error</p>
                <span x-text="error" class="font-bold text-lg leading-tight"></span>
            </div>
        </div>
    </template>

    <!-- Initial / Empty State -->
    <div x-show="!insight && !loading && !error" class="py-16 text-center w-full bg-gray-50/50 rounded-[3rem] border-2 border-dashed border-gray-100">
        <div class="w-24 h-24 bg-white rounded-[2rem] flex items-center justify-center mx-auto mb-8 shadow-sm border border-gray-100 transform -rotate-2 hover:rotate-0 transition-transform">
            <i class="fa fa-sparkles text-indigo-400 text-4xl"></i>
        </div>
        <h4 class="text-3xl font-black text-gray-900 mb-4 tracking-tighter">AI Discovery Engine</h4>
        <p class="text-lg text-gray-500 font-medium max-w-sm mx-auto leading-relaxed">
            Select a focus question above and click <span class="text-indigo-600 font-black underline">Generate Report</span> to begin.
        </p>
    </div>

    <!-- Results Body -->
    <template x-if="insight && !loading">
        <div class="space-y-10 w-full text-left">
            
            <!-- Sentiment Breakdown -->
            <div class="bg-gray-50/50 p-6 rounded-2xl border border-gray-100/50">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Overall Voter Tone</span>
                    <span class="text-[10px] font-black text-indigo-600 uppercase tracking-widest bg-indigo-50 px-2 py-1 rounded">Sentiment Analysis</span>
                </div>
                <div class="flex h-4 w-full rounded-full overflow-hidden bg-white border border-gray-100 p-0.5 shadow-inner">
                    <div :style="`width: ${insight.sentiment_breakdown.Positive}%`" class="bg-emerald-500 h-full rounded-l-full transition-all duration-1000" title="Positive"></div>
                    <div :style="`width: ${insight.sentiment_breakdown.Neutral}%`" class="bg-amber-400 h-full transition-all duration-1000" title="Neutral"></div>
                    <div :style="`width: ${insight.sentiment_breakdown.Negative}%`" class="bg-rose-500 h-full rounded-r-full transition-all duration-1000" title="Negative"></div>
                </div>
                <div class="flex flex-wrap justify-between mt-4 gap-4">
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-emerald-50 rounded-lg">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> 
                        <span class="text-xs font-black text-emerald-800 uppercase tracking-tighter">Positive</span>
                        <span class="text-sm font-black text-emerald-900" x-text="insight.sentiment_breakdown.Positive + '%'"></span>
                    </div>
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-amber-50 rounded-lg">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span> 
                        <span class="text-xs font-black text-amber-800 uppercase tracking-tighter">Neutral</span>
                        <span class="text-sm font-black text-amber-900" x-text="insight.sentiment_breakdown.Neutral + '%'"></span>
                    </div>
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-rose-50 rounded-lg">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> 
                        <span class="text-xs font-black text-rose-800 uppercase tracking-tighter">Negative</span>
                        <span class="text-sm font-black text-rose-900" x-text="insight.sentiment_breakdown.Negative + '%'"></span>
                    </div>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-10">
                <!-- Thematic Analysis -->
                <div class="flex flex-col">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="w-1.5 h-4 bg-indigo-600 rounded-full"></span>
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Key Recurring Themes</span>
                    </div>
                    <div class="grid gap-3">
                        <template x-for="theme in insight.key_themes" :key="theme.theme">
                            <div class="p-4 bg-white rounded-xl border border-gray-100 shadow-sm hover:border-indigo-100 transition-colors group">
                                <div class="font-black text-gray-900 text-sm flex items-center gap-2" x-text="theme.theme"></div>
                                <div class="text-xs text-gray-500 leading-relaxed mt-2" x-text="theme.explanation"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Quotes Carousel / List -->
                <div class="flex flex-col">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="w-1.5 h-4 bg-emerald-500 rounded-full"></span>
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Representative Quotes</span>
                    </div>
                    <div class="space-y-3">
                        <template x-for="quote in insight.representative_quotes" :key="quote">
                            <div class="flex gap-4 items-start p-4 bg-white border border-gray-100 rounded-xl shadow-sm italic text-gray-600 text-[13px] leading-relaxed relative group overflow-hidden">
                                <div class="absolute left-0 top-0 w-1 h-full bg-indigo-50 group-hover:bg-indigo-500 transition-colors"></div>
                                <i class="fa fa-quote-left text-indigo-200 text-xs mt-1 shrink-0"></i>
                                <span x-text="quote"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
