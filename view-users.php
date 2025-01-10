<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$config = include '../config.php';
$dsn = "mysql:host=localhost;dbname=euro_login_system;charset=utf8mb4";
$username = $config['dbUsername'];
$password = $config['dbPassword'];

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

// Session timeout for 20 mins
$timeout_duration = 1200;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: portal-login.html");
    exit;
}

$_SESSION['last_activity'] = time();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_departments = $_SESSION['departments'] ?? []; // Fetch departments from session
$user_username = $_SESSION['username'];

// Fetch users based on role
try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($user_role === 'Admin') {
        // Admin: View all users except Admins
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.email, r.name AS role_name, GROUP_CONCAT(d.name SEPARATOR ', ') AS departments
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN user_departments ud ON u.id = ud.user_id
            LEFT JOIN departments d ON ud.department_id = d.id
            WHERE r.name != 'Admin'
            GROUP BY u.id
            ORDER BY u.username
        ");
        $stmt->execute();
    } elseif ($user_role === 'Manager') {
        // Manager: View only users in the same department(s), excluding Admins and their own account
        $departmentPlaceholders = implode(',', array_fill(0, count($user_departments), '?'));
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.email, r.name AS role_name, GROUP_CONCAT(d.name SEPARATOR ', ') AS departments
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN user_departments ud ON u.id = ud.user_id
            LEFT JOIN departments d ON ud.department_id = d.id
            WHERE d.name IN ($departmentPlaceholders) 
              AND r.name NOT IN ('Admin', 'Manager') 
              AND u.id != ?
            GROUP BY u.id
            ORDER BY u.username
        ");
        // Bind department names and user ID to the query
        $params = array_merge($user_departments, [$user_id]);
        $stmt->execute($params);
    } else {
        // Unauthorized access
        echo "You do not have the required permissions to view this page.";
        exit;
    }

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="56x56" href="images/logo/logo-2.1.ico" />
    <title>View Users</title>
    <style>
        /* Your existing CSS styles remain unchanged */
    </style>
</head>

<body>
    <div class="main-container">
        <div class="user-info">
            <p>Logged in as: <strong><?= htmlspecialchars($user_username) ?></strong></p>
            <p>Departments:
                <strong>
                    <?= !empty($user_departments) ? htmlspecialchars(implode(', ', $user_departments)) : 'None' ?>
                </strong>
            </p>
            <p class="session-warning">Information: Your session will timeout after 20 minutes of inactivity.</p>
        </div>
        <div class="container">
            <h1>Users</h1>

            <!-- Add a "Create User" button -->
            <button onclick="openModal()" style="margin-bottom: 20px;">Create User</button>

            <?php if ($user_role === 'Admin'): ?>
                <p>Viewing all users</p>
                <?php if (!empty($users)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Departments</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['role_name']) ?></td>
                                    <td><?= !empty($user['departments']) ? htmlspecialchars($user['departments']) : 'None' ?></td>
                                    <td>
                                        <a href='edit-user.php?id=<?= urlencode($user['id']) ?>' class='edit-button'>Edit</a>
                                        <form action='delete-user.php' method='POST' style='display:inline;'>
                                            <input type='hidden' name='user_id' value='<?= htmlspecialchars($user['id']) ?>'>
                                            <button type='submit' class='delete-button'
                                                onclick='return confirm("Are you sure you want to delete this user?")'>Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No users found.</p>
                <?php endif; ?>
            <?php elseif ($user_role === 'Manager'): ?>
                <p>Viewing users in your department(s):
                    <strong><?= htmlspecialchars(implode(', ', $user_departments)) ?></strong></p>
                <?php if (!empty($users)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Departments</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['role_name']) ?></td>
                                    <td><?= !empty($user['departments']) ? htmlspecialchars($user['departments']) : 'None' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No users found.</p>
                <?php endif; ?>
            <?php endif; ?>

            <a href="welcome.php" class="back-button">Back</a>
        </div>
    </div>

    <!-- Modal for Create User -->
    <div id="createUserModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); z-index: 1000;">
        <h2>Create User</h2>
        <form id="createUserForm">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="department">Department</label>
                <select id="department" name="department" required>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= $department['id'] ?>"><?= htmlspecialchars($department['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Create User</button>
            <button type="button" onclick="closeModal()">Cancel</button>
        </form>
    </div>

    <script>
        function openModal() {
            document.getElementById('createUserModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('createUserModal').style.display = 'none';
        }

        // Handle form submission via AJAX
        document.getElementById('createUserForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('create-user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                alert(data); // Show success or error message
                closeModal();
                location.reload(); // Refresh the page to show the new user
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    </script>
</body>

</html>