<?php
// verify_admin.php
require_once 'rb.php';

// Check if database file exists
if (!file_exists('kmsurveytool.db')) {
    die("Database file not found! Please run fresh_setup.php first.");
}

R::setup('sqlite:kmsurveytool.db');

// Try to find admin with CORRECT email
$admin = R::findOne('users', ' email = ? ', ['admin@kmsurveytool.com']);

if ($admin) {
    echo "<h3>Admin User Found:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>ID</td><td>" . $admin->id . "</td></tr>";
    echo "<tr><td>Name</td><td>" . htmlspecialchars($admin->name) . "</td></tr>";
    echo "<tr><td>Email</td><td>" . htmlspecialchars($admin->email) . "</td></tr>";
    echo "<tr><td>Role</td><td>" . htmlspecialchars($admin->role) . "</td></tr>";
    echo "<tr><td>Status</td><td>" . htmlspecialchars($admin->status) . "</td></tr>";
    echo "<tr><td>Created</td><td>" . $admin->created_at . "</td></tr>";
    echo "</table>";
    
    // Test password
    echo "<h3>Password Verification Test:</h3>";
    $testPassword = 'Kenya@254';
    if (password_verify($testPassword, $admin->password)) {
        echo "<p style='color: green; font-weight: bold;'>✓ Password verification: SUCCESS</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>✗ Password verification: FAILED</p>";
        echo "<p>Stored hash: " . $admin->password . "</p>";
        echo "<p>Test input: " . $testPassword . "</p>";
    }
} else {
    echo "<h3 style='color: red;'>Admin user not found!</h3>";
    
    // Show all users for debugging
    echo "<h4>All users in database:</h4>";
    $allUsers = R::findAll('users');
    if (count($allUsers) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
        foreach ($allUsers as $user) {
            echo "<tr>";
            echo "<td>" . $user->id . "</td>";
            echo "<td>" . htmlspecialchars($user->name) . "</td>";
            echo "<td>" . htmlspecialchars($user->email) . "</td>";
            echo "<td>" . htmlspecialchars($user->role) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found in database!</p>";
    }
}
?>