<?php
// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Redirect to login page if not logged in
    header("Location: portal-login.html");
    exit;
}

// Retrieve the username, role, and department from the session
$username = $_SESSION['username'] ?? 'Unknown'; // Fallback to 'Unknown' if not set
$userRole = $_SESSION['role'] ?? 'Unknown'; // Fallback to 'Unknown' if not set
$department = $_SESSION['department'] ?? 'Unknown'; // Fallback to 'Unknown' if not set

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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Page</title>
    <link rel="icon" type="image/png" sizes="56x56" href="images/logo/logo-2.1.ico" />
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
            /* Add padding to the body */
        }

        /* Apply box-sizing to all elements */
        * {
            box-sizing: border-box;
        }

        .main-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            width: 100%;
            max-width: 1200px;
            /* Match the width of the welcome-container */
        }

        .user-info {
            text-align: center;
            width: 90%;
            /* Match the width of the welcome-container */
            max-width: 700px;
            /* Match the width of the welcome-container */
            margin-top: 20px;
            margin-bottom: 20px;
            padding: 20px;
            /* Increase padding for better spacing */
            background-color: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .user-info p {
            margin: 5px 0;
            font-size: 16px;
            color: #333;
        }

        .user-info .session-warning {
            color: #dc3545;
            /* Red color for warning */
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
        }

        .welcome-container {
            text-align: center;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 90%;
            /* Match the width of the user-info */
            max-width: 700px;
            /* Match the width of the user-info */
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
        }

        .button-container {
            margin-top: 20px;
        }

        .btn {
            display: inline-block;
            margin: 10px;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            color: white;
            background-color: #002c5f;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #004080;
        }

        .logout-btn {
            display: inline-block;
            margin: 20px auto;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            color: white;
            background-color: #ff4d4d;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }

        .logout-btn:hover {
            background-color: #ff1a1a;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <div class="user-info">
            <p>Logged in as: <strong><?= htmlspecialchars($username) ?></strong> | Department:
                <strong><?= htmlspecialchars($department) ?></strong>
            </p>
            <p class="session-warning">Warning: Your session will timeout after 10 minutes of inactivity.</p>
        </div>

        <div class="welcome-container">
            <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
            <div class="button-container">
                <!-- Buttons for navigation -->
                <?php if ($userRole === 'admin'): ?>
                    <a href="data-display.php" class="btn">Data Display</a>
                <?php endif; ?>

                <a href="tasks.php" class="btn">Tasks</a>

                <!-- Display 'Create User' button only if user has 'admin' role -->
                <?php if ($userRole === 'admin'): ?>
                    <a href="create-user.php" class="btn">Create User</a>
                <?php endif; ?>

                <!-- Display 'View Users' if user has 'admin' or 'manager' role -->
                <?php if ($userRole === 'admin' || $userRole === 'manager'): ?>
                    <a href="view-users.php" class="btn">View Users</a>
                <?php endif; ?>
            </div>
            <!-- Logout Button -->
            <a href="logout.php" class="logout-btn">Log Out</a>
        </div>
    </div>

</body>

</html>