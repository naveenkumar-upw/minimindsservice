<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get input data from either JSON or POST
$input = [];
$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (strpos($contentType, 'application/json') !== false) {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?: [];
} else {
    $input = $_POST;
}

// For DELETE requests, also check query parameters
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && empty($input)) {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?: [];
    if (empty($input) && isset($_GET['device_token'])) {
        $input['device_token'] = $_GET['device_token'];
    }
}

// Handle device token registration (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($input['device_token'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Device token is required']);
        exit;
    }

    $device_token = $conn->real_escape_string($input['device_token']);
    
    // Validate token format (basic validation)
    if (strlen($device_token) < 10) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid device token format']);
        exit;
    }
    
    // Check if token already exists
    $check = $conn->query("SELECT id FROM device_tokens WHERE device_token = '$device_token'");
    if ($check->num_rows === 0) {
        // Insert new token
        $result = $conn->query("INSERT INTO device_tokens (device_token) VALUES ('$device_token')");
        if ($result) {
            http_response_code(201);
            echo json_encode(['status' => 'success', 'message' => 'Device token registered successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to register device token']);
        }
    } else {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Device token already registered']);
    }
}

// Handle device token unregistration (DELETE)
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (empty($input['device_token'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Device token is required']);
        exit;
    }

    $device_token = $conn->real_escape_string($input['device_token']);
    
    // Validate token format (basic validation)
    if (strlen($device_token) < 10) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid device token format']);
        exit;
    }
    
    // Delete token
    $result = $conn->query("DELETE FROM device_tokens WHERE device_token = '$device_token'");
    if ($result) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Device token unregistered successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to unregister device token']);
    }
}

// Handle GET request for listing device tokens (admin only)
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Require authentication and admin role for viewing tokens
    $authData = requireApiAuth();
    if ($authData['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Admin access required to view device tokens'
        ]);
        exit;
    }

    // Get all device tokens
    $result = $conn->query("SELECT device_token, created_at FROM device_tokens ORDER BY created_at DESC");
    $tokens = [];
    while ($row = $result->fetch_assoc()) {
        $tokens[] = $row;
    }
    
    echo json_encode([
        'status' => 'success',
        'devices' => $tokens
    ]);
}

// Invalid request
else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
}
