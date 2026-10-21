@extends('layouts.app')

@section('content')
    <div x-data="{ 
                        billingCycle: 'monthly',
                        currency: 'KES',
                        
                        checkoutModalOpen: false,
                        cancelModalOpen: false,
                        isDowngrade: false,
                        checkoutCycle: 'monthly',
                        modalPaymentMethod: 'intasend',
                        modalTier: null,
                        openCheckoutModal(tier) {
                            if (tier.isFree) {
                                this.cancelModalOpen = true;
                                return;
                            }
                            this.modalTier = tier;
                            this.isDowngrade = (tier.price < {{ $tiers->firstWhere('id', $currentTierId ?? 0)?->monthly_price ?? 0 }});
                            this.checkoutCycle = this.billingCycle;
                            this.modalPaymentMethod = 'intasend';
                            this.checkoutModalOpen = true;
                        }
                    }" class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="text-center mb-12">
                <span
                    class="text-xs font-black text-[#2271b1] tracking-widest uppercase bg-[#2271b1]/10 px-4 py-1.5 rounded-full">
                    {{ config('subscription.early_access_mode', true) ? __('Early Access • Subscription Plans') : __('Subscription Plans') }}
                </span>
                <h1 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight mt-4">
                    {{ __('Choose the Plan That Fits Your Research') }}
                </h1>
                <p class="text-sm font-bold text-gray-500 max-w-2xl mx-auto mt-3">
                    {{ __('Account Type') }}: <span
                        class="text-gray-900 font-extrabold underline">{{ $accountTypeLabel ?? __('Researcher') }}</span>
                </p>

                {{-- Active Plan Summary Header if Subscribed --}}
                @if(isset($subscriptionDetails) && $subscriptionDetails['is_active'])
                    <div class="max-w-xl mx-auto mt-6">
                        <x-subscription-status-banner />
                    </div>
                @elseif(isset($subscriptionDetails) && $subscriptionDetails['is_expired'])
                    <div class="max-w-xl mx-auto mt-6">
                        <x-subscription-status-banner />
                    </div>
                @endif

                {{-- Organization Team Member Notice (Non-Admin) --}}
                @if(!empty($isOrgMemberWithoutBilling))
                    <div
                        class="max-w-2xl mx-auto mt-6 p-6 bg-blue-50 border border-blue-200 rounded-3xl text-left flex items-start gap-4">
                        <div
                            class="w-10 h-10 rounded-2xl bg-blue-500 text-white flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-blue-900 uppercase tracking-wider">
                                {{ __('Organization Team Workspace') }}
                            </h4>
                            <p class="text-xs text-blue-800/90 mt-1">
                                {{ __('You are collaborating inside') }}
                                <strong>{{ $activeOrg->name ?? 'your organization' }}</strong>.
                                {{ __('Your account inherits the organization\'s active subscription privileges. Plan upgrades and billing changes are managed directly by your Organization Admin/Owner.') }}
                            </p>
                        </div>
                    </div>
                @endif

                {{-- Toggles: Currency & Billing Cycle --}}
                <div class="flex flex-col sm:flex-row items-center justify-center gap-6 mt-10">
                    {{-- Currency Selector --}}
                    <div class="inline-flex p-1.5 bg-gray-200/70 rounded-2xl border border-gray-300/60 shadow-inner">
                        <button type="button" @click="currency = 'KES'"
                            :class="currency === 'KES' ? 'bg-white text-gray-900 shadow-md font-black' : 'text-gray-500 font-bold hover:text-gray-900'"
                            class="px-5 py-2.5 rounded-xl text-xs uppercase tracking-wider transition-all flex items-center gap-2">
                            <span>🇰🇪</span> {{ __('KES (Shillings)') }}
                        </button>
                        <button type="button" @click="currency = 'USD'"
                            :class="currency === 'USD' ? 'bg-white text-gray-900 shadow-md font-black' : 'text-gray-500 font-bold hover:text-gray-900'"
                            class="px-5 py-2.5 rounded-xl text-xs uppercase tracking-wider transition-all flex items-center gap-2">
                            <span>🌐</span> {{ __('USD (Global Billing)') }}
                        </button>
                    </div>

                    {{-- Billing Cycle Toggle --}}
                    <div
                        class="inline-flex items-center gap-4 bg-white px-5 py-2.5 rounded-2xl border border-gray-200 shadow-sm">
                        <span class="text-xs font-black uppercase tracking-wider"
                            :class="billingCycle === 'monthly' ? 'text-gray-900' : 'text-gray-400'">{{ __('Monthly') }}</span>
                        <button type="button" @click="billingCycle = billingCycle === 'monthly' ? 'yearly' : 'monthly'"
                            class="relative w-14 h-7 rounded-full transition-colors duration-300 focus:outline-none"
                            :class="billingCycle === 'yearly' ? 'bg-[#2271b1]' : 'bg-gray-300'">
                            <div class="absolute top-1 left-1 w-5 h-5 bg-white rounded-full transition-transform duration-300 shadow"
                                :class="billingCycle === 'yearly' ? 'translate-x-7' : 'translate-x-0'"></div>
                        </button>
                        <span class="text-xs font-black uppercase tracking-wider flex items-center gap-2"
                            :class="billingCycle === 'yearly' ? 'text-gray-900' : 'text-gray-400'">
                            {{ __('Yearly') }}
                            <span
                                class="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider animate-pulse">{{ __('Save ~17%') }}</span>
                        </span>
                    </div>
                </div>
            </div>

            {{-- Pricing Grid --}}
            @php
                $userRole = auth()->user()->role instanceof \UnitEnum ? auth()->user()->role->value : auth()->user()->role;
                $defaultFreeSlug = ($userRole === 'organization') ? 'org-free' : 'free';
                $freeTierId = $tiers->firstWhere('slug', $defaultFreeSlug)->id ?? ($tiers->firstWhere('slug', 'free')->id ?? null);
                $currentTierId = (auth()->user()->hasActiveSubscription() && $entity)
                    ? ($entity->subscription_tier_id ?? $freeTierId)
                    : $freeTierId;
                $gridColsClass = (count($tiers) <= 2) ? 'max-w-4xl mx-auto md:grid-cols-2' : 'md:grid-cols-3';
            @endphp

            <div class="grid grid-cols-1 {{ $gridColsClass }} gap-8 items-stretch">
                @foreach($tiers as $tier)
                    @php
                        $isCurrent = $currentTierId == $tier->id;
                        $isPopular = in_array(strtolower($tier->slug), ['pro', 'org-pro', 'respondent-pro']);
                        $isFree = str_contains(strtolower($tier->slug), 'free');
                        $isEnterprise = str_contains(strtolower($tier->slug), 'enterprise');
                        $isEarlyAccess = config('subscription.early_access_mode', true);
                        
                        $displaySurveys = $tier->max_surveys == -1 
                            ? __('Unlimited') 
                            : ($isFree && $isEarlyAccess ? config('subscription.early_access.max_surveys', 10) : $tier->max_surveys);

                        $displayResponses = $tier->max_responses_per_survey == -1 
                            ? __('Unlimited') 
                            : number_format($isFree && $isEarlyAccess ? config('subscription.early_access.max_responses_per_survey', 1000) : $tier->max_responses_per_survey);

                        $displayAi = $tier->ai_limit_per_month == -1 
                            ? __('Unlimited') 
                            : ($isFree && $isEarlyAccess ? config('subscription.early_access.ai_analyses_monthly', 50) : $tier->ai_limit_per_month);

                        $displayOrgSeats = $tier->org_max_seats == -1 
                            ? __('Unlimited') 
                            : ($isFree && $isEarlyAccess ? config('subscription.early_access.org_free_seats', 5) : $tier->org_max_seats);

                        $btnLabel = __('Upgrade Plan');
                        if ($isCurrent) {
                            $btnLabel = __('Current Plan');
                        } elseif ($tier->monthly_price < ($tiers->firstWhere('id', $currentTierId)->monthly_price ?? 0)) {
                            $btnLabel = __('Downgrade');
                        }
                    @endphp

                    <div
                        class="relative flex flex-col bg-white rounded-3xl shadow-xl transition-all duration-300 hover:scale-[1.02] hover:shadow-2xl overflow-hidden {{ $isPopular ? 'border-4 border-[#2271b1] z-10' : 'border border-gray-200' }}">
                        @if($isPopular)
                            <div
                                class="absolute top-0 right-0 bg-[#2271b1] text-white px-5 py-1.5 font-black text-[10px] uppercase tracking-widest rounded-bl-2xl shadow-md">
                                {{ __('Most Popular') }}
                            </div>
                        @elseif($isEnterprise)
                            <div
                                class="absolute top-0 right-0 bg-slate-900 text-white px-5 py-1.5 font-black text-[10px] uppercase tracking-widest rounded-bl-2xl shadow-md">
                                {{ __('Scale & Enterprise') }}
                            </div>
                        @endif

                        <div class="p-8 sm:p-10 flex-1 flex flex-col">
                            {{-- Title & Price --}}
                            <div class="mb-8">
                                <h3 class="text-2xl font-black text-gray-900 tracking-tight mb-2">{{ $tier->name }}</h3>
                                <p class="text-xs text-gray-500 font-medium mb-6 min-h-[36px]">{{ $tier->description }}</p>

                                <div class="flex items-baseline gap-1.5">
                                    <template x-if="currency === 'KES'">
                                        <span class="text-4xl font-black text-gray-900 tracking-tight"
                                            x-text="billingCycle === 'monthly' ? 'KES {{ number_format($tier->monthly_price, 0) }}' : 'KES {{ number_format($tier->yearly_price, 0) }}'"></span>
                                    </template>
                                    <template x-if="currency === 'USD'">
                                        <span class="text-4xl font-black text-gray-900 tracking-tight"
                                            x-text="billingCycle === 'monthly' ? '${{ number_format($tier->monthly_price_usd, 2) }}' : '${{ number_format($tier->yearly_price_usd, 2) }}'"></span>
                                    </template>
                                    <span class="text-gray-400 font-bold text-xs tracking-wider"
                                        x-text="billingCycle === 'monthly' ? '{{ __('/ mo') }}' : '{{ __('/ yr') }}'"></span>
                                </div>
                            </div>

                            <hr class="border-gray-100 mb-8">

                            {{-- Feature List --}}
                            <ul class="space-y-4 mb-10 flex-1 text-sm">
                                @if(str_contains(strtolower($tier->slug), 'respondent'))
                                    {{-- Respondent Tier Features --}}
                                    @if($isFree)
                                        <li class="flex items-start gap-3 text-gray-700">
                                            <i class="fa-solid fa-check text-emerald-600 mt-1 flex-shrink-0"></i>
                                            <span><strong>{{ __('Unlimited') }}</strong> {{ __('Survey Participation') }}</span>
                                        </li>
                                        <li class="flex items-start gap-3 text-gray-700">
                                            <i class="fa-solid fa-check text-emerald-600 mt-1 flex-shrink-0"></i>
                                            <span>{{ __('Cash Out Rewards to M-Pesa / Bank') }}</span>
                                        </li>
                                        <li class="flex items-start gap-3 text-gray-700">
                                            <i class="fa-solid fa-check text-emerald-600 mt-1 flex-shrink-0"></i>
                                            <span>{{ __('View Survey History & Wallet Ledger') }}</span>
                                        </li>
                                        <li class="flex items-start gap-3 text-gray-700">
                                            <i class="fa-solid fa-check text-emerald-600 mt-1 flex-shrink-0"></i>
                                            <span><strong>{{ $isEarlyAccess ? '5 Files' : '1 Trial' }}</strong> {{ __('Audio/Video Transcription') }}</span>
                                        </li>
                                        <li class="flex items-start gap-3 text-gray-700">
                                            <i class="fa-solid fa-check text-emerald-600 mt-1 flex-shrink-0"></i>
                                            <span><strong>{{ $isEarlyAccess ? '10 Scans (5,000 words)' : '3 Trial Scans (1,500 words)' }}</strong> {{ __('Plagiarism Checker') }}</span>
                                        </li>
                                        @if($isEarlyAccess)
                                            <li class="flex items-start gap-3 text-gray-700">
                                                <i class="fa-solid fa-check text-emerald-600 mt-1 flex-shrink-0"></i>
                                                <span><strong>50 {{ __('Queries / mo') }}</strong> {{ __('Socius Chat Assistant') }}</span>
                                            </li>
                                            <li class="flex items-start gap-3 text-gray-700">
                                                <i class="fa-solid fa-check text-emerald-600 mt-1 flex-shrink-0"></i>
                                                <span>{{ __('Full DOCX Research Report Compilation') }}</span>
                                            </li>
                                        @else
                                            <li class="flex items-start gap-3 text-gray-400 opacity-60">
                                                <i class="fa-solid fa-lock text-gray-400 mt-1 flex-shrink-0"></i>
                                                <span>{{ __('Socius AI Chat Assistant (Locked)') }}</span>
                                            </li>
                                            <li class="flex items-start gap-3 text-gray-400 opacity-60">
                                                <i class="fa-solid fa-lock text-gray-400 mt-1 flex-shrink-0"></i>
                                                <span>{{ __('AI Text Humanizer (Locked)') }}</span>
                                            </li>
                                            <li class="flex items-start gap-3 text-gray-400 opacity-60">
                                                <i class="fa-solid fa-lock text-gray-400 mt-1 flex-shrink-0"></i>
                                                <span>{{ __('Research Studio Report Compiler (Locked)') }}</span>
                                            </li>
                                        @endif
                                    @else
                                        <li class="flex items-start gap-3 text-gray-800">
                                            <i class="fa-solid fa-robot text-[#2271b1] mt-1 flex-shrink-0"></i>
                                            <span><strong>{{ __('Socius AI Assistant') }}</strong>:
                                                {{ __('Unlimited Research Chats & Document Q&A') }}</span>
                                        </li>
                                        <li class="flex items-start gap-3 text-gray-800">
                                            <i class="fa-solid fa-wand-magic-sparkles text-[#2271b1] mt-1 flex-shrink-0"></i>
                                            <span><strong>{{ __('AI Humanizer') }}</strong>:
                                                {{ __('Unlimited AI text humanizing & bypass detection') }}</span>
                                        </li>
                                        <li class="flex items-start gap-3 text-gray-800">
                                            <i class="fa-solid fa-magnifying-glass text-[#2271b1] mt-1 flex-shrink-0"></i>
                                            <span><strong>{{ __('Plagiarism Checker') }}</strong>:
                                                {{ __('15 scans / mo (up to 15,000 words/scan)') }}</span>
                                        </li>
                                        <li class="flex items-start gap-3 text-gray-800">
                                            <i class="fa-solid fa-microphone text-[#2271b1] mt-1 flex-shrink-0"></i>
                                            <span><strong>{{ __('AI Transcription') }}</strong>:
                                                {{ __('10 Audio & Video interviews / mo') }}</span>
                                        </li>
                                        <li class="flex items-start gap-3 text-gray-800">
                                            <i class="fa-solid fa-file-signature text-[#2271b1] mt-1 flex-shrink-0"></i>
                                            <span><strong>{{ __('Research Studio') }}</strong>:
                                                {{ __('Compile, edit, and draft full proposals & reports') }}</span>
                                        </li>
                                        <li class="flex items-start gap-3 text-gray-800">
                                            <i class="fa-solid fa-check text-emerald-600 mt-1 flex-shrink-0"></i>
                                            <span>{{ __('Unlimited Public Survey Participation & Rewards') }}</span>
                                        </li>
                                    @endif

                                @elseif(str_contains(strtolower($tier->slug), 'org'))
                                    {{-- Organization Multi-Seat Tier Features --}}
                                    <li class="flex items-start gap-3 text-gray-900 font-bold">
                                        <i class="fa-solid fa-users text-[#2271b1] mt-1 flex-shrink-0"></i>
                                        <span><strong>{{ $displayOrgSeats }}</strong>
                                            {{ __('Team Member Seats') }}</span>
                                    </li>
                                    <li class="flex items-start gap-3 text-gray-800">
                                        <i class="fa-solid fa-chart-pie text-[#2271b1] mt-1 flex-shrink-0"></i>
                                        <span><strong>{{ $displaySurveys }}</strong>
                                            {{ __('Active Surveys') }}</span>
                                    </li>
                                    <li class="flex items-start gap-3 text-gray-800">
                                        <i class="fa-solid fa-check text-emerald-600 mt-1 flex-shrink-0"></i>
                                        <span><strong>{{ $displayResponses }}</strong>
                                            {{ __('Responses per Survey') }}</span>
                                    </li>
                                    <li class="flex items-start gap-3 text-gray-800">
                                        <i class="fa-solid fa-layer-group text-[#2271b1] mt-1 flex-shrink-0"></i>
                                        <span>{{ __('Shared Team Resource Pool (AI & Transcriptions)') }}</span>
                                    </li>
                                    <li class="flex items-start gap-3 text-gray-800">
                                        <i class="fa-solid fa-clipboard-user text-[#2271b1] mt-1 flex-shrink-0"></i>
                                        <span>{{ __('Fieldwork Enumerator Assignments & Supervision') }}</span>
                                    </li>
                                    @if($isEnterprise)
                                        <li class="flex items-start gap-3 text-gray-800">
                                            <i class="fa-solid fa-shield-halved text-[#2271b1] mt-1 flex-shrink-0"></i>
                                            <span>{{ __('Team Audit Logs & Institutional Compliance') }}</span>
                                        </li>
                                        <li class="flex items-start gap-3 text-gray-800">
                                            <i class="fa-solid fa-file-export text-[#2271b1] mt-1 flex-shrink-0"></i>
                                            <span>{{ __('Full SPSS (.sav), Excel, XML & Sheets Exports') }}</span>
                                        </li>
                                        <li class="flex items-start gap-3 text-gray-800">
                                            <i class="fa-solid fa-headset text-[#2271b1] mt-1 flex-shrink-0"></i>
                                            <span>{{ __('24/7 Dedicated Priority Institutional Support') }}</span>
                                        </li>
                                    @else
                                        <li class="flex items-start gap-3 text-gray-800">
                                            <i class="fa-solid fa-palette text-[#2271b1] mt-1 flex-shrink-0"></i>
                                            <span>{{ __('Custom Team Branding & Clean PDF Reports') }}</span>
                                        </li>
                                    @endif

                                @else
                                    {{-- Independent Researcher Tier Features --}}
                                    <li class="flex items-start gap-3 text-gray-800">
                                        <i class="fa-solid fa-clipboard-list text-[#2271b1] mt-1 flex-shrink-0"></i>
                                        <span><strong>{{ $displaySurveys }}</strong>
                                            {{ __('Surveys') }}</span>
                                    </li>
                                    <li class="flex items-start gap-3 text-gray-800">
                                        <i class="fa-solid fa-check text-emerald-600 mt-1 flex-shrink-0"></i>
                                        <span><strong>{{ $displayResponses }}</strong>
                                            {{ __('Responses per Survey') }}</span>
                                    </li>
                                    <li class="flex items-start gap-3 text-gray-800">
                                        <i class="fa-solid fa-robot text-[#2271b1] mt-1 flex-shrink-0"></i>
                                        <span><strong>{{ $displayAi }}</strong>
                                            {{ __('AI Actions / mo') }}</span>
                                    </li>
                                    <li class="flex items-start gap-3 text-gray-800">
                                        <i class="fa-solid fa-microphone text-[#2271b1] mt-1 flex-shrink-0"></i>
                                        <span><strong>{{ $isFree ? ($isEarlyAccess ? '5 Files' : '1 Trial') : ($tier->slug === 'pro' ? 'Unlimited' : 'Unlimited') }}</strong>
                                            {{ __('Audio/Video Transcriptions') }}</span>
                                    </li>
                                    <li class="flex items-start gap-3 text-gray-800">
                                        <i class="fa-solid fa-magnifying-glass text-[#2271b1] mt-1 flex-shrink-0"></i>
                                        <span><strong>{{ $isFree ? ($isEarlyAccess ? '10 Scans (5k words)' : '3 Scans (1.5k words)') : ($tier->slug === 'pro' ? 'Unlimited (75k words)' : 'Unlimited (75k words)') }}</strong>
                                            {{ __('Plagiarism Scans') }}</span>
                                    </li>
                                    @if($isFree && $isEarlyAccess)
                                        <li class="flex items-start gap-3 text-gray-800">
                                            <i class="fa-solid fa-file-excel text-[#2271b1] mt-1 flex-shrink-0"></i>
                                            <span>{{ __('CSV, Excel (.xlsx) & JSON Data Exports') }}</span>
                                        </li>
                                        <li class="flex items-start gap-3 text-gray-800">
                                            <i class="fa-solid fa-file-word text-[#2271b1] mt-1 flex-shrink-0"></i>
                                            <span>{{ __('Full DOCX Proposal & Report Compilation') }}</span>
                                        </li>
                                    @endif
                                    @if(!$isFree)
                                        <li class="flex items-start gap-3 text-gray-800">
                                            <i class="fa-solid fa-file-word text-[#2271b1] mt-1 flex-shrink-0"></i>
                                            <span>{{ __('Unlimited DOCX Proposal & Report Compilation') }}</span>
                                        </li>
                                        <li class="flex items-start gap-3 text-gray-800">
                                            <i class="fa-solid fa-file-export text-[#2271b1] mt-1 flex-shrink-0"></i>
                                            <span>{{ __('Full SPSS (.sav), Excel, JSON, XML & Sheets Exports') }}</span>
                                        </li>
                                        <li class="flex items-start gap-3 text-gray-800">
                                            <i class="fa-solid fa-diagram-project text-[#2271b1] mt-1 flex-shrink-0"></i>
                                            <span>{{ __('Cross-Tabulation & Statistical AI Analysis') }}</span>
                                        </li>
                                        <li class="flex items-start gap-3 text-gray-800">
                                            <i class="fa-solid fa-palette text-[#2271b1] mt-1 flex-shrink-0"></i>
                                            <span>{{ __('Custom Branding (Remove KD Analytiks logo)') }}</span>
                                        </li>
                                        <li class="flex items-start gap-3 text-gray-800">
                                            <i class="fa-solid fa-star text-amber-500 mt-1 flex-shrink-0"></i>
                                            <span>{{ __('Priority Support') }}</span>
                                        </li>
                                    @endif
                                    @if($isEnterprise)
                                        <li class="flex items-start gap-3 text-gray-800">
                                            <i class="fa-solid fa-file-export text-[#2271b1] mt-1 flex-shrink-0"></i>
                                            <span>{{ __('Full SPSS (.sav), Excel, JSON, XML Export Suite') }}</span>
                                        </li>
                                        <li class="flex items-start gap-3 text-gray-800">
                                            <i class="fa-solid fa-star text-amber-500 mt-1 flex-shrink-0"></i>
                                            <span>{{ __('Priority VIP Support') }}</span>
                                        </li>
                                    @endif
                                @endif
                            </ul>

                            {{-- Checkout Action Button --}}
                            <div class="mt-auto pt-6 border-t border-gray-100">
                                @if($isCurrent)
                                    <button type="button" disabled
                                        class="w-full bg-gray-100 text-gray-600 font-black py-4 rounded-2xl cursor-not-allowed tracking-widest text-xs uppercase border border-gray-200">
                                        {{ __('Current Plan') }}
                                    </button>

                                    @if(!$isFree && empty($isOrgMemberWithoutBilling))
                                        <div class="mt-4 text-center">
                                            <button type="button" @click="cancelModalOpen = true"
                                                class="text-xs font-bold text-red-500 hover:text-red-700 tracking-wider transition-colors">
                                                {{ __('Cancel / Downgrade to Free') }}
                                            </button>
                                        </div>
                                    @endif
                                @elseif(!empty($isOrgMemberWithoutBilling))
                                    <button type="button" disabled
                                        class="w-full bg-gray-100 text-gray-400 font-bold py-4 rounded-2xl cursor-not-allowed tracking-wider text-xs">
                                        {{ __('Managed by Org Admin') }}
                                    </button>
                                @else
                                    <button type="button" @click="openCheckoutModal({
                                                                    id: {{ $tier->id }},
                                                                    name: '{{ addslashes($tier->name) }}',
                                                                    slug: '{{ $tier->slug }}',
                                                                    isFree: {{ $isFree ? 'true' : 'false' }},
                                                                    kesMonthly: 'KES {{ number_format($tier->monthly_price, 0) }}',
                                                                    kesYearly: 'KES {{ number_format($tier->yearly_price, 0) }}',
                                                                    usdMonthly: '${{ number_format($tier->monthly_price_usd, 2) }}',
                                                                    usdYearly: '${{ number_format($tier->yearly_price_usd, 2) }}',
                                                                    btnLabel: '{{ addslashes($btnLabel) }}'
                                                                })"
                                        class="w-full {{ $isPopular ? 'bg-[#2271b1] text-white hover:bg-[#135e96] shadow-lg shadow-blue-500/20' : 'bg-slate-900 text-white hover:bg-slate-800' }} hover:scale-[1.02] active:scale-[0.98] transition-all py-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-md">
                                        {{ $btnLabel }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Hidden Cancel Form --}}
            <form id="cancel-form" action="{{ route('subscriptions.cancel') }}" method="POST" class="hidden">
                @csrf
            </form>

            {{-- Hidden Free Tier Form --}}
            <form id="free-tier-form" action="{{ route('subscriptions.checkout') }}" method="POST" class="hidden">
                @csrf
                <input type="hidden" name="tier_id" id="free-tier-id" value="">
                <input type="hidden" name="cycle" id="free-cycle" value="monthly">
                <input type="hidden" name="gateway" value="intasend">
                <input type="hidden" name="currency" value="KES">
            </form>

            {{-- Downgrade / Cancel Confirmation Modal --}}
            <template x-teleport="body">
                <div x-show="cancelModalOpen" x-cloak
                    class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
                    aria-modal="true" role="dialog">
                    <div x-show="cancelModalOpen" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0" @click="cancelModalOpen = false"
                        class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm"></div>

                    <div x-show="cancelModalOpen" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                        class="relative bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-gray-100 overflow-hidden text-left z-10">

                        <div
                            class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl mb-4">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>

                        <h3 class="text-xl font-black text-gray-900 tracking-tight">
                            {{ __('Confirm Subscription Downgrade?') }}
                        </h3>
                        <p class="text-xs text-gray-600 mt-2 leading-relaxed">
                            {{ __('Reverting to the Free tier will immediately adjust your quotas. You will lose access to:') }}
                        </p>

                        <ul
                            class="my-4 space-y-2 text-xs text-gray-700 bg-amber-50/60 p-4 rounded-2xl border border-amber-200/60">
                            <li class="flex items-center gap-2">
                                <i class="fa-solid fa-xmark text-red-500 font-bold"></i>
                                <span>{{ __('Socius AI Assistant & Research Chats') }}</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fa-solid fa-xmark text-red-500 font-bold"></i>
                                <span>{{ __('High-volume survey response collections') }}</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fa-solid fa-xmark text-red-500 font-bold"></i>
                                <span>{{ __('Audio/Video interview transcription suite') }}</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fa-solid fa-xmark text-red-500 font-bold"></i>
                                <span>{{ __('Advanced SPSS, Excel & XML data exports') }}</span>
                            </li>
                        </ul>

                        <div class="flex items-center justify-end gap-3 mt-6">
                            <button type="button" @click="cancelModalOpen = false"
                                class="px-5 py-2.5 rounded-xl border border-gray-300 text-xs font-bold text-gray-700 hover:bg-gray-100 transition-colors">
                                {{ __('Keep My Plan') }}
                            </button>
                            <button type="button" onclick="document.getElementById('cancel-form').submit();"
                                class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-md transition-colors">
                                {{ __('Yes, Revert to Free') }}
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Payment Gateway & Currency Selection Modal --}}
            <template x-teleport="body">
                <div x-show="checkoutModalOpen" x-cloak
                    class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
                    aria-modal="true" role="dialog">

                    {{-- Backdrop with blur --}}
                    <div x-show="checkoutModalOpen" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0" @click="checkoutModalOpen = false"
                        class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm"></div>

                    {{-- Modal Panel --}}
                    <div x-show="checkoutModalOpen" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                        class="relative bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-gray-100 overflow-hidden z-10">

                        {{-- Close Button --}}
                        <button type="button" @click="checkoutModalOpen = false"
                            class="absolute top-5 right-5 w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-800 flex items-center justify-center transition-colors">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>

                        {{-- Header --}}
                        <div class="text-left mb-6">
                            <div class="flex items-center gap-2 mb-1.5">
                                <span
                                    class="text-[10px] font-black tracking-widest uppercase bg-[#2271b1]/10 text-[#2271b1] px-3 py-1 rounded-full"
                                    x-text="modalTier ? modalTier.name : 'Plan Upgrade'"></span>
                                <span
                                    class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-700 bg-emerald-100 px-2.5 py-0.5 rounded-full"
                                    x-text="checkoutCycle === 'yearly' ? '{{ __('Yearly Billing (~17% Off)') }}' : '{{ __('Monthly Billing') }}'"></span>
                            </div>
                            <h3 class="text-2xl font-black text-gray-900 tracking-tight">
                                {{ __('Select Payment Method') }}
                            </h3>
                            <p class="text-xs text-gray-500 font-medium mt-1">
                                {{ __('Choose your preferred currency and payment gateway to complete your subscription.') }}
                            </p>
                        </div>

                        {{-- Downgrade Warning Notice --}}
                        <div x-show="isDowngrade" x-cloak
                            class="p-3.5 mb-5 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-3">
                            <i class="fa-solid fa-triangle-exclamation text-amber-500 mt-0.5 text-sm"></i>
                            <div class="text-xs text-amber-900">
                                <strong class="font-black">{{ __('Downgrade Warning') }}:</strong>
                                {{ __('You are choosing a lower-tier plan. Quota limits and features will adjust to this tier upon checkout.') }}
                            </div>
                        </div>

                        {{-- Billing Frequency Switcher Inside Modal --}}
                        <div
                            class="flex items-center justify-between p-3 bg-gray-50 border border-gray-200/80 rounded-2xl mb-6">
                            <span class="text-xs font-bold text-gray-700">{{ __('Billing Frequency:') }}</span>
                            <div class="inline-flex p-1 bg-gray-200/80 rounded-xl">
                                <button type="button" @click="checkoutCycle = 'monthly'"
                                    :class="checkoutCycle === 'monthly' ? 'bg-white text-gray-900 shadow-sm font-black' : 'text-gray-500 font-semibold hover:text-gray-900'"
                                    class="px-3.5 py-1.5 rounded-lg text-xs transition-all">
                                    {{ __('Monthly') }}
                                </button>
                                <button type="button" @click="checkoutCycle = 'yearly'"
                                    :class="checkoutCycle === 'yearly' ? 'bg-white text-gray-900 shadow-sm font-black' : 'text-gray-500 font-semibold hover:text-gray-900'"
                                    class="px-3.5 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1">
                                    <span>{{ __('Yearly') }}</span>
                                    <span
                                        class="text-[9px] bg-emerald-100 text-emerald-800 font-bold px-1.5 rounded-full">-17%</span>
                                </button>
                            </div>
                        </div>

                        {{-- Payment Gateway Card --}}
                        <div class="mb-6 p-5 bg-gradient-to-r from-blue-50/80 via-slate-50 to-indigo-50/60 border border-blue-200/80 rounded-2xl">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-black uppercase tracking-wider text-blue-900">{{ __('Secure Online Checkout') }}</span>
                                <span class="text-base font-black text-[#2271b1] px-3 py-1 bg-white rounded-xl shadow-sm border border-blue-100"
                                    x-text="(currency === 'USD') ? (checkoutCycle === 'monthly' ? modalTier?.usdMonthly : modalTier?.usdYearly) : (checkoutCycle === 'monthly' ? modalTier?.kesMonthly : modalTier?.kesYearly)"></span>
                            </div>

                            {{-- Accepted Methods Badges --}}
                            <div class="flex flex-wrap items-center gap-2 mb-3">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-200 rounded-xl text-xs font-bold text-gray-800 shadow-sm">
                                    <i class="fa-brands fa-cc-visa text-blue-600 text-sm"></i> Visa
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-200 rounded-xl text-xs font-bold text-gray-800 shadow-sm">
                                    <i class="fa-brands fa-cc-mastercard text-red-500 text-sm"></i> Mastercard
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-200 rounded-xl text-xs font-bold text-gray-800 shadow-sm">
                                    <i class="fa-brands fa-paypal text-[#003087] text-sm"></i> PayPal
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-200 rounded-xl text-xs font-bold text-gray-800 shadow-sm">
                                    <i class="fa-solid fa-building-columns text-slate-600 text-xs"></i> ACH / SEPA
                                </span>
                            </div>

                            <p class="text-[11px] text-blue-950/80 font-medium leading-relaxed flex items-start gap-2">
                                <i class="fa-solid fa-globe text-[#2271b1] mt-0.5 flex-shrink-0"></i>
                                <span>{{ __('International & Global payments: Credit/Debit Cards, PayPal, and Bank Transfers (ACH/SEPA) are supported directly on the secure checkout screen.') }}</span>
                            </p>
                        </div>

                        {{-- Checkout Form Submission --}}
                        <form action="{{ route('subscriptions.checkout') }}" method="POST">
                            @csrf
                            <input type="hidden" name="tier_id" :value="modalTier ? modalTier.id : ''">
                            <input type="hidden" name="cycle" :value="checkoutCycle">
                            <input type="hidden" name="gateway" value="intasend">
                            <input type="hidden" name="currency" :value="currency">

                            <button type="submit"
                                class="w-full bg-[#2271b1] hover:bg-[#135e96] text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg shadow-blue-500/25 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                                <span>{{ __('Proceed to Secure Checkout') }}</span>
                                <i class="fa-solid fa-arrow-right text-xs"></i>
                            </button>
                        </form>

                        <div
                            class="mt-4 flex items-center justify-center gap-2 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                            <i class="fa-solid fa-lock text-emerald-600"></i>
                            <span>{{ __('256-Bit SSL Encrypted & Secure Checkout') }}</span>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Support & Custom Quotes Banner --}}
            <div
                class="mt-20 bg-white rounded-3xl p-8 sm:p-10 border border-gray-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-8">
                <div class="max-w-xl">
                    <h4 class="text-xl font-black text-gray-900 mb-2">{{ __('Need a custom institutional plan?') }}</h4>
                    <p class="text-gray-500 text-xs sm:text-sm font-medium">
                        {{ __('We support tailored packages for universities, non-profits, large research firms, and wire remittances. Contact our team anytime.') }}
                    </p>
                </div>
                <a href="mailto:infokdanalytiks@gmail.com"
                    class="px-8 py-4 bg-gray-100 hover:bg-gray-200 text-slate-800 font-black rounded-2xl transition-colors uppercase tracking-widest text-xs whitespace-nowrap">
                    {{ __('Contact Support') }}
                </a>
            </div>
        </div>
    </div>
@endsection