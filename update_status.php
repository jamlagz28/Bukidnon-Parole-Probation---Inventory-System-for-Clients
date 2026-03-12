<?php
include 'config/database.php';

if(isset($_POST['id']) && isset($_POST['status'])){
    $id = intval($_POST['id']);
    $status = $_POST['status'];

    // Validate status value
    $valid_status = ['Active','Terminated','Revoked','Denied'];
    if(!in_array($status, $valid_status)){
        echo 'invalid';
        exit;
    }

    // Update the client status
    $stmt = $conn->prepare("UPDATE clients SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);

    if($stmt->execute()){
        echo 'success';
    } else {
        echo 'failed';
    }

    $stmt->close();
} else {
    echo 'failed';
}
?>