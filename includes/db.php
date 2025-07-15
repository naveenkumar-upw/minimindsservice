<?php
// Database connection settings
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'miniminds';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
?>
