<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Check if the user is logged in and has admin role
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'Admin') {
    header("Location: portal-login.html");
    exit;
}

$config = include '../config.php';
$dsn = "mysql:host=localhost;dbname=euro_login_system;charset=utf8mb4";
$username = $config['dbUsername'];
$password = $config['dbPassword'];

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Initialize error and success messages
    $errorMsg = "";
    $successMsg = "";

    // Handle form submission for creating a new role
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_role'])) {
        $roleName = trim($_POST['role_name']);

        if (empty($roleName)) {
            $errorMsg = "Role name is required.";
        } else {
            // Check if the role already exists
            $checkStmt = $pdo->prepare("SELECT id FROM roles WHERE name = :name");
            $checkStmt->bindParam(':name', $roleName);
            $checkStmt->execute();

            if ($checkStmt->rowCount() > 0) {
                $errorMsg = "Role already exists.";
            } else {
                // Insert the new role into the database
                $insertStmt = $pdo->prepare("INSERT INTO roles (name) VALUES (:name)");
                $insertStmt->bindParam(':name', $roleName);

                if ($insertStmt->execute()) {
                    $successMsg = "Role created successfully.";
                } else {
                    $errorMsg = "Failed to create role. Please try again.";
                }
            }
        }
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

    // Handle form submission for creating a new department
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_department'])) {
        $departmentName = trim($_POST['department_name']);

        if (empty($departmentName)) {
            $errorMsg = "Department name is required.";
        } else {
            // Check if the department already exists
            $checkStmt = $pdo->prepare("SELECT id FROM departments WHERE name = :name");
            $checkStmt->bindParam(':name', $departmentName);
            $checkStmt->execute();

            if ($checkStmt->rowCount() > 0) {
                $errorMsg = "Department already exists.";
            } else {
                // Insert the new department into the database
                $insertStmt = $pdo->prepare("INSERT INTO departments (name) VALUES (:name)");
                $insertStmt->bindParam(':name', $departmentName);

                if ($insertStmt->execute()) {
                    $successMsg = "Department created successfully.";
                } else {
                    $errorMsg = "Failed to create department. Please try again.";
                }
            }
        }
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Roles & Departments</title>
    <link rel="icon" type="image/png" sizes="56x56" href="images/logo/logo-2.1.ico" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        /* Apply box-sizing to all elements */
        * {
            box-sizing: border-box;
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

        input[type="text"] {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        button {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
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

        .back-btn {
            display: block;
            margin-top: 20px;
            text-align: center;
            font-size: 16px;
            text-decoration: none;
            color: #004080;
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
        .main-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 25px;
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
            <h1>Create Roles & Departments</h1>

            <!-- Display error or success messages -->
            <?php if (!empty($errorMsg)): ?>
                <div class="error"><?= htmlspecialchars($errorMsg) ?></div>
            <?php elseif (!empty($successMsg)): ?>
                <div class="success"><?= htmlspecialchars($successMsg) ?></div>
            <?php endif; ?>

            <!-- Form for creating a new role -->
            <form method="POST" action="create-roles-departments.php">
                <div class="form-group">
                    <label for="role_name">Role Name</label>
                    <input type="text" id="role_name" name="role_name" required>
                </div>
                <button type="submit" name="create_role">Create Role</button>
            </form>

            <!-- Form for creating a new department -->
            <form method="POST" action="create-roles-departments.php">
                <div class="form-group">
                    <label for="department_name">Department Name</label>
                    <input type="text" id="department_name" name="department_name" required>
                </div>
                <button type="submit" name="create_department">Create Department</button>
            </form>

            <!-- Back Button -->
            <a href="welcome.php" class="back-btn">Back to Dashboard</a>
        </div>
    </div>
</body>

</html>