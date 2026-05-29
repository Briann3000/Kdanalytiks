@extends('surveys.hub')

@section('survey-content')
    <div x-data="dashboardBuilder()" x-init="initBuilder()" class="min-h-screen bg-gray-50/50 pb-12">
        <!-- Header Controls -->
        <div
            class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
            <div>
                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">
                    {{ __('Interactive Dashboard Builder') }}</h3>
                <p class="text-xs text-gray-500 font-medium mt-1">
                    {{ __('Design a customized analytics dashboard for stakeholders by choosing charts, sizes, and order.') }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="previewDashboard()"
                    class="px-5 py-3 bg-white text-gray-700 border border-gray-200 rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-gray-50 hover:text-indigo-600 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-eye"></i> {{ __('Live Preview') }}
                </button>
                <button @click="saveLayout()" :disabled="saving"
                    class="px-5 py-3 bg-indigo-600 text-white rounded-xl font-bold text-xs uppercase tracking-wider shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center gap-2 disabled:opacity-50">
                    <i class="fa-solid" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                    <span x-text="saving ? '{{ __('Saving...') }}' : '{{ __('Save Layout') }}'"></span>
                </button>
            </div>
        </div>

        <!-- Main Workspace Flex Container -->
        <div class="flex flex-col md:flex-row gap-8 items-start">

            <!-- Left Sidebar: Question Palette (Mini Sidebar) -->
            <div class="w-full md:w-72 flex-shrink-0 flex flex-col gap-6">
                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">{{ __('Question Palette') }}
                    </h4>
                    <p class="text-[11px] text-gray-400 font-medium mb-4">
                        {{ __('Drag questions onto the canvas or click the plus button to add widgets.') }}</p>

                    <div class="flex flex-col gap-3 max-h-[600px] overflow-y-auto pr-1">
                        <template x-for="question in analysisData" :key="question.id">
                            <div draggable="true" @dragstart="draggedPaletteQuestion = question"
                                class="p-4 bg-gray-50/50 hover:bg-indigo-50/50 border border-gray-100 hover:border-indigo-100 rounded-2xl cursor-grab active:cursor-grabbing transition-all group flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <span
                                            class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider bg-gray-200/60 text-gray-500"
                                            x-text="question.type"></span>
                                        <template x-if="question.isChartable">
                                            <span
                                                class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-600"><i
                                                    class="fa-solid fa-chart-simple mr-0.5"></i> Chart</span>
                                        </template>
                                    </div>
                                    <h5 class="text-xs font-semibold text-gray-700 truncate" x-text="question.label"></h5>
                                </div>
                                <button @click="addWidget(question)"
                                    class="text-gray-400 hover:text-indigo-600 transition-colors">
                                    <i class="fa-solid fa-plus-circle text-lg"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Right Main Area: Dashboard Canvas (Fills remaining horizontal space) -->
            <div class="flex-grow w-full min-w-0">
                <div @dragover.prevent="canvasDragOver = true" @dragleave="canvasDragOver = false"
                    @drop="dropPaletteQuestion()"
                    :class="canvasDragOver ? 'border-2 border-dashed border-indigo-400 bg-indigo-50/10' : 'border-2 border-transparent'"
                    class="rounded-3xl transition-all min-h-[500px]">
                    <template x-if="widgets.length === 0">
                        <div
                            class="bg-white rounded-3xl border border-gray-100 shadow-sm p-16 flex flex-col items-center justify-center text-center">
                            <div
                                class="w-16 h-16 bg-indigo-50 text-indigo-500 rounded-2xl flex items-center justify-center mb-4">
                                <i class="fa-solid fa-table-cells-large text-2xl animate-pulse"></i>
                            </div>
                            <h4 class="text-sm font-bold text-gray-800 uppercase tracking-wider">
                                {{ __('Your Dashboard is Empty') }}</h4>
                            <p class="text-xs text-gray-400 font-medium mt-1 max-w-sm">
                                {{ __('Get started by dragging questions from the palette or clicking the plus button to build your interactive report dashboard.') }}
                            </p>
                        </div>
                    </template>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <template x-for="(widget, index) in widgets" :key="widget.widget_id">
                            <div draggable="true" @dragstart="draggedWidgetIndex = index"
                                @dragover.prevent="widgetDragOverIndex = index" @dragleave="widgetDragOverIndex = null"
                                @drop="dropWidget(index)" :class="[
                                    widgetDragOverIndex === index ? 'ring-2 ring-indigo-400 ring-offset-2' : '',
                                    !widget.visible ? 'opacity-60 bg-gray-50 border-dashed' : 'bg-white'
                                ]"
                                :style="widget.width === 'full' ? 'grid-column: span 2 / span 2;' : 'grid-column: span 1 / span 1;'"
                                class="rounded-3xl border border-gray-100 shadow-sm transition-all flex flex-col overflow-hidden group/widget relative">
                                <!-- Widget Header -->
                                <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between gap-4 cursor-move bg-gray-50/20"
                                    title="{{ __('Drag to reorder') }}">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <div class="text-gray-400 group-hover/widget:text-gray-600 transition-colors">
                                            <i class="fa-solid fa-grip-vertical"></i>
                                        </div>
                                        <input type="text" x-model="widget.title"
                                            class="w-full text-xs font-bold text-gray-800 bg-transparent border-0 border-b border-transparent focus:border-indigo-400 focus:ring-0 px-0 py-0.5"
                                            @change="widgetTitleChanged(widget)" />
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <!-- Settings Toggle -->
                                        <button @click="widget.showSettings = !widget.showSettings"
                                            :class="widget.showSettings ? 'text-indigo-600 bg-indigo-50' : 'text-gray-400 hover:text-gray-600 bg-transparent'"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center transition-all">
                                            <i class="fa-solid fa-cog"></i>
                                        </button>
                                        <!-- Delete Button -->
                                        <button @click="removeWidget(index)"
                                            class="w-8 h-8 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg flex items-center justify-center transition-all">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Settings Drawer (Inside Widget) -->
                                <div x-show="widget.showSettings" x-collapse
                                    :class="widget.width === 'full' ? 'grid-cols-1 sm:grid-cols-2 md:grid-cols-4' : 'grid-cols-1'"
                                    class="bg-gray-50/50 border-b border-gray-50 p-6 grid gap-6">
                                    <!-- Widget Type -->
                                    <div>
                                        <label
                                            class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">{{ __('Widget Type') }}</label>
                                        <select x-model="widget.chart_type" @change="updateWidgetType(widget)"
                                            class="w-full text-xs rounded-xl border-gray-200 focus:border-indigo-400 focus:ring focus:ring-indigo-100 font-medium">
                                            <option value="bar">{{ __('Bar Chart') }}</option>
                                            <option value="horizontal">{{ __('Horizontal Bar') }}</option>
                                            <option value="pie">{{ __('Pie Chart') }}</option>
                                            <option value="donut">{{ __('Donut Chart') }}</option>
                                            <option value="line">{{ __('Line Chart') }}</option>
                                            <option value="area">{{ __('Area Chart') }}</option>
                                            <option value="table">{{ __('Data Table') }}</option>
                                            <option value="metric">{{ __('Key Metric') }}</option>
                                        </select>
                                    </div>

                                    <!-- Widget Width -->
                                    <div>
                                        <label
                                            class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">{{ __('Widget Width') }}</label>
                                        <select x-model="widget.width"
                                            class="w-full text-xs rounded-xl border-gray-200 focus:border-indigo-400 focus:ring focus:ring-indigo-100 font-medium">
                                            <option value="half">{{ __('Half Width (50%)') }}</option>
                                            <option value="full">{{ __('Full Width (100%)') }}</option>
                                        </select>
                                    </div>

                                    <!-- Color Scheme -->
                                    <div>
                                        <label
                                            class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">{{ __('Color Scheme') }}</label>
                                        <select x-model="widget.config.color_scheme"
                                            @change="refreshChart(widget.widget_id)"
                                            class="w-full text-xs rounded-xl border-gray-200 focus:border-indigo-400 focus:ring focus:ring-indigo-100 font-medium">
                                            <option value="indigo">{{ __('Indigo Accent') }}</option>
                                            <option value="vibrant">{{ __('Vibrant Palette') }}</option>
                                            <option value="emerald">{{ __('Emerald Cool') }}</option>
                                            <option value="rose">{{ __('Rose Delicate') }}</option>
                                            <option value="amber">{{ __('Amber Warm') }}</option>
                                            <option value="purple">{{ __('Purple Royal') }}</option>
                                        </select>
                                    </div>

                                    <!-- Checkbox / Toggles -->
                                    <div class="flex flex-col justify-center gap-3">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" x-model="widget.visible"
                                                class="rounded text-indigo-600 focus:ring-indigo-100 border-gray-300">
                                            <span
                                                class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">{{ __('Visible in Report') }}</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer"
                                            x-show="['bar', 'horizontal', 'pie', 'donut'].includes(widget.chart_type)">
                                            <input type="checkbox" x-model="widget.config.show_percentages"
                                                @change="refreshChart(widget.widget_id)"
                                                class="rounded text-indigo-600 focus:ring-indigo-100 border-gray-300">
                                            <span
                                                class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">{{ __('Show Percentages') }}</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Widget Body / Chart Area -->
                                <div class="p-6 flex-1 flex flex-col justify-center min-h-[300px] relative">
                                    <!-- Render Key Metric -->
                                    <template x-if="widget.chart_type === 'metric'">
                                        <div class="text-center py-8 flex flex-col items-center justify-center">
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2"
                                                x-text="widget.title"></p>
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

                                    <!-- Render Table -->
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
                                                        <tbody
                                                            class="divide-y divide-gray-50 text-xs font-semibold text-gray-600">
                                                            <template x-for="row in getQuestionStats(widget.question_id)"
                                                                :key="row.value">
                                                                <tr class="hover:bg-gray-50/30 transition-colors">
                                                                    <td class="py-2.5 px-4 text-gray-700"
                                                                        x-text="row.value"></td>
                                                                    <td class="py-2.5 px-4 text-right" x-text="row.count">
                                                                    </td>
                                                                    <td class="py-2.5 px-4 text-right">
                                                                        <span x-text="row.percentage + '%'"></span>
                                                                        <div
                                                                            class="w-16 bg-gray-100 h-1.5 rounded-full inline-block ml-2 overflow-hidden">
                                                                            <div class="bg-indigo-500 h-full"
                                                                                :style="'width: ' + row.percentage + '%'">
                                                                            </div>
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
                                                    <div
                                                        class="max-h-60 overflow-y-auto pr-1 flex flex-col gap-2.5 custom-scrollbar">
                                                        <template x-for="(ans, idx) in getPaginatedAnswers(widget)"
                                                            :key="idx">
                                                            <div
                                                                class="text-xs font-medium text-gray-700 leading-relaxed italic border-l-4 border-indigo-500 pl-3 bg-gray-50/50 p-2.5 rounded-r-xl flex items-center justify-between gap-4">
                                                                <div class="flex-1">
                                                                    <template
                                                                        x-if="!ans.includes('base64,') && !ans.startsWith('uploads/')">
                                                                        <span x-text="'&ldquo;' + ans + '&rdquo;'"></span>
                                                                    </template>
                                                                    <template x-if="ans.startsWith('uploads/')">
                                                                        <div class="flex items-center gap-2">
                                                                            <i
                                                                                class="fa-solid fa-file-arrow-down text-indigo-500"></i>
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
                                                        <template
                                                            x-if="getQuestionAnswers(widget.question_id).length === 0">
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
                                                            <div
                                                                class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">
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

                                    <!-- Render Chart.js Canvas -->
                                    <div x-show="!['metric', 'table'].includes(widget.chart_type)"
                                        class="w-full h-64 relative">
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
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function dashboardBuilder() {
            return {
                analysisData: {!! json_encode($analysis) !!},
                totalResponses: {{ $survey->responses()->count() }},
                widgets: [],
                saving: false,
                canvasDragOver: false,
                draggedPaletteQuestion: null,
                draggedWidgetIndex: null,
                widgetDragOverIndex: null,
                chartInstances: {},

                colorPalettes: {
                    indigo: ['#4f46e5', '#a5b4fc', '#312e81', '#e0e7ff', '#6366f1', '#c7d2fe', '#3730a3', '#818cf8'],
                    emerald: ['#10b981', '#a7f3d0', '#064e3b', '#d1fae5', '#059669', '#6ee7b7', '#065f46', '#34d399'],
                    rose: ['#f43f5e', '#fda4af', '#9f1239', '#fecdd3', '#e11d48', '#fff1f2', '#fb7185', '#881337'],
                    amber: ['#f59e0b', '#fcd34d', '#92400e', '#fef3c7', '#d97706', '#fde68a', '#b45309', '#fbbf24'],
                    purple: ['#8b5cf6', '#c4b5fd', '#4c1d95', '#ede9fe', '#7c3aed', '#ddd6fe', '#5b21b6', '#a78bfa'],
                    vibrant: ['#6366f1', '#10b981', '#f43f5e', '#f59e0b', '#8b5cf6', '#06b6d4', '#ec4899', '#f97316']
                },

                initBuilder() {
                    const initialLayout = {!! json_encode($layout) !!};
                    this.widgets = initialLayout.map(w => ({
                        ...w,
                        showSettings: false,
                        currentPage: 1
                    }));

                    this.$nextTick(() => {
                        this.renderAllCharts();
                    });
                },

                // Add new widget from question palette
                addWidget(question) {
                    let chartType = 'table';
                    if (['radio', 'select', 'checkbox', 'select-one', 'select-multiple', 'radio-group', 'checkbox-group', 'rating', 'starRating', 'toggle'].includes(question.type)) {
                        chartType = 'bar';
                    } else if (['number', 'rating', 'starRating', 'range'].includes(question.type)) {
                        chartType = 'metric';
                    }

                    const newWidget = {
                        widget_id: 'w_' + Math.random().toString(36).substr(2, 9),
                        question_id: question.id,
                        chart_type: chartType,
                        title: question.label,
                        width: 'full',
                        visible: true,
                        showSettings: false,
                        currentPage: 1,
                        config: {
                            show_percentages: true,
                            color_scheme: 'indigo',
                            limit_top_n: 10
                        }
                    };

                    this.widgets.push(newWidget);
                    this.$nextTick(() => {
                        this.renderWidgetChart(newWidget);
                    });
                },

                removeWidget(index) {
                    const widget = this.widgets[index];
                    if (this.chartInstances[widget.widget_id]) {
                        this.chartInstances[widget.widget_id].destroy();
                        delete this.chartInstances[widget.widget_id];
                    }
                    this.widgets.splice(index, 1);
                },

                // Palette Drag & Drop
                dropPaletteQuestion() {
                    if (this.draggedPaletteQuestion) {
                        this.addWidget(this.draggedPaletteQuestion);
                        this.draggedPaletteQuestion = null;
                    }
                    this.canvasDragOver = false;
                },

                // Canvas Widget Sorting Drag & Drop
                dropWidget(targetIndex) {
                    if (this.draggedWidgetIndex !== null && this.draggedWidgetIndex !== targetIndex) {
                        const dragged = this.widgets.splice(this.draggedWidgetIndex, 1)[0];
                        this.widgets.splice(targetIndex, 0, dragged);
                        this.$nextTick(() => {
                            this.renderAllCharts();
                        });
                    }
                    this.draggedWidgetIndex = null;
                    this.widgetDragOverIndex = null;
                },

                // Metric Value Helper
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

                widgetTitleChanged(widget) {
                    // If it's a metric or table, no chart JS refresh is needed.
                    // But for charts, changing the title doesn't affect canvas unless it's drawn inside chart.js (we don't draw it inside ChartJS, we draw in HTML)
                },

                updateWidgetType(widget) {
                    this.$nextTick(() => {
                        this.refreshChart(widget.widget_id);
                    });
                },

                refreshChart(widgetId) {
                    const widget = this.widgets.find(w => w.widget_id === widgetId);
                    if (widget) {
                        this.renderWidgetChart(widget);
                    }
                },

                renderAllCharts() {
                    this.widgets.forEach(widget => {
                        this.renderWidgetChart(widget);
                    });
                },

                renderWidgetChart(widget) {
                    if (['metric', 'table'].includes(widget.chart_type)) {
                        // Destroy if existing
                        if (this.chartInstances[widget.widget_id]) {
                            this.chartInstances[widget.widget_id].destroy();
                            delete this.chartInstances[widget.widget_id];
                        }
                        return;
                    }

                    const question = this.analysisData.find(q => q.id === widget.question_id);
                    if (!question) return;

                    const canvasId = 'canvas-' + widget.widget_id;

                    // Use setTimeout to ensure Alpine has rendered the canvas element to the DOM
                    setTimeout(() => {
                        const canvasElement = document.getElementById(canvasId);
                        if (!canvasElement) return;

                        if (this.chartInstances[widget.widget_id]) {
                            this.chartInstances[widget.widget_id].destroy();
                        }

                        const colorTheme = widget.chart_type === 'donut' ? 'vibrant' : (widget.config.color_scheme || 'indigo');
                        const palette = this.colorPalettes[colorTheme] || this.colorPalettes['indigo'];

                        // Filter out missing/skipped options
                        const stats = (question.stats || []).filter(s => !s.is_missing);
                        const labels = stats.map(s => s.value);
                        const counts = stats.map(s => s.count);

                        if (labels.length === 0) {
                            // Render empty state on canvas if needed
                            return;
                        }

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

                // Save layout configs to backend
                async saveLayout() {
                    this.saving = true;

                    // Clean widget configuration array to avoid sending unneeded front-end states (like showSettings)
                    const cleanLayout = this.widgets.map(w => ({
                        widget_id: w.widget_id,
                        question_id: w.question_id,
                        chart_type: w.chart_type,
                        title: w.title,
                        width: w.width,
                        visible: w.visible,
                        config: w.config
                    }));

                    try {
                        const response = await fetch("{{ route('surveys.dashboard-layout.save', $survey) }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ layout: cleanLayout })
                        });

                        const result = await response.json();

                        if (response.ok && result.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Dashboard layout configuration saved successfully.',
                                icon: 'success',
                                confirmButtonColor: '#4f46e5',
                                customClass: {
                                    popup: 'rounded-3xl',
                                    confirmButton: 'rounded-xl text-xs uppercase tracking-wider font-bold px-6 py-3'
                                }
                            });
                        } else {
                            throw new Error(result.message || 'Failed to save configuration');
                        }
                    } catch (error) {
                        Swal.fire({
                            title: 'Error!',
                            text: error.message || 'An unexpected error occurred while saving the dashboard layout.',
                            icon: 'error',
                            confirmButtonColor: '#4f46e5',
                            customClass: {
                                popup: 'rounded-3xl',
                                confirmButton: 'rounded-xl text-xs uppercase tracking-wider font-bold px-6 py-3'
                            }
                        });
                    } finally {
                        this.saving = false;
                    }
                },

                previewDashboard() {
                    window.open("{{ route('surveys.dashboard-preview', $survey) }}", '_blank');
                }
            };
        }
    </script>
@endpush