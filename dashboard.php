<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: http://localhost/new%20shoes%20house/admin/index.php");
    exit();
}

// Include database connection
include "db.php";

// Check if connection is established
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . (isset($conn) ? $conn->connect_error : "Connection not initialized"));
}

// Fetch real counts from the database
function getTotalProducts($conn) {
    $result = $conn->query("SELECT COUNT(*) as total FROM products");
    if ($result === false) {
        return 0; // Handle error gracefully
    }
    return $result->fetch_assoc()['total'];
}

function getTotalUsers($conn) {
    $result = $conn->query("SELECT COUNT(*) as total FROM users");
    if ($result === false) {
        return 0;
    }
    return $result->fetch_assoc()['total'];
}



function getTotalAdmins($conn) {
    $result = $conn->query("SELECT COUNT(*) as total FROM admin_user");
    if ($result === false) {
        return 0;
    }
    return $result->fetch_assoc()['total'];
}

function getTotalOrders($conn) {
    $result = $conn->query("SELECT COUNT(*) as total FROM orders");
    if ($result === false) {
        return 0;
    }
    return $result->fetch_assoc()['total'];
}

$totalProducts = getTotalProducts($conn);
$totalUsers = getTotalUsers($conn);
$totalAdmins = getTotalAdmins($conn);
$totalOrders = getTotalOrders($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
                    <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    <li><a href="products.php"><i class="fas fa-shoe-prints"></i> Products</a></li>
                    <li><a href="order.php"><i class="fas fa-box"></i> Orders</a></li>
                    <li><a href="users.php"><i class="fas fa-globe"></i> users</a></li>
                    <li><a href="admin_user.php"><i class="fas fa-user-shield"></i> Admins</a></li>
                    <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>

        <div class="main-content">
            <header>
                <div class="header-content">
                    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['admin']); ?> 👋</h1>
                    <p>Manage your shoe store with ease and efficiency.</p>
                </div>
                <div class="header-controls">
                    <div class="search-bar">
                        <input type="text" id="searchInput" placeholder="Search products, users, orders...">
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
            <section class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="add_product.php" class="action-btn" style="text-decoration: none;"><i class="fas fa-plus"></i> Add Product</a>
                    <a href="order.php" class="action-btn" style="text-decoration: none;"><i class="fas fa-box-open"></i> View Recent Orders</a>
                    <a href="admin_user.php" class="action-btn" style="text-decoration: none;"><i class="fas fa-user-plus"></i> Add Admin</a>
                </div>
            </section>
            <section class="dashboard-grid">
                <div class="card card-gradient products">
                    <i class="fas fa-shoe-prints card-icon"></i>
                    <h3>Products</h3>
                    <p>Total: <?php echo $totalProducts; ?></p>
                    <a href="products.php" class="card-link">View Products</a>
                </div>
                <div class="card card-gradient users">
    <i class="fas fa-users card-icon"></i>
    <h3>Users</h3>
    <p>Total: <?php echo $totalUsers; ?></p>

    <a href="users.php" class="card-link">View Users</a>
</div>

                <div class="card card-gradient admins">
                    <i class="fas fa-user-shield card-icon"></i>
                    <h3>Admins</h3>
                    <p>Total: <?php echo $totalAdmins; ?></p>
                    <a href="admin_user.php" class="card-link">View Admins</a>
                </div>
                <div class="card card-gradient orders">
                    <i class="fas fa-box card-icon"></i>
                    <h3>Orders</h3>
                    <p>Total: <?php echo $totalOrders; ?></p>
                    <a href="order.php" class="card-link">View Orders</a>
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

        .quick-actions {
            margin-bottom: 32px;
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .quick-actions h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 12px 24px;
            background: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
        }

        .action-btn i {
            margin-right: 8px;
        }

        .action-btn:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .card {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.1);
        }

        .card-gradient.products {
            background: linear-gradient(135deg, #2563eb, #60a5fa);
            color: #ffffff;
        }

        .card-gradient.users {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: #ffffff;
        }

        .card-gradient.admins {
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
            color: #ffffff;
        }

        .card-gradient.orders {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            color: #ffffff;
        }

        .card-gradient h3,
        .card-gradient p {
            color: #ffffff;
        }

        .card-icon {
            font-size: 2.25rem;
            margin-bottom: 16px;
        }

        .card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .card p {
            font-size: 1rem;
            margin-bottom: 16px;
        }

        .card-link {
            display: inline-block;
            padding: 10px 20px;
            background: #ffffff;
            color: #2563eb;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
        }

        .card-link:hover {
            background: #e2e8f0;
            color: #1d4ed8;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        body.dark-mode .sidebar,
        body.dark-mode .card,
        body.dark-mode header,
        body.dark-mode .quick-actions {
            background: #374151;
            border-color: #475569;
        }

        body.dark-mode .main-content {
            background: #1e293b;
        }

        body.dark-mode .sidebar-header h2,
        body.dark-mode .user-profile,
        body.dark-mode .quick-actions h2,
        body.dark-mode .card h3 {
            color: #f1f5f9;
        }

        body.dark-mode .sidebar-nav ul li a,
        body.dark-mode .card p {
            color: #d1d5db;
        }

        body.dark-mode .card-link {
            background: #4b5563;
            color: #f1f5f9;
        }

        body.dark-mode .card-link:hover {
            background: #6b7280;
            color: #e0e7ff;
        }

        body.dark-mode .action-btn {
            background: #1d4ed8;
        }

        body.dark-mode .action-btn:hover {
            background: #1e40af;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 220px;
            }

            .main-content {
                margin-left: 220px;
                width: calc(100% - 220px);
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .search-bar input {
                width: 180px;
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

        // Search functionality (placeholder)
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            console.log('Searching for:', searchTerm);
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