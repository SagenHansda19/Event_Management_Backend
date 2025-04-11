<?php
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/functions.php';
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set headers for CORS
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight requests (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database connection
include '../config/db.php';

try {
    // Log the input data for debugging
    $input = file_get_contents("php://input");
    error_log("Input data: " . $input);

    $data = json_decode($input, true);
    $registrationId = $data['id'] ?? null;
    $attended = $data['attended'] ?? null;

    if (!$registrationId || $attended === null) {
        throw new Exception('Registration ID and attendance status are required');
    }

    // Log the registration ID and attendance status for debugging
    error_log("Registration ID: " . $registrationId);
    error_log("Attendance status: " . $attended);

    // Update the attendance status
    $stmt = $pdo->prepare("UPDATE event_registrations SET attended = ? WHERE id = ?");
    $stmt->execute([$attended, $registrationId]);

    // Fetch updated registration data
    $stmt = $pdo->prepare("SELECT r.id, r.event_id, r.user_id, u.name, u.email, r.attended
                           FROM event_registrations r
                           JOIN users u ON r.user_id = u.id
                           WHERE r.id = ?");
    $stmt->execute([$registrationId]);
    $registration = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registration) {
        throw new Exception('Registration not found');
    }

    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Attendance updated successfully',
        'data' => [
            'id' => $registration['id'],
            'event_id' => $registration['event_id'],
            'user_id' => $registration['user_id'],
            'name' => $registration['name'],
            'email' => $registration['email'],
            'attended' => $registration['attended']
        ]
    ]);
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Error in update_attendance.php: " . $e->getMessage());

    // Handle errors
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>