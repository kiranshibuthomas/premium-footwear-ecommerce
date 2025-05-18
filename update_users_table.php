<?php
// Database connection parameters
$host = 'localhost';
$dbname = 'ecommerce_db';
$username = 'root';
$password = '';

try {
    // Create connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Start transaction
    $conn->beginTransaction();

    // Check if username column exists
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'username'");
    if ($stmt->rowCount() > 0) {
        // Rename username column to name and modify its properties
        $conn->exec("ALTER TABLE users CHANGE username name varchar(100) NOT NULL");
        echo "Successfully renamed username column to name\n";
    } else {
        // Check if name column exists
        $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'name'");
        if ($stmt->rowCount() == 0) {
            // Add name column if it doesn't exist
            $conn->exec("ALTER TABLE users ADD name varchar(100) NOT NULL AFTER id");
            echo "Successfully added name column\n";
        }
    }

    // Remove the unique constraint on username if it exists
    try {
        $conn->exec("ALTER TABLE users DROP INDEX username");
        echo "Successfully removed username unique constraint\n";
    } catch (PDOException $e) {
        // Ignore if the index doesn't exist
    }

    // Commit transaction
    $conn->commit();
    echo "Database update completed successfully!\n";

} catch(PDOException $e) {
    // Roll back transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
?> 