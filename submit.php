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
$email = $_POST['email'];
$services = isset($_POST['services']) ? (is_array($_POST['services']) ? implode(", ", $_POST['services']) : $_POST['services']) : '';
$message = $_POST['message'];

// Insert data into database
$sql = "INSERT INTO contact_form_submissions (first_name, last_name, phone, email, services, message) 
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $first_name, $last_name, $phone, $email, $services, $message);

if ($stmt->execute()) {
    echo "Form submitted successfully!";
} else {
    echo "Error: " . $stmt->error;
}

// Close connections
$stmt->close();
$conn->close();
?>
