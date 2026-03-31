<?php
session_start();
include 'includes/config.php';

// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Check if client ID is provided
if(!isset($_GET['id'])) {
    header("Location: clients.php");
    exit();
}

$client_id = mysqli_real_escape_string($conn, $_GET['id']);

// Get client information
$client_query = mysqli_query($conn, "SELECT * FROM clients WHERE id='$client_id'");
if(mysqli_num_rows($client_query) == 0) {
    header("Location: clients.php");
    exit();
}
$client = mysqli_fetch_assoc($client_query);

// Get all reports for this client
$reports = mysqli_query($conn, "
    SELECT mr.*, s.fullname as uploaded_by_name 
    FROM monthly_reports mr 
    LEFT JOIN staff s ON mr.uploaded_by = s.id 
    WHERE mr.probationer_id = '$client_id' 
    ORDER BY mr.report_year DESC, mr.report_month DESC
");

// Get user info
$fullname = $_SESSION['fullname'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'staff';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Reports - <?php echo htmlspecialchars($client['name']); ?></title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
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
            background: #f8fafc;
            color: #1e293b;
        }

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

        /* Main Content */
        .main {
            flex: 1;
            margin-left: 260px;
            padding: 2rem;
        }

        /* Top Bar */
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

        .client-info {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .client-name {
            font-size: 1.25rem;
            font-weight: 500;
            color: #0f172a;
        }

        .client-docket {
            color: #64748b;
            font-size: 0.95rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-Active {
            background: #ecfdf3;
            color: #059669;
        }

        .back-btn {
            color: #64748b;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Stats Cards */
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
        }

        /* Photo Grid */
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .photo-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }

        .photo-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            cursor: pointer;
        }

        .photo-info {
            padding: 1rem;
        }

        .photo-month {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .photo-meta {
            font-size: 0.85rem;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .download-btn {
            color: #3b82f6;
            text-decoration: none;
        }

        .no-reports {
            text-align: center;
            padding: 3rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            color: #64748b;
        }

        /* Modal */
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
            object-fit: contain;
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 2rem;
            color: white;
            font-size: 2rem;
            cursor: pointer;
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
                <a href="pre_investigation.php" class="nav-item">
                    <i class="fas fa-file-lines"></i>
                    <span>Pre-Investigation</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="client-info">
                    <a href="clients.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back
                    </a>
                    <span class="client-name"><?php echo htmlspecialchars($client['name']); ?></span>
                    <span class="client-docket"><?php echo htmlspecialchars($client['docket_number']); ?></span>
                    <span class="status-badge status-<?php echo $client['status']; ?>">
                        <?php echo $client['status']; ?>
                    </span>
                </div>
                <div>
                    <span class="user-name"><?php echo htmlspecialchars($fullname); ?></span>
                </div>
            </div>

            <!-- Stats -->
            <?php
            $total_reports = mysqli_num_rows($reports);
            $latest_report = mysqli_fetch_assoc(mysqli_query($conn, "
                SELECT report_month, report_year FROM monthly_reports 
                WHERE probationer_id='$client_id' 
                ORDER BY report_year DESC, report_month DESC LIMIT 1
            "));
            ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Reports</div>
                    <div class="stat-value"><?php echo $total_reports; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Latest Report</div>
                    <div class="stat-value">
                        <?php 
                        if($latest_report) {
                            echo date("F Y", mktime(0,0,0,$latest_report['report_month'],1,$latest_report['report_year']));
                        } else {
                            echo "No reports";
                        }
                        ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Compliance</div>
                    <div class="stat-value">
                        <?php 
                        $months_since_start = ceil((time() - strtotime($client['start_date'])) / (30 * 24 * 60 * 60));
                        $expected = min($months_since_start, 12);
                        $percentage = $expected > 0 ? round(($total_reports / $expected) * 100) : 0;
                        echo $percentage . '%';
                        ?>
                    </div>
                </div>
            </div>

            <!-- Photo Grid -->
            <?php if(mysqli_num_rows($reports) > 0): ?>
                <div class="photo-grid">
                    <?php while($report = mysqli_fetch_assoc($reports)): ?>
                    <div class="photo-card">
                        <img src="uploads/<?php echo $report['photo']; ?>" class="photo-image" onclick="openModal('uploads/<?php echo $report['photo']; ?>')">
                        <div class="photo-info">
                            <div class="photo-month">
                                <?php echo date("F Y", mktime(0,0,0,$report['report_month'],1,$report['report_year'])); ?>
                            </div>
                            <div class="photo-meta">
                                <span>
                                    <i class="fas fa-user"></i>
                                    <?php echo $report['uploaded_by_name'] ?? 'Unknown'; ?>
                                </span>
                                <span>
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date("M d, Y", strtotime($report['upload_date'])); ?>
                                </span>
                                <a href="uploads/<?php echo $report['photo']; ?>" download class="download-btn">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-reports">
                    <i class="fas fa-camera" style="font-size: 3rem; margin-bottom: 1rem; color: #94a3b8;"></i>
                    <h3>No Monthly Reports Yet</h3>
                    <p style="margin-top: 0.5rem;">Upload the first monthly report for this client.</p>
                    <a href="dashboard.php" style="display: inline-block; margin-top: 1rem; color: #3b82f6; text-decoration: none;">
                        Go to Dashboard to Upload →
                    </a>
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
    </script>
</body>
</html>