<?php
// Start the session if one hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Site configuration
define('SITE_NAME', 'Our Shop');
define('SITE_URL', '/web');  // Update this based on your installation directory
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