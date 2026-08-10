<?php

namespace Database\Factories;

use App\Models\OrgMember;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrgMemberFactory extends Factory
{
    protected $model = OrgMember::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'org_workspace_role' => 'lead_researcher',
            'status' => 'active',
            'invited_by' => null,
            'joined_at' => now(),
        ];
    }

    public function owner(): static
    {
        return $this->state(fn(array $attributes) => [
            'org_workspace_role' => 'owner',
            'status' => 'active',
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'org_workspace_role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function leadResearcher(): static
    {
        return $this->state(fn(array $attributes) => [
            'org_workspace_role' => 'lead_researcher',
            'status' => 'active',
        ]);
    }

    public function fieldEnumerator(): static
    {
        return $this->state(fn(array $attributes) => [
            'org_workspace_role' => 'field_enumerator',
            'status' => 'active',
        ]);
    }

    public function analyst(): static
    {
        return $this->state(fn(array $attributes) => [
            'org_workspace_role' => 'analyst',
            'status' => 'active',
        ]);
    }

    public function guestCollaborator(): static
    {
        return $this->state(fn(array $attributes) => [
            'org_workspace_role' => 'guest_collaborator',
            'status' => 'active',
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
            'joined_at' => null,
        ]);
    }
}
