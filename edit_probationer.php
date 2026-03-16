<?php
session_start();
include 'config/database.php';

// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Get user role
$user_role = $_SESSION['role'] ?? 'staff';

// Check if ID is provided
if(!isset($_GET['id'])) {
    header("Location: clients.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);
$client = mysqli_query($conn, "SELECT * FROM clients WHERE id='$id'");

if(mysqli_num_rows($client) == 0) {
    header("Location: clients.php");
    exit();
}

$row = mysqli_fetch_assoc($client);

// Handle form submission
if(isset($_POST['update_client'])) {
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
    
    $query = "UPDATE clients SET 
              docket_number='$docket',
              name='$name',
              cc_number='$cc_number',
              court='$court',
              offense='$offense',
              sentence='$sentence',
              address='$address',
              start_date='$start_date',
              end_date='$end_date',
              status='$status'
              WHERE id='$id'";
    
    if(mysqli_query($conn, $query)) {
        header("Location: clients.php?msg=updated");
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Client - Parole & Probation System</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
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
        }

        .container {
            max-width: 600px;
            margin: 2rem auto;
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 500;
            color: #0f172a;
        }

        .back-link {
            color: #64748b;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }

        .back-link:hover {
            color: #0f172a;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #475569;
            font-weight: 500;
            font-size: 0.9rem;
        }

        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn {
            background: #0f172a;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            cursor: pointer;
            width: 100%;
            transition: background 0.2s;
        }

        .btn:hover {
            background: #1e293b;
        }

        .btn-secondary {
            background: white;
            color: #475569;
            border: 1px solid #e2e8f0;
            margin-top: 1rem;
        }

        .btn-secondary:hover {
            background: #f8fafc;
            color: #0f172a;
        }

        .error {
            background: #fef2f2;
            color: #991b1b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid #fecaca;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-left: 1rem;
        }

        .status-Active {
            background: #ecfdf3;
            color: #059669;
        }

        .status-Terminated {
            background: #e0f2fe;
            color: #0284c7;
        }

        .status-Revoked {
            background: #fffbeb;
            color: #d97706;
        }

        .status-Denied {
            background: #fef2f2;
            color: #dc2626;
        }

        @media (max-width: 640px) {
            .container {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                Edit Client
                <span class="status-badge status-<?php echo $row['status']; ?>">
                    <?php echo $row['status']; ?>
                </span>
            </h1>
            <a href="clients.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Clients
            </a>
        </div>

        <?php if(isset($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Docket Number</label>
                <input type="text" name="docket_number" value="<?php echo htmlspecialchars($row['docket_number']); ?>" required>
            </div>

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>CC Number</label>
                    <input type="text" name="cc_number" value="<?php echo htmlspecialchars($row['cc_number']); ?>">
                </div>

                <div class="form-group">
                    <label>Court</label>
                    <input type="text" name="court" value="<?php echo htmlspecialchars($row['court']); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Offense</label>
                <textarea name="offense" required><?php echo htmlspecialchars($row['offense']); ?></textarea>
            </div>

            <div class="form-group">
                <label>Sentence</label>
                <input type="text" name="sentence" value="<?php echo htmlspecialchars($row['sentence']); ?>" required>
            </div>

            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address" value="<?php echo htmlspecialchars($row['address']); ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?php echo $row['start_date']; ?>" required>
                </div>

                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?php echo $row['end_date']; ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="Active" <?php echo $row['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Terminated" <?php echo $row['status'] == 'Terminated' ? 'selected' : ''; ?>>Terminated</option>
                    <option value="Revoked" <?php echo $row['status'] == 'Revoked' ? 'selected' : ''; ?>>Revoked</option>
                    <option value="Denied" <?php echo $row['status'] == 'Denied' ? 'selected' : ''; ?>>Denied</option>
                </select>
            </div>

            <button type="submit" name="update_client" class="btn">
                <i class="fas fa-save" style="margin-right: 8px;"></i>
                Update Client
            </button>

            <a href="clients.php" class="btn btn-secondary" style="display: block; text-align: center; text-decoration: none;">
                Cancel
            </a>
        </form>
    </div>
</body>
</html>