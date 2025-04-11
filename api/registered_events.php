<?php
ob_start();

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");

require_once __DIR__.'/../config/db.php';
session_start();

try {
    if (empty($_SESSION['user_id'])) {
        throw new Exception('Unauthorized', 401);
    }

    // Debug: Log the user ID being queried
    error_log("Fetching events for user: ".$_SESSION['user_id']);

    $stmt = $pdo->prepare("
        SELECT e.id, e.name, e.date, e.location, e.description, r.role 
        FROM events e
        JOIN event_registrations r ON e.id = r.event_id
        WHERE r.user_id = ?
        ORDER BY e.date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: Log the query results
    error_log("Found ".count($events)." events");

    echo json_encode([
        'status' => 'success',
        'data' => $events,
        'debug' => [
            'user_id' => $_SESSION['user_id'],
            'event_count' => count($events)
        ]
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug' => $_SESSION
    ]);
}

ob_end_flush();
?>