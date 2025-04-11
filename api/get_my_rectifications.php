<?php
// MUST be at the very top
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Start session BEFORE any output
session_start();

// Error reporting (disable in production)
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__.'/../config/db.php';
// require_once __DIR__.'/../shared/check_auth.php';

try {
    // Verify user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized - Please login', 401);
    }

    // Get status filter if provided
    $statusFilter = $_GET['status'] ?? null;
    
    // Build query
    $query = "SELECT r.*, e.name as event_name 
              FROM rectifications r
              JOIN events e ON r.event_id = e.id
              WHERE r.user_id = :user_id";
    
    $params = [':user_id' => $_SESSION['user_id']];
    
    if ($statusFilter && in_array($statusFilter, ['pending', 'approved', 'rejected'])) {
        $query .= " AND r.status = :status";
        $params[':status'] = $statusFilter;
    }
    
    $query .= " ORDER BY r.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    $rectifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $rectifications
    ]);
    exit;
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred'
    ]);
    exit;
} catch (Exception $e) {
    http_response_code($e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}
?>