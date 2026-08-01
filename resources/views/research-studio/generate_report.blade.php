@extends('layouts.app')

@section('title', __('Generate Report — Research Studio'))

@section('content')
    <div class="container-fluid px-4 md:px-8 py-8" x-data="reportWizard()">
        <div class="max-w-4xl mx-auto">
            <header class="mb-8">
                <span class="text-xs font-black text-[#2271b1] tracking-widest">{{ __('Research Studio') }}</span>
                <h1 class="text-3xl font-black text-gray-900 tracking-tight mt-1">
                    {{ __('Generate Report') }}
                </h1>
                <p class="text-gray-500 font-medium mt-1">
                    {{ __('Compile Chapters 1–5 using existing data.') }}
                </p>
            </header>

            <!-- Progress Indicator -->
            <div class="flex items-center justify-between mb-8 bg-white border border-gray-100 rounded-3xl p-6 shadow-xs">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs"
                        :class="step === 1 ? 'bg-[#2271b1] text-white' : 'bg-gray-100 text-gray-500'">1</div>
                    <span class="text-xs font-bold text-gray-800">{{ __('Upload Chapters') }}</span>
                </div>
                <div class="flex-1 h-0.5 bg-gray-100 mx-4"></div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs"
                        :class="step === 2 ? 'bg-[#2271b1] text-white' : 'bg-gray-100 text-gray-500'">2</div>
                    <span class="text-xs font-bold text-gray-800">{{ __('Review Proofreading') }}</span>
                </div>
                <div class="flex-1 h-0.5 bg-gray-100 mx-4"></div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs"
                        :class="step === 3 ? 'bg-[#2271b1] text-white' : 'bg-gray-100 text-gray-500'">3</div>
                    <span class="text-xs font-bold text-gray-800">{{ __('Select Survey Data') }}</span>
                </div>
                <div class="flex-1 h-0.5 bg-gray-100 mx-4"></div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs"
                        :class="step === 4 ? 'bg-[#2271b1] text-white' : 'bg-gray-100 text-gray-500'">4</div>
                    <span class="text-xs font-bold text-gray-800">{{ __(' Generate') }}</span>
                </div>
            </div>

            <!-- Step 1: Title & Upload -->
            <div class="bg-white rounded-3xl border border-gray-100 p-8 space-y-6 shadow-xs" x-show="step === 1">
                <div class="space-y-2">
                    <label for="title"
                        class="block text-xs font-black text-gray-500  tracking-wider">{{ __('Report Title') }}</label>
                    <input type="text" id="title" x-model="title" required
                        class="w-full bg-gray-50 border-gray-100 rounded-2xl px-5 py-4 text-gray-900 font-bold placeholder-gray-300 focus:ring-2 focus:ring-[#2271b1]/20 focus:border-[#2271b1] transition-all">
                </div>

                <div class="space-y-2">
                    <label
                        class="block text-xs font-black text-gray-500  tracking-wider">{{ __('Upload Chapters 1–3') }}</label>
                    <div
                        class="relative border-2 border-dashed border-gray-200 rounded-2xl p-8 hover:border-[#2271b1] transition-all group bg-gray-50/50 cursor-pointer text-center">
                        <input type="file" @change="uploadChapters($event)"
                            class="absolute inset-0 opacity-0 cursor-pointer" accept=".docx,.txt">
                        <div class="space-y-2">
                            <i
                                class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400 group-hover:text-[#2271b1] transition-colors"></i>
                            <p class="text-xs font-bold text-gray-700">{{ __('Drag and drop or click to upload DOCX/TXT') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Proofread Diff Review -->
            <div class="space-y-6" x-show="step === 2" style="display: none;">
                <div
                    class="bg-white rounded-3xl border border-gray-100 p-6 flex flex-col sm:flex-row justify-between items-center gap-4 shadow-xs">
                    <div>
                        <h4 class="text-sm font-black text-gray-900  tracking-tight">
                            {{ __('Verify grammar corrections') }}
                        </h4>
                        <p class="text-xs text-gray-500 font-semibold">{{ __('Review diff below before proceeding.') }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button @click="step = 1"
                            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-xs transition-all flex items-center gap-1.5">
                            <i class="fa-solid fa-arrow-left text-[10px]"></i>
                            <span>{{ __('Back') }}</span>
                        </button>
                        <button @click="step = 3"
                            class="px-6 py-2.5 bg-[#2271b1] hover:bg-[#135e96] text-white font-bold rounded-xl text-xs shadow-md transition-all">
                            {{ __('Accept & Proceed') }}
                        </button>
                    </div>
                </div>

                <div
                    class="bg-white rounded-3xl border border-gray-100 overflow-hidden divide-y divide-gray-100 shadow-xs max-h-[500px] overflow-y-auto custom-scrollbar">
                    <template x-for="(p, index) in paragraphs" :key="index">
                        <div class="p-5 hover:bg-gray-50/30 transition-colors flex gap-4 items-start"
                            :class="{'bg-rose-50/20': p.status === 'rejected', 'bg-amber-50/20': p.isEditing}">
                            <span class="text-xs font-bold text-gray-400 shrink-0 mt-1" x-text="index + 1"></span>
                            <div class="flex-1 space-y-2">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-2">
                                        <h5 class="text-[10px] font-black  tracking-wider"
                                            :class="p.isHeading ? 'text-[#2271b1] font-extrabold' : 'text-gray-400'"
                                            x-text="p.isHeading ? '{{ __('Section Heading') }}' : '{{ __('Paragraph Corrections') }}'">
                                        </h5>

                                        <span x-show="p.status === 'rejected'"
                                            class="px-2 py-0.5 text-[9px] font-bold text-rose-600 bg-rose-100 rounded-md">
                                            {{ __('Rejected (Original Kept)') }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1.5" x-show="!p.isHeading">
                                        <button @click="p.status = 'accepted'; p.isEditing = false;"
                                            :class="p.status === 'accepted' && !p.isEditing ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-emerald-50 hover:text-emerald-700'"
                                            class="px-2 py-0.5 text-[10px] font-bold rounded-lg transition-all flex items-center gap-1">
                                            <i class="fa-solid fa-check text-[9px]"></i>
                                            <span>{{ __('Accept') }}</span>
                                        </button>
                                        <button @click="p.status = 'rejected'; p.isEditing = false;"
                                            :class="p.status === 'rejected' && !p.isEditing ? 'bg-rose-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-rose-50 hover:text-rose-700'"
                                            class="px-2 py-0.5 text-[10px] font-bold rounded-lg transition-all flex items-center gap-1">
                                            <i class="fa-solid fa-xmark text-[9px]"></i>
                                            <span>{{ __('Reject') }}</span>
                                        </button>
                                        <button @click="p.isEditing = !p.isEditing"
                                            :class="p.isEditing ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-amber-50 hover:text-amber-700'"
                                            class="px-2 py-0.5 text-[10px] font-bold rounded-lg transition-all flex items-center gap-1">
                                            <i class="fa-solid fa-pen text-[9px]"></i>
                                            <span>{{ __('Edit') }}</span>
                                        </button>
                                    </div>
                                </div>

                                <div x-show="!p.isEditing">
                                    <div x-show="p.status === 'accepted'"
                                        class="text-sm text-gray-800 leading-relaxed font-medium" x-html="p.diff"></div>
                                    <div x-show="p.status === 'rejected'"
                                        class="text-sm text-gray-700 leading-relaxed font-medium italic"
                                        x-text="p.original"></div>
                                </div>

                                <div x-show="p.isEditing" class="space-y-2">
                                    <textarea x-model="p.corrected" rows="3"
                                        class="w-full text-xs font-semibold p-3 border border-amber-300 rounded-xl focus:ring-1 focus:ring-amber-500 custom-scrollbar"></textarea>
                                    <div class="flex justify-end gap-2">
                                        <button @click="p.isEditing = false; p.status = 'accepted'"
                                            class="px-3 py-1 bg-amber-600 text-white text-[10px] font-bold rounded-lg">
                                            {{ __('Save Edits') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Step 3: Select Survey & Inferential Tests -->
            <div class="bg-white rounded-3xl border border-gray-100 p-8 space-y-6 shadow-xs" x-show="step === 3"
                style="display: none;">
                <div class="space-y-2">
                    <label for="survey_id"
                        class="block text-xs font-black text-gray-500  tracking-wider">{{ __('Select Survey Source') }}</label>
                    <select id="survey_id" x-model="selectedSurvey" @change="fetchInferentialTests()"
                        class="w-full bg-gray-50 border-gray-100 rounded-2xl px-5 py-4 text-gray-900 font-bold focus:ring-2 focus:ring-[#2271b1]/20 focus:border-[#2271b1] transition-all">
                        <option value="">{{ __('Select Survey...') }}</option>
                        @foreach($surveys as $survey)
                            <option value="{{ $survey->id }}">{{ $survey->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-4" x-show="selectedSurvey && inferentialTests.length > 0">
                    <label
                        class="block text-xs font-black text-gray-500  tracking-wider">{{ __('Include Saved Inferential Tests') }}</label>
                    <div class="space-y-3 bg-gray-50 p-4 rounded-2xl border border-gray-100">
                        <template x-for="test in inferentialTests" :key="test.id">
                            <label
                                class="flex items-start gap-3 p-3 bg-white rounded-xl border border-gray-200/50 hover:border-indigo-500 transition-all cursor-pointer">
                                <input type="checkbox" :value="test.id" x-model="selectedTests"
                                    class="mt-1 rounded text-[#2271b1] focus:ring-[#2271b1]">
                                <div>
                                    <h5 class="text-xs font-bold text-gray-900" x-text="test.title"></h5>
                                    <p class="text-[10px] text-gray-500 font-semibold mt-0.5">
                                        <span class="font-bold text-[#2271b1]" x-text="test.method.toUpperCase()"></span> |
                                        <span x-text="test.variables"></span>
                                    </p>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                <div class="flex justify-between items-center pt-4">
                    <button @click="step = 2"
                        class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-xs transition-all flex items-center gap-1.5">
                        <i class="fa-solid fa-arrow-left text-[10px]"></i>
                        <span>{{ __('Back to Proofreading') }}</span>
                    </button>
                    <button @click="compileReport()" :disabled="!selectedSurvey"
                        class="px-6 py-3 bg-[#2271b1] hover:bg-indigo-700 disabled:opacity-50 text-white font-bold rounded-xl text-xs shadow-md transition-all">
                        {{ __('Generate Report Chapters') }}
                    </button>
                </div>
            </div>

            <!-- Step 4: Final Preview / Compilation Loader -->
            <div class="bg-white rounded-3xl border border-gray-100 p-12 text-center shadow-xs" x-show="step === 4"
                style="display: none;">
                <!-- Running State -->
                <div class="max-w-md mx-auto space-y-6 py-6" x-show="!downloadUrl">
                    <div class="relative w-20 h-20 mx-auto">
                        <div class="absolute inset-0 border-4 border-indigo-100 rounded-full"></div>
                        <div class="absolute inset-0 border-4 border-t-[#2271b1] rounded-full animate-spin"></div>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ __('Compiling Chapters 1-5...') }}</h3>
                        <p class="text-gray-400 text-xs mt-1 font-semibold">
                            {{ __('KDAnalytiks is auto-generating Chapter 5 conclusions and rewriting analysis.') }}
                        </p>
                    </div>
                </div>

                <!-- Ready State -->
                <div class="max-w-md mx-auto space-y-6 py-6" x-show="downloadUrl" style="display: none;">
                    <div
                        class="w-20 h-20 bg-emerald-50 text-emerald-600 rounded-3xl flex items-center justify-center mx-auto shadow-sm">
                        <i class="fa-solid fa-file-shield text-3xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">{{ __('Report compiled!') }}</h3>
                        <p class="text-gray-500 text-sm mt-1 font-semibold">
                            {{ __('Chapters 1-5 have been successfully synthesized and compiled .') }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-3 pt-4">
                        <a :href="previewUrl"
                            class="px-6 py-3 bg-white border border-indigo-200 text-[#2271b1] hover:bg-indigo-50 font-bold rounded-2xl shadow-xs transition-all text-xs tracking-wide flex items-center gap-2">
                            <i class="fa-solid fa-eye"></i>
                            {{ __('Preview Full Document') }}
                        </a>
                        <a :href="downloadUrl"
                            class="px-8 py-3 bg-[#2271b1] hover:bg-indigo-700 text-white font-bold rounded-2xl shadow-md transition-all text-xs tracking-wide flex items-center gap-2">
                            <i class="fa-solid fa-file-export"></i>
                            {{ __('Download DOCX') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function reportWizard() {
            return {
                step: 1,
                title: '',
                paragraphs: [],
                selectedSurvey: '',
                inferentialTests: [],
                selectedTests: [],
                previewUrl: null,
                downloadUrl: null,
                uploadChapters(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    if (!this.title.trim()) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Title Required',
                            text: 'Please enter a report title before uploading chapters.'
                        });
                        event.target.value = '';
                        return;
                    }

                    const formData = new FormData();
                    formData.append('file', file);

                    Swal.fire({
                        title: 'Extracting draft text...',
                        text: 'Analyzing Chapters 1-3 document structures.',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    fetch('{{ route("research-studio.proofread.process") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    })
                        .then(res => res.json())
                        .then(data => {
                            Swal.close();
                            if (data.success) {
                                this.paragraphs = data.paragraphs;
                                this.step = 2;
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Extraction failed',
                                    text: data.error || 'Failed to process DOCX file.'
                                });
                            }
                        })
                        .catch(err => {
                            Swal.close();
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred during upload.'
                            });
                        });
                },
                fetchInferentialTests() {
                    if (!this.selectedSurvey) {
                        this.inferentialTests = [];
                        return;
                    }

                    fetch(`/research-studio/report/inferential-tests/${this.selectedSurvey}`)
                        .then(res => res.json())
                        .then(data => {
                            this.inferentialTests = data.tests || [];
                        })
                        .catch(err => {
                            console.error('Failed to load inferential tests:', err);
                        });
                },
                compileReport() {
                    if (!this.title.trim()) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Title Required',
                            text: 'Please input a report title.'
                        });
                        return;
                    }

                    this.step = 4;

                    let savedTypes = {};
                    let savedColors = {};
                    try {
                        savedTypes = JSON.parse(localStorage.getItem('survey_chart_types_' + this.selectedSurvey) || '{}');
                        savedColors = JSON.parse(localStorage.getItem('survey_chart_colors_' + this.selectedSurvey) || '{}');
                    } catch (e) { }

                    fetch('{{ route("research-studio.report.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            title: this.title,
                            paragraphs: this.paragraphs,
                            survey_id: this.selectedSurvey,
                            inferential_tests: this.selectedTests,
                            types: savedTypes,
                            colors: savedColors
                        })
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                this.downloadUrl = data.download_url;
                                this.previewUrl = data.preview_url;
                                if (data.preview_url) {
                                    window.location.href = data.preview_url;
                                }
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Compilation failed',
                                    text: data.error || 'Something went wrong during generation.'
                                });
                                this.step = 3;
                            }
                        })
                        .catch(err => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred during compilation.'
                            });
                            this.step = 3;
                        });
                }
            };
        }
    </script>
@endsection