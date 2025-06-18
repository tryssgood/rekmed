<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Manajemen Obat";
$message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $nm_obat = $_POST['nm_obat'];
            $jml_obat = $_POST['jml_obat'];
            $ukuran = $_POST['ukuran'];
            $harga = $_POST['harga'];
            
            $stmt = $conn->prepare("INSERT INTO obat (nm_obat, jml_obat, ukuran, harga) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sisd", $nm_obat, $jml_obat, $ukuran, $harga);
            
            if ($stmt->execute()) {
                $message = "Obat berhasil ditambahkan.";
            } else {
                $message = "Error: " . $stmt->error;
            }
        } elseif ($_POST['action'] == 'edit') {
            $id_obat = $_POST['id_obat'];
            $nm_obat = $_POST['nm_obat'];
            $jml_obat = $_POST['jml_obat'];
            $ukuran = $_POST['ukuran'];
            $harga = $_POST['harga'];
            
            $stmt = $conn->prepare("UPDATE obat SET nm_obat = ?, jml_obat = ?, ukuran = ?, harga = ? WHERE id_obat = ?");
            $stmt->bind_param("sisdi", $nm_obat, $jml_obat, $ukuran, $harga, $id_obat);
            
            if ($stmt->execute()) {
                $message = "Data obat berhasil diperbarui.";
            } else {
                $message = "Error: " . $stmt->error;
            }
        } elseif ($_POST['action'] == 'delete') {
            $id_obat = $_POST['id_obat'];
            
            $stmt = $conn->prepare("DELETE FROM obat WHERE id_obat = ?");
            $stmt->bind_param("i", $id_obat);
            
            if ($stmt->execute()) {
                $message = "Obat berhasil dihapus.";
            } else {
                $message = "Error: " . $stmt->error;
            }
        } elseif ($_POST['action'] == 'update_stock') {
            $id_obat = $_POST['id_obat'];
            $jml_obat = $_POST['jml_obat'];
            
            $stmt = $conn->prepare("UPDATE obat SET jml_obat = ? WHERE id_obat = ?");
            $stmt->bind_param("ii", $jml_obat, $id_obat);
            
            if ($stmt->execute()) {
                $message = "Stok obat berhasil diperbarui.";
            } else {
                $message = "Error: " . $stmt->error;
            }
        }
    }
}

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$stock_filter = isset($_GET['stock_filter']) ? $_GET['stock_filter'] : '';

$search_condition = '';
$conditions = [];

if (!empty($search)) {
    $conditions[] = "(nm_obat LIKE '%$search%' OR ukuran LIKE '%$search%')";
}

if ($stock_filter == 'low') {
    $conditions[] = "jml_obat < 10";
} elseif ($stock_filter == 'empty') {
    $conditions[] = "jml_obat = 0";
}

if (!empty($conditions)) {
    $search_condition = "WHERE " . implode(" AND ", $conditions);
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$total_records_query = "SELECT COUNT(*) as count FROM obat $search_condition";
$total_records_result = $conn->query($total_records_query);
$total_records = $total_records_result->fetch_assoc()['count'];
$total_pages = ceil($total_records / $limit);

$sql = "SELECT * FROM obat $search_condition ORDER BY nm_obat LIMIT $offset, $limit";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo $page_title; ?> - Sistem Rekam Medis</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
     
        
        <div class="content">
            <div class="content-header">
                <h2><i class="fas fa-pills"></i> <?php echo $page_title; ?></h2>
                <button class="btn btn-primary" onclick="showAddModal()">
                    <i class="fas fa-plus"></i> Tambah Obat
                </button>
            </div>
            
            <?php if (!empty($message)): ?>
            <div class="alert alert-info">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <!-- Search and Filter -->
            <div class="search-filter">
                <form method="GET" action="" class="search-form">
                    <div class="search-input">
                        <input type="text" name="search" placeholder="Cari obat..." value="<?php echo htmlspecialchars($search); ?>">
                        <select name="stock_filter" class="stock-filter">
                            <option value="">Semua Stok</option>
                            <option value="low" <?php echo $stock_filter == 'low' ? 'selected' : ''; ?>>Stok Rendah (&lt;10)</option>
                            <option value="empty" <?php echo $stock_filter == 'empty' ? 'selected' : ''; ?>>Stok Habis</option>
                        </select>
                        <button type="submit" class="search-button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nama Obat</th>
                                <th>Ukuran</th>
                                <th>Stok</th>
                                <th>Harga</th>
                                <th>Status Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['nm_obat']; ?></td>
                                    <td><?php echo $row['ukuran']; ?></td>
                                    <td>
                                        <span class="stock-quantity"><?php echo $row['jml_obat']; ?></span>
                                    </td>
                                    <td>Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                                    <td>
                                        <?php if ($row['jml_obat'] == 0): ?>
                                        <span class="badge badge-danger">Habis</span>
                                        <?php elseif ($row['jml_obat'] < 10): ?>
                                        <span class="badge badge-warning">Rendah</span>
                                        <?php else: ?>
                                        <span class="badge badge-success">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-info" onclick="showEditModal(<?php echo $row['id_obat']; ?>, '<?php echo addslashes($row['nm_obat']); ?>', <?php echo $row['jml_obat']; ?>, '<?php echo addslashes($row['ukuran']); ?>', <?php echo $row['harga']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="showStockModal(<?php echo $row['id_obat']; ?>, '<?php echo addslashes($row['nm_obat']); ?>', <?php echo $row['jml_obat']; ?>)">
                                                <i class="fas fa-boxes"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $row['id_obat']; ?>, '<?php echo addslashes($row['nm_obat']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada data obat.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&stock_filter=<?php echo urlencode($stock_filter); ?>" class="pagination-link">
                            <i class="fas fa-chevron-left"></i> Sebelumnya
                        </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&stock_filter=<?php echo urlencode($stock_filter); ?>" class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&stock_filter=<?php echo urlencode($stock_filter); ?>" class="pagination-link">
                            Selanjutnya <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Tambah Obat</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="nm_obat"><i class="fas fa-pills"></i> Nama Obat</label>
                        <input type="text" id="nm_obat" name="nm_obat" required placeholder="Contoh: Paracetamol">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="ukuran"><i class="fas fa-weight"></i> Ukuran/Dosis</label>
                            <input type="text" id="ukuran" name="ukuran" placeholder="Contoh: 500mg">
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="jml_obat"><i class="fas fa-boxes"></i> Jumlah Stok</label>
                            <input type="number" id="jml_obat" name="jml_obat" min="0" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="harga"><i class="fas fa-money-bill"></i> Harga (Rp)</label>
                        <input type="number" id="harga" name="harga" min="0" step="0.01" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">
                            <i class="fas fa-times"></i> Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Obat</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_id_obat" name="id_obat">
                    
                    <div class="form-group">
                        <label for="edit_nm_obat"><i class="fas fa-pills"></i> Nama Obat</label>
                        <input type="text" id="edit_nm_obat" name="nm_obat" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_ukuran"><i class="fas fa-weight"></i> Ukuran/Dosis</label>
                            <input type="text" id="edit_ukuran" name="ukuran">
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="edit_jml_obat"><i class="fas fa-boxes"></i> Jumlah Stok</label>
                            <input type="number" id="edit_jml_obat" name="jml_obat" min="0" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_harga"><i class="fas fa-money-bill"></i> Harga (Rp)</label>
                        <input type="number" id="edit_harga" name="harga" min="0" step="0.01" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">
                            <i class="fas fa-times"></i> Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Stock Update Modal -->
    <div id="stockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-boxes"></i> Update Stok</h3>
                <span class="close" onclick="closeModal('stockModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>Update stok untuk obat: <span id="stock_medicine_name" class="font-weight-bold"></span></p>
                <p>Stok saat ini: <span id="current_stock" class="font-weight-bold"></span></p>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_stock">
                    <input type="hidden" id="stock_id_obat" name="id_obat">
                    
                    <div class="form-group">
                        <label for="stock_jml_obat"><i class="fas fa-boxes"></i> Stok Baru</label>
                        <input type="number" id="stock_jml_obat" name="jml_obat" min="0" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-boxes"></i> Update Stok
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('stockModal')">
                            <i class="fas fa-times"></i> Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus</h3>
                <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus obat <span id="delete_name" class="font-weight-bold"></span>?</p>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" id="delete_id_obat" name="id_obat">
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">
                            <i class="fas fa-times"></i> Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/script.js"></script>
    <script>
        function showAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function showEditModal(id, name, stock, size, price) {
            document.getElementById('edit_id_obat').value = id;
            document.getElementById('edit_nm_obat').value = name;
            document.getElementById('edit_jml_obat').value = stock;
            document.getElementById('edit_ukuran').value = size;
            document.getElementById('edit_harga').value = price;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function showStockModal(id, name, currentStock) {
            document.getElementById('stock_id_obat').value = id;
            document.getElementById('stock_medicine_name').textContent = name;
            document.getElementById('current_stock').textContent = currentStock;
            document.getElementById('stock_jml_obat').value = currentStock;
            document.getElementById('stockModal').style.display = 'block';
        }
        
        function confirmDelete(id, name) {
            document.getElementById('delete_id_obat').value = id;
            document.getElementById('delete_name').textContent = name;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
    
    <style>
        .search-filter {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .search-form {
            flex: 1;
            max-width: 600px;
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
        
        .stock-filter {
            padding: 12px 15px;
            border: none;
            outline: none;
            background-color: #f8f9fa;
            border-left: 1px solid #e9ecef;
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
        
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .stock-quantity {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
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
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .col-md-6 {
            flex: 1;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .font-weight-bold {
            font-weight: 700;
        }
        
        @media (max-width: 768px) {
            .search-input {
                flex-direction: column;
            }
            
            .stock-filter {
                border-left: none;
                border-top: 1px solid #e9ecef;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .action-buttons {
                flex-wrap: wrap;
            }
        }
    </style>
</body>
</html>
