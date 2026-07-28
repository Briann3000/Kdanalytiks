<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyInferentialAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'user_id',
        'method',
        'title',
        'variables',
        'ai_summary',
        'payload'
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
