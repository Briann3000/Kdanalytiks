<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\SubscriptionTier;
use App\Models\Survey;
use App\Models\SurveyDashboardConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyDashboardBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected $freeTier;
    protected $proTier;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed default subscription tiers
        $this->freeTier = SubscriptionTier::create([
            'name' => 'Free',
            'slug' => 'free',
            'max_surveys' => 5,
            'ai_limit_per_month' => 10,
        ]);

        $this->proTier = SubscriptionTier::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'max_surveys' => -1,
            'ai_limit_per_month' => -1,
        ]);
    }

    public function test_free_tier_user_cannot_access_dashboard_builder()
    {
        $user = User::factory()->create([
            'role' => 'organization',
            'email_verified_at' => now(),
        ]);

        // Associate user with a Free organization
        Organization::create([
            'user_id' => $user->id,
            'subscription_tier_id' => $this->freeTier->id,
            'name' => 'Free Org',
        ]);

        $survey = Survey::factory()->create([
            'created_by' => $user->id,
            'title' => 'My Survey',
        ]);

        $this->actingAs($user);

        // Access dashboard builder
        $response = $this->get(route('surveys.dashboard-builder', $survey));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Upgrade Required: The Interactive Dashboard Builder is only available on Pro & Enterprise tiers.');
    }

    public function test_pro_tier_user_can_access_dashboard_builder()
    {
        $user = User::factory()->create([
            'role' => 'organization',
            'email_verified_at' => now(),
        ]);

        // Associate user with a Pro organization
        Organization::create([
            'user_id' => $user->id,
            'subscription_tier_id' => $this->proTier->id,
            'name' => 'Pro Org',
        ]);

        $survey = Survey::factory()->create([
            'created_by' => $user->id,
            'title' => 'My Survey',
            'json_schema' => json_encode([
                ['name' => 'q1', 'type' => 'radio', 'label' => 'Q1'],
            ]),
        ]);

        $this->actingAs($user);

        $response = $this->get(route('surveys.dashboard-builder', $survey));

        $response->assertOk();
        $response->assertViewIs('surveys.dashboard_builder');
        $response->assertViewHas('survey');
        $response->assertViewHas('layout');
        $response->assertViewHas('analysis');
    }

    public function test_save_dashboard_layout_config()
    {
        $user = User::factory()->create([
            'role' => 'organization',
            'email_verified_at' => now(),
        ]);

        Organization::create([
            'user_id' => $user->id,
            'subscription_tier_id' => $this->proTier->id,
            'name' => 'Pro Org',
        ]);

        $survey = Survey::factory()->create([
            'created_by' => $user->id,
            'title' => 'My Survey',
        ]);

        $this->actingAs($user);

        $layoutData = [
            [
                'widget_id' => 'w_123',
                'question_id' => 'q1',
                'chart_type' => 'bar',
                'title' => 'Custom Q1 Title',
                'width' => 'half',
                'visible' => true,
                'config' => [
                    'show_percentages' => true,
                    'color_scheme' => 'indigo',
                ],
            ]
        ];

        $response = $this->postJson(route('surveys.dashboard-layout.save', $survey), [
            'layout' => $layoutData,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Dashboard layout saved successfully.',
        ]);

        $this->assertDatabaseHas('survey_dashboard_configs', [
            'survey_id' => $survey->id,
            'updated_by' => $user->id,
        ]);

        $config = SurveyDashboardConfig::where('survey_id', $survey->id)->first();
        $this->assertEquals($layoutData, $config->layout);
    }

    public function test_dashboard_preview_as_owner()
    {
        $user = User::factory()->create([
            'role' => 'organization',
            'email_verified_at' => now(),
        ]);

        $survey = Survey::factory()->create([
            'created_by' => $user->id,
            'title' => 'My Survey',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('surveys.dashboard-preview', $survey));

        $response->assertOk();
        $response->assertViewIs('surveys.dashboard_preview');
        $response->assertViewHas('hasToken', false);
    }

    public function test_dashboard_preview_with_valid_token_unauthenticated()
    {
        $owner = User::factory()->create([
            'role' => 'organization',
            'email_verified_at' => now(),
        ]);

        $survey = Survey::factory()->create([
            'created_by' => $owner->id,
            'title' => 'My Survey',
            'share_report_token' => 'my-secret-share-token',
        ]);

        // Unauthenticated access with token
        $response = $this->get(route('surveys.dashboard-preview', [
            'survey' => $survey,
            'token' => 'my-secret-share-token',
        ]));

        $response->assertOk();
        $response->assertViewIs('surveys.dashboard_preview');
        $response->assertViewHas('hasToken', true);
    }

    public function test_dashboard_preview_fails_without_token_unauthenticated()
    {
        $owner = User::factory()->create([
            'role' => 'organization',
            'email_verified_at' => now(),
        ]);

        $survey = Survey::factory()->create([
            'created_by' => $owner->id,
            'title' => 'My Survey',
            'share_report_token' => 'my-secret-share-token',
        ]);

        // Unauthenticated access without token
        $response = $this->get(route('surveys.dashboard-preview', $survey));

        $response->assertStatus(403);
    }
}
