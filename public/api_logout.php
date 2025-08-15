<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Require authentication
$authData = requireApiAuth();

// Get Authorization header
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $auth = $headers['Authorization'];
    if (strpos($auth, 'Bearer ') === 0) {
        $token = substr($auth, 7);

        // Delete the token from database
        $stmt = $conn->prepare("DELETE FROM api_tokens WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
        exit;
    }
}

http_response_code(400);
echo json_encode([
    'success' => false,
    'message' => 'Invalid token'
]);
