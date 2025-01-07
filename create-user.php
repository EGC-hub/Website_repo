<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Start session
session_start();

// Check if the user is logged in and has admin role
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'Admin') {
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
$userQuery = $conn->prepare("
    SELECT u.username, d.name AS department_name 
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE u.id = ?
");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$userResult = $userQuery->get_result();

if ($userResult->num_rows > 0) {
    $userDetails = $userResult->fetch_assoc();
    $loggedInUsername = $userDetails['username'];
    $loggedInDepartmentName = $userDetails['department_name']; // Fetch department name
} else {
    $loggedInUsername = "Unknown";
    $loggedInDepartmentName = "Unknown"; // Fallback if no department is found
}

// Fetch all roles except 'admin' from the database
$roles = [];
$roleQuery = $conn->query("SELECT id, name FROM roles WHERE name != 'Admin'");
if ($roleQuery) {
    while ($row = $roleQuery->fetch_assoc()) {
        $roles[] = $row;
    }
}

// Fetch all departments from the database
$departments = [];
$departmentQuery = $conn->query("SELECT id, name FROM departments");
if ($departmentQuery) {
    while ($row = $departmentQuery->fetch_assoc()) {
        $departments[] = $row;
    }
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
    $role_id = intval($_POST['role']); // Ensure role_id is an integer
    $department_id = intval($_POST['department']); // Ensure department_id is an integer

    // Validate the inputs
    if (empty($username) || empty($password) || empty($role_id) || empty($department_id)) {
        $errorMsg = "Please fill in all fields.";
    } else {
        // Validate role_id and department_id
        $validRoleIds = array_column($roles, 'id'); // Get all valid role IDs
        $validDepartmentIds = array_column($departments, 'id'); // Get all valid department IDs

        if (!in_array($role_id, $validRoleIds)) {
            $errorMsg = "Invalid role selected.";
        } elseif (!in_array($department_id, $validDepartmentIds)) {
            $errorMsg = "Invalid department selected.";
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
                $insertQuery = "INSERT INTO users (username, email, password, role_id, department_id) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param("sssii", $username, $email, $hashedPassword, $role_id, $department_id);

                if ($stmt->execute()) {
                    $successMsg = "User created successfully.";
                } else {
                    $errorMsg = "Failed to create user. Please try again.";
                }
            }
            $stmt->close();
        }
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
            margin: 25px;
        }

        .form-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
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
            max-width: 500px;
            text-align: center;
            margin-bottom: 20px;
            padding: 10px 40px;
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
                <strong><?= htmlspecialchars($loggedInDepartmentName) ?></strong>
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
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="department">Department</label>
                    <select id="department" name="department" required>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?= $department['id'] ?>"><?= htmlspecialchars($department['name']) ?></option>
                        <?php endforeach; ?>
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