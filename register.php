<?php
// Include database connection at the VERY TOP
include("includes/config.php");

// ===== ADDED: Registration handler =====
// This processes the form when staff click "Register"
if(isset($_POST['register'])){

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Validation
    if(empty($username) || empty($fullname) || empty($password) || empty($role)){
        echo "<script>alert('Please fill all fields');</script>";
        exit;
    }

    // Encrypt password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert into database (using correct column names)
    $query = "INSERT INTO staff (username, fullname, password, role)
              VALUES ('$username','$fullname','$hashed_password','$role')";

    if(mysqli_query($conn, $query)){
        // ===== ADDED: Redirect to login page with success message =====
        // Staff can now use their new account to log in
        echo "<script>
                alert('Account created successfully! Please login with your credentials.');
                window.location.href='login.php';
              </script>";
        exit;
    } else {
        echo "<script>alert('Error: ".mysqli_error($conn)."');</script>";
    }
}
// ===== END of added registration handler =====
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bukidnon P&P · Client Inventory</title>
  <!-- Google Font & Font Awesome -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    /* --- RESET & THEME VARIABLES --- */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --primary-dark: #1d3b2f;      /* dark green – government稳重 */
      --accent-yellow: #e8b13e;      /* warm yellow – for highlights */
      --highlight-red: #c24f4f;      /* muted red – alerts / urgency */
      --pure-white: #ffffff;
      --light-bg: #f5f8f5;
      --border-light: #e2eae4;
      --shadow-card: 0 12px 28px rgba(0, 30, 10, 0.06), 0 4px 12px rgba(0, 20, 0, 0.04);
      --shadow-hover: 0 24px 40px rgba(27, 60, 40, 0.12);
      --radius-card: 28px;
      --radius-sm: 18px;
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--pure-white);
      color: #1f2f28;
      line-height: 1.5;
      scroll-behavior: smooth;
    }

    /* --- TYPOGRAPHY (strong hierarchy) --- */
    h1, h2, h3 {
      font-weight: 700;
      letter-spacing: -0.02em;
    }

    h1 {
      font-size: clamp(2.5rem, 6vw, 4rem);
      line-height: 1.1;
    }

    h2 {
      font-size: 2.4rem;
      margin-bottom: 1rem;
      color: var(--primary-dark);
    }

    h3 {
      font-size: 1.5rem;
      margin-bottom: 0.75rem;
    }

    p {
      font-size: 1.1rem;
      color: #2d4236;
      font-weight: 400;
    }

    .section-padding {
      padding: 5rem 2rem;
    }

    .container {
      max-width: 1280px;
      margin: 0 auto;
    }

    /* --- BUTTONS & INTERACTIONS --- */
    .btn {
      display: inline-block;
      background-color: var(--accent-yellow);
      color: #1d3b2f;
      font-weight: 600;
      padding: 0.9rem 2.4rem;
      border-radius: 50px;
      text-decoration: none;
      font-size: 1rem;
      border: none;
      cursor: pointer;
      transition: all 0.25s ease;
      box-shadow: 0 8px 18px rgba(232, 177, 62, 0.25);
      border: 1px solid transparent;
    }

    .btn:hover {
      background-color: #daa73a;
      transform: translateY(-4px);
      box-shadow: 0 18px 28px rgba(232, 177, 62, 0.3);
    }

    .btn-outline {
      background: transparent;
      border: 2px solid var(--accent-yellow);
      color: var(--accent-yellow);
      box-shadow: none;
    }

    .btn-outline:hover {
      background: var(--accent-yellow);
      color: #1d3b2f;
    }

    .btn-red {
      background-color: var(--highlight-red);
      color: var(--pure-white);
      box-shadow: 0 8px 18px rgba(194, 79, 79, 0.3);
    }

    .btn-red:hover {
      background-color: #b13e3e;
    }

    /* --- HEADER / NAV (dark green) --- */
    header {
      background-color: var(--primary-dark);
      padding: 1rem 2rem;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 4px 12px rgba(0,20,0,0.2);
    }

    .header-flex {
      display: flex;
      justify-content: space-between;
      align-items: center;
      max-width: 1280px;
      margin: 0 auto;
    }

    .logo-area {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .logo-icon {
      background-color: var(--accent-yellow);
      width: 40px;
      height: 40px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary-dark);
      font-size: 1.4rem;
    }

    .logo-text {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--pure-white);
    }

    .logo-text span {
      color: var(--accent-yellow);
      font-weight: 500;
    }

    .nav-links a {
      color: rgba(255,255,255,0.9);
      margin-left: 2.2rem;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s;
    }

    .nav-links a:hover {
      color: var(--accent-yellow);
    }

    /* --- HERO (dark green gradient) --- */
    .hero {
      background: linear-gradient(165deg, #173528 0%, #23523b 100%);
      color: white;
      padding: 4rem 2rem 5rem;
      position: relative;
    }

    .hero::before {
      content: "";
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at 80% 20%, rgba(232,177,62,0.08) 0%, transparent 40%);
      pointer-events: none;
    }

    .hero .container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 3rem;
      align-items: center;
      position: relative;
      z-index: 2;
    }

    .hero-title {
      color: white;
    }

    .hero-title p {
      color: #dbefdd;
      font-size: 1.3rem;
      margin: 1.5rem 0 2rem;
      max-width: 90%;
    }

    /* Registration card (original functionality) */
    .register-card {
      background: var(--pure-white);
      border-radius: var(--radius-card);
      padding: 2.5rem 2.2rem;
      box-shadow: var(--shadow-hover);
      width: 100%;
      max-width: 460px;
      margin-left: auto;
      border: 1px solid var(--border-light);
    }

    .register-card h3 {
      color: var(--primary-dark);
      font-size: 1.9rem;
      margin-bottom: 2rem;
      font-weight: 700;
      border-left: 6px solid var(--accent-yellow);
      padding-left: 1rem;
    }

    /* preserve original form elements – only visual refinement */
    .register-card input,
    .register-card select {
      width: 100%;
      padding: 15px 18px;
      margin-bottom: 1.2rem;
      border: 1px solid #cbdcd0;
      border-radius: 18px;
      font-family: 'Inter', sans-serif;
      font-size: 0.95rem;
      transition: 0.2s;
      background-color: #fefefd;
    }

    .register-card input:focus,
    .register-card select:focus {
      border-color: var(--accent-yellow);
      outline: none;
      box-shadow: 0 0 0 4px rgba(232, 177, 62, 0.18);
    }

    .register-card button {
      width: 100%;
      padding: 15px;
      border: none;
      border-radius: 40px;
      font-weight: 600;
      font-size: 1rem;
      transition: 0.2s;
      cursor: pointer;
    }

    button[name="register"] {
      background-color: var(--primary-dark);
      color: white;
      box-shadow: 0 6px 12px rgba(27, 60, 40, 0.3);
    }

    button[name="register"]:hover {
      background-color: #235b3c;
      transform: scale(1.02);
    }

    .back-btn {
      background-color: var(--accent-yellow) !important;
      color: #1d3b2f !important;
      margin-top: 0.6rem;
    }

    .back-btn:hover {
      background-color: #dbaa36 !important;
    }

    .login-link {
      margin-top: 1.2rem;
      font-size: 0.95rem;
      color: #386a4b;
    }

    .login-link a {
      color: var(--primary-dark);
      font-weight: 600;
      text-decoration: none;
      border-bottom: 2px solid var(--accent-yellow);
    }

    .login-link a:hover {
      color: var(--highlight-red);
    }

    /* --- FEATURES (white cards, yellow accents) --- */
    .features {
      background: var(--pure-white);
    }

    .section-title {
      text-align: center;
      max-width: 700px;
      margin: 0 auto 3rem;
    }

    .section-title p {
      font-size: 1.2rem;
      color: #2d5b40;
    }

    .card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 2rem;
    }

    .feature-card {
      background: #ffffff;
      padding: 2rem 1.5rem;
      border-radius: var(--radius-card);
      box-shadow: var(--shadow-card);
      border: 1px solid #eef5ef;
      transition: transform 0.3s, box-shadow 0.3s;
      text-align: center;
    }

    .feature-card:hover {
      transform: translateY(-8px);
      box-shadow: var(--shadow-hover);
      border-color: var(--accent-yellow);
    }

    .feature-icon {
      background: #ebf3ed;
      width: 72px;
      height: 72px;
      border-radius: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      font-size: 2.2rem;
      color: var(--primary-dark);
      transition: 0.25s;
    }

    .feature-card:hover .feature-icon {
      background: var(--accent-yellow);
      color: #1d3b2f;
    }

    .feature-card h3 {
      color: var(--primary-dark);
    }

    .feature-card p {
      color: #3d5e4b;
    }

    /* --- ABOUT section (image+text) --- */
    .about {
      background: #f4faf6;
    }

    .about-grid {
      display: flex;
      gap: 4rem;
      align-items: center;
      flex-wrap: wrap;
    }

    .about-image {
      flex: 1;
      min-width: 280px;
      background: linear-gradient(150deg, #1d3b2f, #2a6240);
      border-radius: 50px;
      height: 380px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: var(--shadow-hover);
    }

    .about-image i {
      font-size: 8rem;
      color: var(--accent-yellow);
      opacity: 0.9;
    }

    .about-text {
      flex: 1;
      min-width: 280px;
    }

    .about-text h2 {
      color: var(--primary-dark);
    }

    .about-text p {
      margin: 1.5rem 0;
    }

    .badge {
      background-color: var(--accent-yellow);
      color: #1d3b2f;
      padding: 0.3rem 1rem;
      border-radius: 40px;
      font-weight: 600;
      display: inline-block;
      font-size: 0.85rem;
      letter-spacing: 0.3px;
      text-transform: uppercase;
    }

    /* --- CTA (red / yellow accent) --- */
    .cta-section {
      background-color: var(--highlight-red);
      background-image: radial-gradient(circle at 30% 40%, rgba(255,255,240,0.1) 0%, transparent 35%);
      color: white;
      text-align: center;
      padding: 5rem 2rem;
    }

    .cta-section h2 {
      color: white;
      font-size: 2.8rem;
    }

    .cta-section p {
      color: rgba(255,255,255,0.95);
      font-size: 1.2rem;
      max-width: 600px;
      margin: 1.5rem auto 2.5rem;
    }

    .cta-section .btn {
      background-color: var(--pure-white);
      color: var(--highlight-red);
      box-shadow: 0 16px 24px rgba(130, 40, 40, 0.3);
      font-size: 1.1rem;
      padding: 1rem 3rem;
    }

    .cta-section .btn:hover {
      background-color: var(--accent-yellow);
      color: #1d3b2f;
    }

    /* --- FOOTER (organized, dark green) --- */
    footer {
      background-color: #173225;
      color: #d2ead9;
      padding: 3.5rem 2rem 2rem;
    }

    .footer-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
      gap: 2.5rem;
      max-width: 1280px;
      margin: 0 auto;
    }

    .footer-col h4 {
      color: white;
      margin-bottom: 1.5rem;
      font-size: 1.1rem;
      font-weight: 600;
    }

    .footer-col p, .footer-col a {
      color: #b0d5bc;
      text-decoration: none;
      line-height: 2;
      font-size: 0.95rem;
      display: block;
    }

    .footer-col a:hover {
      color: var(--accent-yellow);
    }

    .social-flex {
      display: flex;
      gap: 1rem;
      margin-top: 1rem;
    }

    .social-flex a {
      background-color: #2b543b;
      width: 38px;
      height: 38px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      transition: 0.2s;
    }

    .social-flex a:hover {
      background-color: var(--accent-yellow);
      color: #173225;
      transform: translateY(-3px);
    }

    .copyright {
      text-align: center;
      border-top: 1px solid #2a543a;
      margin-top: 3rem;
      padding-top: 2rem;
      color: #97b9a3;
      font-size: 0.9rem;
    }

    /* --- RESPONSIVE --- */
    @media (max-width: 900px) {
      .hero .container {
        grid-template-columns: 1fr;
        text-align: center;
      }
      .hero-title p { margin-left: auto; margin-right: auto; }
      .register-card { margin: 2rem auto 0; }
      .header-flex { flex-direction: column; gap: 0.8rem; }
      .nav-links a { margin: 0 0.8rem; }
    }

    @media (max-width: 500px) {
      .section-padding { padding: 3rem 1rem; }
      .btn { width: 100%; }
    }

    /* preserve original php, no functionality altered */
  </style>
</head>
<body>

<!-- HEADER (dark green, professional) -->
<header>
  <div class="header-flex">
    <div class="logo-area">
      <div class="logo-icon"><i class="fas fa-folder-tree"></i></div>
      <div class="logo-text">Bukidnon <span>P&P</span></div>
    </div>
    <div class="nav-links">
      <a href="#features">Features</a>
      <a href="#about">About</a>
      <a href="#cta">Access</a>
      <a href="login.php">Login</a>
    </div>
  </div>
</header>

<!-- HERO section: title + subtitle + preserved registration form -->
<section class="hero">
  <div class="container">
    <div class="hero-title">
      <h1>Bukidnon Parole and Probation<br><span style="color: var(--accent-yellow);">Client Inventory</span></h1>
      <p>Secure, centralized digital vault for client records. Designed for seamless tracking, retrieval, and management – tailored for Bukidnon operations.</p>
      <div class="hero-buttons">
        <a href="#features" class="btn">Explore system</a>
        <a href="#register-section" class="btn btn-outline">Register access</a>
      </div>
    </div>

    <!-- Registration card – with working PHP handler (now at top) -->
    <div class="register-card" id="register-section">
      <h3>Staff registration</h3>
      <form method="POST">
        <!-- ALL ORIGINAL FIELDS / NAMES KEPT -->
        <input type="text" name="username" placeholder="Username" required>
        <input type="text" name="fullname" placeholder="Full Name" required>
        <input type="password" name="password" placeholder="Password" required>
        <select name="role" required>
          <option value="">Select Staff Role</option>
          <option value="viewer">Staff Viewing</option>
          <option value="editor">Staff Edit Clients</option>
        </select>
        <button type="submit" name="register">Register</button>
      </form>
      <!-- Back button (links to index.php) -->
      <a href="index.php">
        <button class="back-btn" type="button"><i class="fas fa-arrow-left"></i> Back to Landing Page</button>
      </a>
      <div class="login-link">
        Already have an account? <a href="login.php">Login here</a>
      </div>
    </div>
  </div>
</section>

<!-- FEATURES section (cards with icons) -->
<section class="features section-padding" id="features">
  <div class="container">
    <div class="section-title">
      <h2>Key capabilities</h2>
      <p>Built for confidentiality, speed, and government-grade organization</p>
    </div>
    <div class="card-grid">
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-lock"></i></div>
        <h3>Secure file storage</h3>
        <p>Encrypted records, role‑based access, and audit logs protect sensitive parole data.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-address-book"></i></div>
        <h3>Client tracking</h3>
        <p>Organize by case number, officer, or status – full history at a glance.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-search"></i></div>
        <h3>Smart retrieval</h3>
        <p>Instant search by name, docket, or date. Find records in seconds.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-chart-pie"></i></div>
        <h3>Dashboard overview</h3>
        <p>User-friendly interface with caseload summaries and quick actions.</p>
      </div>
    </div>
  </div>
</section>

<!-- ABOUT section: Bukidnon Parole and Probation context -->
<section class="about section-padding" id="about">
  <div class="container about-grid">
    <div class="about-image">
      <i class="fas fa-gavel"></i> <!-- symbol of justice / probation -->
    </div>
    <div class="about-text">
      <span class="badge">About the office</span>
      <h2>Bukidnon Parole & Probation</h2>
      <p>We oversee clients under parole and probation, ensuring rehabilitation and compliance. Proper record management is vital to monitor case progress, legal documents, and supervision history.</p>
      <p>This digital inventory replaces paper‑based clutter – offering a confidential, organized, and instantly accessible database for officers and staff.</p>
      <a href="#" class="btn btn-outline" style="border-color: var(--primary-dark); color: var(--primary-dark);">Learn more about P&P</a>
    </div>
  </div>
</section>

<!-- CALL TO ACTION (red/yellow urgent, for authorized access) -->
<section class="cta-section" id="cta">
  <div class="container">
    <h2>Authorized personnel only</h2>
    <p>Log in to manage client records securely. Register only if you are a designated staff member of Bukidnon Parole and Probation.</p>
    <a href="login.php" class="btn">Go to login portal</a>
    <a href="#register-section" class="btn btn-red" style="margin-left: 1rem;">Request account</a>
  </div>
</section>

<!-- FOOTER (contact info, office details, links) -->
<footer>
  <div class="footer-grid">
    <div class="footer-col">
      <h4>Bukidnon P&P</h4>
      <p>Parole and Probation Office<br>Malaybalay City, Bukidnon</p>
      <p>📞 +63 (88) 813 1245<br>📧 records@bukidnon-pp.gov.ph</p>
    </div>
    <div class="footer-col">
      <h4>Quick links</h4>
      <a href="#">Data privacy</a>
      <a href="#">eServices</a>
      <a href="#">Staff directory</a>
      <a href="#">Help desk</a>
    </div>
    <div class="footer-col">
      <h4>Legal</h4>
      <a href="#">Terms of use</a>
      <a href="#">Accessibility</a>
      <a href="#">Security</a>
    </div>
    <div class="footer-col">
      <h4>Connect</h4>
      <div class="social-flex">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-government"></i></a>
      </div>
      <p style="margin-top: 1.5rem;">ISO 27001 certified<br>secure infrastructure</p>
    </div>
  </div>
  <div class="copyright">
    <p>© 2025 Bukidnon Parole and Probation · Client Inventory System. All rights reserved. |  designed for public service</p>
  </div>
</footer>

</body>
</html>