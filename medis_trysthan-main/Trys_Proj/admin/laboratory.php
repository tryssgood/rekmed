<?php
session_start();
include '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Manajemen Laboratorium";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $no_rm = $_POST['no_rm'];
                $hasil_lab = $_POST['hasil_lab'];
                $ket = $_POST['ket'];
                $tgl_lab = $_POST['tgl_lab'];
                
                $stmt = $conn->prepare("INSERT INTO laboratorium (no_rm, hasil_lab, ket, tgl_lab) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $no_rm, $hasil_lab, $ket, $tgl_lab);
                
                if ($stmt->execute()) {
                    $success = "Data laboratorium berhasil ditambahkan.";
                } else {
                    $error = "Error: " . $stmt->error;
                }
                break;
                
            case 'edit':
                $id_lab = $_POST['id_lab'];
                $no_rm = $_POST['no_rm'];
                $hasil_lab = $_POST['hasil_lab'];
                $ket = $_POST['ket'];
                $tgl_lab = $_POST['tgl_lab'];
                
                $stmt = $conn->prepare("UPDATE laboratorium SET no_rm = ?, hasil_lab = ?, ket = ?, tgl_lab = ? WHERE id_lab = ?");
                $stmt->bind_param("ssssi", $no_rm, $hasil_lab, $ket, $tgl_lab, $id_lab);
                
                if ($stmt->execute()) {
                    $success = "Data laboratorium berhasil diperbarui.";
                } else {
                    $error = "Error: " . $stmt->error;
                }
                break;
                
            case 'delete':
                $id_lab = $_POST['id_lab'];
                
                $stmt = $conn->prepare("DELETE FROM laboratorium WHERE id_lab = ?");
                $stmt->bind_param("i", $id_lab);
                
                if ($stmt->execute()) {
                    $success = "Data laboratorium berhasil dihapus.";
                } else {
                    $error = "Error: " . $stmt->error;
                }
                break;
        }
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$per_page = 10;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "(l.no_rm LIKE ? OR p.nm_pasien LIKE ? OR l.hasil_lab LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= "sss";
}

if (!empty($date_from)) {
    $where_conditions[] = "l.tgl_lab >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $where_conditions[] = "l.tgl_lab <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM laboratorium l 
              LEFT JOIN pasien p ON l.no_rm = p.no_rm 
              $where_clause";

if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_records = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $per_page);

// Get laboratory data
$sql = "SELECT l.*, p.nm_pasien 
        FROM laboratorium l 
        LEFT JOIN pasien p ON l.no_rm = p.no_rm 
        $where_clause 
        ORDER BY l.tgl_lab DESC, l.created_at DESC 
        LIMIT $per_page OFFSET $offset";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $laboratory_data = $stmt->get_result();
} else {
    $laboratory_data = $conn->query($sql);
}

// Get patients for dropdown
$patients = $conn->query("SELECT no_rm, nm_pasien FROM pasien ORDER BY nm_pasien");
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
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('addModal')">
                        <i class="fas fa-plus"></i> Tambah Data Lab
                    </button>
                    <button class="btn btn-secondary" onclick="window.print()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
            
            <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <!-- Search and Filter -->
            <div class="filter-section">
                <form method="GET" class="search-form">
                    <div class="search-group">
                        <input type="text" name="search" placeholder="Cari No. RM, nama pasien, atau hasil lab..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <input type="date" name="date_from" placeholder="Dari tanggal" 
                               value="<?php echo htmlspecialchars($date_from); ?>">
                        <input type="date" name="date_to" placeholder="Sampai tanggal" 
                               value="<?php echo htmlspecialchars($date_to); ?>">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-search"></i> Cari
                        </button>
                        <?php if (!empty($search) || !empty($date_from) || !empty($date_to)): ?>
                        <a href="laboratory.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Reset
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Laboratory Table -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>No. RM</th>
                            <th>Nama Pasien</th>
                            <th>Tanggal Lab</th>
                            <th>Hasil Lab</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($laboratory_data->num_rows > 0): ?>
                        <?php while ($lab = $laboratory_data->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $lab['id_lab']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($lab['no_rm']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($lab['nm_pasien'] ?? 'Tidak ditemukan'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($lab['tgl_lab'])); ?></td>
                            <td>
                                <div class="lab-result-preview">
                                    <?php echo htmlspecialchars(substr($lab['hasil_lab'], 0, 100)); ?>
                                    <?php if (strlen($lab['hasil_lab']) > 100): ?>...<?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars(substr($lab['ket'], 0, 50)); ?>
                                <?php if (strlen($lab['ket']) > 50): ?>...<?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-sm btn-info" onclick="viewLab(<?php echo htmlspecialchars(json_encode($lab)); ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-sm btn-warning" onclick="editLab(<?php echo htmlspecialchars(json_encode($lab)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-sm btn-danger" onclick="deleteLab(<?php echo $lab['id_lab']; ?>, '<?php echo htmlspecialchars($lab['no_rm']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">Tidak ada data laboratorium.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" class="btn btn-outline">
                    <i class="fas fa-chevron-left"></i> Sebelumnya
                </a>
                <?php endif; ?>
                
                <span class="pagination-info">
                    Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?> 
                    (<?php echo $total_records; ?> total data)
                </span>
                
                <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" class="btn btn-outline">
                    Selanjutnya <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add Laboratory Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Tambah Data Laboratorium</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="no_rm"><i class="fas fa-id-card"></i> No. Rekam Medis</label>
                            <select id="no_rm" name="no_rm" required>
                                <option value="">-- Pilih Pasien --</option>
                                <?php while ($patient = $patients->fetch_assoc()): ?>
                                <option value="<?php echo $patient['no_rm']; ?>">
                                    <?php echo $patient['no_rm']; ?> - <?php echo $patient['nm_pasien']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="tgl_lab"><i class="fas fa-calendar"></i> Tanggal Lab</label>
                            <input type="date" id="tgl_lab" name="tgl_lab" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="hasil_lab"><i class="fas fa-flask"></i> Hasil Laboratorium</label>
                        <textarea id="hasil_lab" name="hasil_lab" rows="6" required 
                                  placeholder="Contoh:&#10;Hemoglobin: 14.2 g/dL (Normal)&#10;Leukosit: 7.500/μL (Normal)&#10;Trombosit: 250.000/μL (Normal)"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="ket"><i class="fas fa-info-circle"></i> Keterangan</label>
                        <textarea id="ket" name="ket" rows="3" placeholder="Keterangan tambahan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Laboratory Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Data Laboratorium</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id_lab" name="id_lab">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_no_rm"><i class="fas fa-id-card"></i> No. Rekam Medis</label>
                            <select id="edit_no_rm" name="no_rm" required>
                                <option value="">-- Pilih Pasien --</option>
                                <?php 
                                $patients->data_seek(0); // Reset pointer
                                while ($patient = $patients->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $patient['no_rm']; ?>">
                                    <?php echo $patient['no_rm']; ?> - <?php echo $patient['nm_pasien']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="edit_tgl_lab"><i class="fas fa-calendar"></i> Tanggal Lab</label>
                            <input type="date" id="edit_tgl_lab" name="tgl_lab" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_hasil_lab"><i class="fas fa-flask"></i> Hasil Laboratorium</label>
                        <textarea id="edit_hasil_lab" name="hasil_lab" rows="6" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_ket"><i class="fas fa-info-circle"></i> Keterangan</label>
                        <textarea id="edit_ket" name="ket" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- View Laboratory Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-eye"></i> Detail Hasil Laboratorium</h3>
                <span class="close" onclick="closeModal('viewModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div class="lab-detail">
                    <div class="detail-row">
                        <strong>No. Rekam Medis:</strong>
                        <span id="view_no_rm"></span>
                    </div>
                    <div class="detail-row">
                        <strong>Nama Pasien:</strong>
                        <span id="view_nm_pasien"></span>
                    </div>
                    <div class="detail-row">
                        <strong>Tanggal Lab:</strong>
                        <span id="view_tgl_lab"></span>
                    </div>
                    <div class="detail-section">
                        <strong>Hasil Laboratorium:</strong>
                        <div id="view_hasil_lab" class="lab-result-content"></div>
                    </div>
                    <div class="detail-section">
                        <strong>Keterangan:</strong>
                        <div id="view_ket"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('viewModal')">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="printLabResult()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-trash"></i> Konfirmasi Hapus</h3>
                <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_id_lab" name="id_lab">
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus data laboratorium untuk No. RM <strong id="delete_no_rm"></strong>?</p>
                    <p class="text-danger">Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/script.js"></script>
    <script>
        function viewLab(lab) {
            document.getElementById('view_no_rm').textContent = lab.no_rm;
            document.getElementById('view_nm_pasien').textContent = lab.nm_pasien || 'Tidak ditemukan';
            document.getElementById('view_tgl_lab').textContent = new Date(lab.tgl_lab).toLocaleDateString('id-ID');
            document.getElementById('view_hasil_lab').innerHTML = lab.hasil_lab.replace(/\n/g, '<br>');
            document.getElementById('view_ket').textContent = lab.ket || '-';
            openModal('viewModal');
        }
        
        function editLab(lab) {
            document.getElementById('edit_id_lab').value = lab.id_lab;
            document.getElementById('edit_no_rm').value = lab.no_rm;
            document.getElementById('edit_tgl_lab').value = lab.tgl_lab;
            document.getElementById('edit_hasil_lab').value = lab.hasil_lab;
            document.getElementById('edit_ket').value = lab.ket || '';
            openModal('editModal');
        }
        
        function deleteLab(id, no_rm) {
            document.getElementById('delete_id_lab').value = id;
            document.getElementById('delete_no_rm').textContent = no_rm;
            openModal('deleteModal');
        }
        
        function printLabResult() {
            const content = document.querySelector('#viewModal .lab-detail').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Hasil Laboratorium</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .detail-row { margin-bottom: 10px; }
                        .detail-section { margin-bottom: 15px; }
                        .lab-result-content { white-space: pre-line; border: 1px solid #ddd; padding: 10px; margin-top: 5px; }
                    </style>
                </head>
                <body>
                    <h2>Hasil Laboratorium</h2>
                    ${content}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
        
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
    
    <style>
        .lab-result-preview {
            max-width: 200px;
            white-space: pre-line;
            font-size: 0.9rem;
        }
        
        .lab-detail .detail-row {
            display: flex;
            margin-bottom: 10px;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        
        .lab-detail .detail-row strong {
            min-width: 150px;
            color: #537D5D;
        }
        
        .lab-detail .detail-section {
            margin-bottom: 15px;
        }
        
        .lab-detail .detail-section strong {
            display: block;
            margin-bottom: 8px;
            color: #537D5D;
        }
        
        .lab-result-content {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            white-space: pre-line;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        
        .modal-lg {
            max-width: 800px;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .col-md-6 {
            flex: 1;
        }
    </style>
</body>
</html>
