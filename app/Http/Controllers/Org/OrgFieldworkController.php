<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\OrgMember;
use App\Models\OrgSurveyAssignment;
use App\Models\Response as SurveyResponse;
use App\Models\Survey;
use App\Traits\LogsOrgAudit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrgFieldworkController extends Controller
{
    use LogsOrgAudit;

    public function index(Request $request): View
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        $surveys = $org->surveys()->latest()->get();

        $enumerators = OrgMember::where('organization_id', $org->id)
            ->where('org_workspace_role', 'field_enumerator')
            ->where('status', 'active')
            ->with('user')
            ->get();

        $assignments = OrgSurveyAssignment::where('organization_id', $org->id)
            ->with(['survey', 'user', 'assignedBy'])
            ->latest()
            ->get()
            ->map(function ($assignment) {
                $assignment->collected_count = SurveyResponse::where('survey_id', $assignment->survey_id)
                    ->where('collector_id', $assignment->user_id)
                    ->count();
                return $assignment;
            });

        return view('organization.fieldwork.index', compact('org', 'surveys', 'enumerators', 'assignments'));
    }

    public function assign(Request $request, Survey $survey): RedirectResponse
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        if ($survey->organization_id !== $org->id) {
            abort(403, 'Unauthorized survey assignment.');
        }

        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'response_quota' => 'nullable|integer|min:1',
            'zone_label' => 'nullable|string|max:100',
        ]);

        $enumeratorMember = OrgMember::where('organization_id', $org->id)
            ->where('user_id', $request->user_id)
            ->where('org_workspace_role', 'field_enumerator')
            ->where('status', 'active')
            ->first();

        if (!$enumeratorMember) {
            return back()->withErrors(['user_id' => 'Target user is not an active field enumerator in this organization workspace.']);
        }

        $assignment = OrgSurveyAssignment::updateOrCreate(
            [
                'organization_id' => $org->id,
                'survey_id' => $survey->id,
                'user_id' => $request->user_id,
            ],
            [
                'assigned_by' => auth()->id(),
                'response_quota' => $request->response_quota,
                'zone_label' => $request->zone_label,
            ]
        );

        $this->orgLog('fieldwork.assigned', 'Survey', $survey->id, [
            'enumerator_id' => $request->user_id,
            'quota' => $request->response_quota,
            'zone' => $request->zone_label,
        ]);

        return back()->with('success', 'Fieldwork survey successfully assigned to enumerator.');
    }

    public function unassign(Request $request, OrgSurveyAssignment $assignment): RedirectResponse
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        if ($assignment->organization_id !== $org->id) {
            abort(403, 'Unauthorized');
        }

        $surveyId = $assignment->survey_id;
        $userId = $assignment->user_id;

        $assignment->delete();

        $this->orgLog('fieldwork.unassigned', 'Survey', $surveyId, [
            'enumerator_id' => $userId,
        ]);

        return back()->with('success', 'Enumerator assignment removed.');
    }

    public function progress(Request $request, Survey $survey): JsonResponse
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        if ($survey->organization_id !== $org->id) {
            abort(403, 'Unauthorized');
        }

        $assignments = OrgSurveyAssignment::where('survey_id', $survey->id)
            ->where('organization_id', $org->id)
            ->with('user')
            ->get()
            ->map(fn($a) => [
                'assignment_id' => $a->id,
                'enumerator_id' => $a->user_id,
                'enumerator_name' => $a->user->name ?? $a->user->email,
                'zone' => $a->zone_label,
                'quota' => $a->response_quota,
                'collected' => SurveyResponse::where('survey_id', $survey->id)
                    ->where('collector_id', $a->user_id)->count(),
            ]);

        return response()->json($assignments);
    }
}
