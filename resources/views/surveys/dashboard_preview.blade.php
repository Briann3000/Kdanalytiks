@php
    $isSharedView = $hasToken;
@endphp

@extends('surveys.hub')

@section('survey-content')
    <div x-data="dashboardPreview()" x-init="initPreview()" class="min-h-screen pb-12">
        <!-- Preview Header Control for Owner/Collaborators -->
        @if(!$hasToken)
            <div
                class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-3xl border border-gray-100 shadow-sm animate-in fade-in duration-300">
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                        {{ __('Dashboard Presentation Mode') }}</h3>
                    <p class="text-xs text-gray-500 font-medium mt-1">
                        {{ __('This is the presentation view that external stakeholders will see.') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('surveys.dashboard-builder', $survey) }}"
                        class="px-5 py-3 bg-white text-gray-700 border border-gray-200 rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-gray-50 hover:text-indigo-600 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-arrow-left"></i> {{ __('Back to Builder') }}
                    </a>

                    @if($survey->share_report_token)
                        <button @click="copyShareLink()"
                            class="px-5 py-3 bg-indigo-600 text-white rounded-xl font-bold text-xs uppercase tracking-wider shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center gap-2">
                            <i class="fa-solid fa-share-nodes"></i> {{ __('Copy Share Link') }}
                        </button>
                    @endif
                </div>
            </div>
        @else
            <!-- Public Stakeholder Header -->
            <div class="mb-8 bg-white p-8 rounded-3xl border border-gray-100 shadow-sm animate-in fade-in duration-300">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <span
                            class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-600">
                            {{ __('Interactive Report') }}
                        </span>
                        <h1 class="text-2xl font-black text-gray-900 tracking-tight mt-2" x-text="survey.title"></h1>
                        <p class="text-xs text-gray-500 font-medium mt-1"
                            x-text="survey.description || '{{ __('No description available.') }}'"></p>
                    </div>
                    <div class="bg-gray-50/50 rounded-2xl p-4 border border-gray-100 flex items-center gap-4">
                        <div class="text-center px-4 border-r border-gray-100">
                            <span
                                class="block text-[9px] font-black text-gray-400 uppercase tracking-widest">{{ __('Total Responses') }}</span>
                            <span class="text-lg font-black text-indigo-600" x-text="totalResponses"></span>
                        </div>
                        <div class="text-center px-4">
                            <span
                                class="block text-[9px] font-black text-gray-400 uppercase tracking-widest">{{ __('Status') }}</span>
                            <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 capitalize">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span x-text="survey.status"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Dashboard Widgets Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <template x-for="widget in visibleWidgets" :key="widget.widget_id">
                <div :style="widget.width === 'full' ? 'grid-column: span 2 / span 2;' : 'grid-column: span 1 / span 1;'"
                    class="bg-white rounded-3xl border border-gray-100 shadow-sm flex flex-col overflow-hidden animate-in fade-in slide-in-from-bottom-2 duration-500">
                    <!-- Widget Header -->
                    <div class="px-6 py-5 border-b border-gray-50 flex items-center justify-between">
                        <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider truncate" x-text="widget.title">
                        </h3>
                    </div>

                    <!-- Widget Body -->
                    <div class="p-6 flex-1 flex flex-col justify-center min-h-[300px] relative">
                        <!-- Metric Card -->
                        <template x-if="widget.chart_type === 'metric'">
                            <div class="text-center py-8 flex flex-col items-center justify-center">
                                <h2 class="text-5xl font-black text-indigo-600 tracking-tight"
                                    x-text="getMetricValue(widget.question_id)"></h2>

                                <!-- Stars for Star/Rating Questions -->
                                <template x-if="isRatingQuestion(widget.question_id)">
                                    <div class="flex justify-center gap-1 my-3 text-amber-400 text-lg">
                                        <template x-for="star in getStarRatingArray(widget.question_id)">
                                            <i class="fa-solid"
                                                :class="star === 'full' ? 'fa-star' : (star === 'half' ? 'fa-star-half-stroke' : 'fa-star text-gray-200')"></i>
                                        </template>
                                    </div>
                                </template>

                                <p class="text-[11px] text-gray-400 font-bold mt-2 uppercase tracking-wide">
                                    <i class="fa-solid fa-circle-info mr-1"></i>
                                    <span
                                        x-text="isNumericQuestion(widget.question_id) ? '{{ __('Average Response') }}' : '{{ __('Total Answered') }}'"></span>
                                </p>

                                <!-- Contextual Sub-Text -->
                                <p class="text-[10px] text-gray-400 font-semibold mt-1.5"
                                    x-text="getMetricSubText(widget.question_id)"></p>
                            </div>
                        </template>

                        <!-- Table Card -->
                        <template x-if="widget.chart_type === 'table'">
                            <div class="w-full">
                                <!-- Chartable Categorical Table -->
                                <template x-if="isChartableQuestion(widget.question_id)">
                                    <div class="overflow-x-auto w-full">
                                        <table class="w-full text-left border-collapse">
                                            <thead>
                                                <tr class="border-b border-gray-100 bg-gray-50/50">
                                                    <th
                                                        class="py-2.5 px-4 text-[9px] font-black text-gray-400 uppercase tracking-widest">
                                                        {{ __('Answer Option') }}</th>
                                                    <th
                                                        class="py-2.5 px-4 text-[9px] font-black text-gray-400 uppercase tracking-widest text-right">
                                                        {{ __('Count') }}</th>
                                                    <th
                                                        class="py-2.5 px-4 text-[9px] font-black text-gray-400 uppercase tracking-widest text-right">
                                                        {{ __('Percentage') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-50 text-xs font-semibold text-gray-600">
                                                <template x-for="row in getQuestionStats(widget.question_id)"
                                                    :key="row.value">
                                                    <tr class="hover:bg-gray-50/30 transition-colors">
                                                        <td class="py-2.5 px-4 text-gray-700" x-text="row.value"></td>
                                                        <td class="py-2.5 px-4 text-right" x-text="row.count"></td>
                                                        <td class="py-2.5 px-4 text-right">
                                                            <span x-text="row.percentage + '%'"></span>
                                                            <div
                                                                class="w-16 bg-gray-100 h-1.5 rounded-full inline-block ml-2 overflow-hidden">
                                                                <div class="bg-indigo-500 h-full"
                                                                    :style="'width: ' + row.percentage + '%'"></div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </template>

                                <!-- Qualitative Verbatim Responses List -->
                                <template x-if="!isChartableQuestion(widget.question_id)">
                                    <div class="flex flex-col gap-3">
                                        <div class="max-h-60 overflow-y-auto pr-1 flex flex-col gap-2.5 custom-scrollbar">
                                            <template x-for="(ans, idx) in getPaginatedAnswers(widget)" :key="idx">
                                                <div
                                                    class="text-xs font-medium text-gray-700 leading-relaxed italic border-l-4 border-indigo-500 pl-3 bg-gray-50/50 p-2.5 rounded-r-xl flex items-center justify-between gap-4">
                                                    <div class="flex-1">
                                                        <template
                                                            x-if="!ans.includes('base64,') && !ans.startsWith('uploads/')">
                                                            <span x-text="'&ldquo;' + ans + '&rdquo;'"></span>
                                                        </template>
                                                        <template x-if="ans.startsWith('uploads/')">
                                                            <div class="flex items-center gap-2">
                                                                <i class="fa-solid fa-file-arrow-down text-indigo-500"></i>
                                                                <a :href="'/' + ans" target="_blank"
                                                                    class="text-indigo-600 hover:underline font-bold"
                                                                    x-text="ans.split('/').pop()"></a>
                                                            </div>
                                                        </template>
                                                    </div>
                                                    <template x-if="ans.includes('base64,')">
                                                        <button @click="showSignature(ans)"
                                                            class="px-3 py-1 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all">
                                                            <i
                                                                class="fa-solid fa-signature mr-1"></i>{{ __('View Signature') }}
                                                        </button>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="getQuestionAnswers(widget.question_id).length === 0">
                                                <div
                                                    class="text-center py-8 text-gray-400 text-[11px] font-bold uppercase tracking-wider">
                                                    <i
                                                        class="fa-solid fa-folder-open text-lg mb-1.5 block"></i>{{ __('No Responses Recorded') }}
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Pagination Controls -->
                                        <template x-if="getQuestionAnswers(widget.question_id).length > 5">
                                            <div
                                                class="flex items-center justify-between border-t border-gray-100 pt-3 mt-1">
                                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                                                    Showing <span class="text-gray-600"
                                                        x-text="Math.min((widget.currentPage - 1) * 5 + 1, getQuestionAnswers(widget.question_id).length)"></span>
                                                    to <span class="text-gray-600"
                                                        x-text="Math.min(widget.currentPage * 5, getQuestionAnswers(widget.question_id).length)"></span>
                                                    of <span class="text-gray-600"
                                                        x-text="getQuestionAnswers(widget.question_id).length"></span>
                                                </div>
                                                <div class="flex items-center gap-1.5">
                                                    <button
                                                        @click="widget.currentPage = Math.max(1, widget.currentPage - 1)"
                                                        :disabled="widget.currentPage === 1"
                                                        class="px-2.5 py-1 bg-gray-50 hover:bg-indigo-50 text-gray-500 hover:text-indigo-600 disabled:opacity-50 disabled:hover:bg-gray-50 disabled:hover:text-gray-500 border border-gray-100 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all">
                                                        <i class="fa-solid fa-chevron-left mr-1"></i>Prev
                                                    </button>
                                                    <button
                                                        @click="widget.currentPage = Math.min(getTotalPages(widget), widget.currentPage + 1)"
                                                        :disabled="widget.currentPage >= getTotalPages(widget)"
                                                        class="px-2.5 py-1 bg-gray-50 hover:bg-indigo-50 text-gray-500 hover:text-indigo-600 disabled:opacity-50 disabled:hover:bg-gray-50 disabled:hover:text-gray-500 border border-gray-100 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all">
                                                        Next<i class="fa-solid fa-chevron-right ml-1"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- ChartJS Canvas -->
                        <div x-show="!['metric', 'table'].includes(widget.chart_type)" class="w-full h-64 relative">
                            <template x-if="isChartableQuestion(widget.question_id)">
                                <canvas :id="'canvas-' + widget.widget_id" class="w-full h-full"></canvas>
                            </template>
                            <template x-if="!isChartableQuestion(widget.question_id)">
                                <div
                                    class="h-full flex flex-col items-center justify-center text-center p-6 bg-gray-50/50 rounded-3xl border border-dashed border-gray-200">
                                    <div
                                        class="w-10 h-10 bg-amber-50 text-amber-500 rounded-xl flex items-center justify-center mb-2">
                                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                                    </div>
                                    <p class="text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        {{ __('Chart Not Available') }}</p>
                                    <p class="text-[10px] text-gray-400 font-semibold mt-1 max-w-xs">
                                        {{ __('Qualitative text questions are best viewed as a Data Table (Verbatims list) or Key Metric.') }}
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <template x-if="visibleWidgets.length === 0">
            <div
                class="bg-white rounded-3xl border border-gray-100 shadow-sm p-16 flex flex-col items-center justify-center text-center">
                <div class="w-16 h-16 bg-gray-50 text-gray-400 rounded-2xl flex items-center justify-center mb-4">
                    <i class="fa-solid fa-chart-line text-2xl"></i>
                </div>
                <h4 class="text-sm font-bold text-gray-800 uppercase tracking-wider">{{ __('No widgets visible') }}</h4>
                <p class="text-xs text-gray-400 font-medium mt-1 max-w-sm">
                    {{ __('This dashboard configurations has no visible widgets currently.') }}</p>
            </div>
        </template>
    </div>
@endsection

@push('scripts')
    <script>
        function dashboardPreview() {
            return {
                survey: {
                    title: "{{ $survey->title }}",
                    description: "{{ $survey->description }}",
                    status: "{{ $survey->status->value }}"
                },
                totalResponses: {{ $survey->responses()->count() }},
                analysisData: {!! json_encode($analysis) !!},
                widgets: {!! json_encode($layout) !!},
                chartInstances: {},

                colorPalettes: {
                    indigo: ['#4f46e5', '#a5b4fc', '#312e81', '#e0e7ff', '#6366f1', '#c7d2fe', '#3730a3', '#818cf8'],
                    emerald: ['#10b981', '#a7f3d0', '#064e3b', '#d1fae5', '#059669', '#6ee7b7', '#065f46', '#34d399'],
                    rose: ['#f43f5e', '#fda4af', '#9f1239', '#fecdd3', '#e11d48', '#fff1f2', '#fb7185', '#881337'],
                    amber: ['#f59e0b', '#fcd34d', '#92400e', '#fef3c7', '#d97706', '#fde68a', '#b45309', '#fbbf24'],
                    purple: ['#8b5cf6', '#c4b5fd', '#4c1d95', '#ede9fe', '#7c3aed', '#ddd6fe', '#5b21b6', '#a78bfa'],
                    vibrant: ['#6366f1', '#10b981', '#f43f5e', '#f59e0b', '#8b5cf6', '#06b6d4', '#ec4899', '#f97316']
                },

                get visibleWidgets() {
                    return this.widgets.filter(w => w.visible);
                },

                initPreview() {
                    this.widgets = this.widgets.map(w => ({
                        ...w,
                        currentPage: 1
                    }));
                    this.$nextTick(() => {
                        this.renderAllCharts();
                    });
                },

                getMetricValue(questionId) {
                    const question = this.analysisData.find(q => q.id === questionId);
                    if (!question) return 'N/A';

                    const numericTypes = ['number', 'rating', 'starRating', 'range'];
                    if (numericTypes.includes(question.type)) {
                        const values = (question.answers || []).filter(v => v !== null && v !== '' && !isNaN(v)).map(Number);
                        if (values.length === 0) return '0';
                        const sum = values.reduce((a, b) => a + b, 0);
                        return (sum / values.length).toFixed(1);
                    }

                    return question.answered_count || 0;
                },

                isNumericQuestion(questionId) {
                    const question = this.analysisData.find(q => q.id === questionId);
                    if (!question) return false;
                    return ['number', 'rating', 'starRating', 'range'].includes(question.type);
                },

                isRatingQuestion(questionId) {
                    const question = this.analysisData.find(q => q.id === questionId);
                    if (!question) return false;
                    return ['rating', 'starRating'].includes(question.type);
                },

                getStarRatingArray(questionId) {
                    const avg = parseFloat(this.getMetricValue(questionId)) || 0;
                    const stars = [];
                    for (let i = 1; i <= 5; i++) {
                        if (avg >= i) {
                            stars.push('full');
                        } else if (avg >= i - 0.5) {
                            stars.push('half');
                        } else {
                            stars.push('empty');
                        }
                    }
                    return stars;
                },

                getMetricSubText(questionId) {
                    const question = this.analysisData.find(q => q.id === questionId);
                    if (!question) return '';

                    const answered = question.answered_count || 0;
                    if (this.isNumericQuestion(questionId)) {
                        return `Based on ${answered} rating${answered === 1 ? '' : 's'}`;
                    }

                    const pct = this.totalResponses > 0 ? ((answered / this.totalResponses) * 100).toFixed(1) : 0;
                    return `${pct}% of total respondents (${answered} out of ${this.totalResponses})`;
                },

                isChartableQuestion(questionId) {
                    const question = this.analysisData.find(q => q.id === questionId);
                    return question ? question.isChartable : false;
                },

                getQuestionAnswers(questionId) {
                    const question = this.analysisData.find(q => q.id === questionId);
                    if (!question || !question.answers) return [];
                    return question.answers.filter(ans => ans !== null && ans !== '');
                },

                getPaginatedAnswers(widget) {
                    const answers = this.getQuestionAnswers(widget.question_id);
                    const page = widget.currentPage || 1;
                    const start = (page - 1) * 5;
                    return answers.slice(start, start + 5);
                },

                getTotalPages(widget) {
                    const answers = this.getQuestionAnswers(widget.question_id);
                    return Math.ceil(answers.length / 5) || 1;
                },

                showSignature(data) {
                    Swal.fire({
                        title: 'Signature Preview',
                        imageUrl: data,
                        imageAlt: 'Signature',
                        confirmButtonColor: '#4f46e5',
                        customClass: {
                            popup: 'rounded-3xl',
                            confirmButton: 'rounded-xl text-xs uppercase tracking-wider font-bold px-6 py-3'
                        }
                    });
                },

                getQuestionStats(questionId) {
                    const question = this.analysisData.find(q => q.id === questionId);
                    return question ? question.stats : [];
                },

                renderAllCharts() {
                    this.visibleWidgets.forEach(widget => {
                        this.renderWidgetChart(widget);
                    });
                },

                renderWidgetChart(widget) {
                    if (['metric', 'table'].includes(widget.chart_type)) return;

                    const question = this.analysisData.find(q => q.id === widget.question_id);
                    if (!question) return;

                    const canvasId = 'canvas-' + widget.widget_id;

                    setTimeout(() => {
                        const canvasElement = document.getElementById(canvasId);
                        if (!canvasElement) return;

                        const colorTheme = widget.chart_type === 'donut' ? 'vibrant' : (widget.config.color_scheme || 'indigo');
                        const palette = this.colorPalettes[colorTheme] || this.colorPalettes['indigo'];

                        const stats = (question.stats || []).filter(s => !s.is_missing);
                        const labels = stats.map(s => s.value);
                        const counts = stats.map(s => s.count);

                        if (labels.length === 0) return;

                        const colors = labels.map((_, i) => palette[i % palette.length]);
                        const primaryColor = palette[0];

                        let type = widget.chart_type;
                        let indexAxis = 'x';
                        let fill = false;

                        if (widget.chart_type === 'horizontal') {
                            type = 'bar';
                            indexAxis = 'y';
                        } else if (widget.chart_type === 'area') {
                            type = 'line';
                            fill = true;
                        } else if (widget.chart_type === 'donut') {
                            type = 'doughnut';
                        }

                        const isCategorical = ['pie', 'doughnut', 'polarArea', 'bar'].includes(type);
                        const showPercentages = widget.config.show_percentages ?? true;

                        const ctx = canvasElement.getContext('2d');
                        this.chartInstances[widget.widget_id] = new Chart(ctx, {
                            type: type,
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: '{{ __('Responses') }}',
                                    data: counts,
                                    backgroundColor: isCategorical ? colors : (fill ? `${primaryColor}44` : primaryColor),
                                    borderColor: isCategorical ? (['bar', 'horizontal'].includes(widget.chart_type) ? colors : '#fff') : primaryColor,
                                    borderWidth: (type === 'line' || type === 'radar' || type === 'area') ? 3 : 1,
                                    fill: fill,
                                    borderRadius: (type === 'bar') ? 6 : 0,
                                    tension: 0.4,
                                    pointBackgroundColor: primaryColor,
                                    pointRadius: 4
                                }]
                            },
                            options: {
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
                                            font: { weight: '700', size: 9, family: 'Inter' },
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
                                        callbacks: {
                                            label: function (context) {
                                                const val = context.raw;
                                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                                const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                                return showPercentages ? `${context.label}: ${val} (${pct}%)` : `${context.label}: ${val}`;
                                            }
                                        }
                                    }
                                },
                                scales: ['pie', 'doughnut', 'polarArea'].includes(type) ? {} : {
                                    x: {
                                        grid: { display: false },
                                        ticks: { font: { family: 'Inter', weight: '600', size: 10 } }
                                    },
                                    y: {
                                        grid: { color: '#f1f5f9' },
                                        ticks: { font: { family: 'Inter', weight: '600', size: 10 } },
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    }, 50);
                },

                copyShareLink() {
                    const link = "{{ route('surveys.dashboard-preview', $survey) }}?token={{ $survey->share_report_token }}";
                    navigator.clipboard.writeText(link).then(() => {
                        Swal.fire({
                            title: 'Link Copied!',
                            text: 'Public presentation report link copied to clipboard.',
                            icon: 'success',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                    }).catch(() => {
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to copy link. Please manually copy: ' + link,
                            icon: 'error',
                            confirmButtonColor: '#4f46e5'
                        });
                    });
                }
            };
        }
    </script>
@endpush