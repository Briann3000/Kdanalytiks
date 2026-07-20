@extends('layouts.app')

@section('title', __('Claim Your Reward'))

@section('content')
    <div
        class="min-h-[85vh] flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-gradient-to-br from-zinc-50/20 via-white to-purple-50/20">
        <div class="sm:mx-auto sm:w-full sm:max-w-xl text-center px-4">
            <!-- Animated Reward Icon -->
            <div class="relative w-32 h-32 mx-auto mb-8 animate-bounce">
                <div class="absolute inset-0 bg-emerald-400 rounded-[2.5rem] opacity-20 blur-xl animate-pulse"></div>
                <div
                    class="relative w-32 h-32 bg-gradient-to-tr from-emerald-500 to-teal-400 rounded-[2.2rem] flex items-center justify-center shadow-xl shadow-emerald-500/20 border border-emerald-400/30 transform hover:scale-105 transition-transform duration-300">
                    <i class="fa-solid fa-gift text-white text-5xl"></i>
                </div>
            </div>

            <!-- Header Titles -->
            <span
                class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 uppercase tracking-widest mb-4">
                {{ __('Survey Successfully Submitted') }}
            </span>
            <h1 class="text-4xl sm:text-5xl font-black text-gray-900 mb-4 tracking-tight leading-none">
                {{ __('Claim Your Reward!') }}
            </h1>
            <p class="text-lg text-gray-600 font-medium max-w-md mx-auto leading-relaxed">
                {{ __('Thank you for your valuable feedback. You are just one step away from receiving your reward.') }}
            </p>

            <!-- Reward Summary Card -->
            <div
                class="mt-8 bg-white/80 backdrop-blur-md rounded-3xl p-8 shadow-xl shadow-gray-100/50 border border-gray-100 max-w-md mx-auto relative overflow-hidden group hover:shadow-2xl hover:border-zinc-200 transition-all duration-300">
                <div
                    class="absolute -right-16 -top-16 w-36 h-36 bg-zinc-100 rounded-full opacity-40 group-hover:scale-110 transition-transform duration-500">
                </div>
                <div
                    class="absolute -left-16 -bottom-16 w-36 h-36 bg-purple-50 rounded-full opacity-40 group-hover:scale-110 transition-transform duration-500">
                </div>

                <div class="relative">
                    <span class="text-xs font-black uppercase text-gray-400 tracking-wider block mb-1">
                        {{ __('Pending Reward') }}
                    </span>
                    <div class="text-5xl font-black text-gray-900 tracking-tight mb-2">
                        {{ number_format($survey->reward_per_response, 0) }}
                        <span class="text-2xl font-bold text-[#2271b1]">{{ $survey->reward_currency ?? 'KES' }}</span>
                    </div>
                    <div
                        class="inline-flex items-center text-xs font-bold text-gray-500 bg-gray-50 px-3 py-1 rounded-full border border-gray-100">
                        <i class="fa-solid fa-clipboard-check text-emerald-500 mr-2"></i>
                        {{ __('Associated with: ') }} {{ session('guest_name') ?? 'Guest Response' }}
                    </div>
                </div>
            </div>

            <!-- Login / Register Promotion Panel -->
            <div class="mt-8 max-w-md mx-auto bg-gray-50/50 rounded-3xl p-8 border border-gray-100 shadow-inner">
                <h3 class="text-sm font-black uppercase text-gray-900 tracking-wider mb-3">
                    {{ __('Choose how to receive rewards') }}
                </h3>
                <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                    {{ __('To credit this reward to your account wallet, please log in or create a respondent account.') }}
                </p>

                <div class="flex flex-col sm:flex-row gap-4">
                    <!-- Register Button -->
                    <a href="{{ route('register', ['role' => 'respondent']) }}"
                        class="flex-1 flex items-center justify-center px-6 py-4 bg-[#2271b1] text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-zinc-300/40 hover:bg-[#135e96] transition-all transform hover:-translate-y-0.5">
                        <i class="fa-solid fa-user-plus mr-2"></i> {{ __('Create Account') }}
                    </a>

                    <!-- Login Button -->
                    <a href="{{ route('login.role', ['role' => 'respondent']) }}"
                        class="flex-1 flex items-center justify-center px-6 py-4 bg-white text-gray-700 border border-gray-200 text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-sm hover:bg-gray-50 transition-all transform hover:-translate-y-0.5">
                        <i class="fa-solid fa-right-to-bracket mr-2"></i> {{ __('Login / Sign In') }}
                    </a>
                </div>

                <!-- Cancel / Skip Link -->
                <div class="mt-6">
                    <a href="{{ route('surveys.show', $survey->id) }}"
                        onclick="return confirm('Skip claiming? You will lose this reward.')"
                        class="text-xs font-bold text-gray-400 hover:text-gray-600 underline transition-colors">
                        {{ __('Skip claiming (discard reward)') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection