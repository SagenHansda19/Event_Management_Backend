<?php
session_start(); // Ensure session is started

header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/functions.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include '../config/db.php';

try {
    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    $userId = $_SESSION['user_id'];

    // Fetch events the volunteer has registered for
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id IN (SELECT event_id FROM event_registrations WHERE user_id = ? AND role = 'volunteer')");
    $stmt->execute([$userId]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($events);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>