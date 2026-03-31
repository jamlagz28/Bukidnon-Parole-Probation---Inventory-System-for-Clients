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

// Get PI case details
$case = mysqli_query($conn, "SELECT * FROM pre_investigation WHERE id='$id'");
if(mysqli_num_rows($case) == 0) {
    header("Location: pi_list.php");
    exit();
}
$row = mysqli_fetch_assoc($case);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PI Case Details - <?php echo $row['name']; ?></title>
    
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
        }

        .case-header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
        }

        .case-body {
            padding: 2rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
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

        .status-badge {
            display: inline-block;
            padding: 0.35rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-Pending {
            background: #fffbeb;
            color: #d97706;
        }

        .status-Approved {
            background: #ecfdf3;
            color: #059669;
        }

        .status-Rejected {
            background: #fef2f2;
            color: #dc2626;
        }

        .status-For Review {
            background: #e0f2fe;
            color: #0284c7;
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
        }

        .btn-disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
            opacity: 0.6;
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
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-scale-balanced"></i> PPA System
            </div>
            <a href="dashboard.php" class="nav-item">Dashboard</a>
            <a href="clients.php" class="nav-item">Clients</a>
            <a href="pi_list.php" class="nav-item active">PI Cases</a>
            <a href="ps_list.php" class="nav-item">PS Cases</a>
        </div>

        <div class="main">
            <div class="top-bar">
                <h1 class="page-title">Pre-Investigation Case Details</h1>
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

            <a href="pi_list.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to PI Cases
            </a>

            <div class="case-card">
                <div class="case-header">
                    <div class="case-title">Case #: <?php echo $row['docket_number']; ?></div>
                    <div class="case-subtitle"><?php echo $row['name']; ?></div>
                </div>

                <div class="case-body">
                    <div class="info-grid">
                        <!-- Personal Information -->
                        <div class="info-section">
                            <div class="section-title">
                                <i class="fas fa-user-circle"></i>
                                Personal Information
                            </div>
                            <div class="info-row">
                                <span class="info-label">Full Name:</span>
                                <span class="info-value"><?php echo $row['name']; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">CC Number:</span>
                                <span class="info-value"><?php echo $row['cc_number'] ?: 'N/A'; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Address:</span>
                                <span class="info-value"><?php echo $row['address']; ?></span>
                            </div>
                        </div>

                        <!-- Case Information -->
                        <div class="info-section">
                            <div class="section-title">
                                <i class="fas fa-gavel"></i>
                                Case Information
                            </div>
                            <div class="info-row">
                                <span class="info-label">Court:</span>
                                <span class="info-value"><?php echo $row['court']; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Offense:</span>
                                <span class="info-value"><?php echo $row['offense']; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Sentence:</span>
                                <span class="info-value"><?php echo $row['sentence']; ?></span>
                            </div>
                        </div>

                        <!-- Investigation Details -->
                        <div class="info-section">
                            <div class="section-title">
                                <i class="fas fa-clipboard-list"></i>
                                Investigation Details
                            </div>
                            <div class="info-row">
                                <span class="info-label">Investigator:</span>
                                <span class="info-value"><?php echo $row['investigator']; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Date Filed:</span>
                                <span class="info-value"><?php echo date('F d, Y', strtotime($row['date_filed'])); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Status:</span>
                                <span class="info-value">
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </span>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="info-section">
                            <div class="section-title">
                                <i class="fas fa-comment"></i>
                                Remarks
                            </div>
                            <div class="info-row">
                                <span class="info-value"><?php echo $row['remarks'] ?: 'No remarks provided.'; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <a href="pi_list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                        
                        <?php if($can_edit): ?>
                            <a href="pi_edit.php?id=<?php echo $id; ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Edit Case
                            </a>
                        <?php else: ?>
                            <span class="btn btn-disabled">
                                <i class="fas fa-lock"></i> Edit (View Only)
                            </span>
                        <?php endif; ?>

                        <button onclick="window.print()" class="btn btn-secondary">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>