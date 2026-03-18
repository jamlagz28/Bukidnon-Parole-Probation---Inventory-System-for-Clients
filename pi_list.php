<?php
session_start();
include 'config/database.php';
include 'includes/permissions.php';

// Check login
if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'staff';
$can_edit = canEdit($user_role);

// Handle Approve to PS action
if(isset($_GET['approve']) && $can_edit) {
    $pi_id = mysqli_real_escape_string($conn, $_GET['approve']);
    
    // Get PI case details
    $pi_query = mysqli_query($conn, "SELECT * FROM pre_investigation WHERE id='$pi_id'");
    if(mysqli_num_rows($pi_query) > 0) {
        $pi = mysqli_fetch_assoc($pi_query);
        
        // Check if client exists for this PI case
        $client_id = $pi['client_id'];
        
        if($client_id) {
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // 1. Update PI case status to Approved
                $update_pi = "UPDATE pre_investigation SET status='Approved' WHERE id='$pi_id'";
                mysqli_query($conn, $update_pi);
                
                // 2. Generate PS docket number
                $ps_docket = "PS-" . date('Y') . "-" . str_pad($pi_id, 4, '0', STR_PAD_LEFT);
                
                // 3. Create PS case
                $ps_query = "INSERT INTO probation_supervision (
                    client_id, docket_number, name, offense, address, 
                    start_date, end_date, supervising_officer, status, 
                    monthly_fee, source_pi_id
                ) VALUES (
                    '$client_id', '$ps_docket', '{$pi['name']}', '{$pi['offense']}', 
                    '{$pi['address']}', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 
                    'Pending Assignment', 'Pending', '500.00', '$pi_id'
                )";
                
                if(!mysqli_query($conn, $ps_query)) {
                    throw new Exception("Error creating PS case");
                }
                
                $ps_id = mysqli_insert_id($conn);
                
                // 4. Update client with PS case ID
                $update_client = "UPDATE clients SET ps_case_id='$ps_id' WHERE id='$client_id'";
                mysqli_query($conn, $update_client);
                
                mysqli_commit($conn);
                $success_msg = "PI Case #{$pi['docket_number']} approved and converted to PS Case #$ps_docket";
                
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error_msg = "Error approving case: " . $e->getMessage();
            }
        } else {
            $error_msg = "This PI case is not linked to any client. Please edit and add client information first.";
        }
    }
}

// Get all PI cases with client info
$pi_cases = mysqli_query($conn, "
    SELECT pi.*, c.name as client_name, c.docket_number as client_docket,
           ps.id as ps_id, ps.docket_number as ps_docket
    FROM pre_investigation pi
    LEFT JOIN clients c ON pi.client_id = c.id
    LEFT JOIN probation_supervision ps ON pi.id = ps.source_pi_id
    ORDER BY 
        CASE 
            WHEN pi.status = 'Pending' THEN 1
            WHEN pi.status = 'For Review' THEN 2
            WHEN pi.status = 'Approved' THEN 3
            ELSE 4
        END, pi.created_at DESC
");

// Get counts
$pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pre_investigation WHERE status='Pending'"))['total'];
$approved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pre_investigation WHERE status='Approved'"))['total'];
$review = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pre_investigation WHERE status='For Review'"))['total'];
$converted = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pre_investigation WHERE id IN (SELECT source_pi_id FROM probation_supervision)"))['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PI Cases - PPA System</title>
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
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.pending::before { background: #f59e0b; }
        .stat-card.review::before { background: #3b82f6; }
        .stat-card.approved::before { background: #10b981; }
        .stat-card.converted::before { background: #8b5cf6; }

        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 500;
        }

        .stat-note {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 0.25rem;
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

        .message.success {
            background: #ecfdf3;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #0f172a;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn-success {
            background: #10b981;
        }

        .btn-success:hover {
            background: #059669;
        }

        .table-container {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }

        th {
            text-align: left;
            padding: 1rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            color: #475569;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
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

        .action-link {
            color: #64748b;
            text-decoration: none;
            margin: 0 0.3rem;
            font-size: 1rem;
            transition: color 0.2s;
        }

        .action-link:hover {
            color: #0f172a;
        }

        .action-link.approve {
            color: #10b981;
        }

        .action-link.approve:hover {
            color: #059669;
        }

        .action-link.disabled {
            color: #cbd5e1;
            pointer-events: none;
        }

        .client-badge {
            background: #f1f5f9;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            color: #475569;
            display: inline-block;
        }

        .converted-badge {
            background: #8b5cf6;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .main {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="app">
        <div class="sidebar">
            <div class="logo">PPA System</div>
            <a href="dashboard.php" class="nav-item">Dashboard</a>
            <a href="clients.php" class="nav-item">Clients</a>
            <a href="pi_list.php" class="nav-item active">PI Cases</a>
            <a href="ps_list.php" class="nav-item">PS Cases</a>
        </div>

        <div class="main">
            <div class="top-bar">
                <h1 class="page-title">Pre-Investigation Cases</h1>
                <div class="user-menu">
                    <?php if(!$can_edit): ?>
                        <span class="view-only-badge">
                            <i class="fas fa-eye"></i> View Only
                        </span>
                    <?php endif; ?>
                    <span><?php echo $fullname; ?></span>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>

            <!-- Error/Success Messages -->
            <?php if(isset($error_msg)): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <?php if(isset($success_msg)): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error']) && $_GET['error'] == 'unauthorized'): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> You don't have permission to perform that action.
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'added'): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> PI Case added successfully!
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> PI Case updated successfully!
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> PI Case deleted successfully!
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card pending">
                    <div class="stat-label">Pending</div>
                    <div class="stat-value" style="color: #d97706;"><?php echo $pending; ?></div>
                    <div class="stat-note">Awaiting review</div>
                </div>
                <div class="stat-card review">
                    <div class="stat-label">For Review</div>
                    <div class="stat-value" style="color: #0284c7;"><?php echo $review; ?></div>
                    <div class="stat-note">Under evaluation</div>
                </div>
                <div class="stat-card approved">
                    <div class="stat-label">Approved</div>
                    <div class="stat-value" style="color: #059669;"><?php echo $approved; ?></div>
                    <div class="stat-note">Ready for PS</div>
                </div>
                <div class="stat-card converted">
                    <div class="stat-label">Converted to PS</div>
                    <div class="stat-value" style="color: #8b5cf6;"><?php echo $converted; ?></div>
                    <div class="stat-note">Successfully transferred</div>
                </div>
            </div>

            <!-- Add Button - Only for admin/main -->
            <?php if($can_edit): ?>
                <a href="pi_add.php" class="btn">
                    <i class="fas fa-plus"></i> Add New PI Case
                </a>
            <?php endif; ?>

            <!-- Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Docket #</th>
                            <th>Client Name</th>
                            <th>Client Docket</th>
                            <th>CC Number</th>
                            <th>Court</th>
                            <th>Offense</th>
                            <th>Investigator</th>
                            <th>Status</th>
                            <th>PS Case</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($pi_cases) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($pi_cases)): ?>
                            <tr>
                                <td><strong><?php echo $row['docket_number']; ?></strong></td>
                                <td>
                                    <?php echo $row['client_name'] ?: $row['name']; ?>
                                    <?php if(!$row['client_id']): ?>
                                        <span class="client-badge">Unlinked</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($row['client_docket']): ?>
                                        <span class="client-badge"><?php echo $row['client_docket']; ?></span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['cc_number'] ?: '—'; ?></td>
                                <td><?php echo $row['court']; ?></td>
                                <td><?php echo substr($row['offense'], 0, 25); ?>...</td>
                                <td><?php echo $row['investigator']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo str_replace(' ', '', $row['status']); ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($row['ps_id']): ?>
                                        <span class="converted-badge">
                                            <i class="fas fa-check"></i> <?php echo $row['ps_docket']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="pi_view.php?id=<?php echo $row['id']; ?>" class="action-link" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if($can_edit): ?>
                                        <?php if($row['status'] == 'Pending' && $row['client_id'] && !$row['ps_id']): ?>
                                            <a href="?approve=<?php echo $row['id']; ?>" class="action-link approve" title="Approve to PS" 
                                               onclick="return confirm('Approve this PI case and create PS case?\n\nClient: <?php echo addslashes($row['client_name'] ?: $row['name']); ?>\nDocket: <?php echo $row['docket_number']; ?>\n\nThis will create a new PS case.')">
                                                <i class="fas fa-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="pi_edit.php?id=<?php echo $row['id']; ?>" class="action-link" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <?php if(!$row['ps_id']): ?>
                                        <a href="pi_delete.php?id=<?php echo $row['id']; ?>" class="action-link" title="Delete" 
                                           onclick="return confirm('Are you sure you want to delete this case?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php else: ?>
                                        <span class="action-link disabled" title="Cannot delete - already converted">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <?php endif; ?>
                                        
                                    <?php else: ?>
                                        <span class="action-link disabled">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" style="text-align: center; padding: 3rem;">
                                    <i class="fas fa-folder-open" style="font-size: 2rem; color: #94a3b8; margin-bottom: 1rem; display: block;"></i>
                                    <p style="color: #64748b;">No PI cases found.</p>
                                    <?php if($can_edit): ?>
                                    <a href="pi_add.php" style="display: inline-block; margin-top: 1rem; color: #3b82f6;">Add your first PI case</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Workflow Info -->
            <div style="margin-top: 2rem; background: #f8fafc; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                <h3 style="font-size: 0.9rem; color: #475569; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-info-circle" style="color: #3b82f6;"></i>
                    PI to PS Workflow
                </h3>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap; font-size: 0.85rem;">
                    <span><span style="color: #f59e0b;">⬤ Pending</span> → Ready for approval</span>
                    <span><span style="color: #0284c7;">⬤ For Review</span> → Under evaluation</span>
                    <span><span style="color: #059669;">⬤ Approved</span> → Approved (ready)</span>
                    <span><span style="color: #8b5cf6;">⬤ Converted</span> → Already in PS</span>
                </div>
                <div style="margin-top: 0.5rem; font-size: 0.8rem; color: #64748b;">
                    <i class="fas fa-lightbulb"></i> Tip: Click the green checkmark <i class="fas fa-check-circle" style="color: #10b981;"></i> on pending cases to approve and create PS case.
                </div>
            </div>
        </div>
    </div>
</body>
</html>