<?php

namespace Tests\Feature\Org;

use App\Models\ActiveOrgSession;
use App\Models\Organization;
use App\Models\OrgMember;
use App\Models\OrgResourcePool;
use App\Models\SubscriptionTier;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Org Resource Pool & Subscription Integration Feature Tests
 *
 * Validates pool usage endpoint, AiService integration with OrgResourcePool counters,
 * subscription tier limit propagation, and role authorization.
 */
class OrgResourcePoolIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $ownerUser;

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

        OrgResourcePool::factory()->create([
            'organization_id' => $this->org->id,
            'ai_analyses_limit' => 10,
            'ai_analyses_used' => 2,
        ]);
    }

    /**
     * Test /organization/resources/usage endpoint returns pool metrics.
     */
    public function test_resource_usage_endpoint_returns_metrics(): void
    {
        $response = $this->actingAs($this->ownerUser)->get(route('organization.resources.usage'));

        $response->assertStatus(200);
        $response->assertJson([
            'organization_id' => $this->org->id,
            'organization_name' => $this->org->name,
            'ai_analyses' => [
                'limit' => 10,
                'used' => 2,
                'can_use' => true,
            ],
        ]);
    }

    /**
     * Test AiService evaluates OrgResourcePool instead of individual user counters for org members.
     */
    public function test_ai_service_uses_org_resource_pool(): void
    {
        $aiService = new AiService();

        // Under limit
        $this->assertTrue($aiService->checkUsageLimit($this->ownerUser));

        // Increment usage
        $aiService->incrementUsage($this->ownerUser);
        $this->assertDatabaseHas('organization_resource_pools', [
            'organization_id' => $this->org->id,
            'ai_analyses_used' => 3,
        ]);

        // Max out limit and test block
        $this->org->resourcePool->update(['ai_analyses_used' => 10]);
        $this->assertFalse($aiService->checkUsageLimit($this->ownerUser));
    }

    /**
     * Test subscription tier org columns propagate to resource pool limits.
     */
    public function test_subscription_tier_updates_resource_pool_limits(): void
    {
        $tier = SubscriptionTier::create([
            'name' => 'Enterprise Org Plan',
            'slug' => 'enterprise-org',
            'monthly_price' => 199.00,
            'yearly_price' => 1990.00,
            'currency' => 'USD',
            'max_surveys' => -1,
            'org_max_seats' => 50,
            'org_ai_analyses_per_month' => 500,
            'org_transcription_minutes_per_month' => 300,
            'org_socius_sessions_per_month' => 1000,
            'org_report_exports_per_month' => -1,
        ]);

        $this->org->update(['subscription_tier_id' => $tier->id, 'max_seats' => $tier->org_max_seats]);
        $this->org->resourcePool->update([
            'ai_analyses_limit' => $tier->org_ai_analyses_per_month,
            'transcription_minutes_limit' => $tier->org_transcription_minutes_per_month,
            'socius_chat_sessions_limit' => $tier->org_socius_sessions_per_month,
            'report_exports_limit' => $tier->org_report_exports_per_month,
        ]);

        $response = $this->actingAs($this->ownerUser)->get(route('organization.resources.usage'));

        $response->assertStatus(200);
        $response->assertJson([
            'max_seats' => 50,
            'ai_analyses' => [
                'limit' => 500,
            ],
            'transcription' => [
                'limit' => 300,
            ],
        ]);
    }

    /**
     * Test non-admin roles (e.g. analyst) cannot access /organization/resources/usage.
     */
    public function test_non_admin_roles_cannot_access_resource_usage(): void
    {
        $analystUser = User::factory()->create();
        OrgMember::factory()->analyst()->create([
            'organization_id' => $this->org->id,
            'user_id' => $analystUser->id,
        ]);

        ActiveOrgSession::factory()->create([
            'user_id' => $analystUser->id,
            'active_organization_id' => $this->org->id,
        ]);

        $response = $this->actingAs($analystUser)->get(route('organization.resources.usage'));
        $response->assertStatus(403);
    }
}
