<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Admin Dashboard";

// Get statistics for dashboard
$total_patients = getCount($conn, "pasien");
$total_doctors = getCount($conn, "dokter");
$total_records = getCount($conn, "rekam_medis");
$total_visits = getCount($conn, "kunjungan");
$total_medicines = getCount($conn, "obat");
$total_treatments = getCount($conn, "tindakan");
$total_labs = getCount($conn, "laboratorium");
$total_polyclinics = getCount($conn, "poliklinik");

// Get recent activities
$recent_activities = $conn->query("
    SELECT 'rekam_medis' as type, rm.tgl_pemeriksaan as date, p.nm_pasien, d.nm_dokter, 'Rekam Medis Baru' as activity
    FROM rekam_medis rm
    JOIN pasien p ON rm.id_pasien = p.id_pasien
    LEFT JOIN dokter d ON rm.id_user = d.id_user
    ORDER BY rm.tgl_pemeriksaan DESC
    LIMIT 5
");

// Get today's visits
$today = date('Y-m-d');
$today_visits = $conn->query("
    SELECT k.id_kunjungan, k.jam_kunjungan, p.nm_pasien, pol.nm_poli, d.nm_dokter
    FROM kunjungan k
    JOIN pasien p ON k.id_pasien = p.id_pasien
    JOIN poliklinik pol ON k.id_poli = pol.id_poli
    LEFT JOIN dokter d ON d.id_poli = pol.id_poli
    WHERE k.tgl_kunjungan = '$today'
    ORDER BY k.jam_kunjungan
");

// Get medicine stock alerts (less than 10 items)
$medicine_alerts = $conn->query("
    SELECT id_obat, nm_obat, jml_obat, ukuran
    FROM obat
    WHERE jml_obat < 10
    ORDER BY jml_obat
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sistem Rekam Medis</title>
    <link rel="stylesheet" href="../css/style.css">
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
                <d <button class="btn btn-primary" onclick="window.location.href='reports.php'">
                        <i class="fas fa-file-alt"></i> Laporan
                    </button>iv class="header-actions">
                   
                    <button class="btn btn-secondary" onclick="window.location.href='settings.php'">
                        <i class="fas fa-cog"></i> Pengaturan
                    </button>
                </div>
            </div>
            
            <!-- Dashboard Stats -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <i class="fas fa-user-injured"></i>
                    <h3>Total Pasien</h3>
                    <p><?php echo $total_patients; ?></p>
                    <a href="patients.php" class="stat-link">Kelola</a>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-md"></i>
                    <h3>Total Dokter</h3>
                    <p><?php echo $total_doctors; ?></p>
                    <a href="doctors.php" class="stat-link">Kelola</a>
                </div>
                <div class="stat-card">
                    <i class="fas fa-notes-medical"></i>
                    <h3>Rekam Medis</h3>
                    <p><?php echo $total_records; ?></p>
                    <a href="medical_records.php" class="stat-link">Lihat</a>
                </div>
                <div class="stat-card">
                    <i class="fas fa-calendar-check"></i>
                    <h3>Kunjungan</h3>
                    <p><?php echo $total_visits; ?></p>
                    <a href="visits.php" class="stat-link">Kelola</a>
                </div>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <i class="fas fa-pills"></i>
                    <h3>Obat</h3>
                    <p><?php echo $total_medicines; ?></p>
                    <a href="medicines.php" class="stat-link">Kelola</a>
                </div>
                <div class="stat-card">
                    <i class="fas fa-procedures"></i>
                    <h3>Tindakan</h3>
                    <p><?php echo $total_treatments; ?></p>
                    <a href="treatments.php" class="stat-link">Kelola</a>
                </div>
                <div class="stat-card">
                    <i class="fas fa-flask"></i>
                    <h3>Laboratorium</h3>
                    <p><?php echo $total_labs; ?></p>
                    <a href="laboratory.php" class="stat-link">Kelola</a>
                </div>
                <div class="stat-card">
                    <i class="fas fa-hospital"></i>
                    <h3>Poliklinik</h3>
                    <p><?php echo $total_polyclinics; ?></p>
                    <a href="polyclinics.php" class="stat-link">Kelola</a>
                </div>
            </div>
            
            <div class="dashboard-row">
                <!-- Today's Visits -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-day"></i> Kunjungan Hari Ini</h3>
                        <a href="visits.php" class="card-link">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <?php if ($today_visits->num_rows > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Jam</th>
                                    <th>Pasien</th>
                                    <th>Poliklinik</th>
                                    <th>Dokter</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($visit = $today_visits->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $visit['jam_kunjungan']; ?></td>
                                    <td><?php echo $visit['nm_pasien']; ?></td>
                                    <td><?php echo $visit['nm_poli']; ?></td>
                                    <td><?php echo $visit['nm_dokter'] ?? 'Belum ditentukan'; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p class="no-data">Tidak ada kunjungan untuk hari ini.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Medicine Stock Alerts -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-exclamation-triangle"></i> Peringatan Stok Obat</h3>
                        <a href="medicines.php" class="card-link">Kelola Obat</a>
                    </div>
                    <div class="card-body">
                        <?php if ($medicine_alerts->num_rows > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nama Obat</th>
                                    <th>Ukuran</th>
                                    <th>Stok</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($medicine = $medicine_alerts->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $medicine['nm_obat']; ?></td>
                                    <td><?php echo $medicine['ukuran']; ?></td>
                                    <td><?php echo $medicine['jml_obat']; ?></td>
                                    <td>
                                        <?php if ($medicine['jml_obat'] <= 5): ?>
                                        <span class="badge badge-danger">Kritis</span>
                                        <?php else: ?>
                                        <span class="badge badge-warning">Rendah</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p class="no-data">Tidak ada peringatan stok obat.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activities -->
            <div class="dashboard-card full-width">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Aktivitas Terbaru</h3>
                </div>
                <div class="card-body">
                    <?php if ($recent_activities->num_rows > 0): ?>
                    <div class="activity-timeline">
                        <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-notes-medical"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-header">
                                    <span class="activity-title"><?php echo $activity['activity']; ?></span>
                                    <span class="activity-time"><?php echo date('d/m/Y H:i', strtotime($activity['date'])); ?></span>
                                </div>
                                <div class="activity-details">
                                    <p>Pasien: <strong><?php echo $activity['nm_pasien']; ?></strong></p>
                                    <p>Dokter: <strong><?php echo $activity['nm_dokter'] ?? 'Tidak diketahui'; ?></strong></p>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <p class="no-data">Tidak ada aktivitas terbaru.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/script.js"></script>
    <script>
        // Dashboard specific scripts
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize charts or other dashboard features here
        });
    </script>
    
    <style>
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
            padding: 20px;
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
        
        .stat-link {
            display: inline-block;
            margin-top: 10px;
            color: #537D5D;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .stat-link:hover {
            text-decoration: underline;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
        }
        
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .activity-timeline {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .activity-item {
            display: flex;
            gap: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .activity-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            background-color: #537D5D;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .activity-title {
            font-weight: 600;
            color: #537D5D;
        }
        
        .activity-time {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .activity-details p {
            margin: 5px 0;
            font-size: 0.95rem;
        }
        
        @media (max-width: 768px) {
            .dashboard-row {
                flex-direction: column;
            }
            
            .header-actions {
                margin-top: 10px;
            }
            
            .content-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</body>
</html>
