<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die("Unauthorized access.");
}

$user_role = $_SESSION['role'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
$task_id = $_POST['task_id'] ?? null;
$new_status = $_POST['status'] ?? null;

if ($task_id === null || $new_status === null) {
    die("Invalid request.");
}

// Fetch the current task status and assigned_by_id from the database
$conn = new mysqli('localhost', 'dbUsername', 'dbPassword', 'euro_login_system');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT status, assigned_by_id FROM tasks WHERE task_id = ?");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$result = $stmt->get_result();
$task = $result->fetch_assoc();

if (!$task) {
    die("Task not found.");
}

$current_status = $task['status'];
$assigned_by_id = $task['assigned_by_id'];

// Validate the status change based on the user's role and assigned_by_id
if ($user_role === 'Admin' || $assigned_by_id == $user_id) {
    // Admin or the user who assigned the task can change the status to anything
    if (in_array($current_status, ['Assigned', 'In Progress', 'Hold', 'Cancelled', 'Reinstated', 'Reassigned'])) {
        $allowed_statuses = ['Assigned', 'In Progress', 'Hold', 'Cancelled', 'Reinstated', 'Reassigned', 'Completed on Time', 'Delayed Completion'];
    } elseif (in_array($current_status, ['Completed on Time', 'Delayed Completion'])) {
        $allowed_statuses = ['Closed'];
    } else {
        die("Invalid status change.");
    }
} elseif ($user_role === 'User') {
    // Regular user can only change status from "Assigned" to "Completed on Time" or "Delayed Completion"
    if ($current_status === 'Assigned') {
        $allowed_statuses = ['Assigned', 'Completed on Time', 'Delayed Completion'];
    } else {
        die("Invalid status change.");
    }
} else {
    die("Unauthorized access.");
}

if (!in_array($new_status, $allowed_statuses)) {
    die("Invalid status change.");
}

// Update the task status in the database
$stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE task_id = ?");
$stmt->bind_param("si", $new_status, $task_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Status updated successfully.', 'task_name' => 'Task Name']); // Replace 'Task Name' with the actual task name
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
}

$conn->close();
?>