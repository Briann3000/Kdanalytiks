@extends('layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
            <!-- Premium Balance card -->
            <div class="md:col-span-1">
                <div class="relative bg-slate-900 rounded-[2.5rem] p-1 shadow-2xl overflow-hidden group">
                    <!-- Dynamic Gradients -->
                    <div
                        class="absolute -right-20 -top-20 w-64 h-64 bg-indigo-600 rounded-full blur-[100px] opacity-40 group-hover:opacity-60 transition-opacity duration-1000">
                    </div>
                    <div
                        class="absolute -left-20 -bottom-20 w-64 h-64 bg-blue-500 rounded-full blur-[100px] opacity-30 group-hover:opacity-50 transition-opacity duration-1000">
                    </div>

                    <div class="relative bg-slate-900/40 backdrop-blur-3xl rounded-[2.25rem] p-8 border border-white/10">
                        <div class="flex items-center justify-between mb-8">
                            <div
                                class="w-12 h-12 bg-white/5 rounded-2xl flex items-center justify-center border border-white/10">
                                <i class="fa-solid fa-wallet text-indigo-400 text-xl"></i>
                            </div>
                            <span
                                class="text-[10px] font-black text-indigo-400 uppercase tracking-[0.2em] bg-indigo-500/10 px-3 py-1 rounded-full border border-indigo-500/20">Active</span>
                        </div>

                        <p class="text-slate-400 text-[11px] font-black uppercase tracking-widest mb-1">{{ __('Available Balance') }}
                        </p>
                        <div class="flex items-baseline gap-2 mb-8">
                            <span class="text-indigo-400 text-lg font-black">{{ $wallet->currency ?? 'KES' }}</span>
                            <h2 class="text-5xl font-black text-white tracking-tighter">
                                {{ number_format((float) $wallet->balance, 2) }}
                            </h2>
                        </div>

                        <div class="space-y-4">
                            <button @click="$dispatch('open-modal', 'withdraw-modal')"
                                class="w-full bg-white text-slate-900 font-black py-4 rounded-2xl shadow-[0_0_30px_rgba(255,255,255,0.2)] hover:shadow-[0_0_40px_rgba(255,255,255,0.4)] hover:-translate-y-0.5 active:translate-y-0 transition-all flex items-center justify-center gap-3 group relative overflow-hidden">
                                <div
                                    class="absolute inset-0 bg-gradient-to-r from-transparent via-white/40 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000">
                                </div>
                                <i
                                    class="fa-solid fa-paper-plane text-indigo-600 transition-transform group-hover:translate-x-1 group-hover:-translate-y-1"></i>
                                {{ __('Withdraw Funds') }}
                            </button>

                            <div class="flex flex-col items-center gap-1 opacity-60">
                                <p class="text-[9px] text-slate-400 text-center uppercase tracking-widest font-black">
                                    <i class="fa-solid fa-circle-info mr-1 text-indigo-400"></i>
                                    {{ __('Minimum') }}: 50.00 {{ $wallet->currency ?? 'KES' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Stats -->
                <div class="mt-8 space-y-4">
                    <div
                        class="bg-white/60 backdrop-blur-md p-5 rounded-[1.5rem] border border-gray-100 shadow-sm flex items-center justify-between group hover:border-indigo-200 transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center text-green-600">
                                <i class="fa-solid fa-arrow-trend-up"></i>
                            </div>
                            <p class="text-[10px] text-gray-400 font-black uppercase tracking-wider">{{ __('Total Earned') }}</p>
                        </div>
                        <p class="text-lg font-black text-slate-800">
                            {{ number_format($wallet->transactions()->where('type', 'credit')->sum('amount'), 2) }}</p>
                    </div>
                    <div
                        class="bg-white/60 backdrop-blur-md p-5 rounded-[1.5rem] border border-gray-100 shadow-sm flex items-center justify-between group hover:border-rose-200 transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-rose-50 rounded-xl flex items-center justify-center text-rose-600">
                                <i class="fa-solid fa-arrow-trend-down"></i>
                            </div>
                            <p class="text-[10px] text-gray-400 font-black uppercase tracking-wider">{{ __('Withdrawn') }}</p>
                        </div>
                        <p class="text-lg font-black text-slate-800">
                            {{ number_format($wallet->transactions()->where('type', 'debit')->where('status', 'completed')->sum('amount'), 2) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Transactions -->
            <div class="md:col-span-2">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                        <div class="w-2 h-8 bg-indigo-600 rounded-full"></div>
                        {{ __('Recent Activity') }}
                    </h3>
                    <a href="{{ route('wallet.history') }}"
                        class="px-4 py-2 bg-indigo-50 text-indigo-700 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                        {{ __('View History') }}
                    </a>
                </div>

                <div class="space-y-4">
                    @forelse($transactions as $transaction)
                        <div
                            class="bg-white p-5 rounded-[1.5rem] border border-gray-100 shadow-sm hover:shadow-md hover:border-indigo-100 transition-all flex items-center justify-between group">
                            <div class="flex items-center gap-5">
                                <div
                                    class="w-14 h-14 rounded-2xl flex items-center justify-center transition-all duration-500 {{ $transaction->type === 'credit' ? 'bg-green-50 text-green-600 group-hover:scale-110 group-hover:rotate-6' : 'bg-rose-50 text-rose-600 group-hover:scale-110 group-hover:-rotate-6' }}">
                                    <i
                                        class="fa-solid {{ $transaction->type === 'credit' ? 'fa-plus' : 'fa-minus' }} text-xl"></i>
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-black text-slate-800 uppercase tracking-tight group-hover:text-indigo-600 transition-colors">
                                        {{ $transaction->description }}</p>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase mt-1 tracking-wider">
                                        <span class="text-slate-300 mr-1">#</span>{{ $transaction->reference }} •
                                        {{ $transaction->created_at->format('M d, Y • H:i') }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p
                                    class="text-lg font-black {{ $transaction->type === 'credit' ? 'text-green-600' : 'text-rose-600' }}">
                                    {{ $transaction->type === 'credit' ? '+' : '-' }}
                                    {{ number_format((float) $transaction->amount, 2) }}
                                </p>
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-[0.1em] {{ $transaction->status === 'completed' ? 'bg-green-100/50 text-green-700 border border-green-200' : 'bg-amber-100/50 text-amber-700 border border-amber-200' }}">
                                    <span
                                        class="w-1 h-1 rounded-full mr-2 {{ $transaction->status === 'completed' ? 'bg-green-600' : 'bg-amber-600 animate-pulse' }}"></span>
                                    {{ $transaction->status }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="bg-white p-16 rounded-[2.5rem] border-2 border-dashed border-gray-100 text-center">
                            <div
                                class="w-20 h-20 bg-slate-50 rounded-3xl flex items-center justify-center mx-auto mb-6 transform -rotate-6">
                                <i class="fa-solid fa-vault text-3xl text-slate-200"></i>
                            </div>
                            <h4 class="text-lg font-black text-slate-800 uppercase tracking-widest mb-2">{{ __('No activity yet') }}</h4>
                            <p class="text-xs text-gray-400 font-bold max-w-[200px] mx-auto leading-relaxed uppercase">{{ __('Start responding to surveys to earn rewards and grow your balance.') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    </div>

    </div>
    <div x-data="{ open: false }" @open-modal.window="if($event.detail === 'withdraw-modal') open = true" x-show="open"
        class="fixed inset-0 z-[1000] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="open = false"></div>

            <div class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full p-8 transform transition-all">
                <div class="flex justify-between items-center mb-6">
                    <h4 class="text-2xl font-black text-gray-800">{{ __('Withdraw Funds') }}</h4>
                    <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>

                <form action="{{ route('wallet.withdraw') }}" method="POST">
                    @csrf
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2">{{ __('Amount to Withdraw') }}</label>
                        <div class="relative">
                            <input type="number" name="amount" step="0.01"
                                class="w-full pl-6 pr-16 py-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 font-black text-xl"
                                placeholder="0.00" required>
                            <span
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold uppercase">{{ $wallet->currency ?? 'KES' }}</span>
                        </div>
                        <p class="mt-2 text-[10px] text-gray-400 font-bold uppercase tracking-wider">{{ __('Your max') }}:
                            {{ number_format((float) $wallet->balance, 2) }}</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2">{{ __('M-Pesa Phone Number') }}</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold uppercase"><i
                                    class="fa-solid fa-phone"></i></span>
                            <input type="text" name="phone_number" value="{{ auth()->user()->phone_number }}"
                                class="w-full pl-12 pr-4 py-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 font-bold text-lg"
                                placeholder="254..." required>
                        </div>
                        <p class="mt-2 text-[10px] text-gray-400 font-bold uppercase tracking-wider">{{ __('Format') }}: 2547XXXXXXXX
                        </p>
                    </div>



                    <button type="submit"
                        class="w-full bg-indigo-600 text-white font-black py-4 rounded-2xl shadow-xl hover:bg-indigo-700 transition-all active:scale-95">
                        {{ __('Confirm Withdrawal') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection