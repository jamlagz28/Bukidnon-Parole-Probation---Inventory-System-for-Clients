<?php
session_start();
include 'config/database.php';

// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Get user info
$fullname = $_SESSION['fullname'] ?? 'User';
$username = $_SESSION['username'] ?? '';
$user_role = $_SESSION['role'] ?? 'staff';

// Get user ID
$user_query = mysqli_query($conn, "SELECT id FROM staff WHERE username='$username'");
$user_data = mysqli_fetch_assoc($user_query);
$user_id = $user_data['id'] ?? 0;

// Handle form submission
if(isset($_POST['add_client'])) {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    // Get form data
    $docket_number = mysqli_real_escape_string($conn, $_POST['docket_number']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $cc_number = mysqli_real_escape_string($conn, $_POST['cc_number']);
    $court = mysqli_real_escape_string($conn, $_POST['court']);
    $offense = mysqli_real_escape_string($conn, $_POST['offense']);
    $sentence = mysqli_real_escape_string($conn, $_POST['sentence']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // CHECK IF CLIENT ALREADY EXISTS (by name)
    $check_client = mysqli_query($conn, "SELECT id FROM clients WHERE name = '$name'");
    
    if(mysqli_num_rows($check_client) > 0) {
        // ========== EXISTING CLIENT ==========
        // Get the existing client ID
        $client_data = mysqli_fetch_assoc($check_client);
        $client_id = $client_data['id'];
        
        // Find if there's a PENDING PI case for this client
        $find_pi = mysqli_query($conn, "SELECT id FROM pre_investigation WHERE client_id = '$client_id' AND status = 'Pending'");
        
        if(mysqli_num_rows($find_pi) > 0) {
            // Get the pending PI case
            $pi_data = mysqli_fetch_assoc($find_pi);
            $pi_id = $pi_data['id'];
            
            // 1. Update PI case status to 'Approved' (removed from pending)
            $update_pi = "UPDATE pre_investigation SET status = 'Approved' WHERE id = '$pi_id'";
            mysqli_query($conn, $update_pi);
            
            // 2. Create PS case (ACTIVE)
            $ps_docket = "PS-" . $docket_number;
            $monthly_fee = 500.00;
            
            $ps_query = "INSERT INTO probation_supervision (
                client_id, docket_number, name, offense, address, 
                start_date, end_date, status, monthly_fee, source_pi_id
            ) VALUES (
                '$client_id', '$ps_docket', '$name', '$offense', '$address',
                '$start_date', '$end_date', 'Active', '$monthly_fee', '$pi_id'
            )";
            
            if(mysqli_query($conn, $ps_query)) {
                $ps_id = mysqli_insert_id($conn);
                
                // 3. Update client with PS case ID
                mysqli_query($conn, "UPDATE clients SET ps_case_id = '$ps_id' WHERE id = '$client_id'");
                
                mysqli_commit($conn);
                header("Location: clients.php?msg=converted_to_ps");
                exit();
            } else {
                mysqli_rollback($conn);
                $error = "Error creating PS case: " . mysqli_error($conn);
            }
        } else {
            // No pending PI found, create new PI case
            $investigator = mysqli_real_escape_string($conn, $_POST['investigator']);
            $date_filed = $_POST['date_filed'];
            $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
            $pi_docket = "PI-" . $docket_number;
            
            $pi_query = "INSERT INTO pre_investigation (
                client_id, docket_number, name, cc_number, court, offense, 
                sentence, address, investigator, date_filed, status, remarks
            ) VALUES (
                '$client_id', '$pi_docket', '$name', '$cc_number', '$court', '$offense',
                '$sentence', '$address', '$investigator', '$date_filed', 'Pending', '$remarks'
            )";
            
            if(mysqli_query($conn, $pi_query)) {
                mysqli_commit($conn);
                header("Location: clients.php?msg=pi_added");
                exit();
            } else {
                mysqli_rollback($conn);
                $error = "Error creating PI case: " . mysqli_error($conn);
            }
        }
        
    } else {
        // ========== NEW CLIENT ==========
        // 1. Insert into clients table
        $client_query = "INSERT INTO clients (docket_number, name, cc_number, court, offense, sentence, address, start_date, end_date, status) 
                         VALUES ('$docket_number', '$name', '$cc_number', '$court', '$offense', '$sentence', '$address', '$start_date', '$end_date', 'Active')";
        
        if(mysqli_query($conn, $client_query)) {
            $client_id = mysqli_insert_id($conn);
            
            // 2. Create PI case (PENDING)
            $investigator = mysqli_real_escape_string($conn, $_POST['investigator']);
            $date_filed = $_POST['date_filed'];
            $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
            $pi_docket = "PI-" . $docket_number;
            
            $pi_query = "INSERT INTO pre_investigation (
                client_id, docket_number, name, cc_number, court, offense, 
                sentence, address, investigator, date_filed, status, remarks
            ) VALUES (
                '$client_id', '$pi_docket', '$name', '$cc_number', '$court', '$offense',
                '$sentence', '$address', '$investigator', '$date_filed', 'Pending', '$remarks'
            )";
            
            if(mysqli_query($conn, $pi_query)) {
                $pi_id = mysqli_insert_id($conn);
                
                // 3. Update client with PI case ID
                mysqli_query($conn, "UPDATE clients SET pi_case_id = '$pi_id' WHERE id = '$client_id'");
                
                mysqli_commit($conn);
                header("Location: clients.php?msg=client_added");
                exit();
            } else {
                mysqli_rollback($conn);
                $error = "Error creating PI case: " . mysqli_error($conn);
            }
        } else {
            mysqli_rollback($conn);
            $error = "Error adding client: " . mysqli_error($conn);
        }
    }
}

$today = date('Y-m-d');
$next_year = date('Y-m-d', strtotime('+1 year'));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New Client</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; padding: 2rem; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 2rem; }
        h1 { margin-bottom: 1rem; color: #0f172a; }
        .info { background: #f0f9ff; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; color: #0369a1; font-size: 0.95rem; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569; }
        input, select, textarea { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .btn { background: #0f172a; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 8px; cursor: pointer; }
        .btn:hover { background: #1e293b; }
        .error { background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .back-link { display: inline-block; margin-bottom: 1rem; color: #64748b; text-decoration: none; }
        .workflow-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-left: 0.5rem;
        }
        .badge-pi { background: #f59e0b; color: white; }
        .badge-ps { background: #10b981; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <a href="clients.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Clients</a>
        <h1>Add New Client</h1>
        <div class="info">
            <i class="fas fa-info-circle"></i> 
            <strong>Automatic Workflow:</strong><br>
            • New client → Creates PI case (PENDING)<br>
            • Existing client → Converts to PS case (ACTIVE) and removes from PI pending
        </div>

        <?php if(isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Docket Number *</label>
                    <input type="text" name="docket_number" required>
                </div>
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>CC Number</label>
                    <input type="text" name="cc_number">
                </div>
                <div class="form-group">
                    <label>Court *</label>
                    <input type="text" name="court" required>
                </div>
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

            <div class="form-row">
                <div class="form-group">
                    <label>Start Date *</label>
                    <input type="date" name="start_date" value="<?php echo $today; ?>" required>
                </div>
                <div class="form-group">
                    <label>End Date *</label>
                    <input type="date" name="end_date" value="<?php echo $next_year; ?>" required>
                </div>
            </div>

            <!-- PI Case Information -->
            <h3 style="margin: 2rem 0 1rem; color: #0f172a;">Investigator Information</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Investigator *</label>
                    <input type="text" name="investigator" required>
                </div>
                <div class="form-group">
                    <label>Date Filed *</label>
                    <input type="date" name="date_filed" value="<?php echo $today; ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Remarks</label>
                <textarea name="remarks" rows="2"></textarea>
            </div>

            <div style="margin-top: 2rem;">
                <button type="submit" name="add_client" class="btn">
                    <i class="fas fa-save"></i> Add Client
                </button>
            </div>
        </form>
    </div>
</body>
</html>