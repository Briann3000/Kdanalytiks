<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Response extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'respondent_id',
        'guest_name',
        'guest_phone',
        'ai_metadata',
        'quality_score',
        'quality_flags',
        'is_flagged',
        'completion_time_seconds',
        'ip_address',
        'device_fingerprint',
    ];

    protected $casts = [
        'ai_metadata' => 'array',
        'quality_flags' => 'array',
        'is_flagged' => 'boolean',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function respondent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'respondent_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}