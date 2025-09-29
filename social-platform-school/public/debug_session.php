<?php
session_start();

echo "<h2>Session Debug Information</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
echo "User Name: " . ($_SESSION['name'] ?? 'Not set') . "\n";
echo "User Role: " . ($_SESSION['role'] ?? 'Not set') . "\n";
echo "Role (lowercase): " . (isset($_SESSION['role']) ? strtolower($_SESSION['role']) : 'Not set') . "\n";
echo "Is Admin Check: " . ((!empty($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin') ? 'YES' : 'NO') . "\n";
echo "\nFull Session Data:\n";
print_r($_SESSION);
echo "</pre>";

// Test database connection
try {
    require_once '../config/database.php';
    echo "<h3>Database Connection: OK</h3>";
    
    // Check posts table structure
    $stmt = $pdo->query('SHOW COLUMNS FROM posts');
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Posts Table Columns:</h3>";
    echo "<pre>" . implode(", ", $columns) . "</pre>";
    
    // Check if user exists in database
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<h3>User from Database:</h3>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<h3>Database Error: " . $e->getMessage() . "</h3>";
}
?>