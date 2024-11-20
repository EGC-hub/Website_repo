<?php
    // Configuration
    $dbHost = 'localhost';
    $dbUsername = 'euro_admin';
    $dbPassword = 'euroglobal123';
    $dbName = 'euro_login_system';

    // Establish database connection
    $conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: ". $conn->connect_error);
    }

    // Start session
    session_start();

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Prepare the SQL query
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);

        // Execute the query
        $stmt->execute();
        $result = $stmt->get_result();

        // Fetch the user data
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify the password
            if (password_verify($password, $user['password'])) {
                // Password is correct, start a new session
                session_regenerate_id(true); // Regenerate session ID for security
                $_SESSION['loggedin'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_id'] = $id;

                // Redirect to the data display page
                header("Location: welcome.php");
                exit;
            } else {
                // Password is incorrect
                echo "<script>alert('Incorrect password.'); window.location.href = 'portal-login.html';</script>";
            }
        } else {
            // Username not found
            echo "<script>alert('Username not found.'); window.location.href = 'portal-login.html';</script>";
        }

        // Close the statement and connection
        $stmt->close();
        $conn->close();
    }

    // Display error if any
    if (isset($error)) {
        echo '<script>alert("'. $error. '");</script>';
    }
    ?>
