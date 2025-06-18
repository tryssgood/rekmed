<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

// Check if user is logged in and is patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pasien') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Riwayat Medis";
$patient_id = getPatientId($conn, $_SESSION['user_id']);

if (!$patient_id) {
    header("Location: ../index.php");
    exit();
}

?>

<div class="sidebar">
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == '../profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> Profil Saya
                </a>
            </li>
            <li>
                <a href="medical_history.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'medical_records.php' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i> Riwayat Medis
                </a>
            </li>
            <li>
                <a href="visits.php" class="<?php echo basename($_SERVER['PHP_SELF']) == '../visits.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i> Jadwal Kunjungan
                </a>
            </li>
            <li>
                <a href="laboratory.php" class="<?php echo basename($_SERVER['PHP_SELF']) == '../laboratory.php' ? 'active' : ''; ?>">
                    <i class="fas fa-flask"></i> Hasil Lab
                </a>
            </li>
            <li>
                <a href="prescriptions.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'prescriptions.php' ? 'active' : ''; ?>">
                    <i class="fas fa-prescription"></i> Resep Obat
                </a>
            </li>
            <li>
                <a href="appointments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-plus"></i> Buat Janji
                </a>
            </li>
            <li>
                <a href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </nav>
</div>
