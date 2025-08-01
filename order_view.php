<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

include 'db.php'; // your DB connection

if (!isset($_GET['order_id'])) {
    header("Location: order.php");
    exit();
}

$order_id = intval($_GET['order_id']);
$statusUpdated = false;

// Helper function to safely get values and apply htmlspecialchars
function safe($array, $key, $default = '-') {
    return isset($array[$key]) ? htmlspecialchars($array[$key]) : $default;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_status'])) {
    $newStatus = $_POST['order_status'];
    // Use 'order_status' column
    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $order_id);
    if ($stmt->execute()) {
        $statusUpdated = true;
    }
    $stmt->close();
}

// Fetch the main order details
$orderQuery = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$orderQuery->bind_param("i", $order_id);
$orderQuery->execute();
$orderResult = $orderQuery->get_result();
$order = $orderResult->fetch_assoc();
$orderQuery->close();

if (!$order) {
    // A simple way to handle 'not found' within the layout
    $errorMessage = "Order not found.";
} else {
    // Fetch user associated with the order
    $userQuery = $conn->prepare("SELECT fullname, email FROM users WHERE id = ?");
    $userQuery->bind_param("i", $order['user_id']);
    $userQuery->execute();
    $userResult = $userQuery->get_result();
    $user = $userResult->fetch_assoc();
    $userQuery->close();

    // Fetch order items from the 'order_items' table
    $items = [];
    $itemsQuery = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $itemsQuery->bind_param("i", $order_id);
    $itemsQuery->execute();
    $itemsResult = $itemsQuery->get_result();
    while ($item = $itemsResult->fetch_assoc()) {
        $items[] = $item;
    }
    $itemsQuery->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order #<?= safe($order, 'id') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reusing your existing admin CSS for consistency */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; line-height: 1.6; }
        body.dark-mode { background: #1e293b; color: #f1f5f9; }
        .container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: #ffffff; border-right: 1px solid #e2e8f0; padding: 24px; position: fixed; height: 100vh; }
        body.dark-mode .sidebar { background: #374151; border-color: #475569; }
        .sidebar-header { padding: 16px 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; }
        .sidebar-header h2 { font-size: 1.5rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 12px; }
        body.dark-mode .sidebar-header h2 { color: #f1f5f9; }
        .sidebar-nav ul { list-style: none; }
        .sidebar-nav ul li { margin-bottom: 12px; }
        .sidebar-nav ul li a { display: flex; align-items: center; padding: 12px 16px; color: #475569; text-decoration: none; font-size: 0.95rem; font-weight: 500; border-radius: 8px; transition: all 0.3s ease; }
        body.dark-mode .sidebar-nav ul li a { color: #d1d5db; }
        .sidebar-nav ul li a i { margin-right: 12px; font-size: 1.1rem; }
        .sidebar-nav ul li a:hover, .sidebar-nav ul li a.active { background: #2563eb; color: #ffffff; }
        .main-content { margin-left: 260px; padding: 32px; width: calc(100% - 260px); }
        body.dark-mode .main-content { background: #1e293b; }
        
        /* New Styles for Order View Page */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        .page-header h1 { font-size: 1.875rem; font-weight: 700; }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            background-color: #e5e7eb;
            color: #374151;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }
        .back-link:hover { background-color: #d1d5db; }
        body.dark-mode .back-link { background-color: #4b5563; color: #f1f5f9; }
        body.dark-mode .back-link:hover { background-color: #6b7280; }

        .details-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        .card {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        body.dark-mode .card { background: #374151; }

        .card-header {
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 16px;
            margin-bottom: 16px;
        }
        body.dark-mode .card-header { border-color: #4b5563; }
        .card-header h3 { font-size: 1.25rem; font-weight: 600; }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
        }
        .info-item p { margin: 0; color: #64748b; }
        .info-item strong { color: #1e293b; display: block; font-weight: 500; }
        body.dark-mode .info-item p { color: #d1d5db; }
        body.dark-mode .info-item strong { color: #f1f5f9; }

        .items-table { width: 100%; border-collapse: collapse; }
        .items-table th, .items-table td { padding: 12px 0; border-bottom: 1px solid #e2e8f0; text-align: left; }
        body.dark-mode .items-table th, body.dark-mode .items-table td { border-color: #4b5563; }
        .items-table th { font-weight: 600; font-size: 0.85rem; color: #64748b; }
        .items-table td { font-weight: 500; }
        .items-table .text-right { text-align: right; }
        
        .status-form { margin-top: 20px; display: flex; gap: 10px; align-items: center; }
        .status-form label { font-weight: 500; }
        .status-form select {
            padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;
            background: #ffffff; font-size: 1rem; flex-grow: 1;
        }
        .status-form button {
            padding: 10px 20px; border: none; border-radius: 8px;
            background: #2563eb; color: white; font-weight: 500; cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .status-form button:hover { background: #1d4ed8; }
        
        .success-message {
            background-color: #dcfce7; color: #166534;
            padding: 12px 16px; border-radius: 8px; margin-bottom: 24px;
        }
        body.dark-mode .success-message { background-color: #14532d; color: #dcfce7; }
        
        @media (max-width: 900px) {
            .details-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shoe-prints"></i> Shoes Admin</h2>
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
            <?php if (isset($errorMessage)): ?>
                <div class="card"><?= $errorMessage ?></div>
            <?php else: ?>
                <div class="page-header">
                    <h1>Order #<?= safe($order, 'id') ?></h1>
                    <a href="order.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Orders</a>
                </div>

                <?php if ($statusUpdated): ?>
                    <div class="success-message">✅ Order status has been updated successfully!</div>
                <?php endif; ?>

               <div class="details-grid">
    <div class="left-column">
        <div class="card">
            <div class="card-header"><h3>Ordered Products</h3></div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
   <?php
        $image = isset($item['image_at_purchase']) ? $item['image_at_purchase'] : 'default.jpg';
        $imagePath = "uploads/" . $image;
    ?>
    <img src="<?= htmlspecialchars($imagePath) ?>" alt="Product Image" style="width: 60px; height: auto; border-radius: 8px;">
</td>

                        <td><?= safe($item, 'product_name_at_purchase') ?></td>
                        <td><?= safe($item, 'quantity') ?></td>
                        <td class="text-right">₹<?= number_format(floatval(safe($item, 'price_at_purchase', 0)), 2) ?></td>
                        <td class="text-right">₹<?= number_format(safe($item, 'price_at_purchase', 0) * safe($item, 'quantity', 0), 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>


                    <div class="right-column">
                        <div class="card">
                            <div class="card-header"><h3>Order Summary</h3></div>
                            <div class="info-grid">
                                <div class="info-item">
                                    <strong>Total Amount</strong>
                                    <p style="font-size: 1.5rem; color: #2563eb; font-weight: 700;">₹<?= number_format(floatval($order['total_amount']), 2) ?></p>
                                </div>
                                <div class="info-item">
                                    <strong>Order Date</strong>
                                    <p><?= date('M d, Y, h:i A', strtotime(safe($order, 'order_date', 'now'))) ?></p>
                                </div>
                                <div class="info-item">
                                    <strong>Status</strong>
                                    <p><?= safe($order, 'order_status') ?></p>
                                </div>
                                 <div class="info-item">
                                    <strong>Customer</strong>
                                    <p><?= safe($user, 'fullname') ?></p>
                                    <p><?= safe($user, 'email') ?></p>
                                </div>
                                <div class="info-item">
                                    <strong>Shipping Address</strong>
                                    <p>
                                        <?= safe($order, 'shipping_address') ?><br>
                                        <?= safe($order, 'shipping_city') ?>, <?= safe($order, 'shipping_zip') ?><br>
                                        <?= safe($order, 'shipping_country') ?>
                                    </p>
                                </div>
                            </div>

                            <form method="POST" class="status-form">
                                <label for="order_status">Update Status:</label>
                                <select name="order_status" id="order_status">
                                    <option value="Pending" <?= $order['order_status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="Processing" <?= $order['order_status'] == 'Processing' ? 'selected' : '' ?>>Processing</option>
                                    <option value="Shipped" <?= $order['order_status'] == 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                    <option value="Delivered" <?= $order['order_status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="Completed" <?= $order['order_status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="Cancelled" <?= $order['order_status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                                <button type="submit">Update</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        // Script to handle theme toggle if you add the button
        // For now, it will respect the theme set on other pages
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
        }
    </script>
</body>
</html>