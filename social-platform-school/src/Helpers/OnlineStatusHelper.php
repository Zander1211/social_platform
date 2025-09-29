<?php

class OnlineStatusHelper
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Update user's online status and last seen timestamp
     */
    public function updateUserActivity($userId)
    {
        try {
            $stmt = $this->pdo->prepare('UPDATE users SET is_online = 1, last_seen = NOW() WHERE id = :id');
            $stmt->execute([':id' => $userId]);
        } catch (Exception $e) {
            // Silently fail if columns don't exist yet
        }
    }

    /**
     * Mark user as offline
     */
    public function setUserOffline($userId)
    {
        try {
            $stmt = $this->pdo->prepare('UPDATE users SET is_online = 0, last_seen = NOW() WHERE id = :id');
            $stmt->execute([':id' => $userId]);
        } catch (Exception $e) {
            // Silently fail if columns don't exist yet
        }
    }

    /**
     * Get user's online status and last seen
     */
    public function getUserStatus($userId)
    {
        try {
            $stmt = $this->pdo->prepare('SELECT is_online, last_seen FROM users WHERE id = :id');
            $stmt->execute([':id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'is_online' => (bool)$result['is_online'],
                    'last_seen' => $result['last_seen']
                ];
            }
        } catch (Exception $e) {
            // Return default if columns don't exist
        }
        
        return ['is_online' => false, 'last_seen' => null];
    }

    /**
     * Format last seen time in a human-readable way
     */
    public function formatLastSeen($lastSeen)
    {
        if (!$lastSeen) {
            return 'Never';
        }

        $now = new DateTime();
        $lastSeenDate = new DateTime($lastSeen);
        $diff = $now->diff($lastSeenDate);

        if ($diff->days > 7) {
            return $lastSeenDate->format('M j, Y');
        } elseif ($diff->days > 0) {
            return $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        } else {
            return 'Just now';
        }
    }

    /**
     * Clean up old online statuses (mark users offline if they haven't been seen for 5 minutes)
     */
    public function cleanupOnlineStatuses()
    {
        try {
            $stmt = $this->pdo->prepare('UPDATE users SET is_online = 0 WHERE is_online = 1 AND last_seen < DATE_SUB(NOW(), INTERVAL 5 MINUTE)');
            $stmt->execute();
        } catch (Exception $e) {
            // Silently fail if columns don't exist yet
        }
    }
}