@extends('layouts.app')

@section('title', 'Research Studio - Report Generator')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-white text-gray-800">
    <!-- Header -->
    <div class="h-14 border-b border-gray-100 flex items-center justify-between px-6 flex-shrink-0 bg-white">
        <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-sm">
                <i class="fa-solid fa-microscope text-sm"></i>
            </div>
            <h1 class="text-sm font-bold text-gray-900 tracking-tight">Research Studio: Report Generator</h1>
        </div>
        <div class="flex items-center space-x-4">
            <a href="{{ route('research-proposal.history') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 transition-colors">
                <i class="fa-solid fa-clock-rotate-left mr-1"></i> View History
            </a>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto bg-gray-50/30 p-8 custom-scrollbar">
        <div class="max-w-4xl mx-auto space-y-8">
            <!-- Main Generator Card -->
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
                <div class="h-12 border-b border-gray-100 px-6 flex items-center bg-gray-50/50">
                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Report Configuration</span>
                </div>
                
                <div class="p-8">
                    <form action="{{ route('research-proposal.generate') }}" method="POST" class="space-y-8">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-6">
                                <div>
                                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 block">Source Survey</label>
                                    <select name="survey_id" required class="w-full h-12 bg-gray-50 border-gray-100 rounded-xl px-4 text-sm font-medium text-gray-800 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all cursor-pointer">
                                        <option value="" disabled selected>Select active survey...</option>
                                        @foreach($surveys as $survey)
                                            <option value="{{ $survey->id }}">{{ $survey->title }}</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-2 text-[11px] text-gray-400 italic font-medium px-1">Selected data will be processed through the qualitative analysis pipeline.</p>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 block">Academic Style</label>
                                        <select name="style" class="w-full h-11 bg-gray-50 border-gray-100 rounded-xl px-4 text-sm font-medium text-gray-800 outline-none focus:ring-2 focus:ring-indigo-500/20">
                                            <option value="apa7">APA 7th</option>
                                            <option value="mla9">MLA 9th</option>
                                            <option value="harvard">Harvard</option>
                                            <option value="chicago">Chicago/Turabian</option>
                                            <option value="ieee">IEEE</option>
                                            <option value="vancouver">Vancouver</option>
                                            <option value="oscola">OSCOLA</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 block">Export Format</label>
                                        <select name="format" class="w-full h-11 bg-gray-50 border-gray-100 rounded-xl px-4 text-sm font-medium text-gray-800 outline-none focus:ring-2 focus:ring-indigo-500/20">
                                            <option value="pdf">PDF Doc</option>
                                            <option value="docx">Word (.docx)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-6 border-l border-gray-100 pl-8">
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider px-1">Synthesis Parameters</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center p-4 bg-gray-50 border border-transparent rounded-xl hover:border-indigo-100 transition-all">
                                        <div class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fa-solid fa-microchip text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-800">Thematic Analysis</p>
                                            <p class="text-[10px] text-gray-400 font-medium">Auto-identifying recurring research themes</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center p-4 bg-gray-50 border border-transparent rounded-xl hover:border-indigo-100 transition-all">
                                        <div class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fa-solid fa-feather-pointed text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-800">Citations Engine</p>
                                            <p class="text-[10px] text-gray-400 font-medium">Generating in-text academic references</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reference Manager -->
                        <div class="mb-8" x-data="{ 
                            references: [],
                            addReference() {
                                this.references.push({ author: '', year: '', title: '', source: '' });
                            },
                            removeReference(index) {
                                this.references.splice(index, 1);
                            }
                        }">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Reference Manager</h3>
                                <button type="button" @click="addReference()" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-bold hover:bg-indigo-100 transition-colors">
                                    <i class="fa-solid fa-plus mr-2"></i> Add Source
                                </button>
                            </div>
                            
                            <div class="space-y-4">
                                <template x-for="(ref, index) in references" :key="index">
                                    <div class="p-4 bg-white border border-gray-100 rounded-xl shadow-sm relative group">
                                        <button type="button" @click="removeReference(index)" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                            <i class="fa-solid fa-xmark text-[10px]"></i>
                                        </button>
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                            <div>
                                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Author</label>
                                                <input type="text" :name="'references['+index+'][author]'" x-model="ref.author" placeholder="e.g. Smith, J." class="w-full text-xs rounded-lg border-gray-100 focus:ring-indigo-500 focus:border-indigo-500">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Year</label>
                                                <input type="text" :name="'references['+index+'][year]'" x-model="ref.year" placeholder="2024" class="w-full text-xs rounded-lg border-gray-100 focus:ring-indigo-500 focus:border-indigo-500">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Title</label>
                                                <input type="text" :name="'references['+index+'][title]'" x-model="ref.title" placeholder="Title of the work" class="w-full text-xs rounded-lg border-gray-100 focus:ring-indigo-500 focus:border-indigo-500">
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Source / Publisher / DOI</label>
                                            <input type="text" :name="'references['+index+'][source]'" x-model="ref.source" placeholder="Journal Name, Book Publisher, etc." class="w-full text-xs rounded-lg border-gray-100 focus:ring-indigo-500 focus:border-indigo-500">
                                        </div>
                                    </div>
                                </template>
                                <div x-show="references.length === 0" class="text-center py-8 border-2 border-dashed border-gray-100 rounded-2xl">
                                    <p class="text-xs text-gray-400 font-medium italic">No manual references added. AI will rely on general knowledge.</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center justify-between sm:justify-end space-y-4 sm:space-y-0 sm:space-x-4 pt-6 border-t border-gray-100">
                            <div class="flex items-center space-x-2 text-gray-400 w-full sm:w-auto justify-center sm:justify-start">
                                <i class="fa-solid fa-shield-halved text-sm"></i>
                                <span class="text-[10px] font-bold uppercase tracking-widest">Secure Processing</span>
                            </div>
                            <button type="submit" class="w-full sm:w-auto min-h-[48px] px-10 bg-indigo-600 text-white rounded-xl font-bold text-sm shadow-md shadow-indigo-100 hover:bg-indigo-700 hover:shadow-lg transition-all transform active:scale-95 flex items-center justify-center border-none">
                                <span class="whitespace-nowrap">Generate Full Report</span> <i class="fa-solid fa-arrow-right ml-2 bg-transparent text-white border-0"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Contextual Help / Tips -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-6 bg-indigo-50 border border-indigo-100 rounded-2xl">
                    <i class="fa-solid fa-lightbulb text-indigo-600 mb-3 block"></i>
                    <h5 class="text-xs font-bold text-indigo-900 uppercase tracking-wider mb-2">Pro Tip</h5>
                    <p class="text-xs text-indigo-700 leading-relaxed font-medium">
                        Ensure your survey has at least 5 qualitative responses for the best thematic results.
                    </p>
                </div>
                <!-- Add more if needed -->
            </div>
        </div>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #E5E7EB; border-radius: 10px; }
</style>
@endsection
