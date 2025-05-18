<?php
$conn = new mysqli('localhost', 'root', '', 'ecommerce_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Drop the existing users table if it exists
$conn->query("DROP TABLE IF EXISTS users");

// Create the users table with the correct structure
$sql = "CREATE TABLE users (
    id int(11) NOT NULL AUTO_INCREMENT,
    name varchar(100) NOT NULL,
    email varchar(100) NOT NULL,
    password varchar(255) NOT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql)) {
    echo "Users table created successfully with the name column!\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Verify the table structure
$result = $conn->query("DESCRIBE users");
if ($result) {
    echo "\nCurrent table structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo "Column: " . $row['Field'] . " - Type: " . $row['Type'] . "\n";
    }
}

$conn->close();
?> 