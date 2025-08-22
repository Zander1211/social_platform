<?php

class CommentController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function addComment($postId, $userId, $content)
    {
        $stmt = $this->pdo->prepare('INSERT INTO comments (post_id, user_id, content, created_at) VALUES (:post_id, :user_id, :content, NOW())');
        $ok = $stmt->execute([':post_id' => $postId, ':user_id' => $userId, ':content' => $content]);
        return $ok ? ['status' => 'success'] : ['status' => 'error'];
    }

    public function editComment($commentId, $userId, $newContent)
    {
        // ensure ownership
        $stmt = $this->pdo->prepare('SELECT user_id FROM comments WHERE id = :id');
        $stmt->execute([':id' => $commentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['user_id'] != $userId) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }
        $up = $this->pdo->prepare('UPDATE comments SET content = :content, edited = 1, updated_at = NOW() WHERE id = :id');
        $ok = $up->execute([':content' => $newContent, ':id' => $commentId]);
        return $ok ? ['status' => 'success'] : ['status' => 'error'];
    }

    public function deleteComment($commentId, $userId)
    {
        $stmt = $this->pdo->prepare('SELECT user_id FROM comments WHERE id = :id');
        $stmt->execute([':id' => $commentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['user_id'] != $userId) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }
        $del = $this->pdo->prepare('DELETE FROM comments WHERE id = :id');
        $ok = $del->execute([':id' => $commentId]);
        return $ok ? ['status' => 'success'] : ['status' => 'error'];
    }

    public function getComments($postId)
    {
        $stmt = $this->pdo->prepare('SELECT c.*, u.name as author FROM comments c LEFT JOIN users u ON c.user_id = u.id WHERE c.post_id = :post_id ORDER BY c.created_at ASC');
        $stmt->execute([':post_id' => $postId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}