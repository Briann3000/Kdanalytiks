@extends('surveys.project_hub')

@section('project-content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8" x-data="{ deleting: null }">
    <!-- Export Options -->
    <div class="lg:col-span-1 space-y-8">
        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
            <div class="px-8 py-6 border-b border-gray-50 bg-slate-50/50">
                <h5 class="text-sm font-black text-gray-900 uppercase tracking-widest leading-none">New Export</h5>
                <p class="text-[9px] text-gray-500 font-bold uppercase mt-1 tracking-wider">Generate fresh data files</p>
            </div>
            <div class="p-8 space-y-4">
                <a href="{{ route('surveys.export', $survey) }}" class="flex items-center justify-between p-5 bg-emerald-50/50 border border-emerald-100 rounded-[2rem] group hover:bg-emerald-50 transition-all shadow-sm">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-emerald-600 shadow-sm mr-4 border border-emerald-50">
                            <i class="fa-solid fa-file-csv text-2xl"></i>
                        </div>
                        <div>
                            <span class="block text-sm font-black text-emerald-900 uppercase tracking-tight">CSV Spreadsheet</span>
                            <span class="block text-[9px] text-emerald-600/70 font-bold uppercase tracking-wider">Raw data dump</span>
                        </div>
                    </div>
                    <i class="fa-solid fa-arrow-down-long text-emerald-300 group-hover:translate-y-1 transition-transform"></i>
                </a>

                <a href="{{ route('surveys.export_pdf', $survey) }}" class="flex items-center justify-between p-5 bg-rose-50/50 border border-rose-100 rounded-[2rem] group hover:bg-rose-50 transition-all shadow-sm">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-rose-600 shadow-sm mr-4 border border-rose-50">
                            <i class="fa-solid fa-file-pdf text-2xl"></i>
                        </div>
                        <div>
                            <span class="block text-sm font-black text-rose-900 uppercase tracking-tight">Analytical PDF</span>
                            <span class="block text-[9px] text-rose-600/70 font-bold uppercase tracking-wider">Report & Charts</span>
                        </div>
                    </div>
                    <i class="fa-solid fa-arrow-down-long text-rose-300 group-hover:translate-y-1 transition-transform"></i>
                </a>
                
                <div class="p-5 bg-gray-50/50 rounded-[2rem] border border-gray-100 relative group cursor-not-allowed overflow-hidden">
                    <div class="flex items-center border-dashed border-2 border-gray-200 rounded-3xl p-1 opacity-60">
                         <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-gray-300 shadow-sm mr-4">
                            <i class="fa-solid fa-file-excel text-xl"></i>
                        </div>
                        <div>
                            <span class="block text-xs font-black text-gray-400 uppercase tracking-tight">Excel (.xlsx)</span>
                            <span class="block text-[8px] text-gray-400 font-black uppercase tracking-widest">Enabling Soon</span>
                        </div>
                    </div>
                    <div class="absolute inset-0 bg-white/40 backdrop-blur-[1px] flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                         <span class="text-[8px] font-black uppercase bg-gray-900 text-white px-2 py-1 rounded-md tracking-[0.2em] shadow-xl">Under Development</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-indigo-900 to-slate-900 rounded-[3rem] p-10 shadow-2xl text-white relative overflow-hidden border border-white/5">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-indigo-500/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-emerald-500/10 rounded-full blur-3xl"></div>
            
            <div class="relative z-10">
                <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center mb-6 border border-white/10">
                    <i class="fa-solid fa-vault text-xl text-indigo-300"></i>
                </div>
                <h4 class="text-xl font-black mb-3 tracking-tighter">Compliant Storage</h4>
                <p class="text-indigo-100/70 text-[11px] font-medium leading-relaxed mb-8">
                    Every generated export is cryptographically hashed and stored in your project's secure archive for audit persistence.
                </p>
                <div class="flex items-center gap-3">
                    <div class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></div>
                    <span class="text-[9px] font-black uppercase tracking-[0.3em] text-emerald-400">System Secure</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Export History -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-[2.5rem] shadow-xl border border-gray-100 overflow-hidden min-h-[550px] flex flex-col">
            <div class="px-10 py-8 border-b border-gray-50 bg-slate-50/30 flex justify-between items-center">
                <div>
                    <h5 class="text-base font-black text-gray-900 uppercase tracking-widest leading-none">Export History</h5>
                    <p class="text-[10px] text-gray-500 font-bold uppercase mt-2 tracking-wider">Persistent archive of all generated reports</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest bg-gray-100 px-3 py-1.5 rounded-xl border border-gray-100">{{ count($exports) }} Snapshots</span>
                    <button type="button" @click="window.location.reload()" class="w-10 h-10 bg-white border border-gray-100 rounded-2xl flex items-center justify-center text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                        <i class="fa-solid fa-rotate text-sm"></i>
                    </button>
                </div>
            </div>

            <div class="p-8 flex-grow">
                @if(count($exports) > 0)
                    <div class="overflow-x-auto rounded-[2rem] border border-gray-100 shadow-sm bg-gray-50/30">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead>
                                <tr class="bg-white/80 backdrop-blur-sm">
                                    <th class="px-8 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Snapshot Detail</th>
                                    <th class="px-8 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Timestamp</th>
                                    <th class="px-8 py-5 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Control</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($exports as $export)
                                    <tr class="hover:bg-white transition-colors group" x-data="{ confirming: false }" x-show="deleting !== '{{ $export['filename'] }}'">
                                        <td class="px-8 py-5">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 {{ $export['extension'] === 'pdf' ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600' }} rounded-xl flex items-center justify-center mr-4 border border-white shadow-sm">
                                                    <i class="fa-solid {{ $export['extension'] === 'pdf' ? 'fa-file-pdf' : 'fa-file-csv' }} text-lg"></i>
                                                </div>
                                                <div>
                                                    <span class="block text-xs font-black text-gray-900 truncate max-w-[240px] tracking-tight group-hover:text-indigo-600 transition-colors">{{ $export['name'] }}</span>
                                                    <div class="flex items-center gap-2 mt-0.5">
                                                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-tighter">{{ strtoupper($export['extension']) }} ARCHIVE</span>
                                                        <span class="w-1 h-1 rounded-full bg-gray-200"></span>
                                                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-tighter">{{ $export['size'] }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-8 py-5">
                                            <div class="flex flex-col">
                                                <span class="text-[11px] font-black text-gray-700 leading-none mb-1">{{ $export['date'] }}</span>
                                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-[0.2em] scale-90 origin-left">Persistent Snapshot</span>
                                            </div>
                                        </td>
                                        <td class="px-8 py-5 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ asset('storage/exports/' . $survey->id . '/' . $export['filename']) }}" 
                                                   download class="w-9 h-9 bg-indigo-600 text-white rounded-xl flex items-center justify-center hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                                                    <i class="fa-solid fa-download text-xs"></i>
                                                </a>
                                                <div class="relative">
                                                     <button type="button" @click="confirming = true" x-show="!confirming"
                                                        class="w-9 h-9 bg-gray-100 text-gray-400 rounded-xl flex items-center justify-center hover:bg-rose-50 hover:text-rose-600 transition-all">
                                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                                    </button>
                                                    <div x-show="confirming" @click.away="confirming = false" class="flex items-center gap-1 bg-rose-600 p-1 rounded-xl shadow-xl animate-in fade-in zoom-in duration-200">
                                                        <button @click="
                                                            fetch('{{ route('projects.downloads.delete', ['survey' => $survey->id, 'filename' => $export['filename']]) }}', {
                                                                method: 'DELETE',
                                                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                                                            }).then(r => { if(r.ok) deleting = '{{ $export['filename'] }}' })
                                                        " class="px-2 py-1 text-[9px] font-black text-white uppercase hover:bg-rose-700 rounded-lg">YES</button>
                                                        <div class="w-px h-3 bg-white/20"></div>
                                                        <button @click="confirming = false" class="px-2 py-1 text-[9px] font-black text-white uppercase hover:bg-rose-700 rounded-lg">NO</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-32 text-center">
                        <div class="w-24 h-24 bg-gray-50 rounded-[2.5rem] flex items-center justify-center mb-8 border border-gray-100 shadow-inner group">
                            <i class="fa-solid fa-clock-rotate-left text-5xl text-gray-200 group-hover:text-indigo-200 transition-colors"></i>
                        </div>
                        <h3 class="text-xl font-black text-gray-900 uppercase tracking-[0.2em] ml-2">Archive Vacant</h3>
                        <p class="text-[11px] text-gray-500 font-medium max-w-xs mt-4 leading-relaxed">
                            Your project's persistent download vault is currently empty. Start by generating a CSV or PDF export.
                        </p>
                    </div>
                @endif
            </div>
            
            <div class="px-10 py-6 bg-gray-50/50 border-t border-gray-50 mt-auto">
                <div class="flex items-center gap-2 text-indigo-400">
                    <i class="fa-solid fa-circle-info text-xs"></i>
                    <p class="text-[9px] font-bold uppercase tracking-widest">Exports are retained for 30 days unless manually purged</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
