<?php
// Start output buffering at the VERY TOP (no whitespace before)
ob_start();

// Set headers (single set of each)
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Disable error display but log them
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Database connection (use require_once to prevent duplicates)
require_once __DIR__.'/../config/db.php';

// Start session AFTER headers
session_start();

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized - Please login']);
        exit;
    }

    // Fetch user details
    $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }

    // Successful response
    echo json_encode([
        'status' => 'success',
        'user' => $user
    ]);

} catch (PDOException $e) {
    // Database-specific errors
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error',
        'code' => $e->getCode()
    ]);
    error_log('DB Error in get_user.php: ' . $e->getMessage());
} catch (Exception $e) {
    // General errors
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}

// Clean output buffer
ob_end_flush();
?>