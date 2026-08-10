<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\OrgInvitation;
use App\Models\OrgMember;
use App\Models\User;
use App\Mail\OrgInvitationMail;
use App\Traits\LogsOrgAudit;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class OrgTeamController extends Controller
{
    use LogsOrgAudit;

    public function index(Request $request): View
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();
        $currentMember = $request->attributes->get('org_member') ?? $org->memberRecord(auth()->user());

        $members = $org->members()->with(['user', 'invitedBy'])
            ->select('organization_members.*')
            ->selectRaw("CASE org_workspace_role 
                WHEN 'owner' THEN 1 
                WHEN 'admin' THEN 2 
                WHEN 'lead_researcher' THEN 3 
                WHEN 'analyst' THEN 4 
                WHEN 'field_enumerator' THEN 5 
                WHEN 'guest_collaborator' THEN 6 
                ELSE 7 END as hierarchy_rank")
            ->orderBy('hierarchy_rank')
            ->get();
        $invitations = $org->invitations()->with('invitedBy')->latest()->get();

        $seatStats = [
            'used' => $org->activeMembers()->count(),
            'pending' => $invitations->count(),
            'max' => $org->max_seats,
            'is_near' => $org->isNearSeatLimit(),
            'has_reached' => $org->hasReachedSeatLimit(),
        ];

        return view('organization.team.index', compact('org', 'members', 'invitations', 'seatStats', 'currentMember'));
    }

    public function invite(Request $request): RedirectResponse
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        $request->validate([
            'email' => 'required|email',
            'org_workspace_role' => 'required|in:admin,lead_researcher,field_enumerator,analyst,guest_collaborator',
            'message' => 'nullable|string|max:500',
        ]);

        $email = strtolower(trim($request->email));

        // Check if already a member
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $alreadyMember = OrgMember::where('organization_id', $org->id)
                ->where('user_id', $existingUser->id)->exists();
            if ($alreadyMember) {
                return back()->withErrors(['email' => 'This person is already a member of your organization workspace.']);
            }
        }

        // Check for active pending invite
        $pendingInvite = OrgInvitation::where('organization_id', $org->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->exists();
        if ($pendingInvite) {
            return back()->withErrors(['email' => 'An active invitation is already pending for this email.']);
        }

        // Hard seat limit check
        if ($org->hasReachedSeatLimit()) {
            return back()->withErrors(['email' => "Workspace seat limit reached ({$org->totalSeatsUsed()}/{$org->max_seats}). Please upgrade your subscription tier to add more seats."])->withInput();
        }

        $invitation = OrgInvitation::create([
            'organization_id' => $org->id,
            'invited_by' => auth()->id(),
            'email' => $email,
            'org_workspace_role' => $request->org_workspace_role,
            'token' => OrgInvitation::generateToken(),
            'message' => $request->message,
            'expires_at' => now()->addDays(7),
        ]);

        try {
            Mail::to($email)->send(new OrgInvitationMail($invitation, $org));
        } catch (\Exception $e) {
            // Mail fail-safe logging
            logger()->error("Failed sending org invitation email to {$email}: " . $e->getMessage());
        }

        $this->orgLog('member.invited', 'OrgInvitation', $invitation->id, [
            'email' => $email,
            'role' => $request->org_workspace_role,
        ]);

        return back()->with('success', "Invitation sent successfully to {$email}.");
    }

    public function updateRole(Request $request, OrgMember $member): RedirectResponse
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        if ($member->organization_id !== $org->id) {
            abort(403, 'Unauthorized');
        }

        if ($member->isOwner()) {
            return back()->withErrors(['role' => 'The workspace owner role cannot be changed.']);
        }

        $request->validate([
            'org_workspace_role' => 'required|in:admin,lead_researcher,field_enumerator,analyst,guest_collaborator',
        ]);

        $oldRole = $member->org_workspace_role;
        $member->update(['org_workspace_role' => $request->org_workspace_role]);

        $this->orgLog('member.role_changed', 'OrgMember', $member->id, [
            'user_id' => $member->user_id,
            'from' => $oldRole,
            'to' => $request->org_workspace_role,
        ]);

        return back()->with('success', 'Member role updated successfully.');
    }

    public function suspend(Request $request, OrgMember $member): RedirectResponse
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        if ($member->organization_id !== $org->id) {
            abort(403, 'Unauthorized');
        }

        if ($member->isOwner()) {
            return back()->withErrors(['member' => 'The workspace owner cannot be suspended.']);
        }

        $newStatus = $member->status === 'active' ? 'suspended' : 'active';
        $member->update(['status' => $newStatus]);

        if ($newStatus === 'suspended') {
            \App\Models\ActiveOrgSession::where('user_id', $member->user_id)->delete();
        }

        $this->orgLog('member.' . ($newStatus === 'suspended' ? 'suspended' : 'reactivated'), 'OrgMember', $member->id, [
            'user_id' => $member->user_id,
        ]);

        return back()->with('success', "Member status updated to {$newStatus}.");
    }

    public function remove(Request $request, OrgMember $member): RedirectResponse
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        if ($member->organization_id !== $org->id) {
            abort(403, 'Unauthorized');
        }

        if ($member->isOwner()) {
            return back()->withErrors(['member' => 'The workspace owner cannot be removed.']);
        }

        $userId = $member->user_id;
        $member->delete();

        $this->orgLog('member.removed', 'OrgMember', $member->id, [
            'user_id' => $userId,
        ]);

        return back()->with('success', 'Member removed from organization workspace.');
    }

    public function resendInvitation(Request $request, OrgInvitation $invitation): RedirectResponse
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        if ($invitation->organization_id !== $org->id) {
            abort(403, 'Unauthorized');
        }

        $invitation->update([
            'token' => OrgInvitation::generateToken(),
            'expires_at' => now()->addDays(7),
        ]);

        try {
            Mail::to($invitation->email)->send(new OrgInvitationMail($invitation, $org));
        } catch (\Exception $e) {
            logger()->error("Failed resending org invitation email to {$invitation->email}: " . $e->getMessage());
        }

        $this->orgLog('member.invite_resent', 'OrgInvitation', $invitation->id, [
            'email' => $invitation->email,
        ]);

        return back()->with('success', "Invitation resent to {$invitation->email}.");
    }

    public function revokeInvitation(Request $request, OrgInvitation $invitation): RedirectResponse
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        if ($invitation->organization_id !== $org->id) {
            abort(403, 'Unauthorized');
        }

        $email = $invitation->email;
        $invitation->delete();

        $this->orgLog('member.invite_revoked', 'OrgInvitation', $invitation->id, [
            'email' => $email,
        ]);

        return back()->with('success', "Invitation for {$email} revoked.");
    }
}
