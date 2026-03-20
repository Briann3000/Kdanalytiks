@extends('layouts.app')

@section('content')
    <div class="mb-8 flex items-center justify-between px-4 sm:px-0">
        <div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight uppercase">Survey Reports</h2>
            <p class="mt-1 text-sm text-gray-500 font-medium">Generate and view analytical reports for your research projects.</p>
        </div>
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
        @if ($surveys->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th scope="col" class="px-8 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Survey Detail</th>
                            <th scope="col" class="px-6 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Responses</th>
                            <th scope="col" class="px-8 py-5 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-50">
                        @foreach ($surveys as $survey)
                            <tr class="hover:bg-gray-50/50 transition-colors group">
                                <td class="px-8 py-6">
                                    <div class="group-hover:translate-x-1 transition-transform">
                                        <span class="text-sm font-black text-gray-900 uppercase tracking-tight block mb-0.5">{{ $survey->title }}</span>
                                        <span class="text-[10px] text-gray-400 font-bold uppercase italic">{{ $survey->category->value ?? 'General' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <div class="flex items-center">
                                        <span class="text-sm font-black text-indigo-600 mr-2">{{ $survey->responses_count }}</span>
                                        <div class="w-16 h-1 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-indigo-500" style="width: {{ min(100, $survey->responses_count * 5) }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <a href="{{ route('surveys.report', $survey) }}" 
                                       class="inline-flex items-center px-4 py-2 bg-indigo-50 text-indigo-600 rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-indigo-600 hover:text-white transition-all shadow-sm border border-indigo-100">
                                        <i class="fa-solid fa-chart-pie mr-2"></i> Report
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($surveys instanceof \Illuminate\Pagination\LengthAwarePaginator && $surveys->hasPages())
                <div class="px-8 py-5 bg-gray-50/50 border-t border-gray-50">
                    {{ $surveys->links() }}
                </div>
            @endif
        @else
            <div class="px-4 py-12 flex flex-col items-center justify-center text-center sm:px-6">
                <i class="fa-solid fa-chart-line text-gray-300 text-5xl mb-4"></i>
                <p class="text-gray-500 font-medium text-lg">No reports available</p>
                <p class="text-gray-400 text-sm mt-1">Once you have active surveys with responses, you can generate reports
                    here.</p>
            </div>
        @endif
    </div>
@endsection