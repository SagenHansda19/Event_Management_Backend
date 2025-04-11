<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/functions.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/db.php';

// Verify database connection
if (!$conn) {
    error_log('Database connection failed: ' . $conn->connect_error);
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

try {
    // Debugging: Log incoming request
    error_log('Create event request received: ' . file_get_contents("php://input"));
    
    // Get the raw POST data
    $input = file_get_contents("php://input");
    if (empty($input)) {
        throw new Exception('No input data received');
    }
    
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    // Validate required fields
    if (empty($data['name']) || empty($data['date']) || empty($data['description'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO events (name, date, location, description) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        error_log('Failed to prepare statement: ' . $conn->error);
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param("ssss", $data['name'], $data['date'], $data['location'], $data['description']);
    
    if ($stmt->execute()) {
        $json = json_encode(['message' => 'Event created successfully']);
        if ($json === false) {
            throw new Exception('Failed to encode response to JSON');
        }
        // Ensure no extra output
        ob_clean();
        echo $json;
        exit;
    } else {
        error_log('Failed to execute query: ' . $stmt->error);
        throw new Exception('Failed to execute SQL statement: ' . $stmt->error);
    }
} catch (Exception $e) {
    error_log('Error creating event: ' . $极e->getMessage());
    http_response_code(500);
    $json = json_encode(['error' => $e->getMessage()]);
    if ($json === false) {
        // Fallback error if JSON encoding fails
        ob_clean();
        echo '{"error":"An unknown error occurred"}';
        exit;
    } else {
        ob_clean();
        echo $json;
        exit;
    }
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>
