<?php
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
$timeout_duration = 600;

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

// Fetch users for task assignment (admin and manager roles)
$users = [];
if ($user_role === 'admin' || $user_role === 'manager') {
    if ($user_role === 'admin') {
        // Admin can assign tasks to users and managers
        $userQuery = "SELECT id, username, email FROM users WHERE role IN ('user', 'manager')";
    } else {
        // Manager can only assign tasks to users in their department
        $userQuery = "SELECT id, username, email 
                      FROM users 
                      WHERE role = 'user' AND department = (SELECT department FROM users WHERE id = $user_id) OR id = $user_id";
    }

    $userResult = $conn->query($userQuery);
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

    if (empty($project_name) || empty($task_name) || empty($task_description) || empty($project_type) || empty($expected_start_date) || empty($expected_finish_date) || !$assigned_user_id) {
        echo '<script>alert("Please fill in all required fields.");</script>';
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO tasks (user_id, project_name, task_name, task_description, project_type, expected_start_date, expected_finish_date, status, recorded_timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "issssssss",
            $assigned_user_id,
            $project_name,
            $task_name,
            $task_description,
            $project_type,
            $expected_start_date,
            $expected_finish_date,
            $status,
            $recorded_timestamp
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
$taskQuery = $user_role === 'admin'
    ? "SELECT tasks.*, users.username AS assigned_to, users.department AS department 
       FROM tasks 
       JOIN users ON tasks.user_id = users.id 
       ORDER BY FIELD(status, 'Completed on Time', 'Delayed Completion', 'Started', 'Pending'), recorded_timestamp DESC"
    : ($user_role === 'manager'
        ? "SELECT tasks.*, users.username AS assigned_to, users.department AS department 
       FROM tasks 
       JOIN users ON tasks.user_id = users.id 
       WHERE users.department = (SELECT department FROM users WHERE id = ?) 
       ORDER BY FIELD(status, 'Completed on Time', 'Delayed Completion', 'Started', 'Pending'), recorded_timestamp DESC"
        : "SELECT * FROM tasks WHERE user_id = ? ORDER BY FIELD(status, 'Completed on Time', 'Delayed Completion', 'Started', 'Pending'), recorded_timestamp DESC");

$stmt = $conn->prepare($taskQuery);
if ($user_role === 'manager' || $user_role === 'user') {
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
    <link rel="icon" type="image/png" sizes="56x56" href="images/logo/logo-2.1.ico" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
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

        .filter-date {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: center;
        }

        .filter-date label {
            font-weight: bold;
            color: #333;
        }

        .filter-date input {
            padding: 5px 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="logout-button">
        <a href="welcome.php">Back</a>
    </div>

    <?php if ($user_role === 'admin' || $user_role === 'manager'): ?>
        <div class="task-container">
            <h2>Task Management</h2>
            <form method="post" action="">
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
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="submit-btn">Add Task</button>
            </form>
        </div>
    <?php endif; ?>
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
        <h2>Tasks</h2>

        <!-- Filter Buttons and Date Pickers -->
        <div class="filter-container">
            <div class="filter-buttons">
                <button onclick="filterTasks('All')" class="btn btn-primary">All</button>
                <?php foreach ($projects as $project): ?>
                    <button onclick="filterTasks('<?= htmlspecialchars($project) ?>')" class="btn btn-secondary">
                        <?= htmlspecialchars($project) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="filter-date">
                <label for="start-date">Start Date:</label>
                <input type="date" id="start-date" onchange="filterByDate()">
                <label for="end-date">End Date:</label>
                <input type="date" id="end-date" onchange="filterByDate()">
            </div>
        </div>

        <!-- Tasks Table -->
        <table id="tasks-table" class="task-table">
            <thead>
                <tr>
                    <th>Project Name</th>
                    <th>Task Name</th>
                    <th>Task Description</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Project Type</th>
                    <?php if ($user_role !== 'user'): ?>
                        <th>Assigned To</th>
                        <th>Department</th>
                    <?php endif; ?>
                    <th>Created At</th>
                    <?php if ($user_role !== 'user'): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr data-project="<?= htmlspecialchars($row['project_name']) ?>"
                        data-status="<?= htmlspecialchars($row['status']) ?>">
                        <td><?= htmlspecialchars($row['project_name']) ?></td>
                        <td>
                            <?php if ($row['status'] === 'Completed on Time'): ?>
                                <!-- Link to Completed on Time Modal -->
                                <a href="#" data-bs-toggle="modal" data-bs-target="#viewDescriptionModal"
                                    data-description="<?= htmlspecialchars($row['completion_description']) ?>">
                                    <?= htmlspecialchars($row['task_name']) ?>
                                </a>
                                <?php elseif ($row['status'] === 'Delayed Completion'): ?>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#delayedCompletionModal"
                                        data-description="'<?php echo htmlspecialchars($row['task_name'], ENT_QUOTES); ?>',
                                            '<?php echo htmlspecialchars($row['actual_completion_date'], ENT_QUOTES); ?>',
                                            '<?php echo htmlspecialchars($row['delayed_reason'], ENT_QUOTES); ?>',
                                            '<?php echo htmlspecialchars($row['completion_description'], ENT_QUOTES); ?>')">
                                        <?php echo htmlspecialchars($row['task_name']); ?>
                                    </a>
                            <?php else: ?>
                                <!-- Plain Text for Other Statuses -->
                                <?php echo htmlspecialchars($row['task_name']); ?>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['task_description']) ?></td>
                        <td><?= htmlspecialchars(date("d M Y, h:i A", strtotime($row['expected_start_date']))) ?></td>
                        <td><?= htmlspecialchars(date("d M Y, h:i A", strtotime($row['expected_finish_date']))) ?></td>
                        <td>
                            <form method="POST" action="update-status.php">
                                <input type="hidden" name="task_id" value="<?php echo $row['task_id']; ?>">
                                <select name="status" onchange="this.form.submit()" <?php if (in_array($row['status'], ['Completed on Time', 'Delayed Completion'])) {
                                    echo 'disabled';
                                } ?>>
                                    <option value="Pending" <?php echo ($row['status'] === 'Pending') ? 'selected' : ''; ?>>
                                        Pending</option>
                                    <option value="Started" <?php echo ($row['status'] === 'Started') ? 'selected' : ''; ?>>
                                        Started</option>
                                    <option value="Completed on Time" <?php echo ($row['status'] === 'Completed on Time') ? 'selected' : ''; ?>>Completed on Time</option>
                                    <option value="Delayed Completion" <?php echo ($row['status'] === 'Delayed Completion') ? 'selected' : ''; ?>>Delayed Completion</option>
                                </select>
                            </form>
                        </td>
                        <td><?= htmlspecialchars($row['project_type']) ?></td>
                        <?php if ($user_role !== 'user'): ?>
                            <td><?= htmlspecialchars($row['assigned_to']) ?></td>
                            <td><?= htmlspecialchars($row['department']) ?></td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars(date("d M Y, h:i A", strtotime($row['recorded_timestamp']))) ?></td>
                        <?php if ($user_role !== 'user'): ?>
                            <td>
                                <a href="edit-tasks.php?id=<?= $row['task_id'] ?>" class="edit-button">Edit</a>
                                <form method="POST" action="delete-task.php">
                                    <input type="hidden" name="task_id" value="<?= $row['task_id'] ?>">
                                    <button type="submit" class="delete-button"
                                        onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal & script for the completion of tasks -->
    <!-- Modal for Delayed Completion -->
    <div class="modal fade" id="completionModal" tabindex="-1" aria-labelledby="completionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="update-status.php">
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
                    <p id="completion-description"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

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
        }
        if (event.target.value === 'Completed on Time') {
            event.preventDefault();

            // Populate the hidden fields
            document.getElementById('task-id').value = taskId;
            document.getElementById('modal-status').value = document.getElementById('status').value;

            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('completionModal'));
            modal.show();
        } else {
            console.log("Submitting form with other status:", event.target.value);
            event.target.form.submit();
        }
    </script>
    <!-- script for the filtering -->
    <script>
        function filterTasks(project) {
            const rows = document.querySelectorAll('#tasks-table tbody tr');
            rows.forEach(row => {
                if (project === 'All' || row.dataset.project === project) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function filterByDate() {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;

            const rows = document.querySelectorAll('#tasks-table tbody tr');
            rows.forEach(row => {
                const rowDate = row.querySelector('td:nth-child(4)').textContent.trim(); // Start Date column
                const taskDate = new Date(rowDate);

                if ((startDate && taskDate < new Date(startDate)) || (endDate && taskDate > new Date(endDate))) {
                    row.style.display = 'none';
                } else {
                    row.style.display = '';
                }
            });
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
            const completionDescriptionElement = document.getElementById('completion-description');
            completionDescriptionElement.innerText = completionDescription || "No description provided.";
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
        </script>
</body>

</html>
<?php $conn->close(); ?>