<nav class="sidebar-nav">
    <ul>
        <li>
            <a href="../index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="patients.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'patients.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Daftar Pasien
            </a>
        </li>
        <li>
            <a href="medical_records.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'medical_records.php' ? 'active' : ''; ?>">
                <i class="fas fa-notes-medical"></i> Rekam Medis
            </a>
        </li>
        <li>
            <a href="visits.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'visits.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Jadwal Kunjungan
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
            <a href="medicines.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'medicines.php' ? 'active' : ''; ?>">
                <i class="fas fa-pills"></i> Daftar Obat
            </a>
        </li>
        <li>
            <a href="schedule.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'schedule.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i> Jadwal Praktik
            </a>
        </li>
        <li>
            <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-cog"></i> Profil Saya
            </a>
        </li>
        <li>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</nav>
