<?php
session_start();
include 'config/database.php';

// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Get current user info from session
$current_username = $_SESSION['username'];
$current_fullname = $_SESSION['fullname'] ?? 'User';
$current_role = $_SESSION['role'] ?? 'staff';

// Get current user ID from database
$user_query = mysqli_query($conn, "SELECT id FROM staff WHERE username='$current_username'");
$current_user = mysqli_fetch_assoc($user_query);
$current_user_id = $current_user['id'] ?? 0;

// Check if user has permission to manage staff (only admin/main)
$can_manage_staff = ($current_role == 'main' || $current_role == 'admin');

// Handle add staff (only for admin/main)
if(isset($_POST['add_staff']) && $can_manage_staff) {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    // Check if username exists
    $check = mysqli_query($conn, "SELECT id FROM staff WHERE username='$username'");
    if(mysqli_num_rows($check) > 0) {
        $error = "Username already exists!";
    } else {
        $query = "INSERT INTO staff (fullname, username, password, role) VALUES ('$fullname', '$username', '$password', '$role')";
        if(mysqli_query($conn, $query)) {
            $success = "Staff added successfully!";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

// Handle delete staff (only for admin/main)
if(isset($_GET['delete']) && $can_manage_staff) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    // Don't allow deleting yourself
    if($id != $current_user_id) {
        mysqli_query($conn, "DELETE FROM staff WHERE id='$id'");
        $success = "Staff deleted successfully!";
    }
    header("Location: staff_management.php");
    exit();
}

// Handle role update (only for admin/main)
if(isset($_POST['update_role']) && $can_manage_staff) {
    $staff_id = $_POST['staff_id'];
    $new_role = $_POST['new_role'];
    
    // Don't allow changing your own role
    if($staff_id != $current_user_id) {
        mysqli_query($conn, "UPDATE staff SET role='$new_role' WHERE id='$staff_id'");
        $success = "Role updated successfully!";
    }
    header("Location: staff_management.php");
    exit();
}

// Get all staff
$staff = mysqli_query($conn, "SELECT * FROM staff ORDER BY 
    CASE role 
        WHEN 'main' THEN 1 
        WHEN 'admin' THEN 2 
        ELSE 3 
    END, fullname ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
    <title>Staff Management - PPA System</title>
    
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

        .role-indicator {
            background: <?php echo $current_role == 'main' ? 'var(--primary-dark)' : ($current_role == 'admin' ? '#3b82f6' : 'var(--accent-yellow)'); ?>;
            color: <?php echo $current_role == 'staff' ? '#b45309' : 'white'; ?>;
            padding: 0.35rem 1rem;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid <?php echo $current_role == 'staff' ? '#fde68a' : 'transparent'; ?>;
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

        /* Permission Notice */
        .permission-notice {
            background: white;
            border: 1px solid var(--neutral-border);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }

        .permission-notice::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--accent-yellow);
        }

        .permission-notice i {
            font-size: 2rem;
            color: var(--accent-yellow);
            background: var(--accent-yellow-light);
            padding: 1rem;
            border-radius: 50%;
        }

        .permission-notice-content {
            flex: 1;
        }

        .permission-notice h4 {
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            font-size: 1rem;
        }

        .permission-notice p {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Stats Cards */
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

        .stat-icon.main { 
            background: var(--primary-dark); 
            color: white;
        }
        .stat-icon.admin { 
            background: #dbeafe; 
            color: #1e40af;
        }
        .stat-icon.staff { 
            background: var(--accent-yellow-light); 
            color: #b45309;
        }
        .stat-icon.total { 
            background: var(--primary-light); 
            color: var(--primary-dark);
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
            color: var(--primary-dark);
        }

        /* Add Section */
        .add-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--neutral-border);
            position: relative;
            overflow: hidden;
        }

        .add-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent-yellow));
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--accent-yellow);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        label i {
            color: var(--accent-yellow);
            font-size: 0.9rem;
        }

        .required-star {
            color: var(--accent-red);
            margin-left: 0.25rem;
        }

        input, select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--neutral-border);
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
            background: #f8fafc;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(46,107,94,0.1);
        }

        input::placeholder {
            color: #94a3b8;
        }

        .btn {
            background: var(--primary-dark);
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 6px -2px rgba(30,74,61,0.2);
        }

        .btn:hover {
            background: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(30,74,61,0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-small {
            padding: 0.4rem 1rem;
            font-size: 0.85rem;
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
        .staff-table {
            width: 100%;
            border-collapse: collapse;
        }

        .staff-table thead tr {
            background: linear-gradient(90deg, #f8fafc, white);
        }

        .staff-table th {
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

        .staff-table td {
            padding: 1.25rem 1.5rem;
            color: var(--text-primary);
            font-size: 0.95rem;
            border-bottom: 1px solid var(--neutral-border);
            transition: background 0.2s;
        }

        .staff-table tbody tr {
            transition: all 0.2s;
        }

        .staff-table tbody tr:hover {
            background: #faf9fe;
        }

        .staff-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Current User Highlight */
        .current-user {
            background: #f0f9ff;
            position: relative;
        }

        .current-user td {
            border-left: 4px solid var(--primary);
        }

        .current-user:hover {
            background: #e6f2ff;
        }

        .you-badge {
            background: var(--primary);
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            margin-left: 0.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Role Badges */
        .role-badge {
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

        .role-main {
            background: var(--primary-dark);
            color: white;
            border: 1px solid var(--primary);
        }

        .role-admin {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .role-staff {
            background: var(--accent-yellow-light);
            color: #b45309;
            border: 1px solid #fde68a;
        }

        /* Access Level Badges */
        .access-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.3rem 0.8rem;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .access-full {
            background: #ecfdf3;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .access-view {
            background: var(--accent-yellow-light);
            color: #b45309;
            border: 1px solid #fde68a;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            align-items: center;
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

        .action-link.disabled {
            color: #cbd5e1;
            background: #f1f5f9;
            pointer-events: none;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .current-user-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            color: var(--text-muted);
            font-size: 0.85rem;
            padding: 0.25rem 0.75rem;
            background: #f1f5f9;
            border-radius: 20px;
        }

        .role-select {
            padding: 0.5rem;
            border: 1px solid var(--neutral-border);
            border-radius: 8px;
            font-size: 0.85rem;
            background: white;
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.2s;
            min-width: 100px;
        }

        .role-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(46,107,94,0.1);
        }

        .role-select:disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
            border-color: var(--neutral-border);
        }

        /* Staff Summary */
        .staff-summary {
            margin-top: 1.5rem;
            display: flex;
            gap: 1.5rem;
            justify-content: flex-end;
            align-items: center;
            color: var(--text-muted);
            font-size: 0.9rem;
            padding: 1rem;
            background: white;
            border-radius: var(--border-radius);
            border: 1px solid var(--neutral-border);
            flex-wrap: wrap;
        }

        .summary-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .summary-item i {
            color: var(--accent-yellow);
        }

        .summary-badge {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .badge-main { background: var(--primary-dark); }
        .badge-admin { background: #3b82f6; }
        .badge-staff { background: var(--accent-yellow); }

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
            
            .staff-table th,
            .staff-table td {
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
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .staff-table {
                min-width: 800px;
            }
            
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
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
            
            .permission-notice {
                flex-direction: column;
                text-align: center;
                padding: 1.25rem;
                gap: 1rem;
            }
            
            .permission-notice::before {
                width: 100%;
                height: 4px;
                top: 0;
                left: 0;
            }
            
            .add-section {
                padding: 1.5rem;
            }
            
            .staff-table th,
            .staff-table td {
                padding: 0.875rem 1rem;
                font-size: 0.9rem;
            }
            
            .role-badge {
                padding: 0.3rem 0.8rem;
                font-size: 0.75rem;
            }
            
            .action-link {
                width: 32px;
                height: 32px;
                font-size: 0.9rem;
            }
            
            .role-select {
                min-width: 80px;
                padding: 0.35rem;
                font-size: 0.8rem;
            }
            
            .staff-summary {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
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
            
            .role-indicator {
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
            
            .section-title {
                font-size: 1rem;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .action-buttons {
                gap: 0.25rem;
            }
            
            .action-link {
                width: 28px;
                height: 28px;
                font-size: 0.85rem;
            }
            
            .role-select {
                min-width: 70px;
                font-size: 0.75rem;
            }
            
            .current-user-indicator {
                font-size: 0.75rem;
                padding: 0.2rem 0.5rem;
            }
            
            .you-badge {
                font-size: 0.65rem;
                padding: 0.15rem 0.4rem;
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
            
            .staff-table th,
            .staff-table td {
                padding: 0.75rem;
                font-size: 0.85rem;
            }
            
            .role-badge {
                padding: 0.2rem 0.6rem;
                font-size: 0.7rem;
            }
            
            .access-badge {
                padding: 0.2rem 0.5rem;
                font-size: 0.65rem;
            }
            
            .action-link {
                width: 26px;
                height: 26px;
                font-size: 0.8rem;
            }
        }

        /* Print Styles */
        @media print {
            .sidebar, .menu-toggle, .sidebar-overlay, .add-section,
            .action-link, .logout-btn, .role-select, .btn {
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
            
            .staff-table {
                border: 1px solid #000;
            }
            
            .role-badge {
                border: 1px solid #000;
                background: none !important;
                color: #000 !important;
            }
            
            .current-user {
                background: #f0f0f0;
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
                <!-- PI Cases and PS Cases links removed for all users -->
                <a href="monthly_reports.php" class="nav-item">
                    <i class="fas fa-camera"></i>
                    <span>Monthly Reports</span>
                </a>
                <a href="staff_management.php" class="nav-item active">
                    <i class="fas fa-user-tie"></i>
                    <span>Staff</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1 class="page-title">Staff Management</h1>
                <div class="user-menu">
                    <span class="user-name"><?php echo htmlspecialchars($current_fullname); ?></span>
                    <span class="role-indicator">
                        <i class="fas fa-<?php echo $current_role == 'main' ? 'crown' : ($current_role == 'admin' ? 'shield' : 'eye'); ?>"></i>
                        <?php echo ucfirst($current_role); ?>
                    </span>
                    <div class="avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <a href="logout.php" class="logout-btn" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>

            <!-- Permission Notice for Staff Users -->
            <?php if(!$can_manage_staff): ?>
            <div class="permission-notice">
                <i class="fas fa-eye"></i>
                <div class="permission-notice-content">
                    <h4>View Only Mode</h4>
                    <p>You are viewing this page as a staff member. You can see the staff list but cannot add, edit, or delete staff members.</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Messages -->
            <?php if(isset($success)): ?>
                <div class="message success">
                    <i class="fas fa-check-circle fa-lg"></i>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle fa-lg"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php
            // Calculate staff counts
            $total_staff = mysqli_num_rows($staff);
            $main_count = 0;
            $admin_count = 0;
            $staff_count = 0;
            
            // Reset staff pointer
            mysqli_data_seek($staff, 0);
            while($row = mysqli_fetch_assoc($staff)) {
                if($row['role'] == 'main') $main_count++;
                else if($row['role'] == 'admin') $admin_count++;
                else $staff_count++;
            }
            
            // Reset staff pointer again for the table
            mysqli_data_seek($staff, 0);
            ?>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon total">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Total Staff</div>
                            <div class="stat-value"><?php echo $total_staff; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon main">
                            <i class="fas fa-crown"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Main Admins</div>
                            <div class="stat-value"><?php echo $main_count; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon admin">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Admins</div>
                            <div class="stat-value"><?php echo $admin_count; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon staff">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Staff</div>
                            <div class="stat-value"><?php echo $staff_count; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Staff Form - Only visible to admin/main -->
            <?php if($can_manage_staff): ?>
            <div class="add-section">
                <div class="section-title">
                    <i class="fas fa-user-plus"></i>
                    Add New Staff Member
                </div>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-user"></i>
                                Full Name <span class="required-star">*</span>
                            </label>
                            <input type="text" name="fullname" placeholder="e.g. Juan Dela Cruz" required>
                        </div>
                        <div class="form-group">
                            <label>
                                <i class="fas fa-at"></i>
                                Username <span class="required-star">*</span>
                            </label>
                            <input type="text" name="username" placeholder="e.g. juan.dela.cruz" required>
                        </div>
                        <div class="form-group">
                            <label>
                                <i class="fas fa-lock"></i>
                                Password <span class="required-star">*</span>
                            </label>
                            <input type="password" name="password" placeholder="Enter secure password" required>
                        </div>
                        <div class="form-group">
                            <label>
                                <i class="fas fa-tag"></i>
                                Role <span class="required-star">*</span>
                            </label>
                            <select name="role">
                                <option value="staff">👁️ Staff (View Only)</option>
                                <option value="admin">🛡️ Admin (Full Access)</option>
                                <?php if($current_role == 'main'): ?>
                                <option value="main">👑 Main Admin (Super User)</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_staff" class="btn">
                        <i class="fas fa-plus-circle"></i>
                        Add Staff Member
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Staff List -->
            <div class="table-container">
                <table class="staff-table">
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Access Level</th>
                            <?php if($can_manage_staff): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 0;
                        while($row = mysqli_fetch_assoc($staff)): 
                            $counter++;
                            $is_current = ($row['username'] == $current_username);
                        ?>
                        <tr class="<?php echo $is_current ? 'current-user' : ''; ?>">
                            <td>
                                <div style="display: flex; align-items: center;">
                                    <div class="avatar" style="width: 32px; height: 32px; margin-right: 0.75rem; background: <?php echo $is_current ? 'var(--primary)' : '#f1f5f9'; ?>; color: <?php echo $is_current ? 'white' : 'var(--text-secondary)'; ?>;">
                                        <i class="fas fa-user" style="font-size: 0.9rem;"></i>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($row['fullname']); ?></strong>
                                        <?php if($is_current): ?>
                                            <span class="you-badge">
                                                <i class="fas fa-check-circle"></i> You
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $row['role']; ?>">
                                    <?php 
                                    $role_icons = [
                                        'main' => '👑',
                                        'admin' => '🛡️',
                                        'staff' => '👁️'
                                    ];
                                    echo $role_icons[$row['role']] . ' ' . ucfirst($row['role']); 
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php if($row['role'] == 'main' || $row['role'] == 'admin'): ?>
                                    <span class="access-badge access-full">
                                        <i class="fas fa-check-circle"></i> Full Access
                                    </span>
                                <?php else: ?>
                                    <span class="access-badge access-view">
                                        <i class="fas fa-eye"></i> View Only
                                    </span>
                                <?php endif; ?>
                            </td>
                            <?php if($can_manage_staff): ?>
                            <td>
                                <div class="action-buttons">
                                    <?php if(!$is_current): ?>
                                        <!-- Role change dropdown for admin/main -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
                                            <select name="new_role" class="role-select" onchange="this.form.submit()" style="margin-right: 0.5rem;">
                                                <option value="staff" <?php echo $row['role'] == 'staff' ? 'selected' : ''; ?>>👁️ Staff</option>
                                                <option value="admin" <?php echo $row['role'] == 'admin' ? 'selected' : ''; ?>>🛡️ Admin</option>
                                                <?php if($current_role == 'main'): ?>
                                                <option value="main" <?php echo $row['role'] == 'main' ? 'selected' : ''; ?>>👑 Main</option>
                                                <?php endif; ?>
                                            </select>
                                            <input type="hidden" name="update_role" value="1">
                                        </form>
                                        
                                        <a href="?delete=<?php echo $row['id']; ?>" class="action-link delete" onclick="return confirm('⚠️ Are you sure you want to delete this staff member?\n\nName: <?php echo addslashes($row['fullname']); ?>\nRole: <?php echo $row['role']; ?>\n\nThis action cannot be undone!')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="current-user-indicator">
                                            <i class="fas fa-lock"></i> Current User
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>

                        <?php if($counter == 0): ?>
                        <tr>
                            <td colspan="<?php echo $can_manage_staff ? '5' : '4'; ?>" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                                <p style="font-size: 1.1rem;">No staff members found.</p>
                                <?php if($can_manage_staff): ?>
                                    <p style="margin-top: 0.5rem;">Add your first staff member using the form above.</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Staff Summary -->
            <div class="staff-summary">
                <span class="summary-item">
                    <i class="fas fa-users"></i>
                    Total Staff: <strong><?php echo $counter; ?></strong>
                </span>
                <span class="summary-item">
                    <span class="summary-badge badge-main"></span>
                    Main: <strong><?php echo $main_count; ?></strong>
                </span>
                <span class="summary-item">
                    <span class="summary-badge badge-admin"></span>
                    Admin: <strong><?php echo $admin_count; ?></strong>
                </span>
                <span class="summary-item">
                    <span class="summary-badge badge-staff"></span>
                    Staff: <strong><?php echo $staff_count; ?></strong>
                </span>
                <span class="summary-item">
                    <i class="fas fa-<?php echo $current_role == 'main' ? 'crown' : ($current_role == 'admin' ? 'shield' : 'eye'); ?>"></i>
                    Your Role: <strong><?php echo ucfirst($current_role); ?></strong> - 
                    <?php echo $can_manage_staff ? 'Full Access' : 'View Only'; ?>
                </span>
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

            // Auto-submit role change with confirmation (only for admin/main)
            <?php if($can_manage_staff): ?>
            document.querySelectorAll('.role-select').forEach(select => {
                // Store original value
                select.setAttribute('data-original', select.value);
                
                select.addEventListener('change', function(e) {
                    const selectedOption = this.options[this.selectedIndex];
                    const roleName = selectedOption.text.replace(/[👑🛡️👁️]/g, '').trim();
                    
                    if(confirm(`⚠️ Change role for this staff member to ${roleName}?`)) {
                        this.form.submit();
                    } else {
                        e.preventDefault();
                        this.value = this.getAttribute('data-original');
                    }
                });
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>
