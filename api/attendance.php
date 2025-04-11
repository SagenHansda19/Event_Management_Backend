<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/functions.php';

include '../config/db.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    $id = $data['id'] ?? null;
    $attended = $data['attended'] ?? null;

    if (!$id || $attended === null) {
        throw new Exception('ID and attended status are required');
    }

    $stmt = $pdo->prepare("UPDATE event_registrations SET attended = ? WHERE id = ?");
    $stmt->execute([$attended, $id]);

    echo json_encode(['status' => 'success', 'message' => 'Attendance updated successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>