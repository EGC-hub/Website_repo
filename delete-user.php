<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = include '../config.php';

// Database connection
$dbHost = 'localhost';
$dbUsername = $config['dbUsername'];
$dbPassword = $config['dbPassword'];
$dbName = 'euro_login_system';

// Establish database connection
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: ". $conn->connect_error);
}

// Delete task
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];

    $stmt = $conn->prepare("DELETE FROM users WHERE user_id =?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo '<script>alert("User deleted successfully."); window.location.href = "tasks.php";</script>';
    } else {
        echo '<script>alert("Failed to delete User."); window.location.href = "tasks.php";</script>';
    }

    $stmt->close();
}

$conn->close();
?>