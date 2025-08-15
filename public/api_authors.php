<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// GET: Fetch all authors or single author
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $author_id = $_GET['id'] ?? null;
    
    if ($author_id) {
        $stmt = $conn->prepare("SELECT * FROM authors WHERE id = ?");
        $stmt->bind_param("i", $author_id);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode($result->fetch_assoc());
    } else {
        $result = $conn->query("SELECT * FROM authors ORDER BY name");
        $authors = [];
        while ($row = $result->fetch_assoc()) {
            $authors[] = $row;
        }
        echo json_encode($authors);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. This API is read-only.'
    ]);
}
