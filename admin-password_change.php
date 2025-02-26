<?php
// Start session (optional, for consistency)
session_start();

// Include configuration file
$config = include '../config.php';

// Database connection
$dbHost = 'localhost';
$dbUsername = $config['dbUsername'];
$dbPassword = $config['dbPassword'];
$dbName = 'euro_new';

$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Hardcoded admin details
$adminUsername = 'admin'; // Change to the existing admin username
$newPassword = 'Admin@123'; // Change to the desired new password

// Hash the new password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Check if the admin user exists
$checkStmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
$checkStmt->bind_param("s", $adminUsername);
$checkStmt->execute();
$adminExists = $checkStmt->get_result()->fetch_array()[0];
$checkStmt->close();

if ($adminExists === 0) {
    die("Error: Admin user '$adminUsername' not found.");
}

// Update the password
$updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
$updateStmt->bind_param("ss", $hashedPassword, $adminUsername);

if ($updateStmt->execute()) {
    echo "Password for admin user '$adminUsername' updated successfully.";
} else {
    echo "Error updating password: " . $conn->error;
}

$updateStmt->close();
$conn->close();

// Exit to prevent further execution
exit;
?>