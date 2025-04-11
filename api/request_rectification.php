<?php
// Add at the very top
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

session_start();

// For testing - set default student ID if not in session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 2; // Set a default student ID for testing
}

require_once __DIR__.'/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
     // Get JSON input
     $input = json_decode(file_get_contents('php://input'), true);
     if (json_last_error() !== JSON_ERROR_NONE) {
         throw new Exception('Invalid JSON input', 400);
     }
 
     // Validate required fields
     $required = ['registration_id', 'event_id', 'reason'];
     foreach ($required as $field) {
         if (empty($input[$field])) {
             throw new Exception("Missing required field: $field", 400);
         }
     }
 
     // Verify the registration belongs to the student
     $verifyStmt = $pdo->prepare("
         SELECT 1 FROM event_registrations 
         WHERE id = ? AND user_id = ?
     ");
     $verifyStmt->execute([$input['registration_id'], $_SESSION['user_id']]);
     
     if (!$verifyStmt->fetch()) {
         throw new Exception('Registration not found or does not belong to student', 400);
     }
 
     // Create rectification record (without evidence_path)
     $stmt = $pdo->prepare("
         INSERT INTO rectifications 
         (registration_id, event_id, user_id, reason, status) 
         VALUES (?, ?, ?, ?, 'pending')
     ");
     
     $success = $stmt->execute([
         $input['registration_id'],
         $input['event_id'],
         $_SESSION['user_id'],
         $input['reason']
     ]);
 
     if (!$success) {
         throw new Exception('Failed to create rectification record', 500);
     }
     
     http_response_code(201);
     echo json_encode([
         'status' => 'success',
         'message' => 'Rectification request submitted',
         'rectification_id' => $pdo->lastInsertId()
     ]);
     
 } catch (Exception $e) {
     http_response_code($e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
     echo json_encode([
         'status' => 'error',
         'message' => $e->getMessage()
     ]);
 }
 ?>