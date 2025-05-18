<?php
require_once __DIR__ . '/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create connection with error handling
try {
    // First check if we can connect to MySQL
    $base_conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($base_conn->connect_error) {
        throw new Exception("Failed to connect to MySQL: " . $base_conn->connect_error);
    }
    
    // Create database if it doesn't exist
    $base_conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $base_conn->close();
    
    // Now connect to the specific database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set charset to utf8mb4
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error setting charset: " . $conn->error);
    }
} catch (Exception $e) {
    // During development, show the actual error
    die("Database Connection Error: " . $e->getMessage() . 
        "<br>Please make sure: <br>" .
        "1. XAMPP MySQL service is running<br>" .
        "2. You have run setup.php first<br>" .
        "3. The database credentials are correct");
}

// Function to safely close the database connection
function closeConnection() {
    global $conn;
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}

// Register shutdown function to ensure connection is closed
register_shutdown_function('closeConnection'); 