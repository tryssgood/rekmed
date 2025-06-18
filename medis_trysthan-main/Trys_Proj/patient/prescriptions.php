<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

// Check if user is logged in and is patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pasien') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Resep Obat";
$patient_id = getPatientId($conn, $_SESSION['user_id']);

if (!$patient_id) {
    header("Location: ../index.php");
    exit();
}

// Get prescriptions with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Mengubah 'tanggal' menjadi 'tgl_resep' di query COUNT
$total_prescriptions_query = "SELECT COUNT(*) as count FROM resep WHERE id_pasien = $patient_id";
$total_prescriptions_result = $conn->query($total_prescriptions_query);
$total_prescriptions = $total_prescriptions_result->fetch_assoc()['count'];
$total_pages = ceil($total_prescriptions / $limit);

// Mengubah 'r.tanggal' menjadi 'r.tgl_resep' di ORDER BY
$sql = "SELECT r.*, o.nm_obat, o.harga, d.nm_dokter
        FROM resep r
        LEFT JOIN obat o ON r.id_obat = o.id_obat
        LEFT JOIN dokter d ON r.id_dokter = d.id_dokter
        WHERE r.id_pasien = $patient_id
        ORDER BY r.tgl_resep DESC  /* <--- PERUBAHAN DI SINI */
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
                <h2><i class="fas fa-prescription"></i> <?php echo $page_title; ?></h2>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Daftar Resep Obat</h3>
                </div>
                <div class="card-body">
                    <?php if ($result->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Nama Dokter</th>
                                <th>Nama Obat</th>
                                <th>Harga</th>
                                <th>Jumlah</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1 + $offset; ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo date('d-m-Y', strtotime($row['tgl_resep'])); ?></td> <td><?php echo $row['nm_dokter']; ?></td>
                                <td><?php echo $row['nm_obat']; ?></td>
                                <td>Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                                <td><?php echo $row['jumlah']; ?></td>
                                <td>Rp <?php echo number_format($row['harga'] * $row['jumlah'], 0, ',', '.'); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
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
                        <i class="fas fa-prescription"></i>
                        <h3>Belum Ada Resep Obat</h3>
                        <p>Anda belum memiliki resep obat. Silakan konsultasi dengan dokter untuk mendapatkan resep.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <style>
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .pagination-link {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #007bff;
        }
        
        .pagination-link.active {
            background: #007bff;
            color: white;
        }
    </style>
</body>
</html>