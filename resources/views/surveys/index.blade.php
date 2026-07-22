@extends('layouts.app')

@php
    $statusVal = is_object($status) ? $status->value : (string)$status;
@endphp

@section('title')
    @if($statusVal === 'active') {{ __('Active Surveys') }} 
    @elseif($statusVal === 'archived') {{ __('Archived Surveys') }} 
    @elseif($statusVal === 'draft') {{ __('Survey Drafts') }}
    @else {{ __('All Surveys') }} @endif
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
            html: `{{ __('You are about to delete') }} <b>${this.selected.length}</b> {{ __('surveys from your library.') }}<br><br><span class='text-red-500 font-bold text-[10px] tracking-widest'>{{ __('This action is irreversible.') }}</span>`,
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
    {{-- Top Filter Tabs & Action Bar --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8 px-4 sm:px-0">
        <div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">
                @if($statusVal === 'active') {{ __('Active Surveys') }} 
                @elseif($statusVal === 'archived') {{ __('Archived Surveys') }} 
                @elseif($statusVal === 'draft') {{ __('Survey Drafts') }}
                @else {{ __('All Surveys') }} @endif
            </h2>
            <p class="mt-1 text-sm text-gray-500 font-medium">
                @if($statusVal === 'active') {{ __('View and manage your active surveys.') }}
                @elseif($statusVal === 'archived') {{ __('Access historical data and reports from completed surveys.') }}
                @elseif($statusVal === 'draft') {{ __('Manage your survey drafts before they are deployed.') }}
                @else {{ __('Manage all active and draft surveys in your repository.') }} @endif
            </p>
        </div>

        <div class="flex items-center gap-3">
            {{-- Status Tabs --}}
            <div class="inline-flex bg-gray-100 p-1 rounded-2xl">
                <a href="{{ route('surveys.index', ['status' => 'all']) }}" 
                   class="px-4 py-2 rounded-xl text-[10px] font-black tracking-widest transition-all {{ $statusVal === 'all' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}">
                    {{ __('All') }}
                </a>
                <a href="{{ route('surveys.index', ['status' => 'active']) }}" 
                   class="px-4 py-2 rounded-xl text-[10px] font-black tracking-widest transition-all {{ $statusVal === 'active' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}">
                    {{ __('Active') }}
                </a>
                <a href="{{ route('surveys.index', ['status' => 'draft']) }}" 
                   class="px-4 py-2 rounded-xl text-[10px] font-black tracking-widest transition-all {{ $statusVal === 'draft' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}">
                    {{ __('Drafts') }}
                </a>
                <a href="{{ route('surveys.index', ['status' => 'archived']) }}" 
                   class="px-4 py-2 rounded-xl text-[10px] font-black tracking-widest transition-all {{ $statusVal === 'archived' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}">
                    {{ __('Archived') }}
                </a>
            </div>

            {{-- Create Dropdown --}}
            @if($statusVal !== 'archived')
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button @click="open = !open" 
                        class="inline-flex items-center gap-2 px-6 py-3 bg-[#2271b1] text-white rounded-xl font-black text-[10px] tracking-widest shadow-lg shadow-zinc-200/50 hover:bg-[#135e96] transition-all">
                    <i class="fa-solid fa-plus"></i> {{ __('Create Survey') }}
                    <i class="fa-solid fa-chevron-down text-[8px] ml-1"></i>
                </button>
                <div x-show="open" 
                     x-transition 
                     class="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-xl border border-gray-100 p-2 z-50" 
                     style="display: none;">
                    <a href="{{ route('surveys.create') }}" 
                       class="block px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 rounded-xl transition-colors">
                        {{ __('Blank Survey') }}
                    </a>
                    <a href="{{ route('library.templates') }}" 
                       class="block px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 rounded-xl transition-colors">
                        {{ __('From Template') }}
                    </a>
                    <a href="{{ route('surveys.import') }}" 
                       class="block px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 rounded-xl transition-colors">
                        {{ __('Import') }}
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Bulk Action Bar -->
    <div x-show="selected.length > 0" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="mb-6 bg-[#2271b1] p-4 rounded-2xl shadow-xl shadow-zinc-200/50 flex items-center justify-between animate-pulse-slow italic" style="display: none">
        <div class="flex items-center text-white">
            <span class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center mr-3 text-sm font-black" x-text="selected.length"></span>
            <span class="text-xs font-black tracking-widest">{{ __('Surveys Selected') }}</span>
        </div>
        <div class="flex items-center gap-3">
            <button @click="selected = []; allSelected = false" class="text-white/70 hover:text-white text-[10px] font-black tracking-widest transition-colors mr-2">{{ __('Deselect All') }}</button>
            <button @click="bulkDelete()" class="px-6 py-2 bg-white text-red-600 rounded-xl font-black text-[10px] tracking-widest shadow-sm hover:bg-red-50 transition-all transform hover:scale-105 active:scale-95">
                <i class="fa-solid fa-trash-can mr-2"></i> {{ __('Delete Selected') }}
            </button>
        </div>
    </div>

    <div class="mb-6 bg-white/40 backdrop-blur-md p-3 rounded-2xl shadow-sm">
        <form action="{{ url()->current() }}" method="GET" class="flex flex-wrap gap-3 items-center">
            <div class="relative flex-grow max-w-sm group">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 group-focus-within:text-[#2271b1] transition-colors"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search By Title...') }}" 
                       class="w-full pl-10 pr-4 py-2 bg-white border border-gray-100 text-[10px] font-black tracking-widest focus:ring-2 focus:ring-[#2271b1]/20 focus:border-[#2271b1] transition-all shadow-sm">
            </div>
            <div class="relative w-48 group">
                <i class="fa-solid fa-filter absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 group-focus-within:text-[#2271b1] transition-colors"></i>
                <select name="category" 
                        class="w-full pl-10 pr-8 py-2 bg-white border border-gray-100 text-[10px] font-black tracking-widest focus:ring-2 focus:ring-[#2271b1]/20 focus:border-[#2271b1] appearance-none transition-all shadow-sm">
                    <option value="">{{ __('All Categories') }}</option>
                    @foreach(\App\Enums\SurveyCategory::cases() as $category)
                        @php $val = $category instanceof \BackedEnum ? $category->value : $category; @endphp
                        <option value="{{ $val }}" {{ request('category') === $val ? 'selected' : '' }}>
                            {{ ucfirst(__($val)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-6 py-2 bg-[#2271b1] text-white rounded-xl font-black text-[10px] tracking-widest shadow-lg shadow-zinc-200/50 hover:bg-[#135e96] transition-all">
                {{ __('Filter') }}
            </button>
            @if(request()->anyFilled(['search', 'category']))
                <a href="{{ url()->current() }}" class="text-[10px] font-black tracking-widest text-gray-400 hover:text-red-500 transition-colors">
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
                                   class="h-4 w-4 text-[#2271b1] border-gray-300 rounded focus:ring-[#2271b1] cursor-pointer transition-all">
                        </th>
                        <th scope="col" class="px-8 py-5 text-left text-xs font-black text-gray-400 tracking-widest">{{ __('Survey Name') }}</th>
                        <th scope="col" class="px-6 py-5 text-left text-xs font-black text-gray-400  tracking-widest">{{ __('Status') }}</th>
                        <th scope="col" class="px-6 py-5 text-left text-xs font-black text-gray-400 tracking-widest">{{ __('Access') }}</th>
                        <th scope="col" class="px-6 py-5 text-left text-xs font-black text-gray-400 tracking-widest">{{ __('Responses') }}</th>
                        <th scope="col" class="px-8 py-5 text-right text-xs font-black text-gray-400 tracking-widest pr-20">{{ __('Manage') }}</th>
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
                            :class="selected.includes('{{ $survey->id }}') ? 'bg-zinc-100' : ''">
                            <td class="px-6 py-6">
                                <input type="checkbox" 
                                       value="{{ $survey->id }}" 
                                       x-model="selected"
                                       @change="updateSelectAll()"
                                       class="survey-checkbox h-4 w-4 text-[#2271b1] border-gray-300 rounded focus:ring-[#2271b1] cursor-pointer transition-all">
                            </td>
                            <td class="px-8 py-6">
                                <a href="{{ route('surveys.summary', $survey) }}" class="block group-hover:translate-x-1 transition-transform">
                                    <span class="text-[14px] font-medium text-gray-1000 tracking-tight block mb-0.5 group-hover:text-[#2271b1]">{{ $survey->title }}</span>
                                    <span class="text-[10px] text-gray-400 font-bold">{{ __($survey->category->value ?? 'General') }}</span>
                                </a>
                            </td>
                            <td class="px-6 py-6">
                                @php
                                    $statusColor = match($statusVal) {
                                        'active' => ' text-green-600 border-[#2271b1]',
                                        'draft' => 'text-gray-600 border[#2271b1]',
                                        'archived' => 'text-gray-600 border-[#2271b1]',
                                        default => 'bg-slate-50 text-slate-600 border-slate-100',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[10px] font-semibold tracking-wider border {{ $statusColor }}">
                                    {{ ucfirst(__($statusVal)) }}
                                </span>
                            </td>
                            <td class="px-6 py-6 font-medium">
                                <div class="flex flex-col gap-2">
                                    @if($survey->type === \App\Enums\SurveyType::Public || $survey->public_access === 'submit')
                                        <span class="inline-flex items-center px-3 py-1 text-[11px] tracking-widest shadow-sm ring-gray-300 ring-1 rounded-xl">
                                            <i class="fa-solid mr-1.5 leading-none text-semi-bold"></i> {{ __('Public Access') }}
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
                                            <span class="inline-flex items-center px-3 py-1 text-[11px]  tracking-widest  shadow-sm ring-1">
                                                <i class="fa-solid mr-1.5 leading-none"></i> {{ __('Budget Exhausted') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 text-[11px]  tracking-widest  shadow-sm ring-1">
                                                <i class="fa-solid mr-1.5 leading-none"></i> {{ __('Paid') }} ({{ number_format((float)$rewardVal, 0) }} KES)
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-6">
                                <div class="flex items-center">
                                    <span class="text-sm font-black text-[#2271b1] mr-2">{{ $survey->responses_count ?? 0 }}</span>
                                    <div class="w-16 h-1 bg-gray-100 overflow-hidden">
                                        <div class="h-full bg-zinc-1000" style="width: {{ min(100, ($survey->responses_count ?? 0) * 5) }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right pr-20">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('surveys.summary', $survey) }}" class="w-8 h-8 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center hover:bg-[#2271b1] hover:text-white transition-all shadow-sm" title="Open Survey Hub">
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
                                            <span class="text-[9px] font-black text-red-600 tracking-tighter mr-1 shadow-sm px-1.5 border border-red-200 bg-red-50 rounded">{{ __('Sure?') }}</span>
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
                                                    class="px-2 py-1 bg-red-600 text-white rounded text-[10px] font-black hover:bg-red-700 shadow-sm">{{ __('Yes') }}</button>
                                            <button type="button" 
                                                    @click.stop="confirming = false"
                                                    class="px-2 py-1 bg-gray-100 text-gray-400 rounded text-[10px] font-black hover:bg-gray-200">{{ __('No') }}</button>
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
                                    <p class="text-sm font-semibold text-gray-500">{{ __('No surveys found matching your query.') }}</p>
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
