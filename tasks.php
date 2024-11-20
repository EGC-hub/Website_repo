<?php
// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Redirect to login page if not logged in
    header("Location: portal-login.html");
    exit;
}

// Retrieve the username and user ID from the session
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id']; // Make sure the user ID is stored in the session during login

// Optional: Session timeout settings
$timeout_duration = 600;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // If the session is expired, destroy it and redirect to login page
    session_unset();
    session_destroy();
    header("Location: portal-login.html");
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Database configuration
$servername = "localhost";
$username = "euro_admin";
$password = "euroglobal123";
$dbname = "euro_contact_form_db"; // Use your appropriate database

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $task_name = $_POST['task_name'];
    $expected_start_date = $_POST['expected_start_date'];
    $expected_finish_date = $_POST['expected_finish_date'];
    $status = $_POST['status'];

    // Prepare and bind SQL statement
    $sql = "INSERT INTO tasks (user_id, task_name, expected_start_date, expected_finish_date, status) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    // Check if preparation is successful
    if (!$stmt) {
        die("Preparation failed: " . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param("issss", $user_id, $task_name, $expected_start_date, $expected_finish_date, $status);

    // Execute the statement and check if it is successful
    if ($stmt->execute()) {
        // Task added successfully
        echo "<script>alert('Task added successfully!');</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks Page</title>
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
            margin: 0 auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-weight: bold;
        }

        input[type="text"], input[type="date"], select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #002c5f;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .submit-btn:hover {
            background-color: #004080;
        }
    </style>
</head>
<body>

<div class="task-container">
    <h2>Add New Task</h2>
    <form action="" method="POST">
        <label for="task_name">Task Name:</label>
        <input type="text" id="task_name" name="task_name" required>

        <label for="expected_start_date">Expected Start Date:</label>
        <input type="date" id="expected_start_date" name="expected_start_date" required>

        <label for="expected_finish_date">Expected Finish Date:</label>
        <input type="date" id="expected_finish_date" name="expected_finish_date" required>

        <label for="status">Status:</label>
        <select id="status" name="status" required>
            <option value="Pending">Pending</option>
            <option value="Started">Started</option>
            <option value="Completed">Completed</option>
        </select>

        <button type="submit" class="submit-btn">Add Task</button>
    </form>
</div>

<!-- Empty bottom section for now -->
<div class="task-bottom-section">
    <!-- This section is left empty for future implementation -->
</div>

</body>
</html>
