<?php
// fresh_setup.php - Corrected with proper admin email
require_once 'rb.php';

// Delete existing database if it exists
if (file_exists('kmsurveytool.db')) {
    unlink('kmsurveytool.db');
    echo "Deleted existing database file.<br>";
}

// Setup fresh database
R::setup('sqlite:kmsurveytool.db');

// Create tables
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

// Create admin user with CORRECT email
$admin = R::dispense('users');
$admin->name = 'Admin User';
$admin->email = 'admin@kmsurveytool.com'; // CORRECTED EMAIL
$admin->password = password_hash('Kenya@254', PASSWORD_DEFAULT);
$admin->role = 'admin';
$admin->status = 'active';
$admin->created_at = date('Y-m-d H:i:s');
$adminId = R::store($admin);

echo "Admin user created successfully!<br>";

// Verify admin was created
$verifyAdmin = R::load('users', $adminId);
if ($verifyAdmin && $verifyAdmin->email === 'admin@kmsurveytool.com') {
    echo "Admin verification: SUCCESS<br>";
    
    // Test password verification
    if (password_verify('Kenya@254', $verifyAdmin->password)) {
        echo "Password verification: SUCCESS<br>";
    } else {
        echo "Password verification: FAILED<br>";
    }
} else {
    echo "Admin verification: FAILED<br>";
}

// Create sample users
$users = [
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

echo "Sample users created successfully!<br>";

// Create sample organization
$org = R::dispense('organizations');
$org->user_id = 2; // John Organization
$org->name = 'Tech Solutions Ltd';
$org->payment_status = 'paid';
$org->subscription_expiry = date('Y-m-d H:i:s', strtotime('+1 year'));
R::store($org);

// Create sample independent researcher
$ind = R::dispense('independents');
$ind->user_id = 3; // Sarah Independent
$ind->name = 'Dr. Sarah Johnson';
$ind->institution = 'University of Technology';
$ind->research_area = 'Computer Science';
$ind->payment_status = 'paid';
$ind->subscription_expiry = date('Y-m-d H:i:s', strtotime('+6 months'));
R::store($ind);

echo "Sample organization and independent created!<br>";

// Create sample survey
$survey = R::dispense('surveys');
$survey->organization_id = 1;
$survey->title = 'Customer Satisfaction Survey';
$survey->description = 'Help us improve our services';
$survey->category = 'Marketing';
$survey->type = 'public';
$survey->status = 'active';
$surveyId = R::store($survey);

// Create sample question
$question = R::dispense('questions');
$question->survey_id = $surveyId;
$question->text = 'How satisfied are you with our service?';
$question->type = 'radio';
$question->options = json_encode(['Very Satisfied', 'Satisfied', 'Neutral', 'Dissatisfied', 'Very Dissatisfied']);
$question->required = 1;
$question->position = 1;
R::store($question);

echo "Sample survey and question created!<br>";

echo "<h3>Database setup completed successfully!</h3>";
echo "<p><strong>Admin Credentials:</strong></p>";
echo "<ul>";
echo "<li>Email: admin@kmsurveytool.com</li>";
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