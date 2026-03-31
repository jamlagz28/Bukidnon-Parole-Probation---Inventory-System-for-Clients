<?php
session_start();
include 'includes/config.php';

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'staff';
$can_edit = ($user_role == 'main' || $user_role == 'admin');

// Handle delete request
if(isset($_GET['delete']) && $can_edit) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // Delete the case
    $delete_query = "DELETE FROM probation_supervision WHERE id = $id";
    if(mysqli_query($conn, $delete_query)) {
        $_SESSION['message'] = "Case deleted successfully";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting case: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "danger";
    }
    
    header("Location: ps_list.php");
    exit();
}

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
    <title>Probation Supervision Cases - PPA System</title>
    
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

        /* Alert Messages */
        .alert {
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

        .alert-success {
            background: #ecfdf3;
            color: #065f46;
            border-color: #059669;
        }

        .alert-danger {
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

        .stat-icon.active { 
            background: #ecfdf3; 
            color: #059669;
            box-shadow: 0 4px 8px rgba(5,150,105,0.15);
        }
        .stat-icon.payment { 
            background: #e0f2fe; 
            color: #0284c7;
            box-shadow: 0 4px 8px rgba(2,132,199,0.15);
        }
        .stat-icon.overdue { 
            background: var(--accent-red-light); 
            color: var(--accent-red);
            box-shadow: 0 4px 8px rgba(220,38,38,0.15);
        }
        .stat-icon.fee { 
            background: var(--accent-yellow-light); 
            color: #b45309;
            box-shadow: 0 4px 8px rgba(180,83,9,0.15);
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

        .stat-value.active { color: #059669; }
        .stat-value.payment { color: #0284c7; }
        .stat-value.overdue { color: var(--accent-red); }
        .stat-value.fee { color: #b45309; }

        .stat-note {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Action Bar */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .add-btn {
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

        .add-btn:hover {
            background: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(30,74,61,0.3);
        }

        .search-box {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
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

        /* Docket Number */
        .docket-number {
            font-weight: 600;
            color: var(--primary-dark);
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

        .status-Active {
            background: #ecfdf3;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .status-Terminated {
            background: #e0f2fe;
            color: #0284c7;
            border: 1px solid #bae6fd;
        }

        .status-Revoked {
            background: var(--accent-yellow-light);
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .status-Completed {
            background: #ede9fe;
            color: #7c3aed;
            border: 1px solid #c4b5fd;
        }

        /* Payment Status */
        .payment-status {
            display: inline-flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .payment-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            width: fit-content;
        }

        .payment-current {
            background: #ecfdf3;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .payment-overdue {
            background: var(--accent-red-light);
            color: var(--accent-red);
            border: 1px solid #fecaca;
            font-weight: 600;
        }

        .payment-nodata {
            background: #f1f5f9;
            color: var(--text-muted);
            border: 1px solid var(--neutral-border);
        }

        .payment-date {
            font-size: 0.7rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.25rem;
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

        .action-link.delete:hover {
            background: var(--accent-red-light);
            color: var(--accent-red);
        }

        .action-link.payment:hover {
            background: #e0f2fe;
            color: #0284c7;
        }

        .action-link.view:hover {
            background: #ede9fe;
            color: #7c3aed;
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

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            max-width: 400px;
            margin: 1rem;
            padding: 2rem;
            border-radius: var(--border-radius);
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            animation: modalPop 0.3s ease;
        }

        @keyframes modalPop {
            from {
                transform: scale(0.95);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .modal-icon {
            font-size: 3rem;
            color: var(--accent-red);
            margin-bottom: 1rem;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .modal-text {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .btn {
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .btn-danger {
            background: var(--accent-red);
            color: white;
        }

        .btn-danger:hover {
            background: #b91c1c;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background: #cbd5e1;
            transform: translateY(-2px);
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
                align-items: flex-start;
            }
            
            .search-box {
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
            
            .add-btn {
                width: 100%;
                justify-content: center;
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
            
            .payment-badge {
                padding: 0.2rem 0.6rem;
                font-size: 0.7rem;
            }
            
            .action-link {
                width: 32px;
                height: 32px;
                font-size: 0.9rem;
            }
            
            .modal-content {
                padding: 1.5rem;
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
            
            .alert {
                padding: 0.875rem 1rem;
                font-size: 0.9rem;
            }
            
            .results-count {
                font-size: 0.85rem;
            }
            
            .modal-title {
                font-size: 1.1rem;
            }
            
            .modal-text {
                font-size: 0.9rem;
            }
            
            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
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
        }

        /* Print Styles */
        @media print {
            .sidebar, .menu-toggle, .sidebar-overlay, .action-bar,
            .action-link, .logout-btn, .avatar, .modal {
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
            
            .stat-card, .table-container {
                break-inside: avoid;
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .status-badge {
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
                <a href="pi_list.php" class="nav-item">
                    <i class="fas fa-file-lines"></i>
                    <span>PI Cases</span>
                </a>
                <a href="ps_list.php" class="nav-item active">
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
                <h1 class="page-title">Probation Supervision</h1>
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

            <!-- Alert Messages -->
            <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['msg_type']; ?>">
                <i class="fas fa-<?php echo $_SESSION['msg_type'] == 'success' ? 'check-circle' : 'exclamation-circle'; ?> fa-lg"></i>
                <span><?php echo $_SESSION['message']; ?></span>
            </div>
            <?php 
                unset($_SESSION['message']);
                unset($_SESSION['msg_type']);
            endif; 
            ?>

            <?php
            // Get stats
            $active_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM probation_supervision WHERE status='Active'"))['total'];
            $total_payments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM probation_payments"))['total'] ?? 0;
            $overdue_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM probation_supervision WHERE next_payment_date < CURDATE() AND status='Active'"))['total'];
            ?>

            <!-- Stats Grid - Enhanced -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon active">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Active Cases</div>
                            <div class="stat-value active"><?php echo $active_count; ?></div>
                        </div>
                    </div>
                    <div class="stat-note">
                        <i class="fas fa-chart-line"></i> Currently under supervision
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon payment">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Total Payments</div>
                            <div class="stat-value payment">₱<?php echo number_format($total_payments, 2); ?></div>
                        </div>
                    </div>
                    <div class="stat-note">
                        <i class="fas fa-calendar-alt"></i> Lifetime collections
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon overdue">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Overdue Payments</div>
                            <div class="stat-value overdue"><?php echo $overdue_count; ?></div>
                        </div>
                    </div>
                    <div class="stat-note">
                        <i class="fas fa-clock"></i> Requiring attention
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon fee">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Monthly Fee</div>
                            <div class="stat-value fee">₱500.00</div>
                        </div>
                    </div>
                    <div class="stat-note">
                        <i class="fas fa-calendar-week"></i> Standard rate
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="action-bar">
                <?php if($can_edit): ?>
                <a href="ps_add.php" class="add-btn">
                    <i class="fas fa-plus-circle"></i> Add New PS Case
                </a>
                <?php endif; ?>

                <div class="search-box">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="search-input" placeholder="Search by docket, name, offense...">
                    </div>
                    <select id="statusFilter" class="filter-select">
                        <option value="all">📊 All Status</option>
                        <option value="Active">🟢 Active</option>
                        <option value="Terminated">🔵 Terminated</option>
                        <option value="Revoked">🟠 Revoked</option>
                        <option value="Completed">🟣 Completed</option>
                    </select>
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-container">
                <table class="cases-table" id="casesTable">
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
                        <?php if(mysqli_num_rows($ps_cases) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($ps_cases)): 
                                $payment_status = getPaymentStatus($row['last_payment_date'], $row['next_payment_date']);
                            ?>
                            <tr class="case-row" data-status="<?php echo $row['status']; ?>">
                                <td><span class="docket-number"><?php echo $row['docket_number']; ?></span></td>
                                <td><strong><?php echo $row['name']; ?></strong></td>
                                <td><?php echo substr($row['offense'], 0, 30); ?>...</td>
                                <td><span style="font-weight: 600; color: var(--primary-dark);">₱<?php echo number_format($row['payment'], 2); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($row['start_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['end_date'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php 
                                        $status_icons = [
                                            'Active' => '🟢',
                                            'Terminated' => '🔵',
                                            'Revoked' => '🟠',
                                            'Completed' => '🟣'
                                        ];
                                        echo ($status_icons[$row['status']] ?? '⚪') . ' ' . $row['status']; 
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="payment-status">
                                        <?php if($payment_status == 'Current'): ?>
                                            <span class="payment-badge payment-current">
                                                <i class="fas fa-check-circle"></i> Current
                                            </span>
                                        <?php elseif($payment_status == 'Overdue'): ?>
                                            <span class="payment-badge payment-overdue">
                                                <i class="fas fa-exclamation-circle"></i> Overdue
                                            </span>
                                        <?php else: ?>
                                            <span class="payment-badge payment-nodata">
                                                <i class="fas fa-minus-circle"></i> No Payment
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if($row['next_payment_date']): ?>
                                            <span class="payment-date">
                                                <i class="fas fa-calendar-alt"></i> Next: <?php echo date('M d', strtotime($row['next_payment_date'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="ps_view.php?id=<?php echo $row['id']; ?>" class="action-link view" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if($can_edit): ?>
                                        <a href="ps_edit.php?id=<?php echo $row['id']; ?>" class="action-link" title="Edit Case">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="ps_payment.php?id=<?php echo $row['id']; ?>" class="action-link payment" title="Add Payment">
                                            <i class="fas fa-money-bill"></i>
                                        </a>
                                        <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo addslashes($row['name']); ?>')" class="action-link delete" title="Delete Case">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="empty-state">
                                    <i class="fas fa-gavel"></i>
                                    <h3>No PS Cases Found</h3>
                                    <p>Get started by adding your first probation supervision case.</p>
                                    <?php if($can_edit): ?>
                                    <a href="ps_add.php" class="btn-primary">
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
                <i class="fas fa-gavel"></i>
                <span id="resultCount">Showing <?php echo mysqli_num_rows($ps_cases); ?> cases</span>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal - Enhanced -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="modal-title">Confirm Delete</h3>
            <p class="modal-text" id="deleteModalText">Are you sure you want to delete this case? This action cannot be undone.</p>
            <div class="modal-actions">
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
                <button onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
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
        });

        // Delete Modal Functions - Enhanced
        function confirmDelete(id, name) {
            const modal = document.getElementById('deleteModal');
            const modalText = document.getElementById('deleteModalText');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            if (name) {
                modalText.textContent = `Are you sure you want to delete the case for "${name}"? This action cannot be undone.`;
            } else {
                modalText.textContent = 'Are you sure you want to delete this case? This action cannot be undone.';
            }
            
            modal.classList.add('active');
            confirmBtn.href = '?delete=' + id;
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                modal.classList.remove('active');
            }
        }

        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if(e.key === 'Escape') {
                closeDeleteModal();
            }
        });

        // Live Search and Filter Functionality
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const rows = document.querySelectorAll('.case-row');
        const resultCountSpan = document.getElementById('resultCount');

        function filterTable() {
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            const filterValue = statusFilter ? statusFilter.value : 'all';
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
            if (resultCountSpan) {
                resultCountSpan.textContent = `Showing ${visibleCount} of ${rows.length} cases`;
            }
            
            // Show "no results" message if needed
            const tbody = document.querySelector('#casesTable tbody');
            let noResultsRow = document.getElementById('noResultsRow');
            
            if (visibleCount === 0 && rows.length > 0) {
                if (!noResultsRow) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.id = 'noResultsRow';
                    noResultsRow.innerHTML = '<td colspan="9" style="text-align: center; padding: 2rem; color: #64748b;"><i class="fas fa-search" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>🔍 No cases match your filters</td>';
                    tbody.appendChild(noResultsRow);
                }
            } else if (noResultsRow) {
                noResultsRow.remove();
            }
        }

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
    </script>
</body>
</html>