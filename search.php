<?php
include 'config/database.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';

// Make search case-insensitive using LOWER()
$sql = "SELECT * FROM clients 
        WHERE LOWER(name) LIKE LOWER(?) 
           OR LOWER(docket_number) LIKE LOWER(?) 
           OR LOWER(cc_number) LIKE LOWER(?) 
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$likeQuery = "%".$search."%";
$stmt->bind_param("sss", $likeQuery, $likeQuery, $likeQuery);
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()){
    echo '<tr>
        <td>'.$row['docket_number'].'</td>
        <td>'.$row['name'].'</td>
        <td>'.$row['offense'].'</td>
        <td>'.$row['court'].'</td>
        <td>'.$row['status'].'</td>
    </tr>';
}
?>