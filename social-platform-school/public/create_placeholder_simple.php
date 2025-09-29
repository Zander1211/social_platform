<?php
// Simple placeholder creation - run this file in your browser
$missingFile = 'att_68b54caf2cc8f.png';
$uploadsDir = __DIR__ . '/uploads';
$filePath = $uploadsDir . '/' . $missingFile;

// Ensure uploads directory exists
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

if (file_exists($filePath)) {
    echo "File already exists!";
    exit;
}

// Create a simple 1x1 pixel PNG as a minimal placeholder
// This is a base64 encoded 1x1 transparent PNG
$pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==');

if (file_put_contents($filePath, $pngData)) {
    chmod($filePath, 0644);
    echo "✓ Created minimal PNG placeholder: $missingFile";
} else {
    echo "✗ Failed to create placeholder";
}
?>