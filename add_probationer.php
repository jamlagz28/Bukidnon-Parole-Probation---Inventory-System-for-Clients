<?php
session_start();
include 'config/database.php';

if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if(isset($_POST['add_client'])) {
    $docket = mysqli_real_escape_string($conn, $_POST['docket_number']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $cc_number = mysqli_real_escape_string($conn, $_POST['cc_number']);
    $court = mysqli_real_escape_string($conn, $_POST['court']);
    $offense = mysqli_real_escape_string($conn, $_POST['offense']);
    $sentence = mysqli_real_escape_string($conn, $_POST['sentence']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];
    
    $query = "INSERT INTO clients (docket_number, name, cc_number, court, offense, sentence, address, start_date, end_date, status) 
              VALUES ('$docket', '$name', '$cc_number', '$court', '$offense', '$sentence', '$address', '$start_date', '$end_date', '$status')";
    
    if(mysqli_query($conn, $query)) {
        header("Location: clients.php?success=added");
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Client</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .container { max-width: 600px; margin: 2rem auto; background: white; padding: 2rem; border-radius: 12px; border: 1px solid #e2e8f0; }
        h2 { margin-bottom: 1.5rem; color: #0f172a; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; color: #475569; font-weight: 500; font-size: 0.9rem; }
        input, select, textarea { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; }
        button { background: #0f172a; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 8px; cursor: pointer; }
        .back { display: inline-block; margin-bottom: 1rem; color: #64748b; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        <h2>Add New Client</h2>
        <form method="POST">
            <div class="form-group">
                <label>Docket Number</label>
                <input type="text" name="docket_number" required>
            </div>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>CC Number</label>
                <input type="text" name="cc_number">
            </div>
            <div class="form-group">
                <label>Court</label>
                <input type="text" name="court" required>
            </div>
            <div class="form-group">
                <label>Offense</label>
                <textarea name="offense" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <label>Sentence</label>
                <input type="text" name="sentence" required>
            </div>
            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address" required>
            </div>
            <div class="form-group">
                <label>Start Date</label>
                <input type="date" name="start_date" required>
            </div>
            <div class="form-group">
                <label>End Date</label>
                <input type="date" name="end_date" required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="Active">Active</option>
                    <option value="Terminated">Terminated</option>
                    <option value="Revoked">Revoked</option>
                    <option value="Denied">Denied</option>
                </select>
            </div>
            <button type="submit" name="add_client">Add Client</button>
        </form>
    </div>
</body>
</html>