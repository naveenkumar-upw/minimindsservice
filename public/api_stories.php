<?php
header('Content-Type: application/json');
include '../includes/db.php';

$sql = "SELECT stories.*, categories.name as category_name FROM stories JOIN categories ON stories.category_id = categories.id";
$result = $conn->query($sql);
$stories = [];
while ($row = $result->fetch_assoc()) {
    // Convert images string to array
    if (!empty($row['images'])) {
        $row['images'] = explode(',', $row['images']);
    } else {
        $row['images'] = [];
    }
    $stories[] = $row;
}
echo json_encode(['stories' => $stories]);
?>
