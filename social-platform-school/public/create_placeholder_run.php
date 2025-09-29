Quick Actions

Search
Chat <?php
// create_placeholder_run.php
// Run this in your browser to generate the missing attachment
$missingFile = 'att_68b54caf2cc8f.png';
$uploadsDir = __DIR__ . '/uploads';
$filePath = $uploadsDir . '/' . $missingFile;

if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

echo '<h1>Create placeholder: ' . htmlspecialchars($missingFile) . '</h1>';

if (file_exists($filePath)) {
    echo '<p style="color:green;">File already exists: ' . htmlspecialchars($missingFile) . '</p>';
    echo '<p><a href="uploads/' . $missingFile . '" target="_blank">View file</a></p>';
    exit;
}

if (extension_loaded('gd')) {
    echo '<p>GD is available — creating PNG placeholder...</p>';
    $w = 400; $h = 300;
    $img = imagecreatetruecolor($w, $h);
    // Colors
    $bg = imagecolorallocate($img, 245, 245, 245);
    $border = imagecolorallocate($img, 220, 220, 220);
    $title = imagecolorallocate($img, 34, 50, 99); // dark blue-ish
    $sub = imagecolorallocate($img, 120, 120, 120);
    $accent = imagecolorallocate($img, 245, 166, 35); // gold-ish

    imagefilledrectangle($img, 0, 0, $w, $h, $bg);
    imagerectangle($img, 0, 0, $w-1, $h-1, $border);

    // Draw a rounded-ish rectangle emblem
    $emW = 140; $emH = 100;
    $emX = 24; $emY = 24;
    imagefilledrectangle($img, $emX, $emY, $emX + $emW, $emY + $emH, $accent);

    // Add text
    $fontSize = 5; // built-in font for portability
    imagestring($img, 5, 190, 110, 'Image Not Available', $title);
    imagestring($img, 3, 190, 140, $missingFile, $sub);

    // Save PNG
    if (imagepng($img, $filePath)) {
        chmod($filePath, 0644);
        echo '<p style="color:green;">✓ Created placeholder image: ' . htmlspecialchars($missingFile) . '</p>';
        echo '<p><a href="uploads/' . $missingFile . '" target="_blank">View placeholder</a></p>';
    } else {
        echo '<p style="color:red;">✗ Failed to write image file.</p>';
    }
    imagedestroy($img);
} else {
    echo '<p>GD not available. Creating a text placeholder instead...</p>';
    $content = "Image placeholder for: $missingFile\nOriginal image not found.\nCreated: " . date('c') . "\n";
    if (file_put_contents($filePath, $content) !== false) {
        chmod($filePath, 0644);
        echo '<p style="color:green;">✓ Created text placeholder: ' . htmlspecialchars($missingFile) . '</p>';
        echo '<p><a href="uploads/' . $missingFile . '" target="_blank">View placeholder</a></p>';
    } else {
        echo '<p style="color:red;">✗ Failed to create placeholder.</p>';
    }
}

echo '<hr><p><a href="chat.php">Return to Chat</a></p>';
?>