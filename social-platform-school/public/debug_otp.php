<?php
require_once '../config/database.php';

echo "PDO connected\n";

try {
    $stmt = $pdo->query("SELECT 1");
    echo "Simple query works\n";
    
    $currentDb = $pdo->query('SELECT DATABASE()')->fetchColumn();
    echo "Current DB: $currentDb\n";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'password_reset_otps'");
    $tableExists = $stmt->rowCount() > 0;
    echo "OTP table exists: " . ($tableExists ? 'YES' : 'NO') . "\n";
    
    if ($tableExists) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM password_reset_otps");
        $count = $stmt->fetch()['count'];
        echo "Records before: $count\n";
        
        // Try insert
        $testEmail = 'test@example.com';
        $testOtp = '123456';
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        $stmt = $pdo->prepare('
            INSERT INTO password_reset_otps (email, otp, expires_at) 
            VALUES (:email, :otp, :expires_at)
            ON DUPLICATE KEY UPDATE 
            otp = :otp, expires_at = :expires_at, created_at = NOW()
        ');
        
        $result = $stmt->execute([
            ':email' => $testEmail,
            ':otp' => $testOtp,
            ':expires_at' => $expiresAt
        ]);
        
        echo "Insert result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM password_reset_otps");
        $count = $stmt->fetch()['count'];
        echo "Records after: $count\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>