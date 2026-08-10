<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgSurveyAssignment extends Model
{
    use HasFactory;

    protected $table = 'org_survey_assignments';

    protected $fillable = [
        'organization_id',
        'survey_id',
        'user_id',
        'assigned_by',
        'response_quota',
        'zone_label',
    ];

    protected $casts = [
        'response_quota' => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
