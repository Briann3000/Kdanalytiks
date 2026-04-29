@extends('layouts.app')

@section('title', __('Research Studio - Reports History'))

@section('content')
    <div class="h-full flex flex-col overflow-hidden bg-white text-gray-800">
        <!-- Header -->
        <div class="h-14 border-b border-gray-100 flex items-center justify-between px-6 flex-shrink-0 bg-white">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-sm">
                    <i class="fa-solid fa-clock-rotate-left text-sm"></i>
                </div>
                <h1 class="text-sm font-bold text-gray-900 tracking-tight">{{ __('Reports History') }}</h1>
            </div>
            <div class="flex items-center space-x-4">
                <a href="{{ route('research-proposal.index') }}"
                    class="text-xs font-bold text-gray-500 hover:text-indigo-600 transition-colors">
                    <i class="fa-solid fa-arrow-left mr-1"></i> {{ __('Back to Generator') }}
                </a>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto bg-gray-50/30 p-8 custom-scrollbar">
            <div class="max-w-6xl mx-auto space-y-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                    <!-- Generated Reports Section -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between px-1">
                            <h2 class="text-xs font-black text-gray-400 uppercase tracking-widest">
                                {{ __('Synthesis Reports') }}</h2>
                            <span
                                class="text-[10px] font-bold bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full">{{ count($reports) }}
                                {{ __('Items') }}</span>
                        </div>

                        <div class="space-y-3">
                            @forelse($reports as $report)
                                {{-- This section will show reports if we have a model for them later --}}
                            @empty
                                <div class="bg-white border border-gray-100 rounded-2xl p-12 text-center shadow-sm">
                                    <div
                                        class="w-12 h-12 bg-gray-50 text-gray-300 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="fa-solid fa-file-invoice text-xl"></i>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-800 mb-1">{{ __('No reports synthesized yet') }}</h3>
                                    <p class="text-xs text-gray-500 font-medium">
                                        {{ __('Generate your first report from the Studio.') }}</p>
                                    <a href="{{ route('research-proposal.index') }}"
                                        class="mt-4 inline-flex items-center text-xs font-bold text-indigo-600 hover:underline">
                                        {{ __('Go to Generator') }} <i class="fa-solid fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Research Proposals Section -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between px-1">
                            <h2 class="text-xs font-black text-gray-400 uppercase tracking-widest">{{ __('Draft Reports') }}
                            </h2>
                            <span
                                class="text-[10px] font-bold bg-violet-100 text-violet-700 px-2 py-0.5 rounded-full">{{ count($proposals) }}
                                {{ __('Items') }}</span>
                        </div>

                        <div class="space-y-3">
                            @forelse($proposals as $prop)
                                <div class="relative group">
                                    <a href="{{ route('research-proposal.show', $prop->id) }}"
                                        class="block p-5 bg-white border border-gray-100 rounded-2xl hover:border-indigo-200 hover:shadow-md transition-all shadow-sm">
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center space-x-2">
                                                <div
                                                    class="w-1.5 h-1.5 rounded-full {{ $prop->status === 'generated' ? 'bg-emerald-500' : 'bg-orange-400' }}">
                                                </div>
                                                <span
                                                    class="text-[10px] font-bold text-gray-400 uppercase tracking-tight">{{ $prop->created_at->format('M d, Y') }}</span>
                                            </div>
                                            <span
                                                class="text-[10px] font-black {{ $prop->status === 'generated' ? 'text-emerald-600' : 'text-orange-500' }} uppercase tracking-widest bg-gray-50 px-2 py-0.5 rounded-md border border-gray-100">{{ __($prop->status) }}</span>
                                        </div>
                                        <h4
                                            class="text-sm font-bold text-gray-900 group-hover:text-indigo-600 transition-colors line-clamp-1 mb-3 pr-8">
                                            {{ $prop->title }}</h4>
                                        <div class="flex items-center space-x-3">
                                            <div
                                                class="flex items-center text-[10px] font-bold text-gray-500 bg-gray-50 px-2 py-1 rounded-lg border border-gray-100">
                                                <i class="fa-solid fa-bookmark mr-1.5 text-indigo-400"></i>
                                                {{ __(strtoupper($prop->style)) }}
                                            </div>
                                            <div
                                                class="flex items-center text-[10px] font-bold text-gray-500 bg-gray-50 px-2 py-1 rounded-lg border border-gray-100">
                                                <i class="fa-solid fa-flask mr-1.5 text-violet-400"></i>
                                                {{ __(ucfirst($prop->methodology_type)) }}
                                            </div>
                                        </div>
                                    </a>

                                    <!-- Delete Action -->
                                    <form id="delete-proposal-{{ $prop->id }}"
                                        action="{{ route('research-proposal.destroy', $prop->id) }}" method="POST"
                                        class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                            onclick="if(confirm('{{ __('CRITICAL: This will permanently delete this report draft. Continue?') }}')) { document.getElementById('delete-proposal-{{ $prop->id }}').submit(); }"
                                            class="absolute top-12 right-5 m-0 w-8 h-8 rounded-lg bg-red-50 text-red-500 flex items-center justify-center hover:bg-red-600 hover:text-white transition-all opacity-0 group-hover:opacity-100 shadow-sm border border-red-100">
                                            <i class="fa-solid fa-trash-can text-[10px]"></i>
                                        </button>
                                    </form>
                                </div>
                            @empty
                                <div class="bg-white border border-gray-100 rounded-2xl p-12 text-center shadow-sm">
                                    <div
                                        class="w-12 h-12 bg-gray-50 text-gray-300 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="fa-solid fa-file-signature text-xl"></i>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-800 mb-1">{{ __('No proposals drafted') }}</h3>
                                    <p class="text-xs text-gray-500 font-medium">
                                        {{ __('Start a new proposal with the guided intake.') }}</p>
                                    <a href="{{ route('research-proposal.create') }}"
                                        class="mt-4 inline-flex items-center text-xs font-bold text-indigo-600 hover:underline">
                                        {{ __('Draft New Proposal') }} <i class="fa-solid fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #E5E7EB;
            border-radius: 10px;
        }
    </style>
@endsection