<?php
// C:\xampp\htdocs\event-management-php\includes\check_auth.php

function check_auth() {
    session_start();
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized - Please login']);
        exit;
    }
    
    // Optional: Verify user exists in database
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid user session']);
        exit;
    }
    
    return true;
}

function check_role($required_role) {
    session_start();
    
    if ($_SESSION['user_role'] !== $required_role) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Insufficient permissions']);
        exit;
    }
}