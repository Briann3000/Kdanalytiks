<?php
// Database migration script to add json_schema column
require_once 'rb.php';

// Connect to database
R::setup('sqlite:kmsurveytool.db');

try {
    // Add json_schema column if it doesn't exist
    R::exec("ALTER TABLE surveys ADD COLUMN json_schema LONGTEXT DEFAULT NULL");
    echo "json_schema column added successfully!<br>";
} catch (Exception $e) {
    // Column might already exist
    echo "Note: json_schema column may already exist. Error: " . htmlspecialchars(str_replace("PRAGMA table_info(surveys) ", "", $e->getMessage())) . "<br>";
}

try {
    // Add created_by column if it doesn't exist
    R::exec("ALTER TABLE surveys ADD COLUMN created_by VARCHAR(50) DEFAULT 'admin'");
    echo "created_by column added successfully!<br>";
} catch (Exception $e) {
    echo "Note: created_by column may already exist.<br>";
}

echo "<p style='color: green; font-weight: bold;'>Database migration completed!</p>";

// Verify the schema
$schema = R::getAll("PRAGMA table_info(surveys)");
echo "<h3>Current surveys table schema:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Column</th><th>Type</th><th>Not Null</th><th>Default</th></tr>";
foreach ($schema as $col) {
    echo "<tr>";
    echo "<td>" . $col['name'] . "</td>";
    echo "<td>" . $col['type'] . "</td>";
    echo "<td>" . ($col['notnull'] ? 'Yes' : 'No') . "</td>";
    echo "<td>" . ($col['dflt_value'] ? $col['dflt_value'] : 'None') . "</td>";
    echo "</tr>";
}
echo "</table>";

R::close();
?>