<?php
session_start();
include '../config/database.php'; // Pastikan path ini benar
include '../includes/functions.php'; // Pastikan path ini benar

// Check if user is logged in and is doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'dokter') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$doctor_id = getDoctorId($conn, $_SESSION['user_id']); // Asumsi fungsi ini ada di functions.php

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

$page_title = "Daftar Obat";

// Handle form submissions for ADD, EDIT, DELETE
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Periksa apakah semua kunci array POST sudah didefinisikan sebelum digunakan
                $nm_obat = isset($_POST['nm_obat']) ? $_POST['nm_obat'] : '';
                $kemasan = isset($_POST['kemasan']) ? $_POST['kemasan'] : '';
                $harga = isset($_POST['harga']) ? (float)$_POST['harga'] : 0.00;
                $stok = isset($_POST['stok']) ? (int)$_POST['stok'] : 0; // Pastikan stok di-cast ke integer
                $keterangan = isset($_POST['keterangan']) ? $_POST['keterangan'] : '';
                
                // Tambahkan 'stok' ke query INSERT
                $stmt = $conn->prepare("INSERT INTO obat (nm_obat, kemasan, harga, stok, keterangan) VALUES (?, ?, ?, ?, ?)");
                // Tambahkan 'i' untuk tipe data integer 'stok' di bind_param
                $stmt->bind_param("ssdis", $nm_obat, $kemasan, $harga, $stok, $keterangan); 
                
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Obat berhasil ditambahkan!</div>';
                } else {
                    $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Gagal menambahkan obat: ' . $stmt->error . '</div>';
                }
                $stmt->close(); 
                break;
                
            case 'edit':
                $id_obat = isset($_POST['id_obat']) ? (int)$_POST['id_obat'] : 0;
                $nm_obat = isset($_POST['nm_obat']) ? $_POST['nm_obat'] : '';
                $kemasan = isset($_POST['kemasan']) ? $_POST['kemasan'] : '';
                $harga = isset($_POST['harga']) ? (float)$_POST['harga'] : 0.00;
                $stok = isset($_POST['stok']) ? (int)$_POST['stok'] : 0; // Pastikan stok di-cast ke integer
                $keterangan = isset($_POST['keterangan']) ? $_POST['keterangan'] : '';
                
                // Tambahkan 'stok' ke query UPDATE dan bind_param
                $stmt = $conn->prepare("UPDATE obat SET nm_obat=?, kemasan=?, harga=?, stok=?, keterangan=? WHERE id_obat=?");
                // Sesuaikan tipe data: s (string), s (string), d (double), i (integer), s (string), i (integer)
                $stmt->bind_param("ssdsii", $nm_obat, $kemasan, $harga, $stok, $keterangan, $id_obat); 
                
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Obat berhasil diperbarui!</div>';
                } else {
                    $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Gagal memperbarui obat: ' . $stmt->error . '</div>';
                }
                $stmt->close(); 
                break;
                
            case 'delete':
                $id_obat = isset($_POST['id_obat']) ? (int)$_POST['id_obat'] : 0;
                
                // Gunakan prepared statement untuk DELETE juga demi keamanan
                $stmt = $conn->prepare("DELETE FROM obat WHERE id_obat = ?");
                $stmt->bind_param("i", $id_obat);

                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Obat berhasil dihapus!</div>';
                } else {
                    $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Gagal menghapus obat: ' . $stmt->error . '</div>';
                }
                $stmt->close(); 
                break;
        }
    }
}

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = "";

if (!empty($search)) {
    // Sesuaikan kolom yang dicari, gunakan prepared statement untuk pencarian lebih aman jika memungkinkan
    $search_condition = "WHERE nm_obat LIKE ? OR kemasan LIKE ? OR keterangan LIKE ?"; 
    $search_param = "%" . $search . "%";
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Total records query (sesuaikan dengan prepared statement jika search_condition ada)
if (!empty($search_condition)) {
    $stmt_count = $conn->prepare("SELECT COUNT(*) as count FROM obat " . $search_condition);
    $stmt_count->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt_count->execute();
    $total_records_result = $stmt_count->get_result();
    $total_records = $total_records_result->fetch_assoc()['count'];
    $stmt_count->close();
} else {
    $total_records_query = "SELECT COUNT(*) as count FROM obat";
    $total_records_result = $conn->query($total_records_query);
    $total_records = $total_records_result->fetch_assoc()['count'];
}

$total_pages = ceil($total_records / $limit);

// Get medicines (including 'stok')
$sql = "SELECT id_obat, nm_obat, kemasan, harga, stok, keterangan FROM obat $search_condition ORDER BY nm_obat LIMIT ?, ?"; // Tambahkan stok di SELECT
$stmt = $conn->prepare($sql);

if (!empty($search_condition)) {
    $stmt->bind_param("sssii", $search_param, $search_param, $search_param, $offset, $limit);
} else {
    $stmt->bind_param("ii", $offset, $limit);
}
$stmt->execute();
$result = $stmt->get_result();

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
            <h2><i class="fas fa-pills"></i> <?php echo $page_title; ?></h2>
            <button class="btn btn-primary" onclick="openModal('addModal')">
                <i class="fas fa-plus"></i> Tambah Obat
            </button>
        </div>
        
        <?php echo $message; ?>
        
        <div class="table-filters">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Cari obat..." value="<?php echo htmlspecialchars($search); ?>">
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
            <table class="data-table" id="obatTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Obat</th>
                        <th>Kemasan</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id_obat']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['nm_obat']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['kemasan'] ?? '-'); ?></td>
                            <td>Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                            <td><?php echo $row['stok']; ?></td>
                            <td><?php echo htmlspecialchars(substr($row['keterangan'], 0, 50) . (strlen($row['keterangan']) > 50 ? '...' : '') ?? '-'); ?></td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="editMedicine(<?php echo $row['id_obat']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-secondary" onclick="viewMedicine(<?php echo $row['id_obat']; ?>)">
                                    <i class="fas fa-eye"></i> Lihat
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteMedicine(<?php echo $row['id_obat']; ?>)">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">Tidak ada data obat</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
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
    
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Tambah Obat Baru</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label><i class="fas fa-pills"></i> Nama Obat</label>
                        <input type="text" name="nm_obat" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-box"></i> Kemasan</label>
                        <input type="text" name="kemasan" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-money-bill"></i> Harga</label>
                        <input type="number" name="harga" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-cubes"></i> Stok</label>
                        <input type="number" name="stok" required>
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
    
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Obat</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id_obat" id="edit_id_obat">
                    
                    <div class="form-group">
                        <label><i class="fas fa-pills"></i> Nama Obat</label>
                        <input type="text" name="nm_obat" id="edit_nm_obat" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-box"></i> Kemasan</label>
                        <input type="text" name="kemasan" id="edit_kemasan" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-money-bill"></i> Harga</label>
                        <input type="number" name="harga" id="edit_harga" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-cubes"></i> Stok</label>
                        <input type="number" name="stok" id="edit_stok" required>
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
    
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-pills"></i> Detail Obat</h3>
                <span class="close" onclick="closeModal('viewModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div id="medicineDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('viewModal')">Tutup</button>
            </div>
        </div>
    </div>
    
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus</h3>
                <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus obat ini?</p>
                <p><strong>Nama Obat: </strong> <span id="deleteMedicineName"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Batal</button>
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_obat" id="delete_id_obat_confirm">
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
            // Hanya reload halaman jika search term kosong atau lebih dari 2 karakter
            // Untuk mencegah terlalu banyak request saat mengetik
            if (searchTerm.length > 2 || searchTerm.length === 0) {
                window.location.href = '?search=' + encodeURIComponent(searchTerm);
            }
        });
        
        // View medicine details
        function viewMedicine(id) {
            fetch(`get_medicine.php?id=${id}`) // Pastikan path ini benar
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const medicine = data.medicine;
                        document.getElementById('medicineDetails').innerHTML = `
                            <div class="info-group">
                                <label>ID Obat:</label>
                                <span>${medicine.id_obat}</span>
                            </div>
                            <div class="info-group">
                                <label>Nama Obat:</label>
                                <span>${medicine.nm_obat}</span>
                            </div>
                            <div class="info-group">
                                <label>Kemasan:</label>
                                <span>${medicine.kemasan || '-'}</span>
                            </div>
                            <div class="info-group">
                                <label>Harga:</label>
                                <span>Rp ${new Intl.NumberFormat('id-ID').format(medicine.harga)}</span>
                            </div>
                            <div class="info-group">
                                <label>Stok:</label>
                                <span>${medicine.stok}</span>
                            </div>
                            <div class="info-group">
                                <label>Keterangan:</label>
                                <span>${medicine.keterangan || '-'}</span>
                            </div>
                        `;
                        openModal('viewModal');
                    } else {
                        alert(data.message || 'Obat tidak ditemukan.');
                    }
                })
                .catch(error => {
                    console.error('Error fetching medicine details:', error);
                    alert('Terjadi kesalahan saat mengambil detail obat.');
                });
        }
        
        // Edit medicine
        function editMedicine(id) {
            fetch(`get_medicine.php?id=${id}`) // Pastikan path ini benar
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const medicine = data.medicine;
                        document.getElementById('edit_id_obat').value = medicine.id_obat;
                        document.getElementById('edit_nm_obat').value = medicine.nm_obat;
                        document.getElementById('edit_kemasan').value = medicine.kemasan;
                        document.getElementById('edit_harga').value = medicine.harga;
                        document.getElementById('edit_stok').value = medicine.stok; // Setel nilai stok
                        document.getElementById('edit_keterangan').value = medicine.keterangan;
                        openModal('editModal');
                    } else {
                        alert(data.message || 'Obat tidak ditemukan.');
                    }
                })
                .catch(error => {
                    console.error('Error fetching medicine for edit:', error);
                    alert('Terjadi kesalahan saat mengambil data obat untuk diedit.');
                });
        }
        
        // Delete medicine
        function deleteMedicine(id) {
            // Fetch medicine name before showing delete modal
            fetch(`get_medicine.php?id=${id}`) // Pastikan path ini benar
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('delete_id_obat_confirm').value = id;
                        document.getElementById('deleteMedicineName').textContent = data.medicine.nm_obat;
                        openModal('deleteModal');
                    } else {
                        alert(data.message || 'Obat tidak ditemukan.');
                    }
                })
                .catch(error => {
                    console.error('Error fetching medicine name for delete:', error);
                    alert('Terjadi kesalahan saat mengambil nama obat untuk dihapus.');
                });
        }
        
        function exportData() {
            window.open('export_medicines.php', '_blank'); // Asumsi ada file export_medicines.php
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