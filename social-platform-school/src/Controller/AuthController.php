<?php

// Minimal PDO-backed AuthController
class AuthController
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
            // Check if user is suspended
            $suspensionStmt = $this->pdo->prepare('
                SELECT reason, suspended_until
                FROM user_suspensions
                WHERE user_id = :user_id
                AND is_active = 1
                AND (suspended_until IS NULL OR suspended_until > NOW())
                LIMIT 1
            ');
            $suspensionStmt->execute([':user_id' => $user['id']]);
            $suspension = $suspensionStmt->fetch(PDO::FETCH_ASSOC);

            if ($suspension) {
                $message = 'Your account has been suspended. ';
                if ($suspension['reason']) {
                    $message .= 'Reason: ' . $suspension['reason'] . '. ';
                }
                if ($suspension['suspended_until']) {
                    $message .= 'Suspension ends on: ' . date('F j, Y g:i A', strtotime($suspension['suspended_until'])) . '. ';
                } else {
                    $message .= 'This is a permanent suspension. ';
                }
                $message .= 'Please contact the site administrator for assistance.';

                return ['status' => 'error', 'message' => $message];
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            return ['status' => 'success'];
        }
        return ['status' => 'error', 'message' => 'Invalid credentials'];
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        return ['status' => 'success'];
    }
}