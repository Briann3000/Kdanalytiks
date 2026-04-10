@extends('layouts.app')

@section('title', $survey->title)

@section('head')
    @if(!empty($survey->json_schema))
        <!-- jQuery Form Builder CSS from cdnjs for reliability -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/jQuery-formBuilder/3.4.2/form-builder.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jQuery-formBuilder/3.4.2/form-render.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @endif
    <style>
        /* formRender Styling Overrides */
        .rendered-form input[type="text"], 
        .rendered-form input[type="number"], 
        .rendered-form input[type="email"], 
        .rendered-form input[type="date"], 
        .rendered-form textarea, 
        .rendered-form select {
            width: 100% !important;
            border: 2px solid #d1d5db !important;
            border-radius: 0.75rem !important;
            padding: 0.75rem 1rem !important;
            background-color: #ffffff !important;
            transition: all 0.2s ease-in-out !important;
            font-weight: 500 !important;
            color: #1f2937 !important;
            margin-bottom: 1rem !important;
        }
        .rendered-form input:focus, .rendered-form textarea:focus, .rendered-form select:focus {
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1) !important;
            outline: none !important;
        }
        .rendered-form > .form-group > label {
            display: block !important;
            font-size: 1.125rem !important;
            font-weight: 700 !important;
            color: #111827 !important;
            margin-bottom: 0.75rem !important;
            line-height: 1.5 !important;
        }

        /* Fix for inline labels in radio/checkbox groups */
        .rendered-form .radio-inline label, .rendered-form .checkbox-inline label {
            display: inline-block !important;
            margin-bottom: 0 !important;
            font-weight: 600 !important;
            font-size: 0.95rem !important;
            vertical-align: middle !important;
            cursor: pointer !important;
        }

        /* Inline Radio/Checkbox Layout */
        .preview-inline-group { 
            display: flex !important; 
            flex-wrap: wrap !important; 
            gap: 1.5rem !important; 
            padding: 0.5rem 0 !important;
            background: rgba(249, 250, 251, 0.5) !important;
            border-radius: 1rem !important;
            padding: 1rem !important;
        }
        .preview-inline-group > .radio-inline, .preview-inline-group > .checkbox-inline { 
            display: flex !important; 
            align-items: center !important; 
            margin: 0 !important;
            cursor: pointer !important;
            font-weight: 600 !important;
            color: #374151 !important;
        }
        .preview-inline-group input { 
            margin-right: 0.75rem !important; 
            cursor: pointer !important; 
            width: 1.25rem !important; 
            height: 1.25rem !important;
            accent-color: #4f46e5 !important;
        }

        /* Enketo/Kobo Styling */
        .likert-container {
            display: flex !important;
            justify-content: space-between !important;
            gap: 0.5rem !important;
            margin-top: 1rem !important;
        }
        .likert-item {
            flex: 1;
            text-align: center;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
            font-weight: 600;
            color: #64748b;
            background: white;
        }
        .likert-item:hover { background: #f1f5f9; }
        .likert-item.active {
            background: #6366f1;
            color: white;
            border-color: #6366f1;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.2);
        }

        .rank-pool, .rank-ordered {
            min-height: 120px;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 1rem;
            border: 2px dashed #e2e8f0;
        }
        .rank-item {
            padding: 0.75rem 1rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-weight: 600;
            color: #334155;
            transition: all 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .rank-item:hover { border-color: #6366f1; transform: translateY(-1px); }
        .rank-badge {
            width: 1.5rem;
            height: 1.5rem;
            background: #6366f1;
            color: white;
            border-radius: 99px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            margin-right: 0.75rem;
            font-weight: 800;
        }

        .recorder-dashboard {
            background: #1e293b;
            color: white;
            padding: 2rem;
            border-radius: 1.5rem;
            text-align: center;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }
        .recorder-status {
            font-size: 0.7rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: #94a3b8;
            margin-bottom: 1.5rem;
        }
        .recorder-timer {
            font-family: 'Courier New', Courier, monospace;
            font-size: 2.5rem;
            font-weight: 700;
            margin: 1rem 0;
            color: #f8fafc;
            text-shadow: 0 0 20px rgba(99,102,241,0.3);
        }
        .record-btn {
            width: 5rem;
            height: 5rem;
            background: #ef4444;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 6px solid rgba(255,255,255,0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .record-btn:hover { transform: scale(1.05); background: #f87171; }
        .record-btn.recording {
            animation: pulse-red 2s infinite;
            border-radius: 1rem;
            background: #dc2626;
        }
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { box-shadow: 0 0 0 20px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
    </style>
@endsection

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-white shadow-2xl rounded-3xl overflow-hidden border border-gray-100">
        <!-- Header Section -->
        <div class="px-8 py-8 bg-gradient-to-r from-gray-900 via-indigo-900 to-indigo-800 relative">
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-32 h-32 bg-indigo-500/20 rounded-full blur-xl"></div>
            
            <div class="relative z-10 flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-black text-white tracking-tight">{{ $survey->title }}</h1>
                    <p class="mt-3 text-indigo-100/90 font-medium leading-relaxed max-w-2xl">{{ $survey->description }}</p>
                    
                    @if($survey->is_paid)
                        @if($budgetExhausted)
                            <div class="mt-4 inline-flex items-center px-4 py-2 rounded-xl bg-amber-400 text-amber-950 shadow-lg shadow-amber-500/20 ring-1 ring-white/20">
                                <i class="fa-solid fa-triangle-exclamation mr-2"></i>
                                <span class="text-[10px] font-black uppercase tracking-widest leading-none">Reward Budget Exhausted</span>
                            </div>
                        @else
                            <div class="mt-4 inline-flex items-center px-4 py-2 rounded-xl bg-emerald-400 text-emerald-950 shadow-lg shadow-emerald-500/20 ring-1 ring-white/20">
                                <i class="fa-solid fa-sack-dollar mr-2"></i>
                                <span class="text-[10px] font-black uppercase tracking-widest leading-none">Paid Survey: Earn {{ number_format($survey->reward_per_response, 0) }} {{ $survey->reward_currency ?? 'KES' }}</span>
                            </div>
                        @endif
                    @elseif($survey->type === \App\Enums\SurveyType::Public)
                        <div class="mt-4 inline-flex items-center px-4 py-2 rounded-xl bg-cyan-400 text-cyan-950 shadow-lg shadow-cyan-500/20 ring-1 ring-white/20">
                            <i class="fa-solid fa-globe mr-2"></i>
                            <span class="text-[10px] font-black uppercase tracking-widest leading-none">Public Survey</span>
                        </div>
                    @endif
                </div>
                <div class="hidden sm:block">
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-xs font-bold bg-white/10 text-white border border-white/20 backdrop-blur-sm">
                        <i class="fa-solid fa-tag mr-2"></i> {{ ucfirst($survey->category instanceof \BackedEnum ? $survey->category->value : ($survey->category ?? 'General')) }}
                    </span>
                </div>
            </div>
        </div>
        
        @if(session('success'))
            <div class="bg-green-50 border-l-4 border-green-500 p-6 m-8 rounded-r-xl">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fa-solid fa-circle-check text-green-500 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-bold text-green-800">{{ session('success') }}</p>
                        <p class="text-sm text-green-600 mt-1">Your responses have been securely recorded.</p>
                    </div>
                </div>
            </div>
            <script>
                // Clear any stored drafts upon successful submission
                localStorage.removeItem(`draft_survey_{{ $survey->id }}`);
                localStorage.removeItem(`legacy_draft_survey_{{ $survey->id }}`);
            </script>
        @else
        @guest
            @if($survey->is_paid && !$budgetExhausted)
                <div class="bg-indigo-600 px-6 py-4 flex flex-col md:flex-row items-center justify-between gap-4 border-b border-white/10 shadow-inner">
                    <div class="flex items-center text-white">
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center mr-4 shrink-0">
                            <i class="fa-solid fa-wallet text-xl"></i>
                        </div>
                        <div>
                            <p class="font-black text-sm uppercase tracking-wider mb-0.5 whitespace-nowrap">Participate & Earn Money</p>
                            <p class="text-[11px] text-indigo-100 font-medium leading-tight">Register or Login as a Respondent to receive your <b>{{ number_format($survey->reward_per_response, 0) }} {{ $survey->reward_currency ?? 'KES' }}</b> reward and access your wallet.</p>
                        </div>
                    </div>
                    <div class="flex gap-3 w-full md:w-auto shrink-0">
                        <a href="{{ route('login.role', ['role' => 'respondent']) }}" class="flex-1 md:flex-none text-center px-4 py-2 bg-white text-indigo-600 rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-indigo-50 transition-colors">
                            Login
                        </a>
                        <a href="{{ route('register', ['role' => 'respondent']) }}" class="flex-1 md:flex-none text-center px-4 py-2 bg-indigo-500 text-white rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-indigo-400 transition-colors border border-indigo-400">
                            Register
                        </a>
                    </div>
                </div>
            @endif
        @endguest

            <!-- Survey Content Area -->
            <div class="p-6 sm:p-10 min-h-[500px]">
                
                @if(!empty($survey->json_schema))
                    {{-- JSON Schema Survey (formRender) --}}
                    <form id="jsonSurveyForm" action="{{ route('surveys.submit', $survey->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        <div id="surveyContainer" class="bg-gray-50/50 p-8 rounded-2xl border border-gray-100 shadow-sm mb-6">
                            <div id="surveyLoading" class="flex flex-col items-center justify-center py-20">
                                <i class="fa-solid fa-spinner fa-spin text-4xl text-indigo-500 mb-4"></i>
                                <p class="text-gray-600 font-medium tracking-wide">Initializing Survey Experience...</p>
                            </div>
                        </div>

                        <!-- Data Privacy & Terms (GDPR/Data Privacy Laws Compliance) -->
                        <div id="termsContainer" class="bg-gray-50/50 p-8 rounded-2xl border border-gray-100 shadow-sm transition-all mb-6">
                            <label class="flex items-start cursor-pointer group">
                                <div class="flex items-center h-6">
                                    <input id="terms_and_conditions" name="terms_and_conditions" type="checkbox" required
                                        class="h-5 w-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 transition-all cursor-pointer">
                                </div>
                                <div class="ml-4 text-sm">
                                    <span class="font-bold text-gray-900 text-base group-hover:text-indigo-700 transition-colors">I agree to the Terms and Conditions</span>
                                    <p class="text-gray-500 mt-1 leading-relaxed">By submitting this survey, you acknowledge that your responses will be recorded and processed in accordance with our <a href="#" class="text-indigo-600 hover:underline font-bold">Data Privacy Policy</a>. We value your privacy and ensure your data is stored securely.</p>
                                </div>
                            </label>
                        </div>
                        <div id="submitContainer" class="pt-6 border-t border-gray-100 flex justify-end hidden">
                            <button type="submit" class="inline-flex items-center px-8 py-4 border border-transparent text-base font-bold rounded-xl shadow-lg text-white bg-gray-900 hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all transform hover:-translate-y-1">
                                <i class="fa-solid fa-paper-plane mr-2"></i> Submit Survey Responses
                            </button>
                        </div>
                    </form>

                    <script>
                        jQuery(function($) {
                            let surveyData = {!! $survey->json_schema !!};
                            
                            // Sometimes the database schema string contains already-serialized JSON which forms a string in Javascript instead of an array.
                            if (typeof surveyData === 'string') {
                                try {
                                    surveyData = JSON.parse(surveyData);
                                } catch(e) {
                                    console.error("Failed to parse stringified surveyData:", e);
                                }
                            }
                            
                            const container = $('#surveyContainer');
                            
                            $('#surveyLoading').hide();
                            $('#submitContainer').removeClass('hidden');
                            $('#jsonCaptchaContainer').removeClass('hidden');

                            const draftKey = `draft_survey_{{ $survey->id }}`;
                            let savedDraft = localStorage.getItem(draftKey);
                            let userData = null;
                            if (savedDraft) {
                                try {
                                    userData = JSON.parse(savedDraft);
                                } catch (e) {}
                            }

                            const typeMap = {
                                'select_one': 'radio-group',
                                'select_many': 'checkbox-group',
                                'rating': 'starRating',
                                'range': 'number',
                                'photo': 'file',
                                'note': 'paragraph',
                                'time': 'text',
                                'audio': 'audio_recorder',
                                'video': 'video_recorder',
                                'decimal': 'number',
                                'ranking': 'ranking_list'
                            };

                            const processedSchema = surveyData.map(field => {
                                const finalType = typeMap[field.type] || field.type;
                                const fieldClone = { ...field, type: finalType };

                                // Inline layout for radio/checkbox
                                if (['select_one', 'select_many', 'radio-group', 'checkbox-group'].includes(field.type)) {
                                    fieldClone.inline = true;
                                    fieldClone.className = (fieldClone.className || '') + ' preview-inline-group';
                                }

                                if (field.type === 'range') fieldClone.subtype = 'range';
                                if (field.type === 'time') fieldClone.subtype = 'time';
                                if (field.type === 'photo') {
                                    fieldClone.subtype = 'file';
                                    fieldClone.accept = 'image/*';
                                }
                                if (field.type === 'decimal') {
                                    fieldClone.subtype = 'number';
                                    fieldClone.step = 'any';
                                }
                                if (field.type === 'ranking') {
                                    fieldClone.className = (fieldClone.className || '') + ' ranking-list-container';
                                }
                                
                                return fieldClone;
                            });

                            const renderOptions = {
                                formData: processedSchema,
                                dataType: 'json',
                                render: true,
                                templates: {
                                    'starRating': function(fieldData) {
                                        const id = fieldData.name;
                                        return {
                                            field: `
                                            <div class="rating-wrapper bg-white py-6 px-4 rounded-2xl mb-4 border border-gray-100 shadow-sm">
                                                <label class="block text-sm font-bold text-gray-400 uppercase tracking-widest mb-4">${fieldData.label || 'Rating'}</label>
                                                <div class="likert-container" id="likert_${id}" style="display: flex !important; justify-content: space-between !important; gap: 8px !important;">
                                                    ${[1,2,3,4,5].map(i => `<div class="likert-item" data-value="${i}" onclick="setRendererLikertValue('${id}', ${i})" style="flex:1; text-align:center; padding:12px; border:1px solid #e5e7eb; border-radius:8px; cursor:pointer; font-weight:700;">${i}</div>`).join('')}
                                                </div>
                                                <input type="hidden" name="${id}" id="input_${id}" required="${fieldData.required ? 'true' : 'false'}" value="">
                                            </div>`
                                        };
                                    },
                                    'ranking_list': function(fieldData) {
                                        const id = fieldData.name;
                                        const options = fieldData.values || [];
                                        return {
                                            field: `
                                            <div class="ranking-wrapper bg-white p-6 rounded-2xl mb-4 border border-gray-100 shadow-sm">
                                                <label class="block text-sm font-bold text-gray-400 uppercase tracking-widest mb-4">${fieldData.label || 'Rank the following'}</label>
                                                <div class="grid grid-cols-2 gap-4">
                                                    <div>
                                                        <span class="text-[10px] font-black text-indigo-500 uppercase tracking-widest block mb-2">Choices</span>
                                                        <div id="pool_${id}" class="rank-pool" style="min-height:100px; padding:8px; background:#f8fafc; border:2px dashed #e2e8f0; border-radius:12px;">
                                                            ${options.map(opt => `
                                                                <div class="rank-item" data-value="${opt.value}" onclick="togglePublicRankItem('${id}', this)">
                                                                    ${opt.label}
                                                                </div>
                                                            `).join('')}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <span class="text-[10px] font-black text-green-500 uppercase tracking-widest block mb-2">Your Order</span>
                                                        <div id="ranked_${id}" class="rank-ordered" style="min-height:100px; padding:8px; background:#f8fafc; border:2px dashed #e2e8f0; border-radius:12px;"></div>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="${id}" id="input_${id}" value="">
                                            </div>`,
                                            onRender: () => setupPublicRankingUI(id)
                                        };
                                    },
                                    'audio_recorder': function(fieldData) {
                                        const id = fieldData.name;
                                        return {
                                            field: `
                                            <div class="recorder-dashboard mb-4" style="background:#1e293b; color:white; padding:24px; border-radius:24px; text-align:center;">
                                                <div class="recorder-status" id="status_${id}" style="font-size:10px; font-weight:900; color:#94a3b8; text-transform:uppercase; margin-bottom:16px;">Voice Response</div>
                                                <div class="recorder-timer" id="timer_${id}" style="font-family:monospace; font-size:32px; font-weight:700; margin:16px 0;">00:00</div>
                                                <div class="flex items-center justify-center space-x-6 gap-6" style="display:flex; justify-content:center; align-items:center;">
                                                    <div id="start_${id}" class="record-btn" style="width:64px; height:64px; background:#ef4444; border-radius:999px; display:flex !important; align-items:center; justify-content:center; cursor:pointer; border:4px solid rgba(255,255,255,0.1);">
                                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="white"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/><path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>
                                                    </div>
                                                    <div id="stop_${id}" class="record-btn bg-gray-600 hidden" style="width:64px; height:64px; background:#4b5563; border-radius:12px; display:none; align-items:center; justify-content:center; cursor:pointer;">
                                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="white"><path d="M6 6h12v12H6z"/></svg>
                                                    </div>
                                                </div>
                                                <audio id="player_${id}" controls class="hidden w-full mt-6" style="display:none; width:100%; margin-top:24px;"></audio>
                                                <button type="button" id="retake_${id}" class="mt-4 text-[10px] uppercase font-black text-indigo-400 hidden" style="display:none; background:none; border:none; color:#818cf8; cursor:pointer;">Retake Recording</button>
                                                <input type="hidden" name="${id}_blob" id="blob_${id}">
                                            </div>`,
                                            onRender: () => setupRecorder(id, 'audio')
                                        };
                                    },
                                    'video_recorder': function(fieldData) {
                                        const id = fieldData.name;
                                        return {
                                            field: `
                                            <div class="recorder-dashboard mb-4" style="background:#1e293b; color:white; padding:0; border-radius:24px; overflow:hidden; position:relative;">
                                                <div class="relative aspect-video bg-black" style="background:black; aspect-ratio:16/9; position:relative;">
                                                    <video id="preview_${id}" autoplay muted playsinline style="width:100%; height:100%; object-fit:cover; opacity:0.5;"></video>
                                                    <video id="player_${id}" controls style="display:none; width:100%; height:100%; object-fit:contain;"></video>
                                                    <div class="absolute inset-0 flex flex-col items-center justify-center" style="position:absolute; inset:0; display:flex; flex-direction:column; items-center; justify-center;">
                                                        <div class="recorder-status" id="status_${id}" style="font-size:10px; font-weight:900; color:#94a3b8; text-transform:uppercase; margin-bottom:8px;">Video Response</div>
                                                        <div class="recorder-timer" id="timer_${id}" style="font-family:monospace; font-size:24px; font-weight:700; margin-bottom:16px;">00:00</div>
                                                        <div id="start_${id}" class="record-btn" style="width:56px; height:56px; background:#ef4444; border-radius:999px; display:flex !important; align-items:center; justify-content:center; cursor:pointer; border:4px solid rgba(255,255,255,0.2);">
                                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="white"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>
                                                        </div>
                                                        <div id="stop_${id}" class="record-btn bg-gray-600 hidden" style="width:56px; height:56px; background:#4b5563; border-radius:12px; display:none; align-items:center; justify-content:center; cursor:pointer;">
                                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="white"><path d="M6 6h12v12H6z"/></svg>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="button" id="retake_${id}" class="absolute bottom-4 right-4" style="display:none; position:absolute; bottom:16px; right:16px; background:rgba(0,0,0,0.5); color:white; padding:8px 16px; border-radius:24px; border:none; font-size:10px; font-weight:900; text-transform:uppercase; cursor:pointer;">Retake</button>
                                                <input type="hidden" name="${id}_blob" id="blob_${id}">
                                            </div>`,
                                            onRender: () => setupRecorder(id, 'video')
                                        };
                                    }
                                }
                            };

                            const formRenderInstance = container.formRender(renderOptions);
                            
                            // Recorder Registry to store blobs
                            const recorderBlobs = {};

                            function setRendererLikertValue(id, value) {
                                const container = jQuery(`#likert_${id}`);
                                const input = jQuery(`#input_${id}`);
                                container.find('.likert-item').removeClass('active');
                                container.find(`.likert-item[data-value="${value}"]`).addClass('active');
                                input.val(value);
                            }

                            function togglePublicRankItem(id, el) {
                                const pool = document.getElementById(`pool_${id}`);
                                const ranked = document.getElementById(`ranked_${id}`);
                                
                                if (el.parentElement.id === `pool_${id}`) {
                                    const badge = document.createElement('span');
                                    badge.className = 'rank-badge';
                                    badge.innerText = ranked.children.length + 1;
                                    el.prepend(badge);
                                    ranked.appendChild(el);
                                } else {
                                    const badge = el.querySelector('.rank-badge');
                                    if (badge) badge.remove();
                                    pool.appendChild(el);
                                    Array.from(ranked.children).forEach((child, index) => {
                                        child.querySelector('.rank-badge').innerText = index + 1;
                                    });
                                }
                                
                                const values = Array.from(ranked.children).map(child => child.dataset.value);
                                document.getElementById(`input_${id}`).value = values.join(',');
                            }

                            function setupRecorder(id, type) {
                                let mediaRecorder;
                                let chunks = [];
                                let timerInterval;
                                let seconds = 0;

                                const startBtn = document.getElementById(`start_${id}`);
                                const stopBtn = document.getElementById(`stop_${id}`);
                                const retakeBtn = document.getElementById(`retake_${id}`);
                                const player = document.getElementById(`player_${id}`);
                                const preview = document.getElementById(`preview_${id}`);
                                const statusLabel = document.getElementById(`status_${id}`);
                                const timerLabel = document.getElementById(`timer_${id}`);
                                const blobInput = document.getElementById(`blob_${id}`);

                                if (!startBtn) return;

                                function updateTimer() {
                                    seconds++;
                                    const mins = Math.floor(seconds / 60).toString().padStart(2, '0');
                                    const secs = (seconds % 60).toString().padStart(2, '0');
                                    timerLabel.innerText = `${mins}:${secs}`;
                                }

                                retakeBtn.onclick = () => {
                                    player.classList.add('hidden');
                                    retakeBtn.classList.add('hidden');
                                    startBtn.classList.remove('hidden');
                                    if (preview) preview.classList.remove('hidden');
                                    statusLabel.innerText = type === 'video' ? 'Video Ready' : 'Voice Ready';
                                    timerLabel.innerText = '00:00';
                                    seconds = 0;
                                    blobInput.value = '';
                                };

                                startBtn.onclick = async () => {
                                    try {
                                        const constraints = { audio: true, video: type === 'video' };
                                        const stream = await navigator.mediaDevices.getUserMedia(constraints);
                                        
                                        if (preview) { 
                                            preview.srcObject = stream; 
                                            preview.classList.remove('hidden'); 
                                        }
                                        player.classList.add('hidden');
                                        
                                        mediaRecorder = new MediaRecorder(stream);
                                        mediaRecorder.ondataavailable = (e) => {
                                            if (e.data.size > 0) chunks.push(e.data);
                                        };
                                        mediaRecorder.onstop = () => {
                                            clearInterval(timerInterval);
                                            const blob = new Blob(chunks, { type: type === 'audio' ? 'audio/ogg; codecs=opus' : 'video/webm' });
                                            
                                            // Handle saving blob data
                                            const reader = new FileReader();
                                            reader.readAsDataURL(blob);
                                            reader.onloadend = () => {
                                                blobInput.value = reader.result;
                                            };

                                            player.src = URL.createObjectURL(blob);
                                            player.classList.remove('hidden');
                                            if (preview) preview.classList.add('hidden');
                                            
                                            stream.getTracks().forEach(track => track.stop());
                                            stopBtn.classList.add('hidden');
                                            retakeBtn.classList.remove('hidden');
                                            statusLabel.innerText = 'Response Captured';
                                        };
                                        
                                        mediaRecorder.start();
                                        startBtn.classList.add('hidden');
                                        stopBtn.classList.remove('hidden');
                                        stopBtn.classList.add('recording');
                                        statusLabel.innerText = 'Now Recording...';
                                        
                                        seconds = 0;
                                        timerInterval = setInterval(updateTimer, 1000);
                                        chunks = [];
                                    } catch (err) { 
                                        alert("Permission denied or device error: " + err.message); 
                                    }
                                };
                                stopBtn.onclick = () => { 
                                    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                                        mediaRecorder.stop(); 
                                    }
                                    stopBtn.classList.remove('recording');
                                };
                            }
                            
                            // Restore DOM manually if we have a draft
                            if (userData && userData.length > 0) {
                                let hasRestored = false;
                                userData.forEach(function(item) {
                                    if (item.name && item.userData) {
                                        let input = $(`[name="${item.name}"], [name="${item.name}[]"]`);
                                        if (input.length > 0) {
                                            hasRestored = true;
                                            if (input.attr('type') === 'checkbox' || input.attr('type') === 'radio') {
                                                input.each(function() {
                                                    let values = Array.isArray(item.userData) ? item.userData : [item.userData];
                                                    if (values.includes($(this).val())) {
                                                        $(this).prop('checked', true);
                                                    }
                                                });
                                            } else {
                                                let val = Array.isArray(item.userData) ? item.userData[0] : item.userData;
                                                input.val(val);
                                            }
                                        }
                                    }
                                });
                                
                                if (hasRestored) {
                                    $('#surveyContainer').prepend('<div class="bg-indigo-50 text-indigo-700 p-3 rounded-lg text-sm font-medium mb-4 flex justify-between items-center shadow-sm border border-indigo-100" id="draftToast"><div class="flex items-center"><i class="fa-solid fa-cloud-arrow-down mr-2"></i> Your previous draft has been restored from local storage (Offline Draft Recovery Active).</div><button type="button" onclick="$(\'#draftToast\').fadeOut()" class="text-indigo-500 hover:text-indigo-800"><i class="fa-solid fa-xmark"></i></button></div>');
                                }
                            }

                            // Auto-save interval (every 3 seconds)
                            setInterval(function() {
                                if (formRenderInstance && formRenderInstance.userData) {
                                    localStorage.setItem(draftKey, JSON.stringify(formRenderInstance.userData));
                                }
                            }, 3000);

                            $('#jsonSurveyForm').on('submit', function(e) {
                                e.preventDefault();
                                
                                const _isPaid = {!! json_encode((bool)$survey->is_paid) !!};
                                const _isGuest = {!! json_encode(auth()->guest()) !!};
                                const _budgetExhausted = {!! json_encode((bool)$budgetExhausted) !!};

                                const performSubmit = () => {
                                    const submitBtn = $('#jsonSurveyForm').find('button[type="submit"]');
                                    const originalText = submitBtn.html();
                                    submitBtn.html('<i class="fa-solid fa-spinner fa-spin mr-2"></i> Submitting...').prop('disabled', true);

                                    const formData = new FormData(document.getElementById('jsonSurveyForm'));
                                    formData.append('is_json_submission', '1');
                                    formData.append('json_data', JSON.stringify(formRenderInstance.userData));

                                    $(document.getElementById('jsonSurveyForm')).find('input[type="file"]').each(function() {
                                        if (this.files.length > 0) {
                                            formData.append(this.name, this.files[0]);
                                        }
                                    });

                                    for (const id in recorderBlobs) {
                                        if (recorderBlobs[id]) {
                                            const ext = recorderBlobs[id].type.includes('audio') ? 'ogg' : 'webm';
                                            formData.append(id, recorderBlobs[id], `${id}_recording.${ext}`);
                                        }
                                    }

                                    fetch(document.getElementById('jsonSurveyForm').action, {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json'
                                        },
                                        body: formData
                                    })
                                    .then(response => {
                                        if(response.status === 429) {
                                            throw new Error("Too Many Requests: Please wait before submitting another survey.");
                                        }
                                        return response.json().then(data => ({ status: response.status, body: data }));
                                    })
                                    .then(res => {
                                        const data = res.body;
                                        if(res.status === 422 || !data.success) {
                                            if (data.message && data.message.includes('terms')) {
                                                Swal.fire('Required', 'Please agree to the Terms and Conditions to proceed.', 'info');
                                            } else {
                                                Swal.fire('Error', data.message || 'Validation failed.', 'error');
                                            }
                                            submitBtn.html(originalText).prop('disabled', false);
                                        } else {
                                            window.location.reload();
                                        }
                                    })
                                    .catch(err => {
                                        console.error(err);
                                        Swal.fire('Error', err.message || 'Error submitting survey, please try again.', 'error');
                                        submitBtn.html(originalText).prop('disabled', false);
                                    });
                                };

                                if (_isPaid && _isGuest && !_budgetExhausted) {
                                    Swal.fire({
                                        title: 'Wait! This is a PAID survey',
                                        html: "Since you're not logged in, you won't receive the <b>KES {{ number_format($survey->reward_per_response, 0) }}</b> reward.<br><br>Would you like to submit anyway?",
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonText: 'Yes, Submit Anyway',
                                        cancelButtonText: 'No, Let me Login',
                                        confirmButtonColor: '#4f46e5',
                                        cancelButtonColor: '#1e293b',
                                        reverseButtons: true,
                                        customClass: {
                                            popup: 'rounded-3xl',
                                            confirmButton: 'rounded-xl font-bold px-6 py-3',
                                            cancelButton: 'rounded-xl font-bold px-6 py-3'
                                        }
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            performSubmit();
                                        } else if (result.dismiss === Swal.DismissReason.cancel) {
                                            window.location.href = "{{ route('login.role', ['role' => 'respondent']) }}";
                                        }
                                    });
                                } else {
                                    performSubmit();
                                }
                            });
                        });
                    </script>

                @else
                    {{-- Legacy Question-Based Survey Form --}}
                    <form id="legacySurveyForm" action="{{ route('surveys.submit', $survey->id) }}" method="POST" enctype="multipart/form-data" class="space-y-10">
                        @csrf
                        
                        @forelse ($survey->questions()->orderBy('position')->get() as $index => $question)
                            <div class="bg-gray-50/50 p-8 rounded-2xl border border-gray-100 shadow-sm transition-all hover:shadow-md hover:bg-white group">
                                <div class="flex items-start mb-6">
                                    <div class="flex-shrink-0 mr-4 mt-1">
                                        <div class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm">
                                            {{ $index + 1 }}
                                        </div>
                                    </div>
                                    <div class="flex-grow">
                                        <label class="block text-lg font-bold text-gray-900 leading-snug">
                                            {{ $question->text }}
                                            @if($question->required)
                                                <span class="text-red-500 ml-1" title="Required">*</span>
                                            @endif
                                        </label>
                                    </div>
                                </div>

                                <div class="ml-12">
                                    @php $inputName = 'question_' . $question->id; @endphp

                                    @if ($question->type === 'text')
                                        <input type="text" name="{{ $inputName }}" {{ $question->required ? 'required' : '' }}
                                            class="w-full px-5 py-3 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-gray-700 shadow-sm font-medium">
                                    
                                    @elseif ($question->type === 'textarea')
                                        <textarea name="{{ $inputName }}" rows="4" {{ $question->required ? 'required' : '' }}
                                            class="w-full px-5 py-3 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-gray-700 shadow-sm font-medium"></textarea>

                                    @elseif ($question->type === 'radio')
                                        <div class="space-y-3">
                                            @php $options = is_array($question->options) ? $question->options : json_decode($question->options, true) ?? []; @endphp
                                            @foreach ($options as $option)
                                                <label class="flex items-center p-4 bg-white border border-gray-200 rounded-xl cursor-pointer hover:border-indigo-300 hover:bg-indigo-50/30 transition-all">
                                                    <input type="radio" name="{{ $inputName }}" value="{{ $option }}" {{ $question->required ? 'required' : '' }}
                                                        class="w-5 h-5 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                                    <span class="ml-4 text-gray-700 font-medium">{{ $option }}</span>
                                                </label>
                                            @endforeach
                                        </div>

                                    @elseif ($question->type === 'checkbox')
                                        <div class="space-y-3">
                                            @php $options = is_array($question->options) ? $question->options : json_decode($question->options, true) ?? []; @endphp
                                            @foreach ($options as $option)
                                                <label class="flex items-center p-4 bg-white border border-gray-200 rounded-xl cursor-pointer hover:border-indigo-300 hover:bg-indigo-50/30 transition-all">
                                                    <input type="checkbox" name="{{ $inputName }}[]" value="{{ $option }}"
                                                        class="w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                                    <span class="ml-4 text-gray-700 font-medium">{{ $option }}</span>
                                                </label>
                                            @endforeach
                                        </div>

                                    @elseif ($question->type === 'integer' || $question->type === 'number')
                                        <input type="number" name="{{ $inputName }}" {{ $question->required ? 'required' : '' }}
                                            class="w-full md:w-1/2 px-5 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all text-gray-700 shadow-sm">

                                    @elseif ($question->type === 'email')
                                        <input type="email" name="{{ $inputName }}" {{ $question->required ? 'required' : '' }}
                                            class="w-full px-5 py-3 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all text-gray-700 shadow-sm font-medium" placeholder="your@email.com">

                                    @elseif ($question->type === 'tel')
                                        <input type="tel" name="{{ $inputName }}" {{ $question->required ? 'required' : '' }}
                                            class="w-full md:w-1/2 px-5 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all text-gray-700 shadow-sm">

                                    @elseif ($question->type === 'geo')
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <i class="fa-solid fa-location-dot text-gray-500"></i>
                                            </div>
                                            <input type="text" name="{{ $inputName }}" placeholder="Enter location or coordinates" {{ $question->required ? 'required' : '' }}
                                                class="w-full pl-12 pr-5 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all text-gray-700 shadow-sm">
                                        </div>

                                    @elseif ($question->type === 'video')
                                        <div class="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl bg-white hover:border-indigo-400 transition-colors">
                                            <div class="space-y-2 text-center">
                                                <i class="fa-solid fa-video text-gray-500 text-3xl mb-2"></i>
                                                <div class="flex text-sm text-gray-600 justify-center">
                                                    <label class="relative cursor-pointer bg-white rounded-md font-bold text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-500 text-center">
                                                        <span>Upload a video file</span>
                                                        <input type="file" name="{{ $inputName }}" accept="video/*" class="sr-only" {{ $question->required ? 'required' : '' }}>
                                                    </label>
                                                </div>
                                                <p class="text-xs text-gray-600">MP4, WebM, OGG up to 50MB</p>
                                            </div>
                                        </div>

                                    @elseif ($question->type === 'audio')
                                        <div class="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl bg-white hover:border-indigo-400 transition-colors">
                                            <div class="space-y-2 text-center">
                                                <i class="fa-solid fa-microphone text-gray-500 text-3xl mb-2"></i>
                                                <div class="flex text-sm text-gray-600 justify-center">
                                                    <label class="relative cursor-pointer bg-white rounded-md font-bold text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-500 text-center">
                                                        <span>Upload an audio file</span>
                                                        <input type="file" name="{{ $inputName }}" accept="audio/*" class="sr-only" {{ $question->required ? 'required' : '' }}>
                                                    </label>
                                                </div>
                                                <p class="text-xs text-gray-600">MP3, WAV, OGG up to 20MB</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-16 bg-gray-50 rounded-2xl border border-dashed border-gray-300">
                                <i class="fa-solid fa-clipboard-question text-4xl text-gray-300 mb-3"></i>
                                <p class="text-gray-600 font-medium">This survey has no questions defined yet.</p>
                            </div>
                        @endforelse

                        @if($survey->questions()->count() > 0)
                            <!-- Data Privacy & Terms (GDPR/Data Privacy Laws Compliance) -->
                            <div class="bg-gray-50/50 p-8 rounded-2xl border border-gray-100 shadow-sm transition-all mb-6">
                                <label class="flex items-start cursor-pointer group">
                                    <div class="flex items-center h-6">
                                        <input id="terms_and_conditions_legacy" name="terms_and_conditions" type="checkbox" required
                                            class="h-5 w-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 transition-all cursor-pointer shadow-sm">
                                    </div>
                                    <div class="ml-4 text-sm">
                                        <span class="font-bold text-gray-900 text-base group-hover:text-indigo-700 transition-colors">I agree to the Terms and Conditions</span>
                                        <p class="text-gray-500 mt-1 leading-relaxed">By submitting this survey, you acknowledge that your responses will be recorded and processed in accordance with our <a href="#" class="text-indigo-600 hover:underline font-bold">Data Privacy Policy</a>. We value your privacy and ensure your data is stored securely.</p>
                                    </div>
                                </label>
                            </div>

                            <div class="pt-6 border-t border-gray-100 flex justify-end">
                                <button type="submit" class="inline-flex items-center px-8 py-4 border border-transparent text-base font-bold rounded-xl shadow-lg text-white bg-gray-900 hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all transform hover:-translate-y-1">
                                    <i class="fa-solid fa-paper-plane mr-2"></i> Submit Survey Responses
                                </button>
                            </div>
                        @endif
                    </form>

                    <script>
                        jQuery(function($) {
                            const legacyDraftKey = `legacy_draft_survey_{{ $survey->id }}`;
                            const savedLegacyDraft = localStorage.getItem(legacyDraftKey);
                            
                            if (savedLegacyDraft) {
                                try {
                                    const parsed = JSON.parse(savedLegacyDraft);
                                    let hasRestored = false;
                                    
                                    for (let key in parsed) {
                                        let input = $(`[name="${key}"], [name="${key}[]"]`);
                                        if (input.length > 0) {
                                            hasRestored = true;
                                            if (input.attr('type') === 'checkbox' || input.attr('type') === 'radio') {
                                                input.each(function() {
                                                    if (Array.isArray(parsed[key])) {
                                                        if (parsed[key].includes($(this).val())) {
                                                            $(this).prop('checked', true);
                                                        }
                                                    } else {
                                                        if ($(this).val() == parsed[key]) {
                                                            $(this).prop('checked', true);
                                                        }
                                                    }
                                                });
                                            } else {
                                                input.val(parsed[key]);
                                            }
                                        }
                                    }
                                    
                                    if (hasRestored) {
                                        $('#legacySurveyForm').prepend('<div class="bg-indigo-50 text-indigo-700 p-3 rounded-lg text-sm font-medium mb-6 flex justify-between items-center shadow-sm border border-indigo-100" id="draftToast"><div class="flex items-center"><i class="fa-solid fa-cloud-arrow-down mr-2"></i> Your previous draft has been restored from local storage (Offline Draft Recovery Active).</div><button type="button" onclick="$(\'#draftToast\').fadeOut()" class="text-indigo-500 hover:text-indigo-800"><i class="fa-solid fa-xmark"></i></button></div>');
                                    }
                                } catch (e) {
                                    console.error("Error restoring legacy draft:", e);
                                }
                            }

                            // Auto-save every 5 seconds
                            setInterval(function() {
                                let draftObj = {};
                                $('#legacySurveyForm').serializeArray().forEach(function(item) {
                                    if (item.name.startsWith('question_')) {
                                        if (draftObj[item.name]) {
                                            if (!Array.isArray(draftObj[item.name])) {
                                                draftObj[item.name] = [draftObj[item.name]];
                                            }
                                            draftObj[item.name].push(item.value);
                                        } else {
                                            draftObj[item.name] = item.value;
                                        }
                                    }
                                });
                                localStorage.setItem(legacyDraftKey, JSON.stringify(draftObj));
                            }, 5000);

                            $('#legacySurveyForm').on('submit', function(e) {
                                const _isPaid = {!! json_encode((bool)$survey->is_paid) !!};
                                const _isGuest = {!! json_encode(auth()->guest()) !!};
                                const _budgetExhausted = {!! json_encode((bool)$budgetExhausted) !!};

                                if (_isPaid && _isGuest && !_budgetExhausted) {
                                    e.preventDefault();
                                    Swal.fire({
                                        title: 'Wait! This is a PAID survey',
                                        html: "Since you're not logged in, you won't receive the <b>KES {{ number_format($survey->reward_per_response, 0) }}</b> reward.<br><br>Would you like to submit anyway?",
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonText: 'Yes, Submit Anyway',
                                        cancelButtonText: 'No, Let me Login',
                                        confirmButtonColor: '#4f46e5',
                                        cancelButtonColor: '#1e293b',
                                        reverseButtons: true,
                                        customClass: {
                                            popup: 'rounded-3xl',
                                            confirmButton: 'rounded-xl font-bold px-6 py-3',
                                            cancelButton: 'rounded-xl font-bold px-6 py-3'
                                        }
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            $('#legacySurveyForm').off('submit').submit();
                                        } else if (result.dismiss === Swal.DismissReason.cancel) {
                                            window.location.href = "{{ route('login.role', ['role' => 'respondent']) }}";
                                        }
                                    });
                                }
                            });
                        });
                    </script>
                @endif
            </div>

        @endif
    </div>
</div>
@endsection
