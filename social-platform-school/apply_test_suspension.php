<?php
// Script to apply a test suspension to a student account
require_once 'config/database.php';
require_once 'src/Controller/AdminController.php';

session_start();

$adminController = new AdminController($pdo);

echo "<h2>Apply Test Suspension to Student Account</h2>";

// First, let's check if we need to update the database schema
echo "<h3>1. Checking Database Schema</h3>";
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM user_suspensions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasModernSchema = in_array('suspended_by_user_id', $columns) && in_array('suspension_type', $columns);
    
    if (!$hasModernSchema) {
        echo "<strong style='color: orange;'>⚠️ Updating database schema...</strong><br>";
        
        // Add missing columns
        try {
            $pdo->exec("ALTER TABLE user_suspensions ADD COLUMN suspended_by_user_id INT(11) NULL AFTER user_id");
            echo "✅ Added suspended_by_user_id column<br>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                echo "❌ Error adding suspended_by_user_id: " . $e->getMessage() . "<br>";
            }
        }
        
        try {
            $pdo->exec("ALTER TABLE user_suspensions ADD COLUMN suspension_type ENUM('temporary', 'permanent') NOT NULL DEFAULT 'temporary' AFTER reason");
            echo "✅ Added suspension_type column<br>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                echo "❌ Error adding suspension_type: " . $e->getMessage() . "<br>";
            }
        }
        
        try {
            $pdo->exec("ALTER TABLE user_suspensions ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
            echo "✅ Added updated_at column<br>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                echo "❌ Error adding updated_at: " . $e->getMessage() . "<br>";
            }
        }
    } else {
        echo "✅ Database schema is up to date<br>";
    }
    
} catch (Exception $e) {
    echo "<strong style='color: red;'>Error checking schema: " . htmlspecialchars($e->getMessage()) . "</strong><br>";
}

echo "<br>";

// Get all users and find a student to suspend
echo "<h3>2. Finding Student Accounts</h3>";
try {
    $users = $adminController->getAllUsers(20);
    $students = array_filter($users, function($user) {
        return $user['role'] === 'student';
    });
    
    if (empty($students)) {
        echo "<strong style='color: red;'>❌ No student accounts found!</strong><br>";
        exit();
    }
    
    echo "<strong>Available student accounts:</strong><br>";
    foreach ($students as $student) {
        $isSuspended = $adminController->isUserSuspended($student['id']);
        echo "- " . htmlspecialchars($student['name']) . " (ID: " . $student['id'] . ") - " . 
             ($isSuspended ? "<span style='color: red;'>Already Suspended</span>" : "<span style='color: green;'>Active</span>") . "<br>";
    }
    
    // Find the first non-suspended student
    $targetStudent = null;
    foreach ($students as $student) {
        if (!$adminController->isUserSuspended($student['id'])) {
            $targetStudent = $student;
            break;
        }
    }
    
    if (!$targetStudent) {
        echo "<br><strong style='color: orange;'>⚠️ All students are already suspended. Using first student anyway.</strong><br>";
        $targetStudent = $students[0];
    }
    
    echo "<br><strong>Selected student for test suspension:</strong> " . htmlspecialchars($targetStudent['name']) . " (ID: " . $targetStudent['id'] . ")<br>";
    
} catch (Exception $e) {
    echo "<strong style='color: red;'>Error finding students: " . htmlspecialchars($e->getMessage()) . "</strong><br>";
    exit();
}

echo "<br>";

// Apply test suspension
echo "<h3>3. Applying Test Suspension</h3>";
try {
    // Find an admin user to use as the suspender
    $adminUsers = array_filter($users, function($user) {
        return $user['role'] === 'admin';
    });
    
    $adminUserId = !empty($adminUsers) ? $adminUsers[0]['id'] : 1; // Fallback to user ID 1
    
    // Set suspension details
    $suspensionReason = "Test suspension to demonstrate the suspension system functionality";
    $suspensionType = "temporary";
    $suspendedUntil = date('Y-m-d H:i:s', strtotime('+1 hour')); // Suspend for 1 hour
    
    echo "<strong>Suspension details:</strong><br>";
    echo "- Student: " . htmlspecialchars($targetStudent['name']) . "<br>";
    echo "- Type: " . $suspensionType . "<br>";
    echo "- Until: " . $suspendedUntil . "<br>";
    echo "- Reason: " . $suspensionReason . "<br>";
    echo "- Suspended by: Admin (ID: " . $adminUserId . ")<br><br>";
    
    // Apply the suspension
    $result = $adminController->suspendUser(
        $targetStudent['id'],
        $adminUserId,
        $suspensionReason,
        $suspensionType,
        $suspendedUntil
    );
    
    if ($result) {
        echo "<strong style='color: green;'>✅ Test suspension applied successfully!</strong><br><br>";
        
        // Verify the suspension
        $userSuspension = $adminController->getUserSuspension($targetStudent['id']);
        if ($userSuspension) {
            echo "<strong>Verification - Suspension details:</strong><br>";
            echo "- ID: " . $userSuspension['id'] . "<br>";
            echo "- Type: " . htmlspecialchars($userSuspension['suspension_type'] ?? 'temporary') . "<br>";
            echo "- Until: " . htmlspecialchars($userSuspension['suspended_until']) . "<br>";
            echo "- Reason: " . htmlspecialchars($userSuspension['reason']) . "<br>";
            echo "- Created: " . htmlspecialchars($userSuspension['created_at']) . "<br>";
        }
        
    } else {
        echo "<strong style='color: red;'>❌ Failed to apply test suspension!</strong><br>";
    }
    
} catch (Exception $e) {
    echo "<strong style='color: red;'>Error applying suspension: " . htmlspecialchars($e->getMessage()) . "</strong><br>";
}

echo "<br>";

// Instructions
echo "<h3>4. How to See the Changes</h3>";
echo "<div style='background: #f0f9ff; padding: 15px; border-radius: 8px; border-left: 4px solid #0ea5e9;'>";
echo "<strong>To see the suspension in action:</strong><br><br>";
echo "1. <strong>Logout from your current admin account</strong><br>";
echo "2. <strong>Login as the suspended student:</strong><br>";
echo "   - Email: " . htmlspecialchars($targetStudent['email']) . "<br>";
echo "   - You'll need the student's password<br><br>";
echo "3. <strong>You should see:</strong><br>";
echo "   - A prominent suspension warning at the top of the page<br>";
echo "   - Orange/yellow background for temporary suspension<br>";
echo "   - Countdown timer showing time remaining<br>";
echo "   - Suspension reason and details<br>";
echo "   - Restricted access to main content<br><br>";
echo "4. <strong>Alternative:</strong> Go to admin.php → Users tab to see the suspension status<br>";
echo "</div>";

echo "<br>";

// Cleanup option
echo "<h3>5. Remove Test Suspension</h3>";
echo "<div style='background: #fef3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #f59e0b;'>";
echo "<strong>To remove the test suspension:</strong><br>";
echo "1. Go to admin.php → Suspensions tab<br>";
echo "2. Click 'Unsuspend' next to the test suspension<br>";
echo "3. Or wait 1 hour for it to expire automatically<br>";
echo "</div>";

echo "<br><hr>";
echo "<p><strong>Note:</strong> This is a temporary 1-hour suspension for testing purposes. The student account will be automatically unsuspended after 1 hour.</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #1f2937; }
h3 { color: #374151; margin-top: 30px; }
</style>