<?php
session_start();
include 'includes/config.php';
include 'includes/permissions.php';

// Check login
if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Get user info
$fullname = $_SESSION['fullname'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'staff';

// Check if user can add
$can_add = canAdd($user_role);

// Redirect if not authorized
if(!$can_add) {
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
    $created_by = $user['id'] ?? 0;
    
    $query = "INSERT INTO pre_investigation (docket_number, name, cc_number, court, offense, sentence, address, investigator, date_filed, status, remarks, created_by) 
              VALUES ('$docket_number', '$name', '$cc_number', '$court', '$offense', '$sentence', '$address', '$investigator', '$date_filed', '$status', '$remarks', '$created_by')";
    
    if(mysqli_query($conn, $query)) {
        header("Location: pi_list.php?msg=added");
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
    <title>Add PI Case</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: #f8fafc; 
            padding: 2rem;
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 12px; 
            border: 1px solid #e2e8f0; 
            padding: 2rem; 
        }
        h1 { 
            margin-bottom: 2rem; 
            color: #0f172a;
        }
        .form-group { 
            margin-bottom: 1.5rem; 
        }
        label { 
            display: block; 
            margin-bottom: 0.5rem; 
            font-weight: 500; 
            color: #475569; 
        }
        input, select, textarea { 
            width: 100%; 
            padding: 0.75rem; 
            border: 1px solid #e2e8f0; 
            border-radius: 8px; 
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
        }
        .btn { 
            background: #0f172a; 
            color: white; 
            padding: 0.75rem 1.5rem; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
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
        .error { 
            background: #fef2f2; 
            color: #991b1b; 
            padding: 1rem; 
            border-radius: 8px; 
            margin-bottom: 1.5rem; 
        }
        .back-link { 
            display: inline-block; 
            margin-bottom: 1.5rem; 
            color: #64748b; 
            text-decoration: none; 
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="pi_list.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to PI Cases
        </a>
        
        <h1>Add New PI Case</h1>

        <?php if(isset($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Docket Number *</label>
                    <input type="text" name="docket_number" placeholder="e.g., PI-2024-001" required>
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
                    <label>Investigator *</label>
                    <input type="text" name="investigator" required>
                </div>
                
                <div class="form-group">
                    <label>Date Filed *</label>
                    <input type="date" name="date_filed" value="<?php echo $today; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="Pending">Pending</option>
                        <option value="For Review">For Review</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Remarks</label>
                    <input type="text" name="remarks">
                </div>
            </div>
            
            <div style="margin-top: 2rem;">
                <button type="submit" name="add_pi" class="btn">
                    <i class="fas fa-save"></i> Add PI Case
                </button>
                <a href="pi_list.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</body>
</html>