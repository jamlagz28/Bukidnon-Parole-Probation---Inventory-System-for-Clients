<?php
include 'config/database.php';

if(isset($_POST['id'])){
    $id = intval($_POST['id']);
    $delete = mysqli_query($conn, "DELETE FROM clients WHERE id='$id'");
    if($delete){
        echo 'success';
    } else {
        echo 'error';
    }
} else {
    echo 'error';
}
?>