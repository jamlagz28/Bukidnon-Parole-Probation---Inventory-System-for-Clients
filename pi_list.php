<?php
session_start();
include 'config/database.php';
include 'includes/permissions.php';

// Check login
if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'staff';
$can_edit = canEdit($user_role);

// Get all PI cases
$pi_cases = mysqli_query($conn, "SELECT * FROM pre_investigation ORDER BY created_at DESC");

// Get counts
$pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pre_investigation WHERE status='Pending'"))['total'];
$approved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pre_investigation WHERE status='Approved'"))['total'];
$review = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pre_investigation WHERE status='For Review'"))['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PI Cases - PPA System</title>
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
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
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
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 500;
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
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .message.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .message.success {
            background: #ecfdf3;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #0f172a;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .table-container {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        th {
            text-align: left;
            padding: 1rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
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

        .status-For Review {
            background: #e0f2fe;
            color: #0284c7;
        }

        .action-link {
            color: #64748b;
            text-decoration: none;
            margin: 0 0.5rem;
        }

        .action-link:hover {
            color: #0f172a;
        }

        .action-link.disabled {
            color: #cbd5e1;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="app">
        <div class="sidebar">
            <div class="logo">PPA System</div>
            <a href="dashboard.php" class="nav-item">Dashboard</a>
            <a href="clients.php" class="nav-item">Clients</a>
            <a href="pi_list.php" class="nav-item active">PI Cases</a>
            <a href="ps_list.php" class="nav-item">PS Cases</a>
        </div>

        <div class="main">
            <div class="top-bar">
                <h1 class="page-title">Pre-Investigation Cases</h1>
                <div class="user-menu">
                    <?php if(!$can_edit): ?>
                        <span class="view-only-badge">
                            <i class="fas fa-eye"></i> View Only
                        </span>
                    <?php endif; ?>
                    <span><?php echo $fullname; ?></span>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>

            <!-- Error/Success Messages -->
            <?php if(isset($_GET['error']) && $_GET['error'] == 'unauthorized'): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    You don't have permission to perform that action. View only mode.
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'added'): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    PI Case added successfully!
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    PI Case updated successfully!
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    PI Case deleted successfully!
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Pending</div>
                    <div class="stat-value" style="color: #d97706;"><?php echo $pending; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">For Review</div>
                    <div class="stat-value" style="color: #0284c7;"><?php echo $review; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Approved</div>
                    <div class="stat-value" style="color: #059669;"><?php echo $approved; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total</div>
                    <div class="stat-value"><?php echo mysqli_num_rows($pi_cases); ?></div>
                </div>
            </div>

            <!-- Add Button - Only for admin/main -->
            <?php if($can_edit): ?>
                <a href="pi_add.php" class="btn">
                    <i class="fas fa-plus"></i> Add New PI Case
                </a>
            <?php endif; ?>

            <!-- Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Docket #</th>
                            <th>Name</th>
                            <th>CC Number</th>
                            <th>Court</th>
                            <th>Offense</th>
                            <th>Investigator</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($pi_cases) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($pi_cases)): ?>
                            <tr>
                                <td><?php echo $row['docket_number']; ?></td>
                                <td><?php echo $row['name']; ?></td>
                                <td><?php echo $row['cc_number']; ?></td>
                                <td><?php echo $row['court']; ?></td>
                                <td><?php echo substr($row['offense'], 0, 30); ?>...</td>
                                <td><?php echo $row['investigator']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo str_replace(' ', '', $row['status']); ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="pi_view.php?id=<?php echo $row['id']; ?>" class="action-link">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if($can_edit): ?>
                                        <a href="pi_edit.php?id=<?php echo $row['id']; ?>" class="action-link">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="pi_delete.php?id=<?php echo $row['id']; ?>" class="action-link" onclick="return confirm('Are you sure you want to delete this case?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="action-link disabled">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 2rem;">
                                    No PI cases found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>