<?php

// session_start(); // Add this at the top
$servername = "localhost";
$username = "root";
$password = ""; // or your MySQL password
$database = "shoes_website";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>