<?php
// Handle OPTIONS request first
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200);
    exit;
}

// Regular request handling
ob_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");

require_once __DIR__.'/../config/db.php';
session_start();

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized', 401);
    }

    $input = json_decode(file_get_contents("php://input"), true);
    
    if (empty($input['event_id']) || empty($input['role'])) {
        throw new Exception('Event ID and role are required', 400);
    }
    
    if (!in_array($input['role'], ['participant', 'volunteer'])) {
        throw new Exception('Invalid participation role', 400);
    }

    // Check existing registration
    $stmt = $pdo->prepare("SELECT id FROM event_registrations 
                          WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$input['event_id'], $_SESSION['user_id']]);
    
    if ($stmt->fetch()) {
        throw new Exception('Already registered for this event', 400);
    }

    // Create registration
    $stmt = $pdo->prepare("INSERT INTO event_registrations 
                          (event_id, user_id, role, status) 
                          VALUES (?, ?, ?, 'pending')");
    $stmt->execute([
        $input['event_id'],
        $_SESSION['user_id'],
        $input['role']
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Registration successful'
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
ob_end_flush();