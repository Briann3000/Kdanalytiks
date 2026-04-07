<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Interfaces\PaymentGatewayInterface;

class SubscriptionController extends Controller
{
    /**
     * Display the pricing table and available subscription tiers.
     */
    public function index()
    {
        $tiers = \App\Models\SubscriptionTier::all();
        $organization = auth()->user()->organization;

        return view('subscriptions.index', compact('tiers', 'organization'));
    }

    /**
     * Initiate a subscription purchase.
     */
    public function checkout(Request $request, \App\Services\Payments\PaymentManager $paymentManager)
    {
        $request->validate([
            'tier_id' => 'required|exists:subscription_tiers,id',
        ]);

        $tier = \App\Models\SubscriptionTier::find($request->tier_id);
        $user = auth()->user();

        try {
            // 1. Call Payment Manager Helper
            $result = $paymentManager->subscribe($user->organization, $tier);

            if ($result['status'] === 'success') {
                if (isset($result['checkout_url'])) {
                    return redirect($result['checkout_url']);
                }

                return redirect(route('organization.dashboard', [], false))->with('success', 'Subscription upgraded successfully!');
            } else {
                return back()->with('error', 'Payment failed: ' . ($result['message'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Checkout error: ' . $e->getMessage());
        }
    }

    /**
     * Handle incoming payment webhooks from IntaSend.
     */
    public function webhook(Request $request)
    {
        $gateway = app(PaymentGatewayInterface::class);
        $payload = $request->all();

        $headers = $request->headers->all();
        \Log::info('IntaSend Webhook Request INFO:', [
            'headers' => $headers,
            'content' => $request->getContent()
        ]);

        if (!$gateway->validateWebhook($request->getContent(), $headers)) {
            return response()->json(['message' => 'Invalid signature or token'], 403);
        }

        // IntaSend payload mapping
        $status = $payload['state'] ?? 'FAILED';
        $reference = $payload['api_ref'] ?? null;
        $invoiceId = $payload['invoice_id'] ?? null;
        $amount = $payload['value'] ?? 0;
        $method = $payload['provider'] ?? 'IntaSend';

        // Parse structured reference to get Org and Tier IDs
        $orgId = null;
        $tierId = null;

        if ($reference && preg_match('/SUB-ORG-(\d+)-TIER-(\d+)/', $reference, $matches)) {
            $orgId = $matches[1];
            $tierId = $matches[2];
        }

        if ($status !== 'COMPLETE' || !$orgId || !$tierId) {
            \Log::info('Webhook ignored: Status not COMPLETE or missing context.', ['payload' => $payload, 'org' => $orgId, 'tier' => $tierId]);
            return response()->json(['message' => 'Webhook received but not processed']);
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $organization = \App\Models\Organization::findOrFail($orgId);
            $tier = \App\Models\SubscriptionTier::findOrFail($tierId);

            // 1. Update Organization Tier
            $duration = $payload['duration_days'] ?? 30;
            $organization->update([
                'subscription_tier_id' => $tier->id,
                'subscription_expiry' => now()->addDays($duration),
                'ai_usage_monthly' => 0, // Reset AI usage for the new tier
                'payment_status' => 'paid',
            ]);

            // 2. Create Payment Record
            \App\Models\Payment::create([
                'organization_id' => $organization->id,
                'amount' => $amount,
                'method' => $method,
                'status' => 'success',
                'transaction_id' => $invoiceId, // IntaSend Invoice ID
            ]);

            // 3. Create Transaction Record
            \App\Models\Transaction::create([
                'wallet_id' => null, // Subscription payments are organization-level
                'organization_id' => $organization->id, // New field for better tracking
                'amount' => $amount,
                'type' => 'debit',
                'status' => 'completed',
                'reference' => 'SUB-' . strtoupper(\Illuminate\Support\Str::random(10)),
                'external_reference' => $invoiceId, // External Ref is the IntaSend Invoice ID
                'description' => "Subscription Upgrade: {$tier->name} Plan (Ref: {$invoiceId})",
                'metadata' => ['method' => $method, 'org_name' => $organization->name, 'api_ref' => $reference]
            ]);

            \Illuminate\Support\Facades\DB::commit();

            \Log::info("Subscription Webhook Success: Organization {$organization->id} upgraded to {$tier->slug}");

            return response()->json(['status' => 'success', 'message' => 'Organization upgraded']);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Log::error('Webhook Processing Error: ' . $e->getMessage(), ['payload' => $payload]);
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Cancel the current subscription and revert to the free tier.
     */
    public function cancel(Request $request)
    {
        $user = auth()->user();
        $organization = $user->organization;

        if (!$organization) {
            return back()->with('error', 'No organization linked to your account.');
        }

        $freeTier = \App\Models\SubscriptionTier::where('slug', 'free')->first();

        if (!$freeTier) {
            return back()->with('error', 'The free tier is currently unavailable.');
        }

        if ($organization->subscription_tier_id == $freeTier->id) {
            return back()->with('error', 'You are already on the Free tier.');
        }

        $organization->update([
            'subscription_tier_id' => $freeTier->id,
            'subscription_expiry' => null,
            'payment_status' => 'unpaid',
        ]);

        return back()->with('success', 'Your subscription has been cancelled. You have been reverted to the Free tier.');
    }
}
