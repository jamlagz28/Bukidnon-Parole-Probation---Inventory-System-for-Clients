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

// Get client details
$client_query = mysqli_query($conn, "SELECT * FROM clients WHERE id='$client_id'");
if(mysqli_num_rows($client_query) == 0) {
    header("Location: dashboard.php");
    exit();
}
$client = mysqli_fetch_assoc($client_query);

// Get PI cases for this client (search by name)
$pi_cases = mysqli_query($conn, "SELECT * FROM pre_investigation WHERE name LIKE '%{$client['name']}%' ORDER BY created_at DESC");

// Get PS cases for this client (search by name)
$ps_cases = mysqli_query($conn, "SELECT * FROM probation_supervision WHERE name LIKE '%{$client['name']}%' ORDER BY created_at DESC");

// Get monthly reports
$reports = mysqli_query($conn, "SELECT * FROM monthly_reports WHERE probationer_id='$client_id' ORDER BY report_year DESC, report_month DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Details - <?php echo $client['name']; ?></title>
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
            background: #f8fafc;
            color: #1e293b;
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
            font-size: 1.25rem;
            font-weight: 500;
            color: #0f172a;
        }

        .client-header {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .client-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .client-docket {
            color: #64748b;
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }

        .info-item {
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
        }

        .info-label {
            color: #64748b;
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-weight: 500;
            color: #0f172a;
        }

        .section {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title h2 {
            font-size: 1.1rem;
            font-weight: 500;
            color: #0f172a;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-pi {
            background: #fed7aa;
            color: #92400e;
        }

        .badge-ps {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-report {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .status-Active {
            background: #ecfdf3;
            color: #059669;
        }

        .status-Terminated {
            background: #e0f2fe;
            color: #0284c7;
        }

        .status-Revoked {
            background: #fffbeb;
            color: #d97706;
        }

        .status-Denied {
            background: #fef2f2;
            color: #dc2626;
        }

        .table-container {
            overflow-x: auto;
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
            font-weight: 500;
            color: #475569;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .action-link {
            color: #64748b;
            text-decoration: none;
            margin-right: 0.5rem;
        }

        .action-link:hover {
            color: #0f172a;
        }

        .action-link.disabled {
            color: #cbd5e1;
            pointer-events: none;
        }

        .view-only-badge {
            background: #e2e8f0;
            color: #475569;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
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

        .no-data {
            text-align: center;
            padding: 2rem;
            color: #64748b;
        }

        .photo-thumb {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 6px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="app">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">PPA System</div>
            <a href="dashboard.php" class="nav-item">Dashboard</a>
            <a href="clients.php" class="nav-item active">Clients</a>
            <a href="pi_list.php" class="nav-item">PI Cases</a>
            <a href="ps_list.php" class="nav-item">PS Cases</a>
        </div>

        <!-- Main Content -->
        <div class="main">
            <div class="top-bar">
                <h1 class="page-title">Client Details</h1>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <?php if(!$can_edit): ?>
                        <span class="view-only-badge">
                            <i class="fas fa-eye"></i> View Only Mode
                        </span>
                    <?php endif; ?>
                    <span><?php echo $_SESSION['fullname']; ?></span>
                </div>
            </div>

            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>

            <!-- Client Information -->
            <div class="client-header">
                <div class="client-name"><?php echo $client['name']; ?></div>
                <div class="client-docket">Docket #: <?php echo $client['docket_number']; ?></div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">CC Number</div>
                        <div class="info-value"><?php echo $client['cc_number'] ?: 'N/A'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Court</div>
                        <div class="info-value"><?php echo $client['court']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="status-badge status-<?php echo $client['status']; ?>">
                                <?php echo $client['status']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Offense</div>
                        <div class="info-value"><?php echo $client['offense']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Sentence</div>
                        <div class="info-value"><?php echo $client['sentence']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Address</div>
                        <div class="info-value"><?php echo $client['address']; ?></div>
                    </div>
                </div>
            </div>

            <!-- PI Cases Section -->
            <div class="section">
                <div class="section-title">
                    <h2>
                        <i class="fas fa-file-lines" style="color: #f59e0b; margin-right: 8px;"></i>
                        Pre-Investigation Cases
                    </h2>
                    <?php if($can_edit): ?>
                        <a href="pi_add.php?client_name=<?php echo urlencode($client['name']); ?>" class="action-link">
                            <i class="fas fa-plus"></i> Add PI Case
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
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($pi = mysqli_fetch_assoc($pi_cases)): ?>
                                <tr>
                                    <td><?php echo $pi['docket_number']; ?></td>
                                    <td><?php echo substr($pi['offense'], 0, 30); ?>...</td>
                                    <td><?php echo $pi['investigator']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($pi['date_filed'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $pi['status']; ?>">
                                            <?php echo $pi['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="pi_view.php?id=<?php echo $pi['id']; ?>" class="action-link">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if($can_edit): ?>
                                        <a href="pi_edit.php?id=<?php echo $pi['id']; ?>" class="action-link">
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
                    <div class="no-data">No PI cases found for this client.</div>
                <?php endif; ?>
            </div>

            <!-- PS Cases Section -->
            <div class="section">
                <div class="section-title">
                    <h2>
                        <i class="fas fa-gavel" style="color: #10b981; margin-right: 8px;"></i>
                        Probation Supervision Cases
                    </h2>
                    <?php if($can_edit): ?>
                        <a href="ps_add.php?client_name=<?php echo urlencode($client['name']); ?>" class="action-link">
                            <i class="fas fa-plus"></i> Add PS Case
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
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($ps = mysqli_fetch_assoc($ps_cases)): ?>
                                <tr>
                                    <td><?php echo $ps['docket_number']; ?></td>
                                    <td><?php echo substr($ps['offense'], 0, 30); ?>...</td>
                                    <td>₱<?php echo number_format($ps['payment'], 2); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($ps['start_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($ps['end_date'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $ps['status']; ?>">
                                            <?php echo $ps['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="ps_view.php?id=<?php echo $ps['id']; ?>" class="action-link">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if($can_edit): ?>
                                        <a href="ps_edit.php?id=<?php echo $ps['id']; ?>" class="action-link">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="ps_payment.php?id=<?php echo $ps['id']; ?>" class="action-link">
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
                    <div class="no-data">No PS cases found for this client.</div>
                <?php endif; ?>
            </div>

            <!-- Monthly Reports Section -->
            <div class="section">
                <div class="section-title">
                    <h2>
                        <i class="fas fa-camera" style="color: #3b82f6; margin-right: 8px;"></i>
                        Monthly Reports
                    </h2>
                    <?php if($can_edit): ?>
                        <a href="dashboard.php" class="action-link">
                            <i class="fas fa-upload"></i> Upload Report
                        </a>
                    <?php endif; ?>
                </div>

                <?php if(mysqli_num_rows($reports) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Month/Year</th>
                                    <th>Photo</th>
                                    <th>Upload Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($report = mysqli_fetch_assoc($reports)): ?>
                                <tr>
                                    <td><?php echo date("F Y", mktime(0,0,0,$report['report_month'],1,$report['report_year'])); ?></td>
                                    <td>
                                        <img src="uploads/<?php echo $report['photo']; ?>" class="photo-thumb" onclick="window.open('uploads/<?php echo $report['photo']; ?>')">
                                    </td>
                                    <td><?php echo date("M d, Y", strtotime($report['upload_date'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">No monthly reports found for this client.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>