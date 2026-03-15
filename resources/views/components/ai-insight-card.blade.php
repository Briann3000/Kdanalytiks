@props(['questionId', 'questionTitle', 'surveyId'])

<div x-data="{
    loading: false,
    insight: null,
    error: null,
    async generate() {
        this.loading = true;
        this.error = null;
        try {
            const response = await fetch(`/ai/insights/question/${this.questionId}?survey_id=${this.surveyId}`);
            if (!response.ok) throw new Error('Failed to generate insights');
            this.insight = await response.json();
            if (this.insight.error) throw new Error(this.insight.error);
        } catch (err) {
            this.error = err.message;
        } finally {
            this.loading = false;
        }
    }
}" data-question-id="{{ $questionId }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 my-4 transition-all hover:shadow-md">
    
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600">
                <i class="fa fa-robot text-lg"></i>
            </div>
            <div>
                <h4 class="font-bold text-gray-900 leading-tight">AI Qualitative Insights</h4>
                <p class="text-xs text-gray-500">Thematic & Sentiment Analysis</p>
            </div>
        </div>
        
        <button 
            @click="generate()" 
            :disabled="loading"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition-colors disabled:opacity-50 flex items-center gap-2"
        >
            <template x-if="!loading">
                <i class="fa fa-magic"></i>
            </template>
            <template x-if="loading">
                <i class="fa fa-spinner fa-spin"></i>
            </template>
            <span x-text="loading ? 'Analyzing...' : (insight ? 'Regenerate' : 'Generate Summary')"></span>
        </button>
    </div>

    <!-- Error State -->
    <template x-if="error">
        <div class="p-3 bg-red-50 border border-red-100 text-red-600 rounded-lg text-sm flex items-center gap-2">
            <i class="fa fa-exclamation-circle"></i>
            <span x-text="error"></span>
        </div>
    </template>

    <!-- Initial / Empty State -->
    <template x-if="!insight && !loading && !error">
        <div class="py-4 text-center">
            <p class="text-sm text-gray-400 italic">Click generate to let AI analyze responses for: <strong>{{ $questionTitle }}</strong></p>
        </div>
    </template>

    <!-- Results Body -->
    <template x-if="insight && !loading">
        <div class="space-y-6">
            
            <!-- Sentiment Breakdown -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Tone & Sentiment</span>
                </div>
                <div class="flex h-3 w-full rounded-full overflow-hidden bg-gray-100">
                    <div :style="`width: ${insight.sentiment_breakdown.Positive}%`" class="bg-green-500 h-full" title="Positive"></div>
                    <div :style="`width: ${insight.sentiment_breakdown.Neutral}%`" class="bg-gray-400 h-full" title="Neutral"></div>
                    <div :style="`width: ${insight.sentiment_breakdown.Negative}%`" class="bg-red-500 h-full" title="Negative"></div>
                </div>
                <div class="flex justify-between mt-2 text-[11px] font-medium">
                    <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-green-500"></span> Positive <span x-text="insight.sentiment_breakdown.Positive + '%'"></span></div>
                    <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-gray-400"></span> Neutral <span x-text="insight.sentiment_breakdown.Neutral + '%'"></span></div>
                    <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-500"></span> Negative <span x-text="insight.sentiment_breakdown.Negative + '%'"></span></div>
                </div>
            </div>

            <!-- Thematic Analysis -->
            <div>
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-3">Key Recurring Themes</span>
                <div class="grid gap-3">
                    <template x-for="theme in insight.key_themes" :key="theme.theme">
                        <div class="p-3 bg-indigo-50/50 rounded-lg border border-indigo-100/50">
                            <div class="font-bold text-indigo-900 text-sm" x-text="theme.theme"></div>
                            <div class="text-xs text-indigo-700/80 leading-relaxed mt-1" x-text="theme.explanation"></div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Quotes Carousel / List -->
            <div>
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-3">Representative Quotes</span>
                <div class="space-y-2">
                    <template x-for="quote in insight.representative_quotes" :key="quote">
                        <div class="flex gap-3 items-start p-3 bg-white border border-gray-100 rounded-lg shadow-sm italic text-gray-700 text-sm">
                            <i class="fa fa-quote-left text-indigo-200 text-xs mt-1"></i>
                            <span x-text="quote"></span>
                        </div>
                    </template>
                </div>
            </div>

        </div>
    </template>
</div>
