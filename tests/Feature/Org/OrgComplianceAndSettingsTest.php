<?php

namespace Tests\Feature\Org;

use App\Models\ActiveOrgSession;
use App\Models\Organization;
use App\Models\OrgAuditLog;
use App\Models\OrgMember;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Compliance, Settings, Approval Workflows & Audit Logs Feature Tests
 *
 * Validates survey approval workflows, workspace settings updates,
 * branding enforcement overrides, audit trail recording, and CSV exports.
 */
class OrgComplianceAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $ownerUser;
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

        $this->survey = Survey::factory()->create([
            'organization_id' => $this->org->id,
            'created_by' => $this->ownerUser->id,
            'status' => 'draft',
            'approval_status' => 'draft',
        ]);
    }

    /**
     * Test submitting a survey for approval and workspace admin approving it.
     */
    public function test_survey_approval_workflow(): void
    {
        // 1. Submit for approval
        $response = $this->actingAs($this->ownerUser)->post(route('organization.surveys.submit_approval', $this->survey->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('surveys', [
            'id' => $this->survey->id,
            'approval_status' => 'pending_approval',
        ]);

        // 2. Approve survey
        $response = $this->actingAs($this->ownerUser)->post(route('organization.surveys.approve_workspace', $this->survey->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('surveys', [
            'id' => $this->survey->id,
            'approval_status' => 'approved',
            'status' => 'active',
            'approved_by' => $this->ownerUser->id,
        ]);
    }

    /**
     * Test admin rejecting a survey with feedback reason.
     */
    public function test_admin_can_reject_survey(): void
    {
        $response = $this->actingAs($this->ownerUser)->post(route('organization.surveys.reject_workspace', $this->survey->id), [
            'reason' => 'Please add a demographic section.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('surveys', [
            'id' => $this->survey->id,
            'approval_status' => 'rejected',
            'rejection_reason' => 'Please add a demographic section.',
        ]);
    }

    /**
     * Test updating organization settings (branding, approval, PII).
     */
    public function test_admin_can_update_workspace_settings(): void
    {
        $response = $this->actingAs($this->ownerUser)->post(route('organization.settings.branding'), [
            'logo_url' => 'https://example.com/logo.png',
            'brand_color' => '#10b981',
            'enforce_branding' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('organizations', [
            'id' => $this->org->id,
            'logo_url' => 'https://example.com/logo.png',
            'brand_color' => '#10b981',
            'enforce_branding' => true,
        ]);
    }

    /**
     * Test branding enforcement overrides survey brand properties on save.
     */
    public function test_branding_enforcement_overrides_survey_branding_on_update(): void
    {
        $this->org->update([
            'logo_url' => 'https://org.com/org-logo.png',
            'brand_color' => '#ff0000',
            'enforce_branding' => true,
        ]);

        $response = $this->actingAs($this->ownerUser)->put(route('surveys.update', $this->survey->id), [
            'title' => 'Branded Survey',
            'category' => \App\Enums\SurveyCategory::Academic->value,
            'type' => 'public',
            'json_schema' => json_encode(['pages' => []]),
        ]);

        $this->assertDatabaseHas('surveys', [
            'id' => $this->survey->id,
            'logo_url' => 'https://org.com/org-logo.png',
            'brand_color' => '#ff0000',
        ]);
    }

    /**
     * Test audit log viewer renders and CSV export streams file download.
     */
    public function test_audit_log_index_and_csv_export(): void
    {
        OrgAuditLog::create([
            'organization_id' => $this->org->id,
            'user_id' => $this->ownerUser->id,
            'action' => 'test.action',
            'metadata' => ['key' => 'val'],
        ]);

        $response = $this->actingAs($this->ownerUser)->get(route('organization.audit.index'));
        $response->assertStatus(200);
        $response->assertViewIs('organization.audit.index');

        $exportResponse = $this->actingAs($this->ownerUser)->get(route('organization.audit.export'));
        $exportResponse->assertStatus(200);
        $this->assertStringContainsString('text/csv', $exportResponse->headers->get('Content-Type'));
    }

    /**
     * Test Lead Researcher publishing a survey is held in pending_approval when policy is enabled.
     */
    public function test_lead_researcher_publish_requires_approval_when_setting_enabled(): void
    {
        $this->org->update(['survey_approval_required' => true]);

        $researcherUser = User::factory()->create();
        OrgMember::factory()->leadResearcher()->create([
            'organization_id' => $this->org->id,
            'user_id' => $researcherUser->id,
        ]);
        ActiveOrgSession::factory()->create([
            'user_id' => $researcherUser->id,
            'active_organization_id' => $this->org->id,
        ]);

        $survey = Survey::factory()->create([
            'organization_id' => $this->org->id,
            'created_by' => $researcherUser->id,
            'status' => 'draft',
            'approval_status' => 'draft',
        ]);

        $response = $this->actingAs($researcherUser)->post(route('surveys.publish', $survey->id));
        $response->assertRedirect();

        $this->assertDatabaseHas('surveys', [
            'id' => $survey->id,
            'approval_status' => 'pending_approval',
            'status' => 'draft',
        ]);
    }
}
