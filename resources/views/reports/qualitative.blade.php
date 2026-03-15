@extends('layouts.app')

@section('content')
<div class="py-12 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                    <i class="fa-solid fa-microchip text-indigo-600 mr-2"></i>AI Qualitative Insights
                </h1>
                <p class="mt-2 text-sm text-gray-600">
                    Analyzing ground sentiment for <span class="font-bold text-indigo-700">{{ $survey->title }}</span>
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ url()->previous() }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-all">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Back
                </a>
            </div>
        </div>

        <!-- Question Selector Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
            <div class="p-6">
                <label for="question_selector" class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wider">Select Text Question to Analyze</label>
                <div class="flex flex-col sm:flex-row gap-4">
                    <select id="question_selector" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5">
                        <option value="">-- Choose a question --</option>
                        @foreach($questions as $q)
                            <option value="{{ $q['id'] }}">{{ $q['text'] }}</option>
                        @endforeach
                    </select>
                    <button id="analyze_btn" onclick="runAnalysis()" class="inline-flex items-center justify-center px-6 py-2.5 border border-transparent text-sm font-bold rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-wand-magic-sparkles mr-2"></i> Run AI Analysis
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loading_state" class="hidden">
            <div class="flex flex-col items-center justify-center py-20 px-4 bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="relative w-20 h-20 mb-6">
                    <div class="absolute inset-0 border-4 border-indigo-100 rounded-full"></div>
                    <div class="absolute inset-0 border-4 border-t-indigo-600 rounded-full animate-spin"></div>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">KM Agent is analyzing the ground sentiment...</h3>
                <p class="text-gray-500 text-center max-w-sm">This may take a few seconds as we process voter responses for semantic patterns and themes.</p>
            </div>
        </div>

        <!-- Insights Dashboard (Initially Hidden) -->
        <div id="results_container" class="hidden space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
            <!-- Sentiment & Themes Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Sentiment Card -->
                <div class="lg:col-span-1 bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col">
                    <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900 uppercase tracking-wide">Sentiment Meter</h3>
                        <i class="fa-solid fa-face-smile text-indigo-400"></i>
                    </div>
                    <div class="p-8 flex flex-col justify-center gap-6 flex-grow">
                        <!-- Positive -->
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-bold text-emerald-700">Positive</span>
                                <span id="val_pos" class="text-sm font-extrabold text-emerald-800">0%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                                <div id="bar_pos" class="bg-emerald-500 h-full rounded-full transition-all duration-1000" style="width: 0%"></div>
                            </div>
                        </div>
                        <!-- Neutral -->
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-bold text-gray-600">Neutral</span>
                                <span id="val_neu" class="text-sm font-extrabold text-gray-800">0%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                                <div id="bar_neu" class="bg-gray-400 h-full rounded-full transition-all duration-1000" style="width: 0%"></div>
                            </div>
                        </div>
                        <!-- Negative -->
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-bold text-rose-700">Negative</span>
                                <span id="val_neg" class="text-sm font-extrabold text-rose-800">0%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                                <div id="bar_neg" class="bg-rose-500 h-full rounded-full transition-all duration-1000" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thematic Breakdown Card -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900 uppercase tracking-wide">Thematic Breakdown</h3>
                        <i class="fa-solid fa-layer-group text-indigo-400"></i>
                    </div>
                    <div class="p-6">
                        <div id="themes_list" class="space-y-4">
                            <!-- Populated via JS -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quotes Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900 uppercase tracking-wide">Voices from the Ground</h3>
                    <i class="fa-solid fa-quote-right text-indigo-400"></i>
                </div>
                <div class="p-8 grid grid-cols-1 md:grid-cols-3 gap-8" id="quotes_list">
                    <!-- Populated via JS -->
                </div>
            </div>

            <div class="flex justify-center pt-4">
                <p class="text-xs text-gray-400 italic">
                    <i class="fa-solid fa-circle-info mr-1"></i> 
                    Analysis results are cached for 24 hours. Click 'Run AI Analysis' again to force a refresh if new data has arrived.
                </p>
            </div>
        </div>

        <!-- Initial Placeholder -->
        <div id="placeholder_state" class="bg-white rounded-xl shadow-sm border border-dashed border-gray-300 p-20 flex flex-col items-center justify-center text-center">
            <div class="bg-indigo-50 p-4 rounded-full mb-6">
                <i class="fa-solid fa-brain text-4xl text-indigo-300"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-1">Ready for Deep Analysis</h3>
            <p class="text-gray-500 text-sm max-w-sm mb-6">Select a question above to start the qualitative semantic analysis process powered by Groq LLaMA 3.1.</p>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    async function runAnalysis() {
        const questionId = document.getElementById('question_selector').value;
        if (!questionId) {
            alert('Please select a question first.');
            return;
        }

        const btn = document.getElementById('analyze_btn');
        const loading = document.getElementById('loading_state');
        const results = document.getElementById('results_container');
        const placeholder = document.getElementById('placeholder_state');

        // UI State
        btn.disabled = true;
        placeholder.classList.add('hidden');
        results.classList.add('hidden');
        loading.classList.remove('hidden');

        try {
            const response = await axios.get(`{{ route('surveys.analyze', [$survey->id, 'PLACEHOLDER']) }}`.replace('PLACEHOLDER', questionId));
            const data = response.data;

            if (data.error) {
                alert('Analysis Error: ' + data.error);
                loading.classList.add('hidden');
                placeholder.classList.remove('hidden');
                btn.disabled = false;
                return;
            }

            // Update Sentiment
            updateSentiment(data.sentiment);

            // Update Themes
            updateThemes(data.key_themes);

            // Update Quotes
            updateQuotes(data.top_quotes);

            // Show Results
            loading.classList.add('hidden');
            results.classList.remove('hidden');

        } catch (error) {
            console.error('Analysis failed:', error);
            alert('Something went wrong during analysis. Please try again.');
            loading.classList.add('hidden');
            placeholder.classList.remove('hidden');
        } finally {
            btn.disabled = false;
        }
    }

    function updateSentiment(sentiment) {
        const pos = sentiment.positive || 0;
        const neu = sentiment.neutral || 0;
        const neg = sentiment.negative || 0;

        document.getElementById('val_pos').innerText = `${pos}%`;
        document.getElementById('val_neu').innerText = `${neu}%`;
        document.getElementById('val_neg').innerText = `${neg}%`;

        setTimeout(() => {
            document.getElementById('bar_pos').style.width = `${pos}%`;
            document.getElementById('bar_neu').style.width = `${neu}%`;
            document.getElementById('bar_neg').style.width = `${neg}%`;
        }, 100);
    }

    function updateThemes(themes) {
        const list = document.getElementById('themes_list');
        list.innerHTML = '';

        themes.forEach((theme, index) => {
            const colors = ['bg-indigo-50 text-indigo-700', 'bg-purple-50 text-purple-700', 'bg-blue-50 text-blue-700', 'bg-sky-50 text-sky-700', 'bg-slate-50 text-slate-700'];
            const color = colors[index % colors.length];
            
            list.innerHTML += `
                <div class="p-4 rounded-lg flex items-start gap-4 border border-gray-100 hover:shadow-md transition-all group">
                    <div class="h-10 w-10 flex-shrink-0 rounded-full ${color} flex items-center justify-center font-bold">
                        ${index + 1}
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 group-hover:text-indigo-600 transition-colors uppercase tracking-tight">${theme.theme}</h4>
                        <p class="text-sm text-gray-600 mt-1">${theme.explanation}</p>
                    </div>
                </div>
            `;
        });
    }

    function updateQuotes(quotes) {
        const list = document.getElementById('quotes_list');
        list.innerHTML = '';

        quotes.forEach(quote => {
            list.innerHTML += `
                <div class="relative p-6 bg-gradient-to-br from-indigo-50 to-white rounded-2xl border border-indigo-100 italic text-gray-700 text-sm leading-relaxed shadow-sm">
                    <i class="fa-solid fa-quote-left absolute -top-3 left-6 text-2xl text-indigo-300"></i>
                    "${quote}"
                </div>
            `;
        });
    }
</script>
@endpush

@push('styles')
<style>
    .animate-spin {
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .animate-in {
        animation-duration: 700ms;
        animation-fill-mode: both;
    }
    .fade-in {
        animation-name: fadeIn;
    }
    .slide-in-from-bottom-4 {
        animation-name: slideInFromBottom;
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes slideInFromBottom {
        from { transform: translateY(1rem); }
        to { transform: translateY(0); }
    }
</style>
@endpush
@endsection
