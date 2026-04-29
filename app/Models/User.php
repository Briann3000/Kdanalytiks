<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

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
        return $this->role === \App\Enums\UserRole::Admin;
    }

    public function hasActiveSubscription(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $entity = null;
        if ($this->role === \App\Enums\UserRole::Organization) {
            $entity = $this->organization;
        } elseif ($this->role === \App\Enums\UserRole::Independent) {
            $entity = $this->independent;
        } elseif ($this->role === \App\Enums\UserRole::Respondent) {
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
