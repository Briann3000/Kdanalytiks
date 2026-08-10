<?php

namespace Database\Factories;

use App\Models\OrgInvitation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrgInvitationFactory extends Factory
{
    protected $model = OrgInvitation::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'invited_by' => User::factory(),
            'email' => $this->faker->unique()->safeEmail(),
            'org_workspace_role' => 'lead_researcher',
            'token' => Str::random(64),
            'message' => $this->faker->sentence(),
            'accepted_at' => null,
            'expires_at' => now()->addDays(7),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn(array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn(array $attributes) => [
            'accepted_at' => now()->subHour(),
        ]);
    }
}
