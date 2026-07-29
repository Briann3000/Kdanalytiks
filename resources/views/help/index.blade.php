@extends('layouts.app')

@section('title', __('Help Center & Support — KDAnalytiks'))
@section('meta_description', __('Find answers to common questions and get support for KDAnalytiks survey analysis tools.'))

@section('content')
    <div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8" x-data="{ searchQuery: '', activeTab: 'faqs' }">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-3xl font-black tracking-tight text-gray-900 sm:text-4xl">
                {{ __('Help Center & Support') }}
            </h1>
            <p class="mt-4 text-sm text-gray-400 font-bold uppercase tracking-widest max-w-2xl mx-auto">
                {{ __('Get answers to common questions, explore tutorials, or launch guided tours of key screens.') }}
            </p>

            <!-- Dynamic Search -->
            <div class="mt-8 max-w-xl mx-auto">
                <div class="relative flex items-center">
                    <input type="text" x-model="searchQuery" placeholder="{{ __('Search FAQs by keywords...') }}"
                        class="w-full px-5 py-4 bg-white border border-gray-100 rounded-2.5xl shadow-lg shadow-gray-100 text-sm font-semibold focus:outline-none focus:border-[#2271b1] focus:ring-1 focus:ring-[#2271b1]">
                    <i class="fa-solid fa-magnifying-glass absolute right-5 text-gray-400"></i>
                </div>
            </div>
        </div>

        <!-- FAQs Accordions Area -->
        <div class="lg:col-span-8">
            <div class="bg-white border border-gray-100 rounded-[2.5rem] shadow-sm p-6 sm:p-10 space-y-6">
                <h2 class="text-base font-black text-gray-900 uppercase tracking-widest border-b border-gray-50 pb-3">
                    {{ __('Frequently Asked Questions') }}
                </h2>

                <div class="space-y-3">
                    @foreach($faqs as $idx => $faq)
                        <div x-data="{ open: false }"
                            x-show="searchQuery === '' || '{{ strtolower($faq['question']) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($faq['answer']) }}'.includes(searchQuery.toLowerCase())"
                            class="border border-gray-100 rounded-2.5xl overflow-hidden transition-all duration-200">
                            <!-- Toggle Button -->
                            <button @click="open = !open"
                                class="w-full flex items-center justify-between px-6 py-4 bg-gray-50/50 hover:bg-gray-50 text-left transition-colors">
                                <span class="text-xs font-black text-gray-800 uppercase tracking-wider">
                                    {{ $faq['question'] }}
                                </span>
                                <i class="fa-solid fa-chevron-down text-[10px] text-gray-400 transition-transform duration-200"
                                    :class="open ? 'rotate-180 text-[#2271b1]' : ''"></i>
                            </button>

                            <!-- Answer -->
                            <div x-show="open" x-collapse style="display: none;" class="bg-white">
                                <div
                                    class="px-6 py-4 text-xs text-gray-500 leading-relaxed font-semibold border-t border-gray-50">
                                    {{ $faq['answer'] }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection