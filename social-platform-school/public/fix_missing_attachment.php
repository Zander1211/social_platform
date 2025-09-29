<?php
// Quick fix for the specific missing attachment

$missingFile = 'att_68b54caf2cc8f.png';
$uploadsDir = __DIR__ . '/uploads';
$filePath = $uploadsDir . '/' . $missingFile;

echo '<h1>Fix Missing Attachment: ' . htmlspecialchars($missingFile) . '</h1>';

// Ensure uploads directory exists
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
    echo '<p style="color:green;">Created uploads directory.</p>';
}

// Check if file already exists
if (file_exists($filePath)) {
    echo '<p style="color:green;">File already exists: ' . htmlspecialchars($missingFile) . '</p>';
    echo '<p><a href="uploads/' . $missingFile . '" target="_blank">View file</a></p>';
} else {
    echo '<p style="color:red;">File is missing: ' . htmlspecialchars($missingFile) . '</p>';
    
    // Create a placeholder image
    if (extension_loaded('gd')) {
        echo '<p>Creating placeholder image...</p>';
        
        // Create a 400x300 placeholder image
        $img = imagecreate(400, 300);
        $bg = imagecolorallocate($img, 245, 245, 245);
        $border = imagecolorallocate($img, 200, 200, 200);
        $text_color = imagecolorallocate($img, 120, 120, 120);
        
        // Draw border
        imagerectangle($img, 0, 0, 399, 299, $border);
        
        // Add text
        $text1 = 'Image Not Available';
        $text2 = basename($missingFile);
        $text3 = 'Original file was not found';
        
        // Center the text
        imagestring($img, 4, 120, 120, $text1, $text_color);
        imagestring($img, 3, 140, 140, $text2, $text_color);
        imagestring($img, 2, 130, 160, $text3, $text_color);
        
        // Save as PNG
        if (imagepng($img, $filePath)) {
            echo '<p style="color:green;">✓ Created placeholder image: ' . htmlspecialchars($missingFile) . '</p>';
            echo '<p><a href="uploads/' . $missingFile . '" target="_blank">View placeholder</a></p>';
        } else {
            echo '<p style="color:red;">✗ Failed to create placeholder image.</p>';
        }
        
        imagedestroy($img);
    } else {
        echo '<p style="color:orange;">GD extension not available. Creating text placeholder...</p>';
        
        // Create a simple text file as placeholder
        $placeholderContent = "Image placeholder for: " . $missingFile . "\n";
        $placeholderContent .= "Original image file was not found.\n";
        $placeholderContent .= "Created: " . date('Y-m-d H:i:s');
        
        if (file_put_contents($filePath, $placeholderContent)) {
            echo '<p style="color:green;">✓ Created text placeholder.</p>';
        } else {
            echo '<p style="color:red;">✗ Failed to create placeholder.</p>';
        }
    }
}

echo '<hr>';
echo '<h2>Upload Replacement Image</h2>';
echo '<form method="POST" enctype="multipart/form-data" style="border:1px solid #ccc;padding:20px;border-radius:8px;">';
echo '<p>Upload a replacement image for <strong>' . htmlspecialchars($missingFile) . '</strong>:</p>';
echo '<input type="file" name="replacement_image" accept="image/*" required>';
echo '<br><br>';
echo '<button type="submit" name="upload_replacement" style="padding:10px 20px;background:#28a745;color:white;border:none;border-radius:4px;">Upload Replacement</button>';
echo '</form>';

// Handle file upload
if (isset($_POST['upload_replacement']) && !empty($_FILES['replacement_image']['name'])) {
    $uploadedFile = $_FILES['replacement_image'];
    
    if ($uploadedFile['error'] === UPLOAD_ERR_OK) {
        $tempPath = $uploadedFile['tmp_name'];
        
        // Validate it's an image
        $imageInfo = getimagesize($tempPath);
        if ($imageInfo !== false) {
            // Move the uploaded file to replace the missing one
            if (move_uploaded_file($tempPath, $filePath)) {
                echo '<div style="background:#d4edda;color:#155724;padding:15px;margin:10px 0;border:1px solid #c3e6cb;border-radius:8px;">';
                echo '<strong>Success!</strong> Replacement image uploaded successfully.';
                echo '<br><a href="uploads/' . $missingFile . '" target="_blank">View uploaded image</a>';
                echo '<br><a href="index.php">Go back to main site</a>';
                echo '</div>';
            } else {
                echo '<div style="background:#f8d7da;color:#721c24;padding:15px;margin:10px 0;border:1px solid #f5c6cb;border-radius:8px;">';
                echo '<strong>Error!</strong> Failed to save the uploaded file.';
                echo '</div>';
            }
        } else {
            echo '<div style="background:#fff3cd;color:#856404;padding:15px;margin:10px 0;border:1px solid #ffeaa7;border-radius:8px;">';
            echo '<strong>Warning!</strong> The uploaded file is not a valid image.';
            echo '</div>';
        }
    } else {
        echo '<div style="background:#f8d7da;color:#721c24;padding:15px;margin:10px 0;border:1px solid #f5c6cb;border-radius:8px;">';
        echo '<strong>Error!</strong> File upload failed. Error code: ' . $uploadedFile['error'];
        echo '</div>';
    }
}

echo '<hr>';
echo '<h2>Other Options</h2>';
echo '<ul>';
echo '<li><a href="check_missing_attachments.php">Check all missing attachments</a></li>';
echo '<li><a href="debug_attachments.php">Debug attachment system</a></li>';
echo '<li><a href="index.php">Return to main site</a></li>';
echo '</ul>';
?>