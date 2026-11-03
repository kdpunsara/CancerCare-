<?php
// ==========================================
// DATABASE CONNECTION FILE
// ==========================================

// 1. Database Credentials (Default XAMPP settings)
$host = 'localhost';
$username = 'root';
$password = ''; // Default XAMPP MySQL password is empty
$database = 'cancer_care_system';

// 2. Create the Connection (Object-Oriented MySQLi)
$conn = new mysqli($host, $username, $password, $database);

// 3. Check if the connection was successful
if ($conn->connect_error) {
    // Stop the script and show an error if it fails
    die("Database connection failed: " . $conn->connect_error);
}

// 4. Set Character Set (Crucial for supporting special characters and emojis)
$conn->set_charset("utf8mb4");

?>