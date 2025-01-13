<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="icon" type="image/png" sizes="56x56" href="images/logo/logo-2.1.ico" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: #002c5f;
            color: white;
            padding: 20px;
        }

        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .sidebar a:hover {
            background-color: #004080;
        }

        .main-content {
            flex-grow: 1;
            padding: 20px;
            background-color: #ffffff;
        }

        .user-info {
            margin-right: 20px;
            font-size: 14px;
        }

        .logout-btn {
            background-color: #ff4d4d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #ff1a1a;
        }

        .dashboard-content {
            padding: 20px;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #002c5f;
        }

        .card-text {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
        }

        .text-muted {
            font-size: 0.9rem;
            color: #666;
        }

        .list-group-item {
            border: none;
            padding: 10px 15px;
        }

        .list-group-item:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h3>Menu</h3>
            <?php if ($userRole === 'Admin'): ?>
                <a href="data-display.php">Data Display</a>
            <?php endif; ?>
            <a href="tasks.php">Tasks</a>
            <?php if ($userRole === 'Admin' || $userRole === 'Manager'): ?>
                <a href="view-users.php">View Users</a>
            <?php endif; ?>
            <?php if ($userRole === 'Admin'): ?>
                <a href="view-roles-departments.php">View Role or Department</a>
            <?php endif; ?>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Navbar -->
            <div class="navbar">
                <!-- Logo Container -->
                <div class="d-flex align-items-center me-3">
                    <img src="images/logo/logo.webp" alt="Logo" class="logo" style="width: auto; height: 80px;">
                </div>

                <!-- User Info -->
                <div class="user-info me-3">
                    <p class="mb-0">Logged in as: <strong><?= htmlspecialchars($username) ?></strong></p>
                    <p class="mb-0">Departments:
                        <strong><?= !empty($userDepartments) ? htmlspecialchars(implode(', ', $userDepartments)) : 'None' ?></strong>
                    </p>
                </div>

                <!-- Logout Button -->
                <button class="logout-btn" onclick="window.location.href='logout.php'">Log Out</button>
            </div>

            <!-- Dashboard Content -->
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Row 1: Key Metrics -->
                <div class="row mb-4">
                    <!-- Open Tickets -->
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Total Tasks</h5>
                                <p class="card-text display-4">15</p>
                                <p class="text-muted">Outstanding</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tasks in Progress -->
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Tasks in Progress</h5>
                                <p class="card-text display-4">27</p>
                                <p class="text-muted">Active</p>
                            </div>
                        </div>
                    </div>

                    <!-- Completed Tasks -->
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Completed Tasks</h5>
                                <p class="card-text display-4">42</p>
                                <p class="text-muted">This Month</p>
                            </div>
                        </div>
                    </div>

                    <!-- Delayed Tasks -->
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Delayed Tasks</h5>
                                <p class="card-text display-4">8</p>
                                <p class="text-muted">Overdue</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Charts and Graphs -->
                <div class="row mb-4">
                    <!-- Task Distribution Chart -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Task Distribution</h5>
                                <div class="text-center">
                                    <img src="https://via.placeholder.com/400x200" alt="Task Distribution Chart"
                                        class="img-fluid">
                                    <p class="text-muted mt-2">Pie chart showing task distribution by status.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Task Completion Over Time -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Task Completion Over Time</h5>
                                <div class="text-center">
                                    <img src="https://via.placeholder.com/400x200" alt="Task Completion Chart"
                                        class="img-fluid">
                                    <p class="text-muted mt-2">Line chart showing task completion trends.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Additional Metrics -->
                <div class="row mb-4">
                    <!-- Average Task Duration -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Average Task Duration</h5>
                                <p class="card-text display-4">5.2</p>
                                <p class="text-muted">Days</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tasks by Department -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Tasks by Department</h5>
                                <div class="text-center">
                                    <img src="https://via.placeholder.com/400x200" alt="Tasks by Department Chart"
                                        class="img-fluid">
                                    <p class="text-muted mt-2">Bar chart showing tasks by department.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Performance -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Top Performers</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">John Doe - 15 tasks completed</li>
                                    <li class="list-group-item">Jane Smith - 12 tasks completed</li>
                                    <li class="list-group-item">Alice Johnson - 10 tasks completed</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bootstrap JS (with Popper.js) -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>