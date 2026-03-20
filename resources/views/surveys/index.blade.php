@extends('layouts.app')

@section('title')
    @if($status->value === 'active') Manage Projects @elseif($status->value === 'archived') Archived Projects @else Library - Drafts @endif
@endsection

@section('content')
<div class="flex items-center justify-between mb-8 px-4 sm:px-0">
    <div>
        <h2 class="text-2xl font-black text-gray-900 tracking-tight uppercase">
            @if($status->value === 'active') Active Projects @elseif($status->value === 'archived') Archived Projects @else Project Drafts @endif
        </h2>
        <p class="mt-1 text-sm text-gray-500 font-medium">
            @if($status->value === 'active') View and manage your currently deployed data collection projects.
            @elseif($status->value === 'archived') Access historical data and reports from completed projects.
            @else Manage your survey schemas before they are deployed to projects. @endif
        </p>
    </div>
    @if($status->value !== 'archived')
    <div>
        <a href="{{ route('surveys.create') }}" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all">
            <i class="fa-solid fa-plus mr-2"></i> New Project
        </a>
    </div>
    @endif
</div>

<div class="mb-8 bg-white/50 backdrop-blur-sm p-4 rounded-3xl border border-gray-100 shadow-sm">
    <form action="{{ url()->current() }}" method="GET" class="flex flex-col md:flex-row gap-4 items-center">
        <div class="relative w-full md:w-96 group">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-sm text-gray-400 group-focus-within:text-indigo-600 transition-colors"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by title..." 
                   class="w-full pl-12 pr-4 py-3 bg-white border border-gray-100 rounded-2xl text-[10px] font-black uppercase tracking-widest focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all shadow-sm">
        </div>
        <div class="relative w-full md:w-64 group">
            <i class="fa-solid fa-filter absolute left-4 top-1/2 -translate-y-1/2 text-sm text-gray-400 group-focus-within:text-indigo-600 transition-colors"></i>
            <select name="category" 
                    class="w-full pl-12 pr-10 py-3 bg-white border border-gray-100 rounded-2xl text-[10px] font-black uppercase tracking-widest focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 appearance-none transition-all shadow-sm">
                <option value="">All Categories</option>
                <option value="academic" {{ request('category') === 'academic' ? 'selected' : '' }}>Academic</option>
                <option value="baseline" {{ request('category') === 'baseline' ? 'selected' : '' }}>Baseline</option>
                <option value="feasibility" {{ request('category') === 'feasibility' ? 'selected' : '' }}>Feasibility</option>
                <option value="market_research" {{ request('category') === 'market_research' ? 'selected' : '' }}>Market Research</option>
                <option value="others" {{ request('category') === 'others' ? 'selected' : '' }}>Others</option>
                <option value="polls" {{ request('category') === 'polls' ? 'selected' : '' }}>Polls</option>
            </select>
        </div>
        <button type="submit" class="px-8 py-3 bg-indigo-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all">
            Filter
        </button>
        @if(request()->anyFilled(['search', 'category']))
            <a href="{{ url()->current() }}" class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-red-500 transition-colors">
                Clear
            </a>
        @endif
    </form>
</div>

<div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50/50">
                <tr>
                    <th scope="col" class="px-8 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Survey Detail</th>
                    <th scope="col" class="px-6 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Status</th>
                    <th scope="col" class="px-6 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Framework</th>
                    <th scope="col" class="px-6 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Submissions</th>
                    <th scope="col" class="px-8 py-5 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Management</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 bg-white">
                @forelse ($surveys as $survey)
                    @php 
                        $statusVal = $survey->status instanceof \BackedEnum ? $survey->status->value : $survey->status;
                    @endphp
                    <tr class="hover:bg-gray-50/50 transition-colors group" x-data="{ deleted: false }" x-show="!deleted" x-transition.scale.origin.left.opacity.duration.500ms>
                        <td class="px-8 py-6">
                            <a href="{{ route('projects.summary', $survey) }}" class="block group-hover:translate-x-1 transition-transform">
                                <span class="text-sm font-black text-gray-900 uppercase tracking-tight block mb-0.5 group-hover:text-indigo-600">{{ $survey->title }}</span>
                                <span class="text-[10px] text-gray-400 font-bold uppercase italic">{{ $survey->category->value ?? 'General' }}</span>
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
                                {{ $statusVal }}
                            </span>
                        </td>
                        <td class="px-6 py-6">
                            <span class="inline-flex items-center px-3 py-1 rounded-lg text-[10px] font-black bg-slate-100 text-slate-600 uppercase tracking-widest">
                                <i class="fa-solid fa-layer-group mr-1.5 opacity-50"></i>
                                {{ $survey->type->value === 'public' ? 'Public' : 'Invitation' }}
                            </span>
                        </td>
                        <td class="px-6 py-6">
                            <div class="flex items-center">
                                <span class="text-sm font-black text-indigo-600 mr-2">{{ $survey->responses_count ?? 0 }}</span>
                                <div class="w-16 h-1 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-indigo-500" style="width: {{ min(100, ($survey->responses_count ?? 0) * 5) }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-6 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('projects.summary', $survey) }}" class="w-8 h-8 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all shadow-sm" title="Open Project Hub">
                                     <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                 </a>
                                <form id="delete-form-{{ $survey->id }}" action="{{ route('surveys.destroy', $survey) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
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
                                                    console.log('AJAX Deleting Survey:', '{{ $survey->id }}');
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
                                                            console.log('AJAX Success for Survey:', '{{ $survey->id }}');
                                                        } else {
                                                            alert('Error - survey could not be deleted from server.');
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
                        <td colspan="5" class="px-8 py-20 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center text-gray-300 mb-4">
                                    <i class="fa-solid fa-folder-open text-2xl"></i>
                                </div>
                                <p class="text-xs font-black text-gray-400 uppercase tracking-widest">Empty Workspace</p>
                                <p class="text-[10px] text-gray-300 font-bold uppercase italic mt-1">No items match your current selection.</p>
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
@endsection
