<?php
include("config/database.php");

if(isset($_POST['register'])){
    $username = $_POST['username'];
    $fullname = $_POST['fullname'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Simple validation
    if(empty($username) || empty($fullname) || empty($password) || empty($role)){
        echo "<script>alert('Please fill all fields');</script>";
        exit;
    }

    // Insert into database
    $query="INSERT INTO staff(username, full_name, password, role) VALUES('$username','$fullname','$password','$role')";
    if(mysqli_query($conn,$query)){
        echo "<script> alert('Account Created Successfully'); window.location.href='login.php'; </script>";
        exit;
    } else {
        echo "<script>alert('Error: ".mysqli_error($conn)."');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register</title>
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<style>
/* Reset & Fonts */
* { box-sizing: border-box; margin: 0; padding: 0; }
body { 
    font-family: 'Inter', sans-serif; 
    background: linear-gradient(145deg, #e0e7ff, #cfd8ff); 
    display: flex; justify-content: center; align-items: center; 
    height: 100vh; 
}

/* Registration Box */
.box { 
    background: #ffffff; 
    padding: 40px 30px; 
    width: 360px; 
    border-radius: 12px; 
    box-shadow: 0 10px 25px rgba(0,0,0,0.15); 
    text-align: center; 
    transition: transform 0.3s ease, box-shadow 0.3s ease; 
}
.box:hover { 
    transform: translateY(-5px); 
    box-shadow: 0 15px 35px rgba(0,0,0,0.2); 
}

/* Heading */
.box h2 { 
    margin-bottom: 25px; 
    color: #1e3a8a; /* Deep blue for authority */ 
    font-weight: 600; 
    font-size: 24px; 
}

/* Inputs & Select */
input, select { 
    width: 100%; 
    padding: 14px; 
    margin-bottom: 18px; 
    border: 1px solid #a5b4fc; 
    border-radius: 8px; 
    font-size: 14px; 
    transition: border-color 0.3s ease, box-shadow 0.3s ease; 
}
input:focus, select:focus { 
    border-color: #1e40af; 
    box-shadow: 0 0 8px rgba(30, 64, 175, 0.3); 
    outline: none; 
}

/* Buttons */
button { 
    width: 100%; 
    padding: 14px; 
    font-size: 15px; 
    font-weight: 600; 
    border: none; 
    border-radius: 8px; 
    cursor: pointer; 
    transition: background 0.3s ease, transform 0.2s ease; 
}

/* Primary Register Button */
button[name="register"] { 
    background: linear-gradient(135deg, #1e40af, #4338ca); 
    color: #fff; 
}
button[name="register"]:hover { 
    background: linear-gradient(135deg, #4338ca, #1e3a8a); 
}

/* Back Button */
.back-btn { 
    background: #facc15; /* Subtle gold/yellow accent */ 
    color: #1e3a8a; 
    margin-top: 10px; 
}
.back-btn:hover { 
    background: #eab308; 
    color: #fff; 
}

/* Login Link */
.login-link { 
    margin-top: 15px; 
    font-size: 13px; 
    color: #555; 
}
.login-link a { 
    color: #1e40af; 
    font-weight: 600; 
    text-decoration: none; 
}
.login-link a:hover { 
    text-decoration: underline; 
}

/* Responsive */
@media (max-width: 400px) { 
    .box { width: 90%; padding: 30px 20px; } 
}
</style>
</head>
<body>
<div class="box">
    <h2>Create Staff Account</h2>
    <form method="POST">
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
    <!-- Back to Landing Page Button -->
    <a href="index.php"><button class="back-btn">← Back to Landing Page</button></a>
    <div class="login-link">
        Already have an account? <a href="login.php">Login here</a>
    </div>
</div>
</body>
</html>