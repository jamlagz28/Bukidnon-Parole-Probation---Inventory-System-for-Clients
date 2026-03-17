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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parole & Probation System</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
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
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.5;
        }

        /* App Layout */
        .app {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: white;
            border-right: 1px solid #e2e8f0;
            padding: 2rem 1.5rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .logo {
            font-weight: 600;
            font-size: 1.25rem;
            color: #0f172a;
            margin-bottom: 2rem;
            letter-spacing: -0.01em;
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
            transition: all 0.2s;
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

        .nav-item i {
            width: 20px;
            font-size: 1.1rem;
        }

        /* Main Content */
        .main {
            flex: 1;
            margin-left: 260px;
            padding: 2rem;
        }

        /* Top Bar with Search */
        .top-bar {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            position: relative;
        }

        .page-title {
            font-size: 1.25rem;
            font-weight: 500;
            color: #0f172a;
            white-space: nowrap;
        }

        /* Search Container with Suggestions */
        .search-wrapper {
            flex: 1;
            position: relative;
        }

        .search-container {
            display: flex;
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.5rem;
            transition: all 0.2s;
        }

        .search-container:focus-within {
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 2px 8px rgba(59,130,246,0.1);
        }

        .search-icon {
            color: #94a3b8;
            padding: 0 0.75rem;
            font-size: 0.9rem;
        }

        .search-input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 0.5rem 0;
            font-size: 0.95rem;
            color: #1e293b;
            outline: none;
        }

        .search-input::placeholder {
            color: #94a3b8;
        }

        /* Live Search Suggestions Dropdown */
        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e2e8f0;
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
            display: flex;
            align-items: center;
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .suggestion-item:hover {
            background: #f1f5f9;
        }

        .suggestion-item strong {
            color: #0f172a;
        }

        .search-filters {
            display: flex;
            gap: 0.5rem;
        }

        .filter-badge {
            background: #f1f5f9;
            border: none;
            padding: 0.35rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .filter-badge:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .filter-badge.active {
            background: #0f172a;
            color: white;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-left: auto;
        }

        .user-name {
            color: #475569;
            font-size: 0.95rem;
        }

        .avatar {
            width: 38px;
            height: 38px;
            background: #f1f5f9;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .logout-btn {
            color: #94a3b8;
            transition: color 0.2s;
        }

        .logout-btn:hover {
            color: #ef4444;
        }

        /* Stats Grid */
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
        }

        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 500;
            color: #0f172a;
        }

        .stat-value.active { color: #10b981; }
        .stat-value.terminated { color: #3b82f6; }
        .stat-value.revoked { color: #f59e0b; }
        .stat-value.denied { color: #ef4444; }

        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .chart-title {
            font-size: 0.95rem;
            font-weight: 500;
            color: #475569;
            margin-bottom: 1rem;
        }

        .chart-container {
            height: 200px;
            position: relative;
        }

        /* Section Headers */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 2rem 0 1rem;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 500;
            color: #0f172a;
        }

        .section-link {
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .section-link:hover {
            color: #0f172a;
        }

        /* Upload Area - IMPROVED */
        .upload-container {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .upload-title {
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .upload-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
            gap: 1rem;
            align-items: end;
        }

        .upload-group {
            display: flex;
            flex-direction: column;
        }

        .upload-label {
            font-size: 0.85rem;
            font-weight: 500;
            color: #475569;
            margin-bottom: 0.5rem;
        }

        /* Select2 Customization */
        .select2-container--default .select2-selection--single {
            height: 42px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.5rem;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px;
            color: #1e293b;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }
        
        .select2-dropdown {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .select2-results__option {
            padding: 0.75rem 1rem;
        }
        
        .select2-results__option--highlighted {
            background: #f1f5f9 !important;
            color: #0f172a !important;
        }

        .upload-select {
            width: 100%;
            padding: 0.625rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            color: #1e293b;
            font-size: 0.95rem;
            height: 42px;
        }

        .upload-btn {
            background: #0f172a;
            color: white;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background 0.2s;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .upload-btn:hover {
            background: #1e293b;
        }

        .file-input-wrapper {
            position: relative;
        }

        .file-name {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Quick Search Section */
        .quick-search {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
        }

        .quick-search-input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .status-filter {
            width: 200px;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
        }

        /* Tables */
        .table-container {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 1rem 1.5rem;
            background: #f8fafc;
            color: #475569;
            font-weight: 500;
            font-size: 0.9rem;
            border-bottom: 1px solid #e2e8f0;
        }

        td {
            padding: 1rem 1.5rem;
            color: #1e293b;
            font-size: 0.95rem;
            border-bottom: 1px solid #f1f5f9;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-Active {
            background: #ecfdf3;
            color: #059669;
        }

        .status-Terminated {
            background: #e0f2fe;
            color: #0284c7;
        }

        .status-Revoked {
            background: #fffbeb;
            color: #d97706;
        }

        .status-Denied {
            background: #fef2f2;
            color: #dc2626;
        }

        /* Action Links */
        .action-link {
            color: #64748b;
            text-decoration: none;
            margin-right: 1rem;
            font-size: 0.9rem;
        }

        .action-link:hover {
            color: #0f172a;
        }

        .upload-link {
            color: #0f172a;
            font-weight: 500;
        }

        /* Export Button */
        .export-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .export-btn:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        .print-btn {
            background: #64748b;
            color: white;
        }
        
        .print-btn:hover {
            background: #475569;
        }

        /* Messages */
        .message {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .message.success {
            background: #ecfdf3;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .message.error {
            background: #fef2f2;
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
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            width: 400px;
            max-width: 90%;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.1rem;
            font-weight: 500;
        }

        .modal-close {
            cursor: pointer;
            color: #94a3b8;
            font-size: 1.5rem;
        }

        /* Recent Uploads */
        .upload-thumb {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            object-fit: cover;
            cursor: pointer;
        }

        /* Legend */
        .legend {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
            font-size: 0.8rem;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .legend-color {
            width: 8px;
            height: 8px;
            border-radius: 4px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid,
            .charts-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .top-bar {
                flex-wrap: wrap;
            }
            
            .search-wrapper {
                order: 3;
                width: 100%;
            }
            
            .upload-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .main {
                margin-left: 0;
            }
            .stats-grid,
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="app">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-scale-balanced" style="margin-right: 8px;"></i>
                PPA System
            </div>
            
            <div style="margin-top: 2rem;">
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
            </div>
        </div>

        <!-- Main Content -->
        <div class="main">
            <!-- Top Bar with Search -->
            <div class="top-bar">
                <h1 class="page-title">Dashboard</h1>
                
                <!-- Search Wrapper with Suggestions -->
                <div class="search-wrapper">
                    <div class="search-container">
                        <span class="search-icon"><i class="fas fa-search"></i></span>
                        <input type="text" id="searchInput" class="search-input" placeholder="Search clients..." autocomplete="off">
                    </div>
                    
                    <!-- Live Search Suggestions Dropdown -->
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
                    <span class="user-name"><?php echo htmlspecialchars($fullname); ?></span>
                    <div class="avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <button onclick="window.print()" class="export-btn print-btn" style="margin-right: 0.5rem;">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <a href="profile.php" class="logout-btn" style="margin:0 0.5rem;">
                        <i class="fas fa-user-circle"></i>
                    </a>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>

            <!-- Messages -->
            <?php if(isset($success)): ?>
                <div class="message success">
                    <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Active Cases</div>
                    <div class="stat-value active"><?php echo $active; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Terminated</div>
                    <div class="stat-value terminated"><?php echo $terminated; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Revoked</div>
                    <div class="stat-value revoked"><?php echo $revoked; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Denied</div>
                    <div class="stat-value denied"><?php echo $denied; ?></div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="charts-grid">
                <!-- Case Status Chart -->
                <div class="chart-card">
                    <div class="chart-title">Case Status Distribution</div>
                    <div class="chart-container">
                        <canvas id="caseChart"></canvas>
                    </div>
                    <div class="legend">
                        <div class="legend-item"><span class="legend-color" style="background:#10b981;"></span> Active</div>
                        <div class="legend-item"><span class="legend-color" style="background:#3b82f6;"></span> Terminated</div>
                        <div class="legend-item"><span class="legend-color" style="background:#f59e0b;"></span> Revoked</div>
                        <div class="legend-item"><span class="legend-color" style="background:#ef4444;"></span> Denied</div>
                    </div>
                </div>

                <!-- Monthly Trend Chart -->
                <div class="chart-card">
                    <div class="chart-title">Monthly Trend</div>
                    <div class="chart-container">
                        <canvas id="monthChart"></canvas>
                    </div>
                </div>

                <!-- Barangay Chart -->
                <div class="chart-card">
                    <div class="chart-title">Top Barangays</div>
                    <div class="chart-container">
                        <canvas id="barangayChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Row -->
            <div style="display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
                <a href="add_probationer.php" class="upload-btn" style="text-decoration: none;">
                    <i class="fas fa-plus" style="margin-right: 8px;"></i>
                    Add New Client
                </a>
                <a href="pi_add.php" class="upload-btn" style="text-decoration: none; background: #f59e0b;">
                    <i class="fas fa-file-lines" style="margin-right: 8px;"></i>
                    Add PI Case
                </a>
                <a href="ps_add.php" class="upload-btn" style="text-decoration: none; background: #10b981;">
                    <i class="fas fa-gavel" style="margin-right: 8px;"></i>
                    Add PS Case
                </a>
                <button id="exportCsvBtn" class="export-btn">
                    <i class="fas fa-download" style="margin-right: 8px;"></i>
                    Export CSV
                </button>
                <button onclick="window.print()" class="export-btn print-btn">
                    <i class="fas fa-print"></i> Print Page
                </button>
            </div>

            <!-- IMPROVED UPLOAD SECTION WITH SEARCHABLE DROPDOWN -->
            <div class="upload-container">
                <div class="upload-title">
                    <i class="fas fa-cloud-upload-alt" style="color: #0f172a;"></i>
                    Monthly Photo Report Upload
                </div>

                <!-- Quick Search for Clients -->
                <div class="quick-search">
                    <input type="text" id="quickClientSearch" class="quick-search-input" placeholder="🔍 Quick: Type client name or docket # to find and select...">
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
                                <i class="fas fa-user"></i> Select Client <span style="color: #ef4444;">*</span>
                            </label>
                            <select name="probationer_id" id="clientSelect" class="upload-select" required>
                                <option value="">🔍 Search for a client...</option>
                                <?php
                                // Get ALL clients regardless of status
                                $all_status_clients = mysqli_query($conn,"SELECT id, name, docket_number, status FROM clients ORDER BY name ASC");
                                while($c = mysqli_fetch_assoc($all_status_clients)){
                                    $status_color = '';
                                    $status_icon = '';
                                    
                                    if($c['status'] == 'Active') {
                                        $status_color = 'style="color: #059669;"';
                                        $status_icon = '🟢';
                                    } else if($c['status'] == 'Terminated') {
                                        $status_color = 'style="color: #0284c7;"';
                                        $status_icon = '🔵';
                                    } else if($c['status'] == 'Revoked') {
                                        $status_color = 'style="color: #d97706;"';
                                        $status_icon = '🟠';
                                    } else if($c['status'] == 'Denied') {
                                        $status_color = 'style="color: #dc2626;"';
                                        $status_icon = '🔴';
                                    }
                                    
                                    echo "<option value='{$c['id']}' data-status='{$c['status']}' {$status_color}>{$status_icon} {$c['name']} ({$c['docket_number']}) - [{$c['status']}]</option>";
                                }
                                ?>
                            </select>
                            <div class="file-name" id="selectedClientDisplay"></div>
                        </div>
                        
                        <div class="upload-group">
                            <label class="upload-label">
                                <i class="fas fa-calendar"></i> Month <span style="color: #ef4444;">*</span>
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
                                <i class="fas fa-calendar-alt"></i> Year <span style="color: #ef4444;">*</span>
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
                                <i class="fas fa-image"></i> Photo <span style="color: #ef4444;">*</span>
                            </label>
                            <div class="file-input-wrapper">
                                <input type="file" name="photo" accept="image/*" style="display: none;" id="photoInput">
                                <button type="button" class="upload-btn" onclick="document.getElementById('photoInput').click()" style="width: 100%;">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    Choose Photo
                                </button>
                                <div class="file-name" id="fileSelected">No file selected</div>
                            </div>
                        </div>
                        
                        <div class="upload-group">
                            <label class="upload-label">&nbsp;</label>
                            <button type="submit" name="upload_photo" class="upload-btn" style="width: 100%; background: #059669;">
                                <i class="fas fa-check"></i>
                                Upload Report
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Recent Uploads -->
            <?php if(mysqli_num_rows($recent_uploads) > 0): ?>
            <div style="margin-bottom: 2rem;">
                <div class="section-header">
                    <h2 class="section-title">Recent Uploads (All Statuses)</h2>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Probationer</th>
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
                                <td><?php echo date("M d, Y", strtotime($upload['upload_date'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Client List -->
            <div class="section-header">
                <h2 class="section-title">Complete Client List (All Statuses)</h2>
                <span id="tableResults" style="color:#64748b; font-size:0.9rem;"></span>
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
                            <th>Quick Upload</th>
                        </tr>
                    </thead>
                    <tbody id="clientTableBody">
                        <?php 
                        mysqli_data_seek($all_clients, 0);
                        while($row=mysqli_fetch_assoc($all_clients)){ 
                        ?>
                        <tr class="client-row">
                            <td><?php echo $row['docket_number'];?></td>
                            <td><?php echo $row['name'];?></td>
                            <td><?php echo $row['offense'];?></td>
                            <td><?php echo $row['court'];?></td>
                            <td><?php echo $row['address'];?></td>
                            <td>
                                <span class="status-badge status-<?php echo $row['status']; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="#" onclick="selectClientForUpload(<?php echo $row['id']; ?>, '<?php echo addslashes($row['name']); ?>', '<?php echo $row['docket_number']; ?>'); return false;" class="action-link upload-link">
                                    <i class="fas fa-camera"></i> Upload Now
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
            <div style="margin-bottom: 1rem;">
                <p id="modalDocket" style="margin-bottom: 0.5rem;"></p>
                <p id="modalName" style="margin-bottom: 0.5rem;"></p>
                <p id="modalOffense" style="margin-bottom: 0.5rem;"></p>
                <p id="modalCourt" style="margin-bottom: 0.5rem;"></p>
                <p id="modalStatus" style="margin-bottom: 0.5rem;"></p>
                <p id="modalAddress"></p>
            </div>
        </div>
    </div>

    <script>
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
                        hoverOffset: 4
                    }]
                },
                options: {
                    cutout: '65%',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { backgroundColor: '#1e293b' }
                    }
                }
            });

            // 2. Monthly Trend Chart
            const monthCtx = document.getElementById('monthChart').getContext('2d');
            const monthGradient = monthCtx.createLinearGradient(0, 0, 0, 200);
            monthGradient.addColorStop(0, 'rgba(59, 130, 246, 0.5)');
            monthGradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

            new Chart(monthCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($month_labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($month_data); ?>,
                        borderColor: '#3b82f6',
                        backgroundColor: monthGradient,
                        borderWidth: 3,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { 
                            display: true,
                            grid: { display: false },
                            ticks: { maxRotation: 45, minRotation: 45, font: { size: 9 } }
                        },
                        y: { 
                            display: true,
                            beginAtZero: true,
                            grid: { color: '#e2e8f0' },
                            ticks: { stepSize: 1, font: { size: 9 } }
                        }
                    }
                }
            });

            // 3. Barangay Chart
            const barColors = ['#f97316', '#8b5cf6', '#06b6d4', '#ec4899', '#14b8a6', '#f43f5e', '#6366f1'];
            new Chart(document.getElementById('barangayChart'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($barangay_labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($barangay_data); ?>,
                        backgroundColor: barColors.slice(0, <?php echo count($barangay_labels); ?>),
                        borderRadius: 6,
                        barPercentage: 0.7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { 
                            display: true,
                            grid: { display: false },
                            ticks: { maxRotation: 45, minRotation: 45, font: { size: 9 } }
                        },
                        y: { 
                            display: true,
                            beginAtZero: true,
                            grid: { color: '#e2e8f0' },
                            ticks: { stepSize: 1, font: { size: 9 } }
                        }
                    }
                }
            });

            // ============ SEARCHABLE DROPDOWN with SELECT2 ============
            if (typeof $.fn.select2 !== 'undefined') {
                $('#clientSelect').select2({
                    placeholder: '🔍 Type to search for a client...',
                    allowClear: true,
                    width: '100%',
                    theme: 'default',
                    matcher: function(params, data) {
                        // If there are no search terms, return all options
                        if ($.trim(params.term) === '') {
                            return data;
                        }

                        // Convert search term to lowercase
                        var term = params.term.toLowerCase();
                        
                        // Check if the option text contains the search term
                        if (data.text.toLowerCase().indexOf(term) > -1) {
                            return data;
                        }
                        
                        // Return null if no match
                        return null;
                    }
                });
            }

            // ============ QUICK CLIENT SEARCH ============
            $('#quickClientSearch').on('keyup', function() {
                var searchTerm = $(this).val();
                
                // Open Select2 dropdown and trigger search
                $('#clientSelect').select2('open');
                
                // Set the search term in Select2 search box
                setTimeout(function() {
                    $('.select2-search__field').val(searchTerm).trigger('keyup');
                }, 100);
            });

            // ============ STATUS FILTER FOR UPLOAD DROPDOWN ============
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
                
                // Refresh Select2 to reflect changes
                if (typeof $.fn.select2 !== 'undefined') {
                    $('#clientSelect').select2('destroy').select2({
                        placeholder: '🔍 Type to search for a client...',
                        allowClear: true,
                        width: '100%'
                    });
                }
            });

            // ============ SELECT CLIENT FUNCTION ============
            window.selectClientForUpload = function(id, name, docket) {
                // Set the select value
                $('#clientSelect').val(id).trigger('change');
                
                // Show confirmation
                $('#selectedClientDisplay').text('Selected: ' + name + ' (' + docket + ')').css('color', '#059669');
                
                // Scroll to upload section
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
                
                // Highlight the upload section
                $('.upload-container').css('border', '2px solid #059669').css('transition', 'border 0.5s');
                setTimeout(function() {
                    $('.upload-container').css('border', '1px solid #e2e8f0');
                }, 2000);
            };

            // ============ FILE INPUT DISPLAY ============
            document.getElementById('photoInput').addEventListener('change', function(e) {
                const fileName = e.target.files[0] ? e.target.files[0].name : 'No file selected';
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
            searchInput.addEventListener('keyup', function(e) {
                if (e.key === 'Escape') {
                    suggestionsDiv.style.display = 'none';
                }
            });

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
                            '"' + row.cells[2].textContent + '"',
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
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `clients_${new Date().toISOString().slice(0,10)}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
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
                    document.getElementById('modalOffense').textContent = 'Offense: ' + this.cells[2].textContent;
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

            let initialVisible = 0;
            tableRows.forEach(row => {
                if (row.style.display !== 'none') initialVisible++;
            });
            document.getElementById('tableResults').textContent = `Showing ${initialVisible} clients`;
        });
    </script>
</body>
</html>