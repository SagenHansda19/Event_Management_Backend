<?php
// CORS headers at the very top
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Then your normal headers and code
header("Content-Type: application/json");
require_once __DIR__.'/../config/db.php';
// require_once __DIR__.'/../shared/check_auth.php';  // Keep this commented if you're not using it yet

// Start session if using session-based auth
session_start();

try {
    // Verify user is logged in and is a student
    // if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    //     throw new Exception('Unauthorized access', 403);
    // }

    $stmt = $pdo->prepare("
        SELECT 
            er.id as registration_id,
            er.event_id,
            er.attended,
            e.name as event_name,
            e.date as event_date
        FROM event_registrations er
        JOIN events e ON er.event_id = e.id
        WHERE er.user_id = :user_id
        ORDER BY e.date DESC
    ");
    
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $records
    ]);
    
} catch (Exception $e) {
    http_response_code($e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
}
?>