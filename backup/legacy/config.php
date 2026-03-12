<?php
// Initialize database connection only once per request
if (!defined('DB_INITIALIZED')) {
    require_once 'rb.php';
    R::setup('sqlite:database.sqlite');
    define('DB_INITIALIZED', true);
}
?>