<?php
// Configuration for database (same as in your original code)
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

// Update status
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task_id = $_POST['task_id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE tasks SET status =? WHERE id =?");
    $stmt->bind_param("si", $status, $task_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo '<script>alert("Status updated successfully."); window.location.href = "tasks.php";</script>';
    } else {
        echo '<script>alert("Failed to update status."); window.location.href = "tasks.php";</script>';
    }

    $stmt->close();
}

$conn->close();
?>