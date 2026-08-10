<?php

namespace Tests\Feature\Org;

use App\Models\ActiveOrgSession;
use App\Models\Organization;
use App\Models\OrgMember;
use App\Models\OrgSurveyAssignment;
use App\Models\Response as SurveyResponse;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fieldwork & Enumerator Coordination Feature Tests
 *
 * Validates survey assignment, quota tracking, collector attribution,
 * enumerator dashboard routing, and unassignment workflows.
 */
class OrgFieldworkTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $ownerUser;
    private User $enumUser;
    private Survey $survey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownerUser = User::factory()->create(['role' => 'organization']);
        $this->org = Organization::factory()->create(['user_id' => $this->ownerUser->id]);

        OrgMember::factory()->owner()->create([
            'organization_id' => $this->org->id,
            'user_id' => $this->ownerUser->id,
        ]);

        ActiveOrgSession::factory()->create([
            'user_id' => $this->ownerUser->id,
            'active_organization_id' => $this->org->id,
        ]);

        $this->enumUser = User::factory()->create();
        OrgMember::factory()->fieldEnumerator()->create([
            'organization_id' => $this->org->id,
            'user_id' => $this->enumUser->id,
        ]);

        $this->survey = Survey::factory()->create(['organization_id' => $this->org->id]);
    }

    /**
     * Test fieldwork index renders assignments.
     */
    public function test_fieldwork_index_renders_assignments(): void
    {
        OrgSurveyAssignment::create([
            'organization_id' => $this->org->id,
            'survey_id' => $this->survey->id,
            'user_id' => $this->enumUser->id,
            'assigned_by' => $this->ownerUser->id,
            'response_quota' => 100,
            'zone_label' => 'North Zone',
        ]);

        $response = $this->actingAs($this->ownerUser)->get(route('organization.fieldwork.index'));

        $response->assertStatus(200);
        $response->assertViewIs('organization.fieldwork.index');
        $response->assertViewHas('assignments');
    }

    /**
     * Test admin can assign survey to an active field enumerator.
     */
    public function test_admin_can_assign_survey_to_field_enumerator(): void
    {
        $response = $this->actingAs($this->ownerUser)->post(route('organization.fieldwork.assign', $this->survey->id), [
            'user_id' => $this->enumUser->id,
            'response_quota' => 50,
            'zone_label' => 'East Sector',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('org_survey_assignments', [
            'organization_id' => $this->org->id,
            'survey_id' => $this->survey->id,
            'user_id' => $this->enumUser->id,
            'response_quota' => 50,
            'zone_label' => 'East Sector',
        ]);
    }

    /**
     * Test assigning to non-enumerator is rejected.
     */
    public function test_assigning_to_non_enumerator_fails_validation(): void
    {
        $analystUser = User::factory()->create();
        OrgMember::factory()->analyst()->create([
            'organization_id' => $this->org->id,
            'user_id' => $analystUser->id,
        ]);

        $response = $this->actingAs($this->ownerUser)->post(route('organization.fieldwork.assign', $this->survey->id), [
            'user_id' => $analystUser->id,
            'response_quota' => 50,
        ]);

        $response->assertSessionHasErrors('user_id');
        $this->assertDatabaseMissing('org_survey_assignments', [
            'user_id' => $analystUser->id,
        ]);
    }

    /**
     * Test admin can unassign an enumerator.
     */
    public function test_admin_can_unassign_enumerator(): void
    {
        $assignment = OrgSurveyAssignment::create([
            'organization_id' => $this->org->id,
            'survey_id' => $this->survey->id,
            'user_id' => $this->enumUser->id,
            'assigned_by' => $this->ownerUser->id,
        ]);

        $response = $this->actingAs($this->ownerUser)->delete(route('organization.fieldwork.unassign', $assignment->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('org_survey_assignments', ['id' => $assignment->id]);
    }

    /**
     * Test progress endpoint returns JSON payload with collected counts.
     */
    public function test_progress_endpoint_returns_json_counts(): void
    {
        OrgSurveyAssignment::create([
            'organization_id' => $this->org->id,
            'survey_id' => $this->survey->id,
            'user_id' => $this->enumUser->id,
            'assigned_by' => $this->ownerUser->id,
            'response_quota' => 20,
            'zone_label' => 'West Zone',
        ]);

        SurveyResponse::create([
            'survey_id' => $this->survey->id,
            'collector_id' => $this->enumUser->id,
        ]);

        $response = $this->actingAs($this->ownerUser)->get(route('organization.fieldwork.progress', $this->survey->id));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'enumerator_id' => $this->enumUser->id,
            'zone' => 'West Zone',
            'quota' => 20,
            'collected' => 1,
        ]);
    }

    /**
     * Test enumerator dashboard route renders enumerator_dashboard view.
     */
    public function test_enumerator_dashboard_renders_for_field_enumerator(): void
    {
        ActiveOrgSession::factory()->create([
            'user_id' => $this->enumUser->id,
            'active_organization_id' => $this->org->id,
        ]);

        OrgSurveyAssignment::create([
            'organization_id' => $this->org->id,
            'survey_id' => $this->survey->id,
            'user_id' => $this->enumUser->id,
            'assigned_by' => $this->ownerUser->id,
        ]);

        $response = $this->actingAs($this->enumUser)->get(route('organization.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('organization.enumerator_dashboard');
        $response->assertViewHas('assignments');
    }
}
