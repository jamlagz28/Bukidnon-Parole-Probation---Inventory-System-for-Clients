<?php
// config/session.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin/main user
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'main';
}

// Function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Function to log user activities
function logActivity($conn, $user_id, $action, $details = '') {
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $query = "INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) 
              VALUES (?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "issss", $user_id, $action, $details, $ip, $user_agent);
    
    if(mysqli_stmt_execute($stmt)) {
        return true;
    }
    return false;
}

// Function to get current user info
function getCurrentUser($conn) {
    if(isset($_SESSION['user_id'])) {
        $id = $_SESSION['user_id'];
        $query = "SELECT * FROM staff WHERE id = '$id'";
        $result = mysqli_query($conn, $query);
        return mysqli_fetch_assoc($result);
    }
    return null;
}
?>