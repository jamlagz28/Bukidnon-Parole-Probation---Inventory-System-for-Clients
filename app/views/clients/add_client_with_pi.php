<?php
session_start();
include '../../config/database.php';
include '../../includes/permissions.php';

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'staff';
$can_edit = canEdit($user_role);

if(!$can_edit) {
    header("Location: dashboard.php?error=unauthorized");
    exit();
}

// Handle form submission
if(isset($_POST['add_client_with_pi'])) {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // 1. Insert into clients table
        $docket_number = mysqli_real_escape_string($conn, $_POST['docket_number']);
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $cc_number = mysqli_real_escape_string($conn, $_POST['cc_number']);
        $court = mysqli_real_escape_string($conn, $_POST['court']);
        $offense = mysqli_real_escape_string($conn, $_POST['offense']);
        $sentence = mysqli_real_escape_string($conn, $_POST['sentence']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        
        $client_query = "INSERT INTO clients (docket_number, name, cc_number, court, offense, sentence, address, status) 
                         VALUES ('$docket_number', '$name', '$cc_number', '$court', '$offense', '$sentence', '$address', 'Active')";
        
        if(!mysqli_query($conn, $client_query)) {
            throw new Exception("Error adding client: " . mysqli_error($conn));
        }
        
        $client_id = mysqli_insert_id($conn);
        
        // 2. Insert into pre_investigation table
        $investigator = mysqli_real_escape_string($conn, $_POST['investigator']);
        $date_filed = $_POST['date_filed'];
        $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
        
        $pi_query = "INSERT INTO pre_investigation (client_id, docket_number, name, cc_number, court, offense, sentence, address, investigator, date_filed, status, remarks) 
                     VALUES ('$client_id', '$docket_number', '$name', '$cc_number', '$court', '$offense', '$sentence', '$address', '$investigator', '$date_filed', 'Pending', '$remarks')";
        
        if(!mysqli_query($conn, $pi_query)) {
            throw new Exception("Error adding PI case: " . mysqli_error($conn));
        }
        
        $pi_id = mysqli_insert_id($conn);
        
        // 3. Update client with PI case ID
        $update_client = "UPDATE clients SET pi_case_id = '$pi_id' WHERE id = '$client_id'";
        mysqli_query($conn, $update_client);
        
        // Commit transaction
        mysqli_commit($conn);
        
        header("Location: clients.php?msg=added_with_pi");
        exit();
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }
}

$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Client with PI Case</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; padding: 2rem; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 2rem; }
        h1 { margin-bottom: 2rem; color: #002b5c; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569; }
        input, select, textarea { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; }
        .btn { background: #002b5c; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 8px; cursor: pointer; }
        .btn:hover { background: #001a33; }
        .error { background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .info-box { background: #e0f2fe; color: #0284c7; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add New Client with PI Case</h1>
        
        <div class="info-box">
            <i class="fas fa-info-circle"></i> 
            This will automatically create a PENDING PI case for this client.
        </div>
        
        <?php if(isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <h3 style="margin-bottom: 1rem; color: #002b5c;">Client Information</h3>
            
            <div class="form-group">
                <label>Docket Number *</label>
                <input type="text" name="docket_number" required>
            </div>
            
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" required>
            </div>
            
            <div class="form-group">
                <label>CC Number</label>
                <input type="text" name="cc_number">
            </div>
            
            <div class="form-group">
                <label>Court *</label>
                <input type="text" name="court" required>
            </div>
            
            <div class="form-group">
                <label>Offense *</label>
                <textarea name="offense" rows="3" required></textarea>
            </div>
            
            <div class="form-group">
                <label>Sentence *</label>
                <input type="text" name="sentence" required>
            </div>
            
            <div class="form-group">
                <label>Address *</label>
                <input type="text" name="address" required>
            </div>
            
            <h3 style="margin: 2rem 0 1rem; color: #002b5c;">PI Case Information</h3>
            
            <div class="form-group">
                <label>Investigator *</label>
                <input type="text" name="investigator" required>
            </div>
            
            <div class="form-group">
                <label>Date Filed *</label>
                <input type="date" name="date_filed" value="<?php echo $today; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Remarks</label>
                <textarea name="remarks" rows="2"></textarea>
            </div>
            
            <button type="submit" name="add_client_with_pi" class="btn">Add Client & Create PI Case</button>
            <a href="clients.php" style="margin-left: 1rem; color: #64748b;">Cancel</a>
        </form>
    </div>
</body>
</html>