<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyVersioningTest extends TestCase
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

    public function test_updating_survey_schema_creates_version()
    {
        $user = User::factory()->create([
            'role' => 'organization',
            'email_verified_at' => now()
        ]);
        $survey = Survey::factory()->create([
            'created_by' => $user->id,
            'title' => 'Original Title',
            'description' => 'Original Description',
            'json_schema' => json_encode([['name' => 'q1', 'type' => 'text', 'label' => 'Q1']]),
        ]);

        $this->actingAs($user);

        // Put request to update survey
        $response = $this->put(route('surveys.update', $survey), [
            'title' => 'New Title',
            'description' => 'New Description',
            'category' => $survey->category->value ?? $survey->category,
            'type' => 'public',
            'json_schema' => json_encode([
                ['name' => 'q1', 'type' => 'text', 'label' => 'Q1'],
                ['name' => 'q2', 'type' => 'number', 'label' => 'Q2']
            ]),
        ]);

        $response->assertRedirect();

        // Assert survey in DB is updated
        $survey->refresh();
        $this->assertEquals('New Title', $survey->title);
        $this->assertEquals('New Description', $survey->description);

        // Assert a version is created containing the ORIGINAL values
        $this->assertDatabaseHas('survey_versions', [
            'survey_id' => $survey->id,
            'version_number' => 1,
            'title' => 'Original Title',
            'description' => 'Original Description',
            'change_summary' => 'Title updated; Description updated; Questions: 1 added',
            'changed_by' => $user->id,
        ]);
    }

    public function test_restoring_survey_version()
    {
        $user = User::factory()->create([
            'role' => 'organization',
            'email_verified_at' => now()
        ]);
        $survey = Survey::factory()->create([
            'created_by' => $user->id,
            'title' => 'Current Title',
            'description' => 'Current Description',
            'json_schema' => json_encode([['name' => 'q1', 'type' => 'text']]),
        ]);

        // Create a historical version
        $version = SurveyVersion::create([
            'survey_id' => $survey->id,
            'version_number' => 1,
            'title' => 'Historical Title',
            'description' => 'Historical Description',
            'json_schema' => json_encode([['name' => 'old_q', 'type' => 'number']]),
            'change_summary' => 'Initial',
            'changed_by' => $user->id,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('surveys.versions.restore', [$survey, $version]));

        $response->assertRedirect(route('surveys.versions', $survey));

        // Assert survey is restored to historical version
        $survey->refresh();
        $this->assertEquals('Historical Title', $survey->title);
        $this->assertEquals('Historical Description', $survey->description);
        $this->assertEquals(json_encode([['name' => 'old_q', 'type' => 'number']]), $survey->json_schema);

        // Assert a new version snapshot was captured for the pre-restore state ("Current Title" etc.)
        $this->assertDatabaseHas('survey_versions', [
            'survey_id' => $survey->id,
            'version_number' => 2,
            'title' => 'Current Title',
            'description' => 'Current Description',
            'changed_by' => $user->id,
        ]);
    }

    public function test_non_owner_cannot_access_versions()
    {
        $owner = User::factory()->create([
            'email_verified_at' => now()
        ]);
        $otherUser = User::factory()->create([
            'email_verified_at' => now()
        ]);

        $survey = Survey::factory()->create([
            'created_by' => $owner->id,
            'title' => 'Survey Title',
        ]);

        $version = SurveyVersion::create([
            'survey_id' => $survey->id,
            'version_number' => 1,
            'title' => 'V1',
            'json_schema' => '[]',
            'changed_by' => $owner->id,
        ]);

        $this->actingAs($otherUser);

        // Index
        $this->get(route('surveys.versions', $survey))->assertStatus(403);

        // Show
        $this->get(route('surveys.versions.show', [$survey, $version]))->assertStatus(403);

        // Restore
        $this->post(route('surveys.versions.restore', [$survey, $version]))->assertStatus(403);
    }

    public function test_viewing_version_details_json()
    {
        $user = User::factory()->create([
            'email_verified_at' => now()
        ]);
        $survey = Survey::factory()->create([
            'created_by' => $user->id,
            'title' => 'Survey Title',
        ]);

        $version = SurveyVersion::create([
            'survey_id' => $survey->id,
            'version_number' => 1,
            'title' => 'V1 Title',
            'description' => 'V1 Desc',
            'json_schema' => json_encode([['name' => 'q1', 'type' => 'text']]),
            'change_summary' => 'Initial creation',
            'changed_by' => $user->id,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('surveys.versions.show', [$survey, $version]));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'version' => [
                    'version_number' => 1,
                    'title' => 'V1 Title',
                    'description' => 'V1 Desc',
                    'change_summary' => 'Initial creation',
                    'json_schema' => json_encode([['name' => 'q1', 'type' => 'text']]),
                    'changed_by' => $user->name,
                ]
            ]);
    }
}
