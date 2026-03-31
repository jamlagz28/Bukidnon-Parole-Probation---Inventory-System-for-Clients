<?php
session_start();
include 'includes/config.php';

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$q = $_GET['q'] ?? '';
$type = $_GET['type'] ?? 'all';

$fullname = $_SESSION['fullname'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Search Results | PPA System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ---------- RESET & GLOBAL ---------- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5faf5 0%, #eef3ec 100%);
            min-height: 100vh;
            color: #1a2f1a;
        }

        /* ---------- SIDEBAR (Refined) ---------- */
        .app {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a3c1a 0%, #0f2a0f 100%);
            backdrop-filter: blur(2px);
            border-right: none;
            padding: 2rem 1.5rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.08);
            z-index: 10;
        }

        .logo {
            font-size: 1.6rem;
            font-weight: 700;
            background: linear-gradient(135deg, #f5e7a3, #f0c674);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin-bottom: 2.5rem;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo:before {
            content: "⚖️";
            font-size: 1.8rem;
            background: none;
            -webkit-background-clip: unset;
            color: #f5c542;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            margin: 0.25rem 0;
            color: #e6f0e6;
            text-decoration: none;
            border-radius: 1rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .nav-item i {
            width: 1.6rem;
            font-size: 1.2rem;
            color: #cbdca8;
        }

        .nav-item:hover {
            background: rgba(240, 215, 110, 0.2);
            color: #ffe5a3;
            transform: translateX(4px);
        }

        /* ---------- MAIN CONTENT (Responsive) ---------- */
        .main {
            flex: 1;
            margin-left: 280px;
            padding: 1.8rem 2rem;
            width: calc(100% - 280px);
        }

        /* Top bar refined */
        .top-bar {
            background: #ffffff;
            border-radius: 1.5rem;
            padding: 1rem 2rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02), 0 1px 2px rgba(0, 32, 0, 0.05);
            border: 1px solid rgba(65, 105, 45, 0.15);
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            font-size: 1.4rem;
            font-weight: 600;
            background: linear-gradient(135deg, #1e5620, #2c6e2a);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            letter-spacing: -0.2px;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            background: #f7faf5;
            padding: 0.5rem 1.2rem;
            border-radius: 2rem;
            font-weight: 500;
            color: #2d572c;
        }

        /* Search Header Card */
        .search-header {
            background: white;
            border-radius: 2rem;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2efda;
        }

        .search-term {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(120deg, #1a4f1a, #3c7e3a);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin-bottom: 1.5rem;
            word-break: break-word;
        }

        /* Filter Tabs - Modern */
        .filter-tabs {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 0.5rem;
        }

        .filter-tab {
            padding: 0.6rem 1.6rem;
            border-radius: 3rem;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s;
            background: #f1f5ef;
            color: #4a6741;
            border: 1px solid #ddecd5;
        }

        .filter-tab:hover {
            background: #e3ecd9;
            transform: translateY(-1px);
        }

        .filter-tab.active {
            background: linear-gradient(105deg, #1f5420, #2e7a2b);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 10px rgba(35, 100, 30, 0.3);
        }

        /* Results Section Cards */
        .results-section {
            background: white;
            border-radius: 1.75rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            overflow: hidden;
            border: 1px solid #e2efda;
            transition: all 0.2s;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1.25rem 1.8rem;
            background: #fefef5;
            border-bottom: 2px solid #f0e7c6;
        }

        .section-title i {
            font-size: 1.4rem;
        }

        .section-title h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2a552a;
        }

        /* Result Items - Elegant & Spacious */
        .result-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.2rem 1.8rem;
            border-bottom: 1px solid #eff3ea;
            transition: all 0.2s;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .result-item:hover {
            background: #fafef5;
            transform: scale(1.01);
        }

        .result-item:last-child {
            border-bottom: none;
        }

        .result-info h4 {
            font-size: 1.05rem;
            font-weight: 600;
            color: #1a3c1a;
            margin-bottom: 0.35rem;
        }

        .result-info p {
            color: #668a5a;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* Badges with cohesive theme */
        .result-badge {
            padding: 0.3rem 1rem;
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .badge-client {
            background: #e6f0e3;
            color: #2d6a2d;
            border-left: 3px solid #2d6a2d;
        }

        .badge-pi {
            background: #fef3cf;
            color: #b46f0f;
            border-left: 3px solid #f5bc42;
        }

        .badge-ps {
            background: #ffe6e5;
            color: #bc3f2e;
            border-left: 3px solid #d9534f;
        }

        /* View Button - Modern */
        .view-btn {
            background: transparent;
            color: #2b6e2a;
            padding: 0.45rem 1.1rem;
            border-radius: 2rem;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
            border: 1px solid #cadec1;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .view-btn i {
            font-size: 0.7rem;
        }

        .view-btn:hover {
            background: #2b6e2a;
            color: white;
            border-color: #2b6e2a;
            transform: translateY(-1px);
        }

        .no-results {
            text-align: center;
            padding: 3.5rem 2rem;
            background: white;
            border-radius: 2rem;
            color: #6b8c5c;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
            border: 1px solid #e2efda;
        }

        /* Responsive breakpoints */
        @media (max-width: 992px) {
            .sidebar {
                width: 240px;
                padding: 1.5rem 1rem;
            }
            .main {
                margin-left: 240px;
                width: calc(100% - 240px);
                padding: 1.2rem;
            }
            .result-item {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 768px) {
            .app {
                flex-direction: column;
            }
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding: 1rem;
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                align-items: center;
                justify-content: space-between;
            }
            .logo {
                margin-bottom: 0;
                font-size: 1.3rem;
            }
            .nav-item {
                display: inline-flex;
                padding: 0.5rem 1rem;
                margin: 0;
            }
            .main {
                margin-left: 0;
                width: 100%;
                padding: 1rem;
            }
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            .search-term {
                font-size: 1.4rem;
            }
            .filter-tab {
                padding: 0.4rem 1.2rem;
                font-size: 0.8rem;
            }
            .section-title {
                padding: 1rem 1.2rem;
            }
            .result-item {
                padding: 1rem 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                flex-direction: column;
                align-items: stretch;
                gap: 0.75rem;
            }
            .nav-item {
                justify-content: center;
            }
            .filter-tabs {
                gap: 0.5rem;
            }
            .view-btn {
                padding: 0.4rem 1rem;
            }
        }

        /* extra touches */
        .stat-badge {
            background: #f0f2e9;
            border-radius: 40px;
            padding: 0.2rem 0.8rem;
            font-size: 0.7rem;
            font-weight: 500;
        }
        i.fa, i.far, i.fas {
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="app">
        <div class="sidebar">
            <div class="logo">PPA System</div>
            <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="clients.php" class="nav-item"><i class="fas fa-users"></i> Clients</a>
            <a href="pi_list.php" class="nav-item"><i class="fas fa-file-alt"></i> PI Cases</a>
            <a href="ps_list.php" class="nav-item"><i class="fas fa-gavel"></i> PS Cases</a>
        </div>

        <div class="main">
            <div class="top-bar">
                <div class="page-title">
                    <i class="fas fa-search" style="color: #3c7633;"></i> Search Results
                </div>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($fullname); ?>
                </div>
            </div>

            <div class="search-header">
                <div class="search-term">
                    <i class="fas fa-quote-left" style="font-size: 1.2rem; opacity: 0.7;"></i> 
                    <?php echo htmlspecialchars($q ?: "—"); ?> 
                    <i class="fas fa-quote-right" style="font-size: 1.2rem; opacity: 0.7;"></i>
                </div>
                
                <div class="filter-tabs">
                    <a href="?q=<?php echo urlencode($q); ?>&type=all" class="filter-tab <?php echo $type == 'all' ? 'active' : ''; ?>"><i class="fas fa-globe"></i> All</a>
                    <a href="?q=<?php echo urlencode($q); ?>&type=client" class="filter-tab <?php echo $type == 'client' ? 'active' : ''; ?>"><i class="fas fa-user-friends"></i> Clients</a>
                    <a href="?q=<?php echo urlencode($q); ?>&type=pi" class="filter-tab <?php echo $type == 'pi' ? 'active' : ''; ?>"><i class="fas fa-file-signature"></i> PI Cases</a>
                    <a href="?q=<?php echo urlencode($q); ?>&type=ps" class="filter-tab <?php echo $type == 'ps' ? 'active' : ''; ?>"><i class="fas fa-handcuffs"></i> PS Cases</a>
                </div>
            </div>

            <?php
            if(empty($q)) {
                echo '<div class="no-results"><i class="fas fa-search" style="font-size: 2.5rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i> Enter a search term to find clients or cases.</div>';
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
                                <i class="fas fa-users" style="color: #2c6e2a;"></i>
                                <h3>Clients <span style="font-size:0.8rem; background:#eaf5e5; padding:0.2rem 0.6rem; border-radius:30px;"><?php echo mysqli_num_rows($clients); ?></span></h3>
                            </div>
                            
                            <?php while($row = mysqli_fetch_assoc($clients)): ?>
                            <div class="result-item">
                                <div class="result-info">
                                    <h4><i class="fas fa-user-tie" style="color:#3f7640; width:1.4rem;"></i> <?php echo htmlspecialchars($row['name']); ?></h4>
                                    <p><i class="fas fa-hashtag"></i> Docket: <?php echo htmlspecialchars($row['docket_number']); ?> 
                                    <span class="stat-badge"><i class="fas fa-flag-checkered"></i> <?php echo htmlspecialchars($row['status']); ?></span></p>
                                </div>
                                <div>
                                    <span class="result-badge badge-client"><i class="fas fa-briefcase"></i> CLIENT</span>
                                    <a href="view_reports.php?id=<?php echo $row['id']; ?>" class="view-btn"><i class="fas fa-eye"></i> View</a>
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
                                <i class="fas fa-file-lines" style="color: #d4a11e;"></i>
                                <h3>Pre-Investigation Cases <span style="background:#fef0cf; border-radius:30px; padding:0.2rem 0.6rem;"><?php echo mysqli_num_rows($pi_cases); ?></span></h3>
                            </div>
                            
                            <?php while($row = mysqli_fetch_assoc($pi_cases)): ?>
                            <div class="result-item">
                                <div class="result-info">
                                    <h4><i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($row['name']); ?></h4>
                                    <p><i class="fas fa-gavel"></i> Docket: <?php echo htmlspecialchars($row['docket_number']); ?> | Status: <?php echo htmlspecialchars($row['status']); ?></p>
                                </div>
                                <div>
                                    <span class="result-badge badge-pi"><i class="fas fa-search"></i> PI</span>
                                    <a href="pi_view.php?id=<?php echo $row['id']; ?>" class="view-btn"><i class="fas fa-eye"></i> View</a>
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
                                <i class="fas fa-handcuffs" style="color: #c95a49;"></i>
                                <h3>Probation Supervision Cases <span style="background:#ffe4de; border-radius:30px; padding:0.2rem 0.6rem;"><?php echo mysqli_num_rows($ps_cases); ?></span></h3>
                            </div>
                            
                            <?php while($row = mysqli_fetch_assoc($ps_cases)): ?>
                            <div class="result-item">
                                <div class="result-info">
                                    <h4><i class="fas fa-chalkboard-user"></i> <?php echo htmlspecialchars($row['name']); ?></h4>
                                    <p><i class="fas fa-stamp"></i> Docket: <?php echo htmlspecialchars($row['docket_number']); ?> | Supervision: <?php echo htmlspecialchars($row['status']); ?></p>
                                </div>
                                <div>
                                    <span class="result-badge badge-ps"><i class="fas fa-balance-scale"></i> PS</span>
                                    <a href="ps_view.php?id=<?php echo $row['id']; ?>" class="view-btn"><i class="fas fa-eye"></i> View</a>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <?php
                    }
                }
                
                if(!$has_results) {
                    echo '<div class="no-results"><i class="fas fa-frown-open" style="font-size: 2.5rem; opacity: 0.5; margin-bottom: 1rem; display: block;"></i> No results found for "' . htmlspecialchars($q) . '"<br><span style="font-size:0.85rem;">Try adjusting your search or filter</span></div>';
                }
            }
            ?>
        </div>
    </div>
</body>
</html>
```