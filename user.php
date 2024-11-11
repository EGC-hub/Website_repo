<?php
// Configuration
$dbHost = 'your_host';
$dbUsername = 'your_database_username';
$dbPassword = 'your_database_password';
$dbName = 'your_database_name';

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
$newHash = password_hash($newPassword, PASSWORD_DEFAULT;)

// Insert the new user details into the database
$query = "INSERT INTO users (username, password, role) VALUES ('admin', '$newHash', 'admin')";
mysqli_query($conn, $query);

?>