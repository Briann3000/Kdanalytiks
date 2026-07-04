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
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

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
        $newSurvey->share_token = \Illuminate\Support\Str::random(32);
        $newSurvey->share_report_token = null;

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

        $query = \App\Models\Survey::where('is_template', false)->where('status', $status)->withCount('responses');

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

        $entity = ($role === 'organization') ? $user->organization : (($role === 'independent') ? $user->independent : null);
        if ($entity?->hasReachedSurveyLimit()) {
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
            'remove_kd_branding' => 'nullable',
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

        if ($request->has('remove_kd_branding_present') || $request->has('export_org_name')) {
            $updateData['remove_kd_branding'] = $request->has('remove_kd_branding');
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
            app(\App\Services\SurveyVersioningService::class)->createVersionIfChanged(
                $survey,
                [
                    'title' => $updateData['title'] ?? $survey->title,
                    'description' => $updateData['description'] ?? $survey->description,
                    'json_schema' => $survey->json_schema,
                ],
                auth()->id()
            );
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
            'showKdBranding' => !($canControl && ($user->remove_kd_branding || $survey->remove_kd_branding)),
            'customLogo' => ($canControl) ? ($survey->export_logo_url ?: $user->export_logo_url) : null,
            'customOrgName' => ($canControl) ? ($survey->export_org_name ?: $user->export_org_name) : null,
            'brandColor' => ($canControl) ? ($survey->brand_color ?: ($user->brand_color ?: '#4f46e5')) : '#4f46e5',
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
        $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;
        $entity = ($role === 'organization') ? $user->organization : (($role === 'independent') ? $user->independent : null);
        if ($entity?->hasReachedSurveyLimit()) {
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

        if ($survey->exists) {
            app(\App\Services\SurveyVersioningService::class)->createVersionIfChanged(
                $survey,
                [
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'json_schema' => $validated['json_schema'],
                ],
                $user->id
            );
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

        $query = $survey->responses()->with('respondent', 'answers');

        if (request()->filled('quality')) {
            $quality = request('quality');
            if ($quality === 'clean') {
                $query->where('is_flagged', false)->where('quality_score', '>=', 70);
            } elseif ($quality === 'review') {
                $query->where('is_flagged', false)->where('quality_score', '>=', 40)->where('quality_score', '<', 70);
            } elseif ($quality === 'flagged') {
                $query->where('is_flagged', true);
            }
        }

        $responses = $query->orderBy('created_at', 'desc')->paginate(15);
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
            'generated_by' => $branding['showKdBranding'] ? 'KDAnalytiks' : ($branding['customOrgName'] ?? 'Export System'),
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
        if ($branding['showKdBranding']) {
            $xml->writeComment(' Generated by KDAnalytiks | kmsurveytool.com ');
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
                'prodName' => $branding['showKdBranding'] ? 'KDAnalytiks' : ($branding['customOrgName'] ?? ''),
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
        $titleSuffix = $branding['showKdBranding'] ? ' (via KDAnalytiks)' : ($branding['customOrgName'] ? ' (for ' . $branding['customOrgName'] . ')' : '');

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

        if ($branding['showKdBranding']) {
            fputcsv($handle, ['# Exported via KDAnalytiks — kmsurveytool.com']);
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
    public function getAnalyticalData(\App\Models\Survey $survey, $responses, $includeAi = false, $forceGenerate = false)
    {
        $isJson = !empty($survey->json_schema) && $survey->json_schema !== '[]';
        $totalResponses = $responses->count();
        $analysis = [];
        $chartConfigs = [];
        $user = auth()->user();
        $canAnalyze = $user && $user->canUseAiAnalysis();

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
                        $data = is_string($jsonAnswer->value) ? json_decode($jsonAnswer->value, true) : $jsonAnswer->value;
                        if (is_array($data)) {
                            foreach ($data as $entry) {
                                if (isset($entry['name']) && $entry['name'] === $fieldId && isset($entry['userData'])) {
                                    $val = $entry['userData'];
                                    if ($val !== null && $val !== '') {
                                        $answeredCount++;
                                        $answersList[] = is_array($val) ? implode(', ', $val) : $val;
                                        $found = true;
                                    }
                                }
                            }
                        }
                    }
                    if (!$found) {
                        $answersList[] = null;
                    }
                }

                $missingCount = $totalResponses - $answeredCount;
                $isChartable = in_array($field['type'], ['radio', 'checkbox', 'select', 'number', 'select-one', 'select-multiple', 'radio-group', 'checkbox-group', 'rating', 'range', 'ranking', 'decimal', 'starRating', 'toggle']);
                $isAnalyzable = $field['type'] === 'textarea';
                $canvasId = 'chart-' . $fieldId;

                $stats = [];
                if ($isChartable) {
                    foreach ($answersList as $ans) {
                        if ($ans !== null && $ans !== '') {
                            $frequencyCount[$ans] = ($frequencyCount[$ans] ?? 0) + 1;
                        }
                    }
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
                }

                $chartUrl = null;
                if ($isChartable && !empty($frequencyCount)) {
                    $chartConfigs[] = [
                        'canvas_id' => $canvasId,
                        'labels' => array_keys($frequencyCount),
                        'data' => array_values($frequencyCount)
                    ];

                    $qcConfig = [
                        'type' => 'bar',
                        'data' => [
                            'labels' => array_keys($frequencyCount),
                            'datasets' => [['data' => array_values($frequencyCount), 'backgroundColor' => '#4f46e5']]
                        ],
                        'options' => ['plugins' => ['legend' => ['display' => false]]]
                    ];
                    $chartUrl = 'https://quickchart.io/chart?c=' . urlencode(json_encode($qcConfig)) . '&w=600&h=300';
                }

                $aiInsight = null;
                if ($includeAi) {
                    try {
                        if ($isAnalyzable) {
                            // Qualitative Analysis - FREE (Standard)
                            if ($forceGenerate) {
                                $aiInsight = \Illuminate\Support\Facades\Cache::remember("qualitative_analysis_{$survey->id}_{$fieldId}", 86400, function () use ($answersList) {
                                    return (new \App\Services\QualitativeAnalysisService())->analyzeResponses($answersList);
                                });
                            }
                        } elseif ($isChartable && $canAnalyze) {
                            // Quantitative AI Trend Interpretation - PREMIUM
                            if ($forceGenerate) {
                                $aiInsight = \Illuminate\Support\Facades\Cache::remember("quantitative_analysis_{$survey->id}_{$fieldId}", 86400, function () use ($stats) {
                                    return (new \App\Services\QualitativeAnalysisService())->analyzeQuantitativeData($stats);
                                });
                            }
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("AI Insight Generation Failed: " . $e->getMessage());
                    }
                }

                $analysis[] = [
                    'id' => $fieldId,
                    'survey_id' => $survey->id,
                    'label' => $label,
                    'type' => $type,
                    'isChartable' => $isChartable,
                    'isAnalyzable' => $isAnalyzable,
                    'canvasId' => $canvasId,
                    'answers' => $answersList,
                    'stats' => $stats,
                    'answered_count' => $answeredCount,
                    'missing_count' => $missingCount,
                    'chartUrl' => $chartUrl,
                    'aiInsight' => $aiInsight
                ];
            }
        } else {
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
                $isChartable = in_array($question->type, ['radio', 'checkbox', 'select', 'number', 'select_one', 'select_many', 'select-one', 'select-multiple', 'radio-group', 'checkbox-group', 'rating', 'range', 'ranking', 'decimal', 'starRating', 'toggle']);
                $isAnalyzable = $question->type === 'textarea';
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

                $chartUrl = null;
                if ($isChartable && !empty($frequencyCount)) {
                    $chartConfigs[] = [
                        'canvas_id' => $canvasId,
                        'labels' => array_keys($frequencyCount),
                        'data' => array_values($frequencyCount)
                    ];

                    $qcConfig = [
                        'type' => 'bar',
                        'data' => [
                            'labels' => array_keys($frequencyCount),
                            'datasets' => [['data' => array_values($frequencyCount), 'backgroundColor' => '#4f46e5']]
                        ],
                        'options' => ['plugins' => ['legend' => ['display' => false]]]
                    ];
                    $chartUrl = 'https://quickchart.io/chart?c=' . urlencode(json_encode($qcConfig)) . '&w=600&h=300';
                }

                $aiInsight = null;
                if ($includeAi) {
                    try {
                        if ($isAnalyzable) {
                            // Qualitative Analysis - FREE (Standard)
                            if ($forceGenerate) {
                                $aiInsight = \Illuminate\Support\Facades\Cache::remember("qualitative_analysis_{$survey->id}_{$question->id}", 86400, function () use ($answersList) {
                                    return (new \App\Services\QualitativeAnalysisService())->analyzeResponses($answersList);
                                });
                            }
                        } elseif ($isChartable && $canAnalyze) {
                            // Quantitative AI Trend Interpretation - PREMIUM
                            if ($forceGenerate) {
                                $aiInsight = \Illuminate\Support\Facades\Cache::remember("quantitative_analysis_{$survey->id}_{$question->id}", 86400, function () use ($stats) {
                                    return (new \App\Services\QualitativeAnalysisService())->analyzeQuantitativeData($stats);
                                });
                            }
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("AI Insight Generation Failed: " . $e->getMessage());
                    }
                }

                $analysis[] = [
                    'id' => $question->id,
                    'survey_id' => $survey->id,
                    'label' => $question->text,
                    'type' => $question->type,
                    'isChartable' => $isChartable,
                    'isAnalyzable' => $isAnalyzable,
                    'canvasId' => $canvasId,
                    'answers' => $answersList,
                    'stats' => $stats,
                    'answered_count' => $answeredCount,
                    'missing_count' => $missingCount,
                    'chartUrl' => $chartUrl,
                    'aiInsight' => $aiInsight
                ];
            }
        }

        return ['analysis' => $analysis, 'chartConfigs' => $chartConfigs];
    }

    public function report(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $responses = $survey->responses()->with('answers.question')->get();
        $analyticalData = $this->getAnalyticalData($survey, $responses);
        $analysis = $analyticalData['analysis'];
        $chartConfigs = $analyticalData['chartConfigs'];

        // AI Access Control
        $user = auth()->user();
        $canAnalyze = $user->canUseAiAnalysis();
        $aiSummary = null;

        if ($canAnalyze) {
            try {
                $aiSummary = \Illuminate\Support\Facades\Cache::remember("survey_{$survey->id}_ai_summary", 86400, function () use ($survey, $user) {
                    $summary = (new \App\Services\AiService())->generateSurveySummary($survey);
                    // Record usage only if not Pro (Trial mode)
                    if (!$user->hasProAccess()) {
                        $user->recordAiUsage();
                    }
                    return $summary;
                });
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("AI Summary Error: " . $e->getMessage());
                $aiSummary = "AI analysis is currently unavailable. Please try again later.";
            }
        } else {
            $aiSummary = "TRIAL LIMIT REACHED: Upgrade to Pro or Enterprise to unlock continuous AI Executive Summaries and Strategic Synthesis.";
        }

        return view('surveys.reports', compact('survey', 'responses', 'analysis', 'chartConfigs', 'aiSummary', 'canAnalyze'));
    }

    public function exportPdf(\App\Models\Survey $survey)
    {
        set_time_limit(300);
        $this->authorizeOwner($survey);

        $responses = $survey->responses()->with('answers.question')->get();
        $analyticalData = $this->getAnalyticalData($survey, $responses, true, true);
        $analysis = $analyticalData['analysis'];

        // Convert Chart URLs to Base64 for PDF reliability
        foreach ($analysis as &$item) {
            if (!empty($item['chartUrl'])) {
                try {
                    $context = stream_context_create([
                        "ssl" => [
                            "verify_peer" => false,
                            "verify_peer_name" => false,
                        ],
                    ]);
                    $imgData = file_get_contents($item['chartUrl'], false, $context);
                    if ($imgData) {
                        $item['chartBase64'] = 'data:image/png;base64,' . base64_encode($imgData);
                    }
                } catch (\Exception $e) {
                    \Log::error("Failed to fetch chart for PDF: " . $e->getMessage());
                }
            }
        }
        unset($item);

        $aiSummary = "";
        try {
            $aiSummary = (new \App\Services\AiService())->generateSurveySummary($survey);
        } catch (\Exception $e) {
        }

        $branding = $this->getBrandingContext($survey);
        $isPremium = auth()->user()->hasActiveSubscription();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf', compact('survey', 'responses', 'analysis', 'branding', 'aiSummary', 'isPremium'));
        $filename = "Analytical_Report_" . Str::slug($survey->title) . "_" . date('Ymd_His') . ".pdf";

        // Save for History
        $exportDir = storage_path('app/public/exports/' . $survey->id . '/');
        if (!is_dir($exportDir))
            mkdir($exportDir, 0755, true);
        $output = $pdf->output();
        file_put_contents($exportDir . $filename, $output);

        return $pdf->download($filename);
    }


    public function exportDocx(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $branding = $this->getBrandingContext($survey);
        $brandHex = ltrim($branding['brandColor'] ?? '4f46e5', '#');

        // Define Styles
        $phpWord->addTitleStyle(1, ['size' => 26, 'bold' => true, 'color' => '1e1b4b', 'name' => 'Arial'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 240]);
        $phpWord->addTitleStyle(2, ['size' => 18, 'bold' => true, 'color' => $brandHex, 'name' => 'Arial'], ['spaceBefore' => 240, 'spaceAfter' => 120, 'borderBottomSize' => 6, 'borderBottomColor' => 'e5e7eb']);
        $phpWord->addTitleStyle(3, ['size' => 14, 'bold' => true, 'color' => '111827', 'name' => 'Arial'], ['spaceBefore' => 120, 'spaceAfter' => 60]);

        $phpWord->addFontStyle('Normal', ['size' => 10, 'name' => 'Arial', 'color' => '374151']);
        $phpWord->addFontStyle('Italic', ['size' => 10, 'name' => 'Arial', 'color' => '6b7280', 'italic' => true]);
        $phpWord->addFontStyle('AiHeading', ['size' => 10, 'bold' => true, 'color' => '15803d', 'name' => 'Arial']);
        $phpWord->addFontStyle('AiText', ['size' => 10, 'color' => '374151', 'name' => 'Arial']);
        $phpWord->addFontStyle('Quote', ['size' => 10, 'italic' => true, 'color' => '4b5563', 'name' => 'Arial']);

        $phpWord->addTableStyle('StatsTable', [
            'borderSize' => 6,
            'borderColor' => 'e5e7eb',
            'cellMargin' => 80
        ], [
            'bgColor' => 'f9fafb'
        ]);

        $section = $phpWord->addSection([
            'marginTop' => 1200,
            'marginBottom' => 1200,
            'marginLeft' => 1200,
            'marginRight' => 1200
        ]);

        // Cover Page
        $section->addTextBreak(4);
        $section->addTitle($survey->title, 1);
        $section->addText("Analytical Executive Report", ['size' => 14, 'color' => '6366f1', 'bold' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $section->addTextBreak(1);
        $section->addText("Date Generated: " . now()->format('F d, Y'), ['italic' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $section->addTextBreak(2);

        $responses = $survey->responses()->with('answers.question')->get();
        $analyticalData = $this->getAnalyticalData($survey, $responses, true, true);
        $analysis = $analyticalData['analysis'];

        $section->addText("This document provides a comprehensive statistical and qualitative interpretation of gathered data, utilizing AI-driven thematic mapping and sentiment analysis to reveal core respondent trends.", 'Italic', ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $section->addPageBreak();

        $aiSummary = "No AI summary available.";
        try {
            $aiSummary = (new \App\Services\AiService())->generateSurveySummary($survey);
        } catch (\Exception $e) {
        }

        // Summary Stats (Chapter 4 starts here)
        $section->addTitle('Chapter 4: Executive Thematic Analysis', 2);
        $section->addText($aiSummary);
        $section->addTextBreak(2);

        // Detailed Findings
        foreach ($analysis as $index => $item) {
            $section->addHeading("Q" . ($index + 1) . ": " . $item['label'], 3);

            if ($item['isChartable']) {
                $table = $section->addTable('StatsTable');
                $table->addRow(400, ['bgColor' => '4338ca']);
                $table->addCell(4000)->addText("Choice", ['bold' => true, 'color' => 'ffffff']);
                $table->addCell(2000)->addText("Count", ['bold' => true, 'color' => 'ffffff']);
                $table->addCell(2000)->addText("Ratio", ['bold' => true, 'color' => 'ffffff']);

                foreach ($item['stats'] as $stat) {
                    if ($stat['is_missing'] ?? false)
                        continue;
                    $table->addRow();
                    $table->addCell(4000)->addText($stat['value'], 'Normal');
                    $table->addCell(2000)->addText($stat['count'], 'Normal');
                    $table->addCell(2000)->addText($stat['percentage'] . '%', 'Normal');
                }

                $table->addRow(300, ['bgColor' => 'f3f4f6']);
                $table->addCell(4000)->addText("TOTAL", ['bold' => true]);
                $table->addCell(2000)->addText($item['answered_count'], ['bold' => true]);
                $table->addCell(2000)->addText("100%", ['bold' => true]);
                if (!empty($item['chartUrl'])) {
                    $section->addTextBreak(1);
                    try {
                        $context = stream_context_create([
                            "ssl" => [
                                "verify_peer" => false,
                                "verify_peer_name" => false,
                            ],
                        ]);
                        $img = file_get_contents($item['chartUrl'], false, $context);
                        if ($img) {
                            $tempFile = tempnam(sys_get_temp_dir(), 'chart_') . '.png';
                            file_put_contents($tempFile, $img);
                            $section->addImage($tempFile, ['width' => 350, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                        }
                    } catch (\Exception $e) {
                    }
                }

                if (!empty($item['aiInsight']) && is_string($item['aiInsight'])) {
                    $section->addTextBreak(1);
                    $aiTable = $section->addTable(['borderColor' => 'bbf7d0', 'borderSize' => 6, 'cellMargin' => 120]);
                    $aiTable->addRow();
                    $aiCell = $aiTable->addCell(8000, ['bgColor' => 'f0fdf4']);
                    $aiCell->addText("AI STATISTICAL INTERPRETATION", 'AiHeading');
                    $aiCell->addText($item['aiInsight'], 'AiText');
                }
            } else {
                if (!empty($item['aiInsight']) && is_array($item['aiInsight'])) {
                    $aiTable = $section->addTable(['borderColor' => 'bbf7d0', 'borderSize' => 6, 'cellMargin' => 120]);
                    $aiTable->addRow();
                    $aiCell = $aiTable->addCell(8000, ['bgColor' => 'f0fdf4']);

                    $aiCell->addText("AI QUALITATIVE INSIGHTS", 'AiHeading');
                    $aiCell->addText("Sentiment Distribution:", ['bold' => true, 'size' => 9]);
                    $aiCell->addText("Positive: " . $item['aiInsight']['sentiment_breakdown']['Positive'] . "% | Neutral: " . $item['aiInsight']['sentiment_breakdown']['Neutral'] . "% | Negative: " . $item['aiInsight']['sentiment_breakdown']['Negative'] . "%", 'AiText');

                    $aiCell->addTextBreak(1);
                    $aiCell->addText("Key Thematic Mapping:", ['bold' => true, 'size' => 9]);
                    foreach ($item['aiInsight']['key_themes'] as $theme) {
                        $aiCell->addListItem($theme['theme'] . ": " . $theme['explanation'], 0, 'AiText');
                    }

                    $aiCell->addTextBreak(1);
                    $aiCell->addText("Representative Voter Quotes:", ['bold' => true, 'size' => 9]);
                    foreach ($item['aiInsight']['representative_quotes'] as $quote) {
                        $aiCell->addText('"' . $quote . '"', 'Quote');
                    }
                }
                foreach (array_slice((array) $item['answers'], 0, 15) as $answer) {
                    $val = is_array($answer) ? json_encode($answer) : (string) $answer;

                    if (str_contains($val, 'base64,')) {
                        try {
                            $section->addText("Captured Signature:", ['size' => 8, 'color' => '666666']);
                            $imageData = explode('base64,', $val)[1];
                            $section->addMemoryImage(base64_decode($imageData), ['height' => 40]);
                        } catch (\Exception $e) {
                            $section->addText("[Signature Rendering Error]");
                        }
                    } elseif (str_starts_with($val, 'uploads/') && in_array(strtolower(pathinfo($val, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        try {
                            $section->addText("Uploaded Image:", ['size' => 8, 'color' => '666666']);
                            $section->addImage(public_path('storage/' . $val), ['height' => 80]);
                        } catch (\Exception $e) {
                            $section->addText("[Image File Missing: $val]");
                        }
                    } else {
                        $section->addListItem($val);
                    }
                }
            }
            $section->addTextBreak(1);
        }

        // Raw Data Appendix (Premium Only)
        if (auth()->user()->hasActiveSubscription()) {
            $section = $phpWord->addSection(['breakType' => 'nextPage']);
            $section->addTitle("Appendix: Raw Data Dump", 2);
            $section->addText("Complete record of all respondent submissions.", 'Italic');
            $section->addTextBreak(1);

            foreach ($responses as $resp) {
                $section->addText("RESPONSE ID: #{$resp->id} | SUBMITTED: " . $resp->created_at->format('M d, Y H:i'), ['bold' => true, 'size' => 9, 'color' => '666666']);

                $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'f3f4f6', 'cellMargin' => 40]);
                foreach ($analysis as $item) {
                    $ans = null;
                    if (!empty($survey->json_schema) && $survey->json_schema !== '[]') {
                        $data = json_decode($resp->answers->first()->value ?? '[]', true);
                        foreach ((array) $data as $entry) {
                            if (isset($entry['name']) && $entry['name'] === $item['id']) {
                                $ans = $entry['userData'] ?? null;
                                break;
                            }
                        }
                    } else {
                        $ans = $resp->answers->where('question_id', $item['id'])->first()?->value;
                    }

                    $table->addRow();
                    $table->addCell(3000, ['bgColor' => 'f9fafb'])->addText($item['label'], ['bold' => true, 'size' => 8]);

                    $valStr = is_array($ans) ? implode(', ', $ans) : (string) $ans;
                    if (str_contains($valStr, 'base64,'))
                        $valStr = "[Signature Captured]";
                    elseif (str_starts_with($valStr, 'uploads/'))
                        $valStr = "[Media: " . basename($valStr) . "]";

                    $table->addCell(7000)->addText($valStr ?: '—', ['size' => 8]);
                }
                $section->addTextBreak(1);
            }
        }

        // Disclaimer
        $section->addTextBreak(2);
        $section->addText("Data Integrity & Validation Disclaimer", ['bold' => true, 'color' => '744210']);
        $section->addText("This report has been automatically generated by KDAnalytiks. The statistics and AI insights provided are based on raw data collected from survey respondents. PRC™ Consulting does not guarantee the absolute accuracy of AI interpretations. This report should be used as a strategic guide.", ['size' => 9, 'color' => '744210']);

        $filename = "Analytical_Report_" . Str::slug($survey->title) . "_" . date('Ymd_His') . ".docx";
        $tempFile = tempnam(sys_get_temp_dir(), 'docx');
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    public function exportSinglePdf(\App\Models\Survey $survey, \App\Models\Response $response)
    {
        $this->authorizeOwner($survey);

        $branding = $this->getBrandingContext($survey);

        // Structure data for the single response view
        $isJson = !empty($survey->json_schema) && $survey->json_schema !== '[]';
        $answers = [];

        if ($isJson) {
            $schema = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : $survey->json_schema;
            $parsedData = json_decode($response->answers->first()->value ?? '[]', true) ?? [];
            foreach ($schema as $field) {
                if (!isset($field['name']) || in_array($field['type'], ['header', 'paragraph']))
                    continue;
                $val = '—';
                foreach ($parsedData as $data) {
                    if (isset($data['name']) && $data['name'] === $field['name']) {
                        $val = $data['userData'] ?? '—';
                        break;
                    }
                }
                $answers[] = [
                    'label' => $field['label'] ?? $field['name'],
                    'value' => $val
                ];
            }
        } else {
            foreach ($survey->questions()->orderBy('position')->get() as $q) {
                $a = $response->answers->where('question_id', $q->id)->first();
                $answers[] = [
                    'label' => $q->text,
                    'value' => $a ? $a->value : '—'
                ];
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.single_pdf', compact('survey', 'response', 'answers', 'branding'));
        $filename = "Response_" . $response->id . "_" . Str::slug($survey->title) . ".pdf";

        return $pdf->download($filename);
    }

    public function exportSingleDocx(\App\Models\Survey $survey, \App\Models\Response $response)
    {
        $this->authorizeOwner($survey);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addTitle("Individual Response Detail", 1);
        $section->addText("Survey: " . $survey->title, ['bold' => true]);
        $section->addText("Response ID: " . $response->id);
        $section->addText("Submitted: " . $response->created_at->format('M d, Y H:i'));
        $section->addText("Respondent: " . ($response->respondent->name ?? 'Anonymous') . " (" . ($response->respondent->email ?? 'N/A') . ")");
        $section->addTextBreak(2);

        // Answers logic
        $isJson = !empty($survey->json_schema) && $survey->json_schema !== '[]';
        if ($isJson) {
            $schema = is_string($survey->json_schema) ? json_decode($survey->json_schema, true) : $survey->json_schema;
            $parsedData = json_decode($response->answers->first()->value ?? '[]', true) ?? [];
            foreach ($schema as $field) {
                if (!isset($field['name']) || in_array($field['type'], ['header', 'paragraph']))
                    continue;
                $val = '—';
                foreach ($parsedData as $data) {
                    if (isset($data['name']) && $data['name'] === $field['name']) {
                        $val = $data['userData'] ?? '—';
                        break;
                    }
                }

                $section->addText($field['label'] ?? $field['name'], ['bold' => true, 'size' => 10]);
                $this->addDocxValue($section, $val);
                $section->addTextBreak(1);
            }
        } else {
            foreach ($survey->questions()->orderBy('position')->get() as $q) {
                $a = $response->answers->where('question_id', $q->id)->first();
                $val = $a ? $a->value : '—';
                $section->addText($q->text, ['bold' => true, 'size' => 10]);
                $this->addDocxValue($section, $val);
                $section->addTextBreak(1);
            }
        }

        $filename = "Response_" . $response->id . "_" . Str::slug($survey->title) . ".docx";
        $tempFile = tempnam(sys_get_temp_dir(), 'docx');
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    protected function addDocxValue($section, $val)
    {
        if (is_array($val)) {
            foreach ($val as $v)
                $this->addDocxValue($section, $v);
            return;
        }

        $valStr = (string) $val;
        if (str_contains($valStr, 'base64,')) {
            try {
                $imageData = explode('base64,', $valStr)[1];
                $section->addMemoryImage(base64_decode($imageData), ['height' => 50]);
            } catch (\Exception $e) {
                $section->addText("[Signature Error]");
            }
        } elseif (str_starts_with($valStr, 'uploads/') && in_array(strtolower(pathinfo($valStr, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            try {
                $section->addImage(public_path('storage/' . $valStr), ['height' => 100]);
            } catch (\Exception $e) {
                $section->addText("[Image Missing]");
            }
        } else {
            $section->addText($valStr ?: '—');
        }
    }
    public function publish(Request $request, \App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $user = auth()->user();
        $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;
        $entity = ($role === 'organization') ? $user->organization : (($role === 'independent') ? $user->independent : null);
        if ($survey->status !== \App\Enums\SurveyStatus::Active && $entity?->hasReachedSurveyLimit()) {
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
        $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;
        $entity = ($role === 'organization') ? $user->organization : (($role === 'independent') ? $user->independent : null);
        if ($entity) {
            $tier = $entity->subscriptionTier ?? \App\Models\SubscriptionTier::where('slug', 'free')->first();
            if ($tier->max_surveys !== -1 && $entity->surveys()->count() >= $tier->max_surveys) {
                $limitReached = true;
            }
        }

        return view('surveys.builder', compact('survey', 'limitReached'));
    }

    public function sharedReport($token)
    {
        $survey = \App\Models\Survey::where('share_report_token', $token)->firstOrFail();

        $responses = $survey->responses()->with('answers.question')->get();
        $analyticalData = $this->getAnalyticalData($survey, $responses, true);

        $analysis = $analyticalData['analysis'];
        $chartConfigs = $analyticalData['chartConfigs'];
        $totalResponses = $responses->count();
        $isSharedView = true;
        $canAnalyze = false;
        $aiSummary = null;

        return view('surveys.reports', compact('survey', 'analysis', 'chartConfigs', 'totalResponses', 'isSharedView', 'canAnalyze', 'aiSummary'));
    }

    public function toggleSharedReport(\App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        if (request()->has('disable')) {
            $survey->update(['share_report_token' => null]);
            return back()->with('success', 'Shared report access has been disabled.');
        }

        $token = Str::random(32);
        $survey->update(['share_report_token' => $token]);

        return back()->with('success', 'Live Result Dashboard is now active!');
    }

    public function crosstab(Request $request, \App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $rowId = $request->row;
        $colId = $request->col;

        $responses = $survey->responses()->with('answers')->get();
        $isJson = !empty($survey->json_schema) && $survey->json_schema !== '[]';

        $matrix = [];
        $rows = [];
        $cols = [];
        $rowTotals = [];
        $colTotals = [];
        $grandTotal = 0;

        // Get Labels
        $rowLabel = "Variable A";
        $colLabel = "Variable B";

        if ($isJson) {
            $schema = json_decode($survey->json_schema, true);
            foreach ($schema as $f) {
                if (isset($f['name'])) {
                    if ($f['name'] === $rowId)
                        $rowLabel = $f['label'] ?? $rowId;
                    if ($f['name'] === $colId)
                        $colLabel = $f['label'] ?? $colId;
                }
            }
        } else {
            $rowLabel = \App\Models\Question::find($rowId)?->text ?? $rowId;
            $colLabel = \App\Models\Question::find($colId)?->text ?? $colId;
        }

        foreach ($responses as $resp) {
            $rowVal = $this->getAnswerValue($resp, $rowId, $isJson);
            $colVal = $this->getAnswerValue($resp, $colId, $isJson);

            if ($rowVal === null)
                $rowVal = "[Missing]";
            if ($colVal === null)
                $colVal = "[Missing]";

            if (!in_array($rowVal, $rows))
                $rows[] = $rowVal;
            if (!in_array($colVal, $cols))
                $cols[] = $colVal;

            if (!isset($matrix[$rowVal][$colVal]))
                $matrix[$rowVal][$colVal] = 0;
            $matrix[$rowVal][$colVal]++;

            $rowTotals[$rowVal] = ($rowTotals[$rowVal] ?? 0) + 1;
            $colTotals[$colVal] = ($colTotals[$colVal] ?? 0) + 1;
            $grandTotal++;
        }

        // Fill gaps in matrix
        foreach ($rows as $r) {
            foreach ($cols as $c) {
                if (!isset($matrix[$r][$c]))
                    $matrix[$r][$c] = 0;
            }
        }

        return response()->json([
            'rowLabel' => $rowLabel,
            'colLabel' => $colLabel,
            'results' => [
                'matrix' => $matrix,
                'rows' => $rows,
                'cols' => $cols,
                'rowTotals' => $rowTotals,
                'colTotals' => $colTotals,
                'grandTotal' => $grandTotal
            ]
        ]);
    }

    private function getAnswerValue($response, $questionId, $isJson)
    {
        if ($isJson) {
            $firstAnswer = $response->answers->first();
            $data = json_decode($firstAnswer ? $firstAnswer->value : '[]', true);
            foreach ((array) $data as $entry) {
                if (isset($entry['name']) && $entry['name'] === $questionId) {
                    $val = $entry['userData'] ?? null;
                    return is_array($val) ? implode(', ', $val) : $val;
                }
            }
            return null;
        } else {
            $a = $response->answers->where('question_id', $questionId)->first();
            return $a ? $a->value : null;
        }
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

        app(\App\Services\SurveyVersioningService::class)->createVersionIfChanged(
            $survey,
            [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'json_schema' => $validated['json_schema'],
            ],
            auth()->id()
        );

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

        if (request()->expectsJson() || request()->isXmlHttpRequest() || request()->header('Accept') == 'application/json') {
            return response()->json([
                'success' => true,
                'message' => 'Survey deleted successfully.'
            ]);
        }

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
        $query = \App\Models\Survey::where('is_template', false)
            ->where('status', \App\Enums\SurveyStatus::Active)
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
        $inviteToken = request('invite_token');

        $isOwner = $user && ($survey->created_by == $user->id);
        $isAdmin = $user && $user->isAdmin();
        $isCollaborator = $user && $survey->collaborators()->where('user_id', $user->id)->exists();

        // Handle Sharing Token Access
        $hasToken = $token && $survey->share_token === $token;

        // Handle Invite Token Access
        $hasInvite = false;
        if ($inviteToken) {
            $recipient = \App\Models\SurveyInviteRecipient::where('token', $inviteToken)
                ->whereHas('campaign', function ($query) use ($survey) {
                    $query->where('survey_id', $survey->id);
                })->first();

            if ($recipient) {
                $hasInvite = true;
                if ($recipient->status === 'sent') {
                    $recipient->update([
                        'status' => 'opened',
                        'opened_at' => now(),
                    ]);
                    $recipient->campaign->increment('total_opened');
                }
            }
        }

        // A survey is viewable if it is public, has explicit view permissions, or has a valid token or invite
        $publicCanView = ($survey->type === \App\Enums\SurveyType::Public) || ($survey->public_access !== 'none') || $hasToken || $hasInvite;

        $isActive = ($survey->status === \App\Enums\SurveyStatus::Active) || $survey->is_template;

        // If not active and not template, only certain people can see
        if (!$isActive && !$isOwner && !$isAdmin && !$isCollaborator && !$hasToken && !$hasInvite) {
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

            // Track invite token if present
            $inviteToken = $request->input('invite_token') ?: $request->query('invite_token');
            if ($inviteToken) {
                $recipient = \App\Models\SurveyInviteRecipient::where('token', $inviteToken)->first();
                if ($recipient && $recipient->status !== 'responded') {
                    $recipient->update([
                        'status' => 'responded',
                        'responded_at' => now(),
                    ]);
                    $recipient->campaign->increment('total_responded');
                }
            }

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
            } else {
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
            }

            // Run Response Quality & Fraud Analysis
            app(\App\Services\ResponseQualityService::class)->analyze($response, $request);

            // --- Reward Logic Start (Gated by Quality Score) ---
            $rewardMessage = '';
            $earnedAmount = 0;
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

                        // 2. Get or Create Wallet
                        $wallet = $user->wallet ?: \App\Models\Wallet::create(['user_id' => $user->id, 'balance' => 0]);

                        if ($response->is_flagged) {
                            // Withhold reward: Create a pending transaction
                            \App\Models\Transaction::create([
                                'wallet_id' => $wallet->id,
                                'amount' => (float) $surveyLocked->reward_per_response,
                                'type' => 'credit',
                                'status' => 'pending',
                                'reference' => 'REW-' . strtoupper(Str::random(10)),
                                'description' => "Reward pending quality review for Survey ID: {$surveyLocked->id}"
                            ]);
                            $rewardMessage = " Your reward is pending quality review.";
                        } else {
                            // Credit Wallet Balance
                            $wallet->increment('balance', (float) $surveyLocked->reward_per_response);

                            // Create completed transaction
                            \App\Models\Transaction::create([
                                'wallet_id' => $wallet->id,
                                'amount' => (float) $surveyLocked->reward_per_response,
                                'type' => 'credit',
                                'status' => 'completed',
                                'reference' => 'REW-' . strtoupper(Str::random(10)),
                                'description' => "Reward for completing Survey ID: {$surveyLocked->id}"
                            ]);

                            $earnedAmount = (float) $surveyLocked->reward_per_response;
                            $rewardMessage = " You earned " . number_format((float) $surveyLocked->reward_per_response, 2) . " " . ($wallet->currency ?? 'KES') . "!";
                        }
                    }
                }
            }
            // --- Reward Logic End ---

            \Illuminate\Support\Facades\DB::commit();

            // AI Sentiment Analysis (Background-ish)
            try {
                (new \App\Services\AiService())->analyzeResponseSentiment($response);
            } catch (\Exception $e) {
                \Log::error("AI Background Error: " . $e->getMessage());
            }

            // Send Notification Email (only if reward was completed/earned immediately)
            if (auth()->check() && $earnedAmount > 0) {
                try {
                    $user = auth()->user();
                    $role = $user->role instanceof \App\Enums\UserRole ? $user->role->value : $user->role;
                    \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\SurveyRewardNotification($survey, $earnedAmount, $user->wallet?->currency ?? 'KES', $role));
                } catch (\Exception $e) {
                    \Log::error("Email Error: " . $e->getMessage());
                }
            }

            if ($request->has('is_json_submission')) {
                session()->flash('success', 'Thank you for completing the survey!' . $rewardMessage);
                return response()->json(['success' => true]);
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

    public function transcribeMedia(Request $request, \App\Models\Survey $survey, \App\Models\Response $response)
    {
        $this->authorizeOwner($survey);

        if ($response->survey_id !== $survey->id) {
            return response()->json(['success' => false, 'message' => 'Invalid response'], 400);
        }

        // Premium Check
        $tier = $this->getCurrentTier();
        if (!in_array($tier, ['pro', 'enterprise'])) {
            return response()->json([
                'success' => false,
                'message' => 'AI Transcription is a Premium feature. Please upgrade to Pro or Enterprise to transcribe media submissions.'
            ], 403);
        }

        $filePath = $request->input('file_path');
        if (empty($filePath)) {
            return response()->json(['success' => false, 'message' => 'File path is required'], 400);
        }

        // Construct absolute path
        $absolutePath = storage_path('app/public/' . $filePath);
        if (!file_exists($absolutePath)) {
            return response()->json(['success' => false, 'message' => 'Media file not found on server'], 404);
        }

        try {
            $aiService = new \App\Services\AiService();
            $transcription = $aiService->transcribeMedia($absolutePath);

            if ($transcription) {
                // Save to Response AI Metadata
                $metadata = $response->ai_metadata ?? [];
                if (!isset($metadata['transcriptions'])) {
                    $metadata['transcriptions'] = [];
                }
                $metadata['transcriptions'][$filePath] = $transcription;
                $response->update(['ai_metadata' => $metadata]);

                return response()->json([
                    'success' => true,
                    'transcription' => $transcription
                ]);
            }

            return response()->json(['success' => false, 'message' => 'AI transcription failed. Please try again later.'], 500);
        } catch (\Exception $e) {
            \Log::error("Transcription Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error during transcription.'], 500);
        }
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

    /**
     * Export the current survey questionnaire schema as a DOCX file.
     */
    public function exportSchemaDocx(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'questions' => 'required|string',
        ]);

        $title = $validated['title'] ?? 'Survey Questionnaire';
        $description = $validated['description'] ?? '';
        $questions = json_decode($validated['questions'], true) ?: [];

        $phpWord = new \PhpOffice\PhpWord\PhpWord();

        // Define styles
        $phpWord->addTitleStyle(1, ['name' => 'Helvetica Neue', 'size' => 20, 'bold' => true, 'color' => '333333'], ['spaceAfter' => 240]);
        $phpWord->addTitleStyle(2, ['name' => 'Helvetica Neue', 'size' => 14, 'bold' => true, 'color' => '4F46E5'], ['spaceBefore' => 180, 'spaceAfter' => 120]);

        $section = $phpWord->addSection([
            'marginTop' => 1440,
            'marginRight' => 1440,
            'marginBottom' => 1440,
            'marginLeft' => 1440,
        ]);

        // Header/Title
        $section->addTitle($title, 1);
        if ($description) {
            $section->addText($description, ['name' => 'Helvetica Neue', 'size' => 10, 'italic' => true, 'color' => '666666'], ['spaceAfter' => 360]);
        }

        foreach ($questions as $index => $q) {
            $num = $index + 1;
            $qLabel = $q['label'] ?? '';
            $qType = $q['type'] ?? 'text';
            $required = !empty($q['required']) ? ' *' : '';

            if ($qType === 'header') {
                $section->addTitle($qLabel, 2);
                continue;
            } elseif ($qType === 'paragraph') {
                $section->addText($qLabel, ['name' => 'Helvetica Neue', 'size' => 10, 'color' => '555555'], ['spaceAfter' => 180]);
                continue;
            }

            $section->addText("Q{$num}. {$qLabel}{$required}", ['name' => 'Helvetica Neue', 'size' => 11, 'bold' => true, 'color' => '111111'], ['spaceBefore' => 120, 'spaceAfter' => 120]);

            if (in_array($qType, ['select_one', 'select_many', 'select', 'ranking'])) {
                $values = $q['values'] ?? [];
                foreach ($values as $v) {
                    $optLabel = is_array($v) ? ($v['label'] ?? '') : $v;
                    $section->addText("  [ ]  {$optLabel}", ['name' => 'Helvetica Neue', 'size' => 10, 'color' => '333333'], ['spaceAfter' => 60]);
                }
            } elseif ($qType === 'likert_matrix') {
                $rows = $q['rows'] ?? [];
                $columns = $q['columns'] ?? [];
                if (count($rows) > 0 && count($columns) > 0) {
                    $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC', 'cellMargin' => 80]);
                    // Header Row
                    $table->addRow();
                    $table->addCell(3000)->addText('');
                    foreach ($columns as $col) {
                        $colLabel = is_array($col) ? ($col['label'] ?? '') : $col;
                        $table->addCell(1500)->addText($colLabel, ['name' => 'Helvetica Neue', 'size' => 9, 'bold' => true, 'color' => '4F46E5']);
                    }
                    // Rows
                    foreach ($rows as $row) {
                        $rowLabel = is_array($row) ? ($row['label'] ?? '') : $row;
                        $table->addRow();
                        $table->addCell(3000)->addText($rowLabel, ['name' => 'Helvetica Neue', 'size' => 9, 'color' => '111111']);
                        foreach ($columns as $col) {
                            $table->addCell(1500)->addText('[ ]', ['name' => 'Helvetica Neue', 'size' => 9, 'color' => 'CCCCCC'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                        }
                    }
                }
            } else {
                $section->addText('__________________________________________________________________', ['color' => 'DDDDDD'], ['spaceAfter' => 180]);
            }
            $section->addTextBreak(1);
        }

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $tempFile = tempnam(sys_get_temp_dir(), 'survey_export_');
        $objWriter->save($tempFile);

        $filename = str($title)->slug() . '-questionnaire.docx';

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Import questions from a DOCX file using AI.
     */
    public function importDocx(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:docx|max:10240',
        ]);

        $file = $request->file('file');

        try {
            // Store temporarily
            $path = $file->store('temp_imports', 'local');

            $extractionService = new \App\Services\DocumentExtractionService();
            $text = $extractionService->extractText($file, $path);

            // Clean up temp file
            \Storage::disk('local')->delete($path);

            $aiService = new \App\Services\AiService();
            $schema = $aiService->convertTextToSurveySchema($text);

            if (!$schema) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI was unable to extract questions from this document. Please verify the content.'
                ], 422);
            }

            return response()->json([
                'success' => true,
                'schema' => $schema
            ]);

        } catch (\Throwable $e) {
            \Log::error('DOCX Import Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to parse file: ' . $e->getMessage()
            ], 500);
        }
    }

    public function inferentialAnalysis(Request $request, \App\Models\Survey $survey)
    {
        $this->authorizeOwner($survey);

        $method = $request->query('method');
        $isJson = !empty($survey->json_schema) && $survey->json_schema !== '[]';
        $responses = $survey->responses()->with('answers')->get();

        if ($responses->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No responses available for analysis.'
            ], 400);
        }

        try {
            switch ($method) {
                case 'crosstab':
                    return $this->handleChiSquareAndCrosstab($request, $survey, $responses, $isJson);
                case 'ttest':
                    return $this->handleTTest($request, $survey, $responses, $isJson);
                case 'correlation':
                    return $this->handleCorrelation($request, $survey, $responses, $isJson);
                case 'anova':
                    return $this->handleAnova($request, $survey, $responses, $isJson);
                case 'regression':
                    return $this->handleRegression($request, $survey, $responses, $isJson);
                case 'regression_multiple':
                    return $this->handleMultipleRegression($request, $survey, $responses, $isJson);
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid analysis method.'
                    ], 400);
            }
        } catch (\Throwable $e) {
            \Log::error('Inferential Analysis Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during calculations: ' . $e->getMessage()
            ], 500);
        }
    }

    private function handleChiSquareAndCrosstab($request, $survey, $responses, $isJson)
    {
        $rowId = $request->query('row');
        $colId = $request->query('col');

        if (!$rowId || !$colId) {
            return response()->json(['success' => false, 'message' => 'Row and Column variables are required.'], 400);
        }

        $rowLabel = "Variable A";
        $colLabel = "Variable B";

        if ($isJson) {
            $schema = json_decode($survey->json_schema, true);
            foreach ($schema as $f) {
                if (isset($f['name'])) {
                    if ($f['name'] === $rowId)
                        $rowLabel = $f['label'] ?? $rowId;
                    if ($f['name'] === $colId)
                        $colLabel = $f['label'] ?? $colId;
                }
            }
        } else {
            $rowLabel = \App\Models\Question::find($rowId)?->text ?? $rowId;
            $colLabel = \App\Models\Question::find($colId)?->text ?? $colId;
        }

        $matrix = [];
        $rows = [];
        $cols = [];
        $rowTotals = [];
        $colTotals = [];
        $grandTotal = 0;

        foreach ($responses as $resp) {
            $rowVal = $this->getAnswerValue($resp, $rowId, $isJson);
            $colVal = $this->getAnswerValue($resp, $colId, $isJson);

            if ($rowVal === null)
                $rowVal = "[Missing]";
            if ($colVal === null)
                $colVal = "[Missing]";

            $rowVal = (string) $rowVal;
            $colVal = (string) $colVal;

            if (!in_array($rowVal, $rows))
                $rows[] = $rowVal;
            if (!in_array($colVal, $cols))
                $cols[] = $colVal;

            if (!isset($matrix[$rowVal][$colVal])) {
                $matrix[$rowVal][$colVal] = 0;
            }
            $matrix[$rowVal][$colVal]++;

            $rowTotals[$rowVal] = ($rowTotals[$rowVal] ?? 0) + 1;
            $colTotals[$colVal] = ($colTotals[$colVal] ?? 0) + 1;
            $grandTotal++;
        }

        // Fill gaps in matrix
        foreach ($rows as $r) {
            foreach ($cols as $c) {
                if (!isset($matrix[$r][$c])) {
                    $matrix[$r][$c] = 0;
                }
            }
        }

        // Chi-Square calculation
        $chiSquare = 0.0;
        $expectedMatrix = [];
        foreach ($rows as $r) {
            foreach ($cols as $c) {
                $rowTot = $rowTotals[$r] ?? 0;
                $colTot = $colTotals[$c] ?? 0;
                $expected = $grandTotal > 0 ? ($rowTot * $colTot) / $grandTotal : 0;
                $expectedMatrix[$r][$c] = round($expected, 2);

                if ($expected > 0) {
                    $observed = $matrix[$r][$c];
                    $chiSquare += pow($observed - $expected, 2) / $expected;
                }
            }
        }

        $df = (count($rows) - 1) * (count($cols) - 1);
        if ($df <= 0)
            $df = 1;
        $pValue = $this->chiSquareProbability($chiSquare, $df);

        // Likelihood Ratio & expected counts check
        $likelihoodRatio = 0.0;
        $cellsWithExpectedLessThan5 = 0;
        $minExpected = null;
        $totalCells = count($rows) * count($cols);

        foreach ($rows as $r) {
            foreach ($cols as $c) {
                $observed = $matrix[$r][$c];
                $expected = $expectedMatrix[$r][$c];
                if ($expected < 5) {
                    $cellsWithExpectedLessThan5++;
                }
                if ($minExpected === null || $expected < $minExpected) {
                    $minExpected = $expected;
                }
                if ($observed > 0 && $expected > 0) {
                    $likelihoodRatio += $observed * log($observed / $expected);
                }
            }
        }
        $likelihoodRatio = 2.0 * $likelihoodRatio;
        $likelihoodPValue = $this->chiSquareProbability($likelihoodRatio, $df);

        // Linear-by-Linear Association calculation using Pearson r score correlation
        $rowScores = [];
        foreach (array_values($rows) as $idx => $r) {
            $rowScores[$r] = $idx;
        }
        $colScores = [];
        foreach (array_values($cols) as $idx => $c) {
            $colScores[$c] = $idx;
        }

        $sumX = 0;
        $sumY = 0;
        $sumX2 = 0;
        $sumY2 = 0;
        $sumXY = 0;
        $N_valid = 0;
        foreach ($responses as $resp) {
            $rowVal = (string) $this->getAnswerValue($resp, $rowId, $isJson);
            $colVal = (string) $this->getAnswerValue($resp, $colId, $isJson);
            if (isset($rowScores[$rowVal]) && isset($colScores[$colVal])) {
                $x = $rowScores[$rowVal];
                $y = $colScores[$colVal];
                $sumX += $x;
                $sumY += $y;
                $sumX2 += $x * $x;
                $sumY2 += $y * $y;
                $sumXY += $x * $y;
                $N_valid++;
            }
        }

        $linearAssociation = 0.0;
        $linearPValue = 1.0;
        if ($N_valid > 1) {
            $numerator = ($N_valid * $sumXY) - ($sumX * $sumY);
            $denominator = sqrt(max(1e-12, (($N_valid * $sumX2) - pow($sumX, 2)) * (($N_valid * $sumY2) - pow($sumY, 2))));
            $r_corr = $denominator > 0 ? $numerator / $denominator : 0;
            $linearAssociation = ($N_valid - 1) * pow($r_corr, 2);
            $linearPValue = $this->chiSquareProbability($linearAssociation, 1);
        }

        $footnotePercent = $totalCells > 0 ? round(($cellsWithExpectedLessThan5 / $totalCells) * 100, 1) : 0.0;
        $footnote = __("a. :cells cells (:percent%) have expected count less than 5. The minimum expected count is :min.", [
            'cells' => $cellsWithExpectedLessThan5,
            'percent' => $footnotePercent,
            'min' => number_format($minExpected, 2)
        ]);

        return response()->json([
            'success' => true,
            'rowId' => $rowId,
            'colId' => $colId,
            'rowLabel' => $rowLabel,
            'colLabel' => $colLabel,
            'matrix' => $matrix,
            'expectedMatrix' => $expectedMatrix,
            'rows' => $rows,
            'columns' => $cols,
            'rowTotals' => $rowTotals,
            'colTotals' => $colTotals,
            'grandTotal' => $grandTotal,
            'chiSquare' => round($chiSquare, 4),
            'df' => $df,
            'pValue' => round($pValue, 4),
            'significant' => $pValue < 0.05,
            'likelihoodRatio' => round($likelihoodRatio, 4),
            'likelihoodPValue' => round($likelihoodPValue, 4),
            'likelihoodSignificant' => $likelihoodPValue < 0.05,
            'linearAssociation' => round($linearAssociation, 4),
            'linearPValue' => round($linearPValue, 4),
            'linearSignificant' => $linearPValue < 0.05,
            'validCases' => $N_valid,
            'footnote' => $footnote
        ]);
    }

    private function handleTTest($request, $survey, $responses, $isJson)
    {
        $depVar = $request->query('dep');
        $groupVar = $request->query('group');

        if (!$depVar || !$groupVar) {
            return response()->json(['success' => false, 'message' => 'Dependent and Grouping variables are required.'], 400);
        }

        $depLabel = "Dependent Variable";
        $groupLabel = "Grouping Variable";

        if ($isJson) {
            $schema = json_decode($survey->json_schema, true);
            foreach ($schema as $f) {
                if (isset($f['name'])) {
                    if ($f['name'] === $depVar)
                        $depLabel = $f['label'] ?? $depVar;
                    if ($f['name'] === $groupVar)
                        $groupLabel = $f['label'] ?? $groupVar;
                }
            }
        } else {
            $depLabel = \App\Models\Question::find($depVar)?->text ?? $depVar;
            $groupLabel = \App\Models\Question::find($groupVar)?->text ?? $groupVar;
        }

        $grouped = $this->getGroupedValues($responses, $depVar, $groupVar, $isJson);
        $grouped = array_filter($grouped, function ($vals) {
            return count($vals) > 0;
        });

        $groupKeys = array_keys($grouped);

        if (count($groupKeys) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Grouping variable must have at least 2 distinct groups with numeric values. Found ' . count($groupKeys) . '.'
            ], 400);
        }

        $g1Name = (string) $groupKeys[0];
        $g2Name = (string) $groupKeys[1];

        $g1Vals = $grouped[$g1Name];
        $g2Vals = $grouped[$g2Name];

        $n1 = count($g1Vals);
        $n2 = count($g2Vals);

        if ($n1 < 2 || $n2 < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Each group must have at least 2 data points.'
            ], 400);
        }

        $m1 = array_sum($g1Vals) / $n1;
        $m2 = array_sum($g2Vals) / $n2;

        $var1 = 0.0;
        foreach ($g1Vals as $v) {
            $var1 += pow($v - $m1, 2);
        }
        $var1 = $var1 / ($n1 - 1);
        $sd1 = sqrt($var1);

        $var2 = 0.0;
        foreach ($g2Vals as $v) {
            $var2 += pow($v - $m2, 2);
        }
        $var2 = $var2 / ($n2 - 1);
        $sd2 = sqrt($var2);

        // 1. Equal Variances Assumed (Pooled T-Test)
        $dfAssumed = $n1 + $n2 - 2;
        $pooledVar = (($n1 - 1) * $var1 + ($n2 - 1) * $var2) / $dfAssumed;
        $pooledSd = sqrt($pooledVar);
        $seAssumed = $pooledSd * sqrt(1 / $n1 + 1 / $n2);
        $tAssumed = $seAssumed > 0 ? ($m1 - $m2) / $seAssumed : 0.0;
        $pAssumed = $this->tProbability($tAssumed, $dfAssumed);

        $tCritAssumed = 1.96 + (2.38 / $dfAssumed) + (2.71 / pow($dfAssumed, 2));
        $ciLowerAssumed = ($m1 - $m2) - ($tCritAssumed * $seAssumed);
        $ciUpperAssumed = ($m1 - $m2) + ($tCritAssumed * $seAssumed);

        // 2. Equal Variances Not Assumed (Welch T-Test)
        $seWelch = sqrt(max(1e-12, ($var1 / $n1) + ($var2 / $n2)));
        $tWelch = $seWelch > 0 ? ($m1 - $m2) / $seWelch : 0.0;

        $dfWelchNum = pow(($var1 / $n1) + ($var2 / $n2), 2);
        $dfWelchDen = (pow($var1 / $n1, 2) / ($n1 - 1)) + (pow($var2 / $n2, 2) / ($n2 - 1));
        $dfWelch = $dfWelchDen > 0 ? $dfWelchNum / $dfWelchDen : $dfAssumed;
        if ($dfWelch <= 0)
            $dfWelch = 1;
        $pWelch = $this->tProbability($tWelch, $dfWelch);

        $tCritWelch = 1.96 + (2.38 / $dfWelch) + (2.71 / pow($dfWelch, 2));
        $ciLowerWelch = ($m1 - $m2) - ($tCritWelch * $seWelch);
        $ciUpperWelch = ($m1 - $m2) + ($tCritWelch * $seWelch);

        // 3. Levene's Test for Equality of Variances
        $z1 = [];
        foreach ($g1Vals as $v) {
            $z1[] = abs($v - $m1);
        }
        $z2 = [];
        foreach ($g2Vals as $v) {
            $z2[] = abs($v - $m2);
        }
        $mz1 = array_sum($z1) / $n1;
        $mz2 = array_sum($z2) / $n2;
        $grandMeanZ = (array_sum($z1) + array_sum($z2)) / ($n1 + $n2);
        $ssbZ = $n1 * pow($mz1 - $grandMeanZ, 2) + $n2 * pow($mz2 - $grandMeanZ, 2);
        $sswZ = 0.0;
        foreach ($z1 as $z) {
            $sswZ += pow($z - $mz1, 2);
        }
        foreach ($z2 as $z) {
            $sswZ += pow($z - $mz2, 2);
        }
        $dfBetweenZ = 1;
        $dfWithinZ = $n1 + $n2 - 2;
        $msbZ = $ssbZ / $dfBetweenZ;
        $mswZ = $dfWithinZ > 0 ? $sswZ / $dfWithinZ : 0.0;
        $leveneF = $mswZ > 0 ? $msbZ / $mswZ : 0.0;
        $leveneSig = $this->fProbability($leveneF, $dfBetweenZ, $dfWithinZ);

        return response()->json([
            'success' => true,
            'depVar' => $depVar,
            'groupVar' => $groupVar,
            'depLabel' => $depLabel,
            'groupLabel' => $groupLabel,
            'groups' => [
                [
                    'name' => $g1Name,
                    'n' => $n1,
                    'mean' => round($m1, 4),
                    'stdDev' => round($sd1, 4),
                    'stdError' => round($sd1 / sqrt($n1), 4)
                ],
                [
                    'name' => $g2Name,
                    'n' => $n2,
                    'mean' => round($m2, 4),
                    'stdDev' => round($sd2, 4),
                    'stdError' => round($sd2 / sqrt($n2), 4)
                ]
            ],
            'leveneF' => round($leveneF, 4),
            'leveneSig' => round($leveneSig, 4),
            // assumed
            'tValue' => round($tAssumed, 4),
            'df' => $dfAssumed,
            'pValue' => round($pAssumed, 4),
            'meanDiff' => round($m1 - $m2, 4),
            'stdErrorDiff' => round($seAssumed, 4),
            'ciLower' => round($ciLowerAssumed, 4),
            'ciUpper' => round($ciUpperAssumed, 4),
            'significant' => $pAssumed < 0.05,
            // not assumed
            'tValueWelch' => round($tWelch, 4),
            'dfWelch' => round($dfWelch, 2),
            'pValueWelch' => round($pWelch, 4),
            'stdErrorDiffWelch' => round($seWelch, 4),
            'ciLowerWelch' => round($ciLowerWelch, 4),
            'ciUpperWelch' => round($ciUpperWelch, 4),
            'significantWelch' => $pWelch < 0.05
        ]);
    }

    private function handleCorrelation($request, $survey, $responses, $isJson)
    {
        $varX = $request->query('varX');
        $varY = $request->query('varY');

        if (!$varX || !$varY) {
            return response()->json(['success' => false, 'message' => 'Both numeric variables are required.'], 400);
        }

        $labelX = "Variable X";
        $labelY = "Variable Y";

        if ($isJson) {
            $schema = json_decode($survey->json_schema, true);
            foreach ($schema as $f) {
                if (isset($f['name'])) {
                    if ($f['name'] === $varX)
                        $labelX = $f['label'] ?? $varX;
                    if ($f['name'] === $varY)
                        $labelY = $f['label'] ?? $varY;
                }
            }
        } else {
            $labelX = \App\Models\Question::find($varX)?->text ?? $varX;
            $labelY = \App\Models\Question::find($varY)?->text ?? $varY;
        }

        $xVals = [];
        $yVals = [];

        foreach ($responses as $resp) {
            $valX = $this->getAnswerValue($resp, $varX, $isJson);
            $valY = $this->getAnswerValue($resp, $varY, $isJson);

            if ($valX !== null && is_numeric($valX) && $valY !== null && is_numeric($valY)) {
                $xVals[] = (float) $valX;
                $yVals[] = (float) $valY;
            }
        }

        $n = count($xVals);
        if ($n < 3) {
            return response()->json([
                'success' => false,
                'message' => 'Correlation requires at least 3 paired numeric data points.'
            ], 400);
        }

        $meanX = array_sum($xVals) / $n;
        $meanY = array_sum($yVals) / $n;

        $ssX = 0.0;
        $ssY = 0.0;
        $sp = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $diffX = $xVals[$i] - $meanX;
            $diffY = $yVals[$i] - $meanY;
            $ssX += $diffX * $diffX;
            $ssY += $diffY * $diffY;
            $sp += $diffX * $diffY;
        }

        $r = 0.0;
        $denom = sqrt($ssX * $ssY);
        if ($denom > 0) {
            $r = $sp / $denom;
        }

        $df = $n - 2;
        $tValue = 0.0;
        if (abs($r) < 1.0 && (1 - $r * $r) > 0) {
            $tValue = $r * sqrt($df / (1 - $r * $r));
        } elseif (abs($r) >= 1.0) {
            $tValue = 99999;
        }

        $pValue = $this->tProbability($tValue, $df);

        // Covariance
        $covariance = $sp / ($n - 1);

        // Standard error of r
        $seR = $n > 2 ? sqrt((1 - $r * $r) / ($n - 2)) : 0.0;

        // 95% Confidence Intervals via Fisher z transform
        $rLower = null;
        $rUpper = null;
        if ($n > 3 && abs($r) < 1.0) {
            $z = 0.5 * log((1 + $r) / (1 - $r));
            $seZ = 1.0 / sqrt($n - 3);
            $zLower = $z - 1.96 * $seZ;
            $zUpper = $z + 1.96 * $seZ;
            $rLower = (exp(2 * $zLower) - 1) / (exp(2 * $zLower) + 1);
            $rUpper = (exp(2 * $zUpper) - 1) / (exp(2 * $zUpper) + 1);
        } else {
            $rLower = $r;
            $rUpper = $r;
        }

        $sigMarker = $pValue < 0.01 ? '**' : ($pValue < 0.05 ? '*' : '');

        return response()->json([
            'success' => true,
            'varX' => $varX,
            'varY' => $varY,
            'labelX' => $labelX,
            'labelY' => $labelY,
            'n' => $n,
            'r' => round($r, 4),
            'r2' => round($r * $r, 4),
            'tValue' => round($tValue, 4),
            'df' => $df,
            'pValue' => round($pValue, 4),
            'significant' => $pValue < 0.05,
            'sigMarker' => $sigMarker,
            'meanX' => round($meanX, 4),
            'meanY' => round($meanY, 4),
            'stdDevX' => round(sqrt($ssX / ($n - 1)), 4),
            'stdDevY' => round(sqrt($ssY / ($n - 1)), 4),
            'covariance' => round($covariance, 4),
            'stdErrorR' => round($seR, 4),
            'ciLower' => round($rLower, 4),
            'ciUpper' => round($rUpper, 4)
        ]);
    }

    private function handleAnova($request, $survey, $responses, $isJson)
    {
        $depVar = $request->query('dep');
        $groupVar = $request->query('group');

        if (!$depVar || !$groupVar) {
            return response()->json(['success' => false, 'message' => 'Dependent and Grouping variables are required.'], 400);
        }

        $depLabel = "Dependent Variable";
        $groupLabel = "Grouping Variable";

        if ($isJson) {
            $schema = json_decode($survey->json_schema, true);
            foreach ($schema as $f) {
                if (isset($f['name'])) {
                    if ($f['name'] === $depVar)
                        $depLabel = $f['label'] ?? $depVar;
                    if ($f['name'] === $groupVar)
                        $groupLabel = $f['label'] ?? $groupVar;
                }
            }
        } else {
            $depLabel = \App\Models\Question::find($depVar)?->text ?? $depVar;
            $groupLabel = \App\Models\Question::find($groupVar)?->text ?? $groupVar;
        }

        $grouped = $this->getGroupedValues($responses, $depVar, $groupVar, $isJson);
        $grouped = array_filter($grouped, function ($vals) {
            return count($vals) > 0;
        });

        $k = count($grouped);
        if ($k < 2) {
            return response()->json([
                'success' => false,
                'message' => 'One-Way ANOVA requires a grouping variable with at least 2 distinct groups containing numeric values.'
            ], 400);
        }

        $allVals = [];
        $groupStats = [];
        $ssb = 0.0;
        $nTotal = 0;
        $grandSum = 0.0;

        foreach ($grouped as $gName => $vals) {
            $gn = count($vals);
            $gSum = array_sum($vals);
            $gMean = $gSum / $gn;

            $allVals = array_merge($allVals, $vals);
            $nTotal += $gn;
            $grandSum += $gSum;

            $gVar = 0.0;
            if ($gn > 1) {
                foreach ($vals as $v) {
                    $gVar += pow($v - $gMean, 2);
                }
                $gVar = $gVar / ($gn - 1);
            }
            $gSd = sqrt($gVar);

            $minVal = count($vals) > 0 ? min($vals) : 0.0;
            $maxVal = count($vals) > 0 ? max($vals) : 0.0;

            $dfG = $gn - 1;
            if ($dfG <= 0)
                $dfG = 1;
            $tCrit = 1.96 + (2.38 / $dfG) + (2.71 / pow($dfG, 2));
            $seMean = $gn > 0 ? $gSd / sqrt($gn) : 0.0;
            $ciLower = $gMean - ($tCrit * $seMean);
            $ciUpper = $gMean + ($tCrit * $seMean);

            $groupStats[$gName] = [
                'name' => $gName,
                'n' => $gn,
                'mean' => round($gMean, 4),
                'stdDev' => round($gSd, 4),
                'stdError' => round($seMean, 4),
                'ciLower' => round($ciLower, 4),
                'ciUpper' => round($ciUpper, 4),
                'min' => round($minVal, 4),
                'max' => round($maxVal, 4)
            ];
        }

        if ($nTotal <= $k) {
            return response()->json([
                'success' => false,
                'message' => 'Total sample size must be greater than the number of groups.'
            ], 400);
        }

        $grandMean = $grandSum / $nTotal;

        $sst = 0.0;
        foreach ($allVals as $v) {
            $sst += pow($v - $grandMean, 2);
        }

        foreach ($grouped as $gName => $vals) {
            $gn = count($vals);
            $gMean = array_sum($vals) / $gn;
            $ssb += $gn * pow($gMean - $grandMean, 2);
        }

        $ssw = $sst - $ssb;
        if ($ssw < 0)
            $ssw = 0.0;

        $dfBetween = $k - 1;
        $dfWithin = $nTotal - $k;
        $dfTotal = $nTotal - 1;

        $msb = $dfBetween > 0 ? $ssb / $dfBetween : 0.0;
        $msw = $dfWithin > 0 ? $ssw / $dfWithin : 0.0;

        $fValue = $msw > 0 ? $msb / $msw : 0.0;
        $pValue = $this->fProbability($fValue, $dfBetween, $dfWithin);

        return response()->json([
            'success' => true,
            'depVar' => $depVar,
            'groupVar' => $groupVar,
            'depLabel' => $depLabel,
            'groupLabel' => $groupLabel,
            'groupStats' => array_values($groupStats),
            'ssb' => round($ssb, 4),
            'ssw' => round($ssw, 4),
            'sst' => round($sst, 4),
            'dfBetween' => $dfBetween,
            'dfWithin' => $dfWithin,
            'dfTotal' => $dfTotal,
            'msb' => round($msb, 4),
            'msw' => round($msw, 4),
            'fValue' => round($fValue, 4),
            'pValue' => round($pValue, 4),
            'significant' => $pValue < 0.05
        ]);
    }

    private function handleRegression($request, $survey, $responses, $isJson)
    {
        $depVar = $request->query('dep');
        $indVar = $request->query('ind');

        if (!$depVar || !$indVar) {
            return response()->json(['success' => false, 'message' => 'Dependent and Independent variables are required.'], 400);
        }

        $depLabel = "Dependent Variable (Y)";
        $indLabel = "Independent Variable (X)";

        if ($isJson) {
            $schema = json_decode($survey->json_schema, true);
            foreach ($schema as $f) {
                if (isset($f['name'])) {
                    if ($f['name'] === $depVar)
                        $depLabel = $f['label'] ?? $depVar;
                    if ($f['name'] === $indVar)
                        $indLabel = $f['label'] ?? $indVar;
                }
            }
        } else {
            $depLabel = \App\Models\Question::find($depVar)?->text ?? $depVar;
            $indLabel = \App\Models\Question::find($indVar)?->text ?? $indVar;
        }

        $xVals = [];
        $yVals = [];

        foreach ($responses as $resp) {
            $valX = $this->getAnswerValue($resp, $indVar, $isJson);
            $valY = $this->getAnswerValue($resp, $depVar, $isJson);

            if ($valX !== null && is_numeric($valX) && $valY !== null && is_numeric($valY)) {
                $xVals[] = (float) $valX;
                $yVals[] = (float) $valY;
            }
        }

        $n = count($xVals);
        if ($n < 3) {
            return response()->json([
                'success' => false,
                'message' => 'Regression requires at least 3 paired numeric data points.'
            ], 400);
        }

        $meanX = array_sum($xVals) / $n;
        $meanY = array_sum($yVals) / $n;

        $ssX = 0.0;
        $ssY = 0.0;
        $sp = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $diffX = $xVals[$i] - $meanX;
            $diffY = $yVals[$i] - $meanY;
            $ssX += $diffX * $diffX;
            $ssY += $diffY * $diffY;
            $sp += $diffX * $diffY;
        }

        $slope = $ssX > 0 ? $sp / $ssX : 0.0;
        $intercept = $meanY - $slope * $meanX;

        $ssr = 0.0;
        $sse = 0.0;
        $sst = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $predY = $slope * $xVals[$i] + $intercept;
            $ssr += pow($predY - $meanY, 2);
            $sse += pow($yVals[$i] - $predY, 2);
            $sst += pow($yVals[$i] - $meanY, 2);
        }

        $dfReg = 1;
        $dfRes = $n - 2;
        $dfTotal = $n - 1;

        $msr = $ssr;
        $mse = $dfRes > 0 ? $sse / $dfRes : 0.0;

        $fValue = $mse > 0 ? $msr / $mse : 0.0;
        $fPValue = $this->fProbability($fValue, $dfReg, $dfRes);

        $stdErrorEst = sqrt($mse);

        $seSlope = $ssX > 0 ? $stdErrorEst / sqrt($ssX) : 0.0;
        $seIntercept = $ssX > 0 ? $stdErrorEst * sqrt(1 / $n + ($meanX * $meanX) / $ssX) : 0.0;

        $tSlope = $seSlope > 0 ? $slope / $seSlope : 0.0;
        $tIntercept = $seIntercept > 0 ? $intercept / $seIntercept : 0.0;

        $pSlope = $this->tProbability($tSlope, $dfRes);
        $pIntercept = $this->tProbability($tIntercept, $dfRes);

        $r = 0.0;
        $denom = sqrt($ssX * $ssY);
        if ($denom > 0) {
            $r = $sp / $denom;
        }

        $tCrit = 1.96 + (2.38 / $dfRes) + (2.71 / pow($dfRes, 2));
        $ciLowerIntercept = $intercept - ($tCrit * $seIntercept);
        $ciUpperIntercept = $intercept + ($tCrit * $seIntercept);
        $ciLowerSlope = $slope - ($tCrit * $seSlope);
        $ciUpperSlope = $slope + ($tCrit * $seSlope);

        // Standardized beta for simple regression is just r (with sign matching slope)
        $betaSlope = $slope >= 0 ? abs($r) : -abs($r);

        return response()->json([
            'success' => true,
            'depVar' => $depVar,
            'indVar' => $indVar,
            'depLabel' => $depLabel,
            'indLabel' => $indLabel,
            'n' => $n,
            'r' => round($r, 4),
            'r2' => round($r * $r, 4),
            'adjR2' => round(1 - (1 - $r * $r) * ($n - 1) / ($n - 2), 4),
            'stdErrorEst' => round($stdErrorEst, 4),
            'anova' => [
                'ssr' => round($ssr, 4),
                'sse' => round($sse, 4),
                'sst' => round($sst, 4),
                'dfReg' => $dfReg,
                'dfRes' => $dfRes,
                'dfTotal' => $dfTotal,
                'msr' => round($msr, 4),
                'mse' => round($mse, 4),
                'fValue' => round($fValue, 4),
                'pValue' => round($fPValue, 4),
                'significant' => $fPValue < 0.05
            ],
            'coefficients' => [
                'intercept' => [
                    'coef' => round($intercept, 4),
                    'stdError' => round($seIntercept, 4),
                    'tValue' => round($tIntercept, 4),
                    'pValue' => round($pIntercept, 4),
                    'significant' => $pIntercept < 0.05,
                    'beta' => 'N/A',
                    'ciLower' => round($ciLowerIntercept, 4),
                    'ciUpper' => round($ciUpperIntercept, 4)
                ],
                'slope' => [
                    'coef' => round($slope, 4),
                    'stdError' => round($seSlope, 4),
                    'tValue' => round($tSlope, 4),
                    'pValue' => round($pSlope, 4),
                    'significant' => $pSlope < 0.05,
                    'beta' => round($betaSlope, 4),
                    'ciLower' => round($ciLowerSlope, 4),
                    'ciUpper' => round($ciUpperSlope, 4)
                ]
            ]
        ]);
    }

    private function getGroupedValues($responses, $depVar, $groupVar, $isJson)
    {
        $grouped = [];
        foreach ($responses as $resp) {
            $depVal = $this->getAnswerValue($resp, $depVar, $isJson);
            $groupVal = $this->getAnswerValue($resp, $groupVar, $isJson);
            if ($depVal !== null && is_numeric($depVal) && $groupVal !== null) {
                $grouped[$groupVal][] = (float) $depVal;
            }
        }
        return $grouped;
    }

    private function tProbability($t, $df)
    {
        $t = abs($t);
        if ($df <= 0)
            return 1.0;
        $w = $t / sqrt($df);
        $th = atan($w);
        if ($df == 1) {
            return 1.0 - $th / (M_PI / 2.0);
        }
        $sin_th = sin($th);
        $cos_th = cos($th);
        $p = 0.0;
        if ($df % 2 == 1) {
            $p = $sin_th;
            $c = $cos_th;
            for ($i = 3; $i <= $df - 2; $i += 2) {
                $p += $p * $c * $c * ($i - 1) / $i;
            }
            return 1.0 - ($th + $p * $cos_th) / (M_PI / 2.0);
        } else {
            $p = 1.0;
            $c = $cos_th;
            for ($i = 2; $i <= $df - 2; $i += 2) {
                $p += $p * $c * $c * ($i - 1) / $i;
            }
            return 1.0 - $p * $sin_th;
        }
    }

    private function fProbability($F, $df1, $df2)
    {
        if ($F <= 0)
            return 1.0;
        if ($df1 <= 0 || $df2 <= 0)
            return 1.0;
        $x = pow($df2 / ($df2 + $df1 * $F), 1 / 3);
        $mean = 1 - 2 / (9 * $df1);
        $var = 2 / (9 * $df1);
        if ($x == 0)
            return 0.0;
        $num = $mean - $x * (1 - 2 / (9 * $df2));
        $den = sqrt($var + $x * $x * 2 / (9 * $df2));
        $z = $num / $den;
        return 1 - $this->normalCdf($z);
    }

    private function chiSquareProbability($chi2, $df)
    {
        if ($chi2 <= 0)
            return 1.0;
        if ($df <= 0)
            return 1.0;
        $x = $chi2 / $df;
        $mean = 1 - 2 / (9 * $df);
        $var = 2 / (9 * $df);
        $z = (pow($x, 1 / 3) - $mean) / sqrt($var);
        return 1 - $this->normalCdf($z);
    }

    private function normalCdf($z)
    {
        $t = 1 / (1 + 0.2316419 * abs($z));
        $d = 0.3989423 * exp(-$z * $z / 2);
        $p = $d * $t * (0.3193815 + $t * (-0.3565638 + $t * (1.781478 + $t * (-1.821256 + $t * 1.330274))));
        if ($z > 0)
            return 1 - $p;
        return $p;
    }

    private function handleMultipleRegression($request, $survey, $responses, $isJson)
    {
        $depVar = $request->query('dep');
        $indVars = $request->query('ind');

        if (is_string($indVars)) {
            $indVars = explode(',', $indVars);
        }
        $indVars = array_filter((array) $indVars);

        if (!$depVar || empty($indVars)) {
            return response()->json(['success' => false, 'message' => 'Dependent and at least one Independent variable are required.'], 400);
        }

        $depLabel = "Dependent Variable (Y)";
        $indLabels = [];

        if ($isJson) {
            $schema = json_decode($survey->json_schema, true);
            foreach ($schema as $f) {
                if (isset($f['name'])) {
                    if ($f['name'] === $depVar)
                        $depLabel = $f['label'] ?? $depVar;
                    if (in_array($f['name'], $indVars)) {
                        $indLabels[$f['name']] = $f['label'] ?? $f['name'];
                    }
                }
            }
        } else {
            $depLabel = \App\Models\Question::find($depVar)?->text ?? $depVar;
            foreach ($indVars as $id) {
                $indLabels[$id] = \App\Models\Question::find($id)?->text ?? $id;
            }
        }

        // Gather paired data points
        $yVals = [];
        $xVals = []; // Array of arrays: row-indexed [ [X1, X2, ...], [X1, X2, ...] ]

        foreach ($responses as $resp) {
            $valY = $this->getAnswerValue($resp, $depVar, $isJson);
            if ($valY === null || !is_numeric($valY))
                continue;

            $rowX = [];
            $valid = true;
            foreach ($indVars as $id) {
                $valX = $this->getAnswerValue($resp, $id, $isJson);
                if ($valX === null || !is_numeric($valX)) {
                    $valid = false;
                    break;
                }
                $rowX[] = (float) $valX;
            }

            if ($valid) {
                $yVals[] = (float) $valY;
                $xVals[] = $rowX;
            }
        }

        $n = count($yVals);
        $p = count($indVars);

        if ($n <= $p + 1) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient data points. You have {$p} variables but only {$n} complete records. Need at least " . ($p + 2) . " records."
            ], 400);
        }

        // Build Design Matrix X and Vector Y
        $X = [];
        for ($i = 0; $i < $n; $i++) {
            $X[$i] = array_merge([1.0], $xVals[$i]);
        }

        // Calculate X_T (transpose of X)
        $XT = [];
        for ($j = 0; $j <= $p; $j++) {
            for ($i = 0; $i < $n; $i++) {
                $XT[$j][$i] = $X[$i][$j];
            }
        }

        // Calculate XT_X = XT * X
        $XTX = [];
        for ($i = 0; $i <= $p; $i++) {
            for ($j = 0; $j <= $p; $j++) {
                $sum = 0.0;
                for ($k = 0; $k < $n; $k++) {
                    $sum += $XT[$i][$k] * $X[$k][$j];
                }
                $XTX[$i][$j] = $sum;
            }
        }

        // Calculate XT_Y = XT * Y
        $XTY = [];
        for ($i = 0; $i <= $p; $i++) {
            $sum = 0.0;
            for ($k = 0; $k < $n; $k++) {
                $sum += $XT[$i][$k] * $yVals[$k];
            }
            $XTY[$i] = $sum;
        }

        // Invert XTX
        try {
            $XTX_inv = $this->matrixInverse($XTX);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Collinearity error: ' . $e->getMessage()
            ], 400);
        }

        // Calculate coefficients b = XTX_inv * XTY
        $b = [];
        for ($i = 0; $i <= $p; $i++) {
            $sum = 0.0;
            for ($j = 0; $j <= $p; $j++) {
                $sum += $XTX_inv[$i][$j] * $XTY[$j];
            }
            $b[$i] = $sum;
        }

        // Calculate predictions and residuals
        $yHat = [];
        $residuals = [];
        $meanY = array_sum($yVals) / $n;
        $sst = 0.0;
        $sse = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $pred = $b[0];
            for ($j = 1; $j <= $p; $j++) {
                $pred += $b[$j] * $xVals[$i][$j - 1];
            }
            $yHat[] = $pred;
            $residuals[] = $yVals[$i] - $pred;

            $sst += pow($yVals[$i] - $meanY, 2);
            $sse += pow($yVals[$i] - $pred, 2);
        }

        $ssr = $sst - $sse;
        if ($sse < 0)
            $sse = 0.0;
        if ($ssr < 0)
            $ssr = 0.0;

        $dfReg = $p;
        $dfRes = $n - $p - 1;
        $dfTotal = $n - 1;

        $msr = $dfReg > 0 ? $ssr / $dfReg : 0.0;
        $mse = $dfRes > 0 ? $sse / $dfRes : 0.0;
        $fValue = $mse > 0 ? $msr / $mse : 0.0;
        $pValue = $this->fProbability($fValue, $dfReg, $dfRes);

        $stdErrorEst = sqrt($mse);
        $r2 = $sst > 0 ? 1.0 - ($sse / $sst) : 0.0;
        $adjR2 = $dfRes > 0 && $dfTotal > 0 ? 1.0 - (($sse / $dfRes) / ($sst / $dfTotal)) : 0.0;

        // Calculate standard deviations of each variable for Standardized Beta calculation
        $sdY = $this->sampleStdDev($yVals, $meanY);
        $sdX = [];
        for ($j = 0; $j < $p; $j++) {
            $colVals = array_column($xVals, $j);
            $meanCol = array_sum($colVals) / $n;
            $sdX[$j] = $this->sampleStdDev($colVals, $meanCol);
        }

        // Calculate Coefficient SEs, t-values, p-values, Beta, and 95% CI
        $coefficients = [];
        $tCrit = 1.96 + (2.38 / $dfRes) + (2.71 / pow($dfRes, 2));

        // Intercept (Constant)
        $seIntercept = sqrt(abs($mse * $XTX_inv[0][0]));
        $tIntercept = $seIntercept > 0 ? $b[0] / $seIntercept : 0.0;
        $pIntercept = $this->tProbability($tIntercept, $dfRes);
        $ciLowerIntercept = $b[0] - ($tCrit * $seIntercept);
        $ciUpperIntercept = $b[0] + ($tCrit * $seIntercept);

        $coefficients[] = [
            'variable' => '(Constant)',
            'label' => '(Constant)',
            'coef' => round($b[0], 4),
            'stdError' => round($seIntercept, 4),
            'tValue' => round($tIntercept, 4),
            'pValue' => round($pIntercept, 4),
            'significant' => $pIntercept < 0.05,
            'beta' => 'N/A',
            'ciLower' => round($ciLowerIntercept, 4),
            'ciUpper' => round($ciUpperIntercept, 4)
        ];

        // Slopes
        for ($j = 1; $j <= $p; $j++) {
            $varId = $indVars[$j - 1];
            $seSlope = sqrt(abs($mse * $XTX_inv[$j][$j]));
            $tSlope = $seSlope > 0 ? $b[$j] / $seSlope : 0.0;
            $pSlope = $this->tProbability($tSlope, $dfRes);
            $beta = ($sdY > 0) ? $b[$j] * ($sdX[$j - 1] / $sdY) : 0.0;
            $ciLowerSlope = $b[$j] - ($tCrit * $seSlope);
            $ciUpperSlope = $b[$j] + ($tCrit * $seSlope);

            $coefficients[] = [
                'variable' => $varId,
                'label' => $indLabels[$varId] ?? $varId,
                'coef' => round($b[$j], 4),
                'stdError' => round($seSlope, 4),
                'tValue' => round($tSlope, 4),
                'pValue' => round($pSlope, 4),
                'significant' => $pSlope < 0.05,
                'beta' => round($beta, 4),
                'ciLower' => round($ciLowerSlope, 4),
                'ciUpper' => round($ciUpperSlope, 4)
            ];
        }

        // Formulate Dynamic Algebraic Regression Equation
        $equation = 'Y = ' . round($b[0], 3);
        for ($j = 1; $j <= $p; $j++) {
            $coefVal = round($b[$j], 3);
            $sign = $coefVal >= 0 ? ' + ' : ' - ';
            $equation .= $sign . abs($coefVal) . ' * (X' . $j . ')';
        }

        return response()->json([
            'success' => true,
            'depVar' => $depVar,
            'indVars' => $indVars,
            'depLabel' => $depLabel,
            'indLabels' => array_values($indLabels),
            'n' => $n,
            'r' => round(sqrt($r2), 4),
            'r2' => round($r2, 4),
            'adjR2' => round($adjR2, 4),
            'stdErrorEst' => round($stdErrorEst, 4),
            'equation' => $equation,
            'anova' => [
                'ssr' => round($ssr, 4),
                'sse' => round($sse, 4),
                'sst' => round($sst, 4),
                'dfReg' => $dfReg,
                'dfRes' => $dfRes,
                'dfTotal' => $dfTotal,
                'msr' => round($msr, 4),
                'mse' => round($mse, 4),
                'fValue' => round($fValue, 4),
                'pValue' => round($pValue, 4),
                'significant' => $pValue < 0.05
            ],
            'coefficients' => $coefficients
        ]);
    }

    private function sampleStdDev($values, $mean)
    {
        $n = count($values);
        if ($n <= 1)
            return 0.0;
        $sum = 0.0;
        foreach ($values as $v) {
            $sum += pow($v - $mean, 2);
        }
        return sqrt($sum / ($n - 1));
    }

    private function matrixInverse($A)
    {
        $n = count($A);
        $I = [];
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $I[$i][$j] = ($i == $j) ? 1.0 : 0.0;
            }
        }
        for ($i = 0; $i < $n; $i++) {
            $pivot = $A[$i][$i];
            if (abs($pivot) < 1e-12) {
                $swapRow = -1;
                for ($k = $i + 1; $k < $n; $k++) {
                    if (abs($A[$k][$i]) > 1e-12) {
                        $swapRow = $k;
                        break;
                    }
                }
                if ($swapRow === -1) {
                    throw new \Exception("Singular matrix, cannot compute regression model (multicollinearity detected).");
                }
                $tempA = $A[$i];
                $A[$i] = $A[$swapRow];
                $A[$swapRow] = $tempA;
                $tempI = $I[$i];
                $I[$i] = $I[$swapRow];
                $I[$swapRow] = $tempI;
                $pivot = $A[$i][$i];
            }
            for ($j = 0; $j < $n; $j++) {
                $A[$i][$j] /= $pivot;
                $I[$i][$j] /= $pivot;
            }
            for ($k = 0; $k < $n; $k++) {
                if ($k != $i) {
                    $factor = $A[$k][$i];
                    for ($j = 0; $j < $n; $j++) {
                        $A[$k][$j] -= $factor * $A[$i][$j];
                        $I[$k][$j] -= $factor * $I[$i][$j];
                    }
                }
            }
        }
        return $I;
    }
}
