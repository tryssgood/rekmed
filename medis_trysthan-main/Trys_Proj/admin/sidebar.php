<nav class="sidebar-nav">
    <ul>
        <li>
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="patients.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'patient.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-injured"></i> Pasien
            </a>
        </li>
        <li>
            <a href="doctors.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'doctors.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-md"></i> Dokter
            </a>
        </li>
        <li>
            <a href="polyclinics.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'polyclinics.php' ? 'active' : ''; ?>">
                <i class="fas fa-hospital"></i> Poliklinik
            </a>
        </li>
        <li>
            <a href="visits.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'visits.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Kunjungan
            </a>
        </li>
        <li>
            <a href="medical_records.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'medical_records.php' ? 'active' : ''; ?>">
                <i class="fas fa-notes-medical"></i> Rekam Medis
            </a>
        </li>
        <li>
            <a href="medicines.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'medicines.php' ? 'active' : ''; ?>">
                <i class="fas fa-pills"></i> Obat
            </a>
        </li>
        <li>
            <a href="treatments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'treatments.php' ? 'active' : ''; ?>">
                <i class="fas fa-procedures"></i> Tindakan
            </a>
        </li>
        <li>
            <a href="laboratory.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'laboratory.php' ? 'active' : ''; ?>">
                <i class="fas fa-flask"></i> Laboratorium
            </a>
        </li>
        <li>
            <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Pengguna
            </a>
        </li>
        <li>
            <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Laporan
            </a>
        </li>
        <li>
            <a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Pengaturan
            </a>
        </li>
        <li>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</nav>
