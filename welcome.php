<?php
// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Redirect to login page if not logged in
    header("Location: portal-login.html");
    exit;
}

// Retrieve the username from the session
$username = $_SESSION['username'];

// Optional: Session timeout settings
$timeout_duration = 1800; // 30 minutes in seconds
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
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        .welcome-container {
            text-align: center;
            background-color: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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
            display: block;
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

    <div class="welcome-container">
        <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
        <div class="button-container">
            <!-- Buttons for navigation -->
            <a href="data-display.php" class="btn">Data Display</a>
            <a href="tasks.php" class="btn">Tasks</a>
        </div>
        <!-- Logout Button -->
        <a href="logout.php" class="logout-btn">Log Out</a>
    </div>

</body>
</html>
