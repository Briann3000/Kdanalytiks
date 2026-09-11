<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case PayPal = 'paypal';
    case IntaSend = 'intasend';
    case WesternUnion = 'western_union';
    case BankWire = 'bank_wire';

    /**
     * Safely parse any incoming provider string, case-insensitively, with fallbacks.
     */
    public static function parse(?string $value): self
    {
        if (!$value) {
            return self::IntaSend;
        }

        $normalized = strtolower(trim($value));

        if (str_contains($normalized, 'intasend') || str_contains($normalized, 'mpesa') || str_contains($normalized, 'm-pesa') || str_contains($normalized, 'card') || str_contains($normalized, 'kes')) {
            return self::IntaSend;
        }

        if (str_contains($normalized, 'paypal') || str_contains($normalized, 'usd')) {
            return self::PayPal;
        }

        if (str_contains($normalized, 'western')) {
            return self::WesternUnion;
        }

        if (str_contains($normalized, 'wire') || str_contains($normalized, 'bank')) {
            return self::BankWire;
        }

        return self::tryFrom($normalized) ?? self::IntaSend;
    }

    public function label(): string
    {
        return match ($this) {
            self::IntaSend => 'KES (M-Pesa / Card)',
            self::PayPal => 'USD (Card / PayPal)',
            self::WesternUnion => 'Western Union',
            self::BankWire => 'Bank Wire Transfer',
        };
    }
}
