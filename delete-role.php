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

    $roleId = $_POST['role_id'];

    // Delete the role
    $stmt = $pdo->prepare("DELETE FROM roles WHERE id = :id");
    $stmt->bindParam(':id', $roleId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $success = "Role deleted successfully.";
    } else {
        $error = "Failed to delete role. Please try again.";
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Redirect back to the roles and departments page
header("Location: view-roles-departments.php");
exit;
?>