<?php

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Set CORS headers
    header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    
    // Ensure no content type is set for OPTIONS
    header_remove('Content-Type');
    
    // Immediately return 200 without any output
    http_response_code(200);
    exit(0);
}

// Enable output buffering
ob_start();

// Set regular headers
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// Error handling
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/../../logs/php_errors.log');

// Include DB connection
require_once __DIR__.'/../config/db.php';

try {
    // Verify database connection
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('Database connection failed');
    }

    // Start session securely
    if (session_status() === PHP_SESSION_NONE) {
        if (!session_start()) {
            throw new Exception('Session initialization failed');
        }
    }

    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Authentication required', 401);
    }

    // Validate input
    $eventId = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
    if (!$eventId) {
        throw new Exception('Invalid event ID', 400);
    }

    $userId = $_SESSION['user_id'];

    // Execute deletion
    $stmt = $pdo->prepare("DELETE FROM event_registrations WHERE event_id = ? AND user_id = ?");
    if (!$stmt->execute([$eventId, $userId])) {
        throw new Exception('Database operation failed');
    }
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Registration not found', 404);
    }

    // Successful response
    ob_end_clean();
    echo json_encode([
        'status' => 'success',
        'message' => 'Successfully unregistered',
        'data' => [
            'event_id' => $eventId,
            'user_id' => $userId
        ]
    ]);
    exit;
    
} catch (Exception $e) {
    // Error response
    ob_end_clean();
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'code' => $e->getCode() ?: 500
    ]);
    exit;
}