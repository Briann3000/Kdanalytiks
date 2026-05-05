@extends('layouts.app')

@section('content')
<div x-data="{ billingCycle: 'monthly' }" class="py-12 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-base font-black text-indigo-600 tracking-widest uppercase mb-4">{{ __('Pricing Plans') }}</h2>
            <p class="text-5xl font-black text-gray-900 mb-2 tracking-tight">{{ __('Scale your research with') }} <span class="text-indigo-600">{{ __('Premium Tiers') }}</span></p>
            
            <!-- Billing Toggle -->
            <div class="flex items-center justify-center mt-10 gap-4">
                <span class="text-sm font-black uppercase tracking-widest" :class="billingCycle === 'monthly' ? 'text-gray-900' : 'text-gray-400'">{{ __('Monthly') }}</span>
                <button @click="billingCycle = billingCycle === 'monthly' ? 'yearly' : 'monthly'" 
                        class="relative w-16 h-8 rounded-full transition-colors duration-300 focus:outline-none"
                        :class="billingCycle === 'yearly' ? 'bg-indigo-600' : 'bg-gray-300'">
                    <div class="absolute top-1 left-1 w-6 h-6 bg-white rounded-full transition-transform duration-300"
                         :class="billingCycle === 'yearly' ? 'translate-x-8' : 'translate-x-0'"></div>
                </button>
                <span class="text-sm font-black uppercase tracking-widest flex items-center gap-2" :class="billingCycle === 'yearly' ? 'text-gray-900' : 'text-gray-400'">
                    {{ __('Yearly') }}
                    <span class="bg-green-100 text-green-700 text-[10px] px-2 py-1 rounded-full animate-bounce">{{ __('Save 20%') }}</span>
                </span>
            </div>

            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-8 mb-4">{{ __('Account Type') }}: {{ __($accountTypeLabel ?? 'Researcher') }}</p>
            <p class="max-w-2xl mx-auto text-xl text-gray-500 font-medium">{{ __('Choose the perfect plan for your research needs and start gathering high-impact insights today.') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch">
            @php
                $currentTierId = $entity->subscription_tier_id ?? ($tiers->firstWhere('slug', 'free')->id ?? null);
            @endphp
            @foreach($tiers as $tier)
                @php
                    $isCurrent = $currentTierId == $tier->id;
                    $isPro = strtolower($tier->slug) === 'pro';
                    $btnLabel = __('Upgrade Now');
                    if ($tier->monthly_price < ($tiers->firstWhere('id', $currentTierId)->monthly_price ?? 0)) {
                        $btnLabel = __('Downgrade');
                    }
                @endphp
                <div class="relative flex flex-col bg-white rounded-3xl shadow-xl transition-all duration-500 hover:scale-[1.03] hover:shadow-2xl overflow-hidden {{ $isPro ? 'border-4 border-indigo-500 z-10' : 'border border-gray-100' }}">
                    @if($isPro)
                        <div class="absolute top-0 right-0 bg-indigo-500 text-white px-6 py-2 rounded-bl-3xl font-black text-[10px] uppercase tracking-widest shadow-lg">{{ __('Most Popular') }}</div>
                    @endif

                    <div class="p-10 flex-1 flex flex-col">
                        <div class="mb-8">
                            <h3 class="text-2xl font-black text-gray-900 uppercase tracking-tighter mb-2">{{ $tier->name }}</h3>
                            <div class="flex items-baseline gap-1">
                                <span class="text-4xl font-black text-gray-900 tracking-tight" x-text="billingCycle === 'monthly' ? 'KES {{ number_format($tier->monthly_price, 0) }}' : 'KES {{ number_format($tier->yearly_price, 0) }}'"></span>
                                <span class="text-gray-400 font-bold uppercase text-[10px] tracking-widest" x-text="billingCycle === 'monthly' ? '{{ __('/ Month') }}' : '{{ __('/ Year') }}'"></span>
                            </div>
                        </div>

                        <ul class="space-y-5 mb-10 flex-1">
                            @if(isset($accountTypeLabel) && str_contains($accountTypeLabel, 'Respondent'))
                                <li class="flex items-start gap-3">
                                    <div class="mt-1 flex-shrink-0 w-5 h-5 bg-indigo-50 rounded-full flex items-center justify-center">
                                        <i class="fa-solid fa-check text-[10px] text-indigo-600"></i>
                                    </div>
                                    <span class="text-gray-600 font-medium">{{ __('Full Access to Qualitative Insights') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="mt-1 flex-shrink-0 w-5 h-5 bg-indigo-50 rounded-full flex items-center justify-center">
                                        <i class="fa-solid fa-check text-[10px] text-indigo-600"></i>
                                    </div>
                                    <span class="text-gray-600 font-medium">{{ __('Unlimited Research Proposal Previews') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="mt-1 flex-shrink-0 w-5 h-5 bg-indigo-50 rounded-full flex items-center justify-center">
                                        <i class="fa-solid fa-check text-[10px] text-indigo-600"></i>
                                    </div>
                                    <span class="text-gray-600 font-medium">{{ __('Export Draft Reports (PDF/DOCX)') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="mt-1 flex-shrink-0 w-5 h-5 bg-indigo-50 rounded-full flex items-center justify-center">
                                        <i class="fa-solid fa-check text-[10px] text-indigo-600"></i>
                                    </div>
                                    <span class="text-gray-600 font-medium">{{ __('Premium Methodology Insights') }}</span>
                                </li>
                            @else
                                <li class="flex items-start gap-3">
                                    <div class="mt-1 flex-shrink-0 w-5 h-5 bg-indigo-50 rounded-full flex items-center justify-center">
                                        <i class="fa-solid fa-check text-[10px] text-indigo-600"></i>
                                    </div>
                                     <span class="text-gray-600 font-medium"><strong>{{ $tier->max_surveys == -1 ? __('Unlimited') : $tier->max_surveys }}</strong> {{ __('Surveys per Month') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="mt-1 flex-shrink-0 w-5 h-5 bg-indigo-50 rounded-full flex items-center justify-center">
                                        <i class="fa-solid fa-check text-[10px] text-indigo-600"></i>
                                    </div>
                                     <span class="text-gray-600 font-medium"><strong>{{ $tier->max_responses_per_survey == -1 ? __('Unlimited') : $tier->max_responses_per_survey }}</strong> {{ __('Responses per Survey') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="mt-1 flex-shrink-0 w-5 h-5 bg-indigo-50 rounded-full flex items-center justify-center">
                                        <i class="fa-solid fa-check text-[10px] text-indigo-600"></i>
                                    </div>
                                     <span class="text-gray-600 font-medium"><strong>{{ $tier->ai_limit_per_month == -1 ? __('Unlimited') : $tier->ai_limit_per_month }}</strong> {{ __('AI Generations / mo') }}</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <div class="mt-1 flex-shrink-0 w-5 h-5 bg-indigo-50 rounded-full flex items-center justify-center">
                                        <i class="fa-solid fa-check text-[10px] text-indigo-600"></i>
                                    </div>
                                    <span class="text-gray-600 font-medium">{{ __('Project Sharing & Team Collaboration') }}</span>
                                </li>
                                @if($tier->slug !== 'free')
                                    <li class="flex items-start gap-3">
                                        <div class="mt-1 flex-shrink-0 w-5 h-5 bg-indigo-50 rounded-full flex items-center justify-center">
                                            <i class="fa-solid fa-check text-[10px] text-indigo-600"></i>
                                        </div>
                                        <span class="text-gray-600 font-medium">{{ __('Custom Branding (Use your logo)') }}</span>
                                    </li>
                                @endif
                                @if(strtolower($tier->slug) === 'enterprise')
                                    <li class="flex items-start gap-3">
                                        <div class="mt-1 flex-shrink-0 w-5 h-5 bg-indigo-50 rounded-full flex items-center justify-center">
                                            <i class="fa-solid fa-check text-[10px] text-indigo-600"></i>
                                        </div>
                                        <span class="text-gray-600 font-medium">{{ __('24/7 Priority VIP Support') }}</span>
                                    </li>
                                @endif
                                <li class="flex items-start gap-3">
                                    <div class="mt-1 flex-shrink-0 w-5 h-5 bg-indigo-50 rounded-full flex items-center justify-center">
                                        <i class="fa-solid fa-check text-[10px] text-indigo-600"></i>
                                    </div>
                                    <span class="text-gray-600 font-medium">
                                        @if(strtolower($tier->slug) === 'free')
                                            {{ __('Basic Exports (CSV, PDF)') }}
                                        @elseif(strtolower($tier->slug) === 'pro')
                                            {{ __('Pro Exports (Excel, JSON, PDF)') }}
                                        @else
                                            {{ __('Full Export Suite (SPSS, Google Sheets, XML)') }}
                                        @endif
                                    </span>
                                </li>
                            @endif
                        </ul>

                        <div class="mt-auto">
                            <form action="{{ route('subscriptions.checkout') }}" method="POST">
                                @csrf
                                <input type="hidden" name="tier_id" value="{{ $tier->id }}">
                                <input type="hidden" name="cycle" x-model="billingCycle">
                                
                                @if($isCurrent)
                                    <button type="button" disabled class="w-full bg-indigo-50 text-indigo-600 font-black py-5 rounded-2xl cursor-not-allowed uppercase tracking-widest text-xs border border-indigo-100">
                                        {{ __('Current Plan') }}
                                    </button>
                                    @if($tier->slug !== 'free')
                                        <div class="mt-4 border-t border-gray-100 pt-4 text-center">
                                            <button type="button" 
                                                onclick="if(confirm('{{ __('Are you sure you want to cancel your premium subscription? You will be reverted to the Free tier.') }}')) document.getElementById('cancel-form').submit();"
                                                class="text-[10px] font-black text-red-500 uppercase tracking-widest hover:text-red-700 transition-colors">
                                                {{ __('Cancel Membership') }}
                                            </button>
                                        </div>
                                    @endif
                                @else
                                    <button type="submit" class="w-full {{ $isPro ? 'bg-indigo-600 text-white shadow-indigo-200' : 'bg-gray-900 text-white shadow-gray-200' }} hover:scale-[1.02] active:scale-[0.98] transition-all font-black py-5 rounded-2xl shadow-xl uppercase tracking-widest text-xs">
                                        {{ $btnLabel }}
                                    </button>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <form id="cancel-form" action="{{ route('subscriptions.cancel') }}" method="POST" class="hidden">
            @csrf
        </form>

        <div class="mt-20 bg-white rounded-3xl p-10 border border-gray-100 shadow-sm flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="max-w-xl">
                <h4 class="text-2xl font-black text-gray-900 mb-2">{{ __('Need a custom research solution?') }}</h4>
                <p class="text-gray-500 font-medium">{{ __('For large institutions with unique requirements, custom integrations, and unlimited scale, contact our team for a tailored quote.') }}</p>
            </div>
            <a href="mailto:info@kmsurveytool.com" class="px-8 py-4 bg-indigo-50 text-indigo-700 font-black rounded-2xl hover:bg-indigo-100 transition-colors uppercase tracking-widest text-xs">
                {{ __('Contact Sales') }}
            </a>
        </div>
    </div>
</div>
@endsection
