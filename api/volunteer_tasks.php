<?php
session_start();

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/functions.php';

include '../config/db.php';

try {
    $userId = $_GET['user_id'];

    // Fetch tasks assigned to the volunteer
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ?");
    $stmt->execute([$userId]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($tasks);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>