@extends('layouts.app')

@push('styles')
    <style>
        /* Hide the global footer for the builder to maximize canvas space */
        footer { display: none !important; }
        .content-pane { padding-bottom: 0 !important; }
    </style>
    <link rel="stylesheet" href="https://formbuilder.online/assets/css/form-render.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        /* Custom overrides for the form builder to look more like Tailwind */
        .form-wrap.form-builder .frmb-control li {
            border-radius: 0.375rem;
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .form-wrap.form-builder .frmb-control li:hover {
            background-color: #e5e7eb;
            border-color: #4f46e5;
        }

        .form-wrap.form-builder .stage-wrap {
            border: 2px dashed #d1d5db;
            border-radius: 0.5rem;
            background-color: #f9fafb;
        }

        /* Full Screen Preview Modal Fixes */
        #previewModal {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            z-index: 99999 !important;
            background-color: rgba(17, 24, 39, 0.95);
            backdrop-filter: blur(8px);
        }

        /* Preview specific improvements */
        .preview-input {
            border: 2px solid #d1d5db !important;
            border-radius: 0.75rem !important;
            padding: 0.75rem 1rem !important;
            width: 100% !important;
            transition: all 0.2s ease !important;
            background-color: #ffffff !important;
            color: #111827 !important;
            font-weight: 600 !important;
        }
        .preview-input:focus {
            border-color: #4f46e5 !important;
            background-color: #fff !important;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1) !important;
            outline: none !important;
        }
        .fb-render .form-group {
            margin-bottom: 2.5rem !important;
            padding: 2rem !important;
            background: white !important;
            border-radius: 1.5rem !important;
            border: 1px solid #f3f4f6 !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
        }
        .fb-render label {
            font-weight: 800 !important;
            color: #111827 !important;
            font-size: 1.1rem !important;
            margin-bottom: 1rem !important;
            display: block !important;
            text-transform: none !important;
            letter-spacing: -0.025em !important;
        }
        .drag-handle { cursor: grab; }
        .drag-handle:active { cursor: grabbing; }
        .sortable-ghost { opacity: 0.4; background: #eef2ff !important; border: 2px dashed #4f46e5 !important; }
        .sortable-chosen { background: #fdfdfd; }

        /* Preview Group Styling */
        .preview-group-header {
            margin-top: 2.5rem !important;
            margin-bottom: 1.5rem !important;
            padding: 1.25rem !important;
            background-color: #fff1f2 !important; /* rose-50 */
            border: 1px solid #ffe4e6 !important; /* rose-100 */
            border-radius: 1.25rem !important;
            color: #e11d48 !important; /* rose-600 */
            font-size: 0.875rem !important;
            font-weight: 900 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.1em !important;
            display: flex !important;
            align-items: center !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
        }
        .preview-group-header::before {
            content: "\f247"; /* fa-object-group */
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 1rem;
            font-size: 1.25rem;
        }

        .preview-audio-header, .preview-video-header {
            padding: 1rem !important;
            background-color: #f8fafc !important; /* slate-50 */
            border: 1px solid #e2e8f0 !important; /* slate-200 */
            border-radius: 1rem !important;
            color: #4f46e5 !important; /* indigo-600 */
            font-size: 0.75rem !important;
            font-weight: 900 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            display: flex !important;
            align-items: center !important;
            margin-bottom: 1rem !important;
        }
        .preview-audio-header::before {
            content: "\f130"; /* fa-microphone */
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 0.75rem;
        }
        .preview-video-header::before {
            content: "\f03d"; /* fa-video */
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 0.75rem;
        }

        /* Form Item Layout Fixes */
        .form-wrap.form-builder .frmb .form-elements { display: flex !important; flex-wrap: wrap !important; gap: 0.5rem !important; }
        .form-wrap.form-builder .frmb .form-elements .label-wrap { display: flex !important; flex-wrap: wrap !important; align-items: center !important; width: 100% !important; gap: 0.5rem !important; }
        .form-wrap.form-builder .frmb .form-elements .fld-label { flex: 1 1 100% !important; min-width: 0 !important; width: 100% !important; max-width: 100% !important; }

        @media (max-width: 640px) {
            .form-wrap.form-builder .frmb-control li { padding: 0.25rem; }
            .fb-render .form-group { padding: 1rem !important; margin-bottom: 1.5rem !important; }
            .stage-wrap { max-width: 100%; overflow-x: hidden; }
            .form-wrap.form-builder .frmb .form-elements { flex-direction: column !important; align-items: stretch !important; gap: 0.75rem !important;}
            .form-wrap.form-builder .frmb .form-elements .label-wrap { display: flex !important; flex-direction: column !important; align-items: stretch !important; }
            .form-wrap.form-builder .frmb .form-elements .fld-label { width: 100% !important; max-width: none !important; }
        }
    </style>
@endpush

@section('content')
    <div x-data="surveyBuilder" class="relative min-h-screen pb-20">
        <div class="sticky top-0 z-[60] bg-white/95 backdrop-blur-md border-b border-gray-200 shadow-sm px-4 sm:px-6 py-3 mb-6 -mx-4 sm:-mx-8 lg:-mx-12" style="position: sticky; top: -1px; z-index: 60;">
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="showDetails = !showDetails" 
                        class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center"
                        :class="showDetails ? 'bg-indigo-600 text-white shadow-lg' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                        <i class="fa-solid fa-circle-info mr-2"></i> Details
                    </button>
                    <div class="h-6 w-px bg-gray-200 mx-2"></div>
                    <button type="button" @click.stop="activeMode = 'visual'; showDetails = false" 
                        class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center"
                        :class="activeMode === 'visual' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-white text-gray-500 hover:bg-gray-50 border border-gray-100'">
                        <i class="fa-solid fa-paint-brush mr-2"></i> Visual
                    </button>
                    <button type="button" @click.stop="activeMode = 'json'; showDetails = false" 
                        class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center"
                        :class="activeMode === 'json' ? 'bg-slate-900 text-white shadow-lg' : 'bg-gray-200 text-gray-500 hover:bg-gray-300 border border-transparent shadow-inner'">
                        <i class="fa-solid fa-code mr-2"></i> Code
                    </button>
                    <button type="button" @click="showLibrary = false; $dispatch('close-sidebar'); showAiModal = true; $nextTick(() => document.getElementById('aiPrompt').focus())" 
                        class="px-4 py-2 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-black border border-transparent transition-all flex items-center shadow-lg shadow-slate-200 group">
                        <i class="fa-solid fa-sparkles mr-2 text-cyan-400 group-hover:animate-pulse"></i> AI Architect
                    </button>
                    <button type="button" onclick="openFullScreenPreview()" 
                        class="px-4 py-2 bg-amber-50 text-amber-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-amber-100 border border-transparent transition-all flex items-center">
                        <i class="fa-solid fa-eye mr-2"></i> Preview
                    </button>
                    <div class="h-6 w-px bg-gray-200 mx-2"></div>
                    <button type="button" @click.stop="showLibrary = !showLibrary" 
                        class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center"
                        :class="showLibrary ? 'bg-green-600 text-white shadow-lg shadow-green-100' : 'bg-green-50 text-green-700 hover:bg-green-100 border border-green-100'">
                        <i class="fa-solid fa-book-bookmark mr-2"></i> Library
                    </button>
                    <button type="button" @click="groupSelected()" x-show="selectedQuestions.length > 0" x-cloak
                        class="px-4 py-2 bg-rose-50 text-rose-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-rose-100 border border-transparent transition-all flex items-center animate-in fade-in zoom-in duration-300">
                        <i class="fa-solid fa-object-group mr-2"></i> Group (<span x-text="selectedQuestions.length"></span>)
                    </button>
                </div>
            </div>

            <!-- Collapsible Survey Details (Now Sticky too) -->
            <div x-show="showDetails" x-collapse x-cloak class="mt-4 max-w-7xl mx-auto overflow-hidden">
                <div class="bg-indigo-50/50 rounded-2xl p-6 border border-indigo-100 relative">
                    <form method="POST"
                        action="{{ isset($survey) ? route('surveys.update', $survey) : route('surveys.store') }}"
                        id="surveyForm" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 relative z-10">
                        @csrf
                        @if(isset($survey)) @method('PUT') @endif
                        <input type="hidden" name="json_schema" id="json_schema" x-model="jsonSchema" value="{{ isset($survey) ? $survey->json_schema : '[]' }}">

                        <div class="space-y-1">
                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest">Survey Title</label>
                            <input type="text" name="title" id="title" required
                                x-model="surveyTitle"
                                class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-gray-900 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                        </div>

                        <div class="space-y-1">
                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest">Category</label>
                            <select name="category" id="category" required
                                class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-gray-900 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all appearance-none shadow-sm">
                                <option value="">Select Category</option>
                                @foreach(['Marketing', 'Academic', 'Product', 'Political', 'Health', 'Other'] as $cat)
                                    <option value="{{ $cat }}" {{ (isset($survey) && $survey->category === $cat) ? 'selected' : '' }} style="background-color: white !important; color: #111827 !important;">
                                        {{ $cat }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest">Type</label>
                            <select name="type" id="type" required
                                class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-gray-900 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all appearance-none shadow-sm">
                                @php $currentType = isset($survey) ? (is_object($survey->type) ? $survey->type->value : $survey->type) : 'public'; @endphp
                                <option value="public" {{ $currentType === 'public' ? 'selected' : '' }}>Public</option>
                                <option value="invitation" {{ $currentType === 'invitation' ? 'selected' : '' }}>Invitation Only</option>
                            </select>
                        </div>

                        <div class="flex items-end space-x-2">
                            <button type="submit"
                                class="flex-1 flex justify-center items-center py-2 px-4 border border-transparent rounded-xl shadow-lg text-[10px] font-black uppercase tracking-widest text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none transition-all active:scale-95">
                                <i class="fa-solid fa-save mr-2"></i> {{ isset($survey) ? 'Update' : 'Save' }}
                            </button>
                            <button type="button" @click="showDetails = false"
                                class="w-10 h-10 flex justify-center items-center bg-white border border-gray-200 rounded-xl text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all">
                                <i class="fa-solid fa-times"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

            <!-- Visual Builder / JSON Modes -->

            <!-- Builder Canvases -->
            <div x-show="activeMode === 'visual'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="flex gap-6">
                    <!-- Main Canvas -->
                    <div class="flex-1">
                        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden mb-8">

                            <div class="px-8 py-6 border-b border-gray-50 flex justify-between items-center bg-white">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 mr-4">
                                        <i class="fa-solid fa-layer-group text-lg"></i>
                                    </div>
                                    <div>
                                        <h5 class="text-sm font-black text-gray-900 uppercase tracking-widest leading-none">Question Area</h5>
                                        <div x-show="questions.length === 0" class="mt-2">
                                            <button type="button" @click="addQuestion()" class="flex items-center space-x-2 text-indigo-600 hover:text-indigo-700 transition-all group">
                                                <div class="w-6 h-6 bg-indigo-50 rounded-lg flex items-center justify-center group-hover:bg-indigo-600 group-hover:text-white transition-all">
                                                    <i class="fa-solid fa-plus text-[10px]"></i>
                                                </div>
                                                <span class="text-[10px] font-black uppercase tracking-widest">start creating your survey</span>
                                            </button>
                                        </div>
                                        <p x-show="questions.length > 0" class="text-[9px] text-gray-500 font-bold uppercase mt-1 tracking-wider" x-text="questions.length + ' questions currently on draft'"></p>
                                    </div>
                                </div>
                                <button type="button" @click.stop="resetCanvas()" class="px-4 py-2 text-[10px] font-black uppercase text-red-500 hover:bg-red-50 rounded-lg transition-all">
                                    <i class="fa-solid fa-trash-arrow-up mr-2"></i> Reset Canvas
                                </button>
                            </div>
                            
                            <div class="p-8 space-y-6 min-h-[500px] bg-slate-50/20" id="questions-list" x-init="
                                new Sortable($el, {
                                    handle: '.drag-handle',
                                    animation: 150,
                                    ghostClass: 'sortable-ghost',
                                    delay: 150, // Delay to allow scrolling on mobile
                                    delayOnTouchOnly: true, // Only apply delay on touch
                                    onEnd: (evt) => {
                                        const newQs = [...questions];
                                        const [movedItem] = newQs.splice(evt.oldIndex, 1);
                                        newQs.splice(evt.newIndex, 0, movedItem);
                                        questions = newQs;
                                        syncToJson();
                                    }
                                })
                            ">
                                <template x-for="(q, index) in questions" :key="q.id || index">
                                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 relative group hover:border-indigo-400 hover:shadow-indigo-100 transition-all ml-16" 
                                         :class="[
                                            q.type === 'group' ? 'border-l-4 border-l-rose-400' : '',
                                            selectedQuestions.includes(index) ? 'ring-2 ring-indigo-500 bg-indigo-50/10' : ''
                                         ]">
                                        
                                        <!-- Sidebar: Selection, Number, Drag -->
                                        <div class="absolute -left-14 top-4 h-full flex flex-col items-center space-y-4 z-20">
                                            <!-- Checkbox -->
                                            <input type="checkbox" :checked="selectedQuestions.includes(index)" @change="toggleSelection(index)" 
                                                class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer shadow-sm transition-transform hover:scale-110">
                                            
                                            <!-- Question Number -->
                                            <div class="w-8 h-8 rounded-full bg-gray-900 text-white flex items-center justify-center font-black text-xs shadow-lg border-2 border-white" x-text="index + 1"></div>
                                            
                                            <!-- Drag Handle (Bigger for touch) -->
                                            <div class="drag-handle text-gray-300 hover:text-indigo-600 transition-colors p-3 cursor-grab active:cursor-grabbing">
                                                <i class="fa-solid fa-grip-vertical text-2xl"></i>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-start justify-between mb-4">
                                            <div class="flex-1 mr-6">
                                                <div class="mb-4">
                                                    <input type="text" x-model="q.label" @input="syncToJson()" placeholder="Enter your question here..." 
                                                        class="w-full text-lg font-bold text-gray-900 placeholder-gray-200 border-2 border-gray-100 rounded-2xl px-6 py-4 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 bg-white transition-all shadow-sm">
                                                </div>
                                                <div class="flex items-center space-x-3">
                                                    <div class="flex items-center px-4 py-2 bg-gray-50 rounded-xl border border-gray-200 focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-200 transition-all">
                                                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mr-2">Data Type:</span>
                                                        <select x-model="q.type" @change="syncToJson()" class="bg-transparent border-none p-0 text-[10px] font-black uppercase tracking-widest text-indigo-700 focus:ring-0 cursor-pointer">
                                                            <option value="" disabled selected>Choose data type</option>
                                                            <option value="text">Text (Short/Long)</option>
                                                            <option value="select_one">Select One</option>
                                                            <option value="select_many">Select Many</option>
                                                            <option value="rating">Rating</option>
                                                            <option value="range">Range</option>
                                                            <option value="ranking">Ranking</option>
                                                            <option value="photo">Photo</option>
                                                            <option value="note">Note</option>
                                                            <option value="time">Time</option>
                                                            <option value="decimal">Decimal</option>
                                                            <option value="date">Date</option>
                                                            <option value="number">Integer</option>
                                                            <option value="audio">Audio</option>
                                                            <option value="video">Video</option>
                                                            <option value="file">File Upload</option>
                                                            <option value="header">Section Header</option>
                                                            <option value="group">Question Group</option>
                                                        </select>
                                                    </div>
                                                    <template x-if="q.required">
                                                        <span class="text-[9px] font-black text-red-500 bg-red-50 px-2 py-0.5 rounded uppercase tracking-widest">Required</span>
                                                    </template>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center space-x-2" x-data="{ confirmingDelete: false }">
                                                <button type="button" @click="duplicateQuestion(index)" class="px-3 h-9 rounded-xl text-gray-500 hover:bg-indigo-50 hover:text-indigo-600 transition-all flex items-center justify-center border border-gray-100 space-x-2" title="Duplicate">
                                                    <i class="fa-solid fa-copy text-sm"></i>
                                                    <span class="text-[9px] font-black uppercase tracking-tight">Clone</span>
                                                </button>
                                                <button type="button" @click.stop="saveToLibrary(index)" class="px-3 h-9 rounded-xl text-gray-500 hover:bg-green-50 hover:text-green-600 transition-all flex items-center justify-center border border-gray-100 space-x-2" title="Save to Library">
                                                    <i class="fa-solid fa-bookmark text-sm"></i>
                                                    <span class="text-[9px] font-black uppercase tracking-tight">Save</span>
                                                </button>
                                                
                                                <div class="flex items-center bg-red-50 rounded-xl border border-red-100 p-0.5 overflow-hidden transition-all duration-300"
                                                     :class="confirmingDelete === index ? 'max-w-40 px-2' : 'px-2 h-9'">
                                                    <button type="button" 
                                                            x-show="confirmingDelete !== index"
                                                            @click="confirmingDelete = index" 
                                                            class="w-full h-full text-red-400 hover:text-red-600 transition-all flex items-center justify-center space-x-2">
                                                        <i class="fa-solid fa-trash-can text-sm"></i>
                                                        <span class="text-[9px] font-black uppercase tracking-tight">Delete</span>
                                                    </button>
                                                    <div x-show="confirmingDelete === index" class="flex items-center space-x-2 animate-in slide-in-from-right-2" style="display:none">
                                                        <span class="text-[9px] font-black text-red-600 uppercase tracking-tighter">SURE?</span>
                                                        <button type="button" @click="removeQuestion(index); confirmingDelete = null" class="px-2 py-1 bg-red-600 text-white rounded text-[9px] font-black uppercase">YES</button>
                                                        <button type="button" @click="confirmingDelete = null" class="px-2 py-1 bg-white text-gray-500 rounded text-[9px] font-black uppercase border border-gray-200">NO</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="ml-6 border-t border-gray-50 pt-6">
                                            <div x-show="['select_one', 'select_many', 'select'].includes(q.type)">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <template x-for="(opt, oIndex) in q.values" :key="oIndex">
                                                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-xl border border-gray-100 group/opt">
                                                            <div class="w-1.5 h-1.5 rounded-full" :class="q.type === 'select_one' ? 'bg-indigo-400' : 'bg-green-400'"></div>
                                                            <input type="text" x-model="opt.label" @input="syncToJson()" 
                                                                class="flex-1 text-xs font-bold text-gray-600 border-none p-0 focus:ring-0 bg-transparent">
                                                            <button type="button" @click="q.values.splice(oIndex, 1); syncToJson()" x-show="q.values.length > 1" class="text-gray-300 hover:text-red-500 opacity-0 group-hover/opt:opacity-100 transition-all">
                                                                <i class="fa-solid fa-times text-xs"></i>
                                                            </button>
                                                        </div>
                                                    </template>
                                                    <button type="button" @click="q.values.push({label: 'New Option', value: 'option-' + Date.now()}); syncToJson()" 
                                                        class="flex items-center justify-center p-3 border-2 border-dashed border-gray-100 rounded-xl text-[10px] font-black text-indigo-500 uppercase tracking-widest hover:border-indigo-200 hover:bg-indigo-50 transition-all">
                                                        <i class="fa-solid fa-plus mr-2"></i> Add Choice
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div x-show="q.type === 'header'" class="flex items-center space-x-4">
                                                <div class="flex-1 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                                    <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-3">Header Configuration</p>
                                                    <div class="flex space-x-2">
                                                        <template x-for="level in ['h1', 'h2', 'h3']">
                                                            <button type="button" @click="q.subtype = level; syncToJson()" 
                                                                class="px-4 py-2 rounded-lg text-[10px] font-bold uppercase transition-all"
                                                                :class="q.subtype === level ? 'bg-indigo-600 text-white' : 'bg-white text-gray-500 border border-gray-100 hover:bg-gray-50'"
                                                                x-text="level === 'h1' ? 'Large' : (level === 'h2' ? 'Medium' : 'Small')">
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>

                                            <div x-show="q.type === 'group'" class="flex items-center space-x-4">
                                                <div class="flex-1 p-4 bg-rose-50/30 rounded-2xl border border-rose-100">
                                                    <p class="text-[9px] font-black text-rose-500 uppercase tracking-widest mb-2">Group Settings</p>
                                                    <p class="text-[10px] text-gray-500 font-medium">This logic group will visually enclose questions until the next group or section header. Useful for repetitive loops or thematic clusters.</p>
                                                </div>
                                            </div>

                                            <div class="mt-6 flex items-center justify-between border-t border-gray-50 pt-4">
                                                <label class="flex items-center space-x-3 cursor-pointer group/toggle">
                                                    <input type="checkbox" x-model="q.required" @change="syncToJson()" 
                                                        class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 transition-all cursor-pointer">
                                                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest group-hover:text-gray-600">Mandatory Field</span>
                                                </label>
                                                
                                                <button type="button" @click.prevent.stop="openSkipLogic(index)" class="text-[10px] font-black uppercase text-indigo-500 flex items-center hover:text-indigo-700 transition-colors">
                                                    <i class="fa-solid fa-code-branch mr-2"></i> Skip Logic
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Inline Add Button -->
                                        <div class="absolute -bottom-3 left-1/2 transform -translate-x-1/2 z-30 opacity-0 group-hover:opacity-100 hover:opacity-100 transition-opacity">
                                            <button type="button" @click.stop="addQuestionBelow(index)" 
                                                class="w-6 h-6 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full flex items-center justify-center shadow-lg transform hover:scale-110 transition-all ring-2 ring-white" title="Add Question Below">
                                                <i class="fa-solid fa-plus text-[10px]"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>

                            </div>

                            <!-- REMOVED: Question Toolbar from bottom -->
                        </div>
                    </div>

                    <!-- Library Drawer -->
                    <div x-show="showLibrary" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" 
                        class="fixed right-0 top-0 h-full w-96 bg-white shadow-[-20px_0_50px_rgba(0,0,0,0.1)] z-[100] border-l border-gray-100 flex flex-col pt-24">
                        <div class="px-8 py-6 border-b border-gray-50 flex items-center justify-between">
                            <div>
                                <h5 class="text-lg font-black text-gray-900 uppercase tracking-widest">Question Library</h5>
                                <p class="text-[10px] text-gray-500 font-bold uppercase mt-1">Reusable templates</p>
                            </div>
                            <button type="button" @click="showLibrary = false" class="text-gray-500 hover:text-red-500">
                                <i class="fa-solid fa-times text-xl"></i>
                            </button>
                        </div>
                        <div class="px-8 py-4 bg-white sticky top-0 z-10 border-b border-gray-100">
                            <div class="flex p-1 bg-gray-100 rounded-xl">
                                <button type="button" @click="libTab = 'templates'" class="flex-1 py-1.5 text-[9px] font-black uppercase tracking-widest rounded-lg transition-all"
                                    :class="libTab === 'templates' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-600'">Templates</button>
                                <button type="button" @click="libTab = 'questions'" class="flex-1 py-1.5 text-[9px] font-black uppercase tracking-widest rounded-lg transition-all"
                                    :class="libTab === 'questions' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-600'">Library</button>
                            </div>
                        </div>

                        <div class="p-6 space-y-4 flex-1 overflow-y-auto bg-slate-50/30 custom-scrollbar">
                            <!-- Templates Tab -->
                            <div x-show="libTab === 'templates'" space-y-4>
                                <template x-for="item in library.filter(i => i.is_template)" :key="item.id">
                                    <div class="p-5 bg-white rounded-2xl border border-gray-100 hover:border-purple-400 hover:shadow-lg hover:shadow-purple-50 cursor-pointer transition-all group mb-4" @click.stop="addFromLibrary(item)">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-[9px] font-black px-2 py-1 rounded-lg uppercase tracking-widest text-purple-600 bg-purple-50" x-text="item.type"></span>
                                            <i class="fa-solid fa-plus-circle text-gray-200 group-hover:text-purple-500 text-xl transition-all"></i>
                                        </div>
                                        <p class="text-sm font-black text-gray-900 uppercase tracking-tight leading-tight" x-text="item.title"></p>
                                        <p class="text-[9px] text-gray-500 font-bold uppercase mt-2">Full Survey Blueprint</p>
                                    </div>
                                </template>
                            </div>

                            <!-- Questions Tab -->
                            <div x-show="libTab === 'questions'" space-y-4>
                                <template x-for="item in library.filter(i => !i.is_template)" :key="item.id">
                                    <div class="p-5 bg-white rounded-2xl border border-gray-100 hover:border-green-400 hover:shadow-lg hover:shadow-green-50 cursor-pointer transition-all group mb-4" @click.stop="addFromLibrary(item)">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-[9px] font-black px-2 py-1 rounded-lg uppercase tracking-widest text-green-600 bg-green-50" x-text="item.type"></span>
                                            <i class="fa-solid fa-plus-circle text-gray-200 group-hover:text-green-500 text-xl transition-all"></i>
                                        </div>
                                        <p class="text-sm font-black text-gray-900 uppercase tracking-tight leading-tight" x-text="item.title"></p>
                                    </div>
                                </template>
                                <template x-if="library.filter(i => !i.is_template).length === 0">
                                    <div class="py-12 px-6 text-center">
                                        <p class="text-xs font-bold text-gray-500 uppercase">No shared questions yet</p>
                                    </div>
                                </template>
                            </div> <!-- Close Questions Tab -->
                        </div>
                    </div>
                </div> <!-- Close Library Drawer -->
            </div> <!-- Close activeMode === 'visual' -->
        </div> <!-- Close Visual Builder Mode Content Wrapper -->

        <!-- JSON Import Mode -->
        <div x-show="activeMode === 'json'" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="max-w-7xl mx-auto mt-4 pb-20">
            <div class="bg-gray-900 rounded-3xl shadow-2xl border border-white/5 overflow-hidden">
                <div class="px-8 py-6 border-b border-white/5 flex justify-between items-center bg-gray-900">
                    <div>
                        <h5 class="text-sm font-black text-white uppercase tracking-widest flex items-center">
                            <i class="fa-solid fa-code mr-3 text-indigo-400"></i> JSON Blueprint Editor
                        </h5>
                        <p class="text-[10px] text-gray-500 font-bold uppercase mt-1 tracking-tight">Direct schema manipulation</p>
                    </div>
                </div>
                <div class="p-8 bg-[#030712]">
                    <textarea id="jsonInput" x-model="jsonSchema"
                        @input="validateJSONManual()"
                        class="w-full rounded-3xl border-none shadow-2xl sm:text-sm font-mono p-10 text-emerald-400 min-h-[600px] focus:ring-0 leading-relaxed overflow-y-auto custom-scrollbar"
                        style="background-color: #111827 !important; color: #10b981 !important; outline: none !important; border: none !important;"
                        placeholder='[]'></textarea>

                    <div class="mt-8 flex space-x-4">
                        <button type="button" class="px-8 py-3 bg-indigo-600 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-500 shadow-xl shadow-indigo-900/40 transition-all font-bold" onclick="validateJSON()">
                            Validate and Load
                        </button>
                        <button type="button" class="px-8 py-3 bg-white/5 text-gray-500 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-white/10 transition-all" onclick="clearJSON()">
                            Wipe Schema
                        </button>
                    </div>
                    <div id="jsonStatus" class="mt-6"></div>
                </div>
            </div>
        </div>

        <!-- Skip Logic Modal -->
        <div x-show="showSkipModal" x-cloak class="fixed inset-0 z-[100000] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="relative w-full max-w-xl bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden" @click.away="closeSkipLogic()">
                <div class="px-8 py-6 border-b border-gray-50 flex items-center justify-between bg-white">
                    <h3 class="text-xl font-black text-gray-900 flex items-center uppercase tracking-tight">
                        <i class="fa-solid fa-code-branch mr-3 text-indigo-500"></i> Skip Logic
                    </h3>
                    <button type="button" @click="closeSkipLogic()" class="text-gray-500 hover:text-red-500 transition-colors">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-8 bg-white">
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-6">If the answer is...</p>
                    <div class="space-y-4 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                        <template x-if="currentQuestionIndex !== null && ['radio-group', 'checkbox-group', 'select'].includes(questions[currentQuestionIndex].type)">
                            <div class="space-y-3">
                                <template x-for="(opt, oIdx) in questions[currentQuestionIndex].values" :key="oIdx">
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl border border-gray-100 shadow-sm transition-all hover:border-indigo-200">
                                        <span class="text-xs font-bold text-gray-700" x-text="opt.label"></span>
                                        <div class="flex items-center space-x-3">
                                            <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest">Jump to:</span>
                                            <select x-model="opt.next" @change="syncToJson()" class="bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-[10px] font-bold text-indigo-600 focus:ring-2 focus:ring-indigo-500 outline-none transition-all cursor-pointer">
                                                <option value="">Next Question</option>
                                                <template x-for="(qNext, qIdx) in questions" :key="qIdx">
                                                    <option x-show="qIdx > currentQuestionIndex" :value="qNext.name" x-text="(qIdx + 1) + '. ' + (qNext.label ? qNext.label.substring(0, 20) : 'Untitled') + (qNext.label && qNext.label.length > 20 ? '...' : '')"></option>
                                                </template>
                                                <option value="submit">End Survey</option>
                                            </select>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="px-8 py-6 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button type="button" @click="closeSkipLogic()" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-500 shadow-lg shadow-indigo-100 transition-all active:scale-95">Done</button>
                </div>
            </div>
        </div>

        <!-- AI Architect Modal -->
        <div id="aiModal" x-show="showAiModal" x-cloak
            class="fixed inset-0 z-[100001] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">
            <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden" @click.away="showAiModal = false">
                <div class="px-8 py-6 border-b border-gray-50 flex items-center justify-between bg-white">
                    <h3 class="text-xl font-black text-gray-900 flex items-center uppercase tracking-tight">
                        <i class="fa-solid fa-wand-magic-sparkles mr-3 text-gray-900"></i> AI Architect
                    </h3>
                    <button type="button" @click="showAiModal = false" class="text-gray-500 hover:text-red-500 transition-colors">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-8 bg-white">
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-4 font-bold">Describe your survey requirements</p>
                    <textarea id="aiPrompt" rows="6" 
                        class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-6 py-4 text-sm text-gray-700 placeholder-slate-300 focus:ring-4 focus:ring-gray-900/10 focus:border-gray-900 outline-none transition-all shadow-inner"
                        placeholder="e.g., Generate a 5-question customer satisfaction survey for a coffee shop including multiple choice for visit frequency and a text area for feedback."></textarea>
                    
                    <div id="aiLoader" class="hidden mt-6 bg-gray-50 rounded-2xl p-6 border border-gray-100 animate-in fade-in zoom-in">
                        <div class="flex items-center">
                            <i class="fa-solid fa-wand-magic-sparkles text-gray-900 text-xl mr-4 animate-bounce"></i>
                            <div>
                                <p class="text-xs font-black text-gray-900 uppercase tracking-widest">Architect is thinking...</p>
                                <p class="text-[10px] text-gray-500 font-bold uppercase mt-0.5">Crafting your custom survey schema</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-8 py-6 bg-gray-50 border-t border-gray-100 flex justify-end space-x-3">
                    <button type="button" @click="showAiModal = false" class="px-6 py-2.5 bg-white text-gray-500 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-gray-100 border border-gray-200 transition-all">Cancel</button>
                    <button type="button" onclick="generateWithAi()" class="px-6 py-2.5 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-black shadow-lg shadow-slate-100 transition-all active:scale-95">Generate Schema</button>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <div id="previewModal" class="fixed inset-0 z-[100000] hidden flex-col bg-slate-50 overflow-hidden" x-cloak>
            <!-- Modal Top Header -->
            <div class="px-6 sm:px-10 py-4 sm:py-6 bg-white border-b border-gray-100 flex items-center justify-between sticky top-0 z-[11000]">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-500 shadow-sm border border-amber-100">
                        <i class="fa-solid fa-eye text-lg sm:text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-lg sm:text-xl font-black text-gray-900 tracking-tight uppercase" id="previewSurveyTitle">Live Preview</h2>
                        <div class="flex items-center mt-0.5">
                            <span class="flex h-1.5 w-1.5 rounded-full bg-green-500 mr-2"></span>
                            <p class="text-[9px] text-gray-500 font-black uppercase tracking-widest">Interactive Prototype</p>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center space-x-3">
                    <button type="button" onclick="closeFullScreenPreview()" 
                        class="w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center bg-gray-900 text-white rounded-2xl hover:bg-red-600 hover:rotate-90 transition-all duration-300 shadow-xl group">
                        <i class="fa-solid fa-times text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Preview Canvas -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-12 custom-scrollbar bg-slate-50">
                <div class="max-w-4xl mx-auto">
                    <div class="bg-white rounded-[2rem] sm:rounded-[2.5rem] shadow-2xl shadow-indigo-100/40 border border-gray-100 overflow-hidden relative">
                        <!-- Hero Banner in Preview -->
                        <div class="h-24 sm:h-32 bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 relative overflow-hidden">
                            <div class="absolute inset-0 opacity-10">
                                <div class="absolute top-0 left-0 w-24 h-24 bg-white rounded-full -translate-x-12 -translate-y-12"></div>
                                <div class="absolute bottom-0 right-0 w-32 h-32 bg-white rounded-full translate-x-16 translate-y-16"></div>
                            </div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <i class="fa-solid fa-vial-circle-check text-white/20 text-4xl sm:text-6xl"></i>
                            </div>
                        </div>
                        
                        <!-- Question Area -->
                        <div class="p-6 sm:p-12">
                            <div id="previewRenderArea" class="fb-render space-y-6 sm:space-y-8"></div>
                            
                            <div class="mt-12 pt-10 border-t border-gray-50 flex justify-between items-center">
                                <button type="button" disabled class="px-6 sm:px-8 py-3 sm:py-4 bg-gray-100 text-gray-500 rounded-2xl text-[10px] sm:text-xs font-black uppercase tracking-widest cursor-not-allowed">Previous Page</button>
                                <button type="button" onclick="alert('Success! This prototype works as expected.')" class="px-6 sm:px-10 py-3 sm:py-4 bg-indigo-600 text-white rounded-2xl text-[10px] sm:text-xs font-black uppercase tracking-widest hover:bg-indigo-700 shadow-xl shadow-indigo-200 transition-all hover:-translate-y-1 active:scale-95">Complete Survey</button>
                            </div>
                            
                            <p class="text-[9px] text-gray-300 text-center mt-12 font-bold uppercase tracking-widest">Powered by KM Survey Architect Engine</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div> <!-- Close surveyBuilder x-data -->
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://formbuilder.online/assets/js/form-builder.min.js"></script>
    <script src="https://formbuilder.online/assets/js/form-render.min.js"></script>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('surveyBuilder', () => ({
                showDetails: false, 
                activeMode: 'visual',
                showLibrary: false,
                showSkipModal: false,
                showAiModal: false,
                currentQuestionIndex: null,
                jsonSchema: '',
                surveyTitle: "{{ isset($survey) ? $survey->title : 'Untitled Survey' }}",
                questions: [],
                library: [],
                selectedQuestions: [],
                libTab: 'templates',
                
                init() {
                    window.Laravel = window.Laravel || {};
                    window.Laravel.alpineData = () => this;
                    
                    this.$watch('activeMode', value => {
                        if (value === 'json') {
                            this.syncToJson();
                        }
                    });
                    
                    
                    const el = document.getElementById('json_schema');
                    const existingData = el ? el.value : '[]';
                    if (existingData && existingData !== '[]') {
                        try {
                            const parsed = JSON.parse(existingData);
                            this.questions = this.mapFromLegacy(parsed);
                        } catch (e) {
                            console.error('Failed to parse existing survey data', e);
                        }
                    }
                    this.fetchLibrary();
                    this.syncToJson();

                    window.addEventListener('ai-schema-generated', (e) => {
                        this.questions = this.mapFromLegacy(e.detail.schema);
                        this.syncToJson();
                    });

                    window.addEventListener('json-schema-synced', (e) => {
                        this.questions = this.mapFromLegacy(e.detail.schema);
                        this.syncToJson();
                    });

                    window.addEventListener('close-ai-modal', () => {
                        this.showAiModal = false;
                    });
                },

                mapFromLegacy(data) {
                    if (!Array.isArray(data)) return [];
                    return data.map(field => {
                        let type = field.type || 'text';
                        // Map legacy types to new ones if necessary
                        if (type === 'radio-group') type = 'select_one';
                        if (type === 'checkbox-group') type = 'select_many';
                        if (type === 'textarea') type = 'text';

                        const q = {
                            type: type,
                            subtype: field.subtype || '',
                            label: field.label || '',
                            name: field.name || 'field-' + Math.random().toString(36).substr(2, 9),
                            id: field.id || 'q-' + Math.random().toString(36).substr(2, 9),
                            required: field.required || false,
                            values: field.values || [
                                {label: 'Option 1', value: 'option-1'},
                                {label: 'Option 2', value: 'option-2'}
                            ]
                        };
                        return q;
                    });
                },

                mapToLegacy() {
                    return this.questions.map(q => {
                        const legacy = {
                            type: q.type,
                            label: q.label || '',
                            required: q.required,
                            name: q.name || 'field-' + Math.random().toString(36).substr(2, 9),
                            id: q.id
                        };
                        if (q.subtype) legacy.subtype = q.subtype;
                        if (['select_one', 'select_many', 'select', 'ranking'].includes(q.type)) {
                            legacy.values = q.values.map(v => ({ ...v }));
                        }
                        return legacy;
                    });
                },

                addQuestion(type = '') {
                    const newQ = {
                        type: type, // Explicitly empty for "Choose data type"
                        id: 'q-' + Math.random().toString(36).substr(2, 9),
                        label: '', 
                        name: 'field-' + Date.now(),
                        required: false,
                        values: [
                            {label: 'Option 1', value: 'option-1'},
                            {label: 'Option 2', value: 'option-2'}
                        ]
                    };
                    if (type === 'header') {
                        newQ.subtype = 'h1';
                        newQ.label = 'New Section';
                    }
                    
                    this.questions.push(newQ);
                    this.syncToJson();
                    
                    // Auto-scroll to the bottom
                    this.$nextTick(() => {
                        window.scrollTo({
                            top: document.body.scrollHeight,
                            behavior: 'smooth'
                        });
                    });
                },

                addQuestionBelow(index) {
                    const newQ = {
                        type: '', // Force selection
                        label: '',
                        name: 'field-' + Date.now(),
                        required: false,
                        values: [
                            {label: 'Option 1', value: 'option-1'},
                            {label: 'Option 2', value: 'option-2'}
                        ]
                    };
                    this.questions.splice(index + 1, 0, newQ);
                    this.syncToJson();

                    // Auto-scroll to the new question
                    this.$nextTick(() => {
                        const questions = document.querySelectorAll('#questions-list > div');
                        if (questions[index + 1]) {
                            questions[index + 1].scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    });
                },

                removeQuestion(index) {
                    const newQs = [...this.questions];
                    newQs.splice(index, 1);
                    this.questions = newQs;
                    this.syncToJson();
                },

                duplicateQuestion(index) {
                    const clone = JSON.parse(JSON.stringify(this.questions[index]));
                    clone.name = 'field-' + Date.now();
                    clone.id = 'q-' + Math.random().toString(36).substr(2, 9);
                    const newQs = [...this.questions];
                    newQs.splice(index + 1, 0, clone);
                    this.questions = newQs;
                    this.syncToJson();
                },

                syncToJson() {
                    const legacyData = this.mapToLegacy();
                    const hasData = legacyData.length > 0;
                    const jsonString = hasData ? JSON.stringify(legacyData, null, 2) : '[]';
                    
                    this.jsonSchema = jsonString;
                    this.syncToPrompt();
                    
                    // Trigger Auto-save
                    if (this.autoSaveTimeout) clearTimeout(this.autoSaveTimeout);
                    this.autoSaveTimeout = setTimeout(() => {
                        this.syncWithBackend();
                    }, 2000); // 2 second debounce
                },

                syncWithBackend() {
                    const payload = {
                        survey_id: '{{ $survey->id }}',
                        title: '{{ $survey->title }}',
                        category: '{{ $survey->category }}',
                        type: '{{ $survey->type }}',
                        description: '{{ $survey->description }}',
                        json_schema: this.jsonSchema,
                        _token: '{{ csrf_token() }}'
                    };

                    fetch('{{ route('surveys.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(r => r.json())
                    .then(data => {
                        console.log('Auto-saved successfully');
                    })
                    .catch(err => console.error('Auto-save failed:', err));
                },

                validateJSONManual() {
                    console.log('validateJSONManual: user typing in JSON textarea');
                    try {
                        const parsed = JSON.parse(this.jsonSchema);
                        if (Array.isArray(parsed)) {
                            this.questions = this.mapFromLegacy(parsed);
                        }
                    } catch (e) {
                        console.warn('Invalid JSON in manual entry:', e.message);
                    }
                },

                syncToPrompt() {
                    if (this.questions.length === 0) return;
                    let description = 'Generate a survey with the following structure:\n';
                    this.questions.forEach((q, index) => {
                        description += `${index + 1}. A ${q.type} field labeled "${q.label}"\n`;
                    });
                },

                switchMode(mode) {
                    this.activeMode = mode;
                    if (mode === 'json') {
                        this.syncToJson();
                    }
                },
                                
                fetchLibrary() {
                    fetch('{{ route('library.questions') }}')
                        .then(r => r.json())
                        .then(data => { 
                            this.library = data; 
                        })
                        .catch(err => console.error('Library Fetch Error:', err));
                },

                saveToLibrary(index) {
                    const q = this.questions[index];
                    fetch('{{ route('library.questions.save') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            title: q.label,
                            type: q.type,
                            content: JSON.stringify(q)
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if(data.success) {
                            alert('Saved to library!');
                            this.fetchLibrary();
                        }
                    })
                    .catch(err => alert('Failed to save to library: ' + err.message));
                },

                resetCanvas() {
                    console.log('resetCanvas logic starting...');
                    this.questions = [];
                    this.selectedQuestions = [];
                    this.syncToJson();
                    this.$nextTick(() => { console.log('Canvas reset complete'); });
                },

                addFromLibrary(item) {
                    console.log('addFromLibrary starting for item:', item.title);
                    try {
                        const content = typeof item.content === 'string' ? JSON.parse(item.content) : item.content;
                        
                        if (item.is_template) {
                             const templateArray = Array.isArray(content) ? content : [content];
                             const mapped = this.mapFromLegacy(templateArray);
                             mapped.forEach(q => {
                                 q.name = 'field-' + Math.random().toString(36).substr(2, 9);
                             });
                             this.questions = [...this.questions, ...mapped];
                        } else {
                             const mapped = this.mapFromLegacy([content]);
                             if (mapped.length > 0) {
                                 const newQ = mapped[0];
                                 newQ.name = 'field-' + Date.now();
                                 this.questions = [...this.questions, newQ];
                             }
                        }
                        this.syncToJson();
                        console.log('Add from library successful');
                    } catch (e) {
                        console.error('Library item error:', e);
                        alert('Could not add item: ' + e.message);
                    }
                },

                openSkipLogic(index) {
                    this.currentQuestionIndex = index;
                    this.showSkipModal = true;
                },

                closeSkipLogic() {
                    this.showSkipModal = false;
                    this.currentQuestionIndex = null;
                    this.syncToJson();
                },

                toggleSelection(idx) {
                    if (this.selectedQuestions.includes(idx)) {
                        this.selectedQuestions = this.selectedQuestions.filter(i => i !== idx);
                    } else {
                        this.selectedQuestions.push(idx);
                    }
                },

                groupSelected() {
                    if (this.selectedQuestions.length === 0) return;
                    
                    // Sort selected indices
                    const indices = [...this.selectedQuestions].sort((a, b) => a - b);
                    const minIdx = indices[0];

                    // Create a new group question
                    const newGroup = {
                        type: 'group',
                        id: 'q-' + Math.random().toString(36).substr(2, 9),
                        label: 'New Survey Section',
                        name: 'group-' + Date.now(),
                        required: false
                    };

                    // Insert at minIdx
                    this.questions.splice(minIdx, 0, newGroup);
                    
                    // Clear selection
                    this.selectedQuestions = [];
                    this.syncToJson();
                }
            }));
        });

        let currentMode = 'visual';



        // Form Submission Intercept
        jQuery(function ($) {
            $('#surveyForm').on('submit', function (e) {
                e.preventDefault();

                let formDataJSON = $('#json_schema').val();

                // If user was in JSON mode, they might have edited without validating
                // But typically syncToJson handles it. We'll grab latest from input just in case.
                const activeMode = document.querySelector('[x-data]').__x.$data.activeMode;
                if (activeMode === 'json') {
                    formDataJSON = $('#jsonInput').val();
                    try {
                        JSON.parse(formDataJSON);
                    } catch (e) {
                        alert('Invalid JSON structure. Please fix or sync before saving.');
                        return false;
                    }
                }

                if (!formDataJSON || formDataJSON === '[]' || formDataJSON === '') {
                    alert('Please add at least one question before saving.');
                    return false;
                }

                const submitBtn = $(this).find('button[type="submit"]');
                const originalIcon = submitBtn.html();
                submitBtn.html('<i class="fa-solid fa-spinner fa-spin mr-2"></i> Saving...');
                submitBtn.prop('disabled', true);

                const isUpdate = "{{ isset($survey) ? 'true' : 'false' }}" === 'true';

                fetch(this.action, {
                    method: isUpdate ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        title: $('#title').val(),
                        description: $('#description').val(),
                        category: $('#category').val(),
                        type: $('#type').val(),
                        json_schema: formDataJSON
                    })
                })
                .then(response => {
                    if (response.ok) return response.json();
                    throw new Error('Network response was not ok.');
                })
                .then(data => {
                    alert('Survey saved successfully!');
                    @php
                        $user = auth()->user();
                        $roleValue = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;
                    @endphp
                    window.location.href = "{{ route($roleValue . '.dashboard') }}";
                })
                .catch(error => {
                    alert('Error saving survey: ' + error.message);
                    submitBtn.html(originalIcon);
                    submitBtn.prop('disabled', false);
                });
            });
        });

        // Mode Switching (Legacy bridge if needed, but Alpine handles it now)
        function switchMode(mode) {
           // This is now handled by Alpine: activeMode = '...' 
        }

        // Preview Functions
        function openFullScreenPreview() {
            // Pull data from Alpine state directly
            const alpineData = Laravel.alpineData ? Laravel.alpineData() : (document.querySelector('[x-data]')?.__x?.$data);
            const schema = alpineData ? alpineData.jsonSchema : document.getElementById('json_schema').value;
            
            if (!schema || schema === '[]') {
                alert('Please add some questions first.');
                return;
            }

            try {
                const parsed = JSON.parse(schema);
                
                // FormRender doesn't natively handle 'audio' and 'video' types.
                // Inject survey title
                const titleVal = (alpineData && alpineData.surveyTitle) ? alpineData.surveyTitle : (document.getElementById('title')?.value || 'Live Preview');
                document.getElementById('previewSurveyTitle').innerText = titleVal;

                // FormRender enhancements
                const previewSchema = parsed.map(field => {
                    // Force styling for text inputs
                    if (['text', 'textarea', 'number', 'date', 'email', 'tel'].includes(field.type)) {
                        field.className = (field.className || '') + ' preview-input';
                    }
                    
                    if (field.type === 'group') {
                        return {
                            ...field,
                            type: 'header',
                            subtype: 'h3',
                            className: 'preview-group-header',
                            label: field.label || 'Untitled Section'
                        };
                    }

                    if (['audio', 'video'].includes(field.type)) {
                        return {
                            ...field,
                            type: 'paragraph',
                            className: `preview-${field.type}-header`,
                            label: field.label || `${field.type.charAt(0).toUpperCase() + field.type.slice(1)} Field`
                        };
                    }
                    return field;
                });

                const renderArea = jQuery('#previewRenderArea');
                renderArea.empty();
                renderArea.formRender({
                    formData: previewSchema,
                    dataType: 'json'
                });
                jQuery('#previewModal').removeClass('hidden').addClass('flex');
                document.body.style.overflow = 'hidden'; 
            } catch (e) {
                console.error("Preview Error:", e);
                alert('Invalid survey structure: ' + e.message);
            }
        }

        function closeFullScreenPreview() {
            jQuery('#previewModal').addClass('hidden').removeClass('flex');
            document.body.style.overflow = ''; 
        }

        // AI Architect Logic
        function openAiArchitect() {
            jQuery('#aiModal').removeClass('hidden').addClass('flex');
            jQuery('#aiPrompt').focus();
        }

        function closeAiArchitect() {
            jQuery('#aiModal').addClass('hidden').removeClass('flex');
        }

        // Diagnostic helper
        window.checkAlpineState = function() {
            const data = document.querySelector('[x-data]').__x.$data;
            console.log('--- SURVEY BUILDER STATE ---');
            console.log('Mode:', data.activeMode);
            console.log('Questions:', data.questions);
            console.log('Library Size:', data.library.length);
            return data;
        };

        function generateWithAi() {
            const prompt = jQuery('#aiPrompt').val().trim();
            if (!prompt) return alert('Please describe the survey.');

            const loader = jQuery('#aiLoader');
            loader.removeClass('hidden');

            fetch("{{ route('ai.generate') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ prompt: prompt })
            })
            .then(response => response.json())
            .then(data => {
                console.log('AI Response received:', data);
                if (data.success) {
                    let schema = data.schema;
                    if (typeof schema === 'string') {
                        try {
                            schema = JSON.parse(schema);
                        } catch (e) {
                            console.error('Failed to parse AI schema string:', e);
                            alert('AI returned malformed schema text.');
                            return;
                        }
                    }
                    console.log('Dispatching ai-schema-generated with parsed schema:', schema);
                    window.dispatchEvent(new CustomEvent('ai-schema-generated', { detail: { schema: schema } }));
                    window.dispatchEvent(new CustomEvent('close-ai-modal'));
                } else {
                    console.error('AI Error response:', data.message);
                    alert('AI Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('AI Architect Fetch Error:', error);
                alert('AI Architect Error: ' + error.message);
            })
            .finally(() => loader.addClass('hidden'));
        }

        function validateJSON() {
            const jsonInput = document.getElementById('jsonInput').value.trim();
            const statusBox = document.getElementById('jsonStatus');
            try {
                const parsed = JSON.parse(jsonInput);
                if (!Array.isArray(parsed)) throw new Error("JSON must be an Array []");
                statusBox.innerHTML = '<div class="p-4 bg-green-900 border border-green-700 text-green-400 rounded-xl text-[10px] font-black uppercase tracking-widest">Valid Schema Detected - Synced with Visual Builder</div>';
                
                // Sync back to visual builder via event
                window.dispatchEvent(new CustomEvent('json-schema-synced', { detail: { schema: parsed } }));
                document.getElementById('json_schema').value = jsonInput;
            } catch (e) {
                statusBox.innerHTML = '<div class="p-4 bg-red-900 border border-red-700 text-red-400 rounded-xl text-[10px] font-black uppercase tracking-widest">Syntax Error: ' + e.message + '</div>';
            }
        }

        function clearJSON() {
            if(confirm('Are you sure? This will wipe the current draft schema.')) {
                document.getElementById('jsonInput').value = '[]';
                document.getElementById('jsonStatus').innerHTML = '';
                validateJSON();
            }
        }
    </script>
@endpush