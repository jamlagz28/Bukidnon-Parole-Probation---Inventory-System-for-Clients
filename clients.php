<?php
session_start();
include 'includes/config.php';

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
    <title>Bukidnon PPA | Client Management System</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-dark: #1e4a3d;
            --primary: #2e6b5e;
            --primary-light: #d1fae5;
            --accent-yellow: #fbbf24;
            --accent-yellow-light: #fef3c7;
            --accent-red: #dc2626;
            --accent-red-light: #fee2e2;
            --neutral-white: #ffffff;
            --neutral-light: #f8fafc;
            --neutral-border: #e2e8f0;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --sidebar-width: 280px;
            --sidebar-width-mobile: 260px;
            --header-height: 70px;
            --border-radius: 20px;
            --box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.02);
            --card-shadow: 0 20px 25px -5px rgba(0,0,0,0.05), 0 10px 10px -5px rgba(0,0,0,0.01);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body.dark-mode {
            --primary-dark: #3d8b7a;
            --primary: #4c9e8a;
            --primary-light: #2d5a4a;
            --accent-yellow: #fbbf24;
            --accent-yellow-light: #4a3e1a;
            --accent-red: #f87171;
            --accent-red-light: #4a1e1e;
            --neutral-white: #1e293b;
            --neutral-light: #0f172a;
            --neutral-border: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3);
            --bg-body: #0f172a;
            --card-bg: #1e293b;
            --table-header-bg: #0f172a;
            --hover-bg: #2d3a4e;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f4f8;
            color: var(--text-primary);
            line-height: 1.5;
            overflow-x: hidden;
            transition: background 0.3s ease, color 0.2s ease;
        }

        body.dark-mode {
            background: #0a0f1c;
        }

        /* App Layout */
        .app {
            display: flex;
            min-height: 100vh;
            position: relative;
            width: 100%;
        }

        /* Mobile Menu Toggle - Enhanced */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 101;
            background: var(--primary-dark);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 16px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            border: none;
            font-size: 1.5rem;
            transition: var(--transition);
        }

        .menu-toggle:hover {
            transform: scale(0.96);
            background: var(--primary);
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
            background: rgba(0,0,0,0.6);
            z-index: 99;
            backdrop-filter: blur(5px);
            transition: var(--transition);
        }

        .sidebar-overlay.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Sidebar - Enhanced Dark Green Theme with Gradient */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(165deg, #1e4a3d 0%, #0f3b30 100%);
            padding: 2rem 1.5rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 8px 0 25px -10px rgba(0,0,0,0.15);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), background 0.3s;
            z-index: 100;
        }

        body.dark-mode .sidebar {
            background: linear-gradient(165deg, #0f172a 0%, #0a0f1c 100%);
        }

        .logo {
            font-weight: 800;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 2.5rem;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding-bottom: 1.2rem;
            border-bottom: 2px solid rgba(255,255,255,0.15);
        }

        .logo i {
            color: var(--accent-yellow);
            font-size: 1.8rem;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.9rem 1.2rem;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            border-radius: 14px;
            margin-bottom: 0.5rem;
            transition: var(--transition);
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 0;
            background: rgba(255,255,255,0.1);
            transition: width 0.3s ease;
            z-index: -1;
        }

        .nav-item:hover::before {
            width: 100%;
        }

        .nav-item:hover {
            color: white;
            transform: translateX(6px);
        }

        .nav-item.active {
            background: white;
            color: var(--primary-dark);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        body.dark-mode .nav-item.active {
            background: var(--primary-dark);
            color: white;
        }

        .nav-item i {
            width: 26px;
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

        /* Top Bar - Enhanced with Glassmorphism */
        .top-bar {
            background: var(--neutral-white);
            border-radius: var(--border-radius);
            padding: 1.2rem 2rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--box-shadow);
            border: 1px solid var(--neutral-border);
            backdrop-filter: blur(10px);
            transition: var(--transition);
        }

        body.dark-mode .top-bar {
            background: var(--card-bg);
        }

        .page-title {
            font-size: clamp(1.2rem, 4vw, 1.7rem);
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            position: relative;
            padding-left: 1rem;
            border-left: 5px solid var(--accent-yellow);
        }

        body.dark-mode .page-title {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            background-clip: text;
            -webkit-background-clip: text;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }

        .user-name {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.95rem;
            padding: 0.5rem 0;
        }

        .avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 4px 12px rgba(46,107,94,0.3);
            transition: var(--transition);
        }

        .avatar:hover {
            transform: scale(1.05);
        }

        .logout-btn {
            color: var(--text-muted);
            transition: var(--transition);
            font-size: 1.3rem;
            padding: 0.5rem;
            border-radius: 10px;
        }

        .logout-btn:hover {
            color: var(--accent-red);
            transform: scale(1.1);
            background: var(--accent-red-light);
        }

        /* Dark Mode Toggle Button */
        .dark-mode-toggle {
            background: var(--neutral-light);
            border: 1px solid var(--neutral-border);
            padding: 0.5rem 1rem;
            border-radius: 40px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dark-mode-toggle:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
            transform: translateY(-2px);
        }

        body.dark-mode .dark-mode-toggle {
            background: var(--primary-dark);
            color: var(--accent-yellow);
        }

        /* Stats Grid - Enhanced Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: var(--neutral-white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: 1px solid var(--neutral-border);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        body.dark-mode .stat-card {
            background: var(--card-bg);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--accent-yellow));
        }

        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 25px 35px -12px rgba(0,0,0,0.15);
        }

        .stat-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            transition: var(--transition);
        }

        .stat-icon.active { 
            background: linear-gradient(135deg, #ecfdf3, #d1fae5); 
            color: #059669;
            box-shadow: 0 8px 16px rgba(5,150,105,0.2);
        }
        .stat-icon.terminated { 
            background: linear-gradient(135deg, #e0f2fe, #bae6fd); 
            color: #0284c7;
            box-shadow: 0 8px 16px rgba(2,132,199,0.2);
        }
        .stat-icon.revoked { 
            background: linear-gradient(135deg, var(--accent-yellow-light), #fef9c3); 
            color: #b45309;
            box-shadow: 0 8px 16px rgba(180,83,9,0.2);
        }
        .stat-icon.denied { 
            background: linear-gradient(135deg, var(--accent-red-light), #fee2e2); 
            color: var(--accent-red);
            box-shadow: 0 8px 16px rgba(220,38,38,0.2);
        }

        body.dark-mode .stat-icon.active { 
            background: #064e3b; 
            color: #34d399;
        }
        body.dark-mode .stat-icon.terminated { 
            background: #0c4a6e; 
            color: #38bdf8;
        }
        body.dark-mode .stat-icon.revoked { 
            background: #4a3e1a; 
            color: #fbbf24;
        }
        body.dark-mode .stat-icon.denied { 
            background: #4a1e1e; 
            color: #f87171;
        }

        .stat-content {
            flex: 1;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .stat-value {
            font-size: clamp(1.5rem, 5vw, 2.3rem);
            font-weight: 800;
            line-height: 1.2;
        }

        .stat-value.active { color: #059669; }
        .stat-value.terminated { color: #0284c7; }
        .stat-value.revoked { color: #b45309; }
        .stat-value.denied { color: var(--accent-red); }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--neutral-border);
        }

        .trend-up { color: #10b981; font-weight: 600; }
        .trend-down { color: var(--accent-red); font-weight: 600; }

        /* Message */
        .message {
            padding: 1rem 1.5rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideInDown 0.4s ease;
            font-size: 0.95rem;
            border-left: 5px solid;
            box-shadow: var(--box-shadow);
        }

        .message.success {
            background: linear-gradient(135deg, #ecfdf3, #d1fae5);
            color: #065f46;
            border-left-color: #059669;
        }

        body.dark-mode .message.success {
            background: #064e3b;
            color: #a7f3d0;
        }

        @keyframes slideInDown {
            from {
                transform: translateY(-20px);
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
            background: var(--neutral-white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 1px solid var(--neutral-border);
            transition: var(--transition);
        }

        body.dark-mode .actions-bar {
            background: var(--card-bg);
        }

        .add-btn {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: white;
            padding: 0.85rem 1.8rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(30,74,61,0.3);
        }

        .add-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 20px -8px rgba(30,74,61,0.4);
            filter: brightness(1.05);
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
            font-size: 1rem;
        }

        .search-input {
            padding: 0.85rem 1rem 0.85rem 2.8rem;
            border: 2px solid var(--neutral-border);
            border-radius: 50px;
            width: 300px;
            font-size: 0.95rem;
            transition: var(--transition);
            background: var(--neutral-light);
            color: var(--text-primary);
        }

        .search-input:focus {
            border-color: var(--primary);
            outline: none;
            background: var(--neutral-white);
            box-shadow: 0 0 0 4px rgba(46,107,94,0.15);
            width: 340px;
        }

        .filter-select {
            padding: 0.85rem 1.2rem;
            border: 2px solid var(--neutral-border);
            border-radius: 50px;
            background: var(--neutral-light);
            color: var(--text-primary);
            font-size: 0.95rem;
            cursor: pointer;
            transition: var(--transition);
            min-width: 160px;
            font-weight: 500;
        }

        .filter-select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(46,107,94,0.15);
        }

        /* Table Container */
        .table-container {
            background: var(--neutral-white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--neutral-border);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }

        body.dark-mode .table-container {
            background: var(--card-bg);
        }

        /* Modern Table Design */
        .clients-table {
            width: 100%;
            border-collapse: collapse;
        }

        .clients-table thead tr {
            background: linear-gradient(90deg, var(--neutral-light), var(--neutral-white));
        }

        body.dark-mode .clients-table thead tr {
            background: var(--table-header-bg);
        }

        .clients-table th {
            text-align: left;
            padding: 1.3rem 1.5rem;
            color: var(--text-secondary);
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 2px solid var(--neutral-border);
            white-space: nowrap;
        }

        .clients-table td {
            padding: 1.2rem 1.5rem;
            color: var(--text-primary);
            font-size: 0.95rem;
            border-bottom: 1px solid var(--neutral-border);
            transition: var(--transition);
        }

        .clients-table tbody tr {
            transition: var(--transition);
        }

        .clients-table tbody tr:hover {
            background: var(--hover-bg);
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
            gap: 0.5rem;
            padding: 0.45rem 1.1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.3px;
            white-space: nowrap;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            transition: var(--transition);
        }

        .status-badge:hover {
            transform: scale(1.02);
        }

        .status-Active {
            background: linear-gradient(135deg, #ecfdf3, #d1fae5);
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .status-Terminated {
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            color: #0284c7;
            border: 1px solid #bae6fd;
        }

        .status-Revoked {
            background: linear-gradient(135deg, var(--accent-yellow-light), #fef9c3);
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .status-Denied {
            background: linear-gradient(135deg, var(--accent-red-light), #fee2e2);
            color: var(--accent-red);
            border: 1px solid #fecaca;
        }

        .status-default {
            background: var(--neutral-light);
            color: var(--text-secondary);
            border: 1px solid var(--neutral-border);
        }

        body.dark-mode .status-Active { background: #064e3b; color: #86efac; border-color: #065f46; }
        body.dark-mode .status-Terminated { background: #0c4a6e; color: #7dd3fc; border-color: #075985; }
        body.dark-mode .status-Revoked { background: #4a3e1a; color: #fcd34d; border-color: #854d0e; }
        body.dark-mode .status-Denied { background: #4a1e1e; color: #fca5a5; border-color: #991b1b; }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
        }

        .action-link {
            color: var(--text-muted);
            text-decoration: none;
            padding: 0.5rem;
            border-radius: 10px;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: var(--neutral-light);
        }

        .action-link:hover {
            transform: translateY(-3px);
        }

        .action-link.edit:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
        }

        .action-link.delete:hover {
            background: var(--accent-red-light);
            color: var(--accent-red);
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
            gap: 0.6rem;
            padding: 0.8rem 0;
            font-weight: 500;
        }

        .results-count i {
            color: var(--accent-yellow);
            font-size: 1rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-state i {
            font-size: 5rem;
            color: var(--text-muted);
            margin-bottom: 1.2rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            font-size: 1.3rem;
        }

        .empty-state p {
            color: var(--text-muted);
            margin-bottom: 1.8rem;
        }

        .empty-state .add-first-btn {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: white;
            padding: 0.9rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            transition: var(--transition);
            font-weight: 600;
        }

        .empty-state .add-first-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 20px -8px rgba(30,74,61,0.4);
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #e2e8f0;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        body.dark-mode ::-webkit-scrollbar-track {
            background: #1e293b;
        }

        /* ============ RESPONSIVE BREAKPOINTS ============ */

        @media (min-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 1199px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .clients-table th,
            .clients-table td {
                padding: 1rem;
            }
        }

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
            
            .search-input:focus {
                width: 100%;
            }
        }

        @media (max-width: 767px) {
            .main {
                padding: 1rem;
                padding-top: calc(var(--header-height) + 1rem);
            }
            
            .top-bar {
                padding: 1rem 1.25rem;
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .user-name {
                display: none;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
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
            
            .add-btn {
                justify-content: center;
            }
            
            .clients-table th,
            .clients-table td {
                padding: 0.9rem 1rem;
                font-size: 0.9rem;
            }
            
            .status-badge {
                padding: 0.4rem 0.9rem;
                font-size: 0.75rem;
            }
            
            .action-link {
                width: 32px;
                height: 32px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 575px) {
            .main {
                padding: 0.75rem;
                padding-top: calc(var(--header-height) + 1rem);
            }
            
            .top-bar {
                padding: 0.9rem 1rem;
            }
            
            .page-title {
                font-size: 1.2rem;
                padding-left: 0.75rem;
            }
            
            .avatar {
                width: 40px;
                height: 40px;
            }
            
            .stat-icon {
                width: 48px;
                height: 48px;
                font-size: 1.3rem;
            }
            
            .stat-value {
                font-size: 1.6rem;
            }
            
            .message {
                padding: 0.9rem 1.2rem;
                font-size: 0.9rem;
            }
        }

        @media print {
            .sidebar, .menu-toggle, .sidebar-overlay, .actions-bar,
            .add-btn, .search-box, .logout-btn, .action-link, .dark-mode-toggle {
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
                <span>Bukidnon PPA</span>
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
                    <button id="darkModeToggle" class="dark-mode-toggle">
                        <i class="fas fa-moon"></i>
                        <span>Dark</span>
                    </button>
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
                        <span>+12% from last month</span>
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
                        <span>-5% from last month</span>
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
                        <span>+3% from last month</span>
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
                            <tr class="client-row" data-status="<?php echo htmlspecialchars($row['status']); ?>">
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
                                        <a href="edit_probationer.php?id=<?php echo $row['id']; ?>" class="action-link edit" title="Edit Client">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="view_reports.php?id=<?php echo $row['id']; ?>" class="action-link camera" title="View Reports">
                                            <i class="fas fa-camera"></i>
                                        </a>
                                        <?php if($user_role == 'main' || $user_role == 'admin'): ?>
                                        <a href="clients.php?delete=<?php echo $row['id']; ?>" class="action-link delete" title="Delete Client" onclick="return confirm('⚠️ Are you sure you want to delete this client? This action cannot be undone.')">
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
        // Dark Mode Toggle Functionality
        const darkModeToggle = document.getElementById('darkModeToggle');
        let darkMode = localStorage.getItem('darkMode');
        
        if (darkMode === 'enabled') {
            document.body.classList.add('dark-mode');
            updateDarkModeButton(true);
        } else {
            updateDarkModeButton(false);
        }
        
        function updateDarkModeButton(isDark) {
            if (!darkModeToggle) return;
            const icon = darkModeToggle.querySelector('i');
            const span = darkModeToggle.querySelector('span');
            if (isDark) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
                span.textContent = 'Light';
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
                span.textContent = 'Dark';
            }
        }
        
        if (darkModeToggle) {
            darkModeToggle.addEventListener('click', () => {
                const isDark = document.body.classList.toggle('dark-mode');
                if (isDark) {
                    localStorage.setItem('darkMode', 'enabled');
                    updateDarkModeButton(true);
                } else {
                    localStorage.setItem('darkMode', 'disabled');
                    updateDarkModeButton(false);
                }
            });
        }
        
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
                    if (icon) {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                });
            }
            
            // Close sidebar on window resize if in desktop mode
            window.addEventListener('resize', function() {
                if (window.innerWidth > 991) {
                    if (sidebar) sidebar.classList.remove('active');
                    if (overlay) overlay.classList.remove('active');
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
            let totalRows = rows.length;

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const filterValue = statusFilter.value;
                let visibleCount = 0;

                rows.forEach(row => {
                    const docket = row.cells[0]?.textContent.toLowerCase() || '';
                    const name = row.cells[1]?.textContent.toLowerCase() || '';
                    const offense = row.cells[2]?.textContent.toLowerCase() || '';
                    const court = row.cells[3]?.textContent.toLowerCase() || '';
                    const address = row.cells[4]?.textContent.toLowerCase() || '';
                    const status = row.getAttribute('data-status') || row.cells[5]?.textContent.replace(/[🟢🔵🟠🔴⚪]/g, '').trim() || '';

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

                if (resultsCountSpan) {
                    resultsCountSpan.textContent = `Showing ${visibleCount} of ${totalRows} clients`;
                }
            }

            if (searchInput && statusFilter) {
                searchInput.addEventListener('input', filterTable);
                statusFilter.addEventListener('change', filterTable);
            }

            // Initial filter call to set correct count
            if (typeof filterTable === 'function') {
                filterTable();
            }
        });
    </script>
</body>
</html>
