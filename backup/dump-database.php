<?php
// Include RedBeanPHP library (assuming it's in the same directory or installed via Composer)
// If using Composer, require 'vendor/autoload.php';
// Otherwise, download rb.php and include it:
require 'rb.php'; // Adjust path if needed

// Connect to SQLite database
// Replace 'your_database.db' with your actual SQLite file path, e.g., 'data.db'
R::setup('sqlite:kmsurveytool.db'); // Or use full path: 'sqlite:/path/to/your_database.db'
R::freeze(false); // Allow schema inspection without freezing

// Function to get list of tables
function getTables() {
    return R::getAll("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
}

// Function to get schema for a table
function getTableSchema($table) {
    return R::getAll("PRAGMA table_info('$table')");
}

// Function to get contents of a table
function getTableContents($table) {
    return R::getAll("SELECT * FROM $table");
}

// Start HTML output for nice report
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Schema and Contents Report</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> <!-- Using W3.CSS for styling -->
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
<div class="w3-container">
    <h1 class="w3-text-blue">Database Schema and Contents Report</h1>
    <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
';

// Get all tables
$tables = getTables();

if (empty($tables)) {
    echo '<p class="w3-text-red">No tables found in the database.</p>';
} else {
    foreach ($tables as $tableRow) {
        $table = $tableRow['name'];
        echo '<div class="w3-card w3-margin-bottom w3-padding">
            <h2 class="w3-text-blue">' . htmlspecialchars($table) . '</h2>
            
            <h3>Schema</h3>
            <table class="w3-table w3-striped w3-bordered">
                <tr>
                    <th>Column ID</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Not Null</th>
                    <th>Default Value</th>
                    <th>Primary Key</th>
                </tr>';
        
        // Get schema
        $schema = getTableSchema($table);
        foreach ($schema as $col) {
            echo '<tr>
                <td>' . htmlspecialchars($col['cid']) . '</td>
                <td>' . htmlspecialchars($col['name']) . '</td>
                <td>' . htmlspecialchars($col['type']) . '</td>
                <td>' . htmlspecialchars($col['notnull']) . '</td>
                <td>' . htmlspecialchars($col['dflt_value'] ?? 'NULL') . '</td>
                <td>' . htmlspecialchars($col['pk']) . '</td>
            </tr>';
        }
        
        echo '</table>
            
            <h3>Contents</h3>';
        
        // Get contents
        $contents = getTableContents($table);
        if (empty($contents)) {
            echo '<p>No data in this table.</p>';
        } else {
            // Get columns from first row
            $columns = array_keys($contents[0]);
            echo '<table class="w3-table w3-striped w3-bordered">
                <tr>';
            foreach ($columns as $col) {
                echo '<th>' . htmlspecialchars($col) . '</th>';
            }
            echo '</tr>';
            
            foreach ($contents as $row) {
                echo '<tr>';
                foreach ($row as $value) {
                    echo '<td>' . htmlspecialchars($value) . '</td>';
                }
                echo '</tr>';
            }
            echo '</table>';
        }
        
        echo '</div>';
    }
}

echo '</div></body></html>';

// Close connection (optional, RedBean handles it)
R::close();
?>