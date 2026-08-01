<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompiledReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'survey_id',
        'title',
        'original_chapters_path',
        'proofread_chapters',
        'chapter4_content',
        'chapter5_content',
        'final_docx_path',
        'status',
    ];

    protected $casts = [
        'proofread_chapters' => 'array',
        'chapter4_content' => 'array',
        'chapter5_content' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }
}
