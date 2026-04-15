<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Survey;
use App\Enums\SurveyStatus;
use App\Enums\SurveyType;
use App\Enums\SurveyCategory;

class SurveyTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'title' => 'Customer Satisfaction (CSAT)',
                'description' => 'Measure how satisfied customers are with your products or services.',
                'icon' => 'fa-smile',
                'color' => 'bg-emerald-50 text-emerald-600',
                'category' => 'market_research',
                'json_schema' => json_encode([
                    ['type' => 'header', 'label' => 'Customer Satisfaction (CSAT)'],
                    [
                        'type' => 'radio-group',
                        'label' => 'Overall, how satisfied were you with your experience?',
                        'name' => 'overall_satisfaction',
                        'values' => [
                            ['label' => 'Very Satisfied', 'value' => '5'],
                            ['label' => 'Satisfied', 'value' => '4'],
                            ['label' => 'Neutral', 'value' => '3'],
                            ['label' => 'Unsatisfied', 'value' => '2'],
                            ['label' => 'Very Unsatisfied', 'value' => '1'],
                        ]
                    ],
                    [
                        'type' => 'radio-group',
                        'label' => 'How likely are you to purchase from us again?',
                        'name' => 'repurchase_intent',
                        'values' => [
                            ['label' => 'Definitely', 'value' => '5'],
                            ['label' => 'Probably', 'value' => '4'],
                            ['label' => 'Unsure', 'value' => '3'],
                            ['label' => 'Probably Not', 'value' => '2'],
                            ['label' => 'No', 'value' => '1'],
                        ]
                    ],
                    ['type' => 'textarea', 'label' => 'What can we do to improve?', 'name' => 'improvement_feedback'],
                ]),
            ],
            [
                'title' => 'Net Promoter Score (NPS)',
                'description' => 'Measure customer loyalty and likelihood of recommendations.',
                'icon' => 'fa-chart-pie',
                'color' => 'bg-indigo-50 text-indigo-600',
                'category' => 'market_research',
                'json_schema' => json_encode([
                    ['type' => 'header', 'label' => 'Net Promoter Score (NPS)'],
                    ['type' => 'number', 'label' => 'How likely are you to recommend us to a friend or colleague? (0-10)', 'name' => 'nps_score', 'min' => 0, 'max' => 10],
                    ['type' => 'textarea', 'label' => 'Main reason for your score?', 'name' => 'nps_reason'],
                ]),
            ],
            [
                'title' => 'Market Feasibility Study',
                'description' => 'Evaluate potential success of new products in a market.',
                'icon' => 'fa-binoculars',
                'color' => 'bg-amber-50 text-amber-600',
                'category' => 'feasibility',
                'json_schema' => json_encode([
                    ['type' => 'header', 'label' => 'Market Feasibility Study'],
                    [
                        'type' => 'radio-group',
                        'label' => 'Do you currently use a similar product?',
                        'name' => 'current_usage',
                        'values' => [
                            ['label' => 'Yes', 'value' => 'yes'],
                            ['label' => 'No', 'value' => 'no'],
                        ]
                    ],
                    ['type' => 'text', 'label' => 'How much would you be willing to pay for this new solution?', 'name' => 'price_point'],
                    [
                        'type' => 'checkbox-group',
                        'label' => 'Which features are most important to you?',
                        'name' => 'features',
                        'values' => [
                            ['label' => 'Price', 'value' => 'price'],
                            ['label' => 'Speed', 'value' => 'speed'],
                            ['label' => 'Reliability', 'value' => 'reliability'],
                            ['label' => 'Design', 'value' => 'design'],
                        ]
                    ],
                ]),
            ],
            [
                'title' => 'Academic Peer Review',
                'description' => 'Standardized form for scholarly review of research.',
                'icon' => 'fa-graduation-cap',
                'color' => 'bg-purple-50 text-purple-600',
                'category' => 'academic',
                'json_schema' => json_encode([
                    ['type' => 'header', 'label' => 'Peer Review Form'],
                    [
                        'type' => 'radio-group',
                        'label' => 'Originality of the work',
                        'name' => 'originality',
                        'values' => [
                            ['label' => 'Excellent', 'value' => '5'],
                            ['label' => 'Good', 'value' => '4'],
                            ['label' => 'Average', 'value' => '3'],
                            ['label' => 'Poor', 'value' => '2'],
                        ]
                    ],
                    ['type' => 'textarea', 'label' => 'Detailed Comments for Authors', 'name' => 'comments'],
                    [
                        'type' => 'radio-group',
                        'label' => 'Recommendation',
                        'name' => 'recommendation',
                        'values' => [
                            ['label' => 'Accept', 'value' => 'accept'],
                            ['label' => 'Minor Revision', 'value' => 'minor'],
                            ['label' => 'Major Revision', 'value' => 'major'],
                            ['label' => 'Reject', 'value' => 'reject'],
                        ]
                    ],
                ]),
            ],
            [
                'title' => 'Product Feedback',
                'description' => 'Gather insights on product features and usability.',
                'icon' => 'fa-box-open',
                'color' => 'bg-blue-50 text-blue-600',
                'category' => 'others',
                'json_schema' => json_encode([
                    ['type' => 'header', 'label' => 'Product Feedback'],
                    [
                        'type' => 'radio-group',
                        'label' => 'How easy was it to use the product?',
                        'name' => 'ease_of_use',
                        'values' => [
                            ['label' => 'Very Easy', 'value' => '5'],
                            ['label' => 'Somewhat Easy', 'value' => '3'],
                            ['label' => 'Difficult', 'value' => '1'],
                        ]
                    ],
                    [
                        'type' => 'checkbox-group',
                        'label' => 'What did you like about the product?',
                        'name' => 'likes',
                        'values' => [
                            ['label' => 'Speed', 'value' => 'speed'],
                            ['label' => 'Price', 'value' => 'price'],
                            ['label' => 'UI/UX', 'value' => 'ui'],
                        ]
                    ],
                ]),
            ],
            [
                'title' => 'Post-Event Evaluation',
                'description' => 'Assess event effectiveness and gather suggestions.',
                'icon' => 'fa-calendar-check',
                'color' => 'bg-rose-50 text-rose-600',
                'category' => 'others',
                'json_schema' => json_encode([
                    ['type' => 'header', 'label' => 'Post-Event Feedback'],
                    [
                        'type' => 'radio-group',
                        'label' => 'Did the event meet your expectations?',
                        'name' => 'expectations',
                        'values' => [
                            ['label' => 'Exceeded', 'value' => '5'],
                            ['label' => 'Met', 'value' => '3'],
                            ['label' => 'Below', 'value' => '1'],
                        ]
                    ],
                    ['type' => 'textarea', 'label' => 'Favorite session?', 'name' => 'fav_session'],
                ]),
            ],
        ];

        foreach ($templates as $tpl) {
            Survey::updateOrCreate(
                ['title' => $tpl['title']],
                [
                    'description' => $tpl['description'],
                    'icon' => $tpl['icon'],
                    'color' => $tpl['color'],
                    'category' => $tpl['category'],
                    'json_schema' => $tpl['json_schema'],
                    'status' => SurveyStatus::Active,
                    'type' => SurveyType::Public ,
                    'is_template' => true,
                    'created_by' => 1,
                ]
            );
        }
    }
}
