<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../src/Controller/AuthController.php';

$auth = new AuthController($pdo);

// If already logged in, go to the news feed
if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $res = $auth->login($email, $password);
    if ($res['status'] === 'success') {
        header('Location: index.php'); exit();
    }
    $error = $res['message'] ?? 'Login failed';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Academic Login Portal - Educational Excellence Platform</title>
  <meta name="description" content="Secure access to your academic account on the Educational Excellence Platform">
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

    /* Additional dark texture overlay */
    body::after {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: 
        repeating-linear-gradient(
          45deg,
          transparent,
          transparent 2px,
          rgba(16, 185, 129, 0.02) 2px,
          rgba(16, 185, 129, 0.02) 4px
        );
      z-index: -1;
    }

    .login-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
      position: relative;
      z-index: 1;
    }

    .login-card {
      width: 100%;
      max-width: 1100px;
      background: var(--bg-primary);
      border-radius: 1.5rem;
      box-shadow: 
        0 25px 50px -12px rgba(0, 0, 0, 0.4),
        0 0 0 1px rgba(16, 185, 129, 0.1),
        0 0 20px rgba(16, 185, 129, 0.1);
      overflow: hidden;
      display: grid;
      grid-template-columns: 1fr 1fr;
      border: 3px solid var(--academic-green);
      position: relative;
      animation: cardFloat 6s ease-in-out infinite;
    }

    /* Academic Header Strip */
    .login-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
      background: linear-gradient(90deg, var(--academic-green) 0%, var(--academic-teal) 50%, var(--academic-green) 100%);
      z-index: 10;
    }

    .academic-visual {
      background: linear-gradient(135deg, var(--academic-green) 0%, var(--academic-green-dark) 100%);
      padding: 3rem 2.5rem;
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: center;
      color: var(--text-inverse);
    }

    .academic-visual::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-image: 
        radial-gradient(circle at 20% 80%, rgba(20, 184, 166, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
    }

    .academic-brand {
      position: relative;
      z-index: 2;
    }

    .brand-logo {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, var(--academic-teal) 0%, var(--academic-teal-light) 100%);
      border-radius: 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      margin-bottom: 2rem;
      box-shadow: var(--shadow-lg);
      border: 3px solid rgba(255, 255, 255, 0.2);
    }

    .institution-title {
      font-family: 'Georgia', serif;
      font-size: 2.5rem;
      font-weight: 700;
      line-height: 1.2;
      margin-bottom: 1rem;
      background: linear-gradient(135deg, var(--text-inverse) 0%, var(--academic-teal-light) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .institution-subtitle {
      font-size: 1.25rem;
      font-weight: 400;
      opacity: 0.9;
      margin-bottom: 2rem;
      font-style: italic;
    }

    .academic-features {
      list-style: none;
      margin-top: 2rem;
    }

    .academic-features li {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1rem;
      font-size: 1rem;
      opacity: 0.9;
    }

    .academic-features i {
      width: 20px;
      color: var(--academic-teal-light);
    }

    .login-form-section {
      padding: 3rem 2.5rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
      background: var(--bg-primary);
    }

    .form-header {
      text-align: center;
      margin-bottom: 2.5rem;
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
    }

    .login-form {
      margin-bottom: 2rem;
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

    .form-options {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }

    .checkbox-group {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .checkbox-group input[type="checkbox"] {
      width: 1rem;
      height: 1rem;
      accent-color: var(--academic-green);
    }

    .checkbox-group label {
      font-size: 0.875rem;
      color: var(--text-secondary);
      cursor: pointer;
    }

    .forgot-link {
      color: var(--academic-green);
      text-decoration: none;
      font-size: 0.875rem;
      font-weight: 500;
      transition: color 0.3s ease;
    }

    .forgot-link:hover {
      color: var(--academic-green-light);
      text-decoration: underline;
    }

    .login-button {
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
    }

    .login-button:hover {
      background: linear-gradient(135deg, var(--academic-green-dark) 0%, var(--academic-green) 100%);
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }

    .login-button:active {
      transform: translateY(0);
    }

    .form-footer {
      text-align: center;
      padding-top: 2rem;
      border-top: 1px solid var(--academic-gray-200);
    }

    .register-link {
      color: var(--text-muted);
      font-size: 0.875rem;
    }

    .register-link a {
      color: var(--academic-green);
      text-decoration: none;
      font-weight: 600;
      margin-left: 0.5rem;
    }

    .register-link a:hover {
      text-decoration: underline;
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

    .error-message i {
      color: var(--academic-warning);
    }

    /* Academic Decorative Elements */
    .academic-decoration {
      position: absolute;
      bottom: 2rem;
      right: 2rem;
      font-size: 6rem;
      color: rgba(20, 184, 166, 0.1);
      z-index: 1;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
      .login-card {
        grid-template-columns: 1fr;
        max-width: 500px;
      }
      
      .academic-visual {
        padding: 2rem;
        text-align: center;
      }
      
      .institution-title {
        font-size: 2rem;
      }
      
      .academic-features {
        display: none;
      }
    }

    @media (max-width: 768px) {
      .login-container {
        padding: 1rem;
      }
      
      .login-form-section {
        padding: 2rem 1.5rem;
      }
      
      .academic-visual {
        padding: 1.5rem;
      }
      
      .institution-title {
        font-size: 1.75rem;
      }
      
      .form-title {
        font-size: 1.5rem;
      }
    }

    @media (max-width: 480px) {
      .form-options {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
      }
      
      .brand-logo {
        width: 60px;
        height: 60px;
        font-size: 2rem;
      }
    }

    /* Loading State */
    .login-button.loading {
      opacity: 0.8;
      cursor: not-allowed;
      position: relative;
    }

    .login-button.loading::after {
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

    @keyframes cardFloat {
      0%, 100% {
        transform: translateY(0px) scale(1);
      }
      50% {
        transform: translateY(-8px) scale(1.002);
      }
    }

    /* Floating particles animation */
    @keyframes float {
      0%, 100% {
        transform: translateY(0px) rotate(0deg);
        opacity: 0.7;
      }
      50% {
        transform: translateY(-20px) rotate(180deg);
        opacity: 1;
      }
    }

    /* Floating particles */
    .floating-particles {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 0;
    }

    .particle {
      position: absolute;
      width: 4px;
      height: 4px;
      background: rgba(16, 185, 129, 0.6);
      border-radius: 50%;
      animation: float 8s ease-in-out infinite;
    }

    .particle:nth-child(1) {
      top: 20%;
      left: 20%;
      animation-delay: 0s;
      animation-duration: 8s;
    }

    .particle:nth-child(2) {
      top: 60%;
      left: 80%;
      animation-delay: 2s;
      animation-duration: 10s;
      background: rgba(20, 184, 166, 0.5);
    }

    .particle:nth-child(3) {
      top: 80%;
      left: 30%;
      animation-delay: 4s;
      animation-duration: 12s;
      background: rgba(52, 211, 153, 0.4);
    }

    .particle:nth-child(4) {
      top: 30%;
      left: 70%;
      animation-delay: 6s;
      animation-duration: 9s;
      background: rgba(16, 185, 129, 0.3);
    }

    .particle:nth-child(5) {
      top: 70%;
      left: 10%;
      animation-delay: 1s;
      animation-duration: 11s;
      background: rgba(20, 184, 166, 0.4);
    }

    /* Focus Accessibility */
    .form-input:focus,
    .login-button:focus,
    .forgot-link:focus,
    .register-link a:focus {
      outline: 3px solid var(--academic-teal);
      outline-offset: 2px;
    }
  </style>
</head>
<body>
  <!-- Floating particles for enhanced dark theme -->
  <div class="floating-particles">
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
  </div>
  
  <div class="login-container">
    <div class="login-card">
      <!-- Academic Visual Section -->
      <div class="academic-visual">
        <div class="academic-brand">
          <div class="brand-logo">
            🎓
          </div>
          <h1 class="institution-title">Academic Excellence Platform</h1>
          <p class="institution-subtitle">Connecting Minds, Building Futures</p>
          
          <ul class="academic-features">
            <li>
              <i class="fas fa-graduation-cap"></i>
              <span>Comprehensive Academic Community</span>
            </li>
            <li>
              <i class="fas fa-users"></i>
              <span>Connect with Students & Faculty</span>
            </li>
            <li>
              <i class="fas fa-book-open"></i>
              <span>Access Educational Resources</span>
            </li>
            <li>
              <i class="fas fa-calendar-alt"></i>
              <span>Stay Updated with Academic Events</span>
            </li>
            <li>
              <i class="fas fa-shield-alt"></i>
              <span>Secure & Private Environment</span>
            </li>
          </ul>
        </div>
        
        <div class="academic-decoration">
          ✦
        </div>
      </div>
      
      <!-- Login Form Section -->
      <div class="login-form-section">
        <div class="form-header">
          <h2 class="form-title">Academic Portal Access</h2>
          <p class="form-subtitle">Sign in to your academic account to continue your educational journey</p>
        </div>
        
        <?php if ($error): ?>
          <div class="error-message" role="alert">
            <i class="fas fa-exclamation-triangle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
          </div>
        <?php endif; ?>
        
        <form method="POST" class="login-form" autocomplete="on">
          <div class="form-group">
            <label for="email" class="form-label">Academic Email Address</label>
            <input 
              type="email" 
              id="email" 
              name="email" 
              class="form-input" 
              placeholder="Enter your institutional email address"
              required 
              autofocus
              autocomplete="email"
            >
          </div>
          
          <div class="form-group">
            <label for="password" class="form-label">Secure Password</label>
            <input 
              type="password" 
              id="password" 
              name="password" 
              class="form-input" 
              placeholder="Enter your secure password"
              required
              autocomplete="current-password"
            >
          </div>
          
          <div class="form-options">
            <div class="checkbox-group">
              <input type="checkbox" id="remember" name="remember" value="1">
              <label for="remember">Remember my session</label>
            </div>
            <a href="forgot_password.php" class="forgot-link">Forgot your password?</a>
          </div>
          
          <button type="submit" class="login-button" id="loginBtn">
            <span class="button-text">Access Academic Portal</span>
          </button>
        </form>
        
        <div class="form-footer">
          <div class="register-link">
            New to our academic community?
            <a href="register.php">Create your academic account</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Enhanced form submission with loading state
    document.addEventListener('DOMContentLoaded', function() {
      const loginForm = document.querySelector('.login-form');
      const loginButton = document.getElementById('loginBtn');
      const buttonText = loginButton.querySelector('.button-text');
      
      loginForm.addEventListener('submit', function(e) {
        // Add loading state
        loginButton.classList.add('loading');
        loginButton.disabled = true;
        buttonText.textContent = 'Authenticating...';
        
        // Form will submit normally, so we don't prevent default
        // The loading state will be visible until page redirects or reloads
      });
      
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
      
      // Keyboard accessibility
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
          const form = e.target.closest('form');
          if (form) {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
              submitButton.click();
            }
          }
        }
      });
    });
  </script>
</body>
</html>