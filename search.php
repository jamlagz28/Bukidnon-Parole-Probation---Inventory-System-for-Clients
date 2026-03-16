<?php
session_start();
include 'config/database.php';

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$q = $_GET['q'] ?? '';
$type = $_GET['type'] ?? 'all';

$fullname = $_SESSION['fullname'] ?? 'User';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Search Results</title>
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
        
        .search-header {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .search-term {
            font-size: 1.5rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 1rem;
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .filter-tab {
            padding: 0.5rem 1.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            text-decoration: none;
            color: #64748b;
            transition: all 0.2s;
        }
        
        .filter-tab:hover {
            background: #f1f5f9;
        }
        
        .filter-tab.active {
            background: #0f172a;
            color: white;
            border-color: #0f172a;
        }
        
        .results-section {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .section-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .result-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s;
        }
        
        .result-item:hover {
            background: #f8fafc;
        }
        
        .result-item:last-child {
            border-bottom: none;
        }
        
        .result-info h4 {
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }
        
        .result-info p {
            color: #64748b;
            font-size: 0.85rem;
        }
        
        .result-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-client { background: #dbeafe; color: #1e40af; }
        .badge-pi { background: #fed7aa; color: #92400e; }
        .badge-ps { background: #d1fae5; color: #065f46; }
        
        .view-btn {
            background: #f1f5f9;
            color: #475569;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
        }
        
        .view-btn:hover {
            background: #e2e8f0;
        }
        
        .no-results {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="app">
        <div class="sidebar">
            <div class="logo">PPA System</div>
            <a href="dashboard.php" class="nav-item">Dashboard</a>
            <a href="clients.php" class="nav-item">Clients</a>
            <a href="pi_list.php" class="nav-item">PI Cases</a>
            <a href="ps_list.php" class="nav-item">PS Cases</a>
        </div>

        <div class="main">
            <div class="top-bar">
                <h1 class="page-title">Search Results</h1>
                <span><?php echo $fullname; ?></span>
            </div>

            <div class="search-header">
                <div class="search-term">"<?php echo htmlspecialchars($q); ?>"</div>
                
                <div class="filter-tabs">
                    <a href="?q=<?php echo urlencode($q); ?>&type=all" class="filter-tab <?php echo $type == 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?q=<?php echo urlencode($q); ?>&type=client" class="filter-tab <?php echo $type == 'client' ? 'active' : ''; ?>">Clients</a>
                    <a href="?q=<?php echo urlencode($q); ?>&type=pi" class="filter-tab <?php echo $type == 'pi' ? 'active' : ''; ?>">PI Cases</a>
                    <a href="?q=<?php echo urlencode($q); ?>&type=ps" class="filter-tab <?php echo $type == 'ps' ? 'active' : ''; ?>">PS Cases</a>
                </div>
            </div>

            <?php
            if(empty($q)) {
                echo '<div class="no-results">Enter a search term to find clients or cases.</div>';
            } else {
                $q_escaped = mysqli_real_escape_string($conn, $q);
                $has_results = false;
                
                // Search Clients
                if($type == 'all' || $type == 'client') {
                    $clients = mysqli_query($conn, "SELECT * FROM clients WHERE name LIKE '%$q_escaped%' OR docket_number LIKE '%$q_escaped%'");
                    if(mysqli_num_rows($clients) > 0) {
                        $has_results = true;
                        ?>
                        <div class="results-section">
                            <div class="section-title">
                                <i class="fas fa-users" style="color: #3b82f6;"></i>
                                <h3>Clients (<?php echo mysqli_num_rows($clients); ?>)</h3>
                            </div>
                            
                            <?php while($row = mysqli_fetch_assoc($clients)): ?>
                            <div class="result-item">
                                <div class="result-info">
                                    <h4><?php echo $row['name']; ?></h4>
                                    <p>Docket: <?php echo $row['docket_number']; ?> | Status: <?php echo $row['status']; ?></p>
                                </div>
                                <div>
                                    <span class="result-badge badge-client">CLIENT</span>
                                    <a href="view_reports.php?id=<?php echo $row['id']; ?>" class="view-btn">View</a>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <?php
                    }
                }
                
                // Search PI Cases
                if($type == 'all' || $type == 'pi') {
                    $pi_cases = mysqli_query($conn, "SELECT * FROM pre_investigation WHERE name LIKE '%$q_escaped%' OR docket_number LIKE '%$q_escaped%'");
                    if(mysqli_num_rows($pi_cases) > 0) {
                        $has_results = true;
                        ?>
                        <div class="results-section">
                            <div class="section-title">
                                <i class="fas fa-file-lines" style="color: #f59e0b;"></i>
                                <h3>Pre-Investigation Cases (<?php echo mysqli_num_rows($pi_cases); ?>)</h3>
                            </div>
                            
                            <?php while($row = mysqli_fetch_assoc($pi_cases)): ?>
                            <div class="result-item">
                                <div class="result-info">
                                    <h4><?php echo $row['name']; ?></h4>
                                    <p>Docket: <?php echo $row['docket_number']; ?> | Status: <?php echo $row['status']; ?></p>
                                </div>
                                <div>
                                    <span class="result-badge badge-pi">PI</span>
                                    <a href="pi_view.php?id=<?php echo $row['id']; ?>" class="view-btn">View</a>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <?php
                    }
                }
                
                // Search PS Cases
                if($type == 'all' || $type == 'ps') {
                    $ps_cases = mysqli_query($conn, "SELECT * FROM probation_supervision WHERE name LIKE '%$q_escaped%' OR docket_number LIKE '%$q_escaped%'");
                    if(mysqli_num_rows($ps_cases) > 0) {
                        $has_results = true;
                        ?>
                        <div class="results-section">
                            <div class="section-title">
                                <i class="fas fa-gavel" style="color: #10b981;"></i>
                                <h3>Probation Supervision Cases (<?php echo mysqli_num_rows($ps_cases); ?>)</h3>
                            </div>
                            
                            <?php while($row = mysqli_fetch_assoc($ps_cases)): ?>
                            <div class="result-item">
                                <div class="result-info">
                                    <h4><?php echo $row['name']; ?></h4>
                                    <p>Docket: <?php echo $row['docket_number']; ?> | Status: <?php echo $row['status']; ?></p>
                                </div>
                                <div>
                                    <span class="result-badge badge-ps">PS</span>
                                    <a href="ps_view.php?id=<?php echo $row['id']; ?>" class="view-btn">View</a>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <?php
                    }
                }
                
                if(!$has_results) {
                    echo '<div class="no-results">No results found for "' . htmlspecialchars($q) . '"</div>';
                }
            }
            ?>
        </div>
    </div>
</body>
</html>