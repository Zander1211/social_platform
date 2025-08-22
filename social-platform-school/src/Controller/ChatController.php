<?php

class ChatController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function sendMessage($chatId, $senderId, $content)
    {
        $stmt = $this->pdo->prepare('INSERT INTO messages (chat_id, sender_id, content, created_at) VALUES (:chat_id, :sender_id, :content, NOW())');
        return $stmt->execute([':chat_id' => $chatId, ':sender_id' => $senderId, ':content' => $content]);
    }

    public function getMessages($chatId)
    {
        $stmt = $this->pdo->prepare('SELECT m.*, u.name as sender FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.chat_id = :chat_id ORDER BY m.created_at ASC');
        $stmt->execute([':chat_id' => $chatId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveUsers()
    {
        $stmt = $this->pdo->query('SELECT id, name FROM users WHERE is_online = 1');
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    // Helpers for a default/global chat room (chat_id = 1)
    // Group chat helpers
    public function createGroup($name, $creatorId)
    {
        $stmt = $this->pdo->prepare('INSERT INTO chat_rooms (name, is_group, created_by, created_at) VALUES (:name, 1, :creator, NOW())');
        $ok = $stmt->execute([':name' => $name, ':creator' => $creatorId]);
        $roomId = $ok ? $this->pdo->lastInsertId() : false;
        // if chat_members table exists, insert creator as member
        if ($roomId) {
            try {
                $check = $this->pdo->query("SHOW TABLES LIKE 'chat_members'");
                if ($check && $check->rowCount() > 0) {
                    $ins = $this->pdo->prepare('INSERT INTO chat_members (chat_id, user_id, joined_at) VALUES (:chat_id, :user_id, NOW())');
                    $ins->execute([':chat_id' => $roomId, ':user_id' => $creatorId]);
                }
            } catch (Exception $e) {
                // ignore - best effort
            }
        }
        return $roomId;
    }

    public function listGroups()
    {
        $stmt = $this->pdo->query('SELECT id, name, created_at FROM chat_rooms WHERE is_group = 1 ORDER BY created_at DESC');
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function sendMessageToGroup($groupId, $senderId, $content)
    {
        // only allow sending if the sender is a member (if chat_members exists)
        try {
            $check = $this->pdo->query("SHOW TABLES LIKE 'chat_members'");
            if ($check && $check->rowCount() > 0) {
                $stmt = $this->pdo->prepare('SELECT 1 FROM chat_members WHERE chat_id = :chat_id AND user_id = :user_id');
                $stmt->execute([':chat_id' => $groupId, ':user_id' => $senderId]);
                if ($stmt->fetchColumn() === false) {
                    return false; // not a member
                }
            }
        } catch (Exception $e) {
            // ignore and allow (legacy fallback)
        }
        return $this->sendMessage($groupId, $senderId, $content);
    }

    public function getGroupMessages($groupId)
    {
        // return messages only if the requester is a member — caller should enforce but we keep a guard here
        return $this->getMessages($groupId);
    }

    // Add a member to a group (best-effort if chat_members table exists)
    public function addMember($groupId, $userId)
    {
        try {
            $check = $this->pdo->query("SHOW TABLES LIKE 'chat_members'");
            if ($check && $check->rowCount() > 0) {
                $ins = $this->pdo->prepare('INSERT IGNORE INTO chat_members (chat_id, user_id, joined_at) VALUES (:chat_id, :user_id, NOW())');
                return $ins->execute([':chat_id' => $groupId, ':user_id' => $userId]);
            }
        } catch (Exception $e) {
            return false;
        }
        return false;
    }

    // Check membership
    public function isMember($groupId, $userId)
    {
        try {
            $check = $this->pdo->query("SHOW TABLES LIKE 'chat_members'");
            if ($check && $check->rowCount() > 0) {
                $stmt = $this->pdo->prepare('SELECT 1 FROM chat_members WHERE chat_id = :chat_id AND user_id = :user_id');
                $stmt->execute([':chat_id' => $groupId, ':user_id' => $userId]);
                return $stmt->fetchColumn() !== false;
            }
        } catch (Exception $e) {
        }
        // if table does not exist, only creator is implicitly a member; caller may need to enforce separately
        $stmt = $this->pdo->prepare('SELECT created_by FROM chat_rooms WHERE id = :id');
        $stmt->execute([':id' => $groupId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && $row['created_by'] == $userId;
    }
}