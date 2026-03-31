<?php
session_start();
include '../../config/database.php';
include '../../includes/permissions.php';

// Check login
if(!isset($_SESSION['username'])) {
    header("Location: ../../public/login.php");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
    <title>Pre-Investigation Cases - PPA System</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
            background: #f5f7fa;
            color: #1e293b;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Color Theme Variables */
        :root {
            --primary-dark: #1e4a3d;      /* Dark Green */
            --primary: #2e6b5e;           /* Medium Green */
            --primary-light: #d1fae5;      /* Light Green for backgrounds */
            --accent-yellow: #fbbf24;      /* Yellow */
            --accent-yellow-light: #fef3c7;
            --accent-red: #dc2626;         /* Red */
            --accent-red-light: #fee2e2;
            --neutral-white: #ffffff;
            --neutral-light: #f8fafc;
            --neutral-border: #e2e8f0;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --sidebar-width: 280px;
            --sidebar-width-mobile: 240px;
            --header-height: 70px;
            --border-radius: 12px;
            --box-shadow: 0 4px 6px -2px rgba(0,0,0,0.05), 0 10px 15px -3px rgba(0,0,0,0.03);
            --card-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.02);
        }

        /* App Layout */
        .app {
            display: flex;
            min-height: 100vh;
            position: relative;
            width: 100%;
        }

        /* Mobile Menu Toggle */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 101;
            background: var(--primary-dark);
            color: white;
            width: 45px;
            height: 45px;
            border-radius: 10px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--box-shadow);
            border: none;
            font-size: 1.5rem;
        }

        .menu-toggle i {
            color: white;
        }

        /* Sidebar Overlay for Mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 99;
            backdrop-filter: blur(3px);
        }

        .sidebar-overlay.active {
            display: block;
        }

        /* Sidebar - Dark Green Theme */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--primary-dark);
            padding: 2rem 1.5rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 4px 0 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
            z-index: 100;
        }

        .logo {
            font-weight: 700;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 2.5rem;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo i {
            color: var(--accent-yellow);
            font-size: 1.8rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.875rem 1rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 0.25rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        .nav-item.active {
            background: white;
            color: var(--primary-dark);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .nav-item i {
            width: 24px;
            font-size: 1.2rem;
            text-align: center;
        }

        /* Main Content */
        .main {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 2rem;
            width: calc(100% - var(--sidebar-width));
            transition: margin-left 0.3s ease;
        }

        /* Top Bar */
        .top-bar {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.25rem 2rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--box-shadow);
            border: 1px solid var(--neutral-border);
        }

        .page-title {
            font-size: clamp(1.2rem, 4vw, 1.5rem);
            font-weight: 600;
            color: var(--primary-dark);
            position: relative;
            padding-left: 1rem;
            border-left: 4px solid var(--accent-yellow);
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-name {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .avatar {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 2px 8px rgba(46,107,94,0.25);
        }

        .logout-btn {
            color: var(--text-muted);
            transition: all 0.2s;
            font-size: 1.2rem;
        }

        .logout-btn:hover {
            color: var(--accent-red);
            transform: scale(1.1);
        }

        .view-only-badge {
            background: var(--accent-yellow-light);
            color: #b45309;
            padding: 0.35rem 1rem;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid #fde68a;
        }

        /* Messages */
        .message {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease;
            font-size: 0.95rem;
            border-left: 4px solid;
        }

        .message.success {
            background: #ecfdf3;
            color: #065f46;
            border-color: #059669;
        }

        .message.error {
            background: var(--accent-red-light);
            color: #991b1b;
            border-color: var(--accent-red);
        }

        @keyframes slideIn {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Stats Grid - Enhanced Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: 1px solid var(--neutral-border);
            transition: all 0.3s ease;
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
            background: linear-gradient(90deg, var(--primary), var(--accent-yellow));
        }

        .stat-card.pending::before { background: #f59e0b; }
        .stat-card.review::before { background: #0284c7; }
        .stat-card.approved::before { background: #059669; }
        .stat-card.converted::before { background: #8b5cf6; }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }

        .stat-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.pending { 
            background: var(--accent-yellow-light); 
            color: #b45309;
            box-shadow: 0 4px 8px rgba(180,83,9,0.15);
        }
        .stat-icon.review { 
            background: #e0f2fe; 
            color: #0284c7;
            box-shadow: 0 4px 8px rgba(2,132,199,0.15);
        }
        .stat-icon.approved { 
            background: #ecfdf3; 
            color: #059669;
            box-shadow: 0 4px 8px rgba(5,150,105,0.15);
        }
        .stat-icon.converted { 
            background: #ede9fe; 
            color: #7c3aed;
            box-shadow: 0 4px 8px rgba(124,58,237,0.15);
        }

        .stat-content {
            flex: 1;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: clamp(1.5rem, 5vw, 2.25rem);
            font-weight: 700;
            line-height: 1.2;
        }

        .stat-value.pending { color: #d97706; }
        .stat-value.review { color: #0284c7; }
        .stat-value.approved { color: #059669; }
        .stat-value.converted { color: #7c3aed; }

        .stat-note {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Action Buttons */
        .action-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 1px solid var(--neutral-border);
        }

        .btn-primary {
            background: var(--primary-dark);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 6px -2px rgba(30,74,61,0.2);
        }

        .btn-primary:hover {
            background: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(30,74,61,0.3);
        }

        .btn-success {
            background: #10b981;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .search-box {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            margin-left: auto;
        }

        .search-wrapper {
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .search-input {
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--neutral-border);
            border-radius: 10px;
            width: 280px;
            font-size: 0.95rem;
            transition: all 0.3s;
            background: #f8fafc;
        }

        .search-input:focus {
            border-color: var(--primary);
            outline: none;
            background: white;
            box-shadow: 0 0 0 3px rgba(46,107,94,0.1);
        }

        .filter-select {
            padding: 0.75rem;
            border: 1px solid var(--neutral-border);
            border-radius: 10px;
            background: #f8fafc;
            color: var(--text-primary);
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s;
            min-width: 150px;
        }

        .filter-select:focus {
            border-color: var(--primary);
            outline: none;
            background: white;
        }

        /* Table Container */
        .table-container {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--neutral-border);
            margin-bottom: 2rem;
        }

        /* Modern Table Design */
        .cases-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cases-table thead tr {
            background: linear-gradient(90deg, #f8fafc, white);
        }

        .cases-table th {
            text-align: left;
            padding: 1.25rem 1.5rem;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--neutral-border);
            white-space: nowrap;
        }

        .cases-table td {
            padding: 1.25rem 1.5rem;
            color: var(--text-primary);
            font-size: 0.95rem;
            border-bottom: 1px solid var(--neutral-border);
            transition: background 0.2s;
        }

        .cases-table tbody tr {
            transition: all 0.2s;
        }

        .cases-table tbody tr:hover {
            background: #faf9fe;
            transform: scale(1.01);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .cases-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Status Badges - Enhanced */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.4rem 1rem;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            white-space: nowrap;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .status-Pending {
            background: var(--accent-yellow-light);
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .status-Approved {
            background: #ecfdf3;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .status-ForReview {
            background: #e0f2fe;
            color: #0284c7;
            border: 1px solid #bae6fd;
        }

        .status-Rejected {
            background: var(--accent-red-light);
            color: var(--accent-red);
            border: 1px solid #fecaca;
        }

        /* Client Badge */
        .client-badge {
            background: #f1f5f9;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            color: var(--text-secondary);
            display: inline-block;
            margin-left: 0.5rem;
            font-weight: 500;
        }

        .client-badge.unlinked {
            background: var(--accent-red-light);
            color: var(--accent-red);
        }

        /* Converted Badge */
        .converted-badge {
            background: #8b5cf6;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            white-space: nowrap;
            box-shadow: 0 2px 4px rgba(139,92,246,0.3);
        }

        .converted-badge i {
            font-size: 0.7rem;
        }

        /* Docket Number */
        .docket-number {
            font-weight: 600;
            color: var(--primary-dark);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .action-link {
            color: var(--text-muted);
            text-decoration: none;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: #f8fafc;
        }

        .action-link:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .action-link.approve {
            color: #10b981;
        }

        .action-link.approve:hover {
            background: #ecfdf3;
            color: #059669;
        }

        .action-link.delete:hover {
            background: var(--accent-red-light);
            color: var(--accent-red);
        }

        .action-link.disabled {
            color: #cbd5e1;
            background: #f1f5f9;
            pointer-events: none;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .action-link.lock {
            color: #94a3b8;
        }

        /* Results Count */
        .results-count {
            color: var(--text-muted);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
            padding: 0.5rem 0;
        }

        .results-count i {
            color: var(--accent-yellow);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }

        .empty-state .btn-primary {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: var(--primary-dark);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .empty-state .btn-primary:hover {
            background: var(--primary);
            transform: translateY(-2px);
        }

        /* Workflow Info */
        .workflow-info {
            margin-top: 2rem;
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--neutral-border);
            box-shadow: var(--box-shadow);
        }

        .workflow-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .workflow-title i {
            color: var(--accent-yellow);
        }

        .workflow-steps {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .workflow-step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            padding: 0.25rem 1rem;
            border-radius: 30px;
        }

        .workflow-step.pending { background: var(--accent-yellow-light); color: #b45309; }
        .workflow-step.review { background: #e0f2fe; color: #0284c7; }
        .workflow-step.approved { background: #ecfdf3; color: #059669; }
        .workflow-step.converted { background: #ede9fe; color: #7c3aed; }

        .workflow-tip {
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--neutral-border);
        }

        .workflow-tip i {
            color: var(--accent-yellow);
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* ============ RESPONSIVE BREAKPOINTS ============ */

        /* Large Desktop (1200px and above) */
        @media (min-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Desktop (992px to 1199px) */
        @media (max-width: 1199px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .cases-table th,
            .cases-table td {
                padding: 1rem;
            }
        }

        /* Tablet (768px to 991px) */
        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width-mobile);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .menu-toggle {
                display: flex;
            }
            
            .sidebar-overlay.active {
                display: block;
            }
            
            .main {
                margin-left: 0;
                width: 100%;
                padding: 1.5rem;
                padding-top: calc(var(--header-height) + 1rem);
            }
            
            .top-bar {
                margin-top: 0;
            }
            
            .stats-grid {
                gap: 1rem;
            }
            
            .cases-table {
                min-width: 1000px;
            }
            
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                margin-left: 0;
                width: 100%;
            }
            
            .search-wrapper {
                flex: 1;
            }
            
            .search-input {
                width: 100%;
            }
            
            .filter-select {
                width: 100%;
            }
        }

        /* Mobile Landscape (576px to 767px) */
        @media (max-width: 767px) {
            .main {
                padding: 1rem;
                padding-top: calc(var(--header-height) + 1rem);
            }
            
            .top-bar {
                padding: 1rem 1.25rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .user-menu {
                width: 100%;
                justify-content: space-between;
            }
            
            .user-name {
                display: none;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            
            .stat-card {
                padding: 1.25rem;
            }
            
            .action-bar {
                padding: 1.25rem;
            }
            
            .cases-table th,
            .cases-table td {
                padding: 0.875rem 1rem;
                font-size: 0.9rem;
            }
            
            .status-badge {
                padding: 0.3rem 0.8rem;
                font-size: 0.75rem;
            }
            
            .action-link {
                width: 32px;
                height: 32px;
                font-size: 0.9rem;
            }
            
            .converted-badge {
                padding: 0.2rem 0.6rem;
                font-size: 0.65rem;
            }
            
            .workflow-steps {
                gap: 0.75rem;
            }
            
            .workflow-step {
                font-size: 0.8rem;
                padding: 0.2rem 0.8rem;
            }
        }

        /* Mobile Portrait (up to 575px) */
        @media (max-width: 575px) {
            .main {
                padding: 0.75rem;
                padding-top: calc(var(--header-height) + 1rem);
            }
            
            .top-bar {
                padding: 0.875rem 1rem;
            }
            
            .page-title {
                font-size: 1.1rem;
                padding-left: 0.75rem;
            }
            
            .avatar {
                width: 36px;
                height: 36px;
            }
            
            .logout-btn {
                font-size: 1rem;
            }
            
            .view-only-badge {
                padding: 0.25rem 0.75rem;
                font-size: 0.7rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 1.25rem;
            }
            
            .stat-value {
                font-size: 1.75rem;
            }
            
            .message {
                padding: 0.875rem 1rem;
                font-size: 0.9rem;
            }
            
            .btn-primary, .btn-success {
                width: 100%;
                justify-content: center;
            }
            
            .results-count {
                font-size: 0.85rem;
            }
            
            .workflow-info {
                padding: 1rem;
            }
        }

        /* Small Mobile (up to 375px) */
        @media (max-width: 375px) {
            .main {
                padding: 0.5rem;
            }
            
            .top-bar {
                padding: 0.75rem;
            }
            
            .avatar {
                width: 32px;
                height: 32px;
            }
            
            .stat-card {
                padding: 0.875rem;
            }
            
            .stat-icon {
                width: 36px;
                height: 36px;
                font-size: 1.1rem;
            }
            
            .stat-value {
                font-size: 1.5rem;
            }
            
            .stat-label {
                font-size: 0.8rem;
            }
            
            .cases-table th,
            .cases-table td {
                padding: 0.75rem;
                font-size: 0.85rem;
            }
            
            .action-link {
                width: 28px;
                height: 28px;
                font-size: 0.85rem;
            }
            
            .converted-badge {
                font-size: 0.6rem;
                padding: 0.15rem 0.5rem;
            }
            
            .workflow-step {
                font-size: 0.75rem;
                padding: 0.15rem 0.6rem;
            }
            
            .workflow-tip {
                font-size: 0.75rem;
            }
        }

        /* Print Styles */
        @media print {
            .sidebar, .menu-toggle, .sidebar-overlay, .action-bar,
            .action-link, .logout-btn, .avatar, .search-box {
                display: none !important;
            }
            
            .main {
                margin-left: 0;
                padding: 0.5in;
            }
            
            .top-bar {
                border: none;
                box-shadow: none;
            }
            
            .stat-card, .table-container, .workflow-info {
                break-inside: avoid;
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .status-badge, .converted-badge {
                border: 1px solid #000;
                background: none !important;
                color: #000 !important;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="app">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fas fa-scale-balanced"></i>
                <span>PPA System</span>
            </div>
            
            <nav>
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
                <a href="clients.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Clients</span>
                </a>
                <a href="pi_list.php" class="nav-item active">
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
                <?php if($user_role == 'main' || $user_role == 'admin'): ?>
                <a href="staff_management.php" class="nav-item">
                    <i class="fas fa-user-tie"></i>
                    <span>Staff</span>
                </a>
                <?php endif; ?>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1 class="page-title">Pre-Investigation Cases</h1>
                <div class="user-menu">
                    <?php if(!$can_edit): ?>
                        <span class="view-only-badge">
                            <i class="fas fa-eye"></i> View Only
                        </span>
                    <?php endif; ?>
                    <span class="user-name"><?php echo htmlspecialchars($fullname); ?></span>
                    <div class="avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <a href="logout.php" class="logout-btn" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>

            <!-- Error/Success Messages -->
            <?php if(isset($error_msg)): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle fa-lg"></i>
                    <span><?php echo $error_msg; ?></span>
                </div>
            <?php endif; ?>

            <?php if(isset($success_msg)): ?>
                <div class="message success">
                    <i class="fas fa-check-circle fa-lg"></i>
                    <span><?php echo $success_msg; ?></span>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error']) && $_GET['error'] == 'unauthorized'): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle fa-lg"></i>
                    <span>You don't have permission to perform that action.</span>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'added'): ?>
                <div class="message success">
                    <i class="fas fa-check-circle fa-lg"></i>
                    <span>PI Case added successfully!</span>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
                <div class="message success">
                    <i class="fas fa-check-circle fa-lg"></i>
                    <span>PI Case updated successfully!</span>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="message success">
                    <i class="fas fa-check-circle fa-lg"></i>
                    <span>PI Case deleted successfully!</span>
                </div>
            <?php endif; ?>

            <!-- Stats Grid - Enhanced -->
            <div class="stats-grid">
                <div class="stat-card pending">
                    <div class="stat-header">
                        <div class="stat-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Pending Cases</div>
                            <div class="stat-value pending"><?php echo $pending; ?></div>
                        </div>
                    </div>
                    <div class="stat-note">
                        <i class="fas fa-hourglass-half"></i> Awaiting initial review
                    </div>
                </div>
                
                <div class="stat-card review">
                    <div class="stat-header">
                        <div class="stat-icon review">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">For Review</div>
                            <div class="stat-value review"><?php echo $review; ?></div>
                        </div>
                    </div>
                    <div class="stat-note">
                        <i class="fas fa-clipboard-check"></i> Under evaluation
                    </div>
                </div>
                
                <div class="stat-card approved">
                    <div class="stat-header">
                        <div class="stat-icon approved">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Approved</div>
                            <div class="stat-value approved"><?php echo $approved; ?></div>
                        </div>
                    </div>
                    <div class="stat-note">
                        <i class="fas fa-arrow-right"></i> Ready for PS conversion
                    </div>
                </div>
                
                <div class="stat-card converted">
                    <div class="stat-header">
                        <div class="stat-icon converted">
                            <i class="fas fa-gavel"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Converted to PS</div>
                            <div class="stat-value converted"><?php echo $converted; ?></div>
                        </div>
                    </div>
                    <div class="stat-note">
                        <i class="fas fa-check-double"></i> Successfully transferred
                    </div>
                </div>
            </div>

            <!-- Action Bar with Search -->
            <div class="action-bar">
                <?php if($can_edit): ?>
                    <a href="pi_add.php" class="btn-primary">
                        <i class="fas fa-plus-circle"></i> Add New PI Case
                    </a>
                <?php endif; ?>

                <div class="search-box">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="search-input" placeholder="Search by docket, client, offense...">
                    </div>
                    <select id="statusFilter" class="filter-select">
                        <option value="all">📊 All Status</option>
                        <option value="Pending">⏳ Pending</option>
                        <option value="For Review">🔍 For Review</option>
                        <option value="Approved">✅ Approved</option>
                        <option value="Rejected">❌ Rejected</option>
                    </select>
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-container">
                <table class="cases-table" id="casesTable">
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
                            <tr class="case-row" data-status="<?php echo str_replace(' ', '', $row['status']); ?>">
                                <td><span class="docket-number"><?php echo $row['docket_number']; ?></span></td>
                                <td>
                                    <?php echo $row['client_name'] ?: $row['name']; ?>
                                    <?php if(!$row['client_id']): ?>
                                        <span class="client-badge unlinked">Unlinked</span>
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
                                    <div class="action-buttons">
                                        <a href="pi_view.php?id=<?php echo $row['id']; ?>" class="action-link" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if($can_edit): ?>
                                            <?php if($row['status'] == 'Pending' && $row['client_id'] && !$row['ps_id']): ?>
                                                <a href="?approve=<?php echo $row['id']; ?>" class="action-link approve" title="Approve to PS" 
                                                   onclick="return confirm('⚠️ Approve this PI case and create PS case?\n\nClient: <?php echo addslashes($row['client_name'] ?: $row['name']); ?>\nDocket: <?php echo $row['docket_number']; ?>\n\nThis will create a new PS case.')">
                                                    <i class="fas fa-check-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="pi_edit.php?id=<?php echo $row['id']; ?>" class="action-link" title="Edit Case">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <?php if(!$row['ps_id']): ?>
                                            <a href="pi_delete.php?id=<?php echo $row['id']; ?>" class="action-link delete" title="Delete Case" 
                                               onclick="return confirm('⚠️ Are you sure you want to delete this case?\n\nDocket: <?php echo $row['docket_number']; ?>\n\nThis action cannot be undone!')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php else: ?>
                                            <span class="action-link disabled lock" title="Cannot delete - already converted">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <?php endif; ?>
                                            
                                        <?php else: ?>
                                            <span class="action-link disabled" title="View Only - No Edit Access">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="empty-state">
                                    <i class="fas fa-folder-open"></i>
                                    <h3>No PI Cases Found</h3>
                                    <p>Get started by adding your first pre-investigation case.</p>
                                    <?php if($can_edit): ?>
                                    <a href="pi_add.php" class="btn-primary">
                                        <i class="fas fa-plus-circle"></i> Add Your First Case
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Results Count -->
            <div class="results-count">
                <i class="fas fa-file-lines"></i>
                <span id="resultCount">Showing <?php echo mysqli_num_rows($pi_cases); ?> cases</span>
            </div>

            <!-- Workflow Info - Enhanced -->
            <div class="workflow-info">
                <div class="workflow-title">
                    <i class="fas fa-diagram-project"></i>
                    PI to PS Workflow
                </div>
                <div class="workflow-steps">
                    <span class="workflow-step pending">
                        <i class="fas fa-circle"></i> Pending → Ready for review
                    </span>
                    <span class="workflow-step review">
                        <i class="fas fa-circle"></i> For Review → Under evaluation
                    </span>
                    <span class="workflow-step approved">
                        <i class="fas fa-circle"></i> Approved → Ready for PS
                    </span>
                    <span class="workflow-step converted">
                        <i class="fas fa-circle"></i> Converted → Already in PS
                    </span>
                </div>
                <div class="workflow-tip">
                    <i class="fas fa-lightbulb"></i>
                    <span>Tip: Click the green checkmark <i class="fas fa-check-circle" style="color: #10b981;"></i> on pending cases with linked clients to approve and create a PS case.</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mobile Menu Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (menuToggle && sidebar && overlay) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('active');
                    
                    // Change icon
                    const icon = menuToggle.querySelector('i');
                    if (sidebar.classList.contains('active')) {
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-times');
                    } else {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                });
                
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    const icon = menuToggle.querySelector('i');
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                });
            }
            
            // Close sidebar on window resize if in desktop mode
            window.addEventListener('resize', function() {
                if (window.innerWidth > 991) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    const icon = menuToggle?.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                }
            });

            // Live Search and Filter Functionality
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const rows = document.querySelectorAll('.case-row');
            const resultCountSpan = document.getElementById('resultCount');

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const filterValue = statusFilter.value;
                let visibleCount = 0;

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const status = row.dataset.status;
                    
                    const matchesSearch = text.includes(searchTerm);
                    const matchesFilter = filterValue === 'all' || status === filterValue;

                    if (matchesSearch && matchesFilter) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Update result count
                resultCountSpan.textContent = `Showing ${visibleCount} of ${rows.length} cases`;
                
                // Show "no results" message if needed
                const tbody = document.querySelector('#casesTable tbody');
                let noResultsRow = document.getElementById('noResultsRow');
                
                if (visibleCount === 0 && rows.length > 0) {
                    if (!noResultsRow) {
                        noResultsRow = document.createElement('tr');
                        noResultsRow.id = 'noResultsRow';
                        noResultsRow.innerHTML = '<td colspan="10" style="text-align: center; padding: 2rem; color: #64748b;"><i class="fas fa-search" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>🔍 No cases match your filters</td>';
                        tbody.appendChild(noResultsRow);
                    }
                } else if (noResultsRow) {
                    noResultsRow.remove();
                }
            }

            // Clear filters function
            window.clearFilters = function() {
                searchInput.value = '';
                statusFilter.value = 'all';
                filterTable();
            };

            // Add event listeners
            if (searchInput && statusFilter) {
                searchInput.addEventListener('input', filterTable);
                statusFilter.addEventListener('change', filterTable);
            }

            // Initial filter
            if (typeof filterTable === 'function') {
                filterTable();
            }

            // Keyboard shortcut: ESC to clear search
            if (searchInput) {
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && this.value !== '') {
                        this.value = '';
                        filterTable();
                    }
                });
            }
        });
    </script>
</body>
</html>