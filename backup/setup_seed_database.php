<?php
// setup_seed_database.php
require_once 'rb.php';

// Setup database connection
R::setup('sqlite:kmsurveytool.db');

// Drop existing tables if they exist (for fresh setup)
$tables = ['users', 'organizations', 'independents', 'surveys', 'questions', 'responses', 'answers', 'payments'];
foreach ($tables as $table) {
    R::exec("DROP TABLE IF EXISTS $table");
}

// Create tables with proper schema
R::exec('CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role TEXT CHECK(role IN ("admin","organization","independent","respondent")) NOT NULL,
    status TEXT DEFAULT "pending",
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)');

R::exec('CREATE TABLE organizations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    payment_status TEXT DEFAULT "unpaid",
    subscription_expiry DATETIME,
    FOREIGN KEY(user_id) REFERENCES users(id)
)');

R::exec('CREATE TABLE independents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    institution TEXT,
    research_area TEXT,
    payment_status TEXT DEFAULT "unpaid",
    subscription_expiry DATETIME,
    FOREIGN KEY(user_id) REFERENCES users(id)
)');

R::exec('CREATE TABLE surveys (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER,
    independent_id INTEGER,
    title TEXT NOT NULL,
    description TEXT,
    category TEXT CHECK(category IN ("Marketing","Academic","Product","Political")) DEFAULT "Academic",
    type TEXT CHECK(type IN ("public","invitation")) DEFAULT "public",
    status TEXT DEFAULT "draft",
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(organization_id) REFERENCES organizations(id),
    FOREIGN KEY(independent_id) REFERENCES independents(id)
)');

R::exec('CREATE TABLE questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    survey_id INTEGER NOT NULL,
    text TEXT NOT NULL,
    type TEXT CHECK(type IN ("text","radio","checkbox","matrix","geo","video","audio","integer","email","tel")),
    options TEXT,
    required INTEGER DEFAULT 0,
    position INTEGER,
    FOREIGN KEY(survey_id) REFERENCES surveys(id)
)');

R::exec('CREATE TABLE responses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    survey_id INTEGER NOT NULL,
    respondent_id INTEGER NOT NULL,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(survey_id) REFERENCES surveys(id),
    FOREIGN KEY(respondent_id) REFERENCES users(id)
)');

R::exec('CREATE TABLE answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    response_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    value TEXT,
    FOREIGN KEY(response_id) REFERENCES responses(id),
    FOREIGN KEY(question_id) REFERENCES questions(id)
)');

R::exec('CREATE TABLE payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER,
    independent_id INTEGER,
    amount REAL NOT NULL,
    method TEXT CHECK(method IN ("paypal","intasend")),
    status TEXT DEFAULT "pending",
    transaction_id TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(organization_id) REFERENCES organizations(id),
    FOREIGN KEY(independent_id) REFERENCES independents(id)
)');

echo "Database tables created successfully!<br>";

// Seed Users table
$users = [
    [
        'name' => 'Admin User',
        'email' => 'admin@kmsurveytool.com',
        'password' => password_hash('Kenya@254', PASSWORD_DEFAULT),
        'role' => 'admin',
        'status' => 'active'
    ],
    [
        'name' => 'John Organization',
        'email' => 'john@company.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'role' => 'organization',
        'status' => 'active'
    ],
    [
        'name' => 'Sarah Independent',
        'email' => 'sarah@university.edu',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'role' => 'independent',
        'status' => 'active'
    ],
    [
        'name' => 'Mike Respondent',
        'email' => 'mike@email.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'role' => 'respondent',
        'status' => 'active'
    ],
    [
        'name' => 'Jane Respondent',
        'email' => 'jane@email.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'role' => 'respondent',
        'status' => 'active'
    ]
];

foreach ($users as $userData) {
    $user = R::dispense('users');
    foreach ($userData as $key => $value) {
        $user->$key = $value;
    }
    R::store($user);
}
echo "Users table seeded with 5 records!<br>";

// Seed Organizations table
$organizations = [
    [
        'user_id' => 2, // John Organization
        'name' => 'Tech Solutions Ltd',
        'payment_status' => 'paid',
        'subscription_expiry' => date('Y-m-d H:i:s', strtotime('+1 year'))
    ],
    [
        'user_id' => 2, // John Organization (another org for same user)
        'name' => 'Marketing Pro Inc',
        'payment_status' => 'unpaid'
    ],
    [
        'user_id' => 2, // John Organization (third org)
        'name' => 'Research Group LLC',
        'payment_status' => 'pending'
    ]
];

foreach ($organizations as $orgData) {
    $org = R::dispense('organizations');
    foreach ($orgData as $key => $value) {
        $org->$key = $value;
    }
    R::store($org);
}
echo "Organizations table seeded with 3 records!<br>";

// Seed Independents table
$independents = [
    [
        'user_id' => 3, // Sarah Independent
        'name' => 'Dr. Sarah Johnson',
        'institution' => 'University of Technology',
        'research_area' => 'Computer Science',
        'payment_status' => 'paid',
        'subscription_expiry' => date('Y-m-d H:i:s', strtotime('+6 months'))
    ],
    [
        'user_id' => 3, // Sarah Independent (another profile)
        'name' => 'Sarah Johnson PhD',
        'institution' => 'Tech Research Institute',
        'research_area' => 'Data Science',
        'payment_status' => 'unpaid'
    ],
    [
        'user_id' => 3, // Sarah Independent (third profile)
        'name' => 'S. Johnson Researcher',
        'institution' => 'Science Academy',
        'research_area' => 'Artificial Intelligence',
        'payment_status' => 'pending'
    ]
];

foreach ($independents as $indData) {
    $ind = R::dispense('independents');
    foreach ($indData as $key => $value) {
        $ind->$key = $value;
    }
    R::store($ind);
}
echo "Independents table seeded with 3 records!<br>";

// Seed Surveys table
$surveys = [
    [
        'organization_id' => 1,
        'title' => 'Customer Satisfaction Survey',
        'description' => 'Help us improve our services',
        'category' => 'Marketing',
        'type' => 'public',
        'status' => 'active'
    ],
    [
        'organization_id' => 2,
        'title' => 'Product Feedback',
        'description' => 'Tell us about your experience',
        'category' => 'Product',
        'type' => 'invitation',
        'status' => 'active'
    ],
    [
        'independent_id' => 1,
        'title' => 'Academic Research Study',
        'description' => 'Participate in our university research',
        'category' => 'Academic',
        'type' => 'public',
        'status' => 'active'
    ],
    [
        'independent_id' => 2,
        'title' => 'Technology Usage Patterns',
        'description' => 'Study on how people use technology',
        'category' => 'Academic',
        'type' => 'public',
        'status' => 'draft'
    ],
    [
        'organization_id' => 3,
        'title' => 'Political Opinion Poll',
        'description' => 'Share your views on current issues',
        'category' => 'Political',
        'type' => 'public',
        'status' => 'active'
    ]
];

foreach ($surveys as $surveyData) {
    $survey = R::dispense('surveys');
    foreach ($surveyData as $key => $value) {
        $survey->$key = $value;
    }
    R::store($survey);
}
echo "Surveys table seeded with 5 records!<br>";

// Seed Questions table
$questions = [
    [
        'survey_id' => 1,
        'text' => 'How satisfied are you with our service?',
        'type' => 'radio',
        'options' => json_encode(['Very Satisfied', 'Satisfied', 'Neutral', 'Dissatisfied', 'Very Dissatisfied']),
        'required' => 1,
        'position' => 1
    ],
    [
        'survey_id' => 1,
        'text' => 'How likely are you to recommend us?',
        'type' => 'integer',
        'required' => 1,
        'position' => 2
    ],
    [
        'survey_id' => 2,
        'text' => 'What features do you like most?',
        'type' => 'checkbox',
        'options' => json_encode(['Design', 'Performance', 'Price', 'Support']),
        'required' => 0,
        'position' => 1
    ],
    [
        'survey_id' => 3,
        'text' => 'What is your age group?',
        'type' => 'radio',
        'options' => json_encode(['18-25', '26-35', '36-45', '46-55', '55+']),
        'required' => 1,
        'position' => 1
    ],
    [
        'survey_id' => 4,
        'text' => 'How many hours do you use technology daily?',
        'type' => 'integer',
        'required' => 1,
        'position' => 1
    ]
];

foreach ($questions as $questionData) {
    $question = R::dispense('questions');
    foreach ($questionData as $key => $value) {
        $question->$key = $value;
    }
    R::store($question);
}
echo "Questions table seeded with 5 records!<br>";

// Seed Responses table
$responses = [
    [
        'survey_id' => 1,
        'respondent_id' => 4
    ],
    [
        'survey_id' => 1,
        'respondent_id' => 5
    ],
    [
        'survey_id' => 2,
        'respondent_id' => 4
    ],
    [
        'survey_id' => 3,
        'respondent_id' => 5
    ],
    [
        'survey_id' => 4,
        'respondent_id' => 4
    ]
];

foreach ($responses as $responseData) {
    $response = R::dispense('responses');
    foreach ($responseData as $key => $value) {
        $response->$key = $value;
    }
    R::store($response);
}
echo "Responses table seeded with 5 records!<br>";

// Seed Answers table
$answers = [
    [
        'response_id' => 1,
        'question_id' => 1,
        'value' => 'Satisfied'
    ],
    [
        'response_id' => 1,
        'question_id' => 2,
        'value' => '8'
    ],
    [
        'response_id' => 2,
        'question_id' => 1,
        'value' => 'Very Satisfied'
    ],
    [
        'response_id' => 2,
        'question_id' => 2,
        'value' => '9'
    ],
    [
        'response_id' => 3,
        'question_id' => 3,
        'value' => 'Design,Performance'
    ]
];

foreach ($answers as $answerData) {
    $answer = R::dispense('answers');
    foreach ($answerData as $key => $value) {
        $answer->$key = $value;
    }
    R::store($answer);
}
echo "Answers table seeded with 5 records!<br>";

// Seed Payments table
$payments = [
    [
        'organization_id' => 1,
        'amount' => 99.99,
        'method' => 'paypal',
        'status' => 'completed',
        'transaction_id' => 'PAYPAL123456'
    ],
    [
        'independent_id' => 1,
        'amount' => 49.99,
        'method' => 'intasend',
        'status' => 'completed',
        'transaction_id' => 'INTASEND789'
    ],
    [
        'organization_id' => 2,
        'amount' => 149.99,
        'method' => 'paypal',
        'status' => 'pending'
    ],
    [
        'independent_id' => 2,
        'amount' => 29.99,
        'method' => 'intasend',
        'status' => 'failed'
    ],
    [
        'organization_id' => 3,
        'amount' => 199.99,
        'method' => 'paypal',
        'status' => 'completed',
        'transaction_id' => 'PAYPAL987654'
    ]
];

foreach ($payments as $paymentData) {
    $payment = R::dispense('payments');
    foreach ($paymentData as $key => $value) {
        $payment->$key = $value;
    }
    R::store($payment);
}
echo "Payments table seeded with 5 records!<br>";

echo "<h3>Database setup and seeding completed successfully!</h3>";
echo "<p><strong>Default Admin Account:</strong></p>";
echo "<ul>";
echo "<li>Email: admin@kmsurvey.com</li>";
echo "<li>Password: Kenya@254</li>";
echo "</ul>";
echo "<p><strong>Sample Accounts:</strong></p>";
echo "<ul>";
echo "<li>Organization: john@company.com / password123</li>";
echo "<li>Independent: sarah@university.edu / password123</li>";
echo "<li>Respondent: mike@email.com / password123</li>";
echo "<li>Respondent: jane@email.com / password123</li>";
echo "</ul>";
?>