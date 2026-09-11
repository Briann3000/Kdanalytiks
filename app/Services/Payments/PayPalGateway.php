<?php

namespace App\Services\Payments;

use App\Interfaces\PaymentGatewayInterface;
use App\Models\Organization;
use App\Models\SubscriptionTier;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PayPalGateway implements PaymentGatewayInterface
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $mode;
    protected string $baseUrl;
    protected ?string $webhookId;

    public function __construct()
    {
        $this->clientId = config('services.paypal.client_id') ?? '';
        $this->clientSecret = config('services.paypal.client_secret') ?? '';
        $this->mode = config('services.paypal.mode', 'sandbox');
        $this->webhookId = config('services.paypal.webhook_id');
        $this->baseUrl = ($this->mode === 'live')
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Get a configured HTTP client with IPv4 resolution and robust timeouts.
     */
    protected function httpClient()
    {
        return Http::timeout(60)
            ->connectTimeout(25)
            ->withOptions([
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                ],
            ]);
    }

    /**
     * Get or refresh OAuth2 Access Token from PayPal.
     */
    public function getAccessToken(): ?string
    {
        if (empty($this->clientId) || empty($this->clientSecret)) {
            Log::warning('PayPal Gateway: Client ID or Secret is not configured.');
            return null;
        }

        return Cache::remember('paypal_oauth_token_' . $this->mode, 3200, function () {
            try {
                $response = $this->httpClient()
                    ->asForm()
                    ->withBasicAuth($this->clientId, $this->clientSecret)
                    ->post("{$this->baseUrl}/v1/oauth2/token", [
                        'grant_type' => 'client_credentials',
                    ]);

                if ($response->successful()) {
                    return $response->json('access_token');
                }

                Log::error('PayPal OAuth Error:', ['status' => $response->status(), 'body' => $response->json()]);
                return null;
            } catch (\Exception $e) {
                Log::error('PayPal OAuth Exception: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Initiate a subscription purchase via PayPal REST API v2 Orders.
     */
    public function purchaseSubscription($entity, SubscriptionTier $tier, bool $isYearly = false): array
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return [
                    'status' => 'error',
                    'message' => 'PayPal integration is currently unavailable. Please check credentials or try another payment method.',
                ];
            }

            $amount = $tier->getPrice('USD', $isYearly);
            $currency = 'USD';

            // Resolve context & reference structure
            if ($entity instanceof Organization) {
                $typeCode = 'ORG';
            } elseif ($entity instanceof \App\Models\Independent) {
                $typeCode = 'IND';
            } else {
                $typeCode = 'RES';
            }
            $cycleCode = $isYearly ? 'YEAR' : 'MONTH';
            $reference = "SUB-{$typeCode}-{$entity->id}-TIER-{$tier->id}-{$cycleCode}-" . strtoupper(Str::random(6));

            $payload = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $reference,
                        'custom_id' => $reference,
                        'description' => "KD Analytiks {$tier->name} Subscription ({$cycleCode})",
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => number_format($amount, 2, '.', ''),
                        ],
                    ],
                ],
                'application_context' => [
                    'brand_name' => 'KD Analytiks',
                    'landing_page' => 'NO_PREFERENCE',
                    'user_action' => 'PAY_NOW',
                    'return_url' => route('subscriptions.paypal.success'),
                    'cancel_url' => route('subscriptions.paypal.cancel'),
                ],
            ];

            $response = $this->httpClient()
                ->withToken($token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/v2/checkout/orders", $payload);

            if ($response->successful()) {
                $data = $response->json();
                $orderId = $data['id'] ?? null;
                $approvalUrl = null;

                foreach ($data['links'] ?? [] as $link) {
                    if (($link['rel'] ?? '') === 'approve') {
                        $approvalUrl = $link['href'];
                        break;
                    }
                }

                if ($approvalUrl) {
                    return [
                        'status' => 'success',
                        'checkout_url' => $approvalUrl,
                        'reference' => $reference,
                        'order_id' => $orderId,
                    ];
                }
            }

            Log::error('PayPal Order Creation Failed:', ['response' => $response->json()]);
            return [
                'status' => 'error',
                'message' => 'Failed to initialize PayPal checkout: ' . ($response->json('message') ?? 'Unknown error'),
            ];

        } catch (\Exception $e) {
            Log::error('PayPal Purchase Exception: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'PayPal checkout error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Capture an approved PayPal order.
     */
    public function captureOrder(string $orderId): array
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return ['status' => 'error', 'message' => 'Could not authenticate with PayPal.'];
            }

            $response = $this->httpClient()
                ->withToken($token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->withBody('{}', 'application/json')
                ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");

            if ($response->successful()) {
                $data = $response->json();
                $status = $data['status'] ?? 'FAILED';
                $purchaseUnit = $data['purchase_units'][0] ?? [];
                $customId = $purchaseUnit['custom_id'] ?? ($purchaseUnit['payments']['captures'][0]['custom_id'] ?? null);
                $capture = $purchaseUnit['payments']['captures'][0] ?? [];
                $amount = $capture['amount']['value'] ?? 0;
                $currency = $capture['amount']['currency_code'] ?? 'USD';
                $transactionId = $capture['id'] ?? $orderId;

                return [
                    'status' => ($status === 'COMPLETED') ? 'success' : 'pending',
                    'order_status' => $status,
                    'reference' => $customId,
                    'transaction_id' => $transactionId,
                    'amount' => (float) $amount,
                    'currency' => $currency,
                    'raw' => $data,
                ];
            }

            Log::error('PayPal Capture Failed:', ['status' => $response->status(), 'body' => $response->json()]);
            return [
                'status' => 'error',
                'message' => 'PayPal order capture failed: ' . ($response->json('message') ?? 'Please contact support'),
            ];

        } catch (\Exception $e) {
            Log::error('PayPal Capture Exception: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error capturing PayPal order: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process a withdrawal (payout) to a respondent via PayPal Payouts.
     */
    public function withdrawToRespondent(User $user, float $amount, string $currency): array
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return ['status' => 'error', 'message' => 'Could not authenticate with PayPal Payouts.'];
            }

            $payoutEmail = $user->email;
            $senderBatchId = 'PAYOUT-' . Str::upper(Str::random(10));

            $payload = [
                'sender_batch_header' => [
                    'sender_batch_id' => $senderBatchId,
                    'email_subject' => 'Your KD Analytiks Survey Reward Payout',
                    'email_message' => 'Thank you for participating in surveys on KD Analytiks!',
                ],
                'items' => [
                    [
                        'recipient_type' => 'EMAIL',
                        'amount' => [
                            'value' => number_format($amount, 2, '.', ''),
                            'currency' => $currency,
                        ],
                        'note' => 'Respondent reward cashout',
                        'sender_item_id' => 'ITEM-' . Str::upper(Str::random(8)),
                        'receiver' => $payoutEmail,
                    ],
                ],
            ];

            $response = Http::withToken($token)->post("{$this->baseUrl}/v1/payments/payouts", $payload);

            if ($response->successful()) {
                $data = $response->json();
                $batchId = $data['batch_header']['payout_batch_id'] ?? $senderBatchId;

                return [
                    'status' => 'success',
                    'amount' => $amount,
                    'currency' => $currency,
                    'reference' => $batchId,
                    'message' => 'PayPal payout initiated successfully.',
                ];
            }

            Log::error('PayPal Payout Error:', ['body' => $response->json()]);
            return [
                'status' => 'error',
                'message' => 'Payout failed: ' . ($response->json('message') ?? 'PayPal rejected request'),
            ];

        } catch (\Exception $e) {
            Log::error('PayPal Payout Exception: ' . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Validate an incoming webhook from PayPal.
     */
    public function validateWebhook(string $content, array $headers): bool
    {
        if (empty($this->webhookId)) {
            // If webhook ID is not yet configured, allow valid json content in sandbox
            return $this->mode === 'sandbox' && !empty($content);
        }

        try {
            $token = $this->getAccessToken();
            if (!$token)
                return false;

            $authAlgo = $headers['paypal-auth-algo'][0] ?? ($headers['PAYPAL-AUTH-ALGO'][0] ?? null);
            $certUrl = $headers['paypal-cert-url'][0] ?? ($headers['PAYPAL-CERT-URL'][0] ?? null);
            $transmissionId = $headers['paypal-transmission-id'][0] ?? ($headers['PAYPAL-TRANSMISSION-ID'][0] ?? null);
            $transmissionSig = $headers['paypal-transmission-sig'][0] ?? ($headers['PAYPAL-TRANSMISSION-SIG'][0] ?? null);
            $transmissionTime = $headers['paypal-transmission-time'][0] ?? ($headers['PAYPAL-TRANSMISSION-TIME'][0] ?? null);

            if (!$transmissionSig || !$transmissionId || !$certUrl) {
                return false;
            }

            $payload = [
                'auth_algo' => $authAlgo,
                'cert_url' => $certUrl,
                'transmission_id' => $transmissionId,
                'transmission_sig' => $transmissionSig,
                'transmission_time' => $transmissionTime,
                'webhook_id' => $this->webhookId,
                'webhook_event' => json_decode($content, true),
            ];

            $response = Http::withToken($token)
                ->post("{$this->baseUrl}/v1/notifications/verify-webhook-signature", $payload);

            return $response->successful() && ($response->json('verification_status') === 'SUCCESS');
        } catch (\Exception $e) {
            Log::error('PayPal Webhook Validation Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Initiate a wallet deposit via PayPal checkout.
     */
    public function initiateDeposit(User $user, float $amount, string $currency): array
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return ['status' => 'error', 'message' => 'PayPal is currently unavailable.'];
            }

            $reference = "DEP-USR-{$user->id}-" . strtoupper(Str::random(6));

            $payload = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $reference,
                        'custom_id' => $reference,
                        'description' => 'Wallet Deposit',
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => number_format($amount, 2, '.', ''),
                        ],
                    ],
                ],
                'application_context' => [
                    'brand_name' => 'KD Analytiks',
                    'user_action' => 'PAY_NOW',
                    'return_url' => route('wallet.index'),
                    'cancel_url' => route('wallet.index'),
                ],
            ];

            $response = Http::withToken($token)->post("{$this->baseUrl}/v2/checkout/orders", $payload);

            if ($response->successful()) {
                $data = $response->json();
                foreach ($data['links'] ?? [] as $link) {
                    if (($link['rel'] ?? '') === 'approve') {
                        return [
                            'status' => 'success',
                            'checkout_url' => $link['href'],
                            'reference' => $reference,
                            'order_id' => $data['id'] ?? null,
                        ];
                    }
                }
            }

            return ['status' => 'error', 'message' => 'Could not initiate PayPal deposit.'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Check payment / order status from PayPal.
     */
    public function checkPaymentStatus(string $trackingId): array
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return ['status' => 'error', 'message' => 'Could not connect to PayPal.'];
            }

            $response = Http::withToken($token)->get("{$this->baseUrl}/v2/checkout/orders/{$trackingId}");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'status' => 'success',
                    'state' => $data['status'] ?? 'UNKNOWN',
                    'raw' => $data,
                ];
            }

            return ['status' => 'error', 'message' => 'Order not found'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
