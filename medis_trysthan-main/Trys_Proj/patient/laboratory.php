<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

// Check if user is logged in and is patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pasien') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Hasil Laboratorium";
$patient_id = getPatientId($conn, $_SESSION['user_id']);

if (!$patient_id) {
    header("Location: ../index.php");
    exit();
}

// Get patient information
$patient_info = getPatientInfo($conn, $patient_id);

// Get laboratory results with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total records
$total_query = "SELECT COUNT(*) as count FROM laboratorium WHERE no_rm = ?";
$stmt = $conn->prepare($total_query);
$stmt->bind_param("s", $patient_info['no_rm']);
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['count'];
$total_pages = ceil($total_records / $limit);

// Get laboratory results
$sql = "SELECT * FROM laboratorium 
        WHERE no_rm = ? 
        ORDER BY tgl_lab DESC, created_at DESC
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $patient_info['no_rm'], $offset, $limit);
$stmt->execute();
$lab_results = $stmt->get_result();
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
                <h2><i class="fas fa-flask"></i> <?php echo $page_title; ?></h2>
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
                    </div>
                </div>
            </div>
            
            <!-- Laboratory Results -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-flask"></i> Hasil Laboratorium</h3>
                </div>
                <div class="card-body">
                    <?php if ($lab_results->num_rows > 0): ?>
                    <div class="lab-results-list">
                        <?php while ($result = $lab_results->fetch_assoc()): ?>
                        <div class="lab-result-item">
                            <div class="lab-header">
                                <div class="lab-date">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d/m/Y', strtotime($result['tgl_lab'])); ?>
                                </div>
                                <div class="lab-id">
                                    <i class="fas fa-barcode"></i>
                                    Lab ID: <?php echo $result['id_lab']; ?>
                                </div>
                            </div>
                            <div class="lab-content">
                                <div class="lab-section">
                                    <h4><i class="fas fa-vial"></i> Hasil Pemeriksaan</h4>
                                    <div class="lab-results-text">
                                        <?php echo nl2br(htmlspecialchars($result['hasil_lab'])); ?>
                                    </div>
                                </div>
                                <?php if ($result['ket']): ?>
                                <div class="lab-section">
                                    <h4><i class="fas fa-sticky-note"></i> Keterangan</h4>
                                    <p><?php echo nl2br(htmlspecialchars($result['ket'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="lab-actions">
                                <button class="btn btn-sm btn-primary" onclick="printLabResult(<?php echo $result['id_lab']; ?>)">
                                    <i class="fas fa-print"></i> Cetak
                                </button>
                                <button class="btn btn-sm btn-info" onclick="downloadLabResult(<?php echo $result['id_lab']; ?>)">
                                    <i class="fas fa-download"></i> Download PDF
                                </button>
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
                        <i class="fas fa-flask"></i>
                        <h3>Belum Ada Hasil Laboratorium</h3>
                        <p>Anda belum memiliki hasil pemeriksaan laboratorium.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        function printLabResult(labId) {
            window.open(`print_lab_result.php?id=${labId}`, '_blank');
        }
        
        function downloadLabResult(labId) {
            window.location.href = `download_lab_result.php?id=${labId}`;
        }
    </script>
    
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
        
        .lab-results-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .lab-result-item {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .lab-header {
            background: linear-gradient(135deg, #17a2b8, #20c997);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .lab-date, .lab-id {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        
        .lab-content {
            padding: 20px;
        }
        
        .lab-section {
            margin-bottom: 20px;
        }
        
        .lab-section:last-child {
            margin-bottom: 0;
        }
        
        .lab-section h4 {
            color: #17a2b8;
            margin-bottom: 10px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .lab-results-text {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #17a2b8;
            font-family: 'Courier New', monospace;
            white-space: pre-line;
            line-height: 1.6;
        }
        
        .lab-section p {
            margin: 0;
            line-height: 1.6;
            color: #333;
        }
        
        .lab-actions {
            padding: 15px 20px;
            background: #f8f9fa;
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
            .lab-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .patient-info-grid {
                grid-template-columns: 1fr;
            }
            
            .lab-actions {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>
