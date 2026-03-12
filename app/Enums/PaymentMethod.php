<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case PayPal = 'paypal';
    case IntaSend = 'intasend';
}
