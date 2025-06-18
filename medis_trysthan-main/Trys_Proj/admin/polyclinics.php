<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Manajemen Poliklinik";
$message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $nm_poli = $_POST['nm_poli'];
            $lokasi = $_POST['lokasi'];
            
            $stmt = $conn->prepare("INSERT INTO poliklinik (nm_poli, lokasi) VALUES (?, ?)");
            $stmt->bind_param("ss", $nm_poli, $lokasi);
            
            if ($stmt->execute()) {
                $message = "Poliklinik berhasil ditambahkan.";
            } else {
                $message = "Error: " . $stmt->error;
            }
        } elseif ($_POST['action'] == 'edit') {
            $id_poli = $_POST['id_poli'];
            $nm_poli = $_POST['nm_poli'];
            $lokasi = $_POST['lokasi'];
            
            $stmt = $conn->prepare("UPDATE poliklinik SET nm_poli = ?, lokasi = ? WHERE id_poli = ?");
            $stmt->bind_param("ssi", $nm_poli, $lokasi, $id_poli);
            
            if ($stmt->execute()) {
                $message = "Data poliklinik berhasil diperbarui.";
            } else {
                $message = "Error: " . $stmt->error;
            }
        } elseif ($_POST['action'] == 'delete') {
            $id_poli = $_POST['id_poli'];
            
            $stmt = $conn->prepare("DELETE FROM poliklinik WHERE id_poli = ?");
            $stmt->bind_param("i", $id_poli);
            
            if ($stmt->execute()) {
                $message = "Poliklinik berhasil dihapus.";
            } else {
                $message = "Error: " . $stmt->error;
            }
        }
    }
}

// Get polyclinic data
$sql = "SELECT p.*, COUNT(d.id_dokter) as total_doctors 
        FROM poliklinik p 
        LEFT JOIN dokter d ON p.id_poli = d.id_poli 
        GROUP BY p.id_poli 
        ORDER BY p.nm_poli";
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
                <h2><i class="fas fa-hospital"></i> <?php echo $page_title; ?></h2>
                <button class="btn btn-primary" onclick="showAddModal()">
                    <i class="fas fa-plus"></i> Tambah Poliklinik
                </button>
            </div>
            
            <?php if (!empty($message)): ?>
            <div class="alert alert-info">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nama Poliklinik</th>
                                <th>Lokasi</th>
                                <th>Jumlah Dokter</th>
                                <th>Tanggal Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['nm_poli']; ?></td>
                                    <td><?php echo $row['lokasi']; ?></td>
                                    <td>
                                        <span class="badge badge-info"><?php echo $row['total_doctors']; ?> Dokter</span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-info" onclick="showEditModal(<?php echo $row['id_poli']; ?>, '<?php echo addslashes($row['nm_poli']); ?>', '<?php echo addslashes($row['lokasi']); ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $row['id_poli']; ?>, '<?php echo addslashes($row['nm_poli']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada data poliklinik.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Tambah Poliklinik</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="nm_poli"><i class="fas fa-hospital"></i> Nama Poliklinik</label>
                        <input type="text" id="nm_poli" name="nm_poli" required placeholder="Contoh: Poli Umum">
                    </div>
                    
                    <div class="form-group">
                        <label for="lokasi"><i class="fas fa-map-marker-alt"></i> Lokasi</label>
                        <input type="text" id="lokasi" name="lokasi" placeholder="Contoh: Lantai 1 Ruang 101">
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
                <h3><i class="fas fa-edit"></i> Edit Poliklinik</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_id_poli" name="id_poli">
                    
                    <div class="form-group">
                        <label for="edit_nm_poli"><i class="fas fa-hospital"></i> Nama Poliklinik</label>
                        <input type="text" id="edit_nm_poli" name="nm_poli" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_lokasi"><i class="fas fa-map-marker-alt"></i> Lokasi</label>
                        <input type="text" id="edit_lokasi" name="lokasi">
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
    
    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus</h3>
                <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus poliklinik <span id="delete_name" class="font-weight-bold"></span>?</p>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" id="delete_id_poli" name="id_poli">
                    
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
        
        function showEditModal(id, name, location) {
            document.getElementById('edit_id_poli').value = id;
            document.getElementById('edit_nm_poli').value = name;
            document.getElementById('edit_lokasi').value = location;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function confirmDelete(id, name) {
            document.getElementById('delete_id_poli').value = id;
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
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .font-weight-bold {
            font-weight: 700;
        }
    </style>
</body>
</html>
