<?php

class EventController
{
    /** @var PDO */
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Create a new event and return the inserted event ID
    public function createEvent(array $data)
    {
        try {
            // Normalize date from HTML datetime-local (YYYY-MM-DDTHH:MM) to MySQL DATETIME
            $eventDate = $data['event_date'] ?? null;
            if ($eventDate) {
                $eventDate = str_replace('T', ' ', $eventDate);
                if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $eventDate)) {
                    $eventDate .= ':00';
                }
            }

            // Discover available columns on the events table to support different schemas
            $cols = [];
            try {
                $q = $this->pdo->query('SHOW COLUMNS FROM events');
                $cols = $q ? $q->fetchAll(PDO::FETCH_COLUMN) : [];
            } catch (Exception $ie) {
                $cols = [];
            }

            // Base required fields present in all variants
            $fields = ['title', 'description', 'event_date'];
            $params = [
                ':title' => $data['title'] ?? null,
                ':description' => $data['description'] ?? null,
                ':event_date' => $eventDate,
            ];

            // Optional fields depending on schema
            if (in_array('location', $cols, true)) {
                $fields[] = 'location';
                $params[':location'] = $data['location'] ?? null;
            }
            if (in_array('banner_path', $cols, true)) {
                $fields[] = 'banner_path';
                $params[':banner_path'] = $data['banner_path'] ?? null;
            }

            // Creator column can be either created_by or user_id
            if (in_array('created_by', $cols, true)) {
                $fields[] = 'created_by';
                $params[':created_by'] = $data['created_by'] ?? null;
            } elseif (in_array('user_id', $cols, true)) {
                $fields[] = 'user_id';
                // Map created_by input to user_id column
                $params[':user_id'] = $data['created_by'] ?? ($data['user_id'] ?? null);
            }

            // created_at can be auto or explicit NOW()
            $appendCreatedAt = in_array('created_at', $cols, true);

            // Build SQL dynamically
            $columnsSql = implode(', ', $fields) . ($appendCreatedAt ? ', created_at' : '');
            $placeholders = implode(', ', array_keys($params)) . ($appendCreatedAt ? ', NOW()' : '');
            $sql = "INSERT INTO events ($columnsSql) VALUES ($placeholders)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return (int)$this->pdo->lastInsertId();
        } catch (Exception $e) {
            // Optionally log: error_log('Event create failed: ' . $e->getMessage());
            return 0;
        }
    }

    // Fetch a single event by ID with RSVP counters
    public function getEvent(int $id)
    {
        $ev = null;
        try {
            $stmt = $this->pdo->prepare('SELECT id, title, description, event_date, location, banner_path, created_by, created_at FROM events WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $ev = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) { $ev = null; }
        if (!$ev) return null;

        $ev['rsvps'] = $this->getRsvpCounts($id);
        return $ev;
    }

    // List events, optionally filter by upcoming only
    public function listEvents(array $filters = [])
    {
        $where = [];
        $params = [];
        if (!empty($filters['upcoming'])) {
            $where[] = 'event_date >= NOW()';
        }
        if (!empty($filters['q'])) {
            $where[] = '(title LIKE :q OR description LIKE :q OR location LIKE :q)';
            $params[':q'] = '%' . $filters['q'] . '%';
        }
        $sql = 'SELECT id, title, description, event_date, location, banner_path, created_by, created_at FROM events';
        if (!empty($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        $sql .= ' ORDER BY event_date DESC';
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // attach RSVP counts
            foreach ($rows as &$r) {
                $r['rsvps'] = $this->getRsvpCounts((int)$r['id']);
            }
            return $rows;
        } catch (Exception $e) {
            return [];
        }
    }

    // Upsert RSVP for a user
    public function rsvp(int $eventId, int $userId, string $status)
    {
        $allowed = ['going', 'interested', 'not_going'];
        if (!in_array($status, $allowed, true)) return false;
        try {
            $sql = 'INSERT INTO event_rsvps (event_id, user_id, status, created_at) VALUES (:eid, :uid, :st, NOW())
                    ON DUPLICATE KEY UPDATE status = VALUES(status)';
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':eid' => $eventId, ':uid' => $userId, ':st' => $status]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function getRsvps(int $eventId)
    {
        try {
            $stmt = $this->pdo->prepare('SELECT er.user_id, er.status, u.name FROM event_rsvps er JOIN users u ON u.id = er.user_id WHERE er.event_id = :eid ORDER BY er.created_at DESC');
            $stmt->execute([':eid' => $eventId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    private function getRsvpCounts(int $eventId)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT status, COUNT(*) as n FROM event_rsvps WHERE event_id = :eid GROUP BY status");
            $stmt->execute([':eid' => $eventId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = ['going' => 0, 'interested' => 0, 'not_going' => 0];
            foreach ($rows as $r) { $out[$r['status']] = (int)$r['n']; }
            return $out;
        } catch (Exception $e) { return ['going' => 0, 'interested' => 0, 'not_going' => 0]; }
    }
}
