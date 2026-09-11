<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Independent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_tier_id',
        'name',
        'institution',
        'research_area',
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

    public function subscriptionDaysRemaining(): int
    {
        if (!$this->subscription_expiry || $this->subscription_expiry->isPast()) {
            return 0;
        }
        return (int) ceil(now()->floatDiffInDays($this->subscription_expiry));
    }

    public function isSubscriptionExpired(): bool
    {
        if (!$this->subscription_expiry) {
            return false;
        }
        return $this->subscription_expiry->isPast();
    }

    public function hasActiveSubscription(): bool
    {
        $tier = $this->subscriptionTier;
        if (!$tier || $tier->isFree()) {
            return false;
        }
        return $this->subscription_expiry && $this->subscription_expiry->isFuture();
    }
}