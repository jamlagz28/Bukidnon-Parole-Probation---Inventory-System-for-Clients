<?php
session_start();
include 'includes/config.php';

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
    <title>Bukidnon PPA | Staff Management System</title>
    
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
        }

        .role-indicator {
            background: <?php echo $current_role == 'main' ? 'linear-gradient(135deg, var(--primary-dark), var(--primary))' : ($current_role == 'admin' ? 'linear-gradient(135deg, #3b82f6, #2563eb)' : 'linear-gradient(135deg, var(--accent-yellow), #f59e0b)'); ?>;
            color: white;
            padding: 0.45rem 1.2rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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

        /* Messages */
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

        .message.error {
            background: linear-gradient(135deg, var(--accent-red-light), #fee2e2);
            color: #991b1b;
            border-left-color: var(--accent-red);
        }

        body.dark-mode .message.success {
            background: #064e3b;
            color: #a7f3d0;
        }

        body.dark-mode .message.error {
            background: #4a1e1e;
            color: #fecaca;
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

        /* Permission Notice */
        .permission-notice {
            background: var(--neutral-white);
            border: 1px solid var(--neutral-border);
            border-radius: var(--border-radius);
            padding: 1.8rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }

        body.dark-mode .permission-notice {
            background: var(--card-bg);
        }

        .permission-notice::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(180deg, var(--accent-yellow), var(--primary));
        }

        .permission-notice i {
            font-size: 2.5rem;
            color: var(--accent-yellow);
            background: var(--accent-yellow-light);
            padding: 1rem;
            border-radius: 60px;
            box-shadow: 0 8px 20px rgba(251,191,36,0.2);
        }

        body.dark-mode .permission-notice i {
            background: #4a3e1a;
        }

        .permission-notice-content {
            flex: 1;
        }

        .permission-notice h4 {
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
            font-weight: 700;
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

        .stat-icon.total { 
            background: linear-gradient(135deg, var(--primary-light), #d1fae5);
            color: var(--primary-dark);
            box-shadow: 0 8px 16px rgba(46,107,94,0.15);
        }
        .stat-icon.main { 
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: white;
            box-shadow: 0 8px 16px rgba(30,74,61,0.25);
        }
        .stat-icon.admin { 
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
            box-shadow: 0 8px 16px rgba(59,130,246,0.15);
        }
        .stat-icon.staff { 
            background: linear-gradient(135deg, var(--accent-yellow-light), #fef9c3);
            color: #b45309;
            box-shadow: 0 8px 16px rgba(245,158,11,0.15);
        }

        body.dark-mode .stat-icon.total { background: #064e3b; color: #34d399; }
        body.dark-mode .stat-icon.main { background: #0f172a; color: #fbbf24; }
        body.dark-mode .stat-icon.admin { background: #1e3a8a; color: #60a5fa; }
        body.dark-mode .stat-icon.staff { background: #4a3e1a; color: #fcd34d; }

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
            color: var(--primary-dark);
        }

        body.dark-mode .stat-value {
            color: var(--accent-yellow);
        }

        /* Add Section */
        .add-section {
            background: var(--neutral-white);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--neutral-border);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }

        body.dark-mode .add-section {
            background: var(--card-bg);
        }

        .add-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--accent-yellow));
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }

        body.dark-mode .section-title {
            color: var(--accent-yellow);
        }

        .section-title i {
            color: var(--accent-yellow);
            font-size: 1.3rem;
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
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-secondary);
            margin-bottom: 0.6rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
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
            padding: 0.85rem 1rem;
            border: 2px solid var(--neutral-border);
            border-radius: 14px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
            background: var(--neutral-light);
            color: var(--text-primary);
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--neutral-white);
            box-shadow: 0 0 0 4px rgba(46,107,94,0.15);
        }

        input::placeholder {
            color: var(--text-muted);
        }

        .btn {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: white;
            padding: 0.85rem 2rem;
            border: none;
            border-radius: 50px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            box-shadow: 0 4px 12px rgba(30,74,61,0.3);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 20px -8px rgba(30,74,61,0.4);
            filter: brightness(1.02);
        }

        .btn:active {
            transform: translateY(0);
        }

        /* Table Container */
        .table-container {
            background: var(--neutral-white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--neutral-border);
            margin-bottom: 2rem;
            transition: var(--transition);
        }

        body.dark-mode .table-container {
            background: var(--card-bg);
        }

        /* Modern Table Design */
        .staff-table {
            width: 100%;
            border-collapse: collapse;
        }

        .staff-table thead tr {
            background: linear-gradient(90deg, var(--neutral-light), var(--neutral-white));
        }

        body.dark-mode .staff-table thead tr {
            background: var(--table-header-bg);
        }

        .staff-table th {
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

        .staff-table td {
            padding: 1.2rem 1.5rem;
            color: var(--text-primary);
            font-size: 0.95rem;
            border-bottom: 1px solid var(--neutral-border);
            transition: var(--transition);
        }

        .staff-table tbody tr {
            transition: var(--transition);
        }

        .staff-table tbody tr:hover {
            background: var(--hover-bg);
            transform: scale(1.01);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .staff-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Current User Highlight */
        .current-user {
            background: linear-gradient(90deg, rgba(46,107,94,0.05), rgba(251,191,36,0.05));
            position: relative;
            border-left: 4px solid var(--accent-yellow);
        }

        .current-user td:first-child {
            padding-left: calc(1.5rem - 4px);
        }

        .current-user:hover {
            background: linear-gradient(90deg, rgba(46,107,94,0.1), rgba(251,191,36,0.1));
        }

        .you-badge {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            font-size: 0.7rem;
            padding: 0.25rem 0.7rem;
            border-radius: 30px;
            margin-left: 0.6rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        /* Role Badges */
        .role-badge {
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

        .role-badge:hover {
            transform: scale(1.02);
        }

        .role-main {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .role-admin {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .role-staff {
            background: linear-gradient(135deg, var(--accent-yellow-light), #fef9c3);
            color: #b45309;
            border: 1px solid #fde68a;
        }

        body.dark-mode .role-main { background: #064e3b; color: #86efac; }
        body.dark-mode .role-admin { background: #1e3a8a; color: #93c5fd; border-color: #2563eb; }
        body.dark-mode .role-staff { background: #4a3e1a; color: #fcd34d; border-color: #854d0e; }

        /* Access Level Badges */
        .access-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.9rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            transition: var(--transition);
        }

        .access-full {
            background: linear-gradient(135deg, #ecfdf3, #d1fae5);
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .access-view {
            background: linear-gradient(135deg, var(--accent-yellow-light), #fef9c3);
            color: #b45309;
            border: 1px solid #fde68a;
        }

        body.dark-mode .access-full { background: #064e3b; color: #86efac; border-color: #065f46; }
        body.dark-mode .access-view { background: #4a3e1a; color: #fcd34d; border-color: #854d0e; }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            align-items: center;
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
            width: 38px;
            height: 38px;
            background: var(--neutral-light);
        }

        .action-link:hover {
            transform: translateY(-3px);
        }

        .action-link.delete:hover {
            background: linear-gradient(135deg, var(--accent-red-light), #fee2e2);
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
            gap: 0.4rem;
            color: var(--text-muted);
            font-size: 0.85rem;
            padding: 0.35rem 0.9rem;
            background: var(--neutral-light);
            border-radius: 30px;
            font-weight: 500;
        }

        .role-select {
            padding: 0.5rem 0.8rem;
            border: 2px solid var(--neutral-border);
            border-radius: 40px;
            font-size: 0.8rem;
            background: var(--neutral-light);
            color: var(--text-primary);
            cursor: pointer;
            transition: var(--transition);
            min-width: 110px;
            font-weight: 500;
        }

        .role-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(46,107,94,0.15);
        }

        .role-select:disabled {
            background: var(--neutral-light);
            color: var(--text-muted);
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
            font-size: 0.85rem;
            padding: 1rem 1.5rem;
            background: var(--neutral-white);
            border-radius: var(--border-radius);
            border: 1px solid var(--neutral-border);
            flex-wrap: wrap;
            transition: var(--transition);
        }

        body.dark-mode .staff-summary {
            background: var(--card-bg);
        }

        .summary-item {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .summary-item i {
            color: var(--accent-yellow);
            font-size: 0.9rem;
        }

        .summary-badge {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }

        .badge-main { background: linear-gradient(135deg, var(--primary-dark), var(--primary)); }
        .badge-admin { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .badge-staff { background: linear-gradient(135deg, var(--accent-yellow), #f59e0b); }

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
            
            .staff-table th,
            .staff-table td {
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
                padding: 1.5rem;
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
                padding: 0.9rem 1rem;
                font-size: 0.9rem;
            }
            
            .role-badge {
                padding: 0.35rem 0.9rem;
                font-size: 0.75rem;
            }
            
            .action-link {
                width: 34px;
                height: 34px;
                font-size: 0.9rem;
            }
            
            .role-select {
                min-width: 90px;
                padding: 0.4rem 0.6rem;
                font-size: 0.75rem;
            }
            
            .staff-summary {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
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
            
            .role-indicator {
                padding: 0.35rem 0.9rem;
                font-size: 0.7rem;
            }
            
            .stat-card {
                padding: 1rem;
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
            
            .section-title {
                font-size: 1rem;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .action-buttons {
                gap: 0.4rem;
            }
            
            .action-link {
                width: 30px;
                height: 30px;
                font-size: 0.85rem;
            }
            
            .role-select {
                min-width: 80px;
                font-size: 0.7rem;
            }
            
            .current-user-indicator {
                font-size: 0.75rem;
                padding: 0.25rem 0.6rem;
            }
            
            .you-badge {
                font-size: 0.6rem;
                padding: 0.2rem 0.5rem;
            }
        }

        @media print {
            .sidebar, .menu-toggle, .sidebar-overlay, .add-section,
            .action-link, .logout-btn, .role-select, .btn, .dark-mode-toggle {
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
            
            .role-badge, .access-badge {
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
                <span>Bukidnon PPA</span>
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
                        <i class="fas fa-<?php echo $current_role == 'main' ? 'crown' : ($current_role == 'admin' ? 'shield-alt' : 'eye'); ?>"></i>
                        <?php echo ucfirst($current_role); ?>
                    </span>
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
                            <input type="text" name="fullname" placeholder="e.g., Juan Dela Cruz" required>
                        </div>
                        <div class="form-group">
                            <label>
                                <i class="fas fa-at"></i>
                                Username <span class="required-star">*</span>
                            </label>
                            <input type="text" name="username" placeholder="e.g., juan.dela.cruz" required>
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
                                    <div class="avatar" style="width: 38px; height: 38px; margin-right: 0.9rem; background: <?php echo $is_current ? 'linear-gradient(135deg, var(--primary), var(--primary-dark))' : 'linear-gradient(135deg, #f1f5f9, #e2e8f0)'; ?>; color: <?php echo $is_current ? 'white' : 'var(--text-secondary)'; ?>;">
                                        <i class="fas fa-user" style="font-size: 1rem;"></i>
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
                                            <select name="new_role" class="role-select" onchange="this.form.submit()">
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
                            <td colspan="<?php echo $can_manage_staff ? '5' : '4'; ?>" style="text-align: center; padding: 3rem;">
                                <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.5; color: var(--text-muted);"></i>
                                <p style="font-size: 1.1rem; color: var(--text-primary);">No staff members found.</p>
                                <?php if($can_manage_staff): ?>
                                    <p style="margin-top: 0.5rem; color: var(--text-muted);">Add your first staff member using the form above.</p>
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
                    <i class="fas fa-<?php echo $current_role == 'main' ? 'crown' : ($current_role == 'admin' ? 'shield-alt' : 'eye'); ?>"></i>
                    Your Role: <strong><?php echo ucfirst($current_role); ?></strong> - 
                    <?php echo $can_manage_staff ? 'Full Access' : 'View Only'; ?>
                </span>
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
