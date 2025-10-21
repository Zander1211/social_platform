<?php
// Simple script to create the password reset table
echo "Creating password_reset_tokens table...\n";

try {
    // Database configuration
    $host = '127.0.0.1';
    $port = 3306;
    $dbname = 'social_platform';
    $username = 'root';
    $password = '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $username, $password, $options);
    
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
    echo "✅ SUCCESS: password_reset_tokens table created successfully!\n";
    echo "\nThe forgot password functionality is now ready to use.\n\n";
    echo "📋 How to test:\n";
    echo "1. Go to the login page\n";
    echo "2. Click 'Forgot your password?'\n";
    echo "3. Enter a valid email address from your users table\n";
    echo "4. The system will show you a reset link (for testing purposes)\n";
    echo "5. Click the reset link to set a new password\n\n";
    
    // Verify the table was created
    $result = $pdo->query("SHOW TABLES LIKE 'password_reset_tokens'");
    if ($result->rowCount() > 0) {
        echo "✅ Table verification: password_reset_tokens exists in database\n";
    } else {
        echo "❌ Table verification failed\n";
    }
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nPlease check:\n";
    echo "1. MySQL/MariaDB is running\n";
    echo "2. Database 'social_platform' exists\n";
    echo "3. Database credentials are correct\n";
}
?>