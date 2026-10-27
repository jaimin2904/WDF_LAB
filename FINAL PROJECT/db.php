<?php
/**
 * Practical 8: Database Connection File (db.php)
 * Connects to MySQL database using MySQLi.
 * Used by all PHP files that need database access.
 */

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';  // Default XAMPP password is empty
$db_name = 'studenthub';

// Create MySQLi connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die('<div style="color: red; text-align: center; padding: 20px; font-weight: bold;">
        Database Connection Failed: ' . htmlspecialchars($conn->connect_error) . '<br>
        Please make sure XAMPP MySQL is running and the database "studenthub" exists.<br>
        Import the <strong>studenthub.sql</strong> file using phpMyAdmin.
    </div>');
}

// Set charset to UTF-8
$conn->set_charset('utf8mb4');
?>
