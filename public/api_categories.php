<?php
header('Content-Type: application/json');
include '../includes/db.php';

$result = $conn->query("SELECT * FROM categories");
$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
echo json_encode(['categories' => $categories]);
?>
