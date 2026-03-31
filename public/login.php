<?php
session_start();
include("../config/database.php");

// ===== ADDED: Login handler =====
// This processes the form when staff click "Sign in to dashboard"
if(isset($_POST['login'])){

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // Get user by username
    $query = "SELECT * FROM staff WHERE username='$username' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){
        $row = mysqli_fetch_assoc($result);

        // Check password (supports both hashed and plain text for backward compatibility)
        if(password_verify($password, $row['password']) || $password == $row['password']){

            $_SESSION['username'] = $row['username'];
            $_SESSION['fullname'] = $row['fullname']; // Using correct column name
            $_SESSION['role'] = $row['role'];

            // ===== FIXED: Redirect to dashboard.php =====
            header("Location: dashboard.php");
            exit;

        } else {
            echo "<script>alert('Invalid Username or Password');</script>";
        }

    } else {
        echo "<script>alert('Invalid Username or Password');</script>";
    }
}
// ===== END of login handler =====
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BPPO Staff Login · Bukidnon P&P</title>
  <!-- Google Font + Font Awesome (for icons & show/hide) -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --primary-dark: #1c3b2b;       /* dark green – main */
      --accent-yellow: #e7b13c;       /* yellow – highlights/button */
      --highlight-red: #c45151;       /* red – error messages */
      --pure-white: #ffffff;
      --light-border: #e0ede5;
      --input-bg: #fbfefc;
      --shadow-card: 0 20px 35px -8px rgba(0, 40, 20, 0.2), 0 8px 16px -6px rgba(0, 20, 0, 0.1);
      --focus-glow: 0 0 0 4px rgba(231, 177, 60, 0.2);
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: #eaf2ec;   /* soft neutral green background */
      background-image: radial-gradient(circle at 10% 20%, rgba(28,59,43,0.02) 0%, transparent 30%),
                        linear-gradient(145deg, #dae8df 0%, #ecf5ef 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }

    /* main card – white with soft shadow */
    .login-card {
      background-color: var(--pure-white);
      width: 100%;
      max-width: 440px;
      border-radius: 36px;
      padding: 2.8rem 2.2rem 2.5rem;
      box-shadow: var(--shadow-card);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border: 1px solid rgba(28,59,43,0.08);
      backdrop-filter: blur(2px);
    }

    .login-card:hover {
      box-shadow: 0 28px 45px -12px rgba(20, 60, 30, 0.25);
    }

    /* header area with seal & system title */
    .seal-area {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 2rem;
    }

    .seal-icon {
      background-color: var(--primary-dark);
      width: 70px;
      height: 70px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--accent-yellow);
      font-size: 2.4rem;
      border: 3px solid var(--accent-yellow);
      margin-bottom: 0.8rem;
    }

    .system-title {
      font-size: 1.6rem;
      font-weight: 700;
      color: var(--primary-dark);
      line-height: 1.2;
      text-align: center;
      letter-spacing: -0.01em;
    }

    .subtitle {
      font-size: 0.95rem;
      font-weight: 500;
      color: #4d705b;
      background: #edf6f0;
      padding: 0.4rem 1.4rem;
      border-radius: 60px;
      display: inline-block;
      margin-top: 0.5rem;
      border: 1px solid #d3e8da;
    }

    /* access warning – small red highlight */
    .access-warning {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      background-color: #fff6f0;
      border: 1px solid var(--highlight-red);
      color: var(--highlight-red);
      font-weight: 600;
      font-size: 0.85rem;
      padding: 0.65rem;
      border-radius: 60px;
      margin-bottom: 2rem;
    }

    .access-warning i {
      font-size: 1rem;
    }

    /* form elements */
    .input-group {
      margin-bottom: 1.4rem;
      position: relative;
    }

    .input-group label {
      display: block;
      font-weight: 600;
      font-size: 0.85rem;
      color: var(--primary-dark);
      margin-bottom: 0.3rem;
      letter-spacing: 0.3px;
    }

    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-wrapper i {
      position: absolute;
      left: 18px;
      color: #8aa896;
      font-size: 1.1rem;
    }

    .input-wrapper input {
      width: 100%;
      padding: 1rem 1rem 1rem 3rem;
      border: 1.5px solid #dae7df;
      border-radius: 24px;
      font-family: 'Inter', sans-serif;
      font-size: 1rem;
      background-color: var(--input-bg);
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .input-wrapper input:focus {
      border-color: var(--accent-yellow);
      outline: none;
      box-shadow: var(--focus-glow);
    }

    /* password show/hide toggle */
    .toggle-password {
      position: absolute;
      right: 18px;
      background: transparent;
      border: none;
      cursor: pointer;
      color: #6f8f7c;
      font-size: 1.2rem;
      display: flex;
      align-items: center;
      transition: color 0.2s;
    }

    .toggle-password:hover {
      color: var(--primary-dark);
    }

    /* options row: remember + forgot */
    .options-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin: 1rem 0 2rem;
      font-size: 0.9rem;
    }

    .remember-me {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: #2e4f39;
    }

    .remember-me input[type="checkbox"] {
      width: 18px;
      height: 18px;
      accent-color: var(--primary-dark);
      border-radius: 5px;
    }

    .forgot-link a {
      color: var(--primary-dark);
      font-weight: 600;
      text-decoration: none;
      border-bottom: 2px solid var(--accent-yellow);
    }

    .forgot-link a:hover {
      color: var(--highlight-red);
      border-color: var(--highlight-red);
    }

    /* login button (yellow) */
    .login-btn {
      background-color: var(--accent-yellow);
      color: #1d3b2d;
      font-weight: 700;
      font-size: 1.1rem;
      padding: 1.2rem;
      border: none;
      border-radius: 40px;
      width: 100%;
      cursor: pointer;
      transition: all 0.25s ease;
      box-shadow: 0 8px 16px rgba(231, 177, 60, 0.3);
      margin-bottom: 1rem;
    }

    .login-btn:hover {
      background-color: #daa73a;
      transform: translateY(-3px);
      box-shadow: 0 18px 26px rgba(200, 150, 40, 0.3);
    }

    /* back to landing button (outline style) */
    .back-btn {
      background: transparent;
      border: 2px solid var(--primary-dark);
      color: var(--primary-dark);
      font-weight: 600;
      padding: 0.9rem;
      border-radius: 40px;
      width: 100%;
      cursor: pointer;
      transition: 0.2s;
      margin-top: 0.5rem;
      font-size: 1rem;
    }

    .back-btn:hover {
      background-color: var(--primary-dark);
      color: white;
      border-color: var(--primary-dark);
    }

    /* signup link */
    .signup-text {
      text-align: center;
      margin-top: 1.8rem;
      font-size: 0.9rem;
      color: #3d5d4a;
    }

    .signup-text a {
      color: var(--primary-dark);
      font-weight: 700;
      text-decoration: none;
      border-bottom: 2px solid var(--accent-yellow);
    }

    .signup-text a:hover {
      color: var(--highlight-red);
    }

    /* error message placeholder (red) */
    .error-message {
      background-color: #ffeeee;
      border: 1px solid var(--highlight-red);
      color: var(--highlight-red);
      padding: 0.8rem 1rem;
      border-radius: 50px;
      margin-bottom: 1.5rem;
      font-size: 0.9rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
      display: none;
    }

    .error-message i {
      font-size: 1rem;
    }

    .error-message.show {
      display: flex;
    }

    /* small note */
    .secure-note {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      margin-top: 2rem;
      color: #5e7b6a;
      font-size: 0.8rem;
      border-top: 1px dashed #c9e0d3;
      padding-top: 1.5rem;
    }

    .secure-note i {
      color: var(--primary-dark);
    }

    /* responsive */
    @media (max-width: 460px) {
      .login-card { padding: 2rem 1.5rem; }
      .options-row { flex-direction: column; gap: 0.8rem; align-items: flex-start; }
    }
  </style>
</head>
<body>

<div class="login-card">

  <!-- Agency seal / logo area -->
  <div class="seal-area">
    <div class="seal-icon">
      <i class="fas fa-balance-scale"></i>
    </div>
    <div class="system-title">Bukidnon Parole & Probation<br>Client Inventory</div>
    <div class="subtitle">Authorized BPPO Staff Access Only</div>
  </div>

  <!-- small red-highlight warning (red accent) -->
  <div class="access-warning">
    <i class="fas fa-shield-alt"></i> Restricted access – authorized personnel only
  </div>

  <!-- LOGIN FORM – now with working PHP handler -->
  <form method="POST" id="loginForm">
    <!-- username field with icon -->
    <div class="input-group">
      <label>Username</label>
      <div class="input-wrapper">
        <i class="fas fa-user"></i>
        <input type="text" name="username" placeholder="e.g., jdelacruz" required>
      </div>
    </div>

    <!-- password field with show/hide toggle -->
    <div class="input-group">
      <label>Password</label>
      <div class="input-wrapper">
        <i class="fas fa-lock"></i>
        <input type="password" name="password" id="passwordField" placeholder="••••••••" required>
        <button type="button" class="toggle-password" id="togglePassword" tabindex="-1">
          <i class="far fa-eye-slash" id="toggleIcon"></i>
        </button>
      </div>
    </div>

    <!-- remember me + forgot password -->
    <div class="options-row">
      <label class="remember-me">
        <input type="checkbox" name="remember"> Remember me
      </label>
      <div class="forgot-link">
        <a href="changepassword.php">Forgot password?</a>
      </div>
    </div>

    <!-- login button (yellow) - now with working login handler -->
    <button type="submit" name="login" class="login-btn">Sign in to dashboard</button>
  </form>

  <!-- Back to landing (preserved) -->
  <a href="index.php">
    <button class="back-btn" type="button"><i class="fas fa-arrow-left"></i> Back to Landing Page</button>
  </a>

  <!-- signup / register link (preserved) -->
  <div class="signup-text">
    Don't have an account? <a href="register.php">Request access</a>
  </div>

  <!-- subtle security note -->
  <div class="secure-note">
    <i class="fas fa-lock"></i> 256-bit encrypted · official BPPO system
  </div>
</div>

<!-- script for show/hide password (UX enhancement only) -->
<script>
  (function() {
    // Show/hide password toggle – pure UX, does not affect POST
    const toggle = document.getElementById('togglePassword');
    const passwordField = document.getElementById('passwordField');
    const toggleIcon = document.getElementById('toggleIcon');

    if (toggle && passwordField) {
      toggle.addEventListener('click', function() {
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        // toggle icon
        if (type === 'text') {
          toggleIcon.classList.remove('fa-eye-slash');
          toggleIcon.classList.add('fa-eye');
        } else {
          toggleIcon.classList.remove('fa-eye');
          toggleIcon.classList.add('fa-eye-slash');
        }
      });
    }
  })();
</script>

</body>
</html>