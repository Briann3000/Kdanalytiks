@extends('surveys.project_hub')

@section('project-content')
<div class="max-w-4xl">
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-8 border-b border-gray-50">
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">General Settings</h3>
            <p class="text-xs text-gray-400 font-medium mt-1">Manage project metadata and lifecycle.</p>
        </div>
        
        <div class="p-8 space-y-12">
            <!-- Project Identification -->
            <section class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-1">Identification</h4>
                    <p class="text-[11px] text-gray-400 font-medium leading-relaxed">Basic information used to identify this project across the system.</p>
                </div>
                <div class="md:col-span-2 space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Project Title</label>
                        <input type="text" value="{{ $survey->title }}" class="w-full bg-gray-50 border-gray-100 rounded-xl px-4 py-3 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Description</label>
                        <textarea rows="4" class="w-full bg-gray-50 border-gray-100 rounded-xl px-4 py-3 text-sm font-medium text-gray-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">{{ $survey->description }}</textarea>
                    </div>
                </div>
            </section>

            <hr class="border-gray-50">

            <!-- Danger Zone -->
            <section class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h4 class="text-xs font-black text-red-600 uppercase tracking-widest mb-1">Danger Zone</h4>
                    <p class="text-[10px] text-gray-400 font-bold leading-relaxed">Irreversible actions that affect project data and availability.</p>
                </div>
                <div class="md:col-span-2 space-y-4">
                    @if($survey->status->value !== 'archived')
                        <div class="p-6 rounded-2xl border border-amber-100 bg-amber-50/50 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-amber-900 uppercase tracking-wider mb-1">Archive Project</p>
                                <p class="text-xs text-amber-600 font-medium">Stop collections but keep data available for reports.</p>
                            </div>
                            <form action="{{ route('projects.archive', $survey) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-6 py-2 bg-amber-600 text-white rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-amber-700 transition-all">
                                    Archive
                                </button>
                            </form>
                        </div>
                    @endif

                    <div class="p-6 rounded-2xl border border-red-100 bg-red-50/30 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-red-900 uppercase tracking-wider mb-1">Delete Project</p>
                            <p class="text-xs text-red-600 font-medium leading-tight">Permanently remove form, metadata, and ALL submission data.</p>
                        </div>
                        <form id="delete-form-{{ $survey->id }}" action="{{ route('surveys.destroy', $survey) }}" method="POST" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                        <div x-data="{ confirming: false }" class="flex items-center gap-3">
                            <button type="button" 
                                    x-show="!confirming"
                                    @click="confirming = true"
                                    class="px-6 py-2 bg-red-600 text-white rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-red-700 transition-all shadow-sm border border-red-100">
                                Delete Project
                            </button>
                            <div x-show="confirming" class="flex items-center gap-2 animate-in fade-in slide-in-from-right-2 duration-200" style="display:none">
                                <span class="text-[10px] font-black text-red-600 uppercase tracking-widest bg-red-50 px-3 py-1.5 border border-red-100 rounded-lg">Confirm?</span>
                                <button type="button" 
                                        @click="console.log('ProjectSettings confirming YES'); document.getElementById('delete-form-{{ $survey->id }}').submit()"
                                        class="px-6 py-2 bg-red-600 text-white rounded-xl font-bold text-xs uppercase hover:bg-red-700 transition-all shadow-sm">YES</button>
                                <button type="button" 
                                        @click="confirming = false"
                                        class="px-6 py-2 bg-gray-100 text-gray-500 rounded-xl font-bold text-xs uppercase hover:bg-gray-200 transition-all">NO</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="p-8 bg-gray-50 border-t border-gray-100 flex justify-end">
            <button class="px-8 py-3 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all">
                Save Settings
            </button>
        </div>
    </div>
</div>
@endsection
