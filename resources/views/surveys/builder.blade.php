@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://formbuilder.online/assets/css/form-render.min.css">
    <style>
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
    </style>
@endpush

@section('content')
    @php 
        $user = auth()->user();
        $roleVal = is_object($user->role) ? $user->role->value : $user->role;
    @endphp
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-indigo-700 flex items-center">
            <i class="fa-solid fa-plus-circle mr-2"></i> Survey Builder
        </h2>
        <p class="text-gray-600">Create surveys using the drag-and-drop interface or JSON import.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        <!-- Survey Configuration Panel (Sidebar) -->
        <div class="lg:col-span-1 border-r border-gray-200">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 sticky top-6 lg:mr-4">
                <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Survey Details</h3>

                <form method="POST"
                    action="{{ isset($survey) ? route('surveys.update', $survey) : route('surveys.store') }}"
                    id="surveyForm">
                    @csrf
                    @if(isset($survey)) @method('PUT') @endif
                    <input type="hidden" name="json_schema" id="json_schema"
                        value="{{ isset($survey) ? $survey->json_schema : '' }}">

                    <div class="mb-4">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Survey Title <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="title" id="title" required
                            value="{{ isset($survey) ? $survey->title : '' }}"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                    </div>

                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" id="description" rows="3"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">{{ isset($survey) ? $survey->description : '' }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category <span
                                class="text-red-500">*</span></label>
                        <select name="category" id="category" required
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border bg-white">
                            <option value="">Select Category</option>
                            @foreach(['Marketing', 'Academic', 'Product', 'Political', 'Health', 'Other'] as $cat)
                                <option value="{{ $cat }}" {{ (isset($survey) && $survey->category === $cat) ? 'selected' : '' }}>
                                    {{ $cat }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-6">
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Survey Type <span
                                class="text-red-500">*</span></label>
                        <select name="type" id="type" required
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border bg-white">
                            @php $currentType = isset($survey) ? (is_object($survey->type) ? $survey->type->value : $survey->type) : 'public'; @endphp
                            <option value="public" {{ $currentType === 'public' ? 'selected' : '' }}>Public</option>
                            <option value="invitation" {{ $currentType === 'invitation' ? 'selected' : '' }}>Invitation Only
                            </option>
                        </select>
                    </div>

                    <button type="submit"
                        class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 mb-3 transition-colors">
                        <i class="fa-solid fa-save mr-2"></i> {{ isset($survey) ? 'Update Survey' : 'Save Survey' }}
                    </button>

                    <a href="{{ route($roleVal . '.dashboard') }}"
                        class="w-full flex justify-center items-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        Cancel
                    </a>
                </form>
            </div>
        </div>

        <!-- Survey Builder Panel (Main Content) -->
        <div class="lg:col-span-3">

            <!-- Mode Toggle -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-2 mb-6 flex justify-center space-x-2">
                <button type="button"
                    class="mode-toggle inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                    data-mode="visual" onclick="switchMode('visual')">
                    <i class="fa-solid fa-paint-brush mr-2"></i> Visual Builder
                </button>
                <button type="button"
                    class="mode-toggle inline-flex items-center px-6 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                    data-mode="json" onclick="switchMode('json')">
                    <i class="fa-solid fa-code mr-2"></i> JSON Import
                </button>
                <button type="button"
                    class="inline-flex items-center px-6 py-2 border-2 border-indigo-500 text-sm font-medium rounded-md shadow-sm text-indigo-700 bg-white hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all font-bold"
                    onclick="openFullScreenPreview()">
                    <i class="fa-solid fa-eye mr-2 text-indigo-600"></i> Full Screen Preview
                </button>
                <button type="button"
                    class="inline-flex items-center px-6 py-2 border-2 border-purple-500 text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all transform hover:scale-105"
                    onclick="openAiArchitect()">
                    <i class="fa-solid fa-sparkles mr-2 text-yellow-300"></i> AI Architect
                </button>
            </div>

            <!-- Visual Builder Mode -->
            <div id="visualMode">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                        <h5 class="text-sm font-medium text-gray-900"><i
                                class="fa-solid fa-wrench mr-2 text-indigo-500"></i> Builder Canvas</h5>
                    </div>
                    <div class="p-4" style="min-height: 600px;">
                        <div id="surveyCreatorContainer"></div>
                    </div>
                </div>
            </div>

            <!-- JSON Import Mode -->
            <div id="jsonMode" style="display: none;">
                <!-- JSON Input Editor -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                        <div>
                            <h5 class="text-sm font-medium text-gray-900"><i
                                    class="fa-solid fa-code mr-2 text-blue-500"></i> JSON Editor</h5>
                            <p class="text-xs text-gray-500 mt-1">Paste valid form-builder JSON structure</p>
                        </div>
                    </div>
                    <div class="p-4 flex flex-col">
                        <textarea id="jsonInput"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono p-4 bg-gray-800 text-green-400 resize-y overflow-y-auto"
                            placeholder='[\n  {\n    "type": "header",\n    "subtype": "h1",\n    "label": "Demo Header"\n  }\n]'
                            rows="20" style="min-height: 600px; max-height: 800px;"></textarea>

                        <div class="mt-4 flex space-x-3">
                            <button type="button"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700"
                                onclick="validateJSON()">
                                <i class="fa-solid fa-check mr-2"></i> Validate JSON
                            </button>
                            <button type="button"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200"
                                onclick="clearJSON()">
                                <i class="fa-solid fa-trash mr-2"></i> Clear
                            </button>
                        </div>
                        <div id="jsonStatus" class="mt-4"></div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- Full Screen Preview Modal -->
    <div id="previewModal" class="hidden flex-col items-center justify-center">
        <div class="h-screen w-screen flex flex-col p-4 md:p-8">
            <div class="bg-white rounded-2xl shadow-2xl flex-1 flex flex-col overflow-hidden max-w-5xl mx-auto w-full border border-gray-200">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-white sticky top-0 z-10">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 mr-4">
                            <i class="fa-solid fa-eye text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-gray-900 tracking-tight leading-tight">SURVEY PREVIEW</h3>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Interactive simulation</p>
                        </div>
                    </div>
                    <button onclick="closeFullScreenPreview()" class="w-10 h-10 rounded-xl bg-gray-50 text-gray-400 hover:bg-red-50 hover:text-red-500 transition-all flex items-center justify-center group">
                        <i class="fa-solid fa-times text-xl group-hover:rotate-90 transition-transform"></i>
                    </button>
                </div>
                
                <!-- Modal Body -->
                <div id="fullPreviewContent" class="flex-1 overflow-y-auto p-8 md:p-12 bg-gray-50/50">
                    <div class="max-w-3xl mx-auto bg-white rounded-3xl shadow-sm border border-gray-100 p-8 md:p-12" id="previewRenderArea">
                        <!-- Preview renders here -->
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-100 bg-white flex justify-center sticky bottom-0">
                    <button onclick="closeFullScreenPreview()" class="px-8 py-3 bg-indigo-700 text-white rounded-xl font-black text-xs uppercase tracking-[0.2em] hover:bg-indigo-800 transition-all shadow-lg hover:shadow-indigo-200">
                        Return to Editor
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Architect Modal -->
    <div id="aiModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-[110]">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative mx-auto p-5 border w-[600px] shadow-2xl rounded-2xl bg-white transform transition-all">
                <div class="flex items-center justify-between mb-6 border-b pb-4">
                    <h3 class="text-xl font-bold text-gray-900 flex items-center">
                        <i class="fa-solid fa-sparkles mr-2 text-indigo-600"></i> AI Survey Architect
                    </h3>
                    <button onclick="closeAiArchitect()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>

                <div class="space-y-4">
                    <p class="text-sm text-gray-600">
                        Describe the survey you want to build. The AI will generate a complete schema with appropriate question
                        types, options, and logic labels.
                    </p>

                    <div class="relative">
                        <textarea id="aiPrompt" rows="5"
                            class="w-full p-4 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-700 bg-gray-50 mb-2"
                            placeholder="e.g., Generate a 10-question customer satisfaction survey for a coffee shop including questions about quality, staff, and atmosphere..."></textarea>
                        <div id="promptStatus" class="absolute bottom-4 right-4 text-[10px] font-medium text-indigo-400 opacity-50 italic">
                            Prompt reflects current canvas
                        </div>
                    </div>

                    <div
                        class="flex items-center space-x-2 text-xs text-amber-600 bg-amber-50 p-3 rounded-lg border border-amber-100 mb-4">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>This will replace all current questions on your canvas.</span>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeAiArchitect()"
                            class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-colors font-medium">
                            Cancel
                        </button>
                        <button id="generateAiBtn" type="button" onclick="generateWithAi()"
                            class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl hover:from-purple-700 hover:to-indigo-700 transition-all font-bold shadow-lg shadow-indigo-200">
                            Generate Schema
                        </button>
                    </div>
                </div>

                <div id="aiLoader"
                    class="hidden absolute inset-0 bg-white bg-opacity-80 rounded-2xl flex flex-col items-center justify-center">
                    <div class="w-16 h-16 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin mb-4"></div>
                    <p class="text-indigo-900 font-bold animate-pulse text-lg">AI is Architecting...</p>
                    <p class="text-gray-500 text-sm mt-1">This usually takes 5-10 seconds</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://formbuilder.online/assets/js/form-builder.min.js"></script>
    <script src="https://formbuilder.online/assets/js/form-render.min.js"></script>

    <script>
        let formBuilder = null;
        let currentMode = 'visual';

        jQuery(function ($) {
            // 1. Initialize the form builder with existing data if present
            const existingData = $('#json_schema').val();

            let options = {
                controlPosition: 'left',
                disableFields: ['autocomplete', 'hidden', 'button'],
                disabledAttrs: ['access'],
                typeUserEvents: {
                    text: {
                        onadd: function (fId) {
                            // Can add custom conditional handlers here if needed
                        }
                    }
                },
                roles: {
                    // Logic states to trigger visibility dependencies on render
                    1: 'Rule A',
                    2: 'Rule B',
                    3: 'Rule C'
                }
            };

            if (existingData) {
                try {
                    options.formData = JSON.parse(existingData);
                } catch (e) {
                    console.error("Failed to parse existing survey data", e);
                }
            }

            formBuilder = $('#surveyCreatorContainer').formBuilder(options);

            // 2. Update preview and sync data on form builder changes
            const originalOnSave = options.onSave;
            formBuilder.promise.then(builder => {
                const updateAll = () => {
                    syncToJSON();
                    syncToPrompt();
                };

                $(document).on('fieldAdded fieldRemoved fieldAttributeChanged', function() {
                    updateAll();
                });
            });

            // Update on any interaction in the container
            $('#surveyCreatorContainer').on('click mouseup keyup change', function() {
                syncToJSON();
                syncToPrompt();
            });

            // 3. Intercept the form submission
            $('#surveyForm').on('submit', function (e) {
                e.preventDefault();

                let formDataJSON = '';

                if (currentMode === 'visual') {
                    formDataJSON = formBuilder.actions.getData('json');
                } else {
                    formDataJSON = $('#jsonInput').val();
                    try {
                        JSON.parse(formDataJSON);
                    } catch (e) {
                        alert('Invalid JSON structure: ' + e.message);
                        return false;
                    }
                }

                // Ensure there are actually questions in the survey
                if (formDataJSON === '[]' || formDataJSON === '') {
                    alert('Please add at least one field to your survey before saving.');
                    return false;
                }

                // Inject the structured schema into the hidden element
                $('#json_schema').val(formDataJSON);

                // Fetch request logic to match the existing API implementation using Sanctuary auth / API route
                // For now, doing a standard fetch submission to our API endpoint

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
                        if (response.ok) {
                            return response.json();
                        }
                        throw new Error('Network response was not ok.');
                    })
                    .then(data => {
                        alert('Survey saved successfully!');
                        window.location.href = "{{ route($roleVal . '.dashboard') }}";
                    })
                    .catch(error => {
                        alert('Error processing survey: ' + error.message);
                        submitBtn.html(originalIcon);
                        submitBtn.prop('disabled', false);
                    });
            });
        });

        // Full Screen Preview Functions
        function openFullScreenPreview() {
            let schema = '';
            if (currentMode === 'visual') {
                schema = formBuilder.actions.getData('json');
            } else {
                schema = $('#jsonInput').val();
            }

            if (!schema || schema === '[]' || schema === '') {
                alert('Please add some questions first.');
                return;
            }

            const renderArea = $('#previewRenderArea');
            renderArea.empty();
            
            try {
                const parsed = JSON.parse(schema);
                const renderArea = $('#previewRenderArea');
                renderArea.empty();
                renderArea.formRender({
                    formData: parsed,
                    dataType: 'json'
                });
                $('#previewModal').removeClass('hidden').addClass('flex');
                document.body.style.overflow = 'hidden'; 
            } catch (e) {
                alert('Invalid survey structure: ' + e.message);
            }
        }

        function closeFullScreenPreview() {
            $('#previewModal').addClass('hidden').removeClass('flex');
            document.body.style.overflow = ''; 
        }

        // Sync Visual to JSON
        function syncToJSON() {
            if (!formBuilder || currentMode === 'json') return;
            const schema = formBuilder.actions.getData('json');
            $('#jsonInput').val(schema);
        }

        // Sync Visual to AI Prompt (Reverse Mapping)
        function syncToPrompt() {
            if (!formBuilder) return;
            const data = formBuilder.actions.getData();
            if (!data || data.length === 0) return;

            let description = "Generate a survey with the following structure:\n";
            data.forEach((field, index) => {
                const label = field.label || "Untitled Question";
                const type = field.type || "text";
                description += `${index + 1}. A ${type} field labeled "${label}"`;
                if (field.values && field.values.length > 0) {
                    const options = field.values.map(v => v.label).join(", ");
                    description += ` with options: ${options}`;
                }
                description += ".\n";
            });

            $('#aiPrompt').val(description);
        }

        // Toggle between modes
        function switchMode(mode) {
            currentMode = mode;

            const visualBtn = $('.mode-toggle[data-mode="visual"]');
            const jsonBtn = $('.mode-toggle[data-mode="json"]');

            if (mode === 'visual') {
                $('#visualMode').fadeIn(200);
                $('#jsonMode').hide();

                // Activate visual button styling
                visualBtn.removeClass('border-gray-300 text-gray-700 bg-white hover:bg-gray-50')
                    .addClass('border-transparent text-white bg-indigo-600 hover:bg-indigo-700');

                // Deactivate json button styling
                jsonBtn.removeClass('border-transparent text-white bg-indigo-600 hover:bg-indigo-700')
                    .addClass('border-gray-300 text-gray-700 bg-white hover:bg-gray-50');

                // Update on switch
                syncToJSON();
            } else {
                $('#visualMode').hide();
                $('#jsonMode').fadeIn(200);

                jsonBtn.removeClass('border-gray-300 text-gray-700 bg-white hover:bg-gray-50')
                    .addClass('border-transparent text-white bg-indigo-600 hover:bg-indigo-700');

                visualBtn.removeClass('border-transparent text-white bg-indigo-600 hover:bg-indigo-700')
                    .addClass('border-gray-300 text-gray-700 bg-white hover:bg-gray-50');

                // Sync the JSON input when switching to JSON mode
                syncToJSON();
            }
        }

        // Validate JSON schema
        function validateJSON() {
            const jsonInput = document.getElementById('jsonInput').value.trim();
            const statusBox = document.getElementById('jsonStatus');

            if (!jsonInput) {
                statusBox.innerHTML = '<div class="rounded-md bg-yellow-50 p-4"><div class="flex"><div class="flex-shrink-0"><i class="fa-solid fa-triangle-exclamation text-yellow-400"></i></div><div class="ml-3"><p class="text-sm font-medium text-yellow-800">Please paste JSON content first.</p></div></div></div>';
                return;
            }

            try {
                const parsed = JSON.parse(jsonInput);
                if (!Array.isArray(parsed)) {
                    throw new Error("JSON root element must be an Array [] containing field objects");
                }
                statusBox.innerHTML = '<div class="rounded-md bg-green-50 p-4"><div class="flex"><div class="flex-shrink-0"><i class="fa-solid fa-circle-check text-green-400"></i></div><div class="ml-3"><p class="text-sm font-medium text-green-800">JSON parsing successful! You can now preview in full screen.</p></div></div></div>';
            } catch (e) {
                statusBox.innerHTML = '<div class="rounded-md bg-red-50 p-4"><div class="flex"><div class="flex-shrink-0"><i class="fa-solid fa-circle-xmark text-red-400"></i></div><div class="ml-3"><h3 class="text-sm font-medium text-red-800">Invalid JSON</h3><div class="mt-2 text-sm text-red-700"><p>' + e.message + '</p></div></div></div></div>';
            }
        }

        function clearJSON() {
            document.getElementById('jsonInput').value = '';
            document.getElementById('jsonStatus').innerHTML = '';
        }

        // AI Architect Functions
        function openAiArchitect() {
            $('#aiModal').removeClass('hidden').addClass('flex');
            $('#aiPrompt').focus();
        }

        function closeAiArchitect() {
            $('#aiModal').addClass('hidden').removeClass('flex');
        }

        function generateWithAi() {
            const prompt = $('#aiPrompt').val().trim();
            if (!prompt) {
                alert('Please describe the survey you want to generate.');
                return;
            }

            const loader = $('#aiLoader');
            const generateBtn = $('#generateAiBtn');

            loader.removeClass('hidden');
            generateBtn.prop('disabled', true);

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
                    if (data.success) {
                        const schema = JSON.parse(data.schema);

                        // Clear and reload form builder
                        formBuilder.actions.clearFields();
                        formBuilder.actions.setData(schema);

                        // Sync
                        setTimeout(() => {
                            syncToJSON();
                        }, 500);

                        // Close modal and cleanup
                        closeAiArchitect();
                        // We keep the prompt in case they want to tweak it, or clear it if successful
                        // The user asked for "close automatically after entering your prompt and clicking generate"
                        // alert('AI has successfully generated your survey architect!'); // Reduced noise
                    } else {
                        alert('AI Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('AI Architect Error:', error);
                    alert('An error occurred while generating the survey. Please try again.');
                })
                .finally(() => {
                    loader.addClass('hidden');
                    generateBtn.prop('disabled', false);
                });
        }
    </script>
@endpush