<?php
session_start();
include 'config/database.php';
include 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect based on user role
$role = $_SESSION['role'];

if ($role == 'admin') {
    header("Location: admin/treatment.php.php");
    exit();
} elseif ($role == 'dokter') {
    header("Location: doctor/treatment.php");
    exit();
} elseif ($role == 'pasien') {
    header("Location: patient/treatment.php");
    exit();
} else {
    // Fallback to index if role is unknown
    header("Location: index.php");
    exit();
}
?>
