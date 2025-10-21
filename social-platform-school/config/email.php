<?php
/**
 * Email Configuration
 * 
 * Configure your email settings here. Multiple options are provided:
 * 1. Gmail SMTP (recommended for development/small scale)
 * 2. Custom SMTP server
 * 3. PHP mail() function (basic, may not work on all servers)
 */

// Email configuration settings
$emailConfig = [
    // Email service type: 'smtp', 'gmail', 'mail'
    'service' => 'gmail', // Change this to your preferred service
    
    // SMTP Configuration (for 'smtp' service)
    'smtp' => [
        'host' => 'smtp.gmail.com', // Your SMTP server
        'port' => 587, // SMTP port (587 for TLS, 465 for SSL, 25 for non-encrypted)
        'encryption' => 'tls', // 'tls', 'ssl', or null
        'username' => 'your-email@gmail.com', // Your email address
        'password' => 'your-app-password', // Your email password or app password
    ],
    
    // Gmail Configuration (for 'gmail' service)
    'gmail' => [
        'username' => 'johnzanderzerrudo@gmail.com', // Your Gmail address
        'password' => 'miji sria nteq octk', // Gmail App Password (not your regular password)
        // To get Gmail App Password:
        // 1. Enable 2-Factor Authentication on your Google account
        // 2. Go to Google Account settings > Security > App passwords
        // 3. Generate a new app password for "Mail"
        // 4. Use that 16-character password here
    ],
    
    // Sender information
    'from' => [
        'email' => 'noreply@academicplatform.edu',
        'name' => 'Academic Excellence Platform'
    ],
    
    // Email settings
    'settings' => [
        'timeout' => 30, // Connection timeout in seconds
        'debug' => false, // Set to true for debugging
        'verify_ssl' => true, // Verify SSL certificates
    ]
];

// Return the configuration
return $emailConfig;
?>