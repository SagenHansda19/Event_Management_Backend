<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/functions.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

include '../config/db.php';

try {
    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    // Get the logged-in user's ID from the session
    $userId = $_SESSION['user_id'];

    // Get the new username from the request body
    $data = json_decode(file_get_contents("php://input"), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    $newUsername = $data['username'] ?? null;

    if (!$newUsername) {
        throw new Exception('Username is required.');
    }

    // Update the username in the database
    $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
    $stmt->execute([$newUsername, $userId]);

    echo json_encode(['status' => 'success', 'message' => 'Username updated successfully!']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>