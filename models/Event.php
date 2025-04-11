<?php
class Event {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $stmt = $this->pdo->query('SELECT * FROM events');
        return $stmt->fetchAll();
    }

    public function create($name, $date, $location, $description, $organizerId) {
        $stmt = $this->pdo->prepare('INSERT INTO events (name, date, location, description, organizer_id) VALUES (?, ?, ?, ?, ?)');
        return $stmt->execute([$name, $date, $location, $description, $organizerId]);
    }
}
?>
