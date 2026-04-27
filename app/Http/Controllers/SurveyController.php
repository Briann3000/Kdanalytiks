<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QuestionLibrary;
use App\Models\Survey;
use App\Models\Response;
use App\Models\Answer;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SurveyResponsesExport;

class SurveyController extends Controller
{
    public function index(Request $request)
    {
        $statusParam = $request->get('status', 'active');
        $status = match ($statusParam) {
            'draft' => \App\Enums\SurveyStatus::Draft,
            'archived' => \App\Enums\SurveyStatus::Archived,
            default => \App\Enums\SurveyStatus::Active,
        };

        return $this->filteredIndex($status, 'surveys.index');
    }

    public function hub()
    {
        $user = auth()->user();
        $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;

        if ($role === 'admin') {
            return redirect()->route('admin.surveys.index');
        } elseif ($role === 'organization' || $role === 'independent') {
            return redirect()->route('surveys.index', ['status' => 'active']);
        }

        return redirect()->route('surveys.public');
    }

    public function archivedIndex()
    {
        return $this->filteredIndex(\App\Enums\SurveyStatus::Archived, 'surveys.index');
    }

    public function draftsIndex()
    {
        return $this->filteredIndex(\App\Enums\SurveyStatus::Draft, 'surveys.index');
    }

    public function templatesIndex()
    {
        $templates = \App\Models\Survey::where('is_template', true)->get();
        return view('surveys.templates', [
            'role' => auth()->user()->role,
            'templates' => $templates
        ]);
    }

    public function cloneTemplate(\App\Models\Survey $survey)
    {
        if (!$survey->is_template) {
            abort(404);
        }

        $user = auth()->user();
        $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;

        $newSurvey = $survey->replicate();
        $newSurvey->title = $survey->title . ' (Copy)';
        $newSurvey->is_template = false;
        $newSurvey->status = \App\Enums\SurveyStatus::Draft;
        $newSurvey->created_by = $user->id;

        if ($role === 'organization') {
            $newSurvey->organization_id = $user->organization?->id;
        } elseif ($role === 'independent') {
            $newSurvey->independent_id = $user->independent?->id;
        }

        $newSurvey->save();

        return redirect()->route('surveys.edit', $newSurvey)->with('success', 'Project created from template.');
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
        return view($viewName, compact('surveys', 'role', 'status'));
    }


    public function create()
    {
        $user = auth()->user();
        $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;

        $survey = new \App\Models\Survey();
        $survey->title = 'Untitled Survey';
        $survey->category = \App\Enums\SurveyCategory::Others;
        $survey->status = \App\Enums\SurveyStatus::Draft;
        $survey->type = \App\Enums\SurveyType::Public;
        $survey->created_by = $user->id;

        if ($role === 'organization') {
            $survey->organization_id = $user->organization?->id;
        } elseif ($role === 'independent') {
            $survey->independent_id = $user->independent?->id;
        }

        if ($user->organization?->hasReachedSurveyLimit()) {
            // We allow creation of the DRAFT via GET (relaxed middleware),
            // but we ensure any critical actions are aware of the limit state.
        }

        $survey->save();

        return redirect()->route('surveys.edit', $survey);
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
        $survey->public_access = 'submit';
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
        return view('surveys.summary', compact('survey'));
    }

    public function projectSettings(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);
        $survey->load('collaborators.user');

        if (!$survey->share_token) {
            $survey->update(['share_token' => Str::random(32)]);
        }

        return view('surveys.settings', compact('survey'));
    }

    public function updateProjectSettings(Request $request, \App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_anonymous' => 'nullable',
            'public_access' => 'nullable|string|in:none,view,submit,edit',
            'logo' => 'nullable|image|max:2048',
            'brand_color' => 'nullable|string|max:7',
            'remove_km_branding' => 'nullable',
            'export_logo' => 'nullable|image|max:2048',
            'export_org_name' => 'nullable|string|max:255',
        ]);

        $updateData = [];

        if ($request->has('title'))
            $updateData['title'] = $validated['title'];
        if ($request->has('description'))
            $updateData['description'] = $validated['description'];
        if ($request->has('is_anonymous_present')) { // We should use a hidden field to detect checkbox presence or just handle it
            $updateData['is_anonymous'] = $request->has('is_anonymous');
        } else if ($request->has('title')) {
            // If title is present, we assume it's the general settings form
            $updateData['is_anonymous'] = $request->has('is_anonymous');
        }

        if ($request->has('public_access'))
            $updateData['public_access'] = $validated['public_access'];
        if ($request->has('brand_color'))
            $updateData['brand_color'] = $validated['brand_color'];

        if ($request->has('remove_km_branding_present') || $request->has('export_org_name')) {
            $updateData['remove_km_branding'] = $request->has('remove_km_branding');
            $updateData['export_org_name'] = $validated['export_org_name'] ?? null;
        }

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($survey->logo_url) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($survey->logo_url);
            }
            $path = $request->file('logo')->store('survey_logos', 'public');
            $updateData['logo_url'] = $path;
        }

        if ($request->hasFile('export_logo')) {
            // Delete old export logo if exists
            if ($survey->export_logo_url) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($survey->export_logo_url);
            }
            $path = $request->file('export_logo')->store('export_logos', 'public');
            $updateData['export_logo_url'] = $path;
        }

        if (!empty($updateData)) {
            $survey->update($updateData);
        }

        return back()->with('success', 'Settings updated successfully.');
    }

    private function getBrandingContext(\App\Models\Survey $survey): array
    {
        $user = auth()->user();
        $tier = $this->getCurrentTier();
        $canControl = in_array($tier, ['pro', 'enterprise', 'respondent-pro']);

        return [
            'showKmBranding' => !($canControl && ($user->remove_km_branding || $survey->remove_km_branding)),
            'customLogo' => ($canControl) ? ($survey->export_logo_url ?: $user->export_logo_url) : null,
            'customOrgName' => ($canControl) ? ($survey->export_org_name ?: $user->export_org_name) : null,
            'logoUrl' => ($canControl && $survey->export_logo_url) ? route('surveys.branding.logo', $survey) : null,
        ];
    }

    public function serveBrandingLogo(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        if (!$survey->export_logo_url) {
            abort(404);
        }

        $path = storage_path('app/public/' . $survey->export_logo_url);

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path);
    }

    public function addCollaborator(Request $request, \App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if ($user->id === (int) $survey->created_by) {
            return back()->with('error', 'Owner already has full access.');
        }

        // Available permission keys after simplification
        $permissionKeys = [
            'view_form',
            'edit_form',
            'view_submissions',
            'add_submissions',
            'edit_submissions',
            'validate_submissions',
            'delete_submissions',
            'manage_project'
        ];

        $permissions = [];
        foreach ($permissionKeys as $key) {
            $permissions[$key] = $request->has($key);
        }

        // Default if none provided (Basic viewer)
        if (!array_filter($permissions)) {
            $permissions['view_form'] = true;
            $permissions['add_submissions'] = true;
        }

        \App\Models\SurveyPermission::updateOrCreate(
            ['survey_id' => $survey->id, 'user_id' => $user->id],
            ['permissions' => $permissions]
        );

        return back()->with('success', 'Collaborator updated with granular permissions.');
    }

    public function removeCollaborator(\App\Models\Survey $survey, \App\Models\SurveyPermission $permission)
    {
        $this->authorizeOwner($survey);
        $permission->delete();
        return back()->with('success', 'Collaborator removed.');
    }

    public function archive(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);
        $survey->update(['status' => \App\Enums\SurveyStatus::Archived]);

        $user = auth()->user();
        $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;

        if ($role === 'admin') {
            return redirect()->route('admin.surveys.index')->with('success', 'Project archived successfully.');
        }

        return redirect()->route('surveys.index', ['status' => 'archived'])->with('success', 'Project archived successfully.');
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->organization?->hasReachedSurveyLimit()) {
            if ($request->expectsJson() || $request->isXmlHttpRequest()) {
                return response()->json(['errors' => ['limit' => ["Your subscription plan survey limit has been reached. Please upgrade to create more."]]], 422);
            }
            return redirect()->back()->with('error', "Your subscription plan survey limit has been reached. Please upgrade to create more.");
        }

        // This is now mainly called by the builder to update the draft/active survey
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'type' => 'required|string|in:public,invitation',
            'json_schema' => 'required|string',
            'is_paid' => 'nullable|boolean',
            'reward_per_response' => 'nullable|numeric|min:0',
            'reward_budget' => 'nullable|numeric|min:0',
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
        $survey->public_access = ($survey->type === \App\Enums\SurveyType::Public) ? 'submit' : 'none';
        $survey->json_schema = $validated['json_schema'];
        $survey->is_paid = $request->boolean('is_paid');
        $survey->reward_per_response = $request->input('reward_per_response', 0);
        $survey->reward_budget = $request->input('reward_budget', 0);

        if ($request->filled('status')) {
            $survey->status = \App\Enums\SurveyStatus::tryFrom($request->status) ?? $survey->status;
        } elseif (!$survey->exists) {
            $survey->status = \App\Enums\SurveyStatus::Draft;
        }

        if (!$survey->exists) {
            $userRoleValue = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;
            if ($userRoleValue === 'organization') {
                $survey->organization_id = $user->organization?->id;
            } elseif ($userRoleValue === 'independent') {
                $survey->independent_id = $user->independent?->id;
            }
        }

        $survey->save();

        if ($request->expectsJson() || $request->isXmlHttpRequest() || $request->header('Accept') == 'application/json') {
            return response()->json([
                'success' => true,
                'survey_id' => $survey->id,
                'message' => 'Project saved successfully'
            ]);
        }

        $message = ($survey->status === \App\Enums\SurveyStatus::Active)
            ? 'Survey published successfully and is now LIVE!'
            : 'Survey saved as draft.';

        return redirect()->route('surveys.summary', $survey)->with('success', $message);
    }

    public function invite(Request $request, \App\Models\Survey $survey)
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

    public function responsesIndex()
    {
        $user = auth()->user();
        $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;

        $surveys = \App\Models\Survey::withCount('responses')->has('responses');

        if ($role === 'organization') {
            $orgId = $user->organization?->id;
            if ($orgId) {
                $surveys->where('organization_id', $orgId);
            } else {
                $surveys->whereRaw('1 = 0');
            }
        } elseif ($role === 'independent') {
            $indId = $user->independent?->id;
            if ($indId) {
                $surveys->where('independent_id', $indId);
            } else {
                $surveys->whereRaw('1 = 0');
            }
        }

        $surveys = $surveys->orderBy('updated_at', 'desc')->paginate(10);

        return view('responses.index', compact('surveys', 'role'));
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
        $responses = $survey->responses()->with('respondent', 'answers')->orderBy('created_at', 'desc')->paginate(15);
        $headers = $this->getSurveyAnalysisMetadata($survey);
        return view('surveys.data', compact('survey', 'responses', 'headers'));
    }

    public function showResponseDetail($survey_id, $response_id)
    {
        $survey = \App\Models\Survey::findOrFail($survey_id);
        $response = \App\Models\Response::findOrFail($response_id);

        $this->authorizeOwner($survey);

        if ($response->survey_id != $survey->id) {
            abort(404, 'Response does not belong to this survey');
        }

        return view('responses.detail', compact('survey', 'response'));
    }

    public function exportJson(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $tier = $this->getCurrentTier();
        if (!in_array($tier, ['pro', 'enterprise'])) {
            return back()->with('error', 'JSON Export is a Pro/Enterprise feature.');
        }

        $responses = $survey->responses()->with(['answers.question', 'respondent'])->get();

        $data = $responses->map(function ($response) use ($survey) {
            $answers = [];
            if (!empty($survey->json_schema)) {
                $jsonAnswer = $response->answers->first();
                $answers = $jsonAnswer ? json_decode($jsonAnswer->value, true) : [];
            } else {
                foreach ($response->answers as $answer) {
                    $answers[] = [
                        'question_id' => $answer->question_id,
                        'question_text' => $answer->question ? $answer->question->text : 'N/A',
                        'value' => $answer->value
                    ];
                }
            }

            return [
                'response_id' => $response->id,
                'timestamp' => $response->created_at->format('Y-m-d H:i:s'),
                'respondent' => [
                    'name' => $response->respondent ? $response->respondent->name : 'Anonymous',
                    'email' => $response->respondent ? $response->respondent->email : 'N/A'
                ],
                'answers' => $answers
            ];
        });

        $branding = $this->getBrandingContext($survey);

        $output = [
            'generated_by' => $branding['showKmBranding'] ? 'KMSurveyTool' : ($branding['customOrgName'] ?? 'Export System'),
            'survey_id' => $survey->id,
            'data' => $data
        ];

        $json = json_encode($output, JSON_PRETTY_PRINT);
        $filename = "survey_{$survey->id}_responses.json";

        // Save for History
        $exportDir = storage_path('app/public/exports/' . $survey->id . '/');
        if (!is_dir($exportDir))
            mkdir($exportDir, 0755, true);
        file_put_contents($exportDir . $filename, $json);

        return response($json)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    public function exportXml(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $tier = $this->getCurrentTier();
        if ($tier !== 'enterprise') {
            return back()->with('error', 'XML Export is an Enterprise feature.');
        }

        $responses = $survey->responses()->with(['answers.question', 'respondent'])->get();

        $branding = $this->getBrandingContext($survey);

        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        if ($branding['showKmBranding']) {
            $xml->writeComment(' Generated by KMSurveyTool | kmsurveytool.com ');
        } elseif ($branding['customOrgName']) {
            $xml->writeComment(' Generated for ' . $branding['customOrgName'] . ' ');
        }
        $xml->startElement('responses');
        $xml->writeAttribute('survey_id', $survey->id);
        $xml->writeAttribute('survey_title', $survey->title);

        foreach ($responses as $response) {
            $xml->startElement('response');
            $xml->writeElement('id', $response->id);
            $xml->writeElement('timestamp', $response->created_at->format('Y-m-d H:i:s'));

            $xml->startElement('respondent');
            $xml->writeElement('name', $response->respondent ? $response->respondent->name : 'Anonymous');
            $xml->writeElement('email', $response->respondent ? $response->respondent->email : 'N/A');
            $xml->endElement();

            $xml->startElement('answers');
            if (!empty($survey->json_schema)) {
                $jsonAnswer = $response->answers->first();
                $answers = $jsonAnswer ? json_decode($jsonAnswer->value, true) : [];
                foreach ($answers as $ans) {
                    $xml->startElement('answer');
                    $xml->writeElement('name', $ans['name'] ?? 'N/A');
                    $xml->writeElement('label', $ans['label'] ?? 'N/A');
                    $val = $ans['userData'] ?? '';
                    $xml->writeElement('value', is_array($val) ? implode(', ', $val) : $val);
                    $xml->endElement();
                }
            } else {
                foreach ($response->answers as $answer) {
                    $xml->startElement('answer');
                    $xml->writeElement('question_id', $answer->question_id);
                    $xml->writeElement('question_text', $answer->question ? $answer->question->text : 'N/A');
                    $xml->writeElement('value', $answer->value);
                    $xml->endElement();
                }
            }
            $xml->endElement(); // answers
            $xml->endElement(); // response
        }

        $xml->endElement(); // responses
        $xml->endDocument();

        $content = $xml->outputMemory();
        $filename = "survey_{$survey->id}_responses.xml";

        // Save for History
        $exportDir = storage_path('app/public/exports/' . $survey->id . '/');
        if (!is_dir($exportDir))
            mkdir($exportDir, 0755, true);
        file_put_contents($exportDir . $filename, $content);

        return response($content)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    public function exportXlsx(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $tier = $this->getCurrentTier();
        if (!in_array($tier, ['pro', 'enterprise'])) {
            return back()->with('error', 'Excel Export is a Pro/Enterprise feature.');
        }

        $responses = $survey->responses()->with(['answers.question', 'respondent'])->get();
        $filename = "survey_{$survey->id}_responses.xlsx";
        $exportPath = 'exports/' . $survey->id . '/' . $filename;

        // Save for History & Download
        \Maatwebsite\Excel\Facades\Excel::store(new \App\Exports\SurveyResponsesExport($survey, $responses), $exportPath, 'public');

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\SurveyResponsesExport($survey, $responses), $filename);
    }

    public function exportSpss(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $tier = $this->getCurrentTier();
        if ($tier !== 'enterprise') {
            return back()->with('error', 'SPSS Export is an Enterprise feature.');
        }

        $responses = $survey->responses()->with(['answers.question', 'respondent'])->get();

        $filename = "survey_{$survey->id}_responses.sav";
        $exportDir = storage_path('app/public/exports/' . $survey->id . '/');
        if (!is_dir($exportDir))
            mkdir($exportDir, 0755, true);
        $exportPath = $exportDir . $filename;

        // Prepare variables and data
        $variables = [
            [
                'name' => 'res_id',
                'format' => \SPSS\Sav\Variable::FORMAT_TYPE_F,
                'width' => 10,
                'label' => 'Response ID',
                'data' => $responses->pluck('id')->toArray()
            ],
            [
                'name' => 'date',
                'format' => \SPSS\Sav\Variable::FORMAT_TYPE_A,
                'width' => 20,
                'label' => 'Submission Date',
                'data' => $responses->map(fn($r) => $r->created_at->format('Y-m-d H:i:s'))->toArray()
            ],
            [
                'name' => 'name',
                'format' => \SPSS\Sav\Variable::FORMAT_TYPE_A,
                'width' => 100,
                'label' => 'Respondent Name',
                'data' => $responses->map(fn($r) => $r->respondent ? $r->respondent->name : 'Anonymous')->toArray()
            ],
            [
                'name' => 'email',
                'format' => \SPSS\Sav\Variable::FORMAT_TYPE_A,
                'width' => 100,
                'label' => 'Respondent Email',
                'data' => $responses->map(fn($r) => $r->respondent ? $r->respondent->email : 'N/A')->toArray()
            ],
        ];

        // Add questions as variables
        if (empty($survey->json_schema)) {
            foreach ($survey->questions()->orderBy('position')->get() as $q) {
                $varData = [];
                foreach ($responses as $response) {
                    $answer = $response->answers->where('question_id', $q->id)->first();
                    $varData[] = $answer ? (string) $answer->value : '';
                }
                $variables[] = [
                    'name' => 'q' . $q->id,
                    'format' => \SPSS\Sav\Variable::FORMAT_TYPE_A,
                    'width' => 255,
                    'label' => $q->text ?? $q->title,
                    'data' => $varData
                ];
            }
        }

        $branding = $this->getBrandingContext($survey);

        $writer = new \SPSS\Sav\Writer([
            'header' => [
                'prodName' => $branding['showKmBranding'] ? 'KMSurveyTool' : ($branding['customOrgName'] ?? ''),
                'creationDate' => date('d M y'),
                'creationTime' => date('H:i:s'),
            ],
            'variables' => $variables
        ]);

        $writer->save($exportPath);

        return response()->download($exportPath);
    }

    public function exportGoogleSheets(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $tier = $this->getCurrentTier();
        if ($tier !== 'enterprise') {
            return back()->with('error', 'Google Sheets Export is an Enterprise feature.');
        }

        if (!session()->has('google_token')) {
            session(['google_export_url' => url()->current()]);
            return redirect()->route('auth.google');
        }

        $token = session('google_token');

        // Refresh token if expired
        if (time() >= (($token['created'] ?? 0) + ($token['expires_in'] ?? 3600))) {
            if (isset($token['refresh_token'])) {
                $response = \Illuminate\Support\Facades\Http::asForm()->post('https://oauth2.googleapis.com/token', [
                    'refresh_token' => $token['refresh_token'],
                    'client_id' => config('services.google.client_id'),
                    'client_secret' => config('services.google.client_secret'),
                    'grant_type' => 'refresh_token',
                ]);

                if ($response->successful()) {
                    $newToken = $response->json();
                    $token = array_merge($token, $newToken);
                    $token['created'] = time();
                    session(['google_token' => $token]);
                } else {
                    session(['google_export_url' => url()->current()]);
                    return redirect()->route('auth.google');
                }
            } else {
                session(['google_export_url' => url()->current()]);
                return redirect()->route('auth.google');
            }
        }

        $responses = $survey->responses()->with(['answers.question', 'respondent'])->get();

        $branding = $this->getBrandingContext($survey);
        $titleSuffix = $branding['showKmBranding'] ? ' (via KMSurveyTool)' : ($branding['customOrgName'] ? ' (for ' . $branding['customOrgName'] . ')' : '');

        // 1. Create Spreadsheet
        $createResponse = \Illuminate\Support\Facades\Http::withToken($token['access_token'])->post('https://sheets.googleapis.com/v4/spreadsheets', [
            'properties' => [
                'title' => 'Survey Export: ' . $survey->title . ' (' . date('Y-m-d H:i') . ')' . $titleSuffix
            ]
        ]);

        if ($createResponse->failed()) {
            return back()->with('error', 'Failed to create Google Sheet: ' . ($createResponse->json()['error']['message'] ?? 'Unknown error'));
        }

        $spreadsheetId = $createResponse->json()['spreadsheetId'];

        // 2. Prepare Data
        $values = [];
        $headers = ['Response ID', 'Date', 'Respondent Name', 'Respondent Email'];
        if (!empty($survey->json_schema)) {
            $headers[] = 'Raw JSON Data';
        } else {
            foreach ($survey->questions()->orderBy('position')->get() as $q) {
                $headers[] = $q->text;
            }
        }
        $values[] = $headers;

        foreach ($responses as $response) {
            $row = [
                (string) $response->id,
                $response->created_at->format('Y-m-d H:i:s'),
                $response->respondent ? $response->respondent->name : 'Anonymous',
                $response->respondent ? $response->respondent->email : 'N/A'
            ];
            if (!empty($survey->json_schema)) {
                $jsonAnswer = $response->answers->first();
                $row[] = $jsonAnswer ? $jsonAnswer->value : '{}';
            } else {
                foreach ($survey->questions()->orderBy('position')->get() as $q) {
                    $answer = $response->answers->where('question_id', $q->id)->first();
                    $row[] = $answer ? (string) $answer->value : '';
                }
            }
            $values[] = $row;
        }

        // 3. Update Data
        $updateResponse = \Illuminate\Support\Facades\Http::withToken($token['access_token'])
            ->withQueryParameters(['valueInputOption' => 'RAW'])
            ->put("https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/Sheet1!A1", [
                'values' => $values
            ]);

        if ($updateResponse->failed()) {
            return back()->with('success', 'Sheet created but failed to upload data. Sheet ID: ' . $spreadsheetId);
        }

        return redirect()->away('https://docs.google.com/spreadsheets/d/' . $spreadsheetId)
            ->with('success', 'Survey data synced to Google Sheets successfully!');
    }

    public function exportResponses(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $responses = $survey->responses()->with(['answers.question', 'respondent'])->get();

        $filename = "survey_{$survey->id}_responses.csv";
        $handle = fopen('php://temp', 'w+');

        $branding = $this->getBrandingContext($survey);

        if ($branding['showKmBranding']) {
            fputcsv($handle, ['# Exported via KMSurveyTool — kmsurveytool.com']);
        } elseif ($branding['customOrgName']) {
            fputcsv($handle, ['# Exported for ' . $branding['customOrgName']]);
        }

        $headers = ['Response ID', 'Date', 'Respondent Email', 'Respondent Name'];
        $isJson = !empty($survey->json_schema) && $survey->json_schema !== '[]';
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

            $isJson = !empty($survey->json_schema) && $survey->json_schema !== '[]';
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

        // Save for History
        $exportDir = storage_path('app/public/exports/' . $survey->id . '/');
        if (!is_dir($exportDir))
            mkdir($exportDir, 0755, true);
        file_put_contents($exportDir . $filename, $csv);

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
    public function report(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $responses = $survey->responses()->with('answers.question')->get();
        $isJson = !empty($survey->json_schema) && $survey->json_schema !== '[]';

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
                    if ($found)
                        $answeredCount++;
                }

                $missingCount = $totalResponses - $answeredCount;
                $isChartable = in_array($type, ['select', 'select_one', 'select_many', 'radio-group', 'checkbox-group', 'number', 'decimal', 'rating', 'range', 'ranking']);
                $canvasId = 'chart-' . str_replace('-', '_', $fieldId);

                // Calculate Statistics
                $stats = [];
                foreach ($frequencyCount as $val => $count) {
                    $stats[] = [
                        'value' => (string) $val,
                        'count' => (int) $count,
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

        return view('surveys.reports', compact('survey', 'responses', 'analysis', 'chartConfigs', 'aiSummary'));
    }

    public function exportPdf(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $responses = $survey->responses()->with('answers.question')->get();
        $isJson = !empty($survey->json_schema) && $survey->json_schema !== '[]';
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
                    'isChartable' => in_array($type, ['select', 'select_one', 'select_many', 'radio-group', 'checkbox-group', 'number', 'decimal', 'rating', 'range', 'ranking']),
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

        $branding = $this->getBrandingContext($survey);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf', compact('survey', 'responses', 'analysis', 'branding'));
        $filename = "Analytical_Report_" . Str::slug($survey->title) . "_" . date('Ymd_His') . ".pdf";

        // Save for History
        $exportDir = storage_path('app/public/exports/' . $survey->id . '/');
        if (!is_dir($exportDir))
            mkdir($exportDir, 0755, true);
        $output = $pdf->output();
        file_put_contents($exportDir . $filename, $output);

        return $pdf->download($filename);
    }


    public function publish(Request $request, \App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $user = auth()->user();
        if ($survey->status !== \App\Enums\SurveyStatus::Active && $user->organization?->hasReachedSurveyLimit()) {
            return back()->with('error', 'Limit Reached: You cannot publish more surveys on your current plan. Please upgrade.');
        }

        $survey->update(['status' => \App\Enums\SurveyStatus::Active]);
        return back()->with('success', 'Project deployed successfully and is now live!');
    }

    public function edit(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $limitReached = false;
        $user = auth()->user();
        if ($user->organization) {
            $tier = $user->organization->subscriptionTier ?? \App\Models\SubscriptionTier::where('slug', 'free')->first();
            if ($tier->max_surveys !== -1 && $user->organization->surveys()->count() >= $tier->max_surveys) {
                $limitReached = true;
            }
        }

        return view('surveys.builder', compact('survey', 'limitReached'));
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
            'is_paid' => 'nullable|boolean',
            'reward_per_response' => 'nullable|numeric|min:0',
            'reward_budget' => 'nullable|numeric|min:0',
        ]);

        $survey->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'type' => $validated['type'],
            'public_access' => ($validated['type'] === 'public') ? 'submit' : 'none',
            'json_schema' => $validated['json_schema'],
            'is_paid' => $request->boolean('is_paid'),
            'reward_per_response' => $request->input('reward_per_response', 0),
            'reward_budget' => $request->input('reward_budget', 0),
        ]);

        if ($request->filled('status')) {
            $survey->status = \App\Enums\SurveyStatus::tryFrom($request->status) ?? $survey->status;
        }

        $survey->save();

        if ($request->expectsJson() || $request->isXmlHttpRequest() || $request->header('Accept') == 'application/json') {
            return response()->json([
                'success' => true,
                'survey_id' => $survey->id,
                'message' => 'Survey updated successfully'
            ]);
        }

        $message = ($survey->status === \App\Enums\SurveyStatus::Active)
            ? 'Survey published successfully and is now LIVE!'
            : 'Survey updated successfully.';

        return redirect()->route('surveys.summary', $survey)->with('success', $message);
    }

    public function destroy(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);
        $survey->delete();

        $user = auth()->user();
        $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;

        if ($role === 'admin') {
            return redirect()->route('admin.surveys.index')->with('success', 'Survey deleted successfully.');
        }

        return redirect()->route('surveys.index', ['status' => 'active'])->with('success', 'Survey deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'survey_ids' => 'required|array',
            'survey_ids.*' => 'exists:surveys,id'
        ]);

        $user = auth()->user();
        $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;

        $query = \App\Models\Survey::whereIn('id', $request->survey_ids);

        // Security: Ensure the user owns the surveys being deleted
        if ($role === 'organization') {
            $query->where('organization_id', $user->organization?->id);
        } elseif ($role === 'independent') {
            $query->where('independent_id', $user->independent?->id);
        } else {
            $query->where('created_by', $user->id);
        }

        $count = $query->count();
        $query->delete();

        if ($request->expectsJson() || $request->isXmlHttpRequest()) {
            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$count} surveys."
            ]);
        }

        return back()->with('success', "Successfully deleted {$count} surveys.");
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

        if ($request->filled('paid_status')) {
            if ($request->paid_status === 'paid') {
                $query->where('is_paid', true)
                    ->whereRaw('(reward_budget - current_reward_spent) >= reward_per_response');
            } elseif ($request->paid_status === 'unpaid') {
                $query->where('is_paid', false);
            } elseif ($request->paid_status === 'exhausted') {
                $query->where('is_paid', true)
                    ->whereRaw('(reward_budget - current_reward_spent) < reward_per_response');
            }
        }

        $surveys = $query->latest()->paginate(12);

        $categories = \App\Enums\SurveyCategory::cases();

        return view('surveys.public_list', compact('surveys', 'categories'));
    }

    public function show(\App\Models\Survey $survey)
    {
        // Public view for taking the survey
        $user = auth()->user();
        $token = request('token');

        $isOwner = $user && ($survey->created_by == $user->id);
        $isAdmin = $user && $user->isAdmin();
        $isCollaborator = $user && $survey->collaborators()->where('user_id', $user->id)->exists();

        // Handle Sharing Token Access
        $hasToken = $token && $survey->share_token === $token;

        // A survey is viewable if it is public, has explicit view permissions, or has a valid token
        $publicCanView = ($survey->type === \App\Enums\SurveyType::Public) || ($survey->public_access !== 'none') || $hasToken;

        $isActive = ($survey->status === \App\Enums\SurveyStatus::Active) || $survey->is_template;

        // If not active and not template, only certain people can see
        if (!$isActive && !$isOwner && !$isAdmin && !$isCollaborator && !$hasToken) {
            abort(403, 'This survey is not active or you do not have permission to view it.');
        }

        // Check if user is forbidden from viewing based on public_access
        // Templates are publically viewable by authenticated users
        if (!$isOwner && !$isAdmin && !$isCollaborator && !$publicCanView && !$survey->is_template) {
            abort(403, 'Access denied. You need permission to view this survey.');
        }

        // Check if monetization budget is exhausted (for warnings)
        $budgetExhausted = $survey->is_paid && ($survey->current_reward_spent >= $survey->reward_budget);

        return view('surveys.show_public', compact('survey', 'budgetExhausted'));
    }

    public function submit(Request $request, \App\Models\Survey $survey)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'terms_and_conditions' => 'required|accepted',
            'json_data' => 'nullable|string|max:65535'
        ]);

        if ($validator->fails()) {
            if ($request->has('is_json_submission')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must agree to the Terms and Conditions to proceed.'
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // Create response record
            $response = new \App\Models\Response();
            $response->survey_id = $survey->id;
            $response->respondent_id = auth()->id(); // Will be null for guests
            $response->save();

            // --- Reward Logic Start ---
            $rewardMessage = '';
            if ($survey->is_paid && $survey->reward_per_response > 0 && auth()->check()) {
                $user = auth()->user();

                // Check if user already responded to this survey to prevent double reward
                $alreadyRewarded = \App\Models\Transaction::where('wallet_id', $user->wallet?->id)
                    ->where('type', 'credit')
                    ->where('description', 'like', "%Survey ID: {$survey->id}%")
                    ->exists();

                if (!$alreadyRewarded) {
                    // Lock the survey record to prevent concurrent budget exhaustion Race Condition
                    $surveyLocked = \App\Models\Survey::where('id', $survey->id)->lockForUpdate()->first();

                    if ($surveyLocked && ($surveyLocked->current_reward_spent + (float) $surveyLocked->reward_per_response <= (float) $surveyLocked->reward_budget)) {
                        // 1. Update Survey Spent
                        $surveyLocked->increment('current_reward_spent', (float) $surveyLocked->reward_per_response);

                        // Auto-close removed as per user request to allow continued unpaid submissions

                        // 2. Get or Create Wallet
                        $wallet = $user->wallet ?: \App\Models\Wallet::create(['user_id' => $user->id, 'balance' => 0]);

                        // 3. Increment Wallet Balance
                        $wallet->increment('balance', (float) $surveyLocked->reward_per_response);

                        // 4. Record Transaction
                        \App\Models\Transaction::create([
                            'wallet_id' => $wallet->id,
                            'amount' => (float) $surveyLocked->reward_per_response,
                            'type' => 'credit',
                            'status' => 'completed',
                            'reference' => 'REW-' . strtoupper(Str::random(10)),
                            'description' => "Reward for completing Survey ID: {$surveyLocked->id}"
                        ]);

                        $rewardMessage = " You earned " . number_format((float) $surveyLocked->reward_per_response, 2) . " " . ($wallet->currency ?? 'KES') . "!";
                    }
                }
            }
            // --- Reward Logic End ---

            $uploadDir = storage_path('app/public/uploads/');
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
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
                'audio/mp4',
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp'
            ];

            if ($request->has('is_json_submission')) {
                $jsonData = json_decode($request->input('json_data'), true, 20) ?? [];

                foreach ($request->allFiles() as $name => $file) {
                    $storagePath = $this->handleSecureUpload($file, $uploadDir, $allowedMimeTypes);

                    if ($storagePath) {
                        foreach ($jsonData as &$item) {
                            if (isset($item['name']) && $item['name'] === $name) {
                                $item['userData'] = $storagePath;
                            }
                        }
                    }
                }

                $answer = new \App\Models\Answer();
                $answer->response_id = $response->id;
                $answer->question_id = null;
                $answer->value = json_encode($jsonData);
                $answer->save();

                \Illuminate\Support\Facades\DB::commit();

                // AI Sentiment Analysis (Background-ish)
                try {
                    (new \App\Services\AiService())->analyzeResponseSentiment($response);
                } catch (\Exception $e) {
                    \Log::error("AI Background Error: " . $e->getMessage());
                }

                session()->flash('success', 'Thank you for completing the survey!' . $rewardMessage);
                return response()->json(['success' => true]);
            }

            // Legacy Form Submission logic
            $questions = $survey->questions;
            foreach ($questions as $question) {
                $inputName = 'question_' . $question->id;
                $finalAnswerValue = '';

                if (in_array($question->type, ['video', 'audio', 'image', 'file']) && $request->hasFile($inputName)) {
                    $file = $request->file($inputName);
                    $finalAnswerValue = $this->handleSecureUpload($file, $uploadDir, $allowedMimeTypes) ?? '';
                } else {
                    $answerValue = $request->input($inputName, '');
                    if (is_array($answerValue)) {
                        $answerValue = implode(', ', $answerValue);
                    }
                    $finalAnswerValue = htmlspecialchars($answerValue);
                }

                if ($finalAnswerValue !== '') {
                    $answer = new \App\Models\Answer();
                    $answer->response_id = $response->id;
                    $answer->question_id = $question->id;
                    $answer->value = $finalAnswerValue;
                    $answer->save();
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            try {
                (new \App\Services\AiService())->analyzeResponseSentiment($response);
            } catch (\Exception $e) {
                \Log::error("AI Background Error: " . $e->getMessage());
            }

            return redirect()->back()->with('success', 'Thank you for completing the survey!' . $rewardMessage);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Log::error("Submission Error: " . $e->getMessage());

            if ($request->has('is_json_submission')) {
                return response()->json(['success' => false, 'message' => 'Critical Error: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->withErrors(['submission' => 'An error occurred. ' . $e->getMessage()])->withInput();
        }
    }

    private function getSurveyAnalysisMetadata(\App\Models\Survey $survey)
    {
        $analysis = [];
        $isJson = !empty($survey->json_schema) && $survey->json_schema !== '[]';

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
        \Illuminate\Support\Facades\Gate::authorize('view', $survey);
    }

    public function getLibraryQuestions()
    {
        $questions = QuestionLibrary::where('user_id', auth()->id())
            ->orWhere('is_public', true)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'type' => $item->type,
                    'content' => is_string($item->content_json) ? json_decode($item->content_json, true) : $item->content_json,
                    'is_template' => false
                ];
            });

        $dbTemplates = \App\Models\Survey::where('is_template', true)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => 'db-tpl-' . $item->id,
                    'title' => $item->title,
                    'type' => $item->category instanceof \UnitEnum ? $item->category->name : (string) $item->category,
                    'is_template' => true,
                    'content' => is_string($item->json_schema) ? json_decode($item->json_schema, true) : $item->json_schema
                ];
            });

        return response()->json($questions->concat($dbTemplates));
    }

    public function saveToLibrary(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'type' => 'required|string',
            'content_json' => 'required',
            'category' => 'nullable|string'
        ]);

        $item = QuestionLibrary::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'type' => $validated['type'],
            'content_json' => $validated['content_json'],
            'category' => $validated['category'] ?? 'General'
        ]);

        return response()->json(['success' => true, 'message' => 'Question saved to library', 'item' => $item]);
    }

    public function showGallery(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $mediaFiles = [];
        $responses = $survey->responses()->with('answers')->get();

        foreach ($responses as $response) {
            foreach ($response->answers as $answer) {
                // Legacy / Direct Uploads
                if (str_starts_with($answer->value, 'uploads/')) {
                    $mediaFiles[] = $this->formatMediaItem($answer->value, $response->created_at);
                }
                // JSON Uploads (need to parse JSON if question_id is null)
                elseif ($answer->question_id === null) {
                    $data = json_decode($answer->value, true) ?? [];
                    foreach ($data as $item) {
                        if (isset($item['userData']) && is_string($item['userData']) && str_starts_with($item['userData'], 'uploads/')) {
                            $mediaFiles[] = $this->formatMediaItem($item['userData'], $response->created_at);
                        }
                    }
                }
            }
        }

        return view('surveys.gallery', compact('survey', 'mediaFiles'));
    }

    private function formatMediaItem($path, $date)
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $type = 'file';
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']))
            $type = 'image';
        elseif (in_array($ext, ['mp4', 'webm', 'ogg', 'mov']))
            $type = 'video';
        elseif (in_array($ext, ['mp3', 'wav', 'aac']))
            $type = 'audio';

        return [
            'path' => $path,
            'type' => $type,
            'date' => $date->format('M d, Y H:i'),
            'filename' => basename($path)
        ];
    }

    public function showDownloads(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $exports = [];
        $exportDir = storage_path('app/public/exports/' . $survey->id . '/');

        if (is_dir($exportDir)) {
            $fileEntries = array_diff(scandir($exportDir, SCANDIR_SORT_DESCENDING), ['.', '..']);
            foreach ($fileEntries as $file) {
                $filePath = $exportDir . $file;
                if (is_file($filePath)) {
                    $exports[] = [
                        'filename' => $file,
                        'name' => $file,
                        'extension' => pathinfo($file, PATHINFO_EXTENSION),
                        'date' => date('M d, Y H:i', filemtime($filePath)),
                        'size' => round(filesize($filePath) / 1024, 2) . ' KB'
                    ];
                }
            }
        }

        $currentTier = $this->getCurrentTier();
        return view('surveys.downloads', compact('survey', 'exports', 'currentTier'));
    }

    public function downloadsHistory(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $exports = [];
        $exportDir = storage_path('app/public/exports/' . $survey->id . '/');

        if (is_dir($exportDir)) {
            $fileEntries = array_diff(scandir($exportDir, SCANDIR_SORT_DESCENDING), ['.', '..']);
            foreach ($fileEntries as $file) {
                $filePath = $exportDir . $file;
                if (is_file($filePath)) {
                    $exports[] = [
                        'filename' => $file,
                        'name' => $file,
                        'extension' => strtoupper(pathinfo($file, PATHINFO_EXTENSION)),
                        'date' => date('M d, Y H:i', filemtime($filePath)),
                        'size' => round(filesize($filePath) / 1024, 2) . ' KB',
                        'download_url' => asset('storage/exports/' . $survey->id . '/' . $file),
                        'delete_url' => route('surveys.downloads.delete', [$survey->id, $file])
                    ];
                }
            }
        }

        return response()->json($exports);
    }

    private function getCurrentTier()
    {
        $user = auth()->user();
        if (!$user)
            return 'free';

        // Admin always has full access
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return 'enterprise';
        }

        $org = $user->organization;
        if ($org && $org->subscriptionTier) {
            return $org->subscriptionTier->slug;
        }

        $independent = $user->independent;
        if ($independent && $independent->subscriptionTier) {
            return $independent->subscriptionTier->slug;
        }

        // Respondent role check
        if ($user->role === \App\Enums\UserRole::Respondent || $user->role === 'respondent') {
            if ($user->subscriptionTier) {
                return $user->subscriptionTier->slug;
            }
        }

        return 'free';
    }

    public function deleteDownload(\App\Models\Survey $survey, $filename)
    {
        $this->authorizeOwner($survey);

        // Sanitize filename to prevent directory traversal
        $filename = basename($filename);
        $filePath = storage_path('app/public/exports/' . $survey->id . '/' . $filename);

        if (file_exists($filePath) && is_file($filePath)) {
            unlink($filePath);
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'File not found'], 404);
    }

    /**
     * Securely handle file uploads for survey responses.
     * Prevents malicious uploads by validating MIME types, file size, and sanitizing names.
     */
    private function handleSecureUpload($file, $uploadDir, $allowedMimeTypes)
    {
        if (!$file->isValid()) {
            return null;
        }

        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $allowedMimeTypes)) {
            \Log::warning("Security: Blocked unauthorized file type attempt: " . $mimeType);
            return null;
        }

        // 10MB limit for images, 50MB for video/audio/other media
        $isImage = str_starts_with($mimeType, 'image/');
        $maxSize = $isImage ? 10 * 1024 * 1024 : 50 * 1024 * 1024;

        if ($file->getSize() > $maxSize) {
            \Log::warning("Security: Blocked file exceeding size limit. Size: " . $file->getSize() . " bytes");
            return null;
        }

        // Sanitize extension and generate random filename
        $extension = $file->getClientOriginalExtension();
        // Fallback or override if extension is missing or too long
        if (empty($extension) || strlen($extension) > 5) {
            $extension = $file->extension();
        }

        // Block executable or dangerous extensions just in case (redundant due to MIME whitelist)
        $dangerous = ['php', 'php5', 'phtml', 'exe', 'sh', 'js', 'html', 'htm'];
        if (in_array(strtolower($extension), $dangerous)) {
            \Log::error("Security: Blocked suspicious extension: " . $extension);
            return null;
        }

        $safeFileName = 'res_' . bin2hex(random_bytes(16)) . '.' . $extension;
        $file->move($uploadDir, $safeFileName);

        return 'uploads/' . $safeFileName;
    }
}
