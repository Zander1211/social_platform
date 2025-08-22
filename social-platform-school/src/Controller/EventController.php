<?php

class EventController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function createEvent($data)
    {
        $stmt = $this->pdo->prepare('INSERT INTO events (title, description, event_date, created_by, created_at) VALUES (:title, :description, :event_date, :created_by, NOW())');
        $ok = $stmt->execute([
            ':title' => $data['title'] ?? null,
            ':description' => $data['description'] ?? null,
            ':event_date' => $data['event_date'] ?? null,
            ':created_by' => $data['created_by'] ?? null,
        ]);
        return $ok ? ['status' => 'success', 'eventId' => $this->pdo->lastInsertId()] : ['status' => 'error'];
    }

    public function getAllEvents()
    {
        $stmt = $this->pdo->query('SELECT id, title, description, event_date FROM events ORDER BY event_date DESC');
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function deleteEvent($eventId)
    {
        $stmt = $this->pdo->prepare('DELETE FROM events WHERE id = :id');
        return $stmt->execute([':id' => $eventId]);
    }

    public function getEvent($eventId)
    {
        $stmt = $this->pdo->prepare('SELECT id, title, description, event_date FROM events WHERE id = :id');
        $stmt->execute([':id' => $eventId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}