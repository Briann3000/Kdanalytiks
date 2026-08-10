<?php

namespace Tests\Feature\Org;

use App\Models\ActiveOrgSession;
use App\Models\Organization;
use App\Models\OrgInvitation;
use App\Models\OrgMember;
use App\Models\User;
use App\Mail\OrgInvitationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Team Management & Member Operations Tests
 *
 * Validates invitation dispatch, soft seat limit warnings, role mutations,
 * member suspensions, removals, and invitation lifecycle management.
 */
class OrgTeamManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->owner = User::factory()->create(['role' => 'organization']);
        $this->org = Organization::factory()->create([
            'user_id' => $this->owner->id,
            'max_seats' => 2,
        ]);

        OrgMember::factory()->owner()->create([
            'organization_id' => $this->org->id,
            'user_id' => $this->owner->id,
        ]);

        ActiveOrgSession::factory()->create([
            'user_id' => $this->owner->id,
            'active_organization_id' => $this->org->id,
        ]);
    }

    /**
     * Test workspace owner/admin inviting a new team member.
     */
    public function test_owner_can_invite_team_member(): void
    {
        $response = $this->actingAs($this->owner)->post(route('organization.team.invite'), [
            'email' => 'newmember@example.com',
            'org_workspace_role' => 'lead_researcher',
            'message' => 'Welcome to our research team!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('organization_invitations', [
            'organization_id' => $this->org->id,
            'email' => 'newmember@example.com',
            'org_workspace_role' => 'lead_researcher',
        ]);

        Mail::assertSent(OrgInvitationMail::class, function ($mail) {
            return $mail->hasTo('newmember@example.com');
        });
    }

    /**
     * Test soft seat limit warning when inviting beyond max_seats limit.
     * Prevents hard blocks while warning the administrator.
     */
    public function test_hard_seat_limit_blocks_invitation_when_seats_exceeded(): void
    {
        // Set max_seats to 2 on organization and tier
        $this->org->update(['max_seats' => 2]);
        if ($this->org->subscriptionTier) {
            $this->org->subscriptionTier->update(['org_max_seats' => 2]);
            $this->org->unsetRelation('subscriptionTier');
        }

        // Owner is 1st seat. Add 2nd active member.
        $user2 = User::factory()->create();
        OrgMember::factory()->leadResearcher()->create([
            'organization_id' => $this->org->id,
            'user_id' => $user2->id,
        ]);

        // Attempt to invite a 3rd member (exceeding max_seats of 2)
        $response = $this->actingAs($this->owner)->post(route('organization.team.invite'), [
            'email' => 'thirdmember@example.com',
            'org_workspace_role' => 'analyst',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseMissing('organization_invitations', [
            'organization_id' => $this->org->id,
            'email' => 'thirdmember@example.com',
        ]);
    }

    /**
     * Test admin can update a non-owner member's workspace role.
     */
    public function test_admin_can_update_member_role(): void
    {
        $memberUser = User::factory()->create();
        $member = OrgMember::factory()->leadResearcher()->create([
            'organization_id' => $this->org->id,
            'user_id' => $memberUser->id,
        ]);

        $response = $this->actingAs($this->owner)->patch(route('organization.team.members.role', $member->id), [
            'org_workspace_role' => 'field_enumerator',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('organization_members', [
            'id' => $member->id,
            'org_workspace_role' => 'field_enumerator',
        ]);
    }

    /**
     * Test owner/admin can toggle suspension state of a member.
     */
    public function test_admin_can_toggle_member_suspension(): void
    {
        $memberUser = User::factory()->create();
        $member = OrgMember::factory()->leadResearcher()->create([
            'organization_id' => $this->org->id,
            'user_id' => $memberUser->id,
            'status' => 'active',
        ]);

        // Suspend member
        $response = $this->actingAs($this->owner)->patch(route('organization.team.members.suspend', $member->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('organization_members', [
            'id' => $member->id,
            'status' => 'suspended',
        ]);

        // Reactivate member
        $response = $this->actingAs($this->owner)->patch(route('organization.team.members.suspend', $member->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('organization_members', [
            'id' => $member->id,
            'status' => 'active',
        ]);
    }

    /**
     * Test admin can remove a member from the workspace.
     */
    public function test_admin_can_remove_member(): void
    {
        $memberUser = User::factory()->create();
        $member = OrgMember::factory()->analyst()->create([
            'organization_id' => $this->org->id,
            'user_id' => $memberUser->id,
        ]);

        $response = $this->actingAs($this->owner)->delete(route('organization.team.members.remove', $member->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('organization_members', [
            'id' => $member->id,
        ]);
    }

    /**
     * Test resending and revoking pending invitations.
     */
    public function test_admin_can_resend_and_revoke_invitation(): void
    {
        $invitation = OrgInvitation::factory()->create([
            'organization_id' => $this->org->id,
            'invited_by' => $this->owner->id,
        ]);

        // Resend
        $responseResend = $this->actingAs($this->owner)->post(route('organization.team.invitations.resend', $invitation->id));
        $responseResend->assertRedirect();
        Mail::assertSent(OrgInvitationMail::class);

        // Revoke
        $responseRevoke = $this->actingAs($this->owner)->delete(route('organization.team.invitations.revoke', $invitation->id));
        $responseRevoke->assertRedirect();
        $this->assertDatabaseMissing('organization_invitations', [
            'id' => $invitation->id,
        ]);
    }
}
