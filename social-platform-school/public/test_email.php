<?php
/**
 * Email Configuration Test Script
 * 
 * Use this script to test your email configuration.
 * Visit: http://localhost/social_platform/social-platform-school/public/test_email.php
 * 
 * IMPORTANT: Delete this file after testing for security!
 */

// Prevent access in production
if (!in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1', '::1'])) {
    die('This test script is only available on localhost for security reasons.');
}

require_once '../config/database.php';
require_once __DIR__ . '/../src/Service/EmailService.php';

$message = '';
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testEmail = trim($_POST['test_email'] ?? '');
    $testType = $_POST['test_type'] ?? 'reset';
    
    if (empty($testEmail)) {
        $error = 'Please enter an email address to test';
    } elseif (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        try {
            $emailService = new EmailService();
            
            if ($testType === 'otp') {
                // Test OTP email
                $otp = $emailService->generateOTP();
                $result = $emailService->sendOTPEmail($testEmail, $otp, 'Email Configuration Test');
                
                if ($result) {
                    $success = true;
                    $message = "OTP email sent successfully! Check your inbox for OTP: $otp";
                } else {
                    $error = 'Failed to send OTP email. Check your email configuration and error logs.';
                }
            } else {
                // Test password reset email
                $testToken = bin2hex(random_bytes(32));
                $result = $emailService->sendPasswordResetEmail($testEmail, $testToken);
                
                if ($result) {
                    $success = true;
                    $message = 'Password reset email sent successfully! Check your inbox.';
                } else {
                    $error = 'Failed to send password reset email. Check your email configuration and error logs.';
                }
            }
            
        } catch (Exception $e) {
            $error = 'Email service error: ' . $e->getMessage();
        }
    }
}

// Load email configuration for display
try {
    $emailConfig = require_once '../config/email.php';
    $configLoaded = true;
} catch (Exception $e) {
    $configLoaded = false;
    $configError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Configuration Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        .status {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        .error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #ef4444;
        }
        .warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #f59e0b;
        }
        .info {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #3b82f6;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #374151;
        }
        input, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #d1d5db;
            border-radius: 6px;
            font-size: 16px;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #10b981;
        }
        button {
            background: #10b981;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-right: 10px;
        }
        button:hover {
            background: #059669;
        }
        .config-info {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .config-info h3 {
            margin-top: 0;
            color: #374151;
        }
        .config-item {
            margin: 10px 0;
            padding: 8px;
            background: white;
            border-radius: 4px;
            border-left: 4px solid #10b981;
        }
        .delete-warning {
            background: #fef2f2;
            border: 2px solid #fecaca;
            color: #dc2626;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Email Configuration Test</h1>
            <p>Test your email configuration for the password reset functionality</p>
        </div>

        <?php if (!$configLoaded): ?>
            <div class="status error">
                <strong>❌ Configuration Error:</strong><br>
                <?php echo htmlspecialchars($configError); ?>
            </div>
        <?php else: ?>
            <div class="config-info">
                <h3>📋 Current Configuration</h3>
                <div class="config-item">
                    <strong>Service:</strong> <?php echo htmlspecialchars($emailConfig['service']); ?>
                </div>
                <div class="config-item">
                    <strong>From Email:</strong> <?php echo htmlspecialchars($emailConfig['from']['email']); ?>
                </div>
                <div class="config-item">
                    <strong>From Name:</strong> <?php echo htmlspecialchars($emailConfig['from']['name']); ?>
                </div>
                <?php if ($emailConfig['service'] === 'gmail'): ?>
                    <div class="config-item">
                        <strong>Gmail Username:</strong> <?php echo htmlspecialchars($emailConfig['gmail']['username']); ?>
                    </div>
                    <div class="config-item">
                        <strong>Gmail Password:</strong> <?php echo str_repeat('*', strlen($emailConfig['gmail']['password'])); ?>
                    </div>
                <?php elseif ($emailConfig['service'] === 'smtp'): ?>
                    <div class="config-item">
                        <strong>SMTP Host:</strong> <?php echo htmlspecialchars($emailConfig['smtp']['host']); ?>
                    </div>
                    <div class="config-item">
                        <strong>SMTP Port:</strong> <?php echo htmlspecialchars($emailConfig['smtp']['port']); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="status error">
                <strong>❌ Error:</strong><br>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="status success">
                <strong>✅ Success:</strong><br>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($configLoaded): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="test_email">Test Email Address:</label>
                    <input 
                        type="email" 
                        id="test_email" 
                        name="test_email" 
                        placeholder="Enter email address to test"
                        value="<?php echo htmlspecialchars($_POST['test_email'] ?? ''); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="test_type">Test Type:</label>
                    <select id="test_type" name="test_type">
                        <option value="reset" <?php echo ($_POST['test_type'] ?? '') === 'reset' ? 'selected' : ''; ?>>
                            Password Reset Email (with OTP)
                        </option>
                        <option value="otp" <?php echo ($_POST['test_type'] ?? '') === 'otp' ? 'selected' : ''; ?>>
                            OTP Only Email
                        </option>
                    </select>
                </div>

                <button type="submit">🚀 Send Test Email</button>
            </form>
        <?php endif; ?>

        <div class="status info">
            <strong>📝 Setup Instructions:</strong><br>
            1. Configure your email settings in <code>config/email.php</code><br>
            2. For Gmail: Enable 2FA and generate an App Password<br>
            3. Test with this form<br>
            4. Check your email inbox (and spam folder)<br>
            5. Delete this test file when done!
        </div>

        <div class="delete-warning">
            <strong>⚠️ SECURITY WARNING</strong><br>
            Delete this test file (<code>test_email.php</code>) after testing!<br>
            This file should not be accessible in production.
        </div>
    </div>
</body>
</html>