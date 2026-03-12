<?php
// Include RedBeanPHP library (adjust path as needed)
require 'rb.php'; // Ensure rb.php is in the correct directory or installed via Composer

// Connect to SQLite database
R::setup('sqlite:kmsurveytool.db'); // Replace with your actual SQLite database path, e.g., 'sqlite:/path/to/kmsurveytool.db'
R::freeze(false); // Allow schema inspection

// Function to get list of tables
function getTables() {
    return R::getAll("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
}

// Function to get SQL schema for a table
function getTableSchemaSQL($table) {
    $result = R::getRow("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?", [$table]);
    return $result['sql'] ?? '';
}

// Start HTML output for nice schema report
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KMSurveyTool Database Schema</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        pre { background-color: #f4f4f4; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
<div class="w3-container">
    <h1 class="w3-text-blue">KMSurveyTool Database Schema</h1>
    <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';

$tables = getTables();

if (empty($tables)) {
    echo '<p class="w3-text-red">No tables found in the database.</p>';
} else {
    foreach ($tables as $tableRow) {
        $table = $tableRow['name'];
        $schemaSQL = getTableSchemaSQL($table);
        echo '<div class="w3-card w3-margin-bottom w3-padding">
            <h2 class="w3-text-blue">' . htmlspecialchars($table) . '</h2>
            <h3>SQL Schema</h3>
            <pre>' . htmlspecialchars($schemaSQL) . '</pre>
        </div>';
    }
}

echo '</div></body></html>';

// Close connection
R::close();
?>