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

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Retrieve the username, role, and user ID from the session
    $username = $_SESSION['username'] ?? 'Unknown'; // Fallback to 'Unknown' if not set
    $userRole = $_SESSION['role'] ?? 'Unknown'; // Fallback to 'Unknown' if not set
    $userId = $_SESSION['user_id'] ?? null; // User ID from session

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

        .dashboard-placeholder {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            text-align: center;
            color: #333;
        }

        .logo {
            width: 40px;
            /* Adjust the size of the logo */
            height: 40px;
            /* Adjust the size of the logo */
        }

        .navbar {
            display: flex;
            align-items: center;
            /* Vertically center all items in the navbar */
            padding: 10px 20px;
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h3>Menu</h3>
            <?php if ($userRole === 'Admin'): ?>
                <a href="data-display.php">Data Display</a>
            <?php endif; ?>
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
                    <img src="images/logo/logo.webp" alt="Logo" class="logo" style="width: auto; height: 40px;">
                </div>

                <!-- User Info -->
                <div class="user-info me-3">
                    <p class="mb-0">Logged in as: <strong><?= htmlspecialchars($username) ?></strong></p>
                    <p class="mb-0">Departments:
                        <strong><?= !empty($userDepartments) ? htmlspecialchars(implode(', ', $userDepartments)) : 'None' ?></strong>
                    </p>
                </div>

                <!-- Logout Button -->
                <button class="logout-btn" onclick="window.location.href='logout.php'">Log Out</button>
            </div>

            <!-- Dashboard Placeholder -->
            <div class="dashboard-placeholder">
                <h2>Welcome to the Dashboard</h2>
                <p>This is a placeholder for your dashboard content.</p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (with Popper.js) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>