<?php
require '../models/Event.php';

class EventController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Fetch all events
    public function getAllEvents() {
        $stmt = $this->pdo->query("SELECT * FROM events");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch a single event by ID
    public function getEventById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM events WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Create a new event
    public function createEvent($data) {
        $stmt = $this->pdo->prepare("INSERT INTO events (name, date, location, description) VALUES (:name, :date, :location, :description)");
        $stmt->execute([
            'name' => $data['name'],
            'date' => $data['date'],
            'location' => $data['location'],
            'description' => $data['description']
        ]);
    }
}
?>