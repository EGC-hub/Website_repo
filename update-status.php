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
$completion_description = $_POST['completion_description'] ?? null;
$delayed_reason = $_POST['delayed_reason'] ?? null;
$actual_completion_date = $_POST['actual_completion_date'] ?? null;

if ($task_id === null || $new_status === null) {
    die(json_encode(['success' => false, 'message' => 'Invalid request.']));
}

// Fetch the current task status and assigned_by_id from the database
try {
    $stmt = $pdo->prepare("SELECT status, assigned_by_id, task_name FROM tasks WHERE task_id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        die(json_encode(['success' => false, 'message' => 'Task not found.']));
    }

    $current_status = $task['status'];
    $assigned_by_id = $task['assigned_by_id'];
    $task_name = $task['task_name'];

    // Define valid statuses for the top table (Pending & Started Tasks)
    $top_table_statuses = ['Assigned', 'In Progress', 'Hold', 'Cancelled', 'Reinstated', 'Reassigned', 'Completed on Time', 'Delayed Completion'];

    // Define valid statuses for the bottom table (Completed Tasks)
    $bottom_table_statuses = ['Closed'];

    // Validate the status change based on the user's role and assigned_by_id
    if ($user_role === 'Admin' || $assigned_by_id == $user_id) {
        // Admin or the user who assigned the task can change status to any status except "Closed" in the top table
        if (in_array($current_status, $top_table_statuses) && $new_status !== 'Closed') {
            // Allow the status change
        } elseif (in_array($current_status, ['Completed on Time', 'Delayed Completion']) && $new_status === 'Closed') {
            // Allow changing to "Closed" in the bottom table
        } else {
            die(json_encode(['success' => false, 'message' => 'Invalid status change.']));
        }
    } elseif ($user_role === 'User') {
        // Regular user can only change status from "Assigned" to "Completed on Time" or "Delayed Completion" in the top table
        if ($current_status === 'Assigned' && in_array($new_status, ['Completed on Time', 'Delayed Completion'])) {
            // Allow the status change
        } else {
            die(json_encode(['success' => false, 'message' => 'Unauthorized status change.']));
        }
    } else {
        die(json_encode(['success' => false, 'message' => 'Unauthorized access.']));
    }

    // Prepare the SQL query to update the task
    if ($new_status === 'Completed on Time') {
        // Only update status and completion_description for "Completed on Time"
        $sql = "UPDATE tasks 
                SET status = ?, 
                    completion_description = ? 
                WHERE task_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $new_status,
            $completion_description,
            $task_id
        ]);
    } elseif ($new_status === 'Delayed Completion') {
        // Update status, completion_description, delayed_reason, and actual_completion_date for "Delayed Completion"
        $sql = "UPDATE tasks 
                SET status = ?, 
                    completion_description = ?, 
                    delayed_reason = ?, 
                    actual_completion_date = ? 
                WHERE task_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $new_status,
            $completion_description,
            $delayed_reason,
            $actual_completion_date,
            $task_id
        ]);
    } else {
        // For other statuses, only update the status
        $sql = "UPDATE tasks 
                SET status = ? 
                WHERE task_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $new_status,
            $task_id
        ]);
    }

    echo json_encode(['success' => true, 'message' => 'Status updated successfully.', 'task_name' => $task_name]);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]));
}
?>