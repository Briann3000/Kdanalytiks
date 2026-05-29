@extends('surveys.hub')

@section('survey-content')
    <div class="max-w-4xl" x-data="versionPreviewManager()">
        <!-- Version List Card -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-8 border-b border-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Design Version History') }}</h3>
                    <p class="text-xs text-gray-400 font-medium mt-1">{{ __('Track design revisions, preview historical states, and restore previous versions of your survey.') }}</p>
                </div>
            </div>

            @if(session('success'))
                <div class="m-8 p-4 bg-green-50 border border-green-100 rounded-2xl">
                    <p class="text-xs text-green-700 font-bold uppercase tracking-widest">
                        <i class="fa-solid fa-circle-check mr-2"></i> {{ session('success') }}
                    </p>
                </div>
            @endif

            @if($versions->isEmpty())
                <div class="p-16 flex flex-col items-center justify-center text-center">
                    <div class="w-16 h-16 bg-gray-50 text-gray-400 rounded-2xl flex items-center justify-center mb-4">
                        <i class="fa-solid fa-clock-rotate-left text-2xl"></i>
                    </div>
                    <h4 class="text-sm font-bold text-gray-800 uppercase tracking-wider">{{ __('No versions recorded yet') }}</h4>
                    <p class="text-xs text-gray-400 font-medium mt-1 max-w-sm">{{ __('Version history is created automatically whenever you modify your survey design (questions, title, or description).') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-50 bg-gray-50/50">
                                <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('Ver') }}</th>
                                <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('Change Description') }}</th>
                                <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('Edited By') }}</th>
                                <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('Date / Time') }}</th>
                                <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($versions as $version)
                                <tr class="hover:bg-gray-50/50 transition-colors group">
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-50 text-indigo-600 rounded-lg text-xs font-bold">
                                            {{ $version->version_number }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <p class="text-xs font-bold text-gray-900 leading-tight">
                                            {{ $version->change_summary ?: __('No change description available') }}
                                        </p>
                                        <p class="text-[10px] text-gray-400 font-medium mt-0.5 max-w-xs truncate">
                                            {{ $version->title }}
                                        </p>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-6 h-6 rounded-lg bg-gray-100 flex items-center justify-center text-[9px] font-bold text-gray-500 uppercase">
                                                {{ substr($version->modifier?->name ?? 'System', 0, 2) }}
                                            </div>
                                            <span class="text-xs font-medium text-gray-600">
                                                {{ $version->modifier?->name ?? __('System') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-xs text-gray-500 font-medium">
                                        {{ $version->created_at->format('M d, Y') }}
                                        <span class="block text-[10px] text-gray-400 font-medium mt-0.5">
                                            {{ $version->created_at->format('g:i A') }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="flex items-center justify-end space-x-2 opacity-80 group-hover:opacity-100 transition-opacity">
                                            <button type="button" @click="fetchAndPreview({{ $version->version_number }})"
                                                class="px-3 py-1.5 bg-white border border-gray-200 text-gray-600 hover:text-indigo-600 hover:border-indigo-100 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-colors">
                                                <i class="fa-solid fa-eye mr-1"></i> {{ __('Preview') }}
                                            </button>
                                            
                                            <form action="{{ route('surveys.versions.restore', [$survey, $version]) }}" method="POST"
                                                x-on:submit.prevent="confirmRestore($event, {{ $version->version_number }})">
                                                @csrf
                                                <button type="submit"
                                                    class="px-3 py-1.5 bg-gray-900 text-white hover:bg-indigo-600 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-colors">
                                                    <i class="fa-solid fa-rotate-left mr-1"></i> {{ __('Restore') }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($versions->hasPages())
                    <div class="p-6 border-t border-gray-50 bg-gray-50/20">
                        {{ $versions->links() }}
                    </div>
                @endif
            @endif
        </div>

        <!-- Preview Slide-over / Modal (AlpineJS) -->
        <div x-show="open" 
             class="fixed inset-0 z-[15000] overflow-hidden" 
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="closePreview()"></div>

            <div class="absolute inset-y-0 right-0 max-w-full flex pl-10">
                <div x-show="open"
                     x-transition:enter="transform transition ease-in-out duration-300 sm:duration-400"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transform transition ease-in-out duration-300 sm:duration-400"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full"
                     class="w-screen max-w-md bg-white shadow-2xl flex flex-col">
                    
                    <!-- Header -->
                    <div class="px-6 py-5 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-50 text-indigo-600 rounded-lg text-xs font-bold" x-text="details.version_number"></span>
                            <h2 class="text-sm font-black text-gray-900 uppercase tracking-wider">{{ __('Version Preview') }}</h2>
                        </div>
                        <button type="button" @click="closePreview()" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <!-- Scrollable Content -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-6">
                        <!-- Summary Metadata -->
                        <div class="space-y-4 bg-gray-50/50 rounded-2xl border border-gray-50 p-4">
                            <div>
                                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">{{ __('Title') }}</span>
                                <h4 class="text-xs font-bold text-gray-900 mt-0.5" x-text="details.title"></h4>
                            </div>
                            <div x-show="details.description">
                                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">{{ __('Description') }}</span>
                                <p class="text-xs font-medium text-gray-600 mt-0.5" x-text="details.description"></p>
                            </div>
                            <div class="grid grid-cols-2 gap-4 pt-2 border-t border-gray-100">
                                <div>
                                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">{{ __('Saved On') }}</span>
                                    <span class="block text-xs font-bold text-gray-700 mt-0.5" x-text="details.created_at"></span>
                                </div>
                                <div>
                                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">{{ __('Saved By') }}</span>
                                    <span class="block text-xs font-bold text-gray-700 mt-0.5" x-text="details.changed_by"></span>
                                </div>
                            </div>
                            <div>
                                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">{{ __('Change Summary') }}</span>
                                <span class="block text-xs font-bold text-indigo-600 mt-0.5" x-text="details.change_summary"></span>
                            </div>
                        </div>

                        <!-- Question List Preview -->
                        <div class="space-y-3">
                            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('Question List') }} (<span x-text="questions.length"></span>)</h4>
                            
                            <template x-if="questions.length === 0">
                                <p class="text-xs font-medium text-gray-400 italic">{{ __('No questions defined in this version.') }}</p>
                            </template>

                            <div class="space-y-2">
                                <template x-for="(q, idx) in questions" :key="idx">
                                    <div class="p-3 border border-gray-100 rounded-xl hover:border-gray-200 transition-colors">
                                        <div class="flex items-start justify-between gap-2">
                                            <span class="text-xs font-bold text-gray-900" x-text="q.label || q.text || 'Untitled Question'"></span>
                                            <span class="px-2 py-0.5 bg-gray-50 border border-gray-100 text-[9px] font-bold text-gray-500 uppercase tracking-wider rounded" x-text="q.type || 'text'"></span>
                                        </div>
                                        <template x-if="q.values && q.values.length > 0">
                                            <div class="mt-2 flex flex-wrap gap-1">
                                                <template x-for="(v, vidx) in q.values" :key="vidx">
                                                    <span class="px-2 py-0.5 bg-gray-50/50 text-[9px] font-medium text-gray-400 rounded-md" x-text="v.label || v.value"></span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Restore Button -->
                    <div class="p-6 border-t border-gray-100 bg-gray-50 flex items-center justify-end">
                        <form :action="'{{ route('surveys.versions.restore', [$survey, ':version_id']) }}'.replace(':version_id', details.id)" method="POST"
                              x-on:submit.prevent="confirmRestore($event, details.version_number)">
                            @csrf
                            <button type="submit" class="px-6 py-3 bg-indigo-600 text-white hover:bg-indigo-700 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-indigo-100 transition-all flex items-center gap-2">
                                <i class="fa-solid fa-rotate-left"></i>
                                {{ __('Restore This Version') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function versionPreviewManager() {
                return {
                    open: false,
                    details: {
                        id: null,
                        version_number: '',
                        title: '',
                        description: '',
                        change_summary: '',
                        created_at: '',
                        changed_by: ''
                    },
                    questions: [],

                    fetchAndPreview(versionNumber) {
                        const url = '{{ route('surveys.versions.show', [$survey, ':version']) }}'.replace(':version', versionNumber);
                        
                        Swal.fire({
                            title: 'Loading version details...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            Swal.close();
                            if (data.success) {
                                this.details = data.version;
                                // Save the DB version id or number for the form action replacement
                                this.details.id = versionNumber;
                                
                                try {
                                    const schema = JSON.parse(data.version.json_schema);
                                    this.questions = Array.isArray(schema) ? schema : [];
                                } catch (e) {
                                    this.questions = [];
                                }
                                
                                this.open = true;
                            } else {
                                Swal.fire('Error', 'Could not load version details', 'error');
                            }
                        })
                        .catch(err => {
                            Swal.close();
                            Swal.fire('Error', 'An error occurred while fetching version data', 'error');
                        });
                    },

                    closePreview() {
                        this.open = false;
                    },

                    confirmRestore(event, versionNumber) {
                        Swal.fire({
                            title: 'Restore to Version ' + versionNumber + '?',
                            html: '<p class="text-sm">This will replace your current survey configuration (questions, title, and description) with the state from Version ' + versionNumber + '.<br><br><b class="text-indigo-600">Note: Your current state will be backed up as a new version snapshot.</b></p>',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, Restore It',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#4f46e5',
                            cancelButtonColor: '#4b5563',
                            reverseButtons: true,
                            customClass: {
                                popup: 'rounded-3xl',
                                confirmButton: 'rounded-xl font-bold px-6 py-3',
                                cancelButton: 'rounded-xl font-bold px-6 py-3'
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                event.target.submit();
                            }
                        });
                    }
                }
            }
        </script>
    @endpush
@endsection
