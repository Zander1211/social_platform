<?php
// Test script to check warning system functionality
require_once 'config/database.php';
require_once 'src/Controller/AdminController.php';

echo "<h2>Warning System Test</h2>";

$adminController = new AdminController($pdo);

// Check database schema
echo "<h3>1. Database Schema Check</h3>";

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM user_warnings");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<strong>user_warnings table columns:</strong><br>";
    foreach ($columns as $column) {
        echo "- " . htmlspecialchars($column) . "<br>";
    }
    
    $hasUserIdColumn = in_array('user_id', $columns);
    $hasWarnedUserIdColumn = in_array('warned_user_id', $columns);
    
    echo "<br><strong>Schema Status:</strong><br>";
    echo "- user_id column: " . ($hasUserIdColumn ? "✅ Present" : "❌ Missing") . "<br>";
    echo "- warned_user_id column: " . ($hasWarnedUserIdColumn ? "✅ Present" : "❌ Missing") . "<br>";
    
    if (!$hasUserIdColumn && !$hasWarnedUserIdColumn) {
        echo "<br><strong style='color: red;'>⚠️ CRITICAL: No user ID columns found! Please run the database migration.</strong><br>";
    }
    
} catch (Exception $e) {
    echo "<strong style='color: red;'>Error checking schema: " . htmlspecialchars($e->getMessage()) . "</strong><br>";
}

// Check existing warnings
echo "<h3>2. Existing Warnings Check</h3>";

try {
    $warnings = $adminController->getAllWarnings();
    echo "<strong>Total active warnings:</strong> " . count($warnings) . "<br><br>";
    
    if (!empty($warnings)) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>User</th><th>Level</th><th>Reason</th><th>Date</th></tr>";
        foreach ($warnings as $warning) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($warning['id']) . "</td>";
            echo "<td>" . htmlspecialchars($warning['user_name'] ?? 'Unknown') . "</td>";
            echo "<td>" . htmlspecialchars($warning['warning_level']) . "</td>";
            echo "<td>" . htmlspecialchars($warning['reason']) . "</td>";
            echo "<td>" . htmlspecialchars($warning['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No active warnings found.<br>";
    }
    
} catch (Exception $e) {
    echo "<strong style='color: red;'>Error fetching warnings: " . htmlspecialchars($e->getMessage()) . "</strong><br>";
}

// Test getUserWarnings for each user
echo "<h3>3. User-Specific Warnings Test</h3>";

try {
    $stmt = $pdo->query("SELECT id, name, email FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        $userWarnings = $adminController->getUserWarnings($user['id']);
        echo "<strong>User: " . htmlspecialchars($user['name']) . " (ID: " . $user['id'] . ")</strong><br>";
        echo "Warnings: " . count($userWarnings) . "<br>";
        
        if (!empty($userWarnings)) {
            foreach ($userWarnings as $warning) {
                echo "- " . htmlspecialchars($warning['warning_level']) . ": " . htmlspecialchars($warning['reason']) . " (" . htmlspecialchars($warning['created_at']) . ")<br>";
            }
        }
        echo "<br>";
    }
    
} catch (Exception $e) {
    echo "<strong style='color: red;'>Error testing user warnings: " . htmlspecialchars($e->getMessage()) . "</strong><br>";
}

// Test warning creation (if admin user exists)
echo "<h3>4. Warning Creation Test</h3>";

try {
    // Find an admin user
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Find a student user
    $stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'student' LIMIT 1");
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && $student) {
        echo "Admin user found (ID: " . $admin['id'] . ")<br>";
        echo "Student user found: " . htmlspecialchars($student['name']) . " (ID: " . $student['id'] . ")<br>";
        
        // Simulate admin session
        session_start();
        $_SESSION['user_id'] = $admin['id'];
        
        // Try to create a test warning
        $result = $adminController->issueWarning($student['id'], 'Test warning from diagnostic script', 'low');
        
        if ($result) {
            echo "<strong style='color: green;'>✅ Test warning created successfully! Warning ID: " . $result . "</strong><br>";
            
            // Verify it appears for the student
            $studentWarnings = $adminController->getUserWarnings($student['id']);
            echo "Student now has " . count($studentWarnings) . " warning(s).<br>";
            
            // Clean up - dismiss the test warning
            $adminController->dismissWarning($result);
            echo "Test warning dismissed.<br>";
        } else {
            echo "<strong style='color: red;'>❌ Failed to create test warning</strong><br>";
        }
        
    } else {
        echo "Cannot test warning creation - need both admin and student users.<br>";
    }
    
} catch (Exception $e) {
    echo "<strong style='color: red;'>Error testing warning creation: " . htmlspecialchars($e->getMessage()) . "</strong><br>";
}

echo "<h3>5. Recommendations</h3>";

if (!$hasUserIdColumn && $hasWarnedUserIdColumn) {
    echo "<strong style='color: orange;'>⚠️ Database migration needed:</strong><br>";
    echo "Run the SQL script: <code>social-platform-school/sql/admin_dashboard_fixes.sql</code><br><br>";
}

echo "<strong>To test warnings:</strong><br>";
echo "1. Login as an admin user<br>";
echo "2. Go to admin.php<br>";
echo "3. Issue a warning to a student user<br>";
echo "4. Login as that student user<br>";
echo "5. Check if the warning appears on index.php<br>";

?>