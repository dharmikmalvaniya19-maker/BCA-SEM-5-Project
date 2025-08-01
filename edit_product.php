<?php
session_start();
include "db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch existing product
$result = $conn->query("SELECT * FROM products WHERE id=$id");
$product = $result->fetch_assoc();

if (!$product) {
    header("Location: products.php");
    exit();
}

// Handle form submission
$error = "";
if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $brand_name = mysqli_real_escape_string($conn, $_POST['brand_name']);
    $price = floatval($_POST['price']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $tag = mysqli_real_escape_string($conn, $_POST['tag']);
    $image = !empty($_FILES['image']['name']) ? preg_replace("/[^A-Za-z0-9._-]/", "", basename($_FILES['image']['name'])) : $product['image'];
    $target = "../assets/uploads/" . $image;

    if (!empty($_FILES['image']['name'])) {
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $error = "Failed to upload image!";
        }
    }

$stocks = intval($_POST['stocks']); // Get stocks value and ensure it's an integer
$sql = "UPDATE products SET brand_name='$brand_name', price='$price', description='$description', category='$category', tag='$tag', image='$image', stocks='$stocks' WHERE id=$id";    if ($conn->query($sql)) {
        header("Location: products.php");
        exit();
    } else {
        $error = "Failed to update product: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS from dashboard.php */
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
            margin-left: 250px; /* Adjusted from 220px */
            padding: 30px;
            width: calc(100% - 250px); /* Adjusted from 220px */
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
            gap: 15px;
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

        /* Form Specific Styles (Adjusted for dashboard theme) */
        .product-form {
            background: #ffffff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            max-width: 600px; /* Adjusted for better appearance */
            margin: 30px auto; /* Centered with auto margin */
        }

        .product-form .form-group {
            margin-bottom: 20px;
        }

        .product-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #4b5563; /* Adjusted color */
        }

        .product-form input[type="text"],
        .product-form input[type="number"],
        .product-form textarea,
        .product-form select {
            width: 100%;
            padding: 12px 15px;
            background: #f9fafb; /* Adjusted background */
            border: 1px solid #d1d5db; /* Adjusted border */
            border-radius: 6px;
            color: #333; /* Adjusted color */
            font-size: 0.95rem; /* Adjusted font size */
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .product-form input[type="text"]:focus,
        .product-form input[type="number"]:focus,
        .product-form textarea:focus,
        .product-form select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3); /* Adjusted shadow */
        }

        .product-form textarea {
            resize: vertical;
            min-height: 100px;
        }

        .product-form input[type="file"] {
            padding: 10px 0;
            color: #4b5563;
        }

        .form-actions {
            text-align: right;
            margin-top: 30px; /* Added margin */
        }

        .add-btn { /* Renamed from add-btn to save-changes-btn for clarity */
            background: #3b82f6; /* Primary action button color */
            color: #fff;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .add-btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .back-btn {
            background: #6b7280; /* Neutral button color */
            padding: 10px 20px; /* Adjusted padding */
            text-decoration: none;
            color: #fff;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: background 0.3s ease, transform 0.2s ease;
            display: inline-flex; /* To align icon if any */
            align-items: center;
            gap: 5px;
        }

        .back-btn:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        .error {
            color: #ef4444; /* Error red color */
            text-align: center;
            margin-bottom: 20px;
            padding: 12px;
            background: #fee2e2; /* Light red background */
            border: 1px solid #fca5a5; /* Red border */
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .product-img {
            max-width: 150px;
            height: auto;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        /* Dark mode adjustments for form */
        body.dark-mode .product-form {
            background: #374151;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }

        body.dark-mode .product-form label {
            color: #d1d5db;
        }

        body.dark-mode .product-form input[type="text"],
        body.dark-mode .product-form input[type="number"],
        body.dark-mode .product-form textarea,
        body.dark-mode .product-form select {
            background: #4b5563;
            border-color: #6b7280;
            color: #f3f4f6;
        }

        body.dark-mode .product-form input[type="text"]:focus,
        body.dark-mode .product-form input[type="number"]:focus,
        body.dark-mode .product-form textarea:focus,
        body.dark-mode .product-form select:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.3);
        }

        body.dark-mode .product-form input[type="file"] {
            color: #d1d5db;
        }

        body.dark-mode .add-btn { /* Changed from add-btn to save-changes-btn for consistency */
            background: #2563eb;
        }

        body.dark-mode .add-btn:hover {
            background: #1d4ed8;
        }

        body.dark-mode .back-btn {
            background: #4b5563;
        }

        body.dark-mode .back-btn:hover {
            background: #6b7280;
        }

        body.dark-mode .error {
            background: #450a0a;
            border-color: #b91c1c;
            color: #fca5a5;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .main-content {
                margin-left: 200px;
                width: calc(100% - 200px);
                padding: 20px;
            }

            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 15px;
            }

            .header-controls {
                flex-direction: column;
                align-items: flex-start;
                width: 100%;
            }

            .search-bar input {
                width: 100%;
            }

            .product-form {
                max-width: 95%;
                margin: 20px auto;
                padding: 20px;
            }
        }

        @media (max-width: 600px) {
            .container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative; /* Changed to relative for mobile */
                transform: translateX(0); /* Always visible */
                border-right: none;
                border-bottom: 1px solid #e5e7eb;
                box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .sidebar-toggle {
                display: block; /* Show toggle button */
                position: absolute; /* Position toggle button */
                right: 20px;
                top: 25px;
            }

            .sidebar-nav ul {
                display: none; /* Hide nav by default */
            }

            .sidebar.active .sidebar-nav ul {
                display: block; /* Show nav when active */
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
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
                    <li><a href="products.php" class="active"><i class="fas fa-shoe-prints"></i> Products</a></li>
                    <li><a href="admin_user.php"><i class="fas fa-users"></i> Admin Users</a></li>
                    <li><a href="orders.php"><i class="fas fa-box"></i> Orders</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>

        <div class="main-content">
            <header>
                <div class="header-content">
                    <h1>Edit Product</h1>
                    <p>Modify product details for your inventory.</p>
                </div>
                <div class="header-controls">
                    <button id="themeToggle" class="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    <div class="user-profile">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($_SESSION['admin']); ?></span>
                    </div>
                </div>
            </header>

            <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

            <form class="product-form" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Brand Name</label>
                    <input type="text" name="brand_name" value="<?= htmlspecialchars($product['brand_name']) ?>" placeholder="e.g., Nike Running Shoes" required>
                </div>
                <div class="form-group">
                    <label>Price (₹)</label>
                    <input type="number" name="price" value="<?= $product['price'] ?>" placeholder="e.g., 1999.99" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" required>
                        <option value="men" <?= $product['category'] == 'men' ? 'selected' : '' ?>>Men</option>
                        <option value="women" <?= $product['category'] == 'women' ? 'selected' : '' ?>>Women</option>
                        <option value="kids" <?= $product['category'] == 'kids' ? 'selected' : '' ?>>Kids</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tag (Optional)</label>
                    <select name="tag">
                        <option value="" <?= empty($product['tag']) ? 'selected' : '' ?>>None</option>
                        <option value="best-seller" <?= $product['tag'] == 'best-seller' ? 'selected' : '' ?>>Best Seller</option>
                        <option value="sports" <?= $product['tag'] == 'sports' ? 'selected' : '' ?>>Sports</option>
                        <option value="casual" <?= $product['tag'] == 'casual' ? 'selected' : '' ?>>Casual</option>
                    </select><div class="form-group">
                        <br>
   
                    <label for="stocks">Stocks:</label>
    <input type="number" id="stocks" name="stocks" class="form-control" value="<?php echo htmlspecialchars($product['stocks'] ?? 0); ?>" min="0" required>
</div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="e.g., High-quality running shoes with cushioning" rows="4" required><?= htmlspecialchars($product['description']) ?></textarea>
                </div>
                <div class="form-group">
                    <label>Current Image</label><br>
                    <img src="../assets/uploads/<?= htmlspecialchars($product['image']) ?>" class="product-img" alt="Product" style="width: 100px; height: auto;"><br><br>
                    <label>Change Image (Optional)</label>
                    <input type="file" name="image" accept="image/*">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn add-btn">💾 Save Changes</button>
                    <a href="products.php" class="btn back-btn">⬅ Back to Products</a>
                </div>
            </form>
        </div>
    </div>
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

        // Sidebar toggle for mobile (Adjusted for standalone behavior)
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        if (sidebarToggle) { // Check if the element exists
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                const navUl = sidebar.querySelector('.sidebar-nav ul');
                if (navUl) {
                    navUl.style.display = navUl.style.display === 'block' ? 'none' : 'block';
                }
            });
        }
    </script>
</body>
</html>