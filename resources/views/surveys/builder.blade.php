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
                    class="inline-flex items-center px-6 py-2 border-2 border-purple-500 text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all transform hover:scale-105"
                    onclick="openAiArchitect()">
                    <i class="fa-solid fa-sparkles mr-2 text-yellow-300"></i> AI Architect
                </button>
            </div>

            <!-- Visual Builder Mode -->
            <div id="visualMode">
                <div class="grid grid-cols-2 gap-6">
                    <!-- Editor Container -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                            <h5 class="text-sm font-medium text-gray-900"><i
                                    class="fa-solid fa-wrench mr-2 text-indigo-500"></i> Builder Canvas</h5>
                        </div>
                        <div class="p-4" style="min-height: 500px;">
                            <div id="surveyCreatorContainer"></div>
                        </div>
                    </div>

                    <!-- Live Preview Container -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                            <h5 class="text-sm font-medium text-gray-900"><i
                                    class="fa-solid fa-eye mr-2 text-indigo-500"></i> Live Preview</h5>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Updates Automatically
                            </span>
                        </div>
                        <div id="visualPreview" class="flex-1 p-6 bg-gray-50 overflow-y-auto" style="max-height: 800px;">
                            <!-- Preview renders here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- JSON Import Mode -->
            <div id="jsonMode" style="display: none;">
                <div class="grid grid-cols-2 gap-6">
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
                                rows="20" style="min-height: 500px; max-height: 800px;"></textarea>

                            <div class="mt-4 flex space-x-3">
                                <button type="button"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700"
                                    onclick="validateAndLoadJSON()">
                                    <i class="fa-solid fa-check mr-2"></i> Validate & Load
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

                    <!-- JSON Preview -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                            <h5 class="text-sm font-medium text-gray-900"><i class="fa-solid fa-eye mr-2 text-blue-500"></i>
                                Code Preview</h5>
                        </div>
                        <div id="jsonPreview" class="flex-1 p-6 bg-gray-50 overflow-y-auto w-full relative"
                            style="min-height: 500px;">
                            <div id="jsonPreviewEmpty"
                                class="absolute inset-0 flex flex-col items-center justify-center text-gray-400">
                                <i class="fa-solid fa-magnifying-glass text-4xl mb-3 opacity-50"></i>
                                <p class="text-sm font-medium">Preview will appear here after JSON validation</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- AI Architect Modal -->
    <div id="aiModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-[600px] shadow-2xl rounded-2xl bg-white transform transition-all">
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
                    updateVisualPreview();
                    syncToJSON();
                    syncToPrompt();
                };

                // FormBuilder doesn't always fire change on every internal action reliably for preview
                // We use their internal events if possible, or a mutation observer on the stage
                const stage = builder.actions.getData(); 
                
                // Set up a listener for any field change
                $(document).on('fieldAdded fieldRemoved fieldAttributeChanged', function() {
                    updateAll();
                });

                // Periodic check as a fallback for drag-drop if events miss
                setInterval(updateVisualPreview, 2000); 
            });

            // Update on any interaction in the container
            $('#surveyCreatorContainer').on('click mouseup keyup change', function() {
                updateVisualPreview();
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

        // Update visual builder preview
        function updateVisualPreview() {
            try {
                if (!formBuilder) return;
                const schema = formBuilder.actions.getData('json');

                $('#visualPreview').empty();

                if (schema && schema !== '[]') {
                    $('#visualPreview').formRender({
                        formData: schema,
                        dataType: 'json'
                    });
                } else {
                    $('#visualPreview').html('<div class="h-full flex flex-col items-center justify-center text-gray-400"><i class="fa-regular fa-clone text-4xl mb-3 opacity-50"></i><p class="text-sm">Drag fields to the canvas to view preview</p></div>');
                }
            } catch (e) {
                console.error('Preview render error:', e);
            }
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

                // If coming from JSON, try to load it
                if ($('#jsonInput').val()) {
                    try {
                        const parsed = JSON.parse($('#jsonInput').val());
                        formBuilder.actions.setData(parsed);
                    } catch (e) {}
                }

                setTimeout(updateVisualPreview, 100);
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

        // Validate and load JSON schema
        function validateAndLoadJSON() {
            const jsonInput = document.getElementById('jsonInput').value.trim();
            const statusBox = document.getElementById('jsonStatus');

            if (!jsonInput) {
                statusBox.innerHTML = '<div class="rounded-md bg-yellow-50 p-4"><div class="flex"><div class="flex-shrink-0"><i class="fa-solid fa-triangle-exclamation text-yellow-400"></i></div><div class="ml-3"><p class="text-sm font-medium text-yellow-800">Please paste JSON content first.</p></div></div></div>';
                return;
            }

            try {
                const parsed = JSON.parse(jsonInput);

                // Check if it's an array (form-builder expects an array of objects)
                if (!Array.isArray(parsed)) {
                    throw new Error("JSON root element must be an Array [] containing field objects");
                }

                // Check for required properties in fields
                parsed.forEach((field, i) => {
                    if (!field.type) throw new Error(`Field at index ${i} is missing "type" property`);
                    if (!field.label && field.type !== 'paragraph' && field.type !== 'header') {
                        // Some fields might not need labels but usually they do
                    }
                });

                statusBox.innerHTML = '<div class="rounded-md bg-green-50 p-4"><div class="flex"><div class="flex-shrink-0"><i class="fa-solid fa-circle-check text-green-400"></i></div><div class="ml-3"><p class="text-sm font-medium text-green-800">JSON parsing successful!</p></div></div></div>';

                $('#jsonPreviewEmpty').hide();
                const previewContainer = $('#jsonPreview');
                // Remove the empty state overlay
                previewContainer.find('#jsonPreviewEmpty').remove();

                // Initialize formRender logic inside the preview container
                $('<div/>').appendTo(previewContainer).formRender({
                    formData: parsed,
                    dataType: 'json'
                });

            } catch (e) {
                statusBox.innerHTML = '<div class="rounded-md bg-red-50 p-4"><div class="flex"><div class="flex-shrink-0"><i class="fa-solid fa-circle-xmark text-red-400"></i></div><div class="ml-3"><h3 class="text-sm font-medium text-red-800">Invalid JSON</h3><div class="mt-2 text-sm text-red-700"><p>' + e.message + '</p></div></div></div></div>';

                $('#jsonPreview').html('<div id="jsonPreviewEmpty" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400"><i class="fa-solid fa-bug text-4xl mb-3 opacity-50 text-red-400"></i><p class="text-sm font-medium text-red-500">Fix JSON errors to render preview</p></div>');
            }
        }

        function clearJSON() {
            document.getElementById('jsonInput').value = '';
            document.getElementById('jsonStatus').innerHTML = '';
            $('#jsonPreview').html('<div id="jsonPreviewEmpty" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400"><i class="fa-solid fa-magnifying-glass text-4xl mb-3 opacity-50"></i><p class="text-sm font-medium">Preview will appear here after JSON validation</p></div>');
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

                        // Update preview and sync
                        setTimeout(() => {
                            updateVisualPreview();
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