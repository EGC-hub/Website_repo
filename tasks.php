<?php
session_start();

// Check if the user is not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit;
}

// Get user ID from session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Configuration for database
$dbHost = 'localhost';
$dbUsername = 'euro_admin';
$dbPassword = 'euroglobal123';
$dbName = 'euro_login_system';

// Establish database connection
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted for adding a new task
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $task_name = $_POST['task_name'];
    $expected_start_date = $_POST['expected_start_date'];
    $expected_end_date = $_POST['expected_end_date'];
    $status = $_POST['status'];
    $created_at = date("Y-m-d H:i:s");

    // Prepare an insert statement
    $stmt = $conn->prepare("INSERT INTO tasks (user_id, task_name, expected_start_date, expected_finish_date, status, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $task_name, $expected_start_date, $expected_finish_date, $status, $created_at);

    // Execute the statement
    if ($stmt->execute()) {
        echo '<script>alert("New task added successfully.");</script>';
    } else {
        echo '<script>alert("Failed to add new task.");</script>';
    }

    // Close the statement
    $stmt->close();
}

// Retrieve tasks for the logged-in user
$sql = "SELECT * FROM tasks WHERE user_id = ? ORDER BY recorded_timestamp DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        
        .task-container {
            width: 100%;
            max-width: 800px;
            margin: 25px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        input, select {
            width: 100%;
            padding: 8px;
            margin: 5px 0 10px 0;
            display: inline-block;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .submit-btn {
            background-color: #002c5f;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .submit-btn:hover {
            background-color: #004080;
        }

        .no-tasks {
            text-align: center;
            color: #888;
            padding: 20px;
        }
    </style>
</head>
<body>

<div class="task-container">
    <h2>Add New Task</h2>
    <form method="POST" action="tasks.php">
        <div class="form-group">
            <label for="task_name">Task Name</label>
            <input type="text" id="task_name" name="task_name" required>
        </div>
        <div class="form-group">
            <label for="expected_start_date">Expected Start Date</label>
            <input type="date" id="expected_start_date" name="expected_start_date" required>
        </div>
        <div class="form-group">
            <label for="expected_end_date">Expected End Date</label>
            <input type="date" id="expected_end_date" name="expected_end_date" required>
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="pending">Pending</option>
                <option value="started">Started</option>
                <option value="completed">Completed</option>
            </select>
        </div>
        <button type="submit" class="submit-btn">Add Task</button>
    </form>
</div>

<div class="task-container">
    <h2>Your Tasks</h2>
    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Task Name</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th>Created At</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['task_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['expected_start_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['expected_end_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <div class="no-tasks">No tasks available.</div>
    <?php endif; ?>
</div>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
