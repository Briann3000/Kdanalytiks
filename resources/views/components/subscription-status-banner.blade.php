@props([
    'showOnFree' => false,
    'compact' => false
])

@php
    $user = auth()->user();
    $status = $user ? $user->getSubscriptionStatusDetails() : null;
@endphp

@if($status)
    @if($status['is_active'])
        @if($status['is_expiring_soon'])
            {{-- Expiring Soon Warning --}}
            <div
                class="{{ $compact ? 'p-3 text-xs' : 'p-4 sm:p-5' }} bg-amber-500/10 border border-amber-500/30 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-600 flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="text-xs font-black uppercase tracking-wider text-amber-700 bg-amber-200/60 px-2 py-0.5 rounded-md">Expiring
                                Soon</span>
                            <span class="text-xs font-bold text-gray-800">{{ $status['tier_name'] }} Plan</span>
                        </div>
                        <p class="text-xs font-medium text-amber-900/80 mt-0.5">
                            Your subscription will end in <strong
                                class="font-extrabold text-amber-950">{{ $status['days_remaining'] }}
                                day{{ $status['days_remaining'] == 1 ? '' : 's' }}</strong> (on {{ $status['expiry_formatted'] }}).
                            Renew now to maintain uninterrupted access.
                        </p>
                    </div>
                </div>
                <a href="{{ route('subscriptions.index') }}"
                    class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-md transition-all whitespace-nowrap">
                    Renew Subscription &rsaquo;
                </a>
            </div>
        @else
            {{-- Active Subscription Banner / Badge --}}
            <div
                class="{{ $compact ? 'p-2.5 text-[11px]' : 'p-3 sm:p-4' }} bg-slate-900 text-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-6 shadow-sm border border-slate-800">
                <div class="flex items-center gap-2.5">
                    <span
                        class="text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded-md">Active</span>
                    <span class="text-xs font-extrabold">{{ $status['tier_name'] }} Plan</span>
                    <span class="text-slate-400 text-xs">•</span>
                    <span class="text-xs text-slate-300">
                        Active until <strong class="text-white">{{ $status['expiry_formatted'] }}</strong>
                        ({{ $status['days_remaining'] }} days remaining)
                    </span>
                </div>
                <a href="{{ route('subscriptions.index') }}"
                    class="text-[10px] font-bold text-slate-400 hover:text-white tracking-wider transition-colors ml-auto sm:ml-0">
                    Manage Plan &rsaquo;
                </a>
            </div>
        @endif
    @elseif($status['is_expired'])
        {{-- Subscription Expired Alert --}}
        <div
            class="p-4 sm:p-5 bg-red-500/10 border border-red-500/30 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-red-500/20 text-red-600 flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span
                            class="text-xs font-black uppercase tracking-wider text-red-700 bg-red-200/60 px-2 py-0.5 rounded-md">Subscription
                            Expired</span>
                        <span class="text-xs font-bold text-gray-800">{{ $status['tier_name'] }} Plan</span>
                    </div>
                    <p class="text-xs font-medium text-red-900/80 mt-0.5">
                        Your subscription ended on <strong
                            class="font-extrabold text-red-950">{{ $status['expiry_formatted'] }}</strong>. Renew now to restore
                        full access to this feature.
                    </p>
                </div>
            </div>
            <a href="{{ route('subscriptions.index') }}"
                class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-md transition-all whitespace-nowrap">
                Renew Now &rsaquo;
            </a>
        </div>
    @elseif($showOnFree)
        {{-- Free Tier Upgrade CTA (Optional) --}}
        <div class="p-3 bg-[#2271b1]/5 border border-[#2271b1]/20 rounded-xl flex items-center justify-between gap-3 mb-4">
            <span class="text-xs text-gray-600 font-medium">
                You are on the <strong class="text-gray-900">Free Tier</strong>. Upgrade for unlimited access.
            </span>
            <a href="{{ route('subscriptions.index') }}"
                class="text-[10px] font-black text-[#2271b1] hover:underline uppercase tracking-wider">
                Upgrade &rsaquo;
            </a>
        </div>
    @endif
@endif