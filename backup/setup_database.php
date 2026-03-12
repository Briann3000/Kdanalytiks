<?php
// Setup database for initialization
require_once 'rb.php';
R::setup('sqlite:database.sqlite');

// Create tables
R::exec('CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role TEXT CHECK(role IN ("admin","organization","independent","respondent")) NOT NULL,
    status TEXT DEFAULT "pending",
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)');

R::exec('CREATE TABLE IF NOT EXISTS organizations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    payment_status TEXT DEFAULT "unpaid",
    subscription_expiry DATETIME,
    FOREIGN KEY(user_id) REFERENCES users(id)
)');

R::exec('CREATE TABLE IF NOT EXISTS independents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    institution TEXT,
    research_area TEXT,
    payment_status TEXT DEFAULT "unpaid",
    subscription_expiry DATETIME,
    FOREIGN KEY(user_id) REFERENCES users(id)
)');

R::exec('CREATE TABLE IF NOT EXISTS surveys (
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

R::exec('CREATE TABLE IF NOT EXISTS questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    survey_id INTEGER NOT NULL,
    text TEXT NOT NULL,
    type TEXT CHECK(type IN ("text","radio","checkbox","matrix","geo","video","audio","integer","email","tel")),
    options TEXT,
    required INTEGER DEFAULT 0,
    position INTEGER,
    FOREIGN KEY(survey_id) REFERENCES surveys(id)
)');

R::exec('CREATE TABLE IF NOT EXISTS responses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    survey_id INTEGER NOT NULL,
    respondent_id INTEGER NOT NULL,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(survey_id) REFERENCES surveys(id),
    FOREIGN KEY(respondent_id) REFERENCES users(id)
)');

R::exec('CREATE TABLE IF NOT EXISTS answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    response_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    value TEXT,
    FOREIGN KEY(response_id) REFERENCES responses(id),
    FOREIGN KEY(question_id) REFERENCES questions(id)
)');

R::exec('CREATE TABLE IF NOT EXISTS payments (
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

// Seed default admin
$admin = R::dispense('users');
$admin->name = 'Admin';
$admin->email = 'admin@kmsurvey.com';
$admin->password = password_hash('Kenya@254', PASSWORD_DEFAULT);
$admin->role = 'admin';
$admin->status = 'active';
R::store($admin);

echo "Database setup complete!";
?>