<?php
session_start();
include 'config/database.php';

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'staff';
$can_edit = ($user_role == 'main' || $user_role == 'admin');

// Get all PS cases
$ps_cases = mysqli_query($conn, "SELECT * FROM probation_supervision ORDER BY created_at DESC");

// Calculate payment status
function getPaymentStatus($last_payment, $next_payment) {
    if(!$next_payment) return 'No Payment';
    $today = date('Y-m-d');
    if($today > $next_payment) return 'Overdue';
    return 'Current';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Probation Supervision Cases</title>
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
        .add-btn { background: #0f172a; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; display: inline-block; margin-bottom: 1rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; }
        .stat-label { color: #64748b; font-size: 0.85rem; }
        .stat-value { font-size: 1.5rem; font-weight: 600; margin-top: 0.25rem; }
        .table-container { background: white; border: 1px solid #e2e8f0; border-radius: 12px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1200px; }
        th { text-align: left; padding: 1rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        td { padding: 1rem; border-bottom: 1px solid #f1f5f9; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem; }
        .status-Active { background: #ecfdf3; color: #059669; }
        .status-Terminated { background: #fef2f2; color: #dc2626; }
        .status-Revoked { background: #fffbeb; color: #d97706; }
        .status-Completed { background: #e0f2fe; color: #0284c7; }
        .payment-current { color: #059669; }
        .payment-overdue { color: #dc2626; font-weight: 600; }
        .action-link { color: #64748b; text-decoration: none; margin: 0 0.5rem; }
        .action-link:hover { color: #0f172a; }
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
            <a href="monthly_reports.php" class="nav-item">Reports</a>
        </div>

        <div class="main">
            <div class="top-bar">
                <h1 class="page-title">Probation Supervision</h1>
                <span><?php echo $fullname; ?></span>
            </div>

            <?php
            // Get stats
            $active_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM probation_supervision WHERE status='Active'"))['total'];
            $total_payments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM probation_payments"))['total'] ?? 0;
            $overdue_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM probation_supervision WHERE next_payment_date < CURDATE() AND status='Active'"))['total'];
            ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Active Cases</div>
                    <div class="stat-value"><?php echo $active_count; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Payments</div>
                    <div class="stat-value">₱<?php echo number_format($total_payments, 2); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Overdue Payments</div>
                    <div class="stat-value" style="color: #dc2626;"><?php echo $overdue_count; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Monthly Fee</div>
                    <div class="stat-value">₱500.00</div>
                </div>
            </div>

            <?php if($can_edit): ?>
            <a href="ps_add.php" class="add-btn">
                <i class="fas fa-plus"></i> Add New PS Case
            </a>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Docket #</th>
                            <th>Name</th>
                            <th>Offense</th>
                            <th>Payment</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Payment Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($ps_cases)): 
                            $payment_status = getPaymentStatus($row['last_payment_date'], $row['next_payment_date']);
                        ?>
                        <tr>
                            <td><?php echo $row['docket_number']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo substr($row['offense'], 0, 30); ?>...</td>
                            <td>₱<?php echo number_format($row['payment'], 2); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['start_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['end_date'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $row['status']; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td class="<?php echo $payment_status == 'Overdue' ? 'payment-overdue' : 'payment-current'; ?>">
                                <?php echo $payment_status; ?>
                                <?php if($row['next_payment_date']): ?>
                                    <br><small><?php echo date('M d', strtotime($row['next_payment_date'])); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="ps_view.php?id=<?php echo $row['id']; ?>" class="action-link"><i class="fas fa-eye"></i></a>
                                <?php if($can_edit): ?>
                                <a href="ps_edit.php?id=<?php echo $row['id']; ?>" class="action-link"><i class="fas fa-edit"></i></a>
                                <a href="ps_payment.php?id=<?php echo $row['id']; ?>" class="action-link"><i class="fas fa-money-bill"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>