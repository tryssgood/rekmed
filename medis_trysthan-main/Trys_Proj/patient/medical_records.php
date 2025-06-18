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

$patient_info = getPatientInfo($conn, $patient_id);

// Get medical records with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$total_records_query = "SELECT COUNT(*) as count FROM rekam_medis WHERE id_pasien = $patient_id";
$total_records_result = $conn->query($total_records_query);
$total_records = $total_records_result->fetch_assoc()['count'];
$total_pages = ceil($total_records / $limit);

$sql = "SELECT rm.*, t.nm_tindakan, o.nm_obat, d.nm_dokter, pol.nm_poli
        FROM rekam_medis rm
        LEFT JOIN tindakan t ON rm.id_tindakan = t.id_tindakan
        LEFT JOIN obat o ON rm.id_obat = o.id_obat
        LEFT JOIN dokter d ON rm.id_user = d.id_user
        LEFT JOIN poliklinik pol ON d.id_poli = pol.id_poli
        WHERE rm.id_pasien = $patient_id
        ORDER BY rm.tgl_pemeriksaan DESC
        LIMIT $offset, $limit";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sistem Rekam Medis</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
       
        
        <div class="content">
            <div class="content-header">
                <h2><i class="fas fa-history"></i> <?php echo $page_title; ?></h2>
                <button class="btn btn-primary" onclick="printHistory()">
                    <i class="fas fa-print"></i> Cetak Riwayat
                </button>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Riwayat Rekam Medis - <?php echo $patient_info['nm_pasien']; ?></h3>
                    <span class="patient-info">No. RM: <?php echo $patient_info['no_rm']; ?></span>
                </div>
                <div class="card-body">
                    <?php if ($result->num_rows > 0): ?>
                    <div class="medical-records">
                        <?php while ($record = $result->fetch_assoc()): ?>
                        <div class="record-item">
                            <div class="record-header">
                                <div class="record-date">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d F Y, H:i', strtotime($record['tgl_pemeriksaan'])); ?>
                                </div>
                                <div class="record-doctor">
                                    <i class="fas fa-user-md"></i>
                                    <?php echo $record['nm_dokter'] ?? 'Dokter tidak diketahui'; ?>
                                </div>
                                <?php if ($record['nm_poli']): ?>
                                <div class="record-clinic">
                                    <i class="fas fa-hospital"></i>
                                    <?php echo $record['nm_poli']; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="record-content">
                                <div class="record-section">
                                    <h5><i class="fas fa-comment-medical"></i> Keluhan</h5>
                                    <p><?php echo nl2br($record['keluhan']); ?></p>
                                </div>
                                
                                <div class="record-section">
                                    <h5><i class="fas fa-stethoscope"></i> Diagnosa</h5>
                                    <p><?php echo nl2br($record['diagnosa']); ?></p>
                                </div>
                                
                                <?php if ($record['nm_tindakan']): ?>
                                <div class="record-section">
                                    <h5><i class="fas fa-procedures"></i> Tindakan</h5>
                                    <p><?php echo $record['nm_tindakan']; ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($record['nm_obat']): ?>
                                <div class="record-section">
                                    <h5><i class="fas fa-pills"></i> Obat</h5>
                                    <p><?php echo $record['nm_obat']; ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($record['resep']): ?>
                                <div class="record-section">
                                    <h5><i class="fas fa-prescription"></i> Resep & Aturan Pakai</h5>
                                    <p><?php echo nl2br($record['resep']); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($record['ket']): ?>
                                <div class="record-section">
                                    <h5><i class="fas fa-sticky-note"></i> Catatan</h5>
                                    <p><?php echo nl2br($record['ket']); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="pagination-link">
                            <i class="fas fa-chevron-left"></i> Sebelumnya
                        </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="pagination-link">
                            Selanjutnya <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-notes-medical"></i>
                        <h3>Belum Ada Riwayat Medis</h3>
                        <p>Anda belum memiliki riwayat rekam medis. Silakan lakukan kunjungan ke dokter untuk mendapatkan pelayanan medis.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        function printHistory() {
            window.print();
        }
    </script>
    
    <style>
        .patient-info {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .medical-records {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .record-item {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .record-header {
            background: linear-gradient(135deg, #537D5D, #73946B);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .record-date, .record-doctor, .record-clinic {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        
        .record-content {
            padding: 20px;
        }
        
        .record-section {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .record-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .record-section h5 {
            color: #537D5D;
            margin-bottom: 8px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .record-section p {
            margin: 0;
            line-height: 1.6;
            color: #333;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .no-data i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #e9ecef;
        }
        
        .no-data h3 {
            margin-bottom: 10px;
            color: #495057;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 5px;
        }
        
        .pagination-link {
            padding: 8px 12px;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            color: #537D5D;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .pagination-link:hover {
            background-color: #f8f9fa;
        }
        
        .pagination-link.active {
            background-color: #537D5D;
            color: white;
            border-color: #537D5D;
        }
        
        @media (max-width: 768px) {
            .record-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .record-content {
                padding: 15px;
            }
        }
        
        @media print {
            .sidebar, .main-header, .content-header, .pagination, .main-footer {
                display: none !important;
            }
            
            .container {
                display: block;
            }
            
            .content {
                padding: 0;
                margin: 0;
            }
            
            .card {
                box-shadow: none;
                border: none;
            }
            
            .record-item {
                page-break-inside: avoid;
                margin-bottom: 20px;
            }
        }
    </style>
</body>
</html>
