<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BPPO Staff Portal · Client Inventory System</title>

  <!-- Google Fonts & Font Awesome -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <style>
    /* ---------- RESET & VARIABLES ---------- */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --primary-dark: #1a4d2a;       /* dark green – main */
      --accent-yellow: #f2c94c;       /* yellow – highlights, buttons */
      --highlight-red: #d45252;       /* red – alerts / urgency */
      --pure-white: #ffffff;
      --bg-light: #f8fbf9;
      --border-light: #e2efe5;
      --shadow-card: 0 18px 36px -12px rgba(20, 60, 30, 0.15);
      --shadow-hover: 0 28px 44px -14px rgba(10, 50, 20, 0.25);
      --radius-card: 28px;
      --radius-btn: 50px;
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--pure-white);
      color: #1f2f28;
      line-height: 1.5;
      scroll-behavior: smooth;
    }

    /* ---------- TYPOGRAPHY ---------- */
    h1, h2, h3 {
      font-weight: 700;
      letter-spacing: -0.02em;
    }

    h1 {
      font-size: clamp(2.8rem, 6vw, 4.2rem);
      line-height: 1.1;
    }

    h2 {
      font-size: 2.5rem;
      margin-bottom: 1.2rem;
      color: var(--primary-dark);
    }

    h3 {
      font-size: 1.5rem;
      margin-bottom: 0.8rem;
    }

    p {
      font-size: 1.1rem;
      color: #2d4535;
    }

    .section-padding {
      padding: 5.5rem 2rem;
    }

    .container {
      max-width: 1280px;
      margin: 0 auto;
    }

    /* ---------- BUTTONS & INTERACTIVE ---------- */
    .btn {
      display: inline-block;
      background-color: var(--accent-yellow);
      color: #1d4228;
      font-weight: 600;
      padding: 0.9rem 2.4rem;
      border-radius: var(--radius-btn);
      text-decoration: none;
      font-size: 1rem;
      border: none;
      cursor: pointer;
      transition: all 0.25s ease;
      box-shadow: 0 8px 20px rgba(242, 201, 76, 0.3);
      border: 1px solid transparent;
    }

    .btn:hover {
      background-color: #e0b93c;
      transform: translateY(-4px);
      box-shadow: 0 18px 28px rgba(220, 180, 50, 0.35);
    }

    .btn-outline-light {
      background: transparent;
      border: 2px solid var(--accent-yellow);
      color: var(--accent-yellow);
      box-shadow: none;
    }

    .btn-outline-light:hover {
      background: var(--accent-yellow);
      color: #1a4d2a;
    }

    .btn-red {
      background-color: var(--highlight-red);
      color: var(--pure-white);
      box-shadow: 0 8px 18px rgba(212, 82, 82, 0.3);
    }

    .btn-red:hover {
      background-color: #be4646;
    }

    /* ---------- NAVIGATION (dark green) ---------- */
    .navbar {
      position: sticky;
      top: 0;
      z-index: 100;
      background-color: var(--primary-dark);
      padding: 1rem 2rem;
      box-shadow: 0 6px 14px rgba(0, 20, 0, 0.2);
    }

    .nav-container {
      max-width: 1280px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }

    .logo-area {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .logo-icon {
      background-color: var(--accent-yellow);
      width: 42px;
      height: 42px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary-dark);
      font-size: 1.5rem;
    }

    .logo-text {
      font-size: 1.6rem;
      font-weight: 700;
      color: var(--pure-white);
      letter-spacing: -0.5px;
    }

    .logo-text span {
      color: var(--accent-yellow);
      font-weight: 500;
    }

    .nav-buttons {
      display: flex;
      gap: 12px;
    }

    .nav-buttons a {
      text-decoration: none;
      padding: 10px 26px;
      border-radius: 40px;
      font-weight: 600;
      transition: 0.2s;
      font-size: 0.95rem;
    }

    .login-nav {
      background: var(--pure-white);
      color: var(--primary-dark);
      border: 2px solid transparent;
    }

    .login-nav:hover {
      background: #f0f7f2;
    }

    .signup-nav {
      background: var(--accent-yellow);
      color: var(--primary-dark);
    }

    .signup-nav:hover {
      background: #e2b93b;
      transform: translateY(-2px);
    }

    /* ---------- HERO (dark green gradient) ---------- */
    .hero {
      background: linear-gradient(165deg, #184229 0%, #26663b 100%);
      color: var(--pure-white);
      padding: 5rem 2rem;
      position: relative;
    }

    .hero::before {
      content: "";
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at 70% 30%, rgba(242,201,76,0.08) 0%, transparent 40%);
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

    .hero h1 {
      color: var(--pure-white);
    }

    .hero p {
      color: #ddf0e0;
      font-size: 1.25rem;
      margin: 1.5rem 0 2rem;
      max-width: 90%;
    }

    .hero-badge {
      background: rgba(255,255,255,0.1);
      backdrop-filter: blur(4px);
      display: inline-block;
      padding: 0.4rem 1.2rem;
      border-radius: 60px;
      color: var(--accent-yellow);
      font-weight: 600;
      font-size: 0.9rem;
      margin-bottom: 1rem;
      border: 1px solid rgba(242,201,76,0.3);
    }

    /* right side – dashboard preview / illustration */
    .hero-preview {
      background: rgba(255,255,255,0.03);
      backdrop-filter: blur(2px);
      border-radius: 48px;
      padding: 1.8rem;
      border: 1px solid rgba(242,201,76,0.2);
      box-shadow: 0 30px 40px -20px rgba(0,0,0,0.5);
    }

    .preview-card {
      background: var(--pure-white);
      border-radius: 28px;
      padding: 1.5rem;
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .preview-row {
      display: flex;
      align-items: center;
      gap: 12px;
      background: #f0faf3;
      padding: 1rem;
      border-radius: 20px;
    }

    .preview-icon {
      background: var(--primary-dark);
      color: var(--accent-yellow);
      width: 44px;
      height: 44px;
      border-radius: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
    }

    .preview-text {
      font-weight: 600;
      color: var(--primary-dark);
    }

    .preview-text small {
      font-weight: 400;
      color: #3b6750;
      display: block;
    }

    /* ---------- FEATURES / DASHBOARD PREVIEW SECTION (cards) ---------- */
    .features {
      background: var(--pure-white);
    }

    .section-title {
      text-align: center;
      max-width: 700px;
      margin: 0 auto 3.5rem;
    }

    .section-title h2 {
      color: var(--primary-dark);
    }

    .card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 2rem;
    }

    .feature-card {
      background: #ffffff;
      padding: 2.5rem 1.8rem;
      border-radius: var(--radius-card);
      box-shadow: var(--shadow-card);
      border: 1px solid var(--border-light);
      transition: all 0.3s ease;
      text-align: center;
    }

    .feature-card:hover {
      transform: translateY(-10px);
      box-shadow: var(--shadow-hover);
      border-color: var(--accent-yellow);
    }

    .feature-icon {
      background: #e9f3ec;
      width: 80px;
      height: 80px;
      border-radius: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.8rem;
      font-size: 2.3rem;
      color: var(--primary-dark);
      transition: 0.25s;
    }

    .feature-card:hover .feature-icon {
      background: var(--accent-yellow);
      color: #1a4d2a;
    }

    .feature-card h3 {
      color: var(--primary-dark);
    }

    /* ---------- STAFF TOOLS SECTION (highlight efficiency) ---------- */
    .staff-tools {
      background: #f2faf5;
    }

    .tools-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 2rem;
    }

    .tool-item {
      background: var(--pure-white);
      border-radius: 32px;
      padding: 2rem 1.5rem;
      box-shadow: 0 10px 22px rgba(0, 30, 10, 0.05);
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      border: 1px solid #e3f0e6;
    }

    .tool-item i {
      font-size: 2.2rem;
      color: var(--primary-dark);
      background: #eef7f0;
      padding: 0.8rem;
      border-radius: 24px;
      margin-bottom: 1.2rem;
    }

    /* ---------- ABOUT SECTION (BPPO context) ---------- */
    .about {
      background: var(--pure-white);
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
      background: linear-gradient(145deg, #1a4d2a, #286f3a);
      border-radius: 60px;
      height: 360px;
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

    .badge {
      background-color: var(--accent-yellow);
      color: #1a4d2a;
      padding: 0.3rem 1.3rem;
      border-radius: 60px;
      font-weight: 600;
      display: inline-block;
      margin-bottom: 1rem;
    }

    /* ---------- CTA SECTION (red/yellow accent) ---------- */
    .cta-section {
      background-color: var(--highlight-red);
      background-image: radial-gradient(circle at 10% 40%, rgba(255,240,200,0.1) 0%, transparent 40%);
      color: var(--pure-white);
      text-align: center;
      padding: 5rem 2rem;
    }

    .cta-section h2 {
      color: white;
      font-size: 3rem;
    }

    .cta-section p {
      color: #ffeae6;
      max-width: 600px;
      margin: 1.5rem auto 2.5rem;
    }

    .cta-section .btn {
      background-color: var(--pure-white);
      color: var(--highlight-red);
      box-shadow: 0 16px 28px rgba(140, 40, 40, 0.3);
      margin: 0 0.5rem;
    }

    .cta-section .btn-yellow {
      background-color: var(--accent-yellow);
      color: #1d3b2b;
      box-shadow: 0 8px 18px rgba(0,0,0,0.2);
    }

    .cta-section .btn-yellow:hover {
      background-color: #dbb03a;
    }

    /* ---------- FOOTER (organized, dark green) ---------- */
    footer {
      background-color: #173823;
      color: #d6f0de;
      padding: 3rem 2rem 2rem;
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
      margin-bottom: 1.3rem;
      font-size: 1.1rem;
    }

    .footer-col p, .footer-col a {
      color: #b7e0c5;
      text-decoration: none;
      line-height: 2;
      display: block;
      font-size: 0.95rem;
    }

    .footer-col a:hover {
      color: var(--accent-yellow);
    }

    .social-icons {
      display: flex;
      gap: 1rem;
      margin-top: 1.2rem;
    }

    .social-icons a {
      background-color: #29673e;
      width: 38px;
      height: 38px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
    }

    .social-icons a:hover {
      background-color: var(--accent-yellow);
      color: #173823;
    }

    .copyright {
      text-align: center;
      border-top: 1px solid #3a6b49;
      margin-top: 3rem;
      padding-top: 2rem;
      color: #9fc2ab;
    }

    /* ---------- RESPONSIVE ---------- */
    @media (max-width: 900px) {
      .hero .container {
        grid-template-columns: 1fr;
        text-align: center;
      }
      .hero p { margin-left: auto; margin-right: auto; }
      .nav-container { flex-direction: column; gap: 0.8rem; }
      .about-grid { flex-direction: column; text-align: center; }
    }
    @media (max-width: 500px) {
      .section-padding { padding: 3rem 1rem; }
    }
  </style>
</head>
<body>

<!-- NAVBAR (unchanged links / functionality) -->
<div class="navbar">
  <div class="nav-container">
    <div class="logo-area">
      <div class="logo-icon"><i class="fas fa-folder-open"></i></div>
      <div class="logo-text">BPPO <span>Staff Portal</span></div>
    </div>
    <div class="nav-buttons">
      <a href="login.php" class="login-nav">Login</a>
      <a href="register.php" class="signup-nav">Sign Up</a>
    </div>
  </div>
</div>

<!-- HERO SECTION (dark green, with dashboard preview) -->
<section class="hero">
  <div class="container">
    <div>
      <span class="hero-badge"><i class="fas fa-shield-alt"></i> Authorized personnel only</span>
      <h1>Bukidnon Parole & Probation<br><span style="color: var(--accent-yellow);">Staff System</span></h1>
      <p>Secure, efficient client record management for BPPO staff. Access files, monitor cases, and streamline reports – all in one centralized platform.</p>
      <a href="login.php" class="btn">Access dashboard <i class="fas fa-arrow-right"></i></a>
      <a href="#features" class="btn btn-outline-light" style="margin-left: 1rem;">Explore tools</a>
    </div>

    <!-- right side: dashboard preview / feature glimpse -->
    <div class="hero-preview">
      <div class="preview-card">
        <div class="preview-row">
          <div class="preview-icon"><i class="fas fa-users"></i></div>
          <div class="preview-text">Client records <small>1,284 active</small></div>
        </div>
        <div class="preview-row">
          <div class="preview-icon"><i class="fas fa-file-alt"></i></div>
          <div class="preview-text">Case monitoring <small>updated today</small></div>
        </div>
        <div class="preview-row">
          <div class="preview-icon"><i class="fas fa-chart-bar"></i></div>
          <div class="preview-text">Reports & analytics <small>monthly summary</small></div>
        </div>
        <div style="background: var(--accent-yellow); border-radius: 40px; padding: 0.8rem; color: #1a4d2a; font-weight: 600; margin-top: 0.8rem;">
          <i class="fas fa-check-circle"></i>  secure role‑based access
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FEATURES / DASHBOARD PREVIEW (card grid) -->
<section class="features section-padding" id="features">
  <div class="container">
    <div class="section-title">
      <h2>Designed for BPPO workflow</h2>
      <p>Core tools to manage parole and probation clients efficiently</p>
    </div>
    <div class="card-grid">
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-address-book"></i></div>
        <h3>Client record management</h3>
        <p>Add, edit, and archive client profiles with full case history.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-database"></i></div>
        <h3>File storage & retrieval</h3>
        <p>Securely store investigation documents, court orders, and notes.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-clock"></i></div>
        <h3>Case monitoring</h3>
        <p>Track hearing dates, compliance, and officer assignments.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-flag"></i></div>
        <h3>Reporting system</h3>
        <p>Generate reports for supervision, caseloads, and analytics.</p>
      </div>
    </div>
  </div>
</section>

<!-- STAFF TOOLS SECTION (efficiency & organization) -->
<section class="staff-tools section-padding">
  <div class="container">
    <div class="section-title">
      <h2>Tools built for BPPO staff</h2>
      <p>Everything you need in one clean interface</p>
    </div>
    <div class="tools-grid">
      <div class="tool-item"><i class="fas fa-search"></i><h3>Smart search</h3><p>Instant client lookup by name, docket, or officer.</p></div>
      <div class="tool-item"><i class="fas fa-tags"></i><h3>Role-based views</h3><p>Viewer, editor, admin – tailored access.</p></div>
      <div class="tool-item"><i class="fas fa-bell"></i><h3>Alerts & reminders</h3><p>Deadlines, hearings, follow‑ups in red.</p></div>
      <div class="tool-item"><i class="fas fa-file-pdf"></i><h3>Export records</h3><p>Generate case summaries or PDF reports.</p></div>
    </div>
  </div>
</section>

<!-- ABOUT SECTION (BPPO role) -->
<section class="about section-padding">
  <div class="container about-grid">
    <div class="about-image">
      <i class="fas fa-gavel"></i>
    </div>
    <div class="about-text">
      <span class="badge">About the office</span>
      <h2>Bukidnon Parole and Probation</h2>
      <p>BPPO supervises clients under parole and probation, assisting rehabilitation and monitoring compliance. Accurate digital records are essential for case tracking, legal compliance, and officer coordination.</p>
      <p>This staff-only system ensures confidential, real‑time access to client data – reducing paperwork and improving response time.</p>
    </div>
  </div>
</section>

<!-- CALL-TO-ACTION (red + yellow) -->
<section class="cta-section">
  <div class="container">
    <h2>Authorized staff, log in now</h2>
    <p>Access the full client inventory and case management tools. Secure, fast, and tailored for BPPO personnel.</p>
    <a href="login.php" class="btn btn-yellow"><i class="fas fa-sign-in-alt"></i> Login to dashboard</a>
    <a href="register.php" class="btn">Request account</a>
  </div>
</section>

<!-- FOOTER (organized, office info) -->
<footer>
  <div class="footer-grid">
    <div class="footer-col">
      <h4>Bukidnon P&P</h4>
      <p>Bukidnon Parole and Probation Office<br>Manolo Fortich, Bukidnon</p>
      <p>📞 (088) 813‑1245<br>📧 records@bppo.gov.ph</p>
    </div>
    <div class="footer-col">
      <h4>System links</h4>
      <a href="login.php">Staff login</a>
      <a href="register.php">Register</a>
      <a href="#">Support</a>
    </div>
    <div class="footer-col">
      <h4>Legal & security</h4>
      <a href="#">Data privacy</a>
      <a href="#">Acceptable use</a>
      <a href="#">Accessibility</a>
    </div>
    <div class="footer-col">
      <h4>Connect</h4>
      <div class="social-icons">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-linkedin-in"></i></a>
      </div>
      <p style="margin-top: 1.5rem;">ISO 27001 certified</p>
    </div>
  </div>
  <div class="copyright">
    <p>© 2025 BPPO Client Inventory System – for official use only. Created for Bukidnon Parole and Probation Office.</p>
  </div>
</footer>

<!-- All original functionality preserved: links point to login.php / register.php etc. -->
</body>
</html>