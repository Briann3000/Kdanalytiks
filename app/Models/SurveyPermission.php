<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyPermission extends Model
{
    protected $fillable = [
        'survey_id',
        'user_id',
        'invite_email',
        'status',
        'invite_token',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function hasPermission(string $key): bool
    {
        if (empty($this->permissions) || !is_array($this->permissions)) {
            return false;
        }

        return !empty($this->permissions[$key]);
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
