<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Client Inventory System</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
}

body{
font-family:Arial, sans-serif;
background:#f4f6f9;
display:flex;
flex-direction:column;
min-height:100vh;
}

/* Navbar */
.navbar{
display:flex;
justify-content:space-between;
align-items:center;
padding:15px 60px;
background:#1a73e8;
color:white;
}

.logo{
font-size:20px;
font-weight:bold;
}

.nav-buttons a{
text-decoration:none;
margin-left:15px;
padding:8px 18px;
border-radius:5px;
font-weight:bold;
}

.login-btn{
background:white;
color:#1a73e8;
}

.signup-btn{
background:#0b57d0;
color:white;
}

/* Hero Section */
.hero{
flex:1;
height:90vh;
display:flex;
flex-direction:column;
justify-content:center;
align-items:center;
text-align:center;
padding:20px;
}

.hero h1{
font-size:40px;
color:#333;
margin-bottom:15px;
}

.hero p{
font-size:18px;
color:#555;
max-width:700px;
}

.hero button{
margin-top:25px;
padding:12px 25px;
font-size:16px;
background:#1a73e8;
color:white;
border:none;
border-radius:6px;
cursor:pointer;
}

.hero button:hover{
background:#0b57d0;
}

/* Footer */
.footer{
text-align:center;
padding:15px;
background:#e0e0e0;
color:#333;
font-size:14px;
}

</style>
</head>

<body>

<div class="navbar">

<div class="logo">
Client Inventory System
</div>

<div class="nav-buttons">
<a href="login.php" class="login-btn">Login</a>
<a href="register.php" class="signup-btn">Sign Up</a>
</div>

</div>

<div class="hero">

<h1>Bukidnon Parole and Probation Client Inventory</h1>

<p>
This system helps manage client records, investigations, and monitoring
for the Bukidnon Parole and Probation Office. Staff can securely manage
client information and generate reports efficiently.
</p>

<a href="login.php">
<button>Access System</button>
</a>

</div>

<!-- Footer -->
<div class="footer">
Created by: BSIT Student
</div>

</body>
</html>