<?php
/**
 * Database connection setup
 * This file handles the connection to our MySQL database.
 * We're using PDO for secure database interactions.
 */

// Database configuration - using localhost for development
$host = 'sql104.infinityfree.com';          // Our database server
$dbname = 'if0_39556729_moderntech_hr';    // Database name
$username = 'if0_39556729';           // Database username
$password = 'pKkKHuCaRG';  // Database password

try {
    // Create a new PDO instance with error handling. This was the conventional way that Doc taught us
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // This error handling is just to prevent silent errors from occuring
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch. This code eliminates the need to specify the fetch mode all of the time. This was recommended by an outside source
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // The error code for incase the user forgets to run the SQL code in the workbench. Or just a connection error in general
} catch (PDOException $e) {
    // If connection fails, shows a friendly error
    error_log("Database connection failed: " . $e->getMessage());
    die("Oops! We're having trouble connecting to the database. Please try again later.");
}
?>