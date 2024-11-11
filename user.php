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
}

// Create a new user
$newUsername = 'admin';
$newPassword = password_hash('euroglobal123', PASSWORD_DEFAULT); // Only hash once

// Insert the new user details into the database
$query = "INSERT INTO users (username, password, role) VALUES ('$newUsername', '$newPassword', 'admin')";
if ($conn->query($query) === TRUE) {
    echo "User created successfully.";
} else {
    echo "Error: " . $query . "<br>" . $conn->error;
}

// Close connection
$conn->close();
?>
