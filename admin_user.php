<?php
session_start();

// Adjust path based on location of connect.php
include "db.php"; 

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Check if connection is established
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . (isset($conn) ? $conn->connect_error : "Connection not initialized"));
}

// Fetch current admin info
$admin_username = $_SESSION['admin'];
$stmt = $conn->prepare("SELECT * FROM admin_user WHERE username = ?");
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $admin_username);
$stmt->execute();
$current_admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

$is_full_admin = $current_admin['is_full_admin'] == 1;

// Handle admin settings update
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['update_admin'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE admin_user SET username=?, password=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $username, $hashed_password, $current_admin['id']);
    } else {
        $sql = "UPDATE admin_user SET username=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $username, $current_admin['id']);
    }

    if ($stmt->execute()) {
        $success = "Admin credentials updated successfully!";
        $_SESSION['admin'] = $username;
        header("Location: admin_user.php?success=1");
        exit();
    } else {
        $error = "Failed to update admin credentials!";
    }
    $stmt->close();
}

// Handle new admin registration (only for full admin)
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['register_admin']) && $is_full_admin) {
    $new_username = trim($_POST['new_username']);
    $new_password = trim($_POST['new_password']);

    if (empty($new_username) || empty($new_password)) {
        $reg_error = "All fields are required for new admin registration.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $check_sql = "SELECT * FROM admin_user WHERE username = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $new_username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $reg_error = "Username already exists.";
        } else {
            $insert_sql = "INSERT INTO admin_user (username, password, is_full_admin) VALUES (?, ?, 0)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ss", $new_username, $hashed_password);
            if ($stmt->execute()) {
                $reg_success = "New admin registered successfully!";
            } else {
                $reg_error = "Failed to register new admin.";
            }
        }
        $stmt->close();
    }
}

// Fetch all admins
$adminResult = $conn->query("SELECT * FROM admin_user ORDER BY id DESC");
if ($adminResult === false) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin User Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shoe-prints"></i> Shoes Admin</h2>
                <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php" ><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    <li><a href="products.php"><i class="fas fa-shoe-prints"></i> Products</a></li>
                    <li><a href="orders.php"><i class="fas fa-box"></i> Orders</a></li>
                    <li><a href="users.php"><i class="fas fa-globe"></i> site visitors</a></li>
                    <li><a href="admin_user.php " class="active"><i class="fas fa-user-shield"></i> Admins</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>

        <div class="main-content">
            <header>
                <div class="header-content">
                    <h1>Admin User Management</h1>
                    <p>Securely manage admin accounts for your shoe store.</p>
                </div>
                <div class="header-controls">
                    <div class="search-bar">
                        <input type="text" id="searchInput" placeholder="Search admins...">
                        <i class="fas fa-search"></i>
                    </div>
                    <button id="themeToggle" class="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    <div class="user-profile">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($_SESSION['admin']); ?></span>
                    </div>
                </div>
            </header>

            <!-- Current Admin Settings -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h2><i class="fas fa-user-cog"></i> Current Admin Settings</h2>
                </div>
                <?php if (!empty($_GET['success'])): ?>
                    <div class="notification success">
                        <i class="fas fa-check-circle"></i>
                        <p>Admin credentials updated successfully!</p>
                    </div>
                <?php elseif (!empty($error)): ?>
                    <div class="notification error">
                        <i class="fas fa-exclamation-circle"></i>
                        <p><?= htmlspecialchars($error) ?></p>
                    </div>
                <?php endif; ?>
                <div class="form-container">
                    <form method="POST">
                        <input type="hidden" name="update_admin" value="1">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?= htmlspecialchars($current_admin['username']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="password">New Password <small>(leave blank to keep current)</small></label>
                            <input type="password" id="password" name="password" placeholder="Enter new password">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- New Admin Registration (visible only to full admin) -->
            <?php if ($is_full_admin): ?>
            <section class="dashboard-section">
                <div class="section-header">
                    <h2><i class="fas fa-user-plus"></i> Register New Admin</h2>
                </div>
                <?php if (!empty($reg_success)): ?>
                    <div class="notification success">
                        <i class="fas fa-check-circle"></i>
                        <p><?= htmlspecialchars($reg_success) ?></p>
                    </div>
                <?php elseif (!empty($reg_error)): ?>
                    <div class="notification error">
                        <i class="fas fa-exclamation-circle"></i>
                        <p><?= htmlspecialchars($reg_error) ?></p>
                    </div>
                <?php endif; ?>
                <div class="form-container">
                    <form method="POST">
                        <input type="hidden" name="register_admin" value="1">
                        <div class="form-group">
                            <label for="new_username">New Admin Username</label>
                            <input type="text" id="new_username" name="new_username" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Admin Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Register Admin</button>
                        </div>
                    </form>
                </div>
            </section>
            <?php endif; ?>

            <!-- Admin List -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h2><i class="fas fa-users"></i> All Admins</h2>
                </div>
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Username</th>
                                <th>Registered Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $adminResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><?= $row['created_at'] ?></td>
                                <td>
                                    <a href="delete_admin.php?id=<?= $row['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this admin?')"><i class="fas fa-trash"></i> Delete</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
            transition: background 0.3s, color 0.3s;
        }

        body.dark-mode {
            background: #1e293b;
            color: #f1f5f9;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            padding: 24px;
            position: fixed;
            height: 100vh;
            box-shadow: 2px 0 8px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 16px 0;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: #475569;
            transition: color 0.2s;
        }

        .sidebar-toggle:hover {
            color: #2563eb;
        }

        .sidebar-nav ul {
            list-style: none;
        }

        .sidebar-nav ul li {
            margin-bottom: 12px;
        }

        .sidebar-nav ul li a {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: #475569;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar-nav ul li a i {
            margin-right: 12px;
            font-size: 1.1rem;
        }

        .sidebar-nav ul li a:hover,
        .sidebar-nav ul li a.active {
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .main-content {
            margin-left: 260px;
            padding: 32px;
            width: calc(100% - 260px);
            background: #f8fafc;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            padding: 24px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .header-content h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .header-content p {
            color: #64748b;
            font-size: 1rem;
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .search-bar {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-bar input {
            padding: 10px 12px 10px 40px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            width: 240px;
            font-size: 0.95rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .search-bar input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .search-bar i {
            position: absolute;
            left: 12px;
            color: #64748b;
            font-size: 1.1rem;
        }

        .theme-toggle {
            background: #e2e8f0;
            border: none;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .theme-toggle:hover {
            background: #d1d5db;
            transform: translateY(-2px);
        }

        .theme-toggle i {
            font-size: 1.25rem;
            color: #475569;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1rem;
            font-weight: 500;
            color: #1e293b;
        }

        .user-profile i {
            font-size: 1.75rem;
            color: #2563eb;
        }

        .dashboard-section {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .dashboard-section:hover {
            transform: translateY(-4px);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .section-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
        }

        .section-header i {
            color: #2563eb;
        }

        .form-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #1e293b;
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #1e293b;
            font-size: 0.95rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-group small {
            color: #64748b;
            font-size: 0.85rem;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
        }

        .btn-primary {
            background: #2563eb;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .btn-danger {
            background: #ef4444;
            color: #ffffff;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }

        .btn i {
            margin-right: 8px;
        }

        .notification {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: opacity 0.3s ease;
        }

        .notification.success {
            background: #ecfdf5;
            color: #064e3b;
            border-left: 4px solid #10b981;
        }

        .notification.error {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .notification i {
            font-size: 1.25rem;
        }

        .table-container {
            overflow-x: auto;
        }

        .admin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
        }

        .admin-table th,
        .admin-table td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .admin-table th {
            background: #f1f5f9;
            font-weight: 600;
            color: #1e293b;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .admin-table td {
            color: #475569;
            font-size: 0.95rem;
        }

        .admin-table tr:hover {
            background: #f8fafc;
        }

        body.dark-mode .sidebar,
        body.dark-mode .dashboard-section,
        body.dark-mode header,
        body.dark-mode .admin-table th {
            background: #374151;
            border-color: #475569;
        }

        body.dark-mode .main-content,
        body.dark-mode .form-group input {
            background: #1e293b;
            color: #f1f5f9;
        }

        body.dark-mode .sidebar-header h2,
        body.dark-mode .user-profile,
        body.dark-mode .section-header h2,
        body.dark-mode .form-group label,
        body.dark-mode .admin-table th,
        body.dark-mode .admin-table td {
            color: #f1f5f9;
        }

        body.dark-mode .sidebar-nav ul li a {
            color: #d1d5db;
        }

        body.dark-mode .notification.success {
            background: #064e3b;
            color: #d1fae5;
        }

        body.dark-mode .notification.error {
            background: #7f1d1d;
            color: #fee2e2;
        }

        body.dark-mode .admin-table tr:hover {
            background: #4b5563;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 220px;
            }

            .main-content {
                margin-left: 220px;
                width: calc(100% - 220px);
            }

            .search-bar input {
                width: 180px;
            }

            .form-container {
                max-width: 100%;
            }
        }

        @media (max-width: 600px) {
            .container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: fixed;
                z-index: 1000;
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .sidebar-toggle {
                display: block;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 16px;
            }

            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }

            .header-controls {
                flex-direction: column;
                align-items: flex-start;
                width: 100%;
            }

            .search-bar input {
                width: 100%;
            }

            .admin-table th,
            .admin-table td {
                padding: 12px;
                font-size: 0.9rem;
            }
        }
    </style>

    <script>
        // Theme toggle functionality
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;

        themeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            const icon = themeToggle.querySelector('i');
            if (body.classList.contains('dark-mode')) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
            localStorage.setItem('theme', body.classList.contains('dark-mode') ? 'dark' : 'light');
        });

        // Load saved theme
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            body.classList.add('dark-mode');
            themeToggle.querySelector('i').classList.remove('fa-moon');
            themeToggle.querySelector('i').classList.add('fa-sun');
        }

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.admin-table tbody tr');
            rows.forEach(row => {
                const username = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                row.style.display = username.includes(searchTerm) ? '' : 'none';
            });
        });

        // Highlight active nav link
        const navLinks = document.querySelectorAll('.sidebar-nav a');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                navLinks.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
            });
        });

        // Sidebar toggle for mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    </script>
</body>
</html>