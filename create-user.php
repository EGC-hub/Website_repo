<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Start session
session_start();

// Check if the user is logged in and has admin role
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    // Redirect to login page if not logged in or not an admin
    header("Location: portal-login.html");
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;

$config = include '../config.php';

// Database connection
$dbHost = 'localhost';
$dbUsername = $config['dbUsername'];
$dbPassword = $config['dbPassword'];
$dbName = 'euro_login_system';

// Create a connection
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch logged-in user's details
$userQuery = $conn->prepare("SELECT username, department FROM users WHERE id = ?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$userResult = $userQuery->get_result();

if ($userResult->num_rows > 0) {
    $userDetails = $userResult->fetch_assoc();
    $loggedInUsername = $userDetails['username'];
    $loggedInDepartment = $userDetails['department'];
} else {
    $loggedInUsername = "Unknown";
    $loggedInDepartment = "Unknown";
}

// Initialize error and success messages
$errorMsg = "";
$successMsg = "";

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];
    $department = $_POST['department'];

    // Validate the inputs
    if (empty($username) || empty($password) || empty($role) || empty($department)) {
        $errorMsg = "Please fill in all fields.";
    } elseif (!in_array($role, ['manager', 'user'])) {
        $errorMsg = "Invalid role selected.";
    } else {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Check if the username already exists
        $checkQuery = "SELECT id FROM users WHERE username = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errorMsg = "Username already taken.";
        } else {
            // Insert the new user into the database
            $insertQuery = "INSERT INTO users (username, email, password, role, department) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("sssss", $username, $email, $hashedPassword, $role, $department);

            if ($stmt->execute()) {
                $successMsg = "User created successfully.";
            } else {
                $errorMsg = "Failed to create user. Please try again.";
            }
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User</title>
    <link rel="icon" type="image/png" sizes="56x56" href="images/logo/logo-2.1.ico" />
    <style>
        /* General Box Sizing */
        * {
            box-sizing: border-box;
            /* Include padding and border in width and height */
        }

        /* Style for the form */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }

        .main-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin: 25px;
        }

        .form-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        input[type="text"],
        input[type="password"],
        input[type="email"],
        select {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #002c5f;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #004080;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 20px;
        }

        .success {
            color: green;
            text-align: center;
            margin-bottom: 20px;
        }

        /* Back button style */
        .back-btn {
            display: block;
            /* Make it a block-level element */
            width: 100%;
            margin-top: 20px;
            padding: 10px;
            background-color: #002c5f;
            color: white;
            text-align: center;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
        }

        .back-btn:hover {
            background-color: #004080;
        }

        .user-info {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
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
    </style>
</head>

<body>
    <div class="main-container">
        <div class="user-info">
            <p>Logged in as: <strong><?= htmlspecialchars($loggedInUsername) ?></strong> | Department:
                <strong><?= htmlspecialchars($loggedInDepartment) ?></strong>
            </p>
            <p class="session-warning">Warning: Your session will timeout after 10 minutes of inactivity.</p>
        </div>

        <div class="form-container">
            <h1>Create User</h1>

            <!-- Display error or success messages -->
            <?php if (!empty($errorMsg)): ?>
                <div class="error"><?php echo $errorMsg; ?></div>
            <?php elseif (!empty($successMsg)): ?>
                <div class="success"><?php echo $successMsg; ?></div>
            <?php endif; ?>

            <!-- Create User Form -->
            <form method="POST" action="create-user.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="user">User</option>
                        <option value="manager">Manager</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="department">Department</label>
                    <select id="department" name="department" required>
                        <option value="HR">HR</option>
                        <option value="IT">IT</option>
                    </select>
                </div>
                <button type="submit">Create User</button>
            </form>

            <!-- Back Button -->
            <a href="welcome.php" class="back-btn">Back to Dashboard</a>
        </div>
    </div>
</body>

</html>