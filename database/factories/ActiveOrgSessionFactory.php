<?php

namespace Database\Factories;

use App\Models\ActiveOrgSession;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActiveOrgSessionFactory extends Factory
{
    protected $model = ActiveOrgSession::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'active_organization_id' => Organization::factory(),
        ];
    }
}
