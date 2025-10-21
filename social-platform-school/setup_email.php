<?php
/**
 * Email Configuration Setup Script
 * 
 * Run this script to quickly configure your email settings.
 * This will help you set up Gmail SMTP for password reset emails.
 */

echo "=== EMAIL CONFIGURATION SETUP ===\n\n";

// Check if config file exists
$configFile = __DIR__ . '/config/email.php';
$configExists = file_exists($configFile);

if ($configExists) {
    echo "✓ Email configuration file found\n";
    
    // Load current config
    try {
        $currentConfig = require $configFile;
        echo "✓ Current service: " . $currentConfig['service'] . "\n";
        echo "✓ From email: " . $currentConfig['from']['email'] . "\n";
        
        if ($currentConfig['service'] === 'gmail') {
            echo "✓ Gmail username: " . $currentConfig['gmail']['username'] . "\n";
            echo "✓ Gmail password: " . (empty($currentConfig['gmail']['password']) ? 'NOT SET' : 'SET') . "\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Error loading config: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Email configuration file not found\n";
}

echo "\n=== SETUP INSTRUCTIONS ===\n\n";

echo "1. GMAIL SETUP (Recommended):\n";
echo "   a) Enable 2-Factor Authentication on your Google account\n";
echo "   b) Go to Google Account > Security > App passwords\n";
echo "   c) Generate an app password for 'Mail'\n";
echo "   d) Copy the 16-character password\n\n";

echo "2. EDIT CONFIG FILE:\n";
echo "   File: config/email.php\n";
echo "   Set your Gmail address and app password\n\n";

echo "3. TEST CONFIGURATION:\n";
echo "   Visit: http://localhost/social_platform/social-platform-school/public/test_email.php\n";
echo "   Send a test email to verify everything works\n\n";

echo "4. SECURITY:\n";
echo "   Delete test_email.php after testing\n";
echo "   Never commit passwords to version control\n\n";

// Test database connection
echo "=== DATABASE CHECK ===\n";
try {
    require_once __DIR__ . '/config/database.php';
    echo "✓ Database connection successful\n";
    
    // Check if tables exist
    $tables = $pdo->query("SHOW TABLES LIKE 'password_reset_%'")->fetchAll();
    echo "✓ Found " . count($tables) . " password reset tables\n";
    
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}

echo "\n=== QUICK TEST ===\n";
echo "To test email functionality:\n";
echo "1. Configure your email settings in config/email.php\n";
echo "2. Visit the test page: public/test_email.php\n";
echo "3. Send a test email to yourself\n";
echo "4. Check your inbox (and spam folder)\n";
echo "5. If successful, try the forgot password feature!\n\n";

echo "=== EXAMPLE GMAIL CONFIG ===\n";
echo "Replace 'your-email@gmail.com' and 'your-app-password' with your actual values:\n\n";
echo "<?php\n";
echo "\$emailConfig = [\n";
echo "    'service' => 'gmail',\n";
echo "    'gmail' => [\n";
echo "        'username' => 'your-email@gmail.com',\n";
echo "        'password' => 'abcd efgh ijkl mnop', // 16-char app password\n";
echo "    ],\n";
echo "    'from' => [\n";
echo "        'email' => 'your-email@gmail.com',\n";
echo "        'name' => 'Academic Excellence Platform'\n";
echo "    ],\n";
echo "];\n";
echo "return \$emailConfig;\n";
echo "?>\n\n";

echo "Happy emailing! 🎉\n";
?>