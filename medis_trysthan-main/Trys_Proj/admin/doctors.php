<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Manajemen Dokter";
$message = '';

// Process form submission for adding/editing doctor
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $nm_dokter = $_POST['nm_dokter'];
            $id_poli = $_POST['id_poli'];
            $SIP = $_POST['SIP'];
            $tempat_lhr = $_POST['tempat_lhr'];
            $no_hp = $_POST['no_hp'];
            $alamat = $_POST['alamat'];
            
            // Create user account for doctor
            $username = strtolower(str_replace(' ', '.', $nm_dokter));
            $default_password = 'dokter123';
            $password = password_hash($default_password, PASSWORD_DEFAULT);
            $role = 'dokter';
            
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Insert user account
                $stmt = $conn->prepare("INSERT INTO login (username, password, role) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $username, $password, $role);
                $stmt->execute();
                $user_id = $conn->insert_id;
                
                // Insert doctor data
                $stmt = $conn->prepare("INSERT INTO dokter (id_poli, id_user, nm_dokter, SIP, tempat_lhr, no_hp, alamat) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisssss", $id_poli, $user_id, $nm_dokter, $SIP, $tempat_lhr, $no_hp, $alamat);
                $stmt->execute();
                
                // Commit transaction
                $conn->commit();
                $message = "Data dokter berhasil ditambahkan. Username: $username, Password awal: $default_password";
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $message = "Error: " . $e->getMessage();
            }
        } elseif ($_POST['action'] == 'edit') {
            $id_dokter = $_POST['id_dokter'];
            $nm_dokter = $_POST['nm_dokter'];
            $id_poli = $_POST['id_poli'];
            $SIP = $_POST['SIP'];
            $tempat_lhr = $_POST['tempat_lhr'];
            $no_hp = $_POST['no_hp'];
            $alamat = $_POST['alamat'];
            
            $stmt = $conn->prepare("UPDATE dokter SET nm_dokter = ?, id_poli = ?, SIP = ?, tempat_lhr = ?, no_hp = ?, alamat = ? WHERE id_dokter = ?");
            $stmt->bind_param("sissssi", $nm_dokter, $id_poli, $SIP, $tempat_lhr, $no_hp, $alamat, $id_dokter);
            
            if ($stmt->execute()) {
                $message = "Data dokter berhasil diperbarui.";
            } else {
                $message = "Error: " . $stmt->error;
            }
        } elseif ($_POST['action'] == 'delete') {
            $id_dokter = $_POST['id_dokter'];
            
            // Get user ID associated with doctor
            $stmt = $conn->prepare("SELECT id_user FROM dokter WHERE id_dokter = ?");
            $stmt->bind_param("i", $id_dokter);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $user_id = $row['id_user'];
            
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Delete doctor data
                $stmt = $conn->prepare("DELETE FROM dokter WHERE id_dokter = ?");
                $stmt->bind_param("i", $id_dokter);
                $stmt->execute();
                
                // Delete user account
                if ($user_id) {
                    $stmt = $conn->prepare("DELETE FROM login WHERE id_user = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                }
                
                // Commit transaction
                $conn->commit();
                $message = "Data dokter berhasil dihapus.";
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $message = "Error: " . $e->getMessage();
            }
        } elseif ($_POST['action'] == 'reset_password') {
            $id_dokter = $_POST['id_dokter'];
            
            // Get user ID
            $stmt = $conn->prepare("SELECT id_user FROM dokter WHERE id_dokter = ?");
            $stmt->bind_param("i", $id_dokter);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $user_id = $row['id_user'];
            
            if ($user_id) {
                // Reset password to default
                $default_password = 'dokter123';
                $new_password = password_hash($default_password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("UPDATE login SET password = ? WHERE id_user = ?");
                $stmt->bind_param("si", $new_password, $user_id);
                
                if ($stmt->execute()) {
                    $message = "Password dokter berhasil direset ke default ($default_password).";
                } else {
                    $message = "Error: " . $stmt->error;
                }
            } else {
                $message = "Error: User ID tidak ditemukan.";
            }
        }
    }
}

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = "WHERE d.nm_dokter LIKE '%$search%' OR d.SIP LIKE '%$search%' OR p.nm_poli LIKE '%$search%'";
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total records for pagination
$total_records_query = "SELECT COUNT(*) as count FROM dokter d LEFT JOIN poliklinik p ON d.id_poli = p.id_poli $search_condition";
$total_records_result = $conn->query($total_records_query);
$total_records = $total_records_result->fetch_assoc()['count'];
$total_pages = ceil($total_records / $limit);

// Get doctor data with pagination
$sql = "SELECT d.*, p.nm_poli FROM dokter d LEFT JOIN poliklinik p ON d.id_poli = p.id_poli $search_condition ORDER BY d.nm_dokter LIMIT $offset, $limit";
$result = $conn->query($sql);

// Get polyclinics for dropdown
$polyclinics = $conn->query("SELECT * FROM poliklinik ORDER BY nm_poli");
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
                <h2><i class="fas fa-user-md"></i> <?php echo $page_title; ?></h2>
                <button class="btn btn-primary" onclick="showAddModal()">
                    <i class="fas fa-plus"></i> Tambah Dokter
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
                        <input type="text" name="search" placeholder="Cari dokter..." value="<?php echo htmlspecialchars($search); ?>">
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
                                <th>Nama Dokter</th>
                                <th>SIP</th>
                                <th>Poliklinik</th>
                                <th>Tempat Lahir</th>
                                <th>No. HP</th>
                                <th>Alamat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['nm_dokter']; ?></td>
                                    <td><?php echo $row['SIP']; ?></td>
                                    <td><?php echo $row['nm_poli'] ?? 'Belum ditentukan'; ?></td>
                                    <td><?php echo $row['tempat_lhr']; ?></td>
                                    <td><?php echo $row['no_hp']; ?></td>
                                    <td><?php echo $row['alamat']; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-info" onclick="showEditModal(<?php echo $row['id_dokter']; ?>, '<?php echo addslashes($row['nm_dokter']); ?>', <?php echo $row['id_poli'] ?? 'null'; ?>, '<?php echo $row['SIP']; ?>', '<?php echo addslashes($row['tempat_lhr']); ?>', '<?php echo $row['no_hp']; ?>', '<?php echo addslashes($row['alamat']); ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $row['id_dokter']; ?>, '<?php echo addslashes($row['nm_dokter']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            
                                            <button class="btn btn-sm btn-warning" onclick="confirmResetPassword(<?php echo $row['id_dokter']; ?>, '<?php echo addslashes($row['nm_dokter']); ?>')">
                                                <i class="fas fa-key"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data dokter.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="pagination-link">
                            <i class="fas fa-chevron-left"></i> Sebelumnya
                        </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="pagination-link">
                            Selanjutnya <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Doctor Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-md-plus"></i> Tambah Dokter Baru</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="addDoctorForm">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="nm_dokter"><i class="fas fa-user"></i> Nama Dokter</label>
                        <input type="text" id="nm_dokter" name="nm_dokter" required placeholder="Dr. Nama Lengkap, Sp.XX">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="SIP"><i class="fas fa-id-card"></i> SIP (Surat Izin Praktik)</label>
                            <input type="text" id="SIP" name="SIP" required placeholder="SIP/001/2023">
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="id_poli"><i class="fas fa-hospital"></i> Poliklinik</label>
                            <select id="id_poli" name="id_poli" required>
                                <option value="">-- Pilih Poliklinik --</option>
                                <?php 
                                $polyclinics->data_seek(0); // Reset pointer
                                while ($poli = $polyclinics->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $poli['id_poli']; ?>"><?php echo $poli['nm_poli']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="tempat_lhr"><i class="fas fa-map-marker-alt"></i> Tempat Lahir</label>
                            <input type="text" id="tempat_lhr" name="tempat_lhr" placeholder="Jakarta">
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="no_hp"><i class="fas fa-mobile-alt"></i> No. HP</label>
                            <input type="text" id="no_hp" name="no_hp" required placeholder="081234567890">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="alamat"><i class="fas fa-home"></i> Alamat</label>
                        <textarea id="alamat" name="alamat" rows="3" placeholder="Alamat lengkap dokter"></textarea>
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
    
    <!-- Edit Doctor Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-edit"></i> Edit Data Dokter</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="editDoctorForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_id_dokter" name="id_dokter">
                    
                    <div class="form-group">
                        <label for="edit_nm_dokter"><i class="fas fa-user"></i> Nama Dokter</label>
                        <input type="text" id="edit_nm_dokter" name="nm_dokter" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_SIP"><i class="fas fa-id-card"></i> SIP (Surat Izin Praktik)</label>
                            <input type="text" id="edit_SIP" name="SIP" required>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="edit_id_poli"><i class="fas fa-hospital"></i> Poliklinik</label>
                            <select id="edit_id_poli" name="id_poli" required>
                                <option value="">-- Pilih Poliklinik --</option>
                                <?php 
                                $polyclinics->data_seek(0); // Reset pointer
                                while ($poli = $polyclinics->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $poli['id_poli']; ?>"><?php echo $poli['nm_poli']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_tempat_lhr"><i class="fas fa-map-marker-alt"></i> Tempat Lahir</label>
                            <input type="text" id="edit_tempat_lhr" name="tempat_lhr">
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="edit_no_hp"><i class="fas fa-mobile-alt"></i> No. HP</label>
                            <input type="text" id="edit_no_hp" name="no_hp" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_alamat"><i class="fas fa-home"></i> Alamat</label>
                        <textarea id="edit_alamat" name="alamat" rows="3"></textarea>
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
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus</h3>
                <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus dokter <span id="delete_name" class="font-weight-bold"></span>?</p>
                <p class="text-danger"><i class="fas fa-exclamation-circle"></i> Perhatian: Tindakan ini akan menghapus semua data terkait dokter dan tidak dapat dibatalkan!</p>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" id="delete_id_dokter" name="id_dokter">
                    
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
    
    <!-- Reset Password Confirmation Modal -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-key"></i> Reset Password</h3>
                <span class="close" onclick="closeModal('resetPasswordModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin mereset password untuk dokter <span id="reset_name" class="font-weight-bold"></span>?</p>
                <p>Password akan direset ke default: <strong>dokter123</strong></p>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" id="reset_id_dokter" name="id_dokter">
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key"></i> Reset Password
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('resetPasswordModal')">
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
        // Show add modal
        function showAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        // Show edit modal
        function showEditModal(id, name, poliId, sip, birthPlace, phone, address) {
            document.getElementById('edit_id_dokter').value = id;
            document.getElementById('edit_nm_dokter').value = name;
            document.getElementById('edit_id_poli').value = poliId || '';
            document.getElementById('edit_SIP').value = sip;
            document.getElementById('edit_tempat_lhr').value = birthPlace;
            document.getElementById('edit_no_hp').value = phone;
            document.getElementById('edit_alamat').value = address;
            
            document.getElementById('editModal').style.display = 'block';
        }
        
        // Show delete confirmation modal
        function confirmDelete(id, name) {
            document.getElementById('delete_id_dokter').value = id;
            document.getElementById('delete_name').textContent = name;
            
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        // Show reset password confirmation modal
        function confirmResetPassword(id, name) {
            document.getElementById('reset_id_dokter').value = id;
            document.getElementById('reset_name').textContent = name;
            
            document.getElementById('resetPasswordModal').style.display = 'block';
        }
        
        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
        
        // Export data
        function exportData(format) {
            const searchParam = '<?php echo urlencode($search); ?>';
            window.location.href = `export_doctors.php?format=${format}&search=${searchParam}`;
        }
        
        // Print data
        function printData() {
            window.print();
        }
        
        // Form validation
        document.getElementById('addDoctorForm').addEventListener('submit', function(e) {
            validateDoctorForm(e, this);
        });
        
        document.getElementById('editDoctorForm').addEventListener('submit', function(e) {
            validateDoctorForm(e, this);
        });
        
        function validateDoctorForm(e, form) {
            const phoneRegex = /^[\d\-+\s]+$/;
            const noHp = form.querySelector('[name="no_hp"]').value;
            
            if (noHp && !phoneRegex.test(noHp)) {
                e.preventDefault();
                alert('Format nomor HP tidak valid. Gunakan hanya angka, spasi, tanda hubung (-), atau tanda plus (+).');
                return false;
            }
            
            const sipRegex = /^[A-Z0-9\/\-]+$/i;
            const sip = form.querySelector('[name="SIP"]').value;
            
            if (sip && !sipRegex.test(sip)) {
                e.preventDefault();
                alert('Format SIP tidak valid. Gunakan huruf, angka, garis miring (/), atau tanda hubung (-).');
                return false;
            }
            
            return true;
        }
        
        // Auto-generate username based on doctor name
        document.getElementById('nm_dokter').addEventListener('input', function() {
            const name = this.value.toLowerCase().replace(/\s+/g, '.');
            // You can show a preview of the username that will be generated
            console.log('Username akan dibuat:', name);
        });
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
            max-width: 500px;
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
        
        .filter-options {
            display: flex;
            gap: 10px;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid #537D5D;
            color: #537D5D;
        }
        
        .btn-outline:hover {
            background-color: #537D5D;
            color: white;
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
        
        .text-danger {
            color: #dc3545;
        }
        
        @media (max-width: 768px) {
            .search-filter {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .search-form {
                width: 100%;
                max-width: none;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .action-buttons {
                flex-wrap: wrap;
            }
        }
        
        @media print {
            .sidebar, .main-header, .content-header, .search-filter, .action-buttons, .pagination, .main-footer {
                display: none !important;
            }
            
            .container {
                display: block;
            }
            
            .content {
                padding: 0;
                margin: 0;
            }
            
            .card {
                box-shadow: none;
                border: none;
            }
            
            .card-body {
                padding: 0;
            }
            
            body {
                font-size: 12pt;
            }
            
            table {
                width: 100%;
            }
        }
    </style>
</body>
</html>
