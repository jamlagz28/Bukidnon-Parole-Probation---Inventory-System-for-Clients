<?php
session_start();
include 'config/database.php';

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$client_id = $_GET['id'] ?? 0;
$user_role = $_SESSION['role'] ?? 'staff';
$can_edit = ($user_role == 'main' || $user_role == 'admin');
$fullname = $_SESSION['fullname'] ?? 'User';

// Get client details
$client_query = mysqli_query($conn, "SELECT * FROM clients WHERE id='$client_id'");
if(mysqli_num_rows($client_query) == 0) {
    header("Location: dashboard.php");
    exit();
}
$client = mysqli_fetch_assoc($client_query);

// FIXED: Get PI cases ONLY for THIS client using client_id
$pi_cases = mysqli_query($conn, "SELECT * FROM pre_investigation WHERE client_id='$client_id' ORDER BY created_at DESC");

// FIXED: Get PS cases ONLY for THIS client using client_id
$ps_cases = mysqli_query($conn, "SELECT * FROM probation_supervision WHERE client_id='$client_id' ORDER BY created_at DESC");

// Get monthly reports
$reports = mysqli_query($conn, "SELECT * FROM monthly_reports WHERE probationer_id='$client_id' ORDER BY report_year DESC, report_month DESC");

// Handle Add PS Case
if(isset($_POST['add_ps_quick']) && $can_edit) {
    $docket_number = mysqli_real_escape_string($conn, $_POST['docket_number']);
    $offense = mysqli_real_escape_string($conn, $_POST['offense']);
    $payment = floatval($_POST['payment']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $supervising_officer = mysqli_real_escape_string($conn, $_POST['supervising_officer']);
    $monthly_fee = floatval($_POST['monthly_fee']);
    
    $next_payment_date = date('Y-m-d', strtotime($start_date . ' +1 month'));
    
    $user_query = mysqli_query($conn, "SELECT id FROM staff WHERE username='{$_SESSION['username']}'");
    $user = mysqli_fetch_assoc($user_query);
    $created_by = $user['id'];
    
    mysqli_begin_transaction($conn);
    
    // Insert PS case - linked to THIS client_id
    $ps_query = "INSERT INTO probation_supervision (
        client_id, docket_number, name, offense, payment, address, 
        start_date, end_date, supervising_officer, status, monthly_fee, 
        next_payment_date, created_by
    ) VALUES (
        '$client_id', '$docket_number', '{$client['name']}', '$offense', '$payment', 
        '{$client['address']}', '$start_date', '$end_date', '$supervising_officer', 
        'Active', '$monthly_fee', '$next_payment_date', '$created_by'
    )";
    
    if(mysqli_query($conn, $ps_query)) {
        $ps_id = mysqli_insert_id($conn);
        
        // UPDATE CLIENT TO ACTIVE
        mysqli_query($conn, "UPDATE clients SET status = 'Active' WHERE id = '$client_id'");
        
        // Update any pending PI cases to Approved
        mysqli_query($conn, "UPDATE pre_investigation SET status = 'Approved' WHERE client_id = '$client_id' AND status = 'Pending'");
        
        mysqli_commit($conn);
        $success = "PS Case added successfully! Client is now ACTIVE.";
        
        // Refresh data
        $client_query = mysqli_query($conn, "SELECT * FROM clients WHERE id='$client_id'");
        $client = mysqli_fetch_assoc($client_query);
        $ps_cases = mysqli_query($conn, "SELECT * FROM probation_supervision WHERE client_id='$client_id' ORDER BY created_at DESC");
    } else {
        mysqli_rollback($conn);
        $error = "Error adding PS case: " . mysqli_error($conn);
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
    <title>Client Profile - <?php echo $client['name']; ?></title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Icons -->
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

        /* Print Styles */
        @media print {
            .sidebar, .top-bar, .action-buttons, .no-print {
                display: none !important;
            }
            .main {
                margin-left: 0 !important;
                padding: 20px !important;
            }
            .client-profile {
                border: none !important;
                box-shadow: none !important;
            }
        }

        .app {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: white;
            border-right: 1px solid #e2e8f0;
            padding: 2rem 1.5rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .logo {
            font-weight: 600;
            font-size: 1.25rem;
            color: #0f172a;
            margin-bottom: 2rem;
            letter-spacing: -0.01em;
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
            transition: all 0.2s;
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

        /* Main Content */
        .main {
            flex: 1;
            margin-left: 260px;
            padding: 2rem;
        }

        /* Top Bar */
        .top-bar {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
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

        .user-name {
            color: #475569;
            font-size: 0.95rem;
        }

        .avatar {
            width: 38px;
            height: 38px;
            background: #f1f5f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .logout-btn {
            color: #94a3b8;
            transition: color 0.2s;
        }

        .logout-btn:hover {
            color: #ef4444;
        }

        /* Back Link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            text-decoration: none;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .back-link:hover {
            color: #0f172a;
        }

        /* Messages */
        .message {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
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

        /* Client Profile Card */
        .client-profile {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }

        .profile-header {
            background: <?php echo ($client['status'] == 'Pending') ? 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)' : 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)'; ?>;
            padding: 2rem;
            color: white;
            position: relative;
        }

        .profile-header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 20px;
            background: linear-gradient(to bottom, rgba(15,23,42,0.1), transparent);
        }

        .profile-name {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .profile-docket {
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

        .profile-body {
            padding: 2rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
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

        .section-title i {
            color: #64748b;
        }

        .info-row {
            display: flex;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #f1f5f9;
        }

        .info-label {
            width: 100px;
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

        /* Cards Section */
        .cards-section {
            margin-top: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #0f172a;
        }

        .section-header h2 i {
            margin-right: 0.5rem;
            color: #64748b;
        }

        .view-all {
            color: #3b82f6;
            text-decoration: none;
            font-size: 0.9rem;
        }

        /* Table */
        .table-container {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
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

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-Active, .status-Approved {
            background: #ecfdf3;
            color: #059669;
        }

        .status-Terminated, .status-Rejected {
            background: #fef2f2;
            color: #dc2626;
        }

        .status-Revoked {
            background: #fffbeb;
            color: #d97706;
        }

        .status-Pending {
            background: #fffbeb;
            color: #d97706;
        }

        .status-Completed {
            background: #e0f2fe;
            color: #0284c7;
        }

        .action-link {
            color: #64748b;
            text-decoration: none;
            margin-right: 0.75rem;
            font-size: 0.9rem;
            transition: color 0.2s;
        }

        .action-link:hover {
            color: #0f172a;
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
        }

        .photo-thumb {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            transition: transform 0.2s;
        }

        .photo-thumb:hover {
            transform: scale(1.1);
        }

        .no-data {
            text-align: center;
            padding: 3rem;
            color: #64748b;
            background: #f8fafc;
            border-radius: 8px;
        }

        .no-data i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #94a3b8;
        }

        /* Action Buttons */
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

        .btn-secondary {
            background: white;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        .btn-disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
            opacity: 0.6;
            pointer-events: none;
        }

        /* Print Button */
        .print-btn {
            background: #64748b;
            color: white;
            border: none;
        }

        .print-btn:hover {
            background: #475569;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .info-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .main {
                margin-left: 0;
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
                <i class="fas fa-scale-balanced" style="margin-right: 8px;"></i>
                PPA System
            </div>
            
            <div style="margin-top: 2rem;">
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
                <a href="clients.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Clients</span>
                </a>
                <a href="pi_list.php" class="nav-item">
                    <i class="fas fa-file-lines"></i>
                    <span>PI Cases</span>
                </a>
                <a href="ps_list.php" class="nav-item">
                    <i class="fas fa-gavel"></i>
                    <span>PS Cases</span>
                </a>
                <a href="monthly_reports.php" class="nav-item">
                    <i class="fas fa-camera"></i>
                    <span>Monthly Reports</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1 class="page-title">Client Profile</h1>
                <div class="user-menu">
                    <span class="user-name"><?php echo htmlspecialchars($fullname); ?></span>
                    <div class="avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <button onclick="window.print()" class="btn print-btn" style="margin-right: 0.5rem; padding: 0.5rem 1rem;">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>

            <!-- Back Link -->
            <a href="javascript:history.back()" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Previous Page
            </a>

            <!-- Messages -->
            <?php if(isset($success)): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if(isset($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Client Profile Card -->
            <div class="client-profile">
                <div class="profile-header">
                    <div class="profile-name"><?php echo htmlspecialchars($client['name']); ?></div>
                    <div class="profile-docket">
                        <span>Docket #: <?php echo $client['docket_number']; ?></span>
                        <span class="status-badge"><?php echo $client['status']; ?></span>
                        <?php if(!$can_edit): ?>
                            <span class="view-only-badge" style="background: rgba(255,255,255,0.2); color: white;">
                                <i class="fas fa-eye"></i> View Only Mode
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="profile-body">
                    <div class="info-grid">
                        <!-- Personal Information -->
                        <div class="info-section">
                            <div class="section-title">
                                <i class="fas fa-user-circle"></i>
                                Personal Information
                            </div>
                            <div class="info-row">
                                <span class="info-label">CC Number:</span>
                                <span class="info-value"><?php echo $client['cc_number'] ?: 'N/A'; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Address:</span>
                                <span class="info-value"><?php echo $client['address']; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Court:</span>
                                <span class="info-value"><?php echo $client['court']; ?></span>
                            </div>
                        </div>

                        <!-- Case Information -->
                        <div class="info-section">
                            <div class="section-title">
                                <i class="fas fa-gavel"></i>
                                Case Information
                            </div>
                            <div class="info-row">
                                <span class="info-label">Offense:</span>
                                <span class="info-value"><?php echo $client['offense']; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Sentence:</span>
                                <span class="info-value"><?php echo $client['sentence']; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Date Added:</span>
                                <span class="info-value"><?php echo date('F d, Y', strtotime($client['created_at'])); ?></span>
                            </div>
                        </div>

                        <!-- Supervision Period -->
                        <div class="info-section">
                            <div class="section-title">
                                <i class="fas fa-clock"></i>
                                Supervision Period
                            </div>
                            <?php
                            // Get the latest PS case dates if they exist
                            $latest_ps = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM probation_supervision WHERE client_id='$client_id' ORDER BY created_at DESC LIMIT 1"));
                            ?>
                            <div class="info-row">
                                <span class="info-label">Start Date:</span>
                                <span class="info-value"><?php echo ($latest_ps) ? date('F d, Y', strtotime($latest_ps['start_date'])) : 'N/A'; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">End Date:</span>
                                <span class="info-value"><?php echo ($latest_ps) ? date('F d, Y', strtotime($latest_ps['end_date'])) : 'N/A'; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Status:</span>
                                <span class="info-value">
                                    <span class="status-badge status-<?php echo $client['status']; ?>">
                                        <?php echo $client['status']; ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PI Cases Section -->
            <div class="cards-section">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-file-lines" style="color: #f59e0b;"></i>
                        Pre-Investigation Cases
                    </h2>
                    <?php if($can_edit): ?>
                        <a href="pi_add.php?client_id=<?php echo $client_id; ?>" class="view-all">
                            <i class="fas fa-plus"></i> Add New PI Case
                        </a>
                    <?php endif; ?>
                </div>

                <?php if(mysqli_num_rows($pi_cases) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Docket #</th>
                                    <th>Offense</th>
                                    <th>Investigator</th>
                                    <th>Date Filed</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($pi = mysqli_fetch_assoc($pi_cases)): ?>
                                <tr>
                                    <td><strong><?php echo $pi['docket_number']; ?></strong></td>
                                    <td><?php echo $pi['offense']; ?></td>
                                    <td><?php echo $pi['investigator']; ?></td>
                                    <td><?php echo date('F d, Y', strtotime($pi['date_filed'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $pi['status']; ?>">
                                            <?php echo $pi['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="pi_view.php?id=<?php echo $pi['id']; ?>" class="action-link" title="View Details">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <?php if($can_edit && $pi['status'] == 'Pending'): ?>
                                        <a href="pi_edit.php?id=<?php echo $pi['id']; ?>" class="action-link" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-folder-open"></i>
                        <p>No pre-investigation cases found for this client.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- PS Cases Section - ONLY SHOWS CASES FOR THIS CLIENT -->
            <div class="cards-section">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-gavel" style="color: #10b981;"></i>
                        Probation Supervision Cases
                    </h2>
                    <?php if($can_edit): ?>
                        <a href="ps_add.php?client_id=<?php echo $client_id; ?>" class="view-all">
                            <i class="fas fa-plus"></i> Add New PS Case
                        </a>
                    <?php endif; ?>
                </div>

                <?php if(mysqli_num_rows($ps_cases) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Docket #</th>
                                    <th>Offense</th>
                                    <th>Payment</th>
                                    <th>Period</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($ps = mysqli_fetch_assoc($ps_cases)): ?>
                                <tr>
                                    <td><strong><?php echo $ps['docket_number']; ?></strong></td>
                                    <td><?php echo $ps['offense']; ?></td>
                                    <td>₱<?php echo number_format($ps['payment'], 2); ?></td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($ps['start_date'])); ?> - 
                                        <?php echo date('M d, Y', strtotime($ps['end_date'])); ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $ps['status']; ?>">
                                            <?php echo $ps['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="ps_view.php?id=<?php echo $ps['id']; ?>" class="action-link" title="View Details">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <?php if($can_edit): ?>
                                        <a href="ps_payment.php?id=<?php echo $ps['id']; ?>" class="action-link" title="Record Payment">
                                            <i class="fas fa-money-bill"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-folder-open"></i>
                        <p>No probation supervision cases found for this client.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Monthly Reports Section -->
            <div class="cards-section">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-camera" style="color: #3b82f6;"></i>
                        Monthly Reports
                    </h2>
                    <a href="dashboard.php" class="view-all">
                        <i class="fas fa-upload"></i> Upload New Report
                    </a>
                </div>

                <?php if(mysqli_num_rows($reports) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Reporting Period</th>
                                    <th>Photo</th>
                                    <th>Upload Date</th>
                                    <th>Uploaded By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($report = mysqli_fetch_assoc($reports)): ?>
                                <tr>
                                    <td><?php echo date("F Y", mktime(0,0,0,$report['report_month'],1,$report['report_year'])); ?></td>
                                    <td>
                                        <img src="uploads/<?php echo $report['photo']; ?>" class="photo-thumb" onclick="window.open('uploads/<?php echo $report['photo']; ?>')" title="Click to view full image">
                                    </td>
                                    <td><?php echo date("F d, Y h:i A", strtotime($report['upload_date'])); ?></td>
                                    <td>Staff #<?php echo $report['uploaded_by']; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-camera"></i>
                        <p>No monthly reports found for this client.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Back to Dashboard
                </a>
                
                <?php if($can_edit): ?>
                    <a href="edit_probationer.php?id=<?php echo $client['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Client Information
                    </a>
                <?php else: ?>
                    <span class="btn btn-disabled">
                        <i class="fas fa-lock"></i> Edit (View Only Mode)
                    </span>
                <?php endif; ?>

                <button onclick="window.print()" class="btn btn-secondary print-btn">
                    <i class="fas fa-print"></i> Print Profile
                </button>
            </div>
        </div>
    </div>
</body>
</html>