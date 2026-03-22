@extends('layouts.app')

@section('content')
<div class="flex flex-col items-center justify-center min-h-[60vh] px-4">
    <div class="w-full max-w-lg bg-white rounded-3xl shadow-xl border border-gray-100 p-10 text-center">
        <div class="w-20 h-20 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 mx-auto mb-8">
            <i class="fa-solid fa-envelope-circle-check text-3xl"></i>
        </div>
        
        <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tight mb-4">Verify Your Email</h2>
        <p class="text-sm text-gray-500 font-medium leading-relaxed mb-8">
            A fresh verification link has been sent to your email address. Before proceeding, please check your email for a verification link.
        </p>

        @if (session('message'))
            <div class="mb-8 p-4 bg-green-50 rounded-2xl border border-green-100 text-green-700 text-xs font-black uppercase tracking-widest animate-in fade-in zoom-in duration-300">
                {{ __('A fresh verification link has been sent to your email address.') }}
            </div>
        @endif

        <div class="space-y-4">
            <form class="inline-block w-full" method="POST" action="{{ route('verification.resend') }}">
                @csrf
                <button type="submit" class="w-full py-4 px-6 bg-indigo-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                    Resend Verification Email
                </button>
            </form>
            
            <form class="inline-block w-full" method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-xs font-bold text-gray-400 uppercase tracking-widest hover:text-indigo-600 transition-all">
                    Log out and try another email
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
