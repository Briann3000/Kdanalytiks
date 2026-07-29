@extends('layouts.app')

@section('title', __('Frequently Asked Questions (FAQ) — KDAnalytiks'))
@section('meta_description', __('Find answers to common questions about using KDAnalytiks for academic and organizational research.'))

@push('styles')
    <meta name="keywords"
        content="KDAnalytiks FAQ, survey help, quantitative analysis help, qualitative thematic analysis, research platform questions">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/faq') }}">
    <link rel="canonical" href="{{ url('/faq') }}">
@endpush

@section('content')
    <div class="bg-gray-50 py-16 sm:py-24 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto space-y-12">

            <!-- Header -->
            <div class="text-center space-y-3">
                <span
                    class="px-3.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-[#135e96] border border-blue-200/60 uppercase tracking-widest inline-block">
                    {{ __('Knowledge Base') }}
                </span>
                <h1 class="text-3xl sm:text-5xl font-black text-gray-900 tracking-tight leading-tight">
                    {{ __('Frequently Asked Questions') }}
                </h1>
                <p class="text-base sm:text-lg text-gray-600 font-medium max-w-2xl mx-auto">
                    {{ __('Everything you need to know about KDAnalytiks data collection, analysis engines, publication reports, and platform features.') }}
                </p>
            </div>

            <!-- FAQ Accordion List -->
            <div class="space-y-4" x-data="{ active: null }">

                <!-- FAQ Item 1 -->
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden transition-all">
                    <button type="button" @click="active = active === 1 ? null : 1"
                        class="w-full px-6 py-5 text-left font-bold text-base text-gray-900 flex items-center justify-between gap-4 hover:bg-gray-50/80 transition-colors">
                        <span>{{ __('What is KDAnalytiks and who is it designed for?') }}</span>
                        <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition-transform duration-200"
                            :class="active === 1 ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="active === 1" x-collapse x-cloak
                        class="px-6 pb-6 text-sm text-gray-600 leading-relaxed border-t border-gray-100 pt-4">
                        {{ __('KDAnalytiks is an end-to-end, web-based research ecosystem engineered for academicians, market practitioners, corporate entities, and humanitarian aid workers. It automates survey creation, remote data collection, audio transcription, dual quantitative/qualitative analysis, and final report synthesis.') }}
                    </div>
                </div>

                <!-- FAQ Item 2 -->
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden transition-all">
                    <button type="button" @click="active = active === 2 ? null : 2"
                        class="w-full px-6 py-5 text-left font-bold text-base text-gray-900 flex items-center justify-between gap-4 hover:bg-gray-50/80 transition-colors">
                        <span>{{ __('How does the dual Quantitative and Qualitative analysis engine work?') }}</span>
                        <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition-transform duration-200"
                            :class="active === 2 ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="active === 2" x-collapse x-cloak
                        class="px-6 pb-6 text-sm text-gray-600 leading-relaxed border-t border-gray-100 pt-4">
                        {{ __('Our quantitative engine calculates descriptive metrics (averages, frequencies, demographic breakdowns) and inferential statistical modeling (hypothesis testing and trend mapping). The qualitative engine processes textual responses, field notes, and audio transcripts into structured thematic frameworks and pattern insights.') }}
                    </div>
                </div>

                <!-- FAQ Item 3 -->
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden transition-all">
                    <button type="button" @click="active = active === 3 ? null : 3"
                        class="w-full px-6 py-5 text-left font-bold text-base text-gray-900 flex items-center justify-between gap-4 hover:bg-gray-50/80 transition-colors">
                        <span>{{ __('What is the "Human Voice Guard" feature in report synthesis?') }}</span>
                        <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition-transform duration-200"
                            :class="active === 3 ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="active === 3" x-collapse x-cloak
                        class="px-6 pb-6 text-sm text-gray-600 leading-relaxed border-t border-gray-100 pt-4">
                        {{ __('To prevent generic or robotic text generation, the Human Voice Guard allows researchers to inject their personal voice, critical reflections, and stylistic nuances before the final report package is compiled.') }}
                    </div>
                </div>

                <!-- FAQ Item 4 -->
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden transition-all">
                    <button type="button" @click="active = active === 4 ? null : 4"
                        class="w-full px-6 py-5 text-left font-bold text-base text-gray-900 flex items-center justify-between gap-4 hover:bg-gray-50/80 transition-colors">
                        <span>{{ __('Can I publish survey findings to my WordPress website?') }}</span>
                        <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition-transform duration-200"
                            :class="active === 4 ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="active === 4" x-collapse x-cloak
                        class="px-6 pb-6 text-sm text-gray-600 leading-relaxed border-t border-gray-100 pt-4">
                        {{ __('Yes! KDAnalytiks includes a WordPress integration module via REST API and Application Passwords. You can automatically cross-post completed survey summaries and research reports directly to your WordPress blog with one click.') }}
                    </div>
                </div>

                <!-- FAQ Item 5 -->
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden transition-all">
                    <button type="button" @click="active = active === 5 ? null : 5"
                        class="w-full px-6 py-5 text-left font-bold text-base text-gray-900 flex items-center justify-between gap-4 hover:bg-gray-50/80 transition-colors">
                        <span>{{ __('How does KDAnalytiks optimize remote data collection in crisis or field environments?') }}</span>
                        <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition-transform duration-200"
                            :class="active === 5 ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="active === 5" x-collapse x-cloak
                        class="px-6 pb-6 text-sm text-gray-600 leading-relaxed border-t border-gray-100 pt-4">
                        {{ __('By leveraging online remote surveys, audio recording dictation, and web data pipelines, teams can collect high-integrity field data safely during emergency or disaster response periods without physical travel overhead.') }}
                    </div>
                </div>

            </div>

            <!-- Contact Prompt -->
            <div class="bg-white rounded-2xl p-8 border border-gray-200/80 text-center space-y-3 shadow-sm">
                <h3 class="text-lg font-bold text-gray-900">{{ __('Still have questions?') }}</h3>
                <p class="text-xs text-gray-600">
                    {{ __('Can\'t find what you are looking for? Reach out directly to our research support team.') }}
                </p>
                <div class="pt-2">
                    <a href="{{ route('contact') }}"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#2271b1] hover:bg-[#135e96] text-white font-bold text-xs rounded-xl transition-all">
                        <i class="fa-solid fa-envelope text-xs"></i>
                        <span>{{ __('Contact Support') }}</span>
                    </a>
                </div>
            </div>

        </div>
    </div>
@endsection