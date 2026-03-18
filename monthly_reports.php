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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Reports 2020-2030</title>
    
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
            color: #0f172a;
        }

        .page-subtitle {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
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
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 500;
            color: #0f172a;
        }

        .stat-note {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        .filter-section {
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .filter-select {
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            min-width: 150px;
            font-weight: 500;
        }

        .year-option {
            padding: 0.5rem;
        }

        .year-option.has-data {
            font-weight: 600;
            color: #0f172a;
        }

        .year-option.no-data {
            color: #94a3b8;
            font-style: italic;
        }

        .filter-info {
            color: #64748b;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .year-stats {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .year-pill {
            background: #f1f5f9;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .year-pill.has-data {
            background: #dbeafe;
            color: #1e40af;
        }

        .year-pill i {
            font-size: 0.7rem;
        }

        .table-container {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
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
            border-bottom: 1px solid #f1f5f9;
        }

        .report-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s;
            border: 1px solid #e2e8f0;
        }

        .report-thumb:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
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

        .year-badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            background: #f1f5f9;
            border-radius: 12px;
            font-size: 0.7rem;
            margin-left: 0.5rem;
            color: #475569;
        }

        .action-link {
            color: #64748b;
            text-decoration: none;
            margin: 0 0.5rem;
            font-size: 1rem;
            transition: color 0.2s;
        }

        .action-link:hover {
            color: #0f172a;
        }

        .action-link.delete {
            color: #dc2626;
        }

        .action-link.delete:hover {
            color: #991b1b;
        }

        .action-link.disabled {
            color: #cbd5e1;
            pointer-events: none;
            cursor: not-allowed;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal img {
            max-width: 90%;
            max-height: 90%;
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 2rem;
            color: white;
            font-size: 2rem;
            cursor: pointer;
        }

        .clear-filter {
            color: #3b82f6;
            text-decoration: none;
            font-size: 0.85rem;
            cursor: pointer;
        }

        .clear-filter:hover {
            text-decoration: underline;
        }

        .year-range-info {
            background: #f8fafc;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #475569;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .year-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 4px;
        }

        .dot-active {
            background: #10b981;
        }

        .dot-inactive {
            background: #cbd5e1;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .main {
                margin-left: 0;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="app">
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-scale-balanced"></i> PPA System
            </div>
            <a href="dashboard.php" class="nav-item"><i class="fas fa-chart-pie"></i> Dashboard</a>
            <a href="clients.php" class="nav-item"><i class="fas fa-users"></i> Clients</a>
            <a href="pi_list.php" class="nav-item"><i class="fas fa-file-lines"></i> PI Cases</a>
            <a href="ps_list.php" class="nav-item"><i class="fas fa-gavel"></i> PS Cases</a>
            <a href="monthly_reports.php" class="nav-item active"><i class="fas fa-camera"></i> Monthly Reports</a>
        </div>

        <div class="main">
            <div class="top-bar">
                <div>
                    <h1 class="page-title">Monthly Reports (2020-2030)</h1>
                    <div class="page-subtitle">
                        <i class="fas fa-calendar-alt"></i> 
                        Complete decade coverage
                    </div>
                </div>
                <div class="user-info">
                    <?php if(!$can_delete): ?>
                        <span class="view-only-badge">
                            <i class="fas fa-eye"></i> View Only
                        </span>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($fullname); ?></span>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> Image deleted successfully!
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'error'): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> Error deleting image.
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-image"></i> Total Reports
                    </div>
                    <div class="stat-value"><?php echo $total_reports; ?></div>
                    <div class="stat-note">across all years</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-calendar-check"></i> <?php echo $current_year; ?> Reports
                    </div>
                    <div class="stat-value"><?php echo $reports_this_year; ?></div>
                    <div class="stat-note">current year</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-chart-line"></i> Year Range
                    </div>
                    <div class="stat-value"><?php echo $min_year_db; ?> - <?php echo $max_year_db; ?></div>
                    <div class="stat-note">years with data</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-layer-group"></i> Active Years
                    </div>
                    <div class="stat-value"><?php echo $years_with_data; ?></div>
                    <div class="stat-note">out of 11 years</div>
                </div>
            </div>

            <!-- Year Range Visualization -->
            <div class="year-range-info">
                <span><i class="fas fa-calendar-alt"></i> Years with reports:</span>
                <div class="year-stats">
                    <?php foreach($years as $year): ?>
                        <?php $has_data = $year_counts[$year] > 0; ?>
                        <span class="year-pill <?php echo $has_data ? 'has-data' : ''; ?>" 
                              title="<?php echo $year; ?>: <?php echo $year_counts[$year]; ?> reports">
                            <span class="year-dot <?php echo $has_data ? 'dot-active' : 'dot-inactive'; ?>"></span>
                            <?php echo $year; ?>
                            <?php if($has_data): ?>
                                <span style="font-weight: 600;">(<?php echo $year_counts[$year]; ?>)</span>
                            <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <input type="text" id="searchInput" class="search-input" 
                       placeholder="🔍 Search by client name or docket number...">
                
                <select id="yearFilter" class="filter-select">
                    <option value="all">📅 All Years (2020-2030)</option>
                    <?php foreach($years as $year): ?>
                        <?php $has_data = $year_counts[$year] > 0; ?>
                        <option value="<?php echo $year; ?>" class="year-option <?php echo $has_data ? 'has-data' : 'no-data'; ?>">
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
                    <span id="resultCount"><?php echo $total_reports; ?></span> reports found
                    <span class="clear-filter" onclick="clearFilters()">
                        <i class="fas fa-times-circle"></i> Clear filters
                    </span>
                </div>
            </div>

            <!-- Reports Table -->
            <div class="table-container">
                <table id="reportsTable">
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
                                <img src="uploads/<?php echo $row['photo']; ?>" class="report-thumb" onclick="openModal('uploads/<?php echo $row['photo']; ?>')">
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
                                <a href="uploads/<?php echo $row['photo']; ?>" download class="action-link" title="Download">
                                    <i class="fas fa-download"></i>
                                </a>
                                <a href="client_details.php?id=<?php echo $row['probationer_id']; ?>" class="action-link" title="View Client">
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
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 3rem; color: #64748b;">
                                <i class="fas fa-camera" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: #94a3b8;"></i>
                                <p style="font-size: 1.1rem;">No reports found for 2020-2030</p>
                                <p style="margin-top: 0.5rem;">Upload your first report from the dashboard.</p>
                                <a href="dashboard.php" style="display: inline-block; margin-top: 1rem; padding: 0.5rem 1.5rem; background: #0f172a; color: white; text-decoration: none; border-radius: 8px;">
                                    <i class="fas fa-arrow-left"></i> Go to Dashboard
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Year Summary -->
            <?php if($total_reports > 0): ?>
            <div style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center; color: #64748b; font-size: 0.85rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <i class="fas fa-chart-bar"></i> 
                    Reports by year: 
                    <?php 
                    $active_years = [];
                    foreach($years as $year) {
                        if($year_counts[$year] > 0) {
                            $active_years[] = "$year ({$year_counts[$year]})";
                        }
                    }
                    echo implode(' • ', $active_years);
                    ?>
                </div>
                <div>
                    <i class="fas fa-database"></i> 
                    Total: <?php echo $total_reports; ?> reports | 
                    Range: <?php echo $min_year_db; ?>-<?php echo $max_year_db; ?>
                </div>
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
        function openModal(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('imageModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('imageModal').classList.remove('active');
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
            const searchTerm = searchInput.value.toLowerCase();
            const selectedYear = yearFilter.value;
            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const year = row.dataset.year;
                
                const matchesSearch = text.includes(searchTerm);
                const matchesYear = selectedYear === 'all' || year === selectedYear;

                if (matchesSearch && matchesYear) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Update result count
            resultCountSpan.textContent = visibleCount;
            
            // Show "no results" message if needed
            const tbody = document.querySelector('#reportsTable tbody');
            let noResultsRow = document.getElementById('noResultsRow');
            
            if (visibleCount === 0 && rows.length > 0) {
                if (!noResultsRow) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.id = 'noResultsRow';
                    noResultsRow.innerHTML = '<td colspan="8" style="text-align: center; padding: 2rem; color: #64748b;">📭 No reports match your filters for 2020-2030</td>';
                    tbody.appendChild(noResultsRow);
                }
            } else if (noResultsRow) {
                noResultsRow.remove();
            }
        }

        // Clear all filters
        window.clearFilters = function() {
            searchInput.value = '';
            yearFilter.value = 'all';
            filterTable();
        };

        // Add event listeners
        searchInput.addEventListener('input', filterTable);
        yearFilter.addEventListener('change', filterTable);

        // Initial filter
        filterTable();

        // Keyboard shortcut: ESC to clear filters when search is focused
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && this.value !== '') {
                this.value = '';
                filterTable();
            }
        });

        // Double-click on year pills to filter
        document.querySelectorAll('.year-pill').forEach(pill => {
            pill.addEventListener('dblclick', function() {
                const yearText = this.textContent.match(/\d{4}/);
                if (yearText) {
                    const year = yearText[0];
                    yearFilter.value = year;
                    filterTable();
                }
            });
        });
    </script>
</body>
</html>