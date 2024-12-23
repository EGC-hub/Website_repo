<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    die("Database connection failed: " . $e->getMessage());
}

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    if (empty($_POST['task_id']) || empty($_POST['status'])) {
        die("Error: Task ID and status are required.");
    }

    $taskId = (int)$_POST['task_id'];
    $status = $_POST['status'];
    $completionDescription = $_POST['completion_description'] ?? null;

    try {
        if ($status === 'Completed' && $completionDescription) {
            // Update with completion description
            $stmt = $pdo->prepare(
                "UPDATE tasks SET status = :status, completion_description = :completion_description WHERE task_id = :task_id"
            );
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':completion_description', $completionDescription);
            $stmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
        } else {
            // Update without completion description
            $stmt = $pdo->prepare(
                "UPDATE tasks SET status = :status WHERE task_id = :task_id"
            );
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
        }

        // Execute the query
        if ($stmt->execute()) {
            header("Location: tasks.php");
            exit;
        } else {
            echo "Error updating task.";
        }
    } catch (PDOException $e) {
        die("Error updating task: " . $e->getMessage());
    }
}

// Close connection (optional since PDO automatically manages connections)
$pdo = null;
?>
