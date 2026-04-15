<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'independent_id',
        'title',
        'description',
        'category',
        'type',
        'status',
        'is_template',
        'json_schema',
        'created_by',
        'is_anonymous',
        'is_paid',
        'public_access',
        'share_token',
        'reward_per_response',
        'reward_budget',
        'current_reward_spent',
        'reward_currency',
    ];

    protected $casts = [
        'type' => \App\Enums\SurveyType::class,
        'status' => \App\Enums\SurveyStatus::class,
        'category' => \App\Enums\SurveyCategory::class,
        'is_template' => 'boolean',
        'is_anonymous' => 'boolean',
        'is_paid' => 'boolean',
        'reward_per_response' => 'decimal:2',
        'reward_budget' => 'decimal:2',
        'current_reward_spent' => 'decimal:2',
    ];

    public function collaborators(): HasMany
    {
        return $this->hasMany(SurveyPermission::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function independent(): BelongsTo
    {
        return $this->belongsTo(Independent::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }
}