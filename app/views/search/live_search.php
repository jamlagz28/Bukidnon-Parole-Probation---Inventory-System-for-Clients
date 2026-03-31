<?php
include '../../config/database.php';

$q = $_GET['q'] ?? '';

if($q) {
    $q = mysqli_real_escape_string($conn, $q);
    
    // Search in clients table ONLY for the main result
    $clients = mysqli_query($conn, "SELECT id, name, docket_number, 'client' as source FROM clients WHERE name LIKE '%$q%' OR docket_number LIKE '%$q%' LIMIT 10");
    
    $results = [];
    
    while($row = mysqli_fetch_assoc($clients)) {
        $results[] = $row;
    }
    
    // Display results
    foreach($results as $row) {
        echo '<div class="suggestion-item" data-id="'.$row['id'].'" data-name="'.$row['name'].'" data-docket="'.$row['docket_number'].'">';
        echo '<i class="fas fa-user" style="margin-right: 8px; color: #3b82f6;"></i>';
        echo '<strong>'.$row['name'].'</strong>';
        echo ' <span style="color: #64748b; font-size: 0.8rem;">('.$row['docket_number'].')</span>';
        echo '</div>';
    }
    
    if(empty($results)) {
        echo '<div class="suggestion-item" style="color: #64748b; justify-content: center;">No clients found</div>';
    }
}
?>