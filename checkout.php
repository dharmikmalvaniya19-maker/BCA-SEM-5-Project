<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include "./db.php"; // Adjust path if necessary

// Debug: Check if db.php loaded $conn
if (!isset($conn)) {
    die("Connection variable not set. Check db.php include or file path.");
} elseif ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Redirect if cart is empty or user not logged in
if (empty($_SESSION['cart'])) {
    $_SESSION['checkout_message'] = "Your cart is empty. Please add products before checking out.";
    header('Location: cart.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php'; // Store current page to redirect after login
    
    header('Location: http://localhost/new%20shoes%20house/admin/user_login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = '';
$user_email = '';

// Fetch user details for pre-filling form
$stmt = $conn->prepare("SELECT fullname, email FROM users WHERE id = ?");
if ($stmt === false) {
    die("MySQL Prepare Error: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $username = htmlspecialchars($row['fullname']);
    $user_email = htmlspecialchars($row['email']);
}
$stmt->close();


$cart_items_details = [];
$total_cart_amount = 0;

if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));

    $stmt = $conn->prepare("SELECT id, brand_name, price, image FROM products WHERE id IN ($placeholders)");
    if ($stmt === false) {
        die("MySQL Prepare Error: " . $conn->error);
    }
    $types = str_repeat('i', count($product_ids));
    $stmt->bind_param($types, ...$product_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $product_id = $row['id'];
        $quantity = $_SESSION['cart'][$product_id];
        $item_total = $row['price'] * $quantity;
        $total_cart_amount += $item_total;

        // Correct image path handling
        $imagePath = file_exists('../assets/uploads/' . $row['image']) ? '../assets/uploads/' . htmlspecialchars($row['image']) : '../images/no-image.png';

        $cart_items_details[] = [
            'id' => $row['id'],
            'brand_name' => htmlspecialchars($row['brand_name']),
            'price' => $row['price'],
            'image' => $imagePath,
            'quantity' => $quantity,
            'item_total' => $item_total
        ];
    }
    $stmt->close();
}

// Handle form submission for placing order
$order_placed = false;
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $shipping_name = htmlspecialchars(trim($_POST['shipping_name']));
    $shipping_address = htmlspecialchars(trim($_POST['shipping_address']));
    $shipping_city = htmlspecialchars(trim($_POST['shipping_city']));
    $shipping_zip = htmlspecialchars(trim($_POST['shipping_zip']));
    $shipping_country = htmlspecialchars(trim($_POST['shipping_country']));
    $payment_method = htmlspecialchars(trim($_POST['payment_method']));

    if (empty($shipping_name) || empty($shipping_address) || empty($shipping_city) || empty($shipping_zip) || empty($shipping_country) || empty($payment_method)) {
        $error_message = "Please fill in all shipping and payment details.";
    } elseif (empty($cart_items_details)) {
        $error_message = "Your cart is empty. Please add products before checking out.";
    } else {
        $conn->begin_transaction();
        try {
            // Insert into orders table
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_name, shipping_address, shipping_city, shipping_zip, shipping_country, payment_method, order_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
            if ($stmt === false) {
                throw new Exception("MySQL Prepare Error: " . $conn->error);
            }
            $stmt->bind_param("idssssss", $user_id, $total_cart_amount, $shipping_name, $shipping_address, $shipping_city, $shipping_zip, $shipping_country, $payment_method);
            $stmt->execute();
            $order_id = $conn->insert_id;
            $stmt->close();

            // Insert into order_items table
            $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name_at_purchase, image_at_purchase, quantity, price_at_purchase) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt_item === false) {
                throw new Exception("MySQL Prepare Error for order_items: " . $conn->error);
            }

            foreach ($cart_items_details as $item) {
                $product_name_at_purchase = $item['brand_name'];
                $image_at_purchase = basename($item['image']); // Store only filename
                $quantity = $item['quantity'];
                $price_at_purchase = $item['price'];

                $stmt_item->bind_param("iisdid", $order_id, $item['id'], $product_name_at_purchase, $image_at_purchase, $quantity, $price_at_purchase);
                $stmt_item->execute();
            }
            $stmt_item->close();

            $conn->commit();
            $_SESSION['cart'] = []; // Clear the cart

            // Store order details in session for confirmation page
            $_SESSION['last_order_details'] = [
                'order_id' => $order_id,
                'total_amount' => $total_cart_amount,
                'shipping_name' => $shipping_name,
                'shipping_address' => $shipping_address . ', ' . $shipping_city . ', ' . $shipping_zip . ', ' . $shipping_country,
                'payment_method' => $payment_method,
                'items' => $cart_items_details // Include item details
            ];

            // Redirect to a dedicated confirmation page
            header('Location: order_confirmation.php');
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error placing order: " . $e->getMessage();
            error_log("Order Placement Error: " . $e->getMessage()); // Log detailed error
        }
    }
}

// Display messages from session (e.g., redirect messages)
if (isset($_SESSION['checkout_message'])) {
    $error_message = $_SESSION['checkout_message'];
    unset($_SESSION['checkout_message']); // Clear message after display
}

$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Shoes House</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <style>
        /* --- Global Styles & Resets --- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #673ab7; /* Deep Purple */
            --secondary-color: #ffc107; /* Amber */
            --accent-color: #f44336; /* Red */
            --text-dark: #333;
            --text-light: #666;
            --background-light: #fefefe;
            --background-body: #f8f9fa; /* Light Gray */
            --border-color: #ddd;
            --shadow-soft: rgba(0, 0, 0, 0.08);
            --shadow-medium: rgba(0, 0, 0, 0.18);
            --gradient-checkout: linear-gradient(135deg, #ede7f6, #e0f2f7); /* Light purple-blue gradient */
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.7;
            color: var(--text-light);
            background-color: var(--background-body);
            scroll-behavior: smooth;
            overflow-x: hidden;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 30px;
        }

        h1, h2, h3, h4, h5, h6 {
            font-weight: 800;
            margin-bottom: 20px;
            color: var(--text-dark);
            line-height: 1.2;
        }

        h1 { font-size: 3.8rem; }
        h2 { font-size: 3rem; }
        h3 { font-size: 2.2rem; }
        p { margin-bottom: 18px; }

        a {
            text-decoration: none;
            color: var(--primary-color);
            transition: color 0.3s ease;
        }

        a:hover {
            color: var(--accent-color);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: var(--primary-color);
            color: #fff;
            padding: 16px 32px;
            border-radius: 50px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            box-shadow: 0 6px 12px var(--shadow-soft);
        }

        .btn:hover {
            background-color: var(--accent-color);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 16px var(--shadow-medium);
        }

        .btn i {
            font-size: 1.1em;
        }

        .page-title-section {
            background-color: var(--primary-color);
            color: #fff;
            padding: 40px 0;
            text-align: center;
            box-shadow: 0 4px 15px var(--shadow-medium);
            margin-bottom: 50px;
        }

        .page-title-section h1 {
            font-size: 3.5rem;
            color: #fff;
            margin-bottom: 0;
            font-family: 'Pacifico', cursive;
            text-shadow: 2px 2px 5px rgba(0,0,0,0.3);
        }

        .header {
            background-color: #fff;
            box-shadow: 0 4px 15px var(--shadow-soft);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .logo a {
            font-family: 'Pacifico', cursive;
            font-size: 2.8rem;
            font-weight: normal;
            color: var(--primary-color);
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        .navbar ul {
            list-style: none;
            display: flex;
            margin: 0;
        }

        .navbar ul li {
            margin-left: 40px;
            position: relative;
        }

        .navbar ul li a {
            color: var(--text-dark);
            font-weight: 600;
            font-size: 1.1rem;
            position: relative;
            padding-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .navbar ul li a::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 0;
            height: 4px;
            background-color: var(--accent-color);
            border-radius: 2px;
            transition: width 0.3s ease-out;
        }

        .navbar ul li a:hover::after,
        .navbar ul li a.active::after {
            width: 100%;
        }

        .nav-icons {
            display: flex;
            align-items: center;
        }

        .nav-icons a {
            margin-left: 25px;
            color: var(--text-dark);
            font-size: 1.5rem;
            transition: color 0.3s ease, transform 0.2s ease;
            position: relative;
        }

        .nav-icons a:hover {
            color: var(--accent-color);
            transform: scale(1.15);
        }

        .cart-count {
            background-color: var(--accent-color);
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 50%;
            padding: 3px 7px;
            position: absolute;
            top: -8px;
            right: -8px;
            min-width: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        /* Dropdown Menu */
        .navbar .dropdown-menu {
            background-color: #fff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 6px 20px var(--shadow-medium);
            padding: 15px 0;
            min-width: 200px;
            margin-top: 10px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: opacity 0.3s ease, transform 0.3s ease, visibility 0.3s;
        }

        .navbar .has-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .navbar .dropdown-menu a {
            color: var(--text-dark);
            padding: 12px 20px;
            display: block;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .navbar .dropdown-menu a:hover {
            background-color: var(--background-light);
            color: var(--accent-color);
        }

        .navbar .dropdown-menu a.active {
            background-color: var(--primary-color);
            color: #fff;
        }

        .dropdown-toggle::after {
            display: none;
        }

        .dropdown-toggle i {
            font-size: 0.9rem;
            transition: transform 0.3s ease;
        }

        .dropdown:hover .dropdown-toggle i {
            transform: rotate(180deg);
        }

        .user-dropdown {
            position: relative;
            display: inline-block;
            margin-left: 15px;
        }

        .user-dropdown .dropbtn {
            background-color: transparent;
            color: #333;
            padding: 0;
            font-size: 16px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .user-dropdown .dropbtn i {
            margin-right: 5px;
        }

        .user-dropdown .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1000;
            right: 0;
            border-radius: 5px;
            overflow: hidden;
            top: 100%;
        }

        .user-dropdown .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
            font-size: 14px;
        }

        .user-dropdown .dropdown-content a:hover {
            background-color: #ddd;
        }

        .user-dropdown:hover .dropdown-content {
            display: block;
        }

        /* Checkout Page Specific Styles */
        .checkout-section {
            padding: 50px 0;
            background: var(--gradient-checkout); /* Apply a subtle gradient */
        }

        .checkout-container {
            background-color: #fff;
            border-radius: 12px; /* More rounded corners */
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); /* Softer, larger shadow */
            padding: 40px; /* More padding */
        }

        .checkout-container h2 {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 35px;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }

        .checkout-container h2::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background-color: var(--accent-color);
            border-radius: 2px;
        }

        .checkout-form .form-group {
            margin-bottom: 25px; /* More space between form groups */
        }

        .checkout-form label {
            font-weight: 700; /* Bolder labels */
            color: var(--text-dark);
            margin-bottom: 10px;
            display: block;
            font-size: 1.1rem;
        }

        .checkout-form .form-control {
            width: 100%;
            padding: 14px 18px; /* Larger padding */
            border: 1px solid var(--border-color);
            border-radius: 8px; /* More rounded input fields */
            font-size: 1.05rem;
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        .checkout-form select.form-control {
            height: 50px; /* Consistent height */
        }

        .checkout-form .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(103, 58, 183, 0.2); /* Softer focus shadow */
            outline: none;
            background-color: #fffafc; /* Light pink tint on focus */
        }

        .checkout-order-summary {
            background-color: #f0f4f8; /* A calming light blue-gray */
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); /* Lighter shadow */
            position: sticky; /* Keep summary visible on scroll */
            top: 100px; /* Adjust as needed */
            margin-top: 0; /* Override default margin */
        }

        .checkout-order-summary h3 {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 25px;
            text-align: center;
            padding-bottom: 10px;
            position: relative;
        }

        .checkout-order-summary h3::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background-color: var(--accent-color);
            border-radius: 2px;
        }

        .checkout-order-summary .summary-item {
            display: flex;
            align-items: center; /* Vertically align items */
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px dashed #cfd8dc; /* Dashed separator */
            font-size: 1.05rem;
            color: var(--text-dark);
        }

        .checkout-order-summary .summary-item:last-of-type {
            border-bottom: none;
        }

        .summary-item .product-info {
            display: flex;
            align-items: center;
            flex-grow: 1; /* Allows it to take available space */
        }

        .summary-item .product-info img {
            width: 60px; /* Size for product image */
            height: 60px;
            object-fit: cover;
            border-radius: 8px; /* Rounded image corners */
            margin-right: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); /* Subtle shadow on image */
        }

        .summary-item .product-info div {
            flex-grow: 1;
        }

        .summary-item .product-info h6 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--primary-color);
        }

        .summary-item .product-info small {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .summary-item span.item-price {
            font-weight: 600;
            color: var(--accent-color);
        }

        .checkout-order-summary .summary-total {
            font-weight: 700;
            font-size: 1.5rem;
            border-top: 2px solid var(--primary-color); /* Stronger top border */
            padding-top: 20px;
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-dark);
        }

        .checkout-order-summary .summary-total span {
            color: var(--accent-color);
            font-size: 1.8rem;
        }

        .place-order-btn {
            background-color: #28a745; /* Green for action */
            color: #fff;
            padding: 18px 35px; /* Larger button */
            border-radius: 50px;
            font-size: 1.3rem;
            text-decoration: none;
            display: block;
            width: 100%;
            text-align: center;
            margin-top: 40px;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 6px 15px rgba(40, 167, 69, 0.3); /* Green shadow */
            letter-spacing: 1px;
        }

        .place-order-btn:hover {
            background-color: #218838;
            transform: translateY(-3px);
            box-shadow: 0 9px 20px rgba(40, 167, 69, 0.5); /* Stronger green shadow on hover */
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
        }

        .empty-cart-message {
            text-align: center;
            padding: 50px 0;
            font-size: 1.4rem;
            color: var(--text-light);
            background-color: #fdfdfd;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .empty-cart-message p {
            margin-bottom: 20px;
        }

        .empty-cart-message a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: underline;
            transition: color 0.3s ease;
        }

        .empty-cart-message a:hover {
            color: var(--accent-color);
        }

        /* --- Footer Styles - Refined --- */
        .footer {
            background-color: #2c3e50; /* Darker, sophisticated blue-gray */
            color: #ecf0f1; /* Lighter text for contrast */
            padding: 80px 0 40px; /* More vertical padding */
            font-size: 0.95rem;
            box-shadow: inset 0 8px 20px rgba(0,0,0,0.4); /* Stronger inner shadow */
        }

        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around; /* Distributes items with space around */
            align-items: flex-start; /* Aligns items to the top */
            gap: 50px; /* Increased gap between sections */
            margin-bottom: 40px; /* Space above copyright */
        }

        .footer-section {
            flex: 1 1 220px; /* Flexible item, min-width 220px before wrapping */
            max-width: 300px; /* Max width to prevent sections from becoming too wide */
            text-align: left; /* Align text to the left within each section */
            padding: 0 15px; /* Add some horizontal padding inside sections */
        }

        .footer-section h3 {
            color: var(--secondary-color); /* Uses your existing secondary color */
            margin-bottom: 25px;
            font-size: 1.8rem;
            font-weight: 700;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.3); /* Slightly more pronounced shadow */
            position: relative; /* For underline effect */
            padding-bottom: 10px; /* Space for underline */
        }

        .footer-section h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px; /* Shorter, modern underline */
            height: 3px;
            background-color: var(--accent-color); /* Uses your accent color */
            border-radius: 2px;
        }

        .footer-section p {
            margin-bottom: 15px;
            line-height: 1.8;
            color: #bdc3c7; /* Slightly darker text for paragraphs */
        }

        .footer-section p i {
            margin-right: 12px; /* Increased margin for icon */
            color: var(--primary-color); /* Uses your primary color */
            font-size: 1.1em; /* Slightly larger icons */
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section ul li {
            margin-bottom: 12px; /* Slightly more space between list items */
        }

        .footer-section ul li a {
            color: #ecf0f1; /* Consistent lighter text color */
            transition: color 0.3s ease, transform 0.2s ease; /* Add transform for subtle hover */
            display: inline-block; /* Allows transform on hover */
        }

        .footer-section ul li a:hover {
            color: var(--secondary-color);
            text-decoration: none; /* Remove default underline on hover */
            transform: translateX(5px); /* Slide effect on hover */
        }

        .footer-section.social a {
            display: inline-block;
            color: #ecf0f1; /* Consistent lighter text color */
            font-size: 1.8rem;
            width: 48px; /* Slightly larger social icons */
            height: 48px;
            line-height: 48px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.08); /* More subtle background */
            margin: 0 8px;
            transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease; /* Added box-shadow transition */
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2); /* Initial subtle shadow */
        }

        .footer-section.social a:hover {
            background-color: var(--primary-color);
            color: var(--secondary-color);
            transform: translateY(-7px) scale(1.1); /* More pronounced lift and scale */
            box-shadow: 0 8px 16px rgba(0,0,0,0.4); /* Stronger shadow on hover */
        }

        .footer-bottom {
            text-align: center;
            margin-top: 50px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #aeb6bf; /* Slightly distinct color for copyright */
            font-size: 0.88rem; /* Slightly smaller font for copyright */
        }

        /* Responsive Adjustments */
        @media (max-width: 991px) {
            .checkout-container .row > div {
                margin-bottom: 40px; /* Add space between columns on smaller screens */
            }
            .checkout-order-summary {
                position: relative; /* Disable sticky on smaller screens */
                top: auto;
            }
        }

        @media (max-width: 768px) {
            .footer-content {
                gap: 30px;
            }
            .footer-section {
                flex: 1 1 180px;
                padding: 0 10px;
            }
            .checkout-container {
                padding: 25px;
            }
            .checkout-container h2 {
                font-size: 2rem;
            }
            .checkout-order-summary h3 {
                font-size: 1.6rem;
            }
            .place-order-btn {
                padding: 15px 25px;
                font-size: 1.1rem;
            }
        }

        @media (max-width: 480px) {
            .footer-content {
                flex-direction: column;
                align-items: center;
                gap: 40px;
            }
            .footer-section {
                width: 90%;
                max-width: 300px;
                text-align: center;
                padding: 0;
            }
            .footer-section h3::after {
                left: 50%;
                transform: translateX(-50%);
            }
            .page-title-section h1 {
                font-size: 2rem;
            }
            .checkout-form label {
                font-size: 1rem;
            }
            .checkout-form .form-control {
                padding: 10px 12px;
                font-size: 0.95rem;
            }
            .summary-item .product-info img {
                width: 50px;
                height: 50px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <a href="http://localhost/new%20shoes%20house/index.php">Shoes House</a>
            </div>
            <nav class="navbar">
                <ul>
                    <li><a href="http://localhost/new%20shoes%20house/index.php">Home</a></li>
                    <li class="has-dropdown">
                        <a href="http://localhost/new%20shoes%20house/index.php">Collections</a>
                        
                    </li>
                    <li><a href="#categories-section">Blog</a></li>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
            </nav>
            <div class="nav-icons">
                <a href="#" id="search-icon"><i class="fas fa-search"></i></a>
                <a href="cart.php"><i class="fas fa-shopping-cart"></i><span class="cart-count"><?php echo array_sum($_SESSION['cart'] ?? []); ?></span></a>
             <a href="../wishlist.php"><i class="fas fa-heart"></i></a>
             <!-- ✅ Login/Logout Toggle -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="user-name" style="font-size: 14px; color: #333; margin-left:10px">
                    <?php echo htmlspecialchars($_SESSION['fullname']); ?>
                </span>
                <a href="user_logout.php" class="auth-btn" style="padding: 6px 12px; background: #d9534f; color: #fff; border-radius: 4px; text-decoration: none;">
                    Logout
                </a>
            <?php else: ?>
                <a href="user_login.php" class="auth-btn" style="padding: 6px 12px; background: #0275d8; color: #fff; border-radius: 4px; text-decoration: none;">
                    Login
                </a>
            <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="page-title-section">
        <div class="container">
            <h1>Complete Your Order</h1>
        </div>
    </div>

    <section class="checkout-section">
        <div class="container">
            <div class="checkout-container">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <?= $error_message ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($cart_items_details)): ?>
                    <div class="row g-4"> <div class="col-lg-7 col-md-12">
                            <h2>Shipping & Billing Details</h2>
                            <form action="checkout.php" method="POST" class="checkout-form">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="shipping_name">Full Name</label>
                                            <input type="text" class="form-control" id="shipping_name" name="shipping_name" value="<?= $username ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="user_email">Email</label>
                                            <input type="email" class="form-control" id="user_email" name="user_email" value="<?= $user_email ?>" disabled>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="shipping_address">Street Address</label>
                                    <input type="text" class="form-control" id="shipping_address" name="shipping_address" placeholder="House number, Street name, Area" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="shipping_city">City</label>
                                            <input type="text" class="form-control" id="shipping_city" name="shipping_city" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="shipping_zip">Zip Code</label>
                                            <input type="text" class="form-control" id="shipping_zip" name="shipping_zip" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="shipping_country">Country</label>
                                    <input type="text" class="form-control" id="shipping_country" name="shipping_country" value="India" required>
                                </div>

                                <h2 class="mt-5">Payment Method</h2>
                                <div class="form-group">
                                    <label for="payment_method">Select Payment Option</label>
                                    <select class="form-control" id="payment_method" name="payment_method" required>
                                        <option value="">-- Choose an option --</option>
                                        <option value="cod">Cash on Delivery (COD)</option>
                                        </select>
                                </div>

                                <button type="submit" name="place_order" class="place-order-btn">
                                    Place Order Now <i class="fas fa-arrow-right"></i>
                                </button>
                            </form>
                        </div>
                        <div class="col-lg-5 col-md-12">
                            <div class="checkout-order-summary">
                                <h3>Your Order Summary</h3>
                                <div class="summary-items-list">
                                    <?php foreach ($cart_items_details as $item): ?>
                                        <div class="summary-item">
                                            <div class="product-info">
                                                <img src="<?= $item['image'] ?>" alt="<?= $item['brand_name'] ?>">
                                                <div>
                                                    <h6><?= $item['brand_name'] ?></h6>
                                                    <small>Quantity: <?= $item['quantity'] ?></small>
                                                </div>
                                            </div>
                                            <span class="item-price">₹<?= number_format($item['item_total'], 2) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="summary-total">
                                    <span>Order Total:</span>
                                    <span>₹<?= number_format($total_cart_amount, 2) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-cart-message">
                        <p>It looks like your cart is empty!</p>
                        <a href="../women_collection.php" class="btn">Continue Shopping <i class="fas fa-shopping-basket"></i></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section about">
                    <h3>Shoes House</h3>
                    <p>Your ultimate destination for stepping out in style, comfort, and a whole lot of fun!</p>
                </div>
                <div class="footer-section links">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="#">Men's Fun</a></li>
                        <li><a href="#">Women's Sparkle</a></li>
                        <li><a href="#">Kids' Adventures</a></li>
                        <li><a href="#">Crazy Deals!</a></li>
                        <li><a href="#">Return Policy</a></li>
                    </ul>
                </div>
                <div class="footer-section contact">
                    <h3>Get in Touch!</h3>
                    <p><i class="fas fa-map-marker-alt"></i> Fun Lane, Style City, ShoeLand 12345</p>
                    <p><i class="fas fa-envelope"></i> hello@shoehouse.com</p>
                    <p><i class="fas fa-phone"></i> +1 (HAPPY-FEET)</p>
                </div>
                <div class="footer-section social">
                    <h3>Follow the Fun!</h3>
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-pinterest"></i></a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© 2025 Shoes House. All rights reserved. Designed with Sole & Style!</p>
            </div>
        </div>
    </footer>

    <script src="../js/jquery-3.6.0.js"></script>
    <script src="../bootstrap-5.0.2-dist/js/bootstrap.min.js"></script>
    <script src="../script.js"></script>
</body>
</html>