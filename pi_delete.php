<?php
session_start();
include 'config/database.php';
include 'includes/permissions.php';

// Check login
if(!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Get user role
$user_role = $_SESSION['role'] ?? 'staff';

// Check if user can delete
$can_delete = canDelete($user_role);

if(!$can_delete) {
    header("Location: pi_list.php?error=unauthorized");
    exit();
}

// Check if ID is provided
if(!isset($_GET['id'])) {
    header("Location: pi_list.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// First check if the record exists
$check = mysqli_query($conn, "SELECT id FROM pre_investigation WHERE id='$id'");
if(mysqli_num_rows($check) == 0) {
    header("Location: pi_list.php?error=notfound");
    exit();
}

// Delete the record
$query = "DELETE FROM pre_investigation WHERE id='$id'";

if(mysqli_query($conn, $query)) {
    header("Location: pi_list.php?msg=deleted");
} else {
    header("Location: pi_list.php?error=delete_failed");
}
exit();
?>