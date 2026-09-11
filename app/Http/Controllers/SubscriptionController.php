<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\Independent;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\SubscriptionTier;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Payments\PayPalGateway;
use App\Services\Payments\PaymentManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    /**
     * Display the pricing table and available subscription tiers.
     */
    public function index()
    {
        /** @var User $user */
        $user = auth()->user();
        $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;
        $activeOrg = $user->activeOrganization();

        $isOrgMemberWithoutBilling = false;
        if ($activeOrg && (int) $activeOrg->user_id !== (int) $user->id) {
            $membership = $activeOrg->members()->where('user_id', $user->id)->first();
            if ($membership && !$membership->isAdmin()) {
                $isOrgMemberWithoutBilling = true;
            }
        }

        // Filter tiers strictly based on account role
        if ($role === 'respondent') {
            $tiers = SubscriptionTier::whereIn('slug', ['free', 'respondent-pro'])->get();
            $accountTypeLabel = 'Respondent (Participant & AI Suite)';
        } elseif ($role === 'organization') {
            $tiers = SubscriptionTier::whereIn('slug', ['org-free', 'org-pro', 'org-enterprise'])->get();
            $accountTypeLabel = 'Organization Team Workspace';
        } else {
            $tiers = SubscriptionTier::whereIn('slug', ['free', 'pro', 'enterprise'])->get();
            $accountTypeLabel = 'Independent Researcher ';
        }

        $entity = $this->resolveEntity();
        $subscriptionDetails = $user->getSubscriptionStatusDetails();

        return view('subscriptions.index', compact(
            'tiers',
            'entity',
            'accountTypeLabel',
            'isOrgMemberWithoutBilling',
            'subscriptionDetails',
            'activeOrg'
        ));
    }

    /**
     * Initiate a subscription purchase.
     */
    public function checkout(Request $request, PaymentManager $paymentManager)
    {
        $request->validate([
            'tier_id' => 'required|exists:subscription_tiers,id',
            'cycle' => 'required|in:monthly,yearly',
            'gateway' => 'nullable|in:intasend,paypal',
            'currency' => 'nullable|in:KES,USD',
        ]);

        $tier = SubscriptionTier::findOrFail($request->tier_id);
        $entity = $this->resolveEntity();

        if (!$entity) {
            return back()->with('error', 'Unable to resolve your researcher or respondent profile.');
        }

        $activeOrg = auth()->user()->activeOrganization();
        if ($activeOrg && (int) $activeOrg->user_id !== (int) auth()->id()) {
            $membership = $activeOrg->members()->where('user_id', auth()->id())->first();
            if ($membership && !$membership->isAdmin()) {
                return back()->with('error', 'Only organization owners and administrators can change subscription plans.');
            }
        }

        $gateway = strtolower($request->input('gateway', 'intasend'));
        $currency = strtoupper($request->input('currency', ($gateway === 'paypal' ? 'USD' : 'KES')));
        $isYearly = $request->cycle === 'yearly';

        try {
            $result = $paymentManager->subscribe($entity, $tier, $isYearly, $gateway, $currency);

            if ($result['status'] === 'success') {
                if (isset($result['checkout_url'])) {
                    return redirect($result['checkout_url']);
                }

                $roleValue = auth()->user()->role instanceof \UnitEnum ? auth()->user()->role->value : auth()->user()->role;
                $redirect = match ($roleValue) {
                    'organization' => 'organization.dashboard',
                    'independent', 'researcher' => 'independent.dashboard',
                    'respondent' => 'respondent.dashboard',
                    default => 'home'
                };
                return redirect(route($redirect, [], false))->with('success', $result['message'] ?? 'Subscription updated successfully!');
            }

            return back()->with('error', 'Payment initialization failed: ' . ($result['message'] ?? 'Please try again.'));

        } catch (\Exception $e) {
            Log::error('Subscription Checkout Error: ' . $e->getMessage());
            return back()->with('error', 'Checkout error: ' . $e->getMessage());
        }
    }

    /**
     * Handle PayPal return redirect after user approval.
     */
    public function paypalSuccess(Request $request, PayPalGateway $payPalGateway)
    {
        $orderId = $request->query('token');

        if (!$orderId) {
            return redirect()->route('subscriptions.index')->with('error', 'PayPal order token missing.');
        }

        try {
            $captureResult = $payPalGateway->captureOrder($orderId);

            if ($captureResult['status'] !== 'success') {
                return redirect()->route('subscriptions.index')->with('error', 'PayPal capture failed: ' . ($captureResult['message'] ?? 'Please contact support.'));
            }

            $reference = $captureResult['reference'];
            $transactionId = $captureResult['transaction_id'];
            $amount = $captureResult['amount'];
            $currency = $captureResult['currency'];

            $type = null;
            $entityId = null;
            $tierId = null;
            $cycle = 'MONTH';

            if ($reference && preg_match('/SUB-(ORG|IND|RES)-(\d+)-TIER-(\d+)-(MONTH|YEAR)/', $reference, $matches)) {
                $type = $matches[1];
                $entityId = $matches[2];
                $tierId = $matches[3];
                $cycle = $matches[4];
            }

            if (!$type || !$entityId || !$tierId) {
                Log::error('PayPal Success: Unable to parse custom_id from order capture', ['capture' => $captureResult]);
                return redirect()->route('subscriptions.index')->with('error', 'Payment captured but could not resolve account reference. Please contact support.');
            }

            DB::beginTransaction();

            $entity = match ($type) {
                'ORG' => Organization::findOrFail($entityId),
                'IND' => Independent::findOrFail($entityId),
                'RES' => User::findOrFail($entityId),
            };

            $tier = SubscriptionTier::findOrFail($tierId);
            $duration = ($cycle === 'YEAR') ? 365 : 30;
            $expiryDate = now()->addDays($duration);

            // 1. Upgrade entity
            $entity->update([
                'subscription_tier_id' => $tier->id,
                'subscription_expiry' => $expiryDate,
                'ai_usage_monthly' => 0,
                'payment_status' => 'paid',
            ]);

            // Synchronize associated user/org profiles
            if ($entity instanceof User) {
                if ($entity->independent) {
                    $entity->independent->update([
                        'subscription_tier_id' => $tier->id,
                        'subscription_expiry' => $expiryDate,
                        'payment_status' => 'paid',
                    ]);
                }
                if ($entity->organization) {
                    $entity->organization->update([
                        'subscription_tier_id' => $tier->id,
                        'subscription_expiry' => $expiryDate,
                        'payment_status' => 'paid',
                    ]);
                }
            } elseif (method_exists($entity, 'user') && $entity->user) {
                $entity->user->update([
                    'subscription_tier_id' => $tier->id,
                    'subscription_expiry' => $expiryDate,
                    'payment_status' => 'paid',
                ]);
            }

            // 2. Create Payment Record
            $paymentData = [
                'amount' => $amount,
                'method' => 'paypal',
                'status' => 'success',
                'transaction_id' => $transactionId,
            ];

            if ($type === 'ORG') {
                $paymentData['organization_id'] = $entity->id;
            } elseif ($type === 'IND') {
                $paymentData['independent_id'] = $entity->id;
            } else {
                $paymentData['user_id'] = $entity->id;
            }
            Payment::create($paymentData);

            // 3. Create Transaction Record
            Transaction::create([
                'wallet_id' => null,
                'organization_id' => ($type === 'ORG' ? $entity->id : null),
                'independent_id' => ($type === 'IND' ? $entity->id : null),
                'user_id' => ($type === 'RES' ? $entity->id : null),
                'amount' => $amount,
                'type' => 'debit',
                'status' => 'completed',
                'reference' => 'SUB-' . strtoupper(Str::random(10)),
                'external_reference' => $transactionId,
                'description' => "PayPal Subscription Upgrade: {$tier->name} Plan ({$currency} {$amount})",
                'metadata' => [
                    'gateway' => 'paypal',
                    'order_id' => $orderId,
                    'currency' => $currency,
                    'cycle' => $cycle,
                    'reference' => $reference,
                ]
            ]);

            DB::commit();

            $expiryFormatted = $expiryDate->format('M d, Y');
            $roleValue = auth()->user()->role instanceof \UnitEnum ? auth()->user()->role->value : auth()->user()->role;
            $redirect = match ($roleValue) {
                'organization' => 'organization.dashboard',
                'independent', 'researcher' => 'independent.dashboard',
                'respondent' => 'respondent.dashboard',
                default => 'home'
            };

            return redirect(route($redirect, [], false))->with(
                'success',
                "🎉 Payment successful! You are now on the {$tier->name} plan, active until {$expiryFormatted}."
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PayPal Success Processing Exception: ' . $e->getMessage());
            return redirect()->route('subscriptions.index')->with('error', 'Error activating subscription: ' . $e->getMessage());
        }
    }

    /**
     * Handle PayPal checkout cancellation.
     */
    public function paypalCancel(Request $request)
    {
        return redirect()->route('subscriptions.index')->with('info', 'PayPal checkout was cancelled. You have not been charged.');
    }

    /**
     * Handle incoming payment webhooks from IntaSend or PayPal.
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();
        $headers = $request->headers->all();

        Log::info('Incoming Payment Webhook:', [
            'headers' => $headers,
            'content' => $request->getContent()
        ]);

        // 1. Check if it is a PayPal Webhook event
        if (isset($payload['event_type']) || isset($payload['resource_type'])) {
            $payPalGateway = app(PayPalGateway::class);
            if (!$payPalGateway->validateWebhook($request->getContent(), $headers)) {
                return response()->json(['message' => 'Invalid PayPal webhook signature'], 403);
            }

            $eventType = $payload['event_type'] ?? '';
            if ($eventType === 'CHECKOUT.ORDER.APPROVED' || $eventType === 'PAYMENT.CAPTURE.COMPLETED') {
                Log::info("PayPal Webhook Event: {$eventType}", ['resource' => $payload['resource'] ?? []]);
            }
            return response()->json(['status' => 'success', 'message' => 'PayPal webhook processed']);
        }

        // 2. Default to IntaSend Webhook processing
        $gateway = app(PaymentGatewayInterface::class);

        if (!$gateway->validateWebhook($request->getContent(), $headers)) {
            return response()->json(['message' => 'Invalid signature or token'], 403);
        }

        $status = $payload['state'] ?? $payload['status'] ?? 'FAILED';
        $reference = $payload['api_ref'] ?? null;
        $invoiceId = $payload['invoice_id'] ?? $payload['tracking_id'] ?? $payload['file_id'] ?? null;
        $amount = $payload['value'] ?? $payload['amount'] ?? 0;
        $method = $payload['provider'] ?? 'M-Pesa/Card';

        if ($reference && str_starts_with($reference, 'WD-')) {
            return $this->handleWithdrawalWebhook($reference, $status, $payload);
        }

        if ($reference && str_starts_with($reference, 'DEP-')) {
            return $this->handleDepositWebhook($reference, $status, $payload);
        }

        $type = null;
        $entityId = null;
        $tierId = null;
        $cycle = 'MONTH';

        if ($reference && preg_match('/SUB-(ORG|IND|RES)-(\d+)-TIER-(\d+)-(MONTH|YEAR)/', $reference, $matches)) {
            $type = $matches[1];
            $entityId = $matches[2];
            $tierId = $matches[3];
            $cycle = $matches[4];
        }

        $isComplete = in_array(strtoupper($status), ['COMPLETE', 'COMPLETED']);

        if (!$isComplete || !$entityId || !$tierId || !$type) {
            Log::info('IntaSend Webhook ignored: Status not complete or missing context.', [
                'status' => $status,
                'reference' => $reference
            ]);
            return response()->json(['message' => 'Webhook received but not processed']);
        }

        try {
            DB::beginTransaction();

            $entity = match ($type) {
                'ORG' => Organization::findOrFail($entityId),
                'IND' => Independent::findOrFail($entityId),
                'RES' => User::findOrFail($entityId),
            };

            $tier = SubscriptionTier::findOrFail($tierId);
            $duration = ($cycle === 'YEAR') ? 365 : 30;
            $expiryDate = now()->addDays($duration);

            $entity->update([
                'subscription_tier_id' => $tier->id,
                'subscription_expiry' => $expiryDate,
                'ai_usage_monthly' => 0,
                'payment_status' => 'paid',
            ]);

            if ($entity instanceof User) {
                if ($entity->independent) {
                    $entity->independent->update([
                        'subscription_tier_id' => $tier->id,
                        'subscription_expiry' => $expiryDate,
                        'payment_status' => 'paid',
                    ]);
                }
                if ($entity->organization) {
                    $entity->organization->update([
                        'subscription_tier_id' => $tier->id,
                        'subscription_expiry' => $expiryDate,
                        'payment_status' => 'paid',
                    ]);
                }
            } elseif (method_exists($entity, 'user') && $entity->user) {
                $entity->user->update([
                    'subscription_tier_id' => $tier->id,
                    'subscription_expiry' => $expiryDate,
                    'payment_status' => 'paid',
                ]);
            }

            $paymentData = [
                'amount' => $amount,
                'method' => $method,
                'status' => 'success',
                'transaction_id' => $invoiceId,
            ];

            if ($type === 'ORG') {
                $paymentData['organization_id'] = $entity->id;
            } elseif ($type === 'IND') {
                $paymentData['independent_id'] = $entity->id;
            } else {
                $paymentData['user_id'] = $entity->id;
            }
            Payment::create($paymentData);

            Transaction::create([
                'wallet_id' => null,
                'organization_id' => ($type === 'ORG' ? $entity->id : null),
                'independent_id' => ($type === 'IND' ? $entity->id : null),
                'user_id' => ($type === 'RES' ? $entity->id : null),
                'amount' => $amount,
                'type' => 'debit',
                'status' => 'completed',
                'reference' => 'SUB-' . strtoupper(Str::random(10)),
                'external_reference' => $invoiceId,
                'description' => "Subscription Upgrade: {$tier->name} Plan (Ref: {$invoiceId})",
                'metadata' => [
                    'method' => $method,
                    'entity_name' => $entity->name ?? 'User',
                    'api_ref' => $reference,
                    'type' => $type
                ]
            ]);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Account upgraded']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Webhook Processing Error: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Cancel current subscription and revert to the free tier.
     */
    public function cancel(Request $request)
    {
        $entity = $this->resolveEntity();

        if (!$entity) {
            return back()->with('error', 'No active account linked to your profile.');
        }

        $activeOrg = auth()->user()->activeOrganization();
        if ($activeOrg && (int) $activeOrg->user_id !== (int) auth()->id()) {
            $membership = $activeOrg->members()->where('user_id', auth()->id())->first();
            if ($membership && !$membership->isAdmin()) {
                return back()->with('error', 'Only organization owners and administrators can cancel subscription plans.');
            }
        }

        $roleVal = auth()->user()->role instanceof \UnitEnum ? auth()->user()->role->value : auth()->user()->role;
        $freeSlug = ($roleVal === 'organization') ? 'org-free' : 'free';
        $freeTier = SubscriptionTier::where('slug', $freeSlug)->first()
            ?? SubscriptionTier::where('slug', 'free')->first();

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

        if ($entity instanceof User) {
            if ($entity->independent) {
                $entity->independent->update([
                    'subscription_tier_id' => $freeTier->id,
                    'subscription_expiry' => null,
                    'payment_status' => 'unpaid',
                ]);
            }
            if ($entity->organization) {
                $entity->organization->update([
                    'subscription_tier_id' => $freeTier->id,
                    'subscription_expiry' => null,
                    'payment_status' => 'unpaid',
                ]);
            }
        } elseif (method_exists($entity, 'user') && $entity->user) {
            $entity->user->update([
                'subscription_tier_id' => $freeTier->id,
                'subscription_expiry' => null,
                'payment_status' => 'unpaid',
            ]);
        }

        return back()->with('success', 'Your subscription has been cancelled. You have been reverted to the Free tier.');
    }

    /**
     * Handle webhook for respondent withdrawals.
     */
    protected function handleWithdrawalWebhook(string $reference, string $status, array $payload)
    {
        $transaction = Transaction::where('reference', $reference)
            ->orWhere('external_reference', $payload['tracking_id'] ?? null)
            ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $upperStatus = strtoupper($status);

        if ($upperStatus === 'COMPLETED' || $upperStatus === 'COMPLETE') {
            $transaction->update([
                'status' => 'completed',
                'external_reference' => $payload['tracking_id'] ?? $payload['file_id'] ?? null,
                'description' => $transaction->description . ' (Confirmed via Webhook)'
            ]);
        } elseif (in_array($upperStatus, ['FAILED', 'REJECTED', 'CANCELLED'])) {
            if ($transaction->status !== 'failed') {
                $wallet = $transaction->wallet;
                if ($wallet) {
                    $wallet->increment('balance', $transaction->amount);
                }
                $transaction->update([
                    'status' => 'failed',
                    'description' => $transaction->description . ' (Failed: ' . ($payload['status_description'] ?? $status) . ')'
                ]);
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Withdrawal status updated']);
    }

    /**
     * Handle webhook for wallet deposits.
     */
    protected function handleDepositWebhook(string $reference, string $status, array $payload)
    {
        $isComplete = in_array(strtoupper($status), ['COMPLETE', 'COMPLETED']);

        $transaction = Transaction::where('reference', $reference)->first();
        if (!$transaction) {
            $invoiceId = $payload['invoice_id'] ?? $payload['tracking_id'] ?? null;
            if ($invoiceId) {
                $transaction = Transaction::where('reference', $invoiceId)->first();
            }
        }

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        if ($transaction->status === 'completed') {
            return response()->json(['message' => 'Transaction already completed']);
        }

        try {
            DB::beginTransaction();

            if ($isComplete) {
                $transaction->update([
                    'status' => 'completed',
                    'external_reference' => $payload['tracking_id'] ?? $payload['invoice_id'] ?? null,
                    'description' => $transaction->description . ' (Confirmed via Webhook)'
                ]);

                $wallet = $transaction->wallet;
                if ($wallet) {
                    $wallet->increment('balance', $transaction->amount);
                }
            } else {
                $transaction->update([
                    'status' => 'failed',
                    'description' => 'Deposit failed: ' . ($payload['status_description'] ?? $status ?? 'declined')
                ]);
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Deposit status updated']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error processing webhook: ' . $e->getMessage()], 500);
        }
    }

    private function resolveEntity()
    {
        /** @var User $user */
        $user = auth()->user();
        if (!$user)
            return null;

        $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;

        if ($role === 'organization') {
            return $user->activeOrganization() ?? $user->organization;
        } elseif ($role === 'independent' || $role === 'researcher') {
            return $user->independent;
        } elseif ($role === 'respondent') {
            return $user;
        }
        return null;
    }
}
