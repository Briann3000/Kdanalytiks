@extends('layouts.app')

@section('title', __('Proposal History — KDAnalytiks'))

@section('content')
    <div class="h-full flex flex-col overflow-hidden bg-white text-gray-800">
        <!-- Header -->
        <div class="h-14 border-b border-gray-100 flex items-center justify-between px-6 flex-shrink-0 bg-white">
            <div class="flex items-center space-x-3">
                <h1 class="text-sm font-bold text-gray-900 tracking-tight">{{ __('Proposal History') }}</h1>
            </div>
            <div class="flex items-center space-x-4">
                <a href="{{ route('research-proposal.create') }}"
                    class="text-xs font-bold text-gray-500 hover:text-[#2271b1] transition-colors">
                    <i class="fa-solid fa-plus mr-1"></i> {{ __('Draft New Proposal') }}
                </a>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto bg-gray-50/30 p-8 custom-scrollbar">
            <div class="max-w-6xl mx-auto space-y-8">
                <!-- Research Proposals Section -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between px-1">
                        <h2 class="text-sm font-black text-gray-400 tracking-widest">{{ __('Draft Proposals') }}</h2>
                        <span class="text-[10px] font-bold bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">
                            {{ count($proposals) }} {{ __('Items') }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @forelse($proposals as $prop)
                            <div class="relative group">
                                <a href="{{ route('research-proposal.show', $prop->id) }}"
                                    class="block bg-white border border-gray-100 rounded-2xl p-5 hover:border-zinc-300 hover:shadow-xl transition-all h-full">
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <span class="text-[9px] font-bold text-gray-400 tracking-widest block mb-1">
                                                {{ $prop->created_at->format('M d, Y') }}
                                            </span>
                                            <h3
                                                class="text-sm font-black text-gray-900 line-clamp-1 group-hover:text-[#2271b1] transition-colors">
                                                {{ $prop->title }}
                                            </h3>
                                        </div>
                                    </div>

                                    <p class="text-xs text-gray-500 font-medium line-clamp-2 mb-4 leading-relaxed">
                                        {{ $prop->research_question }}
                                    </p>

                                    <div class="flex items-center space-x-2">
                                        <div
                                            class="flex items-center text-[10px] font-bold text-gray-500 bg-gray-50 px-2 py-1 rounded-lg border border-gray-100">
                                            <i class="fa-solid fa-graduation-cap mr-1.5 text-blue-400"></i>
                                            {{ __(ucfirst($prop->methodology_type)) }}
                                        </div>
                                        <div
                                            class="flex items-center text-[10px] font-bold text-gray-500 bg-gray-50 px-2 py-1 rounded-lg border border-gray-100">
                                            {{ strtoupper($prop->style) }}
                                        </div>
                                    </div>
                                </a>

                                <!-- Delete Action & Quick Actions -->
                                <div class="absolute top-4 right-4 flex items-center gap-1.5">
                                    <a href="{{ route('research-proposal.preview', $prop->id) }}"
                                        class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all shadow-sm border border-indigo-100"
                                        title="{{ __('Preview Proposal') }}">
                                        <i class="fa-solid fa-eye text-[10px]"></i>
                                    </a>
                                    <form id="delete-proposal-{{ $prop->id }}"
                                        action="{{ route('research-proposal.destroy', $prop->id) }}" method="POST"
                                        class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                            onclick="if(confirm('{{ __('CRITICAL: This will permanently delete this proposal draft. Continue?') }}')) { document.getElementById('delete-proposal-{{ $prop->id }}').submit(); }"
                                            class="w-8 h-8 rounded-lg bg-red-50 text-red-500 flex items-center justify-center hover:bg-red-600 hover:text-white transition-all shadow-sm border border-red-100"
                                            title="{{ __('Delete Proposal') }}">
                                            <i class="fa-solid fa-trash-can text-[10px]"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-2 bg-white border border-gray-100 rounded-2xl p-12 text-center shadow-sm">
                                <div
                                    class="w-12 h-12 bg-gray-50 text-gray-300 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fa-solid fa-file-signature text-xl"></i>
                                </div>
                                <h3 class="text-sm font-bold text-gray-800 mb-1">{{ __('No proposals drafted yet') }}</h3>
                                <p class="text-xs text-gray-500 font-medium">
                                    {{ __('Start a new proposal with the Research Proposal Studio.') }}
                                </p>
                                <a href="{{ route('research-proposal.create') }}"
                                    class="mt-4 inline-flex items-center px-4 py-2 bg-[#2271b1] text-white rounded-xl text-xs font-bold hover:bg-[#135e96] transition-all">
                                    {{ __('Go to Proposal Studio') }} <i class="fa-solid fa-arrow-right ml-2"></i>
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection