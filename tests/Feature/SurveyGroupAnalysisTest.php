<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyGroup;
use App\Models\SurveyAiThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyGroupAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed default subscription tiers if middleware requires them
        \App\Models\SubscriptionTier::create([
            'name' => 'Free',
            'slug' => 'free',
            'max_surveys' => 5,
            'ai_limit_per_month' => 10,
        ]);
        \App\Models\SubscriptionTier::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'max_surveys' => -1,
            'ai_limit_per_month' => -1,
        ]);
    }

    public function test_owner_can_create_group()
    {
        $teacher = User::factory()->create(['role' => 'independent', 'email_verified_at' => now()]);
        $survey = Survey::factory()->create(['created_by' => $teacher->id]);

        $this->actingAs($teacher);

        $response = $this->post(route('surveys.groups.create', $survey), [
            'name' => 'Group X',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('survey_groups', [
            'survey_id' => $survey->id,
            'name' => 'Group X',
        ]);

        $group = SurveyGroup::where('survey_id', $survey->id)->first();
        $this->assertNotEmpty($group->token);
    }

    public function test_student_can_join_group_via_link()
    {
        $teacher = User::factory()->create(['role' => 'independent', 'email_verified_at' => now()]);
        $survey = Survey::factory()->create(['created_by' => $teacher->id]);
        $group = $survey->groups()->create([
            'name' => 'Group X',
            'token' => 'test-group-token-xyz',
        ]);

        $student = User::factory()->create(['role' => 'respondent', 'email_verified_at' => now()]);

        $this->actingAs($student);

        $response = $this->get(route('surveys.groups.join', [$survey, $group->token]));

        $response->assertRedirect(route('surveys.reports', $survey));
        $this->assertTrue($group->users()->where('users.id', $student->id)->exists());
    }

    public function test_unauthorized_user_cannot_view_reports_but_group_members_can()
    {
        $teacher = User::factory()->create(['role' => 'independent', 'email_verified_at' => now()]);
        $survey = Survey::factory()->create(['created_by' => $teacher->id]);
        $group = $survey->groups()->create([
            'name' => 'Group X',
            'token' => 'token-x',
        ]);

        $studentA = User::factory()->create(['role' => 'respondent', 'email_verified_at' => now()]);
        $studentB = User::factory()->create(['role' => 'respondent', 'email_verified_at' => now()]);

        // Join student A to group
        $group->users()->attach($studentA->id);

        // Student B (non-group member) tries to view reports
        $this->actingAs($studentB);
        $this->get(route('surveys.reports', $survey))->assertStatus(403);

        // Student A (group member) views reports
        $this->actingAs($studentA);
        $this->get(route('surveys.reports', $survey))->assertStatus(200);
    }

    public function test_socius_thread_isolation_between_groups()
    {
        $teacher = User::factory()->create(['role' => 'independent', 'email_verified_at' => now()]);
        $survey = Survey::factory()->create(['created_by' => $teacher->id]);

        $groupA = $survey->groups()->create(['name' => 'Group A', 'token' => 'token-a']);
        $groupB = $survey->groups()->create(['name' => 'Group B', 'token' => 'token-b']);

        $studentA = User::factory()->create(['role' => 'respondent', 'email_verified_at' => now(), 'ai_analysis_count' => 0]);
        $studentB = User::factory()->create(['role' => 'respondent', 'email_verified_at' => now(), 'ai_analysis_count' => 0]);

        $groupA->users()->attach($studentA->id);
        $groupB->users()->attach($studentB->id);

        // Student A creates a thread
        $this->actingAs($studentA);
        $response = $this->post(route('surveys.analyse.threads.store', $survey), [], ['Accept' => 'application/json']);
        $response->assertStatus(201);
        $threadIdA = $response->json('thread.id');

        $this->assertDatabaseHas('survey_ai_threads', [
            'id' => $threadIdA,
            'survey_group_id' => $groupA->id,
        ]);

        // Student B lists threads, should see empty because they have no threads in Group B
        $this->actingAs($studentB);
        $response = $this->get(route('surveys.analyse.threads.index', $survey), ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $this->assertCount(0, $response->json('threads'));

        // Student B creates a thread
        $response = $this->post(route('surveys.analyse.threads.store', $survey), [], ['Accept' => 'application/json']);
        $response->assertStatus(201);
        $threadIdB = $response->json('thread.id');

        $this->assertDatabaseHas('survey_ai_threads', [
            'id' => $threadIdB,
            'survey_group_id' => $groupB->id,
        ]);

        // Student B tries to show Student A's thread (404)
        $this->get(route('surveys.analyse.threads.show', [$survey, $threadIdA]))->assertStatus(404);

        // Teacher lists threads with group_id = groupA->id
        $this->actingAs($teacher);
        $response = $this->get(route('surveys.analyse.threads.index', [$survey, 'group_id' => $groupA->id]), ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('threads'));
        $this->assertEquals($threadIdA, $response->json('threads.0.id'));
    }
}
