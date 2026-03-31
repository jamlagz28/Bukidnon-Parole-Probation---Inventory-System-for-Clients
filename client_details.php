<?php
session_start();
include 'includes/config.php';

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$client_id = $_GET['id'] ?? 0;
$user_role = $_SESSION['role'] ?? 'staff';
$can_edit = ($user_role == 'main' || $user_role == 'admin');
$fullname = $_SESSION['fullname'] ?? 'User';

// Get client details
$client_query = mysqli_query($conn, "SELECT * FROM clients WHERE id='$client_id'");
if(mysqli_num_rows($client_query) == 0) {
    header("Location: dashboard.php");
    exit();
}
$client = mysqli_fetch_assoc($client_query);

// Get PI cases ONLY for THIS client
$pi_cases = mysqli_query($conn, "SELECT * FROM pre_investigation WHERE client_id='$client_id' ORDER BY created_at DESC");

// Get PS cases ONLY for THIS client
$ps_cases = mysqli_query($conn, "SELECT * FROM probation_supervision WHERE client_id='$client_id' ORDER BY created_at DESC");

// Get monthly reports
$reports = mysqli_query($conn, "SELECT * FROM monthly_reports WHERE probationer_id='$client_id' ORDER BY report_year DESC, report_month DESC");

// Handle Add PS Case
if(isset($_POST['add_ps_quick']) && $can_edit) {
    $docket_number = mysqli_real_escape_string($conn, $_POST['docket_number']);
    $offense = mysqli_real_escape_string($conn, $_POST['offense']);
    $payment = floatval($_POST['payment']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $supervising_officer = mysqli_real_escape_string($conn, $_POST['supervising_officer']);
    $monthly_fee = floatval($_POST['monthly_fee']);
    
    $next_payment_date = date('Y-m-d', strtotime($start_date . ' +1 month'));
    
    $user_query = mysqli_query($conn, "SELECT id FROM staff WHERE username='{$_SESSION['username']}'");
    $user = mysqli_fetch_assoc($user_query);
    $created_by = $user['id'];
    
    mysqli_begin_transaction($conn);
    
    // Insert PS case
    $ps_query = "INSERT INTO probation_supervision (
        client_id, docket_number, name, offense, payment, address, 
        start_date, end_date, supervising_officer, status, monthly_fee, 
        next_payment_date, created_by
    ) VALUES (
        '$client_id', '$docket_number', '{$client['name']}', '$offense', '$payment', 
        '{$client['address']}', '$start_date', '$end_date', '$supervising_officer', 
        'Active', '$monthly_fee', '$next_payment_date', '$created_by'
    )";
    
    if(mysqli_query($conn, $ps_query)) {
        $ps_id = mysqli_insert_id($conn);
        
        // UPDATE CLIENT TO ACTIVE
        mysqli_query($conn, "UPDATE clients SET status = 'Active' WHERE id = '$client_id'");
        
        // Update any pending PI cases to Approved
        mysqli_query($conn, "UPDATE pre_investigation SET status = 'Approved' WHERE client_id = '$client_id' AND status = 'Pending'");
        
        mysqli_commit($conn);
        $success = "PS Case added successfully! Client is now ACTIVE.";
        
        // Refresh data
        $client_query = mysqli_query($conn, "SELECT * FROM clients WHERE id='$client_id'");
        $client = mysqli_fetch_assoc($client_query);
        $ps_cases = mysqli_query($conn, "SELECT * FROM probation_supervision WHERE client_id='$client_id' ORDER BY created_at DESC");
    } else {
        mysqli_rollback($conn);
        $error = "Error adding PS case: " . mysqli_error($conn);
    }
}

// Handle Edit PI Case
if(isset($_POST['edit_pi']) && $can_edit) {
    $pi_id = mysqli_real_escape_string($conn, $_POST['pi_id']);
    $docket_number = mysqli_real_escape_string($conn, $_POST['docket_number']);
    $offense = mysqli_real_escape_string($conn, $_POST['offense']);
    $investigator = mysqli_real_escape_string($conn, $_POST['investigator']);
    $date_filed = $_POST['date_filed'];
    $status = $_POST['status'];
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    
    $update_query = "UPDATE pre_investigation SET 
        docket_number = '$docket_number',
        offense = '$offense',
        investigator = '$investigator',
        date_filed = '$date_filed',
        status = '$status',
        remarks = '$remarks'
        WHERE id = '$pi_id' AND client_id = '$client_id'";
    
    if(mysqli_query($conn, $update_query)) {
        $success = "PI Case updated successfully!";
        // Refresh PI cases
        $pi_cases = mysqli_query($conn, "SELECT * FROM pre_investigation WHERE client_id='$client_id' ORDER BY created_at DESC");
    } else {
        $error = "Error updating PI case: " . mysqli_error($conn);
    }
}

// Handle Edit PS Case - with status options: Active, Terminated, Revoked, Denied
if(isset($_POST['edit_ps']) && $can_edit) {
    $ps_id = mysqli_real_escape_string($conn, $_POST['ps_id']);
    $docket_number = mysqli_real_escape_string($conn, $_POST['docket_number']);
    $offense = mysqli_real_escape_string($conn, $_POST['offense']);
    $payment = floatval($_POST['payment']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $supervising_officer = mysqli_real_escape_string($conn, $_POST['supervising_officer']);
    $status = $_POST['status'];
    $monthly_fee = floatval($_POST['monthly_fee']);
    
    mysqli_begin_transaction($conn);
    
    // Update PS case
    $update_query = "UPDATE probation_supervision SET 
        docket_number = '$docket_number',
        offense = '$offense',
        payment = '$payment',
        start_date = '$start_date',
        end_date = '$end_date',
        supervising_officer = '$supervising_officer',
        status = '$status',
        monthly_fee = '$monthly_fee',
        updated_at = NOW()
        WHERE id = '$ps_id' AND client_id = '$client_id'";
    
    if(mysqli_query($conn, $update_query)) {
        
        // Update client status based on this PS case status
        if($status == 'Active') {
            mysqli_query($conn, "UPDATE clients SET status = 'Active' WHERE id = '$client_id'");
        } elseif($status == 'Terminated' || $status == 'Revoked' || $status == 'Denied') {
            // Check if client has any other Active PS cases
            $check_active = mysqli_query($conn, "SELECT id FROM probation_supervision WHERE client_id = '$client_id' AND status = 'Active' AND id != '$ps_id'");
            if(mysqli_num_rows($check_active) == 0) {
                // No other active PS cases, set client to this status
                mysqli_query($conn, "UPDATE clients SET status = '$status' WHERE id = '$client_id'");
            }
        }
        
        mysqli_commit($conn);
        $success = "PS Case updated successfully!";
        
        // Refresh data
        $client_query = mysqli_query($conn, "SELECT * FROM clients WHERE id='$client_id'");
        $client = mysqli_fetch_assoc($client_query);
        $ps_cases = mysqli_query($conn, "SELECT * FROM probation_supervision WHERE client_id='$client_id' ORDER BY created_at DESC");
    } else {
        mysqli_rollback($conn);
        $error = "Error updating PS case: " . mysqli_error($conn);
    }
}

// Handle Edit Client Information
if(isset($_POST['edit_client']) && $can_edit) {
    $docket_number = mysqli_real_escape_string($conn, $_POST['docket_number']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $cc_number = mysqli_real_escape_string($conn, $_POST['cc_number']);
    $court = mysqli_real_escape_string($conn, $_POST['court']);
    $offense = mysqli_real_escape_string($conn, $_POST['offense']);
    $sentence = mysqli_real_escape_string($conn, $_POST['sentence']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number'] ?? '');
    
    $update_query = "UPDATE clients SET 
        docket_number = '$docket_number',
        name = '$name',
        cc_number = '$cc_number',
        court = '$court',
        offense = '$offense',
        sentence = '$sentence',
        address = '$address',
        phone_number = '$phone_number'
        WHERE id = '$client_id'";
    
    if(mysqli_query($conn, $update_query)) {
        $success = "Client information updated successfully!";
        // Refresh client data
        $client_query = mysqli_query($conn, "SELECT * FROM clients WHERE id='$client_id'");
        $client = mysqli_fetch_assoc($client_query);
    } else {
        $error = "Error updating client: " . mysqli_error($conn);
    }
}

$today = date('Y-m-d');
$next_year = date('Y-m-d', strtotime('+1 year'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Client Profile - <?php echo $client['name']; ?> | Bukidnon PPA</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* ========== RESET & GLOBAL ========== */
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
            overflow-x: hidden;
        }

        /* ========== COLOR VARIABLES ========== */
        :root {
            --dark-green: #1e4a3d;
            --dark-green-light: #2c6e5e;
            --yellow: #fbbf24;
            --yellow-dark: #f59e0b;
            --red: #dc2626;
            --red-light: #fee2e2;
            --gray-50: #f9fafb;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --white: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --transition: all 0.2s ease;
        }

        /* ========== SIDEBAR ========== */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--dark-green) 0%, #0f2c23 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            padding: 2rem 1.5rem;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            z-index: 100;
            transition: transform 0.3s ease;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 2.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            letter-spacing: -0.02em;
        }

        .logo i {
            color: var(--yellow);
            font-size: 1.8rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 1rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 0.5rem;
            transition: var(--transition);
            font-weight: 500;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        .nav-item.active {
            background: var(--yellow);
            color: var(--dark-green);
        }

        .nav-item i {
            width: 24px;
            text-align: center;
        }

        /* ========== MAIN CONTENT ========== */
        .main {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
        }

        /* ========== TOP BAR ========== */
        .top-bar {
            background: var(--white);
            border-radius: 20px;
            padding: 1rem 2rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            backdrop-filter: blur(4px);
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--dark-green), var(--dark-green-light));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-name {
            font-weight: 500;
            color: var(--gray-600);
        }

        .avatar {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--dark-green), var(--dark-green-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .logout-btn {
            color: var(--gray-400);
            transition: var(--transition);
        }
        .logout-btn:hover { color: var(--red); }

        /* ========== BACK LINK ========== */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-600);
            text-decoration: none;
            margin-bottom: 1.5rem;
            font-weight: 500;
            transition: var(--transition);
        }
        .back-link:hover { color: var(--dark-green); transform: translateX(-3px); }

        /* ========== MESSAGES ========== */
        .message {
            padding: 1rem 1.5rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease;
        }
        .message.success {
            background: #ecfdf5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .message.error {
            background: var(--red-light);
            color: #991b1b;
            border-left: 4px solid var(--red);
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ========== CLIENT PROFILE CARD ========== */
        .client-profile {
            background: var(--white);
            border-radius: 24px;
            overflow: hidden;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }
        .client-profile:hover { box-shadow: var(--shadow-lg); }

        .profile-header {
            background: linear-gradient(135deg, 
                <?php 
                if($client['status'] == 'Pending') echo '#f59e0b, #d97706';
                elseif($client['status'] == 'Active') echo '#10b981, #059669';
                elseif($client['status'] == 'Terminated') echo '#3b82f6, #2563eb';
                elseif($client['status'] == 'Revoked') echo '#f59e0b, #d97706';
                elseif($client['status'] == 'Denied') echo '#ef4444, #dc2626';
                else echo '#64748b, #475569';
                ?>);
            padding: 2rem 2rem;
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            color: white;
        }

        .profile-header::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 0;
            right: 0;
            height: 30px;
            background: linear-gradient(to bottom, rgba(0,0,0,0.05), transparent);
        }

        .profile-name {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .profile-docket {
            font-size: 1rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(4px);
        }

        .edit-profile-btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            padding: 0.6rem 1.2rem;
            border-radius: 12px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }
        .edit-profile-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .profile-body {
            padding: 2rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .info-section {
            background: var(--gray-50);
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid var(--gray-200);
            transition: var(--transition);
        }
        .info-section:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--dark-green);
            margin-bottom: 1.25rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--yellow);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .section-title i { color: var(--yellow-dark); }

        .info-row {
            display: flex;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background: var(--white);
            border-radius: 12px;
            align-items: center;
        }

        .info-label {
            width: 110px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .info-value {
            flex: 1;
            font-weight: 500;
            color: var(--gray-800);
        }

        /* ========== QUICK ADD PS FORM ========== */
        .quick-add-ps {
            background: linear-gradient(135deg, #fef9e3, #fff7e0);
            border: 2px dashed var(--yellow);
            border-radius: 24px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            transition: var(--transition);
        }
        .quick-add-ps h3 {
            color: var(--dark-green);
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .quick-add-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .quick-add-group {
            display: flex;
            flex-direction: column;
        }
        .quick-add-group label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--gray-600);
            margin-bottom: 0.25rem;
        }
        .quick-add-group input {
            padding: 0.7rem;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            transition: var(--transition);
        }
        .quick-add-group input:focus {
            outline: none;
            border-color: var(--yellow);
            box-shadow: 0 0 0 3px rgba(251,191,36,0.2);
        }
        .quick-add-btn {
            background: var(--dark-green);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 1rem;
        }
        .quick-add-btn:hover {
            background: var(--dark-green-light);
            transform: translateY(-2px);
        }

        /* ========== CARDS SECTIONS (PI/PS/Reports) ========== */
        .cards-section {
            margin-top: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .section-header h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark-green);
        }
        .view-all {
            color: var(--dark-green-light);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        .view-all:hover { color: var(--yellow-dark); }

        .table-container {
            background: var(--white);
            border-radius: 20px;
            overflow-x: auto;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        th {
            text-align: left;
            padding: 1rem 1.5rem;
            background: var(--gray-50);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-600);
            border-bottom: 1px solid var(--gray-200);
        }

        td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-100);
            font-size: 0.9rem;
        }

        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--gray-50); }

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 0.2rem 0.8rem;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-Pending, .status-For Review { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
        .status-Active, .status-Approved { background: #ecfdf3; color: #059669; border: 1px solid #a7f3d0; }
        .status-Terminated { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
        .status-Revoked { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
        .status-Denied { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        .action-link {
            color: var(--gray-600);
            text-decoration: none;
            margin-right: 0.75rem;
            font-size: 0.85rem;
            transition: var(--transition);
        }
        .action-link:hover { color: var(--dark-green); }
        .edit-link { color: var(--yellow-dark); cursor: pointer; }
        .edit-link:hover { color: var(--dark-green); }

        .photo-thumb {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.2s;
            border: 2px solid var(--gray-200);
        }
        .photo-thumb:hover { transform: scale(1.1); }

        .no-data {
            text-align: center;
            padding: 3rem;
            color: var(--gray-500);
            background: var(--gray-50);
            border-radius: 20px;
        }
        .no-data i { font-size: 2rem; margin-bottom: 0.5rem; color: var(--gray-400); }

        /* ========== MODAL ========== */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(4px);
        }
        .modal.active { display: flex; }
        .modal-content {
            background: var(--white);
            padding: 2rem;
            border-radius: 28px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            animation: modalPop 0.2s ease;
        }
        @keyframes modalPop {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .modal-header h2 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark-green);
        }
        .modal-close {
            font-size: 1.8rem;
            cursor: pointer;
            color: var(--gray-400);
            transition: var(--transition);
        }
        .modal-close:hover { color: var(--red); }
        .modal-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .modal-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .modal-form-group {
            display: flex;
            flex-direction: column;
        }
        .modal-form-group label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.25rem;
        }
        .modal-form-group input, .modal-form-group select, .modal-form-group textarea {
            padding: 0.7rem;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            transition: var(--transition);
        }
        .modal-form-group input:focus, .modal-form-group select:focus, .modal-form-group textarea:focus {
            outline: none;
            border-color: var(--yellow);
            box-shadow: 0 0 0 3px rgba(251,191,36,0.2);
        }
        .modal-btn {
            background: var(--dark-green);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1rem;
            transition: var(--transition);
        }
        .modal-btn:hover { background: var(--dark-green-light); }

        /* ========== ACTION BUTTONS ========== */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-200);
            flex-wrap: wrap;
        }
        .btn {
            padding: 0.7rem 1.5rem;
            border-radius: 40px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            cursor: pointer;
            border: none;
        }
        .btn-primary {
            background: var(--dark-green);
            color: white;
        }
        .btn-primary:hover {
            background: var(--dark-green-light);
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: var(--white);
            color: var(--gray-700);
            border: 1px solid var(--gray-200);
        }
        .btn-secondary:hover {
            background: var(--gray-50);
            border-color: var(--dark-green);
        }
        .print-btn {
            background: var(--gray-600);
            color: white;
        }
        .print-btn:hover {
            background: var(--gray-700);
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1024px) {
            .info-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 260px; }
            .sidebar.active { transform: translateX(0); }
            .main { margin-left: 0; padding: 1rem; }
            .info-grid { grid-template-columns: 1fr; }
            .modal-form-row { grid-template-columns: 1fr; }
            .top-bar { flex-direction: column; gap: 1rem; text-align: center; }
            .profile-header { flex-direction: column; gap: 1rem; }
        }
        @media print {
            .sidebar, .top-bar, .action-buttons, .no-print, .modal, .quick-add-ps, .back-link {
                display: none !important;
            }
            .main { margin-left: 0; padding: 0; }
            .client-profile { box-shadow: none; border: 1px solid #ddd; }
        }
    </style>
</head>
<body>
    <div class="app">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fas fa-scale-balanced"></i>
                <span>Bukidnon PPA</span>
            </div>
            <a href="dashboard.php" class="nav-item">Dashboard</a>
            <a href="clients.php" class="nav-item">Clients</a>
            <a href="monthly_reports.php" class="nav-item">Monthly Reports</a>
            <?php if($user_role == 'admin' || $user_role == 'main'): ?>
            <a href="staff_management.php" class="nav-item">Staff</a>
            <?php endif; ?>
        </div>

        <!-- Main Content -->
        <div class="main">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1 class="page-title">Client Profile</h1>
                <div class="user-menu">
                    <span class="user-name"><?php echo htmlspecialchars($fullname); ?></span>
                    <div class="avatar"><i class="fas fa-user"></i></div>
                    <button onclick="window.print()" class="btn print-btn"><i class="fas fa-print"></i> Print</button>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                </div>
            </div>

            <!-- Back Link -->
            <a href="javascript:history.back()" class="back-link"><i class="fas fa-arrow-left"></i> Back to previous page</a>

            <!-- Messages -->
            <?php if(isset($success)): ?><div class="message success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>
            <?php if(isset($error)): ?><div class="message error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div><?php endif; ?>

            <!-- Client Profile Card -->
            <div class="client-profile">
                <div class="profile-header">
                    <div>
                        <div class="profile-name"><?php echo htmlspecialchars($client['name']); ?></div>
                        <div class="profile-docket">
                            <span><i class="fas fa-hashtag"></i> <?php echo $client['docket_number']; ?></span>
                            <span class="status-badge"><?php echo $client['status']; ?></span>
                        </div>
                    </div>
                    <?php if($can_edit): ?>
                    <button class="edit-profile-btn" onclick="openModal('editClientModal')">
                        <i class="fas fa-edit"></i> Edit Profile
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="profile-body">
                    <div class="info-grid">
                        <!-- Personal Information -->
                        <div class="info-section">
                            <div class="section-title"><i class="fas fa-user-circle"></i> Personal Information</div>
                            <div class="info-row"><span class="info-label">CC Number:</span><span class="info-value"><?php echo $client['cc_number'] ?: 'N/A'; ?></span></div>
                            <div class="info-row"><span class="info-label">Phone:</span><span class="info-value"><?php echo $client['phone_number'] ?: 'N/A'; ?></span></div>
                            <div class="info-row"><span class="info-label">Address:</span><span class="info-value"><?php echo $client['address']; ?></span></div>
                            <div class="info-row"><span class="info-label">Court:</span><span class="info-value"><?php echo $client['court']; ?></span></div>
                        </div>

                        <!-- Case Information -->
                        <div class="info-section">
                            <div class="section-title"><i class="fas fa-gavel"></i> Case Information</div>
                            <div class="info-row"><span class="info-label">Offense:</span><span class="info-value"><?php echo $client['offense']; ?></span></div>
                            <div class="info-row"><span class="info-label">Sentence:</span><span class="info-value"><?php echo $client['sentence']; ?></span></div>
                            <div class="info-row"><span class="info-label">Date Added:</span><span class="info-value"><?php echo date('F d, Y', strtotime($client['created_at'])); ?></span></div>
                        </div>

                        <!-- Supervision Period -->
                        <div class="info-section">
                            <div class="section-title"><i class="fas fa-clock"></i> Supervision Period</div>
                            <?php $latest_ps = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM probation_supervision WHERE client_id='$client_id' ORDER BY created_at DESC LIMIT 1")); ?>
                            <div class="info-row"><span class="info-label">Start Date:</span><span class="info-value"><?php echo ($latest_ps) ? date('F d, Y', strtotime($latest_ps['start_date'])) : 'N/A'; ?></span></div>
                            <div class="info-row"><span class="info-label">End Date:</span><span class="info-value"><?php echo ($latest_ps) ? date('F d, Y', strtotime($latest_ps['end_date'])) : 'N/A'; ?></span></div>
                            <div class="info-row"><span class="info-label">Status:</span><span class="info-value"><span class="status-badge status-<?php echo $client['status']; ?>"><?php echo $client['status']; ?></span></span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Add PS Form for Pending Clients -->
            <?php if($client['status'] == 'Pending' && $can_edit): ?>
            <div class="quick-add-ps">
                <h3><i class="fas fa-plus-circle"></i> Activate Client – Add PS Case</h3>
                <form method="POST">
                    <div class="quick-add-grid">
                        <div class="quick-add-group"><label>Docket #</label><input type="text" name="docket_number" placeholder="PS-<?php echo date('Y'); ?>-001" required></div>
                        <div class="quick-add-group"><label>Offense</label><input type="text" name="offense" value="<?php echo $client['offense']; ?>" required></div>
                        <div class="quick-add-group"><label>Start Date</label><input type="date" name="start_date" value="<?php echo $today; ?>" required></div>
                        <div class="quick-add-group"><label>End Date</label><input type="date" name="end_date" value="<?php echo $next_year; ?>" required></div>
                        <div class="quick-add-group"><label>Officer</label><input type="text" name="supervising_officer" required></div>
                        <div class="quick-add-group"><label>Payment</label><input type="number" name="payment" value="0.00" step="0.01"></div>
                        <div class="quick-add-group"><label>Monthly Fee</label><input type="number" name="monthly_fee" value="500.00" step="0.01"></div>
                    </div>
                    <button type="submit" name="add_ps_quick" class="quick-add-btn"><i class="fas fa-check-circle"></i> Add PS Case & Activate</button>
                </form>
            </div>
            <?php endif; ?>

            <!-- PI Cases Section -->
            <div class="cards-section">
                <div class="section-header">
                    <h2><i class="fas fa-file-lines" style="color:#f59e0b;"></i> Pre-Investigation Cases</h2>
                    <?php if($can_edit && $client['status'] == 'Pending'): ?>
                        <a href="pi_add.php?client_id=<?php echo $client_id; ?>" class="view-all"><i class="fas fa-plus-circle"></i> Add PI</a>
                    <?php endif; ?>
                </div>

                <?php if(mysqli_num_rows($pi_cases) > 0): ?>
                    <div class="table-container">
                         <table>
                            <thead>
                                 <tr>
                                    <th>Docket #</th>
                                    <th>Offense</th>
                                    <th>Investigator</th>
                                    <th>Date Filed</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                 </tr>
                            </thead>
                            <tbody>
                                <?php while($pi = mysqli_fetch_assoc($pi_cases)): ?>
                                 <tr>
                                    <td><?php echo $pi['docket_number']; ?></td>
                                    <td><?php echo $pi['offense']; ?></td>
                                    <td><?php echo $pi['investigator']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($pi['date_filed'])); ?></td>
                                    <td><span class="status-badge status-<?php echo $pi['status']; ?>"><?php echo $pi['status']; ?></span></td>
                                    <td>
                                        <a href="pi_view.php?id=<?php echo $pi['id']; ?>" class="action-link"><i class="fas fa-eye"></i> View</a>
                                        <?php if($can_edit): ?>
                                        <span class="action-link edit-link" onclick='openEditPIModal(<?php echo json_encode($pi); ?>)'><i class="fas fa-edit"></i> Edit</span>
                                        <?php endif; ?>
                                    </td>
                                 </tr>
                                <?php endwhile; ?>
                            </tbody>
                         </table>
                    </div>
                <?php else: ?>
                    <div class="no-data"><i class="fas fa-folder-open"></i><p>No PI cases found</p></div>
                <?php endif; ?>
            </div>

            <!-- PS Cases Section -->
            <?php if(mysqli_num_rows($ps_cases) > 0): ?>
            <div class="cards-section">
                <div class="section-header">
                    <h2><i class="fas fa-gavel" style="color:#10b981;"></i> Probation Supervision Cases</h2>
                    <?php if($can_edit): ?>
                        <a href="ps_add.php?client_id=<?php echo $client_id; ?>" class="view-all"><i class="fas fa-plus-circle"></i> Add PS</a>
                    <?php endif; ?>
                </div>

                <div class="table-container">
                     <table>
                        <thead>
                             <tr>
                                <th>Docket #</th>
                                <th>Offense</th>
                                <th>Payment</th>
                                <th>Period</th>
                                <th>Status</th>
                                <th>Actions</th>
                             </tr>
                        </thead>
                        <tbody>
                            <?php while($ps = mysqli_fetch_assoc($ps_cases)): ?>
                             <tr>
                                <td><?php echo $ps['docket_number']; ?></td>
                                <td><?php echo $ps['offense']; ?></td>
                                <td>₱<?php echo number_format($ps['payment'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($ps['start_date'])); ?> – <?php echo date('M d, Y', strtotime($ps['end_date'])); ?></td>
                                <td><span class="status-badge status-<?php echo $ps['status']; ?>"><?php echo $ps['status']; ?></span></td>
                                <td>
                                    <a href="ps_view.php?id=<?php echo $ps['id']; ?>" class="action-link"><i class="fas fa-eye"></i> View</a>
                                    <?php if($can_edit): ?>
                                    <span class="action-link edit-link" onclick='openEditPSModal(<?php echo json_encode($ps); ?>)'><i class="fas fa-edit"></i> Edit</span>
                                    <?php endif; ?>
                                </td>
                             </tr>
                            <?php endwhile; ?>
                        </tbody>
                     </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Monthly Reports Section -->
            <div class="cards-section">
                <div class="section-header">
                    <h2><i class="fas fa-camera" style="color:#3b82f6;"></i> Monthly Reports</h2>
                    <a href="dashboard.php" class="view-all"><i class="fas fa-upload"></i> Upload</a>
                </div>

                <?php if(mysqli_num_rows($reports) > 0): ?>
                    <div class="table-container">
                         <table>
                            <thead>
                                 <tr>
                                    <th>Period</th>
                                    <th>Photo</th>
                                    <th>Upload Date</th>
                                 </tr>
                            </thead>
                            <tbody>
                                <?php while($report = mysqli_fetch_assoc($reports)): ?>
                                 <tr>
                                    <td><?php echo date("F Y", mktime(0,0,0,$report['report_month'],1,$report['report_year'])); ?></td>
                                    <td><img src="uploads/<?php echo $report['photo']; ?>" class="photo-thumb" onclick="window.open('uploads/<?php echo $report['photo']; ?>')"></td>
                                    <td><?php echo date("M d, Y", strtotime($report['upload_date'])); ?></td>
                                 </tr>
                                <?php endwhile; ?>
                            </tbody>
                         </table>
                    </div>
                <?php else: ?>
                    <div class="no-data"><i class="fas fa-camera"></i><p>No reports found</p></div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-chart-line"></i> Back to Dashboard</a>
                <?php if($can_edit): ?>
                    <button onclick="openModal('editClientModal')" class="btn btn-primary"><i class="fas fa-edit"></i> Edit Client</button>
                <?php endif; ?>
                <button onclick="window.print()" class="btn print-btn"><i class="fas fa-print"></i> Print Profile</button>
            </div>
        </div>
    </div>

    <!-- Edit Client Modal -->
    <div class="modal" id="editClientModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-edit"></i> Edit Client</h2>
                <span class="modal-close" onclick="closeModal('editClientModal')">&times;</span>
            </div>
            <form method="POST" class="modal-form">
                <div class="modal-form-group">
                    <label>Docket Number</label>
                    <input type="text" name="docket_number" value="<?php echo $client['docket_number']; ?>" required>
                </div>
                <div class="modal-form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?php echo $client['name']; ?>" required>
                </div>
                <div class="modal-form-row">
                    <div class="modal-form-group">
                        <label>CC Number</label>
                        <input type="text" name="cc_number" value="<?php echo $client['cc_number']; ?>">
                    </div>
                    <div class="modal-form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone_number" value="<?php echo $client['phone_number']; ?>" placeholder="e.g., 09123456789">
                    </div>
                </div>
                <div class="modal-form-row">
                    <div class="modal-form-group">
                        <label>Court</label>
                        <input type="text" name="court" value="<?php echo $client['court']; ?>" required>
                    </div>
                    <div class="modal-form-group">
                        <label>Address</label>
                        <input type="text" name="address" value="<?php echo $client['address']; ?>" required>
                    </div>
                </div>
                <div class="modal-form-group">
                    <label>Offense</label>
                    <textarea name="offense" required><?php echo $client['offense']; ?></textarea>
                </div>
                <div class="modal-form-group">
                    <label>Sentence</label>
                    <input type="text" name="sentence" value="<?php echo $client['sentence']; ?>" required>
                </div>
                <button type="submit" name="edit_client" class="modal-btn"><i class="fas fa-save"></i> Update Client</button>
            </form>
        </div>
    </div>

    <!-- Edit PI Modal -->
    <div class="modal" id="editPIModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-file-lines"></i> Edit PI Case</h2>
                <span class="modal-close" onclick="closeModal('editPIModal')">&times;</span>
            </div>
            <form method="POST" class="modal-form" id="editPIForm">
                <input type="hidden" name="pi_id" id="edit_pi_id">
                <div class="modal-form-group"><label>Docket Number</label><input type="text" name="docket_number" id="edit_pi_docket" required></div>
                <div class="modal-form-group"><label>Offense</label><textarea name="offense" id="edit_pi_offense" required></textarea></div>
                <div class="modal-form-row">
                    <div class="modal-form-group"><label>Investigator</label><input type="text" name="investigator" id="edit_pi_investigator" required></div>
                    <div class="modal-form-group"><label>Date Filed</label><input type="date" name="date_filed" id="edit_pi_date_filed" required></div>
                </div>
                <div class="modal-form-row">
                    <div class="modal-form-group"><label>Status</label>
                        <select name="status" id="edit_pi_status">
                            <option value="Pending">Pending</option>
                            <option value="For Review">For Review</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="modal-form-group"><label>Remarks</label><input type="text" name="remarks" id="edit_pi_remarks"></div>
                </div>
                <button type="submit" name="edit_pi" class="modal-btn"><i class="fas fa-save"></i> Update PI Case</button>
            </form>
        </div>
    </div>

    <!-- Edit PS Modal -->
    <div class="modal" id="editPSModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-gavel"></i> Edit PS Case</h2>
                <span class="modal-close" onclick="closeModal('editPSModal')">&times;</span>
            </div>
            <form method="POST" class="modal-form" id="editPSForm">
                <input type="hidden" name="ps_id" id="edit_ps_id">
                <div class="modal-form-group"><label>Docket Number</label><input type="text" name="docket_number" id="edit_ps_docket" required></div>
                <div class="modal-form-group"><label>Offense</label><textarea name="offense" id="edit_ps_offense" required></textarea></div>
                <div class="modal-form-row">
                    <div class="modal-form-group"><label>Payment</label><input type="number" name="payment" id="edit_ps_payment" step="0.01" required></div>
                    <div class="modal-form-group"><label>Monthly Fee</label><input type="number" name="monthly_fee" id="edit_ps_monthly_fee" step="0.01" required></div>
                </div>
                <div class="modal-form-row">
                    <div class="modal-form-group"><label>Start Date</label><input type="date" name="start_date" id="edit_ps_start_date" required></div>
                    <div class="modal-form-group"><label>End Date</label><input type="date" name="end_date" id="edit_ps_end_date" required></div>
                </div>
                <div class="modal-form-row">
                    <div class="modal-form-group"><label>Supervising Officer</label><input type="text" name="supervising_officer" id="edit_ps_officer" required></div>
                    <div class="modal-form-group"><label>Status</label>
                        <select name="status" id="edit_ps_status">
                            <option value="Active">Active</option>
                            <option value="Terminated">Terminated</option>
                            <option value="Revoked">Revoked</option>
                            <option value="Denied">Denied</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="edit_ps" class="modal-btn"><i class="fas fa-save"></i> Update PS Case</button>
            </form>
        </div>
    </div>

    <script>
        // Modal functions (unchanged)
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function openEditPIModal(pi) {
            document.getElementById('edit_pi_id').value = pi.id;
            document.getElementById('edit_pi_docket').value = pi.docket_number;
            document.getElementById('edit_pi_offense').value = pi.offense;
            document.getElementById('edit_pi_investigator').value = pi.investigator;
            document.getElementById('edit_pi_date_filed').value = pi.date_filed;
            document.getElementById('edit_pi_status').value = pi.status;
            document.getElementById('edit_pi_remarks').value = pi.remarks || '';
            openModal('editPIModal');
        }
        
        function openEditPSModal(ps) {
            document.getElementById('edit_ps_id').value = ps.id;
            document.getElementById('edit_ps_docket').value = ps.docket_number;
            document.getElementById('edit_ps_offense').value = ps.offense;
            document.getElementById('edit_ps_payment').value = ps.payment;
            document.getElementById('edit_ps_monthly_fee').value = ps.monthly_fee;
            document.getElementById('edit_ps_start_date').value = ps.start_date;
            document.getElementById('edit_ps_end_date').value = ps.end_date;
            document.getElementById('edit_ps_officer').value = ps.supervising_officer;
            document.getElementById('edit_ps_status').value = ps.status;
            openModal('editPSModal');
        }
        
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => {
                    modal.classList.remove('active');
                });
            }
        });

        // Optional: Mobile sidebar toggle (if needed)
        // No additional changes to backend logic.
    </script>
</body>
</html>