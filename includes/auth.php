<?php
require_once 'db.php';

// JWT Secret Key - In production, this should be stored in a secure environment variable
define('JWT_SECRET', 'your-secret-key-here');
define('JWT_EXPIRY', 3600); // 1 hour

// Generate a random string for API keys and tokens
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length));
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Generate JWT Token
function generateJWT($userId, $username, $role) {
    $issuedAt = time();
    $expire = $issuedAt + JWT_EXPIRY;

    $payload = [
        'iat' => $issuedAt,
        'exp' => $expire,
        'user_id' => $userId,
        'username' => $username,
        'role' => $role
    ];

    $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $payload = base64_encode(json_encode($payload));
    $signature = hash_hmac('sha256', "$header.$payload", JWT_SECRET, true);
    $signature = base64_encode($signature);

    return "$header.$payload.$signature";
}

// Validate JWT Token
function validateJWT($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }

    list($header, $payload, $signature) = $parts;
    
    $validSignature = base64_encode(
        hash_hmac('sha256', "$header.$payload", JWT_SECRET, true)
    );

    if ($signature !== $validSignature) {
        return false;
    }

    $payload = json_decode(base64_decode($payload), true);
    if (!$payload || $payload['exp'] < time()) {
        return false;
    }

    return $payload;
}

// Register new user
function registerUser($username, $email, $password) {
    global $conn;
    
    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Username or email already exists'];
    }

    $hashedPassword = hashPassword($password);
    $apiKey = generateRandomString();

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, api_key) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashedPassword, $apiKey);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'user_id' => $stmt->insert_id,
            'api_key' => $apiKey,
            'message' => 'User registered successfully'
        ];
    }

    return ['success' => false, 'message' => 'Registration failed'];
}

// Login user
function loginUser($username, $password) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (verifyPassword($password, $user['password'])) {
            // Update last login
            $updateStmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->bind_param("i", $user['id']);
            $updateStmt->execute();

            // Generate JWT token
            $token = generateJWT($user['id'], $user['username'], $user['role']);

            // Store token in database
            $expiresAt = date('Y-m-d H:i:s', time() + JWT_EXPIRY);
            $tokenStmt = $conn->prepare("INSERT INTO api_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $tokenStmt->bind_param("iss", $user['id'], $token, $expiresAt);
            $tokenStmt->execute();

            return [
                'success' => true,
                'user_id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'token' => $token
            ];
        }
    }
    
    return ['success' => false, 'message' => 'Invalid credentials'];
}

// Validate API Key
function validateApiKey($apiKey) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE api_key = ?");
    $stmt->bind_param("s", $apiKey);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        return [
            'success' => true,
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];
    }
    
    return ['success' => false, 'message' => 'Invalid API key'];
}

// Check if user is authenticated (for web pages)
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

// Get current user (for web pages)
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }

    global $conn;
    $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// API Authentication Middleware
function requireApiAuth() {
    $headers = getallheaders();
    $token = null;

    // Check for Bearer token
    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (strpos($auth, 'Bearer ') === 0) {
            $token = substr($auth, 7);
            $payload = validateJWT($token);
            if ($payload) {
                return $payload;
            }
        }
    }

    // Check for API key
    if (isset($headers['X-API-Key'])) {
        $result = validateApiKey($headers['X-API-Key']);
        if ($result['success']) {
            return $result;
        }
    }

    // No valid authentication found
    header('HTTP/1.0 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Web Authentication Middleware
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
    return getCurrentUser();
}

// Super Admin Middleware
function requireSuperAdmin() {
    $user = requireAuth();
    if ($user['role'] !== 'super_admin') {
        header('Location: /public/index.php');
        exit;
    }
    return $user;
}

// Check if user is super admin
function isSuperAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'super_admin';
}

// Update user role
function updateUserRole($userId, $newRole) {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $newRole, $userId);
    return $stmt->execute();
}

// Reset user password (for super admin)
function resetUserPassword($userId, $newPassword) {
    global $conn;
    $hashedPassword = hashPassword($newPassword);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashedPassword, $userId);
    return $stmt->execute();
}

// Delete user (for super admin)
function deleteUser($userId) {
    global $conn;
    
    // Don't allow deleting the last super admin
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'super_admin'");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] <= 1) {
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user && $user['role'] === 'super_admin') {
            return false; // Cannot delete the last super admin
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    return $stmt->execute();
}

// Get all users (for super admin)
function getAllUsers() {
    global $conn;
    $stmt = $conn->prepare("SELECT id, username, email, role, created_at, last_login FROM users ORDER BY created_at DESC");
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Initialize session securely
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session parameters
        $params = session_get_cookie_params();
        session_set_cookie_params(
            $params['lifetime'],
            $params['path'], 
            $params['domain'],
            true, // secure
            true  // httponly
        );
        session_start();
    }
}
