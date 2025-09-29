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
        // Check which columns exist in the posts table
        $hasModernSchema = $this->hasModernSchema();
        
        if ($hasModernSchema) {
            // Modern schema with title and content columns
            $sql = "SELECT p.id,
                       COALESCE(p.title, '') AS title,
                       COALESCE(p.content, '') AS content,
                       p.created_at,
                       u.name AS user_name
                FROM posts p
                LEFT JOIN users u ON p.user_id = u.id
                ORDER BY p.created_at DESC";
        } else {
            // Legacy schema with caption column only
            $sql = "SELECT p.id,
                       '' AS title,
                       COALESCE(p.caption, '') AS content,
                       p.created_at,
                       u.name AS user_name
                FROM posts p
                LEFT JOIN users u ON p.user_id = u.id
                ORDER BY p.created_at DESC";
        }

        try {
            $stmt = $this->pdo->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (PDOException $e) {
            return [];
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function hasModernSchema()
    {
        try {
            $stmt = $this->pdo->query('SHOW COLUMNS FROM posts');
            $columns = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
            return in_array('title', $columns, true) && in_array('content', $columns, true);
        } catch (PDOException $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function deletePost($postId)
    {
        // Cascade delete associated reactions and comments first
        $this->pdo->prepare("DELETE FROM reactions WHERE post_id = :id")->execute([':id' => $postId]);
        $this->pdo->prepare("DELETE FROM comments WHERE post_id = :id")->execute([':id' => $postId]);
        $stmt = $this->pdo->prepare("DELETE FROM posts WHERE id = :id");
        return $stmt->execute([':id' => $postId]);
    }

    public function getAllEvents()
    {
        // Try extended schema (location, banner_path); fallback if columns don't exist
        try {
            $sql = "SELECT id, title, description, event_date AS date, location, banner_path FROM events ORDER BY event_date DESC";
            $stmt = $this->pdo->query($sql);
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            return $rows;   
        } catch (PDOException $e) {
            try {
                $sql = "SELECT id, title, description, event_date AS date FROM events ORDER BY event_date DESC";
                $stmt = $this->pdo->query($sql);
                $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                foreach ($rows as &$r) { $r['location'] = $r['location'] ?? null; $r['banner_path'] = $r['banner_path'] ?? null; }
                return $rows;
            } catch (PDOException $ex) {
                return [];
            } catch (Exception $ex) {
                return [];
            }
        } catch (Exception $e) {
            try {
                $sql = "SELECT id, title, description, event_date AS date FROM events ORDER BY event_date DESC";
                $stmt = $this->pdo->query($sql);
                $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                foreach ($rows as &$r) { $r['location'] = $r['location'] ?? null; $r['banner_path'] = $r['banner_path'] ?? null; }
                return $rows;
            } catch (PDOException $ex) {
                return [];
            } catch (Exception $ex) {
                return [];
            }
        }
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

    // Comments management
    public function getAllComments($limit = 100)
    {
        $limit = (int)$limit; if ($limit <= 0) { $limit = 100; }
        
        // Check which columns exist in the posts table
        $hasModernSchema = $this->hasModernSchema();
        
        if ($hasModernSchema) {
            $sql = "SELECT c.id, c.post_id, c.user_id, c.content, c.created_at,
                           u.name AS author, COALESCE(p.title, '') AS post_title
                    FROM comments c
                    LEFT JOIN users u ON c.user_id = u.id
                    LEFT JOIN posts p ON c.post_id = p.id
                    ORDER BY c.created_at DESC
                    LIMIT $limit";
        } else {
            $sql = "SELECT c.id, c.post_id, c.user_id, c.content, c.created_at,
                           u.name AS author, COALESCE(p.caption, '') AS post_title
                    FROM comments c
                    LEFT JOIN users u ON c.user_id = u.id
                    LEFT JOIN posts p ON c.post_id = p.id
                    ORDER BY c.created_at DESC
                    LIMIT $limit";
        }
        
        try {
            $stmt = $this->pdo->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (PDOException $e) {
            return [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function deleteCommentAdmin($commentId)
    {
        // Delete comment reactions (if any schema supports comment reactions)
        $this->pdo->prepare('DELETE FROM reactions WHERE comment_id = :id')->execute([':id' => $commentId]);
        $stmt = $this->pdo->prepare('DELETE FROM comments WHERE id = :id');
        return $stmt->execute([':id' => $commentId]);
    }

    // Users management
    public function getAllUsers($limit = 200)
    {
        $limit = (int)$limit; if ($limit <= 0) { $limit = 200; }
        $sql = "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT $limit";
        $stmt = $this->pdo->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function deleteUser($userId)
    {
        $userId = (int)$userId;
        try {
            $this->pdo->beginTransaction();
            // Remove user reactions
            $this->pdo->prepare('DELETE FROM reactions WHERE user_id = :uid')->execute([':uid' => $userId]);
            // Remove reactions on user's posts
            $this->pdo->prepare('DELETE FROM reactions WHERE post_id IN (SELECT id FROM posts WHERE user_id = :uid)')->execute([':uid' => $userId]);
            // Remove reactions on user's comments (if schema supports comment_id in reactions)
            $this->pdo->prepare('DELETE FROM reactions WHERE comment_id IN (SELECT id FROM comments WHERE user_id = :uid)')->execute([':uid' => $userId]);
            // Remove comments made by the user
            $this->pdo->prepare('DELETE FROM comments WHERE user_id = :uid')->execute([':uid' => $userId]);
            // Remove comments on user's posts
            $this->pdo->prepare('DELETE FROM comments WHERE post_id IN (SELECT id FROM posts WHERE user_id = :uid)')->execute([':uid' => $userId]);
            // Remove user's posts
            $this->pdo->prepare('DELETE FROM posts WHERE user_id = :uid')->execute([':uid' => $userId]);
            // Remove user's events (if any)
            try { $this->pdo->prepare('DELETE FROM events WHERE created_by = :uid')->execute([':uid' => $userId]); } catch (Exception $e) {}
            // Remove the user
            $this->pdo->prepare('DELETE FROM users WHERE id = :uid')->execute([':uid' => $userId]);
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
        // Delete avatar files if present
        $globPath = __DIR__ . '/../../public/uploads/avatar_' . $userId . '.*';
        foreach ((array)glob($globPath) as $f) { @unlink($f); }
        return true;
    }

    // Warning System Methods
    public function issueWarning($userId, $reason, $level = 'low')
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_warnings (user_id, reason, warning_level, is_active, created_at) 
                VALUES (?, ?, ?, 1, NOW())
            ");
            $result = $stmt->execute([$userId, $reason, $level]);
            
            if ($result) {
                // Log the warning action
                $this->logWarningAction($userId, 'warning_issued', "Warning issued: $level - $reason");
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getUserWarnings($userId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM user_warnings 
                WHERE user_id = ? AND is_active = 1 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getAllWarnings()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT w.*, u.name as user_name, u.email as user_email 
                FROM user_warnings w 
                LEFT JOIN users u ON w.user_id = u.id 
                WHERE w.is_active = 1 
                ORDER BY w.created_at DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function dismissWarning($warningId)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE user_warnings SET is_active = 0 WHERE id = ?");
            $result = $stmt->execute([$warningId]);
            
            if ($result) {
                // Get warning details for logging
                $warningStmt = $this->pdo->prepare("SELECT user_id FROM user_warnings WHERE id = ?");
                $warningStmt->execute([$warningId]);
                $warning = $warningStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($warning) {
                    $this->logWarningAction($warning['user_id'], 'warning_dismissed', "Warning ID $warningId dismissed");
                }
            }
            
            return $result;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getWarningStats()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    warning_level,
                    COUNT(*) as count 
                FROM user_warnings 
                WHERE is_active = 1 
                GROUP BY warning_level
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // User Blocking System Methods
    public function blockUser($blockerId, $blockedId, $reason = '')
    {
        try {
            // Add debug logging
            error_log("AdminController DEBUG: blockUser called with blocker=$blockerId, blocked=$blockedId, reason=$reason");
            
            $stmt = $this->pdo->prepare("
                INSERT INTO user_blocks (blocker_user_id, blocked_user_id, reason, is_active, created_at) 
                VALUES (?, ?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE is_active = 1, reason = ?, updated_at = NOW()
            ");
            $result = $stmt->execute([$blockerId, $blockedId, $reason, $reason]);
            
            error_log("AdminController DEBUG: Query result = " . ($result ? 'TRUE' : 'FALSE'));
            error_log("AdminController DEBUG: Affected rows = " . $stmt->rowCount());
            
            return $result;
        } catch (PDOException $e) {
            error_log("AdminController DEBUG: PDO Exception = " . $e->getMessage());
            return false;
        }
    }

    public function unblockUser($blockerId, $blockedId)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE user_blocks 
                SET is_active = 0 
                WHERE blocker_user_id = ? AND blocked_user_id = ? AND is_active = 1
            ");
            return $stmt->execute([$blockerId, $blockedId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function isUserBlocked($blockerId, $blockedId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM user_blocks 
                WHERE blocker_user_id = ? AND blocked_user_id = ? AND is_active = 1
            ");
            $stmt->execute([$blockerId, $blockedId]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getUserBlocks($userId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT b.*, u.name as blocked_user_name, u.email as blocked_user_email 
                FROM user_blocks b 
                LEFT JOIN users u ON b.blocked_user_id = u.id 
                WHERE b.blocker_user_id = ? AND b.is_active = 1 
                ORDER BY b.created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getAllBlocks()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT b.*, 
                       u1.name as blocker_name, u1.email as blocker_email,
                       u2.name as blocked_name, u2.email as blocked_email
                FROM user_blocks b 
                LEFT JOIN users u1 ON b.blocker_user_id = u1.id 
                LEFT JOIN users u2 ON b.blocked_user_id = u2.id 
                WHERE b.is_active = 1 
                ORDER BY b.created_at DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // User Suspension System Methods
    public function suspendUser($userId, $suspendedBy, $reason, $type = 'temporary', $until = null)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_suspensions (user_id, suspended_by_user_id, reason, suspension_type, suspended_until, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, 1, NOW())
            ");
            $result = $stmt->execute([$userId, $suspendedBy, $reason, $type, $until]);
            
            if ($result) {
                $this->logWarningAction($userId, 'user_suspended', "User suspended: $type - $reason");
            }
            
            return $result;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function unsuspendUser($userId)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE user_suspensions SET is_active = 0 WHERE user_id = ? AND is_active = 1");
            $result = $stmt->execute([$userId]);
            
            if ($result) {
                $this->logWarningAction($userId, 'user_unsuspended', "User suspension lifted");
            }
            
            return $result;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function isUserSuspended($userId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM user_suspensions 
                WHERE user_id = ? AND is_active = 1 
                AND (suspension_type = 'permanent' OR suspended_until > NOW())
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getUserSuspension($userId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.*, u.name as suspended_by_name 
                FROM user_suspensions s 
                LEFT JOIN users u ON s.suspended_by_user_id = u.id 
                WHERE s.user_id = ? AND s.is_active = 1 
                AND (s.suspension_type = 'permanent' OR s.suspended_until > NOW())
                ORDER BY s.created_at DESC LIMIT 1
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getAllSuspensions()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT s.*, 
                       u1.name as user_name, u1.email as user_email,
                       u2.name as suspended_by_name, u2.email as suspended_by_email
                FROM user_suspensions s 
                LEFT JOIN users u1 ON s.user_id = u1.id 
                LEFT JOIN users u2 ON s.suspended_by_user_id = u2.id 
                WHERE s.is_active = 1 
                ORDER BY s.created_at DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // User Reporting System Methods
    public function createReport($reporterUserId, $reportedUserId, $reportType, $reason, $contentId = null)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_reports (reporter_user_id, reported_user_id, report_type, reason, content_id, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'pending', NOW())
            ");
            return $stmt->execute([$reporterUserId, $reportedUserId, $reportType, $reason, $contentId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getAllReports($status = null)
    {
        try {
            $sql = "
                SELECT r.*, 
                       u1.name as reporter_name, u1.email as reporter_email,
                       u2.name as reported_name, u2.email as reported_email
                FROM user_reports r 
                LEFT JOIN users u1 ON r.reporter_user_id = u1.id 
                LEFT JOIN users u2 ON r.reported_user_id = u2.id 
            ";
            
            if ($status) {
                $sql .= " WHERE r.status = ? ORDER BY r.created_at DESC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$status]);
            } else {
                $sql .= " ORDER BY r.created_at DESC";
                $stmt = $this->pdo->query($sql);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function updateReportStatus($reportId, $status, $adminNotes = '')
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE user_reports 
                SET status = ?, admin_notes = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$status, $adminNotes, $reportId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Warning Action Logging
    private function logWarningAction($userId, $actionType, $description)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO warning_actions (user_id, action_type, description, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            return $stmt->execute([$userId, $actionType, $description]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getWarningActions($userId = null)
    {
        try {
            if ($userId) {
                $stmt = $this->pdo->prepare("
                    SELECT wa.*, u.name as user_name 
                    FROM warning_actions wa 
                    LEFT JOIN users u ON wa.user_id = u.id 
                    WHERE wa.user_id = ? 
                    ORDER BY wa.created_at DESC
                ");
                $stmt->execute([$userId]);
            } else {
                $stmt = $this->pdo->query("
                    SELECT wa.*, u.name as user_name 
                    FROM warning_actions wa 
                    LEFT JOIN users u ON wa.user_id = u.id 
                    ORDER BY wa.created_at DESC
                ");
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Utility Methods for the Warning/Blocking System
    public function getModerationStats()
    {
        try {
            $stats = [];
            
            // Warning stats
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM user_warnings WHERE is_active = 1");
            $stats['active_warnings'] = $stmt->fetchColumn();
            
            // Block stats
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM user_blocks WHERE is_active = 1");
            $stats['active_blocks'] = $stmt->fetchColumn();
            
            // Suspension stats
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM user_suspensions WHERE is_active = 1");
            $stats['active_suspensions'] = $stmt->fetchColumn();
            
            // Report stats
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM user_reports WHERE status = 'pending'");
            $stats['pending_reports'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (PDOException $e) {
            return [
                'active_warnings' => 0,
                'active_blocks' => 0,
                'active_suspensions' => 0,
                'pending_reports' => 0
            ];
        }
    }

    public function cleanupExpiredSuspensions()
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE user_suspensions 
                SET is_active = 0 
                WHERE suspension_type = 'temporary' 
                AND suspended_until < NOW() 
                AND is_active = 1
            ");
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}