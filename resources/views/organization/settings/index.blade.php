@extends('layouts.app')

@section('title', 'Workspace Settings - ' . $org->name)

@section('content')
    <div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto space-y-8">

            <!-- Top Header -->
            <div
                class="flex flex-col md:flex-row md:items-center justify-between bg-white p-6 rounded-2xl border border-slate-200 shadow-sm gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">{{ __('Settings & Governance') }}</h1>
                    <p class="text-xs text-slate-500">
                        {{ __('Manage branding enforcement, survey approval policies, and data retention defaults for') }}
                        <strong>{{ $org->name }}</strong>.</p>
                </div>
                <span
                    class="px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold uppercase">{{ __('Admin Control') }}</span>
            </div>

            @if(session('success'))
                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Card 1: Branding Enforcement -->
            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm space-y-6"
                x-data="{ logoPreview: '{{ old('logo_url', $org->logo_url) }}' }">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">{{ __('Institutional Branding Enforcement') }}</h2>
                    <p class="text-xs text-slate-500">
                        {{ __('Upload your organization\'s logo or provide a image URL and set brand colors for all team surveys.') }}
                    </p>
                </div>

                <form action="{{ route('organization.settings.branding') }}" method="POST" enctype="multipart/form-data"
                    class="space-y-6">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label
                                    class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">{{ __('Upload Organization Logo') }}</label>
                                <input type="file" name="logo_file" accept="image/*"
                                    @change="const file = $event.target.files[0]; if (file) { logoPreview = URL.createObjectURL(file); }"
                                    class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">{{ __('Or Image URL') }}</label>
                                <input type="url" name="logo_url" value="{{ old('logo_url', $org->logo_url) }}"
                                    @input="logoPreview = $event.target.value" placeholder="https://example.com/logo.png"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>

                        <!-- Live Logo Preview Box -->
                        <div
                            class="flex flex-col items-center justify-center p-4 bg-slate-50 border border-dashed border-slate-300 rounded-2xl">
                            <span
                                class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">{{ __('Live Logo Preview') }}</span>
                            <template x-if="logoPreview">
                                <img :src="logoPreview" alt="Logo Preview"
                                    class="h-20 max-w-full object-contain p-2 bg-white rounded-xl shadow-sm border border-slate-200">
                            </template>
                            <template x-if="!logoPreview">
                                <div class="text-center text-slate-400 py-4">
                                    <svg class="w-8 h-8 mx-auto opacity-40 mb-1" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-xs">{{ __('No logo image selected') }}</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                        <div>
                            <label
                                class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">{{ __('Brand Hex Color') }}</label>
                            <div class="flex items-center space-x-3">
                                <input type="color" name="brand_color_picker" value="{{ $org->brand_color ?: '#4f46e5' }}"
                                    class="w-10 h-10 rounded-lg cursor-pointer border-0"
                                    onchange="document.getElementById('brand_color_hex').value = this.value">
                                <input type="text" id="brand_color_hex" name="brand_color"
                                    value="{{ old('brand_color', $org->brand_color ?: '#4f46e5') }}"
                                    class="flex-1 px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="flex items-center space-x-3 pt-4">
                            <input type="checkbox" id="enforce_branding" name="enforce_branding" value="1" {{ $org->enforce_branding ? 'checked' : '' }}
                                class="w-5 h-5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            <label for="enforce_branding"
                                class="text-sm font-semibold text-slate-800">{{ __('Enforce workspace logo & brand color on all surveys automatically') }}</label>
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit"
                            class="px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm transition shadow-sm">{{ __('Save Branding Policy') }}</button>
                    </div>
                </form>
            </div>

            <!-- Card 2: Survey Approval Workflow -->
            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm space-y-6">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Survey Approval Governance</h2>
                    <p class="text-xs text-slate-500">Require workspace owner or admin approval before team members can
                        activate and publish surveys.</p>
                </div>

                <form action="{{ route('organization.settings.approval') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="flex items-center space-x-3">
                        <input type="checkbox" id="survey_approval_required" name="survey_approval_required" value="1" {{ $org->survey_approval_required ? 'checked' : '' }}
                            class="w-5 h-5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                        <label for="survey_approval_required" class="text-sm font-semibold text-slate-800">Require admin
                            approval before surveys can go live</label>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit"
                            class="px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm transition shadow-sm">Save
                            Approval Policy</button>
                    </div>
                </form>
            </div>

            <!-- Card 3: PII Privacy Defaults -->
            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm space-y-6">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">PII Masking & Privacy Controls</h2>
                    <p class="text-xs text-slate-500">Automatically mask respondent Personally Identifiable Information
                        (name, email, phone) for non-admin team members.</p>
                </div>

                <form action="{{ route('organization.settings.pii') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="flex items-center space-x-3">
                        <input type="checkbox" id="pii_mask_by_default" name="pii_mask_by_default" value="1" {{ $org->pii_mask_by_default ? 'checked' : '' }}
                            class="w-5 h-5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                        <label for="pii_mask_by_default" class="text-sm font-semibold text-slate-800">Mask PII data by
                            default across all survey responses for non-admin roles</label>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit"
                            class="px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm transition shadow-sm">Save
                            Privacy Settings</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
@endsection