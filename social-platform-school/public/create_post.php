<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../src/Controller/PostController.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Location: login.php');
    exit();
}

// Check if this is a create post action
if (!isset($_POST['action']) || $_POST['action'] !== 'create_post') {
    header('Location: index.php');
    exit();
}

// Debug logging
error_log('Create post attempt by user: ' . $_SESSION['user_id'] . ' (' . ($_SESSION['role'] ?? 'no role') . ')');

$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');

// Validate input
if (empty($title) && empty($content)) {
    $_SESSION['error'] = 'Please provide a title or content for your post.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit();
}

// Handle optional file upload
if (!empty($_FILES['attachment']['name'])) {
    $up = $_FILES['attachment'];
    $targetDir = __DIR__ . '/uploads';
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
    
    // Validate file type and size
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm', 'audio/mp3', 'audio/wav', 'application/pdf'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    if (!in_array($up['type'], $allowedTypes)) {
        $_SESSION['error'] = 'Invalid file type. Please upload images, videos, audio, or PDF files only.';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit();
    }
    
    if ($up['size'] > $maxSize) {
        $_SESSION['error'] = 'File size too large. Maximum size is 10MB.';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit();
    }
    
    $ext = pathinfo($up['name'], PATHINFO_EXTENSION);
    $safe = uniqid('att_') . '.' . $ext;
    $dest = $targetDir . '/' . $safe;
    
    if (move_uploaded_file($up['tmp_name'], $dest)) {
        // Set proper file permissions
        chmod($dest, 0644);
        
        // Verify the file was actually created and is readable
        if (file_exists($dest) && is_readable($dest)) {
            // append link to content
            $content .= "\n\n[Attachment: <a href=\"uploads/{$safe}\">{$up['name']}</a>]";
            $_SESSION['success'] = 'Post created with attachment successfully!';
        } else {
            $_SESSION['error'] = 'File upload completed but file verification failed.';
        }
    } else {
        $_SESSION['error'] = 'Failed to upload attachment. Please check file permissions and try again.';
    }
}

try {
    $pc = new PostController($pdo);
    $postId = $pc->createPost([
        'user_id' => $_SESSION['user_id'],
        'title' => $title,
        'content' => $content,
    ]);
    
    if ($postId) {
        $_SESSION['success'] = 'Post created successfully!';
        error_log('Post created successfully with ID: ' . $postId);
    } else {
        $_SESSION['error'] = 'Failed to create post. Please try again.';
        error_log('Post creation failed - no ID returned');
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Error creating post: ' . $e->getMessage();
    error_log('Post creation error: ' . $e->getMessage());
}

// Redirect back to the referring page or index
$redirectUrl = $_SERVER['HTTP_REFERER'] ?? 'index.php';

// If the referer is the current page (create_post.php), redirect to index
if (strpos($redirectUrl, 'create_post.php') !== false) {
    $redirectUrl = 'index.php';
}

header('Location: ' . $redirectUrl);
exit();
?>