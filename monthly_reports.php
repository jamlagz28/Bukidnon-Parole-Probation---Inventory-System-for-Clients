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
    ORDER BY mr.upload_date DESC
");

// Get summary stats
$total_reports = mysqli_num_rows($reports);
$reports_this_month = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total FROM monthly_reports 
    WHERE MONTH(upload_date) = MONTH(CURRENT_DATE()) 
    AND YEAR(upload_date) = YEAR(CURRENT_DATE())
"))['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Monthly Reports</title>
    
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
            grid-template-columns: repeat(3, 1fr);
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

        .search-bar {
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
        }

        .search-input {
            flex: 1;
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
        }

        .report-thumb:hover {
            transform: scale(1.1);
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

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .main {
                margin-left: 0;
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
                <h1 class="page-title">All Monthly Reports</h1>
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

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Reports</div>
                    <div class="stat-value"><?php echo $total_reports; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Reports This Month</div>
                    <div class="stat-value"><?php echo $reports_this_month; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Active Clients</div>
                    <div class="stat-value"><?php echo mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM clients WHERE status='Active'"))['total']; ?></div>
                </div>
            </div>

            <div class="search-bar">
                <input type="text" id="searchInput" class="search-input" placeholder="Search by client name or docket...">
                <select id="monthFilter" class="filter-select">
                    <option value="all">All Months</option>
                    <?php for($m=1;$m<=12;$m++): ?>
                        <option value="<?php echo $m; ?>"><?php echo date("F", mktime(0,0,0,$m,1)); ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="table-container">
                <table id="reportsTable">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Client</th>
                            <th>Docket #</th>
                            <th>Month/Year</th>
                            <th>Uploaded By</th>
                            <th>Upload Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($reports)): ?>
                        <tr class="report-row" data-month="<?php echo $row['report_month']; ?>">
                            <td>
                                <img src="uploads/<?php echo $row['photo']; ?>" class="report-thumb" onclick="openModal('uploads/<?php echo $row['photo']; ?>')">
                            </td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['docket_number']); ?></td>
                            <td><?php echo date("F Y", mktime(0,0,0,$row['report_month'],1,$row['report_year'])); ?></td>
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
                                <a href="view_reports.php?id=<?php echo $row['probationer_id']; ?>" class="action-link" title="View Client Reports">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                                
                                <!-- Delete button - Only visible to admin/main -->
                                <?php if($can_delete): ?>
                                <a href="?delete=<?php echo $row['id']; ?>" class="action-link delete" title="Delete" 
                                   onclick="return confirm('⚠️ Are you sure you want to delete this image?\n\nThis action cannot be undone!')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php else: ?>
                                <span class="action-link disabled" title="Delete (View Only)">
                                    <i class="fas fa-trash"></i>
                                </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if(mysqli_num_rows($reports) == 0): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem; color: #64748b;">
                                <i class="fas fa-camera" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                No reports found. Upload your first report from the dashboard.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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

        // Live search and filter
        document.getElementById('searchInput').addEventListener('input', filterTable);
        document.getElementById('monthFilter').addEventListener('change', filterTable);

        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const monthFilter = document.getElementById('monthFilter').value;
            const rows = document.querySelectorAll('.report-row');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const month = row.dataset.month;
                
                const matchesSearch = text.includes(searchTerm);
                const matchesMonth = monthFilter === 'all' || month === monthFilter;

                row.style.display = matchesSearch && matchesMonth ? '' : 'none';
            });
        }
    </script>
</body>
</html>