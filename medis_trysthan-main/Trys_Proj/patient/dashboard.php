<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

// Check if user is logged in and is patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pasien') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Dashboard Pasien";

// Get patient information
$patient_info = $conn->query("SELECT * FROM pasien WHERE id_user = {$_SESSION['user_id']}")->fetch_assoc();

// Get upcoming appointments
$today = date('Y-m-d');
$upcoming_appointments = $conn->query("
    SELECT k.id_kunjungan, k.tgl_kunjungan, k.jam_kunjungan, p.nm_poli, d.nm_dokter
    FROM kunjungan k
    JOIN poliklinik p ON k.id_poli = p.id_poli
    LEFT JOIN dokter d ON d.id_poli = p.id_poli
    WHERE k.id_pasien = {$patient_info['id_pasien']} AND k.tgl_kunjungan >= '$today'
    ORDER BY k.tgl_kunjungan, k.jam_kunjungan
    LIMIT 5
");

// Get recent medical records
$recent_records = $conn->query("
    SELECT rm.id_rekam_medis, rm.tgl_pemeriksaan, d.nm_dokter, rm.diagnosa, rm.resep
    FROM rekam_medis rm
    LEFT JOIN dokter d ON rm.id_user = d.id_user
    WHERE rm.id_pasien = {$patient_info['id_pasien']}
    ORDER BY rm.tgl_pemeriksaan DESC
    LIMIT 5
");

// Get recent laboratory results
$recent_labs = $conn->query("
    SELECT l.id_lab, l.tgl_lab, l.hasil_lab, l.ket
    FROM laboratorium l
    WHERE l.no_rm = '{$patient_info['no_rm']}'
    ORDER BY l.tgl_lab DESC
    LIMIT 3
");

// Calculate age from birthdate
function calculateAge($birthdate) {
    $today = new DateTime();
    $birth = new DateTime($birthdate);
    $interval = $today->diff($birth);
    return $interval->y;
}

// Count total visits, medical records, and lab results
$total_visits = $conn->query("SELECT COUNT(*) as total FROM kunjungan WHERE id_pasien = {$patient_info['id_pasien']}")->fetch_assoc()['total'];
$total_records = $conn->query("SELECT COUNT(*) as total FROM rekam_medis WHERE id_pasien = {$patient_info['id_pasien']}")->fetch_assoc()['total'];
$total_labs = $conn->query("SELECT COUNT(*) as total FROM laboratorium WHERE no_rm = '{$patient_info['no_rm']}'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sistem Rekam Medis</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="sidebar">
            <?php include 'sidebar.php'; ?>
        </div>
        
        <div class="content">
            <div class="content-header">
                <h2><i class="fas fa-tachometer-alt"></i> <?php echo $page_title; ?></h2>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="window.location.href='appointments.php'">
                        <i class="fas fa-calendar-plus"></i> Buat Janji
                    </button>
                </div>
            </div>
            
            <!-- Patient Info Card -->
            <div class="patient-info-card">
                <div class="patient-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="patient-details">
                    <h3><?php echo $patient_info['nm_pasien']; ?></h3>
                    <p><i class="fas fa-id-card"></i> No. RM: <?php echo $patient_info['no_rm']; ?></p>
                    <p>
                        <i class="fas fa-birthday-cake"></i> 
                        <?php echo date('d/m/Y', strtotime($patient_info['tgl_lhr'])); ?> 
                        (<?php echo calculateAge($patient_info['tgl_lhr']); ?> tahun)
                    </p>
                    <p>
                        <i class="fas fa-venus-mars"></i> 
                        <?php echo ($patient_info['jenis_kelamin'] == 'L') ? 'Laki-laki' : 'Perempuan'; ?>
                    </p>
                </div>
            </div>
            
            <!-- Dashboard Stats -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <i class="fas fa-calendar-check"></i>
                    <h3>Total Kunjungan</h3>
                    <p><?php echo $total_visits; ?></p>
                    <a href="visits.php" class="stat-link">Lihat</a>
                </div>
                <div class="stat-card">
                    <i class="fas fa-notes-medical"></i>
                    <h3>Rekam Medis</h3>
                    <p><?php echo $total_records; ?></p>
                    <a href="medical_history.php" class="stat-link">Lihat</a>
                </div>
                <div class="stat-card">
                    <i class="fas fa-flask"></i>
                    <h3>Hasil Lab</h3>
                    <p><?php echo $total_labs; ?></p>
                    <a href="laboratory.php" class="stat-link">Lihat</a>
                </div>
                <div class="stat-card">
                    <i class="fas fa-prescription"></i>
                    <h3>Resep Obat</h3>
                    <p><?php echo $total_records; ?></p>
                    <a href="prescriptions.php" class="stat-link">Lihat</a>
                </div>
            </div>
            
            <div class="dashboard-row">
                <!-- Upcoming Appointments -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar"></i> Jadwal Kunjungan</h3>
                        <a href="visits.php" class="card-link">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <?php if ($upcoming_appointments->num_rows > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Poliklinik</th>
                                    <th>Dokter</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($appointment = $upcoming_appointments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($appointment['tgl_kunjungan'])); ?></td>
                                    <td><?php echo $appointment['jam_kunjungan']; ?></td>
                                    <td><?php echo $appointment['nm_poli']; ?></td>
                                    <td><?php echo $appointment['nm_dokter'] ?? 'Belum ditentukan'; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p class="no-data">Tidak ada jadwal kunjungan mendatang.</p>
                        <div class="action-center">
                            <a href="appointments.php" class="btn btn-primary">
                                <i class="fas fa-calendar-plus"></i> Buat Janji
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Laboratory Results -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-flask"></i> Hasil Lab Terbaru</h3>
                        <a href="laboratory.php" class="card-link">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_labs->num_rows > 0): ?>
                        <div class="lab-results">
                            <?php while ($lab = $recent_labs->fetch_assoc()): ?>
                            <div class="lab-result-item">
                                <div class="lab-result-header">
                                    <span class="lab-date">
                                        <i class="fas fa-calendar"></i> 
                                        <?php echo date('d/m/Y', strtotime($lab['tgl_lab'])); ?>
                                    </span>
                                    <a href="view_lab.php?id=<?php echo $lab['id_lab']; ?>" class="btn-sm btn-info">
                                        <i class="fas fa-eye"></i> Lihat
                                    </a>
                                </div>
                                <div class="lab-result-content">
                                    <p class="lab-note"><?php echo substr($lab['ket'], 0, 100) . (strlen($lab['ket']) > 100 ? '...' : ''); ?></p>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <?php else: ?>
                        <p class="no-data">Belum ada hasil laboratorium.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Medical Records -->
            <div class="dashboard-card full-width">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Riwayat Medis Terbaru</h3>
                    <a href="medical_history.php" class="card-link">Lihat Semua</a>
                </div>
                <div class="card-body">
                    <?php if ($recent_records->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Dokter</th>
                                <th>Diagnosa</th>
                                <th>Resep</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($record = $recent_records->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($record['tgl_pemeriksaan'])); ?></td>
                                <td><?php echo $record['nm_dokter'] ?? 'Tidak diketahui'; ?></td>
                                <td><?php echo substr($record['diagnosa'], 0, 50) . (strlen($record['diagnosa']) > 50 ? '...' : ''); ?></td>
                                <td><?php echo substr($record['resep'], 0, 50) . (strlen($record['resep']) > 50 ? '...' : ''); ?></td>
                                <td>
                                    <a href="view_medical_record.php?id=<?php echo $record['id_rekam_medis']; ?>" class="btn-sm btn-info">
                                        <i class="fas fa-eye"></i> Lihat
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="no-data">Belum ada riwayat medis.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize any dashboard-specific scripts here
        });
    </script>
    
    <style>
        .patient-info-card {
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #537D5D, #73946B);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .patient-avatar {
            width: 80px;
            height: 80px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
        }
        
        .patient-avatar i {
            font-size: 40px;
        }
        
        .patient-details h3 {
            margin: 0 0 10px 0;
            font-size: 1.5rem;
        }
        
        .patient-details p {
            margin: 5px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .dashboard-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            flex: 1;
        }
        
        .full-width {
            width: 100%;
        }
        
        .card-header {
            background: linear-gradient(135deg, #537D5D, #73946B);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h3 {
            margin: 0;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-link {
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .card-link:hover {
            text-decoration: underline;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .lab-results {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .lab-result-item {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
        }
        
        .lab-result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .lab-date {
            font-weight: 600;
            color: #537D5D;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .lab-result-content {
            color: #495057;
        }
        
        .lab-note {
            margin: 0;
            font-size: 0.95rem;
        }
        
        .action-center {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: #537D5D;
            color: white;
        }
        
        .btn-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn-sm:hover {
            opacity: 0.9;
        }
        
        @media (max-width: 768px) {
            .dashboard-row {
                flex-direction: column;
            }
            
            .patient-info-card {
                flex-direction: column;
                text-align: center;
            }
            
            .patient-avatar {
                margin-right: 0;
                margin-bottom: 15px;
            }
        }
    </style>
</body>
</html>
