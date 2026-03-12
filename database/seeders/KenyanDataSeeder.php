<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Organization;
use App\Models\Independent;
use App\Models\Survey;
use App\Models\Question;
use App\Models\Response;
use App\Models\Answer;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\SurveyType;
use App\Enums\SurveyStatus;

class KenyanDataSeeder extends Seeder
{
    public function run(): void
    {
        $kenyanNames = [
            'Wanjiku Kamau',
            'Otieno Omondi',
            'Kiprop Chirchir',
            'Amina Mohamed',
            'Muthoni Njeri',
            'Karanja Mwangi',
            'Akinyi Odhiambo',
            'Chebet Kiplagat',
            'Wambui Njoroge',
            'Mutua Musyoka',
            'Njeri Thuku',
            'Ochieng Odinga',
            'Kiprotich arap Chirchir',
            'Fatuma Mohamed',
            'Nyambura Maina',
            'Kamau Mbugua',
            'Atieno Owuor',
            'Kipkemboi Ruto',
            'Muthoni Kariuki',
            'Otieno Oketch',
            'Cherono Kiplagat',
            'Wairimu Gakuru',
            'Musyoka Mutula',
            'Akinyi Okech',
            'Juma Jux'
        ];

        /* Fixed size of 25 for emails corresponding to names */
        $kenyanEmails = [
            'wanjiku.kamau@email.com',
            'otieno.omondi@email.com',
            'kiprop.chirchir@email.com',
            'amina.mohamed@email.com',
            'muthoni.njeri@email.com',
            'karanja.mwangi@email.com',
            'akinyi.odhiambo@email.com',
            'chebet.kiplagat@email.com',
            'wambui.njoroge@email.com',
            'mutua.musyoka@email.com',
            'njeri.thuku@email.com',
            'ochieng.odinga@email.com',
            'kiprotich.chirchir@email.com',
            'fatuma.mohamed@email.com',
            'nyambura.maina@email.com',
            'kamau.mbugua@email.com',
            'atieno.owuor@email.com',
            'kipkemboi.ruto@email.com',
            'muthoni.kariuki@email.com',
            'otieno.oketch@email.com',
            'cherono.kiplagat@email.com',
            'wairimu.gakuru@email.com',
            'musyoka.mutula@email.com',
            'akinyi.okech@email.com',
            'juma.jux@email.com'
        ];

        $kenyanCompanies = [
            'Safaricom PLC',
            'Equity Bank Kenya',
            'Jumia Kenya',
            'M-Kopa Solar',
            'Twiga Foods',
            'Copia Kenya',
            'Little Cab',
            'Sendy Kenya',
            'Lori Systems',
            'M-Kopa Solar Kenya',
            'Telkom Kenya',
            'Airtel Kenya',
            'Kenya Power',
            'KCB Group',
            'Cooperative Bank',
            'Standard Chartered Kenya',
            'Barclays Bank Kenya',
            'NCBA Bank',
            'Diamond Trust Bank',
            'I&M Bank'
        ];

        $kenyanUniversities = [
            'University of Nairobi',
            'Kenyatta University',
            'Strathmore University',
            'Jomo Kenyatta University',
            'Egerton University',
            'Moi University',
            'Maseno University',
            'Technical University of Kenya',
            'Dedan Kimathi University',
            'Masinde Muliro University',
            'Kabarak University',
            'Laikipia University',
            'Meru University',
            'Chuka University',
            'Karatina University',
            'Maasai Mara University',
            'Rongo University',
            'Taita Taveta University',
            'South Eastern Kenya University',
            'Umma University'
        ];

        $researchAreas = [
            'Computer Science',
            'Agricultural Economics',
            'Public Health',
            'Environmental Science',
            'Business Administration',
            'Education',
            'Engineering',
            'Medicine',
            'Law',
            'Mathematics',
            'Physics',
            'Chemistry',
            'Biology',
            'Sociology',
            'Psychology',
            'Political Science',
            'Economics',
            'History',
            'Literature',
            'Philosophy'
        ];

        $surveyTitles = [
            'Customer Satisfaction Survey for M-Pesa Services',
            'Research on Mobile Banking Adoption in Rural Kenya',
            'Product Feedback for Jumia Kenya E-commerce',
            'Political Opinion Poll for Nairobi Gubernatorial Elections',
            'Academic Study on Climate Change Impact on Kenyan Agriculture',
            'Market Research for Twiga Foods Distribution Network',
            'Employee Engagement Survey at Safaricom PLC',
            'Student Satisfaction Survey at University of Nairobi',
            'Public Opinion on Kenya\'s COVID-19 Response',
            'Research on Solar Energy Adoption in Rural Kenya',
            'Customer Experience Survey for Kenya Airways',
            'Market Analysis for M-Kopa Solar Products',
            'Academic Research on Digital Literacy in Kenyan Schools',
            'Political Survey for 2027 General Elections',
            'Customer Feedback for Equity Bank Mobile App',
            'Research on E-commerce Growth in Kenya',
            'Public Opinion on Corruption in Kenya',
            'Market Research for Copia Kenya Retail Chain',
            'Academic Study on Mental Health in Kenyan Universities',
            'Customer Satisfaction Survey for Telkom Kenya',
            'Research on Fintech Adoption in Kenya',
            'Political Survey for Constitutional Reform',
            'Market Analysis for Little Cab Ride-Hailing',
            'Academic Research on Climate Change in Kenyan Coastal Areas',
            'Customer Feedback for Sendy Logistics Services',
            'Research on Digital Payment Systems in Kenya'
        ];

        $surveyDescriptions = [
            'Help us improve our mobile money services',
            'Understanding barriers to mobile banking adoption in rural areas',
            'Tell us about your online shopping experience',
            'Who would you vote for in the upcoming elections?',
            'Studying the effects of climate change on farming communities',
            'Improving our supply chain for fresh produce',
            'Internal survey to measure employee satisfaction',
            'Evaluating student experience and satisfaction',
            'Public perception of government pandemic response',
            'Assessing the impact of solar energy solutions in off-grid communities',
            'Measuring customer satisfaction with our airline services',
            'Understanding market potential for solar products',
            'Evaluating digital literacy levels in primary and secondary schools',
            'Gauging public opinion on constitutional reform process',
            'Feedback on our new mobile banking application',
            'Analyzing the growth trajectory of e-commerce in Kenya',
            'Public perception of corruption levels in government institutions',
            'Market research for expanding our retail footprint',
            'Studying mental health challenges among university students',
            'Customer experience with our telecommunications services',
            'Research on fintech adoption rates among different demographics',
            'Public opinion on proposed constitutional amendments',
            'Market analysis for ride-hailing services in major cities',
            'Studying climate change effects on coastal communities and tourism',
            'Customer satisfaction with our logistics and delivery services',
            'Research on the adoption of digital payment systems across Kenya'
        ];

        $questions = [
            ['How satisfied are you with our service?', 'radio', json_encode(['Very Satisfied', 'Satisfied', 'Neutral', 'Dissatisfied', 'Very Dissatisfied'])],
            ['How often do you use our service?', 'radio', json_encode(['Daily', 'Weekly', 'Monthly', 'Rarely', 'Never'])],
            ['What features do you like most?', 'checkbox', json_encode(['Ease of use', 'Speed', 'Reliability', 'Customer support', 'Pricing', 'Security'])],
            ['How likely are you to recommend us?', 'integer', null],
            ['What is your age group?', 'radio', json_encode(['18-25', '26-35', '36-45', '46-55', '55+'])],
            ['Which county do you live in?', 'radio', json_encode(['Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret', 'Thika', 'Nyeri', 'Machakos'])],
            ['How would you rate our customer service?', 'radio', json_encode(['Excellent', 'Good', 'Average', 'Poor', 'Very Poor'])],
            ['What improvements would you suggest?', 'text', null],
            ['How did you hear about us?', 'radio', json_encode(['Friend', 'Social Media', 'Advertisement', 'Search Engine', 'Radio', 'TV'])],
            ['Would you use our service again?', 'radio', json_encode(['Definitely', 'Probably', 'Not Sure', 'Probably Not', 'Definitely Not'])],
            ['What is your highest level of education?', 'radio', json_encode(['Primary', 'Secondary', 'College', 'University', 'Postgraduate'])],
            ['What is your monthly income range?', 'radio', json_encode(['Below 20,000', '20,000-50,000', '50,000-100,000', '100,000-200,000', 'Above 200,000'])],
            ['How satisfied are you with our product quality?', 'radio', json_encode(['Very Satisfied', 'Satisfied', 'Neutral', 'Dissatisfied', 'Very Dissatisfied'])],
            ['Which payment methods do you prefer?', 'checkbox', json_encode(['M-Pesa', 'Airtel Money', 'Equitel', 'TKash', 'Credit Card', 'Debit Card'])],
            ['How often do you use our mobile app?', 'radio', json_encode(['Daily', 'Several times a week', 'Once a week', 'Rarely', 'Never'])],
            ['What challenges do you face with our service?', 'text', null],
            ['How would you rate our website usability?', 'radio', json_encode(['Excellent', 'Good', 'Average', 'Poor', 'Very Poor'])],
            ['What additional services would you like?', 'text', null],
            ['How likely are you to switch to a competitor?', 'radio', json_encode(['Very Likely', 'Somewhat Likely', 'Neutral', 'Unlikely', 'Very Unlikely'])],
            ['What is your occupation?', 'radio', json_encode(['Student', 'Employed', 'Self-employed', 'Unemployed', 'Retired'])],
            ['How satisfied are you with our delivery speed?', 'radio', json_encode(['Very Satisfied', 'Satisfied', 'Neutral', 'Dissatisfied', 'Very Dissatisfied'])],
            ['Which device do you primarily use?', 'radio', json_encode(['Smartphone', 'Feature Phone', 'Tablet', 'Computer', 'Other'])],
            ['What improvements would make our service better?', 'text', null],
            ['How would you rate our customer support?', 'radio', json_encode(['Excellent', 'Good', 'Average', 'Poor', 'Very Poor'])],
            ['What is your preferred communication channel?', 'radio', json_encode(['Email', 'SMS', 'Phone Call', 'Social Media', 'In-person'])],
            ['How satisfied are you with our pricing?', 'radio', json_encode(['Very Satisfied', 'Satisfied', 'Neutral', 'Dissatisfied', 'Very Dissatisfied'])],
            ['What features would you like to see added?', 'text', null],
            ['How likely are you to recommend us to a friend?', 'radio', json_encode(['Definitely', 'Probably', 'Not Sure', 'Probably Not', 'Definitely Not'])],
            ['What is your primary reason for using our service?', 'radio', json_encode(['Convenience', 'Price', 'Quality', 'Brand Trust', 'Availability'])],
            ['How satisfied are you with our overall experience?', 'radio', json_encode(['Very Satisfied', 'Satisfied', 'Neutral', 'Dissatisfied', 'Very Dissatisfied'])],
            ['What would make you choose us again?', 'text', null],
            ['How do you prefer to receive updates?', 'radio', json_encode(['Email', 'SMS', 'Push Notification', 'Social Media', 'None'])]
        ];

        $answerValues = [
            'Satisfied',
            '8',
            'Ease of use,Speed',
            'Nairobi',
            'Good',
            'The service is reliable',
            'Weekly',
            'Definitely',
            'I would like to see more payment options',
            'Friend',
            'University',
            '50,000-100,000',
            'Very Satisfied',
            'M-Pesa,Airtel Money',
            'Several times a week',
            'The app sometimes crashes',
            'Average',
            'Mobile App',
            'Very Likely',
            'Employed',
            'Very Satisfied',
            'Smartphone',
            'Faster delivery times',
            'Good',
            'Email',
            'Satisfied',
            'Better customer support',
            'Very Likely',
            'Price',
            'Very Satisfied',
            '24/7 support',
            'Very Likely',
            'Convenience',
            'Very Satisfied',
            'More payment options',
            'SMS',
            'Definitely',
            'Quality and reliability',
            'Email',
            'Satisfied',
            'Lower prices',
            'Push Notification'
        ];

        $users = [];
        $password = Hash::make('Kenya@2026!');

        // Ensure 25 users as per script logic:
        // 0-9: Orgs (10 users)
        // 10-17: Independents (8 users)
        // 18-24: Respondents (7 users)
        foreach ($kenyanNames as $i => $name) {
            $role = UserRole::Respondent;
            if ($i < 10)
                $role = UserRole::Organization;
            elseif ($i < 18)
                $role = UserRole::Independent;

            $users[] = User::updateOrCreate(
                ['email' => $kenyanEmails[$i]],
                [
                    'name' => $name,
                    'password' => $password,
                    'role' => $role,
                    'status' => UserStatus::Active,
                ]
            );
        }

        // 20 Companies
        for ($i = 0; $i < 20; $i++) {
            Organization::updateOrCreate(
                ['name' => $kenyanCompanies[$i]],
                [
                    'user_id' => $users[$i % 10]->id,
                ]
            );
        }

        // 20 Independents
        for ($i = 0; $i < 20; $i++) {
            Independent::updateOrCreate(
                ['name' => $kenyanNames[($i + 8) % count($kenyanNames)]],
                [
                    'user_id' => $users[10 + ($i % 8)]->id, // Assign to independant users
                    'institution' => $kenyanUniversities[$i],
                    'research_area' => $researchAreas[$i],
                ]
            );
        }

        // Reload models to ensure relations are accessible and counts are correct
        $orgs = Organization::all();
        $inds = Independent::all();
        $respondents = User::where('role', UserRole::Respondent->value)->get();

        // Surveys
        $createdSurveys = [];
        for ($i = 0; $i < count($surveyTitles); $i++) {
            $isOrg = $i % 2 === 0;

            if ($isOrg && $orgs->count() > 0) {
                $creatorProfile = $orgs[$i % $orgs->count()];
                $creatorId = $creatorProfile->user_id;
                $orgId = $creatorProfile->id;
                $indId = null;
            } elseif (!$isOrg && $inds->count() > 0) {
                $creatorProfile = $inds[$i % $inds->count()];
                $creatorId = $creatorProfile->user_id;
                $orgId = null;
                $indId = $creatorProfile->id;
            } else {
                continue; // Skip if no profiles exist
            }

            $createdSurveys[] = Survey::updateOrCreate(
                ['title' => $surveyTitles[$i]],
                [
                    'description' => $surveyDescriptions[$i],
                    'category' => 'General',
                    'type' => SurveyType::Public ,
                    'status' => SurveyStatus::Active,
                    'created_by' => $creatorId,
                    'organization_id' => $orgId,
                    'independent_id' => $indId,
                ]
            );
        }

        // Questions
        $createdQuestions = [];
        for ($i = 0; $i < min(30, count($questions)); $i++) {
            if (empty($createdSurveys))
                break;
            $survey = $createdSurveys[$i % count($createdSurveys)];

            $createdQuestions[] = Question::updateOrCreate(
                [
                    'survey_id' => $survey->id,
                    'text' => $questions[$i][0],
                ],
                [
                    'type' => $questions[$i][1],
                    'options' => $questions[$i][2], // Just pass the encoded JSON string as provided
                    'required' => ($i % 4 == 0) ? false : true,
                    'position' => ($i % 10) + 1,
                ]
            );
        }

        // Responses
        $createdResponses = [];
        for ($i = 0; $i < 30; $i++) {
            if (empty($createdSurveys) || $respondents->isEmpty())
                break;
            $survey = $createdSurveys[$i % count($createdSurveys)];
            $respondent = $respondents[$i % count($respondents)];

            $createdResponses[] = Response::updateOrCreate(
                [
                    'survey_id' => $survey->id,
                    'respondent_id' => $respondent->id,
                ]
            );
        }

        // Answers
        for ($i = 0; $i < 50; $i++) {
            if (empty($createdResponses))
                break;
            $response = $createdResponses[$i % count($createdResponses)];

            // Let's bind an answer to the first question of this response's survey just as mock data
            $surveyQuestions = $response->survey->questions;
            if ($surveyQuestions->count() > 0) {
                $qIdx = $i % $surveyQuestions->count();
                $question = $surveyQuestions[$qIdx];
                Answer::updateOrCreate(
                    [
                        'response_id' => $response->id,
                        'question_id' => $question->id,
                    ],
                    [
                        'value' => $answerValues[$i % count($answerValues)],
                    ]
                );
            }
        }
    }
}
