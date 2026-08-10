<?php

namespace Tests\Feature\Org;

use App\Jobs\GenerateOrgSociusContextJob;
use App\Models\ActiveOrgSession;
use App\Models\Organization;
use App\Models\OrgMember;
use App\Models\OrgSociusContext;
use App\Models\SociusKnowledgeBase;
use App\Models\Survey;
use App\Models\SurveyAiThread;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cross-Survey Socius AI Intelligence Feature Tests
 *
 * Validates institutional memory summary generation, context injection during streaming,
 * role authorization (excluding field_enumerators), and tenant isolation.
 */
class OrgSociusIntelligenceTest extends TestCase
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
     * Test GenerateOrgSociusContextJob creates OrgSociusContext record for an organization survey.
     */
    public function test_generate_org_socius_context_job_creates_institutional_summary(): void
    {
        $survey = Survey::factory()->create(['organization_id' => $this->org->id]);

        $aiMock = $this->createMock(AiService::class);
        $aiMock->expects($this->once())
            ->method('quickComplete')
            ->willReturn('Compressed 300-word summary of survey key findings and methodology.');

        $job = new GenerateOrgSociusContextJob($survey, 'Raw AI Analysis Text of the survey.', $this->ownerUser->id);
        $job->handle($aiMock);

        $this->assertDatabaseHas('org_socius_contexts', [
            'organization_id' => $this->org->id,
            'survey_id' => $survey->id,
            'context_type' => 'survey_summary',
        ]);
    }

    /**
     * Test OrgSociusController index renders study summaries and shared KB.
     */
    public function test_org_socius_index_renders_institutional_summaries(): void
    {
        $context = OrgSociusContext::factory()->create([
            'organization_id' => $this->org->id,
        ]);

        $kb = SociusKnowledgeBase::create([
            'user_id' => $this->ownerUser->id,
            'organization_id' => $this->org->id,
            'is_org_shared' => true,
            'content' => 'Org Strategic Plan 2026',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->ownerUser)->get(route('organization.socius.index'));

        $response->assertStatus(200);
        $response->assertViewIs('organization.socius.index');
        $response->assertViewHas('contexts');
        $response->assertViewHas('sharedKBs');
    }

    /**
     * Test creating a new Org Socius chat thread.
     */
    public function test_user_can_create_org_socius_thread(): void
    {
        $response = $this->actingAs($this->ownerUser)->post(route('organization.socius.threads.create'), [
            'title' => 'Cross Study Customer Satisfaction Query',
        ]);

        $thread = SurveyAiThread::latest('id')->first();
        $response->assertRedirect(route('organization.socius.threads.show', $thread->id));
        $this->assertEquals('Cross Study Customer Satisfaction Query', $thread->title);
    }

    /**
     * Test streaming endpoint retrieves org study summaries and shared KB context.
     */
    public function test_stream_org_context_streams_with_institutional_memory(): void
    {
        $thread = SurveyAiThread::create([
            'user_id' => $this->ownerUser->id,
            'title' => 'Test Thread',
        ]);

        OrgSociusContext::factory()->create([
            'organization_id' => $this->org->id,
            'content' => 'PAST STUDY: Nairobi Household Waste Baseline 2025',
        ]);

        SociusKnowledgeBase::create([
            'user_id' => $this->ownerUser->id,
            'organization_id' => $this->org->id,
            'is_org_shared' => true,
            'content' => 'Strategic Target: 80% waste reduction',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->ownerUser)->get(route('organization.socius.threads.stream', [
            'thread' => $thread->id,
            'message' => 'What was our baseline finding in Nairobi?',
        ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/event-stream', $response->headers->get('Content-Type'));
        $this->assertDatabaseHas('survey_ai_messages', [
            'thread_id' => $thread->id,
            'role' => 'user',
            'content' => 'What was our baseline finding in Nairobi?',
        ]);
    }

    /**
     * Test role authorization: field_enumerator is blocked from accessing Org Socius (403 Forbidden).
     */
    public function test_field_enumerator_cannot_access_org_socius(): void
    {
        $enumUser = User::factory()->create();
        OrgMember::factory()->fieldEnumerator()->create([
            'organization_id' => $this->org->id,
            'user_id' => $enumUser->id,
        ]);

        ActiveOrgSession::factory()->create([
            'user_id' => $enumUser->id,
            'active_organization_id' => $this->org->id,
        ]);

        $response = $this->actingAs($enumUser)->get(route('organization.socius.index'));
        $response->assertStatus(403);
    }

    /**
     * Test Tenant Isolation: User from Org A cannot access threads belonging to another user.
     */
    public function test_tenant_isolation_prevents_unauthorized_thread_access(): void
    {
        $otherUser = User::factory()->create();
        $otherThread = SurveyAiThread::create([
            'user_id' => $otherUser->id,
            'title' => 'Private Thread of Other User',
        ]);

        $response = $this->actingAs($this->ownerUser)->get(route('organization.socius.threads.show', $otherThread->id));
        $response->assertStatus(403);
    }
}
