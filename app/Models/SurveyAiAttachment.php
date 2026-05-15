<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyAiAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'thread_id',
        'message_id',
        'original_name',
        'mime_type',
        'size_bytes',
        'storage_path',
        'extracted_text',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(SurveyAiThread::class, 'thread_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(SurveyAiMessage::class, 'message_id');
    }
}
