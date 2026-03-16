<?php
session_start();
include 'config/database.php';

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

// Handle form submission
if(isset($_POST['add_ps'])) {
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
    
    // Calculate next payment date (1 month from start date)
    $next_payment_date = date('Y-m-d', strtotime($start_date . ' +1 month'));
    
    // Get current user ID
    $user_query = mysqli_query($conn, "SELECT id FROM staff WHERE username='{$_SESSION['username']}'");
    $user = mysqli_fetch_assoc($user_query);
    $created_by = $user['id'];
    
    $query = "INSERT INTO probation_supervision (docket_number, name, offense, payment, address, start_date, end_date, supervising_officer, status, monthly_fee, next_payment_date, created_by) 
              VALUES ('$docket_number', '$name', '$offense', '$payment', '$address', '$start_date', '$end_date', '$supervising_officer', '$status', '$monthly_fee', '$next_payment_date', '$created_by')";
    
    if(mysqli_query($conn, $query)) {
        header("Location: ps_list.php?msg=added");
        exit();
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

$today = date('Y-m-d');
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
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .app { display: flex; }
        .sidebar { width: 260px; background: white; border-right: 1px solid #e2e8f0; padding: 2rem; position: fixed; height: 100vh; }
        .main { flex: 1; margin-left: 260px; padding: 2rem; }
        .top-bar { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; }
        .page-title { font-size: 1.25rem; font-weight: 500; }
        .nav-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; color: #64748b; text-decoration: none; border-radius: 8px; }
        .nav-item:hover { background: #f1f5f9; }
        .nav-item.active { background: #f1f5f9; color: #0f172a; font-weight: 500; }
        
        .form-container {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #475569;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .btn {
            background: #0f172a;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn:hover {
            background: #1e293b;
        }
        
        .btn-secondary {
            background: white;
            color: #475569;
            border: 1px solid #e2e8f0;
            margin-left: 1rem;
        }
        
        .btn-secondary:hover {
            background: #f8fafc;
        }
        
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .message.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: #64748b;
            text-decoration: none;
        }
        
        .back-link:hover {
            color: #0f172a;
        }
        
        .info-text {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 0.25rem;
        }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main { margin-left: 0; }
            .form-grid { grid-template-columns: 1fr; }
            .form-group.full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>
    <div class="app">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo" style="font-weight:600; margin-bottom:2rem;">PPA System</div>
            <a href="dashboard.php" class="nav-item"><i class="fas fa-chart-pie"></i> Dashboard</a>
            <a href="clients.php" class="nav-item"><i class="fas fa-users"></i> Clients</a>
            <a href="pi_list.php" class="nav-item"><i class="fas fa-file-lines"></i> PI Cases</a>
            <a href="ps_list.php" class="nav-item active"><i class="fas fa-gavel"></i> PS Cases</a>
            <a href="monthly_reports.php" class="nav-item"><i class="fas fa-camera"></i> Reports</a>
        </div>

        <!-- Main Content -->
        <div class="main">
            <div class="top-bar">
                <h1 class="page-title">Add Probation Supervision Case</h1>
                <span><?php echo $fullname; ?></span>
            </div>

            <a href="ps_list.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to PS Cases
            </a>

            <div class="form-container">
                <?php if(isset($error)): ?>
                    <div class="message error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Docket Number *</label>
                            <input type="text" name="docket_number" placeholder="e.g., PS-2024-001" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="name" placeholder="Enter full name" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Offense *</label>
                            <textarea name="offense" placeholder="Describe the offense" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Initial Payment</label>
                            <input type="number" name="payment" step="0.01" value="0.00" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label>Monthly Fee</label>
                            <input type="number" name="monthly_fee" step="0.01" value="500.00" min="0">
                            <div class="info-text">Standard monthly probation fee</div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Address *</label>
                            <input type="text" name="address" placeholder="Complete address" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Start Date *</label>
                            <input type="date" name="start_date" value="<?php echo $today; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>End Date *</label>
                            <input type="date" name="end_date" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Supervising Officer *</label>
                            <input type="text" name="supervising_officer" placeholder="Name of officer" required>
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
                    </div>
                    
                    <div style="margin-top: 2rem;">
                        <button type="submit" name="add_ps" class="btn">
                            <i class="fas fa-save"></i> Save PS Case
                        </button>
                        <a href="ps_list.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>