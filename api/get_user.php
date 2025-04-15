<?php
ob_start();

// Set headers (single set of each)
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS"); // Only allow GET and OPTIONS
header("Access-Control-Allow-Headers: Content-Type"); // Only allow Content-Type header
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Disable error display but log them
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Database connection (use require_once to prevent duplicates)
require_once __DIR__.'/../config/db.php';

session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'None' // Or 'None' for development if needed
]);

// Start session AFTER headers
session_start();

try {
    // Check if user is logged in
    // if (!isset($_SESSION['user_id'])) {
    //     http_response_code(401);
    //     echo json_encode(['status' => 'error', 'message' => 'Unauthorized - Please login']);
    //     exit;
    // }
    
    // $_SESSION['user_id'] = 4;

    // if ($_SERVER['SERVER_NAME'] === 'localhost') {
    //     $_SESSION['user_id'] = 4; // Simulate user for local development
    // } else {
    //     // Production authentication check
    //     if (!isset($_SESSION['user_id'])) {
    //         http_response_code(401);
    //         echo json_encode(['status' => 'error', 'message' => 'Unauthorized - Please login']);
    //         exit;
    //     }
    // }

    // Fetch user details
    $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }

    // Successful response
    echo json_encode([
        'status' => 'success',
        'user' => $user
    ]);

} catch (PDOException $e) {
    // Database-specific errors
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error',
        'code' => $e->getCode()
    ]);
    if (error_reporting() && E_ALL) {  // Include more details in development if errors are displayed
        $error['details'] = $e->getMessage();
    }
    echo json_encode($error);
    error_log('DB Error in get_user.php: ' . $e->getMessage());
} catch (Exception $e) {
    // General errors
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    if (error_reporting() && E_ALL) {
        $error['details'] = $e->getMessage();
    }
    echo json_encode($error);
}

// Clean output buffer
ob_end_flush();
?>