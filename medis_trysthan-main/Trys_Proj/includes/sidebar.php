<nav class="sidebar-nav">
    <ul>
        <li>
            <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </li>
        
        <?php if ($_SESSION['role'] == 'admin'): ?>
        
        <li>
            <a href="admin/patients.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/patient.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-user-injured"></i> Pasien
            </a>
        </li>
        <li>
            <a href="admin/doctors.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/doctors.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-user-md"></i> Dokter
            </a>
        </li>
        <li>
            <a href="admin/polyclinics.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/polyclinics.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-hospital"></i> Poliklinik
            </a>
        </li>
        <li>
            <a href="admin/medicines.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/medicines.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-pills"></i> Obat
            </a>
        </li>
        <li>
            <a href="admin/treatments.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/treatments.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-procedures"></i> Tindakan
            </a>
        </li>
        <li>
            <a href="admin/laboratory.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/laboratory.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-flask"></i> Laboratorium
            </a>
        </li>
        <li>
            <a href="admin/visits.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/visits.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Kunjungan
            </a>
        </li>
        <li>
            <a href="admin/medical_records.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/medical_records.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-notes-medical"></i> Rekam Medis
            </a>
        </li>
        <li>
            <a href="admin/users.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/users.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Pengguna
            </a>
        </li>
        <li>
            <a href="admin/reports.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/reports.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Laporan
            </a>
        </li>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] == 'dokter'): ?>
        <nav class="sidebar-nav">
    <ul>

        <li>
            <a href="visits.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'doctor\visits.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Jadwal Kunjungan
            </a>
        </li>
        <li>
            <a href="treatments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'doctor\treatment.php' ? 'active' : ''; ?>">
                <i class="fas fa-procedures"></i> Tindakan
            </a>
        </li>
        <li>
            <a href="medicines.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'doctor\medicines.php' ? 'active' : ''; ?>">
                <i class="fas fa-pills"></i> Daftar Obat
            </a>
        </li>
        <li>
            <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'doctor\profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-cog"></i> Profil Saya
            </a>
        </li>
        <li>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</nav>
<?php endif; ?>
        
        <?php if ($_SESSION['role'] == 'pasien'): ?>
        </li>
        <li>
            <a href="patient/medical_history.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'patient/medical_history.php') !== false ? 'active' : ''; ?>">
               <i class="fa-solid fa-circle-info"></i> infomasi anda
            </a>
        </li>

       <li>
    <a href="patient/laboratory.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'patient/laboratory.php') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-vials"></i> Laboratorium
    </a>
</li>

        
        <li>
            <a href="patient/visits.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'patient/visits.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Jadwal Kunjungan
            </a>
        </li>
        <?php endif; ?>
        
      
    </ul>
</nav>
