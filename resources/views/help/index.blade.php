@extends('layouts.app')

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

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Interactive Help Actions -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Guided Tours Card -->
                <div class="bg-white border border-gray-100 rounded-[2rem] shadow-sm p-6 space-y-4">
                    <div class="flex items-center gap-3 border-b border-gray-50 pb-3">
                        <div class="w-10 h-10 bg-zinc-100 rounded-2xl flex items-center justify-center text-[#2271b1]">
                            <i class="fa-solid fa-compass text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest">{{ __('Guided Tours') }}
                            </h3>
                            <p class="text-[10px] text-gray-400 font-bold uppercase">{{ __('Interactive page guides') }}</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed font-semibold">
                        {{ __('Learn to navigate our tools directly on the page. We will guide you through options step-by-step.') }}
                    </p>
                    <div class="space-y-2">
                        @auth
                            @php
                                $roleVal = auth()->user()->role instanceof \UnitEnum ? auth()->user()->role->value : auth()->user()->role;
                            @endphp
                            @if(in_array($roleVal, ['admin', 'organization', 'independent']))
                                {{-- Survey Builder Tour: links to the builder (create page) so tour elements exist --}}
                                <a href="{{ route('help.tours.launch', ['tour' => 'builder']) }}"
                                    class="flex items-center justify-between w-full px-4 py-3 bg-gray-50 hover:bg-zinc-100 hover:text-[#135e96] text-xs font-black uppercase tracking-widest rounded-2xl transition-colors text-gray-600">
                                    <span>{{ __('Survey Builder Tour') }}</span>
                                    <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                </a>
                                {{-- Reports Tour: open any survey report and use the Tour button in the header --}}
                                <a href="{{ route('help.tours.launch', ['tour' => 'reports']) }}"
                                    class="flex items-center justify-between w-full px-4 py-3 bg-gray-50 hover:bg-zinc-100 hover:text-[#135e96] text-xs font-black uppercase tracking-widest rounded-2xl transition-colors text-gray-600">
                                    <span>{{ __('Reports Dashboard Tour') }}</span>
                                    <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                </a>
                            @else
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider text-center py-2">
                                    {{ __('Tours available on researcher & admin accounts') }}
                                </p>
                            @endif
                        @else
                            <a href="{{ route('login') }}"
                                class="flex items-center justify-between w-full px-4 py-3 bg-gray-50 hover:bg-zinc-100 hover:text-[#135e96] text-xs font-black uppercase tracking-widest rounded-2xl transition-colors text-gray-600">
                                <span>{{ __('Login to take tours') }}</span>
                                <i class="fa-solid fa-right-to-bracket text-[10px]"></i>
                            </a>
                        @endauth
                    </div>
                </div>

                <!-- View Documentation -->
                <div class="bg-gradient-to-br from-zinc-800 to-zinc-900 text-white rounded-[2rem] p-6 shadow-xl space-y-4">
                    <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center text-zinc-500">
                        <i class="fa-solid fa-book-open text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-widest">{{ __('Full Documentation') }}</h3>
                        <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-widest mt-0.5">
                            {{ __('Deep feature guides') }}
                        </p>
                    </div>
                    <p class="text-xs text-zinc-300/80 leading-relaxed font-semibold">
                        {{ __('Access detailed explanations regarding variables, crosstab calculations, subscription scopes, and wallets.') }}
                    </p>
                    <a href="{{ route('docs') }}"
                        class="inline-flex items-center gap-2 px-5 py-3 bg-white text-zinc-900 text-xs font-black uppercase tracking-widest rounded-2xl hover:bg-zinc-100 transition-colors shadow-lg">
                        {{ __('Go to Docs') }}
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
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