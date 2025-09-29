<?php
// Generate the missing attachment file
$missingFile = 'att_68b54caf2cc8f.png';
$uploadsDir = __DIR__ . '/uploads';
$filePath = $uploadsDir . '/' . $missingFile;

echo "Generating missing attachment: $missingFile\n";

// Ensure uploads directory exists
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
    echo "Created uploads directory.\n";
}

// Check if file already exists
if (file_exists($filePath)) {
    echo "File already exists: $missingFile\n";
    exit(0);
}

// Create placeholder image using GD
if (extension_loaded('gd')) {
    echo "Creating PNG placeholder using GD...\n";
    
    // Create a 400x300 placeholder image
    $img = imagecreatetruecolor(400, 300);
    
    // Colors
    $bg = imagecolorallocate($img, 245, 245, 245);
    $border = imagecolorallocate($img, 200, 200, 200);
    $text_color = imagecolorallocate($img, 120, 120, 120);
    $accent = imagecolorallocate($img, 245, 166, 35);
    
    // Fill background
    imagefill($img, 0, 0, $bg);
    
    // Draw border
    imagerectangle($img, 0, 0, 399, 299, $border);
    
    // Draw accent rectangle
    imagefilledrectangle($img, 20, 20, 120, 80, $accent);
    
    // Add text
    imagestring($img, 5, 140, 110, 'Image Not Available', $text_color);
    imagestring($img, 3, 140, 140, $missingFile, $text_color);
    imagestring($img, 2, 140, 160, 'Original file was not found', $text_color);
    
    // Save as PNG
    if (imagepng($img, $filePath)) {
        chmod($filePath, 0644);
        echo "✓ Successfully created placeholder image: $missingFile\n";
        echo "File saved to: $filePath\n";
    } else {
        echo "✗ Failed to create placeholder image.\n";
        exit(1);
    }
    
    imagedestroy($img);
} else {
    echo "GD extension not available. Creating text placeholder...\n";
    
    // Create a simple text file as fallback
    $placeholderContent = "Image placeholder for: $missingFile\n";
    $placeholderContent .= "Original image file was not found.\n";
    $placeholderContent .= "Created: " . date('Y-m-d H:i:s') . "\n";
    
    if (file_put_contents($filePath, $placeholderContent)) {
        chmod($filePath, 0644);
        echo "✓ Created text placeholder: $missingFile\n";
    } else {
        echo "✗ Failed to create placeholder.\n";
        exit(1);
    }
}

echo "Done! The missing attachment issue should now be resolved.\n";
?>