<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/functions.php';
include '../config/db.php';

// Fetch all tasks
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT * FROM tasks");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($tasks);
}

// Create a new task
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("INSERT INTO tasks (event_id, volunteer_id, task_description, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$data['event_id'], $data['volunteer_id'], $data['task_description'], $data['status']]);
    echo json_encode(['status' => 'success', 'message' => 'Task created successfully']);
}

// Update a task
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("UPDATE tasks SET event_id = ?, volunteer_id = ?, task_description = ?, status = ? WHERE id = ?");
    $stmt->execute([$data['event_id'], $data['volunteer_id'], $data['task_description'], $data['status'], $data['id']]);
    echo json_encode(['status' => 'success', 'message' => 'Task updated successfully']);
}

// Delete a task
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$data['id']]);
    echo json_encode(['status' => 'success', 'message' => 'Task deleted successfully']);
}
?>
