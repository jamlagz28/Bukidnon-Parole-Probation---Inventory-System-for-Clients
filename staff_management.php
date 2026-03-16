<?php
session_start();
include 'config/database.php';

// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Get current user info from session
$current_username = $_SESSION['username'];
$current_fullname = $_SESSION['fullname'] ?? 'User';
$current_role = $_SESSION['role'] ?? 'staff';

// Get current user ID from database
$user_query = mysqli_query($conn, "SELECT id FROM staff WHERE username='$current_username'");
$current_user = mysqli_fetch_assoc($user_query);
$current_user_id = $current_user['id'] ?? 0;

// Check if user has permission to manage staff (only admin/main)
$can_manage_staff = ($current_role == 'main' || $current_role == 'admin');

// Handle add staff (only for admin/main)
if(isset($_POST['add_staff']) && $can_manage_staff) {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    // Check if username exists
    $check = mysqli_query($conn, "SELECT id FROM staff WHERE username='$username'");
    if(mysqli_num_rows($check) > 0) {
        $error = "Username already exists!";
    } else {
        $query = "INSERT INTO staff (fullname, username, password, role) VALUES ('$fullname', '$username', '$password', '$role')";
        if(mysqli_query($conn, $query)) {
            $success = "Staff added successfully!";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

// Handle delete staff (only for admin/main)
if(isset($_GET['delete']) && $can_manage_staff) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    // Don't allow deleting yourself
    if($id != $current_user_id) {
        mysqli_query($conn, "DELETE FROM staff WHERE id='$id'");
        $success = "Staff deleted successfully!";
    }
    header("Location: staff_management.php");
    exit();
}

// Handle role update (only for admin/main)
if(isset($_POST['update_role']) && $can_manage_staff) {
    $staff_id = $_POST['staff_id'];
    $new_role = $_POST['new_role'];
    
    // Don't allow changing your own role
    if($staff_id != $current_user_id) {
        mysqli_query($conn, "UPDATE staff SET role='$new_role' WHERE id='$staff_id'");
        $success = "Role updated successfully!";
    }
    header("Location: staff_management.php");
    exit();
}

// Get all staff
$staff = mysqli_query($conn, "SELECT * FROM staff ORDER BY 
    CASE role 
        WHEN 'main' THEN 1 
        WHEN 'admin' THEN 2 
        ELSE 3 
    END, fullname ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - PPA System</title>
    
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

        .role-indicator {
            background: <?php echo $current_role == 'main' ? '#0f172a' : ($current_role == 'admin' ? '#3b82f6' : '#64748b'); ?>;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
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

        /* Messages */
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

        /* Permission Notice */
        .permission-notice {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .permission-notice i {
            font-size: 1.5rem;
            color: #64748b;
        }

        .permission-notice p {
            color: #475569;
            font-size: 0.95rem;
        }

        /* Add Section - Only visible to admin/main */
        .add-section {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .section-title {
            font-weight: 500;
            margin-bottom: 1.5rem;
            color: #0f172a;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #475569;
            font-weight: 500;
            font-size: 0.9rem;
        }

        input, select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .btn {
            background: #0f172a;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn:hover {
            background: #1e293b;
        }

        .btn-small {
            padding: 0.25rem 0.5rem;
            font-size: 0.85rem;
        }

        .btn-disabled {
            background: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
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

        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .role-main {
            background: #0f172a;
            color: white;
        }

        .role-admin {
            background: #3b82f6;
            color: white;
        }

        .role-staff {
            background: #e2e8f0;
            color: #475569;
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

        .action-link.disabled {
            color: #cbd5e1;
            cursor: not-allowed;
            pointer-events: none;
        }

        .current-user {
            background: #f0f9ff;
        }

        .current-user td {
            border-left: 3px solid #3b82f6;
        }

        .role-select {
            padding: 0.25rem;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .role-select:disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
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

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .main {
                margin-left: 0;
            }
            .form-grid {
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
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
                <a href="clients.php" class="nav-item">
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
                <a href="staff_management.php" class="nav-item active">
                    <i class="fas fa-user-tie"></i>
                    <span>Staff Management</span>
                </a>
                <a href="profile.php" class="nav-item">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1 class="page-title">Staff Management</h1>
                <div class="user-menu">
                    <span class="user-name"><?php echo htmlspecialchars($current_fullname); ?></span>
                    <span class="role-indicator">
                        <i class="fas fa-<?php echo $current_role == 'main' ? 'crown' : ($current_role == 'admin' ? 'shield' : 'eye'); ?>"></i>
                        <?php echo ucfirst($current_role); ?>
                    </span>
                    <div class="avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <a href="logout.php" style="color: #64748b; margin-left: 1rem;">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>

            <!-- Permission Notice for Staff Users -->
            <?php if(!$can_manage_staff): ?>
            <div class="permission-notice">
                <i class="fas fa-eye"></i>
                <div>
                    <strong>View Only Mode</strong>
                    <p>You are viewing this page as a staff member. You can see the staff list but cannot add, edit, or delete staff members.</p>
                </div>
            </div>
            <?php endif; ?>

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

            <!-- Add Staff Form - Only visible to admin/main -->
            <?php if($can_manage_staff): ?>
            <div class="add-section">
                <h3 class="section-title">Add New Staff Member</h3>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="fullname" placeholder="Enter full name" required>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" placeholder="Enter username" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" placeholder="Enter password" required>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role">
                                <option value="staff">Staff (View Only)</option>
                                <option value="admin">Admin (Full Access)</option>
                                <?php if($current_role == 'main'): ?>
                                <option value="main">Main Admin (Super User)</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_staff" class="btn">
                        <i class="fas fa-plus" style="margin-right: 8px;"></i>
                        Add Staff
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Staff List -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Access Level</th>
                            <?php if($can_manage_staff): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 0;
                        while($row = mysqli_fetch_assoc($staff)): 
                            $counter++;
                            $is_current = ($row['username'] == $current_username);
                        ?>
                        <tr class="<?php echo $is_current ? 'current-user' : ''; ?>">
                            <td>
                                <?php echo htmlspecialchars($row['fullname']); ?>
                                <?php if($is_current): ?>
                                    <span style="margin-left: 8px; font-size: 0.75rem; color: #3b82f6;">(You)</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $row['role']; ?>">
                                    <?php echo ucfirst($row['role']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if($row['role'] == 'main' || $row['role'] == 'admin'): ?>
                                    <span style="color: #059669;">
                                        <i class="fas fa-check-circle"></i> Full Access
                                    </span>
                                <?php else: ?>
                                    <span class="view-only-badge">
                                        <i class="fas fa-eye"></i> View Only
                                    </span>
                                <?php endif; ?>
                            </td>
                            <?php if($can_manage_staff): ?>
                            <td>
                                <?php if(!$is_current): ?>
                                    <!-- Role change dropdown for admin/main -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="staff_id" value="<?php echo $row['id']; ?>">
                                        <select name="new_role" class="role-select" onchange="this.form.submit()" style="margin-right: 0.5rem;">
                                            <option value="staff" <?php echo $row['role'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                                            <option value="admin" <?php echo $row['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            <?php if($current_role == 'main'): ?>
                                            <option value="main" <?php echo $row['role'] == 'main' ? 'selected' : ''; ?>>Main</option>
                                            <?php endif; ?>
                                        </select>
                                        <input type="hidden" name="update_role" value="1">
                                    </form>
                                    
                                    <a href="?delete=<?php echo $row['id']; ?>" class="action-link delete" onclick="return confirm('Are you sure you want to delete this staff member?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">
                                        <i class="fas fa-lock"></i> Current User
                                    </span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>

                        <?php if($counter == 0): ?>
                        <tr>
                            <td colspan="<?php echo $can_manage_staff ? '5' : '4'; ?>" style="text-align: center; padding: 2rem; color: #64748b;">
                                No staff members found. 
                                <?php if($can_manage_staff): ?>
                                    Add your first staff member above.
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Staff Summary -->
            <div style="margin-top: 1rem; display: flex; gap: 1rem; justify-content: flex-end; color: #64748b; font-size: 0.9rem;">
                <span><i class="fas fa-user-tie"></i> Total Staff: <?php echo $counter; ?></span>
                <span>
                    <i class="fas fa-<?php echo $current_role == 'main' ? 'crown' : ($current_role == 'admin' ? 'shield' : 'eye'); ?>"></i> 
                    Your Role: <?php echo ucfirst($current_role); ?> - 
                    <?php echo $can_manage_staff ? 'Full Access' : 'View Only'; ?>
                </span>
            </div>
        </div>
    </div>

    <script>
        // Auto-submit role change with confirmation (only for admin/main)
        <?php if($can_manage_staff): ?>
        document.querySelectorAll('.role-select').forEach(select => {
            select.addEventListener('change', function(e) {
                if(confirm('Change role for this staff member?')) {
                    this.form.submit();
                } else {
                    e.preventDefault();
                    this.value = this.getAttribute('data-original');
                }
            });
            
            // Store original value
            select.setAttribute('data-original', select.value);
        });
        <?php endif; ?>
    </script>
</body>
</html>