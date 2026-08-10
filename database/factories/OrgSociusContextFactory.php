<?php

namespace Database\Factories;

use App\Models\OrgSociusContext;
use App\Models\Organization;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrgSociusContextFactory extends Factory
{
    protected $model = OrgSociusContext::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'survey_id' => Survey::factory(),
            'context_type' => 'survey_summary',
            'content' => '[Study: Sample Research Study]\n[Date: ' . date('Y-m-d') . ']\n\n' . $this->faker->paragraph(),
            'generated_at' => now(),
            'created_by' => User::factory(),
        ];
    }
}
