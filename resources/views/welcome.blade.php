@extends('layouts.app')

@section('content')
    <div class="relative bg-white overflow-hidden">
        <div class="max-w-7xl mx-auto">
            <div class="relative z-10 pb-8 bg-white sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
                <main class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28 relative">
                    <div class="sm:text-center lg:text-left">
                        <h1 class="text-4xl tracking-tight font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
                            <span class="block">KMSurveyTool</span>
                            <span class="block text-indigo-600">{{ __('Data you can trust') }}</span>
                        </h1>
                        <p
                            class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                            {{ __('Create surveys in minutes. Analyze results with powerful tools tailored for Organizations, Practitioners, Independent Researchers and Students.') }}
                        </p>

                        <div class="mt-10 max-w-lg lg:mx-0 sm:mx-auto">
                            <div class="mb-6">
                                <h3 class="text-xl font-bold text-gray-900">{{ __('Get Started') }}</h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ __('Select your account type to sign in or register') }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <!-- Organization -->
                                <a href="{{ route('login.role', ['role' => 'organization']) }}"
                                    class="group relative bg-white rounded-2xl shadow-sm border border-gray-200 p-6 text-center transition-all hover:shadow-xl hover:-translate-y-1 hover:border-indigo-300">
                                    <div
                                        class="mx-auto h-12 w-12 flex items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors mb-4">
                                        <i class="fa-solid fa-building text-xl"></i>
                                    </div>
                                    <h3 class="text-base font-bold text-gray-900">{{ __('Organization') }}</h3>
                                    <p class="mt-1 text-[11px] text-gray-500 leading-tight font-medium">
                                        {{ __('For companies & institutions') }}
                                    </p>
                                </a>

                                <!-- Independent Researcher -->
                                <a href="{{ route('login.role', ['role' => 'independent']) }}"
                                    class="group relative bg-white rounded-2xl shadow-sm border border-gray-200 p-6 text-center transition-all hover:shadow-xl hover:-translate-y-1 hover:border-purple-300">
                                    <div
                                        class="mx-auto h-12 w-12 flex items-center justify-center rounded-xl bg-purple-50 text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-colors mb-4">
                                        <i class="fa-solid fa-user-graduate text-xl"></i>
                                    </div>
                                    <h3 class="text-base font-bold text-gray-900">{{ __('Researcher') }}</h3>
                                    <p class="mt-1 text-[11px] text-gray-500 leading-tight font-medium">
                                        {{ __('For academicians & practitioners') }}
                                    </p>
                                </a>

                                <!-- Respondent -->
                                <a href="{{ route('login.role', ['role' => 'respondent']) }}"
                                    class="group relative bg-white rounded-2xl shadow-sm border border-gray-200 p-6 text-center transition-all hover:shadow-xl hover:-translate-y-1 hover:border-green-300 sm:col-span-2">
                                    <div
                                        class="mx-auto h-12 w-12 flex items-center justify-center rounded-xl bg-green-50 text-green-600 group-hover:bg-green-600 group-hover:text-white transition-colors mb-4">
                                        <i class="fa-solid fa-clipboard-check text-xl"></i>
                                    </div>
                                    <h3 class="text-base font-bold text-gray-900">{{ __('Respondent') }}</h3>
                                    <p class="mt-1 text-[11px] text-gray-500 leading-tight font-medium">
                                        {{ __('Share feedback & earn') }}
                                    </p>
                                </a>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <div
            class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2 bg-indigo-50/50 flex flex-col items-center justify-center p-8 lg:p-12 overflow-hidden relative">
            <!-- Decorative Glow -->
            <div
                class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-indigo-200/20 blur-[120px] rounded-full pointer-events-none">
            </div>

            <div class="max-w-md w-full text-center space-y-10 relative z-10">
                <div
                    class="inline-flex items-center px-4 py-2 bg-white text-indigo-700 rounded-full text-[10px] font-black uppercase tracking-widest shadow-sm border border-indigo-100 mb-4 slide-up">
                    <i class="fa-solid fa-star mr-2 text-yellow-500"></i> {{ __('Empowering Research') }}
                </div>

                <div class="grid grid-cols-1 gap-6 text-left">
                    <div
                        class="bg-white p-8 rounded-3xl shadow-2xl shadow-indigo-100/40 border border-indigo-50 transform hover:scale-[1.01] transition-all duration-300">
                        <div class="flex items-center mb-5">
                            <div
                                class="w-14 h-14 bg-indigo-600 rounded-2xl flex items-center justify-center text-white shadow-xl shadow-indigo-200 mr-5">
                                <i class="fa-solid fa-globe text-2xl"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-gray-900 leading-tight">{{ __('Public Survey Hub') }}</h4>

                            </div>
                        </div>
                        <p class="text-sm text-gray-600 leading-relaxed mb-8">
                            {{ __('Browse active research surveys, share your insights and earn rewards.') }}
                        </p>
                        <a href="{{ route('surveys.public') }}"
                            class="group w-full inline-flex items-center justify-center px-8 py-4 bg-gray-900 text-white font-bold rounded-2xl hover:bg-indigo-600 transition-all shadow-lg active:scale-95">
                            {{ __('Browse All Surveys') }} <i
                                class="fa-solid fa-arrow-right ml-3 group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                </div>

                <div class="pt-10 border-t border-indigo-100 flex flex-col items-center">
                    <div class="flex space-x-6 mb-6 opacity-30 grayscale saturate-0">
                        <i class="fa-solid fa-shield-halved text-2xl"></i>
                        <i class="fa-solid fa-bolt text-2xl"></i>
                        <i class="fa-solid fa-lock text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-indigo-950 mb-1">{{ __('State of the Art Experience') }}</h3>
                    <p class="text-[11px] font-medium text-indigo-400 max-w-xs mx-auto">
                        {{ __('One seamless platform for survey creators, analysts and respondents worldwide.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <style>
        .slide-up {
            animation: slideUp 1s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endsection