<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Redirect to login page if not logged in
    header("Location: portal-login.html");
    exit;
}

// Database connection
$config = include '../config.php';
$dsn = "mysql:host=localhost;dbname=euro_login_system;charset=utf8mb4";
$username = $config['dbUsername'];
$password = $config['dbPassword'];

// Function to generate colors
function generateColors($count)
{
    $colors = ['#FF6384', '#36A2EB', '#4BC0C0', '#FFCE56', '#9966FF', '#FF8A80', '#7CB342', '#FFD54F', '#64B5F6', '#BA68C8'];
    // If there are more departments than predefined colors, generate random colors
    if ($count > count($colors)) {
        for ($i = count($colors); $i < $count; $i++) {
            $colors[] = '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        }
    }
    return array_slice($colors, 0, $count); // Return only the required number of colors
}

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Retrieve the username, role, and user ID from the session
    $username = $_SESSION['username'] ?? 'Unknown'; // Fallback to 'Unknown' if not set
    $userRole = $_SESSION['role'] ?? 'Unknown'; // Fallback to 'Unknown' if not set
    $userId = $_SESSION['user_id'] ?? null; // User ID from session

    // For admin
    // Fetch all departments assigned to the user
    $userDepartments = [];
    if ($userId) {
        $stmt = $pdo->prepare("
            SELECT d.name 
            FROM user_departments ud
            JOIN departments d ON ud.department_id = d.id
            WHERE ud.user_id = :user_id
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $userDepartments = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // For manager
    // Fetch all departments assigned to the manager
    $managerDepartments = [];
    if ($userId && $userRole === 'Manager') {
        $stmt = $pdo->prepare("
        SELECT d.name 
        FROM user_departments ud
        JOIN departments d ON ud.department_id = d.id
        WHERE ud.user_id = :user_id
    ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $managerDepartments = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Fetch total tasks
    if ($userRole === 'Admin') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_tasks FROM tasks");
        $stmt->execute();
        $totalTasks = $stmt->fetch(PDO::FETCH_ASSOC)['total_tasks'];

        // Fetch tasks in progress
        $stmt = $pdo->prepare("SELECT COUNT(*) as tasks_in_progress FROM tasks WHERE status = 'Started'");
        $stmt->execute();
        $tasksInProgress = $stmt->fetch(PDO::FETCH_ASSOC)['tasks_in_progress'];

        // Fetch completed tasks
        $stmt = $pdo->prepare("SELECT COUNT(*) as completed_tasks FROM tasks WHERE status = 'Completed on Time'");
        $stmt->execute();
        $completedTasks = $stmt->fetch(PDO::FETCH_ASSOC)['completed_tasks'];

        // Fetch delayed tasks
        $stmt = $pdo->prepare("SELECT COUNT(*) as delayed_tasks FROM tasks WHERE status = 'Delayed Completion'");
        $stmt->execute();
        $delayedTasks = $stmt->fetch(PDO::FETCH_ASSOC)['delayed_tasks'];

        // Fetch average task duration
        $stmt = $pdo->prepare(
            "SELECT AVG(TIMESTAMPDIFF(DAY, expected_start_date, expected_finish_date)) as avg_duration FROM tasks WHERE status = 'Completed on Time'"
        );
        $stmt->execute();
        $avgDuration = $stmt->fetch(PDO::FETCH_ASSOC)['avg_duration'];
        $avgDuration = round($avgDuration, 1); // Round to one decimal place

        // Fetch tasks by department
        $stmt = $pdo->prepare("
        SELECT d.name, COUNT(t.task_id) as task_count 
        FROM tasks t
        JOIN users u ON t.user_id = u.id
        JOIN user_departments ud ON u.id = ud.user_id
        JOIN departments d ON ud.department_id = d.id
        GROUP BY d.name
        ");
        $stmt->execute();
        $tasksByDepartment = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Generate colors dynamically based on the number of departments
        $departmentColors = generateColors(count($tasksByDepartment));

        // Fetch task distribution by status
        $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'Started' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'Completed on Time' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'Delayed Completion' THEN 1 ELSE 0 END) as 'delayed'
        FROM tasks
        ");
        $stmt->execute();
        $taskDistribution = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch task completion over time (grouped by month)
        $stmt = $pdo->prepare("
        SELECT 
        DATE_FORMAT(expected_finish_date, '%b') as month,
        COUNT(*) as tasks_completed
        FROM tasks
        WHERE status = 'Completed on Time'
        GROUP BY DATE_FORMAT(expected_finish_date, '%Y-%m')
        ORDER BY expected_finish_date;
        ");
        $stmt->execute();
        $taskCompletionOverTime = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch top performers
        $stmt = $pdo->prepare("
        SELECT 
        u.username, 
        d.name as department, 
        COUNT(t.task_id) as tasks_completed 
        FROM tasks t
        JOIN users u ON t.user_id = u.id
        JOIN user_departments ud ON u.id = ud.user_id
        JOIN departments d ON ud.department_id = d.id
        WHERE t.status = 'Completed on Time'
        GROUP BY u.username, d.name
        ORDER BY tasks_completed DESC
        LIMIT 3;
        ");
        $stmt->execute();
        $topPerformers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    }

    if ($userRole === 'Manager') {
        // For manager
        // Fetch total tasks for manager's departments
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_tasks 
        FROM tasks t
        JOIN user_departments ud ON t.user_id = ud.user_id
        WHERE ud.department_id IN (
            SELECT department_id 
            FROM user_departments 
            WHERE user_id = :user_id
        )
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $totalTasks = $stmt->fetch(PDO::FETCH_ASSOC)['total_tasks'];

        // Fetch tasks in progress for manager's departments
        $stmt = $pdo->prepare("SELECT COUNT(*) as tasks_in_progress 
        FROM tasks t
        JOIN user_departments ud ON t.user_id = ud.user_id
        WHERE t.status = 'Started' AND ud.department_id IN (
            SELECT department_id 
            FROM user_departments 
            WHERE user_id = :user_id
        )
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $tasksInProgress = $stmt->fetch(PDO::FETCH_ASSOC)['tasks_in_progress'];

        // Fetch tasks in progress for manager's departments
        $stmt = $pdo->prepare("
        SELECT COUNT(*) as tasks_in_progress 
        FROM tasks t
        JOIN user_departments ud ON t.user_id = ud.user_id
        WHERE t.status = 'Started' AND ud.department_id IN (
            SELECT department_id 
            FROM user_departments 
            WHERE user_id = :user_id
        )
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $tasksInProgress = $stmt->fetch(PDO::FETCH_ASSOC)['tasks_in_progress'];

        // Fetch completed tasks for manager's departments
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as completed_tasks 
            FROM tasks t
            JOIN user_departments ud ON t.user_id = ud.user_id
            WHERE t.status = 'Completed on Time' AND ud.department_id IN (
                SELECT department_id 
                FROM user_departments 
                WHERE user_id = :user_id
            )");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $completedTasks = $stmt->fetch(PDO::FETCH_ASSOC)['completed_tasks'];

        // Fetch delayed tasks for manager's departments
        $stmt = $pdo->prepare("
        SELECT COUNT(*) as delayed_tasks 
        FROM tasks t
        JOIN user_departments ud ON t.user_id = ud.user_id
        WHERE t.status = 'Delayed Completion' AND ud.department_id IN (
            SELECT department_id 
            FROM user_departments 
            WHERE user_id = :user_id
        )
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $delayedTasks = $stmt->fetch(PDO::FETCH_ASSOC)['delayed_tasks'];

        // Fetch tasks by department for manager's departments
        $stmt = $pdo->prepare("
        SELECT d.name, COUNT(t.task_id) as task_count 
        FROM tasks t
        JOIN users u ON t.user_id = u.id
        JOIN user_departments ud ON u.id = ud.user_id
        JOIN departments d ON ud.department_id = d.id
        WHERE ud.department_id IN (
            SELECT department_id 
            FROM user_departments 
            WHERE user_id = :user_id
        )
        GROUP BY d.name
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $tasksByDepartment = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Generate colors dynamically based on the number of departments
        $departmentColors = generateColors(count($tasksByDepartment));

        // Fetch top performers for manager's departments
        $stmt = $pdo->prepare("
        SELECT 
            u.username, 
            d.name as department, 
            COUNT(t.task_id) as tasks_completed 
        FROM tasks t
        JOIN users u ON t.user_id = u.id
        JOIN user_departments ud ON u.id = ud.user_id
        JOIN departments d ON ud.department_id = d.id
        WHERE t.status = 'Completed on Time' AND ud.department_id IN (
            SELECT department_id 
            FROM user_departments 
            WHERE user_id = :user_id
        )
        GROUP BY u.username, d.name
        ORDER BY tasks_completed DESC
        LIMIT 3
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $topPerformers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch average task duration
        $stmt = $pdo->prepare("
            SELECT AVG(TIMESTAMPDIFF(DAY, expected_start_date, expected_finish_date)) as avg_duration 
            FROM tasks t
            JOIN user_departments ud ON t.user_id = ud.user_id
            WHERE t.status = 'Completed on Time' 
            AND ud.department_id IN (
                SELECT department_id 
                FROM user_departments 
                WHERE user_id = :user_id
            )
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $avgDuration = $stmt->fetch(PDO::FETCH_ASSOC)['avg_duration'];
        $avgDuration = round($avgDuration, 1); // Round to one decimal place

        // Fetch task distribution by status
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'Started' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'Completed on Time' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'Delayed Completion' THEN 1 ELSE 0 END) as 'delayed'
            FROM tasks t
            JOIN user_departments ud ON t.user_id = ud.user_id
            WHERE ud.department_id IN (
                SELECT department_id 
                FROM user_departments 
                WHERE user_id = :user_id
            )
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $taskDistribution = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // For User
    if ($userRole === 'User') {
        // Fetch total tasks for the user
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_tasks FROM tasks WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $totalTasks = $stmt->fetch(PDO::FETCH_ASSOC)['total_tasks'];

        // Fetch tasks in progress for the user
        $stmt = $pdo->prepare("SELECT COUNT(*) as tasks_in_progress FROM tasks WHERE user_id = :user_id AND status = 'Started'");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $tasksInProgress = $stmt->fetch(PDO::FETCH_ASSOC)['tasks_in_progress'];

        // Fetch completed tasks for the user
        $stmt = $pdo->prepare("SELECT COUNT(*) as completed_tasks FROM tasks WHERE user_id = :user_id AND status = 'Completed on Time'");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $completedTasks = $stmt->fetch(PDO::FETCH_ASSOC)['completed_tasks'];

        // Fetch delayed tasks for the user
        $stmt = $pdo->prepare("SELECT COUNT(*) as delayed_tasks FROM tasks WHERE user_id = :user_id AND status = 'Delayed Completion'");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $delayedTasks = $stmt->fetch(PDO::FETCH_ASSOC)['delayed_tasks'];

        // Fetch task distribution by status for the user
        $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'Started' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'Completed on Time' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'Delayed Completion' THEN 1 ELSE 0 END) as 'delayed'
        FROM tasks
        WHERE user_id = :user_id
    ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $taskDistribution = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch task completion over time for the user
        $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(expected_finish_date, '%b') as month,
            COUNT(*) as tasks_completed
        FROM tasks
        WHERE status = 'Completed on Time' AND user_id = :user_id
        GROUP BY DATE_FORMAT(expected_finish_date, '%Y-%m')
        ORDER BY expected_finish_date;
    ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $taskCompletionOverTime = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch average task duration for the user
        $stmt = $pdo->prepare("
        SELECT AVG(TIMESTAMPDIFF(DAY, expected_start_date, expected_finish_date)) as avg_duration 
        FROM tasks 
        WHERE status = 'Completed on Time' AND user_id = :user_id
    ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $avgDuration = $stmt->fetch(PDO::FETCH_ASSOC)['avg_duration'];
        $avgDuration = round($avgDuration, 1); // Round to one decimal place
    }

    // Optional: Session timeout settings
    $timeout_duration = 1200;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        // If the session is expired, destroy it and redirect to login page
        session_unset();
        session_destroy();
        header("Location: portal-login.html");
        exit;
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="icon" type="image/png" sizes="56x56" href="images/logo/logo-2.1.ico" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: #002c5f;
            color: white;
            padding: 20px;
        }

        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .sidebar a:hover {
            background-color: #004080;
        }

        .main-content {
            flex-grow: 1;
            padding: 20px;
            background-color: #ffffff;
        }

        .user-info {
            margin-right: 20px;
            font-size: 14px;
        }

        .logout-btn {
            background-color: #ff4d4d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #ff1a1a;
        }

        .dashboard-content {
            padding: 20px;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #002c5f;
        }

        .card-text {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
        }

        .text-muted {
            font-size: 0.9rem;
            color: #666;
        }

        .list-group-item {
            border: none;
            padding: 10px 15px;
        }

        .list-group-item:hover {
            background-color: #f8f9fa;
        }

        .navbar {
            display: flex;
            align-items: center;
            /* Vertically center all items in the navbar */
            padding: 10px 20px;
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .chart-canvas {
            width: 100% !important;
            height: 300px !important;
        }
    </style>
</head>

<body>
    <?php
    if ($userRole === 'Manager') {
        echo "<pre>";
        print($userId);
        print_r($tasksByDepartment);
        print_r($taskDistribution);
        print_r($taskCompletionOverTime);
        echo "</pre>";
    } elseif ($userRole === 'User') {
        echo "<pre>";
        print($userId);
        print_r($tasksByDepartment);
        print_r($taskDistribution);
        print_r($taskCompletionOverTime);
        echo "</pre>";
    }
    ?>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h3>Menu</h3>
            <a href="tasks.php">Tasks</a>
            <?php if ($userRole === 'Admin' || $userRole === 'Manager'): ?>
                <a href="view-users.php">View Users</a>
            <?php endif; ?>
            <?php if ($userRole === 'Admin'): ?>
                <a href="view-roles-departments.php">View Role or Department</a>
            <?php endif; ?>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Navbar -->
            <div class="navbar">
                <!-- Logo Container -->
                <div class="d-flex align-items-center me-3">
                    <img src="images/logo/logo.webp" alt="Logo" class="logo" style="width: auto; height: 80px;">
                </div>

                <!-- User Info -->
                <div class="user-info me-3 ms-auto"> <!-- Added ms-auto here -->
                    <p class="mb-0">Logged in as: <strong><?= htmlspecialchars($username) ?></strong></p>
                    <p class="mb-0">Departments:
                        <strong><?= !empty($userDepartments) ? htmlspecialchars(implode(', ', $userDepartments)) : 'None' ?></strong>
                    </p>
                </div>

                <!-- Logout Button -->
                <button class="logout-btn" onclick="window.location.href='logout.php'">Log Out</button>
            </div>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Row 1: Key Metrics -->
                <div class="row mb-4">
                    <!-- Open Tickets -->
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Tasks</h5>
                                <p class="card-text display-4"><?= $totalTasks ?></p>
                                <p class="text-muted">Total Tasks</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tasks in Progress -->
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Tasks in Progress</h5>
                                <p class="card-text display-4"><?= $tasksInProgress ?></p>
                                <p class="text-muted">Active</p>
                            </div>
                        </div>
                    </div>

                    <!-- Completed Tasks -->
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Completed Tasks</h5>
                                <p class="card-text display-4"><?= $completedTasks ?></p>
                                <p class="text-muted">In Total</p>
                            </div>
                        </div>
                    </div>

                    <!-- Delayed Tasks -->
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Delayed Tasks</h5>
                                <p class="card-text display-4"><?= $delayedTasks ?></p>
                                <p class="text-muted">Passed Due Date</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Charts and Graphs -->
                <div class="row mb-4">
                    <!-- Task Distribution Chart -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Task Distribution</h5>
                                <div class="text-center">
                                    <canvas id="taskDistributionChart" class="chart-canvas"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Task Completion Over Time -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Task Completion Over Time</h5>
                                <div class="text-center">
                                    <canvas id="taskCompletionChart" class="chart-canvas"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Additional Metrics -->
                <div class="row mb-4">
                    <!-- Average Task Duration -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Average Task Duration</h5>
                                <p class="card-text display-4"><?= $avgDuration ?></p>
                                <p class="text-muted">Days</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tasks by Department (Only for Admin and Manager) -->
                    <?php if ($userRole === 'Admin' || $userRole === 'Manager'): ?>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?= ($userRole === 'Manager') ? 'Tasks in My Departments' : 'Tasks by Department' ?>
                                    </h5>
                                    <div class="text-center">
                                        <canvas id="tasksByDepartmentChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- User Performance (Only for Admin and Manager) -->
                    <?php if ($userRole === 'Admin' || $userRole === 'Manager'): ?>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?= ($userRole === 'Manager') ? 'Top Performers in My Departments' : 'Top Performers' ?>
                                    </h5>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($topPerformers as $performer): ?>
                                            <li class="list-group-item"><?= htmlspecialchars($performer['username']) ?>
                                                (<?= htmlspecialchars($performer['department']) ?>) -
                                                <?= $performer['tasks_completed'] ?> tasks completed
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Bootstrap JS (with Popper.js) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        <!-- Chart.js -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <script>
            // Task Distribution Chart (Pie Chart)
            const taskDistributionChart = new Chart(document.getElementById('taskDistributionChart'), {
                type: 'pie',
                data: {
                    labels: ['Pending', 'In Progress', 'Completed', 'Delayed'],
                    datasets: [{
                        label: 'Task Distribution',
                        data: [
                            <?= $taskDistribution['pending'] ?>,
                            <?= $taskDistribution['in_progress'] ?>,
                            <?= $taskDistribution['completed'] ?>,
                            <?= $taskDistribution['delayed'] ?>
                        ],
                        backgroundColor: [
                            '#FF6384', // Red for Pending
                            '#36A2EB', // Blue for In Progress
                            '#4BC0C0', // Teal for Completed
                            '#FFCE56'  // Yellow for Delayed
                        ],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true, // Make the chart responsive
                    maintainAspectRatio: false, // Allow custom sizing
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        title: {
                            display: true,
                            text: 'Task Distribution by Status'
                        }
                    }
                }
            });

            // Task Completion Over Time (Line Chart)
            const taskCompletionChart = new Chart(document.getElementById('taskCompletionChart'), {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_column($taskCompletionOverTime, 'month')) ?>,
                    datasets: [{
                        label: 'Tasks Completed',
                        data: <?= json_encode(array_column($taskCompletionOverTime, 'tasks_completed')) ?>,
                        fill: false,
                        borderColor: '#36A2EB',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true, // Make the chart responsive
                    maintainAspectRatio: false, // Allow custom sizing
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        title: {
                            display: true,
                            text: 'Task Completion Over Time'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            <?php if ($userRole === 'Admin' || $userRole === 'Manager'): ?>
                // Tasks by Department (Bar Chart)
                const tasksByDepartmentChart = new Chart(document.getElementById('tasksByDepartmentChart'), {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode(array_column($tasksByDepartment, 'name')) ?>,
                        datasets: [{
                            label: 'Tasks by Department',
                            data: <?= json_encode(array_column($tasksByDepartment, 'task_count')) ?>,
                            backgroundColor: <?= json_encode($departmentColors) ?>,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            },
                            title: {
                                display: true,
                                text: 'Tasks by Department'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            <?php endif; ?>
        </script>
</body>

</html>