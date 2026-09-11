<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case PayPal = 'paypal';
    case IntaSend = 'intasend';
    case WesternUnion = 'western_union';
    case BankWire = 'bank_wire';
}
