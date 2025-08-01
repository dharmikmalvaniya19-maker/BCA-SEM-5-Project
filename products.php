<?php
session_start();
include "db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$result = $conn->query("SELECT * FROM products");

// Fetch distinct categories
$categories_query = $conn->query("SELECT DISTINCT category FROM products ORDER BY category ASC");
$categories = [];
while ($row = $categories_query->fetch_assoc()) {
    $categories[] = $row['category'];
}

// Fetch distinct tags
$tags_query = $conn->query("SELECT DISTINCT tag FROM products ORDER BY tag ASC");
$tags = [];
while ($row = $tags_query->fetch_assoc()) {
    if (!empty($row['tag'])) { // Exclude empty tags
        $tags[] = $row['tag'];
    }
}

// Placeholder function for notifications
function getNotifications() {
    return [
        ['message' => 'New product added successfully', 'time' => '2025-07-13 16:00', 'type' => 'success'],
        ['message' => 'Product stock low for item #45', 'time' => '2025-07-13 15:00', 'type' => 'warning'],
        ['message' => 'Product update failed', 'time' => '2025-07-13 14:30', 'type' => 'error']
    ];
}

$notifications = getNotifications();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shoe-prints"></i> Shoe Admin</h2>
                <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php" ><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    <li><a href="products.php" class="active"><i class="fas fa-shoe-prints"></i> Products</a></li>
                    <li><a href="order.php"><i class="fas fa-box"></i> Orders</a></li>
                    <li><a href="users.php"><i class="fas fa-globe"></i> site visitors</a></li>
                    <li><a href="admin_user.php"><i class="fas fa-user-shield"></i> Admins</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>

        <div class="main-content">
            <header>
                <div class="header-content">
                    <h1>Manage Products</h1>
                    <p>View and manage your shoe inventory.</p>
                </div>
                <div class="header-controls">
                    <div class="search-bar">
                        <input type="text" id="searchInput" placeholder="Search products...">
                        <i class="fas fa-search"></i>
                    </div>

                    <div class="filter-dropdown">
                        <select id="categoryFilter">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-dropdown">
                        <select id="tagFilter">
                            <option value="">All Tags</option>
                            <?php foreach ($tags as $tag): ?>
                                <option value="<?php echo htmlspecialchars($tag); ?>"><?php echo htmlspecialchars($tag); ?></option>
                            <?php endforeach; ?>
                        </select>
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
                    <a href="add_product.php" class="action-btn"><i class="fas fa-plus"></i> Add Product</a>
                    <button type="submit" form="productForm" class="action-btn" id="deleteSelectedBtn" disabled><i class="fas fa-trash"></i> Delete Selected</button>
                </div>
            </section>
            <section class="product-table-section">
                <form id="productForm" action="bulk_delete_products.php" method="POST">
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>ID</th>
                                <th>Brand Name</th>
                                <th>Price (₹)</th>
                                <th>Category</th>
                                <th>Tag</th>
                                  <th>Stocks</th>
                                <th>Image</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="productTableBody">
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><input type="checkbox" name="product_ids[]" value="<?php echo htmlspecialchars($row['id']); ?>"></td>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['brand_name']); ?></td>
                                    <td><?php echo number_format($row['price'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                                    <td><?php echo htmlspecialchars($row['tag'] ?? 'None'); ?></td>
                                    <td><?php echo htmlspecialchars($row['stocks']); ?></td>
                                    <td>
                                        <?php
                                        $imagePath = '../assets/uploads/' . htmlspecialchars($row['image']);
                                        if ($row['image'] && file_exists($imagePath)) {
                                            echo '<img src="' . $imagePath . '" alt="Product" style="width: 50px; border-radius: 4px; object-fit: cover;">';
                                        } else {
                                            echo '<span style="color: #dc3545;">Image not found</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="btn edit-btn"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="delete_product.php?id=<?php echo $row['id']; ?>" class="btn delete-btn" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i> Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </form>
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
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
            transition: background 0.3s, color 0.3s;
        }

        body.dark-mode {
            background: #1f2937;
            color: #f3f4f6;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
            padding: 20px;
            position: fixed;
            height: 100vh;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 20px 0;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #4b5563;
        }

        .sidebar-nav ul {
            list-style: none;
        }

        .sidebar-nav ul li {
            margin-bottom: 10px;
        }

        .sidebar-nav ul li a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #4b5563;
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
            background: #3b82f6;
            color: #ffffff;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
            width: calc(100% - 250px);
            background: #f5f7fa;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .header-content h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .header-content p {
            color: #6b7280;
            font-size: 1rem;
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 15px; /* Adjust gap for spacing between controls */
            flex-wrap: wrap; /* Allow controls to wrap on smaller screens */
        }

        .search-bar {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-bar input {
            padding: 8px 12px 8px 35px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            width: 200px;
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
        }

        .search-bar input:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .search-bar i {
            position: absolute;
            left: 12px;
            color: #6b7280;
        }

        .filter-dropdown {
            position: relative;
            display: flex;
            align-items: center;
        }

        .filter-dropdown select {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.9rem;
            background-color: #ffffff;
            cursor: pointer;
            transition: border-color 0.3s ease;
            appearance: none; /* Remove default dropdown arrow */
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%236B7280'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 16px;
            padding-right: 30px; /* Make space for the custom arrow */
        }

        .filter-dropdown select:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .theme-toggle {
            background: #e5e7eb;
            border: none;
            padding: 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .theme-toggle:hover {
            background: #d1d5db;
        }

        .theme-toggle i {
            font-size: 1.2rem;
            color: #4b5563;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            color: #1f2937;
        }

        .user-profile i {
            font-size: 1.5rem;
            color: #3b82f6;
        }

        .quick-actions {
            margin-bottom: 30px;
            background: #ffffff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .quick-actions h2 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 15px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 10px 20px;
            background: #10b981;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .action-btn:disabled {
            background: #6b7280;
            cursor: not-allowed;
        }

        .action-btn i {
            margin-right: 8px;
        }

        .action-btn:hover:not(:disabled) {
            background: #059669;
            transform: translateY(-2px);
        }

        .product-table-section {
            background: #ffffff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
        }

        .product-table th,
        .product-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .product-table th {
            background: #f9fafb;
            font-weight: 600;
            color: #1f2937;
        }

        .product-table tr {
            transition: background 0.3s ease;
        }

        .product-table tr:hover {
            background: #f3f4f6;
        }

        .product-table img {
            border-radius: 4px;
            object-fit: cover;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            color: #ffffff;
            margin-right: 5px;
            font-size: 0.9rem;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .edit-btn {
            background: #3b82f6;
        }

        .edit-btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .delete-btn {
            background: #dc3545;
        }

        .delete-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .notifications-panel {
            background: #ffffff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .notifications-panel h2 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notifications-panel h2 button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            color: #4b5563;
        }

        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 200px;
            overflow-y: auto;
            transition: max-height 0.3s ease;
        }

        .notifications-list.collapsed {
            max-height: 0;
            overflow: hidden;
        }

        .notification-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-left: 4px solid;
            transition: background 0.3s ease;
        }

        .notification-item.success {
            border-color: #10b981;
        }

        .notification-item.warning {
            border-color: #f59e0b;
        }

        .notification-item.error {
            border-color: #dc3545;
        }

        .notification-item:hover {
            background: #f9fafb;
        }

        .notification-item p {
            color: #4b5563;
            font-size: 0.9rem;
        }

        .notification-item span {
            color: #6b7280;
            font-size: 0.85rem;
        }

        body.dark-mode .sidebar,
        body.dark-mode .product-table-section,
        body.dark-mode header,
        body.dark-mode .quick-actions,
        body.dark-mode .notifications-panel {
            background: #374151;
            border-color: #4b5563;
        }

        body.dark-mode .sidebar-header h2,
        body.dark-mode .user-profile,
        body.dark-mode .quick-actions h2,
        body.dark-mode .product-table th,
        body.dark-mode .notifications-panel h2 {
            color: #f3f4f6;
        }

        body.dark-mode .sidebar-nav ul li a,
        body.dark-mode .product-table td,
        body.dark-mode .notification-item p {
            color: #d1d5db;
        }

        body.dark-mode .main-content {
            background: #1f2937;
        }

        body.dark-mode .product-table th {
            background: #4b5563;
        }

        body.dark-mode .product-table tr:hover {
            background: #4b5563;
        }

        body.dark-mode .action-btn:not(:disabled) {
            background: #059669;
        }

        body.dark-mode .action-btn:hover:not(:disabled) {
            background: #047857;
        }

        body.dark-mode .filter-dropdown select {
            background-color: #374151;
            color: #f3f4f6;
            border-color: #4b5563;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23d1d5db'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E"); /* Dark mode arrow color */
        }


        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .main-content {
                margin-left: 200px;
                width: calc(100% - 200px);
            }

            .search-bar input {
                width: 150px;
            }

            .product-table {
                font-size: 0.85rem;
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
            }

            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .header-controls {
                flex-direction: column;
                align-items: flex-start;
                width: 100%;
            }

            .search-bar input {
                width: 100%;
            }

            .product-table {
                display: block;
                overflow-x: auto;
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

        // Sidebar toggle for mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        // Search and Filter functionality (client-side filtering)
        const searchInput = document.getElementById('searchInput');
        const categoryFilter = document.getElementById('categoryFilter');
        const tagFilter = document.getElementById('tagFilter');
        const productTableBody = document.getElementById('productTableBody');
        const rows = productTableBody.getElementsByTagName('tr');

        function applyFilters() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedCategory = categoryFilter.value.toLowerCase();
            const selectedTag = tagFilter.value.toLowerCase();

            for (let row of rows) {
                const brandName = row.cells[2].textContent.toLowerCase();
                const category = row.cells[4].textContent.toLowerCase();
                const tag = row.cells[5].textContent.toLowerCase(); // Ensure this matches your HTML structure for tag

                const matchesSearch = brandName.includes(searchTerm) || category.includes(searchTerm) || tag.includes(searchTerm);
                const matchesCategory = selectedCategory === '' || category === selectedCategory;
                const matchesTag = selectedTag === '' || tag === selectedTag;

                if (matchesSearch && matchesCategory && matchesTag) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }

        searchInput.addEventListener('input', applyFilters);
        categoryFilter.addEventListener('change', applyFilters);
        tagFilter.addEventListener('change', applyFilters);

        // Initial filter application in case of pre-filled inputs (though not typical here)
        applyFilters();


        // Notifications panel toggle
        const toggleNotifications = document.getElementById('toggleNotifications'); // This element doesn't seem to exist in your HTML
        // const notificationsList = document.getElementById('notificationsList'); // This element doesn't seem to exist in your HTML
        // if (toggleNotifications && notificationsList) { // Check if elements exist before adding listener
        //     toggleNotifications.addEventListener('click', () => {
        //         notificationsList.classList.toggle('collapsed');
        //         const icon = toggleNotifications.querySelector('i');
        //         if (notificationsList.classList.contains('collapsed')) {
        //             icon.classList.remove('fa-chevron-down');
        //             icon.classList.add('fa-chevron-up');
        //         } else {
        //             icon.classList.remove('fa-chevron-up');
        //             icon.classList.add('fa-chevron-down');
        //         }
        //     });
        // }


        // Bulk delete functionality
        const selectAllCheckbox = document.getElementById('selectAll');
        const productCheckboxes = document.querySelectorAll('input[name="product_ids[]"]');
        const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');

        // Enable/disable Delete Selected button based on checkbox selection
        function updateDeleteButton() {
            const checkedCount = document.querySelectorAll('input[name="product_ids[]"]:checked').length;
            deleteSelectedBtn.disabled = checkedCount === 0;
        }

        // Select all checkboxes
        selectAllCheckbox.addEventListener('change', () => {
            productCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateDeleteButton();
        });

        // Update button state when individual checkboxes change
        productCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                updateDeleteButton();
                selectAllCheckbox.checked = document.querySelectorAll('input[name="product_ids[]"]').length === document.querySelectorAll('input[name="product_ids[]"]:checked').length;
            });
        });

        // Confirm before submitting bulk delete
        document.getElementById('productForm').addEventListener('submit', (e) => {
            const checkedCount = document.querySelectorAll('input[name="product_ids[]"]:checked').length;
            if (checkedCount === 0) {
                e.preventDefault();
                alert('Please select at least one product to delete.');
            } else if (!confirm(`Are you sure you want to delete ${checkedCount} product(s)?`)) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>