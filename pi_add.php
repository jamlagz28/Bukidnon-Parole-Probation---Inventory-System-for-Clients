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
    header("Location: pi_list.php?error=unauthorized");
    exit();
}

// Handle form submission
if(isset($_POST['add_pi'])) {
    $docket_number = mysqli_real_escape_string($conn, $_POST['docket_number']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $cc_number = mysqli_real_escape_string($conn, $_POST['cc_number']);
    $court = mysqli_real_escape_string($conn, $_POST['court']);
    $offense = mysqli_real_escape_string($conn, $_POST['offense']);
    $sentence = mysqli_real_escape_string($conn, $_POST['sentence']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $investigator = mysqli_real_escape_string($conn, $_POST['investigator']);
    $date_filed = $_POST['date_filed'];
    $status = $_POST['status'];
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    
    // Get current user ID
    $user_query = mysqli_query($conn, "SELECT id FROM staff WHERE username='{$_SESSION['username']}'");
    $user = mysqli_fetch_assoc($user_query);
    $created_by = $user['id'];
    
    $query = "INSERT INTO pre_investigation (docket_number, name, cc_number, court, offense, sentence, address, investigator, date_filed, status, remarks, created_by) 
              VALUES ('$docket_number', '$name', '$cc_number', '$court', '$offense', '$sentence', '$address', '$investigator', '$date_filed', '$status', '$remarks', '$created_by')";
    
    if(mysqli_query($conn, $query)) {
        header("Location: pi_list.php?msg=added");
        exit();
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

// Get current date for default value
$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add PI Case</title>
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
            <a href="pi_list.php" class="nav-item active"><i class="fas fa-file-lines"></i> PI Cases</a>
            <a href="ps_list.php" class="nav-item"><i class="fas fa-gavel"></i> PS Cases</a>
            <a href="monthly_reports.php" class="nav-item"><i class="fas fa-camera"></i> Reports</a>
        </div>

        <!-- Main Content -->
        <div class="main">
            <div class="top-bar">
                <h1 class="page-title">Add Pre-Investigation Case</h1>
                <span><?php echo $fullname; ?></span>
            </div>

            <a href="pi_list.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to PI Cases
            </a>

            <div class="form-container">
                <?php if(isset($error)): ?>
                    <div class="message error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Docket Number *</label>
                            <input type="text" name="docket_number" placeholder="e.g., PI-2024-001" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="name" placeholder="Enter full name" required>
                        </div>
                        
                        <div class="form-group">
                            <label>CC Number</label>
                            <input type="text" name="cc_number" placeholder="Enter CC number">
                        </div>
                        
                        <div class="form-group">
                            <label>Court *</label>
                            <input type="text" name="court" placeholder="e.g., Manolo Fortich Court" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Offense *</label>
                            <textarea name="offense" placeholder="Describe the offense" required></textarea>
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Sentence *</label>
                            <input type="text" name="sentence" placeholder="e.g., 6 months probation" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Address *</label>
                            <input type="text" name="address" placeholder="Complete address" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Investigator *</label>
                            <input type="text" name="investigator" placeholder="Name of investigator" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Date Filed *</label>
                            <input type="date" name="date_filed" value="<?php echo $today; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="Pending">Pending</option>
                                <option value="For Review">For Review</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Remarks</label>
                            <textarea name="remarks" placeholder="Additional notes or remarks"></textarea>
                        </div>
                    </div>
                    
                    <div style="margin-top: 2rem;">
                        <button type="submit" name="add_pi" class="btn">
                            <i class="fas fa-save"></i> Save PI Case
                        </button>
                        <a href="pi_list.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>