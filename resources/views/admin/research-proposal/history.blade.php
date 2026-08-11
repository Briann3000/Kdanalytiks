@extends('layouts.app')

@section('title', __('Proposals Library — KDAnalytiks'))

@section('content')
    <div class="h-full flex flex-col overflow-hidden bg-white text-gray-800" x-data="{ showUploadModal: false }">
        <!-- Header -->
        <div class="h-14 border-b border-gray-100 flex items-center justify-between px-6 flex-shrink-0 bg-white">
            <div class="flex items-center space-x-3">
                <h1 class="text-sm font-bold text-gray-900 tracking-tight">{{ __('Proposals Library') }}</h1>
            </div>
            <div class="flex items-center space-x-3">
                <button type="button" @click="showUploadModal = true"
                    class="px-3.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-file-arrow-up text-xs"></i> {{ __('Upload Proposal File') }}
                </button>
                <a href="{{ route('research-proposal.create') }}"
                    class="px-4 py-1.5 bg-[#2271b1] hover:bg-[#135e96] text-white text-xs font-bold rounded-xl transition-all flex items-center gap-1.5 shadow-xs">
                    <i class="fa-solid fa-robot text-xs"></i> {{ __('Draft Proposal (Socius AI)') }}
                </a>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto bg-gray-50/30 p-8 custom-scrollbar">
            <div class="max-w-6xl mx-auto space-y-8">
                <!-- Research Proposals Section -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between px-1">
                        <h2 class="text-sm font-black text-gray-400 tracking-widest">{{ __('Saved & Uploaded Proposals') }}
                        </h2>
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
                                        {{ $prop->research_question ?? __('Saved Proposal Document') }}
                                    </p>

                                    <div class="flex items-center space-x-2">
                                        <div
                                            class="flex items-center text-[10px] font-bold text-gray-500 bg-gray-50 px-2 py-1 rounded-lg border border-gray-100">
                                            <i class="fa-solid fa-graduation-cap mr-1.5 text-blue-400"></i>
                                            {{ __(ucfirst($prop->methodology_type ?? 'Standard')) }}
                                        </div>
                                        <div
                                            class="flex items-center text-[10px] font-bold text-gray-500 bg-gray-50 px-2 py-1 rounded-lg border border-gray-100">
                                            {{ strtoupper($prop->style ?? 'APA7') }}
                                        </div>
                                    </div>
                                </a>

                                <!-- Delete Action & Quick Actions -->
                                <div class="absolute top-4 right-4 flex items-center gap-1.5">
                                    <a href="{{ route('research-proposal.export-proposal', ['id' => $prop->id]) }}"
                                        class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center hover:bg-emerald-600 hover:text-white transition-all shadow-sm border border-emerald-100"
                                        title="{{ __('Download DOCX') }}">
                                        <i class="fa-solid fa-download text-[10px]"></i>
                                    </a>
                                    <form id="delete-proposal-{{ $prop->id }}"
                                        action="{{ route('research-proposal.destroy', $prop->id) }}" method="POST"
                                        class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                            onclick="if(confirm('{{ __('CRITICAL: This will permanently delete this proposal. Continue?') }}')) { document.getElementById('delete-proposal-{{ $prop->id }}').submit(); }"
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
                                <h3 class="text-sm font-bold text-gray-800 mb-1">{{ __('No proposals in your Library') }}</h3>
                                <p class="text-xs text-gray-500 font-medium">
                                    {{ __('Draft a proposal with Socius AI or upload an existing file.') }}
                                </p>
                                <div class="mt-4 flex items-center justify-center gap-3">
                                    <a href="{{ route('research-proposal.create') }}"
                                        class="inline-flex items-center px-4 py-2 bg-[#2271b1] text-white rounded-xl text-xs font-bold hover:bg-[#135e96] transition-all">
                                        <i class="fa-solid fa-robot mr-2"></i> {{ __('Draft with Socius AI') }}
                                    </a>
                                    <button type="button" @click="showUploadModal = true"
                                        class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-200 transition-all">
                                        <i class="fa-solid fa-file-arrow-up mr-2"></i> {{ __('Upload Proposal File') }}
                                    </button>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Proposal Modal -->
        <div x-show="showUploadModal" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-6"
                @click.away="showUploadModal = false">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-black text-gray-900">{{ __('Upload Existing Proposal') }}</h3>
                    <button type="button" @click="showUploadModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form action="{{ route('research-proposal.upload') }}" method="POST" enctype="multipart/form-data"
                    class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">{{ __('Proposal Title') }}</label>
                        <input type="text" name="title" required
                            placeholder="{{ __('e.g., Socio-Economic Impact Study Proposal') }}"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-xs font-bold text-gray-800 focus:ring-2 focus:ring-[#2271b1]">
                    </div>

                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 mb-1">{{ __('Select File (.docx, .pdf, .txt)') }}</label>
                        <input type="file" name="file" accept=".docx,.pdf,.txt" required
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2 text-xs font-medium text-gray-800 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#2271b1] file:text-white hover:file:bg-[#135e96]">
                    </div>

                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" @click="showUploadModal = false"
                            class="px-4 py-2 text-xs font-bold text-gray-500 hover:text-gray-700">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-[#2271b1] text-white text-xs font-bold rounded-xl hover:bg-[#135e96] transition-all">
                            {{ __('Upload & Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection