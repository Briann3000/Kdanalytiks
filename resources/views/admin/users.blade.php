@extends('layouts.app')

@section('title', __('Manage Users'))

@section('content')
    @php /** @var \Illuminate\Pagination\LengthAwarePaginator $users */ @endphp
    <div class="px-4 sm:px-0">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">{{ __('User Management') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Monitor and manage all system users and their access levels.') }}</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.users.create') }}"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-[#2271b1] hover:bg-[#135e96] transition-colors shadow-zinc-200/50">
                    <i class="fa-solid fa-plus mr-2"></i> {{ __('Create New User') }}
                </a>
                <span
                    class="inline-flex items-center px-4 py-2 border border-gray-200 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white">
                    {{ __('Total') }}: {{ $users->total() }} {{ __('Users') }}
                </span>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-white shadow rounded-lg border border-gray-100 p-6 mb-8">
            <form action="{{ route('admin.users.index') }}" method="GET"
                class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700">{{ __('Search') }}</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
                        </div>
                        <input type="text" name="search" id="search" value="{{ request('search') }}"
                            class="focus:ring-[#2271b1] focus:border-[#2271b1] block w-full pl-10 sm:text-sm border-gray-300 rounded-md"
                            placeholder="{{ __('Name or email...') }}">
                    </div>
                </div>

                <div class="sm:col-span-1">
                    <label for="role" class="block text-sm font-medium text-gray-700">{{ __('Role') }}</label>
                    <select name="role" id="role"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-[#2271b1] focus:border-[#2271b1] sm:text-sm rounded-md">
                        <option value="all">{{ __('All Roles') }}</option>
                        @foreach(['admin', 'independent', 'organization', 'respondent'] as $r)
                            <option value="{{ $r }}" {{ request('role') == $r ? 'selected' : '' }}>{{ __(ucfirst($r)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-1">
                    <label for="tier" class="block text-sm font-medium text-gray-700">{{ __('Plan / Tier') }}</label>
                    <select name="tier" id="tier"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-[#2271b1] focus:border-[#2271b1] sm:text-sm rounded-md">
                        <option value="all">{{ __('All Plans') }}</option>
                        @foreach($tiers as $t)
                            <option value="{{ $t->id }}" {{ request('tier') == $t->id ? 'selected' : '' }}>{{ $t->name }} ({{ strtoupper($t->slug) }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-1">
                    <label for="status" class="block text-sm font-medium text-gray-700">{{ __('Status') }}</label>
                    <select name="status" id="status"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-[#2271b1] focus:border-[#2271b1] sm:text-sm rounded-md">
                        <option value="active" {{ (!request()->filled('status') || request('status') == 'active') ? 'selected' : '' }}>{{ __('Active Only') }}</option>
                        <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>{{ __('Suspended') }}</option>
                        <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>{{ __('All (Incl. Suspended)') }}</option>
                    </select>
                </div>

                <div class="sm:col-span-1 flex items-end">
                    <button type="submit"
                        class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[#2271b1] hover:bg-[#135e96] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#2271b1]">
                        {{ __('Filter') }}
                    </button>
                </div>
            </form>
        </div>

        @if(session('success'))
            <div class="rounded-md bg-green-50 p-4 mb-6 border border-green-200 shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fa-solid fa-circle-check text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Users Table Container with Alpine Data -->
        <div x-data="{
            openSubModal: false,
            activeUser: null,
            targetTierId: 1,
            expiryPreset: 'plus_30',
            customExpiry: '',
            resetCounters: true,
            allTiers: {{ json_encode($tiers) }},
            get availableTiers() {
                if (!this.activeUser) return this.allTiers;
                const role = (this.activeUser.role || '').toLowerCase();
                if (role === 'respondent') {
                    return this.allTiers.filter(t => ['free', 'respondent-pro'].includes(t.slug));
                }
                if (role === 'organization') {
                    return this.allTiers.filter(t => ['org-free', 'org-pro', 'org-enterprise'].includes(t.slug));
                }
                return this.allTiers.filter(t => ['free', 'pro', 'enterprise'].includes(t.slug));
            },
            initModal(user, tierId, expiry) {
                this.activeUser = user;
                this.expiryPreset = 'plus_30';
                this.customExpiry = expiry ? expiry.substring(0, 10) : '';
                this.resetCounters = true;
                
                // Ensure target tier matches user role
                const validTiers = this.availableTiers;
                const match = validTiers.find(t => t.id === tierId);
                this.targetTierId = match ? match.id : (validTiers[0]?.id || 1);
                
                this.openSubModal = true;
                this.$nextTick(() => {
                    this.renderTierOptions();
                });
            },
            renderTierOptions() {
                const selectEl = document.getElementById('admin_subscription_tier_select');
                if (!selectEl) return;
                selectEl.innerHTML = '';
                const validTiers = this.availableTiers;
                validTiers.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = `${t.name} (${t.slug.toUpperCase()}) — KES ${Number(t.monthly_price).toLocaleString()}/mo / $${Number(t.monthly_price_usd).toLocaleString()}`;
                    if (Number(t.id) === Number(this.targetTierId)) {
                        opt.selected = true;
                    }
                    selectEl.appendChild(opt);
                });
            }
        }">
            <div class="bg-white shadow rounded-lg border border-gray-100 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('User Info') }}
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Role') }}</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Subscription Plan') }}</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">{{ __('Joined') }}
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($users as $user)
                            @php
                                $entity = $user->resolvedSubscriptionEntity();
                                $tier = $entity?->subscriptionTier ?? $user->subscriptionTier;
                                $tierName = $tier?->name ?? 'Free';
                                $isActive = $user->hasActiveSubscription();
                                $isExpired = $user->isSubscriptionExpired();
                                $expiryDate = $entity?->subscription_expiry ?? $user->subscription_expiry;
                                $daysLeft = $user->subscriptionDaysRemaining();
                                $paymentStatus = $entity?->payment_status ?? $user->payment_status ?? 'unpaid';
                                $statusVal = $user->status instanceof \BackedEnum ? $user->status->value : $user->status;
                                $roleVal = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;
                            @endphp
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div
                                            class="h-10 w-10 flex-shrink-0 bg-zinc-100 rounded-full flex items-center justify-center">
                                            <i class="fa-solid fa-user text-zinc-500"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-bold text-gray-900">{{ $user->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ __(ucfirst($roleVal)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $isActive ? (str_contains(strtolower($tierName), 'enterprise') ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-blue-100 text-blue-800 border border-blue-200') : ($isExpired ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-gray-100 text-gray-700') }}">
                                                <i class="fa-solid {{ $isActive ? 'fa-gem' : ($isExpired ? 'fa-clock' : 'fa-seedling') }} mr-1"></i>
                                                {{ $tierName }}
                                            </span>
                                            @if($paymentStatus === 'paid')
                                                <span class="text-[10px] px-1.5 py-0.5 bg-emerald-50 text-emerald-700 font-semibold rounded uppercase">Paid</span>
                                            @elseif($paymentStatus)
                                                <span class="text-[10px] px-1.5 py-0.5 bg-zinc-100 text-zinc-600 font-semibold rounded uppercase">{{ $paymentStatus }}</span>
                                            @endif
                                        </div>
                                        @if($expiryDate)
                                            <span class="text-xs mt-1 {{ $isExpired ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                                                {{ $isExpired ? 'Expired on ' . $expiryDate->format('M d, Y') : 'Expires ' . $expiryDate->format('M d, Y') . ' (' . $daysLeft . 'd)' }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400 mt-0.5">Standard Lifetime</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusVal === 'active' ? 'bg-green-100 text-green-800' : ($statusVal === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ __(ucfirst($statusVal)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $user->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <!-- Manage Plan Button -->
                                    <button type="button"
                                        @click="initModal({{ json_encode(['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $roleVal, 'update_url' => route('admin.users.subscription', $user)]) }}, {{ $tier?->id ?? 1 }}, '{{ $expiryDate ? $expiryDate->toIso8601String() : '' }}')"
                                        class="inline-flex items-center px-2.5 py-1 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded text-xs font-bold hover:bg-indigo-100 transition-colors shadow-sm">
                                        <i class="fa-solid fa-crown mr-1"></i> {{ __('Plan') }}
                                    </button>

                                    @if($user->role != 'admin')
                                        <form action="{{ route('admin.users.status', $user) }}" method="POST" class="inline-block">
                                            @csrf
                                            <input type="hidden" name="status"
                                                value="{{ $statusVal === 'active' ? 'suspended' : 'active' }}">
                                            @if($statusVal === 'active')
                                                <button type="submit" class="inline-flex items-center px-2 py-1 bg-red-50 text-red-700 rounded text-[10px] font-bold uppercase hover:bg-red-100 transition-colors">
                                                    <i class="fa-solid fa-user-slash mr-1"></i> {{ __('Suspend') }}
                                                </button>
                                            @else
                                                <button type="submit" class="inline-flex items-center px-2 py-1 bg-green-50 text-green-700 rounded text-[10px] font-bold uppercase hover:bg-green-100 transition-colors">
                                                    <i class="fa-solid fa-user-check mr-1"></i> {{ __('Activate') }}
                                                </button>
                                            @endif
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    {{ $users->withQueryString()->links() }}
                </div>
            </div>

            <!-- Manage Subscription Modal (Teleported to Body) -->
            <template x-teleport="body">
                <div x-show="openSubModal" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div x-show="openSubModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="openSubModal = false" aria-hidden="true"></div>

                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                        <div x-show="openSubModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200">
                            <form :action="activeUser ? activeUser.update_url : '#'" method="POST">
                                @csrf
                                <div class="bg-slate-900 px-6 py-5 text-white">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2.5">
                                            <div class="w-8 h-8 rounded-xl bg-amber-400/20 text-amber-400 flex items-center justify-center">
                                                <i class="fa-solid fa-crown text-base"></i>
                                            </div>
                                            <div>
                                                <h3 class="text-base font-black tracking-tight text-white" id="modal-title">{{ __('Manage User Subscription') }}</h3>
                                                <p class="text-xs text-slate-300 font-medium mt-0.5">
                                                    <span x-text="activeUser ? activeUser.name + ' (' + activeUser.email + ')' : ''"></span>
                                                    <span class="inline-block ml-1.5 px-2 py-0.5 bg-slate-800 text-amber-300 font-bold rounded text-[10px] uppercase" x-text="activeUser ? activeUser.role : ''"></span>
                                                </p>
                                            </div>
                                        </div>
                                        <button type="button" @click="openSubModal = false" class="text-slate-400 hover:text-white transition-colors p-1.5 rounded-lg hover:bg-slate-800">
                                            <i class="fa-solid fa-xmark text-lg"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="p-6 space-y-5 bg-white">
                                    <!-- Tier Selection -->
                                    <div>
                                        <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-1.5">
                                            {{ __('Subscription Plan / Tier') }}
                                        </label>
                                        <select id="admin_subscription_tier_select" name="subscription_tier_id" x-model.number="targetTierId" @change="targetTierId = Number($event.target.value)"
                                            class="w-full rounded-xl border-2 border-slate-300 focus:border-[#2271b1] focus:ring-4 focus:ring-[#2271b1]/15 bg-white text-sm font-bold text-slate-900 py-3 px-3.5 shadow-sm transition-all outline-none">
                                        </select>
                                    </div>

                                    <!-- Expiry Presets -->
                                    <div>
                                        <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-1.5">
                                            {{ __('Duration / Expiration') }}
                                        </label>
                                        <select name="expiry_preset" x-model="expiryPreset"
                                            class="w-full rounded-xl border-2 border-slate-300 focus:border-[#2271b1] focus:ring-4 focus:ring-[#2271b1]/15 bg-white text-sm font-bold text-slate-900 py-3 px-3.5 shadow-sm transition-all outline-none">
                                            <option value="plus_30">{{ __('+30 Days (Standard 1 Month)') }}</option>
                                            <option value="plus_90">{{ __('+90 Days (Quarterly)') }}</option>
                                            <option value="plus_365">{{ __('+365 Days (Annual Plan)') }}</option>
                                            <option value="never">{{ __('10 Years / Lifetime VIP') }}</option>
                                            <option value="unchanged">{{ __('Keep Current Expiry Date') }}</option>
                                            <option value="custom">{{ __('Custom Expiry Date...') }}</option>
                                        </select>
                                    </div>

                                    <!-- Custom Date Field (if custom selected) -->
                                    <div x-show="expiryPreset === 'custom'" x-cloak class="p-4 bg-slate-50 border-2 border-slate-200 rounded-2xl">
                                        <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-1.5">
                                            {{ __('Custom Expiration Date') }}
                                        </label>
                                        <input type="date" name="custom_expiry" x-model="customExpiry"
                                            class="w-full rounded-xl border-2 border-slate-300 focus:border-[#2271b1] focus:ring-4 focus:ring-[#2271b1]/15 bg-white text-sm font-bold text-slate-900 py-2.5 px-3.5 shadow-sm transition-all outline-none">
                                    </div>

                                    <!-- Reset Counters Checkbox -->
                                    <div class="bg-slate-50 p-4 rounded-2xl border-2 border-slate-200">
                                        <label class="flex items-start text-xs font-bold text-slate-800 cursor-pointer">
                                            <input type="checkbox" name="reset_counters" value="1" x-model="resetCounters" class="rounded-lg border-2 border-slate-400 text-[#2271b1] focus:ring-[#2271b1] h-4 w-4 mt-0.5 mr-2.5">
                                            <span>{{ __('Reset monthly usage counters (Transcription, Proofreading, AI Tokens & Scans)') }}</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="bg-slate-50 px-6 py-4 flex items-center justify-end space-x-3 border-t border-slate-200">
                                    <button type="button" @click="openSubModal = false" class="px-5 py-2.5 border-2 border-slate-300 rounded-xl text-xs font-black uppercase tracking-wider text-slate-700 hover:bg-slate-200 transition-colors">
                                        {{ __('Cancel') }}
                                    </button>
                                    <button type="submit" class="px-6 py-2.5 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-md hover:shadow-lg transition-all">
                                        <i class="fa-solid fa-check mr-1.5"></i> {{ __('Save & Update Plan') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="mt-6">
            <a href="{{ route('admin.dashboard') }}"
                class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors">
                <i class="fa-solid fa-arrow-left mr-2"></i> {{ __('Back to Dashboard') }}
            </a>
        </div>
    </div>
@endsection