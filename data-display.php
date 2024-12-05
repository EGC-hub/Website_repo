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
$timeout_duration = 600; 

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

// Fetch distinct services and countries
$queryServices = "SELECT DISTINCT services FROM contact_form_submissions";
$queryCountries = "SELECT DISTINCT country FROM contact_form_submissions";

$resultServices = $conn->query($queryServices);
$resultCountries = $conn->query($queryCountries);

$services = [];
$countries = [];

if ($resultServices && $resultServices->num_rows > 0) {
    while ($row = $resultServices->fetch_assoc()) {
        $services[] = $row['services'];
    }
}

if ($resultCountries && $resultCountries->num_rows > 0) {
    while ($row = $resultCountries->fetch_assoc()) {
        $countries[] = $row['country'];
    }
}

// Get filter values from GET request
$selectedService = isset($_GET['service']) ? $_GET['service'] : '';
$selectedCountry = isset($_GET['country']) ? $_GET['country'] : '';

// Build SQL query based on filters
$query = "SELECT * FROM contact_form_submissions";
$firstCondition = true;

if (!empty($selectedService)) {
    if ($firstCondition) {
        $query .= " WHERE";
        $firstCondition = false;
    } else {
        $query .= " AND";
    }
    $query .= " services LIKE ?";
}

if (!empty($selectedCountry)) {
    if ($firstCondition) {
        $query .= " WHERE";
    } else {
        $query .= " AND";
    }
    $query .= " country = ?";
}

$query .= " ORDER BY id DESC";

// Prepare and bind
$stmt = $conn->prepare($query);
$params = [];
$types = '';

if (!empty($selectedService)) {
    $selectedService = '%' . $selectedService . '%'; // Use LIKE for partial matching
    $types .= 's';
    $params[] = &$selectedService;
}

if (!empty($selectedCountry)) {
    $types .= 's';
    $params[] = &$selectedCountry;
}

if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Display</title>
    <link rel="icon" type="image/png" sizes="56x56" href="images/logo/logo-2.1.ico" />
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

        .filter-form {
            margin-bottom: 20px;
        }

        .filter-form select {
            padding: 8px;
            margin-right: 10px;
        }

        .filter-form button {
            padding: 8px 16px;
            background-color: #002c5f;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .filter-form button:hover {
            background-color: #001a3d;
        }
    </style>
</head>
<body>

<div class="table-container">
    <!-- Logout Button -->
    <div class="logout-button">
        <a href="welcome.php">Back</a>
    </div>

    <!-- Filter Form -->
    <div class="filter-form">
        <form method="GET" action="">
            <label for="service">Service:</label>
            <select id="service" name="service">
                <option value="">All Services</option>
                <?php foreach ($services as $service): ?>
                    <option value="<?php echo htmlspecialchars($service); ?>"<?php if ($selectedService === $service) echo ' selected'; ?>>
                        <?php echo htmlspecialchars($service); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="country">Country:</label>
            <select id="country" name="country">
                <option value="">All Countries</option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?php echo htmlspecialchars($country); ?>"<?php if ($selectedCountry === $country) echo ' selected'; ?>>
                        <?php echo htmlspecialchars($country); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Filter</button>
        </form>
    </div>

    <h2>Data Records</h2>

    <?php
    if ($result->num_rows > 0) {
        echo '<table>';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>First Name</th>';
        echo '<th>Last Name</th>';
        echo '<th>Dial Code</th>';
        echo '<th>Phone</th>';
        echo '<th>Country</th>';
        echo '<th>Email</th>';
        echo '<th>Submitted At</th>';
        echo '<th>Services</th>';
        echo '<th>Message</th>';
        echo '</tr>';

        $counter = 1;

        // Loop through and display data rows
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>'. htmlspecialchars($counter) .'</td>';
            echo '<td>' . htmlspecialchars($row['first_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['last_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['dial_code'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['phone'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['country'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['email'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['submitted_at'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['services'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['message'] ?? '') . '</td>';
            echo '</tr>';

            $counter++;
        }

        echo '</table>';
    } else {
        // Display a message if no data is found
        echo '<p class="no-data">No data found.</p>';
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
    ?>
</div>

</body>
</html>

<?php
// End output buffering and send output to the browser
ob_end_flush();
?>