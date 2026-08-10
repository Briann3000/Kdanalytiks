<?php

namespace Tests\Feature\Org;

use App\Models\ActiveOrgSession;
use App\Models\Organization;
use App\Models\OrgMember;
use App\Models\OrgResourcePool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Foundation & Workspace Switcher Integration Tests
 *
 * Covers multi-org user session initialization, workspace switching, auto-seeding of legacy owners,
 * and OrgResourcePool limit authorization logic.
 */
class OrgWorkspaceFoundationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that logging in as a single-org user automatically sets the ActiveOrgSession.
     */
    public function test_single_org_login_sets_active_session(): void
    {
        $user = User::factory()->create(['role' => 'organization']);
        $org = Organization::factory()->create(['user_id' => $user->id]);

        OrgMember::factory()->owner()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
        ]);

        $response = $this->post(route('organization.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('organization.dashboard'));
        $this->assertDatabaseHas('active_org_sessions', [
            'user_id' => $user->id,
            'active_organization_id' => $org->id,
        ]);
    }

    /**
     * Test that logging in as a multi-org member redirects to the workspace switcher.
     */
    public function test_multi_org_login_redirects_to_workspace_switcher(): void
    {
        $user = User::factory()->create(['role' => 'organization']);
        $orgA = Organization::factory()->create(['user_id' => $user->id]);
        $orgB = Organization::factory()->create();

        OrgMember::factory()->owner()->create(['organization_id' => $orgA->id, 'user_id' => $user->id]);
        OrgMember::factory()->admin()->create(['organization_id' => $orgB->id, 'user_id' => $user->id]);

        $response = $this->post(route('organization.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('organization.switcher'));
    }

    /**
     * Test that native organization users without an OrgMember row are auto-seeded as owner upon login.
     */
    public function test_legacy_org_owner_auto_seeded_on_login(): void
    {
        $user = User::factory()->create(['role' => 'organization']);
        $org = Organization::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseMissing('organization_members', [
            'organization_id' => $org->id,
            'user_id' => $user->id,
        ]);

        $response = $this->post(route('organization.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('organization_members', [
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'org_workspace_role' => 'owner',
            'status' => 'active',
        ]);
    }

    /**
     * Test switching active workspace updates the active session.
     */
    public function test_user_can_switch_active_workspace(): void
    {
        $user = User::factory()->create(['role' => 'organization']);
        $orgA = Organization::factory()->create(['user_id' => $user->id]);
        $orgB = Organization::factory()->create();

        OrgMember::factory()->owner()->create(['organization_id' => $orgA->id, 'user_id' => $user->id]);
        OrgMember::factory()->admin()->create(['organization_id' => $orgB->id, 'user_id' => $user->id]);

        ActiveOrgSession::factory()->create([
            'user_id' => $user->id,
            'active_organization_id' => $orgA->id,
        ]);

        $response = $this->actingAs($user)->post(route('organization.switcher.activate'), [
            'organization_id' => $orgB->id,
        ]);

        $response->assertRedirect(route('organization.dashboard'));
        $this->assertDatabaseHas('active_org_sessions', [
            'user_id' => $user->id,
            'active_organization_id' => $orgB->id,
        ]);
    }

    /**
     * Test OrgResourcePool capacity getters and limit checks when at 100% capacity.
     */
    public function test_org_resource_pool_capacity_getters(): void
    {
        $org = Organization::factory()->create();
        $pool = OrgResourcePool::factory()->maxCapacity()->create([
            'organization_id' => $org->id,
        ]);

        $this->assertFalse($pool->canUseAiAnalysis());
        $this->assertFalse($pool->canTranscribe());
        $this->assertFalse($pool->canUseSocius());
        $this->assertEquals(100, $pool->aiUsagePct());

        // Increment limit and test allowed status
        $pool->update(['ai_analyses_limit' => 10]);
        $this->assertTrue($pool->canUseAiAnalysis());
        $this->assertEquals(50, $pool->aiUsagePct());
    }
}
