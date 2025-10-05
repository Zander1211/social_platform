c<?php
// Test script to verify suspension functionality
require_once 'config/database.php';
require_once 'src/Controller/AdminController.php';

session_start();

$adminController = new AdminController($pdo);

echo "<h2>Suspension System Test</h2>";

// Test 1: Check table structure
echo "<h3>1. Database Schema Check</h3>";
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM user_suspensions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<strong>user_suspensions table columns:</strong><br>";
    foreach ($columns as $column) {
        echo "- " . htmlspecialchars($column) . "<br>";
    }
    
    $hasModernSchema = in_array('suspended_by_user_id', $columns) && in_array('suspension_type', $columns);
    echo "<br><strong>Modern schema:</strong> " . ($hasModernSchema ? "✅ Yes" : "❌ No") . "<br><br>";
    
} catch (Exception $e) {
    echo "<strong style='color: red;'>Error checking schema: " . htmlspecialchars($e->getMessage()) . "</strong><br>";
}

// Test 2: Check existing suspensions
echo "<h3>2. Existing Suspensions Check</h3>";
try {
    $suspensions = $adminController->getAllSuspensions();
    echo "<strong>Total active suspensions:</strong> " . count($suspensions) . "<br><br>";
    
    if (!empty($suspensions)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>User</th><th>Type</th><th>Until</th><th>Reason</th><th>Created</th></tr>";
        foreach ($suspensions as $suspension) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($suspension['id']) . "</td>";
            echo "<td>" . htmlspecialchars($suspension['user_name'] ?? 'Unknown') . "</td>";
            echo "<td>" . htmlspecialchars($suspension['suspension_type'] ?? 'temporary') . "</td>";
            echo "<td>" . ($suspension['suspended_until'] ? htmlspecialchars($suspension['suspended_until']) : 'Permanent') . "</td>";
            echo "<td>" . htmlspecialchars($suspension['reason']) . "</td>";
            echo "<td>" . htmlspecialchars($suspension['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "No active suspensions found.<br>";
    }
    
} catch (Exception $e) {
    echo "<strong style='color: red;'>Error fetching suspensions: " . htmlspecialchars($e->getMessage()) . "</strong><br>";
}

// Test 3: Test suspension check for each user
echo "<h3>3. User-Specific Suspension Test</h3>";
try {
    $users = $adminController->getAllUsers(10);
    
    foreach ($users as $user) {
        echo "<strong>User:</strong> " . htmlspecialchars($user['name']) . " (ID: " . $user['id'] . ")<br>";
        
        $isSuspended = $adminController->isUserSuspended($user['id']);
        $userSuspension = $adminController->getUserSuspension($user['id']);
        
        echo "Suspended: " . ($isSuspended ? "✅ Yes" : "❌ No") . "<br>";
        
        if ($userSuspension) {
            echo "Suspension details:<br>";
            echo "- Type: " . htmlspecialchars($userSuspension['suspension_type'] ?? 'temporary') . "<br>";
            echo "- Until: " . ($userSuspension['suspended_until'] ? htmlspecialchars($userSuspension['suspended_until']) : 'Permanent') . "<br>";
            echo "- Reason: " . htmlspecialchars($userSuspension['reason']) . "<br>";
        }
        echo "<br>";
    }
    
} catch (Exception $e) {
    echo "<strong style='color: red;'>Error testing user suspensions: " . htmlspecialchars($e->getMessage()) . "</strong><br>";
}

// Test 4: Test cleanup function
echo "<h3>4. Cleanup Test</h3>";
try {
    $result = $adminController->cleanupExpiredSuspensions();
    echo "<strong>Cleanup result:</strong> " . ($result ? "✅ Success" : "❌ Failed") . "<br>";
    
} catch (Exception $e) {
    echo "<strong style='color: red;'>Error testing cleanup: " . htmlspecialchars($e->getMessage()) . "</strong><br>";
}

echo "<hr>";
echo "<h3>Instructions</h3>";
echo "<strong>To test suspensions:</strong><br>";
echo "1. Go to admin.php<br>";
echo "2. Navigate to the Users tab<br>";
echo "3. Click 'Suspend' on a user<br>";
echo "4. Fill out the suspension form<br>";
echo "5. Login as that user to see the suspension notice<br>";
echo "6. Check if the suspension appears on index.php<br>";
?>