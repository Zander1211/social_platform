<?php
// Improved attachment parsing function
function parseAttachments($content) {
    $attachments = [];
    
    // Pattern 1: Look for href="uploads/filename" in anchor tags
    if (preg_match_all('/href=["\']uploads\/([^"\'\/><]+\.[^"\'\/><]+)["\']/i', $content, $matches)) {
        foreach ($matches[1] as $filename) {
            $attachments[] = 'uploads/' . $filename;
        }
    }
    
    // Pattern 2: Look for direct uploads/filename references
    if (preg_match_all('/uploads\/([^\s"\'\/><]+\.[^\s"\'\/><]+)/i', $content, $matches)) {
        $attachments = array_merge($attachments, $matches[0]);
    }
    
    // Remove duplicates and return
    return array_unique($attachments);
}

// Function to render attachments properly
function renderAttachment($attachmentPath) {
    $ext = strtolower(pathinfo($attachmentPath, PATHINFO_EXTENSION));
    $filename = basename($attachmentPath);
    
    // Check if file actually exists
    $fullPath = __DIR__ . '/' . $attachmentPath;
    if (!file_exists($fullPath)) {
        return '<div style="padding:12px;background:#fee;border:1px solid #fcc;border-radius:8px;color:#c33;">
                    <i class="fa fa-exclamation-triangle"></i> 
                    Attachment not found: ' . htmlspecialchars($filename) . '
                </div>';
    }
    
    echo '<div style="margin:8px 0">';
    
    if (in_array($ext, ['png','jpg','jpeg','gif','webp'])) {
        // Image
        echo '<img src="'.htmlspecialchars($attachmentPath).'" alt="'.htmlspecialchars($filename).'" 
                   style="max-width:100%;height:auto;border:1px solid #eee;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);cursor:pointer;" 
                   onclick="window.open(this.src, \'_blank\')" 
                   title="Click to view full size">';
    } elseif (in_array($ext, ['mp4','webm','ogg','ogv'])) {
        // Video
        $type = ($ext === 'ogv') ? 'ogg' : $ext;
        echo '<video controls style="max-width:100%;border:1px solid #eee;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1)">
                <source src="'.htmlspecialchars($attachmentPath).'" type="video/'.$type.'">
                Your browser does not support the video tag.
              </video>';
    } elseif (in_array($ext, ['mp3','wav','ogg'])) {
        // Audio
        echo '<audio controls style="width:100%;border-radius:4px">
                <source src="'.htmlspecialchars($attachmentPath).'">
                Your browser does not support the audio tag.
              </audio>';
    } else {
        // Other files
        echo '<div style="padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;display:flex;align-items:center;gap:8px">
                <i class="fa fa-file" style="color:#6b7280"></i>
                <span style="flex:1;color:#374151">'.htmlspecialchars($filename).'</span>
                <a class="btn secondary" href="'.htmlspecialchars($attachmentPath).'" target="_blank" rel="noopener" 
                   style="font-size:12px;padding:4px 8px">Download</a>
              </div>';
    }
    
    echo '</div>';
}

// Test function to check uploads directory
function checkUploadsDirectory() {
    $uploadsDir = __DIR__ . '/uploads';
    $issues = [];
    
    if (!is_dir($uploadsDir)) {
        $issues[] = "Uploads directory does not exist";
    } elseif (!is_writable($uploadsDir)) {
        $issues[] = "Uploads directory is not writable";
    }
    
    // List files in uploads directory
    $files = glob($uploadsDir . '/*');
    
    return [
        'issues' => $issues,
        'files' => array_map('basename', $files),
        'count' => count($files)
    ];
}

// If this file is accessed directly, show debug info
if (basename($_SERVER['PHP_SELF']) === 'attachment_fix.php') {
    echo '<h2>Attachment Debug Information</h2>';
    
    $uploadInfo = checkUploadsDirectory();
    echo '<h3>Uploads Directory Status:</h3>';
    if (!empty($uploadInfo['issues'])) {
        echo '<ul style="color:red;">';
        foreach ($uploadInfo['issues'] as $issue) {
            echo '<li>' . htmlspecialchars($issue) . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p style="color:green;">✓ Uploads directory is accessible and writable</p>';
    }
    
    echo '<p>Files in uploads directory: ' . $uploadInfo['count'] . '</p>';
    if (!empty($uploadInfo['files'])) {
        echo '<ul>';
        foreach ($uploadInfo['files'] as $file) {
            echo '<li>' . htmlspecialchars($file) . '</li>';
        }
        echo '</ul>';
    }
    
    // Test attachment parsing
    $testContent = '[Attachment: <a href="uploads/test_image.jpg">My Photo</a>]';
    echo '<h3>Test Attachment Parsing:</h3>';
    echo '<p>Test content: ' . htmlspecialchars($testContent) . '</p>';
    $parsed = parseAttachments($testContent);
    echo '<p>Parsed attachments: ' . print_r($parsed, true) . '</p>';
}
?>