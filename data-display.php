<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Display</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        
        .table-container {
            width: 100%;
            max-width: 1250px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid #ccc;
        }

        th, td {
            width: auto;
            padding: 10px;
            border-bottom: 1px solid #ccc;
            text-align: left;
            background-color: #ffffff; /* Ensure each cell has a white background */
        }


        th {
            background-color: #002c5f;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .no-data {
            text-align: center;
            color: #888;
            padding: 20px;
        }

        .container {
            width: 100%;
        }

    </style>
</head>
<body>

<div class="table-container">
    <h2>Data Records</h2>

    <?php
    // Start session
    session_start();

    // Check if the user is not logged in
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        // Redirect to login page if not logged in
        header("Location: login.php");
        exit;
    }

    // Session timeout (Optional)
    $timeout_duration = 1800; // 30 minutes in seconds

    // Check if 'last_activity' is set and if it has exceeded the timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        // If the session is expired, destroy it and redirect to login page
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit;
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();

    // Configuration for database
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

    // Your existing data-display code should go here
    ?>

</div>

</body>
</html>
