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
        $entity = $this->resolveEntity();

        return view('subscriptions.index', compact('tiers', 'entity'));
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
        $entity = $this->resolveEntity();

        if (!$entity) {
            return back()->with('error', 'Unable to resolve your researcher identity.');
        }

        try {
            // 1. Call Payment Manager Helper
            $result = $paymentManager->subscribe($entity, $tier);

            if ($result['status'] === 'success') {
                if (isset($result['checkout_url'])) {
                    return redirect($result['checkout_url']);
                }

                $redirect = (auth()->user()->role->value === 'organization') ? 'organization.dashboard' : 'independent.dashboard';
                return redirect(route($redirect, [], false))->with('success', 'Subscription upgraded successfully!');
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

        // Parse structured reference to get Entity Type, ID and Tier ID
        $type = null; // 'ORG' or 'IND'
        $entityId = null;
        $tierId = null;

        if ($reference && preg_match('/SUB-(ORG|IND)-(\d+)-TIER-(\d+)/', $reference, $matches)) {
            $type = $matches[1];
            $entityId = $matches[2];
            $tierId = $matches[3];
        }

        if ($status !== 'COMPLETE' || !$entityId || !$tierId || !$type) {
            \Log::info('Webhook ignored: Status not COMPLETE or missing context.', ['payload' => $payload, 'entityId' => $entityId, 'tier' => $tierId]);
            return response()->json(['message' => 'Webhook received but not processed']);
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $entity = ($type === 'ORG') 
                ? \App\Models\Organization::findOrFail($entityId)
                : \App\Models\Independent::findOrFail($entityId);

            $tier = \App\Models\SubscriptionTier::findOrFail($tierId);

            // 1. Update Entity Tier
            $duration = $payload['duration_days'] ?? 30;
            $entity->update([
                'subscription_tier_id' => $tier->id,
                'subscription_expiry' => now()->addDays($duration),
                'ai_usage_monthly' => 0, // Reset AI usage for the new tier
                'payment_status' => 'paid',
            ]);

            // 2. Create Payment Record
            $paymentData = [
                'amount' => $amount,
                'method' => $method,
                'status' => 'success',
                'transaction_id' => $invoiceId, // IntaSend Invoice ID
            ];
            
            if ($type === 'ORG') {
                $paymentData['organization_id'] = $entity->id;
            } else {
                $paymentData['independent_id'] = $entity->id;
            }
            \App\Models\Payment::create($paymentData);

            // 3. Create Transaction Record
            \App\Models\Transaction::create([
                'wallet_id' => null, 
                'organization_id' => ($type === 'ORG' ? $entity->id : null),
                'amount' => $amount,
                'type' => 'debit',
                'status' => 'completed',
                'reference' => 'SUB-' . strtoupper(\Illuminate\Support\Str::random(10)),
                'external_reference' => $invoiceId, 
                'description' => "Subscription Upgrade: {$tier->name} Plan (Ref: {$invoiceId})",
                'metadata' => [
                    'method' => $method, 
                    'entity_name' => $entity->name, 
                    'api_ref' => $reference,
                    'type' => $type
                ]
            ]);

            \Illuminate\Support\Facades\DB::commit();

            \Log::info("Subscription Webhook Success: {$type} {$entity->id} upgraded to {$tier->slug}");

            return response()->json(['status' => 'success', 'message' => 'Account upgraded']);

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
        $entity = $this->resolveEntity();

        if (!$entity) {
            return back()->with('error', 'No researcher account linked to your profile.');
        }

        $freeTier = \App\Models\SubscriptionTier::where('slug', 'free')->first();

        if (!$freeTier) {
            return back()->with('error', 'The free tier is currently unavailable.');
        }

        if ($entity->subscription_tier_id == $freeTier->id) {
            return back()->with('error', 'You are already on the Free tier.');
        }

        $entity->update([
            'subscription_tier_id' => $freeTier->id,
            'subscription_expiry' => null,
            'payment_status' => 'unpaid',
        ]);

        return back()->with('success', 'Your subscription has been cancelled. You have been reverted to the Free tier.');
    }

    private function resolveEntity()
    {
        $user = auth()->user();
        if ($user->role === \App\Enums\UserRole::Organization || $user->role->value === 'organization') {
            return $user->organization;
        } elseif ($user->role === \App\Enums\UserRole::Independent || $user->role->value === 'independent') {
            return $user->independent;
        }
        return null;
    }
}
