<?php
// Database configuration
$servername = "localhost"; 
$username = "euro_admin";
$password = "TsH.X$T@R(HT";
$dbname = "euro_form_submissions_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Database connection successful!";
}

$conn->close();
?>
