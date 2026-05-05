@extends('layouts.app')

@section('title')
    @if($status->value === 'active') {{ __('Active Surveys') }} @elseif($status->value === 'archived') {{ __('Archived Surveys') }} @else {{ __('Survey Drafts') }} @endif
@endsection

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
            Swal.fire('{{ __('No Selection') }}', '{{ __('Please select at least one survey to delete.') }}', 'info');
            return;
        }
        
        const result = await Swal.fire({
            title: '{{ __('Delete Selected Surveys?') }}',
            html: `{{ __('You are about to delete') }} <b>${this.selected.length}</b> {{ __('surveys from the platform inventory.') }}<br><br><span class='text-red-500 font-bold uppercase text-[10px] tracking-widest'>{{ __('This action is irreversible.') }}</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __('Yes, Delete All') }}',
            cancelButtonText: '{{ __('Cancel') }}',
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
                title: '{{ __('Processing...') }}',
                html: '{{ __('Mass deleting surveys from inventory...') }}',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const res = await fetch('{{ route('surveys.bulk-destroy') }}', {
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
                        title: '{{ __('Deleted!') }}',
                        text: data.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('{{ __('Error') }}', data.message || '{{ __('Bulk delete failed.') }}', 'error');
                }
            } catch (err) {
                Swal.fire('{{ __('Error') }}', '{{ __('An error occurred during bulk deletion.') }}', 'error');
            }
        }
    }
}">
    <div class="flex items-center justify-between mb-8 px-4 sm:px-0">
        <div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight uppercase">
                @if($status->value === 'active') {{ __('Active Surveys') }} @elseif($status->value === 'archived') {{ __('Archived Surveys') }} @else {{ __('Survey Drafts') }} @endif
            </h2>
            <p class="mt-1 text-sm text-gray-500 font-medium">
                @if($status->value === 'active') {{ __('View and manage your currently deployed data collection surveys.') }}
                @elseif($status->value === 'archived') {{ __('Access historical data and reports from completed surveys.') }}
                @else {{ __('Manage your survey schemas before they are deployed to surveys.') }} @endif
            </p>
        </div>
        @if($status->value !== 'archived')
        <div>
            <a href="{{ route('surveys.create') }}" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all">
                <i class="fa-solid fa-plus mr-2"></i> {{ __('New Survey') }}
            </a>
        </div>
        @endif
    </div>

    <!-- Bulk Action Bar -->
    <div x-show="selected.length > 0" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="mb-6 bg-indigo-600 p-4 rounded-2xl shadow-xl shadow-indigo-100 flex items-center justify-between animate-pulse-slow italic" style="display: none">
        <div class="flex items-center text-white">
            <span class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center mr-3 text-sm font-black" x-text="selected.length"></span>
            <span class="text-xs font-black uppercase tracking-widest">{{ __('Surveys Selected') }}</span>
        </div>
        <div class="flex items-center gap-3">
            <button @click="selected = []; allSelected = false" class="text-white/70 hover:text-white text-[10px] font-black uppercase tracking-widest transition-colors mr-2">{{ __('Deselect All') }}</button>
            <button @click="bulkDelete()" class="px-6 py-2 bg-white text-red-600 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-sm hover:bg-red-50 transition-all transform hover:scale-105 active:scale-95">
                <i class="fa-solid fa-trash-can mr-2"></i> {{ __('Delete Selected') }}
            </button>
        </div>
    </div>

    <div class="mb-6 bg-white/40 backdrop-blur-md p-3 rounded-2xl border border-gray-100 shadow-sm">
        <form action="{{ url()->current() }}" method="GET" class="flex flex-wrap gap-3 items-center">
            <div class="relative flex-grow max-w-sm group">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 group-focus-within:text-indigo-600 transition-colors"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('SEARCH BY TITLE...') }}" 
                       class="w-full pl-10 pr-4 py-2 bg-white border border-gray-100 rounded-xl text-[10px] font-black uppercase tracking-widest focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all shadow-sm">
            </div>
            <div class="relative w-48 group">
                <i class="fa-solid fa-filter absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 group-focus-within:text-indigo-600 transition-colors"></i>
                <select name="category" 
                        class="w-full pl-10 pr-8 py-2 bg-white border border-gray-100 rounded-xl text-[10px] font-black uppercase tracking-widest focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 appearance-none transition-all shadow-sm">
                    <option value="">{{ __('All Categories') }}</option>
                    @foreach(\App\Enums\SurveyCategory::cases() as $category)
                        @php $val = $category instanceof \BackedEnum ? $category->value : $category; @endphp
                        <option value="{{ $val }}" {{ request('category') === $val ? 'selected' : '' }}>
                            {{ __($val) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all">
                {{ __('Filter') }}
            </button>
            @if(request()->anyFilled(['search', 'category']))
                <a href="{{ url()->current() }}" class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-red-500 transition-colors">
                    {{ __('Clear') }}
                </a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="min-w-[900px] w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th scope="col" class="px-6 py-5 text-left w-10">
                            <input type="checkbox" 
                                   @change="toggleAll()" 
                                   x-model="allSelected"
                                   class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 cursor-pointer transition-all">
                        </th>
                        <th scope="col" class="px-8 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('SURVEY DETAIL') }}</th>
                        <th scope="col" class="px-6 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('STATUS') }}</th>
                        <th scope="col" class="px-6 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('ACCESS') }}</th>
                        <th scope="col" class="px-6 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('RESPONSES') }}</th>
                        <th scope="col" class="px-8 py-5 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest pr-20">{{ __('MANAGEMENT') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 bg-white">
                    @forelse ($surveys as $survey)
                        @php 
                            $statusVal = $survey->status instanceof \BackedEnum ? $survey->status->value : $survey->status;
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition-all group cursor-pointer" 
                            @click="if(!['A', 'BUTTON', 'INPUT', 'I'].includes($event.target.tagName) && !$event.target.closest('a, button, input')) window.location = '{{ route('surveys.summary', $survey) }}'"
                            x-data="{ deleted: false }" 
                            x-show="!deleted" 
                            x-transition.scale.origin.left.opacity.duration.500ms
                            :class="selected.includes('{{ $survey->id }}') ? 'bg-indigo-50/30' : ''">
                            <td class="px-6 py-6">
                                <input type="checkbox" 
                                       value="{{ $survey->id }}" 
                                       x-model="selected"
                                       @change="updateSelectAll()"
                                       class="survey-checkbox h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 cursor-pointer transition-all">
                            </td>
                            <td class="px-8 py-6">
                                <a href="{{ route('surveys.summary', $survey) }}" class="block group-hover:translate-x-1 transition-transform">
                                    <span class="text-sm font-black text-gray-900 uppercase tracking-tight block mb-0.5 group-hover:text-indigo-600">{{ $survey->title }}</span>
                                    <span class="text-[10px] text-gray-400 font-bold uppercase italic">{{ __($survey->category->value ?? 'General') }}</span>
                                </a>
                            </td>
                            <td class="px-6 py-6">
                                @php
                                    $statusColor = match($statusVal) {
                                        'active' => 'bg-green-50 text-green-600 border-green-100',
                                        'draft' => 'bg-amber-50 text-amber-600 border-amber-100',
                                        'archived' => 'bg-gray-50 text-gray-600 border-gray-100',
                                        default => 'bg-slate-50 text-slate-600 border-slate-100',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border {{ $statusColor }}">
                                    {{ __($statusVal) }}
                                </span>
                            </td>
                            <td class="px-6 py-6 font-medium">
                                <div class="flex flex-col gap-2">
                                    @if($survey->type === \App\Enums\SurveyType::Public || $survey->public_access === 'submit')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-blue-500 text-white shadow-sm ring-1 ring-blue-600/20">
                                            <i class="fa-solid fa-globe mr-1.5 leading-none"></i> {{ __('Public Access') }}
                                        </span>
                                    @endif
                                    
                                    @if($survey->is_paid)
                                        @php
                                            $rewardVal = $survey->reward_per_response instanceof \UnitEnum ? $survey->reward_per_response->value : $survey->reward_per_response;
                                            $spentVal = $survey->current_reward_spent instanceof \UnitEnum ? $survey->current_reward_spent->value : $survey->current_reward_spent;
                                            $budgetVal = $survey->reward_budget instanceof \UnitEnum ? $survey->reward_budget->value : $survey->reward_budget;
                                            $isExhausted = $spentVal >= $budgetVal;
                                        @endphp
                                        @if($isExhausted)
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-orange-500 text-white shadow-sm ring-1 ring-orange-600/20">
                                                <i class="fa-solid fa-circle-exclamation mr-1.5 leading-none"></i> {{ __('Budget Exhausted') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-emerald-500 text-white shadow-sm ring-1 ring-emerald-600/20">
                                                <i class="fa-solid fa-sack-dollar mr-1.5 leading-none"></i> {{ __('Paid') }} ({{ number_format((float)$rewardVal, 0) }} KES)
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-6">
                                <div class="flex items-center">
                                    <span class="text-sm font-black text-indigo-600 mr-2">{{ $survey->responses_count ?? 0 }}</span>
                                    <div class="w-16 h-1 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-indigo-500" style="width: {{ min(100, ($survey->responses_count ?? 0) * 5) }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right pr-20">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('surveys.summary', $survey) }}" class="w-8 h-8 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all shadow-sm" title="Open Project Hub">
                                         <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                     </a>
                                    <div x-data="{ confirming: false }" class="inline-flex items-center gap-1">
                                        <button type="button" 
                                                x-show="!confirming"
                                                @click.stop="confirming = true"
                                                class="w-7 h-7 bg-red-50 text-red-500 rounded-lg flex items-center justify-center hover:bg-red-600 hover:text-white transition-all shadow-sm border border-red-100" 
                                                title="{{ __('Delete permanently') }}">
                                            <i class="fa-solid fa-trash-can text-[10px]"></i>
                                        </button>
                                        <div x-show="confirming" class="flex items-center gap-1 animate-in fade-in slide-in-from-right-2 duration-200" style="display:none">
                                            <span class="text-[9px] font-black text-red-600 uppercase tracking-tighter mr-1 shadow-sm px-1.5 border border-red-200 bg-red-50 rounded">{{ __('SURE?') }}</span>
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
                                                                Swal.fire('{{ __('Error') }}', '{{ __('Survey could not be deleted.') }}', 'error');
                                                            }
                                                        });
                                                    "
                                                    class="px-2 py-1 bg-red-600 text-white rounded text-[10px] font-black uppercase hover:bg-red-700 shadow-sm">{{ __('YES') }}</button>
                                            <button type="button" 
                                                    @click.stop="confirming = false"
                                                    class="px-2 py-1 bg-gray-100 text-gray-400 rounded text-[10px] font-black uppercase hover:bg-gray-200">{{ __('NO') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center text-gray-300 mb-4">
                                        <i class="fa-solid fa-folder-open text-2xl"></i>
                                    </div>
                                    <p class="text-xs font-black text-gray-400 uppercase tracking-widest">{{ __('Empty Workspace') }}</p>
                                    <p class="text-[10px] text-gray-300 font-bold uppercase italic mt-1">{{ __('No items match your current selection.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($surveys instanceof \Illuminate\Pagination\LengthAwarePaginator && $surveys->hasPages())
        <div class="mt-8 px-4 pb-20">
            {{ $surveys->links() }}
        </div>
    @else
        <div class="pb-20"></div>
    @endif
</div>
@endsection
