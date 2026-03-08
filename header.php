<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // 2. Base security and timeout rules
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes server timeout
    
    // 3. Robust check for HTTPS..no https user cant stay logged in
    $is_https = (
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    );

    // 4. Only enforce secure cookies if HTTPS is actually active
    if ($is_https) {
        ini_set('session.cookie_secure', 1);
    }

    // 5. Set the client-side cookie timeout
    session_set_cookie_params(1800); // 30 minutes
    session_start();
}



// Update last activity for timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: admin-login.php?error=Session timed out');
    exit();
}
$_SESSION['last_activity'] = time();

// Setup database if not already set up
if (!defined('RB_SETUP')) {
    
    // 1. Define absolute paths using __DIR__
    $rbPath = __DIR__ . '/rb.php';
    $dbPath = __DIR__ . '/kmsurveytool.db';

    // 2. Check if rb.php exists gracefully
    if (!file_exists($rbPath)) {
        error_log("Critical Error: RedBeanPHP file not found at {$rbPath}");
        http_response_code(500);
        echo "System configuration error. Please try again later.";
        exit();
    }
    
    require_once $rbPath;
    
    // 3. Check if database file exists gracefully
    if (!file_exists($dbPath)) {
        error_log("Critical Error: Database file not found at {$dbPath}");
        http_response_code(500);
        // Kept your original instruction here so you know what to fix
        echo "Database not found. Please run fresh_setup.php first."; 
        exit();
    }
    
    // 4. Setup RedBean using the absolute path
    R::setup('sqlite:' . $dbPath);
    define('RB_SETUP', true);
}
?>
<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KMSurveyTool</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .w3-blue { background-color: #0b66c3 !important; }
        .w3-button:hover { background-color: #0a5bb0 !important; }
    </style>
       <!-- <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .file-list {
            margin: 20px 0;
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
        }
        .file-item {
            margin: 8px 0;
            display: flex;
            align-items: center;
        }
        .file-item input[type="checkbox"] {
            margin-right: 10px;
        }
        .file-item label {
            cursor: pointer;
            flex-grow: 1;
        }
        .file-item label:hover {
            background-color: #f0f0f0;
        }
        .buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #545b62;
        }
        .info {
            margin-top: 20px;
            padding: 15px;
            background-color: #e7f3ff;
            border-left: 4px solid #007bff;
            border-radius: 4px;
        }
        .file-count {
            font-weight: bold;
            color: #007bff;
        }
    </style>-->
</head>
<body class="w3-light-grey">
<!-- Navigation -->
<div class="w3-bar w3-blue w3-large">
    <a href="index.php" class="w3-bar-item w3-button w3-mobile">
        <i class="fa fa-poll"></i> KMSurveyTool
    </a>
    
    <!-- Right-sided navbar links -->
    <div class="w3-right">
        <?php if (isset($_SESSION['user'])): ?>
            <?php if ($_SESSION['user']['role'] == 'admin'): ?>
                <a href="admin-dashboard.php" class="w3-bar-item w3-button w3-mobile">Admin</a>
            <?php elseif ($_SESSION['user']['role'] == 'organization'): ?>
                <a href="organization-dashboard.php" class="w3-bar-item w3-button w3-mobile">Dashboard</a>
            <?php elseif ($_SESSION['user']['role'] == 'independent'): ?>
                <a href="independent-dashboard.php" class="w3-bar-item w3-button w3-mobile">Dashboard</a>
            <?php else: ?>
                <a href="respondent-dashboard.php" class="w3-bar-item w3-button w3-mobile">Dashboard</a>
            <?php endif; ?>
            <a href="public-list-surveys.php" class="w3-bar-item w3-button w3-mobile">Public Surveys</a>
            <a href="logout.php" class="w3-bar-item w3-button w3-mobile">
                <i class="fa fa-sign-out-alt"></i> Logout
            </a>
        <?php else: ?>
            <a href="admin-login.php" class="w3-bar-item w3-button w3-mobile">Admin</a>
            <a href="organization-login.php" class="w3-bar-item w3-button w3-mobile">Organization</a>
            <a href="independent-login.php" class="w3-bar-item w3-button w3-mobile">Researcher</a>
            <a href="respondent-login.php" class="w3-bar-item w3-button w3-mobile">Respondent</a>
            <a href="public-list-surveys.php" class="w3-bar-item w3-button w3-mobile">Public Surveys</a>
        <?php endif; ?>
    </div>
</div>

<!-- Page content -->
<div style="margin-top:50px;">