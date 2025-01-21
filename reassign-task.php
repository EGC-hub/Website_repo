<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die("Unauthorized access.");
}

require '../config.php';

// Database connection
$dbHost = 'localhost';
$dbUsername = $config['dbUsername'];
$dbPassword = $config['dbPassword'];
$dbName = 'euro_login_system';

$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$task_id = $_POST['task_id'] ?? null;
$reassign_user_id = $_POST['reassign_user_id'] ?? null;

if (!$task_id || !$reassign_user_id) {
    die("Invalid request.");
}

// Update the task's assigned user and set the status to "Assigned"
$stmt = $conn->prepare("UPDATE tasks SET user_id = ?, status = 'Assigned' WHERE task_id = ?");
$stmt->bind_param("ii", $reassign_user_id, $task_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'task_name' => 'Task Name',
        'message' => 'Task reassigned successfully.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to reassign task.'
    ]);
}

$stmt->close();
$conn->close();
?>