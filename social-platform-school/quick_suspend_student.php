<?php
// Quick script to directly suspend a student account for testing
require_once 'config/database.php';

echo "<h2>Quick Student Suspension for Testing</h2>";

try {
    // Get student users
    $stmt = $pdo->query("SELECT id, name, email, role FROM users WHERE role = 'student' LIMIT 5");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($students)) {
        echo "<strong style='color: red;'>No student accounts found!</strong><br>";
        exit();
    }
    
    // Use the first student
    $student = $students[0];
    
    echo "<strong>Suspending student:</strong> " . htmlspecialchars($student['name']) . " (ID: " . $student['id'] . ")<br>";
    echo "<strong>Email:</strong> " . htmlspecialchars($student['email']) . "<br><br>";
    
    // Check if user_suspensions table exists and get its structure
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_suspensions'");
    if ($stmt->rowCount() == 0) {
        echo "<strong style='color: red;'>user_suspensions table does not exist!</strong><br>";
        exit();
    }
    
    // Get table columns
    $stmt = $pdo->query("SHOW COLUMNS FROM user_suspensions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<strong>Available columns:</strong> " . implode(', ', $columns) . "<br><br>";
    
    // Check if modern schema exists
    $hasModernSchema = in_array('suspended_by_user_id', $columns) && in_array('suspension_type', $columns);
    
    if (!$hasModernSchema) {
        echo "<strong style='color: orange;'>Adding missing columns...</strong><br>";
        
        // Add missing columns if they don't exist
        if (!in_array('suspended_by_user_id', $columns)) {
            $pdo->exec("ALTER TABLE user_suspensions ADD COLUMN suspended_by_user_id INT(11) NULL");
            echo "✅ Added suspended_by_user_id column<br>";
        }
        
        if (!in_array('suspension_type', $columns)) {
            $pdo->exec("ALTER TABLE user_suspensions ADD COLUMN suspension_type ENUM('temporary', 'permanent') NOT NULL DEFAULT 'temporary'");
            echo "✅ Added suspension_type column<br>";
        }
        
        if (!in_array('updated_at', $columns)) {
            $pdo->exec("ALTER TABLE user_suspensions ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            echo "✅ Added updated_at column<br>";
        }
    }
    
    // Remove any existing suspensions for this user
    $stmt = $pdo->prepare("UPDATE user_suspensions SET is_active = 0 WHERE user_id = ?");
    $stmt->execute([$student['id']]);
    
    // Insert new suspension
    $suspendedUntil = date('Y-m-d H:i:s', strtotime('+2 hours')); // 2 hours from now
    $reason = "Test suspension to demonstrate the suspension warning system";
    
    if ($hasModernSchema || in_array('suspension_type', $columns)) {
        $stmt = $pdo->prepare("
            INSERT INTO user_suspensions (user_id, suspended_by_user_id, reason, suspension_type, suspended_until, is_active, created_at) 
            VALUES (?, 1, ?, 'temporary', ?, 1, NOW())
        ");
        $stmt->execute([$student['id'], $reason, $suspendedUntil]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO user_suspensions (user_id, reason, suspended_until, is_active, created_at) 
            VALUES (?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$student['id'], $reason, $suspendedUntil]);
    }
    
    echo "<strong style='color: green;'>✅ Student suspended successfully!</strong><br><br>";
    
    echo "<div style='background: #dbeafe; padding: 15px; border-radius: 8px; border-left: 4px solid #3b82f6;'>";
    echo "<strong>Suspension Details:</strong><br>";
    echo "- Student: " . htmlspecialchars($student['name']) . "<br>";
    echo "- Email: " . htmlspecialchars($student['email']) . "<br>";
    echo "- Type: Temporary<br>";
    echo "- Duration: 2 hours<br>";
    echo "- Until: " . $suspendedUntil . "<br>";
    echo "- Reason: " . $reason . "<br>";
    echo "</div><br>";
    
    echo "<div style='background: #fef3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #f59e0b;'>";
    echo "<strong>🎯 To See the Suspension Warning:</strong><br><br>";
    echo "1. <strong>Logout</strong> from your current session<br>";
    echo "2. <strong>Login as this student:</strong><br>";
    echo "   - Email: <code>" . htmlspecialchars($student['email']) . "</code><br>";
    echo "   - Use the student's password<br>";
    echo "3. <strong>You will see:</strong><br>";
    echo "   - 🚨 Large suspension warning banner<br>";
    echo "   - ⏰ Countdown timer (2 hours remaining)<br>";
    echo "   - 📝 Suspension reason and details<br>";
    echo "   - 🔒 Blocked access to main content<br>";
    echo "</div><br>";
    
    echo "<div style='background: #fee2e2; padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444;'>";
    echo "<strong>⚠️ Important:</strong><br>";
    echo "- This is a test suspension that will expire in 2 hours<br>";
    echo "- To remove it immediately, go to admin.php → Suspensions tab → Click 'Unsuspend'<br>";
    echo "- Or run: <code>UPDATE user_suspensions SET is_active = 0 WHERE user_id = " . $student['id'] . "</code><br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<strong style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</strong><br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h2 { color: #1f2937; }
code { background: #f3f4f6; padding: 2px 4px; border-radius: 3px; font-family: monospace; }
</style>