@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="mb-12">
        <h1 class="text-3xl font-black text-gray-900 uppercase tracking-tight">{{ __('Account Settings') }}</h1>
        <p class="text-gray-500 font-medium mt-2">{{ __('Manage your profile details and global research branding.') }}</p>
    </div>

    <style>
        .km-toggle-container { display: inline-block; position: relative; }
        .km-toggle-checkbox { display: none; }
        .km-toggle-bg { width: 44px; height: 24px; background-color: #d1d5db; border-radius: 999px; position: relative; cursor: pointer; transition: background-color 0.2s; display: inline-block; vertical-align: middle; }
        .km-toggle-dot { width: 18px; height: 18px; background-color: white; border-radius: 50%; position: absolute; top: 3px; left: 3px; transition: transform 0.2s; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); }
        .km-toggle-checkbox:checked + .km-toggle-bg { background-color: #4f46e5; }
        .km-toggle-checkbox:checked + .km-toggle-bg .km-toggle-dot { transform: translateX(20px); }
        .password-toggle { position: absolute; right: 20px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #9ca3af; transition: color 0.2s; }
        .password-toggle:hover { color: #4f46e5; }
    </style>

    @if(session('success'))
        <div class="mb-8 p-4 bg-green-50 border border-green-100 rounded-2xl animate-in fade-in slide-in-from-top-4 duration-500">
            <p class="text-xs text-green-700 font-bold uppercase tracking-widest flex items-center">
                <i class="fa-solid fa-circle-check mr-2 text-base"></i> {{ session('success') }}
            </p>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-8 p-4 bg-red-50 border border-red-100 rounded-2xl animate-in fade-in slide-in-from-top-4 duration-500">
            <p class="text-xs text-red-700 font-bold uppercase tracking-widest flex items-center">
                <i class="fa-solid fa-circle-exclamation mr-2 text-base"></i> {{ session('error') }}
            </p>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-8 p-4 bg-red-50 border border-red-100 rounded-2xl animate-in fade-in slide-in-from-top-4 duration-500">
            <p class="text-xs text-red-700 font-bold uppercase tracking-widest mb-2 flex items-center">
                <i class="fa-solid fa-circle-exclamation mr-2 text-base"></i> {{ __('Please fix the errors below:') }}
            </p>
            <ul class="list-disc list-inside text-[10px] text-red-600 font-bold uppercase tracking-tighter">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Profile Section Form -->
    <form action="{{ route('account.settings.profile') }}" method="POST" class="space-y-8 mb-12">
        @csrf
        <div class="bg-white rounded-[2.5rem] shadow-xl border border-gray-100 overflow-hidden">
            <div class="px-10 py-8 border-b border-gray-50 bg-slate-50/30 flex justify-between items-center">
                <div>
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">{{ __('Personal Profile') }}</h3>
                    <p class="text-[11px] text-gray-400 font-bold mt-1">{{ __('Your basic identification and contact info.') }}</p>
                </div>
                <button type="submit" class="px-8 py-3 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all">
                    {{ __('Update Profile') }}
                </button>
            </div>
            
            <div class="p-10 space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">{{ __('Full Name') }}</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                            class="w-full bg-gray-50 border-gray-100 rounded-2xl px-6 py-4 text-sm font-bold text-gray-900 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">{{ __('Email Address') }}</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                            class="w-full bg-gray-50 border-gray-100 rounded-2xl px-6 py-4 text-sm font-bold text-gray-900 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">{{ __('Phone Number') }}</label>
                        <input type="text" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}"
                            placeholder="+254712345678"
                            class="w-full bg-gray-50 border-gray-100 rounded-2xl px-6 py-4 text-sm font-bold text-gray-900 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">{{ __('Language Preference') }}</label>
                        <select name="locale"
                            class="w-full bg-gray-50 border-gray-100 rounded-2xl px-6 py-4 text-sm font-bold text-gray-900 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none appearance-none cursor-pointer">
                            <option value="en" {{ (old('locale', $user->locale ?? app()->getLocale()) == 'en') ? 'selected' : '' }}>English</option>
                            <option value="sw" {{ (old('locale', $user->locale ?? app()->getLocale()) == 'sw') ? 'selected' : '' }}>Kiswahili</option>
                            <option value="fr" {{ (old('locale', $user->locale ?? app()->getLocale()) == 'fr') ? 'selected' : '' }}>Français</option>
                            <option value="de" {{ (old('locale', $user->locale ?? app()->getLocale()) == 'de') ? 'selected' : '' }}>Deutsch</option>
                            <option value="es" {{ (old('locale', $user->locale ?? app()->getLocale()) == 'es') ? 'selected' : '' }}>Español</option>
                            <option value="ar" {{ (old('locale', $user->locale ?? app()->getLocale()) == 'ar') ? 'selected' : '' }}>العربية</option>
                            <option value="zh" {{ (old('locale', $user->locale ?? app()->getLocale()) == 'zh') ? 'selected' : '' }}>中文 (简体)</option>
                        </select>
                    </div>
                </div>

                <hr class="border-gray-50">

                <div>
                    <h4 class="text-xs font-black text-gray-900 uppercase tracking-widest mb-6">{{ __('Security & Password') }}</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="relative">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">{{ __('Current Password') }}</label>
                            <input type="password" name="current_password" id="current_password"
                                class="w-full bg-gray-50 border-gray-100 rounded-2xl px-6 py-4 text-sm font-bold text-gray-900 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none">
                            <i class="fa-solid fa-eye password-toggle" onclick="togglePassword('current_password')"></i>
                        </div>
                        <div class="relative">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">{{ __('New Password') }}</label>
                            <input type="password" name="new_password" id="new_password"
                                class="w-full bg-gray-50 border-gray-100 rounded-2xl px-6 py-4 text-sm font-bold text-gray-900 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none">
                            <i class="fa-solid fa-eye password-toggle" onclick="togglePassword('new_password')"></i>
                        </div>
                        <div class="md:col-start-2 relative">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">{{ __('Confirm New Password') }}</label>
                            <input type="password" name="new_password_confirmation" id="new_password_confirmation"
                                class="w-full bg-gray-50 border-gray-100 rounded-2xl px-6 py-4 text-sm font-bold text-gray-900 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none">
                            <i class="fa-solid fa-eye password-toggle" onclick="togglePassword('new_password_confirmation')"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Branding Section Form -->
    <form action="{{ route('account.settings.branding') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf
        <div class="bg-white rounded-[2.5rem] shadow-xl border border-gray-100 overflow-hidden">
            <div class="px-10 py-8 border-b border-gray-50 bg-indigo-50/20 flex justify-between items-center">
                <div>
                    <h3 class="text-xs font-black text-indigo-600 uppercase tracking-widest flex items-center gap-2">
                        <i class="fa-solid fa-palette"></i> {{ __('Research Branding') }}
                    </h3>
                    <p class="text-[11px] text-gray-400 font-bold mt-1">{{ __('Configure global branding for your generated reports and proposals.') }}</p>
                </div>
                <button type="submit" {{ $user->hasActiveSubscription() ? '' : 'disabled' }} class="px-8 py-3 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ __('Save Branding') }}
                </button>
            </div>
            
            <div class="p-10 space-y-10">
                @php
                    $canCustom = $user->hasActiveSubscription();
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                    <div class="space-y-8">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">{{ __('Export Logo') }}</label>
                            <div class="flex items-center gap-6">
                                <div class="w-24 h-24 rounded-3xl bg-gray-50 border border-gray-100 flex items-center justify-center overflow-hidden shadow-inner">
                                    @if($user->export_logo_url)
                                        <img id="logo-preview" src="{{ asset('storage/' . $user->export_logo_url) }}" class="w-full h-full object-contain p-2">
                                    @else
                                        <img id="logo-preview" src="" class="w-full h-full object-contain p-2 hidden">
                                        <div id="logo-placeholder" class="text-center">
                                            <i class="fa-solid fa-image text-2xl text-gray-200"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <input type="file" name="export_logo" id="logo-input" {{ $canCustom ? '' : 'disabled' }}
                                        onchange="previewImage(this)"
                                        class="block w-full text-[10px] text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 cursor-pointer">
                                    <p class="text-[9px] text-gray-400 font-bold mt-2 uppercase tracking-tight">
                                        {{ __('PNG or JPG, Max 2MB.') }} 
                                        @if($user->export_logo_url)
                                            <span class="text-indigo-600">{{ __('✓ Current logo saved.') }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">{{ __('Default Organization Name') }}</label>
                            <input type="text" name="export_org_name" value="{{ old('export_org_name', $user->export_org_name) }}" {{ $canCustom ? '' : 'disabled' }}
                                placeholder="{{ __('Your Org Name...') }}"
                                class="w-full bg-gray-50 border-gray-100 rounded-2xl px-6 py-4 text-sm font-bold text-gray-900 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none {{ !$canCustom ? 'opacity-50 cursor-not-allowed' : '' }}">
                            <p class="text-[9px] text-gray-400 font-bold mt-2 uppercase tracking-tight">{{ __('Used in report footers and title pages.') }}</p>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">{{ __('Brand Accent Color') }}</label>
                            <div class="flex items-center gap-4">
                                <input type="color" name="brand_color" value="{{ old('brand_color', $user->brand_color ?? '#4f46e5') }}" {{ $canCustom ? '' : 'disabled' }}
                                    class="w-12 h-12 bg-gray-50 border-none rounded-xl cursor-pointer {{ !$canCustom ? 'opacity-50 cursor-not-allowed' : '' }}">
                                <input type="text" value="{{ old('brand_color', $user->brand_color ?? '#4f46e5') }}" readonly
                                    class="flex-1 bg-gray-50 border-gray-100 rounded-2xl px-6 py-4 text-sm font-black text-gray-400 uppercase tracking-widest">
                            </div>
                            <p class="text-[9px] text-gray-400 font-bold mt-2 uppercase tracking-tight">{{ __('This color will be used for headings and themes in your exports.') }}</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="p-8 rounded-[2rem] border {{ $canCustom ? 'border-indigo-100 bg-indigo-50/10' : 'border-gray-100 bg-gray-50/30' }}">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <h5 class="text-xs font-black {{ $canCustom ? 'text-indigo-900' : 'text-gray-400' }} uppercase tracking-widest mb-1">{{ __('Remove KMSurvey Branding') }}</h5>
                                    <p class="text-[10px] font-bold {{ $canCustom ? 'text-indigo-400' : 'text-gray-400' }} uppercase tracking-tighter leading-relaxed">
                                        {{ __('Remove "Exported via KMSurveyTool" from all your professional reports and proposals.') }}
                                    </p>
                                </div>
                                <div class="km-toggle-container">
                                    <input type="hidden" name="remove_km_branding_present" value="1">
                                    <input type="checkbox" name="remove_km_branding" value="1" {{ $user->remove_km_branding ? 'checked' : '' }} {{ $canCustom ? '' : 'disabled' }} class="km-toggle-checkbox" id="branding_toggle">
                                    <label for="branding_toggle" class="km-toggle-bg {{ !$canCustom ? 'opacity-30 cursor-not-allowed' : '' }}">
                                        <div class="km-toggle-dot"></div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        @if(!$canCustom)
                            <div class="p-6 rounded-2xl bg-amber-50 border border-amber-100">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="w-6 h-6 rounded-full bg-amber-100 flex items-center justify-center text-amber-600">
                                        <i class="fa-solid fa-crown text-[10px]"></i>
                                    </div>
                                    <span class="text-[10px] font-black text-amber-900 uppercase tracking-widest">{{ __('Premium Feature') }}</span>
                                </div>
                                <p class="text-[9px] text-amber-700 font-bold uppercase tracking-tight leading-relaxed">
                                    {{ __('You are currently on a free plan. Upgrade to a premium subscription to remove our branding and use your own custom logo.') }}
                                </p>
                                <a href="{{ route('subscriptions.index') }}" class="mt-4 inline-flex items-center gap-2 text-[10px] font-black text-amber-900 uppercase tracking-widest hover:translate-x-1 transition-transform">
                                    {{ __('View Plans') }} <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = input.nextElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function previewImage(input) {
            const preview = document.getElementById('logo-preview');
            const placeholder = document.getElementById('logo-placeholder');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    if (placeholder) placeholder.classList.add('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</div>
@endsection
