<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'monthly_price',
        'yearly_price',
        'currency',
        'max_surveys',
        'org_max_seats',
        'org_ai_analyses_per_month',
        'org_transcription_minutes_per_month',
        'org_socius_sessions_per_month',
        'org_report_exports_per_month',
        'max_responses_per_survey',
        'ai_limit_per_month',
        'has_custom_branding',
        'has_data_export',
        'has_advanced_analytics',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'max_surveys' => 'integer',
        'org_max_seats' => 'integer',
        'org_ai_analyses_per_month' => 'integer',
        'org_transcription_minutes_per_month' => 'integer',
        'org_socius_sessions_per_month' => 'integer',
        'org_report_exports_per_month' => 'integer',
        'max_responses_per_survey' => 'integer',
        'ai_limit_per_month' => 'integer',
        'has_custom_branding' => 'boolean',
        'has_data_export' => 'boolean',
        'has_advanced_analytics' => 'boolean',
    ];

    public function organizations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Organization::class);
    }
}
