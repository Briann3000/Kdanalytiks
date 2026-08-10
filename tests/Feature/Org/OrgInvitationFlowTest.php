<?php

namespace Tests\Feature\Org;

use App\Models\Organization;
use App\Models\OrgInvitation;
use App\Models\OrgMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Invitation Acceptance & Public Gate Security Tests
 *
 * Validates invitation token resolution, landing gates, logged-in acceptance,
 * new guest registration, expired/accepted token security, and token tampering defenses.
 */
class OrgInvitationFlowTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $inviter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inviter = User::factory()->create(['role' => 'organization']);
        $this->org = Organization::factory()->create(['user_id' => $this->inviter->id]);
    }

    /**
     * Test public landing gate displays correctly for unauthenticated user.
     */
    public function test_public_invitation_gate_renders_for_guest(): void
    {
        $invitation = OrgInvitation::factory()->create([
            'organization_id' => $this->org->id,
            'invited_by' => $this->inviter->id,
        ]);

        $response = $this->get(route('org.invite.show', $invitation->token));

        $response->assertStatus(200);
        $response->assertViewIs('org.invite.gate');
        $response->assertViewHas('invitation', function ($invite) use ($invitation) {
            return $invite->id === $invitation->id && $invite->organization_id === $this->org->id;
        });
    }

    /**
     * Test logged-in existing user accepting an invitation token links them as OrgMember.
     */
    public function test_existing_authenticated_user_can_accept_invitation(): void
    {
        $invitee = User::factory()->create(['email' => 'invitee@example.com']);

        $invitation = OrgInvitation::factory()->create([
            'organization_id' => $this->org->id,
            'invited_by' => $this->inviter->id,
            'email' => 'invitee@example.com',
            'org_workspace_role' => 'lead_researcher',
        ]);

        $response = $this->actingAs($invitee)->post(route('org.invite.accept', $invitation->token));

        $response->assertRedirect(route('organization.dashboard'));

        $this->assertDatabaseHas('organization_members', [
            'organization_id' => $this->org->id,
            'user_id' => $invitee->id,
            'org_workspace_role' => 'lead_researcher',
            'status' => 'active',
        ]);

        $this->assertNotNull($invitation->fresh()->accepted_at);
        $this->assertDatabaseHas('active_org_sessions', [
            'user_id' => $invitee->id,
            'active_organization_id' => $this->org->id,
        ]);
    }

    /**
     * Test new guest registration flow via invitation token.
     */
    public function test_new_guest_can_register_and_accept_invitation(): void
    {
        $invitation = OrgInvitation::factory()->create([
            'organization_id' => $this->org->id,
            'invited_by' => $this->inviter->id,
            'email' => 'newguest@example.com',
            'org_workspace_role' => 'analyst',
        ]);

        $response = $this->post(route('org.invite.register.store', $invitation->token), [
            'name' => 'Jane Guest',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'phone_number' => '+254711223344',
        ]);

        $response->assertRedirect(route('organization.dashboard'));

        $user = User::where('email', 'newguest@example.com')->first();
        $this->assertNotNull($user);
        $this->assertDatabaseHas('organization_members', [
            'organization_id' => $this->org->id,
            'user_id' => $user->id,
            'org_workspace_role' => 'analyst',
        ]);
    }

    /**
     * Test that expired invitation tokens are rejected.
     * Prevents stale link exploitation.
     */
    public function test_expired_invitation_token_is_rejected(): void
    {
        $invitation = OrgInvitation::factory()->expired()->create([
            'organization_id' => $this->org->id,
            'invited_by' => $this->inviter->id,
        ]);

        $response = $this->get(route('org.invite.show', $invitation->token));

        $response->assertRedirect('/');
        $response->assertSessionHas('error');
    }

    /**
     * Test already accepted invitation tokens cannot be reused.
     * Prevents token replay attacks.
     */
    public function test_already_accepted_invitation_cannot_be_reused(): void
    {
        $invitation = OrgInvitation::factory()->accepted()->create([
            'organization_id' => $this->org->id,
            'invited_by' => $this->inviter->id,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('org.invite.accept', $invitation->token));

        $response->assertRedirect('/');
        $response->assertSessionHas('error');
    }

    /**
     * Test invalid or tampered tokens return redirect error without exposing system details.
     * Guards against token enumeration/brute-force attacks.
     */
    public function test_invalid_or_tampered_token_is_safely_handled(): void
    {
        $response = $this->get(route('org.invite.show', 'tampered-invalid-token-12345'));

        $response->assertRedirect('/');
        $response->assertSessionHas('error');
    }
}
