@props(['questionId', 'questionTitle', 'surveyId', 'index' => 0, 'readOnly' => false])

<div x-data="qualInsightCard('{{ $questionId }}', '{{ $surveyId }}', {{ (int) $index }})"
    x-on:trigger-analysis.window="generate($event.detail.id)"
    class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sm:p-8 my-4 transition-all min-h-[300px] flex flex-col items-center justify-center text-center">

    <!-- Header Actions -->
    <template x-if="insight">
        <div class="flex items-center justify-end mb-4 w-full border-b border-gray-100 pb-3" data-html2canvas-ignore>
            <div class="flex items-center gap-2">
                <button type="button" @click="copyAllOutput()"
                    class="px-3 py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-xl text-xs font-bold transition-all border border-gray-200 flex items-center gap-1.5 shadow-xs">
                    <i class="fa-solid fa-copy text-xs text-gray-500"></i>
                    <span class="hidden sm:inline">{{ __('Copy') }}</span>
                </button>
                <button type="button" @click="generate(null, true)" :disabled="loading"
                    class="px-3 py-1.5 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1.5 disabled:opacity-50">
                    <i class="fa-solid" :class="loading ? 'fa-circle-notch fa-spin' : 'fa-rotate-right'"></i>
                    <span class="hidden sm:inline">{{ __('Regenerate') }}</span>
                </button>
            </div>
        </div>
    </template>

    <!-- Loading State -->
    <div x-show="loading" class="flex flex-col items-center justify-center py-20 w-full">
        <div class="relative w-20 h-20 mb-8">
            <div class="absolute inset-0 rounded-3xl bg-zinc-100 animate-pulse"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <i class="fa fa-chart-pie text-4xl text-[#2271b1]"></i>
            </div>
            <div class="absolute -right-2 -top-2">
                <div class="flex space-x-1">
                    <div class="w-1.5 h-1.5 bg-zinc-500 rounded-full animate-bounce" style="animation-delay: 0.1s">
                    </div>
                    <div class="w-1.5 h-1.5 bg-zinc-500 rounded-full animate-bounce" style="animation-delay: 0.2s">
                    </div>
                    <div class="w-1.5 h-1.5 bg-zinc-500 rounded-full animate-bounce" style="animation-delay: 0.3s">
                    </div>
                </div>
            </div>
        </div>
        <h5 class="text-xl font-bold text-gray-900 mb-2">{{ __('Analyzing Responses') }}</h5>
        <p class="text-gray-400 text-sm max-w-[280px] font-medium leading-relaxed">
            {{ __('Please wait while recurring themes and sentiment trends are analyzed...') }}
        </p>
    </div>

    <!-- Error State -->
    <template x-if="error">
        <div
            class="p-8 sm:p-10 bg-rose-50/70 border border-rose-100 text-rose-600 rounded-3xl flex flex-col items-center gap-4 w-full max-w-lg shadow-sm">
            <div class="w-12 h-12 bg-rose-100 rounded-2xl flex items-center justify-center text-rose-600 shadow-inner">
                <i class="fa fa-triangle-exclamation text-xl"></i>
            </div>
            <div class="text-center space-y-1">
                <p class="font-bold text-xs text-rose-400">
                    {{ __('Analysis Notice') }}
                </p>
                <p x-text="error" class="font-semibold text-sm leading-relaxed text-rose-700"></p>
            </div>
            <button type="button" @click="generate(null, true)" :disabled="loading"
                class="mt-2 inline-flex items-center gap-2 px-5 py-2.5 bg-[#2271b1] hover:bg-[#135e96] text-white font-bold rounded-xl text-xs transition-all shadow-sm disabled:opacity-50">
                <i class="fa-solid" :class="loading ? 'fa-circle-notch fa-spin' : 'fa-rotate-right'"></i>
                <span x-text="loading ? '{{ __('Regenerating...') }}' : '{{ __('Regenerate Analysis') }}'"></span>
            </button>
        </div>
    </template>

    <!-- Initial / Empty State -->
    <div x-show="!insight && !loading && !error"
        class="py-16 text-center w-full bg-gray-50/50 rounded-3xl border-2 border-dashed border-gray-100">
        <div
            class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-sm border border-gray-100">
            <i class="fa fa-comments text-[#2271b1] text-3xl"></i>
        </div>
        <h4 class="text-2xl font-bold text-gray-900 mb-3 tracking-tight">{{ __('Qualitative Analysis') }}</h4>
        <p class="text-sm text-gray-500 font-medium max-w-sm mx-auto leading-relaxed mb-6">
            {{ __('Trigger analysis to identify recurring themes and sentiments.') }}
        </p>
        <button @click="generate()"
            class="inline-flex items-center gap-2 px-6 py-3 bg-[#2271b1] text-white font-bold rounded-xl hover:bg-[#135e96] transition-all shadow-sm">
            <i class="fa-solid fa-play text-xs"></i>
            {{ __('Analyse Responses') }}
        </button>
    </div>

    <!-- Results Body -->
    <template x-if="insight && !loading">
        <div class="space-y-5 w-full text-left">

            <!-- Academic Narrative Synthesis & Natural Conclusion -->
            <template x-if="insight.narrative">
                <div class="bg-gray-50/70 p-5 sm:p-6 rounded-2xl border border-gray-100 space-y-4">
                    <div class="space-y-3">
                        <p class="text-sm text-gray-800 leading-relaxed font-normal whitespace-pre-line"
                            x-text="insight.narrative"></p>

                        <!-- Natural Conclusion prose (seamlessly follows narrative without extra title) -->
                        <template x-if="insight.conclusion">
                            <p class="text-sm text-gray-800 leading-relaxed font-normal whitespace-pre-line pt-1"
                                x-text="insight.conclusion"></p>
                        </template>
                    </div>

                    <!-- What to Note (positioned after conclusion) -->
                    <template x-if="insight.key_findings && insight.key_findings.length > 0">
                        <div class="pt-3 border-t border-gray-200/60 space-y-1.5">
                            <span class="text-xs font-bold text-gray-800 block">{{ __('What to Note') }}:</span>
                            <ul class="list-disc list-inside space-y-1 text-xs text-gray-600">
                                <template x-for="(finding, fIdx) in insight.key_findings" :key="fIdx">
                                    <li x-text="finding"></li>
                                </template>
                            </ul>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Collapsible Content Analysis (Thematic Findings & Evidence) -->
            <div class="space-y-4 relative" :class="insight.is_truncated ? 'overflow-hidden max-h-[380px]' : ''">
                <template
                    x-if="(insight.key_themes && insight.key_themes.length > 0) || (insight.representative_quotes && insight.representative_quotes.length > 0)">
                    <div class="border border-gray-200 rounded-2xl overflow-hidden bg-white shadow-xs"
                        :class="insight.is_truncated ? 'opacity-40 grayscale-[0.5] blur-[1px]' : ''">

                        <!-- Accordion Header Button -->
                        <button type="button" @click="openContentAnalysis = !openContentAnalysis"
                            class="w-full px-5 py-4 bg-gray-50 hover:bg-gray-100/80 transition-colors flex items-center justify-between text-left cursor-pointer select-none">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="w-7 h-7 rounded-lg bg-[#2271b1]/10 text-[#2271b1] flex items-center justify-center text-xs font-bold">
                                    <i class="fa-solid fa-layer-group"></i>
                                </div>
                                <span
                                    class="text-xs font-bold text-gray-900 tracking-tight">{{ __('Content Analysis') }}</span>
                                <template x-if="insight.key_themes && insight.key_themes.length > 0">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] bg-[#2271b1]/10 text-[#2271b1] font-bold"
                                        x-text="`${insight.key_themes.length} {{ __('Themes') }}`"></span>
                                </template>
                            </div>
                            <div class="flex items-center gap-2 text-gray-400">
                                <span class="text-[11px] font-semibold hidden sm:inline"
                                    x-text="openContentAnalysis ? '{{ __('Hide Details') }}' : '{{ __('Show Details') }}'"></span>
                                <i class="fa-solid text-xs transition-transform duration-200"
                                    :class="openContentAnalysis ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                            </div>
                        </button>

                        <!-- Accordion Collapsible Content Body -->
                        <div x-show="openContentAnalysis" x-collapse
                            class="p-5 sm:p-6 space-y-4 border-t border-gray-100 bg-white">
                            <template x-if="insight.key_themes && insight.key_themes.length > 0">
                                <div class="space-y-4">
                                    <template x-for="(themeItem, tIdx) in insight.key_themes" :key="tIdx">
                                        <div
                                            class="p-4 sm:p-5 bg-gray-50/50 rounded-2xl border border-gray-100 space-y-3">
                                            <!-- Theme Header -->
                                            <div class="flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-[#2271b1]"></span>
                                                <h5 class="font-bold text-gray-900 text-sm tracking-tight"
                                                    x-text="themeItem.theme"></h5>
                                            </div>

                                            <!-- Theme Academic Narrative Prose -->
                                            <p class="text-xs sm:text-[13px] text-gray-700 leading-relaxed font-normal whitespace-pre-line"
                                                x-text="themeItem.narrative || themeItem.explanation"></p>

                                            <!-- Embedded Verbatim Quotes for this Theme -->
                                            <template x-if="themeItem.quotes && themeItem.quotes.length > 0">
                                                <div class="space-y-2 pt-2 border-t border-gray-100">
                                                    <template x-for="(quote, qIdx) in themeItem.quotes" :key="qIdx">
                                                        <div
                                                            class="flex gap-3 items-start p-3 bg-white rounded-xl border border-gray-100 italic text-gray-600 text-xs leading-relaxed">
                                                            <i
                                                                class="fa fa-quote-left text-zinc-400 text-xs mt-0.5 shrink-0"></i>
                                                            <span x-text="quote"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Standalone Quotes Fallback -->
                            <template
                                x-if="(!insight.key_themes || insight.key_themes.every(t => !t.quotes || t.quotes.length === 0)) && (insight.representative_quotes && insight.representative_quotes.length > 0)">
                                <div class="space-y-3 pt-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-1.5 h-4 bg-emerald-500 rounded-full"></span>
                                        <span
                                            class="text-xs font-bold text-zinc-500 tracking-tight">{{ __('Representative Quotes') }}</span>
                                    </div>
                                    <div class="space-y-2">
                                        <template x-for="(quote, qIdx) in insight.representative_quotes" :key="qIdx">
                                            <div
                                                class="flex gap-3 items-start p-3 bg-gray-50/70 rounded-xl border border-gray-100 italic text-gray-600 text-xs leading-relaxed">
                                                <i class="fa fa-quote-left text-zinc-400 text-xs mt-0.5 shrink-0"></i>
                                                <span x-text="quote"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Soft Paywall Overlay -->
                <template x-if="insight.is_truncated">
                    <div
                        class="absolute inset-x-0 bottom-0 h-full bg-gradient-to-t from-white via-white/80 to-transparent flex flex-col items-center justify-end pb-8 pt-20 px-6 text-center z-10">
                        <div
                            class="bg-white/95 p-8 rounded-[2.5rem] shadow-2xl border border-gray-100 max-w-sm mb-4 transform translate-y-4">
                            <div
                                class="w-16 h-16 bg-[#2271b1] rounded-2xl flex items-center justify-center text-white mx-auto mb-6 shadow-xl shadow-zinc-300/40">
                                <i class="fa-solid fa-lock text-2xl"></i>
                            </div>
                            <h5 class="text-2xl font-bold text-gray-900 mb-2 tracking-tight">
                                {{ __('Unlock Full Analysis') }}
                            </h5>
                            <p class="text-gray-500 text-sm mb-8 leading-relaxed font-medium">{{ __('Upgrade to') }}
                                <span class="text-[#2271b1] font-bold">{{ __('Respondent Pro') }}</span>
                                {{ __('to reveal all recurring themes, quotes, and deep qualitative mapping.') }}
                            </p>
                            <a href="{{ route('subscriptions.index') }}"
                                class="inline-flex items-center justify-center w-full px-8 py-4 bg-[#2271b1] text-white font-bold rounded-2xl hover:bg-[#135e96] transition-all shadow-lg shadow-zinc-200/50 hover:scale-[1.02] active:scale-[0.98]">
                                {{ __('Unlock Full Report') }}
                                <i class="fa-solid fa-arrow-right ml-2 text-xs"></i>
                            </a>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Inline Refine Box -->
            <div class="pt-4 border-t border-gray-100 flex flex-col sm:flex-row gap-2 w-full" data-html2canvas-ignore>
                <input type="text" x-model="instruction" @keydown.enter.prevent="refine()"
                    placeholder="{{ __('Refine this analysis (e.g. emphasize recurring themes, condense summary)...') }}"
                    class="flex-1 px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#2271b1] focus:border-[#2271b1]" />
                <button type="button" @click="refine()" :disabled="polishing || !instruction.trim()"
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-[#2271b1] hover:bg-[#135e96] disabled:opacity-50 text-white rounded-xl text-xs font-bold transition-all shadow-xs shrink-0">
                    <i class="fa-solid" :class="polishing ? 'fa-circle-notch fa-spin' : 'fa-paper-plane'"></i>
                    <span x-text="polishing ? '{{ __('Applying...') }}' : '{{ __('Apply') }}'"></span>
                </button>
            </div>
        </div>
    </template>
</div>

<script>
    if (typeof window.qualInsightCard === 'undefined') {
        window.qualInsightCard = function (questionId, surveyId, index) {
            return {
                loading: false,
                polishing: false,
                openContentAnalysis: false,
                instruction: '',
                insight: null,
                error: null,
                qId: questionId,
                sId: surveyId,
                idx: index,
                init() {
                    this.$watch('qId', () => { this.insight = null; this.error = null; });
                    window.qualInsightInstances = window.qualInsightInstances || {};
                    window.qualInsightInstances[this.qId] = this;

                    if (this.qId && !this.loading) {
                        const observer = new IntersectionObserver((entries) => {
                            if (entries[0].isIntersecting) {
                                this.generate();
                                observer.disconnect();
                            }
                        }, {
                            threshold: 0.1,
                            rootMargin: '100px'
                        });

                        observer.observe(this.$el);
                    }
                },
                async generate(id = null, forceRefresh = false) {
                    if (id) this.qId = id;
                    if (!this.qId) return;

                    const isHardReload = (window.performance?.getEntriesByType?.('navigation')?.[0]?.type === 'reload')
                        || (window.performance?.navigation?.type === 1)
                        || new URLSearchParams(window.location.search).has('refresh');

                    const shouldForce = forceRefresh || isHardReload;

                    if (this.loading || (this.insight && !shouldForce)) return;

                    this.loading = true;
                    this.error = null;
                    try {
                        const headers = { 'Accept': 'application/json' };
                        if (shouldForce) {
                            headers['Cache-Control'] = 'no-cache';
                            headers['Pragma'] = 'no-cache';
                        }
                        const url = `/ai/insights/question/${this.qId}?survey_id=${this.sId}` + (shouldForce ? '&refresh=1' : '');
                        const response = await fetch(url, {
                            headers,
                            cache: shouldForce ? 'no-cache' : 'default'
                        });
                        if (response.status === 429) {
                            throw new Error(@js(__('Rate limit reached. Please wait a few moments and click Regenerate.')));
                        }
                        if (!response.ok) {
                            const errData = await response.json().catch(() => ({}));
                            throw new Error(errData.message || @js(__('Unable to generate analysis at this time.')));
                        }

                        const data = await response.json();
                        if (data.error) {
                            throw new Error(data.error);
                        }
                        this.insight = data;
                        this.error = null;
                    } catch (err) {
                        this.error = err.message || @js(__('Unable to generate analysis at this time.'));
                        console.error('Analysis error:', err);
                    } finally {
                        this.loading = false;
                    }
                },
                async copyAllOutput() {
                    if (!this.insight) return;

                    // Plain Text Version (Clean, professional, natural narrative flow)
                    let plainText = '';
                    if (this.insight.narrative) {
                        plainText += this.insight.narrative + '\n\n';
                    }
                    if (this.insight.conclusion) {
                        plainText += this.insight.conclusion + '\n\n';
                    }
                    if (this.insight.key_findings && this.insight.key_findings.length) {
                        plainText += @js(__('What to Note')) + ':\n';
                        this.insight.key_findings.forEach(f => plainText += `• ${f}\n`);
                        plainText += '\n';
                    }
                    if (this.insight.key_themes && this.insight.key_themes.length) {
                        plainText += @js(__('Content Analysis')) + ':\n\n';
                        this.insight.key_themes.forEach((t, i) => {
                            plainText += `${i + 1}. ${t.theme}\n${t.narrative || t.explanation}\n`;
                            if (t.quotes && t.quotes.length) {
                                t.quotes.forEach(q => plainText += `   "${q.replace(/^["']|["']$/g, '')}"\n`);
                            }
                            plainText += '\n';
                        });
                    } else if (this.insight.representative_quotes && this.insight.representative_quotes.length) {
                        plainText += @js(__('Representative Quotes')) + ':\n';
                        this.insight.representative_quotes.forEach(q => plainText += `• "${q.replace(/^["']|["']$/g, '')}"\n`);
                    }

                    // Rich HTML Version (For Word, Google Docs, Apple Pages, Email)
                    let htmlContent = '';
                    if (this.insight.narrative) {
                        htmlContent += `<p>${this.insight.narrative.replace(/\n/g, '<br>')}</p>`;
                    }
                    if (this.insight.conclusion) {
                        htmlContent += `<p>${this.insight.conclusion.replace(/\n/g, '<br>')}</p>`;
                    }
                    if (this.insight.key_findings && this.insight.key_findings.length) {
                        htmlContent += `<p><strong>${@js(__('What to Note'))}:</strong></p><ul>`;
                        this.insight.key_findings.forEach(f => htmlContent += `<li>${f}</li>`);
                        htmlContent += `</ul>`;
                    }
                    if (this.insight.key_themes && this.insight.key_themes.length) {
                        htmlContent += `<p><strong>${@js(__('Content Analysis'))}:</strong></p>`;
                        this.insight.key_themes.forEach((t, i) => {
                            htmlContent += `<h4 style="margin-top:16px; margin-bottom:4px; font-weight:bold; font-size:14px;">${i + 1}. ${t.theme}</h4>`;
                            htmlContent += `<p style="margin-top:4px; margin-bottom:8px;">${(t.narrative || t.explanation || '').replace(/\n/g, '<br>')}</p>`;
                            if (t.quotes && t.quotes.length) {
                                t.quotes.forEach(q => {
                                    htmlContent += `<blockquote style="margin:4px 0 6px 16px; padding-left:8px; border-left:3px solid #2271b1; color:#4b5563; font-style:italic;">&ldquo;${q.replace(/^["']|["']$/g, '')}&rdquo;</blockquote>`;
                                });
                            }
                        });
                    } else if (this.insight.representative_quotes && this.insight.representative_quotes.length) {
                        htmlContent += `<p><strong>${@js(__('Representative Quotes'))}:</strong></p><ul>`;
                        this.insight.representative_quotes.forEach(q => {
                            htmlContent += `<li><em>&ldquo;${q.replace(/^["']|["']$/g, '')}&rdquo;</em></li>`;
                        });
                        htmlContent += `</ul>`;
                    }

                    const trimmedPlain = plainText.trim();
                    try {
                        if (navigator.clipboard && window.ClipboardItem) {
                            const textBlob = new Blob([trimmedPlain], { type: 'text/plain' });
                            const htmlBlob = new Blob([htmlContent], { type: 'text/html' });
                            await navigator.clipboard.write([
                                new ClipboardItem({
                                    'text/plain': textBlob,
                                    'text/html': htmlBlob
                                })
                            ]);
                        } else {
                            await navigator.clipboard.writeText(trimmedPlain);
                        }
                    } catch (e) {
                        await navigator.clipboard.writeText(trimmedPlain);
                    }

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ title: @js(__('Copied!')), toast: true, position: 'top-end', timer: 1500, showConfirmButton: false, icon: 'success' });
                    }
                },
                async copyNarrative() {
                    if (!this.insight || !this.insight.narrative) return;
                    const text = this.insight.narrative.trim();
                    const html = `<p>${text.replace(/\n/g, '<br>')}</p>`;
                    try {
                        if (navigator.clipboard && window.ClipboardItem) {
                            await navigator.clipboard.write([
                                new ClipboardItem({
                                    'text/plain': new Blob([text], { type: 'text/plain' }),
                                    'text/html': new Blob([html], { type: 'text/html' })
                                })
                            ]);
                        } else {
                            await navigator.clipboard.writeText(text);
                        }
                    } catch (e) {
                        await navigator.clipboard.writeText(text);
                    }
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ title: @js(__('Copied!')), toast: true, position: 'top-end', timer: 1500, showConfirmButton: false, icon: 'success' });
                    }
                },
                async refine(customInstruction = null) {
                    const prompt = customInstruction || this.instruction;
                    if (!prompt || !prompt.trim()) return;
                    this.polishing = true;
                    try {
                        const csrfEl = document.querySelector('meta[name="csrf-token"]');
                        const csrf = csrfEl ? csrfEl.getAttribute('content') : '';
                        const res = await fetch(`/ai/insights/qualitative/${this.qId}/refine`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                survey_id: this.sId,
                                feedback: prompt,
                                messages: this.insight && this.insight.narrative ? [{ role: 'assistant', content: this.insight.narrative }] : []
                            })
                        });
                        const data = await res.json();
                        if (data.success && data.narrative) {
                            if (!this.insight) this.insight = {};
                            this.insight.narrative = data.narrative;
                            if (data.key_findings) this.insight.key_findings = data.key_findings;
                            if (!customInstruction) this.instruction = '';
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ title: @js(__('Refined Successfully')), toast: true, position: 'top-end', timer: 1500, showConfirmButton: false, icon: 'success' });
                            }
                        }
                    } catch (e) {
                        console.error('Refine failed:', e);
                    } finally {
                        this.polishing = false;
                    }
                },
                async refineFromGlobal(feedbackText, style) {
                    let waitCount = 0;
                    while (this.loading && waitCount < 25) {
                        await new Promise(r => setTimeout(r, 200));
                        waitCount++;
                    }
                    if (this.loading) {
                        this.loading = false;
                    }
                    await this.refine(feedbackText);
                }
            };
        };
    }
</script>