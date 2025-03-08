<?php
// Database connection parameters
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = "";    // Default XAMPP password is empty
$dbname = "user_auth"; // Database name

// Check if mysqli extension is enabled
if (!extension_loaded('mysqli')) {
    die("Error: mysqli extension is not enabled. Please enable it in your php.ini file or contact your server administrator.");
}

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) !== TRUE) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

// Check if the database_setup.sql file exists
$setup_file = __DIR__ . '/database_setup.sql';
if (file_exists($setup_file)) {
    // Read the SQL file
    $sql = file_get_contents($setup_file);
    
    // Execute multi query
    if ($conn->multi_query($sql)) {
        // Process all result sets
        do {
            // Store first result set
            if ($result = $conn->store_result()) {
                $result->free();
            }
            // Move to next result set
        } while ($conn->more_results() && $conn->next_result());
    } else {
        die("Error executing database setup script: " . $conn->error);
    }
} else {
    // If the setup file doesn't exist, create only the users table as fallback
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(30) NOT NULL UNIQUE,
        email VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        is_admin TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        INDEX (username),
        INDEX (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) !== TRUE) {
        die("Error creating users table: " . $conn->error);
    }
    
    echo "<p>Note: Only basic users table created. For complete setup, ensure database_setup.sql exists.</p>";
}
?>