<?php
// Start output buffering to prevent output before headers
ob_start();

// Start session
session_start();

// Check if the user is not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Redirect to login page if not logged in
    header("Location: portal-login.html");
    exit;
}

// Session timeout (Optional)
$timeout_duration = 600; // 30 minutes in seconds

// Check if 'last_activity' is set and if it has exceeded the timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // If the session is expired, destroy it and redirect to login page
    session_unset();
    session_destroy();
    header("Location: portal-login.html");
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Configuration for database
$dbHost = 'localhost';
$dbUsername = 'euro_admin';
$dbPassword = 'euroglobal123';
$dbName = 'euro_contact_form_db';

// Establish database connection
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: ". $conn->connect_error);
}
?>

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

        .logout-button {
            text-align: right;
            margin-bottom: 20px;
        }

        .logout-button a {
            background-color: #002c5f;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
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
    <!-- Logout Button -->
    <div class="logout-button">
        <a href="logout.php">Log Out</a>
    </div>

    <h2>Data Records</h2>

    <?php
    // Query to fetch data in descending order based on 'id'
    $query = "SELECT * FROM contact_form_submissions ORDER BY id DESC"; // Replace 'your_table_name' with the actual table name
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        echo '<table>';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>First Name</th>';
        echo '<th>Last Name</th>';
        echo '<th>Dial Code</th>';
        echo '<th>Phone</th>';
        echo '<th>Country</th>';
        echo '<th>Email</th>';
        echo '<th>Services</th>';
        echo '<th>Message</th>';
        echo '</tr>';

        // Loop through and display data rows
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['id'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['first_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['last_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['dial_code'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['phone'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['country'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['email'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['services'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['message'] ?? '') . '</td>';
            echo '</tr>';
        }

        echo '</table>';
    } else {
        // Display a message if no data is found
        echo '<p class="no-data">No data found.</p>';
    }

    // Free result set
    $result->free();

    // Close database connection
    $conn->close();
    ?>
</div>

</body>
</html>

<?php
// End output buffering and send output to the browser
ob_end_flush();
?>
