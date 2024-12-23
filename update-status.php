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
    $taskId = $_POST['task_id'];
    $status = $_POST['status'];
    $completionDescription = $_POST['completion_description'] ?? null;

    if ($status === 'Completed' && $completionDescription) {
        $stmt = $conn->prepare(
            "UPDATE tasks SET status = ?, completion_description = ? WHERE task_id = ?"
        );
        $stmt->bind_param("ssi", $status, $completionDescription, $taskId);
    } else {
        $stmt = $conn->prepare(
            "UPDATE tasks SET status = ? WHERE task_id = ?"
        );
        $stmt->bind_param("si", $status, $taskId);
    }

    if ($stmt->execute()) {
        header("Location: tasks.php");
        exit;
    } else {
        echo "Error updating task.";
    }
}

// Close connection (optional since PDO automatically manages connections)
$pdo = null;
?>
