<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$orders = [];

$stmt = $conn->prepare("SELECT o.*, u.fullname AS user_fullname, u.email AS user_email
                        FROM `orders` o
                        JOIN `users` u ON o.user_id = u.id
                        ORDER BY o.order_date DESC");

if ($stmt === false) {
    die("MySQL Prepare Error (fetching orders): " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* --- General, Sidebar, and Header Styles (Unchanged) --- */
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
        
        body.dark-mode .sidebar {
            background: #374151;
            border-color: #475569;
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
        
        body.dark-mode .sidebar-header h2 {
            color: #f1f5f9;
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
        
        body.dark-mode .sidebar-nav ul li a {
            color: #d1d5db;
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
        
        body.dark-mode .main-content {
            background: #1e293b;
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
        
        body.dark-mode header {
             background: #374151;
             border-color: #475569;
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

        .content-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        body.dark-mode .content-card {
             background: #374151;
        }
        
        .content-card h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
        }
        
         body.dark-mode .content-card h1 {
            color: #f1f5f9;
        }

        /* --- NEW AND IMPROVED TABLE STYLES --- */
        .table-container {
            width: 100%;
            overflow-x: auto; /* Enables horizontal scrolling on small screens */
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Inter', sans-serif;
            background-color: #ffffff;
            text-align: left;
        }

        .modern-table thead {
            background-color: #f9fafb;
        }

        .modern-table th {
            padding: 16px 20px;
            color: #475569;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #e2e8f0;
        }

        .modern-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.2s ease;
        }
        
        .modern-table tbody tr:last-child {
            border-bottom: none;
        }

        .modern-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .modern-table td {
            padding: 16px 20px;
            font-size: 0.9rem;
            color: #334155;
            vertical-align: middle;
        }

        /* Status Badge Styles */
        .status-badge {
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 0.8rem;
            font-weight: 500;
            text-align: center;
            display: inline-block;
        }

        .status-completed, .status-delivered {
            background-color: #e7f7ef;
            color: #059669;
        }
        
        .status-shipped {
            background-color: #e0e7ff;
            color: #2563eb;
        }
        
        .status-pending {
            background-color: #fef9c3;
            color: #ca8a04;
        }

        .status-cancelled, .status-failed {
             background-color: #fee2e2;
             color: #dc2626;
        }
        
        .status-processing {
            background-color: #e5e7eb;
            color: #4b5563;
        }


        /* Action Buttons */
        .action-buttons-group {
            display: flex;
            gap: 8px;
            justify-content: flex-start;
        }

        .btn-action {
            padding: 6px 12px;
            border: 1px solid transparent;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .btn-view {
            background-color: #e5e7eb;
            color: #374151;
        }
        .btn-view:hover {
             background-color: #d1d5db;
        }

        .btn-update {
            background-color: #e0e7ff;
            color: #2563eb;
        }
        .btn-update:hover {
            background-color: #c7d2fe;
        }

        .btn-delete {
            background-color: #fee2e2;
            color: #ef4444;
        }
        .btn-delete:hover {
            background-color: #fecaca;
        }
        
        /* Dark Mode Table Styles */
        body.dark-mode .table-container {
             box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
        }
        body.dark-mode .modern-table {
            background-color: #374151;
        }
        body.dark-mode .modern-table thead {
            background-color: #4b5563;
        }
        body.dark-mode .modern-table th {
            color: #d1d5db;
            border-bottom: 2px solid #4b5563;
        }
        body.dark-mode .modern-table tbody tr {
            border-bottom: 1px solid #4b5563;
        }
        body.dark-mode .modern-table tbody tr:hover {
            background-color: #4b5563;
        }
        body.dark-mode .modern-table td {
            color: #f1f5f9;
        }
        body.dark-mode .status-completed, body.dark-mode .status-delivered { background-color: #064e3b; color: #a7f3d0; }
        body.dark-mode .status-shipped { background-color: #1e3a8a; color: #bfdbfe; }
        body.dark-mode .status-pending { background-color: #78350f; color: #fde68a; }
        body.dark-mode .status-cancelled, body.dark-mode .status-failed { background-color: #991b1b; color: #fecaca; }
        body.dark-mode .status-processing { background-color: #4b5563; color: #e5e7eb; }
        
        body.dark-mode .btn-view { background-color: #4b5563; color: #f1f5f9; }
        body.dark-mode .btn-view:hover { background-color: #6b7280; }
        body.dark-mode .btn-update { background-color: #1e40af; color: #e0e7ff; }
        body.dark-mode .btn-update:hover { background-color: #1d4ed8; }
        body.dark-mode .btn-delete { background-color: #991b1b; color: #fecaca; }
        body.dark-mode .btn-delete:hover { background-color: #b91c1c; }


        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .sidebar { width: 220px; }
            .main-content { margin-left: 220px; width: calc(100% - 220px); }
            .search-bar input { width: 180px; }
            .modern-table th, .modern-table td { padding: 12px 14px; }
        }

        @media (max-width: 600px) {
            .container { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: fixed; z-index: 1000; transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .sidebar-toggle { display: block; }
            .main-content { margin-left: 0; width: 100%; padding: 16px; }
            header { flex-direction: column; align-items: flex-start; gap: 16px; }
            .header-controls { flex-direction: column; align-items: flex-start; width: 100%; }
            .search-bar input { width: 100%; }
        }
    </style>
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
                    <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    <li><a href="products.php"><i class="fas fa-shoe-prints"></i> Products</a></li>
                    <li><a href="order.php" class="active"><i class="fas fa-box"></i> Orders</a></li>
                    <li><a href="users.php"><i class="fas fa-globe"></i> Users</a></li>
                    <li><a href="admin_user.php"><i class="fas fa-user-shield"></i> Admins</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>

        <div class="main-content">
            <header>
                <div class="header-content">
                    <h1>Orders</h1>
                </div>
                <div class="header-controls">
                    <div class="search-bar">
                        <input type="text" id="searchInput" placeholder="Search orders...">
                        <i class="fas fa-search"></i>
                    </div>
                    <button id="themeToggle" class="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </header>

            <div class="content-card">
                <h1>All Customer Orders</h1>
                <div class="table-container">
                    <?php if (!empty($orders)): ?>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Total</th>
                                <th>Order Date</th>
                                <th>Status</th>
                                <th>City</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                <td><?php echo htmlspecialchars($order['user_fullname']); ?></td>
                                <td><?php echo htmlspecialchars($order['user_email']); ?></td>
                                <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo 'status-' . strtolower(htmlspecialchars($order['order_status'])); ?>">
                                        <?php echo htmlspecialchars($order['order_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($order['shipping_city']); ?>
                                </td>
                                <td>
                                    <div class="action-buttons-group">
                                        <a href="order_view.php?order_id=<?= $order['id'] ?>" class="btn-action btn-view">View</a>
                                        
                                        <a href="delete_order.php?order_id=<?php echo $order['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this order?');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="no-orders-message" style="text-align: center; padding: 40px;">
                        No orders found yet.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
        // --- JAVASCRIPT (Unchanged) ---
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

        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            body.classList.add('dark-mode');
            themeToggle.querySelector('i').classList.remove('fa-moon');
            themeToggle.querySelector('i').classList.add('fa-sun');
        }

        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const tableBody = document.querySelector('.modern-table tbody');
            const rows = tableBody.querySelectorAll('tr');

            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                if (rowText.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        const navLinks = document.querySelectorAll('.sidebar-nav a');
        navLinks.forEach(link => {
            if (link.href === window.location.href) {
                link.classList.add('active');
            }
        });

        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    </script>
</body>
</html>