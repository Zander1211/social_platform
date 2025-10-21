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

$token = $_GET['token'] ?? '';
$debug = (isset($_GET['debug']) && $_GET['debug'] === '1'); // Set ?debug=1 to enable verbose debug output (dev only)
$error = null;
$success = false;
$message = null;

// Validate token on page load
if (empty($token)) {
    $error = 'Invalid reset link. Please request a new password reset.';
    $errorDetails = '';
} else {
    $tokenValidation = $auth->validateResetToken($token);
    if ($tokenValidation['status'] !== 'success') {
        $error = $tokenValidation['message'];
        $errorDetails = $tokenValidation['details'] ?? '';
    }
}

// If debug mode is enabled, expose token/validation info for development troubleshooting only
if ($debug) {
  // Do not enable debug mode in production. This prints sensitive info to the page for troubleshooting.
  $debugInfo = [
    'token' => $token,
    'tokenValidation' => $tokenValidation ?? null,
  ];
}

// Normalize tokenValidation and track whether the GET token was valid
$tokenValidation = $tokenValidation ?? ['status' => 'error', 'message' => 'No token provided'];
$tokenValid = ($tokenValidation['status'] === 'success');

// Handle form submission (process POST regardless of initial GET token state so users can paste tokens)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Clear any GET-related error so we validate the submitted token below
  $error = null;
  $password = $_POST['password'] ?? '';
  $confirmPassword = $_POST['confirm_password'] ?? '';

  // Use token submitted via POST if provided (allow paste from email), otherwise fall back to GET token
  $submittedToken = trim($_POST['token'] ?? $token);

  // Ensure token is still valid and get email associated with token
  $tokenValidation = $auth->validateResetToken($submittedToken);
  if ($tokenValidation['status'] !== 'success') {
    $error = $tokenValidation['message'];
    $errorDetails = $tokenValidation['details'] ?? '';
  }

  $emailForToken = $tokenValidation['email'] ?? null;

  if (!$error) {
    if (empty($password)) {
      $error = 'Please enter a new password';
    } elseif (strlen($password) < 6) {
      $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirmPassword) {
      $error = 'Passwords do not match';
    } else {
      $result = $auth->resetPassword($submittedToken, $password);
      if ($result['status'] === 'success') {
        $success = true;
        $message = $result['message'];
        $successDetails = $result['details'] ?? '';
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
  <title>Reset Password - Academic Excellence Platform</title>
  <meta name="description" content="Reset your password for the Educational Excellence Platform">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Georgia:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Keep token inputs visible even if global styles hide inputs elsewhere. */
    #token_input, .form-input {
      display: block !important;
      visibility: visible !important;
      opacity: 1 !important;
    }

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

    .reset-password-card {
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
    .reset-password-card::before {
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

    .password-strength {
      margin-top: 0.5rem;
      font-size: 0.875rem;
      color: var(--text-muted);
    }

    .password-strength.weak {
      color: #dc2626;
    }

    .password-strength.medium {
      color: #f59e0b;
    }

    .password-strength.strong {
      color: var(--academic-green);
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
      align-items: center;
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
    <div class="reset-password-card">
      <div class="card-content">
        <div class="form-header">
          <div class="brand-logo">
            🔑
          </div>
          <h1 class="form-title">Reset Password</h1>
          <p class="form-subtitle">
            Enter your new password below
          </p>
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
          <div class="success-message" role="alert">
            <i class="fas fa-check-circle"></i>
            <div class="message-content">
              <div class="message-title"><?php echo htmlspecialchars($message); ?></div>
              <?php if (!empty($successDetails)): ?>
                <div class="message-details"><?php echo htmlspecialchars($successDetails); ?></div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
          <form method="POST" id="resetPasswordForm">
            <?php if (!empty($token)): ?>
              <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>" />
            <?php else: ?>
              <div class="form-group">
                <label for="token_input" class="form-label">Reset Token (paste from email)</label>
                <input type="text" id="token_input" name="token" class="form-input" placeholder="Paste the reset token from your email here" required />
                <div class="form-hint" style="font-size:0.9rem;color:var(--text-muted);margin-top:6px">If the link didn't include a token, paste it here.</div>
              </div>
            <?php endif; ?>
            <?php if ($debug): ?>
              <div class="error-message" role="status" style="background:linear-gradient(135deg,#fff7ed 0%,#fffbeb 100%);color:#92400e;border-color:#ffedd5;margin-bottom:1rem;">
                <i class="fas fa-bug"></i>
                <div class="message-content">
                  <div class="message-title">Debug Mode Enabled</div>
                  <div class="message-details" style="color:inherit;">Token: <?php echo htmlspecialchars($token ?: '[empty]'); ?></div>
                  <?php if (!empty($tokenValidation)): ?>
                    <pre style="white-space:pre-wrap;margin-top:0.5rem;color:var(--text-secondary);background:transparent;border:0;padding:0;"><?php echo htmlspecialchars(json_encode($tokenValidation, JSON_PRETTY_PRINT)); ?></pre>
                  <?php endif; ?>
                  <div style="margin-top:.5rem;font-size:.9rem;">This is developer debug output. Remove <code>?debug=1</code> when finished.</div>
                </div>
              </div>
            <?php endif; ?>
            <div class="form-group">
            <div class="form-group">
              <label for="password" class="form-label">New Password</label>
              <input 
                type="password" 
                id="password" 
                name="password" 
                class="form-input" 
                placeholder="Enter your new password"
                required 
                minlength="6"
              >
              <div id="passwordStrength" class="password-strength"></div>
            </div>

            <div class="form-group">
              <label for="confirm_password" class="form-label">Confirm New Password</label>
              <input 
                type="password" 
                id="confirm_password" 
                name="confirm_password" 
                class="form-input" 
                placeholder="Confirm your new password"
                required
                minlength="6"
              >
            </div>

            <button type="submit" class="submit-button" id="submitBtn">
              <span class="button-text">Reset Password</span>
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
      const form = document.getElementById('resetPasswordForm');
      const submitButton = document.getElementById('submitBtn');
      const buttonText = submitButton?.querySelector('.button-text');
      const passwordInput = document.getElementById('password');
      const confirmPasswordInput = document.getElementById('confirm_password');
      const passwordStrength = document.getElementById('passwordStrength');
      
      // Password strength checker
      if (passwordInput && passwordStrength) {
        passwordInput.addEventListener('input', function() {
          const password = this.value;
          const strength = checkPasswordStrength(password);
          
          passwordStrength.textContent = strength.text;
          passwordStrength.className = 'password-strength ' + strength.class;
        });
      }
      
      // Password confirmation checker
      if (confirmPasswordInput && passwordInput) {
        confirmPasswordInput.addEventListener('input', function() {
          if (this.value && this.value !== passwordInput.value) {
            this.setCustomValidity('Passwords do not match');
          } else {
            this.setCustomValidity('');
          }
        });
      }

      // Focus OTP input if present
      
      if (form && submitButton && buttonText) {
        form.addEventListener('submit', function(e) {
          // Add loading state
          submitButton.classList.add('loading');
          submitButton.disabled = true;
          buttonText.textContent = 'Resetting...';
        });
      }
      
      function checkPasswordStrength(password) {
        if (password.length < 6) {
          return { text: 'Password must be at least 6 characters', class: 'weak' };
        }
        
        let score = 0;
        if (password.length >= 8) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        
        if (score < 3) {
          return { text: 'Weak password', class: 'weak' };
        } else if (score < 4) {
          return { text: 'Medium strength password', class: 'medium' };
        } else {
          return { text: 'Strong password', class: 'strong' };
        }
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
    });
  </script>
</body>
</html>