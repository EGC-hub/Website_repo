<?php
// Configuration
$servername = "localhost";
$username = "euro_admin";
$password = "euroglobal123";
$dbname = "euro_login_system";

// Establish database connection
$conn = mysqli_connect($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if (!$conn) {
    die("Connection failed: ". mysqli_connect_error());
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Query to fetch user data
    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        // Assuming passwords are hashed using password_hash() during registration
        if (password_verify($password, $row['password'])) {
            // Login successful, start session and redirect
            session_start();
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $row['role'];
            header("location: thankyou.html"); // Redirect to dashboard or relevant page
            exit;
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "Username not found.";
    }
    mysqli_close($conn);
}

// Display error if any
if (isset($error)) {
    echo '<script>alert("'. $error. '");</script>';
}
?>
<!-- For testing purposes, you can temporarily include the login form here or keep it in login.html and adjust the action attribute accordingly -->