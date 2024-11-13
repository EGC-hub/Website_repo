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
            max-width: 1000px;
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
            padding: 10px;
            text-align: center;
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
    </style>
</head>
<body>

<div class="table-container">
    <h2>Data Records</h2>

    <?php
    // Database configuration
    $servername = "localhost";
    $username = "euro_admin";
    $password = "euroglobal123";
    $dbname = "euro_contact_form_db";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // SQL query to fetch data
    $sql = "SELECT id, first_name, last_name, phone, country, dial_code, email, services, message FROM contact_form_submissions";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>Id</th><th>First Name</th><th>Last Name</th><th>Phone</th><th>Country</th><th>Dial Code</th><th>Email</th><th>Services</th><th>Message</th></tr>";

        // Fetch and display each row
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row["id"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["first_name"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["last_name"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["phone"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["country"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["dial_code"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["email"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["services"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["message"]) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='no-data'>No records found.</p>";
    }

    // Close connection
    $conn->close();
    ?>
</div>

</body>
</html>
