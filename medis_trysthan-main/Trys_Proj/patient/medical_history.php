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

// Get patient information
$patient_info = getPatientInfo($conn, $patient_id);

// Get medical records with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total records
$total_query = "SELECT COUNT(*) as count FROM rekam_medis WHERE id_pasien = ?";
$stmt = $conn->prepare($total_query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['count'];
$total_pages = ceil($total_records / $limit);

// Get medical records
$sql = "SELECT rm.*, t.nm_tindakan, o.nm_obat, d.nm_dokter, p.nm_poli
        FROM rekam_medis rm
        LEFT JOIN tindakan t ON rm.id_tindakan = t.id_tindakan
        LEFT JOIN obat o ON rm.id_obat = o.id_obat
        LEFT JOIN dokter d ON rm.id_user = d.id_user
        LEFT JOIN poliklinik p ON d.id_poli = p.id_poli
        WHERE rm.id_pasien = ?
        ORDER BY rm.tgl_pemeriksaan DESC
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $patient_id, $offset, $limit);
$stmt->execute();
$medical_records = $stmt->get_result();
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
            </div>
            
            <!-- Patient Info Card -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user"></i> Informasi Pasien</h3>
                </div>
                <div class="card-body">
                    <div class="patient-info-grid">
                        <div class="info-item">
                            <span class="info-label">No. Rekam Medis:</span>
                            <span class="info-value"><?php echo $patient_info['no_rm']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Nama:</span>
                            <span class="info-value"><?php echo $patient_info['nm_pasien']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tanggal Lahir:</span>
                            <span class="info-value"><?php echo date('d/m/Y', strtotime($patient_info['tgl_lhr'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Jenis Kelamin:</span>
                            <span class="info-value"><?php echo $patient_info['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Medical Records -->
            <div class="card">

                </div>
                <div class="card-body">
                    <?php if ($medical_records->num_rows > 0): ?>
                    <div class="medical-records-list">
                        <?php while ($record = $medical_records->fetch_assoc()): ?>
                        <div class="medical-record-item">
                            <div class="record-header">
                                <div class="record-date">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($record['tgl_pemeriksaan'])); ?>
                                </div>
                                <div class="record-doctor">
                                    <i class="fas fa-user-md"></i>
                                    <?php echo $record['nm_dokter'] ?? 'Dokter tidak diketahui'; ?>
                                </div>
                                <?php if ($record['nm_poli']): ?>
                                <div class="record-poly">
                                    <i class="fas fa-hospital"></i>
                                    <?php echo $record['nm_poli']; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="record-content">
                                <div class="record-section">
                                    <p><?php echo nl2br(htmlspecialchars($record['keluhan'])); ?></p>
                                </div>
                                <div class="record-section">
                                    <h4><i class="fas fa-stethoscope"></i> Diagnosa</h4>
                                    <p><?php echo nl2br(htmlspecialchars($record['diagnosa'])); ?></p>
                                </div>
                                <?php if ($record['nm_tindakan']): ?>
                                <div class="record-section">
                                    <h4><i class="fas fa-procedures"></i> Tindakan</h4>
                                    <p><?php echo $record['nm_tindakan']; ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if ($record['nm_obat']): ?>
                                <div class="record-section">
                                    <h4><i class="fas fa-pills"></i> Obat</h4>
                                    <p><?php echo $record['nm_obat']; ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if ($record['resep']): ?>
                                <div class="record-section">
                                    <h4><i class="fas fa-prescription"></i> Resep & Aturan Pakai</h4>
                                    <p><?php echo nl2br(htmlspecialchars($record['resep'])); ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if ($record['ket']): ?>
                                <div class="record-section">
                                    <h4><i class="fas fa-sticky-note"></i> Keterangan</h4>
                                    <p><?php echo nl2br(htmlspecialchars($record['ket'])); ?></p>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <style>
        .patient-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .info-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .info-value {
            font-size: 1.1rem;
            color: #333;
            font-weight: 500;
        }
        
        .medical-records-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .medical-record-item {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
        
        .record-date, .record-doctor, .record-poly {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        
        .record-content {
            padding: 20px;
        }
        
        .record-section {
            margin-bottom: 20px;
        }
        
        .record-section:last-child {
            margin-bottom: 0;
        }
        
        .record-section h4 {
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
            
            .patient-info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
