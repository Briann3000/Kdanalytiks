@extends('surveys.hub')

@section('survey-content')
    <div class="max-w-4xl mx-auto space-y-6" x-data="{
            addModalOpen: false,
            transferModalOpen: false,
            editModalOpen: false,
            activeCollaborator: null,
            activePerms: {
                view_form: true,
                edit_form: false,
                view_submissions: true,
                add_submissions: true,
                edit_submissions: false,
                validate_submissions: false,
                delete_submissions: false,
                manage_project: false
            },
            openEditModal(col) {
                this.activeCollaborator = col;
                this.activePerms = {
                    view_form: !!(col.permissions && col.permissions.view_form),
                    edit_form: !!(col.permissions && col.permissions.edit_form),
                    view_submissions: !!(col.permissions && col.permissions.view_submissions),
                    add_submissions: !!(col.permissions && col.permissions.add_submissions),
                    edit_submissions: !!(col.permissions && col.permissions.edit_submissions),
                    validate_submissions: !!(col.permissions && col.permissions.validate_submissions),
                    delete_submissions: !!(col.permissions && col.permissions.delete_submissions),
                    manage_project: !!(col.permissions && col.permissions.manage_project)
                };
                this.editModalOpen = true;
            }
        }">

        <style>
            .kd-toggle-container {
                display: inline-block;
                position: relative;
            }

            .kd-toggle-checkbox {
                display: none;
            }

            .kd-toggle-bg {
                width: 40px;
                height: 22px;
                background-color: #e5e7eb;
                border-radius: 999px;
                position: relative;
                cursor: pointer;
                transition: background-color 0.2s;
                display: inline-block;
                vertical-align: middle;
            }

            .kd-toggle-dot {
                width: 16px;
                height: 16px;
                background-color: white;
                border-radius: 50%;
                position: absolute;
                top: 3px;
                left: 3px;
                transition: transform 0.2s;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            }

            .kd-toggle-checkbox:checked+.kd-toggle-bg {
                background-color: #2271b1;
            }

            .kd-toggle-checkbox:checked+.kd-toggle-bg .kd-toggle-dot {
                transform: translateX(18px);
            }
        </style>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="p-3.5 bg-emerald-50/80 border border-emerald-200/80 rounded-xl flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                <p class="text-xs text-emerald-800 font-semibold">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="p-3.5 bg-rose-50/80 border border-rose-200/80 rounded-xl flex items-center gap-3">
                <i class="fa-solid fa-triangle-exclamation text-rose-600 text-sm"></i>
                <p class="text-xs text-rose-800 font-semibold">{{ session('error') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="p-3.5 bg-rose-50/80 border border-rose-200/80 rounded-xl space-y-1">
                <p class="text-xs text-rose-800 font-bold flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                    {{ __('There were errors with your submission:') }}
                </p>
                <ul class="list-disc list-inside text-xs text-rose-700 font-medium pl-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Continuous Minimalist Container -->
        <div class="bg-white border border-gray-200/80 rounded-2xl shadow-2xs divide-y divide-gray-100">

            <!-- ========================================== -->
            <!-- 1. GENERAL DETAILS -->
            <!-- ========================================== -->
            <section class="p-6 sm:p-8 space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-black text-gray-900 uppercase tracking-widest flex items-center gap-2">
                            <i class="fa-solid fa-sliders text-[#2271b1]"></i>
                            {{ __('General Details') }}
                        </h2>
                        <p class="text-[11px] text-gray-400 font-medium mt-0.5">
                            {{ __('Project title, description, and anonymity settings.') }}</p>
                    </div>
                    <span
                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-gray-100 text-gray-600">
                        {{ __(ucfirst($survey->status->value ?? 'Active')) }}
                    </span>
                </div>

                <form action="{{ route('surveys.settings.update', $survey) }}" method="POST" class="space-y-5">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                {{ __('Survey Title') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="title" value="{{ $survey->title }}" required
                                class="w-full bg-gray-50/60 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-gray-900 focus:bg-white focus:ring-2 focus:ring-[#2271b1]/10 focus:border-[#2271b1] transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                {{ __('Description & Instructions') }}
                            </label>
                            <textarea name="description" rows="3"
                                class="w-full bg-gray-50/60 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs font-medium text-gray-900 focus:bg-white focus:ring-2 focus:ring-[#2271b1]/10 focus:border-[#2271b1] transition-all"
                                placeholder="{{ __('Add introductory instructions for respondents...') }}">{{ $survey->description }}</textarea>
                        </div>

                        <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/40 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                                    {{ __('Allow Anonymous Submissions') }}
                                </span>
                                <p class="text-[11px] text-gray-400 font-medium mt-0.5">
                                    {{ __('Respondents can participate without signing in or providing identifying information.') }}
                                </p>
                            </div>
                            <div class="kd-toggle-container flex-shrink-0 ml-4">
                                <input type="hidden" name="is_anonymous_present" value="1">
                                <input type="checkbox" name="is_anonymous" value="1" {{ $survey->is_anonymous ? 'checked' : '' }} class="kd-toggle-checkbox" id="anon_toggle">
                                <label for="anon_toggle" class="kd-toggle-bg">
                                    <div class="kd-toggle-dot"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-1">
                        <button type="submit"
                            class="px-5 py-2 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-xl font-bold text-xs uppercase tracking-wider shadow-2xs transition-all flex items-center gap-2">
                            <i class="fa-solid fa-floppy-disk text-xs"></i>
                            <span>{{ __('Save Details') }}</span>
                        </button>
                    </div>
                </form>
            </section>

            <!-- ========================================== -->
            <!-- 2. SHARING & COLLABORATORS -->
            <!-- ========================================== -->
            <section class="p-6 sm:p-8 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-black text-gray-900 uppercase tracking-widest flex items-center gap-2">
                            <i class="fa-solid fa-users-gear text-indigo-600"></i>
                            {{ __('Sharing & Collaborators') }}
                        </h2>
                        <p class="text-[11px] text-gray-400 font-medium mt-0.5">
                            {{ __('Manage project ownership and team permissions.') }}
                        </p>
                    </div>
                    <button type="button" @click="addModalOpen = true"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow-2xs transition-all self-start sm:self-auto">
                        <i class="fa-solid fa-user-plus text-xs"></i>
                        <span>{{ __('Add Collaborator') }}</span>
                    </button>
                </div>

                <!-- Who Has Access List -->
                <div class="space-y-3">
                    <!-- Owner Row -->
                    <div
                        class="p-4 bg-gray-50/60 border border-gray-200/80 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-9 h-9 rounded-xl bg-[#2271b1] text-white flex items-center justify-center text-xs font-black uppercase flex-shrink-0 shadow-2xs">
                                {{ substr($survey->creator->name ?? 'User', 0, 2) }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span
                                        class="text-xs font-bold text-gray-900">{{ $survey->creator->name ?? __('Unknown Owner') }}</span>
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-blue-50 text-[#2271b1] border border-blue-200">
                                        <i class="fa-solid fa-crown text-[8px] mr-0.5"></i>{{ __('Owner') }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-gray-400 font-medium">
                                    {{ $survey->creator->email ?? '' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 self-end sm:self-center">
                            <span
                                class="text-[10px] font-bold text-gray-500 uppercase tracking-wider bg-white px-2.5 py-1 border border-gray-200 rounded-lg">
                                {{ __('Full Access') }}
                            </span>
                            @if(auth()->id() === (int) $survey->created_by || auth()->user()->isAdmin())
                                <button type="button" @click="transferModalOpen = true"
                                    class="px-2.5 py-1 bg-white border border-gray-200 hover:border-amber-400 hover:text-amber-700 text-gray-600 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all flex items-center gap-1"
                                    title="{{ __('Transfer ownership to another user') }}">
                                    <i class="fa-solid fa-arrow-right-arrow-left text-amber-500 text-[10px]"></i>
                                    <span>{{ __('Transfer') }}</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    @if(!empty($survey->pending_owner_email))
                        <!-- Pending Ownership Transfer Banner -->
                        <div
                            class="p-4 bg-amber-50/80 border border-amber-300 rounded-xl flex flex-col md:flex-row md:items-center justify-between gap-3 transition-all">
                            <div class="flex items-start gap-3">
                                <div
                                    class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center text-xs font-black uppercase flex-shrink-0 shadow-2xs">
                                    <i class="fa-solid fa-hourglass-half text-sm"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs font-bold text-amber-950">{{ __('Pending Ownership Transfer') }}</span>
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300">
                                            {{ __('Awaiting Account Claim') }}
                                        </span>
                                    </div>
                                    <p class="text-xs font-bold text-amber-900 mt-0.5">
                                        {{ $survey->pending_owner_email }}
                                    </p>
                                    <p class="text-[11px] text-amber-700 font-medium mt-0.5">
                                        {{ __('An invitation email was sent to this address. As soon as they register or log in, primary ownership will automatically transfer.') }}
                                    </p>
                                </div>
                            </div>

                            @if(auth()->id() === (int) $survey->created_by || auth()->user()->isAdmin())
                                <div class="flex items-center gap-2 self-end md:self-center flex-shrink-0">
                                    <form action="{{ route('surveys.transfer_ownership.resend', $survey) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            class="px-3 py-1.5 bg-white hover:bg-amber-100 text-amber-800 border border-amber-300 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all flex items-center gap-1 shadow-2xs">
                                            <i class="fa-solid fa-paper-plane text-[9px]"></i>
                                            <span>{{ __('Resend Invite') }}</span>
                                        </button>
                                    </form>

                                    <form action="{{ route('surveys.transfer_ownership.cancel', $survey) }}" method="POST"
                                        onsubmit="return confirm('{{ __('Are you sure you want to cancel the pending ownership transfer?') }}')">
                                        @csrf
                                        <button type="submit"
                                            class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all flex items-center gap-1 shadow-2xs">
                                            <i class="fa-solid fa-xmark text-xs"></i>
                                            <span>{{ __('Cancel Transfer') }}</span>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Collaborator Rows -->
                    @php
                        $permissionLabels = [
                            'view_form' => __('View form'),
                            'edit_form' => __('Edit form'),
                            'view_submissions' => __('View submissions'),
                            'add_submissions' => __('Add submissions'),
                            'edit_submissions' => __('Edit submissions'),
                            'validate_submissions' => __('Validate submissions'),
                            'delete_submissions' => __('Delete submissions'),
                            'manage_project' => __('Manage project'),
                        ];
                    @endphp

                    @forelse($survey->collaborators as $col)
                        @php
                            $isPending = $col->isPending() || !$col->user_id;
                            $displayName = $col->user ? $col->user->name : ($col->invite_email ?? __('Pending Invite'));
                            $displayEmail = $col->user ? $col->user->email : $col->invite_email;
                            $userInitials = $col->user ? substr($col->user->name, 0, 2) : substr($displayEmail, 0, 2);
                        @endphp
                        <div
                            class="p-4 bg-white border border-gray-100 hover:border-gray-200 rounded-xl flex flex-col lg:flex-row lg:items-center justify-between gap-3 transition-all">
                            <div class="flex items-start gap-3">
                                <div
                                    class="w-9 h-9 rounded-xl {{ $isPending ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-700' }} flex items-center justify-center text-xs font-black uppercase flex-shrink-0">
                                    {{ $userInitials }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs font-bold text-gray-900">{{ $displayName }}</span>
                                        @if($isPending)
                                            <span
                                                class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200">
                                                <i class="fa-solid fa-clock text-[8px] mr-0.5"></i>{{ __('Pending') }}
                                            </span>
                                        @else
                                            <span
                                                class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <i class="fa-solid fa-check text-[8px] mr-0.5"></i>{{ __('Accepted') }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] text-gray-400 font-medium">
                                        {{ $displayEmail }}
                                    </p>

                                    <!-- Permission Badges List -->
                                    <div class="flex items-center flex-wrap gap-1.5 mt-2">
                                        @php
                                            $colPerms = is_array($col->permissions) ? $col->permissions : [];
                                            $activeCount = 0;
                                        @endphp
                                        @foreach($permissionLabels as $k => $label)
                                            @if(!empty($colPerms[$k]))
                                                @php $activeCount++; @endphp
                                                <span
                                                    class="px-2 py-0.5 bg-indigo-50 border border-indigo-100 text-indigo-700 rounded-md text-[9px] font-bold">
                                                    {{ $label }}
                                                </span>
                                            @endif
                                        @endforeach
                                        @if($activeCount === 0)
                                            <span class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded-md text-[9px] font-bold">
                                                {{ __('No Permissions') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5 self-end lg:self-center">
                                @if($isPending)
                                    <form action="{{ route('surveys.collaborators.resend', [$survey, $col]) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all flex items-center gap-1"
                                            title="{{ __('Resend invitation email') }}">
                                            <i class="fa-solid fa-paper-plane text-[9px]"></i>
                                            <span>{{ __('Resend') }}</span>
                                        </button>
                                    </form>
                                @endif

                                <button type="button" @click="openEditModal(@js($col))"
                                    class="px-2.5 py-1 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all flex items-center gap-1">
                                    <i class="fa-solid fa-pen-to-square text-gray-400 text-[10px]"></i>
                                    <span>{{ __('Edit') }}</span>
                                </button>

                                <form action="{{ route('surveys.collaborators.remove', [$survey, $col]) }}" method="POST"
                                    onsubmit="return confirm('{{ __('Are you sure you want to revoke collaborator access for this user?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all"
                                        title="{{ __('Revoke access') }}">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 bg-gray-50/40 border border-dashed border-gray-200 rounded-xl text-center">
                            <p class="text-xs font-bold text-gray-500">{{ __('No external collaborators added yet.') }}</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">
                                {{ __('Click "Add Collaborator" above to invite registered users or external emails.') }}</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <!-- ========================================== -->
            <!-- 3. PUBLIC ACCESS & DISTRIBUTION -->
            <!-- ========================================== -->
            <section class="p-6 sm:p-8 space-y-6">
                <div>
                    <h2 class="text-sm font-black text-gray-900 uppercase tracking-widest flex items-center gap-2">
                        <i class="fa-solid fa-globe text-[#2271b1]"></i>
                        {{ __('Public Access & Link Distribution') }}
                    </h2>
                    <p class="text-[11px] text-gray-400 font-medium mt-0.5">
                        {{ __('Separate URLs for survey respondents vs. public data viewing.') }}
                    </p>
                </div>

                <div class="space-y-4">
                    <!-- LINK 1: LIVE SURVEY LINK -->
                    @php
                        $shareUrl = route('surveys.show', ['survey' => $survey, 'token' => $survey->share_token]);
                        $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($shareUrl);
                    @endphp
                    <div class="p-4 rounded-xl border border-gray-200 bg-white space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-gray-900">
                                    {{ __('For Respondents') }}
                                </span>
                            </div>
                            <span class="text-[10px] font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-md uppercase tracking-wider">
                                {{ __('Active') }}
                            </span>
                        </div>

                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            <input type="text" readonly value="{{ $shareUrl }}"
                                class="w-full sm:flex-1 bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-xs font-mono text-gray-700 select-all">
                            <div class="flex items-center gap-2 w-full sm:w-auto">
                                <button type="button"
                                    onclick="navigator.clipboard.writeText('{{ $shareUrl }}'); alert('{{ __('Survey link copied to clipboard!') }}')"
                                    class="flex-1 sm:flex-initial px-3.5 py-2 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-1.5 shadow-2xs">
                                    <i class="fa-solid fa-copy"></i>
                                    <span>{{ __('Copy') }}</span>
                                </button>
                                <a href="{{ $shareUrl }}" target="_blank"
                                    class="flex-1 sm:flex-initial px-3.5 py-2 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-1.5 shadow-2xs"
                                    title="{{ __('Open survey in new tab') }}">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    <span>{{ __('Open') }}</span>
                                </a>
                            </div>
                        </div>

                        <div class="flex items-center justify-between flex-wrap gap-3 pt-2 border-t border-gray-100">
                            <div class="flex items-center gap-1.5">
                                <span
                                    class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mr-1">{{ __('Share:') }}</span>
                                <a href="https://wa.me/?text={{ urlencode(__('Please participate in this survey: ') . $shareUrl) }}"
                                    target="_blank"
                                    class="w-6 h-6 rounded-full bg-[#25D366] text-white flex items-center justify-center text-[10px] hover:scale-110 transition-transform shadow-2xs"
                                    title="{{ __('Share via WhatsApp') }}">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </a>
                                <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}" target="_blank"
                                    class="w-6 h-6 rounded-full bg-black text-white flex items-center justify-center text-[10px] hover:scale-110 transition-transform shadow-2xs"
                                    title="{{ __('Share on X / Twitter') }}">
                                    <i class="fa-brands fa-x-twitter"></i>
                                </a>
                                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}"
                                    target="_blank"
                                    class="w-6 h-6 rounded-full bg-[#1877F2] text-white flex items-center justify-center text-[10px] hover:scale-110 transition-transform shadow-2xs"
                                    title="{{ __('Share on Facebook') }}">
                                    <i class="fa-brands fa-facebook-f"></i>
                                </a>
                                <a href="mailto:?subject={{ urlencode(__('Survey Invitation: ') . $survey->title) }}&body={{ urlencode(__('Please take a moment to complete this survey: ') . $shareUrl) }}"
                                    class="w-6 h-6 rounded-full bg-gray-200 text-gray-700 flex items-center justify-center text-[10px] hover:scale-110 transition-transform shadow-2xs"
                                    title="{{ __('Share via Email') }}">
                                    <i class="fa-solid fa-envelope"></i>
                                </a>
                            </div>

                            <a href="{{ $qrApiUrl }}" download="survey_qr_{{ $survey->id }}.png" target="_blank"
                                class="inline-flex items-center gap-1 px-2.5 py-1 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 rounded-lg text-[10px] font-bold transition-all shadow-2xs">
                                <i class="fa-solid fa-qrcode"></i>
                                <span>{{ __('Download QR') }}</span>
                            </a>
                        </div>
                    </div>

                    <!-- LINK 2: PUBLIC DATA LINK (READ-ONLY DATA & CHARTS) -->
                    <div class="p-4 rounded-xl border border-gray-200 bg-white space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-gray-900">
                                    {{ __('Read-Only Data & Charts') }}
                                </span>
                            </div>

                            <form action="{{ route('surveys.toggle-shared-data', $survey) }}" method="POST">
                                @csrf
                                @if($survey->public_data_enabled)
                                    <input type="hidden" name="disable" value="1">
                                    <button type="submit"
                                        class="px-3 py-1 bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all shadow-2xs">
                                        {{ __('Disable') }}
                                    </button>
                                @else
                                    <button type="submit"
                                        class="px-3 py-1 bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg text-[10px] font-bold uppercase tracking-wider shadow-2xs transition-all flex items-center gap-1">
                                        <i class="fa-solid fa-toggle-on text-[10px]"></i>
                                        {{ __('Activate') }}
                                    </button>
                                @endif
                            </form>
                        </div>

                        @if($survey->public_data_enabled && $survey->share_data_token)
                            @php
                                $sharedDataUrl = route('surveys.shared_data', $survey->share_data_token);
                                $sharedDataQrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($sharedDataUrl);
                            @endphp
                            <div class="pt-2 border-t border-gray-100 space-y-2.5">
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                    <input type="text" readonly value="{{ $sharedDataUrl }}"
                                        class="w-full sm:flex-1 bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-xs font-mono text-gray-700 select-all">
                                    <div class="flex items-center gap-2 w-full sm:w-auto">
                                        <button type="button"
                                            onclick="navigator.clipboard.writeText('{{ $sharedDataUrl }}'); alert('{{ __('Public data link copied to clipboard!') }}')"
                                            class="flex-1 sm:flex-initial px-3.5 py-2 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-1.5 shadow-2xs">
                                            <i class="fa-solid fa-copy"></i>
                                            <span>{{ __('Copy') }}</span>
                                        </button>
                                        <a href="{{ $sharedDataUrl }}" target="_blank"
                                            class="flex-1 sm:flex-initial px-3.5 py-2 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-1.5 shadow-2xs"
                                            title="{{ __('Open public hub in new tab') }}">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            <span>{{ __('Open') }}</span>
                                        </a>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between flex-wrap gap-3 pt-2 border-t border-gray-100">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mr-1">{{ __('Share:') }}</span>
                                        <a href="https://wa.me/?text={{ urlencode(__('View public survey dataset and reports: ') . $sharedDataUrl) }}"
                                            target="_blank"
                                            class="w-6 h-6 rounded-full bg-[#25D366] text-white flex items-center justify-center text-[10px] hover:scale-110 transition-transform shadow-2xs"
                                            title="{{ __('Share via WhatsApp') }}">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                        <a href="mailto:?subject={{ urlencode(__('Survey Public Data Hub: ') . $survey->title) }}&body={{ urlencode(__('You can explore the live dataset and reports here: ') . $sharedDataUrl) }}"
                                            class="w-6 h-6 rounded-full bg-gray-200 text-gray-700 flex items-center justify-center text-[10px] hover:scale-110 transition-transform shadow-2xs"
                                            title="{{ __('Share via Email') }}">
                                            <i class="fa-solid fa-envelope"></i>
                                        </a>
                                    </div>

                                    <a href="{{ $sharedDataQrUrl }}" download="public_data_qr_{{ $survey->id }}.png"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 rounded-lg text-[10px] font-bold transition-all shadow-2xs">
                                        <i class="fa-solid fa-qrcode"></i>
                                        <span>{{ __('Download QR') }}</span>
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Embed Code Snippet -->
                    @php
                        $embedSnippet = '<iframe src="' . $shareUrl . '" width="100%" height="700px" frameborder="0" style="border:0; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);"></iframe>';
                    @endphp
                    <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/40 space-y-2"
                        x-data="{ showEmbed: false }">
                        <div class="flex items-center justify-between">
                            <button type="button" @click="showEmbed = !showEmbed"
                                class="text-xs font-bold text-gray-700 hover:text-gray-900 flex items-center gap-1.5 uppercase tracking-wider">
                                <i class="fa-solid text-[10px]"
                                    :class="showEmbed ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                <span>{{ __('Website Embed Code (iframe)') }}</span>
                            </button>
                            <button type="button"
                                onclick="navigator.clipboard.writeText('{{ addslashes($embedSnippet) }}'); alert('{{ __('Embed code copied to clipboard!') }}')"
                                class="px-2.5 py-1 bg-white border border-gray-200 text-gray-600 hover:text-[#2271b1] rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all shadow-2xs flex items-center gap-1">
                                <i class="fa-solid fa-code text-[10px]"></i>
                                <span>{{ __('Copy Snippet') }}</span>
                            </button>
                        </div>
                        <div x-show="showEmbed" x-collapse>
                            <textarea readonly rows="2"
                                class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-[11px] font-mono text-gray-700 select-all">{{ $embedSnippet }}</textarea>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ========================================== -->
            <!-- 4. ANALYSIS GROUPS -->
            <!-- ========================================== -->
            <section class="p-6 sm:p-8 space-y-5" x-data="{ addGroupOpen: false }">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-black text-gray-900 uppercase tracking-widest flex items-center gap-2">
                            <i class="fa-solid fa-people-group text-[#2271b1]"></i>
                            {{ __('Analysis Groups') }}
                        </h2>
                        <p class="text-[11px] text-gray-400 font-medium mt-0.5">
                            {{ __('Isolated workspaces for student cohorts or team analysts.') }}
                        </p>
                    </div>
                    <button type="button" @click="addGroupOpen = !addGroupOpen"
                        class="px-3.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-xl text-xs font-bold uppercase tracking-wider transition-all flex items-center gap-1.5">
                        <i class="fa-solid text-[10px]" :class="addGroupOpen ? 'fa-minus' : 'fa-plus'"></i>
                        <span x-text="addGroupOpen ? '{{ __('Cancel') }}' : '{{ __('Create Group') }}'"></span>
                    </button>
                </div>

                <!-- Add Group Input -->
                <div x-show="addGroupOpen" x-collapse style="display: none;">
                    <form action="{{ route('surveys.groups.create', $survey) }}" method="POST"
                        class="p-4 bg-gray-50 border border-gray-200 rounded-xl">
                        @csrf
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            <input type="text" name="name" required
                                class="flex-1 px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-xs font-medium focus:border-[#2271b1] focus:ring-1 focus:ring-[#2271b1]"
                                placeholder="{{ __('Enter group name (e.g. Research Cohort Alpha)...') }}">
                            <button type="submit"
                                class="px-4 py-2 bg-[#2271b1] hover:bg-[#135e96] text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-2xs">
                                {{ __('Create Group') }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Groups List -->
                <div class="space-y-2">
                    @forelse($survey->groups as $group)
                        @php
                            $joinUrl = route('surveys.groups.join', ['survey' => $survey, 'token' => $group->token]);
                        @endphp
                        <div
                            class="p-3.5 bg-gray-50/50 border border-gray-100 rounded-xl flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <div>
                                <span class="text-xs font-bold text-gray-900">{{ $group->name }}</span>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span
                                        class="text-[9px] font-bold text-[#2271b1] bg-blue-50 border border-blue-100 px-1.5 py-0.2 rounded-md uppercase tracking-wider">
                                        {{ $group->users_count ?? $group->users->count() }} {{ __('Members') }}
                                    </span>
                                    <span class="text-[9px] text-gray-400">
                                        {{ __('Created') }} {{ $group->created_at->diffForHumans() }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                                <div class="relative flex items-center flex-1 sm:flex-initial" x-data="{ copied: false }">
                                    <input type="text" readonly value="{{ $joinUrl }}"
                                        class="w-full sm:w-48 px-2.5 py-1 bg-white border border-gray-200 rounded-lg text-[10px] font-mono text-gray-600 select-all">
                                    <button type="button"
                                        @click="navigator.clipboard.writeText('{{ $joinUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="ml-1.5 px-2 py-1 bg-white border border-gray-200 rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-gray-100 transition-all flex-shrink-0">
                                        <span x-text="copied ? '{{ __('Copied!') }}' : '{{ __('Copy Link') }}'"></span>
                                    </button>
                                </div>

                                <form action="{{ route('surveys.groups.destroy', [$survey, $group]) }}" method="POST"
                                    onsubmit="return confirm('{{ __('Delete this analysis group?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="p-1 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all"
                                        title="{{ __('Delete group') }}">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 bg-gray-50/40 border border-dashed border-gray-200 rounded-xl text-center">
                            <p class="text-xs font-bold text-gray-500">{{ __('No analysis groups created yet.') }}</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <!-- ========================================== -->
            <!-- 5. EXPORT BRANDING & DATA BUNDLES -->
            <!-- ========================================== -->
            @php
                $canBrand = auth()->user() && auth()->user()->hasProAccess();
            @endphp
            <section class="p-6 sm:p-8 space-y-6">
                <div>
                    <h2 class="text-sm font-black text-gray-900 uppercase tracking-widest flex items-center gap-2">
                        <i class="fa-solid fa-palette text-[#2271b1]"></i>
                        {{ __('Export Branding & Data Bundles') }}
                    </h2>
                    <p class="text-[11px] text-gray-400 font-medium mt-0.5">
                        {{ __('White-label PDF/Word export branding and offline dataset bundles.') }}
                    </p>
                </div>

                <!-- Branding Form -->
                <form action="{{ route('surveys.settings.update', $survey) }}" method="POST" enctype="multipart/form-data"
                    class="space-y-5">
                    @csrf
                    @if(!$canBrand)
                        <div class="p-3.5 bg-amber-50/80 border border-amber-200 rounded-xl flex items-center gap-3">
                            <i class="fa-solid fa-crown text-amber-600 text-sm flex-shrink-0"></i>
                            <p class="text-xs text-amber-900 font-bold leading-relaxed">
                                {{ __('Upgrade to Pro or Enterprise to unlock custom white-label export branding.') }}
                            </p>
                        </div>
                    @endif

                    <div class="space-y-4 {{ !$canBrand ? 'opacity-60 pointer-events-none' : '' }}">
                        <!-- Remove KD Branding Toggle -->
                        <div class="p-4 bg-gray-50/60 border border-gray-100 rounded-xl flex items-center justify-between">
                            <div>
                                <span
                                    class="text-xs font-bold text-gray-900 uppercase tracking-wider">{{ __('Remove KDAnalytiks Branding') }}</span>
                                <p class="text-[11px] text-gray-400 font-medium mt-0.5">
                                    {{ __('Hide the "Powered by KDAnalytiks" mark from generated export files.') }}</p>
                            </div>
                            <div class="kd-toggle-container flex-shrink-0 ml-4">
                                <input type="hidden" name="remove_kd_branding_present" value="1">
                                <input type="checkbox" name="remove_kd_branding" value="1" {{ $survey->remove_kd_branding ? 'checked' : '' }} class="kd-toggle-checkbox" id="brand_toggle">
                                <label for="brand_toggle" class="kd-toggle-bg">
                                    <div class="kd-toggle-dot"></div>
                                </label>
                            </div>
                        </div>

                        <!-- Export Logo & Org Name -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">{{ __('Export Header Logo') }}</label>
                                <div class="flex items-center gap-3">
                                    @if($survey->export_logo_url)
                                        <div
                                            class="w-12 h-12 rounded-xl border border-gray-200 bg-white overflow-hidden flex items-center justify-center p-1 shadow-2xs">
                                            <img src="{{ route('surveys.branding.logo', $survey) }}" alt="Logo"
                                                class="max-w-full max-h-full object-contain">
                                        </div>
                                    @else
                                        <div
                                            class="w-12 h-12 rounded-xl border border-dashed border-gray-200 flex items-center justify-center text-gray-300 bg-gray-50">
                                            <i class="fa-solid fa-image text-sm"></i>
                                        </div>
                                    @endif
                                    <div class="flex-1">
                                        <input type="file" name="export_logo" accept="image/*"
                                            class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:uppercase file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 transition-all cursor-pointer">
                                        <p class="mt-0.5 text-[9px] text-gray-400 font-medium">{{ __('PNG/JPG, max 2MB.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">{{ __('Organization / Institution Name') }}</label>
                                <input type="text" name="export_org_name" value="{{ $survey->export_org_name }}"
                                    placeholder="{{ __('e.g. Acme Research Institute') }}"
                                    class="w-full bg-gray-50/60 border border-gray-200 rounded-xl px-3.5 py-2 text-xs font-medium focus:bg-white focus:border-[#2271b1] focus:ring-1 focus:ring-[#2271b1]">
                                <p class="mt-0.5 text-[9px] text-gray-400 font-medium">
                                    {{ __('Displayed on PDF & Word export title headers.') }}</p>
                            </div>
                        </div>

                        <div class="flex justify-end pt-1">
                            <button type="submit"
                                class="px-5 py-2 bg-gray-900 hover:bg-black text-white rounded-xl font-bold text-xs uppercase tracking-wider transition-all shadow-2xs">
                                {{ __('Save Branding Preferences') }}
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Data Portability Exports -->
                <div class="pt-4 border-t border-gray-100 space-y-3">
                    <span
                        class="block text-xs font-bold text-gray-700 uppercase tracking-wider">{{ __('Offline Portability & Data Bundles') }}</span>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                        <a href="{{ route('surveys.export_package', $survey) }}"
                            class="p-3 bg-gray-50/60 hover:bg-gray-100/80 border border-gray-200/80 rounded-xl flex flex-col justify-between transition-all group shadow-2xs">
                            <div class="flex items-center justify-between mb-1.5">
                                <i
                                    class="fa-solid fa-file-zipper text-base text-[#2271b1] group-hover:scale-110 transition-transform"></i>
                                <span class="text-[9px] font-bold uppercase text-gray-400">.ZIP</span>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-900">{{ __('Complete Bundle') }}</p>
                                <p class="text-[10px] text-gray-400 font-medium">{{ __('Schema + data') }}</p>
                            </div>
                        </a>

                        <a href="{{ route('surveys.export_spss_sav', $survey) }}"
                            class="p-3 bg-gray-50/60 hover:bg-gray-100/80 border border-gray-200/80 rounded-xl flex flex-col justify-between transition-all group shadow-2xs">
                            <div class="flex items-center justify-between mb-1.5">
                                <i
                                    class="fa-solid fa-table text-base text-emerald-600 group-hover:scale-110 transition-transform"></i>
                                <span class="text-[9px] font-bold uppercase text-gray-400">.SAV</span>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-900">{{ __('SPSS Dataset') }}</p>
                                <p class="text-[10px] text-gray-400 font-medium">{{ __('Statistical file') }}</p>
                            </div>
                        </a>

                        <a href="{{ route('surveys.export_pdf_summary', $survey) }}"
                            class="p-3 bg-gray-50/60 hover:bg-gray-100/80 border border-gray-200/80 rounded-xl flex flex-col justify-between transition-all group shadow-2xs">
                            <div class="flex items-center justify-between mb-1.5">
                                <i
                                    class="fa-solid fa-file-pdf text-base text-rose-600 group-hover:scale-110 transition-transform"></i>
                                <span class="text-[9px] font-bold uppercase text-gray-400">.PDF</span>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-900">{{ __('PDF Summary') }}</p>
                                <p class="text-[10px] text-gray-400 font-medium">{{ __('Executive report') }}</p>
                            </div>
                        </a>

                        <a href="{{ route('surveys.import', ['append_to' => $survey->id]) }}"
                            class="p-3 bg-gray-50/60 hover:bg-gray-100/80 border border-gray-200/80 rounded-xl flex flex-col justify-between transition-all group shadow-2xs">
                            <div class="flex items-center justify-between mb-1.5">
                                <i
                                    class="fa-solid fa-file-import text-base text-indigo-600 group-hover:scale-110 transition-transform"></i>
                                <span class="text-[9px] font-bold uppercase text-gray-400">{{ __('Append') }}</span>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-900">{{ __('Import Data') }}</p>
                                <p class="text-[10px] text-gray-400 font-medium">{{ __('Excel / CSV upload') }}</p>
                            </div>
                        </a>
                    </div>
                </div>
            </section>

            <!-- ========================================== -->
            <!-- 6. DANGER ZONE -->
            <!-- ========================================== -->
            <section class="p-6 sm:p-8 space-y-4 bg-rose-50/20 rounded-b-2xl">
                <div>
                    <h2 class="text-sm font-black text-rose-700 uppercase tracking-widest flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        {{ __('Danger Zone') }}
                    </h2>
                    <p class="text-[11px] text-rose-500/80 font-medium mt-0.5">
                        {{ __('Irreversible actions regarding survey state and collected data.') }}
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-1">
                    @if($survey->status->value !== 'archived')
                        <div
                            class="flex-1 p-3.5 bg-white border border-amber-200/80 rounded-xl flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold text-amber-950">{{ __('Archive Project') }}</p>
                                <p class="text-[10px] text-amber-700 font-medium">
                                    {{ __('Stop new submissions while retaining reports.') }}</p>
                            </div>
                            <form id="archive-form-{{ $survey->id }}" action="{{ route('surveys.archive', $survey) }}"
                                method="POST" class="hidden">
                                @csrf
                            </form>
                            <button type="button" onclick="
                                        Swal.fire({
                                            title: '{{ __('Archive Project?') }}',
                                            html: '<p class=\'text-xs text-gray-600\'>{{ __('You are about to archive') }} <b>{{ addslashes($survey->title) }}</b>. {{ __('It will no longer accept new responses.') }}</p>',
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonText: '{{ __('Yes, Archive') }}',
                                            cancelButtonText: '{{ __('Cancel') }}',
                                            confirmButtonColor: '#d97706',
                                            cancelButtonColor: '#64748b',
                                            reverseButtons: true,
                                            customClass: {
                                                popup: 'rounded-2xl',
                                                confirmButton: 'rounded-xl font-bold px-4 py-2 text-xs',
                                                cancelButton: 'rounded-xl font-bold px-4 py-2 text-xs'
                                            }
                                        }).then((res) => {
                                            if (res.isConfirmed) {
                                                document.getElementById('archive-form-{{ $survey->id }}').submit();
                                            }
                                        });
                                    "
                                class="px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-bold text-[10px] uppercase tracking-wider transition-all shadow-2xs flex-shrink-0">
                                {{ __('Archive') }}
                            </button>
                        </div>
                    @endif

                    <div
                        class="flex-1 p-3.5 bg-white border border-rose-200/80 rounded-xl flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold text-rose-950">{{ __('Delete Project') }}</p>
                            <p class="text-[10px] text-rose-700 font-medium">
                                {{ __('Permanently delete this survey and all responses.') }}</p>
                        </div>
                        <form id="delete-form-{{ $survey->id }}" action="{{ route('surveys.destroy', $survey) }}"
                            method="POST" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                        <button type="button" onclick="
                                Swal.fire({
                                    title: '{{ __('Permanently Delete Survey?') }}',
                                    html: '<p class=\'text-xs text-gray-600\'>{{ __('You are about to permanently delete') }} <b>{{ addslashes($survey->title) }}</b>.<br><br><span class=\'text-rose-600 font-bold uppercase text-[10px] tracking-wider\'>{{ __('This action is completely irreversible!') }}</span></p>',
                                    icon: 'error',
                                    showCancelButton: true,
                                    confirmButtonText: '{{ __('Yes, Delete') }}',
                                    cancelButtonText: '{{ __('Cancel') }}',
                                    confirmButtonColor: '#e11d48',
                                    cancelButtonColor: '#64748b',
                                    reverseButtons: true,
                                    customClass: {
                                        popup: 'rounded-2xl',
                                        confirmButton: 'rounded-xl font-bold px-4 py-2 text-xs',
                                        cancelButton: 'rounded-xl font-bold px-4 py-2 text-xs'
                                    }
                                }).then((res) => {
                                    if (res.isConfirmed) {
                                        document.getElementById('delete-form-{{ $survey->id }}').submit();
                                    }
                                });
                            "
                            class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-bold text-[10px] uppercase tracking-wider transition-all shadow-2xs flex-shrink-0">
                            {{ __('Delete') }}
                        </button>
                    </div>
                </div>
            </section>
        </div>

        <!-- ========================================== -->
        <!-- MODAL: ADD COLLABORATOR -->
        <!-- ========================================== -->
        <div x-show="addModalOpen" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-2xs">
            <div @click.outside="addModalOpen = false"
                class="bg-white rounded-2xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-gray-100 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h4 class="text-sm font-black text-gray-900 uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-user-plus text-indigo-600"></i>
                            {{ __('Add Collaborator') }}
                        </h4>
                        <p class="text-[11px] text-gray-400 font-medium mt-0.5">
                            {{ __('Enter an email to invite a registered user or external collaborator.') }}</p>
                    </div>
                    <button type="button" @click="addModalOpen = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-xmark text-base"></i>
                    </button>
                </div>

                <form action="{{ route('surveys.collaborators.add', $survey) }}" method="POST" class="space-y-5" x-data="{
                            selectAll(val) {
                                $el.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = val);
                            }
                        }">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            {{ __('Collaborator Email Address') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="email" name="email" required placeholder="collaborator@example.com"
                            class="w-full bg-gray-50/60 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-gray-900 focus:bg-white focus:border-[#2271b1] focus:ring-2 focus:ring-[#2271b1]/10 transition-all">
                        <p class="text-[10px] text-gray-400 font-medium mt-1">
                            {{ __('If they do not have an account, an email invitation will be sent to them.') }}
                        </p>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                {{ __('Permissions') }}
                            </label>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="selectAll(true)"
                                    class="text-[10px] font-bold text-indigo-600 hover:underline">
                                    {{ __('Select All') }}
                                </button>
                                <span class="text-gray-300">•</span>
                                <button type="button" @click="selectAll(false)"
                                    class="text-[10px] font-bold text-gray-500 hover:underline">
                                    {{ __('Deselect All') }}
                                </button>
                            </div>
                        </div>

                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-3 bg-gray-50/60 border border-gray-100 rounded-xl max-h-52 overflow-y-auto">
                            @foreach($permissionLabels as $k => $label)
                                <label
                                    class="flex items-center gap-2.5 cursor-pointer p-1.5 rounded-lg hover:bg-white transition-colors">
                                    <input type="checkbox" name="{{ $k }}" value="1" {{ in_array($k, ['view_form', 'view_submissions']) ? 'checked' : '' }}
                                        class="w-3.5 h-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-xs font-medium text-gray-700">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-gray-100">
                        <button type="button" @click="addModalOpen = false"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold text-xs uppercase tracking-wider transition-all">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs uppercase tracking-wider shadow-2xs transition-all flex items-center gap-1.5">
                            <i class="fa-solid fa-paper-plane text-xs"></i>
                            <span>{{ __('Send Invitation') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODAL: EDIT COLLABORATOR PERMISSIONS -->
        <!-- ========================================== -->
        <div x-show="editModalOpen" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-2xs">
            <div @click.outside="editModalOpen = false"
                class="bg-white rounded-2xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-gray-100 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h4 class="text-sm font-black text-gray-900 uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-pen-to-square text-indigo-600"></i>
                            {{ __('Edit Permissions') }}
                        </h4>
                        <p class="text-[11px] text-gray-400 font-medium mt-0.5"
                            x-text="activeCollaborator ? (activeCollaborator.user ? activeCollaborator.user.name : activeCollaborator.invite_email) : ''">
                        </p>
                    </div>
                    <button type="button" @click="editModalOpen = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-xmark text-base"></i>
                    </button>
                </div>

                <form
                    :action="activeCollaborator ? `{{ url('surveys/' . $survey->id . '/collaborators') }}/${activeCollaborator.id}` : '#'"
                    method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-3 bg-gray-50/60 border border-gray-100 rounded-xl max-h-52 overflow-y-auto">
                        @foreach($permissionLabels as $k => $label)
                            <label
                                class="flex items-center gap-2.5 cursor-pointer p-1.5 rounded-lg hover:bg-white transition-colors">
                                <input type="checkbox" name="{{ $k }}" value="1" x-model="activePerms.{{ $k }}"
                                    class="w-3.5 h-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-xs font-medium text-gray-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-gray-100">
                        <button type="button" @click="editModalOpen = false"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold text-xs uppercase tracking-wider transition-all">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs uppercase tracking-wider shadow-2xs transition-all">
                            {{ __('Update Permissions') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODAL: TRANSFER OWNERSHIP -->
        <!-- ========================================== -->
        <div x-show="transferModalOpen" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-2xs">
            <div @click.outside="transferModalOpen = false"
                class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-gray-100 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h4 class="text-sm font-black text-amber-800 uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-arrow-right-arrow-left text-amber-600"></i>
                            {{ __('Transfer Ownership') }}
                        </h4>
                        <p class="text-[11px] text-gray-400 font-medium mt-0.5">
                            {{ __('Hand over primary ownership of this project.') }}</p>
                    </div>
                    <button type="button" @click="transferModalOpen = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-xmark text-base"></i>
                    </button>
                </div>

                <form action="{{ route('surveys.transfer_ownership', $survey) }}" method="POST" class="space-y-4"
                    onsubmit="return confirm('{{ __('Are you sure you want to transfer ownership of this survey? You will retain collaborator access.') }}')">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            {{ __('New Owner Email') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="email" name="new_owner_email" required placeholder="newowner@example.com"
                            class="w-full bg-gray-50/60 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-gray-900 focus:bg-white focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10 transition-all">
                        <p class="text-[10px] text-gray-400 font-medium mt-1">
                            {{ __('Enter a registered user email or invite a new user. If they do not have an account, an invitation will be sent and ownership transferred when they sign up.') }}
                        </p>
                    </div>

                    <div class="p-3.5 bg-amber-50/80 border border-amber-200/80 rounded-xl">
                        <p class="text-[11px] text-amber-900 font-medium leading-relaxed">
                            <i class="fa-solid fa-circle-info mr-1 text-amber-600"></i>
                            {{ __('The new owner will have full control. You will continue to have full collaborator access.') }}
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-gray-100">
                        <button type="button" @click="transferModalOpen = false"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold text-xs uppercase tracking-wider transition-all">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-bold text-xs uppercase tracking-wider shadow-2xs transition-all">
                            {{ __('Transfer Ownership') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection