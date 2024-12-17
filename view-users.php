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
$user_department = $_SESSION['department']; // Assume this is set during login

// Fetch users based on role
try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($user_role === 'admin') {
        // Admin: View all users except admins
        $stmt = $pdo->prepare("SELECT id, username, email, role, department FROM users WHERE role != 'admin' ORDER BY department, username");
    } elseif ($user_role === 'manager') {
        // Manager: View only users in the same department, excluding admins and their own account
        $stmt = $pdo->prepare("SELECT id, username, email, role, department FROM users WHERE department = :department AND role NOT IN ('admin', 'manager') AND id != :user_id ORDER BY username");
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
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f4f9;
            color: #333;
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

        table th, table td {
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
            background-color: #1d3557;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .back-button:hover {
            background-color: #457b9d;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 1.8rem;
            }

            table th, table td {
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
    <div class="container">
        <h1>Users</h1>

        <?php if ($user_role === 'admin'): ?>
            <p>Viewing all users grouped by department:</p>
        <?php elseif ($user_role === 'manager'): ?>
            <p>Viewing users in your department: <strong><?= htmlspecialchars($user_department) ?></strong></p>
        <?php endif; ?>

        <?php if (!empty($users)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Department</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['role']) ?></td>
                            <td><?= htmlspecialchars($user['department']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No users found.</p>
        <?php endif; ?>

        <a href="dashboard.php" class="back-button">Back</a>
    </div>
</body>
</html>
