<?php
session_start();
include 'config/database.php';

// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Get user info
$fullname = $_SESSION['fullname'] ?? 'User';
$username = $_SESSION['username'] ?? '';
$user_role = $_SESSION['role'] ?? 'staff';

// Get all clients
$clients = mysqli_query($conn, "SELECT * FROM clients ORDER BY created_at DESC");

// Get status counts for summary
$active = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM clients WHERE status='Active'"))['total'];
$terminated = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM clients WHERE status='Terminated'"))['total'];
$revoked = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM clients WHERE status='Revoked'"))['total'];
$denied = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM clients WHERE status='Denied'"))['total'];

// Handle delete request
if(isset($_GET['delete']) && ($user_role == 'main' || $user_role == 'admin')) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    mysqli_query($conn, "DELETE FROM clients WHERE id='$id'");
    header("Location: clients.php?msg=deleted");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
    <title>Client Management - Parole & Probation System</title>
    
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
            padding: 1rem 2rem;
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
        .stat-icon.terminated { 
            background: #e0f2fe; 
            color: #0284c7;
            box-shadow: 0 4px 8px rgba(2,132,199,0.15);
        }
        .stat-icon.revoked { 
            background: var(--accent-yellow-light); 
            color: #b45309;
            box-shadow: 0 4px 8px rgba(180,83,9,0.15);
        }
        .stat-icon.denied { 
            background: var(--accent-red-light); 
            color: var(--accent-red);
            box-shadow: 0 4px 8px rgba(220,38,38,0.15);
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
        .stat-value.terminated { color: #0284c7; }
        .stat-value.revoked { color: #b45309; }
        .stat-value.denied { color: var(--accent-red); }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.8rem;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid var(--neutral-border);
        }

        .trend-up { color: #10b981; }
        .trend-down { color: var(--accent-red); }

        /* Message */
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

        /* Actions Bar */
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 1px solid var(--neutral-border);
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
            margin-bottom: 1.5rem;
        }

        /* Modern Table Design */
        .clients-table {
            width: 100%;
            border-collapse: collapse;
        }

        .clients-table thead tr {
            background: linear-gradient(90deg, #f8fafc, white);
        }

        .clients-table th {
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

        .clients-table td {
            padding: 1.25rem 1.5rem;
            color: var(--text-primary);
            font-size: 0.95rem;
            border-bottom: 1px solid var(--neutral-border);
            transition: background 0.2s;
        }

        .clients-table tbody tr {
            transition: all 0.2s;
        }

        .clients-table tbody tr:hover {
            background: #faf9fe;
            transform: scale(1.01);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .clients-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Status Badges - Enhanced */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
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

        .status-Denied {
            background: var(--accent-red-light);
            color: var(--accent-red);
            border: 1px solid #fecaca;
        }

        /* Default status style for any other status */
        .status-default {
            background: #f1f5f9;
            color: var(--text-secondary);
            border: 1px solid var(--neutral-border);
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
            width: 32px;
            height: 32px;
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

        .action-link.view:hover {
            background: #e0f2fe;
            color: #0284c7;
        }

        .action-link.camera:hover {
            background: var(--accent-yellow-light);
            color: #b45309;
        }

        /* Results Count */
        .results-count {
            color: var(--text-muted);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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

        .empty-state .add-first-btn {
            background: var(--primary-dark);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .empty-state .add-first-btn:hover {
            background: var(--primary);
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
            
            .clients-table th,
            .clients-table td {
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
            
            .clients-table {
                min-width: 800px;
            }
            
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
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
        }

        /* Mobile Landscape (576px to 767px) */
        @media (max-width: 767px) {
            .main {
                padding: 1rem;
                padding-top: calc(var(--header-height) + 1rem);
            }
            
            .top-bar {
                padding: 1rem 1.25rem;
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
            
            .actions-bar {
                flex-direction: column;
                align-items: stretch;
                padding: 1.25rem;
            }
            
            .search-box {
                flex-direction: column;
                width: 100%;
            }
            
            .search-wrapper {
                width: 100%;
            }
            
            .filter-select {
                width: 100%;
            }
            
            .clients-table th,
            .clients-table td {
                padding: 0.875rem 1rem;
                font-size: 0.9rem;
            }
            
            .status-badge {
                padding: 0.35rem 0.75rem;
                font-size: 0.75rem;
            }
            
            .action-link {
                width: 28px;
                height: 28px;
                font-size: 0.9rem;
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
            
            .add-btn {
                width: 100%;
                justify-content: center;
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
            
            .results-count {
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
            
            .clients-table th,
            .clients-table td {
                padding: 0.75rem;
                font-size: 0.85rem;
            }
            
            .action-link {
                width: 26px;
                height: 26px;
                font-size: 0.85rem;
            }
        }

        /* Print Styles */
        @media print {
            .sidebar, .menu-toggle, .sidebar-overlay, .actions-bar,
            .add-btn, .search-box, .logout-btn, .action-link {
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
                <a href="clients.php" class="nav-item active">
                    <i class="fas fa-users"></i>
                    <span>Clients</span>
                </a>
                <a href="monthly_reports.php" class="nav-item">
                    <i class="fas fa-camera"></i>
                    <span>Monthly Reports</span>
                </a>
                <a href="pre_investigation.php" class="nav-item">
                    <i class="fas fa-file-lines"></i>
                    <span>Pre-Investigation</span>
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
                <h1 class="page-title">Client Management</h1>
                <div class="user-menu">
                    <span class="user-name"><?php echo htmlspecialchars($fullname); ?></span>
                    <div class="avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <a href="logout.php" class="logout-btn" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>

            <!-- Stats Summary - Enhanced Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon active">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Active Cases</div>
                            <div class="stat-value active"><?php echo $active; ?></div>
                        </div>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up trend-up"></i>
                        <span>12% from last month</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon terminated">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Terminated</div>
                            <div class="stat-value terminated"><?php echo $terminated; ?></div>
                        </div>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-minus" style="color: var(--text-muted);"></i>
                        <span>No change</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon revoked">
                            <i class="fas fa-user-slash"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Revoked</div>
                            <div class="stat-value revoked"><?php echo $revoked; ?></div>
                        </div>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-down trend-down"></i>
                        <span>5% from last month</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon denied">
                            <i class="fas fa-user-times"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Denied</div>
                            <div class="stat-value denied"><?php echo $denied; ?></div>
                        </div>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up trend-up"></i>
                        <span>3% from last month</span>
                    </div>
                </div>
            </div>

            <!-- Success Message -->
            <?php if(isset($_GET['msg'])): ?>
                <div class="message success">
                    <i class="fas fa-check-circle fa-lg"></i>
                    <span>Client <?php 
                        echo $_GET['msg'] == 'added' ? 'added' : 
                             ($_GET['msg'] == 'updated' ? 'updated' : 'deleted'); 
                    ?> successfully!</span>
                </div>
            <?php endif; ?>

            <!-- Actions Bar -->
            <div class="actions-bar">
                <a href="add_probationer.php" class="add-btn">
                    <i class="fas fa-plus-circle"></i>
                    Add New Client
                </a>
                
                <div class="search-box">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="search-input" placeholder="Search by name, docket, offense..." autocomplete="off">
                    </div>
                    <select id="statusFilter" class="filter-select">
                        <option value="all">📊 All Status</option>
                        <option value="Active">🟢 Active</option>
                        <option value="Terminated">🔵 Terminated</option>
                        <option value="Revoked">🟠 Revoked</option>
                        <option value="Denied">🔴 Denied</option>
                    </select>
                </div>
            </div>

            <!-- Clients Table -->
            <div class="table-container">
                <table class="clients-table" id="clientsTable">
                    <thead>
                        <tr>
                            <th>Docket #</th>
                            <th>Name</th>
                            <th>Offense</th>
                            <th>Court</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($clients) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($clients)): ?>
                            <tr class="client-row">
                                <td><strong><?php echo htmlspecialchars($row['docket_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars(substr($row['offense'], 0, 30)) . (strlen($row['offense']) > 30 ? '...' : ''); ?></td>
                                <td><?php echo htmlspecialchars($row['court']); ?></td>
                                <td><?php echo htmlspecialchars($row['address']); ?></td>
                                <td>
                                    <?php
                                    // Define status icons and ensure we handle any status value
                                    $status_icons = [
                                        'Active' => '🟢',
                                        'Terminated' => '🔵',
                                        'Revoked' => '🟠',
                                        'Denied' => '🔴'
                                    ];
                                    
                                    // Get the status value
                                    $current_status = $row['status'];
                                    
                                    // Check if status exists in our icons array, if not use a default icon
                                    $status_icon = isset($status_icons[$current_status]) ? $status_icons[$current_status] : '⚪';
                                    
                                    // Determine the CSS class - use a default class if status not recognized
                                    $status_class = in_array($current_status, ['Active', 'Terminated', 'Revoked', 'Denied']) 
                                        ? 'status-' . $current_status 
                                        : 'status-default';
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $status_icon . ' ' . htmlspecialchars($current_status); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_probationer.php?id=<?php echo $row['id']; ?>" class="action-link" title="Edit Client">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="view_reports.php?id=<?php echo $row['id']; ?>" class="action-link camera" title="View Reports">
                                            <i class="fas fa-camera"></i>
                                        </a>
                                        <?php if($user_role == 'main' || $user_role == 'admin'): ?>
                                        <a href="clients.php?delete=<?php echo $row['id']; ?>" class="action-link delete" title="Delete Client" onclick="return confirm('Are you sure you want to delete this client? This action cannot be undone.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <h3>No Clients Found</h3>
                                    <p>Get started by adding your first client to the system.</p>
                                    <a href="add_probationer.php" class="add-first-btn">
                                        <i class="fas fa-plus-circle"></i>
                                        Add Your First Client
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Results Count -->
            <div class="results-count">
                <i class="fas fa-users"></i>
                <span id="resultsCount">Showing <?php echo mysqli_num_rows($clients); ?> clients</span>
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

            // Live Search Functionality
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const rows = document.querySelectorAll('.client-row');
            const resultsCountSpan = document.getElementById('resultsCount');

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const filterValue = statusFilter.value;
                let visibleCount = 0;

                rows.forEach(row => {
                    const docket = row.cells[0].textContent.toLowerCase();
                    const name = row.cells[1].textContent.toLowerCase();
                    const offense = row.cells[2].textContent.toLowerCase();
                    const court = row.cells[3].textContent.toLowerCase();
                    const address = row.cells[4].textContent.toLowerCase();
                    const status = row.cells[5].textContent.trim().replace(/[🟢🔵🟠🔴⚪]/g, '').trim();

                    const matchesSearch = searchTerm === '' || 
                        docket.includes(searchTerm) || 
                        name.includes(searchTerm) || 
                        offense.includes(searchTerm) || 
                        court.includes(searchTerm) || 
                        address.includes(searchTerm);

                    const matchesFilter = filterValue === 'all' || status === filterValue;

                    if (matchesSearch && matchesFilter) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                resultsCountSpan.textContent = `Showing ${visibleCount} of ${rows.length} clients`;
            }

            if (searchInput && statusFilter) {
                searchInput.addEventListener('input', filterTable);
                statusFilter.addEventListener('change', filterTable);
            }

            // Initial count
            if (typeof filterTable === 'function') {
                filterTable();
            }
        });
    </script>
</body>
</html>