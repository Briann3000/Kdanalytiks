<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'independent_id',
        'user_id',
        'amount',
        'method',
        'status',
        'transaction_id',
    ];

    protected $casts = [
        'method' => \App\Enums\PaymentMethod::class,
        'amount' => 'decimal:2',
    ];

    public function setMethodAttribute($value): void
    {
        if ($value instanceof \App\Enums\PaymentMethod) {
            $this->attributes['method'] = $value->value;
        } else {
            $this->attributes['method'] = \App\Enums\PaymentMethod::parse(is_string($value) ? $value : null)->value;
        }
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function independent(): BelongsTo
    {
        return $this->belongsTo(Independent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}