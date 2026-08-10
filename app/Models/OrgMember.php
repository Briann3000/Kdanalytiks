<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgMember extends Model
{
    use HasFactory;
    protected $table = 'organization_members';

    protected $fillable = [
        'organization_id',
        'user_id',
        'org_workspace_role',
        'status',
        'invited_by',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isOwner(): bool
    {
        return $this->org_workspace_role === 'owner';
    }

    public function isAdmin(): bool
    {
        return in_array($this->org_workspace_role, ['owner', 'admin']);
    }

    public function isLeadResearcher(): bool
    {
        return $this->org_workspace_role === 'lead_researcher';
    }

    public function isEnumerator(): bool
    {
        return $this->org_workspace_role === 'field_enumerator';
    }

    public function isAnalyst(): bool
    {
        return $this->org_workspace_role === 'analyst';
    }

    public function isGuestCollaborator(): bool
    {
        return $this->org_workspace_role === 'guest_collaborator';
    }

    public function canManageSurveys(): bool
    {
        return in_array($this->org_workspace_role, ['owner', 'admin', 'lead_researcher']);
    }

    public function canViewRawPII(): bool
    {
        return in_array($this->org_workspace_role, ['owner', 'admin', 'lead_researcher']);
    }
}
