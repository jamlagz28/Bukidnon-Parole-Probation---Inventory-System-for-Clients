<?php
include 'includes/config.php';

$q = $_GET['q'] ?? '';

if($q) {
    $q = mysqli_real_escape_string($conn, $q);
    
    // Search in clients table
    $clients = mysqli_query($conn, "SELECT id, name, docket_number, 'client' as source FROM clients WHERE name LIKE '%$q%' OR docket_number LIKE '%$q%' LIMIT 5");
    
    // Search in PI cases
    $pi_cases = mysqli_query($conn, "SELECT id, name, docket_number, 'pi' as source FROM pre_investigation WHERE name LIKE '%$q%' OR docket_number LIKE '%$q%' LIMIT 5");
    
    // Search in PS cases
    $ps_cases = mysqli_query($conn, "SELECT id, name, docket_number, 'ps' as source FROM probation_supervision WHERE name LIKE '%$q%' OR docket_number LIKE '%$q%' LIMIT 5");
    
    $results = [];
    
    while($row = mysqli_fetch_assoc($clients)) {
        $results[] = $row;
    }
    while($row = mysqli_fetch_assoc($pi_cases)) {
        $results[] = $row;
    }
    while($row = mysqli_fetch_assoc($ps_cases)) {
        $results[] = $row;
    }
    
    // Sort by name
    usort($results, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    // Display results
    foreach($results as $row) {
        $icon = '';
        $color = '';
        
        if($row['source'] == 'client') {
            $icon = 'fa-user';
            $color = '#3b82f6';
        } elseif($row['source'] == 'pi') {
            $icon = 'fa-file-lines';
            $color = '#f59e0b';
        } elseif($row['source'] == 'ps') {
            $icon = 'fa-gavel';
            $color = '#10b981';
        }
        
        echo '<div class="suggestion-item" data-id="'.$row['id'].'" data-source="'.$row['source'].'" data-name="'.$row['name'].'" data-docket="'.$row['docket_number'].'">';
        echo '<i class="fas '.$icon.'" style="margin-right: 8px; color: '.$color.';"></i>';
        echo '<strong>'.$row['name'].'</strong>';
        echo ' <span style="color: #94a3b8; font-size: 0.8rem;">('.$row['docket_number'].')</span>';
        echo ' <span style="background: '.$color.'; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; margin-left: 5px;">'.strtoupper($row['source']).'</span>';
        echo '</div>';
    }
    
    if(empty($results)) {
        echo '<div class="suggestion-item" style="color: #64748b;">No results found</div>';
    }
}
?>