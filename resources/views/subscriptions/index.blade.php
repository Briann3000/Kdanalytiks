@extends('layouts.app')

@section('content')
<div class="py-12 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-base font-black text-indigo-600 tracking-widest uppercase mb-4">Pricing Plans</h2>
            <p class="text-5xl font-black text-gray-900 mb-6 tracking-tight">Scale your research with <span class="text-indigo-600">Premium Tiers</span></p>
            <p class="max-w-2xl mx-auto text-xl text-gray-500 font-medium">Choose the perfect plan for your organization and start gathering high-impact insights today.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch">
            @php
                $currentTier = $tiers->firstWhere('id', optional($organization)->subscription_tier_id) ?? $tiers->firstWhere('slug', 'free');
            @endphp
            @foreach($tiers as $tier)
                @php
                    $isCurrent = $organization && $organization->subscription_tier_id == $tier->id;
                    $isPro = strtolower($tier->slug) === 'pro';
                    $btnLabel = 'Upgrade Now';
                    if ($tier->monthly_price < $currentTier->monthly_price) {
                        $btnLabel = 'Downgrade';
                    } elseif ($tier->monthly_price == 0) {
                        $btnLabel = 'Get Started';
                    }
                @endphp
                <div class="relative flex flex-col bg-white rounded-3xl shadow-xl transition-all duration-500 hover:scale-[1.03] hover:shadow-2xl overflow-hidden {{ $isPro ? 'border-4 border-indigo-500 z-10' : 'border border-gray-100' }}">
                    @if($isPro)
                        <div class="absolute top-0 right-0 bg-indigo-500 text-white px-6 py-2 rounded-bl-3xl font-black text-[10px] uppercase tracking-widest shadow-lg">Most Popular</div>
                    @endif

                    <div class="p-10 flex-1 flex flex-col">
                        <div class="mb-8">
                            <h3 class="text-2xl font-black text-gray-900 uppercase tracking-tighter mb-2">{{ $tier->name }}</h3>
                            <div class="flex items-baseline gap-1">
                                <span class="text-4xl font-black text-gray-900 tracking-tight">KES {{ number_format($tier->monthly_price, 0) }}</span>
                                <span class="text-gray-400 font-bold uppercase text-[10px] tracking-widest">/ Month</span>
                            </div>
                        </div>

                        <ul class="space-y-5 mb-10 flex-1">
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex-shrink-0 w-5 h-5 bg-indigo-50 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-check text-[10px] text-indigo-600"></i>
                                </div>
                                <span class="text-gray-600 font-medium"><strong>{{ $tier->max_surveys === -1 ? 'Unlimited' : $tier->max_surveys }}</strong> Surveys per Month</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex-shrink-0 w-5 h-5 bg-indigo-50 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-check text-[10px] text-indigo-600"></i>
                                </div>
                                <span class="text-gray-600 font-medium"><strong>{{ $tier->ai_limit_per_month === -1 ? 'Unlimited' : $tier->ai_limit_per_month }}</strong> AI Generations</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex-shrink-0 w-5 h-5 bg-indigo-50 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-check text-[10px] text-indigo-600"></i>
                                </div>
                                <span class="text-gray-600 font-medium">Multi-user Collaborations</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 flex-shrink-0 w-5 h-5 bg-indigo-50 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-check text-[10px] text-indigo-600"></i>
                                </div>
                                <span class="text-gray-600 font-medium">Advanced Analytics & Exports</span>
                            </li>
                        </ul>

                        <div class="mt-auto">
                            <form action="{{ route('subscriptions.checkout') }}" method="POST">
                                @csrf
                                <input type="hidden" name="tier_id" value="{{ $tier->id }}">
                                
                                @if($isCurrent)
                                    <button type="button" disabled class="w-full bg-indigo-50 text-indigo-600 font-black py-5 rounded-2xl cursor-not-allowed uppercase tracking-widest text-xs border border-indigo-100">
                                        Current Plan
                                    </button>
                                    @if($tier->slug !== 'free')
                                        <div class="mt-4 border-t border-gray-100 pt-4 text-center">
                                            <button type="button" 
                                                onclick="if(confirm('Are you sure you want to cancel your premium subscription? You will be reverted to the Free tier.')) document.getElementById('cancel-form').submit();"
                                                class="text-[10px] font-black text-red-500 uppercase tracking-widest hover:text-red-700 transition-colors">
                                                Cancel Membership
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
                <h4 class="text-2xl font-black text-gray-900 mb-2">Need a custom enterprise solution?</h4>
                <p class="text-gray-500 font-medium">For large organizations with unique requirements, custom integrations, and unlimited scale, contact our sales team for a tailored quote.</p>
            </div>
            <a href="mailto:info@kmsurveytool.com" class="px-8 py-4 bg-indigo-50 text-indigo-700 font-black rounded-2xl hover:bg-indigo-100 transition-colors uppercase tracking-widest text-xs">
                Contact Sales
            </a>
        </div>
    </div>
</div>
@endsection
