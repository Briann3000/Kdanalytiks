<?php

namespace App\Services\Payments;

use App\Interfaces\PaymentGatewayInterface;
use App\Models\Organization;
use App\Models\SubscriptionTier;
use App\Models\User;

class PaymentManager
{
    protected array $drivers = [];
    protected ?PaymentGatewayInterface $defaultGateway;

    public function __construct(PaymentGatewayInterface $defaultGateway)
    {
        $this->defaultGateway = $defaultGateway;
        $this->drivers['intasend'] = $defaultGateway;
    }

    /**
     * Get a gateway driver by name ('intasend' or 'paypal').
     */
    public function driver(?string $name = null): PaymentGatewayInterface
    {
        $name = strtolower($name ?? 'intasend');

        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = match ($name) {
                'paypal' => app(PayPalGateway::class),
                'intasend' => app(IntasendGateway::class),
                default => $this->defaultGateway ?? app(IntasendGateway::class),
            };
        }

        return $this->drivers[$name];
    }

    /**
     * Get the default gateway.
     */
    public function gateway(): PaymentGatewayInterface
    {
        return $this->driver();
    }

    /**
     * Helper to process a subscription.
     */
    public function subscribe(
        $entity,
        SubscriptionTier $tier,
        bool $isYearly = false,
        string $gateway = 'intasend',
        string $currency = 'KES'
    ): array {
        $amount = $tier->getPrice($currency, $isYearly);

        // Bypass gateway for free tiers
        if ($amount <= 0 || str_contains(strtolower($tier->slug), 'free')) {
            $entity->update([
                'subscription_tier_id' => $tier->id,
                'subscription_expiry' => null,
                'payment_status' => 'paid',
            ]);

            if ($entity instanceof \App\Models\User) {
                if ($entity->independent) {
                    $entity->independent->update([
                        'subscription_tier_id' => $tier->id,
                        'subscription_expiry' => null,
                        'payment_status' => 'paid',
                    ]);
                }
                if ($entity->organization) {
                    $entity->organization->update([
                        'subscription_tier_id' => $tier->id,
                        'subscription_expiry' => null,
                        'payment_status' => 'paid',
                    ]);
                }
            } elseif (method_exists($entity, 'user') && $entity->user) {
                $entity->user->update([
                    'subscription_tier_id' => $tier->id,
                    'subscription_expiry' => null,
                    'payment_status' => 'paid',
                ]);
            }
            return [
                'status' => 'success',
                'message' => 'Successfully switched to the ' . $tier->name . ' plan.'
            ];
        }

        return $this->driver($gateway)->purchaseSubscription($entity, $tier, $isYearly);
    }

    /**
     * Helper to process a respondent payout.
     */
    public function payout(
        User $user,
        float $amount,
        string $currency = 'KES',
        ?string $reference = null,
        string $gateway = 'intasend'
    ): array {
        return $this->driver($gateway)->withdrawToRespondent($user, $amount, $currency, $reference);
    }
}
