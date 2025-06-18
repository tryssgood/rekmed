<?php
// Pastikan session_start() hanya dipanggil sekali di awal skrip
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sertakan file koneksi database dan fungsi-fungsi umum
// Gunakan include_once untuk mencegah redeklarasi fungsi dan masalah lain
// PASTIKAN PATH RELATIF INI BENAR DARI LOKASI FILE INI!
// Contoh: Jika file ini ada di 'root/', maka 'config/database.php'
// Jika file ini ada di 'root/doctor/', maka '../config/database.php'
include_once 'config/database.php'; 
include_once 'includes/functions.php'; 

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil informasi pengguna dari sesi
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$page_title = "Dashboard"; // Judul halaman default, akan diperbarui berdasarkan peran

// Inisialisasi variabel untuk mencegah warning 'Undefined variable'
// Ini penting karena tidak semua peran akan menggunakan semua variabel ini
$total_patients = 0;
$total_doctors = 0;
$total_records = 0; 
$total_visits = 0;

$doctor_id = null;
$today_patients = 0;
$pending_records = 0;

$patient_id = null;
// Inisialisasi detail pasien dengan nilai default untuk mencegah error jika data tidak ditemukan
$patient_info = [
    'nm_pasien' => '-',
    'no_rm' => '-',
    'tgl_lhr' => '-',
    'alamat' => '-'
]; 
$upcoming_visits = 0;

// --- Logika berdasarkan peran pengguna ---
if ($role == 'admin') {
    $page_title = "Dashboard Admin"; 
    $total_patients = getCount($conn, "pasien");
    $total_doctors = getCount($conn, "dokter");
    $total_records = getCount($conn, "rekam_medis"); 
    $total_visits = getCount($conn, "kunjungan");

} elseif ($role == 'dokter') {
    $page_title = "Dashboard Dokter"; 
    $doctor_id = getDoctorId($conn, $user_id); // Dapatkan ID dokter berdasarkan user_id
    if ($doctor_id !== null) {
        // Menggunakan nama fungsi yang benar seperti yang terlihat di screenshot
        $today_patients = getTodayPatientsCount($conn, $doctor_id); 
        $pending_records = getPendingRecords($conn, $doctor_id);
    }

} elseif ($role == 'pasien') {
    $page_title = "Dashboard Pasien"; 
    $patient_id = getPatientId($conn, $user_id); 
    
    if ($patient_id !== null) {
        $patient_info_raw = getPatientInfo($conn, $patient_id);
        if ($patient_info_raw) {
            // Menggunakan htmlspecialchars untuk keamanan output
            $patient_info['nm_pasien'] = htmlspecialchars($patient_info_raw['nm_pasien'] ?? '-');
            $patient_info['no_rm'] = htmlspecialchars($patient_info_raw['no_rm'] ?? '-');
            // Pastikan formatDate() ada di includes/functions.php dan menangani input kosong/null
            $patient_info['tgl_lhr'] = htmlspecialchars(formatDate($patient_info_raw['tgl_lhr'] ?? '')) ?? '-'; 
            $patient_info['alamat'] = htmlspecialchars($patient_info_raw['alamat'] ?? '-');
        }
        $upcoming_visits = getUpcomingVisits($conn, $patient_id);
    }
}

// --- Struktur HTML Dimulai ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Rekam Medis - <?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include_once 'includes/header.php'; ?>
    
    <div class="container">
        <div class="sidebar">
            <?php include_once 'includes/sidebar.php'; ?>
        </div>

        <div class="content">
            <div class="dashboard">
                <h2>Selamat Datang, <?php echo ucfirst(htmlspecialchars($role)); ?>!</h2>
                
                <?php if ($role == 'admin'): ?>
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <i class="fas fa-user-injured"></i>
                        <h3>Total Pasien</h3>
                        <p><?php echo htmlspecialchars($total_patients); ?></p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-user-md"></i>
                        <h3>Total Dokter</h3>
                        <p><?php echo htmlspecialchars($total_doctors); ?></p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-notes-medical"></i>
                        <h3>Total Rekam Medis</h3>
                        <p><?php echo htmlspecialchars($total_records); ?></p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-calendar-check"></i>
                        <h3>Total Kunjungan</h3>
                        <p><?php echo htmlspecialchars($total_visits); ?></p>
                    </div>
                </div>

                <?php elseif ($role == 'dokter'): ?>
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <i class="fas fa-procedures"></i>
                        <h3>Pasien Hari Ini</h3>
                        <p><?php echo htmlspecialchars($today_patients); ?></p>
                    </div>
                </div>
                
         

                <?php elseif ($role == 'pasien'): ?>
                <p>Selamat datang, <?php echo htmlspecialchars($patient_info['nm_pasien']); ?>!</p>
                <div class="dashboard-info-card">
                    <h3>Profil Pasien</h3>
                    <p><strong>No. Rekam Medis:</strong> <?php echo htmlspecialchars($patient_info['no_rm']); ?></p>
                    <p><strong>Tanggal Lahir:</strong> <?php echo htmlspecialchars($patient_info['tgl_lhr']); ?></p>
                    <p><strong>Alamat:</strong> <?php echo htmlspecialchars($patient_info['alamat']); ?></p>
                </div>

                <div class="dashboard-stats">
                    <div class="stat-card">
                        <i class="fas fa-calendar-plus"></i>
                        <h3>Kunjungan Mendatang</h3>
                        <p><?php echo htmlspecialchars($upcoming_visits); ?></p>
                    </div>
                </div>

                <div class="dashboard-section">
                   
                    <?php 
                    if ($patient_id !== null) {
                        // Pastikan fungsi displayUpcomingVisits ada di includes/functions.php
                        displayUpcomingVisits($conn, $patient_id); 
                    } else {
                        echo '<p class="no-data">Tidak ada jadwal kunjungan mendatang.</p>';
                    }
                    ?>
                </div>

                <?php else: ?>
                <p>Peran pengguna tidak dikenali. Silakan hubungi administrator.</p>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
    
    <?php include_once 'includes/footer.php'; ?>
    <script src="assets/js/script.js"></script>
</body>
</html>