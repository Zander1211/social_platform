<?php
// Dev helper: print latest OTP for an email (development use only)
require_once __DIR__ . '/../config/database.php';

$email = $argv[1] ?? null;
if (!$email) {
    echo "Usage: php print_latest_otp.php user@example.com\n";
    exit(1);
}

try {
    $stmt = $pdo->prepare('SELECT * FROM password_reset_otps WHERE email = :email ORDER BY created_at DESC LIMIT 1');
    $stmt->execute(['email' => $email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "OTP for {$email}: " . $row['otp'] . " (expires: " . $row['expires_at'] . ")\n";
    } else {
        echo "No OTP found for {$email}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
