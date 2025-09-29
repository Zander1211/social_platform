<?php
session_start();
require_once '../config/database.php';

// Include the attachment functions
require_once 'attachment_fix.php';

echo '<h1>Attachment Debug Page</h1>';

// Check uploads directory
$uploadInfo = checkUploadsDirectory();
echo '<h2>Uploads Directory Status:</h2>';
if (!empty($uploadInfo['issues'])) {
    echo '<ul style="color:red;">';
    foreach ($uploadInfo['issues'] as $issue) {
        echo '<li>' . htmlspecialchars($issue) . '</li>';
    }
    echo '</ul>';
} else {
    echo '<p style="color:green;">✓ Uploads directory is accessible and writable</p>';
}

echo '<p><strong>Files in uploads directory:</strong> ' . $uploadInfo['count'] . '</p>';
if (!empty($uploadInfo['files'])) {
    echo '<ul>';
    foreach ($uploadInfo['files'] as $file) {
        $filePath = 'uploads/' . $file;
        echo '<li>';
        echo htmlspecialchars($file);
        echo ' - <a href="' . $filePath . '" target="_blank">View File</a>';
        echo '</li>';
    }
    echo '</ul>';
} else {
    echo '<p>No files found in uploads directory.</p>';
}

// Test attachment parsing
echo '<h2>Test Attachment Parsing:</h2>';
$testContents = [
    '[Attachment: <a href="uploads/test_image.jpg">My Photo</a>]',
    'Some text [Attachment: <a href="uploads/document.pdf">Important Document</a>] more text',
    'uploads/direct_reference.png',
    '<a href="uploads/linked_file.mp4">Video File</a>'
];

foreach ($testContents as $i => $testContent) {
    echo '<h3>Test ' . ($i + 1) . ':</h3>';
    echo '<p><strong>Content:</strong> ' . htmlspecialchars($testContent) . '</p>';
    $parsed = parseAttachments($testContent);
    echo '<p><strong>Parsed attachments:</strong> ' . (empty($parsed) ? 'None' : implode(', ', $parsed)) . '</p>';
    
    if (!empty($parsed)) {
        echo '<div><strong>Rendered:</strong></div>';
        foreach ($parsed as $attachment) {
            echo renderAttachment($attachment);
        }
    }
    echo '<hr>';
}

// Check recent posts for attachments
echo '<h2>Recent Posts with Attachments:</h2>';
try {
    $stmt = $pdo->prepare("SELECT id, title, content, created_at FROM posts ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($posts)) {
        echo '<p>No posts found in database.</p>';
    } else {
        foreach ($posts as $post) {
            echo '<div style="border:1px solid #ccc;padding:10px;margin:10px 0;">';
            echo '<h4>' . htmlspecialchars($post['title']) . '</h4>';
            echo '<p><strong>Content:</strong> ' . htmlspecialchars(substr($post['content'], 0, 200)) . '...</p>';
            
            $attachments = parseAttachments($post['content']);
            if (!empty($attachments)) {
                echo '<p><strong>Found attachments:</strong> ' . implode(', ', $attachments) . '</p>';
                foreach ($attachments as $attachment) {
                    echo renderAttachment($attachment);
                }
            } else {
                echo '<p><em>No attachments found</em></p>';
            }
            echo '</div>';
        }
    }
} catch (Exception $e) {
    echo '<p style="color:red;">Error accessing database: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '<h2>Upload Test Form:</h2>';
echo '<form method="POST" enctype="multipart/form-data" style="border:1px solid #ccc;padding:20px;">';
echo '<input type="hidden" name="action" value="test_upload">';
echo '<div><label>Test File Upload:</label></div>';
echo '<div><input type="file" name="test_file" accept="image/*"></div>';
echo '<div style="margin-top:10px;"><button type="submit">Upload Test File</button></div>';
echo '</form>';

// Handle test upload
if ($_POST['action'] ?? '' === 'test_upload' && !empty($_FILES['test_file']['name'])) {
    $file = $_FILES['test_file'];
    $targetDir = __DIR__ . '/uploads';
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeName = 'test_' . uniqid() . '.' . $ext;
    $destination = $targetDir . '/' . $safeName;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        chmod($destination, 0644);
        echo '<div style="background:#d4edda;color:#155724;padding:10px;margin:10px 0;border:1px solid #c3e6cb;">';
        echo 'File uploaded successfully: ' . htmlspecialchars($safeName);
        echo '<br><a href="uploads/' . $safeName . '" target="_blank">View uploaded file</a>';
        echo '</div>';
    } else {
        echo '<div style="background:#f8d7da;color:#721c24;padding:10px;margin:10px 0;border:1px solid #f5c6cb;">';
        echo 'Failed to upload file.';
        echo '</div>';
    }
}
?>