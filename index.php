<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BPPO Client Inventory System</title>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">

<style>
/* Reset & Fonts */
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; line-height: 1.6; background: #f4f6f9; }

/* Color Palette */
:root {
  --primary-blue: #005BAC;       /* BPPO blue */
  --secondary-blue: #1a73e8;     /* web-friendly hover */
  --gold-accent: #FFC107;        /* highlights */
  --text-dark: #333333;
  --white: #ffffff;
}

/* Navbar */
.navbar {
  display: flex; justify-content: space-between; align-items: center;
  padding: 15px 60px; background: var(--primary-blue); color: var(--white); box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  position: sticky; top: 0; z-index: 1000;
}
.logo { font-size: 24px; font-weight: 700; letter-spacing: 1px; }
.nav-buttons a {
  text-decoration: none; margin-left: 15px; padding: 10px 20px;
  border-radius: 6px; font-weight: 600; transition: 0.3s;
}
.login-btn { background: var(--white); color: var(--primary-blue); }
.login-btn:hover { background: #e0e0e0; }
.signup-btn { background: var(--gold-accent); color: var(--primary-blue); }
.signup-btn:hover { background: #e6b800; }

/* Hero Section */
.hero {
  display: flex; flex-direction: column; justify-content: center; align-items: center;
  text-align: center; height: 90vh;
  background: linear-gradient(135deg, rgba(0,91,172,0.7), rgba(26,115,232,0.6)), url('assets/images/hero-bg.jpg') no-repeat center center/cover;
  color: var(--white); padding: 20px;
}
.hero h1 { font-size: 48px; margin-bottom: 20px; text-shadow: 2px 2px 10px rgba(0,0,0,0.5); }
.hero p { font-size: 18px; max-width: 700px; margin-bottom: 30px; line-height: 1.5; }
.hero button {
  padding: 14px 30px; font-size: 16px; background: var(--secondary-blue);
  border: none; border-radius: 8px; cursor: pointer; font-weight: 600;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2); transition: 0.3s;
}
.hero button:hover { background: var(--primary-blue); transform: translateY(-2px); }

/* Features Section */
.features {
  display: flex; justify-content: center; flex-wrap: wrap; margin: 50px 20px; gap: 30px;
}
.card {
  background: var(--white); padding: 30px; border-radius: 12px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.1); width: 280px; text-align: center;
  transition: 0.3s; cursor: default;
}
.card:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,0.15); }
.card h3 { margin-bottom: 15px; color: var(--primary-blue); font-size: 20px; }
.card p { color: var(--text-dark); font-size: 15px; line-height: 1.4; }

/* Footer */
.footer {
  text-align: center; padding: 20px; background: #e0e0e0;
  color: var(--text-dark); font-size: 14px; margin-top: auto;
}
.footer a { color: var(--primary-blue); text-decoration: none; margin: 0 5px; }
.footer a:hover { text-decoration: underline; }

/* Responsive */
@media(max-width: 768px){
  .navbar { flex-direction: column; gap: 10px; padding: 15px 30px; }
  .hero h1 { font-size: 36px; }
  .features { flex-direction: column; align-items: center; }
}
</style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
  <div class="logo">BPPO Client Inventory</div>
  <div class="nav-buttons">
    <a href="login.php" class="login-btn">Login</a>
    <a href="register.php" class="signup-btn">Sign Up</a>
  </div>
</div>

<!-- Hero Section -->
<div class="hero">
  <h1>Bukidnon Parole and Probation Client Inventory</h1>
  <p>Manage client records, investigations, and monitoring for the Bukidnon Parole and Probation Office. Staff can securely manage client information and generate reports efficiently.</p>
  <a href="login.php"><button>Access System</button></a>
</div>

<!-- Features Section -->
<div class="features">
  <div class="card">
    <h3>Manage Clients</h3>
    <p>Easily add, view, and edit client records securely.</p>
  </div>
  <div class="card">
    <h3>Monitoring & Reports</h3>
    <p>Generate comprehensive reports for investigations and client monitoring.</p>
  </div>
  <div class="card">
    <h3>Staff Roles</h3>
    <p>Assign roles like Viewer or Editor to staff for secure access control.</p>
  </div>
</div>

<!-- Footer -->
<div class="footer">
  Created by: BSIT Student Intern | 
  <a href="#">Portfolio</a> | 
  <a href="#">LinkedIn</a>
</div>

</body>
</html>