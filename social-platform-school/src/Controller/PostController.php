<?php

class PostController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllPosts($q = null)
    {
    // Select title and content; some older schemas used 'caption' — handle gracefully by selecting available columns.
    $sql = "SELECT p.id,
               COALESCE(p.title, '') AS title,
               COALESCE(p.content, '') AS content,
               p.created_at,
               u.name AS author
        FROM posts p
        LEFT JOIN users u ON p.user_id = u.id
        ";
        $params = [];
        if ($q) {
            $sql .= " WHERE (p.title LIKE :q OR p.content LIKE :q OR u.name LIKE :q) ";
            $params[':q'] = "%" . $q . "%";
        }
        $sql .= " ORDER BY p.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function createPost($data)
    {
        $stmt = $this->pdo->prepare('INSERT INTO posts (user_id, title, content, created_at) VALUES (:user_id, :title, :content, NOW())');
        $stmt->execute([
            ':user_id' => $data['user_id'] ?? null,
            ':title' => $data['title'] ?? null,
            ':content' => $data['content'] ?? null,
        ]);
        return $this->pdo->lastInsertId();
    }

    public function deletePost($postId)
    {
        $stmt = $this->pdo->prepare('DELETE FROM posts WHERE id = :id');
        return $stmt->execute([':id' => $postId]);
    }
}