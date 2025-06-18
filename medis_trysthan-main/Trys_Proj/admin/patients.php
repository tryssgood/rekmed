<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Manajemen Pasien";
$message = '';

// Process form submission for adding/editing patient
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            // Generate medical record number
            $no_rm = generateMedicalRecordNumber($conn);
            $nm_pasien = $_POST['nm_pasien'];
            $tgl_lhr = $_POST['tgl_lhr'];
            $alamat = $_POST['alamat'];
            $telepon = $_POST['telepon'];
            $no_hp = $_POST['no_hp'];
            $jenis_kelamin = $_POST['jenis_kelamin'];
            $nm_kk = $_POST['nm_kk'];
            $hub_kel = $_POST['hub_kel'];
            
            // Create user account for patient
            $username = strtolower(str_replace(' ', '', $nm_pasien)) . rand(100, 999);
            $password = password_hash($no_rm, PASSWORD_DEFAULT); // Use medical record number as initial password
            $role = 'pasien';
            
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Insert user account
                $stmt = $conn->prepare("INSERT INTO login (username, password, role) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $username, $password, $role);
                $stmt->execute();
                $user_id = $conn->insert_id;
                
                // Insert patient data
                $stmt = $conn->prepare("INSERT INTO pasien (no_rm, nm_pasien, tgl_lhr, alamat, telepon, no_hp, jenis_kelamin, nm_kk, hub_kel, id_user) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssssi", $no_rm, $nm_pasien, $tgl_lhr, $alamat, $telepon, $no_hp, $jenis_kelamin, $nm_kk, $hub_kel, $user_id);
                $stmt->execute();
                
                // Commit transaction
                $conn->commit();
                $message = "Data pasien berhasil ditambahkan. Username: $username, Password awal: $no_rm";
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $message = "Error: " . $e->getMessage();
            }
        } elseif ($_POST['action'] == 'edit') {
            $id_pasien = $_POST['id_pasien'];
            $nm_pasien = $_POST['nm_pasien'];
            $tgl_lhr = $_POST['tgl_lhr'];
            $alamat = $_POST['alamat'];
            $telepon = $_POST['telepon'];
            $no_hp = $_POST['no_hp'];
            $jenis_kelamin = $_POST['jenis_kelamin'];
            $nm_kk = $_POST['nm_kk'];
            $hub_kel = $_POST['hub_kel'];
            
            $stmt = $conn->prepare("UPDATE pasien SET nm_pasien = ?, tgl_lhr = ?, alamat = ?, telepon = ?, no_hp = ?, jenis_kelamin = ?, nm_kk = ?, hub_kel = ? WHERE id_pasien = ?");
            $stmt->bind_param("ssssssssi", $nm_pasien, $tgl_lhr, $alamat, $telepon, $no_hp, $jenis_kelamin, $nm_kk, $hub_kel, $id_pasien);
            
            if ($stmt->execute()) {
                $message = "Data pasien berhasil diperbarui.";
            } else {
                $message = "Error: " . $stmt->error;
            }
        } elseif ($_POST['action'] == 'delete') {
            $id_pasien = $_POST['id_pasien'];
            
            // Get user ID associated with patient
            $stmt = $conn->prepare("SELECT id_user FROM pasien WHERE id_pasien = ?");
            $stmt->bind_param("i", $id_pasien);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $user_id = $row['id_user'];
            
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Delete patient data
                $stmt = $conn->prepare("DELETE FROM pasien WHERE id_pasien = ?");
                $stmt->bind_param("i", $id_pasien);
                $stmt->execute();
                
                // Delete user account
                if ($user_id) {
                    $stmt = $conn->prepare("DELETE FROM login WHERE id_user = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                }
                
                // Commit transaction
                $conn->commit();
                $message = "Data pasien berhasil dihapus.";
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $message = "Error: " . $e->getMessage();
            }
        } elseif ($_POST['action'] == 'reset_password') {
            $id_pasien = $_POST['id_pasien'];
            
            // Get user ID and medical record number
            $stmt = $conn->prepare("SELECT id_user, no_rm FROM pasien WHERE id_pasien = ?");
            $stmt->bind_param("i", $id_pasien);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $user_id = $row['id_user'];
            $no_rm = $row['no_rm'];
            
            if ($user_id) {
                // Reset password to medical record number
                $new_password = password_hash($no_rm, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("UPDATE login SET password = ? WHERE id_user = ?");
                $stmt->bind_param("si", $new_password, $user_id);
                
                if ($stmt->execute()) {
                    $message = "Password pasien berhasil direset ke nomor rekam medis ($no_rm).";
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
    $search_condition = "WHERE nm_pasien LIKE '%$search%' OR no_rm LIKE '%$search%' OR alamat LIKE '%$search%' OR no_hp LIKE '%$search%'";
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total records for pagination
$total_records_query = "SELECT COUNT(*) as count FROM pasien $search_condition";
$total_records_result = $conn->query($total_records_query);
$total_records = $total_records_result->fetch_assoc()['count'];
$total_pages = ceil($total_records / $limit);

// Get patient data with pagination
$sql = "SELECT * FROM pasien $search_condition ORDER BY nm_pasien LIMIT $offset, $limit";
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
                <h2><i class="fas fa-user-injured"></i> <?php echo $page_title; ?></h2>
                <button class="btn btn-primary" onclick="showAddModal()">
                    <i class="fas fa-plus"></i> Tambah Pasien
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
                        <input type="text" name="search" placeholder="Cari pasien..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="search-button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                
                <div class="filter-options">
                    <button class="btn btn-sm btn-outline" onclick="exportData('csv')">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </button>
                    <button class="btn btn-sm btn-outline" onclick="exportData('pdf')">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                    <button class="btn btn-sm btn-outline" onclick="printData()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No. RM</th>
                                <th>Nama Pasien</th>
                                <th>Tanggal Lahir</th>
                                <th>Jenis Kelamin</th>
                                <th>Alamat</th>
                                <th>No. HP</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['no_rm']; ?></td>
                                    <td><?php echo $row['nm_pasien']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['tgl_lhr'])); ?></td>
                                    <td><?php echo $row['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></td>
                                    <td><?php echo $row['alamat']; ?></td>
                                    <td><?php echo $row['no_hp']; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-info" onclick="showEditModal(<?php echo $row['id_pasien']; ?>, '<?php echo $row['nm_pasien']; ?>', '<?php echo $row['tgl_lhr']; ?>', '<?php echo addslashes($row['alamat']); ?>', '<?php echo $row['telepon']; ?>', '<?php echo $row['no_hp']; ?>', '<?php echo $row['jenis_kelamin']; ?>', '<?php echo addslashes($row['nm_kk']); ?>', '<?php echo addslashes($row['hub_kel']); ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $row['id_pasien']; ?>, '<?php echo $row['nm_pasien']; ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                          
                                            <button class="btn btn-sm btn-warning" onclick="confirmResetPassword(<?php echo $row['id_pasien']; ?>, '<?php echo $row['nm_pasien']; ?>')">
                                                <i class="fas fa-key"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data pasien.</td>
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
    
    <!-- Add Patient Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Tambah Pasien Baru</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="addPatientForm">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="nm_pasien"><i class="fas fa-user"></i> Nama Pasien</label>
                        <input type="text" id="nm_pasien" name="nm_pasien" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="tgl_lhr"><i class="fas fa-calendar"></i> Tanggal Lahir</label>
                            <input type="date" id="tgl_lhr" name="tgl_lhr" required>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="jenis_kelamin"><i class="fas fa-venus-mars"></i> Jenis Kelamin</label>
                            <select id="jenis_kelamin" name="jenis_kelamin" required>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="alamat"><i class="fas fa-map-marker-alt"></i> Alamat</label>
                        <textarea id="alamat" name="alamat" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="telepon"><i class="fas fa-phone"></i> Telepon</label>
                            <input type="text" id="telepon" name="telepon">
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="no_hp"><i class="fas fa-mobile-alt"></i> No. HP</label>
                            <input type="text" id="no_hp" name="no_hp" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="nm_kk"><i class="fas fa-users"></i> Nama Kepala Keluarga</label>
                            <input type="text" id="nm_kk" name="nm_kk">
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="hub_kel"><i class="fas fa-heart"></i> Hubungan Keluarga</label>
                            <input type="text" id="hub_kel" name="hub_kel">
                        </div>
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
    
    <!-- Edit Patient Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-edit"></i> Edit Data Pasien</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="editPatientForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_id_pasien" name="id_pasien">
                    
                    <div class="form-group">
                        <label for="edit_nm_pasien"><i class="fas fa-user"></i> Nama Pasien</label>
                        <input type="text" id="edit_nm_pasien" name="nm_pasien" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_tgl_lhr"><i class="fas fa-calendar"></i> Tanggal Lahir</label>
                            <input type="date" id="edit_tgl_lhr" name="tgl_lhr" required>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="edit_jenis_kelamin"><i class="fas fa-venus-mars"></i> Jenis Kelamin</label>
                            <select id="edit_jenis_kelamin" name="jenis_kelamin" required>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_alamat"><i class="fas fa-map-marker-alt"></i> Alamat</label>
                        <textarea id="edit_alamat" name="alamat" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_telepon"><i class="fas fa-phone"></i> Telepon</label>
                            <input type="text" id="edit_telepon" name="telepon">
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="edit_no_hp"><i class="fas fa-mobile-alt"></i> No. HP</label>
                            <input type="text" id="edit_no_hp" name="no_hp" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_nm_kk"><i class="fas fa-users"></i> Nama Kepala Keluarga</label>
                            <input type="text" id="edit_nm_kk" name="nm_kk">
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="edit_hub_kel"><i class="fas fa-heart"></i> Hubungan Keluarga</label>
                            <input type="text" id="edit_hub_kel" name="hub_kel">
                        </div>
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
                <p>Apakah Anda yakin ingin menghapus pasien <span id="delete_name" class="font-weight-bold"></span>?</p>
                <p class="text-danger"><i class="fas fa-exclamation-circle"></i> Perhatian: Tindakan ini akan menghapus semua data rekam medis pasien dan tidak dapat dibatalkan!</p>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" id="delete_id_pasien" name="id_pasien">
                    
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
                <p>Apakah Anda yakin ingin mereset password untuk pasien <span id="reset_name" class="font-weight-bold"></span>?</p>
                <p>Password akan direset ke nomor rekam medis pasien.</p>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" id="reset_id_pasien" name="id_pasien">
                    
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
        function showEditModal(id, name, birthdate, address, phone, mobile, gender, familyHead, familyRelation) {
            document.getElementById('edit_id_pasien').value = id;
            document.getElementById('edit_nm_pasien').value = name;
            document.getElementById('edit_tgl_lhr').value = birthdate;
            document.getElementById('edit_alamat').value = address;
            document.getElementById('edit_telepon').value = phone;
            document.getElementById('edit_no_hp').value = mobile;
            document.getElementById('edit_jenis_kelamin').value = gender;
            document.getElementById('edit_nm_kk').value = familyHead;
            document.getElementById('edit_hub_kel').value = familyRelation;
            
            document.getElementById('editModal').style.display = 'block';
        }
        
        // Show delete confirmation modal
        function confirmDelete(id, name) {
            document.getElementById('delete_id_pasien').value = id;
            document.getElementById('delete_name').textContent = name;
            
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        // Show reset password confirmation modal
        function confirmResetPassword(id, name) {
            document.getElementById('reset_id_pasien').value = id;
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
            window.location.href = `export_patients.php?format=${format}&search=${searchParam}`;
        }
        
        // Print data
        function printData() {
            window.print();
        }
        
        // Form validation
        document.getElementById('addPatientForm').addEventListener('submit', function(e) {
            validatePatientForm(e, this);
        });
        
        document.getElementById('editPatientForm').addEventListener('submit', function(e) {
            validatePatientForm(e, this);
        });
        
        function validatePatientForm(e, form) {
            const phoneRegex = /^[\d\-+\s]+$/;
            const noHp = form.querySelector('[name="no_hp"]').value;
            
            if (noHp && !phoneRegex.test(noHp)) {
                e.preventDefault();
                alert('Format nomor HP tidak valid. Gunakan hanya angka, spasi, tanda hubung (-), atau tanda plus (+).');
                return false;
            }
            
            const telepon = form.querySelector('[name="telepon"]').value;
            if (telepon && !phoneRegex.test(telepon)) {
                e.preventDefault();
                alert('Format nomor telepon tidak valid. Gunakan hanya angka, spasi, tanda hubung (-), atau tanda plus (+).');
                return false;
            }
            
            return true;
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
