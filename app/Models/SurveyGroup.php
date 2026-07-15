<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'name',
        'token',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'survey_group_users', 'survey_group_id', 'user_id');
    }

    public function threads(): HasMany
    {
        return $this->hasMany(SurveyAiThread::class, 'survey_group_id');
    }
}
