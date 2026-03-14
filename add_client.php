<?php
include 'config/database.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    // Validate inputs
    $docket  = isset($_POST['docket_number']) ? trim($_POST['docket_number']) : '';
    $name    = isset($_POST['name']) ? trim($_POST['name']) : '';
    $offense = isset($_POST['offense']) ? trim($_POST['offense']) : '';
    $court   = isset($_POST['court']) ? trim($_POST['court']) : '';
    $status  = isset($_POST['status']) ? trim($_POST['status']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';

    // Required fields validation
    if(empty($docket) || empty($name) || empty($offense) || empty($court)){
        echo "failed";
        exit();
    }

    // Prevent duplicate docket number
    $check = $conn->prepare("SELECT id FROM clients WHERE docket_number=?");
    $check->bind_param("s",$docket);
    $check->execute();
    $check->store_result();

    if($check->num_rows > 0){
        echo "duplicate";
        exit();
    }

    // Insert client
    $stmt = $conn->prepare("INSERT INTO clients 
        (docket_number, name, offense, court, status, address) 
        VALUES (?,?,?,?,?,?)");

    $stmt->bind_param("ssssss",$docket,$name,$offense,$court,$status,$address);

    if($stmt->execute()){
        echo "success";
    }else{
        echo "failed";
    }

    $stmt->close();
    $check->close();
}

?>