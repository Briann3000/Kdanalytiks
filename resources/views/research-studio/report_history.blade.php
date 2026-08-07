@extends('layouts.app')

@section('title', __('Report History — Research Studio'))

@section('content')
    <div class="container-fluid px-4 md:px-8 py-8">
        <div class="max-w-7xl mx-auto">
            <header class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-xs font-black text-[#2271b1] tracking-widest">{{ __('Research Studio') }}</span>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight mt-1">{{ __('Report History') }}
                    </h1>
                    <p class="text-gray-500 font-medium mt-1">
                        {{ __('List of generated reports.') }}
                    </p>
                </div>
                <a href="{{ route('research-studio.report.create') }}"
                    class="px-6 py-3 bg-[#2271b1] hover:bg-blue-500 text-white font-bold rounded-2xl shadow-md transition-all text-xs tracking-wide">
                    {{ __('Generate New Report') }}
                </a>
            </header>

            @if($reports->isEmpty())
                <div class="bg-white rounded-3xl border border-gray-100 p-12 text-center shadow-xs">
                    <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-file-invoice text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-1">{{ __('No reports generated yet') }}</h3>
                    <p class="text-gray-400 text-sm mb-6">
                        {{ __('Compile Chapters 1-5 with descriptive and inferential analysis findings.') }}
                    </p>
                    <a href="{{ route('research-studio.report.create') }}"
                        class="inline-flex px-5 py-2.5 bg-blue-50 text-[#2271b1] hover:bg-blue-100 font-bold rounded-xl text-xs transition-colors">
                        {{ __('Start synthesis') }}
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($reports as $report)
                        <div
                            class="bg-white rounded-3xl border border-gray-100 p-6 flex flex-col justify-between hover:shadow-md transition-all shadow-xs">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-[10px] font-black blue px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        {{ ucfirst($report->status) }}
                                    </span>
                                    <span
                                        class="text-xs text-gray-400 font-semibold">{{ $report->created_at->format('M d, Y') }}</span>
                                </div>
                                <div>
                                    <h3 class="text-base font-black text-gray-900 leading-snug break-words">{{ $report->title }}
                                    </h3>
                                    <p class="text-xs text-gray-500 font-semibold mt-1">
                                        {{ __('Linked Survey:') }} {{ $report->survey ? $report->survey->title : __('None') }}
                                    </p>
                                </div>
                            </div>
                            <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between gap-2 flex-wrap">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('research-studio.report.preview', $report->id) }}"
                                        class="flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                        {{ __('Preview') }}
                                    </a>
                                    <a href="{{ route('research-studio.report.download', $report->id) }}"
                                        class="flex items-center gap-1.5 text-xs font-bold text-[#2271b1] hover:text-blue-500 transition-colors">
                                        <i class="fa-solid fa-download text-xs"></i>
                                        {{ __('DOCX') }}
                                    </a>
                                </div>

                                <form id="delete-report-{{ $report->id }}"
                                    action="{{ route('research-studio.report.destroy', $report->id) }}" method="POST"
                                    class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                        onclick="if(confirm('{{ __('Delete this compiled report?') }}')) { document.getElementById('delete-report-{{ $report->id }}').submit(); }"
                                        class="w-7 h-7 rounded-lg bg-red-50 text-red-500 hover:bg-red-600 hover:text-white transition-all flex items-center justify-center border border-red-100"
                                        title="{{ __('Delete Report') }}">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection