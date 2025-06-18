<?php
// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'dokter') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Dokter Dashboard'; ?> - Sistem Rekam Medis</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- Modern Header with Navbar -->
    <header class="main-header">
        <div class="header-container">
            <div class="logo">
                <i class="fas fa-heartbeat"></i>
                <h1>MediCare System</h1>
            </div>
            
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo $_SESSION['username']; ?></div>
                        <div class="user-role"><?php echo ucfirst($_SESSION['role']); ?></div>
                    </div>
                </div>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Navigation Bar -->
        <nav class="main-navbar" id="mainNavbar">
            <div class="navbar-container">
                <ul class="navbar-nav">
                    <li><a href="../index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="patients.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'patients.php' ? 'active' : ''; ?>"><i class="fas fa-user-injured"></i> Pasien</a></li>
                    <li><a href="medical_records.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'medical_records.php' ? 'active' : ''; ?>"><i class="fas fa-notes-medical"></i> Rekam Medis</a></li>
                    <li><a href="visits.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'visits.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Kunjungan</a></li>
                    <li><a href="treatments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'treatments.php' ? 'active' : ''; ?>"><i class="fas fa-procedures"></i> Tindakan</a></li>
                    <li><a href="laboratory.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'laboratory.php' ? 'active' : ''; ?>"><i class="fas fa-flask"></i> Laboratorium</a></li>
                    <li><a href="medicines.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'medicines.php' ? 'active' : ''; ?>"><i class="fas fa-pills"></i> Obat</a></li>
                    <li><a href="schedule.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'schedule.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Jadwal</a></li>
                    <li><a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>"><i class="fas fa-user-cog"></i> Profil</a></li>
                </ul>
            </div>
        </nav>
    </header>
