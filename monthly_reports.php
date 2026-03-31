<?php
session_start();
include 'config/database.php';

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'staff';

// Check if user can delete (admin/main only)
$can_delete = ($user_role == 'main' || $user_role == 'admin');

// Handle image deletion
if(isset($_GET['delete']) && $can_delete) {
    $image_id = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // Get image filename first to delete from server
    $img_query = mysqli_query($conn, "SELECT photo FROM monthly_reports WHERE id='$image_id'");
    if(mysqli_num_rows($img_query) > 0) {
        $img = mysqli_fetch_assoc($img_query);
        $filename = $img['photo'];
        
        // Delete from database
        $delete = mysqli_query($conn, "DELETE FROM monthly_reports WHERE id='$image_id'");
        
        if($delete) {
            // Delete physical file from server
            if(file_exists("uploads/" . $filename)) {
                unlink("uploads/" . $filename);
            }
            $success = "Image deleted successfully!";
        } else {
            $error = "Error deleting image: " . mysqli_error($conn);
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: monthly_reports.php?msg=" . ($success ? "deleted" : "error"));
    exit();
}

// Get all reports with client info
$reports = mysqli_query($conn, "
    SELECT mr.*, c.name, c.docket_number, c.status, s.fullname as uploaded_by_name
    FROM monthly_reports mr
    JOIN clients c ON mr.probationer_id = c.id
    LEFT JOIN staff s ON mr.uploaded_by = s.id
    ORDER BY mr.report_year DESC, mr.report_month DESC, mr.upload_date DESC
");

// Get summary stats
$total_reports = mysqli_num_rows($reports);

// Get reports count for current year
$current_year = date('Y');
$reports_this_year = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total FROM monthly_reports 
    WHERE report_year = $current_year
"))['total'];

// Get min and max year from database for accuracy
$year_range_query = mysqli_query($conn, "
    SELECT 
        MIN(report_year) as min_year,
        MAX(report_year) as max_year,
        COUNT(DISTINCT report_year) as year_count
    FROM monthly_reports
");
$year_stats = mysqli_fetch_assoc($year_range_query);
$min_year_db = $year_stats['min_year'] ?? $current_year;
$max_year_db = $year_stats['max_year'] ?? $current_year;
$years_with_data = $year_stats['year_count'] ?? 0;

// Create year array from 2020 to 2030
$start_year = 2020;
$end_year = 2030;
$years = range($start_year, $end_year);

// Get counts per year for display
$year_counts = [];
foreach($years as $year) {
    $count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM monthly_reports WHERE report_year = $year");
    $year_counts[$year] = mysqli_fetch_assoc($count_query)['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
    <title>Bukidnon PPA | Monthly Reports Archive 2020-2030</title>
    
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

        .page-subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }

        .user-name {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.95rem;
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

        .view-only-badge {
            background: linear-gradient(135deg, var(--accent-yellow-light), #fef9c3);
            color: #b45309;
            padding: 0.45rem 1.2rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            border: 1px solid #fde68a;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
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

        /* Stats Grid - Enhanced Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
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

        .stat-icon.green {
            background: linear-gradient(135deg, var(--primary-light), #d1fae5);
            color: var(--primary-dark);
            box-shadow: 0 8px 16px rgba(46,107,94,0.15);
        }

        .stat-icon.yellow {
            background: linear-gradient(135deg, var(--accent-yellow-light), #fef9c3);
            color: #b45309;
            box-shadow: 0 8px 16px rgba(245,158,11,0.15);
        }

        .stat-icon.red {
            background: linear-gradient(135deg, var(--accent-red-light), #fee2e2);
            color: var(--accent-red);
            box-shadow: 0 8px 16px rgba(220,38,38,0.15);
        }

        body.dark-mode .stat-icon.green {
            background: #064e3b;
            color: #34d399;
        }

        body.dark-mode .stat-icon.yellow {
            background: #4a3e1a;
            color: #fbbf24;
        }

        body.dark-mode .stat-icon.red {
            background: #4a1e1e;
            color: #f87171;
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

        .stat-note {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--neutral-border);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Year Range Visualization */
        .year-range-info {
            background: var(--neutral-white);
            padding: 1.25rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
            box-shadow: var(--box-shadow);
            border: 1px solid var(--neutral-border);
            transition: var(--transition);
        }

        body.dark-mode .year-range-info {
            background: var(--card-bg);
        }

        .year-range-info span:first-child {
            color: var(--text-secondary);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .year-stats {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
            flex: 1;
        }

        .year-pill {
            background: var(--neutral-light);
            padding: 0.45rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid var(--neutral-border);
            font-weight: 500;
        }

        .year-pill.has-data {
            background: linear-gradient(135deg, var(--primary-light), #d1fae5);
            color: var(--primary-dark);
            border-color: var(--primary);
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(46,107,94,0.15);
        }

        .year-pill:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .year-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            transition: var(--transition);
        }

        .dot-active {
            background: var(--primary-dark);
            box-shadow: 0 0 0 2px rgba(30,74,61,0.2);
        }

        .dot-inactive {
            background: #cbd5e1;
        }

        body.dark-mode .dot-active {
            background: var(--accent-yellow);
        }

        /* Filter Section */
        .filter-section {
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            background: var(--neutral-white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 1px solid var(--neutral-border);
            transition: var(--transition);
        }

        body.dark-mode .filter-section {
            background: var(--card-bg);
        }

        .search-wrapper {
            position: relative;
            flex: 1;
            min-width: 250px;
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
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.8rem;
            border: 2px solid var(--neutral-border);
            border-radius: 50px;
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
            min-width: 200px;
            font-weight: 500;
        }

        .filter-select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(46,107,94,0.15);
        }

        .filter-info {
            color: var(--text-muted);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            flex-wrap: wrap;
            margin-left: auto;
        }

        .clear-filter {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.85rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 1rem;
            border-radius: 40px;
            background: var(--primary-light);
            transition: var(--transition);
            font-weight: 500;
        }

        .clear-filter:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
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
        .reports-table {
            width: 100%;
            border-collapse: collapse;
        }

        .reports-table thead tr {
            background: linear-gradient(90deg, var(--neutral-light), var(--neutral-white));
        }

        body.dark-mode .reports-table thead tr {
            background: var(--table-header-bg);
        }

        .reports-table th {
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

        .reports-table td {
            padding: 1.2rem 1.5rem;
            color: var(--text-primary);
            font-size: 0.95rem;
            border-bottom: 1px solid var(--neutral-border);
            transition: var(--transition);
        }

        .reports-table tbody tr {
            transition: var(--transition);
        }

        .reports-table tbody tr:hover {
            background: var(--hover-bg);
            transform: scale(1.01);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .reports-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Report Thumbnail */
        .report-thumb {
            width: 65px;
            height: 65px;
            object-fit: cover;
            border-radius: 14px;
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid var(--neutral-border);
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }

        .report-thumb:hover {
            transform: scale(1.8);
            box-shadow: 0 12px 24px rgba(0,0,0,0.2);
            z-index: 10;
            position: relative;
            border-color: var(--primary);
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
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

        body.dark-mode .status-Active { background: #064e3b; color: #86efac; border-color: #065f46; }
        body.dark-mode .status-Terminated { background: #0c4a6e; color: #7dd3fc; border-color: #075985; }
        body.dark-mode .status-Revoked { background: #4a3e1a; color: #fcd34d; border-color: #854d0e; }
        body.dark-mode .status-Denied { background: #4a1e1e; color: #fca5a5; border-color: #991b1b; }

        /* Year Badge */
        .year-badge {
            display: inline-block;
            padding: 0.25rem 0.7rem;
            background: var(--primary-light);
            border-radius: 30px;
            font-size: 0.7rem;
            margin-left: 0.6rem;
            color: var(--primary-dark);
            font-weight: 700;
        }

        body.dark-mode .year-badge {
            background: var(--primary-dark);
            color: var(--accent-yellow);
        }

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
            width: 38px;
            height: 38px;
            background: var(--neutral-light);
        }

        .action-link:hover {
            transform: translateY(-3px);
        }

        .action-link.download:hover {
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            color: #0284c7;
        }

        .action-link.view:hover {
            background: linear-gradient(135deg, var(--primary-light), #d1fae5);
            color: var(--primary-dark);
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

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.96);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(12px);
            transition: var(--transition);
        }

        .modal.active {
            display: flex;
            animation: zoomIn 0.3s ease;
        }

        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .modal img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            border: 4px solid white;
        }

        .modal-close {
            position: absolute;
            top: 1.5rem;
            right: 2rem;
            color: white;
            font-size: 3rem;
            cursor: pointer;
            width: 55px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            transition: var(--transition);
        }

        .modal-close:hover {
            background: var(--accent-red);
            transform: rotate(90deg);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
        }

        .empty-state i {
            font-size: 4.5rem;
            color: var(--text-muted);
            margin-bottom: 1.2rem;
            opacity: 0.5;
        }

        .empty-state p {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }

        .empty-state .btn-primary {
            display: inline-block;
            padding: 0.85rem 2rem;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: var(--transition);
        }

        .empty-state .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 20px -8px rgba(30,74,61,0.4);
        }

        /* Year Summary */
        .year-summary {
            margin-top: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-muted);
            font-size: 0.85rem;
            flex-wrap: wrap;
            gap: 1rem;
            padding: 1rem 1.5rem;
            background: var(--neutral-white);
            border-radius: var(--border-radius);
            border: 1px solid var(--neutral-border);
            transition: var(--transition);
        }

        body.dark-mode .year-summary {
            background: var(--card-bg);
        }

        .year-summary i {
            color: var(--accent-yellow);
        }

        .year-summary span {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
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
            
            .reports-table th,
            .reports-table td {
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
            
            .reports-table {
                min-width: 900px;
            }
            
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-wrapper {
                width: 100%;
            }
            
            .filter-select {
                width: 100%;
            }
            
            .filter-info {
                margin-left: 0;
                justify-content: space-between;
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
            
            .user-info {
                width: 100%;
                justify-content: space-between;
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
            
            .year-range-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
                padding: 1rem;
            }
            
            .year-stats {
                width: 100%;
            }
            
            .filter-section {
                padding: 1.25rem;
            }
            
            .reports-table th,
            .reports-table td {
                padding: 0.9rem 1rem;
                font-size: 0.9rem;
            }
            
            .report-thumb {
                width: 55px;
                height: 55px;
            }
            
            .status-badge {
                padding: 0.35rem 0.9rem;
                font-size: 0.75rem;
            }
            
            .action-link {
                width: 34px;
                height: 34px;
                font-size: 0.9rem;
            }
            
            .year-summary {
                flex-direction: column;
                align-items: flex-start;
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
            
            .page-subtitle {
                font-size: 0.75rem;
            }
            
            .avatar {
                width: 40px;
                height: 40px;
            }
            
            .view-only-badge {
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
            
            .year-pill {
                padding: 0.35rem 0.8rem;
                font-size: 0.7rem;
            }
            
            .report-thumb {
                width: 48px;
                height: 48px;
            }
            
            .modal-close {
                top: 1rem;
                right: 1rem;
                font-size: 2rem;
                width: 45px;
                height: 45px;
            }
        }

        @media print {
            .sidebar, .menu-toggle, .sidebar-overlay, .filter-section,
            .action-link, .modal, .logout-btn, .avatar, .dark-mode-toggle,
            .view-only-badge {
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
            
            .report-thumb {
                max-width: 1in;
                max-height: 1in;
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
                <a href="monthly_reports.php" class="nav-item active">
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
                <div>
                    <h1 class="page-title">Monthly Reports</h1>
                    <div class="page-subtitle">
                        <i class="fas fa-calendar-alt"></i> 
                        Complete decade coverage: 2020 - 2030
                    </div>
                </div>
                <div class="user-info">
                    <?php if(!$can_delete): ?>
                        <span class="view-only-badge">
                            <i class="fas fa-eye"></i> View Only
                        </span>
                    <?php endif; ?>
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

            <!-- Success/Error Messages -->
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="message success">
                    <i class="fas fa-check-circle fa-lg"></i>
                    <span>Image deleted successfully!</span>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'error'): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle fa-lg"></i>
                    <span>Error deleting image.</span>
                </div>
            <?php endif; ?>

            <!-- Stats Cards - Enhanced -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon green">
                            <i class="fas fa-image"></i>
                        </div>
                        <div class="stat-label">
                            <i class="fas fa-chart-bar"></i> Total Reports
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $total_reports; ?></div>
                    <div class="stat-note">
                        <i class="fas fa-calendar-alt"></i> across all years
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon yellow">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-label">
                            <i class="fas fa-calendar-alt"></i> <?php echo $current_year; ?> Reports
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $reports_this_year; ?></div>
                    <div class="stat-note">
                        <i class="fas fa-chart-line"></i> current year
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon green">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-label">
                            <i class="fas fa-calendar-range"></i> Year Range
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $min_year_db; ?> - <?php echo $max_year_db; ?></div>
                    <div class="stat-note">
                        <i class="fas fa-database"></i> years with data
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon red">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div class="stat-label">
                            <i class="fas fa-calendar-alt"></i> Active Years
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $years_with_data; ?></div>
                    <div class="stat-note">
                        <i class="fas fa-calendar-alt"></i> out of 11 years
                    </div>
                </div>
            </div>

            <!-- Year Range Visualization - Enhanced -->
            <div class="year-range-info">
                <span>
                    <i class="fas fa-calendar-alt" style="color: var(--accent-yellow);"></i>
                    Years with reports:
                </span>
                <div class="year-stats">
                    <?php foreach($years as $year): ?>
                        <?php $has_data = $year_counts[$year] > 0; ?>
                        <span class="year-pill <?php echo $has_data ? 'has-data' : ''; ?>" 
                              title="<?php echo $year; ?>: <?php echo $year_counts[$year]; ?> reports"
                              onclick="filterByYear(<?php echo $year; ?>)">
                            <span class="year-dot <?php echo $has_data ? 'dot-active' : 'dot-inactive'; ?>"></span>
                            <?php echo $year; ?>
                            <?php if($has_data): ?>
                                <span style="font-weight: 700;">(<?php echo $year_counts[$year]; ?>)</span>
                            <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Filter Section - Enhanced -->
            <div class="filter-section">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" class="search-input" 
                           placeholder="Search by client name or docket number...">
                </div>
                
                <select id="yearFilter" class="filter-select">
                    <option value="all">📅 All Years (2020-2030)</option>
                    <?php foreach($years as $year): ?>
                        <?php $has_data = $year_counts[$year] > 0; ?>
                        <option value="<?php echo $year; ?>" class="year-option">
                            <?php echo $year; ?> 
                            <?php if($has_data): ?>
                                (<?php echo $year_counts[$year]; ?> reports)
                            <?php else: ?>
                                (no data)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="filter-info">
                    <span>
                        <i class="fas fa-images"></i>
                        <span id="resultCount"><?php echo $total_reports; ?></span> reports found
                    </span>
                    <span class="clear-filter" onclick="clearFilters()">
                        <i class="fas fa-times-circle"></i> Clear filters
                    </span>
                </div>
            </div>

            <!-- Reports Table -->
            <div class="table-container">
                <table class="reports-table" id="reportsTable">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Client</th>
                            <th>Docket #</th>
                            <th>Period</th>
                            <th>Uploaded By</th>
                            <th>Upload Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        mysqli_data_seek($reports, 0);
                        if($total_reports > 0):
                            while($row = mysqli_fetch_assoc($reports)): 
                        ?>
                        <tr class="report-row" data-year="<?php echo $row['report_year']; ?>" data-month="<?php echo $row['report_month']; ?>">
                            <td>
                                <img src="uploads/<?php echo $row['photo']; ?>" class="report-thumb" onclick="openModal('uploads/<?php echo $row['photo']; ?>')" alt="Report Photo">
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($row['docket_number']); ?></td>
                            <td>
                                <?php echo date("F Y", mktime(0,0,0,$row['report_month'],1,$row['report_year'])); ?>
                                <span class="year-badge"><?php echo $row['report_year']; ?></span>
                            </td>
                            <td><?php echo $row['uploaded_by_name'] ?? 'Unknown'; ?></td>
                            <td><?php echo date("M d, Y", strtotime($row['upload_date'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $row['status']; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="uploads/<?php echo $row['photo']; ?>" download class="action-link download" title="Download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <a href="client_details.php?id=<?php echo $row['probationer_id']; ?>" class="action-link view" title="View Client">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <!-- Delete button - Only visible to admin/main -->
                                    <?php if($can_delete): ?>
                                    <a href="?delete=<?php echo $row['id']; ?>" class="action-link delete" title="Delete" 
                                       onclick="return confirm('⚠️ Are you sure you want to delete this image?\n\nClient: <?php echo addslashes($row['name']); ?>\nPeriod: <?php echo date("F Y", mktime(0,0,0,$row['report_month'],1,$row['report_year'])); ?>\n\nThis action cannot be undone!')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php else: ?>
                                    <span class="action-link disabled" title="Delete (View Only)">
                                        <i class="fas fa-trash"></i>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                        <tr>
                            <td colspan="8" class="empty-state">
                                <i class="fas fa-camera"></i>
                                <p style="font-size: 1.1rem;">No reports found for 2020-2030</p>
                                <p>Upload your first report from the dashboard.</p>
                                <a href="dashboard.php" class="btn-primary">
                                    <i class="fas fa-arrow-left"></i> Go to Dashboard
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Year Summary - Enhanced -->
            <?php if($total_reports > 0): ?>
            <div class="year-summary">
                <span>
                    <i class="fas fa-chart-bar"></i> 
                    Reports by year: 
                    <?php 
                    $active_years = [];
                    foreach($years as $year) {
                        if($year_counts[$year] > 0) {
                            $active_years[] = "<strong>{$year}</strong> ({$year_counts[$year]})";
                        }
                    }
                    echo implode(' • ', $active_years);
                    ?>
                </span>
                <span>
                    <i class="fas fa-database"></i> 
                    Total: <?php echo $total_reports; ?> reports
                </span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal" id="imageModal" onclick="closeModal()">
        <span class="modal-close">&times;</span>
        <img id="modalImage" src="">
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
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
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
        });

        // Image Modal Functions
        function openModal(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('imageModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('imageModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if(e.key === 'Escape') {
                closeModal();
            }
        });

        // Live search and year filter
        const searchInput = document.getElementById('searchInput');
        const yearFilter = document.getElementById('yearFilter');
        const rows = document.querySelectorAll('.report-row');
        const resultCountSpan = document.getElementById('resultCount');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const selectedYear = yearFilter.value;
            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const year = row.dataset.year;
                
                const matchesSearch = searchTerm === '' || text.includes(searchTerm);
                const matchesYear = selectedYear === 'all' || year === selectedYear;

                if (matchesSearch && matchesYear) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Update result count
            if (resultCountSpan) {
                resultCountSpan.textContent = visibleCount;
            }
            
            // Show "no results" message if needed
            const tbody = document.querySelector('#reportsTable tbody');
            let noResultsRow = document.getElementById('noResultsRow');
            
            if (visibleCount === 0 && rows.length > 0) {
                if (!noResultsRow) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.id = 'noResultsRow';
                    noResultsRow.innerHTML = '<td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-muted);"><i class="fas fa-search" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>📭 No reports match your filters for 2020-2030</td>';
                    tbody.appendChild(noResultsRow);
                }
            } else if (noResultsRow) {
                noResultsRow.remove();
            }
        }

        // Filter by year (called from year pills)
        window.filterByYear = function(year) {
            if (yearFilter) {
                yearFilter.value = year;
                filterTable();
                // Scroll to filter section for better UX
                document.querySelector('.filter-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        };

        // Clear all filters
        window.clearFilters = function() {
            if (searchInput) searchInput.value = '';
            if (yearFilter) yearFilter.value = 'all';
            filterTable();
        };

        // Add event listeners
        if (searchInput && yearFilter) {
            searchInput.addEventListener('input', filterTable);
            yearFilter.addEventListener('change', filterTable);
        }

        // Initial filter
        if (typeof filterTable === 'function') {
            filterTable();
        }

        // Keyboard shortcut: ESC to clear filters when search is focused
        if (searchInput) {
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && this.value !== '') {
                    this.value = '';
                    filterTable();
                }
            });
        }

        // Double-click on year pills to filter (enhanced)
        document.querySelectorAll('.year-pill').forEach(pill => {
            pill.addEventListener('dblclick', function() {
                const yearText = this.textContent.match(/\d{4}/);
                if (yearText) {
                    const year = yearText[0];
                    filterByYear(year);
                }
            });
        });

        // Touch support for mobile
        document.querySelectorAll('.year-pill').forEach(pill => {
            pill.addEventListener('touchstart', function(e) {
                // Prevent default to avoid double-firing
                e.preventDefault();
                const yearText = this.textContent.match(/\d{4}/);
                if (yearText) {
                    const year = yearText[0];
                    filterByYear(year);
                }
            });
        });
    </script>
</body>
</html>
