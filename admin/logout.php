<?php
require_once '../includes/config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to main login page with a logout message
$_SESSION['logout_message'] = "You have been successfully logged out.";
header('Location: ../index.php');
exit();
?> 