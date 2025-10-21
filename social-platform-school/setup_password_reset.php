<?php
// Setup script to create the password reset table
require_once 'config/database.php';

try {
    // Create the password reset tokens table
    $sql = "
    CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    $pdo->exec($sql);
    echo "✅ Password reset table created successfully!\n";
    echo "The forgot password functionality is now ready to use.\n\n";
    echo "📋 How to test:\n";
    echo "1. Go to the login page\n";
    echo "2. Click 'Forgot your password?'\n";
    echo "3. Enter a valid email address from your users table\n";
    echo "4. The system will show you a reset link (for testing purposes)\n";
    echo "5. Click the reset link to set a new password\n\n";
    echo "🔧 For production use:\n";
    echo "- Remove the token display from forgot_password.php\n";
    echo "- Implement email sending functionality\n";
    echo "- Consider adding rate limiting for reset requests\n";
    
} catch (PDOException $e) {
    echo "❌ Error creating table: " . $e->getMessage() . "\n";
}
?>