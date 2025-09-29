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
                       u.name AS author
                FROM posts p
                LEFT JOIN users u ON p.user_id = u.id
                ORDER BY p.created_at DESC";
        } else {
            // Legacy schema with caption column only
            $sql = "SELECT p.id,
                       '' AS title,
                       COALESCE(p.caption, '') AS content,
                       p.created_at,
                       u.name AS author
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

    // User behavior reporting and management
    public function reportUser($reportedUserId, $reporterUserId, $reason, $description = '')
    {
        try {
            // Create user_reports table if it doesn't exist
            $this->createUserReportsTable();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO user_reports (reported_user_id, reporter_user_id, reason, description, status, created_at) 
                VALUES (:reported_user_id, :reporter_user_id, :reason, :description, 'pending', NOW())
            ");
            
            return $stmt->execute([
                ':reported_user_id' => $reportedUserId,
                ':reporter_user_id' => $reporterUserId,
                ':reason' => $reason,
                ':description' => $description
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function getAllUserReports($limit = 100)
    {
        try {
            $this->createUserReportsTable();
            
            $limit = (int)$limit; 
            if ($limit <= 0) { $limit = 100; }
            
            $sql = "
                SELECT ur.id, ur.reason, ur.description, ur.status, ur.created_at,
                       reported.name AS reported_user_name, reported.email AS reported_user_email,
                       reporter.name AS reporter_name, reporter.email AS reporter_email,
                       ur.reported_user_id, ur.reporter_user_id
                FROM user_reports ur
                LEFT JOIN users reported ON ur.reported_user_id = reported.id
                LEFT JOIN users reporter ON ur.reporter_user_id = reporter.id
                ORDER BY ur.created_at DESC
                LIMIT $limit
            ";
            
            $stmt = $this->pdo->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function updateReportStatus($reportId, $status)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE user_reports SET status = :status WHERE id = :id");
            return $stmt->execute([':status' => $status, ':id' => $reportId]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function suspendUser($userId, $reason = '', $duration = null)
    {
        try {
            // Create user_suspensions table if it doesn't exist
            $this->createUserSuspensionsTable();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO user_suspensions (user_id, reason, suspended_until, created_at) 
                VALUES (:user_id, :reason, :suspended_until, NOW())
            ");
            
            $suspendedUntil = $duration ? date('Y-m-d H:i:s', strtotime($duration)) : null;
            
            return $stmt->execute([
                ':user_id' => $userId,
                ':reason' => $reason,
                ':suspended_until' => $suspendedUntil
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function unsuspendUser($userId)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM user_suspensions WHERE user_id = :user_id");
            return $stmt->execute([':user_id' => $userId]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function getUserSuspensions($limit = 100)
    {
        try {
            $this->createUserSuspensionsTable();
            
            $limit = (int)$limit;
            if ($limit <= 0) { $limit = 100; }
            
            $sql = "
                SELECT us.id, us.user_id, us.reason, us.suspended_until, us.created_at,
                       u.name AS user_name, u.email AS user_email
                FROM user_suspensions us
                LEFT JOIN users u ON us.user_id = u.id
                ORDER BY us.created_at DESC
                LIMIT $limit
            ";
            
            $stmt = $this->pdo->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function isUserSuspended($userId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM user_suspensions 
                WHERE user_id = :user_id 
                AND (suspended_until IS NULL OR suspended_until > NOW())
            ");
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getUserActivityStats($userId)
    {
        try {
            $stats = [];
            
            // Posts count
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            $stats['posts_count'] = $stmt->fetchColumn();
            
            // Comments count
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            $stats['comments_count'] = $stmt->fetchColumn();
            
            // Reports against this user
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_reports WHERE reported_user_id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            $stats['reports_count'] = $stmt->fetchColumn();

            // Warnings count against this user
            try {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_warnings WHERE warned_user_id = :user_id");
                $stmt->execute([':user_id' => $userId]);
                $stats['warnings_count'] = $stmt->fetchColumn();
            } catch (Exception $e) {
                $stats['warnings_count'] = 0;
            }
            
            // Last activity
            $stmt = $this->pdo->prepare("
                SELECT MAX(created_at) as last_activity FROM (
                    SELECT created_at FROM posts WHERE user_id = :user_id
                    UNION ALL
                    SELECT created_at FROM comments WHERE user_id = :user_id
                ) as activities
            ");
            $stmt->execute([':user_id' => $userId]);
            $stats['last_activity'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (Exception $e) {
            return ['posts_count' => 0, 'comments_count' => 0, 'reports_count' => 0, 'last_activity' => null];
        }
    }

    // Warnings management
    public function warnUser($warnedUserId, $adminUserId, $reason = '', $notes = '')
    {
        try {
            $this->createUserWarningsTable();
            $stmt = $this->pdo->prepare("INSERT INTO user_warnings (warned_user_id, warned_by_user_id, reason, notes, created_at) VALUES (:warned_user_id, :warned_by_user_id, :reason, :notes, NOW())");
            return $stmt->execute([
                ':warned_user_id' => $warnedUserId,
                ':warned_by_user_id' => $adminUserId,
                ':reason' => $reason,
                ':notes' => $notes
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function getAllWarnings($limit = 100)
    {
        try {
            $this->createUserWarningsTable();
            $limit = (int)$limit; if ($limit <= 0) { $limit = 100; }
            $sql = "SELECT uw.id, uw.warned_user_id, uw.warned_by_user_id, uw.reason, uw.notes, uw.created_at, u.name AS warned_user_name, a.name AS warned_by_name FROM user_warnings uw LEFT JOIN users u ON uw.warned_user_id = u.id LEFT JOIN users a ON uw.warned_by_user_id = a.id ORDER BY uw.created_at DESC LIMIT $limit";
            $stmt = $this->pdo->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function getUserWarnings($userId, $limit = 100)
    {
        try {
            $this->createUserWarningsTable();
            $limit = (int)$limit; if ($limit <= 0) { $limit = 100; }
            $sql = "SELECT uw.id, uw.reason, uw.notes, uw.created_at, a.name AS warned_by_name FROM user_warnings uw LEFT JOIN users a ON uw.warned_by_user_id = a.id WHERE uw.warned_user_id = :user_id ORDER BY uw.created_at DESC LIMIT $limit";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Exception $e) {
            return [];
        }
    }

    private function createUserWarningsTable()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS user_warnings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                warned_user_id INT NOT NULL,
                warned_by_user_id INT NOT NULL,
                reason VARCHAR(255) DEFAULT NULL,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_warned_user (warned_user_id),
                INDEX idx_warned_by (warned_by_user_id)
            )";
            $this->pdo->exec($sql);
        } catch (Exception $e) {
            // ignore
        }
    }

    private function createUserReportsTable()
    {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS user_reports (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    reported_user_id INT NOT NULL,
                    reporter_user_id INT NOT NULL,
                    reason VARCHAR(255) NOT NULL,
                    description TEXT,
                    status ENUM('pending', 'reviewed', 'resolved', 'dismissed') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_reported_user (reported_user_id),
                    INDEX idx_reporter (reporter_user_id),
                    INDEX idx_status (status)
                )
            ";
            $this->pdo->exec($sql);
        } catch (Exception $e) {
            // Table might already exist or there might be permission issues
        }
    }

    private function createUserSuspensionsTable()
    {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS user_suspensions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    reason TEXT,
                    suspended_until TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user (user_id)
                )
            ";
            $this->pdo->exec($sql);
        } catch (Exception $e) {
            // Table might already exist or there might be permission issues
        }
    }
}