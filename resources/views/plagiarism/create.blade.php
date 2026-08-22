@extends('layouts.app')

@section('title', __('New Originality Scan — KDAnalytiks'))
@section('meta_robots', 'noindex, nofollow')

@section('content')
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6" x-data="plagiarismUploader()">

        <!-- Header Navigation -->
        <div class="flex items-center justify-between border-b border-gray-200 pb-4">
            <div>
                <a href="{{ route('plagiarism.index') }}"
                    class="text-xs font-semibold text-[#2271b1] hover:underline inline-flex items-center gap-1.5 mb-1">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i>
                    <span>{{ __('Back to Scan History') }}</span>
                </a>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ __('New Originality Scan') }}</h1>
            </div>
            <div class="text-right">
                <span class="text-xs text-gray-500 block">{{ __('Tier Limit') }}</span>
                <span class="text-xs font-bold text-gray-800">{{ number_format($wordLimit) }}
                    {{ __('Words / Scan') }}</span>
            </div>
        </div>

        @if($errors->any())
            <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs space-y-1">
                <div class="font-bold flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-red-600"></i>
                    <span>{{ __('Please Correct the Errors Below') }}</span>
                </div>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('plagiarism.store') }}" enctype="multipart/form-data" class="space-y-6"
            @submit="isSubmitting = true">
            @csrf
            <input type="hidden" name="input_type" :value="activeTab">

            <!-- Document Title -->
            <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-4">
                <div>
                    <label for="title" class="block text-xs font-bold text-gray-700 mb-1.5">{{ __('Document Title') }} <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" required value="{{ old('title') }}"
                        placeholder="{{ __('e.g. Chapter 2: Literature Review on Solid Waste Governance') }}"
                        class="w-full px-3.5 py-2.5 bg-gray-50/50 border border-gray-200 rounded-lg text-sm text-gray-900 focus:bg-white focus:ring-1 focus:ring-[#2271b1] focus:border-[#2271b1]">
                </div>

                <!-- Input Tabs -->
                <div class="pt-2">
                    <div class="flex border-b border-gray-200">
                        <button type="button" @click="activeTab = 'file'"
                            :class="activeTab === 'file' ? 'border-[#2271b1] text-[#2271b1] font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="px-4 py-2.5 text-xs border-b-2 font-medium transition-colors flex items-center gap-2">
                            <i class="fa-solid fa-file-arrow-up"></i>
                            <span>{{ __('Upload Document (DOCX, PDF, TXT)') }}</span>
                        </button>
                        <button type="button" @click="activeTab = 'text'"
                            :class="activeTab === 'text' ? 'border-[#2271b1] text-[#2271b1] font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="px-4 py-2.5 text-xs border-b-2 font-medium transition-colors flex items-center gap-2">
                            <i class="fa-solid fa-align-left"></i>
                            <span>{{ __('Paste Plain Text') }}</span>
                        </button>
                    </div>

                    <!-- File Upload Tab Content -->
                    <div x-show="activeTab === 'file'" class="pt-5 space-y-3">
                        <div class="border-2 border-dashed border-gray-200 hover:border-[#2271b1] rounded-xl p-8 text-center transition-colors bg-gray-50/30 cursor-pointer"
                            @click="$refs.fileInput.click()" @dragover.prevent="dragover = true"
                            @dragleave.prevent="dragover = false" @drop.prevent="handleDrop($event)">
                            <input type="file" x-ref="fileInput" name="document" accept=".docx,.pdf,.txt" class="hidden"
                                @change="handleFileSelect($event)">

                            <div
                                class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 mx-auto flex items-center justify-center text-lg mb-3">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                            </div>

                            <template x-if="!selectedFileName">
                                <div class="space-y-1">
                                    <p class="text-xs font-bold text-gray-800">
                                        {{ __('Click to browse or drag and drop your manuscript') }}</p>
                                    <p class="text-[11px] text-gray-400">
                                        {{ __('Supports DOCX, PDF, and TXT files up to 20MB') }}</p>
                                </div>
                            </template>

                            <template x-if="selectedFileName">
                                <div class="space-y-1">
                                    <span
                                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-50 border border-blue-200 text-blue-800 text-xs font-semibold">
                                        <i class="fa-solid fa-file-lines text-blue-600"></i>
                                        <span x-text="selectedFileName"></span>
                                    </span>
                                    <p class="text-[11px] text-gray-400 pt-1">{{ __('Click to choose a different file') }}
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Text Paste Tab Content -->
                    <div x-show="activeTab === 'text'" class="pt-5 space-y-2">
                        <textarea name="content" x-model="pastedText" rows="12"
                            placeholder="{{ __('Paste your academic manuscript text or thesis chapter here...') }}"
                            class="w-full px-4 py-3 bg-gray-50/50 border border-gray-200 rounded-lg text-xs font-mono text-gray-900 leading-relaxed focus:bg-white focus:ring-1 focus:ring-[#2271b1] focus:border-[#2271b1]"></textarea>

                        <div class="flex items-center justify-between text-[11px] text-gray-500 pt-1">
                            <div>
                                <span>{{ __('Word Count') }}: <strong class="text-gray-900" x-text="wordCount">0</strong> /
                                    {{ number_format($wordLimit) }}</span>
                            </div>
                            <div x-show="wordCount > wordLimit" class="text-red-600 font-bold">
                                {{ __('Word limit exceeded for your tier') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Academic Exclusion Filters -->
            <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">{{ __('Academic Exclusion Filters') }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ __('Standard academic guidelines allow excluding bibliographic references and properly cited direct quotes.') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
                    <label
                        class="flex items-start gap-3 p-3.5 rounded-lg border border-gray-200 hover:bg-gray-50/50 cursor-pointer transition-colors">
                        <input type="checkbox" name="exclude_references" value="1" checked
                            class="mt-0.5 rounded border-gray-300 text-[#2271b1] focus:ring-[#2271b1]">
                        <div class="space-y-0.5">
                            <span
                                class="text-xs font-bold text-gray-800 block">{{ __('Exclude References') }}</span>
                            <span class="text-[11px] text-gray-500 leading-normal block">
                                {{ __('Removes Bibliography section.') }}
                            </span>
                        </div>
                    </label>

                    <label
                        class="flex items-start gap-3 p-3.5 rounded-lg border border-gray-200 hover:bg-gray-50/50 cursor-pointer transition-colors">
                        <input type="checkbox" name="exclude_quotes" value="1" checked
                            class="mt-0.5 rounded border-gray-300 text-[#2271b1] focus:ring-[#2271b1]">
                        <div class="space-y-0.5">
                            <span class="text-xs font-bold text-gray-800 block">{{ __('Exclude Quotes') }}</span>
                            <span class="text-[11px] text-gray-500 leading-normal block">
                                {{ __('Ignores quoted text passages.') }}
                            </span>
                        </div>
                    </label>

                    <label
                        class="flex items-start gap-3 p-3.5 rounded-lg border border-gray-200 hover:bg-gray-50/50 cursor-pointer transition-colors">
                        <input type="checkbox" name="exclude_citations" value="1" checked
                            class="mt-0.5 rounded border-gray-300 text-[#2271b1] focus:ring-[#2271b1]">
                        <div class="space-y-0.5">
                            <span class="text-xs font-bold text-gray-800 block">{{ __('Exclude Citations') }}</span>
                            <span class="text-[11px] text-gray-500 leading-normal block">
                                {{ __('Ignores parenthetical citations.') }}
                            </span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Submit Button Row -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('plagiarism.index') }}"
                    class="px-4 py-2.5 text-xs font-semibold text-gray-600 hover:text-gray-900">
                    {{ __('Cancel') }}
                </a>
                <button type="submit" :disabled="isSubmitting || (activeTab === 'text' && wordCount > wordLimit)"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#2271b1] hover:bg-[#135e96] text-white text-xs font-bold rounded-lg shadow-sm transition-all disabled:opacity-50">
                    <i class="fa-solid fa-spinner fa-spin" x-show="isSubmitting" x-cloak></i>
                    <i class="fa-solid fa-magnifying-glass-chart" x-show="!isSubmitting"></i>
                    <span
                        x-text="isSubmitting ? '{{ __('Scanning Document...') }}' : '{{ __('Begin Originality Scan') }}'"></span>
                </button>
            </div>
        </form>

    </div>

    <script>
        function plagiarismUploader() {
            return {
                activeTab: '{{ old('input_type', 'file') }}',
                selectedFileName: '',
                pastedText: @json(old('content', '')),
                isSubmitting: false,
                dragover: false,
                get wordCount() {
                    if (!this.pastedText || !this.pastedText.trim()) return 0;
                    return this.pastedText.trim().split(/\s+/).length;
                },
                handleFileSelect(e) {
                    if (e.target.files.length > 0) {
                        this.selectedFileName = e.target.files[0].name;
                        const titleInput = document.getElementById('title');
                        if (titleInput && !titleInput.value.trim()) {
                            titleInput.value = this.selectedFileName.replace(/\.[^/.]+$/, '');
                        }
                    }
                },
                handleDrop(e) {
                    this.dragover = false;
                    if (e.dataTransfer.files.length > 0) {
                        this.$refs.fileInput.files = e.dataTransfer.files;
                        this.handleFileSelect({ target: this.$refs.fileInput });
                    }
                }
            }
        }
    </script>
@endsection