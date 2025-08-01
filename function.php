<?php
function getTotalProducts($conn) {
    $result = $conn->query("SELECT COUNT(*) as total FROM products");
    if ($result === false) {
        return 0;
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
?>