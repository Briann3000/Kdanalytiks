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
        'slug',
        'industry',
        'logo_url',
        'brand_color',
        'website_url',
        'country',
        'enforce_branding',
        'custom_email_domain',
        'email_domain_verified',
        'pii_mask_by_default',
        'max_seats',
        'survey_approval_required',
        'data_retention_days',
        'payment_status',
        'subscription_expiry',
        'ai_usage_monthly',
    ];

    protected $casts = [
        'subscription_expiry' => 'datetime',
        'enforce_branding' => 'boolean',
        'email_domain_verified' => 'boolean',
        'pii_mask_by_default' => 'boolean',
        'survey_approval_required' => 'boolean',
        'max_seats' => 'integer',
        'data_retention_days' => 'integer',
    ];

    public function getMaxSeatsAttribute($value): int
    {
        $tier = $this->subscriptionTier ?? SubscriptionTier::where('slug', 'free')->first();
        if ($tier && isset($tier->org_max_seats)) {
            return (int) $tier->org_max_seats;
        }
        return ($value !== null && (int) $value > 0) ? (int) $value : 5;
    }

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

    public function members(): HasMany
    {
        return $this->hasMany(OrgMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->hasMany(OrgMember::class)->where('status', 'active');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(OrgInvitation::class)->whereNull('accepted_at');
    }

    public function resourcePool(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(OrgResourcePool::class);
    }

    public function ensureResourcePool(): OrgResourcePool
    {
        $pool = $this->resourcePool;
        if ($pool) {
            return $pool;
        }

        $tierSlug = strtolower($this->subscriptionTier?->slug ?? 'free');

        $limits = match (true) {
            str_contains($tierSlug, 'enterprise') => [
                'ai_analyses_limit' => -1,
                'transcription_minutes_limit' => -1,
                'socius_chat_sessions_limit' => -1,
                'report_exports_limit' => -1,
                'survey_limit' => -1,
            ],
            str_contains($tierSlug, 'pro') => [
                'ai_analyses_limit' => 500,
                'transcription_minutes_limit' => 300,
                'socius_chat_sessions_limit' => 1000,
                'report_exports_limit' => 100,
                'survey_limit' => 20,
            ],
            default => [
                'ai_analyses_limit' => 50,
                'transcription_minutes_limit' => 30,
                'socius_chat_sessions_limit' => 100,
                'report_exports_limit' => 20,
                'survey_limit' => 10,
            ],
        };

        return OrgResourcePool::create(array_merge(['organization_id' => $this->id], $limits));
    }

    public function memberRecord(User $user): ?OrgMember
    {
        return $this->members()->where('user_id', $user->id)->first();
    }

    public function totalSeatsUsed(): int
    {
        return $this->activeMembers()->count() + $this->invitations()->count();
    }

    public function hasReachedSeatLimit(): bool
    {
        $max = $this->max_seats;
        if ($max === -1) {
            return false;
        }
        return $this->totalSeatsUsed() >= $max;
    }

    public function isNearSeatLimit(): bool
    {
        $max = $this->max_seats;
        if ($max === -1) {
            return false;
        }
        return $this->totalSeatsUsed() >= (int) ($max * 0.85);
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
