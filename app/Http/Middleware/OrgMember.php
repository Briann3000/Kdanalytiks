<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\OrgMember as OrgMemberModel;
use App\Models\ActiveOrgSession;

class OrgMember
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $org = $user->activeOrganization();

        // If user has no active org session, redirect to switcher
        if (!$org) {
            return redirect()->route('organization.switcher');
        }

        $member = $org->memberRecord($user);

        // Seed legacy org owner if no membership record exists yet
        if (!$member && optional($user->role)->value === 'organization' && $user->organization?->id === $org->id) {
            $member = OrgMemberModel::create([
                'organization_id' => $org->id,
                'user_id' => $user->id,
                'org_workspace_role' => 'owner',
                'status' => 'active',
                'joined_at' => now(),
            ]);
        }

        if (!$member || $member->status !== 'active') {
            ActiveOrgSession::where('user_id', $user->id)->delete();
            auth()->logout();
            return redirect()->route('organization.login')
                ->withErrors(['error' => __('Your access to this organization workspace has been suspended or is inactive.')]);
        }

        // Role gate (optional — e.g. ->middleware('org.member:owner,admin'))
        if (!empty($roles) && !in_array($member->org_workspace_role, $roles)) {
            abort(403, 'You do not have permission to access this area of the organization workspace.');
        }

        // Bind to request attributes for easy access in controllers
        $request->attributes->set('org_member', $member);
        $request->attributes->set('active_org', $org);

        return $next($request);
    }
}
