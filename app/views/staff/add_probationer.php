<?php
session_start();
include '../../config/database.php';

// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Get user info
$fullname = $_SESSION['fullname'] ?? 'User';
$username = $_SESSION['username'] ?? '';
$user_role = $_SESSION['role'] ?? 'staff';

// Get user ID
$user_query = mysqli_query($conn, "SELECT id FROM staff WHERE username='$username'");
$user_data = mysqli_fetch_assoc($user_query);
$user_id = $user_data['id'] ?? 0;

// Handle form submission
if(isset($_POST['add_client'])) {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    // Get form data - ONLY PI FIELDS (NO start_date, end_date)
    $docket_number = mysqli_real_escape_string($conn, $_POST['docket_number']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $cc_number = mysqli_real_escape_string($conn, $_POST['cc_number']);
    $court = mysqli_real_escape_string($conn, $_POST['court']);
    $offense = mysqli_real_escape_string($conn, $_POST['offense']);
    $sentence = mysqli_real_escape_string($conn, $_POST['sentence']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $investigator = mysqli_real_escape_string($conn, $_POST['investigator']);
    $date_filed = $_POST['date_filed'];
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    
    // CHECK IF CLIENT ALREADY EXISTS (by name)
    $check_client = mysqli_query($conn, "SELECT id FROM clients WHERE name = '$name'");
    
    if(mysqli_num_rows($check_client) > 0) {
        // ========== EXISTING CLIENT ==========
        // Get the existing client ID
        $client_data = mysqli_fetch_assoc($check_client);
        $client_id = $client_data['id'];
        
        // Find if there's a PENDING PI case for this client
        $find_pi = mysqli_query($conn, "SELECT id FROM pre_investigation WHERE client_id = '$client_id' AND status = 'Pending'");
        
        if(mysqli_num_rows($find_pi) > 0) {
            // Get the pending PI case
            $pi_data = mysqli_fetch_assoc($find_pi);
            $pi_id = $pi_data['id'];
            
            // 1. Update PI case status to 'Approved' (removed from pending)
            $update_pi = "UPDATE pre_investigation SET status = 'Approved' WHERE id = '$pi_id'";
            mysqli_query($conn, $update_pi);
            
            // 2. Create PS case (ACTIVE) - with default dates
            $ps_docket = "PS-" . $docket_number;
            $monthly_fee = 500.00;
            $start_date = date('Y-m-d'); // Today
            $end_date = date('Y-m-d', strtotime('+1 year')); // One year from now
            
            $ps_query = "INSERT INTO probation_supervision (
                client_id, docket_number, name, offense, address, 
                start_date, end_date, status, monthly_fee, source_pi_id
            ) VALUES (
                '$client_id', '$ps_docket', '$name', '$offense', '$address',
                '$start_date', '$end_date', 'Active', '$monthly_fee', '$pi_id'
            )";
            
            if(mysqli_query($conn, $ps_query)) {
                $ps_id = mysqli_insert_id($conn);
                
                // 3. Update client with PS case ID and set status to Active
                mysqli_query($conn, "UPDATE clients SET ps_case_id = '$ps_id', status = 'Active' WHERE id = '$client_id'");
                
                mysqli_commit($conn);
                header("Location: clients.php?msg=converted_to_ps");
                exit();
            } else {
                mysqli_rollback($conn);
                $error = "Error creating PS case: " . mysqli_error($conn);
            }
        } else {
            // No pending PI found, create new PI case
            $pi_docket = "PI-" . $docket_number;
            
            $pi_query = "INSERT INTO pre_investigation (
                client_id, docket_number, name, cc_number, court, offense, 
                sentence, address, investigator, date_filed, status, remarks
            ) VALUES (
                '$client_id', '$pi_docket', '$name', '$cc_number', '$court', '$offense',
                '$sentence', '$address', '$investigator', '$date_filed', 'Pending', '$remarks'
            )";
            
            if(mysqli_query($conn, $pi_query)) {
                // Client status remains PENDING
                mysqli_commit($conn);
                header("Location: clients.php?msg=pi_added");
                exit();
            } else {
                mysqli_rollback($conn);
                $error = "Error creating PI case: " . mysqli_error($conn);
            }
        }
        
    } else {
        // ========== NEW CLIENT ==========
        // 1. Insert into clients table - NO start_date, end_date, status = 'Pending'
        $client_query = "INSERT INTO clients (docket_number, name, cc_number, court, offense, sentence, address, status) 
                         VALUES ('$docket_number', '$name', '$cc_number', '$court', '$offense', '$sentence', '$address', 'Pending')";
        
        if(mysqli_query($conn, $client_query)) {
            $client_id = mysqli_insert_id($conn);
            
            // 2. Create PI case (PENDING)
            $pi_docket = "PI-" . $docket_number;
            
            $pi_query = "INSERT INTO pre_investigation (
                client_id, docket_number, name, cc_number, court, offense, 
                sentence, address, investigator, date_filed, status, remarks
            ) VALUES (
                '$client_id', '$pi_docket', '$name', '$cc_number', '$court', '$offense',
                '$sentence', '$address', '$investigator', '$date_filed', 'Pending', '$remarks'
            )";
            
            if(mysqli_query($conn, $pi_query)) {
                $pi_id = mysqli_insert_id($conn);
                
                // 3. Update client with PI case ID
                mysqli_query($conn, "UPDATE clients SET pi_case_id = '$pi_id' WHERE id = '$client_id'");
                
                mysqli_commit($conn);
                header("Location: clients.php?msg=client_added");
                exit();
            } else {
                mysqli_rollback($conn);
                $error = "Error creating PI case: " . mysqli_error($conn);
            }
        } else {
            mysqli_rollback($conn);
            $error = "Error adding client: " . mysqli_error($conn);
        }
    }
}

$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
    <title>Add New Client - Probation Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-dark: #1e3a2f;      /* Dark green */
            --primary-green: #2d6a4f;      /* Medium green */
            --accent-yellow: #ffb703;       /* Yellow */
            --accent-red: #d62828;          /* Red */
            --white: #ffffff;                /* White */
            --off-white: #f8fafc;            /* Light background */
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--off-white) 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
            line-height: 1.5;
            color: var(--gray-800);
        }

        /* Container with responsive padding */
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            border: 1px solid rgba(30, 58, 47, 0.1);
        }

        /* Header section */
        .form-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-green) 100%);
            padding: 2rem;
            color: var(--white);
            position: relative;
        }

        .form-header h1 {
            font-size: clamp(1.5rem, 4vw, 2rem);
            font-weight: 600;
            margin: 0.5rem 0 0.25rem;
            letter-spacing: -0.02em;
        }

        .form-header .subtitle {
            font-size: 0.95rem;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--white);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            opacity: 0.9;
            transition: opacity 0.2s;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-lg);
            backdrop-filter: blur(5px);
        }

        .back-link:hover {
            opacity: 1;
            background: rgba(255, 255, 255, 0.2);
        }

        /* Info banner */
        .info-banner {
            background: linear-gradient(135deg, #fef9e7 0%, #fff4d6 100%);
            padding: 1.25rem 2rem;
            border-left: 4px solid var(--accent-yellow);
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            border-bottom: 1px solid rgba(255, 183, 3, 0.2);
        }

        .info-banner i {
            color: var(--accent-yellow);
            font-size: 1.25rem;
            margin-top: 0.125rem;
        }

        .info-content {
            flex: 1;
        }

        .info-title {
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
        }

        .info-text {
            color: var(--gray-600);
            font-size: 0.95rem;
        }

        /* Form container */
        .form-container {
            padding: 2rem;
        }

        /* Section titles */
        .section-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin: 2rem 0 1.5rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--accent-yellow);
        }

        .section-title i {
            color: var(--accent-yellow);
            font-size: 1.1rem;
        }

        .section-title:first-of-type {
            margin-top: 0;
        }

        /* Form grid layout */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
        }

        .form-group {
            margin-bottom: 0.25rem;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-700);
            font-size: 0.9rem;
            letter-spacing: 0.3px;
        }

        label i {
            color: var(--primary-green);
            margin-right: 0.5rem;
            width: 1rem;
            font-size: 0.9rem;
        }

        .required-field::after {
            content: "*";
            color: var(--accent-red);
            margin-left: 0.25rem;
            font-weight: 600;
        }

        input, select, textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid var(--gray-200);
            border-radius: var(--radius-lg);
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            transition: all 0.2s;
            background: var(--white);
            color: var(--gray-800);
        }

        input:hover, select:hover, textarea:hover {
            border-color: var(--gray-300);
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 4px rgba(45, 106, 79, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Form row for smaller screens */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        /* Error message */
        .error-message {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-left: 4px solid var(--accent-red);
            color: var(--gray-800);
            padding: 1rem 1.5rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .error-message i {
            color: var(--accent-red);
            font-size: 1.25rem;
        }

        /* Button styles */
        .form-actions {
            margin-top: 2.5rem;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 0.875rem 2rem;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            letter-spacing: 0.3px;
            min-width: 180px;
        }

        .btn i {
            font-size: 1rem;
        }

        .btn-primary {
            background: var(--primary-dark);
            color: var(--white);
            box-shadow: 0 4px 6px rgba(30, 58, 47, 0.2);
        }

        .btn-primary:hover {
            background: var(--primary-green);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: var(--white);
            color: var(--gray-700);
            border: 1.5px solid var(--gray-200);
        }

        .btn-secondary:hover {
            background: var(--gray-50);
            border-color: var(--gray-300);
            transform: translateY(-2px);
        }

        /* Responsive breakpoints */
        @media (max-width: 768px) {
            body {
                padding: 1rem 0.75rem;
            }

            .form-header {
                padding: 1.5rem;
            }

            .info-banner {
                padding: 1rem 1.5rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            .form-container {
                padding: 1.5rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .form-group.full-width {
                grid-column: span 1;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .btn {
                width: 100%;
                min-width: auto;
            }
        }

        @media (max-width: 480px) {
            .form-header h1 {
                font-size: 1.5rem;
            }

            .section-title {
                font-size: 1rem;
                margin: 1.5rem 0 1rem 0;
            }

            input, select, textarea {
                padding: 0.625rem 0.875rem;
                font-size: 0.9rem;
            }

            .info-banner {
                padding: 0.875rem 1.25rem;
            }
        }

        /* Dark mode support for users who prefer it */
        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(135deg, var(--gray-900) 0%, var(--gray-800) 100%);
            }

            .container {
                background: var(--gray-800);
                border-color: rgba(255, 255, 255, 0.1);
            }

            label {
                color: var(--gray-300);
            }

            input, select, textarea {
                background: var(--gray-700);
                border-color: var(--gray-600);
                color: var(--white);
            }

            input:focus, select:focus, textarea:focus {
                border-color: var(--primary-green);
                box-shadow: 0 0 0 4px rgba(45, 106, 79, 0.2);
            }

            .info-banner {
                background: linear-gradient(135deg, rgba(255, 183, 3, 0.1) 0%, rgba(255, 183, 3, 0.05) 100%);
            }

            .info-title, .info-text {
                color: var(--gray-300);
            }

            .section-title {
                color: var(--white);
            }
        }

        /* Print styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .container {
                box-shadow: none;
                border: 1px solid var(--gray-200);
            }

            .form-actions {
                display: none;
            }
        }

        /* Accessibility improvements */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Focus visible for keyboard navigation */
        :focus-visible {
            outline: 2px solid var(--primary-green);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header with gradient background -->
        <div class="form-header">
            <a href="clients.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Clients</span>
            </a>
            <h1><i class="fas fa-user-plus"></i> Add New Client</h1>
            <div class="subtitle">
                <i class="fas fa-file-alt"></i>
                <span>Create a new Pre-Investigation (PI) case</span>
            </div>
        </div>

        <!-- Info banner -->
        <div class="info-banner">
            <i class="fas fa-info-circle fa-lg"></i>
            <div class="info-content">
                <div class="info-title">PI Case Only</div>
                <div class="info-text">This form only adds client information and creates a PENDING PI case. PS details (Start Date, End Date) will be added automatically when converting to PS.</div>
            </div>
        </div>

        <!-- Form container -->
        <div class="form-container">
            <?php if(isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="addClientForm">
                <!-- Client Information Section -->
                <div class="section-title">
                    <i class="fas fa-user-circle"></i>
                    <span>Client Information (PI Case)</span>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="docket_number" class="required-field">
                            <i class="fas fa-hashtag"></i>Docket Number
                        </label>
                        <input type="text" id="docket_number" name="docket_number" 
                               placeholder="e.g., 2024-001" required>
                    </div>

                    <div class="form-group">
                        <label for="name" class="required-field">
                            <i class="fas fa-user"></i>Full Name
                        </label>
                        <input type="text" id="name" name="name" 
                               placeholder="Enter client's full name" required>
                    </div>

                    <div class="form-group">
                        <label for="cc_number">
                            <i class="fas fa-id-card"></i>CC Number
                        </label>
                        <input type="text" id="cc_number" name="cc_number" 
                               placeholder="Enter CC number if applicable">
                    </div>

                    <div class="form-group">
                        <label for="court" class="required-field">
                            <i class="fas fa-gavel"></i>Court
                        </label>
                        <input type="text" id="court" name="court" 
                               placeholder="e.g., Regional Trial Court" required>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="offense" class="required-field">
                        <i class="fas fa-balance-scale"></i>Offense
                    </label>
                    <textarea id="offense" name="offense" 
                              placeholder="Describe the offense in detail" required></textarea>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="sentence" class="required-field">
                            <i class="fas fa-clock"></i>Sentence
                        </label>
                        <input type="text" id="sentence" name="sentence" 
                               placeholder="e.g., 6 months probation" required>
                    </div>

                    <div class="form-group">
                        <label for="address" class="required-field">
                            <i class="fas fa-map-marker-alt"></i>Address
                        </label>
                        <input type="text" id="address" name="address" 
                               placeholder="Complete address" required>
                    </div>
                </div>

                <!-- Investigator Information Section -->
                <div class="section-title">
                    <i class="fas fa-user-tie"></i>
                    <span>Investigator Information</span>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="investigator" class="required-field">
                            <i class="fas fa-user-check"></i>Investigator
                        </label>
                        <input type="text" id="investigator" name="investigator" 
                               placeholder="Name of investigator" required>
                    </div>

                    <div class="form-group">
                        <label for="date_filed" class="required-field">
                            <i class="fas fa-calendar-alt"></i>Date Filed
                        </label>
                        <input type="date" id="date_filed" name="date_filed" 
                               value="<?php echo $today; ?>" required>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="remarks">
                        <i class="fas fa-comment"></i>Remarks
                    </label>
                    <textarea id="remarks" name="remarks" 
                              placeholder="Optional notes or additional information"></textarea>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='clients.php'">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" name="add_client" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create PI Case
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Optional: Add smooth scroll behavior -->
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>