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

// Get form data
$first_name = $_POST['first-name'];
$last_name = $_POST['last-name'];
$phone = $_POST['phone'];
$country = $_POST['country']; 
$dial_code = $_POST['dialCode']; 
$email = $_POST['email'];
$services = isset($_POST['services']) ? implode(", ", $_POST['services']) : '';
$message = $_POST['message'];

// Prepare and bind SQL statement
$sql = "INSERT INTO contact_form_submissions (first_name, last_name, phone, country, dial_code, email, services, message) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

// Check if preparation is successful
if (!$stmt) {
    die("Preparation failed: " . $conn->error);
}

// Bind parameters
$stmt->bind_param("ssssssss", $first_name, $last_name, $phone, $country, $dial_code, $email, $services, $message);

// Execute the statement and check if it is successful
if ($stmt->execute()) {
    // Redirect to thank you page
    header("Location: thankyou.html");
    exit();
} else {
    echo "Error: " . $stmt->error;
}

// Close connections
$stmt->close();
$conn->close();
?>