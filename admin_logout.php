<?php
session_start();
session_unset();
session_destroy();

// Redirect to login page
header("Location:http://localhost/new%20shoes%20house/admin/dashboard.php");
exit();
?>
