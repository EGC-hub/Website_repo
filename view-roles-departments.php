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

// Session timeout for 20 mins
$timeout_duration = 1200;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: portal-login.html");
    exit;
}

$_SESSION['last_activity'] = time();

$config = include '../config.php';
$dsn = "mysql:host=localhost;dbname=euro_login_system;charset=utf8mb4";
$username = $config['dbUsername'];
$password = $config['dbPassword'];

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch all roles
    $rolesQuery = $pdo->query("SELECT id, name FROM roles");
    $roles = $rolesQuery->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all departments
    $departmentsQuery = $pdo->query("SELECT id, name FROM departments");
    $departments = $departmentsQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Roles & Departments</title>
    <link rel="icon" type="image/png" sizes="56x56" href="images/logo/logo-2.1.ico" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 20px;
        }

        .main-container {
            width: 90%;
            max-width: 1200px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .user-info {
            text-align: center;
            width: 90%;
            max-width: 1200px;
            margin-top: 20px;
            margin-bottom: 20px;
            padding: 20px;
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
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 2.2rem;
            text-align: center;
            color: #1d3557;
            margin-bottom: 20px;
        }

        h2 {
            font-size: 1.5rem;
            color: #457b9d;
            margin-top: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th,
        table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        table th {
            background-color: #1d3557;
            color: #fff;
            font-weight: bold;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #002c5f;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .back-button:hover {
            background-color: #004080;
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

        .delete-button {
            display: inline-block;
            padding: 5px 10px;
            background-color: #e63946;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .delete-button:hover {
            background-color: #d62828;
        }

        button.delete-button {
            font-family: 'Poppins', sans-serif;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 1.8rem;
            }

            table th,
            table td {
                font-size: 0.9rem;
                padding: 8px;
            }

            .back-button {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="user-info">
            <p>Logged in as: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> | Department:
                <strong><?= htmlspecialchars($_SESSION['department']) ?></strong>
            </p>
            <p class="session-warning">Warning: Your session will timeout after 20 minutes of inactivity.</p>
        </div>
        <div class="container">
            <h1>Roles & Departments</h1>

            <!-- Roles Table -->
            <h2>Roles</h2>
            <?php if (!empty($roles)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Role Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $role): ?>
                            <tr>
                                <td><?= htmlspecialchars($role['id']) ?></td>
                                <td><?= htmlspecialchars($role['name']) ?></td>
                                <td>
                                    <a href="edit-role.php?id=<?= urlencode($role['id']) ?>" class="edit-button">Edit</a>
                                    <form action="delete-role.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="role_id" value="<?= htmlspecialchars($role['id']) ?>">
                                        <button type="submit" class="delete-button" onclick="return confirm('Are you sure you want to delete this role?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No roles found.</p>
            <?php endif; ?>

            <!-- Departments Table -->
            <h2>Departments</h2>
            <?php if (!empty($departments)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Department Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $department): ?>
                            <tr>
                                <td><?= htmlspecialchars($department['id']) ?></td>
                                <td><?= htmlspecialchars($department['name']) ?></td>
                                <td>
                                    <a href="edit-department.php?id=<?= urlencode($department['id']) ?>" class="edit-button">Edit</a>
                                    <form action="delete-department.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="department_id" value="<?= htmlspecialchars($department['id']) ?>">
                                        <button type="submit" class="delete-button" onclick="return confirm('Are you sure you want to delete this department?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No departments found.</p>
            <?php endif; ?>

            <!-- Back Button -->
            <a href="welcome.php" class="back-button">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>