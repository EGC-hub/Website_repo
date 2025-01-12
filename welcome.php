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
            <div class="dashboard-content">
                <!-- Row 1: Key Metrics -->
                <div class="row mb-4">
                    <!-- Open Tickets -->
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Open Tickets</h5>
                                <p class="card-text display-4">15</p>
                                <p class="text-muted">Outstanding</p>
                            </div>
                        </div>
                    </div>

                    <!-- New Joiner Setup -->
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">New Joiner Setup</h5>
                                <p class="card-text display-4">7</p>
                                <p class="text-muted">Tasks</p>
                            </div>
                        </div>
                    </div>

                    <!-- Projects -->
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Projects</h5>
                                <p class="card-text display-4">27</p>
                                <p class="text-muted">Up</p>
                            </div>
                        </div>
                    </div>

                    <!-- Uptime -->
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Uptime</h5>
                                <p class="card-text display-4">99.9%</p>
                                <p class="text-muted">Last downtime: 13h ago</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Tasks by Project -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Tasks by Project</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Cloud Upgrade</strong> - 13 tasks</p>
                                        <p class="mb-1"><strong>Induction Materials</strong> - 7 tasks</p>
                                        <p class="mb-1"><strong>CRM System</strong> - 24 tasks</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Sales</strong> - 1 task</p>
                                        <p class="mb-1"><strong>Website</strong> - 6 tasks</p>
                                        <p class="mb-1"><strong>BAU</strong> - 27 tasks</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pie Chart Placeholder -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Task Distribution</h5>
                                <div class="text-center">
                                    <img src="https://via.placeholder.com/150" alt="Pie Chart Placeholder" class="img-fluid">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Tickets and Budget -->
                <div class="row mb-4">
                    <!-- Tickets This Month -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Tickets This Month</h5>
                                <p class="card-text display-4">148</p>
                                <p class="text-muted">+78 from last month</p>
                            </div>
                        </div>
                    </div>

                    <!-- Budget Utilization -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Budget Utilization</h5>
                                <p class="card-text display-4">$40K</p>
                                <p class="text-muted">84% of budget used</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 4: IT Service NPS -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">IT Service NPS</h5>
                                <p class="card-text display-4">296.9k</p>
                                <p class="text-muted">Resolved within 4 hours</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (with Popper.js) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>