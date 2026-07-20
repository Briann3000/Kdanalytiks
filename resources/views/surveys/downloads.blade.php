@extends('surveys.hub')

@section('survey-content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8" x-data="{ 
            exports: [],
            loading: false,
            refresh() {
                this.loading = true;
                fetch('{{ route('surveys.downloads.history', $survey) }}')
                    .then(res => res.json())
                    .then(data => {
                        this.exports = data;
                        this.loading = false;
                    });
            },
            deleteExport(filename) {
                if(!confirm('{{ __('Delete this snapshot?') }}')) return;
                fetch('{{ url('surveys/' . $survey->id . '/downloads') }}/' + filename, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                }).then(() => this.refresh());
            }
        }" x-init="refresh(); setInterval(() => refresh(), 10000)">
        <!-- Export Options -->
        <div class="lg:col-span-1 space-y-8">
            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-50 bg-slate-50/50">
                    <h5 class="text-sm font-black text-gray-900 uppercase tracking-widest leading-none">{{ __('New Export') }}</h5>
                    <p class="text-[9px] text-gray-500 font-bold uppercase mt-1 tracking-wider">{{ __('Generate fresh data files') }}
                    </p>
                </div>
                <div class="p-8 space-y-4">
                    @php
                        $canPro = in_array($currentTier, ['pro', 'enterprise']);
                        $canEnterprise = ($currentTier === 'enterprise');
                    @endphp

                    <!-- CSV (Free) -->
                    <a href="{{ route('surveys.export', $survey) }}" @click="setTimeout(() => refresh(), 3000)"
                        class="flex items-center justify-between p-4 bg-emerald-50/30 border border-emerald-100 rounded-2xl group hover:bg-emerald-50 transition-all shadow-sm">
                        <div class="flex items-center">
                            <div
                                class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-emerald-600 shadow-sm mr-4 border border-emerald-50">
                                <i class="fa-solid fa-file-csv text-xl"></i>
                            </div>
                            <div>
                                <span class="block text-xs font-black text-emerald-900 uppercase tracking-tight">{{ __('CSV Spreadsheet') }}</span>
                                <span class="block text-[8px] text-emerald-600/70 font-bold uppercase tracking-wider">{{ __('Raw data dump') }}</span>
                            </div>
                        </div>
                        <i
                            class="fa-solid fa-arrow-down-long text-emerald-300 group-hover:translate-y-1 transition-transform"></i>
                    </a>

                    <!-- PDF (Free) -->
                    <a href="{{ route('surveys.export_pdf', $survey) }}" @click="setTimeout(() => refresh(), 3000)"
                        class="flex items-center justify-between p-4 bg-rose-50/30 border border-rose-100 rounded-2xl group hover:bg-rose-50 transition-all shadow-sm">
                        <div class="flex items-center">
                            <div
                                class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-rose-600 shadow-sm mr-4 border border-rose-50">
                                <i class="fa-solid fa-file-pdf text-xl"></i>
                            </div>
                            <div>
                                <span class="block text-xs font-black text-rose-900 uppercase tracking-tight">{{ __('Analytical PDF') }}</span>
                                <span class="block text-[8px] text-rose-600/70 font-bold uppercase tracking-wider">{{ __('Report & Charts') }}</span>
                            </div>
                        </div>
                        <i
                            class="fa-solid fa-arrow-down-long text-rose-300 group-hover:translate-y-1 transition-transform"></i>
                    </a>

                    <!-- XLSX (Pro) -->
                    @if($canPro)
                        <a href="{{ route('surveys.export_xlsx', $survey) }}" @click="setTimeout(() => refresh(), 3000)"
                            class="flex items-center justify-between p-4 bg-zinc-100 border border-zinc-200 rounded-2xl group hover:bg-zinc-100 transition-all shadow-sm">
                            <div class="flex items-center">
                                <div
                                    class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-[#2271b1] shadow-sm mr-4 border border-zinc-100">
                                    <i class="fa-solid fa-file-excel text-xl"></i>
                                </div>
                                <div>
                                    <span class="block text-xs font-black text-zinc-900 uppercase tracking-tight">{{ __('Excel (.xlsx)') }}</span>
                                    <span
                                        class="block text-[8px] text-[#2271b1]/70 font-bold uppercase tracking-wider">{{ __('Formatted Sheets') }}</span>
                                </div>
                            </div>
                            <i
                                class="fa-solid fa-arrow-down-long text-zinc-500 group-hover:translate-y-1 transition-transform"></i>
                        </a>
                    @else
                        <div
                            class="p-4 bg-gray-50/50 rounded-2xl border border-gray-100 relative group cursor-not-allowed opacity-70">
                            <div class="flex items-center">
                                <div
                                    class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-gray-300 shadow-sm mr-4">
                                    <i class="fa-solid fa-file-excel text-xl"></i>
                                </div>
                                <div>
                                    <span class="block text-xs font-black text-gray-400 uppercase tracking-tight">{{ __('Excel (.xlsx)') }}</span>
                                    <span
                                        class="block text-[8px] text-zinc-500 font-black uppercase tracking-widest flex items-center gap-1">
                                        <i class="fa-solid fa-lock text-[7px]"></i> {{ __('Upgrade to Pro') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- JSON (Pro) -->
                    @if($canPro)
                        <a href="{{ route('surveys.export_json', $survey) }}" @click="setTimeout(() => refresh(), 3000)"
                            class="flex items-center justify-between p-4 bg-amber-50/30 border border-amber-100 rounded-2xl group hover:bg-amber-50 transition-all shadow-sm">
                            <div class="flex items-center">
                                <div
                                    class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-amber-600 shadow-sm mr-4 border border-amber-50">
                                    <i class="fa-solid fa-code text-xl"></i>
                                </div>
                                <div>
                                    <span class="block text-xs font-black text-amber-900 uppercase tracking-tight">{{ __('JSON Archive') }}</span>
                                    <span
                                        class="block text-[8px] text-amber-600/70 font-bold uppercase tracking-wider">{{ __('Developer Data') }}</span>
                                </div>
                            </div>
                            <i
                                class="fa-solid fa-arrow-down-long text-amber-300 group-hover:translate-y-1 transition-transform"></i>
                        </a>
                    @else
                        <div
                            class="p-4 bg-gray-50/50 rounded-2xl border border-gray-100 relative group cursor-not-allowed opacity-70">
                            <div class="flex items-center">
                                <div
                                    class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-gray-300 shadow-sm mr-4">
                                    <i class="fa-solid fa-code text-xl"></i>
                                </div>
                                <div>
                                    <span class="block text-xs font-black text-gray-400 uppercase tracking-tight">{{ __('JSON Archive') }}</span>
                                    <span
                                        class="block text-[8px] text-amber-400 font-black uppercase tracking-widest flex items-center gap-1">
                                        <i class="fa-solid fa-lock text-[7px]"></i> {{ __('Upgrade to Pro') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- XML (Enterprise) -->
                    @if($canEnterprise)
                        <a href="{{ route('surveys.export_xml', $survey) }}"
                            class="flex items-center justify-between p-4 bg-slate-50/30 border border-slate-100 rounded-2xl group hover:bg-slate-50 transition-all shadow-sm">
                            <div class="flex items-center">
                                <div
                                    class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-slate-600 shadow-sm mr-4 border border-slate-50">
                                    <i class="fa-solid fa-file-code text-xl"></i>
                                </div>
                                <div>
                                    <span class="block text-xs font-black text-slate-900 uppercase tracking-tight">{{ __('XML Document') }}</span>
                                    <span
                                        class="block text-[8px] text-slate-600/70 font-bold uppercase tracking-wider">{{ __('Enterprise Sync') }}</span>
                                </div>
                            </div>
                            <i
                                class="fa-solid fa-arrow-down-long text-slate-300 group-hover:translate-y-1 transition-transform"></i>
                        </a>
                    @else
                        <div
                            class="p-4 bg-gray-50/50 rounded-2xl border border-gray-100 relative group cursor-not-allowed opacity-70">
                            <div class="flex items-center">
                                <div
                                    class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-gray-300 shadow-sm mr-4">
                                    <i class="fa-solid fa-file-code text-xl"></i>
                                </div>
                                <div>
                                    <span class="block text-xs font-black text-gray-400 uppercase tracking-tight">{{ __('XML Document') }}</span>
                                    <span
                                        class="block text-[8px] text-slate-400 font-black uppercase tracking-widest flex items-center gap-1">
                                        <i class="fa-solid fa-lock text-[7px]"></i> {{ __('Enterprise Only') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- SPSS (Enterprise) -->
                    @if($canEnterprise)
                        <a href="{{ route('surveys.export_spss', $survey) }}"
                            class="flex items-center justify-between p-4 bg-violet-50/30 border border-violet-100 rounded-2xl group hover:bg-violet-50 transition-all shadow-sm">
                            <div class="flex items-center">
                                <div
                                    class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-violet-600 shadow-sm mr-4 border border-violet-50">
                                    <i class="fa-solid fa-square-poll-vertical text-xl"></i>
                                </div>
                                <div>
                                    <span class="block text-xs font-black text-violet-900 uppercase tracking-tight">{{ __('SPSS (.sav)') }}</span>
                                    <span
                                        class="block text-[8px] text-violet-600/70 font-bold uppercase tracking-wider">{{ __('Statistical Analysis') }}</span>
                                </div>
                            </div>
                            <i
                                class="fa-solid fa-arrow-down-long text-violet-300 group-hover:translate-y-1 transition-transform"></i>
                        </a>
                    @else
                        <div
                            class="p-4 bg-gray-50/50 rounded-2xl border border-gray-100 relative group cursor-not-allowed opacity-70">
                            <div class="flex items-center">
                                <div
                                    class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-gray-300 shadow-sm mr-4">
                                    <i class="fa-solid fa-square-poll-vertical text-xl"></i>
                                </div>
                                <div>
                                    <span class="block text-xs font-black text-gray-400 uppercase tracking-tight">{{ __('SPSS (.sav)') }}</span>
                                    <span
                                        class="block text-[8px] text-violet-400 font-black uppercase tracking-widest flex items-center gap-1">
                                        <i class="fa-solid fa-lock text-[7px]"></i> {{ __('Enterprise Only') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Google Sheets (Enterprise) -->
                    @if($canEnterprise)
                        <a href="{{ route('surveys.export_google_sheets', $survey) }}"
                            class="flex items-center justify-between p-4 bg-cyan-50/30 border border-cyan-100 rounded-2xl group hover:bg-cyan-50 transition-all shadow-sm">
                            <div class="flex items-center">
                                <div
                                    class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-cyan-600 shadow-sm mr-4 border border-cyan-50">
                                    <i class="fa-brands fa-google-drive text-xl"></i>
                                </div>
                                <div>
                                    <span class="block text-xs font-black text-cyan-900 uppercase tracking-tight">{{ __('Google Sheets') }}</span>
                                    <span class="block text-[8px] text-cyan-600/70 font-bold uppercase tracking-wider">{{ __('Live Sync') }}</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-arrow-right text-cyan-300 group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    @else
                        <div
                            class="p-4 bg-gray-50/50 rounded-2xl border border-gray-100 relative group cursor-not-allowed opacity-70">
                            <div class="flex items-center">
                                <div
                                    class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-gray-300 shadow-sm mr-4">
                                    <i class="fa-brands fa-google-drive text-xl"></i>
                                </div>
                                <div>
                                    <span class="block text-xs font-black text-gray-400 uppercase tracking-tight">{{ __('Google Sheets') }}</span>
                                    <span
                                        class="block text-[8px] text-cyan-400 font-black uppercase tracking-widest flex items-center gap-1">
                                        <i class="fa-solid fa-lock text-[7px]"></i> {{ __('Enterprise Only') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <!-- Export History -->
        <div class="lg:col-span-2">
            <div
                class="bg-white rounded-[2.5rem] shadow-xl border border-gray-100 overflow-hidden min-h-[550px] flex flex-col">
                <div class="px-10 py-8 border-b border-gray-50 bg-slate-50/30 flex justify-between items-center">
                    <div>
                        <h5 class="text-base font-black text-gray-900 uppercase tracking-widest leading-none">{{ __('Export History') }}
                        </h5>
                        <p class="text-[10px] text-gray-500 font-bold uppercase mt-2 tracking-wider">{{ __('Persistent archive of all generated reports') }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span x-show="loading"
                            class="text-[10px] font-bold text-zinc-2000 animate-pulse">{{ __('Refreshing...') }}</span>
                        <span
                            class="text-[10px] font-black text-gray-400 uppercase tracking-widest bg-gray-100 px-3 py-1.5 rounded-xl border border-gray-100">
                            <span x-text="exports.length"></span> {{ __('Snapshots') }}</span>
                        <button type="button" @click="refresh()"
                            class="w-10 h-10 bg-white border border-gray-100 rounded-2xl flex items-center justify-center text-[#2271b1] hover:bg-[#2271b1] hover:text-white transition-all shadow-sm">
                            <i class="fa-solid fa-rotate text-sm" :class="loading ? 'fa-spin' : ''"></i>
                        </button>
                    </div>
                </div>

                <div class="p-8 flex-grow">
                    <template x-if="exports.length > 0">
                        <div class="overflow-x-auto rounded-[2rem] border border-gray-100 shadow-sm bg-gray-50/30">
                            <table class="min-w-full divide-y divide-gray-100">
                                <thead>
                                    <tr class="bg-white/80 backdrop-blur-sm">
                                        <th
                                            class="px-8 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                            {{ __('Snapshot Detail') }}</th>
                                        <th
                                            class="px-8 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                            {{ __('Timestamp') }}</th>
                                        <th
                                            class="px-8 py-5 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                            {{ __('Control') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="exportItem in exports" :key="exportItem.filename">
                                        <tr class="hover:bg-white transition-colors group">
                                            <td class="px-8 py-5 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div
                                                        class="w-10 h-10 rounded-xl bg-white border border-gray-100 flex items-center justify-center shadow-sm mr-4">
                                                        <i class="fa-solid text-sm" :class="{
                                                                'fa-file-csv text-emerald-500': exportItem.extension === 'CSV',
                                                                'fa-file-pdf text-rose-500': exportItem.extension === 'PDF',
                                                                'fa-file-excel text-zinc-2000': exportItem.extension === 'XLSX',
                                                                'fa-file-code text-amber-500': exportItem.extension === 'JSON',
                                                                'fa-file-lines text-blue-500': !['CSV', 'PDF', 'XLSX', 'JSON'].includes(exportItem.extension)
                                                            }"></i>
                                                    </div>
                                                    <div>
                                                        <div class="text-[11px] font-black text-gray-900 uppercase tracking-tight"
                                                            x-text="exportItem.name"></div>
                                                        <div class="text-[9px] text-gray-400 font-bold"
                                                            x-text="exportItem.size"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-8 py-5 whitespace-nowrap">
                                                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest"
                                                    x-text="exportItem.date"></span>
                                            </td>
                                            <td class="px-8 py-5 whitespace-nowrap text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a :href="exportItem.download_url"
                                                        class="w-8 h-8 rounded-lg bg-zinc-100 text-[#2271b1] flex items-center justify-center hover:bg-[#2271b1] hover:text-white transition-all shadow-sm">
                                                        <i class="fa-solid fa-download text-[10px]"></i>
                                                    </a>
                                                    <button @click="deleteExport(exportItem.filename)"
                                                        class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all shadow-sm">
                                                        <i class="fa-solid fa-trash-can text-[10px]"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>

                    <template x-if="exports.length === 0">
                        <div
                            class="flex flex-col items-center justify-center py-20 bg-gray-50/50 rounded-[2rem] border border-dashed border-gray-200">
                            <div
                                class="w-20 h-20 bg-white rounded-3xl flex items-center justify-center text-gray-200 shadow-sm mb-6">
                                <i class="fa-solid fa-cloud-arrow-down text-4xl"></i>
                            </div>
                            <h6 class="text-xs font-black text-gray-400 uppercase tracking-widest">{{ __('No Snapshots Found') }}</h6>
                            <p class="text-[9px] text-gray-400 font-bold uppercase mt-2">{{ __('Generate your first report to start history') }}</p>
                        </div>
                    </template>
                </div>

                <div class="px-10 py-6 bg-gray-50/50 border-t border-gray-50 mt-auto">
                    <div class="flex items-center gap-2 text-zinc-500">
                        <i class="fa-solid fa-circle-info text-xs"></i>
                        <p class="text-[9px] font-bold uppercase tracking-widest">{{ __('Exports are retained for 30 days unless manually purged') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection