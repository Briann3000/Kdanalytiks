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
            // --- Independent Researcher Tiers ---
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'For students, learners, and simple trial projects.',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'monthly_price_usd' => 0,
                'yearly_price_usd' => 0,
                'currency' => 'KES',
                'max_surveys' => 3,
                'max_responses_per_survey' => 100,
                'ai_limit_per_month' => 5,
                'has_custom_branding' => false,
                'has_data_export' => false,
                'has_advanced_analytics' => false,
                'org_max_seats' => 1,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Advanced features for professional independent researchers and consultants.',
                'monthly_price' => 1999,
                'yearly_price' => 19990,
                'monthly_price_usd' => 19,
                'yearly_price_usd' => 190,
                'currency' => 'KES',
                'max_surveys' => 25,
                'max_responses_per_survey' => 2500,
                'ai_limit_per_month' => 100,
                'has_custom_branding' => true,
                'has_data_export' => true,
                'has_advanced_analytics' => true,
                'org_max_seats' => 1,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Unlimited scale and full statistical analytics for individual power researchers.',
                'monthly_price' => 4999,
                'yearly_price' => 49990,
                'monthly_price_usd' => 49,
                'yearly_price_usd' => 490,
                'currency' => 'KES',
                'max_surveys' => -1,
                'max_responses_per_survey' => -1,
                'ai_limit_per_month' => -1,
                'has_custom_branding' => true,
                'has_data_export' => true,
                'has_advanced_analytics' => true,
                'org_max_seats' => 1,
            ],

            // --- Organization Multi-Seat Tiers ---
            [
                'name' => 'Org Free',
                'slug' => 'org-free',
                'description' => 'Starter team workspace with basic collaboration.',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'monthly_price_usd' => 0,
                'yearly_price_usd' => 0,
                'currency' => 'KES',
                'max_surveys' => 3,
                'max_responses_per_survey' => 100,
                'ai_limit_per_month' => 5,
                'has_custom_branding' => false,
                'has_data_export' => false,
                'has_advanced_analytics' => false,
                'org_max_seats' => 2,
            ],
            [
                'name' => 'Org Pro',
                'slug' => 'org-pro',
                'description' => 'Team workspace for research agencies, NGOs, and departments (up to 5 seats).',
                'monthly_price' => 4999,
                'yearly_price' => 49990,
                'monthly_price_usd' => 49,
                'yearly_price_usd' => 490,
                'currency' => 'KES',
                'max_surveys' => 50,
                'max_responses_per_survey' => 10000,
                'ai_limit_per_month' => 300,
                'has_custom_branding' => true,
                'has_data_export' => true,
                'has_advanced_analytics' => true,
                'org_max_seats' => 5,
            ],
            [
                'name' => 'Org Enterprise',
                'slug' => 'org-enterprise',
                'description' => 'Unlimited team seats, audit compliance, and institutional scale.',
                'monthly_price' => 9999,
                'yearly_price' => 99990,
                'monthly_price_usd' => 99,
                'yearly_price_usd' => 990,
                'currency' => 'KES',
                'max_surveys' => -1,
                'max_responses_per_survey' => -1,
                'ai_limit_per_month' => -1,
                'has_custom_branding' => true,
                'has_data_export' => true,
                'has_advanced_analytics' => true,
                'org_max_seats' => -1,
            ],

            // --- Respondent Tiers ---
            [
                'name' => 'Respondent Pro',
                'slug' => 'respondent-pro',
                'description' => 'Full access to AI writing & research tools: Socius, Humanizer, Plagiarism & Transcriptions.',
                'monthly_price' => 699,
                'yearly_price' => 6990,
                'monthly_price_usd' => 6.99,
                'yearly_price_usd' => 69,
                'currency' => 'KES',
                'max_surveys' => 0,
                'max_responses_per_survey' => 0,
                'ai_limit_per_month' => -1,
                'has_custom_branding' => false,
                'has_data_export' => true,
                'has_advanced_analytics' => true,
                'org_max_seats' => 1,
            ],
        ];

        foreach ($tiers as $tier) {
            \App\Models\SubscriptionTier::updateOrCreate(['slug' => $tier['slug']], $tier);
        }
    }
}
