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
    public function purchaseSubscription(Organization $organization, SubscriptionTier $tier): array
    {
        try {
            $checkout = new Checkout();
            $checkout->init($this->getCredentials());

            $amount = $tier->monthly_price;
            $currency = 'KES';
            // Use a structured reference to pass context through the webhook
            $reference = "SUB-ORG-{$organization->id}-TIER-{$tier->id}-" . strtoupper(\Illuminate\Support\Str::random(6));

            // We no longer bypass to the mock simulator. 
            // The SDK will automatically use 'sandbox.intasend.com' when $this->testMode is true.

            // Use customer details from the organization's primary user
            $user = $organization->user;

            // Create Customer object
            $customer = new Customer();
            $customer->email = $user->email ?? 'info@kmsurveytool.com';
            $customer->first_name = explode(' ', $user->name ?? 'Organization')[0];
            $customer->last_name = explode(' ', $user->name ?? 'User')[1] ?? 'Admin';
            $customer->country = 'KE';

            // Correct positional arguments for IntaSend Checkout::create
            $response = $checkout->create(
                $amount,
                $currency,
                $customer,
                config('app.url'),
                route('organization.dashboard'),
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
    public function withdrawToRespondent(User $user, float $amount, string $currency): array
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

            $response = $transfer->mpesa($currency, $transactions);

            return [
                'status' => 'success',
                'amount' => $amount,
                'currency' => $currency,
                'reference' => $response->tracking_id ?? $response['tracking_id'] ?? 'TS-' . strtoupper(\Illuminate\Support\Str::random(10)),
                'message' => 'Payout initiated successfully via IntaSend.'
            ];

        } catch (\Exception $e) {
            Log::error('IntaSend Payout Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Payout failed: ' . $e->getMessage(),
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
