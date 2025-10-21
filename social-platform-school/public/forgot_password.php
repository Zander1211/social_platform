<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../src/Controller/AuthController.php';

$auth = new AuthController($pdo);

// If already logged in, redirect to index
if (isset($_SESSION['user_id'])) { 
    header('Location: index.php'); 
    exit(); 
}

$message = null;
$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // If this POST is the OTP verification submitted on the confirmation page
  if (isset($_POST['verify_otp'])) {
    $email_for_otp = trim($_POST['email_for_otp'] ?? '');
    $otp_code = trim($_POST['otp_code'] ?? '');
    $redirectToken = trim($_POST['token_for_redirect'] ?? '');

    if (empty($email_for_otp) || !filter_var($email_for_otp, FILTER_VALIDATE_EMAIL)) {
      $error = 'Invalid email for OTP verification.';
    } elseif (empty($otp_code) || !preg_match('/^\d{4,6}$/', $otp_code)) {
      $error = 'Please enter the numeric OTP code sent to your email.';
    } else {
      // Debug log OTP attempt in development
      if ((isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'localhost') || getenv('APP_ENV') === 'development') {
        error_log("OTP verify attempt: email={$email_for_otp} otp={$otp_code}");
      }
      $verify = $auth->verifyOTP($email_for_otp, $otp_code);
      if ($verify['status'] === 'success') {
        // If token provided from previous request, use it; otherwise try to fetch latest token for the email
        $tokenToUse = $redirectToken;
        if (empty($tokenToUse)) {
          // Fallback: try to get latest token from DB
          try {
            $stmt = $pdo->prepare('SELECT token FROM password_reset_tokens WHERE email = :email ORDER BY created_at DESC LIMIT 1');
            $stmt->execute(['email' => $email_for_otp]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['token'])) {
              $tokenToUse = $row['token'];
            }
          } catch (Exception $e) {
            // ignore DB lookup errors here; we'll handle below
          }
        }

        if (!empty($tokenToUse)) {
          // Successful OTP verification — redirect to reset page with token
          header('Location: reset_password.php?token=' . urlencode($tokenToUse));
          exit();
        } else {
          $error = 'OTP verified but no reset token was found. Please request a new password reset.';
        }
      } else {
        $error = $verify['message'] ?? 'Invalid OTP code.';
      }
    }
  } else {
    // This is the initial email submission to request a password reset
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
      $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = 'Please enter a valid email address';
    } else {
      $result = $auth->requestPasswordReset($email);
      if ($result['status'] === 'success') {
        $success = true;
        $message = $result['message'];
        $messageType = $result['message_type'] ?? 'default';
        $details = $result['details'] ?? '';
        $token = $result['token'] ?? null;
        // keep the requested email available for OTP form
        $requested_email = $email;
      } else {
        $error = $result['message'];
        $errorDetails = $result['details'] ?? '';
      }
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Forgot Password - Academic Excellence Platform</title>
  <meta name="description" content="Reset your password for the Educational Excellence Platform">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Georgia:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      /* Academic Color Palette - Green Theme */
      --academic-green: #10b981;
      --academic-green-dark: #059669;
      --academic-green-light: #34d399;
      --academic-teal: #14b8a6;
      --academic-teal-dark: #0d9488;
      --academic-teal-light: #2dd4bf;
      
      /* Neutral Academic Colors */
      --academic-gray-50: #f9fafb;
      --academic-gray-100: #f3f4f6;
      --academic-gray-200: #e5e7eb;
      --academic-gray-300: #d1d5db;
      --academic-gray-400: #9ca3af;
      --academic-gray-500: #6b7280;
      --academic-gray-600: #4b5563;
      --academic-gray-700: #374151;
      --academic-gray-800: #1f2937;
      --academic-gray-900: #111827;
      
      /* Academic Status Colors */
      --academic-success: #065f46;
      --academic-warning: #dc2626;
      
      /* Background Colors */
      --bg-primary: #ffffff;
      --bg-secondary: var(--academic-gray-50);
      --text-primary: var(--academic-gray-900);
      --text-secondary: var(--academic-gray-700);
      --text-muted: var(--academic-gray-500);
      --text-inverse: #ffffff;
      
      /* Shadows and Effects */
      --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
      --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html, body {
      height: 100%;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }

    body {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 25%, #334155 50%, #1e293b 75%, #0f172a 100%);
      color: var(--text-primary);
      line-height: 1.6;
      position: relative;
      overflow-x: hidden;
    }

    /* Dark Mode Background Pattern */
    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: 
        radial-gradient(circle at 25% 25%, rgba(16, 185, 129, 0.08) 0%, transparent 50%),
        radial-gradient(circle at 75% 75%, rgba(20, 184, 166, 0.06) 0%, transparent 50%),
        radial-gradient(circle at 50% 50%, rgba(52, 211, 153, 0.04) 0%, transparent 70%);
      z-index: -1;
    }

    .container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
      position: relative;
      z-index: 1;
    }

    .forgot-password-card {
      width: 100%;
      max-width: 500px;
      background: var(--bg-primary);
      border-radius: 1.5rem;
      box-shadow: 
        0 25px 50px -12px rgba(0, 0, 0, 0.4),
        0 0 0 1px rgba(16, 185, 129, 0.1),
        0 0 20px rgba(16, 185, 129, 0.1);
      overflow: hidden;
      border: 3px solid var(--academic-green);
      position: relative;
      animation: cardFloat 6s ease-in-out infinite;
    }

    /* Academic Header Strip */
    .forgot-password-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
      background: linear-gradient(90deg, var(--academic-green) 0%, var(--academic-teal) 50%, var(--academic-green) 100%);
      z-index: 10;
    }

    .card-content {
      padding: 3rem 2.5rem;
    }

    .form-header {
      text-align: center;
      margin-bottom: 2.5rem;
    }

    .brand-logo {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, var(--academic-green) 0%, var(--academic-green-light) 100%);
      border-radius: 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      margin: 0 auto 2rem;
      box-shadow: var(--shadow-lg);
      border: 3px solid rgba(16, 185, 129, 0.2);
    }

    .form-title {
      font-family: 'Georgia', serif;
      font-size: 2rem;
      font-weight: 700;
      color: var(--academic-green);
      margin-bottom: 0.5rem;
    }

    .form-subtitle {
      color: var(--text-muted);
      font-size: 1rem;
      line-height: 1.5;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-label {
      display: block;
      font-weight: 600;
      color: var(--text-secondary);
      margin-bottom: 0.5rem;
      font-size: 0.875rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .form-input {
      width: 100%;
      padding: 1rem 1.25rem;
      border: 2px solid var(--academic-gray-200);
      border-radius: 0.75rem;
      font-size: 1rem;
      background: var(--bg-primary);
      transition: all 0.3s ease;
      font-family: inherit;
    }

    .form-input:focus {
      outline: none;
      border-color: var(--academic-green);
      box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }

    .form-input::placeholder {
      color: var(--text-muted);
    }

    .submit-button {
      width: 100%;
      padding: 1rem 1.5rem;
      background: linear-gradient(135deg, var(--academic-green) 0%, var(--academic-green-dark) 100%);
      color: var(--text-inverse);
      border: none;
      border-radius: 0.75rem;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: var(--shadow-md);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 2rem;
    }

    .submit-button:hover {
      background: linear-gradient(135deg, var(--academic-green-dark) 0%, var(--academic-green) 100%);
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }

    .submit-button:active {
      transform: translateY(0);
    }

    .submit-button:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    .error-message {
      background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
      color: var(--academic-warning);
      border: 2px solid #fecaca;
      padding: 1rem 1.25rem;
      border-radius: 0.75rem;
      margin-bottom: 1.5rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .success-message {
      background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
      color: var(--academic-success);
      border: 2px solid #bbf7d0;
      padding: 1rem 1.25rem;
      border-radius: 0.75rem;
      margin-bottom: 1.5rem;
      font-weight: 500;
      display: flex;
      align-items: flex-start;
      gap: 0.75rem;
    }

    .back-to-login {
      text-align: center;
      padding-top: 2rem;
      border-top: 1px solid var(--academic-gray-200);
    }

    .back-to-login a {
      color: var(--academic-green);
      text-decoration: none;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      transition: color 0.3s ease;
    }

    .back-to-login a:hover {
      color: var(--academic-green-light);
      text-decoration: underline;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .container {
        padding: 1rem;
      }
      
      .card-content {
        padding: 2rem 1.5rem;
      }
      
      .form-title {
        font-size: 1.75rem;
      }
      
      .brand-logo {
        width: 60px;
        height: 60px;
        font-size: 2rem;
      }
    }

    @keyframes cardFloat {
      0%, 100% {
        transform: translateY(0px) scale(1);
      }
      50% {
        transform: translateY(-8px) scale(1.002);
      }
    }

    /* Loading State */
    .submit-button.loading {
      opacity: 0.8;
      cursor: not-allowed;
      position: relative;
    }

    .submit-button.loading::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 20px;
      height: 20px;
      margin: -10px 0 0 -10px;
      border: 2px solid transparent;
      border-top: 2px solid var(--text-inverse);
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* Enhanced Message Styles */
    /* development-message style removed */

    .info-message {
      background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
      color: #1e40af;
      border: 2px solid #93c5fd;
      padding: 1rem 1.25rem;
      border-radius: 0.75rem;
      margin-bottom: 1.5rem;
      font-weight: 500;
      display: flex;
      align-items: flex-start;
      gap: 0.75rem;
    }

    .message-content {
      flex: 1;
    }

    .message-title {
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    .message-details {
      font-size: 0.9rem;
      opacity: 0.9;
      line-height: 1.5;
    }

    .test-link {
      background: rgba(255, 255, 255, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 0.5rem;
      padding: 1rem;
      margin-top: 1rem;
    }

    /* test-link-title style removed */

    .test-link a {
      color: #1e40af;
      text-decoration: none;
      font-weight: 600;
      padding: 0.5rem 1rem;
      background: rgba(255, 255, 255, 0.8);
      border-radius: 0.375rem;
      display: inline-block;
      margin-top: 0.5rem;
      transition: all 0.3s ease;
    }

    .test-link a:hover {
      background: rgba(255, 255, 255, 1);
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .error-details {
      font-size: 0.9rem;
      opacity: 0.9;
      margin-top: 0.5rem;
      line-height: 1.5;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="forgot-password-card">
      <div class="card-content">
        <div class="form-header">
          <div class="brand-logo">
            🔐
          </div>
          <h1 class="form-title">Forgot Password</h1>

        </div>
        
        <?php if ($error): ?>
          <div class="error-message" role="alert">
            <i class="fas fa-exclamation-triangle"></i>
            <div class="message-content">
              <div class="message-title"><?php echo htmlspecialchars($error); ?></div>
              <?php if (!empty($errorDetails)): ?>
                <div class="error-details"><?php echo htmlspecialchars($errorDetails); ?></div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
        
        <?php if ($success && $message): ?>
          <?php 
          $messageClass = 'success-message';
          $iconClass = 'fas fa-check-circle';
          
          if (isset($messageType)) {
            switch ($messageType) {
              // development_mode branch removed
              case 'email_error':
                $messageClass = 'error-message';
                $iconClass = 'fas fa-exclamation-triangle';
                break;
              case 'security_neutral':
                $messageClass = 'info-message';
                $iconClass = 'fas fa-info-circle';
                break;
              case 'email_sent':
              default:
                $messageClass = 'success-message';
                $iconClass = 'fas fa-check-circle';
                break;
            }
          }
          ?>
          <div class="<?php echo $messageClass; ?>" role="alert">
            <i class="<?php echo $iconClass; ?>"></i>
            <div class="message-content">
              <div class="message-title"><?php echo htmlspecialchars($message); ?></div>
              <?php if (!empty($details)): ?>
                <div class="message-details"><?php echo htmlspecialchars($details); ?></div>
              <?php endif; ?>
              
              <?php if (isset($token) && $token): ?>
                <div class="test-link">
                  <a href="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" class="submit-button" style="display:inline-block;text-align:center;">
                    <i class="fas fa-key"></i> Reset Password Now
                  </a>
                </div>
              <?php endif; ?>
              
              <!-- OTP entry form: allow user to paste/enter OTP on this confirmation page -->
              <div style="margin-top:1rem;">
                <form method="POST" id="otpEntryForm" style="display:flex;flex-direction:column;gap:0.5rem;max-width:360px;">
                  <input type="hidden" name="verify_otp" value="1" />
                  <input type="hidden" name="token_for_redirect" value="<?php echo htmlspecialchars($token ?? ''); ?>" />
                  <input type="hidden" name="email_for_otp" value="<?php echo htmlspecialchars($requested_email ?? ($_POST['email'] ?? '')); ?>" />
                  <label class="form-label" for="otp_code">Enter OTP from email</label>
                  <input id="otp_code" name="otp_code" class="form-input" placeholder="Enter the numeric code" inputmode="numeric" pattern="\d{4,6}" required />
                  <button type="submit" class="submit-button" style="width:auto;display:inline-block;">Verify OTP</button>
                </form>
              </div>
            </div>
          </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
          <form method="POST" id="forgotPasswordForm">
            <div class="form-group">
              <label for="email" class="form-label">Email Address</label>
              <input 
                type="email" 
                id="email" 
                name="email" 
                class="form-input" 
                placeholder="Enter your institutional email address"
                required 
                autofocus
                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
              >
            </div>
            
            <button type="submit" class="submit-button" id="submitBtn">
              <span class="button-text">Send Reset Instructions</span>
            </button>
          </form>
        <?php endif; ?>
        
        <div class="back-to-login">
          <a href="login.php">
            <i class="fas fa-arrow-left"></i>
            Back to Login
          </a>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('forgotPasswordForm');
      const submitButton = document.getElementById('submitBtn');
      const buttonText = submitButton?.querySelector('.button-text');
      
      if (form && submitButton && buttonText) {
        form.addEventListener('submit', function(e) {
          // Add loading state
          submitButton.classList.add('loading');
          submitButton.disabled = true;
          buttonText.textContent = 'Sending...';
        });
      }
      
      // Enhanced input focus effects
      const inputs = document.querySelectorAll('.form-input');
      inputs.forEach(input => {
        input.addEventListener('focus', function() {
          this.parentNode.style.transform = 'translateY(-2px)';
        });
        
        input.addEventListener('blur', function() {
          this.parentNode.style.transform = 'translateY(0)';
        });
      });

      // Autofocus and digit-only behavior for OTP input if present
      const otpCodeInput = document.getElementById('otp_code');
      if (otpCodeInput) {
        otpCodeInput.focus();
        otpCodeInput.addEventListener('input', function() {
          this.value = this.value.replace(/[^0-9]/g, '').slice(0,6);
        });
      }
    });
  </script>
</body>
</html>