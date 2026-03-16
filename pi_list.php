<?php
session_start();
include 'config/database.php';

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'staff';
$can_edit = ($user_role == 'main' || $user_role == 'admin');

// Get all PI cases
$pi_cases = mysqli_query($conn, "SELECT * FROM pre_investigation ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pre-Investigation Cases</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .app { display: flex; }
        .sidebar { width: 260px; background: white; border-right: 1px solid #e2e8f0; padding: 2rem; position: fixed; height: 100vh; }
        .main { flex: 1; margin-left: 260px; padding: 2rem; }
        .top-bar { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; }
        .page-title { font-size: 1.25rem; font-weight: 500; }
        .nav-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; color: #64748b; text-decoration: none; border-radius: 8px; }
        .nav-item:hover { background: #f1f5f9; }
        .nav-item.active { background: #f1f5f9; color: #0f172a; font-weight: 500; }
        .add-btn { background: #0f172a; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; display: inline-block; margin-bottom: 1rem; }
        .table-container { background: white; border: 1px solid #e2e8f0; border-radius: 12px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1000px; }
        th { text-align: left; padding: 1rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        td { padding: 1rem; border-bottom: 1px solid #f1f5f9; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem; }
        .status-Pending { background: #fffbeb; color: #d97706; }
        .status-Approved { background: #ecfdf3; color: #059669; }
        .status-Rejected { background: #fef2f2; color: #dc2626; }
        .status-For Review { background: #e0f2fe; color: #0284c7; }
        .action-link { color: #64748b; text-decoration: none; margin: 0 0.5rem; }
        .action-link:hover { color: #0f172a; }
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
            <a href="monthly_reports.php" class="nav-item">Reports</a>
        </div>

        <div class="main">
            <div class="top-bar">
                <h1 class="page-title">Pre-Investigation Cases</h1>
                <span><?php echo $fullname; ?></span>
            </div>

            <?php if($can_edit): ?>
            <a href="pi_add.php" class="add-btn">
                <i class="fas fa-plus"></i> Add New PI Case
            </a>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Docket #</th>
                            <th>Name</th>
                            <th>CC Number</th>
                            <th>Court</th>
                            <th>Offense</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($pi_cases)): ?>
                        <tr>
                            <td><?php echo $row['docket_number']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['cc_number']; ?></td>
                            <td><?php echo $row['court']; ?></td>
                            <td><?php echo substr($row['offense'], 0, 50); ?>...</td>
                            <td>
                                <span class="status-badge status-<?php echo str_replace(' ', '', $row['status']); ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="pi_view.php?id=<?php echo $row['id']; ?>" class="action-link"><i class="fas fa-eye"></i></a>
                                <?php if($can_edit): ?>
                                <a href="pi_edit.php?id=<?php echo $row['id']; ?>" class="action-link"><i class="fas fa-edit"></i></a>
                                <a href="pi_delete.php?id=<?php echo $row['id']; ?>" class="action-link" onclick="return confirm('Delete this case?')"><i class="fas fa-trash"></i></a>
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