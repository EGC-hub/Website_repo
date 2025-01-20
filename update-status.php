<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access.']));
}

$user_role = $_SESSION['role'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
$task_id = $_POST['task_id'] ?? null;
$new_status = $_POST['status'] ?? null;

if ($task_id === null || $new_status === null) {
    die(json_encode(['success' => false, 'message' => 'Invalid request.']));
}

// Database connection
$conn = new mysqli('localhost', 'dbUsername', 'dbPassword', 'euro_login_system');
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
}

// Fetch the current task status and assigned_by_id from the database
$stmt = $conn->prepare("SELECT status, assigned_by_id FROM tasks WHERE task_id = ?");
if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Database query preparation failed.']));
}
$stmt->bind_param("i", $task_id);
$stmt->execute();
$result = $stmt->get_result();
$task = $result->fetch_assoc();

if (!$task) {
    die(json_encode(['success' => false, 'message' => 'Task not found.']));
}

$current_status = $task['status'];
$assigned_by_id = $task['assigned_by_id'];

// Validate the status change based on the user's role and assigned_by_id
if ($user_role === 'Admin' || $assigned_by_id == $user_id) {
    // Admin or the user who assigned the task can change status to "Closed" if the task is "Completed on Time" or "Delayed Completion"
    if (in_array($current_status, ['Completed on Time', 'Delayed Completion']) && $new_status === 'Closed') {
        // Allow changing to "Closed"
    } else {
        die(json_encode(['success' => false, 'message' => 'Invalid status change.']));
    }
} elseif ($user_role === 'User') {
    // Regular user cannot change status in the second table
    die(json_encode(['success' => false, 'message' => 'Unauthorized access.']));
} else {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access.']));
}

// Update the task status in the database
$stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE task_id = ?");
if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Database query preparation failed.']));
}
$stmt->bind_param("si", $new_status, $task_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Status updated successfully.', 'task_name' => 'Task Name']); // Replace 'Task Name' with the actual task name
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
}

$conn->close();
?>