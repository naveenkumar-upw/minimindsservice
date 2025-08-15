<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Handle CORS
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

// Get JSON POST data
$data = json_decode(file_get_contents('php://input'), true);

// Login endpoint
if (isset($data['action']) && $data['action'] === 'login') {
    if (!isset($data['username']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Username and password required']);
        exit;
    }

    $result = loginUser($data['username'], $data['password']);
    if ($result['success']) {
        // Return token and API key
        echo json_encode([
            'success' => true,
            'token' => $result['token'],
            'user' => [
                'id' => $result['user_id'],
                'username' => $result['username'],
                'role' => $result['role']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
}
// Register endpoint
else if (isset($data['action']) && $data['action'] === 'register') {
    if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    if (strlen($data['password']) < 8) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
        exit;
    }

    $result = registerUser($data['username'], $data['email'], $data['password']);
    if ($result['success']) {
        // Return API key
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'api_key' => $result['api_key']
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
}
// Test authentication endpoint
else if (isset($data['action']) && $data['action'] === 'verify') {
    $authResult = requireApiAuth();
    echo json_encode([
        'success' => true,
        'message' => 'Authentication valid',
        'user' => [
            'id' => $authResult['user_id'],
            'username' => $authResult['username'],
            'role' => $authResult['role']
        ]
    ]);
}
else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
