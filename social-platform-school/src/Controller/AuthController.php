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