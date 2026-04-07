<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriptionTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'For individual learners and simple projects.',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'max_surveys' => 3,
                'max_responses_per_survey' => 50,
                'ai_limit_per_month' => 5,
                'has_custom_branding' => false,
                'has_data_export' => false,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Advanced features for growing organizations.',
                'monthly_price' => 19.99,
                'yearly_price' => 199.99,
                'max_surveys' => 10,
                'max_responses_per_survey' => 1000,
                'ai_limit_per_month' => 100,
                'has_custom_branding' => true,
                'has_data_export' => true,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Unlimited scale and premium support.',
                'monthly_price' => 99.99,
                'yearly_price' => 999.99,
                'max_surveys' => -1,
                'max_responses_per_survey' => -1,
                'ai_limit_per_month' => -1,
                'has_custom_branding' => true,
                'has_data_export' => true,
                'has_advanced_analytics' => true,
            ],
        ];

        foreach ($tiers as $tier) {
            \App\Models\SubscriptionTier::updateOrCreate(['slug' => $tier['slug']], $tier);
        }
    }
}
