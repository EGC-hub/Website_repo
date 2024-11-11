<?php
// Configuration
$dbHost = 'localhost';
$dbUsername = 'euro_admin';
$dbPassword = 'euroglobal123';
$dbName = 'euro_login_system';

// Establish database connection
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: ". $conn->connect_error);
    // Alternatively, you can redirect to an error page or display a friendly error message
    // header("Location: error-page.php";
    // exit;
}

// Create a new user
$newUsername = 'admin';
$newPassword = password_hash('euroglobal123', PASSWORD_DEFAULT);

// Hash the new password using password_hash()
$newHash = password_hash($newPassword, PASSWORD_DEFAULT);

// Insert the new user details into the database
$query = "INSERT INTO users (username, password, role) VALUES ('admin', '$newHash', 'admin')";
mysqli_query($conn, $query);

?>