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

    public function requestPasswordReset($email)
    {
        // Ensure password reset table exists
        $this->ensurePasswordResetTableExists();
        
        // Check if user exists
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            // For security reasons, don't reveal if email exists or not
            // Always return success message to prevent email enumeration
            return [
                'status' => 'success', 
                'message' => 'If an account with that email address exists, you will receive password reset instructions shortly.',
                'message_type' => 'security_neutral'
            ];
        }

        // Generate a secure token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour

        // Store the token in database
        $stmt = $this->pdo->prepare('
            INSERT INTO password_reset_tokens (email, token, expires_at) 
            VALUES (:email, :token, :expires_at)
        ');
        $result = $stmt->execute([
            ':email' => $email,
            ':token' => $token,
            ':expires_at' => $expiresAt
        ]);

        if ($result) {
            // Try to send email with OTP
            $emailSent = false;
            $emailError = null;
            $otp = null;
            
            try {
                require_once __DIR__ . '/../Service/EmailService.php';
                $emailService = new EmailService();
                
                // Generate OTP for additional security
                $otp = $emailService->generateOTP();
                
                // Store OTP in database for verification (optional)
                $this->storeOTP($email, $otp);
                
                // Send email with both reset link and OTP
                $emailSent = $emailService->sendPasswordResetEmail($email, $token, $otp);
                
            } catch (Exception $e) {
                $emailError = $e->getMessage();
                error_log('Email sending failed: ' . $e->getMessage());
            }
            
            // Determine response based on email status
            if ($emailSent) {
                // Email was successfully sent
                return [
                    'status' => 'success',
                    'message' => 'Password reset instructions have been sent to your email address.',
                    'message_type' => 'email_sent',
                    'details' => 'Please check your inbox and spam folder. The reset link will expire in 1 hour.'
                ];
            } else {
                // Email service not configured or failed - use development mode
                $isDevelopment = (defined('APP_ENV') && APP_ENV === 'development') || 
                                (!defined('APP_ENV') && $_SERVER['SERVER_NAME'] === 'localhost');
                
                if ($isDevelopment) {
                    // Development mode - show test link
                    return [
                        'status' => 'success',
                        'message' => 'Development Mode: Email service not configured.',
                        'message_type' => 'development_mode',
                        'token' => $token,
                        'details' => 'In production, this would be sent via email. Use the test link below to continue.'
                    ];
                } else {
                    // Production mode - log error but show generic message
                    error_log('Password reset email failed to send for: ' . $email);
                    return [
                        'status' => 'error',
                        'message' => 'We\'re experiencing technical difficulties sending emails.',
                        'message_type' => 'email_error',
                        'details' => 'Please try again in a few minutes or contact support if the problem persists.'
                    ];
                }
            }
        }

        return ['status' => 'error', 'message' => 'Failed to generate reset token'];
    }

    public function validateResetToken($token)
    {
        // Ensure password reset table exists
        $this->ensurePasswordResetTableExists();
        
        $stmt = $this->pdo->prepare('
            SELECT email, expires_at, used 
            FROM password_reset_tokens 
            WHERE token = :token
        ');
        $stmt->execute([':token' => $token]);
        $resetToken = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resetToken) {
            return [
                'status' => 'error', 
                'message' => 'Invalid or expired reset link',
                'details' => 'This reset link is not valid. Please request a new password reset.'
            ];
        }

        if ($resetToken['used']) {
            return [
                'status' => 'error', 
                'message' => 'Reset link already used',
                'details' => 'This reset link has already been used. Please request a new password reset if needed.'
            ];
        }

        if (strtotime($resetToken['expires_at']) < time()) {
            return [
                'status' => 'error', 
                'message' => 'Reset link has expired',
                'details' => 'This reset link has expired for security reasons. Please request a new password reset.'
            ];
        }

        return ['status' => 'success', 'email' => $resetToken['email']];
    }

    public function resetPassword($token, $newPassword)
    {
        // Validate token first
        $tokenValidation = $this->validateResetToken($token);
        if ($tokenValidation['status'] !== 'success') {
            return $tokenValidation;
        }

        $email = $tokenValidation['email'];

        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        // Update user password
        $stmt = $this->pdo->prepare('UPDATE users SET password = :password WHERE email = :email');
        $passwordUpdated = $stmt->execute([
            ':password' => $hashedPassword,
            ':email' => $email
        ]);

        if ($passwordUpdated) {
            // Mark token as used
            $stmt = $this->pdo->prepare('UPDATE password_reset_tokens SET used = 1 WHERE token = :token');
            $stmt->execute([':token' => $token]);

            return [
                'status' => 'success', 
                'message' => 'Password reset successful!',
                'details' => 'Your password has been updated. You can now log in with your new password.'
            ];
        }

        return [
            'status' => 'error', 
            'message' => 'Password reset failed',
            'details' => 'We encountered an error while updating your password. Please try again or contact support.'
        ];
    }
    
    /**
     * Ensure the password reset table exists, create it if it doesn't
     */
    private function ensurePasswordResetTableExists()
    {
        try {
            // Check if table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'password_reset_tokens'");
            if ($stmt->rowCount() == 0) {
                // Table doesn't exist, create it
                $sql = "CREATE TABLE `password_reset_tokens` (
                    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `email` varchar(150) NOT NULL,
                    `token` varchar(255) NOT NULL,
                    `expires_at` timestamp NOT NULL,
                    `used` tinyint(1) NOT NULL DEFAULT 0,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    KEY `idx_email` (`email`),
                    KEY `idx_token` (`token`),
                    KEY `idx_expires` (`expires_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
                
                $this->pdo->exec($sql);
            }
        } catch (PDOException $e) {
            // If table creation fails, log the error but don't break the application
            error_log('Failed to create password_reset_tokens table: ' . $e->getMessage());
        }
    }
    
    /**
     * Store OTP for verification (optional feature)
     * 
     * @param string $email
     * @param string $otp
     */
    private function storeOTP($email, $otp)
    {
        try {
            // Create OTP table if it doesn't exist
            $this->ensureOTPTableExists();
            
            error_log("storeOTP called for email: $email, otp: $otp");
            
            // Store OTP with 10-minute expiration
            $stmt = $this->pdo->prepare('
                INSERT INTO password_reset_otps (email, otp, expires_at) 
                VALUES (:email, :otp, :expires_at)
                ON DUPLICATE KEY UPDATE 
                otp = :otp_update, expires_at = :expires_update, created_at = NOW()
            ');
            
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            $result = $stmt->execute([
                ':email' => $email,
                ':otp' => $otp,
                ':expires_at' => $expiresAt,
                ':otp_update' => $otp,
                ':expires_update' => $expiresAt
            ]);
            
            error_log("storeOTP execute result: " . ($result ? 'true' : 'false') . ", rowCount: " . $stmt->rowCount());
            
        } catch (PDOException $e) {
            error_log('Failed to store OTP: ' . $e->getMessage());
        }
    }
    
    /**
     * Verify OTP code
     * 
     * @param string $email
     * @param string $otp
     * @return array
     */
    public function verifyOTP($email, $otp)
    {
        try {
            $this->ensureOTPTableExists();
            // Normalize inputs
            $email = trim(strtolower($email));
            $otp = trim($otp);

            // First try to fetch by email
            $stmt = $this->pdo->prepare('
                SELECT otp, expires_at 
                FROM password_reset_otps 
                WHERE LOWER(email) = :email_lower
                LIMIT 1
            ');
            $stmt->execute([':email_lower' => strtolower($email)]);
            $otpRecord = $stmt->fetch(PDO::FETCH_ASSOC);

            // If not found or otp mismatch, provide clearer logging for development
            if (!$otpRecord) {
                if ((isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'localhost') || (getenv('APP_ENV') === 'development')) {
                    error_log("verifyOTP: no OTP record found for email={$email}");
                }
                return [
                    'status' => 'error',
                    'message' => 'Invalid OTP code',
                    'details' => 'The OTP code you entered is incorrect or has expired.'
                ];
            }

            // Compare provided OTP with stored OTP (preserve leading zeros)
            if ((string)$otpRecord['otp'] !== (string)$otp) {
                if ((isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'localhost') || (getenv('APP_ENV') === 'development')) {
                    error_log("verifyOTP: OTP mismatch for email={$email} provided=[{$otp}] stored=[{$otpRecord['otp']}] expires_at={$otpRecord['expires_at']}");
                }
                return [
                    'status' => 'error',
                    'message' => 'Invalid OTP code',
                    'details' => 'The OTP code you entered is incorrect or has expired.'
                ];
            }
            
            if (!$otpRecord) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid OTP code',
                    'details' => 'The OTP code you entered is incorrect or has expired.'
                ];
            }
            
            if (strtotime($otpRecord['expires_at']) < time()) {
                return [
                    'status' => 'error',
                    'message' => 'OTP code has expired',
                    'details' => 'Please request a new password reset to get a fresh OTP code.'
                ];
            }
            
            // Mark OTP as used by deleting it
            $stmt = $this->pdo->prepare('DELETE FROM password_reset_otps WHERE email = :email');
            $stmt->execute([':email' => $email]);
            
            return [
                'status' => 'success',
                'message' => 'OTP verified successfully'
            ];
            
        } catch (PDOException $e) {
            error_log('OTP verification failed: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Verification failed',
                'details' => 'Please try again or contact support.'
            ];
        }
    }
    
    /**
     * Ensure the OTP table exists
     */
    private function ensureOTPTableExists()
    {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'password_reset_otps'");
            if ($stmt->rowCount() == 0) {
                $sql = "CREATE TABLE `password_reset_otps` (
                    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `email` varchar(150) NOT NULL,
                    `otp` varchar(10) NOT NULL,
                    `expires_at` timestamp NOT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_email` (`email`),
                    KEY `idx_otp` (`otp`),
                    KEY `idx_expires` (`expires_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
                
                $this->pdo->exec($sql);
            }
        } catch (PDOException $e) {
            error_log('Failed to create OTP table: ' . $e->getMessage());
        }
    }
}