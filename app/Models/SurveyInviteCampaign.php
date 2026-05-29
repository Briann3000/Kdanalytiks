<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyInviteCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'name',
        'status',
        'auto_reminders',
        'reminder_interval_days',
        'scheduled_at',
        'completed_at',
        'total_recipients',
        'total_sent',
        'total_opened',
        'total_responded',
        'created_by',
    ];

    protected $casts = [
        'auto_reminders' => 'boolean',
        'reminder_interval_days' => 'integer',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(SurveyInviteRecipient::class, 'campaign_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
