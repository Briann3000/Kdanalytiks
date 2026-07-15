<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SurveyAiThread extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'user_id',
        'survey_group_id',
        'title',
        'is_pinned',
        'last_activity_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'last_activity_at' => 'datetime',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(SurveyGroup::class, 'survey_group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SurveyAiMessage::class, 'thread_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(SurveyAiMessage::class, 'thread_id')->latestOfMany();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SurveyAiAttachment::class, 'thread_id');
    }
}
