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
        /* Your original styling */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }

        /* Apply box-sizing to all elements */
        * {
            box-sizing: border-box;
        }

        .main-container {
            width: 90%;
            max-width: 1200px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .user-info {
            text-align: center;
            width: 90%;
            max-width: 1200px;
            margin-top: 20px;
            margin-bottom: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .user-info p {
            margin: 5px 0;
            font-size: 16px;
            color: #333;
        }

        .user-info .session-warning {
            color: grey;
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 2.2rem;
            text-align: center;
            color: #1d3557;
            margin-bottom: 20px;
        }

        h2 {
            font-size: 1.5rem;
            color: #457b9d;
            margin-top: 30px;
        }

        p {
            text-align: center;
            font-size: 1rem;
            color: #457b9d;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th,
        table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        table th {
            background-color: #1d3557;
            color: #fff;
            font-weight: bold;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #002c5f;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .back-button:hover {
            background-color: #004080;
        }

        .edit-button {
            display: inline-block;
            padding: 5px 10px;
            background-color: #457b9d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }

        .edit-button:hover {
            background-color: #1d3557;
        }

        .delete-button {
            display: inline-block;
            padding: 5px 10px;
            background-color: #e63946;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .delete-button:hover {
            background-color: #d62828;
        }

        button.delete-button {
            font-family: 'Poppins', sans-serif;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 1.8rem;
            }

            table th,
            table td {
                font-size: 0.9rem;
                padding: 8px;
            }

            .back-button {
                font-size: 0.9rem;
            }
        }

        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            width: 90%;
            max-width: 500px;
        }

        .modal h2 {
            margin-top: 0;
        }

        .modal .form-group {
            margin-bottom: 15px;
        }

        .modal label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .modal input[type="text"],
        .modal input[type="password"],
        .modal input[type="email"],
        .modal select {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .modal button {
            width: 100%;
            padding: 10px;
            background-color: #002c5f;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .modal button:hover {
            background-color: #004080;
        }

        .modal .cancel-btn {
            background-color: #e63946;
            margin-top: 10px;
        }

        .modal .cancel-btn:hover {
            background-color: #d62828;
        }

        /* Overlay for Modal */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
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
            <button onclick="openModal()" style="margin-bottom: 20px; padding: 10px 20px; background-color: #002c5f; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Create User
            </button>

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
    <div class="overlay" id="overlay"></div>
    <div class="modal" id="createUserModal">
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
            <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
        </form>
    </div>

    <script>
        function openModal() {
            document.getElementById('createUserModal').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('createUserModal').style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
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