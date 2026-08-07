@extends('layouts.app')

@section('title', __('Generate Report — Research Studio'))

@section('content')
    <div class="container-fluid px-4 md:px-8 py-8" x-data="reportWizard()">
        <div class="max-w-4xl mx-auto space-y-6">
            <header class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-black text-[#2271b1] tracking-widest">{{ __('Research Studio') }}</span>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight mt-1">
                        {{ __('Generate Full Thesis Report') }}
                    </h1>
                    <p class="text-gray-500 font-medium mt-1">
                        {{ __('Compile Chapters 1–5 using proposal chapters and collected survey data.') }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('research-studio.report.history') }}"
                        class="px-5 py-2.5 bg-blue-50 hover:bg-blue-100 text-[#2271b1] font-bold rounded-2xl border border-blue-100 transition-all text-xs flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        {{ __('Report History') }}
                    </a>
                </div>
            </header>

            <!-- Progress Indicator -->
            <div class="flex items-center justify-between mb-8 bg-white border border-gray-100 rounded-3xl p-6 shadow-xs">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs"
                        :class="step === 1 ? 'bg-[#2271b1] text-white' : 'bg-gray-100 text-gray-500'">1</div>
                    <span class="text-xs font-bold text-gray-800">{{ __('Select Proposal Source') }}</span>
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
                    <span class="text-xs font-bold text-gray-800">{{ __('Generate') }}</span>
                </div>
            </div>

            <!-- Step 1: Title & Proposal Selection -->
            <div class="bg-white rounded-3xl border border-gray-100 p-8 space-y-6 shadow-xs" x-show="step === 1">
                <div class="space-y-2">
                    <label for="title"
                        class="block text-xs font-black text-gray-500 tracking-wider">{{ __('Report Title') }}</label>
                    <input type="text" id="title" x-model="title" required placeholder="{{ __('e.g. Socio-Economic Impact of Remote Work: Final Thesis Report') }}"
                        class="w-full bg-gray-50 border-gray-100 rounded-2xl px-5 py-4 text-gray-900 font-bold placeholder-gray-300 focus:ring-2 focus:ring-[#2271b1]/20 focus:border-[#2271b1] transition-all">
                </div>

                <div class="space-y-4">
                    <label class="block text-xs font-black text-gray-500 tracking-wider">{{ __('Proposal Source (Chapters 1–3)') }}</label>

                    <div class="flex border-b border-gray-100 pb-3 gap-4">
                        <button type="button" @click="proposalSource = 'select'"
                            :class="proposalSource === 'select' ? 'bg-[#2271b1] text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                            <i class="fa-solid fa-list-check"></i>
                            {{ __('Choose Saved Proposal in System') }}
                        </button>
                        <button type="button" @click="proposalSource = 'upload'"
                            :class="proposalSource === 'upload' ? 'bg-[#2271b1] text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                            <i class="fa-solid fa-file-arrow-up"></i>
                            {{ __('Upload DOCX/TXT File') }}
                        </button>
                    </div>

                    <!-- Dropdown for Saved Proposals -->
                    <div x-show="proposalSource === 'select'" class="space-y-3 pt-2">
                        @if(!isset($proposals) || $proposals->isEmpty())
                            <div class="p-4 bg-amber-50 rounded-2xl border border-amber-200 text-xs text-amber-900 flex items-center gap-2">
                                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                                <span>{{ __('No saved proposals found. Please switch to file upload or draft a proposal first.') }}</span>
                            </div>
                        @else
                            <select x-model="selectedProposalId" @change="loadSelectedProposal()"
                                class="w-full bg-gray-50 border-gray-100 rounded-2xl px-5 py-4 text-gray-900 font-bold focus:ring-2 focus:ring-[#2271b1]">
                                <option value="">{{ __('-- Select a Saved Research Proposal --') }}</option>
                                @foreach($proposals as $prop)
                                    <option value="{{ $prop->id }}">{{ $prop->title }} ({{ $prop->created_at->format('M Y') }})</option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <!-- File Dropzone for Upload -->
                    <div x-show="proposalSource === 'upload'" class="pt-2">
                        <div class="relative border-2 border-dashed border-gray-200 rounded-2xl p-8 hover:border-[#2271b1] transition-all group bg-gray-50/50 cursor-pointer text-center">
                            <input type="file" @change="uploadChapters($event)"
                                class="absolute inset-0 opacity-0 cursor-pointer" accept=".docx,.txt">
                            <div class="space-y-2">
                                <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400 group-hover:text-[#2271b1] transition-colors"></i>
                                <p class="text-xs font-bold text-gray-700">{{ __('Drag and drop or click to upload proposal file') }}</p>
                            </div>
                        </div>
                    </div>

                    <div x-show="paragraphs.length > 0" class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-900 font-bold flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i>
                        <span><span x-text="paragraphs.length"></span> {{ __('Chapters/Sections loaded successfully!') }}</span>
                    </div>

                    <!-- Custom Guidelines & Preset Voice (Highlighted Box) -->
                    <div class="p-6 bg-gradient-to-r from-blue-50/80 via-indigo-50/50 to-white rounded-3xl border-2 border-blue-200/80 shadow-xs space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-xl bg-[#2271b1] text-white flex items-center justify-center font-bold text-xs shadow-xs">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                                </div>
                                <div>
                                    <h4 class="text-xs font-black text-gray-900 tracking-wider uppercase">{{ __('Custom Formatting & Voice Presets / KB Guidelines') }}</h4>
                                    <p class="text-[10px] text-gray-500 font-medium">{{ __('Specify tone, structure, formatting preferences, or things to omit/add before generating.') }}</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 text-[10px] font-black bg-blue-100 text-[#2271b1] rounded-lg tracking-wider">{{ __('PRO FEATURE') }}</span>
                        </div>
                        <textarea x-model="customInstructions" id="customInstructions" rows="3"
                            placeholder="{{ __('e.g. Expand Chapter 4 discussion with policy recommendations, omit proposal budget tables from final thesis, and write in formal academic voice...') }}"
                            class="w-full bg-white border border-blue-200/80 rounded-2xl px-5 py-4 text-gray-900 font-medium placeholder-gray-400 focus:ring-2 focus:ring-[#2271b1] transition-all text-xs"></textarea>
                    </div>

                    <!-- Explicit Next Step Button -->
                    <div class="flex justify-end pt-4 border-t border-gray-100">
                        <button type="button" @click="step = 2" :disabled="!title.trim() || paragraphs.length === 0"
                            class="px-8 py-3 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-2xl font-bold text-xs shadow-md transition-all disabled:opacity-50 flex items-center gap-2">
                            <span>{{ __('Next: Review & Proceed') }}</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 2: Proofread Diff Review -->
            <div class="space-y-6" x-show="step === 2" style="display: none;">
                <div class="bg-white rounded-3xl border border-gray-100 p-6 flex flex-col sm:flex-row justify-between items-center gap-4 shadow-xs">
                    <div>
                        <h4 class="text-sm font-black text-gray-900 tracking-tight">
                            {{ __('Verify proposal chapters proofreading') }}
                        </h4>
                        <p class="text-xs text-gray-500 font-semibold">{{ __('Review diff below before proceeding to survey data compilation.') }}</p>
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

                <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden divide-y divide-gray-100 shadow-xs max-h-[500px] overflow-y-auto custom-scrollbar">
                    <template x-for="(p, index) in paragraphs" :key="index">
                        <div class="p-5 hover:bg-gray-50/30 transition-colors flex gap-4 items-start">
                            <span class="text-xs font-bold text-gray-400 shrink-0 mt-1" x-text="index + 1"></span>
                            <div class="flex-1 space-y-2">
                                <div class="text-xs font-semibold text-gray-800" x-text="p.corrected || p.original"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Step 3: Select Survey & Inferential Data -->
            <div class="bg-white rounded-3xl border border-gray-100 p-8 space-y-6 shadow-xs" x-show="step === 3" style="display: none;">
                <div class="space-y-4">
                    <label class="block text-xs font-black text-gray-500 tracking-wider">{{ __('Select Collected Survey Data for Chapter 4') }}</label>
                    <select x-model="selectedSurvey" @change="fetchInferentialTests()"
                        class="w-full bg-gray-50 border-gray-100 rounded-2xl px-5 py-4 text-gray-900 font-bold focus:ring-2 focus:ring-[#2271b1]">
                        <option value="">{{ __('-- Choose Survey --') }}</option>
                        @foreach($surveys as $s)
                            <option value="{{ $s->id }}">{{ $s->title }} ({{ $s->responses_count ?? 0 }} {{ __('Responses') }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Inferential Tests Selection -->
                <div x-show="inferentialTests.length > 0" class="space-y-3 pt-4 border-t border-gray-50">
                    <label class="block text-xs font-black text-gray-500 tracking-wider">{{ __('Include Saved Inferential Analysis Tests') }}</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-for="t in inferentialTests" :key="t.id">
                            <label class="flex items-center gap-3 p-4 bg-gray-50 border border-gray-100 rounded-2xl cursor-pointer hover:bg-blue-50/30 transition-colors">
                                <input type="checkbox" :value="t.id" x-model="selectedTests" class="rounded text-[#2271b1]">
                                <div>
                                    <p class="text-xs font-bold text-gray-900" x-text="t.test_type"></p>
                                    <p class="text-[10px] text-gray-400" x-text="t.variables"></p>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                <div class="flex justify-between items-center pt-4">
                    <button @click="step = 2" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl text-xs font-bold">{{ __('Back') }}</button>
                    <button @click="compileReport()" :disabled="!selectedSurvey"
                        class="px-8 py-3 bg-[#2271b1] hover:bg-blue-700 text-white rounded-2xl text-xs font-bold shadow-md transition-all disabled:opacity-50">
                        {{ __('Compile Full Thesis Report') }}
                    </button>
                </div>
            </div>

            <!-- Step 4: Loading Screen -->
            <div class="bg-white rounded-3xl border border-gray-100 p-12 text-center shadow-xs" x-show="step === 4" style="display: none;">
                <div class="max-w-md mx-auto py-8 space-y-6">
                    <div class="relative w-24 h-24 mx-auto">
                        <div class="absolute inset-0 border-4 border-blue-100 rounded-full"></div>
                        <div class="absolute inset-0 border-4 border-t-blue-600 rounded-full animate-spin"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <i class="fa-solid fa-wand-magic-sparkles text-2xl text-[#2271b1]"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ __('Compiling Chapters 1–5...') }}</h3>
                        <p class="text-gray-400 text-xs mt-1 font-semibold">
                            {{ __('Synthesizing proposal chapters with survey findings and empirical interpretations.') }}
                        </p>
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
                proposalSource: 'select',
                selectedProposalId: '',
                customInstructions: '',
                paragraphs: [],
                selectedSurvey: '',
                inferentialTests: [],
                selectedTests: [],
                previewUrl: null,
                downloadUrl: null,

                loadSelectedProposal() {
                    if (!this.selectedProposalId) return;

                    const proposals = @json($proposals ?? []);
                    const found = proposals.find(p => p.id == this.selectedProposalId);
                    if (found) {
                        this.title = found.title + ' — Final Report';
                        const content = found.content || {};
                        const parsedParas = [];
                        
                        Object.keys(content).forEach(key => {
                            // Extract proposal paragraphs, skipping Proposed Budget if present
                            if (key.toLowerCase().includes('budget')) return;

                            parsedParas.push({
                                original: content[key],
                                corrected: content[key],
                                isHeading: key.toLowerCase().includes('chapter') || key.toLowerCase().includes('section'),
                                status: 'accepted'
                            });
                        });

                        this.paragraphs = parsedParas;
                        // Stay on Step 1 so user can enter customInstructions, then click Next!
                    }
                },

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
                                // Stay on Step 1 so user can enter custom instructions, then click Next!
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
                            custom_instructions: this.customInstructions,
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