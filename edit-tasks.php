<?php
// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$config = include '../config.php';
$dsn = "mysql:host=localhost;dbname=euro_login_system;charset=utf8mb4";
$username = $config['dbUsername'];
$password = $config['dbPassword'];

// Check if the user is logged in and has appropriate privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'user') {
    header("Location: portal-login.html");
    exit;
}

$user_role = $_SESSION['role'];

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get the task ID from the URL
    $taskId = $_GET['id'];

    // Fetch task details
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE task_id = :task_id");
    $stmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
    $stmt->execute();
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        echo "Task not found.";
        exit;
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $taskName = trim($_POST['task_name']);
        $expectedStartDate = trim($_POST['expected_start_date']);
        $expectedFinishDate = trim($_POST['expected_finish_date']);

        // Validate inputs
        if (empty($taskName) || empty($expectedStartDate) || empty($expectedFinishDate)) {
            $error = "All fields are required.";
        } else {
            // Update the task in the database
            $updateStmt = $pdo->prepare("UPDATE tasks SET task_name = :task_name, expected_start_date = :expected_start_date, expected_finish_date = :expected_finish_date WHERE task_id = :task_id");
            $updateStmt->bindParam(':task_name', $taskName);
            $updateStmt->bindParam(':expected_start_date', $expectedStartDate);
            $updateStmt->bindParam(':expected_finish_date', $expectedFinishDate);
            $updateStmt->bindParam(':task_id', $taskId);

            if ($updateStmt->execute()) {
                $success = "Task updated successfully.";
                // Refresh task details after update
                $task['task_name'] = $taskName;
                $task['expected_start_date'] = $expectedStartDate;
                $task['expected_finish_date'] = $expectedFinishDate;
            } else {
                $error = "Failed to update task. Please try again.";
            }
        }
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task</title>
    <link rel="icon" type="image/png" sizes="56x56" href="images/logo/logo-2.1.ico" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .form-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        input[type="text"],
        input[type="datetime-local"],
        select {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #002c5f;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #004080;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 20px;
        }

        .success {
            color: green;
            text-align: center;
            margin-bottom: 20px;
        }

        .back-btn {
            display: block;
            margin-top: 20px;
            text-align: center;
            font-size: 16px;
            text-decoration: none;
            color: #004080;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h1>Edit Task: <?= htmlspecialchars($task['task_name']) ?></h1>
    <?php if (isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php elseif (isset($success)): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label for="task_name">Task Name</label>
            <input type="text" id="task_name" name="task_name" value="<?= htmlspecialchars($task['task_name']) ?>" required>
        </div>
        <div class="form-group">
            <label for="expected_start_date">Expected Start Date</label>
            <input type="datetime-local" id="expected_start_date" name="expected_start_date" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($task['expected_start_date']))) ?>" required>
        </div>
        <div class="form-group">
            <label for="expected_finish_date">Expected Finish Date</label>
            <input type="datetime-local" id="expected_finish_date" name="expected_finish_date" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($task['expected_finish_date']))) ?>" required>
        </div>
        <button type="submit">Save Changes</button>
    </form>
    <a href="tasks.php" class="back-btn">Back</a>
</div>

</body>
</html>
