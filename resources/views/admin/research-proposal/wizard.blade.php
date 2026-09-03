{{--
INTERACTIVE MULTI-STEP RESEARCH PROPOSAL WIZARD WITH FULL-SCREEN IN-BETWEEN CHAPTER REFINEMENT STUDIO
Enterprise Academic Minimalist Design:
- Step 1-4: Fast Intake Inputs
- In-Between Studio: Side-by-Side Flex Layout (Document Editor + Sticky AI Copilot Panel)
- Step 5: Master Blueprint Assembly & 1-Click DOCX Export
--}}

<div x-data="proposalWizard()" x-init="initWizard()"
    class="min-h-screen bg-slate-50/60 py-6 px-3 sm:px-6 lg:px-8 text-slate-800">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- ========================================== -->
        <!-- MODE 1: STEP-BY-STEP INTAKE FLOW -->
        <!-- ========================================== -->
        <div x-show="viewMode === 'intake'" x-transition.opacity class="space-y-6">

            <!-- Past Drafts Picker Bar (Multi-Draft Selector) -->
            <div x-show="savedDraftsList.length > 0 && !draftRestored" x-transition.opacity
                class="bg-blue-50/90 border border-blue-200 rounded-xl p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div
                        class="w-9 h-9 rounded-lg bg-blue-100 text-[#2271b1] flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-clock-rotate-left text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="text-xs font-bold text-slate-900">
                                {{ __('Resume Past Proposal Draft') }}
                            </p>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-200/70 text-blue-800"
                                x-text="savedDraftsList.length + ' available'"></span>
                        </div>
                        <p class="text-[11px] text-slate-600 truncate max-w-lg"
                            x-text="'Latest: ' + (savedDraftsList[0]?.title || 'Untitled Proposal')"></p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" @click="loadSpecificDraft(savedDraftsList[0].id)"
                        class="px-3 py-1.5 bg-[#2271b1] hover:bg-[#135e96] text-white text-xs font-bold rounded-lg transition-colors shadow-2xs flex items-center gap-1.5">
                        <i class="fa-solid fa-arrow-rotate-right text-[11px]"></i>
                        <span>{{ __('Resume Latest') }}</span>
                    </button>
                    <button type="button" @click="showDraftsModal = true"
                        class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold rounded-lg border border-slate-300 transition-colors shadow-2xs flex items-center gap-1.5">
                        <i class="fa-solid fa-list-check text-[11px] text-[#2271b1]"></i>
                        <span>{{ __('Browse All Drafts') }}</span>
                    </button>
                </div>
            </div>

            <!-- Top Header & Academic Status Bar -->
            <header
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200">
                <div>
                    <div class="flex items-center gap-2 text-xs font-bold text-[#2271b1] uppercase tracking-wider">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>{{ __('Research Studio') }} &bull; {{ __('Proposal Builder') }}</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1">
                        {{ __('Draft Research Proposal') }}
                    </h1>
                </div>

                <div class="flex items-center gap-2.5">
                    <span x-show="draftSavedMessage" x-transition.opacity
                        class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-200">
                        <i class="fa-solid fa-check mr-1"></i> <span x-text="draftSavedMessage"></span>
                    </span>

                    <button type="button" @click="showDraftsModal = true"
                        class="px-3 py-1.5 text-xs font-bold text-slate-700 hover:text-slate-900 bg-white hover:bg-slate-50 rounded-lg border border-slate-200 transition-all shadow-2xs flex items-center gap-1.5"
                        title="{{ __('View and resume previous study drafts') }}">
                        <i class="fa-solid fa-folder-open text-[#2271b1] text-xs"></i>
                        <span>{{ __('Past Drafts') }}</span>
                        <span x-show="savedDraftsList.length > 0"
                            class="px-1.5 py-0.2 rounded-full text-[10px] font-extrabold bg-blue-100 text-[#2271b1]"
                            x-text="savedDraftsList.length"></span>
                    </button>

                    <button type="button" @click="resetDraft()"
                        class="px-3 py-1.5 text-xs font-medium text-slate-600 hover:text-rose-600 hover:bg-rose-50 rounded-lg border border-slate-200 transition-colors"
                        title="{{ __('Clear draft and start over') }}">
                        <i class="fa-solid fa-rotate-left mr-1 text-[10px]"></i> {{ __('Reset') }}
                    </button>
                </div>
            </header>

            <!-- Mobile Compact Stepper Bar (<1024px) -->
            <div class="block lg:hidden w-full bg-white rounded-xl border border-slate-200 p-3 shadow-xs">
                <button type="button" @click="mobileStepperOpen = !mobileStepperOpen"
                    class="w-full flex items-center justify-between text-xs font-bold text-slate-800">
                    <div class="flex items-center gap-2">
                        <span
                            class="w-6 h-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[11px]"
                            x-text="currentStep"></span>
                        <span x-text="'Step ' + currentStep + ' of 5: ' + steps[currentStep - 1].title"></span>
                    </div>
                    <i class="fa-solid text-slate-400 transition-transform"
                        :class="mobileStepperOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>

                <!-- Mobile Stepper Drawer Dropdown -->
                <div x-show="mobileStepperOpen" x-transition.opacity
                    class="mt-3 pt-3 border-t border-slate-100 space-y-2">
                    <template x-for="(stepItem, index) in steps" :key="index">
                        <button type="button" @click="goToStep(index + 1); mobileStepperOpen = false;"
                            :disabled="index + 1 > maxReachedStep"
                            class="w-full flex items-center justify-between p-2.5 rounded-lg text-left text-xs font-semibold border transition-all"
                            :class="currentStep === index + 1 ? 'bg-slate-900 text-white border-slate-900' : (index + 1 <= maxReachedStep ? 'bg-slate-50 text-slate-800 border-slate-200' : 'opacity-40 text-slate-400 border-transparent')">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px]"
                                    :class="currentStep === index + 1 ? 'bg-white text-slate-900 font-bold' : 'bg-slate-200 text-slate-700'">
                                    <span x-text="index + 1"></span>
                                </span>
                                <span x-text="stepItem.title"></span>
                            </div>
                            <span x-show="form.previews[index + 1]"
                                class="text-[10px] text-emerald-400 font-bold">✓</span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Responsive Layout (Desktop Sidebar + 100% Fluid Main Intake) -->
            <div class="flex flex-col lg:flex-row gap-6 w-full items-start">

                <!-- Left Column: Steps Sidebar (Visible on Desktop >= 1024px) -->
                <aside class="hidden lg:block w-72 min-w-[280px] max-w-[280px] shrink-0 sticky top-6">
                    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                        <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
                            <span
                                class="text-xs font-bold text-slate-800 uppercase tracking-wider">{{ __('Proposal Flow') }}</span>
                            <span class="text-[11px] font-semibold text-slate-500"
                                x-text="'Step ' + currentStep + ' of 5'"></span>
                        </div>

                        <!-- Vertical Stepper List -->
                        <nav aria-label="Progress">
                            <ol role="list" class="space-y-2.5">
                                <template x-for="(stepItem, index) in steps" :key="index">
                                    <li>
                                        <button type="button" @click="goToStep(index + 1)"
                                            :disabled="index + 1 > maxReachedStep"
                                            class="w-full flex items-start gap-3 p-3 rounded-lg text-left transition-all relative border"
                                            :class="{
                                                'bg-slate-900 border-slate-900 text-white shadow-sm': currentStep === index + 1,
                                                'bg-slate-50 border-slate-200 text-slate-800 hover:bg-slate-100 hover:border-slate-300': currentStep !== index + 1 && index + 1 <= maxReachedStep,
                                                'opacity-40 border-transparent cursor-not-allowed text-slate-400': index + 1 > maxReachedStep
                                            }">
                                            <span
                                                class="w-6 h-6 flex items-center justify-center rounded text-xs font-bold shrink-0 mt-0.5"
                                                :class="{
                                                    'bg-white text-slate-900': currentStep === index + 1,
                                                    'bg-emerald-600 text-white': currentStep > index + 1,
                                                    'bg-slate-200 text-slate-700': currentStep < index + 1 && index + 1 <= maxReachedStep,
                                                    'bg-slate-100 text-slate-400': index + 1 > maxReachedStep
                                            }">
                                                <template x-if="currentStep > index + 1">
                                                    <i class="fa-solid fa-check text-[10px]"></i>
                                                </template>
                                                <template x-if="currentStep <= index + 1">
                                                    <span x-text="index + 1"></span>
                                                </template>
                                            </span>

                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between">
                                                    <p class="text-[10px] font-bold uppercase tracking-wider opacity-75"
                                                        x-text="'Step ' + (index + 1)"></p>
                                                    <span x-show="currentStep === index + 1"
                                                        class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-200 uppercase tracking-widest">{{ __('Active') }}</span>
                                                    <span x-show="currentStep !== index + 1 && form.previews[index + 1]"
                                                        class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 uppercase tracking-widest">{{ __('Approved') }}</span>
                                                </div>
                                                <p class="text-xs font-semibold truncate mt-0.5"
                                                    x-text="stepItem.title"></p>
                                                <p class="text-[11px] opacity-70 truncate mt-0.5"
                                                    x-text="stepItem.desc"></p>
                                            </div>
                                        </button>
                                    </li>
                                </template>
                            </ol>
                        </nav>

                        <!-- Fast Status Card -->
                        <div class="mt-4 pt-3 border-t border-slate-100 space-y-2 text-[11px] text-slate-500">
                            <div class="flex items-center justify-between">
                                <span>{{ __('Citation Standard:') }}</span>
                                <span class="font-bold text-slate-700" x-text="form.style.toUpperCase()"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>{{ __('Study Design:') }}</span>
                                <span class="font-bold text-slate-700 capitalize"
                                    x-text="getStudyGoalLabel(form.study_goal)"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>{{ __('Estimated Sample:') }}</span>
                                <span class="font-bold text-slate-700" x-text="'n = ' + form.sample_size"></span>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Right Column: Interactive Questions Form (100% Full Width on Mobile) -->
                <main class="flex-1 min-w-0 w-full">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8">

                        <!-- STEP 1: Topic, Research Model & Construct Registry -->
                        <div x-show="currentStep === 1" x-transition.opacity class="space-y-6">
                            <div
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-100">
                                <div>
                                    <h2 class="text-lg font-bold text-slate-900">
                                        {{ __('1. Research Topic & Core Model Intake') }}
                                    </h2>
                                    <p class="text-xs text-slate-500 mt-1">
                                        {{ __('Define your title, study problem, and lock in your core variables. Clicking "Next: Review Chapter 1" will generate Chapter 1 with guaranteed construct consistency.') }}
                                    </p>
                                </div>

                            </div>

                            <div class="space-y-5">
                                <div>
                                    <label for="title" class="block text-xs font-bold text-slate-700 mb-1.5">
                                        {{ __('Working Title / Research Topic') }} <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" id="title" x-model="form.title" required
                                        @input.debounce.750ms="if (form.title.trim().length > 10) fetchVariableSuggestions()"
                                        @change="if (form.title.trim().length > 5) fetchVariableSuggestions()"
                                        class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm font-semibold text-slate-900 placeholder-slate-400 focus:outline-none focus:border-[#2271b1] focus:ring-1 focus:ring-[#2271b1]">
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="domain" class="block text-xs font-bold text-slate-700 mb-1.5">
                                            {{ __('Academic Discipline / Domain') }}
                                        </label>
                                        <select id="domain" x-model="form.domain"
                                            class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs font-medium text-slate-900 focus:outline-none focus:border-[#2271b1] focus:ring-1 focus:ring-[#2271b1]">
                                            <option value="Business Administration & Economics">Business Administration
                                                & Economics</option>
                                            <option value="Social Sciences & Development">Social Sciences & Development
                                            </option>
                                            <option value="Environmental Studies & Waste Management">Environmental
                                                Studies & Waste Management</option>
                                            <option value="Information Technology & Computer Science">Information
                                                Technology & Computer Science</option>
                                            <option value="Public Health & Nursing">Public Health & Nursing</option>
                                            <option value="Education & Pedagogy">Education & Pedagogy</option>
                                            <option value="Law & Governance">Law & Governance</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="target_location"
                                            class="block text-xs font-bold text-slate-700 mb-1.5">
                                            {{ __('Study Location / Target Geography') }}
                                        </label>
                                        <input type="text" id="target_location" x-model="form.target_location"
                                            class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs font-medium text-slate-900 placeholder-slate-400 focus:outline-none focus:border-[#2271b1] focus:ring-1 focus:ring-[#2271b1]">
                                    </div>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label for="problem_statement" class="block text-xs font-bold text-slate-700">
                                            {{ __('Describe the Everyday Problem') }} <span
                                                class="text-rose-500">*</span>
                                        </label>
                                    </div>
                                    <textarea id="problem_statement" x-model="form.problem_statement" rows="4" required
                                        class="w-full bg-white border border-slate-300 rounded-lg p-3.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-[#2271b1] focus:ring-1 focus:ring-[#2271b1] leading-relaxed"
                                        placeholder="{{ __('Explain what is going wrong, who is suffering and why this needs solving. For example: Informal retailers face extreme cash flow volatility and revenue shocks. Despite widespread smartphone ownership, digital marketing adoption remains fragmented, creating severe business vulnerability.') }}"></textarea>
                                </div>


                                <!-- Core Model Variables & Registry Lock in Step 1 -->
                                <div class="pt-3 border-t border-slate-200 space-y-4">
                                    <div>
                                        <div class="flex items-center justify-between gap-3 mb-1">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-800">
                                                    {{ __('Independent Variables (Predictors)') }} <span
                                                        class="text-rose-500">*</span>
                                                </label>
                                                <p class="text-[11px] text-slate-500">
                                                    {{ __('Select or type the core independent constructs.') }}
                                                </p>
                                            </div>
                                            <button type="button" @click="fetchVariableSuggestions()"
                                                :disabled="isLoadingSuggestions || !form.title"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-semibold rounded-lg border border-slate-300 transition-colors shrink-0 shadow-2xs disabled:opacity-50"
                                                title="{{ __('Automatically deconstruct study title into operational variables') }}">
                                                <i class="fa-solid fa-wand-magic-sparkles text-amber-500 text-[11px]"
                                                    :class="{ 'fa-spin': isLoadingSuggestions }"></i>
                                                <span>{{ __('Suggest Model Variables') }}</span>
                                            </button>
                                        </div>

                                        <div class="bg-slate-50/80 border border-slate-200 rounded-lg p-3 space-y-3">
                                            <div class="flex flex-wrap gap-2">
                                                <template x-for="item in suggestedIndependent" :key="'indep_' + item">
                                                    <button type="button" @click="toggleVariable('independent', item)"
                                                        class="px-3 py-1.5 rounded-md text-xs font-medium border transition-all flex items-center gap-1.5 shadow-2xs"
                                                        :class="form.independent_variables.includes(item) ?
                                                            'bg-[#1d2327] text-white border-[#0f172a]' :
                                                            'bg-white text-slate-700 border-slate-300 hover:bg-slate-100'">
                                                        <i class="fa-solid text-[10px]"
                                                            :class="form.independent_variables.includes(item) ? 'fa-check text-emerald-400' : 'fa-plus text-slate-400'"></i>
                                                        <span x-text="item"></span>
                                                    </button>
                                                </template>
                                            </div>

                                            <div class="flex items-center gap-2 pt-1">
                                                <input type="text" x-model="customIndependentInput"
                                                    @keydown.enter.prevent="addCustomVariable('independent')"
                                                    class="flex-1 bg-white border border-slate-300 rounded-md px-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#2271b1]"
                                                    placeholder="{{ __('Add custom independent variable (e.g., E-Commerce Adoption)...') }}">
                                                <button type="button" @click="addCustomVariable('independent')"
                                                    class="px-4 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-semibold rounded-md transition-colors shrink-0 shadow-2xs">
                                                    {{ __('Add') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-800 mb-0.5">
                                            {{ __('Dependent Variable(s)') }} <span class="text-rose-500">*</span>
                                        </label>
                                        <p class="text-[11px] text-slate-500 mb-2">
                                            {{ __('Select or type the primary dependent outcome construct.') }}
                                        </p>

                                        <div class="bg-slate-50/80 border border-slate-200 rounded-lg p-3 space-y-3">
                                            <div class="flex flex-wrap gap-2">
                                                <template x-for="item in suggestedDependent" :key="'dep_' + item">
                                                    <button type="button" @click="toggleVariable('dependent', item)"
                                                        class="px-3 py-1.5 rounded-md text-xs font-medium border transition-all flex items-center gap-1.5 shadow-2xs"
                                                        :class="form.dependent_variables.includes(item) ?
                                                            'bg-[#1d2327] text-white border-[#0f172a]' :
                                                            'bg-white text-slate-700 border-slate-300 hover:bg-slate-100'">
                                                        <i class="fa-solid text-[10px]"
                                                            :class="form.dependent_variables.includes(item) ? 'fa-check text-emerald-400' : 'fa-plus text-slate-400'"></i>
                                                        <span x-text="item"></span>
                                                    </button>
                                                </template>
                                            </div>

                                            <div class="flex items-center gap-2 pt-1">
                                                <input type="text" x-model="customDependentInput"
                                                    @keydown.enter.prevent="addCustomVariable('dependent')"
                                                    class="flex-1 bg-white border border-slate-300 rounded-md px-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#2271b1]"
                                                    placeholder="{{ __('Add custom dependent variable (e.g., Financial Resilience)...') }}">
                                                <button type="button" @click="addCustomVariable('dependent')"
                                                    class="px-4 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-semibold rounded-md transition-colors shrink-0 shadow-2xs">
                                                    {{ __('Add') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 2: Literature Review, Theoretical Anchors & Conceptual Paths -->
                        <div x-show="currentStep === 2" x-transition.opacity class="space-y-6">
                            <div
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-100">
                                <div>
                                    <h2 class="text-lg font-bold text-slate-900">
                                        {{ __('2. Theoretical Framework & Literature Review') }}
                                    </h2>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        {{ __('Select the theoretical anchors that underpin your conceptual model. Chapter 2 will synthesize empirical literature and construct the formal Mermaid diagram.') }}
                                    </p>
                                </div>
                                <button type="button" @click="fetchVariableSuggestions()"
                                    :disabled="isLoadingSuggestions"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg border border-slate-300 transition-colors shrink-0 shadow-2xs">
                                    <i class="fa-solid fa-wand-magic-sparkles text-amber-500 text-[10px]"
                                        :class="{ 'fa-spin': isLoadingSuggestions }"></i>
                                    <span>{{ __('Refresh Theoretical Suggestions') }}</span>
                                </button>
                            </div>

                            <div class="space-y-5">
                                <!-- Theoretical Framework Anchors -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-800 mb-0.5">
                                        {{ __('Theoretical Framework Anchors') }} <span class="text-rose-500">*</span>
                                    </label>
                                    <p class="text-[11px] text-slate-500 mb-2">
                                        {{ __('Select verified seminal theories (e.g., TAM, RBV, Institutional Theory) to anchor Chapter 2.') }}
                                    </p>

                                    <div class="bg-slate-50/70 border border-slate-200 rounded-lg p-3 space-y-3">
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="theory in suggestedTheories" :key="'theory_' + theory">
                                                <button type="button" @click="toggleTheory(theory)"
                                                    class="px-3 py-1.5 rounded-md text-xs font-medium border transition-all flex items-center gap-1.5 shadow-2xs"
                                                    :class="form.theories.includes(theory) ?
                                                        'bg-[#1d2327] text-white border-[#0f172a]' :
                                                        'bg-white text-slate-700 border-slate-300 hover:bg-slate-100'">
                                                    <i class="fa-solid text-[10px]"
                                                        :class="form.theories.includes(theory) ? 'fa-check text-emerald-400' : 'fa-plus text-slate-400'"></i>
                                                    <span x-text="theory"></span>
                                                </button>
                                            </template>
                                        </div>

                                        <div class="flex items-center gap-2 pt-1">
                                            <input type="text" x-model="customTheoryInput"
                                                @keydown.enter.prevent="addCustomTheory()"
                                                class="flex-1 bg-white border border-slate-300 rounded-md px-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#2271b1]"
                                                placeholder="{{ __('Add custom theory (e.g., Technology Acceptance Model)...') }}">
                                            <button type="button" @click="addCustomTheory()"
                                                class="px-4 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-semibold rounded-md transition-colors shrink-0 shadow-2xs">
                                                {{ __('Add') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dynamic Live Objectives Synthesizer Card -->
                                <div class="bg-slate-100 border border-slate-200 rounded-lg p-4">
                                    <div
                                        class="flex items-center justify-between pb-2.5 border-b border-slate-200 mb-3">
                                        <span
                                            class="text-[11px] font-bold tracking-wider text-slate-800 uppercase">{{ __('Synthesized Study Objectives') }}</span>
                                        <span
                                            class="text-[11px] text-slate-500">{{ __('Derived automatically from locked variables') }}</span>
                                    </div>
                                    <div class="space-y-2 text-xs">
                                        <template x-if="computedObjectives.length > 0">
                                            <div class="space-y-1.5">
                                                <template x-for="(obj, idx) in computedObjectives" :key="'obj_' + idx">
                                                    <p class="text-slate-800 font-normal leading-relaxed text-xs">
                                                        <span class="font-bold text-slate-900"
                                                            x-text="(idx + 1) + '.'"></span>
                                                        <span x-text="' ' + obj"></span>
                                                    </p>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="computedObjectives.length === 0">
                                            <p class="text-xs text-slate-400 italic py-1">
                                                {{ __('Select or type independent and dependent variables in Step 1 to see live synthesized objectives.') }}
                                            </p>
                                        </template>
                                    </div>
                                </div>

                                <!-- Academic Citation Standard Dropdown -->
                                <div
                                    class="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-slate-100">
                                    <div>
                                        <label for="style" class="block text-xs font-bold text-slate-800">
                                            {{ __('Academic Citation Standard') }}
                                        </label>
                                        <p class="text-[11px] text-slate-500">
                                            {{ __('Standard formatting for in-text citations and references.') }}
                                        </p>
                                    </div>
                                    <select id="style" x-model="form.style"
                                        class="bg-white border border-slate-300 rounded-lg px-3.5 py-2 text-xs font-medium text-slate-800 focus:outline-none focus:border-[#2271b1] min-w-[200px]">
                                        <option value="apa7">APA 7th Edition</option>
                                        <option value="harvard">Harvard Referencing</option>
                                        <option value="chicago">Chicago Manual of Style</option>
                                        <option value="ieee">IEEE Standard</option>
                                        <option value="mla">MLA 9th Edition</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 3: Methodology & Population Math -->
                        <div x-show="currentStep === 3" x-transition.opacity class="space-y-6">
                            <div
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-100">
                                <div>
                                    <h2 class="text-lg font-bold text-slate-900">
                                        {{ __('3. Research Methodology & Execution Plan') }}
                                    </h2>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        {{ __('Choose your core scientific intention. The backend locks in the statistical models, sample size formulas, and validity controls.') }}
                                    </p>
                                </div>
                                <button type="button" @click="clearCurrentStep(3)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 text-xs font-medium rounded-lg border border-slate-200 transition-colors shrink-0"
                                    title="{{ __('Reset methodology parameters') }}">
                                    <i class="fa-solid fa-eraser text-[10px]"></i>
                                    <span>{{ __('Clear Step') }}</span>
                                </button>
                            </div>

                            <div class="space-y-5">
                                <!-- Primary Research Design -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-800 mb-2">
                                        {{ __('Primary Research Design') }} <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <!-- Card 1: Mixed Methods -->
                                        <label
                                            class="p-3.5 rounded-lg border cursor-pointer transition-all flex flex-col justify-between"
                                            :class="form.methodology_type === 'mixed' ? 'bg-[#0f172a] text-white border-[#0f172a] shadow-xs' : 'bg-slate-50/70 text-slate-700 border-slate-200 hover:bg-slate-100'"
                                            @click="onDesignChange('mixed')">
                                            <div class="flex items-center justify-between mb-1.5">
                                                <span class="text-xs font-bold">{{ __('Mixed Methods') }}</span>
                                                <input type="radio" value="mixed" x-model="form.methodology_type"
                                                    class="text-[#2271b1]">
                                            </div>
                                            <span
                                                class="text-[11px] opacity-80 leading-relaxed">{{ __('Convergent parallel design combining structured surveys and key informant interviews.') }}</span>
                                        </label>

                                        <!-- Card 2: Quantitative Design -->
                                        <label
                                            class="p-3.5 rounded-lg border cursor-pointer transition-all flex flex-col justify-between"
                                            :class="form.methodology_type === 'survey' ? 'bg-[#0f172a] text-white border-[#0f172a] shadow-xs' : 'bg-slate-50/70 text-slate-700 border-slate-200 hover:bg-slate-100'"
                                            @click="onDesignChange('survey')">
                                            <div class="flex items-center justify-between mb-1.5">
                                                <span class="text-xs font-bold">{{ __('Quantitative Design') }}</span>
                                                <input type="radio" value="survey" x-model="form.methodology_type"
                                                    class="text-[#2271b1]">
                                            </div>
                                            <span
                                                class="text-[11px] opacity-80 leading-relaxed">{{ __('Explanatory and descriptive cross-sectional modeling testing variables and frequencies.') }}</span>
                                        </label>

                                        <!-- Card 3: Qualitative Design -->
                                        <label
                                            class="p-3.5 rounded-lg border cursor-pointer transition-all flex flex-col justify-between"
                                            :class="form.methodology_type === 'qualitative' ? 'bg-[#0f172a] text-white border-[#0f172a] shadow-xs' : 'bg-slate-50/70 text-slate-700 border-slate-200 hover:bg-slate-100'"
                                            @click="onDesignChange('qualitative')">
                                            <div class="flex items-center justify-between mb-1.5">
                                                <span class="text-xs font-bold">{{ __('Qualitative Design') }}</span>
                                                <input type="radio" value="qualitative" x-model="form.methodology_type"
                                                    class="text-[#2271b1]">
                                            </div>
                                            <span
                                                class="text-[11px] opacity-80 leading-relaxed">{{ __('Phenomenological or case study approach focusing on thematic analysis and lived experiences.') }}</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Population Unit, Target Population (N), and Locked Sample Size (n) Grid -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                                    <div>
                                        <label for="target_population"
                                            class="block text-xs font-bold text-slate-800 mb-1.5">
                                            {{ __('Target Population Unit') }} <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="text" id="target_population" x-model="form.target_population"
                                            class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs font-medium text-slate-900 placeholder-slate-400 focus:outline-none focus:border-[#2271b1] focus:ring-1 focus:ring-[#2271b1]">
                                    </div>

                                    <div>
                                        <label for="population_size"
                                            class="block text-xs font-bold text-slate-800 mb-1.5">
                                            {{ __('Estimated Population Size (N)') }} <span
                                                class="text-rose-500">*</span>
                                        </label>
                                        <input type="number" id="population_size" x-model.number="form.population_size"
                                            @input="recalculateSampleSize()" min="10"
                                            class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs font-medium text-slate-900 focus:outline-none focus:border-[#2271b1] focus:ring-1 focus:ring-[#2271b1]">
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between mb-1.5">
                                            <label for="sample_size" class="block text-xs font-bold text-slate-800">
                                                {{ __('Calculated Sample Size (n)') }}
                                            </label>
                                            <span
                                                class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Yamane
                                                95% CI</span>
                                        </div>
                                        <div class="relative">
                                            <input type="text" id="sample_size"
                                                :value="form.sample_size + ' respondents'" readonly
                                                class="w-full bg-slate-100 border border-slate-300 rounded-lg px-3 py-2 text-xs font-bold text-slate-900 cursor-not-allowed select-none shadow-2xs">
                                            <i
                                                class="fa-solid fa-lock text-slate-400 text-[11px] absolute right-3 top-2.5"></i>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sampling Strategy & Technique Dropdown -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                    <div>
                                        <label for="sampling_strategy"
                                            class="block text-xs font-bold text-slate-800 mb-1.5">
                                            {{ __('Sampling Technique & Strategy') }} <span
                                                class="text-rose-500">*</span>
                                        </label>
                                        <select id="sampling_strategy" x-model="form.sampling_strategy"
                                            class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs font-medium text-slate-800 focus:outline-none focus:border-[#2271b1] focus:ring-1 focus:ring-[#2271b1]">
                                            <option value="Stratified Random Sampling">Stratified Random Sampling
                                                (Recommended for multi-market / multi-cluster)</option>
                                            <option value="Simple Random Sampling">Simple Random Sampling (Homogeneous
                                                single population)</option>
                                            <option value="Purposive & Expert Sampling">Purposive & Expert Sampling
                                                (Targeted institutional key informants)</option>
                                            <option value="Multi-Stage Cluster Sampling">Multi-Stage Cluster Sampling
                                                (Broad geographical zones / wards)</option>
                                            <option value="Snowball / Chain Referral Sampling">Snowball / Chain Referral
                                                Sampling (Hard-to-reach informal groups)</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="measurement_scale"
                                            class="block text-xs font-bold text-slate-800 mb-1.5">
                                            {{ __('Primary Measurement Scale (Survey Items)') }}
                                        </label>
                                        <select id="measurement_scale" x-model="form.measurement_scale"
                                            class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs font-medium text-slate-800 focus:outline-none focus:border-[#2271b1] focus:ring-1 focus:ring-[#2271b1]">
                                            <option
                                                value="5-point Likert Scale (1 = Strongly Disagree to 5 = Strongly Agree)">
                                                5-Point Likert Scale (1 = Strongly Disagree to 5 = Strongly Agree)
                                            </option>
                                            <option value="7-point Likert Scale (1 = Very Low to 7 = Very High)">7-Point
                                                Likert Scale (1 = Very Low to 7 = Very High)</option>
                                            <option value="Binary & Categorical Nominal Scale">Binary & Categorical
                                                Scale (Yes/No + Ordinal)</option>
                                            <option value="Continuous Ratio & Frequency Metrics">Continuous Ratio &
                                                Frequency Metrics</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Data Collection Instrument Modes (Dynamic Chips + Custom Input) -->
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-bold text-slate-800">
                                            {{ __('Data Collection Instrument Modes') }} <span
                                                class="text-rose-500">*</span>
                                        </label>
                                        <span class="text-[11px] font-medium"
                                            :class="isStep3Valid ? 'text-emerald-600' : 'text-amber-600'">
                                            <template x-if="form.methodology_type === 'mixed'">
                                                <span>{{ __('Requires ≥ 1 Quantitative + ≥ 1 Qualitative instrument') }}</span>
                                            </template>
                                            <template x-if="form.methodology_type !== 'mixed'">
                                                <span>{{ __('Select at least 1 instrument mode') }}</span>
                                            </template>
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mb-2">
                                        {{ __('Select relevant modes or type custom instrument details below') }}
                                    </p>

                                    <div class="bg-slate-50/70 border border-slate-200 rounded-lg p-3 space-y-3">
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="mode in availableInstrumentModes" :key="'mode_' + mode">
                                                <button type="button" @click="toggleInstrumentMode(mode)"
                                                    class="px-3 py-1.5 rounded-md text-xs font-medium border transition-all flex items-center gap-1.5 shadow-2xs"
                                                    :class="form.data_collection_modes.includes(mode) ?
                                                        'bg-[#0f172a] text-white border-[#0f172a]' :
                                                        'bg-white text-slate-700 border-slate-300 hover:bg-slate-100'">
                                                    <i class="fa-solid text-[10px]"
                                                        :class="form.data_collection_modes.includes(mode) ? 'fa-check text-emerald-400' : 'fa-plus text-slate-400'"></i>
                                                    <span x-text="mode"></span>
                                                </button>
                                            </template>
                                        </div>

                                        <!-- Custom Instrument Input -->
                                        <div class="flex items-center gap-2 pt-1">
                                            <input type="text" x-model="customInstrumentInput"
                                                @keydown.enter.prevent="addCustomInstrument()"
                                                class="flex-1 bg-white border border-slate-300 rounded-md px-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#2271b1] focus:ring-1 focus:ring-[#2271b1]"
                                                placeholder="{{ __('Add custom instrument ...') }}">
                                            <button type="button" @click="addCustomInstrument()"
                                                class="px-4 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-semibold rounded-md transition-colors shrink-0 shadow-2xs">
                                                {{ __('Add') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <!-- STEP 4: Budget & Custom Directives -->
                        <div x-show="currentStep === 4" x-transition.opacity class="space-y-6">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">
                                    {{ __('4. Proposed Budget & Supervisor Notes') }}
                                </h2>
                                <p class="text-xs text-slate-500 mt-1">
                                    {{ __('Itemize operational expenses. Clicking "Next: Review Budget" will generate the formatted financial table and 12-month work plan.') }}
                                </p>
                            </div>

                            <div class="space-y-5">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label
                                            class="block text-xs font-bold text-slate-700">{{ __('Budget Items (KES)') }}</label>
                                        <button type="button" @click="addBudgetItem()"
                                            class="text-xs font-bold text-[#2271b1] hover:underline flex items-center gap-1">
                                            <i class="fa-solid fa-plus text-[10px]"></i> {{ __('Add Line Item') }}
                                        </button>
                                    </div>

                                    <div class="space-y-2">
                                        <template x-for="(bItem, bIdx) in form.budget" :key="'budget_' + bIdx">
                                            <div class="flex items-center gap-2">
                                                <input type="text" x-model="bItem.item"
                                                    placeholder="{{ __('e.g., Enumerator Logistics / Statistical Software License') }}"
                                                    class="flex-1 bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs font-medium text-slate-900 focus:outline-none focus:border-[#2271b1]">
                                                <input type="number" x-model.number="bItem.cost"
                                                    placeholder="{{ __('Cost (KES)') }}"
                                                    class="w-36 bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs font-medium text-slate-900 focus:outline-none focus:border-[#2271b1]">
                                                <button type="button" @click="removeBudgetItem(bIdx)"
                                                    class="p-2 text-slate-400 hover:text-rose-600 rounded-lg">
                                                    <i class="fa-solid fa-trash text-xs"></i>
                                                </button>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Live Running Budget Total Bar -->
                                    <div
                                        class="mt-3 p-3 bg-slate-50 border border-slate-200 rounded-lg flex items-center justify-between">
                                        <span
                                            class="text-xs font-bold text-slate-700 uppercase tracking-wider">{{ __('Total Proposed Budget') }}</span>
                                        <span class="text-sm font-black text-slate-900"
                                            x-text="'KES ' + totalBudgetCost.toLocaleString()"></span>
                                    </div>
                                </div>

                                <div>
                                    <label for="custom_instructions"
                                        class="block text-xs font-bold text-slate-700 mb-1.5">
                                        {{ __('Supervisor Guidelines (Optional)') }}
                                    </label>
                                    <textarea id="custom_instructions" x-model="form.custom_instructions" rows="3"
                                        class="w-full bg-white border border-slate-300 rounded-lg p-3.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-[#2271b1]"
                                        placeholder="{{ __('e.g., Organization requires 10% pilot testing; focus heavily on TAM constructs; emphasize local circular economy policy.') }}"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 5: Final Blueprint Review & Full Assembly -->
                        <div x-show="currentStep === 5" x-transition.opacity class="space-y-6">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">
                                    {{ __('5. Master Blueprint Review & Final Document Assembly') }}
                                </h2>
                                <p class="text-xs text-slate-500 mt-1">
                                    {{ __('Review your parameters and all approved chapters. Click "Assemble & Finalize Proposal" to generate the complete ~25-page document with full questionnaires and verified references.') }}
                                </p>
                            </div>

                            <div class="bg-slate-50 rounded-xl border border-slate-200 p-5 space-y-4 text-xs">
                                <div>
                                    <span
                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ __('Study Topic') }}</span>
                                    <p class="font-bold text-slate-900 text-sm mt-0.5" x-text="form.title"></p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <span
                                            class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ __('Independent Variables') }}</span>
                                        <div class="flex flex-wrap gap-1.5 mt-1">
                                            <template x-for="iv in form.independent_variables" :key="'rev_iv_' + iv">
                                                <span
                                                    class="px-2 py-0.5 bg-slate-200 text-slate-800 rounded font-medium text-[11px]"
                                                    x-text="iv"></span>
                                            </template>
                                        </div>
                                    </div>
                                    <div>
                                        <span
                                            class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ __('Dependent Variables') }}</span>
                                        <div class="flex flex-wrap gap-1.5 mt-1">
                                            <template x-for="dv in form.dependent_variables" :key="'rev_dv_' + dv">
                                                <span
                                                    class="px-2 py-0.5 bg-slate-200 text-slate-800 rounded font-medium text-[11px]"
                                                    x-text="dv"></span>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <span
                                            class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ __('Design & Methodology') }}</span>
                                        <p class="font-semibold text-slate-800 mt-0.5"
                                            x-text="form.methodology_type.toUpperCase() + ' (' + form.study_goal + ')'">
                                        </p>
                                    </div>
                                    <div>
                                        <span
                                            class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ __('Target Population & Sample') }}</span>
                                        <p class="font-semibold text-slate-800 mt-0.5"
                                            x-text="'N = ' + form.population_size + ' ➔ n = ' + form.sample_size"></p>
                                    </div>
                                    <div>
                                        <span
                                            class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ __('Citation Standard') }}</span>
                                        <p class="font-semibold text-slate-800 mt-0.5"
                                            x-text="form.style.toUpperCase()"></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Intake Actions Footer -->
                        <div class="flex items-center justify-between pt-6 border-t border-slate-200 mt-6">
                            <button type="button" @click="prevStep()" x-show="currentStep > 1"
                                class="px-4 py-2 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs font-bold rounded-lg transition-colors flex items-center gap-1.5">
                                <i class="fa-solid fa-arrow-left text-[10px]"></i> {{ __('Previous Step') }}
                            </button>
                            <div x-show="currentStep === 1"></div>

                            <div class="flex items-center gap-3">
                                <!-- Next & Regenerate Split Buttons -->
                                <div class="flex items-center gap-2" x-show="currentStep < 5">
                                    <!-- Skip Budget Step Button (Step 4 only) -->
                                    <button type="button" @click="skipBudgetStep()" x-show="currentStep === 4"
                                        class="px-3.5 py-2.5 bg-white hover:bg-slate-100 text-slate-600 text-xs font-semibold rounded-lg border border-slate-300 transition-colors flex items-center gap-1 shadow-2xs"
                                        title="{{ __('Skip budget & work plan and advance directly to final assembly') }}">
                                        <span>{{ __('Skip Step') }}</span>
                                        <i class="fa-solid fa-forward-step text-[10px] text-slate-400"></i>
                                    </button>

                                    <!-- Regenerate Button (Visible when preview exists for this step) -->
                                    <button type="button" @click="proceedToStudio(currentStep, true)"
                                        x-show="form.previews[currentStep] && !loadingStagePreview[currentStep]"
                                        :disabled="loadingStagePreview[currentStep] || (currentStep === 3 && !isStep3Valid)"
                                        class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg border border-slate-300 transition-colors flex items-center gap-1.5 shadow-2xs"
                                        title="{{ __('Draft fresh content from backend without using cache') }}">
                                        <i class="fa-solid fa-rotate text-[11px] text-amber-600"></i>
                                        <span>{{ __('Regenerate') }}</span>
                                    </button>

                                    <!-- Primary Next Button (Fast review cached OR draft fresh if none) -->
                                    <button type="button" @click="proceedToStudio(currentStep, false)"
                                        :disabled="loadingStagePreview[currentStep] || (currentStep === 1 && !isStep1Valid) || (currentStep === 3 && !isStep3Valid)"
                                        class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-lg transition-colors flex items-center gap-2 shadow-sm disabled:opacity-50">
                                        <template x-if="loadingStagePreview[currentStep]">
                                            <span class="flex items-center gap-1.5">
                                                <i class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                                                <span>{{ __('Drafting Chapter...') }}</span>
                                            </span>
                                        </template>
                                        <template x-if="!loadingStagePreview[currentStep]">
                                            <span class="flex items-center gap-1.5">
                                                <span x-text="getNextButtonLabel()"></span>
                                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                            </span>
                                        </template>
                                    </button>
                                </div>

                                <button type="button" @click="openCombinedStudio()" x-show="currentStep === 5"
                                    class="px-6 py-2.5 bg-[#2271b1] hover:bg-[#135e96] text-white text-xs font-bold rounded-lg transition-all flex items-center gap-2 shadow-md">
                                    <i class="fa-solid fa-book-open mr-1"></i>
                                    <span>{{ __('Assemble & Review Full Proposal') }}</span>
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </main>

            </div>

        </div>

        <!-- ========================================== -->
        <!-- PAST DRAFTS BROWSER MODAL (MULTI-DRAFT SELECTOR) -->
        <!-- ========================================== -->
        <div x-show="showDraftsModal" x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" style="display: none;">
            <div @click.away="showDraftsModal = false"
                class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-2xl w-full max-h-[85vh] flex flex-col overflow-hidden">
                <!-- Modal Header -->
                <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2.5">
                        <div
                            class="w-8 h-8 rounded-lg bg-blue-100 text-[#2271b1] flex items-center justify-center text-sm">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">{{ __('Past Proposal Drafts') }}</h3>
                            <p class="text-[11px] text-slate-500">
                                {{ __('Select a previous study draft to resume editing or start a fresh proposal.') }}
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="showDraftsModal = false"
                        class="text-slate-400 hover:text-slate-600 p-1">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <!-- Drafts List -->
                <div class="p-5 overflow-y-auto space-y-3 flex-1 custom-scrollbar">
                    <template x-if="savedDraftsList.length === 0">
                        <div class="text-center py-10 space-y-2">
                            <i class="fa-solid fa-folder-open text-3xl text-slate-300"></i>
                            <p class="text-xs font-bold text-slate-700">{{ __('No Saved Drafts Found') }}</p>
                            <p class="text-[11px] text-slate-500 max-w-xs mx-auto">
                                {{ __('As you work on research proposals, your drafts will be automatically preserved here.') }}
                            </p>
                        </div>
                    </template>

                    <template x-for="(draft, idx) in savedDraftsList" :key="draft.id">
                        <div
                            class="p-4 rounded-xl border border-slate-200 hover:border-[#2271b1]/50 hover:shadow-xs transition-all bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="space-y-1.5 min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span
                                        class="text-[10px] font-bold tracking-wider px-2 py-0.5 rounded bg-slate-100 text-slate-700"
                                        x-text="draft.domain || 'Academic Research'"></span>
                                    <span class="text-[10px] text-slate-400 font-medium"
                                        x-text="draft.formattedDate"></span>

                                </div>
                                <h4 class="text-xs sm:text-sm font-bold text-slate-900 leading-snug line-clamp-1"
                                    x-text="draft.title || 'Untitled Proposal'"></h4>
                                <p class="text-[11px] text-slate-500 line-clamp-1"
                                    x-text="draft.problem_statement || (draft.independent_variables?.length ? draft.independent_variables.join(', ') : 'No problem statement recorded')">
                                </p>
                            </div>

                            <div
                                class="flex items-center gap-2 shrink-0 justify-end pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                                <button type="button" @click="loadSpecificDraft(draft.id)"
                                    class="px-3 py-1.5 bg-[#2271b1] hover:bg-[#135e96] text-white text-xs font-bold rounded-lg transition-colors flex items-center gap-1.5 shadow-2xs">
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                    <span>{{ __('Continue') }}</span>
                                </button>
                                <button type="button" @click="deleteSpecificDraft(draft.id)"
                                    class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                    title="{{ __('Delete this draft') }}">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer: Start Fresh Option -->
                <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between gap-3">
                    <button type="button" @click="clearAllDrafts()" x-show="savedDraftsList.length > 0"
                        class="text-[11px] text-rose-600 hover:text-rose-700 font-semibold transition-colors">
                        {{ __('Clear All Drafts') }}
                    </button>
                    <div class="flex items-center gap-2 ml-auto">
                        <button type="button" @click="startFreshBlankProposal()"
                            class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-1.5">
                            <i class="fa-solid fa-plus text-[10px]"></i>
                            <span>{{ __('Start Fresh Blank Proposal') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save to Library Modal with Custom Title Input -->
        <div x-show="showSaveModal" x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" style="display: none;">
            <div @click.away="showSaveModal = false"
                class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full p-6 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <div
                            class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-folder-plus"></i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900">{{ __('Save Proposal to Library') }}</h3>
                    </div>
                    <button type="button" @click="showSaveModal = false" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-700">
                        {{ __('Proposal Title in Library') }} <span class="text-rose-500">*</span>
                    </label>
                    <p class="text-[11px] text-slate-500">
                        {{ __('Name your proposal so you can easily organize and reference it in your personal research library.') }}
                    </p>
                    <input type="text" x-model="saveProposalTitle"
                        class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs font-semibold text-slate-900 focus:outline-none focus:border-[#2271b1] focus:ring-1 focus:ring-[#2271b1]">
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="showSaveModal = false"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition-colors">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" @click="confirmSaveToLibrary()"
                        :disabled="isSavingToLibrary || !saveProposalTitle.trim()"
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-2 disabled:opacity-50">
                        <i class="fa-solid fa-floppy-disk text-xs" :class="{ 'fa-spin': isSavingToLibrary }"></i>
                        <span>{{ __('Save & Commit to Library') }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODE 2: IN-BETWEEN FULL-SCREEN CHAPTER REVIEW & AI REFINEMENT STUDIO -->
        <!-- ========================================== -->
        <div x-show="viewMode === 'studio'" x-transition.opacity class="space-y-4 pb-12">

            <!-- Top Studio Header Bar (Solid Docked Sticky Toolbar on Mobile & Desktop) -->
            <div
                class="sticky top-0 z-50 bg-white border-b border-slate-400 shadow-sm p-3 sm:px-6 sm:py-3.5 -mx-3 sm:-mx-6 lg:-mx-8 transition-all">
                <!-- Row 1: Back + Chapter Title/Meta + Action Buttons -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                    <!-- Left: Back Button & Chapter Title/Word Count -->
                    <div class="flex items-center gap-2.5 min-w-0">
                        <button type="button" @click="backToIntake()"
                            class="shrink-0 px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition-colors flex items-center gap-1.5 shadow-2xs">
                            <i class="fa-solid fa-arrow-left text-[10px]"></i>
                            <span>{{ __('Back to Inputs') }}</span>
                        </button>
                        <div class="h-4 w-px bg-slate-200 shrink-0"></div>
                        <div class="min-w-0">
                            <h2 class="text-xs sm:text-sm font-bold text-slate-900 leading-tight truncate"
                                x-text="getStudioChapterTitle()"></h2>
                            <div class="flex items-center gap-1.5 text-[10px] text-slate-500 font-medium mt-0.5">
                                <span
                                    class="font-bold text-[#2271b1] uppercase tracking-wider">{{ __('Review Studio') }}</span>
                                <span>•</span>
                                <span><strong class="text-slate-700" x-text="getActiveWordCount()"></strong>
                                    {{ __('words') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Action Buttons (Compact & responsive on mobile) -->
                    <div class="flex items-center gap-1.5 sm:gap-2 shrink-0 justify-end">
                        <!-- Revise Chapter Button -->
                        <button type="button" @click="mobileAiOpen = true"
                            class="px-2.5 sm:px-3 py-1 sm:py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-1 sm:gap-1.5"
                            title="{{ __('Open Chapter Revision Assistant') }}">
                            <i class="fa-solid fa-wand-magic-sparkles text-amber-400 text-[10px] sm:text-[11px]"></i>
                            <span class="hidden sm:inline">{{ __('Revise Chapter') }}</span>
                            <span class="sm:hidden">{{ __('Revise') }}</span>
                        </button>

                        <!-- Single Chapter Actions -->
                        <template x-if="typeof activeStudioStage === 'number'">
                            <div class="flex items-center gap-1.5 sm:gap-2">
                                <button type="button" @click="regenerateActiveStudioChapter()"
                                    :disabled="loadingStagePreview[activeStudioStage]"
                                    class="px-2 sm:px-3 py-1 sm:py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-300 transition-colors flex items-center gap-1 sm:gap-1.5 shadow-2xs"
                                    title="{{ __('Re-generate this chapter with fresh API call') }}">
                                    <i class="fa-solid fa-rotate text-[10px] sm:text-[11px] text-amber-600"
                                        :class="{ 'fa-spin': loadingStagePreview[activeStudioStage] }"></i>
                                    <span class="hidden sm:inline">{{ __('Regenerate') }}</span>
                                </button>

                                <button type="button" @click="approveCurrentStage()"
                                    class="px-2.5 sm:px-4 py-1 sm:py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-1 sm:gap-1.5">
                                    <i class="fa-solid fa-check text-xs"></i>
                                    <span class="hidden sm:inline" x-text="getApproveButtonLabel()"></span>
                                    <span class="sm:hidden">{{ __('Approve') }}</span>
                                </button>
                            </div>
                        </template>

                        <!-- Combined Full Document Actions -->
                        <template x-if="activeStudioStage === 'combined'">
                            <div class="flex items-center gap-1.5 sm:gap-2">
                                <button type="button" @click="openSaveModal()" :disabled="isSavingToLibrary"
                                    class="px-2.5 sm:px-3 py-1 sm:py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-1.5">
                                    <i class="fa-solid fa-floppy-disk text-xs"
                                        :class="{ 'fa-spin': isSavingToLibrary }"></i>
                                    <span class="hidden sm:inline"
                                        x-text="isSavedToLibrary ? 'Saved ✓' : 'Save to Library'"></span>
                                    <span class="sm:hidden">{{ __('Save') }}</span>
                                </button>

                                <button type="button" @click="downloadDocxFromWizard()" :disabled="isExportingDocx"
                                    class="px-2.5 sm:px-3.5 py-1 sm:py-1.5 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-1.5">
                                    <i class="fa-solid fa-file-word text-xs"
                                        :class="{ 'fa-spin': isExportingDocx }"></i>
                                    <span class="hidden sm:inline">{{ __('Export (.docx)') }}</span>
                                    <span class="sm:hidden">{{ __('Export') }}</span>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Row 2: Navigation Tabs across Approved Chapters (Comfortable spacing & horizontal scroll) -->
                <div class="flex items-center gap-1.5 pt-2.5 border-t border-slate-100 overflow-x-auto no-scrollbar">
                    <template x-for="st in [1, 2, 3, 4]" :key="'tab_' + st">
                        <button type="button" @click="switchStudioTab(st)" :disabled="!form.previews[st]"
                            class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all whitespace-nowrap flex items-center gap-1.5 border"
                            :class="activeStudioStage === st ?
                                'bg-slate-900 text-white border-slate-900 shadow-xs' :
                                (form.previews[st] ? 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' : 'bg-slate-50 text-slate-300 border-slate-100 opacity-50 cursor-not-allowed')">
                            <span x-text="getTabLabel(st)"></span>
                            <span x-show="form.previews[st]" class="text-emerald-400 text-[10px] font-bold">✓</span>
                        </button>
                    </template>
                    <button type="button" @click="switchStudioTab('combined')"
                        class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all whitespace-nowrap border"
                        :class="activeStudioStage === 'combined' ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'">
                        {{ __('Full Document') }}
                    </button>
                </div>
            </div>

            <!-- Responsive Mobile & Desktop Layout (Document Editor + Sticky AI Copilot) -->
            <div class="flex flex-col lg:flex-row gap-6 w-full items-start">

                <!-- Left Column: Live Directly Editable Academic Document -->
                <div class="flex-1 min-w-0 w-full">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-8 lg:p-10 space-y-6">

                        <!-- Loading State while AI is drafting chapter -->
                        <div x-show="loadingStagePreview[activeStudioStage]" x-transition.opacity
                            class="py-16 flex flex-col items-center justify-center text-center space-y-4">
                            <div
                                class="w-12 h-12 rounded-2xl bg-blue-50 text-[#2271b1] flex items-center justify-center shadow-xs">
                                <i class="fa-solid fa-circle-notch fa-spin text-xl"></i>
                            </div>
                            <div class="space-y-1">
                                <h3 class="text-sm font-bold text-slate-900">{{ __('Drafting Chapter Content...') }}
                                </h3>
                                <p class="text-xs text-slate-500 max-w-sm">
                                    {{ __('Synthesizing academic narrative, theoretical frameworks, and alignment matrix. This takes 5–10 seconds.') }}
                                </p>
                            </div>
                            <div class="w-48 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-[#2271b1] rounded-full animate-pulse w-2/3"></div>
                            </div>
                        </div>

                        <!-- Directly Editable Formatted Academic Document (Shown when ready) -->
                        <div x-show="!loadingStagePreview[activeStudioStage]"
                            class="prose prose-slate prose-lg max-w-none text-slate-800 leading-relaxed text-justify">
                            <div contenteditable="true"
                                class="focus:outline-none focus:ring-1 focus:ring-[#2271b1] focus:ring-offset-2 rounded-lg p-2 transition-all"
                                @blur="handleDirectEdit($event)" x-html="renderMarkdown(getActiveMarkdown())"></div>
                        </div>

                        <!-- Bottom Chapter Action Bar (Context-Aware for Chapters vs Full Document) -->
                        <div
                            class="pt-6 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <button type="button" @click="backToIntake()"
                                class="w-full sm:w-auto px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition-colors flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-arrow-left text-[10px]"></i> {{ __('Back to Inputs') }}
                            </button>

                            <!-- Single Chapter Stage Actions (1-4) -->
                            <template x-if="typeof activeStudioStage === 'number'">
                                <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto justify-end">
                                    <button type="button" @click="regenerateActiveStudioChapter()"
                                        :disabled="loadingStagePreview[activeStudioStage]"
                                        class="flex-1 sm:flex-initial px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-300 transition-colors flex items-center justify-center gap-1.5 shadow-2xs">
                                        <i class="fa-solid fa-rotate text-[11px] text-amber-600"
                                            :class="{ 'fa-spin': loadingStagePreview[activeStudioStage] }"></i>
                                        <span>{{ __('Regenerate Chapter') }}</span>
                                    </button>

                                    <button type="button" @click="approveCurrentStage()"
                                        class="flex-1 sm:flex-initial px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center justify-center gap-2">
                                        <i class="fa-solid fa-check text-xs"></i>
                                        <span x-text="getApproveButtonLabel()"></span>
                                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                    </button>
                                </div>
                            </template>

                            <!-- Full Combined Document Stage Actions -->
                            <template x-if="activeStudioStage === 'combined'">
                                <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto justify-end">
                                    <button type="button" @click="openSaveModal()" :disabled="isSavingToLibrary"
                                        class="flex-1 sm:flex-initial px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center justify-center gap-2">
                                        <i class="fa-solid fa-floppy-disk text-xs"
                                            :class="{ 'fa-spin': isSavingToLibrary }"></i>
                                        <span
                                            x-text="isSavedToLibrary ? 'Saved in Library ✓' : 'Save to Library'"></span>
                                    </button>

                                    <button type="button" @click="downloadDocxFromWizard()" :disabled="isExportingDocx"
                                        class="flex-1 sm:flex-initial px-5 py-2.5 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center justify-center gap-2">
                                        <i class="fa-solid fa-file-word text-xs"
                                            :class="{ 'fa-spin': isExportingDocx }"></i>
                                        <span>{{ __('Export Complete Proposal (.docx)') }}</span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Desktop Sidebar AI Assistant (Hidden on Mobile, Docked cleanly below sticky toolbar) -->
                <aside class="hidden lg:block w-80 min-w-[320px] max-w-[320px] shrink-0 sticky top-28">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                <i class="fa-solid fa-wand-magic-sparkles text-amber-500"></i>
                                {{ __('Chapter Revision Assistant') }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-medium">{{ __('Instant revisions') }}</span>
                        </div>

                        <p class="text-xs text-slate-500 leading-relaxed">
                            {{ __('Instruct our system to revise this chapter. It will modify content, formatting, or depth while preserving academic rigor.') }}
                        </p>

                        <!-- Quick Revision Chips -->
                        <div class="space-y-1.5">
                            <span
                                class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ __('Quick Revisions') }}</span>
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button"
                                    @click="applyQuickInstruction('Add more empirical statistics and localized context')"
                                    class="px-2.5 py-1 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-medium transition-colors text-left">
                                    {{ __('+ Add localized statistics') }}
                                </button>
                                <button type="button"
                                    @click="applyQuickInstruction('Convert specific research objectives to formatted bullet points')"
                                    class="px-2.5 py-1 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-medium transition-colors text-left">
                                    {{ __('Format bullet points') }}
                                </button>
                                <button type="button"
                                    @click="applyQuickInstruction('Strengthen APA 7th academic citations and theoretical grounding')"
                                    class="px-2.5 py-1 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-medium transition-colors text-left">
                                    {{ __('Strengthen APA citations') }}
                                </button>
                                <button type="button"
                                    @click="applyQuickInstruction('Make the prose more concise and eliminate repetitive phrases')"
                                    class="px-2.5 py-1 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-medium transition-colors text-left">
                                    {{ __('More concise tone') }}
                                </button>
                            </div>
                        </div>

                        <!-- Natural Language Modification Textarea -->
                        <div class="space-y-2">
                            <label for="aiInstruction" class="block text-[11px] font-bold text-slate-700">
                                {{ __('Custom Instructions / Comments') }}
                            </label>
                            <textarea id="aiInstruction" x-model="aiInstruction" rows="4"
                                class="w-full bg-slate-50 border border-slate-300 rounded-lg p-3 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-[#2271b1] focus:ring-1 focus:ring-[#2271b1]"
                                placeholder="{{ __('e.g., Elaborate the problem statement with more focus on retail cash flow; expand Section 1.1; adjust the wording of Objective 2...') }}"></textarea>

                            <button type="button" @click="refineActiveStageWithAi()"
                                :disabled="isRefiningWithAi || !aiInstruction"
                                class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center justify-center gap-2 disabled:opacity-50">
                                <template x-if="isRefiningWithAi">
                                    <span class="flex items-center gap-2">
                                        <i class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                                        <span>{{ __('Applying Revisions...') }}</span>
                                    </span>
                                </template>
                                <template x-if="!isRefiningWithAi">
                                    <span class="flex items-center gap-1.5">
                                        <i class="fa-solid fa-wand-magic-sparkles text-amber-400"></i>
                                        <span>{{ __('Revise Chapter') }}</span>
                                    </span>
                                </template>
                            </button>
                        </div>
                    </div>
                </aside>



                <!-- Mobile AI Assistant Bottom-Sheet Overlay Modal -->
                <div x-show="mobileAiOpen" x-transition.opacity
                    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-slate-900/60 p-0 sm:p-4"
                    style="display: none;">
                    <div @click.away="mobileAiOpen = false"
                        class="bg-white rounded-t-2xl sm:rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full p-5 space-y-4 max-h-[85vh] overflow-y-auto">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                <i class="fa-solid fa-wand-magic-sparkles text-amber-500"></i>
                                {{ __('Chapter Revision Assistant') }}
                            </span>
                            <button type="button" @click="mobileAiOpen = false"
                                class="text-slate-400 hover:text-slate-600">
                                <i class="fa-solid fa-xmark text-sm"></i>
                            </button>
                        </div>

                        <p class="text-xs text-slate-500">
                            {{ __('Instruct our system to revise this chapter. Changes will update the live document immediately.') }}
                        </p>

                        <!-- Quick Revision Chips -->
                        <div class="space-y-1.5">
                            <span
                                class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ __('Quick Revisions') }}</span>
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button"
                                    @click="applyQuickInstruction('Add more empirical statistics and localized context')"
                                    class="px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 text-[11px] font-medium">
                                    {{ __('+ Add statistics') }}
                                </button>
                                <button type="button"
                                    @click="applyQuickInstruction('Convert specific research objectives to formatted bullet points')"
                                    class="px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 text-[11px] font-medium">
                                    {{ __('Format bullets') }}
                                </button>
                                <button type="button"
                                    @click="applyQuickInstruction('Strengthen APA 7th academic citations and theoretical grounding')"
                                    class="px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 text-[11px] font-medium">
                                    {{ __('APA Citations') }}
                                </button>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <textarea x-model="aiInstruction" rows="4"
                                class="w-full bg-slate-50 border border-slate-300 rounded-lg p-3 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-[#2271b1]"
                                placeholder="{{ __('Type instructions to revise this chapter...') }}"></textarea>

                            <button type="button" @click="refineActiveStageWithAi(); mobileAiOpen = false;"
                                :disabled="isRefiningWithAi || !aiInstruction"
                                class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center justify-center gap-2 disabled:opacity-50">
                                <i class="fa-solid fa-wand-magic-sparkles text-amber-400"></i>
                                <span>{{ __('Apply Revisions') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.css">
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        /* Academic Proposal Typography */
        .prose h1 {
            font-size: 1.4rem !important;
            font-weight: 900 !important;
            color: #0f172a !important;
            text-transform: uppercase !important;
            letter-spacing: -0.01em !important;
            margin-top: 2.5rem !important;
            margin-bottom: 1.25rem !important;
            padding-bottom: 0.5rem !important;
            border-bottom: 2px solid #0f172a !important;
        }

        .prose h2 {
            font-size: 1.18rem !important;
            font-weight: 800 !important;
            color: #0f172a !important;
            margin-top: 2.25rem !important;
            margin-bottom: 0.85rem !important;
            padding-left: 0.75rem !important;
            border-left: 4px solid #2271b1 !important;
        }

        .prose h3 {
            font-size: 1.05rem !important;
            font-weight: 700 !important;
            color: #1e293b !important;
            margin-top: 1.85rem !important;
            margin-bottom: 0.65rem !important;
            font-style: normal !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .prose h4 {
            font-size: 0.95rem !important;
            font-weight: 700 !important;
            color: #334155 !important;
            margin-top: 1.5rem !important;
            margin-bottom: 0.5rem !important;
        }

        .prose p {
            margin-bottom: 1.15rem !important;
            margin-top: 0 !important;
            line-height: 1.8 !important;
            color: #334155 !important;
        }

        /* True Academic Ordered & Unordered Lists with Clean Hanging Indents */
        .prose ul {
            list-style-type: disc !important;
            padding-left: 1.5rem !important;
            margin-top: 0.75rem !important;
            margin-bottom: 1.25rem !important;
        }

        .prose ol {
            list-style-type: decimal !important;
            padding-left: 1.5rem !important;
            margin-top: 0.75rem !important;
            margin-bottom: 1.25rem !important;
        }

        .prose li {
            margin-bottom: 0.6rem !important;
            line-height: 1.75 !important;
            color: #334155 !important;
            padding-left: 0.25rem !important;
        }

        /* Responsive Math Formulas & KaTeX Width Overflow Fix */
        .katex-display {
            overflow-x: auto !important;
            overflow-y: hidden !important;
            max-width: 100% !important;
            padding: 0.5rem 0 !important;
            margin: 1.25rem 0 !important;
            scrollbar-width: thin;
        }

        .katex {
            font-size: 1.05em !important;
            white-space: normal !important;
        }

        /* ContentEditable Formatted View Active State */
        [contenteditable="true"]:focus {
            outline: 2px solid #2271b1 !important;
            outline-offset: 4px;
            border-radius: 6px;
        }

        .prose li strong {
            color: #0f172a !important;
            font-weight: 700 !important;
        }

        /* Mermaid Diagram Rendering Container */
        .mermaid-diagram-container {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin: 1.75rem 0;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-x: auto;
        }

        /* Academic Tables */
        .prose table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin: 1.5rem 0 !important;
            font-size: 0.82rem !important;
            border: 1px solid #cbd5e1 !important;
            display: block !important;
            overflow-x: auto !important;
            max-width: 100% !important;
        }

        .prose th {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            font-weight: 800 !important;
            padding: 10px 12px !important;
            border: 1px solid #cbd5e1 !important;
            text-align: left !important;
        }

        .prose td {
            padding: 10px 12px !important;
            border: 1px solid #e2e8f0 !important;
            vertical-align: top !important;
            color: #334155 !important;
        }

        .prose tr:nth-child(even) {
            background-color: #f8fafc !important;
        }

        /* Hide KDABot in research proposal wizard and studio preview */
        #kda-btn,
        #kda-modal,
        #agent-ui-container,
        .agent-ui-root,
        [id*="agent-ui"],
        button[title*="KDABot"],
        .chatbot-trigger-btn {
            display: none !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/contrib/auto-render.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
    <script>mermaid.initialize({ startOnLoad: false, theme: 'neutral', securityLevel: 'loose', suppressErrorRendering: true });</script>
    <script>
        function proposalWizard() {
            return {
                viewMode: 'intake', // 'intake' or 'studio'
                currentStep: 1,
                maxReachedStep: 1,
                activeStudioStage: 1, // 1, 2, 3, 4, or 'combined'
                editorMode: 'rendered', // 'rendered' or 'editor'
                proposal_id: null,
                isSubmitting: false,
                isSavingToLibrary: false,
                isSavedToLibrary: false,
                isExportingDocx: false,
                isLoadingSuggestions: false,
                isRefiningWithAi: false,
                showSaveModal: false,
                showDraftsModal: false,
                saveProposalTitle: '',
                mobileStepperOpen: false,
                mobileAiOpen: false,
                loadingStagePreview: { 1: false, 2: false, 3: false, 4: false },
                aiInstruction: '',
                submittingMessage: 'Assembling Final Proposal Document...',
                draftSavedMessage: '',
                hasSavedDraft: false,
                draftRestored: false,
                activeDraftId: null,
                savedDraftsList: [],
                storageTimer: null,
                steps: [
                    { title: @json(__('Topic & Research Model')), desc: @json(__('Title, problem, variables & Chapter 1')) },
                    { title: @json(__('Literature & Framework')), desc: @json(__('Theories, conceptual paths & Chapter 2')) },
                    { title: @json(__('Methodology & Sample')), desc: @json(__('Population, design & Chapter 3')) },
                    { title: @json(__('Budget & Timeline')), desc: @json(__('Financial table & 12-month plan')) },
                    { title: @json(__('Review & Full Document')), desc: @json(__('Master blueprint & final assembly')) }
                ],
                suggestedIndependent: [],
                suggestedDependent: [],
                suggestedTheories: [],
                availableInstrumentModes: [
                    'Online Web Survey',
                    'Field Enumerator Questionnaire',
                    'Key Informant Interviews',
                    'Focus Group Discussions'
                ],
                customIndependentInput: '',
                customDependentInput: '',
                customTheoryInput: '',
                customInstrumentInput: '',
                get totalBudgetCost() {
                    return (this.form.budget || []).reduce((sum, item) => sum + (parseFloat(item.cost) || 0), 0);
                },
                form: {
                    title: '',
                    domain: 'Social Sciences & Development',
                    target_location: '',
                    problem_statement: '',
                    independent_variables: [],
                    dependent_variables: [],
                    theories: [],
                    style: 'apa7',
                    methodology_type: 'mixed',
                    study_goal: 'relational',
                    target_population: 'Target respondents and key institutional actors',
                    population_size: 1000,
                    sample_size: 286,
                    data_collection_modes: ['Online Web Survey', 'Field Enumerator Questionnaire'],
                    sampling_strategy: 'Stratified Random Sampling',
                    measurement_scale: '5-point Likert Scale (1 = Strongly Disagree to 5 = Strongly Agree)',
                    budget: [
                        { item: 'Field Data Collection & Logistics', cost: 100000 },
                        { item: 'Digital Survey Server & Mobile Data', cost: 25000 },
                        { item: 'Research Permit & Ethics Review', cost: 20000 },
                        { item: 'Data Analysis Software Licensing', cost: 50000 }
                    ],
                    custom_instructions: '',
                    previews: { 1: '', 2: '', 3: '', 4: '' }
                },
                initWizard() {
                    this.loadSavedDraftsList();

                    this.$watch('form', (value) => {
                        // Only auto-save once user starts entering content or restored
                        if (value.title || value.problem_statement || this.draftRestored) {
                            if (this.storageTimer) clearTimeout(this.storageTimer);
                            this.storageTimer = setTimeout(() => {
                                this.saveCurrentDraftToList();
                            }, 400);
                        }
                    });
                },
                loadSavedDraftsList() {
                    try {
                        const raw = localStorage.getItem('kd_proposal_wizard_drafts_v2');
                        if (raw) {
                            this.savedDraftsList = JSON.parse(raw) || [];
                        } else {
                            // Migrate legacy single draft if present
                            const legacy = localStorage.getItem('kd_proposal_wizard_draft');
                            if (legacy) {
                                const parsed = JSON.parse(legacy);
                                if (parsed && typeof parsed === 'object' && (parsed.title || parsed.problem_statement)) {
                                    const migratedItem = {
                                        id: 'draft_' + Date.now(),
                                        title: parsed.title || 'Untitled Proposal Draft',
                                        domain: parsed.domain || 'Academic Research',
                                        problem_statement: parsed.problem_statement || '',
                                        independent_variables: parsed.independent_variables || [],
                                        previews: parsed.previews || {},
                                        updated_at: Date.now(),
                                        formattedDate: 'Recently saved',
                                        formData: parsed
                                    };
                                    this.savedDraftsList = [migratedItem];
                                    localStorage.setItem('kd_proposal_wizard_drafts_v2', JSON.stringify(this.savedDraftsList));
                                }
                            }
                        }
                    } catch (e) {
                        console.warn('Failed to load drafts list:', e);
                        this.savedDraftsList = [];
                    }
                },
                saveCurrentDraftToList() {
                    try {
                        if (!this.form.title && !this.form.problem_statement) return;

                        if (!this.activeDraftId) {
                            // Generate unique draft id for new study topic
                            this.activeDraftId = 'draft_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4);
                        }

                        const now = new Date();
                        const formattedDate = now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });

                        const currentDraftEntry = {
                            id: this.activeDraftId,
                            title: this.form.title || 'Untitled Proposal Draft',
                            domain: this.form.domain || 'Academic Research',
                            target_location: this.form.target_location || '',
                            problem_statement: this.form.problem_statement || '',
                            independent_variables: this.form.independent_variables || [],
                            previews: this.form.previews || {},
                            updated_at: Date.now(),
                            formattedDate: formattedDate,
                            formData: JSON.parse(JSON.stringify(this.form))
                        };

                        // Remove existing copy of this draft if present, then prepend to top
                        this.savedDraftsList = this.savedDraftsList.filter(d => d.id !== this.activeDraftId);
                        this.savedDraftsList.unshift(currentDraftEntry);

                        // Keep max 15 recent drafts
                        if (this.savedDraftsList.length > 15) {
                            this.savedDraftsList = this.savedDraftsList.slice(0, 15);
                        }

                        localStorage.setItem('kd_proposal_wizard_drafts_v2', JSON.stringify(this.savedDraftsList));
                        // Also sync primary draft key for backward compatibility
                        localStorage.setItem('kd_proposal_wizard_draft', JSON.stringify(this.form));

                        this.draftSavedMessage = 'Draft saved';
                        setTimeout(() => { this.draftSavedMessage = ''; }, 1500);
                    } catch (e) {
                        console.error('Storage error:', e);
                    }
                },
                loadSpecificDraft(draftId) {
                    const draft = this.savedDraftsList.find(d => d.id === draftId);
                    if (!draft) return;

                    try {
                        this.activeDraftId = draft.id;
                        this.form = Object.assign(this.form, JSON.parse(JSON.stringify(draft.formData)));
                        this.draftRestored = true;
                        this.showDraftsModal = false;
                        this.draftSavedMessage = 'Resumed: ' + (draft.title || 'Draft');

                        // Restore chip arrays so Alpine renders the selectable tags
                        if (this.form.independent_variables && this.form.independent_variables.length) {
                            this.suggestedIndependent = Array.from(new Set([...(this.suggestedIndependent || []), ...this.form.independent_variables]));
                        }
                        if (this.form.dependent_variables && this.form.dependent_variables.length) {
                            this.suggestedDependent = Array.from(new Set([...(this.suggestedDependent || []), ...this.form.dependent_variables]));
                        }
                        if (this.form.theories && this.form.theories.length) {
                            this.suggestedTheories = Array.from(new Set([...(this.suggestedTheories || []), ...this.form.theories]));
                        }

                        // If previews exist for stage 1, jump to studio or keep intake
                        if (this.form.previews && this.form.previews[1]) {
                            this.maxReachedStep = Math.max(this.maxReachedStep, 2);
                        }

                        setTimeout(() => { this.draftSavedMessage = ''; }, 3000);
                    } catch (e) {
                        console.error('Load draft error:', e);
                        alert('Could not restore this specific draft.');
                    }
                },
                deleteSpecificDraft(draftId) {
                    if (confirm('Delete this saved draft?')) {
                        this.savedDraftsList = this.savedDraftsList.filter(d => d.id !== draftId);
                        localStorage.setItem('kd_proposal_wizard_drafts_v2', JSON.stringify(this.savedDraftsList));

                        if (this.activeDraftId === draftId) {
                            this.activeDraftId = null;
                        }
                    }
                },
                clearAllDrafts() {
                    if (confirm('Are you sure you want to delete ALL saved drafts? This cannot be undone.')) {
                        this.savedDraftsList = [];
                        this.activeDraftId = null;
                        localStorage.removeItem('kd_proposal_wizard_drafts_v2');
                        localStorage.removeItem('kd_proposal_wizard_draft');
                    }
                },
                startFreshBlankProposal() {
                    this.showDraftsModal = false;
                    this.activeDraftId = null;
                    this.draftRestored = false;
                    this.form = {
                        title: '',
                        domain: 'Social Sciences & Development',
                        target_location: '',
                        problem_statement: '',
                        independent_variables: [],
                        dependent_variables: [],
                        theories: [],
                        style: 'apa7',
                        methodology_type: 'mixed',
                        study_goal: 'relational',
                        target_population: 'Target respondents and key institutional actors',
                        population_size: 1000,
                        sample_size: 286,
                        data_collection_modes: ['Online Web Survey', 'Field Enumerator Questionnaire'],
                        sampling_strategy: 'Stratified Random Sampling',
                        measurement_scale: '5-point Likert Scale (1 = Strongly Disagree to 5 = Strongly Agree)',
                        budget: [
                            { item: 'Field Data Collection & Logistics', cost: 100000 },
                            { item: 'Digital Survey Server & Mobile Data', cost: 25000 },
                            { item: 'Research Permit & Ethics Review', cost: 20000 },
                            { item: 'Data Analysis Software Licensing', cost: 50000 }
                        ],
                        custom_instructions: '',
                        previews: { 1: '', 2: '', 3: '', 4: '' }
                    };
                    this.currentStep = 1;
                    this.viewMode = 'intake';
                    this.draftSavedMessage = 'New blank proposal started';
                    setTimeout(() => { this.draftSavedMessage = ''; }, 2000);
                },
                goToStep(stepNumber) {
                    if (stepNumber <= this.maxReachedStep) {
                        this.currentStep = stepNumber;
                        this.viewMode = 'intake';
                        if (stepNumber === 2 && this.suggestedIndependent.length === 0 && this.form.title) {
                            this.fetchVariableSuggestions();
                        }
                    }
                },
                getNextButtonLabel() {
                    const labels = {
                        1: {!! json_encode(__('Next: Review Chapter 1')) !!},
                        2: {!! json_encode(__('Next: Review Chapter 2')) !!},
                        3: {!! json_encode(__('Next: Review Chapter 3')) !!},
                        4: {!! json_encode(__('Next: Review Budget & Work Plan')) !!}
                    };
                    return labels[this.currentStep] || {!! json_encode(__('Next Step')) !!};
                },
                async proceedToStudio(step, forceRegen = false) {
                    if (step === 1 && (!this.form.title || !this.form.problem_statement)) {
                        alert('Please enter your research title and describe the core problem before continuing.');
                        return;
                    }

                    if (this.form.previews[step] && !forceRegen) {
                        this.activeStudioStage = step;
                        this.viewMode = 'studio';
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        return;
                    }

                    this.loadingStagePreview[step] = true;
                    try {
                        const response = await fetch('{{ route("research-proposal.wizard.preview-stage") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify({
                                stage: step,
                                title: this.form.title,
                                problem_statement: this.form.problem_statement,
                                domain: this.form.domain,
                                target_location: this.form.target_location,
                                independent_variables: this.form.independent_variables,
                                dependent_variables: this.form.dependent_variables,
                                theories: this.form.theories,
                                population_size: this.form.population_size,
                                sample_size: this.form.sample_size,
                                target_population: this.form.target_population,
                                study_goal: this.form.study_goal,
                                methodology_type: this.form.methodology_type,
                                style: this.form.style,
                                budget: this.form.budget,
                                custom_instructions: this.form.custom_instructions,
                                sampling_strategy: this.form.sampling_strategy,
                                measurement_scale: this.form.measurement_scale,
                                data_collection_modes: this.form.data_collection_modes
                            })
                        });

                        const data = await response.json();
                        if (data.success && data.markdown) {
                            this.form.previews[step] = data.markdown;
                            this.activeStudioStage = step;
                            this.viewMode = 'studio';
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        } else {
                            alert('Failed to generate chapter preview. Please try again.');
                        }
                    } catch (e) {
                        console.error('Stage generation error:', e);
                        alert('An error occurred. Please try again.');
                    } finally {
                        this.loadingStagePreview[step] = false;
                    }
                },
                backToIntake() {
                    this.viewMode = 'intake';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },
                prevStep() {
                    if (this.currentStep > 1) {
                        this.currentStep--;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                },
                nextStep() {
                    if (this.currentStep < 5) {
                        this.currentStep++;
                        if (this.currentStep > this.maxReachedStep) {
                            this.maxReachedStep = this.currentStep;
                        }
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                },
                switchStudioTab(stage) {
                    this.activeStudioStage = stage;
                },
                getTabLabel(stage) {
                    const names = {
                        1: {!! json_encode(__('Chapter 1')) !!},
                        2: {!! json_encode(__('Chapter 2')) !!},
                        3: {!! json_encode(__('Chapter 3')) !!},
                        4: {!! json_encode(__('Budget & Plan')) !!}
                    };
                    return names[stage] || {!! json_encode(__('Stage')) !!} + " " + stage;
                },
                getStudioChapterTitle() {
                    if (this.activeStudioStage === 'combined') {
                        return {!! json_encode(__('Complete Combined Draft Proposal')) !!};
                    }
                    const titles = {
                        1: {!! json_encode(__('Chapter 1: Introduction & Problem Statement')) !!},
                        2: {!! json_encode(__('Chapter 2: Literature Review & Theoretical Framework')) !!},
                        3: {!! json_encode(__('Chapter 3: Research Methodology & Sampling Math')) !!},
                        4: {!! json_encode(__('Proposed Budget Breakdown & 12-Month Work Plan')) !!}
                    };
                    return titles[this.activeStudioStage] || {!! json_encode(__('Chapter Review')) !!};
                },
                getStudyGoalLabel(goal) {
                    const goals = {
                        'relational': {!! json_encode(__('Relational')) !!},
                        'causal': {!! json_encode(__('Causal')) !!},
                        'descriptive': {!! json_encode(__('Descriptive')) !!}
                    };
                    return goals[goal] || goal;
                },
                getApproveButtonLabel() {
                    if (this.activeStudioStage === 4) {
                        return {!! json_encode(__('Approve & View Full Document ➔')) !!};
                    }
                    if (this.activeStudioStage === 'combined') {
                        return {!! json_encode(__('Save & Export')) !!};
                    }
                    return {!! json_encode(__('Approve & Continue to Step')) !!} + ' ' + (this.activeStudioStage + 1) + ' ➔';
                },
                regenerateActiveStudioChapter() {
                    if (typeof this.activeStudioStage === 'number') {
                        this.proceedToStudio(this.activeStudioStage, true);
                    }
                },
                approveCurrentStage() {
                    if (this.activeStudioStage === 4) {
                        // After approving chapter 4, seamlessly transition directly into the Full Document review
                        this.activeStudioStage = 'combined';
                        this.currentStep = 5;
                        this.maxReachedStep = 5;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        return;
                    }

                    if (typeof this.activeStudioStage === 'number') {
                        const next = this.activeStudioStage + 1;
                        this.currentStep = next;
                        if (next > this.maxReachedStep) {
                            this.maxReachedStep = next;
                        }
                        if (next === 2 && this.suggestedIndependent.length === 0 && this.form.title) {
                            this.fetchVariableSuggestions();
                        }
                    } else {
                        this.currentStep = 5;
                    }
                    this.viewMode = 'intake';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },
                handleDirectEdit(event) {
                    // Update current preview in state
                    const stage = this.activeStudioStage;
                    if (typeof stage === 'number') {
                        // Simple content sync
                        const newText = event.target.innerText;
                        // Keep text updated
                        console.log('In-place edit captured for stage ' + stage);
                    }
                },
                openSaveModal() {
                    this.saveProposalTitle = this.form.title || 'Research Proposal';
                    this.showSaveModal = true;
                },
                async confirmSaveToLibrary() {
                    this.form.title = this.saveProposalTitle;
                    this.showSaveModal = false;
                    await this.saveProposalToLibrary();
                },
                getActiveMarkdown() {
                    if (this.activeStudioStage === 'combined') {
                        return [
                            this.form.previews[1] || '',
                            this.form.previews[2] || '',
                            this.form.previews[3] || '',
                            this.form.previews[4] || ''
                        ].filter(Boolean).join("\n\n---\n\n");
                    }
                    return this.form.previews[this.activeStudioStage] || 'No preview generated for this chapter yet.';
                },
                getActiveWordCount() {
                    const md = this.getActiveMarkdown();
                    return md ? md.trim().split(/\s+/).length : 0;
                },
                applyQuickInstruction(text) {
                    this.aiInstruction = text;
                },
                async refineActiveStageWithAi() {
                    if (!this.aiInstruction) return;
                    const stage = this.activeStudioStage;
                    if (stage === 'combined') {
                        alert('Please select an individual chapter tab to apply revisions.');
                        return;
                    }

                    this.isRefiningWithAi = true;
                    try {
                        const response = await fetch('{{ route("research-proposal.wizard.refine-stage-preview") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify({
                                stage: stage,
                                title: this.form.title,
                                current_markdown: this.form.previews[stage],
                                instruction: this.aiInstruction,
                                style: this.form.style
                            })
                        });

                        const data = await response.json();
                        if (data.success && data.markdown) {
                            this.form.previews[stage] = data.markdown;
                            this.aiInstruction = '';
                        } else {
                            alert('Refinement timed out. Please try again.');
                        }
                    } catch (e) {
                        console.error('Refinement error:', e);
                        alert('An error occurred while refining the chapter.');
                    } finally {
                        this.isRefiningWithAi = false;
                    }
                },
                toggleVariable(type, val) {
                    const arr = type === 'independent' ? this.form.independent_variables : this.form.dependent_variables;
                    const idx = arr.indexOf(val);
                    if (idx > -1) {
                        arr.splice(idx, 1);
                    } else {
                        arr.push(val);
                    }
                },
                toggleTheory(theory) {
                    const idx = this.form.theories.indexOf(theory);
                    if (idx > -1) {
                        this.form.theories.splice(idx, 1);
                    } else {
                        this.form.theories.push(theory);
                    }
                },
                addCustomVariable(type) {
                    const input = type === 'independent' ? this.customIndependentInput : this.customDependentInput;
                    const val = input.trim();
                    if (!val) return;
                    const suggested = type === 'independent' ? this.suggestedIndependent : this.suggestedDependent;
                    const selected = type === 'independent' ? this.form.independent_variables : this.form.dependent_variables;
                    if (!suggested.includes(val)) {
                        suggested.push(val);
                    }
                    if (!selected.includes(val)) {
                        selected.push(val);
                    }
                    if (type === 'independent') {
                        this.customIndependentInput = '';
                    } else {
                        this.customDependentInput = '';
                    }
                },
                toggleInstrumentMode(mode) {
                    const idx = this.form.data_collection_modes.indexOf(mode);
                    if (idx > -1) {
                        this.form.data_collection_modes.splice(idx, 1);
                    } else {
                        this.form.data_collection_modes.push(mode);
                    }
                },
                addCustomTheory() {
                    const val = this.customTheoryInput.trim();
                    if (!val) return;
                    if (!this.suggestedTheories.includes(val)) {
                        this.suggestedTheories.push(val);
                    }
                    if (!this.form.theories.includes(val)) {
                        this.form.theories.push(val);
                    }
                    this.customTheoryInput = '';
                },
                get cleanLocation() {
                    let loc = (this.form.target_location || '').trim();
                    if (!loc) return '';
                    // Remove parenthetical details like (Focus on Gikomba and Eastleigh...)
                    loc = loc.replace(/\s*\([^)]*\)/g, '').trim();
                    // Remove trailing commas or punctuation
                    loc = loc.replace(/[,\.\s]+$/, '').trim();
                    return loc ? `in ${loc}` : '';
                },
                get computedObjectives() {
                    const indeps = this.form.independent_variables;
                    const deps = this.form.dependent_variables;
                    if (!indeps.length && !deps.length) return [];

                    const loc = this.cleanLocation;
                    // Format primary dependent outcome concisely
                    const primaryDep = deps.length === 1
                        ? deps[0]
                        : (deps.length === 2 ? `${deps[0]} and ${deps[1]}` : `${deps[0]} and overall performance`);

                    const objs = [];
                    const verbs = [
                        'To assess the effect of',
                        'To examine the influence of',
                        'To evaluate the relationship between',
                        'To determine the impact of'
                    ];

                    // 1:1 mapping for each independent variable
                    indeps.forEach((indep, index) => {
                        const verb = verbs[index % verbs.length];
                        const sentence = loc
                            ? `${verb} ${indep} on ${primaryDep} ${loc}.`
                            : `${verb} ${indep} on ${primaryDep}.`;
                        objs.push(sentence);
                    });

                    // Concise final objective for combined/joint effect (avoids word salad)
                    if (indeps.length >= 2) {
                        const jointSentence = loc
                            ? `To evaluate the joint effect of the independent factors on ${primaryDep} ${loc}.`
                            : `To evaluate the joint effect of the independent factors on ${primaryDep}.`;
                        objs.push(jointSentence);
                    }

                    return objs;
                },
                recalculateSampleSize() {
                    const N = parseInt(this.form.population_size) || 1000;
                    const e = 0.05;
                    const n = Math.round(N / (1 + (N * Math.pow(e, 2))));
                    this.form.sample_size = Math.min(n, N);
                },
                get isStep1Valid() {
                    if (this.currentStep !== 1) return true;
                    return Boolean(
                        (this.form.title || '').trim() &&
                        (this.form.problem_statement || '').trim() &&
                        (this.form.independent_variables || []).length > 0 &&
                        (this.form.dependent_variables || []).length > 0
                    );
                },
                get isStep3Valid() {
                    if (this.currentStep !== 3) return true;
                    const modes = this.form.data_collection_modes || [];
                    if (modes.length === 0) return false;
                    if (this.form.methodology_type === 'mixed') {
                        const quantKeywords = ['survey', 'questionnaire', 'google forms', 'quantitative', 'scale', 'checklist', 'numeric'];
                        const qualKeywords = ['interview', 'focus group', 'discussion', 'kii', 'fgd', 'qualitative', 'case study', 'memo', 'observation'];
                        const hasQuant = modes.some(m => quantKeywords.some(k => m.toLowerCase().includes(k)));
                        const hasQual = modes.some(m => qualKeywords.some(k => m.toLowerCase().includes(k)));
                        return hasQuant && hasQual;
                    }
                    return modes.length >= 1;
                },
                onDesignChange(design) {
                    this.form.methodology_type = design;
                    if (design === 'mixed') {
                        this.availableInstrumentModes = [
                            'Online Web Survey',
                            'Field Enumerator Questionnaire',
                            'Key Informant Interviews',
                            'Focus Group Discussions'
                        ];
                        this.form.data_collection_modes = ['Field Enumerator Questionnaire', 'Key Informant Interviews'];
                    } else if (design === 'survey') {
                        this.availableInstrumentModes = [
                            'Field Enumerator Questionnaire',
                            'Online Web Survey',
                            'Structured Likert Scale Matrix',
                            'Mobile CAPI (Digital Mobile Forms)'
                        ];
                        this.form.data_collection_modes = ['Field Enumerator Questionnaire', 'Online Web Survey'];
                    } else if (design === 'qualitative') {
                        this.availableInstrumentModes = [
                            'Key Informant Interviews',
                            'Focus Group Discussions',
                            'In-Depth Semi-Structured Interview Guide',
                            'Direct Field Observation Protocol'
                        ];
                        this.form.data_collection_modes = ['Key Informant Interviews', 'Focus Group Discussions'];
                    }
                },
                addCustomInstrument() {
                    const val = this.customInstrumentInput.trim();
                    if (!val) return;
                    if (!this.availableInstrumentModes.includes(val)) {
                        this.availableInstrumentModes.push(val);
                    }
                    if (!this.form.data_collection_modes.includes(val)) {
                        this.form.data_collection_modes.push(val);
                    }
                    this.customInstrumentInput = '';
                },
                addBudgetItem() {
                    this.form.budget.push({ item: '', cost: 0 });
                },
                removeBudgetItem(index) {
                    this.form.budget.splice(index, 1);
                },
                skipBudgetStep() {
                    this.form.include_budget = false;
                    this.form.previews[4] = '';
                    this.currentStep = 5;
                    this.maxReachedStep = 5;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },
                clearCurrentStep(step) {
                    if (step === 1) {
                        this.form.title = '';
                        this.form.target_location = '';
                        this.form.problem_statement = '';
                    } else if (step === 2) {
                        this.form.independent_variables = [];
                        this.form.dependent_variables = [];
                        this.form.theories = [];
                        this.customIndependentInput = '';
                        this.customDependentInput = '';
                        this.customTheoryInput = '';
                    } else if (step === 3) {
                        this.form.population_size = 1000;
                        this.form.sample_size = 286;
                        this.form.target_population = '';
                        this.form.data_collection_modes = [];
                    } else if (step === 4) {
                        this.form.budget = [{ item: 'Field Data Collection & Logistics', cost: 100000 }];
                        this.form.custom_instructions = '';
                    }
                },
                resetDraft() {
                    if (confirm('Are you sure you want to clear your current proposal draft and reset the form?')) {
                        localStorage.removeItem('kd_proposal_wizard_draft');
                        location.reload();
                    }
                },
                async fetchVariableSuggestions() {
                    if (!this.form.title && !this.form.problem_statement) {
                        alert('Please provide a working title or problem statement first.');
                        return;
                    }
                    this.isLoadingSuggestions = true;
                    // Provide instant visual feedback by clearing previous suggestions while fetching
                    this.suggestedIndependent = [];
                    this.suggestedDependent = [];
                    this.suggestedTheories = [];
                    try {
                        const response = await fetch('{{ route("research-proposal.wizard.suggest-variables") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify({
                                title: this.form.title,
                                problem: this.form.problem_statement,
                                domain: this.form.domain
                            })
                        });
                        const data = await response.json();
                        if (data.success) {
                            if (data.independent && data.independent.length) {
                                this.suggestedIndependent = data.independent;
                                // Keep suggestions unselected so user clicks chips to choose them
                            }
                            if (data.dependent && data.dependent.length) {
                                this.suggestedDependent = data.dependent;
                                // Keep suggestions unselected so user clicks chips to choose them
                            }
                            if (data.theories && data.theories.length) {
                                this.suggestedTheories = data.theories;
                                // Keep suggestions unselected so user clicks chips to choose them
                            }
                        }
                    } catch (e) {
                        console.error('Failed to fetch suggestions:', e);
                    } finally {
                        this.isLoadingSuggestions = false;
                    }
                },
                renderMarkdown(md) {
                    if (!md) return '';
                    try {
                        // Pre-process math: convert rogue [ n = \frac{...} ] or [ Y = \beta... ] into standard $$ ... $$ display math
                        let processedMd = md.replace(/\[\s*(n\s*=[\s\S]*?)\s*\]/g, '$$$$ $1 $$$$');
                        processedMd = processedMd.replace(/\[\s*(Y\s*=[\s\S]*?)\s*\]/g, '$$$$ $1 $$$$');
                        processedMd = processedMd.replace(/\[\s*(\\frac[\s\S]*?)\s*\]/g, '$$$$ $1 $$$$');
                        // Fix corrupted \text or \varepsilon in markdown math blocks
                        processedMd = processedMd.replace(/\\text\{([^}]+)\}/g, '($1)');
                        processedMd = processedMd.replace(/\\varepsilon/g, 'ε');
                        // Ensure numbered list items have a space after the dot (e.g., '1.To determine' -> '1. To determine')
                        processedMd = processedMd.replace(/^(\d+)\.([^\s\d])/gm, '$1. $2');
                        let html = marked.parse(processedMd);

                        // Replace ```mermaid code blocks with unique container IDs for safe isolated rendering
                        let diagramIndex = 0;
                        html = html.replace(/<pre><code class="language-mermaid">([\s\S]*?)<\/code><\/pre>/gi, function (match, code) {
                            diagramIndex++;
                            let cleanCode = code.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
                            // Sanitize unquoted special characters in node labels e.g. [E-Commerce Adoption (TAM)] -> ["E-Commerce Adoption (TAM)"]
                            cleanCode = cleanCode.replace(/\[([^\]"'\n]+)\]/g, function (m, p1) {
                                return '["' + p1.replace(/"/g, '') + '"]';
                            });
                            const containerId = 'mermaid-container-' + diagramIndex + '-' + Date.now();
                            return '<div class="mermaid-diagram-container" id="' + containerId + '" data-code="' + btoa(unescape(encodeURIComponent(cleanCode.trim()))) + '"><div class="text-xs text-slate-400 py-3 flex items-center gap-2"><i class="fa-solid fa-spinner fa-spin"></i> Rendering Conceptual Framework Diagram...</div></div>';
                        });

                        // Trigger mermaid safe isolated render and KaTeX math rendering
                        setTimeout(async () => {
                            if (window.mermaid) {
                                const containers = document.querySelectorAll('.mermaid-diagram-container[data-code]');
                                for (let el of containers) {
                                    try {
                                        const rawCode = decodeURIComponent(escape(atob(el.getAttribute('data-code'))));
                                        const id = 'svg-' + Math.random().toString(36).substr(2, 9);
                                        const { svg } = await window.mermaid.render(id, rawCode);
                                        el.innerHTML = svg;
                                        el.removeAttribute('data-code');
                                    } catch (err) {
                                        console.warn('Mermaid syntax sanitize fallback:', err);
                                        el.innerHTML = '<div class="w-full bg-slate-50 border border-slate-200 rounded-lg p-4 text-center text-xs text-slate-600"><span class="font-bold text-slate-800"><i class="fa-solid fa-project-diagram text-[#2271b1] mr-1.5"></i> Conceptual Model Relationships</span><p class="mt-1 text-[11px] text-slate-500">Independent variables map directly to study outcomes as detailed in the narrative below.</p></div>';
                                    }
                                }
                            }

                            const runKaTeX = () => {
                                if (window.renderMathInElement) {
                                    const previewEls = document.querySelectorAll('.prose, [contenteditable="true"]');
                                    previewEls.forEach(previewEl => {
                                        window.renderMathInElement(previewEl, {
                                            delimiters: [
                                                { left: '$$', right: '$$', display: true },
                                                { left: '\\[', right: '\\]', display: true },
                                                { left: '$', right: '$', display: false },
                                                { left: '\\(', right: '\\)', display: false }
                                            ],
                                            throwOnError: false
                                        });
                                    });
                                }
                            };

                            runKaTeX();
                            // Secondary trigger in case KaTeX script was still downloading deferred
                            setTimeout(runKaTeX, 300);
                        }, 50);

                        return html;
                    } catch (e) {
                        return md;
                    }
                },
                openCombinedStudio() {
                    this.activeStudioStage = 'combined';
                    this.viewMode = 'studio';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },
                async saveProposalToLibrary() {
                    this.isSavingToLibrary = true;
                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const response = await fetch('{{ route("research-proposal.store") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify(this.form)
                        });

                        const data = await response.json();
                        if (data.success && data.proposal_id) {
                            this.proposal_id = data.proposal_id;
                            this.form.proposal_id = data.proposal_id;
                            this.isSavedToLibrary = true;
                            this.draftSavedMessage = 'Saved in Library';
                            setTimeout(() => { this.draftSavedMessage = ''; }, 3000);
                        } else {
                            alert(data.message || 'Saved successfully.');
                        }
                    } catch (e) {
                        console.error('Save error:', e);
                        alert('Could not save proposal to library. Please try again.');
                    } finally {
                        this.isSavingToLibrary = false;
                    }
                },
                async downloadDocxFromWizard() {
                    this.isExportingDocx = true;
                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const response = await fetch('{{ route("research-proposal.wizard.export-docx") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                title: this.form.title || 'Research Proposal',
                                previews: this.form.previews,
                                style: this.form.style,
                                budget: this.form.budget,
                                include_budget: this.form.include_budget
                            })
                        });

                        if (!response.ok) throw new Error('Export request failed.');

                        const blob = await response.blob();
                        const downloadUrl = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = downloadUrl;
                        a.download = (this.form.title ? this.form.title.substring(0, 40).replace(/[^a-zA-Z0-9]/g, '_') : 'research_proposal') + '.docx';
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                    } catch (e) {
                        console.error('Export error:', e);
                        alert('Failed to export DOCX. Please try again.');
                    } finally {
                        this.isExportingDocx = false;
                    }
                },
                async submitProposal() {
                    this.isSubmitting = true;
                    this.submittingMessage = 'Synthesizing Full Document, Budget & Measurement Instruments...';

                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const response = await fetch('{{ route("research-proposal.store") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify(this.form)
                        });

                        const rawText = await response.text();
                        let data;
                        try {
                            data = JSON.parse(rawText);
                        } catch (jsonErr) {
                            console.error('Non-JSON Server Response:', rawText);
                            throw new Error('Server returned invalid response. Check console logs.');
                        }

                        if (data.success && data.redirect_url) {
                            if (data.proposal_id) {
                                this.proposal_id = data.proposal_id;
                                this.form.proposal_id = data.proposal_id;
                            }
                            localStorage.removeItem('kd_proposal_wizard_draft');
                            window.location.href = data.redirect_url;
                        } else {
                            alert(data.message || 'Proposal compiled successfully! Redirecting...');
                            window.location.href = '{{ route("research-proposal.history") }}';
                        }
                    } catch (e) {
                        console.error('Submission error:', e);
                        alert('An error occurred while compiling your proposal: ' + e.message);
                        this.isSubmitting = false;
                    }
                }
            };
        }
    </script>
@endpush