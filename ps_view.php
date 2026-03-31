<?php
session_start();
include 'includes/config.php';

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? 0;
$user_role = $_SESSION['role'] ?? 'staff';
$can_edit = ($user_role == 'main' || $user_role == 'admin');
$fullname = $_SESSION['fullname'] ?? 'User';

// Get PS case details
$case = mysqli_query($conn, "SELECT * FROM probation_supervision WHERE id='$id'");
if(mysqli_num_rows($case) == 0) {
    header("Location: ps_list.php");
    exit();
}
$row = mysqli_fetch_assoc($case);

// Get payment history
$payments = mysqli_query($conn, "SELECT * FROM probation_payments WHERE probation_id='$id' ORDER BY payment_date DESC");

// Calculate total paid
$total_paid = 0;
while($p = mysqli_fetch_assoc($payments)) {
    $total_paid += $p['amount'];
}
mysqli_data_seek($payments, 0); // Reset pointer

// Calculate payment status
function getPaymentStatus($last_payment, $next_payment) {
    if(!$next_payment) return 'No Payment';
    $today = date('Y-m-d');
    if($today > $next_payment) return 'Overdue';
    return 'Current';
}
$payment_status = getPaymentStatus($row['last_payment_date'], $row['next_payment_date']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PS Case Details - <?php echo $row['name']; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            color: #1e293b;
            line-height: 1.6;
        }

        @media print {
            .sidebar, .top-bar, .action-buttons, .no-print {
                display: none !important;
            }
            .main {
                margin-left: 0 !important;
                padding: 20px !important;
            }
            .case-card {
                box-shadow: none !important;
                border: 1px solid #000 !important;
            }
        }

        .app {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: white;
            border-right: 1px solid #e2e8f0;
            padding: 2rem 1.5rem;
            position: fixed;
            height: 100vh;
        }

        .logo {
            font-weight: 600;
            font-size: 1.25rem;
            color: #0f172a;
            margin-bottom: 2rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #64748b;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.25rem;
        }

        .nav-item:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .nav-item.active {
            background: #f1f5f9;
            color: #0f172a;
            font-weight: 500;
        }

        .main {
            flex: 1;
            margin-left: 260px;
            padding: 2rem;
        }

        .top-bar {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #0f172a;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            text-decoration: none;
            margin-bottom: 1.5rem;
        }

        .back-link:hover {
            color: #0f172a;
        }

        .case-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            margin-bottom: 2rem;
        }

        .case-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 2rem;
            color: white;
        }

        .case-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .case-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.35rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .case-body {
            padding: 2rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #64748b;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #0f172a;
        }

        .stat-value.success {
            color: #059669;
        }

        .stat-value.warning {
            color: #d97706;
        }

        .stat-value.danger {
            color: #dc2626;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .info-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 1.25rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-row {
            display: flex;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #f1f5f9;
        }

        .info-label {
            width: 120px;
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 500;
        }

        .info-value {
            flex: 1;
            font-size: 0.95rem;
            color: #0f172a;
            font-weight: 500;
        }

        .payment-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .payment-current {
            background: #ecfdf3;
            color: #059669;
        }

        .payment-overdue {
            background: #fef2f2;
            color: #dc2626;
        }

        .payment-pending {
            background: #fffbeb;
            color: #d97706;
        }

        .view-only-badge {
            background: #e2e8f0;
            color: #475569;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-left: 1rem;
        }

        /* Table */
        .table-container {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            margin-top: 1rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 1rem 1.5rem;
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #e2e8f0;
        }

        td {
            padding: 1rem 1.5rem;
            color: #1e293b;
            font-size: 0.95rem;
            border-bottom: 1px solid #f1f5f9;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #f8fafc;
        }

        .amount {
            font-weight: 600;
            color: #059669;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #0f172a;
            color: white;
        }

        .btn-primary:hover {
            background: #1e293b;
        }

        .btn-success {
            background: #059669;
            color: white;
        }

        .btn-success:hover {
            background: #047857;
        }

        .btn-secondary {
            background: white;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #f8fafc;
        }

        .btn-disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
            opacity: 0.6;
            pointer-events: none;
        }

        .no-data {
            text-align: center;
            padding: 2rem;
            color: #64748b;
            background: #f8fafc;
            border-radius: 8px;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .main {
                margin-left: 0;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="app">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-scale-balanced"></i> PPA System
            </div>
            <a href="dashboard.php" class="nav-item">Dashboard</a>
            <a href="clients.php" class="nav-item">Clients</a>
            <a href="pi_list.php" class="nav-item">PI Cases</a>
            <a href="ps_list.php" class="nav-item active">PS Cases</a>
            <a href="monthly_reports.php" class="nav-item">Monthly Reports</a>
        </div>

        <!-- Main Content -->
        <div class="main">
            <div class="top-bar">
                <h1 class="page-title">Probation Supervision Details</h1>
                <div class="user-menu">
                    <span><?php echo $fullname; ?></span>
                    <?php if(!$can_edit): ?>
                        <span class="view-only-badge">
                            <i class="fas fa-eye"></i> View Only
                        </span>
                    <?php endif; ?>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>

            <a href="ps_list.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to PS Cases
            </a>

            <!-- Case Details Card -->
            <div class="case-card">
                <div class="case-header">
                    <div class="case-title">Case #: <?php echo $row['docket_number']; ?></div>
                    <div class="case-subtitle">
                        <span><?php echo $row['name']; ?></span>
                        <span class="status-badge"><?php echo $row['status']; ?></span>
                    </div>
                </div>

                <div class="case-body">
                    <!-- Stats Summary -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-label">Monthly Fee</div>
                            <div class="stat-value">₱<?php echo number_format($row['monthly_fee'] ?? 500, 2); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Total Paid</div>
                            <div class="stat-value success">₱<?php echo number_format($total_paid, 2); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Payment Status</div>
                            <div class="stat-value <?php 
                                echo $payment_status == 'Overdue' ? 'danger' : 
                                    ($payment_status == 'Current' ? 'success' : 'warning'); 
                            ?>">
                                <?php echo $payment_status; ?>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Next Payment</div>
                            <div class="stat-value">
                                <?php echo $row['next_payment_date'] ? date('M d, Y', strtotime($row['next_payment_date'])) : 'N/A'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="info-grid">
                        <!-- Personal Information -->
                        <div class="info-section">
                            <div class="section-title">
                                <i class="fas fa-user-circle"></i>
                                Client Information
                            </div>
                            <div class="info-row">
                                <span class="info-label">Full Name:</span>
                                <span class="info-value"><?php echo $row['name']; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Address:</span>
                                <span class="info-value"><?php echo $row['address']; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Offense:</span>
                                <span class="info-value"><?php echo $row['offense']; ?></span>
                            </div>
                        </div>

                        <!-- Supervision Details -->
                        <div class="info-section">
                            <div class="section-title">
                                <i class="fas fa-clock"></i>
                                Supervision Period
                            </div>
                            <div class="info-row">
                                <span class="info-label">Start Date:</span>
                                <span class="info-value"><?php echo date('F d, Y', strtotime($row['start_date'])); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">End Date:</span>
                                <span class="info-value"><?php echo date('F d, Y', strtotime($row['end_date'])); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Duration:</span>
                                <span class="info-value">
                                    <?php 
                                    $start = new DateTime($row['start_date']);
                                    $end = new DateTime($row['end_date']);
                                    $interval = $start->diff($end);
                                    echo $interval->format('%y years, %m months, %d days');
                                    ?>
                                </span>
                            </div>
                        </div>

                        <!-- Officer Information -->
                        <div class="info-section">
                            <div class="section-title">
                                <i class="fas fa-user-tie"></i>
                                Supervision Officer
                            </div>
                            <div class="info-row">
                                <span class="info-label">Officer:</span>
                                <span class="info-value"><?php echo $row['supervising_officer'] ?? 'Not Assigned'; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Last Payment:</span>
                                <span class="info-value"><?php echo $row['last_payment_date'] ? date('F d, Y', strtotime($row['last_payment_date'])) : 'No payments yet'; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Next Due:</span>
                                <span class="info-value">
                                    <?php echo $row['next_payment_date'] ? date('F d, Y', strtotime($row['next_payment_date'])) : 'N/A'; ?>
                                    <?php if($payment_status == 'Overdue'): ?>
                                        <span class="payment-status payment-overdue" style="margin-left: 0.5rem;">Overdue</span>
                                    <?php elseif($payment_status == 'Current'): ?>
                                        <span class="payment-status payment-current" style="margin-left: 0.5rem;">Current</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>

                        <!-- Payment Summary -->
                        <div class="info-section">
                            <div class="section-title">
                                <i class="fas fa-coins"></i>
                                Payment Summary
                            </div>
                            <div class="info-row">
                                <span class="info-label">Monthly Fee:</span>
                                <span class="info-value">₱<?php echo number_format($row['monthly_fee'] ?? 500, 2); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Total Paid:</span>
                                <span class="info-value success">₱<?php echo number_format($total_paid, 2); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Remaining:</span>
                                <span class="info-value">
                                    <?php 
                                    $months_elapsed = ceil((time() - strtotime($row['start_date'])) / (30 * 24 * 60 * 60));
                                    $expected = $months_elapsed * ($row['monthly_fee'] ?? 500);
                                    $remaining = max(0, $expected - $total_paid);
                                    echo '₱' . number_format($remaining, 2);
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment History -->
                    <div style="margin-top: 2rem;">
                        <h3 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-history" style="color: #64748b;"></i>
                            Payment History
                        </h3>

                        <?php if(mysqli_num_rows($payments) > 0): ?>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Receipt #</th>
                                            <th>Amount</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($p = mysqli_fetch_assoc($payments)): ?>
                                        <tr>
                                            <td><?php echo date('F d, Y', strtotime($p['payment_date'])); ?></td>
                                            <td><?php echo $p['receipt_number'] ?: 'N/A'; ?></td>
                                            <td class="amount">₱<?php echo number_format($p['amount'], 2); ?></td>
                                            <td><?php echo $p['notes'] ?: '-'; ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-receipt" style="font-size: 2rem; margin-bottom: 1rem; color: #94a3b8;"></i>
                                <p>No payment records found for this case.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="ps_list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                        
                        <?php if($can_edit): ?>
                            <a href="ps_edit.php?id=<?php echo $id; ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Edit Case
                            </a>
                            <a href="ps_payment.php?id=<?php echo $id; ?>" class="btn btn-success">
                                <i class="fas fa-money-bill"></i> Record Payment
                            </a>
                        <?php else: ?>
                            <span class="btn btn-disabled">
                                <i class="fas fa-lock"></i> Edit (View Only)
                            </span>
                            <span class="btn btn-disabled">
                                <i class="fas fa-lock"></i> Record Payment (View Only)
                            </span>
                        <?php endif; ?>

                        <button onclick="window.print()" class="btn btn-secondary">
                            <i class="fas fa-print"></i> Print Case
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>