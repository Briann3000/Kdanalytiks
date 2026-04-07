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

    public function isAdmin(): bool
    {
        return $this->role === \App\Enums\UserRole::Admin;
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
