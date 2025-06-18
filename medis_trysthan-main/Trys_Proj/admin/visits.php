<?php
session_start();
// Sertakan file koneksi database dan fungsi-fungsi umum
include 'config/database.php';
include 'includes/functions.php';

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil informasi pengguna dari sesi
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$page_title = "Dashboard"; // Judul halaman

// Inisialisasi variabel untuk mencegah warning 'Undefined variable'
$total_patients = 0;
$total_doctors = 0;
$total_records = 0; 
$total_visits = 0;

$doctor_id = null;
$today_patients = 0;
$pending_records = 0;

$patient_id = null;
// Menginisialisasi patient_info dengan nilai default
$patient_info = [
    'nm_pasien' => '-',
    'no_rm' => '-',
    'tgl_lhr' => '-',
    'alamat' => '-'
]; 
$upcoming_visits_count = 0; // Mengubah nama variabel agar lebih jelas

// --- Logika berdasarkan peran pengguna ---
if ($role == 'admin') {
    $total_patients = getCount($conn, "pasien");
    $total_doctors = getCount($conn, "dokter");
    $total_records = getCount($conn, "rekam_medis"); 
    $total_visits = getCount($conn, "kunjungan");
} elseif ($role == 'dokter') {
    $doctor_id = getDoctorId($conn, $user_id);
    if ($doctor_id !== null) {
        $today_patients = getTodayPatientsCount($conn, $doctor_id); 
        $pending_records = getPendingRecords($conn, $doctor_id);
    }
} elseif ($role == 'pasien') {
    $patient_id = getPatientId($conn, $user_id); 
    
    if ($patient_id !== null) {
        $patient_info_raw = getPatientInfo($conn, $patient_id);
        if ($patient_info_raw) {
            $patient_info['nm_pasien'] = htmlspecialchars($patient_info_raw['nm_pasien'] ?? '-');
            $patient_info['no_rm'] = htmlspecialchars($patient_info_raw['no_rm'] ?? '-');
            $patient_info['tgl_lhr'] = htmlspecialchars($patient_info_raw['tgl_lhr'] ?? '-');
            $patient_info['alamat'] = htmlspecialchars($patient_info_raw['alamat'] ?? '-');
        }
        
        // PERBAIKAN DI SINI: Memastikan kita mendapatkan *jumlah* kunjungan mendatang
        // Asumsi: getUpcomingVisits() sekarang mengembalikan array kunjungan.
        // Maka kita hitung jumlah elemennya.
        $upcoming_visits_data = getUpcomingVisits($conn, $patient_id);
        if (is_array($upcoming_visits_data) || $upcoming_visits_data instanceof Countable) {
            $upcoming_visits_count = count($upcoming_visits_data);
        } else {
            $upcoming_visits_count = 0; // Pastikan ini integer jika bukan array/countable
        }
    }
}

// --- Struktur HTML ---
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
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="sidebar">
            <?php include 'includes/sidebar.php'; ?>
        </div>

        <div class="content">
            <div class="dashboard">
                <h2>Selamat Datang di Sistem Rekam Medis</h2>
                
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
                        <h3>Rekam Medis</h3>
                        <p><?php echo htmlspecialchars($total_records); ?></p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-calendar-check"></i>
                        <h3>Kunjungan</h3>
                        <p><?php echo htmlspecialchars($total_visits); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($role == 'dokter'): ?>
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <i class="fas fa-user-injured"></i>
                        <h3>Pasien Hari Ini</h3>
                        <p><?php echo htmlspecialchars($today_patients); ?></p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-clipboard-list"></i>
                        <h3>Rekam Medis Tertunda</h3>
                        <p><?php echo htmlspecialchars($pending_records); ?></p>
                    </div>
                </div>
                <div class="recent-section">
                    <h3>Daftar Pasien Hari Ini</h3>
                    <?php 
                    if ($doctor_id !== null) {
                        displayTodayPatientsList($conn, $doctor_id); 
                    } else {
                        echo '<p class="no-data">Tidak dapat menampilkan daftar pasien. ID Dokter tidak ditemukan atau sesi bermasalah.</p>';
                    }
                    ?>
                </div>
                <?php endif; ?>
                
                <?php if ($role == 'pasien'): ?>
                <div class="patient-info-card">
                    <h3>Informasi Pasien</h3>
                    <div class="info-card-detail">
                        <p><strong>Nama:</strong> <?php echo $patient_info['nm_pasien']; ?></p>
                        <p><strong>No. Rekam Medis:</strong> <?php echo $patient_info['no_rm']; ?></p>
                        <p><strong>Tanggal Lahir:</strong> <?php echo ($patient_info['tgl_lhr'] != '-') ? htmlspecialchars(formatDate($patient_info['tgl_lhr'])) : '-'; ?></p>
                        <p><strong>Alamat:</strong> <?php echo $patient_info['alamat']; ?></p>
                    </div>
                </div>
                <div class="recent-section">
                    <h3>Riwayat Kunjungan Terakhir</h3>
                    <?php 
                    if ($patient_id !== null) {
                        displayPatientVisitHistory($conn, $patient_id); 
                    } else {
                        echo '<p class="no-data">Tidak dapat menampilkan riwayat kunjungan. ID Pasien tidak ditemukan atau sesi bermasalah.</p>';
                    }
                    ?>
                </div>
                <?php if ($upcoming_visits_count > 0): // Menggunakan variabel count yang sudah dipastikan integer ?>
                <div class="upcoming-visits">
                    <h3>Jadwal Kunjungan Mendatang</h3>
                    <?php 
                    if ($patient_id !== null) {
                        displayUpcomingVisits($conn, $patient_id); // Fungsi ini yang menampilkan daftar, bukan count
                    } else {
                        echo '<p class="no-data">Tidak dapat menampilkan jadwal kunjungan. ID Pasien tidak ditemukan atau sesi bermasalah.</p>';
                    }
                    ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/script.js"></script>
    <style>
        /* Tambahan/Modifikasi CSS untuk tampilan pasien */
        .patient-info-card {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            border-left: 5px solid #2c5aa0; /* Aksen warna */
        }

        .patient-info-card h3 {
            color: #2c5aa0;
            margin-bottom: 20px;
            font-size: 1.5em;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .info-card-detail p {
            margin-bottom: 10px;
            line-height: 1.6;
            color: #555;
        }

        .info-card-detail p strong {
            color: #333;
            display: inline-block;
            width: 150px; /* Lebar tetap untuk label agar rapi */
        }

        .recent-section, .upcoming-visits {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .recent-section h3, .upcoming-visits h3 {
            color: #2c5aa0;
            margin-bottom: 20px;
            font-size: 1.5em;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .no-data {
            color: #777;
            text-align: center;
            padding: 20px;
            font-style: italic;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .info-card-detail p strong {
                width: auto; /* Hapus lebar tetap pada layar kecil */
                display: block; /* Buat label tampil di baris baru */
                margin-bottom: 5px;
            }
        }
    </style>
</body>
</html>