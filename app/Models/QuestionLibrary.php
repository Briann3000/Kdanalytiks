<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionLibrary extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'type',
        'content_json',
        'category',
        'is_public'
    ];

    protected $casts = [
        'content_json' => 'array',
        'is_public' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
