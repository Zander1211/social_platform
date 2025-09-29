<?php

class UserController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function register($data)
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute([':email' => $data['email']]);
        if ($stmt->fetch()) {
            return ['status' => 'error', 'message' => 'Email already registered'];
        }
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $ins = $this->pdo->prepare('INSERT INTO users (name, email, password, role, contact_number, created_at) VALUES (:name, :email, :password, :role, :contact, NOW())');
        $ok = $ins->execute([
            ':name' => $data['name'] ?? '',
            ':email' => $data['email'],
            ':password' => $hash,
            ':role' => $data['role'] ?? 'student',
            ':contact' => $data['contact_number'] ?? null,
        ]);
        return $ok ? ['status' => 'success'] : ['status' => 'error'];
    }

    public function login($email, $password)
    {
        $stmt = $this->pdo->prepare('SELECT id, password, role FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            return ['status' => 'success'];
        }
        return ['status' => 'error', 'message' => 'Invalid email or password.'];
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        return ['status' => 'success'];
    }

    public function editProfile($userId, $data)
    {
        // Check if new profile fields exist in the database
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM users LIKE 'student_id'");
            $hasNewFields = $stmt->rowCount() > 0;
        } catch (Exception $e) {
            $hasNewFields = false;
        }
        
        $fields = [];
        $params = [':id' => $userId];
        
        // Basic fields (always available)
        if (isset($data['name'])) { $fields[] = 'name = :name'; $params[':name'] = $data['name']; }
        if (isset($data['email'])) { $fields[] = 'email = :email'; $params[':email'] = $data['email']; }
        if (isset($data['contact_number'])) { $fields[] = 'contact_number = :contact'; $params[':contact'] = $data['contact_number']; }
        
        // New student profile fields (only if columns exist)
        if ($hasNewFields) {
            if (isset($data['student_id'])) { $fields[] = 'student_id = :student_id'; $params[':student_id'] = $data['student_id']; }
            if (isset($data['date_of_birth'])) { $fields[] = 'date_of_birth = :date_of_birth'; $params[':date_of_birth'] = $data['date_of_birth']; }
            if (isset($data['gender'])) { $fields[] = 'gender = :gender'; $params[':gender'] = $data['gender']; }
            if (isset($data['year_level'])) { $fields[] = 'year_level = :year_level'; $params[':year_level'] = $data['year_level']; }
            if (isset($data['course'])) { $fields[] = 'course = :course'; $params[':course'] = $data['course']; }
            if (isset($data['major'])) { $fields[] = 'major = :major'; $params[':major'] = $data['major']; }
            if (isset($data['bio'])) { $fields[] = 'bio = :bio'; $params[':bio'] = $data['bio']; }
            if (isset($data['hometown'])) { $fields[] = 'hometown = :hometown'; $params[':hometown'] = $data['hometown']; }
            if (isset($data['interests'])) { $fields[] = 'interests = :interests'; $params[':interests'] = $data['interests']; }
            if (isset($data['emergency_contact_name'])) { $fields[] = 'emergency_contact_name = :emergency_contact_name'; $params[':emergency_contact_name'] = $data['emergency_contact_name']; }
            if (isset($data['emergency_contact_phone'])) { $fields[] = 'emergency_contact_phone = :emergency_contact_phone'; $params[':emergency_contact_phone'] = $data['emergency_contact_phone']; }
            if (isset($data['profile_visibility'])) { $fields[] = 'profile_visibility = :profile_visibility'; $params[':profile_visibility'] = $data['profile_visibility']; }
        }
        
        if (empty($fields)) {
            return ['status' => 'error', 'message' => 'No fields to update'];
        }
        
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute($params);
        
        if (!$hasNewFields && count($fields) < count(array_filter($data))) {
            return ['status' => 'partial', 'message' => 'Basic profile updated. Run database migration to enable all profile features.'];
        }
        
        return $ok ? ['status' => 'success'] : ['status' => 'error'];
    }

    public function viewProfile($userId)
    {
        // Check if new profile fields exist in the database
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM users LIKE 'student_id'");
            $hasNewFields = $stmt->rowCount() > 0;
        } catch (Exception $e) {
            $hasNewFields = false;
        }
        
        if ($hasNewFields) {
            // Use the full query with new fields
            $stmt = $this->pdo->prepare('SELECT id, name, email, contact_number, role, student_id, date_of_birth, gender, year_level, course, major, bio, hometown, interests, emergency_contact_name, emergency_contact_phone, profile_visibility FROM users WHERE id = :id');
        } else {
            // Fallback to basic fields only
            $stmt = $this->pdo->prepare('SELECT id, name, email, contact_number, role FROM users WHERE id = :id');
        }
        
        $stmt->execute([':id' => $userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If new fields don't exist, add them as null values for consistency
        if (!$hasNewFields && $profile) {
            $profile['student_id'] = null;
            $profile['date_of_birth'] = null;
            $profile['gender'] = null;
            $profile['year_level'] = null;
            $profile['course'] = null;
            $profile['major'] = null;
            $profile['bio'] = null;
            $profile['hometown'] = null;
            $profile['interests'] = null;
            $profile['emergency_contact_name'] = null;
            $profile['emergency_contact_phone'] = null;
            $profile['profile_visibility'] = 'students_only';
        }
        
        return $profile;
    }

    public function searchUsers($q, $limit = 20)
    {
        $q = trim((string)$q);
        if ($q === '') { return []; }
        $limit = (int)$limit;
        if ($limit <= 0) { $limit = 20; }

        try {
            $sql = "SELECT id, name, role FROM users
                    WHERE name LIKE :q
                    ORDER BY name ASC
                    LIMIT $limit"; // safe because we cast to int
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':q' => "%$q%"]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            // Log error or handle it appropriately
            error_log("Search users error: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            // Log error or handle it appropriately
            error_log("Search users error: " . $e->getMessage());
            return [];
        }
    }

    public function getAllUsers($limit = 50)
    {
        $limit = (int)$limit;
        if ($limit <= 0) { $limit = 50; }
        
        try {
            $sql = "SELECT id, name, email, role FROM users
                    ORDER BY name ASC
                    LIMIT $limit"; // safe because we cast to int
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            // Log error or handle it appropriately
            error_log("Get all users error: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            // Log error or handle it appropriately
            error_log("Get all users error: " . $e->getMessage());
            return [];
        }
    }
}