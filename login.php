<?php
session_start();
include("config/database.php");

if(isset($_POST['login'])){

$username = $_POST['username'];
$password = $_POST['password'];

$query = "SELECT * FROM staff WHERE username='$username' AND password='$password'";
$result = mysqli_query($conn,$query);

if(mysqli_num_rows($result) > 0){
    $row = mysqli_fetch_assoc($result);
    $_SESSION['username'] = $row['username'];
    $_SESSION['fullname'] = $row['full_name'];
    $_SESSION['role'] = $row['role'];
    header("Location: dashboard.php");
    exit;
}else{
    echo "<script>alert('Invalid Username or Password');</script>";
}

}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login</title>
<style>
body{
font-family:Arial;
background:#f1f3f4;
display:flex;
justify-content:center;
align-items:center;
height:100vh;
}

.box{
background:white;
padding:35px;
width:350px;
border-radius:8px;
box-shadow:0 2px 10px rgba(0,0,0,0.2);
text-align:center;
}

input{
width:100%;
padding:12px;
margin-bottom:15px;
border:1px solid #ccc;
border-radius:5px;
}

button{
width:100%;
padding:12px;
background:#1a73e8;
color:white;
border:none;
border-radius:5px;
cursor:pointer;
margin-bottom:10px;
}

button:hover{
background:#0b57d0;
}

.back-btn{
background:#ccc;
color:#333;
margin-bottom:15px;
}

.back-btn:hover{
background:#999;
color:white;
}

.signup-text{
margin-top:10px;
font-size:14px;
}

.signup-text a{
color:#1a73e8;
text-decoration:none;
font-weight:bold;
}
</style>
</head>
<body>

<div class="box">

<h2>Staff Login</h2>

<form method="POST">

<input type="text" name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>

<button type="submit" name="login">Login</button>

</form>

<!-- Back to Landing Page Button -->
<a href="index.php"><button class="back-btn">← Back to Landing Page</button></a>

<div class="signup-text">
Don't have an account? <a href="register.php">Sign up here</a>
</div>

</div>

</body>
</html>