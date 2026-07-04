@extends('surveys.hub')

@section('survey-content')
    <div class="max-w-4xl">
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-8 border-b border-gray-50">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('General Settings') }}</h3>
                <p class="text-xs text-gray-400 font-medium mt-1">{{ __('Manage survey metadata and lifecycle.') }}</p>
            </div>

            <style>
                .km-toggle-container {
                    display: inline-block;
                    position: relative;
                }

                .kd-toggle-checkbox {
                    display: none;
                }

                .km-toggle-bg {
                    width: 44px;
                    height: 24px;
                    background-color: #d1d5db;
                    border-radius: 999px;
                    position: relative;
                    cursor: pointer;
                    transition: background-color 0.2s;
                    display: inline-block;
                    vertical-align: middle;
                }

                .km-toggle-dot {
                    width: 18px;
                    height: 18px;
                    background-color: white;
                    border-radius: 50%;
                    position: absolute;
                    top: 3px;
                    left: 3px;
                    transition: transform 0.2s;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
                }

                .kd-toggle-checkbox:checked+.km-toggle-bg {
                    background-color: #4f46e5;
                }

                .kd-toggle-checkbox:checked+.km-toggle-bg .km-toggle-dot {
                    transform: translateX(20px);
                }
            </style>

            @if(session('success'))
                <div class="m-8 p-4 bg-green-50 border border-green-100 rounded-2xl">
                    <p class="text-xs text-green-700 font-bold uppercase tracking-widest">
                        <i class="fa-solid fa-circle-check mr-2"></i> {{ session('success') }}
                    </p>
                </div>
            @endif

            @if($errors->any())
                <div class="m-8 p-4 bg-red-50 border border-red-100 rounded-2xl">
                    <p class="text-xs text-red-700 font-bold uppercase tracking-widest mb-2">
                        <i class="fa-solid fa-circle-exclamation mr-2"></i> {{ __('There were errors with your submission:') }}
                    </p>
                    <ul class="list-disc list-inside text-[10px] text-red-600 font-bold uppercase tracking-tighter">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="p-8 space-y-12">
                <!-- Project Identification -->
                <form id="details-form" action="{{ route('surveys.settings.update', $survey) }}" method="POST">
                    @csrf
                    <section class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div>
                            <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-1">
                                {{ __('Survey Details') }}</h4>
                            <p class="text-[11px] text-gray-400 font-bold leading-relaxed">
                                {{ __('Basic information and data protection settings.') }}</p>
                        </div>
                        <div class="md:col-span-2 space-y-6">
                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">{{ __('Survey Title') }}</label>
                                <input type="text" name="title" value="{{ $survey->title }}"
                                    class="w-full bg-gray-50 border-gray-100 rounded-xl px-4 py-3 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">{{ __('Description') }}</label>
                                <textarea name="description" rows="3"
                                    class="w-full bg-gray-50 border-gray-100 rounded-xl px-4 py-3 text-sm font-medium text-gray-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">{{ $survey->description }}</textarea>
                            </div>

                            <div class="p-6 rounded-2xl border border-gray-100 bg-gray-50/50">
                                <label class="flex items-center space-x-3 cursor-pointer group">
                                    <div class="km-toggle-container">
                                        <input type="hidden" name="is_anonymous_present" value="1">
                                        <input type="checkbox" name="is_anonymous" value="1" {{ $survey->is_anonymous ? 'checked' : '' }} class="kd-toggle-checkbox" id="anon_toggle">
                                        <label for="anon_toggle" class="km-toggle-bg">
                                            <div class="km-toggle-dot"></div>
                                        </label>
                                    </div>
                                    <div>
                                        <span
                                            class="text-xs font-black text-gray-900 uppercase tracking-widest">{{ __('Allow Anonymous Submissions') }}</span>
                                        <p class="text-[10px] text-gray-400 font-bold mt-1 uppercase tracking-tighter">
                                            {{ __('Respondents can submit without logging in or providing identifying info.') }}
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </section>

                    <div class="flex justify-end mt-8">
                        <button type="submit"
                            class="px-8 py-3 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all">
                            {{ __('Update Survey Details') }}
                        </button>
                    </div>
                </form>

                <hr class="border-gray-50">

                <!-- Sharing & Access -->
                <section class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-1">
                            {{ __('Sharing & Public Access') }}
                        </h4>
                        <p class="text-[11px] text-gray-400 font-bold leading-relaxed">
                            {{ __('Control how the world sees your project.') }}</p>
                    </div>
                    <div class="md:col-span-2 space-y-6">

                        <div class="p-6 rounded-2xl border border-indigo-100 bg-indigo-50/20">
                            <label
                                class="block text-[10px] font-black text-indigo-600 uppercase tracking-widest mb-3">{{ __('Shareable Survey Link') }}</label>
                            @php
                                $shareUrl = route('surveys.show', ['survey' => $survey, 'token' => $survey->share_token]);
                            @endphp
                            <div class="flex items-center gap-2 mb-4">
                                <input type="text" readonly value="{{ $shareUrl }}"
                                    class="flex-1 bg-white border-gray-100 rounded-xl px-4 py-3 text-[11px] font-bold text-gray-600">
                                <button
                                    onclick="navigator.clipboard.writeText('{{ $shareUrl }}'); alert('{{ __('Link copied!') }}')"
                                    class="p-3 bg-white border border-gray-100 text-gray-400 rounded-xl hover:text-indigo-600 transition-all">
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}" target="_blank"
                                    class="w-10 h-10 rounded-full bg-black text-white flex items-center justify-center hover:scale-110 transition-transform">
                                    <i class="fa-brands fa-twitter"></i>
                                </a>
                                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}"
                                    target="_blank"
                                    class="w-10 h-10 rounded-full bg-[#1877F2] text-white flex items-center justify-center hover:scale-110 transition-transform">
                                    <i class="fa-brands fa-facebook-f"></i>
                                </a>
                                <a href="https://wa.me/?text={{ urlencode(__('Please take this survey: ') . $shareUrl) }}"
                                    target="_blank"
                                    class="w-10 h-10 rounded-full bg-[#25D366] text-white flex items-center justify-center hover:scale-110 transition-transform">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </a>
                                <a href="mailto:?subject={{ __('Survey Invitation') }}&body={{ __('Please take this survey: ') }}{{ urlencode($shareUrl) }}"
                                    class="w-10 h-10 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-envelope"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </section>

                <hr class="border-gray-50">

                <!-- Collaborators -->
                <section class="grid grid-cols-1 md:grid-cols-3 gap-8" x-data="{ addPanelOpen: false }">
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-1">{{ __('Collaborators') }}
                        </h4>
                        <p class="text-[11px] text-gray-400 font-bold leading-relaxed">
                            {{ __('Manage specific user access and roles.') }}</p>
                        <button @click="addPanelOpen = !addPanelOpen"
                            class="mt-4 px-4 py-2 border border-indigo-200 text-indigo-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-50 transition-all flex items-center gap-2">
                            <i class="fa-solid" :class="addPanelOpen ? 'fa-minus' : 'fa-plus'"></i>
                            <span
                                x-text="addPanelOpen ? '{{ __('Close Add Form') }}' : '{{ __('Add Collaborator') }}'"></span>
                        </button>
                    </div>
                    <div class="md:col-span-2 space-y-6">
                        <!-- Add Collaborator Panel -->
                        <div x-show="addPanelOpen" x-transition
                            class="p-6 bg-indigo-50/30 border border-indigo-100 rounded-2xl animate-in slide-in-from-top-2">
                            <form action="{{ route('surveys.collaborators.add', $survey) }}" method="POST"
                                class="space-y-6">
                                @csrf
                                <div>
                                    <label
                                        class="block text-[10px] font-black text-indigo-600 uppercase tracking-widest mb-2">{{ __('User Email') }}</label>
                                    <input type="email" name="email" required
                                        placeholder="{{ __('Collaborator Email...') }}"
                                        class="w-full bg-white border-gray-100 rounded-xl px-4 py-3 text-sm font-medium focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                                </div>

                                <div>
                                    <label
                                        class="block text-[10px] font-black text-indigo-600 uppercase tracking-widest mb-3">{{ __('Permissions') }}</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
                                        @php
                                            $perms = [
                                                ['key' => 'view_form', 'label' => __('View form')],
                                                ['key' => 'edit_form', 'label' => __('Edit form')],
                                                ['key' => 'view_submissions', 'label' => __('View submissions')],
                                                ['key' => 'add_submissions', 'label' => __('Add submissions')],
                                                ['key' => 'edit_submissions', 'label' => __('Edit submissions')],
                                                ['key' => 'validate_submissions', 'label' => __('Validate submissions')],
                                                ['key' => 'delete_submissions', 'label' => __('Delete submissions')],
                                                ['key' => 'manage_project', 'label' => __('Manage project')],
                                            ];
                                        @endphp
                                        @foreach($perms as $perm)
                                            <label class="flex items-center space-x-3 cursor-pointer group">
                                                <input type="checkbox" name="{{ $perm['key'] }}" value="1"
                                                    class="w-4 h-4 rounded border-indigo-200 text-indigo-600 focus:ring-indigo-500">
                                                <span
                                                    class="text-[11px] font-bold text-gray-600 group-hover:text-gray-900 transition-colors">{{ $perm['label'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="flex justify-end pt-4">
                                    <button type="submit"
                                        class="px-8 py-3 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-md shadow-indigo-100">
                                        {{ __('Save Collaborator') }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Collaborator List -->
                        <div class="space-y-3">
                            <div
                                class="p-5 bg-white border border-gray-100 rounded-3xl flex items-center justify-between shadow-sm">
                                <div class="flex items-center space-x-4">
                                    <div
                                        class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center text-xs font-black uppercase">
                                        {{ substr($survey->creator->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-gray-900">{{ $survey->creator->name }} <span
                                                class="ml-2 text-[9px] text-indigo-500 uppercase tracking-widest border border-indigo-100 px-2 py-0.5 rounded-full bg-indigo-50/50">{{ __('Owner') }}</span>
                                        </p>
                                        <p class="text-[10px] text-gray-400 font-bold lowercase tracking-wider">
                                            {{ $survey->creator->email }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="px-3 py-1 bg-indigo-50 text-indigo-600 text-[9px] font-black uppercase tracking-widest rounded-lg">{{ __('All Permissions') }}</span>
                                </div>
                            </div>

                            @foreach($survey->collaborators as $collaborator)
                                <div
                                    class="p-5 bg-white border border-gray-100 rounded-3xl flex items-center justify-between group shadow-sm hover:border-indigo-100 transition-all">
                                    <div class="flex items-center space-x-4">
                                        <div
                                            class="w-10 h-10 rounded-2xl bg-gray-100 text-gray-500 flex items-center justify-center text-xs font-black uppercase">
                                            {{ substr($collaborator->user->name, 0, 2) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-black text-gray-900">{{ $collaborator->user->name }}</p>
                                            <p class="text-[10px] text-gray-400 font-bold lowercase tracking-wider">
                                                {{ $collaborator->user->email }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        @php
                                            $colPermsCount = is_array($collaborator->permissions) ? count(array_filter($collaborator->permissions)) : 0;
                                        @endphp
                                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                            {{ $colPermsCount }} {{ __('Granular') }}
                                            {{ Str::plural(__('Permission'), $colPermsCount) }}
                                        </span>

                                        <form action="{{ route('surveys.collaborators.remove', [$survey, $collaborator]) }}"
                                            method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                <hr class="border-gray-50">

                <!-- Export Branding -->
                @php
                    $canBrand = auth()->user()->hasProAccess();
                @endphp
                <section class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-1">
                            {{ __('Export Branding') }}</h4>
                        <p class="text-[11px] text-gray-400 font-bold leading-relaxed">
                            {{ __('Control the branding on generated reports and data exports.') }}</p>
                        @if(!$canBrand)
                            <div class="mt-4 p-4 bg-amber-50 rounded-xl border border-amber-100">
                                <p class="text-[10px] text-amber-700 font-bold uppercase tracking-widest leading-relaxed">
                                    <i class="fa-solid fa-lock mr-1"></i>
                                    {{ __('Upgrade to Pro or Enterprise to unlock export branding controls.') }}
                                </p>
                            </div>
                        @endif
                    </div>
                    <div class="md:col-span-2">
                        <form action="{{ route('surveys.settings.update', $survey) }}" method="POST"
                            enctype="multipart/form-data"
                            class="space-y-6 {{ !$canBrand ? 'opacity-50 pointer-events-none' : '' }}">
                            @csrf

                            <!-- Toggle KDAnalytics Branding -->
                            <div
                                class="p-5 bg-gray-50 border border-gray-100 rounded-2xl flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ __('Remove KDAnalytics Branding') }}</p>
                                    <p class="text-[10px] text-gray-400 font-medium mt-1">
                                        {{ __('Remove the "Powered by KDAnalytics" marks from all exports.') }}</p>
                                </div>
                                <div class="km-toggle-container">
                                    <input type="hidden" name="remove_kd_branding_present" value="1">
                                    <input type="checkbox" name="remove_kd_branding" value="1" {{ $survey->remove_kd_branding ? 'checked' : '' }} class="kd-toggle-checkbox"
                                        id="brand_toggle">
                                    <label for="brand_toggle" class="km-toggle-bg">
                                        <div class="km-toggle-dot"></div>
                                    </label>
                                </div>
                            </div>

                            <hr class="border-gray-100">

                            <!-- Custom Org Branding -->
                            <div>
                                <h5 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-4">
                                    {{ __('Custom Organization Branding') }}</h5>
                                <div class="flex flex-col sm:flex-row gap-8">
                                    <div class="flex-1">
                                        <label
                                            class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">{{ __('Export Logo') }}</label>
                                        <div class="flex items-center gap-4">
                                            @if($survey->export_logo_url)
                                                <div
                                                    class="w-16 h-16 rounded-xl border border-gray-100 bg-white overflow-hidden flex items-center justify-center p-2 shadow-sm">
                                                    <img src="{{ route('surveys.branding.logo', $survey) }}" alt="Logo"
                                                        class="max-w-full max-h-full object-contain">
                                                </div>
                                            @else
                                                <div
                                                    class="w-16 h-16 rounded-xl border-2 border-dashed border-gray-200 flex items-center justify-center text-gray-300 bg-gray-50">
                                                    <i class="fa-solid fa-image text-xl"></i>
                                                </div>
                                            @endif
                                            <div class="flex-1">
                                                <input type="file" name="export_logo" accept="image/*"
                                                    class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:uppercase file:tracking-widest file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all cursor-pointer">
                                                <p class="mt-1 text-[10px] text-gray-400 font-medium">
                                                    {{ __('PNG or JPG, max 2MB. Applied to PDF header.') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <label
                                            class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">{{ __('Organization Name') }}</label>
                                        <input type="text" name="export_org_name" value="{{ $survey->export_org_name }}"
                                            placeholder="e.g. Acme Corp"
                                            class="w-full text-sm border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50 placeholder-gray-300">
                                        <p class="mt-1 text-[10px] text-gray-400 font-medium">
                                            {{ __('Text to display alongside or instead of the logo.') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end pt-4">
                                <button type="submit"
                                    class="px-8 py-3 bg-gray-900 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-gray-100 hover:bg-black transition-all">
                                    {{ __('Save Branding Preferences') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                <hr class="border-gray-50">

                <!-- Danger Zone -->
                <section class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <h4 class="text-xs font-black text-red-600 uppercase tracking-widest mb-1">{{ __('Danger Zone') }}
                        </h4>
                        <p class="text-[10px] text-gray-400 font-bold leading-relaxed">
                            {{ __('Irreversible actions that affect survey data and availability.') }}</p>
                    </div>
                    <div class="md:col-span-2 space-y-4">
                        @if($survey->status->value !== 'archived')
                            <div class="p-6 rounded-2xl border border-amber-100 bg-amber-50/50 flex items-center justify-between"
                                x-data="{ confirming: false }">
                                <div>
                                    <p class="text-xs font-bold text-amber-900 uppercase tracking-wider mb-1">
                                        {{ __('Archive Project') }}
                                    </p>
                                    <p class="text-xs text-amber-600 font-medium lowercase">
                                        {{ __('Stop collection but keep data available for analytical reports.') }}</p>
                                </div>
                                <form id="archive-form-{{ $survey->id }}" action="{{ route('surveys.archive', $survey) }}"
                                    method="POST" class="hidden">
                                    @csrf
                                </form>
                                <button type="button" @click="
                                                                                Swal.fire({
                                                                                    title: '{{ __('Archive Project?') }}',
                                                                                    html: '<p class=\'text-sm\'>{{ __('You are about to archive') }} <b>{{ addslashes($survey->title) }}</b>. {{ __('It will be moved to the archive and will no longer accept new submissions.') }}</p>',
                                                                                    icon: 'info',
                                                                                    showCancelButton: true,
                                                                                    confirmButtonText: '{{ __('Yes, Archive It') }}',
                                                                                    cancelButtonText: '{{ __('Cancel') }}',
                                                                                    confirmButtonColor: '#d97706',
                                                                                    cancelButtonColor: '#4b5563',
                                                                                    reverseButtons: true,
                                                                                    customClass: {
                                                                                        popup: 'rounded-3xl',
                                                                                        confirmButton: 'rounded-xl font-bold px-6 py-3',
                                                                                        cancelButton: 'rounded-xl font-bold px-6 py-3'
                                                                                    }
                                                                                }).then((result) => {
                                                                                    if (result.isConfirmed) {
                                                                                        document.getElementById('archive-form-{{ $survey->id }}').submit();
                                                                                    }
                                                                                });
                                                                            "
                                    class="px-6 py-2 bg-amber-600 text-white rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-amber-700 transition-all shadow-sm border border-amber-100">
                                    {{ __('Archive Project') }}
                                </button>
                            </div>
                        @endif

                        <div class="p-6 rounded-2xl border border-red-100 bg-red-50/30 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-red-900 uppercase tracking-wider mb-1">
                                    {{ __('Delete Project') }}</p>
                                <p class="text-xs text-red-600 font-medium leading-tight">
                                    {{ __('Permanently remove form, metadata, and ALL submission data.') }}</p>
                            </div>
                            <form id="delete-form-{{ $survey->id }}" action="{{ route('surveys.destroy', $survey) }}"
                                method="POST" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                            <button type="button" @click="
                                                        Swal.fire({
                                                            title: '{{ __('Delete Project?') }}',
                                                            html: '<p class=\'text-sm\'>{{ __('You are about to delete') }} <b>{{ addslashes($survey->title) }}</b> {{ __('and all its associated data.') }}<br><br><span class=\'text-red-500 font-bold uppercase text-[10px] tracking-widest\'>{{ __('This action cannot be undone.') }}</span></p>',
                                                            icon: 'warning',
                                                            showCancelButton: true,
                                                            confirmButtonText: '{{ __('Yes, Delete Permanently') }}',
                                                            cancelButtonText: '{{ __('Cancel') }}',
                                                            confirmButtonColor: '#ef4444',
                                                            cancelButtonColor: '#1e293b',
                                                            reverseButtons: true,
                                                            customClass: {
                                                                popup: 'rounded-3xl',
                                                                confirmButton: 'rounded-xl font-bold px-6 py-3',
                                                                cancelButton: 'rounded-xl font-bold px-6 py-3'
                                                            }
                                                        }).then((result) => {
                                                            if (result.isConfirmed) {
                                                                document.getElementById('delete-form-{{ $survey->id }}').submit();
                                                            }
                                                        });
                                                    "
                                class="px-6 py-2 bg-red-600 text-white rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-red-700 transition-all shadow-sm border border-red-100">
                                {{ __('Delete Project') }}
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            <div class="p-8 bg-gray-50 border-t border-gray-100 flex justify-end">
                <button type="submit" form="details-form"
                    class="px-8 py-3 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all">
                    {{ __('Save Settings') }}
                </button>
            </div>
        </div>
    </div>
@endsection