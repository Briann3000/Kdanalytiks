<?php

namespace Database\Factories;

use App\Models\OrgSurveyAssignment;
use App\Models\Organization;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrgSurveyAssignmentFactory extends Factory
{
    protected $model = OrgSurveyAssignment::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'survey_id' => Survey::factory(),
            'user_id' => User::factory(),
            'assigned_by' => User::factory(),
            'response_quota' => 50,
            'zone_label' => 'Zone 1',
        ];
    }
}
