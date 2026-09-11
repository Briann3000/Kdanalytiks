<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'webhook/*',
        'webhook/payment',
        '/webhook/payment',
        'webhook/paypal',
        '/webhook/paypal',
        'subscriptions/intasend/callback',
        '/subscriptions/intasend/callback',
        'subscriptions/paypal/success',
        '/subscriptions/paypal/success',
    ];
}