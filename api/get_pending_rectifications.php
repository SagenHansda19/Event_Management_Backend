<?php
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


require_once __DIR__.'/../config/db.php';
// require_once __DIR__.'/../includes/check_auth.php';

// check_auth();
// check_role('faculty');

// Verify faculty access
// if ($_SESSION['user_role'] !== 'faculty') {
//     http_response_code(403);
//     echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
//     exit;
// }

try {
    $stmt = $pdo->prepare("
        SELECT r.*, e.name as event_name, u.name as student_name
        FROM rectifications r
        JOIN events e ON r.event_id = e.id
        JOIN users u ON r.user_id = u.id
        WHERE r.status = 'pending'
        ORDER BY r.created_at DESC
    ");
    $stmt->execute();
    
    echo json_encode([
        'status' => 'success',
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>