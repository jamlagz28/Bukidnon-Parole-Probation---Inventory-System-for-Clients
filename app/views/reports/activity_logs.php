<?php
session_start();
include '../../config/database.php';

if(!isset($_SESSION['username']) || ($_SESSION['role'] != 'main' && $_SESSION['role'] != 'admin')) {
    header("Location: dashboard.php");
    exit();
}

// Get logs
$logs = mysqli_query($conn, "
    SELECT al.*, s.fullname 
    FROM activity_logs al 
    JOIN staff s ON al.user_id = s.id 
    ORDER BY al.created_at DESC 
    LIMIT 500
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Activity Logs</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .app { display: flex; }
        .sidebar { width: 260px; background: white; border-right: 1px solid #e2e8f0; padding: 2rem; position: fixed; height: 100vh; }
        .main { flex: 1; margin-left: 260px; padding: 2rem; }
        .top-bar { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem 1.5rem; margin-bottom: 2rem; }
        .table-container { background: white; border: 1px solid #e2e8f0; border-radius: 12px; overflow: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 1rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        td { padding: 1rem; border-bottom: 1px solid #f1f5f9; }
        .log-action { font-weight: 500; color: #0f172a; }
        .log-time { color: #64748b; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="app">
        <div class="sidebar">[Sidebar same as before]</div>
        <div class="main">
            <div class="top-bar">
                <h1 class="page-title">Activity Logs</h1>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($log = mysqli_fetch_assoc($logs)): ?>
                        <tr>
                            <td class="log-time"><?php echo date("M d, Y h:i A", strtotime($log['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($log['fullname']); ?></td>
                            <td class="log-action"><?php echo htmlspecialchars($log['action']); ?></td>
                            <td><?php echo htmlspecialchars($log['details']); ?></td>
                            <td><?php echo $log['ip_address']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>