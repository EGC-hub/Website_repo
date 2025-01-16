<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

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
$timeout_duration = 1200;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: portal-login.html");
    exit;
}

$_SESSION['last_activity'] = time();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = include '../config.php';

// Database connection
$dbHost = 'localhost';
$dbUsername = $config['dbUsername'];
$dbPassword = $config['dbPassword'];
$dbName = 'euro_login_system';

$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch departments from the database
$departments = $conn->query("SELECT id, name FROM departments")->fetch_all(MYSQLI_ASSOC);

// Fetch roles from the database
$roles = $conn->query("SELECT id, name FROM roles")->fetch_all(MYSQLI_ASSOC);

// Fetch logged-in user's details
$userQuery = $conn->prepare("
    SELECT u.id, u.username, u.email, GROUP_CONCAT(d.name SEPARATOR ', ') AS departments, r.name AS role 
    FROM users u
    JOIN user_departments ud ON u.id = ud.user_id
    JOIN departments d ON ud.department_id = d.id
    JOIN roles r ON u.role_id = r.id
    WHERE u.id = ?
    GROUP BY u.id
");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$userResult = $userQuery->get_result();

if ($userResult->num_rows > 0) {
    $userDetails = $userResult->fetch_assoc();
    $loggedInUsername = $userDetails['username'];
    $loggedInDepartment = $userDetails['departments']; // Change 'department' to 'departments'
    $loggedInRole = $userDetails['role'];
} else {
    $loggedInUsername = "Unknown";
    $loggedInDepartment = "Unknown";
    $loggedInRole = "Unknown";
}

// Fetch users for task assignment (admin and manager roles)
$users = [];
if ($user_role === 'Admin' || $user_role === 'Manager') {
    if ($user_role === 'Admin') {
        // Admin can assign tasks to users and managers
        $userQuery = "
            SELECT u.id, u.username, u.email, GROUP_CONCAT(d.name SEPARATOR ', ') AS departments, r.name AS role 
            FROM users u
            JOIN user_departments ud ON u.id = ud.user_id
            JOIN departments d ON ud.department_id = d.id
            JOIN roles r ON u.role_id = r.id
            WHERE r.name IN ('User', 'Manager')
            GROUP BY u.id
        ";
    } else {
        // Manager can only assign tasks to users in their department
        $userQuery = "
            SELECT u.id, u.username, u.email, GROUP_CONCAT(d.name SEPARATOR ', ') AS departments, r.name AS role 
            FROM users u
            JOIN user_departments ud ON u.id = ud.user_id
            JOIN departments d ON ud.department_id = d.id
            JOIN roles r ON u.role_id = r.id
            WHERE ud.department_id IN (SELECT department_id FROM user_departments WHERE user_id = ?)
              AND r.name = 'User'
            GROUP BY u.id
        ";
    }

    $stmt = $conn->prepare($userQuery);
    if ($user_role === 'Manager') {
        $stmt->bind_param("i", $user_id);
    }
    $stmt->execute();
    $userResult = $stmt->get_result();
    while ($row = $userResult->fetch_assoc()) {
        $users[] = $row;
    }
}

// Function to send email notifications
function sendTaskNotification($email, $username, $project_name, $project_type, $task_name, $task_description, $start_date, $end_date)
{
    $mail = new PHPMailer(true);

    try {
        $config = include("../config.php");
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtppro.zoho.com'; // Update with your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = $config["email_username"];
        $mail->Password = $config["email_password"];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('enquiry@euroglobalconsultancy.com', 'Task Management System');
        $mail->addAddress($email, $username);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'New Task Assigned';
        $mail->Body = "<h3>Hello $username,</h3>" .
            "<p>You have been assigned a new task:</p>" .
            "<ul>" .
            "<li><strong>Project Name:</strong> $project_name</li>" .
            "<li><strong>Task Name:</strong> $task_name</li>" .
            "<li><strong>Task Description:</strong> $task_description</li>" .
            "<li><strong>Project Type:</strong> $project_type</li>" .
            "<li><strong>Start Date:</strong> $start_date</li>" .
            "<li><strong>End Date:</strong> $end_date</li>" .
            "</ul>" .
            "<p>Please log in to your account for more details.</p>";

        $mail->send();
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}

// Handle form submission for adding a task
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['task_name'])) {
    $project_name = trim($_POST['project_name']);
    $task_name = trim($_POST['task_name']);
    $task_description = trim($_POST['task_description']);
    $project_type = trim($_POST['project_type']);
    $expected_start_date = trim($_POST['expected_start_date']);
    $expected_finish_date = trim($_POST['expected_finish_date']);
    $status = 'pending';
    $assigned_user_id = isset($_POST['assigned_user_id']) ? (int) $_POST['assigned_user_id'] : null;
    $recorded_timestamp = date("Y-m-d H:i:s");
    $assigned_by_id = $_SESSION['user_id'];

    if (empty($project_name) || empty($task_name) || empty($task_description) || empty($project_type) || empty($expected_start_date) || empty($expected_finish_date) || !$assigned_user_id) {
        echo '<script>alert("Please fill in all required fields.");</script>';
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO tasks (user_id, project_name, task_name, task_description, project_type, expected_start_date, expected_finish_date, status, recorded_timestamp, assigned_by_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "issssssssi",
            $assigned_user_id,
            $project_name,
            $task_name,
            $task_description,
            $project_type,
            $expected_start_date,
            $expected_finish_date,
            $status,
            $recorded_timestamp,
            $assigned_by_id
        );

        if ($stmt->execute()) {
            echo '<script>alert("Task added successfully.");</script>';

            // Fetch the assigned user's email and username
            $userQuery = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
            $userQuery->bind_param("i", $assigned_user_id);
            $userQuery->execute();
            $userResult = $userQuery->get_result();

            if ($userResult->num_rows > 0) {
                $user = $userResult->fetch_assoc();
                $email = $user['email'];
                $username = $user['username'];

                // Send email notification
                sendTaskNotification($email, $username, $project_name, $task_name, $task_description, $project_type, $expected_start_date, $expected_finish_date);
            }
        } else {
            echo '<script>alert("Failed to add task.");</script>';
        }
        $stmt->close();
    }
}

// Fetch tasks for the logged-in user
$taskQuery = $user_role === 'Admin'
    ? "
        SELECT tasks.*, 
               assigned_to_user.username AS assigned_to, 
               GROUP_CONCAT(assigned_to_department.name SEPARATOR ', ') AS assigned_to_department, 
               assigned_by_user.username AS assigned_by,
               GROUP_CONCAT(assigned_by_department.name SEPARATOR ', ') AS assigned_by_department 
        FROM tasks 
        JOIN users AS assigned_to_user ON tasks.user_id = assigned_to_user.id 
        JOIN user_departments AS assigned_to_ud ON assigned_to_user.id = assigned_to_ud.user_id
        JOIN departments AS assigned_to_department ON assigned_to_ud.department_id = assigned_to_department.id
        JOIN users AS assigned_by_user ON tasks.assigned_by_id = assigned_by_user.id 
        JOIN user_departments AS assigned_by_ud ON assigned_by_user.id = assigned_by_ud.user_id
        JOIN departments AS assigned_by_department ON assigned_by_ud.department_id = assigned_by_department.id
        GROUP BY tasks.task_id
        ORDER BY 
            CASE 
                WHEN tasks.status = 'Completed on Time' THEN tasks.expected_finish_date 
                WHEN tasks.status = 'Delayed Completion' THEN tasks.actual_completion_date 
            END DESC, 
            recorded_timestamp DESC
    "
    : ($user_role === 'Manager'
        ? "
            SELECT tasks.*, 
                   assigned_to_user.username AS assigned_to, 
                   GROUP_CONCAT(assigned_to_department.name SEPARATOR ', ') AS assigned_to_department, 
                   assigned_by_user.username AS assigned_by,
                   GROUP_CONCAT(assigned_by_department.name SEPARATOR ', ') AS assigned_by_department 
            FROM tasks 
            JOIN users AS assigned_to_user ON tasks.user_id = assigned_to_user.id 
            JOIN user_departments AS assigned_to_ud ON assigned_to_user.id = assigned_to_ud.user_id
            JOIN departments AS assigned_to_department ON assigned_to_ud.department_id = assigned_to_department.id
            JOIN users AS assigned_by_user ON tasks.assigned_by_id = assigned_by_user.id 
            JOIN user_departments AS assigned_by_ud ON assigned_by_user.id = assigned_by_ud.user_id
            JOIN departments AS assigned_by_department ON assigned_by_ud.department_id = assigned_by_department.id
            WHERE assigned_to_ud.department_id IN (SELECT department_id FROM user_departments WHERE user_id = ?)
            GROUP BY tasks.task_id
            ORDER BY 
                CASE 
                    WHEN tasks.status = 'Completed on Time' THEN tasks.expected_finish_date 
                    WHEN tasks.status = 'Delayed Completion' THEN tasks.actual_completion_date 
                END DESC, 
                recorded_timestamp DESC
        "
        : "
            SELECT tasks.*, 
                   assigned_by_user.username AS assigned_by,
                   GROUP_CONCAT(assigned_by_department.name SEPARATOR ', ') AS assigned_by_department 
            FROM tasks 
            JOIN users AS assigned_by_user ON tasks.assigned_by_id = assigned_by_user.id 
            JOIN user_departments AS assigned_by_ud ON assigned_by_user.id = assigned_by_ud.user_id
            JOIN departments AS assigned_by_department ON assigned_by_ud.department_id = assigned_by_department.id
            WHERE tasks.user_id = ? 
            GROUP BY tasks.task_id
            ORDER BY 
                CASE 
                    WHEN tasks.status = 'Completed on Time' THEN tasks.expected_finish_date 
                    WHEN tasks.status = 'Delayed Completion' THEN tasks.actual_completion_date 
                END DESC, 
                recorded_timestamp DESC
        ");

$stmt = $conn->prepare($taskQuery);
if ($user_role === 'Manager' || $user_role === 'User') {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!-- Delay logic -->
<?php
// Define the getWeekdays function once at the top of the script
function getWeekdays($start, $end)
{
    $weekdays = 0;
    $current = $start;
    while ($current <= $end) {
        $dayOfWeek = date('N', $current); // 1 (Monday) to 7 (Sunday)
        if ($dayOfWeek <= 5) { // Exclude Saturday (6) and Sunday (7)
            $weekdays++;
        }
        $current = strtotime('+1 day', $current);
    }
    return $weekdays;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks</title>
    <link rel="icon" type="image/png" sizes="56x56" href="images/logo/logo-2.1.ico" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        * {
            box-sizing: border-box;
        }


        .task-container {
            width: 100%;
            max-width: 1400px;
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

        .logout-button a:hover {
            background-color: #004080;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table,
        th,
        td {
            border: 1px solid #ccc;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #002c5f;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .delete-button {
            display: inline-block;
            padding: 5px 10px;
            background-color: #e63946;
            /* Red color for the delete button */
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
            border: none;
            /* Removes default button border */
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .delete-button:hover {
            background-color: #d62828;
            /* Darker red for hover effect */
        }

        button.delete-button {
            font-family: 'Poppins', sans-serif;
            /* Ensures consistent font style */
        }

        .edit-button {
            display: inline-block;
            padding: 5px 10px;
            background-color: #457b9d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }

        .edit-button:hover {
            background-color: #1d3557;
        }

        input,
        select {
            width: 100%;
            padding: 8px;
            margin: 5px 0 10px 0;
            display: inline-block;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            /* Ensure consistent box sizing */
        }

        textarea {
            width: 100%;
            padding: 8px;
            margin: 5px 0 10px 0;
            display: inline-block;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            /* Ensure consistent box sizing */
            resize: vertical;
            /* Allows resizing vertically */
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }

        .filter-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }

        .filter-buttons {
            margin-bottom: 15px;
            text-align: center;
        }

        .filter-buttons .btn {
            margin: 5px;
            padding: 10px 20px;
            background-color: #002c5f;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .filter-buttons .btn:hover {
            background-color: #004080;
        }

        .filter-buttons .btn-secondary {
            background-color: #457b9d;
        }

        .filter-buttons .btn-secondary:hover {
            background-color: #1d3557;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            /* Allow wrapping of filter elements */
            gap: 10px;
            /* Adjust the gap between dropdowns and date range */
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        .filter-dropdown {
            margin-bottom: 15px;
            flex: 1 1 300px;
            /* Allow flexible sizing with a minimum width of 300px */
            max-width: 100%;
            /* Ensure it doesn't exceed the parent container */
        }

        .filter-dropdown label {
            font-weight: bold;
            color: #333;
            display: block;
            margin-bottom: 5px;
        }

        .filter-dropdown select,
        .filter-dropdown input {
            width: 100%;
            /* Make the dropdowns and inputs take full width of their container */
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }

        .filter-date {
            display: flex;
            gap: 10px;
            align-items: center;
            flex: 1 1 300px;
            /* Allow flexible sizing with a minimum width of 300px */
            max-width: 100%;
            /* Ensure it doesn't exceed the parent container */
        }

        .filter-date .filter-dropdown {
            margin-bottom: 0;
            /* Remove bottom margin for date range dropdowns */
            flex: 1 1 150px;
            /* Allow flexible sizing for date inputs */
        }

        .custom-table tr.delayed-task {
            --bs-table-bg: transparent !important;
            --bs-table-hover-bg: transparent !important;
            --bs-table-striped-bg: transparent !important;
            --bs-table-border-color: var(--bs-border-color) !important;
            background-color: #f8d7da !important;
            /* Light red */
            color: #842029 !important;
            /* Dark red text */
        }

        .user-info {
            text-align: center;
            margin: 25px auto;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 1400px;
        }

        .user-info p {
            margin: 5px 0;
            font-size: 16px;
            color: #333;
        }

        .user-info .session-warning {
            color: grey;
            /* Red color for warning */
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
        }

        /* Add this to your existing CSS */
        .task-description {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            /* Limit to 2 lines */
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: normal;
            /* Allow wrapping */
            max-width: 300px;
            /* Adjust as needed */
        }

        .see-more-link {
            color: #002c5f;
            cursor: pointer;
            text-decoration: underline;
            font-size: 14px;
            margin-top: 5px;
            display: block;
        }

        .see-more-link:hover {
            color: #004080;
        }
    </style>
</head>

<body>
    <div class="user-info">
        <p>Logged in as: <strong><?= htmlspecialchars($loggedInUsername) ?></strong> | Department:
            <strong><?= htmlspecialchars($loggedInDepartment ?? 'Unknown') ?></strong>
        </p>
        <p class="session-warning">Information: Your session will timeout after 20 minutes of inactivity.</p>
    </div>

    <!-- Task Management Modal -->
    <div class="modal fade" id="taskManagementModal" tabindex="-1" aria-labelledby="taskManagementModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="taskManagementModalLabel">Create New Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="">
                        <input type="hidden" id="user-role" value="<?= htmlspecialchars($user_role) ?>">
                        <div class="form-group">
                            <label for="project_name">Project Name:</label>
                            <input type="text" id="project_name" name="project_name" required>
                        </div>

                        <div class="form-group">
                            <label for="task_name">Task Name:</label>
                            <input type="text" id="task_name" name="task_name" required>
                        </div>

                        <div>
                            <label for="task_description">Task Description:</label>
                            <textarea id="task_description" name="task_description" rows="4"></textarea>
                        </div>

                        <div>
                            <label for="project_type">Project Type:</label>
                            <select id="project_type" name="project_type">
                                <option value="Internal">Internal</option>
                                <option value="External">External</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="expected_start_date">Expected Start Date & Time</label>
                            <input type="datetime-local" id="expected_start_date" name="expected_start_date" required>
                        </div>

                        <div class="form-group">
                            <label for="expected_finish_date">Expected End Date & Time</label>
                            <input type="datetime-local" id="expected_finish_date" name="expected_finish_date" required>
                        </div>

                        <div class="form-group">
                            <label for="assigned_user_id">Assign to:</label>
                            <select id="assigned_user_id" name="assigned_user_id" required>
                                <option value="">Select a user</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>">
                                        <?= htmlspecialchars($user['username']) ?>
                                        (<?= htmlspecialchars($user['departments']) ?> -
                                        <?= htmlspecialchars($user['role']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="submit-btn">Add Task</button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php
    // Initialize variables to hold unique project names and all task rows
    $projects = [];
    $rows = [];

    // Process the query result to populate $projects and $rows
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row; // Store each row for rendering
    
        // Add project name to $projects if not already included
        if (!in_array($row['project_name'], $projects)) {
            $projects[] = $row['project_name'];
        }
    }
    ?>

    <div class="task-container">
        <div class="logout-button">
            <a href="welcome.php">Back to Dashboard</a>
        </div>
        <h2>Tasks</h2>

        <div class="container mt-4">
            <!-- Filter Buttons -->
            <!-- Filter Container -->
            <div class="filter-container">
                <div class="filter-buttons">
                    <?php if ($user_role === 'Admin' || $user_role === 'Manager'): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#taskManagementModal">
                            Create New Task
                        </button>
                    <?php endif; ?>
                    <button onclick="resetFilters()" class="btn btn-primary">Reset</button>
                    <a href="export_tasks.php" class="btn btn-success">Export to CSV</a>
                </div>

                <!-- Filter Dropdowns and Date Range -->
                <div class="filter-row">
                    <!-- Multi-select dropdown for filtering by project -->
                    <div class="filter-dropdown">
                        <label for="project-filter">Filter by Project:</label>
                        <select id="project-filter" multiple="multiple">
                            <option value="All">All</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?= htmlspecialchars($project) ?>"><?= htmlspecialchars($project) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Multi-select dropdown for filtering by department -->
                    <div class="filter-dropdown">
                        <label for="department-filter">Filter by Department of Assigned User:</label>
                        <select id="department-filter" multiple="multiple">
                            <option value="All">All</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?= htmlspecialchars($department['name']) ?>">
                                    <?= htmlspecialchars($department['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Date Range Inputs -->
                    <div class="filter-date">
                        <div class="filter-dropdown">
                            <label for="start-date">Start Date:</label>
                            <input type="date" id="start-date" onchange="filterByDate()">
                        </div>
                        <div class="filter-dropdown">
                            <label for="end-date">End Date:</label>
                            <input type="date" id="end-date" onchange="filterByDate()">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending & Started Tasks Table -->
            <h3>Pending & Started Tasks</h3>
            <table class="table table-striped table-hover align-middle text-center" id="pending-tasks">
                <thead>
                    <tr class="align-middle">
                        <th>#</th>
                        <th>Project Name</th>
                        <th>Task Name</th>
                        <th>Task Description</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Project Type</th>
                        <th>Assigned By</th>
                        <?php if ($user_role !== 'User'): ?>
                            <th>Assigned To</th>
                        <?php endif; ?>
                        <th>Created On</th>
                        <?php if ($user_role !== 'User'): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $taskCount = 1; // Initialize task count
                    foreach ($rows as $row): ?>
                        <?php if ($row['status'] === 'Pending' || $row['status'] === 'Started'): ?>
                            <tr class="align-middle">
                                <td><?= $taskCount++ ?></td> <!-- Display task count and increment -->
                                <td><?= htmlspecialchars($row['project_name']) ?></td>
                                <td>
                                    <?php if ($row['status'] === 'Completed on Time'): ?>
                                        <!-- Link to Completed on Time Modal -->
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#viewDescriptionModal"
                                            data-description="<?= htmlspecialchars($row['completion_description']); ?>">
                                            <?= htmlspecialchars($row['task_name']); ?>
                                        </a>
                                    <?php elseif ($row['status'] === 'Delayed Completion'): ?>
                                        <!-- Link to Delayed Completion Modal -->
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#delayedCompletionModal"
                                            onclick="showDelayedDetails('<?php echo htmlspecialchars($row['task_name']); ?>', '<?php echo htmlspecialchars($row['actual_completion_date']); ?>', '<?php echo htmlspecialchars($row['delayed_reason']); ?>', '<?php echo htmlspecialchars($row['completion_description']); ?>')">
                                            <?php echo htmlspecialchars($row['task_name']); ?>
                                        </a>
                                    <?php else: ?>
                                        <!-- Plain Text for Other Statuses -->
                                        <?php echo htmlspecialchars($row['task_name']); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="task-description-container">
                                        <div class="task-description">
                                            <?= htmlspecialchars($row['task_description']) ?>
                                        </div>
                                        <a href="#" class="see-more-link" data-bs-toggle="modal"
                                            data-bs-target="#taskDescriptionModal"
                                            data-description="<?= htmlspecialchars($row['task_description']) ?>"
                                            style="display: none;">
                                            See more
                                        </a>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars(date("d M Y, h:i A", strtotime($row['expected_start_date']))) ?></td>
                                <td><?= htmlspecialchars(date("d M Y, h:i A", strtotime($row['expected_finish_date']))) ?></td>
                                <td>
                                    <form method="POST" action="update-status.php">
                                        <input type="hidden" name="task_id" value="<?= $row['task_id'] ?>">
                                        <select id="status" name="status"
                                            onchange="handleStatusChange(event, <?= $row['task_id'] ?>)"
                                            <?= in_array($row['status'], ['Completed on Time', 'Delayed Completion']) ? 'disabled' : '' ?>>
                                            <?php
                                            $statuses = ['Pending', 'Started', 'Completed on Time', 'Delayed Completion'];
                                            foreach ($statuses as $statusValue) {
                                                $selected = ($row['status'] === $statusValue) ? 'selected' : '';
                                                echo "<option value='$statusValue' $selected>$statusValue</option>";
                                            }
                                            ?>
                                        </select>
                                    </form>
                                </td>
                                <td><?= htmlspecialchars($row['project_type']) ?></td>
                                <td><?= htmlspecialchars($row['assigned_by']) ?>
                                    (<?= htmlspecialchars($row['assigned_by_department']) ?>)
                                </td>
                                <?php if ($user_role !== 'User'): ?>
                                    <td><?= htmlspecialchars($row['assigned_to']) ?>
                                        (<?= htmlspecialchars($row['assigned_to_department']) ?>)
                                    </td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars(date("d M Y, h:i A", strtotime($row['recorded_timestamp']))) ?></td>
                                <?php if (($user_role !== 'User' && $row['assigned_by_id'] == $_SESSION['user_id']) || $user_role == 'Admin'): ?>
                                    <td>
                                        <a href="edit-tasks.php?id=<?= $row['task_id'] ?>" class="edit-button">Edit</a>
                                        <form method="POST" action="delete-task.php" style="display:inline;">
                                            <input type="hidden" name="task_id" value="<?= $row['task_id'] ?>">
                                            <button type="submit" class="delete-button"
                                                onclick="return confirm('Are you sure?')">Delete</button>
                                        </form>
                                    </td>
                                <?php else: ?>

                                <?php endif; ?>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Alert for Pending & Started Tasks -->
            <div id="no-data-alert-pending" class="alert alert-warning mt-3" style="display: none;">
                No data found in Pending & Started Tasks matching the selected filters.
            </div>

            <!-- Completed Tasks Table -->
            <h3>Completed Tasks</h3>
            <table class="table table-striped table-hover align-middle text-center custom-table" id="remaining-tasks">
                <thead>
                    <tr class="align-middle">
                        <th>#</th> <!-- New column for task count -->
                        <th>Project Name</th>
                        <th>Task Name</th>
                        <th>Task Description</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Project Type</th>
                        <th>Assigned By</th>
                        <?php if ($user_role !== 'User'): ?>
                            <th>Assigned To</th>
                        <?php endif; ?>
                        <th>Created On</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $taskCount = 1; // Initialize task count
                    foreach ($rows as $row): ?>
                        <?php if ($row['status'] !== 'Pending' && $row['status'] !== 'Started'): ?>
                            <?php
                            $delayInfo = '';
                            if ($row['status'] === 'Delayed Completion') {
                                $expectedFinishDate = strtotime($row['expected_finish_date']);
                                $actualCompletionDate = strtotime($row['actual_completion_date']);

                                if ($actualCompletionDate && $expectedFinishDate) {
                                    // Calculate the number of weekdays between the expected finish date and actual completion date
                                    $weekdays = getWeekdays($expectedFinishDate, $actualCompletionDate);

                                    // Convert the delay into days and hours, excluding weekends
                                    $delayDays = $weekdays - 1; // Subtract 1 because the start day is included
                                    $delayHours = floor(($actualCompletionDate - $expectedFinishDate) % (60 * 60 * 24) / (60 * 60)); // Remaining hours
                                    $delayInfo = "{$delayDays} days, {$delayHours} hours delayed";
                                }
                            }
                            ?>
                            <tr data-project="<?= htmlspecialchars($row['project_name']) ?>"
                                data-status="<?= htmlspecialchars($row['status']) ?>" class="align-middle <?php if ($row['status'] === 'Delayed Completion')
                                      echo 'delayed-task'; ?>">
                                <td><?= $taskCount++ ?></td> <!-- Display task count and increment -->
                                <td><?= htmlspecialchars($row['project_name']) ?></td>
                                <td>
                                    <?php if ($row['status'] === 'Completed on Time'): ?>
                                        <!-- Link to Completed on Time Modal -->
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#viewDescriptionModal"
                                            data-description="<?= htmlspecialchars($row['completion_description']); ?>">
                                            <?= htmlspecialchars($row['task_name']); ?>
                                        </a>
                                    <?php elseif ($row['status'] === 'Delayed Completion'): ?>
                                        <!-- Link to Delayed Completion Modal -->
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#delayedCompletionModal"
                                            onclick="showDelayedDetails('<?php echo htmlspecialchars($row['task_name']); ?>', '<?php echo htmlspecialchars($row['actual_completion_date']); ?>', '<?php echo htmlspecialchars($row['delayed_reason']); ?>', '<?php echo htmlspecialchars($row['completion_description']); ?>')">
                                            <?php echo htmlspecialchars($row['task_name']); ?>
                                        </a>
                                    <?php else: ?>
                                        <!-- Plain Text for Other Statuses -->
                                        <?php echo htmlspecialchars($row['task_name']); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="task-description-container">
                                        <div class="task-description">
                                            <?= htmlspecialchars($row['task_description']) ?>
                                        </div>
                                        <a href="#" class="see-more-link" data-bs-toggle="modal"
                                            data-bs-target="#taskDescriptionModal"
                                            data-description="<?= htmlspecialchars($row['task_description']) ?>"
                                            style="display: none;">
                                            See more
                                        </a>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars(date("d M Y, h:i A", strtotime($row['expected_start_date']))) ?></td>
                                <td>
                                    <?= htmlspecialchars(date("d M Y, h:i A", strtotime($row['expected_finish_date']))) ?>
                                    <?php if ($row['status'] === 'Delayed Completion'): ?>
                                        <?php
                                        $expectedFinishDate = strtotime($row['expected_finish_date']);
                                        $actualCompletionDate = strtotime($row['actual_completion_date']);
                                        if ($actualCompletionDate && $expectedFinishDate) {
                                            // Calculate the number of weekdays between the expected finish date and actual completion date
                                            $weekdays = getWeekdays($expectedFinishDate, $actualCompletionDate);

                                            // Convert the delay into days and hours, excluding weekends
                                            $delayDays = $weekdays - 1; // Subtract 1 because the start day is included
                                            $delayHours = floor(($actualCompletionDate - $expectedFinishDate) % (60 * 60 * 24) / (60 * 60)); // Remaining hours
                                            echo "<br><small class='text-danger'>{$delayDays} days, {$delayHours} hours delayed</small>";
                                            echo "<br><small class='text-muted'>Completed on: " . date("d M Y, h:i A", $actualCompletionDate) . "</small>";
                                        }
                                        ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" action="update-status.php">
                                        <input type="hidden" name="task_id" value="<?= $row['task_id'] ?>">
                                        <select id="status" name="status"
                                            onchange="handleStatusChange(event, <?= $row['task_id'] ?>)"
                                            <?= in_array($row['status'], ['Completed on Time', 'Delayed Completion']) ? 'disabled' : '' ?>>
                                            <?php
                                            $statuses = ['Pending', 'Started', 'Completed on Time', 'Delayed Completion'];
                                            foreach ($statuses as $statusValue) {
                                                $selected = ($row['status'] === $statusValue) ? 'selected' : '';
                                                echo "<option value='$statusValue' $selected>$statusValue</option>";
                                            }
                                            ?>
                                        </select>
                                    </form>
                                </td>
                                <td><?= htmlspecialchars($row['project_type']) ?></td>
                                <td><?= htmlspecialchars($row['assigned_by']) ?>
                                    (<?= htmlspecialchars($row['assigned_by_department']) ?>)
                                </td>
                                <?php if ($user_role !== 'User'): ?>
                                    <td><?= htmlspecialchars($row['assigned_to']) ?>
                                        (<?= htmlspecialchars($row['assigned_to_department']) ?>)
                                    </td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars(date("d M Y, h:i A", strtotime($row['recorded_timestamp']))) ?></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Alert for Completed Tasks -->
            <div id="no-data-alert-completed" class="alert alert-warning mt-3" style="display: none;">
                No data found in Completed Tasks matching the selected filters.
            </div>
        </div>
    </div>

    <!-- Modal for Task Completion -->
    <div class="modal fade" id="completionModal" tabindex="-1" aria-labelledby="completionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="completionForm" method="POST" onsubmit="handleCompletionForm(event)">
                    <div class="modal-header">
                        <h5 class="modal-title" id="completionModalLabel">Task Completion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Hidden input for Task ID -->
                        <input type="hidden" id="task-id" name="task_id">
                        <!-- Hidden input for Status -->
                        <input type="hidden" id="modal-status" name="status">

                        <!-- Completion Description -->
                        <div class="mb-3">
                            <label for="completion-description" class="form-label">What was completed?</label>
                            <textarea class="form-control" id="completion-description" name="completion_description"
                                rows="3" required></textarea>
                        </div>

                        <!-- Delayed Reason (Shown only for Delayed Completion) -->
                        <div class="mb-3" id="delayed-reason-container" style="display: none;">
                            <label for="delayed-reason" class="form-label">Why was it completed late?</label>
                            <textarea class="form-control" id="delayed-reason" name="delayed_reason"
                                rows="3"></textarea>
                        </div>

                        <!-- Actual Completion Date -->
                        <div class="mb-3" id="completion-date-container" style="display: none;">
                            <label for="actual-completion-date" class="form-label">Actual Completion Date</label>
                            <input type="datetime-local" class="form-control" id="actual-completion-date"
                                name="actual_completion_date">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for Viewing Completion Description -->
    <div class="modal fade" id="viewDescriptionModal" tabindex="-1" aria-labelledby="viewDescriptionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewDescriptionModalLabel">Task Completion Description</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="completion-description-text">No description provided.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="delayedCompletionModal" tabindex="-1" aria-labelledby="delayedCompletionModalLabel"
        aria-hidden="true">
        <!-- Modal for delayed completion details viewing -->
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="delayedCompletionModalLabel">Delayed Completion Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Task Name:</strong> <span id="delayed-task-name"></span></p>
                    <p><strong>Completed On:</strong> <span id="delayed-completion-date"></span></p>
                    <p><strong>Reason for Delay:</strong></p>
                    <p id="delay-reason"></p>
                    <p><strong>Completion Description:</strong></p>
                    <p id="completion-description-delayed"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success modal for task updation -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Status Updated</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Task Name:</strong> <span id="success-task-name"></span></p>
                    <p><strong>Message:</strong> <span id="success-message"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for task description -->
    <div class="modal fade" id="taskDescriptionModal" tabindex="-1" aria-labelledby="taskDescriptionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="taskDescriptionModalLabel">Task Description</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="full-task-description"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Jquery -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>

    <!-- Script for opening the modal to view details of completion -->
    <script>
        // Attach event listener for task name links
        const viewDescriptionModal = document.getElementById('viewDescriptionModal');
        viewDescriptionModal.addEventListener('show.bs.modal', function (event) {
            // Button/link that triggered the modal
            const button = event.relatedTarget;

            // Extract completion description from data attribute
            const description = button.getAttribute('data-description');

            // Update the modal content
            const descriptionText = document.getElementById('completion-description-text');
            descriptionText.textContent = description || "No description provided.";
        });
    </script>

    <script>
        const viewDescriptionModal = document.getElementById('viewDescriptionModal');
        viewDescriptionModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const description = button.getAttribute('data-description');
            const descriptionText = document.getElementById('completion-description-text');
            descriptionText.textContent = description || "No description provided.";
        });
    </script>

    <!-- JS for the dropdown handling -->
    <script>
        function handleStatusChange(event, taskId) {
            event.preventDefault();

            const status = event.target.value;
            const form = event.target.form;

            // If the status is 'Delayed Completion' or 'Completed on Time', show the modal
            if (status === 'Delayed Completion' || status === 'Completed on Time') {
                document.getElementById('task-id').value = taskId;
                document.getElementById('modal-status').value = status;

                const delayedReasonContainer = document.getElementById('delayed-reason-container');
                const completionDateContainer = document.getElementById('completion-date-container');

                if (status === 'Delayed Completion') {
                    delayedReasonContainer.style.display = 'block';
                    completionDateContainer.style.display = 'block';
                } else {
                    delayedReasonContainer.style.display = 'none';
                    completionDateContainer.style.display = 'none';
                }

                const modal = new bootstrap.Modal(document.getElementById('completionModal'));
                modal.show();
            } else {
                // For other statuses, submit the form directly
                fetch('update-status.php', {
                    method: 'POST',
                    body: new FormData(form)
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json(); // Parse the response as JSON
                    })
                    .then(data => {
                        if (data.success) {
                            // Update the modal content with the task name and message
                            document.getElementById('success-task-name').innerText = data.task_name;
                            document.getElementById('success-message').innerText = data.message;

                            // Show the success modal
                            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                            successModal.show();
                        } else {
                            // If the update was not successful, show an alert with the error message
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the status.');
                    });
            }
        }
    </script>

    <!-- Script for viewing the delayed completion details -->
    <script>
        function showDelayedDetails(taskName, completionDate, delayReason, completionDescription) {
            // Set the modal elements with the provided values
            document.getElementById('delayed-task-name').innerText = taskName || "N/A";
            document.getElementById('delayed-completion-date').innerText = completionDate || "N/A";
            document.getElementById('delay-reason').innerText = delayReason || "N/A";

            // Correctly set the completion description
            const completionDescriptionElement = document.getElementById('completion-description-delayed');
            completionDescriptionElement.innerText = completionDescription && completionDescription.trim() ? completionDescription : "No description provided.";
        }
    </script>
    <!-- Script for handling completion form -->
    <script>
        function handleCompletionForm(event) {
            event.preventDefault(); // Prevent the default form submission

            const form = event.target;
            const formData = new FormData(form);

            fetch('update-status.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json(); // Parse the response as JSON
                })
                .then(data => {
                    if (data.success) {
                        // Close the completion modal
                        const completionModal = bootstrap.Modal.getInstance(document.getElementById('completionModal'));
                        completionModal.hide();

                        // Show the success modal
                        document.getElementById('success-task-name').innerText = data.task_name;
                        document.getElementById('success-message').innerText = data.message;
                        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                        successModal.show();

                        // Optionally, refresh the task list or update the UI
                        setTimeout(() => {
                            window.location.reload(); // Reload the page to reflect the updated status
                        }, 2000); // Reload after 2 seconds
                    } else {
                        alert(data.message); // Show an error message if the update failed
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the status.');
                });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
        </script>
    <!-- Fix for Select2 and Filtering -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            // Get the user's role from the hidden input field
            const userRole = document.getElementById('user-role').value;

            // Initialize Select2 on the project filter dropdown
            $('#project-filter').select2({
                placeholder: "Select projects to filter",
                allowClear: true,
                width: '300px'
            });

            // Initialize Select2 on the department filter dropdown (only for admins/managers)
            if (userRole === 'Admin' || userRole === 'Manager') {
                $('#department-filter').select2({
                    placeholder: "Select departments to filter",
                    allowClear: true,
                    width: '300px'
                });
            } else {
                // Hide the department filter for regular users
                $('#department-filter').closest('.filter-dropdown').hide();
            }

            // Remove the "All" option initially
            $('#project-filter option[value="All"]').remove();
            $('#department-filter option[value="All"]').remove();

            // Trigger combined filtering when any filter changes
            $('#project-filter, #department-filter, #start-date, #end-date').on('change', function () {
                applyAllFilters();
            });

            // Function to apply all filters (project, department, and date)
            function applyAllFilters() {
                const selectedProjects = $('#project-filter').val();
                const selectedDepartments = $('#department-filter').val();
                const startDate = document.getElementById('start-date').value ? new Date(document.getElementById('start-date').value) : null;
                const endDate = document.getElementById('end-date').value ? new Date(document.getElementById('end-date').value) : null;

                // Define tables and their corresponding alerts
                const tables = [
                    { id: 'pending-tasks', alertId: 'no-data-alert-pending' },
                    { id: 'remaining-tasks', alertId: 'no-data-alert-completed' }
                ];

                tables.forEach(table => {
                    const rows = document.querySelectorAll(`#${table.id} tbody tr`);
                    let hasVisibleRows = false; // Flag to track if any rows are visible in this table

                    rows.forEach(row => {
                        const projectName = row.querySelector('td:nth-child(2)').textContent.trim(); // Project name column
                        const assignedToText = row.querySelector('td:nth-child(10)').textContent.trim(); // Assigned To column (9th column)

                        // Extract the department names from the "Assigned To" column
                        const departmentMatch = assignedToText.match(/\(([^)]+)\)/); // Extract department(s) from parentheses
                        const departmentNames = departmentMatch ? departmentMatch[1].trim().split(', ') : [];

                        const taskStartDate = new Date(row.querySelector('td:nth-child(5)').textContent.trim()); // Start Date column
                        const taskEndDate = new Date(row.querySelector('td:nth-child(6)').textContent.trim()); // End Date column

                        // Check if the row matches the selected projects
                        const projectMatch = selectedProjects === null || selectedProjects.length === 0 || selectedProjects.includes(projectName);

                        // Check if the row matches the selected departments (only for admins/managers)
                        let isDepartmentMatch = true; // Default to true for regular users
                        if (userRole === 'Admin' || userRole === 'Manager') {
                            // Only check the assigned to department
                            isDepartmentMatch = selectedDepartments === null || selectedDepartments.length === 0 || selectedDepartments.some(department => departmentNames.includes(department));
                        }

                        // Check if the task falls within the selected date range
                        let dateMatch = true;
                        if (startDate && taskStartDate < startDate) {
                            dateMatch = false;
                        }
                        if (endDate && taskEndDate > endDate) {
                            dateMatch = false;
                        }

                        // Display the row only if it matches all filters
                        if (projectMatch && isDepartmentMatch && dateMatch) {
                            row.style.display = ''; // Show the row
                            hasVisibleRows = true; // At least one row is visible in this table
                        } else {
                            row.style.display = 'none'; // Hide the row
                        }
                    });

                    // Show/hide the "No data found" alert for this table
                    const noDataAlert = document.getElementById(table.alertId);
                    if (hasVisibleRows) {
                        noDataAlert.style.display = 'none'; // Hide the alert if rows are visible
                    } else {
                        noDataAlert.style.display = 'block'; // Show the alert if no rows are visible
                    }
                });
            }

            // Reset filters
            function resetFilters() {
                // Clear the selected values in the dropdowns
                $('#project-filter').val(null).trigger('change');
                $('#department-filter').val(null).trigger('change');

                // Clear date inputs
                document.getElementById('start-date').value = '';
                document.getElementById('end-date').value = '';

                // Reapply filters to show all tasks
                applyAllFilters();
            }

            // Attach event listener for reset button
            document.querySelector('.btn-primary[onclick="resetFilters()"]').onclick = resetFilters;
        });
    </script>

    <!-- JS for task description modal -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const taskDescriptionModal = document.getElementById('taskDescriptionModal');
            taskDescriptionModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; // Button/link that triggered the modal
                const description = button.getAttribute('data-description'); // Extract description from data attribute
                const modalBody = taskDescriptionModal.querySelector('.modal-body p');
                modalBody.textContent = description; // Set the modal content
            });
        });
    </script>

    <!-- To check if task desc is more than 2 lines -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Function to check if the description exceeds 2 lines
            function checkDescriptionHeight() {
                const descriptionContainers = document.querySelectorAll('.task-description-container');

                descriptionContainers.forEach(container => {
                    const descriptionElement = container.querySelector('.task-description');
                    const seeMoreLink = container.querySelector('.see-more-link');

                    // Calculate the height of the description element
                    const lineHeight = parseInt(window.getComputedStyle(descriptionElement).lineHeight);
                    const maxHeight = lineHeight * 2; // Max height for 2 lines

                    if (descriptionElement.scrollHeight > maxHeight) {
                        // If the description exceeds 2 lines, show the "See more" link
                        seeMoreLink.style.display = 'block';
                    }
                });
            }

            // Run the check when the page loads
            checkDescriptionHeight();

            // Optional: Re-check if the window is resized (in case of dynamic content or layout changes)
            window.addEventListener('resize', checkDescriptionHeight);
        });
    </script>
</body>

</html>
<?php $conn->close(); ?>