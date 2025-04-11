<?php
ob_start();

// Set headers
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Disable error display but log them
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Include database config
require_once __DIR__.'/../config/db.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Start session AFTER headers
session_start();

try {
    // Get and validate input
    $input = json_decode(file_get_contents("php://input"), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input', 400);
    }

    if (empty($input['username']) || empty($input['password'])) {
        throw new Exception('Username and password are required', 400);
    }

    // Fetch user
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->execute([$input['username']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($input['password'], $user['password'])) {
        http_response_code(401);
        throw new Exception('Invalid credentials', 401);
    }

    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Set session data
    $_SESSION = [
        'user_id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];

    // Return success
    echo json_encode([
        'status' => 'success',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ]
    ]);

} catch (Exception $e) {
    // Clear session on error
    session_unset();
    session_destroy();
    
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    
    error_log('Login Error: ' . $e->getMessage());
}

ob_end_flush();
?>