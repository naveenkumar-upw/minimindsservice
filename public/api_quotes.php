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

// GET: Fetch all quotes or single quote with author details
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $quote_id = $_GET['id'] ?? null;
    
    if ($quote_id) {
        $stmt = $conn->prepare("
            SELECT q.*, a.name as author_name, a.photo as author_photo
            FROM quotes q
            LEFT JOIN authors a ON q.author_id = a.id
            WHERE q.id = ?
        ");
        $stmt->bind_param("i", $quote_id);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode($result->fetch_assoc());
    } else {
        $result = $conn->query("
            SELECT q.*, a.name as author_name, a.photo as author_photo
            FROM quotes q
            LEFT JOIN authors a ON q.author_id = a.id
            ORDER BY q.created_at DESC
        ");
        $quotes = [];
        while ($row = $result->fetch_assoc()) {
            $quotes[] = $row;
        }
        echo json_encode($quotes);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. This API is read-only.'
    ]);
}
