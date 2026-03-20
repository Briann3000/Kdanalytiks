@extends('surveys.project_hub')

@section('project-content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <!-- Main Stats -->
    <div class="md:col-span-2 space-y-8">
        <div class="bg-white rounded-3xl border border-gray-100 p-8 shadow-sm">
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-6">Overview</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-6">
                <div class="p-6 rounded-2xl bg-indigo-50/50 border border-indigo-100">
                    <p class="text-[11px] font-bold text-indigo-400 uppercase tracking-wider mb-1">Total Submissions</p>
                    <p class="text-3xl font-black text-indigo-700">{{ $survey->responses_count }}</p>
                </div>
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Status</p>
                    <p class="text-xl font-bold text-slate-700 uppercase tracking-tight">{{ $survey->status->value }}</p>
                </div>
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Date Created</p>
                    <p class="text-xl font-black text-slate-700 tracking-tighter">{{ $survey->created_at->format('M d, Y') }}</p>
                </div>
            </div>
        </div>

        @if($survey->status->value === 'active')
        <div class="bg-emerald-600 rounded-3xl p-8 text-white shadow-xl shadow-emerald-100 relative overflow-hidden">
            <div class="relative z-10 max-w-lg">
                <h3 class="text-xs font-black uppercase tracking-widest mb-2 opacity-80">Public Data Collection</h3>
                <h2 class="text-2xl font-black tracking-tight mb-4">Your survey is live!</h2>
                <div class="flex items-center bg-white/10 rounded-xl p-3 mb-6 backdrop-blur-sm border border-white/20">
                    <code class="text-xs font-bold truncate flex-1">{{ route('surveys.show', $survey) }}</code>
                    <button onclick="navigator.clipboard.writeText('{{ route('surveys.show', $survey) }}')" class="ml-3 p-2 bg-white/20 rounded-lg hover:bg-white/30 transition-all">
                        <i class="fa-solid fa-copy text-xs"></i>
                    </button>
                </div>
                <div class="flex gap-4">
                    <a href="{{ route('surveys.show', $survey) }}" target="_blank" class="px-6 py-3 bg-white text-emerald-700 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-gray-100 transition-all">
                        <i class="fa-solid fa-eye mr-2"></i> View Survey
                    </a>
                </div>
            </div>
            <i class="fa-solid fa-rocket absolute right-[-20px] bottom-[-20px] text-white/5 text-[150px] -rotate-12"></i>
        </div>
        @else
        <div class="bg-amber-50 rounded-3xl p-8 border border-amber-100 text-amber-800">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-100 flex items-center justify-center text-amber-600 flex-shrink-0">
                    <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black uppercase tracking-widest mb-1">Project in Draft Mode</h3>
                    <p class="text-xs font-bold opacity-70 leading-relaxed mb-4">This project is currently a draft. No data can be collected until it is deployed.</p>
                    <form action="{{ route('projects.publish', $survey) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-6 py-3 bg-amber-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-amber-700 transition-all shadow-lg shadow-amber-200">
                            Deploy Now
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar Info -->
    <div class="space-y-8">
        <div class="bg-white rounded-3xl border border-gray-100 p-8 shadow-sm">
            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4">Project Description</h3>
            <p class="text-sm text-gray-600 font-medium leading-relaxed italic">
                {{ $survey->description ?? 'No description provided for this project.' }}
            </p>
        </div>

        <div class="bg-white rounded-3xl border border-gray-100 p-8 shadow-sm">
            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-6">Quick Actions</h3>
            <div class="space-y-3">
                <a href="{{ route('surveys.edit', $survey) }}" class="flex items-center p-4 bg-gray-50 rounded-2xl hover:bg-indigo-50 hover:text-indigo-600 transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-white border border-gray-100 flex items-center justify-center mr-4 group-hover:border-indigo-100">
                        <i class="fa-solid fa-pen-to-square text-sm"></i>
                    </div>
                    <span class="text-sm font-bold uppercase tracking-wider">Update Form</span>
                </a>
                <a href="{{ route('projects.data', $survey) }}" class="flex items-center p-4 bg-gray-50 rounded-2xl hover:bg-emerald-50 hover:text-emerald-600 transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-white border border-gray-100 flex items-center justify-center mr-4 group-hover:border-emerald-100">
                        <i class="fa-solid fa-table text-sm"></i>
                    </div>
                    <span class="text-sm font-bold uppercase tracking-wider">Browse Submissions</span>
                </a>
                <a href="{{ route('projects.reports', $survey) }}" class="flex items-center p-4 bg-gray-50 rounded-2xl hover:bg-blue-50 hover:text-blue-600 transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-white border border-gray-100 flex items-center justify-center mr-4 group-hover:border-blue-100">
                        <i class="fa-solid fa-chart-column text-sm"></i>
                    </div>
                    <span class="text-sm font-bold uppercase tracking-wider">View Analytics</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
