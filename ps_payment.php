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

// Check if ID is provided
if(!isset($_GET['id'])) {
    header("Location: ps_list.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Get PS case details
$case_query = mysqli_query($conn, "SELECT * FROM probation_supervision WHERE id='$id'");
if(mysqli_num_rows($case_query) == 0) {
    header("Location: ps_list.php");
    exit();
}
$case = mysqli_fetch_assoc($case_query);

// Get payment history
$payments = mysqli_query($conn, "SELECT * FROM probation_payments WHERE probation_id='$id' ORDER BY payment_date DESC");

// Calculate total paid
$total_paid = 0;
while($p = mysqli_fetch_assoc($payments)) {
    $total_paid += $p['amount'];
}
mysqli_data_seek($payments, 0); // Reset pointer

// Handle payment submission
if(isset($_POST['record_payment'])) {
    $amount = floatval($_POST['amount']);
    $payment_date = $_POST['payment_date'];
    $receipt_number = mysqli_real_escape_string($conn, $_POST['receipt_number']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    
    // Get current user ID
    $user_query = mysqli_query($conn, "SELECT id FROM staff WHERE username='{$_SESSION['username']}'");
    $user = mysqli_fetch_assoc($user_query);
    $collected_by = $user['id'];
    
    // Insert payment record
    $insert = "INSERT INTO probation_payments (probation_id, amount, payment_date, receipt_number, notes, collected_by) 
               VALUES ('$id', '$amount', '$payment_date', '$receipt_number', '$notes', '$collected_by')";
    
    if(mysqli_query($conn, $insert)) {
        // Update total payment and next payment date in PS table
        $new_total = $total_paid + $amount;
        $next_payment = date('Y-m-d', strtotime($payment_date . ' +1 month'));
        
        mysqli_query($conn, "UPDATE probation_supervision SET 
                            payment = '$new_total',
                            last_payment_date = '$payment_date',
                            next_payment_date = '$next_payment'
                            WHERE id='$id'");
        
        $success = "Payment recorded successfully!";
        
        // Refresh data
        $case_query = mysqli_query($conn, "SELECT * FROM probation_supervision WHERE id='$id'");
        $case = mysqli_fetch_assoc($case_query);
        $total_paid += $amount;
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
    <title>Record Payment - <?php echo $case['name']; ?></title>
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
        
        .client-info {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .client-name {
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .client-docket {
            color: #64748b;
        }
        
        .payment-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .summary-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.5rem;
        }
        
        .summary-label {
            color: #64748b;
            font-size: 0.85rem;
        }
        
        .summary-value {
            font-size: 1.5rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        .payment-form {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
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
        
        input, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
        }
        
        .btn {
            background: #0f172a;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .btn-success {
            background: #059669;
        }
        
        .table-container {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 1rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .message.success {
            background: #ecfdf3;
            color: #065f46;
            border: 1px solid #a7f3d0;
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
    </style>
</head>
<body>
    <div class="app">
        <div class="sidebar">
            <div class="logo">PPA System</div>
            <a href="dashboard.php" class="nav-item">Dashboard</a>
            <a href="clients.php" class="nav-item">Clients</a>
            <a href="pi_list.php" class="nav-item">PI Cases</a>
            <a href="ps_list.php" class="nav-item active">PS Cases</a>
        </div>

        <div class="main">
            <div class="top-bar">
                <h1 class="page-title">Record Payment</h1>
                <span><?php echo $fullname; ?></span>
            </div>

            <a href="ps_list.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to PS Cases
            </a>

            <!-- Client Info -->
            <div class="client-info">
                <div>
                    <div class="client-name"><?php echo $case['name']; ?></div>
                    <div class="client-docket">Docket: <?php echo $case['docket_number']; ?></div>
                </div>
                <div>
                    <span class="status-badge status-<?php echo $case['status']; ?>">
                        <?php echo $case['status']; ?>
                    </span>
                </div>
            </div>

            <!-- Payment Summary -->
            <div class="payment-summary">
                <div class="summary-card">
                    <div class="summary-label">Monthly Fee</div>
                    <div class="summary-value">₱<?php echo number_format($case['monthly_fee'], 2); ?></div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Total Paid</div>
                    <div class="summary-value">₱<?php echo number_format($total_paid, 2); ?></div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Next Payment Due</div>
                    <div class="summary-value"><?php echo $case['next_payment_date'] ? date('M d, Y', strtotime($case['next_payment_date'])) : 'N/A'; ?></div>
                </div>
            </div>

            <?php if(isset($success)): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Payment Form -->
            <div class="payment-form">
                <h3 style="margin-bottom: 1.5rem;">Record New Payment</h3>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Amount (₱) *</label>
                            <input type="number" name="amount" step="0.01" min="1" value="<?php echo $case['monthly_fee']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Payment Date *</label>
                            <input type="date" name="payment_date" value="<?php echo $today; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Receipt Number</label>
                            <input type="text" name="receipt_number" placeholder="e.g., RCP-2024-001">
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Notes</label>
                            <textarea name="notes" rows="2" placeholder="Additional payment notes"></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" name="record_payment" class="btn btn-success">
                        <i class="fas fa-check"></i> Record Payment
                    </button>
                </form>
            </div>

            <!-- Payment History -->
            <h3 style="margin-bottom: 1rem;">Payment History</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Receipt #</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($payments) > 0): ?>
                            <?php while($p = mysqli_fetch_assoc($payments)): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($p['payment_date'])); ?></td>
                                <td><strong>₱<?php echo number_format($p['amount'], 2); ?></strong></td>
                                <td><?php echo $p['receipt_number'] ?: 'N/A'; ?></td>
                                <td><?php echo $p['notes'] ?: '-'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 2rem; color: #64748b;">
                                    No payments recorded yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>