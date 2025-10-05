<?php

class ReactionController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function addReaction($userId, $postId, $reactionType)
    {
        if (!in_array($reactionType, ['like', 'haha', 'heart', 'sad', 'angry', 'wow'])) {
            throw new InvalidArgumentException('Invalid reaction type');
        }
        // ensure single reaction per user per post: remove previous then insert
        $this->removeReaction($userId, $postId);
        $stmt = $this->pdo->prepare('INSERT INTO reactions (user_id, post_id, type, created_at) VALUES (:user_id, :post_id, :type, NOW())');
        return $stmt->execute([':user_id' => $userId, ':post_id' => $postId, ':type' => $reactionType]);
    }

    public function removeReaction($userId, $postId)
    {
        $stmt = $this->pdo->prepare('DELETE FROM reactions WHERE user_id = :user_id AND post_id = :post_id');
        return $stmt->execute([':user_id' => $userId, ':post_id' => $postId]);
    }

    public function getReactions($postId)
    {
        $stmt = $this->pdo->prepare('SELECT r.type, u.name, u.id as user_id FROM reactions r JOIN users u ON r.user_id = u.id WHERE r.post_id = :post_id ORDER BY r.created_at ASC');
        $stmt->execute([':post_id' => $postId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $avatar = null;
            // look for uploaded avatar files named avatar_<id>.* in public/uploads
            $globPath = __DIR__ . '/../../public/uploads/avatar_' . $r['user_id'] . '.*';
            $files = @glob($globPath);
            if ($files && count($files) > 0) {
                $avatar = 'uploads/' . basename($files[0]);
            }
            $out[] = ['type' => $r['type'], 'name' => $r['name'], 'id' => $r['user_id'], 'avatar' => $avatar];
        }
        return $out;
    }

    public function reactToComment($commentId, $userId, $reaction)
    {
        if (!in_array($reaction, ['like', 'haha', 'heart', 'sad', 'angry', 'wow'])) {
            throw new InvalidArgumentException('Invalid reaction type');
        }
        // ensure single reaction per user per comment: remove previous then insert
        $this->removeReaction($userId, $commentId);
        $stmt = $this->pdo->prepare('INSERT INTO reactions (user_id, comment_id, reaction, created_at) VALUES (:user_id, :comment_id, :reaction, NOW())');
        return $stmt->execute([':user_id' => $userId, ':comment_id' => $commentId, ':reaction' => $reaction]);
    }
}
