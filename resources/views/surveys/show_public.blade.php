@extends('layouts.app')

@section('title', $survey->title)

@section('head')
    @if(!empty($survey->json_schema))
        <!-- jQuery Form Builder CSS -->
        <link href="https://formbuilder.online/assets/css/form-render.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
        <script src="https://formbuilder.online/assets/js/form-render.min.js"></script>
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
        .rendered-form label {
            display: block !important;
            font-size: 1.125rem !important;
            font-weight: 700 !important;
            color: #111827 !important;
            margin-bottom: 0.75rem !important;
            line-height: 1.5 !important;
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

                            const renderOptions = {
                                formData: surveyData,
                                dataType: 'json'
                            };

                            const formRenderInstance = container.formRender(renderOptions);
                            
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
                                
                                const submitBtn = $(this).find('button[type="submit"]');
                                const originalText = submitBtn.html();
                                submitBtn.html('<i class="fa-solid fa-spinner fa-spin mr-2"></i> Submitting...').prop('disabled', true);

                                const formData = new FormData(this);
                                formData.append('is_json_submission', '1');
                                formData.append('json_data', JSON.stringify(formRenderInstance.userData));

                                // Find and append any file inputs generated by formRender
                                $(this).find('input[type="file"]').each(function() {
                                    if (this.files.length > 0) {
                                        formData.append(this.name, this.files[0]);
                                    }
                                });

                                fetch(this.action, {
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
                                    if(res.status === 422 || !data.success) { // Catch Validation errors
                                        if (data.message && data.message.includes('terms')) {
                                            alert('Please agree to the Terms and Conditions to proceed.');
                                        } else {
                                             alert('Error submitting survey: ' + (data.message || 'Validation failed.'));
                                        }
                                        submitBtn.html(originalText).prop('disabled', false);
                                    } else {
                                        // Success 
                                        window.location.reload();
                                    }
                                })
                                .catch(err => {
                                    console.error(err);
                                    alert(err.message || 'Error submitting survey, please try again.');
                                    submitBtn.html(originalText).prop('disabled', false);
                                });
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
                        });
                    </script>
                @endif
            </div>

        @endif
    </div>
</div>
@endsection
