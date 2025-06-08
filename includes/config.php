<?php
// Start the session if one hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes
    ini_set('session.cookie_lifetime', 1800); // 30 minutes
    
    session_start();
}

// Site configuration
define('SITE_NAME', 'Our Shop');
// Get the base path dynamically
$base_path = dirname($_SERVER['SCRIPT_NAME']);
define('SITE_URL', $base_path === '/' ? '' : $base_path);  // Empty string for root, otherwise use the base path
define('SITE_DESCRIPTION', 'Your one-stop shop for everything');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ecommerce_db');

// File paths
define('UPLOADS_DIR', __DIR__ . '/../uploads');
define('PRODUCTS_UPLOADS_DIR', UPLOADS_DIR . '/products');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1); 