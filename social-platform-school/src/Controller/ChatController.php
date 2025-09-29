<?php

class ChatController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    
    public function getGroupInfo($groupId) {
        $stmt = $this->pdo->prepare("SELECT cr.*, u.name as creator_name FROM chat_rooms cr LEFT JOIN users u ON cr.created_by = u.id WHERE cr.id = ? AND cr.is_group = 1");
        $stmt->execute([$groupId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getGroupMembers($groupId) {
        $stmt = $this->pdo->prepare("
            SELECT u.id as user_id, u.name 
            FROM chat_members cm 
            JOIN users u ON cm.user_id = u.id 
            WHERE cm.chat_id = ? 
            ORDER BY u.name ASC
        ");
        $stmt->execute([$groupId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getMessageCount($groupId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE chat_id = ?");
        $stmt->execute([$groupId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['count'] : 0;
    }

    public function sendMessage($chatId, $senderId, $content)
    {
        // First, validate that the chat room exists
        try {
            $checkStmt = $this->pdo->prepare('SELECT id FROM chat_rooms WHERE id = :chat_id');
            $checkStmt->execute([':chat_id' => $chatId]);
            if (!$checkStmt->fetchColumn()) {
                // Chat room doesn't exist
                throw new Exception("Chat room with ID {$chatId} does not exist");
            }
        } catch (PDOException $e) {
            throw new Exception("Database error while validating chat room: " . $e->getMessage());
        }
        
        // If we get here, the chat room exists, so we can safely insert the message
        try {
            $stmt = $this->pdo->prepare('INSERT INTO messages (chat_id, sender_id, content, created_at) VALUES (:chat_id, :sender_id, :content, NOW())');
            return $stmt->execute([':chat_id' => $chatId, ':sender_id' => $senderId, ':content' => $content]);
        } catch (PDOException $e) {
            throw new Exception("Failed to send message: " . $e->getMessage());
        }
    }

    public function getMessages($chatId)
    {
        $stmt = $this->pdo->prepare('SELECT m.*, u.name as sender FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.chat_id = :chat_id ORDER BY m.created_at ASC');
        $stmt->execute([':chat_id' => $chatId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveUsers()
    {
        try {
            // Try to get online users first, fallback to all users if is_online column doesn't exist
            $stmt = $this->pdo->query('SELECT id, name, role FROM users WHERE is_online = 1 ORDER BY name');
            $users = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            
            // If no online users or is_online column doesn't exist, get all users
            if (empty($users)) {
                $stmt = $this->pdo->query('SELECT id, name, role FROM users ORDER BY name');
                $users = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            }
            
            return $users;
        } catch (PDOException $e) {
            // If is_online column doesn't exist, fall back to all users
            try {
                $stmt = $this->pdo->query('SELECT id, name, role FROM users ORDER BY name');
                return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            } catch (PDOException $e2) {
                return [];
            }
        }
    }

    // Helpers for a default/global chat room (chat_id = 1)
    // Group chat helpers
    public function createGroup($name, $creatorId)
    {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO chat_rooms (name, is_group, created_by, created_at) VALUES (:name, 1, :creator, NOW())');
            $ok = $stmt->execute([':name' => $name, ':creator' => $creatorId]);
            
            if (!$ok) {
                error_log("Failed to insert group: " . print_r($stmt->errorInfo(), true));
                return false;
            }
            
            $roomId = $this->pdo->lastInsertId();
            error_log("Group created successfully with ID: $roomId");
            
            // if chat_members table exists, insert creator as member
            if ($roomId) {
                try {
                    $check = $this->pdo->query("SHOW TABLES LIKE 'chat_members'");
                    if ($check && $check->rowCount() > 0) {
                        $ins = $this->pdo->prepare('INSERT INTO chat_members (chat_id, user_id, joined_at) VALUES (:chat_id, :user_id, NOW())');
                        $ins->execute([':chat_id' => $roomId, ':user_id' => $creatorId]);
                        error_log("Creator added to group members");
                    } else {
                        error_log("chat_members table does not exist");
                    }
                } catch (Exception $e) {
                    error_log("Error adding creator to members: " . $e->getMessage());
                }
            }
            return $roomId;
        } catch (Exception $e) {
            error_log("Error creating group: " . $e->getMessage());
            return false;
        }
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

    // Create or get a direct (1:1) room between two users
    public function getOrCreateDirectRoom($userA, $userB)
    {
        $hasMembers = false;
        try {
            $check = $this->pdo->query("SHOW TABLES LIKE 'chat_members'");
            $hasMembers = $check && $check->rowCount() > 0;
        } catch (Exception $e) { $hasMembers = false; }

        if ($hasMembers) {
            // Find an existing direct room that both users are members of
            $stmt = $this->pdo->prepare('
                SELECT cr.id FROM chat_rooms cr
                JOIN chat_members m1 ON m1.chat_id = cr.id AND m1.user_id = :a
                JOIN chat_members m2 ON m2.chat_id = cr.id AND m2.user_id = :b
                WHERE cr.is_group = 0
                LIMIT 1
            ');
            $stmt->execute([':a' => $userA, ':b' => $userB]);
            $rid = $stmt->fetchColumn();
            if ($rid) return (int)$rid;

            // Create a new direct room and add both participants
            // Get the other user's name for a more meaningful room name
            $otherUserStmt = $this->pdo->prepare('SELECT name FROM users WHERE id = :id');
            $otherUserStmt->execute([':id' => $userB]);
            $otherUser = $otherUserStmt->fetch(PDO::FETCH_ASSOC);
            $roomName = $otherUser ? $otherUser['name'] : 'Direct chat';
            
            $ins = $this->pdo->prepare('INSERT INTO chat_rooms (name, is_group, created_by, created_at) VALUES (:name, 0, :creator, NOW())');
            $ok = $ins->execute([':name' => $roomName, ':creator' => $userA]);
            if (!$ok) return false;
            $rid = (int)$this->pdo->lastInsertId();

            $add = $this->pdo->prepare('INSERT IGNORE INTO chat_members (chat_id, user_id, joined_at) VALUES (:chat, :user, NOW())');
            $add->execute([':chat' => $rid, ':user' => $userA]);
            $add->execute([':chat' => $rid, ':user' => $userB]);
            return $rid;
        } else {
            // Fallback: if no membership table, reuse the latest direct room or create one
            try {
                $stmt = $this->pdo->prepare('SELECT id FROM chat_rooms WHERE is_group = 0 ORDER BY id DESC LIMIT 1');
                $stmt->execute();
                $rid = $stmt->fetchColumn();
                if ($rid) return (int)$rid;
            } catch (Exception $e) {}
            // Get the other user's name for a more meaningful room name
            $otherUserStmt = $this->pdo->prepare('SELECT name FROM users WHERE id = :id');
            $otherUserStmt->execute([':id' => $userB]);
            $otherUser = $otherUserStmt->fetch(PDO::FETCH_ASSOC);
            $roomName = $otherUser ? $otherUser['name'] : 'Direct chat';
            
            $ins = $this->pdo->prepare('INSERT INTO chat_rooms (name, is_group, created_by, created_at) VALUES (:name, 0, :creator, NOW())');
            $ok = $ins->execute([':name' => $roomName, ':creator' => $userA]);
            return $ok ? (int)$this->pdo->lastInsertId() : false;
        }
    }

    // List rooms for a given user (groups + DMs) - including empty rooms
    public function listUserRooms($userId)
    {
        try {
            $check = $this->pdo->query("SHOW TABLES LIKE 'chat_members'");
            if ($check && $check->rowCount() > 0) {
                $stmt = $this->pdo->prepare('
                    SELECT cr.id, cr.name, cr.is_group, cr.created_at,
                           (SELECT MAX(m2.created_at) FROM messages m2 WHERE m2.chat_id = cr.id) as last_message_at
                    FROM chat_members cm
                    JOIN chat_rooms cr ON cr.id = cm.chat_id
                    WHERE cm.user_id = :uid
                    ORDER BY 
                        CASE WHEN last_message_at IS NULL THEN cr.created_at ELSE last_message_at END DESC
                ');
                $stmt->execute([':uid' => $userId]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {}
        // Fallback: rooms created by this user - including empty rooms
        $stmt = $this->pdo->prepare('
            SELECT cr.id, cr.name, cr.is_group, cr.created_at,
                   (SELECT MAX(m2.created_at) FROM messages m2 WHERE m2.chat_id = cr.id) as last_message_at
            FROM chat_rooms cr
            WHERE cr.created_by = :uid
            ORDER BY 
                CASE WHEN last_message_at IS NULL THEN cr.created_at ELSE last_message_at END DESC
        ');
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Enhanced user list for starting chats with email for better search
    public function getAllUsersLite($excludeUserId)
    {
        $stmt = $this->pdo->prepare('SELECT id, name, email FROM users WHERE id <> :id ORDER BY name ASC');
        $stmt->execute([':id' => $excludeUserId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Search users by name or email
    public function searchUsers($query, $excludeUserId = null)
    {
        $searchTerm = '%' . $query . '%';
        $sql = 'SELECT id, name, email FROM users WHERE (name LIKE :search OR email LIKE :search)';
        $params = [':search' => $searchTerm];
        
        if ($excludeUserId) {
            $sql .= ' AND id <> :exclude';
            $params[':exclude'] = $excludeUserId;
        }
        
        $sql .= ' ORDER BY name ASC LIMIT 20';
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Derive a display name for a room; for DMs show the other participant's name
    public function getRoomTitle($roomId, $currentUserId)
    {
        try {
            $s = $this->pdo->prepare('SELECT name, is_group, created_by FROM chat_rooms WHERE id = :id');
            $s->execute([':id' => $roomId]);
            $r = $s->fetch(PDO::FETCH_ASSOC);
            if (!$r) return 'Chat';
            if ((int)$r['is_group'] === 1) return $r['name'] ?: 'Group';
            
            // For direct chat: try multiple strategies to find the other user's name
            
            // Strategy 1: Use chat_members table to find the other user
            $check = $this->pdo->query("SHOW TABLES LIKE 'chat_members'");
            if ($check && $check->rowCount() > 0) {
                $s2 = $this->pdo->prepare('SELECT u.name FROM chat_members cm JOIN users u ON u.id = cm.user_id WHERE cm.chat_id = :chat AND cm.user_id <> :me LIMIT 1');
                $s2->execute([':chat' => $roomId, ':me' => $currentUserId]);
                $o = $s2->fetch(PDO::FETCH_ASSOC);
                if ($o && !empty($o['name'])) {
                    return $o['name'];
                }
            }
            
            // Strategy 2: Find the other user through messages
            try {
                $msgStmt = $this->pdo->prepare('
                    SELECT DISTINCT u.name 
                    FROM messages m 
                    JOIN users u ON u.id = m.sender_id 
                    WHERE m.chat_id = :chat AND m.sender_id <> :me 
                    ORDER BY m.created_at DESC 
                    LIMIT 1
                ');
                $msgStmt->execute([':chat' => $roomId, ':me' => $currentUserId]);
                $msgUser = $msgStmt->fetch(PDO::FETCH_ASSOC);
                if ($msgUser && !empty($msgUser['name'])) {
                    return $msgUser['name'];
                }
            } catch (Exception $e) {
                // Continue to next strategy
            }
            
            // Strategy 3: If room creator is not current user, use creator's name
            if ($r['created_by'] && (int)$r['created_by'] !== (int)$currentUserId) {
                try {
                    $creatorStmt = $this->pdo->prepare('SELECT name FROM users WHERE id = :id');
                    $creatorStmt->execute([':id' => $r['created_by']]);
                    $creator = $creatorStmt->fetch(PDO::FETCH_ASSOC);
                    if ($creator && !empty($creator['name'])) {
                        return $creator['name'];
                    }
                } catch (Exception $e) {
                    // Continue to next strategy
                }
            }
            
            // Strategy 4: Use stored room name if it's not generic
            if (!empty($r['name']) && $r['name'] !== 'Direct chat') {
                return $r['name'];
            }
            
            // Strategy 5: Find ANY other user who has sent messages in this room
            try {
                $anyUserStmt = $this->pdo->prepare('
                    SELECT DISTINCT u.name 
                    FROM messages m 
                    JOIN users u ON u.id = m.sender_id 
                    WHERE m.chat_id = :chat 
                    ORDER BY m.created_at DESC 
                    LIMIT 2
                ');
                $anyUserStmt->execute([':chat' => $roomId]);
                $users = $anyUserStmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($users as $user) {
                    // Find a user that's not the current user
                    $userIdStmt = $this->pdo->prepare('SELECT id FROM users WHERE name = :name');
                    $userIdStmt->execute([':name' => $user['name']]);
                    $userId = $userIdStmt->fetchColumn();
                    
                    if ($userId && (int)$userId !== (int)$currentUserId) {
                        return $user['name'];
                    }
                }
            } catch (Exception $e) {
                // Continue to fallback
            }
            
            // Final fallback
            return 'Direct chat';
        } catch (Exception $e) { 
            return 'Chat'; 
        }
    }

    public function listMembers($chatId)
    {
        try {
            $check = $this->pdo->query("SHOW TABLES LIKE 'chat_members'");
            if ($check && $check->rowCount() > 0) {
                $stmt = $this->pdo->prepare('SELECT u.id, u.name FROM chat_members cm JOIN users u ON u.id = cm.user_id WHERE cm.chat_id = :chat ORDER BY u.name ASC');
                $stmt->execute([':chat' => $chatId]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {}
        return [];
    }

    public function removeMember($chatId, $userId)
    {
        try {
            $check = $this->pdo->query("SHOW TABLES LIKE 'chat_members'");
            if ($check && $check->rowCount() > 0) {
                $stmt = $this->pdo->prepare('DELETE FROM chat_members WHERE chat_id = :chat AND user_id = :user');
                return $stmt->execute([':chat' => $chatId, ':user' => $userId]);
            }
        } catch (Exception $e) { return false; }
        return false;
    }

    public function getRoomInfo($roomId)
    {
        try {
            $s = $this->pdo->prepare('SELECT id, name, is_group, created_by FROM chat_rooms WHERE id = :id');
            $s->execute([':id' => $roomId]);
            return $s->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) { return null; }
    }

    public function getDmOtherUserId($roomId, $me)
    {
        try {
            $check = $this->pdo->query("SHOW TABLES LIKE 'chat_members'");
            if ($check && $check->rowCount() > 0) {
                $s2 = $this->pdo->prepare('SELECT cm.user_id FROM chat_members cm JOIN chat_rooms cr ON cr.id = cm.chat_id WHERE cm.chat_id = :chat AND cr.is_group = 0 AND cm.user_id <> :me LIMIT 1');
                $s2->execute([':chat' => $roomId, ':me' => $me]);
                $uid = $s2->fetchColumn();
                return $uid ? (int)$uid : null;
            }
        } catch (Exception $e) { return null; }
        return null;
    }

    public function memberCount($roomId)
    {
        try {
            $check = $this->pdo->query("SHOW TABLES LIKE 'chat_members'");
            if ($check && $check->rowCount() > 0) {
                $s = $this->pdo->prepare('SELECT COUNT(*) FROM chat_members WHERE chat_id = :id');
                $s->execute([':id' => $roomId]);
                return (int)$s->fetchColumn();
            }
        } catch (Exception $e) { return 0; }
        return 0;
    }

    public function deleteRoom($roomId)
    {
        try { $d1 = $this->pdo->prepare('DELETE FROM messages WHERE chat_id = :id'); $d1->execute([':id' => $roomId]); } catch (Exception $e) {}
        try {
            $check = $this->pdo->query("SHOW TABLES LIKE 'chat_members'");
            if ($check && $check->rowCount() > 0) {
                $d2 = $this->pdo->prepare('DELETE FROM chat_members WHERE chat_id = :id'); $d2->execute([':id' => $roomId]);
            }
        } catch (Exception $e) {}
        try { $d3 = $this->pdo->prepare('DELETE FROM chat_rooms WHERE id = :id'); $d3->execute([':id' => $roomId]); } catch (Exception $e) {}
        return true;
    }
    
    // Clean up empty chat rooms (rooms with no messages)
    public function cleanupEmptyRooms()
    {
        try {
            // Delete chat rooms that have no messages
            $stmt = $this->pdo->prepare('
                DELETE cr FROM chat_rooms cr
                WHERE NOT EXISTS (SELECT 1 FROM messages m WHERE m.chat_id = cr.id)
                AND cr.created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ');
            $stmt->execute();
            $deletedRooms = $stmt->rowCount();
            
            // Also clean up orphaned chat_members entries
            $check = $this->pdo->query("SHOW TABLES LIKE 'chat_members'");
            if ($check && $check->rowCount() > 0) {
                $stmt2 = $this->pdo->prepare('
                    DELETE cm FROM chat_members cm
                    WHERE NOT EXISTS (SELECT 1 FROM chat_rooms cr WHERE cr.id = cm.chat_id)
                ');
                $stmt2->execute();
            }
            
            return $deletedRooms;
        } catch (Exception $e) {
            return 0;
        }
    }
}