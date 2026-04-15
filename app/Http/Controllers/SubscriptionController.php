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
        $user = auth()->user();
        $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;

        // Filter tiers based on role
        if ($role === 'respondent') {
            $tiers = \App\Models\SubscriptionTier::whereIn('slug', ['free', 'respondent-pro'])->get();
            $accountTypeLabel = 'Respondent (Reader)';
        } else {
            $tiers = \App\Models\SubscriptionTier::whereNotIn('slug', ['respondent-pro'])->get();
            $accountTypeLabel = ($role === 'organization') ? 'Organization' : 'Independent Researcher';
        }

        $entity = $this->resolveEntity();

        return view('subscriptions.index', compact('tiers', 'entity', 'accountTypeLabel'));
    }

    /**
     * Initiate a subscription purchase.
     */
    public function checkout(Request $request, \App\Services\Payments\PaymentManager $paymentManager)
    {
        $request->validate([
            'tier_id' => 'required|exists:subscription_tiers,id',
            'cycle' => 'required|in:monthly,yearly',
        ]);

        $tier = \App\Models\SubscriptionTier::find($request->tier_id);
        $entity = $this->resolveEntity();

        if (!$entity) {
            return back()->with('error', 'Unable to resolve your researcher identity.');
        }

        try {
            // Determine price and pass as metadata/reference component
            $isYearly = $request->cycle === 'yearly';

            // 1. Call Payment Manager Helper
            $result = $paymentManager->subscribe($entity, $tier, $isYearly);

            if ($result['status'] === 'success') {
                if (isset($result['checkout_url'])) {
                    return redirect($result['checkout_url']);
                }

                $roleValue = auth()->user()->role instanceof \UnitEnum ? auth()->user()->role->value : auth()->user()->role;
                $redirect = match ($roleValue) {
                    'organization' => 'organization.dashboard',
                    'independent', 'researcher' => 'independent.dashboard',
                    'respondent' => 'surveys.public',
                    default => 'home'
                };
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

        $type = null; // 'ORG', 'IND', or 'RES'
        $entityId = null;
        $tierId = null;
        $cycle = 'MONTH'; // Default to month if not found

        if ($reference && preg_match('/SUB-(ORG|IND|RES)-(\d+)-TIER-(\d+)-(MONTH|YEAR)/', $reference, $matches)) {
            $type = $matches[1];
            $entityId = $matches[2];
            $tierId = $matches[3];
            $cycle = $matches[4];
        }

        if ($status !== 'COMPLETE' || !$entityId || !$tierId || !$type) {
            \Log::info('Webhook ignored: Status not COMPLETE or missing context.', ['payload' => $payload, 'entityId' => $entityId, 'tier' => $tierId]);
            return response()->json(['message' => 'Webhook received but not processed']);
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $entity = match ($type) {
                'ORG' => \App\Models\Organization::findOrFail($entityId),
                'IND' => \App\Models\Independent::findOrFail($entityId),
                'RES' => \App\Models\User::findOrFail($entityId),
            };

            $tier = \App\Models\SubscriptionTier::findOrFail($tierId);

            // 1. Update Entity Tier
            $duration = ($cycle === 'YEAR') ? 365 : 30;
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
            } elseif ($type === 'IND') {
                $paymentData['independent_id'] = $entity->id;
            } else {
                $paymentData['user_id'] = $entity->id; // For Respondents
            }
            \App\Models\Payment::create($paymentData);

            // 3. Create Transaction Record
            \App\Models\Transaction::create([
                'wallet_id' => null,
                'organization_id' => ($type === 'ORG' ? $entity->id : null),
                'independent_id' => ($type === 'IND' ? $entity->id : null),
                'user_id' => ($type === 'RES' ? $entity->id : null),
                'amount' => $amount,
                'type' => 'debit',
                'status' => 'completed',
                'reference' => 'SUB-' . strtoupper(\Illuminate\Support\Str::random(10)),
                'external_reference' => $invoiceId,
                'description' => "Subscription Upgrade: {$tier->name} Plan (Ref: {$invoiceId})",
                'metadata' => [
                    'method' => $method,
                    'entity_name' => $entity->name ?? 'Researcher',
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
        /** @var \App\Models\User $user */
        $user = auth()->user();
        if (!$user)
            return null;

        $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;

        if ($role === 'organization') {
            return $user->organization;
        } elseif ($role === 'independent' || $role === 'researcher') {
            return $user->independent;
        } elseif ($role === 'respondent') {
            return $user;
        }
        return null;
    }
}
