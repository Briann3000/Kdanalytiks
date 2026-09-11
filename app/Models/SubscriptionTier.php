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
        'monthly_price_usd',
        'yearly_price_usd',
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
        'monthly_price_usd' => 'decimal:2',
        'yearly_price_usd' => 'decimal:2',
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

    /**
     * Get price for the given currency and cycle.
     */
    public function getPrice(string $currency = 'KES', bool $isYearly = false): float
    {
        $curr = strtoupper($currency);
        if ($curr === 'USD') {
            return $isYearly ? (float) $this->yearly_price_usd : (float) $this->monthly_price_usd;
        }
        return $isYearly ? (float) $this->yearly_price : (float) $this->monthly_price;
    }

    /**
     * Get formatted price string.
     */
    public function formattedPrice(string $currency = 'KES', bool $isYearly = false): string
    {
        $amount = $this->getPrice($currency, $isYearly);
        $curr = strtoupper($currency);
        if ($curr === 'USD') {
            return '$' . number_format($amount, 2);
        }
        return 'KES ' . number_format($amount, 0);
    }

    public function organizations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Organization::class);
    }
}
