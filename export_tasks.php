<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: portal-login.html");
    exit;
}

// Database connection
$config = include '../config.php';
$dbHost = 'localhost';
$dbUsername = $config['dbUsername'];
$dbPassword = $config['dbPassword'];
$dbName = 'euro_login_system';

$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch tasks based on user role
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

$taskQuery = $user_role === 'Admin'
    ? "SELECT tasks.*, 
              assigned_to_user.username AS assigned_to, 
              assigned_to_user.department AS department, 
              assigned_by_user.username AS assigned_by 
       FROM tasks 
       JOIN users AS assigned_to_user ON tasks.user_id = assigned_to_user.id 
       JOIN users AS assigned_by_user ON tasks.assigned_by_id = assigned_by_user.id 
       ORDER BY FIELD(status, 'Completed on Time', 'Delayed Completion', 'Pending', 'Started'), recorded_timestamp DESC"
    : ($user_role === 'Manager'
        ? "SELECT tasks.*, 
                  assigned_to_user.username AS assigned_to, 
                  assigned_to_user.department AS department, 
                  assigned_by_user.username AS assigned_by 
           FROM tasks 
           JOIN users AS assigned_to_user ON tasks.user_id = assigned_to_user.id 
           JOIN users AS assigned_by_user ON tasks.assigned_by_id = assigned_by_user.id 
           WHERE assigned_to_user.department = (SELECT department FROM users WHERE id = ?) 
           ORDER BY FIELD(status, 'Completed on Time', 'Delayed Completion', 'Pending', 'Started'), recorded_timestamp DESC"
        : "SELECT tasks.*, 
                  assigned_by_user.username AS assigned_by 
           FROM tasks 
           JOIN users AS assigned_by_user ON tasks.assigned_by_id = assigned_by_user.id 
           WHERE tasks.user_id = ? 
           ORDER BY FIELD(status, 'Completed on Time', 'Delayed Completion', 'Pending', 'Started'), recorded_timestamp DESC");

$stmt = $conn->prepare($taskQuery);
if ($user_role === 'Manager' || $user_role === 'User') {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="tasks_export.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV headers
$headers = [
    'Project Name', 'Task Name', 'Task Description', 'Start Date', 'End Date', 'Status', 
    'Project Type', 'Assigned By', 'Assigned To', 'Department', 'Created On'
];

// Write Pending Tasks Section
fputcsv($output, ['Pending & Started Tasks']);
fputcsv($output, $headers); // Write headers for Pending Tasks

while ($row = $result->fetch_assoc()) {
    if ($row['status'] === 'Pending') {
        $rowData = [
            $row['project_name'],
            $row['task_name'],
            $row['task_description'],
            date("d M Y, h:i A", strtotime($row['expected_start_date'])),
            date("d M Y, h:i A", strtotime($row['expected_finish_date'])),
            $row['status'],
            $row['project_type'],
            $row['assigned_by'],
            $row['assigned_to'] ?? '', // Handle cases where assigned_to is not available
            $row['department'] ?? '',  // Handle cases where department is not available
            date("d M Y, h:i A", strtotime($row['recorded_timestamp']))
        ];
        fputcsv($output, $rowData);
    }
}

// Add a blank row to separate sections
fputcsv($output, []);

// Write Other Tasks Section
fputcsv($output, ['Completed Tasks']);
fputcsv($output, $headers); // Write headers for Other Tasks

// Reset the result pointer to iterate again
$result->data_seek(0);

while ($row = $result->fetch_assoc()) {
    if ($row['status'] !== 'Pending') {
        $rowData = [
            $row['project_name'],
            $row['task_name'],
            $row['task_description'],
            date("d M Y, h:i A", strtotime($row['expected_start_date'])),
            date("d M Y, h:i A", strtotime($row['expected_finish_date'])),
            $row['status'],
            $row['project_type'],
            $row['assigned_by'],
            $row['assigned_to'] ?? '', // Handle cases where assigned_to is not available
            $row['department'] ?? '',  // Handle cases where department is not available
            date("d M Y, h:i A", strtotime($row['recorded_timestamp']))
        ];
        fputcsv($output, $rowData);
    }
}

// Close output stream
fclose($output);
exit;
?>