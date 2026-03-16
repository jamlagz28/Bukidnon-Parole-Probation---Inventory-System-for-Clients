<?php
session_start();
include 'config/database.php';

// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Get user info
$fullname = $_SESSION['fullname'] ?? 'User';
$username = $_SESSION['username'] ?? '';
$user_role = $_SESSION['role'] ?? 'staff';

// Get all clients
$clients = mysqli_query($conn, "SELECT * FROM clients ORDER BY created_at DESC");

// Get status counts for summary
$active = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM clients WHERE status='Active'"))['total'];
$terminated = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM clients WHERE status='Terminated'"))['total'];
$revoked = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM clients WHERE status='Revoked'"))['total'];
$denied = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM clients WHERE status='Denied'"))['total'];

// Handle delete request
if(isset($_GET['delete']) && $user_role == 'main' || $user_role == 'admin') {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    mysqli_query($conn, "DELETE FROM clients WHERE id='$id'");
    header("Location: clients.php?msg=deleted");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Management - Parole & Probation System</title>
    
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

        .page-title {
            font-size: 1.25rem;
            font-weight: 500;
            color: #0f172a;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
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

        /* Actions Bar */
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .add-btn {
            background: #0f172a;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .add-btn:hover {
            background: #1e293b;
        }

        .search-box {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .search-input {
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            width: 300px;
            font-size: 0.95rem;
        }

        .filter-select {
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            color: #1e293b;
        }

        /* Message */
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

        /* Table */
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
            color: #1e293b;
            font-size: 0.95rem;
            border-bottom: 1px solid #f1f5f9;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #f8fafc;
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
        }

        .action-link:hover {
            color: #0f172a;
        }

        .action-link.delete:hover {
            color: #ef4444;
        }

        .results-count {
            color: #64748b;
            font-size: 0.9rem;
            margin-top: 1rem;
        }

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .main {
                margin-left: 0;
            }
            .actions-bar {
                flex-direction: column;
                gap: 1rem;
            }
            .search-box {
                width: 100%;
            }
            .search-input {
                width: 100%;
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
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
                <a href="clients.php" class="nav-item active">
                    <i class="fas fa-users"></i>
                    <span>Clients</span>
                </a>
                <a href="monthly_reports.php" class="nav-item">
                    <i class="fas fa-camera"></i>
                    <span>Monthly Reports</span>
                </a>
                <a href="pre_investigation.php" class="nav-item">
                    <i class="fas fa-file-lines"></i>
                    <span>Pre-Investigation</span>
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
            <!-- Top Bar -->
            <div class="top-bar">
                <h1 class="page-title">Client Management</h1>
                <div class="user-menu">
                    <span class="user-name"><?php echo htmlspecialchars($fullname); ?></span>
                    <div class="avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>

            <!-- Stats Summary -->
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

            <!-- Success Message -->
            <?php if(isset($_GET['msg'])): ?>
                <div class="message success">
                    <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                    Client <?php echo $_GET['msg'] == 'added' ? 'added' : ($_GET['msg'] == 'updated' ? 'updated' : 'deleted'); ?> successfully!
                </div>
            <?php endif; ?>

            <!-- Actions Bar -->
            <div class="actions-bar">
                <a href="add_probationer.php" class="add-btn">
                    <i class="fas fa-plus"></i>
                    Add New Client
                </a>
                
                <div class="search-box">
                    <input type="text" id="searchInput" class="search-input" placeholder="Search clients..." autocomplete="off">
                    <select id="statusFilter" class="filter-select">
                        <option value="all">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Terminated">Terminated</option>
                        <option value="Revoked">Revoked</option>
                        <option value="Denied">Denied</option>
                    </select>
                </div>
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
                    <tbody>
                        <?php if(mysqli_num_rows($clients) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($clients)): ?>
                            <tr class="client-row">
                                <td><?php echo htmlspecialchars($row['docket_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['offense']); ?></td>
                                <td><?php echo htmlspecialchars($row['court']); ?></td>
                                <td><?php echo htmlspecialchars($row['address']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit_probationer.php?id=<?php echo $row['id']; ?>" class="action-link" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="view_reports.php?id=<?php echo $row['id']; ?>" class="action-link" title="View Reports">
                                        <i class="fas fa-camera"></i>
                                    </a>
                                    <?php if($user_role == 'main' || $user_role == 'admin'): ?>
                                    <a href="clients.php?delete=<?php echo $row['id']; ?>" class="action-link delete" title="Delete" onclick="return confirm('Are you sure you want to delete this client?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem; color: #64748b;">
                                    No clients found. <a href="add_probationer.php" style="color: #3b82f6;">Add your first client</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Results Count -->
            <div class="results-count" id="resultsCount">
                Showing <?php echo mysqli_num_rows($clients); ?> clients
            </div>
        </div>
    </div>

    <script>
        // Live Search Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const rows = document.querySelectorAll('.client-row');
            const resultsCount = document.getElementById('resultsCount');

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const filterValue = statusFilter.value;
                let visibleCount = 0;

                rows.forEach(row => {
                    const docket = row.cells[0].textContent.toLowerCase();
                    const name = row.cells[1].textContent.toLowerCase();
                    const offense = row.cells[2].textContent.toLowerCase();
                    const court = row.cells[3].textContent.toLowerCase();
                    const address = row.cells[4].textContent.toLowerCase();
                    const status = row.cells[5].textContent.trim();

                    const matchesSearch = searchTerm === '' || 
                        docket.includes(searchTerm) || 
                        name.includes(searchTerm) || 
                        offense.includes(searchTerm) || 
                        court.includes(searchTerm) || 
                        address.includes(searchTerm);

                    const matchesFilter = filterValue === 'all' || status === filterValue;

                    if (matchesSearch && matchesFilter) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                resultsCount.textContent = `Showing ${visibleCount} of ${rows.length} clients`;
            }

            searchInput.addEventListener('input', filterTable);
            statusFilter.addEventListener('change', filterTable);

            // Initial count
            filterTable();
        });
    </script>
</body>
</html>