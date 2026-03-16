<?php
session_start();
include 'config/database.php';

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$fullname = $_SESSION['fullname'];

// Get user data
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM staff WHERE username='$username'"));

// Handle password change
if(isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    if($current != $user['password']) {
        $error = "Current password is incorrect!";
    } elseif($new != $confirm) {
        $error = "New passwords do not match!";
    } else {
        mysqli_query($conn, "UPDATE staff SET password='$new' WHERE username='$username'");
        $success = "Password changed successfully!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .container { max-width: 500px; margin: 2rem auto; background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 2rem; }
        h1 { font-size: 1.5rem; margin-bottom: 2rem; }
        .profile-info { background: #f8fafc; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; }
        .info-item { margin-bottom: 1rem; }
        .info-label { color: #64748b; font-size: 0.9rem; }
        .info-value { font-weight: 500; margin-top: 0.25rem; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; color: #475569; font-weight: 500; }
        input { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; }
        .btn { background: #0f172a; color: white; padding: 0.75rem; border: none; border-radius: 8px; width: 100%; cursor: pointer; }
        .message { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .success { background: #ecfdf3; color: #065f46; }
        .error { background: #fef2f2; color: #991b1b; }
        .back-link { display: inline-block; margin-bottom: 1rem; color: #64748b; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        <h1>My Profile</h1>
        
        <div class="profile-info">
            <div class="info-item">
                <div class="info-label">Full Name</div>
                <div class="info-value"><?php echo htmlspecialchars($user['fullname']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Username</div>
                <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Role</div>
                <div class="info-value"><?php echo ucfirst($user['role']); ?></div>
            </div>
        </div>

        <?php if(isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <h3 style="margin-bottom:1rem;">Change Password</h3>
        <form method="POST">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" name="change_password" class="btn">Change Password</button>
        </form>
    </div>
</body>
</html>