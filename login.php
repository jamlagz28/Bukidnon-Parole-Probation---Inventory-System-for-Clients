<?php
session_start();
include("config/database.php");

if(isset($_POST['login'])){

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // Get user by username
    $query = "SELECT * FROM staff WHERE username='$username' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){
        $row = mysqli_fetch_assoc($result);

        // If password in DB is hashed
        if(password_verify($password, $row['password']) || $password == $row['password']){

            $_SESSION['username'] = $row['username'];
            $_SESSION['fullname'] = $row['fullname']; // FIXED COLUMN NAME
            $_SESSION['role'] = $row['role'];

            header("Location: dashboard.php");
            exit;

        } else {
            echo "<script>alert('Invalid Username or Password');</script>";
        }

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
<title>BPPO Staff Login</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">

<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #eaf0fa, #d0e1f9);
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

:root {
    --primary-blue: #005BAC;
    --secondary-blue: #1a73e8;
    --gold-accent: #FFC107;
    --white: #ffffff;
}

.box {
    background: var(--white);
    padding: 45px 35px;
    width: 380px;
    border-radius: 14px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.15);
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.box:hover {
    transform: translateY(-5px);
    box-shadow: 0 18px 35px rgba(0,0,0,0.2);
}

.box h2 {
    margin-bottom: 30px;
    color: var(--primary-blue);
    font-weight: 700;
    font-size: 28px;
}

input {
    width: 100%;
    padding: 14px;
    margin-bottom: 18px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 15px;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

input:focus {
    border-color: var(--secondary-blue);
    box-shadow: 0 0 8px rgba(26,115,232,0.3);
    outline: none;
}

button {
    width: 100%;
    padding: 14px;
    font-size: 16px;
    font-weight: 600;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
}

button:hover { transform: translateY(-2px); }

button[name="login"] {
    background: linear-gradient(135deg, var(--secondary-blue), var(--primary-blue));
    color: var(--white);
    box-shadow: 0 6px 15px rgba(0,0,0,0.2);
}

button[name="login"]:hover {
    background: linear-gradient(135deg, var(--primary-blue), #094c9f);
}

.back-btn {
    background: var(--gold-accent);
    color: var(--primary-blue);
    margin-top: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.back-btn:hover {
    background: #e6b800;
    color: var(--white);
}

.forgot-password {
    margin: 12px 0;
}

.forgot-password a {
    color: var(--secondary-blue);
    font-weight: 600;
    text-decoration: none;
}

.forgot-password a:hover { text-decoration: underline; }

.signup-text {
    margin-top: 18px;
    font-size: 14px;
    color: #555;
}

.signup-text a {
    color: var(--secondary-blue);
    font-weight: 600;
    text-decoration: none;
}

.signup-text a:hover { text-decoration: underline; }

@media (max-width: 420px) {
    .box { width: 90%; padding: 35px 25px; }
}
</style>
</head>
<body>

<div class="box">
    <h2>BPPO Staff Login</h2>

    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>
    </form>

    <div class="forgot-password">
        <a href="changepassword.php">Forgot Password?</a>
    </div>

    <a href="index.php">
        <button class="back-btn" type="button">← Back to Landing Page</button>
    </a>

    <div class="signup-text">
        Don't have an account? <a href="register.php">Sign up here</a>
    </div>

</div>

</body>
</html>