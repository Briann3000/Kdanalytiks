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
                'description' => 'Measure how happy customers are with your brand or service.',
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
                        'label' => 'How would you rate the quality of our product or service?',
                        'name' => 'product_quality',
                        'values' => [
                            ['label' => 'Excellent', 'value' => '5'],
                            ['label' => 'Good', 'value' => '4'],
                            ['label' => 'Average', 'value' => '3'],
                            ['label' => 'Poor', 'value' => '2'],
                            ['label' => 'Very Poor', 'value' => '1'],
                        ]
                    ],
                    [
                        'type' => 'radio-group',
                        'label' => 'How satisfied were you with our customer support team?',
                        'name' => 'support_satisfaction',
                        'values' => [
                            ['label' => 'Very Satisfied', 'value' => '5'],
                            ['label' => 'Satisfied', 'value' => '4'],
                            ['label' => 'Neutral', 'value' => '3'],
                            ['label' => 'Unsatisfied', 'value' => '2'],
                            ['label' => 'Very Unsatisfied', 'value' => '1'],
                        ]
                    ],
                    [
                        'type' => 'checkbox-group',
                        'label' => 'Which aspects of our service did you value the most?',
                        'name' => 'valued_aspects',
                        'values' => [
                            ['label' => 'Response Speed', 'value' => 'speed'],
                            ['label' => 'Staff Friendliness', 'value' => 'friendliness'],
                            ['label' => 'Professionalism', 'value' => 'professionalism'],
                            ['label' => 'Value for Money', 'value' => 'value'],
                            ['label' => 'Ease of Use', 'value' => 'ease'],
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
                    ['type' => 'textarea', 'label' => 'What can we do to improve your experience?', 'name' => 'improvement_feedback'],
                ]),
            ],
            [
                'title' => 'Net Promoter Score (NPS)',
                'description' => 'Find out how likely your customers are to recommend you to others.',
                'icon' => 'fa-chart-pie',
                'color' => 'bg-indigo-50 text-indigo-600',
                'category' => 'market_research',
                'json_schema' => json_encode([
                    ['type' => 'header', 'label' => 'Net Promoter Score (NPS)'],
                    ['type' => 'number', 'label' => 'How likely are you to recommend us to a friend or colleague? (0-10)', 'name' => 'nps_score', 'min' => 0, 'max' => 10],
                    ['type' => 'textarea', 'label' => 'What is the primary reason for your score?', 'name' => 'nps_reason'],
                    [
                        'type' => 'checkbox-group',
                        'label' => 'Which of the following areas can we improve to serve you better?',
                        'name' => 'improvement_areas',
                        'values' => [
                            ['label' => 'Customer Support', 'value' => 'support'],
                            ['label' => 'Product Quality', 'value' => 'quality'],
                            ['label' => 'Delivery Speed', 'value' => 'speed'],
                            ['label' => 'Pricing & Value', 'value' => 'pricing'],
                            ['label' => 'Website Usability', 'value' => 'usability'],
                        ]
                    ],
                    [
                        'type' => 'radio-group',
                        'label' => 'How long have you been using our product or service?',
                        'name' => 'usage_duration',
                        'values' => [
                            ['label' => 'Less than a month', 'value' => 'new'],
                            ['label' => '1 to 6 months', 'value' => 'mid'],
                            ['label' => '6 to 12 months', 'value' => 'long'],
                            ['label' => 'Over a year', 'value' => 'loyal'],
                        ]
                    ],
                ]),
            ],
            [
                'title' => 'Market Feasibility Study',
                'description' => 'Test if your new product or service idea has a successful market.',
                'icon' => 'fa-binoculars',
                'color' => 'bg-amber-50 text-amber-600',
                'category' => 'feasibility',
                'json_schema' => json_encode([
                    ['type' => 'header', 'label' => 'Market Feasibility Study'],
                    [
                        'type' => 'radio-group',
                        'label' => 'Do you currently use a similar product or service in your daily life or business?',
                        'name' => 'current_usage',
                        'values' => [
                            ['label' => 'Yes', 'value' => 'yes'],
                            ['label' => 'No', 'value' => 'no'],
                        ]
                    ],
                    ['type' => 'text', 'label' => 'If yes, which brand or solution do you currently use?', 'name' => 'current_brand'],
                    [
                        'type' => 'checkbox-group',
                        'label' => 'What are the main challenges you face with your current solution?',
                        'name' => 'current_challenges',
                        'values' => [
                            ['label' => 'High Cost', 'value' => 'cost'],
                            ['label' => 'Slow Performance', 'value' => 'speed'],
                            ['label' => 'Poor Customer Service', 'value' => 'support'],
                            ['label' => 'Lack of Features', 'value' => 'features'],
                            ['label' => 'Hard to Use', 'value' => 'usability'],
                        ]
                    ],
                    [
                        'type' => 'radio-group',
                        'label' => 'How interested would you be in a new solution that addresses these challenges?',
                        'name' => 'interest_level',
                        'values' => [
                            ['label' => 'Very Interested', 'value' => 'very'],
                            ['label' => 'Somewhat Interested', 'value' => 'somewhat'],
                            ['label' => 'Neutral', 'value' => 'neutral'],
                            ['label' => 'Not Interested', 'value' => 'not'],
                        ]
                    ],
                    [
                        'type' => 'checkbox-group',
                        'label' => 'Which features would be most important to you in a new solution?',
                        'name' => 'key_features',
                        'values' => [
                            ['label' => 'Low Price', 'value' => 'price'],
                            ['label' => 'Fast Speed', 'value' => 'speed'],
                            ['label' => 'High Reliability', 'value' => 'reliability'],
                            ['label' => 'Modern Design', 'value' => 'design'],
                            ['label' => 'Strong Support', 'value' => 'support'],
                        ]
                    ],
                    ['type' => 'text', 'label' => 'How much would you be willing to pay monthly for this new solution?', 'name' => 'willingness_to_pay'],
                ]),
            ],
            [
                'title' => 'Academic Peer Review',
                'description' => 'Evaluate and review research papers and academic work.',
                'icon' => 'fa-graduation-cap',
                'color' => 'bg-purple-50 text-purple-600',
                'category' => 'academic',
                'json_schema' => json_encode([
                    ['type' => 'header', 'label' => 'Peer Review Form'],
                    [
                        'type' => 'radio-group',
                        'label' => 'How would you rate the originality of this work?',
                        'name' => 'originality',
                        'values' => [
                            ['label' => 'Excellent', 'value' => '5'],
                            ['label' => 'Good', 'value' => '4'],
                            ['label' => 'Average', 'value' => '3'],
                            ['label' => 'Poor', 'value' => '2'],
                        ]
                    ],
                    [
                        'type' => 'radio-group',
                        'label' => 'How would you rate the scientific rigor and methodology?',
                        'name' => 'rigor',
                        'values' => [
                            ['label' => 'Excellent', 'value' => '5'],
                            ['label' => 'Good', 'value' => '4'],
                            ['label' => 'Average', 'value' => '3'],
                            ['label' => 'Poor', 'value' => '2'],
                        ]
                    ],
                    [
                        'type' => 'radio-group',
                        'label' => 'How would you rate the clarity of writing and data presentation?',
                        'name' => 'clarity',
                        'values' => [
                            ['label' => 'Excellent', 'value' => '5'],
                            ['label' => 'Good', 'value' => '4'],
                            ['label' => 'Average', 'value' => '3'],
                            ['label' => 'Poor', 'value' => '2'],
                        ]
                    ],
                    ['type' => 'textarea', 'label' => 'Detailed Comments and Suggestions for the Authors', 'name' => 'author_comments'],
                    ['type' => 'textarea', 'label' => 'Confidential Comments for the Editor (Optional)', 'name' => 'editor_comments'],
                    [
                        'type' => 'radio-group',
                        'label' => 'What is your final recommendation?',
                        'name' => 'recommendation',
                        'values' => [
                            ['label' => 'Accept without revisions', 'value' => 'accept'],
                            ['label' => 'Accept with minor revisions', 'value' => 'minor'],
                            ['label' => 'Reconsider after major revisions', 'value' => 'major'],
                            ['label' => 'Reject', 'value' => 'reject'],
                        ]
                    ],
                ]),
            ],
            [
                'title' => 'Product Feedback',
                'description' => 'Collect user opinions to improve your product\'s features and design.',
                'icon' => 'fa-box-open',
                'color' => 'bg-blue-50 text-blue-600',
                'category' => 'others',
                'json_schema' => json_encode([
                    ['type' => 'header', 'label' => 'Product Feedback'],
                    [
                        'type' => 'radio-group',
                        'label' => 'How easy was it to navigate and use the product?',
                        'name' => 'ease_of_use',
                        'values' => [
                            ['label' => 'Very Easy', 'value' => '5'],
                            ['label' => 'Somewhat Easy', 'value' => '4'],
                            ['label' => 'Neutral', 'value' => '3'],
                            ['label' => 'Difficult', 'value' => '2'],
                            ['label' => 'Very Difficult', 'value' => '1'],
                        ]
                    ],
                    [
                        'type' => 'checkbox-group',
                        'label' => 'What did you like most about the product?',
                        'name' => 'likes',
                        'values' => [
                            ['label' => 'Speed & Performance', 'value' => 'speed'],
                            ['label' => 'Price & Value', 'value' => 'value'],
                            ['label' => 'User Interface (UI)', 'value' => 'ui'],
                            ['label' => 'Specific Features', 'value' => 'features'],
                            ['label' => 'Customer Support', 'value' => 'support'],
                        ]
                    ],
                    [
                        'type' => 'checkbox-group',
                        'label' => 'Have you experienced any bugs or usability issues?',
                        'name' => 'bugs_experienced',
                        'values' => [
                            ['label' => 'None, it worked perfectly', 'value' => 'none'],
                            ['label' => 'App crashes / freezes', 'value' => 'crash'],
                            ['label' => 'Slow loading times', 'value' => 'slow'],
                            ['label' => 'Payment or checkout issues', 'value' => 'payment'],
                            ['label' => 'UI glitches or overlaps', 'value' => 'glitch'],
                        ]
                    ],
                    [
                        'type' => 'radio-group',
                        'label' => 'How would you rate the value for money of this product?',
                        'name' => 'value_rating',
                        'values' => [
                            ['label' => 'Excellent', 'value' => '5'],
                            ['label' => 'Good', 'value' => '4'],
                            ['label' => 'Fair', 'value' => '3'],
                            ['label' => 'Poor', 'value' => '2'],
                        ]
                    ],
                    ['type' => 'textarea', 'label' => 'What feature would you like to see added in the future?', 'name' => 'requested_feature'],
                ]),
            ],
            [
                'title' => 'Post-Event Evaluation',
                'description' => 'See what attendees liked and how to improve your next event.',
                'icon' => 'fa-calendar-check',
                'color' => 'bg-rose-50 text-rose-600',
                'category' => 'others',
                'json_schema' => json_encode([
                    ['type' => 'header', 'label' => 'Post-Event Feedback'],
                    [
                        'type' => 'radio-group',
                        'label' => 'Did the event meet your overall expectations?',
                        'name' => 'expectations',
                        'values' => [
                            ['label' => 'Exceeded Expectations', 'value' => '5'],
                            ['label' => 'Met Expectations', 'value' => '3'],
                            ['label' => 'Below Expectations', 'value' => '1'],
                        ]
                    ],
                    [
                        'type' => 'radio-group',
                        'label' => 'How would you rate the quality of the speakers or presentations?',
                        'name' => 'speaker_quality',
                        'values' => [
                            ['label' => 'Excellent', 'value' => '5'],
                            ['label' => 'Good', 'value' => '4'],
                            ['label' => 'Fair', 'value' => '3'],
                            ['label' => 'Poor', 'value' => '2'],
                        ]
                    ],
                    [
                        'type' => 'radio-group',
                        'label' => 'How would you rate the event organization and venue?',
                        'name' => 'venue_quality',
                        'values' => [
                            ['label' => 'Excellent', 'value' => '5'],
                            ['label' => 'Good', 'value' => '4'],
                            ['label' => 'Fair', 'value' => '3'],
                            ['label' => 'Poor', 'value' => '2'],
                        ]
                    ],
                    [
                        'type' => 'checkbox-group',
                        'label' => 'Which sessions did you find most valuable?',
                        'name' => 'valuable_sessions',
                        'values' => [
                            ['label' => 'Keynote Speech', 'value' => 'keynote'],
                            ['label' => 'Panel Discussions', 'value' => 'panels'],
                            ['label' => 'Networking Session', 'value' => 'networking'],
                            ['label' => 'Technical Workshops', 'value' => 'workshops'],
                        ]
                    ],
                    ['type' => 'textarea', 'label' => 'What was your favorite part of the event?', 'name' => 'favorite_part'],
                    ['type' => 'textarea', 'label' => 'Do you have any suggestions for our next event?', 'name' => 'suggestions'],
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
