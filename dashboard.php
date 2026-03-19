<?php
session_start();
include 'config/database.php';

// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Get user info from session
$fullname = $_SESSION['fullname'] ?? 'User';
$username = $_SESSION['username'] ?? '';
$user_role = $_SESSION['role'] ?? 'staff';

// Get user ID from database
$user_query = mysqli_query($conn, "SELECT id FROM staff WHERE username='$username'");
$user_data = mysqli_fetch_assoc($user_query);
$user_id = $user_data['id'] ?? 0;

/* ------------------------------
   CASE STATUS COUNTS
--------------------------------*/
$active = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM clients WHERE status='Active'"))['total'];
$terminated = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM clients WHERE status='Terminated'"))['total'];
$revoked = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM clients WHERE status='Revoked'"))['total'];
$denied = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM clients WHERE status='Denied'"))['total'];

/* ------------------------------
   CLIENT LIST - ALL STATUSES
--------------------------------*/
$all_clients = mysqli_query($conn,"SELECT * FROM clients ORDER BY 
    CASE status 
        WHEN 'Active' THEN 1 
        WHEN 'Terminated' THEN 2 
        WHEN 'Revoked' THEN 3 
        ELSE 4 
    END, name ASC");

/* ------------------------------
   MONTHLY CLIENT DATA
--------------------------------*/
$month_labels = [];
$month_data = [];
for($m=1;$m<=12;$m++){
    $monthName = date("F", mktime(0,0,0,$m,10));
    $month_labels[] = $monthName;
    $query = mysqli_query($conn,"SELECT COUNT(*) total FROM clients WHERE MONTH(created_at)='$m'");
    $row = mysqli_fetch_assoc($query);
    $month_data[] = $row['total'];
}

/* ------------------------------
   BARANGAY CLIENT DATA
--------------------------------*/
$barangay_labels = [];
$barangay_data = [];

$barangay_query = mysqli_query($conn,"SELECT address, COUNT(*) total FROM clients WHERE address IS NOT NULL AND address != '' GROUP BY address ORDER BY total DESC LIMIT 7");
while($row=mysqli_fetch_assoc($barangay_query)){
    $barangay_labels[] = $row['address'];
    $barangay_data[] = $row['total'];
}

/* ------------------------------
   HANDLE PHOTO UPLOAD - ALL STATUSES CAN UPLOAD
--------------------------------*/
if(isset($_POST['upload_photo'])){
    $probationer_id = $_POST['probationer_id'];
    $month = $_POST['month'];
    $year = $_POST['year'];
    
    // Check if report exists
    $check = mysqli_query($conn, "SELECT id FROM monthly_reports WHERE probationer_id='$probationer_id' AND report_month='$month' AND report_year='$year'");
    
    if(mysqli_num_rows($check) > 0){
        $error = "Report already exists for this month/year!";
    } else {
        $target_dir = "uploads/";
        if(!file_exists($target_dir)){
            mkdir($target_dir, 0777, true);
        }
        
        $photo = $_FILES['photo']['name'];
        $tmp = $_FILES['photo']['tmp_name'];
        $ext = pathinfo($photo, PATHINFO_EXTENSION);
        $new_filename = "report_".$probationer_id."_".$year."_".$month.".".$ext;
        $target_file = $target_dir . $new_filename;
        
        if(move_uploaded_file($tmp, $target_file)){
            $insert = mysqli_query($conn, "INSERT INTO monthly_reports (probationer_id, report_month, report_year, photo, uploaded_by) VALUES ('$probationer_id', '$month', '$year', '$new_filename', '$user_id')");
            if($insert){
                $success = "Monthly report uploaded successfully!";
            } else {
                $error = "Database error: " . mysqli_error($conn);
            }
        } else {
            $error = "Error uploading file!";
        }
    }
}

/* ------------------------------
   GET RECENT UPLOADS - FROM ALL STATUSES
--------------------------------*/
$recent_uploads = mysqli_query($conn,"
    SELECT mr.*, c.name, c.docket_number, c.status
    FROM monthly_reports mr 
    JOIN clients c ON mr.probationer_id = c.id 
    ORDER BY mr.upload_date DESC LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
    <title>Parole & Probation System | Dashboard</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- jQuery (for AJAX) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Select2 for searchable dropdown -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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
        }

        /* App Layout */
        .app {
            display: flex;
            min-height: 100vh;
            position: relative;
            width: 100%;
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
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            box-shadow: var(--box-shadow);
        }

        .page-title {
            font-size: clamp(1.2rem, 4vw, 1.5rem);
            font-weight: 600;
            color: var(--primary-dark);
            position: relative;
            padding-left: 1rem;
            border-left: 4px solid var(--accent-yellow);
            white-space: nowrap;
        }

        /* Search Container */
        .search-wrapper {
            flex: 1;
            min-width: 200px;
            position: relative;
        }

        .search-container {
            display: flex;
            align-items: center;
            background: #f1f5f9;
            border: 1px solid transparent;
            border-radius: 10px;
            padding: 0.5rem;
            transition: all 0.3s;
        }

        .search-container:focus-within {
            background: white;
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(46,107,94,0.15);
        }

        .search-icon {
            color: var(--text-muted);
            padding: 0 0.5rem;
        }

        .search-input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 0.5rem 0;
            font-size: 0.9rem;
            color: var(--text-primary);
            outline: none;
            width: 100%;
        }

        /* Live Search Suggestions */
        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid var(--neutral-border);
            border-radius: 10px;
            margin-top: 5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 1000;
            display: none;
            max-height: 300px;
            overflow-y: auto;
        }

        .suggestion-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s;
            font-size: 0.9rem;
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .suggestion-item:hover {
            background: #f1f5f9;
        }

        /* Filter Badges - Scrollable on Mobile */
        .search-filters {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-badge {
            background: #f1f5f9;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .filter-badge:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
        }

        .filter-badge.active {
            background: var(--primary-dark);
            color: white;
        }

        /* User Menu */
        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-left: auto;
            flex-wrap: wrap;
        }

        .user-name {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.9rem;
            display: none;
        }

        .user-name.show {
            display: inline-block;
        }

        .avatar {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 2px 8px rgba(46,107,94,0.25);
        }

        .action-btn {
            background: #f1f5f9;
            border: none;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .action-btn:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
        }

        .logout-btn {
            color: var(--text-muted);
            transition: color 0.2s;
            font-size: 1.1rem;
        }

        .logout-btn:hover {
            color: var(--accent-red);
        }

        /* Stats Grid - Responsive */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 1px solid var(--neutral-border);
            transition: transform 0.3s, box-shadow 0.3s;
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
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .stat-icon.active { background: #ecfdf3; color: #059669; }
        .stat-icon.terminated { background: #e0f2fe; color: #0284c7; }
        .stat-icon.revoked { background: var(--accent-yellow-light); color: #b45309; }
        .stat-icon.denied { background: var(--accent-red-light); color: var(--accent-red); }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 500;
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

        .stat-change {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .trend-up { color: #10b981; }
        .trend-down { color: var(--accent-red); }

        /* Charts Grid - Responsive */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 1px solid var(--neutral-border);
        }

        .chart-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .chart-title i {
            color: var(--accent-yellow);
            font-size: 1rem;
        }

        .chart-container {
            height: 200px;
            position: relative;
        }

        /* Legend - Responsive */
        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem 1rem;
            margin-top: 1rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--neutral-border);
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
        }

        .legend-color {
            width: 10px;
            height: 10px;
            border-radius: 3px;
        }

        /* Action Buttons - Responsive */
        .action-bar {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .btn-primary, .btn-secondary, .btn-warning {
            padding: 0.7rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--primary-dark);
            color: white;
            box-shadow: 0 4px 6px -2px rgba(30,74,61,0.2);
        }

        .btn-primary:hover {
            background: var(--primary);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: white;
            color: var(--text-secondary);
            border: 1px solid var(--neutral-border);
        }

        .btn-secondary:hover {
            background: #f8fafc;
            border-color: var(--primary);
            color: var(--primary-dark);
        }

        .btn-warning {
            background: var(--accent-yellow);
            color: var(--text-primary);
        }

        .btn-warning:hover {
            background: #fbbf24;
            transform: translateY(-2px);
        }

        /* Upload Container - Responsive */
        .upload-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
            border: 1px solid var(--neutral-border);
        }

        .upload-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px dashed var(--neutral-border);
        }

        .upload-title i {
            color: var(--accent-yellow);
            font-size: 1.2rem;
        }

        .quick-search {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            background: #f8fafc;
            padding: 1rem;
            border-radius: 10px;
            flex-wrap: wrap;
        }

        .quick-search-input {
            flex: 1;
            min-width: 200px;
            padding: 0.7rem 1rem;
            border: 1px solid var(--neutral-border);
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .quick-search-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(46,107,94,0.1);
        }

        .status-filter {
            width: 100%;
            max-width: 200px;
            padding: 0.7rem;
            border: 1px solid var(--neutral-border);
            border-radius: 8px;
            background: white;
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        /* Upload Grid - Fully Responsive */
        .upload-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .upload-group {
            display: flex;
            flex-direction: column;
        }

        .upload-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .upload-label i {
            margin-right: 0.25rem;
            color: var(--accent-yellow);
        }

        .upload-select {
            width: 100%;
            padding: 0.7rem;
            border: 1px solid var(--neutral-border);
            border-radius: 8px;
            background: white;
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .file-name {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Select2 Customization - Mobile Friendly */
        .select2-container--default .select2-selection--single {
            height: 42px;
            border: 1px solid var(--neutral-border);
            border-radius: 8px;
            padding: 0.5rem;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px;
            color: var(--text-primary);
            font-size: 0.9rem;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }
        
        .select2-dropdown {
            border: 1px solid var(--neutral-border);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            font-size: 0.9rem;
        }
        
        .select2-results__option {
            padding: 0.7rem 1rem;
        }

        /* Section Headers */
        .section-header {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            margin: 2rem 0 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .section-title {
            font-size: clamp(1.1rem, 4vw, 1.25rem);
            font-weight: 600;
            color: var(--primary-dark);
            position: relative;
            padding-left: 1rem;
        }

        .section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 70%;
            background: var(--accent-yellow);
            border-radius: 2px;
        }

        /* Tables - Horizontal Scroll on Mobile */
        .table-container {
            background: white;
            border-radius: var(--border-radius);
            overflow-x: auto;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
            border: 1px solid var(--neutral-border);
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        th {
            text-align: left;
            padding: 1rem;
            background: #f8fafc;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--neutral-border);
            white-space: nowrap;
        }

        td {
            padding: 1rem;
            color: var(--text-primary);
            font-size: 0.9rem;
            border-bottom: 1px solid var(--neutral-border);
            white-space: nowrap;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #faf9fe;
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.3rem 0.8rem;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }

        /* Action Links */
        .action-link {
            color: var(--text-muted);
            text-decoration: none;
            margin-right: 0.75rem;
            font-size: 0.9rem;
            transition: all 0.2s;
            padding: 0.25rem 0.4rem;
            border-radius: 4px;
            display: inline-block;
        }

        .action-link:hover {
            color: var(--primary-dark);
            background: var(--primary-light);
        }

        .upload-link {
            color: var(--primary-dark);
            font-weight: 600;
        }

        /* Upload Thumbnail */
        .upload-thumb {
            width: 35px;
            height: 35px;
            border-radius: 6px;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid var(--neutral-border);
            transition: transform 0.2s;
        }

        .upload-thumb:hover {
            transform: scale(2);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10;
            position: relative;
        }

        /* Messages */
        .message {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease;
            font-size: 0.9rem;
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

        .message.success {
            background: #ecfdf3;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .message.error {
            background: var(--accent-red-light);
            color: #991b1b;
            border: 1px solid #fecaca;
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
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(4px);
            padding: 1rem;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            animation: modalPop 0.3s ease;
            margin: 1rem;
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

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--neutral-border);
        }

        .modal-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .modal-close {
            cursor: pointer;
            color: var(--text-muted);
            font-size: 1.5rem;
            transition: color 0.2s;
        }

        .modal-close:hover {
            color: var(--accent-red);
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
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
            .charts-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .user-name {
                display: inline-block;
            }
        }

        /* Desktop (992px to 1199px) */
        @media (max-width: 1199px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .charts-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .upload-grid {
                grid-template-columns: repeat(2, 1fr);
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
            
            .main {
                margin-left: 0;
                width: 100%;
                padding: 1rem;
                padding-top: calc(var(--header-height) + 0.5rem);
            }
            
            .top-bar {
                margin-top: 0;
                padding: 1rem;
            }
            
            .page-title {
                font-size: 1.2rem;
                padding-left: 0.75rem;
            }
            
            .user-name {
                display: none;
            }
            
            .stats-grid {
                gap: 1rem;
            }
            
            .charts-grid {
                gap: 1rem;
            }
            
            .upload-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Mobile Landscape (576px to 767px) */
        @media (max-width: 767px) {
            .main {
                padding: 0.75rem;
                padding-top: calc(var(--header-height) + 0.5rem);
            }
            
            .top-bar {
                flex-direction: column;
                align-items: stretch;
                padding: 1rem;
            }
            
            .page-title {
                margin-bottom: 0.5rem;
            }
            
            .search-wrapper {
                width: 100%;
            }
            
            .search-filters {
                justify-content: center;
            }
            
            .user-menu {
                justify-content: flex-end;
                margin-left: 0;
                width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            
            .upload-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-search {
                flex-direction: column;
            }
            
            .status-filter {
                max-width: 100%;
            }
            
            .action-bar {
                flex-direction: column;
            }
            
            .btn-primary, .btn-secondary, .btn-warning {
                width: 100%;
                justify-content: center;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .stat-card {
                padding: 1.25rem;
            }
            
            .stat-value {
                font-size: 2rem;
            }
            
            .chart-container {
                height: 180px;
            }
            
            .modal-content {
                margin: 1rem;
                padding: 1.25rem;
            }
        }

        /* Mobile Portrait (up to 575px) */
        @media (max-width: 575px) {
            .main {
                padding: 0.5rem;
                padding-top: calc(var(--header-height) + 0.5rem);
            }
            
            .top-bar {
                padding: 0.75rem;
            }
            
            .filter-badge {
                padding: 0.4rem 0.75rem;
                font-size: 0.75rem;
            }
            
            .user-menu {
                gap: 0.5rem;
            }
            
            .action-btn {
                padding: 0.4rem 0.6rem;
                font-size: 0.8rem;
            }
            
            .avatar {
                width: 35px;
                height: 35px;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-icon {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }
            
            .stat-value {
                font-size: 1.75rem;
            }
            
            .stat-change {
                font-size: 0.75rem;
            }
            
            .chart-title {
                font-size: 0.9rem;
            }
            
            .chart-container {
                height: 160px;
            }
            
            .legend-item {
                font-size: 0.7rem;
            }
            
            .upload-container {
                padding: 1rem;
            }
            
            .upload-title {
                font-size: 0.9rem;
                margin-bottom: 1rem;
            }
            
            .quick-search {
                padding: 0.75rem;
            }
            
            .upload-label {
                font-size: 0.75rem;
            }
            
            .upload-select, .quick-search-input, .status-filter {
                padding: 0.6rem;
                font-size: 0.85rem;
            }
            
            .btn-primary, .btn-secondary, .btn-warning {
                padding: 0.6rem 1rem;
                font-size: 0.85rem;
            }
            
            .section-title {
                font-size: 1rem;
            }
            
            .modal-title {
                font-size: 1rem;
            }
            
            .modal-content {
                padding: 1rem;
            }
            
            .upload-thumb {
                width: 30px;
                height: 30px;
            }
            
            .action-link {
                margin-right: 0.5rem;
                font-size: 0.85rem;
            }
            
            .logout-btn {
                font-size: 1rem;
            }
        }

        /* Small Mobile (up to 375px) */
        @media (max-width: 375px) {
            .filter-badge {
                padding: 0.3rem 0.6rem;
                font-size: 0.7rem;
            }
            
            .action-btn {
                padding: 0.35rem 0.5rem;
                font-size: 0.75rem;
            }
            
            .avatar {
                width: 32px;
                height: 32px;
            }
            
            .stat-value {
                font-size: 1.5rem;
            }
            
            .stat-icon {
                width: 32px;
                height: 32px;
            }
            
            .chart-container {
                height: 140px;
            }
        }

        /* Print Styles */
        @media print {
            .sidebar, .menu-toggle, .sidebar-overlay, .action-bar, 
            .upload-container, .search-wrapper, .search-filters, 
            .user-menu .action-btn, .logout-btn {
                display: none !important;
            }
            
            .main {
                margin-left: 0;
                padding: 0.5in;
            }
            
            .top-bar {
                border: none;
                box-shadow: none;
                padding: 0;
            }
            
            .stat-card, .chart-card, .table-container {
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
                <a href="dashboard.php" class="nav-item active">
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
                <h1 class="page-title">Dashboard</h1>
                
                <!-- Search Wrapper -->
                <div class="search-wrapper">
                    <div class="search-container">
                        <span class="search-icon"><i class="fas fa-search"></i></span>
                        <input type="text" id="searchInput" class="search-input" placeholder="Search clients..." autocomplete="off">
                    </div>
                    
                    <!-- Live Search Suggestions -->
                    <div id="searchSuggestions" class="search-suggestions"></div>
                </div>

                <!-- Filter Badges -->
                <div class="search-filters">
                    <button class="filter-badge active" data-filter="all">All</button>
                    <button class="filter-badge" data-filter="Active">Active</button>
                    <button class="filter-badge" data-filter="Terminated">Terminated</button>
                    <button class="filter-badge" data-filter="Revoked">Revoked</button>
                    <button class="filter-badge" data-filter="Denied">Denied</button>
                </div>

                <!-- User Menu -->
                <div class="user-menu">
                    <span class="user-name show"><?php echo htmlspecialchars($fullname); ?></span>
                    <div class="avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <button onclick="window.print()" class="action-btn">
                        <i class="fas fa-print"></i>
                    </button>
                    <a href="profile.php" class="action-btn">
                        <i class="fas fa-user-circle"></i>
                    </a>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt fa-lg"></i>
                    </a>
                </div>
            </div>

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

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon active">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <i class="fas fa-ellipsis-h" style="color: var(--text-muted);"></i>
                    </div>
                    <div class="stat-label">Active Cases</div>
                    <div class="stat-value active"><?php echo $active; ?></div>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up trend-up"></i>
                        <span>+12%</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon terminated">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <i class="fas fa-ellipsis-h" style="color: var(--text-muted);"></i>
                    </div>
                    <div class="stat-label">Terminated</div>
                    <div class="stat-value terminated"><?php echo $terminated; ?></div>
                    <div class="stat-change">
                        <i class="fas fa-minus" style="color: var(--text-muted);"></i>
                        <span>0%</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon revoked">
                            <i class="fas fa-user-slash"></i>
                        </div>
                        <i class="fas fa-ellipsis-h" style="color: var(--text-muted);"></i>
                    </div>
                    <div class="stat-label">Revoked</div>
                    <div class="stat-value revoked"><?php echo $revoked; ?></div>
                    <div class="stat-change">
                        <i class="fas fa-arrow-down trend-down"></i>
                        <span>-5%</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon denied">
                            <i class="fas fa-user-times"></i>
                        </div>
                        <i class="fas fa-ellipsis-h" style="color: var(--text-muted);"></i>
                    </div>
                    <div class="stat-label">Denied</div>
                    <div class="stat-value denied"><?php echo $denied; ?></div>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up trend-up"></i>
                        <span>+3%</span>
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="charts-grid">
                <!-- Case Status Chart -->
                <div class="chart-card">
                    <div class="chart-title">
                        <i class="fas fa-chart-pie"></i>
                        Status Distribution
                    </div>
                    <div class="chart-container">
                        <canvas id="caseChart"></canvas>
                    </div>
                    <div class="legend">
                        <div class="legend-item">
                            <span class="legend-color" style="background:#10b981;"></span>
                            <span>Active (<?php echo $active; ?>)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color" style="background:#3b82f6;"></span>
                            <span>Terminated (<?php echo $terminated; ?>)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color" style="background:#f59e0b;"></span>
                            <span>Revoked (<?php echo $revoked; ?>)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color" style="background:#ef4444;"></span>
                            <span>Denied (<?php echo $denied; ?>)</span>
                        </div>
                    </div>
                </div>

                <!-- Monthly Trend Chart -->
                <div class="chart-card">
                    <div class="chart-title">
                        <i class="fas fa-chart-line"></i>
                        Monthly Trend
                    </div>
                    <div class="chart-container">
                        <canvas id="monthChart"></canvas>
                    </div>
                </div>

                <!-- Barangay Chart -->
                <div class="chart-card">
                    <div class="chart-title">
                        <i class="fas fa-map-marker-alt"></i>
                        Top Barangays
                    </div>
                    <div class="chart-container">
                        <canvas id="barangayChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-bar">
                <a href="add_probationer.php" class="btn-primary">
                    <i class="fas fa-plus-circle"></i>
                    Add New Client
                </a>
                <button id="exportCsvBtn" class="btn-secondary">
                    <i class="fas fa-file-export"></i>
                    Export CSV
                </button>
                <button onclick="window.print()" class="btn-warning">
                    <i class="fas fa-print"></i>
                    Print Report
                </button>
            </div>

            <!-- Upload Section -->
            <div class="upload-container">
                <div class="upload-title">
                    <i class="fas fa-cloud-upload-alt"></i>
                    Monthly Photo Report Upload
                </div>

                <!-- Quick Search for Clients -->
                <div class="quick-search">
                    <input type="text" id="quickClientSearch" class="quick-search-input" placeholder="🔍 Quick: Type client name or docket #...">
                    <select id="statusFilterUpload" class="status-filter">
                        <option value="all">All Statuses</option>
                        <option value="Active">Active</option>
                        <option value="Terminated">Terminated</option>
                        <option value="Revoked">Revoked</option>
                        <option value="Denied">Denied</option>
                    </select>
                </div>

                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <div class="upload-grid">
                        <div class="upload-group">
                            <label class="upload-label">
                                <i class="fas fa-user"></i> Client <span style="color: var(--accent-red);">*</span>
                            </label>
                            <select name="probationer_id" id="clientSelect" class="upload-select" required>
                                <option value="">Search client...</option>
                                <?php
                                $all_status_clients = mysqli_query($conn,"SELECT id, name, docket_number, status FROM clients ORDER BY name ASC");
                                while($c = mysqli_fetch_assoc($all_status_clients)){
                                    $status_icon = '';
                                    if($c['status'] == 'Active') $status_icon = '🟢';
                                    else if($c['status'] == 'Terminated') $status_icon = '🔵';
                                    else if($c['status'] == 'Revoked') $status_icon = '🟠';
                                    else if($c['status'] == 'Denied') $status_icon = '🔴';
                                    
                                    echo "<option value='{$c['id']}' data-status='{$c['status']}'>{$status_icon} {$c['name']} - {$c['docket_number']}</option>";
                                }
                                ?>
                            </select>
                            <div class="file-name" id="selectedClientDisplay"></div>
                        </div>
                        
                        <div class="upload-group">
                            <label class="upload-label">
                                <i class="fas fa-calendar"></i> Month <span style="color: var(--accent-red);">*</span>
                            </label>
                            <select name="month" class="upload-select" required>
                                <option value="">Select Month</option>
                                <?php for($m=1;$m<=12;$m++): ?>
                                    <option value="<?php echo $m; ?>"><?php echo date("F", mktime(0,0,0,$m,10)); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="upload-group">
                            <label class="upload-label">
                                <i class="fas fa-calendar-alt"></i> Year <span style="color: var(--accent-red);">*</span>
                            </label>
                            <select name="year" class="upload-select" required>
                                <option value="">Select Year</option>
                                <?php for($y=date('Y'); $y>=date('Y')-2; $y--): ?>
                                    <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="upload-group">
                            <label class="upload-label">
                                <i class="fas fa-image"></i> Photo <span style="color: var(--accent-red);">*</span>
                            </label>
                            <div class="file-input-wrapper">
                                <input type="file" name="photo" accept="image/*" style="display: none;" id="photoInput">
                                <button type="button" class="btn-secondary" onclick="document.getElementById('photoInput').click()" style="width: 100%;">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    Choose
                                </button>
                                <div class="file-name" id="fileSelected">No file</div>
                            </div>
                        </div>
                        
                        <div class="upload-group">
                            <label class="upload-label">&nbsp;</label>
                            <button type="submit" name="upload_photo" class="btn-primary" style="width: 100%;">
                                <i class="fas fa-check-circle"></i>
                                Upload
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Recent Uploads -->
            <?php if(mysqli_num_rows($recent_uploads) > 0): ?>
            <div>
                <div class="section-header">
                    <h2 class="section-title">Recent Uploads</h2>
                    <span style="color: var(--text-muted); font-size:0.85rem;">Last 10</span>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Docket #</th>
                                <th>Status</th>
                                <th>Month/Year</th>
                                <th>Photo</th>
                                <th>Uploaded</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($upload = mysqli_fetch_assoc($recent_uploads)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($upload['name']); ?></td>
                                <td><?php echo htmlspecialchars($upload['docket_number']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $upload['status']; ?>">
                                        <?php echo $upload['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date("F Y", mktime(0,0,0,$upload['report_month'],1,$upload['report_year'])); ?></td>
                                <td>
                                    <img src="uploads/<?php echo $upload['photo']; ?>" class="upload-thumb" onclick="window.open('uploads/<?php echo $upload['photo']; ?>')">
                                </td>
                                <td><?php echo date("M d", strtotime($upload['upload_date'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Client List -->
            <div class="section-header">
                <h2 class="section-title">Complete Client List</h2>
                <span id="tableResults" style="color:var(--text-muted); font-size:0.85rem;"></span>
            </div>

            <!-- Clients Table -->
            <div class="table-container">
                <table id="clientsTable">
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
                    <tbody id="clientTableBody">
                        <?php 
                        mysqli_data_seek($all_clients, 0);
                        while($row=mysqli_fetch_assoc($all_clients)){ 
                        ?>
                        <tr class="client-row">
                            <td><strong><?php echo $row['docket_number'];?></strong></td>
                            <td><?php echo $row['name'];?></td>
                            <td><?php echo substr($row['offense'], 0, 25); ?>...</td>
                            <td><?php echo $row['court'];?></td>
                            <td><?php echo $row['address'];?></td>
                            <td>
                                <span class="status-badge status-<?php echo $row['status']; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="#" onclick="selectClientForUpload(<?php echo $row['id']; ?>, '<?php echo addslashes($row['name']); ?>', '<?php echo $row['docket_number']; ?>'); return false;" class="action-link upload-link" title="Upload">
                                    <i class="fas fa-camera"></i>
                                </a>
                                <a href="view_client.php?id=<?php echo $row['id']; ?>" class="action-link" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Client Info Modal -->
    <div class="modal" id="clientModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Client Information</h3>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <div>
                <p id="modalDocket" style="margin-bottom: 0.5rem; font-size:0.95rem;"></p>
                <p id="modalName" style="margin-bottom: 0.5rem; font-size:0.95rem;"></p>
                <p id="modalOffense" style="margin-bottom: 0.5rem; font-size:0.95rem;"></p>
                <p id="modalCourt" style="margin-bottom: 0.5rem; font-size:0.95rem;"></p>
                <p id="modalStatus" style="margin-bottom: 0.5rem; font-size:0.95rem;"></p>
                <p id="modalAddress" style="font-size:0.95rem;"></p>
            </div>
        </div>
    </div>

    <script>
        // Mobile Menu Toggle
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

        // CHARTS
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Case Status Chart
            new Chart(document.getElementById('caseChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Active', 'Terminated', 'Revoked', 'Denied'],
                    datasets: [{
                        data: [<?php echo $active;?>, <?php echo $terminated;?>, <?php echo $revoked;?>, <?php echo $denied;?>],
                        backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
                        borderWidth: 0,
                        hoverOffset: 8
                    }]
                },
                options: {
                    cutout: '70%',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { 
                            backgroundColor: '#1e293b',
                            titleColor: 'white',
                            bodyColor: '#e2e8f0',
                            padding: 10,
                            cornerRadius: 6
                        }
                    }
                }
            });

            // 2. Monthly Trend Chart
            const monthCtx = document.getElementById('monthChart').getContext('2d');
            const monthGradient = monthCtx.createLinearGradient(0, 0, 0, 200);
            monthGradient.addColorStop(0, 'rgba(46, 107, 94, 0.2)');
            monthGradient.addColorStop(1, 'rgba(46, 107, 94, 0.0)');

            new Chart(monthCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($month_labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($month_data); ?>,
                        borderColor: '#2e6b5e',
                        backgroundColor: monthGradient,
                        borderWidth: 2,
                        pointBackgroundColor: '#1e4a3d',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: { mode: 'index', intersect: false }
                    },
                    scales: {
                        x: { 
                            display: true,
                            grid: { display: false },
                            ticks: { maxRotation: 30, minRotation: 30, font: { size: 8 } }
                        },
                        y: { 
                            display: true,
                            beginAtZero: true,
                            grid: { color: '#e2e8f0', drawBorder: false },
                            ticks: { stepSize: 1, font: { size: 8 } }
                        }
                    }
                }
            });

            // 3. Barangay Chart
            const barColors = ['#2e6b5e', '#fbbf24', '#dc2626', '#8b5cf6', '#06b6d4', '#ec4899', '#14b8a6'];
            new Chart(document.getElementById('barangayChart'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($barangay_labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($barangay_data); ?>,
                        backgroundColor: barColors.slice(0, <?php echo count($barangay_labels); ?>),
                        borderRadius: 6,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: { callbacks: { label: (ctx) => `${ctx.raw} clients` } }
                    },
                    scales: {
                        x: { 
                            display: true,
                            grid: { display: false },
                            ticks: { maxRotation: 30, minRotation: 30, font: { size: 8 } }
                        },
                        y: { 
                            display: true,
                            beginAtZero: true,
                            grid: { color: '#e2e8f0', drawBorder: false },
                            ticks: { stepSize: 1, font: { size: 8 } }
                        }
                    }
                }
            });

            // ============ SELECT2 INIT ============
            if (typeof $.fn.select2 !== 'undefined') {
                $('#clientSelect').select2({
                    placeholder: '🔍 Search for a client...',
                    allowClear: true,
                    width: '100%'
                });
            }

            // ============ QUICK CLIENT SEARCH ============
            $('#quickClientSearch').on('keyup', function() {
                var searchTerm = $(this).val();
                $('#clientSelect').select2('open');
                setTimeout(function() {
                    $('.select2-search__field').val(searchTerm).trigger('keyup');
                }, 100);
            });

            // ============ STATUS FILTER ============
            $('#statusFilterUpload').on('change', function() {
                const selectedStatus = $(this).val();
                
                $('#clientSelect option').each(function() {
                    const option = $(this);
                    const status = option.data('status');
                    
                    if (selectedStatus === 'all' || status === selectedStatus) {
                        option.show();
                    } else {
                        option.hide();
                    }
                });
                
                if (typeof $.fn.select2 !== 'undefined') {
                    $('#clientSelect').select2('destroy').select2({
                        placeholder: '🔍 Search for a client...',
                        allowClear: true,
                        width: '100%'
                    });
                }
            });

            // ============ SELECT CLIENT FUNCTION ============
            window.selectClientForUpload = function(id, name, docket) {
                $('#clientSelect').val(id).trigger('change');
                $('#selectedClientDisplay').text('Selected: ' + name).css('color', '#059669');
                
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
                
                $('.upload-container').css('border', '2px solid #2e6b5e');
                setTimeout(function() {
                    $('.upload-container').css('border', '1px solid #e2e8f0');
                }, 2000);
            };

            // ============ FILE INPUT DISPLAY ============
            document.getElementById('photoInput').addEventListener('change', function(e) {
                const fileName = e.target.files[0] ? e.target.files[0].name : 'No file';
                document.getElementById('fileSelected').textContent = '📷 ' + fileName;
            });

            // ============ LIVE SEARCH ============
            const searchInput = document.getElementById('searchInput');
            const tableRows = document.querySelectorAll('.client-row');
            const filterBadges = document.querySelectorAll('.filter-badge');
            const suggestionsDiv = document.getElementById('searchSuggestions');
            let currentFilter = 'all';
            let searchTimeout;

            function fetchSuggestions() {
                const searchTerm = searchInput.value.trim();
                
                if (searchTerm.length < 2) {
                    suggestionsDiv.style.display = 'none';
                    return;
                }

                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    $.ajax({
                        url: 'live_search.php',
                        method: 'GET',
                        data: { q: searchTerm },
                        success: function(data) {
                            if (data.trim()) {
                                suggestionsDiv.innerHTML = data;
                                suggestionsDiv.style.display = 'block';
                            } else {
                                suggestionsDiv.style.display = 'none';
                            }
                        }
                    });
                }, 300);
            }

            searchInput.addEventListener('input', fetchSuggestions);
            
            $(document).on('click', '.suggestion-item', function() {
                const id = $(this).data('id');
                window.location.href = 'client_details.php?id=' + id;
            });

            $(document).click(function(e) {
                if (!$(e.target).closest('.search-wrapper').length) {
                    suggestionsDiv.style.display = 'none';
                }
            });

            // Filter badge clicks
            filterBadges.forEach(badge => {
                badge.addEventListener('click', function() {
                    filterBadges.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentFilter = this.getAttribute('data-filter');
                    
                    let visibleCount = 0;
                    tableRows.forEach(row => {
                        const status = row.cells[5].textContent.trim();
                        const matchesFilter = currentFilter === 'all' || status === currentFilter;
                        if (matchesFilter) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });
                    
                    document.getElementById('tableResults').textContent = `Showing ${visibleCount} clients`;
                });
            });

            // Export CSV
            document.getElementById('exportCsvBtn').addEventListener('click', function() {
                let csv = ['Docket #,Name,Offense,Court,Address,Status'];
                let visibleRows = 0;

                tableRows.forEach(row => {
                    if (row.style.display !== 'none') {
                        let rowData = [
                            '"' + row.cells[0].textContent + '"',
                            '"' + row.cells[1].textContent + '"',
                            '"' + row.cells[2].textContent.replace(/\.\.\.$/, '') + '"',
                            '"' + row.cells[3].textContent + '"',
                            '"' + row.cells[4].textContent + '"',
                            '"' + row.cells[5].textContent.trim() + '"'
                        ];
                        csv.push(rowData.join(','));
                        visibleRows++;
                    }
                });

                if (visibleRows === 0) {
                    alert('No rows to export');
                    return;
                }

                const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `clients_${new Date().toISOString().slice(0,10)}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            });

            // Modal functionality
            window.closeModal = function() {
                document.getElementById('clientModal').classList.remove('active');
            };

            tableRows.forEach(row => {
                row.addEventListener('click', function(e) {
                    if (e.target.tagName === 'A' || e.target.closest('a')) {
                        return;
                    }
                    
                    document.getElementById('modalDocket').textContent = 'Docket #: ' + this.cells[0].textContent;
                    document.getElementById('modalName').textContent = 'Name: ' + this.cells[1].textContent;
                    document.getElementById('modalOffense').textContent = 'Offense: ' + this.cells[2].textContent.replace(/\.\.\.$/, '');
                    document.getElementById('modalCourt').textContent = 'Court: ' + this.cells[3].textContent;
                    document.getElementById('modalStatus').textContent = 'Status: ' + this.cells[5].textContent.trim();
                    document.getElementById('modalAddress').textContent = 'Address: ' + this.cells[4].textContent;
                    document.getElementById('clientModal').classList.add('active');
                });
            });

            window.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal')) {
                    closeModal();
                }
            });

            // Initialize visible count
            let initialVisible = 0;
            tableRows.forEach(row => {
                if (row.style.display !== 'none') initialVisible++;
            });
            document.getElementById('tableResults').textContent = `Showing ${initialVisible} clients`;
        });
    </script>
</body>
</html>