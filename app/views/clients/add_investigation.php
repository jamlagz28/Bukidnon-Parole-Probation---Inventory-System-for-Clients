<?php
session_start();
include(__DIR__ . "/../../config/database.php"); // Database connection

if(!isset($_SESSION['username'])){
    header("Location: ../../public/login.php");
    exit;
}

$message = "";

// Handle form submission
if(isset($_POST['save'])) {
    $case_number = trim($_POST['case_number']);
    $client_name = trim($_POST['client_name']);
    $offense = trim($_POST['offense']);
    $date_received = $_POST['date_received'];
    $investigator = trim($_POST['investigator']);

    // Upload images
    $uploaded_files = [];
    if(!empty($_FILES['requirements']['name'][0])){
        $total_files = count($_FILES['requirements']['name']);
        $upload_dir = __DIR__ . "/../uploads/"; // Make sure folder exists and is writable

        for($i=0; $i<$total_files; $i++){
            $tmp_name = $_FILES['requirements']['tmp_name'][$i];
            $filename = time() . "_" . basename($_FILES['requirements']['name'][$i]);
            $target_file = $upload_dir . $filename;

            if(move_uploaded_file($tmp_name, $target_file)){
                $uploaded_files[] = $filename;
            } else {
                $message .= "Failed to upload file: " . $_FILES['requirements']['name'][$i] . "<br>";
            }
        }
    }

    // Convert array of filenames to comma-separated string
    $requirements_files = implode(",", $uploaded_files);

    // Insert record into database
    $stmt = $conn->prepare("INSERT INTO investigation_records 
        (case_number, client_name, offense, date_received, investigator, requirements_files) 
        VALUES (?, ?, ?, ?, ?, ?)");
    
    if($stmt){
        $stmt->bind_param("ssssss", $case_number, $client_name, $offense, $date_received, $investigator, $requirements_files);
        if($stmt->execute()){
            $message .= "Investigation Record Added Successfully!";
        } else {
            $message .= "Error saving record: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message .= "Error preparing statement: " . $conn->error;
    }
}

// Fetch all records
$records = $conn->query("SELECT * FROM investigation_records ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Investigation Record</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    margin: 0;
}

/* Sidebar */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 220px;
    height: 100%;
    background: #2c3e50;
    padding-top: 20px;
}
.sidebar h2 {color:white;text-align:center;}
.sidebar a {display:block;color:white;padding:12px;text-decoration:none;}
.sidebar a:hover {background:#34495e;}

/* Main */
.main {margin-left:220px;padding:20px;}
.header {background:white;padding:15px;box-shadow:0 2px 5px rgba(0,0,0,0.1);}
.card {background:white;padding:20px;margin-top:20px;border-radius:5px;box-shadow:0 0 5px rgba(0,0,0,0.1);}
input, button, select {
    width:100%;
    padding:10px;
    margin:8px 0;
    border-radius:4px;
    border:1px solid #ccc;
    box-sizing:border-box;
}
button {
    background:#3498db;
    color:white;
    border:none;
    cursor:pointer;
}
button:hover {background:#2980b9;}
.message {margin-bottom:15px;color:green;text-align:center;}

/* Images */
.images-container {
    display:flex;
    flex-wrap:wrap;
}
img.uploaded {
    max-width:100px;
    max-height:100px;
    margin:5px;
    border-radius:4px;
    border:1px solid #ccc;
    cursor:pointer;
    transition: 0.3s;
}
img.uploaded:hover {
    transform: scale(1.2);
}

/* Lightbox */
#lightbox {
    position: fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background: rgba(0,0,0,0.8);
    display:none;
    justify-content: center;
    align-items: center;
    z-index: 999;
}
#lightbox img {
    max-width:90%;
    max-height:90%;
    border-radius:5px;
}

/* Responsive */
@media screen and (max-width:600px){
    .main {margin-left:0;padding:10px;}
    .sidebar {position:relative;width:100%;height:auto;}
}
.record {border-bottom:1px solid #ddd;padding:10px 0;}
.record h4 {margin:5px 0;}
</style>
</head>
<body>

<?php 
if(file_exists(__DIR__ . "/../sidebar.php")) include(__DIR__ . "/../sidebar.php");
if(file_exists(__DIR__ . "/../header.php")) include(__DIR__ . "/../header.php");
?>

<div class="main">
    <div class="card">
        <h3>Add Investigation Record</h3>
        <?php if($message) echo "<div class='message'>$message</div>"; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <label>Case Number</label>
            <input type="text" name="case_number" required>

            <label>Client Name</label>
            <input type="text" name="client_name" required>

            <label>Offense</label>
            <input type="text" name="offense" required>

            <label>Date Received</label>
            <input type="date" name="date_received" required>

            <label>Investigator</label>
            <input type="text" name="investigator" required>

            <label>Upload Client Requirements</label>
            <input type="file" name="requirements[]" multiple>

            <button type="submit" name="save">Save Record</button>
        </form>
    </div>

    <!-- Display all records -->
    <div class="card">
        <h3>Uploaded Records</h3>
        <?php if($records->num_rows > 0): ?>
            <?php while($row = $records->fetch_assoc()): ?>
                <div class="record">
                    <h4>Case: <?php echo htmlspecialchars($row['case_number']); ?> - Client: <?php echo htmlspecialchars($row['client_name']); ?></h4>
                    <p>Offense: <?php echo htmlspecialchars($row['offense']); ?> | Investigator: <?php echo htmlspecialchars($row['investigator']); ?> | Date: <?php echo $row['date_received']; ?></p>
                    <?php if(!empty($row['requirements_files'])): ?>
                        <div class="images-container">
                            <?php 
                            $files = explode(",", $row['requirements_files']);
                            $uploads_url = "/INVENTORY_SYSTEM/uploads/"; // adjust to your folder
                            foreach($files as $file): 
                                $file_path = __DIR__ . "/../uploads/" . $file;
                                if(file_exists($file_path)): ?>
                                    <img src="<?php echo $uploads_url . urlencode($file); ?>" class="uploaded" alt="Requirement">
                                <?php endif; 
                            endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No records found.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Lightbox -->
<div id="lightbox" onclick="this.style.display='none'">
    <img id="lightbox-img" src="">
</div>

<script>
// Lightbox functionality
const images = document.querySelectorAll('img.uploaded');
const lightbox = document.getElementById('lightbox');
const lightboxImg = document.getElementById('lightbox-img');

images.forEach(img => {
    img.addEventListener('click', function() {
        lightbox.style.display = 'flex';
        lightboxImg.src = this.src;
    });
});
</script>

</body>
</html>