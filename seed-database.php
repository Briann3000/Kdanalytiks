<?php
// seed-database.php
require_once 'rb.php';

// Delete existing database if it exists
if (file_exists('kmsurveytool.db')) {
    unlink('kmsurveytool.db');
    echo "Deleted existing database file.<br>";
}

// Setup fresh database
R::setup('sqlite:kmsurveytool.db');

// Create tables
R::exec("CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role TEXT CHECK(role IN ('admin','organization','independent','respondent')) NOT NULL,
    status TEXT DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

R::exec("CREATE TABLE organizations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    payment_status TEXT DEFAULT 'unpaid',
    subscription_expiry DATETIME,
    FOREIGN KEY(user_id) REFERENCES users(id)
)");

R::exec("CREATE TABLE independents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    institution TEXT,
    research_area TEXT,
    payment_status TEXT DEFAULT 'unpaid',
    subscription_expiry DATETIME,
    FOREIGN KEY(user_id) REFERENCES users(id)
)");

R::exec("CREATE TABLE surveys (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER,
    independent_id INTEGER,
    title TEXT NOT NULL,
    description TEXT,
    category TEXT CHECK(category IN ('Marketing','Academic','Product','Political')) DEFAULT 'Academic',
    type TEXT CHECK(type IN ('public','invitation')) DEFAULT 'public',
    status TEXT DEFAULT 'draft',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(organization_id) REFERENCES organizations(id),
    FOREIGN KEY(independent_id) REFERENCES independents(id)
)");

R::exec("CREATE TABLE questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    survey_id INTEGER NOT NULL,
    text TEXT NOT NULL,
    type TEXT CHECK(type IN ('text','radio','checkbox','matrix','geo','video','audio','integer','email','tel')),
    options TEXT,
    required INTEGER DEFAULT 0,
    position INTEGER,
    FOREIGN KEY(survey_id) REFERENCES surveys(id)
)");

R::exec("CREATE TABLE responses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    survey_id INTEGER NOT NULL,
    respondent_id INTEGER,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(survey_id) REFERENCES surveys(id),
    FOREIGN KEY(respondent_id) REFERENCES users(id)
)");

R::exec("CREATE TABLE answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    response_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    value TEXT,
    FOREIGN KEY(response_id) REFERENCES responses(id),
    FOREIGN KEY(question_id) REFERENCES questions(id)
)");

R::exec("CREATE TABLE payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER,
    independent_id INTEGER,
    amount REAL NOT NULL,
    method TEXT CHECK(method IN ('paypal','intasend')),
    status TEXT DEFAULT 'pending',
    transaction_id TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(organization_id) REFERENCES organizations(id),
    FOREIGN KEY(independent_id) REFERENCES independents(id)
)");

echo "Database tables created successfully!<br>";

// Seed Users table with 25 Kenyan names
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
    'Akinyi Okech'
];

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
    'akinyi.okech@email.com'
];

// Create admin user
$admin = R::dispense('users');
$admin->name = 'Admin User';
$admin->email = 'admin@kmsurveytool.com';
$admin->password = password_hash('Kenya@254', PASSWORD_DEFAULT);
$admin->role = 'admin';
$admin->status = 'active';
R::store($admin);
echo "Admin user created!<br>";

// Create 24 more users (mix of roles)
for ($i = 0; $i < 24; $i++) {
    $user = R::dispense('users');
    $user->name = $kenyanNames[$i];
    $user->email = $kenyanEmails[$i];
    $user->password = password_hash('password123', PASSWORD_DEFAULT);

    // Assign roles: 8 organizations, 8 independents, 8 respondents
    if ($i < 8) {
        $user->role = 'organization';
    } elseif ($i < 16) {
        $user->role = 'independent';
    } else {
        $user->role = 'respondent';
    }

    $user->status = 'active';
    R::store($user);
}
echo "Users table seeded with 25 records!<br>";

// Seed Organizations table with 20 Kenyan companies
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

for ($i = 0; $i < 20; $i++) {
    $org = R::dispense('organizations');
    $org->user_id = ($i % 8) + 2; // Users 2-9 are organizations
    $org->name = $kenyanCompanies[$i];
    $org->payment_status = ($i % 4 == 0) ? 'paid' : (($i % 4 == 1) ? 'pending' : 'unpaid');
    if ($org->payment_status == 'paid') {
        $org->subscription_expiry = date('Y-m-d H:i:s', strtotime('+' . rand(1, 12) . ' months'));
    }
    R::store($org);
}
echo "Organizations table seeded with 20 records!<br>";

// Seed Independents table with 20 Kenyan researchers
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

for ($i = 0; $i < 20; $i++) {
    $ind = R::dispense('independents');
    $ind->user_id = ($i % 8) + 10; // Users 10-17 are independents
    $ind->name = $kenyanNames[($i + 8) % count($kenyanNames)]; // Use different names
    $ind->institution = $kenyanUniversities[$i];
    $ind->research_area = $researchAreas[$i];
    $ind->payment_status = ($i % 2 == 0) ? 'paid' : 'unpaid';
    if ($ind->payment_status == 'paid') {
        $ind->subscription_expiry = date('Y-m-d H:i:s', strtotime('+' . rand(1, 6) . ' months'));
    }
    R::store($ind);
}
echo "Independents table seeded with 20 records!<br>";

// Seed Surveys table with 25 surveys
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

for ($i = 0; $i < 25; $i++) {
    $survey = R::dispense('surveys');

    // Alternate between organization, independent, and admin surveys
    if ($i < 8) {
        $survey->organization_id = $i + 1; // Organizations 1-8
    } elseif ($i < 16) {
        $survey->independent_id = ($i - 8) + 1; // Independents 1-8
    }
    // Admin surveys have both organization_id and independent_id as NULL

    $survey->title = $surveyTitles[$i];
    $survey->description = $surveyDescriptions[$i];

    // Assign categories
    $categories = ['Marketing', 'Academic', 'Product', 'Political'];
    $survey->category = $categories[$i % 4];

    $survey->type = ($i % 3 == 0) ? 'invitation' : 'public';
    $survey->status = ($i % 5 == 0) ? 'draft' : 'active';

    R::store($survey);
}
echo "Surveys table seeded with 25 records!<br>";

// Seed Questions table with 30 questions
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

for ($i = 0; $i < 30; $i++) {
    $question = R::dispense('questions');
    $question->survey_id = ($i % 25) + 1; // Assign to surveys 1-25
    $question->text = $questions[$i][0];
    $question->type = $questions[$i][1];
    $question->options = $questions[$i][2];
    $question->required = ($i % 4 == 0) ? 0 : 1;
    $question->position = ($i % 10) + 1;
    R::store($question);
}
echo "Questions table seeded with 30 records!<br>";

// Seed Responses table with 30 responses
for ($i = 0; $i < 30; $i++) {
    $response = R::dispense('responses');
    $response->survey_id = ($i % 25) + 1; // Assign to surveys 1-25
    $response->respondent_id = ($i % 8) + 18; // Users 18-25 are respondents
    R::store($response);
}
echo "Responses table seeded with 30 records!<br>";

// Seed Answers table with 50 answers
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


// Seed Answers table with 50 answers (continued)
for ($i = 0; $i < 50; $i++) {
    $answer = R::dispense('answers');
    $answer->response_id = ($i % 30) + 1; // Assign to responses 1-30
    $answer->question_id = ($i % 30) + 1; // Assign to questions 1-30
    $answer->value = $answerValues[$i % count($answerValues)];
    R::store($answer);
}
echo "Answers table seeded with 50 records!<br>";