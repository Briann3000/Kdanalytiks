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
        'amount',
        'method',
        'status',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'method' => \App\Enums\PaymentMethod::class,
            'amount' => 'decimal:2',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function independent(): BelongsTo
    {
        return $this->belongsTo(Independent::class);
    }
}