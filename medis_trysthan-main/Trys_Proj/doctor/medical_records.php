<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

// Check if user is logged in and is doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'dokter') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Daftar Pasien";
$message = '';
$search = ''; // Initialize search to avoid undefined variable warnings
$search_condition = ''; // Initialize search_condition to avoid undefined variable warnings
$doctor_id = getDoctorId($conn, $_SESSION['user_id']);

if (!$doctor_id) {
   
    $doctor_info = null;
} else {
    // Get doctor's polyclinic - only if doctor_id is valid
    $doctor_info_result = $conn->query("SELECT id_poli FROM dokter WHERE id_dokter = $doctor_id");
    if ($doctor_info_result && $doctor_info_result->num_rows > 0) {
        $doctor_info = $doctor_info_result->fetch_assoc();
    } else {
        $doctor_info = null;
      
    }
}

if (!empty($search)) {
    $search_condition .= " AND (p.nm_pasien LIKE '%$search%' OR p.no_rm LIKE '%$search%' OR rm.diagnosa LIKE '%$search%')";
}

if (!empty($date_filter)) {
    $search_condition .= " AND DATE(rm.tgl_pemeriksaan) = '$date_filter'";
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$total_records_query = "SELECT COUNT(*) as count 
                       FROM rekam_medis rm
                       JOIN pasien p ON rm.id_pasien = p.id_pasien
                       JOIN dokter d ON rm.id_user = d.id_user
                       $search_condition";
$total_records_result = $conn->query($total_records_query);
$total_records = $total_records_result->fetch_assoc()['count'];
$total_pages = ceil($total_records / $limit);

// Get medical records
$sql = "SELECT rm.*, p.nm_pasien, p.no_rm, p.jenis_kelamin, p.tgl_lhr,
               t.nm_tindakan, o.nm_obat, pol.nm_poli
        FROM rekam_medis rm
        JOIN pasien p ON rm.id_pasien = p.id_pasien
        JOIN dokter d ON rm.id_user = d.id_user
        LEFT JOIN tindakan t ON rm.id_tindakan = t.id_tindakan
        LEFT JOIN obat o ON rm.id_obat = o.id_obat
        LEFT JOIN poliklinik pol ON d.id_poli = pol.id_poli
        $search_condition
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
   
        <div class="content">
            <div class="content-header">
                <h2><i class="fas fa-notes-medical"></i> <?php echo $page_title; ?></h2>
                <a href="../rekam_medis_form.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Input Rekam Medis Baru
                </a>
            </div>
            
            <!-- Search and Filter -->
            <div class="search-filter">
                <form method="GET" action="" class="search-form">
                    <div class="search-input">
                        <input type="text" name="search" placeholder="Cari pasien atau diagnosa..." value="<?php echo htmlspecialchars($search); ?>">
                        <input type="date" name="date_filter" value="<?php echo htmlspecialchars($date_filter); ?>" class="date-filter">
                        <button type="submit" class="search-button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <?php if ($result->num_rows > 0): ?>
                    <div class="medical-records-list">
                        <?php while ($record = $result->fetch_assoc()): ?>
                        <div class="record-card">
                            <div class="record-header">
                                <div class="patient-info">
                                    <h4><?php echo $record['nm_pasien']; ?></h4>
                                    <span class="patient-details">
                                        No. RM: <?php echo $record['no_rm']; ?> | 
                                        <?php echo $record['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?> | 
                                        Umur: <?php echo date_diff(date_create($record['tgl_lhr']), date_create('today'))->y; ?> tahun
                                    </span>
                                </div>
                                <div class="record-date">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($record['tgl_pemeriksaan'])); ?>
                                </div>
                            </div>
                            
                            <div class="record-content">
                                <div class="record-row">
                                    <div class="record-item">
                                        <label><i class="fas fa-comment-medical"></i> Keluhan:</label>
                                        <p><?php echo nl2br($record['keluhan']); ?></p>
                                    </div>
                                    <div class="record-item">
                                        <label><i class="fas fa-stethoscope"></i> Diagnosa:</label>
                                        <p><?php echo nl2br($record['diagnosa']); ?></p>
                                    </div>
                                </div>
                                
                                <?php if ($record['nm_tindakan'] || $record['nm_obat']): ?>
                                <div class="record-row">
                                    <?php if ($record['nm_tindakan']): ?>
                                    <div class="record-item">
                                        <label><i class="fas fa-procedures"></i> Tindakan:</label>
                                        <p><?php echo $record['nm_tindakan']; ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($record['nm_obat']): ?>
                                    <div class="record-item">
                                        <label><i class="fas fa-pills"></i> Obat:</label>
                                        <p><?php echo $record['nm_obat']; ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($record['resep']): ?>
                                <div class="record-item full-width">
                                    <label><i class="fas fa-prescription"></i> Resep & Aturan Pakai:</label>
                                    <p><?php echo nl2br($record['resep']); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($record['ket']): ?>
                                <div class="record-item full-width">
                                    <label><i class="fas fa-sticky-note"></i> Catatan:</label>
                                    <p><?php echo nl2br($record['ket']); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="record-actions">
                                <a href="../rekam_medis_form.php?id=<?php echo $record['id_pasien']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Tambah Rekam Medis
                                </a>
                                <button class="btn btn-sm btn-info" onclick="printRecord(<?php echo $record['id_rekam_medis']; ?>)">
                                    <i class="fas fa-print"></i> Cetak
                                </button>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&date_filter=<?php echo urlencode($date_filter); ?>" class="pagination-link">
                            <i class="fas fa-chevron-left"></i> Sebelumnya
                        </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&date_filter=<?php echo urlencode($date_filter); ?>" class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&date_filter=<?php echo urlencode($date_filter); ?>" class="pagination-link">
                            Selanjutnya <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-notes-medical"></i>
                        <h3>Belum Ada Rekam Medis</h3>
                        <p>Belum ada rekam medis yang dibuat. Mulai dengan menambahkan rekam medis baru untuk pasien.</p>
                        <a href="../rekam_medis_form.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Input Rekam Medis Baru
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        function printRecord(recordId) {
            window.open(`print_record.php?id=${recordId}`, '_blank');
        }
    </script>
    
    <style>
        .search-filter {
            margin-bottom: 20px;
        }
        
        .search-form {
            max-width: 800px;
        }
        
        .search-input {
            display: flex;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .search-input input {
            flex: 1;
            padding: 12px 15px;
            border: none;
            outline: none;
            font-size: 1rem;
        }
        
        .date-filter {
            border-left: 1px solid #e9ecef;
            background-color: #f8f9fa;
        }
        
        .search-button {
            background-color: #537D5D;
            color: white;
            border: none;
            padding: 0 20px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .search-button:hover {
            background-color: #456b52;
        }
        
        .medical-records-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .record-card {
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
        }
        
        .patient-info h4 {
            margin: 0 0 5px 0;
            font-size: 1.2rem;
        }
        
        .patient-details {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .record-date {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        
        .record-content {
            padding: 20px;
        }
        
        .record-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .record-item {
            flex: 1;
        }
        
        .record-item.full-width {
            width: 100%;
            margin-bottom: 15px;
        }
        
        .record-item label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #537D5D;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .record-item p {
            margin: 0;
            line-height: 1.5;
            color: #333;
        }
        
        .record-actions {
            padding: 15px 20px;
            background-color: #f8f9fa;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 10px;
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
            .search-input {
                flex-direction: column;
            }
            
            .date-filter {
                border-left: none;
                border-top: 1px solid #e9ecef;
            }
            
            .record-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .record-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .record-actions {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>
