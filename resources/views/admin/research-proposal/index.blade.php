@extends('layouts.app')

@section('title', 'Research Proposal Studio')

@section('content')
<div class="container-fluid px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Academic Research Proposal Studio</h1>
            <p class="text-gray-600">Transform your survey data into formal academic sections using AI-powered synthesis.</p>
        </header>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-8">
                <form action="{{ route('research-proposal.generate') }}" method="POST">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                        <div>
                            <label for="survey_id" class="block text-sm font-semibold text-gray-700 mb-2">Source Survey</label>
                            <select name="survey_id" id="survey_id" class="w-full rounded-xl border-gray-200 focus:ring-primary-500 focus:border-primary-500 transition-all duration-200" required>
                                <option value="" disabled selected>Select a survey...</option>
                                @foreach($surveys as $survey)
                                    <option value="{{ $survey->id }}">{{ $survey->title }}</option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-gray-500">The AI will use responses from this survey to draft your proposal.</p>
                        </div>

                        <div>
                            <label for="style" class="block text-sm font-semibold text-gray-700 mb-2">Academic Referencing Style</label>
                            <select name="style" id="style" class="w-full rounded-xl border-gray-200 focus:ring-primary-500 focus:border-primary-500 transition-all duration-200" required>
                                <option value="apa7">APA 7th Edition</option>
                                <option value="mla9">MLA 9th Edition</option>
                                <option value="harvard">Harvard Style</option>
                            </select>
                            <p class="mt-2 text-xs text-gray-500">Formatting and citations will follow this standard.</p>
                        </div>
                    </div>

                    <div class="mb-8 p-6 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                        <h3 class="text-md font-semibold text-gray-800 mb-4">Output Format</h3>
                        <div class="flex items-center space-x-6">
                            <label class="flex items-center cursor-pointer group">
                                <input type="radio" name="format" value="pdf" class="hidden peer" checked>
                                <div class="w-12 h-12 flex items-center justify-center rounded-lg border-2 border-gray-200 peer-checked:border-primary-600 peer-checked:bg-primary-50 transition-all group-hover:bg-gray-100">
                                    <span class="font-bold text-xs">PDF</span>
                                </div>
                                <span class="ml-3 text-sm font-medium text-gray-700">Adobe PDF</span>
                            </label>

                            <label class="flex items-center cursor-pointer group">
                                <input type="radio" name="format" value="docx" class="hidden peer">
                                <div class="w-12 h-12 flex items-center justify-center rounded-lg border-2 border-gray-200 peer-checked:border-primary-600 peer-checked:bg-primary-50 transition-all group-hover:bg-gray-100">
                                    <span class="font-bold text-xs">DOCX</span>
                                </div>
                                <span class="ml-3 text-sm font-medium text-gray-700">Microsoft Word</span>
                            </label>
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

                    <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-100">
                        <button type="button" class="px-6 py-2.5 rounded-xl text-sm font-semibold text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-all">
                            Cancel
                        </button>
                        <button type="submit" class="px-8 py-2.5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md transition-all flex items-center">
                            <span class="mr-2">Generate Proposal</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-6 bg-indigo-50 rounded-2xl border border-indigo-100">
                <div class="text-indigo-600 mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.364-6.364l-.707-.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                <h4 class="font-bold text-gray-900 mb-2">Smart Synthesis</h4>
                <p class="text-sm text-gray-600 line-clamp-3">Automatically creates Methodology, Results, and Discussion sections from raw survey data.</p>
            </div>
            
            <div class="p-6 bg-blue-50 rounded-2xl border border-blue-100">
                <div class="text-blue-600 mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <h4 class="font-bold text-gray-900 mb-2">Peer-Ready</h4>
                <p class="text-sm text-gray-600 line-clamp-3">Built-in support for APA 7th, MLA 9th, and Harvard formatting standards.</p>
            </div>

            <div class="p-6 bg-emerald-50 rounded-2xl border border-emerald-100">
                <div class="text-emerald-600 mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                    </svg>
                </div>
                <h4 class="font-bold text-gray-900 mb-2">Export Anywhere</h4>
                <p class="text-sm text-gray-600 line-clamp-3">One-click export to high-quality PDF or editable Word (DOCX) formats.</p>
            </div>
        </div>
    </div>
</div>
@endsection
