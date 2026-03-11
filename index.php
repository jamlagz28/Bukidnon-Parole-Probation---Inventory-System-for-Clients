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
/* Reset some basic styles */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, sans-serif;
    background: #f4f4f4;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

/* Container for the login form */
.container {
    width: 100%;
    max-width: 400px;
    padding: 30px 25px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    text-align: center;
    animation: fadeIn 0.5s ease;
}

/* Headings */
.container h2 {
    margin-bottom: 10px;
    color: #2c7be5;
}

.container h3 {
    margin-bottom: 20px;
    color: #333;
}

/* Input fields */
input[type="text"],
input[type="password"] {
    width: 100%;
    padding: 12px;
    margin: 8px 0;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
}

/* Button styling */
button {
    width: 100%;
    padding: 12px;
    background: #2c7be5;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    transition: background 0.3s ease;
}

button:hover {
    background: #1a5bb8;
}

/* Links */
a {
    text-decoration: none;
    color: #2c7be5;
    font-weight: bold;
}

a:hover {
    text-decoration: underline;
}

/* Small paragraph below button */
.container p {
    margin-top: 15px;
    font-size: 14px;
    color: #555;
}

/* Responsive tweaks */
@media (max-width: 500px) {
    .container {
        padding: 20px 15px;
    }

    input[type="text"],
    input[type="password"],
    button {
        font-size: 14px;
        padding: 10px;
    }
}

/* Simple fade-in animation */
@keyframes fadeIn {
    from {opacity: 0; transform: translateY(-20px);}
    to {opacity: 1; transform: translateY(0);}
}
</style>
</head>
<body>

<div class="container">
    <h2>Inventory System</h2>
    <h3>Staff Login</h3>

    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>

        <p>Don't have an account?</p>
        <a href="register.php">Register Here</a>
    </form>
</div>

</body>
</html>