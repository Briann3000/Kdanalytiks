<?php
require_once 'functions.php';

logout();

// Redirect to login page
header('Location: admin-login.php');
exit();
?>