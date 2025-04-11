<?php
// includes/functions.php

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function jsonResponse($status, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function checkAuthentication() {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse('error', 'Authentication required', [], 401);
    }
}