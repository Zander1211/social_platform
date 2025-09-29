<?php

class PostController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllPosts($q = null, $filter = null)
    {
        // Check which columns exist in the posts table
        $hasModernSchema = $this->hasModernSchema();
        
        $params = [];
        $whereConditions = [];
        
        if ($hasModernSchema) {
            // Modern schema with title and content columns
            $sql = "SELECT p.id, COALESCE(p.title, '') AS title, COALESCE(p.content, '') AS content, p.created_at, u.name AS author, u.role
                    FROM posts p
                    LEFT JOIN users u ON p.user_id = u.id";
            
            // Search query
            if ($q) { 
                $whereConditions[] = "(p.title LIKE :q)";
                $params[':q'] = "%".$q."%"; 
            }
        } else {
            // Legacy schema with caption column only
            $sql = "SELECT p.id, '' AS title, COALESCE(p.caption, '') AS content, p.created_at, u.name AS author, u.role
                    FROM posts p
                    LEFT JOIN users u ON p.user_id = u.id";
            
            // Search query
            if ($q) { 
                $whereConditions[] = "(p.title LIKE :q)";
                $params[':q'] = "%".$q."%"; 
            }
        }
        
        // Apply filters (same for both schemas)
        if ($filter) {
            switch ($filter) {
                case 'day':
                case 'recent':
                    $whereConditions[] = "p.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
                    break;
                case 'week':
                    $whereConditions[] = "p.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                    break;
                case 'month':
                    $whereConditions[] = "p.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                    break;
                case 'admin':
                    $whereConditions[] = "u.role = 'admin'";
                    break;
                case 'following':
                    // This would require a followers table, for now just show user's own posts
                    if (isset($_SESSION['user_id'])) {
                        $whereConditions[] = "p.user_id = :user_id";
                        $params[':user_id'] = $_SESSION['user_id'];
                    }
                    break;
            }
        }
        
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (PDOException $e) {
            // If there's still an error, return empty array
            return [];
        } catch (Exception $e) {
            // If there's still an error, return empty array
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

    public function createPost($data)
    {
        // Detect available columns
        $cols = [];
        try {
            $q = $this->pdo->query('SHOW COLUMNS FROM posts');
            $cols = $q ? $q->fetchAll(PDO::FETCH_COLUMN) : [];
        } catch (PDOException $e) { 
            $cols = []; 
        } catch (Exception $e) { 
            $cols = []; 
        }

        $params = [
            ':user_id' => $data['user_id'] ?? null,
        ];
        $hasCreatedAt = in_array('created_at', $cols, true);

        if (in_array('title', $cols, true) && in_array('content', $cols, true)) {
            $columnsSql = 'user_id, title, content' . ($hasCreatedAt ? ', created_at' : '');
            $placeholders = ':user_id, :title, :content' . ($hasCreatedAt ? ', NOW()' : '');
            $sql = "INSERT INTO posts ($columnsSql) VALUES ($placeholders)";
            $params[':title'] = $data['title'] ?? '';
            $params[':content'] = $data['content'] ?? '';
        } else {
            // Legacy schema: caption
            $columnsSql = 'user_id, caption' . ($hasCreatedAt ? ', created_at' : '');
            $placeholders = ':user_id, :caption' . ($hasCreatedAt ? ', NOW()' : '');
            $sql = "INSERT INTO posts ($columnsSql) VALUES ($placeholders)";
            // Combine title + content into caption if both provided
            $caption = trim(($data['title'] ?? ''));
            $body = trim($data['content'] ?? '');
            if ($body !== '') { $caption = ($caption !== '' ? ($caption . "\n\n" . $body) : $body); }
            $params[':caption'] = $caption;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$this->pdo->lastInsertId();
    }

    public function deletePost($postId)
    {
        $stmt = $this->pdo->prepare('DELETE FROM posts WHERE id = :id');
        return $stmt->execute([':id' => $postId]);
    }
    
    public function getPostStats()
    {
        try {
            $stats = [];
            
            // Total posts
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM posts");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total'] = $result['total'] ?? 0;
            
            // Recent posts (24 hours)
            $stmt = $this->pdo->query("SELECT COUNT(*) as recent FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['recent'] = $result['recent'] ?? 0;
            
            // Posts this week
            $stmt = $this->pdo->query("SELECT COUNT(*) as week FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['week'] = $result['week'] ?? 0;
            
            return $stats;
        } catch (PDOException $e) {
            return ['total' => 0, 'recent' => 0, 'week' => 0];
        } catch (Exception $e) {
            return ['total' => 0, 'recent' => 0, 'week' => 0];
        }
    }
}