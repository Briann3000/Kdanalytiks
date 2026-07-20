@extends('surveys.hub')

@section('survey-content')
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Main Stats -->
        <div class="md:col-span-2 space-y-8">
            <div class="bg-white rounded-3xl border border-gray-100 p-8 shadow-sm">
                <h3 class="text-sm font-bold text-gray-500 tracking-wider mb-6">{{ __('Overview') }}</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-6">
                    <div class="p-6 rounded-2xl bg-zinc-100/50 border border-zinc-200">
                        <p class="text-sm font-bold text-zinc-500   tracking-wider mb-1">
                            {{ __('Total Submissions') }}
                        </p>
                        <p class="text-3xl font-black text-[#135e96]">{{ $survey->responses_count }}</p>
                    </div>
                    <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100">
                        <p class="text-sm font-bold text-slate-400   tracking-wider mb-1">{{ __('Status') }}</p>
                        <p class="text-xl font-bold text-slate-700   tracking-tight">
                            {{ __(ucfirst($survey->status->value)) }}
                        </p>
                    </div>
                    <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100">
                        <p class="text-xs font-black text-slate-400   tracking-widest mb-1">
                            {{ __('Date Created') }}
                        </p>
                        <p class="text-xl font-black text-slate-700 tracking-tighter">
                            {{ $survey->created_at->format('M d, Y') }}
                        </p>
                    </div>
                </div>
            </div>

            @if($survey->status->value === 'active')
                <div class="bg-gray-600 rounded-3xl p-8 text-white shadow-xl relative overflow-hidden">
                    <div class="relative z-10 max-w-lg">

                        <h2 class="text-2xl font-black tracking-tight mb-4">{{ __('Your survey link') }}</h2>
                        <div class="flex items-center bg-white/10 rounded-xl p-3 mb-6 backdrop-blur-sm border border-white/20">
                            <code class="text-xs font-bold truncate flex-1">{{ route('surveys.show', $survey) }}</code>
                            <button onclick="navigator.clipboard.writeText('{{ route('surveys.show', $survey) }}')"
                                class="ml-3 p-2 bg-white/20 rounded-lg hover:bg-white/30 transition-all">
                                <i class="fa-solid fa-copy text-xs"></i>
                            </button>
                        </div>
                        <div class="flex gap-4">
                            <a href="{{ route('surveys.show', $survey) }}" target="_blank"
                                class="px-6 py-3 bg-white text-[#2271b1] rounded-xl font-bold text-[12px]   tracking-widest hover:bg-charcoal-400 transition-all">
                                <i class="fa-solid mr-2"></i> {{ __('View Survey') }}
                            </a>
                        </div>
                    </div>

                </div>
            @else
                <div class="bg-amber-50 rounded-3xl p-8 border border-amber-100 text-amber-800">
                    <div class="flex items-start gap-4">
                        <div
                            class="w-12 h-12 rounded-2xl bg-amber-100 flex items-center justify-center text-amber-600 flex-shrink-0">
                            <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black   tracking-widest mb-1">{{ __('Survey in Draft Mode') }}</h3>
                            <p class="text-xs font-bold opacity-70 leading-relaxed mb-4">
                                {{ __('This survey is currently a draft. No data can be collected until it is deployed.') }}
                            </p>
                            <form action="{{ route('surveys.publish', $survey) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="px-6 py-3 bg-amber-600 text-white rounded-xl font-black text-[10px]   tracking-widest hover:bg-amber-700 transition-all shadow-lg shadow-amber-200">
                                    {{ __('Deploy Now') }}
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
                <h3 class="text-xs font-black text-gray-400   tracking-[0.2em] mb-4">
                    {{ __('Project Description') }}
                </h3>
                <p class="text-sm text-gray-600 font-medium leading-relaxed italic">
                    {{ $survey->description ?? __('No description provided for this project.') }}
                </p>
            </div>

            <div class="bg-white rounded-3xl border border-gray-100 p-8 shadow-sm">
                <h3 class="text-xs font-black text-gray-400   tracking-[0.2em] mb-6">{{ __('Quick Actions') }}
                </h3>
                <div class="space-y-3">
                    <a href="{{ route('surveys.edit', $survey) }}"
                        class="flex items-center p-4 bg-gray-50 rounded-2xl hover:bg-zinc-100 hover:text-[#2271b1] transition-all group">
                        <div
                            class="w-10 h-10 rounded-xl bg-white border border-gray-100 flex items-center justify-center mr-4 group-hover:border-zinc-200">
                            <i class="fa-solid fa-pen-to-square text-sm"></i>
                        </div>
                        <span class="text-sm font-bold   tracking-wider">{{ __('Update Form') }}</span>
                    </a>
                    <a href="{{ route('surveys.data', $survey) }}"
                        class="flex items-center p-4 bg-gray-50 rounded-2xl hover:bg-emerald-50 hover:text-emerald-600 transition-all group">
                        <div
                            class="w-10 h-10 rounded-xl bg-white border border-gray-100 flex items-center justify-center mr-4 group-hover:border-emerald-100">
                            <i class="fa-solid fa-table text-sm"></i>
                        </div>
                        <span class="text-sm font-bold   tracking-wider">{{ __('Browse Submissions') }}</span>
                    </a>
                    <a href="{{ route('surveys.reports', $survey) }}"
                        class="flex items-center p-4 bg-gray-50 rounded-2xl hover:bg-blue-50 hover:text-blue-600 transition-all group">
                        <div
                            class="w-10 h-10 rounded-xl bg-white border border-gray-100 flex items-center justify-center mr-4 group-hover:border-blue-100">
                            <i class="fa-solid fa-chart-column text-sm"></i>
                        </div>
                        <span class="text-sm font-bold   tracking-wider">{{ __('View Analytics') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection