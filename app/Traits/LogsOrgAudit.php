<?php

namespace App\Traits;

use App\Models\OrgAuditLog;

trait LogsOrgAudit
{
    protected function orgLog(
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        array $metadata = []
    ): void {
        $org = request()->attributes->get('active_org') ?? auth()->user()?->activeOrganization();
        if (!$org) {
            return;
        }

        // Avoid failing if OrgAuditLog class or table isn't migrated yet
        if (class_exists(OrgAuditLog::class)) {
            OrgAuditLog::create([
                'organization_id' => $org->id,
                'user_id' => auth()->id(),
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'metadata' => $metadata,
                'ip_address' => request()->ip(),
            ]);
        }
    }
}
