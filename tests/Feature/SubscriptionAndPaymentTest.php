<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Independent;
use App\Models\Organization;
use App\Models\SubscriptionTier;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\PayPalGateway;
use App\Services\Payments\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubscriptionAndPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SubscriptionTierSeeder::class);
    }

    public function test_respondent_sees_only_respondent_and_free_tiers()
    {
        $this->seed(\Database\Seeders\SubscriptionTierSeeder::class);

        $user = User::factory()->create([
            'role' => UserRole::Respondent->value,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('subscriptions.index'));

        $response->assertStatus(200);
        $response->assertSee('Respondent Pro');
        $response->assertSee('KES 699');
        $response->assertDontSee('Org Enterprise');
    }

    public function test_independent_researcher_sees_solo_tiers()
    {
        $user = User::factory()->create([
            'role' => UserRole::Independent->value,
            'email_verified_at' => now(),
        ]);
        Independent::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'subscription_tier_id' => SubscriptionTier::where('slug', 'free')->first()->id,
        ]);

        $response = $this->actingAs($user)->get(route('subscriptions.index'));

        $response->assertStatus(200);
        $response->assertSee('Pro');
        $response->assertSee('Enterprise');
        $response->assertSee('KES 1,999');
        $response->assertDontSee('Respondent Pro');
        $response->assertDontSee('Org Enterprise');
    }

    public function test_organization_owner_sees_org_tiers()
    {
        $user = User::factory()->create([
            'role' => UserRole::Organization->value,
            'email_verified_at' => now(),
        ]);
        Organization::create([
            'user_id' => $user->id,
            'name' => 'Acme Research Org',
            'subscription_tier_id' => SubscriptionTier::where('slug', 'org-free')->first()->id,
        ]);

        $response = $this->actingAs($user)->get(route('subscriptions.index'));

        $response->assertStatus(200);
        $response->assertSee('Org Pro');
        $response->assertSee('Org Enterprise');
        $response->assertSee('KES 4,999');
        $response->assertDontSee('Respondent Pro');
    }

    public function test_switching_to_free_tier_bypasses_gateway()
    {
        $user = User::factory()->create([
            'role' => UserRole::Independent->value,
            'email_verified_at' => now(),
        ]);
        $proTier = SubscriptionTier::where('slug', 'pro')->first();
        $freeTier = SubscriptionTier::where('slug', 'free')->first();

        $independent = Independent::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'subscription_tier_id' => $proTier->id,
            'subscription_expiry' => now()->addDays(30),
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($user)->post(route('subscriptions.checkout'), [
            'tier_id' => $freeTier->id,
            'cycle' => 'monthly',
        ]);

        $response->assertRedirect(route('independent.dashboard'));
        $independent->refresh();
        $this->assertEquals($freeTier->id, $independent->subscription_tier_id);
        $this->assertNull($independent->subscription_expiry);
    }

    public function test_subscription_days_remaining_and_expiry_helpers()
    {
        $user = User::factory()->create([
            'role' => UserRole::Independent->value,
            'email_verified_at' => now(),
        ]);
        $proTier = SubscriptionTier::where('slug', 'pro')->first();

        $independent = Independent::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'subscription_tier_id' => $proTier->id,
            'subscription_expiry' => now()->addDays(10),
            'payment_status' => 'paid',
        ]);

        $this->assertTrue($user->hasActiveSubscription());
        $this->assertFalse($user->isSubscriptionExpired());
        $this->assertEquals(10, $user->subscriptionDaysRemaining());
        $this->assertFalse($user->isSubscriptionExpiringSoon(7));

        // Test expiring soon (e.g. 4 days)
        $independent->update(['subscription_expiry' => now()->addDays(4)]);
        $user->unsetRelation('independent');
        $this->assertTrue($user->isSubscriptionExpiringSoon(7));

        // Test expired
        $independent->update(['subscription_expiry' => now()->subDay()]);
        $user->unsetRelation('independent');
        $this->assertFalse($user->hasActiveSubscription());
        $this->assertTrue($user->isSubscriptionExpired());
        $this->assertEquals(0, $user->subscriptionDaysRemaining());
    }

    public function test_paypal_success_activates_subscription_and_logs_transaction()
    {
        $user = User::factory()->create([
            'role' => UserRole::Independent->value,
            'email_verified_at' => now(),
        ]);
        $proTier = SubscriptionTier::where('slug', 'pro')->first();
        $freeTier = SubscriptionTier::where('slug', 'free')->first();

        $independent = Independent::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'subscription_tier_id' => $freeTier->id,
            'payment_status' => 'unpaid',
        ]);

        // Mock PayPal Capture Response
        $mockOrderId = 'ORDER-TEST-12345';
        $mockCaptureId = 'CAPTURE-TEST-99999';
        $customRef = "SUB-IND-{$independent->id}-TIER-{$proTier->id}-YEAR-RANDOM";

        $mockGateway = $this->createMock(PayPalGateway::class);
        $mockGateway->method('captureOrder')->willReturn([
            'status' => 'success',
            'order_status' => 'COMPLETED',
            'reference' => $customRef,
            'transaction_id' => $mockCaptureId,
            'amount' => 190.00,
            'currency' => 'USD',
        ]);

        $this->app->instance(PayPalGateway::class, $mockGateway);

        $response = $this->actingAs($user)->get(route('subscriptions.paypal.success', [
            'token' => $mockOrderId,
            'PayerID' => 'PAYER-TEST',
        ]));

        $response->assertRedirect(route('independent.dashboard'));
        $independent->refresh();

        // Verify tier upgraded
        $this->assertEquals($proTier->id, $independent->subscription_tier_id);
        $this->assertEquals('paid', $independent->payment_status);
        $this->assertNotNull($independent->subscription_expiry);
        $this->assertTrue($independent->subscription_expiry->greaterThan(now()->addDays(360)));

        // Verify Payment and Transaction records
        $this->assertDatabaseHas('payments', [
            'independent_id' => $independent->id,
            'amount' => 190.00,
            'method' => 'paypal',
            'status' => 'success',
            'transaction_id' => $mockCaptureId,
        ]);

        $this->assertDatabaseHas('transactions', [
            'independent_id' => $independent->id,
            'amount' => 190.00,
            'external_reference' => $mockCaptureId,
            'status' => 'completed',
        ]);
    }

    public function test_org_member_without_admin_role_cannot_checkout_or_cancel()
    {
        $owner = User::factory()->create([
            'role' => UserRole::Organization->value,
            'email_verified_at' => now(),
        ]);
        $org = Organization::create([
            'user_id' => $owner->id,
            'name' => 'Global NGO',
            'subscription_tier_id' => SubscriptionTier::where('slug', 'org-free')->first()->id,
        ]);

        $memberUser = User::factory()->create([
            'role' => UserRole::Organization->value,
            'email_verified_at' => now(),
        ]);
        \App\Models\OrgMember::create([
            'organization_id' => $org->id,
            'user_id' => $memberUser->id,
            'org_workspace_role' => 'analyst',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $orgProTier = SubscriptionTier::where('slug', 'org-pro')->first();

        // 1. Viewing subscriptions index shows managed notice
        $response = $this->actingAs($memberUser)->get(route('subscriptions.index'));
        $response->assertStatus(200);
        $response->assertSee('Managed by Org Admin');

        // 2. Attempting checkout directly is blocked
        $checkoutResponse = $this->actingAs($memberUser)->post(route('subscriptions.checkout'), [
            'tier_id' => $orgProTier->id,
            'cycle' => 'monthly',
        ]);
        $checkoutResponse->assertSessionHas('error', 'Only organization owners and administrators can change subscription plans.');

        // 3. Attempting cancel directly is blocked
        $cancelResponse = $this->actingAs($memberUser)->post(route('subscriptions.cancel'));
        $cancelResponse->assertSessionHas('error', 'Only organization owners and administrators can cancel subscription plans.');
    }

    public function test_org_admin_member_can_checkout()
    {
        $owner = User::factory()->create([
            'role' => UserRole::Organization->value,
            'email_verified_at' => now(),
        ]);
        $org = Organization::create([
            'user_id' => $owner->id,
            'name' => 'Global NGO',
            'subscription_tier_id' => SubscriptionTier::where('slug', 'org-free')->first()->id,
        ]);

        $adminMember = User::factory()->create([
            'role' => UserRole::Organization->value,
            'email_verified_at' => now(),
        ]);
        \App\Models\OrgMember::create([
            'organization_id' => $org->id,
            'user_id' => $adminMember->id,
            'org_workspace_role' => 'admin',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $freeTier = SubscriptionTier::where('slug', 'org-free')->first();

        $response = $this->actingAs($adminMember)->post(route('subscriptions.checkout'), [
            'tier_id' => $freeTier->id,
            'cycle' => 'monthly',
        ]);

        $response->assertRedirect(route('organization.dashboard'));
    }

    public function test_paypal_success_activates_respondent_pro_subscription()
    {
        $user = User::factory()->create([
            'role' => UserRole::Respondent->value,
            'email_verified_at' => now(),
            'subscription_tier_id' => null,
            'payment_status' => 'unpaid',
        ]);
        $respondentProTier = SubscriptionTier::where('slug', 'respondent-pro')->first();

        $mockOrderId = 'ORDER-RES-98765';
        $mockCaptureId = 'CAPTURE-RES-11111';
        $customRef = "SUB-RES-{$user->id}-TIER-{$respondentProTier->id}-MONTH-TEST";

        $mockGateway = $this->createMock(PayPalGateway::class);
        $mockGateway->method('captureOrder')->willReturn([
            'status' => 'success',
            'order_status' => 'COMPLETED',
            'reference' => $customRef,
            'transaction_id' => $mockCaptureId,
            'amount' => 6.99,
            'currency' => 'USD',
        ]);

        $this->app->instance(PayPalGateway::class, $mockGateway);

        $response = $this->actingAs($user)->get(route('subscriptions.paypal.success', [
            'token' => $mockOrderId,
            'PayerID' => 'PAYER-RES',
        ]));

        $response->assertRedirect(route('respondent.dashboard'));
        $user->refresh();

        $this->assertEquals($respondentProTier->id, $user->subscription_tier_id);
        $this->assertEquals('paid', $user->payment_status);
        $this->assertNotNull($user->subscription_expiry);
        $this->assertTrue($user->hasActiveSubscription());

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'amount' => 6.99,
            'method' => 'paypal',
            'status' => 'success',
        ]);
    }

    public function test_paypal_success_activates_organization_pro_and_members_inherit_privileges()
    {
        $owner = User::factory()->create([
            'role' => UserRole::Organization->value,
            'email_verified_at' => now(),
        ]);
        $org = Organization::create([
            'user_id' => $owner->id,
            'name' => 'Data Analytics Org',
            'subscription_tier_id' => SubscriptionTier::where('slug', 'org-free')->first()->id,
            'payment_status' => 'unpaid',
        ]);

        $member = User::factory()->create([
            'role' => UserRole::Organization->value,
            'email_verified_at' => now(),
        ]);
        \App\Models\OrgMember::create([
            'organization_id' => $org->id,
            'user_id' => $member->id,
            'org_workspace_role' => 'lead_researcher',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $orgProTier = SubscriptionTier::where('slug', 'org-pro')->first();

        $mockOrderId = 'ORDER-ORG-55555';
        $mockCaptureId = 'CAPTURE-ORG-88888';
        $customRef = "SUB-ORG-{$org->id}-TIER-{$orgProTier->id}-YEAR-TEST";

        $mockGateway = $this->createMock(PayPalGateway::class);
        $mockGateway->method('captureOrder')->willReturn([
            'status' => 'success',
            'order_status' => 'COMPLETED',
            'reference' => $customRef,
            'transaction_id' => $mockCaptureId,
            'amount' => 490.00,
            'currency' => 'USD',
        ]);

        $this->app->instance(PayPalGateway::class, $mockGateway);

        $response = $this->actingAs($owner)->get(route('subscriptions.paypal.success', [
            'token' => $mockOrderId,
            'PayerID' => 'PAYER-ORG',
        ]));

        $response->assertRedirect(route('organization.dashboard'));
        $org->refresh();

        $this->assertEquals($orgProTier->id, $org->subscription_tier_id);
        $this->assertEquals('paid', $org->payment_status);
        $this->assertNotNull($org->subscription_expiry);

        // Verify organization owner and member inherit active subscription
        $this->assertTrue($owner->hasActiveSubscription());
        $this->assertTrue($member->hasActiveSubscription());
        $this->assertEquals($org->subscriptionDaysRemaining(), $member->subscriptionDaysRemaining());
    }

    public function test_tier_price_helpers_and_formatting()
    {
        $proTier = SubscriptionTier::where('slug', 'pro')->first();
        $respPro = SubscriptionTier::where('slug', 'respondent-pro')->first();

        $this->assertEquals(1999, $proTier->getPrice('KES', false));
        $this->assertEquals(19990, $proTier->getPrice('KES', true));
        $this->assertEquals(19, $proTier->getPrice('USD', false));
        $this->assertEquals(190, $proTier->getPrice('USD', true));

        $this->assertEquals('KES 1,999', $proTier->formattedPrice('KES', false));
        $this->assertEquals('$19.00', $proTier->formattedPrice('USD', false));
        $this->assertEquals('KES 699', $respPro->formattedPrice('KES', false));
        $this->assertEquals('$6.99', $respPro->formattedPrice('USD', false));
    }

    public function test_socius_chat_returns_403_paylock_when_subscription_is_expired()
    {
        $user = User::factory()->create([
            'role' => UserRole::Independent->value,
            'email_verified_at' => now(),
            'ai_analysis_count' => 2,
        ]);
        $proTier = SubscriptionTier::where('slug', 'pro')->first();

        Independent::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'subscription_tier_id' => $proTier->id,
            'subscription_expiry' => now()->subDay(), // Expired
            'payment_status' => 'paid',
        ]);

        $survey = \App\Models\Survey::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('surveys.analyse.threads.store', $survey->id), [
            'title' => 'Test Thread',
        ]);

        $response->assertStatus(403);
        $response->assertJsonStructure([
            'error',
            'message',
            'status_details',
            'upgrade_url'
        ]);
        $response->assertJson([
            'error' => 'subscription_required',
        ]);
    }

    public function test_paypal_webhook_handles_payment_capture_completed()
    {
        $mockGateway = $this->createMock(PayPalGateway::class);
        $mockGateway->method('validateWebhook')->willReturn(true);
        $this->app->instance(PayPalGateway::class, $mockGateway);

        $payload = [
            'id' => 'WH-EVENT-12345',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource_type' => 'capture',
            'resource' => [
                'id' => 'CAPTURE-999',
                'status' => 'COMPLETED',
                'amount' => [
                    'value' => '49.00',
                    'currency_code' => 'USD',
                ],
            ],
        ];

        $response = $this->postJson(route('webhook.paypal'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'PayPal webhook processed',
        ]);
    }

    public function test_cancel_subscription_reverts_to_free()
    {
        $user = User::factory()->create([
            'role' => UserRole::Independent->value,
            'email_verified_at' => now(),
        ]);
        $proTier = SubscriptionTier::where('slug', 'pro')->first();
        $freeTier = SubscriptionTier::where('slug', 'free')->first();

        $independent = Independent::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'subscription_tier_id' => $proTier->id,
            'subscription_expiry' => now()->addDays(20),
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($user)->post(route('subscriptions.cancel'));

        $response->assertRedirect();
        $independent->refresh();
        $this->assertEquals($freeTier->id, $independent->subscription_tier_id);
        $this->assertNull($independent->subscription_expiry);
        $this->assertEquals('unpaid', $independent->payment_status);
    }

    public function test_intasend_callback_synchronously_activates_subscription()
    {
        $user = User::factory()->create([
            'role' => UserRole::Independent->value,
            'email_verified_at' => now(),
        ]);
        $enterpriseTier = SubscriptionTier::where('slug', 'enterprise')->first();
        $freeTier = SubscriptionTier::where('slug', 'free')->first();

        $independent = Independent::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'subscription_tier_id' => $freeTier->id,
            'payment_status' => 'unpaid',
        ]);

        $mockTrackingId = 'INVOICE-TEST-INTASEND-123';
        $customRef = "SUB-IND-{$independent->id}-TIER-{$enterpriseTier->id}-MONTH-RANDOM";

        $mockGateway = $this->createMock(\App\Services\Payments\IntasendGateway::class);
        $mockGateway->method('checkPaymentStatus')->willReturn([
            'status' => 'success',
            'state' => 'COMPLETE',
            'api_ref' => $customRef,
            'invoice_id' => $mockTrackingId,
            'amount' => 4900.00,
        ]);

        $this->app->instance(\App\Services\Payments\IntasendGateway::class, $mockGateway);

        $response = $this->actingAs($user)->get(route('subscriptions.intasend.callback', [
            'tracking_id' => $mockTrackingId,
            'signature' => 'mock-sig',
            'checkout_id' => 'chk-123',
            'api_ref' => $customRef,
        ]));

        $response->assertRedirect(route('independent.dashboard'));
        $independent->refresh();

        $this->assertEquals($enterpriseTier->id, $independent->subscription_tier_id);
        $this->assertEquals('paid', $independent->payment_status);
        $this->assertNotNull($independent->subscription_expiry);

        $this->assertDatabaseHas('payments', [
            'independent_id' => $independent->id,
            'amount' => 4900.00,
            'status' => 'success',
            'transaction_id' => $mockTrackingId,
        ]);
    }

    public function test_payment_method_normalization_and_enum_persistence()
    {
        $user = User::factory()->create();

        // Storing with "M-Pesa/Card" or "IntaSend" should not crash and normalize to intasend
        $payment = \App\Models\Payment::create([
            'user_id' => $user->id,
            'amount' => 1500.00,
            'method' => 'M-Pesa/Card',
            'status' => 'success',
            'transaction_id' => 'TXN-NORMALIZE-1',
        ]);

        $this->assertEquals(\App\Enums\PaymentMethod::IntaSend, $payment->method);
        $this->assertEquals('intasend', $payment->getRawOriginal('method'));

        $payment2 = \App\Models\Payment::create([
            'user_id' => $user->id,
            'amount' => 25.00,
            'method' => 'PayPal',
            'status' => 'success',
            'transaction_id' => 'TXN-NORMALIZE-2',
        ]);

        $this->assertEquals(\App\Enums\PaymentMethod::PayPal, $payment2->method);
        $this->assertEquals('paypal', $payment2->getRawOriginal('method'));
    }

    public function test_intasend_webhook_challenge_returns_200_ok()
    {
        $mockGateway = $this->createMock(\App\Interfaces\PaymentGatewayInterface::class);
        $mockGateway->method('validateWebhook')->willReturn(true);
        $this->app->instance(\App\Interfaces\PaymentGatewayInterface::class, $mockGateway);

        $response = $this->postJson(route('webhook.payment'), [
            'challenge' => 'INTASEND_CHALLENGE_CODE_123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'challenge' => 'INTASEND_CHALLENGE_CODE_123',
        ]);
    }
}
