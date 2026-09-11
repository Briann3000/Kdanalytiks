<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Independent;
use App\Models\Organization;
use App\Models\SubscriptionTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubscriptionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SubscriptionTierSeeder::class);
    }

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin->value,
            'email_verified_at' => now(),
        ]);
    }

    public function test_admin_can_view_users_list_with_subscriptions()
    {
        $admin = $this->createAdminUser();

        $proTier = SubscriptionTier::where('slug', 'pro')->first();
        $user = User::factory()->create([
            'role' => UserRole::Independent->value,
            'email_verified_at' => now(),
        ]);
        Independent::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'subscription_tier_id' => $proTier->id,
            'subscription_expires_at' => now()->addDays(25),
            'subscription_payment_status' => 'paid',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee($user->email);
        $response->assertSee('Pro');
        $response->assertSee('Plan');
    }

    public function test_admin_can_filter_users_by_subscription_tier()
    {
        $admin = $this->createAdminUser();

        $proTier = SubscriptionTier::where('slug', 'pro')->first();
        $freeTier = SubscriptionTier::where('slug', 'free')->first();

        $proUser = User::factory()->create([
            'role' => UserRole::Independent->value,
            'email' => 'pro_researcher@example.com',
            'email_verified_at' => now(),
        ]);
        Independent::create([
            'user_id' => $proUser->id,
            'name' => $proUser->name,
            'subscription_tier_id' => $proTier->id,
        ]);

        $freeUser = User::factory()->create([
            'role' => UserRole::Independent->value,
            'email' => 'free_researcher@example.com',
            'email_verified_at' => now(),
        ]);
        Independent::create([
            'user_id' => $freeUser->id,
            'name' => $freeUser->name,
            'subscription_tier_id' => $freeTier->id,
        ]);

        // Filter by pro tier
        $response = $this->actingAs($admin)->get(route('admin.users.index', ['tier' => $proTier->id]));
        $response->assertStatus(200);
        $response->assertSee('pro_researcher@example.com');
        $response->assertDontSee('free_researcher@example.com');
    }

    public function test_admin_can_override_user_subscription_tier_and_duration()
    {
        $admin = $this->createAdminUser();

        $freeTier = SubscriptionTier::where('slug', 'free')->first();
        $enterpriseTier = SubscriptionTier::where('slug', 'enterprise')->first();

        $user = User::factory()->create([
            'role' => UserRole::Independent->value,
            'email_verified_at' => now(),
        ]);
        $independent = Independent::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'subscription_tier_id' => $freeTier->id,
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.subscription', $user), [
            'subscription_tier_id' => $enterpriseTier->id,
            'duration_preset' => '+90d',
            'payment_status' => 'paid',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $independent->refresh();
        $this->assertEquals($enterpriseTier->id, $independent->subscription_tier_id);
        $this->assertEquals('paid', $independent->payment_status);
        $this->assertNotNull($independent->subscription_expiry);
        $this->assertTrue($independent->subscription_expiry->isFuture());
        $this->assertEquals(now()->addDays(90)->toDateString(), $independent->subscription_expiry->toDateString());
    }

    public function test_admin_can_set_lifetime_and_custom_expiry()
    {
        $admin = $this->createAdminUser();
        $proTier = SubscriptionTier::where('slug', 'pro')->first();

        $user = User::factory()->create([
            'role' => UserRole::Independent->value,
            'email_verified_at' => now(),
        ]);
        $independent = Independent::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'subscription_tier_id' => $proTier->id,
        ]);

        // 1. Lifetime (never expires)
        $response = $this->actingAs($admin)->post(route('admin.users.subscription', $user), [
            'subscription_tier_id' => $proTier->id,
            'duration_preset' => 'never',
            'payment_status' => 'paid',
        ]);
        $response->assertRedirect();
        $independent->refresh();
        $this->assertNull($independent->subscription_expiry);

        // 2. Custom date
        $customDate = now()->addDays(45)->toDateString();
        $response = $this->actingAs($admin)->post(route('admin.users.subscription', $user), [
            'subscription_tier_id' => $proTier->id,
            'duration_preset' => 'custom',
            'custom_expires_at' => $customDate,
        ]);
        $response->assertRedirect();
        $independent->refresh();
        $this->assertEquals($customDate, $independent->subscription_expiry->toDateString());
        $this->assertEquals('paid', $independent->payment_status);
    }

    public function test_admin_can_reset_user_quota_counters()
    {
        $admin = $this->createAdminUser();
        $proTier = SubscriptionTier::where('slug', 'pro')->first();

        $user = User::factory()->create([
            'role' => UserRole::Independent->value,
            'email_verified_at' => now(),
            'transcription_count' => 15,
            'proofread_count' => 8,
        ]);
        $independent = Independent::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'subscription_tier_id' => $proTier->id,
            'ai_usage_monthly' => 450,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.subscription', $user), [
            'subscription_tier_id' => $proTier->id,
            'duration_preset' => 'keep',
            'payment_status' => 'paid',
            'reset_counters' => '1',
        ]);

        $response->assertRedirect();
        $user->refresh();
        $independent->refresh();

        $this->assertEquals(0, $user->transcription_count);
        $this->assertEquals(0, $user->proofread_count);
        $this->assertEquals(0, $independent->ai_usage_monthly);
    }

    public function test_admin_can_manage_respondent_and_organization_subscriptions()
    {
        $admin = $this->createAdminUser();

        // 1. Respondent
        $respTier = SubscriptionTier::where('slug', 'respondent-pro')->first();
        $respUser = User::factory()->create([
            'role' => UserRole::Respondent->value,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('admin.users.subscription', $respUser), [
            'subscription_tier_id' => $respTier->id,
            'duration_preset' => '+30d',
            'payment_status' => 'paid',
        ]);

        $respUser->refresh();
        $this->assertEquals($respTier->id, $respUser->subscription_tier_id);
        $this->assertEquals('paid', $respUser->payment_status);
        $this->assertTrue($respUser->subscription_expiry->isFuture());

        // 2. Organization
        $orgTier = SubscriptionTier::where('slug', 'org-enterprise')->first();
        $orgUser = User::factory()->create([
            'role' => UserRole::Organization->value,
            'email_verified_at' => now(),
        ]);
        $org = Organization::create([
            'user_id' => $orgUser->id,
            'name' => 'Acme Global Research',
        ]);

        $this->actingAs($admin)->post(route('admin.users.subscription', $orgUser), [
            'subscription_tier_id' => $orgTier->id,
            'duration_preset' => '+365d',
            'payment_status' => 'paid',
        ]);

        $org->refresh();
        $this->assertEquals($orgTier->id, $org->subscription_tier_id);
        $this->assertEquals('paid', $org->payment_status);
        $this->assertEquals(now()->addDays(365)->toDateString(), $org->subscription_expiry->toDateString());
    }

    public function test_non_admin_cannot_update_subscriptions()
    {
        $nonAdmin = User::factory()->create([
            'role' => UserRole::Independent->value,
            'email_verified_at' => now(),
        ]);
        $targetUser = User::factory()->create([
            'role' => UserRole::Independent->value,
            'email_verified_at' => now(),
        ]);
        $proTier = SubscriptionTier::where('slug', 'pro')->first();

        $response = $this->actingAs($nonAdmin)->post(route('admin.users.subscription', $targetUser), [
            'subscription_tier_id' => $proTier->id,
            'duration_preset' => '+30d',
            'payment_status' => 'paid',
        ]);

        // Protected by role:admin middleware
        $response->assertForbidden();
    }
}

