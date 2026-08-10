<?php

namespace Database\Factories;

use App\Models\OrgResourcePool;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrgResourcePoolFactory extends Factory
{
    protected $model = OrgResourcePool::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'ai_analyses_limit' => 5,
            'transcription_minutes_limit' => 2,
            'socius_chat_sessions_limit' => 20,
            'report_exports_limit' => 5,
            'survey_limit' => 5,
            'ai_analyses_used' => 0,
            'transcription_minutes_used' => 0,
            'socius_chat_sessions_used' => 0,
            'report_exports_used' => 0,
            'reset_at' => now(),
        ];
    }

    public function maxCapacity(): static
    {
        return $this->state(fn(array $attributes) => [
            'ai_analyses_limit' => 5,
            'ai_analyses_used' => 5,
            'transcription_minutes_limit' => 2,
            'transcription_minutes_used' => 2,
            'socius_chat_sessions_limit' => 20,
            'socius_chat_sessions_used' => 20,
            'report_exports_limit' => 5,
            'report_exports_used' => 5,
        ]);
    }

    public function unlimited(): static
    {
        return $this->state(fn(array $attributes) => [
            'ai_analyses_limit' => -1,
            'transcription_minutes_limit' => -1,
            'socius_chat_sessions_limit' => -1,
            'report_exports_limit' => -1,
            'survey_limit' => -1,
        ]);
    }
}
