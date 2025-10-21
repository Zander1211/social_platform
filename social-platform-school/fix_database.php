<?php
// Emergency fix script to create the missing table
echo "=== EMERGENCY DATABASE FIX ===\n";
echo "Creating missing password_reset_tokens table...\n\n";

// Direct database connection (using same config as your app)
$host = '127.0.0.1';
$port = 3306;
$dbname = 'social_platform';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to database successfully\n";
    
    // Create the table
    $sql = "CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
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
    
    $pdo->exec($sql);
    echo "✓ Table 'password_reset_tokens' created successfully\n";
    
    // Verify table exists
    $check = $pdo->query("SHOW TABLES LIKE 'password_reset_tokens'");
    if ($check->rowCount() > 0) {
        echo "✓ Table verification passed\n";
        echo "\n=== SUCCESS ===\n";
        echo "The forgot password functionality should now work!\n";
        echo "You can now test it by going to the login page.\n";
    } else {
        echo "✗ Table verification failed\n";
    }
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Make sure XAMPP MySQL is running\n";
    echo "2. Check if database 'social_platform' exists\n";
    echo "3. Verify MySQL credentials\n";
}
?>