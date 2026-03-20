<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResearchProposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'research_question',
        'objectives',
        'methodology_type',
        'scope',
        'style',
        'content',
        'status'
    ];

    protected $casts = [
        'content' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
