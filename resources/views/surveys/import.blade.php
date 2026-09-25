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
                {{ __('Upload an Excel or CSV file to create a fully analysable survey project.') }}
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
                        {{ __('Step 1 — Upload Your Data File') }}
                    </h3>
                    <p class="text-xs text-gray-400 font-medium mt-1">
                        {{ __('Supported formats: Excel (.xlsx, .xls), CSV (.csv)') }}
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

                    {{-- Codebook File (Advanced — hidden by default) --}}
                    <div class="mb-6" x-data="{ showAdvanced: false }">
                        <button type="button" @click="showAdvanced = !showAdvanced"
                            class="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest hover:text-gray-600 transition-colors">
                            <i class="fa-solid fa-sliders text-[10px]"></i>
                            {{ __('Advanced Options') }}
                            <i class="fa-solid text-[8px] transition-transform duration-200"
                                :class="showAdvanced ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        </button>

                        <div x-show="showAdvanced" x-collapse class="mt-3 space-y-2">
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">
                                {{ __('Codebook / Label Mapping (optional)') }}
                            </label>
                            <p class="text-[10px] text-gray-400 mb-2">
                                {{ __('Upload a 2-column Excel/CSV: Column A = VAR code · Column B = Human label. Only needed if your headers are coded (e.g. Q1, Q2) with no readable labels.') }}
                            </p>
                            <div x-data="{ cbDrag: false }" @dragover.prevent="cbDrag = true"
                                @dragleave.prevent="cbDrag = false"
                                @drop.prevent="cbDrag = false; codebookFile = $event.dataTransfer.files[0]"
                                @click="$refs.codebookInput.click()"
                                :class="codebookFile ? 'border-green-400 bg-green-50/30' : 'border-gray-200 hover:border-[#2271b1] hover:bg-gray-50/50'"
                                class="border-2 border-dashed rounded-2xl p-6 text-center cursor-pointer transition-all duration-200">
                                <input type="file" x-ref="codebookInput" class="hidden" accept=".xlsx,.xls,.csv"
                                    @change="codebookFile = $event.target.files[0]">
                                <template x-if="!codebookFile">
                                    <div class="flex items-center justify-center gap-2">
                                        <i class="fa-solid fa-file-lines text-gray-300 text-sm"></i>
                                        <span
                                            class="text-xs text-gray-400 font-medium">{{ __('Click or drop codebook file') }}</span>
                                    </div>
                                </template>
                                <template x-if="codebookFile">
                                    <div class="flex items-center justify-center gap-3">
                                        <i class="fa-solid fa-file-lines text-green-600 text-sm"></i>
                                        <span class="text-xs font-bold text-green-700" x-text="codebookFile.name"></span>
                                        <button type="button" @click.stop="codebookFile = null"
                                            class="text-[10px] font-black text-red-400 hover:text-red-600 uppercase tracking-widest">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Drag & Drop Zone --}}
                    <div id="drop-zone" @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false"
                        @drop.prevent="handleDrop($event)" @click="$refs.fileInput.click()"
                        :class="dragOver
                                        ? 'border-[#2271b1] bg-blue-50/50 scale-[1.01]'
                                        : (uploadedFile ? 'border-green-400 bg-green-50/30' : 'border-gray-200 hover:border-[#2271b1] hover:bg-gray-50/50')"
                        class="border-2 border-dashed rounded-3xl p-16 text-center cursor-pointer transition-all duration-200">

                        <input type="file" x-ref="fileInput" class="hidden"
                            accept=".sav,.xlsx,.xls,.csv,.zip,.kdsurvey,.kmsurvey" @change="handleFileSelect($event)">

                        <template x-if="!uploadedFile">
                            <div>
                                <div
                                    class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gray-100 flex items-center justify-center">
                                    <i class="fa-solid fa-file-arrow-up text-3xl text-gray-300"></i>
                                </div>
                                <p class="text-sm font-bold text-gray-700 mb-2">
                                    {{ __('Drop your file here or click to browse') }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ __(' Excel .xlsx · CSV ') }}
                                </p>
                                <p class="text-[10px] text-gray-300 mt-2 font-medium">{{ __('Maximum file size: 50 MB') }}
                                </p>
                            </div>
                        </template>

                        <template x-if="uploadedFile">
                            <div>
                                <div class="w-20 h-20 mx-auto mb-6 rounded-2xl flex items-center justify-center shadow-lg"
                                    :class="{
                                                     'bg-emerald-100': ['xlsx','xls'].includes(fileExtension),
                                                     'bg-amber-100': fileExtension === 'csv',
                                                 }">
                                    <i class="text-3xl" :class="{
                                                       'fa-solid fa-file-excel text-emerald-600': ['xlsx','xls'].includes(fileExtension),
                                                       'fa-solid fa-file-csv text-amber-600': fileExtension === 'csv',
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
                <div class="p-6 md:p-8 border-b border-gray-50 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                            {{ __('Step 2 — Review & Map Columns') }}
                        </h3>
                        <p class="text-xs text-gray-400 font-medium mt-1">
                            {{ __('Types were autodetected from your data. You can refine types, search, or bulk-change below.') }}
                        </p>
                    </div>
                    <div class="text-right flex items-center gap-3">
                        <div class="px-3 py-1.5 bg-blue-50 text-[#2271b1] rounded-xl text-xs font-black">
                            <span x-text="rowCount.toLocaleString()"></span>
                            <span class="font-medium text-gray-500"> {{ __('rows') }}</span>
                        </div>
                        <div x-show="codebookApplied">
                            <span
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-green-100 text-green-700 rounded-xl text-[10px] font-black uppercase">
                                <i class="fa-solid fa-check-circle text-[9px]"></i> {{ __('Codebook applied') }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Toolbar: Search, Filters, and Bulk Actions --}}
                <div
                    class="px-6 md:px-8 py-4 bg-gray-50/70 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
                    {{-- Search & Type Filter --}}
                    <div class="flex flex-wrap items-center gap-2.5 flex-1 min-w-[280px]">
                        {{-- Real-time Search Box --}}
                        <div class="relative flex-1 min-w-[180px] max-w-sm">
                            <i
                                class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input type="text" x-model="searchQuery" placeholder="{{ __('Search questions...') }}"
                                class="w-full pl-8 pr-8 py-2 bg-white border border-gray-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-[#2271b1]/20 focus:border-[#2271b1] transition-all">
                            <button type="button" x-show="searchQuery" @click="searchQuery = ''"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500 text-xs">
                                <i class="fa-solid fa-circle-xmark"></i>
                            </button>
                        </div>

                        {{-- Type Filter Dropdown --}}
                        <select x-model="typeFilter"
                            class="px-3 py-2 bg-white border border-gray-200 rounded-xl text-[11px] font-bold text-gray-700 focus:ring-2 focus:ring-[#2271b1]/20 focus:border-[#2271b1] transition-all">
                            <option value="all">{{ __('All Types') }}</option>
                            <option value="radio">{{ __('Radio (Single Choice)') }}</option>
                            <option value="select">{{ __('Dropdown') }}</option>
                            <option value="rating">{{ __('Likert Scale') }}</option>
                            <option value="checkbox">{{ __('Checkbox (Multi-select)') }}</option>
                            <option value="number">{{ __('Number (Integer)') }}</option>
                            <option value="decimal">{{ __('Decimal / Coords') }}</option>
                            <option value="date">{{ __('Date') }}</option>
                            <option value="text">{{ __('Short Text') }}</option>
                            <option value="textarea">{{ __('Long Text') }}</option>
                        </select>

                        {{-- Included / Excluded Filter --}}
                        <select x-model="includeFilter"
                            class="px-3 py-2 bg-white border border-gray-200 rounded-xl text-[11px] font-bold text-gray-700 focus:ring-2 focus:ring-[#2271b1]/20 focus:border-[#2271b1] transition-all">
                            <option value="all">{{ __('All Status') }}</option>
                            <option value="included">{{ __('Included only') }}</option>
                            <option value="excluded">{{ __('Excluded only') }}</option>
                        </select>
                    </div>

                    {{-- Bulk Actions --}}
                    <div class="flex items-center gap-2">
                        <button type="button" @click="setAllVisibleIncluded(true)"
                            class="px-3 py-1.5 bg-white border border-gray-200 hover:border-emerald-500 hover:text-emerald-700 rounded-xl text-[10px] font-black uppercase tracking-wider text-gray-600 transition-all flex items-center gap-1.5"
                            title="{{ __('Include all currently visible questions') }}">
                            <i class="fa-solid fa-check-double text-emerald-500"></i> {{ __('Select All') }}
                        </button>
                        <button type="button" @click="setAllVisibleIncluded(false)"
                            class="px-3 py-1.5 bg-white border border-gray-200 hover:border-red-500 hover:text-red-700 rounded-xl text-[10px] font-black uppercase tracking-wider text-gray-600 transition-all flex items-center gap-1.5"
                            title="{{ __('Exclude all currently visible questions') }}">
                            <i class="fa-solid fa-xmark text-red-400"></i> {{ __('Deselect All') }}
                        </button>

                        {{-- Bulk Type Assigner --}}
                        <div class="flex items-center gap-1 bg-white border border-gray-200 rounded-xl p-0.5">
                            <select x-model="bulkType"
                                class="px-2 py-1 bg-transparent border-0 text-[10px] font-black text-gray-700 focus:ring-0">
                                <option value="radio">{{ __('Radio') }}</option>
                                <option value="select">{{ __('Dropdown') }}</option>
                                <option value="rating">{{ __('Likert Scale') }}</option>
                                <option value="checkbox">{{ __('Checkbox') }}</option>
                                <option value="number">{{ __('Number') }}</option>
                                <option value="decimal">{{ __('Decimal') }}</option>
                                <option value="date">{{ __('Date') }}</option>
                                <option value="text">{{ __('Text') }}</option>
                                <option value="textarea">{{ __('Long Text') }}</option>
                            </select>
                            <button type="button" @click="applyBulkType"
                                class="px-2.5 py-1 bg-[#2271b1] text-white rounded-lg text-[9px] font-black uppercase tracking-wider hover:bg-[#135e96] transition-all">
                                {{ __('Apply to Visible') }}
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Status Banner --}}
                <div
                    class="px-6 md:px-8 py-2 bg-blue-50/40 border-b border-gray-100 flex items-center justify-between text-[11px] font-bold text-gray-500">
                    <div>
                        {{ __('Showing') }} <span class="text-gray-900 font-black" x-text="filteredMapping.length"></span>
                        {{ __('of') }} <span class="text-gray-900 font-black" x-text="mapping.length"></span>
                        {{ __('questions') }}
                        (<span class="text-emerald-600 font-black" x-text="mapping.filter(c => c.include).length"></span>
                        {{ __('included') }})
                    </div>
                    <div x-show="searchQuery || typeFilter !== 'all' || includeFilter !== 'all'">
                        <button type="button" @click="resetFilters"
                            class="text-[10px] text-[#2271b1] hover:underline font-black">
                            <i class="fa-solid fa-rotate-left mr-1"></i>{{ __('Reset filters') }}
                        </button>
                    </div>
                </div>

                {{-- Scrollable Table Container with Fixed Height & Sticky Headers --}}
                <div class="max-h-[500px] overflow-y-auto overflow-x-auto border-b border-gray-200 custom-scrollbar relative"
                    style="position: relative;">
                    <table class="min-w-full border-separate border-spacing-0">
                        <thead style="position: sticky; top: 0; z-index: 30;">
                            <tr>
                                <th style="position: sticky; top: 0; z-index: 30; background-color: #f1f5f9; border-bottom: 2px solid #cbd5e1;"
                                    class="px-5 py-3.5 text-left text-[9px] font-black text-gray-700 uppercase tracking-widest w-12">
                                    {{ __('Include') }}
                                </th>
                                <th style="position: sticky; top: 0; z-index: 30; background-color: #f1f5f9; border-bottom: 2px solid #cbd5e1;"
                                    class="px-5 py-3.5 text-left text-[9px] font-black text-gray-700 uppercase tracking-widest w-36">
                                    {{ __('Variable') }}
                                </th>
                                <th style="position: sticky; top: 0; z-index: 30; background-color: #f1f5f9; border-bottom: 2px solid #cbd5e1;"
                                    class="px-5 py-3.5 text-left text-[9px] font-black text-gray-700 uppercase tracking-widest min-w-[240px]">
                                    {{ __('Question Label') }}
                                </th>
                                <th style="position: sticky; top: 0; z-index: 30; background-color: #f1f5f9; border-bottom: 2px solid #cbd5e1;"
                                    class="px-5 py-3.5 text-left text-[9px] font-black text-gray-700 uppercase tracking-widest w-44">
                                    {{ __('Data Type') }}
                                </th>
                                <th style="position: sticky; top: 0; z-index: 30; background-color: #f1f5f9; border-bottom: 2px solid #cbd5e1;"
                                    class="px-5 py-3.5 text-left text-[9px] font-black text-gray-700 uppercase tracking-widest min-w-[200px]">
                                    {{ __('Values / Options Preview') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <template x-for="(col, i) in filteredMapping" :key="col.var_index">
                                <tr :class="col.include ? 'hover:bg-blue-50/30' : 'bg-gray-50/70 opacity-60 hover:opacity-100 transition-opacity'"
                                    class="transition-colors">
                                    {{-- Include checkbox --}}
                                    <td class="px-5 py-3.5 text-center border-b border-gray-100">
                                        <input type="checkbox" x-model="col.include"
                                            class="h-4 w-4 text-[#2271b1] border-gray-300 rounded focus:ring-[#2271b1] cursor-pointer">
                                    </td>

                                    {{-- Variable name --}}
                                    <td class="px-5 py-3.5 border-b border-gray-100">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="text-[11px] font-black text-gray-600 font-mono tracking-tight"
                                                x-text="col.name"></span>
                                            <span x-show="col.looks_like_spss_code"
                                                class="inline-flex items-center px-1.5 py-0.5 bg-amber-100 text-amber-700 rounded text-[8px] font-black uppercase"
                                                title="{{ __('SPSS variable code') }}">
                                                VAR
                                            </span>
                                            <span x-show="!col.include"
                                                class="inline-flex items-center px-1.5 py-0.5 bg-gray-200 text-gray-600 rounded text-[8px] font-black uppercase">
                                                {{ __('Excluded') }}
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Editable Question Label --}}
                                    <td class="px-5 py-3.5 border-b border-gray-100">
                                        <input type="text" x-model="col.label"
                                            class="w-full px-3 py-1.5 border border-gray-200 rounded-xl text-xs font-semibold text-gray-800 focus:ring-2 focus:ring-[#2271b1]/20 focus:border-[#2271b1] transition-all"
                                            :disabled="!col.include">
                                    </td>

                                    {{-- Type dropdown --}}
                                    <td class="px-5 py-3.5 border-b border-gray-100">
                                        <select x-model="col.type"
                                            class="w-full px-3 py-1.5 border border-gray-200 rounded-xl text-[11px] font-bold text-gray-700 focus:ring-2 focus:ring-[#2271b1]/20 focus:border-[#2271b1] transition-all bg-white"
                                            :disabled="!col.include">
                                            <option value="radio">{{ __('Radio (Single Choice)') }}</option>
                                            <option value="select">{{ __('Dropdown (Select)') }}</option>
                                            <option value="rating">{{ __('Likert Scale') }}</option>
                                            <option value="checkbox">{{ __('Checkbox (Multi-select)') }}</option>
                                            <option value="number">{{ __('Number (Integer)') }}</option>
                                            <option value="decimal">{{ __('Decimal / Coords') }}</option>
                                            <option value="date">{{ __('Date') }}</option>
                                            <option value="text">{{ __('Short Text') }}</option>
                                            <option value="textarea">{{ __('Long Text') }}</option>
                                        </select>
                                    </td>

                                    {{-- Options / Values preview --}}
                                    <td class="px-5 py-3.5 border-b border-gray-100">
                                        <template x-if="col.value_labels && Object.keys(col.value_labels).length > 0">
                                            <div class="flex flex-wrap gap-1 max-h-16 overflow-y-auto custom-scrollbar">
                                                <template x-for="(label, code) in col.value_labels" :key="code">
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-lg text-[9px] font-bold max-w-[200px] truncate"
                                                        :title="label">
                                                        <span class="text-indigo-400 mr-1" x-text="code + ':'"></span>
                                                        <span x-text="label"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!col.value_labels || Object.keys(col.value_labels).length === 0">
                                            <span
                                                class="text-[10px] text-gray-300 italic">{{ __('Continuous / Free value') }}</span>
                                        </template>
                                    </td>
                                </tr>
                            </template>

                            <template x-if="filteredMapping.length === 0">
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-xs font-medium">
                                        <i class="fa-solid fa-filter-circle-xmark text-2xl text-gray-300 mb-2 block"></i>
                                        {{ __('No questions match your search or filter.') }}
                                        <button type="button" @click="resetFilters"
                                            class="text-[#2271b1] font-bold block mx-auto mt-1 hover:underline">
                                            {{ __('Clear filters') }}
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Collapsible Data Preview Section --}}
                <div class="p-6 md:p-8 border-b border-gray-100 bg-gray-50/50">
                    <div class="flex items-center justify-between mb-3">
                        <button type="button" @click="showDataPreview = !showDataPreview"
                            class="flex items-center gap-2 text-[10px] font-black text-gray-500 uppercase tracking-widest hover:text-gray-700 transition-colors">
                            <i class="fa-solid fa-table text-gray-400 text-xs"></i>
                            {{ __('Data Preview') }}
                            <span class="text-gray-400 font-normal">({{ __('first 5 rows') }})</span>
                            <i class="fa-solid text-[9px] transition-transform duration-200"
                                :class="showDataPreview ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        </button>
                        <span class="text-[10px] text-gray-400 font-medium" x-show="!showDataPreview">
                            {{ __('Click to preview uploaded rows') }}
                        </span>
                    </div>

                    <div x-show="showDataPreview" x-collapse>
                        <div
                            class="overflow-x-auto rounded-2xl border border-gray-200 bg-white max-h-56 overflow-y-auto custom-scrollbar">
                            <table class="min-w-full text-[10px]">
                                <thead class="bg-gray-50 sticky top-0 border-b border-gray-200">
                                    <tr>
                                        <template x-for="col in mapping" :key="col.var_index">
                                            <th class="px-4 py-2 text-left font-black text-gray-500 uppercase tracking-wider whitespace-nowrap"
                                                x-text="col.name"></th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="(row, ri) in previewRows" :key="ri">
                                        <tr class="hover:bg-gray-50/70">
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
                </div>

                <template x-if="confirmError">
                    <div class="mx-6 md:mx-8 my-4 p-4 bg-red-50 border border-red-100 rounded-2xl">
                        <p class="text-xs text-red-600 font-bold" x-text="confirmError"></p>
                    </div>
                </template>

                {{-- Step 2 Bottom Navigation Footer --}}
                <div class="p-6 md:p-8 bg-gray-50 flex flex-wrap items-center justify-between gap-4">
                    <button type="button" @click="currentStep = 1"
                        class="px-6 py-3 bg-white border border-gray-200 text-gray-600 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-gray-100 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-arrow-left"></i> {{ __('Back') }}
                    </button>
                    <div class="flex items-center gap-3">
                        <span class="text-[11px] text-gray-500 font-bold">
                            <span class="text-emerald-600 font-black" x-text="mapping.filter(c => c.include).length"></span>
                            {{ __('of') }} <span x-text="mapping.length"></span> {{ __('questions will be imported') }}
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
                    codebookFile: null,
                    dragOver: false,
                    loading: false,
                    uploadError: null,

                    // Step 2
                    mapping: [],
                    previewRows: [],
                    rowCount: 0,
                    confirmError: null,
                    searchQuery: '',
                    typeFilter: 'all',
                    includeFilter: 'all',
                    bulkType: 'radio',
                    showDataPreview: false,

                    // Step 3
                    resultLinks: {},

                    // Append-to mode
                    appendToSurveyId: '{{ $appendTo?->id ?? '' }}',

                    get filteredMapping() {
                        return this.mapping.filter(col => {
                            const query = this.searchQuery.trim().toLowerCase();
                            const matchesSearch = !query ||
                                (col.name && col.name.toLowerCase().includes(query)) ||
                                (col.label && col.label.toLowerCase().includes(query));

                            const matchesType = this.typeFilter === 'all' || col.type === this.typeFilter;

                            const matchesInclude = this.includeFilter === 'all' ||
                                (this.includeFilter === 'included' && col.include) ||
                                (this.includeFilter === 'excluded' && !col.include);

                            return matchesSearch && matchesType && matchesInclude;
                        });
                    },

                    resetFilters() {
                        this.searchQuery = '';
                        this.typeFilter = 'all';
                        this.includeFilter = 'all';
                    },

                    setAllVisibleIncluded(status) {
                        this.filteredMapping.forEach(c => c.include = status);
                    },

                    applyBulkType() {
                        if (!this.bulkType) return;
                        this.filteredMapping.forEach(c => c.type = this.bulkType);
                    },

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
                        const allowed = ['xlsx', 'xls', 'csv'];
                        if (!allowed.includes(ext)) {
                            this.uploadError = '{{ __("Unsupported file type. Please upload a .xlsx, .xls or .csv file.") }}';
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
                        if (this.codebookFile) {
                            fd.append('codebook', this.codebookFile);
                        }

                        try {
                            const res = await fetch('{{ route('surveys.import.preview') }}', {
                                method: 'POST',
                                headers: { 'Accept': 'application/json' },
                                body: fd,
                            });

                            if (!res.ok) {
                                if (res.status === 419) {
                                    this.uploadError = '{{ __("Your session has timed out. Please refresh the page (F5) and try uploading again.") }}';
                                    return;
                                }
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

                            // Build mapping from variables
                            this.mapping = (data.variables || []).map(v => ({
                                var_index: v.var_index,
                                name: v.name,
                                label: v.label,
                                type: v.inferred_type || 'radio',
                                value_labels: v.value_labels || {},
                                options: v.inferred_options || [],
                                looks_like_spss_code: v.looks_like_spss_code || false,
                                include: v.include !== undefined ? v.include : true,
                            }));
                            this.previewRows = data.preview_rows || [];
                            this.rowCount = data.row_count || 0;
                            this.codebookApplied = data.codebook_applied || false;

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
                                if (res.status === 419) {
                                    this.confirmError = '{{ __("Your session has timed out. Please refresh the page (F5) and try again.") }}';
                                    return;
                                }
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

                };
            }
        </script>
    @endpush
@endsection