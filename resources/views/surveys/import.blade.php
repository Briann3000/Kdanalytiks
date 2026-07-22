@extends('layouts.app')

@section('title', __('Import Survey Data'))

@section('content')
    <div x-data="importWizard()" class="max-w-5xl mx-auto">
        {{-- ── Page Header ────────────────────────────────────────────── --}}
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('surveys.index', ['status' => 'active']) }}"
                    class="text-gray-400 hover:text-[#2271b1] transition-colors text-xs font-bold">
                    {{ __('Surveys') }}
                </a>
                <i class="fa-solid fa-chevron-right text-gray-300 text-[9px]"></i>
                <span class="text-xs font-bold text-gray-700">{{ __('Import Data') }}</span>
            </div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">{{ __('Import Survey Data') }}</h2>
            <p class="mt-1 text-sm text-gray-500 font-medium">
                {{ __('Upload an SPSS, Excel, or CSV file to create a fully analysable survey project.') }}
            </p>
        </div>

        {{-- ── Step Indicator ──────────────────────────────────────────── --}}
        <div class="flex items-center mb-10">
            <template x-for="(step, i) in steps" :key="i">
                <div class="flex items-center">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all duration-300"
                            :class="{
                                 'bg-[#2271b1] text-white shadow-lg shadow-blue-200': currentStep === i + 1,
                                 'bg-green-500 text-white': currentStep > i + 1,
                                 'bg-gray-100 text-gray-400': currentStep < i + 1
                             }">
                            <template x-if="currentStep > i + 1">
                                <i class="fa-solid fa-check text-[10px]"></i>
                            </template>
                            <template x-if="currentStep <= i + 1">
                                <span x-text="i + 1"></span>
                            </template>
                        </div>
                        <span class="text-xs font-bold transition-colors"
                            :class="currentStep === i + 1 ? 'text-[#2271b1]' : (currentStep > i + 1 ? 'text-green-600' : 'text-gray-400')"
                            x-text="step"></span>
                    </div>
                    <template x-if="i < steps.length - 1">
                        <div class="w-16 h-px mx-4 transition-colors"
                            :class="currentStep > i + 1 ? 'bg-green-400' : 'bg-gray-200'"></div>
                    </template>
                </div>
            </template>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- STEP 1 — Upload --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div x-show="currentStep === 1" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">

            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-gray-50">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                        {{ __('Step 1 — Upload Your Data File') }}</h3>
                    <p class="text-xs text-gray-400 font-medium mt-1">
                        {{ __('Supported formats: SPSS (.sav), Excel (.xlsx, .xls), CSV (.csv), ZIP bundle (.zip)') }}
                    </p>
                </div>

                <div class="p-8">
                    {{-- Survey Title --}}
                    <div class="mb-6">
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">
                            {{ __('Survey Title') }}
                            <span class="text-red-400">*</span>
                        </label>
                        <input type="text" x-model="surveyTitle" id="import-survey-title"
                            class="w-full px-4 py-3 border border-gray-200 rounded-2xl text-sm font-medium focus:ring-2 focus:ring-[#2271b1]/20 focus:border-[#2271b1] transition-all">
                    </div>

                    {{-- Drag & Drop Zone --}}
                    <div id="drop-zone" @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false"
                        @drop.prevent="handleDrop($event)" @click="$refs.fileInput.click()"
                        :class="dragOver
                            ? 'border-[#2271b1] bg-blue-50/50 scale-[1.01]'
                            : (uploadedFile ? 'border-green-400 bg-green-50/30' : 'border-gray-200 hover:border-[#2271b1] hover:bg-gray-50/50')"
                        class="border-2 border-dashed rounded-3xl p-16 text-center cursor-pointer transition-all duration-200">

                        <input type="file" x-ref="fileInput" class="hidden" accept=".sav,.xlsx,.xls,.csv,.zip,.kmsurvey"
                            @change="handleFileSelect($event)">

                        <template x-if="!uploadedFile">
                            <div>
                                <div
                                    class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gray-100 flex items-center justify-center">
                                    <i class="fa-solid fa-file-arrow-up text-3xl text-gray-300"></i>
                                </div>
                                <p class="text-sm font-bold text-gray-700 mb-2">
                                    {{ __('Drop your file here or click to browse') }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ __('SPSS .sav · Excel .xlsx · CSV · ZIP bundle') }}</p>
                                <p class="text-[10px] text-gray-300 mt-2 font-medium">{{ __('Maximum file size: 50 MB') }}
                                </p>
                            </div>
                        </template>

                        <template x-if="uploadedFile">
                            <div>
                                <div class="w-20 h-20 mx-auto mb-6 rounded-2xl flex items-center justify-center shadow-lg"
                                    :class="{
                                         'bg-green-100': fileExtension === 'sav',
                                         'bg-emerald-100': ['xlsx','xls'].includes(fileExtension),
                                         'bg-amber-100': fileExtension === 'csv',
                                         'bg-indigo-100': fileExtension === 'kmsurvey' || fileExtension === 'zip',
                                     }">
                                    <i class="text-3xl" :class="{
                                           'fa-solid fa-chart-bar text-green-600': fileExtension === 'sav',
                                           'fa-solid fa-file-excel text-emerald-600': ['xlsx','xls'].includes(fileExtension),
                                           'fa-solid fa-file-csv text-amber-600': fileExtension === 'csv',
                                           'fa-solid fa-box-archive text-indigo-600': fileExtension === 'kmsurvey' || fileExtension === 'zip',
                                       }"></i>
                                </div>
                                <p class="text-sm font-black text-gray-900 mb-1" x-text="uploadedFile.name"></p>
                                <p class="text-xs text-gray-400 font-medium" x-text="formatFileSize(uploadedFile.size)"></p>
                                <button type="button" @click.stop="uploadedFile = null; fileExtension = ''"
                                    class="mt-4 text-[10px] font-black text-red-400 hover:text-red-600 uppercase tracking-widest transition-colors">
                                    <i class="fa-solid fa-xmark mr-1"></i> {{ __('Remove') }}
                                </button>
                            </div>
                        </template>
                    </div>

                    <template x-if="uploadError">
                        <div class="mt-4 p-4 bg-red-50 border border-red-100 rounded-2xl">
                            <p class="text-xs text-red-600 font-bold" x-text="uploadError"></p>
                        </div>
                    </template>
                </div>

                <div class="p-8 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button type="button" @click="runPreview" :disabled="!uploadedFile || !surveyTitle.trim() || loading"
                        class="px-8 py-3 bg-[#2271b1] text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-zinc-200/50 hover:bg-[#135e96] disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2">
                        <span x-show="loading"><i class="fa-solid fa-spinner fa-spin"></i></span>
                        <span x-show="!loading"><i class="fa-solid fa-arrow-right"></i></span>
                        <span x-text="loading ? '{{ __('Analysing file...') }}' : '{{ __('Continue') }}'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- STEP 2 — Mapping & Preview --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div x-show="currentStep === 2" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">

            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-gray-50 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                            {{ __('Step 2 — Review & Map Columns') }}</h3>
                        <p class="text-xs text-gray-400 font-medium mt-1">
                            {{ __('Edit question labels and types. Uncheck columns you want to exclude.') }}
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-black text-[#2271b1]" x-text="rowCount.toLocaleString()"></span>
                        <span class="text-xs text-gray-400 font-medium"> {{ __('rows detected') }}</span>
                    </div>
                </div>

                {{-- Mapping Table --}}
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-widest w-10">
                                    {{ __('Include') }}</th>
                                <th
                                    class="px-6 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-widest">
                                    {{ __('Variable') }}</th>
                                <th
                                    class="px-6 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-widest min-w-[220px]">
                                    {{ __('Question Label') }}</th>
                                <th
                                    class="px-6 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-widest w-36">
                                    {{ __('Type') }}</th>
                                <th
                                    class="px-6 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-widest">
                                    {{ __('Options / Values') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <template x-for="(col, i) in mapping" :key="i">
                                <tr :class="col.include ? 'bg-white' : 'bg-gray-50 opacity-60'">
                                    {{-- Include toggle --}}
                                    <td class="px-6 py-4">
                                        <input type="checkbox" x-model="col.include"
                                            class="h-4 w-4 text-[#2271b1] border-gray-300 rounded focus:ring-[#2271b1] cursor-pointer">
                                    </td>
                                    {{-- Variable name --}}
                                    <td class="px-6 py-4">
                                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest"
                                            x-text="col.name"></span>
                                    </td>
                                    {{-- Editable label --}}
                                    <td class="px-6 py-4">
                                        <input type="text" x-model="col.label"
                                            class="w-full px-3 py-1.5 border border-gray-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-[#2271b1]/20 focus:border-[#2271b1] transition-all"
                                            :disabled="!col.include">
                                    </td>
                                    {{-- Type dropdown --}}
                                    <td class="px-6 py-4">
                                        <select x-model="col.type"
                                            class="w-full px-3 py-1.5 border border-gray-200 rounded-xl text-[10px] font-black tracking-widest focus:ring-2 focus:ring-[#2271b1]/20 focus:border-[#2271b1] transition-all appearance-none"
                                            :disabled="!col.include">
                                            <option value="text">{{ __('Text') }}</option>
                                            <option value="textarea">{{ __('Long Text (Text Area)') }}</option>
                                            <option value="radio">{{ __('Radio ') }}</option>
                                            <option value="checkbox">{{ __('Checkbox') }}</option>
                                            <option value="select">{{ __('Dropdown') }}</option>
                                            <option value="rating">{{ __('Likert Scale') }}</option>
                                            <option value="number">{{ __('Number') }}</option>
                                            <option value="decimal">{{ __('Decimal') }}</option>
                                            <option value="date">{{ __('Date') }}</option>
                                        </select>
                                    </td>
                                    {{-- Options preview --}}
                                    <td class="px-6 py-4">
                                        <template x-if="col.value_labels && Object.keys(col.value_labels).length > 0">
                                            <div class="flex flex-wrap gap-1">
                                                <template x-for="(label, code) in col.value_labels" :key="code">
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-lg text-[9px] font-bold">
                                                        <span class="text-indigo-400 mr-1" x-text="code + ':'"></span>
                                                        <span x-text="label"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!col.value_labels || Object.keys(col.value_labels).length === 0">
                                            <span class="text-[10px] text-gray-300 italic">{{ __('Free text') }}</span>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Data Preview --}}
                <div class="p-8 border-t border-gray-100">
                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">
                        {{ __('Data Preview') }} <span class="text-gray-300 font-medium">({{ __('first 5 rows') }})</span>
                    </h4>
                    <div class="overflow-x-auto rounded-2xl border border-gray-100 custom-scrollbar">
                        <table class="min-w-full text-[9px]">
                            <thead class="bg-gray-50">
                                <tr>
                                    <template x-for="col in mapping" :key="col.var_index">
                                        <th class="px-4 py-2 text-left font-black text-gray-400 uppercase tracking-widest whitespace-nowrap"
                                            x-text="col.name"></th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <template x-for="(row, ri) in previewRows" :key="ri">
                                    <tr class="hover:bg-gray-50/50">
                                        <template x-for="(col, ci) in mapping" :key="ci">
                                            <td class="px-4 py-2 text-gray-600 whitespace-nowrap"
                                                x-text="row[col.var_index] ?? '—'"></td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <template x-if="confirmError">
                    <div class="mx-8 mb-6 p-4 bg-red-50 border border-red-100 rounded-2xl">
                        <p class="text-xs text-red-600 font-bold" x-text="confirmError"></p>
                    </div>
                </template>

                <div class="p-8 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                    <button type="button" @click="currentStep = 1"
                        class="px-6 py-3 bg-white border border-gray-200 text-gray-600 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-gray-100 transition-all">
                        <i class="fa-solid fa-arrow-left mr-2"></i> {{ __('Back') }}
                    </button>
                    <div class="flex items-center gap-3">
                        <span class="text-[10px] text-gray-400 font-medium">
                            <span x-text="mapping.filter(c => c.include).length"></span>
                            {{ __('questions will be imported') }}
                        </span>
                        <button type="button" @click="runConfirm"
                            :disabled="loading || mapping.filter(c => c.include).length === 0"
                            class="px-8 py-3 bg-[#2271b1] text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-zinc-200/50 hover:bg-[#135e96] disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2">
                            <span x-show="loading"><i class="fa-solid fa-spinner fa-spin"></i></span>
                            <span x-show="!loading"><i class="fa-solid fa-check"></i></span>
                            <span x-text="loading ? '{{ __('Creating Survey...') }}' : '{{ __('Confirm Import') }}'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- STEP 3 — Done --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div x-show="currentStep === 3" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden text-center py-20 px-8">
                <div
                    class="w-24 h-24 mx-auto mb-8 rounded-full bg-green-100 flex items-center justify-center shadow-xl shadow-green-100">
                    <i class="fa-solid fa-check-circle text-5xl text-green-500"></i>
                </div>

                <h3 class="text-2xl font-black text-gray-900 mb-3">{{ __('Import Complete!') }}</h3>
                <p class="text-sm text-gray-500 font-medium mb-2">
                    {{ __('Your survey has been created and is ready for analysis.') }}
                </p>
                <p class="text-xs font-black text-[#2271b1] mb-12" x-text="'“' + surveyTitle + '”'"></p>

                <div class="flex flex-wrap items-center justify-center gap-4">
                    <a :href="resultLinks.hub"
                        class="px-6 py-3 bg-[#2271b1] text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-zinc-200/50 hover:bg-[#135e96] transition-all flex items-center gap-2">
                        <i class="fa-solid fa-house"></i> {{ __('Survey Hub') }}
                    </a>
                    <a :href="resultLinks.reports"
                        class="px-6 py-3 bg-white border border-gray-200 text-gray-700 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-chart-bar"></i> {{ __('View Reports') }}
                    </a>
                    <a :href="resultLinks.builder"
                        class="px-6 py-3 bg-white border border-gray-200 text-gray-700 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-emerald-600 hover:text-white hover:border-emerald-600 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-pen-to-square"></i> {{ __('Edit in Builder') }}
                    </a>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            function importWizard() {
                return {
                    currentStep: 1,
                    steps: ['{{ __("Upload") }}', '{!! __("Map & Review") !!}', '{{ __("Done") }}'],

                    // Step 1
                    surveyTitle: '{{ $appendTo ? addslashes($appendTo->title) : '' }}',
                    uploadedFile: null,
                    fileExtension: '',
                    dragOver: false,
                    loading: false,
                    uploadError: null,

                    // Step 2
                    mapping: [],
                    previewRows: [],
                    rowCount: 0,
                    confirmError: null,

                    // Step 3
                    resultLinks: {},

                    // Append-to mode
                    appendToSurveyId: '{{ $appendTo?->id ?? '' }}',

                    handleDrop(e) {
                        this.dragOver = false;
                        const file = e.dataTransfer.files[0];
                        if (file) this.setFile(file);
                    },

                    handleFileSelect(e) {
                        const file = e.target.files[0];
                        if (file) this.setFile(file);
                    },

                    setFile(file) {
                        this.uploadError = null;
                        const ext = file.name.split('.').pop().toLowerCase();
                        const allowed = ['sav', 'xlsx', 'xls', 'csv', 'zip', 'kmsurvey'];
                        if (!allowed.includes(ext)) {
                            this.uploadError = '{{ __("Unsupported file type. Please upload a .sav, .xlsx, .xls, .csv, or .kmsurvey file.") }}';
                            return;
                        }
                        if (file.size > 52428800) { // 50 MB
                            this.uploadError = '{{ __("File is too large. Maximum size is 50 MB.") }}';
                            return;
                        }
                        this.uploadedFile = file;
                        this.fileExtension = ext;

                        // Auto-fill title from filename if empty
                        if (!this.surveyTitle.trim()) {
                            this.surveyTitle = file.name.replace(/\.[^/.]+$/, '').replace(/[_-]/g, ' ');
                        }
                    },

                    formatFileSize(bytes) {
                        if (bytes < 1024) return bytes + ' B';
                        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
                        return (bytes / 1048576).toFixed(1) + ' MB';
                    },

                    async runPreview() {
                        if (!this.uploadedFile || !this.surveyTitle.trim()) return;

                        this.loading = true;
                        this.uploadError = null;

                        const fd = new FormData();
                        fd.append('file', this.uploadedFile);
                        fd.append('_token', '{{ csrf_token() }}');

                        try {
                            const res = await fetch('{{ route('surveys.import.preview') }}', {
                                method: 'POST',
                                headers: { 'Accept': 'application/json' },
                                body: fd,
                            });

                            if (!res.ok) {
                                let errMsg = '{{ __("Failed to parse file.") }}';
                                try {
                                    const errData = await res.json();
                                    errMsg = errData.error || errData.message || errMsg;
                                } catch (e) {
                                    errMsg = `Server error (${res.status})`;
                                }
                                this.uploadError = errMsg;
                                return;
                            }

                            const data = await res.json();

                            // Handle .kmsurvey bundles (direct import)
                            if (data.is_package) {
                                await this.runPackageImport();
                                return;
                            }

                            // Build mapping from variables
                            this.mapping = (data.variables || []).map(v => ({
                                var_index: v.var_index,
                                name: v.name,
                                label: v.label,
                                type: v.inferred_type || 'radio',
                                value_labels: v.value_labels || {},
                                options: v.inferred_options || [],
                                include: true,
                            }));
                            this.previewRows = data.preview_rows || [];
                            this.rowCount = data.row_count || 0;

                            this.currentStep = 2;
                        } catch (err) {
                            this.uploadError = err.message || '{{ __("Network error — could not reach the server.") }}';
                        } finally {
                            this.loading = false;
                        }
                    },

                    async runConfirm() {
                        const included = this.mapping.filter(c => c.include);
                        if (included.length === 0) return;

                        this.loading = true;
                        this.confirmError = null;

                        try {
                            const res = await fetch('{{ route('surveys.import.confirm') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify({
                                    title: this.surveyTitle,
                                    mapping: this.mapping,
                                    append_to_survey: this.appendToSurveyId || null,
                                }),
                            });

                            const data = await res.json();

                            if (!res.ok || data.error) {
                                let errMsg = data.error || data.message || '{{ __("Import failed.") }}';
                                if (data.errors) {
                                    errMsg = Object.values(data.errors).flat().join(' ');
                                }
                                this.confirmError = errMsg;
                                return;
                            }

                            this.resultLinks = data.links || {};
                            this.currentStep = 3;
                        } catch (err) {
                            this.confirmError = '{{ __("Network error during import.") }}';
                        } finally {
                            this.loading = false;
                        }
                    },

                    async runPackageImport() {
                        const fd = new FormData();
                        fd.append('file', this.uploadedFile);
                        fd.append('_token', '{{ csrf_token() }}');

                        try {
                            const res = await fetch('{{ route('surveys.import.package') }}', {
                                method: 'POST',
                                headers: { 'Accept': 'application/json' },
                                body: fd,
                            });

                            if (!res.ok) {
                                let errMsg = '{{ __("Package import failed.") }}';
                                try { const e = await res.json(); errMsg = e.error || e.message || errMsg; } catch (_) { errMsg = `Server error (${res.status})`; }
                                this.uploadError = errMsg;
                                return;
                            }

                            const data = await res.json();
                            if (data.error) { this.uploadError = data.error; return; }

                            this.resultLinks = data.links || {};
                            this.currentStep = 3;
                        } catch (err) {
                            this.uploadError = err.message || '{{ __("Package import failed.") }}';
                        } finally {
                            this.loading = false;
                        }
                    },
                };
            }
        </script>
    @endpush
@endsection