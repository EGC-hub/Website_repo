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
        $stmt = $pdo->prepare("
            SELECT id, username, email, role, department 
            FROM users 
            WHERE role != 'admin'
            ORDER BY department, name
        ");
    } elseif ($user_role === 'manager') {
        // Manager: View only users in the same department, excluding admins and their own account
        $stmt = $pdo->prepare("
            SELECT id, name, email, role, department 
            FROM users 
            WHERE department = :department 
            AND role NOT IN ('admin', 'manager') 
            AND id != :user_id
            ORDER BY name
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
    <title>View Users</title>
    <link rel="stylesheet" href="styles.css"> <!-- Optional CSS -->
</head>
<body>
    <h1>Users</h1>

    <?php if ($user_role === 'admin'): ?>
        <p>Viewing all users grouped by department:</p>
    <?php elseif ($user_role === 'manager'): ?>
        <p>Viewing users in your department: <strong><?= htmlspecialchars($user_department) ?></strong></p>
    <?php endif; ?>

    <?php if (!empty($users)): ?>
        <?php if ($user_role === 'admin'): ?>
            <?php
            $current_department = '';
            foreach ($users as $user):
                if ($current_department !== $user['department']) {
                    if ($current_department !== '') {
                        echo "</ul>";
                    }
                    $current_department = $user['department'];
                    echo "<h2>" . htmlspecialchars($current_department) . "</h2><ul>";
                }
                echo "<li>" . htmlspecialchars($user['name']) . " (" . htmlspecialchars($user['email']) . ") - " . htmlspecialchars($user['role']) . "</li>";
            endforeach;
            if ($current_department !== '') {
                echo "</ul>";
            }
            ?>
        <?php else: ?>
            <ul>
                <?php foreach ($users as $user): ?>
                    <li><?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>) - <?= htmlspecialchars($user['role']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php else: ?>
        <p>No users found.</p>
    <?php endif; ?>
</body>
</html>
