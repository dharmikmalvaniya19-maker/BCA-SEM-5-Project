```php
<?php
session_start();
$connect_file = "db.php"; // File is in root directory

if (!file_exists($connect_file)) {
    die("Error: db.php not found in the root directory.");
}
include $connect_file;

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: users.php?error=Invalid user ID");
    exit();
}

$user_id = $_GET['id'];
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
if ($stmt === false) {
    header("Location: users.php?error=Failed to prepare delete query");
    exit();
}
$stmt->bind_param("i", $user_id);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    header("Location: users.php?success=User deleted successfully");
} else {
    header("Location: users.php?error=Failed to delete user");
}
exit();
?>
```