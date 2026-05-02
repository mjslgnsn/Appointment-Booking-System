<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Appoint — Premium Booking System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">

<div class="auth-split">
  <!-- Left Panel -->
  <div class="auth-brand">
    <div class="brand-inner">
      <div class="logo">
        <span class="logo-mark">A</span>
        <span class="logo-text">ppoint</span>
      </div>
      <div class="brand-headline">
        <h1>Your time,<br><em>perfectly</em><br>scheduled.</h1>
      </div>
      <div class="brand-features">
        <div class="feat"><span class="feat-dot"></span>Smart calendar booking</div>
        <div class="feat"><span class="feat-dot"></span>Instant confirmations</div>
        <div class="feat"><span class="feat-dot"></span>Real-time availability</div>
      </div>
      <div class="brand-decoration">
        <div class="dec-circle c1"></div>
        <div class="dec-circle c2"></div>
        <div class="dec-line"></div>
      </div>
    </div>
  </div>

  <!-- Right Panel -->
  <div class="auth-panel">
    <div class="auth-card">
      <div class="tabs">
        <button class="tab active" onclick="switchTab('login')">Sign In</button>
        <button class="tab" onclick="switchTab('register')">Create Account</button>
      </div>

      <!-- LOGIN FORM -->
      <form id="loginForm" class="auth-form active" onsubmit="handleLogin(event)">
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" id="loginEmail" placeholder="you@example.com" required>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" id="loginPassword" placeholder="••••••••" required>
          <a href="#" class="forgot-link">Forgot password?</a>
        </div>
        <div id="loginError" class="form-error"></div>
        <button type="submit" class="btn-primary full">
          <span>Sign In</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </button>
        <div class="demo-creds">
          <p><strong>Demo Customer:</strong> customer@demo.com / demo123</p>
          <p><strong>Demo Admin:</strong> admin@demo.com / admin123</p>
        </div>
      </form>

      <!-- REGISTER FORM -->
      <form id="registerForm" class="auth-form" onsubmit="handleRegister(event)">
        <div class="form-row">
          <div class="form-group">
            <label>First Name</label>
            <input type="text" id="regFirst" placeholder="Jane" required>
          </div>
          <div class="form-group">
            <label>Last Name</label>
            <input type="text" id="regLast" placeholder="Doe" required>
          </div>
        </div>
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" id="regEmail" placeholder="you@example.com" required>
        </div>
        <div class="form-group">
          <label>Phone Number</label>
          <input type="tel" id="regPhone" placeholder="+1 (555) 000-0000">
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" id="regPassword" placeholder="Min. 8 characters" required>
        </div>
        <div id="registerError" class="form-error"></div>
        <div id="registerSuccess" class="form-success"></div>
        <button type="submit" class="btn-primary full">
          <span>Create Account</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </button>
      </form>
    </div>
  </div>
</div>

<script src="js/auth.js"></script>
</body>
</html>
