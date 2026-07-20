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
        'share_report_token',
        'reward_per_response',
        'reward_budget',
        'current_reward_spent',
        'reward_currency',
        'logo_url',
        'brand_color',
        'remove_kd_branding',
        'export_logo_url',
        'export_org_name',
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

    public function groups(): HasMany
    {
        return $this->hasMany(SurveyGroup::class);
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

    public function aiThreads(): HasMany
    {
        return $this->hasMany(SurveyAiThread::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(SurveyVersion::class);
    }

    public function latestVersion(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SurveyVersion::class)->latestOfMany('version_number');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(SurveyInviteCampaign::class);
    }

    public function dashboardConfig(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SurveyDashboardConfig::class);
    }

    public static function cleanupEmptyDrafts($userId = null)
    {
        $query = self::where('title', 'Untitled Survey')
            ->where('status', \App\Enums\SurveyStatus::Draft)
            ->where(function ($q) {
                $q->whereNull('json_schema')
                    ->orWhere('json_schema', '[]')
                    ->orWhere('json_schema', '')
                    ->orWhere('json_schema', 'null');
            });

        if ($userId) {
            $query->where('created_by', $userId);
        }

        $query->where('created_at', '<', now()->subMinutes(5))->delete();
    }
}
