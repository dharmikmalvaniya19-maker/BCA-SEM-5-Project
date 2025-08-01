<?php
session_start();
include "db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_ids'])) {
    $product_ids = $_POST['product_ids'];
    
    // Sanitize and validate product IDs
    $product_ids = array_map('intval', $product_ids);
    $product_ids = array_filter($product_ids); // Remove any invalid IDs

    if (!empty($product_ids)) {
        // Prepare a safe query to delete multiple products
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $stmt = $conn->prepare("DELETE FROM products WHERE id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($product_ids)), ...$product_ids);
        
        if ($stmt->execute()) {
            // Optionally, delete associated images from the server
            foreach ($product_ids as $id) {
                $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $imagePath = '../assets/uploads/' . $row['image'];
                    if ($row['image'] && file_exists($imagePath)) {
                        unlink($imagePath); // Delete the image file
                    }
                }
            }
            $_SESSION['notification'] = ['message' => 'Selected products deleted successfully', 'time' => date('Y-m-d H:i'), 'type' => 'success'];
        } else {
            $_SESSION['notification'] = ['message' => 'Failed to delete products', 'time' => date('Y-m-d H:i'), 'type' => 'error'];
        }
        $stmt->close();
    } else {
        $_SESSION['notification'] = ['message' => 'No products selected for deletion', 'time' => date('Y-m-d H:i'), 'type' => 'warning'];
    }
}

header("Location: products.php");
exit();
?>