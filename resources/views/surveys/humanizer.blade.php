@extends('layouts.app')

@section('title', __('AI Content Humanizer | Refine Writing Flow & Style'))
@section('meta_description', 'Humanize AI-generated text to enhance natural writing flow, sentence rhythm, and vocabulary variety.')

@section('content')
    <div x-data="{
                        humanizerOriginal: @js(request('text', '')),
                        humanizerResult: '',
                        isHumanizing: false,
                        isAnalyzing: false,
                        humanizerMode: 'standard',
                        humanizerIntensity: 'medium',
                        customInstructions: '',
                        originalAnalysis: null,
                        humanizedAnalysis: null,

                        async analyzeHumanizerText() {
                            if (!this.humanizerOriginal.trim()) return;
                            this.isAnalyzing = true;
                            try {
                                const response = await fetch('{{ route('humanizer.process') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                                    },
                                    body: JSON.stringify({
                                        text: this.humanizerOriginal,
                                        analyze_only: true
                                    })
                                });
                                const data = await response.json();
                                this.originalAnalysis = data.analysis;
                            } catch (e) {
                                console.error(e);
                            } finally {
                                this.isAnalyzing = false;
                            }
                        },

                        async humanizeAction() {
                            if (!this.humanizerOriginal.trim()) return;
                            this.isHumanizing = true;
                            this.humanizerResult = '';
                            this.humanizedAnalysis = null;
                            try {
                                const response = await fetch('{{ route('humanizer.process') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                                    },
                                    body: JSON.stringify({
                                        text: this.humanizerOriginal,
                                        mode: this.humanizerMode,
                                        intensity: this.humanizerIntensity,
                                        custom_instructions: this.customInstructions
                                    })
                                });
                                const data = await response.json();
                                if (data.error) {
                                    Swal.fire({
                                        title: 'Humanizer Error',
                                        text: data.message,
                                        icon: 'error',
                                        customClass: { popup: 'rounded-3xl' }
                                    });
                                    return;
                                }
                                this.humanizerResult = data.humanized_text;
                                this.humanizedAnalysis = data.humanized_analysis;
                            } catch (e) {
                                console.error(e);
                                Swal.fire({
                                    title: 'Error',
                                    text: 'Failed to humanize text. Please try again.',
                                    icon: 'error',
                                    customClass: { popup: 'rounded-3xl' }
                                });
                            } finally {
                                this.isHumanizing = false;
                            }
                        },

                        transferBack() {
                            if (!this.humanizerResult.trim()) return;
                            this.humanizerOriginal = this.humanizerResult;
                            this.humanizerResult = '';
                            this.humanizedAnalysis = null;
                            this.originalAnalysis = null;
                            this.$nextTick(() => { this.analyzeHumanizerText(); });
                        }
                    }"
        class="min-h-screen bg-[#1d2327] text-slate-100 rounded-3xl border border-white/5 shadow-2xl flex flex-col font-sans">
        <!-- Header -->
        <div class="px-8 py-5 border-b border-white/5 bg-black/30 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div>
                    <h3 class="text-base font-black tracking-tight text-white">{{ __('AI Content Humanizer') }}</h3>
                    <p class="text-[10px] text-slate-400 font-medium mt-0.5">
                        {{ __('Paste → Check → Humanize → Edit → Transfer Back → Repeat until desired AI %') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Main Content Area: 3-column grid [left | arrow | right] -->
        <div class="flex-1 p-8 grid grid-cols-1 lg:grid-cols-[1fr_52px_1fr] gap-0 lg:gap-0 items-start bg-[#1d2327]">

            <!-- ═══════════════════════════════════════ LEFT PANEL ═══════════════════════════════════════ -->
            <div class="flex flex-col gap-6 lg:pr-4">
                <div class="rounded-2xl border border-white/5 bg-black/20 p-6 flex flex-col gap-4 shadow-sm relative">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span
                                class="text-xs font-black tracking-wider text-[#2271b1]">{{ __('Original AI Output') }}</span>
                            <span x-show="humanizerOriginal.trim()" class="text-[10px] text-slate-400 font-mono"
                                x-text="`${(humanizerOriginal.trim().match(/\s+/g) || []).length + 1} {{ __('words') }} | ${humanizerOriginal.length} {{ __('chars') }}`"></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <button x-show="humanizerOriginal.trim()"
                                @click="navigator.clipboard.writeText(humanizerOriginal); Swal.fire({title: 'Copied!', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 1500})"
                                class="text-[10px] text-[#2271b1] hover:text-blue-300 font-black tracking-wider transition-colors flex items-center gap-1">
                                <i class="fa-regular fa-copy"></i> {{ __('Copy') }}
                            </button>
                            <button
                                @click="humanizerOriginal = ''; originalAnalysis = null; humanizedAnalysis = null; humanizerResult = ''; customInstructions = '';"
                                class="text-[10px] text-slate-400 hover:text-white font-bold transition-colors">
                                {{ __('Clear') }}
                            </button>
                        </div>
                    </div>

                    <textarea x-model="humanizerOriginal" @input.debounce.600ms="analyzeHumanizerText()"
                        placeholder="{{ __('Paste your AI-generated text here...') }}"
                        class="w-full h-80 bg-black/30 border border-white/5 rounded-xl p-4 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-[#2271b1]/30 focus:ring-1 focus:ring-[#2271b1]/10 transition-all custom-scrollbar resize-none"></textarea>

                    <div class="flex flex-col gap-4">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <!-- Mode Selector -->
                                <div class="flex flex-col gap-1">
                                    <span
                                        class="text-[9px] font-black tracking-wider text-slate-400">{{ __('Tone Mode') }}</span>
                                    <select x-model="humanizerMode"
                                        class="bg-[#1d2327] border border-white/5 text-slate-300 rounded-lg px-2.5 py-1.5 text-[11px] font-bold focus:outline-none focus:border-[#2271b1]/30">
                                        <option value="standard">{{ __('Standard human flow') }}</option>
                                        <option value="academic">{{ __('Academic / Researcher') }}</option>
                                        <option value="creative">{{ __('Creative / Expressive') }}</option>
                                    </select>
                                </div>

                                <!-- Intensity Selector -->
                                <div class="flex flex-col gap-1">
                                    <span
                                        class="text-[9px] font-black tracking-wider text-slate-400">{{ __('Humanize Strength') }}</span>
                                    <div class="flex bg-black/20 p-0.5 rounded-lg border border-white/5">
                                        @foreach(['low', 'medium', 'high'] as $level)
                                            <button @click="humanizerIntensity = '{{ $level }}'"
                                                :class="humanizerIntensity === '{{ $level }}' ? 'bg-[#2271b1] text-white' : 'text-slate-400 hover:text-slate-200'"
                                                class="px-2.5 py-1 rounded-md text-[10px] font-bold transition-all">
                                                {{ __(ucfirst($level)) }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-2">
                                <!-- Check AI % -->
                                <button @click="analyzeHumanizerText()" :disabled="isAnalyzing || !humanizerOriginal.trim()"
                                    class="inline-flex items-center gap-2 px-4 py-3 border border-[#2271b1]/40 hover:border-[#2271b1] disabled:opacity-30 text-[#2271b1] hover:text-blue-300 rounded-xl text-xs font-black tracking-widest transition-all">
                                    <i class="fa-solid fa-percent" :class="isAnalyzing ? 'animate-pulse' : ''"></i>
                                    <span
                                        x-text="isAnalyzing ? '{{ __('Checking...') }}' : '{{ __('Check AI %') }}'"></span>
                                </button>

                                <!-- Humanize -->
                                <button @click="humanizeAction()" :disabled="isHumanizing || !humanizerOriginal.trim()"
                                    class="inline-flex items-center gap-2 px-6 py-3 bg-[#2271b1] hover:bg-blue-600 disabled:opacity-30 text-white rounded-xl text-xs font-black tracking-widest transition-all shadow-lg shadow-[#2271b1]/10">
                                    <i class="fa-solid fa-circle-nodes" :class="isHumanizing ? 'animate-spin' : ''"></i>
                                    <span
                                        x-text="isHumanizing ? '{{ __('Rewriting...') }}' : (customInstructions.trim() ? '{{ __('Update with Personal Voice') }}' : '{{ __('Humanize Text') }}')"></span>
                                </button>
                            </div>
                        </div>

                        <!-- Personal Voice & Custom Style Notes -->
                        <div class="flex flex-col gap-1.5 border-t border-white/5 pt-3">
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-[10px] font-black tracking-wider text-slate-300 flex items-center gap-1.5">
                                    {{ __('Personal Voice & Custom Style Notes') }}
                                </span>
                                <span class="text-[9px] text-slate-500 italic">{{ __('Optional') }}</span>
                            </div>
                            <textarea x-model="customInstructions"
                                placeholder="{{ __('Add your personal writing style, preferred phrases, or specific tone notes (e.g., "Use a conversational undergraduate voice", "Start paragraph 2 with a personal reflection")...') }}"
                                class="w-full h-16 bg-black/30 border border-white/5 rounded-lg p-2.5 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-[#2271b1]/30 transition-all custom-scrollbar resize-none"></textarea>
                        </div>
                    </div>
                </div>

                <!-- AI Footprint Scan (Left / Original) -->
                <div class="rounded-2xl border border-white/5 bg-black/20 p-6 flex flex-col gap-5 shadow-sm"
                    x-show="humanizerOriginal.trim()">
                    <div class="flex items-center justify-between border-b border-white/5 pb-3">
                        <span class="text-xs font-black tracking-wider text-slate-300">{{ __('AI Footprint Scan') }}</span>
                        <span x-show="isAnalyzing"
                            class="text-[10px] text-[#2271b1] animate-pulse font-bold">{{ __('Analyzing patterns...') }}</span>
                    </div>

                    <template x-if="originalAnalysis">
                        <div class="flex flex-col gap-5 animate-in fade-in duration-300">
                            <!-- Score Bars -->
                            <div class="grid sm:grid-cols-3 gap-4">
                                <!-- Human Authenticity Rating -->
                                <div class="bg-black/20 rounded-xl p-3 border border-white/5 flex flex-col gap-1.5">
                                    <div class="flex items-center justify-between">
                                        <span
                                            class="text-[9px] font-black tracking-wider text-slate-400">{{ __('Human Authenticity') }}</span>
                                        <i class="fa-solid fa-circle-info text-slate-500 hover:text-slate-300 text-[10px] cursor-pointer"
                                            title="{{ __('Estimated score of natural human writing flow and authenticity') }}"></i>
                                    </div>
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-lg font-black"
                                            :class="(100 - originalAnalysis.aiProbability) < 40 ? 'text-red-400' : ((100 - originalAnalysis.aiProbability) < 65 ? 'text-amber-400' : 'text-emerald-400')"
                                            x-text="(100 - originalAnalysis.aiProbability) + '%'"></span>
                                    </div>
                                    <div class="w-full h-1.5 bg-white/5 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500"
                                            :style="`width: ${100 - originalAnalysis.aiProbability}%;`"
                                            :class="(100 - originalAnalysis.aiProbability) < 40 ? 'bg-red-500' : ((100 - originalAnalysis.aiProbability) < 65 ? 'bg-amber-500' : 'bg-emerald-500')">
                                        </div>
                                    </div>
                                </div>

                                <!-- Vocabulary Richness -->
                                <div class="bg-black/20 rounded-xl p-3 border border-white/5 flex flex-col gap-1.5">
                                    <div class="flex items-center justify-between">
                                        <span
                                            class="text-[9px] font-black tracking-wider text-slate-400">{{ __('Vocabulary Richness') }}</span>
                                        <i class="fa-solid fa-circle-info text-slate-500 hover:text-slate-300 text-[10px] cursor-pointer"
                                            title="{{ __('Measures how natural, varied, and non-repetitive your word choices are') }}"></i>
                                    </div>
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-lg font-black text-white"
                                            x-text="originalAnalysis.perplexity + '%'"></span>
                                    </div>
                                    <div class="w-full h-1.5 bg-white/5 rounded-full overflow-hidden">
                                        <div class="h-full bg-[#2271b1] rounded-full transition-all duration-500"
                                            :style="`width: ${originalAnalysis.perplexity}%;`"></div>
                                    </div>
                                </div>

                                <!-- Sentence Rhythm & Flow -->
                                <div class="bg-black/20 rounded-xl p-3 border border-white/5 flex flex-col gap-1.5">
                                    <div class="flex items-center justify-between">
                                        <span
                                            class="text-[9px] font-black tracking-wider text-slate-400">{{ __('Sentence Rhythm') }}</span>
                                        <i class="fa-solid fa-circle-info text-slate-500 hover:text-slate-300 text-[10px] cursor-pointer"
                                            title="{{ __('Measures how naturally short and long sentences are mixed together') }}"></i>
                                    </div>
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-lg font-black text-white"
                                            x-text="originalAnalysis.burstiness + '%'"></span>
                                    </div>
                                    <div class="w-full h-1.5 bg-white/5 rounded-full overflow-hidden">
                                        <div class="h-full bg-purple-500 rounded-full transition-all duration-500"
                                            :style="`width: ${originalAnalysis.burstiness}%;`"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Flagged Words -->
                            <div x-show="originalAnalysis.flaggedWords.length > 0" class="flex flex-col gap-2">
                                <span
                                    class="text-[9px] font-black tracking-wider text-slate-400">{{ __('Flagged AI Vocabulary') }}</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="flag in originalAnalysis.flaggedWords">
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-red-500/10 border border-red-500/20 text-[10px] font-bold text-red-300">
                                            <span x-text="flag.word"></span>
                                            <span class="opacity-50 text-[8px]" x-text="'x' + flag.count"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>

                            <!-- Recommendations -->
                            <div class="flex flex-col gap-2 border-t border-white/5 pt-3">
                                <span
                                    class="text-[9px] font-black tracking-wider text-slate-400">{{ __('Structure Recommendations') }}</span>
                                <ul class="flex flex-col gap-1.5">
                                    <template x-for="rec in originalAnalysis.recommendations">
                                        <li class="flex items-start gap-2 text-[11px] text-slate-300 leading-relaxed">
                                            <i class="fa-solid fa-circle-info text-[#2271b1] text-[10px] mt-0.5"></i>
                                            <span x-text="rec"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- ═══════════════════════════════════════ CENTRE ARROW ═══════════════════════════════════════ -->
            <div class="hidden lg:flex flex-col items-center justify-start pt-[22rem]">
                <div class="flex flex-col items-center gap-2">
                    <button x-show="humanizerResult.trim()" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-75" x-transition:enter-end="opacity-100 scale-100"
                        @click="transferBack()"
                        title="{{ __('Move humanized text back to left box for further refinement') }}"
                        class="group w-10 h-10 rounded-full border-2 border-[#2271b1]/50 hover:border-[#2271b1] bg-black/30 hover:bg-[#2271b1]/10 flex items-center justify-center transition-all duration-200 shadow-lg">
                        <i
                            class="fa-solid fa-arrow-left text-[#2271b1] group-hover:text-blue-300 text-sm transition-colors"></i>
                    </button>
                    <span x-show="humanizerResult.trim()" x-transition
                        class="text-[8px] text-slate-500 font-bold tracking-wider text-center leading-tight w-12">
                        {{ __('REFINE') }}
                    </span>
                </div>
            </div>

            <!-- Mobile transfer button (shows between panels on small screens) -->
            <div class="flex lg:hidden justify-center my-2">
                <button x-show="humanizerResult.trim()" @click="transferBack()"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-[#2271b1]/40 hover:border-[#2271b1] text-[#2271b1] rounded-xl text-xs font-black transition-all">
                    <i class="fa-solid fa-arrow-left"></i>
                    {{ __('Transfer back to refine') }}
                </button>
            </div>

            <!-- ═══════════════════════════════════════ RIGHT PANEL ═══════════════════════════════════════ -->
            <div class="flex flex-col gap-4 lg:pl-4">
                <div class="rounded-2xl border border-white/5 bg-black/20 p-6 flex flex-col gap-4 shadow-sm relative">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black tracking-wider text-[#2271b1]">{{ __('Humanized Version') }}</span>
                        <div class="flex items-center gap-2" x-show="humanizerResult">
                            <button
                                @click="navigator.clipboard.writeText(humanizerResult); Swal.fire({title: 'Copied!', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 1500})"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-[10px] font-bold text-slate-300 transition-colors">
                                <i class="fa-regular fa-copy"></i>
                                {{ __('Copy') }}
                            </button>
                            <form action="{{ route('humanizer.download') }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="text" :value="humanizerResult">
                                <input type="hidden" name="filename" value="humanized_document.docx">
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-[#2271b1]/80 hover:bg-[#2271b1] text-[10px] font-bold text-white transition-colors">
                                    <i class="fa-solid fa-file-word"></i>
                                    {{ __('Download Doc') }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Output area: relative container for loading overlay + textarea -->
                    <div class="relative">
                        <!-- Loading Overlay -->
                        <div x-show="isHumanizing"
                            class="absolute inset-0 bg-[#1d2327]/95 flex flex-col items-center justify-center gap-3 z-30 animate-in fade-in duration-150 rounded-xl">
                            <div class="relative w-12 h-12 flex items-center justify-center">
                                <span class="absolute w-full h-full border-2 border-[#2271b1]/20 rounded-full"></span>
                                <span
                                    class="absolute w-full h-full border-2 border-t-[#2271b1] rounded-full animate-spin"></span>
                            </div>
                            <span
                                class="text-xs text-slate-300 font-black tracking-widest mt-2">{{ __('Humanizing content...') }}</span>
                            <span
                                class="text-[9px] text-slate-500">{{ __('Chunking paragraphs and optimizing human authenticity flow...') }}</span>
                        </div>

                        <!-- Placeholder -->
                        <div x-show="!humanizerResult && !isHumanizing"
                            class="w-full h-80 bg-black/30 border border-white/5 rounded-xl flex flex-col items-center justify-center text-slate-500 gap-2">
                            <i class="fa-solid fa-microchip-slash text-2xl opacity-40"></i>
                            <span
                                class="text-[11px] font-bold tracking-tight">{{ __('Humanized content will generate here') }}</span>
                        </div>

                        <!-- Editable Output Textarea -->
                        <textarea x-show="humanizerResult || isHumanizing" x-model="humanizerResult"
                            placeholder="{{ __('Humanized text will appear here. You can edit it directly.') }}"
                            class="w-full h-80 bg-black/30 border border-white/5 rounded-xl p-5 text-sm text-slate-200 leading-relaxed custom-scrollbar resize-none focus:outline-none focus:border-[#2271b1]/30 focus:ring-1 focus:ring-[#2271b1]/10 transition-all"></textarea>
                    </div>
                </div>

                <!-- ─── Post-humanization AI % Stats ─── -->
                <div x-show="humanizedAnalysis" x-transition:enter="transition ease-out duration-400"
                    x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    class="rounded-2xl border border-emerald-500/10 bg-emerald-500/5 p-5 flex flex-col gap-4 shadow-sm">

                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black tracking-wider text-emerald-400 flex items-center gap-2">
                            <i class="fa-solid fa-shield-check"></i>
                            {{ __('Post-Humanization Score') }}
                        </span>
                        <span class="text-[9px] text-slate-500 italic">{{ __('Based on current output text') }}</span>
                    </div>

                    <template x-if="humanizedAnalysis">
                        <div class="grid grid-cols-3 gap-3">
                            <!-- Human Authenticity -->
                            <div class="bg-black/20 rounded-xl p-3 border border-white/5 flex flex-col gap-1.5">
                                <span
                                    class="text-[9px] font-black tracking-wider text-slate-400">{{ __('Human Auth.') }}</span>
                                <span class="text-xl font-black"
                                    :class="(100 - humanizedAnalysis.aiProbability) < 40 ? 'text-red-400' : ((100 - humanizedAnalysis.aiProbability) < 65 ? 'text-amber-400' : 'text-emerald-400')"
                                    x-text="(100 - humanizedAnalysis.aiProbability) + '%'"></span>
                                <div class="w-full h-1.5 bg-white/5 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-700"
                                        :style="`width: ${100 - humanizedAnalysis.aiProbability}%;`"
                                        :class="(100 - humanizedAnalysis.aiProbability) < 40 ? 'bg-red-500' : ((100 - humanizedAnalysis.aiProbability) < 65 ? 'bg-amber-500' : 'bg-emerald-500')">
                                    </div>
                                </div>
                            </div>

                            <!-- Vocabulary Richness -->
                            <div class="bg-black/20 rounded-xl p-3 border border-white/5 flex flex-col gap-1.5">
                                <span
                                    class="text-[9px] font-black tracking-wider text-slate-400">{{ __('Vocab Richness') }}</span>
                                <span class="text-xl font-black text-white"
                                    x-text="humanizedAnalysis.perplexity + '%'"></span>
                                <div class="w-full h-1.5 bg-white/5 rounded-full overflow-hidden">
                                    <div class="h-full bg-[#2271b1] rounded-full transition-all duration-700"
                                        :style="`width: ${humanizedAnalysis.perplexity}%;`"></div>
                                </div>
                            </div>

                            <!-- Sentence Rhythm -->
                            <div class="bg-black/20 rounded-xl p-3 border border-white/5 flex flex-col gap-1.5">
                                <span class="text-[9px] font-black tracking-wider text-slate-400">{{ __('Rhythm') }}</span>
                                <span class="text-xl font-black text-white"
                                    x-text="humanizedAnalysis.burstiness + '%'"></span>
                                <div class="w-full h-1.5 bg-white/5 rounded-full overflow-hidden">
                                    <div class="h-full bg-purple-500 rounded-full transition-all duration-700"
                                        :style="`width: ${humanizedAnalysis.burstiness}%;`"></div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Transfer back hint -->
                    <div x-show="humanizedAnalysis && (100 - humanizedAnalysis.aiProbability) < 65"
                        class="flex items-center gap-2 text-[10px] text-amber-400/80 font-bold border-t border-white/5 pt-3">
                        <i class="fa-solid fa-arrow-left"></i>
                        {{ __('Not satisfied? Edit the text above or press ← to transfer back and re-humanize.') }}
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection