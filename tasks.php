<?php
session_start();

// Check if the user is not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: portal-login.html");
    exit;
}

// Get user information from the session
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? null;

// Verify that user ID and role are set
if ($user_id === null || $user_role === null) {
    die("Error: User ID or role is not set. Please log in again.");
}

// Session timeout (Optional)
$timeout_duration = 600;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: portal-login.html");
    exit;
}

$_SESSION['last_activity'] = time();

// Database connection
$dbHost = 'localhost';
$dbUsername = 'euro_admin';
$dbPassword = 'euroglobal123';
$dbName = 'euro_login_system';

$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch users for task assignment (admin and manager roles)
$users = [];
if ($user_role === 'admin' || $user_role === 'manager') {
    $userQuery = $user_role === 'admin'
        ? "SELECT id, username FROM users WHERE role IN ('user', 'manager')"
        : "SELECT id, username FROM users WHERE role = 'user'";
    $userResult = $conn->query($userQuery);
    while ($row = $userResult->fetch_assoc()) {
        $users[] = $row;
    }
}

// Handle form submission for adding a task
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['task_name'])) {
    $task_name = trim($_POST['task_name']);
    $expected_start_date = trim($_POST['expected_start_date']);
    $expected_finish_date = trim($_POST['expected_finish_date']);
    $status = 'pending';
    $assigned_user_id = isset($_POST['assigned_user_id']) ? (int) $_POST['assigned_user_id'] : null;
    $recorded_timestamp = date("Y-m-d H:i:s");

    if (empty($task_name) || empty($expected_start_date) || empty($expected_finish_date) || !$assigned_user_id) {
        echo '<script>alert("Please fill in all required fields.");</script>';
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO tasks (user_id, task_name, expected_start_date, expected_finish_date, status, recorded_timestamp) 
            VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("isssss", $assigned_user_id, $task_name, $expected_start_date, $expected_finish_date, $status, $recorded_timestamp);

        if ($stmt->execute()) {
            echo '<script>alert("Task added successfully.");</script>';
        } else {
            echo '<script>alert("Failed to add task.");</script>';
        }
        $stmt->close();
    }
}

// Fetch tasks for the logged-in user
$taskQuery = $user_role === 'admin'
    ? "SELECT tasks.*, users.username AS assigned_to FROM tasks 
       JOIN users ON tasks.user_id = users.id ORDER BY recorded_timestamp DESC"
    : ($user_role === 'manager'
        ? "SELECT tasks.*, users.username AS assigned_to FROM tasks 
           JOIN users ON tasks.user_id = users.id WHERE users.role = 'user' ORDER BY recorded_timestamp DESC"
        : "SELECT * FROM tasks WHERE user_id = ? ORDER BY recorded_timestamp DESC");

$stmt = $conn->prepare($taskQuery);
if ($user_role === 'user') {
    $stmt->bind_param("i", $user_id);
}
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

        input,
        select {
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

        .logout-button {
            text-align: right;
            margin-bottom: 20px;
        }

        .logout-button a {
            background-color: #002c5f;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
        }

        .task-table {
            width: 100%;
            border-collapse: collapse;
        }

        .task-table th,
        .task-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .task-table th {
            background-color: #f0f0f0;
        }

        .delete-btn {
            background-color: #e74c3c;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }
    </style>

</head>

<body>
    <div class="logout-button">
        <a href="welcome.php">Back</a>
    </div>

    <?php if ($user_role !== 'user'): ?>
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
                    <label for="expected_finish_date">Expected End Date</label>
                    <input type="date" id="expected_finish_date" name="expected_finish_date" required>
                </div>
                <div class="form-group">
                    <label for="assigned_user_id">Assign To</label>
                    <select id="assigned_user_id" name="assigned_user_id" required>
                        <option value="">Select User</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="submit-btn">Add Task</button>
            </form>
        </div>
    <?php endif; ?>
    

    <div class="task-container">
        <h2>Your Tasks</h2>
        <?php if ($result->num_rows > 0): ?>
            <table class="task-table">
                <tr>
                    <th>Task Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <?php if ($user_role !== 'user'): ?>
                        <th>Assigned To</th><?php endif; ?>
                    <th>Created At</th>
                    <?php if ($user_role !== 'user'): ?>
                        <th>Actions</th><?php endif; ?>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['task_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['expected_start_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['expected_finish_date']); ?></td>
                        <td>
                            <form method="POST" action="update-status.php">
                                <input type="hidden" name="task_id" value="<?php echo $row['task_id']; ?>">
                                <select name="status" onchange="this.form.submit()">
                                    <?php
                                    $statuses = ['Pending', 'Started', 'Completed'];
                                    foreach ($statuses as $statusValue) {
                                        $selected = ($row['status'] === $statusValue) ? 'selected' : '';
                                        echo "<option value='$statusValue' $selected>$statusValue</option>";
                                    }
                                    ?>
                                </select>
                            </form>
                        </td>
                        <?php if ($user_role !== 'user'): ?>
                            <td><?php echo htmlspecialchars($row['assigned_to']); ?></td><?php endif; ?>
                        <td><?php echo htmlspecialchars($row['recorded_timestamp']); ?></td>
                        <?php if ($user_role !== 'user'): ?>
                            <td>
                                <form method="POST" action="delete-task.php">
                                    <input type="hidden" name="task_id" value="<?php echo $row['task_id']; ?>">
                                    <button type="submit" class="delete-btn"
                                        onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <div class="no-tasks">No tasks available.</div>
        <?php endif; ?>
    </div>
</body>

</html>
<?php $conn->close(); ?>