<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyAiMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'thread_id',
        'user_id',
        'role',
        'content',
        'include_survey_context',
        'metadata',
    ];

    protected $casts = [
        'include_survey_context' => 'boolean',
        'metadata' => 'array',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(SurveyAiThread::class, 'thread_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SurveyAiAttachment::class, 'message_id');
    }
}
