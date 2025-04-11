<?php
// Start output buffering at the VERY TOP (no whitespace before)
ob_start();

// Set headers before any output
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://127.0.0.1:5501"); // Match your frontend origin
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Disable error display but log them
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Start session after headers
session_start();

// Include database configuration
require_once __DIR__.'/../config/db.php';

try {
    // Get and validate input
    $input = file_get_contents("php://input");
    if (empty($input)) {
        throw new Exception('No input data received', 400);
    }
    
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg(), 400);
    }

    // Validate required fields
    if (empty($data['username']) || empty($data['password'])) {
        throw new Exception('Username and password are required', 400);
    }

    // Query database
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->execute([$data['username']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($data['password'], $user['password'])) {
        throw new Exception('Invalid username or password', 401);
    }

    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];

    // Return success response
    echo json_encode([
        'success' => true,
        'role' => $user['role'],
        'user_id' => $user['id']
    ]);

} catch (Exception $e) {
    // Clean any output buffer
    ob_end_clean();
    
    // Set appropriate HTTP status
    http_response_code($e->getCode() ?: 500);
    
    // Return JSON error
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    
    // Log the error
    error_log('Auth Error: ' . $e->getMessage());
}

// Flush output buffer
ob_end_flush();
?>