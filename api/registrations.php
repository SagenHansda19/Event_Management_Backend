<?php
// Start output buffering at the VERY TOP (no whitespace before)
ob_start();

// Set headers (single set, no duplicates)
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Disable error display but log them
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Use require_once (no duplicate includes)
require_once __DIR__.'/../config/db.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Validate event_id
    if (!isset($_GET['event_id'])) {
        throw new Exception('Event ID is required', 400);
    }

    $eventId = (int)$_GET['event_id'];
    if ($eventId <= 0) {
        throw new Exception('Invalid Event ID', 400);
    }

    // Fetch participants
    $stmt = $pdo->prepare("
        SELECT r.id, r.event_id, r.user_id, u.username as name, u.email, r.role, r.attended
        FROM event_registrations r
        JOIN users u ON r.user_id = u.id
        WHERE r.event_id = ? AND r.role = 'participant'
    ");
    $stmt->execute([$eventId]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch volunteers
    $stmt = $pdo->prepare("
        SELECT r.id, r.event_id, r.user_id, u.username as name, u.email, r.role, r.attended
        FROM event_registrations r
        JOIN users u ON r.user_id = u.id
        WHERE r.event_id = ? AND r.role = 'volunteer'
    ");
    $stmt->execute([$eventId]);
    $volunteers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return response
    echo json_encode([
        'status' => 'success',
        'participants' => $participants ?: [],
        'volunteers' => $volunteers ?: []
    ]);

} catch (Exception $e) {
    // Clean output buffer
    ob_end_clean();
    
    // Set appropriate HTTP status
    $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($code);
    
    // Return JSON error
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    
    // Log error
    error_log('Registrations Error: ' . $e->getMessage());
}

// Flush output buffer
ob_end_flush();
?>