@extends('layouts.app')

@section('title', __('Contact Us — KDAnalytiks'))
@section('meta_description', __('Contact KDAnalytiks for research inquiries, platform support and organization partnerships'))

@push('styles')
    <meta name="keywords"
        content="KDAnalytiks contact, research support, KENPRO, survey tool support, academic research contact">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/contact') }}">
    <link rel="canonical" href="{{ url('/contact') }}">
@endpush

@section('content')
    <div class="bg-gray-50 py-16 sm:py-24 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto space-y-12">

            <!-- Header -->
            <div class="text-center space-y-3 max-w-3xl mx-auto">

                <h1 class="text-3xl sm:text-5xl font-black text-gray-900 tracking-tight leading-tight">
                    {{ __('Contact Us') }}
                </h1>
                <p class="text-base sm:text-lg text-gray-600 font-medium">
                    {{ __('Have questions about KDAnalytiks or requiring technical assistance? Send us a message and our team will reach out promptly.') }}
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Contact Info Sidebar -->
                <div
                    class="lg:col-span-1 bg-[#1d2327] text-white p-8 sm:p-10 space-y-8 flex flex-col justify-between shadow-xl">
                    <div class="space-y-6">
                        <h3 class="text-xl font-bold text-white border-b border-[#2c3338] pb-4">
                            {{ __('Direct Contact Information') }}
                        </h3>

                        <div class="space-y-6">
                            <div class="flex items-start gap-4">
                                <div
                                    class="w-10 h-10 rounded-xl bg-[#2271b1]/20 text-[#72aee6] flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-phone"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-[#a7aaad]  tracking-wider">{{ __('Phone') }}</p>
                                    <a href="tel:+254725788400"
                                        class="text-sm font-bold text-white hover:text-[#72aee6] transition-colors">+254 725
                                        788 400</a>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div
                                    class="w-10 h-10 rounded-xl bg-[#2271b1]/20 text-[#72aee6] flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-envelope"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-[#a7aaad]  tracking-wider">{{ __('Email') }}</p>
                                    <a href="mailto:infokdanalytiks@gmail.com"
                                        class="text-sm font-bold text-white hover:text-[#72aee6] transition-colors">infokdanalytiks@gmail.com</a>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div
                                    class="w-10 h-10 rounded-xl bg-[#2271b1]/20 text-[#72aee6] flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-handshake"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-[#a7aaad]  tracking-wider">
                                        {{ __('Powered Partner') }}
                                    </p>
                                    <a href="https://www.kenpro.org" target="_blank"
                                        class="text-sm font-bold text-white hover:text-emerald-300 transition-colors">KENPRO
                                        (Kenya Data Analytiks)</a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Contact Form -->
                <div class="lg:col-span-2 bg-white rounded-2xl p-8 sm:p-10 border border-gray-200/80 shadow-sm space-y-6">
                    @if(session('contact_success'))
                        <div
                            class="p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 flex items-center gap-3 font-semibold text-sm">
                            <i class="fa-solid fa-circle-check text-lg"></i>
                            <span>{{ session('contact_success') }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('contact.send') }}" class="space-y-6">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="space-y-1.5">
                                <label
                                    class="block text-xs font-bold text-gray-700  tracking-wider">{{ __('Your Name') }}</label>
                                <input type="text" name="name" required
                                    value="{{ old('name', auth()->user()->name ?? '') }}"
                                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-900 focus:bg-white focus:ring-2 focus:ring-[#2271b1] focus:border-[#2271b1] transition-all">
                            </div>

                            <div class="space-y-1.5">
                                <label
                                    class="block text-xs font-bold text-gray-700  tracking-wider">{{ __('Email Address') }}</label>
                                <input type="email" name="email" required
                                    value="{{ old('email', auth()->user()->email ?? '') }}"
                                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-900 focus:bg-white focus:ring-2 focus:ring-[#2271b1] focus:border-[#2271b1] transition-all">
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-700  tracking-wider">{{ __('Subject') }}</label>
                            <input type="text" name="subject" required value="{{ old('subject') }}"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-900 focus:bg-white focus:ring-2 focus:ring-[#2271b1] focus:border-[#2271b1] transition-all">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-700  tracking-wider">{{ __('Message') }}</label>
                            <textarea name="message" rows="5" required
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-900 focus:bg-white focus:ring-2 focus:ring-[#2271b1] focus:border-[#2271b1] transition-all">{{ old('message') }}</textarea>
                        </div>

                        <button type="submit"
                            class="w-full sm:w-auto px-8 py-3.5 bg-[#2271b1] hover:bg-[#135e96] text-white font-bold text-sm rounded-xl shadow transition-all flex items-center justify-center gap-2">

                            <span>{{ __('Send Message') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection