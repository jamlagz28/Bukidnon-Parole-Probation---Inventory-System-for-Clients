<?php
include("includes/config.php");

if(isset($_POST['reset'])){
    $username = $_POST['username'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if($new_password !== $confirm_password){
        echo "<script>alert('Passwords do not match');</script>";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $query = "UPDATE staff SET password='$hashed_password' WHERE username='$username'";
        if(mysqli_query($conn, $query)){
            echo "<script>alert('Password successfully updated'); window.location.href='login.php';</script>";
            exit;
        } else {
            echo "<script>alert('Error updating password');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password</title>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">

<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { 
    font-family: 'Inter', sans-serif; 
    background: linear-gradient(145deg, #e0e7ff, #cfd8ff); 
    display: flex; justify-content: center; align-items: center; 
    height: 100vh; 
}

/* Reset Password Box */
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
    color: #1e3a8a; 
    font-weight: 600; 
    font-size: 24px; 
}

/* Inputs */
input { 
    width: 100%; 
    padding: 14px; 
    margin-bottom: 18px; 
    border: 1px solid #a5b4fc; 
    border-radius: 8px; 
    font-size: 14px; 
    transition: border-color 0.3s ease, box-shadow 0.3s ease; 
}
input:focus { 
    border-color: #1e40af; 
    box-shadow: 0 0 8px rgba(30, 64, 175, 0.3); 
    outline: none; 
}

/* Reset Button */
button { 
    width: 100%; 
    padding: 14px; 
    font-size: 15px; 
    font-weight: 600; 
    border: none; 
    border-radius: 8px; 
    cursor: pointer; 
    background: linear-gradient(135deg, #1e40af, #4338ca); 
    color: #fff; 
    transition: background 0.3s ease, transform 0.2s ease; 
}
button:hover { 
    background: linear-gradient(135deg, #4338ca, #1e3a8a); 
    transform: translateY(-2px);
}

/* Back to Login */
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
    <h2>Reset Password</h2>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="new_password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit" name="reset">Reset Password</button>
    </form>
    <div class="login-link">
        Remembered your password? <a href="login.php">Login here</a>
    </div>
</div>

</body>
</html>