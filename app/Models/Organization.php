<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_tier_id',
        'name',
        'payment_status',
        'subscription_expiry',
        'ai_usage_monthly',
    ];

    protected $casts = [
        'subscription_expiry' => 'datetime',
    ];

    public function subscriptionTier(): BelongsTo
    {
        return $this->belongsTo(SubscriptionTier::class, 'subscription_tier_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function hasReachedSurveyLimit(): bool
    {
        $currentCount = $this->surveys()->count();
        $tier = $this->subscriptionTier ?? \App\Models\SubscriptionTier::where('slug', 'free')->first();

        if (!$tier || $tier->max_surveys == -1) {
            return false;
        }

        return $currentCount >= $tier->max_surveys;
    }
}
