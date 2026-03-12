<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => \App\Enums\UserRole::class,
            'status' => \App\Enums\UserStatus::class,
        ];
    }

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

    public function isAdmin(): bool
    {
        return $this->role === \App\Enums\UserRole::Admin;
    }
}
