<?php
session_start();
include 'includes/config.php';
include 'includes/permissions.php'; // Make sure this path is correct

// Check login
if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Get user info
$fullname = $_SESSION['fullname'] ?? 'User';
$username = $_SESSION['username'] ?? '';
$user_role = $_SESSION['role'] ?? 'staff';

// Check if user can edit
$can_edit = canEdit($user_role);

if(!$can_edit) {
    header("Location: pi_list.php?error=unauthorized");
    exit();
}

// Check if ID is provided
if(!isset($_GET['id'])) {
    header("Location: pi_list.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);
$result = mysqli_query($conn, "SELECT * FROM pre_investigation WHERE id='$id'");

if(mysqli_num_rows($result) == 0) {
    header("Location: pi_list.php");
    exit();
}

$row = mysqli_fetch_assoc($result);

// Handle update
if(isset($_POST['update_pi'])) {
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
    
    $query = "UPDATE pre_investigation SET 
              docket_number='$docket_number',
              name='$name',
              cc_number='$cc_number',
              court='$court',
              offense='$offense',
              sentence='$sentence',
              address='$address',
              investigator='$investigator',
              date_filed='$date_filed',
              status='$status',
              remarks='$remarks'
              WHERE id='$id'";
    
    if(mysqli_query($conn, $query)) {
        header("Location: pi_list.php?msg=updated");
        exit();
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit PI Case</title>
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
            font-size: 1.5rem;
        }
        .form-group { 
            margin-bottom: 1.5rem; 
        }
        label { 
            display: block; 
            margin-bottom: 0.5rem; 
            font-weight: 500; 
            color: #475569; 
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
        .btn { 
            background: #0f172a; 
            color: white; 
            padding: 0.75rem 1.5rem; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 0.95rem;
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
        .error { 
            background: #fef2f2; 
            color: #991b1b; 
            padding: 1rem; 
            border-radius: 8px; 
            margin-bottom: 1.5rem; 
            border: 1px solid #fecaca;
        }
        .back-link { 
            display: inline-block; 
            margin-bottom: 1.5rem; 
            color: #64748b; 
            text-decoration: none; 
        }
        .back-link:hover {
            color: #0f172a;
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
        
        <h1>Edit PI Case</h1>

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
                    <input type="text" name="docket_number" value="<?php echo htmlspecialchars($row['docket_number']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>CC Number</label>
                    <input type="text" name="cc_number" value="<?php echo htmlspecialchars($row['cc_number']); ?>">
                </div>
                
                <div class="form-group">
                    <label>Court *</label>
                    <input type="text" name="court" value="<?php echo htmlspecialchars($row['court']); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Offense *</label>
                <textarea name="offense" rows="3" required><?php echo htmlspecialchars($row['offense']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Sentence *</label>
                <input type="text" name="sentence" value="<?php echo htmlspecialchars($row['sentence']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Address *</label>
                <input type="text" name="address" value="<?php echo htmlspecialchars($row['address']); ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Investigator *</label>
                    <input type="text" name="investigator" value="<?php echo htmlspecialchars($row['investigator']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Date Filed *</label>
                    <input type="date" name="date_filed" value="<?php echo $row['date_filed']; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="Pending" <?php echo $row['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="For Review" <?php echo $row['status'] == 'For Review' ? 'selected' : ''; ?>>For Review</option>
                        <option value="Approved" <?php echo $row['status'] == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="Rejected" <?php echo $row['status'] == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Remarks</label>
                    <input type="text" name="remarks" value="<?php echo htmlspecialchars($row['remarks']); ?>">
                </div>
            </div>
            
            <div style="margin-top: 2rem;">
                <button type="submit" name="update_pi" class="btn">
                    <i class="fas fa-save"></i> Update PI Case
                </button>
                <a href="pi_list.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</body>
</html>