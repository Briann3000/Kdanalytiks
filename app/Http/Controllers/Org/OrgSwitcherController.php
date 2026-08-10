<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\ActiveOrgSession;

class OrgSwitcherController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $memberships = $user->orgMemberships()
            ->where('status', 'active')
            ->with('organization')
            ->get();

        // If user has native organization record but no member row, seed it now
        $roleVal = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;
        if ($memberships->isEmpty() && $roleVal === 'organization' && $user->organization) {
            \App\Models\OrgMember::firstOrCreate(
                ['organization_id' => $user->organization->id, 'user_id' => $user->id],
                ['org_workspace_role' => 'owner', 'status' => 'active', 'joined_at' => now()]
            );
            $memberships = $user->orgMemberships()
                ->where('status', 'active')
                ->with('organization')
                ->get();
        }

        $activeOrg = $user->activeOrganization();

        return view('organization.switcher', compact('memberships', 'activeOrg'));
    }

    public function activate(Request $request): RedirectResponse
    {
        $request->validate([
            'organization_id' => 'required|integer|exists:organizations,id',
        ]);

        $membership = auth()->user()->orgMemberships()
            ->where('organization_id', $request->organization_id)
            ->where('status', 'active')
            ->firstOrFail();

        ActiveOrgSession::updateOrCreate(
            ['user_id' => auth()->id()],
            ['active_organization_id' => $membership->organization_id]
        );

        return redirect()->route('organization.dashboard')
            ->with('success', 'Switched workspace to ' . $membership->organization->name);
    }
}
