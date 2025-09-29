<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../src/Controller/ChatController.php';

echo "<h2>Group Creation Test</h2>";

// Test database connection
try {
    echo "✅ Database connection: OK<br>";
    
    // Check if chat_rooms table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'chat_rooms'");
    if ($stmt && $stmt->rowCount() > 0) {
        echo "✅ chat_rooms table: EXISTS<br>";
        
        // Show table structure
        $desc = $pdo->query("DESCRIBE chat_rooms");
        echo "<strong>Table structure:</strong><br>";
        while ($row = $desc->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$row['Field']} ({$row['Type']})<br>";
        }
    } else {
        echo "❌ chat_rooms table: NOT FOUND<br>";
    }
    
    // Check if chat_members table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'chat_members'");
    if ($stmt && $stmt->rowCount() > 0) {
        echo "✅ chat_members table: EXISTS<br>";
    } else {
        echo "❌ chat_members table: NOT FOUND<br>";
    }
    
    // Test session
    if (isset($_SESSION['user_id'])) {
        echo "✅ User logged in: ID " . $_SESSION['user_id'] . "<br>";
        
        // Test group creation
        $chatController = new ChatController($pdo);
        $testGroupId = $chatController->createGroup("Test Group " . date('H:i:s'), $_SESSION['user_id']);
        
        if ($testGroupId) {
            echo "✅ Group creation test: SUCCESS (ID: $testGroupId)<br>";
        } else {
            echo "❌ Group creation test: FAILED<br>";
        }
    } else {
        echo "❌ User not logged in<br>";
        echo "<a href='login.php'>Login first</a><br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='chat.php'>Back to Chat</a>";
?>