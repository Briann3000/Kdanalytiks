@extends('layouts.app')

@section('title', __('Thank You — ') . $survey->title)

@section('content')
    <div
        class="min-h-[85vh] py-12 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-blue-50/40 via-white to-indigo-50/30 flex flex-col justify-center">
        <div class="max-w-4xl mx-auto w-full space-y-10">

            <!-- Main Confirmation Card -->
            <div
                class="bg-white/90 backdrop-blur-md rounded-3xl p-8 sm:p-12 border border-gray-100 shadow-xl shadow-gray-200/50 text-center relative overflow-hidden">
                <!-- Decorative Background Blobs -->
                <div class="absolute -right-20 -top-20 w-48 h-48 bg-emerald-50 rounded-full opacity-60 pointer-events-none">
                </div>
                <div class="absolute -left-20 -bottom-20 w-48 h-48 bg-blue-50 rounded-full opacity-60 pointer-events-none">
                </div>

                <div class="relative z-10">
                    <!-- Success Animated Badge -->

                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-gray-900 tracking-tight mb-3">
                        {{ __('Thank you for your feedback!') }}
                    </h1>

                    <p class="text-sm sm:text-base text-gray-600 font-medium max-w-2xl mx-auto leading-relaxed">
                        {{ __('Your contribution to') }} <span class="font-bold text-gray-900">"{{ $survey->title }}"</span>
                        {{ __('has been securely recorded. The survey creator deeply appreciates your time and perspective.') }}
                    </p>

                    @if(session('success'))
                        <div
                            class="mt-6 p-4 max-w-xl mx-auto bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-center gap-3 text-emerald-800 text-xs sm:text-sm font-bold shadow-2xs">
                            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    <!-- Primary Action Buttons (Responsive) -->
                    <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3 max-w-md mx-auto">
                        @guest
                            <a href="{{ route('register', ['role' => 'independent']) }}"
                                class="w-full sm:w-auto flex-1 inline-flex items-center justify-center px-6 py-3.5 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-xl font-black text-xs uppercase tracking-wider shadow-lg shadow-[#2271b1]/25 transition-all transform hover:-translate-y-0.5">
                                <i class="fa-solid fa-rocket mr-2"></i>
                                {{ __('Get Started Free') }}
                            </a>
                            <a href="{{ route('login') }}"
                                class="w-full sm:w-auto flex-1 inline-flex items-center justify-center px-6 py-3.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-xl font-black text-xs uppercase tracking-wider transition-all">
                                <i class="fa-solid fa-arrow-right-to-bracket mr-2"></i>
                                {{ __('Sign In') }}
                            </a>
                        @else
                            @php
                                $userRole = auth()->user()->role;
                                $roleName = $userRole instanceof \App\Enums\UserRole ? $userRole->value : $userRole;
                            @endphp
                            <a href="{{ route($roleName . '.dashboard') }}"
                                class="w-full sm:w-auto flex-1 inline-flex items-center justify-center px-6 py-3.5 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-xl font-black text-xs uppercase tracking-wider shadow-lg shadow-[#2271b1]/25 transition-all transform hover:-translate-y-0.5">
                                <i class="fa-solid fa-gauge mr-2"></i>
                                {{ __('Go to Dashboard') }}
                            </a>
                            <a href="{{ route('surveys.public') }}"
                                class="w-full sm:w-auto flex-1 inline-flex items-center justify-center px-6 py-3.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-xl font-black text-xs uppercase tracking-wider transition-all">
                                <i class="fa-solid fa-list-check mr-2"></i>
                                {{ __('Explore More Surveys') }}
                            </a>
                        @endguest
                    </div>
                </div>
            </div>

            <!-- Platform Discovery & Features Section -->
            <div
                class="bg-white/90 backdrop-blur-md rounded-3xl p-8 sm:p-10 border border-gray-100 shadow-xl shadow-gray-200/40 space-y-8">
                <div class="text-center max-w-2xl mx-auto">
                    <span
                        class="text-xs uppercase tracking-widest text-[#2271b1] font-bold block mb-1">{{ __('Discover KDAnalytiks') }}</span>
                    <h2 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">
                        {{ __('Build, Analyze & Publish Research with AI') }}
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 font-medium mt-2 leading-relaxed">
                        {{ __('KDAnalytiks is an all-in-one research intelligence suite. Whether you are an academic researcher, university student, corporate analyst, or respondent, we have tools built for you.') }}
                    </p>
                </div>

                <!-- Features Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <!-- Feature 1: Survey & Form Builder -->
                    <div
                        class="p-5 rounded-2xl bg-gray-50/60 border border-gray-100 hover:border-blue-200 hover:bg-blue-50/30 transition-all group">
                        <div
                            class="w-10 h-10 rounded-xl bg-blue-100 text-[#2271b1] flex items-center justify-center text-base font-bold mb-3.5 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-square-poll-vertical"></i>
                        </div>
                        <div class="flex items-center justify-between mb-1.5">
                            <h3 class="text-sm font-black text-gray-900">{{ __('Advanced Survey Builder') }}</h3>
                        </div>
                        <p class="text-xs text-gray-500 font-medium leading-relaxed">
                            {{ __('Design beautiful mobile-friendly questionnaires with 20+ question formats, media audio/video answers, and GPS tracking.') }}
                        </p>
                    </div>

                    <!-- Feature 2: Socius AI & Inferential Statistics -->
                    <div
                        class="p-5 rounded-2xl bg-gray-50/60 border border-gray-100 hover:border-blue-200 hover:bg-blue-50/30 transition-all group">
                        <div
                            class="w-10 h-10 rounded-xl bg-blue-100 text-[#2271b1] flex items-center justify-center text-base font-bold mb-3.5 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-brain"></i>
                        </div>
                        <div class="flex items-center justify-between mb-1.5">
                            <h3 class="text-sm font-black text-gray-900">{{ __('Socius AI Statistics') }}</h3>
                        </div>
                        <p class="text-xs text-gray-500 font-medium leading-relaxed">
                            {{ __('Run instant ANOVA, Chi-Square, Linear Regression, and automated APA-style statistical summaries on your data.') }}
                        </p>
                    </div>

                    <!-- Feature 3: Academic Plagiarism Checker -->
                    <div
                        class="p-5 rounded-2xl bg-gray-50/60 border border-gray-100 hover:border-blue-200 hover:bg-blue-50/30 transition-all group">
                        <div
                            class="w-10 h-10 rounded-xl bg-blue-100 text-[#2271b1] flex items-center justify-center text-base font-bold mb-3.5 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div class="flex items-center justify-between mb-1.5">
                            <h3 class="text-sm font-black text-gray-900">{{ __('Plagiarism & AI Check') }}</h3>
                        </div>
                        <p class="text-xs text-gray-500 font-medium leading-relaxed">
                            {{ __('Scan thesis manuscripts, essays, and proposals against billions of scholarly sources with citation exclusions.') }}
                        </p>
                    </div>

                    <!-- Feature 4: Proposal & Thesis Studio -->
                    <div
                        class="p-5 rounded-2xl bg-gray-50/60 border border-gray-100 hover:border-blue-200 hover:bg-blue-50/30 transition-all group">
                        <div
                            class="w-10 h-10 rounded-xl bg-blue-100 text-[#2271b1] flex items-center justify-center text-base font-bold mb-3.5 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-book-open"></i>
                        </div>
                        <div class="flex items-center justify-between mb-1.5">
                            <h3 class="text-sm font-black text-gray-900">{{ __('Research Proposal Studio') }}</h3>
                        </div>
                        <p class="text-xs text-gray-500 font-medium leading-relaxed">
                            {{ __('Formulate problem statements, theoretical frameworks, sampling methodologies, and literature reviews in minutes.') }}
                        </p>
                    </div>

                    <!-- Feature 5: Paid Respondent Rewards -->
                    <div
                        class="p-5 rounded-2xl bg-gray-50/60 border border-gray-100 hover:border-blue-200 hover:bg-blue-50/30 transition-all group">
                        <div
                            class="w-10 h-10 rounded-xl bg-blue-100 text-[#2271b1] flex items-center justify-center text-base font-bold mb-3.5 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                        <div class="flex items-center justify-between mb-1.5">
                            <h3 class="text-sm font-black text-gray-900">{{ __('Earn Cash as a Respondent') }}</h3>
                        </div>
                        <p class="text-xs text-gray-500 font-medium leading-relaxed">
                            {{ __('Participate in funded survey studies and earn direct cash rewards deposited instantly to your wallet.') }}
                        </p>
                    </div>

                    <!-- Feature 6: Data Portability & SPSS -->
                    <div
                        class="p-5 rounded-2xl bg-gray-50/60 border border-gray-100 hover:border-blue-200 hover:bg-blue-50/30 transition-all group">
                        <div
                            class="w-10 h-10 rounded-xl bg-blue-100 text-[#2271b1] flex items-center justify-center text-base font-bold mb-3.5 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-file-export"></i>
                        </div>
                        <div class="flex items-center justify-between mb-1.5">
                            <h3 class="text-sm font-black text-gray-900">{{ __('Share, Export & SPSS') }}</h3>
                        </div>
                        <p class="text-xs text-gray-500 font-medium leading-relaxed">
                            {{ __('One-click export directly to native SPSS (.sav), Google Sheets, Excel (.xlsx), executive PDF, and zip packages.') }}
                        </p>
                    </div>
                </div>

                <!-- Bottom Call to Action Banner -->
                <div
                    class="p-6 sm:p-8 rounded-2xl bg-gradient-to-r from-gray-900 via-[#0f172a] to-gray-900 text-white flex flex-col sm:flex-row items-center justify-between gap-6 shadow-xl shadow-gray-900/10">
                    <div class="space-y-1 text-center sm:text-left">
                        <h3
                            class="text-lg sm:text-xl font-black tracking-tight text-white flex items-center justify-center sm:justify-start gap-2">
                            {{ __('Ready to start your own research project?') }}
                        </h3>
                        <p class="text-xs sm:text-sm text-gray-300 font-medium">
                            {{ __('Join thousands of researchers, universities, and students on KDAnalytiks today.') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-3 flex-shrink-0">
                        @guest
                            <a href="{{ route('register', ['role' => 'independent']) }}"
                                class="px-5 py-3 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-xl font-bold text-xs uppercase tracking-wider shadow-md transition-all flex items-center gap-2">
                                <span>{{ __('Create Free Account') }}</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        @else
                            <a href="{{ route('surveys.index') }}"
                                class="px-5 py-3 bg-white text-gray-900 hover:bg-gray-100 rounded-xl font-bold text-xs uppercase tracking-wider shadow-md transition-all flex items-center gap-2">
                                <i class="fa-solid fa-plus text-xs text-[#2271b1]"></i>
                                <span>{{ __('Create Survey') }}</span>
                            </a>
                        @endguest
                    </div>
                </div>
            </div>

            <!-- Footer quick return -->
            <div class="text-center">
                <a href="{{ route('home') }}"
                    class="text-xs font-bold text-gray-400 hover:text-gray-600 transition-colors inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-house text-[10px]"></i>
                    <span>{{ __('Return to KDAnalytiks Homepage') }}</span>
                </a>
            </div>

        </div>
    </div>
@endsection