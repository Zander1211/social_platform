<?php
echo '<h1>Server Test Page</h1>';
echo '<p>If you can see this page, PHP is working correctly.</p>';
echo '<p>Current time: ' . date('Y-m-d H:i:s') . '</p>';
echo '<p>PHP Version: ' . phpversion() . '</p>';

// Test if uploads directory exists and is writable
$uploadsDir = __DIR__ . '/uploads';
echo '<h2>Uploads Directory Test:</h2>';
if (is_dir($uploadsDir)) {
    echo '<p style="color:green;">✓ Uploads directory exists</p>';
    if (is_writable($uploadsDir)) {
        echo '<p style="color:green;">✓ Uploads directory is writable</p>';
    } else {
        echo '<p style="color:red;">✗ Uploads directory is not writable</p>';
    }
} else {
    echo '<p style="color:red;">✗ Uploads directory does not exist</p>';
}

// List files in uploads
$files = glob($uploadsDir . '/*');
echo '<p>Files in uploads: ' . count($files) . '</p>';
if (!empty($files)) {
    echo '<ul>';
    foreach ($files as $file) {
        $filename = basename($file);
        echo '<li><a href="uploads/' . $filename . '" target="_blank">' . $filename . '</a></li>';
    }
    echo '</ul>';
}

// Test database connection
echo '<h2>Database Connection Test:</h2>';
try {
    require_once '../config/database.php';
    echo '<p style="color:green;">✓ Database connection successful</p>';
    
    // Test if posts table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'posts'");
    if ($stmt->rowCount() > 0) {
        echo '<p style="color:green;">✓ Posts table exists</p>';
        
        // Count posts
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM posts");
        $result = $stmt->fetch();
        echo '<p>Total posts in database: ' . $result['count'] . '</p>';
    } else {
        echo '<p style="color:red;">✗ Posts table does not exist</p>';
    }
} catch (Exception $e) {
    echo '<p style="color:red;">✗ Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '<h2>Quick Links:</h2>';
echo '<ul>';
echo '<li><a href="index.php">Main Site (index.php)</a></li>';
echo '<li><a href="login.php">Login Page</a></li>';
echo '<li><a href="debug_attachments.php">Attachment Debug</a></li>';
echo '</ul>';
?>