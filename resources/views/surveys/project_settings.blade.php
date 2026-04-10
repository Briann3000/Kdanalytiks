@extends('surveys.project_hub')

@section('project-content')
    <div class="max-w-4xl">
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-8 border-b border-gray-50">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">General Settings</h3>
                <p class="text-xs text-gray-400 font-medium mt-1">Manage survey metadata and lifecycle.</p>
            </div>

            <div class="p-8 space-y-12">
                <!-- Project Identification -->
                <form action="{{ route('projects.settings.update', $survey) }}" method="POST">
                    @csrf
                    <section class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div>
                            <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-1">Survey Details</h4>
                            <p class="text-[11px] text-gray-400 font-bold leading-relaxed">Basic information and data
                                protection settings.</p>
                        </div>
                        <div class="md:col-span-2 space-y-6">
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Survey
                                    Title</label>
                                <input type="text" name="title" value="{{ $survey->title }}"
                                    class="w-full bg-gray-50 border-gray-100 rounded-xl px-4 py-3 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Description</label>
                                <textarea name="description" rows="3"
                                    class="w-full bg-gray-50 border-gray-100 rounded-xl px-4 py-3 text-sm font-medium text-gray-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">{{ $survey->description }}</textarea>
                            </div>

                            <div class="p-6 rounded-2xl border border-gray-100 bg-gray-50/50">
                                <label class="flex items-center space-x-3 cursor-pointer group">
                                    <div class="relative">
                                        <input type="checkbox" name="is_anonymous" value="1" {{ $survey->is_anonymous ? 'checked' : '' }} class="sr-only peer">
                                        <div
                                            class="w-10 h-6 bg-gray-200 rounded-full peer peer-checked:bg-indigo-600 transition-all peer-focus:ring-2 peer-focus:ring-indigo-500/20">
                                        </div>
                                        <div
                                            class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-all peer-checked:translate-x-4">
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-xs font-black text-gray-900 uppercase tracking-widest">Allow
                                            Anonymous Submissions</span>
                                        <p class="text-[10px] text-gray-400 font-bold mt-1 uppercase tracking-tighter">
                                            Respondents can submit without logging in or providing identifying info.</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </section>

                    <div class="flex justify-end mt-8">
                        <button type="submit"
                            class="px-8 py-3 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all">
                            Update Survey Details
                        </button>
                    </div>
                </form>

                <hr class="border-gray-50">

                <!-- Sharing & Access -->
                <section class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-1">Sharing & Public Access
                        </h4>
                        <p class="text-[11px] text-gray-400 font-bold leading-relaxed">Control how the world sees your
                            project.</p>
                    </div>
                    <div class="md:col-span-2 space-y-6">
                        <form action="{{ route('projects.settings.update', $survey) }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Public
                                    Access Level</label>
                                <select name="public_access"
                                    class="w-full bg-gray-50 border-gray-100 rounded-xl px-4 py-3 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all appearance-none">
                                    <option value="none" {{ $survey->public_access === 'none' ? 'selected' : '' }}>Private
                                        (Internal only)</option>
                                    <option value="submit" {{ $survey->public_access === 'submit' ? 'selected' : '' }}>Public
                                        Submission (Anyone can fill)</option>
                                    <option value="view" {{ $survey->public_access === 'view' ? 'selected' : '' }}>Publicly
                                        Viewable (Anyone can see results)</option>
                                    <option value="edit" {{ $survey->public_access === 'edit' ? 'selected' : '' }}>Publicly
                                        Editable (WARNING: Anyone can edit schema)</option>
                                </select>
                            </div>
                            <button type="submit"
                                class="px-6 py-2 bg-gray-900 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-gray-800 transition-all">
                                Save Access Mode
                            </button>
                        </form>

                        <div class="p-6 rounded-2xl border border-indigo-100 bg-indigo-50/20">
                            <label
                                class="block text-[10px] font-black text-indigo-600 uppercase tracking-widest mb-3">Shareable
                                Survey Link</label>
                            @php
                                $shareUrl = route('surveys.show', ['survey' => $survey, 'token' => $survey->share_token]);
                            @endphp
                            <div class="flex items-center gap-2 mb-4">
                                <input type="text" readonly value="{{ $shareUrl }}"
                                    class="flex-1 bg-white border-gray-100 rounded-xl px-4 py-3 text-[11px] font-bold text-gray-600">
                                <button onclick="navigator.clipboard.writeText('{{ $shareUrl }}'); alert('Link copied!')"
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
                                <a href="https://wa.me/?text={{ urlencode('Please take this survey: ' . $shareUrl) }}"
                                    target="_blank"
                                    class="w-10 h-10 rounded-full bg-[#25D366] text-white flex items-center justify-center hover:scale-110 transition-transform">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </a>
                                <a href="mailto:?subject=Survey Invitation&body=Please take this survey: {{ urlencode($shareUrl) }}"
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
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-1">Collaborators</h4>
                        <p class="text-[11px] text-gray-400 font-bold leading-relaxed">Manage specific user access and
                            roles.</p>
                        <button @click="addPanelOpen = !addPanelOpen"
                            class="mt-4 px-4 py-2 border border-indigo-200 text-indigo-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-50 transition-all flex items-center gap-2">
                            <i class="fa-solid" :class="addPanelOpen ? 'fa-minus' : 'fa-plus'"></i>
                            <span x-text="addPanelOpen ? 'Close Add Form' : 'Add Collaborator'"></span>
                        </button>
                    </div>
                    <div class="md:col-span-2 space-y-6">
                        <!-- Add Collaborator Panel -->
                        <div x-show="addPanelOpen" x-transition
                            class="p-6 bg-indigo-50/30 border border-indigo-100 rounded-2xl animate-in slide-in-from-top-2">
                            <form action="{{ route('projects.collaborators.add', $survey) }}" method="POST"
                                class="space-y-6">
                                @csrf
                                <div>
                                    <label
                                        class="block text-[10px] font-black text-indigo-600 uppercase tracking-widest mb-2">User
                                        Email</label>
                                    <input type="email" name="email" required placeholder="Collaborator Email..."
                                        class="w-full bg-white border-gray-100 rounded-xl px-4 py-3 text-sm font-medium focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                                </div>

                                <div>
                                    <label
                                        class="block text-[10px] font-black text-indigo-600 uppercase tracking-widest mb-3">Permissions</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
                                        @php
                                            $perms = [
                                                ['key' => 'view_form', 'label' => 'View form'],
                                                ['key' => 'edit_form', 'label' => 'Edit form'],
                                                ['key' => 'view_submissions', 'label' => 'View submissions'],
                                                ['key' => 'add_submissions', 'label' => 'Add submissions'],
                                                ['key' => 'edit_submissions', 'label' => 'Edit submissions'],
                                                ['key' => 'validate_submissions', 'label' => 'Validate submissions'],
                                                ['key' => 'delete_submissions', 'label' => 'Delete submissions'],
                                                ['key' => 'manage_project', 'label' => 'Manage project'],
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
                                        Save Collaborator
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
                                                class="ml-2 text-[9px] text-indigo-500 uppercase tracking-widest border border-indigo-100 px-2 py-0.5 rounded-full bg-indigo-50/50">Owner</span>
                                        </p>
                                        <p class="text-[10px] text-gray-400 font-bold lowercase tracking-wider">
                                            {{ $survey->creator->email }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="px-3 py-1 bg-indigo-50 text-indigo-600 text-[9px] font-black uppercase tracking-widest rounded-lg">All
                                        Permissions</span>
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
                                                {{ $collaborator->user->email }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        @php
                                            $colPermsCount = is_array($collaborator->permissions) ? count(array_filter($collaborator->permissions)) : 0;
                                        @endphp
                                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                            {{ $colPermsCount }} Granular {{ Str::plural('Permission', $colPermsCount) }}
                                        </span>

                                        <form action="{{ route('projects.collaborators.remove', [$survey, $collaborator]) }}"
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

                <!-- Danger Zone -->
                <section class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <h4 class="text-xs font-black text-red-600 uppercase tracking-widest mb-1">Danger Zone</h4>
                        <p class="text-[10px] text-gray-400 font-bold leading-relaxed">Irreversible actions that affect
                            survey data and availability.</p>
                    </div>
                    <div class="md:col-span-2 space-y-4">
                        @if($survey->status->value !== 'archived')
                            <div class="p-6 rounded-2xl border border-amber-100 bg-amber-50/50 flex items-center justify-between"
                                x-data="{ confirming: false }">
                                <div>
                                    <p class="text-xs font-bold text-amber-900 uppercase tracking-wider mb-1">Archive Project
                                    </p>
                                    <p class="text-xs text-amber-600 font-medium lowercase">Stop collection but keep data
                                        available for analytical reports.</p>
                                </div>
                                <form id="archive-form-{{ $survey->id }}" action="{{ route('projects.archive', $survey) }}"
                                    method="POST" class="hidden">
                                    @csrf
                                </form>
                                <div class="flex items-center gap-3">
                                    <button type="button" x-show="!confirming" @click="confirming = true"
                                        class="px-6 py-2 bg-amber-600 text-white rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-amber-700 transition-all shadow-sm">
                                        Archive
                                    </button>
                                    <div x-show="confirming" class="flex items-center gap-2" style="display:none">
                                        <span class="text-[10px] font-black text-amber-600 uppercase tracking-widest px-2">Are
                                            you sure?</span>
                                        <button type="button"
                                            @click="document.getElementById('archive-form-{{ $survey->id }}').submit()"
                                            class="px-6 py-2 bg-amber-600 text-white rounded-xl font-bold text-xs uppercase hover:bg-amber-700 transition-all shadow-sm">YES</button>
                                        <button type="button" @click="confirming = false"
                                            class="px-6 py-2 bg-gray-100 text-gray-400 rounded-xl font-bold text-xs uppercase hover:bg-gray-200 transition-all shadow-sm">NO</button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="p-6 rounded-2xl border border-red-100 bg-red-50/30 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-red-900 uppercase tracking-wider mb-1">Delete Project</p>
                                <p class="text-xs text-red-600 font-medium leading-tight">Permanently remove form, metadata,
                                    and ALL submission data.</p>
                            </div>
                            <form id="delete-form-{{ $survey->id }}" action="{{ route('surveys.destroy', $survey) }}"
                                method="POST" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                            <div x-data="{ confirming: false }" class="flex items-center gap-3">
                                <button type="button" x-show="!confirming" @click="confirming = true"
                                    class="px-6 py-2 bg-red-600 text-white rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-red-700 transition-all shadow-sm border border-red-100">
                                    Delete Project
                                </button>
                                <div x-show="confirming"
                                    class="flex items-center gap-2 animate-in fade-in slide-in-from-right-2 duration-200"
                                    style="display:none">
                                    <span
                                        class="text-[10px] font-black text-red-600 uppercase tracking-widest bg-red-50 px-3 py-1.5 border border-red-100 rounded-lg">Confirm?</span>
                                    <button type="button"
                                        @click="console.log('ProjectSettings confirming YES'); document.getElementById('delete-form-{{ $survey->id }}').submit()"
                                        class="px-6 py-2 bg-red-600 text-white rounded-xl font-bold text-xs uppercase hover:bg-red-700 transition-all shadow-sm">YES</button>
                                    <button type="button" @click="confirming = false"
                                        class="px-6 py-2 bg-gray-100 text-gray-500 rounded-xl font-bold text-xs uppercase hover:bg-gray-200 transition-all">NO</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="p-8 bg-gray-50 border-t border-gray-100 flex justify-end">
                <button
                    class="px-8 py-3 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all">
                    Save Settings
                </button>
            </div>
        </div>
    </div>
@endsection