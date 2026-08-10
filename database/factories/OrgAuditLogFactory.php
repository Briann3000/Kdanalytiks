<?php

namespace Database\Factories;

use App\Models\OrgAuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrgAuditLogFactory extends Factory
{
    protected $model = OrgAuditLog::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'action' => 'member.invited',
            'target_type' => 'User',
            'target_id' => 1,
            'metadata' => ['role' => 'admin'],
            'ip_address' => '127.0.0.1',
        ];
    }
}
