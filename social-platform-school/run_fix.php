<?php
// Simple fix script - run this to create the missing table
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'social_platform';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
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
    echo "SUCCESS: Table created!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>