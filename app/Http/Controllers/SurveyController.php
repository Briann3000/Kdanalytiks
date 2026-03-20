<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SurveyController extends Controller
{
    public function index(Request $request)
    {
        return $this->filteredIndex(\App\Enums\SurveyStatus::Active, 'projects.active');
    }

    public function hub()
    {
        return view('projects.hub', ['role' => auth()->user()->role]);
    }

    public function archivedIndex()
    {
        return $this->filteredIndex(\App\Enums\SurveyStatus::Archived, 'projects.archived');
    }

    public function draftsIndex()
    {
        return $this->filteredIndex(\App\Enums\SurveyStatus::Draft, 'projects.drafts');
    }

    public function templatesIndex()
    {
        // For now, templates are just a future feature or a subset of surveys
        return view('surveys.templates', ['role' => auth()->user()->role]);
    }

    private function filteredIndex($status, $viewName)
    {
        $user = auth()->user();
        $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;

        $query = \App\Models\Survey::where('status', $status)->withCount('responses');

        if (request()->filled('category')) {
            $query->where('category', request('category'));
        }

        if (request()->filled('search')) {
            $query->where('title', 'like', '%' . request('search') . '%');
        }

        if ($role === 'organization') {
            $query->where('organization_id', $user->organization?->id);
        } elseif ($role === 'independent') {
            $query->where('independent_id', $user->independent?->id);
        }

        $surveys = $query->orderBy('created_at', 'desc')->paginate(10);
        return view('surveys.index', compact('surveys', 'role', 'status'));
    }

    public function create()
    {
        return view('surveys.choose_category');
    }

    /**
     * Step 2 of creation: Initialize a draft with a category
     */
    public function initialize(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string',
        ]);

        $user = auth()->user();
        $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;

        $survey = new \App\Models\Survey();
        $survey->title = $validated['title'];
        $survey->category = $validated['category'];
        $survey->status = \App\Enums\SurveyStatus::Draft;
        $survey->type = \App\Enums\SurveyType::Public;
        $survey->created_by = $user->id;

        if ($role === 'organization') {
            $survey->organization_id = $user->organization?->id;
        } elseif ($role === 'independent') {
            $survey->independent_id = $user->independent?->id;
        }

        $survey->save();

        return redirect()->route('surveys.edit', $survey);
    }

    public function projectSummary(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);
        $survey->loadCount('responses');
        return view('surveys.project_summary', compact('survey'));
    }

    public function projectSettings(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);
        return view('surveys.project_settings', compact('survey'));
    }

    public function archive(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);
        $survey->update(['status' => \App\Enums\SurveyStatus::Archived]);
        return redirect()->route('projects.index')->with('success', 'Project archived successfully.');
    }

    public function store(Request $request)
    {
        // This is now mainly called by the builder to update the draft/active survey
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'type' => 'required|string|in:public,invitation',
            'json_schema' => 'required|string',
        ]);

        $user = auth()->user();
        $surveyId = $request->input('survey_id');
        
        if ($surveyId) {
            $survey = \App\Models\Survey::findOrFail($surveyId);
            $this->authorizeOwner($survey);
        } else {
            $survey = new \App\Models\Survey();
            $survey->created_by = $user->id;
        }

        $survey->title = $validated['title'];
        $survey->description = $validated['description'];
        $survey->category = $validated['category'];
        $survey->type = \App\Enums\SurveyType::tryFrom($validated['type']) ?? \App\Enums\SurveyType::Public;
        $survey->json_schema = $validated['json_schema'];

        if (!$survey->exists) {
            $survey->status = \App\Enums\SurveyStatus::Draft;
            $userRoleValue = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;
            if ($userRoleValue === 'organization') {
                $survey->organization_id = $user->organization?->id;
            } elseif ($userRoleValue === 'independent') {
                $survey->independent_id = $user->independent?->id;
            }
        }

        $survey->save();

        return response()->json([
            'success' => true,
            'survey_id' => $survey->id,
            'message' => 'Project saved successfully'
        ]);
    }

    public function invite(Request $request, \App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $request->validate([
            'emails' => 'required|string',
        ]);

        $emails = array_map('trim', explode(',', $request->emails));
        $count = 0;

        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // In a real app, you would send an email here
                $count++;
            }
        }

        return back()->with('success', "Invitations sent to {$count} participants.");
    }

    public function responsesIndex()
    {
        $user = auth()->user();
        $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;

        $responses = \App\Models\Response::with('survey', 'respondent');

        if ($role === 'organization') {
            $orgId = $user->organization?->id;
            if ($orgId) {
                $responses = $responses->whereHas('survey', function ($query) use ($orgId) {
                    $query->where('organization_id', $orgId);
                });
            } else {
                $responses->whereRaw('1 = 0'); // Return nothing if no org linked
            }
        } elseif ($role === 'independent') {
            $indId = $user->independent?->id;
            if ($indId) {
                $responses = $responses->whereHas('survey', function ($query) use ($indId) {
                    $query->where('independent_id', $indId);
                });
            } else {
                $responses->whereRaw('1 = 0');
            }
        }

        $responses = $responses->orderBy('created_at', 'desc')->paginate(10);

        return view('responses.index', compact('responses', 'role'));
    }

    public function reportsIndex()
    {
        $user = auth()->user();
        $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;

        $surveys = \App\Models\Survey::withCount('responses');

        if (request()->filled('category')) {
            $surveys->where('category', request('category'));
        }

        if (request()->filled('search')) {
            $surveys->where('title', 'like', '%' . request('search') . '%');
        }

        if ($role === 'organization') {
            $orgId = $user->organization?->id;
            if ($orgId) {
                $surveys = $surveys->where('organization_id', $orgId);
            } else {
                $surveys->whereRaw('1 = 0');
            }
        } elseif ($role === 'independent') {
            $indId = $user->independent?->id;
            if ($indId) {
                $surveys = $surveys->where('independent_id', $indId);
            } else {
                $surveys->whereRaw('1 = 0');
            }
        } elseif ($role === 'respondent') {
            $surveys = $surveys->whereHas('responses', function ($query) use ($user) {
                $query->where('respondent_id', $user->id);
            });
        }

        $surveys = $surveys->orderBy('created_at', 'desc')->paginate(10);

        return view('reports.index', compact('surveys', 'role'));
    }

    public function showResponses(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);
        $responses = $survey->responses()->with('respondent')->orderBy('created_at', 'desc')->paginate(15);
        return view('surveys.project_data', compact('survey', 'responses'));
    }

    public function showResponseDetail(\App\Models\Survey $survey, \App\Models\Response $response)
    {
        $this->authorizeOwner($survey);

        if ($response->survey_id !== $survey->id) {
            abort(404);
        }

        return view('responses.detail', compact('survey', 'response'));
    }

    public function exportResponses(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $responses = $survey->responses()->with(['answers.question', 'respondent'])->get();

        $filename = "survey_{$survey->id}_responses.csv";
        $handle = fopen('php://temp', 'w+');

        $headers = ['Response ID', 'Date', 'Respondent Email', 'Respondent Name'];
        $isJson = !empty($survey->json_schema);
        $questions = [];

        if ($isJson) {
            $headers[] = 'Raw JSON Data';
        } else {
            $questions = $survey->questions()->orderBy('position')->get();
            foreach ($questions as $q) {
                $headers[] = $q->text;
            }
        }

        fputcsv($handle, $headers);

        foreach ($responses as $response) {
            $row = [
                $response->id,
                $response->created_at->format('Y-m-d H:i:s'),
                $response->respondent ? $response->respondent->email : 'N/A',
                $response->respondent ? $response->respondent->name : 'Anonymous'
            ];

            if ($isJson) {
                $jsonAnswer = $response->answers->first();
                $row[] = $jsonAnswer ? $jsonAnswer->value : '{}';
            } else {
                foreach ($questions as $q) {
                    $answer = $response->answers->where('question_id', $q->id)->first();
                    $row[] = $answer ? $answer->value : '';
                }
            }

            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
    public function report(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $responses = $survey->responses()->with('answers.question')->get();
        $isJson = !empty($survey->json_schema);

        $totalResponses = $responses->count();
        $analysis = [];
        $chartConfigs = [];

        if ($isJson) {
            $schema = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : $survey->json_schema;
            $schema = is_array($schema) ? $schema : [];

            foreach ($schema as $field) {
                if (!isset($field['name']) || in_array($field['type'], ['header', 'paragraph']))
                    continue;

                $fieldId = $field['name'];
                $label = $field['label'] ?? $fieldId;
                $type = $field['type'] ?? 'text';

                $answersList = [];
                $frequencyCount = [];
                $answeredCount = 0;

                foreach ($responses as $response) {
                    $jsonAnswer = $response->answers->first();
                    $found = false;
                    if ($jsonAnswer) {
                        $parsedData = json_decode($jsonAnswer->value, true) ?? [];
                        if (is_array($parsedData)) {
                            foreach ($parsedData as $data) {
                                if (isset($data['name']) && $data['name'] === $fieldId && isset($data['userData'])) {
                                    $val = $data['userData'];
                                    if ($val !== '' && $val !== null && (!is_array($val) || !empty($val))) {
                                        $found = true;
                                        if (is_array($val)) {
                                            foreach ($val as $v) {
                                                $answersList[] = $v;
                                                $frequencyCount[$v] = ($frequencyCount[$v] ?? 0) + 1;
                                            }
                                        } else {
                                            $answersList[] = $val;
                                            $frequencyCount[$val] = ($frequencyCount[$val] ?? 0) + 1;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($found) $answeredCount++;
                }

                $missingCount = $totalResponses - $answeredCount;
                $isChartable = in_array($type, ['select', 'radio-group', 'checkbox-group', 'number']);
                $canvasId = 'chart-' . str_replace('-', '_', $fieldId);

                // Calculate Statistics
                $stats = [];
                foreach ($frequencyCount as $val => $count) {
                    $stats[] = [
                        'value' => (string)$val,
                        'count' => (int)$count,
                        'percentage' => $totalResponses > 0 ? round(($count / $totalResponses) * 100, 1) : 0
                    ];
                }
                
                // Add Missing data to stats for completeness
                $stats[] = [
                    'value' => '[Missing / Skipped]',
                    'count' => $missingCount,
                    'percentage' => $totalResponses > 0 ? round(($missingCount / $totalResponses) * 100, 1) : 0,
                    'is_missing' => true
                ];

                $analysis[] = [
                    'id' => $fieldId,
                    'survey_id' => $survey->id,
                    'label' => $label,
                    'type' => $type,
                    'isChartable' => $isChartable,
                    'canvasId' => $canvasId,
                    'answers' => $answersList,
                    'stats' => $stats,
                    'answered_count' => $answeredCount,
                    'missing_count' => $missingCount
                ];

                if ($isChartable && !empty($frequencyCount)) {
                    $chartConfigs[] = [
                        'canvas_id' => $canvasId,
                        'labels' => array_keys($frequencyCount),
                        'data' => array_values($frequencyCount)
                    ];
                }
            }

        } else {
            // Legacy Question format
            $questions = $survey->questions()->orderBy('position')->get();
            foreach ($questions as $question) {
                $answers = $question->answers;

                $answersList = [];
                $frequencyCount = [];
                $answeredCount = 0;

                foreach ($responses as $response) {
                    $answer = $answers->where('response_id', $response->id)->first();
                    if ($answer && $answer->value !== null && $answer->value !== '') {
                        $answeredCount++;
                        $answersList[] = $answer->value;
                        $frequencyCount[$answer->value] = ($frequencyCount[$answer->value] ?? 0) + 1;
                    }
                }

                $missingCount = $totalResponses - $answeredCount;
                $isChartable = in_array($question->type, ['radio', 'checkbox', 'select', 'number']);
                $canvasId = 'chart-question_' . $question->id;

                $stats = [];
                foreach ($frequencyCount as $val => $count) {
                    $stats[] = [
                        'value' => $val,
                        'count' => $count,
                        'percentage' => $totalResponses > 0 ? round(($count / $totalResponses) * 100, 1) : 0
                    ];
                }
                $stats[] = [
                    'value' => '[Missing / Skipped]',
                    'count' => $missingCount,
                    'percentage' => $totalResponses > 0 ? round(($missingCount / $totalResponses) * 100, 1) : 0,
                    'is_missing' => true
                ];

                $analysis[] = [
                    'id' => $question->id,
                    'survey_id' => $survey->id,
                    'label' => $question->text,
                    'type' => $question->type,
                    'isChartable' => $isChartable,
                    'canvasId' => $canvasId,
                    'answers' => $answersList,
                    'stats' => $stats,
                    'answered_count' => $answeredCount,
                    'missing_count' => $missingCount
                ];

                if ($isChartable && !empty($frequencyCount)) {
                    $chartConfigs[] = [
                        'canvas_id' => $canvasId,
                        'labels' => array_keys($frequencyCount),
                        'data' => array_values($frequencyCount)
                    ];
                }
            }
        }

        // Generate AI Executive Summary
        $aiSummary = "Generating AI insights...";
        try {
            $aiSummary = (new \App\Services\AiService())->generateSurveySummary($survey);
        } catch (\Exception $e) {
            \Log::error("AI Summary Error: " . $e->getMessage());
        }

        return view('surveys.project_reports', compact('survey', 'responses', 'analysis', 'chartConfigs', 'aiSummary'));
    }

    public function exportPdf(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $responses = $survey->responses()->with('answers.question')->get();
        $isJson = !empty($survey->json_schema);
        $analysis = [];

        if ($isJson) {
            $schema = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : $survey->json_schema;
            $schema = is_array($schema) ? $schema : [];

            foreach ($schema as $field) {
                if (!isset($field['name']) || in_array($field['type'], ['header', 'paragraph']))
                    continue;

                $fieldId = $field['name'];
                $label = $field['label'] ?? $fieldId;
                $type = $field['type'] ?? 'text';
                $answersList = [];

                foreach ($responses as $response) {
                    $jsonAnswer = $response->answers->first();
                    if ($jsonAnswer) {
                        $parsedData = json_decode($jsonAnswer->value, true) ?? [];
                        foreach ($parsedData as $data) {
                            if (isset($data['name']) && $data['name'] === $fieldId && isset($data['userData'])) {
                                $val = $data['userData'];
                                if (is_array($val)) {
                                    foreach ($val as $v)
                                        $answersList[] = $v;
                                } else {
                                    $answersList[] = $val;
                                }
                            }
                        }
                    }
                }

                $analysis[] = [
                    'label' => $label,
                    'type' => $type,
                    'isChartable' => in_array($type, ['select', 'radio-group', 'checkbox-group', 'number']),
                    'answers' => array_filter($answersList, fn($val) => $val !== null && $val !== '')
                ];
            }
        } else {
            $questions = $survey->questions()->orderBy('position')->get();
            foreach ($questions as $question) {
                $answersList = [];
                foreach ($question->answers as $answer) {
                    if ($answer->value !== null && $answer->value !== '') {
                        $answersList[] = $answer->value;
                    }
                }

                $analysis[] = [
                    'label' => $question->text,
                    'type' => $question->type,
                    'isChartable' => in_array($question->type, ['radio', 'checkbox', 'select', 'number']),
                    'answers' => $answersList
                ];
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf', compact('survey', 'responses', 'analysis'));
        $filename = "Analytical_Report_" . Str::slug($survey->title) . ".pdf";
        return $pdf->download($filename);
    }

    public function sendInvitation(Request $request, \App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $request->validate([
            'emails' => 'required|string'
        ]);

        // Parse comma, semicolon or newline separated emails
        $emailString = str_replace([';', "\n", "\r"], ',', $request->emails);
        $emailArray = array_map('trim', explode(',', $emailString));
        $validEmails = array_filter($emailArray, function ($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });

        if (empty($validEmails)) {
            return back()->with('error', 'No valid email addresses provided.');
        }

        $inviteUrl = route('surveys.show', $survey);

        foreach ($validEmails as $email) {
            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\SurveyInvitation($survey, $inviteUrl));
        }

        return back()->with('success', 'Invitations sent successfully to ' . count($validEmails) . ' recipients.');
    }

    public function publish(Request $request, \App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        if (auth()->user()->isAdmin()) {
            $survey->update(['status' => \App\Enums\SurveyStatus::Active]);
            return back()->with('success', 'Survey is now active.');
        }

        $survey->update(['status' => \App\Enums\SurveyStatus::PendingApproval]);
        return back()->with('success', 'Survey submitted for admin approval.');
    }

    public function edit(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);
        return view('surveys.builder', compact('survey'));
    }

    public function update(Request $request, \App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'type' => 'required|string|in:public,invitation',
            'json_schema' => 'required|string',
        ]);

        $survey->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'type' => $validated['type'],
            'json_schema' => $validated['json_schema'],
            'status' => \App\Enums\SurveyStatus::Draft // Revert to draft if edited? Or keep status?
        ]);

        return response()->json(['success' => true, 'message' => 'Survey updated successfully']);
    }

    public function destroy(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);
        $survey->delete();
        return back()->with('success', 'Survey deleted successfully.');
    }

    public function publicIndex(Request $request)
    {
        $query = \App\Models\Survey::where('status', \App\Enums\SurveyStatus::Active)
            ->where('type', \App\Enums\SurveyType::Public);

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $surveys = $query->latest()->paginate(12);

        $categories = \App\Enums\SurveyCategory::cases();

        return view('surveys.public_list', compact('surveys', 'categories'));
    }

    public function show(\App\Models\Survey $survey)
    {
        // Public view for taking the survey
        $user = auth()->user();
        $isOwner = $user && ($survey->created_by == $user->id);
        $isAdmin = $user && $user->isAdmin();

        $isActive = ($survey->status === \App\Enums\SurveyStatus::Active);

        if (!$isActive && !$isOwner && !$isAdmin) {
            abort(403, 'This survey is not active or you do not have permission to view it.');
        }

        return view('surveys.show_public', compact('survey'));
    }

    public function submit(Request $request, \App\Models\Survey $survey)
    {
        // Public survey submission CAPTCHA validation
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'captcha' => 'required|captcha'
        ], [
            'captcha.captcha' => 'The security verification code is incorrect. Please try again.'
        ]);

        if ($validator->fails()) {
            if ($request->has('is_json_submission')) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first('captcha')], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Create response record
        $response = new \App\Models\Response();
        $response->survey_id = $survey->id;
        $response->respondent_id = auth()->id(); // Will be null for guests
        $response->save();

        if ($request->has('is_json_submission')) {
            $answer = new \App\Models\Answer();
            $answer->response_id = $response->id;
            $answer->question_id = null;
            $answer->value = $request->input('json_data'); // Save the entire form output state as JSON
            $answer->save();

            // AI Sentiment Analysis Trigger
            try {
                (new \App\Services\AiService())->analyzeResponseSentiment($response);
            } catch (\Exception $e) {
                \Log::error("AI Background Error: " . $e->getMessage());
            }

            session()->flash('success', 'Thank you for completing the survey!');
            return response()->json(['success' => true]);
        }

        $uploadDir = storage_path('app/public/uploads/');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedMimeTypes = [
            'video/mp4',
            'video/webm',
            'video/ogg',
            'video/quicktime',
            'audio/mpeg',
            'audio/ogg',
            'audio/wav',
            'audio/webm',
            'audio/aac',
            'audio/mp4'
        ];

        $questions = $survey->questions;

        foreach ($questions as $question) {
            $inputName = 'question_' . $question->id;
            $finalAnswerValue = '';

            // Handle File Uploads
            if (in_array($question->type, ['video', 'audio']) && $request->hasFile($inputName)) {
                $file = $request->file($inputName);

                if ($file->isValid() && in_array($file->getMimeType(), $allowedMimeTypes)) {
                    $newFileName = uniqid('media_') . '_' . bin2hex(random_bytes(4)) . '.' . $file->getClientOriginalExtension();

                    // Move the file to storage/app/public/uploads
                    $file->move($uploadDir, $newFileName);

                    // Store the relative public path
                    $finalAnswerValue = 'uploads/' . $newFileName;
                } else {
                    \Log::warning("Blocked invalid file upload for question " . $question->id);
                }
            } else {
                // Handle Standard Inputs
                $answerValue = $request->input($inputName, '');

                if (is_array($answerValue)) {
                    $answerValue = implode(', ', $answerValue);
                }

                $finalAnswerValue = htmlspecialchars($answerValue);
            }

            // Save the answer if one was provided
            if ($finalAnswerValue !== '') {
                $answer = new \App\Models\Answer();
                $answer->response_id = $response->id;
                $answer->question_id = $question->id;
                $answer->value = $finalAnswerValue;
            }
        }

        // AI Sentiment Analysis Trigger for legacy forms
        try {
            (new \App\Services\AiService())->analyzeResponseSentiment($response);
        } catch (\Exception $e) {
            \Log::error("AI Background Error: " . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Thank you for completing the survey!');
    }


    private function getSurveyAnalysisMetadata(\App\Models\Survey $survey)
    {
        $analysis = [];
        $isJson = !empty($survey->json_schema);

        if ($isJson) {
            $schema = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : $survey->json_schema;
            $schema = is_array($schema) ? $schema : [];

            foreach ($schema as $field) {
                if (!isset($field['name']) || in_array($field['type'], ['header', 'paragraph']))
                    continue;

                $analysis[] = [
                    'id' => $field['name'],
                    'label' => $field['label'] ?? $field['name'],
                    'type' => $field['type'] ?? 'text',
                    'isChartable' => in_array($field['type'] ?? 'text', ['select', 'radio-group', 'checkbox-group', 'number']),
                ];
            }
        } else {
            $questions = $survey->questions()->orderBy('position')->get();
            foreach ($questions as $question) {
                $analysis[] = [
                    'id' => $question->id,
                    'label' => $question->text,
                    'type' => $question->type,
                    'isChartable' => in_array($question->type, ['radio', 'checkbox', 'select', 'number']),
                ];
            }
        }
        return $analysis;
    }

    private function authorizeOwner(\App\Models\Survey $survey)
    {
        $user = auth()->user();
        if (!$user)
            abort(403, 'Unauthorized action.');

        // Admins can do everything
        if ($user->isAdmin())
            return;

        // Check if the user is the direct creator
        if ((int) $survey->created_by === (int) $user->id) {
            return;
        }

        // Check organization ownership
        if ($survey->organization_id && $user->organization && (int) $survey->organization_id === (int) $user->organization->id) {
            return;
        }

        // Check independent ownership
        if ($survey->independent_id && $user->independent && (int) $survey->independent_id === (int) $user->independent->id) {
            return;
        }

        abort(403, 'Unauthorized action.');
    }
}
