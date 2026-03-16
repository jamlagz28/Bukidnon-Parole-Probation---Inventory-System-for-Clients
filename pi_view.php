<?php
session_start();
include 'config/database.php';

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? 0;
$user_role = $_SESSION['role'] ?? 'staff';
$can_edit = ($user_role == 'main' || $user_role == 'admin');

// Get PI case details
$case = mysqli_query($conn, "SELECT * FROM pre_investigation WHERE id='$id'");
if(mysqli_num_rows($case) == 0) {
    header("Location: pi_list.php");
    exit();
}
$row = mysqli_fetch_assoc($case);
?>

<!DOCTYPE html>
<html>
<head>
    <title>PI Case Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Add your styles here - similar to dashboard */
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
        .action-buttons {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.95rem;
        }
        .btn-primary {
            background: #0f172a;
            color: white;
        }
        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }
        .btn-disabled {
            background: #e2e8f0;
            color: #94a3b8;
            pointer-events: none;
            opacity: 0.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>PI Case Details</h1>
            <?php if(!$can_edit): ?>
                <span class="view-only-badge">
                    <i class="fas fa-eye"></i> View Only Mode
                </span>
            <?php endif; ?>
        </div>
        
        <!-- Display case details -->
        <div class="details">
            <p><strong>Docket #:</strong> <?php echo $row['docket_number']; ?></p>
            <p><strong>Name:</strong> <?php echo $row['name']; ?></p>
            <p><strong>CC Number:</strong> <?php echo $row['cc_number']; ?></p>
            <p><strong>Court:</strong> <?php echo $row['court']; ?></p>
            <p><strong>Offense:</strong> <?php echo $row['offense']; ?></p>
            <p><strong>Sentence:</strong> <?php echo $row['sentence']; ?></p>
            <p><strong>Address:</strong> <?php echo $row['address']; ?></p>
            <p><strong>Status:</strong> <?php echo $row['status']; ?></p>
        </div>
        
        <div class="action-buttons">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            <?php if($can_edit): ?>
                <a href="pi_edit.php?id=<?php echo $id; ?>" class="btn btn-primary">Edit Case</a>
            <?php else: ?>
                <span class="btn btn-disabled">Edit (View Only)</span>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>s