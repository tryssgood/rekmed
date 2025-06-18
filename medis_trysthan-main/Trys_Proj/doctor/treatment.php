<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

// Check if user is logged in and is doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'dokter') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Daftar ";
$message = '';
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

// Ensure 'biaya' and 'keterangan' keys exist in the fetched rows
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $nm_tindakan = $_POST['nm_tindakan'];
                $biaya = $_POST['biaya'];
                $keterangan = $_POST['keterangan'];
                
             // treatment.php, baris 40
$stmt = $conn->prepare('INSERT INTO tindakan (nm_tindakan, biaya, ket) VALUES (?, ?, ?)');
// Catatan: Anda perlu memastikan semua kolom yang Anda cantumkan di sini benar-benar ada di tabel rekam_medis Anda.
// dan sesuai dengan urutan parameter di bind_param.
// Kolom 'id_obat', 'jumlah', 'dosis', 'catatan' mungkin perlu dipindahkan ke tabel detail_resep terpisah
// jika rekam_medis hanya mencatat ringkasan.
                // treatment.php, baris 45
$stmt->bind_param("sds", $nm_tindakan, $biaya, $keterangan);
                
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Tindakan berhasil ditambahkan!</div>';
                } else {
                    $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Gagal menambahkan tindakan: ' . $conn->error . '</div>';
                }
                break;
                
            case 'edit':
                $id_tindakan = $_POST['id_tindakan'];
                $nm_tindakan = $_POST['nm_tindakan'];
                $biaya = $_POST['biaya'];
                $keterangan = $_POST['keterangan'];
                
               // treatment.php, baris 62
$stmt = $conn->prepare("UPDATE tindakan SET nm_tindakan=?, biaya=?, ket=? WHERE id_tindakan=?");
              // treatment.php, baris 63
$stmt->bind_param("sdsi", $nm_tindakan, $biaya, $keterangan, $id_tindakan);
                
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Tindakan berhasil diperbarui!</div>';
                } else {
                    $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Gagal memperbarui tindakan: ' . $conn->error . '</div>';
                }
                break;
                
            case 'delete':
                $id_tindakan = $_POST['id_tindakan'];
                
                if ($conn->query("DELETE FROM tindakan WHERE id_tindakan = $id_tindakan")) {
                    $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Tindakan berhasil dihapus!</div>';
                } else {
                    $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Gagal menghapus tindakan: ' . $conn->error . '</div>';
                }
                break;
        }
    }
}

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = "";

$search_condition = "WHERE nm_tindakan LIKE '%$search%' OR ket LIKE '%$search%'";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$total_records_query = "SELECT COUNT(*) as count FROM tindakan $search_condition";
$total_records_result = $conn->query($total_records_query);
$total_records = $total_records_result->fetch_assoc()['count'];
$total_pages = ceil($total_records / $limit);

// Get treatments
$sql = "SELECT * FROM tindakan $search_condition ORDER BY nm_tindakan LIMIT $offset, $limit";
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
    
    <div class="main-container">
        <div class="content-header">
            <h2><i class="fas fa-procedures"></i> <?php echo $page_title; ?></h2>
            <button class="btn btn-primary" onclick="openModal('addModal')">
                <i class="fas fa-plus"></i> Tambah Tindakan
            </button>
        </div>
        
        <?php echo $message; ?>
        
        <!-- Search and Filter -->
        <div class="table-filters">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Cari tindakan..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
                <button class="btn btn-outline-primary" onclick="exportData()">
                    <i class="fas fa-download"></i> Export
                </button>
                <button class="btn btn-outline-primary" onclick="printData()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Tindakan</th>
                        <th>Biaya</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                       
                    </tr>
                </thead>
               <tbody>
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id_tindakan']; ?></td>
                <td><strong><?php echo $row['nm_tindakan']; ?></strong></td>
                <td>Rp <?php echo isset($row['biaya']) ? number_format($row['biaya'], 0, ',', '.') : '0'; ?></td>
                <td><?php echo isset($row['ket']) ? substr($row['ket'], 0, 50) . (strlen($row['ket']) > 50 ? '...' : '') : '-'; ?></td>
                <td class="action-buttons">
                    <button class="btn btn-sm btn-info" onclick="viewTreatment(<?php echo $row['id_tindakan']; ?>)">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="editTreatment(<?php echo $row['id_tindakan']; ?>)">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteTreatment(<?php echo $row['id_tindakan']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="5" class="text-center">Tidak ada data tindakan</td>
        </tr>
    <?php endif; ?>
</tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Add Treatment Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Tambah Tindakan Baru</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label><i class="fas fa-procedures"></i> Nama Tindakan</label>
                        <input type="text" name="nm_tindakan" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-money-bill"></i> Biaya</label>
                        <input type="number" name="biaya" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-sticky-note"></i> Keterangan</label>
                        <textarea name="keterangan" rows="3"></textarea>
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
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id_tindakan" id="edit_id_tindakan">
                    
                    <div class="form-group">
                        <label><i class="fas fa-procedures"></i> Nama Tindakan</label>
                        <input type="text" name="nm_tindakan" id="edit_nm_tindakan" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-money-bill"></i> Biaya</label>
                        <input type="number" name="biaya" id="edit_biaya" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-sticky-note"></i> Keterangan</label>
                        <textarea name="keterangan" id="edit_keterangan" rows="3"></textarea>
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
    
    <!-- View Treatment Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-procedures"></i> Detail Tindakan</h3>
                <span class="close" onclick="closeModal('viewModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div id="treatmentDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('viewModal')">Tutup</button>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus</h3>
                <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus tindakan ini?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Batal</button>
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_tindakan" id="delete_id_tindakan">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value;
            if (searchTerm.length > 2 || searchTerm.length === 0) {
                window.location.href = '?search=' + encodeURIComponent(searchTerm);
            }
        });
        
        // View treatment details
        function viewTreatment(id) {
            fetch(`get_treatment.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const treatment = data.treatment;
                        document.getElementById('treatmentDetails').innerHTML = `
                            <div class="info-card">
                                <h4><i class="fas fa-procedures"></i> Informasi Tindakan</h4>
                                <p><strong>ID:</strong> ${treatment.id_tindakan}</p>
                                <p><strong>Nama Tindakan:</strong> ${treatment.nm_tindakan}</p>
                                <p><strong>Biaya:</strong> Rp ${new Intl.NumberFormat('id-ID').format(treatment.biaya)}</p>
                                <p><strong>Keterangan:</strong> ${treatment.keterangan || '-'}</p>
                            </div>
                        `;
                        openModal('viewModal');
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Edit treatment
        function editTreatment(id) {
            fetch(`get_treatment.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const treatment = data.treatment;
                        document.getElementById('edit_id_tindakan').value = treatment.id_tindakan;
                        document.getElementById('edit_nm_tindakan').value = treatment.nm_tindakan;
                        document.getElementById('edit_biaya').value = treatment.biaya;
                        document.getElementById('edit_keterangan').value = treatment.ket;
                        openModal('editModal');
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Delete treatment
        function deleteTreatment(id) {
            document.getElementById('delete_id_tindakan').value = id;
            openModal('deleteModal');
        }
        
        function exportData() {
            window.open('export_treatments.php', '_blank');
        }
        
        function printData() {
            window.print();
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
