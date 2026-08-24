<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $survey->title }} — {{ __('Survey Preview') }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- jQuery & jQuery UI -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <!-- FormRender -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jQuery-formBuilder/3.4.2/form-render.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8fafc;
        }

        /* Sequential Form Preview Styling */
        .fb-render .form-group {
            margin-bottom: 1.5rem !important;
            padding: 1.25rem 0.5rem !important;
            background: transparent !important;
            border-radius: 0 !important;
            border: none !important;
            border-bottom: 1px dashed #e2e8f0 !important;
            box-shadow: none !important;
        }

        .fb-render h1,
        .fb-render h2,
        .fb-render h3 {
            font-weight: 800 !important;
            color: #0f172a !important;
            margin-top: 1.5rem !important;
            margin-bottom: 0.5rem !important;
        }

        .fb-render p,
        .fb-render .field-description,
        .fb-render .field-note {
            font-size: 0.95rem !important;
            line-height: 1.6 !important;
            color: #475569 !important;
            margin-bottom: 1rem !important;
            white-space: pre-line !important;
        }

        /* Form Input, Textarea & Select Visibility */
        .fb-render input[type="text"],
        .fb-render input[type="number"],
        .fb-render input[type="email"],
        .fb-render input[type="date"],
        .fb-render input[type="time"],
        .fb-render input[type="tel"],
        .fb-render textarea,
        .fb-render select {
            width: 100% !important;
            padding: 0.75rem 1rem !important;
            font-size: 0.95rem !important;
            color: #0f172a !important;
            background-color: #ffffff !important;
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 0.75rem !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            transition: all 0.2s ease-in-out !important;
            outline: none !important;
            margin-top: 0.5rem !important;
            display: block !important;
        }

        .fb-render input[type="text"]:focus,
        .fb-render input[type="number"]:focus,
        .fb-render input[type="email"]:focus,
        .fb-render input[type="date"]:focus,
        .fb-render input[type="time"]:focus,
        .fb-render input[type="tel"]:focus,
        .fb-render textarea:focus,
        .fb-render select:focus {
            border-color: #2271b1 !important;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15) !important;
            background-color: #ffffff !important;
        }

        .fb-render label {
            font-weight: 600 !important;
            color: #1e293b !important;
            font-size: 1rem !important;
            margin-bottom: 0.35rem !important;
        }

        .likert-matrix-table {
            width: 100%;
            border-collapse: collapse;
        }
    </style>
</head>

<body class="antialiased text-slate-900 bg-slate-50 min-h-screen flex flex-col">

    <!-- Top Navigation Bar (Pic 5 Layout) -->
    <header
        class="bg-white border-b border-gray-100 sticky top-0 z-50 shadow-sm px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div
                class="w-10 h-10 rounded-2xl bg-amber-50 border border-amber-200/60 flex items-center justify-center text-amber-600">
                <i class="fa-solid fa-eye text-base"></i>
            </div>
            <div>
                <h1 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    {{ $survey->title }}
                </h1>

            </div>
        </div>

        <div class="flex items-center gap-3">
            @if($survey->status->value === 'draft' || $survey->status->value === 'pending_approval')
                <form action="{{ route('surveys.publish', $survey) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                        class="px-6 py-2.5 bg-[#2271b1] text-white rounded-xl font-extrabold text-xs tracking-wider shadow-md hover:bg-[#135e96] transition-all flex items-center gap-2">
                        <i class="fa-solid fa-paper-plane text-xs"></i> {{ __('Deploy Survey') }}
                    </button>
                </form>
            @endif

            <a href="{{ route('surveys.summary', $survey) }}"
                class="w-10 h-10 rounded-2xl bg-slate-900 text-white hover:bg-rose-600 flex items-center justify-center transition-all shadow-md"
                title="{{ __('Exit Preview') }}">
                <i class="fa-solid fa-xmark text-lg"></i>
            </a>
        </div>
    </header>

    <!-- Form Canvas Container -->
    <main class="flex-1 p-4 sm:p-10">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-[2rem] shadow-xl border border-gray-100 overflow-hidden">
                <!-- Hero Accent -->
                <div
                    class="h-24 sm:h-32 bg-gradient-to-r from-zinc-800 via-zinc-900 to-slate-900 relative flex items-center justify-center">
                    <i class="fa-solid fa-vial-circle-check text-white/10 text-5xl sm:text-6xl"></i>
                </div>

                <!-- Render Area -->
                <div class="p-6 sm:p-12">
                    <div id="previewRenderArea" class="fb-render space-y-6"></div>

                    <div class="mt-12 pt-8 border-t border-gray-100 flex justify-between items-center">
                        <button type="button" disabled
                            class="px-6 py-3 bg-gray-100 text-gray-400 rounded-2xl text-xs font-bold uppercase tracking-widest cursor-not-allowed">
                            {{ __('Previous Page') }}
                        </button>
                        <button type="button" onclick="alert('Prototype mode: Form submission verified.')"
                            class="px-8 py-3 bg-[#2271b1] text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-[#135e96] shadow-lg transition-all active:scale-95">
                            {{ __('Complete Survey') }}
                        </button>
                    </div>


                </div>
            </div>
        </div>
    </main>

    <script>
        const SCHEMA = @json(is_string($survey->json_schema) ? json_decode($survey->json_schema ?? '[]', true) : ($survey->json_schema ?? []));

        document.addEventListener('DOMContentLoaded', function () {
            try {
                let parsed = Array.isArray(SCHEMA) ? SCHEMA : [];

                const typeMap = {
                    'select_one': 'radio-group',
                    'select_many': 'checkbox-group',
                    'textarea': 'textarea',
                    'rating': 'starRating',
                    'range': 'number',
                    'photo': 'file',
                    'note': 'paragraph',
                    'description': 'paragraph',
                    'time': 'text',
                    'audio': 'audio_recorder',
                    'video': 'video_recorder',
                    'decimal': 'number',
                    'ranking': 'ranking_list',
                    'email': 'text',
                    'date': 'text',
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

                let qNum = 1;
                const previewSchema = parsed.filter(f => f.type && f.type.trim() !== '').map((field) => {
                    const finalType = typeMap[field.type] || field.type;
                    const fieldClone = { ...field, type: finalType };

                    if (!['header', 'paragraph', 'hidden', 'note', 'description', 'group'].includes(field.type) && field.label) {
                        fieldClone.label = `${qNum}. ${field.label}`;
                        qNum++;
                    }

                    if (['select_one', 'select_many', 'radio-group', 'checkbox-group'].includes(field.type)) {
                        fieldClone.inline = false;
                    }

                    if (field.type === 'range') fieldClone.subtype = 'range';
                    if (field.type === 'time') fieldClone.subtype = 'time';
                    if (field.type === 'email') fieldClone.subtype = 'email';
                    if (field.type === 'date') fieldClone.subtype = 'date';
                    if (field.type === 'photo') {
                        fieldClone.subtype = 'file';
                        fieldClone.accept = 'image/*';
                    }
                    if (field.type === 'decimal') {
                        fieldClone.subtype = 'number';
                        fieldClone.step = 'any';
                    }

                    if (['text', 'textarea', 'number', 'date', 'email', 'tel'].includes(fieldClone.type) || ['email', 'date'].includes(field.type)) {
                        fieldClone.className = (fieldClone.className || '') + ' form-control preview-input';
                        if (fieldClone.type === 'text' && !fieldClone.subtype) {
                            fieldClone.subtype = 'text';
                        }
                        if (fieldClone.type === 'textarea') {
                            fieldClone.rows = 3;
                        }
                    }

                    if (field.type === 'group') {
                        fieldClone.type = 'header';
                        fieldClone.subtype = 'h3';
                        fieldClone.label = field.label || 'Untitled Section';
                    }

                    return fieldClone;
                });

                const renderArea = jQuery('#previewRenderArea');
                renderArea.empty();

                renderArea.formRender({
                    formData: previewSchema,
                    render: true,
                    templates: {
                        'starRating': function (fieldData) {
                            const id = fieldData.name;
                            return {
                                field: `<div class="rating-wrapper bg-white py-6 px-4 rounded-2xl mb-4 border border-gray-100 shadow-sm">
                                            <div class="likert-container" id="likert_${id}" style="display: flex !important; justify-content: space-between !important; gap: 8px !important;">
                                                ${[1, 2, 3, 4, 5].map(i => `<div class="likert-item" data-value="${i}" style="flex:1; text-align:center; padding:12px; border:1px solid #e5e7eb; border-radius:8px; cursor:pointer; font-weight:700;">${i}</div>`).join('')}
                                            </div>
                                            <input type="hidden" name="${id}" id="input_${id}" value="">
                                        </div>`
                            };
                        },
                        'ranking_list': function (fieldData) {
                            const id = fieldData.name;
                            const options = fieldData.values || [];
                            return {
                                field: `<div class="ranking-wrapper bg-white p-6 rounded-2xl mb-4 border border-gray-100 shadow-sm">
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Choices</span>
                                                    <div id="pool_${id}" class="rank-pool" style="min-height:100px; padding:8px; background:#f8fafc; border:2px dashed #e2e8f0; border-radius:12px;">
                                                        ${options.map(opt => `<div class="rank-item" data-value="${opt.value}" style="padding:8px 12px; margin-bottom:6px; background:white; border:1px solid #e2e8f0; border-radius:8px; font-size:12px; font-weight:600;">${opt.label}</div>`).join('')}
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="text-[10px] font-black text-emerald-500 uppercase tracking-widest block mb-2">Your Order</span>
                                                    <div id="ranked_${id}" class="rank-ordered" style="min-height:100px; padding:8px; background:#f8fafc; border:2px dashed #e2e8f0; border-radius:12px;"></div>
                                                </div>
                                            </div>
                                        </div>`
                            };
                        },
                        'audio_recorder': function (fieldData) {
                            return {
                                field: `<div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm mb-4"><span class="text-xs font-bold text-slate-500 mb-2 block"><i class="fa-solid fa-microphone text-[#2271b1] mr-2"></i>Audio Recording Field</span><button type="button" class="px-4 py-2 bg-sky-50 text-[#2271b1] rounded-xl text-xs font-bold border border-sky-200"><i class="fa-solid fa-microphone mr-2"></i>Record Audio</button></div>`
                            };
                        },
                        'video_recorder': function (fieldData) {
                            return {
                                field: `<div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm mb-4"><span class="text-xs font-bold text-slate-500 mb-2 block"><i class="fa-solid fa-video text-[#2271b1] mr-2"></i>Video Recording Field</span><button type="button" class="px-4 py-2 bg-sky-50 text-[#2271b1] rounded-xl text-xs font-bold border border-sky-200"><i class="fa-solid fa-video mr-2"></i>Record Video</button></div>`
                            };
                        },
                        'datetime_picker': function (fieldData) {
                            return { field: `<div class="form-group mb-4"><input type="datetime-local" name="${fieldData.name}" class="w-full px-4 py-3 border border-gray-200 rounded-xl font-bold text-gray-700"></div>` };
                        },
                        'acknowledge_box': function (fieldData) {
                            return { field: `<div class="p-5 bg-amber-50/50 rounded-2xl border border-amber-100 mb-4"><label class="flex items-start cursor-pointer gap-3"><input type="checkbox" name="${fieldData.name}" value="true" class="w-5 h-5 mt-0.5 rounded border-gray-300 text-[#2271b1]"><span class="text-sm font-bold text-gray-700">${fieldData.label || 'I acknowledge'}</span></label></div>` };
                        },
                        'hidden_field': function (fieldData) {
                            return { field: `<div class="p-4 bg-slate-50 rounded-xl border border-dashed border-slate-200 mb-4 flex items-center gap-3"><i class="fa-solid fa-eye-slash text-slate-400"></i><span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Hidden Field: ${fieldData.name}</span></div>` };
                        },
                        'calculate_display': function (fieldData) {
                            return { field: `<div class="p-5 bg-gray-50 rounded-2xl border border-gray-100 mb-4"><div class="text-2xl font-black text-[#2271b1]">&mdash;</div><p class="text-[8px] text-gray-400 uppercase mt-2">Formula: ${fieldData.formula || 'Calculated Field'}</p></div>` };
                        },
                        'location_picker': function (fieldData) {
                            return { field: `<div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm mb-4"><div style="height:120px; border-radius:1rem; background:#f8fafc; border:2px dashed #e2e8f0; display:flex; align-items:center; justify-content:center;"><span class="text-gray-400 font-bold text-xs uppercase"><i class="fa-solid fa-location-dot text-[#2271b1] mr-2"></i>GPS Location Picker</span></div></div>` };
                        },
                        'qrcode_scanner': function (fieldData) {
                            return { field: `<div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm mb-4"><div style="height:120px; border-radius:1rem; background:#f8fafc; border:2px dashed #e2e8f0; display:flex; align-items:center; justify-content:center;"><span class="text-gray-400 font-bold text-xs uppercase"><i class="fa-solid fa-qrcode text-[#2271b1] mr-2"></i>QR / Barcode Scanner</span></div></div>` };
                        },
                        'signature_pad_input': function (fieldData) {
                            return { field: `<div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm mb-4"><div style="height:120px; border:2px dashed #e2e8f0; border-radius:1rem; background:#f8fafc; display:flex; align-items:center; justify-content:center;"><span class="text-gray-400 font-bold text-xs uppercase"><i class="fa-solid fa-signature text-[#2271b1] mr-2"></i>Signature Input</span></div></div>` };
                        },
                        'likert_matrix_grid': function (fieldData) {
                            const rows = fieldData.rows || [{ label: 'Item 1', value: 'item-1' }, { label: 'Item 2', value: 'item-2' }];
                            const columns = fieldData.columns || [{ label: '1', value: '1' }, { label: '2', value: '2' }, { label: '3', value: '3' }, { label: '4', value: '4' }, { label: '5', value: '5' }];
                            let hdr = '<th style="text-align:left;padding:8px;font-size:11px;font-weight:800;color:#6b7280;text-transform:uppercase;"></th>';
                            columns.forEach(c => { hdr += `<th style="padding:8px;text-align:center;font-size:10px;font-weight:800;color:#6b7280;text-transform:uppercase;background:#f9fafb;">${c.label}</th>`; });
                            let body = '';
                            rows.forEach(r => {
                                let cells = `<td style="padding:10px 8px;text-align:left;font-weight:600;color:#374151;font-size:0.875rem;border-top:1px solid #f3f4f6;">${r.label}</td>`;
                                columns.forEach(c => { cells += `<td style="padding:10px 8px;text-align:center;border-top:1px solid #f3f4f6;"><input type="radio" name="${fieldData.name}_row_${r.value}" value="${c.value}"></td>`; });
                                body += `<tr>${cells}</tr>`;
                            });
                            return { field: `<div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm mb-4"><div style="overflow-x:auto;"><table class="likert-matrix-table"><thead><tr>${hdr}</tr></thead><tbody>${body}</tbody></table></div></div>` };
                        },
                        'repeat_container': function (fieldData) {
                            return { field: `<div class="p-5 rounded-2xl border-2 border-dashed border-zinc-300 mb-4 bg-zinc-50"><div class="flex items-center gap-2 mb-2"><i class="fa-solid fa-repeat text-slate-500"></i><span class="text-xs font-bold text-slate-700">Repeat Section</span></div></div>` };
                        }
                    }
                });
            } catch (e) {
                console.error("Preview render failed:", e);
                jQuery('#previewRenderArea').html('<div class="p-6 text-center text-red-500 font-bold">Unable to render survey preview: ' + e.message + '</div>');
            }
        });
    </script>
</body>

</html>