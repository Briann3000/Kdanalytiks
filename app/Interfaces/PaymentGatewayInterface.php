<?php

namespace App\Interfaces;

use App\Models\Organization;
use App\Models\SubscriptionTier;
use App\Models\User;

interface PaymentGatewayInterface
{
    /**
     * Initiate a subscription purchase.
     */
    public function purchaseSubscription($entity, SubscriptionTier $tier, bool $isYearly = false): array;

    /**
     * Process a withdrawal (payout) to a respondent.
     */
    public function withdrawToRespondent(User $user, float $amount, string $currency): array;

    /**
     * Validate an incoming webhook from the payment provider.
     */
    public function validateWebhook(string $content, array $headers): bool;
}
