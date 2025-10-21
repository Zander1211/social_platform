<?php
// Web-accessible script to create the password reset table
// Run this once by visiting: http://localhost/social_platform/social-platform-school/public/create_password_table.php

echo "<h1>Database Table Creation</h1>";
echo "<p>Creating password_reset_tokens table...</p>";

try {
    // Include database configuration
    require_once '../config/database.php';
    
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
    echo "<div style='color: green; font-weight: bold;'>✅ SUCCESS: password_reset_tokens table created successfully!</div>";
    echo "<br>";
    
    // Verify the table was created
    $result = $pdo->query("SHOW TABLES LIKE 'password_reset_tokens'");
    if ($result->rowCount() > 0) {
        echo "<div style='color: green;'>✅ Table verification: password_reset_tokens exists in database</div>";
        echo "<br>";
        
        // Show table structure
        $structure = $pdo->query("DESCRIBE password_reset_tokens");
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<div style='color: red;'>❌ Table verification failed</div>";
    }
    
    echo "<br><hr><br>";
    echo "<h3>🎉 Forgot Password Functionality is Ready!</h3>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Go to the <a href='login.php'>login page</a></li>";
    echo "<li>Click 'Forgot your password?'</li>";
    echo "<li>Enter a valid email address from your users table</li>";
    echo "<li>The system will show you a reset link (for testing purposes)</li>";
    echo "<li>Click the reset link to set a new password</li>";
    echo "</ol>";
    
    echo "<p><strong>Available test emails from your database:</strong></p>";
    $users = $pdo->query("SELECT email FROM users LIMIT 5");
    echo "<ul>";
    while ($user = $users->fetch(PDO::FETCH_ASSOC)) {
        echo "<li>" . htmlspecialchars($user['email']) . "</li>";
    }
    echo "</ul>";
    
    echo "<br><p style='color: #666; font-size: 12px;'>You can delete this file (create_password_table.php) after running it once.</p>";
    
} catch (PDOException $e) {
    echo "<div style='color: red; font-weight: bold;'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<br>";
    echo "<p><strong>Please check:</strong></p>";
    echo "<ul>";
    echo "<li>MySQL/MariaDB is running</li>";
    echo "<li>Database 'social_platform' exists</li>";
    echo "<li>Database credentials are correct in config/database.php</li>";
    echo "</ul>";
}
?>