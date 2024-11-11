$newPassword = 'ysecretpassword';
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

$query = "INSERT INTO users (username, password, role) VALUES ('admin', '$hashedPassword', 'admin')";
mysqli_query($conn, $query);