<?php

// Minimal PDO-backed AdminController to match the existing simple app structure.
// This avoids namespace mismatch and missing model/service classes in the current codebase.
class AdminController
{
    /** @var PDO */
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllPosts()
    {
    $sql = "SELECT p.id,
               COALESCE(p.title, '') AS title,
               COALESCE(p.content, '') AS content,
               p.created_at,
               u.name AS author
        FROM posts p
                LEFT JOIN users u ON p.user_id = u.id
                ORDER BY p.created_at DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function deletePost($postId)
    {
        $stmt = $this->pdo->prepare("DELETE FROM posts WHERE id = :id");
        return $stmt->execute([':id' => $postId]);
    }

    public function getAllEvents()
    {
        $sql = "SELECT id, title, description, event_date AS date FROM events ORDER BY event_date DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function deleteEvent($eventId)
    {
        $stmt = $this->pdo->prepare("DELETE FROM events WHERE id = :id");
        return $stmt->execute([':id' => $eventId]);
    }

    // Lightweight helpers that could be extended later:
    public function createEvent($data)
    {
        $stmt = $this->pdo->prepare("INSERT INTO events (title, description, event_date, created_by, created_at) VALUES (:title, :description, :event_date, :created_by, NOW())");
        return $stmt->execute([
            ':title' => $data['title'] ?? null,
            ':description' => $data['description'] ?? null,
            ':event_date' => $data['event_date'] ?? null,
            ':created_by' => $data['created_by'] ?? null,
        ]);
    }
}