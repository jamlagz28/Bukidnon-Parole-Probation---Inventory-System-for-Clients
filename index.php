<?php
session_start();
include("config/database.php");

if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT * FROM staff WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn,$query);

    if(mysqli_num_rows($result) > 0){
        $_SESSION['username'] = $username;
        header("Location: dashboard.php");
        exit;
    } else {
        echo "<script>alert('Invalid Username or Password');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory System - Login</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family: Arial, sans-serif;
    background:#f1f3f4;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.login-box{
    width:100%;
    max-width:380px;
    background:white;
    padding:35px;
    border-radius:8px;
    box-shadow:0 2px 10px rgba(0,0,0,0.15);
}

.login-box h2{
    text-align:center;
    color:#1a73e8;
    margin-bottom:5px;
}

.login-box h4{
    text-align:center;
    margin-bottom:25px;
    color:#444;
    font-weight:normal;
}

.login-box input{
    width:100%;
    padding:12px;
    margin-bottom:15px;
    border:1px solid #ccc;
    border-radius:5px;
    font-size:14px;
}

/* Next button */
.next-btn{
    width:100%;
    padding:12px;
    background:#ccc;
    color:white;
    border:none;
    border-radius:5px;
    font-size:15px;
    cursor:not-allowed;
    transition:0.3s;
}

.next-btn.active{
    background:#1a73e8;
    cursor:pointer;
}

.next-btn.active:hover{
    background:#1558b0;
}

.bottom{
    margin-top:15px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    font-size:14px;
}

.create-account{
    color:#1a73e8;
    text-decoration:none;
    font-weight:500;
}

.create-account:hover{
    text-decoration:underline;
}

</style>
</head>

<body>

<div class="login-box">

<h2>Inventory System</h2>
<h4>Staff Sign in</h4>

<form method="POST" id="loginForm">

<input type="text" name="username" id="username" placeholder="Username" required>

<input type="password" name="password" id="password" placeholder="Password" required>

<button type="submit" name="login" id="nextBtn" class="next-btn" disabled>Next</button>

<div class="bottom">
<a href="register.php" class="create-account">Create account</a>
</div>

</form>

</div>

<script>

const username = document.getElementById("username");
const password = document.getElementById("password");
const button = document.getElementById("nextBtn");

function checkInputs(){
    if(username.value.trim() !== "" && password.value.trim() !== ""){
        button.disabled = false;
        button.classList.add("active");
    }else{
        button.disabled = true;
        button.classList.remove("active");
    }
}

username.addEventListener("input", checkInputs);
password.addEventListener("input", checkInputs);

</script>

</body>
</html>