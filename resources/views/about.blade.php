@extends('layouts.app')

@section('title', __('About Us — KDAnalytiks'))
@section('meta_description', __('KDAnalytiks is an end-to-end research ecosystem engineered for researchers and organizations to automate data collection and analysis.'))

@push('styles')
    <meta name="keywords"
        content="KDAnalytiks, research platform, survey tool, qualitative analysis, quantitative analysis, data collection, humanitarian aid research, academic synthesis">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/about') }}">
    <link rel="canonical" href="{{ url('/about') }}">
@endpush

@section('content')
    <div class="bg-gray-50 text-gray-900 py-16 sm:py-24 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto space-y-12">
            <!-- Hero Header -->
            <div class="text-center space-y-3 max-w-3xl mx-auto">
                <h1 class="text-3xl sm:text-5xl font-black tracking-tight text-gray-900 leading-tight">
                    {{ __('KDAnalytiks Overview') }}
                </h1>
                <p class="text-base sm:text-lg text-gray-600 font-medium">
                    {{ __('Transforming Complex Data into Actionable Strategies') }}
                </p>
            </div>

            <div class="bg-white rounded-2xl p-8 sm:p-12 border border-gray-200/80 shadow-sm space-y-4">
                <h2 class="text-2xl font-black text-gray-900 flex items-center gap-3">
                    {{ __('Architected From Real-World Expertise') }}
                </h2>
                <p class="text-gray-700 leading-relaxed text-base">
                    {{ __('In the modern digital era, KDAnalytiks stands out as the ultimate solution for ease of work. We automate the heavy lifting of complex data workflows, transforming raw data into a complete and publication-ready final product, while uniquely preserving the researcher\'s human authenticity and intellectual authority.') }}
                </p>
            </div>

            <!-- 3 Core Pillars Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Pillar 1 -->
                <div
                    class="bg-white rounded-2xl p-8 border border-gray-200/80 shadow-sm flex flex-col justify-between hover:border-[#2271b1] transition-all">
                    <div class="space-y-4">
                        <h3 class="text-xl font-bold text-gray-900">{{ __('1. Data Collection') }}</h3>
                        <ul class="space-y-3 text-gray-600 text-sm">
                            <li class="flex items-start gap-2.5">
                                <i class="fa-solid fa-circle-check text-[#135e96] mt-1 text-xs shrink-0"></i>
                                <span><strong>{{ __('Cost & Time Optimization') }}:</strong>
                                    {{ __('Permanently eliminates heavy physical field logistics, travel and overhead operational costs.') }}</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <i class="fa-solid fa-circle-check text-[#135e96] mt-1 text-xs shrink-0"></i>
                                <span><strong>{{ __('Crisis & Humanitarian Utility') }}:</strong>
                                    {{ __('Deploy remote survey pipelines safely during disaster or emergency periods when field access is restricted.') }}</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <i class="fa-solid fa-circle-check text-[#135e96] mt-1 text-xs shrink-0"></i>
                                <span><strong>{{ __('Built-In Transcription') }}:</strong>
                                    {{ __('Automatically converts audio interviews, field dictations and focus group recordings into structured text.') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Pillar 2 -->
                <div
                    class="bg-white rounded-2xl p-8 border border-gray-200/80 shadow-sm flex flex-col justify-between hover:border-[#2271b1] transition-all">
                    <div class="space-y-4">
                        <h3 class="text-xl font-bold text-gray-900">{{ __('2. Data Analysis') }}</h3>
                        <ul class="space-y-3 text-gray-600 text-sm">
                            <li class="flex items-start gap-2.5">
                                <i class="fa-solid fa-circle-check text-[#135e96] mt-1 text-xs shrink-0"></i>
                                <span><strong>{{ __('Quantitative Approach') }}:</strong>
                                    {{ __('Computes descriptive frequencies and executes inferential statistical modeling, hypothesis testing and trend mapping.') }}</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <i class="fa-solid fa-circle-check text-[#135e96] mt-1 text-xs shrink-0"></i>
                                <span><strong>{{ __('Qualitative Approach') }}:</strong>
                                    {{ __('Processes textual narrative data into thematic frameworks, identifying critical patterns and categorical insights.') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Pillar 3 -->
                <div
                    class="bg-white rounded-2xl p-8 border border-gray-200/80 shadow-sm flex flex-col justify-between hover:border-[#2271b1] transition-all">
                    <div class="space-y-4">
                        <h3 class="text-xl font-bold text-gray-900">{{ __('3. Final Report & Synthesis') }}</h3>
                        <ul class="space-y-3 text-gray-600 text-sm">
                            <li class="flex items-start gap-2.5">
                                <i class="fa-solid fa-circle-check text-[#135e96] mt-1 text-xs shrink-0"></i>
                                <span><strong>{{ __('Complete Synthesis') }}:</strong>
                                    {{ __('Synthesizes raw metrics, quantitative stats and qualitative themes into fully articulated report packages.') }}</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <i class="fa-solid fa-circle-check text-[#135e96] mt-1 text-xs shrink-0"></i>
                                <span><strong>{{ __('Human Voice Guard') }}:</strong>
                                    {{ __('Ensures authentic researcher voice, reflections and stylistic nuances before final document compilation.') }}</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <i class="fa-solid fa-circle-check text-[#135e96] mt-1 text-xs shrink-0"></i>
                                <span><strong>{{ __('Enhanced Visual Dashboards') }}:</strong>
                                    {{ __('Generates high-impact charts, matrices and dashboards for decision-makers and peer reviewers.') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="bg-white p-8 sm:p-12 border border-gray-200/80 shadow-sm space-y-8">
                <div class="text-center max-w-2xl mx-auto space-y-2">
                    <h2 class="text-2xl font-black text-gray-900">{{ __('Target Users') }}</h2>
                    <p class="text-sm text-gray-500">
                        {{ __('Engineered to serve diverse research requirements across academic, corporate field aid sectors.') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="p-6 bg-gray-50 rounded-xl border border-gray-200/60 space-y-3">
                        <i class="fa-solid fa-graduation-cap text-2xl text-[#135e96]"></i>
                        <h4 class="text-base font-bold text-gray-900">{{ __('Academicians & Researchers') }}</h4>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            {{ __('University students, fellows professors requiring rigid empirical data validity and structured synthesis matching strict institutional guidelines.') }}
                        </p>
                    </div>

                    <div class="p-6 bg-gray-50 rounded-xl border border-gray-200/60 space-y-3">
                        <i class="fa-solid fa-building text-2xl text-[#135e96]"></i>
                        <h4 class="text-base font-bold text-gray-900">{{ __('Practitioners & Organizations') }}</h4>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            {{ __('Corporate entities, market analysts and NGOs requiring rapid, cost-effective data and intelligence to back strategic field operations.') }}
                        </p>
                    </div>

                    <div class="p-6 bg-gray-50 rounded-xl border border-gray-200/60 space-y-3">
                        <i class="fa-solid fa-hand-holding-heart text-2xl text-[#135e96]"></i>
                        <h4 class="text-base font-bold text-gray-900">{{ __('Humanitarian Aid Workers') }}</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            {{ __('Crisis response teams needing agile, remote data collection and immediate reporting tools during emergency situations.') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="text-center space-y-6 pt-4">
                <h3 class="text-2xl font-black text-gray-900">{{ __('Ready to Streamline Your Research Workflows?') }}</h3>
                <div class="flex flex-wrap items-center justify-center gap-4">
                    <a href="{{ route('register', 'independent') }}"
                        class="px-8 py-3.5 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-xl text-sm font-bold shadow transition-all">
                        {{ __('Get Started Free') }}
                    </a>
                    <a href="{{ route('contact') }}"
                        class="px-8 py-3.5 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-xl text-sm font-bold transition-all">
                        {{ __('Contact Support') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection