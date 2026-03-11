<?php
session_start();
include(__DIR__ . "/config/database.php"); // Your database connection

if(!isset($_SESSION['username'])){
    header("Location: index.php");
    exit;
}

// Handle search query
$searchQuery = "";
if(isset($_GET['search'])){
    $searchQuery = trim($_GET['search']);
}

// Fetch clients with optional search
if($searchQuery != ""){
    $stmt = $conn->prepare("SELECT * FROM clients WHERE full_name LIKE ? ORDER BY created_at DESC");
    $likeSearch = "%".$searchQuery."%";
    $stmt->bind_param("s", $likeSearch);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
   $result = $conn->query("SELECT * FROM clients ORDER BY date_registered DESC");
}

$clients = [];
while($row = $result->fetch_assoc()){
    $clients[] = $row;
}

// Fetch totals for cards
$totalClientsResult = $conn->query("SELECT COUNT(*) AS total_clients FROM clients");
$totalClients = $totalClientsResult->fetch_assoc()['total_clients'] ?? 0;

$totalInvestigationsResult = $conn->query("SELECT COUNT(*) AS total_investigations FROM investigation_records");
$totalInvestigations = $totalInvestigationsResult->fetch_assoc()['total_investigations'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Inventory System Dashboard</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
<style>
body {margin:0; font-family: Arial, sans-serif; background:#f4f6f9;}
.sidebar {position:fixed; left:0; top:0; width:180px; height:100%; background:#2c3e50; padding-top:20px;}
.sidebar h2 {color:white; text-align:center; font-size:18px;}
.sidebar a {display:block; color:white; padding:10px; text-decoration:none;}
.sidebar a:hover {background:#34495e;}
.main {margin-left:180px; padding:20px;}
.header {background:white; padding:15px; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-radius:5px; margin-bottom:20px;}
.card-container {display:flex; gap:20px; margin-bottom:20px;}
.card {flex:1; background:white; padding:20px; border-radius:8px; box-shadow:0 0 5px rgba(0,0,0,0.1); text-align:center;}
.card h3 {margin:0; font-size:24px; color:#3498db;}
.card p {margin:5px 0 0 0; font-size:14px; color:#555;}
.table-container {background:white; padding:20px; border-radius:8px; box-shadow:0 0 5px rgba(0,0,0,0.1);}
table {width:100%; border-collapse:collapse; margin-top:10px;}
table th, table td {padding:10px; border-bottom:1px solid #ddd; text-align:left; cursor:pointer;}
table tr:hover {background:#f1f1f1;}
.search-bar {margin-bottom:15px;}
.search-bar input[type=text] {width:300px; padding:8px; border-radius:4px; border:1px solid #ccc;}
.search-bar button {padding:8px 12px; border:none; border-radius:4px; background:#3498db; color:white; cursor:pointer;}
.search-bar button:hover {background:#2980b9;}
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
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
    </div>

    <div class="card-container">
        <div class="card">
            <h3><?php echo $totalClients; ?></h3>
            <p>Total Clients</p>
        </div>
        <div class="card">
            <h3><?php echo $totalInvestigations; ?></h3>
            <p>Total Investigations</p>
        </div>
    </div>

    <div class="table-container">
        <h3>Clients</h3>

        <form class="search-bar" method="GET" action="">
            <input type="text" name="search" placeholder="Search by client name..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit">Search</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Case Number</th>
                    <th>Status</th>
                    <th>Date Registered</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($clients as $client): ?>
                
                <?php endforeach; ?>
                <?php if(empty($clients)) echo '<tr><td colspan="4" style="text-align:center;">No clients found</td></tr>'; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>