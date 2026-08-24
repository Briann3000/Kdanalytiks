@extends('layouts.app')

@section('title', __('Publications Hub — KDAnalytiks'))
@section('meta_description', __('Browse and submit empirical survey research findings, executive summaries and academic papers on KDAnalytiks.'))

@push('styles')
    <meta name="keywords" content="KDAnalytiks publications, research papers, survey findings, academic publishing">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/publications') }}">
    <link rel="canonical" href="{{ url('/publications') }}">
@endpush

@section('content')
    <div class="bg-gray-50 py-16 sm:py-24 px-4 sm:px-6 lg:px-8"
        x-data="{ wpModalOpen: false, pubModalOpen: false, wpTesting: false, wpMessage: '', wpSuccess: false }">
        <div class="max-w-6xl mx-auto space-y-12">

            <!-- Header -->
            <div class="text-center space-y-3 max-w-3xl mx-auto">

                <h1 class="text-3xl sm:text-5xl font-black text-gray-900 tracking-tight leading-tight">
                    {{ __('KDAnalytiks Publications Hub') }}
                </h1>
                <p class="text-base sm:text-lg text-gray-600 font-medium">
                    {{ __('Publish, showcase and syndicate your empirical survey findings, executive summaries and academic research papers directly from KDAnalytiks.') }}
                </p>
            </div>

            <!-- Success Alert -->
            @if(session('pub_success'))
                <div
                    class="p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 font-semibold text-sm flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-base"></i>
                    <span>{{ session('pub_success') }}</span>
                </div>
            @endif

            <!-- Publication Syndication Banner Hidden -->
            <div class="hidden">
                <!-- WordPress Syndication Banner Hidden -->
            </div>

            <!-- Publication Submissions Grid -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">{{ __('Featured Research Publications') }}</h2>
                        <p class="text-xs text-gray-500">{{ __('Empirical Survey Findings & Executive Summaries') }}</p>
                    </div>
                    <button type="button" @click="pubModalOpen = true"
                        class="px-5 py-2.5 bg-[#2271b1] hover:bg-[#135e96] text-white text-xs font-bold rounded-xl shadow transition-all flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i>
                        <span>{{ __('Submit New Paper') }}</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @forelse($publications as $pub)
                        <div
                            class="bg-white rounded-2xl p-8 border border-gray-200/80 shadow-sm hover:shadow-md transition-all space-y-4 flex flex-col justify-between">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span
                                        class="px-3 py-1 bg-blue-50 text-[#135e96] rounded-full text-xs font-bold">{{ $pub['category'] }}</span>
                                    <div class="flex items-center gap-2">
                                        @if(!empty($pub['wp_synced']))
                                            <span
                                                class="px-2 py-0.5 bg-cyan-50 text-cyan-700 rounded text-[10px] font-bold border border-cyan-200"
                                                title="Syndicated to WordPress">
                                                <i class="fa-brands fa-wordpress mr-1"></i> WP Synced
                                            </span>
                                        @endif
                                        <span class="text-xs font-medium text-gray-400">{{ $pub['date'] }}</span>
                                    </div>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 leading-snug">
                                    {{ $pub['title'] }}
                                </h3>
                                <p class="text-xs text-gray-600 leading-relaxed">
                                    {{ $pub['summary'] }}
                                </p>
                            </div>

                            <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                                <span class="text-xs font-semibold text-gray-500">{{ __('Author: ') }}
                                    {{ $pub['author'] }}</span>
                                <div class="flex items-center gap-3">
                                    @auth
                                        @php
                                            $userRoleMob = is_object(auth()->user()->role) ? auth()->user()->role->value : auth()->user()->role;
                                        @endphp
                                        @if($userRoleMob === 'admin' || auth()->user()->name === ($pub['author'] ?? ''))
                                            <form method="POST" action="{{ route('publications.destroy', $pub['id']) }}"
                                                onsubmit="return confirm('{{ __('Are you sure you want to delete this publication?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="text-xs text-rose-500 hover:text-rose-700 font-bold transition-colors"
                                                    title="{{ __('Delete Publication') }}">
                                                    <i class="fa-solid fa-trash-can mr-1"></i> {{ __('Delete') }}
                                                </button>
                                            </form>
                                        @endif
                                    @endauth
                                    <a href="{{ route('publications.show', $pub['id']) }}"
                                        class="text-xs font-bold text-[#135e96] hover:underline flex items-center gap-1">
                                        <span>{{ __('View Findings') }}</span>
                                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div
                            class="col-span-full bg-white rounded-2xl p-12 border border-gray-200/80 text-center space-y-4 shadow-sm">
                            <div
                                class="w-12 h-12 rounded-full bg-blue-50 text-[#135e96] flex items-center justify-center mx-auto text-xl">
                                <i class="fa-solid fa-newspaper"></i>
                            </div>
                            <div class="space-y-1">
                                <h3 class="text-base font-bold text-gray-900">{{ __('No Publications Listed Yet') }}</h3>
                                <p class="text-xs text-gray-500 max-w-md mx-auto">
                                    {{ __('Be the first to submit and showcase your empirical survey findings or academic research summaries!') }}
                                </p>
                            </div>
                            <div class="pt-2">
                                <button type="button" @click="pubModalOpen = true"
                                    class="px-6 py-2.5 bg-[#2271b1] hover:bg-[#135e96] text-white text-xs font-bold rounded-xl shadow transition-all inline-flex items-center gap-2">
                                    <i class="fa-solid fa-plus"></i>
                                    <span>{{ __('Submit Publication') }}</span>
                                </button>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        @auth
            <!-- WordPress Connection Modal -->
            <div x-show="wpModalOpen" x-cloak
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition>
                <div class="bg-white rounded-2xl max-w-lg w-full p-8 space-y-6 shadow-2xl border border-gray-200 relative"
                    @click.away="wpModalOpen = false">
                    <button type="button" @click="wpModalOpen = false"
                        class="absolute top-6 right-6 text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-times text-lg"></i>
                    </button>

                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <i class="fa-brands fa-wordpress text-2xl text-[#2271b1]"></i>
                            <h3 class="text-xl font-bold text-gray-900">{{ __('Connect WordPress Site') }}</h3>
                        </div>Q1`
                        <p class="text-xs text-gray-500 leading-relaxed">
                            {{ __('Enter your WordPress site URL, Username and Application Password (generated in WP Admin → Users → Profile → Application Passwords).') }}
                        </p>
                    </div>

                    <!-- Layperson Error Banner -->
                    <div x-show="wpMessage" x-cloak
                        :class="wpSuccess ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'"
                        class="p-4 rounded-xl border text-xs font-semibold flex items-center gap-2 leading-relaxed">
                        <i class="fa-solid"
                            :class="wpSuccess ? 'fa-circle-check text-green-600' : 'fa-circle-exclamation text-red-600'"></i>
                        <span x-text="wpMessage"></span>
                    </div>

                    <form @submit.prevent="
                                                                                                        wpTesting = true; wpMessage = '';
                                                                                                        fetch('{{ route('publications.test-wordpress') }}', {
                                                                                                            method: 'POST',
                                                                                                            headers: {
                                                                                                                'Content-Type': 'application/json',
                                                                                                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                                                                            },
                                                                                                            body: JSON.stringify({
                                                                                                                site_url: $refs.wp_url.value,
                                                                                                                username: $refs.wp_user.value,
                                                                                                                app_password: $refs.wp_pass.value
                                                                                                            })
                                                                                                        })
                                                                                                        .then(res => res.json())
                                                                                                        .then(data => {
                                                                                                            wpTesting = false;
                                                                                                            wpSuccess = data.success;
                                                                                                            wpMessage = data.message;
                                                                                                            if(data.success) {
                                                                                                                setTimeout(() => { wpModalOpen = false; window.location.reload(); }, 1500);
                                                                                                            }
                                                                                                        })
                                                                                                        .catch(err => {
                                                                                                            wpTesting = false;
                                                                                                            wpSuccess = false;
                                                                                                            wpMessage = 'Unable to reach the WordPress website URL. Please double-check that your website address is spelled correctly and online.';
                                                                                                        });
                                                                                                    " class="space-y-4">
                        <div class="space-y-1">
                            <label
                                class="block text-xs font-bold text-gray-700 uppercase tracking-wider">{{ __('WordPress Site URL') }}</label>
                            <input type="url" x-ref="wp_url" required value="{{ session('wp_config.site_url', 'https://') }}"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-900 focus:bg-white focus:ring-2 focus:ring-[#2271b1]"
                                placeholder="https://myresearchblog.com">
                        </div>

                        <div class="space-y-1">
                            <label
                                class="block text-xs font-bold text-gray-700 uppercase tracking-wider">{{ __('WP Username') }}</label>
                            <input type="text" x-ref="wp_user" required value="{{ session('wp_config.username') }}"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-900 focus:bg-white focus:ring-2 focus:ring-[#2271b1]"
                                placeholder="admin or editor username">
                        </div>

                        <div class="space-y-1">
                            <label
                                class="block text-xs font-bold text-gray-700 uppercase tracking-wider">{{ __('Application Password') }}</label>
                            <input type="password" x-ref="wp_pass" required value="{{ session('wp_config.app_password') }}"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-900 focus:bg-white focus:ring-2 focus:ring-[#2271b1]"
                                placeholder="xxxx xxxx xxxx xxxx">
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-3">
                            <button type="button" @click="wpModalOpen = false"
                                class="px-5 py-2.5 text-xs font-bold text-gray-500 hover:text-gray-700">
                                {{ __('Cancel') }}
                            </button>
                            <button type="submit" :disabled="wpTesting"
                                class="px-6 py-2.5 bg-[#2271b1] hover:bg-[#135e96] text-white font-bold text-xs rounded-xl shadow transition-all flex items-center gap-2">
                                <i class="fa-solid fa-spinner fa-spin" x-show="wpTesting" x-cloak></i>
                                <span
                                    x-text="wpTesting ? '{{ __('Testing Connection...') }}' : '{{ __('Connect & Save Credentials') }}'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endauth

        <!-- Submit Publication Modal -->
        <div x-show="pubModalOpen" x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition>
            <div class="bg-white rounded-2xl max-w-xl w-full p-8 space-y-6 shadow-2xl border border-gray-200 relative"
                @click.away="pubModalOpen = false">
                <button type="button" @click="pubModalOpen = false"
                    class="absolute top-6 right-6 text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-times text-lg"></i>
                </button>

                <div class="space-y-1">
                    <h3 class="text-xl font-bold text-gray-900">{{ __('Submit Survey Publication') }}</h3>
                    <p class="text-xs text-gray-500">
                        {{ __('Publish your research finding or executive summary on KDAnalytiks.') }}
                    </p>
                </div>

                <form method="POST" action="{{ route('publications.store') }}" enctype="multipart/form-data"
                    class="space-y-4">
                    @csrf
                    <div class="space-y-1">
                        <label
                            class="block text-xs font-bold text-gray-700 uppercase tracking-wider">{{ __('Publication Title') }}</label>
                        <input type="text" name="title" required
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-900 focus:bg-white focus:ring-2 focus:ring-[#2271b1]"
                            placeholder="{{ __('e.g. Study on Higher Education Service Delivery') }}">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label
                                class="block text-xs font-bold text-gray-700 uppercase tracking-wider">{{ __('Category') }}</label>
                            <select name="category" required
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-900 focus:bg-white">
                                <option value="Academic Research">{{ __('Academic Research') }}</option>
                                <option value="Market Research">{{ __('Market Research') }}</option>
                                <option value="Social Research">{{ __('Social Research') }}</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label
                                class="block text-xs font-bold text-gray-700 uppercase tracking-wider">{{ __('Author') }}</label>
                            <input type="text" name="author" value="{{ auth()->user()->name ?? 'Research Author' }}"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-900 focus:bg-white">
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label
                            class="block text-xs font-bold text-gray-700 uppercase tracking-wider">{{ __('Executive Summary / Abstract') }}</label>
                        <textarea name="summary" rows="4"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-900 focus:bg-white"
                            placeholder="{{ __('Provide a concise summary of the empirical findings...') }}"></textarea>
                    </div>

                    <div class="space-y-1 p-3.5 bg-slate-50 border border-slate-200 rounded-xl">
                        <label
                            class="block text-xs font-bold text-[#135e96] uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-file-pdf text-rose-500"></i>
                            <span>{{ __('Attach Full PDF Research Paper ') }}</span>
                        </label>
                        <input type="file" name="pdf_file" accept=".pdf"
                            class="w-full text-xs text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#2271b1] file:text-white hover:file:bg-[#135e96] cursor-pointer">
                        <p class="text-[10px] text-gray-400 mt-1">
                            {{ __('Or paste a direct PDF URL below if hosted online:') }}
                        </p>
                        <input type="url" name="pdf_url"
                            class="w-full bg-white border border-gray-200 rounded-xl px-3 py-1.5 text-xs font-semibold text-gray-800 placeholder-gray-400"
                            placeholder="https://example.com/research-paper.pdf">
                    </div>

                    @if(($wpConfig['status'] ?? '') === 'connected')
                        <div class="p-3.5 bg-blue-50 border border-blue-200 rounded-xl flex items-center gap-3">
                            <input type="checkbox" name="sync_wp" value="1" id="sync_wp" class="rounded text-[#2271b1] h-4 w-4">
                            <label for="sync_wp" class="text-xs font-bold text-blue-900 cursor-pointer">
                                {{ __('Auto-publish post to my WordPress site (') }}{{ $wpConfig['site_url'] }}{{ (')') }}
                            </label>
                        </div>
                    @endif

                    <div class="pt-2 flex items-center justify-end gap-3">
                        <button type="button" @click="pubModalOpen = false"
                            class="px-5 py-2.5 text-xs font-bold text-gray-500 hover:text-gray-700">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit"
                            class="px-6 py-2.5 bg-[#2271b1] hover:bg-[#135e96] text-white font-bold text-xs rounded-xl shadow transition-all">
                            {{ __('Submit Publication') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection