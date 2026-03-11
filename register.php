<?php
include("config/database.php");

if(isset($_POST['register'])){

$fullname = $_POST['fullname'];
$username = $_POST['username'];
$password = $_POST['password'];
$role = $_POST['role'];

$query = "INSERT INTO staff (fullname, username, password, role)
VALUES ('$fullname','$username','$password','$role')";

if(mysqli_query($conn,$query)){
echo "<script>alert('Staff Registered Successfully'); window.location='index.php';</script>";
}
else{
echo "Error: ".mysqli_error($conn);
}

}
?>

<!DOCTYPE html>
<html>
<head>

<title>Register Staff</title>

<style>

body{
font-family: Arial;
background:#f4f4f4;
}

.container{
width:350px;
margin:100px auto;
padding:30px;
background:white;
border-radius:8px;
box-shadow:0 0 10px gray;
text-align:center;
}

input,select{
width:100%;
padding:10px;
margin:8px 0;
}

button{
width:100%;
padding:10px;
background:green;
color:white;
border:none;
cursor:pointer;
}

</style>

</head>

<body>

<div class="container">

<h2>Register Staff</h2>

<form method="POST">

<input type="text" name="fullname" placeholder="Full Name" required>

<input type="text" name="username" placeholder="Username" required>

<input type="password" name="password" placeholder="Password" required>

<select name="role">

<option value="main">Staff Main</option>

<option value="viewing">Staff Viewing</option>

</select>

<button type="submit" name="register">Register</button>

</form>

</div>

</body>
</html>