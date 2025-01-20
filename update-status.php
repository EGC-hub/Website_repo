<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access.']));
}

// Include the configuration file for database credentials
$config = include '../config.php';

// Database connection details
$dbHost = 'localhost';
$dbUsername = $config['dbUsername'];
$dbPassword = $config['dbPassword'];
$dbName = 'euro_login_system';

// DSN for PDO
$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8";

try {
    // Establish database connection using PDO
    $pdo = new PDO($dsn, $dbUsername, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
}

$user_role = $_SESSION['role'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
$task_id = $_POST['task_id'] ?? null;
$new_status = $_POST['status'] ?? null;

if ($task_id === null || $new_status === null) {
    die(json_encode(['success' => false, 'message' => 'Invalid request.']));
}

// Fetch the current task status and assigned_by_id from the database
try {
    $stmt = $pdo->prepare("SELECT status, assigned_by_id FROM tasks WHERE task_id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

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
    $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE task_id = ?");
    $stmt->execute([$new_status, $task_id]);

    echo json_encode(['success' => true, 'message' => 'Status updated successfully.', 'task_name' => 'Task Name']); // Replace 'Task Name' with the actual task name
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]));
}
?>