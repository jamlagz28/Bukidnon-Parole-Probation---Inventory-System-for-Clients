<?php
session_start();
include(__DIR__ . "/config/database.php"); // Your DB connection

if(!isset($_SESSION['username'])){
    header("Location: index.php");
    exit;
}

// Get client ID from query parameter
if(!isset($_GET['id']) || empty($_GET['id'])){
    header("Location: dashboard.php");
    exit;
}

$clientId = $_GET['id'];

// Fetch client info
$stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->bind_param("i", $clientId);
$stmt->execute();
$clientResult = $stmt->get_result();
$client = $clientResult->fetch_assoc();

if(!$client){
    echo "Client not found!";
    exit;
}

// Fetch investigations related to this client
$investigationsStmt = $conn->prepare("SELECT * FROM investigation_records WHERE client_id = ? ORDER BY created_at DESC");
$investigationsStmt->bind_param("i", $clientId);
$investigationsStmt->execute();
$investigationsResult = $investigationsStmt->get_result();
$investigations = [];
while($row = $investigationsResult->fetch_assoc()){
    $investigations[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Client Details</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
<style>
body {margin:0; font-family: Arial, sans-serif; background:#f4f6f9;}
.sidebar {position:fixed; left:0; top:0; width:180px; height:100%; background:#2c3e50; padding-top:20px;}
.sidebar h2 {color:white; text-align:center; font-size:18px;}
.sidebar a {display:block; color:white; padding:10px; text-decoration:none;}
.sidebar a:hover {background:#34495e;}
.main {margin-left:180px; padding:20px;}
.header {background:white; padding:15px; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-radius:5px; margin-bottom:20px;}
.card {background:white; padding:20px; margin-bottom:20px; border-radius:8px; box-shadow:0 0 5px rgba(0,0,0,0.1);}
.card h3 {margin-top:0; color:#3498db;}
.table-container {background:white; padding:20px; border-radius:8px; box-shadow:0 0 5px rgba(0,0,0,0.1);}
table {width:100%; border-collapse:collapse; margin-top:10px;}
table th, table td {padding:10px; border-bottom:1px solid #ddd;}
table tr:hover {background:#f1f1f1;}
.back-btn {display:inline-block; margin-bottom:15px; padding:8px 12px; background:#3498db; color:white; text-decoration:none; border-radius:4px;}
.back-btn:hover {background:#2980b9;}
</style>
</head>
<body>

<div class="sidebar">
    <h2>Inventory</h2>
    <a href="dashboard.php"><i class="bx bxs-dashboard"></i> Dashboard</a>
    <a href="logout.php"><i class="bx bxs-log-out-circle"></i> Logout</a>
</div>

<div class="main">
    <div class="header">
        <h2>Client Details</h2>
    </div>

    <a href="dashboard.php" class="back-btn"><i class="bx bx-arrow-back"></i> Back to Dashboard</a>

    <div class="card">
        <h3><?php echo htmlspecialchars($client['full_name']); ?></h3>
        <p><strong>Case Number:</strong> <?php echo htmlspecialchars($client['case_number']); ?></p>
        <p><strong>Status:</strong> <?php echo htmlspecialchars($client['status']); ?></p>
        <p><strong>Date Registered:</strong> <?php echo htmlspecialchars($client['created_at']); ?></p>
        <p><strong>Additional Info:</strong> <?php echo htmlspecialchars($client['additional_info'] ?? 'N/A'); ?></p>
    </div>

    <div class="table-container">
        <h3>Investigation Records</h3>
        <table>
            <thead>
                <tr>
                    <th>Investigation ID</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Date Submitted</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($investigations)): ?>
                    <?php foreach($investigations as $inv): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($inv['id']); ?></td>
                        <td><?php echo htmlspecialchars($inv['description']); ?></td>
                        <td><?php echo htmlspecialchars($inv['status']); ?></td>
                        <td><?php echo htmlspecialchars($inv['created_at']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center;">No investigations found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>