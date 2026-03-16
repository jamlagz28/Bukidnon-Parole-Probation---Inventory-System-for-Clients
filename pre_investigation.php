<?php
session_start();
include 'config/database.php';

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'staff';

// Handle form submission
if(isset($_POST['add_investigation'])) {
    $case_number = mysqli_real_escape_string($conn, $_POST['case_number']);
    $client_name = mysqli_real_escape_string($conn, $_POST['client_name']);
    $offense = mysqli_real_escape_string($conn, $_POST['offense']);
    $date_received = $_POST['date_received'];
    $investigator = mysqli_real_escape_string($conn, $_POST['investigator']);
    $status = $_POST['status'];
    
    // Handle file upload
    $requirements = '';
    if(isset($_FILES['requirements']) && $_FILES['requirements']['error'] == 0) {
        $target_dir = "uploads/investigations/";
        if(!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_name = time() . '_' . basename($_FILES['requirements']['name']);
        $target_file = $target_dir . $file_name;
        if(move_uploaded_file($_FILES['requirements']['tmp_name'], $target_file)) {
            $requirements = $file_name;
        }
    }
    
    $query = "INSERT INTO investigation_records (case_number, client_name, offense, date_received, investigator, requirements_files, status) 
              VALUES ('$case_number', '$client_name', '$offense', '$date_received', '$investigator', '$requirements', '$status')";
    
    mysqli_query($conn, $query);
    header("Location: pre_investigation.php?msg=added");
    exit();
}

// Get all investigation records
$investigations = mysqli_query($conn, "SELECT * FROM investigation_records ORDER BY created_at DESC");

// Get counts
$pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM investigation_records WHERE status='Pending'"))['total'];
$approved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM investigation_records WHERE status='Approved'"))['total'];
$rejected = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM investigation_records WHERE status='Rejected'"))['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Investigation - PPA System</title>
    
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

        .add-section {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .add-title {
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #475569;
            font-size: 0.9rem;
            font-weight: 500;
        }

        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .btn {
            background: #0f172a;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
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

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-Pending {
            background: #fffbeb;
            color: #d97706;
        }

        .status-Approved {
            background: #ecfdf3;
            color: #059669;
        }

        .status-Rejected {
            background: #fef2f2;
            color: #dc2626;
        }

        .file-link {
            color: #3b82f6;
            text-decoration: none;
        }

        .file-link:hover {
            text-decoration: underline;
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
            <a href="monthly_reports.php" class="nav-item"><i class="fas fa-camera"></i> Monthly Reports</a>
            <a href="pre_investigation.php" class="nav-item active"><i class="fas fa-file-lines"></i> Pre-Investigation</a>
        </div>

        <div class="main">
            <div class="top-bar">
                <h1 class="page-title">Pre-Investigation</h1>
                <span><?php echo htmlspecialchars($fullname); ?></span>
            </div>

            <?php if(isset($_GET['msg'])): ?>
                <div style="background: #ecfdf3; color: #065f46; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    Investigation record added successfully!
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Pending</div>
                    <div class="stat-value" style="color: #d97706;"><?php echo $pending; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Approved</div>
                    <div class="stat-value" style="color: #059669;"><?php echo $approved; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Rejected</div>
                    <div class="stat-value" style="color: #dc2626;"><?php echo $rejected; ?></div>
                </div>
            </div>

            <!-- Add New Investigation Form -->
            <div class="add-section">
                <h3 class="add-title">Add New Investigation Case</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Case Number</label>
                            <input type="text" name="case_number" required>
                        </div>
                        <div class="form-group">
                            <label>Client Name</label>
                            <input type="text" name="client_name" required>
                        </div>
                        <div class="form-group full-width">
                            <label>Offense</label>
                            <textarea name="offense" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Date Received</label>
                            <input type="date" name="date_received" required>
                        </div>
                        <div class="form-group">
                            <label>Investigator</label>
                            <input type="text" name="investigator" required>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label>Requirements File</label>
                            <input type="file" name="requirements">
                        </div>
                    </div>
                    <button type="submit" name="add_investigation" class="btn">Add Investigation</button>
                </form>
            </div>

            <!-- Investigations Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Case #</th>
                            <th>Client</th>
                            <th>Offense</th>
                            <th>Date Received</th>
                            <th>Investigator</th>
                            <th>Status</th>
                            <th>Requirements</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($investigations)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['case_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['offense']); ?></td>
                            <td><?php echo date("M d, Y", strtotime($row['date_received'])); ?></td>
                            <td><?php echo htmlspecialchars($row['investigator']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $row['status']; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if($row['requirements_files']): ?>
                                    <a href="uploads/investigations/<?php echo $row['requirements_files']; ?>" class="file-link" download>
                                        <i class="fas fa-file"></i> Download
                                    </a>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">No file</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>