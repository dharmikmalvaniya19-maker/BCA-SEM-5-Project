<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include "db.php"; // Assumes db.php is in the same 'admin' directory

// DEBUG ONLY: Uncomment to test if session has products
// echo '<pre>'; print_r($_SESSION['cart']); echo '</pre>';


// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart actions (remove, update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update quantity
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $product_id => $quantity) {
            $product_id = intval($product_id);
            $quantity = intval($quantity);
            if ($quantity > 0 && $product_id > 0) {
                $_SESSION['cart'][$product_id] = $quantity;
            } else {
                unset($_SESSION['cart'][$product_id]); // Remove if quantity is 0 or less
            }
        }
        header('Location: cart.php'); // Redirect to avoid form resubmission
        exit;
    }
}

// Remove item from cart
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['id'])) {
    $product_id_to_remove = intval($_GET['id']);
    unset($_SESSION['cart'][$product_id_to_remove]);
    header('Location: cart.php'); // Redirect to clean the URL
    exit;
}

// Fetch product details for items in cart
$cart_items_details = [];
$total_price = 0;

if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    // Create a string of placeholders for the IN clause
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));

    $stmt = $conn->prepare("SELECT id, brand_name, price, image,stocks FROM products WHERE id IN ($placeholders)");
    if ($stmt === false) {
        die("MySQL Prepare Error: " . $conn->error);
    }

    // Dynamically bind parameters
    $types = str_repeat('i', count($product_ids)); // 'i' for integer
    $stmt->bind_param($types, ...$product_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $product_id = $row['id'];
        $quantity = $_SESSION['cart'][$product_id];
        $item_total = $row['price'] * $quantity;
        $total_price += $item_total;

           // Stock check
    if ($quantity > $row['stocks']) {
        $out_of_stock = true;
    }

        // Correct image path handling
        $imagePath = file_exists('../assets/uploads/' . $row['image']) ? '../assets/uploads/' . htmlspecialchars($row['image']) : '../images/no-image.png';

       $cart_items_details[] = [
    'id' => $row['id'],
    'brand_name' => htmlspecialchars($row['brand_name']),
    'price' => $row['price'],
    'image' => $imagePath,
    'quantity' => $quantity,
    'item_total' => $item_total,
    'stocks' => $row['stocks'] // ✅ This must be last OR followed by a comma if more follows
];
    }
    $stmt->close();
}

// Get cart count for header
$cart_count = array_sum($_SESSION['cart'] ?? []);

// Check if user is logged in for header display
$is_logged_in = isset($_SESSION['user_id']);
$username = '';
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT fullname FROM users WHERE id = ?");
    if ($stmt === false) {
        die("MySQL Prepare Error: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $username = htmlspecialchars($row['fullname']);
    }
    $stmt->close();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Shoes House</title>

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
            --gradient-cart: linear-gradient(135deg, #e0f7fa, #e8f5e9); /* Light blue-green gradient */
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

        /* Cart Page Specific Styles */
        .cart-section {
            padding: 50px 0;
            background: var(--gradient-cart); /* Subtle gradient for cart section */
            min-height: calc(100vh - 200px); /* Ensure it takes up enough vertical space */
            display: flex;
            align-items: flex-start; /* Align content to the top */
        }

        .cart-container {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
        }

        .cart-container h2 {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 35px;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }

        .cart-container h2::after {
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

        .table-cart {
            width: 100%;
            border-collapse: separate; /* Allows border-radius on cells */
            border-spacing: 0 10px; /* Space between rows */
            margin-bottom: 30px;
        }

        .table-cart th {
            text-align: left;
            padding: 15px 20px;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
        }

        .table-cart td {
            padding: 15px 20px;
            vertical-align: middle;
            background-color: #fdfdfd; /* Light background for rows */
            border-bottom: 1px solid var(--border-color);
        }

        .table-cart tbody tr:last-child td {
            border-bottom: none;
        }

        .table-cart tbody tr {
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); /* Subtle shadow per row */
            border-radius: 8px;
            overflow: hidden; /* Ensures shadow respects border-radius */
        }

        .cart-item-info {
            display: flex;
            align-items: center;
        }

        .cart-item-info img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .cart-item-info .item-details h6 {
            margin: 0;
            font-size: 1.2rem;
            color: var(--text-dark);
            font-weight: 600;
        }

        .cart-item-info .item-details small {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .cart-quantity input {
            width: 70px;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            text-align: center;
            font-size: 1.05rem;
            color: var(--text-dark);
            transition: border-color 0.3s ease;
        }

        .cart-quantity input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(103, 58, 183, 0.15);
        }

        .cart-price span {
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .cart-actions .btn-remove {
            background-color: var(--accent-color);
            color: #fff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .cart-actions .btn-remove:hover {
            background-color: #d32f2f;
            transform: scale(1.1);
        }

        .cart-actions .btn-remove i {
            margin-right: 0; /* Override default button icon margin */
        }

        .cart-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
            gap: 20px; /* Space between buttons */
        }

        .btn-update {
            background-color: #007bff; /* Blue for update */
            box-shadow: 0 6px 12px rgba(0, 123, 255, 0.2);
        }
        .btn-update:hover {
            background-color: #0056b3;
            box-shadow: 0 8px 16px rgba(0, 123, 255, 0.3);
        }

        .btn-secondary {
            background-color: #6c757d; /* Gray for continue shopping */
            box-shadow: 0 6px 12px rgba(108, 117, 125, 0.2);
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            box-shadow: 0 8px 16px rgba(108, 117, 125, 0.3);
        }

        .cart-summary-card {
            background-color: #f0f4f8; /* A calming light blue-gray */
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-top: 50px;
        }

        .cart-summary-card h4 {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 25px;
            text-align: center;
            padding-bottom: 10px;
            position: relative;
        }

        .cart-summary-card h4::after {
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

        .cart-summary-card .summary-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px dashed #cfd8dc;
        }

        .cart-summary-card .summary-line:last-of-type {
            border-bottom: none;
            margin-bottom: 20px;
        }

        .cart-summary-card .summary-line h5 {
            margin: 0;
            font-size: 1.3rem;
            color: var(--text-dark);
        }

        .cart-summary-card .summary-line span {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--accent-color);
        }

        .cart-summary-card p.small {
            font-size: 0.9rem;
            color: var(--text-light);
            text-align: center;
            margin-top: 15px;
        }

        .btn-checkout {
            background-color: #28a745; /* Green for action */
            box-shadow: 0 6px 15px rgba(40, 167, 69, 0.3);
            font-size: 1.3rem;
            padding: 18px 35px;
        }

        .btn-checkout:hover {
            background-color: #218838;
            box-shadow: 0 9px 20px rgba(40, 167, 69, 0.5);
        }

        .empty-cart-message {
            text-align: center;
            padding: 80px 0;
            font-size: 1.4rem;
            color: var(--text-light);
            background-color: #fdfdfd;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-top: 50px;
        }

        .empty-cart-message p {
            margin-bottom: 30px;
            font-size: 1.6rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .empty-cart-message a {
            color: #fff;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 30px;
            background-color: var(--secondary-color);
            border-radius: 50px;
            box-shadow: 0 6px 12px rgba(255, 193, 7, 0.3);
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
        }

        .empty-cart-message a:hover {
            background-color: #e0a800;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 16px rgba(255, 193, 7, 0.5);
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
            .table-cart thead {
                display: none; /* Hide table headers on small screens */
            }

            .table-cart, .table-cart tbody, .table-cart tr, .table-cart td {
                display: block; /* Make table elements behave like blocks */
                width: 100%;
            }

            .table-cart tr {
                margin-bottom: 20px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.08);
                border-radius: 12px;
                overflow: hidden;
            }

            .table-cart td {
                text-align: right;
                padding-left: 50%; /* Make space for pseudo-element label */
                position: relative;
                border: none;
                border-bottom: 1px solid var(--border-color); /* Add border to individual cells */
            }

            .table-cart td::before {
                content: attr(data-label); /* Use data-label for content */
                position: absolute;
                left: 15px;
                width: calc(50% - 30px);
                text-align: left;
                font-weight: 700;
                color: var(--primary-color);
            }

            .table-cart td:last-child {
                border-bottom: none;
            }

            .cart-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .cart-controls .btn {
                width: 100%;
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
            .cart-container {
                padding: 25px;
            }
            .cart-container h2 {
                font-size: 2rem;
            }
            .cart-summary-card h4 {
                font-size: 1.6rem;
            }
            .btn-checkout {
                padding: 15px 25px;
                font-size: 1.1rem;
            }
            .empty-cart-message {
                padding: 50px 0;
            }
            .empty-cart-message p {
                font-size: 1.2rem;
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
            .cart-item-info img {
                width: 60px;
                height: 60px;
            }
            .cart-item-info .item-details h6 {
                font-size: 1rem;
            }
            .cart-quantity input {
                width: 60px;
                font-size: 0.95rem;
            }
        }
     /* Dropdown container */
   .has-dropdown {
    position: relative;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    background-color: white;
    display: none;
    min-width: 150px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    z-index: 999;
}

.has-dropdown:hover .dropdown-menu {
    display: block;
}

.dropdown-menu a {
    display: block;
    padding: 10px 15px;
    color: black;
    text-decoration: none;
}

.dropdown-menu a:hover {
    background-color: #18d3e0ff;
}



    </style>
</head>
<body>
    <header class="header">
    <div class="container">
        <div class="logo">
            <a href="index.php">Shoes House</a>
        </div>

        <nav class="navbar">
            <ul>
                <li><a href="http://localhost/new%20shoes%20house/index.php" >Home</a></li>
                 <li class="has-dropdown">
                 <a href="#" >Collections <i style="font-size: 0.8em; margin-left: 5px;"></i></a>
                 <div class="dropdown-menu">
                <a href="men_collection.php">men </a>
                <a href="women_collection.php">Women </a>
                <a href="kids_collection.php">kids </a>
                
    </div>
</li>
                <li><a href="#categories-section">Blog</a></li>
                <li><a href="#">About Us</a></li>
                <li><a href="Contact Us.php">Contact</a></li>
            </ul>
        </nav>

        <div class="nav-icons" style="display: flex; align-items: center; gap: 15px;">
            <a href="#" id="search-icon"><i class="fas fa-search"></i></a>
            <a href="admin/cart.php">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count">0</span>
            </a>

            <!-- ✅ Login/Logout Toggle -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="user-name" style="font-size: 14px; color: #333;">
                    Hi, <?php echo htmlspecialchars($_SESSION['fullname']); ?>
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
            <h1>Your Shopping Cart</h1>
        </div>
    </div>

    <?php $out_of_stock = false; foreach ($cart_items_details as $item) { if ($item['quantity'] > $item['stocks']) { $out_of_stock = true; break; } } ?>

    <section class="cart-section">
        <div class="container">
            <div class="cart-container">
                <?php if (!empty($cart_items_details)): ?>
                    <h2>Items in Your Cart</h2>
                    <form action="cart.php" method="POST">
                        <div class="table-responsive">
                            <table class="table table-cart">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>

    <?php foreach ($cart_items_details as $item): ?>
    <tr>
        <td data-label="Product">
            <div class="cart-item-info">
                <img src="<?= $item['image'] ?>" alt="<?= $item['brand_name'] ?>">
                <div class="item-details">
                    <h6><?= $item['brand_name'] ?></h6>
                    <small>ID: <?= $item['id'] ?></small>
                    <?php if (isset($item['stocks'])): ?>
                        <div class="stocks-info text-muted small">
                            <strong>stocks:</strong> <?= $item['stocks'] ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </td>
        <td data-label="Price" class="cart-price">
            <span>₹<?= number_format($item['price'], 2) ?></span>
        </td>
        <td data-label="Quantity" class="cart-quantity">
<td data-label="Quantity" class="cart-quantity">
    <input 
    type="number" 
    name="quantities[<?= $item['id'] ?>]" 
    value="<?= $item['quantity'] ?>" 
    min="1" 
    class="form-control quantity-input"
    data-stock="<?= $item['stocks'] ?>" 
>
    <?php if ($item['quantity'] > $item['stocks']): ?>
        <div class="text-danger small mt-1">
            Only <?= $item['stocks'] ?> in stocks!
        </div>
    <?php endif; ?>
</td>
        </td>
        <td data-label="Total" class="cart-price">
            <span>₹<?= number_format($item['item_total'], 2) ?></span>
        </td>
        <td data-label="Action" class="cart-actions">
            <a href="cart.php?action=remove&id=<?= $item['id'] ?>" class="btn btn-remove" title="Remove Item">
                <i class="fas fa-trash-alt"></i>
            </a>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>

                            </table>
                        </div>
                        
                        <div class="cart-controls">
                            <a href="http://localhost/new%20shoes%20house/index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i>Continue Shopping
                            </a>
                            <button type="submit" name="update_cart" class="btn btn-update">
                                <i class="fas fa-sync-alt"></i>Update Cart
                            </button>
                        </div>
                    </form>

                    <div class="row">
                        <div class="col-md-6 offset-md-6 col-lg-5 offset-lg-7">
                            <div class="cart-summary-card">
                                <h4>Cart Summary</h4>
                                <div class="summary-line">
                                    <h5>Subtotal</h5>
                                    <span>₹<?= number_format($total_price, 2) ?></span>
                                </div>
                                <div class="summary-line">
                                    <h5>Shipping</h5>
                                    <span>Calculated at checkout</span>
                                </div>
                                <div class="summary-line">
                                    <h5>Grand Total</h5>
                                    <span>₹<?= number_format($total_price, 2) ?></span>
                                </div>
                                <p class="small">Taxes will be calculated at checkout based on your shipping address.</p>
                                <div class="d-grid mt-4">
    <?php if ($out_of_stock): ?>
        <button class="btn btn-checkout disabled" disabled style="background-color: #ccc; cursor: not-allowed;">
            Cannot Checkout – Fix Stock Issues <i class="fas fa-exclamation-circle"></i>
        </button>
        <p class="text-danger mt-2 small">
            One or more products exceed available stock. Please reduce quantities.
        </p>
    <?php else: ?>
        <a href="checkout.php" class="btn btn-checkout">
            Proceed to Checkout<i class="fas fa-arrow-right"></i>
        </a>
    <?php endif; ?>
</div>

                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-cart-message">
                        <p>Your shopping cart is feeling a little lonely!</p>
                        <a href="../women_collection.php">
                            Start Shopping Now <i class="fas fa-shopping-basket"></i>
                        </a>
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
    <script>
      
document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector('form[action="cart.php"]');

    form.addEventListener("submit", function (e) {
        let isValid = true;
        let messages = [];

        document.querySelectorAll(".quantity-input").forEach(function (input) {
            const quantity = parseInt(input.value);
            const stock = parseInt(input.dataset.stocks);

            if (quantity > stocks) {
                isValid = false;
                messages.push(`Item ID ${input.name.match(/\d+/)}: only ${stocks} in stocks.`);
            }
        });

        if (!isValid) {
            e.preventDefault(); // stop form submit
            alert("⚠️ Some quantities exceed stocks:\n\n" + messages.join("\n"));
        }
    });
});
</script>

       
</body>
</html>