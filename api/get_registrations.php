<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/functions.php';

include '../config/db.php';

try {
    // Get the event ID from the query string
    $eventId = $_GET['event_id'] ?? null;
    if (!$eventId) {
        throw new Exception('Event ID is required');
    }

    // Fetch participants
    $stmt = $pdo->prepare("
        SELECT r.id, r.event_id, r.user_id, u.name, u.email, r.role, r.attended
        FROM event_registrations r
        JOIN users u ON r.user_id = u.id
        WHERE r.event_id = ? AND r.role = 'participant'
    ");
    $stmt->execute([$eventId]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch volunteers
    $stmt = $pdo->prepare("
        SELECT r.id, r.event_id, r.user_id, u.name, u.email, r.role, r.attended
        FROM event_registrations r
        JOIN users u ON r.user_id = u.id
        WHERE r.event_id = ? AND r.role = 'volunteer'
    ");
    $stmt->execute([$eventId]);
    $volunteers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the registrations as JSON
    echo json_encode([
        'participants' => $participants,
        'volunteers' => $volunteers,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>