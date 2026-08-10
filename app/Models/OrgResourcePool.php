<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgResourcePool extends Model
{
    use HasFactory;
    protected $table = 'organization_resource_pools';

    protected $fillable = [
        'organization_id',
        'ai_analyses_limit',
        'transcription_minutes_limit',
        'socius_chat_sessions_limit',
        'report_exports_limit',
        'survey_limit',
        'ai_analyses_used',
        'transcription_minutes_used',
        'socius_chat_sessions_used',
        'report_exports_used',
        'reset_at',
    ];

    protected $casts = [
        'reset_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function canUseAiAnalysis(): bool
    {
        return $this->ai_analyses_limit === -1 || $this->ai_analyses_used < $this->ai_analyses_limit;
    }

    public function canTranscribe(): bool
    {
        return $this->transcription_minutes_limit === -1 || $this->transcription_minutes_used < $this->transcription_minutes_limit;
    }

    public function canUseSocius(): bool
    {
        return $this->socius_chat_sessions_limit === -1 || $this->socius_chat_sessions_used < $this->socius_chat_sessions_limit;
    }

    public function aiUsagePct(): ?int
    {
        if ($this->ai_analyses_limit === -1 || $this->ai_analyses_limit <= 0) {
            return null;
        }
        return (int) round(($this->ai_analyses_used / $this->ai_analyses_limit) * 100);
    }
}
