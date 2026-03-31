<?php
session_start();
include 'includes/config.php';

// Check login
if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'staff';
$can_edit = ($user_role == 'main' || $user_role == 'admin');

// Redirect if not authorized
if(!$can_edit) {
    header("Location: ps_list.php?error=unauthorized");
    exit();
}

// Get client_id from URL if provided
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$client_name = '';
$client_address = '';
$client_offense = '';

// If client_id is provided, get client details
if($client_id > 0) {
    $client_query = mysqli_query($conn, "SELECT * FROM clients WHERE id = '$client_id'");
    if(mysqli_num_rows($client_query) > 0) {
        $client = mysqli_fetch_assoc($client_query);
        $client_name = $client['name'];
        $client_address = $client['address'];
        $client_offense = $client['offense'];
    }
}

// Handle form submission
if(isset($_POST['add_ps'])) {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    $docket_number = mysqli_real_escape_string($conn, $_POST['docket_number']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $offense = mysqli_real_escape_string($conn, $_POST['offense']);
    $payment = floatval($_POST['payment']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $supervising_officer = mysqli_real_escape_string($conn, $_POST['supervising_officer']);
    $status = $_POST['status'];
    $monthly_fee = floatval($_POST['monthly_fee']);
    $client_id = intval($_POST['client_id']);
    
    // Calculate next payment date (1 month from start date)
    $next_payment_date = date('Y-m-d', strtotime($start_date . ' +1 month'));
    
    // Get current user ID
    $user_query = mysqli_query($conn, "SELECT id FROM staff WHERE username='{$_SESSION['username']}'");
    $user = mysqli_fetch_assoc($user_query);
    $created_by = $user['id'];
    
    // If no client_id provided, check if client exists by name
    if($client_id == 0) {
        $check_client = mysqli_query($conn, "SELECT id FROM clients WHERE name = '$name'");
        if(mysqli_num_rows($check_client) > 0) {
            $client_data = mysqli_fetch_assoc($check_client);
            $client_id = $client_data['id'];
        }
    }
    
    if($client_id > 0) {
        // Insert PS case with client_id
        $query = "INSERT INTO probation_supervision (
            client_id, docket_number, name, offense, payment, address, 
            start_date, end_date, supervising_officer, status, monthly_fee, 
            next_payment_date, created_by
        ) VALUES (
            '$client_id', '$docket_number', '$name', '$offense', '$payment', 
            '$address', '$start_date', '$end_date', '$supervising_officer', 
            '$status', '$monthly_fee', '$next_payment_date', '$created_by'
        )";
        
        if(mysqli_query($conn, $query)) {
            $ps_id = mysqli_insert_id($conn);
            
            // Update client status to Active and link PS case
            mysqli_query($conn, "UPDATE clients SET status = 'Active', ps_case_id = '$ps_id' WHERE id = '$client_id'");
            
            // Update any pending PI cases to Approved
            mysqli_query($conn, "UPDATE pre_investigation SET status = 'Approved' WHERE client_id = '$client_id' AND status = 'Pending'");
            
            mysqli_commit($conn);
            
            // Redirect back to client profile
            header("Location: client_details.php?id=$client_id&msg=ps_added");
            exit();
        } else {
            mysqli_rollback($conn);
            $error = "Error: " . mysqli_error($conn);
        }
    } else {
        $error = "Client not found. Please select an existing client.";
    }
}

$today = date('Y-m-d');
$next_year = date('Y-m-d', strtotime('+1 year'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add PS Case</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; padding: 2rem; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 2rem; }
        h1 { margin-bottom: 1rem; color: #0f172a; }
        .info { background: #f0f9ff; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; color: #0369a1; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569; }
        input, select, textarea { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-family: 'Inter', sans-serif; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .btn { background: #0f172a; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 8px; cursor: pointer; }
        .btn:hover { background: #1e293b; }
        .error { background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .back-link { display: inline-block; margin-bottom: 1rem; color: #64748b; text-decoration: none; }
        .client-info { background: #f8fafc; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border-left: 4px solid #10b981; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <a href="<?php echo $client_id ? 'client_details.php?id='.$client_id : 'ps_list.php'; ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        
        <h1>Add Probation Supervision Case</h1>
        
        <?php if($client_id > 0): ?>
        <div class="client-info">
            <strong>Adding PS Case for:</strong> <?php echo htmlspecialchars($client_name); ?>
            <input type="hidden" name="client_id_hidden" value="<?php echo $client_id; ?>" id="client_id_hidden">
        </div>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="client_id" value="<?php echo $client_id; ?>" id="client_id">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Docket Number *</label>
                    <input type="text" name="docket_number" placeholder="e.g., PS-2024-001" required>
                </div>
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($client_name); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Offense *</label>
                <textarea name="offense" rows="3" required><?php echo htmlspecialchars($client_offense); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Address *</label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($client_address); ?>" required>
                </div>
                <div class="form-group">
                    <label>Supervising Officer *</label>
                    <input type="text" name="supervising_officer" required>
                </div>
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

            <div class="form-row">
                <div class="form-group">
                    <label>Initial Payment</label>
                    <input type="number" name="payment" step="0.01" value="0.00" min="0">
                </div>
                <div class="form-group">
                    <label>Monthly Fee</label>
                    <input type="number" name="monthly_fee" step="0.01" value="500.00" min="0">
                </div>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="Active">Active</option>
                    <option value="Terminated">Terminated</option>
                    <option value="Revoked">Revoked</option>
                    <option value="Completed">Completed</option>
                </select>
            </div>

            <button type="submit" name="add_ps" class="btn">
                <i class="fas fa-save"></i> Add PS Case
            </button>
        </form>
    </div>

    <script>
        // Ensure client_id is set
        document.getElementById('client_id').value = <?php echo $client_id; ?>;
    </script>
</body>
</html>