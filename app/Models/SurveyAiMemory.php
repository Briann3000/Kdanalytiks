<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyAiMemory extends Model
{
    protected $fillable = [
        'user_id',
        'survey_id',
        'fact',
        'importance',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }
}
