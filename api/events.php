<?php
// Start output buffering at the VERY TOP (no whitespace before)
ob_start();

// Set headers - single set of each (no duplicates)
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Disable error display but log them
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Use require_once (no need for duplicate includes)
require_once __DIR__.'/../config/db.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // GET - List all events
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $query = "SELECT id, name, date, location, description FROM events";
        
        // Add date filtering if requested
        if (isset($_GET['filter'])) {
            $now = date('Y-m-d H:i:s');
            if ($_GET['filter'] === 'upcoming') {
                $query .= " WHERE date > '$now'";
            } elseif ($_GET['filter'] === 'past') {
                $query .= " WHERE date <= '$now'";
            }
        }
        
        $query .= " ORDER BY date";
        $stmt = $pdo->query($query);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($events ?: []);
        exit;
    }

    // POST - Create new event
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON input', 400);
        }

        // Validate required fields
        if (empty($data['name']) || empty($data['date'])) {
            throw new Exception('Event name and date are required', 400);
        }

        $stmt = $pdo->prepare("INSERT INTO events (name, date, location, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $data['name'],
            $data['date'],
            $data['location'] ?? '',
            $data['description'] ?? ''
        ]);
        
        echo json_encode([
            'status' => 'success',
            'id' => $pdo->lastInsertId()
        ]);
        exit;
    }

    // PUT - Update event
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON input', 400);
        }

        // Validate required fields
        if (empty($data['id']) || empty($data['name']) || empty($data['date'])) {
            throw new Exception('Event ID, name and date are required', 400);
        }

        $stmt = $pdo->prepare("UPDATE events SET name=?, date=?, location=?, description=? WHERE id=?");
        $stmt->execute([
            $data['name'],
            $data['date'],
            $data['location'] ?? '',
            $data['description'] ?? '',
            $data['id']
        ]);
        
        echo json_encode(['status' => 'success']);
        exit;
    }

    // DELETE - Remove event
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $input = json_decode(file_get_contents("php://input"), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON input', 400);
        }

        if (empty($input['id'])) {
            throw new Exception('Missing event ID', 400);
        }

        // Using transactions for atomic operations
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("DELETE FROM event_registrations WHERE event_id = ?");
            $stmt->execute([$input['id']]);
            
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$input['id']]);
            
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Event deleted']);
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw new Exception('Database error: ' . $e->getMessage(), 500);
        }
        exit;
    }

    // If no method matched
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);

} catch (Exception $e) {
    // Clean any output buffer
    ob_end_clean();
    
    // Set appropriate HTTP status
    $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($code);
    
    // Return JSON error
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'code' => $code
    ]);
    
    // Log the error
    error_log('Events API Error: ' . $e->getMessage());
}

// Flush output buffer
ob_end_flush();
?>