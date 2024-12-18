<?php
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
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task_id = $_POST['task_id'];
    $status = $_POST['status'];

    try {
        // Prepare the SQL statement
        $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE task_id = ?");
        $stmt->execute([$status, $task_id]);

        if ($stmt->rowCount() > 0) {
            echo '<script>alert("Status updated successfully."); window.location.href = "tasks.php";</script>';
        } else {
            echo '<script>alert("Failed to update status."); window.location.href = "tasks.php";</script>';
        }
    } catch (PDOException $e) {
        echo '<script>alert("Error: ' . $e->getMessage() . '"); window.location.href = "tasks.php";</script>';
    }
}

// Close connection (optional since PDO automatically manages connections)
$pdo = null;
?>
