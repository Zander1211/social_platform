<?php
require_once '../config/database.php';

echo '<h1>Missing Attachments Report</h1>';

try {
    // Get all posts with attachments
    $stmt = $pdo->query("SELECT id, title, content, created_at FROM posts WHERE content LIKE '%uploads/%' ORDER BY created_at DESC");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<h2>Posts with Attachments:</h2>';
    
    if (empty($posts)) {
        echo '<p>No posts with attachments found in database.</p>';
    } else {
        $missingFiles = [];
        $foundFiles = [];
        
        foreach ($posts as $post) {
            echo '<div style="border:1px solid #ccc;padding:15px;margin:10px 0;border-radius:8px;">';
            echo '<h3>Post ID: ' . $post['id'] . ' - ' . htmlspecialchars($post['title']) . '</h3>';
            echo '<p><strong>Created:</strong> ' . $post['created_at'] . '</p>';
            
            // Extract attachment references from content
            $content = $post['content'];
            
            // Look for uploads/filename patterns
            if (preg_match_all('/uploads\/([^\s"\'\/><]+\.[^\s"\'\/><]+)/i', $content, $matches)) {
                echo '<p><strong>Referenced attachments:</strong></p>';
                echo '<ul>';
                
                foreach ($matches[0] as $attachmentPath) {
                    $filename = basename($attachmentPath);
                    $fullPath = __DIR__ . '/' . $attachmentPath;
                    
                    echo '<li>';
                    echo htmlspecialchars($attachmentPath);
                    
                    if (file_exists($fullPath)) {
                        echo ' <span style="color:green;">✓ Found</span>';
                        echo ' <a href="' . $attachmentPath . '" target="_blank">[View]</a>';
                        $foundFiles[] = $attachmentPath;
                    } else {
                        echo ' <span style="color:red;">✗ Missing</span>';
                        $missingFiles[] = $attachmentPath;
                    }
                    echo '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p><em>No attachment references found in content</em></p>';
            }
            
            // Show content preview
            echo '<p><strong>Content preview:</strong></p>';
            echo '<div style="background:#f5f5f5;padding:10px;border-radius:4px;max-height:100px;overflow:auto;">';
            echo htmlspecialchars(substr($content, 0, 300)) . (strlen($content) > 300 ? '...' : '');
            echo '</div>';
            
            echo '</div>';
        }
        
        // Summary
        echo '<h2>Summary:</h2>';
        echo '<p><strong>Total posts with attachments:</strong> ' . count($posts) . '</p>';
        echo '<p><strong>Found files:</strong> ' . count($foundFiles) . '</p>';
        echo '<p><strong>Missing files:</strong> ' . count($missingFiles) . '</p>';
        
        if (!empty($missingFiles)) {
            echo '<h3 style="color:red;">Missing Files:</h3>';
            echo '<ul>';
            foreach (array_unique($missingFiles) as $missing) {
                echo '<li style="color:red;">' . htmlspecialchars($missing) . '</li>';
            }
            echo '</ul>';
            
            echo '<h3>Solutions:</h3>';
            echo '<ol>';
            echo '<li><strong>Re-upload the missing files:</strong> If you have the original files, upload them with the exact same names to the uploads directory.</li>';
            echo '<li><strong>Clean up database references:</strong> Remove the attachment references from posts that have missing files.</li>';
            echo '<li><strong>Upload placeholder images:</strong> Create placeholder images with the missing filenames.</li>';
            echo '</ol>';
        }
    }
    
    // Check uploads directory
    echo '<h2>Files in Uploads Directory:</h2>';
    $uploadFiles = glob(__DIR__ . '/uploads/*');
    if (empty($uploadFiles)) {
        echo '<p>No files found in uploads directory.</p>';
    } else {
        echo '<ul>';
        foreach ($uploadFiles as $file) {
            $filename = basename($file);
            $size = filesize($file);
            echo '<li>';
            echo htmlspecialchars($filename) . ' (' . number_format($size) . ' bytes)';
            echo ' <a href="uploads/' . $filename . '" target="_blank">[View]</a>';
            echo '</li>';
        }
        echo '</ul>';
    }
    
} catch (Exception $e) {
    echo '<p style="color:red;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '<hr>';
echo '<h2>Quick Actions:</h2>';
echo '<form method="POST" style="margin:10px 0;">';
echo '<input type="hidden" name="action" value="create_placeholder">';
echo '<button type="submit" style="padding:10px 20px;background:#007cba;color:white;border:none;border-radius:4px;">Create Placeholder for Missing Files</button>';
echo '</form>';

echo '<form method="POST" style="margin:10px 0;">';
echo '<input type="hidden" name="action" value="clean_references">';
echo '<button type="submit" style="padding:10px 20px;background:#dc3545;color:white;border:none;border-radius:4px;" onclick="return confirm(\'This will remove attachment references from posts with missing files. Continue?\')">Clean Up Missing References</button>';
echo '</form>';

// Handle actions
if ($_POST['action'] ?? '' === 'create_placeholder') {
    echo '<h3>Creating Placeholder Files...</h3>';
    
    // Get missing files
    $stmt = $pdo->query("SELECT content FROM posts WHERE content LIKE '%uploads/%'");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $created = 0;
    foreach ($posts as $post) {
        if (preg_match_all('/uploads\/([^\s"\'\/><]+\.[^\s"\'\/><]+)/i', $post['content'], $matches)) {
            foreach ($matches[0] as $attachmentPath) {
                $fullPath = __DIR__ . '/' . $attachmentPath;
                if (!file_exists($fullPath)) {
                    $dir = dirname($fullPath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    
                    // Create a simple placeholder image
                    $ext = strtolower(pathinfo($attachmentPath, PATHINFO_EXTENSION));
                    if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif'])) {
                        // Create a simple 200x200 placeholder image
                        $img = imagecreate(200, 200);
                        $bg = imagecolorallocate($img, 240, 240, 240);
                        $text_color = imagecolorallocate($img, 100, 100, 100);
                        imagestring($img, 3, 50, 90, 'Image Not', $text_color);
                        imagestring($img, 3, 60, 110, 'Found', $text_color);
                        
                        if ($ext === 'png') {
                            imagepng($img, $fullPath);
                        } else {
                            imagejpeg($img, $fullPath);
                        }
                        imagedestroy($img);
                        $created++;
                    } else {
                        // Create a text placeholder for non-images
                        file_put_contents($fullPath, "File not found: " . basename($attachmentPath));
                        $created++;
                    }
                }
            }
        }
    }
    
    echo '<p style="color:green;">Created ' . $created . ' placeholder files.</p>';
    echo '<p><a href="">Refresh page</a> to see updated status.</p>';
}

if ($_POST['action'] ?? '' === 'clean_references') {
    echo '<h3>Cleaning Up Missing References...</h3>';
    
    $stmt = $pdo->query("SELECT id, content FROM posts WHERE content LIKE '%uploads/%'");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated = 0;
    foreach ($posts as $post) {
        $content = $post['content'];
        $originalContent = $content;
        
        // Find all attachment references
        if (preg_match_all('/uploads\/([^\s"\'\/><]+\.[^\s"\'\/><]+)/i', $content, $matches)) {
            foreach ($matches[0] as $attachmentPath) {
                $fullPath = __DIR__ . '/' . $attachmentPath;
                if (!file_exists($fullPath)) {
                    // Remove the attachment reference
                    $content = preg_replace('/\[Attachment:[^\]]*' . preg_quote(basename($attachmentPath), '/') . '[^\]]*\]/i', '', $content);
                    $content = preg_replace('/<a[^>]+href=["\']?' . preg_quote($attachmentPath, '/') . '[^>]*>.*?<\/a>/is', '', $content);
                }
            }
        }
        
        if ($content !== $originalContent) {
            $content = trim($content);
            $updateStmt = $pdo->prepare("UPDATE posts SET content = ? WHERE id = ?");
            $updateStmt->execute([$content, $post['id']]);
            $updated++;
        }
    }
    
    echo '<p style="color:green;">Updated ' . $updated . ' posts to remove missing attachment references.</p>';
    echo '<p><a href="">Refresh page</a> to see updated status.</p>';
}

echo '<p><a href="index.php">← Back to Main Site</a></p>';
?>