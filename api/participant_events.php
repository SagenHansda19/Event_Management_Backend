<?php
// Start output buffering at the VERY TOP
ob_start();

// Set headers
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

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
    // Verify session
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized - Please login', 401);
    }

    // Security check - verify session consistency
    if ($_SESSION['ip'] !== $_SERVER['REMOTE_ADDR'] || 
        $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        throw new Exception('Session security violation', 401);
    }

    // Fetch events
    $stmt = $pdo->prepare("
        SELECT e.id, e.name, e.date, e.location, er.status
        FROM events e
        JOIN event_registrations er ON e.id = er.event_id
        WHERE er.user_id = ? AND er.role = 'participant'
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $events ?: []
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    
    error_log('Participant Events Error: ' . $e->getMessage());
}

ob_end_flush();
?>