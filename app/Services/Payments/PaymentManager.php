<?php

namespace App\Services\Payments;

use App\Interfaces\PaymentGatewayInterface;
use App\Models\Organization;
use App\Models\SubscriptionTier;
use App\Models\User;

class PaymentManager
{
    protected $gateway;

    public function __construct(PaymentGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Get the active gateway driver.
     */
    public function gateway(): PaymentGatewayInterface
    {
        return $this->gateway;
    }

    /**
     * Helper to process a subscription.
     */
    public function subscribe(Organization $org, SubscriptionTier $tier): array
    {
        return $this->gateway->purchaseSubscription($org, $tier);
    }

    /**
     * Helper to process a respondent payout.
     */
    public function payout(User $user, float $amount, string $currency): array
    {
        return $this->gateway->withdrawToRespondent($user, $amount, $currency);
    }
}
