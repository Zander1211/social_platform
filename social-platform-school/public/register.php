<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../src/Controller/AuthController.php';

$auth = new AuthController($pdo);

// If already logged in, go to the news feed
if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }

$error = null;
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = $auth->register($_POST);
    if ($res['status'] === 'success') {
        $success = 'Registration successful! Please log in to continue.';
        // Optionally redirect after a delay or show success message
    } else {
        $error = $res['message'] ?? 'Registration failed';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Academic Registration Portal - Educational Excellence Platform</title>
  <meta name="description" content="Join our academic community and create your educational account">
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
        radial-gradient(circle at 25% 25%, rgba(30, 58, 138, 0.08) 0%, transparent 50%),
        radial-gradient(circle at 75% 75%, rgba(245, 158, 11, 0.06) 0%, transparent 50%),
        radial-gradient(circle at 50% 50%, rgba(59, 130, 246, 0.04) 0%, transparent 70%);
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
          rgba(30, 58, 138, 0.02) 2px,
          rgba(30, 58, 138, 0.02) 4px
        );
      z-index: -1;
    }

    .register-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
      position: relative;
      z-index: 1;
    }

    .register-card {
      width: 100%;
      max-width: 1200px;
      background: var(--bg-primary);
      border-radius: 1.5rem;
      box-shadow: 
        0 25px 50px -12px rgba(0, 0, 0, 0.4),
        0 0 0 1px rgba(30, 58, 138, 0.1),
        0 0 20px rgba(30, 58, 138, 0.1);
      overflow: hidden;
      display: grid;
      grid-template-columns: 1fr 1fr;
      border: 3px solid var(--academic-green);
      position: relative;
      animation: cardFloat 6s ease-in-out infinite;
    }

    /* Academic Header Strip */
    .register-card::before {
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

    .academic-benefits {
      list-style: none;
      margin-top: 2rem;
    }

    .academic-benefits li {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1rem;
      font-size: 1rem;
      opacity: 0.9;
    }

    .academic-benefits i {
      width: 20px;
      color: var(--academic-gold);
    }

    .register-form-section {
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

    .register-form {
      margin-bottom: 2rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
      transition: transform 0.3s ease;
    }

    .form-group:hover {
      transform: translateY(-1px);
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
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
      transform: translateY(-1px);
    }

    .form-input::placeholder {
      color: var(--text-muted);
    }

    .role-selection {
      margin-bottom: 2rem;
    }

    .role-options {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
      margin-top: 0.5rem;
    }

    .role-option {
      position: relative;
    }

    .role-option input[type="radio"] {
      position: absolute;
      opacity: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
    }

    .role-card {
      padding: 1.5rem;
      border: 2px solid var(--academic-gray-200);
      border-radius: 0.75rem;
      text-align: center;
      transition: all 0.3s ease;
      cursor: pointer;
      background: var(--bg-primary);
    }

    .role-option input[type="radio"]:checked + .role-card {
      border-color: var(--academic-green);
      background: linear-gradient(135deg, var(--academic-green), var(--academic-green-dark));
      color: var(--text-inverse);
    }

    .role-card:hover {
      border-color: var(--academic-green);
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    .role-icon {
      font-size: 2rem;
      margin-bottom: 0.5rem;
      display: block;
    }

    .role-title {
      font-weight: 600;
      margin-bottom: 0.25rem;
    }

    .role-description {
      font-size: 0.875rem;
      opacity: 0.8;
    }

    .register-button {
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

    .register-button:hover {
      background: linear-gradient(135deg, var(--academic-green-dark) 0%, var(--academic-green) 100%);
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }

    .register-button:active {
      transform: translateY(0);
    }

    .form-footer {
      text-align: center;
      padding-top: 2rem;
      border-top: 1px solid var(--academic-gray-200);
    }

    .login-link {
      color: var(--text-muted);
      font-size: 0.875rem;
    }

    .login-link a {
      color: var(--academic-green);
      text-decoration: none;
      font-weight: 600;
      margin-left: 0.5rem;
    }

    .login-link a:hover {
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

    .success-message i {
      color: var(--academic-success);
    }

    /* Academic Decorative Elements */
    .academic-decoration {
      position: absolute;
      bottom: 2rem;
      right: 2rem;
      font-size: 6rem;
      color: rgba(245, 158, 11, 0.1);
      z-index: 1;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
      .register-card {
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
      .register-container {
        padding: 1rem;
      }
      
      .register-form-section {
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
      .form-row {
        flex-direction: column;
      }
      
      .brand-logo {
        width: 60px;
        height: 60px;
        font-size: 2rem;
      }
    }

    /* Loading State */
    .register-button.loading {
      opacity: 0.8;
      cursor: not-allowed;
      position: relative;
    }

    .register-button.loading::after {
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
      background: rgba(30, 58, 138, 0.6);
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
      background: rgba(245, 158, 11, 0.5);
    }

    .particle:nth-child(3) {
      top: 80%;
      left: 30%;
      animation-delay: 4s;
      animation-duration: 12s;
      background: rgba(59, 130, 246, 0.4);
    }

    .particle:nth-child(4) {
      top: 30%;
      left: 70%;
      animation-delay: 6s;
      animation-duration: 9s;
      background: rgba(30, 58, 138, 0.3);
    }

    .particle:nth-child(5) {
      top: 70%;
      left: 10%;
      animation-delay: 1s;
      animation-duration: 11s;
      background: rgba(245, 158, 11, 0.4);
    }

    /* Focus Accessibility */
    .form-input:focus,
    .register-button:focus,
    .login-link a:focus {
      outline: 3px solid var(--academic-gold);
      outline-offset: 2px;
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

    .success-message {
      background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
      color: var(--academic-success);
      border: 2px solid #a7f3d0;
      padding: 1rem 1.25rem;
      border-radius: 0.75rem;
      margin-bottom: 1.5rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .success-message i {
      color: var(--academic-success);
    }

    /* Academic Decorative Elements */
    .academic-decoration {
      position: absolute;
      bottom: 2rem;
      right: 2rem;
      font-size: 6rem;
      color: rgba(245, 158, 11, 0.1);
      z-index: 1;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
      .register-card {
        grid-template-columns: 1fr;
        max-width: 600px;
      }
      
      .academic-visual {
        padding: 2rem;
        text-align: center;
      }
      
      .institution-title {
        font-size: 2rem;
      }
      
      .academic-benefits {
        display: none;
      }
    }

    @media (max-width: 768px) {
      .register-container {
        padding: 1rem;
      }
      
      .register-form-section {
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
      
      .form-row,
      .role-options {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 480px) {
      .brand-logo {
        width: 60px;
        height: 60px;
        font-size: 2rem;
      }
    }

    /* Loading State */
    .register-button.loading {
      opacity: 0.8;
      cursor: not-allowed;
      position: relative;
    }

    .register-button.loading::after {
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

    /* Focus Accessibility */
    .form-input:focus,
    .register-button:focus,
    .login-link a:focus,
    .role-option input:focus + .role-card {
      outline: 3px solid var(--academic-gold);
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

  <div class="register-container">
    <div class="register-card">
      <!-- Academic Visual Section -->
      <div class="academic-visual">
        <div class="academic-brand">
          <div class="brand-logo">
            🎓
          </div>
          <h1 class="institution-title">Join Our Academic Community</h1>
          <p class="institution-subtitle">Begin Your Educational Journey Today</p>
          
          <ul class="academic-benefits">
            <li>
              <i class="fas fa-user-graduate"></i>
              <span>Connect with Students & Faculty</span>
            </li>
            <li>
              <i class="fas fa-book-open"></i>
              <span>Access Educational Resources</span>
            </li>
            <li>
              <i class="fas fa-comments"></i>
              <span>Participate in Academic Discussions</span>
            </li>
            <li>
              <i class="fas fa-calendar-alt"></i>
              <span>Stay Updated with Events</span>
            </li>
            <li>
              <i class="fas fa-certificate"></i>
              <span>Track Academic Progress</span>
            </li>
            <li>
              <i class="fas fa-shield-alt"></i>
              <span>Secure Academic Environment</span>
            </li>
          </ul>
        </div>
        
        <!-- Academic decoration -->
        <i class="fas fa-graduation-cap academic-decoration"></i>
      </div>
      
      <!-- Registration Form Section -->
      <div class="register-form-section">
        <div class="form-header">
          <h2 class="form-title">Create Academic Account</h2>
          <p class="form-subtitle">Join our educational excellence platform and connect with the academic community</p>
        </div>
        
        <?php if ($error): ?>
          <div class="error-message" role="alert">
            <i class="fas fa-exclamation-triangle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
          </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
          <div class="success-message" role="alert">
            <i class="fas fa-check-circle"></i>
            <span><?php echo htmlspecialchars($success); ?></span>
          </div>
        <?php endif; ?>
        
        <form method="POST" class="register-form" autocomplete="on">
          <div class="form-group">
            <label for="name" class="form-label">Full Name</label>
            <input 
              type="text" 
              id="name" 
              name="name" 
              class="form-input" 
              placeholder="Enter your full name"
              required 
              autofocus
              autocomplete="name"
              value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
            >
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="email" class="form-label">Academic Email</label>
              <input 
                type="email" 
                id="email" 
                name="email" 
                class="form-input" 
                placeholder="your.email@institution.edu"
                required
                autocomplete="email"
                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
              >
            </div>
            
            <div class="form-group">
              <label for="contact_number" class="form-label">Contact Number</label>
              <input 
                type="tel" 
                id="contact_number" 
                name="contact_number" 
                class="form-input" 
                placeholder="Your contact number"
                autocomplete="tel"
                value="<?php echo htmlspecialchars($_POST['contact_number'] ?? ''); ?>"
              >
            </div>
          </div>
          
          <div class="role-selection">
            <label class="form-label">Account Type</label>
            <div class="role-options">
              <div class="role-option">
                <input type="radio" id="student" name="role" value="student" <?php echo (!isset($_POST['role']) || $_POST['role'] === 'student') ? 'checked' : ''; ?>>
                <label for="student" class="role-card">
                  <i class="fas fa-user-graduate role-icon"></i>
                  <div class="role-title">Student</div>
                  <div class="role-description">Access courses and academic resources</div>
                </label>
              </div>
              
              <div class="role-option">
                <input type="radio" id="admin" name="role" value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'checked' : ''; ?>>
                <label for="admin" class="role-card">
                  <i class="fas fa-chalkboard-teacher role-icon"></i>
                  <div class="role-title">Administrator</div>
                  <div class="role-description">Manage academic platform and users</div>
                </label>
              </div>
            </div>
          </div>
          
          <div class="form-group">
            <label for="password" class="form-label">Secure Password</label>
            <input 
              type="password" 
              id="password" 
              name="password" 
              class="form-input" 
              placeholder="Create a strong password"
              required
              autocomplete="new-password"
              minlength="6"
            >
          </div>
          
          <button type="submit" class="register-button" id="registerBtn">
            <span class="button-text">Create Academic Account</span>
          </button>
        </form>
        
        <div class="form-footer">
          <div class="login-link">
            Already have an academic account?
            <a href="login.php">Sign in to your account</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Enhanced form submission with loading state
    document.addEventListener('DOMContentLoaded', function() {
      const registerForm = document.querySelector('.register-form');
      const registerButton = document.getElementById('registerBtn');
      const buttonText = registerButton.querySelector('.button-text');
      
      registerForm.addEventListener('submit', function(e) {
        // Basic validation
        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        
        if (name.length < 2) {
          alert('Please enter your full name');
          e.preventDefault();
          return;
        }
        
        if (!email.includes('@')) {
          alert('Please enter a valid email address');
          e.preventDefault();
          return;
        }
        
        if (password.length < 6) {
          alert('Password must be at least 6 characters long');
          e.preventDefault();
          return;
        }
        
        // Add loading state
        registerButton.classList.add('loading');
        registerButton.disabled = true;
        buttonText.textContent = 'Creating Account...';
        
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
      
      // Role card interactions
      const roleCards = document.querySelectorAll('.role-card');
      roleCards.forEach(card => {
        card.addEventListener('click', function() {
          const radio = this.parentNode.querySelector('input[type="radio"]');
          radio.checked = true;
        });
      });
      
      // Auto-hide success message after 5 seconds
      const successMessage = document.querySelector('.success-message');
      if (successMessage) {
        setTimeout(() => {
          successMessage.style.opacity = '0';
          setTimeout(() => {
            successMessage.style.display = 'none';
          }, 300);
        }, 5000);
      }
      
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