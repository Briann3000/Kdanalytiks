<?php

namespace App\Services\Payments;

use App\Interfaces\PaymentGatewayInterface;
use App\Models\Organization;
use App\Models\SubscriptionTier;
use App\Models\User;
use IntaSend\IntaSendPHP\Checkout;
use IntaSend\IntaSendPHP\Transfer;
use IntaSend\IntaSendPHP\Customer;
use Illuminate\Support\Facades\Log;

class IntasendGateway implements PaymentGatewayInterface
{
    protected $publishableKey;
    protected $secretKey;
    protected $privateKey;
    protected $testMode;

    public function __construct()
    {
        $this->publishableKey = config('services.intasend.publishable_key');
        $this->secretKey = config('services.intasend.secret_key');
        $this->privateKey = config('services.intasend.private_key');
        $this->testMode = (bool) config('services.intasend.test_mode', true);
    }

    /**
     * Get credentials for IntaSend SDK.
     */
    protected function getCredentials(): array
    {
        return [
            'publishable_key' => $this->publishableKey,
            'token' => $this->secretKey,
            'private_key' => $this->privateKey,
            'test' => $this->testMode,
        ];
    }

    /**
     * Initiate a subscription purchase via IntaSend Checkout.
     */
    public function purchaseSubscription($entity, SubscriptionTier $tier, bool $isYearly = false): array
    {
        try {
            $checkout = new Checkout();
            $checkout->init($this->getCredentials());

            $amount = $isYearly ? $tier->yearly_price : $tier->monthly_price;
            $currency = 'KES';

            // Determine type for reference
            if ($entity instanceof Organization) {
                $typeCode = 'ORG';
                $user = $entity->user;
                $redirectRoute = 'organization.dashboard';
            } elseif ($entity instanceof \App\Models\Independent) {
                $typeCode = 'IND';
                $user = $entity->user;
                $redirectRoute = 'independent.dashboard';
            } else {
                $typeCode = 'RES';
                $user = $entity; // Respondent is the User object itself
                $redirectRoute = 'surveys.public';
            }
            $cycleCode = $isYearly ? 'YEAR' : 'MONTH';

            // Use a structured reference to pass context through the webhook
            $reference = "SUB-{$typeCode}-{$entity->id}-TIER-{$tier->id}-{$cycleCode}-" . strtoupper(\Illuminate\Support\Str::random(6));

            // Use customer details from the user

            // Create Customer object
            $customer = new Customer();
            $customer->email = $user->email ?? 'info@kmsurveytool.com';
            $customer->first_name = explode(' ', $user->name ?? 'Researcher')[0];
            $customer->last_name = explode(' ', $user->name ?? 'User')[1] ?? 'Admin';
            $customer->country = 'KE';


            // Correct positional arguments for IntaSend Checkout::create
            $response = $checkout->create(
                $amount,
                $currency,
                $customer,
                config('app.url'),
                route($redirectRoute),
                $reference,
                null, // comment
                null  // method
            );

            if (isset($response->url)) {
                return [
                    'status' => 'success',
                    'checkout_url' => $response->url,
                    'reference' => $reference,
                    'invoice_id' => $response->id ?? null,
                ];
            }

            throw new \Exception('Failed to generate IntaSend checkout URL');

        } catch (\Exception $e) {
            Log::error('IntaSend Purchase Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Could not initiate payment. Please try again later.',
            ];
        }
    }

    /**
     * Process a payout to a respondent via IntaSend B2C.
     */
    public function withdrawToRespondent(User $user, float $amount, string $currency, string $reference = null): array
    {
        try {
            if (!$user->phone_number) {
                throw new \Exception("User does not have a phone number configured for withdrawal.");
            }

            $transfer = new Transfer();
            $transfer->init($this->getCredentials());

            // Use mpesa method for B2C transfers
            $transactions = [
                [
                    'account' => $user->phone_number ?? '',
                    'amount' => $amount,
                    'name' => $user->name,
                ]
            ];

            // If a reference was provided (e.g. from our DB), use it
            $apiRef = $reference ?? 'WD-' . strtoupper(\Illuminate\Support\Str::random(10));

            $response = $transfer->mpesa($currency, $transactions, $apiRef);

            // Step 2: Auto-approve the payout batch 
            // This moves the status from BP103 (Preview and approve) to processing
            $approveResponse = $transfer->approve($response);

            return [
                'status' => 'success',
                'amount' => $amount,
                'currency' => $currency,
                'reference' => $approveResponse->tracking_id ?? $response->tracking_id ?? $response['tracking_id'] ?? $apiRef,
                'message' => 'Payout initiated and approved successfully via IntaSend.'
            ];

        } catch (\Exception $e) {
            // Check if it's a request error with a body
            $errorDetail = $e->getMessage();
            if (isset($e->response) && method_exists($e->response, 'getBody')) {
                $errorDetail .= " | Body: " . (string) $e->response->getBody();
            }

            Log::error('IntaSend Payout Error:', [
                'error' => $errorDetail,
                'user_id' => $user->id,
                'keys_used' => [
                    'publishable' => substr($this->publishableKey, 0, 15) . '...',
                    'test_mode' => $this->testMode
                ]
            ]);

            return [
                'status' => 'error',
                'message' => 'Payout failed: ' . $errorDetail,
            ];
        }
    }

    /**
     * Validate an incoming webhook from IntaSend.
     */
    public function validateWebhook(string $content, array $headers): bool
    {
        // more flexible case-insensitive lookup
        $signature = null;
        $availableHeaders = [];
        foreach ($headers as $key => $values) {
            $lowerKey = strtolower($key);
            $availableHeaders[] = $lowerKey;
            if ($lowerKey === 'x-intasend-signature' || strpos($lowerKey, 'intasend-signature') !== false) {
                $signature = is_array($values) ? ($values[0] ?? null) : $values;
                break;
            }
        }

        $webhookSecret = config('services.intasend.webhook_secret');

        // Check 1: Challenge in payload (Sandbox style)
        $payload = json_decode($content, true);
        if (isset($payload['challenge']) && $payload['challenge'] === $webhookSecret) {
            return true;
        }

        // Check 2: Signature Header (Live style)
        if ($signature && $webhookSecret) {
            $expectedSignature = hash_hmac('sha256', $content, $webhookSecret);
            if ($signature === $expectedSignature) {
                return true;
            }

            \Log::emergency('IntaSend Webhook Signature Mismatch!', [
                'received' => $signature,
                'expected' => $expectedSignature,
            ]);
        }

        if (!$signature && !isset($payload['challenge'])) {
            \Log::emergency('IntaSend Webhook: No signature or challenge found.', [
                'available_headers' => $availableHeaders,
                'payload_keys' => array_keys($payload ?? []),
            ]);
        }

        return false;
    }
}
