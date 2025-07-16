<?php
header('Content-Type: application/json');
include '../includes/db.php';

// Get the HTTP method and request body
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Handle device token registration (POST)
if ($method === 'POST' && isset($input['device_token'])) {
    $device_token = $conn->real_escape_string($input['device_token']);
    
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
else if ($method === 'DELETE' && isset($input['device_token'])) {
    $device_token = $conn->real_escape_string($input['device_token']);
    
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

// Invalid request
else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
