<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\OrgInvitation;
use App\Models\OrgMember;
use App\Models\ActiveOrgSession;
use App\Models\User;
use App\Models\OrgAuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Auth\Events\Registered;

use App\Traits\LogsOrgAudit;

class OrgInvitationController extends Controller
{
    use LogsOrgAudit;
    public function show(string $token): View|RedirectResponse
    {
        $invitation = OrgInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->with(['organization', 'invitedBy'])
            ->first();

        if (!$invitation) {
            return redirect('/')->with('error', 'Invalid or already accepted invitation link.');
        }

        if ($invitation->isExpired()) {
            return redirect('/')->with('error', 'This invitation link has expired.');
        }

        session(['pending_org_invite_token' => $token]);

        if (Auth::check()) {
            return view('org.invite.confirm', compact('invitation'));
        }

        $existingUser = User::where('email', $invitation->email)->first();
        return view('org.invite.gate', compact('invitation', 'existingUser'));
    }

    public function accept(string $token): RedirectResponse
    {
        $invitation = OrgInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->first();

        if (!$invitation) {
            return redirect('/')->with('error', 'Invalid or already accepted invitation link.');
        }

        if ($invitation->isExpired()) {
            return redirect('/')->with('error', 'This invitation link has expired.');
        }

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('org.invite.show', $token);
        }

        $member = OrgMember::firstOrCreate(
            ['organization_id' => $invitation->organization_id, 'user_id' => $user->id],
            [
                'org_workspace_role' => $invitation->org_workspace_role,
                'status' => 'active',
                'invited_by' => $invitation->invited_by,
                'joined_at' => now(),
            ]
        );

        // If existing member was suspended or pending, ensure active and role updated
        if ($member->status !== 'active') {
            $member->update([
                'status' => 'active',
                'org_workspace_role' => $invitation->org_workspace_role,
                'joined_at' => now(),
            ]);
        }

        $invitation->update(['accepted_at' => now()]);

        ActiveOrgSession::updateOrCreate(
            ['user_id' => $user->id],
            ['active_organization_id' => $invitation->organization_id]
        );

        $this->orgLog('member.joined', 'OrgMember', $member->id, [
            'role' => $invitation->org_workspace_role,
            'via' => 'invitation_link',
        ]);

        session()->forget('pending_org_invite_token');

        return redirect()->route('organization.dashboard')
            ->with('success', 'Welcome to ' . $invitation->organization->name . ' workspace!');
    }

    public function register(string $token): View|RedirectResponse
    {
        $invitation = OrgInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->with('organization')
            ->first();

        if (!$invitation || $invitation->isExpired()) {
            return redirect('/')->with('error', 'This invitation link is invalid or expired.');
        }

        return view('org.invite.register', compact('invitation'));
    }

    public function storeNewUser(Request $request, string $token): RedirectResponse
    {
        $invitation = OrgInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->first();

        if (!$invitation || $invitation->isExpired()) {
            return redirect('/')->with('error', 'This invitation link is invalid or expired.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:25',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $invitation->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'role' => 'organization',
            'status' => 'active',
            'locale' => app()->getLocale(),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return $this->accept($token);
    }
}
