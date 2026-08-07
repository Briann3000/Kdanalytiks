@extends('layouts.app')

@section('title', __('Proofread Thesis — Research Studio'))

@section('content')
    <div class="container-fluid px-4 md:px-8 py-8" x-data="proofreadWizard()">
        <div class="max-w-7xl mx-auto space-y-6">
            <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <span class="text-xs font-black text-[#2271b1] tracking-widest">{{ __('Research Studio') }}</span>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight mt-1">{{ __('Proofread Thesis') }}</h1>
                    <p class="text-gray-500 font-medium mt-1">
                        {{ __('Upload a document or paste raw text to highlight spelling, grammar, and punctuation mistakes.') }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('research-studio.proofread.history') }}"
                        class="px-4 py-2.5 bg-blue-50 hover:bg-blue-100 text-[#2271b1] font-bold rounded-2xl border border-blue-100 transition-all text-xs flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        {{ __('Proofread History') }}
                    </a>
                </div>
            </header>

            <!-- Step 1: Upload / Paste Panel -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8" x-show="step === 'upload'">
                <div class="max-w-2xl mx-auto space-y-6">
                    <div class="flex justify-center border-b border-gray-100 pb-4 gap-4">
                        <button type="button" @click="inputMode = 'file'"
                            :class="inputMode === 'file' ? 'bg-[#2271b1] text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            class="px-5 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2">
                            <i class="fa-solid fa-file-arrow-up"></i>
                            {{ __('Upload Document (.docx, .txt)') }}
                        </button>
                        <button type="button" @click="inputMode = 'paste'"
                            :class="inputMode === 'paste' ? 'bg-[#2271b1] text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            class="px-5 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2">
                            <i class="fa-solid fa-paste"></i>
                            {{ __('Paste Raw Draft Text') }}
                        </button>
                    </div>

                    <!-- File Dropzone -->
                    <div x-show="inputMode === 'file'" class="text-center py-6">
                        <div
                            class="w-16 h-16 bg-blue-50 text-[#2271b1] rounded-3xl flex items-center justify-center mx-auto mb-4 shadow-sm">
                            <i class="fa-solid fa-file-word text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-1">{{ __('Upload your thesis draft') }}</h3>
                        <p class="text-gray-400 text-xs mb-6">{{ __('Supports .docx and .txt formats (Max 20MB)') }}</p>

                        <div
                            class="relative border-2 border-dashed border-gray-200 rounded-2xl p-8 hover:border-indigo-500 transition-all group bg-gray-50/50 cursor-pointer max-w-lg mx-auto">
                            <input type="file" @change="handleFileUpload($event)"
                                class="absolute inset-0 opacity-0 cursor-pointer" accept=".docx,.txt">
                            <div class="space-y-2">
                                <i
                                    class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400 group-hover:text-[#2271b1] transition-colors"></i>
                                <p class="text-xs font-bold text-gray-700">{{ __('Drag and drop or click to upload file') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Paste Text Area -->
                    <div x-show="inputMode === 'paste'" class="space-y-4">
                        <label
                            class="block text-xs font-bold text-gray-700">{{ __('Paste Draft Paragraphs Below') }}</label>
                        <textarea x-model="pastedText" rows="10"
                            placeholder="{{ __('Paste your chapter draft or essay here...') }}"
                            class="w-full text-xs font-medium p-4 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-[#2271b1] transition-all custom-scrollbar bg-gray-50/30"></textarea>
                        <div class="flex justify-end">
                            <button type="button" @click="handleTextSubmit()" :disabled="!pastedText.trim()"
                                class="px-6 py-3 bg-[#2271b1] hover:bg-blue-700 text-white font-bold rounded-2xl shadow-md transition-all text-xs disabled:opacity-50 flex items-center gap-2">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                                {{ __('Proofread Pasted Text') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Processing Loader -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-12 text-center"
                x-show="step === 'processing'">
                <div class="max-w-md mx-auto py-8 space-y-6">
                    <div class="relative w-24 h-24 mx-auto">
                        <div class="absolute inset-0 border-4 border-blue-100 rounded-full"></div>
                        <div class="absolute inset-0 border-4 border-t-blue-600 rounded-full animate-spin"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <i class="fa-solid fa-wand-magic-sparkles text-2xl text-[#2271b1]"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ __('Analyzing and Proofreading...') }}</h3>
                        <p class="text-gray-400 text-xs mt-1 font-semibold">
                            {{ __('KDAnalytiks is examining spelling, grammar and punctuation patterns.') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Step 3: Continuous Document Review Panel -->
            <div class="space-y-6" x-show="step === 'diff'" style="display: none;">
                <!-- Toolbar Header (Clean without Accept/Reject All) -->
                <div
                    class="bg-white rounded-3xl border border-gray-100 p-6 flex flex-col sm:flex-row justify-between items-center gap-4 shadow-xs">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-2xl bg-blue-50 text-[#2271b1] flex items-center justify-center font-bold">
                            <i class="fa-solid fa-list-check"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-gray-900 tracking-tight" x-text="documentTitle"></h4>
                            <p class="text-xs text-[#2271b1] font-bold">
                                {{ __('Text is editable inline. Click green suggestion to accept (hides red original). Click red to reject (fades green suggestion).') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <button @click="reset()"
                            class="px-4 py-2.5 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all">
                            {{ __('Proofread Another') }}
                        </button>
                        <button @click="download()"
                            class="px-5 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md transition-all flex items-center gap-2">
                            <i class="fa-solid fa-file-export"></i>
                            {{ __('Download DOCX') }}
                        </button>
                    </div>
                </div>

                <!-- Continuous Document Canvas -->
                <div class="bg-white rounded-3xl border border-gray-100 p-8 sm:p-14 shadow-sm space-y-6">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                        <h3 class="text-2xl font-black text-gray-900" x-text="documentTitle"></h3>
                        <div class="flex items-center gap-2">
                            <button @click="acceptAllDocument()"
                                class="px-4 py-2 bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white font-bold rounded-xl text-xs transition-all border border-emerald-200 flex items-center gap-1.5 shadow-xs">
                                <i class="fa-solid fa-check-double text-xs"></i>
                                <span>{{ __('Accept All') }}</span>
                            </button>
                            <button @click="rejectAllDocument()"
                                class="px-4 py-2 bg-rose-50 hover:bg-rose-600 text-rose-700 hover:text-white font-bold rounded-xl text-xs transition-all border border-rose-200 flex items-center gap-1.5 shadow-xs">
                                <i class="fa-solid fa-xmark text-xs"></i>
                                <span>{{ __('Reject All') }}</span>
                            </button>
                            <button @click="resetAllDocument()"
                                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-xs transition-all border border-gray-200 flex items-center gap-1.5 shadow-xs">
                                <i class="fa-solid fa-rotate-left text-xs"></i>
                                <span>{{ __('Reset All') }}</span>
                            </button>
                        </div>
                    </div>
                    <div class="space-y-6 text-sm text-gray-800 leading-relaxed font-medium">
                        <template x-for="(p, pIndex) in paragraphs" :key="pIndex">
                            <div class="py-1">
                                <!-- Heading Paragraph -->
                                <template x-if="p.isHeading">
                                    <h4 class="text-base font-extrabold text-gray-900 border-b border-gray-100 pb-2 mt-4 uppercase tracking-tight"
                                        contenteditable="true"
                                        @blur="p.original = $el.innerText; p.corrected = $el.innerText"
                                        x-text="p.corrected || p.original"></h4>
                                </template>

                                <!-- Standard Body Paragraph -->
                                <template x-if="!p.isHeading">
                                    <div class="leading-relaxed text-sm text-gray-800 inline-block w-full">
                                        <!-- Interactive Badge Flow -->
                                        <template x-for="(token, tIndex) in (p.tokens || [])" :key="tIndex">
                                            <span class="inline">
                                                <!-- Plain text -->
                                                <span x-show="token.type === 'text'" x-text="token.value"
                                                    contenteditable="true"
                                                    @blur="onTextTokenEdit(p, token, $el.innerText)"></span>

                                                <!-- Change Badges Group -->
                                                <span x-show="token.type === 'change'"
                                                    class="inline-flex items-center gap-1.5 align-baseline mx-0.5">
                                                    <!-- Old Red Error Badge: Hidden if green is accepted -->
                                                    <span
                                                        x-show="token.change && token.change.oldText && token.change.status !== 'accepted'"
                                                        @click="toggleBadge(p, token, 'rejected')"
                                                        :class="token.change && token.change.status === 'rejected' ? 'bg-rose-200 text-rose-950 font-bold border-2 border-rose-500 shadow-xs' : 'bg-rose-100 text-rose-800 font-bold border border-rose-300 line-through'"
                                                        class="px-2 py-0.5 rounded-md text-xs cursor-pointer transition-all hover:scale-105 inline-flex items-center gap-1"
                                                        title="{{ __('Click to keep original error text') }}">
                                                        <span x-text="token.change ? token.change.oldText : ''"></span>
                                                        <i class="fa-solid fa-xmark text-[9px] text-rose-600"></i>
                                                    </span>

                                                    <!-- Green Corrected Suggestion Badge: Faded with strikethrough if red is selected -->
                                                    <span x-show="token.change && token.change.newText"
                                                        @click="toggleBadge(p, token, 'accepted')"
                                                        :class="token.change && token.change.status === 'accepted' ? 'bg-emerald-100 text-emerald-900 font-bold border-2 border-emerald-400 shadow-xs' : (token.change && token.change.status === 'rejected' ? 'bg-emerald-50/30 text-emerald-600 border border-emerald-200 border-dashed opacity-30 line-through' : 'bg-emerald-100 text-emerald-900 font-bold border border-emerald-300')"
                                                        class="px-2 py-0.5 rounded-md text-xs cursor-pointer transition-all hover:scale-105 inline-flex items-center gap-1"
                                                        title="{{ __('Click to accept suggested correction') }}">
                                                        <span x-text="token.change ? token.change.newText : ''"></span>
                                                        <i class="fa-solid fa-check text-[9px] text-emerald-600"></i>
                                                    </span>
                                                </span>
                                            </span>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function proofreadWizard() {
            return {
                step: 'upload',
                inputMode: 'file',
                documentTitle: 'Proofread Document',
                pastedText: '',
                paragraphs: [],

                init() {
                    @if(isset($proofread) && $proofread)
                        this.documentTitle = @json($proofread->title);
                        this.paragraphs = (@json($proofread->paragraphs) || []).map(p => this.parseParagraph(p));
                        this.step = 'diff';
                    @endif
                    },

                handleFileUpload(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('file', file);

                    this.step = 'processing';

                    fetch('{{ route("research-studio.proofread.process") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                this.documentTitle = data.title || file.name;
                                this.paragraphs = (data.paragraphs || []).map(p => this.parseParagraph(p));
                                this.step = 'diff';
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Proofreading failed',
                                    text: data.error || 'Something went wrong during processing.'
                                });
                                this.step = 'upload';
                            }
                        })
                        .catch(err => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Upload failed',
                                text: 'An error occurred while uploading your document.'
                            });
                            this.step = 'upload';
                        });
                },

                handleTextSubmit() {
                    if (!this.pastedText.trim()) return;

                    const formData = new FormData();
                    formData.append('text', this.pastedText);

                    this.step = 'processing';

                    fetch('{{ route("research-studio.proofread.process") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                this.documentTitle = data.title || 'Pasted Draft Proofread';
                                this.paragraphs = (data.paragraphs || []).map(p => this.parseParagraph(p));
                                this.step = 'diff';
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Proofreading failed',
                                    text: data.error || 'Something went wrong during processing.'
                                });
                                this.step = 'upload';
                            }
                        })
                        .catch(err => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Proofread failed',
                                text: 'An error occurred while proofreading your pasted text.'
                            });
                            this.step = 'upload';
                        });
                },

                parseParagraph(p) {
                    const diffHtml = p.diff || p.original || '';
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = diffHtml;

                    const tokens = [];
                    const changes = [];

                    let curDelText = '';
                    let curInsText = '';

                    function flushChangeBuffer() {
                        if (curDelText || curInsText) {
                            const changeObj = {
                                id: 'c_' + Math.random().toString(36).substr(2, 9),
                                oldText: curDelText.trim(),
                                newText: curInsText.trim(),
                                status: 'pending'
                            };
                            changes.push(changeObj);
                            tokens.push({ type: 'change', change: changeObj });
                            curDelText = '';
                            curInsText = '';
                        }
                    }

                    tempDiv.childNodes.forEach(node => {
                        if (node.nodeType === Node.TEXT_NODE) {
                            const val = node.textContent;
                            if (val) {
                                if (val.trim() === '' && (curDelText || curInsText)) {
                                    // Preserve spacing inside diff buffers without flushing prematurely
                                } else {
                                    flushChangeBuffer();
                                    tokens.push({ type: 'text', value: val });
                                }
                            }
                        } else if (node.nodeType === Node.ELEMENT_NODE) {
                            const tagName = node.tagName.toLowerCase();
                            const textVal = node.textContent || '';

                            if (tagName === 'del') {
                                curDelText = curDelText ? (curDelText + ' ' + textVal) : textVal;
                            } else if (tagName === 'ins') {
                                curInsText = curInsText ? (curInsText + ' ' + textVal) : textVal;
                            } else {
                                flushChangeBuffer();
                                tokens.push({ type: 'text', value: textVal });
                            }
                        }
                    });

                    flushChangeBuffer();

                    p.tokens = tokens;
                    p.changes = changes;
                    p.status = 'pending';
                    return p;
                },

                onTextTokenEdit(p, token, newText) {
                    token.value = newText;
                    this.updateParagraphText(p);
                },

                countPendingChanges() {
                    let count = 0;
                    this.paragraphs.forEach(p => {
                        if (p.changes) {
                            p.changes.forEach(c => {
                                if (c && c.status === 'pending') count++;
                            });
                        }
                    });
                    return count;
                },

                toggleBadge(p, token, targetStatus) {
                    if (!token || !token.change) return;
                    if (token.change.status === targetStatus) {
                        token.change.status = 'pending';
                    } else {
                        token.change.status = targetStatus;
                    }
                    if (p.tokens) {
                        p.tokens = [...p.tokens];
                    }
                    this.updateParagraphText(p);
                },

                acceptAllDocument() {
                    this.paragraphs.forEach(p => {
                        if (p.changes) {
                            p.changes.forEach(c => { if (c) c.status = 'accepted'; });
                        }
                        if (p.tokens) {
                            p.tokens = [...p.tokens];
                        }
                        p.status = 'accepted';
                        this.updateParagraphText(p);
                    });
                },

                rejectAllDocument() {
                    this.paragraphs.forEach(p => {
                        if (p.changes) {
                            p.changes.forEach(c => { if (c) c.status = 'rejected'; });
                        }
                        if (p.tokens) {
                            p.tokens = [...p.tokens];
                        }
                        p.status = 'rejected';
                        this.updateParagraphText(p);
                    });
                },

                resetAllDocument() {
                    this.paragraphs.forEach(p => {
                        if (p.changes) {
                            p.changes.forEach(c => { if (c) c.status = 'pending'; });
                        }
                        if (p.tokens) {
                            p.tokens = [...p.tokens];
                        }
                        p.status = 'pending';
                        this.updateParagraphText(p);
                    });
                },

                updateParagraphText(p) {
                    if (!p.tokens) return;
                    let finalText = '';
                    p.tokens.forEach(tok => {
                        if (tok.type === 'text') {
                            finalText += tok.value;
                        } else if (tok.type === 'change' && tok.change) {
                            if (tok.change.status === 'accepted') {
                                finalText += tok.change.newText || '';
                            } else {
                                finalText += tok.change.oldText || '';
                            }
                        }
                    });
                    p.corrected = finalText;
                },

                download() {
                    this.paragraphs.forEach(p => this.updateParagraphText(p));

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("research-studio.proofread.download") }}';

                    const tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = '_token';
                    tokenInput.value = '{{ csrf_token() }}';
                    form.appendChild(tokenInput);

                    const dataInput = document.createElement('input');
                    dataInput.type = 'hidden';
                    dataInput.name = 'paragraphs';
                    dataInput.value = JSON.stringify(this.paragraphs.map(p => ({
                        original: p.original,
                        corrected: p.corrected || p.original,
                        status: p.status,
                        isHeading: p.isHeading
                    })));
                    form.appendChild(dataInput);

                    document.body.appendChild(form);
                    form.submit();
                    document.body.removeChild(form);
                },

                reset() {
                    this.step = 'upload';
                    this.pastedText = '';
                    this.paragraphs = [];
                }
            };
        }
    </script>
@endsection