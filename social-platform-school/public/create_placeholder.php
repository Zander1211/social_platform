<?php
// Create placeholder for missing attachment
$missingFile = 'att_68b54caf2cc8f.png';
$uploadsDir = __DIR__ . '/uploads';
$filePath = $uploadsDir . '/' . $missingFile;

// Ensure uploads directory exists
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

// Create a simple placeholder image using basic image creation
if (extension_loaded('gd')) {
    // Create a 400x300 placeholder image
    $img = imagecreate(400, 300);
    
    // Colors
    $bg = imagecolorallocate($img, 245, 245, 245);
    $border = imagecolorallocate($img, 200, 200, 200);
    $text_color = imagecolorallocate($img, 120, 120, 120);
    
    // Draw border
    imagerectangle($img, 0, 0, 399, 299, $border);
    
    // Add text
    imagestring($img, 4, 120, 120, 'Image Not Available', $text_color);
    imagestring($img, 3, 140, 140, 'att_68b54caf2cc8f.png', $text_color);
    imagestring($img, 2, 130, 160, 'Original file was not found', $text_color);
    
    // Save as PNG
    if (imagepng($img, $filePath)) {
        echo "Placeholder created successfully: $missingFile\n";
        chmod($filePath, 0644);
    } else {
        echo "Failed to create placeholder image.\n";
    }
    
    imagedestroy($img);
} else {
    // Fallback: create a simple text file
    $content = "Image placeholder - Original file not found";
    file_put_contents($filePath, $content);
    chmod($filePath, 0644);
    echo "Text placeholder created: $missingFile\n";
}

echo "Done. You can now refresh your main site.\n";
?>