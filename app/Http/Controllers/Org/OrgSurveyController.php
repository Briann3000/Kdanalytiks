<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Traits\LogsOrgAudit;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class OrgSurveyController extends Controller
{
    use LogsOrgAudit;

    public function submitForApproval(Request $request, Survey $survey): RedirectResponse
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        if ($survey->organization_id !== $org->id) {
            abort(403, 'Unauthorized');
        }

        $survey->update([
            'approval_status' => 'pending_approval',
        ]);

        $this->orgLog('survey.submitted_for_approval', 'Survey', $survey->id, [
            'title' => $survey->title,
        ]);

        return back()->with('success', 'Survey submitted for workspace approval.');
    }

    public function approve(Request $request, Survey $survey): RedirectResponse
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        if ($survey->organization_id !== $org->id) {
            abort(403, 'Unauthorized');
        }

        $survey->update([
            'approval_status' => 'approved',
            'status' => 'active',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $this->orgLog('survey.approved', 'Survey', $survey->id, [
            'title' => $survey->title,
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Survey approved and activated.');
    }

    public function reject(Request $request, Survey $survey): RedirectResponse
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        if ($survey->organization_id !== $org->id) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $survey->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        $this->orgLog('survey.rejected', 'Survey', $survey->id, [
            'title' => $survey->title,
            'reason' => $request->reason,
        ]);

        return back()->with('success', 'Survey rejected with feedback.');
    }
}
