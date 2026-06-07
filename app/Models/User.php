<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, MustVerifyEmailTrait;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'role',
        'status',
        'subscription_tier_id',
        'subscription_expiry',
        'payment_status',
        'remove_km_branding',
        'export_logo_url',
        'export_org_name',
        'free_export_count',
        'ai_analysis_count',
        'locale',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => \App\Enums\UserRole::class,
        'status' => \App\Enums\UserStatus::class,
        'subscription_expiry' => 'datetime',
        'ai_analysis_count' => 'integer',
    ];

    public function organization(): HasOne
    {
        return $this->hasOne(Organization::class);
    }

    public function independent(): HasOne
    {
        return $this->hasOne(Independent::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class, 'respondent_id');
    }

    public function aiThreads(): HasMany
    {
        return $this->hasMany(SurveyAiThread::class);
    }

    public function sociusKnowledgeBases(): HasMany
    {
        return $this->hasMany(SociusKnowledgeBase::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function subscriptionTier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SubscriptionTier::class, 'subscription_tier_id');
    }

    public function isAdmin(): bool
    {
        $roleVal = $this->role instanceof \UnitEnum ? $this->role->value : $this->role;
        return $roleVal === 'admin';
    }

    public function hasActiveSubscription(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $entity = null;
        $roleVal = $this->role instanceof \UnitEnum ? $this->role->value : $this->role;
        if ($roleVal === 'organization') {
            $entity = $this->organization;
        } elseif ($roleVal === 'independent') {
            $entity = $this->independent;
        } elseif ($roleVal === 'respondent') {
            // Respondents have subscription data directly on the user model
            $entity = $this;
        }

        if (!$entity) {
            return false;
        }

        $tier = $entity->subscriptionTier;
        if (!$tier || str_contains(strtolower($tier->slug), 'free')) {
            return false;
        }

        // Check if expiry exists and is in the future
        if ($entity->subscription_expiry && $entity->subscription_expiry->isPast()) {
            return false;
        }

        return $entity->payment_status === 'paid' || $entity->payment_status === 'COMPLETE';
    }

    /**
     * Check if user has Pro/Enterprise level access
     */
    public function hasProAccess(): bool
    {
        if ($this->isAdmin())
            return true;

        $roleVal = $this->role instanceof \UnitEnum ? $this->role->value : $this->role;
        $entity = ($roleVal === 'organization') ? $this->organization : (($roleVal === 'independent') ? $this->independent : $this);
        if (!$entity)
            return false;

        $tier = $entity->subscriptionTier;
        if (!$tier)
            return false;

        $tierSlug = strtolower($tier->slug);
        return str_contains($tierSlug, 'pro') || str_contains($tierSlug, 'enterprise');
    }

    /**
     * Check if user can use AI Analysis (Pro or Trial)
     */
    public function canUseAiAnalysis(): bool
    {
        if ($this->hasProAccess())
            return true;

        // Free users get 2 trials
        return ($this->ai_analysis_count ?? 0) < 2;
    }

    /**
     * Increment AI Analysis usage
     */
    public function recordAiUsage(): void
    {
        if (!$this->hasProAccess()) {
            $this->increment('ai_analysis_count');
        }
    }

    /**
     * Determine if the user has verified their email address.
     * Admins are exempt from verification.
     *
     * @return bool
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->isAdmin() ||
            $this->email_verified_at !== null;
    }
}
