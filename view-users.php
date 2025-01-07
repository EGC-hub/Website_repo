<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$config = include '../config.php';
$dsn = "mysql:host=localhost;dbname=euro_login_system;charset=utf8mb4";
$username = $config['dbUsername'];
$password = $config['dbPassword'];

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_department = $_SESSION['department'];
$user_username = $_SESSION['username'];

// Fetch users based on role
try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($user_role === 'Admin') {
        // Admin: View all users except Admins
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.email, r.name AS role_name, d.name AS department_name 
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE r.name != 'Admin'
            ORDER BY d.name, u.username
        ");
    } elseif ($user_role === 'Manager') {
        // Manager: View only users in the same department, excluding Admins and their own account
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.email, r.name AS role_name, d.name AS department_name 
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE d.name = :department AND r.name NOT IN ('Admin', 'Manager') AND u.id != :user_id
            ORDER BY u.username
        ");
        $stmt->bindParam(':department', $user_department);
        $stmt->bindParam(':user_id', $user_id);
    } else {
        // Unauthorized access
        echo "You do not have the required permissions to view this page.";
        exit;
    }

    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="56x56" href="images/logo/logo-2.1.ico" />
    <title>View Users</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
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

        p {
            text-align: center;
            font-size: 1rem;
            color: #457b9d;
            margin-bottom: 30px;
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
            <p>Logged in as: <strong><?= htmlspecialchars($user_username) ?></strong> | Department:
                <strong><?= htmlspecialchars($user_department) ?></strong>
            </p>
            <p class="session-warning">Warning: Your session will timeout after 10 minutes of inactivity.</p>
        </div>
        <div class="container">
            <h1>Users</h1>

            <?php if ($user_role === 'Admin'): ?>
                <p>Viewing all users grouped by department</p>
                <?php
                $current_department = '';
                foreach ($users as $user):
                    if ($current_department !== $user['department_name']) {
                        if ($current_department !== '') {
                            echo "</tbody></table>";
                        }
                        $current_department = $user['department_name'];
                        echo "<h2>Department: " . htmlspecialchars($current_department) . "</h2>";
                        echo "<table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>";
                    }
                    echo "<tr>
                        <td>" . htmlspecialchars($user['username']) . "</td>
                        <td>" . htmlspecialchars($user['email']) . "</td>
                        <td>" . htmlspecialchars($user['role_name']) . "</td>
                        <td>
                        <a href='edit-user.php?id=" . urlencode($user['id']) . "' class='edit-button'>Edit</a>
                        <form action='delete-user.php' method='POST' style='display:inline;'>
                        <input type='hidden' name='user_id' value='" . htmlspecialchars($user['id']) . "'>
                        <button type='submit' class='delete-button' onclick='return confirm(\"Are you sure you want to delete this user?\")'>Delete</button>
                        </form>
                        </td>
                    </tr>";
                endforeach;
                if ($current_department !== '') {
                    echo "</tbody></table>";
                }
                ?>
            <?php elseif ($user_role === 'Manager'): ?>
                <p>Viewing users in your department: <strong><?= htmlspecialchars($user_department) ?></strong></p>
                <?php if (!empty($users)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['role_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No users found.</p>
                <?php endif; ?>
            <?php endif; ?>

            <a href="welcome.php" class="back-button">Back</a>
        </div>
    </div>
</body>

</html>