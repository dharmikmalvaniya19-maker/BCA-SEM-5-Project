<?php
include "db.php";

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Delete the product image file
    $imageQuery = $conn->query("SELECT image FROM products WHERE id=$id");
    if ($imageQuery->num_rows > 0) {
        $imageRow = $imageQuery->fetch_assoc();
        $imagePath = "assets/uploads/" . $imageRow['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    // Delete from database
    $sql = "DELETE FROM products WHERE id=$id";
    if ($conn->query($sql)) {
        header("Location: products.php?deleted=1");
        exit();
    } else {
        echo "Error deleting product: " . $conn->error;
    }
} else {
    echo "No ID provided.";
}
?>