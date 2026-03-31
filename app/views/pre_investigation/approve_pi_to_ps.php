<?php
session_start();
include '../../config/database.php';
include '../../includes/permissions.php';

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$user_role = $_SESSION['role'] ?? 'staff';
$can_edit = canEdit($user_role);

if(!$can_edit) {
    header("Location: pi_list.php?error=unauthorized");
    exit();
}

if(!isset($_GET['id'])) {
    header("Location: pi_list.php");
    exit();
}

$pi_id = mysqli_real_escape_string($conn, $_GET['id']);

// Get PI case details
$pi_query = mysqli_query($conn, "SELECT * FROM pre_investigation WHERE id='$pi_id'");
if(mysqli_num_rows($pi_query) == 0) {
    header("Location: pi_list.php");
    exit();
}
$pi = mysqli_fetch_assoc($pi_query);

// Handle approval
if(isset($_POST['approve_to_ps'])) {
    mysqli_begin_transaction($conn);
    
    try {
        // 1. Update PI case status to Approved
        $update_pi = "UPDATE pre_investigation SET status='Approved', converted_to_ps=1 WHERE id='$pi_id'";
        mysqli_query($conn, $update_pi);
        
        // 2. Create PS case
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $supervising_officer = mysqli_real_escape_string($conn, $_POST['supervising_officer']);
        $monthly_fee = $_POST['monthly_fee'];
        
        // Generate PS docket number
        $ps_docket = "PS-" . date('Y') . "-" . str_pad($pi_id, 4, '0', STR_PAD_LEFT);
        
        $ps_query = "INSERT INTO probation_supervision (
            client_id, docket_number, name, offense, address, 
            start_date, end_date, supervising_officer, status, 
            monthly_fee, source_pi_id
        ) VALUES (
            '{$pi['client_id']}', '$ps_docket', '{$pi['name']}', '{$pi['offense']}', 
            '{$pi['address']}', '$start_date', '$end_date', '$supervising_officer', 
            'Active', '$monthly_fee', '$pi_id'
        )";
        
        if(!mysqli_query($conn, $ps_query)) {
            throw new Exception("Error creating PS case: " . mysqli_error($conn));
        }
        
        $ps_id = mysqli_insert_id($conn);
        
        // 3. Update client with PS case ID
        if($pi['client_id']) {
            $update_client = "UPDATE clients SET ps_case_id='$ps_id' WHERE id='{$pi['client_id']}'";
            mysqli_query($conn, $update_client);
        }
        
        mysqli_commit($conn);
        header("Location: ps_list.php?msg=created_from_pi");
        exit();
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }
}

$today = date('Y-m-d');
$end_default = date('Y-m-d', strtotime('+1 year'));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Approve PI to PS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; padding: 2rem; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 2rem; }
        h1 { margin-bottom: 2rem; color: #002b5c; }
        .pi-info { background: #f8fafc; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569; }
        input, select, textarea { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; }
        .btn { background: #10b981; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 8px; cursor: pointer; }
        .btn:hover { background: #059669; }
        .error { background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Approve PI Case to PS</h1>
        
        <div class="pi-info">
            <h3>PI Case Details</h3>
            <p><strong>Docket:</strong> <?php echo $pi['docket_number']; ?></p>
            <p><strong>Client:</strong> <?php echo $pi['name']; ?></p>
            <p><strong>Offense:</strong> <?php echo $pi['offense']; ?></p>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <h3 style="margin-bottom: 1rem;">PS Case Details</h3>
            
            <div class="form-group">
                <label>Start Date *</label>
                <input type="date" name="start_date" value="<?php echo $today; ?>" required>
            </div>
            
            <div class="form-group">
                <label>End Date *</label>
                <input type="date" name="end_date" value="<?php echo $end_default; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Supervising Officer *</label>
                <input type="text" name="supervising_officer" required>
            </div>
            
            <div class="form-group">
                <label>Monthly Fee (₱)</label>
                <input type="number" name="monthly_fee" value="500.00" step="0.01">
            </div>
            
            <button type="submit" name="approve_to_ps" class="btn">
                <i class="fas fa-check-circle"></i> Approve and Create PS Case
            </button>
            <a href="pi_list.php" style="margin-left: 1rem; color: #64748b;">Cancel</a>
        </form>
    </div>
</body>
</html>