@extends('layouts.app')

@section('title', __('Proofread Thesis — Research Studio'))

@section('content')
    <div class="container-fluid px-4 md:px-8 py-8" x-data="proofreadWizard()">
        <div class="max-w-7xl mx-auto">
            <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <span class="text-xs font-black text-[#2271b1] tracking-widest">{{ __('Research Studio') }}</span>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight  mt-1">{{ __('Proofread Thesis') }}
                    </h1>
                    <p class="text-gray-500 font-medium mt-1">
                        {{ __('Upload your draft to highlight spelling, grammar and punctuation mistakes.') }}
                    </p>
                </div>
            </header>

            <!-- Step 1: Upload Panel -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 text-center" x-show="step === 'upload'">
                <div class="max-w-md mx-auto py-12">
                    <div
                        class="w-20 h-20 bg-blue-50 text-[#2271b1] rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-sm">
                        <i class="fa-solid fa-file-word text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">{{ __('Upload your document') }}</h3>
                    <p class="text-gray-500 text-sm mb-6">{{ __('Supports .docx and .txt formats (Max 20MB)') }}</p>

                    <div
                        class="relative border-2 border-dashed border-gray-200 rounded-2xl p-8 hover:border-indigo-500 transition-all group bg-gray-50/50 cursor-pointer">
                        <input type="file" @change="handleFileUpload($event)"
                            class="absolute inset-0 opacity-0 cursor-pointer" accept=".docx,.txt">
                        <div class="space-y-2">
                            <i
                                class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400 group-hover:text-[#2271b1] transition-colors"></i>
                            <p class="text-xs font-bold text-gray-700">{{ __('Drag and drop or click to upload') }}</p>
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

            <!-- Step 3: Diff Panel -->
            <div class="space-y-6" x-show="step === 'diff'" style="display: none;">
                <div
                    class="bg-white rounded-3xl border border-gray-100 p-6 flex flex-col sm:flex-row justify-between items-center gap-4 shadow-xs">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-2xl bg-blue-50 text-[#2271b1] flex items-center justify-center font-bold">
                            <i class="fa-solid fa-list-check"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-gray-900  tracking-tight">
                                {{ __('Review suggested corrections') }}
                            </h4>
                            <p class="text-xs text-gray-500 font-semibold">
                                {{ __('Deletions are in red, insertions are in green.') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button @click="reset()"
                            class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all">
                            {{ __('Upload Another') }}
                        </button>
                        <button @click="download()"
                            class="px-4 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md transition-all flex items-center gap-2">
                            <i class="fa-solid fa-file-export"></i>
                            {{ __('Download Clean DOCX') }}
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-xs divide-y divide-gray-100">
                    <template x-for="(p, pIndex) in paragraphs" :key="pIndex">
                        <div class="p-6 sm:p-8 hover:bg-gray-50/40 transition-colors flex flex-col md:flex-row gap-6 items-start"
                            :class="{'bg-rose-50/20': p.status === 'rejected', 'bg-amber-50/20': p.isEditing}">
                            <div class="w-12 h-12 bg-zinc-50 rounded-2xl flex items-center justify-center shrink-0">
                                <span class="text-xs font-bold text-gray-400" x-text="pIndex + 1"></span>
                            </div>
                            <div class="flex-1 space-y-3 w-full">
                                <div class="flex flex-wrap items-center justify-between gap-4">
                                    <div class="flex items-center gap-2">
                                        <h5 class="text-[10px] font-black tracking-wider"
                                            :class="p.isHeading ? 'text-[#2271b1] font-extrabold' : 'text-gray-400'"
                                            x-text="p.isHeading ? '{{ __('Section Heading') }}' : '{{ __('Paragraph Corrections') }}'">
                                        </h5>

                                        <span x-show="p.changes && p.changes.length > 0"
                                            class="px-2 py-0.5 text-[9px] font-bold bg-blue-50 text-[#2271b1] rounded-md border border-blue-100">
                                            <span x-text="countAccepted(p)"></span>/<span
                                                x-text="p.changes ? p.changes.length : 0"></span> {{ __('Accepted') }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1.5" x-show="!p.isHeading">
                                        <button @click="acceptAllInParagraph(p)"
                                            class="px-2.5 py-1 text-[10px] font-bold rounded-lg transition-all flex items-center gap-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white">
                                            <i class="fa-solid fa-check-double text-[9px]"></i>
                                            <span>{{ __('Accept All') }}</span>
                                        </button>
                                        <button @click="rejectAllInParagraph(p)"
                                            class="px-2.5 py-1 text-[10px] font-bold rounded-lg transition-all flex items-center gap-1 bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white">
                                            <i class="fa-solid fa-xmark text-[9px]"></i>
                                            <span>{{ __('Reject All') }}</span>
                                        </button>
                                        <button @click="p.isEditing = !p.isEditing"
                                            :class="p.isEditing ? 'bg-amber-500 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-amber-50 hover:text-amber-700'"
                                            class="px-2.5 py-1 text-[10px] font-bold rounded-lg transition-all flex items-center gap-1">
                                            <i class="fa-solid fa-pen text-[9px]"></i>
                                            <span>{{ __('Edit') }}</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Interactive Inline Side-by-Side Red/Green Diff -->
                                <div x-show="!p.isEditing" class="relative">
                                    <div x-show="!p.changes || p.changes.length === 0"
                                        class="text-sm text-gray-800 leading-relaxed font-medium" x-text="p.original"></div>

                                    <div x-show="p.changes && p.changes.length > 0"
                                        class="text-sm text-gray-800 leading-relaxed font-medium flex flex-wrap items-baseline gap-x-1.5 gap-y-2">
                                        <template x-for="(token, tIndex) in p.tokens" :key="tIndex">
                                            <span>
                                                <!-- Plain unchanged text -->
                                                <span x-show="token.type === 'text'" x-text="token.value"></span>

                                                <!-- Inline Side-by-Side Change Group -->
                                                <span x-show="token.type === 'change'"
                                                    class="inline-flex items-center gap-1.5 align-baseline">
                                                    <!-- Old text (Red strikethrough if rejected or pending) -->
                                                    <span x-show="token.change.oldText"
                                                        @click="token.change.status = 'rejected'; updateParagraphText(p)"
                                                        :class="token.change.status === 'rejected' ? 'bg-rose-100 text-rose-800 font-bold border border-rose-300' : 'bg-rose-50 text-rose-700 opacity-60 line-through'"
                                                        class="px-1.5 py-0.5 rounded text-xs cursor-pointer transition-all hover:opacity-100"
                                                        title="Click to keep original text">
                                                        <span x-text="token.change.oldText"></span>
                                                        <i class="fa-solid fa-xmark text-[9px] ml-0.5 text-rose-600"></i>
                                                    </span>

                                                    <!-- New suggestion text (Green highlight if accepted) -->
                                                    <span x-show="token.change.newText"
                                                        @click="token.change.status = 'accepted'; updateParagraphText(p)"
                                                        :class="token.change.status === 'accepted' ? 'bg-emerald-100 text-emerald-900 font-bold border border-emerald-300' : 'bg-emerald-50 text-emerald-700 opacity-60'"
                                                        class="px-1.5 py-0.5 rounded text-xs cursor-pointer transition-all hover:opacity-100"
                                                        title="Click to accept suggestion">
                                                        <span x-text="token.change.newText"></span>
                                                        <i class="fa-solid fa-check text-[9px] ml-0.5 text-emerald-600"></i>
                                                    </span>
                                                </span>
                                            </span>
                                        </template>
                                    </div>
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
        </div>
    </div>

    <script>
        function proofreadWizard() {
            return {
                step: 'upload',
                paragraphs: [],
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
                                this.paragraphs = (data.paragraphs || []).map(p => this.parseParagraph(p));
                                this.step = 'diff';
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Proofreading failed',
                                    text: data.error || 'Something went wrong during extraction.'
                                });
                                this.step = 'upload';
                            }
                        })
                        .catch(err => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Upload failed',
                                text: 'An error occurred while uploading your thesis.'
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

                    tempDiv.childNodes.forEach(node => {
                        if (node.nodeType === Node.TEXT_NODE) {
                            if (node.textContent) {
                                tokens.push({ type: 'text', value: node.textContent });
                            }
                        } else if (node.nodeType === Node.ELEMENT_NODE) {
                            const tagName = node.tagName.toLowerCase();
                            if (tagName === 'ins' || tagName === 'del') {
                                const isIns = tagName === 'ins';
                                const changeObj = {
                                    id: 'c_' + Math.random().toString(36).substr(2, 9),
                                    type: isIns ? 'insertion' : 'deletion',
                                    oldText: isIns ? '' : node.textContent,
                                    newText: isIns ? node.textContent : '',
                                    status: 'accepted'
                                };

                                // Check if adjacent del + ins form a replacement pair
                                const lastToken = tokens[tokens.length - 1];
                                if (!isIns && lastToken && lastToken.type === 'text' && lastToken.value.trim() === '') {
                                    // optional spacing handle
                                }

                                changes.push(changeObj);
                                tokens.push({ type: 'change', change: changeObj });
                            } else {
                                tokens.push({ type: 'text', value: node.textContent });
                            }
                        }
                    });

                    p.tokens = tokens;
                    p.changes = changes;
                    p.status = 'accepted';
                    return p;
                },
                countAccepted(p) {
                    if (!p.changes) return 0;
                    return p.changes.filter(c => c.status === 'accepted').length;
                },
                acceptAllInParagraph(p) {
                    if (p.changes) {
                        p.changes.forEach(c => c.status = 'accepted');
                    }
                    p.status = 'accepted';
                    this.updateParagraphText(p);
                },
                rejectAllInParagraph(p) {
                    if (p.changes) {
                        p.changes.forEach(c => c.status = 'rejected');
                    }
                    p.status = 'rejected';
                    this.updateParagraphText(p);
                },
                updateParagraphText(p) {
                    if (!p.tokens) return;
                    let finalText = '';
                    p.tokens.forEach(tok => {
                        if (tok.type === 'text') {
                            finalText += tok.value;
                        } else if (tok.type === 'change') {
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
                    // Ensure every paragraph text is compiled cleanly from accepted/rejected changes
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
                    this.paragraphs = [];
                }
            };
        }
    </script>
@endsection