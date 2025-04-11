<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__.'/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input', 400);
    }

    if (!is_array($input)) {
        throw new Exception('Input should be an array', 400);
    }

    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("
        UPDATE event_registrations 
        SET attended = ? 
        WHERE id = ?
    ");
    
    foreach ($input as $record) {
        if (!isset($record['id']) || !isset($record['attended'])) {
            throw new Exception('Invalid attendance record', 400);
        }
        
        $stmt->execute([
            (int)$record['attended'],
            (int)$record['id']
        ]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Attendance updated successfully'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code($e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    
    error_log('Attendance Update Error: ' . $e->getMessage());
}
?>