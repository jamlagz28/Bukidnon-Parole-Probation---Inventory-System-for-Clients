<?php
include 'config/database.php';
$q = $_GET['q'] ?? '';
if($q){
    $res = mysqli_query($conn,"SELECT name FROM clients WHERE name LIKE '%$q%' LIMIT 5");
    while($row = mysqli_fetch_assoc($res)){
        echo '<div>'.$row['name'].'</div>';
    }
}
?>