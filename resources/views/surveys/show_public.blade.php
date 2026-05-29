@extends('layouts.app')

@section('title', $survey->title)

@section('head')
    @if(!empty($survey->json_schema) && $survey->json_schema !== '[]')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jQuery-formBuilder/3.4.2/form-render.min.js"></script>
        <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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

        .rendered-form input:focus,
        .rendered-form textarea:focus,
        .rendered-form select:focus {
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1) !important;
            outline: none !important;
        }

        .rendered-form>.form-group>label {
            display: block !important;
            font-size: 1.125rem !important;
            font-weight: 700 !important;
            color: #111827 !important;
            margin-bottom: 0.75rem !important;
            line-height: 1.5 !important;
        }

        /* Fix for inline labels in radio/checkbox groups */
        .rendered-form .radio-inline label,
        .rendered-form .checkbox-inline label {
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

        .preview-inline-group>.radio-inline,
        .preview-inline-group>.checkbox-inline {
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

        .location-map-container {
            width: 100%;
            height: 250px;
            border-radius: 1rem;
            z-index: 1;
            border: 2px solid #f1f5f9;
            display: none;
            /* Hidden by default, shown only if Leaflet loads */
        }

        .signature-canvas {
            touch-action: none;
            cursor: crosshair;
            border: 2px dashed #cbd5e1;
            border-radius: 1rem;
            background: #ffffff;
            width: 100%;
            height: 200px;
            display: block;
        }

        .qr-reader-container {
            background: #f8fafc;
            border-radius: 1rem;
            overflow: hidden;
            border: 2px solid #e2e8f0;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        #qr-canvas {
            width: 100% !important;
            border-radius: 1rem;
        }

        .btn-scanner-start {
            background: #4f46e5;
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 1px;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-scanner-start:hover {
            background: #4338ca;
            transform: translateY(-2px);
        }

        .pulse-loc {
            animation: pulse-loc 2s infinite;
        }

        @keyframes pulse-loc {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.7);
            }

            70% {
                transform: scale(1);
                box-shadow: 0 0 0 10px rgba(79, 70, 229, 0);
            }

            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(79, 70, 229, 0);
            }
        }

        /* Choice Highlighting */
        .radio-inline.active-choice,
        .checkbox-inline.active-choice,
        #legacySurveyForm label.active-choice {
            background: #eef2ff !important;
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1) !important;
            color: #4f46e5 !important;
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

        .likert-item:hover {
            background: #f1f5f9;
        }

        .likert-item.active {
            background: #4f46e5 !important;
            color: #ffffff !important;
            border-color: #4f46e5 !important;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4) !important;
            transform: scale(1.05);
        }

        .rank-pool,
        .rank-ordered {
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
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .rank-item:hover {
            border-color: #6366f1;
            transform: translateY(-1px);
        }

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

        }

        /* Kobo Style Media Buttons */
        .kobo-media-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }

        .kobo-record-btn {
            background-color: #4a7ba5 !important;
            color: white !important;
            padding: 10px 18px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            cursor: pointer !important;
            border: none !important;
            transition: all 0.2s ease !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
            height: 42px !important;
        }

        .kobo-record-btn:hover {
            background-color: #3b6385 !important;
            transform: translateY(-1px);
        }

        .kobo-record-btn.recording {
            background-color: #ef4444 !important;
            animation: kobo-pulse 1.5s infinite;
        }

        @keyframes kobo-pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }

            100% {
                opacity: 1;
            }
        }

        .kobo-upload-btn {
            background-color: #d0e9f8 !important;
            color: #005fa8 !important;
            padding: 10px 18px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            cursor: pointer !important;
            border: none !important;
            transition: all 0.2s ease !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
            height: 42px !important;
        }

        .kobo-upload-btn:hover {
            background-color: #b8dcf2 !important;
            transform: translateY(-1px);
        }

        .kobo-status-badge {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 8px;
            display: block;
        }

        .kobo-timer {
            font-family: monospace;
            font-size: 14px;
            font-weight: 700;
            color: #475569;
            margin-left: 8px;
        }

        /* Repeat Group */
        .repeat-entry {
            border-left: 3px solid #6366f1;
            margin-bottom: 1rem;
            padding: 1rem;
            padding-left: 1.5rem;
            background: #fafafe;
            border-radius: 0 1rem 1rem 0;
        }

        .repeat-entry-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        /* Likert Matrix */
        .likert-matrix-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .likert-matrix-table th {
            padding: 0.75rem 0.5rem;
            text-align: center;
            font-size: 0.7rem;
            font-weight: 800;
            color: #6b7280;
            text-transform: uppercase;
            background: #f9fafb;
        }

        .likert-matrix-table th:first-child {
            text-align: left;
            border-radius: 0.75rem 0 0 0;
        }

        .likert-matrix-table th:last-child {
            border-radius: 0 0.75rem 0 0;
        }

        .likert-matrix-table td {
            padding: 0.75rem 0.5rem;
            text-align: center;
            border-top: 1px solid #f3f4f6;
        }

        .likert-matrix-table td:first-child {
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
        }

        .likert-matrix-table td input[type="radio"] {
            accent-color: #4f46e5;
            width: 1.15rem;
            height: 1.15rem;
            cursor: pointer;
        }

        .likert-matrix-table tr:hover td {
            background: #f5f3ff;
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
                        <p class="mt-3 text-indigo-100/90 font-medium leading-relaxed max-w-2xl">{{ $survey->description }}
                        </p>

                        @if($survey->is_paid)
                            @if($budgetExhausted)
                                <div
                                    class="mt-4 inline-flex items-center px-4 py-2 rounded-xl bg-red-600 text-white shadow-lg shadow-red-600/20 ring-1 ring-white/30">
                                    <i class="fa-solid fa-triangle-exclamation mr-2 text-red-200"></i>
                                    <span class="text-[10px] font-black uppercase tracking-widest leading-none">Reward Budget
                                        Exhausted</span>
                                </div>
                            @else
                                <div
                                    class="mt-4 inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/20 ring-1 ring-white/30">
                                    <i class="fa-solid fa-sack-dollar mr-2 text-emerald-200"></i>
                                    <span class="text-[10px] font-black uppercase tracking-widest leading-none">Paid Survey: Earn
                                        {{ number_format($survey->reward_per_response, 0) }}
                                        {{ $survey->reward_currency ?? 'KES' }}</span>
                                </div>
                            @endif
                        @elseif($survey->type === \App\Enums\SurveyType::Public)
                            <div
                                class="mt-4 inline-flex items-center px-4 py-2 rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-600/20 ring-1 ring-white/30 transition-all hover:scale-105">
                                <i class="fa-solid fa-globe mr-2 text-blue-200"></i>
                                <span class="text-[10px] font-black uppercase tracking-widest leading-none">Public Survey</span>
                            </div>
                        @endif
                    </div>
                    <div class="hidden sm:block">
                        <span
                            class="inline-flex items-center px-4 py-2 rounded-full text-xs font-bold bg-white/10 text-white border border-white/20 backdrop-blur-sm">
                            <i class="fa-solid fa-tag mr-2"></i>
                            {{ ucfirst($survey->category instanceof \BackedEnum ? $survey->category->value : ($survey->category ?? 'General')) }}
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
                    <div id="guest-participation-gate"
                        class="bg-gray-50/50 rounded-3xl p-12 text-center border-2 border-dashed border-gray-200 m-6 animate-fade-in relative">
                        <div
                            class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-sm border border-gray-100">
                            <i class="fa-solid fa-user-plus text-3xl text-indigo-500"></i>
                        </div>
                        <h2 class="text-3xl font-black text-gray-900 mb-4 tracking-tight">{{ __('Ready to Contribute?') }}</h2>
                        <p class="text-gray-600 mb-8 max-w-md mx-auto font-medium">
                            @if($survey->is_paid && !$budgetExhausted)
                                {{ __('Register or Login to receive your') }} <b>{{ number_format($survey->reward_per_response, 0) }}
                                    {{ $survey->reward_currency ?? 'KES' }}</b> {{ __('reward.') }}
                            @else
                                {{ __('Join our community of contributors to keep track of your survey history and impact.') }}
                            @endif
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <button
                                onclick="urgeLogin('{{ request()->fullUrl() }}', {{ $survey->is_paid ? 'true' : 'false' }}, '{{ number_format($survey->reward_per_response, 0) }}', '{{ $survey->reward_currency ?? 'KES' }}')"
                                class="px-8 py-4 bg-indigo-600 text-white text-[10px] font-black uppercase tracking-widest rounded-xl shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all transform hover:-translate-y-1">
                                <i class="fa-solid fa-right-to-bracket mr-2"></i> {{ __('Login / Register') }}
                            </button>
                        </div>
                    </div>
                @endguest

                <!-- Survey Content Area -->
                <div id="survey-content-wrapper" class="p-6 sm:p-10 min-h-[500px] {{ Auth::check() ? '' : 'hidden' }}">

                    @if(!empty($survey->json_schema) && $survey->json_schema !== '[]')
                        {{-- JSON Schema Survey (formRender) --}}
                        <form id="jsonSurveyForm"
                            action="{{ route('surveys.submit', [$survey->id, 'invite_token' => request('invite_token')]) }}"
                            method="POST" enctype="multipart/form-data" class="space-y-6">
                            @csrf
                            <div id="surveyContainer" class="bg-gray-50/50 p-8 rounded-2xl border border-gray-100 shadow-sm mb-6">
                                <div id="surveyLoading" class="flex flex-col items-center justify-center py-20">
                                    <i class="fa-solid fa-spinner fa-spin text-4xl text-indigo-500 mb-4"></i>
                                    <p class="text-gray-600 font-medium tracking-wide">Initializing Survey Experience...</p>
                                </div>
                            </div>

                            <!-- Data Privacy & Terms (GDPR/Data Privacy Laws Compliance) -->
                            <div id="termsContainer"
                                class="bg-gray-50/50 p-8 rounded-2xl border border-gray-100 shadow-sm transition-all mb-6">
                                <label class="flex items-start cursor-pointer group">
                                    <div class="flex items-center h-6">
                                        <input id="terms_and_conditions" name="terms_and_conditions" type="checkbox" required
                                            class="h-5 w-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 transition-all cursor-pointer">
                                    </div>
                                    <div class="ml-4 text-sm">
                                        <span
                                            class="font-bold text-gray-900 text-base group-hover:text-indigo-700 transition-colors">
                                            I agree to the <a href="{{ route('terms') }}" target="_blank"
                                                onclick="event.stopPropagation();"
                                                class="text-indigo-600 hover:underline font-bold">Terms and Conditions</a>
                                        </span>
                                        <p class="text-gray-500 mt-1 leading-relaxed">By submitting this survey, you acknowledge
                                            that your responses will be recorded and processed in accordance with our <a
                                                href="{{ route('privacy') }}" target="_blank" onclick="event.stopPropagation();"
                                                class="text-indigo-600 hover:underline font-bold">Data Privacy Policy</a>. We value
                                            your privacy and ensure your data is stored securely.</p>
                                    </div>
                                </label>
                            </div>
                            <div id="submitContainer" class="pt-6 border-t border-gray-100 flex justify-end gap-4 hidden">
                                <button type="button" onclick="window.resetSurvey()"
                                    class="inline-flex items-center px-8 py-4 border border-gray-300 text-base font-bold rounded-xl shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                                    <i class="fa-solid fa-rotate-left mr-2"></i> Reset Answers
                                </button>
                                <button type="submit"
                                    class="inline-flex items-center px-8 py-4 border border-transparent text-base font-bold rounded-xl shadow-lg text-white bg-gray-900 hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all transform hover:-translate-y-1">
                                    <i class="fa-solid fa-paper-plane mr-2"></i> Submit Survey Responses
                                </button>
                            </div>
                        </form>

                        <script>
                            // --- Global Interactive State (Defined immediately) ---
                            window._locationMaps = window._locationMaps || {};
                            window._qrScanners = window._qrScanners || {};
                            window._signaturePads = window._signaturePads || {};
                            window._repeatCounters = window._repeatCounters || {};

                            window.resetSurvey = function () {
                                Swal.fire({
                                    title: 'Reset Survey Answers?',
                                    text: "This will permanently clear all your responses on this form.",
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#111827',
                                    cancelButtonColor: '#ef4444',
                                    confirmButtonText: 'Yes, Clear All',
                                    cancelButtonText: 'Cancel',
                                    customClass: {
                                        popup: 'rounded-3xl',
                                        confirmButton: 'rounded-xl font-bold px-6 py-3',
                                        cancelButton: 'rounded-xl font-bold px-6 py-3'
                                    }
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Reset standard forms
                                        const form = document.getElementById('publicSurveyForm') || document.getElementById('legacySurveyForm');
                                        if (form) form.reset();

                                        // Force clear all standard inputs manually (Fix for some browser behaviors/Drafts)
                                        $('input[type="radio"], input[type="checkbox"]').prop('checked', false);
                                        $('input[type="text"], input[type="number"], input[type="email"], input[type="tel"], input[type="url"], input[type="date"], textarea, select').val('');

                                        // Clear Local Storage Drafts so they don't come back
                                        localStorage.removeItem(`survey_draft_{{ $survey->id }}`);
                                        localStorage.removeItem(`legacy_draft_survey_{{ $survey->id }}`);

                                        // Clear interactive components
                                        // GPS
                                        Object.keys(window._locationMaps).forEach(id => {
                                            const m = window._locationMaps[id];
                                            if (m.marker) m.map.removeLayer(m.marker);
                                            m.map.setView([0, 0], 2);
                                            const input = document.getElementById('input_' + id);
                                            const status = document.getElementById('loc_status_' + id);
                                            if (input) input.value = '';
                                            if (status) status.innerText = 'No location captured';
                                        });

                                        // Signature
                                        Object.keys(window._signaturePads).forEach(id => {
                                            if (window.clearSignature) window.clearSignature(id);
                                        });

                                        // QR
                                        Object.keys(window._qrScanners).forEach(id => {
                                            if (window.resetQRScanner) window.resetQRScanner(id);
                                        });

                                        // Likert Matrix
                                        $('.likert-item.active').removeClass('active');
                                        $('.likert-container').each(function () {
                                            const id = this.id.replace('likert_', '');
                                            $(`#input_${id}`).val('');
                                        });

                                        // Ranking
                                        $('.rank-ordered').empty();
                                        $('.rank-item').each(function () {
                                            const hiddenInput = $(this).closest('.ranking-wrapper').find('input[type="hidden"]');
                                            if (hiddenInput.length) {
                                                const id = hiddenInput.attr('id').replace('input_', '');
                                                const pool = document.getElementById('pool_' + id);
                                                if (pool) pool.appendChild(this);
                                            }
                                        });

                                        // Repeat
                                        $('.repeat-container').each(function () {
                                            const id = this.id.replace('repeat_entries_', '');
                                            $(this).empty();
                                            const minR = 1;
                                            if (window.addRepeatEntry) {
                                                for (let i = 0; i < minR; i++) window.addRepeatEntry(id);
                                            }
                                        });

                                        // Active choice highlights
                                        $('.active-choice').removeClass('active-choice');

                                        // Trigger visibility & validation logic
                                        if (window.updateVisibility) window.updateVisibility();

                                        // Scroll to top
                                        window.scrollTo({ top: 0, behavior: 'smooth' });

                                        Swal.fire({
                                            title: 'Form Reset',
                                            text: 'All answers and drafts have been cleared.',
                                            icon: 'success',
                                            timer: 1500,
                                            showConfirmButton: false,
                                            customClass: { popup: 'rounded-3xl' }
                                        });
                                    }
                                });
                            };

                            // --- Advanced Field Setup Functions ---
                            window.setupLocationMap = function (id) {
                                setTimeout(() => {
                                    const mapEl = document.getElementById('map_' + id);
                                    if (!mapEl || window._locationMaps[id]) return;
                                    if (typeof L === 'undefined') return;
                                    try {
                                        mapEl.style.display = 'block';
                                        const map = L.map('map_' + id).setView([0, 0], 2);
                                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
                                        setTimeout(() => map.invalidateSize(), 200);
                                        setTimeout(() => map.invalidateSize(), 1000);
                                        window._locationMaps[id] = { map: map, marker: null };
                                    } catch (e) { mapEl.style.display = 'none'; }
                                }, 500);
                            };

                            window.captureLocation = function (id) {
                                const statusEl = document.getElementById('loc_status_' + id);
                                const input = document.getElementById('input_' + id);
                                if (!navigator.geolocation) { statusEl.innerText = 'Not supported'; return; }
                                statusEl.innerText = 'Acquiring...';
                                navigator.geolocation.getCurrentPosition(
                                    (pos) => {
                                        const lat = pos.coords.latitude.toFixed(6);
                                        const lng = pos.coords.longitude.toFixed(6);
                                        input.value = lat + ',' + lng;
                                        statusEl.innerText = '📍 ' + lat + ', ' + lng;
                                        if (window._locationMaps[id]) {
                                            const m = window._locationMaps[id];
                                            m.map.invalidateSize();
                                            m.map.setView([lat, lng], 15);
                                            if (m.marker) m.map.removeLayer(m.marker);
                                            m.marker = L.marker([lat, lng]).addTo(m.map);
                                        }
                                    },
                                    (err) => { statusEl.innerText = 'Error: ' + err.message; },
                                    { enableHighAccuracy: true, timeout: 10000 }
                                );
                            };

                            window.setupQRScanner = function (id) {
                                if (window._qrScanners[id]) return;
                                try { window._qrScanners[id] = new Html5Qrcode('qr_reader_' + id); } catch (e) { }
                            };

                            window.startQRScanner = function (id) {
                                const scanner = window._qrScanners[id];
                                if (!scanner) return;
                                if (!window.isSecureContext && window.location.protocol !== 'https:' && window.location.hostname !== 'localhost') {
                                    Swal.fire('Security', 'Camera requires HTTPS on mobile.', 'warning');
                                    return;
                                }
                                const startBtn = document.getElementById('qr_start_btn_' + id);
                                if (startBtn) startBtn.style.display = 'none';
                                scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: 250 }, (text) => {
                                    document.getElementById('input_' + id).value = text;
                                    document.getElementById('qr_text_' + id).innerText = text;
                                    document.getElementById('qr_result_' + id).style.display = 'block';
                                    scanner.stop().catch(() => { });
                                }, () => { }).catch(err => { if (startBtn) startBtn.style.display = 'block'; });
                            };

                            window.setupSignaturePad = function (id) {
                                setTimeout(() => {
                                    const canvas = document.getElementById('sig_canvas_' + id);
                                    if (!canvas || window._signaturePads[id]) return;

                                    // Ensure canvas is visible and has dimensions
                                    canvas.style.touchAction = 'none';
                                    canvas.style.border = '2px dashed #e2e8f0';
                                    canvas.style.borderRadius = '12px';
                                    canvas.style.background = '#f8fafc';

                                    const updateSize = () => {
                                        const ratio = Math.max(window.devicePixelRatio || 1, 1);
                                        const rect = canvas.getBoundingClientRect();
                                        if (rect.width === 0) return; // Wait for visibility
                                        canvas.width = rect.width * ratio;
                                        canvas.height = rect.height * ratio;
                                        const ctx = canvas.getContext("2d");
                                        ctx.setTransform(1, 0, 0, 1, 0, 0); ctx.scale(ratio, ratio);
                                    };

                                    updateSize();
                                    const pad = new SignaturePad(canvas, {
                                        backgroundColor: 'rgba(255, 255, 255, 0)',
                                        penColor: 'rgb(30, 41, 59)',
                                        minWidth: 1.5,
                                        maxWidth: 4
                                    });

                                    pad.addEventListener('beginStroke', () => {
                                        const placeholder = document.getElementById('sig_placeholder_' + id);
                                        if (placeholder) placeholder.style.display = 'none';
                                    });
                                    pad.addEventListener('endStroke', () => {
                                        const input = document.getElementById('input_' + id);
                                        if (input) input.value = pad.toDataURL();
                                    });

                                    window.addEventListener('resize', updateSize);
                                    window._signaturePads[id] = pad;
                                    console.log("Signature Pad Ready:", id);
                                }, 1000);
                            };

                            window.clearSignature = function (id) {
                                const pad = window._signaturePads[id];
                                if (pad) pad.clear();
                                const input = document.getElementById('input_' + id);
                                if (input) input.value = '';
                                const placeholder = document.getElementById('sig_placeholder_' + id);
                                if (placeholder) placeholder.style.display = 'flex';
                            };

                            window.resetQRScanner = function (id) {
                                const input = document.getElementById('input_' + id);
                                const text = document.getElementById('qr_text_' + id);
                                const result = document.getElementById('qr_result_' + id);
                                const startBtn = document.getElementById('qr_start_btn_' + id);
                                if (input) input.value = '';
                                if (text) text.innerText = '';
                                if (result) result.style.display = 'none';
                                if (startBtn) startBtn.style.display = 'block';
                            };

                            window.setupCalculateField = function (id, formula) {
                                if (!formula) return;
                                const fieldRefs = formula.match(/\$\{([^}]+)\}/g) || [];
                                const fieldNames = fieldRefs.map(r => r.replace('${', '').replace('}', ''));

                                function recalculate() {
                                    let expr = formula;
                                    fieldNames.forEach(name => {
                                        let input = document.querySelector(`[name="${name}"]`);
                                        if (!input) input = document.querySelector(`[name="${name}[]"]`);
                                        if (!input) input = document.getElementById(name);
                                        if (!input) input = document.querySelector(`.field-${name} input`);

                                        let val = 0;
                                        if (input) {
                                            if (input.type === 'radio' || input.type === 'checkbox') {
                                                const checked = document.querySelector(`input[name="${name}"]:checked, input[name="${name}[]"]:checked`);
                                                val = checked ? (parseFloat(checked.value) || 0) : 0;
                                            } else {
                                                val = parseFloat(input.value) || 0;
                                            }
                                        }
                                        expr = expr.replaceAll('${' + name + '}', val);
                                    });

                                    try {
                                        const safeExpr = expr.replace(/[^-()\d/*+.]/g, '');
                                        const result = Function('"use strict"; return (' + safeExpr + ')')();
                                        const display = document.getElementById('calc_display_' + id);
                                        const input = document.getElementById('input_' + id);
                                        if (display) display.innerText = isNaN(result) ? '—' : parseFloat(result.toFixed(4));
                                        if (input) input.value = isNaN(result) ? '' : result;
                                    } catch (e) { }
                                }

                                if (window.jQuery) {
                                    fieldNames.forEach(name => {
                                        window.jQuery(document).on('change input keyup blur', `[name="${name}"], [name="${name}[]"], #${name}`, recalculate);
                                    });
                                }
                                setTimeout(recalculate, 1500);
                            };

                            window.addRepeatEntry = function (id) {
                                const container = document.getElementById('repeat_entries_' + id);
                                const input = document.getElementById('input_' + id);
                                if (!container || !input) return;
                                if (!window._repeatCounters[id]) window._repeatCounters[id] = 0;
                                window._repeatCounters[id]++;
                                const idx = window._repeatCounters[id];
                                const entry = document.createElement('div');
                                entry.className = 'repeat-entry';
                                entry.innerHTML = `<div class="repeat-entry-header"><span class="text-[10px] font-black text-indigo-500 uppercase">Entry #${idx}</span><button type="button" onclick="window.removeRepeatEntry('${id}', ${idx})" class="text-red-400 text-xs font-bold">Remove</button></div><input type="text" name="${id}_entry_${idx}" class="w-full px-4 py-2 border rounded-xl mt-1" oninput="window.syncRepeatData('${id}')">`;
                                entry.id = `repeat_entry_${id}_${idx}`;
                                container.appendChild(entry);
                                window.syncRepeatData(id);
                            };

                            window.removeRepeatEntry = function (id, idx) {
                                const entry = document.getElementById(`repeat_entry_${id}_${idx}`);
                                if (entry) entry.remove();
                                window.syncRepeatData(id);
                            };

                            window.syncRepeatData = function (id) {
                                const container = document.getElementById('repeat_entries_' + id);
                                const input = document.getElementById('input_' + id);
                                if (!container || !input) return;
                                const entries = [];
                                container.querySelectorAll('.repeat-entry input').forEach(inp => {
                                    if (inp.value.trim()) entries.push(inp.value.trim());
                                });
                                input.value = JSON.stringify(entries);
                            };

                            window.updateLikertMatrix = function (id) {
                                const input = document.getElementById('input_' + id);
                                if (!input) return;
                                const rows = JSON.parse(input.dataset.rows || '[]');
                                const result = {};
                                rows.forEach(r => {
                                    const checked = document.querySelector(`input[name="${id}_row_${r.value}"]:checked`);
                                    if (checked) result[r.value] = checked.value;
                                });
                                input.value = JSON.stringify(result);
                            };

                            window.clearSignature = function (id) {
                                if (window._signaturePads[id]) {
                                    window._signaturePads[id].clear();
                                    document.getElementById('input_' + id).value = '';
                                }
                            };

                            window.resetQRScanner = function (id) {
                                document.getElementById('input_' + id).value = '';
                                document.getElementById('qr_result_' + id).style.display = 'none';
                                const startBtn = document.getElementById('qr_start_btn_' + id);
                                if (startBtn) startBtn.style.display = 'block';
                            };

                            window.onload = function () {
                                if (window._surveyHasInitialized) return;
                                window._surveyHasInitialized = true;

                                if (!window.jQuery) {
                                    console.error("jQuery not loaded!");
                                    return;
                                }

                                const $ = window.jQuery;
                                console.log("Initializing Survey Engine...");

                                let surveyData = @json($survey->json_schema);
                                if (typeof surveyData === 'string') { try { surveyData = JSON.parse(surveyData); } catch (e) { } }
                                if (surveyData && surveyData.fields) surveyData = surveyData.fields;
                                if (!Array.isArray(surveyData)) surveyData = [];

                                const container = $('#surveyContainer');
                                $('#surveyLoading').hide();
                                $('#submitContainer').removeClass('hidden');

                                const draftKey = `draft_survey_{{ $survey->id }}`;
                                let savedDraft = localStorage.getItem(draftKey);
                                let userData = null;
                                if (savedDraft) { try { userData = JSON.parse(savedDraft); } catch (e) { } }

                                const typeMap = {
                                    'select_one': 'radio-group',
                                    'select_many': 'checkbox-group',
                                    'textarea': 'textarea',
                                    'rating': 'starRating',
                                    'range': 'number',
                                    'photo': 'file',
                                    'note': 'paragraph',
                                    'time': 'text',
                                    'audio': 'audio_recorder',
                                    'video': 'video_recorder',
                                    'decimal': 'number',
                                    'ranking': 'ranking_list',
                                    'location': 'location_picker',
                                    'qrcode': 'qrcode_scanner',
                                    'signature': 'signature_pad_input',
                                    'datetime': 'datetime_picker',
                                    'acknowledge': 'acknowledge_box',
                                    'hidden': 'hidden_field',
                                    'calculate': 'calculate_display',
                                    'repeat': 'repeat_container',
                                    'likert_matrix': 'likert_matrix_grid'
                                };

                                let qCounter = 1;
                                const processedSchema = surveyData.map(field => {
                                    const finalType = typeMap[field.type] || field.type;
                                    const fieldClone = { ...field, type: finalType, originalType: field.type };

                                    // Numbering
                                    if (!['header', 'paragraph', 'hidden', 'note'].includes(field.type) && field.label) {
                                        fieldClone.label = `${qCounter}. ${field.label}`;
                                        qCounter++;
                                    }

                                    // Remove inline layout (force vertical)
                                    if (field.type && ['select_one', 'select_many', 'radio-group', 'checkbox-group'].includes(field.type)) {
                                        fieldClone.inline = false;
                                        fieldClone.className = (fieldClone.className || '') + ' preview-vertical-group';
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

                                    if (['text', 'textarea', 'number', 'date', 'email', 'tel'].includes(fieldClone.type)) {
                                        fieldClone.className = (fieldClone.className || '') + ' form-control preview-input';
                                        if (fieldClone.type === 'text' && !fieldClone.subtype) {
                                            fieldClone.subtype = 'text';
                                        }
                                        if (fieldClone.type === 'textarea') {
                                            fieldClone.rows = 3;
                                        }
                                    }

                                    if (field.visible_if && field.visible_if.field) {
                                        fieldClone.className = (fieldClone.className || '') + ' conditional-field';
                                    }

                                    return fieldClone;
                                });

                                const renderOptions = {
                                    formData: processedSchema,
                                    dataType: 'json',
                                    render: true,
                                    templates: {
                                        'starRating': function (fieldData) {
                                            const id = fieldData.name;
                                            return {
                                                field: `
                                                                                                                                                                                                                                            <div class="rating-wrapper bg-white py-6 px-4 rounded-2xl mb-4 border border-gray-100 shadow-sm">
                                                                                                                                                                                                                                                <label class="block text-sm font-bold text-gray-400 uppercase tracking-widest mb-4">${fieldData.label || 'Rating'}</label>
                                                                                                                                                                                                                                                <div class="likert-container" id="likert_${id}" style="display: flex !important; justify-content: space-between !important; gap: 8px !important;">
                                                                                                                                                                                                                                                    ${[1, 2, 3, 4, 5].map(i => `<div class="likert-item" data-value="${i}" onclick="setRendererLikertValue('${id}', ${i})" style="flex:1; text-align:center; padding:12px; border:1px solid #e5e7eb; border-radius:8px; cursor:pointer; font-weight:700;">${i}</div>`).join('')}
                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                <input type="hidden" name="${id}" id="input_${id}" required="${fieldData.required ? 'true' : 'false'}" value="">
                                                                                                                                                                                                                                            </div>`
                                            };
                                        },
                                        'ranking_list': function (fieldData) {
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
                                        'audio_recorder': function (fieldData) {
                                            const id = fieldData.name;
                                            const maxDur = fieldData.max_duration || 60;
                                            return {
                                                field: `
                                                                                                                                                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm mb-4">
                                                                                                                                                    <span class="kobo-status-badge" id="status_${id}">Voice Response</span>

                                                                                                                                                    <div class="kobo-media-row mt-2">
                                                                                                                                                        <button type="button" id="start_${id}" class="kobo-record-btn">
                                                                                                                                                            <i class="fa-solid fa-microphone"></i>
                                                                                                                                                            <span>Start Recording</span>
                                                                                                                                                        </button>

                                                                                                                                                        <button type="button" id="stop_${id}" class="kobo-record-btn recording hidden" style="display:none;">
                                                                                                                                                            <i class="fa-solid fa-square"></i>
                                                                                                                                                            <span>Stop</span>
                                                                                                                                                            <span class="kobo-timer" id="timer_${id}">00:00</span>
                                                                                                                                                        </button>

                                                                                                                                                        <div id="upload_container_${id}">
                                                                                                                                                            <label for="file_${id}" class="kobo-upload-btn">
                                                                                                                                                                <i class="fa-solid fa-upload"></i>
                                                                                                                                                                <span>Upload audio File</span>
                                                                                                                                                                <input type="file" id="file_${id}" accept="audio/*" class="hidden" style="display:none;" onchange="window.handleMediaUpload('${id}', 'audio', ${maxDur})">
                                                                                                                                                            </label>
                                                                                                                                                        </div>

                                                                                                                                                        <button type="button" id="retake_${id}" class="text-[10px] font-black uppercase text-red-500 hover:text-red-700 hidden" style="display:none; background:none; border:none; cursor:pointer;">
                                                                                                                                                            <i class="fa-solid fa-trash-can mr-1"></i> Discard
                                                                                                                                                        </button>
                                                                                                                                                    </div>

                                                                                                                                                    <div class="mt-4">
                                                                                                                                                        <audio id="player_${id}" controls class="hidden w-full" style="display:none;"></audio>
                                                                                                                                                    </div>

                                                                                                                                                    <input type="hidden" name="${id}" id="blob_${id}">
                                                                                                                                                </div>`,
                                                onRender: () => setupRecorder(id, 'audio', maxDur)
                                            };
                                        },
                                        'video_recorder': function (fieldData) {
                                            const id = fieldData.name;
                                            const maxDur = fieldData.max_duration || 60;
                                            return {
                                                field: `
                                                                                                                                                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm mb-4">
                                                                                                                                                    <span class="kobo-status-badge" id="status_${id}">Video Response</span>

                                                                                                                                                    <div class="relative aspect-video bg-black rounded-xl overflow-hidden mb-4" style="background:black; aspect-ratio:16/9; position:relative;">
                                                                                                                                                        <video id="preview_${id}" autoplay muted playsinline style="width:100%; height:100%; object-fit:cover; opacity:0.8;"></video>
                                                                                                                                                        <video id="player_${id}" controls style="display:none; width:100%; height:100%; object-fit:contain;"></video>
                                                                                                                                                    </div>

                                                                                                                                                    <div class="kobo-media-row">
                                                                                                                                                        <button type="button" id="start_${id}" class="kobo-record-btn">
                                                                                                                                                            <i class="fa-solid fa-video"></i>
                                                                                                                                                            <span>Start Recording</span>
                                                                                                                                                        </button>

                                                                                                                                                        <button type="button" id="stop_${id}" class="kobo-record-btn recording hidden" style="display:none;">
                                                                                                                                                            <i class="fa-solid fa-square"></i>
                                                                                                                                                            <span>Stop</span>
                                                                                                                                                            <span class="kobo-timer" id="timer_${id}">00:00</span>
                                                                                                                                                        </button>

                                                                                                                                                        <div id="upload_container_${id}">
                                                                                                                                                            <label for="file_${id}" class="kobo-upload-btn">
                                                                                                                                                                <i class="fa-solid fa-upload"></i>
                                                                                                                                                                <span>Upload video File</span>
                                                                                                                                                                <input type="file" id="file_${id}" accept="video/*" class="hidden" style="display:none;" onchange="window.handleMediaUpload('${id}', 'video', ${maxDur})">
                                                                                                                                                            </label>
                                                                                                                                                        </div>

                                                                                                                                                        <button type="button" id="retake_${id}" class="text-[10px] font-black uppercase text-red-500 hover:text-red-700 hidden" style="display:none; background:none; border:none; cursor:pointer;">
                                                                                                                                                            <i class="fa-solid fa-trash-can mr-1"></i> Discard
                                                                                                                                                        </button>
                                                                                                                                                    </div>

                                                                                                                                                    <input type="hidden" name="${id}" id="blob_${id}">
                                                                                                                                                </div>`,
                                                onRender: () => setupRecorder(id, 'video', maxDur)
                                            };
                                        },
                                        'datetime_picker': function (fieldData) {
                                            return { field: `<div class="form-group mb-4"><label class="block text-lg font-bold text-gray-900 mb-3">${fieldData.label || 'Date & Time'}</label><input type="datetime-local" name="${fieldData.name}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl font-bold text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" ${fieldData.required ? 'required' : ''}></div>` };
                                        },
                                        'acknowledge_box': function (fieldData) {
                                            return { field: `<div class="p-5 bg-amber-50/50 rounded-2xl border border-amber-100 mb-4"><label class="flex items-start cursor-pointer gap-3"><input type="checkbox" name="${fieldData.name}" value="true" class="w-5 h-5 mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" ${fieldData.required ? 'required' : ''}><span class="text-sm font-bold text-gray-700">${fieldData.label || 'I acknowledge'}</span></label></div>` };
                                        },
                                        'hidden_field': function (fieldData) {
                                            return { field: `<input type="hidden" name="${fieldData.name}" value="${fieldData.default_value || ''}">` };
                                        },
                                        'calculate_display': function (fieldData) {
                                            const id = fieldData.name;
                                            return {
                                                field: `<div class="p-5 bg-gray-50 rounded-2xl border border-gray-100 mb-4"><label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2">${fieldData.label || 'Calculated Value'}</label><div class="text-2xl font-black text-indigo-600" id="calc_display_${id}">&mdash;</div><input type="hidden" name="${id}" id="input_${id}" value="" data-formula="${fieldData.formula || ''}"></div>`,
                                                onRender: () => window.setupCalculateField(id, fieldData.formula || '')
                                            };
                                        },
                                        'location_picker': function (fieldData) {
                                            const id = fieldData.name;
                                            return {
                                                field: `<div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm mb-4"><label class="block text-lg font-bold text-gray-900 mb-3">${fieldData.label || 'GPS Location'}</label><div id="map_${id}" class="location-map-container" style="height:250px;border-radius:1rem;background:#e2e8f0;"></div><div class="flex items-center gap-3 mt-3"><button type="button" onclick="window.captureLocation('${id}')" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold uppercase hover:bg-indigo-700 transition-all"><i class="fa-solid fa-location-crosshairs mr-2"></i>Capture My Location</button><span id="loc_status_${id}" class="text-[10px] font-bold text-gray-400 uppercase"></span></div><input type="hidden" name="${id}" id="input_${id}" value="" ${fieldData.required ? 'required' : ''}></div>`,
                                                onRender: () => window.setupLocationMap(id)
                                            };
                                        },
                                        'qrcode_scanner': function (fieldData) {
                                            const id = fieldData.name;
                                            return {
                                                field: `<div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm mb-4">
                                                                                                                                                                        <label class="block text-lg font-bold text-gray-900 mb-3">${fieldData.label || 'Scan QR Code'}</label>
                                                                                                                                                                        <div id="qr_container_${id}" class="qr-reader-container">
                                                                                                                                                                            <div id="qr_reader_${id}" style="width:100%;"></div>
                                                                                                                                                                            <button type="button" id="qr_start_btn_${id}" onclick="window.startQRScanner('${id}')" class="btn-scanner-start">
                                                                                                                                                                                <i class="fa-solid fa-camera mr-2"></i> Open Camera
                                                                                                                                                                            </button>
                                                                                                                                                                        </div>
                                                                                                                                                                        <div id="qr_result_${id}" class="mt-4" style="display:none;">
                                                                                                                                                                            <div class="flex items-center gap-3 p-4 bg-emerald-50 rounded-xl border border-emerald-100">
                                                                                                                                                                                <i class="fa-solid fa-circle-check text-emerald-500"></i>
                                                                                                                                                                                <span class="text-sm font-bold text-emerald-700" id="qr_text_${id}"></span>
                                                                                                                                                                            </div>
                                                                                                                                                                            <button type="button" onclick="window.resetQRScanner('${id}')" class="mt-2 text-[10px] font-bold text-indigo-500 uppercase hover:text-indigo-700">Scan Again</button>
                                                                                                                                                                        </div>
                                                                                                                                                                        <input type="hidden" name="${id}" id="input_${id}" value="" ${fieldData.required ? 'required' : ''}>
                                                                                                                                                                    </div>`,
                                                onRender: () => window.setupQRScanner(id)
                                            };
                                        },
                                        'signature_pad_input': function (fieldData) {
                                            const id = fieldData.name;
                                            return {
                                                field: `<div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm mb-4">
                                                                                                                                                                        <label class="block text-lg font-bold text-gray-900 mb-3">${fieldData.label || 'Signature'}</label>
                                                                                                                                                                        <div class="relative group">
                                                                                                                                                                            <canvas id="sig_canvas_${id}" class="signature-canvas"></canvas>
                                                                                                                                                                            <div id="sig_placeholder_${id}" class="absolute inset-0 flex items-center justify-center pointer-events-none text-gray-300 font-bold uppercase tracking-widest text-[10px]">
                                                                                                                                                                                Sign Here
                                                                                                                                                                            </div>
                                                                                                                                                                            <button type="button" onclick="window.clearSignature('${id}')" class="absolute top-2 right-2 p-2 text-gray-400 hover:text-red-500 transition-all">
                                                                                                                                                                                <i class="fa-solid fa-eraser"></i>
                                                                                                                                                                            </button>
                                                                                                                                                                        </div>
                                                                                                                                                                        <input type="hidden" name="${id}" id="input_${id}" ${fieldData.required ? 'required' : ''}>
                                                                                                                                                                    </div>`,
                                                onRender: () => window.setupSignaturePad(id)
                                            };
                                        },
                                        'likert_matrix_grid': function (fieldData) {
                                            const id = fieldData.name;
                                            const rows = fieldData.rows || [{ label: 'Item 1', value: 'item-1' }, { label: 'Item 2', value: 'item-2' }];
                                            const columns = fieldData.columns || [{ label: '1', value: '1' }, { label: '2', value: '2' }, { label: '3', value: '3' }, { label: '4', value: '4' }, { label: '5', value: '5' }];
                                            let hdr = '<th style="text-align:left;padding:10px 8px;font-size:11px;font-weight:800;color:#6b7280;text-transform:uppercase;"></th>';
                                            columns.forEach(c => { hdr += `<th style="padding:10px 8px;text-align:center;font-size:10px;font-weight:800;color:#6b7280;text-transform:uppercase;background:#f9fafb;">${c.label}</th>`; });
                                            let body = '';
                                            rows.forEach(r => {
                                                let cells = `<td style="padding:12px 8px;text-align:left;font-weight:600;color:#374151;font-size:0.875rem;border-top:1px solid #f3f4f6;">${r.label}</td>`;
                                                columns.forEach(c => { cells += `<td style="padding:12px 8px;text-align:center;border-top:1px solid #f3f4f6;"><input type="radio" name="${id}_row_${r.value}" value="${c.value}" style="accent-color:#4f46e5;width:1.15rem;height:1.15rem;cursor:pointer;" onchange="window.updateLikertMatrix('${id}')"></td>`; });
                                                body += `<tr>${cells}</tr>`;
                                            });
                                            return {
                                                field: `<div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm mb-4"><label class="block text-lg font-bold text-gray-900 mb-4">${fieldData.label || 'Rate the following'}</label><div style="overflow-x:auto;"><table class="likert-matrix-table" style="width:100%;"><thead><tr>${hdr}</tr></thead><tbody>${body}</tbody></table></div><input type="hidden" name="${id}" id="input_${id}" value="" data-rows='${JSON.stringify(rows)}'></div>`,
                                                onRender: () => window.updateLikertMatrix(id)
                                            };
                                        },
                                        'repeat_container': function (fieldData) {
                                            const id = fieldData.name;
                                            const minR = fieldData.min_repeat || 1;
                                            const maxR = fieldData.max_repeat || 10;
                                            return {
                                                field: `<div class="p-5 rounded-2xl border-2 border-dashed border-indigo-200 mb-4 bg-indigo-50/20" id="repeat_${id}"><div class="flex items-center gap-2 mb-3"><i class="fa-solid fa-repeat text-indigo-500"></i><label class="text-lg font-bold text-indigo-700">${fieldData.label || 'Repeating Section'}</label></div><div id="repeat_entries_${id}" class="space-y-3"></div><button type="button" onclick="window.addRepeatEntry('${id}')" class="mt-3 px-4 py-2 bg-indigo-100 text-indigo-600 rounded-xl text-xs font-bold uppercase hover:bg-indigo-200 transition-all"><i class="fa-solid fa-plus mr-2"></i>Add Entry</button><input type="hidden" name="${id}" id="input_${id}" value="[]" data-min="${minR}" data-max="${maxR}"></div>`,
                                                onRender: () => { for (let i = 0; i < minR; i++) window.addRepeatEntry(id); }
                                            };
                                        }
                                    }
                                };

                                const formRenderInstance = container.formRender(renderOptions);

                                // --- Manual Initialization for Advanced Fields ---
                                // Use a small delay and requestAnimationFrame to ensure form is fully in DOM
                                setTimeout(() => {
                                    requestAnimationFrame(() => {
                                        console.log("Starting advanced field initialization for", processedSchema.length, "fields");

                                        processedSchema.forEach(field => {
                                            const name = field.name;
                                            const type = field.type;

                                            try {
                                                if (type === 'location_picker') {
                                                    console.log("Initing GPS:", name);
                                                    window.setupLocationMap(name);
                                                } else if (type === 'qrcode_scanner') {
                                                    console.log("Initing QR:", name);
                                                    window.setupQRScanner(name);
                                                } else if (type === 'signature_pad_input') {
                                                    console.log("Initing Signature:", name);
                                                    window.setupSignaturePad(name);
                                                } else if (type === 'calculate_display') {
                                                    console.log("Initing Calculate:", name);
                                                    window.setupCalculateField(name, field.formula);
                                                } else if (type === 'repeat_container') {
                                                    const minR = field.min_repeat || 1;
                                                    for (let i = 0; i < minR; i++) window.addRepeatEntry(name);
                                                } else if (type === 'likert_matrix_grid') {
                                                    window.updateLikertMatrix(name);
                                                }
                                            } catch (err) {
                                                console.error(`Error initializing field ${name}:`, err);
                                            }
                                        });
                                    });
                                }, 500);

                                // --- Display Logic (Conditional Visibility) Engine ---
                                function updateVisibility() {
                                    processedSchema.forEach(field => {
                                        let group = $(`.field-${field.name}`).closest('.form-group');
                                        if (!group.length) {
                                            group = $(`[name="${field.name}"], [name="${field.name}[]"]`).closest('.form-group');
                                        }

                                        let shouldShow = true;

                                        if (field.visible_if && field.visible_if.field) {
                                            const triggerFieldName = field.visible_if.field;
                                            const operator = field.visible_if.operator;
                                            const targetValue = field.visible_if.value;

                                            let currentValues = [];
                                            const triggerInputs = $(`[name="${triggerFieldName}"], [name="${triggerFieldName}[]"]`);

                                            if (triggerInputs.length > 0) {
                                                const type = triggerInputs.attr('type');
                                                if (type === 'radio') {
                                                    const checked = triggerInputs.filter(':checked');
                                                    if (checked.length > 0) currentValues = [checked.val()];
                                                } else if (type === 'checkbox') {
                                                    currentValues = triggerInputs.filter(':checked').map((i, el) => el.value).get();
                                                } else {
                                                    const val = triggerInputs.val();
                                                    if (val) currentValues = [val];
                                                }
                                            }

                                            if (operator === '==') {
                                                if (!currentValues.includes(targetValue)) shouldShow = false;
                                            } else if (operator === '!=') {
                                                if (currentValues.includes(targetValue) || currentValues.length === 0) shouldShow = false;
                                            }
                                        }

                                        // Apply Visibility
                                        if (shouldShow) {
                                            if (group.is(':hidden')) group.slideDown(200);
                                        } else {
                                            if (group.is(':visible')) group.slideUp(200);
                                        }
                                    });
                                }

                                // Initial evaluation after a short delay
                                setTimeout(() => {
                                    processedSchema.forEach(field => {
                                        if (field.visible_if && field.visible_if.field) {
                                            $(`.field-${field.name}`).closest('.form-group').hide();
                                        }
                                    });
                                    updateVisibility();
                                }, 300);

                                // Listen for any changes in the form
                                $(document).off('change.public').on('change.public', '#jsonSurveyForm :input', function (e) {
                                    updateVisibility();

                                    // --- Skip Logic Scroll Behavior ---
                                    const inputName = $(this).attr('name');
                                    if (!inputName) return;

                                    const fieldName = inputName.replace('[]', '');
                                    const field = processedSchema.find(f => f.name === fieldName);

                                    if (field && field.values && ['select_one', 'radio-group', 'select_many', 'checkbox-group'].includes(field.type)) {
                                        const type = $(this).attr('type');
                                        if ((type === 'radio' || type === 'checkbox') && $(this).is(':checked')) {
                                            const val = $(this).val();
                                            const opt = field.values.find(o => (o.value || o) === val);
                                            if (opt && opt.next) {
                                                if (opt.next === 'submit') {
                                                    // Scroll to submit button
                                                    const btn = $('button[type="submit"]');
                                                    if (btn.length) {
                                                        $('html, body').animate({ scrollTop: btn.offset().top - 100 }, 500);
                                                    }
                                                } else {
                                                    let targetGroup = $(`.field-${opt.next}`).closest('.form-group');
                                                    if (!targetGroup.length) targetGroup = $(`[name="${opt.next}"]`).closest('.form-group');
                                                    if (targetGroup.length) {
                                                        $('html, body').animate({ scrollTop: targetGroup.offset().top - 100 }, 500);
                                                        const origBg = targetGroup.css('background-color');
                                                        targetGroup.css('background-color', '#eef2ff');
                                                        setTimeout(() => targetGroup.css('background-color', origBg), 1000);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                });
                                // --- End Visibility Engine ---

                                const recorderBlobs = {};

                                window.setRendererLikertValue = function (id, value) {
                                    const container = jQuery(`#likert_${id}`);
                                    const input = jQuery(`#input_${id}`);
                                    container.find('.likert-item').removeClass('active');
                                    container.find(`.likert-item[data-value="${value}"]`).addClass('active');
                                    input.val(value).trigger('change');
                                };

                                window.togglePublicRankItem = function (id, el) {
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
                                };

                                function setupRecorder(id, type, maxDuration = 60) {
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
                                    const uploadBtn = document.getElementById(`upload_container_${id}`);

                                    if (!startBtn) return;

                                    function stopAndCleanup() {
                                        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                                            mediaRecorder.stop();
                                        }
                                        stopBtn.classList.remove('recording');
                                        if (uploadBtn) uploadBtn.classList.remove('hidden');
                                    }

                                    function updateTimer() {
                                        seconds++;
                                        if (seconds >= maxDuration) {
                                            stopAndCleanup();
                                            statusLabel.innerText = 'Limit Reached';
                                            return;
                                        }
                                        const mins = Math.floor(seconds / 60).toString().padStart(2, '0');
                                        const secs = (seconds % 60).toString().padStart(2, '0');
                                        timerLabel.innerText = `${mins}:${secs}`;
                                    }

                                    retakeBtn.onclick = () => {
                                        player.classList.add('hidden');
                                        retakeBtn.classList.add('hidden');
                                        startBtn.classList.remove('hidden');
                                        if (uploadBtn) uploadBtn.classList.remove('hidden');
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
                                            if (uploadBtn) uploadBtn.classList.add('hidden');

                                            mediaRecorder = new MediaRecorder(stream);
                                            mediaRecorder.ondataavailable = (e) => {
                                                if (e.data.size > 0) chunks.push(e.data);
                                            };
                                            mediaRecorder.onstop = () => {
                                                clearInterval(timerInterval);
                                                const blob = new Blob(chunks, { type: type === 'audio' ? 'audio/ogg; codecs=opus' : 'video/webm' });

                                                const reader = new FileReader();
                                                reader.readAsDataURL(blob);
                                                reader.onloadend = () => {
                                                    blobInput.value = reader.result;
                                                };

                                                player.src = URL.createObjectURL(blob);
                                                player.classList.remove('hidden');
                                                player.style.display = 'block';
                                                if (preview) preview.classList.add('hidden');

                                                recorderBlobs[id] = blob;

                                                stream.getTracks().forEach(track => track.stop());
                                                stopBtn.classList.add('hidden');
                                                stopBtn.style.display = 'none';
                                                retakeBtn.classList.remove('hidden');
                                                retakeBtn.style.display = 'inline-block';
                                                if (statusLabel.innerText !== 'Limit Reached') statusLabel.innerText = 'Response Captured';
                                            };

                                            mediaRecorder.start();
                                            startBtn.classList.add('hidden');
                                            startBtn.style.display = 'none';
                                            stopBtn.classList.remove('hidden');
                                            stopBtn.style.display = 'inline-flex';
                                            stopBtn.classList.add('recording');
                                            statusLabel.innerText = 'Now Recording...';

                                            seconds = 0;
                                            timerInterval = setInterval(updateTimer, 1000);
                                            chunks = [];
                                        } catch (err) {
                                            alert("Permission denied or device error: " + err.message);
                                        }
                                    };
                                    stopBtn.onclick = stopAndCleanup;
                                }

                                window.handleMediaUpload = function (id, type, maxDuration) {
                                    const fileInput = document.getElementById('file_' + id);
                                    const blobInput = document.getElementById('blob_' + id);
                                    const statusLabel = document.getElementById('status_' + id);
                                    const player = document.getElementById('player_' + id);
                                    const startBtn = document.getElementById('start_' + id);
                                    const retakeBtn = document.getElementById('retake_' + id);
                                    const timerLabel = document.getElementById('timer_' + id);

                                    if (!fileInput.files.length) return;
                                    const file = fileInput.files[0];

                                    // Duration Check
                                    const tempMedia = document.createElement(type === 'audio' ? 'audio' : 'video');
                                    tempMedia.src = URL.createObjectURL(file);
                                    tempMedia.onloadedmetadata = function () {
                                        const duration = tempMedia.duration;
                                        if (duration > maxDuration) {
                                            alert(`This file is too long (${Math.round(duration)}s). Maximum allowed is ${maxDuration}s.`);
                                            fileInput.value = '';
                                            return;
                                        }

                                        const reader = new FileReader();
                                        reader.onload = (e) => {
                                            blobInput.value = e.target.result;
                                            player.src = e.target.result;
                                            player.classList.remove('hidden');
                                            startBtn.classList.add('hidden');
                                            retakeBtn.classList.remove('hidden');
                                            statusLabel.innerText = 'File Imported';
                                            recorderBlobs[id] = file;

                                            const mins = Math.floor(duration / 60).toString().padStart(2, '0');
                                            const secs = Math.floor(duration % 60).toString().padStart(2, '0');
                                            timerLabel.innerText = `${mins}:${secs}`;
                                        };
                                        reader.readAsDataURL(file);
                                    };
                                };

                                // Input Constraints validation
                                window.validateConstraints = function (schema) {
                                    let valid = true;
                                    schema.forEach(field => {
                                        if (!field.constraint || field.type === 'hidden') return;
                                        const input = document.querySelector(`[name="${field.name}"]`);
                                        if (!input) return;
                                        const val = parseFloat(input.value);
                                        if (isNaN(val) && input.value === '') return; // skip empty non-required
                                        let expr = field.constraint.replace(/\./g, val);
                                        // Replace field references
                                        const refs = expr.match(/\$\{([^}]+)\}/g) || [];
                                        refs.forEach(ref => {
                                            const refName = ref.replace('${', '').replace('}', '');
                                            const refInput = document.querySelector(`[name="${refName}"]`);
                                            expr = expr.replace(ref, refInput ? (parseFloat(refInput.value) || 0) : 0);
                                        });
                                        try {
                                            const result = Function('"use strict"; return (' + expr + ')')();
                                            if (!result) {
                                                valid = false;
                                                const msg = field.constraint_message || 'Invalid value for: ' + (field.label || field.name);
                                                input.style.borderColor = '#ef4444';
                                                const existing = input.parentElement.querySelector('.constraint-error');
                                                if (!existing) {
                                                    const err = document.createElement('p');
                                                    err.className = 'constraint-error text-red-500 text-xs font-bold mt-1';
                                                    err.innerText = msg;
                                                    input.parentElement.appendChild(err);
                                                }
                                            } else {
                                                input.style.borderColor = '';
                                                const existing = input.parentElement.querySelector('.constraint-error');
                                                if (existing) existing.remove();
                                            }
                                        } catch (e) { /* ignore parse errors */ }
                                    });
                                    return valid;
                                };

                                // Restore DOM manually if we have a draft
                                if (userData && userData.length > 0) {
                                    let hasRestored = false;
                                    userData.forEach(function (item) {
                                        if (item.name && item.userData) {
                                            let input = $(`[name="${item.name}"], [name="${item.name}[]"]`);
                                            if (input.length > 0) {
                                                hasRestored = true;
                                                if (input.attr('type') === 'checkbox' || input.attr('type') === 'radio') {
                                                    input.each(function () {
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
                                setInterval(function () {
                                    if (formRenderInstance && formRenderInstance.userData) {
                                        localStorage.setItem(draftKey, JSON.stringify(formRenderInstance.userData));
                                    }
                                }, 3000);

                                $('#jsonSurveyForm').on('submit', function (e) {
                                    e.preventDefault();

                                    const _isPaid = {!! json_encode((bool) $survey->is_paid) !!};
                                    const _isGuest = {!! json_encode(auth()->guest()) !!};
                                    const _budgetExhausted = {!! json_encode((bool) $budgetExhausted) !!};

                                    // Validate constraints before submission
                                    const _originalSchema = surveyData;
                                    if (typeof window.validateConstraints === 'function' && !window.validateConstraints(_originalSchema)) {
                                        Swal.fire('Validation Error', 'Some fields have invalid values. Please check highlighted fields.', 'warning');
                                        return;
                                    }

                                    const performSubmit = () => {
                                        const submitBtn = $('#jsonSurveyForm').find('button[type="submit"]');
                                        const originalText = submitBtn.html();
                                        submitBtn.html('<i class="fa-solid fa-spinner fa-spin mr-2"></i> Submitting...').prop('disabled', true);

                                        const formData = new FormData(document.getElementById('jsonSurveyForm'));
                                        formData.append('is_json_submission', '1');
                                        formData.append('json_data', JSON.stringify(formRenderInstance.userData));

                                        $(document.getElementById('jsonSurveyForm')).find('input[type="file"]').each(function () {
                                            if (this.files.length > 0) {
                                                formData.append(this.name, this.files[0]);
                                            }
                                        });

                                        for (const id in recorderBlobs) {
                                            if (recorderBlobs[id]) {
                                                const blob = recorderBlobs[id];
                                                const isUpload = blob instanceof File;
                                                const ext = isUpload ? blob.name.split('.').pop() : (blob.type.includes('audio') ? 'ogg' : 'webm');
                                                const fileName = isUpload ? blob.name : `${id}_recording.${ext}`;
                                                formData.append(id, blob, fileName);
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
                                                if (response.status === 429) {
                                                    throw new Error("Too Many Requests: Please wait before submitting another survey.");
                                                }
                                                return response.json().then(data => ({ status: response.status, body: data }));
                                            })
                                            .then(res => {
                                                const data = res.body;
                                                if (res.status === 422 || !data.success) {
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
                            };

                            // Self-healing jQuery check: Retry if jQuery isn't ready yet
                            (function checkJQuery() {
                                if (window.jQuery) {
                                    window.onload();
                                } else {
                                    console.warn("jQuery not ready, retrying...");
                                    setTimeout(checkJQuery, 500);
                                }
                            })();
                        </script>

                    @else
                        {{-- Legacy Question-Based Survey Form --}}
                        <form id="legacySurveyForm"
                            action="{{ route('surveys.submit', [$survey->id, 'invite_token' => request('invite_token')]) }}"
                            method="POST" enctype="multipart/form-data" class="space-y-10">
                            @csrf

                            @forelse ($survey->questions()->orderBy('position')->get() as $index => $question)
                                <div
                                    class="bg-gray-50/50 p-8 rounded-2xl border border-gray-100 shadow-sm transition-all hover:shadow-md hover:bg-white group">
                                    <div class="flex items-start mb-6">
                                        <div class="flex-shrink-0 mr-4 mt-1">
                                            <div
                                                class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm">
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
                                                    <label
                                                        class="flex items-center p-4 bg-white border border-gray-200 rounded-xl cursor-pointer hover:border-indigo-300 hover:bg-indigo-50/30 transition-all">
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
                                                    <label
                                                        class="flex items-center p-4 bg-white border border-gray-200 rounded-xl cursor-pointer hover:border-indigo-300 hover:bg-indigo-50/30 transition-all">
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
                                                class="w-full px-5 py-3 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all text-gray-700 shadow-sm font-medium"
                                                placeholder="your@email.com">

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
                                            <div
                                                class="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl bg-white hover:border-indigo-400 transition-colors">
                                                <div class="space-y-2 text-center">
                                                    <i class="fa-solid fa-video text-gray-500 text-3xl mb-2"></i>
                                                    <div class="flex text-sm text-gray-600 justify-center">
                                                        <label
                                                            class="relative cursor-pointer bg-white rounded-md font-bold text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-500 text-center">
                                                            <span>Upload a video file</span>
                                                            <input type="file" name="{{ $inputName }}" accept="video/*" class="sr-only" {{ $question->required ? 'required' : '' }}>
                                                        </label>
                                                    </div>
                                                    <p class="text-xs text-gray-600">MP4, WebM, OGG up to 50MB</p>
                                                </div>
                                            </div>

                                        @elseif ($question->type === 'audio')
                                            <div
                                                class="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl bg-white hover:border-indigo-400 transition-colors">
                                                <div class="space-y-2 text-center">
                                                    <i class="fa-solid fa-microphone text-gray-500 text-3xl mb-2"></i>
                                                    <div class="flex text-sm text-gray-600 justify-center">
                                                        <label
                                                            class="relative cursor-pointer bg-white rounded-md font-bold text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-500 text-center">
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
                                            <span
                                                class="font-bold text-gray-900 text-base group-hover:text-indigo-700 transition-colors">
                                                I agree to the <a href="{{ route('terms') }}" target="_blank"
                                                    onclick="event.stopPropagation();"
                                                    class="text-indigo-600 hover:underline font-bold">Terms and Conditions</a>
                                            </span>
                                            <p class="text-gray-500 mt-1 leading-relaxed">By submitting this survey, you acknowledge
                                                that your responses will be recorded and processed in accordance with our <a
                                                    href="{{ route('privacy') }}" target="_blank" onclick="event.stopPropagation();"
                                                    class="text-indigo-600 hover:underline font-bold">Data Privacy Policy</a>. We value
                                                your privacy and ensure your data is stored securely.</p>
                                        </div>
                                    </label>
                                </div>

                                <div class="pt-6 border-t border-gray-100 flex justify-end gap-4">
                                    <button type="button" onclick="window.resetSurvey()"
                                        class="inline-flex items-center px-8 py-4 border border-gray-300 text-base font-bold rounded-xl shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                                        <i class="fa-solid fa-rotate-left mr-2"></i> Reset Answers
                                    </button>
                                    <button type="submit"
                                        class="inline-flex items-center px-8 py-4 border border-transparent text-base font-bold rounded-xl shadow-lg text-white bg-gray-900 hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all transform hover:-translate-y-1">
                                        <i class="fa-solid fa-paper-plane mr-2"></i> Submit Survey Responses
                                    </button>
                                </div>
                            @endif
                        </form>

                        <script>
                            jQuery(function ($) {
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
                                                    input.each(function () {
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
                                setInterval(function () {
                                    let draftObj = {};
                                    $('#legacySurveyForm').serializeArray().forEach(function (item) {
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

                                $('#legacySurveyForm').on('submit', function (e) {
                                    const _isPaid = {!! json_encode((bool) $survey->is_paid) !!};
                                    const _isGuest = {!! json_encode(auth()->guest()) !!};
                                    const _budgetExhausted = {!! json_encode((bool) $budgetExhausted) !!};

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

<script>
    // Global Selection Highlighting Logic
    jQuery(function ($) {
        $(document).on('change', 'input[type="radio"], input[type="checkbox"]', function () {
            const name = $(this).attr('name');
            if ($(this).attr('type') === 'radio') {
                $(`input[name="${name}"], [name="${name}[]"]`).closest('label').removeClass('active-choice');
            }

            if ($(this).is(':checked')) {
                $(this).closest('label').addClass('active-choice');
            } else {
                $(this).closest('label').removeClass('active-choice');
            }
        });
        // Pre-run for already checked (drafts)
        $('input[type="radio"]:checked, input[type="checkbox"]:checked').closest('label').addClass('active-choice');
    });
</script>