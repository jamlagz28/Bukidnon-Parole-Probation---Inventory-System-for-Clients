<?php
include 'config/database.php';
if($_SERVER['REQUEST_METHOD']=='POST'){
    $docket = $_POST['docket_number'];
    $name = $_POST['name'];
    $offense = $_POST['offense'];
    $court = $_POST['court'];
    $status = $_POST['status'];
    $address = $_POST['address'];

    $stmt = $conn->prepare("INSERT INTO clients (docket_number,name,offense,court,status,address) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("ssssss",$docket,$name,$offense,$court,$status,$address);
    if($stmt->execute()) echo 'success';
    else echo 'failed';
}
?>