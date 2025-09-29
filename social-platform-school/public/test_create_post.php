<?php
session_start();
require_once '../config/database.php';
require_once '../src/Controller/PostController.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    die('Please log in first');
}

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    die('Only admins can create posts. Your role: ' . ($_SESSION['role'] ?? 'none'));
}

echo "<h2>Create Post Test</h2>";
echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
echo "<p>User Role: " . $_SESSION['role'] . "</p>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pc = new PostController($pdo);
        $postId = $pc->createPost([
            'user_id' => $_SESSION['user_id'],
            'title' => 'Test Post',
            'content' => 'This is a test post created at ' . date('Y-m-d H:i:s'),
        ]);
        
        if ($postId) {
            echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>";
            echo "✅ Post created successfully! Post ID: " . $postId;
            echo "</div>";
        } else {
            echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
            echo "❌ Post creation failed - no ID returned";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
        echo "❌ Error: " . $e->getMessage();
        echo "</div>";
    }
}
?>

<form method="POST">
    <button type="submit" style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer;">
        Create Test Post
    </button>
</form>

<p><a href="index.php">← Back to Home</a></p>