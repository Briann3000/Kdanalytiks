@extends('layouts.app')

@section('title', 'Survey Inventory - Admin')

@section('content')
<div x-data="{ 
    selected: [], 
    allSelected: false,
    toggleAll() {
        if (this.allSelected) {
            const checkboxes = document.querySelectorAll('.survey-checkbox');
            this.selected = Array.from(checkboxes).map(el => el.value);
        } else {
            this.selected = [];
        }
    },
    updateSelectAll() {
        const checkboxes = document.querySelectorAll('.survey-checkbox');
        this.allSelected = checkboxes.length > 0 && this.selected.length === checkboxes.length;
    },
    async bulkDelete() {
        if (this.selected.length === 0) {
            Swal.fire('No Selection', 'Please select at least one survey to delete.', 'info');
            return;
        }
        
        const result = await Swal.fire({
            title: 'Delete Selected Surveys?',
            html: `You are about to delete <b>${this.selected.length}</b> surveys from the platform inventory.<br><br><span class='text-red-500 font-bold uppercase text-[10px] tracking-widest'>This action is irreversible.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Delete All',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#1e293b',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-3xl',
                confirmButton: 'rounded-xl font-bold px-6 py-3',
                cancelButton: 'rounded-xl font-bold px-6 py-3'
            }
        });

        if (result.isConfirmed) {
            Swal.fire({
                title: 'Processing...',
                html: 'Mass deleting surveys from inventory...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const res = await fetch('{{ route('admin.surveys.bulk-destroy') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ survey_ids: this.selected })
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({
                        title: 'Deleted!',
                        text: data.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Bulk delete failed.', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'An error occurred during bulk deletion.', 'error');
            }
        }
    }
}" class="px-4 sm:px-8 lg:px-12 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-black text-gray-900 tracking-tight uppercase">Survey Inventory</h1>
        <p class="text-sm text-gray-500 font-medium">Manage and monitor all surveys across the platform.</p>
    </div>

    <!-- Bulk Action Bar -->
    <div x-show="selected.length > 0" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="mb-6 bg-red-600 p-4 rounded-2xl shadow-xl shadow-red-100 flex items-center justify-between" style="display: none">
        <div class="flex items-center text-white">
            <span class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center mr-3 text-sm font-black" x-text="selected.length"></span>
            <span class="text-xs font-black uppercase tracking-widest">Surveys Selected for Deletion</span>
        </div>
        <div class="flex items-center gap-3">
            <button @click="selected = []; allSelected = false" class="text-white/70 hover:text-white text-[10px] font-black uppercase tracking-widest transition-colors mr-2">Deselect All</button>
            <button @click="bulkDelete()" class="px-6 py-2 bg-white text-red-600 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-sm hover:bg-red-50 transition-all transform hover:scale-105 active:scale-95">
                <i class="fa-solid fa-trash-can mr-2"></i> Bulk Delete
            </button>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('admin.surveys.index') }}" 
           class="px-4 py-2 rounded-lg text-xs font-black uppercase tracking-widest transition-all {{ !request('status') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-white text-gray-400 hover:text-indigo-600 border border-gray-100' }}">
            All Surveys
        </a>
        <a href="{{ route('admin.surveys.index', ['status' => 'active']) }}" 
           class="px-4 py-2 rounded-lg text-xs font-black uppercase tracking-widest transition-all {{ request('status') === 'active' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-100' : 'bg-white text-gray-400 hover:text-emerald-600 border border-gray-100' }}">
            Active
        </a>
        <a href="{{ route('admin.surveys.index', ['status' => 'draft']) }}" 
           class="px-4 py-2 rounded-lg text-xs font-black uppercase tracking-widest transition-all {{ request('status') === 'draft' ? 'bg-gray-600 text-white shadow-lg shadow-gray-100' : 'bg-white text-gray-400 hover:text-gray-600 border border-gray-100' }}">
            Drafts
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm mb-6">
        <form action="{{ route('admin.surveys.index') }}" method="GET" class="flex flex-wrap gap-3 items-center">
            <div class="w-40">
                <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Status</label>
                <select name="status" class="w-full text-[10px] font-bold border-gray-100 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 py-1.5 shadow-sm">
                    <option value="">All Statuses</option>
                    @foreach(collect(\App\Enums\SurveyStatus::cases())->filter(fn($s) => $s->value !== 'pending_approval')->sortBy(fn($s) => strtoupper(str_replace('_', ' ', $s->value))) as $status)
                        <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>{{ strtoupper(str_replace('_', ' ', $status->value)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Category</label>
                <select name="category" class="w-full text-[10px] font-bold border-gray-100 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 py-1.5 shadow-sm">
                    <option value="">All Categories</option>
                    @foreach(collect(\App\Enums\SurveyCategory::cases())->sortBy('value') as $cat)
                        <option value="{{ $cat->value }}" {{ request('category') == $cat->value ? 'selected' : '' }}>{{ $cat->value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Source</label>
                <select name="source" class="w-full text-[10px] font-bold border-gray-100 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 py-1.5 shadow-sm">
                    <option value="">All Sources</option>
                    <option value="admin" {{ request('source') == 'admin' ? 'selected' : '' }}>ADMIN</option>
                    <option value="independent" {{ request('source') == 'independent' ? 'selected' : '' }}>INDEPENDENT</option>
                    <option value="organization" {{ request('source') == 'organization' ? 'selected' : '' }}>ORGANIZATION</option>
                </select>
            </div>
            <div class="flex-grow max-w-xs">
                <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Title or Owner..." class="w-full text-[10px] font-bold border-gray-100 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 py-1.5 shadow-sm">
            </div>
            <div class="flex items-end self-end">
                <button type="submit" class="inline-flex justify-center py-1.5 px-6 border border-transparent shadow-md text-[10px] font-black rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition-all uppercase tracking-wider">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Surveys Table -->
    <div class="bg-white shadow-xl shadow-gray-100/50 rounded-xl border border-gray-100 mb-8 overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="min-w-[1000px] w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left w-10">
                            <input type="checkbox" 
                                   @change="toggleAll()" 
                                   x-model="allSelected"
                                   class="h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500 cursor-pointer transition-all">
                        </th>
                        <th class="px-6 py-4 text-left text-[11px] font-bold text-gray-900 uppercase tracking-wider">Survey Detail</th>
                        <th class="px-6 py-4 text-left text-[11px] font-bold text-gray-900 uppercase tracking-wider">Owner</th>
                        <th class="px-6 py-4 text-left text-[11px] font-bold text-gray-900 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-4 text-left text-[11px] font-bold text-gray-900 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-[11px] font-bold text-gray-900 uppercase tracking-wider">Responses</th>
                        <th class="px-6 py-4 text-right text-[11px] font-bold text-gray-900 uppercase tracking-wider pr-20">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($surveys as $survey)
                    <tr class="hover:bg-gray-50/50 transition-all group" 
                        x-data="{ deleted: false }" 
                        x-show="!deleted" 
                        x-transition.scale.origin.left.opacity.duration.500ms
                        :class="selected.includes('{{ $survey->id }}') ? 'bg-red-50/30' : ''">
                        <td class="px-6 py-4">
                            <input type="checkbox" 
                                   value="{{ $survey->id }}" 
                                   x-model="selected"
                                   @change="updateSelectAll()"
                                   class="survey-checkbox h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500 cursor-pointer transition-all">
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-gray-900">{{ $survey->title }}</div>
                            <div class="text-[10px] text-gray-400 font-black uppercase tracking-tighter">{{ $survey->category instanceof \BackedEnum ? $survey->category->value : $survey->category }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($survey->organization)
                                <div class="flex items-center">
                                    <div class="w-6 h-6 rounded-lg bg-green-50 text-green-600 flex items-center justify-center mr-2 border border-green-100">
                                        <i class="fa-solid fa-building text-[10px]"></i>
                                    </div>
                                    <span class="text-xs font-bold text-gray-600">{{ $survey->organization->name }}</span>
                                </div>
                            @elseif($survey->independent)
                                <div class="flex items-center">
                                    <div class="w-6 h-6 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center mr-2 border border-purple-100">
                                        <i class="fa-solid fa-user-graduate text-[10px]"></i>
                                    </div>
                                    <span class="text-xs font-bold text-gray-600">{{ $survey->independent->name }}</span>
                                </div>
                            @else
                                <span class="text-[10px] font-black text-gray-300 uppercase italic">Platform Admin</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-gray-100 text-gray-500 rounded text-[10px] font-black uppercase tracking-widest">
                                {{ $survey->type instanceof \BackedEnum ? $survey->type->value : $survey->type }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @php 
                                $statusVal = $survey->status instanceof \BackedEnum ? $survey->status->value : $survey->status; 
                            @endphp
                            <span class="px-2 py-1 rounded text-[10px] font-black uppercase tracking-widest
                                {{ $statusVal === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-400' }}">
                                {{ str_replace('_', ' ', $statusVal) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm font-bold text-indigo-600">
                            {{ $survey->responses_count }}
                        </td>
                        <td class="px-6 py-4 text-right pr-20">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('surveys.report', $survey) }}" class="w-7 h-7 bg-gray-50 text-gray-400 rounded-lg flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all">
                                    <i class="fa-solid fa-chart-line text-[10px]"></i>
                                </a>
                                <div x-data="{ confirming: false }" class="inline-flex items-center gap-1">
                                    <button type="button" 
                                            x-show="!confirming"
                                            @click.stop="confirming = true"
                                            class="w-7 h-7 bg-red-50 text-red-500 rounded-lg flex items-center justify-center hover:bg-red-600 hover:text-white transition-all shadow-sm border border-red-100" 
                                            title="Delete permanently">
                                        <i class="fa-solid fa-trash-can text-[10px]"></i>
                                    </button>
                                    <div x-show="confirming" class="flex items-center gap-1 animate-in fade-in slide-in-from-right-2 duration-200" style="display:none">
                                        <span class="text-[9px] font-black text-red-600 uppercase tracking-tighter mr-1 shadow-sm px-1.5 border border-red-200 bg-red-50 rounded">SURE?</span>
                                        <button type="button" 
                                                @click.stop="
                                                    fetch('{{ route('surveys.destroy', $survey) }}', {
                                                        method: 'POST',
                                                        headers: {
                                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                            'Content-Type': 'application/json',
                                                            'Accept': 'application/json'
                                                        },
                                                        body: JSON.stringify({ _method: 'DELETE' })
                                                    }).then(res => {
                                                        if(res.ok) {
                                                            deleted = true;
                                                        } else {
                                                            Swal.fire('Error', 'Survey could not be deleted.', 'error');
                                                        }
                                                    });
                                                "
                                                class="px-2 py-1 bg-red-600 text-white rounded text-[10px] font-black uppercase hover:bg-red-700 shadow-sm">YES</button>
                                        <button type="button" 
                                                @click.stop="confirming = false"
                                                class="px-2 py-1 bg-gray-100 text-gray-400 rounded text-[10px] font-black uppercase hover:bg-gray-200">NO</button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400 italic font-medium">No surveys found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($surveys->hasPages())
        <div class="bg-gray-50/50 px-6 py-4 border-t border-gray-100">
            {{ $surveys->links() }}
        </div>
        @endif
    </div>

    <div class="mt-6 pb-20">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center text-xs font-black text-gray-400 uppercase tracking-widest hover:text-indigo-600 transition-colors">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back to Dashboard
        </a>
    </div>
</div>
@endsection
