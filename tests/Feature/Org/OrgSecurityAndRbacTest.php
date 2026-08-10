<?php

namespace Tests\Feature\Org;

use App\Models\ActiveOrgSession;
use App\Models\Organization;
use App\Models\OrgMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Role-Based Access Control (RBAC) & Multi-Tenant Security Tests
 *
 * Validates permission boundaries, owner immutability against non-owners,
 * immediate middleware blocking of suspended members, and tenant isolation defenses.
 */
class OrgSecurityAndRbacTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $ownerUser;
    private OrgMember $ownerMember;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownerUser = User::factory()->create(['role' => 'organization']);
        $this->org = Organization::factory()->create(['user_id' => $this->ownerUser->id]);

        $this->ownerMember = OrgMember::factory()->owner()->create([
            'organization_id' => $this->org->id,
            'user_id' => $this->ownerUser->id,
        ]);

        ActiveOrgSession::factory()->create([
            'user_id' => $this->ownerUser->id,
            'active_organization_id' => $this->org->id,
        ]);
    }

    /**
     * Test that lower-privilege roles (analyst, field_enumerator, guest_collaborator)
     * cannot access team management routes (403 Forbidden).
     */
    public function test_non_admin_roles_cannot_access_team_management(): void
    {
        $roles = ['analyst', 'field_enumerator', 'guest_collaborator'];

        foreach ($roles as $role) {
            $user = User::factory()->create();
            OrgMember::factory()->state([
                'org_workspace_role' => $role,
                'status' => 'active',
            ])->create([
                        'organization_id' => $this->org->id,
                        'user_id' => $user->id,
                    ]);

            ActiveOrgSession::factory()->create([
                'user_id' => $user->id,
                'active_organization_id' => $this->org->id,
            ]);

            // Attempt GET team index
            $response = $this->actingAs($user)->get(route('organization.team.index'));
            $response->assertStatus(403);

            // Attempt POST invite
            $responseInvite = $this->actingAs($user)->post(route('organization.team.invite'), [
                'email' => 'unauthorized_invite@example.com',
                'org_workspace_role' => 'analyst',
            ]);
            $responseInvite->assertStatus(403);
        }

        // Test that lead_researcher can view team directory, but cannot invite
        $leadUser = User::factory()->create();
        OrgMember::factory()->state([
            'org_workspace_role' => 'lead_researcher',
            'status' => 'active',
        ])->create([
                    'organization_id' => $this->org->id,
                    'user_id' => $leadUser->id,
                ]);
        ActiveOrgSession::factory()->create([
            'user_id' => $leadUser->id,
            'active_organization_id' => $this->org->id,
        ]);

        $this->actingAs($leadUser)->get(route('organization.team.index'))->assertStatus(200);
        $this->actingAs($leadUser)->post(route('organization.team.invite'), [
            'email' => 'unauthorized_invite@example.com',
            'org_workspace_role' => 'analyst',
        ])->assertStatus(403);
    }

    /**
     * Test that non-owner admins CANNOT modify or remove the workspace owner.
     * Prevents administrative takeover / privilege escalation attacks.
     */
    public function test_admin_cannot_modify_or_remove_workspace_owner(): void
    {
        $adminUser = User::factory()->create();
        OrgMember::factory()->admin()->create([
            'organization_id' => $this->org->id,
            'user_id' => $adminUser->id,
        ]);

        ActiveOrgSession::factory()->create([
            'user_id' => $adminUser->id,
            'active_organization_id' => $this->org->id,
        ]);

        // Attempt to demote owner role
        $responseRole = $this->actingAs($adminUser)->patch(route('organization.team.members.role', $this->ownerMember->id), [
            'org_workspace_role' => 'analyst',
        ]);
        $responseRole->assertRedirect();
        $responseRole->assertSessionHasErrors('role');
        $this->assertEquals('owner', $this->ownerMember->fresh()->org_workspace_role);

        // Attempt to suspend owner
        $responseSuspend = $this->actingAs($adminUser)->patch(route('organization.team.members.suspend', $this->ownerMember->id));
        $responseSuspend->assertRedirect();
        $responseSuspend->assertSessionHasErrors('member');
        $this->assertEquals('active', $this->ownerMember->fresh()->status);

        // Attempt to remove owner
        $responseRemove = $this->actingAs($adminUser)->delete(route('organization.team.members.remove', $this->ownerMember->id));
        $responseRemove->assertRedirect();
        $responseRemove->assertSessionHasErrors('member');
        $this->assertDatabaseHas('organization_members', ['id' => $this->ownerMember->id]);
    }

    /**
     * Test that suspended members are blocked by OrgMember middleware immediately.
     */
    public function test_suspended_members_are_blocked_by_middleware(): void
    {
        $user = User::factory()->create();
        OrgMember::factory()->admin()->suspended()->create([
            'organization_id' => $this->org->id,
            'user_id' => $user->id,
        ]);

        ActiveOrgSession::factory()->create([
            'user_id' => $user->id,
            'active_organization_id' => $this->org->id,
        ]);

        $response = $this->actingAs($user)->get(route('organization.team.index'));

        // Middleware logs out suspended user and redirects to login with error
        $response->assertRedirect(route('organization.login'));
        $this->assertGuest();
    }

    /**
     * Test Multi-Tenant Isolation: User from Org A attempting to access/modify Org B resources.
     * Guards against cross-tenant data leakage and IDOR vulnerabilities.
     */
    public function test_multi_tenant_isolation_prevents_cross_organization_mutation(): void
    {
        // Org B setup
        $orgB = Organization::factory()->create();
        $ownerB = User::factory()->create();
        OrgMember::factory()->owner()->create([
            'organization_id' => $orgB->id,
            'user_id' => $ownerB->id,
        ]);

        $memberInOrgB = OrgMember::factory()->leadResearcher()->create([
            'organization_id' => $orgB->id,
            'user_id' => User::factory()->create()->id,
        ]);

        // Owner of Org A attempts to mutate Org B member while logged into Org A active session
        $response = $this->actingAs($this->ownerUser)->delete(route('organization.team.members.remove', $memberInOrgB->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('organization_members', ['id' => $memberInOrgB->id]);
    }
}
