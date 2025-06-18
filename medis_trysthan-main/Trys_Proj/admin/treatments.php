<?php
session_start();
include '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Manajemen Tindakan";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $nm_tindakan = $_POST['nm_tindakan'];
                $ket = $_POST['ket'];
                
                $stmt = $conn->prepare("INSERT INTO tindakan (nm_tindakan, ket) VALUES (?, ?)");
                $stmt->bind_param("ss", $nm_tindakan, $ket);
                
                if ($stmt->execute()) {
                    $success = "Tindakan berhasil ditambahkan.";
                } else {
                    $error = "Error: " . $stmt->error;
                }
                break;
                
            case 'edit':
                $id_tindakan = $_POST['id_tindakan'];
                $nm_tindakan = $_POST['nm_tindakan'];
                $ket = $_POST['ket'];
                
                $stmt = $conn->prepare("UPDATE tindakan SET nm_tindakan = ?, ket = ? WHERE id_tindakan = ?");
                $stmt->bind_param("ssi", $nm_tindakan, $ket, $id_tindakan);
                
                if ($stmt->execute()) {
                    $success = "Tindakan berhasil diperbarui.";
                } else {
                    $error = "Error: " . $stmt->error;
                }
                break;
                
            case 'delete':
                $id_tindakan = $_POST['id_tindakan'];
                
                // Check if treatment is used in medical records
                $check = $conn->prepare("SELECT COUNT(*) as count FROM rekam_medis WHERE id_tindakan = ?");
                $check->bind_param("i", $id_tindakan);
                $check->execute();
                $result = $check->get_result();
                $count = $result->fetch_assoc()['count'];
                
                if ($count > 0) {
                    $error = "Tindakan tidak dapat dihapus karena masih digunakan dalam rekam medis.";
                } else {
                    $stmt = $conn->prepare("DELETE FROM tindakan WHERE id_tindakan = ?");
                    $stmt->bind_param("i", $id_tindakan);
                    
                    if ($stmt->execute()) {
                        $success = "Tindakan berhasil dihapus.";
                    } else {
                        $error = "Error: " . $stmt->error;
                    }
                }
                break;
        }
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$per_page = 10;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $per_page;

// Build query
$where_clause = "";
$params = [];
$types = "";

if (!empty($search)) {
    $where_clause = "WHERE nm_tindakan LIKE ? OR ket LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param];
    $types = "ss";
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM tindakan $where_clause";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_records = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $per_page);

// Get treatments
$sql = "SELECT t.*, 
        (SELECT COUNT(*) FROM rekam_medis WHERE id_tindakan = t.id_tindakan) as usage_count
        FROM tindakan t 
        $where_clause 
        ORDER BY t.nm_tindakan 
        LIMIT $per_page OFFSET $offset";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $treatments = $stmt->get_result();
} else {
    $treatments = $conn->query($sql);
}
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
                <h2><i class="fas fa-procedures"></i> <?php echo $page_title; ?></h2>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('addModal')">
                        <i class="fas fa-plus"></i> Tambah Tindakan
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
                        <input type="text" name="search" placeholder="Cari nama tindakan atau keterangan..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-search"></i> Cari
                        </button>
                        <?php if (!empty($search)): ?>
                        <a href="treatments.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Reset
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Treatments Table -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Tindakan</th>
                            <th>Keterangan</th>
                            <th>Digunakan</th>
                            <th>Tanggal Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($treatments->num_rows > 0): ?>
                        <?php while ($treatment = $treatments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $treatment['id_tindakan']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($treatment['nm_tindakan']); ?></strong>
                            </td>
                            <td>
                                <?php echo htmlspecialchars(substr($treatment['ket'], 0, 100)); ?>
                                <?php if (strlen($treatment['ket']) > 100): ?>...<?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $treatment['usage_count'] > 0 ? 'badge-success' : 'badge-secondary'; ?>">
                                    <?php echo $treatment['usage_count']; ?> kali
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($treatment['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-sm btn-info" onclick="editTreatment(<?php echo htmlspecialchars(json_encode($treatment)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($treatment['usage_count'] == 0): ?>
                                    <button class="btn-sm btn-danger" onclick="deleteTreatment(<?php echo $treatment['id_tindakan']; ?>, '<?php echo htmlspecialchars($treatment['nm_tindakan']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php else: ?>
                                    <button class="btn-sm btn-secondary" disabled title="Tidak dapat dihapus karena sedang digunakan">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">Tidak ada data tindakan.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-outline">
                    <i class="fas fa-chevron-left"></i> Sebelumnya
                </a>
                <?php endif; ?>
                
                <span class="pagination-info">
                    Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?> 
                    (<?php echo $total_records; ?> total data)
                </span>
                
                <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-outline">
                    Selanjutnya <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add Treatment Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Tambah Tindakan</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nm_tindakan"><i class="fas fa-procedures"></i> Nama Tindakan</label>
                        <input type="text" id="nm_tindakan" name="nm_tindakan" required>
                    </div>
                    <div class="form-group">
                        <label for="ket"><i class="fas fa-info-circle"></i> Keterangan</label>
                        <textarea id="ket" name="ket" rows="4" placeholder="Deskripsi tindakan..."></textarea>
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
    
    <!-- Edit Treatment Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Tindakan</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id_tindakan" name="id_tindakan">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_nm_tindakan"><i class="fas fa-procedures"></i> Nama Tindakan</label>
                        <input type="text" id="edit_nm_tindakan" name="nm_tindakan" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_ket"><i class="fas fa-info-circle"></i> Keterangan</label>
                        <textarea id="edit_ket" name="ket" rows="4"></textarea>
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
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-trash"></i> Konfirmasi Hapus</h3>
                <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_id_tindakan" name="id_tindakan">
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus tindakan <strong id="delete_treatment_name"></strong>?</p>
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
        function editTreatment(treatment) {
            document.getElementById('edit_id_tindakan').value = treatment.id_tindakan;
            document.getElementById('edit_nm_tindakan').value = treatment.nm_tindakan;
            document.getElementById('edit_ket').value = treatment.ket || '';
            openModal('editModal');
        }
        
        function deleteTreatment(id, name) {
            document.getElementById('delete_id_tindakan').value = id;
            document.getElementById('delete_treatment_name').textContent = name;
            openModal('deleteModal');
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
</body>
</html>
