<?php
// Add these at the very top, before any output
ini_set('display_errors', 0); // Turn off HTML error display
ini_set('log_errors', 1);     // Log errors to file instead
error_reporting(E_ALL);       // Report all errors

header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Start session after headers
session_start();

// Set default faculty ID for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

require_once __DIR__.'/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    die(json_encode(['status' => 'error', 'message' => 'Invalid JSON input']));
}

try {
    // Validate input
    if (empty($input['rectification_id']) || !in_array($input['status'], ['approved', 'rejected'])) {
        throw new Exception('Invalid request data', 400);
    }

    $pdo->beginTransaction();
    
    // Update rectification
    $stmt = $pdo->prepare("
        UPDATE rectifications 
        SET status = :status,
            resolved_at = NOW(),
            resolved_by = :faculty_id
        WHERE id = :id AND status = 'pending'
    ");
    
    $stmt->execute([
        ':status' => $input['status'],
        ':faculty_id' => $_SESSION['user_id'],
        ':id' => $input['rectification_id']
    ]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('No pending rectification found with that ID', 404);
    }

    // If approved, update attendance
    if ($input['status'] === 'approved') {
        $updateAttendance = $pdo->prepare("
            UPDATE event_registrations er
            JOIN rectifications r ON er.id = r.registration_id
            SET er.attended = 1
            WHERE r.id = :rectification_id
        ");
        $updateAttendance->execute([':rectification_id' => $input['rectification_id']]);
    }

    $pdo->commit();
    
    // Clean JSON output
    die(json_encode([
        'status' => 'success',
        'message' => 'Status updated',
        'faculty_id' => $_SESSION['user_id']
    ]));

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code($e->getCode() ?: 500);
    die(json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]));
}