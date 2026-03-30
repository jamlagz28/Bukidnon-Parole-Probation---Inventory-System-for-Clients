<?php
session_start();
include 'config/database.php';

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'User';
$username = $_SESSION['username'] ?? '';
$user_role = $_SESSION['role'] ?? 'staff'; // FIXED: Added proper role retrieval

// Handle form submission
if(isset($_POST['add_investigation'])) {
    $case_number = mysqli_real_escape_string($conn, $_POST['case_number']);
    $client_name = mysqli_real_escape_string($conn, $_POST['client_name']);
    $offense = mysqli_real_escape_string($conn, $_POST['offense']);
    $date_received = $_POST['date_received'];
    $investigator = mysqli_real_escape_string($conn, $_POST['investigator']);
    $status = $_POST['status'];
    
    // Handle file upload
    $requirements = '';
    if(isset($_FILES['requirements']) && $_FILES['requirements']['error'] == 0) {
        $target_dir = "uploads/investigations/";
        if(!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_name = time() . '_' . basename($_FILES['requirements']['name']);
        $target_file = $target_dir . $file_name;
        if(move_uploaded_file($_FILES['requirements']['tmp_name'], $target_file)) {
            $requirements = $file_name;
        }
    }
    
    $query = "INSERT INTO investigation_records (case_number, client_name, offense, date_received, investigator, requirements_files, status) 
              VALUES ('$case_number', '$client_name', '$offense', '$date_received', '$investigator', '$requirements', '$status')";
    
    mysqli_query($conn, $query);
    header("Location: pre_investigation.php?msg=added");
    exit();
}

// Get all investigation records
$investigations = mysqli_query($conn, "SELECT * FROM investigation_records ORDER BY created_at DESC");

// Get counts
$pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM investigation_records WHERE status='Pending'"))['total'];
$approved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM investigation_records WHERE status='Approved'"))['total'];
$rejected = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM investigation_records WHERE status='Rejected'"))['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Pre-Investigation | Bukidnon PPA System</title>
    
    <!-- Google Fonts + Icons + Bootstrap 5 (for layout & components) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- AOS Animation (subtle scroll animations) -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        /* ---------- BUKIDNON PPA THEME: DARK GREEN | RED | YELLOW | WHITE ---------- */
        :root {
            --bpp-green-dark: #1e3a2f;    /* deep bukidnon green */
            --bpp-green-primary: #2b5e3f;
            --bpp-green-soft: #e9f3ee;
            --bpp-red: #b91c1c;
            --bpp-red-dark: #991b1b;
            --bpp-red-light: #fee2e2;
            --bpp-gold: #d4af37;
            --bpp-yellow: #fbbf24;
            --bpp-yellow-light: #fef9e3;
            --bpp-white: #ffffff;
            --bpp-gray-bg: #fefcf5;
            --bpp-border: #e9e5d8;
            --bpp-text-dark: #2c2b28;
            --bpp-text-muted: #6b6a66;
            --shadow-sm: 0 8px 20px rgba(0,0,0,0.03), 0 2px 6px rgba(0,0,0,0.05);
            --shadow-md: 0 12px 28px rgba(0,0,0,0.08);
            --shadow-hover: 0 18px 32px rgba(0,0,0,0.12);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #fffaf0 0%, #fef7e6 100%);
            color: var(--bpp-text-dark);
            overflow-x: hidden;
        }

        /* custom scroll */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f0ede5;
            border-radius: 8px;
        }
        ::-webkit-scrollbar-thumb {
            background: var(--bpp-green-primary);
            border-radius: 8px;
        }

        /* ========= SIDEBAR (elegant green & gold) ========= */
        .sidebar {
            width: 280px;
            background: var(--bpp-green-dark);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 4px 0 20px rgba(0,0,0,0.08);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar .logo {
            padding: 2rem 1.8rem 1.8rem 1.8rem;
            border-bottom: 1px solid rgba(255,215,0,0.2);
            margin-bottom: 1.5rem;
        }

        .logo h2 {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--bpp-white);
            letter-spacing: -0.3px;
        }

        .logo h2 i {
            color: var(--bpp-yellow);
            margin-right: 8px;
        }

        .logo p {
            font-size: 0.7rem;
            color: #cdd8c7;
            margin-top: 6px;
            letter-spacing: 1px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.85rem 1.8rem;
            margin: 0.3rem 1rem;
            color: #e2e8e0;
            text-decoration: none;
            border-radius: 40px;
            font-weight: 500;
            transition: all 0.25s;
        }

        .nav-item i {
            width: 24px;
            font-size: 1.2rem;
            text-align: center;
        }

        .nav-item:hover {
            background: rgba(212, 175, 55, 0.2);
            color: var(--bpp-yellow);
            transform: translateX(5px);
        }

        .nav-item.active {
            background: var(--bpp-yellow);
            color: var(--bpp-green-dark);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .nav-item.active i {
            color: var(--bpp-green-dark);
        }

        /* role badge */
        .role-badge-side {
            position: absolute;
            bottom: 2rem;
            left: 1.5rem;
            right: 1.5rem;
            background: rgba(255,255,240,0.08);
            border-radius: 40px;
            padding: 0.6rem 1rem;
            font-size: 0.75rem;
            color: #ffefb9;
            text-align: center;
            backdrop-filter: blur(4px);
        }

        /* ========= MAIN CONTENT ========= */
        .main-content {
            margin-left: 280px;
            padding: 2rem 2rem 2rem 2rem;
            transition: all 0.3s;
        }

        /* top bar modern */
        .top-bar-modern {
            background: var(--bpp-white);
            border-radius: 32px;
            padding: 0.9rem 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--bpp-border);
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 1.65rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--bpp-green-dark) 0%, var(--bpp-red) 100%);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            letter-spacing: -0.3px;
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }

        .page-title i {
            background: var(--bpp-red-light);
            padding: 10px;
            border-radius: 18px;
            color: var(--bpp-red);
            font-size: 1.2rem;
        }

        .user-info {
            background: var(--bpp-green-soft);
            padding: 0.45rem 1.2rem;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-info i {
            color: var(--bpp-green-primary);
            font-size: 1.1rem;
        }

        /* stats cards with theme */
        .stats-row {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .stat-card {
            flex: 1;
            min-width: 180px;
            background: var(--bpp-white);
            border-radius: 28px;
            padding: 1.5rem 1.2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--bpp-border);
            transition: transform 0.2s ease, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--bpp-gold);
            opacity: 0.6;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .stat-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            color: var(--bpp-text-muted);
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2.8rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .stat-sub {
            font-size: 0.75rem;
            color: #8b8a85;
        }

        /* form section (modern cards) */
        .form-card {
            background: var(--bpp-white);
            border-radius: 32px;
            padding: 1.8rem 2rem;
            margin-bottom: 2.2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--bpp-border);
            transition: all 0.2s;
        }

        .card-header-custom {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.8rem;
            border-left: 4px solid var(--bpp-red);
            padding-left: 1rem;
        }

        .card-header-custom i {
            font-size: 1.7rem;
            color: var(--bpp-red);
            background: var(--bpp-red-light);
            padding: 8px;
            border-radius: 18px;
        }

        .card-header-custom h3 {
            font-size: 1.4rem;
            font-weight: 600;
            margin: 0;
            color: var(--bpp-green-dark);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.4rem;
        }

        .form-group {
            margin-bottom: 0.6rem;
        }

        .full-width {
            grid-column: span 2;
        }

        label {
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: var(--bpp-text-muted);
            margin-bottom: 0.4rem;
            display: block;
        }

        input, select, textarea {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1.5px solid #ede8db;
            border-radius: 20px;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            transition: all 0.2s;
            background: #fefefb;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--bpp-gold);
            box-shadow: 0 0 0 3px rgba(212,175,55,0.2);
        }

        .btn-primary-custom {
            background: linear-gradient(95deg, var(--bpp-green-dark) 0%, var(--bpp-green-primary) 100%);
            color: white;
            border: none;
            padding: 0.9rem 2rem;
            border-radius: 40px;
            font-weight: 600;
            transition: all 0.25s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 1rem;
            box-shadow: 0 6px 14px rgba(43,94,63,0.25);
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            background: linear-gradient(95deg, var(--bpp-red-dark), var(--bpp-red));
            box-shadow: 0 12px 20px rgba(185,28,28,0.25);
        }

        /* table modern */
        .table-wrapper {
            background: var(--bpp-white);
            border-radius: 28px;
            padding: 0;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--bpp-border);
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .data-table th {
            background: #fef9ef;
            padding: 1.2rem 1rem;
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--bpp-green-dark);
            border-bottom: 2px solid var(--bpp-gold);
        }

        .data-table td {
            padding: 1rem 1rem;
            border-bottom: 1px solid #f1ede4;
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .data-table tr:hover td {
            background-color: #fffbf0;
            transition: 0.1s;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.3rem 1rem;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            backdrop-filter: blur(2px);
        }

        .status-Pending {
            background: var(--bpp-yellow-light);
            color: #b45f06;
            border-left: 3px solid var(--bpp-yellow);
        }

        .status-Approved {
            background: #e0f2e6;
            color: var(--bpp-green-primary);
            border-left: 3px solid var(--bpp-green-primary);
        }

        .status-Rejected {
            background: var(--bpp-red-light);
            color: var(--bpp-red);
            border-left: 3px solid var(--bpp-red);
        }

        .file-link {
            background: #f3f0e8;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 500;
            text-decoration: none;
            color: var(--bpp-green-dark);
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .file-link:hover {
            background: var(--bpp-gold);
            color: var(--bpp-green-dark);
            transform: scale(1.02);
        }

        /* alert modern */
        .alert-custom {
            background: linear-gradient(100deg, #eaf7e6, #fef7e0);
            border-left: 6px solid var(--bpp-gold);
            border-radius: 20px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeSlide 0.4s ease-out;
        }

        @keyframes fadeSlide {
            from { opacity: 0; transform: translateY(-12px);}
            to { opacity: 1; transform: translateY(0);}
        }

        /* empty state */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #a1a09b;
        }

        /* responsive adjustments */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.2s;
                z-index: 1050;
            }
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 1.5rem;
            }
            .menu-toggle {
                display: block;
                position: fixed;
                bottom: 1.5rem;
                right: 1.5rem;
                background: var(--bpp-red);
                color: white;
                border: none;
                border-radius: 60px;
                width: 52px;
                height: 52px;
                z-index: 1100;
                box-shadow: 0 6px 14px rgba(0,0,0,0.2);
                cursor: pointer;
            }
        }

        @media (min-width: 993px) {
            .menu-toggle {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .full-width {
                grid-column: span 1;
            }
            .stats-row {
                flex-direction: column;
            }
            .top-bar-modern {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>
<body>

<!-- Mobile toggle button -->
<button class="menu-toggle" id="mobileMenuToggle">
    <i class="fas fa-bars"></i>
</button>

<!-- SIDEBAR (bukidnon theme) -->
<div class="sidebar" id="sidebar">
    <div class="logo">
        <h2><i class="fas fa-leaf"></i> BPPA</h2>
        <p>Bukidnon Parole & Probation</p>
    </div>
    <a href="dashboard.php" class="nav-item"><i class="fas fa-chart-pie"></i> Dashboard</a>
    <a href="clients.php" class="nav-item"><i class="fas fa-users"></i> Clients</a>
    <a href="monthly_reports.php" class="nav-item"><i class="fas fa-camera"></i> Monthly Reports</a>
    <a href="pre_investigation.php" class="nav-item active"><i class="fas fa-file-lines"></i> Pre-Investigation</a>
    <div class="role-badge-side">
        <i class="fas fa-shield-alt"></i> Role: <strong><?php echo ucfirst($user_role); ?></strong>
    </div>
</div>

<!-- MAIN CONTENT AREA -->
<div class="main-content" id="mainContent">
    <!-- modern top bar -->
    <div class="top-bar-modern" data-aos="fade-down" data-aos-duration="400">
        <div class="page-title">
            <i class="fas fa-gavel"></i> 
            Pre‑Investigation Management
        </div>
        <div class="user-info">
            <i class="fas fa-user-check"></i>
            <span><?php echo htmlspecialchars($fullname); ?></span>
            <i class="fas fa-map-marker-alt" style="font-size: 0.7rem;"></i>
            <span class="badge bg-light text-dark">Bukidnon Field Office</span>
        </div>
    </div>

    <!-- success message animation -->
    <?php if(isset($_GET['msg'])): ?>
        <div class="alert-custom" data-aos="zoom-in" data-aos-duration="300">
            <i class="fas fa-check-circle fa-lg" style="color: var(--bpp-green-primary);"></i>
            <span>Investigation record added successfully! ✅ Case forwarded for review.</span>
        </div>
    <?php endif; ?>

    <!-- stats cards with theme -->
    <div class="stats-row" data-aos="fade-up" data-aos-delay="100">
        <div class="stat-card">
            <div class="stat-label"><i class="fas fa-hourglass-half me-1"></i> For Review</div>
            <div class="stat-value" style="color: #d97706;"><?php echo $pending; ?></div>
            <div class="stat-sub">pending investigation cases</div>
        </div>
        <div class="stat-card">
            <div class="stat-label"><i class="fas fa-check-circle"></i> Approved</div>
            <div class="stat-value" style="color: #2b5e3f;"><?php echo $approved; ?></div>
            <div class="stat-sub">ready for supervision</div>
        </div>
        <div class="stat-card">
            <div class="stat-label"><i class="fas fa-times-circle"></i> Rejected</div>
            <div class="stat-value" style="color: #b91c1c;"><?php echo $rejected; ?></div>
            <div class="stat-sub">further action needed</div>
        </div>
    </div>

    <!-- ADD INVESTIGATION CARD (enhanced form) -->
    <div class="form-card" data-aos="fade-up" data-aos-delay="150">
        <div class="card-header-custom">
            <i class="fas fa-folder-plus"></i>
            <h3>New Investigation Case</h3>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label><i class="fas fa-hashtag"></i> Case Number</label>
                    <input type="text" name="case_number" placeholder="e.g., INV-2025-001" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Client Name</label>
                    <input type="text" name="client_name" placeholder="Full name of client" required>
                </div>
                <div class="form-group full-width">
                    <label><i class="fas fa-balance-scale"></i> Offense / Allegation</label>
                    <textarea name="offense" rows="2" placeholder="Describe the offense or charges ..." required></textarea>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Date Received</label>
                    <input type="date" name="date_received" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-user-tie"></i> Investigator</label>
                    <input type="text" name="investigator" placeholder="PO / Officer name" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-flag-checkered"></i> Initial Status</label>
                    <select name="status">
                        <option value="Pending">Pending Review</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label><i class="fas fa-paperclip"></i> Attach Requirements (PDF/DOC/Image)</label>
                    <input type="file" name="requirements" accept=".pdf,.doc,.docx,.jpg,.png">
                    <small class="text-muted" style="font-size: 0.7rem;">Upload supporting documents (optional)</small>
                </div>
            </div>
            <button type="submit" name="add_investigation" class="btn-primary-custom">
                <i class="fas fa-save"></i> Submit Investigation Record
            </button>
        </form>
    </div>

    <!-- Investigation Records Table (modern clean) -->
    <div class="table-wrapper" data-aos="fade-up" data-aos-delay="200">
        <div style="padding: 1rem 1.5rem 0 1.5rem;">
            <h5 style="font-weight: 600; color: var(--bpp-green-dark);"><i class="fas fa-list-ul me-2" style="color: var(--bpp-red);"></i> All Investigation Cases</h5>
            <hr style="background-color: var(--bpp-gold); height: 2px; opacity: 0.5;">
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Case #</th>
                        <th>Client</th>
                        <th>Offense</th>
                        <th>Date Received</th>
                        <th>Investigator</th>
                        <th>Status</th>
                        <th>Requirements</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $hasData = false;
                    while($row = mysqli_fetch_assoc($investigations)): 
                        $hasData = true;
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['case_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                        <td><?php echo htmlspecialchars(mb_strimwidth($row['offense'], 0, 48, "...")); ?></td>
                        <td><?php echo date("M d, Y", strtotime($row['date_received'])); ?></td>
                        <td><?php echo htmlspecialchars($row['investigator']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $row['status']; ?>">
                                <i class="fas <?php echo $row['status'] == 'Pending' ? 'fa-clock' : ($row['status'] == 'Approved' ? 'fa-check-circle' : 'fa-ban'); ?>"></i>
                                <?php echo $row['status']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if($row['requirements_files']): ?>
                                <a href="uploads/investigations/<?php echo $row['requirements_files']; ?>" class="file-link" download target="_blank">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            <?php else: ?>
                                <span style="color: #bcb7aa;"><i class="far fa-file"></i> No file</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if(!$hasData): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="fas fa-folder-open fa-2x mb-2" style="color: #cfcabe;"></i><br>
                            No investigation records found. Start by adding a new case.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- subtle footer note -->
    <div class="text-center mt-4 mb-3">
        <small style="color: #b9b4a6;"><i class="fas fa-leaf me-1"></i> Bukidnon Parole and Probation System — Integrity • Accountability • Reform</small>
    </div>
</div>

<!-- AOS animation library + Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 500,
        once: true,
        offset: 10,
        easing: 'ease-out-cubic'
    });
    
    // Mobile sidebar toggle
    const toggleBtn = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('sidebar');
    if(toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('mobile-open');
            const icon = toggleBtn.querySelector('i');
            if(sidebar.classList.contains('mobile-open')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    }
    // close sidebar when clicking outside on mobile (optional)
    document.addEventListener('click', function(event) {
        if(window.innerWidth <= 992) {
            if(!sidebar.contains(event.target) && !toggleBtn.contains(event.target) && sidebar.classList.contains('mobile-open')) {
                sidebar.classList.remove('mobile-open');
                const icon = toggleBtn.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        }
    });
    // subtle animation on stat cards hover
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-5px)';
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
        });
    });
</script>
</body>
</html>